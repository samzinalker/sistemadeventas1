<?php

require_once __DIR__ . '/AlmacenModel.php'; 

class CompraModel {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function getSiguienteNumeroCompraSecuencial(int $id_usuario): int {
        $sql = "SELECT MAX(nro_compra) as max_nro FROM tb_compras WHERE id_usuario = :id_usuario";
        $query = $this->pdo->prepare($sql);
        $query->bindParam(':id_usuario', $id_usuario, PDO::PARAM_INT);
        $query->execute();
        $max_nro = $query->fetchColumn();
        return ($max_nro === null) ? 1 : (int)$max_nro + 1;
    }
    public function formatearCodigoCompra(int $numero_secuencial): string {
        return "C-" . str_pad($numero_secuencial, 5, "0", STR_PAD_LEFT);
    }

    public function registrarCompraConDetalles(array $datosCabecera, array $datosItems) {
        $almacenModel = new AlmacenModel($this->pdo);
        $id_usuario_compra = (int)$datosCabecera['id_usuario']; 

        try {
            $this->pdo->beginTransaction();

            $nro_compra_secuencial = $this->getSiguienteNumeroCompraSecuencial($id_usuario_compra);
            $codigo_compra_formateado = $this->formatearCodigoCompra($nro_compra_secuencial);

            $subtotal_general_calculado = 0;
            $monto_iva_general_calculado = 0;

            foreach ($datosItems as $item_data) {
                $subtotal_item = (float)$item_data['cantidad'] * (float)$item_data['precio_compra_unitario'];
                $monto_iva_item = $subtotal_item * ((float)$item_data['porcentaje_iva_item'] / 100);
                $subtotal_general_calculado += $subtotal_item;
                $monto_iva_general_calculado += $monto_iva_item;
            }
            $total_general_calculado = $subtotal_general_calculado + $monto_iva_general_calculado;
            
            $sql_compra = "INSERT INTO tb_compras 
                                (nro_compra, codigo_compra_referencia, fecha_compra, id_proveedor, comprobante, id_usuario, 
                                 subtotal_general, monto_iva_general, total_general, fyh_creacion, fyh_actualizacion) 
                           VALUES 
                                (:nro_compra, :codigo_compra_referencia, :fecha_compra, :id_proveedor, :comprobante, :id_usuario, 
                                 :subtotal_general, :monto_iva_general, :total_general, :fyh_creacion, :fyh_actualizacion)";
            
            $query_compra = $this->pdo->prepare($sql_compra);
            $query_compra->bindValue(':nro_compra', $nro_compra_secuencial, PDO::PARAM_INT);
            $query_compra->bindValue(':codigo_compra_referencia', $codigo_compra_formateado, PDO::PARAM_STR); // Usar el código de referencia del formulario
            $query_compra->bindValue(':fecha_compra', $datosCabecera['fecha_compra'], PDO::PARAM_STR);
            $query_compra->bindValue(':id_proveedor', $datosCabecera['id_proveedor'], PDO::PARAM_INT);
            $query_compra->bindValue(':comprobante', $datosCabecera['comprobante'], PDO::PARAM_STR);
            $query_compra->bindValue(':id_usuario', $id_usuario_compra, PDO::PARAM_INT);
            $query_compra->bindValue(':subtotal_general', $subtotal_general_calculado, PDO::PARAM_STR); 
            $query_compra->bindValue(':monto_iva_general', $monto_iva_general_calculado, PDO::PARAM_STR);
            $query_compra->bindValue(':total_general', $total_general_calculado, PDO::PARAM_STR);
            $query_compra->bindValue(':fyh_creacion', $datosCabecera['fyh_creacion'], PDO::PARAM_STR);
            $query_compra->bindValue(':fyh_actualizacion', $datosCabecera['fyh_actualizacion'], PDO::PARAM_STR);

            if (!$query_compra->execute()) {
                throw new PDOException("Error al insertar la cabecera de la compra: " . implode(", ", $query_compra->errorInfo()));
            }
            $id_compra_nueva = $this->pdo->lastInsertId();

            $sql_detalle = "INSERT INTO tb_detalle_compras 
                                (id_compra, id_producto, cantidad, precio_compra_unitario, porcentaje_iva_item, 
                                 subtotal_item, monto_iva_item, total_item, fyh_creacion, fyh_actualizacion)
                            VALUES
                                (:id_compra, :id_producto, :cantidad, :precio_compra_unitario, :porcentaje_iva_item, 
                                 :subtotal_item, :monto_iva_item, :total_item, :fyh_creacion, :fyh_actualizacion)";
            $query_detalle = $this->pdo->prepare($sql_detalle);

            foreach ($datosItems as $item) {
                $id_producto_final_detalle = null;

                if (isset($item['es_nuevo']) && $item['es_nuevo'] == '1') {
                    $codigo_nuevo_producto = $almacenModel->generarCodigoProducto($id_usuario_compra);
                    
                    $datos_nuevo_almacen = [
                        'codigo' => $codigo_nuevo_producto,
                        'nombre' => $item['nombre_producto'],
                        'descripcion' => $item['nueva_descripcion'] ?? null,
                        'stock' => (int)$item['cantidad'], 
                        'stock_minimo' => $item['nuevo_stock_minimo'] ?? null,
                        'stock_maximo' => $item['nuevo_stock_maximo'] ?? null,
                        'precio_compra' => (float)$item['precio_compra_unitario'],
                        'precio_venta' => (float)($item['nuevo_precio_venta'] ?? 0),
                        'iva_predeterminado' => (float)$item['porcentaje_iva_item'],
                        'fecha_ingreso' => $item['nueva_fecha_ingreso'] ?? $datosCabecera['fecha_compra'],
                        'imagen' => 'default_product.png',
                        'id_usuario' => $id_usuario_compra,
                        'id_categoria' => (int)$item['nueva_id_categoria'],
                        'fyh_creacion' => $item['fyh_creacion'], 
                        'fyh_actualizacion' => $item['fyh_actualizacion']
                    ];

                    $id_producto_final_detalle = $almacenModel->crearProducto($datos_nuevo_almacen);
                    if (!$id_producto_final_detalle) {
                        throw new Exception("Error al crear el nuevo producto '{$item['nombre_producto']}' en almacén.");
                    }
                } else {
                    $id_producto_final_detalle = (int)$item['id_producto'];
                    if (!$almacenModel->ajustarStockProducto($id_producto_final_detalle, (float)$item['cantidad'], $id_usuario_compra)) {
                        throw new Exception("Error al actualizar stock del producto existente ID: {$id_producto_final_detalle}.");
                    }
                }

                $subtotal_item_calc = (float)$item['cantidad'] * (float)$item['precio_compra_unitario'];
                $monto_iva_item_calc = $subtotal_item_calc * ((float)$item['porcentaje_iva_item'] / 100);
                $total_item_calc = $subtotal_item_calc + $monto_iva_item_calc;

                $query_detalle->bindValue(':id_compra', $id_compra_nueva, PDO::PARAM_INT);
                $query_detalle->bindValue(':id_producto', $id_producto_final_detalle, PDO::PARAM_INT);
                $query_detalle->bindValue(':cantidad', $item['cantidad'], PDO::PARAM_STR); 
                $query_detalle->bindValue(':precio_compra_unitario', $item['precio_compra_unitario'], PDO::PARAM_STR);
                $query_detalle->bindValue(':porcentaje_iva_item', $item['porcentaje_iva_item'], PDO::PARAM_STR);
                $query_detalle->bindValue(':subtotal_item', $subtotal_item_calc, PDO::PARAM_STR);
                $query_detalle->bindValue(':monto_iva_item', $monto_iva_item_calc, PDO::PARAM_STR);
                $query_detalle->bindValue(':total_item', $total_item_calc, PDO::PARAM_STR);
                $query_detalle->bindValue(':fyh_creacion', $item['fyh_creacion'], PDO::PARAM_STR);
                $query_detalle->bindValue(':fyh_actualizacion', $item['fyh_actualizacion'], PDO::PARAM_STR);

                if (!$query_detalle->execute()) {
                    throw new PDOException("Error al insertar detalle para producto ID {$id_producto_final_detalle}: " . implode(", ", $query_detalle->errorInfo()));
                }
            }

            $this->pdo->commit();
            return $id_compra_nueva; 

        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Error PDO en ComprasModel->registrarCompraConDetalles: " . $e->getMessage() . " | SQLState: " . $e->getCode() . " | ErrorInfo: " . print_r($e->errorInfo, true));
            return ['error' => "Error de base de datos: " . $e->getMessage()];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Error General en ComprasModel->registrarCompraConDetalles: " . $e->getMessage());
            return ['error' => "Error general: " . $e->getMessage()];
        }
    }
    
    public function getComprasPorUsuarioId(int $id_usuario): array {
        $sql = "SELECT 
                    c.id_compra, 
                    c.nro_compra, 
                    c.codigo_compra_referencia, 
                    c.fecha_compra, 
                    c.comprobante, 
                    c.total_general,
                    c.fyh_creacion,
                    p.nombre_proveedor,
                    p.empresa as empresa_proveedor
                FROM 
                    tb_compras as c
                INNER JOIN 
                    tb_proveedores as p ON c.id_proveedor = p.id_proveedor
                WHERE 
                    c.id_usuario = :id_usuario
                ORDER BY 
                    c.fecha_compra DESC, c.id_compra DESC";
        
        $query = $this->pdo->prepare($sql);
        $query->bindParam(':id_usuario', $id_usuario, PDO::PARAM_INT);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCompraConDetallesPorId(int $id_compra, int $id_usuario) {
        $sql_cabecera = "SELECT 
                            c.*, 
                            p.nombre_proveedor, 
                            p.celular as celular_proveedor, 
                            p.telefono as telefono_proveedor, 
                            p.empresa as empresa_proveedor, 
                            p.email as email_proveedor, 
                            p.direccion as direccion_proveedor
                         FROM tb_compras as c
                         INNER JOIN tb_proveedores as p ON c.id_proveedor = p.id_proveedor
                         WHERE c.id_compra = :id_compra AND c.id_usuario = :id_usuario";
        
        $query_cabecera = $this->pdo->prepare($sql_cabecera);
        $query_cabecera->bindParam(':id_compra', $id_compra, PDO::PARAM_INT);
        $query_cabecera->bindParam(':id_usuario', $id_usuario, PDO::PARAM_INT);
        $query_cabecera->execute();
        $compra_cabecera = $query_cabecera->fetch(PDO::FETCH_ASSOC);

        if (!$compra_cabecera) {
            return false; 
        }

        $sql_detalles = "SELECT 
                            dc.*,
                            prod.nombre as nombre_producto,
                            prod.codigo as codigo_producto
                         FROM tb_detalle_compras as dc
                         INNER JOIN tb_almacen as prod ON dc.id_producto = prod.id_producto
                         WHERE dc.id_compra = :id_compra
                         ORDER BY dc.id_detalle_compra ASC";
        
        $query_detalles = $this->pdo->prepare($sql_detalles);
        $query_detalles->bindParam(':id_compra', $id_compra, PDO::PARAM_INT);
        $query_detalles->execute();
        $compra_detalles = $query_detalles->fetchAll(PDO::FETCH_ASSOC);

        $compra_cabecera['detalles'] = $compra_detalles;

        return $compra_cabecera;
    }

    // =======================================================================================
    // === MÉTODO NUEVO PARA ACTUALIZAR COMPRA Y SUS DETALLES ===
    // =======================================================================================
    public function actualizarCompraConDetalles(int $id_compra, int $id_usuario, array $datosCabecera, array $itemsRecibidos) {
        $almacenModel = new AlmacenModel($this->pdo);
        $fyh_actual = date('Y-m-d H:i:s'); // Fecha y hora actual para fyh_actualizacion

        try {
            $this->pdo->beginTransaction();

            // 1. Verificar que la compra pertenezca al usuario
            $compra_original = $this->getCompraConDetallesPorId($id_compra, $id_usuario);
            if (!$compra_original) {
                throw new Exception("Compra no encontrada o no tienes permiso para editarla.");
            }
            $detalles_originales_map = []; // Mapear detalles originales por id_detalle_compra para fácil acceso
            foreach ($compra_original['detalles'] as $detalle_orig) {
                $detalles_originales_map[$detalle_orig['id_detalle_compra']] = $detalle_orig;
            }

            // 2. Actualizar cabecera de la compra
            $sql_update_compra = "UPDATE tb_compras SET
                                    id_proveedor = :id_proveedor,
                                    fecha_compra = :fecha_compra,
                                    comprobante = :comprobante,
                                    fyh_actualizacion = :fyh_actualizacion
                                  WHERE id_compra = :id_compra AND id_usuario = :id_usuario";
            $query_update_compra = $this->pdo->prepare($sql_update_compra);
            $query_update_compra->bindParam(':id_proveedor', $datosCabecera['id_proveedor'], PDO::PARAM_INT);
            $query_update_compra->bindParam(':fecha_compra', $datosCabecera['fecha_compra'], PDO::PARAM_STR);
            $query_update_compra->bindParam(':comprobante', $datosCabecera['comprobante'], PDO::PARAM_STR);
            $query_update_compra->bindParam(':fyh_actualizacion', $fyh_actual, PDO::PARAM_STR);
            $query_update_compra->bindParam(':id_compra', $id_compra, PDO::PARAM_INT);
            $query_update_compra->bindParam(':id_usuario', $id_usuario, PDO::PARAM_INT);
            if (!$query_update_compra->execute()) {
                throw new PDOException("Error al actualizar la cabecera de la compra.");
            }

            // --- Preparar statements para detalles ---
            $sql_insert_detalle = "INSERT INTO tb_detalle_compras (id_compra, id_producto, cantidad, precio_compra_unitario, porcentaje_iva_item, subtotal_item, monto_iva_item, total_item, fyh_creacion, fyh_actualizacion) VALUES (:id_compra, :id_producto, :cantidad, :precio_compra_unitario, :porcentaje_iva_item, :subtotal_item, :monto_iva_item, :total_item, :fyh_creacion, :fyh_actualizacion)";
            $stmt_insert_detalle = $this->pdo->prepare($sql_insert_detalle);

            $sql_update_detalle = "UPDATE tb_detalle_compras SET id_producto = :id_producto, cantidad = :cantidad, precio_compra_unitario = :precio_compra_unitario, porcentaje_iva_item = :porcentaje_iva_item, subtotal_item = :subtotal_item, monto_iva_item = :monto_iva_item, total_item = :total_item, fyh_actualizacion = :fyh_actualizacion WHERE id_detalle_compra = :id_detalle_compra AND id_compra = :id_compra_cond";
            $stmt_update_detalle = $this->pdo->prepare($sql_update_detalle);
            
            $sql_delete_detalle = "DELETE FROM tb_detalle_compras WHERE id_detalle_compra = :id_detalle_compra AND id_compra = :id_compra_cond";
            $stmt_delete_detalle = $this->pdo->prepare($sql_delete_detalle);

            // --- Procesar cada ítem recibido ---
            $ids_detalles_procesados = []; // Para rastrear qué detalles originales ya se manejaron

            foreach ($itemsRecibidos as $item) {
                $id_detalle_actual = $item['id_detalle_compra']; // Puede ser null si es un ítem nuevo
                $estado_item = $item['estado'];
                $id_producto_item = $item['id_producto']; // Puede ser null si es 'nuevo_almacen'

                if ($id_detalle_actual) {
                    $ids_detalles_procesados[] = $id_detalle_actual;
                }
                
                // Calcular valores del ítem
                $cantidad_item = (float)$item['cantidad'];
                $precio_unitario_item = (float)$item['precio_compra_unitario'];
                $porcentaje_iva_item_val = (float)$item['porcentaje_iva_item'];
                $subtotal_item_calc = $cantidad_item * $precio_unitario_item;
                $monto_iva_item_calc = $subtotal_item_calc * ($porcentaje_iva_item_val / 100);
                $total_item_calc = $subtotal_item_calc + $monto_iva_item_calc;

                if ($estado_item === 'eliminado') {
                    if ($id_detalle_actual && isset($detalles_originales_map[$id_detalle_actual])) {
                        $detalle_original_a_eliminar = $detalles_originales_map[$id_detalle_actual];
                        // Revertir stock
                        $cantidad_a_revertir = - (float)$detalle_original_a_eliminar['cantidad'];
                        if (!$almacenModel->ajustarStockProducto((int)$detalle_original_a_eliminar['id_producto'], $cantidad_a_revertir, $id_usuario)) {
                            throw new Exception("Error al revertir stock para el producto ID " . $detalle_original_a_eliminar['id_producto'] . " del detalle eliminado.");
                        }
                        // Eliminar detalle
                        $stmt_delete_detalle->bindParam(':id_detalle_compra', $id_detalle_actual, PDO::PARAM_INT);
                        $stmt_delete_detalle->bindParam(':id_compra_cond', $id_compra, PDO::PARAM_INT);
                        if (!$stmt_delete_detalle->execute()) {
                            throw new PDOException("Error al eliminar el detalle de compra ID " . $id_detalle_actual);
                        }
                    }
                } elseif ($estado_item === 'modificado') {
                    if ($id_detalle_actual && isset($detalles_originales_map[$id_detalle_actual])) {
                        $detalle_original_modificar = $detalles_originales_map[$id_detalle_actual];
                        // Calcular diferencia de stock
                        $diferencia_cantidad = $cantidad_item - (float)$detalle_original_modificar['cantidad'];
                        if ($diferencia_cantidad != 0) {
                             if (!$almacenModel->ajustarStockProducto((int)$detalle_original_modificar['id_producto'], $diferencia_cantidad, $id_usuario)) {
                                throw new Exception("Error al ajustar stock para el producto ID " . $detalle_original_modificar['id_producto'] . " del detalle modificado.");
                            }
                        }
                        // Actualizar detalle
                        $stmt_update_detalle->bindParam(':id_producto', $id_producto_item, PDO::PARAM_INT);
                        $stmt_update_detalle->bindParam(':cantidad', $item['cantidad'], PDO::PARAM_STR);
                        $stmt_update_detalle->bindParam(':precio_compra_unitario', $item['precio_compra_unitario'], PDO::PARAM_STR);
                        $stmt_update_detalle->bindParam(':porcentaje_iva_item', $item['porcentaje_iva_item'], PDO::PARAM_STR);
                        $stmt_update_detalle->bindParam(':subtotal_item', $subtotal_item_calc, PDO::PARAM_STR);
                        $stmt_update_detalle->bindParam(':monto_iva_item', $monto_iva_item_calc, PDO::PARAM_STR);
                        $stmt_update_detalle->bindParam(':total_item', $total_item_calc, PDO::PARAM_STR);
                        $stmt_update_detalle->bindParam(':fyh_actualizacion', $fyh_actual, PDO::PARAM_STR);
                        $stmt_update_detalle->bindParam(':id_detalle_compra', $id_detalle_actual, PDO::PARAM_INT);
                        $stmt_update_detalle->bindParam(':id_compra_cond', $id_compra, PDO::PARAM_INT);
                        if (!$stmt_update_detalle->execute()) {
                            throw new PDOException("Error al actualizar el detalle de compra ID " . $id_detalle_actual);
                        }
                    } else {
                         throw new Exception("Se intentó modificar un detalle (ID: {$id_detalle_actual}) que no existe en la compra original.");
                    }
                } elseif ($estado_item === 'nuevo_item_existente_almacen' || $estado_item === 'nuevo_almacen') {
                    $id_producto_para_nuevo_detalle = $id_producto_item; // Para 'nuevo_item_existente_almacen'

                    if ($estado_item === 'nuevo_almacen' && $item['es_nuevo_almacen']) {
                        // Crear producto en tb_almacen
                        $codigo_nuevo_prod_almacen = $almacenModel->generarCodigoProducto($id_usuario);
                        $datos_nuevo_prod_almacen = [
                            'codigo' => $codigo_nuevo_prod_almacen,
                            'nombre' => $item['nombre_producto'],
                            'descripcion' => $item['nueva_descripcion'] ?? null,
                            'stock' => $cantidad_item, // Stock inicial es la cantidad comprada
                            'stock_minimo' => $item['nuevo_stock_minimo'] ?? null,
                            'stock_maximo' => $item['nuevo_stock_maximo'] ?? null,
                            'precio_compra' => $precio_unitario_item,
                            'precio_venta' => (float)($item['nuevo_precio_venta'] ?? 0),
                            'iva_predeterminado' => $porcentaje_iva_item_val,
                            'fecha_ingreso' => $item['nueva_fecha_ingreso'] ?? $datosCabecera['fecha_compra'],
                            'imagen' => 'default_product.png',
                            'id_usuario' => $id_usuario,
                            'id_categoria' => (int)$item['nueva_id_categoria'],
                            'fyh_creacion' => $item['fyh_creacion_nuevo_producto'] ?? $fyh_actual,
                            'fyh_actualizacion' => $item['fyh_creacion_nuevo_producto'] ?? $fyh_actual
                        ];
                        $id_producto_para_nuevo_detalle = $almacenModel->crearProducto($datos_nuevo_prod_almacen);
                        if (!$id_producto_para_nuevo_detalle) {
                            throw new Exception("Error al crear el nuevo producto '{$item['nombre_producto']}' en almacén durante la edición.");
                        }
                        // No se ajusta stock aquí porque ya se creó con el stock inicial.
                    } else { // 'nuevo_item_existente_almacen'
                         if (!$almacenModel->ajustarStockProducto((int)$id_producto_para_nuevo_detalle, $cantidad_item, $id_usuario)) {
                            throw new Exception("Error al ajustar stock para el nuevo ítem (producto existente ID: {$id_producto_para_nuevo_detalle}).");
                        }
                    }

                    // Insertar nuevo detalle
                    $stmt_insert_detalle->bindParam(':id_compra', $id_compra, PDO::PARAM_INT);
                    $stmt_insert_detalle->bindParam(':id_producto', $id_producto_para_nuevo_detalle, PDO::PARAM_INT);
                    $stmt_insert_detalle->bindParam(':cantidad', $item['cantidad'], PDO::PARAM_STR);
                    $stmt_insert_detalle->bindParam(':precio_compra_unitario', $item['precio_compra_unitario'], PDO::PARAM_STR);
                    $stmt_insert_detalle->bindParam(':porcentaje_iva_item', $item['porcentaje_iva_item'], PDO::PARAM_STR);
                    $stmt_insert_detalle->bindParam(':subtotal_item', $subtotal_item_calc, PDO::PARAM_STR);
                    $stmt_insert_detalle->bindParam(':monto_iva_item', $monto_iva_item_calc, PDO::PARAM_STR);
                    $stmt_insert_detalle->bindParam(':total_item', $total_item_calc, PDO::PARAM_STR);
                    $stmt_insert_detalle->bindParam(':fyh_creacion', $fyh_actual, PDO::PARAM_STR); // Fecha de creación del detalle
                    $stmt_insert_detalle->bindParam(':fyh_actualizacion', $fyh_actual, PDO::PARAM_STR);
                    if (!$stmt_insert_detalle->execute()) {
                        throw new PDOException("Error al insertar nuevo detalle de compra para producto ID " . $id_producto_para_nuevo_detalle);
                    }
                }
                // Los ítems con estado 'existente' (sin cambios) no requieren acción en la BD aquí.
            }

            // 4. Eliminar detalles que estaban en la compra original pero no se enviaron (y no fueron marcados como 'eliminado' explícitamente)
            // Esto es un fallback, el JS debería enviar todos los items originales con estado 'eliminado' si se borran.
            foreach ($detalles_originales_map as $id_detalle_orig => $detalle_orig_data) {
                if (!in_array($id_detalle_orig, $ids_detalles_procesados)) {
                    // Este detalle original no fue procesado (ni modificado, ni eliminado explícitamente, ni era nuevo).
                    // Esto podría indicar un error en el JS o que el item fue borrado del DOM sin marcarlo como 'eliminado'.
                    // Por seguridad, lo eliminamos y revertimos stock.
                    error_log("[COMPRA EDIT] Detalle ID {$id_detalle_orig} no procesado, se asume eliminado.");
                    $cantidad_a_revertir_imp = - (float)$detalle_orig_data['cantidad'];
                    if (!$almacenModel->ajustarStockProducto((int)$detalle_orig_data['id_producto'], $cantidad_a_revertir_imp, $id_usuario)) {
                        throw new Exception("Error al revertir stock para el producto ID " . $detalle_orig_data['id_producto'] . " de un detalle implícitamente eliminado.");
                    }
                    $stmt_delete_detalle->bindParam(':id_detalle_compra', $id_detalle_orig, PDO::PARAM_INT);
                    $stmt_delete_detalle->bindParam(':id_compra_cond', $id_compra, PDO::PARAM_INT);
                    if (!$stmt_delete_detalle->execute()) {
                        throw new PDOException("Error al eliminar el detalle de compra ID " . $id_detalle_orig . " (implícito).");
                    }
                }
            }


            // 5. Recalcular totales generales de la compra y actualizar tb_compras
            $sql_recalcular_totales = "SELECT 
                                        SUM(subtotal_item) as nuevo_subtotal, 
                                        SUM(monto_iva_item) as nuevo_iva,
                                        SUM(total_item) as nuevo_total
                                     FROM tb_detalle_compras 
                                     WHERE id_compra = :id_compra_recalc";
            $query_recalc = $this->pdo->prepare($sql_recalcular_totales);
            $query_recalc->bindParam(':id_compra_recalc', $id_compra, PDO::PARAM_INT);
            $query_recalc->execute();
            $nuevos_totales = $query_recalc->fetch(PDO::FETCH_ASSOC);

            if ($nuevos_totales) {
                $sql_update_totales_compra = "UPDATE tb_compras SET
                                                subtotal_general = :subtotal_general,
                                                monto_iva_general = :monto_iva_general,
                                                total_general = :total_general,
                                                fyh_actualizacion = :fyh_actualizacion_totales
                                              WHERE id_compra = :id_compra_upd_totales";
                $query_update_totales = $this->pdo->prepare($sql_update_totales_compra);
                $query_update_totales->bindValue(':subtotal_general', $nuevos_totales['nuevo_subtotal'] ?? 0, PDO::PARAM_STR);
                $query_update_totales->bindValue(':monto_iva_general', $nuevos_totales['nuevo_iva'] ?? 0, PDO::PARAM_STR);
                $query_update_totales->bindValue(':total_general', $nuevos_totales['nuevo_total'] ?? 0, PDO::PARAM_STR);
                $query_update_totales->bindParam(':fyh_actualizacion_totales', $fyh_actual, PDO::PARAM_STR);
                $query_update_totales->bindParam(':id_compra_upd_totales', $id_compra, PDO::PARAM_INT);
                if (!$query_update_totales->execute()) {
                     throw new PDOException("Error al actualizar los totales generales de la compra.");
                }
            } else { // Si no hay detalles (todos eliminados), poner totales a 0
                 $sql_update_totales_compra_cero = "UPDATE tb_compras SET
                                                subtotal_general = 0,
                                                monto_iva_general = 0,
                                                total_general = 0,
                                                fyh_actualizacion = :fyh_actualizacion_totales_cero
                                              WHERE id_compra = :id_compra_upd_totales_cero";
                $query_update_totales_cero = $this->pdo->prepare($sql_update_totales_compra_cero);
                $query_update_totales_cero->bindParam(':fyh_actualizacion_totales_cero', $fyh_actual, PDO::PARAM_STR);
                $query_update_totales_cero->bindParam(':id_compra_upd_totales_cero', $id_compra, PDO::PARAM_INT);
                 if (!$query_update_totales_cero->execute()) {
                     throw new PDOException("Error al actualizar los totales generales de la compra a cero.");
                }
            }

            $this->pdo->commit();
            return true;

        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Error PDO en ComprasModel->actualizarCompraConDetalles: " . $e->getMessage() . " | SQLState: " . $e->getCode() . " | ErrorInfo: " . print_r($e->errorInfo, true));
            return ['error' => "Error de base de datos al actualizar: " . $e->getMessage()];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Error General en ComprasModel->actualizarCompraConDetalles: " . $e->getMessage());
            return ['error' => "Error general al actualizar: " . $e->getMessage()];
        }
    }


    public function eliminarCompraConDetalles(int $id_compra, int $id_usuario): mixed {
        $almacenModel = new AlmacenModel($this->pdo);
        $sql_check_compra = "SELECT id_usuario FROM tb_compras WHERE id_compra = :id_compra";
        $stmt_check = $this->pdo->prepare($sql_check_compra);
        $stmt_check->bindParam(':id_compra', $id_compra, PDO::PARAM_INT);
        $stmt_check->execute();
        $compra_owner = $stmt_check->fetchColumn();

        if ($compra_owner === false) return "La compra no existe.";
        if ((int)$compra_owner !== $id_usuario) return "No tienes permiso para eliminar esta compra.";

        $sql_detalles = "SELECT id_producto, cantidad FROM tb_detalle_compras WHERE id_compra = :id_compra";
        $query_detalles = $this->pdo->prepare($sql_detalles);
        $query_detalles->bindParam(':id_compra', $id_compra, PDO::PARAM_INT);
        $query_detalles->execute();
        $items_a_revertir = $query_detalles->fetchAll(PDO::FETCH_ASSOC);
        
        try {
            $this->pdo->beginTransaction();
            foreach ($items_a_revertir as $item) {
                $cantidad_a_restar = - (float)$item['cantidad']; 
                if (!$almacenModel->ajustarStockProducto($item['id_producto'], $cantidad_a_restar, $id_usuario)) {
                    throw new Exception("Error al revertir el stock para el producto ID: " . $item['id_producto'] . " de la compra ID: " . $id_compra);
                }
            }
            $sql_delete_detalles = "DELETE FROM tb_detalle_compras WHERE id_compra = :id_compra";
            $stmt_delete_detalles = $this->pdo->prepare($sql_delete_detalles);
            $stmt_delete_detalles->bindParam(':id_compra', $id_compra, PDO::PARAM_INT);
            if (!$stmt_delete_detalles->execute()) {
                 throw new PDOException("Error al eliminar los detalles de la compra ID: " . $id_compra);
            }
            $sql_delete_compra = "DELETE FROM tb_compras WHERE id_compra = :id_compra AND id_usuario = :id_usuario";
            $stmt_delete_compra = $this->pdo->prepare($sql_delete_compra);
            $stmt_delete_compra->bindParam(':id_compra', $id_compra, PDO::PARAM_INT);
            $stmt_delete_compra->bindParam(':id_usuario', $id_usuario, PDO::PARAM_INT);
            if (!$stmt_delete_compra->execute()) {
                throw new PDOException("Error al eliminar la cabecera de la compra ID: " . $id_compra);
            }
            if ($stmt_delete_compra->rowCount() > 0) {
                $this->pdo->commit();
                return true; 
            } else {
                throw new Exception("La compra ID {$id_compra} no se encontró para eliminar o ya fue eliminada (rowCount 0).");
            }
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Error PDO en ComprasModel->eliminarCompraConDetalles para compra ID {$id_compra}: " . $e->getMessage());
            return "Error de base de datos al eliminar la compra.";
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Error General en ComprasModel->eliminarCompraConDetalles para compra ID {$id_compra}: " . $e->getMessage());
            return "Error general del sistema: " . $e->getMessage();
        }
    }
}
?>