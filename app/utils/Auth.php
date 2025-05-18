<?php
/**
 * Clase para manejar autenticación y autorización
 */
class Auth {
    /**
     * Verificar si el usuario tiene permiso de administrador
     */
    public static function esAdministrador() {
        if (!isset($_SESSION['rol'])) {
            return false;
        }
        
        return $_SESSION['rol'] === 'administrador';
    }
    
    /**
     * Redireccionar si el usuario no tiene permisos
     */
    public static function requireAdmin($URL) {
        if (!self::esAdministrador()) {
            $_SESSION['mensaje'] = "No tienes permisos para acceder a esta página";
            $_SESSION['icono'] = "error";
            header('Location: ' . $URL . '/index.php');
            exit();
        }
    }
    
    /**
     * Verificar si hay una sesión activa
     */
    public static function verificarSesion($URL) {
        if (!isset($_SESSION['id_usuario']) || !isset($_SESSION['rol'])) {
            $_SESSION['mensaje'] = "Debes iniciar sesión para acceder a esta página";
            $_SESSION['icono'] = "error";
            header('Location: ' . $URL . '/login');
            exit();
        }
    }
    
    /**
     * Verificar que el usuario pueda modificar un registro específico
     */
    public static function puedeModificar($id_usuario_objetivo, $URL) {
        // Si es el propio usuario o es administrador, permitir modificar
        if ($_SESSION['id_usuario'] == $id_usuario_objetivo || self::esAdministrador()) {
            return true;
        }
        
        $_SESSION['mensaje'] = "No tienes permisos para modificar este usuario";
        $_SESSION['icono'] = "error";
        header('Location: ' . $URL . '/usuarios/');
        exit();
    }
}