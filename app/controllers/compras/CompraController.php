<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Validator.php';
require_once __DIR__ . '/../../models/CompraModel.php';
require_once __DIR__ . '/../../models/AlmacenModel.php';
require_once __DIR__ . '/../../models/ProveedorModel.php';

/**
 * Controlador para el módulo de compras
 * Gestiona todas las operaciones relacionadas con las compras de productos
 */
class CompraController extends Controller {
    private $compraModel;
    private $almacenModel;
    private $proveedorModel;
    private $validator;
    
    /**
     * Constructor
     * @param PDO|null $pdo Conexión PDO opcional
     */
    public function __construct($pdo = null) {
        parent::__construct();
        $this->compraModel = new CompraModel($pdo);
        $this->almacenModel = new AlmacenModel($pdo);
        $this->proveedorModel = new ProveedorModel($pdo);
        $this->validator = new Validator();
    }
    
    /**
     * Obtiene todas las compras para mostrar en la vista principal
     * @param bool $onlyCurrentUser Si solo debe mostrar compras del usuario actual
     * @return array Compras
     */
    public function index($onlyCurrentUser = true) {
        $this->checkSession();
        
        $userId = null;
        if ($onlyCurrentUser) {
            $userId = $this->getUserId();
        }
        
        try {
            // Limitar a las últimas 50 compras para mejorar rendimiento
            $compras = $this->compraModel->getAllWithLimit($userId, 50);
            
            // Obtener estadísticas optimizadas
            $stats = $this->compraModel->getStatsOptimized($userId);
            
            return [
                'stats' => $stats,
                'compras' => $compras
            ];
        } catch (Exception $e) {
            $this->logError("Error en CompraController::index: " . $e->getMessage());
            return [
                'stats' => [
                    'total' => ['count' => 0, 'total' => 0],
                    'month' => ['count' => 0, 'total' => 0],
                    'week' => ['count' => 0, 'total' => 0],
                    'today' => ['count' => 0, 'total' => 0]
                ],
                'compras' => []
            ];
        }
    }
    
    /**
     * Prepara datos para el formulario de creación de compra
     * @return array Datos para el formulario
     */
    public function create() {
        $this->checkSession();
        
        try {
            // Obtener listado de productos (limitado para mejor rendimiento)
            $productos = $this->almacenModel->getAll($this->getUserId());
            
            // Obtener listado de proveedores (limitado para mejor rendimiento)
            $proveedores = $this->proveedorModel->getAll($this->getUserId());
            
            // Generar número de compra
            $nroCompra = $this->compraModel->generateNroCompra();
            
            return [
                'productos' => $productos,
                'proveedores' => $proveedores,
                'nro_compra' => $nroCompra,
                'fecha_actual' => date('Y-m-d')
            ];
        } catch (Exception $e) {
            $this->logError("Error en CompraController::create: " . $e->getMessage());
            throw $e; // Relanzar para manejo en la vista
        }
    }
    
    /**
     * Procesa el formulario de creación de compra
     * @param array $data Datos del formulario
     * @return array Resultado de la operación
     */
    public function store($data) {
        $this->checkSession();
        
        try {
            // Validar campos obligatorios
            if (!isset(
                $data['id_producto'], 
                $data['nro_compra'], 
                $data['fecha_compra'], 
                $data['id_proveedor'], 
                $data['comprobante'], 
                $data['precio_compra'], 
                $data['cantidad']
            )) {
                return [
                    'status' => false,
                    'message' => 'Faltan campos obligatorios en el formulario'
                ];
            }
            
            // Validar campos numéricos
            if (!is_numeric($data['precio_compra']) || floatval($data['precio_compra']) <= 0) {
                return [
                    'status' => false,
                    'message' => 'El precio de compra debe ser un número positivo'
                ];
            }
            
            if (!is_numeric($data['cantidad']) || intval($data['cantidad']) <= 0) {
                return [
                    'status' => false,
                    'message' => 'La cantidad debe ser un número positivo'
                ];
            }
            
            // Validar fecha
            $fechaFormato = DateTime::createFromFormat('Y-m-d', $data['fecha_compra']);
            if (!$fechaFormato || $fechaFormato->format('Y-m-d') !== $data['fecha_compra']) {
                return [
                    'status' => false,
                    'message' => 'El formato de fecha debe ser YYYY-MM-DD'
                ];
            }
            
            // Verificar que el producto exista
            $producto = $this->almacenModel->getById($data['id_producto']);
            if (!$producto) {
                return [
                    'status' => false,
                    'message' => 'El producto seleccionado no existe'
                ];
            }
            
            // Verificar que el proveedor exista
            $proveedor = $this->proveedorModel->getById($data['id_proveedor']);
            if (!$proveedor) {
                return [
                    'status' => false,
                    'message' => 'El proveedor seleccionado no existe'
                ];
            }
            
            // Preparar datos para guardar
            $compraData = [
                'id_producto' => $data['id_producto'],
                'nro_compra' => $data['nro_compra'],
                'fecha_compra' => $data['fecha_compra'],
                'id_proveedor' => $data['id_proveedor'],
                'comprobante' => $data['comprobante'],
                'id_usuario' => $this->getUserId(),
                'precio_compra' => $data['precio_compra'],
                'cantidad' => $data['cantidad'],
                'fyh_creacion' => date('Y-m-d H:i:s'),
                'fyh_actualizacion' => date('Y-m-d H:i:s')
            ];
            
            // Guardar y actualizar stock
            $result = $this->compraModel->createAndUpdateStock($compraData);
            
            if (is_numeric($result)) {
                return [
                    'status' => true,
                    'message' => 'Compra registrada correctamente',
                    'id' => $result
                ];
            } else {
                return [
                    'status' => false,
                    'message' => is_string($result) ? $result : 'Error al registrar la compra'
                ];
            }
        } catch (Exception $e) {
            $this->logError("Error en CompraController::store: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Error interno del servidor: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtiene los detalles de una compra
     * @param int $id ID de la compra
     * @param bool $checkOwnership Si debe verificar la propiedad
     * @return array|false Detalles de la compra o false
     */
    public function show($id, $checkOwnership = true) {
        $this->checkSession();
        
        try {
            $userId = null;
            if ($checkOwnership) {
                $userId = $this->getUserId();
            }
            
            $compra = $this->compraModel->getByIdWithDetails($id, $userId);
            
            if (!$compra) {
                $this->logError("CompraController::show - Compra no encontrada ID: $id");
            }
            
            return $compra;
        } catch (Exception $e) {
            $this->logError("Error en CompraController::show: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Busca compras según criterios
     * @param array $criteria Criterios de búsqueda
     * @param bool $onlyCurrentUser Si solo debe buscar compras del usuario actual
     * @return array Compras encontradas
     */
    public function search($criteria, $onlyCurrentUser = true) {
        $this->checkSession();
        
        try {
            $userId = null;
            if ($onlyCurrentUser) {
                $userId = $this->getUserId();
            }
            
            // Sanitizar criterios de búsqueda
            $sanitizedCriteria = [];
            
            if (!empty($criteria['producto'])) {
                $sanitizedCriteria['producto'] = trim($criteria['producto']);
            }
            
            if (!empty($criteria['proveedor'])) {
                $sanitizedCriteria['proveedor'] = trim($criteria['proveedor']);
            }
            
            if (!empty($criteria['fecha_desde'])) {
                // Validar formato fecha
                $fecha = DateTime::createFromFormat('Y-m-d', $criteria['fecha_desde']);
                if ($fecha && $fecha->format('Y-m-d') === $criteria['fecha_desde']) {
                    $sanitizedCriteria['fecha_desde'] = $criteria['fecha_desde'];
                }
            }
            
            if (!empty($criteria['fecha_hasta'])) {
                // Validar formato fecha
                $fecha = DateTime::createFromFormat('Y-m-d', $criteria['fecha_hasta']);
                if ($fecha && $fecha->format('Y-m-d') === $criteria['fecha_hasta']) {
                    $sanitizedCriteria['fecha_hasta'] = $criteria['fecha_hasta'];
                }
            }
            
            return $this->compraModel->search($sanitizedCriteria, $userId);
        } catch (Exception $e) {
            $this->logError("Error en CompraController::search: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtiene estadísticas de compras
     * @param bool $onlyCurrentUser Si solo debe mostrar estadísticas del usuario actual
     * @return array Datos estadísticos
     */
    public function getStats($onlyCurrentUser = true) {
        $this->checkSession();
        
        try {
            $userId = null;
            if ($onlyCurrentUser) {
                $userId = $this->getUserId();
            }
            
            return $this->compraModel->getStatsOptimized($userId);
        } catch (Exception $e) {
            $this->logError("Error en CompraController::getStats: " . $e->getMessage());
            return [
                'total' => ['count' => 0, 'total' => 0],
                'month' => ['count' => 0, 'total' => 0],
                'week' => ['count' => 0, 'total' => 0],
                'today' => ['count' => 0, 'total' => 0]
            ];
        }
    }
    
    /**
     * Obtiene datos de producto para AJAX
     * @param int $id ID del producto
     * @return array|false Datos del producto
     */
    public function getProductInfo($id) {
        $this->checkSession();
        try {
            return $this->almacenModel->getById($id);
        } catch (Exception $e) {
            $this->logError("Error en CompraController::getProductInfo: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtiene datos de proveedor para AJAX
     * @param int $id ID del proveedor
     * @return array|false Datos del proveedor
     */
    public function getProveedorInfo($id) {
        $this->checkSession();
        try {
            return $this->proveedorModel->getById($id);
        } catch (Exception $e) {
            $this->logError("Error en CompraController::getProveedorInfo: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verifica si el usuario está autenticado
     * @throws Exception Si no hay sesión activa
     */
    protected function checkSession() {
        if (!isset($_SESSION['id_usuario'])) {
            throw new Exception("Usuario no autenticado");
        }
    }
    
    /**
     * Obtiene el ID del usuario actual
     * @return int ID del usuario
     * @throws Exception Si no hay ID de usuario en la sesión
     */
    protected function getUserId() {
        if (!isset($_SESSION['id_usuario'])) {
            throw new Exception("No hay ID de usuario en la sesión");
        }
        return $_SESSION['id_usuario'];
    }
    
    /**
     * Registra errores en el log
     * @param string $message Mensaje de error
     */
    protected function logError($message) {  // Cambiado de private a protected
        $logDir = __DIR__ . '/../../../logs';
        if (!file_exists($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $logFile = $logDir . '/compras_controller_errors.log';
        $date = date('Y-m-d H:i:s');
        error_log("[$date] $message\n", 3, $logFile);
    }
}