<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Data qualification PDF generator
Asynchronous AJAX server-side process

Builds a PDF table of the data_meta blocks (quality code, period, point
count, correction, comment) for a station + chronicle, using the same
mPDF technique as process_station_pdf.php (shared header/footer + CSS).

Receives JSON: idStation, typedataChron, xDateMin, xDateMax (FR, optional)
Returns JSON: { status, fileName }  — client then downloads DIR_WS_PDF/fileName
----------------------------------------
*/

require('../../config.php');
require('../../database_tables.php');

require('../../function/sessions.php');
require('../../function/date.php');
require('../../function/database.php');
require('../../function/html_output.php');
require('../../function/general.php');
require('../../function/stats.php');

use Mpdf\Mpdf;

header('Content-Type: text/html; charset=utf-8');

$sql_link = mysqli_connect(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD, DB_DATABASE)
    or die('Impossible de se connecter à la base de données!');
mysqli_query($sql_link, 'SET NAMES UTF8');

require('../../text_content_' . LANGUAGE . '.php');

$data           = json_decode(file_get_contents('php://input'), true);
$min_x          = $data['xDateMin']       ?? '';
$max_x          = $data['xDateMax']       ?? '';
$station_chron  = (int)($data['idStation']      ?? 0);
$typedata_chron = (int)($data['typedataChron']  ?? 0);

$today  = date('d/m/Y H:i');

$date_1 = '1950-01-01';
$date_2 = date('Y-m-d');
if (tep_not_null($min_x) && tep_not_null($max_x))
{
    $date_1 = datefr_us($min_x);
    $date_2 = datefr_us($max_x);
}

try
{
    // -----------------------------------------------
    // Station + chronicle labels

    $code_station = '';
    $nom_station  = '';
    $st_query = tep_db_query($sql_link,
        "SELECT code_station, nom_station FROM " . TABLE_STATION . "
         WHERE id_station = " . $station_chron);
    if ($st = tep_db_fetch_array($st_query))
    {
        $code_station = $st['code_station'];
        $nom_station  = html_entity_decode($st['nom_station'] ?? '');
    }

    $chron_init = '';
    $chron_nom  = '';
    $tc_query = tep_db_query($sql_link,
        "SELECT init_type_data, nom_type_data FROM " . TABLE_TYPE_DATA . "
         WHERE id_data_type = " . $typedata_chron);
    if ($tc = tep_db_fetch_array($tc_query))
    {
        $chron_init = $tc['init_type_data'];
        $chron_nom  = html_entity_decode($tc['nom_type_data'] ?? '');
    }


    // -----------------------------------------------
    // Quality code lookup

    $code_qual_array = [];
    $cq_query = tep_db_query($sql_link,
        "SELECT DISTINCT id_data_qualite, init_qualite_data, nom_qualite_data, couleur_qualite_data
         FROM " . TABLE_DATA_QUALITE . " ORDER BY id_data_qualite");
    while ($cq = tep_db_fetch_array($cq_query))
    {
        $code_qual_array[$cq['id_data_qualite']] = [
            'init'    => html_entity_decode($cq['init_qualite_data'] ?? ''),
            'nom'     => html_entity_decode($cq['nom_qualite_data']  ?? ''),
            'couleur' => $cq['couleur_qualite_data'] ?? '',
        ];
    }


    // -----------------------------------------------
    // data_meta blocks (same query as process_graph_qualif.php)

    $rows_html = '';
    $meta_query = tep_db_query($sql_link,
        "SELECT dm.id          AS id_meta,
                dm.id_codequal AS id_codequal,
                dm.obs         AS obs,
                dm.obs_user    AS obs_user,
                MIN(da.dateheure) AS date_first,
                MAX(da.dateheure) AS date_end,
                COUNT(*)          AS nb_points
         FROM " . TABLE_DATA_META . " dm
         JOIN " . TABLE_DATA_ALL  . " da ON da.id_meta = dm.id
         WHERE dm.id_station  = " . $station_chron  . "
         AND   dm.id_typedata = " . $typedata_chron . "
         AND   da.dateheure  <= '" . $date_2 . " 23:59:59'
         AND   da.dateheure  >= '" . $date_1 . " 00:00:00'
         GROUP BY dm.id, dm.id_codequal, dm.obs, dm.obs_user
         ORDER BY date_first ASC");

    while ($m = tep_db_fetch_array($meta_query))
    {
        $cq    = $code_qual_array[$m['id_codequal']] ?? null;
        $qinit = $cq ? $cq['init'] : '';
        $qnom  = $cq ? $cq['nom']  : '';
        $color = ($cq && tep_not_null($cq['couleur'])) ? $cq['couleur'] : '#ffffff';

        $qlabel = trim($qinit . ($qnom !== '' ? ' - ' . $qnom : ''));

        // mPDF renders inline-block backgrounds unreliably, so use a colored
        // filled-square glyph (■) instead — robust in PDF output.
        $swatch = ($color !== '#ffffff')
            ? "<span style='color:" . $color . ";font-size:12px;'>&#9632;</span> "
            : "<span style='color:#000;font-size:12px;'>&#9633;</span> ";

        $rows_html .=
            "<tr>"
          . "<td>" . $swatch . " " . htmlspecialchars($qlabel) . "</td>"
          . "<td style='white-space:nowrap;'>" . date('d/m/Y H:i', strtotime($m['date_first'])) . "</td>"
          . "<td style='white-space:nowrap;'>" . date('d/m/Y H:i', strtotime($m['date_end']))   . "</td>"
          . "<td style='text-align:right;'>" . (int)$m['nb_points'] . "</td>"
          . "<td>" . htmlspecialchars($m['obs'] ?? '')      . "</td>"
          . "<td>" . htmlspecialchars($m['obs_user'] ?? '') . "</td>"
          . "</tr>";
    }

    if ($rows_html === '')
    {
        $rows_html = "<tr><td colspan='6' style='text-align:center;color:#888;'>—</td></tr>";
    }


    // -----------------------------------------------
    // Header / footer (same as process_station_pdf.php)

    $header = "
        <img src='../../../" . DIR_WS_IMG_PDF . "bando.png' style='100%;'>
    ";

    $footer = "
        <div style='text-align: center; font-size: 10px; border-top: 1px solid #000; padding-top: 5px;'>
            " . TEXT_PDF_FOOTER_PAGE . " {PAGENO} " . TEXT_PDF_FOOTER_OF . " {nbpg} - " . TEXT_PDF_FOOTER_GENERATED . " " . $today . "
        </div>
    ";


    // -----------------------------------------------
    // Body HTML

    $html = "
        <h1>" . TEXT_DATA_QUALIF . "</h1>

        <div id='bloc' style='margin-top:0px;'>
            <p><b>" . TEXT_STATS_STATION . " :</b> " . htmlspecialchars($code_station . ' - ' . $nom_station) . "</p>
            <p><b>" . TEXT_STATS_CHRONIQUE . " :</b> " . htmlspecialchars($chron_init . ' - ' . $chron_nom) . "</p>
            <p><b>" . TEXT_STATS_PERIOD . " :</b> " . TEXT_STATS_PERIOD_FROM . " " . date('d/m/Y', strtotime($date_1))
                . " " . TEXT_STATS_PERIOD_TO . " " . date('d/m/Y', strtotime($date_2)) . "</p>
        </div>

        <table border='1' cellspacing='0' cellpadding='4'
               style='width:100%;border-collapse:collapse;font-size:11px;margin-top:10px;table-layout:fixed;'>
            <colgroup>
                <col style='width:18%;'>
                <col style='width:14%;'>
                <col style='width:14%;'>
                <col style='width:7%;'>
                <col style='width:18%;'>
                <col style='width:29%;'>
            </colgroup>
            <thead>
                <tr style='background-color:#eef3f8;font-weight:bold;'>
                    <th>" . TEXT_GRAPH_HOVER_QUALCODE . "</th>
                    <th style='white-space:nowrap;'>" . TEXT_GRAPH_META_START . "</th>
                    <th style='white-space:nowrap;'>" . TEXT_GRAPH_META_END . "</th>
                    <th>" . TEXT_GRAPH_META_NBPTS . "</th>
                    <th>" . TEXT_GRAPH_HOVER_CORRECTION . "</th>
                    <th>" . TEXT_GRAPH_HOVER_CORRECTION_OBS . "</th>
                </tr>
            </thead>
            <tbody>
                " . $rows_html . "
            </tbody>
        </table>
    ";


    // -----------------------------------------------
    // Build filename + generate the PDF

    $fileName = TEXT_FILE_QUALIF . "_" . $code_station . "_" . nettoyerNomFichier($nom_station)
              . "_" . nettoyerNomFichier($chron_init) . ".pdf";
    $filePath = $_SERVER['DOCUMENT_ROOT'] . "/" . DIR_WS_PDF . $fileName;

    $mpdf = new \Mpdf\Mpdf([
        'margin_left'   => 10,
        'margin_right'  => 10,
        'margin_top'    => 30,
        'margin_bottom' => 10
    ]);

    $stylesheet = file_get_contents('../../../css/pdf_css.css');
    $mpdf->WriteHTML($stylesheet, \Mpdf\HTMLParserMode::HEADER_CSS);
    $mpdf->SetHTMLHeader($header);
    $mpdf->SetHTMLFooter($footer);
    $mpdf->WriteHTML($html);

    $mpdf->Output($filePath, \Mpdf\Output\Destination::FILE);

    echo json_encode([
        'status'   => 'success',
        'fileName' => $fileName,
    ]);
}
catch (\Mpdf\MpdfException $e)
{
    echo json_encode([
        'status'  => 'error',
        'message' => $e->getMessage(),
    ]);
}
?>