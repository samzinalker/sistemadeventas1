<?php
require_once __DIR__ . '/../app/config.php'; // $pdo, $URL, $fechaHora

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Seguridad: Verificar que el usuario esté logueado
if (!isset($_SESSION['id_usuario'])) {
    // Normalmente redirigirías al login o mostrarías un error JSON si es una API
    header('Location: ' . $URL . '/login.php?error=session_expired');
    exit;
}
$id_usuario_actual = $_SESSION['id_usuario'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'registrar_compra') {

    // 1. Recuperar y Validar Datos Generales de la Compra
    $nro_compra = filter_input(INPUT_POST, 'nro_compra', FILTER_SANITIZE_NUMBER_INT);
    $fecha_compra = filter_input(INPUT_POST, 'fecha_compra', FILTER_SANITIZE_STRING); // Validar formato YYYY-MM-DD después
    $id_proveedor = filter_input(INPUT_POST, 'id_proveedor', FILTER_SANITIZE_NUMBER_INT);
    $comprobante = filter_input(INPUT_POST, 'comprobante', FILTER_SANITIZE_STRING);
    $id_usuario_registro_form = filter_input(INPUT_POST, 'id_usuario_registro', FILTER_SANITIZE_NUMBER_INT);

    // Asegurarse que el usuario que registra es el usuario en sesión
    if ((int)$id_usuario_registro_form !== $id_usuario_actual) {
        header('Location: create.php?error=Error de validación de usuario.');
        exit;
    }

    $items = $_POST['items'] ?? []; // Array de productos

    // Validaciones básicas
    if (empty($nro_compra) || empty($fecha_compra) || empty($id_proveedor) || empty($comprobante) || empty($items)) {
        header('Location: create.php?error=Faltan datos generales o no hay productos en la compra.');
        exit;
    }
    // Validar formato de fecha (ejemplo simple)
    if (!preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $fecha_compra)) {
        header('Location: create.php?error=Formato de fecha inválido.');
        exit;
    }

    $pdo->beginTransaction();
    try {
        $compra_exitosa_general = true;
        $errores_items = [];

        foreach ($items as $index => $item) {
            $id_producto_almacen = filter_var($item['id_producto_almacen'] ?? null, FILTER_VALIDATE_INT);
            // $nombre_producto_manual = filter_var($item['nombre_producto_manual'] ?? '', FILTER_SANITIZE_STRING); // No usado para crear producto en esta versión
            $cantidad = filter_var($item['cantidad'] ?? 0, FILTER_VALIDATE_INT);
            $precio_compra_item = filter_var($item['precio_compra'] ?? 0, FILTER_VALIDATE_FLOAT);

            // Validar cada ítem
            if (!$id_producto_almacen || $cantidad <= 0 || $precio_compra_item < 0) {
                $errores_items[] = "Ítem #".($index+1)." tiene datos inválidos (Producto, Cantidad o Precio). Solo se procesan productos existentes en almacén.";
                continue; // Saltar este ítem, o podrías decidir abortar toda la compra
            }
            
            // Verificar que el producto exista en el almacén DEL USUARIO ACTUAL (importante)
            $stmt_check_producto = $pdo->prepare("SELECT id_producto, nombre FROM tb_almacen WHERE id_producto = :id_producto AND id_usuario = :id_usuario");
            $stmt_check_producto->bindParam(':id_producto', $id_producto_almacen, PDO::PARAM_INT);
            $stmt_check_producto->bindParam(':id_usuario', $id_usuario_actual, PDO::PARAM_INT);
            $stmt_check_producto->execute();
            $producto_en_almacen = $stmt_check_producto->fetch(PDO::FETCH_ASSOC);

            if (!$producto_en_almacen) {
                $errores_items[] = "Ítem #".($index+1).": Producto con ID ".$id_producto_almacen." no encontrado en su almacén o no le pertenece.";
                continue;
            }

            // 2. Insertar en tb_compras
            $sql_insert_compra = "INSERT INTO tb_compras 
                                    (id_producto, nro_compra, fecha_compra, id_proveedor, comprobante, id_usuario, precio_compra, cantidad, fyh_creacion, fyh_actualizacion)
                                  VALUES 
                                    (:id_producto, :nro_compra, :fecha_compra, :id_proveedor, :comprobante, :id_usuario, :precio_compra_item, :cantidad, :fyh_creacion, :fyh_actualizacion)";
            $stmt_insert = $pdo->prepare($sql_insert_compra);
            $stmt_insert->bindParam(':id_producto', $id_producto_almacen, PDO::PARAM_INT);
            $stmt_insert->bindParam(':nro_compra', $nro_compra, PDO::PARAM_INT);
            $stmt_insert->bindParam(':fecha_compra', $fecha_compra, PDO::PARAM_STR);
            $stmt_insert->bindParam(':id_proveedor', $id_proveedor, PDO::PARAM_INT);
            $stmt_insert->bindParam(':comprobante', $comprobante, PDO::PARAM_STR);
            $stmt_insert->bindParam(':id_usuario', $id_usuario_actual, PDO::PARAM_INT);
            $stmt_insert->bindParam(':precio_compra_item', $precio_compra_item, PDO::PARAM_STR); // tb_compras.precio_compra es VARCHAR
            $stmt_insert->bindParam(':cantidad', $cantidad, PDO::PARAM_INT);
            $stmt_insert->bindParam(':fyh_creacion', $fechaHora, PDO::PARAM_STR);
            $stmt_insert->bindParam(':fyh_actualizacion', $fechaHora, PDO::PARAM_STR);
            
            if (!$stmt_insert->execute()) {
                $compra_exitosa_general = false;
                $errores_items[] = "Error al guardar ítem del producto: " . htmlspecialchars($producto_en_almacen['nombre']);
                // Podrías loggear $stmt_insert->errorInfo()
                break; // Salir del bucle si un ítem falla
            }

            // 3. Actualizar stock en tb_almacen
            $sql_update_stock = "UPDATE tb_almacen 
                                 SET stock = stock + :cantidad, fyh_actualizacion = :fyh_actualizacion 
                                 WHERE id_producto = :id_producto AND id_usuario = :id_usuario_owner";
            $stmt_update_stock = $pdo->prepare($sql_update_stock);
            $stmt_update_stock->bindParam(':cantidad', $cantidad, PDO::PARAM_INT);
            $stmt_update_stock->bindParam(':fyh_actualizacion', $fechaHora, PDO::PARAM_STR);
            $stmt_update_stock->bindParam(':id_producto', $id_producto_almacen, PDO::PARAM_INT);
            $stmt_update_stock->bindParam(':id_usuario_owner', $id_usuario_actual, PDO::PARAM_INT); // El usuario actual es el dueño del stock que se actualiza

            if (!$stmt_update_stock->execute()) {
                $compra_exitosa_general = false;
                $errores_items[] = "Error al actualizar stock para producto: " . htmlspecialchars($producto_en_almacen['nombre']);
                // Podrías loggear $stmt_update_stock->errorInfo()
                break; // Salir del bucle si falla la actualización de stock
            }
        } // Fin del bucle foreach $items

        if ($compra_exitosa_general && count($errores_items) == 0) {
            $pdo->commit();
            header('Location: index.php?status=compra_registrada');
            exit;
        } else {
            $pdo->rollBack();
            $mensaje_error = "No se pudo completar el registro de la compra. ";
            if (!empty($errores_items)) {
                $mensaje_error .= "Detalles: " . implode("; ", $errores_items);
            }
            // Es mejor pasar mensajes de error más genéricos en la URL o usar sesiones flash.
            // Por simplicidad, aquí un mensaje más detallado (pero cuidado con la longitud de URL).
            header('Location: create.php?error=' . urlencode($mensaje_error));
            exit;
        }

    } catch (PDOException $e) {
        $pdo->rollBack();
        // Loggear el error real: error_log($e->getMessage());
        header('Location: create.php?error=Error en la base de datos al procesar la compra.');
        exit;
    }

} else {
    // Si no es POST o la acción no es correcta, redirigir o mostrar error.
    header('Location: index.php');
    exit;
}
?>