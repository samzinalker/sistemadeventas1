<?php
include '../app/config.php'; // $URL, $pdo, $fechaHora
include '../layout/sesion.php'; // Verifica sesión, establece $id_usuario_sesion, $rol_sesion, etc.

// Incluir el controlador que obtiene los datos de las compras
include '../app/controllers/compras/listado_de_compras.php'; 
// Ahora la variable $compras_datos está disponible aquí con la lista de compras.

include '../layout/parte1.php'; // Cabecera HTML, CSS, jQuery, y menú lateral

// Manejo de mensajes de feedback (éxito/error) que puedan venir de otras acciones (ej. creación)
include '../layout/mensajes.php'; 
?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Mis Compras Registradas</h1>
                </div><!-- /.col -->
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?php echo $URL;?>/">Inicio</a></li>
                        <li class="breadcrumb-item active">Mis Compras</li>
                    </ol>
                </div><!-- /.col -->
            </div><!-- /.row -->
        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->

    <!-- Main content -->
    <div class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card card-outline card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Listado de Compras</h3>
                            <div class="card-tools">
                                <a href="<?php echo $URL; ?>/compras/create.php" class="btn btn-success">
                                    <i class="fas fa-plus-circle"></i> Registrar Nueva Compra
                                </a>
                            </div>
                        </div>
                        <!-- /.card-header -->
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="tabla_compras_listado" class="table table-bordered table-striped table-hover table-sm">
                                    <thead class="thead-light">
                                        <tr>
                                            <th style="width: 30px;"><center>#</center></th>
                                            <th><center>Nro. Compra (Ref.)</center></th>
                                            <th><center>Fecha Compra</center></th>
                                            <th><center>Proveedor</center></th>
                                            <th><center>Comprobante</center></th>
                                            <th><center>Total Compra</center></th>
                                            <th><center>Registrado</center></th>
                                            <th style="width: 100px;"><center>Acciones</center></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $contador_compras = 0;
                                        if (!empty($compras_datos)) {
                                            foreach ($compras_datos as $compra) {
                                                $contador_compras++;
                                        ?>
                                                <tr id="fila_compra_<?php echo $compra['id_compra']; ?>"> 
                                                    <td><center><?php echo $contador_compras; ?></center></td>
                                                    <td><?php echo htmlspecialchars($compra['codigo_compra_referencia']); ?></td>
                                                    <td>
                                                        <center>
                                                        <?php 
                                                            // Formatear la fecha para mejor legibilidad
                                                            $fecha_compra_obj = DateTime::createFromFormat('Y-m-d', $compra['fecha_compra']);
                                                            echo $fecha_compra_obj ? $fecha_compra_obj->format('d/m/Y') : htmlspecialchars($compra['fecha_compra']);
                                                        ?>
                                                        </center>
                                                    </td>
                                                    <td>
                                                        <?php echo htmlspecialchars($compra['nombre_proveedor']); ?>
                                                        <?php if (!empty($compra['empresa_proveedor'])): ?>
                                                            <br><small class="text-muted">(<?php echo htmlspecialchars($compra['empresa_proveedor']); ?>)</small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($compra['comprobante'] ?: 'N/A'); ?></td>
                                                    <td class="text-right">
                                                        <?php echo number_format((float)$compra['total_general'], 2, '.', ','); ?>
                                                    </td>
                                                    <td>
                                                        <center>
                                                        <?php
                                                            $fyh_creacion_obj = DateTime::createFromFormat('Y-m-d H:i:s', $compra['fyh_creacion']);
                                                            echo $fyh_creacion_obj ? $fyh_creacion_obj->format('d/m/Y H:i') : htmlspecialchars($compra['fyh_creacion']);
                                                        ?>
                                                        </center>
                                                    </td>
                                                    <td>
                                                        <center>
                                                            <div class="btn-group">
                                                                <a href="<?php echo $URL; ?>/compras/show.php?id=<?php echo $compra['id_compra']; ?>" class="btn btn-info btn-xs" title="Ver Detalles">
                                                                    <i class="fas fa-eye"></i>
                                                                </a>
                                                                
                                                                <a href="<?php echo $URL; ?>/compras/edit.php?id=<?php echo $compra['id_compra']; ?>" class="btn btn-success btn-xs" title="Editar Compra">
                                                                <i class="fas fa-edit"></i>
                                                                </a>
                                                                <button type="button" class="btn btn-danger btn-xs btn-delete-compra" 
                                                                        data-id="<?php echo $compra['id_compra']; ?>" 
                                                                        data-codigo="<?php echo htmlspecialchars($compra['codigo_compra_referencia']); ?>"
                                                                        title="Eliminar Compra">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </div>
                                                        </center>
                                                    </td>
                                                </tr>
                                        <?php
                                            }
                                        } else {
                                        ?>
                                            <tr>
                                                <td colspan="8"><center>No tienes compras registradas actualmente.</center></td>
                                            </tr>
                                        <?php
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div> <!-- /.table-responsive -->
                        </div>
                        <!-- /.card-body -->
                    </div>
                    <!-- /.card -->
                </div>
            </div>
            <!-- /.row -->
        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content -->
</div>
<!-- /.content-wrapper -->
<!-- Modal para Confirmar Eliminación de Compra -->
<div class="modal fade" id="modal-delete-compra" tabindex="-1" role="dialog" aria-labelledby="modalDeleteCompraLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="modalDeleteCompraLabel">Confirmar Eliminación</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="id_compra_delete_hidden">
                <p>¿Está seguro de eliminar la compra: <strong id="codigo_compra_delete_display"></strong>?</p>
                <p class="text-danger"><small><strong>Advertencia:</strong> Esta acción también revertirá el stock de los productos asociados a esta compra. Esta acción no se puede deshacer.</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="btn_confirmar_delete_compra">Eliminar Compra</button>
            </div>
        </div>
    </div>
</div>

<?php include '../layout/parte2.php'; // Pie de página, JS global ?>

<!-- Script específico para esta página (DataTables) -->
<script>
$(document).ready(function() {
    var tablaCompras = $('#tabla_compras_listado').DataTable({
        "responsive": true,
        "lengthChange": true,
        "autoWidth": false,
        "pageLength": 10, 
        "buttons": [
            { extend: 'copy', text: '<i class="fas fa-copy"></i> Copiar', className: 'btn-sm' },
            { extend: 'excel', text: '<i class="fas fa-file-excel"></i> Excel', className: 'btn-sm' },
            { extend: 'pdf', text: '<i class="fas fa-file-pdf"></i> PDF', className: 'btn-sm', orientation: 'landscape' },
            { extend: 'print', text: '<i class="fas fa-print"></i> Imprimir', className: 'btn-sm' }
        ],
        "language": { /* Tu configuración de idioma para DataTables */
            "sProcessing":     "Procesando...",
            "sLengthMenu":     "Mostrar _MENU_ registros",
            "sZeroRecords":    "No se encontraron resultados",
            "sEmptyTable":     "Ninguna compra registrada",
            "sInfo":           "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
            "sInfoEmpty":      "Mostrando registros del 0 al 0 de un total de 0 registros",
            "sInfoFiltered":   "(filtrado de un total de _MAX_ registros)",
            "sInfoPostFix":    "",
            "sSearch":         "Buscar:",
            "sUrl":            "",
            "sInfoThousands":  ",",
            "sLoadingRecords": "Cargando...",
            "oPaginate": {
                "sFirst":    "Primero",
                "sLast":     "Último",
                "sNext":     "Siguiente",
                "sPrevious": "Anterior"
            },
            "oAria": {
                "sSortAscending":  ": Activar para ordenar la columna de manera ascendente",
                "sSortDescending": ": Activar para ordenar la columna de manera descendente"
            },
            "buttons": {
                "copyTitle": 'Copiado al Portapapeles',
                "copySuccess": {
                    _: '%d filas copiadas',
                    1: '1 fila copiada'
                }
            }
        }
    });
    tablaCompras.buttons().container().appendTo('#tabla_compras_listado_wrapper .col-md-6:eq(0)');

    // Lógica para el botón de eliminar compra
    $('#tabla_compras_listado tbody').on('click', '.btn-delete-compra', function () {
        var idCompra = $(this).data('id');
        var codigoCompra = $(this).data('codigo');
        
        $('#id_compra_delete_hidden').val(idCompra);
        $('#codigo_compra_delete_display').text(codigoCompra);
        $('#modal-delete-compra').modal('show');
    });

    // Lógica para confirmar la eliminación de la compra
    $('#btn_confirmar_delete_compra').on('click', function () {
        var idCompra = $('#id_compra_delete_hidden').val();
        
        // Deshabilitar botón para evitar múltiples clics
        $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Eliminando...');

        $.ajax({
            url: '<?php echo $URL; ?>/app/controllers/compras/controller_delete_compra.php',
            type: 'POST',
            data: { id_compra: idCompra },
            dataType: 'json',
            success: function(response) {
                $('#modal-delete-compra').modal('hide');
                if (response.status === 'success') {
                    Swal.fire({
                        title: '¡Eliminado!',
                        text: response.message,
                        icon: 'success',
                        timer: 2500,
                        showConfirmButton: false
                    }).then(() => {
                        // Opción 1: Recargar toda la página (si la eliminación de DataTables falla o se prefiere)
                        // location.reload(); 
                        
                        // Opción 2: Eliminar la fila de DataTables y redibujar (más eficiente)
                        // Asegúrate que la variable idCompra esté disponible aquí.
                        console.log("Intentando eliminar fila: #fila_compra_" + idCompra);
                        console.log("Elemento encontrado:", $('#fila_compra_' + idCompra).length);
                        if (tablaCompras && typeof tablaCompras.row === 'function' && $('#fila_compra_' + idCompra).length > 0) {
                           tablaCompras.row('#fila_compra_' + idCompra).remove().draw(false);
                        } else {
                           console.error("No se pudo eliminar la fila de DataTables, recargando página.");
                           location.reload(); // Fallback si no se puede eliminar la fila
                        }
                    });
                } else {
                    Swal.fire('Error', response.message || 'No se pudo eliminar la compra.', 'error');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                $('#modal-delete-compra').modal('hide');
                console.error("Error AJAX al eliminar compra:", textStatus, errorThrown, jqXHR.responseText);
                Swal.fire('Error de Conexión', 'No se pudo conectar con el servidor.', 'error');
            },
            complete: function() {
                // Volver a habilitar el botón
                $('#btn_confirmar_delete_compra').prop('disabled', false).html('Eliminar Compra');
            }
        });
    });
});
</script>