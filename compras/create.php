<?php
// --- INCLUDES Y CONFIGURACIÓN INICIAL ---
include ('../app/config.php');
include ('../app/utils/funciones_globales.php');
include ('../layout/sesion.php');
include ('../layout/parte1.php');

// --- CARGA DE DATOS NECESARIOS PARA EL FORMULARIO ---
require_once __DIR__ . '/../app/models/ComprasModel.php';
require_once __DIR__ . '/../app/models/CategoriaModel.php';

$comprasModel = new ComprasModel($pdo);
$categoriaModel = new CategoriaModel($pdo);

$proveedores_select = $comprasModel->getProveedoresActivosParaSelect($id_usuario_sesion);
$categorias_select_datos_almacen = $categoriaModel->getCategoriasByUsuarioId($id_usuario_sesion);
?>

<!-- Estilos Adicionales -->
<link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
<style>
    .ui-autocomplete { max-height: 200px; overflow-y: auto; overflow-x: hidden; z-index: 1052 !important; }
    .ui-menu-item { padding: 5px; } .ui-menu-item:hover { background-color: #f0f0f0; }
    #producto_busqueda { max-width: 300px; display: inline-block; margin-right: 5px; }
    input[type=number]::-webkit-inner-spin-button, input[type=number]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
    input[type=number] { -moz-appearance: textfield; }
    .loader { border: 4px solid #f3f3f3; border-radius: 50%; border-top: 4px solid #3498db; width: 20px; height: 20px; animation: spin 1s linear infinite; display: none; margin-left: 10px; vertical-align: middle; }
    @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    #tabla_productos_compra .btn-group .btn, #tabla_productos_almacen_modal .btn-xs { padding: .25rem .5rem; font-size: .8rem; }
</style>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2"><div class="col-sm-6"><h1 class="m-0">Registrar Nueva Compra</h1></div>
                <div class="col-sm-6"><ol class="breadcrumb float-sm-right"><li class="breadcrumb-item"><a href="<?php echo $URL; ?>/compras/">Listado</a></li><li class="breadcrumb-item active">Registrar</li></ol></div>
            </div>
        </div>
    </div>

    <!-- Main content -->
    <div class="content">
        <div class="container-fluid">
            <form id="form-registrar-compra" novalidate>
                <div class="row">
                    <!-- Datos Generales de la Compra -->
                    <div class="col-md-7">
                        <div class="card card-outline card-primary"><div class="card-header"><h3 class="card-title">Datos de la Compra</h3></div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-7 form-group"><label for="id_proveedor">Proveedor <span class="text-danger">*</span></label><select class="form-control" id="id_proveedor" name="id_proveedor" required><option value="">Seleccione...</option><?php foreach ($proveedores_select as $p): ?><option value="<?php echo $p['id_proveedor']; ?>"><?php echo sanear($p['nombre_proveedor']); ?></option><?php endforeach; ?></select></div>
                                    <div class="col-md-5 form-group"><label for="fecha_compra">Fecha Compra <span class="text-danger">*</span></label><input type="date" class="form-control" id="fecha_compra" name="fecha_compra" value="<?php echo date('Y-m-d'); ?>" required></div>
                                </div>
                                <div class="form-group"><label for="nro_comprobante_proveedor">Nro. Comprobante Proveedor</label><input type="text" class="form-control" id="nro_comprobante_proveedor" name="nro_comprobante_proveedor" placeholder="Ej: F001-002"></div>
                                <div class="form-group"><label for="observaciones_compra">Observaciones</label><textarea class="form-control" id="observaciones_compra" name="observaciones_compra" rows="2" placeholder="Notas..."></textarea></div>
                            </div>
                        </div>
                    </div>
                    <!-- Impuestos y Totales -->
                    <div class="col-md-5">
                        <div class="card card-outline card-info"><div class="card-header"><h3 class="card-title">Impuestos y Totales</h3></div>
                            <div class="card-body">
                                <div class="form-group row"><label for="aplica_iva_compra" class="col-sm-5 col-form-label">Aplica IVA:</label><div class="col-sm-7"><select class="form-control" id="aplica_iva_compra" name="aplica_iva_compra"><option value="0">No</option><option value="1">Sí</option></select></div></div>
                                <div class="form-group row" id="fila_porcentaje_iva" style="display: none;"><label for="porcentaje_iva_compra" class="col-sm-5 col-form-label">IVA (%):</label><div class="col-sm-7"><input type="number" class="form-control" id="porcentaje_iva_compra" name="porcentaje_iva_compra" step="0.01" min="0" value="0.00"></div></div>
                                <button type="button" id="btn_guardar_config_iva" class="btn btn-sm btn-outline-secondary mb-3" style="display: none;">Guardar IVA por defecto</button><hr>
                                <dl class="row"><dt class="col-sm-6">Subtotal Neto:</dt><dd class="col-sm-6 text-right" id="display_subtotal_neto">0.00</dd><dt class="col-sm-6">Monto IVA:</dt><dd class="col-sm-6 text-right" id="display_monto_iva">0.00</dd><dt class="col-sm-6 h5">TOTAL COMPRA:</dt><dd class="col-sm-6 text-right h5" id="display_monto_total">0.00</dd></dl>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sección de Productos -->
                <div class="card card-outline card-success"><div class="card-header"><h3 class="card-title">Detalle de Productos</h3></div>
                    <div class="card-body">
                        <div class="form-row align-items-center mb-3">
                            <div class="col-auto"><input type="text" class="form-control form-control-sm" id="producto_busqueda" placeholder="Autocompletar..."></div>
                            <div class="col-auto"><div id="loader_busqueda_producto" class="loader"></div></div>
                            <div class="col-auto"><button type="button" class="btn btn-info btn-sm" id="btn_abrir_modal_buscar_producto_almacen"><i class="fas fa-search-plus"></i> Buscar en Almacén</button></div>
                            <div class="col-auto"><button type="button" class="btn btn-success btn-sm" id="btn_abrir_modal_nuevo_producto_almacen"><i class="fa fa-plus"></i> Nuevo en Almacén</button></div>
                        </div>
                        <div class="table-responsive"><table id="tabla_productos_compra" class="table table-bordered table-sm"><thead><tr><th style="width: 5%;"><center>#</center></th><th style="width: 35%;">Producto (Código)</th><th style="width: 15%;">Cantidad <span class="text-danger">*</span></th><th style="width: 20%;">P. Compra Unit. <span class="text-danger">*</span></th><th style="width: 15%;">Subtotal</th><th style="width: 10%;"><center>Acción</center></th></tr></thead><tbody><tr id="fila_vacia_productos"><td colspan="6" class="text-center">Aún no has añadido productos.</td></tr></tbody></table></div>
                    </div>
                </div>
                
                <div id="error_general_compra" class="alert alert-danger mt-3" style="display: none;"></div>
                <div class="row mt-3 mb-4"><div class="col-md-12 text-center"><a href="<?php echo $URL; ?>/compras/" class="btn btn-secondary mr-2">Cancelar</a><button type="submit" class="btn btn-primary" id="btn_registrar_compra_final"><i class="fas fa-save"></i> Registrar Compra</button></div></div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL PARA BUSCAR PRODUCTOS EN ALMACÉN -->
<div class="modal fade" id="modal-buscar-producto-almacen" tabindex="-1" role="dialog" aria-hidden="true"><div class="modal-dialog modal-xl"><div class="modal-content">
    <div class="modal-header bg-info text-white"><h5 class="modal-title">Buscar Productos del Almacén</h5><button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span></button></div>
    <div class="modal-body">
        <div class="form-group"><input type="text" class="form-control" id="filtro_productos_almacen_modal" placeholder="Filtrar por código, nombre..."></div>
        <div class="table-responsive" style="max-height: 400px; overflow-y: auto;"><table id="tabla_productos_almacen_modal" class="table table-sm table-hover table-bordered"><thead><tr><th width="5%"><center><input type="checkbox" id="seleccionar_todo_almacen_modal" title="Seleccionar Todos"></center></th><th width="15%">Código</th><th>Nombre</th><th width="10%"><center>Stock</center></th><th width="15%"><center>P.Venta (Alm.)</center></th><th width="15%"><center>P.Compra (Alm.)</center></th><th width="10%"><center>Acción</center></th></tr></thead><tbody><tr><td colspan="7" class="text-center">Cargando...</td></tr></tbody></table></div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button><button type="button" class="btn btn-primary" id="btn_anadir_seleccionados_compra_modal"><i class="fas fa-plus-circle"></i> Añadir Seleccionados</button></div>
</div></div></div>

<!-- MODAL PARA CREAR NUEVO PRODUCTO EN ALMACÉN -->
<div class="modal fade" id="modal-nuevo-producto-almacen" tabindex="-1" role="dialog" aria-hidden="true"><div class="modal-dialog modal-lg"><div class="modal-content">
    <div class="modal-header bg-success text-white"><h5 class="modal-title">Registrar Nuevo Producto en Almacén</h5><button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span></button></div>
    <form id="form-nuevo-producto-almacen-modal" enctype="multipart/form-data"><div class="modal-body">
        <div class="row"><div class="col-md-8 form-group"><label>Nombre <span class="text-danger">*</span></label><input type="text" class="form-control" name="nombre" required></div><div class="col-md-4 form-group"><label>Categoría <span class="text-danger">*</span></label><select class="form-control" name="id_categoria" required><option value="">Seleccione...</option><?php if(!empty($categorias_select_datos_almacen)) foreach ($categorias_select_datos_almacen as $cat):?><option value="<?php echo $cat['id_categoria'];?>"><?php echo sanear($cat['nombre_categoria']);?></option><?php endforeach;?></select></div></div>
        <div class="form-group"><label>Descripción</label><textarea class="form-control" name="descripcion" rows="2"></textarea></div>
        <div class="row"><div class="col-md-4 form-group"><label>Stock Inicial <span class="text-danger">*</span></label><input type="number" class="form-control" name="stock" required min="0" value="0"></div><div class="col-md-4 form-group"><label>Precio Compra <span class="text-danger">*</span></label><input type="number" class="form-control" name="precio_compra" required step="0.01" min="0.01"></div><div class="col-md-4 form-group"><label>Precio Venta <span class="text-danger">*</span></label><input type="number" class="form-control" name="precio_venta" required step="0.01" min="0.01"></div></div>
        <div class="row"><div class="col-md-4 form-group"><label>Stock Mínimo</label><input type="number" class="form-control" name="stock_minimo" min="0" value="0"></div><div class="col-md-4 form-group"><label>Fecha Ingreso <span class="text-danger">*</span></label><input type="date" class="form-control" name="fecha_ingreso" value="<?php echo date('Y-m-d');?>" required></div></div>
        <div class="form-group"><label>Imagen</label><input type="file" class="form-control-file" name="imagen_producto" accept="image/*" id="imagen_prod_almacen_modal_ctrl"><img id="preview_imagen_prod_almacen_modal" src="#" alt="Vista previa" class="mt-2 img-thumbnail" style="max-height:80px;display:none;"/></div>
        <div id="error_message_prod_almacen_modal" class="alert alert-danger" style="display:none;"></div>
    </div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-success" id="btn_guardar_nuevo_prod_almacen_modal_ctrl">Guardar Producto</button></div></form>
</div></div></div>

<?php include ('../layout/mensajes.php'); ?>
<?php include ('../layout/parte2.php'); ?>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
<script>
$(document).ready(function () {
    // --- Resumen de Funciones ---
    // cargarConfigIVADefecto: Carga y aplica el IVA por defecto.
    // calcularTotalesCompra: Actualiza los montos de subtotal, IVA y total.
    // agregarProductoATabla: Añade una fila de producto a la tabla de compra (sin advertencia, solo UI).
    // agregarProductoATablaConAdvertencia: Lógica principal para añadir producto, incluye la advertencia de precio.
    // cargarProductosAlmacenModal: Llena el modal de búsqueda de productos con datos del almacén.
    // mostrarProductosEnTablaModal: Renderiza los productos en la tabla del modal de búsqueda.
    // mostrarAlerta: Muestra mensajes SweetAlert.
    // sanear: Limpia strings para evitar XSS simple en UI.
    // renumerarFilasProductos: Actualiza los números de ítem en la tabla de compra.

    function cargarConfigIVADefecto() { /* ... (código como en la respuesta anterior) ... */ 
        $.ajax({
            url: "../app/controllers/compras/acciones_compras.php", type: "POST",
            data: { accion: "get_config_iva_defecto" }, dataType: "json",
            success: function(response) {
                if (response.status === 'success') {
                    $('#aplica_iva_compra').val(response.aplica_iva).trigger('change');
                    if (response.aplica_iva == '1') {
                        $('#porcentaje_iva_compra').val(parseFloat(response.porcentaje_iva).toFixed(2));
                    }
                } else { mostrarAlerta('Error Config IVA', response.message || 'No se pudo cargar config de IVA.', 'error'); }
            },
            error: function() { mostrarAlerta('Error Conexión', 'No se pudo obtener la configuración de IVA por defecto.', 'error'); }
        });
    }
    cargarConfigIVADefecto();

    $('#aplica_iva_compra').change(function() {
        if ($(this).val() == '1') {
            $('#fila_porcentaje_iva').slideDown(); $('#btn_guardar_config_iva').fadeIn();
        } else {
            $('#fila_porcentaje_iva').slideUp(); $('#porcentaje_iva_compra').val('0.00');
            $('#btn_guardar_config_iva').fadeOut();
        }
        calcularTotalesCompra();
    });
    $('#porcentaje_iva_compra').on('input change', calcularTotalesCompra);

    $('#btn_guardar_config_iva').click(function() { /* ... (código como en la respuesta anterior) ... */ 
        $.ajax({
            url: "../app/controllers/compras/acciones_compras.php", type: "POST",
            data: { accion: "guardar_config_iva_defecto", aplica_iva: $('#aplica_iva_compra').val(), porcentaje_iva: $('#porcentaje_iva_compra').val() },
            dataType: "json",
            success: function(response) {
                if (response.status === 'success') { mostrarAlerta('Configuración Guardada', response.message, 'success'); }
                else { mostrarAlerta('Error', response.message || 'No se pudo guardar la configuración.', 'error'); }
            },
            error: function() { mostrarAlerta('Error Conexión', 'No se pudo guardar la configuración de IVA.', 'error');}
        });
    });

    var cacheProductos = {}; 
    $("#producto_busqueda").autocomplete({
        source: function(request, response) { /* ... (código como en la respuesta anterior, asegurando que ui.item tenga precio_venta) ... */ 
            var term = request.term;
            if (term in cacheProductos && term.length > 0) { response(cacheProductos[term]); return; }
            if (term.length < 2) { response([]); return; }
            $('#loader_busqueda_producto').show(); 
            $.ajax({
                url: "../app/controllers/compras/acciones_compras.php", type: "GET", dataType: "json",
                data: { accion: "buscar_productos_compra", term: term }, // Asegúrate que esta acción devuelva precio_venta
                success: function(data) {
                    $('#loader_busqueda_producto').hide(); cacheProductos[term] = data; response(data);
                },
                error: function(){ $('#loader_busqueda_producto').hide(); response([]); }
            });
        },
        minLength: 2, 
        select: function(event, ui) {
            event.preventDefault(); 
            if (ui.item) { agregarProductoATablaConAdvertencia(ui.item); $(this).val(''); }
            return false;
        },
        focus: function(event, ui) { event.preventDefault(); }
    }).data("ui-autocomplete")._renderItem = function(ul, item) { // Asegúrate que item.precio_venta esté disponible
        return $("<li>")
            .append("<div><b>" + sanear(item.nombre) + "</b> (" + sanear(item.codigo) + ")<br><small>P.Compra Sug: " + parseFloat(item.precio_compra || 0).toFixed(2) + " | P.Venta: " + parseFloat(item.precio_venta || 0).toFixed(2) + " | Stock: " + item.stock + "</small></div>")
            .appendTo(ul);
    };

    var productos_almacen_data_modal = []; 
    $('#btn_abrir_modal_buscar_producto_almacen').click(function() {
        $('#filtro_productos_almacen_modal').val(''); 
        cargarProductosAlmacenModal(); 
        $('#modal-buscar-producto-almacen').modal('show');
    });

    function cargarProductosAlmacenModal() { /* ... (código como en la respuesta anterior) ... */ 
        var tbody = $('#tabla_productos_almacen_modal tbody');
        tbody.html('<tr><td colspan="7" class="text-center"><i class="fas fa-spinner fa-spin"></i> Cargando...</td></tr>');
        $.ajax({
            url: "../app/controllers/compras/acciones_compras.php", type: "POST", 
            data: { accion: "listar_productos_almacen_para_modal" }, dataType: "json",
            success: function(response) {
                tbody.empty(); productos_almacen_data_modal = []; 
                if (response.status === 'success' && response.data && response.data.length > 0) {
                    productos_almacen_data_modal = response.data; 
                    mostrarProductosEnTablaModal(productos_almacen_data_modal);
                } else {
                    tbody.html('<tr><td colspan="7" class="text-center">' + (response.message || 'No hay productos.') + '</td></tr>');
                }
                $('#seleccionar_todo_almacen_modal').prop('checked', false);
            },
            error: function() { tbody.html('<tr><td colspan="7" class="text-center">Error de conexión.</td></tr>');}
        });
    }
    
    function mostrarProductosEnTablaModal(datos) { /* ... (código como en la respuesta anterior, asegurando que prod.precio_venta y prod.precio_compra se usen) ... */ 
        var tbody = $('#tabla_productos_almacen_modal tbody');
        tbody.empty();
        if (datos.length === 0) {
            tbody.html('<tr><td colspan="7" class="text-center">No se encontraron productos.</td></tr>'); return;
        }
        datos.forEach(function(prod) { // prod debe tener id_producto, codigo, nombre, stock, precio_venta, precio_compra
            var filaHtml = `
                <tr data-id-producto-modal="${prod.id_producto}">
                    <td class="text-center"><input type="checkbox" class="seleccionar_producto_almacen_modal" value="${prod.id_producto}"></td>
                    <td>${sanear(prod.codigo)}</td>
                    <td>${sanear(prod.nombre)}</td>
                    <td class="text-center">${prod.stock}</td>
                    <td class="text-center">${parseFloat(prod.precio_venta || 0).toFixed(2)}</td>
                    <td class="text-center">${parseFloat(prod.precio_compra || 0).toFixed(2)}</td>
                    <td class="text-center">
                        <button type="button" class="btn btn-xs btn-outline-primary btn-anadir-prod-individual-modal" title="Añadir"><i class="fas fa-plus"></i></button>
                    </td></tr>`;
            tbody.append(filaHtml);
        });
    }

    $('#filtro_productos_almacen_modal').on('keyup', function() { /* ... (código como en la respuesta anterior) ... */ 
        var valorFiltro = $(this).val().toLowerCase();
        if (!productos_almacen_data_modal.length) return;
        var datosFiltrados = productos_almacen_data_modal.filter(function(prod) {
            return (prod.codigo && prod.codigo.toLowerCase().includes(valorFiltro)) ||
                   (prod.nombre && prod.nombre.toLowerCase().includes(valorFiltro));
        });
        mostrarProductosEnTablaModal(datosFiltrados);
    });
    
    $('#seleccionar_todo_almacen_modal').click(function() { /* ... (código como en la respuesta anterior) ... */ 
        $('#tabla_productos_almacen_modal tbody .seleccionar_producto_almacen_modal').prop('checked', $(this).prop('checked'));
    });

    $('#tabla_productos_almacen_modal tbody').on('click', '.btn-anadir-prod-individual-modal', function() { /* ... (código como en la respuesta anterior) ... */ 
        var idProducto = $(this).closest('tr').data('id-producto-modal');
        var productoSeleccionado = productos_almacen_data_modal.find(p => p.id_producto == idProducto);
        if (productoSeleccionado) { agregarProductoATablaConAdvertencia(productoSeleccionado); }
    });

    $('#btn_anadir_seleccionados_compra_modal').click(function() { /* ... (código como en la respuesta anterior) ... */ 
        var algunoAnadido = false;
        $('#tabla_productos_almacen_modal tbody .seleccionar_producto_almacen_modal:checked').each(function() {
            var idProducto = $(this).val();
            var productoSeleccionado = productos_almacen_data_modal.find(p => p.id_producto == idProducto);
            if (productoSeleccionado) { agregarProductoATablaConAdvertencia(productoSeleccionado); algunoAnadido = true;}
        });
        if (algunoAnadido) { $('#modal-buscar-producto-almacen').modal('hide'); } 
        else { mostrarAlerta('Sin Selección', 'No has seleccionado ningún producto para añadir.', 'info');}
        $('#seleccionar_todo_almacen_modal').prop('checked', false);
    });

    var contador_productos_tabla = 0;
    function agregarProductoATabla(producto, precioCompraInicial) { /* ... (código como en la respuesta anterior, usando precioVentaAlmacen) ... */ 
        contador_productos_tabla++;
        var precioVentaAlmacen = parseFloat(producto.precio_venta || 0).toFixed(2); // Asegúrate que producto.precio_venta exista
        var nuevaFila = `
            <tr data-id-producto="${producto.id_producto}" data-precio-venta-almacen="${precioVentaAlmacen}">
                <td class="text-center align-middle">${contador_productos_tabla}</td>
                <td class="align-middle">${sanear(producto.nombre)} (${sanear(producto.codigo)})<input type="hidden" name="detalle_id_producto[]" value="${producto.id_producto}"></td>
                <td><input type="number" class="form-control form-control-sm cantidad-producto" value="1" min="0.01" step="0.01" required></td>
                <td><input type="number" class="form-control form-control-sm precio-compra-unitario-tabla" value="${precioCompraInicial}" min="0" step="0.01" required></td>
                <td class="text-right align-middle subtotal-producto">${precioCompraInicial}</td>
                <td class="text-center align-middle"><button type="button" class="btn btn-danger btn-sm btn-eliminar-producto-tabla" title="Eliminar"><i class="fas fa-trash"></i></button></td>
            </tr>`;
        $('#fila_vacia_productos').hide();
        $('#tabla_productos_compra tbody').append(nuevaFila);
        $('#tabla_productos_compra tbody tr[data-id-producto="' + producto.id_producto + '"]').find('.precio-compra-unitario-tabla').trigger('change'); // Para la advertencia inicial
        calcularTotalesCompra();
    }
    
    function agregarProductoATablaConAdvertencia(productoOriginal) { /* ... (código como en la respuesta anterior, usando productoOriginal.precio_compra y productoOriginal.precio_venta) ... */ 
        if ($('#tabla_productos_compra tbody tr[data-id-producto="' + productoOriginal.id_producto + '"]').length > 0) {
            mostrarAlerta('Producto ya Añadido', `El producto "${sanear(productoOriginal.nombre)}" ya está en la lista.`, 'warning'); return;
        }
        // productoOriginal debe tener: id_producto, codigo, nombre, precio_compra (del almacén), precio_venta (del almacén)
        var precioCompraSugerido = parseFloat(productoOriginal.precio_compra || 0).toFixed(2); 
        var precioVentaAlmacen = parseFloat(productoOriginal.precio_venta || 0).toFixed(2);
        
        // Clonamos para no modificar el objeto original en el array de datos del modal
        var productoParaTabla = JSON.parse(JSON.stringify(productoOriginal)); 
        // Pasamos precio_venta del almacén explícitamente a la función que crea la fila
        // o nos aseguramos que esté en productoParaTabla para que agregarProductoATabla lo use.
        productoParaTabla.precio_venta = precioVentaAlmacen; // Asegurar que este campo se use para data-precio-venta-almacen

        if (parseFloat(precioCompraSugerido) >= parseFloat(precioVentaAlmacen) && parseFloat(precioVentaAlmacen) > 0) {
            Swal.fire({
                title: 'Advertencia de Precio',
                html: `El P.Compra sugerido para "${sanear(productoParaTabla.nombre)}" (<b>${precioCompraSugerido}</b>) es >= a su P.Venta en almacén (<b>${precioVentaAlmacen}</b>).<br>¿Añadir con este P.Compra?`,
                icon: 'warning', showCancelButton: true, confirmButtonColor: '#3085d6', cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, añadir', cancelButtonText: 'No, corregir'
            }).then((result) => {
                if (result.isConfirmed) { agregarProductoATabla(productoParaTabla, precioCompraSugerido); }
                else { mostrarAlerta('Acción Cancelada', 'Producto no añadido.', 'info');}
            });
        } else { agregarProductoATabla(productoParaTabla, precioCompraSugerido); }
    }

    $('#tabla_productos_compra').on('click', '.btn-eliminar-producto-tabla', function() { /* ... (código como en la respuesta anterior) ... */ 
        $(this).closest('tr').remove();
        if ($('#tabla_productos_compra tbody tr').not('#fila_vacia_productos').length === 0) { $('#fila_vacia_productos').show(); }
        renumerarFilasProductos(); calcularTotalesCompra();
    });

    $('#tabla_productos_compra').on('input change', '.cantidad-producto, .precio-compra-unitario-tabla', function(event) { /* ... (código como en la respuesta anterior, con la lógica de advertencia mejorada) ... */ 
        var fila = $(this).closest('tr');
        var cantidad = parseFloat(fila.find('.cantidad-producto').val()) || 0;
        var precioCompraUnitario = parseFloat(fila.find('.precio-compra-unitario-tabla').val()) || 0;
        fila.find('.subtotal-producto').text((cantidad * precioCompraUnitario).toFixed(2));
        
        if (event.type === 'change' && $(this).hasClass('precio-compra-unitario-tabla')) {
            var precioVentaAlmacen = parseFloat(fila.attr('data-precio-venta-almacen')) || 0;
            var inputPrecioActual = $(this);
            if (precioCompraUnitario >= precioVentaAlmacen && precioVentaAlmacen > 0) {
                // Evitar alertas repetitivas si el valor no cambia o ya se mostró la alerta para este valor
                if (inputPrecioActual.data('advertencia-mostrada-para-valor') === precioCompraUnitario.toFixed(2)) {
                    calcularTotalesCompra(); 
                    return; // No mostrar alerta de nuevo para el mismo valor problemático
                }
                // Marcar que la advertencia se va a mostrar para este valor
                inputPrecioActual.data('advertencia-mostrada-para-valor', precioCompraUnitario.toFixed(2));

                Swal.fire({
                    title: 'Advertencia de Precio',
                    html: `El P.Compra ingresado (<b>${precioCompraUnitario.toFixed(2)}</b>) es >= al P.Venta del producto (<b>${precioVentaAlmacen.toFixed(2)}</b>).<br>¿Continuar con este P.Compra?`,
                    icon: 'warning', showCancelButton: true, confirmButtonColor: '#3085d6', cancelButtonColor: '#d33',
                    confirmButtonText: 'Sí, continuar', cancelButtonText: 'No, corregir'
                }).then((result) => {
                    if (!result.isConfirmed) { 
                        inputPrecioActual.focus(); // Permitir al usuario corregir
                        // No revertir automáticamente, permitir que el usuario decida.
                    }
                    // Limpiar el flag después de que el usuario interactúa con la alerta, 
                    // para que si vuelve a poner otro valor problemático, se le advierta.
                    // Si el valor sigue siendo el mismo problemático y no lo cambió, la condición de arriba lo atrapará.
                    if(inputPrecioActual.val() !== precioCompraUnitario.toFixed(2)) { // Si el usuario cambió el valor en el input mientras la alerta estaba
                         inputPrecioActual.removeData('advertencia-mostrada-para-valor');
                    }
                    calcularTotalesCompra(); 
                });
            } else { 
                // Si el precio es válido, quitar el flag
                inputPrecioActual.removeData('advertencia-mostrada-para-valor');
            }
        }
        calcularTotalesCompra();
    });

    function renumerarFilasProductos() { /* ... (código como en la respuesta anterior) ... */ 
        contador_productos_tabla = 0;
        $('#tabla_productos_compra tbody tr').not('#fila_vacia_productos').each(function() {
            contador_productos_tabla++; $(this).find('td:first-child').text(contador_productos_tabla);
        });
    }
    
    function calcularTotalesCompra() { /* ... (código como en la respuesta anterior) ... */ 
        var subtotalNeto = 0;
        $('#tabla_productos_compra tbody tr').not('#fila_vacia_productos').each(function() {
            subtotalNeto += parseFloat($(this).find('.subtotal-producto').text()) || 0;
        });
        $('#display_subtotal_neto').text(subtotalNeto.toFixed(2));
        var aplicaIVA = $('#aplica_iva_compra').val() == '1';
        var porcentajeIVA = aplicaIVA ? (parseFloat($('#porcentaje_iva_compra').val()) || 0) : 0;
        var montoIVA = (aplicaIVA && porcentajeIVA > 0) ? (subtotalNeto * (porcentajeIVA / 100)) : 0;
        $('#display_monto_iva').text(montoIVA.toFixed(2));
        $('#display_monto_total').text((subtotalNeto + montoIVA).toFixed(2));
    }

    $('#form-registrar-compra').submit(function(e) { /* ... (código como en la respuesta anterior) ... */ 
        e.preventDefault();
        $('#error_general_compra').hide().text('');
        $('#btn_registrar_compra_final').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Registrando...');

        if (!$('#id_proveedor').val() || !$('#fecha_compra').val()) {
            $('#error_general_compra').text('Debe seleccionar un proveedor y una fecha.').show();
            $('#btn_registrar_compra_final').prop('disabled', false).html('<i class="fas fa-save"></i> Registrar Compra'); return;
        }
        if ($('#tabla_productos_compra tbody tr').not('#fila_vacia_productos').length === 0) {
            $('#error_general_compra').text('Debe agregar al menos un producto.').show();
            $('#btn_registrar_compra_final').prop('disabled', false).html('<i class="fas fa-save"></i> Registrar Compra'); return;
        }
        var datosMaestro = {
            accion: "registrar_compra", id_proveedor: $('#id_proveedor').val(),
            nro_comprobante_proveedor: $('#nro_comprobante_proveedor').val(), fecha_compra: $('#fecha_compra').val(),
            aplica_iva_compra: $('#aplica_iva_compra').val(), porcentaje_iva_compra: $('#porcentaje_iva_compra').val(),
            observaciones_compra: $('#observaciones_compra').val()
        };
        var productosDetalleArray = [];
        $('#tabla_productos_compra tbody tr').not('#fila_vacia_productos').each(function() {
            var fila = $(this);
            productosDetalleArray.push({
                id_producto: fila.data('id-producto'),
                cantidad: fila.find('.cantidad-producto').val(),
                precio_unitario: fila.find('.precio-compra-unitario-tabla').val()
            });
        });
        datosMaestro.productos_detalle = JSON.stringify(productosDetalleArray); 
        $.ajax({
            url: "../app/controllers/compras/acciones_compras.php", type: "POST", data: datosMaestro, dataType: "json",
            success: function(response) {
                if (response.status === 'success') {
                    mostrarAlerta('¡Compra Registrada!', response.message + (response.id_compra ? ` (ID: ${response.id_compra})` : ''), 'success', function() {
                        window.location.href = "<?php echo $URL; ?>/compras/"; 
                    });
                } else {
                    $('#error_general_compra').text(response.message || 'Error desconocido.').show();
                    mostrarAlerta('Error al Registrar', response.message || 'No se pudo registrar.', 'error');
                }
            },
            error: function() {
                $('#error_general_compra').text('Error de conexión.').show();
                mostrarAlerta('Error de Conexión', 'No se pudo contactar al servidor.', 'error');
            },
            complete: function() { $('#btn_registrar_compra_final').prop('disabled', false).html('<i class="fas fa-save"></i> Registrar Compra');}
        });
    });
    
    $('#btn_abrir_modal_nuevo_producto_almacen').click(function() { /* ... (código como en la respuesta anterior) ... */ 
        $('#form-nuevo-producto-almacen-modal')[0].reset(); 
        $('#preview_imagen_prod_almacen_modal').hide().attr('src', '#');
        $('#error_message_prod_almacen_modal').hide().text('');
        $('#fecha_ingreso_prod_almacen_modal').val(new Date().toISOString().split('T')[0]);
        $('#modal-nuevo-producto-almacen').modal('show');
    });
    $('#imagen_prod_almacen_modal_ctrl').change(function() { /* ... (código como en la respuesta anterior, usando el ID correcto del input file) ... */ 
        const file = this.files[0]; if (file) { const reader = new FileReader();
        reader.onload = function(e) { $('#preview_imagen_prod_almacen_modal').attr('src', e.target.result).show(); }
        reader.readAsDataURL(file); } else { $('#preview_imagen_prod_almacen_modal').hide(); }
    });
    $('#modal-nuevo-producto-almacen').on('hidden.bs.modal', function () { /* ... (código como en la respuesta anterior) ... */ 
        $('#form-nuevo-producto-almacen-modal')[0].reset();
        $('#preview_imagen_prod_almacen_modal').hide().attr('src', '#');
        $('#error_message_prod_almacen_modal').hide().text('');
    });
    $('#form-nuevo-producto-almacen-modal').submit(function(e) { /* ... (código como en la respuesta anterior, usando el ID correcto del botón y llamando a agregarProductoATablaConAdvertencia) ... */ 
        e.preventDefault(); $('#error_message_prod_almacen_modal').hide().text('');
        var formData = new FormData(this);
        $('#btn_guardar_nuevo_prod_almacen_modal_ctrl').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');
        $.ajax({
            url: "../app/controllers/almacen/create_producto.php", type: "POST", data: formData,
            contentType: false, processData: false, dataType: "json",
            success: function(response) {
                if (response.status === 'success') {
                    $('#modal-nuevo-producto-almacen').modal('hide');
                    mostrarAlerta('Producto Guardado', response.message, 'success');
                    if (response.new_data && response.new_data.id_producto) {
                        // response.new_data debe tener id_producto, codigo, nombre, precio_compra, precio_venta, stock
                        agregarProductoATablaConAdvertencia(response.new_data); 
                    } else { mostrarAlerta('Producto Creado', 'El producto fue creado. Búscalo para añadirlo.', 'info');}
                } else { $('#error_message_prod_almacen_modal').text(response.message || 'Error.').show(); }
            },
            error: function() { $('#error_message_prod_almacen_modal').text('Error de conexión.').show(); },
            complete: function() { $('#btn_guardar_nuevo_prod_almacen_modal_ctrl').prop('disabled', false).html('Guardar Producto');}
        });
    });

    function mostrarAlerta(title, text, icon, callback) { /* ... (código como en la respuesta anterior) ... */ 
        Swal.fire({
            title: title, text: text, icon: icon, timer: icon === 'success' ? 2500 : 4000,
            showConfirmButton: icon !== 'success', allowOutsideClick: false, allowEscapeKey: false
        }).then((result) => { if (callback && typeof callback === 'function') { callback(); }});
    }
    
    function sanear(str) { /* ... (código como en la respuesta anterior) ... */ 
        if (typeof str !== 'string') return '';
        var map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return str.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
});
</script>