<?php

session_start();

if (isset($_SESSION['sesion_email'])) {
    $email_sesion = $_SESSION['sesion_email'];

    try {
        $sql = "SELECT us.id_usuario as id_usuario, us.nombres as nombres, us.email as email, rol.rol as rol 
                FROM tb_usuarios as us 
                INNER JOIN tb_roles as rol ON us.id_rol = rol.id_rol 
                WHERE us.email = :email";
        $query = $pdo->prepare($sql);
        $query->bindParam(':email', $email_sesion, PDO::PARAM_STR);
        $query->execute();

        // Validar que la consulta devuelva al menos un resultado
        if ($query->rowCount() > 0) {
            $usuario = $query->fetch(PDO::FETCH_ASSOC);

            // Asignar datos de sesión
            $_SESSION['id_usuario'] = $usuario['id_usuario'];
            $_SESSION['nombres'] = $usuario['nombres'];
            $_SESSION['rol'] = $usuario['rol'];
        } else {
            // Manejo cuando no se encuentra el usuario
            $_SESSION['error'] = "No se encontró un usuario con el email proporcionado.";
            header('Location: ' . $URL . '/login');
            exit();
        }
    } catch (PDOException $e) {
        // Manejo de errores en la consulta
        $_SESSION['error'] = "Error al conectar con la base de datos: " . $e->getMessage();
        header('Location: ' . $URL . '/login');
        exit();
    }
} else {
    // Redirigir si no hay sesión iniciada
    header('Location: ' . $URL . '/login');
    exit();
}

