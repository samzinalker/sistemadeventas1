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
        
        // Crear directorio si no existe
        if (!file_exists($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
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
     * @return bool|string True si éxito, mensaje de error si falla
     */
    public function uploadFile($file, $fileName) {
        $filePath = $this->uploadDir . '/' . $fileName;
        
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
        $filePath = $this->uploadDir . '/' . $fileName;
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
        if (!file_exists($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
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
}