<?php
// Iniciar sesión solo si no hay una activa
// Esta línea es crucial y debe estar antes de cualquier acceso a $_SESSION
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Asegurarse de que $URL y $pdo estén disponibles.
// Es responsabilidad del script que incluye este (la vista) haber incluido config.php antes.
global $URL, $pdo; 
if (!isset($URL) || !isset($pdo)) {
    // Esto indica un problema grave en el orden de inclusión.
    // En un entorno de producción, se debería loguear este error y mostrar una página de error amigable.
    die("Error crítico: Las variables de configuración $URL o $pdo no están disponibles en layout/sesion.php. Verifique el orden de inclusión.");
}


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

        if ($query->rowCount() > 0) {
            $usuario = $query->fetch(PDO::FETCH_ASSOC);
            $_SESSION['id_usuario'] = $usuario['id_usuario'];
            $_SESSION['nombres'] = $usuario['nombres'];
            $_SESSION['rol'] = $usuario['rol'];
            // Estas variables son para uso local en el script si se necesitan después, $_SESSION ya está actualizado.
            $id_usuario_sesion = $_SESSION['id_usuario'];
            $rol_sesion = $_SESSION['rol'];
            $nombres_sesion = $_SESSION['nombres'];
        } else {
            // Si no se encuentra el usuario, limpiar datos de sesión y redirigir.
            unset($_SESSION['sesion_email'], $_SESSION['id_usuario'], $_SESSION['nombres'], $_SESSION['rol']);
            
            // Establecer mensaje SOLO SI NO HAY OTRO MENSAJE MÁS IMPORTANTE YA ESTABLECIDO
            if (!isset($_SESSION['mensaje'])) {
                $_SESSION['mensaje'] = "Tu sesión ha expirado o el usuario no es válido. Por favor, inicia sesión de nuevo.";
                $_SESSION['icono'] = "warning";
            }
            header('Location: ' . rtrim($URL, '/') . '/login.php');
            exit();
        }
    } catch (PDOException $e) {
        // En un entorno de producción, loguear el error: error_log("Error en layout/sesion.php: " . $e->getMessage());
        unset($_SESSION['sesion_email'], $_SESSION['id_usuario'], $_SESSION['nombres'], $_SESSION['rol']);
        
        // Establecer mensaje SOLO SI NO HAY OTRO MENSAJE MÁS IMPORTANTE
        if (!isset($_SESSION['mensaje'])) {
            $_SESSION['mensaje'] = "Error de base de datos al verificar la sesión. Intenta de nuevo.";
            $_SESSION['icono'] = "error";
        }
        header('Location: ' . rtrim($URL, '/') . '/login.php');
        exit();
    }
} else {
    // Si no existe $_SESSION['sesion_email'], redirigir al login.
    // No se sobreescribe $_SESSION['mensaje'] aquí, para permitir que un mensaje flash de una acción previa (si existe) se muestre en la página de login.
    // Si la página de login necesita mostrar un mensaje específico para este caso, ella misma lo puede gestionar.
    header('Location: ' . rtrim($URL, '/') . '/login.php');
    exit();
}
?>