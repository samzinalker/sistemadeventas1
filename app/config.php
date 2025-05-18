<?php
// Definir constantes de conexión a la base de datos
if (!defined('SERVIDOR')) define('SERVIDOR','localhost');
if (!defined('USUARIO')) define('USUARIO','root');
if (!defined('PASSWORD')) define('PASSWORD','');
if (!defined('BD')) define('BD','sistemadeventas');

// Definir rutas base para el proyecto
define('BASE_PATH', $_SERVER['DOCUMENT_ROOT'] . '/sistemadeventas');
define('APP_PATH', BASE_PATH . '/app');
define('CONTROLLERS_PATH', APP_PATH . '/controllers');
define('MODELS_PATH', APP_PATH . '/models');
define('VIEWS_PATH', BASE_PATH . '/views');
define('PUBLIC_PATH', BASE_PATH . '/public');
define('UPLOADS_PATH', PUBLIC_PATH . '/images');

// Definir URL base
$URL = "http://localhost/sistemadeventas";
define('BASE_URL', $URL);

// Conexión a la base de datos
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

// Configuración de zona horaria
date_default_timezone_set('America/Guayaquil');
$fechaHora = date('Y-m-d H:i:s');

/**
 * Función auxiliar para incluir archivos con rutas absolutas
 * 
 * @param string $path Ruta relativa del archivo a incluir
 * @param string $base Constante de ruta base (opcional)
 * @return bool Resultado de la inclusión
 */
function includeFile($path, $base = BASE_PATH) {
    $fullPath = $base . '/' . ltrim($path, '/');
    if (file_exists($fullPath)) {
        return include_once $fullPath;
    } else {
        error_log("Error: No se pudo encontrar el archivo $fullPath");
        return false;
    }
}

/**
 * Función para incluir controladores
 * 
 * @param string $controller Nombre del controlador sin extensión
 * @return bool Resultado de la inclusión
 */
function includeController($controller) {
    return includeFile("controllers/$controller.php", APP_PATH);
}

/**
 * Función para incluir modelos
 * 
 * @param string $model Nombre del modelo sin extensión
 * @return bool Resultado de la inclusión
 */
function includeModel($model) {
    return includeFile("models/$model.php", APP_PATH);
}

// Función para habilitar depuración durante desarrollo
function enableDebugging() {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    
    // Crear directorio de logs si no existe
    $logDir = BASE_PATH . '/logs';
    if (!file_exists($logDir)) {
        mkdir($logDir, 0755, true);
    }
}

// Activar en desarrollo, comentar en producción
enableDebugging();