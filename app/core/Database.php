<?php
/**
 * Clase para gestionar la conexión a la base de datos
 */
class Database {
    private static $instance = null;
    
    /**
     * Obtiene una instancia de la conexión PDO existente
     * @return PDO
     */
    public static function getInstance() {
        if (self::$instance === null) {
            // Usar la conexión global existente
            global $pdo;
            self::$instance = $pdo;
        }
        return self::$instance;
    }
}