<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../models/AlmacenModel.php'; // Solo AlmacenModel es necesario aquí

$response = ['status' => 'error', 'message' => 'Producto no encontrado o acceso denegado.'];

if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['id_usuario'])) {
    $response['message'] = 'Debe iniciar sesión para ver los detalles.';
    $response['redirectTo'] = $URL . '/login/';
    echo json_encode($response);
    exit();
}
$id_usuario_logueado = (int)$_SESSION['id_usuario'];

if (!isset($_GET['id_producto'])) {
    $response['message'] = 'ID de producto no proporcionado.';
    echo json_encode($response);
    exit();
}

$id_producto = filter_var($_GET['id_producto'], FILTER_VALIDATE_INT);
if (!$id_producto) {
    $response['message'] = 'ID de producto no válido.';
    echo json_encode($response);
    exit();
}

try {
    $almacenModel = new AlmacenModel($pdo);
    $producto = $almacenModel->getProductoByIdAndUsuarioId($id_producto, $id_usuario_logueado);

    if ($producto) {
        $producto['imagen_url'] = $URL . '/almacen/img_productos/' . ($producto['imagen'] ?: 'default_product.png');
        // Formatear fechas para consistencia si es necesario
        // $producto['fecha_ingreso_formato'] = date('d/m/Y', strtotime($producto['fecha_ingreso']));
        // $producto['fyh_actualizacion_formato'] = $producto['fyh_actualizacion'] != '0000-00-00 00:00:00' ? date('d/m/Y H:i:s', strtotime($producto['fyh_actualizacion'])) : 'N/A';
        
        $response['status'] = 'success';
        $response['data'] = $producto;
        unset($response['message']); 
    }
    // Si no se encuentra, el mensaje de error por defecto es adecuado.
} catch (Exception $e) {
    error_log("Error en get_producto.php: " . $e->getMessage());
    $response['message'] = "Error del servidor al obtener datos del producto.";
}

echo json_encode($response);
?>