<?php
include('../../config.php');

// Obtener los datos del formulario
$email = $_POST['email'];
$password_user = $_POST['password_user'];

try {
    // Preparar consulta parametrizada
    $sql = "SELECT * FROM tb_usuarios WHERE email = :email";
    $query = $pdo->prepare($sql);
    $query->bindParam(':email', $email, PDO::PARAM_STR);
    $query->execute();

    // Verificar si el usuario existe
    $contador = 0;
    $usuarios = $query->fetchAll(PDO::FETCH_ASSOC);
    foreach ($usuarios as $usuario) {
        $contador++;
        $email_tabla = $usuario['email'];
        $password_user_tabla = $usuario['password_user'];
    }

   // Verificar si el usuario existe
if ($contador > 0 && password_verify($password_user, $password_user_tabla)) {
    session_start();
    $_SESSION['id_usuario'] = $usuario['id_usuario'];
    $_SESSION['sesion_email'] = $usuario['email'];
    $_SESSION['rol'] = $usuario['rol'];
    $_SESSION['nombres'] = $usuario['nombres'];

    header('Location: ' . $URL . '/index.php');
    exit();
}
} catch (Exception $e) {
    // Manejo de errores en caso de fallo en la base de datos
    session_start();
    $_SESSION['mensaje'] = "Ocurrió un error al procesar su solicitud. Por favor, inténtelo más tarde.";
    $_SESSION['icono'] = "error";
    header('Location: '.$URL.'/login');
}
?>