<?php
/**
 * Crear Nueva Compra - Permite registrar compras con productos existentes
 * o crear productos nuevos durante el proceso.
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
$pagina_activa = 'crear_compra';

// Obtener proveedores para el dropdown
$sql = "SELECT * FROM tb_proveedores WHERE id_usuario = ? ORDER BY nombre_proveedor";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_usuario]);
$proveedores = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener categorías para el formulario de nuevo producto
$sql = "SELECT * FROM tb_categorias WHERE id_usuario = ? ORDER BY nombre_categoria";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_usuario]);
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener configuración de IVA
$sql = "SELECT valor FROM configuracion WHERE parametro = 'iva_defecto' AND id_usuario = ? LIMIT 1";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_usuario]);
$config = $stmt->fetch(PDO::FETCH_ASSOC);
$config_iva = $config ? floatval($config['valor']) : 0.18; // 18% por defecto

include '../layout/parte1.php';
?>

<div class="content-wrapper">
    <!-- Cabecera -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Nueva Compra</h1>
                </div>
            </div>
        </div>
    </section>

    <!-- Contenido principal -->
    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Registro de Compra</h3>
                </div>
                
                <div class="card-body">
                    <form id="formCompra">
                        <!-- Datos de compra -->
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Proveedor:</label>
                                    <select class="form-control" id="id_proveedor" required>
                                        <option value="">Seleccione un proveedor</option>
                                        <?php foreach ($proveedores as $proveedor): ?>
                                        <option value="<?= $proveedor['id_proveedor'] ?>">
                                            <?= htmlspecialchars($proveedor['nombre_proveedor']) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Fecha:</label>
                                    <input type="date" class="form-control" id="fecha_compra" value="<?= date('Y-m-d') ?>">
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>IVA (%):</label>
                                    <div class="input-group">
                                        <input type="number" step="0.01" class="form-control" id="iva_porcentaje" 
                                               value="<?= $config_iva * 100 ?>">
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-outline-secondary" id="btnGuardarIva">
                                                <i class="fas fa-save"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Búsqueda de productos -->
                        <div class="row mb-3">
                            <div class="col-md-8">
                                <div class="input-group">
                                    <input type="text" class="form-control" id="buscarProducto" 
                                           placeholder="Buscar producto por nombre o código...">
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-primary" id="btnBuscar">
                                            <i class="fas fa-search"></i> Buscar
                                        </button>
                                        <button type="button" class="btn btn-success" id="btnVerTodos">
                                            <i class="fas fa-list"></i> Ver todos
                                        </button>
                                        <button type="button" class="btn btn-info" id="btnNuevoProducto">
                                            <i class="fas fa-plus"></i> Nuevo producto
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Tabla de productos de la compra -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm">
                                <thead>
                                    <tr>
                                        <th>Código</th>
                                        <th>Producto</th>
                                        <th>Precio</th>
                                        <th>Cantidad</th>
                                        <th>Subtotal</th>
                                        <th>Acción</th>
                                    </tr>
                                </thead>
                                <tbody id="tablaProductosCompra">
                                    <!-- Se llenará dinámicamente -->
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="4" class="text-right"><strong>Subtotal:</strong></td>
                                        <td id="subtotalCompra">0.00</td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td colspan="4" class="text-right"><strong>IVA:</strong></td>
                                        <td id="ivaCompra">0.00</td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td colspan="4" class="text-right"><strong>Total:</strong></td>
                                        <td id="totalCompra">0.00</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        
                        <div class="row mt-3">
                            <div class="col-12 text-right">
                                <a href="index.php" class="btn btn-secondary">Cancelar</a>
                                <button type="submit" class="btn btn-primary" id="btnGuardar">
                                    <i class="fas fa-save"></i> Guardar Compra
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Modal para seleccionar productos existentes -->
<div class="modal fade" id="modalProductos" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Seleccionar Producto</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-sm table-hover" id="tablaSeleccionProductos">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Nombre</th>
                                <th>Categoría</th>
                                <th>Stock</th>
                                <th>Precio</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Se llenará dinámicamente -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para crear nuevo producto -->
<div class="modal fade" id="modalNuevoProducto" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nuevo Producto</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="formNuevoProducto">
                    <div class="form-group">
                        <label>Código:</label>
                        <input type="text" class="form-control" id="codigo" placeholder="Dejar vacío para autogenerar">
                    </div>
                    <div class="form-group">
                        <label>Nombre: <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nombre" required>
                    </div>
                    <div class="form-group">
                        <label>Categoría: <span class="text-danger">*</span></label>
                        <select class="form-control" id="id_categoria" required>
                            <option value="">Seleccione una categoría</option>
                            <?php foreach ($categorias as $categoria): ?>
                            <option value="<?= $categoria['id_categoria'] ?>">
                                <?= htmlspecialchars($categoria['nombre_categoria']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Precio Compra: <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0.01" class="form-control" id="precio_compra" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Precio Venta: <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0.01" class="form-control" id="precio_venta" required>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Stock Inicial:</label>
                        <input type="number" min="0" class="form-control" id="stock" value="0">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnCrearProducto">Guardar</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Array para almacenar productos de la compra
    let productosCompra = [];
    
    // Función para recalcular totales
    function calcularTotales() {
        let subtotal = 0;
        
        // Sumar subtotales de productos
        productosCompra.forEach(function(producto) {
            subtotal += producto.subtotal;
        });
        
        // Calcular IVA y total
        const porcentajeIva = parseFloat($('#iva_porcentaje').val()) / 100;
        const iva = subtotal * porcentajeIva;
        const total = subtotal + iva;
        
        // Actualizar valores en pantalla
        $('#subtotalCompra').text(subtotal.toFixed(2));
        $('#ivaCompra').text(iva.toFixed(2));
        $('#totalCompra').text(total.toFixed(2));
    }
    
    // Función para agregar producto a la tabla
    function agregarProductoTabla(producto) {
        // Verificar si ya existe en la tabla
        const indice = productosCompra.findIndex(p => p.id_producto === producto.id_producto);
        
        if (indice !== -1) {
            // Ya existe, incrementar cantidad
            productosCompra[indice].cantidad++;
            productosCompra[indice].subtotal = productosCompra[indice].cantidad * productosCompra[indice].precio;
            
            // Actualizar fila existente
            $(`#fila-${producto.id_producto} .cantidad-producto`).val(productosCompra[indice].cantidad);
            $(`#fila-${producto.id_producto} .subtotal-producto`).text(productosCompra[indice].subtotal.toFixed(2));
        } else {
            // Nuevo producto para la compra
            const nuevoProducto = {
                id_producto: producto.id_producto,
                codigo: producto.codigo,
                nombre: producto.nombre,
                precio: parseFloat(producto.precio_compra),
                cantidad: 1,
                subtotal: parseFloat(producto.precio_compra)
            };
            
            // Añadir al array
            productosCompra.push(nuevoProducto);
            
            // Crear fila en la tabla
            const fila = `
                <tr id="fila-${producto.id_producto}">
                    <td>${producto.codigo}</td>
                    <td>${producto.nombre}</td>
                    <td>
                        <input type="number" class="form-control form-control-sm precio-producto" 
                               step="0.01" min="0.01" value="${producto.precio_compra}" 
                               data-id="${producto.id_producto}">
                    </td>
                    <td>
                        <input type="number" class="form-control form-control-sm cantidad-producto" 
                               min="1" value="1" data-id="${producto.id_producto}">
                    </td>
                    <td class="subtotal-producto">${producto.precio_compra}</td>
                    <td>
                        <button type="button" class="btn btn-danger btn-sm btn-eliminar-producto" 
                                data-id="${producto.id_producto}">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
            
            $('#tablaProductosCompra').append(fila);
        }
        
        // Recalcular totales
        calcularTotales();
    }
    
    // Buscar productos
    $('#btnBuscar').click(function() {
        const termino = $('#buscarProducto').val();
        
        if (termino.length < 2) {
            Swal.fire('Atención', 'Ingrese al menos 2 caracteres para buscar', 'warning');
            return;
        }
        
        $.ajax({
            url: '../app/controllers/compras/buscar_productos.php',
            type: 'GET',
            data: { term: termino },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    // Si hay un solo resultado, agregarlo directamente
                    if (response.productos.length === 1) {
                        agregarProductoTabla(response.productos[0]);
                        $('#buscarProducto').val('');
                    }
                    // Si hay múltiples resultados, mostrar modal
                    else if (response.productos.length > 1) {
                        $('#tablaSeleccionProductos tbody').empty();
                        
                        response.productos.forEach(function(producto) {
                            const fila = `
                                <tr>
                                    <td>${producto.codigo}</td>
                                    <td>${producto.nombre}</td>
                                    <td>${producto.nombre_categoria}</td>
                                    <td>${producto.stock}</td>
                                    <td>$${parseFloat(producto.precio_compra).toFixed(2)}</td>
                                    <td>
                                        <button type="button" class="btn btn-primary btn-sm btn-seleccionar-producto"
                                                data-id="${producto.id_producto}" data-codigo="${producto.codigo}"
                                                data-nombre="${producto.nombre}" data-precio="${producto.precio_compra}">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </td>
                                </tr>
                            `;
                            $('#tablaSeleccionProductos tbody').append(fila);
                        });
                        
                        $('#modalProductos').modal('show');
                    } else {
                        Swal.fire('Información', 'No se encontraron productos', 'info');
                    }
                } else {
                    Swal.fire('Error', response.message || 'Error al buscar productos', 'error');
                }
            },
            error: function() {
                Swal.fire('Error', 'Error de comunicación con el servidor', 'error');
            }
        });
    });
    
    // Ver todos los productos
    $('#btnVerTodos').click(function() {
        $.ajax({
            url: '../app/controllers/compras/listar_productos.php',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    $('#tablaSeleccionProductos tbody').empty();
                    
                    response.productos.forEach(function(producto) {
                        const fila = `
                            <tr>
                                <td>${producto.codigo}</td>
                                <td>${producto.nombre}</td>
                                <td>${producto.nombre_categoria}</td>
                                <td>${producto.stock}</td>
                                <td>$${parseFloat(producto.precio_compra).toFixed(2)}</td>
                                <td>
                                    <button type="button" class="btn btn-primary btn-sm btn-seleccionar-producto"
                                            data-id="${producto.id_producto}" data-codigo="${producto.codigo}"
                                            data-nombre="${producto.nombre}" data-precio="${producto.precio_compra}">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </td>
                            </tr>
                        `;
                        $('#tablaSeleccionProductos tbody').append(fila);
                    });
                    
                    $('#modalProductos').modal('show');
                } else {
                    Swal.fire('Error', response.message || 'Error al listar productos', 'error');
                }
            },
            error: function() {
                Swal.fire('Error', 'Error de comunicación con el servidor', 'error');
            }
        });
    });
    
    // Seleccionar producto desde el modal
    $(document).on('click', '.btn-seleccionar-producto', function() {
        const producto = {
            id_producto: $(this).data('id'),
            codigo: $(this).data('codigo'),
            nombre: $(this).data('nombre'),
            precio_compra: $(this).data('precio')
        };
        
        agregarProductoTabla(producto);
        $('#modalProductos').modal('hide');
    });
    
    // Abrir modal para nuevo producto
    $('#btnNuevoProducto').click(function() {
        $('#formNuevoProducto')[0].reset();
        $('#modalNuevoProducto').modal('show');
    });
    
    // Crear nuevo producto
    $('#btnCrearProducto').click(function() {
        // Validar campos obligatorios
        if (!$('#nombre').val() || !$('#id_categoria').val() || 
            !$('#precio_compra').val() || !$('#precio_venta').val()) {
            Swal.fire('Atención', 'Complete los campos obligatorios', 'warning');
            return;
        }
        
        // Validar que precio_compra <= precio_venta
        if (parseFloat($('#precio_compra').val()) > parseFloat($('#precio_venta').val())) {
            Swal.fire('Atención', 'El precio de compra no puede ser mayor al de venta', 'warning');
            return;
        }
        
        // Recolectar datos para enviar
        const formData = {
            codigo: $('#codigo').val(),
            nombre: $('#nombre').val(),
            id_categoria: $('#id_categoria').val(),
            precio_compra: $('#precio_compra').val(),
            precio_venta: $('#precio_venta').val(),
            stock: $('#stock').val() || 0
        };
        
        $.ajax({
            url: '../app/controllers/compras/crear_producto.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    $('#modalNuevoProducto').modal('hide');
                    
                    Swal.fire({
                        icon: 'success',
                        title: '¡Producto creado!',
                        text: response.message,
                        showConfirmButton: false,
                        timer: 1500
                    });
                    
                    // Agregar el nuevo producto a la tabla de compra
                    if (response.producto) {
                        agregarProductoTabla(response.producto);
                    }
                } else {
                    Swal.fire('Error', response.message || 'Error al crear el producto', 'error');
                }
            },
            error: function() {
                Swal.fire('Error', 'Error de comunicación con el servidor', 'error');
            }
        });
    });
    
    // Eliminar producto de la tabla
    $(document).on('click', '.btn-eliminar-producto', function() {
        const idProducto = $(this).data('id');
        
        // Eliminar del array
        productosCompra = productosCompra.filter(p => p.id_producto != idProducto);
        
        // Eliminar fila de la tabla
        $(`#fila-${idProducto}`).remove();
        
        // Recalcular totales
        calcularTotales();
    });
    
    // Actualizar cantidad o precio
    $(document).on('change', '.cantidad-producto, .precio-producto', function() {
        const idProducto = $(this).data('id');
        const fila = $(`#fila-${idProducto}`);
        const indice = productosCompra.findIndex(p => p.id_producto == idProducto);
        
        if (indice !== -1) {
            const precio = parseFloat(fila.find('.precio-producto').val());
            const cantidad = parseInt(fila.find('.cantidad-producto').val());
            
            // Validar valores
            if (isNaN(precio) || precio <= 0 || isNaN(cantidad) || cantidad <= 0) {
                Swal.fire('Atención', 'Precio y cantidad deben ser mayores a cero', 'warning');
                
                // Restaurar valores anteriores
                fila.find('.precio-producto').val(productosCompra[indice].precio);
                fila.find('.cantidad-producto').val(productosCompra[indice].cantidad);
                return;
            }
            
            // Actualizar en el array
            productosCompra[indice].precio = precio;
            productosCompra[indice].cantidad = cantidad;
            productosCompra[indice].subtotal = precio * cantidad;
            
            // Actualizar subtotal en la fila
            fila.find('.subtotal-producto').text(productosCompra[indice].subtotal.toFixed(2));
            
            // Recalcular totales
            calcularTotales();
        }
    });
    
    // Guardar configuración de IVA
    $('#btnGuardarIva').click(function() {
        const porcentaje = parseFloat($('#iva_porcentaje').val());
        
        if (isNaN(porcentaje) || porcentaje < 0 || porcentaje > 100) {
            Swal.fire('Atención', 'El porcentaje de IVA debe estar entre 0 y 100', 'warning');
            return;
        }
        
        const iva = porcentaje / 100;
        
        $.ajax({
            url: '../app/controllers/compras/guardar_config_iva.php',
            type: 'POST',
            data: { iva: iva },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    Swal.fire({
                        position: 'top-end',
                        icon: 'success',
                        title: response.message,
                        showConfirmButton: false,
                        timer: 1500
                    });
                    
                    // Recalcular totales con el nuevo IVA
                    calcularTotales();
                } else {
                    Swal.fire('Error', response.message || 'Error al guardar configuración', 'error');
                }
            },
            error: function() {
                Swal.fire('Error', 'Error de comunicación con el servidor', 'error');
            }
        });
    });
    
    // Guardar compra completa
    $('#formCompra').submit(function(e) {
        e.preventDefault();
        
        // Validar datos mínimos
        if (!$('#id_proveedor').val()) {
            Swal.fire('Atención', 'Debe seleccionar un proveedor', 'warning');
            return;
        }
        
        if (productosCompra.length === 0) {
            Swal.fire('Atención', 'Debe agregar al menos un producto', 'warning');
            return;
        }
        
        // Preparar datos para enviar
        const subtotal = parseFloat($('#subtotalCompra').text());
        const iva = parseFloat($('#ivaCompra').text());
        const total = parseFloat($('#totalCompra').text());
        
        const datos = {
            id_proveedor: $('#id_proveedor').val(),
            fecha_compra: $('#fecha_compra').val(),
            subtotal: subtotal,
            iva: iva,
            total: total,
            productos: productosCompra
        };
        
        $.ajax({
            url: '../app/controllers/compras/guardar_compra.php',
            type: 'POST',
            data: JSON.stringify(datos),
            contentType: 'application/json',
            dataType: 'json',
            beforeSend: function() {
                $('#btnGuardar').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');
            },
            success: function(response) {
                $('#btnGuardar').prop('disabled', false).html('<i class="fas fa-save"></i> Guardar Compra');
                
                if (response.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Compra registrada!',
                        text: response.message,
                        confirmButtonText: 'Ver compras'
                    }).then(() => {
                        window.location.href = 'index.php';
                    });
                } else {
                    Swal.fire('Error', response.message || 'Error al registrar la compra', 'error');
                }
            },
            error: function() {
                $('#btnGuardar').prop('disabled', false).html('<i class="fas fa-save"></i> Guardar Compra');
                Swal.fire('Error', 'Error de comunicación con el servidor', 'error');
            }
        });
    });
});
</script>

<?php include '../layout/parte2.php'; ?>