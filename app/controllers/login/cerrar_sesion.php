<?php
require_once '../../config.php';
require_once 'LoginController.php';

// Instanciar controlador
$loginController = new LoginController($pdo);

// Cerrar sesión
$loginController->logout();

// Redireccionar al login
header('Location: ' . $URL . '/login');
exit();