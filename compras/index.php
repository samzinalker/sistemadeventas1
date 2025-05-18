<?php
require_once '../app/config.php';  // Ruta correcta a config.php
require_once '../app/controllers/compras/CompraController.php';

// Configuración de página
$modulo_abierto = 'compras';
$pagina_activa = 'compras';

// Incluir sesión y layout
include_once '../layout/sesion.php';
include_once '../layout/parte1.php';

// Instanciar controlador y obtener datos
$controller = new CompraController($pdo);
$data = $controller->index(true); // true para mostrar solo compras del usuario actual
$compras = $data['compras'];
$stats = $data['stats'];
?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><i class="fas fa-cart-plus"></i> Gestión de Compras</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= $URL ?>">Inicio</a></li>
                        <li class="breadcrumb-item active">Compras</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <!-- Estadísticas -->
            <div class="row">
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3><?= number_format($stats['total']['count']) ?></h3>
                            <p>Compras Totales</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div class="small-box-footer">
                            Total: $<?= number_format($stats['total']['total'], 2) ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3><?= number_format($stats['month']['count']) ?></h3>
                            <p>Compras del Mes</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <div class="small-box-footer">
                            Total: $<?= number_format($stats['month']['total'], 2) ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h3><?= number_format($stats['week']['count']) ?></h3>
                            <p>Compras de la Semana</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-calendar-week"></i>
                        </div>
                        <div class="small-box-footer">
                            Total: $<?= number_format($stats['week']['total'], 2) ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-danger">
                        <div class="inner">
                            <h3><?= number_format($stats['today']['count']) ?></h3>
                            <p>Compras de Hoy</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-calendar-day"></i>
                        </div>
                        <div class="small-box-footer">
                            Total: $<?= number_format($stats['today']['total'], 2) ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Alertas -->
            <div id="alertaContainer"></div>
            
            <!-- Buscador y filtros -->
            <div class="card card-outline card-primary collapsed-card">
                <div class="card-header">
                    <h3 class="card-title">Filtros de búsqueda</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body" style="display: none;">
                    <form id="searchForm">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Producto:</label>
                                    <input type="text" class="form-control" id="searchProducto" placeholder="Nombre o código">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Proveedor:</label>
                                    <input type="text" class="form-control" id="searchProveedor" placeholder="Nombre o empresa">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Desde:</label>
                                    <input type="date" class="form-control" id="searchFechaDesde">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Hasta:</label>
                                    <input type="date" class="form-control" id="searchFechaHasta">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12 text-right">
                                <button type="button" class="btn btn-default" id="resetSearch">
                                    <i class="fas fa-eraser"></i> Limpiar
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Buscar
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Lista de compras -->
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Listado de Compras</h3>
                    <div class="card-tools">
                        <a href="create.php" class="btn btn-outline-light">
                            <i class="fas fa-plus"></i> Nueva Compra
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <table id="tablaCompras" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Nro. Compra</th>
                                <th>Fecha</th>
                                <th>Producto</th>
                                <th>Cantidad</th>
                                <th>Precio</th>
                                <th>Total
