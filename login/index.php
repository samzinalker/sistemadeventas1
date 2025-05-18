<?php
require_once '../app/config.php';
require_once '../app/utils/Security.php';

// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Redirigir si ya hay sesión activa
if (isset($_SESSION['sesion_email'])) {
    header('Location: ' . $URL . '/index.php');
    exit();
}

// Crear instancia de seguridad
$security = new Security();
$csrf_token = $security->generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sistema de Ventas | Login</title>
    
    <!-- Meta tags de seguridad -->
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net; font-src 'self' https://fonts.gstatic.com; img-src 'self' data:;">
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-Frame-Options" content="DENY">
    <meta http-equiv="X-XSS-Protection" content="1; mode=block">

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="<?php echo $URL; ?>/public/templeates/AdminLTE-3.2.0/plugins/fontawesome-free/css/all.min.css">
    <!-- icheck bootstrap -->
    <link rel="stylesheet" href="<?php echo $URL; ?>/public/templeates/AdminLTE-3.2.0/plugins/icheck-bootstrap/icheck-bootstrap.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="<?php echo $URL; ?>/public/templeates/AdminLTE-3.2.0/dist/css/adminlte.min.css">
    
    <!-- SweetAlert2 -->
    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Custom CSS -->
    <style>
        .login-box {
            max-width: 400px;
            width: 90%;
        }
        .login-logo img {
            max-width: 200px;
            height: auto;
        }
        .error-feedback {
            color: #dc3545;
            font-size: 80%;
            margin-top: 0.25rem;
        }
        .password-toggle-icon {
            cursor: pointer;
            position: absolute;
            right: 10px;
            top: 10px;
            z-index: 10;
        }
        .input-group-append .fas {
            width: 14px;
        }
    </style>
</head>
<body class="hold-transition login-page">
    <div class="login-box">
        <!-- Logo -->
        <div class="login-logo">
            <img src="<?php echo $URL; ?>/public/images/logo.png" alt="Logo Sistema de Ventas" class="mb-2">
            <a href="<?php echo $URL; ?>"><b>Sistema</b> de Ventas</a>
        </div>
        
        <!-- /.login-logo -->
        <div class="card card-outline card-primary">
            <div class="card-header text-center">
                <h1 class="h1">Iniciar Sesión</h1>
            </div>
            <div class="card-body">
                <p class="login-box-msg">Ingrese sus credenciales para acceder al sistema</p>

                <form id="loginForm" action="../app/controllers/login/proceso_login.php" method="post" autocomplete="off">
                    <!-- Token CSRF -->
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <!-- Campo Email -->
                    <div class="form-group">
                        <label for="email">Email</label>
                        <div class="input-group mb-3">
                            <input type="email" class="form-control" id="email" name="email" placeholder="correo@ejemplo.com" required>
                            <div class="input-group-append">
                                <div class="input-group-text">
                                    <span class="fas fa-envelope"></span>
                                </div>
                            </div>
                        </div>
                        <div class="error-feedback" id="email-error"></div>
                    </div>
                    
                    <!-- Campo Contraseña -->
                    <div class="form-group">
                        <label for="password">Contraseña</label>
                        <div class="input-group mb-3">
                            <input type="password" class="form-control" id="password" name="password" placeholder="************" required>
                            <div class="input-group-append">
                                <div class="input-group-text">
                                    <span class="fas fa-lock"></span>
                                </div>
                            </div>
                            <span class="password-toggle-icon" onclick="togglePasswordVisibility()">
                                <i class="fas fa-eye-slash" id="togglePasswordIcon"></i>
                            </span>
                        </div>
                        <div class="error-feedback" id="password-error"></div>
                    </div>
                    
                    <!-- Recordarme -->
                    <div class="row">
                        <div class="col-8">
                            <div class="icheck-primary">
                                <input type="checkbox" id="remember" name="remember">
                                <label for="remember">
                                    Recordarme
                                </label>
                            </div>
                        </div>
                        <div class="col-4">
                            <button type="submit" id="loginButton" class="btn btn-primary btn-block">
                                <i class="fas fa-sign-in-alt mr-1"></i> Ingresar
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Links adicionales -->
                <p class="mb-1 mt-3">
                    <a href="forgot-password.php">Olvidé mi contraseña</a>
                </p>
                
                <!-- Mensaje de contacto al administrador -->
                <div class="text-center mt-3">
                    <small class="text-muted">
                        Si tiene problemas para acceder, contacte al administrador del sistema.
                    </small>
                </div>
            </div>
            <!-- /.card-body -->
        </div>
        <!-- /.card -->
        
        <!-- Versión del sistema -->
        <div class="text-center mt-3">
            <span class="text-muted">Sistema de Ventas v1.0</span>
        </div>
    </div>
    <!-- /.login-box -->

    <!-- jQuery -->
    <script src="<?php echo $URL; ?>/public/templeates/AdminLTE-3.2.0/plugins/jquery/jquery.min.js"></script>
    <!-- Bootstrap 4 -->
    <script src="<?php echo $URL; ?>/public/templeates/AdminLTE-3.2.0/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <!-- AdminLTE App -->
    <script src="<?php echo $URL; ?>/public/templeates/AdminLTE-3.2.0/dist/js/adminlte.min.js"></script>

    <!-- Mensajes de sesión -->
    <?php include('../layout/mensajes.php'); ?>
    
    <!-- Scripts personalizados -->
    <script>
        // Función para alternar visibilidad de contraseña
        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('togglePasswordIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            }
        }
        
        // Validación de formulario
        $(document).ready(function() {
            $('#loginForm').submit(function(event) {
                // Limpiar mensajes de error anteriores
                $('.error-feedback').text('');
                
                let hasErrors = false;
                const email = $('#email').val().trim();
                const password = $('#password').val();
                
                // Validar email
                if (!email) {
                    $('#email-error').text('El email es obligatorio');
                    hasErrors = true;
                } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                    $('#email-error').text('Por favor ingrese un email válido');
                    hasErrors = true;
                }
                
                // Validar contraseña
                if (!password) {
                    $('#password-error').text('La contraseña es obligatoria');
                    hasErrors = true;
                }
                
                // Prevenir envío del formulario si hay errores
                if (hasErrors) {
                    event.preventDefault();
                } else {
                    // Deshabilitar botón para prevenir múltiples envíos
                    $('#loginButton').prop('disabled', true).html(
                        '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Procesando...'
                    );
                }
            });
        });
        
        // Prevenir reenvío de formulario al recargar la página
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>
</html>