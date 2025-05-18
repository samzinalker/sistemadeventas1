<?php
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Model.php';
require_once __DIR__ . '/AlmacenModel.php';

/**
 * Modelo para gestionar compras de productos
 */
class CompraModel extends Model {
    protected $table = 'tb_compras';
    protected $primaryKey = 'id_compra';
    
    /**
     * Constructor
     */
    public function __construct($pdo = null) {
        parent::__construct($pdo);
    }
    
    /**
     * Obtiene todas las compras con detalles relacionados
     * @param int|null $userId ID del usuario para filtrar
     * @return array Lista de compras
     */
    public function getAllWithDetails($userId = null) {
        try {
            $sql = "SELECT c.*, 
                    p.nombre as nombre_producto,
                    p.codigo as codigo_producto,
                    pr.nombre_proveedor,
                    pr.empresa as empresa_proveedor,
                    u.nombres as nombre_usuario
                    FROM {$this->table} c
                    INNER JOIN tb_almacen p ON c.id_producto = p.id_producto
                    INNER JOIN tb_proveedores pr ON c.id_proveedor = pr.id_proveedor
                    INNER JOIN tb_usuarios u ON c.id_usuario = u.id_usuario";
            
            // Filtrar por usuario si se proporciona ID
            if ($userId !== null) {
                $sql .= " WHERE c.id_usuario = :userId";
            }
            
            $sql .= " ORDER BY c.fyh_creacion DESC";
            
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
     * Obtiene compras con límite para mejorar rendimiento
     * @param int|null $userId ID del usuario para filtrar
     * @param int $limit Número máximo de registros
     * @return array Lista de compras limitada
     */
    public function getAllWithLimit($userId = null, $limit = 50) {
        try {
            $sql = "SELECT c.*, 
                    p.nombre as nombre_producto,
                    p.codigo as codigo_producto,
                    pr.nombre_proveedor,
                    pr.empresa as empresa_proveedor,
                    u.nombres as nombre_usuario
                    FROM {$this->table} c
                    INNER JOIN tb_almacen p ON c.id_producto = p.id_producto
                    INNER JOIN tb_proveedores pr ON c.id_proveedor = pr.id_proveedor
                    INNER JOIN tb_usuarios u ON c.id_usuario = u.id_usuario";
            
            // Filtrar por usuario si se proporciona ID
            if ($userId !== null) {
                $sql .= " WHERE c.id_usuario = :userId";
            }
            
            $sql .= " ORDER BY c.fyh_creacion DESC LIMIT :limit";
            
            $stmt = $this->pdo->prepare($sql);
            
            if ($userId !== null) {
                $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
            }
            
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError("Error en getAllWithLimit: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtiene una compra por ID con todos sus detalles
     * @param int $id ID de la compra
     * @param int|null $userId ID del usuario para verificación
     * @return array|false Datos de la compra o false
     */
    public function getByIdWithDetails($id, $userId = null) {
        try {
            $sql = "SELECT c.*, 
                    p.nombre as nombre_producto,
                    p.codigo as codigo_producto,
                    p.descripcion as descripcion_producto,
                    p.id_categoria,
                    pr.nombre_proveedor,
                    pr.empresa as empresa_proveedor,
                    pr.celular as celular_proveedor,
                    pr.direccion as direccion_proveedor,
                    u.nombres as nombre_usuario
                    FROM {$this->table} c
                    INNER JOIN tb_almacen p ON c.id_producto = p.id_producto
                    INNER JOIN tb_proveedores pr ON c.id_proveedor = pr.id_proveedor
                    INNER JOIN tb_usuarios u ON c.id_usuario = u.id_usuario
                    WHERE c.id_compra = :id";
            
            // Añadir filtro de usuario si se proporciona
            if ($userId !== null) {
                $sql .= " AND c.id_usuario = :userId";
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
     * Genera un nuevo número de compra
     * @return int Siguiente número de compra
     */
    public function generateNroCompra() {
        try {
            $sql = "SELECT MAX(nro_compra) as ultimo FROM {$this->table}";
            $stmt = $this->pdo->query($sql);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $ultimo = intval($result['ultimo'] ?? 0);
            return $ultimo + 1;
        } catch (PDOException $e) {
            $this->logError($e->getMessage());
            return 1; // Si hay error, comenzar desde 1
        }
    }
    
    /**
     * Registra una nueva compra y actualiza el stock
     * @param array $data Datos de la compra
     * @return int|string ID de la compra o mensaje de error
     */
    public function createAndUpdateStock(array $data) {
        // Verificar si PDO está configurado para manejar errores
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        try {
            // Comprobar si hay transacción activa
            $transactionActive = $this->pdo->inTransaction();
            
            // Iniciar transacción solo si no hay una activa
            if (!$transactionActive) {
                $this->pdo->beginTransaction();
            }
            
            // 1. Registrar la compra usando el método base insert en lugar de create
            $columns = [];
            $placeholders = [];
            $values = [];
            
            foreach ($data as $column => $value) {
                $columns[] = $column;
                $placeholders[] = ":$column";
                $values[":$column"] = $value;
            }
            
            $sql = "INSERT INTO {$this->table} (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
            $stmt = $this->pdo->prepare($sql);
            
            foreach ($values as $param => $val) {
                $stmt->bindValue($param, $val);
            }
            
            $stmt->execute();
            $compraId = $this->pdo->lastInsertId();
            
            if (!is_numeric($compraId) || $compraId <= 0) {
                // Hacer rollback solo si iniciamos la transacción
                if (!$transactionActive) {
                    $this->pdo->rollBack();
                }
                return "Error al insertar la compra";
            }
            
            // 2. Actualizar el stock del producto
            $almacenModel = new AlmacenModel($this->pdo);
            
            // Obtener el producto para verificar existencia
            $producto = $almacenModel->getById($data['id_producto']);
            
            if (!$producto) {
                // Hacer rollback solo si iniciamos la transacción
                if (!$transactionActive) {
                    $this->pdo->rollBack();
                }
                return "Producto no encontrado";
            }
            
            // Actualizar stock
            $nuevoStock = $producto['stock'] + intval($data['cantidad']);
            
            $updateSql = "UPDATE tb_almacen SET 
                      stock = :stock,
                      fyh_actualizacion = :fyh_actualizacion
                      WHERE id_producto = :id_producto";
                
            $updateStmt = $this->pdo->prepare($updateSql);
            $fechaActual = date('Y-m-d H:i:s');
            
            $updateStmt->bindParam(':stock', $nuevoStock, PDO::PARAM_INT);
            $updateStmt->bindParam(':fyh_actualizacion', $fechaActual);
            $updateStmt->bindParam(':id_producto', $data['id_producto'], PDO::PARAM_INT);
            
            $updateResult = $updateStmt->execute();
            
            if (!$updateResult) {
                // Hacer rollback solo si iniciamos la transacción
                if (!$transactionActive) {
                    $this->pdo->rollBack();
                }
                return "Error al actualizar el stock del producto";
            }
            
            // Commit solo si iniciamos la transacción
            if (!$transactionActive) {
                $this->pdo->commit();
            }
            
            return $compraId;
        } catch (PDOException $e) {
            // Hacer rollback solo si iniciamos la transacción y aún está activa
            if (!$transactionActive && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            
            $this->logError("Error en createAndUpdateStock: " . $e->getMessage());
            return "Error en la transacción: " . $e->getMessage();
        }
    }
    
    /**
     * Busca compras por criterios
     * @param array $criteria Criterios de búsqueda (producto, proveedor, fecha)
     * @param int|null $userId ID del usuario para filtrar
     * @return array Compras encontradas
     */
    public function search($criteria, $userId = null) {
        try {
            $params = [];
            
            $sql = "SELECT c.*, 
                   p.nombre as nombre_producto,
                   p.codigo as codigo_producto,
                   pr.nombre_proveedor,
                   pr.empresa as empresa_proveedor,
                   u.nombres as nombre_usuario
                   FROM {$this->table} c
                   INNER JOIN tb_almacen p ON c.id_producto = p.id_producto
                   INNER JOIN tb_proveedores pr ON c.id_proveedor = pr.id_proveedor
                   INNER JOIN tb_usuarios u ON c.id_usuario = u.id_usuario
                   WHERE 1=1";
            
            // Filtrar por usuario
            if ($userId !== null) {
                $sql .= " AND c.id_usuario = :userId";
                $params[':userId'] = $userId;
            }
            
            // Filtrar por producto
            if (!empty($criteria['producto'])) {
                $sql .= " AND (p.nombre LIKE :producto OR p.codigo LIKE :producto)";
                $params[':producto'] = '%' . $criteria['producto'] . '%';
            }
            
            // Filtrar por proveedor
            if (!empty($criteria['proveedor'])) {
                $sql .= " AND (pr.nombre_proveedor LIKE :proveedor OR pr.empresa LIKE :proveedor)";
                $params[':proveedor'] = '%' . $criteria['proveedor'] . '%';
            }
            
            // Filtrar por fecha
            if (!empty($criteria['fecha_desde']) && !empty($criteria['fecha_hasta'])) {
                $sql .= " AND c.fecha_compra BETWEEN :fecha_desde AND :fecha_hasta";
                $params[':fecha_desde'] = $criteria['fecha_desde'];
                $params[':fecha_hasta'] = $criteria['fecha_hasta'];
            } else if (!empty($criteria['fecha_desde'])) {
                $sql .= " AND c.fecha_compra >= :fecha_desde";
                $params[':fecha_desde'] = $criteria['fecha_desde'];
            } else if (!empty($criteria['fecha_hasta'])) {
                $sql .= " AND c.fecha_compra <= :fecha_hasta";
                $params[':fecha_hasta'] = $criteria['fecha_hasta'];
            }
            
            // Limitar resultados para mejor rendimiento
            $sql .= " ORDER BY c.fyh_creacion DESC LIMIT 100";
            
            $stmt = $this->pdo->prepare($sql);
            
            foreach ($params as $param => $value) {
                $stmt->bindValue($param, $value);
            }
            
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError("Error en search: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtiene estadísticas optimizadas de compras
     * @param int|null $userId ID del usuario para filtrar
     * @return array Estadísticas simples (total, mes actual, semana, hoy)
     */
    public function getStatsOptimized($userId = null) {
        try {
            // Obtener las compras del usuario con límite para mayor rendimiento
            $compras = $this->getAllWithLimit($userId, 500);  // Limitamos a 500 compras para estadísticas
            
            // Inicializar contadores
            $stats = [
                'total' => ['count' => 0, 'total' => 0],
                'month' => ['count' => 0, 'total' => 0],
                'week' => ['count' => 0, 'total' => 0],
                'today' => ['count' => 0, 'total' => 0]
            ];
            
            // Fechas de referencia
            $today = date('Y-m-d');
            $thisMonth = date('Y-m');
            $thisWeekStart = date('Y-m-d', strtotime('monday this week'));
            
            // Calcular estadísticas
            foreach ($compras as $compra) {
                // Calcular total
                $monto = floatval($compra['precio_compra']) * intval($compra['cantidad']);
                
                // Incrementar total general
                $stats['total']['count']++;
                $stats['total']['total'] += $monto;
                
                // Fecha de compra
                $fechaCompra = substr($compra['fecha_compra'], 0, 10); // Formato Y-m-d
                $mesCompra = substr($compra['fecha_compra'], 0, 7);   // Formato Y-m
                
                // Compras del mes
                if ($mesCompra === $thisMonth) {
                    $stats['month']['count']++;
                    $stats['month']['total'] += $monto;
                    
                    // Compras de la semana
                    if ($fechaCompra >= $thisWeekStart) {
                        $stats['week']['count']++;
                        $stats['week']['total'] += $monto;
                        
                        // Compras de hoy
                        if ($fechaCompra === $today) {
                            $stats['today']['count']++;
                            $stats['today']['total'] += $monto;
                        }
                    }
                }
            }
            
            return $stats;
            
        } catch (Exception $e) {
            $this->logError("Error en getStatsOptimized: " . $e->getMessage());
            return [
                'total' => ['count' => 0, 'total' => 0],
                'month' => ['count' => 0, 'total' => 0],
                'week' => ['count' => 0, 'total' => 0],
                'today' => ['count' => 0, 'total' => 0]
            ];
        }
    }

    /**
     * Obtiene estadísticas de compras
     * @param int|null $userId ID del usuario para filtrar
     * @return array Estadísticas (total, mes actual, semana, hoy)
     */
    public function getStats($userId = null) {
        try {
            // Base de la consulta
            $baseQuery = "SELECT COUNT(*) as count, COALESCE(SUM(precio_compra * cantidad), 0) as total
                         FROM {$this->table}";
            
            // Condición de usuario
            $userCondition = $userId !== null ? " WHERE id_usuario = :userId" : "";
            
            // Total de todas las compras
            $totalQuery = $baseQuery . $userCondition;
            $stmt = $this->pdo->prepare($totalQuery);
            
            if ($userId !== null) {
                $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
            }
            
            $stmt->execute();
            $total = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Compras del mes actual
            $monthQuery = $baseQuery . 
                          ($userCondition ? $userCondition . " AND" : " WHERE") . 
                          " MONTH(fecha_compra) = MONTH(CURRENT_DATE()) AND YEAR(fecha_compra) = YEAR(CURRENT_DATE())";
            $stmt = $this->pdo->prepare($monthQuery);
            
            if ($userId !== null) {
                $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
            }
            
            $stmt->execute();
            $month = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Compras de la semana actual
            $weekQuery = $baseQuery . 
                        ($userCondition ? $userCondition . " AND" : " WHERE") . 
                        " YEARWEEK(fecha_compra, 1) = YEARWEEK(CURRENT_DATE(), 1)";
            $stmt = $this->pdo->prepare($weekQuery);
            
            if ($userId !== null) {
                $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
            }
            
            $stmt->execute();
            $week = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Compras de hoy
            $todayQuery = $baseQuery . 
                         ($userCondition ? $userCondition . " AND" : " WHERE") . 
                         " DATE(fecha_compra) = CURRENT_DATE()";
            $stmt = $this->pdo->prepare($todayQuery);
            
            if ($userId !== null) {
                $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
            }
            
            $stmt->execute();
            $today = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return [
                'total' => $total,
                'month' => $month,
                'week' => $week,
                'today' => $today
            ];
        } catch (PDOException $e) {
            $this->logError($e->getMessage());
            return [
                'total' => ['count' => 0, 'total' => 0],
                'month' => ['count' => 0, 'total' => 0],
                'week' => ['count' => 0, 'total' => 0],
                'today' => ['count' => 0, 'total' => 0]
            ];
        }
    }
}