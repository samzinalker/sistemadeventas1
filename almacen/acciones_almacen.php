<?php
// Configuración, sesión y modelos necesarios
require_once __DIR__ . '/../app/config.php'; // $pdo, $URL, $fechaHora
require_once __DIR__ . '/../app/models/AlmacenModel.php';
// require_once __DIR__ . '/../app/utils/Validator.php'; // Si necesitas validaciones más complejas

// Iniciar sesión si aún no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Respuesta por defecto
$response = ['status' => 'error', 'message' => 'Acción no válida o error desconocido.'];

// Verificar que el usuario esté logueado
if (!isset($_SESSION['id_usuario'])) {
    $response['message'] = 'Sesión expirada. Por favor, inicie sesión de nuevo.';
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
$id_usuario_actual = (int)$_SESSION['id_usuario'];

// Procesar solo si es una petición POST y la acción es la correcta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'crear_producto_almacen_rapido') {
    
    // Validar que el id_usuario_creador del formulario coincida con el de la sesión
    $id_usuario_form = filter_input(INPUT_POST, 'id_usuario_creador', FILTER_VALIDATE_INT);
    if ($id_usuario_form !== $id_usuario_actual) {
        $response['message'] = 'Error de validación de usuario.';
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    // Recuperar y sanitizar datos del POST
    $producto_codigo = filter_input(INPUT_POST, 'producto_codigo', FILTER_SANITIZE_STRING);
    $producto_nombre = filter_input(INPUT_POST, 'producto_nombre', FILTER_SANITIZE_STRING);
    $producto_descripcion = filter_input(INPUT_POST, 'producto_descripcion', FILTER_SANITIZE_STRING);
    $producto_id_categoria = filter_input(INPUT_POST, 'producto_id_categoria', FILTER_VALIDATE_INT);
    $producto_fecha_ingreso = filter_input(INPUT_POST, 'producto_fecha_ingreso', FILTER_SANITIZE_STRING); // Validar formato YYYY-MM-DD después
    $producto_precio_compra = filter_input(INPUT_POST, 'producto_precio_compra', FILTER_VALIDATE_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $producto_precio_venta = filter_input(INPUT_POST, 'producto_precio_venta', FILTER_VALIDATE_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $producto_stock_minimo = filter_input(INPUT_POST, 'producto_stock_minimo', FILTER_VALIDATE_INT);
    $producto_stock_maximo = filter_input(INPUT_POST, 'producto_stock_maximo', FILTER_VALIDATE_INT);
    // $producto_stock_inicial = 0; // El stock se manejará con la compra en sí

    // Validaciones básicas
    if (empty($producto_nombre) || !$producto_id_categoria || $producto_precio_compra === false || $producto_precio_compra < 0 || $producto_precio_venta === false || $producto_precio_venta < 0) {
        $response['message'] = 'Faltan datos requeridos o tienen formato incorrecto (Nombre, Categoría, Precios).';
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
    if (!preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $producto_fecha_ingreso)) {
        $response['message'] = 'Formato de fecha de ingreso inválido.';
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }


    try {
        $almacenModel = new AlmacenModel($pdo);

        // Generar código si no se proporcionó
        if (empty($producto_codigo)) {
            $producto_codigo = $almacenModel->generarCodigoProducto($id_usuario_actual);
        } else {
            // Opcional: Validar si el código manual ya existe para este usuario
            // $stmt_check_code = $pdo->prepare("SELECT COUNT(*) FROM tb_almacen WHERE codigo = :codigo AND id_usuario = :id_usuario");
            // $stmt_check_code->bindParam(':codigo', $producto_codigo);
            // $stmt_check_code->bindParam(':id_usuario', $id_usuario_actual);
            // $stmt_check_code->execute();
            // if ($stmt_check_code->fetchColumn() > 0) {
            //    $response['message'] = "El código de producto '{$producto_codigo}' ya existe para usted. Déjelo en blanco para autogenerar o ingrese uno diferente.";
            //    header('Content-Type: application/json');
            //    echo json_encode($response);
            //    exit;
            // }
        }

        $datos_nuevo_producto = [
            'codigo' => $producto_codigo,
            'nombre' => $producto_nombre,
            'descripcion' => $producto_descripcion ?: null,
            'stock' => 0, // Stock inicial es 0, se actualizará con la compra
            'stock_minimo' => $producto_stock_minimo ?: null,
            'stock_maximo' => $producto_stock_maximo ?: null,
            'precio_compra' => $producto_precio_compra,
            'precio_venta' => $producto_precio_venta,
            'fecha_ingreso' => $producto_fecha_ingreso,
            'imagen' => null, // La subida de imágenes no se maneja en este "quick add"
            'id_usuario' => $id_usuario_actual,
            'id_categoria' => $producto_id_categoria,
            'fyh_creacion' => $fechaHora, // De config.php
            'fyh_actualizacion' => $fechaHora // De config.php
        ];

        $nuevo_producto_id = $almacenModel->crearProducto($datos_nuevo_producto);

        if ($nuevo_producto_id) {
            $response['status'] = 'success';
            $response['message'] = 'Producto "' . htmlspecialchars($producto_nombre) . '" creado exitosamente en el almacén.';
            // Devolver los datos clave del producto para usar en el JS de compras/create.php
            $response['producto'] = [
                'id_producto' => (int)$nuevo_producto_id, // Asegurar que sea entero
                'codigo' => $producto_codigo,
                'nombre' => $producto_nombre,
                'precio_compra' => $producto_precio_compra,
                'stock' => 0 // El stock actual antes de la compra
                // Podrías añadir más campos si el JS los necesita
            ];
        } else {
            $response['message'] = 'Error al guardar el producto en la base de datos.';
            // Podrías loggear el error real del modelo aquí si lo capturas
        }

    } catch (PDOException $e) {
        // Loggear el error: error_log("Error PDO en almacen/acciones_almacen.php: " . $e->getMessage());
        $response['message'] = 'Error de base de datos al crear el producto.';
    } catch (Exception $e) {
        // Loggear el error: error_log("Error general en almacen/acciones_almacen.php: " . $e->getMessage());
        $response['message'] = 'Ocurrió un error inesperado: ' . $e->getMessage();
    }

} else {
    $response['message'] = 'Acceso no válido.';
}

header('Content-Type: application/json');
echo json_encode($response);
exit;
?>