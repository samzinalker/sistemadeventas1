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
        if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'administrador') {
            return true;
        }
        
        // El usuario solo puede editar su propio perfil
        return $_SESSION['id_usuario'] == $userId;
    }
    
    /**
     * Obtiene los datos del perfil del usuario
     * @param int $userId ID del usuario
     * @return array Datos del usuario para mostrar en perfil
     */
    public function getUserProfile($userId) {
        try {
            $sql = "SELECT u.*, r.rol 
                    FROM tb_usuarios u 
                    INNER JOIN tb_roles r ON u.id_rol = r.id_rol 
                    WHERE u.id_usuario = :id_usuario";
            
            $query = $this->usuarioModel->getConnection()->prepare($sql);
            $query->bindParam(':id_usuario', $userId, PDO::PARAM_INT);
            $query->execute();
            
            if ($query->rowCount() > 0) {
                return $query->fetch(PDO::FETCH_ASSOC);
            }
            return null;
        } catch (Exception $e) {
            error_log("Error en PerfilController::getUserProfile: " . $e->getMessage());
            return null;
        }
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