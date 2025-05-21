<?php
/**
 * Controlador para generar un reporte PDF de todas las compras
 * Requiere la biblioteca TCPDF
 */
require_once '../../config.php';
require_once '../../../vendor/tecnickcom/tcpdf/tcpdf.php';

// Verificación de sesión
session_start();
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../../../login.php');
    exit;
}

$id_usuario = $_SESSION['id_usuario'];

try {
    // Obtener datos de compras
    $sql = "SELECT c.*, p.nombre_proveedor 
            FROM compras c
            INNER JOIN tb_proveedores p ON c.id_proveedor = p.id_proveedor
            WHERE c.id_usuario = ?
            ORDER BY c.fecha_compra DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_usuario]);
    $compras = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Información de la empresa (podría venir de la BD)
    $empresa_info = [
        'nombre' => 'MI EMPRESA S.A.',
        'ruc' => '20123456789',
        'direccion' => 'Av. Principal 123',
        'telefono' => '(01) 234-5678'
    ];

    // Crear nuevo PDF
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');

    // Configuración del documento
    $pdf->SetCreator('Sistema de Ventas');
    $pdf->SetAuthor($empresa_info['nombre']);
    $pdf->SetTitle('Reporte de Compras');
    $pdf->SetSubject('Listado de todas las compras');
    
    // Eliminar encabezado y pie de página predeterminados
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    
    // Establecer márgenes
    $pdf->SetMargins(15, 15, 15);
    $pdf->AddPage();
    
    // Encabezado con datos de la empresa
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, mb_strtoupper($empresa_info['nombre']), 0, 1, 'C');
    
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 5, 'RUC: ' . $empresa_info['ruc'], 0, 1, 'C');
    $pdf->Cell(0, 5, 'Dirección: ' . $empresa_info['direccion'], 0, 1, 'C');
    $pdf->Cell(0, 5, 'Teléfono: ' . $empresa_info['telefono'], 0, 1, 'C');
    
    $pdf->Ln(10);
    
    // Título del reporte
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'REPORTE DE COMPRAS', 0, 1, 'C');
    $pdf->Cell(0, 5, 'Fecha de emisión: ' . date('d/m/Y'), 0, 1, 'C');
    
    $pdf->Ln(5);
    
    // Encabezados de la tabla
    $pdf->SetFillColor(240, 240, 240);
    $pdf->SetFont('helvetica', 'B', 9);
    
    $pdf->Cell(15, 7, 'ID', 1, 0, 'C', true);
    $pdf->Cell(25, 7, 'FECHA', 1, 0, 'C', true);
    $pdf->Cell(70, 7, 'PROVEEDOR', 1, 0, 'C', true);
    $pdf->Cell(25, 7, 'TOTAL', 1, 0, 'C', true);
    $pdf->Cell(25, 7, 'IVA', 1, 0, 'C', true);
    $pdf->Cell(25, 7, 'ESTADO', 1, 1, 'C', true);
    
    // Datos de la tabla
    $pdf->SetFont('helvetica', '', 9);
    $total_general = 0;
    
    foreach ($compras as $compra) {
        // Determinar estado para mostrar
        $estado = ($compra['estado'] == 1) ? 'ACTIVO' : 'ANULADO';
        
        // Calcular total general (solo de compras activas)
        if ($compra['estado'] == 1) {
            $total_general += $compra['total'];
        }
        
        $pdf->Cell(15, 6, $compra['id'], 1, 0, 'C');
        $pdf->Cell(25, 6, date('d/m/Y', strtotime($compra['fecha_compra'])), 1, 0, 'C');
        $pdf->Cell(70, 6, $compra['nombre_proveedor'], 1, 0, 'L');
        $pdf->Cell(25, 6, '$' . number_format($compra['total'], 2), 1, 0, 'R');
        $pdf->Cell(25, 6, '$' . number_format($compra['iva'], 2), 1, 0, 'R');
        $pdf->Cell(25, 6, $estado, 1, 1, 'C');
    }
    
    // Fila del total general
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->Cell(110, 7, 'TOTAL GENERAL', 1, 0, 'R', true);
    $pdf->Cell(75, 7, '$' . number_format($total_general, 2), 1, 1, 'R', true);
    
    // Pie de página
    $pdf->Ln(10);
    $pdf->SetFont('helvetica', 'I', 8);
    $pdf->Cell(0, 5, 'Reporte generado el ' . date('d/m/Y H:i:s'), 0, 1, 'C');
    
    // Enviar PDF al navegador
    $pdf->Output('reporte_compras_' . date('Ymd') . '.pdf', 'I');
    
} catch (Exception $e) {
    error_log("Error en generar_reporte_pdf.php: " . $e->getMessage());
    echo "Error al generar el reporte PDF: " . $e->getMessage();
}
?>