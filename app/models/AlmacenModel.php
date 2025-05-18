<?php
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Model.php';

/**
 * Modelo para el almacén de productos
 */
class AlmacenModel extends Model {
    protected $table = 'tb_almacen';
    protected $primaryKey = 'id_producto';
    
    /**
     * Constructor
     * @param PDO|null $pdo Conexión PDO opcional
     */
    public function __construct($pdo = null) {
        parent::__construct($pdo);
    }
    
    /**
     * Obtiene todos los productos con datos relacionados
     * @param int|null $userId ID del usuario para filtrar (null para obtener todos)
     * @return array Lista de productos
     */
    public function getAllWithDetails($userId = null) {
        try {
            $sql = "SELECT a.*, c.nombre_categoria, u.nombres as usuario_nombres
                    FROM {$this->table} a
                    INNER JOIN tb_categorias c ON a.id_categoria = c.id_categoria
                    INNER JOIN tb_usuarios u ON a.id_usuario = u.id_usuario";
            
            // Añadir filtro de usuario si se proporciona
            if ($userId !== null) {
                $sql .= " WHERE a.id_usuario = :userId";
            }
            
            $sql .= " ORDER BY a.fyh_creacion DESC";
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
     * Busca productos por término
     * @param string $term Término de búsqueda
     * @param int|null $userId ID del usuario para filtrar (null para buscar en todos)
     * @return array Productos encontrados
     */
    public function search($term, $userId = null) {
        try {
            $term = "%$term%";
            $sql = "SELECT a.*, c.nombre_categoria
                    FROM {$this->table} a
                    INNER JOIN tb_categorias c ON a.id_categoria = c.id_categoria
                    WHERE (a.nombre LIKE :term OR a.codigo LIKE :term OR a.descripcion LIKE :term)";
            
            // Añadir filtro de usuario si se proporciona
            if ($userId !== null) {
                $sql .= " AND a.id_usuario = :userId";
            }
            
            $sql .= " ORDER BY a.nombre";
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
    
    /**
     * Obtiene productos con stock bajo
     * @param int|null $userId ID del usuario para filtrar (null para obtener todos)
     * @return array Productos con stock bajo
     */
    public function getLowStock($userId = null) {
        try {
            $sql = "SELECT a.*, c.nombre_categoria
                    FROM {$this->table} a
                    INNER JOIN tb_categorias c ON a.id_categoria = c.id_categoria
                    WHERE a.stock <= a.stock_minimo";
            
            // Añadir filtro de usuario si se proporciona
            if ($userId !== null) {
                $sql .= " AND a.id_usuario = :userId";
            }
            
            $sql .= " ORDER BY a.stock";
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
     * Genera un código para un nuevo producto
     * @return string Código generado
     */
    public function generateCode() {
        try {
            $sql = "SELECT MAX(CAST(SUBSTRING(codigo, 3) AS UNSIGNED)) as ultimo 
                    FROM {$this->table} 
                    WHERE codigo LIKE 'P-%'";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $ultimo = (int)($result['ultimo'] ?? 0);
            $siguiente = $ultimo + 1;
            
            return 'P-' . str_pad($siguiente, 5, '0', STR_PAD_LEFT);
        } catch (PDOException $e) {
            $this->logError($e->getMessage());
            return 'P-00001'; // Código por defecto
        }
    }
    
    /**
     * Actualiza el stock de un producto
     * @param int $id ID del producto
     * @param int $quantity Cantidad a aumentar (positivo) o disminuir (negativo)
     * @param int|null $userId ID del usuario propietario (para verificación)
     * @return bool|string Resultado de la operación
     */
    public function updateStock($id, $quantity, $userId = null) {
        try {
            $this->pdo->beginTransaction();
            
            // Construir consulta para obtener el producto
            $sql = "SELECT stock FROM {$this->table} WHERE {$this->primaryKey} = :id";
            
            // Añadir filtro de usuario si se proporciona
            if ($userId !== null) {
                $sql .= " AND id_usuario = :userId";
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            
            if ($userId !== null) {
                $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
            }
            
            $stmt->execute();
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$product) {
                $this->pdo->rollBack();
                return "Producto no encontrado o no autorizado";
            }
            
            $newStock = $product['stock'] + $quantity;
            
            // No permitir stock negativo
            if ($newStock < 0) {
                $this->pdo->rollBack();
                return "Stock insuficiente";
            }
            
            // Actualizar stock
            $sql = "UPDATE {$this->table} SET 
                    stock = :stock, 
                    fyh_actualizacion = :fyh
                    WHERE {$this->primaryKey} = :id";
            
            // Añadir filtro de usuario si se proporciona
            if ($userId !== null) {
                $sql .= " AND id_usuario = :userId";
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':stock', $newStock, PDO::PARAM_INT);
            $stmt->bindParam(':fyh', date('Y-m-d H:i:s'));
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            
            if ($userId !== null) {
                $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
            }
            
            $result = $stmt->execute();
            
            if ($result) {
                $this->pdo->commit();
                return true;
            } else {
                $this->pdo->rollBack();
                return "Error al actualizar el stock";
            }
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            $this->logError($e->getMessage());
            return "Error: " . $e->getMessage();
        }
    }
    
    /**
     * Verifica si un producto está siendo utilizado en otras tablas
     * @param int $id ID del producto
     * @return array [enUso, mensaje]
     */
    public function isInUse($id) {
        try {
            // Verificar en carrito
            $sql = "SELECT COUNT(*) FROM tb_carrito WHERE id_producto = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $inCart = $stmt->fetchColumn() > 0;
            
            if ($inCart) {
                return [true, "No se puede eliminar: el producto está en el carrito de ventas"];
            }
            
            // Verificar en compras
            $sql = "SELECT COUNT(*) FROM tb_compras WHERE id_producto = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $inPurchases = $stmt->fetchColumn() > 0;
            
            if ($inPurchases) {
                return [true, "No se puede eliminar: el producto está asociado a compras"];
            }
            
            return [false, ""];
        } catch (PDOException $e) {
            $this->logError($e->getMessage());
            return [true, "Error al verificar dependencias: " . $e->getMessage()];
        }
    }
    
    /**
     * Sobrescribe el método delete para verificar dependencias
     * @param int $id ID del producto
     * @param int|null $userId ID del usuario (para verificación)
     * @return bool|string Resultado de la operación
     */
    public function delete($id, $userId = null) {
        // Verificar dependencias
        $inUse = $this->isInUse($id);
        if ($inUse[0]) {
            return $inUse[1];
        }
        
        // Si no hay dependencias, proceder con la eliminación
        return parent::delete($id, $userId);
    }
}