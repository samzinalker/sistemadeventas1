<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../controllers/almacen/AlmacenController.php';

// Verificar autenticación
if (!isset($_SESSION['id_usuario'])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => false, 'message' => 'No autorizado']);
    exit;
}

// Crear instancia del controlador
$controller = new AlmacenController($pdo);

// Procesar petición según el método
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'store':
            // Crear un nuevo producto
            $result = $controller->store($_POST, $_FILES['imagen'] ?? null);
            header('Content-Type: application/json');
            echo json_encode($result);
            break;
            
        case 'update':
            // Actualizar un producto existente
            $id = $_POST['id_producto'] ?? 0;
            $result = $controller->update($id, $_POST, $_FILES['imagen'] ?? null);
            header('Content-Type: application/json');
            echo json_encode($result);
            break;
            
        case 'destroy':
            // Eliminar un producto
            $id = $_POST['id_producto'] ?? 0;
            $result = $controller->destroy($id);
            header('Content-Type: application/json');
            echo json_encode($result);
            break;
            
        case 'search':
            // Buscar productos
            $term = $_POST['term'] ?? '';
            $products = $controller->search($term);
            header('Content-Type: application/json');
            echo json_encode(['status' => true, 'data' => $products]);
            break;
            
        case 'update_stock':
            // Actualizar stock
            $id = $_POST['id_producto'] ?? 0;
            $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 0;
            $result = $controller->updateStock($id, $quantity);
            header('Content-Type: application/json');
            echo json_encode($result);
            break;
            
        case 'low_stock':
            // Obtener productos con stock bajo
            $products = $controller->lowStock();
            header('Content-Type: application/json');
            echo json_encode(['status' => true, 'data' => $products]);
            break;
            
        default:
            header('Content-Type: application/json');
            echo json_encode(['status' => false, 'message' => 'Acción no válida']);
            break;
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['status' => false, 'message' => 'Método no permitido']);
}