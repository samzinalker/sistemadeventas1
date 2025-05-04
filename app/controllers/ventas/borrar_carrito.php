<?php
include ($_SERVER['DOCUMENT_ROOT'] . '/sistemadeventas/app/config.php');

$id_carrito = $_POST['id_carrito'];


    $pdo->beginTransaction();

    $sentencia = $pdo->prepare("DELETE FROM tb_carrito WHERE id_carrito=:id_carrito");
    $sentencia->bindParam('id_carrito', $id_carrito);

    if ($sentencia->execute()) {
        $pdo->commit(); // Confirma la transacción
        ?>
        <script>
            location.href = "<?php echo $URL;?>/ventas/create.php";
        </script>
        <?php
    } else {
      
        ?>
        
        <?php
    }

