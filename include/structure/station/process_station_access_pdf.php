<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Station ACCESS PDF sheet generator
Asynchronous AJAX server-side process
- Builds a one-page PDF with the station access information
  (owner, contact, access details) and the access map if present.
- Modeled on process_station_pdf.php (same mPDF shell / header / footer).
----------------------------------------
*/

// ----------------------------------------------
// Required files for script configuration

require('../../config.php');
require('../../database_tables.php');

// mPDF on a Dropbox/Windows path emits unlink() warnings when removing its
// internal temp PNG masks (file briefly locked by Dropbox sync). These are
// harmless but, if printed, they corrupt the JSON response. Silence display
// and capture output so only the JSON is ever returned.
@ini_set('display_errors', '0');
error_reporting(0);
ob_start();

require('../../function/sessions.php');
require('../../function/date.php');
require('../../function/database.php');
require('../../function/html_output.php');
require('../../function/general.php');

use Mpdf\Mpdf;

// Set UTF-8 charset header
header('Content-Type: text/html; charset=utf-8');

// Database connection
$sql_link = mysqli_connect(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD, DB_DATABASE)
    or die('Impossible de se connecter à la base de données!');
mysqli_query($sql_link, 'SET NAMES UTF8');

// -----------------------------------------------
// Load translation strings for the active language
require('../../text_content_' . LANGUAGE . '.php');

// Retrieve JSON data sent from the AJAX request
$jsonDataInfo = file_get_contents('php://input');
$dataInfo     = json_decode($jsonDataInfo, true);

// Extract values from the decoded array (cast / default for safety)
$territoire_nom = isset($dataInfo['territoire_nom']) ? $dataInfo['territoire_nom'] : '';
$timezone_php   = isset($dataInfo['timezone_php'])   ? $dataInfo['timezone_php']   : 'UTC';
$id_user        = isset($dataInfo['id_user'])        ? (int) $dataInfo['id_user']  : 0;
$id_station     = isset($dataInfo['idStation'])      ? (int) $dataInfo['idStation'] : 0;

// Set timezone based on territory and get current date
date_default_timezone_set($timezone_php);
$today = date('d-m-Y H:i:s');


// ----------------------------------------------
// DATA RETRIEVAL

// User info (PDF author)
$sql_user = "SELECT DISTINCT id, nom, prenom, info
             FROM " . TABLE_USER . "
             WHERE id=" . $id_user;
$user_query = tep_db_query($sql_link, $sql_user);
$user_tab   = tep_db_fetch_array($user_query);

$nom_user    = html_entity_decode($user_tab['nom']    ?? '');
$prenom_user = html_entity_decode($user_tab['prenom'] ?? '');
$info_user   = html_entity_decode($user_tab['info']   ?? '');

// Station info (name / code, used in the title and filename)
$sql_station = "SELECT DISTINCT id_station, nom_station, code_station
                FROM " . TABLE_STATION . "
                WHERE id_station = " . $id_station;
$station_query = tep_db_query($sql_link, $sql_station);
$station_tab   = tep_db_fetch_array($station_query);

$nom_station  = html_entity_decode($station_tab['nom_station']  ?? '');
$code_station = html_entity_decode($station_tab['code_station'] ?? '');

// Municipalities (to resolve contact_commune id -> name)
$commune_array = [];
$sql_commune   = "SELECT DISTINCT id_commune, nom_commune FROM " . TABLE_COMMUNE;
$commune_query = tep_db_query($sql_link, $sql_commune);
while ($commune = tep_db_fetch_array($commune_query))
{
    $commune_array[$commune['id_commune']] = html_entity_decode($commune['nom_commune'] ?? '');
}

// Access record
$access = [
    'proprietaire'      => '',
    'contact_nom'       => '',
    'contact_phone'     => '',
    'contact_mail'      => '',
    'contact_adresse'   => '',
    'contact_bp'        => '',
    'contact_cp'        => '',
    'contact_commune'   => 0,
    'info_access'       => '',
    'pedestre_access'   => 0,
    'time_access'       => '',
    'difficulty_access' => '',
    'remarque_access'   => '',
];

$sql_access = "SELECT DISTINCT proprietaire, contact_nom, contact_phone, contact_mail,
                      contact_adresse, contact_bp, contact_cp, contact_commune,
                      info_access, pedestre_access, time_access, difficulty_access, remarque_access
               FROM " . TABLE_STATION_ACCESS . "
               WHERE id_station = " . $id_station;
$access_query = tep_db_query($sql_link, $sql_access);
while ($access_tab = tep_db_fetch_array($access_query))
{
    $access = [
        'proprietaire'      => html_entity_decode($access_tab['proprietaire']      ?? ''),
        'contact_nom'       => html_entity_decode($access_tab['contact_nom']       ?? ''),
        'contact_phone'     => html_entity_decode($access_tab['contact_phone']     ?? ''),
        'contact_mail'      => html_entity_decode($access_tab['contact_mail']      ?? ''),
        'contact_adresse'   => html_entity_decode($access_tab['contact_adresse']   ?? ''),
        'contact_bp'        => html_entity_decode($access_tab['contact_bp']        ?? ''),
        'contact_cp'        => html_entity_decode($access_tab['contact_cp']        ?? ''),
        'contact_commune'   => $access_tab['contact_commune'],
        'info_access'       => html_entity_decode($access_tab['info_access']       ?? ''),
        'pedestre_access'   => $access_tab['pedestre_access'],
        'time_access'       => html_entity_decode($access_tab['time_access']       ?? ''),
        'difficulty_access' => html_entity_decode($access_tab['difficulty_access'] ?? ''),
        'remarque_access'   => html_entity_decode($access_tab['remarque_access']   ?? ''),
    ];
}

// Resolve display values
$text_commune   = isset($commune_array[$access['contact_commune']]) ? $commune_array[$access['contact_commune']] : '';
$text_pedestre  = ($access['pedestre_access'] == 1) ? TEXT_PDF_ACCESS_YES : TEXT_PDF_ACCESS_NO;

// Locate the access map file on disk (try jpg, jpeg, png)
$dossier_plan = '../../../' . DIR_WS_STATION_PHOTO_ACCESS;
$basePath     = $dossier_plan . $code_station . '_access';
$plan_img_src = '';
foreach (['jpg', 'jpeg', 'png'] as $ext)
{
    if (is_file($basePath . '.' . $ext)) { $plan_img_src = $basePath . '.' . $ext; break; }
}


// ----------------------------------------------
// PDF GENERATION

try {

    // PDF header and footer (same as the station PDF)
    $header = "
                <img src='../../../" . DIR_WS_IMG_PDF . "bando.png' style='100%;'>
            ";

    $footer = "
                <div style='text-align: center; font-size: 10px; border-top: 1px solid #000; padding-top: 5px;'>
                    " . TEXT_PDF_FOOTER_PAGE . " {PAGENO} " . TEXT_PDF_FOOTER_OF . " {nbpg} - " . TEXT_PDF_FOOTER_GENERATED . " " . $today . "
                </div>
            ";

    // Helper: escape text values injected into the HTML
    $esc = function ($v) { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); };

    // Main HTML content
    $html = "
            <h1>" . TEXT_PDF_ACCESS_TITLE . "</h1>

            <div id='bloc' style='margin-top:0px;'>
                <p><span>" . TEXT_PDF_EDITED_ON . "</span> : " . $today . "</p>
                <p><span>" . TEXT_PDF_EDITED_BY . "</span> : " . $esc($prenom_user) . " " . $esc($nom_user) . " - " . $esc($info_user) . "</p>
            </div>

            <div id='bloc' style='font-size:18px;margin-top:20px;'>
                <p style='width:400px;'>
                    <span>" . TEXT_PDF_STATION_NAME . "</span><br>" . $esc($nom_station) . "
                </p>
                <p>
                    <span>" . TEXT_PDF_STATION_CODE . "</span><br>" . $esc($code_station) . "
                </p>
            </div>

            <h2>" . TEXT_PDF_ACCESS_CONTACT . "</h2>

            <div id='bloc' style='margin-top:20px;'>
                <table>
                    <tr><td style='width:160px;'>" . TEXT_PDF_ACCESS_OWNER   . "</td><td style='padding-left:10px;'>" . $esc($access['proprietaire'])  . "</td></tr>
                    <tr><td style='width:160px;'>" . TEXT_PDF_ACCESS_NAME    . "</td><td style='padding-left:10px;'>" . $esc($access['contact_nom'])   . "</td></tr>
                    <tr><td style='width:160px;'>" . TEXT_PDF_ACCESS_PHONE   . "</td><td style='padding-left:10px;'>" . $esc($access['contact_phone']) . "</td></tr>
                    <tr><td style='width:160px;'>" . TEXT_PDF_ACCESS_EMAIL   . "</td><td style='padding-left:10px;'>" . $esc($access['contact_mail'])  . "</td></tr>
                    <tr><td style='width:160px;'>" . TEXT_PDF_ACCESS_ADDRESS . "</td><td style='padding-left:10px;'>" . $esc($access['contact_adresse']) . "</td></tr>
                    <tr><td style='width:160px;'>" . TEXT_PDF_ACCESS_PO_BOX  . "</td><td style='padding-left:10px;'>" . $esc($access['contact_bp'])    . "</td></tr>
                    <tr><td style='width:160px;'>" . TEXT_PDF_ACCESS_POSTCODE . "</td><td style='padding-left:10px;'>" . $esc($access['contact_cp'])   . "</td></tr>
                    <tr><td style='width:160px;'>" . TEXT_PDF_ACCESS_COMMUNE . "</td><td style='padding-left:10px;'>" . $esc($text_commune)            . "</td></tr>
                </table>
            </div>

            <h2>" . TEXT_PDF_ACCESS_DETAILS . "</h2>

            <div id='bloc' style='margin-top:20px;'>
                <p><span>" . TEXT_PDF_ACCESS_PEDESTRIAN . "</span> : " . $text_pedestre . "</p>
                <p><span>" . TEXT_PDF_ACCESS_TIME . "</span> : " . $esc($access['time_access']) . "</p>
            </div>

            <div id='bloc' style='margin-top:10px;'>
                <p><span>" . TEXT_PDF_ACCESS_INFO . "</span></p>
                " . nl2br($esc($access['info_access'])) . "
            </div>

            <div id='bloc' style='margin-top:10px;'>
                <p><span>" . TEXT_PDF_ACCESS_DIFFICULTY . "</span></p>
                " . nl2br($esc($access['difficulty_access'])) . "
            </div>

            <div id='bloc' style='margin-top:10px;'>
                <p><span>" . TEXT_PDF_ACCESS_REMARKS . "</span></p>
                " . nl2br($esc($access['remarque_access'])) . "
            </div>";

    // Access map (only if a file exists on disk)
    if (tep_not_null($plan_img_src))
    {
        $html .= "
            <pagebreak />
            <h2>" . TEXT_PDF_ACCESS_MAP . "</h2>
            <div id='bloc' style='margin-top:20px;text-align:center;'>
                <img src='" . $plan_img_src . "' style='max-width:100%;' />
            </div>";
    }

    // Build output filename and path
    $fileName = "Acces_" . $code_station . "_" . nettoyerNomFichier($nom_station) . ".pdf";
    $filePath = $_SERVER['DOCUMENT_ROOT'] . "/" . DIR_WS_PDF . $fileName;

    // Initialise mPDF
    $mpdf = new \Mpdf\Mpdf([
        'margin_left'   => 10,
        'margin_right'  => 10,
        'margin_top'    => 30,
        'margin_bottom' => 10
    ]);

    // Load and apply CSS stylesheet (same as the station PDF)
    $stylesheet = file_get_contents('../../../css/pdf_css.css');
    $mpdf->WriteHTML($stylesheet, \Mpdf\HTMLParserMode::HEADER_CSS);
    $mpdf->SetHTMLHeader($header);
    $mpdf->SetHTMLFooter($footer);
    $mpdf->WriteHTML($html);

    $mpdf->Output($filePath, \Mpdf\Output\Destination::FILE);

    $responseData = array(
        'status'   => 'success',
        'fileName' => $fileName
    );

    if (ob_get_length() !== false) { ob_clean(); }
    echo json_encode($responseData);

} catch (\Mpdf\MpdfException $e) {
    if (ob_get_length() !== false) { ob_clean(); }
    echo json_encode([
        'status'   => 'error',
        'msg_info' => TEXT_PDF_ACCESS_ERROR
    ]);
}
?>