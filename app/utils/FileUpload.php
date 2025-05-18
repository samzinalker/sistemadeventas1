<?php
/**
 * Clase para manejo de subida de archivos
 */
class FileUpload {
    private $maxSize = 2097152; // 2MB
    private $allowedImageTypes = ['image/jpeg', 'image/png', 'image/gif'];
    private $uploadDir = '';
    
    /**
     * Constructor
     */
    public function __construct() {
        // Configurar directorio de subida para imágenes de perfil
        $this->uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/sistemadeventas/public/images/perfiles/';
        
        // Crear el directorio si no existe
        if (!file_exists($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }
    
    /**
     * Sube una imagen de perfil
     * @param array $file Archivo del formulario ($_FILES['campo'])
     * @return array Resultado de la operación con status y mensaje
     */
    public function uploadProfileImage($file) {
        // Verificar errores de subida
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return $this->getUploadErrorMessage($file['error']);
        }
        
        // Verificar tamaño
        if ($file['size'] > $this->maxSize) {
            return [
                'status' => 'error',
                'message' => 'La imagen no debe superar los 2MB'
            ];
        }
        
        // Verificar tipo de archivo
        if (!in_array($file['type'], $this->allowedImageTypes)) {
            return [
                'status' => 'error',
                'message' => 'Solo se permiten imágenes en formato JPG, PNG y GIF'
            ];
        }
        
        // Generar nombre único para el archivo
        $timestamp = date('Y-m-d-H-i-s');
        $uniqueId = uniqid('_', true);
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = $timestamp . $uniqueId . '.' . $extension;
        
        // Ruta completa donde se guardará el archivo
        $uploadPath = $this->uploadDir . $filename;
        
        // Intentar mover el archivo subido
        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            return [
                'status' => 'success',
                'message' => 'Archivo subido correctamente',
                'filename' => $filename
            ];
        } else {
            return [
                'status' => 'error',
                'message' => 'Error al guardar el archivo. Verifique los permisos del directorio.'
            ];
        }
    }
    
    /**
     * Obtiene un mensaje de error basado en el código de error de PHP
     * @param int $errorCode Código de error de PHP
     * @return array Resultado con status y mensaje
     */
    private function getUploadErrorMessage($errorCode) {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
                return [
                    'status' => 'error',
                    'message' => 'El archivo excede el tamaño máximo permitido por el servidor'
                ];
            case UPLOAD_ERR_FORM_SIZE:
                return [
                    'status' => 'error',
                    'message' => 'El archivo excede el tamaño máximo permitido por el formulario'
                ];
            case UPLOAD_ERR_PARTIAL:
                return [
                    'status' => 'error',
                    'message' => 'El archivo solo fue subido parcialmente'
                ];
            case UPLOAD_ERR_NO_FILE:
                return [
                    'status' => 'error',
                    'message' => 'No se seleccionó ningún archivo'
                ];
            case UPLOAD_ERR_NO_TMP_DIR:
                return [
                    'status' => 'error',
                    'message' => 'No se encuentra el directorio temporal del servidor'
                ];
            case UPLOAD_ERR_CANT_WRITE:
                return [
                    'status' => 'error',
                    'message' => 'Error al escribir el archivo en el disco'
                ];
            case UPLOAD_ERR_EXTENSION:
                return [
                    'status' => 'error',
                    'message' => 'La subida del archivo fue detenida por una extensión de PHP'
                ];
            default:
                return [
                    'status' => 'error',
                    'message' => 'Error desconocido al subir el archivo'
                ];
        }
    }
}