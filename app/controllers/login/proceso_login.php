<?php
require_once '../../config.php';
require_once __DIR__ . '/LoginController.php';
require_once __DIR__ . '/../../utils/Security.php';

// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Crear instancia de seguridad
$security = new Security();

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Registrar intento de acceso no permitido
    $security->logSuspiciousActivity('invalid_request', 'Intento de acceso al proceso de login mediante ' . $_SERVER['REQUEST_METHOD']);
    
    // Redirigir al login
    $_SESSION['mensaje'] = "Método de acceso no permitido";
    $_SESSION['icono'] = "error";
    header('Location: ' . $URL . '/login');
    exit();
}

// Verificar token CSRF
if (!isset($_POST['csrf_token']) || !$security->verifyCSRFToken($_POST['csrf_token'])) {
    // Registrar intento de CSRF
    $security->logSuspiciousActivity('csrf_attempt', 'Intento CSRF en proceso de login');
    
    // Redirigir al login con mensaje de error
    $_SESSION['mensaje'] = "Error de seguridad: solicitud inválida";
    $_SESSION['icono'] = "error";
    header('Location: ' . $URL . '/login');
    exit();
}

try {
    // Sanitizar y obtener datos del formulario
    $email = isset($_POST['email']) ? $security->sanitizeInput($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : ''; // No sanitizamos el password
    
    // Validar datos básicos del formulario en el servidor
    $errors = [];
    
    if (empty($email)) {
        $errors[] = "El email es obligatorio";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "El formato del email no es válido";
    }
    
    if (empty($password)) {
        $errors[] = "La contraseña es obligatoria";
    }
    
    // Si hay errores, redirigir con mensaje
    if (!empty($errors)) {
        $_SESSION['mensaje'] = $errors[0];
        $_SESSION['icono'] = "error";
        header('Location: ' . $URL . '/login');
        exit();
    }
    
    // Comprobar si se ha establecido un límite de intentos de inicio de sesión
    $rate_limit_file = __DIR__ . '/../../../logs/auth/rate_limit_' . md5($email) . '.json';
    if (file_exists($rate_limit_file)) {
        $rate_limit = json_decode(file_get_contents($rate_limit_file), true);
        $current_time = time();
        
        // Si ha excedido el límite y aún está dentro del tiempo de bloqueo
        if ($rate_limit['count'] >= 5 && ($current_time - $rate_limit['last_attempt']) < 1800) {
            $time_left = 1800 - ($current_time - $rate_limit['last_attempt']);
            $minutes = floor($time_left / 60);
            $seconds = $time_left % 60;
            
            $_SESSION['mensaje'] = "Demasiados intentos fallidos. Por favor, espere {$minutes} minutos y {$seconds} segundos antes de intentarlo de nuevo.";
            $_SESSION['icono'] = "error";
            header('Location: ' . $URL . '/login');
            exit();
        }
        
        // Reiniciar el contador si ha pasado el tiempo de bloqueo
        if (($current_time - $rate_limit['last_attempt']) >= 1800) {
            unlink($rate_limit_file);
        }
    }
    
    // Instanciar controlador de login
    $loginController = new LoginController($pdo);
    
    // Autenticar usuario
    $result = $loginController->authenticate($email, $password);
    
    // Procesar resultado de la autenticación
    if ($result['status'] === 'success') {
        // Si hay un archivo de límite de intentos, eliminarlo en caso de éxito
        if (file_exists($rate_limit_file)) {
            unlink($rate_limit_file);
        }
        
        // Establecer cookie "remember me" si se ha seleccionado
        if (isset($_POST['remember']) && $_POST['remember'] == 'on') {
            $token = bin2hex(random_bytes(16));
            $hash = password_hash($token, PASSWORD_DEFAULT);
            
            // Almacenamos el hash en la base de datos (omitido en este ejemplo)
            // $user_id = $_SESSION['id_usuario'];
            // almacenarTokenEnBaseDeDatos($user_id, $hash);
            
            // Establecemos la cookie con el token, válida por 30 días
            setcookie('remember_token', $token, time() + (86400 * 30), '/', '', true, true);
        }
        
        // Opcional: Registrar acceso exitoso
        error_log(date('Y-m-d H:i:s') . " - Login exitoso: {$email} desde {$_SERVER['REMOTE_ADDR']}");
        
        // Redirigir a la página principal
        if (isset($_SESSION['redirect_after_login']) && !empty($_SESSION['redirect_after_login'])) {
            $redirect = $_SESSION['redirect_after_login'];
            unset($_SESSION['redirect_after_login']);
            header('Location: ' . $redirect);
        } else {
            header('Location: ' . $URL . '/index.php');
        }
    } else {
        // Incrementar contador de intentos fallidos
        if (file_exists($rate_limit_file)) {
            $rate_limit = json_decode(file_get_contents($rate_limit_file), true);
            $rate_limit['count']++;
            $rate_limit['last_attempt'] = time();
        } else {
            $rate_limit = [
                'email' => $email,
                'count' => 1,
                'first_attempt' => time(),
                'last_attempt' => time()
            ];
        }
        file_put_contents($rate_limit_file, json_encode($rate_limit));
        
        // Opcional: Registrar intento fallido
        error_log(date('Y-m-d H:i:s') . " - Intento fallido para {$email} desde {$_SERVER['REMOTE_ADDR']}. Intento #" . $rate_limit['count']);
        
        // Mensaje específico si la cuenta está bloqueada
        if (isset($result['account_locked']) && $result['account_locked']) {
            $_SESSION['mensaje'] = $result['message'];
            $_SESSION['icono'] = "error";
        } else {
            // Mensaje para no revelar información de seguridad
            $_SESSION['mensaje'] = "Credenciales incorrectas. Por favor, verifique su email y contraseña.";
            $_SESSION['icono'] = "error";
        }
        
        // Redirigir al login
        header('Location: ' . $URL . '/login');
    }
} catch (Exception $e) {
    // Registrar el error
    error_log("Error en proceso_login.php: " . $e->getMessage());
    
    // Redirigir con mensaje de error genérico
    $_SESSION['mensaje'] = "Ocurrió un error al procesar su solicitud. Por favor, inténtelo nuevamente.";
    $_SESSION['icono'] = "error";
    header('Location: ' . $URL . '/login');
}
exit();