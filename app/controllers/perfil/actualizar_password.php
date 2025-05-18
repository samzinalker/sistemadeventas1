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
$password_actual = isset($_POST['password_actual']) ? $_POST['password_actual'] : '';
$password_nueva = isset($_POST['password_nueva']) ? $_POST['password_nueva'] : '';
$password_confirmar = isset($_POST['password_confirmar']) ? $_POST['password_confirmar'] : '';

// Instanciar controlador
$perfilController = new PerfilController($pdo);

// Actualizar contraseña
$result = $perfilController->updatePassword($_SESSION['id_usuario'], [
    'password_actual' => $password_actual,
    'password_nueva' => $password_nueva,
    'password_confirmar' => $password_confirmar
]);

// Establecer mensaje de respuesta
$_SESSION['mensaje'] = $result['message'];
$_SESSION['icono'] = $result['status'] === 'success' ? 'success' : 'error';

// Redireccionar al perfil
header('Location: ' . $URL . '/perfil');
exit();