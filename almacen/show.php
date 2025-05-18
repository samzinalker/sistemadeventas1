<?php
require_once '../app/config.php';
require_once '../app/controllers/almacen/AlmacenController.php';

// Configuración de página
$modulo_abierto = 'almacen';
$pagina_activa = 'almacen_show';

// Incluir sesión y layout
include_once '../layout/sesion.php';
include_once '../layout/parte1.php';

// Verificar que exista el id del producto
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: $URL/almacen");
    exit;
}

// Instanciar controlador y obtener producto
$controller = new AlmacenController($pdo);
$producto = $controller->show($_GET['id']);

// Si no existe el producto o no pertenece al usuario, redirigir
if (!$producto) {
    $_SESSION['mensaje'] = "El producto solicitado no existe o no tienes permisos para verlo";
    $_SESSION['icono'] = "error";
    header("Location: $URL/almacen");
    exit;
}

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
                    <h1><i class="fas fa-eye"></i> Detalles del Producto</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= $URL ?>/almacen">Almacén</a></li>
                        <li class="breadcrumb-item active">Detalles</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-4">
                    <!-- Imagen del producto -->
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Imagen</h3>
                        </div>
                        <div class="card-body text-center">
                            <img src="<?= $rutaImagen ?>" class="img-fluid" style="max-height:280px;" alt="<?= $producto['nombre'] ?>">
                        </div>
                    </div>
                </div>
                
                <div class="col-md-8">
                    <!-- Información del producto -->
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Información General</h3>
                            <div class="card-tools">
                                <a href="edit.php?id=<?= $producto['id_producto'] ?>" class="btn btn-warning btn-sm">
                                    <i class="fas fa-edit"></i> Editar
                                </a>
                                <a href="<?= $URL ?>/almacen" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-reply"></i> Volver
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="info-box bg-light">
                                        <div class="info-box-content">
                                            <span class="info-box-text text-muted">Código</span>
                                            <span class="info-box-number"><?= $producto['codigo'] ?></span>
                                        </div>
                                    </div>
                                    <div class="info-box bg-light">
                                        <div class="info-box-content">
                                            <span class="info-box-text text-muted">Nombre</span>
                                            <span class="info-box-number"><?= $producto['nombre'] ?></span>
                                        </div>
                                    </div>
                                    <div class="info-box bg-light">
                                        <div class="info-box-content">
                                            <span class="info-box-text text-muted">Categoría</span>
                                            <span class="info-box-number"><?= $producto['nombre_categoria'] ?? 'N/A' ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-box bg-light">
                                        <div class="info-box-content">
                                            <span class="info-box-text text-muted">Stock</span>
                                            <span class="info-box-number">
                                                <?php if($producto['stock'] <= $producto['stock_minimo']): ?>
                                                    <span class="badge badge-danger"><?= $producto['stock'] ?></span>
                                                <?php else: ?>
                                                    <span class="badge badge-success"><?= $producto['stock'] ?></span>
                                                <?php endif; ?>
                                                <small>Min: <?= $producto['stock_minimo'] ?> | Max: <?= $producto['stock_maximo'] ?></small>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="info-box bg-light">
                                        <div class="info-box-content">
                                            <span class="info-box-text text-muted">Precio Compra / Venta</span>
                                            <span class="info-box-number">
                                                <?= $producto['precio_compra'] ?> / 
                                                <span class="text-success"><?= $producto['precio_venta'] ?></span>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="info-box bg-light">
                                        <div class="info-box-content">
                                            <span class="info-box-text text-muted">Fecha Ingreso</span>
                                            <span class="info-box-number"><?= $producto['fecha_ingreso'] ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 mt-3">
                                    <h5>Descripción</h5>
                                    <p><?= $producto['descripcion'] ?: 'Sin descripción' ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include_once '../layout/parte2.php'; ?>