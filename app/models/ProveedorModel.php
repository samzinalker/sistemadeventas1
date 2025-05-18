<?php
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Model.php';

/**
 * Modelo para gestionar proveedores
 */
class ProveedorModel extends Model {
    protected $table = 'tb_proveedores';
    protected $primaryKey = 'id_proveedor';
    
    /**
     * Constructor
     */
    public function __construct($pdo = null) {
        parent::__construct($pdo);
    }
    
    /**
     * Obtiene todos los proveedores activos
     * @param int|null $userId ID del usuario para filtrar
     * @return array Lista de proveedores
     */
    public function getAll($userId = null) {
        try {
            $sql = "SELECT * FROM {$this->table}";
            
            // Filtrar por usuario si se proporciona ID
            if ($userId !== null) {
                $sql .= " WHERE id_usuario = :userId";
            }
            
            $sql .= " ORDER BY nombre_proveedor ASC";
            
            $stmt = $this->pdo->prepare($sql);
            
            if ($userId !== null) {
                $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
            }
            
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError($e->getMessage());
            return [];
        }
    }
    
    /**
     * Busca proveedores por término
     * @param string $term Término de búsqueda
     * @param int|null $userId ID del usuario para filtrar
     * @return array Proveedores encontrados
     */
    public function search($term, $userId = null) {
        try {
            $term = "%$term%";
            $sql = "SELECT * FROM {$this->table} 
                    WHERE (nombre_proveedor LIKE :term 
                    OR empresa LIKE :term 
                    OR celular LIKE :term)";
            
            // Añadir filtro de usuario si se proporciona
            if ($userId !== null) {
                $sql .= " AND id_usuario = :userId";
            }
            
            $sql .= " ORDER BY nombre_proveedor ASC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':term', $term);
            
            if ($userId !== null) {
                $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
            }
            
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError($e->getMessage());
            return [];
        }
    }
}