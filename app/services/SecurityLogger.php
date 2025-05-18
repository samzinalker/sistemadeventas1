<?php
/**
 * Clase para registro de actividades y errores de seguridad
 */
class SecurityLogger {
    private $pdo;
    
    /**
     * Constructor
     */
    public function __construct($pdo = null) {
        $this->pdo = $pdo;
    }
    
    /**
     * Registra actividad del usuario en el log
     */
    public function logActivity($activity, $userId, $description) {
        $logDir = $this->ensureLogDirectory();
        
        $logFile = $logDir . '/user_activity.log';
        $date = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        $logMessage = "[$date] Activity: $activity, User ID: $userId, IP: $ip, Description: $description\n";
        error_log($logMessage, 3, $logFile);
    }
    
    /**
     * Registra errores en el log
     */
    public function logError($message) {
        $logDir = $this->ensureLogDirectory();
        $logFile = $logDir . '/errors.log';
        $date = date('Y-m-d H:i:s');
        $userId = $_SESSION['id_usuario'] ?? 0;
        
        $logMessage = "[$date] ERROR: $message, User ID: $userId\n";
        error_log($logMessage, 3, $logFile);
    }
    
    /**
     * Registra actividades sospechosas en log de seguridad
     */
    public function logSuspiciousActivity($activity, $details) {
        $logDir = $this->ensureLogDirectory();
        $logFile = $logDir . '/security.log';
        $date = date('Y-m-d H:i:s');
        $userId = $_SESSION['id_usuario'] ?? 0;
        
        $logMessage = "[$date] ALERT: $activity, User ID: $userId, Details: $details\n";
        error_log($logMessage, 3, $logFile);
    }
    
    /**
     * Asegura que el directorio de logs exista
     */
    private function ensureLogDirectory() {
        $logDir = __DIR__ . '/../../logs';
        if (!file_exists($logDir)) {
            mkdir($logDir, 0755, true);
        }
        return $logDir;
    }
}