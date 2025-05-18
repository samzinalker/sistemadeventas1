<?php
require_once '../../config.php';
require_once 'PerfilController.php';

// Verificar sesión
session_start();
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ' . $URL . '/login');
    exit();
}

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $URL . '/perfil');
    exit();
}

// Verificar que se haya subido un archivo
if (!isset($_FILES['imagen']) || $_FILES['imagen']['error'] === UPLOAD_ERR_NO_FILE) {
    $_SESSION['mensaje'] = "No se ha seleccionado ninguna imagen";
    $_SESSION['icono'] = "error";
    header('Location: ' . $URL . '/perfil');
    exit();
}

// Instanciar controlador
$perfilController = new PerfilController($pdo);

// Actualizar imagen de perfil
$result = $perfilController->updateProfileImage($_SESSION['id_usuario'], $_FILES['imagen']);

// Establecer mensaje de respuesta
$_SESSION['mensaje'] = $result['message'];
$_SESSION['icono'] = $result['status'] === 'success' ? 'success' : 'error';

// Redireccionar al perfil
header('Location: ' . $URL . '/perfil');
exit();