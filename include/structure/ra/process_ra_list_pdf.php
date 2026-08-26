<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Field Report (RA) summary list PDF.
One row per selected field report, with a small set of columns:
validation, station number/name, reading date/time, comment, planned, operators.
Asynchronous AJAX server-side process.
----------------------------------------
*/

require('../../config.php');
require('../../database_tables.php');

@ini_set('display_errors', '0');
error_reporting(0);
ob_start();

require('../../function/sessions.php');
require('../../function/date.php');
require('../../function/database.php');
require('../../function/html_output.php');
require('../../function/general.php');

use Mpdf\Mpdf;

header('Content-Type: text/html; charset=utf-8');

$sql_link = mysqli_connect(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD, DB_DATABASE)
    or die('Impossible de se connecter a la base de donnees!');
mysqli_query($sql_link, 'SET NAMES UTF8');

require('../../text_content_' . LANGUAGE . '.php');

// -----------------------------------------------
// Read JSON request
$jsonDataInfo = file_get_contents('php://input');
$dataInfo     = json_decode($jsonDataInfo, true);

$territoire_id = $dataInfo['territoire_id'] ?? 0;
$timezone_php  = $dataInfo['timezone_php'] ?? 'Pacific/Tahiti';

$list_id_ra = [];
if (isset($dataInfo['list_id_ra'])) {
    if (is_array($dataInfo['list_id_ra'])) {
        $list_id_ra = $dataInfo['list_id_ra'];
    } else {
        $list_id_ra = explode(',', (string)$dataInfo['list_id_ra']);
    }
} elseif (isset($dataInfo['id_ra'])) {
    $list_id_ra = [$dataInfo['id_ra']];
}
$list_id_ra = array_values(array_filter(array_map('intval', $list_id_ra), function ($v) { return $v > 0; }));

if (empty($list_id_ra)) {
    if (ob_get_length() !== false) { ob_clean(); }
    echo json_encode(['status' => 'error', 'msg_info' => TEXT_RA_PDF_NO_SELECTION]);
    exit;
}

date_default_timezone_set($timezone_php);
$today = date('d-m-Y');

// -----------------------------------------------
// Reference data: stations (code + name)
$station_all_array = [];
$sql_station_all = "SELECT DISTINCT id_station, nom_station, code_station FROM " . TABLE_STATION;
$station_all_query = tep_db_query($sql_link, $sql_station_all);
while ($s = tep_db_fetch_array($station_all_query)) {
    $station_all_array[$s['id_station']] = array(
        'code_station' => $s['code_station'],
        'nom_station'  => htmlaccent(html_entity_decode($s['nom_station'] ?? ''))
    );
}

// Short cell value: dash when empty, strip line breaks
function ral_val($v) {
    $v = trim(str_replace(["\r", "\n"], ' ', (string)$v));
    return ($v === '' || $v === '00:00:00') ? '-' : htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}

try {
    // PDF header / footer (same look as the other RA PDF)
    $header = "<img src='../../../" . DIR_WS_IMG_PDF . "bando.png' style='100%;'>";
    $footer = "
        <div style='text-align: center; font-size: 10px; border-top: 1px solid #000; padding-top: 5px;'>
            " . TEXT_PDF_FOOTER_PAGE . " {PAGENO} " . TEXT_PDF_FOOTER_OF . " {nbpg} - " . TEXT_PDF_FOOTER_GENERATED . " " . $today . "
        </div>";

    $in_list = implode(',', $list_id_ra);
    $sql_RA = "SELECT ra.id_ra, ra.id_station, ra.etat_ra, ra.date_heure_ra,
                      ra.ra_obs, ra.ra_futur, ra.agents_complement
               FROM " . TABLE_DATA_RA . " ra
               WHERE ra.id_ra IN (" . $in_list . ")
               ORDER BY ra.date_heure_ra ASC";
    $RA_query = tep_db_query($sql_link, $sql_RA);

    // Build the table
    $rows = "";
    $r = 0;
    while ($RA = tep_db_fetch_array($RA_query)) {
        $r++;
        $row_class = ($r % 2 == 0) ? 'row2' : 'row1';

        $sid  = $RA['id_station'];
        $code = isset($station_all_array[$sid]) ? $station_all_array[$sid]['code_station'] : '';
        $nom  = isset($station_all_array[$sid]) ? $station_all_array[$sid]['nom_station'] : '';

        $dh = explode(' ', $RA['date_heure_ra'] ?? '');
        $date_ra  = isset($dh[0]) ? dateus_fr($dh[0]) : '';
        $heure_ra = $dh[1] ?? '';

        // Validation dot
        $etat_ra = (int)$RA['etat_ra'];
        $dot_color = ($etat_ra > 0) ? '#2e7d32' : '#c62828';
        $dot = "<span style='color:" . $dot_color . ";font-size:13px;'>&#9679;</span>";

        $rows .= "<tr class='" . $row_class . "'>
                    <td style='text-align:center;'>" . $dot . "</td>
                    <td>" . ral_val($code) . "</td>
                    <td>" . htmlspecialchars($nom, ENT_QUOTES, 'UTF-8') . "</td>
                    <td>" . ral_val($date_ra) . "</td>
                    <td>" . ral_val($heure_ra) . "</td>
                    <td>" . ral_val($RA['ra_obs']) . "</td>
                    <td>" . ral_val($RA['ra_futur']) . "</td>
                    <td>" . ral_val($RA['agents_complement']) . "</td>
                  </tr>";
    }

    $html = "
        <h1>" . TEXT_RA_LIST_PDF_TITLE . "</h1>
        <table class='data' style='margin-top:10px;'>
            <thead>
                <tr>
                    <th style='text-align:center;'>" . TEXT_RA_LIST_COL_VALID . "</th>
                    <th>" . TEXT_RA_LIST_COL_STATION_NUM . "</th>
                    <th>" . TEXT_RA_LIST_COL_STATION_NAME . "</th>
                    <th>" . TEXT_RA_LIST_COL_DATE . "</th>
                    <th>" . TEXT_RA_LIST_COL_TIME . "</th>
                    <th>" . TEXT_RA_LIST_COL_COMMENT . "</th>
                    <th>" . TEXT_RA_LIST_COL_PLANNED . "</th>
                    <th>" . TEXT_RA_LIST_COL_OPERATORS . "</th>
                </tr>
            </thead>
            <tbody>" . $rows . "</tbody>
        </table>
    ";

    $fileName = TEXT_RA . "_liste_" . count($list_id_ra) . "_" . date('Ymd_His') . ".pdf";
    $filePath = $_SERVER['DOCUMENT_ROOT'] . "/" . DIR_WS_PDF . $fileName;

    // Landscape orientation: the list is wide
    $mpdf = new \Mpdf\Mpdf([
        'orientation'   => 'L',
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

    if (ob_get_length() !== false) { ob_clean(); }
    echo json_encode(['status' => 'success', 'fileName' => $fileName]);

} catch (\Mpdf\MpdfException $e) {
    if (ob_get_length() !== false) { ob_clean(); }
    echo json_encode(['status' => 'error', 'msg_info' => $e->getMessage()]);
}
?>
