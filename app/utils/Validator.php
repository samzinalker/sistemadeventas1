<?php
/**
 * Clase para validar datos de entrada
 */
class Validator {
    /**
     * Validar contraseñas coincidentes
     */
    public static function passwordsMatch($password1, $password2) {
        return $password1 === $password2;
    }
    
    /**
     * Validar longitud mínima de la contraseña
     */
    public static function passwordLength($password, $minLength = 6) {
        return strlen($password) >= $minLength;
    }
    
    /**
     * Validar formato de email
     */
    public static function isValidEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Validar campos obligatorios
     * @param array $data Array con los datos a validar
     * @param array $required Array con los campos requeridos
     * @return array Array con los campos faltantes o vacío si todo está bien
     */
    public static function requiredFields($data, $required) {
        $missing = [];
        foreach ($required as $field) {
            if (!isset($data[$field]) || trim($data[$field]) === '') {
                $missing[] = $field;
            }
        }
        return $missing;
    }
}