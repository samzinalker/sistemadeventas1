<?php
/**
 * Archivo para listar todos los roles
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

// Sólo administradores pueden ver la lista de roles
if ($_SESSION['rol'] !== 'administrador') {
    $roles_datos = array(); // Array vacío para evitar errores en la vista
    return;
}

try {
    // Consulta para obtener todos los roles con conteo de usuarios
    $sql_roles = "SELECT r.id_rol, r.rol, r.fyh_creacion, r.fyh_actualizacion, 
                        COUNT(u.id_usuario) AS cantidad_usuarios 
                 FROM tb_roles r 
                 LEFT JOIN tb_usuarios u ON r.id_rol = u.id_rol 
                 GROUP BY r.id_rol 
                 ORDER BY r.id_rol ASC";
    
    $query_roles = $pdo->prepare($sql_roles);
    $query_roles->execute();
    
    // Verificar si hay resultados
    if ($query_roles->rowCount() > 0) {
        $roles_datos = $query_roles->fetchAll(PDO::FETCH_ASSOC);
        
        // Sanitizar datos para prevenir XSS
        foreach ($roles_datos as &$rol) {
            $rol['rol'] = htmlspecialchars($rol['rol']);
        }
    } else {
        $roles_datos = array();
    }
    
} catch (PDOException $e) {
    // Registrar error en el log
    error_log("Error en listado_de_roles.php: " . $e->getMessage());
    
    // Establecer array vacío para evitar errores en la vista
    $roles_datos = array();
    
    // Opcional: notificar al administrador del sistema sobre el error
}