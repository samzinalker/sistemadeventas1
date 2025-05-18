<?php
/**
 * Modelo para gestionar usuarios en la base de datos
 */
class UsuarioModel {
    private $pdo;
    
    /**
     * Constructor - inyección de la conexión PDO
     */
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Obtener todos los usuarios con información de roles
     */
    public function getAll() {
        $sql = "SELECT us.id_usuario, us.nombres, us.email, us.imagen_perfil, 
                       rol.rol, rol.id_rol 
                FROM tb_usuarios us 
                INNER JOIN tb_roles rol ON us.id_rol = rol.id_rol";
        $query = $this->pdo->prepare($sql);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener un usuario por ID
     */
    public function getById($id) {
        $sql = "SELECT us.id_usuario, us.nombres, us.email, us.imagen_perfil, 
                       rol.rol, rol.id_rol
                FROM tb_usuarios us 
                INNER JOIN tb_roles rol ON us.id_rol = rol.id_rol 
                WHERE id_usuario = :id";
        $query = $this->pdo->prepare($sql);
        $query->bindParam(':id', $id, PDO::PARAM_INT);
        $query->execute();
        return $query->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Verificar si un email ya existe (excluyendo un ID opcional)
     */
    public function emailExiste($email, $id_excluir = null) {
        $sql = "SELECT COUNT(*) FROM tb_usuarios WHERE email = :email";
        if ($id_excluir !== null) {
            $sql .= " AND id_usuario != :id";
        }
        
        $query = $this->pdo->prepare($sql);
        $query->bindParam(':email', $email, PDO::PARAM_STR);
        if ($id_excluir !== null) {
            $query->bindParam(':id', $id_excluir, PDO::PARAM_INT);
        }
        $query->execute();
        
        return $query->fetchColumn() > 0;
    }
    
    /**
     * Crear un nuevo usuario
     */
    public function crear($nombres, $email, $id_rol, $password_hash, $fecha_hora) {
        $sql = "INSERT INTO tb_usuarios 
                (nombres, email, id_rol, password_user, fyh_creacion) 
                VALUES (:nombres, :email, :id_rol, :password_user, :fyh_creacion)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':nombres', $nombres);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':id_rol', $id_rol);
        $stmt->bindParam(':password_user', $password_hash);
        $stmt->bindParam(':fyh_creacion', $fecha_hora);
        
        return $stmt->execute();
    }
    
    /**
     * Actualizar un usuario existente con todos sus datos
     */
    public function actualizar($id, $nombres, $email, $id_rol, $fecha_hora) {
        $sql = "UPDATE tb_usuarios 
                SET nombres = :nombres, email = :email, id_rol = :id_rol, 
                    fyh_actualizacion = :fyh_actualizacion 
                WHERE id_usuario = :id_usuario";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':nombres', $nombres);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':id_rol', $id_rol);
        $stmt->bindParam(':fyh_actualizacion', $fecha_hora);
        $stmt->bindParam(':id_usuario', $id);
        
        return $stmt->execute();
    }
    
    /**
     * Actualizar la contraseña de un usuario
     */
    public function actualizarPassword($id, $password_hash, $fecha_hora) {
        $sql = "UPDATE tb_usuarios 
                SET password_user = :password_user, 
                    fyh_actualizacion = :fyh_actualizacion 
                WHERE id_usuario = :id_usuario";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':password_user', $password_hash);
        $stmt->bindParam(':fyh_actualizacion', $fecha_hora);
        $stmt->bindParam(':id_usuario', $id);
        
        return $stmt->execute();
    }
    
    /**
     * Eliminar un usuario por ID
     */
    public function eliminar($id) {
        // Verificar si existen registros relacionados antes de eliminar
        if ($this->tieneRegistrosRelacionados($id)) {
            return false;
        }
        
        $sql = "DELETE FROM tb_usuarios WHERE id_usuario = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        return $stmt->execute();
    }
    
    /**
     * Actualizar la imagen de perfil de un usuario
     */
    public function actualizarImagen($id, $imagen, $fecha_hora) {
        $sql = "UPDATE tb_usuarios 
                SET imagen_perfil = :imagen, 
                    fyh_actualizacion = :fyh_actualizacion 
                WHERE id_usuario = :id_usuario";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':imagen', $imagen);
        $stmt->bindParam(':fyh_actualizacion', $fecha_hora);
        $stmt->bindParam(':id_usuario', $id);
        
        return $stmt->execute();
    }
    
    /**
     * Verificar si un usuario tiene registros relacionados que impidan su eliminación
     */
    private function tieneRegistrosRelacionados($id_usuario) {
        // Verificar si hay productos asociados
        $sql = "SELECT COUNT(*) FROM tb_almacen WHERE id_usuario = :id";
        $query = $this->pdo->prepare($sql);
        $query->bindParam(':id', $id_usuario, PDO::PARAM_INT);
        $query->execute();
        if ($query->fetchColumn() > 0) {
            return true;
        }
        
        // Verificar si hay ventas asociadas
        $sql = "SELECT COUNT(*) FROM tb_ventas WHERE id_usuario = :id";
        $query = $this->pdo->prepare($sql);
        $query->bindParam(':id', $id_usuario, PDO::PARAM_INT);
        $query->execute();
        if ($query->fetchColumn() > 0) {
            return true;
        }
        
        // Verificar si hay categorías asociadas
        $sql = "SELECT COUNT(*) FROM tb_categorias WHERE id_usuario = :id";
        $query = $this->pdo->prepare($sql);
        $query->bindParam(':id', $id_usuario, PDO::PARAM_INT);
        $query->execute();
        if ($query->fetchColumn() > 0) {
            return true;
        }
        
        return false;
    }
}