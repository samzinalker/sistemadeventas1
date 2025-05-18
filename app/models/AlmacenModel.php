<?php
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Model.php';

/**
 * Modelo para gestionar productos en almacén
 */
class AlmacenModel extends Model {
    protected $table = 'tb_almacen';
    protected $primaryKey = 'id_producto';
    
    /**
     * Constructor
     */
    public function __construct($pdo = null) {
        parent::__construct($pdo);
    }
    
    /**
     * Obtiene productos con detalles de categoría
     * @param int|null $userId ID del usuario para filtrar
     * @return array Lista de productos
     */
    public function getAllWithDetails($userId = null) {
        try {
            $sql = "SELECT p.*, c.nombre_categoria 
                   FROM {$this->table} p
                   INNER JOIN tb_categorias c ON p.id_categoria = c.id_categoria";
            
            // Filtrar por usuario si se proporciona ID
            if ($userId !== null) {
                $sql .= " WHERE p.id_usuario = :userId";
            }
            
            $sql .= " ORDER BY p.fyh_creacion DESC";
            
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
     * Obtiene un producto por ID con detalles de categoría
     * @param int $id ID del producto
     * @param int|null $userId ID del usuario para verificación
     * @return array|false Datos del producto o false
     */
    public function getByIdWithDetails($id, $userId = null) {
        try {
            $sql = "SELECT p.*, c.nombre_categoria 
                   FROM {$this->table} p
                   INNER JOIN tb_categorias c ON p.id_categoria = c.id_categoria
                   WHERE p.{$this->primaryKey} = :id";
            
            // Añadir filtro de usuario si se proporciona
            if ($userId !== null) {
                $sql .= " AND p.id_usuario = :userId";
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            
            if ($userId !== null) {
                $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
            }
            
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError($e->getMessage());
            return false;
        }
    }
    
    /**
     * Genera un nuevo código de producto
     * @return string Código de producto (formato: P-XXXXX)
     */
    public function generateProductCode() {
        try {
            $sql = "SELECT MAX(SUBSTRING(codigo, 3)) as ultimo FROM {$this->table} 
                    WHERE codigo LIKE 'P-%'";
            $stmt = $this->pdo->query($sql);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $ultimo = intval($result['ultimo'] ?? 0);
            $nuevo = $ultimo + 1;
            return 'P-' . str_pad($nuevo, 5, '0', STR_PAD_LEFT);
        } catch (PDOException $e) {
            $this->logError($e->getMessage());
            return 'P-00001'; // Código por defecto en caso de error
        }
    }
    
    /**
     * Actualiza el stock de un producto
     * @param int $id ID del producto
     * @param int $quantity Cantidad a añadir
     * @return bool|string True si se actualizó correctamente, mensaje de error si falla
     */
    public function updateStock($id, $quantity) {
        try {
            // Primero obtenemos el producto para verificar su existencia
            $producto = $this->getById($id);
            
            if (!$producto) {
                return "Producto no encontrado";
            }
            
            // Actualizar stock
            $nuevoStock = $producto['stock'] + $quantity;
            
            $sql = "UPDATE {$this->table} SET 
                    stock = :stock,
                    fyh_actualizacion = :fyh_actualizacion
                    WHERE {$this->primaryKey} = :id";
                    
            $stmt = $this->pdo->prepare($sql);
            $fechaActual = date('Y-m-d H:i:s');
            
            $stmt->bindParam(':stock', $nuevoStock, PDO::PARAM_INT);
            $stmt->bindParam(':fyh_actualizacion', $fechaActual);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            
            $stmt->execute();
            return true;
        } catch (PDOException $e) {
            $this->logError($e->getMessage());
            return "Error al actualizar stock: " . $e->getMessage();
        }
    }
    
    /**
     * Obtiene productos con stock bajo
     * @param int|null $userId ID del usuario para filtrar
     * @return array Lista de productos con stock bajo
     */
    public function lowStock($userId = null) {
        try {
            $sql = "SELECT p.*, c.nombre_categoria 
                   FROM {$this->table} p
                   INNER JOIN tb_categorias c ON p.id_categoria = c.id_categoria
                   WHERE p.stock <= p.stock_minimo";
            
            // Filtrar por usuario si se proporciona ID
            if ($userId !== null) {
                $sql .= " AND p.id_usuario = :userId";
            }
            
            $sql .= " ORDER BY p.stock ASC";
            
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
     * Busca productos por criterios
     * @param string $term Término de búsqueda
     * @param int|null $userId ID del usuario para filtrar
     * @param array|null $options Opciones adicionales (categoría, solo en stock, etc)
     * @return array Productos encontrados
     */
    public function search($term, $userId = null, $options = null) {
        try {
            $term = "%$term%";
            $sql = "SELECT p.*, c.nombre_categoria 
                   FROM {$this->table} p
                   INNER JOIN tb_categorias c ON p.id_categoria = c.id_categoria
                   WHERE (p.nombre LIKE :term 
                   OR p.codigo LIKE :term 
                   OR p.descripcion LIKE :term)";
            
            // Añadir filtro de usuario si se proporciona
            if ($userId !== null) {
                $sql .= " AND p.id_usuario = :userId";
            }
            
            // Filtrar por categoría
            if (!empty($options['categoria'])) {
                $sql .= " AND p.id_categoria = :categoria";
            }
            
            // Filtrar solo productos con stock
            if (!empty($options['en_stock']) && $options['en_stock'] === true) {
                $sql .= " AND p.stock > 0";
            }
            
            $sql .= " ORDER BY p.nombre ASC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':term', $term);
            
            if ($userId !== null) {
                $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
            }
            
            if (!empty($options['categoria'])) {
                $stmt->bindParam(':categoria', $options['categoria'], PDO::PARAM_INT);
            }
            
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError($e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtiene datos de ventas de producto
     * @param int $id ID del producto
     * @param string $period Periodo (daily, weekly, monthly, yearly)
     * @return array Datos de ventas
     */
    public function getProductSalesData($id, $period = 'monthly') {
        try {
            // Determinar la agrupación según el periodo
            switch ($period) {
                case 'daily':
                    $groupBy = "DATE(v.fyh_creacion)";
                    $dateFormat = "DATE_FORMAT(v.fyh_creacion, '%Y-%m-%d')";
                    break;
                case 'weekly':
                    $groupBy = "YEARWEEK(v.fyh_creacion)";
                    $dateFormat = "DATE_FORMAT(v.fyh_creacion, '%Y-%u')"; // Año-Semana
                    break;
                case 'yearly':
                    $groupBy = "YEAR(v.fyh_creacion)";
                    $dateFormat = "DATE_FORMAT(v.fyh_creacion, '%Y')";
                    break;
                case 'monthly':
                default:
                    $groupBy = "YEAR(v.fyh_creacion), MONTH(v.fyh_creacion)";
                    $dateFormat = "DATE_FORMAT(v.fyh_creacion, '%Y-%m')"; // Año-Mes
            }
            
            $sql = "SELECT {$dateFormat} as periodo, 
                    SUM(c.cantidad) as cantidad_vendida,
                    SUM(c.cantidad * p.precio_venta) as total_ventas
                    FROM tb_carrito c
                    INNER JOIN tb_ventas v ON c.nro_venta = v.nro_venta
                    INNER JOIN {$this->table} p ON c.id_producto = p.id_producto
                    WHERE p.id_producto = :id
                    GROUP BY {$groupBy}
                    ORDER BY v.fyh_creacion ASC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError($e->getMessage());
            return [];
        }
    }
    
    /**
     * Comprueba si se puede eliminar un producto
     * @param int $id ID del producto
     * @return bool|string True si se puede eliminar, mensaje si no
     */
    public function canDelete($id) {
        try {
            // Verificar si hay ventas asociadas
            $sql = "SELECT COUNT(*) FROM tb_carrito WHERE id_producto = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            if ($stmt->fetchColumn() > 0) {
                return "No se puede eliminar: el producto tiene ventas asociadas";
            }
            
            // Verificar si hay compras asociadas
            $sql = "SELECT COUNT(*) FROM tb_compras WHERE id_producto = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            if ($stmt->fetchColumn() > 0) {
                return "No se puede eliminar: el producto tiene compras registradas";
            }
            
            return true;
        } catch (PDOException $e) {
            $this->logError($e->getMessage());
            return "Error al verificar dependencias: " . $e->getMessage();
        }
    }
    
    /**
     * Actualiza el precio de compra y precio de venta
     * @param int $id ID del producto
     * @param float $precioCompra Nuevo precio de compra
     * @param float $precioVenta Nuevo precio de venta
     * @return bool|string True si se actualizó, mensaje si hubo error
     */
    public function updatePrices($id, $precioCompra, $precioVenta) {
        try {
            $sql = "UPDATE {$this->table} SET 
                    precio_compra = :precio_compra,
                    precio_venta = :precio_venta,
                    fyh_actualizacion = :fyh_actualizacion
                    WHERE {$this->primaryKey} = :id";
                    
            $stmt = $this->pdo->prepare($sql);
            $fechaActual = date('Y-m-d H:i:s');
            
            $stmt->bindParam(':precio_compra', $precioCompra);
            $stmt->bindParam(':precio_venta', $precioVenta);
            $stmt->bindParam(':fyh_actualizacion', $fechaActual);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            
            $stmt->execute();
            return true;
        } catch (PDOException $e) {
            $this->logError($e->getMessage());
            return "Error al actualizar precios: " . $e->getMessage();
        }
    }
    
    /**
     * Sobrescritura de delete para verificar dependencias
     */
    public function delete($id, $userId = null) {
        // Verificar si se puede eliminar
        $canDelete = $this->canDelete($id);
        if ($canDelete !== true) {
            return $canDelete; // Mensaje de error
        }
        
        // Proceder con la eliminación si es seguro
        return parent::delete($id, $userId);
    }
}