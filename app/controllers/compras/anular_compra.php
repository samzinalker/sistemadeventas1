<?php
/**
 * Controlador para anular una compra existente
 * Cambia el estado de la compra y revierte el stock de los productos
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

// Verificar método y parámetro
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id']) || !is_numeric($_POST['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Solicitud no válida']);
    exit;
}

$id_compra = intval($_POST['id']);

try {
    // Iniciar transacción para garantizar integridad
    $pdo->beginTransaction();
    
    // Verificar que la compra exista y pertenezca al usuario
    $sql = "SELECT id, estado FROM compras WHERE id = ? AND id_usuario = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_compra, $id_usuario]);
    $compra = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$compra) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Compra no encontrada o sin acceso']);
        exit;
    }
    
    if ($compra['estado'] == 0) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'La compra ya se encuentra anulada']);
        exit;
    }
    
    // Obtener los detalles para revertir el stock
    $sql = "SELECT d.id_producto, d.cantidad 
            FROM detalle_compras d
            WHERE d.id_compra = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_compra]);
    $detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Revertir el stock de cada producto
    foreach ($detalles as $detalle) {
        $sql = "UPDATE tb_almacen 
                SET stock = stock - ? 
                WHERE id_producto = ? AND id_usuario = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$detalle['cantidad'], $detalle['id_producto'], $id_usuario]);
    }
    
    // Actualizar estado de la compra a anulado (0)
    $sql = "UPDATE compras SET estado = 0 WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_compra]);
    
    // Confirmar transacción
    $pdo->commit();
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Compra anulada correctamente'
    ]);
} catch (Exception $e) {
    // Revertir cambios si hay error
    $pdo->rollBack();
    error_log("Error en anular_compra.php: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Error al anular la compra']);
}
?>