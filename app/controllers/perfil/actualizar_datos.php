<?php
require_once '../../config.php';
require_once __DIR__ . '/PerfilController.php';

// Iniciar sesión (si no está iniciada)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar si el usuario está autenticado
if (!isset($_SESSION['id_usuario'])) {
    $_SESSION['mensaje'] = "Debe iniciar sesión para realizar esta acción";
    $_SESSION['icono'] = "error";
    header('Location: ' . BASE_URL . '/login');
    exit();
}

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['mensaje'] = "Método no permitido";
    $_SESSION['icono'] = "error";
    header('Location: ' . BASE_URL . '/perfil');
    exit();
}

// Obtener datos del formulario
$nombres = isset($_POST['nombres']) ? trim($_POST['nombres']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';

// Validar datos
if (empty($nombres) || strlen($nombres) < 2) {
    $_SESSION['mensaje'] = "El nombre es obligatorio y debe tener al menos 2 caracteres";
    $_SESSION['icono'] = "error";
    header('Location: ' . BASE_URL . '/perfil');
    exit();
}

try {
    // Instanciar controlador
    $perfilController = new PerfilController($pdo);
    
    // Llamar al método para actualizar datos
    $resultado = $perfilController->updatePersonalData($_SESSION['id_usuario'], $_POST);
    
    // Establecer mensaje según resultado
    $_SESSION['mensaje'] = $resultado['message'];
    $_SESSION['icono'] = $resultado['status'] === 'success' ? 'success' : 'error';
    
} catch (Exception $e) {
    // Registrar error en el log
    error_log("Error en actualizar_datos.php: " . $e->getMessage());
    
    $_SESSION['mensaje'] = "Error interno del sistema";
    $_SESSION['icono'] = "error";
}

// Redireccionar de vuelta al perfil
header('Location: ' . $URL . '/perfil');
exit();