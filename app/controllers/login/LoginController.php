<?php
require_once __DIR__ . '/../../models/UsuarioModel.php';
require_once __DIR__ . '/../../utils/Security.php';

/**
 * Controlador para el módulo de login y autenticación
 */
class LoginController {
    private $usuarioModel;
    private $security;
    
    // Límite de intentos de login fallidos
    private $maxLoginAttempts = 5;
    // Tiempo de bloqueo en segundos (30 minutos)
    private $lockoutTime = 1800;
    
    /**
     * Constructor
     * @param PDO|null $pdo Conexión PDO opcional
     */
    public function __construct($pdo = null) {
        $this->usuarioModel = new UsuarioModel($pdo);
        $this->security = new Security();
    }
    
    /**
     * Autenticar usuario
     * @param string $email Email/usuario
     * @param string $password Contraseña
     * @param string $captchaResponse Respuesta del captcha (opcional)
     * @return array Resultado con status y mensaje
     */
    public function authenticate($email, $password, $captchaResponse = '') {
        // Validar datos
        if (empty($email) || empty($password)) {
            return [
                'status' => 'error',
                'message' => 'Todos los campos son obligatorios'
            ];
        }
        
        // Sanitizar datos de entrada
        $email = $this->security->sanitizeInput($email);
        // No sanitizamos password para no afectar la verificación
        
        // Verificar si se requiere captcha (después de ciertos intentos fallidos)
        if ($this->requiresCaptcha($email) && empty($captchaResponse)) {
            return [
                'status' => 'error',
                'message' => 'Por favor complete el captcha',
                'requires_captcha' => true
            ];
        }
        
        // Verificar si la cuenta está bloqueada por muchos intentos fallidos
        if ($this->isAccountLocked($email)) {
            return [
                'status' => 'error',
                'message' => 'Su cuenta ha sido temporalmente bloqueada por seguridad. Por favor, inténtelo más tarde o contacte al administrador.',
                'account_locked' => true
            ];
        }
        
        // Verificar credenciales
        $user = $this->usuarioModel->validateCredentials($email, $password);
        
        if ($user) {
            // Iniciar sesión
            session_start();
            
            // Generar un nuevo ID de sesión para prevenir ataques de fijación de sesión
            session_regenerate_id(true);
            
            // Almacenar datos de sesión
            $_SESSION['sesion_email'] = $user['email'];
            $_SESSION['id_usuario'] = $user['id_usuario'];
            $_SESSION['nombres'] = $user['nombres'];
            $_SESSION['rol'] = $this->getRol($user['id_rol']);
            
            // Información adicional de seguridad de la sesión
            $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
            $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
            $_SESSION['last_activity'] = time();
            
            // Limpiar intentos fallidos
            $this->clearLoginAttempts($email);
            
            // Registrar login exitoso
            $this->logActivity('login_success', $user['id_usuario'], $email);
            
            return [
                'status' => 'success',
                'message' => 'Inicio de sesión exitoso',
                'user' => [
                    'id' => $user['id_usuario'],
                    'nombres' => $user['nombres'],
                    'email' => $user['email'],
                    'rol' => $_SESSION['rol']
                ]
            ];
        } else {
            // Incrementar contador de intentos fallidos
            $this->incrementLoginAttempts($email);
            
            // Determinar si se requiere captcha para el próximo intento
            $needsCaptcha = $this->requiresCaptcha($email);
            
            // Registrar intento fallido
            $this->logActivity('login_failed', 0, $email);
            
            return [
                'status' => 'error',
                'message' => 'Email o contraseña incorrectos',
                'requires_captcha' => $needsCaptcha
            ];
        }
    }
    
    /**
     * Cerrar sesión
     * @return array Resultado con status y mensaje
     */
    public function logout() {
        // Iniciar sesión (en caso de que no esté iniciada)
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        // Registrar logout
        if (isset($_SESSION['id_usuario'])) {
            $this->logActivity('logout', $_SESSION['id_usuario'], $_SESSION['sesion_email'] ?? 'desconocido');
        }
        
        // Destruir todas las variables de sesión
        $_SESSION = [];
        
        // Destruir la cookie de sesión si existe
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Destruir la sesión
        session_destroy();
        
        return [
            'status' => 'success',
            'message' => 'Sesión cerrada correctamente'
        ];
    }
    
    /**
     * Recuperar contraseña (generar token)
     * @param string $email Email del usuario
     * @return array Resultado con status y mensaje
     */
    public function recoverPassword($email) {
        // Validar email
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'status' => 'error',
                'message' => 'Debe proporcionar un email válido'
            ];
        }
        
        // Sanitizar email
        $email = $this->security->sanitizeInput($email);
        
        try {
            // Verificar si el usuario existe
            $user = $this->usuarioModel->findByEmail($email);
            
            if (!$user) {
                // Por seguridad, no revelamos si el email existe o no
                return [
                    'status' => 'success',
                    'message' => 'Si el email existe en nuestro sistema, recibirá instrucciones para reestablecer su contraseña'
                ];
            }
            
            // Generar token seguro con tiempo de expiración
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', time() + 3600); // Expira en 1 hora
            
            // Almacenar token y fecha de expiración en la base de datos
            $updateData = [
                'token' => $token,
                'token_expiry' => $expires,
                'fyh_actualizacion' => date('Y-m-d H:i:s')
            ];
            
            $updated = $this->usuarioModel->update($user['id_usuario'], $updateData);
            
            if ($updated === true) {
                // En un sistema real, aquí enviaríamos un email con el link de recuperación
                // Registrar generación de token
                $this->logActivity('password_recovery', $user['id_usuario'], $email);
                
                global $URL;
                $resetUrl = "$URL/login/reset_password.php?token=$token";
                
                // Simulamos que se ha enviado correctamente
                return [
                    'status' => 'success',
                    'message' => 'Se ha enviado un correo con instrucciones para reestablecer tu contraseña',
                    'debug_info' => [
                        'reset_url' => $resetUrl,
                        'token' => $token,
                        'expires' => $expires
                    ] // Solo para desarrollo
                ];
            } else {
                return [
                    'status' => 'error',
                    'message' => 'Error al procesar la solicitud'
                ];
            }
        } catch (Exception $e) {
            error_log("Error en recuperación de contraseña: " . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Error interno del sistema'
            ];
        }
    }
    
    /**
     * Validar token de recuperación
     * @param string $token Token a validar
     * @return array|false Datos del usuario o false si no es válido
     */
    public function validateToken($token) {
        if (empty($token)) {
            return false;
        }
        
        // Sanitizar token
        $token = $this->security->sanitizeInput($token);
        
        try {
            $sql = "SELECT * FROM tb_usuarios WHERE token = :token AND token_expiry > NOW()";
            $stmt = $this->usuarioModel->getPdo()->prepare($sql);
            $stmt->bindParam(':token', $token, PDO::PARAM_STR);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                return $stmt->fetch(PDO::FETCH_ASSOC);
            }
            
            return false;
        } catch (PDOException $e) {
            error_log("Error al validar token: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Reestablecer contraseña con token
     * @param string $token Token de verificación
     * @param string $password Nueva contraseña
     * @param string $confirmPassword Confirmación de contraseña
     * @return array Resultado con status y mensaje
     */
    public function resetPassword($token, $password, $confirmPassword) {
        // Validar datos
        if (empty($token) || empty($password) || empty($confirmPassword)) {
            return [
                'status' => 'error',
                'message' => 'Todos los campos son obligatorios'
            ];
        }
        
        // Sanitizar token
        $token = $this->security->sanitizeInput($token);
        
        // Verificar que las contraseñas coinciden
        if ($password !== $confirmPassword) {
            return [
                'status' => 'error',
                'message' => 'Las contraseñas no coinciden'
            ];
        }
        
        // Verificar que la contraseña tenga al menos 8 caracteres
        if (strlen($password) < 8) {
            return [
                'status' => 'error',
                'message' => 'La contraseña debe tener al menos 8 caracteres'
            ];
        }
        
        // Verificar complejidad de la contraseña
        if (!$this->security->isStrongPassword($password)) {
            return [
                'status' => 'error',
                'message' => 'La contraseña debe incluir números, letras mayúsculas y minúsculas, y al menos un carácter especial'
            ];
        }
        
        // Validar token
        $user = $this->validateToken($token);
        
        if (!$user) {
            return [
                'status' => 'error',
                'message' => 'Token inválido o expirado'
            ];
        }
        
        try {
            // Actualizar contraseña
            $result = $this->usuarioModel->updatePassword($user['id_usuario'], $password);
            
            if ($result === true) {
                // Invalidar token
                $this->usuarioModel->update($user['id_usuario'], [
                    'token' => '',
                    'token_expiry' => null,
                    'fyh_actualizacion' => date('Y-m-d H:i:s')
                ]);
                
                // Registrar cambio de contraseña
                $this->logActivity('password_reset', $user['id_usuario'], $user['email']);
                
                return [
                    'status' => 'success',
                    'message' => 'Contraseña actualizada correctamente'
                ];
            } else {
                return [
                    'status' => 'error',
                    'message' => 'Error al actualizar la contraseña'
                ];
            }
        } catch (Exception $e) {
            error_log("Error en resetPassword: " . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Error interno del sistema'
            ];
        }
    }
    
    /**
     * Verificar la seguridad de la sesión actual
     * @return bool|array True si la sesión es válida, array con error si hay problemas
     */
    public function verifySessionSecurity() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        // Verificar si existen las variables de seguridad
        if (!isset($_SESSION['ip_address']) || !isset($_SESSION['user_agent']) || !isset($_SESSION['last_activity'])) {
            return [
                'status' => 'error',
                'message' => 'Sesión no inicializada correctamente',
                'action' => 'logout'
            ];
        }
        
        // Verificar la dirección IP (podría ser un problema con conexiones móviles o VPN)
        if ($_SESSION['ip_address'] !== $_SERVER['REMOTE_ADDR']) {
            $this->security->logSuspiciousActivity(
                'session_hijacking_attempt', 
                "IP original: {$_SESSION['ip_address']}, IP actual: {$_SERVER['REMOTE_ADDR']}"
            );
            
            return [
                'status' => 'error',
                'message' => 'Su dirección IP ha cambiado. Por razones de seguridad, debe iniciar sesión nuevamente.',
                'action' => 'logout'
            ];
        }
        
        // Verificar el User-Agent
        if ($_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
            $this->security->logSuspiciousActivity(
                'session_hijacking_attempt', 
                "User-Agent original: {$_SESSION['user_agent']}, User-Agent actual: {$_SERVER['HTTP_USER_AGENT']}"
            );
            
            return [
                'status' => 'error',
                'message' => 'Su navegador ha cambiado. Por razones de seguridad, debe iniciar sesión nuevamente.',
                'action' => 'logout'
            ];
        }
        
        // Verificar inactividad (30 minutos = 1800 segundos)
        $inactivityLimit = 1800;
        if (time() - $_SESSION['last_activity'] > $inactivityLimit) {
            return [
                'status' => 'error',
                'message' => 'Su sesión ha expirado por inactividad. Por favor, inicie sesión nuevamente.',
                'action' => 'logout'
            ];
        }
        
        // Actualizar tiempo de última actividad
        $_SESSION['last_activity'] = time();
        
        // Regenerar ID de sesión periódicamente para mayor seguridad (cada 30 minutos)
        if (!isset($_SESSION['last_regeneration']) || (time() - $_SESSION['last_regeneration'] > 1800)) {
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
        
        return true;
    }
    
    /**
     * Obtiene el nombre del rol a partir del ID
     * @param int $rolId ID del rol
     * @return string Nombre del rol
     */
    private function getRol($rolId) {
        try {
            $sql = "SELECT rol FROM tb_roles WHERE id_rol = :id_rol";
            $stmt = $this->usuarioModel->getPdo()->prepare($sql);
            $stmt->bindParam(':id_rol', $rolId, PDO::PARAM_INT);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                return $result['rol'];
            }
            
            return 'usuario'; // Rol por defecto si no se encuentra
        } catch (PDOException $e) {
            error_log("Error al obtener rol: " . $e->getMessage());
            return 'usuario'; // Rol por defecto en caso de error
        }
    }
    
    /**
     * Verifica si una cuenta está bloqueada por muchos intentos fallidos
     * @param string $email Email del usuario
     * @return bool True si la cuenta está bloqueada
     */
    private function isAccountLocked($email) {
        $lockFile = $this->getLockFilePath($email);
        
        if (!file_exists($lockFile)) {
            return false;
        }
        
        $lockData = json_decode(file_get_contents($lockFile), true);
        
        // Verificar si el tiempo de bloqueo ha expirado
        if (time() > $lockData['expires']) {
            unlink($lockFile); // Eliminar archivo de bloqueo
            return false;
        }
        
        return true;
    }
    
    /**
     * Verifica si se requiere captcha para el siguiente intento de login
     * @param string $email Email del usuario
     * @return bool True si se requiere captcha
     */
    private function requiresCaptcha($email) {
        $attemptsFile = $this->getAttemptsFilePath($email);
        
        if (!file_exists($attemptsFile)) {
            return false;
        }
        
        $attempts = json_decode(file_get_contents($attemptsFile), true);
        
        // Si hay más de 3 intentos fallidos, requerir captcha
        return ($attempts['count'] >= 3);
    }
    
    /**
     * Incrementa el contador de intentos fallidos
     * @param string $email Email del usuario
     * @return void
     */
    private function incrementLoginAttempts($email) {
        $attemptsFile = $this->getAttemptsFilePath($email);
        
        if (file_exists($attemptsFile)) {
            $attempts = json_decode(file_get_contents($attemptsFile), true);
            $attempts['count']++;
        } else {
            $attempts = [
                'email' => $email,
                'count' => 1,
                'first_attempt' => time()
            ];
        }
        
        file_put_contents($attemptsFile, json_encode($attempts));
        
        // Si se supera el límite de intentos, bloquear la cuenta
        if ($attempts['count'] >= $this->maxLoginAttempts) {
            $this->lockAccount($email);
        }
    }
    
    /**
     * Bloquea una cuenta después de muchos intentos fallidos
     * @param string $email Email del usuario
     * @return void
     */
    private function lockAccount($email) {
        $lockFile = $this->getLockFilePath($email);
        
        $lockData = [
            'email' => $email,
            'locked_at' => time(),
            'expires' => time() + $this->lockoutTime
        ];
        
        file_put_contents($lockFile, json_encode($lockData));
        
        // Registrar bloqueo
        $this->security->logSuspiciousActivity(
            'account_locked', 
            "Cuenta $email bloqueada después de {$this->maxLoginAttempts} intentos fallidos"
        );
    }
    
    /**
     * Limpia los intentos fallidos de login
     * @param string $email Email del usuario
     * @return void
     */
    private function clearLoginAttempts($email) {
        $attemptsFile = $this->getAttemptsFilePath($email);
        $lockFile = $this->getLockFilePath($email);
        
        if (file_exists($attemptsFile)) {
            unlink($attemptsFile);
        }
        
        if (file_exists($lockFile)) {
            unlink($lockFile);
        }
    }
    
    /**
     * Obtiene la ruta del archivo de intentos fallidos
     * @param string $email Email del usuario
     * @return string Ruta del archivo
     */
    private function getAttemptsFilePath($email) {
        $safeEmail = preg_replace('/[^a-zA-Z0-9]/', '_', $email);
        $dir = __DIR__ . '/../../../logs/auth';
        
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
        
        return $dir . '/attempts_' . md5($safeEmail) . '.json';
    }
    
    /**
     * Obtiene la ruta del archivo de bloqueo de cuenta
     * @param string $email Email del usuario
     * @return string Ruta del archivo
     */
    private function getLockFilePath($email) {
        $safeEmail = preg_replace('/[^a-zA-Z0-9]/', '_', $email);
        $dir = __DIR__ . '/../../../logs/auth';
        
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
        
        return $dir . '/lock_' . md5($safeEmail) . '.json';
    }
    
    /**
     * Registra actividad de autenticación en el log
     * @param string $activity Tipo de actividad
     * @param int $userId ID del usuario
     * @param string $email Email del usuario
     * @return void
     */
    private function logActivity($activity, $userId, $email) {
        $logDir = __DIR__ . '/../../../logs';
        if (!file_exists($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $logFile = $logDir . '/auth.log';
        $date = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        $logMessage = "[$date] Activity: $activity, User ID: $userId, Email: $email, IP: $ip, User-Agent: $userAgent\n";
        error_log($logMessage, 3, $logFile);
    }
}