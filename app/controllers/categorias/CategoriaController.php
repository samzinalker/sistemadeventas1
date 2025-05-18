<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../models/CategoriaModel.php';

/**
 * Controlador para el módulo de categorías
 */
class CategoriaController extends Controller {
    private $categoriaModel;
    
    /**
     * Constructor
     */
    public function __construct($pdo = null) {
        parent::__construct();
        $this->categoriaModel = new CategoriaModel($pdo);
    }
    
    /**
     * Obtiene todas las categorías
     * @param bool $onlyCurrentUser Si solo debe mostrar categorías del usuario actual
     * @return array Categorías
     */
    public function index($onlyCurrentUser = true) {
        $this->checkSession();
        
        $userId = null;
        if ($onlyCurrentUser) {
            $userId = $this->getUserId();
        }
        
        return $this->categoriaModel->getAllWithCount($userId);
    }
    
    /**
     * Crea una nueva categoría
     * @param string $nombreCategoria Nombre de la categoría
     * @return array Resultado de la operación
     */
    public function store($nombreCategoria) {
        $this->checkSession();
        
        // Validar nombre de categoría
        if (empty(trim($nombreCategoria))) {
            return [
                'status' => false,
                'message' => 'El nombre de la categoría es obligatorio',
                'icon' => 'error'
            ];
        }
        
        // Preparar datos para guardar
        $data = [
            'nombre_categoria' => trim($nombreCategoria),
            'id_usuario' => $this->getUserId(),
            'fyh_creacion' => date('Y-m-d H:i:s'),
            'fyh_actualizacion' => date('Y-m-d H:i:s')
        ];
        
        // Guardar en la base de datos
        $result = $this->categoriaModel->create($data);
        
        if (is_numeric($result)) {
            return [
                'status' => true,
                'message' => 'Categoría creada correctamente',
                'icon' => 'success',
                'id' => $result
            ];
        } else {
            return [
                'status' => false,
                'message' => is_string($result) ? $result : 'Error al crear la categoría',
                'icon' => 'error'
            ];
        }
    }
    
    /**
     * Actualiza una categoría existente
     * @param int $id ID de la categoría
     * @param string $nombreCategoria Nuevo nombre
     * @return array Resultado de la operación
     */
    public function update($id, $nombreCategoria) {
        $this->checkSession();
        
        // Validar nombre de categoría
        if (empty(trim($nombreCategoria))) {
            return [
                'status' => false,
                'message' => 'El nombre de la categoría es obligatorio',
                'icon' => 'error'
            ];
        }
        
        // Verificar que la categoría exista y pertenezca al usuario
        $userId = $this->getUserId();
        $categoria = $this->categoriaModel->getById($id, $userId);
        
        if (!$categoria) {
            return [
                'status' => false,
                'message' => 'La categoría no existe o no tienes permisos para editarla',
                'icon' => 'error'
            ];
        }
        
        // Datos a actualizar
        $data = [
            'nombre_categoria' => trim($nombreCategoria),
            'fyh_actualizacion' => date('Y-m-d H:i:s')
        ];
        
        // Actualizar en la base de datos
        $result = $this->categoriaModel->update($id, $data, $userId);
        
        if ($result === true) {
            return [
                'status' => true,
                'message' => 'Categoría actualizada correctamente',
                'icon' => 'success'
            ];
        } else {
            return [
                'status' => false,
                'message' => is_string($result) ? $result : 'Error al actualizar la categoría',
                'icon' => 'error'
            ];
        }
    }
    
    /**
     * Elimina una categoría
     * @param int $id ID de la categoría
     * @return array Resultado de la operación
     */
    public function destroy($id) {
        $this->checkSession();
        
        // Verificar que la categoría exista y pertenezca al usuario
        $userId = $this->getUserId();
        $categoria = $this->categoriaModel->getById($id, $userId);
        
        if (!$categoria) {
            return [
                'status' => false,
                'message' => 'La categoría no existe o no tienes permisos para eliminarla',
                'icon' => 'error'
            ];
        }
        
        // Intentar eliminar
        $result = $this->categoriaModel->delete($id, $userId);
        
        if ($result === true) {
            return [
                'status' => true,
                'message' => 'Categoría eliminada correctamente',
                'icon' => 'success'
            ];
        } else {
            return [
                'status' => false,
                'message' => is_string($result) ? $result : 'Error al eliminar la categoría',
                'icon' => 'error'
            ];
        }
    }
    
    /**
     * Busca categorías por término
     * @param string $term Término de búsqueda
     * @return array Categorías encontradas
     */
    public function search($term) {
        $this->checkSession();
        $userId = $this->getUserId();
        
        return $this->categoriaModel->search($term, $userId);
    }
}