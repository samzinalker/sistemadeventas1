<?php
/**
 * Clase para manejar operaciones con archivos
 */
class FileHandler {
    /**
     * Directorio base para guardar imágenes de perfil
     */
    private static $profileImageDir = '../public/images/perfiles/';
    
    /**
     * Tipos de archivo permitidos para imágenes
     */
    private static $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    
    /**
     * Tamaño máximo permitido (2MB)
     */
    private static $maxSize = 2097152; // 2MB en bytes
    
    /**
     * Validar archivo de imagen
     * 
     * @param array $file Archivo de $_FILES
     * @return array [esValido, mensaje]
     */
    public static function validateImage($file) {
        // Verificar si se subió un archivo
        if ($file['size'] === 0) {
            return [false, "No se ha seleccionado ningún archivo."];
        }
        
        // Verificar el tipo de archivo
        if (!in_array($file['type'], self::$allowedTypes)) {
            return [false, "El tipo de archivo no está permitido. Use JPEG, PNG o GIF."];
        }
        
        // Verificar el tamaño del archivo
        if ($file['size'] > self::$maxSize) {
            return [false, "El archivo es demasiado grande. El tamaño máximo es de 2MB."];
        }
        
        return [true, ""];
    }
    
    /**
     * Guardar imagen de perfil
     * 
     * @param array $file Archivo de $_FILES
     * @return array [éxito, nombre_archivo, mensaje]
     */
    public static function saveProfileImage($file) {
        // Validar imagen
        list($isValid, $message) = self::validateImage($file);
        if (!$isValid) {
            return [false, "", $message];
        }
        
        // Generar nombre único para el archivo
        $fileName = date('Y-m-d-H-i-s') . '_' . uniqid() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
        $targetPath = self::$profileImageDir . $fileName;
        
        // Mover el archivo al directorio destino
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            return [true, $fileName, "Imagen guardada correctamente"];
        } else {
            return [false, "", "Error al guardar la imagen"];
        }
    }
    
    /**
     * Eliminar imagen de perfil
     * 
     * @param string $fileName Nombre del archivo a eliminar
     * @return bool Éxito de la operación
     */
    public static function deleteProfileImage($fileName) {
        // No eliminar la imagen por defecto
        if ($fileName === 'user_default.png') {
            return true;
        }
        
        $filePath = self::$profileImageDir . $fileName;
        if (file_exists($filePath) && is_file($filePath)) {
            return unlink($filePath);
        }
        return false;
    }
}