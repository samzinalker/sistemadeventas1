<?php
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Model.php';

/**
 * Modelo para gestionar categorías de productos
 */
class CategoriaModel extends Model {
    protected $table = 'tb_categorias';
    protected $primaryKey = 'id_categoria';
    
    /**
     * Constructor
     */
    public function __construct($pdo = null) {
        parent::__construct($pdo);
    }
    
    /**
     * Obtiene categorías con filtrado por usuario
     * @param int|null $userId ID del usuario propietario
     * @return array Lista de categorías
     */
    public function getAllWithCount($userId = null) {
        try {
            $sql = "SELECT c.*, COUNT(a.id_producto) as productos_count
                    FROM {$this->table} c
                    LEFT JOIN tb_almacen a ON c.id_categoria = a.id_categoria";
            
            if ($userId !== null) {
                $sql .= " WHERE c.id_usuario = :userId";
            }
            
            $sql .= " GROUP BY c.id_categoria ORDER BY c.nombre_categoria ASC";
            
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
     * Verifica si una categoría puede ser eliminada
     * @param int $id ID de la categoría
     * @return array [enUso, mensaje]
     */
    public function canDelete($id) {
        try {
            // Verificar si hay productos usando esta categoría
            $sql = "SELECT COUNT(*) FROM tb_almacen WHERE id_categoria = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $count = $stmt->fetchColumn();
            
            if ($count > 0) {
                return [false, "No se puede eliminar: la categoría tiene $count productos asociados"];
            }
            
            return [true, ""];
        } catch (PDOException $e) {
            $this->logError($e->getMessage());
            return [false, "Error al verificar dependencias: " . $e->getMessage()];
        }
    }
    
    /**
     * Sobrescribe el método delete para verificar dependencias
     * @param int $id ID de la categoría
     * @param int|null $userId ID del usuario para verificar propiedad
     * @return bool|string Resultado de la operación
     */
    public function delete($id, $userId = null) {
        // Verificar dependencias
        list($canDelete, $message) = $this->canDelete($id);
        if (!$canDelete) {
            return $message;
        }
        
        // Si no hay dependencias, proceder con la eliminación
        return parent::delete($id, $userId);
    }
    
    /**
     * Busca categorías por nombre
     * @param string $term Término de búsqueda
     * @param int|null $userId ID del usuario para filtrar
     * @return array Categorías encontradas
     */
    public function search($term, $userId = null) {
        try {
            $term = "%$term%";
            $sql = "SELECT * FROM {$this->table} WHERE nombre_categoria LIKE :term";
            
            if ($userId !== null) {
                $sql .= " AND id_usuario = :userId";
            }
            
            $sql .= " ORDER BY nombre_categoria ASC";
            
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