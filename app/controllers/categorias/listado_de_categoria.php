<?php
// Conexión a la base de datos ya establecida en config.php

try {
    // Consulta SQL para obtener todas las categorías
    $sql_categorias = "SELECT * FROM tb_categorias ORDER BY id_categoria DESC";
    $query_categorias = $pdo->prepare($sql_categorias);
    $query_categorias->execute();
    $categorias_datos = $query_categorias->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Manejo de errores
    error_log("Error al cargar categorías: " . $e->getMessage());
    // Inicializamos array vacío para evitar errores
    $categorias_datos = [];
}