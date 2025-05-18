<?php
// Habilitar manejo de errores para depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Función para registrar errores
function logError($message) {
    $logDir = __DIR__ . '/../logs';
    if (!file_exists($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $logFile = $logDir . '/compras_errors.log';
    $date = date('Y-m-d H:i:s');
    error_log("[$date] $message\n", 3, $logFile);
}

try {
    require_once '../app/config.php';
    require_once '../app/controllers/compras/CompraController.php';

    // Configuración de página
    $modulo_abierto = 'compras';
    $pagina_activa = 'compras_create';

    // Incluir sesión y layout
    include_once '../layout/sesion.php';
    include_once '../layout/parte1.php';

    // Instanciar controlador y obtener datos para el formulario con manejo de errores
    try {
        $controller = new CompraController($pdo);
        $data = $controller->create();
    } catch (Exception $e) {
        logError("Error en create.php al obtener datos: " . $e->getMessage());
        
        // Proporcionar datos fallback para que la página no falle
        $data = [
            'productos' => [],
            'proveedores' => [],
            'nro_compra' => 1,
            'fecha_actual' => date('Y-m-d')
        ];
        
        echo '<div class="alert alert-warning">
            <h5><i class="icon fas fa-exclamation-triangle"></i> Advertencia</h5>
            No se pudieron cargar todos los datos. Algunas opciones pueden no estar disponibles.
            Por favor, recargue la página o contacte al administrador.
        </div>';
    }
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
                
                <form id="formCompra" autocomplete="off">
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
                                    <div class="invalid-feedback">Este campo es obligatorio</div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="comprobante">Nro. Comprobante <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="comprobante" name="comprobante" placeholder="Factura/Boleta Nro." required>
                                    <div class="invalid-feedback">Este campo es obligatorio</div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="idProveedor">Proveedor <span class="text-danger">*</span></label>
                                    <select class="form-control" id="idProveedor" name="id_proveedor" required>
                                        <option value="">Seleccione un proveedor</option>
                                        <?php if (empty($data['proveedores'])): ?>
                                            <option value="" disabled>No hay proveedores disponibles</option>
                                        <?php else: ?>
                                            <?php foreach ($data['proveedores'] as $proveedor): ?>
                                            <option value="<?= $proveedor['id_proveedor'] ?>"><?= htmlspecialchars($proveedor['nombre_proveedor']) ?> - <?= htmlspecialchars($proveedor['empresa']) ?></option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                    <div class="invalid-feedback">Debe seleccionar un proveedor</div>
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
                                        <?php if (empty($data['productos'])): ?>
                                            <option value="" disabled>No hay productos disponibles</option>
                                        <?php else: ?>
                                            <?php foreach ($data['productos'] as $producto): ?>
                                            <option value="<?= $producto['id_producto'] ?>">[<?= $producto['codigo'] ?>] <?= htmlspecialchars($producto['nombre']) ?></option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                    <div class="invalid-feedback">Debe seleccionar un producto</div>
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
                                                <div class="invalid-feedback">Ingrese un precio válido</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="cantidad">Cantidad <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control" id="cantidad" name="cantidad" min="1" value="1" required>
                                            <div class="invalid-feedback">La cantidad debe ser al menos 1</div>
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
                    <div class="card-footer">
                        <div class="row">
                            <div class="col-md-6">
                                <a href="<?= $URL ?>/compras" class="btn btn-secondary btn-block">
                                    <i class="fas fa-times"></i> Cancelar
                                </a>
                            </div>
                            <div class="col-md-6">
                                <button type="submit" class="btn btn-primary btn-block" id="btnGuardar">
                                    <i class="fas fa-save"></i> Registrar Compra
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </section>
</div>

<script>
// Función para mostrar alertas
function mostrarAlerta(mensaje, tipo) {
    $("#alertaContainer").html(`
        <div class="alert alert-${tipo} alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            <h5><i class="icon fas fa-${tipo === 'success' ? 'check' : 'exclamation-triangle'}"></i> Atención</h5>
            ${mensaje}
        </div>
    `);
    
    // Auto-ocultar después de 5 segundos para alertas no críticas
    if (tipo !== 'danger') {
        setTimeout(function() {
            $("#alertaContainer .alert").fadeOut();
        }, 5000);
    }
}

// Función para mostrar errores AJAX
function mostrarErrorAjax(mensaje) {
    console.error("Error AJAX:", mensaje);
    
    Swal.fire({
        icon: 'error',
        title: 'Error',
        text: mensaje || 'Ocurrió un error al procesar la solicitud',
        confirmButtonText: 'Entendido'
    });
}

$(document).ready(function() {
    // Manejo global de errores AJAX
    $(document).ajaxError(function(event, jqxhr, settings, thrownError) {
        mostrarErrorAjax("Error en la petición: " + (thrownError || "Error desconocido"));
    });

    // Calcular total al cambiar precio o cantidad con manejo de errores
    function calcularTotal() {
        try {
            var precio = parseFloat($('#precioCompra').val()) || 0;
            var cantidad = parseInt($('#cantidad').val()) || 0;
            var total = precio * cantidad;
            
            $('#total').val(total.toFixed(2));
        } catch (e) {
            console.error("Error al calcular total: ", e);
            $('#total').val("0.00");
        }
    }
    
    $('#precioCompra, #cantidad').on('input', calcularTotal);
    
    // Cuando cambia el producto - con timeout y manejo de errores
    $('#idProducto').change(function() {
        var idProducto = $(this).val();
        if (!idProducto) {
            $('#datosProducto').html('<p>Seleccione un producto para ver sus datos</p>');
            return;
        }
        
        // Indicador visual de carga
        $('#datosProducto').html('<p><i class="fas fa-spinner fa-spin"></i> Cargando datos...</p>');
        
        // Timeout para la petición
        var peticion = $.ajax({
            url: '../app/ajax/compra.php',
            type: 'POST',
            data: {
                action: 'get_product_info',
                id_producto: idProducto
            },
            dataType: 'json',
            timeout: 5000 // 5 segundos de timeout
        });
        
        peticion.done(function(response) {
            if (response.status) {
                try {
                    var producto = response.data;
                    var html = '';
                    
                    html += '<h5>' + producto.nombre + ' [' + producto.codigo + ']</h5>';
                    html += '<p><strong>Descripción:</strong> ' + (producto.descripcion || 'Sin descripción') + '</p>';
                    html += '<p><strong>Stock Actual:</strong> ' + producto.stock + ' unidades</p>';
                    html += '<p><strong>Precio Anterior:</strong> $' + parseFloat(producto.precio_compra).toFixed(2) + '</p>';
                    
                    $('#datosProducto').html(html);
                    $('#precioCompra').val(producto.precio_compra);
                    calcularTotal();
                } catch (e) {
                    console.error("Error al procesar datos del producto:", e);
                    $('#datosProducto').html('<p class="text-danger">Error al procesar los datos del producto</p>');
                }
            } else {
                $('#datosProducto').html('<p class="text-danger">' + (response.message || 'No se pudo obtener información del producto') + '</p>');
            }
        });
        
        peticion.fail(function(jqXHR, textStatus) {
            $('#datosProducto').html('<p class="text-danger">Error al cargar datos: ' + textStatus + '</p>');
        });
    });
    
    // Cuando cambia el proveedor - con timeout y manejo de errores
    $('#idProveedor').change(function() {
        var idProveedor = $(this).val();
        if (!idProveedor) {
            $('#datosProveedor').html('<p>Seleccione un proveedor para ver sus datos</p>');
            return;
        }
        
        // Indicador visual de carga
        $('#datosProveedor').html('<p><i class="fas fa-spinner fa-spin"></i> Cargando datos...</p>');
        
        $.ajax({
            url: '../app/ajax/compra.php',
            type: 'POST',
            data: {
                action: 'get_proveedor_info',
                id_proveedor: idProveedor
            },
            dataType: 'json',
            timeout: 5000, // 5 segundos de timeout
            success: function(response) {
                if (response.status) {
                    try {
                        var proveedor = response.data;
                        var html = '';
                        
                        html += '<h5>' + proveedor.nombre_proveedor + '</h5>';
                        html += '<p><strong>Empresa:</strong> ' + proveedor.empresa + '</p>';
                        html += '<p><strong>Contacto:</strong> ' + proveedor.celular + (proveedor.telefono ? ' / ' + proveedor.telefono : '') + '</p>';
                        html += '<p><strong>Dirección:</strong> ' + proveedor.direccion + '</p>';
                        
                        $('#datosProveedor').html(html);
                    } catch (e) {
                        console.error("Error al procesar datos del proveedor:", e);
                        $('#datosProveedor').html('<p class="text-danger">Error al procesar los datos del proveedor</p>');
                    }
                } else {
                    $('#datosProveedor').html('<p class="text-danger">' + (response.message || 'No se pudo obtener información del proveedor') + '</p>');
                }
            },
            error: function(jqXHR, textStatus) {
                $('#datosProveedor').html('<p class="text-danger">Error al cargar datos: ' + textStatus + '</p>');
            }
        });
    });
    
    // Validación del formulario
    $('#formCompra').submit(function(e) {
        e.preventDefault();
        
        // Restablecer validaciones previas
        $(this).find('.is-invalid').removeClass('is-invalid');
        
        // Validar campos requeridos
        var isValid = true;
        
        if (!$('#fechaCompra').val()) {
            $('#fechaCompra').addClass('is-invalid');
            isValid = false;
        }
        
        if (!$('#comprobante').val()) {
            $('#comprobante').addClass('is-invalid');
            isValid = false;
        }
        
        if (!$('#idProveedor').val()) {
            $('#idProveedor').addClass('is-invalid');
            isValid = false;
        }
        
        if (!$('#idProducto').val()) {
            $('#idProducto').addClass('is-invalid');
            isValid = false;
        }
        
        var precio = parseFloat($('#precioCompra').val());
        if (isNaN(precio) || precio <= 0) {
            $('#precioCompra').addClass('is-invalid');
            isValid = false;
        }
        
        var cantidad = parseInt($('#cantidad').val());
        if (isNaN(cantidad) || cantidad <= 0) {
            $('#cantidad').addClass('is-invalid');
            isValid = false;
        }
        
        if (!isValid) {
            mostrarAlerta('Por favor complete todos los campos requeridos', 'warning');
            return;
        }
        
        // Deshabilitar botón para evitar doble envío
        var btnGuardar = $('#btnGuardar');
        var btnOriginalText = btnGuardar.html();
        btnGuardar.html('<i class="fas fa-spinner fa-spin"></i> Procesando...').prop('disabled', true);
        
        // Preparar datos con FormData para permitir adjuntos en el futuro
        var formData = new FormData(this);
        formData.append('action', 'store');
        
        $.ajax({
            url: '../app/ajax/compra.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            cache: false,
            dataType: 'json',
            timeout: 30000, // Aumentar timeout a 30 segundos
            beforeSend: function() {
                // Mostrar indicador de carga más notable
                Swal.fire({
                    title: 'Procesando compra',
                    text: 'Registrando la compra y actualizando inventario...',
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    willOpen: () => {
                        Swal.showLoading();
                    }
                });
            },
            success: function(response) {
                // Cerrar el diálogo de carga
                Swal.close();
                
                if (response.status) {
                    // Éxito
                    Swal.fire({
                        icon: 'success',
                        title: '¡Compra Registrada!',
                        text: response.message,
                        confirmButtonText: 'Ver Detalles',
                        allowOutsideClick: false
                    }).then(function() {
                        window.location.href = 'show.php?id=' + response.id;
                    });
                } else {
                    // Error - Restaurar botón
                    btnGuardar.html(btnOriginalText).prop('disabled', false);
                    
                    // Mostrar mensaje de error
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message || 'Ocurrió un error al procesar la compra',
                        confirmButtonText: 'Entendido'
                    });
                    
                    // Registrar error en console para debug
                    console.error('Error al registrar compra:', response.message);
                }
            },
            error: function(xhr, status, error) {
                // Restaurar botón
                btnGuardar.html(btnOriginalText).prop('disabled', false);
                
                // Determinar mensaje de error apropiado
                let errorMessage = 'Ocurrió un error al procesar la solicitud';
                
                if (status === 'timeout') {
                    errorMessage = 'La solicitud ha tardado demasiado tiempo. Por favor, inténtelo de nuevo.';
                } else if (xhr.responseText) {
                    try {
                        const errorData = JSON.parse(xhr.responseText);
                        if (errorData.message) {
                            errorMessage = errorData.message;
                        }
                    } catch (e) {
                        console.error('Error al procesar respuesta:', xhr.responseText);
                    }
                }
                
                // Mostrar mensaje al usuario
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: errorMessage,
                    confirmButtonText: 'Entendido'
                });
                
                // Registrar error en console para debug
                console.error('Error AJAX:', {status, error, response: xhr.responseText});
            }
        });
    });
    
    // Evento para validación al cambiar campos
    $('input, select').on('change', function() {
        $(this).removeClass('is-invalid');
    });
});
</script>

<?php 
    include_once '../layout/parte2.php';
} catch (Exception $e) {
    // Registrar el error
    logError("Error crítico en create.php: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    
    // Mostrar error amigable
    echo '<div class="alert alert-danger m-3">
        <h4><i class="icon fas fa-exclamation-triangle"></i> Error:</h4>
        <p>Ha ocurrido un problema al cargar la página. Por favor, inténtelo de nuevo más tarde.</p>
        <p>Si el problema persiste, contacte al administrador del sistema.</p>
    </div>
    <div class="text-center m-3">
        <a href="' . $URL . '/compras" class="btn btn-primary">
            <i class="fas fa-arrow-left"></i> Volver a Compras
        </a>
    </div>';
}
?>