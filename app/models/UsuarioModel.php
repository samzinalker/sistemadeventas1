<?php
require_once __DIR__ . '/../core/Model.php';

/**
 * Modelo para gestión de usuarios
 */
class UsuarioModel extends Model {
    protected $table = 'tb_usuarios';
    protected $primaryKey = 'id_usuario';
    
    /**
     * Constructor
     * @param PDO|null $pdo Conexión PDO opcional
     */
    public function __construct($pdo = null) {
        parent::__construct($pdo);
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
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError("Error en getAllWithRoles: " . $e->getMessage());
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
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError("Error en getByIdWithRole: " . $e->getMessage());
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
            $sql = "SELECT * FROM {$this->table} WHERE email = :email";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError("Error en findByEmail: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Crea un nuevo usuario
     * @param array $data Datos del usuario
     * @return int|string ID del usuario insertado o mensaje de error
     */
    public function createUser(array $data) {
        // Verificar si ya existe un usuario con ese email
        $existingUser = $this->findByEmail($data['email']);
        if ($existingUser) {
            return "Ya existe un usuario con ese email";
        }
        
        try {
            // Cifrar la contraseña antes de guardarla
            if (isset($data['password_user'])) {
                $data['password_user'] = password_hash($data['password_user'], PASSWORD_DEFAULT);
            }
            
            // Utilizar el método create de la clase padre
            return parent::create($data);
        } catch (PDOException $e) {
            $this->logError("Error al crear usuario: " . $e->getMessage());
            return "Error al crear el usuario: " . $e->getMessage();
        }
    }
    
    /**
     * Actualiza la contraseña de un usuario
     * @param int $id ID del usuario
     * @param string $password Nueva contraseña (sin cifrar)
     * @return bool|string True si se actualizó o mensaje de error
     */
    public function updatePassword($id, $password) {
        try {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            $sql = "UPDATE {$this->table} SET 
                    password_user = :password, 
                    fyh_actualizacion = :fyh_actualizacion
                    WHERE id_usuario = :id_usuario";
                    
            $stmt = $this->pdo->prepare($sql);
            $fyh_actualizacion = date('Y-m-d H:i:s');
            
            $stmt->bindParam(':password', $hashedPassword, PDO::PARAM_STR);
            $stmt->bindParam(':fyh_actualizacion', $fyh_actualizacion);
            $stmt->bindParam(':id_usuario', $id, PDO::PARAM_INT);
            
            $stmt->execute();
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            $this->logError("Error al actualizar contraseña: " . $e->getMessage());
            return "Error al actualizar la contraseña: " . $e->getMessage();
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
            $sql = "UPDATE {$this->table} SET 
                    imagen_perfil = :imagen, 
                    fyh_actualizacion = :fyh_actualizacion
                    WHERE id_usuario = :id_usuario";
                    
            $stmt = $this->pdo->prepare($sql);
            $fyh_actualizacion = date('Y-m-d H:i:s');
            
            $stmt->bindParam(':imagen', $imagen, PDO::PARAM_STR);
            $stmt->bindParam(':fyh_actualizacion', $fyh_actualizacion);
            $stmt->bindParam(':id_usuario', $id, PDO::PARAM_INT);
            
            $stmt->execute();
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            $this->logError("Error al actualizar imagen de perfil: " . $e->getMessage());
            return "Error al actualizar la imagen: " . $e->getMessage();
        }
    }
    
    /**
     * Verifica las credenciales de un usuario para login
     * @param string $email Email del usuario
     * @param string $password Contraseña sin cifrar
     * @return array|false Datos del usuario si las credenciales son correctas o false
     */
    public function validateCredentials($email, $password) {
        $user = $this->findByEmail($email);
        
        if ($user && password_verify($password, $user['password_user'])) {
            return $user;
        }
        
        return false;
    }
    
    /**
     * Verifica si una contraseña coincide con la del usuario
     * @param int $userId ID del usuario
     * @param string $password Contraseña sin cifrar para verificar
     * @return bool True si coincide, false en caso contrario
     */
    public function verifyPassword($userId, $password) {
        $user = $this->getById($userId);
        
        if ($user && password_verify($password, $user['password_user'])) {
            return true;
        }
        
        return false;
    }
}