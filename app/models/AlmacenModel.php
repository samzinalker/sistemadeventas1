<?php

class AlmacenModel {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Genera un nuevo código de producto único para un usuario.
     * Formato: P-XXXXX (donde XXXXX es un número secuencial para ese usuario)
     * @param int $id_usuario
     * @return string
     */
    public function generarCodigoProducto(int $id_usuario): string {
        // Esta es una forma simple. Para alta concurrencia, se podría necesitar un enfoque más robusto.
        $sql = "SELECT COUNT(*) FROM tb_almacen WHERE id_usuario = :id_usuario";
        $query = $this->pdo->prepare($sql);
        $query->bindParam(':id_usuario', $id_usuario, PDO::PARAM_INT);
        $query->execute();
        $total_productos_usuario = $query->fetchColumn();
        $siguiente_numero = $total_productos_usuario + 1;
        
        // Busca el último código numérico para evitar colisiones si se borran productos intermedios
        $sql_last = "SELECT MAX(CAST(SUBSTRING_INDEX(codigo, '-', -1) AS UNSIGNED)) as max_codigo 
                     FROM tb_almacen WHERE id_usuario = :id_usuario AND codigo LIKE 'P-%'";
        $query_last = $this->pdo->prepare($sql_last);
        $query_last->bindParam(':id_usuario', $id_usuario, PDO::PARAM_INT);
        $query_last->execute();
        $max_codigo_actual = $query_last->fetchColumn();
        
        if ($max_codigo_actual >= $siguiente_numero) {
            $siguiente_numero = $max_codigo_actual + 1;
        }

        return "P-" . str_pad($siguiente_numero, 5, "0", STR_PAD_LEFT);
    }


    /**
     * Crea un nuevo producto para un usuario.
     * @param array $datos Datos del producto.
     * @return string|false El ID del producto creado o false en error.
     */
    public function crearProducto(array $datos): ?string {
        $sql = "INSERT INTO tb_almacen (codigo, nombre, descripcion, stock, stock_minimo, stock_maximo, 
                                      precio_compra, precio_venta, fecha_ingreso, imagen, 
                                      id_usuario, id_categoria, fyh_creacion, fyh_actualizacion)
                VALUES (:codigo, :nombre, :descripcion, :stock, :stock_minimo, :stock_maximo, 
                        :precio_compra, :precio_venta, :fecha_ingreso, :imagen, 
                        :id_usuario, :id_categoria, :fyh_creacion, :fyh_actualizacion)";
        
        $query = $this->pdo->prepare($sql);
        // Bind todos los parámetros desde el array $datos
        foreach ($datos as $key => $value) {
            $paramType = PDO::PARAM_STR;
            if (is_int($value) || $key === 'id_usuario' || $key === 'id_categoria' || $key === 'stock' || $key === 'stock_minimo' || $key === 'stock_maximo') {
                $paramType = PDO::PARAM_INT;
            }
            $query->bindValue(":$key", $value, $paramType);
        }

        if ($query->execute()) {
            return $this->pdo->lastInsertId();
        }
        return null;
    }

    /**
     * Obtiene todos los productos de un usuario específico, uniéndose con categorías.
     * @param int $id_usuario
     * @return array
     */
    public function getProductosByUsuarioId(int $id_usuario): array {
        $sql = "SELECT p.*, c.nombre_categoria as categoria 
                FROM tb_almacen as p
                INNER JOIN tb_categorias as c ON p.id_categoria = c.id_categoria
                WHERE p.id_usuario = :id_usuario
                ORDER BY p.nombre ASC";
        $query = $this->pdo->prepare($sql);
        $query->bindParam(':id_usuario', $id_usuario, PDO::PARAM_INT);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene un producto específico por su ID y el ID del usuario propietario.
     * @param int $id_producto
     * @param int $id_usuario
     * @return array|false
     */
     /**
     * Obtiene un producto específico por su ID y el ID del usuario propietario.
     * Se une con la tabla de categorías para obtener el nombre de la categoría.
     * @param int $id_producto
     * @param int $id_usuario
     * @return array|false Los datos del producto o false si no se encuentra o no pertenece al usuario.
     */
    public function getProductoByIdAndUsuarioId(int $id_producto, int $id_usuario) {
        $sql = "SELECT p.*, c.nombre_categoria as categoria
                FROM tb_almacen as p
                INNER JOIN tb_categorias as c ON p.id_categoria = c.id_categoria
                WHERE p.id_producto = :id_producto AND p.id_usuario = :id_usuario";
        $query = $this->pdo->prepare($sql);
        $query->bindParam(':id_producto', $id_producto, PDO::PARAM_INT);
        $query->bindParam(':id_usuario', $id_usuario, PDO::PARAM_INT);
        $query->execute();
        return $query->fetch(PDO::FETCH_ASSOC); // Devuelve una sola fila o false
    }
    
    /**
     * Actualiza un producto existente de un usuario.
     * @param int $id_producto
     * @param int $id_usuario
     * @param array $datos
     * @return bool
     */
    public function actualizarProducto(int $id_producto, int $id_usuario, array $datos): bool {
        $producto_actual = $this->getProductoByIdAndUsuarioId($id_producto, $id_usuario);
        if (!$producto_actual) {
            return false; 
        }

        // Construcción dinámica de la consulta para actualizar solo los campos proporcionados
        $set_parts = [];
        foreach (array_keys($datos) as $key) {
            if ($key !== 'fyh_actualizacion' && $key !== 'imagen' && array_key_exists($key, $producto_actual)) { // Evitar actualizar PK o campos no existentes
                 $set_parts[] = "$key = :$key";
            }
        }
         // Manejo especial para la imagen si se incluye en $datos
        if (!empty($datos['imagen'])) {
            $set_parts[] = "imagen = :imagen";
        }
        $set_parts[] = "fyh_actualizacion = :fyh_actualizacion";


        if (empty($set_parts)) return false; // Nada que actualizar

        $sql = "UPDATE tb_almacen SET " . implode(', ', $set_parts) . 
               " WHERE id_producto = :id_producto_cond AND id_usuario = :id_usuario_cond";
        
        $query = $this->pdo->prepare($sql);
        
        foreach ($datos as $key => $value) {
            if ($key !== 'fyh_actualizacion' && $key !== 'imagen' && array_key_exists($key, $producto_actual)) {
                 $paramType = (is_int($value) || $key === 'id_categoria' || $key === 'stock' || $key === 'stock_minimo' || $key === 'stock_maximo') ? PDO::PARAM_INT : PDO::PARAM_STR;
                 $query->bindValue(":$key", $value, $paramType);
            }
        }
        if (!empty($datos['imagen'])) {
            $query->bindValue(':imagen', $datos['imagen'], PDO::PARAM_STR);
        }
        $query->bindValue(':fyh_actualizacion', $datos['fyh_actualizacion'], PDO::PARAM_STR);
        $query->bindValue(':id_producto_cond', $id_producto, PDO::PARAM_INT);
        $query->bindValue(':id_usuario_cond', $id_usuario, PDO::PARAM_INT);
        
        return $query->execute();
    }

    /**
     * Verifica si un producto está en uso (tb_carrito o tb_compras).
     * @param int $id_producto
     * @return bool
     */
    public function productoEnUso(int $id_producto): bool {
        $sql_carrito = "SELECT COUNT(*) FROM tb_carrito WHERE id_producto = :id_producto";
        $query_carrito = $this->pdo->prepare($sql_carrito);
        $query_carrito->bindParam(':id_producto', $id_producto, PDO::PARAM_INT);
        $query_carrito->execute();
        if ($query_carrito->fetchColumn() > 0) return true;

        $sql_compras = "SELECT COUNT(*) FROM tb_compras WHERE id_producto = :id_producto";
        $query_compras = $this->pdo->prepare($sql_compras);
        $query_compras->bindParam(':id_producto', $id_producto, PDO::PARAM_INT);
        $query_compras->execute();
        if ($query_compras->fetchColumn() > 0) return true;
        
        return false;
    }

    /**
     * Elimina un producto de un usuario.
     * @param int $id_producto
     * @param int $id_usuario
     * @return string|false Nombre de la imagen eliminada o false en error/no permiso/en uso.
     */
    public function eliminarProducto(int $id_producto, int $id_usuario): ?string {
        $producto_actual = $this->getProductoByIdAndUsuarioId($id_producto, $id_usuario);
        if (!$producto_actual) return null; // No encontrado o no pertenece al usuario
        
        if ($this->productoEnUso($id_producto)) return null; // Producto en uso

        $sql = "DELETE FROM tb_almacen WHERE id_producto = :id_producto AND id_usuario = :id_usuario";
        $query = $this->pdo->prepare($sql);
        $query->bindParam(':id_producto', $id_producto, PDO::PARAM_INT);
        $query->bindParam(':id_usuario', $id_usuario, PDO::PARAM_INT);
        
        if ($query->execute() && $query->rowCount() > 0) {
            return $producto_actual['imagen']; // Devuelve el nombre de la imagen para borrarla del servidor
        }
        return null;
    }
}
?>