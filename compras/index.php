<?php
// Incluir la configuración de la base de datos y la URL base
require_once __DIR__ . '/../app/config.php';

// Iniciar sesión si aún no está iniciada (app/config.php no parece iniciarla)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar si el usuario está logueado y tiene id_usuario en la sesión
if (!isset($_SESSION['id_usuario'])) {
    // Redirigir al login o mostrar un mensaje de error
    // Asumiendo que tienes una página de login en la URL base
    header('Location: ' . $URL . '/login.php'); // Ajusta la URL si es diferente
    exit;
}

$id_usuario_actual = $_SESSION['id_usuario'];
$pageTitle = "Listado de Compras";

// Lógica para obtener las compras agrupadas por nro_compra
// Cada "compra" es un conjunto de filas en tb_compras con el mismo nro_compra
$stmt_compras_agrupadas = $pdo->prepare("
    SELECT 
        c.nro_compra, 
        c.fecha_compra, 
        p.nombre_proveedor, 
        u.nombres as nombre_usuario,
        c.comprobante,
        SUM(c.cantidad * c.precio_compra) as total_compra,
        GROUP_CONCAT(DISTINCT alm.nombre SEPARATOR ', ') as productos_resumen,
        -- Podríamos añadir un estado si existiera un campo para ello por cada nro_compra
        MAX(c.fyh_creacion) as fyh_creacion_compra 
        -- MAX(c.estado) si tuvieras un estado por nro_compra o por item que se pueda generalizar
    FROM tb_compras as c
    JOIN tb_proveedores as p ON c.id_proveedor = p.id_proveedor
    JOIN tb_usuarios as u ON c.id_usuario = u.id_usuario
    JOIN tb_almacen as alm ON c.id_producto = alm.id_producto
    WHERE c.id_usuario = :id_usuario -- Filtrar por el usuario actual
    GROUP BY c.nro_compra, c.fecha_compra, p.nombre_proveedor, u.nombres, c.comprobante
    ORDER BY c.fecha_compra DESC, MAX(c.fyh_creacion) DESC
");
$stmt_compras_agrupadas->bindParam(':id_usuario', $id_usuario_actual, PDO::PARAM_INT);
$stmt_compras_agrupadas->execute();
$compras_listado = $stmt_compras_agrupadas->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - Sistema de Ventas</title>
    <!-- Incluir Bootstrap CSS (asumiendo que lo usas y está en una ruta accesible) -->
    <link rel="stylesheet" href="<?php echo $URL; ?>/public/css/bootstrap.min.css">
    <style>
        body { padding-top: 20px; padding-bottom: 20px; }
        .container { max-width: 1100px; }
        .table th, .table td { vertical-align: middle; }
    </style>
</head>
<body>
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
            <div>
                <a href="create.php" class="btn btn-primary">Registrar Nueva Compra</a>
                <!-- El action para el PDF lo manejaremos más adelante, podría ser un script separado o un query param aquí -->
                <a href="?action=generar_reporte_pdf" class="btn btn-info">Generar Reporte PDF</a>
            </div>
        </div>

        <?php
        // Manejo simple de la acción de generar reporte PDF (simulación por ahora)
        if (isset($_GET['action']) && $_GET['action'] === 'generar_reporte_pdf'):
            // Aquí llamarías a tu lógica de generación de PDF.
            // Por ahora, solo un mensaje.
        ?>
            <div class="alert alert-info">
                <strong>Simulación de Reporte PDF:</strong> La funcionalidad de generación de PDF se implementará aquí.
                Se tomarían los datos de las compras (filtradas por usuario si es necesario) y se generarían con TCPDF.
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['status']) && $_GET['status'] === 'compra_registrada'): ?>
            <div class="alert alert-success">
                Compra registrada exitosamente.
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['status']) && $_GET['status'] === 'error_guardado'): ?>
            <div class="alert alert-danger">
                Hubo un error al registrar la compra. <?php echo isset($_GET['message']) ? htmlspecialchars($_GET['message']) : ''; ?>
            </div>
        <?php endif; ?>


        <div class="card">
            <div class="card-body">
                <table class="table table-hover table-bordered table-sm">
                    <thead class="thead-dark">
                        <tr>
                            <th>Nro. Compra</th>
                            <th>Fecha Compra</th>
                            <th>Proveedor</th>
                            <th>Comprobante</th>
                            <th>Productos (Resumen)</th>
                            <th>Total</th>
                            <th>Registrado por</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($compras_listado) > 0): ?>
                            <?php foreach ($compras_listado as $compra): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($compra['nro_compra']); ?></td>
                                    <td><?php echo htmlspecialchars(date("d/m/Y", strtotime($compra['fecha_compra']))); ?></td>
                                    <td><?php echo htmlspecialchars($compra['nombre_proveedor']); ?></td>
                                    <td><?php echo htmlspecialchars($compra['comprobante']); ?></td>
                                    <td><?php echo htmlspecialchars($compra['productos_resumen']); ?></td>
                                    <td><?php echo htmlspecialchars(number_format($compra['total_compra'], 2)); ?></td>
                                    <td><?php echo htmlspecialchars($compra['nombre_usuario']); ?></td>
                                    <td>
                                        <a href="show.php?nro_compra=<?php echo htmlspecialchars($compra['nro_compra']); ?>" class="btn btn-info btn-sm">Ver Detalles</a>
                                        <!-- Podrías añadir más acciones como "Anular" si tienes esa lógica -->
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center">No hay compras registradas para este usuario.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Incluir Bootstrap JS y dependencias (jQuery, Popper.js) -->
    <script src="<?php echo $URL; ?>/public/js/jquery-3.5.1.min.js"></script>
    <script src="<?php echo $URL; ?>/public/js/popper.min.js"></script>
    <script src="<?php echo $URL; ?>/public/js/bootstrap.min.js"></script>
</body>
</html>