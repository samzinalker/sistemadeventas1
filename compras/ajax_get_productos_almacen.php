<?php
require_once __DIR__ . '/../app/config.php'; // Para $pdo y $URL

// Podrías añadir lógica para server-side processing de DataTables aquí si tienes muchos productos
// (manejo de $_POST['draw'], $_POST['start'], $_POST['length'], $_POST['search']['value'], $_POST['order'], etc.)
// Por ahora, una consulta simple que devuelve todos los productos.

try {
    $stmt = $pdo->query("
        SELECT 
            p.id_producto, 
            p.codigo, 
            cat.nombre_categoria, 
            p.nombre as nombre_producto, 
            p.descripcion, 
            p.stock, 
            p.precio_compra as precio_compra_sugerido,  -- Usaremos este como precio sugerido
            p.imagen
        FROM tb_almacen as p
        INNER JOIN tb_categorias as cat ON p.id_categoria = cat.id_categoria
        ORDER BY p.nombre ASC
    ");
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // DataTables espera un objeto con una propiedad "data" que es un array de los items.
    echo json_encode(['data' => $productos]);

} catch (PDOException $e) {
    // Manejo básico de errores
    error_log("Error en ajax_get_productos_almacen.php: " . $e->getMessage());
    echo json_encode(['data' => [], 'error' => 'Error al obtener productos.']);
}
?>