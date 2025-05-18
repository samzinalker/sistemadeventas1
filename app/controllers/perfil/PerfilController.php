<?php
require_once __DIR__ . '/../../models/UsuarioModel.php';
require_once __DIR__ . '/../../utils/FileUpload.php';

/**
 * Controlador para el módulo de perfil de usuario
 */
class PerfilController {
    private $usuarioModel;
    private $fileUpload;
    
    /**
     * Constructor
     * @param PDO|null $pdo Conexión PDO opcional
     */
    public function __construct($pdo = null) {
        $this->usuarioModel = new UsuarioModel($pdo);
        $this->fileUpload = new FileUpload();
    }
    
    /**
     * Obtener datos del perfil del usuario actual
     * @param int $userId ID del usuario
     * @return array|false Datos del perfil o false
     */
    public function getUserProfile($userId) {
        if (!$userId) {
            return false;
        }
        
        return $this->usuarioModel->getByIdWithRole($userId);
    }
    
    /**
     * Actualizar datos personales
     * @param int $userId ID del usuario
     * @param array $data Datos a actualizar
     * @return array Resultado con status y mensaje
     */
    public function updatePersonalData($userId, $data) {
        // Validar datos
        if (empty($data['nombres']) || empty($data['email'])) {
            return [
                'status' => 'error',
                'message' => 'El nombre y email son obligatorios'
            ];
        }
        
        // Verificar que el email no esté en uso por otro usuario
        $userByEmail = $this->usuarioModel->findByEmail($data['email']);
        if ($userByEmail && $userByEmail['id_usuario'] != $userId) {
            return [
                'status' => 'error',
                'message' => 'El email ya está en uso por otro usuario'
            ];
        }
        
        // Actualizar datos
        $userData = [
            'nombres' => $data['nombres'],
            'email' => $data['email'],
            'fyh_actualizacion' => date('Y-m-d H:i:s')
        ];
        
        $result = $this->usuarioModel->update($userId, $userData);
        
        if ($result === true) {
            // Actualizar sesión si corresponde
            session_start();
            if (isset($_SESSION['nombres']) && isset($_SESSION['sesion_email'])) {
                $_SESSION['nombres'] = $data['nombres'];
                $_SESSION['sesion_email'] = $data['email'];
            }
            
            return [
                'status' => 'success',
                'message' => 'Datos personales actualizados correctamente'
            ];
        } else {
            return [
                'status' => 'error',
                'message' => is_string($result) ? $result : 'Error al actualizar los datos personales'
            ];
        }
    }
    
    /**
     * Actualizar contraseña
     * @param int $userId ID del usuario
     * @param array $data Datos con contraseñas
     * @return array Resultado con status y mensaje
     */
    public function updatePassword($userId, $data) {
        // Validar datos
        if (empty($data['password_actual']) || empty($data['password_nueva']) || empty($data['password_confirmar'])) {
            return [
                'status' => 'error',
                'message' => 'Todos los campos son obligatorios'
            ];
        }
        
        // Verificar que la nueva contraseña tenga al menos 6 caracteres
        if (strlen($data['password_nueva']) < 6) {
            return [
                'status' => 'error',
                'message' => 'La contraseña debe tener al menos 6 caracteres'
            ];
        }
        
        // Verificar que las contraseñas nuevas coincidan
        if ($data['password_nueva'] !== $data['password_confirmar']) {
            return [
                'status' => 'error',
                'message' => 'Las contraseñas no coinciden'
            ];
        }
        
        // Verificar la contraseña actual
        if (!$this->usuarioModel->verifyPassword($userId, $data['password_actual'])) {
            return [
                'status' => 'error',
                'message' => 'La contraseña actual es incorrecta'
            ];
        }
        
        // Actualizar contraseña
        $result = $this->usuarioModel->updatePassword($userId, $data['password_nueva']);
        
        if ($result === true) {
            return [
                'status' => 'success',
                'message' => 'Contraseña actualizada correctamente'
            ];
        } else {
            return [
                'status' => 'error',
                'message' => is_string($result) ? $result : 'Error al actualizar la contraseña'
            ];
        }
    }
    
    /**
     * Actualizar imagen de perfil
     * @param int $userId ID del usuario
     * @param array $file Archivo de imagen ($_FILES['imagen'])
     * @return array Resultado con status y mensaje
     */
    public function updateProfileImage($userId, $file) {
        // Verificar que se ha subido una imagen
        if (empty($file['name'])) {
            return [
                'status' => 'error',
                'message' => 'No se ha seleccionado ninguna imagen'
            ];
        }
        
        // Procesar la imagen
        $resultado = $this->fileUpload->uploadProfileImage($file);
        
        if ($resultado['status'] !== 'success') {
            return [
                'status' => 'error',
                'message' => $resultado['message']
            ];
        }
        
        // Actualizar imagen de perfil
        $result = $this->usuarioModel->updateProfileImage($userId, $resultado['filename']);
        
        if ($result === true) {
            return [
                'status' => 'success',
                'message' => 'Imagen de perfil actualizada correctamente',
                'filename' => $resultado['filename']
            ];
        } else {
            return [
                'status' => 'error',
                'message' => is_string($result) ? $result : 'Error al actualizar la imagen de perfil'
            ];
        }
    }
}