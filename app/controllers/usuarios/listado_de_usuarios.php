<?php
/**
 * Archivo para listar todos los usuarios
 * Este archivo es incluido desde el index.php principal
 */

// Verificar si hay una sesión activa
if (!isset($_SESSION['id_usuario'])) {
    // Este código no debería ejecutarse normalmente, pero es una capa extra de seguridad
    $_SESSION['mensaje'] = "Error: No se ha iniciado sesión correctamente";
    $_SESSION['icono'] = "error";
    header('Location: ' . $URL . '/login');
    exit();
}

// Sólo administradores pueden ver la lista de usuarios
if ($_SESSION['rol'] !== 'administrador') {
    $usuarios_datos = array(); // Array vacío para evitar errores en la vista
    return;
}

try {
    // Consulta para obtener todos los usuarios con sus roles
    $sql_usuarios = "SELECT u.id_usuario, u.nombres, u.email, u.imagen_perfil, 
                           u.fyh_creacion, u.fyh_actualizacion, r.rol 
                    FROM tb_usuarios u 
                    INNER JOIN tb_roles r ON u.id_rol = r.id_rol 
                    ORDER BY u.id_usuario DESC";
    
    $query_usuarios = $pdo->prepare($sql_usuarios);
    $query_usuarios->execute();
    
    // Verificar si hay resultados
    if ($query_usuarios->rowCount() > 0) {
        $usuarios_datos = $query_usuarios->fetchAll(PDO::FETCH_ASSOC);
        
        // Sanitizar datos para prevenir XSS
        foreach ($usuarios_datos as &$usuario) {
            $usuario['nombres'] = htmlspecialchars($usuario['nombres']);
            $usuario['email'] = htmlspecialchars($usuario['email']);
            $usuario['rol'] = htmlspecialchars($usuario['rol']);
        }
    } else {
        $usuarios_datos = array();
    }
    
} catch (PDOException $e) {
    // Registrar error en el log
    error_log("Error en listado_de_usuarios.php: " . $e->getMessage());
    
    // Establecer array vacío para evitar errores en la vista
    $usuarios_datos = array();
    
    // Opcional: notificar al administrador del sistema sobre el error
    // Esto podría hacerse con una alerta en la interfaz o un email al administrador
}