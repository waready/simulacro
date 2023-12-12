<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use TCPDF;

class OrdenCompraController extends Controller
{
    public function generarOrdenCompra()
    {
       // Inicializar un nuevo objeto PDF
       $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
       // Establecer la información del documento PDF
       $pdf->SetCreator(PDF_CREATOR);
       $pdf->SetAuthor('Nicola Asuni');
       $pdf->SetTitle('TCPDF Example 048');
       $pdf->SetSubject('TCPDF Tutorial');
       $pdf->SetKeywords('TCPDF, PDF, example, test, guide');

       // Establecer datos predeterminados para el encabezado
       $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 048', PDF_HEADER_STRING);

       // Establecer fuentes para encabezado y pie de página
       $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
       $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

       // Establecer la fuente monoespaciada predeterminada
       $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

       // Establecer márgenes
       $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
       $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
       $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

       // Habilitar saltos de página automáticos
       $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

       // Establecer el factor de escala de la imagen
       $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

       // Establecer algunas cadenas dependientes del idioma (opcional)
       if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
           require_once(dirname(__FILE__).'/lang/eng.php');
           $pdf->setLanguageArray($l);
       }

       // ---------------------------------------------------------

       // Establecer la fuente
       $pdf->SetFont('helvetica', 'B', 20);

       // Agregar una página
       $pdf->AddPage();

       $pdf->Write(0, 'Example of HTML tables', '', 0, 'L', true, 0, false, false, 0);
       $pdf->SetFont('helvetica', '', 8);

       // ---------------------------------------------------------------------

       $tbl = <<<EOD
       <table cellspacing="0" cellpadding="1" border="1">
           <tr>
               <td rowspan="3">COL 1 - ROW 1<br />COLSPAN 3</td>
               <td>COL 2 - ROW 1</td>
               <td>COL 3 - ROW 1</td>
           </tr>
           <!-- Agregar el resto del código de la tabla aquí -->
       </table>
       EOD;

       $pdf->writeHTML($tbl, true, false, false, false, '');

       // Agregar código para las otras tablas

       // ---------------------------------------------------------------------

       // Table with rowspans and THEAD
       $tbl = <<<EOD
       <table border="1" cellpadding="2" cellspacing="2">
       <thead>
       <tr style="background-color:#FFFF00;color:#0000FF;">
       <td width="30" align="center"><b>A</b></td>
       <td width="140" align="center"><b>XXXX</b></td>
       <td width="140" align="center"><b>XXXX</b></td>
       <td width="80" align="center"> <b>XXXX</b></td>
       <td width="80" align="center"><b>XXXX</b></td>
       <td width="45" align="center"><b>XXXX</b></td>
       </tr>
       <tr style="background-color:#FF0000;color:#FFFF00;">
       <td width="30" align="center"><b>B</b></td>
       <td width="140" align="center"><b>XXXX</b></td>
       <td width="140" align="center"><b>XXXX</b></td>
       <td width="80" align="center"> <b>XXXX</b></td>
       <td width="80" align="center"><b>XXXX</b></td>
       <td width="45" align="center"><b>XXXX</b></td>
       </tr>
       </thead>
       <tr>
       <td width="30" align="center">1.</td>
       <td width="140" rowspan="6">XXXX<br />XXXX<br />XXXX<br />XXXX<br />XXXX<br />XXXX<br />XXXX<br />XXXX</td>
       <td width="140">XXXX<br />XXXX</td>
       <td width="80">XXXX<br />XXXX</td>
       <td width="80">XXXX</td>
       <td align="center" width="45">XXXX<br />XXXX</td>
       </tr>
       <!-- Agregar el resto del código de la tabla aquí -->
       </table>
       EOD;

       $pdf->writeHTML($tbl, true, false, false, false, '');

       // Agregar más tablas si es necesario

       // ---------------------------------------------------------------------

       // NON-BREAKING TABLE (nobr="true")
       $tbl = <<<EOD
       <table border="1" cellpadding="2" cellspacing="2" nobr="true">
        <tr>
         <th colspan="3" align="center">NON-BREAKING TABLE</th>
        </tr>
        <tr>
         <td>1-1</td>
         <td>1-2</td>
         <td>1-3</td>
        </tr>
        <!-- Agregar el contenido de la tabla aquí -->
       </table>
       EOD;

       $pdf->writeHTML($tbl, true, false, false, false, '');

       // ---------------------------------------------------------------------

       // NON-BREAKING ROWS (nobr="true")
       $tbl = <<<EOD
       <table border="1" cellpadding="2" cellspacing="2" align="center">
        <tr nobr="true">
         <th colspan="3">NON-BREAKING ROWS</th>
        </tr>
        <tr nobr="true">
         <td>ROW 1<br />COLUMN 1</td>
         <td>ROW 1<br />COLUMN 2</td>
         <td>ROW 1<br />COLUMN 3</td>
        </tr>
        <!-- Agregar el contenido de la tabla aquí -->
       </table>
       EOD;

       $pdf->writeHTML($tbl, true, false, false, false, '');

       // ---------------------------------------------------------------------

       // Cerrar y generar el documento PDF
       $pdf->Output('example_048.pdf', 'I');
    }
}
