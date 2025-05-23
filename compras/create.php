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
            <div class="row">
                <div class="col-md-12">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Ingrese los datos de la compra</h3>
                        </div>
                        <form action="<?php echo $URL;?>/app/controllers/compras/controller_create_compra.php" method="POST" id="formNuevaCompra">
                            <input type="hidden" name="id_usuario_compra" value="<?php echo $id_usuario_sesion; ?>">
                            <!-- Hidden input para el nro_secuencial si tu controller_create_compra lo necesita -->
                            <!-- <input type="hidden" id="nro_compra_secuencial_hidden" name="nro_compra_secuencial"> -->
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6"> <!-- Ajustado de col-md-7 -->
                                        <div class="form-group">
                                            <label for="producto">Producto</label>
                                            <div class="input-group">
                                                <input type="text" class="form-control" id="nombre_producto_compra" name="nombre_producto_compra_display" placeholder="Seleccione un producto de su almacén" readonly>
                                                <input type="hidden" id="id_producto_compra" name="id_producto_compra" required>
                                                <input type="hidden" id="iva_original_producto" name="iva_original_producto"> 
                                                <div class="input-group-append">
                                                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modalBuscarProducto">
                                                        <i class="fas fa-search"></i> Buscar/Crear Producto
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <div id="detalle_producto_seleccionado" class="alert alert-light mt-2" style="display:none;">
                                            <h6>Producto Seleccionado:</h6>
                                            <small><strong>Código:</strong> <span id="info_codigo_producto"></span> | <strong>Stock Actual:</strong> <span id="info_stock_producto"></span> | <strong>Precio Compra Ref.:</strong> $<span id="info_precio_compra_producto"></span></small><br>
                                            <small><strong>IVA Predeterminado Producto:</strong> <span id="info_iva_producto">0</span>%</small>
                                        </div>
                                    </div>
                                    <div class="col-md-2"> 
                                        <div class="form-group">
                                            <label for="cantidad_compra">Cantidad</label>
                                            <input type="number" class="form-control" id="cantidad_compra" name="cantidad_compra" min="1" value="1" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4"> <!-- Ajustado de col-md-3 -->
                                        <div class="form-group">
                                            <label for="precio_compra_unidad_compra">Precio de Compra (Unidad)</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">$</span>
                                                </div>
                                                <input type="number" step="0.01" class="form-control" id="precio_compra_unidad_compra" name="precio_compra_unidad_compra" min="0" required>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <hr>

                                <div class="row">
                                    <div class="col-md-7">
                                        <div class="form-group">
                                            <label for="proveedor">Proveedor</label>
                                            <div class="input-group">
                                                <input type="text" class="form-control" id="nombre_proveedor_compra" name="nombre_proveedor_compra_display" placeholder="Seleccione un proveedor de su lista" readonly>
                                                <input type="hidden" id="id_proveedor_compra" name="id_proveedor_compra" required>
                                                <div class="input-group-append">
                                                    <button type="button" class="btn btn-info" data-toggle="modal" data-target="#modalBuscarProveedor">
                                                        <i class="fas fa-search"></i> Buscar/Crear Proveedor
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <div id="detalle_proveedor_seleccionado" class="alert alert-light mt-2" style="display:none;">
                                            <h6>Proveedor Seleccionado:</h6>
                                            <small><strong>Empresa:</strong> <span id="info_empresa_proveedor"></span> | <strong>Celular:</strong> <span id="info_celular_proveedor"></span></small>
                                        </div>
                                    </div>
                                    <div class="col-md-5">
                                        <div class="form-group">
                                            <label for="fecha_compra_compra">Fecha de Compra</label>
                                            <input type="date" class="form-control" id="fecha_compra_compra" name="fecha_compra_compra" value="<?php echo date('Y-m-d'); ?>" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="nro_compra_referencia">Nro. Compra (Referencia Interna)</label>
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
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="porcentaje_iva_compra">IVA Aplicado a esta Compra (%)</label>
                                            <input type="number" step="0.01" class="form-control" id="porcentaje_iva_compra" name="porcentaje_iva_compra_transaccion" value="0" min="0">
                                            <small>El IVA predeterminado de este producto es <span id="iva_predeterminado_producto_info">0</span>%. Si lo cambia aquí, se usará para esta compra y podrá actualizar el producto si lo desea.</small>
                                        </div>
                                    </div>
                                    <div class="col-md-9 text-right">
                                        <h5>Subtotal: <span class="text-muted">$<span id="subtotal_compra_display">0.00</span></span></h5>
                                        <h5>IVA (<span id="iva_porcentaje_display_dinamico">0</span>%): <span class="text-muted">$<span id="monto_iva_display">0.00</span></span></h5>
                                        <h3>Total Compra: <span class="text-primary">$<span id="total_compra_display">0.00</span></span></h3>
                                        
                                        <input type="hidden" name="subtotal_compra_calculado" id="subtotal_compra_hidden">
                                        <input type="hidden" name="monto_iva_calculado" id="monto_iva_hidden">
                                        <input type="hidden" name="total_compra_calculado" id="total_compra_hidden">
                                    </div>
                                </div>

                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Registrar Compra</button>
                                <a href="<?php echo $URL;?>/compras/" class="btn btn-secondary"><i class="fas fa-times"></i> Cancelar</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
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
                        <a class="nav-link active" id="buscar-producto-tab" data-toggle="tab" href="#buscarProductoPane" role="tab" aria-controls="buscarProductoPane" aria-selected="true">Buscar Producto</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="crear-producto-tab" data-toggle="tab" href="#crearProductoPane" role="tab" aria-controls="crearProductoPane" aria-selected="false">Crear Nuevo Producto</a>
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
                        <h5>Registrar Nuevo Producto en su Almacén (Creación Rápida)</h5>
                        <form id="formNuevoProductoRapido" class="mt-3">
                            <input type="hidden" name="id_usuario_creador" value="<?php echo $id_usuario_sesion; ?>">
                            <input type="hidden" name="accion" value="crear_producto_almacen_rapido">
                            <input type="hidden" id="producto_iva_predeterminado_rapido_hidden" name="producto_iva_predeterminado">
                            <input type="hidden" id="producto_codigo_rapido_hidden" name="producto_codigo">

                            <div class="row">
                                <div class="col-md-4 form-group">
                                    <label for="producto_codigo_rapido_display">Código <small>(Se autogenera)</small></label>
                                    <input type="text" class="form-control" id="producto_codigo_rapido_display" readonly placeholder="Generando...">
                                </div>
                                <div class="col-md-8 form-group">
                                    <label for="producto_nombre_rapido">Nombre del Producto <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="producto_nombre_rapido" name="producto_nombre" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="producto_descripcion_rapido">Descripción</label>
                                <textarea class="form-control" id="producto_descripcion_rapido" name="producto_descripcion" rows="2"></textarea>
                            </div>
                            <div class="row">
                                <div class="col-md-3 form-group">
                                    <label for="producto_id_categoria_rapido">Categoría <span class="text-danger">*</span></label>
                                    <select class="form-control" id="producto_id_categoria_rapido" name="producto_id_categoria" required>
                                        <option value="">Cargando sus categorías...</option>
                                    </select>
                                </div>
                                 <div class="col-md-3 form-group">
                                    <label for="producto_precio_compra_rapido">Precio Compra Ref. <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" class="form-control" id="producto_precio_compra_rapido" name="producto_precio_compra" min="0" required>
                                </div>
                                <div class="col-md-3 form-group">
                                    <label for="producto_precio_venta_rapido">Precio Venta Ref. <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" class="form-control" id="producto_precio_venta_rapido" name="producto_precio_venta" min="0" required>
                                </div>
                                <div class="col-md-3 form-group">
                                    <label for="producto_iva_rapido">IVA Predeterminado (%) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" class="form-control" id="producto_iva_rapido" name="producto_iva_predeterminado_visible" value="0" min="0" required>
                                </div>
                            </div>
                             <div class="row">
                                <div class="col-md-4 form-group">
                                    <label for="producto_stock_minimo_rapido">Stock Mínimo <small>(Opcional)</small></label>
                                    <input type="number" class="form-control" id="producto_stock_minimo_rapido" name="producto_stock_minimo" min="0">
                                </div>
                                <div class="col-md-4 form-group">
                                    <label for="producto_stock_maximo_rapido">Stock Máximo <small>(Opcional)</small></label>
                                    <input type="number" class="form-control" id="producto_stock_maximo_rapido" name="producto_stock_maximo" min="0">
                                </div>
                                <div class="col-md-4 form-group">
                                    <label for="producto_fecha_ingreso_rapido">Fecha Ingreso <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="producto_fecha_ingreso_rapido" name="producto_fecha_ingreso" value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-success"><i class="fas fa-plus-circle"></i> Guardar Nuevo Producto</button>
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
                             <!-- No se necesita id_usuario aquí, el controlador create.php lo toma de la sesión -->
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
                                    <!-- El HTML original tenía input type="text", lo mantengo. Si era textarea, ajustar -->
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
    var idUsuarioActual = <?php echo json_encode($id_usuario_sesion); ?>;

    // --- LÓGICA PARA GENERAR NRO DE COMPRA DE REFERENCIA ---
    function generarNroCompraReferencia() {
        $('#nro_compra_referencia').val('Generando...');
        $.ajax({
            url: '<?php echo $URL; ?>/app/controllers/compras/controller_generar_codigo_compra.php',
            type: 'POST', // Puede ser GET si prefieres, ya que el id_usuario se toma de la sesión del servidor
            dataType: 'json',
            // No es necesario enviar id_usuario_sesion desde el cliente explícitamente
            // si el controlador PHP lo toma de $_SESSION['id_usuario']
            success: function(response) {
                if (response && response.status === 'success' && response.codigo_compra) {
                    $('#nro_compra_referencia').val(response.codigo_compra);
                    // Si necesitas el secuencial para el backend (controller_create_compra.php)
                    // y no quieres que el controlador de creación lo recalcule, puedes guardarlo en un hidden input:
                    // $('#nro_compra_secuencial_hidden').val(response.nro_secuencial);
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

    function generarSiguienteCodigoProducto() {
        $('#producto_codigo_rapido_display').val('Generando...');
        $.ajax({
            url: '<?php echo $URL; ?>/app/controllers/almacen/controller_generar_siguiente_codigo.php',
            type: 'POST',
            data: { id_usuario: idUsuarioActual },
            dataType: 'json',
            success: function(response) {
                if (response && response.status === 'success' && response.nuevo_codigo) {
                    $('#producto_codigo_rapido_display').val(response.nuevo_codigo);
                    $('#producto_codigo_rapido_hidden').val(response.nuevo_codigo);
                } else {
                    $('#producto_codigo_rapido_display').val('Error al generar');
                    $('#producto_codigo_rapido_hidden').val('');
                    Swal.fire('Error', response.message || 'No se pudo generar el código del producto.', 'error');
                }
            },
            error: function() {
                $('#producto_codigo_rapido_display').val('Error de conexión');
                $('#producto_codigo_rapido_hidden').val('');
                Swal.fire('Error', 'No se pudo conectar para generar el código del producto.', 'error');
            }
        });
    }

    $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
        if ($(e.target).attr('href') === '#crearProductoPane' && $(e.target).closest('.modal').attr('id') === 'modalBuscarProducto') {
            generarSiguienteCodigoProducto();
            var ivaActualFormPrincipal = parseFloat($('#porcentaje_iva_compra').val()) || 0;
            $('#producto_iva_rapido').val(ivaActualFormPrincipal);
        }
    });

    function cargarCategoriasUsuario() {
        $.ajax({
            url: '<?php echo $URL; ?>/app/controllers/categorias/controller_listar_categorias_usuario.php', 
            type: 'POST', 
            // data: { id_usuario: idUsuarioActual }, // No es necesario si el controller usa la sesión
            dataType: 'json',
            success: function(response) {
                var options = '<option value="">Seleccione una categoría</option>';
                if(response && response.status === 'success' && response.data && response.data.length > 0) {
                    response.data.forEach(function(cat) {
                        options += '<option value="' + cat.id_categoria + '">' + cat.nombre_categoria + '</option>';
                    });
                } else if (response && response.status === 'success' && response.data && response.data.length === 0) {
                    options = '<option value="">No tiene categorías registradas. Puede crear en el módulo de Categorías.</option>';
                } 
                else {
                     options = '<option value="">Error: ' + (response.message || 'No se pudo cargar categorías') + '</option>';
                }
                $('#producto_id_categoria_rapido').html(options);
            },
            error: function() {
                $('#producto_id_categoria_rapido').html('<option value="">Error de conexión al cargar categorías</option>');
            }
        });
    }

    $('#modalBuscarProducto').on('shown.bs.modal', function () {
        cargarCategoriasUsuario(); 
        if ($('#crear-producto-tab').hasClass('active')) { 
            generarSiguienteCodigoProducto();
            var ivaActualFormPrincipal = parseFloat($('#porcentaje_iva_compra').val()) || 0;
            $('#producto_iva_rapido').val(ivaActualFormPrincipal);
        }

        if (!$.fn.DataTable.isDataTable('#tablaProductosAlmacen')) {
            tablaProductosAlmacen = $('#tablaProductosAlmacen').DataTable({
                "processing": true, "serverSide": true,
                "ajax": {
                    "url": "<?php echo $URL; ?>/app/controllers/almacen/controller_buscar_productos_dt.php",
                    "type": "POST",
                    "data": function (d) { d.id_usuario = idUsuarioActual; }
                },
                "columns": [
                    { "data": "id_producto" }, { "data": "codigo" }, { "data": "nombre" },
                    { "data": "stock" },
                    { "data": "precio_compra", "render": $.fn.dataTable.render.number(',', '.', 2, '$') },
                    { "data": "iva_porcentaje_producto", 
                      "render": function(data, type, row){ return (parseFloat(data) || 0) + '%';} 
                    }, 
                    { "data": "nombre_categoria" }, 
                    {
                        "data": null, "title": "Acción", "orderable": false, "searchable": false,
                        "render": function (data, type, row) {
                            return `<button type="button" class="btn btn-success btn-sm seleccionar-producto" 
                                data-id="${row.id_producto}" data-nombre="${row.nombre}" 
                                data-codigo="${row.codigo || 'N/A'}" data-stock="${row.stock || 0}" 
                                data-preciocompra="${row.precio_compra || 0}"
                                data-iva="${row.iva_porcentaje_producto || 0}"> 
                                <i class="fas fa-check-circle"></i> Seleccionar
                                </button>`;
                        }
                    }
                ],
                "language": {"url": "<?php echo $URL;?>/public/templeates/AdminLTE-3.2.0/plugins/datatables-plugins/i18n/es_es.json"},
                "responsive": true, "lengthChange": true, "autoWidth": false, "pageLength": 5, "lengthMenu": [5, 10, 25, 50]
            });
        } else {
            tablaProductosAlmacen.ajax.reload(null, false); 
        }
    });

    $('#tablaProductosAlmacen tbody').on('click', '.seleccionar-producto', function () {
        var ivaProducto = parseFloat($(this).data('iva')) || 0;

        $('#id_producto_compra').val($(this).data('id'));
        $('#nombre_producto_compra').val($(this).data('nombre'));
        $('#info_codigo_producto').text($(this).data('codigo'));
        $('#info_stock_producto').text($(this).data('stock'));
        let precioCompraSugerido = parseFloat($(this).data('preciocompra')).toFixed(2);
        $('#info_precio_compra_producto').text(precioCompraSugerido);
        
        $('#info_iva_producto').text(ivaProducto.toFixed(2));
        $('#iva_predeterminado_producto_info').text(ivaProducto.toFixed(2));
        $('#porcentaje_iva_compra').val(ivaProducto.toFixed(2));
        $('#iva_original_producto').val(ivaProducto.toFixed(2));

        $('#detalle_producto_seleccionado').fadeIn();
        
        if(precioCompraSugerido > 0 && parseFloat(precioCompraSugerido) > 0) { 
             $('#precio_compra_unidad_compra').val(precioCompraSugerido);
        } else {
            $('#precio_compra_unidad_compra').val(''); 
        }
        $('#modalBuscarProducto').modal('hide');
        calcularTotalCompra();
    });

    $('#formNuevoProductoRapido').on('submit', function(e) {
        e.preventDefault();
        if (!$('#producto_codigo_rapido_hidden').val() || $('#producto_codigo_rapido_hidden').val() === 'Error al generar' || $('#producto_codigo_rapido_hidden').val() === 'Error de conexión') {
            Swal.fire('Atención', 'No se pudo generar un código de producto válido. Intente abrir la pestaña de nuevo.', 'warning');
            return;
        }
        $('#producto_iva_predeterminado_rapido_hidden').val($('#producto_iva_rapido').val());
        var formData = new FormData(this);

        $.ajax({
            url: '<?php echo $URL; ?>/almacen/acciones_almacen.php', 
            type: 'POST', data: formData, contentType: false, processData: false, dataType: 'json',
            success: function(response) {
                if(response.status === 'success' && response.producto) {
                    Swal.fire('¡Éxito!', response.message || 'Producto creado.', 'success');
                    var ivaNuevoProducto = parseFloat(response.producto.iva_predeterminado || 0);

                    $('#id_producto_compra').val(response.producto.id_producto);
                    $('#nombre_producto_compra').val(response.producto.nombre);
                    $('#info_codigo_producto').text(response.producto.codigo || 'N/A'); 
                    $('#info_stock_producto').text(response.producto.stock_inicial || '0'); 
                    let precioCompraSugerido = parseFloat(response.producto.precio_compra || 0).toFixed(2);
                    $('#info_precio_compra_producto').text(precioCompraSugerido);

                    $('#info_iva_producto').text(ivaNuevoProducto.toFixed(2));
                    $('#iva_predeterminado_producto_info').text(ivaNuevoProducto.toFixed(2));
                    $('#porcentaje_iva_compra').val(ivaNuevoProducto.toFixed(2));
                    $('#iva_original_producto').val(ivaNuevoProducto.toFixed(2));

                    $('#detalle_producto_seleccionado').fadeIn();
                    if(precioCompraSugerido > 0 && parseFloat(precioCompraSugerido) > 0) $('#precio_compra_unidad_compra').val(precioCompraSugerido);

                    $('#modalBuscarProducto').modal('hide');
                    $('#formNuevoProductoRapido')[0].reset();
                    $('#producto_codigo_rapido_display').val(''); 
                    $('#producto_codigo_rapido_hidden').val(''); 
                    $('#producto_iva_rapido').val(0); 
                    if (tablaProductosAlmacen) tablaProductosAlmacen.ajax.reload(null, false);
                    calcularTotalCompra();
                } else {
                    Swal.fire('Error', response.message || 'No se pudo crear el producto.', 'error');
                }
            },
            error: function() { 
                Swal.fire('Error de Conexión', 'No se pudo conectar con el servidor.', 'error');
            }
        });
    });

    
    $('#modalBuscarProveedor').on('shown.bs.modal', function () {
        if (!$.fn.DataTable.isDataTable('#tablaProveedores')) {
            tablaProveedores = $('#tablaProveedores').DataTable({
                "processing": true,
                "serverSide": true,
                "ajax": {
                    "url": "<?php echo $URL; ?>/app/controllers/proveedores/controller_proveedores_serverside.php",
                    "type": "POST"
                },
                "columns": [
                    { "data": "id_proveedor", "title": "ID" },
                    { "data": "nombre_proveedor", "title": "Nombre" },
                    { "data": "empresa", "title": "Empresa" },
                    { "data": "celular", "title": "Celular" },
                    { "data": "telefono", "title": "Teléfono" },
                    { "data": "email", "title": "Email" },
                    { "data": "direccion", "title": "Dirección" },
                    {
                        "data": null,
                        "title": "Acción", 
                        "orderable": false,
                        "searchable": false,
                        "render": function (data, type, row) {
                            return `<button type="button" class="btn btn-success btn-sm seleccionar-proveedor" 
                                data-id="${row.id_proveedor}" 
                                data-nombre="${row.nombre_proveedor}"
                                data-empresa="${row.empresa || 'N/A'}" 
                                data-celular="${row.celular || 'N/A'}">
                                <i class="fas fa-check-circle"></i> Seleccionar
                                </button>`;
                        }
                    }
                ],
                "language": {
                    "url": "<?php echo $URL; ?>/public/templeates/AdminLTE-3.2.0/plugins/datatables-plugins/i18n/es_es.json"
                },
                "responsive": true, 
                "lengthChange": true, 
                "autoWidth": false,
                "pageLength": 5,
                "lengthMenu": [5, 10, 25, 50]
            });
        } else {
            tablaProveedores.ajax.reload(null, false); 
        }
    });

    $('#tablaProveedores tbody').on('click', '.seleccionar-proveedor', function () {
        var idProveedor = $(this).data('id');
        var nombreProveedor = $(this).data('nombre');
        var empresaProveedor = $(this).data('empresa');
        var celularProveedor = $(this).data('celular');
        
        $('#id_proveedor_compra').val(idProveedor);
        $('#nombre_proveedor_compra').val(nombreProveedor); 
        
        $('#info_empresa_proveedor').text(empresaProveedor);
        $('#info_celular_proveedor').text(celularProveedor);
        $('#detalle_proveedor_seleccionado').fadeIn();
        
        $('#modalBuscarProveedor').modal('hide'); 
    });

    $('#formNuevoProveedor').on('submit', function(e) {
        e.preventDefault();
        var formData = $(this).serialize(); 

        $.ajax({
            url: '<?php echo $URL; ?>/app/controllers/proveedores/create.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    Swal.fire('¡Éxito!', response.message || 'Proveedor creado correctamente.', 'success');
                    
                    var nombreNuevoProv = $('#nuevo_proveedor_nombre').val();
                    var empresaNuevoProv = $('#nuevo_proveedor_empresa').val() || 'N/A';
                    var celularNuevoProv = $('#nuevo_proveedor_celular').val() || 'N/A';

                    if(response.creadoId){ // Asegúrate que el controlador create.php devuelva 'creadoId' o el id del proveedor
                         $('#id_proveedor_compra').val(response.creadoId);
                    } else {
                        // Fallback o manejar el caso donde el ID no es devuelto explícitamente
                        // Podrías intentar obtener el último ID o simplemente dejarlo para que el usuario lo seleccione de la tabla
                         $('#id_proveedor_compra').val(''); // O no hacer nada si el usuario debe re-seleccionar
                    }
                    $('#nombre_proveedor_compra').val(nombreNuevoProv);
                    
                    $('#info_empresa_proveedor').text(empresaNuevoProv);
                    $('#info_celular_proveedor').text(celularNuevoProv);
                    $('#detalle_proveedor_seleccionado').fadeIn();

                    $('#modalBuscarProveedor').modal('hide');
                    $('#formNuevoProveedor')[0].reset(); 
                    if (tablaProveedores) {
                        tablaProveedores.ajax.reload(null, false); 
                    }
                } else {
                    Swal.fire('Error', response.message || 'No se pudo crear el proveedor.', 'error');
                }
            },
            error: function() {
                Swal.fire('Error de Conexión', 'No se pudo conectar con el servidor para crear el proveedor.', 'error');
            }
        });
    });

    function calcularTotalCompra() {
        var cantidad = parseFloat($('#cantidad_compra').val()) || 0;
        var precioUnidad = parseFloat($('#precio_compra_unidad_compra').val()) || 0;
        var porcentajeIvaTransaccion = parseFloat($('#porcentaje_iva_compra').val()) || 0;

        var subtotal = cantidad * precioUnidad;
        var montoIva = subtotal * (porcentajeIvaTransaccion / 100);
        var total = subtotal + montoIva;

        $('#subtotal_compra_display').text(subtotal.toFixed(2));
        $('#monto_iva_display').text(montoIva.toFixed(2));
        $('#total_compra_display').text(total.toFixed(2));
        $('#iva_porcentaje_display_dinamico').text(porcentajeIvaTransaccion.toFixed(2));
        $('#subtotal_compra_hidden').val(subtotal.toFixed(2));
        $('#monto_iva_hidden').val(montoIva.toFixed(2));
        $('#total_compra_hidden').val(total.toFixed(2));
    }
    $('#cantidad_compra, #precio_compra_unidad_compra, #porcentaje_iva_compra').on('input change keyup', calcularTotalCompra);
    
    // Llamadas iniciales al cargar el documento
    calcularTotalCompra(); 
    generarNroCompraReferencia(); // <--- LLAMADA PARA GENERAR EL NRO DE COMPRA

    $('#formNuevaCompra').on('submit', function(e){
        if (!$('#id_producto_compra').val()) {
            e.preventDefault(); Swal.fire('Atención', 'Debe seleccionar un producto.', 'warning'); return false;
        }
        if (!$('#id_proveedor_compra').val()) { 
            e.preventDefault(); Swal.fire('Atención', 'Debe seleccionar un proveedor.', 'warning'); return false;
        }
        if (!$('#nro_compra_referencia').val() || $('#nro_compra_referencia').val() === 'Generando...' || $('#nro_compra_referencia').val().startsWith('Error')) {
            e.preventDefault(); Swal.fire('Atención', 'Espere a que se genere el Número de Compra o verifique si hay errores.', 'warning'); return false;
        }
        var porcentajeIva = parseFloat($('#porcentaje_iva_compra').val());
        if (isNaN(porcentajeIva) || porcentajeIva < 0) {
             e.preventDefault(); Swal.fire('Atención', 'El IVA aplicado debe ser un número válido (0 o mayor).', 'warning'); $('#porcentaje_iva_compra').focus(); return false;
        }
    });
});
</script>
