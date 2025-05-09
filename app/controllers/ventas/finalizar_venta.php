<?php
include('../../config.php');
session_start();
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../../login.php');
    exit();
}
$id_usuario = $_SESSION['id_usuario'];
$id_cliente = intval($_POST['id_cliente']);
$fyh = date('Y-m-d H:i:s');

// Generar un nro_venta único (puedes mejorar la lógica si quieres)
$nro_venta = time(); // o usa un autoincrement

// 1. Actualizar productos del carrito
$sql_update = "UPDATE tb_carrito SET nro_venta = :nro_venta, fyh_actualizacion = :fyh WHERE id_usuario = :id_usuario AND nro_venta = 0";
$stmt_update = $pdo->prepare($sql_update);
$stmt_update->execute([
    ':nro_venta' => $nro_venta,
    ':fyh' => $fyh,
    ':id_usuario' => $id_usuario
]);

// 2. Calcular el total
$sql_total = "SELECT SUM(cantidad * p.precio_venta) as total 
              FROM tb_carrito c 
              INNER JOIN tb_almacen p ON c.id_producto = p.id_producto
              WHERE c.id_usuario = :id_usuario AND c.nro_venta = :nro_venta";
$stmt_total = $pdo->prepare($sql_total);
$stmt_total->execute([':id_usuario' => $id_usuario, ':nro_venta' => $nro_venta]);
$total = $stmt_total->fetchColumn();
if (!$total) $total = 0;

// 3. Registrar la venta
$sql_venta = "INSERT INTO tb_ventas (nro_venta, id_cliente, id_usuario, total_pagado, fyh_creacion, fyh_actualizacion)
              VALUES (:nro_venta, :id_cliente, :id_usuario, :total_pagado, :fyh, :fyh)";
$stmt_venta = $pdo->prepare($sql_venta);
$stmt_venta->execute([
    ':nro_venta' => $nro_venta,
    ':id_cliente' => $id_cliente,
    ':id_usuario' => $id_usuario,
    ':total_pagado' => $total,
    ':fyh' => $fyh
]);

header('Location: ../../ventas/index.php?success=1');
exit();
?>