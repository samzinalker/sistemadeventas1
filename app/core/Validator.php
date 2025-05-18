<?php
/**
 * Clase base para todos los controladores
 */
abstract class Controller {
    protected $model;
    protected $url;
    
    /**
     * Constructor
     */
    public function __construct() {
        global $URL;
        $this->url = $URL;
        
        // Asegurarse de que el directorio de logs existe
        if (!file_exists(__DIR__ . '/../logs')) {
            mkdir(__DIR__ . '/../logs', 0755, true);
        }
    }
    
    /**
     * Verifica si la sesión está activa
     */
    protected function checkSession() {
        if (!isset($_SESSION['id_usuario'])) {
            if (!headers_sent()) {
                header('Location: ' . $this->url . '/login');
                exit;
            } else {
                echo "<script>window.location.href = '{$this->url}/login';</script>";
                exit;
            }
        }
    }
    
    /**
     * Verifica si el usuario tiene el rol requerido
     * @param array $allowedRoles Roles permitidos
     */
    protected function checkRole($allowedRoles) {
        if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], $allowedRoles)) {
            if (!headers_sent()) {
                header('Location: ' . $this->url . '/error/forbidden.php');
                exit;
            } else {
                echo "<script>window.location.href = '{$this->url}/error/forbidden.php';</script>";
                exit;
            }
        }
    }
    
    /**
     * Obtiene el ID del usuario en sesión
     * @return int|null ID del usuario o null si no hay sesión
     */
    protected function getUserId() {
        return $_SESSION['id_usuario'] ?? null;
    }
    
    /**
     * Establece un mensaje de sesión
     * @param string $message Mensaje
     * @param string $type Tipo (success, error, warning, info)
     */
    protected function setMessage($message, $type = 'success') {
        $_SESSION['mensaje'] = $message;
        $_SESSION['icono'] = $type;
    }
    
    /**
     * Redirecciona a una URL
     * @param string $path Ruta a redireccionar
     */
    protected function redirect($path) {
        if (!headers_sent()) {
            header('Location: ' . $this->url . $path);
            exit;
        } else {
            echo "<script>window.location.href = '{$this->url}{$path}';</script>";
            exit;
        }
    }
    
    /**
     * Responde con JSON (para AJAX)
     * @param array $data Datos a convertir a JSON
     */
    protected function jsonResponse($data) {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
    
    /**
     * Registra un error
     * @param string $message Mensaje de error
     */
    protected function logError($message) {
        $logFile = __DIR__ . '/../logs/' . strtolower(get_class($this)) . '.log';
        $date = date('Y-m-d H:i:s');
        $log = "[$date] ERROR: $message" . PHP_EOL;
        error_log($log, 3, $logFile);
    }
}