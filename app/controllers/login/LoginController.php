<?php
require_once __DIR__ . '/../../models/UsuarioModel.php';

/**
 * Controlador para el módulo de login y autenticación
 */
class LoginController {
    private $usuarioModel;
    
    /**
     * Constructor
     * @param PDO|null $pdo Conexión PDO opcional
     */
    public function __construct($pdo = null) {
        $this->usuarioModel = new UsuarioModel($pdo);
    }
    
    /**
     * Autenticar usuario
     * @param string $email Email/usuario
     * @param string $password Contraseña
     * @return array Resultado con status y mensaje
     */
    public function authenticate($email, $password) {
        // Validar datos
        if (empty($email) || empty($password)) {
            return [
                'status' => 'error',
                'message' => 'Todos los campos son obligatorios'
            ];
        }
        
        // Verificar credenciales
        $user = $this->usuarioModel->validateCredentials($email, $password);
        
        if ($user) {
            // Iniciar sesión
            session_start();
            $_SESSION['sesion_email'] = $user['email'];
            
            return [
                'status' => 'success',
                'message' => 'Inicio de sesión exitoso',
                'user' => $user
            ];
        } else {
            return [
                'status' => 'error',
                'message' => 'Email o contraseña incorrectos'
            ];
        }
    }
    
    /**
     * Cerrar sesión
     * @return array Resultado con status y mensaje
     */
    public function logout() {
        // Iniciar sesión (en caso de que no esté iniciada)
        session_start();
        
        // Destruir todas las variables de sesión
        $_SESSION = [];
        
        // Destruir la cookie de sesión si existe
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Destruir la sesión
        session_destroy();
        
        return [
            'status' => 'success',
            'message' => 'Sesión cerrada correctamente'
        ];
    }
    
    /**
     * Recuperar contraseña (generar token)
     * @param string $email Email del usuario
     * @return array Resultado con status y mensaje
     */
    public function recoverPassword($email) {
        // Verificar si el usuario existe
        $user = $this->usuarioModel->findByEmail($email);
        
        if (!$user) {
            return [
                'status' => 'error',
                'message' => 'No existe un usuario con ese email'
            ];
        }
        
        // Generar token único
        $token = bin2hex(random_bytes(32));
        
        // Actualizar token en la base de datos
        $updated = $this->usuarioModel->update($user['id_usuario'], [
            'token' => $token,
            'fyh_actualizacion' => date('Y-m-d H:i:s')
        ]);
        
        if ($updated === true) {
            // En un sistema real, aquí enviaríamos un email con el link de recuperación
            // Simulamos que se ha enviado correctamente
            
            return [
                'status' => 'success',
                'message' => 'Se ha enviado un correo con instrucciones para reestablecer tu contraseña',
                'debug_token' => $token // Solo para desarrollo
            ];
        } else {
            return [
                'status' => 'error',
                'message' => 'Error al procesar la solicitud'
            ];
        }
    }
    
    /**
     * Validar token de recuperación
     * @param string $token Token a validar
     * @return array|false Datos del usuario o false si no es válido
     */
    public function validateToken($token) {
        if (empty($token)) {
            return false;
        }
        
        try {
            $sql = "SELECT * FROM tb_usuarios WHERE token = :token";
            $stmt = $this->usuarioModel->pdo->prepare($sql);
            $stmt->bindParam(':token', $token, PDO::PARAM_STR);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                return $stmt->fetch(PDO::FETCH_ASSOC);
            }
            
            return false;
        } catch (PDOException $e) {
            // Log del error
            return false;
        }
    }
    
    /**
     * Reestablecer contraseña con token
     * @param string $token Token de verificación
     * @param string $password Nueva contraseña
     * @return array Resultado con status y mensaje
     */
    public function resetPassword($token, $password) {
        // Validar datos
        if (empty($token) || empty($password)) {
            return [
                'status' => 'error',
                'message' => 'Token y contraseña son obligatorios'
            ];
        }
        
        // Verificar que la contraseña tenga al menos 6 caracteres
        if (strlen($password) < 6) {
            return [
                'status' => 'error',
                'message' => 'La contraseña debe tener al menos 6 caracteres'
            ];
        }
        
        // Validar token
        $user = $this->validateToken($token);
        
        if (!$user) {
            return [
                'status' => 'error',
                'message' => 'Token inválido o expirado'
            ];
        }
        
        // Actualizar contraseña
        $result = $this->usuarioModel->updatePassword($user['id_usuario'], $password);
        
        if ($result === true) {
            // Invalidar token
            $this->usuarioModel->update($user['id_usuario'], [
                'token' => '',
                'fyh_actualizacion' => date('Y-m-d H:i:s')
            ]);
            
            return [
                'status' => 'success',
                'message' => 'Contraseña actualizada correctamente'
            ];
        } else {
            return [
                'status' => 'error',
                'message' => 'Error al actualizar la contraseña'
            ];
        }
    }
}