<?php
/*
----------------------------------------
Copyright (c) 2025 - Vai-Natura
----------------------------------------
Low-flow methodological note — PDF generator
Renders the theoretical note (same content as the on-screen popup) to a PDF.
----------------------------------------
*/

require('../../config.php');
require('../../database_tables.php');

require('../../function/date.php');
require('../../function/database.php');
require('../../function/html_output.php');
require('../../function/general.php');

use Mpdf\Mpdf;

header('Content-Type: text/html; charset=utf-8');

require('../../text_content_' . LANGUAGE . '.php');

// Load the localized theoretical content (HTML body) from data/html/.
$html_file = '../../../data/html/lowflow_help_' . LANGUAGE . '.html';
if (!is_file($html_file)) { $html_file = '../../../data/html/lowflow_help_fr.html'; }
$LOWFLOW_HELP_HTML  = file_get_contents($html_file);
$LOWFLOW_HELP_TITLE = TEXT_LOWFLOW_HELP_TITLE;

try {

    $header = "<img src='../../../" . DIR_WS_IMG_PDF . "bando.png' style='100%;'>";
    $footer = "
        <div style='text-align:center;font-size:10px;border-top:1px solid #000;padding-top:5px;'>
            " . TEXT_PDF_FOOTER_PAGE . " {PAGENO} " . TEXT_PDF_FOOTER_OF . " {nbpg}
        </div>";

    // Light styling for the note (headings + lists), independent from pdf_css.
    $note_css = "
        h1.lf-title { font-size:18px; margin:0 0 10px; }
        .lf-sec { margin-bottom:6px; }
        .lf-h { font-size:13px; color:#176B87; margin:12px 0 4px; }
        .lf-body { font-size:11px; line-height:1.45; }
        .lf-body ul { margin:4px 0 4px 0; padding-left:16px; }
        .lf-body li { margin-bottom:3px; }
        .lf-body p { margin:4px 0; }
    ";

    $html = "<style>" . $note_css . "</style>"
          . "<h1 class='lf-title'>" . $LOWFLOW_HELP_TITLE . "</h1>"
          . $LOWFLOW_HELP_HTML;

    $fileName = nettoyerNomFichier($LOWFLOW_HELP_TITLE) . ".pdf";
    $filePath = $_SERVER['DOCUMENT_ROOT'] . "/" . DIR_WS_PDF . $fileName;

    $mpdf = new \Mpdf\Mpdf([
        'margin_left'   => 12,
        'margin_right'  => 12,
        'margin_top'    => 30,
        'margin_bottom' => 18,
        'margin_footer' => 6
    ]);

    $mpdf->SetHTMLHeader($header);
    $mpdf->SetHTMLFooter($footer);
    $mpdf->WriteHTML($html);

    $mpdf->Output($filePath, \Mpdf\Output\Destination::FILE);

    echo json_encode([
        'status'   => 'success',
        'fileName' => $fileName
    ]);

} catch (\Mpdf\MpdfException $e) {
    echo json_encode([
        'status' => 'error',
        'msg'    => $e->getMessage()
    ]);
}
?>
