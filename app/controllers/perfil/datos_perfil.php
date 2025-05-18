<?php
// Este archivo es incluido desde perfil/index.php

// Obtener datos del usuario actual
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
    $nombres = $usuario['nombres'];
    $email = $usuario['email'];
    $imagen_perfil = $usuario['imagen_perfil'];
    $rol = $usuario['rol'];
    $fyh_creacion = $usuario['fyh_creacion'];
    $fyh_actualizacion = $usuario['fyh_actualizacion'];
} else {
    // Redireccionar si no se encuentra el usuario
    $_SESSION['mensaje'] = "Error: No se encontraron datos del usuario.";
    $_SESSION['icono'] = "error";
    header('Location: ' . $URL . '/login');
    exit();
}