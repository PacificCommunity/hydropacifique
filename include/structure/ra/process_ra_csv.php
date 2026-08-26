<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Field Report (RA) CSV export for a selection of field reports.
Reuses the column conventions of process_export_csv.php (TEXT_CSV_COL_*),
but accepts an arbitrary list of id_ra (possibly several types / stations)
and groups the output by data type.
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

// -----------------------------------------------
// Output file path
$today    = date('Ymd_His');
$fileName = TEXT_RA . "_export_" . count($list_id_ra) . "_" . $today . ".csv";
$rel_dir  = 'data/csv';
$abs_dir  = $_SERVER['DOCUMENT_ROOT'] . '/' . $rel_dir;
if (!is_dir($abs_dir)) { mkdir($abs_dir, 0755, true); }
$filePath = $abs_dir . '/' . $fileName;

// -----------------------------------------------
// Header rows per data type (same columns as process_export_csv.php)
function ra_csv_header($type_eq) {
    if ($type_eq == 1) { // Pluvio
        return [
            TEXT_CSV_COL_STATION_NUM,       TEXT_CSV_COL_STATION_NAME,
            TEXT_CSV_COL_PLU_RELEVE_DATE,   TEXT_CSV_COL_PLU_RELEVE_HEURE,
            TEXT_CSV_COL_PLU_APP_TYPE,      TEXT_CSV_COL_PLU_APP_NUM,        TEXT_CSV_COL_PLU_APP_HEURE,
            TEXT_CSV_COL_PLU_TOT_TYPE,      TEXT_CSV_COL_PLU_TOT_ARRIVE,
            TEXT_CSV_COL_PLU_TOT_DEPART,    TEXT_CSV_COL_PLU_TOT_HEURE,
            TEXT_CSV_COL_PLU_NB_BASC,       TEXT_CSV_COL_PLU_NB_OCTET,
            TEXT_CSV_COL_PLU_BAT_NUM,       TEXT_CSV_COL_PLU_BAT_TENSION,
            TEXT_CSV_COL_PLU_CUMUL_TOT,     TEXT_CSV_COL_PLU_CUMUL_PLU,     TEXT_CSV_COL_PLU_DIFF,
            TEXT_CSV_COL_PLU_CALAGE_HEURE,  TEXT_CSV_COL_PLU_TEST_AUGET,
            TEXT_CSV_COL_PLU_BOUCHAGE,      TEXT_CSV_COL_PLU_DEBROUSSAILLAGE,
            TEXT_CSV_COL_PLU_EAU_BAT,       TEXT_CSV_COL_PLU_HUILE_TOT,
            TEXT_CSV_COL_PLU_TRANSFERT,     TEXT_CSV_COL_PLU_MEM_EFFACEE,
            TEXT_CSV_COL_OBS,               TEXT_CSV_COL_FUTURE,            TEXT_CSV_COL_AGENTS,
            TEXT_CSV_COL_FILE_NAME,         TEXT_CSV_COL_FILE_OBS,
        ];
    }
    if ($type_eq == 11) { // Hydro
        return [
            TEXT_CSV_COL_STATION_NUM,        TEXT_CSV_COL_STATION_NAME,
            TEXT_CSV_COL_PLU_RELEVE_DATE,    TEXT_CSV_COL_PLU_RELEVE_HEURE,
            TEXT_CSV_COL_PLU_APP_TYPE,       TEXT_CSV_COL_PLU_APP_HEURE,
            TEXT_CSV_COL_HYD_COTE_HEURE,     TEXT_CSV_COL_HYD_COTE_SONDE,
            TEXT_CSV_COL_HYD_COTE_ECHL,      TEXT_CSV_COL_HYD_COTE_ECHL2,
            TEXT_CSV_COL_HYD_NUM_SONDE,      TEXT_CSV_COL_HYD_NB_OCTET,
            TEXT_CSV_COL_HYD_BAT_TENSION,
            TEXT_CSV_COL_HYD_CTRL_RECAL_SONDE,
            TEXT_CSV_COL_HYD_PURGE,          TEXT_CSV_COL_HYD_JAUGEAGE,
            TEXT_CSV_COL_HYD_DEBROUSSAILLAGE, TEXT_CSV_COL_HYD_EAU_BAT,
            TEXT_CSV_COL_HYD_TRANSFERT,      TEXT_CSV_COL_HYD_MEM_EFFACEE,
            TEXT_CSV_COL_OBS,                TEXT_CSV_COL_FUTURE,              TEXT_CSV_COL_AGENTS,
            TEXT_CSV_COL_FILE_NAME,          TEXT_CSV_COL_FILE_OBS,
        ];
    }
    if ($type_eq == 5) { // Piezo
        return [
            TEXT_CSV_COL_STATION_NUM,           TEXT_CSV_COL_STATION_NAME,
            TEXT_CSV_COL_PLU_RELEVE_DATE,       TEXT_CSV_COL_PLU_RELEVE_HEURE,
            TEXT_CSV_COL_PIE_SONDE_FIXE_TYPE,   TEXT_CSV_COL_PIE_SONDE_FIXE_NUM,
            TEXT_CSV_COL_PIE_SONDE_MAN_TYPE,    TEXT_CSV_COL_PIE_SONDE_MAN_NUM,
            TEXT_CSV_COL_PIE_MESURE_TOIT_M,     TEXT_CSV_COL_PIE_MESURE_COND,       TEXT_CSV_COL_PIE_MESURE_TEMP,
            TEXT_CSV_COL_PIE_MAN_TOIT_M,        TEXT_CSV_COL_PIE_PROF_OUV,
            TEXT_CSV_COL_PIE_CTRL_DIFF,         TEXT_CSV_COL_PIE_CTRL_RECAL_SONDE,
            TEXT_CSV_COL_PIE_MEM_NB,            TEXT_CSV_COL_PIE_MEM_EFFACEE,
            TEXT_CSV_COL_PIE_BAT,
            TEXT_CSV_COL_PIE_NATURE_REPERE,
            TEXT_CSV_COL_PIE_POMPAGE_ENCOURS,   TEXT_CSV_COL_PIE_POMPAGE_PROCHE,
            TEXT_CSV_COL_PIE_PLUIE_CRUE,        TEXT_CSV_COL_PIE_TEMPS_SEC,
            TEXT_CSV_COL_OBS,                   TEXT_CSV_COL_FUTURE,                TEXT_CSV_COL_AGENTS,
            TEXT_CSV_COL_COORD_X,               TEXT_CSV_COL_COORD_Y,
            TEXT_CSV_COL_FILE_NAME,             TEXT_CSV_COL_FILE_OBS,
        ];
    }
    return [];
}

// Clean a value for CSV (strip line breaks, blank-out falsy flags is handled by caller)
function ra_csv_clean($v) {
    if ($v === null) { return ''; }
    return str_replace(["\r", "\n"], ' ', (string)$v);
}

// Build a data row for a given RA row and type
function ra_csv_row($r, $type_eq, $code_station, $nom_station) {
    $tab_dh = explode(' ', $r['date_heure_ra'] ?? '');
    $date_ra  = str_replace('-', '/', $tab_dh[0] ?? '');
    $heure_ra = $tab_dh[1] ?? '';

    // Falsy flags -> empty string
    foreach (['plu_ra_bouchage','ra_debroussaillage','ra_eau_batterie','plu_ra_huile_tot',
              'ra_transfert_data','ra_delete_memory','hydro_purge_sonde','hydro_ra_jaugeage',
              'piezo_pompage_encours','piezo_pompage_proche','piezo_pluie_crue','piezo_temps_sec'] as $f) {
        if (isset($r[$f]) && $r[$f] < 1) { $r[$f] = ''; }
    }

    if ($type_eq == 1) {
        return [
            $code_station, $nom_station,
            $date_ra, $heure_ra,
            $r['type_appareil'] ?? '', $r['num_appareil'] ?? '', $r['heure_appareil'] ?? '',
            $r['plu_tot_type'] ?? '', $r['plu_tot_first'] ?? '',
            $r['plu_tot_last'] ?? '', $r['plu_tot_heure_basc'] ?? '',
            $r['plu_nb_basculement'] ?? '', $r['nb_octet'] ?? '',
            $r['num_batterie'] ?? '', $r['tension_batterie'] ?? '',
            $r['plu_cumul_tot'] ?? '', $r['plu_cumul_plu'] ?? '', $r['plu_diff_tot_plu'] ?? '',
            $r['plu_recalage_heure_plu'] ?? '', $r['plu_test_auget'] ?? '',
            $r['plu_ra_bouchage'] ?? '', $r['ra_debroussaillage'] ?? '',
            $r['ra_eau_batterie'] ?? '', $r['plu_ra_huile_tot'] ?? '',
            $r['ra_transfert_data'] ?? '', $r['ra_delete_memory'] ?? '',
            ra_csv_clean($r['ra_obs'] ?? ''), ra_csv_clean($r['ra_futur'] ?? ''),
            ra_csv_clean($r['agents_complement'] ?? ''),
            $r['name_file_data'] ?? '', ra_csv_clean($r['obs_file_data'] ?? ''),
        ];
    }
    if ($type_eq == 11) {
        return [
            $code_station, $nom_station,
            $date_ra, $heure_ra,
            $r['type_appareil'] ?? '', $r['heure_appareil'] ?? '',
            $r['hydro_heure_cote'] ?? '', $r['hydro_h_sonde'] ?? '',
            $r['hydro_h_echelle_1'] ?? '', $r['hydro_h_echelle_2'] ?? '',
            $r['hydro_num_sonde'] ?? '', $r['nb_octet'] ?? '',
            $r['tension_batterie'] ?? '',
            $r['hydro_recalage_sonde'] ?? '',
            $r['hydro_purge_sonde'] ?? '', $r['hydro_ra_jaugeage'] ?? '',
            $r['ra_debroussaillage'] ?? '', $r['ra_eau_batterie'] ?? '',
            $r['ra_transfert_data'] ?? '', $r['ra_delete_memory'] ?? '',
            ra_csv_clean($r['ra_obs'] ?? ''), ra_csv_clean($r['ra_futur'] ?? ''),
            ra_csv_clean($r['agents_complement'] ?? ''),
            $r['name_file_data'] ?? '', ra_csv_clean($r['obs_file_data'] ?? ''),
        ];
    }
    if ($type_eq == 5) {
        $manuelle_toitnappe_m = $r['piezo_prof_toitnappe'] ?? '';
        $sondefixe_toitnappe  = $r['piezo_toitnappesonde'] ?? '';
        $diff = ($sondefixe_toitnappe === '' || $manuelle_toitnappe_m === '')
            ? '' : ((float)$manuelle_toitnappe_m - (float)$sondefixe_toitnappe);
        return [
            $code_station, $nom_station,
            $date_ra, $heure_ra,
            $r['type_appareil'] ?? '', $r['num_appareil'] ?? '',
            $r['piezo_instrument'] ?? '', $r['piezo_num_instrument'] ?? '',
            $sondefixe_toitnappe, $r['piezo_conductivite'] ?? '', $r['piezo_temperature'] ?? '',
            $manuelle_toitnappe_m, $r['piezo_prof_totale'] ?? '',
            $diff, $r['piezo_recalage_sonde'] ?? '',
            $r['nb_octet'] ?? '', $r['ra_delete_memory'] ?? '',
            $r['tension_batterie'] ?? '',
            ra_csv_clean($r['piezo_nature_repere'] ?? ''),
            $r['piezo_pompage_encours'] ?? '', $r['piezo_pompage_proche'] ?? '',
            $r['piezo_pluie_crue'] ?? '', $r['piezo_temps_sec'] ?? '',
            ra_csv_clean($r['ra_obs'] ?? ''), ra_csv_clean($r['ra_futur'] ?? ''),
            ra_csv_clean($r['agents_complement'] ?? ''),
            ra_csv_clean($r['piezo_x_terrain'] ?? ''), ra_csv_clean($r['piezo_y_terrain'] ?? ''),
            $r['name_file_data'] ?? '', ra_csv_clean($r['obs_file_data'] ?? ''),
        ];
    }
    return [];
}

// -----------------------------------------------
// Fetch selected RA, ordered by type then station then date
try {
    $in_list = implode(',', $list_id_ra);
    $sql_RA = "SELECT * FROM " . TABLE_DATA_RA . " ra
               WHERE ra.id_ra IN (" . $in_list . ")
               ORDER BY ra.id_eq_type ASC, ra.id_station ASC, ra.date_heure_ra ASC";
    $RA_query = tep_db_query($sql_link, $sql_RA);

    // Group rows by type
    $rows_by_type = array(1 => [], 11 => [], 5 => []);
    while ($r = tep_db_fetch_array($RA_query)) {
        $t = (int)$r['id_eq_type'];
        if (isset($rows_by_type[$t])) { $rows_by_type[$t][] = $r; }
    }

    $handle = fopen($filePath, 'w');
    // UTF-8 BOM so Excel opens accents correctly
    fwrite($handle, "\xEF\xBB\xBF");

    $type_titles = array(1 => TEXT_CSV_TITLE_RA, 11 => TEXT_CSV_TITLE_RA, 5 => TEXT_CSV_TITLE_RA);
    $first_block = true;
    foreach (array(1, 11, 5) as $type_eq) {
        if (empty($rows_by_type[$type_eq])) { continue; }

        if (!$first_block) { fputcsv($handle, [''], ';'); }
        $first_block = false;

        // Section header (data type label)
        fputcsv($handle, ra_csv_header($type_eq), ';');

        foreach ($rows_by_type[$type_eq] as $r) {
            $sid  = $r['id_station'];
            $code = isset($station_all_array[$sid]) ? $station_all_array[$sid]['code_station'] : '';
            $nom  = isset($station_all_array[$sid]) ? $station_all_array[$sid]['nom_station'] : '';
            fputcsv($handle, ra_csv_row($r, $type_eq, $code, $nom), ';');
        }
    }

    fclose($handle);

    if (ob_get_length() !== false) { ob_clean(); }
    echo json_encode(['status' => 'success', 'fileName' => $fileName]);

} catch (Exception $e) {
    if (ob_get_length() !== false) { ob_clean(); }
    echo json_encode(['status' => 'error', 'msg_info' => $e->getMessage()]);
}
?>
