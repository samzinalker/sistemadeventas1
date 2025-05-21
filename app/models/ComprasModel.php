<?php
// Resumen: Modelo para gestionar las operaciones CRUD y lógicas de negocio
// para las tablas 'compras' y 'detalle_compras'.
// Proporciona métodos para listar, crear, anular compras, obtener detalles,
// y manejar la actualización de stock de productos.

class ComprasModel {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Obtiene la configuración del sistema por clave.
     * @param string $clave
     * @return string|null
     */
    public function getConfiguracion(string $clave): ?string {
        $sql = "SELECT valor FROM configuracion_sistema WHERE clave = :clave";
        $query = $this->pdo->prepare($sql);
        $query->bindParam(':clave', $clave, PDO::PARAM_STR);
        $query->execute();
        $result = $query->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['valor'] : null;
    }

    /**
     * Guarda o actualiza una configuración del sistema.
     * @param string $clave
     * @param string $valor
     * @param string $fyh_actualizacion
     * @return bool
     */
    public function guardarConfiguracion(string $clave, string $valor, string $fyh_actualizacion): bool {
        $sql = "INSERT INTO configuracion_sistema (clave, valor, fyh_actualizacion)
                VALUES (:clave, :valor, :fyh_actualizacion)
                ON DUPLICATE KEY UPDATE valor = :valor_update, fyh_actualizacion = :fyh_actualizacion_update";
        $query = $this->pdo->prepare($sql);
        $query->bindParam(':clave', $clave, PDO::PARAM_STR);
        $query->bindParam(':valor', $valor, PDO::PARAM_STR);
        $query->bindParam(':fyh_actualizacion', $fyh_actualizacion, PDO::PARAM_STR);
        $query->bindParam(':valor_update', $valor, PDO::PARAM_STR);
        $query->bindParam(':fyh_actualizacion_update', $fyh_actualizacion, PDO::PARAM_STR);
        return $query->execute();
    }


    /**
     * Lista las compras para DataTables con paginación, búsqueda y ordenamiento.
     * @param int $id_usuario El ID del usuario para filtrar sus compras (o todos si es admin y se adapta).
     * @param int $start Inicio para la paginación.
     * @param int $length Número de registros a devolver.
     * @param string $searchValue Valor de búsqueda.
     * @param string $orderColumn Índice de la columna para ordenar.
     * @param string $orderDir Dirección del orden (asc/desc).
     * @param array $columns Mapeo de índices de columna a nombres de columna de BD.
     * @return array
     */
    public function listarComprasParaDataTable(int $id_usuario, int $start, int $length, ?string $searchValue, ?int $orderColumnIdx, ?string $orderDir, array $columnsMap): array {
        $sqlBase = "SELECT 
                        c.id_compra, 
                        c.nro_comprobante_proveedor, 
                        p.nombre_proveedor, 
                        c.fecha_compra, 
                        u.nombres as nombre_usuario_registra, 
                        c.subtotal_neto,
                        c.porcentaje_iva,
                        c.monto_iva,
                        c.monto_total,
                        c.estado
                    FROM compras c
                    JOIN tb_proveedores p ON c.id_proveedor = p.id_proveedor
                    JOIN tb_usuarios u ON c.id_usuario = u.id_usuario
                    WHERE c.id_usuario = :id_usuario_filter "; // Asumiendo que el usuario solo ve sus compras

        $sqlSearch = "";
        $params = [':id_usuario_filter' => $id_usuario];

        if (!empty($searchValue)) {
            $sqlSearch .= " AND (c.nro_comprobante_proveedor LIKE :search_value OR p.nombre_proveedor LIKE :search_value OR u.nombres LIKE :search_value OR c.estado LIKE :search_value OR c.fecha_compra LIKE :search_value)";
            $params[':search_value'] = "%$searchValue%";
        }

        $sqlOrder = "";
        if ($orderColumnIdx !== null && isset($columnsMap[$orderColumnIdx]) && !empty($orderDir)) {
            $colName = $columnsMap[$orderColumnIdx];
             // Validar que el nombre de columna es seguro (evitar inyección SQL)
            if (in_array($colName, ['c.id_compra', 'c.nro_comprobante_proveedor', 'p.nombre_proveedor', 'c.fecha_compra', 'u.nombres', 'c.monto_total', 'c.estado'])) {
                $sqlOrder = " ORDER BY $colName " . ($orderDir === 'asc' ? 'ASC' : 'DESC');
            }
        } else {
            $sqlOrder = " ORDER BY c.id_compra DESC"; // Orden por defecto
        }

        $sqlLimit = " LIMIT :start, :length";

        // Conteo total de registros para el usuario
        $stmtTotal = $this->pdo->prepare("SELECT COUNT(c.id_compra) FROM compras c WHERE c.id_usuario = :id_usuario_count");
        $stmtTotal->execute([':id_usuario_count' => $id_usuario]);
        $recordsTotal = $stmtTotal->fetchColumn();

        // Conteo de registros filtrados
        $stmtFiltered = $this->pdo->prepare("SELECT COUNT(c.id_compra) FROM compras c JOIN tb_proveedores p ON c.id_proveedor = p.id_proveedor JOIN tb_usuarios u ON c.id_usuario = u.id_usuario WHERE c.id_usuario = :id_usuario_filter_count " . $sqlSearch);
        $paramsCount = [':id_usuario_filter_count' => $id_usuario];
        if (!empty($searchValue)) $paramsCount[':search_value'] = "%$searchValue%";
        $stmtFiltered->execute($paramsCount);
        $recordsFiltered = $stmtFiltered->fetchColumn();
        
        // Obtener los datos
        $sqlFinal = $sqlBase . $sqlSearch . $sqlOrder . $sqlLimit;
        $query = $this->pdo->prepare($sqlFinal);
        foreach ($params as $key => $value) {
            $query->bindValue($key, $value);
        }
        $query->bindValue(':start', $start, PDO::PARAM_INT);
        $query->bindValue(':length', $length, PDO::PARAM_INT);
        $query->execute();
        $data = $query->fetchAll(PDO::FETCH_ASSOC);

        return [
            "data" => $data,
            "recordsTotal" => (int)$recordsTotal,
            "recordsFiltered" => (int)$recordsFiltered
        ];
    }

    /**
     * Obtiene los datos maestros de una compra específica.
     * @param int $id_compra
     * @param int $id_usuario (Para verificar propiedad si es necesario)
     * @return array|false
     */
    public function getCompraMaestroById(int $id_compra, int $id_usuario) {
        $sql = "SELECT c.*, p.nombre_proveedor, u.nombres as nombre_usuario_registra
                FROM compras c
                JOIN tb_proveedores p ON c.id_proveedor = p.id_proveedor
                JOIN tb_usuarios u ON c.id_usuario = u.id_usuario
                WHERE c.id_compra = :id_compra AND c.id_usuario = :id_usuario_check";
        $query = $this->pdo->prepare($sql);
        $query->bindParam(':id_compra', $id_compra, PDO::PARAM_INT);
        $query->bindParam(':id_usuario_check', $id_usuario, PDO::PARAM_INT);
        $query->execute();
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene los detalles (productos) de una compra específica.
     * @param int $id_compra
     * @return array
     */
    public function getDetalleProductosByCompraId(int $id_compra): array {
        $sql = "SELECT dc.*, pr.nombre as nombre_producto, pr.codigo as codigo_producto
                FROM detalle_compras dc
                JOIN tb_almacen pr ON dc.id_producto = pr.id_producto
                WHERE dc.id_compra = :id_compra";
        $query = $this->pdo->prepare($sql);
        $query->bindParam(':id_compra', $id_compra, PDO::PARAM_INT);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Anula una compra y revierte el stock de los productos.
     * Esta operación debe ser transaccional.
     * @param int $id_compra
     * @param int $id_usuario_anula
     * @param string $fyh_actualizacion
     * @return bool
     */
    public function anularCompra(int $id_compra, int $id_usuario_anula, string $fyh_actualizacion): bool {
        // Verificar que la compra pertenezca al usuario (o que sea admin) y no esté ya anulada
        $compra = $this->getCompraMaestroById($id_compra, $id_usuario_anula);
        if (!$compra || $compra['estado'] === 'ANULADA') {
            return false; // Compra no existe, no pertenece al usuario o ya está anulada
        }

        $detalles = $this->getDetalleProductosByCompraId($id_compra);

        try {
            $this->pdo->beginTransaction();

            // 1. Actualizar estado de la compra
            $sql_update_compra = "UPDATE compras SET estado = 'ANULADA', fyh_actualizacion = :fyh_actualizacion 
                                  WHERE id_compra = :id_compra AND id_usuario = :id_usuario_check";
            $query_update = $this->pdo->prepare($sql_update_compra);
            $query_update->bindParam(':fyh_actualizacion', $fyh_actualizacion, PDO::PARAM_STR);
            $query_update->bindParam(':id_compra', $id_compra, PDO::PARAM_INT);
            $query_update->bindParam(':id_usuario_check', $id_usuario_anula, PDO::PARAM_INT);
            $query_update->execute();

            // 2. Revertir stock de productos
            foreach ($detalles as $detalle) {
                // Al anular una compra, el stock que se había SUMADO, ahora se RESTA.
                $this->actualizarStockProducto($detalle['id_producto'], (float)$detalle['cantidad'], false); // false para restar
            }

            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Error al anular compra ID $id_compra: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualiza el stock de un producto.
     * @param int $id_producto
     * @param float $cantidad La cantidad a sumar o restar.
     * @param bool $es_suma True para sumar al stock (compra), false para restar (anulación de compra / venta).
     * @return bool
     */
    public function actualizarStockProducto(int $id_producto, float $cantidad, bool $es_suma = true): bool {
        $operador = $es_suma ? '+' : '-';
        $sql = "UPDATE tb_almacen SET stock = stock $operador :cantidad, fyh_actualizacion = NOW() 
                WHERE id_producto = :id_producto";
        $query = $this->pdo->prepare($sql);
        $query->bindParam(':cantidad', $cantidad, PDO::PARAM_STR); // PDO trata decimales como string
        $query->bindParam(':id_producto', $id_producto, PDO::PARAM_INT);
        return $query->execute();
    }

    /**
     * Crea una nueva compra y sus detalles. Operación transaccional.
     * @param array $datos_compra Datos para la tabla 'compras'.
     * @param array $datos_detalle Array de arrays con datos para 'detalle_compras'.
     * @return int|false ID de la compra creada o false en error.
     */
    public function crearNuevaCompra(array $datos_compra, array $datos_detalle): ?int {
        if (empty($datos_detalle)) {
            // No se puede crear una compra sin productos
            return null;
        }

        try {
            $this->pdo->beginTransaction();

            // 1. Insertar en la tabla 'compras'
            $sql_compra = "INSERT INTO compras (id_proveedor, id_usuario, nro_comprobante_proveedor, fecha_compra, 
                                             aplica_iva, porcentaje_iva, subtotal_neto, monto_iva, monto_total, 
                                             estado, observaciones, fyh_creacion, fyh_actualizacion)
                           VALUES (:id_proveedor, :id_usuario, :nro_comprobante_proveedor, :fecha_compra, 
                                   :aplica_iva, :porcentaje_iva, :subtotal_neto, :monto_iva, :monto_total,
                                   :estado, :observaciones, :fyh_creacion, :fyh_actualizacion)";
            $query_compra = $this->pdo->prepare($sql_compra);
            $query_compra->execute([
                ':id_proveedor' => $datos_compra['id_proveedor'],
                ':id_usuario' => $datos_compra['id_usuario'],
                ':nro_comprobante_proveedor' => $datos_compra['nro_comprobante_proveedor'],
                ':fecha_compra' => $datos_compra['fecha_compra'],
                ':aplica_iva' => $datos_compra['aplica_iva'],
                ':porcentaje_iva' => $datos_compra['porcentaje_iva'],
                ':subtotal_neto' => $datos_compra['subtotal_neto'],
                ':monto_iva' => $datos_compra['monto_iva'],
                ':monto_total' => $datos_compra['monto_total'],
                ':estado' => $datos_compra['estado'] ?? 'REGISTRADA',
                ':observaciones' => $datos_compra['observaciones'],
                ':fyh_creacion' => $datos_compra['fyh_creacion'],
                ':fyh_actualizacion' => $datos_compra['fyh_actualizacion']
            ]);
            $id_compra_creada = $this->pdo->lastInsertId();

            if (!$id_compra_creada) {
                throw new Exception("No se pudo obtener el ID de la compra creada.");
            }

            // 2. Insertar en 'detalle_compras' y actualizar stock
            $sql_detalle = "INSERT INTO detalle_compras (id_compra, id_producto, cantidad, precio_compra_unitario, subtotal)
                            VALUES (:id_compra, :id_producto, :cantidad, :precio_compra_unitario, :subtotal)";
            $query_detalle = $this->pdo->prepare($sql_detalle);

            foreach ($datos_detalle as $item) {
                $query_detalle->execute([
                    ':id_compra' => $id_compra_creada,
                    ':id_producto' => $item['id_producto'],
                    ':cantidad' => $item['cantidad'],
                    ':precio_compra_unitario' => $item['precio_compra_unitario'],
                    ':subtotal' => $item['subtotal']
                ]);
                // Actualizar stock del producto (sumar)
                $this->actualizarStockProducto((int)$item['id_producto'], (float)$item['cantidad'], true);
            }

            $this->pdo->commit();
            return (int)$id_compra_creada;

        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Error al crear nueva compra: " . $e->getMessage());
            return null;
        }
    }

    public function getAllComprasByUsuarioId(int $id_usuario): array {
        $sql = "SELECT
                    c.id_compra,
                    c.nro_comprobante_proveedor,
                    p.nombre_proveedor,
                    c.fecha_compra,
                    u.nombres as nombre_usuario_registra,
                    c.aplica_iva,
                    c.porcentaje_iva,
                    c.subtotal_neto,
                    c.monto_iva,
                    c.monto_total,
                    c.estado,
                    c.observaciones,
                    c.fyh_creacion
                FROM compras c
                JOIN tb_proveedores p ON c.id_proveedor = p.id_proveedor
                JOIN tb_usuarios u ON c.id_usuario = u.id_usuario
                WHERE c.id_usuario = :id_usuario_filter
                ORDER BY c.fecha_compra DESC, c.id_compra DESC";
        $query = $this->pdo->prepare($sql);
        $query->bindParam(':id_usuario_filter', $id_usuario, PDO::PARAM_INT);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }
     /**
     * Obtiene todos los proveedores activos para un select.
     * @param int $id_usuario
     * @return array
     */
    public function getProveedoresActivosParaSelect(int $id_usuario): array {
        // Asumiendo que no hay una columna 'estado' en tb_proveedores, los tomamos todos.
        // Si hubiera un estado, filtrarías por ej. WHERE estado = 'ACTIVO'
        $sql = "SELECT id_proveedor, nombre_proveedor FROM tb_proveedores 
                WHERE id_usuario = :id_usuario 
                ORDER BY nombre_proveedor ASC";
        $query = $this->pdo->prepare($sql);
        $query->bindParam(':id_usuario', $id_usuario, PDO::PARAM_INT);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca productos por término para un autocompletar o select dinámico.
     * @param string $termino
     * @param int $id_usuario
     * @param int $limite
     * @return array
     */
    public function buscarProductosParaCompra(string $termino, int $id_usuario, int $limite = 10): array {
        $sql = "SELECT id_producto, codigo, nombre, precio_compra, stock 
                FROM tb_almacen 
                WHERE id_usuario = :id_usuario 
                AND (nombre LIKE :termino_nombre OR codigo LIKE :termino_codigo)
                LIMIT :limite";
        $query = $this->pdo->prepare($sql);
        $query->bindParam(':id_usuario', $id_usuario, PDO::PARAM_INT);
        $query->bindValue(':termino_nombre', "%$termino%", PDO::PARAM_STR);
        $query->bindValue(':termino_codigo', "%$termino%", PDO::PARAM_STR);
        $query->bindParam(':limite', $limite, PDO::PARAM_INT);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>