<?php
include('../app/config.php'); // Contiene $pdo, $URL, $fechaHora y debería iniciar session_start()

// 1. Verificación de Sesión y Usuario (CRUCIAL)
if (!isset($_SESSION['id_usuario'])) {
    // Si no hay sesión de usuario, redirigir con error.
    // Podrías guardar los datos del POST en $_SESSION si quieres repoblar el formulario.
    // Ejemplo: $_SESSION['compra_form_data'] = $_POST;
    header('Location: ' . $URL . '/compras/create.php?error=' . urlencode('Error de sesión. Por favor, inicie sesión nuevamente.'));
    exit;
}
$id_usuario_sesion = (int)$_SESSION['id_usuario']; // ID del usuario logueado


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 2. Recuperar y Validar Datos del Formulario
    $id_proveedor = filter_input(INPUT_POST, 'id_proveedor', FILTER_VALIDATE_INT);
    $comprobante = trim(filter_input(INPUT_POST, 'comprobante', FILTER_SANITIZE_STRING));
    $fecha_compra_input = filter_input(INPUT_POST, 'fecha_compra', FILTER_SANITIZE_STRING); // ej: 22/05/2025

    // Convertir fecha al formato YYYY-MM-DD para la base de datos
    $fecha_compra_obj = DateTime::createFromFormat('d/m/Y', $fecha_compra_input);
    if ($fecha_compra_obj) {
        $fecha_compra_db = $fecha_compra_obj->format('Y-m-d');
    } else {
        // $_SESSION['compra_form_data'] = $_POST;
        header('Location: ' . $URL . '/compras/create.php?error=' . urlencode('Formato de fecha de compra inválido. Use DD/MM/YYYY.'));
        exit;
    }

    $productos_compra = isset($_POST['productos']) && is_array($_POST['productos']) ? $_POST['productos'] : [];

    // Validaciones básicas
    if (empty($id_proveedor) || empty($comprobante) || empty($fecha_compra_db) || empty($productos_compra)) {
        // $_SESSION['compra_form_data'] = $_POST;
        header('Location: ' . $URL . '/compras/create.php?error=' . urlencode('Faltan datos obligatorios: Proveedor, Comprobante, Fecha o al menos un Producto.'));
        exit;
    }

    // 3. Generar Número de Compra Único por Usuario
    // Formato: C-UID(3)-NUM(5) -> C-001-00001
    $stmt_last_nro = $pdo->prepare("
        SELECT MAX(CAST(SUBSTRING_INDEX(nro_compra, '-', -1) AS UNSIGNED)) as max_nro 
        FROM tb_compras 
        WHERE id_usuario = :id_usuario
    ");
    $stmt_last_nro->bindParam(':id_usuario', $id_usuario_sesion, PDO::PARAM_INT);
    $stmt_last_nro->execute();
    $last_nro_result = $stmt_last_nro->fetch(PDO::FETCH_ASSOC);
    
    $siguiente_nro_compra_num = ($last_nro_result && $last_nro_result['max_nro'] !== null) ? (int)$last_nro_result['max_nro'] + 1 : 1;
    $nro_compra_generado = "C-" . str_pad($id_usuario_sesion, 3, "0", STR_PAD_LEFT) . "-" . str_pad($siguiente_nro_compra_num, 5, "0", STR_PAD_LEFT);

    // 4. Iniciar Transacción
    $pdo->beginTransaction();

    try {
        $stmt_insert_compra = $pdo->prepare("
            INSERT INTO tb_compras 
            (nro_compra, id_producto, id_proveedor, id_usuario, fecha_compra, comprobante, cantidad, precio_compra, fyh_creacion) 
            VALUES (:nro_compra, :id_producto, :id_proveedor, :id_usuario, :fecha_compra, :comprobante, :cantidad, :precio_compra, :fyh_creacion)
        ");

        $stmt_update_stock = $pdo->prepare(
            "UPDATE tb_almacen SET stock = stock + :cantidad_comprada, precio_compra = :nuevo_precio_compra WHERE id_producto = :id_producto"
        );

        foreach ($productos_compra as $indice => $item) {
            $id_producto_item = filter_var($item['id_producto'], FILTER_VALIDATE_INT);
            $cantidad_item = filter_var($item['cantidad'], FILTER_VALIDATE_FLOAT); // Podría ser float si se compran fracciones
            $precio_compra_item = filter_var($item['precio_compra'], FILTER_VALIDATE_FLOAT);

            if (!$id_producto_item || $cantidad_item <= 0 || $precio_compra_item < 0) {
                // $_SESSION['compra_form_data'] = $_POST;
                throw new Exception('Datos inválidos para el producto en el índice ' . $indice . '. Verifique cantidad y precio.');
            }

            // Insertar en tb_compras
            $stmt_insert_compra->bindParam(':nro_compra', $nro_compra_generado, PDO::PARAM_STR);
            $stmt_insert_compra->bindParam(':id_producto', $id_producto_item, PDO::PARAM_INT);
            $stmt_insert_compra->bindParam(':id_proveedor', $id_proveedor, PDO::PARAM_INT);
            $stmt_insert_compra->bindParam(':id_usuario', $id_usuario_sesion, PDO::PARAM_INT); // ID del usuario de la sesión
            $stmt_insert_compra->bindParam(':fecha_compra', $fecha_compra_db, PDO::PARAM_STR);
            $stmt_insert_compra->bindParam(':comprobante', $comprobante, PDO::PARAM_STR);
            $stmt_insert_compra->bindParam(':cantidad', $cantidad_item, PDO::PARAM_STR); // PDO maneja float como string
            $stmt_insert_compra->bindParam(':precio_compra', $precio_compra_item, PDO::PARAM_STR);
            $stmt_insert_compra->bindParam(':fyh_creacion', $fechaHora, PDO::PARAM_STR);
            $stmt_insert_compra->execute();

            // Actualizar stock y precio_compra en tb_almacen
            $stmt_update_stock->bindParam(':cantidad_comprada', $cantidad_item, PDO::PARAM_STR);
            $stmt_update_stock->bindParam(':nuevo_precio_compra', $precio_compra_item, PDO::PARAM_STR); // Actualiza el precio de compra del producto
            $stmt_update_stock->bindParam(':id_producto', $id_producto_item, PDO::PARAM_INT);
            $stmt_update_stock->execute();
        }

        // 5. Confirmar Transacción
        $pdo->commit();
        // unset($_SESSION['compra_form_data']); // Limpiar datos de formulario guardados si existieran
        header('Location: ' . $URL . '/compras/index.php?status=compra_registrada&nro_compra=' . urlencode($nro_compra_generado));
        exit;

    } catch (Exception $e) {
        // 6. Revertir Transacción en caso de Error
        $pdo->rollBack();
        // $_SESSION['compra_form_data'] = $_POST; // Guardar datos para repoblar
        // Considera loggear $e->getMessage() para depuración interna
        header('Location: ' . $URL . '/compras/create.php?error=' . urlencode('Error al registrar la compra: ' . $e->getMessage()));
        exit;
    }

} else {
    // Si no es POST, redirigir a la página de creación (o a donde corresponda)
    header('Location: ' . $URL . '/compras/create.php?info=' . urlencode('Por favor, complete el formulario para registrar una compra.'));
    exit;
}
?>