<?php
include('../app/config.php');
include('../layout/sesion.php');

// Verificar permisos de administrador
Auth::requireAdmin($URL);

// Título para la página y el módulo
$titulo_pagina = 'Listado de usuarios';
$modulo_abierto = 'usuarios';
$pagina_activa = 'usuarios';

// Incluir el controlador antes que parte1.php para tener datos disponibles
include('../app/controllers/usuarios/listado_de_usuarios.php');
include('../layout/parte1.php');
?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-12">
                    <h1 class="m-0"><?php echo $titulo_pagina; ?></h1>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Main content -->
    <div class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card card-outline card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Usuarios registrados</h3>
                            <div class="card-tools">
                                <a href="create.php" class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus"></i> Nuevo usuario
                                </a>
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>

                        <div class="card-body">
                            <table id="tabla-usuarios" class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th width="5%"><center>Nro</center></th>
                                        <th width="25%"><center>Nombres</center></th>
                                        <th width="30%"><center>Email</center></th>
                                        <th width="15%"><center>Rol</center></th>
                                        <th width="25%"><center>Acciones</center></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $contador = 0;
                                    foreach ($usuarios_datos as $usuario) {
                                        $id_usuario = $usuario['id_usuario'];
                                    ?>
                                        <tr>
                                            <td><center><?php echo ++$contador; ?></center></td>
                                            <td><?php echo htmlspecialchars($usuario['nombres']); ?></td>
                                            <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                            <td><center><?php echo htmlspecialchars($usuario['rol']); ?></center></td>
                                            <td>
                                                <center>
                                                    <div class="btn-group">
                                                        <a href="show.php?id=<?php echo $id_usuario; ?>" class="btn btn-info btn-sm">
                                                            <i class="fa fa-eye"></i> Ver
                                                        </a>
                                                        <a href="update.php?id=<?php echo $id_usuario; ?>" class="btn btn-success btn-sm">
                                                            <i class="fa fa-pencil-alt"></i> Editar
                                                        </a>
                                                        <a href="delete.php?id=<?php echo $id_usuario; ?>" class="btn btn-danger btn-sm">
                                                            <i class="fa fa-trash"></i> Borrar
                                                        </a>
                                                    </div>
                                                </center>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('../layout/mensajes.php'); ?>
<?php include('../layout/parte2.php'); ?>

<script>
$(document).ready(function() {
    $('#tabla-usuarios').DataTable({
        "responsive": true,
        "lengthChange": false,
        "autoWidth": false,
        "buttons": ["copy", "csv", "excel", "pdf", "print"],
        "language": {
            "search": "Buscar:",
            "lengthMenu": "Mostrar _MENU_ registros por página",
            "zeroRecords": "No se encontraron registros",
            "info": "Mostrando página _PAGE_ de _PAGES_",
            "infoEmpty": "No hay registros disponibles",
            "infoFiltered": "(filtrado de _MAX_ registros totales)",
            "paginate": {
                "first": "Primero",
                "last": "Último",
                "next": "Siguiente",
                "previous": "Anterior"
            }
        }
    }).buttons().container().appendTo('#tabla-usuarios_wrapper .col-md-6:eq(0)');
});
</script>