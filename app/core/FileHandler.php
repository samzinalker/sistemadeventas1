<?php
/**
 * Clase para manejo de archivos
 */
class FileHandler {
    private $allowedImageTypes = ['image/jpeg', 'image/png', 'image/gif'];
    private $maxFileSize = 2097152; // 2MB
    private $uploadDir;
    
    /**
     * Constructor
     * @param string $uploadDir Directorio de subida (sin trailing slash)
     */
    public function __construct($uploadDir = null) {
        global $URL;
        if ($uploadDir === null) {
            // Directorio predeterminado
            $this->uploadDir = $_SERVER['DOCUMENT_ROOT'] . rtrim($URL, '/') . '/public/images/uploads';
        } else {
            $this->uploadDir = $uploadDir;
        }
        
        // Crear directorio de forma recursiva
        $this->createDirectoryIfNotExists($this->uploadDir);
    }
    
    /**
     * Crea un directorio recursivamente si no existe
     * @param string $path Ruta del directorio a crear
     * @return bool True si se creó o ya existía, False si falló
     */
    private function createDirectoryIfNotExists($path) {
        if (file_exists($path)) {
            return true;
        }
        
        // Obtener ruta absoluta y eliminar cualquier barra extra
        $path = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);
        
        // Intentar crear el directorio y todos sus padres
        try {
            // Crear la estructura completa de directorios
            if (!mkdir($path, 0755, true)) {
                error_log("No se pudo crear el directorio: " . $path);
                return false;
            }
            return true;
        } catch (Exception $e) {
            error_log("Error al crear directorio: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Valida un archivo de imagen
     * @param array $file Archivo ($_FILES['campo'])
     * @param bool $required Si es obligatorio
     * @return array [isValid, message, fileName]
     */
    public function validateImage($file, $required = false) {
        // Si no hay archivo y no es requerido
        if (empty($file['name']) && !$required) {
            return [true, "", ""];
        }
        
        // Si no hay archivo pero es requerido
        if (empty($file['name']) && $required) {
            return [false, "La imagen es obligatoria", ""];
        }
        
        // Validar tipo de archivo
        if (!in_array($file['type'], $this->allowedImageTypes)) {
            return [false, "El archivo debe ser una imagen (JPG, PNG o GIF)", ""];
        }
        
        // Validar tamaño
        if ($file['size'] > $this->maxFileSize) {
            return [false, "La imagen no debe superar los 2MB", ""];
        }
        
        // Generar nombre único
        $fileName = date('Y-m-d-H-i-s') . '__' . $file['name'];
        
        return [true, "", $fileName];
    }
    
    /**
     * Sube un archivo al servidor
     * @param array $file Archivo ($_FILES['campo'])
     * @param string $fileName Nombre del archivo
     * @return bool|string True si éxito, mensaje error si falla
     */
    public function uploadFile($file, $fileName) {
        // Asegurarse que el directorio existe
        if (!$this->createDirectoryIfNotExists($this->uploadDir)) {
            return "No se pudo crear el directorio para guardar la imagen";
        }
        
        $filePath = rtrim($this->uploadDir, '/\\') . DIRECTORY_SEPARATOR . $fileName;
        
        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            return "Error al subir el archivo";
        }
        
        return true;
    }
    
    /**
     * Elimina un archivo
     * @param string $fileName Nombre del archivo
     * @return bool True si se eliminó, false en caso contrario
     */
    public function deleteFile($fileName) {
        $filePath = rtrim($this->uploadDir, '/\\') . DIRECTORY_SEPARATOR . $fileName;
        if (file_exists($filePath)) {
            return unlink($filePath);
        }
        return false;
    }
    
    /**
     * Configura el directorio de subida
     * @param string $dir Directorio de subida
     */
    public function setUploadDir($dir) {
        $this->uploadDir = $dir;
        $this->createDirectoryIfNotExists($this->uploadDir);
    }
    
    /**
     * Configura el tamaño máximo de archivo
     * @param int $sizeInBytes Tamaño en bytes
     */
    public function setMaxFileSize($sizeInBytes) {
        $this->maxFileSize = $sizeInBytes;
    }
    
    /**
     * Establece los tipos de imagen permitidos
     * @param array $types Array de tipos MIME
     */
    public function setAllowedImageTypes($types) {
        $this->allowedImageTypes = $types;
    }
    
    /**
     * Obtiene el directorio de subida actual
     * @return string Directorio de subida
     */
    public function getUploadDir() {
        return $this->uploadDir;
    }
}