<?php
// 1. Configuración (antes de cualquier sesión o HTML)
require_once __DIR__ . '/../app/config.php'; // Define $URL, $pdo, $fechaHora

// 2. Iniciar Sesión y Cargar Datos de Sesión del Usuario
// Es crucial que layout/sesion.php se incluya aquí para que $nombres_sesion y otras
// variables de sesión estén disponibles para layout/parte1.php y el resto de la página.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../layout/sesion.php'; // <<<----- AÑADIR ESTA LÍNEA

// Verificar si después de layout/sesion.php, el usuario sigue logueado.
// layout/sesion.php ya redirige si no hay sesión, pero una doble verificación no daña.
if (!isset($_SESSION['id_usuario'])) {
    // Este header podría ser redundante si layout/sesion.php ya lo hizo,
    // pero es una salvaguarda.
    header('Location: ' . rtrim($URL, '/') . '/login.php');
    exit;
}
$id_usuario_actual = (int)$_SESSION['id_usuario'];
// $nombres_sesion es establecido por layout/parte1.php (vía layout/sesion.php)

$pageTitle = "Registrar Nueva Compra";
$modulo_abierto = "compras"; 
$pagina_activa = "compras_create"; 

// --- Obtener datos necesarios ---
$stmt_proveedores = $pdo->prepare("SELECT id_proveedor, nombre_proveedor, empresa FROM tb_proveedores WHERE id_usuario = :id_usuario ORDER BY nombre_proveedor ASC");
$stmt_proveedores->bindParam(':id_usuario', $id_usuario_actual, PDO::PARAM_INT);
$stmt_proveedores->execute();
$proveedores_list = $stmt_proveedores->fetchAll(PDO::FETCH_ASSOC);

$stmt_productos_almacen = $pdo->prepare("SELECT id_producto, codigo, nombre, precio_compra, stock FROM tb_almacen WHERE id_usuario = :id_usuario ORDER BY nombre ASC");
$stmt_productos_almacen->bindParam(':id_usuario', $id_usuario_actual, PDO::PARAM_INT);
$stmt_productos_almacen->execute();
$productos_almacen_list = $stmt_productos_almacen->fetchAll(PDO::FETCH_ASSOC);

$stmt_categorias = $pdo->prepare("SELECT id_categoria, nombre_categoria FROM tb_categorias WHERE id_usuario = :id_usuario ORDER BY nombre_categoria ASC");
$stmt_categorias->bindParam(':id_usuario', $id_usuario_actual, PDO::PARAM_INT);
$stmt_categorias->execute();
$categorias_list = $stmt_categorias->fetchAll(PDO::FETCH_ASSOC);

// 5. Generar Número de Compra Secuencial POR USUARIO
// Cada usuario tiene su propia secuencia de nro_compra.
$stmt_max_nro_usuario = $pdo->prepare("SELECT MAX(nro_compra) as max_nro FROM tb_compras WHERE id_usuario = :id_usuario");
$stmt_max_nro_usuario->bindParam(':id_usuario', $id_usuario_actual, PDO::PARAM_INT);
$stmt_max_nro_usuario->execute();
$max_nro_actual_usuario = $stmt_max_nro_usuario->fetchColumn();
$nro_compra_sugerido = ($max_nro_actual_usuario) ? $max_nro_actual_usuario + 1 : 1;

// Consideraciones Adicionales para nro_compra (Secuencial por Usuario):
//
// 1. Aislamiento de Datos: Este enfoque asegura que el `nro_compra` es secuencial DENTRO del contexto de cada usuario.
//    El Usuario A tendrá compras 1, 2, 3... y el Usuario B también tendrá 1, 2, 3...
//    Esto es lógico si cada usuario gestiona un "negocio" o "instancia" separada dentro del sistema.
//    La unicidad global de una compra se sigue garantizando por `id_compra` (PK de `tb_compras` que es por ítem)
//    o por la combinación `(nro_compra, id_usuario)` si se define un índice UNIQUE sobre estos dos campos
//    en `tb_compras` para agrupar los ítems de una compra específica de un usuario.
//
// 2. Concurrencia: Si un MISMO usuario intenta crear dos compras nuevas exactamente al mismo tiempo (ej. abriendo dos pestañas),
//    ambas podrían obtener el mismo `$nro_compra_sugerido`.
//    Para manejar esto:
//    a) Se puede añadir un índice UNIQUE en `tb_compras` sobre `(nro_compra, id_usuario)`.
//       Luego, en `acciones_compras.php`, al intentar insertar, la base de datos rechazaría la segunda inserción
//       si el par `(nro_compra, id_usuario)` ya existe. Se debería capturar esta excepción y pedir al usuario
//       que reintente (lo que implicaría regenerar el `nro_compra` en `create.php` o en `acciones_compras.php`).
//    b) `acciones_compras.php` podría re-calcular el `nro_compra` justo antes de la inserción dentro de la transacción,
//       bloqueando potencialmente las filas relevantes para ese usuario para asegurar la secuencialidad. Esto es más complejo.
//    Para la mayoría de los casos donde un solo usuario no suele hacer compras simultáneas extremas, el enfoque actual
//    con un posible índice UNIQUE es una buena primera aproximación.
//
// 3. Formato: Si se deseara un formato como "C-[ID_USUARIO]-00001", se podría construir:
//    $nro_compra_formateado = "C-" . $id_usuario_actual . "-" . str_pad($nro_compra_sugerido, 5, "0", STR_PAD_LEFT);
//    En este caso, el campo `nro_compra` en la BD debería ser VARCHAR. Actualmente es INT.
//    Si `nro_compra` se mantiene como INT, el formato se aplicaría solo para visualización.
//
// 4. `acciones_compras.php`: Este script es el que finalmente inserta el `nro_compra` en `tb_compras`.
//    El `nro_compra` generado aquí en `create.php` se envía con el formulario. `acciones_compras.php` lo usa.
//    Si se implementa la re-verificación por concurrencia (punto 2b), `acciones_compras.php`
//    tendría que hacer esa lógica. Por ahora, se asume que el número sugerido es el que se intentará usar.

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
                </div><!-- /.col -->
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?php echo $URL; ?>/index.php">Inicio</a></li>
                        <li class="breadcrumb-item"><a href="<?php echo $URL; ?>/compras/index.php">Compras</a></li>
                        <li class="breadcrumb-item active"><?php echo htmlspecialchars($pageTitle); ?></li>
                    </ol>
                </div><!-- /.col -->
            </div><!-- /.row -->
        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">

            <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                <h5><i class="icon fas fa-ban"></i> Error!</h5>
                <?php echo htmlspecialchars(urldecode($_GET['error'])); ?>
            </div>
            <?php endif; ?>
            <?php if (isset($_GET['success_producto'])): ?>
            <div class="alert alert-success alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                <h5><i class="icon fas fa-check"></i> Éxito!</h5>
                <?php echo htmlspecialchars(urldecode($_GET['success_producto'])); ?>
            </div>
            <?php endif; ?>
            
            <?php include __DIR__ . '/../layout/mensajes.php'; ?>


            <form id="form_nueva_compra" action="acciones_compras.php" method="POST">
                <input type="hidden" name="accion" value="registrar_compra">

                <!-- Datos Generales de la Compra -->
                <div class="card card-primary card-outline mb-3">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-info-circle"></i> Datos Generales de la Compra</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="nro_compra">Número de Compra (Usuario <?php echo $id_usuario_actual; ?>):</label>
                                    <input type="text" class="form-control form-control-sm" id="nro_compra" name="nro_compra" value="<?php echo $nro_compra_sugerido; ?>" required readonly>
                                    <small class="form-text text-muted">Secuencia para este usuario.</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="fecha_compra">Fecha de Compra:</label>
                                    <input type="date" class="form-control form-control-sm" id="fecha_compra" name="fecha_compra" value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="comprobante">Comprobante (Factura N°):</label>
                                    <input type="text" class="form-control form-control-sm" id="comprobante" name="comprobante" placeholder="Ej: F001-12345" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="id_proveedor">Proveedor:</label>
                                    <select class="form-control form-control-sm select2bs4" id="id_proveedor" name="id_proveedor" style="width: 100%;" required>
                                        <option value="">-- Seleccione Proveedor --</option>
                                        <?php foreach ($proveedores_list as $proveedor): ?>
                                            <option value="<?php echo htmlspecialchars($proveedor['id_proveedor']); ?>">
                                                <?php echo htmlspecialchars($proveedor['nombre_proveedor']) . ($proveedor['empresa'] ? ' ('.htmlspecialchars($proveedor['empresa']).')' : ''); ?>
                                            </option>
                                        <?php endforeach; ?>
                                        <?php if (count($proveedores_list) === 0): ?>
                                            <option value="" disabled>No hay proveedores. <a href="<?php echo $URL; ?>/proveedores/create.php">Agregar</a></option>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Productos de la Compra -->
                <div class="card card-info card-outline mb-3">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-boxes"></i> Ítems de la Compra</h3>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="producto_buscar_almacen">Buscar Producto en Almacén:</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                                        </div>
                                        <input type="text" id="producto_buscar_almacen" class="form-control form-control-sm" placeholder="Buscar por código o nombre...">
                                    </div>
                                    <div id="sugerencias_productos_almacen" class="list-group mt-1" style="position: absolute; z-index: 1000; width: calc(100% - 40px);"></div>
                                </div>
                            </div>
                            <div class="col-md-6 align-self-center text-right">
                                <button type="button" class="btn btn-sm btn-success" data-toggle="modal" data-target="#modalCrearProductoAlmacen">
                                    <i class="fas fa-plus-circle"></i> Crear Nuevo Producto en Almacén
                                </button>
                            </div>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered table-hover" id="tabla_items_compra_full">
                                <thead class="thead-light">
                                    <tr>
                                        <th style="width: 10%;">Código</th>
                                        <th style="width: 30%;">Producto (Almacén)</th>
                                        <th style="width: 15%;">Cantidad</th>
                                        <th style="width: 20%;">Precio Compra (Unit.)</th>
                                        <th style="width: 15%;">Subtotal</th>
                                        <th style="width: 10%;">Acción</th>
                                    </tr>
                                </thead>
                                <tbody id="tabla_items_compra_body">
                                    <tr id="fila_sin_items">
                                        <td colspan="6" class="text-center">Aún no se han agregado productos.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer text-right" style="background-color: #f0f0f0; border-top: 2px solid #007bff;">
                        <h3 class="mb-0">Total Compra: <span id="total_compra_display" class="total-compra-display text-primary font-weight-bold">0.00</span></h3>
                        <input type="hidden" name="total_compra_valor_final" id="total_compra_valor_final" value="0.00">
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-12 text-center">
                        <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save"></i> Registrar Compra</button>
                        <a href="index.php" class="btn btn-secondary btn-lg"><i class="fas fa-times-circle"></i> Cancelar</a>
                    </div>
                </div>
            </form>

        </div><!-- /.container-fluid -->
    </section>
    <!-- /.content -->
</div>
<!-- /.content-wrapper -->

<!-- Modal para Crear Nuevo Producto en Almacén -->
<div class="modal fade" id="modalCrearProductoAlmacen" tabindex="-1" role="dialog" aria-labelledby="modalCrearProductoAlmacenLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form id="form_crear_producto_almacen_modal">
                <input type="hidden" name="accion" value="crear_producto_almacen_rapido">
                <div class="modal-header bg-success">
                    <h5 class="modal-title" id="modalCrearProductoAlmacenLabel"><i class="fas fa-cube"></i> Crear Nuevo Producto en Almacén</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="producto_modal_codigo">Código:</label>
                                <input type="text" class="form-control form-control-sm" id="producto_modal_codigo" name="producto_codigo" placeholder="Ej: P001 (Opcional)">
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="producto_modal_nombre">Nombre Producto: <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm" id="producto_modal_nombre" name="producto_nombre" required>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="producto_modal_descripcion">Descripción (Opcional):</label>
                        <textarea class="form-control form-control-sm" id="producto_modal_descripcion" name="producto_descripcion" rows="2"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="producto_modal_id_categoria">Categoría: <span class="text-danger">*</span></label>
                                <select class="form-control form-control-sm select2bs4" id="producto_modal_id_categoria" name="producto_id_categoria" style="width: 100%;" required>
                                    <option value="">-- Seleccione Categoría --</option>
                                    <?php foreach ($categorias_list as $categoria): ?>
                                        <option value="<?php echo htmlspecialchars($categoria['id_categoria']); ?>"><?php echo htmlspecialchars($categoria['nombre_categoria']); ?></option>
                                    <?php endforeach; ?>
                                     <?php if (count($categorias_list) === 0): ?>
                                        <option value="" disabled>No hay categorías. <a href="<?php echo $URL; ?>/categorias/">Agregar</a></option>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>
                         <div class="col-md-6">
                            <div class="form-group">
                                <label for="producto_modal_fecha_ingreso">Fecha Ingreso: <span class="text-danger">*</span></label>
                                <input type="date" class="form-control form-control-sm" id="producto_modal_fecha_ingreso" name="producto_fecha_ingreso" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="producto_modal_precio_compra">Precio Compra (Actual): <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" class="form-control form-control-sm" id="producto_modal_precio_compra" name="producto_precio_compra" required min="0" value="0.00">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="producto_modal_precio_venta">Precio Venta: <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" class="form-control form-control-sm" id="producto_modal_precio_venta" name="producto_precio_venta" required min="0" value="0.00">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="producto_modal_stock_inicial">Stock Inicial:</label>
                                <input type="number" class="form-control form-control-sm" id="producto_modal_stock_inicial" name="producto_stock_inicial" value="0" readonly title="El stock se actualizará con la cantidad de la compra.">
                            </div>
                        </div>
                    </div>
                     <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="producto_modal_stock_minimo">Stock Mínimo (Opcional):</label>
                                <input type="number" class="form-control form-control-sm" id="producto_modal_stock_minimo" name="producto_stock_minimo" min="0">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="producto_modal_stock_maximo">Stock Máximo (Opcional):</label>
                                <input type="number" class="form-control form-control-sm" id="producto_modal_stock_maximo" name="producto_stock_maximo" min="0">
                            </div>
                        </div>
                    </div>
                    <small class="form-text text-muted"><span class="text-danger">*</span> Campos obligatorios.</small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> Cancelar</button>
                    <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Guardar Producto en Almacén</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../layout/parte2.php';
?>

<script>
    $(document).ready(function() {
        // Inicializar Select2
        if ($.fn.select2) {
            $('.select2bs4').select2({
                theme: 'bootstrap4',
            });
            // Para el select dentro del modal, si se reinicializa o tiene problemas de foco:
             $('#modalCrearProductoAlmacen').on('shown.bs.modal', function () {
                // Asegurarse de que el ID del select sea el correcto
                $('#producto_modal_id_categoria').select2({ 
                    theme: 'bootstrap4',
                    dropdownParent: $('#modalCrearProductoAlmacen .modal-body') // Contenedor correcto
                });
            });
        }

        let productosAlmacenData = <?php echo json_encode($productos_almacen_list); ?>;
        let itemsCompraList = []; 
        let itemCounterSuffix = 0; 

        $('#producto_buscar_almacen').on('input', function() { 
            let searchTerm = $(this).val().toLowerCase();
            let sugerenciasDiv = $('#sugerencias_productos_almacen');
            sugerenciasDiv.empty().hide();

            if (searchTerm.length < 2) return;

            let filtrados = productosAlmacenData.filter(function(producto) {
                return producto.nombre.toLowerCase().includes(searchTerm) || producto.codigo.toLowerCase().includes(searchTerm);
            });

            if (filtrados.length > 0) {
                filtrados.slice(0, 10).forEach(function(producto) { 
                    sugerenciasDiv.append(
                        `<a href="#" class="list-group-item list-group-item-action list-group-item-sm seleccionar-producto-sugerencia-almacen" 
                            data-id="${producto.id_producto}" 
                            data-nombre="${escapeHtml(producto.nombre)}" 
                            data-codigo="${escapeHtml(producto.codigo)}" 
                            data-precio_compra="${parseFloat(producto.precio_compra || 0).toFixed(2)}"
                            data-stock="${producto.stock}">
                            <strong>${escapeHtml(producto.codigo)}</strong> - ${escapeHtml(producto.nombre)} 
                            <span class="badge badge-info float-right">Stock: ${producto.stock}</span>
                            <span class="badge badge-secondary float-right mr-1">Precio: ${parseFloat(producto.precio_compra || 0).toFixed(2)}</span>
                        </a>`
                    );
                });
                sugerenciasDiv.show();
            } else {
                sugerenciasDiv.append('<span class="list-group-item list-group-item-sm text-muted">No se encontraron productos.</span>').show();
            }
        });

        $(document).on('click', '.seleccionar-producto-sugerencia-almacen', function(e) {
            e.preventDefault();
            const id = $(this).data('id');
            const nombre = $(this).data('nombre');
            const codigo = $(this).data('codigo');
            const precio_compra = parseFloat($(this).data('precio_compra'));
            
            agregarItemCompraATabla(id, codigo, nombre, 1, precio_compra);
            
            $('#producto_buscar_almacen').val('');
            $('#sugerencias_productos_almacen').empty().hide();
        });

        $(document).on('click', function(e) {
            if (!$(e.target).closest('#producto_buscar_almacen, #sugerencias_productos_almacen').length) {
                $('#sugerencias_productos_almacen').empty().hide();
            }
        });

        $('#form_crear_producto_almacen_modal').on('submit', function(e) {
            e.preventDefault();
            let form = $(this);
            let formData = form.serialize(); 

            form.find('button[type="submit"]').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');

            $.ajax({
                url: '<?php echo $URL; ?>/almacen/acciones_almacen.php',
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success' && response.producto) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Producto Creado',
                            text: response.message,
                            timer: 2000,
                            showConfirmButton: false
                        });

                        $('#modalCrearProductoAlmacen').modal('hide');
                        // form[0].reset(); // Se resetea en hidden.bs.modal
                        
                        productosAlmacenData.push(response.producto);

                        agregarItemCompraATabla(
                            response.producto.id_producto, 
                            response.producto.codigo, 
                            response.producto.nombre, 
                            1, 
                            parseFloat(response.producto.precio_compra)
                        );
                    } else {
                         Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.message || 'No se pudo crear el producto.'
                        });
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                     Swal.fire({
                        icon: 'error',
                        title: 'Error de Conexión',
                        text: 'No se pudo comunicar con el servidor: ' + textStatus
                    });
                },
                complete: function() {
                    form.find('button[type="submit"]').prop('disabled', false).html('<i class="fas fa-save"></i> Guardar Producto en Almacén');
                }
            });
        });
        
        function agregarItemCompraATabla(id_producto_almacen, codigo_producto, nombre_producto, cantidad, precio_compra) {
            let productoExistente = itemsCompraList.find(item => parseInt(item.id_producto_almacen) === parseInt(id_producto_almacen));
            if (productoExistente) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Producto ya en lista',
                    text: 'Este producto ya ha sido agregado.',
                    timer: 2500,
                    showConfirmButton: false
                });
                return;
            }

            $('#fila_sin_items').hide();
            itemCounterSuffix++; 
            
            let subtotal = parseFloat(cantidad) * parseFloat(precio_compra);

            let filaHtml = `
                <tr id="item_row_${itemCounterSuffix}">
                    <td>
                        ${escapeHtml(codigo_producto)}
                        <input type="hidden" name="items[${itemCounterSuffix}][id_producto_almacen]" value="${id_producto_almacen}">
                    </td>
                    <td>${escapeHtml(nombre_producto)}</td>
                    <td><input type="number" class="form-control form-control-sm cantidad-item" name="items[${itemCounterSuffix}][cantidad]" value="${cantidad}" min="1" required data-item-suffix="${itemCounterSuffix}"></td>
                    <td><input type="number" step="0.01" class="form-control form-control-sm precio-item" name="items[${itemCounterSuffix}][precio_compra]" value="${parseFloat(precio_compra).toFixed(2)}" min="0" required data-item-suffix="${itemCounterSuffix}"></td>
                    <td class="subtotal-item align-middle text-right">${subtotal.toFixed(2)}</td>
                    <td class="text-center align-middle">
                        <button type="button" class="btn btn-danger btn-xs eliminar-item-compra" data-item-suffix="${itemCounterSuffix}" title="Eliminar ítem">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
            $('#tabla_items_compra_body').append(filaHtml);
            
            itemsCompraList.push({ 
                item_suffix: itemCounterSuffix, 
                id_producto_almacen: parseInt(id_producto_almacen), 
                cantidad: parseFloat(cantidad), 
                precio_compra: parseFloat(precio_compra)
            });
            calcularTotalCompraFinal();
        }

        $('#tabla_items_compra_body').on('change keyup', '.cantidad-item, .precio-item', function() {
            let suffix = $(this).data('item-suffix');
            // Asegurarse de que los selectores de cantidad y precio sean correctos para los inputs dentro de la fila
            let cantidad = parseFloat($('#item_row_' + suffix + ' .cantidad-item').val()) || 0;
            let precio = parseFloat($('#item_row_' + suffix + ' .precio-item').val()) || 0;
            let subtotal = cantidad * precio;
            $('#item_row_' + suffix + ' .subtotal-item').text(subtotal.toFixed(2));
            
            let itemEnArray = itemsCompraList.find(item => item.item_suffix === suffix);
            if(itemEnArray){
                itemEnArray.cantidad = cantidad;
                itemEnArray.precio_compra = precio;
            }
            calcularTotalCompraFinal();
        });

        $('#tabla_items_compra_body').on('click', '.eliminar-item-compra', function() {
            let suffix = $(this).data('item-suffix');
            $('#item_row_' + suffix).remove();
            itemsCompraList = itemsCompraList.filter(item => item.item_suffix !== suffix);
            if (itemsCompraList.length === 0) {
                $('#fila_sin_items').show();
            }
            calcularTotalCompraFinal();
        });

        function calcularTotalCompraFinal() {
            let totalGeneral = 0;
            itemsCompraList.forEach(function(item) {
                totalGeneral += item.cantidad * item.precio_compra;
            });
            $('#total_compra_display').text(totalGeneral.toFixed(2));
            $('#total_compra_valor_final').val(totalGeneral.toFixed(2));
        }

        $('#form_nueva_compra').on('submit', function(e){
            if(itemsCompraList.length === 0){
                 Swal.fire('Atención', 'Debe agregar al menos un producto a la compra.', 'warning');
                e.preventDefault(); return false;
            }
            let totalFinal = parseFloat($('#total_compra_valor_final').val());
            if(totalFinal <= 0){
                Swal.fire('Atención', 'El total de la compra debe ser mayor a cero.', 'warning');
                e.preventDefault(); return false;
            }
        });

        function escapeHtml(unsafe) {
            if (typeof unsafe !== 'string') return '';
            return unsafe
                 .replace(/&/g, "&amp;")
                 .replace(/</g, "&lt;")
                 .replace(/>/g, "&gt;")
                 .replace(/"/g, "&quot;")
                 .replace(/'/g, "&#039;");
        }
        
        $('#modalCrearProductoAlmacen').on('hidden.bs.modal', function () {
            $(this).find('form')[0].reset();
            // Asegurarse de que el ID del select sea el correcto para resetearlo
            $('#producto_modal_id_categoria').val(null).trigger('change'); 
        });
    });
</script>
</body>
</html>
