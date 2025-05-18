<?php
// Asumimos que config.php (para $pdo, $URL, $fechaHora) y funciones.php ya están cargados 
// en el script que llama a este controlador (usuarios/create.php o un router central).
// Si no, deben incluirse aquí. Por seguridad, verificamos:
if (session_status() === PHP_SESSION_NONE) {
    session_start(); // Necesario para los mensajes de sesión
}

// Incluir dependencias
require_once __DIR__ . '/../../config.php'; // Para $pdo, $URL, $fechaHora
require_once __DIR__ . '/../../models/UsuarioModel.php';
require_once __DIR__ . '/../../utils/Validator.php'; // Asumiendo que existe y tiene los métodos necesarios
require_once __DIR__ . '/funciones.php'; // Para setMensaje, redirigir, procesarPassword

// Verificar que la solicitud sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setMensaje("Acceso no permitido.", "error");
    redirigir('/usuarios/create.php'); // Redirigir a la página del formulario
}

// Instanciar modelos
$usuarioModel = new UsuarioModel($pdo, $URL);

// Validar campos requeridos
$campos_requeridos = ['nombres', 'email', 'rol', 'password_user', 'password_repeat'];
$campos_faltantes = Validator::requiredFields($_POST, $campos_requeridos);

if (!empty($campos_faltantes)) {
    $campos_str = implode(', ', $campos_faltantes);
    setMensaje("Los siguientes campos son obligatorios: {$campos_str}.", "error");
    redirigir('/usuarios/create.php');
}

// Obtener y limpiar datos del formulario
$nombres = trim($_POST['nombres']);
$email = trim($_POST['email']);
$id_rol = filter_input(INPUT_POST, 'rol', FILTER_VALIDATE_INT);
$password = $_POST['password_user']; // No trim, la contraseña puede tener espacios intencionales
$password_repeat = $_POST['password_repeat'];

// Validaciones adicionales
if (!Validator::isValidEmail($email)) {
    setMensaje("El formato del correo electrónico no es válido.", "error");
    redirigir('/usuarios/create.php');
}

if ($id_rol === false || $id_rol <= 0) {
    setMensaje("Seleccione un rol válido.", "error");
    redirigir('/usuarios/create.php');
}

// Verificar si el correo ya está registrado
if ($usuarioModel->emailExiste($email)) {
    setMensaje("El correo electrónico '{$email}' ya está registrado. Intente con otro.", "error");
    redirigir('/usuarios/create.php');
}

// Validar y procesar contraseña
list($password_hash, $error_password) = procesarPassword($password, $password_repeat); // De funciones.php

if ($error_password) {
    setMensaje($error_password, "error");
    redirigir('/usuarios/create.php');
}

// Crear el usuario
// La variable $fechaHora viene de config.php
$creado = $usuarioModel->crearUsuario($nombres, $email, $password_hash, $id_rol, $fechaHora);

if ($creado) {
    setMensaje("Usuario registrado correctamente.", "success");
    redirigir('/usuarios/'); // Redirigir al listado de usuarios
} else {
    setMensaje("Error al registrar el usuario. Inténtelo de nuevo.", "error");
    redirigir('/usuarios/create.php');
}

?>