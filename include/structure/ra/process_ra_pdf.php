<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Field Report (RA) PDF sheet generator
Generates an exhaustive PDF sheet for one or several field reports.
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

// Some station / commune / user names are stored double-encoded in the
// database (e.g. "Coul&amp;eacute;e" for "Coulée"). A single
// html_entity_decode() only peels one layer, leaving "&eacute;" visible in
// the PDF. Decode repeatedly until the string is stable so every layer is
// removed, whatever the encoding depth. mPDF renders UTF-8 natively, so the
// goal is plain accented UTF-8, no entities.
function ra_decode($s) {
    $s = (string)$s;
    for ($i = 0; $i < 5; $i++) {
        $d = html_entity_decode($s, ENT_QUOTES, 'UTF-8');
        if ($d === $s) { break; }
        $s = $d;
    }
    return $s;
}

// Retrieve JSON data sent from the AJAX request
$jsonDataInfo = file_get_contents('php://input');
$dataInfo = json_decode($jsonDataInfo, true);

// Extract values
$territoire_id  = $dataInfo['territoire_id'] ?? 0;
$timezone_php   = $dataInfo['timezone_php'] ?? 'Pacific/Tahiti';
$id_user        = $dataInfo['id_user'] ?? 0;
$prenom_user    = $dataInfo['prenom_user'] ?? '';
$nom_user       = $dataInfo['nom_user'] ?? '';
$info_user      = $dataInfo['info_user'] ?? '';

// One or several RA ids. Accept either a single id_ra or a comma list / array.
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

// Sanitize to a clean list of positive integers
$list_id_ra = array_values(array_filter(array_map('intval', $list_id_ra), function ($v) {
    return $v > 0;
}));

// Hard cap on the number of detailed sheets (see front-end guard).
// Each sheet is a full page; beyond this the generation gets too heavy.
define('RA_PDF_MAX_SHEETS', 50);
if (count($list_id_ra) > RA_PDF_MAX_SHEETS) {
    if (ob_get_length() !== false) { ob_clean(); }
    echo json_encode([
        'status'   => 'error',
        'msg_info' => TEXT_RA_PDF_TOO_MANY
    ]);
    exit;
}

if (empty($list_id_ra)) {
    if (ob_get_length() !== false) { ob_clean(); }
    echo json_encode([
        'status'   => 'error',
        'msg_info' => TEXT_RA_PDF_NO_SELECTION
    ]);
    exit;
}

// Time management
date_default_timezone_set($timezone_php);
$today = date('d-m-Y');

// =============================================================================
// REFERENCE DATA (loaded once, shared by all sheets)
// =============================================================================

// User list
$user_list_array = [];
$sql_user_list = "SELECT DISTINCT id, id_statut, login, nom, prenom FROM " . TABLE_USER;
$user_list_query = tep_db_query($sql_link, $sql_user_list);
while ($user_list = tep_db_fetch_array($user_list_query)) {
    $user_list_array[$user_list['id']] = array(
        'login'  => ra_decode($user_list['login'] ?? ''),
        'nom'    => ucfirst(strtolower(ra_decode($user_list['nom'] ?? ''))),
        'prenom' => ucfirst(strtolower(ra_decode($user_list['prenom'] ?? '')))
    );
}

// Commune list
$commune_array = [];
$sql_commune = "SELECT DISTINCT c.id_commune, c.nom_commune
                FROM " . TABLE_COMMUNE . " c
                JOIN " . TABLE_REGION . " r ON c.id_region = r.id_region
                WHERE r.id_territoire = " . $territoire_id . "
                ORDER BY c.nom_commune ASC";
$commune_query = tep_db_query($sql_link, $sql_commune);
while ($commune = tep_db_fetch_array($commune_query)) {
    $commune_array[$commune['id_commune']] = ra_decode($commune['nom_commune'] ?? '');
}

// Station list
$station_all_array = [];
$sql_station_all = "SELECT DISTINCT id_station, nom_station, code_station, station_type, id_commune
                    FROM " . TABLE_STATION . "
                    ORDER BY nom_station ASC";
$station_all_query = tep_db_query($sql_link, $sql_station_all);
while ($station_all = tep_db_fetch_array($station_all_query)) {
    $station_all_array[$station_all['id_station']] = array(
        'code_station' => $station_all['code_station'],
        'nom_station'  => ra_decode($station_all['nom_station'] ?? ''),
        'station_type' => $station_all['station_type'],
        'id_commune'   => $station_all['id_commune']
    );
}

// Equipment types (data type: rainfall, streamflow, piezometry, ...)
$eq_type_array = [];
$sql_eq_type = "SELECT DISTINCT id_eq_type, nom_eq_type, unite_eq_type, type_color_border
                FROM " . TABLE_EQ_TYPE . "
                WHERE active_eq_type = 1
                ORDER BY order_eq_type ASC";
$eq_type_query = tep_db_query($sql_link, $sql_eq_type);
while ($eq_type_tab = tep_db_fetch_array($eq_type_query)) {
    $eq_type_array[$eq_type_tab['id_eq_type']] = array(
        'nom_eq_type'       => $eq_type_tab['nom_eq_type'],
        'unite_eq_type'     => $eq_type_tab['unite_eq_type'],
        'type_color_border' => $eq_type_tab['type_color_border']
    );
}

// =============================================================================
// SMALL HELPERS
// =============================================================================

// Display a value or a dash when empty (never shows 00:00:00 times)
function ra_val($v) {
    $v = trim((string)$v);
    if ($v === '' || $v === '00:00:00') { return '-'; }
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}

// Yes / No from a boolean-ish flag
function ra_yesno($v) {
    return ((int)$v > 0) ? TEXT_RA_PDF_YES : TEXT_RA_PDF_NO;
}

// One "label : value" field rendered as a table row
function ra_field($label, $value) {
    return "<tr>
                <td class='ra-label'>" . $label . "</td>
                <td class='ra-value'>" . $value . "</td>
            </tr>";
}

// A titled mini-section: colored heading + its own label/value grid.
// Used inside the 3-column layout.
function ra_section($title, $rows) {
    return "
        <div class='ra-sec'>
            <table class='ra-sec-title'>
                <tr>
                    <td class='ra-sec-bar'></td>
                    <td class='ra-sec-txt'>" . $title . "</td>
                </tr>
            </table>
            <table class='ra-fields ra-fields-col'>" . $rows . "</table>
        </div>";
}

// A full-width section title (same bar+text style), for Observations / To do.
function ra_fulltitle($title) {
    return "
        <table class='ra-sec-title' style='margin-top:18px;'>
            <tr>
                <td class='ra-sec-bar'></td>
                <td class='ra-sec-txt'>" . $title . "</td>
            </tr>
        </table>";
}

// A row of up to 3 sections laid out side by side (mPDF-friendly table).
function ra_section_row($c1, $c2, $c3) {
    return "
        <table class='ra-row'>
            <tr>
                <td class='ra-col'>" . $c1 . "</td>
                <td class='ra-col'>" . $c2 . "</td>
                <td class='ra-col'>" . $c3 . "</td>
            </tr>
        </table>";
}

// =============================================================================
// BUILD ONE SHEET (per RA)
// =============================================================================

// Returns the HTML for a single field report, dispatched by data type.
function build_ra_sheet($RA_tab, $eq_type_array, $station_all_array, $commune_array, $user_list_array, $today, $prenom_user, $nom_user, $info_user) {

    $id_ra     = $RA_tab['id_ra'];
    $type_data = (int)$RA_tab['id_eq_type']; // 1: rainfall, 5: piezo, 11: hydro

    // Station info
    $id_station   = $RA_tab['id_station'];
    $code_station = '';
    $nom_station  = '';
    $id_commune   = 0;
    if (isset($station_all_array[$id_station])) {
        $code_station = $station_all_array[$id_station]['code_station'];
        $nom_station  = $station_all_array[$id_station]['nom_station'];
        $id_commune   = $station_all_array[$id_station]['id_commune'];
    }
    $nom_commune = isset($commune_array[$id_commune]) ? $commune_array[$id_commune] : '-';

    // Data type label + colour
    $nom_data   = isset($eq_type_array[$type_data]) ? $eq_type_array[$type_data]['nom_eq_type'] : '';
    $type_color = isset($eq_type_array[$type_data]) ? $eq_type_array[$type_data]['type_color_border'] : '#000';
    $unite_ra   = isset($eq_type_array[$type_data]) ? $eq_type_array[$type_data]['unite_eq_type'] : '';

    // Date / time
    $date_heure_ra_tab = explode(' ', $RA_tab['date_heure_ra']);
    $date_ra  = dateus_fr($date_heure_ra_tab[0]);
    $heure_ra = isset($date_heure_ra_tab[1]) ? $date_heure_ra_tab[1] : '';
    if ($heure_ra == '00:00:00') { $heure_ra = ''; }

    // Entry agent
    $id_agent_user = $RA_tab['id_agent_user'];
    $agent_nom = '-';
    if (isset($user_list_array[$id_agent_user])) {
        $agent_nom = $user_list_array[$id_agent_user]['prenom'] . ' ' . $user_list_array[$id_agent_user]['nom'];
    }

    // Status
    $etat_ra = (int)$RA_tab['etat_ra'];
    $etat_label = ($etat_ra > 0) ? TEXT_RA_PDF_STATUS_VALID : TEXT_RA_PDF_STATUS_FIELD;

    // Validation badge (green = validated, red = not validated)
    if ($etat_ra > 0) {
        $badge_color = '#2e7d32';
        $badge_text  = TEXT_RA_PDF_BADGE_VALID;
    } else {
        $badge_color = '#c62828';
        $badge_text  = TEXT_RA_PDF_BADGE_NOTVALID;
    }
    $status_badge = "<span class='ra-badge' style='color:" . $badge_color . ";'>&#9679;</span>"
                  . "<span class='ra-badge-txt'>" . $badge_text . "</span>";

    // ---- Common header ----
    // Two columns. Left: station + code (large) then commune/date/time/status.
    // Right: edition info. Each row is label (grey, right-aligned) + value (bold).
    $nom_station_h = htmlspecialchars($nom_station, ENT_QUOTES, 'UTF-8');
    $code_station_h = htmlspecialchars($code_station, ENT_QUOTES, 'UTF-8');

    $html = "
        <table class='ra-titlebar'>
            <tr>
                <td class='ra-title-cell'>
                    <span class='ra-title'>" . TEXT_RA_PDF_TITLE . "</span>
                    <span class='ra-title-type' style='color:" . $type_color . ";'>" . $nom_data . "</span>
                </td>
                <td class='ra-badge-cell'>" . $status_badge . "</td>
            </tr>
        </table>

        <table class='ra-head'>
            <tr>
                <td class='ra-head-left'>

                    <table class='ra-grid ra-grid-id'>
                        <tr>
                            <td class='ra-glabel-big'>" . TEXT_RA_PDF_STATION . "</td>
                            <td class='ra-gbig'>" . $nom_station_h . "</td>
                        </tr>
                        <tr>
                            <td class='ra-glabel-big'>" . TEXT_RA_PDF_STATION_CODE . "</td>
                            <td class='ra-gbig'>" . $code_station_h . "</td>
                        </tr>
                    </table>

                    <table class='ra-grid' style='margin-top:12px;'>
                        <tr><td class='ra-glabel'>" . TEXT_RA_PDF_COMMUNE . "</td><td class='ra-gval'>" . ra_val($nom_commune) . "</td></tr>
                        <tr><td class='ra-glabel'>" . TEXT_RA_PDF_DATE . "</td><td class='ra-gval'>" . ra_val($date_ra) . "</td></tr>
                        <tr><td class='ra-glabel'>" . TEXT_RA_PDF_TIME . "</td><td class='ra-gval'>" . ra_val($heure_ra) . "</td></tr>
                        <tr><td class='ra-glabel'>" . TEXT_RA_PDF_STATUS . "</td><td class='ra-gval'>" . $etat_label . "</td></tr>
                    </table>

                </td>
                <td class='ra-head-right'>
                    <table class='ra-grid'>
                        <tr><td class='ra-glabel'>" . TEXT_RA_PDF_EDITED_ON . "</td><td class='ra-gval'>" . ra_val($today) . "</td></tr>
                        <tr><td class='ra-glabel'>" . TEXT_RA_PDF_ENTRY_AGENT . "</td><td class='ra-gval'>" . ra_val($agent_nom) . "</td></tr>
                    </table>
                </td>
            </tr>
        </table>
    ";

    // ---- Common footer fields: observations / future actions ----
    $ra_obs    = nettoyer_et_echapper($RA_tab['ra_obs']);
    $ra_futur  = nettoyer_et_echapper($RA_tab['ra_futur']);
    $agents    = nettoyer_et_echapper($RA_tab['agents_complement']);

    // ---- Type-specific body (participants placed inside the body's last row) ----
    if ($type_data == 1) {
        $html .= build_ra_body_pluvio($RA_tab, $unite_ra, $agents);
    } elseif ($type_data == 11) {
        $html .= build_ra_body_hydro($RA_tab, $unite_ra, $agents);
    } elseif ($type_data == 5) {
        $html .= build_ra_body_piezo($RA_tab, $unite_ra, $agents);
    }

    $html .= "
        " . ra_fulltitle(TEXT_RA_PDF_OBSERVATIONS) . "
        <div class='ra-text'>" . nl2br(htmlspecialchars($ra_obs, ENT_QUOTES, 'UTF-8')) . "</div>

        " . ra_fulltitle(TEXT_RA_PDF_FUTURE_ACTIONS) . "
        <div class='ra-text'>" . nl2br(htmlspecialchars($ra_futur, ENT_QUOTES, 'UTF-8')) . "</div>
    ";

    return $html;
}

// =============================================================================
// RAINFALL (pluvio) BODY
// =============================================================================

function build_ra_body_pluvio($RA_tab, $unite_ra, $agents = '') {

    // Reading
    $name_file_data = nettoyer_et_echapper($RA_tab['name_file_data'] ?? '');

    // Device
    $type_appareil    = ra_val($RA_tab['type_appareil'] ?? '');
    $num_appareil     = ra_val($RA_tab['num_appareil'] ?? '');
    $heure_appareil   = ra_val($RA_tab['heure_appareil'] ?? '');

    // Device state
    $plu_nb_basculement = ra_val($RA_tab['plu_nb_basculement'] ?? '');
    $nb_octet           = ra_val($RA_tab['nb_octet'] ?? '');
    $num_batterie       = ra_val($RA_tab['num_batterie'] ?? '');
    $tension_batterie   = ra_val($RA_tab['tension_batterie'] ?? '');

    // Totalizer
    $plu_tot_type       = ra_val($RA_tab['plu_tot_type'] ?? '');
    $plu_tot_first      = ra_val($RA_tab['plu_tot_first'] ?? '');
    $plu_tot_last       = ra_val($RA_tab['plu_tot_last'] ?? '');
    $plu_tot_heure_basc = ra_val($RA_tab['plu_tot_heure_basc'] ?? '');

    // Control
    $plu_cumul_tot          = ra_val($RA_tab['plu_cumul_tot'] ?? '');
    $plu_cumul_plu          = ra_val($RA_tab['plu_cumul_plu'] ?? '');
    $plu_diff_tot_plu       = ra_val($RA_tab['plu_diff_tot_plu'] ?? '');
    $plu_recalage_heure_plu = ra_val($RA_tab['plu_recalage_heure_plu'] ?? '');
    $plu_test_auget         = ra_val($RA_tab['plu_test_auget'] ?? '');

    // Maintenance check flags
    $check_bouchage     = ra_yesno($RA_tab['plu_ra_bouchage'] ?? 0);
    $check_huile        = ra_yesno($RA_tab['plu_ra_huile_tot'] ?? 0);
    $check_debrouss     = ra_yesno($RA_tab['ra_debroussaillage'] ?? 0);
    $check_eaubat       = ra_yesno($RA_tab['ra_eau_batterie'] ?? 0);
    $check_transfert    = ra_yesno($RA_tab['ra_transfert_data'] ?? 0);
    $check_deletememory = ra_yesno($RA_tab['ra_delete_memory'] ?? 0);

    $u = $unite_ra;

    // Each section is a titled mini-block; sections are laid out 3 per row.
    $s_reading = ra_section(TEXT_RA_PDF_SECTION_READING_DEVICE,
        ra_field(TEXT_READING_FILE_NAME, ra_val($name_file_data))
      . ra_field(TEXT_DEVICE . ' - ' . TEXT_TYPE, $type_appareil)
      . ra_field(TEXT_DEVICE . ' - ' . TEXT_NUMBER, $num_appareil)
      . ra_field(TEXT_DEVICE . ' - ' . TEXT_TIME, $heure_appareil)
    );

    $s_state = ra_section(TEXT_RA_PDF_SECTION_DEVICE_STATE,
        ra_field(TEXT_NB_TIPPINGS, $plu_nb_basculement)
      . ra_field(TEXT_NB_BYTES, $nb_octet)
      . ra_field(TEXT_BATTERY_NUM, $num_batterie)
      . ra_field(TEXT_BATTERY_VOLTAGE, $tension_batterie)
    );

    $s_totalizer = ra_section(TEXT_RA_PDF_SECTION_TOTALIZER,
        ra_field(TEXT_TOTAL_TYPE, $plu_tot_type)
      . ra_field(TEXT_CUMUL_ARRIVAL, $plu_tot_first . ' ' . $u)
      . ra_field(TEXT_CUMUL_DEPARTURE, $plu_tot_last . ' ' . $u)
      . ra_field(TEXT_TIPPING_TIME, $plu_tot_heure_basc)
    );

    $s_control = ra_section(TEXT_RA_PDF_SECTION_CONTROL,
        ra_field(TEXT_CUMUL_TOTAL, $plu_cumul_tot . ' ' . $u)
      . ra_field(TEXT_CUMUL_RAIN, $plu_cumul_plu . ' ' . $u)
      . ra_field(TEXT_TOTAL_RAIN, $plu_diff_tot_plu . ' ' . $u)
      . ra_field(TEXT_TIME_ADJUSTMENT, $plu_recalage_heure_plu)
      . ra_field(TEXT_AUGET_TEST, $plu_test_auget)
    );

    $s_maintenance = ra_section(TEXT_RA_PDF_SECTION_MAINTENANCE,
        ra_field(TEXT_RA_PDF_CHK_BOUCHAGE, $check_bouchage)
      . ra_field(TEXT_RA_PDF_CHK_HUILE, $check_huile)
      . ra_field(TEXT_RA_PDF_CHK_DEBROUSS, $check_debrouss)
      . ra_field(TEXT_RA_PDF_CHK_EAUBAT, $check_eaubat)
      . ra_field(TEXT_RA_PDF_CHK_TRANSFERT, $check_transfert)
      . ra_field(TEXT_RA_PDF_CHK_DELETEMEM, $check_deletememory)
    );

    // Two rows: 3 sections then 2 sections (empty 3rd cell to keep widths).
    $s_participants = ra_section(TEXT_RA_PDF_PARTICIPANTS,
        "<tr><td class='ra-value' style='white-space:normal;font-weight:normal;'>" . nl2br(htmlspecialchars($agents, ENT_QUOTES, 'UTF-8')) . "</td></tr>"
    );

    $html  = ra_section_row($s_reading, $s_state, $s_totalizer);
    $html .= ra_section_row($s_control, $s_maintenance, $s_participants);

    return $html;
}

// =============================================================================
// SURFACE WATER (hydro) BODY
// =============================================================================
// Territory-conditional fields (if INIT_T == 'NC'/'PF') are intentionally
// excluded, as well as the old cassette equipment block.

function build_ra_body_hydro($RA_tab, $unite_ra, $agents = '') {

    // Reading / device
    $type_appareil   = ra_val($RA_tab['type_appareil'] ?? '');
    $heure_appareil  = ra_val($RA_tab['heure_appareil'] ?? '');

    // Device state
    $nb_octet         = ra_val($RA_tab['nb_octet'] ?? '');
    $tension_batterie = ra_val($RA_tab['tension_batterie'] ?? '');

    // Probe info
    $hydro_num_sonde = ra_val($RA_tab['hydro_num_sonde'] ?? '');

    // Water level
    $hydro_heure_cote  = ra_val($RA_tab['hydro_heure_cote'] ?? '');
    $hydro_h_sonde     = ra_val($RA_tab['hydro_h_sonde'] ?? '');
    $hydro_h_echelle_1 = ra_val($RA_tab['hydro_h_echelle_1'] ?? '');
    $hydro_h_echelle_2 = ra_val($RA_tab['hydro_h_echelle_2'] ?? '');

    // Hydrology control
    $hydro_recalage_sonde = ra_val($RA_tab['hydro_recalage_sonde'] ?? '');
    $hydro_purge_sonde    = ra_yesno($RA_tab['hydro_purge_sonde'] ?? 0);

    // Maintenance flags
    $check_jaugeage     = ra_yesno($RA_tab['hydro_ra_jaugeage'] ?? 0);
    $check_debrouss     = ra_yesno($RA_tab['ra_debroussaillage'] ?? 0);
    $check_eaubat       = ra_yesno($RA_tab['ra_eau_batterie'] ?? 0);
    $check_transfert    = ra_yesno($RA_tab['ra_transfert_data'] ?? 0);
    $check_deletememory = ra_yesno($RA_tab['ra_delete_memory'] ?? 0);

    $u = $unite_ra;

    $s_reading = ra_section(TEXT_READING,
        ra_field(TEXT_DEVICE . ' - ' . TEXT_TYPE, $type_appareil)
      . ra_field(TEXT_DEVICE . ' - ' . TEXT_TIME, $heure_appareil)
    );

    $s_state = ra_section(TEXT_DEVICE_STATE,
        ra_field(TEXT_NB_BYTES, $nb_octet)
      . ra_field(TEXT_BATTERY_VOLTAGE, $tension_batterie)
    );

    $s_probe = ra_section(TEXT_PROBE_INFO,
        ra_field(TEXT_NUMBER, $hydro_num_sonde)
    );

    $s_level = ra_section(TEXT_WATER_LEVEL,
        ra_field(TEXT_TIME, $hydro_heure_cote)
      . ra_field(TEXT_PROBE_HEIGHT, $hydro_h_sonde)
      . ra_field(TEXT_SCALE_HEIGHT, $hydro_h_echelle_1)
      . ra_field(TEXT_SCALE_HEIGHT_2, $hydro_h_echelle_2)
    );

    $s_control = ra_section(TEXT_HYDRO_CONTROL,
        ra_field(TEXT_PROBE_ADJUSTMENT, $hydro_recalage_sonde)
      . ra_field(TEXT_DATA_PURGE, $hydro_purge_sonde)
    );

    $s_maintenance = ra_section(TEXT_RA_PDF_SECTION_MAINTENANCE,
        ra_field(TEXT_GAUGING, $check_jaugeage)
      . ra_field(TEXT_RA_PDF_CHK_DEBROUSS, $check_debrouss)
      . ra_field(TEXT_RA_PDF_CHK_EAUBAT, $check_eaubat)
      . ra_field(TEXT_RA_PDF_CHK_TRANSFERT, $check_transfert)
      . ra_field(TEXT_RA_PDF_CHK_DELETEMEM, $check_deletememory)
    );

    $s_participants = ra_section(TEXT_RA_PDF_PARTICIPANTS,
        "<tr><td class='ra-value' style='white-space:normal;font-weight:normal;'>" . nl2br(htmlspecialchars($agents, ENT_QUOTES, 'UTF-8')) . "</td></tr>"
    );

    $html  = ra_section_row($s_reading, $s_state, $s_probe);
    $html .= ra_section_row($s_level, $s_control, $s_maintenance);
    $html .= ra_section_row($s_participants, '', '');

    return $html;
}

// =============================================================================
// GROUNDWATER (piezo) BODY
// =============================================================================
// Territory-conditional fields (if INIT_T == 'NC'/'PF') are excluded:
// GPS precision, fixed-probe device time, probe time adjustment.

function build_ra_body_piezo($RA_tab, $unite_ra, $agents = '') {

    // Measurement position
    $piezo_x_terrain     = ra_val($RA_tab['piezo_x_terrain'] ?? '');
    $piezo_y_terrain     = ra_val($RA_tab['piezo_y_terrain'] ?? '');
    $piezo_systeme_coord = ra_val($RA_tab['piezo_systeme_coord'] ?? '');

    // Fixed probe characteristics + measurement
    $type_appareil        = ra_val($RA_tab['type_appareil'] ?? '');
    $num_appareil         = ra_val($RA_tab['num_appareil'] ?? '');
    $piezo_prof_toitnappe = ra_val($RA_tab['piezo_prof_toitnappe'] ?? '');
    $piezo_conductivite   = ra_val($RA_tab['piezo_conductivite'] ?? '');
    $piezo_temperature    = ra_val($RA_tab['piezo_temperature'] ?? '');

    // Manual probe characteristics + measurement
    $piezo_instrument     = ra_val($RA_tab['piezo_instrument'] ?? '');
    $piezo_num_instrument = ra_val($RA_tab['piezo_num_instrument'] ?? '');
    $piezo_nature_repere  = ra_val($RA_tab['piezo_nature_repere'] ?? '');
    $piezo_prof_totale    = ra_val($RA_tab['piezo_prof_totale'] ?? '');

    // Adjustment + device state
    $piezo_recalage_diff  = ra_val($RA_tab['piezo_recalage_diff'] ?? '');
    $piezo_recalage_sonde = ra_val($RA_tab['piezo_recalage_sonde'] ?? '');
    $nb_octet             = ra_val($RA_tab['nb_octet'] ?? '');
    $tension_batterie     = ra_val($RA_tab['tension_batterie'] ?? '');

    // Maintenance / context flags
    $check_pompage_encours = ra_yesno($RA_tab['piezo_pompage_encours'] ?? 0);
    $check_pompage_proche  = ra_yesno($RA_tab['piezo_pompage_proche'] ?? 0);
    $check_pluie_crue      = ra_yesno($RA_tab['piezo_pluie_crue'] ?? 0);
    $check_temps_sec       = ra_yesno($RA_tab['piezo_temps_sec'] ?? 0);
    $check_deletememory    = ra_yesno($RA_tab['ra_delete_memory'] ?? 0);

    $s_position = ra_section(TEXT_MEASUREMENT_POSITION,
        ra_field(TEXT_X_GPS_POSITION, $piezo_x_terrain)
      . ra_field(TEXT_Y_GPS_POSITION, $piezo_y_terrain)
      . ra_field(TEXT_COORD_SYSTEM, $piezo_systeme_coord)
    );

    $s_fixed = ra_section(TEXT_FIXED_PROBE_CHARACTERISTICS,
        ra_field(TEXT_TYPE, $type_appareil)
      . ra_field(TEXT_NUMBER, $num_appareil)
      . ra_field(TEXT_WATER_TABLE_DEPTH, $piezo_prof_toitnappe)
      . ra_field(TEXT_CONDUCTIVITY, $piezo_conductivite)
      . ra_field(TEXT_TEMPERATURE, $piezo_temperature)
    );

    $s_manual = ra_section(TEXT_MANUAL_PROBE_CHARACTERISTICS,
        ra_field(TEXT_TYPE, $piezo_instrument)
      . ra_field(TEXT_NUMBER, $piezo_num_instrument)
      . ra_field(TEXT_MARKER_NATURE, $piezo_nature_repere)
      . ra_field(TEXT_TOTAL_DEPTH, $piezo_prof_totale)
    );

    $s_adjust = ra_section(TEXT_FIXED_PROBE_ADJUSTMENT,
        ra_field(TEXT_DIFF_MANUAL_FIXED, $piezo_recalage_diff)
      . ra_field(TEXT_PROBE_ADJUSTMENT, $piezo_recalage_sonde)
    );

    $s_state = ra_section(TEXT_DEVICE_STATE_FIXED_PROBE,
        ra_field(TEXT_NB_DATA, $nb_octet)
      . ra_field(TEXT_BATTERY_PERCENT, $tension_batterie)
    );

    $s_context = ra_section(TEXT_RA_PDF_SECTION_CONTEXT,
        ra_field(TEXT_PUMPING_IN_PROGRESS, $check_pompage_encours)
      . ra_field(TEXT_NEARBY_PUMPING, $check_pompage_proche)
      . ra_field(TEXT_RAIN_FLOOD, $check_pluie_crue)
      . ra_field(TEXT_DRY_DAY, $check_temps_sec)
      . ra_field(TEXT_RA_PDF_CHK_DELETEMEM, $check_deletememory)
    );

    $s_participants = ra_section(TEXT_RA_PDF_PARTICIPANTS,
        "<tr><td class='ra-value' style='white-space:normal;font-weight:normal;'>" . nl2br(htmlspecialchars($agents, ENT_QUOTES, 'UTF-8')) . "</td></tr>"
    );

    $html  = ra_section_row($s_position, $s_fixed, $s_manual);
    $html .= ra_section_row($s_adjust, $s_state, $s_context);
    $html .= ra_section_row($s_participants, '', '');

    return $html;
}

// =============================================================================
// MAIN : fetch each RA and assemble the document
// =============================================================================

try {

    // PDF header and footer (same look as the station sheet)
    $header = "
                <img src='../../../" . DIR_WS_IMG_PDF . "bando.png' style='100%;'>
            ";

    $footer = "
                <div style='text-align: center; font-size: 10px; border-top: 1px solid #000; padding-top: 5px;'>
                    " . TEXT_PDF_FOOTER_PAGE . " {PAGENO} " . TEXT_PDF_FOOTER_OF . " {nbpg} - " . TEXT_PDF_FOOTER_GENERATED . " " . $today . "
                </div>
            ";

    // Columns selected for the detailed sheet
    $cols = "ra.id_ra, ra.id_agent_user, ra.id_station, ra.date_heure_ra, ra.id_eq_type, ra.etat_ra,
             ra.type_appareil, ra.num_appareil, ra.heure_appareil, ra.plu_taille_auget,
             ra.plu_tot_type, ra.plu_tot_first, ra.plu_tot_last, ra.plu_tot_heure_basc,
             ra.plu_cumul_tot, ra.plu_cumul_plu, ra.plu_diff_tot_plu, ra.plu_recalage_heure_plu,
             ra.plu_test_auget, ra.plu_nb_basculement,
             ra.nb_octet, ra.num_batterie, ra.tension_batterie,
             ra.plu_ra_bouchage, ra.plu_ra_huile_tot, ra.ra_debroussaillage, ra.ra_eau_batterie,
             ra.ra_transfert_data, ra.ra_delete_memory,
             ra.hydro_num_sonde, ra.hydro_heure_cote, ra.hydro_h_sonde, ra.hydro_h_echelle_1, ra.hydro_h_echelle_2,
             ra.hydro_recalage_sonde, ra.hydro_purge_sonde, ra.hydro_ra_jaugeage,
             ra.piezo_x_terrain, ra.piezo_y_terrain, ra.piezo_systeme_coord,
             ra.piezo_prof_toitnappe, ra.piezo_conductivite, ra.piezo_temperature,
             ra.piezo_instrument, ra.piezo_num_instrument, ra.piezo_nature_repere, ra.piezo_prof_totale,
             ra.piezo_recalage_diff, ra.piezo_recalage_sonde,
             ra.piezo_pompage_encours, ra.piezo_pompage_proche, ra.piezo_pluie_crue, ra.piezo_temps_sec,
             ra.ra_obs, ra.ra_futur, ra.name_file_data, ra.obs_file_data,
             ra.pre_marquant, ra.fait_marquant, ra.agents_complement";

    $in_list = implode(',', $list_id_ra);

    $sql_RA = "SELECT DISTINCT " . $cols . "
               FROM " . TABLE_DATA_RA . " ra
               WHERE ra.id_ra IN (" . $in_list . ")
               ORDER BY ra.date_heure_ra DESC";

    $RA_query = tep_db_query($sql_link, $sql_RA);

    $html = '';
    $first = true;
    $last_code = '';
    $last_nom  = '';
    $last_date = '';
    while ($RA_tab = tep_db_fetch_array($RA_query)) {
        if (!$first) { $html .= "<pagebreak />"; }
        $first = false;
        $html .= build_ra_sheet($RA_tab, $eq_type_array, $station_all_array, $commune_array, $user_list_array, $today, $prenom_user, $nom_user, $info_user);

        $sid = $RA_tab['id_station'];
        if (isset($station_all_array[$sid])) {
            $last_code = $station_all_array[$sid]['code_station'];
            $last_nom  = $station_all_array[$sid]['nom_station'];
        }
        // RA reading date (yyyy-mm-dd part) -> ddmmyyyy for the filename
        $dpart = explode(' ', $RA_tab['date_heure_ra'])[0];
        $last_date = str_replace('-', '', dateus_fr($dpart)); // ddmmyyyy
    }

    // Output filename:
    //   single RA  -> [PREFIX]_[code]_[clean station name]_[date].pdf
    //   several RA -> [PREFIX]_lot_[count]_[today].pdf
    if (count($list_id_ra) == 1) {
        $fileName = TEXT_RA . "_" . $last_code . "_" . nettoyerNomFichier($last_nom) . "_" . $last_date . ".pdf";
    } else {
        $fileName = TEXT_RA . "_lot_" . count($list_id_ra) . "_" . str_replace('-', '', $today) . ".pdf";
    }
    $filePath = $_SERVER['DOCUMENT_ROOT'] . "/" . DIR_WS_PDF . $fileName;

    // Initialise mPDF
    $mpdf = new \Mpdf\Mpdf([
        'margin_left'   => 10,
        'margin_right'  => 10,
        'margin_top'    => 30,
        'margin_bottom' => 10
    ]);

    // Load and apply CSS stylesheet (shared with the station sheet)
    $stylesheet = file_get_contents('../../../css/pdf_css.css');
    $mpdf->WriteHTML($stylesheet, \Mpdf\HTMLParserMode::HEADER_CSS);
    $mpdf->SetHTMLHeader($header);
    $mpdf->SetHTMLFooter($footer);
    $mpdf->WriteHTML($html);

    $mpdf->Output($filePath, \Mpdf\Output\Destination::FILE);

    if (ob_get_length() !== false) { ob_clean(); }
    echo json_encode([
        'status'   => 'success',
        'fileName' => $fileName
    ]);

} catch (\Mpdf\MpdfException $e) {
    if (ob_get_length() !== false) { ob_clean(); }
    echo json_encode([
        'status'   => 'error',
        'msg_info' => $e->getMessage()
    ]);
}
?>