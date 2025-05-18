<?php
require_once '../../config.php';
require_once 'LoginController.php';

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $URL . '/login');
    exit();
}

// Obtener datos del formulario
$email = isset($_POST['email']) ? $_POST['email'] : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

// Instanciar controlador
$loginController = new LoginController($pdo);

// Autenticar usuario
$result = $loginController->authenticate($email, $password);

// Establecer mensaje de respuesta
session_start();
$_SESSION['mensaje'] = $result['message'];
$_SESSION['icono'] = $result['status'] === 'success' ? 'success' : 'error';

if ($result['status'] === 'success') {
    // Redireccionar a la página principal
    header('Location: ' . $URL . '/index.php');
} else {
    // Redireccionar al login
    header('Location: ' . $URL . '/login');
}
exit();