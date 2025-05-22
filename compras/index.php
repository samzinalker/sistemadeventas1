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
$modulo_abierto = "compras"; // Para el menú activo en AdminLTE (según tu layout/parte1.php)
$pagina_activa = "compras_listado"; // Un nombre único para esta página específica si necesitas diferenciarla en el sidebar

// 3. Lógica para obtener las compras del usuario actual
// Cada "compra" es un conjunto de filas en tb_compras con el mismo nro_compra.
// Se agrupan para mostrar un resumen por cada transacción de compra.
$stmt_compras_agrupadas = $pdo->prepare("
    SELECT 
        c.nro_compra, 
        c.fecha_compra, 
        prov.nombre_proveedor,
        prov.empresa as empresa_proveedor,
        usr.nombres as nombre_usuario_registro, -- Quién registró la compra (será el usuario actual en "mis compras")
        c.comprobante,
        SUM(c.cantidad * c.precio_compra) as total_compra_calculado, -- El precio_compra en tb_compras es VARCHAR, necesita CAST
        GROUP_CONCAT(DISTINCT alm.nombre ORDER BY alm.nombre SEPARATOR ', ') as productos_resumen,
        MAX(c.fyh_creacion) as fyh_creacion_compra 
    FROM tb_compras as c
    INNER JOIN tb_proveedores as prov ON c.id_proveedor = prov.id_proveedor
    INNER JOIN tb_usuarios as usr ON c.id_usuario = usr.id_usuario
    INNER JOIN tb_almacen as alm ON c.id_producto = alm.id_producto
    WHERE c.id_usuario = :id_usuario_actual -- MUY IMPORTANTE: Filtrar por el usuario actual
    GROUP BY c.nro_compra, c.fecha_compra, prov.nombre_proveedor, prov.empresa, usr.nombres, c.comprobante
    ORDER BY c.fecha_compra DESC, MAX(c.fyh_creacion) DESC
");
$stmt_compras_agrupadas->bindParam(':id_usuario_actual', $id_usuario_actual, PDO::PARAM_INT);
$stmt_compras_agrupadas->execute();
$compras_listado = $stmt_compras_agrupadas->fetchAll(PDO::FETCH_ASSOC);

// --- Inicio de la Plantilla AdminLTE ---
require_once __DIR__ . '/../layout/parte1.php'; // Contiene <html>, <head>, <nav>, <aside>
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
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: 'Compra registrada correctamente.',
                        timer: 2500,
                        showConfirmButton: false
                    });
                </script>
            <?php endif; ?>
            
            <div class="row mb-3">
                <div class="col-md-12 text-right">
                    <a href="create.php" class="btn btn-primary">
                        <i class="fas fa-plus-circle"></i> Registrar Nueva Compra
                    </a>
                    <a href="reporte_compras.php" class="btn btn-info" target="_blank"> <!-- Asumiendo un script para reportes -->
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
                                    <!-- <th style="width: 10%;">Registrado por</th> -->
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
                                                // Asegurarse de que el total se formatea correctamente
                                                $total_compra = (float) $compra['total_compra_calculado'];
                                                echo htmlspecialchars(number_format($total_compra, 2, '.', ',')); 
                                                ?>
                                            </td>
                                            <!-- La columna "Registrado por" es redundante aquí ya que son las compras del usuario actual -->
                                            <!-- <td><?php echo htmlspecialchars($compra['nombre_usuario_registro']); ?></td> -->
                                            <td class="text-center">
                                                <a href="show.php?nro_compra=<?php echo htmlspecialchars($compra['nro_compra']); ?>" class="btn btn-info btn-xs" title="Ver Detalles">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <!-- Podrías añadir más acciones como "Anular" o "Imprimir Comprobante Específico" -->
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
require_once __DIR__ . '/../layout/parte2.php'; // Contiene el footer y los scripts JS base de AdminLTE
?>

<!-- Script específico para DataTables (Opcional, pero recomendado para tablas con muchos datos) -->
<!-- Los JS de DataTables ya están incluidos en tu layout/parte1.php y layout/parte2.php -->
<script>
$(document).ready(function() {
    $('#tabla_compras').DataTable({
        "responsive": true,
        "lengthChange": false,
        "autoWidth": false,
        "buttons": ["copy", "csv", "excel", "pdf", "print", "colvis"],
        "language": {
            "url": "<?php echo $URL;?>/public/templeates/AdminLTE-3.2.0/plugins/datatables-bs4/Spanish.json" // Ajusta la ruta si es necesario
        },
        "order": [[ 2, "desc" ]] // Ordenar por fecha descendente por defecto
    }).buttons().container().appendTo('#tabla_compras_wrapper .col-md-6:eq(0)');
});
</script>

</body>
</html>