<?php
include '../../config.php';
include '../../utils/funciones_globales.php';
include '../../models/ComprasModel.php'; // Necesitaremos el modelo para la lógica de eliminación

header('Content-Type: application/json');
$response = ['status' => 'error', 'message' => 'Error desconocido al procesar la solicitud.'];

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id_usuario'])) {
    $response['message'] = 'Debe iniciar sesión para realizar esta acción.';
    // Podrías añadir $response['redirectTo'] = $URL . '/login/'; si quieres manejarlo en el JS
    echo json_encode($response);
    exit();
}
$id_usuario_actual = (int)$_SESSION['id_usuario'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['id_compra']) || !filter_var($_POST['id_compra'], FILTER_VALIDATE_INT)) {
        $response['message'] = 'ID de compra no válido o no proporcionado.';
        echo json_encode($response);
        exit();
    }
    $id_compra_a_eliminar = (int)$_POST['id_compra'];

    try {
        $compraModel = new CompraModel($pdo);
        // El método eliminarCompraConDetalles debe manejar la lógica de reversión de stock
        $resultado_eliminacion = $compraModel->eliminarCompraConDetalles($id_compra_a_eliminar, $id_usuario_actual);

        if ($resultado_eliminacion === true) {
            $response['status'] = 'success';
            $response['message'] = 'Compra eliminada exitosamente y stock revertido.';
        } elseif (is_string($resultado_eliminacion)) { // Si el modelo devuelve un mensaje de error específico
            $response['message'] = $resultado_eliminacion;
            $response['status'] = 'warning'; // O 'error' según la naturaleza del mensaje del modelo
        } else {
            // Fallback si el modelo devuelve false o algo inesperado sin mensaje específico
            $response['message'] = 'No se pudo eliminar la compra. Verifique que exista y le pertenezca, o que no esté referenciada de forma que impida su borrado.';
        }

    } catch (PDOException $e) {
        error_log("Error PDO en controller_delete_compra.php: " . $e->getMessage());
        $response['message'] = "Error de base de datos al eliminar la compra. Código: " . $e->getCode();
    } catch (Exception $e) {
        error_log("Error general en controller_delete_compra.php: " . $e->getMessage());
        $response['message'] = "Error inesperado del servidor al eliminar la compra. " . $e->getMessage();
    }

} else {
    $response['message'] = 'Método de solicitud no permitido.';
}

echo json_encode($response);
exit();
?>