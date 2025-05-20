<?php
// Resumen: Controlador para crear un nuevo proveedor.
// Recibe datos vía POST, los valida, y utiliza ProveedorModel para la inserción.
// Devuelve una respuesta JSON indicando el resultado de la operación.

header('Content-Type: application/json');
require_once __DIR__ . '/../../config.php';        // Define $pdo, $URL, $fechaHora
require_once __DIR__ . '/../../utils/funciones_globales.php'; // Para sanear()
require_once __DIR__ . '/../../models/ProveedorModel.php';

$response = ['status' => 'error', 'message' => 'Error desconocido al crear el proveedor.'];

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

// Validación de campos (ejemplo básico, expandir según necesidad)
$nombre_proveedor = trim($_POST['nombre_proveedor'] ?? '');
$celular = trim($_POST['celular'] ?? '');
$empresa = trim($_POST['empresa'] ?? '');
$direccion = trim($_POST['direccion'] ?? '');
$telefono = trim($_POST['telefono'] ?? null); // Opcional
$email = trim($_POST['email'] ?? null);       // Opcional

// Validaciones básicas
if (empty($nombre_proveedor)) {
    $response['message'] = 'El nombre del proveedor es requerido.';
    $response['status'] = 'warning';
    echo json_encode($response);
    exit();
}
if (empty($celular)) {
    $response['message'] = 'El celular del proveedor es requerido.';
    $response['status'] = 'warning';
    echo json_encode($response);
    exit();
}
// Aquí podrías añadir más validaciones: longitud, formato de email, formato de teléfono/celular, etc.
if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $response['message'] = 'El formato del email no es válido.';
    $response['status'] = 'warning';
    echo json_encode($response);
    exit();
}


try {
    $proveedorModel = new ProveedorModel($pdo, $URL);

    // Opcional: Verificar si el nombre del proveedor ya existe para este usuario
    // if ($proveedorModel->nombreProveedorExisteParaUsuario($nombre_proveedor, $id_usuario_logueado)) {
    //     $response['message'] = "Ya existe un proveedor con el nombre '" . sanear($nombre_proveedor) . "'.";
    //     $response['status'] = 'warning';
    //     echo json_encode($response);
    //     exit();
    // }

    $datos_proveedor = [
        'nombre_proveedor' => $nombre_proveedor,
        'celular' => $celular,
        'telefono' => $telefono ?: null, // Guardar null si está vacío
        'empresa' => $empresa,
        'email' => $email ?: null, // Guardar null si está vacío
        'direccion' => $direccion,
        'id_usuario' => $id_usuario_logueado,
        'fyh_creacion' => $fechaHora,
        'fyh_actualizacion' => $fechaHora
    ];

    $creadoId = $proveedorModel->crearProveedor($datos_proveedor);

    if ($creadoId) {
        $response['status'] = 'success';
        $response['message'] = "Proveedor '" . sanear($nombre_proveedor) . "' registrado correctamente.";
    } else {
        $response['message'] = "No se pudo registrar el proveedor en la base de datos.";
    }

} catch (PDOException $e) {
    error_log("PDO Error en create_proveedor.php: " . $e->getMessage());
    $response['message'] = "Error de base de datos al crear el proveedor.";
} catch (Exception $e) {
    error_log("General Error en create_proveedor.php: " . $e->getMessage());
    $response['message'] = "Error inesperado del servidor al crear el proveedor.";
}

echo json_encode($response);
?>