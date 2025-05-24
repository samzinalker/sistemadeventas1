<?php
include '../../config.php'; // $URL, $pdo, $fechaHora
include '../../utils/funciones_globales.php'; // Para setMensaje, redirigir
include '../../models/ComprasModel.php'; 
// AlmacenModel será instanciado dentro de ComprasModel si es necesario

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id_usuario'])) {
    setMensaje("Debe iniciar sesión para actualizar una compra.", "error");
    redirigir("login/");
    exit();
}
$id_usuario_sesion = (int)$_SESSION['id_usuario'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // --- Recoger datos de la cabecera de la compra ---
    $id_compra_a_editar = filter_input(INPUT_POST, 'id_compra_a_editar', FILTER_VALIDATE_INT);
    $id_proveedor = filter_input(INPUT_POST, 'id_proveedor_compra', FILTER_VALIDATE_INT);
    $fecha_compra = filter_input(INPUT_POST, 'fecha_compra_compra', FILTER_SANITIZE_STRING);
    $comprobante = filter_input(INPUT_POST, 'comprobante_compra', FILTER_SANITIZE_STRING);

    // --- Recoger datos de los ítems ---
    // Estos son arrays que vienen del formulario de edición
    $item_ids_detalle_compra = $_POST['item_id_detalle_compra'] ?? []; // ID del detalle original (si existe)
    $item_estados = $_POST['item_estado'] ?? []; // 'existente', 'modificado', 'eliminado', 'nuevo_item_existente_almacen', 'nuevo_almacen'
    $item_ids_productos = $_POST['item_id_producto'] ?? []; // ID del producto en tb_almacen
    $item_codigos_productos = $_POST['item_codigo_producto'] ?? []; 
    $item_nombres_productos = $_POST['item_nombre_producto'] ?? []; 
    $item_cantidades = $_POST['item_cantidad'] ?? [];      
    $item_precios_unitarios = $_POST['item_precio_unitario'] ?? []; 
    $item_porcentajes_iva = $_POST['item_porcentaje_iva'] ?? [];   
    
    // Datos para productos nuevos que se crean en almacén
    $item_es_nuevo_flags = $_POST['item_es_nuevo'] ?? []; // '1' si es nuevo para almacén, '0' si no
    $item_nuevas_descripciones = $_POST['item_nueva_descripcion'] ?? [];
    $item_nuevas_ids_categorias = $_POST['item_nueva_id_categoria'] ?? [];
    $item_nuevos_precios_venta = $_POST['item_nuevo_precio_venta'] ?? [];
    $item_nuevos_stocks_minimos = $_POST['item_nuevo_stock_minimo'] ?? [];
    $item_nuevos_stocks_maximos = $_POST['item_nuevo_stock_maximo'] ?? [];
    $item_nuevas_fechas_ingreso = $_POST['item_nueva_fecha_ingreso'] ?? [];

    // --- Validaciones básicas ---
    $errores = [];
    if (!$id_compra_a_editar) $errores[] = "ID de compra a editar no proporcionado.";
    if (!$id_proveedor) $errores[] = "Debe seleccionar un proveedor.";
    if (empty($fecha_compra)) $errores[] = "La fecha de compra es obligatoria.";
    
    // Contar el número de ítems activos (no marcados como 'eliminado' que aún están en el DOM)
    $num_items_activos = 0;
    foreach ($item_estados as $estado) {
        if ($estado !== 'eliminado') {
            $num_items_activos++;
        }
    }
    if ($num_items_activos === 0 && count($item_nombres_productos) > 0) { // Si todos los items fueron eliminados
         $errores[] = "Una compra no puede quedar sin productos. Si desea eliminar todos, cancele la edición y elimine la compra desde el listado.";
    } elseif (empty($item_nombres_productos)) { // Si no se envió ningún producto (debería ser prevenido por JS)
        $errores[] = "Debe haber al menos un producto en la compra.";
    }


    // Validar consistencia de arrays de ítems
    $num_items_total_enviados = count($item_nombres_productos);
    if (
        count($item_ids_detalle_compra) !== $num_items_total_enviados ||
        count($item_estados) !== $num_items_total_enviados ||
        count($item_ids_productos) !== $num_items_total_enviados ||
        count($item_codigos_productos) !== $num_items_total_enviados ||
        count($item_cantidades) !== $num_items_total_enviados ||
        count($item_precios_unitarios) !== $num_items_total_enviados ||
        count($item_porcentajes_iva) !== $num_items_total_enviados ||
        count($item_es_nuevo_flags) !== $num_items_total_enviados ||
        count($item_nuevas_descripciones) !== $num_items_total_enviados ||
        count($item_nuevas_ids_categorias) !== $num_items_total_enviados ||
        count($item_nuevos_precios_venta) !== $num_items_total_enviados ||
        count($item_nuevos_stocks_minimos) !== $num_items_total_enviados ||
        count($item_nuevos_stocks_maximos) !== $num_items_total_enviados ||
        count($item_nuevas_fechas_ingreso) !== $num_items_total_enviados
    ) {
        $errores[] = "Inconsistencia en los datos de los productos enviados. Intente de nuevo.";
    }

    // --- Procesar y validar cada ítem ---
    $items_para_modelo = [];
    if (empty($errores)) {
        for ($i = 0; $i < $num_items_total_enviados; $i++) {
            $estado_item_actual = $item_estados[$i];
            
            // Validaciones comunes para ítems no eliminados
            if ($estado_item_actual !== 'eliminado') {
                $cantidad_str = $item_cantidades[$i] ?? '0';
                $precio_u_str = $item_precios_unitarios[$i] ?? '0';
                $iva_pct_str = $item_porcentajes_iva[$i] ?? '0';

                $cantidad = filter_var($cantidad_str, FILTER_VALIDATE_FLOAT);
                $precio_u = filter_var($precio_u_str, FILTER_VALIDATE_FLOAT);
                $iva_pct = filter_var($iva_pct_str, FILTER_VALIDATE_FLOAT);

                if (empty($item_nombres_productos[$i]) || $cantidad === false || $precio_u === false || $iva_pct === false) {
                    $errores[] = "Datos inválidos (nombre, cantidad, precio o IVA) para el producto #" . ($i + 1) . "."; break;
                }
                if ($cantidad <= 0) {
                    $errores[] = "La cantidad del producto '" . htmlspecialchars($item_nombres_productos[$i]) . "' debe ser mayor a cero."; break;
                }
            } else { // Para ítems eliminados, solo necesitamos el id_detalle_compra si existe
                $cantidad = 0; // No relevante pero evitamos notice
                $precio_u = 0;
                $iva_pct = 0;
            }
            
            $item_actual_para_modelo = [
                'id_detalle_compra' => filter_var($item_ids_detalle_compra[$i], FILTER_VALIDATE_INT) ?: null,
                'estado' => $estado_item_actual,
                'id_producto' => filter_var($item_ids_productos[$i], FILTER_VALIDATE_INT) ?: null,
                'codigo_producto' => $item_codigos_productos[$i] ?? null,
                'nombre_producto' => $item_nombres_productos[$i],
                'cantidad' => $cantidad,
                'precio_compra_unitario' => $precio_u,
                'porcentaje_iva_item' => $iva_pct,
                'fyh_actualizacion' => $fechaHora // Se usa para actualizar o crear
            ];

            // Si es un producto nuevo para el almacén (estado 'nuevo_almacen')
            if ($estado_item_actual === 'nuevo_almacen' && isset($item_es_nuevo_flags[$i]) && $item_es_nuevo_flags[$i] == '1') {
                $item_actual_para_modelo['es_nuevo_almacen'] = true; // Flag para el modelo
                $item_actual_para_modelo['nueva_descripcion'] = $item_nuevas_descripciones[$i] ?? null;
                $item_actual_para_modelo['nueva_id_categoria'] = filter_var($item_nuevas_ids_categorias[$i] ?? null, FILTER_VALIDATE_INT);
                $item_actual_para_modelo['nuevo_precio_venta'] = filter_var($item_nuevos_precios_venta[$i] ?? 0, FILTER_VALIDATE_FLOAT);
                $item_actual_para_modelo['nuevo_stock_minimo'] = filter_var($item_nuevos_stocks_minimos[$i] ?? null, FILTER_VALIDATE_INT);
                $item_actual_para_modelo['nuevo_stock_maximo'] = filter_var($item_nuevos_stocks_maximos[$i] ?? null, FILTER_VALIDATE_INT);
                $item_actual_para_modelo['nueva_fecha_ingreso'] = $item_nuevas_fechas_ingreso[$i] ?? date('Y-m-d');
                $item_actual_para_modelo['fyh_creacion_nuevo_producto'] = $fechaHora; // Para el nuevo producto en almacen

                if (empty($item_actual_para_modelo['nombre_producto']) || !$item_actual_para_modelo['nueva_id_categoria'] || $item_actual_para_modelo['nuevo_precio_venta'] === false) {
                     $errores[] = "Faltan datos para el nuevo producto a crear en almacén: '" . htmlspecialchars($item_actual_para_modelo['nombre_producto']) . "' (nombre, categoría o precio venta)."; break;
                }
            } else {
                $item_actual_para_modelo['es_nuevo_almacen'] = false;
            }
            $items_para_modelo[] = $item_actual_para_modelo;
        }
    }

    if (!empty($errores)) {
        setMensaje(implode("<br>", $errores), "error");
        // Redirigir de vuelta a la página de edición
        redirigir("compras/edit.php?id=" . $id_compra_a_editar);
        exit();
    }

    // --- Preparar datos para el modelo ---
    $datos_cabecera_compra_actualizada = [
        'id_proveedor' => $id_proveedor,
        'fecha_compra' => $fecha_compra,
        'comprobante' => $comprobante ?: null,
        'fyh_actualizacion' => $fechaHora
        // Los totales se recalcularán en el modelo
    ];

    try {
        $compraModel = new CompraModel($pdo);
        // Llamar al nuevo método en el modelo (que crearemos a continuación)
        $resultado = $compraModel->actualizarCompraConDetalles($id_compra_a_editar, $id_usuario_sesion, $datos_cabecera_compra_actualizada, $items_para_modelo);

        if ($resultado === true) { 
            setMensaje("Compra actualizada exitosamente.", "success");
            redirigir("compras/show.php?id=" . $id_compra_a_editar); // Redirigir a la vista de la compra
            exit();
        } elseif (is_array($resultado) && isset($resultado['error'])) { 
             setMensaje("Error al actualizar la compra: " . htmlspecialchars($resultado['error']), "error");
        } else {
            setMensaje("Error desconocido al actualizar la compra. Intente de nuevo.", "error");
        }
    } catch (PDOException $e) {
        error_log("Error PDO en controller_update_compra.php: " . $e->getMessage());
        setMensaje("Error de base de datos al actualizar la compra. Código: " . $e->getCode(), "error");
    } catch (Exception $e) {
        error_log("Error general en controller_update_compra.php: " . $e->getMessage());
        setMensaje("Error inesperado del servidor: " . htmlspecialchars($e->getMessage()), "error");
    }

    // Si llegamos aquí, hubo un error, redirigir de vuelta a la edición
    redirigir("compras/edit.php?id=" . $id_compra_a_editar);
    exit();

} else {
    setMensaje("Método de solicitud no permitido.", "error");
    redirigir("compras/");
    exit();
}
?>