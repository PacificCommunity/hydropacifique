<?php
/*
----------------------------------------
Copyright (c) 2025 - Vai-Natura
----------------------------------------
Low-flow results PDF generator (report style).
Receives from the client:
  - station/chronicle params (cle_station, type_station, id_typedata, min_x, max_x)
  - mode       : 'summary' (synthese) | 'full' (developpe)
  - images     : array of { id, png(base64 dataURL) }  (plotCDC + plot_<metric>)
  - tablesHtml : cleaned clone of #contenu_stats, where each chart div has been
                 replaced by a [[CHART:id]] marker (no Plotly furniture / svg).
----------------------------------------
*/

require('../../config.php');
require('../../database_tables.php');

// mPDF on a Dropbox/Windows path emits unlink() warnings when removing its
// internal temp PNG masks (file briefly locked by Dropbox sync). These are
// harmless but, if printed, they corrupt our JSON response. Silence display
// and capture output so only the JSON is ever returned.
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
$mode        = (isset($data['mode']) && $data['mode'] === 'full') ? 'full' : 'summary';
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
$nb_dec    = isset($ax['nb_round']) ? (int)$ax['nb_round'] : 0;

$ov = tep_db_fetch_array(tep_db_query($sql_link,
    "SELECT COUNT(ABS(da.valeur)) AS n, AVG(ABS(da.valeur)) AS vmoy
     FROM " . TABLE_DATA_ALL  . " da
     JOIN " . TABLE_DATA_META . " dm ON da.id_meta = dm.id
     WHERE dm.id_typedata = " . $id_typedata . "
     AND   dm.id_station  = " . $cle_station . "
     AND   da.dateheure  >= '" . $format_min_x . "'
     AND   da.dateheure  <= '" . $format_max_x . "'
     AND   da.valeur NOT IN (9999, -9999, 8888, -8888, 99999, -99999, 88888, -88888)"));
$g_n    = (int)($ov['n'] ?? 0);
$module = round((float)($ov['vmoy'] ?? 0), $nb_dec);

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
    $tmp = $_SERVER['DOCUMENT_ROOT'] . "/" . DIR_WS_PDF . "tmp_lf_" . preg_replace('/[^a-zA-Z0-9_]/', '', $im['id']) . "_" . uniqid() . ".png";
    file_put_contents($tmp, $bin);
    $tmp_files[]          = $tmp;
    $img_by_id[$im['id']] = $tmp;
}


// Body HTML from the cleaned DOM clone.
$body = $tablesHtml;

// Common cleanup (both modes): remove the on-screen \"navMenuGraph\" CDC control
// panel (a duplicated legend next to the CDC chart). On screen it is positioned
// over the chart; in the PDF it renders as overlapping black labels above the
// CDC graph, so we drop it entirely.
$body = preg_replace(
    "#<div[^>]*class=[\"']navMenuGraph[\"'][^>]*>.*?</div>\s*</div>\s*</div>#is",
    '',
    $body
);

// In summary mode, drop the whole "metric calculation details" annex: its
// heading and every per-metric panel. The panels are floated columns whose
// exact inline style is rewritten by the browser clone, so instead of matching
// the style we cut from the annex heading to the annual-minima annex heading
// (everything in between is the per-metric detail).
if ($mode === 'summary') {
    $start = TEXT_LOWFLOW_ANNEX_METRICS;
    $end   = TEXT_LOWFLOW_ANNEX_ANNUAL_MINIMA;
    // Remove from the metrics-annex heading up to (but not including) the
    // annual-minima heading.
    $body = preg_replace(
        "#<p[^>]*class=[\"']info_stats[\"'][^>]*>\s*" . preg_quote($start, '#') . ".*?(?=<p[^>]*class=[\"']info_stats[\"'][^>]*>\s*" . preg_quote($end, '#') . ")#su",
        '',
        $body
    );
    // Safety net: blank any metric chart markers that might remain.
    $body = preg_replace('#\[\[CHART:plot_[A-Za-z0-9]+\]\]#', '', $body);
}
else {
    // Full mode: per-metric panels render as rows of 3 columns (QMNA, DCE, VCN3
    // then VCN7, VCN10, VCN30). An A4 page can't hold two rows of three, so we
    // start a new page before the second row — i.e. before the VCN7 panel.
    // Anchor on the panel <div> whose first heading is "VCN 7 jours" (browser
    // clone rewrites inline styles, so we stay tolerant on whitespace/attrs).
    $body = preg_replace(
        "#(<div\b[^>]*>\s*<p\b[^>]*>\s*VCN 7\b)#u",
        "<div style='clear:both;'></div><pagebreak />$1",
        $body,
        1
    );
}

// Force the two annex sections to start on a fresh page (page-break before
// their heading). They are introduced by <p class='info_stats'> headings whose
// text is the annex title.
$body = preg_replace(
    "#(<p[^>]*class=[\"']info_stats[\"'][^>]*>\s*" . preg_quote(TEXT_LOWFLOW_ANNEX_METRICS, '#') . ")#u",
    "<pagebreak />$1",
    $body
);
$body = preg_replace(
    "#(<p[^>]*class=[\"']info_stats[\"'][^>]*>\s*" . preg_quote(TEXT_LOWFLOW_ANNEX_ANNUAL_MINIMA, '#') . ")#u",
    "<pagebreak />$1",
    $body
);

// Replace remaining [[CHART:id]] markers by their image.
$body = preg_replace_callback('#\[\[CHART:([A-Za-z0-9_]+)\]\]#', function($m) use ($img_by_id) {
    $id = $m[1];
    if (!empty($img_by_id[$id])) {
        $maxw = ($id === 'plotCDC') ? 620 : 300;
        return "<div style='text-align:center;margin:4px 0 8px;'>"
             . "<img src='" . $img_by_id[$id] . "' style='width:100%;max-width:" . $maxw . "px;' /></div>";
    }
    return '';
}, $body);


// Methodological note (appendix)
$help_file = '../../../data/html/lowflow_help_' . LANGUAGE . '.html';
if (!is_file($help_file)) { $help_file = '../../../data/html/lowflow_help_fr.html'; }
$note_html = is_file($help_file) ? file_get_contents($help_file) : '';


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
        table#table_tri, table[id^='table_tri_'], table[id^='table_res_'] {
            border-collapse:collapse; font-size:10px; margin:6px auto 14px;
        }
        table#table_tri th, table#table_tri td,
        table[id^='table_tri_'] th, table[id^='table_tri_'] td,
        table[id^='table_res_'] th, table[id^='table_res_'] td {
            border:1px solid #ccc; padding:3px 5px; text-align:center;
        }
        table#table_tri th { background-color:#eef3f8; }
        .info_stats { font-size:13px; color:#176B87; font-weight:bold; margin:12px 0 4px; }
        .lf-sec { margin-bottom:6px; }
        .lf-h { font-size:13px; color:#176B87; margin:10px 0 4px; }
        .lf-body { font-size:11px; line-height:1.45; }
        .lf-body ul { margin:4px 0; padding-left:16px; }
        .lf-body li { margin-bottom:3px; }
    ";

    $foreword = "
        <h1 class='lf-title'>" . TEXT_STATS_TITLE . " &ndash; " . TEXT_STATS_BTN_LOWFLOW . "</h1>
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
                <td><b>" . TEXT_LOWFLOW_MODULE . "</b> : " . $module . " " . $axe_unite . "</td>
                <td><b>" . TEXT_STATS_CARD_N . "</b> : " . number_format($g_n, 0, '.', ' ') . "</td>
            </tr>
        </table>";

    $html  = "<style>" . $css . "</style>";
    $html .= $foreword;
    $html .= "<h2 class='lf-sectitle'>" . TEXT_LOWFLOW_RESULTS_TITLE . "</h2>";
    $html .= $body;

    if (!empty($note_html)) {
        $html .= "<pagebreak />";
        $html .= "<h2 class='lf-sectitle'>" . TEXT_LOWFLOW_HELP_TITLE . "</h2>" . $note_html;
    }

    $suffix   = ($mode === 'full') ? TEXT_LOWFLOW_PDF_FULL : TEXT_LOWFLOW_PDF_SUMMARY;
    $fileName = nettoyerNomFichier($code_station) . "_" . nettoyerNomFichier($nom_station)
              . "_" . nettoyerNomFichier(TEXT_STATS_BTN_LOWFLOW) . "_" . nettoyerNomFichier($suffix) . ".pdf";
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