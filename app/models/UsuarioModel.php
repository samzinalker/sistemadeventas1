<?php
require_once __DIR__ . '/../core/Model.php';
require_once __DIR__ . '/../utils/Security.php';
require_once __DIR__ . '/../services/AuthService.php';
require_once __DIR__ . '/../services/SecurityLogger.php';

/**
 * Modelo para gestión de usuarios
 */
class UsuarioModel extends Model {
    protected $table = 'tb_usuarios';
    protected $primaryKey = 'id_usuario';
    protected $security;
    protected $authService;
    protected $logger;
    
    /**
     * Constructor
     * @param PDO|null $pdo Conexión PDO opcional
     */
    public function __construct($pdo = null) {
        parent::__construct($pdo);
        $this->security = new Security();
        $this->logger = new SecurityLogger($pdo);
        $this->authService = new AuthService($pdo, $this->logger);
    }
    
    /**
     * Obtiene todos los usuarios con sus roles
     * @return array Lista de usuarios
     */
    public function getAllWithRoles() {
        try {
            $sql = "SELECT u.*, r.rol 
                    FROM {$this->table} u
                    INNER JOIN tb_roles r ON u.id_rol = r.id_rol
                    ORDER BY u.id_usuario DESC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            
            $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Sanitizar datos para prevenir XSS
            foreach ($usuarios as $key => $usuario) {
                $usuarios[$key] = $this->security->sanitizeOutput($usuario);
            }
            
            return $usuarios;
        } catch (PDOException $e) {
            $this->logger->logError("Error en getAllWithRoles: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtiene un usuario por su ID con información de rol
     * @param int $id ID del usuario
     * @return array|false Datos del usuario o false
     */
    public function getByIdWithRole($id) {
        try {
            $sql = "SELECT u.*, r.rol 
                    FROM {$this->table} u
                    INNER JOIN tb_roles r ON u.id_rol = r.id_rol
                    WHERE u.id_usuario = :id";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Sanitizar datos para prevenir XSS
            if ($usuario) {
                $usuario = $this->security->sanitizeOutput($usuario);
            }
            
            return $usuario;
        } catch (PDOException $e) {
            $this->logger->logError("Error en getByIdWithRole: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Busca un usuario por su email
     * @param string $email Email del usuario
     * @return array|false Datos del usuario o false
     */
    public function findByEmail($email) {
        try {
            $email = $this->security->sanitizeInput($email);
            
            $sql = "SELECT * FROM {$this->table} WHERE email = :email";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logger->logError("Error en findByEmail: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Crea un nuevo usuario con validaciones de seguridad
     * @param array $data Datos del usuario
     * @return int|string ID del usuario insertado o mensaje de error
     */
    public function createUser(array $data) {
        // Validar datos
        $validationErrors = $this->validateUserData($data);
        if (!empty($validationErrors)) {
            return $validationErrors[0]; // Retornar el primer error
        }
        
        // Sanitizar datos de entrada
        foreach ($data as $key => $value) {
            if ($key !== 'password_user') { // No sanitizar password antes del hash
                $data[$key] = $this->security->sanitizeInput($value);
            }
        }
        
        // Verificar si ya existe un usuario con ese email
        $existingUser = $this->findByEmail($data['email']);
        if ($existingUser) {
            return "Ya existe un usuario con ese email";
        }
        
        try {
            // Cifrar la contraseña con un algoritmo seguro
            if (isset($data['password_user'])) {
                $data['password_user'] = $this->authService->hashPassword($data['password_user']);
            }
            
            // Registrar fecha de creación
            if (!isset($data['fyh_creacion'])) {
                $data['fyh_creacion'] = date('Y-m-d H:i:s');
            }
            
            if (!isset($data['fyh_actualizacion'])) {
                $data['fyh_actualizacion'] = date('Y-m-d H:i:s');
            }
            
            // Utilizar el método create de la clase padre
            $userId = parent::create($data);
            
            // Registrar la creación del usuario
            $this->logger->logActivity('create', $userId, 'Usuario creado');
            
            return $userId;
        } catch (PDOException $e) {
            $this->logger->logError("Error al crear usuario: " . $e->getMessage());
            return "Error al crear el usuario: error interno del sistema";
        }
    }
    
    /**
     * Actualiza la imagen de perfil de un usuario
     * @param int $id ID del usuario
     * @param string $imagen Nombre del archivo de imagen
     * @return bool|string True si se actualizó o mensaje de error
     */
    public function updateProfileImage($id, $imagen) {
        try {
            // Validar que el usuario existe
            $usuario = $this->getById($id);
            if (!$usuario) {
                return "Usuario no encontrado";
            }
            
            // Sanitizar nombre de archivo
            $imagen = $this->security->sanitizeFilename($imagen);
            
            $sql = "UPDATE {$this->table} SET 
                    imagen_perfil = :imagen, 
                    fyh_actualizacion = :fyh_actualizacion
                    WHERE id_usuario = :id_usuario";
                    
            $stmt = $this->pdo->prepare($sql);
            $fyh_actualizacion = date('Y-m-d H:i:s');
            
            $stmt->bindParam(':imagen', $imagen, PDO::PARAM_STR);
            $stmt->bindParam(':fyh_actualizacion', $fyh_actualizacion);
            $stmt->bindParam(':id_usuario', $id, PDO::PARAM_INT);
            
            $result = $stmt->execute();
            
            if ($result && $stmt->rowCount() > 0) {
                // Registrar el cambio de imagen
                $this->logger->logActivity('update_image', $id, 'Imagen de perfil actualizada');
                return true;
            } else {
                return "No se pudo actualizar la imagen de perfil";
            }
        } catch (PDOException $e) {
            $this->logger->logError("Error al actualizar imagen de perfil: " . $e->getMessage());
            return "Error interno al actualizar la imagen";
        }
    }
    
    /**
     * Verifica las credenciales de un usuario para login
     * @param string $email Email del usuario
     * @param string $password Contraseña sin cifrar
     * @return array|false Datos del usuario si las credenciales son correctas o false
     */
    public function validateCredentials($email, $password) {
        return $this->authService->authenticateUser($email, $password);
    }
    
    /**
     * Verifica si una contraseña coincide con la del usuario
     * @param int $userId ID del usuario
     * @param string $password Contraseña sin cifrar para verificar
     * @return bool True si coincide, false en caso contrario
     */
    public function verifyPassword($userId, $password) {
        return $this->authService->verifyUserPassword($userId, $password);
    }
    
    /**
     * Actualiza la contraseña de un usuario con validaciones de seguridad
     * @param int $id ID del usuario
     * @param string $password Nueva contraseña (sin cifrar)
     * @param bool $requiresOldPassword Si se requiere la contraseña actual
     * @param string $oldPassword Contraseña actual (si se requiere)
     * @return bool|string True si se actualizó o mensaje de error
     */
    public function updatePassword($id, $password, $requiresOldPassword = false, $oldPassword = '') {
        return $this->authService->updateUserPassword($id, $password, $requiresOldPassword, $oldPassword);
    }
    
    /**
     * Valida los datos de usuario antes de crear o actualizar
     * @param array $data Datos del usuario
     * @return array Errores encontrados
     */
    private function validateUserData($data) {
        $errors = [];
        
        // Validar nombre
        if (empty($data['nombres']) || strlen($data['nombres']) < 3) {
            $errors[] = "El nombre es obligatorio y debe tener al menos 3 caracteres";
        }
        
        // Validar email
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Debe proporcionar un email válido";
        }
        
        // Validar contraseña al crear usuario
        if (isset($data['password_user'])) {
            if (strlen($data['password_user']) < 8) {
                $errors[] = "La contraseña debe tener al menos 8 caracteres";
            }
            
            if (!$this->security->isStrongPassword($data['password_user'])) {
                $errors[] = "La contraseña debe incluir números, letras mayúsculas y minúsculas, y al menos un carácter especial";
            }
        }
        
        // Validar rol
        if (empty($data['id_rol']) || !is_numeric($data['id_rol'])) {
            $errors[] = "Debe seleccionar un rol válido";
        }
        
        return $errors;
    }
}