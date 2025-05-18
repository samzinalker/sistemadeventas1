<?php

// Iniciar sesión solo si no hay una activa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['sesion_email'])) {
    $email_sesion = $_SESSION['sesion_email'];

    try {
        // Asegúrate de que $pdo esté disponible aquí. 
        // Si layout/sesion.php se incluye ANTES de config.php, $pdo no existirá.
        // Lo ideal es que config.php se incluya antes que cualquier archivo que necesite $pdo.
        // Si $pdo no está globalmente disponible, necesitarías pasarlo o incluir config.php aquí DENTRO del if.
        // Por ahora, asumiré que $pdo está disponible si config.php se incluye antes en el script principal.
        
        // Si $pdo no está disponible, deberías incluir config.php aquí:
        // if (!isset($pdo)) {
        //     require_once __DIR__ . '/../app/config.php'; // Ajusta la ruta si es necesario
        // }

        $sql = "SELECT us.id_usuario as id_usuario, us.nombres as nombres, us.email as email, rol.rol as rol 
                FROM tb_usuarios as us 
                INNER JOIN tb_roles as rol ON us.id_rol = rol.id_rol 
                WHERE us.email = :email";
        $query = $pdo->prepare($sql); // $pdo debe estar definido
        $query->bindParam(':email', $email_sesion, PDO::PARAM_STR);
        $query->execute();

        if ($query->rowCount() > 0) {
            $usuario = $query->fetch(PDO::FETCH_ASSOC);

            $_SESSION['id_usuario'] = $usuario['id_usuario'];
            $_SESSION['nombres'] = $usuario['nombres'];
            $_SESSION['rol'] = $usuario['rol'];

            // Esta parte parece redundante o un posible error lógico:
            // if (isset($_SESSION['id_usuario'])) {
            //     $id_usuario_sesion = $_SESSION['id_usuario'];
            // } else {
            //     // Esto nunca debería ocurrir si acabas de setear $_SESSION['id_usuario']
            //     // y el usuario fue encontrado.
            //     session_start(); // Esto es definitivamente un problema aquí si la sesión ya está activa
            //     $_SESSION['mensaje'] = "Error: No se ha iniciado sesión correctamente.";
            //     $_SESSION['icono'] = "error";
            //     // header('Location: ../login.php'); // Cuidado con las rutas relativas aquí
            //     // Deberías usar la variable $URL para construir la ruta completa.
            //     // Ejemplo: header('Location: ' . $URL . '/login.php');
            //     exit();
            // }
            // Lo simplificamos a:
            $id_usuario_sesion = $_SESSION['id_usuario'];


            $rol_sesion = $_SESSION['rol'];
            $nombres_sesion = $_SESSION['nombres'];
        } else {
            // Si no se encuentra el usuario, destruir la sesión y redirigir al login.
            unset($_SESSION['sesion_email']);
            unset($_SESSION['id_usuario']);
            unset($_SESSION['nombres']);
            unset($_SESSION['rol']);
            // session_destroy(); // Considera destruir la sesión completamente

            // Asegúrate de que $URL esté disponible para la redirección
            // if (!isset($URL)) {
            //    require_once __DIR__ . '/../app/config.php';
            // }
            $_SESSION['mensaje'] = "Tu sesión ha expirado o el usuario no es válido. Por favor, inicia sesión de nuevo.";
            $_SESSION['icono'] = "warning";
            header('Location: ' . $URL . '/login.php'); // Usar $URL
            exit();
        }
    } catch (PDOException $e) {
        // Manejo de errores en la consulta
        // En un entorno de producción, no muestres $e->getMessage() directamente al usuario.
        // Loguea el error y muestra un mensaje genérico.
        // error_log("Error en layout/sesion.php: " . $e->getMessage());

        // Destruir sesión para evitar bucles o estados inconsistentes
        unset($_SESSION['sesion_email']);
        // session_destroy();
        $_SESSION['mensaje'] = "Error de base de datos al verificar la sesión. Intenta de nuevo.";
        $_SESSION['icono'] = "error";
        // header('Location: ' . $URL . '/login.php'); // Usar $URL
        exit();
    }
} else {
    // Si no existe $_SESSION['sesion_email'], redirigir al login.
    // Esto es crucial para proteger las páginas.
    // Asegúrate de que $URL esté disponible
    // if (!isset($URL)) {
    //    require_once __DIR__ . '/../app/config.php';
    // }
    // No pongas mensajes de sesión aquí si vas a redirigir,
    // porque la redirección ocurre antes de que `mensajes.php` pueda mostrarlos.
    // El login puede tener su propio mensaje si es necesario.
    header('Location: ' . $URL . '/login.php'); // Usar $URL
    exit();
}
?>