<?php

class RolModel {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Obtiene todos los roles.
     * @return array Lista de roles.
     */
    public function getAllRoles(): array {
        $sql = "SELECT id_rol, rol FROM tb_roles ORDER BY rol ASC";
        $query = $this->pdo->prepare($sql);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene un rol específico por su ID.
     * @param int $id_rol
     * @return array|false Datos del rol o false si no se encuentra.
     */
    public function getRolById(int $id_rol) {
        $sql = "SELECT id_rol, rol FROM tb_roles WHERE id_rol = :id_rol";
        $query = $this->pdo->prepare($sql);
        $query->bindParam(':id_rol', $id_rol, PDO::PARAM_INT);
        $query->execute();
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    // Aquí podrías añadir más métodos si necesitas crear, actualizar o eliminar roles desde la aplicación.
    // Por ahora, solo necesitamos leerlos para el formulario de usuarios.
}

?>