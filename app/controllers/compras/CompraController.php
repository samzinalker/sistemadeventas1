<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../models/CompraModel.php';
require_once __DIR__ . '/../../models/AlmacenModel.php';
require_once __DIR__ . '/../../models/ProveedorModel.php';

/**
 * Controlador para el módulo de compras
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
        
        // Obtener estadísticas
        $stats = $this->compraModel->getStats($userId);
        
        // Obtener listado de compras
        $compras = $this->compraModel->getAllWithDetails($userId);
        
        return [
            'stats' => $stats,
            'compras' => $compras
        ];
    }
    
    /**
     * Prepara datos para el formulario de creación de compra
     * @return array Datos para el formulario
     */
    public function create() {
        $this->checkSession();
        
        // Obtener listado de productos
        $productos = $this->almacenModel->getAll($this->getUserId());
        
        // Obtener listado de proveedores
        $proveedores = $this->proveedorModel->getAllActive($this->getUserId());
        
        // Generar número de compra
        $nroCompra = $this->compraModel->generateNroCompra();
        
        return [
            'productos' => $productos,
            'proveedores' => $proveedores,
            'nro_compra' => $nroCompra,
            'fecha_actual' => date('Y-m-d')
        ];
    }
    
    /**
     * Procesa el formulario de creación de compra
     * @param array $data Datos del formulario
     * @return array Resultado de la operación
     */
    public function store($data) {
        $this->checkSession();
        
        // Validar campos obligatorios
        if (!$this->validator->required($data, [
            'id_producto', 'nro_compra', 'fecha_compra', 'id_proveedor', 
            'comprobante', 'precio_compra', 'cantidad'
        ])) {
            return ['status' => false, 'message' => $this->validator->getFirstError()];
        }
        
        // Validar campos numéricos
        if (!$this->validator->numeric($data['precio_compra'], 'precio_compra')) {
            return ['status' => false, 'message' => $this->validator->getFirstError()];
        }
        
        if (!$this->validator->numeric($data['cantidad'], 'cantidad')) {
            return ['status' => false, 'message' => $this->validator->getFirstError()];
        }
        
        // Validar valores positivos
        if (!$this->validator->min($data['precio_compra'], 0, 'precio_compra')) {
            return ['status' => false, 'message' => $this->validator->getFirstError()];
        }
        
        if (!$this->validator->min($data['cantidad'], 1, 'cantidad')) {
            return ['status' => false, 'message' => 'La cantidad debe ser al menos 1'];
        }
        
        // Validar fecha
        if (!$this->validator->date($data['fecha_compra'], 'fecha_compra')) {
            return ['status' => false, 'message' => $this->validator->getFirstError()];
        }
        
        // Verificar que el producto exista
        $producto = $this->almacenModel->getById($data['id_producto']);
        if (!$producto) {
            return ['status' => false, 'message' => 'El producto seleccionado no existe'];
        }
        
        // Verificar que el proveedor exista
        $proveedor = $this->proveedorModel->getById($data['id_proveedor']);
        if (!$proveedor) {
            return ['status' => false, 'message' => 'El proveedor seleccionado no existe'];
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
    }
    
    /**
     * Obtiene los detalles de una compra
     * @param int $id ID de la compra
     * @param bool $checkOwnership Si debe verificar la propiedad
     * @return array|false Detalles de la compra o false
     */
    public function show($id, $checkOwnership = true) {
        $this->checkSession();
        
        $userId = null;
        if ($checkOwnership) {
            $userId = $this->getUserId();
        }
        
        return $this->compraModel->getByIdWithDetails($id, $userId);
    }
    
    /**
     * Busca compras según criterios
     * @param array $criteria Criterios de búsqueda
     * @param bool $onlyCurrentUser Si solo debe buscar compras del usuario actual
     * @return array Compras encontradas
     */
    public function search($criteria, $onlyCurrentUser = true) {
        $this->checkSession();
        
        $userId = null;
        if ($onlyCurrentUser) {
            $userId = $this->getUserId();
        }
        
        return $this->compraModel->search($criteria, $userId);
    }
    
    /**
     * Obtiene estadísticas de compras
     * @param bool $onlyCurrentUser Si solo debe mostrar estadísticas del usuario actual
     * @return array Datos estadísticos
     */
    public function getStats($onlyCurrentUser = true) {
        $this->checkSession();
        
        $userId = null;
        if ($onlyCurrentUser) {
            $userId = $this->getUserId();
        }
        
        return $this->compraModel->getStats($userId);
    }
    
    /**
     * Obtiene datos de producto para AJAX
     * @param int $id ID del producto
     * @return array|false Datos del producto
     */
    public function getProductInfo($id) {
        $this->checkSession();
        return $this->almacenModel->getById($id);
    }
    
    /**
     * Obtiene datos de proveedor para AJAX
     * @param int $id ID del proveedor
     * @return array|false Datos del proveedor
     */
    public function getProveedorInfo($id) {
        $this->checkSession();
        return $this->proveedorModel->getById($id);
    }
}