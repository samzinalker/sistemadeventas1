<?php
require_once '../app/config.php';
require_once '../app/controllers/compras/CompraController.php';

// Configuración de página
$modulo_abierto = 'compras';
$pagina_activa = 'compras';

// Incluir sesión y layout
include_once '../layout/sesion.php';
include_once '../layout/parte1.php';

// Verificar que exista el ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: $URL/compras");
    exit;
}

// Instanciar controlador y obtener detalles
$controller = new CompraController($pdo);
$compra = $controller->show($_GET['id']);

// Verificar si la compra existe
if (!$compra) {
    $_SESSION['mensaje'] = "La compra solicitada no existe o no tiene permisos para verla.";
    $_SESSION['icono'] = "error";
    header("Location: $URL/compras");
    exit;
}

// Calcular el total
$total = $compra['precio_compra'] * $compra['cantidad'];
?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><i class="fas fa-file-invoice"></i> Detalle de Compra #<?= $compra['nro_compra'] ?></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= $URL ?>">Inicio</a></li>
                        <li class="breadcrumb-item"><a href="<?= $URL ?>/compras">Compras</a></li>
                        <li class="breadcrumb-item active">Detalle</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Información de la Compra</h3>
                            <div class="card-tools">
                                <a href="<?= $URL ?>/compras" class="btn btn-outline-light">
                                    <i class="fas fa-arrow-left"></i> Volver
                                </a>
                                <button type="button" class="btn btn-outline-light" onclick="imprimirComprobante()">
                                    <i class="fas fa-print"></i> Imprimir
                                </button>
                            </div>
                        </div>
                        
                        <div class="card-body">
                            <div class="row">
                                <!-- Información general -->
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header bg-light">
                                            <h3 class="card-title">Datos de la Compra</h3>
                                        </div>
                                        <div class="card-body">
                                            <table class="table table-bordered">
                                                <tr>
                                                    <th style="width: 40%">Nro. Compra:</th>
                                                    <td><?= $compra['nro_compra'] ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Fecha de Compra:</th>
                                                    <td><?= date('d/m/Y', strtotime($compra['fecha_compra'])) ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Comprobante:</th>
                                                    <td><?= $compra['comprobante'] ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Registrado por:</th>
                                                    <td><?= htmlspecialchars($compra['nombre_usuario']) ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Fecha de Registro:</th>
                                                    <td><?= date('d/m/Y H:i:s', strtotime($compra['fyh_creacion'])) ?></td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Información del proveedor -->
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header bg-light">
                                            <h3 class="card-title">Datos del Proveedor</h3>
                                        </div>
                                        <div class="card-body">
                                            <table class="table table-bordered">
                                                <tr>
                                                    <th style="width: 40%">Nombre:</th>
                                                    <td><?= htmlspecialchars($compra['nombre_proveedor']) ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Empresa:</th>
                                                    <td><?= htmlspecialchars($compra['empresa_proveedor']) ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Teléfono:</th>
                                                    <td><?= htmlspecialchars($compra['celular_proveedor']) ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Dirección:</th>
                                                    <td><?= htmlspecialchars($compra['direccion_proveedor']) ?></td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mt-4">
                                <!-- Detalles del producto -->
                                <div class="col-md-12">
                                    <div class="card">
                                        <div class="card-header bg-light">
                                            <h3 class="card-title">Producto Comprado</h3>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <table class="table table-bordered">
                                                        <tr>
                                                            <th style="width: 40%">Código:</th>
                                                            <td><?= htmlspecialchars($compra['codigo_producto']) ?></td>
                                                        </tr>
                                                        <tr>
                                                            <th>Nombre:</th>
                                                            <td><?= htmlspecialchars($compra['nombre_producto']) ?></td>
                                                        </tr>
                                                        <tr>
                                                            <th>Descripción:</th>
                                                            <td><?= htmlspecialchars($compra['descripcion_producto'] ?? 'Sin descripción') ?></td>
                                                        </tr>
                                                    </table>
                                                </div>
                                                <div class="col-md-6">
                                                    <table class="table table-bordered">
                                                        <tr>
                                                            <th style="width: 40%">Cantidad:</th>
                                                            <td><?= $compra['cantidad'] ?> unidades</td>
                                                        </tr>
                                                        <tr>
                                                            <th>Precio Unitario:</th>
                                                            <td>$<?= number_format($compra['precio_compra'], 2) ?></td>
                                                        </tr>
                                                        <tr>
                                                            <th>Total:</th>
                                                            <td>
                                                                <strong>$<?= number_format($total, 2) ?></strong>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Script para imprimir comprobante -->
<script>
function imprimirComprobante() {
    window.print();
}
</script>

<?php include_once '../layout/parte2.php'; ?>