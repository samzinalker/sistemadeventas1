<?php
// No necesitamos ruta relativa, usamos constantes definidas en config.php
// El archivo config.php ya está incluido desde perfil/index.php

// Verificar que haya una sesión activa
if (!isset($_SESSION['id_usuario'])) {
    // Redirigir si no hay sesión
    $_SESSION['mensaje'] = "Error: No se ha iniciado sesión correctamente";
    $_SESSION['icono'] = "error";
    header('Location: ' . BASE_URL . '/login');
    exit();
}

try {
    // Obtener datos del usuario actual con información de rol
    $id_usuario = $_SESSION['id_usuario'];

    $sql = "SELECT u.*, r.rol 
            FROM tb_usuarios u 
            INNER JOIN tb_roles r ON u.id_rol = r.id_rol 
            WHERE u.id_usuario = :id_usuario";
    
    $query = $pdo->prepare($sql);
    $query->bindParam(':id_usuario', $id_usuario, PDO::PARAM_INT);
    $query->execute();

    if ($query->rowCount() > 0) {
        $usuario = $query->fetch(PDO::FETCH_ASSOC);
        
        // Asignar variables para usar en la vista
        $nombres = htmlspecialchars($usuario['nombres']);
        $email = htmlspecialchars($usuario['email']);
        $imagen_perfil = $usuario['imagen_perfil'];
        $rol = htmlspecialchars($usuario['rol']);
        $fyh_creacion = $usuario['fyh_creacion'];
        $fyh_actualizacion = $usuario['fyh_actualizacion'];
    } else {
        // Redireccionar si no se encuentra el usuario
        $_SESSION['mensaje'] = "Error: No se encontraron datos del usuario";
        $_SESSION['icono'] = "error";
        header('Location: ' . BASE_URL . '/login');
        exit();
    }
} catch (PDOException $e) {
    // Registrar error en el log
    error_log("Error en datos_perfil.php: " . $e->getMessage());
    
    // Redireccionar con mensaje de error
    $_SESSION['mensaje'] = "Error al cargar datos del perfil";
    $_SESSION['icono'] = "error";
    header('Location: ' . BASE_URL . '/login');
    exit();
}