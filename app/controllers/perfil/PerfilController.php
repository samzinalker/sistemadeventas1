<?php
require_once APP_PATH . '/models/UsuarioModel.php';
require_once APP_PATH . '/utils/FileUpload.php';
require_once APP_PATH . '/utils/Security.php';


/**
 * Controlador para el módulo de perfil de usuario
 */
class PerfilController {
    private $usuarioModel;
    private $fileUpload;
    private $security;
    private $pdo;
    
    /**
     * Constructor
     * @param PDO|null $pdo Conexión PDO opcional
     */
    public function __construct($pdo = null) {
        $this->pdo = $pdo;
        $this->usuarioModel = new UsuarioModel($pdo);
        $this->fileUpload = new FileUpload();
        $this->security = new Security();
    }
    
    /**
     * Obtiene los datos del perfil del usuario para mostrar en la vista
     * @param int $userId ID del usuario 
     * @return array Datos del usuario o array vacío si hay error
     */
    public function getDatosPerfilUsuario($userId) {
        try {
            // Verificar que el usuario está autenticado
            if (!$userId) {
                throw new Exception("Usuario no autenticado");
            }
            
            // Verificar permisos
            if (!$this->checkProfileOwnership($userId)) {
                $this->security->logSuspiciousActivity(
                    'unauthorized_profile_access', 
                    "Usuario {$_SESSION['id_usuario']} intentó acceder al perfil de usuario $userId"
                );
                throw new Exception("No tiene permisos para acceder a este perfil");
            }
            
            // Obtener datos del usuario
            $sql = "SELECT u.*, r.rol 
                    FROM tb_usuarios u 
                    INNER JOIN tb_roles r ON u.id_rol = r.id_rol 
                    WHERE u.id_usuario = :id_usuario";
            
            $query = $this->pdo->prepare($sql);
            $query->bindParam(':id_usuario', $userId, PDO::PARAM_INT);
            $query->execute();

            if ($query->rowCount() > 0) {
                $usuario = $query->fetch(PDO::FETCH_ASSOC);
                
                // Asignar y sanitizar datos
                return [
                    'nombres' => htmlspecialchars($usuario['nombres']),
                    'email' => htmlspecialchars($usuario['email']),
                    'imagen_perfil' => $usuario['imagen_perfil'],
                    'rol' => htmlspecialchars($usuario['rol']),
                    'fyh_creacion' => $usuario['fyh_creacion'],
                    'fyh_actualizacion' => $usuario['fyh_actualizacion'],
                    'csrf_token' => $this->security->generateCSRFToken()
                ];
            } else {
                throw new Exception("Usuario no encontrado");
            }
        } catch (Exception $e) {
            // Registrar error en el log
            error_log("Error en PerfilController::getDatosPerfilUsuario: " . $e->getMessage());
            return [];
        }
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
        private function logActivity($activity, $userId, $description) {
            $logDir = BASE_PATH . '/logs';
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
    }
   