<?php
// Este archivo es incluido desde el index.php principal

// Consulta para obtener todos los usuarios con sus roles
$sql_usuarios = "SELECT u.*, r.rol 
                FROM tb_usuarios u 
                INNER JOIN tb_roles r ON u.id_rol = r.id_rol 
                ORDER BY u.id_usuario DESC";
$query_usuarios = $pdo->prepare($sql_usuarios);
$query_usuarios->execute();
$usuarios_datos = $query_usuarios->fetchAll(PDO::FETCH_ASSOC);