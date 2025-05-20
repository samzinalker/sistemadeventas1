<?php
// Resumen: Controlador para actualizar un proveedor existente.
// Recibe datos vía POST, valida el ID del proveedor y los datos a actualizar.
// Utiliza ProveedorModel para la actualización y devuelve una respuesta JSON.

header('Content-Type: application/json');
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../utils/funciones_globales.php';
require_once __DIR__ . '/../../models/ProveedorModel.php';

$response = ['status' => 'error', 'message' => 'Error desconocido al actualizar el proveedor.'];

if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['id_usuario'])) {
    $response['message'] = 'Debe iniciar sesión para esta acción.';
    $response['redirectTo'] = $URL . '/login/';
    echo json_encode($response);
    exit();
}
$id_usuario_logueado = (int)$_SESSION['id_usuario'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Método de solicitud no permitido.';
    echo json_encode($response);
    exit();
}

$id_proveedor = filter_var($_POST['id_proveedor_update'] ?? null, FILTER_VALIDATE_INT); // Asegúrate que el form envía este ID
if (!$id_proveedor) {
    $response['message'] = 'ID de proveedor no válido o no proporcionado.';
    echo json_encode($response);
    exit();
}

// Validación de campos (similar a create.php)
$nombre_proveedor = trim($_POST['nombre_proveedor_update'] ?? '');
$celular = trim($_POST['celular_update'] ?? '');
$empresa = trim($_POST['empresa_update'] ?? '');
$direccion = trim($_POST['direccion_update'] ?? '');
$telefono = trim($_POST['telefono_update'] ?? null);
$email = trim($_POST['email_update'] ?? null);

if (empty($nombre_proveedor)) {
    $response['message'] = 'El nombre del proveedor es requerido para actualizar.';
    $response['status'] = 'warning';
    echo json_encode($response);
    exit();
}
if (empty($celular)) {
    $response['message'] = 'El celular del proveedor es requerido para actualizar.';
    $response['status'] = 'warning';
    echo json_encode($response);
    exit();
}
if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $response['message'] = 'El formato del email no es válido para actualizar.';
    $response['status'] = 'warning';
    echo json_encode($response);
    exit();
}
// Añadir más validaciones si es necesario

try {
    $proveedorModel = new ProveedorModel($pdo, $URL);

    // Opcional: Verificar si el nuevo nombre de proveedor ya existe para otro proveedor del mismo usuario
    // if ($proveedorModel->nombreProveedorExisteParaUsuario($nombre_proveedor, $id_usuario_logueado, $id_proveedor)) {
    //     $response['message'] = "Ya existe otro proveedor con el nombre '" . sanear($nombre_proveedor) . "'.";
    //     $response['status'] = 'warning';
    //     echo json_encode($response);
    //     exit();
    // }

    $datos_actualizar = [
        'nombre_proveedor' => $nombre_proveedor,
        'celular' => $celular,
        'telefono' => $telefono ?: null,
        'empresa' => $empresa,
        'email' => $email ?: null,
        'direccion' => $direccion,
        'fyh_actualizacion' => $fechaHora
    ];

    if ($proveedorModel->actualizarProveedor($id_proveedor, $id_usuario_logueado, $datos_actualizar)) {
        $response['status'] = 'success';
        $response['message'] = "Proveedor '" . sanear($nombre_proveedor) . "' actualizado correctamente.";
    } else {
        // Esto podría ser porque el proveedor no existe, no pertenece al usuario, o no hubo cambios reales.
        // El modelo ya verifica pertenencia. Si no hay cambios, la consulta podría no afectar filas.
        $response['message'] = "No se pudo actualizar el proveedor. Verifique los datos o si el proveedor pertenece a su cuenta.";
    }

} catch (PDOException $e) {
    error_log("PDO Error en update_proveedor.php: " . $e->getMessage());
    $response['message'] = "Error de base de datos al actualizar el proveedor.";
} catch (Exception $e) {
    error_log("General Error en update_proveedor.php: " . $e->getMessage());
    $response['message'] = "Error inesperado del servidor al actualizar el proveedor.";
}

echo json_encode($response);
?>