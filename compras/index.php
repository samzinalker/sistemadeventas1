<?php
/**
 * Listado de Compras - Muestra todas las compras registradas con opciones
 * para ver detalles, anular compras y generar reportes PDF.
 */

// Inicialización y configuración
require_once '../app/config.php';
session_start();

// Verificación de sesión
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../login.php');
    exit;
}

$id_usuario = $_SESSION['id_usuario'];
$modulo_abierto = 'compras';
$pagina_activa = 'compras';

// Obtener listado de compras
$sql = "SELECT c.*, p.nombre_proveedor FROM compras c 
        INNER JOIN tb_proveedores p ON c.id_proveedor = p.id_proveedor 
        WHERE c.id_usuario = ? ORDER BY c.fecha_compra DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_usuario]);
$compras = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Incluir cabecera
include '../layout/parte1.php';
?>

<div class="content-wrapper">
    <!-- Cabecera -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Gestión de Compras</h1>
                </div>
            </div>
        </div>
    </section>

    <!-- Contenido principal -->
    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Listado de Compras</h3>
                    <div class="card-tools">
                        <a href="create.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Nueva Compra
                        </a>
                        <button type="button" id="btnGenerarPDF" class="btn btn-danger btn-sm ml-2">
                            <i class="fas fa-file-pdf"></i> Generar PDF
                        </button>
                    </div>
                </div>
                
                <div class="card-body">
                    <table id="tablaCompras" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Fecha</th>
                                <th>Proveedor</th>
                                <th>Total</th>
                                <th>IVA</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($compras as $compra): ?>
                            <tr>
                                <td><?= $compra['id'] ?></td>
                                <td><?= date('d/m/Y', strtotime($compra['fecha_compra'])) ?></td>
                                <td><?= htmlspecialchars($compra['nombre_proveedor']) ?></td>
                                <td>$<?= number_format($compra['total'], 2) ?></td>
                                <td>$<?= number_format($compra['iva'], 2) ?></td>
                                <td>
                                    <?php if ($compra['estado'] == 1): ?>
                                        <span class="badge badge-success">Activo</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Anulado</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-info btn-sm btn-ver-detalle" data-id="<?= $compra['id'] ?>">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    
                                    <?php if ($compra['estado'] == 1): ?>
                                    <button class="btn btn-danger btn-sm btn-anular" data-id="<?= $compra['id'] ?>">
                                        <i class="fas fa-ban"></i>
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Modal Detalle Compra -->
<div class="modal fade" id="modalDetalleCompra" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalle de Compra</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <p><strong>Proveedor:</strong> <span id="detalle-proveedor"></span></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Fecha:</strong> <span id="detalle-fecha"></span></p>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Producto</th>
                                <th>Cantidad</th>
                                <th>Precio</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody id="tablaDetalleCompra">
                            <!-- Se llenará dinámicamente -->
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4" class="text-right"><strong>Subtotal:</strong></td>
                                <td id="detalle-subtotal"></td>
                            </tr>
                            <tr>
                                <td colspan="4" class="text-right"><strong>IVA:</strong></td>
                                <td id="detalle-iva"></td>
                            </tr>
                            <tr>
                                <td colspan="4" class="text-right"><strong>Total:</strong></td>
                                <td id="detalle-total"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Inicializar DataTable
    $('#tablaCompras').DataTable({
        "responsive": true,
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json"
        }
    });
    
    // Ver detalle de compra
    $('.btn-ver-detalle').click(function() {
        const idCompra = $(this).data('id');
        
        // Limpiar tabla de detalles
        $('#tablaDetalleCompra').empty();
        
        // Solicitar datos vía AJAX
        $.ajax({
            url: '../app/controllers/compras/obtener_detalle.php',
            type: 'GET',
            data: { id: idCompra },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    // Mostrar datos del encabezado
                    $('#detalle-proveedor').text(response.compra.nombre_proveedor);
                    $('#detalle-fecha').text(new Date(response.compra.fecha_compra).toLocaleDateString());
                    
                    // Calcular subtotal (total - iva)
                    const subtotal = parseFloat(response.compra.total) - parseFloat(response.compra.iva);
                    
                    // Mostrar detalles de productos
                    response.detalles.forEach(function(detalle) {
                        const subtotalLinea = detalle.cantidad * detalle.precio_compra;
                        
                        $('#tablaDetalleCompra').append(`
                            <tr>
                                <td>${detalle.codigo}</td>
                                <td>${detalle.nombre}</td>
                                <td>${detalle.cantidad}</td>
                                <td>$${parseFloat(detalle.precio_compra).toFixed(2)}</td>
                                <td>$${subtotalLinea.toFixed(2)}</td>
                            </tr>
                        `);
                    });
                    
                    // Mostrar totales
                    $('#detalle-subtotal').text('$' + subtotal.toFixed(2));
                    $('#detalle-iva').text('$' + parseFloat(response.compra.iva).toFixed(2));
                    $('#detalle-total').text('$' + parseFloat(response.compra.total).toFixed(2));
                    
                    // Mostrar modal
                    $('#modalDetalleCompra').modal('show');
                } else {
                    Swal.fire('Error', response.message || 'No se pudo cargar el detalle', 'error');
                }
            },
            error: function() {
                Swal.fire('Error', 'Error de comunicación con el servidor', 'error');
            }
        });
    });
    
    // Anular compra
    $('.btn-anular').click(function() {
        const idCompra = $(this).data('id');
        
        Swal.fire({
            title: '¿Está seguro?',
            text: "La compra será anulada y se revertirá el stock de los productos",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, anular',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '../app/controllers/compras/anular_compra.php',
                    type: 'POST',
                    data: { id: idCompra },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            Swal.fire('¡Anulada!', response.message, 'success')
                                .then(() => {
                                    location.reload();
                                });
                        } else {
                            Swal.fire('Error', response.message || 'No se pudo anular la compra', 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error', 'Error de comunicación con el servidor', 'error');
                    }
                });
            }
        });
    });
    
    // Generar PDF
    $('#btnGenerarPDF').click(function() {
        window.open('../app/controllers/compras/generar_reporte_pdf.php', '_blank');
    });
});
</script>

<?php include '../layout/parte2.php'; ?>