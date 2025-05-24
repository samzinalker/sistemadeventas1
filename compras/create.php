<?php
include '../app/config.php'; // $URL, $pdo, $fechaHora
include '../layout/sesion.php';

// include '../layout/permisos.php'; // Futuro para permisos en especificos roles

include '../layout/parte1.php';

if (!isset($_SESSION['id_usuario'])) {
    echo "<div class='container'>Error: No se pudo obtener el ID del usuario de la sesión. Por favor, inicie sesión nuevamente.</div>";
    include '../layout/parte2.php';
    exit;
}
$id_usuario_sesion = $_SESSION['id_usuario'];

include '../layout/mensajes.php';
?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Registrar Nueva Compra</h1>
                </div><!-- /.col -->
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?php echo $URL;?>/compras/">Mis Compras</a></li>
                        <li class="breadcrumb-item active">Registrar Nueva Compra</li>
                    </ol>
                </div><!-- /.col -->
            </div><!-- /.row -->
        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->

    <!-- Main content -->
    <div class="content">
        <div class="container-fluid">
            <form action="<?php echo $URL;?>/app/controllers/compras/controller_create_compra.php" method="POST" id="formNuevaCompra">
                <input type="hidden" name="id_usuario_compra" value="<?php echo $id_usuario_sesion; ?>">
                
                <div class="row">
                    <!-- Columna Izquierda: Datos Generales y Selección de Productos -->
                    <div class="col-md-8">
                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title">Datos Generales y Productos</h3>
                            </div>
                            <div class="card-body">
                                <!-- Datos del Proveedor y Fecha -->
                                <div class="row">
                                    <div class="col-md-7">
                                        <div class="form-group">
                                            <label for="proveedor">Proveedor <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <input type="text" class="form-control" id="nombre_proveedor_compra_display" name="nombre_proveedor_compra_display" placeholder="Seleccione un proveedor" readonly required>
                                                <input type="hidden" id="id_proveedor_compra" name="id_proveedor_compra" required>
                                                <div class="input-group-append">
                                                    <button type="button" class="btn btn-info" data-toggle="modal" data-target="#modalBuscarProveedor">
                                                        <i class="fas fa-search"></i> Buscar/Crear
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <div id="detalle_proveedor_seleccionado" class="alert alert-light mt-0 mb-2 py-1 px-2" style="display:none; font-size:0.9em;">
                                            <small><strong>Empresa:</strong> <span id="info_empresa_proveedor"></span> | <strong>Celular:</strong> <span id="info_celular_proveedor"></span></small>
                                        </div>
                                    </div>
                                    <div class="col-md-5">
                                        <div class="form-group">
                                            <label for="fecha_compra_compra">Fecha de Compra <span class="text-danger">*</span></label>
                                            <input type="date" class="form-control" id="fecha_compra_compra" name="fecha_compra_compra" value="<?php echo date('Y-m-d'); ?>" required>
                                        </div>
                                    </div>
                                </div>
                                 <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="nro_compra_referencia">Nro. Compra (Referencia Interna) <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="nro_compra_referencia" name="nro_compra_referencia" required readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="comprobante_compra">Comprobante (Factura/Boleta Nro.)</label>
                                            <input type="text" class="form-control" id="comprobante_compra" name="comprobante_compra">
                                        </div>
                                    </div>
                                </div>
                                <hr>
                                <!-- Sección para Añadir Productos a la Lista -->
                                <h5>Añadir Producto a la Compra</h5>
                                <div class="row align-items-end">
                                    <div class="col-md-5">
                                        <div class="form-group">
                                            <label for="temp_nombre_producto">Producto <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <input type="text" class="form-control" id="temp_nombre_producto" placeholder="Buscar o crear producto..." readonly>
                                                
                                                <input type="hidden" id="temp_id_producto">
                                                <input type="hidden" id="temp_codigo_producto">
                                                <input type="hidden" id="temp_iva_predeterminado_producto">
                                                <input type="hidden" id="temp_precio_compra_sugerido_producto">
                                                <input type="hidden" id="temp_stock_actual_producto">
                                                
                                                <input type="hidden" id="temp_es_nuevo_producto" value="0">
                                                <input type="hidden" id="temp_nueva_descripcion_producto">
                                                <input type="hidden" id="temp_nueva_id_categoria_producto">
                                                <input type="hidden" id="temp_nuevo_precio_venta_producto">
                                                <input type="hidden" id="temp_nuevo_stock_minimo_producto">
                                                <input type="hidden" id="temp_nuevo_stock_maximo_producto">
                                                <input type="hidden" id="temp_nueva_fecha_ingreso_producto">

                                                <div class="input-group-append">
                                                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modalBuscarProducto"><i class="fas fa-search"></i></button>
                                                </div>
                                            </div>
                                             <small id="temp_producto_info" class="form-text text-muted" style="display:none;"></small>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label for="temp_cantidad">Cantidad <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control" id="temp_cantidad" min="1" step="1" value="1" pattern="[0-9]*">
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label for="temp_precio_compra">Precio U. <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <div class="input-group-prepend"><span class="input-group-text">$</span></div>
                                                <input type="number" step="0.01" class="form-control" id="temp_precio_compra" min="0" placeholder="0.00">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label for="temp_porcentaje_iva">IVA % <span class="text-danger">*</span></label>
                                            <input type="number" step="0.01" class="form-control" id="temp_porcentaje_iva" min="0" value="0">
                                        </div>
                                    </div>
                                    <div class="col-md-1">
                                        <div class="form-group">
                                            <label>&nbsp;</label>
                                            <button type="button" class="btn btn-success btn-block" id="btnAnadirProductoALista" title="Añadir a la lista"><i class="fas fa-plus"></i></button>
                                        </div>
                                    </div>
                                </div>
                                <hr>
                                <!-- Tabla de Ítems de la Compra -->
                                <h5>Ítems de la Compra</h5>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover table-sm" id="tablaItemsCompra">
                                        <thead class="thead-light">
                                            <tr>
                                                <th style="width: 30px;">#</th>
                                                <th>Código</th>
                                                <th>Producto</th>
                                                <th style="width: 80px;">Cant.</th>
                                                <th style="width: 100px;">Precio U.</th>
                                                <th style="width: 70px;">IVA %</th>
                                                <th style="width: 100px;" class="text-right">Subtotal</th>
                                                <th style="width: 90px;" class="text-right">Monto IVA</th>
                                                <th style="width: 110px;" class="text-right">Total Ítem</th>
                                                <th style="width: 50px;">Acción</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr id="filaNoItems">
                                                <td colspan="10" class="text-center">No hay productos añadidos a la compra.</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Columna Derecha: Resumen y Totales -->
                    <div class="col-md-4">
                        <div class="card card-primary">
                             <div class="card-header">
                                <h3 class="card-title">Resumen de la Compra</h3>
                            </div>
                            <div class="card-body">
                                <div class="text-right">
                                    <h5>Subtotal General: <span class="text-muted">$<span id="subtotal_general_compra_display">0.00</span></span></h5>
                                    <h5>IVA General: <span class="text-muted">$<span id="monto_iva_general_display">0.00</span></span></h5>
                                    <hr>
                                    <h3>Total Compra: <span class="text-primary">$<span id="total_general_compra_display">0.00</span></span></h3>
                                </div>
                                
                                <input type="hidden" name="subtotal_general_compra_calculado" id="subtotal_general_compra_hidden">
                                <input type="hidden" name="monto_iva_general_calculado" id="monto_iva_general_hidden">
                                <input type="hidden" name="total_general_compra_calculado" id="total_general_compra_hidden">
                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-save"></i> Registrar Compra</button>
                                <a href="<?php echo $URL;?>/compras/" class="btn btn-secondary btn-block mt-2"><i class="fas fa-times"></i> Cancelar</a>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content -->
</div>
<!-- /.content-wrapper -->

<!-- Modal Buscar/Crear Producto -->
<div class="modal fade" id="modalBuscarProducto" tabindex="-1" role="dialog" aria-labelledby="modalBuscarProductoLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalBuscarProductoLabel">Buscar o Crear Producto para su Almacén</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <ul class="nav nav-tabs" id="productoTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="buscar-producto-tab" data-toggle="tab" href="#buscarProductoPane" role="tab" aria-controls="buscarProductoPane" aria-selected="true">Buscar Producto Existente</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="crear-producto-tab" data-toggle="tab" href="#crearProductoPane" role="tab" aria-controls="crearProductoPane" aria-selected="false">Crear Nuevo Producto (para esta compra)</a>
                    </li>
                </ul>
                <div class="tab-content" id="productoTabsContent">
                    <div class="tab-pane fade show active p-3" id="buscarProductoPane" role="tabpanel" aria-labelledby="buscar-producto-tab">
                        <p>Estos son los productos de su almacén personal.</p>
                        <table id="tablaProductosAlmacen" class="table table-bordered table-striped table-hover" style="width:100%">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Código</th>
                                    <th>Nombre</th>
                                    <th>Stock</th>
                                    <th>P. Compra Ref.</th>
                                    <th>IVA Prod. (%)</th>
                                    <th>Categoría</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                    <div class="tab-pane fade p-3" id="crearProductoPane" role="tabpanel" aria-labelledby="crear-producto-tab">
                        <h5>Registrar Nuevo Producto (se creará al finalizar la compra)</h5>
                        <div id="formDatosNuevoProductoRapido" class="mt-3">
                            <div class="row">
                                <div class="col-md-4 form-group">
                                    <label for="modal_producto_codigo_rapido_display">Código <small>(Sugerido)</small></label>
                                    <input type="text" class="form-control" id="modal_producto_codigo_rapido_display" readonly placeholder="Generando...">
                                </div>
                                <div class="col-md-8 form-group">
                                    <label for="modal_producto_nombre_rapido">Nombre del Producto <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="modal_producto_nombre_rapido" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="modal_producto_descripcion_rapido">Descripción</label>
                                <textarea class="form-control" id="modal_producto_descripcion_rapido" rows="2"></textarea>
                            </div>
                            <div class="row">
                                <div class="col-md-3 form-group">
                                    <label for="modal_producto_id_categoria_rapido">Categoría <span class="text-danger">*</span></label>
                                    <select class="form-control" id="modal_producto_id_categoria_rapido" required>
                                        <option value="">Cargando sus categorías...</option>
                                    </select>
                                </div>
                                 <div class="col-md-3 form-group">
                                    <label for="modal_producto_precio_compra_rapido">Precio Compra <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" class="form-control" id="modal_producto_precio_compra_rapido" min="0" required>
                                </div>
                                <div class="col-md-3 form-group">
                                    <label for="modal_producto_precio_venta_rapido">Precio Venta <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" class="form-control" id="modal_producto_precio_venta_rapido" min="0" required>
                                </div>
                                <div class="col-md-3 form-group">
                                    <label for="modal_producto_iva_rapido">IVA Predeterminado (%) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" class="form-control" id="modal_producto_iva_rapido" value="0" min="0" required>
                                </div>
                            </div>
                             <div class="row">
                                <div class="col-md-4 form-group">
                                    <label for="modal_producto_stock_minimo_rapido">Stock Mínimo <small>(Opcional)</small></label>
                                    <input type="number" class="form-control" id="modal_producto_stock_minimo_rapido" min="0">
                                </div>
                                <div class="col-md-4 form-group">
                                    <label for="modal_producto_stock_maximo_rapido">Stock Máximo <small>(Opcional)</small></label>
                                    <input type="number" class="form-control" id="modal_producto_stock_maximo_rapido" min="0">
                                </div>
                                <div class="col-md-4 form-group">
                                    <label for="modal_producto_fecha_ingreso_rapido">Fecha Ingreso <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="modal_producto_fecha_ingreso_rapido" value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                            </div>
                            <button type="button" class="btn btn-success" id="btnConfirmarNuevoProductoTemporal">
                                <i class="fas fa-check"></i> Usar estos datos para el ítem
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Buscar/Crear Proveedor -->
<div class="modal fade" id="modalBuscarProveedor" tabindex="-1" role="dialog" aria-labelledby="modalBuscarProveedorLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalBuscarProveedorLabel">Buscar o Crear Proveedor Personal</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <ul class="nav nav-tabs" id="proveedorTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="buscar-proveedor-tab" data-toggle="tab" href="#buscarProveedorPane" role="tab" aria-controls="buscarProveedorPane" aria-selected="true">Buscar Proveedor</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="crear-proveedor-tab" data-toggle="tab" href="#crearProveedorPane" role="tab" aria-controls="crearProveedorPane" aria-selected="false">Crear Nuevo Proveedor</a>
                    </li>
                </ul>
                <div class="tab-content" id="proveedorTabsContent">
                    <div class="tab-pane fade show active p-3" id="buscarProveedorPane" role="tabpanel" aria-labelledby="buscar-proveedor-tab">
                        <p>Estos son sus proveedores personales.</p>
                        <table id="tablaProveedores" class="table table-bordered table-striped table-hover" style="width:100%">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Empresa</th>
                                    <th>Celular</th>
                                    <th>Teléfono</th>
                                    <th>Email</th>
                                    <th>Dirección</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                    <div class="tab-pane fade p-3" id="crearProveedorPane" role="tabpanel" aria-labelledby="crear-proveedor-tab">
                        <h5>Registrar Nuevo Proveedor Personal</h5>
                        <form id="formNuevoProveedor" class="mt-3">
                             <div class="row">
                                <div class="col-md-6 form-group">
                                    <label for="nuevo_proveedor_nombre">Nombre del Proveedor <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="nuevo_proveedor_nombre" name="nombre_proveedor" required>
                                </div>
                                <div class="col-md-6 form-group">
                                    <label for="nuevo_proveedor_empresa">Empresa</label>
                                    <input type="text" class="form-control" id="nuevo_proveedor_empresa" name="empresa">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <label for="nuevo_proveedor_celular">Celular <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="nuevo_proveedor_celular" name="celular" required>
                                </div>
                                <div class="col-md-6 form-group">
                                    <label for="nuevo_proveedor_telefono">Teléfono <small>(Opcional)</small></label>
                                    <input type="text" class="form-control" id="nuevo_proveedor_telefono" name="telefono">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <label for="nuevo_proveedor_email">Email <small>(Opcional)</small></label>
                                    <input type="email" class="form-control" id="nuevo_proveedor_email" name="email">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label for="nuevo_proveedor_direccion">Dirección</label>
                                    <input type="text" class="form-control" id="nuevo_proveedor_direccion" name="direccion">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-success"><i class="fas fa-plus-circle"></i> Guardar Nuevo Proveedor</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<?php
include '../layout/parte2.php';
?>
<script>
$(document).ready(function() {
    var tablaProductosAlmacen;
    var tablaProveedores; 
    var idUsuarioActual = <?php echo json_encode((int)$id_usuario_sesion); ?>; 
    var contadorItemsCompra = 0;

    function limpiarCamposProductoTemporal() {
        $('#temp_id_producto').val('');
        $('#temp_codigo_producto').val('');
        $('#temp_nombre_producto').val('');
        $('#temp_iva_predeterminado_producto').val('');
        $('#temp_precio_compra_sugerido_producto').val('');
        $('#temp_stock_actual_producto').val('');
        $('#temp_precio_compra').val('');
        $('#temp_porcentaje_iva').val('0');
        $('#temp_cantidad').val('1'); 
        $('#temp_producto_info').hide().empty();
        $('#temp_es_nuevo_producto').val('0');
        $('#temp_nueva_descripcion_producto').val('');
        $('#temp_nueva_id_categoria_producto').val('');
        $('#temp_nuevo_precio_venta_producto').val('');
        $('#temp_nuevo_stock_minimo_producto').val('');
        $('#temp_nuevo_stock_maximo_producto').val('');
        $('#temp_nueva_fecha_ingreso_producto').val('');
    }

    function generarNroCompraReferencia() {
        $('#nro_compra_referencia').val('Generando...');
        $.ajax({
            url: '<?php echo $URL; ?>/app/controllers/compras/controller_generar_codigo_compra.php',
            type: 'POST', 
            dataType: 'json',
            success: function(response) {
                if (response && response.status === 'success' && response.codigo_compra) {
                    $('#nro_compra_referencia').val(response.codigo_compra);
                } else {
                    $('#nro_compra_referencia').val('Error REF');
                    Swal.fire('Error', response.message || 'No se pudo generar la referencia de compra.', 'error');
                }
            },
            error: function() {
                $('#nro_compra_referencia').val('Error Conexión REF');
                Swal.fire('Error', 'No se pudo conectar para generar la referencia de compra.', 'error');
            }
        });
    }

    function generarSiguienteCodigoProductoParaModal() {
        $('#modal_producto_codigo_rapido_display').val('Generando...');
        $.ajax({
            url: '<?php echo $URL; ?>/app/controllers/almacen/controller_generar_siguiente_codigo.php',
            type: 'POST',
            data: { id_usuario: idUsuarioActual },
            dataType: 'json',
            success: function(response) {
                if (response && response.status === 'success' && response.nuevo_codigo) {
                    $('#modal_producto_codigo_rapido_display').val(response.nuevo_codigo);
                } else {
                    $('#modal_producto_codigo_rapido_display').val('Error GEN');
                }
            },
            error: function() {
                $('#modal_producto_codigo_rapido_display').val('Error CON');
            }
        });
    }
    
    function cargarCategoriasUsuarioParaModal() {
        $.ajax({
            url: '<?php echo $URL; ?>/app/controllers/categorias/controller_listar_categorias_usuario.php', 
            type: 'POST', 
            dataType: 'json',
            data: { id_usuario: idUsuarioActual },
            success: function(response) {
                var options = '<option value="">Seleccione una categoría</option>';
                if(response && response.status === 'success' && response.data && response.data.length > 0) {
                    response.data.forEach(function(cat) {
                        options += '<option value="' + cat.id_categoria + '">' + cat.nombre_categoria + '</option>';
                    });
                } else {
                     options = '<option value="">No tiene categorías o hubo un error.</option>';
                }
                $('#modal_producto_id_categoria_rapido').html(options);
            },
            error: function() {
                $('#modal_producto_id_categoria_rapido').html('<option value="">Error de conexión al cargar categorías</option>');
            }
        });
    }

    $('#modalBuscarProducto').on('shown.bs.modal', function () {
        cargarCategoriasUsuarioParaModal(); 
        if ($('#crear-producto-tab').hasClass('active')) { 
            generarSiguienteCodigoProductoParaModal();
            $('#formDatosNuevoProductoRapido')[0].reset(); // Resetear todo el formulario
            $('#modal_producto_fecha_ingreso_rapido').val('<?php echo date('Y-m-d'); ?>');
            $('#modal_producto_iva_rapido').val(0);
        }
        if ($.fn.DataTable.isDataTable('#tablaProductosAlmacen')) {
            tablaProductosAlmacen.ajax.reload(null, false);
        } else {
            inicializarTablaProductos();
        }
    });

    $('a[data-toggle="tab"][href="#crearProductoPane"]').on('shown.bs.tab', function (e) {
        if ($(e.target).closest('.modal').attr('id') === 'modalBuscarProducto') {
            generarSiguienteCodigoProductoParaModal();
            $('#formDatosNuevoProductoRapido')[0].reset();
            $('#modal_producto_fecha_ingreso_rapido').val('<?php echo date('Y-m-d'); ?>');
             $('#modal_producto_iva_rapido').val(0);
        }
    });

    $('a[data-toggle="tab"][href="#buscarProductoPane"]').on('shown.bs.tab', function (e) {
        if ($(e.target).closest('.modal').attr('id') === 'modalBuscarProducto') {
            if (tablaProductosAlmacen) tablaProductosAlmacen.ajax.reload(null, false);
        }
    });

    $('#btnConfirmarNuevoProductoTemporal').on('click', function() {
        var nombreRapido = $('#modal_producto_nombre_rapido').val().trim();
        var idCategoriaRapido = $('#modal_producto_id_categoria_rapido').val();
        var precioCompraRapidoStr = $('#modal_producto_precio_compra_rapido').val();
        var precioVentaRapidoStr = $('#modal_producto_precio_venta_rapido').val();
        var ivaRapidoStr = $('#modal_producto_iva_rapido').val();
        var fechaIngresoRapido = $('#modal_producto_fecha_ingreso_rapido').val();

        if (!nombreRapido) { Swal.fire('Atención', 'El nombre del producto es obligatorio.', 'warning'); return; }
        if (!idCategoriaRapido) { Swal.fire('Atención', 'La categoría es obligatoria.', 'warning'); return; }
        if (precioCompraRapidoStr === '' || isNaN(parseFloat(precioCompraRapidoStr)) || parseFloat(precioCompraRapidoStr) < 0) { Swal.fire('Atención', 'El precio de compra es obligatorio y no puede ser negativo.', 'warning'); return; }
        if (precioVentaRapidoStr === '' || isNaN(parseFloat(precioVentaRapidoStr)) || parseFloat(precioVentaRapidoStr) < 0) { Swal.fire('Atención', 'El precio de venta es obligatorio y no puede ser negativo.', 'warning'); return; }
        if (ivaRapidoStr === '' || isNaN(parseFloat(ivaRapidoStr)) || parseFloat(ivaRapidoStr) < 0) { Swal.fire('Atención', 'El IVA es obligatorio y no puede ser negativo.', 'warning'); return; }
        if (!fechaIngresoRapido) { Swal.fire('Atención', 'La fecha de ingreso es obligatoria.', 'warning'); return; }
        
        var precioCompraRapido = parseFloat(precioCompraRapidoStr);
        var precioVentaRapido = parseFloat(precioVentaRapidoStr);
        var ivaRapido = parseFloat(ivaRapidoStr);

        limpiarCamposProductoTemporal();

        $('#temp_es_nuevo_producto').val('1');
        $('#temp_id_producto').val(''); 
        $('#temp_codigo_producto').val($('#modal_producto_codigo_rapido_display').val()); 
        $('#temp_nombre_producto').val(nombreRapido);
        $('#temp_precio_compra_sugerido_producto').val(precioCompraRapido.toFixed(2));
        $('#temp_precio_compra').val(precioCompraRapido.toFixed(2));
        $('#temp_iva_predeterminado_producto').val(ivaRapido.toFixed(2));
        $('#temp_porcentaje_iva').val(ivaRapido.toFixed(2));
        $('#temp_stock_actual_producto').val('0'); 

        $('#temp_nueva_descripcion_producto').val($('#modal_producto_descripcion_rapido').val().trim());
        $('#temp_nueva_id_categoria_producto').val(idCategoriaRapido);
        $('#temp_nuevo_precio_venta_producto').val(precioVentaRapido.toFixed(2));
        $('#temp_nuevo_stock_minimo_producto').val($('#modal_producto_stock_minimo_rapido').val() || '');
        $('#temp_nuevo_stock_maximo_producto').val($('#modal_producto_stock_maximo_rapido').val() || '');
        $('#temp_nueva_fecha_ingreso_producto').val(fechaIngresoRapido);
        
        $('#temp_producto_info').html(`NUEVO: ${nombreRapido} (Cód. Sugerido: ${$('#modal_producto_codigo_rapido_display').val()}) | IVA: ${ivaRapido.toFixed(2)}%`).show();
        
        $('#modalBuscarProducto').modal('hide');
        $('#temp_cantidad').val('1.00').focus();
    });

    function inicializarTablaProductos() {
        tablaProductosAlmacen = $('#tablaProductosAlmacen').DataTable({
            "processing": true, "serverSide": true,
            "ajax": {
                "url": "<?php echo $URL; ?>/app/controllers/almacen/controller_buscar_productos_dt.php",
                "type": "POST",
                "data": function (d) { d.id_usuario = idUsuarioActual; }
            },
            "columns": [
                { "data": "id_producto", "visible": false }, 
                { "data": "codigo" }, { "data": "nombre" }, { "data": "stock" },
                { "data": "precio_compra", "render": $.fn.dataTable.render.number(',', '.', 2, '$') },
                { "data": "iva_porcentaje_producto", "render": function(data){ return (parseFloat(data) || 0).toFixed(2) + '%';} }, 
                { "data": "nombre_categoria" },
                { "data": null, "orderable": false, "searchable": false, "defaultContent": "<button type='button' class='btn btn-sm btn-success seleccionar-producto-para-compra'><i class='fas fa-check-circle'></i> Sel.</button>" }
            ],
            "language": {"url": "<?php echo $URL;?>/public/templeates/AdminLTE-3.2.0/plugins/datatables-plugins/i18n/es_es.json"},
            "responsive": true, "lengthChange": true, "autoWidth": false, "pageLength": 5, "lengthMenu": [5, 10, 25, 50]
        });
    }

    // --- INICIO CAMBIO: Lógica de selección de producto mejorada ---
    $('#tablaProductosAlmacen tbody').on('click', '.seleccionar-producto-para-compra', function () {
        console.log("Botón seleccionar producto clickeado"); 
        if (!tablaProductosAlmacen) {
            console.error("tablaProductosAlmacen no está inicializada.");
            return;
        }
        var tr = $(this).closest('tr');
        var row = tablaProductosAlmacen.row(tr);
        var datosFila = row.data();

        console.log("TR:", tr); 
        console.log("Row object:", row); 
        console.log("Datos de la fila (intento 1):", datosFila); 

        if (!datosFila) {
            if (row.child.isShown()) { 
                datosFila = tablaProductosAlmacen.row(tr.prev('tr.parent')).data();
                console.log("Intentando con fila padre (responsive). Datos:", datosFila);
            }
        }
        
        if (!datosFila && tr.hasClass('child')) { // Si es fila hija y el anterior falló
            var parentRowNode = tr.prev('tr.parent');
            if (parentRowNode.length > 0) {
                datosFila = tablaProductosAlmacen.row(parentRowNode).data();
                console.log("Intentando con tr.prev('tr.parent') explícito. Datos:", datosFila);
            } else { // Fallback si no hay 'tr.parent', intentar con el anterior inmediato
                 datosFila = tablaProductosAlmacen.row(tr.prev()).data();
                 console.log("Intentando con tr.prev() general. Datos:", datosFila);
            }
        }

        if (!datosFila) {
            Swal.fire('Error', 'No se pudieron obtener datos del producto. Por favor, revise la consola del navegador para más detalles.', 'error');
            console.error("No se pudieron obtener datos de la fila. TR:", tr.get(0), "Row object:", row, "DatosFila final:", datosFila);
            return;
        }

        limpiarCamposProductoTemporal();
        $('#temp_es_nuevo_producto').val('0'); 
        $('#temp_id_producto').val(datosFila.id_producto);
        $('#temp_nombre_producto').val(datosFila.nombre);
        $('#temp_codigo_producto').val(datosFila.codigo || 'N/A');
        $('#temp_stock_actual_producto').val(datosFila.stock || 0);
        
        let precioCompraSugerido = parseFloat(datosFila.precio_compra || 0).toFixed(2);
        $('#temp_precio_compra_sugerido_producto').val(precioCompraSugerido);
        $('#temp_precio_compra').val(precioCompraSugerido > 0 ? precioCompraSugerido : '');

        let ivaAplicar = 0;
        if (datosFila.iva_ultima_compra !== null && !isNaN(parseFloat(datosFila.iva_ultima_compra))) {
            ivaAplicar = parseFloat(datosFila.iva_ultima_compra);
        } else if (datosFila.iva_porcentaje_producto !== null && !isNaN(parseFloat(datosFila.iva_porcentaje_producto))) {
            ivaAplicar = parseFloat(datosFila.iva_porcentaje_producto);
        }
        
        $('#temp_iva_predeterminado_producto').val(ivaAplicar.toFixed(2));
        $('#temp_porcentaje_iva').val(ivaAplicar.toFixed(2));
        
        $('#temp_producto_info').html(`Cód: ${datosFila.codigo || 'N/A'} | Stock: ${datosFila.stock || 0} | IVA Aplicado: ${ivaAplicar.toFixed(2)}%`).show();
        $('#temp_cantidad').val('1.00').focus(); 
        $('#modalBuscarProducto').modal('hide');
    });
    // --- FIN CAMBIO ---
   
    $('#btnAnadirProductoALista').on('click', function() {
    var nombreProducto = $('#temp_nombre_producto').val().trim();
    var cantidadStr = $('#temp_cantidad').val();
    var precioCompraStr = $('#temp_precio_compra').val();
    var porcentajeIvaStr = $('#temp_porcentaje_iva').val();
    
    var esNuevoProducto = $('#temp_es_nuevo_producto').val() === '1';
    var idProducto = $('#temp_id_producto').val(); // Usado si !esNuevoProducto
    var codigoProducto = $('#temp_codigo_producto').val();

    // Validaciones (las que ya tienes)
    if (!nombreProducto) { Swal.fire('Atención', 'Debe seleccionar o definir un producto.', 'warning'); return; }
    if (cantidadStr === '' || isNaN(cantidad) || cantidad <= 0 || parseFloat(cantidadStr) % 1 !== 0) {
    Swal.fire('Atención', 'La cantidad debe ser un número entero mayor a cero.', 'warning');
    $('#temp_cantidad').focus();
    return;
}
    if (precioCompraStr === '' || isNaN(parseFloat(precioCompraStr)) || parseFloat(precioCompraStr) < 0) { Swal.fire('Atención', 'El precio de compra es obligatorio y no puede ser negativo.', 'warning'); $('#temp_precio_compra').focus(); return; }
    if (porcentajeIvaStr === '' || isNaN(parseFloat(porcentajeIvaStr)) || parseFloat(porcentajeIvaStr) < 0) { Swal.fire('Atención', 'El porcentaje de IVA es obligatorio y no puede ser negativo.', 'warning'); $('#temp_porcentaje_iva').focus(); return; }

    var cantidad = parseInt(cantidadStr, 10);
    var precioCompra = parseFloat(precioCompraStr);
    var porcentajeIva = parseFloat(porcentajeIvaStr);

    // Evitar duplicados (lógica que ya tienes)
    var yaEnLista = false;
    $('#tablaItemsCompra tbody tr').not('#filaNoItems').each(function() {
        if (!esNuevoProducto && $(this).find('input[name="item_id_producto[]"]').val() == idProducto) {
            Swal.fire('Atención', 'Este producto ya está en la lista.', 'warning');
            yaEnLista = true; return false; 
        }
        if (esNuevoProducto && $(this).find('input[name="item_nombre_producto[]"]').val() == nombreProducto && $(this).find('input[name="item_es_nuevo[]"]').val() == '1') {
             Swal.fire('Atención', 'Ya añadió un nuevo producto con este nombre. Si es diferente, use un nombre distintivo.', 'warning');
            yaEnLista = true; return false;
        }
    });
    if (yaEnLista) return;

    // --- INICIO DE LA MODIFICACIÓN IMPORTANTE ---
    let input_es_nuevo_val = esNuevoProducto ? '1' : '0';
    let input_id_producto_val = esNuevoProducto ? '' : idProducto; // ID vacío si es nuevo para almacén

    // Siempre definir estos campos, vacíos si no es un "nuevo producto para almacén"
    let input_nueva_descripcion_val = esNuevoProducto ? ($('#temp_nueva_descripcion_producto').val() || '') : '';
    let input_nueva_id_categoria_val = esNuevoProducto ? ($('#temp_nueva_id_categoria_producto').val() || '') : '';
    let input_nuevo_precio_venta_val = esNuevoProducto ? ($('#temp_nuevo_precio_venta_producto').val() || '') : '';
    let input_nuevo_stock_minimo_val = esNuevoProducto ? ($('#temp_nuevo_stock_minimo_producto').val() || '') : '';
    let input_nuevo_stock_maximo_val = esNuevoProducto ? ($('#temp_nuevo_stock_maximo_producto').val() || '') : '';
    let input_nueva_fecha_ingreso_val = esNuevoProducto ? ($('#temp_nueva_fecha_ingreso_producto').val() || '') : '';
    // --- FIN DE LA MODIFICACIÓN IMPORTANTE ---

    contadorItemsCompra++;
    var subtotalItem = cantidad * precioCompra;
    var montoIvaItem = subtotalItem * (porcentajeIva / 100);
    var totalItem = subtotalItem + montoIvaItem;

    var nuevaFila = `
        <tr>
            <td>${contadorItemsCompra}</td>
            <td>${codigoProducto || (esNuevoProducto ? 'PENDIENTE' : 'N/A')}
                
                <input type="hidden" name="item_es_nuevo[]" value="${input_es_nuevo_val}">
                <input type="hidden" name="item_id_producto[]" value="${input_id_producto_val}"> 
                
                <input type="hidden" name="item_nueva_descripcion[]" value="${input_nueva_descripcion_val}">
                <input type="hidden" name="item_nueva_id_categoria[]" value="${input_nueva_id_categoria_val}">
                <input type="hidden" name="item_nuevo_precio_venta[]" value="${input_nuevo_precio_venta_val}">
                <input type="hidden" name="item_nuevo_stock_minimo[]" value="${input_nuevo_stock_minimo_val}">
                <input type="hidden" name="item_nuevo_stock_maximo[]" value="${input_nuevo_stock_maximo_val}">
                <input type="hidden" name="item_nueva_fecha_ingreso[]" value="${input_nueva_fecha_ingreso_val}">
                
                {/* Campos comunes a todos los ítems */}
                <input type="hidden" name="item_codigo_producto[]" value="${codigoProducto || ''}">
                <input type="hidden" name="item_nombre_producto[]" value="${nombreProducto}">
            </td>
            <td>${nombreProducto} ${esNuevoProducto ? '<span class="badge badge-info">Nuevo</span>' : ''}</td>
            // Dentro de la plantilla de nuevaFilaHTML
            <td><input type="number" name="item_cantidad[]" class="form-control form-control-sm item-cantidad text-right" value="${parseInt(cantidad)}" min="1" step="1" style="width:70px;" required pattern="[0-9]*"></td>
            <td><input type="number" name="item_precio_unitario[]" class="form-control form-control-sm item-precio text-right" step="0.01" value="${precioCompra.toFixed(2)}" min="0" style="width:90px;" required></td>
            <td><input type="number" name="item_porcentaje_iva[]" class="form-control form-control-sm item-iva text-right" step="0.01" value="${porcentajeIva.toFixed(2)}" min="0" style="width:60px;" required></td>
            <td class="item-subtotal text-right">${subtotalItem.toFixed(2)}</td>
            <td class="item-monto-iva text-right">${montoIvaItem.toFixed(2)}</td>
            <td class="item-total text-right font-weight-bold">${totalItem.toFixed(2)}</td>
            <td><button type="button" class="btn btn-danger btn-sm btn-eliminar-item"><i class="fas fa-trash"></i></button></td>
        </tr>
    `;
    
    $('#filaNoItems').hide(); 
    $('#tablaItemsCompra tbody').append(nuevaFila);
    recalcularTotalesGenerales();
    limpiarCamposProductoTemporal();
    $('#temp_nombre_producto').focus(); 
});

    $('#tablaItemsCompra tbody').on('change keyup', '.item-cantidad, .item-precio, .item-iva', function() {
        var fila = $(this).closest('tr');
        var cantidad = parseInt(fila.find('.item-cantidad').val(), 10) || 0; // Usar parseInt
        var precio = parseFloat(fila.find('.item-precio').val()) || 0;
        var ivaPct = parseFloat(fila.find('.item-iva').val()) || 0;

        var subtotal = cantidad * precio;
        var montoIva = subtotal * (ivaPct / 100);
        var total = subtotal + montoIva;

        fila.find('.item-subtotal').text(subtotal.toFixed(2));
        fila.find('.item-monto-iva').text(montoIva.toFixed(2));
        fila.find('.item-total').text(total.toFixed(2));
        
        recalcularTotalesGenerales();
    });
    
    $('#tablaItemsCompra tbody').on('click', '.btn-eliminar-item', function() {
        $(this).closest('tr').remove();
        recalcularTotalesGenerales();
        if ($('#tablaItemsCompra tbody tr').not('#filaNoItems').length === 0) {
            $('#filaNoItems').show();
            contadorItemsCompra = 0; 
        } else {
            var count = 1;
            $('#tablaItemsCompra tbody tr').not('#filaNoItems').each(function(idx, tr){
                $(tr).find('td:first').text(idx + 1);
            });
            contadorItemsCompra = $('#tablaItemsCompra tbody tr').not('#filaNoItems').length;
        }
    });

    function recalcularTotalesGenerales() {
        var subtotalGeneral = 0;
        var montoIvaGeneral = 0;

        $('#tablaItemsCompra tbody tr').not('#filaNoItems').each(function() {
            var subtotalItem = parseFloat($(this).find('.item-subtotal').text()) || 0;
            var montoIvaItem = parseFloat($(this).find('.item-monto-iva').text()) || 0;
            subtotalGeneral += subtotalItem;
            montoIvaGeneral += montoIvaItem;
        });
        var totalGeneral = subtotalGeneral + montoIvaGeneral;

        $('#subtotal_general_compra_display').text(subtotalGeneral.toFixed(2));
        $('#monto_iva_general_display').text(montoIvaGeneral.toFixed(2));
        $('#total_general_compra_display').text(totalGeneral.toFixed(2));

        $('#subtotal_general_compra_hidden').val(subtotalGeneral.toFixed(2));
        $('#monto_iva_general_hidden').val(montoIvaGeneral.toFixed(2));
        $('#total_general_compra_hidden').val(totalGeneral.toFixed(2));
    }

    // --- LÓGICA PARA PROVEEDORES ---
    $('#modalBuscarProveedor').on('shown.bs.modal', function () {
        if (!$.fn.DataTable.isDataTable('#tablaProveedores')) {
            tablaProveedores = $('#tablaProveedores').DataTable({
                "processing": true, "serverSide": true,
                "ajax": {"url": "<?php echo $URL; ?>/app/controllers/proveedores/controller_proveedores_serverside.php", "type": "POST"},
                "columns": [
                    { "data": "id_proveedor", "visible": false }, 
                    { "data": "nombre_proveedor"}, { "data": "empresa" }, 
                    { "data": "celular" }, { "data": "telefono" }, { "data": "email" }, 
                    { "data": "direccion" },
                    { "data": null, "orderable": false, "searchable": false,
                        "render": function (data, type, row) {
                            return `<button type="button" class="btn btn-success btn-sm seleccionar-proveedor" 
                                data-id="${row.id_proveedor}" data-nombre="${row.nombre_proveedor}"
                                data-empresa="${row.empresa || ''}" data-celular="${row.celular || ''}">
                                <i class="fas fa-check-circle"></i> Seleccionar</button>`;
                        }
                    }
                ],
                "language": {"url": "<?php echo $URL;?>/public/templeates/AdminLTE-3.2.0/plugins/datatables-plugins/i18n/es_es.json"},
                "responsive": true, "lengthChange": true, "autoWidth": false, "pageLength": 5, "lengthMenu": [5, 10, 25, 50]
            });
        } else {
            tablaProveedores.ajax.reload(null, false); 
        }
    });

    $('#tablaProveedores tbody').on('click', '.seleccionar-proveedor', function () {
        $('#id_proveedor_compra').val($(this).data('id'));
        $('#nombre_proveedor_compra_display').val($(this).data('nombre'));
        $('#info_empresa_proveedor').text($(this).data('empresa'));
        $('#info_celular_proveedor').text($(this).data('celular'));
        $('#detalle_proveedor_seleccionado').show();
        $('#modalBuscarProveedor').modal('hide');
    });

    $('#formNuevoProveedor').on('submit', function(e) {
        e.preventDefault();
        var formData = $(this).serialize() + '&id_usuario=' + idUsuarioActual; 
        $.ajax({
            url: '<?php echo $URL; ?>/app/controllers/proveedores/create.php',
            type: 'POST', data: formData, dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    Swal.fire('¡Éxito!', response.message || 'Proveedor creado.', 'success');
                    var provData = response.data || { id_proveedor: response.creadoId, nombre_proveedor: $('#nuevo_proveedor_nombre').val(), empresa: $('#nuevo_proveedor_empresa').val(), celular: $('#nuevo_proveedor_celular').val() };
                    if(provData.id_proveedor) { 
                        $('#id_proveedor_compra').val(provData.id_proveedor);
                        $('#nombre_proveedor_compra_display').val(provData.nombre_proveedor);
                        $('#info_empresa_proveedor').text(provData.empresa || '');
                        $('#info_celular_proveedor').text(provData.celular || '');
                        $('#detalle_proveedor_seleccionado').show();
                    }
                    $('#modalBuscarProveedor').modal('hide');
                    $('#formNuevoProveedor')[0].reset();
                    if(tablaProveedores) tablaProveedores.ajax.reload(null, false);
                } else {
                    Swal.fire('Error', response.message || 'No se pudo crear el proveedor.', 'error');
                }
            },
            error: function() { 
                Swal.fire('Error de Conexión', 'No se pudo conectar para crear el proveedor.', 'error');
            }
        });
    });
    
    // --- INICIALIZACIÓN Y VALIDACIÓN DEL FORMULARIO PRINCIPAL ---
    generarNroCompraReferencia();
    recalcularTotalesGenerales(); 

    $('#formNuevaCompra').on('submit', function(e){
        if (!$('#id_proveedor_compra').val()) { 
            e.preventDefault(); Swal.fire('Atención', 'Debe seleccionar un proveedor.', 'warning'); return false;
        }
        if ($('#tablaItemsCompra tbody tr').not('#filaNoItems').length === 0) {
            e.preventDefault(); Swal.fire('Atención', 'Debe añadir al menos un producto a la compra.', 'warning'); return false;
        }
        if (!$('#nro_compra_referencia').val() || $('#nro_compra_referencia').val() === 'Generando...' || $('#nro_compra_referencia').val().startsWith('Error')) {
            e.preventDefault(); Swal.fire('Atención', 'Espere a que se genere el Número de Compra o verifique si hay errores.', 'warning'); return false;
        }
        var itemsValidos = true;
        $('#tablaItemsCompra tbody tr').not('#filaNoItems').each(function() {
            var cantidad = parseFloat($(this).find('.item-cantidad').val()) || 0;
            if (cantidad <= 0) {
                itemsValidos = false;
                Swal.fire('Atención', 'Todas las cantidades de los productos deben ser mayores a cero.', 'warning');
                $(this).find('.item-cantidad').focus();
                return false; 
            }
        });
        if(!itemsValidos) {
            e.preventDefault();
            return false;
        }
        $('#tablaItemsCompra tbody .item-cantidad, #tablaItemsCompra tbody .item-precio, #tablaItemsCompra tbody .item-iva').trigger('change'); 
    });
});
</script>