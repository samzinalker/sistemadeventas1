<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../controllers/categorias/CategoriaController.php';

// Verificar autenticación
if (!isset($_SESSION['id_usuario'])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => false, 'message' => 'No autorizado', 'icon' => 'error']);
    exit;
}

// Crear instancia del controlador
$controller = new CategoriaController($pdo);

// Proceso de acción según método y parámetros
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'store':
            $nombreCategoria = $_POST['nombre_categoria'] ?? '';
            $result = $controller->store($nombreCategoria);
            header('Content-Type: application/json');
            echo json_encode($result);
            break;
            
        case 'update':
            $id = $_POST['id_categoria'] ?? 0;
            $nombreCategoria = $_POST['nombre_categoria'] ?? '';
            $result = $controller->update($id, $nombreCategoria);
            header('Content-Type: application/json');
            echo json_encode($result);
            break;
            
        case 'destroy':
            $id = $_POST['id_categoria'] ?? 0;
            $result = $controller->destroy($id);
            header('Content-Type: application/json');
            echo json_encode($result);
            break;
            
        case 'search':
            $term = $_POST['term'] ?? '';
            $categorias = $controller->search($term);
            header('Content-Type: application/json');
            echo json_encode(['status' => true, 'data' => $categorias]);
            break;
            
        default:
            header('Content-Type: application/json');
            echo json_encode(['status' => false, 'message' => 'Acción no válida', 'icon' => 'error']);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['status' => false, 'message' => 'Método no permitido', 'icon' => 'error']);
}