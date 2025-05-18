<?php
require_once __DIR__ . '/../../models/RolModel.php';

/**
 * Controlador para el módulo de roles
 */
class RolController {
    private $rolModel;
    
    /**
     * Constructor
     * @param PDO|null $pdo Conexión PDO opcional
     */
    public function __construct($pdo = null) {
        $this->rolModel = new RolModel($pdo);
    }
    
    /**
     * Obtener todos los roles con contador de usuarios
     * @return array Lista de roles
     */
    public function index() {
        $roles = $this->rolModel->getAll();
        $userCounts = $this->rolModel->getUserCountByRol();
        
        // Agregar información de usuarios por rol
        foreach ($roles as &$rol) {
            $rol['cantidad_usuarios'] = isset($userCounts[$rol['id_rol']]) 
                ? $userCounts[$rol['id_rol']]['cantidad'] 
                : 0;
        }
        
        return $roles;
    }
    
    /**
     * Almacenar un nuevo rol
     * @param array $data Datos del formulario
     * @return array Resultado de la operación
     */
    public function store($data) {
        // Validar datos
        if (empty($data['rol'])) {
            return [
                'status' => 'error', 
                'message' => 'El nombre del rol es obligatorio'
            ];
        }
        
        // Preparar datos del rol
        $rolData = [
            'rol' => trim($data['rol']),
            'fyh_creacion' => date('Y-m-d H:i:s'),
            'fyh_actualizacion' => date('Y-m-d H:i:s')
        ];
        
        // Guardar rol
        $result = $this->rolModel->createRol($rolData);
        
        if (is_numeric($result)) {
            return [
                'status' => 'success',
                'message' => 'Rol creado correctamente',
                'id' => $result
            ];
        } else {
            return [
                'status' => 'error',
                'message' => $result
            ];
        }
    }
    
    /**
     * Obtener rol por ID
     * @param int $id ID del rol
     * @return array|false Datos del rol o false
     */
    public function show($id) {
        return $this->rolModel->getById($id);
    }
    
    /**
     * Actualizar datos de rol
     * @param int $id ID del rol
     * @param array $data Datos del formulario
     * @return array Resultado de la operación
     */
    public function update($id, $data) {
        // Validar datos
        if (empty($data['rol'])) {
            return [
                'status' => 'error', 
                'message' => 'El nombre del rol es obligatorio'
            ];
        }
        
        // Verificar que el rol existe
        $rol = $this->rolModel->getById($id);
        if (!$rol) {
            return [
                'status' => 'error', 
                'message' => 'Rol no encontrado'
            ];
        }
        
        // No permitir editar el rol con ID 1 (administrador)
        if ($id == 1) {
            return [
                'status' => 'error', 
                'message' => 'No se puede modificar el rol de administrador principal'
            ];
        }
        
        // Verificar si ya existe otro rol con el mismo nombre
        $existingRol = $this->rolModel->findByName($data['rol']);
        if ($existingRol && $existingRol['id_rol'] != $id) {
            return [
                'status' => 'error',
                'message' => 'Ya existe un rol con ese nombre'
            ];
        }
        
        // Preparar datos de actualización
        $rolData = [
            'rol' => trim($data['rol']),
            'fyh_actualizacion' => date('Y-m-d H:i:s')
        ];
        
        // Actualizar rol
        $result = $this->rolModel->update($id, $rolData);
        
        if ($result === true) {
            return [
                'status' => 'success',
                'message' => 'Rol actualizado correctamente'
            ];
        } else {
            return [
                'status' => 'error',
                'message' => is_string($result) ? $result : 'Error al actualizar el rol'
            ];
        }
    }
    
    /**
     * Eliminar rol
     * @param int $id ID del rol
     * @return array Resultado de la operación
     */
    public function destroy($id) {
        // Verificar que el rol existe
        $rol = $this->rolModel->getById($id);
        if (!$rol) {
            return [
                'status' => 'error', 
                'message' => 'Rol no encontrado'
            ];
        }
        
        // No permitir eliminar el rol con ID 1 (administrador)
        if ($id == 1) {
            return [
                'status' => 'error', 
                'message' => 'No se puede eliminar el rol de administrador principal'
            ];
        }
        
        // Verificar si hay usuarios asociados al rol
        if ($this->rolModel->hasUsers($id)) {
            return [
                'status' => 'error',
                'message' => 'No se puede eliminar el rol porque tiene usuarios asociados'
            ];
        }
        
        // Eliminar rol
        $result = $this->rolModel->delete($id);
        
        if ($result === true) {
            return [
                'status' => 'success',
                'message' => 'Rol eliminado correctamente'
            ];
        } else {
            return [
                'status' => 'error',
                'message' => is_string($result) ? $result : 'Error al eliminar el rol'
            ];
        }
    }
}