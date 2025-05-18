<?php
// Conexión a la base de datos ya establecida en config.php

try {
    // Consulta SQL para obtener productos con nombres de categorías
    $sql_productos = "SELECT p.*, c.nombre_categoria 
                     FROM tb_almacen p 
                     INNER JOIN tb_categorias c ON p.id_categoria = c.id_categoria 
                     ORDER BY p.id_producto DESC";
    $query_productos = $pdo->prepare($sql_productos);
    $query_productos->execute();
    $productos_datos = $query_productos->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Manejo de errores
    error_log("Error al cargar productos: " . $e->getMessage());
    // Inicializamos array vacío para evitar errores
    $productos_datos = [];
}