<?php
require_once '../../config.php';
require_once __DIR__ . '/../../models/UsuarioModel.php';

// Iniciar sesión (si no está iniciada)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Registrar la salida del usuario en el log (opcional)
if (isset($_SESSION['id_usuario'])) {
    $usuario_id = $_SESSION['id_usuario'];
    error_log("Usuario ID: $usuario_id cerró sesión el " . date('Y-m-d H:i:s'));
}

// Destruir todas las variables de sesión
$_SESSION = array();

// Si se desea destruir la cookie de sesión
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destruir la sesión
session_destroy();

// Establecer mensaje de salida
session_start();
$_SESSION['mensaje'] = "Ha cerrado sesión correctamente";
$_SESSION['icono'] = "success";

// Redireccionar al login
header("Location: $URL/login");
exit();