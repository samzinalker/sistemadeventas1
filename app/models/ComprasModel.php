<?php

class CompraModel {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Obtiene el siguiente número secuencial para una nueva compra de un usuario.
     * Se basa en el valor máximo actual de nro_compra para ese usuario.
     * @param int $id_usuario
     * @return int El siguiente número secuencial.
     */
    public function getSiguienteNumeroCompraSecuencial(int $id_usuario): int {
        $sql = "SELECT MAX(nro_compra) as max_nro FROM tb_compras WHERE id_usuario = :id_usuario";
        $query = $this->pdo->prepare($sql);
        $query->bindParam(':id_usuario', $id_usuario, PDO::PARAM_INT);
        $query->execute();
        $max_nro = $query->fetchColumn();
        return ($max_nro === null) ? 1 : (int)$max_nro + 1;
    }

    /**
     * Formatea el número secuencial de compra al formato C-XXXXX.
     * @param int $numero_secuencial
     * @return string Código formateado.
     */
    public function formatearCodigoCompra(int $numero_secuencial): string {
        return "C-" . str_pad($numero_secuencial, 5, "0", STR_PAD_LEFT);
    }
    
    // Aquí irían otros métodos del modelo de compras, como crearCompra, etc.
    // Por ejemplo, al crear una compra, deberías guardar tanto el $nro_compra_secuencial como el $codigo_compra_formateado.
    
    public function crearCompra(array $datos_compra): ?string {
        // $datos_compra debería incluir:
        // id_producto, nro_compra (secuencial), codigo_compra_referencia (formateado), 
        // fecha_compra, id_proveedor, comprobante (factura proveedor), id_usuario, 
        // precio_compra_unidad, cantidad, subtotal, iva_aplicado, monto_iva, total_compra, fyh_creacion

        $sql = "INSERT INTO tb_compras 
                    (id_producto, nro_compra, codigo_compra_referencia, fecha_compra, id_proveedor, comprobante, id_usuario, 
                     precio_compra, cantidad, subtotal, iva_porcentaje, monto_iva, total, fyh_creacion, fyh_actualizacion) 
                VALUES 
                    (:id_producto, :nro_compra, :codigo_compra_referencia, :fecha_compra, :id_proveedor, :comprobante, :id_usuario, 
                     :precio_compra, :cantidad, :subtotal, :iva_porcentaje, :monto_iva, :total, :fyh_creacion, :fyh_actualizacion)";
        
        $query = $this->pdo->prepare($sql);
        
        // Bind de todos los parámetros...
        // Ejemplo:
        // $query->bindValue(':nro_compra', $datos_compra['nro_compra_secuencial'], PDO::PARAM_INT);
        // $query->bindValue(':codigo_compra_referencia', $datos_compra['codigo_compra_formateado'], PDO::PARAM_STR);
        // ...otros binds...

        if ($query->execute()) {
            return $this->pdo->lastInsertId(); // Devuelve el id_compra (PK)
        }
        return null;
    }
    
}
?>