<?php
require_once '../app/config.php';
require_once '../app/controllers/almacen/AlmacenController.php';

// Configuración de página
$modulo_abierto = 'almacen';
$pagina_activa = 'almacen';

// Incluir sesión y layout
include_once '../layout/sesion.php';
include_once '../layout/parte1.php';

// Instanciar controlador y obtener datos
$controller = new AlmacenController($pdo);
$productos = $controller->index(true); // true para mostrar solo los productos del usuario actual
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Listado de productos</h1>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Productos registrados</h3>
                            <div class="card-tools">
                                <a href="create.php" class="btn btn-outline-light">
                                    <i class="fas fa-plus"></i> Crear nuevo
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if(isset($_SESSION['mensaje'])): ?>
                                <div class="alert alert-<?= $_SESSION['icono'] ?> alert-dismissible fade show" role="alert">
                                    <?= $_SESSION['mensaje'] ?>
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <?php 
                                    unset($_SESSION['mensaje']);
                                    unset($_SESSION['icono']);
                                ?>
                            <?php endif; ?>
                            
                            <table id="tabla_productos" class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Código</th>
                                        <th>Nombre</th>
                                        <th>Stock</th>
                                        <th>Precio venta</th>
                                        <th>Categoría</th>
                                        <th>Imagen</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $contador = 1;
                                    foreach($productos as $producto): 
                                        $rutaImagen = !empty($producto['imagen']) 
                                            ? $URL . "/public/images/productos/" . $producto['imagen'] 
                                            : $URL . "/public/images/no-image.png";
                                    ?>
                                    <tr>
                                        <td><?= $contador++ ?></td>
                                        <td><?= $producto['codigo'] ?></td>
                                        <td><?= $producto['nombre'] ?></td>
                                        <td>
                                            <?php if($producto['stock'] <= $producto['stock_minimo']): ?>
                                                <span class="badge badge-danger"><?= $producto['stock'] ?></span>
                                            <?php else: ?>
                                                <span class="badge badge-success"><?= $producto['stock'] ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= $producto['precio_venta'] ?></td>
                                        <td><?= $producto['nombre_categoria'] ?></td>
                                        <td class="text-center">
                                            <img src="<?= $rutaImagen ?>" width="50" alt="<?= $producto['nombre'] ?>">
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="show.php?id=<?= $producto['id_producto'] ?>" class="btn btn-info btn-sm" title="Ver detalles">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="edit.php?id=<?= $producto['id_producto'] ?>" class="btn btn-warning btn-sm" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button type="button" class="btn btn-danger btn-sm" title="Eliminar"
                                                   onclick="eliminarProducto(<?= $producto['id_producto'] ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
$(document).ready(function() {
    // Inicializar DataTable
    $('#tabla_productos').DataTable({
        "responsive": true,
        "lengthChange": false,
        "autoWidth": false,
        "buttons": ["copy", "csv", "excel", "pdf", "print", "colvis"]
    }).buttons().container().appendTo('#tabla_productos_wrapper .col-md-6:eq(0)');
});

// Función para eliminar un producto
function eliminarProducto(id) {
    Swal.fire({
        title: '¿Estás seguro?',
        text: "Esta acción no se puede revertir",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '../app/ajax/almacen.php',
                type: 'POST',
                data: {
                    'action': 'destroy',
                    'id_producto': id
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status) {
                        Swal.fire(
                            'Eliminado',
                            response.message,
                            'success'
                        ).then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire(
                            'Error',
                            response.message,
                            'error'
                        );
                    }
                },
                error: function() {
                    Swal.fire(
                        'Error',
                        'Ocurrió un error al procesar la solicitud',
                        'error'
                    );
                }
            });
        }
    });
}
</script>

<?php
include_once '../layout/parte2.php';
?>