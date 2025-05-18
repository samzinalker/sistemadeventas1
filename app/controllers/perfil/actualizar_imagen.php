<?php
require_once '../../config.php';
require_once __DIR__ . '/../../models/UsuarioModel.php';
require_once __DIR__ . '/../../utils/FileUpload.php';

// Iniciar sesión (si no está iniciada)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar si el usuario está autenticado
if (!isset($_SESSION['id_usuario'])) {
    $_SESSION['mensaje'] = "Debe iniciar sesión para realizar esta acción";
    $_SESSION['icono'] = "error";
    header('Location: ' . $URL . '/login');
    exit();
}

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['mensaje'] = "Método no permitido";
    $_SESSION['icono'] = "error";
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

try {
    // Instanciar utilidad para subir archivos
    $fileUpload = new FileUpload();
    
    // Subir la nueva imagen
    $resultado = $fileUpload->uploadProfileImage($_FILES['imagen']);
    
    if ($resultado['status'] === 'success') {
        // Instanciar modelo de usuario
        $usuarioModel = new UsuarioModel($pdo);
        
        // Obtener la imagen de perfil actual para eliminarla luego si no es la predeterminada
        $usuario = $usuarioModel->getById($_SESSION['id_usuario']);
        $imagenAnterior = $usuario['imagen_perfil'];
        
        // Actualizar la imagen de perfil en la base de datos
        $actualizado = $usuarioModel->updateProfileImage($_SESSION['id_usuario'], $resultado['filename']);
        
        if ($actualizado === true) {
            // Eliminar la imagen anterior si existe y no es la predeterminada
            if ($imagenAnterior != 'user_default.png') {
                $rutaImagenAnterior = $_SERVER['DOCUMENT_ROOT'] . '/sistemadeventas/public/images/perfiles/' . $imagenAnterior;
                if (file_exists($rutaImagenAnterior)) {
                    unlink($rutaImagenAnterior);
                }
            }
            
            $_SESSION['mensaje'] = "Imagen de perfil actualizada correctamente";
            $_SESSION['icono'] = "success";
        } else {
            // Si falla la actualización en la base de datos, eliminar la imagen recién subida
            $rutaImagenNueva = $_SERVER['DOCUMENT_ROOT'] . '/sistemadeventas/public/images/perfiles/' . $resultado['filename'];
            if (file_exists($rutaImagenNueva)) {
                unlink($rutaImagenNueva);
            }
            
            $_SESSION['mensaje'] = "Error al actualizar la imagen de perfil en la base de datos";
            $_SESSION['icono'] = "error";
        }
    } else {
        $_SESSION['mensaje'] = $resultado['message'];
        $_SESSION['icono'] = "error";
    }
    
} catch (Exception $e) {
    // Registrar error en el log
    error_log("Error en actualizar_imagen.php: " . $e->getMessage());
    
    $_SESSION['mensaje'] = "Error interno del sistema al procesar la imagen";
    $_SESSION['icono'] = "error";
}

// Redireccionar de vuelta al perfil
header('Location: ' . $URL . '/perfil');
exit();