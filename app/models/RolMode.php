<?php
require_once __DIR__ . '/../core/Model.php';

/**
 * Modelo para gestión de roles
 */
class RolModel extends Model {
    protected $table = 'tb_roles';
    protected $primaryKey = 'id_rol';
    
    /**
     * Constructor
     * @param PDO|null $pdo Conexión PDO opcional
     */
    public function __construct($pdo = null) {
        parent::__construct($pdo);
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
                    FROM tb_roles r
                    LEFT JOIN tb_usuarios u ON r.id_rol = u.id_rol
                    GROUP BY r.id_rol
                    ORDER BY r.id_rol";
                    
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            
            $result = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $result[$row['id_rol']] = [
                    'nombre' => $row['rol'],
                    'cantidad' => $row['cantidad']
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
        // Verificar si ya existe un rol con ese nombre
        $existingRol = $this->findByName($data['rol']);
        if ($existingRol) {
            return "Ya existe un rol con ese nombre";
        }
        
        try {
            return parent::create($data);
        } catch (PDOException $e) {
            $this->logError("Error al crear rol: " . $e->getMessage());
            return "Error al crear el rol: " . $e->getMessage();
        }
    }
}