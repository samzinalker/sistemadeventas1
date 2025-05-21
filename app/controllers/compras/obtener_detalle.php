<?php
/**
 * Controlador para obtener los detalles de una compra específica
 * Devuelve la información de la cabecera de compra y sus productos
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

// Validar parámetro id
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'ID de compra no válido']);
    exit;
}

$id_compra = intval($_GET['id']);

try {
    // Obtener información de la compra
    $sql = "SELECT c.*, p.nombre_proveedor 
            FROM compras c
            INNER JOIN tb_proveedores p ON c.id_proveedor = p.id_proveedor
            WHERE c.id = ? AND c.id_usuario = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_compra, $id_usuario]);
    $compra = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$compra) {
        echo json_encode(['status' => 'error', 'message' => 'Compra no encontrada o sin acceso']);
        exit;
    }
    
    // Obtener detalles de la compra (productos)
    $sql = "SELECT d.*, p.codigo, p.nombre 
            FROM detalle_compras d
            INNER JOIN tb_almacen p ON d.id_producto = p.id_producto
            WHERE d.id_compra = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_compra]);
    $detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Devolver respuesta exitosa
    echo json_encode([
        'status' => 'success',
        'compra' => $compra,
        'detalles' => $detalles
    ]);
} catch (PDOException $e) {
    error_log("Error en obtener_detalle.php: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Error al obtener los detalles de la compra']);
}
?>