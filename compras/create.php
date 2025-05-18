<?php
require_once '../app/config.php';
require_once '../app/controllers/compras/CompraController.php';

// Configuración de página
$modulo_abierto = 'compras';
$pagina_activa = 'compras_create';

// Incluir sesión y layout
include_once '../layout/sesion.php';
include_once '../layout/parte1.php';

// Instanciar controlador y obtener datos para el formulario
$controller = new CompraController($pdo);
$data = $controller->create();
?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><i class="fas fa-cart-plus"></i> Nueva Compra</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= $URL ?>">Inicio</a></li>
                        <li class="breadcrumb-item"><a href="<?= $URL ?>/compras">Compras</a></li>
                        <li class="breadcrumb-item active">Nueva Compra</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <!-- Alertas -->
            <div id="alertaContainer"></div>
            
            <!-- Formulario de compra -->
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Registro de Nueva Compra</h3>
                </div>
                
                <form id="formCompra">
                    <div class="card-body">
                        <div class="row">
                            <!-- Primera columna -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="nroCompra">Nro. Compra</label>
                                    <input type="text" class="form-control" id="nroCompra" name="nro_compra" value="<?= $data['nro_compra'] ?>" readonly>
                                </div>
                                
                                <div class="form-group">
                                    <label for="fechaCompra">Fecha de Compra <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="fechaCompra" name="fecha_compra" value="<?= $data['fecha_actual'] ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="comprobante">Nro. Comprobante <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="comprobante" name="comprobante" placeholder="Factura/Boleta Nro." required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="idProveedor">Proveedor <span class="text-danger">*</span></label>
                                    <select class="form-control" id="idProveedor" name="id_proveedor" required>
                                        <option value="">Seleccione un proveedor</option>
                                        <?php foreach ($data['proveedores'] as $proveedor): ?>
                                        <option value="<?= $proveedor['id_proveedor'] ?>"><?= htmlspecialchars($proveedor['nombre_proveedor']) ?> - <?= htmlspecialchars($proveedor['empresa']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label>Datos del Proveedor</label>
                                    <div class="callout callout-info" id="datosProveedor">
                                        <p>Seleccione un proveedor para ver sus datos</p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Segunda columna -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="idProducto">Producto <span class="text-danger">*</span></label>
                                    <select class="form-control" id="idProducto" name="id_producto" required>
                                        <option value="">Seleccione un producto</option>
                                        <?php foreach ($data['productos'] as $producto): ?>
                                        <option value="<?= $producto['id_producto'] ?>">[<?= $producto['codigo'] ?>] <?= htmlspecialchars($producto['nombre']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label>Datos del Producto</label>
                                    <div class="callout callout-info" id="datosProducto">
                                        <p>Seleccione un producto para ver sus datos</p>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="precioCompra">Precio Unitario <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">$</span>
                                                </div>
                                                <input type="number" class="form-control" id="precioCompra" name="precio_compra" min="0.01" step="0.01" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="cantidad">Cantidad <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control" id="cantidad" name="cantidad" min="1" value="1" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label>Total a Pagar</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">$</span>
                                        </div>
                                        <input type="text" class="form-control" id="total" readonly>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-center">
                        <a href="<?= $URL ?>/compras" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Registrar Compra
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</div>

<script>
$(document).ready(function() {
    // Cuando cambia el proveedor
    $('#idProveedor').change(function() {
        var idProveedor = $(this).val();
        if (!idProveedor) {
            $('#datosProveedor').html('<p>Seleccione un proveedor para ver sus datos</p>');
            return;
        }
        
        $.ajax({
            url: '../app/ajax/compra.php',
            type: 'POST',
            data: {
                action: 'get_proveedor_info',
                id_proveedor: idProveedor
            },
            dataType: 'json',
            success: function(response) {
                if (response.status) {
                    var proveedor = response.data;
                    var html = '';
                    
                    html += '<h5>' + proveedor.nombre_proveedor + '</h5>';
                    html += '<p><strong>Empresa:</strong> ' + proveedor.empresa + '</p>';
                    html += '<p><strong>Contacto:</strong> ' + proveedor.celular + (proveedor.telefono ? ' / ' + proveedor.telefono : '') + '</p>';
                    html += '<p><strong>Dirección:</strong> ' + proveedor.direccion + '</p>';
                    
                    $('#datosProveedor').html(html);
                }
            }
        });
    });
    
    // Cuando cambia el producto
    $('#idProducto').change(function() {
        var idProducto = $(this).val();
        if (!idProducto) {
            $('#datosProducto').html('<p>Seleccione un producto para ver sus datos</p>');
            return;
        }
        
        $.ajax({
            url: '../app/ajax/compra.php',
            type: 'POST',
            data: {
                action: 'get_product_info',
                id_producto: idProducto
            },
            dataType: 'json',
            success: function(response) {
                if (response.status) {
                    var producto = response.data;
                    var html = '';
                    
                    html += '<h5>' + producto.nombre + ' [' + producto.codigo + ']</h5>';
                    html += '<p><strong>Descripción:</strong> ' + (producto.descripcion || 'Sin descripción') + '</p>';
                    html += '<p><strong>Stock Actual:</strong> ' + producto.stock + ' unidades</p>';
                    html += '<p><strong>Precio Anterior:</strong> $' + parseFloat(producto.precio_compra).toFixed(2) + '</p>';
                    
                    $('#datosProducto').html(html);
                    $('#precioCompra').val(producto.precio_compra);
                    
                    // Calcular total
                    calcularTotal();
                }
            }
        });
    });
    
    // Calcular total al cambiar precio o cantidad
    $('#precioCompra, #cantidad').on('input', function() {
        calcularTotal();
    });
    
    function calcularTotal() {
        var precio = parseFloat($('#precioCompra').val()) || 0;
        var cantidad = parseInt($('#cantidad').val()) || 0;
        var total = precio * cantidad;
        
        $('#total').val(total.toFixed(2));
    }
    
    // Envío del formulario
    $('#formCompra').submit(function(e) {
        e.preventDefault();
        
        $.ajax({
            url: '../app/ajax/compra.php',
            type: 'POST',
            data: $(this).serialize() + '&action=store',
            dataType: 'json',
            beforeSend: function() {
                // Mostrar indicador de carga
                Swal.fire({
                    title: 'Procesando...',
                    text: 'Registrando la compra',
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    willOpen: () => {
                        Swal.showLoading();
                    }
                });
            },
            success: function(response) {
                if (response.status) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Compra Registrada',
                        text: response.message,
                        confirmButtonColor: '#3085d6'
                    }).then((result) => {
                        window.location.href = 'show.php?id=' + response.id;
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message,
                        confirmButtonColor: '#3085d6'
                    });
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Ocurrió un error al procesar la solicitud',
                    confirmButtonColor: '#3085d6'
                });
            }
        });
    });
});
</script>

<?php include_once '../layout/parte2.php'; ?>