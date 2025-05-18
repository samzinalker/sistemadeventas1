<?php
include('../../config.php');
require_once('../../utils/Validator.php');
require_once('funciones.php');
require_once('../../models/UsuarioModel.php');

session_start();

try {
    // Validar campos requeridos
    $campos_requeridos = ['nombres', 'email', 'rol', 'password_user', 'password_repeat'];
    $campos_faltantes = Validator::requiredFields($_POST, $campos_requeridos);
    
    if (!empty($campos_faltantes)) {
        setMensaje("Todos los campos son obligatorios", "error");
        redirigir('/usuarios/create.php');
    }
    
    // Obtener y limpiar datos
    $nombres = trim($_POST['nombres']);
    $email = trim($_POST['email']);
    $rol = $_POST['rol'];
    $password = $_POST['password_user'];
    $password_repeat = $_POST['password_repeat'];
    
    // Validar email
    if (!Validator::isValidEmail($email)) {
        setMensaje("El formato del correo electrónico no es válido", "error");
        redirigir('/usuarios/create.php');
    }
    
    // Inicializar modelo
    $usuarioModel = new UsuarioModel($pdo);
    
    // Verificar si el correo ya está registrado
    if ($usuarioModel->emailExiste($email)) {
        setMensaje("El correo ya está registrado. Intente con otro", "error");
        redirigir('/usuarios/create.php');
    }
    
    // Validar y procesar contraseña
    list($password_hash, $error) = procesarPassword($password, $password_repeat);
    
    if ($error) {
        setMensaje($error, "error");
        redirigir('/usuarios/create.php');
    }
    
    // Crear el usuario
    $resultado = $usuarioModel->crear($nombres, $email, $rol, $password_hash, $fechaHora);
    
    if ($resultado) {
        setMensaje("Usuario registrado correctamente", "success");
        redirigir('/usuarios/');
    } else {
        throw new Exception("Error al registrar el usuario");
    }
    
} catch (Exception $e) {
    setMensaje("Error: " . $e->getMessage(), "error");
    redirigir('/usuarios/create.php');
}