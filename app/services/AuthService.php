<?php
require_once __DIR__ . '/../utils/Security.php';

/**
 * Servicio de autenticación y gestión de contraseñas
 */
class AuthService {
    private $pdo;
    private $security;
    private $logger;
    private $table = 'tb_usuarios';
    
    /**
     * Constructor
     * @param PDO $pdo Conexión PDO
     * @param SecurityLogger $logger Logger de seguridad
     */
    public function __construct($pdo, $logger) {
        $this->pdo = $pdo;
        $this->security = new Security();
        $this->logger = $logger;
    }
    
    /**
     * Autentica un usuario verificando credenciales
     * @param string $email Email del usuario
     * @param string $password Contraseña sin cifrar
     * @return array|false Datos del usuario o false si falla la autenticación
     */
    public function authenticateUser($email, $password) {
        // Verificar intentos fallidos
        if ($this->tooManyFailedAttempts($email)) {
            $this->logger->logActivity('login_blocked', 0, "Bloqueo de login para $email por demasiados intentos fallidos");
            return false;
        }
        
        // Obtener usuario por email
        $user = $this->getUserByEmail($email);
        
        if ($user && password_verify($password, $user['password_user'])) {
            // Verificar si el hash necesita ser actualizado
            if (password_needs_rehash($user['password_user'], PASSWORD_ARGON2ID)) {
                // Actualizar el hash a un algoritmo más seguro
                $newHash = $this->hashPassword($password);
                $this->updatePasswordHash($user['id_usuario'], $newHash);
            }
            
            // Restablecer contador de intentos fallidos
            $this->resetFailedAttempts($email);
            
            // Registrar login exitoso
            $this->logger->logActivity('login_success', $user['id_usuario'], 'Login exitoso');
            
            return $user;
        }
        
        // Incrementar contador de intentos fallidos
        $this->recordFailedAttempt($email);
        
        // Registrar intento fallido
        $this->logger->logActivity('login_failed', 0, "Intento fallido para $email");
        
        return false;
    }
    
    /**
     * Verifica si una contraseña coincide con la del usuario
     * @param int $userId ID del usuario
     * @param string $password Contraseña sin cifrar para verificar
     * @return bool True si coincide, false en caso contrario
     */
    public function verifyUserPassword($userId, $password) {
        $sql = "SELECT password_user FROM {$this->table} WHERE id_usuario = :id_usuario";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':id_usuario', $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password_user'])) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Actualiza la contraseña de un usuario con validaciones de seguridad
     * @param int $userId ID del usuario
     * @param string $password Nueva contraseña (sin cifrar)
     * @param bool $requiresOldPassword Si se requiere la contraseña actual
     * @param string $oldPassword Contraseña actual (si se requiere)
     * @return bool|string True si se actualizó o mensaje de error
     */
    public function updateUserPassword($userId, $password, $requiresOldPassword = false, $oldPassword = '') {
        try {
            // Validar longitud de la contraseña
            if (strlen($password) < 8) {
                return "La contraseña debe tener al menos 8 caracteres";
            }
            
            // Verificar complejidad de la contraseña
            if (!$this->security->isStrongPassword($password)) {
                return "La contraseña debe incluir números, letras mayúsculas y minúsculas, y al menos un carácter especial";
            }
            
            // Verificar contraseña actual si es requerido
            if ($requiresOldPassword) {
                if (!$this->verifyUserPassword($userId, $oldPassword)) {
                    return "La contraseña actual es incorrecta";
                }
            }
            
            // Cifrar la nueva contraseña
            $hashedPassword = $this->hashPassword($password);
            
            // Actualizar contraseña en la base de datos
            $sql = "UPDATE {$this->table} SET 
                    password_user = :password, 
                    fyh_actualizacion = :fyh_actualizacion
                    WHERE id_usuario = :id_usuario";
                    
            $stmt = $this->pdo->prepare($sql);
            $fyh_actualizacion = date('Y-m-d H:i:s');
            
            $stmt->bindParam(':password', $hashedPassword, PDO::PARAM_STR);
            $stmt->bindParam(':fyh_actualizacion', $fyh_actualizacion);
            $stmt->bindParam(':id_usuario', $userId, PDO::PARAM_INT);
            
            $result = $stmt->execute();
            
            if ($result && $stmt->rowCount() > 0) {
                // Registrar el cambio de contraseña
                $this->logger->logActivity('update_password', $userId, 'Contraseña actualizada');
                return true;
            } else {
                return "No se pudo actualizar la contraseña";
            }
        } catch (PDOException $e) {
            $this->logger->logError("Error al actualizar contraseña: " . $e->getMessage());
            return "Error interno al actualizar la contraseña";
        }
    }
    
    /**
     * Genera un hash seguro para contraseñas
     * @param string $password Contraseña sin cifrar
     * @return string Hash de la contraseña
     */
    public function hashPassword($password) {
        return password_hash(
            $password, 
            PASSWORD_ARGON2ID, 
            ['memory_cost' => 2048, 'time_cost' => 4, 'threads' => 3]
        );
    }
    
    /**
     * Actualiza el hash de contraseña (sin cambiar la contraseña)
     * @param int $userId ID del usuario
     * @param string $newHash Nuevo hash de contraseña
     * @return bool Resultado de la operación
     */
    private function updatePasswordHash($userId, $newHash) {
        try {
            $sql = "UPDATE {$this->table} SET password_user = :password_user WHERE id_usuario = :id_usuario";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':password_user', $newHash, PDO::PARAM_STR);
            $stmt->bindParam(':id_usuario', $userId, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            $this->logger->logError("Error al actualizar hash de contraseña: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtiene un usuario por su email
     * @param string $email Email del usuario
     * @return array|false Datos del usuario o false
     */
    private function getUserByEmail($email) {
        try {
            $email = $this->security->sanitizeInput($email);
            
            $sql = "SELECT * FROM {$this->table} WHERE email = :email";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logger->logError("Error en getUserByEmail: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verifica si hay demasiados intentos fallidos de login
     * @param string $email Email del usuario
     * @return bool True si hay demasiados intentos
     */
    private function tooManyFailedAttempts($email) {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['login_attempts'])) {
            $_SESSION['login_attempts'] = [];
        }
        
        if (!isset($_SESSION['login_attempts'][$email])) {
            $_SESSION['login_attempts'][$email] = [
                'count' => 0,
                'last_attempt' => 0
            ];
        }
        
        $attempts = $_SESSION['login_attempts'][$email];
        $currentTime = time();
        
        // Si han pasado más de 30 minutos desde el último intento, reiniciar contador
        if ($currentTime - $attempts['last_attempt'] > 1800) {
            $_SESSION['login_attempts'][$email]['count'] = 0;
            $_SESSION['login_attempts'][$email]['last_attempt'] = $currentTime;
            return false;
        }
        
        // Bloquear después de 5 intentos fallidos
        return $attempts['count'] >= 5;
    }
    
    /**
     * Reinicia el contador de intentos fallidos
     * @param string $email Email del usuario
     * @return void
     */
    private function resetFailedAttempts($email) {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        if (isset($_SESSION['login_attempts']) && isset($_SESSION['login_attempts'][$email])) {
            $_SESSION['login_attempts'][$email]['count'] = 0;
            $_SESSION['login_attempts'][$email]['last_attempt'] = time();
        }
    }
    
    /**
     * Registra un intento fallido de login
     * @param string $email Email del usuario
     * @return void
     */
    private function recordFailedAttempt($email) {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['login_attempts'])) {
            $_SESSION['login_attempts'] = [];
        }
        
        if (!isset($_SESSION['login_attempts'][$email])) {
            $_SESSION['login_attempts'][$email] = [
                'count' => 0,
                'last_attempt' => 0
            ];
        }
        
        $_SESSION['login_attempts'][$email]['count']++;
        $_SESSION['login_attempts'][$email]['last_attempt'] = time();
    }
}