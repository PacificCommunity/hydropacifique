<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
AJAX endpoint — platform parameter XLSX export
Called by downloadParam_xls() in export_param.php.
Receives JSON: idTerritoire, listParam (comma-separated), cheminFolder.
Generates a multi-sheet XLSX file on the server using PhpSpreadsheet,
then returns its filename to the client for download.

Sheets created depending on listParam values:
  zonegeo   → Regions Geographiques, Communes, Regions Hydrologiques, Rivieres
  typechron → Types Chroniques
  st_nature → Natures Stations
  codequal  → Codes Qualite
  eqjge     → Helices, Moulinets, Saumons

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
$listParam     = $data['listParam'];
$chemin_folder = $data['cheminFolder'];

$paramArray = explode(',', $listParam);


// -----------------------------------------------
// Lookup data — used across multiple sheets

// Territory info
$territoire_query = tep_db_query($sql_link,
    "SELECT DISTINCT t.id_territoire, t.init_territoire, t.nom_territoire, t.theme_region
     FROM " . TABLE_TERRITOIRE . " t
     WHERE t.id_territoire = " . $territoire_id);
$territoire      = tep_db_fetch_array($territoire_query);
$init_territoire = $territoire['init_territoire'];
$nom_territoire  = $territoire['nom_territoire'];
$theme_region    = $territoire['theme_region'];

// Active measurement types (used for time-series and quality code sheets)
$eq_type_array  = [];
$eq_type_query  = tep_db_query($sql_link,
    "SELECT DISTINCT id_eq_type, nom_eq_type
     FROM " . TABLE_EQ_TYPE . "
     WHERE active_eq_type = 1
     ORDER BY order_eq_type ASC");
while ($eq_type_tab = tep_db_fetch_array($eq_type_query))
{
    $eq_type_array[$eq_type_tab['id_eq_type']] = $eq_type_tab['nom_eq_type'];
}


// -----------------------------------------------
// Spreadsheet initialisation

$todayTime        = new DateTime();
$today_formatted  = $todayTime->format('dmY');
$spreadsheet      = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
$startTime        = microtime(true);


// Helper: create a new named worksheet, discard the auto-generated 'Worksheet'

function addSheet($spreadsheet, $title)
{
    $spreadsheet->createSheet();
    $nb = $spreadsheet->getIndex($spreadsheet->getSheetByName('Worksheet'));
    $spreadsheet->setActiveSheetIndex($nb);
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle($title);
    return $sheet;
}

// Helper: bold first row + auto-size all columns in the given range

function formatSheet($sheet, $lastCol)
{
    $sheet->getStyle('A1:' . $lastCol . '1')->getFont()->setBold(true);
    foreach (range('A', $lastCol) as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
}


// -----------------------------------------------
// SHEET 1–4 — Geographic zones

if (in_array('zonegeo', $paramArray))
{
    // Sheet 1: Geographic regions
    $sheet1 = addSheet($spreadsheet, 'Regions Geographiques');
    $sheet1->setCellValue('A1', 'Ident');
    $sheet1->setCellValue('B1', 'Nom Region');

    $num_ligne    = 2;
    $region_array = [];

    $region_query = tep_db_query($sql_link,
        "SELECT DISTINCT id_region, nom_region
         FROM " . TABLE_REGION . "
         WHERE id_territoire = " . $territoire_id);
    while ($region_tab = tep_db_fetch_array($region_query))
    {
        $sheet1->setCellValue('A' . $num_ligne, $region_tab['id_region']);
        $sheet1->setCellValue('B' . $num_ligne, $region_tab['nom_region']);
        $region_array[$region_tab['id_region']] = $region_tab['nom_region'];
        $num_ligne++;
    }
    formatSheet($sheet1, 'B');


    // Sheet 2: Communes
    $sheet2 = addSheet($spreadsheet, 'Communes');
    $sheet2->setCellValue('A1', 'Ident');
    $sheet2->setCellValue('B1', 'Nom Commune');
    $sheet2->setCellValue('C1', 'Ident Region Geo');
    $sheet2->setCellValue('D1', 'Nom Region Geo');

    $num_ligne = 2;
    $commune_query = tep_db_query($sql_link,
        "SELECT DISTINCT c.id_commune, c.nom_commune, c.id_region
         FROM " . TABLE_COMMUNE . " c
         WHERE c.id_territoire = " . $territoire_id . "
         ORDER BY c.nom_commune ASC");
    while ($commune_tab = tep_db_fetch_array($commune_query))
    {
        $sheet2->setCellValue('A' . $num_ligne, $commune_tab['id_commune']);
        $sheet2->setCellValue('B' . $num_ligne, $commune_tab['nom_commune']);
        $sheet2->setCellValue('C' . $num_ligne, $commune_tab['id_region']);
        $sheet2->setCellValue('D' . $num_ligne, $region_array[$commune_tab['id_region']]);
        $num_ligne++;
    }
    formatSheet($sheet2, 'D');


    // Sheet 3: Hydrological regions
    $sheet3 = addSheet($spreadsheet, 'Regions Hydrologiques');
    $sheet3->setCellValue('A1', 'Ident');
    $sheet3->setCellValue('B1', 'Nom Region Hydro');
    $sheet3->setCellValue('C1', 'Description');

    $num_ligne        = 2;
    $regionhydro_array = [];
    $regionhydro_query = tep_db_query($sql_link,
        "SELECT DISTINCT id, nom, description
         FROM " . TABLE_REGIONHYDRO . "
         WHERE id_territoire = " . $territoire_id . "
         ORDER BY LOWER(nom) ASC");
    while ($regionhydro_tab = tep_db_fetch_array($regionhydro_query))
    {
        $sheet3->setCellValue('A' . $num_ligne, $regionhydro_tab['id']);
        $sheet3->setCellValue('B' . $num_ligne, $regionhydro_tab['nom']);
        $sheet3->setCellValue('C' . $num_ligne, $regionhydro_tab['description']);
        $regionhydro_array[$regionhydro_tab['id']] = $regionhydro_tab['nom'];
        $num_ligne++;
    }
    formatSheet($sheet3, 'C');


    // Sheet 4: Rivers
    $sheet4 = addSheet($spreadsheet, 'Rivieres');
    $sheet4->setCellValue('A1', 'Ident');
    $sheet4->setCellValue('B1', 'Nom Riviere');
    $sheet4->setCellValue('C1', 'Description');
    $sheet4->setCellValue('D1', 'Ident Region Hydro');
    $sheet4->setCellValue('E1', 'Nom Region Hydro');

    $num_ligne = 2;
    $riviere_query = tep_db_query($sql_link,
        "SELECT DISTINCT id, nom, description, id_regionhydro
         FROM " . TABLE_RIVIERE . "
         WHERE id_territoire = " . $territoire_id . "
         ORDER BY LOWER(nom) ASC");
    while ($riviere_tab = tep_db_fetch_array($riviere_query))
    {
        $id_rh = $riviere_tab['id_regionhydro'] ?? '';
        $sheet4->setCellValue('A' . $num_ligne, $riviere_tab['id']          ?? '');
        $sheet4->setCellValue('B' . $num_ligne, $riviere_tab['nom']         ?? '');
        $sheet4->setCellValue('C' . $num_ligne, $riviere_tab['description'] ?? '');
        $sheet4->setCellValue('D' . $num_ligne, $id_rh);
        $sheet4->setCellValue('E' . $num_ligne,
            ($id_rh !== '' && isset($regionhydro_array[$id_rh])) ? $regionhydro_array[$id_rh] : '');
        $num_ligne++;
    }
    formatSheet($sheet4, 'E');
}


// -----------------------------------------------
// SHEET 5 — Time-series types

if (in_array('typechron', $paramArray))
{
    $sheet5 = addSheet($spreadsheet, 'Types Chroniques');
    $sheet5->setCellValue('A1', 'Ident');
    $sheet5->setCellValue('B1', 'Initiales');
    $sheet5->setCellValue('C1', 'Nom');
    $sheet5->setCellValue('D1', 'Unite');
    $sheet5->setCellValue('E1', 'Ident Type Donnees');
    $sheet5->setCellValue('F1', 'Nom Type Donnees');

    $num_ligne = 2;
    $type_chron_query = tep_db_query($sql_link,
        "SELECT DISTINCT id_data_type, init_type_data, nom_type_data, id_eq_type_data, unite
         FROM " . TABLE_TYPE_DATA . "
         ORDER BY id_eq_type_data, init_type_data ASC");
    while ($type_chron_tab = tep_db_fetch_array($type_chron_query))
    {
        $chron_idtypedata = '';
        $chron_typedata   = '';
        if (isset($eq_type_array[$type_chron_tab['id_eq_type_data']]))
        {
            $chron_idtypedata = $type_chron_tab['id_eq_type_data'];
            $chron_typedata   = $eq_type_array[$type_chron_tab['id_eq_type_data']];
        }
        $sheet5->setCellValue('A' . $num_ligne, $type_chron_tab['id_data_type']);
        $sheet5->setCellValue('B' . $num_ligne, $type_chron_tab['init_type_data']);
        $sheet5->setCellValue('C' . $num_ligne, $type_chron_tab['nom_type_data']);
        $sheet5->setCellValue('D' . $num_ligne, $type_chron_tab['unite']);
        $sheet5->setCellValue('E' . $num_ligne, $chron_idtypedata);
        $sheet5->setCellValue('F' . $num_ligne, $chron_typedata);
        $num_ligne++;
    }
    formatSheet($sheet5, 'F');
}


// -----------------------------------------------
// SHEET 6 — Station natures

if (in_array('st_nature', $paramArray))
{
    $sheet6 = addSheet($spreadsheet, 'Natures Stations');
    $sheet6->setCellValue('A1', 'Ident');
    $sheet6->setCellValue('B1', 'Libelle');

    $num_ligne = 2;
    $nature_query = tep_db_query($sql_link,
        "SELECT DISTINCT id, libelle
         FROM " . TABLE_STATION_NATURE . "
         ORDER BY libelle ASC");
    while ($nature_tab = tep_db_fetch_array($nature_query))
    {
        $sheet6->setCellValue('A' . $num_ligne, $nature_tab['id']);
        $sheet6->setCellValue('B' . $num_ligne, $nature_tab['libelle']);
        $num_ligne++;
    }
    formatSheet($sheet6, 'B');
}


// -----------------------------------------------
// SHEET 7 — Quality codes

if (in_array('codequal', $paramArray))
{
    $sheet7 = addSheet($spreadsheet, 'Codes Qualite');
    $sheet7->setCellValue('A1', 'Ident');
    $sheet7->setCellValue('B1', 'Initiales');
    $sheet7->setCellValue('C1', 'Nom');
    $sheet7->setCellValue('D1', 'Informations');
    $sheet7->setCellValue('E1', 'Ident Type Donnees');
    $sheet7->setCellValue('F1', 'Nom Type Donnees');

    $num_ligne = 2;
    $quality_query = tep_db_query($sql_link,
        "SELECT DISTINCT id_data_qualite, init_qualite_data, nom_qualite_data, info_qualite_data, id_eq_type
         FROM " . TABLE_DATA_QUALITE . "
         WHERE init_qualite_data <> ''
         ORDER BY id_eq_type ASC, init_qualite_data ASC");
    while ($quality_tab = tep_db_fetch_array($quality_query))
    {
        $codequal_idtypedata = '';
        $codequal_typedata   = '';
        if (isset($eq_type_array[$quality_tab['id_eq_type']]))
        {
            $codequal_idtypedata = $quality_tab['id_eq_type'];
            $codequal_typedata   = $eq_type_array[$quality_tab['id_eq_type']];
        }
        $sheet7->setCellValue('A' . $num_ligne, $quality_tab['id_data_qualite']);
        $sheet7->setCellValue('B' . $num_ligne, $quality_tab['init_qualite_data']);
        $sheet7->setCellValue('C' . $num_ligne, $quality_tab['nom_qualite_data']);
        $sheet7->setCellValue('D' . $num_ligne, $quality_tab['info_qualite_data']);
        $sheet7->setCellValue('E' . $num_ligne, $codequal_idtypedata);
        $sheet7->setCellValue('F' . $num_ligne, $codequal_typedata);
        $num_ligne++;
    }
    formatSheet($sheet7, 'F');
}


// -----------------------------------------------
// SHEETS 8–10 — Gauging equipment

if (in_array('eqjge', $paramArray))
{
    // Sheet 8: Propellers
    $sheet8 = addSheet($spreadsheet, 'Helices');
    $sheet8->setCellValue('A1', 'Ident');
    $sheet8->setCellValue('B1', 'Numero');
    $sheet8->setCellValue('C1', 'Diametre');
    $sheet8->setCellValue('D1', 'Pas');
    $sheet8->setCellValue('E1', 'l1');
    $sheet8->setCellValue('F1', 'a1');
    $sheet8->setCellValue('G1', 'b1');
    $sheet8->setCellValue('H1', 'l2');
    $sheet8->setCellValue('I1', 'a2');
    $sheet8->setCellValue('J1', 'b2');
    $sheet8->setCellValue('K1', 'a3');
    $sheet8->setCellValue('L1', 'b3');
    $sheet8->setCellValue('M1', 'Fabricant');
    $sheet8->setCellValue('N1', 'Observation');

    $num_ligne = 2;
    $helice_query = tep_db_query($sql_link,
        "SELECT DISTINCT id, num, diametre, pas, l1, a1, b1, l2, a2, b2, a3, b3, fabricant, obs
         FROM " . TABLE_HELICE . "
         ORDER BY num ASC");
    while ($helice_tab = tep_db_fetch_array($helice_query))
    {
        // Only write numeric values > 0; empty string otherwise
        $diametre = ($helice_tab['diametre'] > 0) ? $helice_tab['diametre'] : '';
        $pas      = ($helice_tab['pas']      > 0) ? $helice_tab['pas']      : '';
        $l1       = ($helice_tab['l1']       > 0) ? $helice_tab['l1']       : '';
        $a1       = ($helice_tab['a1']       > 0) ? $helice_tab['a1']       : '';
        $b1       = ($helice_tab['b1']       > 0) ? $helice_tab['b1']       : '';
        $l2       = ($helice_tab['l2']       > 0) ? $helice_tab['l2']       : '';
        $a2       = ($helice_tab['a2']       > 0) ? $helice_tab['a2']       : '';
        $b2       = ($helice_tab['b2']       > 0) ? $helice_tab['b2']       : '';
        $a3       = ($helice_tab['a3']       > 0) ? $helice_tab['a3']       : '';
        $b3       = ($helice_tab['b3']       > 0) ? $helice_tab['b3']       : '';

        $sheet8->setCellValue('A' . $num_ligne, $helice_tab['id']);
        $sheet8->setCellValue('B' . $num_ligne, $helice_tab['num']);
        $sheet8->setCellValue('C' . $num_ligne, $diametre);
        $sheet8->setCellValue('D' . $num_ligne, $pas);
        $sheet8->setCellValue('E' . $num_ligne, $l1);
        $sheet8->setCellValue('F' . $num_ligne, $a1);
        $sheet8->setCellValue('G' . $num_ligne, $b1);
        $sheet8->setCellValue('H' . $num_ligne, $l2);
        $sheet8->setCellValue('I' . $num_ligne, $a2);
        $sheet8->setCellValue('J' . $num_ligne, $b2);
        $sheet8->setCellValue('K' . $num_ligne, $a3);
        $sheet8->setCellValue('L' . $num_ligne, $b3);
        $sheet8->setCellValue('M' . $num_ligne, $helice_tab['fabricant']);
        $sheet8->setCellValue('N' . $num_ligne, $helice_tab['obs']);
        $num_ligne++;
    }
    formatSheet($sheet8, 'N');


    // Sheet 9: Current meters
    $sheet9 = addSheet($spreadsheet, 'Moulinets');
    $sheet9->setCellValue('A1', 'Ident');
    $sheet9->setCellValue('B1', 'Numero');
    $sheet9->setCellValue('C1', 'Fabricant');
    $sheet9->setCellValue('D1', 'Observation');

    $num_ligne = 2;
    $moulinet_query = tep_db_query($sql_link,
        "SELECT DISTINCT id, num, fabricant, obs
         FROM " . TABLE_MOULINET);
    while ($moulinet_tab = tep_db_fetch_array($moulinet_query))
    {
        $sheet9->setCellValue('A' . $num_ligne, $moulinet_tab['id']);
        $sheet9->setCellValue('B' . $num_ligne, $moulinet_tab['num']);
        $sheet9->setCellValue('C' . $num_ligne, $moulinet_tab['fabricant']);
        $sheet9->setCellValue('D' . $num_ligne, $moulinet_tab['obs']);
        $num_ligne++;
    }
    formatSheet($sheet9, 'D');


    // Sheet 10: Weights
    $sheet10 = addSheet($spreadsheet, 'Saumons');
    $sheet10->setCellValue('A1', 'Ident');
    $sheet10->setCellValue('B1', 'Numero');
    $sheet10->setCellValue('C1', 'Titre');
    $sheet10->setCellValue('D1', 'Points');
    $sheet10->setCellValue('E1', 'Distance axe');
    $sheet10->setCellValue('F1', 'T air');
    $sheet10->setCellValue('G1', 'R distance');
    $sheet10->setCellValue('H1', 'Fabricant');
    $sheet10->setCellValue('I1', 'Observation');

    $num_ligne = 2;
    $saumon_query = tep_db_query($sql_link,
        "SELECT DISTINCT id, num, titre, poids, distance_axe, t_air, r_dist, fabricant, obs
         FROM " . TABLE_SAUMON . "
         ORDER BY num ASC");
    while ($saumon_tab = tep_db_fetch_array($saumon_query))
    {
        $sheet10->setCellValue('A' . $num_ligne, $saumon_tab['id']);
        $sheet10->setCellValue('B' . $num_ligne, $saumon_tab['num']);
        $sheet10->setCellValue('C' . $num_ligne, $saumon_tab['titre']);
        $sheet10->setCellValue('D' . $num_ligne, $saumon_tab['poids']);
        $sheet10->setCellValue('E' . $num_ligne, $saumon_tab['distance_axe']);
        $sheet10->setCellValue('F' . $num_ligne, $saumon_tab['t_air']);
        $sheet10->setCellValue('G' . $num_ligne, $saumon_tab['r_dist']);
        $sheet10->setCellValue('H' . $num_ligne, $saumon_tab['fabricant']);
        $sheet10->setCellValue('I' . $num_ligne, $saumon_tab['obs']);
        $num_ligne++;
    }
    formatSheet($sheet10, 'I');
}


// -----------------------------------------------
// Finalise the spreadsheet — remove the default blank sheet and save

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
$Filename              = 'HP_Parametres_' . $today_formatted . '.xlsx';
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
