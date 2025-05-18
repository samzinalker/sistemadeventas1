<?php
require_once __DIR__ . '/../core/Model.php';
require_once __DIR__ . '/../utils/Security.php';

/**
 * Modelo para gestión de roles
 */
class RolModel extends Model {
    protected $table = 'tb_roles';
    protected $primaryKey = 'id_rol';
    protected $security;
    
    /**
     * Constructor
     * @param PDO|null $pdo Conexión PDO opcional
     */
    public function __construct($pdo = null) {
        parent::__construct($pdo);
        $this->security = new Security();
    }
    
    /**
     * Verifica si un rol tiene usuarios asignados
     * @param int $id ID del rol
     * @return bool True si hay usuarios con ese rol
     */
    public function hasUsers($id) {
        try {
            $sql = "SELECT COUNT(*) as count FROM tb_usuarios WHERE id_rol = :id_rol";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id_rol', $id, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result['count'] > 0;
        } catch (PDOException $e) {
            $this->logError("Error en hasUsers: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtiene la cantidad de usuarios por rol
     * @return array Arreglo asociativo con id_rol => cantidad
     */
    public function getUserCountByRol() {
        try {
            $sql = "SELECT r.id_rol, r.rol, COUNT(u.id_usuario) as cantidad 
                    FROM {$this->table} r
                    LEFT JOIN tb_usuarios u ON r.id_rol = u.id_rol
                    GROUP BY r.id_rol
                    ORDER BY r.id_rol";
                    
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            
            $result = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $result[$row['id_rol']] = [
                    'nombre' => $this->security->sanitizeOutput($row['rol']),
                    'cantidad' => intval($row['cantidad'])
                ];
            }
            
            return $result;
        } catch (PDOException $e) {
            $this->logError("Error en getUserCountByRol: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Busca un rol por su nombre
     * @param string $nombre Nombre del rol
     * @return array|false Datos del rol o false
     */
    public function findByName($nombre) {
        try {
            $nombre = $this->security->sanitizeInput($nombre);
            
            $sql = "SELECT * FROM {$this->table} WHERE rol = :rol";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':rol', $nombre, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError("Error en findByName: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Crea un nuevo rol verificando duplicados
     * @param array $data Datos del rol
     * @return int|string ID del rol insertado o mensaje de error
     */
    public function createRol(array $data) {
        // Validar datos
        if (empty($data['rol']) || strlen($data['rol']) < 3) {
            return "El nombre del rol es obligatorio y debe tener al menos 3 caracteres";
        }
        
        // Sanitizar datos de entrada
        $data['rol'] = $this->security->sanitizeInput($data['rol']);
        
        // Verificar si ya existe un rol con ese nombre
        $existingRol = $this->findByName($data['rol']);
        if ($existingRol) {
            return "Ya existe un rol con ese nombre";
        }
        
        try {
            // Registrar fecha de creación
            if (!isset($data['fyh_creacion'])) {
                $data['fyh_creacion'] = date('Y-m-d H:i:s');
            }
            
            if (!isset($data['fyh_actualizacion'])) {
                $data['fyh_actualizacion'] = date('Y-m-d H:i:s');
            }
            
            // Utilizar el método create de la clase padre
            $rolId = parent::create($data);
            
            // Registrar la creación del rol
            $this->logActivity('create_rol', $rolId, 'Rol creado: ' . $data['rol']);
            
            return $rolId;
        } catch (PDOException $e) {
            $this->logError("Error al crear rol: " . $e->getMessage());
            return "Error al crear el rol: error interno del sistema";
        }
    }
    
    /**
     * Actualiza un rol existente
     * @param int $id ID del rol
     * @param array $data Datos a actualizar
     * @return bool|string True si se actualizó, mensaje de error si falla
     */
    public function updateRol($id, array $data) {
        // Validar datos
        if (empty($data['rol']) || strlen($data['rol']) < 3) {
            return "El nombre del rol es obligatorio y debe tener al menos 3 caracteres";
        }
        
        // Sanitizar datos de entrada
        $data['rol'] = $this->security->sanitizeInput($data['rol']);
        
        // Verificar que el rol existe
        $rol = $this->getById($id);
        if (!$rol) {
            return "Rol no encontrado";
        }
        
        // No permitir editar el rol de administrador
        if ($id == 1) {
            return "El rol de administrador no puede ser modificado";
        }
        
        // Verificar si ya existe otro rol con el mismo nombre
        $existingRol = $this->findByName($data['rol']);
        if ($existingRol && $existingRol['id_rol'] != $id) {
            return "Ya existe otro rol con ese nombre";
        }
        
        try {
            // Actualizar rol
            $result = parent::update($id, $data);
            
            if ($result === true) {
                // Registrar la actualización del rol
                $this->logActivity('update_rol', $id, 'Rol actualizado: ' . $data['rol']);
            }
            
            return $result;
        } catch (PDOException $e) {
            $this->logError("Error al actualizar rol: " . $e->getMessage());
            return "Error al actualizar el rol: error interno del sistema";
        }
    }
    
    /**
     * Elimina un rol con validaciones de seguridad
     * @param int $id ID del rol
     * @return bool|string True si se eliminó, mensaje de error si falla
     */
    public function deleteRol($id) {
        // Verificar que el rol existe
        $rol = $this->getById($id);
        if (!$rol) {
            return "Rol no encontrado";
        }
        
        // No permitir eliminar el rol de administrador
        if ($id == 1) {
            return "El rol de administrador no puede ser eliminado";
        }
        
        // Verificar si hay usuarios asociados
        if ($this->hasUsers($id)) {
            return "No se puede eliminar el rol porque tiene usuarios asociados";
        }
        
        try {
            // Registrar la eliminación del rol
            $this->logActivity('delete_rol', $id, 'Rol eliminado: ' . $rol['rol']);
            
            // Eliminar rol
            return parent::delete($id);
        } catch (PDOException $e) {
            $this->logError("Error al eliminar rol: " . $e->getMessage());
            return "Error al eliminar el rol: error interno del sistema";
        }
    }
    
    /**
     * Obtiene todos los roles con sanitización
     * @return array Lista de roles
     */
    public function getAll($userId = null) {
        $roles = parent::getAll();
        
        // Sanitizar datos para prevenir XSS
        foreach ($roles as $key => $rol) {
            $roles[$key] = $this->security->sanitizeOutput($rol);
        }
        
        return $roles;
    }
    
    /**
     * Obtiene un rol por su ID con sanitización
     * @param int $id ID del rol
     * @return array|false Datos del rol o false
     */
    public function getById($id, $userId = null) {
        $rol = parent::getById($id);
        
        // Sanitizar datos para prevenir XSS
        if ($rol) {
            $rol = $this->security->sanitizeOutput($rol);
        }
        
        return $rol;
    }
    
    /**
     * Registra actividad relacionada con roles en el log
     * @param string $activity Tipo de actividad
     * @param int $rolId ID del rol
     * @param string $description Descripción de la actividad
     * @return void
     */
    private function logActivity($activity, $rolId, $description) {
        $logDir = __DIR__ . '/../../logs';
        if (!file_exists($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $logFile = $logDir . '/role_activity.log';
        $date = date('Y-m-d H:i:s');
        $userId = $_SESSION['id_usuario'] ?? 0;
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        $logMessage = "[$date] Activity: $activity, User ID: $userId, Role ID: $rolId, IP: $ip, Description: $description\n";
        error_log($logMessage, 3, $logFile);
    }
}