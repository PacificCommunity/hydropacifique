<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Station PDF sheet generator
Asynchronous AJAX server-side process
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
require('../../function/stats.php');

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

// Decode JSON data into a PHP associative array
$dataInfo = json_decode($jsonDataInfo, true);

// Extract values from the decoded array
$territoire_id         = $dataInfo['territoire_id'];
$territoire_nom        = $dataInfo['territoire_nom'];
$territoire_region     = $dataInfo['territoire_region'];
$timezone_php          = $dataInfo['timezone_php'];
$id_user               = $dataInfo['id_user'];
$id_station            = $dataInfo['idStation'];
$graphImage            = $dataInfo['graphImage'] ?? null;
$html_tab_data_station = $dataInfo['html_tab_data_station'];
$html_tab_code_cal     = $dataInfo['html_tab_code_cal'];
$chron_data_array      = $dataInfo['chron_data_array'];

// Set timezone based on territory and get current date
date_default_timezone_set($timezone_php);
$today = date('d-m-Y H:i:s');


// ----------------------------------------------
// DATA RETRIEVAL

// User info
$sql_user = "SELECT DISTINCT id, nom, prenom, info
             FROM " . TABLE_USER . "
             WHERE id=" . $id_user;
$user_query = tep_db_query($sql_link, $sql_user);
$user_tab   = tep_db_fetch_array($user_query);

$nom_user    = html_entity_decode($user_tab['nom']    ?? '');
$prenom_user = html_entity_decode($user_tab['prenom'] ?? '');
$info_user   = html_entity_decode($user_tab['info']   ?? '');

// Data type table (streamflow, rainfall, piezometry, ...)
$sql_eq_type = "SELECT DISTINCT id_eq_type, nom_eq_type, type_color_border, type_color_background
                FROM " . TABLE_EQ_TYPE . "
                WHERE active_eq_type=1
                ORDER BY order_eq_type ASC";
$eq_type_query = tep_db_query($sql_link, $sql_eq_type);
while ($eq_type = tep_db_fetch_array($eq_type_query))
{
    $eq_type_array[$eq_type['id_eq_type']] = array(
        'nom_eq_type'          => html_entity_decode($eq_type['nom_eq_type']          ?? ''),
        'type_color_border'    => html_entity_decode($eq_type['type_color_border']    ?? ''),
        'type_color_background' => html_entity_decode($eq_type['type_color_background'] ?? ''),
    );
}

// Region / territory table (Province for NC, Islands for PF/WF)
$sql_region = "SELECT DISTINCT id_region, nom_region
               FROM " . TABLE_REGION . "
               WHERE id_territoire=" . $territoire_id;
$region_query = tep_db_query($sql_link, $sql_region);
while ($region = tep_db_fetch_array($region_query))
{
    $region_array[$region['id_region']] = html_entity_decode($region['nom_region'] ?? '');
}

// Commune table
$sql_commune = "SELECT DISTINCT c.id_commune, c.nom_commune
                FROM " . TABLE_COMMUNE . " c
                JOIN " . TABLE_REGION . " r ON c.id_region=r.id_region
                WHERE r.id_territoire=" . $territoire_id . "
                ORDER BY c.nom_commune ASC";
$commune_query = tep_db_query($sql_link, $sql_commune);
while ($commune = tep_db_fetch_array($commune_query))
{
    $commune_array[$commune['id_commune']] = html_entity_decode($commune['nom_commune'] ?? '');
}

// Hydrological region table
$sql_regionhydro = "SELECT DISTINCT rh.id, rh.nom, rh.id_territoire
                    FROM " . TABLE_REGIONHYDRO . " rh
                    WHERE rh.id_territoire=" . $territoire_id . "
                    ORDER BY nom ASC";
$regionhydro_query = tep_db_query($sql_link, $sql_regionhydro);
while ($regionhydro = tep_db_fetch_array($regionhydro_query))
{
    $regionhydro_array[$regionhydro['id']] = html_entity_decode($regionhydro['nom'] ?? '');
}

// Station data
$sql_station = "SELECT DISTINCT s.id_station, s.id_station_old, s.nom_station, s.nom_court, s.code_station, s.num_irh,
                                s.id_region, s.id_commune, s.site_station, s.id_regionhydro, s.vallee_station, s.riviere_station,
                                s.altitude_station, s.orientation_station, s.longitude_station, s.latitude_station,
                                s.utm_station_x, s.utm_station_y, s.ign_station_x, s.ign_station_y, s.lamb_station_x,
                                s.lamb_station_y, s.source_info, s.station_type, s.transmission_station,
                                s.active_station, s.suivi, s.armee,
                                s.id_tournee, s.id_regionhydro,
                                s.date_installation_station, s.date_fermeture_station, s.description_station
                FROM " . TABLE_STATION . " s
                WHERE s.id_station=" . $id_station;

$station_query = tep_db_query($sql_link, $sql_station);
$station       = tep_db_fetch_array($station_query);

$id_eq_type     = 0;
$id_regionhydro = 0;
$id_region      = 0;
$id_commune     = 0;
$id_tournee     = 0;

if (isset($station))
{
    $id_station_old = $station['id_station_old'];

    $nom_station  = html_entity_decode($station['nom_station'] ?? '');
    $nom_court    = html_entity_decode($station['nom_court']   ?? '');
    $code_station = html_entity_decode($station['code_station'] ?? '');
    $num_irh      = html_entity_decode($station['num_irh']     ?? '');

    $id_region    = $station['id_region'];
    $text_region  = '-';
    if (isset($region_array[$id_region])) { $text_region = $region_array[$id_region]; }

    $id_commune   = $station['id_commune'];
    $text_commune = '-';
    if (isset($region_array[$id_commune])) { $text_commune = $commune_array[$id_commune]; }

    $site_station = html_entity_decode($station['site_station'] ?? '');

    $id_eq_type        = $station['station_type'];
    $text_eq_type      = '-';
    $type_color_border = '';

    if (isset($eq_type_array[$id_eq_type]))
    {
        $text_eq_type      = $eq_type_array[$id_eq_type]['nom_eq_type'];
        $type_color_border = $eq_type_array[$id_eq_type]['type_color_border'];
    }

    $riviere_station = html_entity_decode($station['riviere_station'] ?? '-');

    $id_regionhydro   = $station['id_regionhydro'];
    $text_regionhydro = '-';
    if (isset($regionhydro_array[$id_regionhydro])) { $text_regionhydro = $regionhydro_array[$id_regionhydro]; }

    // Altitude
    $altitude_station = '-';
    if (tep_not_null($station['altitude_station']))
    {
        $altitude_station = str_replace(',', '.', $station['altitude_station']);
    }
    if (is_numeric($altitude_station)) { $altitude_station = number_format(floatval($altitude_station), 3); }

    // GPS coordinates
    $longitude_station = '-';
    if (tep_not_null($station['longitude_station']))
    {
        $longitude_station = str_replace(',', '.', $station['longitude_station']);
    }
    $longitude_station = str_replace(["Ã", "", "Â"], "", $longitude_station);

    $latitude_station = '-';
    if (tep_not_null($station['latitude_station']))
    {
        $latitude_station = str_replace(',', '.', $station['latitude_station']);
    }
    $latitude_station = str_replace(["Ã", "", "Â"], "", $latitude_station);

    $utm_station_x = '-';
    if (tep_not_null($station['utm_station_x']))
    {
        $utm_station_x = str_replace(',', '.', $station['utm_station_x']);
    }

    $utm_station_y = '-';
    if (tep_not_null($station['utm_station_y']))
    {
        $utm_station_y = str_replace(',', '.', $station['utm_station_y']);
    }

    $ign_station_x = '-';
    if (tep_not_null($station['ign_station_x']))
    {
        $ign_station_x = str_replace(',', '.', $station['ign_station_x']);
    }
    if (is_numeric($ign_station_x)) { $ign_station_x = number_format(floatval($ign_station_x), 3); }

    $ign_station_y = '-';
    if (tep_not_null($station['ign_station_y']))
    {
        $ign_station_y = str_replace(',', '.', $station['ign_station_y']);
    }
    if (is_numeric($ign_station_y)) { $ign_station_y = number_format(floatval($ign_station_y), 3); }

    $lamb_station_x = '-';
    if (tep_not_null($station['lamb_station_x']))
    {
        $lamb_station_x = str_replace(',', '.', $station['lamb_station_x']);
    }

    $lamb_station_y = '-';
    if (tep_not_null($station['lamb_station_y']))
    {
        $lamb_station_y = str_replace(',', '.', $station['lamb_station_y']);
    }

    $source_info = $station['source_info'];

    // Station status labels
    $active_station      = $station['active_station'];
    $text_active_station = TEXT_PDF_STATUS_CLOSED;
    if ($active_station == 1) { $text_active_station = TEXT_PDF_STATUS_ACTIVE; }

    $suivi_station      = $station['suivi'];
    $text_suivi_station = TEXT_PDF_MONITORING_SPOT;
    if ($suivi_station == 1) { $text_suivi_station = TEXT_PDF_MONITORING_CONTINUOUS; }

    $armee_station      = $station['armee'];
    $text_armee_station = TEXT_PDF_EQUIPMENT_OK;
    if ($armee_station == 1) { $text_armee_station = TEXT_PDF_EQUIPMENT_FAULTY; }

    $transmission_station = $station['transmission_station'];

    // Dates
    $date_installation_station = '-';
    if ($station['date_installation_station'] != '0000-00-00')
    {
        $date_installation_station = dateus_fr($station['date_installation_station']);
    }

    $date_fermeture_station = '-';
    if ($station['date_fermeture_station'] != '0000-00-00')
    {
        $date_fermeture_station = dateus_fr($station['date_fermeture_station']);
    }

    $description_station = '-';
    if (tep_not_null($station['description_station']))
    {
        $description_station = $station['description_station'];
    }
}

// Activity reports (last 25)
$sql_ra = "SELECT DISTINCT ra.id_ra, ra.id_agent_user,
                            ra.date_heure_ra, ra.etat_ra,
                            ra.ra_obs, ra.ra_futur, ra.pre_marquant, ra.fait_marquant,
                            ra.agents_complement
           FROM " . TABLE_DATA_RA . " ra
           WHERE id_station=" . $id_station . "
           ORDER BY date_heure_ra DESC
           LIMIT 25";
$ra_query = tep_db_query($sql_link, $sql_ra);
while ($ra_tab = tep_db_fetch_array($ra_query))
{
    $tab_date_heure_ra = explode(" ", $ra_tab['date_heure_ra']);
    $date_ra           = dateus_fr($tab_date_heure_ra[0]);

    $ra_array[$ra_tab['id_ra']] = array(
        'id_agent'          => $ra_tab['id_agent_user'],
        'date_ra'           => $date_ra,
        'etat_ra'           => $ra_tab['etat_ra'],
        'ra_obs'            => $ra_tab['ra_obs'],
        'ra_futur'          => $ra_tab['ra_futur'],
        'agents_complement' => $ra_tab['agents_complement'],
    );
}

// Station photos
$html_photos_station = '';
$num_photo           = 0;

$sql_photos = "SELECT DISTINCT id, id_station, date_photo, description_photo, file_photo
               FROM " . TABLE_STATION_PHOTOS . "
               WHERE id_station = " . $id_station . "
               ORDER BY date_photo DESC";
$photos_query = tep_db_query($sql_link, $sql_photos);
while ($photos_tab = tep_db_fetch_array($photos_query))
{
    $num_photo++;

    $image_path = $_SERVER['DOCUMENT_ROOT'] . "/" . DIR_WS_DATA_PHOTOS . $photos_tab['file_photo'];

    $date_photo_fr = dateus_fr($photos_tab['date_photo']);
    if ($date_photo_fr == '00-00-0000') { $date_photo_fr = ''; }

    $html_photos_station .= "<h2>" . TEXT_PDF_PHOTOS_TITLE . "</h2>";
    $html_photos_station .= "<div id='cadre_photo'>";

        if (is_file($image_path))
        {
            $html_photos_station .= "
                        <div style='width:70%;margin:14px auto 6px auto;text-align:center;'>
                            <img src='" . $image_path . "' style='max-width:100%;height:auto;'>
                        </div>
                        ";
        }

        $html_photos_station .= "
                        <table class='fields' style='width:70%;margin:0 auto;'>
                            <tr>
                                <td class='label'><span class='field-label'>" . TEXT_PDF_PHOTO_DATE . "</span></td>
                                <td class='value'>" . $date_photo_fr . "</td>
                            </tr>
                            <tr>
                                <td class='label'><span class='field-label'>" . TEXT_PDF_PHOTO_DESC . "</span></td>
                                <td class='value'>" . $photos_tab['description_photo'] . "</td>
                            </tr>
                        </table>
                        ";

    $html_photos_station .= "</div>";
    $html_photos_station .= "<pagebreak /> <!-- Page break -->";
}


// ----------------------------------------------
// PDF GENERATION

try {

    // PDF header and footer
    $header = "
                <img src='../../../" . DIR_WS_IMG_PDF . "bando.png' style='100%;'>
            ";

    $footer = "
                <div style='text-align: center; font-size: 10px; border-top: 1px solid #000; padding-top: 5px;'>
                    " . TEXT_PDF_FOOTER_PAGE . " {PAGENO} " . TEXT_PDF_FOOTER_OF . " {nbpg} - " . TEXT_PDF_FOOTER_GENERATED . " " . $today . "
                </div>
            ";

    // Main HTML content
    $html = "
            <h1>
                " . TEXT_PDF_TITLE . "
                <span style='color:" . $type_color_border . ";'>" . $text_eq_type . "</span>
            </h1>

            <table class='fields' style='margin-top:8px;'>
                <tr>
                    <td class='label'>" . TEXT_PDF_EDITED_ON . "</td>
                    <td class='value'>" . $today . "</td>
                </tr>
                <tr>
                    <td class='label'>" . TEXT_PDF_EDITED_BY . "</td>
                    <td class='value'>" . $prenom_user . " " . $nom_user . " - " . $info_user . "</td>
                </tr>
            </table>

            <table class='identity'>
                <tr>
                    <td style='width:60%;padding:0 16px 0 0;font-size:11px;color:#555;'>" . TEXT_PDF_STATION_NAME . "</td>
                    <td style='padding:0 16px 0 0;font-size:11px;color:#555;'>" . TEXT_PDF_STATION_CODE . "</td>
                </tr>
                <tr>
                    <td style='width:60%;padding:2px 16px 0 0;font-size:22px;font-weight:bold;color:#000;'>" . $nom_station . "</td>
                    <td style='padding:2px 16px 0 0;font-size:22px;font-weight:bold;color:#000;'>" . $code_station . "</td>
                </tr>
            </table>"
            ;

            $html .= "
            <table class='fields' style='margin-top:14px;'>
                <tr>
                    <td class='label'>" . TEXT_PDF_SHORT_NAME . "</td>
                    <td class='value'>" . $nom_court . "</td>
                    <td class='label2'>" . TEXT_PDF_NUM_IRH . "</td>
                    <td class='value'>" . $num_irh . "</td>
                </tr>
                <tr>
                    <td class='label'>" . TEXT_PDF_STATUS . "</td>
                    <td class='value'>" . $text_active_station . "</td>
                    <td class='label2'>" . TEXT_PDF_MONITORING . "</td>
                    <td class='value'>" . $text_suivi_station . "</td>
                </tr>
                <tr>
                    <td class='label'>" . TEXT_PDF_EQUIPMENT . "</td>
                    <td class='value'>" . $text_armee_station . "</td>
                    <td class='label2'></td>
                    <td class='value'></td>
                </tr>
            </table>

            <h2>
                " . TEXT_PDF_GEO_LOCATION . "
            </h2>

            <table class='fields'>
                <tr>
                    <td class='label'>" . TEXT_PDF_TERRITORY . "</td>
                    <td class='value'>" . $territoire_nom . "</td>
                    <td class='label2'>" . $territoire_region . "</td>
                    <td class='value'>" . $text_region . "</td>
                </tr>
                <tr>
                    <td class='label'>" . TEXT_PDF_COMMUNE . "</td>
                    <td class='value'>" . $text_commune . "</td>
                    <td class='label2'>" . TEXT_PDF_SITE . "</td>
                    <td class='value'>" . $site_station . "</td>
                </tr>
                <tr>
                    <td class='label'>" . TEXT_PDF_HYDRO_REGION . "</td>
                    <td class='value'>" . $text_regionhydro . "</td>
                    <td class='label2'>" . TEXT_PDF_RIVER . "</td>
                    <td class='value'>" . $riviere_station . "</td>
                </tr>
                <tr>
                    <td class='label'>" . TEXT_PDF_ALTITUDE . "</td>
                    <td class='value'>" . $altitude_station . "</td>
                    <td class='label2'></td>
                    <td class='value'></td>
                </tr>
            </table>

            <table class='data' style='width:60%;margin-top:14px;'>
                <tr>
                    <th colspan='2'>" . TEXT_PDF_GEO_COORDS . "</th>
                </tr>
                <tr class='row1'>
                    <td style='width:45%;'>" . TEXT_PDF_LONGITUDE . "</td>
                    <td class='bold'>" . $longitude_station . "</td>
                </tr>
                <tr class='row2'>
                    <td>" . TEXT_PDF_LATITUDE . "</td>
                    <td class='bold'>" . $latitude_station . "</td>
                </tr>
                <tr class='row1'>
                    <td>" . TEXT_PDF_UTM_X . "</td>
                    <td class='bold'>" . $utm_station_x . "</td>
                </tr>
                <tr class='row2'>
                    <td>" . TEXT_PDF_UTM_Y . "</td>
                    <td class='bold'>" . $utm_station_y . "</td>
                </tr>
                <tr class='row1'>
                    <td>" . TEXT_PDF_LAMBERT_X . "</td>
                    <td class='bold'>" . $lamb_station_x . "</td>
                </tr>
                <tr class='row2'>
                    <td>" . TEXT_PDF_LAMBERT_Y . "</td>
                    <td class='bold'>" . $lamb_station_y . "</td>
                </tr>
            </table>

            <h2>
                " . TEXT_PDF_INFORMATION . "
            </h2>

            <table class='fields'>
                <tr>
                    <td class='label'>" . TEXT_PDF_INSTALL_DATE . "</td>
                    <td class='value'>" . $date_installation_station . "</td>
                    <td class='label2'>" . TEXT_PDF_CLOSE_DATE . "</td>
                    <td class='value'>" . $date_fermeture_station . "</td>
                </tr>
            </table>

            <table class='fields' style='margin-top:8px;'>
                <tr>
                    <td class='label' style='vertical-align:top;'>" . TEXT_PDF_DESCRIPTION . "</td>
                    <td class='value' style='font-weight:normal;'>
                        <div class='description-text'>" . nl2br(htmlspecialchars($description_station, ENT_QUOTES, 'UTF-8')) . "</div>
                    </td>
                </tr>
            </table>

        <pagebreak /> <!-- Page break -->

        " . $html_photos_station;


    // Activity reports section
    if (isset($ra_array))
    {
        $html .= "
            <h2>
                " . TEXT_PDF_RA_TITLE . "
            </h2>

            <div style='margin-top:8px;'>
                <table class='data'>
                    <tr>
                        <th style='width:60px;'>" . TEXT_PDF_RA_DATE . "</th>
                        <th>" . TEXT_PDF_RA_OBS . "</th>
                        <th>" . TEXT_PDF_RA_TODO . "</th>
                        <th style='width:130px;'>" . TEXT_PDF_RA_AGENTS . "</th>
                    </tr>
                    ";

                    $ra_row = 0;
                    foreach ($ra_array as $key => $value)
                    {
                        $ra_row++;
                        $ra_class = ($ra_row % 2 == 0) ? 'row2' : 'row1';
                        $html .= "
                        <tr class='" . $ra_class . "'>
                            <td>" . $value['date_ra'] . "</td>
                            <td>" . $value['ra_obs'] . "</td>
                            <td>" . $value['ra_futur'] . "</td>
                            <td>" . $value['agents_complement'] . "</td>
                        </tr>
                        ";
                    }

        $html .= "
                </table>
            </div>

        <pagebreak /> <!-- Page break -->";
    }

    // Available data section
    $html .= "
            <h2>
                " . TEXT_PDF_DATA_AVAILABLE . "
            </h2>

            <table class='data-layout'>
                <tr>
                    <td class='col-data'>
                        " . $html_tab_data_station . "
                    </td>
                    <td class='col-cal'>
                        " . $html_tab_code_cal . "
                    </td>
                </tr>
            </table>

            <div class='graph-wrap'>
                <img src='" . $graphImage . "' />
            </div>";


    // Build output filename and path
    $fileName = "Station_" . $code_station . "_" . nettoyerNomFichier($nom_station) . ".pdf";
    $filePath = $_SERVER['DOCUMENT_ROOT'] . "/" . DIR_WS_PDF . $fileName;

    // Initialise mPDF
    $mpdf = new \Mpdf\Mpdf([
        'margin_left'   => 10,
        'margin_right'  => 10,
        'margin_top'    => 30,
        'margin_bottom' => 10
    ]);

    // Load and apply CSS stylesheet
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

    // Encode response as JSON
    $jsonResponse = json_encode($responseData);

    // Send response to the client
    if (ob_get_length() !== false) { ob_clean(); }
    echo $jsonResponse;

} catch (\Mpdf\MpdfException $e) {
    if (ob_get_length() !== false) { ob_clean(); }
    echo json_encode([
        'status'   => 'error',
        'msg_info' => $e->getMessage()
    ]);
}
?>