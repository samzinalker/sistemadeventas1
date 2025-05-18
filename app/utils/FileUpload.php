<?php
require_once __DIR__ . '/Security.php';

/**
 * Clase para manejo de subida de archivos
 */
class FileUpload {
    private $maxSize = 2097152; // 2MB
    private $allowedImageTypes = ['image/jpeg', 'image/png', 'image/gif'];
    private $uploadDir = '';
    private $security;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->security = new Security();
        
        // Configurar directorio de subida para imágenes de perfil
        $this->uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/sistemadeventas/public/images/perfiles/';
        
        // Crear el directorio si no existe
        if (!file_exists($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }
    
    /**
     * Sube una imagen de perfil con validaciones de seguridad
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
        
        // Validar el archivo usando getimagesize y finfo para evitar uploads maliciosos
        if (!$this->isValidImage($file['tmp_name'])) {
            $this->security->logSuspiciousActivity('invalid_image_upload', 'Intento de subir archivo inválido como imagen');
            return [
                'status' => 'error',
                'message' => 'El archivo no es una imagen válida'
            ];
        }
        
        // Generar nombre único para el archivo
        $timestamp = date('Y-m-d-H-i-s');
        $uniqueId = uniqid('_', true);
        $extension = $this->getSecureExtension($file);
        $filename = $timestamp . $uniqueId . '.' . $extension;
        
        // Ruta completa donde se guardará el archivo
        $uploadPath = $this->uploadDir . $filename;
        
        // Intentar mover el archivo subido
        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            // Verificar si la imagen contiene código malicioso después de subir
            if (!$this->isImageSafe($uploadPath)) {
                unlink($uploadPath); // Eliminar archivo sospechoso
                $this->security->logSuspiciousActivity('malicious_image_upload', 'Imagen con contenido sospechoso: ' . $filename);
                return [
                    'status' => 'error',
                    'message' => 'La imagen contiene datos sospechosos y ha sido rechazada'
                ];
            }
            
            // Redimensionar la imagen si es muy grande (opcional)
            $this->resizeImageIfNeeded($uploadPath);
            
            return [
                'status' => 'success',
                'message' => 'Imagen subida correctamente',
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
     * Verifica si un archivo es una imagen válida
     * @param string $filepath Ruta del archivo
     * @return bool True si es una imagen válida
     */
    private function isValidImage($filepath) {
        // Verificar con getimagesize
        $imageInfo = @getimagesize($filepath);
        if ($imageInfo === false) {
            return false;
        }
        
        // Verificar tipo MIME con finfo
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($filepath);
        
        return in_array($mime, $this->allowedImageTypes);
    }
    
    /**
     * Verifica si una imagen no contiene código malicioso
     * @param string $filepath Ruta del archivo
     * @return bool True si la imagen es segura
     */
    private function isImageSafe($filepath) {
        // Leer los primeros bytes del archivo para verificar que solo contenga datos de imagen
        $handle = fopen($filepath, 'r');
        $content = fread($handle, 8192); // Leer primeros 8KB
        fclose($handle);
        
        // Verificar por scripts PHP
        if (preg_match('/<\?php|<\?=|<script|<\?/', $content)) {
            return false;
        }
        
        // Verificar por técnicas de ocultación de código
        $suspiciousPatterns = [
            'eval\s*\(', 'base64_decode\s*\(', 'gzinflate\s*\(',
            'str_rot13\s*\(', 'preg_replace\s*\(.+\/e'
        ];
        
        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match('/' . $pattern . '/', $content)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Obtiene la extensión de archivo de forma segura
     * @param array $file Información del archivo
     * @return string Extensión del archivo
     */
    private function getSecureExtension($file) {
        // Obtener extensión basada en el tipo MIME
        $mimeToExt = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif'
        ];
        
        // Validar con finfo
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        
        if (isset($mimeToExt[$mime])) {
            return $mimeToExt[$mime];
        }
        
        // Fallback a la extensión original (sanitizada)
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        return strtolower($this->security->sanitizeFilename($extension));
    }
    
    /**
     * Redimensiona una imagen si supera un tamaño máximo
     * @param string $filepath Ruta de la imagen
     * @return bool True si se redimensionó correctamente
     */
    private function resizeImageIfNeeded($filepath) {
        // Verificar si la extensión GD está disponible
        if (!extension_loaded('gd') || !function_exists('imagecreatetruecolor')) {
            return false;
        }
        
        // Dimensiones máximas
        $maxWidth = 1024;
        $maxHeight = 1024;
        
        // Obtener dimensiones actuales
        list($width, $height, $type) = getimagesize($filepath);
        
        // Si la imagen es más pequeña que las dimensiones máximas, no hacer nada
        if ($width <= $maxWidth && $height <= $maxHeight) {
            return true;
        }
        
        // Calcular nuevas dimensiones manteniendo la proporción
        if ($width > $height) {
            $newWidth = $maxWidth;
            $newHeight = intval($height * $maxWidth / $width);
        } else {
            $newHeight = $maxHeight;
            $newWidth = intval($width * $maxHeight / $height);
        }
        
        // Crear imagen redimensionada según el tipo
        $sourceImage = null;
        $destImage = imagecreatetruecolor($newWidth, $newHeight);
        
        // Mantener transparencia para PNG
        if ($type === IMAGETYPE_PNG) {
            imagealphablending($destImage, false);
            imagesavealpha($destImage, true);
            $transparent = imagecolorallocatealpha($destImage, 255, 255, 255, 127);
            imagefilledrectangle($destImage, 0, 0, $newWidth, $newHeight, $transparent);
        }
        
        // Cargar imagen según el tipo
        switch ($type) {
            case IMAGETYPE_JPEG:
                $sourceImage = imagecreatefromjpeg($filepath);
                break;
            case IMAGETYPE_PNG:
                $sourceImage = imagecreatefrompng($filepath);
                break;
            case IMAGETYPE_GIF:
                $sourceImage = imagecreatefromgif($filepath);
                break;
            default:
                return false;
        }
        
        // Redimensionar
        imagecopyresampled($destImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        
        // Guardar imagen redimensionada
        $result = false;
        switch ($type) {
            case IMAGETYPE_JPEG:
                $result = imagejpeg($destImage, $filepath, 90);
                break;
            case IMAGETYPE_PNG:
                $result = imagepng($destImage, $filepath, 9);
                break;
            case IMAGETYPE_GIF:
                $result = imagegif($destImage, $filepath);
                break;
        }
        
        // Liberar memoria
        imagedestroy($sourceImage);
        imagedestroy($destImage);
        
        return $result;
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