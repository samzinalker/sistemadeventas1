<?php
// --- INCLUDES Y CONFIGURACIÓN INICIAL ---
// Resumen: Este bloque incluye los archivos necesarios para la configuración de la aplicación,
// gestión de sesión, el modelo de compras y la librería TCPDF para generar el PDF.
include ('../app/config.php');                     // $URL, $pdo, $fechaHora
include ('../layout/sesion.php');                 // Verifica sesión, establece $id_usuario_sesion, etc.
require_once __DIR__ . '/../app/models/ComprasModel.php';
require_once __DIR__ . '/../public/TCPDF-main/tcpdf.php'; // Ajusta esta ruta si es diferente

// --- CLASE MYPDF_COMPRAS PERSONALIZADA (HEREDA DE TCPDF) ---
// Resumen: Se define una clase que extiende TCPDF para personalizar el encabezado y pie de página del PDF.
class MYPDF_Compras extends TCPDF {
    // Encabezado de página personalizado
    public function Header() {
        global $URL; // Acceder a la variable global $URL para la imagen
        // Logo (ajusta la ruta y tamaño según tu logo)
        $image_file = K_PATH_IMAGES . 'logo.png'; // TCPDF busca en su carpeta de imágenes por defecto
                                                 // o puedes poner una ruta absoluta o relativa desde este script.
                                                 // Por ejemplo: __DIR__ . '/../public/images/logo.png'
        
        // Si usas la ruta relativa desde la carpeta de imágenes de TCPDF, asegúrate que el logo esté ahí.
        // Si prefieres una ruta absoluta/relativa desde tu proyecto:
        $logoPath = __DIR__ . '/../public/images/logo.png'; // Ajusta si tu logo está en otra parte

        if (file_exists($logoPath)) {
            $this->Image($logoPath, 15, 10, 25, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
        } else {
            // Fallback si el logo no se encuentra en la ruta especificada
            $this->SetFont('helvetica', 'B', 10);
            $this->Cell(0, 10, 'Logo no encontrado', 0, false, 'L', 0, '', 0, false, 'M', 'M');
        }
        
        // Configuración de la fuente para el título del reporte
        $this->SetFont('helvetica', 'B', 16);
        // Título del Reporte
        $this->SetY(15); // Ajustar posición Y para que no se solape con el logo
        $this->Cell(0, 15, 'Reporte de Compras Registradas', 0, true, 'C', 0, '', 0, false, 'M', 'M');
        
        // Información adicional (ej. nombre de la empresa, fecha de generación)
        $this->SetFont('helvetica', '', 9);
        $this->SetY(25);
        $this->Cell(0, 10, 'Empresa: Tu Nombre de Empresa/Sistema', 0, true, 'C');
        $this->Cell(0, 5, 'Generado el: ' . date('d/m/Y H:i:s'), 0, true, 'C');
        
        // Línea debajo del encabezado
        $this->Line(15, $this->GetY() + 2, $this->getPageWidth() - 15, $this->GetY() + 2);
        $this->SetY($this->GetY() + 5); // Espacio antes del contenido
    }

    // Pie de página personalizado
    public function Footer() {
        // Posición a 15 mm del final
        $this->SetY(-15);
        // Configuración de la fuente
        $this->SetFont('helvetica', 'I', 8);
        // Número de página
        $this->Cell(0, 10, 'Página '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
    }
}

// --- LÓGICA PRINCIPAL PARA GENERAR EL PDF ---
try {
    // 1. Instanciar el modelo y obtener los datos de las compras
    $comprasModel = new ComprasModel($pdo);
    $compras_datos = $comprasModel->getAllComprasByUsuarioId($id_usuario_sesion); // Usar el ID del usuario de la sesión

    // 2. Crear una nueva instancia del PDF
    $pdf = new MYPDF_Compras(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    // 3. Establecer información del documento
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor($_SESSION['nombres'] ?? 'Usuario del Sistema'); // Nombre del usuario logueado
    $pdf->SetTitle('Reporte de Compras');
    $pdf->SetSubject('Listado de todas las compras registradas');
    $pdf->SetKeywords('TCPDF, PDF, compras, reporte, sistema de ventas');

    // 4. Establecer márgenes
    $pdf->SetMargins(PDF_MARGIN_LEFT, 45, PDF_MARGIN_RIGHT); // Aumentar margen superior para el header
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

    // 5. Establecer saltos de página automáticos
    $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

    // 6. Establecer fuente para el contenido
    $pdf->SetFont('helvetica', '', 8); // Tamaño de fuente más pequeño para la tabla

    // 7. Añadir una página
    $pdf->AddPage('L', 'A4'); // 'L' para Landscape (apaisado), 'A4' es el formato

    // 8. Crear el HTML para la tabla de compras
    // Resumen: Se construye una cadena HTML que representa la tabla con los datos de las compras.
    // Se itera sobre los datos obtenidos del modelo y se crea una fila <tr> por cada compra.
    $html = '<table border="1" cellpadding="4" cellspacing="0">';
    $html .= '<tr style="background-color:#f2f2f2; font-weight:bold;">
                <th width="5%">ID</th>
                <th width="12%">Nro. Comprob.</th>
                <th width="15%">Proveedor</th>
                <th width="10%">Fecha Compra</th>
                <th width="10%">Usuario Reg.</th>
                <th width="8%" align="right">Subtotal</th>
                <th width="7%" align="center">IVA (%)</th>
                <th width="8%" align="right">Monto IVA</th>
                <th width="10%" align="right">Total</th>
                <th width="15%">Estado</th>
              </tr>';

    if (count($compras_datos) > 0) {
        foreach ($compras_datos as $compra) {
            $html .= '<tr>';
            $html .= '<td width="5%" align="center">' . sanear($compra['id_compra']) . '</td>';
            $html .= '<td width="12%">' . sanear($compra['nro_comprobante_proveedor'] ?: 'N/A') . '</td>';
            $html .= '<td width="15%">' . sanear($compra['nombre_proveedor']) . '</td>';
            $html .= '<td width="10%" align="center">' . date('d/m/Y', strtotime($compra['fecha_compra'])) . '</td>';
            $html .= '<td width="10%">' . sanear($compra['nombre_usuario_registra']) . '</td>';
            $html .= '<td width="8%" align="right">' . number_format($compra['subtotal_neto'], 2) . '</td>';
            $html .= '<td width="7%" align="center">' . ($compra['aplica_iva'] ? number_format($compra['porcentaje_iva'], 2) . '%' : 'No Aplica') . '</td>';
            $html .= '<td width="8%" align="right">' . number_format($compra['monto_iva'], 2) . '</td>';
            $html .= '<td width="10%" align="right">' . number_format($compra['monto_total'], 2) . '</td>';
            $html .= '<td width="15%" align="center">' . sanear($compra['estado']) . '</td>';
            $html .= '</tr>';
        }
    } else {
        $html .= '<tr><td colspan="10" align="center">No hay compras registradas para mostrar.</td></tr>';
    }
    $html .= '</table>';

    // 9. Escribir el HTML en el PDF
    $pdf->writeHTML($html, true, false, true, false, '');

    // 10. Cerrar y generar el documento PDF
    // El nombre del archivo PDF que se descargará/mostrará.
    $nombre_archivo = 'Reporte_Compras_' . date('Ymd_His') . '.pdf';
    $pdf->Output($nombre_archivo, 'I'); // 'I' para mostrar en navegador, 'D' para descargar

} catch (PDOException $e) {
    error_log("Error de PDO en reporte_compras_pdf.php: " . $e->getMessage());
    echo "Error de base de datos al generar el reporte. Por favor, contacte al administrador.";
} catch (Exception $e) {
    error_log("Error general en reporte_compras_pdf.php: " . $e->getMessage());
    echo "Error inesperado al generar el reporte: " . $e->getMessage();
}
?>