<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
AJAX endpoint — station information XLSX export
Called by the station download feature.
Receives JSON: idTerritoire, listStation (comma-separated station IDs), cheminFolder.
Generates a multi-sheet XLSX file on the server using PhpSpreadsheet.

Sheets created:
  Sheet 1 — Identification    (all station types; piezo-specific columns N–T)
  Sheet 2 — Historique Repères (piezometric stations only)
  Sheet 3 — Caractéristiques  (piezometric stations only)

Returns JSON:
  statut        : bool   — true on success
  executionTime : float  — script duration in seconds
  xlsFile       : string — generated filename (basename only)
----------------------------------------
*/

require('../../config.php');
require('../../database_tables.php');

require('../../function/date.php');
require('../../function/database.php');
require('../../function/html_output.php');
require('../../function/general.php');

// Load translation strings for the active language
require('../../text_content_' . LANGUAGE . '.php');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Singleton wrapper — avoids re-instantiating the spreadsheet if this file
// is included more than once in the same request
class SpreadsheetManager
{
    private static $spreadsheet;

    public static function getSpreadsheet()
    {
        if (!isset(self::$spreadsheet)) {
            self::$spreadsheet = new Spreadsheet();
        }
        return self::$spreadsheet;
    }
}

header('Content-Type: text/html; charset=utf-8');

$sql_link = mysqli_connect(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD, DB_DATABASE)
    or die('Cannot connect to the database');
mysqli_query($sql_link, 'SET NAMES UTF8');

// Decode JSON payload sent by the AJAX call
$data          = json_decode(file_get_contents('php://input'), true);
$territoire_id = $data['idTerritoire'];
$listStation   = $data['listStation'];
$chemin_folder = $data['cheminFolder'];


// -----------------------------------------------
// Lookup arrays populated before building sheets

// -----------------------------------------------
// Query: territoire metadata
// theme_region provides the custom label used as a suffix in tab and column headers
$nom_territoire    = '';
$init_territoire    = '';
$theme_region    = '';
$region_default    = '';

$sql_territoire = "SELECT DISTINCT nom_territoire, init_territoire, theme_region, region_default
                   FROM " . TABLE_TERRITOIRE . "
                   WHERE id_territoire = " . $territoire_id . "
                   ORDER BY LOWER(nom_territoire) ASC";

$territoire_query = tep_db_query($sql_link, $sql_territoire);
while ($territoire = tep_db_fetch_array($territoire_query))
{
    $nom_territoire  = html_entity_decode($territoire['nom_territoire']  ?? '');
    $init_territoire = html_entity_decode($territoire['init_territoire'] ?? '');
    $theme_region    = html_entity_decode($territoire['theme_region']    ?? '');
    $region_default  = $territoire['region_default'];
}

// Geographic regions
$region_array = [];
$region_query = tep_db_query($sql_link,
    "SELECT DISTINCT id_region, nom_region
     FROM " . TABLE_REGION . "
     WHERE id_territoire = " . $territoire_id);
while ($region = tep_db_fetch_array($region_query))
{
    $region_array[$region['id_region']] = $region['nom_region'];
}

// Communes
$commune_array = [];
$commune_query = tep_db_query($sql_link,
    "SELECT DISTINCT c.id_commune, c.nom_commune
     FROM " . TABLE_COMMUNE . " c
     JOIN " . TABLE_REGION . " r ON c.id_region = r.id_region
     WHERE r.id_territoire = " . $territoire_id . "
     ORDER BY c.nom_commune ASC");
while ($commune = tep_db_fetch_array($commune_query))
{
    $commune_array[$commune['id_commune']] = $commune['nom_commune'];
}

// Hydrological regions
$regionhydro_array = [];
$regionhydro_query = tep_db_query($sql_link,
    "SELECT DISTINCT id, nom
     FROM " . TABLE_REGIONHYDRO . "
     WHERE id_territoire = " . $territoire_id . "
     ORDER BY nom ASC");
while ($regionhydro = tep_db_fetch_array($regionhydro_query))
{
    $regionhydro_array[$regionhydro['id']] = $regionhydro['nom'];
}

// Station natures (piezometric)
$nature_array = [];
$nature_query = tep_db_query($sql_link,
    "SELECT DISTINCT id, libelle FROM " . TABLE_STATION_NATURE);
while ($nature_tab = tep_db_fetch_array($nature_query))
{
    $nature_array[$nature_tab['id']] = $nature_tab['libelle'];
}

// Data types (Surface water, Rainfall, Groundwater, ...) — same source as the PDF sheet
$eq_type_array = [];
$eq_type_query = tep_db_query($sql_link,
    "SELECT DISTINCT id_eq_type, nom_eq_type
     FROM " . TABLE_EQ_TYPE . "
     WHERE active_eq_type = 1
     ORDER BY order_eq_type ASC");
while ($eq_type = tep_db_fetch_array($eq_type_query))
{
    $eq_type_array[$eq_type['id_eq_type']] = html_entity_decode($eq_type['nom_eq_type'] ?? '');
}


// -----------------------------------------------
// SQL queries — executed inside the sheet-building loops below

$sql_station = "SELECT DISTINCT
                    id_station, id_station_old, station_type,
                    nom_station, nom_court, code_station, num_irh,
                    active_station, suivi, armee,
                    id_territoire, id_region, id_commune, id_regionhydro, id_riviere,
                    id_tournee, site_station, vallee_station, riviere_station,
                    altitude_station, orientation_station,
                    latitude_station, longitude_station,
                    ign_station_x, ign_station_y, lamb_station_x, lamb_station_y,
                    date_installation_station, date_fermeture_station,
                    description_station, source_info, proprio_station,
                    transmission_station, correct_station,
                    piezo_id_nature, piezo_sonde, piezo_precision,
                    piezo_maitre_ouvrage, piezo_date_realisation, z_sol
                FROM " . TABLE_STATION . "
                WHERE id_station IN (" . $listStation . ")";

$sql_caract = "SELECT DISTINCT
                    s.nom_station, s.code_station,
                    c.id, c.date, c.prof, c.materiaux_tete, c.dim_tete_ext,
                    c.materiaux_tub_inter, c.diam_tub_inter, c.materiaux_dalle, c.dim_dalle,
                    c.dist_capto_tube, c.dist_tube_dalle, c.dist_dalle_sol, c.presence_capot,
                    c.etat, c.activite, c.utilisation, c.equipement_exploitation,
                    c.schema_tete, c.schema_protect, c.obs
               FROM " . TABLE_STATION_PIEZO_CARACTERISTIQUE . " c
               JOIN " . TABLE_STATION . " s ON c.id_station = s.id_station
               WHERE c.id_station IN (" . $listStation . ")
               ORDER BY c.date DESC";

$sql_repere = "SELECT DISTINCT
                    s.nom_station, s.code_station,
                    r.id, r.nature_repere, r.code_repere, r.z_repere,
                    r.precision_repere, r.date_debut_valid, r.date_fin_valid,
                    r.nature_repere_1, r.z_repere_g1, r.nature_repere_2, r.z_repere_g2, r.obs
               FROM " . TABLE_STATION_PIEZO_REPERE . " r
               JOIN " . TABLE_STATION . " s ON r.id_station = s.id_station
               WHERE r.id_station IN (" . $listStation . ")
               ORDER BY r.date_debut_valid DESC";


// -----------------------------------------------
// Spreadsheet initialisation

$todayTime       = new DateTime();
$today_formatted = $todayTime->format('dmY');
$spreadsheet     = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
$startTime       = microtime(true);


// -----------------------------------------------
// SHEET 1 — Station identification

$spreadsheet->createSheet();
$nb    = $spreadsheet->getIndex($spreadsheet->getSheetByName('Worksheet'));
$spreadsheet->setActiveSheetIndex($nb);
$sheet1 = $spreadsheet->getActiveSheet();
$sheet1->setTitle(TEXT_STATION_COL_SHEET_IDENT);

$sheet1->setCellValue('A1', TEXT_STATION_COL_TYPE);
$sheet1->setCellValue('B1', TEXT_STATION_COL_CODE);
$sheet1->setCellValue('C1', TEXT_STATION_COL_NOM);
$sheet1->setCellValue('D1', TEXT_STATION_COL_SITE);
$sheet1->setCellValue('E1', $theme_region);
$sheet1->setCellValue('F1', TEXT_STATION_COL_COMMUNE);
$sheet1->setCellValue('G1', TEXT_STATION_COL_REGIONHYDRO);
$sheet1->setCellValue('H1', TEXT_STATION_COL_NAPPE);
$sheet1->setCellValue('I1', TEXT_STATION_COL_X_RGNC);
$sheet1->setCellValue('J1', TEXT_STATION_COL_Y_RGNC);
$sheet1->setCellValue('K1', TEXT_STATION_COL_X_WGS);
$sheet1->setCellValue('L1', TEXT_STATION_COL_Y_WGS);
$sheet1->setCellValue('M1', TEXT_STATION_COL_STATUS);
$sheet1->setCellValue('N1', TEXT_STATION_COL_DESCRIPTION);
$sheet1->setCellValue('O1', TEXT_STATION_COL_ZSOL);
$sheet1->setCellValue('P1', TEXT_STATION_COL_PRECISION);
$sheet1->setCellValue('Q1', TEXT_STATION_COL_AQUIFERE);
$sheet1->setCellValue('R1', TEXT_STATION_COL_NATURE);
$sheet1->setCellValue('S1', TEXT_STATION_COL_MAITRE_OUVRAGE);
$sheet1->setCellValue('T1', TEXT_STATION_COL_DATE_REALISATION);
$sheet1->setCellValue('U1', TEXT_STATION_COL_SONDE);

$nb_station    = 0;
$num_ligne     = 2;
$piezo_encours = false;

$station_query = tep_db_query($sql_link, $sql_station);
while ($station_tab = tep_db_fetch_array($station_query))
{
    $type_data        = $station_tab['station_type'];
    $code_station     = $station_tab['code_station'];
    $nom_station      = $station_tab['nom_station'];
    $site             = $station_tab['site_station'];

    // Data-type label (Surface water, Rainfall, Groundwater, ...)
    $nom_eq_type = '';
    if (isset($eq_type_array[$type_data])) { $nom_eq_type = $eq_type_array[$type_data]; }

    $region_geo = '';
    if (!empty($station_tab['id_region']) && isset($region_array[$station_tab['id_region']]))
    {
        $region_geo = $region_array[$station_tab['id_region']];
    }

    $commune = '';
    if (!empty($station_tab['id_commune']) && isset($commune_array[$station_tab['id_commune']]))
    {
        $commune = $commune_array[$station_tab['id_commune']];
    }

    $region_hydro = '';
    if (!empty($station_tab['id_regionhydro']) && isset($regionhydro_array[$station_tab['id_regionhydro']]))
    {
        $region_hydro = $regionhydro_array[$station_tab['id_regionhydro']];
    }

    $nappe            = ''; // not yet stored in the database
    $lamb_station_x   = $station_tab['lamb_station_x'];
    $lamb_station_y   = $station_tab['lamb_station_y'];
    $ign_station_x    = $station_tab['ign_station_x'];
    $ign_station_y    = $station_tab['ign_station_y'];
    $active_station   = ($station_tab['active_station'] > 0) ? 'Active' : 'Historique';
    $description_station = $station_tab['description_station'];

    // Piezometric-only fields (station_type = 5)
    if ($type_data == 5)
    {
        $piezo_encours    = true;
        $z_sol            = $station_tab['z_sol'];
        $aquifere         = ''; // not yet stored in the database
        $precision        = $station_tab['piezo_precision'];
        $maitre_ouvrage   = $station_tab['piezo_maitre_ouvrage'];

        $nature = '';
        if (isset($nature_array[$station_tab['piezo_id_nature']]))
        {
            $nature = $nature_array[$station_tab['piezo_id_nature']];
        }

        $date_realisation_formated = '';
        $date_realisation = $station_tab['piezo_date_realisation'];
        if (!empty($date_realisation))
        {
            $date_object = DateTime::createFromFormat('Y-m-d H:i:s', $date_realisation);
            $date_realisation_formated = $date_object->format('d-m-Y');
        }

        $sonde_piezo = ($station_tab['piezo_sonde'] > 0) ? 'Oui' : 'Non';
    }

    $sheet1->setCellValue('A' . $num_ligne, $nom_eq_type);
    $sheet1->setCellValue('B' . $num_ligne, $code_station);
    $sheet1->setCellValue('C' . $num_ligne, $nom_station);
    $sheet1->setCellValue('D' . $num_ligne, $site);
    $sheet1->setCellValue('E' . $num_ligne, $region_geo);
    $sheet1->setCellValue('F' . $num_ligne, $commune);
    $sheet1->setCellValue('G' . $num_ligne, $region_hydro);
    $sheet1->setCellValue('H' . $num_ligne, $nappe);
    $sheet1->setCellValue('I' . $num_ligne, $lamb_station_x);
    $sheet1->setCellValue('J' . $num_ligne, $lamb_station_y);
    $sheet1->setCellValue('K' . $num_ligne, $ign_station_x);
    $sheet1->setCellValue('L' . $num_ligne, $ign_station_y);
    $sheet1->setCellValue('M' . $num_ligne, $active_station);
    $sheet1->setCellValue('N' . $num_ligne, $description_station);

    if ($type_data == 5)
    {
        $sheet1->setCellValue('O' . $num_ligne, $z_sol);
        $sheet1->setCellValue('P' . $num_ligne, $precision);
        $sheet1->setCellValue('Q' . $num_ligne, $aquifere);
        $sheet1->setCellValue('R' . $num_ligne, $nature);
        $sheet1->setCellValue('S' . $num_ligne, $maitre_ouvrage);
        $sheet1->setCellValue('T' . $num_ligne, $date_realisation_formated);
        $sheet1->setCellValue('U' . $num_ligne, $sonde_piezo);
    }

    $num_ligne++;
    $nb_station++;
}

$sheet1->getStyle('A1:U1')->getFont()->setBold(true);
foreach (range('A', 'U') as $col) { $sheet1->getColumnDimension($col)->setAutoSize(true); }


// -----------------------------------------------
// SHEETS 2–3 — Piezometric-only detail (only when at least one piezo station found)

if ($piezo_encours)
{
    // Sheet 2: Benchmark history
    $spreadsheet->createSheet();
    $nb    = $spreadsheet->getIndex($spreadsheet->getSheetByName('Worksheet'));
    $spreadsheet->setActiveSheetIndex($nb);
    $sheet2 = $spreadsheet->getActiveSheet();
    $sheet2->setTitle(TEXT_STATION_TAB_BENCHMARK);

    $sheet2->setCellValue('A1', TEXT_STATION_COL_CODE);
    $sheet2->setCellValue('B1', TEXT_STATION_COL_NOM);
    $sheet2->setCellValue('C1', TEXT_STATION_COL_REP_NATURE);
    $sheet2->setCellValue('D1', TEXT_STATION_COL_REP_Z);
    $sheet2->setCellValue('E1', TEXT_STATION_COL_REP_PRECISION);
    $sheet2->setCellValue('F1', TEXT_STATION_COL_REP_CODE);
    $sheet2->setCellValue('G1', TEXT_STATION_COL_REP_DATE_DEBUT);
    $sheet2->setCellValue('H1', TEXT_STATION_COL_REP_DATE_FIN);
    $sheet2->setCellValue('I1', TEXT_STATION_COL_REP_NATURE_G1);
    $sheet2->setCellValue('J1', TEXT_STATION_COL_REP_Z_G1);
    $sheet2->setCellValue('K1', TEXT_STATION_COL_REP_NATURE_G2);
    $sheet2->setCellValue('L1', TEXT_STATION_COL_REP_Z_G2);
    $sheet2->setCellValue('M1', TEXT_STATION_COL_REP_OBS);

    $num_ligne    = 2;
    $repere_query = tep_db_query($sql_link, $sql_repere);
    while ($repere_tab = tep_db_fetch_array($repere_query))
    {
        // Format dates from DB format to d-m-Y
        $date_debut_valid_formated = '';
        $date_debut_valid = $repere_tab['date_debut_valid'];
        if (!empty($date_debut_valid) && $date_debut_valid !== '0000-00-00')
        {
            $date_debut_valid_formated = DateTime::createFromFormat('Y-m-d', $date_debut_valid)->format('d-m-Y');
        }

        $date_fin_valid_formated = '';
        $date_fin_valid = $repere_tab['date_fin_valid'];
        if (!empty($date_fin_valid) && $date_fin_valid !== '0000-00-00')
        {
            $date_fin_valid_formated = DateTime::createFromFormat('Y-m-d', $date_fin_valid)->format('d-m-Y');
        }

        // Normalise z values: replace comma decimal separator and round to 3dp
        $z_repere = $repere_tab['z_repere'] ?? '';
        if ($z_repere !== '')
        {
            $z_repere = round(floatval(str_replace(',', '.', $z_repere)), 3);
        }

        $z_repere_g1 = $repere_tab['z_repere_g1'] ?? '';
        if ($z_repere_g1 !== '')
        {
            $z_repere_g1 = round(floatval(str_replace(',', '.', $z_repere_g1)), 3);
        }

        // Note: z_repere_g2 rounding was overwritten immediately after in the original;
        // the rounded value is used here (the raw overwrite was a bug in the original).
        $z_repere_g2 = $repere_tab['z_repere_g2'] ?? '';
        if ($z_repere_g2 !== '')
        {
            $z_repere_g2 = round(floatval(str_replace(',', '.', $z_repere_g2)), 3);
        }

        $sheet2->setCellValue('A' . $num_ligne, $repere_tab['code_station']);
        $sheet2->setCellValue('B' . $num_ligne, $repere_tab['nom_station']);
        $sheet2->setCellValue('C' . $num_ligne, $repere_tab['nature_repere']);
        $sheet2->setCellValue('D' . $num_ligne, $z_repere);
        $sheet2->setCellValue('E' . $num_ligne, $repere_tab['precision_repere']);
        $sheet2->setCellValue('F' . $num_ligne, $repere_tab['code_repere']);
        $sheet2->setCellValue('G' . $num_ligne, $date_debut_valid_formated);
        $sheet2->setCellValue('H' . $num_ligne, $date_fin_valid_formated);
        $sheet2->setCellValue('I' . $num_ligne, $repere_tab['nature_repere_1']);
        $sheet2->setCellValue('J' . $num_ligne, $z_repere_g1);
        $sheet2->setCellValue('K' . $num_ligne, $repere_tab['nature_repere_2']);
        $sheet2->setCellValue('L' . $num_ligne, $z_repere_g2);
        $sheet2->setCellValue('M' . $num_ligne, $repere_tab['obs']);
        $num_ligne++;
    }

    $sheet2->getStyle('A1:M1')->getFont()->setBold(true);
    foreach (range('A', 'T') as $col) { $sheet2->getColumnDimension($col)->setAutoSize(true); }


    // Sheet 3: Construction characteristics
    $spreadsheet->createSheet();
    $nb    = $spreadsheet->getIndex($spreadsheet->getSheetByName('Worksheet'));
    $spreadsheet->setActiveSheetIndex($nb);
    $sheet3 = $spreadsheet->getActiveSheet();
    $sheet3->setTitle(TEXT_STATION_TAB_CHARACTERISTICS);

    $sheet3->setCellValue('A1', TEXT_STATION_COL_CODE);
    $sheet3->setCellValue('B1', TEXT_STATION_COL_NOM);
    $sheet3->setCellValue('C1', TEXT_STATION_COL_CAR_DATE_OBS);
    $sheet3->setCellValue('D1', TEXT_STATION_COL_CAR_PROF);
    $sheet3->setCellValue('E1', TEXT_STATION_COL_CAR_MAT_TETE);
    $sheet3->setCellValue('F1', TEXT_STATION_COL_CAR_DIM_TETE);
    $sheet3->setCellValue('G1', TEXT_STATION_COL_CAR_MAT_TUB);
    $sheet3->setCellValue('H1', TEXT_STATION_COL_CAR_DIAM_TUB);
    $sheet3->setCellValue('I1', TEXT_STATION_COL_CAR_MAT_DALLE);
    $sheet3->setCellValue('J1', TEXT_STATION_COL_CAR_DIM_DALLE);
    $sheet3->setCellValue('K1', TEXT_STATION_COL_CAR_DIST_CAPOT);
    $sheet3->setCellValue('L1', TEXT_STATION_COL_CAR_DIST_TUB_DALLE);
    $sheet3->setCellValue('M1', TEXT_STATION_COL_CAR_DIST_DALLE_SOL);
    $sheet3->setCellValue('N1', TEXT_STATION_COL_CAR_PRESENCE_CAPOT);
    $sheet3->setCellValue('O1', TEXT_STATION_COL_CAR_ETAT);
    $sheet3->setCellValue('P1', TEXT_STATION_COL_CAR_ACTIVITE);
    $sheet3->setCellValue('Q1', TEXT_STATION_COL_CAR_USAGE);
    $sheet3->setCellValue('R1', TEXT_STATION_COL_CAR_EQUIP);
    $sheet3->setCellValue('S1', TEXT_STATION_COL_CAR_SCHEMA_TETE);
    $sheet3->setCellValue('T1', TEXT_STATION_COL_CAR_OBS);

    $num_ligne    = 2;
    $caract_query = tep_db_query($sql_link, $sql_caract);
    while ($caract_tab = tep_db_fetch_array($caract_query))
    {
        $date_caract_formated = '';
        $date_caract = $caract_tab['date'];
        if (!empty($date_caract))
        {
            $date_caract_formated = DateTime::createFromFormat('Y-m-d', $date_caract)->format('d-m-Y');
        }

        // Normalise dimension/distance values
        $diam_tub_inter = $caract_tab['diam_tub_inter'] ?? '';
        if ($diam_tub_inter !== '')
        {
            $diam_tub_inter = round(floatval(str_replace(',', '.', $diam_tub_inter)), 3);
        }

        $dist_capto_tube = $caract_tab['dist_capto_tube'] ?? '';
        if ($dist_capto_tube !== '')
        {
            $dist_capto_tube = round(floatval(str_replace(',', '.', $dist_capto_tube)), 3);
        }

        $dist_tube_dalle = $caract_tab['dist_tube_dalle'] ?? '';
        if ($dist_tube_dalle !== '')
        {
            $dist_tube_dalle = round(floatval(str_replace(',', '.', $dist_tube_dalle)), 3);
        }

        $dist_dalle_sol = $caract_tab['dist_dalle_sol'] ?? '';
        if ($dist_dalle_sol !== '')
        {
            $dist_dalle_sol = round(floatval(str_replace(',', '.', $dist_dalle_sol)), 3);
        }

        $presence_capot = ($caract_tab['presence_capot'] > 0) ? 'Oui' : 'Non';
        $schema_tete    = 'SO_' . $caract_tab['schema_tete'];

        $sheet3->setCellValue('A' . $num_ligne, $caract_tab['code_station']);
        $sheet3->setCellValue('B' . $num_ligne, $caract_tab['nom_station']);
        $sheet3->setCellValue('C' . $num_ligne, $date_caract_formated);
        $sheet3->setCellValue('D' . $num_ligne, $caract_tab['prof']);
        $sheet3->setCellValue('E' . $num_ligne, $caract_tab['materiaux_tete']);
        $sheet3->setCellValue('F' . $num_ligne, $caract_tab['dim_tete_ext']);
        $sheet3->setCellValue('G' . $num_ligne, $caract_tab['materiaux_tub_inter']);
        $sheet3->setCellValue('H' . $num_ligne, $diam_tub_inter);
        $sheet3->setCellValue('I' . $num_ligne, $caract_tab['materiaux_dalle']);
        $sheet3->setCellValue('J' . $num_ligne, $caract_tab['dim_dalle']);
        $sheet3->setCellValue('K' . $num_ligne, $dist_capto_tube);
        $sheet3->setCellValue('L' . $num_ligne, $dist_tube_dalle);
        $sheet3->setCellValue('M' . $num_ligne, $dist_dalle_sol);
        $sheet3->setCellValue('N' . $num_ligne, $presence_capot);
        $sheet3->setCellValue('O' . $num_ligne, $caract_tab['etat']);
        $sheet3->setCellValue('P' . $num_ligne, $caract_tab['activite']);
        $sheet3->setCellValue('Q' . $num_ligne, $caract_tab['utilisation']);
        $sheet3->setCellValue('R' . $num_ligne, $caract_tab['equipement_exploitation']);
        $sheet3->setCellValue('S' . $num_ligne, $schema_tete);
        $sheet3->setCellValue('T' . $num_ligne, $caract_tab['obs']);
        $num_ligne++;
    }

    $sheet3->getStyle('A1:T1')->getFont()->setBold(true);
    foreach (range('A', 'T') as $col) { $sheet3->getColumnDimension($col)->setAutoSize(true); }
}


// -----------------------------------------------
// Finalise — remove the default blank sheet and save

$defaultSheet = $spreadsheet->getSheetByName('Worksheet 1');
if ($defaultSheet !== null) {
    $spreadsheet->removeSheetByIndex($spreadsheet->getIndex($defaultSheet));
}
$spreadsheet->setActiveSheetIndex(0);

$writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
$writer->setPreCalculateFormulas(false);

$chemin_folder_process = '../../../' . $chemin_folder;

// The export folder is a bind-mounted volume in the Docker deployment and is
// kept out of git, so it can legitimately be absent on a fresh install.
// Create it rather than letting PhpSpreadsheet die with an uncaught exception.
if (!is_dir($chemin_folder_process))
{
    mkdir($chemin_folder_process, 0755, true);
}

if (!is_dir($chemin_folder_process) || !is_writable($chemin_folder_process))
{
    echo json_encode([
        'statut'  => false,
        'message' => 'Export folder is not writable: ' . $chemin_folder,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Single station: use the station name; multiple: generic name
if ($nb_station > 1)
{
    $Filename = 'InfoStations_' . $today_formatted . '.xlsx';
}
else
{
    $nom_station_filename = ucfirst(strtolower(nettoyerNomFichier($nom_station)));
    $Filename = $nom_station_filename . '_' . $today_formatted . '.xlsx';
}

$writer->save($chemin_folder_process . '/' . $Filename);

$endTime       = microtime(true);
$executionTime = number_format($endTime - $startTime, 1);


// Return JSON response to the client
echo json_encode([
    'statut'        => true,
    'executionTime' => $executionTime,
    'xlsFile'       => $Filename,
], JSON_UNESCAPED_UNICODE);
?>