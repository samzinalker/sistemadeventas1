<?php
/**
 * Funciones comunes para el módulo de usuarios
 */

/**
 * Obtener modelo de usuario
 */
function getUsuarioModel() {
    global $pdo;
    require_once(__DIR__ . '/../../models/UsuarioModel.php');
    return new UsuarioModel($pdo);
}

/**
 * Configurar mensaje de sesión para el usuario
 */
function setMensaje($mensaje, $icono) {
    $_SESSION['mensaje'] = $mensaje;
    $_SESSION['icono'] = $icono;
}

/**
 * Redireccionar al usuario
 */
function redirigir($ruta) {
    global $URL;
    header('Location: ' . $URL . $ruta);
    exit();
}

/**
 * Procesar contraseña
 * 
 * @return array [hash, error]
 */
function procesarPassword($password, $password_repeat) {
    require_once(__DIR__ . '/../../utils/Validator.php');
    
    if (empty($password) && empty($password_repeat)) {
        return [null, null]; // No hay cambio de contraseña
    }
    
    if (!Validator::passwordsMatch($password, $password_repeat)) {
        return [null, "Las contraseñas no coinciden"];
    }
    
    if (!Validator::passwordLength($password)) {
        return [null, "La contraseña debe tener al menos 6 caracteres"];
    }
    
    return [password_hash($password, PASSWORD_DEFAULT), null];
}