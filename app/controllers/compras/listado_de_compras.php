<?php
// Conexión a la base de datos ya establecida en config.php

try {
    // Consulta SQL para obtener compras con información relacionada
    $sql_compras = "SELECT c.*, p.nombre as nombre_producto, pr.nombre_proveedor
                   FROM tb_compras c 
                   INNER JOIN tb_almacen p ON c.id_producto = p.id_producto
                   INNER JOIN tb_proveedores pr ON c.id_proveedor = pr.id_proveedor
                   ORDER BY c.id_compra DESC";
    $query_compras = $pdo->prepare($sql_compras);
    $query_compras->execute();
    $compras_datos = $query_compras->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Manejo de errores
    error_log("Error al cargar compras: " . $e->getMessage());
    // Inicializamos array vacío para evitar errores
    $compras_datos = [];
}