<?php
// Configuración de base de datos
if (!defined('SERVIDOR')) define('SERVIDOR', 'localhost');
if (!defined('USUARIO')) define('USUARIO', 'root');
if (!defined('PASSWORD')) define('PASSWORD', '');
if (!defined('BD')) define('BD', 'sistemadeventas');

// URL base del sistema
$URL = "http://localhost/sistemadeventas";

// Configuración de zona horaria
date_default_timezone_set('America/Guayaquil');
$fechaHora = date('Y-m-d H:i:s');

// Conexión a la base de datos usando PDO
try {
    $servidor = "mysql:dbname=".BD.";host=".SERVIDOR;
    $opciones = [
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ];
    
    $pdo = new PDO($servidor, USUARIO, PASSWORD, $opciones);
} catch(PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// Cargar archivos comunes
require_once __DIR__ . '/utils/Auth.php';
require_once __DIR__ . '/utils/Validator.php';

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}