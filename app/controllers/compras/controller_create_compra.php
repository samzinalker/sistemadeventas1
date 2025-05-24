<?php
include '../../config.php'; // $URL, $pdo, $fechaHora
include '../../utils/funciones_globales.php'; // Para setMensaje, redirigir
include '../../models/ComprasModel.php'; 

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id_usuario'])) {
    setMensaje("Debe iniciar sesión para registrar una compra.", "error");
    redirigir("login/"); // Usar la función redirigir
    exit();
}
$id_usuario_sesion = (int)$_SESSION['id_usuario'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_proveedor = filter_input(INPUT_POST, 'id_proveedor_compra', FILTER_VALIDATE_INT);
    $fecha_compra = filter_input(INPUT_POST, 'fecha_compra_compra', FILTER_SANITIZE_STRING);
    $comprobante = filter_input(INPUT_POST, 'comprobante_compra', FILTER_SANITIZE_STRING);
    
    $item_ids_productos = $_POST['item_id_producto'] ?? [];
    $item_codigos_productos = $_POST['item_codigo_producto'] ?? []; 
    $item_nombres_productos = $_POST['item_nombre_producto'] ?? []; 
    $item_cantidades = $_POST['item_cantidad'] ?? [];      
    $item_precios_unitarios = $_POST['item_precio_unitario'] ?? []; 
    $item_porcentajes_iva = $_POST['item_porcentaje_iva'] ?? [];   
    
    $item_es_nuevo_flags = $_POST['item_es_nuevo'] ?? [];
    $item_nuevas_descripciones = $_POST['item_nueva_descripcion'] ?? [];
    $item_nuevas_ids_categorias = $_POST['item_nueva_id_categoria'] ?? [];
    $item_nuevos_precios_venta = $_POST['item_nuevo_precio_venta'] ?? [];
    $item_nuevos_stocks_minimos = $_POST['item_nuevo_stock_minimo'] ?? [];
    $item_nuevos_stocks_maximos = $_POST['item_nuevo_stock_maximo'] ?? [];
    $item_nuevas_fechas_ingreso = $_POST['item_nueva_fecha_ingreso'] ?? [];

    $errores = [];
    if (!$id_proveedor) $errores[] = "Debe seleccionar un proveedor.";
    if (empty($fecha_compra)) $errores[] = "La fecha de compra es obligatoria.";
    if (empty($item_nombres_productos)) $errores[] = "Debe añadir al menos un producto a la compra.";
    
    $num_items = count($item_nombres_productos);
    if (count($item_ids_productos) !== $num_items || count($item_codigos_productos) !== $num_items ||
        count($item_cantidades) !== $num_items || count($item_precios_unitarios) !== $num_items ||
        count($item_porcentajes_iva) !== $num_items || count($item_es_nuevo_flags) !== $num_items ||
        count($item_nuevas_descripciones) !== $num_items || count($item_nuevas_ids_categorias) !== $num_items ||
        count($item_nuevos_precios_venta) !== $num_items || count($item_nuevos_stocks_minimos) !== $num_items ||
        count($item_nuevos_stocks_maximos) !== $num_items || count($item_nuevas_fechas_ingreso) !== $num_items
        ) {
        $errores[] = "Inconsistencia en los datos de los productos. Intente de nuevo.";
    }

    $items_para_modelo = [];
    if (empty($errores)) {
        for ($i = 0; $i < $num_items; $i++) {
            $es_nuevo = (isset($item_es_nuevo_flags[$i]) && $item_es_nuevo_flags[$i] == '1');
            $id_prod = $es_nuevo ? null : filter_var($item_ids_productos[$i], FILTER_VALIDATE_INT);
            
            $cantidad_str = $item_cantidades[$i] ?? '0';
            $precio_u_str = $item_precios_unitarios[$i] ?? '0';
            $iva_pct_str = $item_porcentajes_iva[$i] ?? '0';

            $cantidad = filter_var($cantidad_str, FILTER_VALIDATE_FLOAT);
            $precio_u = filter_var($precio_u_str, FILTER_VALIDATE_FLOAT);
            $iva_pct = filter_var($iva_pct_str, FILTER_VALIDATE_FLOAT);

            if (($es_nuevo && empty($item_nombres_productos[$i])) || (!$es_nuevo && !$id_prod) || $cantidad === false || $precio_u === false || $iva_pct === false) {
                $errores[] = "Datos inválidos para el producto #" . ($i + 1) . "."; break;
            }
            if ($cantidad <= 0) { $errores[] = "Cantidad del producto #" . ($i + 1) . " debe ser > 0."; break; }
            
            $item_actual = [
                'es_nuevo' => $es_nuevo,
                'id_producto' => $id_prod, 
                'codigo_sugerido' => $item_codigos_productos[$i] ?? null, 
                'nombre_producto' => $item_nombres_productos[$i], 
                'cantidad' => $cantidad,
                'precio_compra_unitario' => $precio_u,
                'porcentaje_iva_item' => $iva_pct,
                'fyh_creacion' => $fechaHora,
                'fyh_actualizacion' => $fechaHora
            ];

            if ($es_nuevo) {
                $item_actual['nueva_descripcion'] = $item_nuevas_descripciones[$i] ?? null;
                $item_actual['nueva_id_categoria'] = filter_var($item_nuevas_ids_categorias[$i] ?? null, FILTER_VALIDATE_INT);
                $item_actual['nuevo_precio_venta'] = filter_var($item_nuevos_precios_venta[$i] ?? 0, FILTER_VALIDATE_FLOAT);
                $item_actual['nuevo_stock_minimo'] = filter_var($item_nuevos_stocks_minimos[$i] ?? null, FILTER_VALIDATE_INT);
                $item_actual['nuevo_stock_maximo'] = filter_var($item_nuevos_stocks_maximos[$i] ?? null, FILTER_VALIDATE_INT);
                $item_actual['nueva_fecha_ingreso'] = $item_nuevas_fechas_ingreso[$i] ?? date('Y-m-d');

                if (empty($item_actual['nombre_producto']) || !$item_actual['nueva_id_categoria'] || $item_actual['nuevo_precio_venta'] === false) {
                     $errores[] = "Faltan datos para el nuevo producto #" . ($i + 1) . " (nombre, categoría o precio venta)."; break;
                }
            }
            $items_para_modelo[] = $item_actual;
        }
    }

    if (!empty($errores)) {
        setMensaje(implode("<br>", $errores), "error");
        redirigir("compras/create.php"); // Usar la función redirigir
        exit();
    }

    $datos_cabecera_compra = [
        'id_usuario' => $id_usuario_sesion,
        'id_proveedor' => $id_proveedor,
        'fecha_compra' => $fecha_compra,
        'comprobante' => $comprobante ?: null,
        'fyh_creacion' => $fechaHora,
        'fyh_actualizacion' => $fechaHora
    ];

    try {
        $compraModel = new CompraModel($pdo);
        $resultado = $compraModel->registrarCompraConDetalles($datos_cabecera_compra, $items_para_modelo);

        if ($resultado && !is_array($resultado)) { 
            setMensaje("Compra registrada exitosamente con ID: " . $resultado, "success");
            redirigir("compras/"); // Usar la función redirigir
            exit();
        } elseif (is_array($resultado) && isset($resultado['error'])) { 
             setMensaje("Error al registrar la compra: " . $resultado['error'], "error");
             redirigir("compras/create.php"); // Usar la función redirigir
             exit();
        }
        else {
            setMensaje("Error al registrar la compra. Intente de nuevo.", "error");
            redirigir("compras/create.php"); // Usar la función redirigir
            exit();
        }
    } catch (PDOException $e) {
        error_log("Error PDO en controller_create_compra.php: " . $e->getMessage());
        setMensaje("Error de base de datos al registrar la compra. Código: " . $e->getCode(), "error");
        redirigir("compras/create.php"); // Usar la función redirigir
        exit();
    } catch (Exception $e) {
        error_log("Error general en controller_create_compra.php: " . $e->getMessage());
        setMensaje("Error inesperado del servidor: " . $e->getMessage(), "error");
        redirigir("compras/create.php"); // Usar la función redirigir
        exit();
    }

} else {
    setMensaje("Método de solicitud no permitido.", "error");
    redirigir("compras/"); // Usar la función redirigir
    exit();
}
?>