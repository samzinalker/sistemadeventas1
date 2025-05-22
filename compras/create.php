<?php
// 1. Configuración (antes de cualquier sesión o HTML)
require_once __DIR__ . '/../app/config.php'; // Define $URL, $pdo, $fechaHora, session_start()

// 2. Cargar Datos de Sesión del Usuario y verificar acceso
// layout/sesion.php es llamado por parte1.php. Este script asegura que el usuario esté logueado
// y establece variables de sesión como $_SESSION['id_usuario'], $_SESSION['preferencia_iva'], etc.

$pageTitle = "Registrar Nueva Compra";
$modulo_abierto = "compras"; // Para el menú lateral activo
$pagina_activa = "compras_crear"; // Para el submenú activo

// --- Inicio de la Plantilla AdminLTE ---
// parte1.php DEBE incluir layout/sesion.php al principio para que las variables de sesión estén disponibles.
require_once __DIR__ . '/../layout/parte1.php';

// Verificar si el usuario tiene permiso para acceder a esta página (ejemplo básico)
// Aquí podrías añadir una lógica más compleja basada en roles si es necesario.
if (!isset($_SESSION['id_usuario'])) {
    // layout/sesion.php ya debería haber redirigido si no hay sesión.
    // Esto es una doble verificación por si acaso.
    $_SESSION['mensaje'] = "Acceso denegado. Debe iniciar sesión.";
    $_SESSION['icono'] = "error";
    echo "<script>window.location.href = '{$URL}/login/';</script>";
    exit;
}

// Obtener la preferencia de IVA del usuario de la sesión.
// layout/sesion.php ya la carga en $_SESSION['preferencia_iva']
$preferencia_iva_usuario = $_SESSION['preferencia_iva'] ?? 0.00;

// Obtener proveedores para el select
try {
    $stmt_proveedores = $pdo->query("SELECT id_proveedor, nombre_proveedor, empresa FROM tb_proveedores ORDER BY nombre_proveedor ASC");
    $proveedores = $stmt_proveedores->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['mensaje'] = "Error al cargar proveedores: " . $e->getMessage();
    $_SESSION['icono'] = "error";
    $proveedores = []; // Evitar errores si la consulta falla
}

// Obtener categorías para el modal de creación de producto (asociadas al usuario actual)
try {
    $stmt_categorias = $pdo->prepare("SELECT id_categoria, nombre_categoria FROM tb_categorias WHERE id_usuario = :id_usuario ORDER BY nombre_categoria ASC");
    $stmt_categorias->bindParam(':id_usuario', $_SESSION['id_usuario'], PDO::PARAM_INT);
    $stmt_categorias->execute();
    $categorias_para_modal_producto = $stmt_categorias->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['mensaje'] = "Error al cargar categorías: " . $e->getMessage();
    $_SESSION['icono'] = "error";
    $categorias_para_modal_producto = [];
}
?>

<!-- Content Wrapper. Contiene el contenido de la página -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0"><?php echo htmlspecialchars($pageTitle); ?></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?php echo $URL; ?>/">Inicio</a></li>
                        <li class="breadcrumb-item"><a href="<?php echo $URL; ?>/compras/">Listado de Compras</a></li>
                        <li class="breadcrumb-item active"><?php echo htmlspecialchars($pageTitle); ?></li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <!-- /.content-header -->

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">

            <?php include __DIR__ . '/../layout/mensajes.php'; // Muestra mensajes de $_SESSION['mensaje'] ?>
            
            <form action="<?php echo $URL; ?>/compras/controller_compras.php" method="POST" id="form_nueva_compra">
                <div class="row">
                    <!-- Columna Izquierda: Datos Generales de la Compra -->
                    <div class="col-md-4">
                        <div class="card card-primary card-outline">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-file-invoice-dollar"></i> Datos de la Compra</h3>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="id_proveedor">Proveedor <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <select name="id_proveedor" id="id_proveedor" class="form-control select2bs4" required style="width: 85%;">
                                            <option value="">-- Seleccione un Proveedor --</option>
                                            <?php foreach ($proveedores as $proveedor): ?>
                                                <option value="<?php echo htmlspecialchars($proveedor['id_proveedor']); ?>">
                                                    <?php echo htmlspecialchars(trim($proveedor['nombre_proveedor'] . ($proveedor['empresa'] ? ' ('.$proveedor['empresa'].')' : ''))); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="input-group-append" style="width: 15%;">
                                            <button type="button" class="btn btn-success btn-block" id="btnAbrirModalCrearProveedor" title="Crear Nuevo Proveedor">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="comprobante">Nro. Comprobante <span class="text-danger">*</span></label>
                                    <input type="text" name="comprobante" id="comprobante" class="form-control" required placeholder="Ej: Factura F-001-123">
                                </div>
                                <div class="form-group">
                                    <label for="fecha_compra">Fecha de Compra <span class="text-danger">*</span></label>
                                    <input type="text" name="fecha_compra" id="fecha_compra" class="form-control" required autocomplete="off" placeholder="DD/MM/YYYY" value="<?php echo date('d/m/Y'); ?>">
                                </div>
                                <div class="form-group">
                                    <label for="iva_porcentaje">IVA (%) <span class="text-danger">*</span></label>
                                    <input type="number" name="iva_porcentaje" id="iva_porcentaje" class="form-control" required step="0.01" min="0" max="100" placeholder="Ej: 12.00" value="<?php echo htmlspecialchars(number_format($preferencia_iva_usuario, 2, '.', '')); ?>">
                                </div>
                                <!-- El campo "Usuario Registra" se eliminó, se toma de la sesión en el controlador -->
                            </div>
                        </div>
                    </div>

                    <!-- Columna Derecha: Detalle de Productos -->
                    <div class="col-md-8">
                        <div class="card card-info card-outline">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-boxes"></i> Productos a Comprar</h3>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <button type="button" class="btn btn-primary mb-2" data-toggle="modal" data-target="#modalBuscarProducto">
                                        <i class="fas fa-search"></i> Buscar Producto en Almacén
                                    </button>
                                    <button type="button" class="btn btn-success mb-2" id="btnAbrirModalCrearProducto">
                                        <i class="fas fa-plus-circle"></i> Crear Nuevo Producto
                                    </button>
                                </div>
                                <div class="table-responsive">
                                    <table id="tabla_productos_compra" class="table table-bordered table-hover table-sm">
                                        <thead class="thead-light">
                                            <tr>
                                                <th style="width: 35%;">Producto</th>
                                                <th style="width: 15%;" class="text-center">Cantidad</th>
                                                <th style="width: 20%;" class="text-center">Precio Compra (sin IVA)</th>
                                                <th style="width: 20%;" class="text-right">Subtotal (sin IVA)</th>
                                                <th style="width: 10%;" class="text-center">Acción</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr id="fila_sin_productos">
                                                <td colspan="5" class="text-center text-muted">Aún no se han añadido productos.</td>
                                            </tr>
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <td colspan="3" class="text-right"><strong>SUBTOTAL GENERAL (sin IVA):</strong></td>
                                                <td class="text-right"><strong id="subtotal_general_compra_display">0.00</strong></td>
                                                <td></td>
                                            </tr>
                                            <tr>
                                                <td colspan="3" class="text-right"><strong>TOTAL IVA (<span id="iva_porcentaje_display_total">0.00</span>%):</strong></td>
                                                <td class="text-right"><strong id="total_iva_compra_display">0.00</strong></td>
                                                <td></td>
                                            </tr>
                                            <tr>
                                                <td colspan="3" class="text-right"><strong>TOTAL COMPRA (con IVA):</strong></td>
                                                <td class="text-right"><strong id="gran_total_compra_display">0.00</strong></td>
                                                <td></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-3 mb-4">
                    <div class="col-md-12 text-center">
                        <a href="<?php echo $URL; ?>/compras/" class="btn btn-secondary mr-2"><i class="fas fa-times-circle"></i> Cancelar</a>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar Compra</button>
                    </div>
                </div>
            </form>

        </div><!-- /.container-fluid -->
    </section>
    <!-- /.content -->
</div>
<!-- /.content-wrapper -->

<!-- Modal para Buscar Producto en Almacén -->
<div class="modal fade" id="modalBuscarProducto" tabindex="-1" role="dialog" aria-labelledby="modalBuscarProductoLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalBuscarProductoLabel">Buscar Producto en Almacén</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <table id="tabla_productos_almacen_modal" class="table table-bordered table-striped table-hover table-sm" style="width:100%;">
                    <thead class="thead-dark">
                        <tr>
                            <th>ID</th>
                            <th>Código</th>
                            <th>Categoría</th>
                            <th>Producto</th>
                            <th class="text-center">Stock Actual</th>
                            <th class="text-center">Precio Compra Sug. (sin IVA)</th>
                            <th class="text-center">Acción</th>
                        </tr>
                    </thead>
                    <tbody></tbody> <!-- Los datos se cargarán vía AJAX -->
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para CREAR Proveedor -->
<div class="modal fade" id="modalCrearProveedor" tabindex="-1" role="dialog" aria-labelledby="modalCrearProveedorLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="modalCrearProveedorLabel"><i class="fas fa-truck-loading"></i> Crear Nuevo Proveedor</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <form id="form_crear_proveedor_modal" novalidate>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label for="nombre_proveedor_modal">Nombre Proveedor <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nombre_proveedor_modal" name="nombre_proveedor" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="celular_modal">Celular <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="celular_modal" name="celular" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label for="telefono_modal">Teléfono</label>
                            <input type="text" class="form-control" id="telefono_modal" name="telefono">
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="empresa_modal">Empresa</label>
                            <input type="text" class="form-control" id="empresa_modal" name="empresa">
                        </div>
                    </div>
                    <div class="row">
                         <div class="col-md-6 form-group">
                            <label for="email_modal">Email</label>
                            <input type="email" class="form-control" id="email_modal_prov" name="email"> <!-- ID único para evitar colisión -->
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="direccion_modal">Dirección</label>
                            <textarea class="form-control" id="direccion_modal_prov" name="direccion" rows="2"></textarea> <!-- ID único -->
                        </div>
                    </div>
                    <div id="error_message_proveedor_modal" class="alert alert-danger" style="display: none;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success" id="btn_submit_crear_proveedor_modal"><i class="fas fa-save"></i> Guardar Proveedor</button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- Modal para CREAR Producto -->
<div class="modal fade" id="modalCrearProducto" tabindex="-1" role="dialog" aria-labelledby="modalCrearProductoLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document"> <!-- modal-xl para más espacio -->
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="modalCrearProductoLabel"><i class="fas fa-box-open"></i> Crear Nuevo Producto para Almacén</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <form id="form_crear_producto_modal" novalidate enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8 form-group">
                            <label for="nombre_producto_modal">Nombre Producto <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nombre_producto_modal" name="nombre" required>
                        </div>
                        <div class="col-md-4 form-group">
                             <label for="codigo_producto_modal">Código Producto <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="codigo_producto_modal" name="codigo" required>
                        </div>
                    </div>
                     <div class="row">
                        <div class="col-md-12 form-group">
                            <label for="descripcion_producto_modal">Descripción</label>
                            <textarea class="form-control" id="descripcion_producto_modal" name="descripcion" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 form-group">
                            <label for="id_categoria_modal">Categoría <span class="text-danger">*</span></label>
                            <select class="form-control select2bs4" id="id_categoria_modal" name="id_categoria" required style="width: 100%;">
                                <option value="">-- Seleccione Categoría --</option>
                                <?php foreach ($categorias_para_modal_producto as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat['id_categoria']); ?>"><?php echo htmlspecialchars($cat['nombre_categoria']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 form-group">
                            <label for="stock_producto_modal">Stock Inicial <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="stock_producto_modal" name="stock" required min="0" value="0" step="any">
                        </div>
                         <div class="col-md-4 form-group">
                            <label for="precio_compra_producto_modal">Precio Compra (sin IVA) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="precio_compra_producto_modal" name="precio_compra" required step="0.01" min="0.01">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 form-group">
                            <label for="precio_venta_producto_modal">Precio Venta (sin IVA) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="precio_venta_producto_modal" name="precio_venta" required step="0.01" min="0.01">
                        </div>
                        <div class="col-md-4 form-group">
                            <label for="stock_min_producto_modal">Stock Mínimo</label>
                            <input type="number" class="form-control" id="stock_min_producto_modal" name="stock_minimo" min="0" value="0" step="any">
                        </div>
                        <div class="col-md-4 form-group">
                            <label for="stock_max_producto_modal">Stock Máximo</label>
                            <input type="number" class="form-control" id="stock_max_producto_modal" name="stock_maximo" min="0" value="0" step="any">
                        </div>
                    </div>
                     <div class="row">
                        <div class="col-md-4 form-group">
                            <label for="fecha_ingreso_producto_modal">Fecha Ingreso <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="fecha_ingreso_producto_modal" name="fecha_ingreso" required autocomplete="off" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="col-md-8 form-group">
                            <label for="imagen_producto_modal">Imagen del Producto</label>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" id="imagen_producto_modal" name="imagen_producto">
                                <label class="custom-file-label" for="imagen_producto_modal">Seleccionar archivo</label>
                            </div>
                        </div>
                    </div>
                    <div id="error_message_producto_modal" class="alert alert-danger" style="display: none;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success" id="btn_submit_crear_producto_modal"><i class="fas fa-save"></i> Guardar Producto</button>
                </div>
            </form>
        </div>
    </div>
</div>


<?php
require_once __DIR__ . '/../layout/parte2.php'; // Cierra la plantilla AdminLTE
?>

<!-- Estilos Adicionales que se cargarán después de AdminLTE.css -->
<link rel="stylesheet" href="<?php echo $URL; ?>/public/templeates/AdminLTE-3.2.0/plugins/select2/css/select2.min.css">
<link rel="stylesheet" href="<?php echo $URL; ?>/public/templeates/AdminLTE-3.2.0/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">
<link rel="stylesheet" href="<?php echo $URL; ?>/public/templeates/AdminLTE-3.2.0/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="<?php echo $URL; ?>/public/templeates/AdminLTE-3.2.0/plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
<link rel="stylesheet" href="<?php echo $URL; ?>/public/templeates/AdminLTE-3.2.0/plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css">
<link rel="stylesheet" href="<?php echo $URL; ?>/public/templeates/AdminLTE-3.2.0/plugins/sweetalert2-theme-bootstrap-4/bootstrap-4.min.css">


<!-- Scripts Adicionales -->
<script src="<?php echo $URL; ?>/public/templeates/AdminLTE-3.2.0/plugins/select2/js/select2.full.min.js"></script>
<script src="<?php echo $URL; ?>/public/templeates/AdminLTE-3.2.0/plugins/datatables/jquery.dataTables.min.js"></script>
<script src="<?php echo $URL; ?>/public/templeates/AdminLTE-3.2.0/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="<?php echo $URL; ?>/public/templeates/AdminLTE-3.2.0/plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
<script src="<?php echo $URL; ?>/public/templeates/AdminLTE-3.2.0/plugins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>
<script src="<?php echo $URL; ?>/public/templeates/AdminLTE-3.2.0/plugins/moment/moment.min.js"></script>
<script src="<?php echo $URL; ?>/public/templeates/AdminLTE-3.2.0/plugins/moment/locale/es.js"></script> <!-- Locale para moment.js -->
<script src="<?php echo $URL; ?>/public/templeates/AdminLTE-3.2.0/plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js"></script>
<script src="<?php echo $URL; ?>/public/templeates/AdminLTE-3.2.0/plugins/sweetalert2/sweetalert2.min.js"></script>
<script src="<?php echo $URL; ?>/public/templeates/AdminLTE-3.2.0/plugins/bs-custom-file-input/bs-custom-file-input.min.js"></script>


<script>
$(document).ready(function() {
    // Inicializar Select2 con tema Bootstrap 4
    $('#id_proveedor').select2({ theme: 'bootstrap4' });
    // Para los select2 dentro de modales, es importante especificar el dropdownParent
    $('#id_categoria_modal').select2({ theme: 'bootstrap4', dropdownParent: $('#modalCrearProducto .modal-body') });

    // Inicializar bsCustomFileInput para nombres de archivo en input[type=file]
    bsCustomFileInput.init();

    // Inicializar DatePicker para fecha_compra
    $('#fecha_compra').datetimepicker({
        format: 'DD/MM/YYYY',
        locale: 'es', // Asegúrate de tener el locale 'es' para moment.js
        useCurrent: true,
        icons: { time: 'far fa-clock' }
    });
    // Inicializar DatePicker para fecha_ingreso_producto_modal (formato YYYY-MM-DD para BD)
    $('#fecha_ingreso_producto_modal').datetimepicker({
        format: 'YYYY-MM-DD',
        locale: 'es',
        useCurrent: true,
        icons: { time: 'far fa-clock' }
    });


    // DataTables para la tabla de productos en el modal de búsqueda
    var tablaProductosAlmacen = $('#tabla_productos_almacen_modal').DataTable({
        "processing": true,
        "serverSide": false, // Cambiado a false si ajax_get_productos_almacen.php devuelve todos los datos de una vez
        "ajax": {
            "url": "<?php echo $URL; ?>/compras/ajax_get_productos_almacen.php", // Endpoint que debe devolver JSON
            "type": "GET",
            "dataType": "json",
            "dataSrc": "data" // Asumiendo que el JSON tiene una clave "data" con el array de productos
        },
        "columns": [
            { "data": "id_producto", "visible": false }, // ID oculto pero disponible
            { "data": "codigo" },
            { "data": "nombre_categoria" },
            { "data": "nombre_producto" },
            { "data": "stock", "className": "text-center" },
            { 
                "data": "precio_compra_sugerido", // Este debe ser el precio SIN IVA
                "className": "text-center",
                "render": function(data, type, row) { return parseFloat(data || 0).toFixed(2); }
            },
            { 
                "data": null, "className": "text-center", "width": "100px",
                "render": function(data, type, row) {
                    return `<button type="button" class="btn btn-success btn-sm btn-seleccionar-producto" 
                                    data-id="${row.id_producto}" 
                                    data-nombre="${escapeHtml(row.nombre_producto)}" 
                                    data-codigo="${escapeHtml(row.codigo)}" 
                                    data-precio="${parseFloat(row.precio_compra_sugerido || 0).toFixed(2)}">
                                <i class="fas fa-plus-circle"></i> Añadir
                            </button>`;
                }, "orderable": false
            }
        ],
        "responsive": true, "lengthChange": true, "autoWidth": false, "pageLength": 5,
        "lengthMenu": [ [5, 10, 25, 50, -1], [5, 10, 25, 50, "Todos"] ],
        "language": { "url": "<?php echo $URL;?>/public/templeates/AdminLTE-3.2.0/plugins/datatables-bs4/Spanish.json" }
    });

    function escapeHtml(unsafe) {
        if (typeof unsafe !== 'string') return unsafe;
        return unsafe
             .replace(/&/g, "&amp;")
             .replace(/</g, "&lt;")
             .replace(/>/g, "&gt;")
             .replace(/"/g, "&quot;")
             .replace(/'/g, "&#039;");
    }
    
    var indiceProductoCompra = 0;

    // --- LÓGICA PARA AÑADIR PRODUCTO DESDE MODAL DE BÚSQUEDA ---
    $('#tabla_productos_almacen_modal tbody').on('click', '.btn-seleccionar-producto', function() {
        var idProducto = $(this).data('id');
        var nombreProducto = $(this).data('nombre');
        var codigoProducto = $(this).data('codigo');
        var precioSugerido = parseFloat($(this).data('precio')).toFixed(2);

        var productoExistente = false;
        $('#tabla_productos_compra tbody tr').not('#fila_sin_productos').each(function() {
            if ($(this).find('input[name$="[id_producto]"]').val() == idProducto) {
                productoExistente = true;
                var cantidadInput = $(this).find('input[name$="[cantidad]"]');
                var nuevaCantidad = parseFloat(cantidadInput.val()) + 1;
                cantidadInput.val(nuevaCantidad).trigger('input'); // 'input' para recalcular
                Toast.fire({ icon: 'info', title: 'Cantidad incrementada para ' + nombreProducto });
                return false; 
            }
        });

        if (!productoExistente) {
            indiceProductoCompra++;
            var nuevaFilaHtml = `
                <tr data-id-producto="${idProducto}">
                    <td>
                        <input type="hidden" name="productos[${indiceProductoCompra}][id_producto]" value="${idProducto}">
                        <strong>${nombreProducto}</strong> <br><small class="text-muted">(${codigoProducto})</small>
                    </td>
                    <td><input type="number" name="productos[${indiceProductoCompra}][cantidad]" class="form-control form-control-sm cantidad-producto text-center" value="1" min="0.01" step="any" required></td>
                    <td><input type="number" name="productos[${indiceProductoCompra}][precio_compra]" class="form-control form-control-sm precio-compra-producto text-right" value="${precioSugerido}" min="0.01" step="0.01" required></td>
                    <td class="subtotal-producto text-right">0.00</td>
                    <td class="text-center"><button type="button" class="btn btn-danger btn-sm btn-remover-producto" title="Quitar producto"><i class="fas fa-trash-alt"></i></button></td>
                </tr>`;
            
            if ($('#tabla_productos_compra tbody tr#fila_sin_productos').is(':visible')) {
                $('#fila_sin_productos').hide();
            }
            $('#tabla_productos_compra tbody').append(nuevaFilaHtml);
            actualizarSubtotalFila($('#tabla_productos_compra tbody tr[data-id-producto="'+idProducto+'"]')); // Actualiza la nueva fila
        }
        
        $('#modalBuscarProducto').modal('hide');
        actualizarTotalGeneral(); // Actualiza los totales generales
    });

    function actualizarSubtotalFila(fila) {
        var cantidad = parseFloat(fila.find('.cantidad-producto').val()) || 0;
        var precio = parseFloat(fila.find('.precio-compra-producto').val()) || 0;
        var subtotal = cantidad * precio;
        fila.find('.subtotal-producto').text(subtotal.toFixed(2));
    }

    // Evento para recalcular cuando se cambia cantidad o precio en la tabla de compra
    $('#tabla_productos_compra tbody').on('input change', '.cantidad-producto, .precio-compra-producto', function() {
        var fila = $(this).closest('tr');
        actualizarSubtotalFila(fila);
        actualizarTotalGeneral();
    });

    // Evento para remover producto de la tabla de compra
    $('#tabla_productos_compra tbody').on('click', '.btn-remover-producto', function() {
        $(this).closest('tr').remove();
        if ($('#tabla_productos_compra tbody tr').not('#fila_sin_productos').length === 0) {
            $('#fila_sin_productos').show();
        }
        actualizarTotalGeneral();
    });
    
    // Actualizar IVA display y totales cuando cambia el input de IVA
    $('#iva_porcentaje').on('input change', function() {
        actualizarTotalGeneral();
    });

    function actualizarTotalGeneral() {
        var subtotalGeneral = 0;
        $('#tabla_productos_compra tbody tr').not('#fila_sin_productos').each(function() {
            var subtotalTexto = $(this).find('.subtotal-producto').text();
            subtotalGeneral += parseFloat(subtotalTexto) || 0;
        });
        $('#subtotal_general_compra_display').text(subtotalGeneral.toFixed(2));

        var ivaPorcentaje = parseFloat($('#iva_porcentaje').val()) || 0;
        $('#iva_porcentaje_display_total').text(ivaPorcentaje.toFixed(2)); // Muestra el % de IVA
        
        var totalIva = (subtotalGeneral * ivaPorcentaje) / 100;
        $('#total_iva_compra_display').text(totalIva.toFixed(2));

        var granTotal = subtotalGeneral + totalIva;
        $('#gran_total_compra_display').text(granTotal.toFixed(2));
    }
    
    // --- LÓGICA PARA MODAL CREAR PROVEEDOR ---
    $('#btnAbrirModalCrearProveedor').click(function() {
        $('#form_crear_proveedor_modal')[0].reset(); // Limpia el formulario
        $('#error_message_proveedor_modal').hide().text(''); // Oculta y limpia mensajes de error
        $('#modalCrearProveedor').modal('show');
    });

    $('#form_crear_proveedor_modal').submit(function(e) {
        e.preventDefault();
        $('#error_message_proveedor_modal').hide().text('');
        var formData = $(this).serialize(); // Obtiene datos del formulario

        // Deshabilitar botón para evitar doble envío
        $('#btn_submit_crear_proveedor_modal').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');

        $.ajax({
            url: "<?php echo $URL; ?>/app/controllers/proveedores/create.php", // URL del controlador
            type: "POST",
            data: formData,
            dataType: "json",
            success: function(response) {
                if (response.status === 'success') {
                    $('#modalCrearProveedor').modal('hide');
                    Toast.fire({ icon: 'success', title: response.message });
                    
                    // Actualizar el select de proveedores y seleccionarlo
                    if (response.data && response.data.id_proveedor && response.data.nombre_proveedor_completo) {
                        var newOption = new Option(response.data.nombre_proveedor_completo, response.data.id_proveedor, true, true);
                        $('#id_proveedor').append(newOption).trigger('change');
                    }
                } else {
                    $('#error_message_proveedor_modal').text(response.message || 'Error desconocido al crear proveedor.').show();
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error("Error AJAX proveedor:", textStatus, errorThrown, jqXHR.responseText);
                $('#error_message_proveedor_modal').text('Error de conexión con el servidor al crear proveedor. Detalles en consola.').show();
            },
            complete: function() {
                // Rehabilitar botón
                $('#btn_submit_crear_proveedor_modal').prop('disabled', false).html('<i class="fas fa-save"></i> Guardar Proveedor');
            }
        });
    });
    
    // --- LÓGICA PARA MODAL CREAR PRODUCTO ---
    $('#btnAbrirModalCrearProducto').click(function() {
        $('#form_crear_producto_modal')[0].reset();
        $('#id_categoria_modal').val(null).trigger('change'); // Reset select2
        bsCustomFileInput.destroy(); // Para resetear el nombre del archivo
        bsCustomFileInput.init();    // y reinicializar
        $('#error_message_producto_modal').hide().text('');
        $('#modalCrearProducto').modal('show');
    });

    $('#form_crear_producto_modal').submit(function(e) {
        e.preventDefault();
        $('#error_message_producto_modal').hide().text('');
        var formData = new FormData(this); // Necesario para file uploads

        $('#btn_submit_crear_producto_modal').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');

        $.ajax({
            url: "<?php echo $URL; ?>/app/controllers/almacen/create.php", // Ajustado a la ruta create.php de almacen
            type: "POST",
            data: formData,
            dataType: "json",
            contentType: false, 
            processData: false, 
            success: function(response) {
                if (response.status === 'success') {
                    $('#modalCrearProducto').modal('hide');
                    Toast.fire({ icon: 'success', title: response.message });
                    
                    if (tablaProductosAlmacen) {
                        tablaProductosAlmacen.ajax.reload(null, false); // Recarga DataTables sin resetear paginación
                    }
                    // Opcional: añadir directamente a la tabla de compra si se devuelve el producto creado
                    if(response.new_product_data){ // El controlador debe devolver 'new_product_data'
                         var nuevoProducto = response.new_product_data;
                         if(nuevoProducto.id_producto && nuevoProducto.nombre && nuevoProducto.codigo && nuevoProducto.precio_compra !== undefined){
                            var precioCompra = parseFloat(nuevoProducto.precio_compra || 0).toFixed(2); // Precio SIN IVA
                            indiceProductoCompra++;
                            var nuevaFilaHtml = `
                                <tr data-id-producto="${nuevoProducto.id_producto}">
                                    <td>
                                        <input type="hidden" name="productos[${indiceProductoCompra}][id_producto]" value="${nuevoProducto.id_producto}">
                                        <strong>${escapeHtml(nuevoProducto.nombre)}</strong> <br><small class="text-muted">(${escapeHtml(nuevoProducto.codigo)})</small>
                                    </td>
                                    <td><input type="number" name="productos[${indiceProductoCompra}][cantidad]" class="form-control form-control-sm cantidad-producto text-center" value="1" min="0.01" step="any" required></td>
                                    <td><input type="number" name="productos[${indiceProductoCompra}][precio_compra]" class="form-control form-control-sm precio-compra-producto text-right" value="${precioCompra}" min="0.01" step="0.01" required></td>
                                    <td class="subtotal-producto text-right">${precioCompra}</td>
                                    <td class="text-center"><button type="button" class="btn btn-danger btn-sm btn-remover-producto" title="Quitar producto"><i class="fas fa-trash-alt"></i></button></td>
                                </tr>`;
                            
                            if ($('#tabla_productos_compra tbody tr#fila_sin_productos').is(':visible')) {
                                $('#fila_sin_productos').hide();
                            }
                            $('#tabla_productos_compra tbody').append(nuevaFilaHtml);
                            actualizarTotalGeneral();
                         }
                    }
                } else {
                    $('#error_message_producto_modal').text(response.message || 'Error desconocido al crear el producto.').show();
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error("Error AJAX producto:", textStatus, errorThrown, jqXHR.responseText);
                $('#error_message_producto_modal').text('Error de conexión o del servidor al crear producto. Detalles en consola.').show();
            },
            complete: function() {
                $('#btn_submit_crear_producto_modal').prop('disabled', false).html('<i class="fas fa-save"></i> Guardar Producto');
            }
        });
    });

    // Validación del formulario principal antes de enviar
    $('#form_nueva_compra').on('submit', function(e) {
        // Validar que haya al menos un producto
        if ($('#tabla_productos_compra tbody tr').not('#fila_sin_productos').length === 0) {
            e.preventDefault(); 
            Swal.fire({ icon: 'error', title: 'Error de Validación', text: 'Debe añadir al menos un producto a la compra.' });
            return false;
        }
        // Validar que el IVA sea un número y esté en rango
        var ivaInput = $('#iva_porcentaje');
        var iva = parseFloat(ivaInput.val());
        if (isNaN(iva) || iva < 0 || iva > 100) { // Suponiendo un IVA máximo de 100%
            e.preventDefault();
            Swal.fire({ icon: 'error', title: 'Error de Validación', text: 'El porcentaje de IVA no es válido (debe ser entre 0 y 100).' });
            ivaInput.focus();
            return false;
        }
        // Validar que cada producto tenga cantidad y precio válidos
        var productosValidos = true;
        $('#tabla_productos_compra tbody tr').not('#fila_sin_productos').each(function() {
            var cantidad = parseFloat($(this).find('.cantidad-producto').val());
            var precio = parseFloat($(this).find('.precio-compra-producto').val());
            if (isNaN(cantidad) || cantidad <= 0 || isNaN(precio) || precio <= 0) {
                productosValidos = false;
                return false; // Rompe el each
            }
        });

        if (!productosValidos) {
            e.preventDefault();
            Swal.fire({ icon: 'error', title: 'Error de Validación', text: 'Todos los productos deben tener una cantidad y precio de compra válidos (mayores a cero).' });
            return false;
        }

        // Si todo es válido, el formulario se enviará. Podrías mostrar un spinner aquí si el envío es largo.
    });

    // Configuración global para SweetAlert2 (Toast)
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer)
            toast.addEventListener('mouseleave', Swal.resumeTimer)
        }
    });

    actualizarTotalGeneral(); // Calcular totales iniciales si hay IVA por defecto o productos cargados
});
</script>
</body>
</html>