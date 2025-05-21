<?php
/**
 * Controlador para buscar productos por nombre o código
 * Utilizado en la creación de compras para agregar productos
 */
require_once '../../config.php';
header('Content-Type: application/json');

// Verificación de sesión
session_start();
if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(['status' => 'error', 'message' => 'Sesión no válida']);
    exit;
}

$id_usuario = $_SESSION['id_usuario'];

// Validar término de búsqueda
if (!isset($_GET['term']) || strlen($_GET['term']) < 2) {
    echo json_encode(['status' => 'error', 'message' => 'Término de búsqueda no válido']);
    exit;
}

$termino = '%' . $_GET['term'] . '%';

try {
    // Buscar productos que coincidan con el término
    $sql = "SELECT p.*, c.nombre_categoria 
            FROM tb_almacen p
            INNER JOIN tb_categorias c ON p.id_categoria = c.id_categoria
            WHERE p.id_usuario = ? AND (p.nombre LIKE ? OR p.codigo LIKE ?)
            ORDER BY p.nombre
            LIMIT 10";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_usuario, $termino, $termino]);
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'status' => 'success',
        'productos' => $productos
    ]);
} catch (PDOException $e) {
    error_log("Error en buscar_productos.php: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Error al buscar productos']);
}
?>