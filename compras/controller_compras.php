<?php
include('../app/config.php');
// Asegurarse que UsuarioModel está disponible si no lo está globalmente
if (!class_exists('UsuarioModel')) {
    include_once __DIR__ . '/../app/models/UsuarioModel.php';
}


if (!isset($_SESSION['id_usuario'])) {
    // Si no hay sesión, es mejor redirigir al login directamente.
    // El script create.php ya no necesita manejar un error de sesión si este controlador lo hace.
    $_SESSION['mensaje'] = "Error de sesión. Por favor, inicie sesión nuevamente.";
    $_SESSION['icono'] = "error";
    header('Location: ' . $URL . '/login/'); // Redirigir al login
    exit;
}
$id_usuario_sesion = (int)$_SESSION['id_usuario'];


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_proveedor = filter_input(INPUT_POST, 'id_proveedor', FILTER_VALIDATE_INT);
    $comprobante = trim(filter_input(INPUT_POST, 'comprobante', FILTER_SANITIZE_STRING));
    $fecha_compra_input = filter_input(INPUT_POST, 'fecha_compra', FILTER_SANITIZE_STRING);
    $iva_porcentaje_form = filter_input(INPUT_POST, 'iva_porcentaje', FILTER_VALIDATE_FLOAT);

    $fecha_compra_obj = DateTime::createFromFormat('d/m/Y', $fecha_compra_input);
    if ($fecha_compra_obj) {
        $fecha_compra_db = $fecha_compra_obj->format('Y-m-d');
    } else {
        $_SESSION['mensaje'] = "Formato de fecha de compra inválido. Use DD/MM/YYYY.";
        $_SESSION['icono'] = "error";
        header('Location: ' . $URL . '/compras/create.php');
        exit;
    }

    $productos_compra = isset($_POST['productos']) && is_array($_POST['productos']) ? $_POST['productos'] : [];

    if (empty($id_proveedor) || empty($comprobante) || empty($fecha_compra_db) || empty($productos_compra) || $iva_porcentaje_form === false || $iva_porcentaje_form < 0) {
        $error_msg = 'Faltan datos obligatorios o son inválidos: Proveedor, Comprobante, Fecha, IVA o al menos un Producto.';
        if ($iva_porcentaje_form === false || $iva_porcentaje_form < 0) {
            $error_msg = 'El porcentaje de IVA no es válido.';
        }
        $_SESSION['mensaje'] = $error_msg;
        $_SESSION['icono'] = "error";
        header('Location: ' . $URL . '/compras/create.php');
        exit;
    }

    // Generar Número de Compra Único por Usuario
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

    $pdo->beginTransaction();

    try {
        $stmt_insert_compra = $pdo->prepare("
            INSERT INTO tb_compras 
            (nro_compra, id_producto, id_proveedor, id_usuario, fecha_compra, comprobante, cantidad, precio_compra, iva_porcentaje, fyh_creacion, fyh_actualizacion) 
            VALUES (:nro_compra, :id_producto, :id_proveedor, :id_usuario, :fecha_compra, :comprobante, :cantidad, :precio_compra, :iva_porcentaje, :fyh_creacion, :fyh_actualizacion)
        ");

        $stmt_update_stock = $pdo->prepare(
            "UPDATE tb_almacen SET stock = stock + :cantidad_comprada, precio_compra = :nuevo_precio_compra, fyh_actualizacion = :fyh_actualizacion WHERE id_producto = :id_producto AND id_usuario = :id_usuario_stock"
        );

        foreach ($productos_compra as $indice => $item) {
            $id_producto_item = filter_var($item['id_producto'], FILTER_VALIDATE_INT);
            $cantidad_item = filter_var($item['cantidad'], FILTER_VALIDATE_FLOAT); // Permitir decimales en cantidad
            $precio_compra_item_sin_iva = filter_var($item['precio_compra'], FILTER_VALIDATE_FLOAT);

            if (!$id_producto_item || $cantidad_item <= 0 || $precio_compra_item_sin_iva < 0) {
                throw new Exception('Datos inválidos para el producto en el índice ' . htmlspecialchars($indice) . '. Verifique cantidad y precio.');
            }

            $stmt_insert_compra->bindParam(':nro_compra', $nro_compra_generado, PDO::PARAM_STR);
            $stmt_insert_compra->bindParam(':id_producto', $id_producto_item, PDO::PARAM_INT);
            $stmt_insert_compra->bindParam(':id_proveedor', $id_proveedor, PDO::PARAM_INT);
            $stmt_insert_compra->bindParam(':id_usuario', $id_usuario_sesion, PDO::PARAM_INT);
            $stmt_insert_compra->bindParam(':fecha_compra', $fecha_compra_db, PDO::PARAM_STR);
            $stmt_insert_compra->bindParam(':comprobante', $comprobante, PDO::PARAM_STR);
            $stmt_insert_compra->bindParam(':cantidad', $cantidad_item, PDO::PARAM_STR); 
            $stmt_insert_compra->bindParam(':precio_compra', $precio_compra_item_sin_iva, PDO::PARAM_STR);
            $stmt_insert_compra->bindParam(':iva_porcentaje', $iva_porcentaje_form, PDO::PARAM_STR);
            $stmt_insert_compra->bindParam(':fyh_creacion', $fechaHora, PDO::PARAM_STR);
            $stmt_insert_compra->bindParam(':fyh_actualizacion', $fechaHora, PDO::PARAM_STR);
            $stmt_insert_compra->execute();

            $stmt_update_stock->bindParam(':cantidad_comprada', $cantidad_item, PDO::PARAM_STR);
            $stmt_update_stock->bindParam(':nuevo_precio_compra', $precio_compra_item_sin_iva, PDO::PARAM_STR);
            $stmt_update_stock->bindParam(':fyh_actualizacion', $fechaHora, PDO::PARAM_STR);
            $stmt_update_stock->bindParam(':id_producto', $id_producto_item, PDO::PARAM_INT);
            $stmt_update_stock->bindParam(':id_usuario_stock', $id_usuario_sesion, PDO::PARAM_INT);
            $stmt_update_stock->execute();
        }

        // Actualizar preferencia de IVA del usuario
        $usuarioModel = new UsuarioModel($pdo, $URL);
        $usuarioModel->updatePreferenciaIva($id_usuario_sesion, $iva_porcentaje_form);
        $_SESSION['preferencia_iva'] = $iva_porcentaje_form;

        $pdo->commit();

        $_SESSION['mensaje'] = "Compra Nro. " . htmlspecialchars($nro_compra_generado) . " registrada exitosamente.";
        $_SESSION['icono'] = "success";
        header('Location: ' . $URL . '/compras/'); // Redirige al index de compras
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['mensaje'] = 'Error al registrar la compra: ' . $e->getMessage();
        $_SESSION['icono'] = "error";
        header('Location: ' . $URL . '/compras/create.php');
        exit;
    }

} else {
    // Si alguien intenta acceder directamente al controlador sin POST, redirigir.
    $_SESSION['mensaje'] = "Acceso no permitido.";
    $_SESSION['icono'] = "warning";
    header('Location: ' . $URL . '/compras/create.php');
    exit;
}
?>