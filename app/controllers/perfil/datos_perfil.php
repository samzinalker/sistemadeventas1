<?php
require_once '../../config.php';
require_once __DIR__ . '/../../models/UsuarioModel.php';

// Iniciar sesión (si no está iniciada)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar si el usuario está autenticado
if (!isset($_SESSION['id_usuario'])) {
    $_SESSION['mensaje'] = "Debe iniciar sesión para realizar esta acción";
    $_SESSION['icono'] = "error";
    header('Location: ' . $URL . '/login');
    exit();
}

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['mensaje'] = "Método no permitido";
    $_SESSION['icono'] = "error";
    header('Location: ' . $URL . '/perfil');
    exit();
}

// Obtener datos del formulario
$password_actual = isset($_POST['password_actual']) ? $_POST['password_actual'] : '';
$password_nueva = isset($_POST['password_nueva']) ? $_POST['password_nueva'] : '';
$password_confirmar = isset($_POST['password_confirmar']) ? $_POST['password_confirmar'] : '';

// Validar datos
if (empty($password_actual) || empty($password_nueva) || empty($password_confirmar)) {
    $_SESSION['mensaje'] = "Todos los campos de contraseña son obligatorios";
    $_SESSION['icono'] = "error";
    header('Location: ' . $URL . '/perfil');
    exit();
}

// Validar longitud mínima de la nueva contraseña
if (strlen($password_nueva) < 6) {
    $_SESSION['mensaje'] = "La nueva contraseña debe tener al menos 6 caracteres";
    $_SESSION['icono'] = "warning";
    header('Location: ' . $URL . '/perfil');
    exit();
}

// Verificar que las nuevas contraseñas coincidan
if ($password_nueva !== $password_confirmar) {
    $_SESSION['mensaje'] = "Las nuevas contraseñas no coinciden";
    $_SESSION['icono'] = "error";
    header('Location: ' . $URL . '/perfil');
    exit();
}

try {
    // Instanciar modelo de usuario
    $usuarioModel = new UsuarioModel($pdo);
    
    // Verificar la contraseña actual
    if (!$usuarioModel->verifyPassword($_SESSION['id_usuario'], $password_actual)) {
        $_SESSION['mensaje'] = "La contraseña actual es incorrecta";
        $_SESSION['icono'] = "error";
        header('Location: ' . $URL . '/perfil');
        exit();
    }
    
    // Actualizar la contraseña
    $resultado = $usuarioModel->updatePassword($_SESSION['id_usuario'], $password_nueva);
    
    if ($resultado === true) {
        $_SESSION['mensaje'] = "Contraseña actualizada correctamente";
        $_SESSION['icono'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error al actualizar la contraseña";
        $_SESSION['icono'] = "error";
    }
    
} catch (Exception $e) {
    // Registrar error en el log
    error_log("Error en actualizar_password.php: " . $e->getMessage());
    
    $_SESSION['mensaje'] = "Error interno del sistema";
    $_SESSION['icono'] = "error";
}

// Redireccionar de vuelta al perfil
header('Location: ' . $URL . '/perfil');
exit();