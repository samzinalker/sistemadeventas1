<?php
// 1. INICIAR SESIÓN (si no está activa)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. CONFIGURACIÓN PRINCIPAL (define $pdo, $URL, $fechaHora)
require_once __DIR__ . '/../app/config.php'; 

// 3. MANEJO DE SESIÓN DEL USUARIO (valida sesión, carga datos del usuario)
require_once __DIR__ . '/../layout/sesion.php'; 

// 4. MANEJO DE PERMISOS (valida si el usuario tiene acceso a esta página/módulo)
require_once __DIR__ . '/../layout/permisos.php'; 
// DEBUG START

// DEBUG END
// ---------------------------------------------------------------------------
// Lógica específica de la página (variables de título, carga de datos, etc.)
// ---------------------------------------------------------------------------
$titulo_pagina = 'Listado de Roles';
$modulo_abierto = 'roles';
$pagina_activa = 'roles_listado';

// Cargar los datos de los roles usando el controlador de listado
require_once __DIR__ . '/../app/controllers/roles/listado_de_roles.php'; 
// ---------------------------------------------------------------------------

// 5. LAYOUT PARTE 1 (HTML head, navbar, sidebar, SweetAlert JS incluido)
require_once __DIR__ . '/../layout/parte1.php'; 
?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0"><?php echo htmlspecialchars($titulo_pagina); ?></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?php echo $URL; ?>/">Inicio</a></li>
                        <li class="breadcrumb-item active">Roles</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <!-- /.content-header -->

    <!-- Main content -->
    <div class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-8"> {/* Ajusta el ancho si es necesario */}
                    <div class="card card-outline card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Roles Registrados</h3>
                            <div class="card-tools">
                                <a href="create.php" class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus"></i> Nuevo Rol
                                </a>
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <!-- /.card-header -->
                        <div class="card-body">
                            <table id="tabla-roles" class="table table-bordered table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th style="width: 10%;"><center>Nro</center></th>
                                        <th style="width: 40%;">Nombre del Rol</th>
                                        <th style="width: 25%;"><center>Fecha Creación</center></th>
                                        <th style="width: 25%;"><center>Acciones</center></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $contador = 0;
                                    if (!empty($roles_datos)) {
                                        foreach ($roles_datos as $rol_dato) {
                                            $id_rol = $rol_dato['id_rol'];
                                    ?>
                                        <tr>
                                            <td><center><?php echo ++$contador; ?></center></td>
                                            <td><?php echo htmlspecialchars($rol_dato['rol']); ?></td>
                                            <td><center><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($rol_dato['fyh_creacion']))); ?></center></td>
                                            <td>
                                                <center>
                                                    <div class="btn-group">
                                                        <a href="update.php?id=<?php echo $id_rol; ?>" class="btn btn-success btn-sm" title="Editar Rol">
                                                            <i class="fa fa-pencil-alt"></i> Editar
                                                        </a>
                                                        <?php
                                                        // No permitir borrar roles "administrador" o roles críticos por defecto.
                                                        // Podrías tener una lista de roles protegidos.
                                                        // Aquí, como ejemplo, impedimos borrar el rol con ID 1 si asumimos que es 'administrador'.
                                                        // Y también el rol actual del usuario logueado (aunque ya se valida en el controlador, doble seguro).
                                                        $esRolAdminPrincipal = ($id_rol == 1); // Asumiendo que ID 1 es el admin principal.
                                                        $esRolDelUsuarioLogueado = false;
                                                        if (isset($_SESSION['id_rol_sesion']) && $_SESSION['id_rol_sesion'] == $id_rol) {
                                                            // Necesitaríamos guardar el id_rol en sesión en layout/sesion.php
                                                            // $esRolDelUsuarioLogueado = true; 
                                                            // Por ahora, esta comprobación es más robusta en el controlador de borrado.
                                                        }

                                                        if (!$esRolAdminPrincipal /* && !$esRolDelUsuarioLogueado */ ): ?>
                                                            <form action="<?php echo htmlspecialchars($URL . '/app/controllers/roles/delete_controller.php'); ?>" method="POST" style="display:inline;" onsubmit="return confirm('¿Está seguro de que desea eliminar este rol? Si hay usuarios asignados, no se podrá eliminar.');">
                                                                <input type="hidden" name="id_rol_a_eliminar" value="<?php echo htmlspecialchars($id_rol); ?>">
                                                                <button type="submit" class="btn btn-danger btn-sm" title="Eliminar Rol">
                                                                    <i class="fa fa-trash"></i> Borrar
                                                                </button>
                                                            </form>
                                                        <?php else: ?>
                                                            <button type="button" class="btn btn-danger btn-sm disabled" title="Este rol no se puede eliminar">
                                                                <i class="fa fa-trash"></i> Borrar
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </center>
                                            </td>
                                        </tr>
                                    <?php 
                                        } // Fin foreach
                                    } else { // Si no hay roles
                                    ?>
                                        <tr>
                                            <td colspan="4"><center>No hay roles registrados.</center></td>
                                        </tr>
                                    <?php
                                    } // Fin if-else empty
                                    ?>
                                </tbody>
                            </table>
                        </div>
                        <!-- /.card-body -->
                    </div>
                    <!-- /.card -->
                </div>
            </div>
            <!-- /.row -->
        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content -->
</div>
<!-- /.content-wrapper -->

<?php 
// 6. LAYOUT MENSAJES (Lee y muestra $_SESSION['mensaje'] con SweetAlert)
require_once __DIR__ . '/../layout/mensajes.php'; 

// 7. LAYOUT PARTE 2 (footer, cierre de HTML, otros JS)
require_once __DIR__ . '/../layout/parte2.php'; 
?>
<!-- Scripts para DataTables (opcional, pero recomendado para tablas) -->
<script>
$(function () {
    $("#tabla-roles").DataTable({
        "responsive": true, "lengthChange": false, "autoWidth": false,
        // "buttons": ["copy", "csv", "excel", "pdf", "print", "colvis"], // Puedes habilitar botones si los necesitas
        "language": { 
            "url": "<?php echo $URL; ?>/public/plugins/datatables/i18n/Spanish.json" // URL LOCAL
        },
        "order": [[1, "asc"]] // Ordenar por nombre de rol por defecto
    });
    // Si habilitas botones:
    // .buttons().container().appendTo('#tabla-roles_wrapper .col-md-6:eq(0)');
});
</script>