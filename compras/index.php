<?php
include ('../app/config.php');
include ('../app/utils/funciones_globales.php');
include ('../layout/sesion.php'); // Verifica sesión y roles si es necesario
include ('../layout/parte1.php'); // Cabecera HTML, CSS, y menú

// Para el menú lateral activo (opcional, si tu layout/parte1.php lo usa)
// $modulo_abierto = 'compras';
// $pagina_activa = 'compras_listado';

// --- Obtener listado de compras (simplificado, el controlador AJAX hará el trabajo pesado) ---
// En esta página principal, podríamos cargar las compras directamente o, para mantener la consistencia
// con un enfoque AJAX, la tabla podría llenarse vía AJAX al cargar. Por ahora, la dejaremos
// para que el controlador AJAX la pueble.
?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-12">
                <h1 class="m-0">Listado de Compras
                        <a href="create.php" class="btn btn-primary ml-2"> <!-- Añadido ml-2 para un pequeño margen -->
                           <i class="fa fa-plus"></i> Registrar Nueva Compra
                        </a>
                        <a href="reporte_compras_pdf.php" class="btn btn-danger ml-2" target="_blank">
                           <i class="fas fa-file-pdf"></i> Generar Reporte PDF
                        </a>
                    </h1>
                </div>
            </div>
        </div>
    </div>

    <!-- Main content -->
    <div class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card card-outline card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Compras Registradas</h3>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="tabla_compras" class="table table-bordered table-striped table-sm">
                                    <thead>
                                    <tr>
                                        <th><center>ID</center></th>
                                        <th>Nro. Comprobante Prov.</th>
                                        <th>Proveedor</th>
                                        <th>Fecha Compra</th>
                                        <th>Usuario Registra</th>
                                        <th>Subtotal Neto</th>
                                        <th>IVA (%)</th>
                                        <th>Monto IVA</th>
                                        <th>Monto Total</th>
                                        <th>Estado</th>
                                        <th><center>Acciones</center></th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Los datos se cargarán vía AJAX -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL PARA VER DETALLE DE COMPRA -->
    <div class="modal fade" id="modal-ver-detalle-compra" tabindex="-1" role="dialog" aria-labelledby="modalVerDetalleCompraLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalVerDetalleCompraLabel">Detalle de Compra Nro: <span id="detalle_nro_compra_modal"></span></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div id="info_compra_maestro">
                        <!-- Aquí se mostrarán datos del maestro de la compra -->
                    </div>
                    <hr>
                    <h5>Productos:</h5>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Cantidad</th>
                                    <th>Precio Unit.</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody id="tabla_detalle_productos_compra">
                                <!-- Detalles de productos se cargarán aquí -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-info" id="btn_imprimir_compra_modal"><i class="fa fa-print"></i> Imprimir</button>
                </div>
            </div>
        </div>
    </div>

</div>
<!-- /.content-wrapper -->

<?php include ('../layout/mensajes.php'); ?>
<?php include ('../layout/parte2.php'); ?>

<script>
$(document).ready(function () {
    var tablaCompras = $("#tabla_compras").DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": {
            "url": "../app/controllers/compras/acciones_compras.php",
            "type": "POST",
            "data": { accion: "listar_compras" } // Acción para el controlador
        },
        "columns": [
            { "data": "id_compra", "className": "text-center" },
            { "data": "nro_comprobante_proveedor" },
            { "data": "nombre_proveedor" }, // Asumiendo que el backend hace JOIN con tb_proveedores
            { "data": "fecha_compra" },
            { "data": "nombre_usuario_registra" }, // Asumiendo JOIN con tb_usuarios
            { "data": "subtotal_neto", "className": "text-right", "render": $.fn.dataTable.render.number(',', '.', 2, '') },
            { "data": "porcentaje_iva", "className": "text-center", "render": function(data) { return parseFloat(data).toFixed(2) + '%';} },
            { "data": "monto_iva", "className": "text-right", "render": $.fn.dataTable.render.number(',', '.', 2, '') },
            { "data": "monto_total", "className": "text-right", "render": $.fn.dataTable.render.number(',', '.', 2, '') },
            { "data": "estado", "className": "text-center", "render": function(data){
                let badgeClass = data === 'ANULADA' ? 'badge-danger' : 'badge-success';
                return '<span class="badge '+badgeClass+'">'+data+'</span>';
            }},
            { "data": null, "className": "text-center", "orderable": false, "searchable": false, "render": function (data, type, row) {
                var botones = '<div class="btn-group">';
                botones += '<button type="button" class="btn btn-info btn-xs btn-ver-detalle" data-id="'+row.id_compra+'" title="Ver Detalle"><i class="fa fa-eye"></i></button>';
                if (row.estado !== 'ANULADA') {
                    botones += '<button type="button" class="btn btn-warning btn-xs btn-anular-compra" data-id="'+row.id_compra+'" title="Anular Compra"><i class="fa fa-ban"></i></button>';
                }
                // Podrías añadir un botón de imprimir aquí también o usar el del modal
                botones += '</div>';
                return botones;
            }}
        ],
        "pageLength": 10,
        "language": { /* ... tu config de idioma DataTables ... */ 
            "emptyTable": "No hay compras registradas",
            "info": "Mostrando _START_ a _END_ de _TOTAL_ compras",
            // ... más traducciones ...
        },
        "responsive": true, "lengthChange": true, "autoWidth": false,
        "buttons": ["copy", "excel", "pdf", "print", "colvis"] // Configura los botones que necesites
    }).buttons().container().appendTo('#tabla_compras_wrapper .col-md-6:eq(0)');

    function mostrarAlerta(title, text, icon, callback) {
        Swal.fire({
            title: title, text: text, icon: icon,
            timer: icon === 'success' ? 2500 : 4000,
            showConfirmButton: icon !== 'success',
            allowOutsideClick: false, allowEscapeKey: false
        }).then((result) => {
            if (callback && typeof callback === 'function') {
                callback();
            }
        });
    }

    // --- Lógica para VER DETALLE de Compra ---
    $('#tabla_compras tbody').on('click', '.btn-ver-detalle', function () {
        var id_compra = $(this).data('id');
        $('#detalle_nro_compra_modal').text(id_compra); // O el nro de comprobante si lo prefieres
        $('#info_compra_maestro').html('Cargando datos del maestro...');
        $('#tabla_detalle_productos_compra').html('<tr><td colspan="4" class="text-center">Cargando productos...</td></tr>');

        $.ajax({
            url: "../app/controllers/compras/acciones_compras.php",
            type: "POST",
            data: { accion: "get_detalle_compra", id_compra: id_compra },
            dataType: "json",
            success: function(response) {
                if (response.status === 'success' && response.data_maestro && response.data_detalle) {
                    let maestro = response.data_maestro;
                    let infoHtml = `
                        <p><strong>Proveedor:</strong> ${maestro.nombre_proveedor || 'N/A'}</p>
                        <p><strong>Nro. Comprobante Prov.:</strong> ${maestro.nro_comprobante_proveedor || 'N/A'}</p>
                        <p><strong>Fecha Compra:</strong> ${maestro.fecha_compra}</p>
                        <p><strong>Registrado por:</strong> ${maestro.nombre_usuario_registra || 'N/A'}</p>
                        <p><strong>Observaciones:</strong> ${maestro.observaciones || 'Ninguna'}</p>
                        <p><strong>Aplica IVA:</strong> ${maestro.aplica_iva == 1 ? 'Sí' : 'No'} | <strong>% IVA:</strong> ${parseFloat(maestro.porcentaje_iva).toFixed(2)}%</p>
                        <p><strong>Subtotal Neto:</strong> ${parseFloat(maestro.subtotal_neto).toFixed(2)}</p>
                        <p><strong>Monto IVA:</strong> ${parseFloat(maestro.monto_iva).toFixed(2)}</p>
                        <p><strong>TOTAL COMPRA:</strong> ${parseFloat(maestro.monto_total).toFixed(2)}</p>
                    `;
                    $('#info_compra_maestro').html(infoHtml);

                    let detalleHtml = '';
                    if (response.data_detalle.length > 0) {
                        response.data_detalle.forEach(function(item){
                            detalleHtml += `
                                <tr>
                                    <td>${item.nombre_producto}</td>
                                    <td class="text-center">${parseFloat(item.cantidad).toFixed(2)}</td>
                                    <td class="text-right">${parseFloat(item.precio_compra_unitario).toFixed(2)}</td>
                                    <td class="text-right">${parseFloat(item.subtotal).toFixed(2)}</td>
                                </tr>`;
                        });
                    } else {
                        detalleHtml = '<tr><td colspan="4" class="text-center">No hay productos en esta compra.</td></tr>';
                    }
                    $('#tabla_detalle_productos_compra').html(detalleHtml);
                    $('#modal-ver-detalle-compra').modal('show');
                } else {
                    mostrarAlerta('Error', response.message || 'No se pudo cargar el detalle.', 'error');
                }
            },
            error: function() {
                mostrarAlerta('Error de Conexión', 'No se pudo obtener el detalle de la compra.', 'error');
            }
        });
    });

    // --- Lógica para ANULAR Compra ---
    $('#tabla_compras tbody').on('click', '.btn-anular-compra', function () {
        var id_compra = $(this).data('id');
        Swal.fire({
            title: '¿Está seguro?',
            text: "Esta acción anulará la compra Nro. " + id_compra + " y revertirá el stock de los productos. ¡No podrá deshacer esto!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, ¡Anular Compra!',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "../app/controllers/compras/acciones_compras.php",
                    type: "POST",
                    data: { accion: "anular_compra", id_compra: id_compra },
                    dataType: "json",
                    success: function(response) {
                        if (response.status === 'success') {
                            mostrarAlerta('¡Anulada!', response.message, 'success', function() {
                                tablaCompras.ajax.reload(null, false); // Recargar DataTables sin resetear paginación
                            });
                        } else {
                            mostrarAlerta('Error', response.message || 'No se pudo anular la compra.', 'error');
                        }
                    },
                    error: function() {
                        mostrarAlerta('Error de Conexión', 'No se pudo contactar al servidor para anular.', 'error');
                    }
                });
            }
        });
    });
    
    // --- Lógica para IMPRIMIR Compra (desde el modal de detalle) ---
    $('#btn_imprimir_compra_modal').click(function() {
    // Aquí implementarías la lógica para imprimir.
    // Podrías generar un PDF en el servidor o usar window.print() para una versión simple.
    //Ejemplo simple:
        var contenidoModal = $('#modal-ver-detalle-compra .modal-body').html();
        var ventanaImpresion = window.open('', '_blank');
     ventanaImpresion.document.write('<html><head><title>Detalle Compra</title>');
     // Opcional: enlazar un CSS para impresión
        ventanaImpresion.document.write('</head><body>' + contenidoModal + '</body></html>');
        ventanaImpresion.document.close();
        ventanaImpresion.print();
        mostrarAlerta('Información', 'La funcionalidad de imprimir aún no está implementada completamente.', 'info');
    });

});




    // --- LÓGICA PARA EL MODAL DE NUEVO PRODUCTO EN ALMACÉN ---
    // Resumen: Controla la apertura del modal para crear un nuevo producto en almacén y
    // el envío de su formulario. Si tiene éxito, añade el nuevo producto a la tabla de compra.

    // Abrir el modal de nuevo producto en almacén
    $('#btn_abrir_modal_nuevo_producto_almacen').click(function() {
        $('#form-nuevo-producto-almacen-modal')[0].reset(); // Limpiar formulario
        $('#preview_imagen_prod_almacen_modal').hide().attr('src', '#');
        $('#error_message_prod_almacen_modal').hide().text('');
        // Establecer fecha de ingreso por defecto (si no lo hace el HTML por defecto)
        if (!$('#fecha_ingreso_prod_almacen_modal').val()) {
            var today = new Date().toISOString().split('T')[0];
            $('#fecha_ingreso_prod_almacen_modal').val(today);
        }
        // Aquí podrías cargar las categorías para el select si no están ya en el HTML
        // o si necesitas recargarlas dinámicamente.
        $('#modal-nuevo-producto-almacen').modal('show');
    });

    // Vista previa de imagen para el modal de nuevo producto almacén
    $('#imagen_prod_almacen_modal').change(function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) { $('#preview_imagen_prod_almacen_modal').attr('src', e.target.result).show(); }
            reader.readAsDataURL(file);
        } else { $('#preview_imagen_prod_almacen_modal').hide(); }
    });
    
    // Limpiar modal de nuevo producto almacén al cerrarse
    $('#modal-nuevo-producto-almacen').on('hidden.bs.modal', function () {
        $('#form-nuevo-producto-almacen-modal')[0].reset();
        $('#preview_imagen_prod_almacen_modal').hide().attr('src', '#');
        $('#error_message_prod_almacen_modal').hide().text('');
    });

    // Enviar formulario del modal de nuevo producto en almacén
    $('#form-nuevo-producto-almacen-modal').submit(function(e) {
        e.preventDefault();
        $('#error_message_prod_almacen_modal').hide().text('');
        var formData = new FormData(this);
        // El controlador create_producto.php ya está preparado para recibir estos nombres de campo.

        $('#btn_guardar_nuevo_prod_almacen_modal').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');

        $.ajax({
            url: "../app/controllers/almacen/create_producto.php", // Reutilizamos el controlador existente
            type: "POST",
            data: formData,
            contentType: false,
            processData: false,
            dataType: "json",
            success: function(response) {
                if (response.status === 'success') {
                    $('#modal-nuevo-producto-almacen').modal('hide');
                    mostrarAlerta('Producto Guardado', response.message, 'success');
                    
                    // Ahora, necesitamos los datos del producto recién creado para añadirlo a la tabla de compras.
                    // El controlador create_producto.php debería devolver estos datos.
                    // Si no los devuelve, necesitaremos hacer otra llamada AJAX para obtenerlos por ID,
                    // o modificar create_producto.php para que los devuelva.
                    // Asumamos que response.new_product_data contiene lo necesario.
                    // El controlador actual de almacen/create_producto.php no devuelve el producto creado,
                    // solo un ID. Necesitaremos ajustarlo o hacer una llamada GET.
                    // Por ahora, mostraremos un mensaje y el usuario lo buscará manualmente.
                    
                    if (response.new_data && response.new_data.id_producto) {
                         // Formatear para que coincida con lo que espera agregarProductoATabla
                        var productoParaTabla = {
                            id_producto: response.new_data.id_producto,
                            codigo: response.new_data.codigo,
                            nombre: response.new_data.nombre,
                            precio_compra_sugerido: response.new_data.precio_compra, // Asumiendo que este es el precio de compra
                            stock_actual: response.new_data.stock 
                        };
                        agregarProductoATabla(productoParaTabla);
                    } else if (response.id_producto_creado) { // Si solo devuelve el ID
                        // Haríamos un get_producto para luego añadirlo
                         mostrarAlerta('Producto Creado', 'Producto creado con ID: ' + response.id_producto_creado + '. Búscalo para añadirlo a la compra.', 'info');
                         // Implementación de get y luego add:
                         /*
                         $.ajax({
                            url: "../app/controllers/almacen/get_producto.php",
                            type: "GET",
                            data: { id_producto: response.id_producto_creado },
                            dataType: "json",
                            success: function(getResp) {
                                if (getResp.status === 'success' && getResp.data) {
                                    var productoParaTabla = {
                                        id_producto: getResp.data.id_producto,
                                        codigo: getResp.data.codigo,
                                        nombre: getResp.data.nombre,
                                        precio_compra_sugerido: getResp.data.precio_compra,
                                        stock_actual: getResp.data.stock 
                                    };
                                    agregarProductoATabla(productoParaTabla);
                                }
                            }
                         });
                         */
                    } else {
                         mostrarAlerta('Producto Creado', 'El producto ha sido creado en el almacén. Ahora puedes buscarlo y añadirlo a la compra.', 'info');
                    }

                } else {
                    $('#error_message_prod_almacen_modal').text(response.message || 'Error desconocido.').show();
                }
            },
            error: function() {
                $('#error_message_prod_almacen_modal').text('Error de conexión con el servidor.').show();
            },
            complete: function() {
                $('#btn_guardar_nuevo_prod_almacen_modal').prop('disabled', false).html('Guardar Producto');
            }
        });
    });
</script>