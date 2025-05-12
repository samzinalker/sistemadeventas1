<?php
include('../app/config.php');
if (session_status() == PHP_SESSION_NONE) session_start();

$id_usuario_actual = $_SESSION['id_usuario'];
$sql_carrito = "SELECT c.*, 
                       pro.nombre AS nombre_producto, 
                       pro.descripcion AS descripcion, 
                       pro.precio_venta AS precio_venta, 
                       pro.stock AS stock 
                FROM tb_carrito c
                INNER JOIN tb_almacen pro ON c.id_producto = pro.id_producto
                WHERE c.id_usuario = :id_usuario AND c.nro_venta = 0";
$query_carrito = $pdo->prepare($sql_carrito);
$query_carrito->bindParam(':id_usuario', $id_usuario_actual, PDO::PARAM_INT);
$query_carrito->execute();
$carrito_datos = $query_carrito->fetchAll(PDO::FETCH_ASSOC);

$contador_de_carrito = 0;
$cantidad_total = 0;
$precio_total = 0;
$iva_total = 0;

// Recuperamos los porcentajes de IVA almacenados en la sesión
// Si no existe la variable en la sesión, la inicializamos como un array vacío
if (!isset($_SESSION['iva_productos'])) {
    $_SESSION['iva_productos'] = [];
}
?>
<div class="table-responsive">
    <table class="table table-bordered table-sm table-hover table-striped">
        <thead>
            <tr>
                <th style="background-color:#4d66ca; text-align:center">Nro</th>
                <th style="background-color:#4d66ca; text-align:center">Producto</th>
                <th style="background-color:#4d66ca; text-align:center">Descripción</th>
                <th style="background-color:#4d66ca; text-align:center">Cantidad</th>
                <th style="background-color:#4d66ca; text-align:center">Precio Unitario</th>
                <th style="background-color:#4d66ca; text-align:center">Subtotal</th>
                <th style="background-color:#4d66ca; text-align:center">IVA (%)</th>
                <th style="background-color:#4d66ca; text-align:center">Total+IVA</th>
                <th style="background-color:#4d66ca; text-align:center">Acción</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            foreach ($carrito_datos as $carrito_dato): 
                $contador_de_carrito++;
                $id_carrito = $carrito_dato['id_carrito'];
                $subtotal = $carrito_dato['cantidad'] * $carrito_dato['precio_venta'];
                $cantidad_total += $carrito_dato['cantidad'];
                $precio_total += $subtotal;
                
                // Obtener el porcentaje de IVA para este producto específico (por defecto 0%)
                $porcentaje_iva = isset($_SESSION['iva_productos'][$id_carrito]) ? $_SESSION['iva_productos'][$id_carrito] : 0;
                $monto_iva = $subtotal * ($porcentaje_iva / 100);
                $iva_total += $monto_iva;
                $total_con_iva = $subtotal + $monto_iva;
            ?>
                <tr>
                    <td><center><?php echo $contador_de_carrito;?></center></td>
                    <td><center><?php echo htmlspecialchars($carrito_dato['nombre_producto']); ?></center></td>
                    <td><center><?php echo htmlspecialchars($carrito_dato['descripcion']); ?></center></td>
                    <td><center><?php echo $carrito_dato['cantidad']; ?></center></td>
                    <td><center><?php echo number_format($carrito_dato['precio_venta'],2); ?></center></td>
                    <td><center><?php echo number_format($subtotal,2); ?></center></td>
                    <td>
                        <div class="input-group input-group-sm">
                            <input type="number" class="form-control iva-producto" 
                                   id="iva_producto_<?php echo $id_carrito; ?>"
                                   data-id-carrito="<?php echo $id_carrito; ?>"
                                   value="<?php echo $porcentaje_iva; ?>"
                                   min="0" step="0.01" style="width: 60px; text-align: center;">
                            <div class="input-group-append">
                                <button class="btn btn-sm btn-outline-primary btn-actualizar-iva" 
                                        data-id-carrito="<?php echo $id_carrito; ?>" type="button">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                            </div>
                        </div>
                    </td>
                    <td><center><?php echo number_format($total_con_iva, 2); ?></center></td>
                    <td>
                        <center>
                            <form action="../app/controllers/ventas/borrar_carrito.php" method="post" style="display:inline;">
                                <input type="hidden" name="id_carrito" value="<?php echo $id_carrito; ?>"> 
                                <button type="submit" class="btn btn-danger btn-sm"><i class="fa fa-trash"></i> Borrar</button>
                            </form>
                        </center>
                    </td>
                </tr>
            <?php endforeach; ?>
            <tr>
                <th colspan="5" style="background-color:#aef77d;text-align:right">Total</th>
                <th style="background-color:#e0f933">
                    <center>
                        <span id="subtotal_carrito"><?php echo number_format($precio_total,2);?></span>
                    </center>
                </th>
                <th></th>
                <th style="background-color:#e0f933">
                    <center>
                        <span id="total_carrito"><?php echo number_format($precio_total + $iva_total,2);?></span>
                    </center>
                </th>
                <th></th>
            </tr>
            <tr>
                <td colspan="6" style="text-align:right">
                    <strong>Total IVA:</strong>
                </td>
                <td colspan="3" style="background-color:#f8d7da">
                    <center>
                        <span id="total_iva"><?php echo number_format($iva_total, 2); ?></span>
                    </center>
                </td>
            </tr>
        </tbody>
    </table>
</div>

<script>
$(document).ready(function() {
    // Función para validar y actualizar el IVA de un producto
    $('.btn-actualizar-iva').on('click', function() {
        const idCarrito = $(this).data('id-carrito');
        const inputIva = $('#iva_producto_' + idCarrito);
        let porcentajeIva = parseFloat(inputIva.val());
        
        // Validar que el IVA no sea negativo
        if (porcentajeIva < 0 || isNaN(porcentajeIva)) {
            Swal.fire({
                icon: 'error',
                title: 'Valor inválido',
                text: 'El porcentaje de IVA no puede ser negativo'
            });
            inputIva.val(0);
            porcentajeIva = 0;
        }
        
        // Actualizar el valor del IVA en la sesión mediante AJAX
        $.post('../app/controllers/ventas/actualizar_iva_producto.php', {
            id_carrito: idCarrito,
            porcentaje_iva: porcentajeIva
        }, function(response) {
            try {
                const data = JSON.parse(response);
                if (data.status === 'success') {
                    // Recargar la tabla del carrito para reflejar el nuevo IVA
                    $('#carrito_contenido').load('carrito_tabla.php?nocache=' + new Date().getTime(), function() {
                        // Actualizar el campo total_a_cancelar del formulario principal
                        var nuevoTotal = $('#total_carrito').text();
                        $('#total_a_cancelar').val(nuevoTotal);
                        
                        // Notificar al usuario
                        Swal.fire({
                            icon: 'success',
                            title: 'IVA actualizado',
                            text: 'El porcentaje de IVA ha sido actualizado',
                            showConfirmButton: false,
                            timer: 1500
                        });
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'No se pudo actualizar el IVA'
                    });
                }
            } catch(e) {
                console.error('Error al procesar la respuesta:', e);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Ocurrió un error al procesar la respuesta'
                });
            }
        }).fail(function(xhr, status, error) {
            console.error('Error en la petición AJAX:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error de conexión',
                text: 'No se pudo comunicar con el servidor'
            });
        });
    });
    
    // También permitir actualizar el IVA al presionar Enter en el campo
    $('.iva-producto').on('keypress', function(e) {
        if (e.which === 13) { // Código de tecla Enter
            e.preventDefault();
            const idCarrito = $(this).data('id-carrito');
            $('.btn-actualizar-iva[data-id-carrito="' + idCarrito + '"]').click();
        }
    });
});
</script>