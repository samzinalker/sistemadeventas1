<?php
include('../../config.php');
session_start();

if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../../login.php');
    exit();
}

$id_usuario = $_SESSION['id_usuario'];
$id_producto = intval($_POST['id_producto']);
$cantidad = intval($_POST['cantidad']);
$fyh_creacion = date('Y-m-d H:i:s');
$fyh_actualizacion = $fyh_creacion;
$nro_venta = 0; // carrito abierto

// Verifica si ya existe el producto en el carrito abierto
$sql_check = "SELECT id_carrito, cantidad FROM tb_carrito WHERE id_usuario = :id_usuario AND id_producto = :id_producto AND nro_venta = 0";
$stmt_check = $pdo->prepare($sql_check);
$stmt_check->execute([':id_usuario' => $id_usuario, ':id_producto' => $id_producto]);
if ($row = $stmt_check->fetch(PDO::FETCH_ASSOC)) {
    $nueva_cantidad = $row['cantidad'] + $cantidad;
    $sql_update = "UPDATE tb_carrito SET cantidad = :cantidad, fyh_actualizacion = :fyh_actualizacion WHERE id_carrito = :id_carrito";
    $stmt_update = $pdo->prepare($sql_update);
    $stmt_update->execute([
        ':cantidad' => $nueva_cantidad,
        ':fyh_actualizacion' => $fyh_actualizacion,
        ':id_carrito' => $row['id_carrito']
    ]);
} else {
    $sql_insert = "INSERT INTO tb_carrito (id_usuario, nro_venta, id_producto, cantidad, fyh_creacion, fyh_actualizacion)
                   VALUES (:id_usuario, :nro_venta, :id_producto, :cantidad, :fyh_creacion, :fyh_actualizacion)";
    $stmt_insert = $pdo->prepare($sql_insert);
    $stmt_insert->execute([
        ':id_usuario' => $id_usuario,
        ':nro_venta' => $nro_venta,
        ':id_producto' => $id_producto,
        ':cantidad' => $cantidad,
        ':fyh_creacion' => $fyh_creacion,
        ':fyh_actualizacion' => $fyh_actualizacion
    ]);
}

header('Location: ../../ventas/create.php');
exit();
?>