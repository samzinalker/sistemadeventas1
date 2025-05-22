<?php
// 1. Configuración (antes de cualquier sesión o HTML)
require_once __DIR__ . '/../app/config.php'; // Define $URL, $pdo, $fechaHora

// 2. Iniciar Sesión y Cargar Datos de Sesión del Usuario
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../layout/sesion.php'; // Carga datos del usuario en $_SESSION y define $nombres_sesion

// Verificar si después de layout/sesion.php, el usuario sigue logueado.
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ' . rtrim($URL, '/') . '/login.php');
    exit;
}
$id_usuario_actual = (int)$_SESSION['id_usuario'];
// $nombres_sesion está disponible gracias a layout/sesion.php

$pageTitle = "Listado de Compras";
$modulo_abierto = "compras"; 
$pagina_activa = "compras_listado"; 

// 3. Lógica para obtener las compras del usuario actual
$stmt_compras_agrupadas = $pdo->prepare("
    SELECT 
        c.nro_compra, 
        c.fecha_compra, 
        prov.nombre_proveedor,
        prov.empresa as empresa_proveedor,
        usr.nombres as nombre_usuario_registro, /* Quién registró la compra */
        c.comprobante,
        SUM(c.cantidad * CAST(c.precio_compra AS DECIMAL(10,2))) as total_compra_calculado, /* Cast si precio_compra es VARCHAR */
        GROUP_CONCAT(DISTINCT alm.nombre ORDER BY alm.nombre SEPARATOR ', ') as productos_resumen,
        MAX(c.fyh_creacion) as fyh_creacion_compra 
    FROM tb_compras as c
    INNER JOIN tb_proveedores as prov ON c.id_proveedor = prov.id_proveedor
    INNER JOIN tb_usuarios as usr ON c.id_usuario = usr.id_usuario
    INNER JOIN tb_almacen as alm ON c.id_producto = alm.id_producto
    WHERE c.id_usuario = :id_usuario_actual /* MUY IMPORTANTE: Filtrar por el usuario actual */
    GROUP BY c.nro_compra, c.fecha_compra, prov.nombre_proveedor, prov.empresa, usr.nombres, c.comprobante
    ORDER BY c.fecha_compra DESC, MAX(c.fyh_creacion) DESC
");
$stmt_compras_agrupadas->bindParam(':id_usuario_actual', $id_usuario_actual, PDO::PARAM_INT);
$stmt_compras_agrupadas->execute();
$compras_listado = $stmt_compras_agrupadas->fetchAll(PDO::FETCH_ASSOC);

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

            <!-- Mensajes de SweetAlert (si se redirige con alguno) -->
            <?php include __DIR__ . '/../layout/mensajes.php'; ?>

            <?php if (isset($_GET['status']) && $_GET['status'] === 'compra_registrada'): ?>
                <script>
                    // Asegurarse que Swal esté definido antes de usarlo
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Éxito!',
                            text: 'Compra registrada correctamente.',
                            timer: 2500,
                            showConfirmButton: false
                        });
                    } else {
                        // Fallback si Swal no está listo (aunque debería por layout/parte1.php)
                        alert('Compra registrada correctamente.');
                    }
                </script>
            <?php endif; ?>
            
            <div class="row mb-3">
                <div class="col-md-12 text-right">
                    <a href="create.php" class="btn btn-primary">
                        <i class="fas fa-plus-circle"></i> Registrar Nueva Compra
                    </a>
                    <a href="reporte_compras.php" class="btn btn-info" target="_blank"> 
                        <i class="fas fa-file-pdf"></i> Generar Reporte PDF
                    </a>
                </div>
            </div>

            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-list-alt"></i> Mis Compras Registradas</h3>
                </div>
                <!-- /.card-header -->
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="tabla_compras" class="table table-bordered table-striped table-hover table-sm">
                            <thead class="thead-dark">
                                <tr>
                                    <th class="text-center" style="width: 5%;">#</th>
                                    <th style="width: 10%;">Nro. Compra</th>
                                    <th style="width: 10%;">Fecha</th>
                                    <th style="width: 20%;">Proveedor</th>
                                    <th style="width: 15%;">Comprobante</th>
                                    <th>Productos (Resumen)</th>
                                    <th style="width: 10%;" class="text-right">Total</th>
                                    <th style="width: 10%;" class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($compras_listado) > 0): ?>
                                    <?php $contador = 1; ?>
                                    <?php foreach ($compras_listado as $compra): ?>
                                        <tr>
                                            <td class="text-center"><?php echo $contador++; ?></td>
                                            <td><?php echo htmlspecialchars($compra['nro_compra']); ?></td>
                                            <td><?php echo htmlspecialchars(date("d/m/Y", strtotime($compra['fecha_compra']))); ?></td>
                                            <td>
                                                <?php echo htmlspecialchars($compra['nombre_proveedor']); ?>
                                                <?php if($compra['empresa_proveedor']): ?>
                                                    <br><small class="text-muted">(<?php echo htmlspecialchars($compra['empresa_proveedor']); ?>)</small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($compra['comprobante']); ?></td>
                                            <td><small><?php echo htmlspecialchars($compra['productos_resumen']); ?></small></td>
                                            <td class="text-right">
                                                <?php 
                                                $total_compra = (float) $compra['total_compra_calculado'];
                                                echo htmlspecialchars(number_format($total_compra, 2, '.', ',')); 
                                                ?>
                                            </td>
                                            <td class="text-center">
                                                <a href="show.php?nro_compra=<?php echo htmlspecialchars($compra['nro_compra']); ?>&id_usuario=<?php echo $id_usuario_actual; /* Pasar id_usuario para show.php */?>" class="btn btn-info btn-xs" title="Ver Detalles">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center">No tiene compras registradas actualmente.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <!-- /.card-body -->
            </div>
            <!-- /.card -->

        </div><!-- /.container-fluid -->
    </section>
    <!-- /.content -->
</div>
<!-- /.content-wrapper -->

<?php
// --- Fin de la Plantilla AdminLTE ---
require_once __DIR__ . '/../layout/parte2.php'; 
?>

<script>
$(document).ready(function() {
    if ($.fn.DataTable) { // Verificar que DataTables esté cargado
        $('#tabla_compras').DataTable({
            "responsive": true,
            "lengthChange": false,
            "autoWidth": false,
            "buttons": ["copy", "csv", "excel", "pdf", "print", "colvis"],
            "language": {
                "url": "<?php echo $URL;?>/public/templeates/AdminLTE-3.2.0/plugins/datatables-bs4/Spanish.json" 
            },
            "order": [[ 2, "desc" ]] 
        }).buttons().container().appendTo('#tabla_compras_wrapper .col-md-6:eq(0)');
    }
});
</script>

</body>
</html>