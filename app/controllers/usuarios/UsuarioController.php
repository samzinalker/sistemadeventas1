<?php
require_once __DIR__ . '/../../models/UsuarioModel.php';
require_once __DIR__ . '/../../models/RolModel.php';
require_once __DIR__ . '/../../utils/FileUpload.php';
require_once __DIR__ . '/../../utils/Security.php';

/**
 * Controlador para el módulo de usuarios
 */
class UsuarioController {
    private $usuarioModel;
    private $rolModel;
    private $fileUpload;
    private $security;
    
    /**
     * Constructor
     * @param PDO|null $pdo Conexión PDO opcional
     */
    public function __construct($pdo = null) {
        $this->usuarioModel = new UsuarioModel($pdo);
        $this->rolModel = new RolModel($pdo);
        $this->fileUpload = new FileUpload();
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
                'Intento de acceso a módulo de usuarios sin permisos'
            );
            return false;
        }
        return true;
    }
    
    /**
     * Obtener todos los usuarios para listar
     * @return array Lista de usuarios
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
        
        $usuarios = $this->usuarioModel->getAllWithRoles();
        return [
            'status' => 'success',
            'data' => $usuarios
        ];
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
                'roles' => $this->rolModel->getAll(),
                'csrf_token' => $this->security->generateCSRFToken()
            ]
        ];
    }
    
    /**
     * Almacenar un nuevo usuario
     * @param array $data Datos del formulario
     * @param array $files Archivos subidos
     * @return array Resultado de la operación
     */
    public function store($data, $files = []) {
        // Verificar permisos
        if (!$this->checkAdminPermission()) {
            return [
                'status' => 'error',
                'message' => 'No tiene permisos para esta acción'
            ];
        }
        
        // Verificar token CSRF
        if (!isset($data['csrf_token']) || !$this->security->verifyCSRFToken($data['csrf_token'])) {
            $this->security->logSuspiciousActivity('csrf_attempt', 'Intento CSRF en creación de usuario');
            return [
                'status' => 'error',
                'message' => 'Solicitud inválida o expirada, por favor recargue la página e intente nuevamente'
            ];
        }
        
        // Sanitizar datos
        $data = $this->security->sanitizeInput($data);
        
        // Validar datos
        $validationErrors = $this->validateUserData($data);
        if (!empty($validationErrors)) {
            return [
                'status' => 'error', 
                'message' => $validationErrors[0], // Mostrar el primer error
                'errors' => $validationErrors
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
     * @return array Resultado de la operación
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
                'message' => 'ID de usuario inválido',
                'data' => null
            ];
        }
        
        $usuario = $this->usuarioModel->getByIdWithRole($id);
        
        if ($usuario) {
            return [
                'status' => 'success',
                'data' => $usuario,
                'csrf_token' => $this->security->generateCSRFToken()
            ];
        } else {
            return [
                'status' => 'error',
                'message' => 'Usuario no encontrado',
                'data' => null
            ];
        }
    }
    
    /**
     * Obtener datos para el formulario de edición
     * @param int $id ID del usuario
     * @return array Datos para el formulario
     */
    public function edit($id) {
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
                'message' => 'ID de usuario inválido',
                'data' => null
            ];
        }
        
        $usuario = $this->usuarioModel->getById($id);
        $roles = $this->rolModel->getAll();
        
        if ($usuario) {
            return [
                'status' => 'success',
                'data' => [
                    'usuario' => $usuario,
                    'roles' => $roles,
                    'csrf_token' => $this->security->generateCSRFToken()
                ]
            ];
        } else {
            return [
                'status' => 'error',
                'message' => 'Usuario no encontrado',
                'data' => null
            ];
        }
    }
    
    /**
     * Actualizar datos de usuario
     * @param int $id ID del usuario
     * @param array $data Datos del formulario
     * @param array $files Archivos subidos
     * @return array Resultado de la operación
     */
    public function update($id, $data, $files = []) {
        // Verificar permisos
        if (!$this->checkAdminPermission()) {
            return [
                'status' => 'error',
                'message' => 'No tiene permisos para esta acción'
            ];
        }
        
        // Verificar token CSRF
        if (!isset($data['csrf_token']) || !$this->security->verifyCSRFToken($data['csrf_token'])) {
            $this->security->logSuspiciousActivity('csrf_attempt', 'Intento CSRF en actualización de usuario');
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
                'message' => 'ID de usuario inválido'
            ];
        }
        
        // Sanitizar datos
        $data = $this->security->sanitizeInput($data);
        
        // Validar datos básicos (sin contraseña)
        $validationErrors = $this->validateUserDataForUpdate($data);
        if (!empty($validationErrors)) {
            return [
                'status' => 'error', 
                'message' => $validationErrors[0], // Mostrar el primer error
                'errors' => $validationErrors
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
        
        // Verificar email
        $usuarioExistente = $this->usuarioModel->findByEmail($data['email']);
        if ($usuarioExistente && $usuarioExistente['id_usuario'] != $id) {
            return [
                'status' => 'error',
                'message' => 'El email ya está en uso por otro usuario'
            ];
        }
        
        // Procesar imagen si se ha subido
        if (!empty($files['imagen']['name'])) {
            $resultado = $this->fileUpload->uploadProfileImage($files['imagen']);
            
            if ($resultado['status'] === 'success') {
                // Actualizar imagen de perfil
                $this->usuarioModel->updateProfileImage($id, $resultado['filename']);
                
                // Eliminar imagen anterior si no es la predeterminada
                if ($usuario['imagen_perfil'] != 'user_default.png') {
                    $rutaImagenAnterior = $_SERVER['DOCUMENT_ROOT'] . '/sistemadeventas/public/images/perfiles/' . $usuario['imagen_perfil'];
                    if (file_exists($rutaImagenAnterior)) {
                        unlink($rutaImagenAnterior);
                    }
                }
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
            if (strlen($data['password']) < 8) {
                return [
                    'status' => 'error', 
                    'message' => 'La contraseña debe tener al menos 8 caracteres'
                ];
            }
            
            if (!$this->security->isStrongPassword($data['password'])) {
                return [
                    'status' => 'error', 
                    'message' => 'La contraseña debe incluir números, letras mayúsculas y minúsculas, y al menos un carácter especial'
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
     * Eliminar usuario con confirmación
     * @param int $id ID del usuario
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
        
        