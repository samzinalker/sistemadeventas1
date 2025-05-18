<?php
require_once __DIR__ . '/../../models/UsuarioModel.php';
require_once __DIR__ . '/../../models/RolModel.php';
require_once __DIR__ . '/../../utils/FileUpload.php';

/**
 * Controlador para el módulo de usuarios
 */
class UsuarioController {
    private $usuarioModel;
    private $rolModel;
    private $fileUpload;
    
    /**
     * Constructor
     * @param PDO|null $pdo Conexión PDO opcional
     */
    public function __construct($pdo = null) {
        $this->usuarioModel = new UsuarioModel($pdo);
        $this->rolModel = new RolModel($pdo);
        $this->fileUpload = new FileUpload();
    }
    
    /**
     * Obtener todos los usuarios para listar
     * @return array Lista de usuarios
     */
    public function index() {
        return $this->usuarioModel->getAllWithRoles();
    }
    
    /**
     * Obtener datos para el formulario de creación
     * @return array Datos para el formulario
     */
    public function create() {
        return [
            'roles' => $this->rolModel->getAll()
        ];
    }
    
    /**
     * Almacenar un nuevo usuario
     * @param array $data Datos del formulario
     * @param array $files Archivos subidos
     * @return array Resultado de la operación
     */
    public function store($data, $files = []) {
        // Validar datos
        if (empty($data['nombres']) || empty($data['email']) || empty($data['password']) || empty($data['id_rol'])) {
            return [
                'status' => 'error', 
                'message' => 'Todos los campos son obligatorios'
            ];
        }
        
        // Verificar que la contraseña tenga al menos 6 caracteres
        if (strlen($data['password']) < 6) {
            return [
                'status' => 'error', 
                'message' => 'La contraseña debe tener al menos 6 caracteres'
            ];
        }
        
        // Procesar imagen si se ha subido
        $imagen_perfil = 'user_default.png';
        if (!empty($files['imagen']['name'])) {
            $resultado = $this->fileUpload->uploadProfileImage($files['imagen']);
            
            if ($resultado['status'] === 'success') {
                $imagen_perfil = $resultado['filename'];
            } else {
                return [
                    'status' => 'error', 
                    'message' => $resultado['message']
                ];
            }
        }
        
        // Preparar datos del usuario
        $userData = [
            'nombres' => $data['nombres'],
            'email' => $data['email'],
            'password_user' => $data['password'],
            'id_rol' => $data['id_rol'],
            'imagen_perfil' => $imagen_perfil,
            'token' => '',
            'fyh_creacion' => date('Y-m-d H:i:s'),
            'fyh_actualizacion' => date('Y-m-d H:i:s')
        ];
        
        // Guardar usuario
        $result = $this->usuarioModel->createUser($userData);
        
        if (is_numeric($result)) {
            return [
                'status' => 'success',
                'message' => 'Usuario creado correctamente',
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
     * Obtener usuario por ID para mostrar
     * @param int $id ID del usuario
     * @return array|false Datos del usuario o false
     */
    public function show($id) {
        return $this->usuarioModel->getByIdWithRole($id);
    }
    
    /**
     * Obtener datos para el formulario de edición
     * @param int $id ID del usuario
     * @return array Datos para el formulario
     */
    public function edit($id) {
        return [
            'usuario' => $this->usuarioModel->getById($id),
            'roles' => $this->rolModel->getAll()
        ];
    }
    
    /**
     * Actualizar datos de usuario
     * @param int $id ID del usuario
     * @param array $data Datos del formulario
     * @param array $files Archivos subidos
     * @return array Resultado de la operación
     */
    public function update($id, $data, $files = []) {
        // Validar datos
        if (empty($data['nombres']) || empty($data['email']) || empty($data['id_rol'])) {
            return [
                'status' => 'error', 
                'message' => 'Todos los campos son obligatorios'
            ];
        }
        
        // Verificar que el usuario existe
        $usuario = $this->usuarioModel->getById($id);
        if (!$usuario) {
            return [
                'status' => 'error', 
                'message' => 'Usuario no encontrado'
            ];
        }
        
        // Procesar imagen si se ha subido
        if (!empty($files['imagen']['name'])) {
            $resultado = $this->fileUpload->uploadProfileImage($files['imagen']);
            
            if ($resultado['status'] === 'success') {
                // Actualizar imagen de perfil
                $this->usuarioModel->updateProfileImage($id, $resultado['filename']);
            } else {
                return [
                    'status' => 'error', 
                    'message' => $resultado['message']
                ];
            }
        }
        
        // Preparar datos de actualización
        $userData = [
            'nombres' => $data['nombres'],
            'email' => $data['email'],
            'id_rol' => $data['id_rol'],
            'fyh_actualizacion' => date('Y-m-d H:i:s')
        ];
        
        // Actualizar contraseña si se proporciona
        if (!empty($data['password'])) {
            if (strlen($data['password']) < 6) {
                return [
                    'status' => 'error', 
                    'message' => 'La contraseña debe tener al menos 6 caracteres'
                ];
            }
            
            $result = $this->usuarioModel->updatePassword($id, $data['password']);
            if ($result !== true) {
                return [
                    'status' => 'error', 
                    'message' => 'Error al actualizar la contraseña'
                ];
            }
        }
        
        // Actualizar usuario
        $result = $this->usuarioModel->update($id, $userData);
        
        if ($result === true) {
            return [
                'status' => 'success',
                'message' => 'Usuario actualizado correctamente'
            ];
        } else {
            return [
                'status' => 'error',
                'message' => is_string($result) ? $result : 'Error al actualizar el usuario'
            ];
        }
    }
    
    /**
     * Eliminar usuario
     * @param int $id ID del usuario
     * @return array Resultado de la operación
     */
    public function destroy($id) {
        // Verificar que el usuario existe
        $usuario = $this->usuarioModel->getById($id);
        if (!$usuario) {
            return [
                'status' => 'error', 
                'message' => 'Usuario no encontrado'
            ];
        }
        
        // No permitir eliminar el usuario con ID 1 (administrador principal)
        if ($id == 1) {
            return [
                'status' => 'error', 
                'message' => 'No se puede eliminar el usuario administrador principal'
            ];
        }
        
        // Eliminar usuario
        $result = $this->usuarioModel->delete($id);
        
        if ($result === true) {
            return [
                'status' => 'success',
                'message' => 'Usuario eliminado correctamente'
            ];
        } else {
            return [
                'status' => 'error',
                'message' => is_string($result) ? $result : 'Error al eliminar el usuario'
            ];
        }
    }
}