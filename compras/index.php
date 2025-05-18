<?php
// Activar modo de depuración para desarrollo
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    require_once '../app/config.php';
    require_once '../app/controllers/compras/CompraController.php';

    // Configuración de página
    $modulo_abierto = 'compras';
    $pagina_activa = 'compras';

    // Incluir sesión y layout
    include_once '../layout/sesion.php';
    include_once '../layout/parte1.php';

    // Instanciar controlador y obtener datos (con manejo de errores)
    $controller = new CompraController($pdo);
    
    try {
        // Obtener datos con límite para mejorar rendimiento
        $data = $controller->index(true);
        $compras = $data['compras'] ?? [];
        $stats = $data['stats'] ?? [
            'total' => ['count' => 0, 'total' => 0],
            'month' => ['count' => 0, 'total' => 0],
            'week' => ['count' => 0, 'total' => 0],
            'today' => ['count' => 0, 'total' => 0]
        ];
    } catch (Exception $e) {
        // Log del error
        error_log("Error al cargar datos de compras: " . $e->getMessage());
        
        // Datos fallback en caso de error
        $compras = [];
        $stats = [
            'total' => ['count' => 0, 'total' => 0],
            'month' => ['count' => 0, 'total' => 0],
            'week' => ['count' => 0, 'total' => 0],
            'today' => ['count' => 0, 'total' => 0]
        ];
        
        // Notificar al usuario
        echo '<div class="alert alert-warning">
            <h5><i class="icon fas fa-exclamation-triangle"></i> Atención</h5>
            Ocurrió un problema al cargar los datos. Se muestra información limitada.
        </div>';
    }
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
            
            <!-- Buscador simplificado -->
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
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Producto o proveedor:</label>
                                    <input type="text" class="form-control" id="searchTerm" placeholder="Nombre, código o empresa">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Fecha:</label>
                                    <input type="date" class="form-control" id="searchFecha">
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
            
            <!-- Lista de compras simplificada -->
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Listado de Compras</h3>
                    <div class="card-tools">
                        <a href="create.php" class="btn btn-outline-light">
                            <i class="fas fa-plus"></i> Nueva Compra
                        </a>
                    </div>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-bordered table-striped table-hover">
                        <thead>
                            <tr>
                                <th width="5%">#</th>
                                <th width="10%">Nro.</th>
                                <th width="10%">Fecha</th>
                                <th width="25%">Producto</th>
                                <th width="10%">Cantidad</th>
                                <th width="10%">Precio</th>
                                <th width="10%">Total</th>
                                <th width="10%">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $contador = 1;
                            foreach ($compras as $compra): 
                                $total = floatval($compra['precio_compra']) * intval($compra['cantidad']);
                            ?>
                            <tr>
                                <td class="text-center"><?= $contador++ ?></td>
                                <td><?= $compra['nro_compra'] ?></td>
                                <td><?= date('d/m/Y', strtotime($compra['fecha_compra'])) ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($compra['codigo_producto']) ?></strong>: 
                                    <?= htmlspecialchars($compra['nombre_producto']) ?>
                                </td>
                                <td class="text-center"><?= number_format($compra['cantidad']) ?></td>
                                <td class="text-right">$<?= number_format(floatval($compra['precio_compra']), 2) ?></td>
                                <td class="text-right">$<?= number_format($total, 2) ?></td>
                                <td class="text-center">
                                    <a href="show.php?id=<?= $compra['id_compra'] ?>" class="btn btn-info btn-sm">
                                        <i class="fas fa-eye"></i> Ver
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <?php if(empty($compras)): ?>
                            <tr>
                                <td colspan="8" class="text-center">
                                    No hay compras registradas
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="card-footer">
                    <a href="create.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Registrar Nueva Compra
                    </a>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
$(document).ready(function() {
    // Manejo global de errores AJAX
    $(document).ajaxError(function(event, jqxhr, settings, thrownError) {
        console.error("Error AJAX:", thrownError);
        mostrarAlerta('Ocurrió un error en la comunicación con el servidor', 'danger');
    });
    
    // Función para mostrar alertas
    function mostrarAlerta(mensaje, tipo) {
        $("#alertaContainer").html(`
            <div class="alert alert-${tipo} alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                <h5><i class="icon fas fa-${tipo === 'success' ? 'check' : 'exclamation-triangle'}"></i> Atención</h5>
                ${mensaje}
            </div>
        `);
        
        // Auto-ocultar después de 5 segundos
        setTimeout(function() {
            $("#alertaContainer .alert").fadeOut();
        }, 5000);
    }
    
    // Búsqueda de compras
    $("#searchForm").submit(function(e) {
        e.preventDefault();
        
        var term = $("#searchTerm").val();
        var fecha = $("#searchFecha").val();
        
        if (!term && !fecha) {
            mostrarAlerta('Ingrese al menos un criterio de búsqueda', 'warning');
            return;
        }
        
        // Simular búsqueda con animación
        $("tbody").html('<tr><td colspan="8" class="text-center"><i class="fas fa-spinner fa-spin"></i> Buscando...</td></tr>');
        
        // Usar setTimeout para evitar bloqueos de UI
        setTimeout(function() {
            window.location.reload(); // Temporal: recargar para evitar problemas de rendimiento
        }, 500);
    });
    
    // Resetear búsqueda
    $("#resetSearch").click(function() {
        $("#searchTerm").val('');
        $("#searchFecha").val('');
    });
});
</script>

<?php 
    include_once '../layout/parte2.php';
} catch (Exception $e) {
    // Mostrar error amigable
    echo '<div class="alert alert-danger m-3">
        <h4><i class="icon fas fa-exclamation-triangle"></i> Error:</h4>
        <p>Ha ocurrido un problema al cargar la página. Por favor, inténtelo de nuevo más tarde.</p>';
    
    // Solo mostrar detalles técnicos si estamos en desarrollo
    if (defined('ENTORNO') && ENTORNO === 'desarrollo') {
        echo '<p><small>Detalles técnicos: ' . htmlspecialchars($e->getMessage()) . '</small></p>';
    }
    
    echo '</div>';
    
    // Registrar el error
    error_log("Error en compras/index.php: " . $e->getMessage() . "\n" . $e->getTraceAsString());
}
?>