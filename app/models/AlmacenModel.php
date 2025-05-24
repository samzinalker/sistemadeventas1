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
                                      precio_compra, precio_venta, iva_predeterminado, fecha_ingreso, imagen, 
                                      id_usuario, id_categoria, fyh_creacion, fyh_actualizacion)
                VALUES (:codigo, :nombre, :descripcion, :stock, :stock_minimo, :stock_maximo, 
                        :precio_compra, :precio_venta, :iva_predeterminado, :fecha_ingreso, :imagen, 
                        :id_usuario, :id_categoria, :fyh_creacion, :fyh_actualizacion)";
        
        $query = $this->pdo->prepare($sql);
        // Bind todos los parámetros desde el array $datos
        foreach ($datos as $key => $value) {
            $paramType = PDO::PARAM_STR;
            if (is_int($value) || $key === 'id_usuario' || $key === 'id_categoria' || $key === 'stock' || $key === 'stock_minimo' || $key === 'stock_maximo') {
                $paramType = PDO::PARAM_INT;
            } elseif (is_float($value) || $key === 'precio_compra' || $key === 'precio_venta' || $key === 'iva_predeterminado') {
                // Precios e IVA se guardan como DECIMAL en la BD, PDO los maneja bien como string.
                $paramType = PDO::PARAM_STR; 
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
    
    public function getProductoByIdAndUsuarioId(int $id_producto, int $id_usuario) {
        $sql = "SELECT p.*, c.nombre_categoria as categoria
                FROM tb_almacen as p
                INNER JOIN tb_categorias as c ON p.id_categoria = c.id_categoria
                WHERE p.id_producto = :id_producto AND p.id_usuario = :id_usuario";
        $query = $this->pdo->prepare($sql);
        $query->bindParam(':id_producto', $id_producto, PDO::PARAM_INT);
        $query->bindParam(':id_usuario', $id_usuario, PDO::PARAM_INT);
        $query->execute();
        return $query->fetch(PDO::FETCH_ASSOC); 
    }
    
    public function actualizarProducto(int $id_producto, int $id_usuario, array $datos): bool {
        $producto_actual = $this->getProductoByIdAndUsuarioId($id_producto, $id_usuario);
        if (!$producto_actual) {
            return false; 
        }
        $set_parts = [];
        // Obtener las columnas reales de la tabla para evitar errores si $datos tiene claves extras
        $stmt_cols = $this->pdo->query("DESCRIBE tb_almacen");
        $columnas_permitidas = $stmt_cols->fetchAll(PDO::FETCH_COLUMN);

        foreach (array_keys($datos) as $key) {
            // Excluir llaves primarias, y campos que no deben actualizarse directamente o no existen.
            if ($key !== 'id_producto' && $key !== 'codigo' && $key !== 'id_usuario' && $key !== 'fyh_creacion' && $key !== 'fyh_actualizacion' && $key !== 'imagen' && in_array($key, $columnas_permitidas)) {
                 $set_parts[] = "$key = :$key";
            }
        }
        if (!empty($datos['imagen'])) { // Si se proporciona una nueva imagen
            $set_parts[] = "imagen = :imagen";
        }
        $set_parts[] = "fyh_actualizacion = :fyh_actualizacion";

        if (empty($set_parts)) return false; 

        $sql = "UPDATE tb_almacen SET " . implode(', ', $set_parts) . 
               " WHERE id_producto = :id_producto_cond AND id_usuario = :id_usuario_cond";
        $query = $this->pdo->prepare($sql);

        foreach ($datos as $key => $value) {
             if ($key !== 'id_producto' && $key !== 'codigo' && $key !== 'id_usuario' && $key !== 'fyh_creacion' && $key !== 'fyh_actualizacion' && $key !== 'imagen' && in_array($key, $columnas_permitidas)) {
                 $paramType = PDO::PARAM_STR;
                 if (is_int($value) || $key === 'id_categoria' || $key === 'stock' || $key === 'stock_minimo' || $key === 'stock_maximo') {
                     $paramType = PDO::PARAM_INT;
                 } elseif (is_float($value) || $key === 'precio_compra' || $key === 'precio_venta' || $key === 'iva_predeterminado') {
                     $paramType = PDO::PARAM_STR;
                 }
                 $query->bindValue(":$key", $value, $paramType);
            }
        }
        if (!empty($datos['imagen'])) {
            $query->bindValue(':imagen', $datos['imagen'], PDO::PARAM_STR);
        }
        $query->bindValue(':fyh_actualizacion', $datos['fyh_actualizacion'], PDO::PARAM_STR); // Asegúrate que $datos['fyh_actualizacion'] esté seteado antes de llamar
        $query->bindValue(':id_producto_cond', $id_producto, PDO::PARAM_INT);
        $query->bindValue(':id_usuario_cond', $id_usuario, PDO::PARAM_INT);
        
        return $query->execute();
    }

    /**
     * Ajusta el stock de un producto específico.
     * Si la cantidad es positiva, incrementa el stock. Si es negativa, lo decrementa.
     * @param int $id_producto ID del producto.
     * @param float $cantidad_ajuste La cantidad a sumar/restar (puede ser decimal si el stock fuera decimal).
     * @param int $id_usuario_producto ID del usuario propietario del producto (para seguridad).
     * @return bool True si la actualización fue exitosa, false en caso contrario.
     * @throws Exception Si el producto no se encuentra o no pertenece al usuario.
     */
    public function ajustarStockProducto(int $id_producto, float $cantidad_ajuste, int $id_usuario_producto): bool {
        $producto = $this->getProductoByIdAndUsuarioId($id_producto, $id_usuario_producto);
        if (!$producto) {
            throw new Exception("Producto con ID $id_producto no encontrado o no pertenece al usuario $id_usuario_producto.");
        }
        $cantidad_ajuste_entero = intval($cantidad_ajuste);

        $sql = "UPDATE tb_almacen SET stock = stock + :cantidad_ajuste, fyh_actualizacion = :fyh_actualizacion
                WHERE id_producto = :id_producto AND id_usuario = :id_usuario_producto";
        
        $query = $this->pdo->prepare($sql);
        $query->bindValue(':cantidad_ajuste', $cantidad_ajuste_entero, PDO::PARAM_INT); 
        $query->bindValue(':fyh_actualizacion', date('Y-m-d H:i:s'), PDO::PARAM_STR);
        $query->bindValue(':id_producto', $id_producto, PDO::PARAM_INT);
        $query->bindValue(':id_usuario_producto', $id_usuario_producto, PDO::PARAM_INT);
        
        return $query->execute();
    }

    public function productoEnUso(int $id_producto): bool {
        // Verificar en tb_carrito
        $sql_carrito = "SELECT COUNT(*) FROM tb_carrito WHERE id_producto = :id_producto";
        $query_carrito = $this->pdo->prepare($sql_carrito);
        $query_carrito->bindParam(':id_producto', $id_producto, PDO::PARAM_INT);
        $query_carrito->execute();
        if ($query_carrito->fetchColumn() > 0) {
            error_log("productoEnUso: Producto ID {$id_producto} encontrado en tb_carrito.");
            return true;
        }

        // Verificar en tb_detalle_compras (esta es la verificación correcta para compras)
        $sql_detalle_compras = "SELECT COUNT(*) FROM tb_detalle_compras WHERE id_producto = :id_producto_detalle";
        $query_detalle_compras = $this->pdo->prepare($sql_detalle_compras);
        $query_detalle_compras->bindParam(':id_producto_detalle', $id_producto, PDO::PARAM_INT);
        $query_detalle_compras->execute();
        if ($query_detalle_compras->fetchColumn() > 0) {
            error_log("productoEnUso: Producto ID {$id_producto} encontrado en tb_detalle_compras.");
            return true;
        }
        
        // La consulta a tb_compras buscando id_producto directamente era incorrecta y ha sido eliminada.
        
        error_log("productoEnUso: Producto ID {$id_producto} NO encontrado en uso.");
        return false;
    }

    public function eliminarProducto(int $id_producto, int $id_usuario): ?string {
        // 1. Verificar si el producto pertenece al usuario
        $producto_actual = $this->getProductoByIdAndUsuarioId($id_producto, $id_usuario);
        if (!$producto_actual) {
            error_log("eliminarProducto: Producto ID {$id_producto} no encontrado o no pertenece al usuario ID {$id_usuario}.");
            return null; 
        }

        // 2. Verificar si el producto está en uso
        if ($this->productoEnUso($id_producto)) {
            error_log("eliminarProducto: Producto ID {$id_producto} está en uso. No se puede eliminar.");
            return null; 
        }
        
        // 3. El nombre de la imagen ya lo tenemos de $producto_actual['imagen']
        $nombre_imagen_a_borrar_fs = $producto_actual['imagen'];

        // 4. Eliminar el producto de la base de datos
        $sql = "DELETE FROM tb_almacen WHERE id_producto = :id_producto AND id_usuario = :id_usuario";
        $query = $this->pdo->prepare($sql);
        $query->bindParam(':id_producto', $id_producto, PDO::PARAM_INT);
        $query->bindParam(':id_usuario', $id_usuario, PDO::PARAM_INT);

        if ($query->execute()) {
            if ($query->rowCount() > 0) {
                error_log("eliminarProducto: Producto ID {$id_producto} eliminado de la BD para usuario ID {$id_usuario}. Imagen a borrar del FS: {$nombre_imagen_a_borrar_fs}");
                return $nombre_imagen_a_borrar_fs; // Éxito, devuelve el nombre de la imagen para que el controlador la borre del FS.
            } else {
                // Esto podría ocurrir si, por alguna razón muy extraña, el producto existía al inicio pero ya no cuando se hizo el DELETE.
                error_log("eliminarProducto: DELETE para producto ID {$id_producto} (usuario {$id_usuario}) se ejecutó pero rowCount es 0. No se eliminó nada.");
                return null;
            }
        } else {
            // Error en la ejecución del DELETE
            $errorInfo = $query->errorInfo();
            error_log("eliminarProducto: Error SQL al ejecutar DELETE para producto ID {$id_producto}: " . print_r($errorInfo, true));
            // La excepción PDO debería ser capturada por el controlador.
            // Devolver null aquí indica al controlador que algo falló en el modelo.
            return null;
        }
    }
}
?>