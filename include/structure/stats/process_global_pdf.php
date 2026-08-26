<?php
/*
----------------------------------------
Copyright (c) 2025 - Vai-Natura
----------------------------------------
General-view PDF generator.
Outputs General data + (when computed) the "Return periods of extreme maximum
events" block (return-period table + 95% CI + Gumbel params + Gumbel chart).
Receives from the client:
  - station/chronicle params (cle_station, type_station, id_typedata, min_x, max_x)
  - images     : array of { id, png } (plotStats, plotGumbel, ...)
  - tablesHtml : cleaned clone of the general view, charts replaced by [[CHART:id]]
----------------------------------------
*/

require('../../config.php');
require('../../database_tables.php');

// Keep mPDF temp-file unlink warnings (Dropbox/Windows lock) out of the JSON.
@ini_set('display_errors', '0');
error_reporting(0);
ob_start();

require('../../function/date.php');
require('../../function/database.php');
require('../../function/html_output.php');
require('../../function/general.php');

use Mpdf\Mpdf;

header('Content-Type: text/html; charset=utf-8');

$sql_link = mysqli_connect(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD, DB_DATABASE)
    or die('Cannot connect to the database');
mysqli_query($sql_link, 'SET NAMES UTF8');

require('../../text_content_' . LANGUAGE . '.php');


// Payload
$data = json_decode(file_get_contents('php://input'), true);

$cle_station = $data['cle_station'];
$id_typedata = $data['id_typedata'];
$min_x       = $data['min_x'];
$max_x       = $data['max_x'];
$images      = isset($data['images'])     ? $data['images']     : array();
$tablesHtml  = isset($data['tablesHtml']) ? $data['tablesHtml'] : '';

$date = DateTime::createFromFormat('d-m-Y', $min_x); $format_min_x = $date->format('Y-m-d');
$date = DateTime::createFromFormat('d-m-Y', $max_x); $format_max_x = $date->format('Y-m-d');


// Station + chronicle + axis
$st = tep_db_fetch_array(tep_db_query($sql_link,
    "SELECT nom_station, code_station FROM " . TABLE_STATION . " WHERE id_station = " . $cle_station));
$nom_station  = html_entity_decode($st['nom_station']  ?? '');
$code_station = html_entity_decode($st['code_station'] ?? '');

$tc = tep_db_fetch_array(tep_db_query($sql_link,
    "SELECT init_type_data, nom_type_data, axe_data FROM " . TABLE_TYPE_DATA . "
     WHERE id_data_type = " . $id_typedata . " ORDER BY init_type_data ASC"));
$init_type_data = $tc['init_type_data'] ?? '';
$nom_type_data  = $tc['nom_type_data']  ?? '';
$axe_data       = $tc['axe_data']       ?? '';

$ax = tep_db_fetch_array(tep_db_query($sql_link,
    "SELECT axe, unite, nb_round FROM " . TABLE_DATA_TYPE_AXE . " WHERE id = " . $axe_data));
$axe       = $ax['axe']   ?? '';
$axe_unite = $ax['unite'] ?? '';

$d1 = new DateTime($format_min_x); $d2 = new DateTime($format_max_x);
$itv = $d1->diff($d2);
$duree = $itv->y . ' ' . TEXT_STATS_DURATION_YEARS;
if ($itv->m > 0) { $duree .= ' ' . TEXT_STATS_DURATION_AND . ' ' . $itv->m . ' ' . TEXT_STATS_DURATION_MONTHS; }


// Decode chart images to temp files.
$tmp_files = array();
$img_by_id = array();
foreach ($images as $im) {
    if (empty($im['id']) || empty($im['png']) || strpos($im['png'], 'data:image') !== 0) { continue; }
    $bin = base64_decode(substr($im['png'], strpos($im['png'], ',') + 1));
    if ($bin === false) { continue; }
    $tmp = $_SERVER['DOCUMENT_ROOT'] . "/" . DIR_WS_PDF . "tmp_gl_" . preg_replace('/[^a-zA-Z0-9_]/', '', $im['id']) . "_" . uniqid() . ".png";
    file_put_contents($tmp, $bin);
    $tmp_files[]          = $tmp;
    $img_by_id[$im['id']] = $tmp;
}


// Body from the cleaned DOM clone.
$body = $tablesHtml;

// Remove the on-screen navMenuGraph control panel if present.
$body = preg_replace(
    "#<div[^>]*class=[\"']navMenuGraph[\"'][^>]*>.*?</div>\s*</div>\s*</div>#is",
    '',
    $body
);

// Replace chart markers by their images.
$body = preg_replace_callback('#\[\[CHART:([A-Za-z0-9_]+)\]\]#', function($m) use ($img_by_id) {
    $id = $m[1];
    if (!empty($img_by_id[$id])) {
        return "<div style='text-align:center;margin:6px 0 12px;'>"
             . "<img src='" . $img_by_id[$id] . "' style='width:100%;max-width:620px;' /></div>";
    }
    return '';
}, $body);

// Push the Gumbel distribution parameters (and the Gumbel chart that follows)
// onto a fresh page: insert a page break before the "Gumbel parameters" heading.
$body = preg_replace(
    "#(<p[^>]*class=[\"']info_stats[\"'][^>]*>\s*" . preg_quote(TEXT_STATS_GUMBEL_PARAMS, '#') . ")#u",
    "<pagebreak />$1",
    $body
);


// PDF GENERATION
try {

    $header = "<img src='../../../" . DIR_WS_IMG_PDF . "bando.png' style='100%;'>";
    $footer = "
        <div style='text-align:center;font-size:10px;border-top:1px solid #000;padding-top:5px;'>
            " . TEXT_PDF_FOOTER_PAGE . " {PAGENO} " . TEXT_PDF_FOOTER_OF . " {nbpg}
        </div>";

    $css = "
        h1.lf-title { font-size:18px; margin:0 0 10px; }
        h2.lf-sectitle { font-size:14px; color:#176B87; margin:14px 0 6px; border-bottom:1px solid #cdddE6; padding-bottom:3px; }
        .lf-fore td { font-size:13px; padding:5px 8px; border:none; vertical-align:top; }
        table#table_tri { border-collapse:collapse; font-size:10px; margin:6px 0 14px; }
        table#table_tri th, table#table_tri td { border:1px solid #ccc; padding:3px 5px; text-align:center; }
        table#table_tri th { background-color:#eef3f8; }
        .info_stats { font-size:13px; color:#176B87; font-weight:bold; margin:12px 0 4px; }
    ";

    $foreword = "
        <h1 class='lf-title'>" . TEXT_STATS_TITLE . " &ndash; " . TEXT_STATS_BTN_GENERAL . "</h1>
        <table class='lf-fore' style='width:100%;border-collapse:collapse;border:none;'>
            <tr>
                <td style='width:50%;'><b>" . TEXT_STATS_STATION . "</b> : " . $code_station . " - " . $nom_station . "</td>
                <td style='width:50%;'><b>" . TEXT_STATS_CHRONIQUE . "</b> : " . $init_type_data . " - " . $nom_type_data . "</td>
            </tr>
            <tr>
                <td><b>" . TEXT_STATS_PERIOD . "</b> : " . $min_x . " &rarr; " . $max_x . "</td>
                <td><b>" . TEXT_STATS_DURATION . "</b> : " . $duree . "</td>
            </tr>
            <tr>
                <td><b>" . TEXT_STATS_DATA . "</b> : " . $axe . " (" . $axe_unite . ")</td>
                <td></td>
            </tr>
        </table>";

    $html  = "<style>" . $css . "</style>";
    $html .= $foreword;
    $html .= "<h2 class='lf-sectitle'>" . TEXT_STATS_BTN_GENERAL . "</h2>";
    $html .= $body;

    $fileName = nettoyerNomFichier($code_station) . "_" . nettoyerNomFichier($nom_station)
              . "_" . nettoyerNomFichier($init_type_data) . "_" . nettoyerNomFichier(TEXT_STATS_BTN_GENERAL) . ".pdf";
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

    if (!empty($tmp_files)) { foreach ($tmp_files as $f) { if (is_file($f)) { @unlink($f); } } }

    if (ob_get_length() !== false) { ob_clean(); }
    echo json_encode([ 'status' => 'success', 'fileName' => $fileName ]);

} catch (\Mpdf\MpdfException $e) {
    if (ob_get_length() !== false) { ob_clean(); }
    echo json_encode([ 'status' => 'error', 'msg' => $e->getMessage() ]);
}
?>