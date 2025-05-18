<?php
require_once __DIR__ . '/../../models/RolModel.php';
require_once __DIR__ . '/../../utils/Security.php';

/**
 * Controlador para el módulo de roles
 */
class RolController {
    private $rolModel;
    private $security;
    
    /**
     * Constructor
     * @param PDO|null $pdo Conexión PDO opcional
     */
    public function __construct($pdo = null) {
        $this->rolModel = new RolModel($pdo);
        $this->security = new Security();
    }
    
    /**
     * Verifica permisos de administrador
     * @return bool True si el usuario actual es administrador
     */
    public function checkAdminPermission() {
        if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'administrador') {
            // Registrar intento no autorizado
            $this->security->logSuspiciousActivity(
                'unauthorized_access', 
                'Intento de acceso a módulo de roles sin permisos'
            );
            return false;
        }
        return true;
    }
    
    /**
     * Obtener todos los roles con contador de usuarios
     * @return array Lista de roles
     */
    public function index() {
        // Verificar permisos
        if (!$this->checkAdminPermission()) {
            return [
                'status' => 'error',
                'message' => 'No tiene permisos para esta acción',
                'data' => []
            ];
        }
        
        try {
            $roles = $this->rolModel->getAll();
            $userCounts = $this->rolModel->getUserCountByRol();
            
            // Agregar información de usuarios por rol
            foreach ($roles as &$rol) {
                $rol['cantidad_usuarios'] = isset($userCounts[$rol['id_rol']]) 
                    ? $userCounts[$rol['id_rol']]['cantidad'] 
                    : 0;
            }
            
            return [
                'status' => 'success',
                'data' => $roles,
                'csrf_token' => $this->security->generateCSRFToken()
            ];
        } catch (Exception $e) {
            error_log("Error en RolController::index: " . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Error al obtener la lista de roles',
                'data' => []
            ];
        }
    }
    
    /**
     * Obtener datos para el formulario de creación
     * @return array Datos para el formulario
     */
    public function create() {
        // Verificar permisos
        if (!$this->checkAdminPermission()) {
            return [
                'status' => 'error',
                'message' => 'No tiene permisos para esta acción',
                'data' => []
            ];
        }
        
        return [
            'status' => 'success',
            'data' => [
                'csrf_token' => $this->security->generateCSRFToken()
            ]
        ];
    }
    
    /**
     * Almacenar un nuevo rol
     * @param array $data Datos del formulario
     * @return array Resultado de la operación
     */
    public function store($data) {
        // Verificar permisos
        if (!$this->checkAdminPermission()) {
            return [
                'status' => 'error',
                'message' => 'No tiene permisos para esta acción'
            ];
        }
        
        // Verificar token CSRF
        if (!isset($data['csrf_token']) || !$this->security->verifyCSRFToken($data['csrf_token'])) {
            $this->security->logSuspiciousActivity('csrf_attempt', 'Intento CSRF en creación de rol');
            return [
                'status' => 'error',
                'message' => 'Solicitud inválida o expirada, por favor recargue la página e intente nuevamente'
            ];
        }
        
        // Sanitizar datos
        $data = $this->security->sanitizeInput($data);
        
        // Validar datos
        if (empty($data['rol']) || strlen($data['rol']) < 3) {
            return [
                'status' => 'error', 
                'message' => 'El nombre del rol debe tener al menos 3 caracteres'
            ];
        }
        
        // Validar que el nombre del rol contenga solo caracteres alfanuméricos y espacios
        if (!preg_match('/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s]+$/', $data['rol'])) {
            return [
                'status' => 'error', 
                'message' => 'El nombre del rol solo puede contener letras, números y espacios'
            ];
        }
        
        // Preparar datos del rol
        $rolData = [
            'rol' => trim($data['rol']),
            'fyh_creacion' => date('Y-m-d H:i:s'),
            'fyh_actualizacion' => date('Y-m-d H:i:s')
        ];
        
        try {
            // Guardar rol
            $result = $this->rolModel->createRol($rolData);
            
            if (is_numeric($result)) {
                // Registrar actividad en el log
                error_log(date('Y-m-d H:i:s') . " - Usuario " . $_SESSION['id_usuario'] . " creó el rol " . $rolData['rol'] . " (ID: $result)");
                
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
        } catch (Exception $e) {
            error_log("Error en RolController::store: " . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Error al crear el rol'
            ];
        }
    }
    
    /**
     * Obtener rol por ID para mostrar o editar
     * @param int $id ID del rol
     * @return array Datos del rol
     */
    public function show($id) {
        // Verificar permisos
        if (!$this->checkAdminPermission()) {
            return [
                'status' => 'error',
                'message' => 'No tiene permisos para esta acción',
                'data' => null
            ];
        }
        
        // Validar ID
        $id = intval($id);
        if ($id <= 0) {
            return [
                'status' => 'error',
                'message' => 'ID de rol inválido',
                'data' => null
            ];
        }
        
        try {
            $rol = $this->rolModel->getById($id);
            
            if ($rol) {
                return [
                    'status' => 'success',
                    'data' => $rol,
                    'csrf_token' => $this->security->generateCSRFToken()
                ];
            } else {
                return [
                    'status' => 'error',
                    'message' => 'Rol no encontrado',
                    'data' => null
                ];
            }
        } catch (Exception $e) {
            error_log("Error en RolController::show: " . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Error al obtener el rol',
                'data' => null
            ];
        }
    }
    
    /**
     * Actualizar datos de rol
     * @param int $id ID del rol
     * @param array $data Datos del formulario
     * @return array Resultado de la operación
     */
    public function update($id, $data) {
        // Verificar permisos
        if (!$this->checkAdminPermission()) {
            return [
                'status' => 'error',
                'message' => 'No tiene permisos para esta acción'
            ];
        }
        
        // Verificar token CSRF
        if (!isset($data['csrf_token']) || !$this->security->verifyCSRFToken($data['csrf_token'])) {
            $this->security->logSuspiciousActivity('csrf_attempt', 'Intento CSRF en actualización de rol');
            return [
                'status' => 'error',
                'message' => 'Solicitud inválida o expirada, por favor recargue la página e intente nuevamente'
            ];
        }
        
        // Validar ID
        $id = intval($id);
        if ($id <= 0) {
            return [
                'status' => 'error',
                'message' => 'ID de rol inválido'
            ];
        }
        
        // Sanitizar datos
        $data = $this->security->sanitizeInput($data);
        
        // Validar datos
        if (empty($data['rol']) || strlen($data['rol']) < 3) {
            return [
                'status' => 'error', 
                'message' => 'El nombre del rol debe tener al menos 3 caracteres'
            ];
        }
        
        // Validar que el nombre del rol contenga solo caracteres alfanuméricos y espacios
        if (!preg_match('/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s]+$/', $data['rol'])) {
            return [
                'status' => 'error', 
                'message' => 'El nombre del rol solo puede contener letras, números y espacios'
            ];
        }
        
        try {
            // Verificar que el rol existe
            $rol = $this->rolModel->getById($id);
            if (!$rol) {
                return [
                    'status' => 'error', 
                    'message' => 'Rol no encontrado'
                ];
            }
            
            // No permitir editar el rol con ID 1 (administrador)
            if ($id == 1 && strtolower(trim($data['rol'])) !== 'administrador') {
                return [
                    'status' => 'error', 
                    'message' => 'No se puede modificar el nombre del rol de administrador principal'
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
                // Registrar actividad en el log
                error_log(date('Y-m-d H:i:s') . " - Usuario " . $_SESSION['id_usuario'] . " actualizó el rol " . $rolData['rol'] . " (ID: $id)");
                
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
        } catch (Exception $e) {
            error_log("Error en RolController::update: " . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Error al actualizar el rol'
            ];
        }
    }
    
    /**
     * Eliminar rol con confirmación
     * @param int $id ID del rol
     * @param string $confirmToken Token de confirmación
     * @return array Resultado de la operación
     */
    public function destroy($id, $confirmToken) {
        // Verificar permisos
        if (!$this->checkAdminPermission()) {
            return [
                'status' => 'error',
                'message' => 'No tiene permisos para esta acción'
            ];
        }
        
        // Verificar token CSRF
        if (!$this->security->verifyCSRFToken($confirmToken)) {
            $this->security->logSuspiciousActivity('csrf_attempt', 'Intento CSRF en eliminación de rol');
            return [
                'status' => 'error',
                'message' => 'Solicitud inválida o expirada, por favor recargue la página e intente nuevamente'
            ];
        }
        
        // Validar ID
        $id = intval($id);
        if ($id <= 0) {
            return [
                'status' => 'error',
                'message' => 'ID de rol inválido'
            ];
        }
        
        try {
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
                $this->security->logSuspiciousActivity('protected_role_delete', 'Intento de eliminar rol de administrador');
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
                // Registrar actividad en el log
                error_log(date('Y-m-d H:i:s') . " - Usuario " . $_SESSION['id_usuario'] . " eliminó el rol " . $rol['rol'] . " (ID: $id)");
                
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
        } catch (Exception $e) {
            error_log("Error en RolController::destroy: " . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Error al eliminar el rol'
            ];
        }
    }
}