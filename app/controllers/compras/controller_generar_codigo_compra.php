<?php
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config.php'; // Para $pdo
require_once __DIR__ . '/../../models/CompraModel.php';

$response = ['status' => 'error', 'message' => 'No se pudo generar el código de compra.'];

if (!isset($_SESSION['id_usuario'])) {
    $response['message'] = 'Usuario no autenticado.';
    echo json_encode($response);
    exit();
}

$id_usuario_sesion = (int)$_SESSION['id_usuario'];

try {
    $compraModel = new CompraModel($pdo);
    $siguienteNroSecuencial = $compraModel->getSiguienteNumeroCompraSecuencial($id_usuario_sesion);
    $codigoFormateado = $compraModel->formatearCodigoCompra($siguienteNroSecuencial);

    $response['status'] = 'success';
    $response['codigo_compra'] = $codigoFormateado;
    $response['nro_secuencial'] = $siguienteNroSecuencial; // Opcional, por si lo necesitas en el form
    $response['message'] = 'Código de compra generado.';

} catch (PDOException $e) {
    error_log("Error PDO en controller_generar_codigo_compra.php: " . $e->getMessage());
    $response['message'] = 'Error de base de datos al generar código.';
} catch (Exception $e) {
    error_log("Error general en controller_generar_codigo_compra.php: " . $e->getMessage());
    $response['message'] = 'Error inesperado al generar código.';
}

echo json_encode($response);
exit();
?>