<?php
// 1. Configuración (antes de cualquier sesión o HTML)
require_once __DIR__ . '/../app/config.php'; // Define $URL, $pdo, $fechaHora, session_start()

// 2. Iniciar Sesión y Cargar Datos de Sesión del Usuario
if (session_status() == PHP_SESSION_NONE) { // Doble check por si config.php no lo hizo
    session_start();
}
require_once __DIR__ . '/../layout/sesion.php'; // Carga datos del usuario en $_SESSION y define $nombres_sesion

// Verificar si el usuario está logueado
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ' . rtrim($URL, '/') . '/login.php?error=access_denied');
    exit;
}
$id_usuario_actual = (int)$_SESSION['id_usuario'];

$pageTitle = "Registrar Nueva Compra";
$modulo_abierto = "compras";
$pagina_activa = "compras_crear";

// Obtener proveedores para el select
$stmt_proveedores = $pdo->query("SELECT id_proveedor, nombre_proveedor, empresa FROM tb_proveedores ORDER BY nombre_proveedor ASC");
$proveedores = $stmt_proveedores->fetchAll(PDO::FETCH_ASSOC);

// Repoblar formulario si hay datos guardados en sesión (ej. por error en controller)
// $form_data = $_SESSION['compra_form_data'] ?? [];
// unset($_SESSION['compra_form_data']); // Limpiar después de usar

// --- Inicio de la Plantilla AdminLTE ---
require_once __DIR__ . '/../layout/parte1.php';
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

            <?php include __DIR__ . '/../layout/mensajes.php'; // Para mostrar $_GET['error'], $_GET['success'], $_GET['info'] ?>
            
            <form action="controller_compras.php" method="POST" id="form_nueva_compra">
                <div class="row">
                    <!-- Columna Izquierda: Datos Generales de la Compra -->
                    <div class="col-md-4">
                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-shopping-cart"></i> Datos de la Compra</h3>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="id_proveedor">Proveedor <span class="text-danger">*</span></label>
                                    <select name="id_proveedor" id="id_proveedor" class="form-control select2" required>
                                        <option value="">-- Seleccione un Proveedor --</option>
                                        <?php foreach ($proveedores as $proveedor): ?>
                                            <option value="<?php echo htmlspecialchars($proveedor['id_proveedor']); ?>">
                                                <?php echo htmlspecialchars($proveedor['nombre_proveedor'] . ($proveedor['empresa'] ? ' ('.$proveedor['empresa'].')' : '')); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="comprobante">Nro. Comprobante <span class="text-danger">*</span></label>
                                    <input type="text" name="comprobante" id="comprobante" class="form-control" required placeholder="Ej: Factura F-001-123, Boleta B-001-456">
                                </div>
                                <div class="form-group">
                                    <label for="fecha_compra">Fecha de Compra <span class="text-danger">*</span></label>
                                    <input type="text" name="fecha_compra" id="fecha_compra" class="form-control" required autocomplete="off" placeholder="DD/MM/YYYY" value="<?php echo date('d/m/Y'); ?>">
                                </div>
                                 <div class="form-group">
                                    <label>Usuario Registra:</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($nombres_sesion . ' ' . $apellidos_sesion); ?>" readonly>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Columna Derecha: Detalle de Productos -->
                    <div class="col-md-8">
                        <div class="card card-info">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-boxes"></i> Productos a Comprar</h3>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <button type="button" class="btn btn-primary mb-3" data-toggle="modal" data-target="#modalBuscarProducto">
                                        <i class="fas fa-search"></i> Buscar y Añadir Producto del Almacén
                                    </button>
                                </div>
                                <div class="table-responsive">
                                    <table id="tabla_productos_compra" class="table table-bordered table-hover table-sm">
                                        <thead class="thead-light">
                                            <tr>
                                                <th style="width: 40%;">Producto</th>
                                                <th style="width: 15%;" class="text-center">Cantidad</th>
                                                <th style="width: 20%;" class="text-center">Precio Compra (Bs.)</th>
                                                <th style="width: 20%;" class="text-right">Subtotal (Bs.)</th>
                                                <th style="width: 5%;">Acción</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- Los productos seleccionados se añadirán aquí por JavaScript -->
                                            <tr id="fila_sin_productos">
                                                <td colspan="5" class="text-center text-muted">Aún no se han añadido productos.</td>
                                            </tr>
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <td colspan="3" class="text-right"><strong>TOTAL COMPRA:</strong></td>
                                                <td class="text-right"><strong id="total_compra_display">0.00</strong></td>
                                                <td></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
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
                            <th class="text-center">Precio Compra Sug. (Bs.)</th>
                            <th class="text-center">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Los datos se cargarán aquí por DataTables/AJAX -->
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>


<?php
// --- Fin de la Plantilla AdminLTE ---
require_once __DIR__ . '/../layout/parte2.php';
?>

<!-- Estilos Adicionales -->
<link rel="stylesheet" href="<?php echo $URL; ?>/public/templeates/AdminLTE-3.2.0/plugins/select2/css/select2.min.css">
<link rel="stylesheet" href="<?php echo $URL; ?>/public/templeates/AdminLTE-3.2.0/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">
<link rel="stylesheet" href="<?php echo $URL; ?>/public/templeates/AdminLTE-3.2.0/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="<?php echo $URL; ?>/public/templeates/AdminLTE-3.2.0/plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
<link rel="stylesheet" href="<?php echo $URL; ?>/public/templeates/AdminLTE-3.2.0/plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css">


<!-- Scripts Adicionales -->
<script src="<?php echo $URL; ?>/public/templeates/AdminLTE-3.2.0/plugins/select2/js/select2.full.min.js"></script>
<script src="<?php echo $URL; ?>/public/templeates/AdminLTE-3.2.0/plugins/datatables/jquery.dataTables.min.js"></script>
<script src="<?php echo $URL; ?>/public/templeates/AdminLTE-3.2.0/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="<?php echo $URL; ?>/public/templeates/AdminLTE-3.2.0/plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
<script src="<?php echo $URL; ?>/public/templeates/AdminLTE-3.2.0/plugins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>
<script src="<?php echo $URL; ?>/public/templeates/AdminLTE-3.2.0/plugins/moment/moment.min.js"></script>
<script src="<?php echo $URL; ?>/public/templeates/AdminLTE-3.2.0/plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js"></script>


<script>
$(document).ready(function() {
    // Inicializar Select2
    $('.select2').select2({
        theme: 'bootstrap4'
    });

    // Inicializar DatePicker para fecha_compra
    $('#fecha_compra').datetimepicker({
        format: 'DD/MM/YYYY',
        locale: 'es',
        icons: {
            time: 'far fa-clock',
            date: 'far fa-calendar-alt',
            up: 'fas fa-chevron-up',
            down: 'fas fa-chevron-down',
            previous: 'fas fa-chevron-left',
            next: 'fas fa-chevron-right',
            today: 'far fa-calendar-check',
            clear: 'far fa-trash-alt',
            close: 'fas fa-times'
        },
        buttons: {
            showToday: true,
            showClear: false,
            showClose: true
        }
    });

    // DataTables para la tabla de productos en el modal
    var tablaProductosAlmacen = $('#tabla_productos_almacen_modal').DataTable({
        "processing": true,
        "serverSide": false, // Poner true si ajax_get_productos_almacen.php implementa server-side
        "ajax": {
            "url": "<?php echo $URL; ?>/compras/ajax_get_productos_almacen.php",
            "type": "GET", // o POST si tu script PHP lo espera así
            "dataType": "json",
            "dataSrc": "data" // Asegúrate que tu JSON tenga una raíz "data"
        },
        "columns": [
            { "data": "id_producto", "visible": false }, // Oculto pero disponible
            { "data": "codigo" },
            { "data": "nombre_categoria" },
            { "data": "nombre_producto" },
            { "data": "stock", "className": "text-center" },
            { 
                "data": "precio_compra_sugerido",
                "className": "text-center",
                "render": function(data, type, row) {
                    return parseFloat(data || 0).toFixed(2);
                }
            },
            { 
                "data": null,
                "className": "text-center",
                "render": function(data, type, row) {
                    return `<button type="button" class="btn btn-success btn-sm btn-seleccionar-producto" 
                                    data-id="${row.id_producto}" 
                                    data-nombre="${row.nombre_producto}" 
                                    data-codigo="${row.codigo}"
                                    data-precio="${parseFloat(row.precio_compra_sugerido || 0).toFixed(2)}">
                                <i class="fas fa-plus-circle"></i> Añadir
                            </button>`;
                },
                "orderable": false
            }
        ],
        "responsive": true,
        "lengthChange": true,
        "autoWidth": false,
        "pageLength": 5, // Mostrar 5 productos por página en el modal
        "lengthMenu": [ [5, 10, 25, -1], [5, 10, 25, "Todos"] ],
        "language": { "url": "<?php echo $URL;?>/public/templeates/AdminLTE-3.2.0/plugins/datatables-bs4/Spanish.json" }
    });

    var indiceProductoCompra = 0; // Para los names de los inputs array

    // Manejar clic en "Añadir" producto desde el modal
    $('#tabla_productos_almacen_modal tbody').on('click', '.btn-seleccionar-producto', function() {
        var idProducto = $(this).data('id');
        var nombreProducto = $(this).data('nombre');
        var codigoProducto = $(this).data('codigo');
        var precioSugerido = parseFloat($(this).data('precio')).toFixed(2);

        // Verificar si el producto ya está en la tabla de compra
        var productoExistente = false;
        $('#tabla_productos_compra tbody tr').not('#fila_sin_productos').each(function() {
            if ($(this).find('input[name$="[id_producto]"]').val() == idProducto) {
                productoExistente = true;
                // Opcional: Incrementar cantidad o mostrar alerta
                var cantidadInput = $(this).find('input[name$="[cantidad]"]');
                cantidadInput.val(parseInt(cantidadInput.val()) + 1).trigger('input');
                // alert('El producto ya está en la lista. Se incrementó la cantidad.');
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'info',
                    title: 'Producto ya en lista. Cantidad incrementada.',
                    showConfirmButton: false,
                    timer: 2000
                });
                return false; // Salir del bucle .each
            }
        });

        if (!productoExistente) {
            indiceProductoCompra++;
            var nuevaFilaHtml = `
                <tr data-id-producto="${idProducto}">
                    <td>
                        <input type="hidden" name="productos[${indiceProductoCompra}][id_producto]" value="${idProducto}">
                        <strong>${nombreProducto}</strong> <small class="text-muted">(${codigoProducto})</small>
                    </td>
                    <td><input type="number" name="productos[${indiceProductoCompra}][cantidad]" class="form-control form-control-sm cantidad-producto text-center" value="1" min="1" step="any" required></td>
                    <td><input type="number" name="productos[${indiceProductoCompra}][precio_compra]" class="form-control form-control-sm precio-compra-producto text-right" value="${precioSugerido}" min="0" step="0.01" required></td>
                    <td class="subtotal-producto text-right">0.00</td>
                    <td class="text-center"><button type="button" class="btn btn-danger btn-sm btn-remover-producto"><i class="fas fa-trash-alt"></i></button></td>
                </tr>`;
            
            $('#fila_sin_productos').hide(); // Ocultar mensaje si es la primera fila
            $('#tabla_productos_compra tbody').append(nuevaFilaHtml);
            actualizarSubtotalFila($('#tabla_productos_compra tbody tr[data-id-producto="'+idProducto+'"]'));
        }
        
        $('#modalBuscarProducto').modal('hide'); // Ocultar modal
        actualizarTotalGeneral();
    });

    // Función para actualizar subtotal de una fila
    function actualizarSubtotalFila(fila) {
        var cantidad = parseFloat(fila.find('.cantidad-producto').val()) || 0;
        var precio = parseFloat(fila.find('.precio-compra-producto').val()) || 0;
        var subtotal = cantidad * precio;
        fila.find('.subtotal-producto').text(subtotal.toFixed(2));
    }

    // Actualizar subtotal y total general cuando cambie cantidad o precio en la tabla de compra
    $('#tabla_productos_compra tbody').on('input', '.cantidad-producto, .precio-compra-producto', function() {
        var fila = $(this).closest('tr');
        actualizarSubtotalFila(fila);
        actualizarTotalGeneral();
    });

    // Remover producto de la tabla de compra
    $('#tabla_productos_compra tbody').on('click', '.btn-remover-producto', function() {
        $(this).closest('tr').remove();
        if ($('#tabla_productos_compra tbody tr').not('#fila_sin_productos').length === 0) {
            $('#fila_sin_productos').show();
        }
        actualizarTotalGeneral();
    });

    // Función para recalcular el total general de la compra
    function actualizarTotalGeneral() {
        var totalGeneral = 0;
        $('#tabla_productos_compra tbody tr').not('#fila_sin_productos').each(function() {
            var subtotalTexto = $(this).find('.subtotal-producto').text();
            totalGeneral += parseFloat(subtotalTexto) || 0;
        });
        $('#total_compra_display').text(totalGeneral.toFixed(2));
    }

    // Validación del formulario antes de enviar
    $('#form_nueva_compra').on('submit', function(e) {
        if ($('#tabla_productos_compra tbody tr').not('#fila_sin_productos').length === 0) {
            e.preventDefault(); // Detener envío
            Swal.fire({
                icon: 'error',
                title: 'Error de Validación',
                text: 'Debe añadir al menos un producto a la compra.',
            });
            return false;
        }
        // Otras validaciones si son necesarias
    });

});
</script>
</body>
</html>