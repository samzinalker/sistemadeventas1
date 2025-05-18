<?php
require_once '../app/config.php';
require_once '../app/controllers/almacen/AlmacenController.php';

// Configuración de página
$modulo_abierto = 'almacen';
$pagina_activa = 'almacen_edit';

// Incluir sesión y layout
include_once '../layout/sesion.php';
include_once '../layout/parte1.php';

// Verificar que exista el id
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: $URL/almacen");
    exit;
}

// Instanciar controlador
$controller = new AlmacenController($pdo);
$producto = $controller->edit($_GET['id']);

// Si no existe el producto o no pertenece al usuario
if (!$producto) {
    $_SESSION['mensaje'] = "El producto solicitado no existe o no tienes permisos para editarlo";
    $_SESSION['icono'] = "error";
    header("Location: $URL/almacen");
    exit;
}

// Obtener categorías para el select
require_once '../app/models/CategoriaModel.php';
$categoriasModel = new CategoriaModel($pdo);
$categorias = $categoriasModel->getAll($_SESSION['id_usuario']);

// Formatear ruta de imagen
$rutaImagen = !empty($producto['imagen']) 
    ? $URL . "/public/images/productos/" . $producto['imagen'] 
    : $URL . "/public/images/no-image.png";
?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><i class="fas fa-edit"></i> Editar Producto</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= $URL ?>/almacen">Almacén</a></li>
                        <li class="breadcrumb-item active">Editar Producto</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <!-- Mensajes de alerta -->
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

            <div class="row">
                <div class="col-md-3">
                    <!-- Imagen actual -->
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Imagen Actual</h3>
                        </div>
                        <div class="card-body text-center">
                            <img src="<?= $rutaImagen ?>" class="img-fluid" alt="<?= $producto['nombre'] ?>">
                        </div>
                    </div>
                </div>
                
                <div class="col-md-9">
                    <!-- Formulario de edición -->
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Datos del Producto</h3>
                            <div class="card-tools">
                                <a href="<?= $URL ?>/almacen" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-reply"></i> Volver
                                </a>
                            </div>
                        </div>
                        
                        <form action="" method="post" enctype="multipart/form-data" id="formEditProducto">
                            <input type="hidden" name="id_producto" value="<?= $producto['id_producto'] ?>">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="codigo">Código</label>
                                            <input type="text" class="form-control" id="codigo" name="codigo" value="<?= $producto['codigo'] ?>" readonly>
                                        </div>
                                        <div class="form-group">
                                            <label for="nombre">Nombre *</label>
                                            <input type="text" class="form-control" id="nombre" name="nombre" value="<?= $producto['nombre'] ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="descripcion">Descripción</label>
                                            <textarea class="form-control" id="descripcion" name="descripcion" rows="2"><?= $producto['descripcion'] ?></textarea>
                                        </div>
                                        <div class="form-group">
                                            <label for="id_categoria">Categoría *</label>
                                            <select class="form-control" id="id_categoria" name="id_categoria" required>
                                                <option value="">Seleccione una categoría</option>
                                                <?php foreach ($categorias as $categoria): ?>
                                                    <option value="<?= $categoria['id_categoria'] ?>" <?= ($producto['id_categoria'] == $categoria['id_categoria']) ? 'selected' : '' ?>>
                                                        <?= $categoria['nombre_categoria'] ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="stock">Stock Actual *</label>
                                                    <input type="number" min="0" class="form-control" id="stock" name="stock" value="<?= $producto['stock'] ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="stock_minimo">Stock Mínimo</label>
                                                    <input type="number" min="0" class="form-control" id="stock_minimo" name="stock_minimo" value="<?= $producto['stock_minimo'] ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="stock_maximo">Stock Máximo</label>
                                                    <input type="number" min="0" class="form-control" id="stock_maximo" name="stock_maximo" value="<?= $producto['stock_maximo'] ?>">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="precio_compra">Precio Compra</label>
                                                    <input type="number" min="0" step="0.01" class="form-control" id="precio_compra" name="precio_compra" value="<?= $producto['precio_compra'] ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="precio_venta">Precio Venta *</label>
                                                    <input type="number" min="0" step="0.01" class="form-control" id="precio_venta" name="precio_venta" value="<?= $producto['precio_venta'] ?>" required>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label for="fecha_ingreso">Fecha Ingreso *</label>
                                            <input type="date" class="form-control" id="fecha_ingreso" name="fecha_ingreso" value="<?= $producto['fecha_ingreso'] ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="imagen">Cambiar Imagen</label>
                                            <div class="custom-file">
                                                <input type="file" class="custom-file-input" id="imagen" name="imagen" accept="image/*">
                                                <label class="custom-file-label" for="imagen">Seleccionar archivo</label>
                                            </div>
                                            <small class="text-muted">Dejar vacío para mantener la imagen actual</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer text-center">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Actualizar Producto
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
$(function() {
    // Mostrar nombre del archivo seleccionado en input file
    $('.custom-file-input').on('change', function() {
        var fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').addClass("selected").html(fileName);
    });
    
    // Enviar formulario por AJAX
    $('#formEditProducto').on('submit', function(e) {
        e.preventDefault();
        
        // Crear FormData con los datos del formulario
        var formData = new FormData(this);
        formData.append('action', 'update');
        
        // Mostrar indicador de carga
        Swal.fire({
            title: 'Actualizando...',
            text: 'Por favor espere',
            allowOutsideClick: false,
            showConfirmButton: false,
            willOpen: () => {
                Swal.showLoading();
            }
        });
        
        // Enviar datos
        $.ajax({
            url: '../app/ajax/almacen.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            contentType: false,
            processData: false,
            success: function(response) {
                if (response.status) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Éxito',
                        text: response.message
                    }).then(() => {
                        window.location.href = '<?= $URL ?>/almacen';
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message
                    });
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Ocurrió un error al procesar la solicitud'
                });
            }
        });
    });
});
</script>

<?php include_once '../layout/parte2.php'; ?>