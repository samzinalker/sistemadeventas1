<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config.php';        // Define $pdo, $URL, $fechaHora
require_once __DIR__ . '/../../utils/funciones_globales.php'; // Para sanear()
require_once __DIR__ . '/../../models/ComprasModel.php';

$response = ['status' => 'error', 'message' => 'Acción no válida o error desconocido.'];

if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['id_usuario'])) {
    $response['message'] = 'Debe iniciar sesión para realizar esta acción.';
    $response['redirectTo'] = $URL . '/login/'; // Para que el JS pueda redirigir
    echo json_encode($response);
    exit();
}
$id_usuario_sesion = (int)$_SESSION['id_usuario'];

$accion = $_POST['accion'] ?? $_GET['accion'] ?? null;

if (!$accion) {
    $response['message'] = 'No se especificó ninguna acción.';
    echo json_encode($response);
    exit();
}

$comprasModel = new ComprasModel($pdo);

try {
    switch ($accion) {
        case 'listar_compras':
            // Parámetros de DataTables
            $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
            $length = isset($_POST['length']) ? intval($_POST['length']) : 10;
            $searchValue = $_POST['search']['value'] ?? null;
            $orderColumnIdx = isset($_POST['order'][0]['column']) ? intval($_POST['order'][0]['column']) : null;
            $orderDir = $_POST['order'][0]['dir'] ?? 'desc';
            
            // Mapeo de columnas de DataTables a columnas de BD (debe coincidir con el JS)
            $columnsMap = [
                0 => 'c.id_compra',
                1 => 'c.nro_comprobante_proveedor',
                2 => 'p.nombre_proveedor',
                3 => 'c.fecha_compra',
                4 => 'u.nombres', // nombre_usuario_registra
                // 5,6,7,8 son montos, el ordenamiento se puede hacer por monto_total
                8 => 'c.monto_total',
                9 => 'c.estado'
            ];

            $resultado = $comprasModel->listarComprasParaDataTable($id_usuario_sesion, $start, $length, $searchValue, $orderColumnIdx, $orderDir, $columnsMap);
            
            $response = [
                "draw" => isset($_POST['draw']) ? intval($_POST['draw']) : 0,
                "recordsTotal" => $resultado['recordsTotal'],
                "recordsFiltered" => $resultado['recordsFiltered'],
                "data" => $resultado['data']
            ];
            break;

        case 'get_detalle_compra':
            $id_compra = filter_var($_POST['id_compra'] ?? null, FILTER_VALIDATE_INT);
            if (!$id_compra) {
                $response['message'] = "ID de compra no válido.";
                break;
            }
            $maestro = $comprasModel->getCompraMaestroById($id_compra, $id_usuario_sesion);
            if ($maestro) {
                $detalle = $comprasModel->getDetalleProductosByCompraId($id_compra);
                $response = [
                    'status' => 'success',
                    'data_maestro' => $maestro,
                    'data_detalle' => $detalle
                ];
            } else {
                $response['message'] = "Compra no encontrada o no tienes permiso para verla.";
            }
            break;

        case 'anular_compra':
            $id_compra_anular = filter_var($_POST['id_compra'] ?? null, FILTER_VALIDATE_INT);
            if (!$id_compra_anular) {
                $response['message'] = "ID de compra para anular no válido.";
                break;
            }
            if ($comprasModel->anularCompra($id_compra_anular, $id_usuario_sesion, $fechaHora)) {
                $response = ['status' => 'success', 'message' => "Compra Nro. $id_compra_anular anulada correctamente y stock revertido."];
            } else {
                $response['message'] = "No se pudo anular la compra. Puede que ya esté anulada, no exista o no tengas permiso.";
            }
            break;
        
        // --- ACCIONES PARA compras/create.php ---
        case 'get_config_iva_defecto':
            $aplica_iva = $comprasModel->getConfiguracion('iva_aplica_compras_defecto');
            $porcentaje_iva = $comprasModel->getConfiguracion('iva_porcentaje_compras_defecto');
            $response = [
                'status' => 'success',
                'aplica_iva' => $aplica_iva ?? '0', // 0 por defecto si no existe
                'porcentaje_iva' => $porcentaje_iva ?? '0.00' // 0.00 por defecto
            ];
            break;

        case 'guardar_config_iva_defecto':
            $aplica_iva_nuevo = isset($_POST['aplica_iva']) ? ($_POST['aplica_iva'] == '1' ? '1' : '0') : '0';
            $porcentaje_iva_nuevo = isset($_POST['porcentaje_iva']) ? number_format((float)$_POST['porcentaje_iva'], 2, '.', '') : '0.00';

            $comprasModel->guardarConfiguracion('iva_aplica_compras_defecto', $aplica_iva_nuevo, $fechaHora);
            $comprasModel->guardarConfiguracion('iva_porcentaje_compras_defecto', $porcentaje_iva_nuevo, $fechaHora);
            
            $response = ['status' => 'success', 'message' => 'Configuración de IVA por defecto guardada.'];
            break;
        
        case 'buscar_productos_compra':
            $termino = trim($_GET['term'] ?? ''); // Para compatibilidad con jQuery UI Autocomplete
            if (empty($termino)) {
                $response['message'] = "Término de búsqueda vacío.";
                echo json_encode([]); // Devuelve array vacío para autocompletar
                exit();
            }
            $productos_encontrados = $comprasModel->buscarProductosParaCompra($termino, $id_usuario_sesion);
            // Formatear para jQuery UI Autocomplete (value, label, y datos adicionales)
            $resultado_autocomplete = [];
            foreach($productos_encontrados as $prod) {
                $resultado_autocomplete[] = [
                    'label' => $prod['codigo'] . ' - ' . $prod['nombre'] . ' (Stock: ' . $prod['stock'] . ')',
                    'value' => $prod['id_producto'], // O el nombre/código si prefieres que se muestre eso en el input
                    'id_producto' => $prod['id_producto'],
                    'codigo' => $prod['codigo'],
                    'nombre' => $prod['nombre'],
                    'precio_compra_sugerido' => $prod['precio_compra'], // precio_compra de tb_almacen
                    'stock_actual' => $prod['stock']
                ];
            }
            echo json_encode($resultado_autocomplete); // No usar $response aquí, solo el array
            exit(); // Importante salir después de json_encode para autocomplete

        case 'registrar_compra':
            // Recoger todos los datos del POST
            $id_proveedor = filter_var($_POST['id_proveedor'] ?? null, FILTER_VALIDATE_INT);
            $nro_comprobante_proveedor = trim($_POST['nro_comprobante_proveedor'] ?? '');
            $fecha_compra = trim($_POST['fecha_compra'] ?? '');
            $aplica_iva_compra = isset($_POST['aplica_iva_compra']) && $_POST['aplica_iva_compra'] == '1';
            $porcentaje_iva_compra = $aplica_iva_compra ? (filter_var($_POST['porcentaje_iva_compra'] ?? 0, FILTER_VALIDATE_FLOAT) ?: 0.00) : 0.00;
            $observaciones_compra = trim($_POST['observaciones_compra'] ?? '');
            
            // Detalles de los productos (esperados como un array de strings JSON)
            $productos_json = $_POST['productos_detalle'] ?? null;
            $productos_detalle_array = $productos_json ? json_decode($productos_json, true) : [];

            // Validaciones básicas
            if (!$id_proveedor || empty($fecha_compra) || empty($productos_detalle_array)) {
                $response['message'] = "Faltan datos del proveedor, fecha o productos para registrar la compra.";
                break;
            }
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_compra)) {
                $response['message'] = "Formato de fecha de compra inválido.";
                break;
            }
            
            // Calcular totales basados en los detalles
            $subtotal_neto_calculado = 0;
            foreach($productos_detalle_array as $prod_detalle) {
                if (!isset($prod_detalle['id_producto'], $prod_detalle['cantidad'], $prod_detalle['precio_unitario']) ||
                    !is_numeric($prod_detalle['cantidad']) || !is_numeric($prod_detalle['precio_unitario']) ||
                    $prod_detalle['cantidad'] <= 0 || $prod_detalle['precio_unitario'] < 0) {
                    $response['message'] = "Datos de producto inválidos en el detalle.";
                    echo json_encode($response); exit(); // Salir temprano
                }
                $subtotal_neto_calculado += (float)$prod_detalle['cantidad'] * (float)$prod_detalle['precio_unitario'];
            }

            $monto_iva_calculado = 0;
            if ($aplica_iva_compra && $porcentaje_iva_compra > 0) {
                $monto_iva_calculado = $subtotal_neto_calculado * ($porcentaje_iva_compra / 100);
            }
            $monto_total_calculado = $subtotal_neto_calculado + $monto_iva_calculado;

            $datos_compra_maestro = [
                'id_proveedor' => $id_proveedor,
                'id_usuario' => $id_usuario_sesion,
                'nro_comprobante_proveedor' => $nro_comprobante_proveedor,
                'fecha_compra' => $fecha_compra,
                'aplica_iva' => $aplica_iva_compra ? 1 : 0,
                'porcentaje_iva' => number_format($porcentaje_iva_compra, 2, '.', ''),
                'subtotal_neto' => number_format($subtotal_neto_calculado, 2, '.', ''),
                'monto_iva' => number_format($monto_iva_calculado, 2, '.', ''),
                'monto_total' => number_format($monto_total_calculado, 2, '.', ''),
                'estado' => 'REGISTRADA',
                'observaciones' => $observaciones_compra,
                'fyh_creacion' => $fechaHora,
                'fyh_actualizacion' => $fechaHora
            ];
            
            $datos_compra_detalle_final = [];
            foreach($productos_detalle_array as $prod_detalle) {
                 $datos_compra_detalle_final[] = [
                    'id_producto' => (int)$prod_detalle['id_producto'],
                    'cantidad' => (float)$prod_detalle['cantidad'],
                    'precio_compra_unitario' => (float)$prod_detalle['precio_unitario'],
                    'subtotal' => (float)$prod_detalle['cantidad'] * (float)$prod_detalle['precio_unitario']
                 ];
            }

            $id_compra_creada = $comprasModel->crearNuevaCompra($datos_compra_maestro, $datos_compra_detalle_final);

            if ($id_compra_creada) {
                $response = [
                    'status' => 'success', 
                    'message' => "Compra Nro. $id_compra_creada registrada correctamente.",
                    'id_compra' => $id_compra_creada
                ];
            } else {
                $response['message'] = "Error al registrar la compra en la base de datos.";
            }
            break;

        default:
            $response['message'] = "Acción '$accion' no reconocida.";
            break;
    }
} catch (PDOException $e) {
    error_log("PDO Error en acciones_compras.php ($accion): " . $e->getMessage());
    $response['message'] = "Error de base de datos procesando la solicitud.";
} catch (Exception $e) {
    error_log("Error general en acciones_compras.php ($accion): " . $e->getMessage());
    $response['message'] = "Error inesperado del servidor: " . $e->getMessage();
}

echo json_encode($response);
?>