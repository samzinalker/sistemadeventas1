<?php
require_once '../app/config.php';
require_once '../app/controllers/categorias/CategoriaController.php';

// Configuración de página
$modulo_abierto = 'categorias';
$pagina_activa = 'categorias';

// Incluir sesión y layout
include_once '../layout/sesion.php';
include_once '../layout/parte1.php';

// Instanciar controlador y obtener categorías
$controller = new CategoriaController($pdo);
$categorias = $controller->index(true); // true para mostrar solo categorías del usuario actual
?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><i class="fas fa-tags"></i> Categorías</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= $URL ?>">Inicio</a></li>
                        <li class="breadcrumb-item active">Categorías</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <!-- Alertas -->
            <div id="alertaContainer"></div>
            
            <!-- Categorías -->
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title">Listado de Categorías</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modalCrearCategoria">
                            <i class="fas fa-plus"></i> Nueva Categoría
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <table id="tablaCategorias" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th width="10%">#</th>
                                <th>Categoría</th>
                                <th width="15%">Productos</th>
                                <th width="20%">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $contador = 1;
                            foreach ($categorias as $categoria): 
                            ?>
                            <tr id="fila_<?= $categoria['id_categoria'] ?>">
                                <td class="text-center"><?= $contador++ ?></td>
                                <td><?= htmlspecialchars($categoria['nombre_categoria']) ?></td>
                                <td class="text-center">
                                    <span class="badge badge-info"><?= $categoria['productos_count'] ?? 0 ?></span>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-warning btn-sm" 
                                                onclick="editarCategoria(<?= $categoria['id_categoria'] ?>, '<?= htmlspecialchars($categoria['nombre_categoria'], ENT_QUOTES) ?>')">
                                            <i class="fas fa-edit"></i> Editar
                                        </button>
                                        <button type="button" class="btn btn-danger btn-sm" 
                                                onclick="confirmarEliminar(<?= $categoria['id_categoria'] ?>, '<?= htmlspecialchars($categoria['nombre_categoria'], ENT_QUOTES) ?>')">
                                            <i class="fas fa-trash"></i> Eliminar
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Modal para Crear Categoría -->
<div class="modal fade" id="modalCrearCategoria">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h4 class="modal-title">Nueva Categoría</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formCrearCategoria">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="nombreCategoria">Nombre de la categoría <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nombreCategoria" name="nombre_categoria" required>
                        <div class="invalid-feedback">Este campo es obligatorio</div>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Editar Categoría -->
<div class="modal fade" id="modalEditarCategoria">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h4 class="modal-title">Editar Categoría</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formEditarCategoria">
                <input type="hidden" id="editIdCategoria" name="id_categoria">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="editNombreCategoria">Nombre de la categoría <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="editNombreCategoria" name="nombre_categoria" required>
                        <div class="invalid-feedback">Este campo es obligatorio</div>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-save"></i> Actualizar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Scripts para la página -->
<script>
$(document).ready(function() {
    // Inicializar DataTable
    $('#tablaCategorias').DataTable({
        "responsive": true,
        "lengthChange": false,
        "autoWidth": false,
        "pageLength": 10,
        "language": {
            "emptyTable": "No hay categorías registradas",
            "info": "Mostrando _START_ a _END_ de _TOTAL_ categorías",
            "infoEmpty": "Mostrando 0 a 0 de 0 categorías",
            "infoFiltered": "(filtrado de _MAX_ categorías)",
            "search": "Buscar:",
            "paginate": {
                "first": "Primero",
                "last": "Último",
                "next": "Siguiente",
                "previous": "Anterior"
            }
        },
        "buttons": ["copy", "csv", "excel", "pdf", "print", "colvis"]
    }).buttons().container().appendTo('#tablaCategorias_wrapper .col-md-6:eq(0)');

    // Crear Categoría
    $('#formCrearCategoria').submit(function(e) {
        e.preventDefault();
        
        const nombreCategoria = $('#nombreCategoria').val().trim();
        if (!nombreCategoria) {
            $('#nombreCategoria').addClass('is-invalid');
            return;
        }
        
        $.ajax({
            url: '../app/ajax/categoria.php',
            type: 'POST',
            data: {
                action: 'store',
                nombre_categoria: nombreCategoria
            },
            dataType: 'json',
            beforeSend: function() {
                Swal.fire({
                    title: 'Procesando...',
                    text: 'Por favor espere',
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    willOpen: () => {
                        Swal.showLoading();
                    }
                });
            },
            success: function(response) {
                Swal.close();
                
                if (response.status) {
                    // Mostrar alerta de éxito
                    mostrarAlerta(response.message, 'success');
                    
                    // Cerrar modal y recargar página
                    $('#modalCrearCategoria').modal('hide');
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    Swal.fire('Error', response.message, 'error');
                }
            },
            error: function() {
                Swal.close();
                Swal.fire('Error', 'Ocurrió un error al procesar la solicitud', 'error');
            }
        });
    });

    // Editar Categoría
    $('#formEditarCategoria').submit(function(e) {
        e.preventDefault();
        
        const idCategoria = $('#editIdCategoria').val();
        const nombreCategoria = $('#editNombreCategoria').val().trim();
        
        if (!nombreCategoria) {
            $('#editNombreCategoria').addClass('is-invalid');
            return;
        }
        
        $.ajax({
            url: '../app/ajax/categoria.php',
            type: 'POST',
            data: {
                action: 'update',
                id_categoria: idCategoria,
                nombre_categoria: nombreCategoria
            },
            dataType: 'json',
            beforeSend: function() {
                Swal.fire({
                    title: 'Actualizando...',
                    text: 'Por favor espere',
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    willOpen: () => {
                        Swal.showLoading();
                    }
                });
            },
            success: function(response) {
                Swal.close();
                
                if (response.status) {
                    // Mostrar alerta de éxito
                    mostrarAlerta(response.message, 'success');
                    
                    // Cerrar modal y recargar página
                    $('#modalEditarCategoria').modal('hide');
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    Swal.fire('Error', response.message, 'error');
                }
            },
            error: function() {
                Swal.close();
                Swal.fire('Error', 'Ocurrió un error al procesar la solicitud', 'error');
            }
        });
    });

    // Limpiar campos al cerrar modales
    $('#modalCrearCategoria').on('hidden.bs.modal', function() {
        $('#nombreCategoria').val('').removeClass('is-invalid');
    });

    $('#modalEditarCategoria').on('hidden.bs.modal', function() {
        $('#editNombreCategoria').val('').removeClass('is-invalid');
    });
});

// Mostrar modal de edición
function editarCategoria(id, nombre) {
    $('#editIdCategoria').val(id);
    $('#editNombreCategoria').val(nombre);
    $('#modalEditarCategoria').modal('show');
}

// Mostrar confirmación para eliminar
function confirmarEliminar(id, nombre) {
    Swal.fire({
        title: '¿Eliminar categoría?',
        html: `¿Estás seguro de eliminar la categoría <strong>${nombre}</strong>?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            eliminarCategoria(id);
        }
    });
}

// Eliminar categoría
function eliminarCategoria(id) {
    $.ajax({
        url: '../app/ajax/categoria.php',
        type: 'POST',
        data: {
            action: 'destroy',
            id_categoria: id
        },
        dataType: 'json',
        beforeSend: function() {
            Swal.fire({
                title: 'Eliminando...',
                text: 'Por favor espere',
                allowOutsideClick: false,
                showConfirmButton: false,
                willOpen: () => {
                    Swal.showLoading();
                }
            });
        },
        success: function(response) {
            Swal.close();
            
            if (response.status) {
                // Mostrar alerta de éxito
                mostrarAlerta(response.message, 'success');
                
                // Eliminar fila de la tabla
                $(`#fila_${id}`).fadeOut(500, function() {
                    $(this).remove();
                });
            } else {
                Swal.fire('Error', response.message, 'error');
            }
        },
        error: function() {
            Swal.close();
            Swal.fire('Error', 'Ocurrió un error al procesar la solicitud', 'error');
        }
    });
}

// Mostrar alerta
function mostrarAlerta(mensaje, tipo) {
    const alertaHTML = `
        <div class="alert alert-${tipo} alert-dismissible fade show" role="alert">
            ${mensaje}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    `;
    
    $('#alertaContainer').html(alertaHTML);
    
    // Auto-ocultar después de 3 segundos
    setTimeout(() => {
        $('.alert').alert('close');
    }, 3000);
}
</script>

<?php include_once '../layout/parte2.php'; ?>