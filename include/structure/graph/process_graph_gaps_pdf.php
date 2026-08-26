<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Data gaps (lacunes) PDF generator
Asynchronous AJAX server-side process

Builds a PDF table of the detected gaps (marker-value runs) for a station
+ chronicle, using the same mPDF technique as process_station_pdf.php. Each
gap shows its period, point count, and the quality code / obs of the
data_meta the START point belongs to.

Receives JSON: idStation, typedataChron, xDateMin, xDateMax (FR, optional)
Returns JSON: { status, fileName }
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
    // data_meta lookup keyed by id_meta (to resolve the gap's quality code)

    $meta_info = [];
    $mi_query = tep_db_query($sql_link,
        "SELECT id, id_codequal, obs, obs_user
         FROM " . TABLE_DATA_META . "
         WHERE id_station  = " . $station_chron  . "
         AND   id_typedata = " . $typedata_chron);
    while ($mi = tep_db_fetch_array($mi_query))
    {
        $meta_info[$mi['id']] = [
            'id_codequal' => $mi['id_codequal'],
            'obs'         => $mi['obs']      ?? '',
            'obs_user'    => $mi['obs_user'] ?? '',
        ];
    }


    // -----------------------------------------------
    // Scan points in date order; detect marker runs as gaps.

    $markers = [9999, 99999, 8888, 88888];

    $rows_html = '';
    $pts_query = tep_db_query($sql_link,
        "SELECT da.dateheure, da.valeur, da.id_meta
         FROM " . TABLE_DATA_ALL  . " da
         JOIN " . TABLE_DATA_META . " dm ON da.id_meta = dm.id
         WHERE dm.id_station  = " . $station_chron  . "
         AND   dm.id_typedata = " . $typedata_chron . "
         AND   da.dateheure  >= '" . $date_1 . " 00:00:00'
         AND   da.dateheure  <= '" . $date_2 . " 23:59:59'
         ORDER BY da.dateheure ASC");

    $gap_open  = false;
    $gap_first = ''; $gap_last = ''; $gap_meta = null; $gap_count = 0;

    $emit_gap = function($gap_meta, $gap_first, $gap_last, $gap_count)
                use ($code_qual_array, $meta_info) {
        $qinit = ''; $qnom = ''; $color = '#ffffff'; $obs = ''; $obs_user = '';
        if ($gap_meta !== null && isset($meta_info[$gap_meta]))
        {
            $mi = $meta_info[$gap_meta];
            $obs = $mi['obs']; $obs_user = $mi['obs_user'];
            $cq  = $code_qual_array[$mi['id_codequal']] ?? null;
            if ($cq)
            {
                $qinit = $cq['init']; $qnom = $cq['nom'];
                $color = tep_not_null($cq['couleur']) ? $cq['couleur'] : '#ffffff';
            }
        }
        $qlabel = trim($qinit . ($qnom !== '' ? ' - ' . $qnom : ''));
        $swatch = ($color !== '#ffffff')
            ? "<span style='color:" . $color . ";font-size:12px;'>&#9632;</span> "
            : "<span style='color:#000;font-size:12px;'>&#9633;</span> ";

        return "<tr>"
             . "<td>" . $swatch . htmlspecialchars($qlabel) . "</td>"
             . "<td style='white-space:nowrap;'>" . date('d/m/Y H:i', strtotime($gap_first)) . "</td>"
             . "<td style='white-space:nowrap;'>" . date('d/m/Y H:i', strtotime($gap_last))  . "</td>"
             . "<td style='text-align:right;'>" . (int)$gap_count . "</td>"
             . "<td>" . htmlspecialchars($obs)      . "</td>"
             . "<td>" . htmlspecialchars($obs_user) . "</td>"
             . "</tr>";
    };

    while ($p = tep_db_fetch_array($pts_query))
    {
        $is_marker = in_array(abs($p['valeur']), $markers);
        if ($is_marker)
        {
            if (!$gap_open)
            {
                $gap_open = true; $gap_first = $p['dateheure'];
                $gap_meta = $p['id_meta']; $gap_count = 0;
            }
            $gap_last = $p['dateheure']; $gap_count++;
        }
        else
        {
            if ($gap_open)
            {
                $rows_html .= $emit_gap($gap_meta, $gap_first, $gap_last, $gap_count);
                $gap_open = false; $gap_meta = null;
            }
        }
    }
    if ($gap_open)
    {
        $rows_html .= $emit_gap($gap_meta, $gap_first, $gap_last, $gap_count);
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
        <h1>" . TEXT_GAPS . "</h1>

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

    $fileName = TEXT_FILE_GAPS . "_" . $code_station . "_" . nettoyerNomFichier($nom_station)
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