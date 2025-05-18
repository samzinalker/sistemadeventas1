<?php
if (!defined('SERVIDOR')) define('SERVIDOR','localhost');
if (!defined('USUARIO')) define('USUARIO','root');
if (!defined('PASSWORD')) define('PASSWORD','');
if (!defined('BD')) define('BD','sistemadeventas');

$servidor = "mysql:dbname=".BD.";host=".SERVIDOR;

try{
    // Opciones mejoradas para PDO
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
    ];
    
    $pdo = new PDO($servidor, USUARIO, PASSWORD, $options);
}catch(PDOException $e){
    error_log("Error de conexión a la base de datos: " . $e->getMessage());
    echo "Error al conectar a la base de datos";
}

$URL = "http://localhost/sistemadeventas";

date_default_timezone_set('America/Guayaquil');
$fechaHora = date('Y-m-d H:i:s');

// Función para habilitar depuración durante desarrollo
function enableDebugging() {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    
    // Crear directorio de logs si no existe
    $logDir = __DIR__ . '/../logs';
    if (!file_exists($logDir)) {
        mkdir($logDir, 0755, true);
    }
}

// Activar en desarrollo, comentar en producción
enableDebugging();