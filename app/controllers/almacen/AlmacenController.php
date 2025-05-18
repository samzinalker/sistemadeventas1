<?php
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Validator.php';
require_once __DIR__ . '/../../core/FileHandler.php';
require_once __DIR__ . '/../../models/AlmacenModel.php';

/**
 * Controlador para el módulo de almacén
 */
class AlmacenController extends Controller {
    private $almacenModel;
    private $validator;
    private $fileHandler;
    
    /**
     * Constructor
     * @param PDO|null $pdo Conexión PDO opcional
     */
    public function __construct($pdo = null) {
        parent::__construct();
        $this->almacenModel = new AlmacenModel($pdo);
        $this->validator = new Validator();
        
        // Directorio de imágenes de productos
        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . $this->url . '/public/images/productos';
        $this->fileHandler = new FileHandler($uploadDir);
    }
    
    /**
     * Lista todos los productos
     * @param bool $onlyCurrentUser Si solo debe mostrar productos del usuario actual
     * @return array Productos
     */
    public function index($onlyCurrentUser = true) {
        $this->checkSession();
        
        $userId = null;
        if ($onlyCurrentUser) {
            $userId = $this->getUserId();
        }
        
        return $this->almacenModel->getAllWithDetails($userId);
    }
    
    /**
     * Obtiene un producto
     * @param int $id ID del producto
     * @param bool $checkOwnership Si debe verificar la propiedad
     * @return array|false Producto o false
     */
    public function show($id, $checkOwnership = true) {
        $this->checkSession();
        
        $userId = null;
        if ($checkOwnership) {
            $userId = $this->getUserId();
        }
        
        return $this->almacenModel->getById($id, $userId);
    }
    
    /**
     * Prepara datos para el formulario de creación
     * @return array Datos para el formulario
     */
    public function create() {
        $this->checkSession();
        
        // Generar código para el nuevo producto
        $code = $this->almacenModel->generateCode();
        
        return [
            'codigo' => $code,
            'fecha_actual' => date('Y-m-d')
        ];
    }
    
    /**
     * Guarda un nuevo producto
     * @param array $data Datos del producto
     * @param array $file Archivo de imagen
     * @return array Resultado de la operación
     */
    public function store($data, $file = null) {
        $this->checkSession();
        
        // Validar campos obligatorios
        if (!$this->validator->required($data, ['nombre', 'stock', 'precio_venta', 'fecha_ingreso', 'id_categoria'])) {
            return ['status' => false, 'message' => $this->validator->getFirstError()];
        }
        
        // Validar campos numéricos
        if (isset($data['stock']) && !$this->validator->numeric($data['stock'], 'stock')) {
            return ['status' => false, 'message' => $this->validator->getFirstError()];
        }
        
        if (isset($data['precio_venta']) && !$this->validator->numeric($data['precio_venta'], 'precio_venta')) {
            return ['status' => false, 'message' => $this->validator->getFirstError()];
        }
        
        // Validar valores mínimos
        if (isset($data['stock']) && !$this->validator->min($data['stock'], 0, 'stock')) {
            return ['status' => false, 'message' => $this->validator->getFirstError()];
        }
        
        if (isset($data['precio_venta']) && !$this->validator->min($data['precio_venta'], 0, 'precio_venta')) {
            return ['status' => false, 'message' => $this->validator->getFirstError()];
        }
        
        // Validar fecha
        if (!$this->validator->date($data['fecha_ingreso'], 'fecha_ingreso')) {
            return ['status' => false, 'message' => $this->validator->getFirstError()];
        }
        
        // Procesar imagen si existe
        $imageName = null;
        if (isset($file) && !empty($file['name'])) {
            [$valid, $message, $fileName] = $this->fileHandler->validateImage($file);
            
            if (!$valid) {
                return ['status' => false, 'message' => $message];
            }
            
            $result = $this->fileHandler->uploadFile($file, $fileName);
            if ($result !== true) {
                return ['status' => false, 'message' => $result];
            }
            
            $imageName = $fileName;
        }
        
        // Preparar datos para guardar
        $productData = [
            'codigo' => $data['codigo'],
            'nombre' => $data['nombre'],
            'descripcion' => $data['descripcion'] ?? '',
            'stock' => $data['stock'],
            'stock_minimo' => $data['stock_minimo'] ?? 0,
            'stock_maximo' => $data['stock_maximo'] ?? 0,
            'precio_compra' => $data['precio_compra'] ?? 0,
            'precio_venta' => $data['precio_venta'],
            'fecha_ingreso' => $data['fecha_ingreso'],
            'imagen' => $imageName,
            'id_usuario' => $this->getUserId(),
            'id_categoria' => $data['id_categoria'],
            'fyh_creacion' => date('Y-m-d H:i:s'),
            'fyh_actualizacion' => date('Y-m-d H:i:s')
        ];
        
        // Guardar en la base de datos
        $result = $this->almacenModel->create($productData);
        
        if (is_numeric($result)) {
            return [
                'status' => true,
                'message' => 'Producto registrado correctamente',
                'id' => $result
            ];
        } else {
            // Si hay error y se subió una imagen, eliminarla
            if ($imageName) {
                $this->fileHandler->deleteFile($imageName);
            }
            
            return [
                'status' => false,
                'message' => is_string($result) ? $result : 'Error al guardar el producto'
            ];
        }
    }
    
    /**
     * Prepara datos para el formulario de edición
     * @param int $id ID del producto
     * @param bool $checkOwnership Si debe verificar la propiedad
     * @return array|false Datos para el formulario o false
     */
    public function edit($id, $checkOwnership = true) {
        $this->checkSession();
        
        $userId = null;
        if ($checkOwnership) {
            $userId = $this->getUserId();
        }
        
        $producto = $this->almacenModel->getById($id, $userId);
        if (!$producto) {
            return false;
        }
        
        return $producto;
    }
    
    /**
     * Actualiza un producto
     * @param int $id ID del producto
     * @param array $data Datos del producto
     * @param array $file Archivo de imagen
     * @param bool $checkOwnership Si debe verificar la propiedad
     * @return array Resultado de la operación
     */
    public function update($id, $data, $file = null, $checkOwnership = true) {
        $this->checkSession();
        
        $userId = null;
        if ($checkOwnership) {
            $userId = $this->getUserId();
        }
        
        // Verificar que el producto exista
        $producto = $this->almacenModel->getById($id, $userId);
        if (!$producto) {
            return ['status' => false, 'message' => 'Producto no encontrado o no autorizado'];
        }
        
        // Realizar validaciones similares a store()
        // ...
        
        // Procesar imagen si se proporcionó una nueva
        if (isset($file) && !empty($file['name'])) {
            [$valid, $message, $fileName] = $this->fileHandler->validateImage($file);
            
            if (!$valid) {
                return ['status' => false, 'message' => $message];
            }
            
            $result = $this->fileHandler->uploadFile($file, $fileName);
            if ($result !== true) {
                return ['status' => false, 'message' => $result];
            }
            
            // Eliminar imagen anterior si existe
            if (!empty($producto['imagen'])) {
                $this->fileHandler->deleteFile($producto['imagen']);
            }
            
            $data['imagen'] = $fileName;
        }
        
        // Actualizar fecha
        $data['fyh_actualizacion'] = date('Y-m-d H:i:s');
        
        // Actualizar producto
        $result = $this->almacenModel->update($id, $data, $userId);
        
        if ($result === true) {
            return [
                'status' => true,
                'message' => 'Producto actualizado correctamente'
            ];
        } else {
            return [
                'status' => false,
                'message' => is_string($result) ? $result : 'Error al actualizar el producto'
            ];
        }
    }
    
    /**
     * Elimina un producto
     * @param int $id ID del producto
     * @param bool $checkOwnership Si debe verificar la propiedad
     * @return array Resultado de la operación
     */
    public function destroy($id, $checkOwnership = true) {
        $this->checkSession();
        
        $userId = null;
        if ($checkOwnership) {
            $userId = $this->getUserId();
        }
        
        // Verificar que el producto exista
        $producto = $this->almacenModel->getById($id, $userId);
        if (!$producto) {
            return ['status' => false, 'message' => 'Producto no encontrado o no autorizado'];
        }
        
        // Intentar eliminar
        $result = $this->almacenModel->delete($id, $userId);
        
        if ($result === true) {
            // Eliminar imagen asociada si existe
            if (!empty($producto['imagen'])) {
                $this->fileHandler->deleteFile($producto['imagen']);
            }
            
            return [
                'status' => true,
                'message' => 'Producto eliminado correctamente'
            ];
        } else {
            return [
                'status' => false,
                'message' => is_string($result) ? $result : 'Error al eliminar el producto'
            ];
        }
    }
    
    /**
     * Busca productos
     * @param string $term Término de búsqueda
     * @param bool $onlyCurrentUser Si solo debe buscar productos del usuario actual
     * @return array Productos encontrados
     */
    public function search($term, $onlyCurrentUser = true) {
        $this->checkSession();
        
        $userId = null;
        if ($onlyCurrentUser) {
            $userId = $this->getUserId();
        }
        
        return $this->almacenModel->search($term, $userId);
    }
    
    /**
     * Obtiene productos con stock bajo
     * @param bool $onlyCurrentUser Si solo debe mostrar productos del usuario actual
     * @return array Productos con stock bajo
     */
    public function lowStock($onlyCurrentUser = true) {
        $this->checkSession();
        
        $userId = null;
        if ($onlyCurrentUser) {
            $userId = $this->getUserId();
        }
        
        return $this->almacenModel->getLowStock($userId);
    }
    
    /**
     * Actualiza el stock de un producto
     * @param int $id ID del producto
     * @param int $quantity Cantidad a añadir (positivo) o restar (negativo)
     * @param bool $checkOwnership Si debe verificar la propiedad
     * @return array Resultado de la operación
     */
    public function updateStock($id, $quantity, $checkOwnership = true) {
        $this->checkSession();
        
        $userId = null;
        if ($checkOwnership) {
            $userId = $this->getUserId();
        }
        
        $result = $this->almacenModel->updateStock($id, $quantity, $userId);
        
        if ($result === true) {
            return [
                'status' => true,
                'message' => 'Stock actualizado correctamente'
            ];
        } else {
            return [
                'status' => false,
                'message' => is_string($result) ? $result : 'Error al actualizar el stock'
            ];
        }
    }
}