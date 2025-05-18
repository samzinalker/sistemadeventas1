<?php
require_once __DIR__ . '/../../models/UsuarioModel.php';
require_once __DIR__ . '/../../utils/FileUpload.php';
require_once __DIR__ . '/../../utils/Security.php';

/**
 * Controlador para el módulo de perfil de usuario
 */
class PerfilController {
    private $usuarioModel;
    private $fileUpload;
    private $security;
    
    /**
     * Constructor
     * @param PDO|null $pdo Conexión PDO opcional
     */
    public function __construct($pdo = null) {
        $this->usuarioModel = new UsuarioModel($pdo);
        $this->fileUpload = new FileUpload();
        $this->security = new Security();
    }
    
    /**
     * Verifica que el usuario esté autenticado y sea el propietario del perfil
     * @param int $userId ID del usuario a verificar
     * @return bool True si el usuario tiene permisos
     */
    public function checkProfileOwnership($userId) {
        if (!isset($_SESSION['id_usuario'])) {
            return false;
        }
        
        // Permitir al admin editar cualquier perfil
        if ($_SESSION['rol'] === 'administrador') {
            return true;
        }
        
        // El usuario solo puede editar su propio perfil
        return $_SESSION['id_usuario'] == $userId;
    }
    
    /**
     * Obtener datos del perfil del usuario actual
     * @param int $userId ID del usuario
     * @return array Resultado con estado, mensaje y datos
     */
    public function getUserProfile($userId) {
        if (!$userId) {
            return [
                'status' => 'error',
                'message' => 'ID de usuario no proporcionado',
                'data' => null
            ];
        }
        
        // Verificar permisos
        if (!$this->checkProfileOwnership($userId)) {
            $this->security->logSuspiciousActivity(
                'unauthorized_profile_access', 
                "Usuario {$_SESSION['id_usuario']} intentó acceder al perfil de usuario $userId"
            );
            
            return [
                'status' => 'error',
                'message' => 'No tiene permisos para acceder a este perfil',
                'data' => null
            ];
        }
        
        try {
            $profile = $this->usuarioModel->getByIdWithRole($userId);
            
            if ($profile) {
                // Remover datos sensibles
                unset($profile['password_user']);
                unset($profile['token']);
                
                return [
                    'status' => 'success',
                    'data' => $profile,
                    'csrf_token' => $this->security->generateCSRFToken()
                ];
            } else {
                return [
                    'status' => 'error',
                    'message' => 'Perfil no encontrado',
                    'data' => null
                ];
            }
        } catch (Exception $e) {
            error_log("Error en PerfilController::getUserProfile: " . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Error al obtener datos del perfil',
                'data' => null
            ];
        }
    }
    
    /**
     * Actualizar datos personales
     * @param int $userId ID del usuario
     * @param array $data Datos a actualizar
     * @return array Resultado con status y mensaje
     */
    public function updatePersonalData($userId, $data) {
        // Verificar token CSRF
        if (!isset($data['csrf_token']) || !$this->security->verifyCSRFToken($data['csrf_token'])) {
            $this->security->logSuspiciousActivity('csrf_attempt', 'Intento CSRF en actualización de perfil');
            return [
                'status' => 'error',
                'message' => 'Solicitud inválida o expirada, por favor recargue la página e intente nuevamente'
            ];
        }
        
        // Verificar permisos
        if (!$this->checkProfileOwnership($userId)) {
            $this->security->logSuspiciousActivity(
                'unauthorized_profile_update', 
                "Usuario {$_SESSION['id_usuario']} intentó actualizar el perfil de usuario $userId"
            );
            
            return [
                'status' => 'error',
                'message' => 'No tiene permisos para actualizar este perfil'
            ];
        }
        
        // Sanitizar datos
        $data = $this->security->sanitizeInput($data);
        
        // Validar datos
        if (empty($data['nombres']) || strlen($data['nombres']) < 3) {
            return [
                'status' => 'error',
                'message' => 'El nombre debe tener al menos 3 caracteres'
            ];
        }
        
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return [
                'status' => 'error',
                'message' => 'Debe proporcionar un email válido'
            ];
        }
        
        try {
            // Verificar que el usuario existe
            $usuario = $this->usuarioModel->getById($userId);
            if (!$usuario) {
                return [
                    'status' => 'error',
                    'message' => 'Usuario no encontrado'
                ];
            }
            
            // Verificar que el email no esté en uso por otro usuario
            $usuarioExistente = $this->usuarioModel->findByEmail($data['email']);
            if ($usuarioExistente && $usuarioExistente['id_usuario'] != $userId) {
                return [
                    'status' => 'error',
                    'message' => 'El email ya está en uso por otro usuario'
                ];
            }
            
            // Preparar datos para actualizar
            $updateData = [
                'nombres' => $data['nombres'],
                'email' => $data['email'],
                'fyh_actualizacion' => date('Y-m-d H:i:s')
            ];
            
            // Actualizar datos
            $result = $this->usuarioModel->update($userId, $updateData);
            
            if ($result === true) {
                // Actualizar sesión si corresponde
                if (isset($_SESSION['nombres']) && isset($_SESSION['sesion_email']) && $_SESSION['id_usuario'] == $userId) {
                    $_SESSION['nombres'] = $data['nombres'];
                    $_SESSION['sesion_email'] = $data['email'];
                }
                
                // Registrar actualización
                $this->logActivity('profile_update', $userId, 'Actualización de datos personales');
                
                return [
                    'status' => 'success',
                    'message' => 'Datos personales actualizados correctamente'
                ];
            } else {
                return [
                    'status' => 'error',
                    'message' => is_string($result) ? $result : 'Error al actualizar los datos personales'
                ];
            }
        } catch (Exception $e) {
            error_log("Error en PerfilController::updatePersonalData: " . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Error interno al actualizar los datos personales'
            ];
        }
    }
    
    /**
     * Actualizar contraseña
     * @param int $userId ID del usuario
     * @param array $data Datos con contraseñas
     * @return array Resultado con status y mensaje
     */
    public function updatePassword($userId, $data) {
        // Verificar token CSRF
        if (!isset($data['csrf_token']) || !$this->security->verifyCSRFToken($data['csrf_token'])) {
            $this->security->logSuspiciousActivity('csrf_attempt', 'Intento CSRF en cambio de contraseña');
            return [
                'status' => 'error',
                'message' => 'Solicitud inválida o expirada, por favor recargue la página e intente nuevamente'
            ];
        }
        
        // Verificar permisos
        if (!$this->checkProfileOwnership($userId)) {
            $this->security->logSuspiciousActivity(
                'unauthorized_password_update', 
                "Usuario {$_SESSION['id_usuario']} intentó cambiar contraseña de usuario $userId"
            );
            
            return [
                'status' => 'error',
                'message' => 'No tiene permisos para cambiar la contraseña de este usuario'
            ];
        }
        
        // Validar datos
        if (empty($data['password_actual']) || empty($data['password_nueva']) || empty($data['password_confirmar'])) {
            return [
                'status' => 'error',
                'message' => 'Todos los campos son obligatorios'
            ];
        }
        
        // Verificar que las contraseñas nuevas coincidan
        if ($data['password_nueva'] !== $data['password_confirmar']) {
            return [
                'status' => 'error',
                'message' => 'Las contraseñas nuevas no coinciden'
            ];
        }
        
        // Verificar que la nueva contraseña tenga al menos 8 caracteres
        if (strlen($data['password_nueva']) < 8) {
            return [
                'status' => 'error',
                'message' => 'La contraseña debe tener al menos 8 caracteres'
            ];
        }
        
        // Verificar complejidad de la contraseña
        if (!$this->security->isStrongPassword($data['password_nueva'])) {
            return [
                'status' => 'error',
                'message' => 'La contraseña debe incluir números, letras mayúsculas y minúsculas, y al menos un carácter especial'
            ];
        }
        
        try {
            // Verificar que la contraseña actual es correcta
            if (!$this->usuarioModel->verifyPassword($userId, $data['password_actual'])) {
                // Incrementar contador de intentos fallidos
                $this->incrementPasswordAttempts($userId);
                
                // Verificar si hay demasiados intentos fallidos
                if ($this->tooManyPasswordAttempts($userId)) {
                    $this->security->logSuspiciousActivity(
                        'too_many_password_attempts', 
                        "Demasiados intentos fallidos de cambio de contraseña para usuario $userId"
                    );
                    
                    return [
                        'status' => 'error',
                        'message' => 'Demasiados intentos fallidos. Por seguridad, por favor espere unos minutos e intente nuevamente.',
                        'locked' => true
                    ];
                }
                
                return [
                    'status' => 'error',
                    'message' => 'La contraseña actual es incorrecta'
                ];
            }
            
            // Actualizar contraseña
            $result = $this->usuarioModel->updatePassword($userId, $data['password_nueva']);
            
            if ($result === true) {
                // Resetear contador de intentos fallidos
                $this->resetPasswordAttempts($userId);
                
                // Registrar cambio de contraseña
                $this->logActivity('password_change', $userId, 'Cambio de contraseña');
                
                return [
                    'status' => 'success',
                    'message' => 'Contraseña actualizada correctamente'
                ];
            } else {
                return [
                    'status' => 'error',
                    'message' => is_string($result) ? $result : 'Error al actualizar la contraseña'
                ];
            }
        } catch (Exception $e) {
            error_log("Error en PerfilController::updatePassword: " . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Error interno al actualizar la contraseña'
            ];
        }
    }
    
    /**
     * Actualizar imagen de perfil
     * @param int $userId ID del usuario
     * @param array $file Archivo de imagen ($_FILES['imagen'])
     * @param string $csrfToken Token CSRF
     * @return array Resultado con status y mensaje
     */
    public function updateProfileImage($userId, $file, $csrfToken) {
        // Verificar token CSRF
        if (!$this->security->verifyCSRFToken($csrfToken)) {
            $this->security->logSuspiciousActivity('csrf_attempt', 'Intento CSRF en actualización de imagen de perfil');
            return [
                'status' => 'error',
                'message' => 'Solicitud inválida o expirada, por favor recargue la página e intente nuevamente'
            ];
        }
        
        // Verificar permisos
        if (!$this->checkProfileOwnership($userId)) {
            $this->security->logSuspiciousActivity(
                'unauthorized_image_upload', 
                "Usuario {$_SESSION['id_usuario']} intentó cambiar imagen de perfil de usuario $userId"
            );
            
            return [
                'status' => 'error',
                'message' => 'No tiene permisos para cambiar la imagen de perfil de este usuario'
            ];
        }
        
        // Verificar que se ha subido una imagen
        if (empty($file['name'])) {
            return [
                'status' => 'error',
                'message' => 'No se ha seleccionado ninguna imagen'
            ];
        }
        
        try {
            // Verificar que el usuario existe
            $usuario = $this->usuarioModel->getById($userId);
            if (!$usuario) {
                return [
                    'status' => 'error',
                    'message' => 'Usuario no encontrado'
                ];
            }
            
            // Procesar la imagen
            $resultado = $this->fileUpload->uploadProfileImage($file);
            
            if ($resultado['status'] !== 'success') {
                return [
                    'status' => 'error',
                    'message' => $resultado['message']
                ];
            }
            
            // Guardar imagen anterior para poder eliminarla después
            $imagenAnterior = $usuario['imagen_perfil'];
            
            // Actualizar imagen de perfil
            $result = $this->usuarioModel->updateProfileImage($userId, $resultado['filename']);
            
            if ($result === true) {
                // Eliminar imagen anterior si no es la predeterminada
                if ($imagenAnterior != 'user_default.png') {
                    $rutaImagenAnterior = $_SERVER['DOCUMENT_ROOT'] . '/sistemadeventas/public/images/perfiles/' . $imagenAnterior;
                    if (file_exists($rutaImagenAnterior)) {
                        unlink($rutaImagenAnterior);
                    }
                }
                
                // Registrar actualización de imagen
                $this->logActivity('profile_image_update', $userId, 'Actualización de imagen de perfil');
                
                return [
                    'status' => 'success',
                    'message' => 'Imagen de perfil actualizada correctamente',
                    'filename' => $resultado['filename']
                ];
            } else {
                // Si falla la actualización en la base de datos, eliminar la imagen recién subida
                $rutaImagenNueva = $_SERVER['DOCUMENT_ROOT'] . '/sistemadeventas/public/images/perfiles/' . $resultado['filename'];
                if (file_exists($rutaImagenNueva)) {
                    unlink($rutaImagenNueva);
                }
                
                return [
                    'status' => 'error',
                    'message' => is_string($result) ? $result : 'Error al actualizar la imagen de perfil'
                ];
            }
        } catch (Exception $e) {
            error_log("Error en PerfilController::updateProfileImage: " . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Error interno al actualizar la imagen de perfil'
            ];
        }
    }
    
    /**
     * Incrementa contador de intentos fallidos de contraseña
     * @param int $userId ID del usuario
     * @return void
     */
    private function incrementPasswordAttempts($userId) {
        $attemptsFile = $this->getPasswordAttemptsFilePath($userId);
        
        if (file_exists($attemptsFile)) {
            $attempts = json_decode(file_get_contents($attemptsFile), true);
            $attempts['count']++;
        } else {
            $attempts = [
                'user_id' => $userId,
                'count' => 1,
                'first_attempt' => time(),
                'last_attempt' => time()
            ];
        }
        
        $attempts['last_attempt'] = time();
        file_put_contents($attemptsFile, json_encode($attempts));
    }
    
    /**
     * Verifica si hay demasiados intentos fallidos de cambio de contraseña
     * @param int $userId ID del usuario
     * @return bool True si hay demasiados intentos
     */
    private function tooManyPasswordAttempts($userId) {
        $attemptsFile = $this->getPasswordAttemptsFilePath($userId);
        
        if (!file_exists($attemptsFile)) {
            return false;
        }
        
        $attempts = json_decode(file_get_contents($attemptsFile), true);
        
        // Si han pasado más de 30 minutos desde el último intento, reiniciar contador
        if (time() - $attempts['last_attempt'] > 1800) {
            $this->resetPasswordAttempts($userId);
            return false;
        }
        
        // Bloquear después de 5 intentos fallidos en un periodo de 30 minutos
        return ($attempts['count'] >= 5 && (time() - $attempts['first_attempt'] < 1800));
    }
    
    /**
     * Resetea contador de intentos fallidos de contraseña
     * @param int $userId ID del usuario
     * @return void
     */
    private function resetPasswordAttempts($userId) {
        $attemptsFile = $this->getPasswordAttemptsFilePath($userId);
        
        if (file_exists($attemptsFile)) {
            unlink($attemptsFile);
        }
    }
    
    /**
     * Obtiene ruta del archivo para controlar intentos de cambio de contraseña
     * @param int $userId ID del usuario
     * @return string Ruta del archivo
     */
    private function getPasswordAttemptsFilePath($userId) {
        $dir = __DIR__ . '/../../../logs/auth';
        
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
        
        return $dir . '/pwd_attempts_' . $userId . '.json';
    }
    
    /**
     * Registra actividad del perfil en el log
     * @param string $activity Tipo de actividad
     * @param int $userId ID del usuario
     * @param string $description Descripción de la actividad
     * @return void
     */
    private function logActivity($activity, $userId, $description) {
        $logDir = __DIR__ . '/../../../logs';
        if (!file_exists($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $logFile = $logDir . '/profile_activity.log';
        $date = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $sessionUserId = $_SESSION['id_usuario'] ?? 'no-session';
        
        $logMessage = "[$date] Activity: $activity, User ID: $userId, Session User ID: $sessionUserId, IP: $ip, Description: $description\n";
        error_log($logMessage, 3, $logFile);
    }
}