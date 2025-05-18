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
$nombres = isset($_POST['nombres']) ? trim($_POST['nombres']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';

// Validar datos
if (empty($nombres) || strlen($nombres) < 2) {
    $_SESSION['mensaje'] = "El nombre es obligatorio y debe tener al menos 2 caracteres";
    $_SESSION['icono'] = "error";
    header('Location: ' . $URL . '/perfil');
    exit();
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['mensaje'] = "Debe proporcionar un email válido";
    $_SESSION['icono'] = "error";
    header('Location: ' . $URL . '/perfil');
    exit();
}

try {
    // Instanciar modelo de usuario
    $usuarioModel = new UsuarioModel($pdo);
    
    // Verificar si el email ya está en uso por otro usuario
    $usuarioExistente = $usuarioModel->findByEmail($email);
    if ($usuarioExistente && $usuarioExistente['id_usuario'] != $_SESSION['id_usuario']) {
        $_SESSION['mensaje'] = "El email ya está en uso por otro usuario";
        $_SESSION['icono'] = "error";
        header('Location: ' . $URL . '/perfil');
        exit();
    }
    
    // Preparar datos para actualizar
    $datosUsuario = [
        'nombres' => $nombres,
        'email' => $email,
        'fyh_actualizacion' => date('Y-m-d H:i:s')
    ];
    
    // Actualizar datos
    $resultado = $usuarioModel->update($_SESSION['id_usuario'], $datosUsuario);
    
    if ($resultado === true) {
        // Actualizar datos en la sesión
        $_SESSION['nombres'] = $nombres;
        $_SESSION['sesion_email'] = $email;
        
        $_SESSION['mensaje'] = "Datos personales actualizados correctamente";
        $_SESSION['icono'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error al actualizar los datos personales";
        $_SESSION['icono'] = "error";
    }
    
} catch (Exception $e) {
    // Registrar error en el log
    error_log("Error en actualizar_datos.php: " . $e->getMessage());
    
    $_SESSION['mensaje'] = "Error interno del sistema";
    $_SESSION['icono'] = "error";
}

// Redireccionar de vuelta al perfil
header('Location: ' . $URL . '/perfil');
exit();