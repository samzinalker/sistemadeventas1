<?php
include('../../config.php');
session_start();

if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../../login.php');
    exit();
}

$id_usuario = $_SESSION['id_usuario'];
$id_carrito = intval($_POST['id_carrito'] ?? 0);

// Elimina solo si el producto pertenece al usuario y está en carrito abierto
$sql = "DELETE FROM tb_carrito WHERE id_carrito = :id_carrito AND id_usuario = :id_usuario AND nro_venta = 0";
$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':id_carrito' => $id_carrito,
    ':id_usuario' => $id_usuario
]);

header('Location: ../../ventas/create.php');
exit();
?>