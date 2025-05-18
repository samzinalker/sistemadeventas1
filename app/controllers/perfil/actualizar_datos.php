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

// Obtener datos del formulario
$nombres = isset($_POST['nombres']) ? trim($_POST['nombres']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';

// Instanciar controlador
$perfilController = new PerfilController($pdo);

// Actualizar datos personales
$result = $perfilController->updatePersonalData($_SESSION['id_usuario'], [
    'nombres' => $nombres,
    'email' => $email
]);

// Establecer mensaje de respuesta
$_SESSION['mensaje'] = $result['message'];
$_SESSION['icono'] = $result['status'] === 'success' ? 'success' : 'error';

// Redireccionar al perfil
header('Location: ' . $URL . '/perfil');
exit();