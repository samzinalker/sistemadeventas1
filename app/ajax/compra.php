<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../controllers/compras/CompraController.php';

// Verificar autenticación
if (!isset($_SESSION['id_usuario'])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => false, 'message' => 'No autorizado']);
    exit;
}

// Crear instancia del controlador
$controller = new CompraController($pdo);

// Procesar petición
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'store':
            // Registrar nueva compra
            $result = $controller->store($_POST);
            header('Content-Type: application/json');
            echo json_encode($result);
            break;
            
        case 'get_product_info':
            // Obtener información de un producto
            $id = $_POST['id_producto'] ?? 0;
            $product = $controller->getProductInfo($id);
            
            if ($product) {
                header('Content-Type: application/json');
                echo json_encode([
                    'status' => true,
                    'data' => $product
                ]);
            } else {
                header('Content-Type: application/json');
                echo json_encode([
                    'status' => false,
                    'message' => 'Producto no encontrado'
                ]);
            }
            break;
            
        case 'get_proveedor_info':
            // Obtener información de un proveedor
            $id = $_POST['id_proveedor'] ?? 0;
            $proveedor = $controller->getProveedorInfo($id);
            
            if ($proveedor) {
                header('Content-Type: application/json');
                echo json_encode([
                    'status' => true,
                    'data' => $proveedor
                ]);
            } else {
                header('Content-Type: application/json');
                echo json_encode([
                    'status' => false,
                    'message' => 'Proveedor no encontrado'
                ]);
            }
            break;
            
        case 'search':
            // Buscar compras
            $criteria = [
                'producto' => $_POST['producto'] ?? '',
                'proveedor' => $_POST['proveedor'] ?? '',
                'fecha_desde' => $_POST['fecha_desde'] ?? '',
                'fecha_hasta' => $_POST['fecha_hasta'] ?? ''
            ];
            
            $compras = $controller->search($criteria);
            
            header('Content-Type: application/json');
            echo json_encode([
                'status' => true,
                'data' => $compras
            ]);
            break;
            
        default:
            header('Content-Type: application/json');
            echo json_encode(['status' => false, 'message' => 'Acción no válida']);
            break;
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'stats':
            // Obtener estadísticas
            $stats = $controller->getStats();
            
            header('Content-Type: application/json');
            echo json_encode([
                'status' => true,
                'data' => $stats
            ]);
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