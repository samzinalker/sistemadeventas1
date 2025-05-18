<?php
/**
 * Clase para validación de datos en formularios y otras entradas
 * @author Sistema de Ventas
 */
class Validator {
    /**
     * Almacena los errores de validación
     * @var array
     */
    private $errors = [];
    
    /**
     * Valida que los campos requeridos existan y no estén vacíos
     * @param array $data Datos a validar
     * @param array $fields Campos requeridos
     * @return bool True si todos los campos son válidos
     */
    public function required($data, $fields) {
        foreach ($fields as $field) {
            if (!isset($data[$field]) || trim($data[$field]) === '') {
                $this->errors[$field] = "El campo '$field' es obligatorio";
                return false;
            }
        }
        return true;
    }
    
    /**
     * Valida que un valor sea numérico
     * @param mixed $value Valor a validar
     * @param string $field Nombre del campo
     * @return bool True si es válido
     */
    public function numeric($value, $field) {
        if (!is_numeric($value)) {
            $this->errors[$field] = "El campo '$field' debe ser un número";
            return false;
        }
        return true;
    }
    
    /**
     * Valida que un valor sea mayor o igual a un mínimo
     * @param mixed $value Valor a validar
     * @param mixed $min Valor mínimo
     * @param string $field Nombre del campo
     * @return bool True si es válido
     */
    public function min($value, $min, $field) {
        if ($value < $min) {
            $this->errors[$field] = "El campo '$field' debe ser al menos $min";
            return false;
        }
        return true;
    }
    
    /**
     * Valida que un valor sea menor o igual a un máximo
     * @param mixed $value Valor a validar
     * @param mixed $max Valor máximo
     * @param string $field Nombre del campo
     * @return bool True si es válido
     */
    public function max($value, $max, $field) {
        if ($value > $max) {
            $this->errors[$field] = "El campo '$field' debe ser como máximo $max";
            return false;
        }
        return true;
    }
    
    /**
     * Valida que un valor esté entre un rango
     * @param mixed $value Valor a validar
     * @param mixed $min Valor mínimo
     * @param mixed $max Valor máximo
     * @param string $field Nombre del campo
     * @return bool True si es válido
     */
    public function range($value, $min, $max, $field) {
        if ($value < $min || $value > $max) {
            $this->errors[$field] = "El campo '$field' debe estar entre $min y $max";
            return false;
        }
        return true;
    }
    
    /**
     * Valida una fecha en formato Y-m-d
     * @param string $date Fecha a validar
     * @param string $field Nombre del campo
     * @return bool True si es válido
     */
    public function date($date, $field) {
        $format = 'Y-m-d';
        $dateObj = DateTime::createFromFormat($format, $date);
        if (!$dateObj || $dateObj->format($format) !== $date) {
            $this->errors[$field] = "El campo '$field' debe tener formato de fecha válido (YYYY-MM-DD)";
            return false;
        }
        return true;
    }
    
    /**
     * Valida que un valor tenga una longitud exacta
     * @param string $value Valor a validar
     * @param int $length Longitud requerida
     * @param string $field Nombre del campo
     * @return bool True si es válido
     */
    public function length($value, $length, $field) {
        if (strlen($value) !== $length) {
            $this->errors[$field] = "El campo '$field' debe tener exactamente $length caracteres";
            return false;
        }
        return true;
    }
    
    /**
     * Valida que un valor tenga una longitud mínima
     * @param string $value Valor a validar
     * @param int $min Longitud mínima
     * @param string $field Nombre del campo
     * @return bool True si es válido
     */
    public function minLength($value, $min, $field) {
        if (strlen($value) < $min) {
            $this->errors[$field] = "El campo '$field' debe tener al menos $min caracteres";
            return false;
        }
        return true;
    }
    
    /**
     * Valida que un valor tenga una longitud máxima
     * @param string $value Valor a validar
     * @param int $max Longitud máxima
     * @param string $field Nombre del campo
     * @return bool True si es válido
     */
    public function maxLength($value, $max, $field) {
        if (strlen($value) > $max) {
            $this->errors[$field] = "El campo '$field' debe tener como máximo $max caracteres";
            return false;
        }
        return true;
    }
    
    /**
     * Valida que un valor sea un email válido
     * @param string $value Valor a validar
     * @param string $field Nombre del campo
     * @return bool True si es válido
     */
    public function email($value, $field) {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = "El campo '$field' debe ser un email válido";
            return false;
        }
        return true;
    }
    
    /**
     * Valida que un valor coincida con una expresión regular
     * @param string $value Valor a validar
     * @param string $pattern Patrón de expresión regular
     * @param string $field Nombre del campo
     * @param string $message Mensaje de error personalizado (opcional)
     * @return bool True si es válido
     */
    public function pattern($value, $pattern, $field, $message = null) {
        if (!preg_match($pattern, $value)) {
            $this->errors[$field] = $message ?? "El campo '$field' no tiene el formato correcto";
            return false;
        }
        return true;
    }
    
    /**
     * Valida que un valor sea igual a otro
     * @param mixed $value1 Primer valor
     * @param mixed $value2 Segundo valor
     * @param string $field Nombre del campo
     * @param string $message Mensaje de error personalizado (opcional)
     * @return bool True si son iguales
     */
    public function equals($value1, $value2, $field, $message = null) {
        if ($value1 !== $value2) {
            $this->errors[$field] = $message ?? "El campo '$field' no coincide";
            return false;
        }
        return true;
    }
    
    /**
     * Valida que un valor sea un número de teléfono válido
     * @param string $value Valor a validar
     * @param string $field Nombre del campo
     * @return bool True si es válido
     */
    public function phone($value, $field) {
        // Patrón básico para teléfonos (se puede personalizar según país)
        $pattern = '/^[0-9]{7,15}$/';
        if (!preg_match($pattern, preg_replace('/[^0-9]/', '', $value))) {
            $this->errors[$field] = "El campo '$field' debe ser un número de teléfono válido";
            return false;
        }
        return true;
    }
    
    /**
     * Valida que un valor sea un número entero
     * @param mixed $value Valor a validar
     * @param string $field Nombre del campo
     * @return bool True si es válido
     */
    public function integer($value, $field) {
        if (!filter_var($value, FILTER_VALIDATE_INT)) {
            $this->errors[$field] = "El campo '$field' debe ser un número entero";
            return false;
        }
        return true;
    }
    
    /**
     * Valida que un valor sea un número decimal
     * @param mixed $value Valor a validar
     * @param string $field Nombre del campo
     * @return bool True si es válido
     */
    public function decimal($value, $field) {
        if (!is_numeric($value)) {
            $this->errors[$field] = "El campo '$field' debe ser un número decimal";
            return false;
        }
        return true;
    }
    
    /**
     * Valida que un valor esté en una lista de opciones permitidas
     * @param mixed $value Valor a validar
     * @param array $options Opciones permitidas
     * @param string $field Nombre del campo
     * @return bool True si es válido
     */
    public function inList($value, $options, $field) {
        if (!in_array($value, $options)) {
            $this->errors[$field] = "El campo '$field' debe ser uno de los valores permitidos";
            return false;
        }
        return true;
    }
    
    /**
     * Valida que un archivo tenga un tipo MIME permitido
     * @param array $file Array con información de archivo ($_FILES['campo'])
     * @param array $allowedTypes Tipos MIME permitidos
     * @param string $field Nombre del campo
     * @return bool True si es válido
     */
    public function fileType($file, $allowedTypes, $field) {
        if (empty($file['tmp_name'])) {
            return true; // No se subió archivo, no validar
        }
        
        if (!in_array($file['type'], $allowedTypes)) {
            $this->errors[$field] = "El archivo '$field' debe ser de tipo: " . implode(', ', $allowedTypes);
            return false;
        }
        return true;
    }
    
    /**
     * Valida que un archivo no exceda un tamaño máximo
     * @param array $file Array con información de archivo ($_FILES['campo'])
     * @param int $maxSize Tamaño máximo en bytes
     * @param string $field Nombre del campo
     * @return bool True si es válido
     */
    public function fileSize($file, $maxSize, $field) {
        if (empty($file['tmp_name'])) {
            return true; // No se subió archivo, no validar
        }
        
        if ($file['size'] > $maxSize) {
            $sizeInMB = round($maxSize / (1024 * 1024), 2);
            $this->errors[$field] = "El archivo '$field' no debe superar los $sizeInMB MB";
            return false;
        }
        return true;
    }
    
    /**
     * Valida una imagen (tipo y tamaño)
     * @param array $file Array con información de archivo ($_FILES['campo'])
     * @param array $allowedTypes Tipos MIME permitidos (default: jpg, png, gif)
     * @param int $maxSize Tamaño máximo en bytes (default: 2MB)
     * @param string $field Nombre del campo
     * @return bool True si es válido
     */
    public function image($file, $allowedTypes = null, $maxSize = null, $field = 'imagen') {
        if (empty($file['tmp_name'])) {
            return true; // No se subió archivo, no validar
        }
        
        if ($allowedTypes === null) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        }
        
        if ($maxSize === null) {
            $maxSize = 2 * 1024 * 1024; // 2MB por defecto
        }
        
        // Validar tipo
        if (!$this->fileType($file, $allowedTypes, $field)) {
            return false;
        }
        
        // Validar tamaño
        if (!$this->fileSize($file, $maxSize, $field)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Ejecuta múltiples validaciones y acumula errores
     * @param array $validations Array de configuraciones de validación
     * @return bool True si todas las validaciones son exitosas
     * 
     * Ejemplo de uso:
     * $validator->validate([
     *     ['required', $data, ['nombre', 'email']],
     *     ['email', $data['email'], 'email'],
     *     ['min', $data['edad'], 18, 'edad']
     * ]);
     */
    public function validate($validations) {
        $valid = true;
        
        foreach ($validations as $validation) {
            $method = array_shift($validation);
            if (method_exists($this, $method)) {
                $result = call_user_func_array([$this, $method], $validation);
                $valid = $valid && $result;
            }
        }
        
        return $valid;
    }
    
    /**
     * Obtiene todos los errores
     * @return array Errores
     */
    public function getErrors() {
        return $this->errors;
    }
    
    /**
     * Obtiene el primer error
     * @return string|null Primer mensaje de error o null si no hay errores
     */
    public function getFirstError() {
        return !empty($this->errors) ? reset($this->errors) : null;
    }
    
    /**
     * Obtiene el error de un campo específico
     * @param string $field Nombre del campo
     * @return string|null Mensaje de error o null si no hay error para ese campo
     */
    public function getError($field) {
        return isset($this->errors[$field]) ? $this->errors[$field] : null;
    }
    
    /**
     * Verifica si hay errores
     * @return bool True si hay errores
     */
    public function hasErrors() {
        return !empty($this->errors);
    }
    
    /**
     * Verifica si hay un error para un campo específico
     * @param string $field Nombre del campo
     * @return bool True si hay un error para ese campo
     */
    public function hasError($field) {
        return isset($this->errors[$field]);
    }
    
    /**
     * Limpia todos los errores
     */
    public function clearErrors() {
        $this->errors = [];
    }
    
    /**
     * Añade un error manualmente
     * @param string $field Nombre del campo
     * @param string $message Mensaje de error
     */
    public function addError($field, $message) {
        $this->errors[$field] = $message;
    }
}