<?php
/**
 * Clase para funciones de seguridad
 */
class Security {
    /**
     * Sanitiza datos de entrada para prevenir XSS e inyección SQL
     * @param string|array $input Datos a sanitizar
     * @return string|array Datos sanitizados
     */
    public function sanitizeInput($input) {
        if (is_array($input)) {
            foreach ($input as $key => $value) {
                $input[$key] = $this->sanitizeInput($value);
            }
            return $input;
        }
        
        // Eliminar espacios en blanco al inicio y final
        $input = trim($input);
        
        // Eliminar barras invertidas
        if (get_magic_quotes_gpc()) {
            $input = stripslashes($input);
        }
        
        // Convertir caracteres especiales en entidades HTML
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        
        return $input;
    }
    
    /**
     * Sanitiza datos de salida para prevenir XSS
     * @param string|array $output Datos a sanitizar
     * @return string|array Datos sanitizados
     */
    public function sanitizeOutput($output) {
        if (is_array($output)) {
            foreach ($output as $key => $value) {
                if (!is_numeric($value) && !is_bool($value) && $key != 'password_user') {
                    $output[$key] = $this->sanitizeOutput($value);
                }
            }
            return $output;
        }
        
        if (!is_numeric($output) && !is_bool($output)) {
            return htmlspecialchars($output, ENT_QUOTES, 'UTF-8');
        }
        
        return $output;
    }
    
    /**
     * Sanitiza nombre de archivo
     * @param string $filename Nombre de archivo a sanitizar
     * @return string Nombre de archivo sanitizado
     */
    public function sanitizeFilename($filename) {
        // Eliminar caracteres especiales y espacios
        $filename = preg_replace('/[^a-zA-Z0-9_.-]/', '', $filename);
        
        // Asegurarse de que no haya ataques de directorio traversal
        $filename = str_replace('..', '', $filename);
        
        return $filename;
    }
    
    /**
     * Genera un token CSRF
     * @return string Token CSRF
     */
    public function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Verifica un token CSRF
     * @param string $token Token a verificar
     * @return bool True si el token es válido
     */
    public function verifyCSRFToken($token) {
        if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
            return false;
        }
        
        // Regenerar token para evitar reutilización
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        
        return true;
    }
    
    /**
     * Verifica si una contraseña es suficientemente fuerte
     * @param string $password Contraseña a verificar
     * @return bool True si la contraseña es fuerte
     */
    public function isStrongPassword($password) {
        // Debe tener al menos 8 caracteres
        if (strlen($password) < 8) {
            return false;
        }
        
        // Debe contener al menos una letra minúscula
        if (!preg_match('/[a-z]/', $password)) {
            return false;
        }
        
        // Debe contener al menos una letra mayúscula
        if (!preg_match('/[A-Z]/', $password)) {
            return false;
        }
        
        // Debe contener al menos un número
        if (!preg_match('/[0-9]/', $password)) {
            return false;
        }
        
        // Debe contener al menos un carácter especial
        if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Genera un hash seguro para tokens
     * @param string $data Datos para generar el hash
     * @return string Hash generado
     */
    public function generateSecureHash($data) {
        return hash_hmac('sha256', $data, $this->getSecretKey());
    }
    
    /**
     * Genera una clave secreta para la aplicación
     * @return string Clave secreta
     */
    private function getSecretKey() {
        // En producción, esta clave debería estar en un archivo de configuración seguro
        // o en variables de entorno, no hardcodeada
        return 'f2a1db3e4b5c6d7e8f9a0b1c2d3e4f5a6b7c8d9e0f1a2b3c4d5e6f7a8b9c0d1e2f';
    }
    
    /**
     * Registra un intento de actividad sospechosa
     * @param string $activity Tipo de actividad
     * @param string $details Detalles
     * @return void
     */
    public function logSuspiciousActivity($activity, $details) {
        $logDir = __DIR__ . '/../../logs';
        if (!file_exists($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $logFile = $logDir . '/security.log';
        $date = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $userId = $_SESSION['id_usuario'] ?? 0;
        
        $logMessage = "[$date] ALERT: $activity, User ID: $userId, IP: $ip, Details: $details, User-Agent: $userAgent\n";
        error_log($logMessage, 3, $logFile);
    }
}