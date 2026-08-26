<?php
/*
----------------------------------------
Copyright (c) 2025 - Vai-Natura
----------------------------------------
Statistics views XLSX generator (factored by 'view')
- Builds an Excel workbook for a stats view (annual / monthly / daily):
    * Sheet "Synthese" : General data foreword (period, station, chronicle,
      N / min / max / mean / std)
    * one or more data sheets depending on the view
- Follows the PhpSpreadsheet technique of process_station_download_xls.php.

Returns JSON: { statut, xlsFile }
----------------------------------------
*/

require('../../config.php');
require('../../database_tables.php');

require('../../function/date.php');
require('../../function/database.php');
require('../../function/html_output.php');
require('../../function/general.php');

require('../../function/math.php');
require('../../function/stats.php');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

header('Content-Type: text/html; charset=utf-8');

$sql_link = mysqli_connect(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD, DB_DATABASE)
    or die('Cannot connect to the database');
mysqli_query($sql_link, 'SET NAMES UTF8');

require('../../text_content_' . LANGUAGE . '.php');


// ----------------------------------------------
// Parse incoming JSON payload

$dataGraph = json_decode(file_get_contents('php://input'), true);

$cle_station  = $dataGraph['cle_station'];
$type_station = $dataGraph['type_station'];
$id_typedata  = $dataGraph['id_typedata'];
$min_x        = $dataGraph['min_x'];
$max_x        = $dataGraph['max_x'];
$view         = isset($dataGraph['view']) ? $dataGraph['view'] : 'byyear';

$date         = DateTime::createFromFormat('d-m-Y', $min_x);
$format_min_x = $date->format('Y-m-d');
$date         = DateTime::createFromFormat('d-m-Y', $max_x);
$format_max_x = $date->format('Y-m-d');


// ----------------------------------------------
// Station + chronicle + axis

$station_tab = tep_db_fetch_array(tep_db_query($sql_link,
    "SELECT id_station, nom_station, code_station FROM " . TABLE_STATION . " WHERE id_station = " . $cle_station));
$nom_station  = html_entity_decode($station_tab['nom_station']  ?? '');
$code_station = html_entity_decode($station_tab['code_station'] ?? '');

$type_chron_tab = tep_db_fetch_array(tep_db_query($sql_link,
    "SELECT init_type_data, nom_type_data, id_eq_type_data, axe_data, unite
     FROM " . TABLE_TYPE_DATA . " WHERE id_data_type = " . $id_typedata . " ORDER BY init_type_data ASC"));
$id_eq_type_data = $type_chron_tab['id_eq_type_data'] ?? '';
$init_type_data  = $type_chron_tab['init_type_data']  ?? '';
$nom_type_data   = $type_chron_tab['nom_type_data']   ?? '';
$axe_data        = $type_chron_tab['axe_data']        ?? '';

$axe_tab = tep_db_fetch_array(tep_db_query($sql_link,
    "SELECT axe, unite, nb_round FROM " . TABLE_DATA_TYPE_AXE . " WHERE id = " . $axe_data));
$axe       = $axe_tab['axe']   ?? '';
$axe_unite = $axe_tab['unite'] ?? '';
$nb_dec    = isset($axe_tab['nb_round']) ? (int)$axe_tab['nb_round'] : 0;


// ----------------------------------------------
// Overall figures (foreword)

$ov = tep_db_fetch_array(tep_db_query($sql_link,
    "SELECT COUNT(ABS(da.valeur)) AS n, MIN(ABS(da.valeur)) AS vmin, MAX(ABS(da.valeur)) AS vmax,
            AVG(ABS(da.valeur)) AS vmoy, STD(ABS(da.valeur)) AS vstd
     FROM " . TABLE_DATA_ALL  . " da
     JOIN " . TABLE_DATA_META . " dm ON da.id_meta = dm.id
     WHERE dm.id_typedata = " . $id_typedata . "
     AND   dm.id_station  = " . $cle_station . "
     AND   da.dateheure  >= '" . $format_min_x . "'
     AND   da.dateheure  <= '" . $format_max_x . "'
     AND   da.valeur NOT IN (9999, -9999, 8888, -8888, 99999, -99999, 88888, -88888)"));
$g_n   = (int)($ov['n'] ?? 0);
$g_min = round((float)($ov['vmin'] ?? 0), $nb_dec);
$g_max = round((float)($ov['vmax'] ?? 0), $nb_dec);
$g_moy = round((float)($ov['vmoy'] ?? 0), $nb_dec);
$g_std = round((float)($ov['vstd'] ?? 0), $nb_dec);

$d1 = new DateTime($format_min_x);
$d2 = new DateTime($format_max_x);
$itv = $d1->diff($d2);
$duree = $itv->y . ' ' . TEXT_STATS_DURATION_YEARS;
if ($itv->m > 0) { $duree .= ' ' . TEXT_STATS_DURATION_AND . ' ' . $itv->m . ' ' . TEXT_STATS_DURATION_MONTHS; }

$view_label = TEXT_STATS_TITLE;
switch ($view) {
    case 'byyear':  $view_label = TEXT_STATS_BTN_BYYEAR;  break;
    case 'bymonth': $view_label = TEXT_STATS_BTN_BYMONTH; break;
    case 'bydays':  $view_label = TEXT_STATS_BTN_BYDAYS;  break;
}

$mois = [1=>TEXT_MONTH_SHORT_JAN,2=>TEXT_MONTH_SHORT_FEB,3=>TEXT_MONTH_SHORT_MAR,
         4=>TEXT_MONTH_SHORT_APR,5=>TEXT_MONTH_SHORT_MAY,6=>TEXT_MONTH_SHORT_JUN,
         7=>TEXT_MONTH_SHORT_JUL,8=>TEXT_MONTH_SHORT_AUG,9=>TEXT_MONTH_SHORT_SEP,
         10=>TEXT_MONTH_SHORT_OCT,11=>TEXT_MONTH_SHORT_NOV,12=>TEXT_MONTH_SHORT_DEC];


// ----------------------------------------------
// Build the workbook

$spreadsheet = new Spreadsheet();

// ---- Sheet 1: foreword ----
$s0 = $spreadsheet->getActiveSheet();
$s0->setTitle(substr(TEXT_STATS_TITLE, 0, 28));

$s0->setCellValue('A1', TEXT_STATS_TITLE . ' - ' . $view_label);
$s0->getStyle('A1')->getFont()->setBold(true)->setSize(14);

$s0->setCellValue('A3', TEXT_STATS_STATION);   $s0->setCellValue('B3', $code_station . ' - ' . $nom_station);
$s0->setCellValue('A4', TEXT_STATS_CHRONIQUE); $s0->setCellValue('B4', $init_type_data . ' - ' . $nom_type_data);
$s0->setCellValue('A5', TEXT_STATS_PERIOD);    $s0->setCellValue('B5', $min_x . ' / ' . $max_x);
$s0->setCellValue('A6', TEXT_STATS_DURATION);  $s0->setCellValue('B6', $duree);
$s0->setCellValue('A7', TEXT_STATS_DATA);      $s0->setCellValue('B7', $axe . ' (' . $axe_unite . ')');
$s0->getStyle('A3:A7')->getFont()->setBold(true);

$s0->setCellValue('A9',  TEXT_STATS_CARD_N);   $s0->setCellValue('B9', $g_n);
$s0->setCellValue('A10', TEXT_STATS_MINIMUM);  $s0->setCellValue('B10', $g_min);
$s0->setCellValue('A11', TEXT_STATS_MAXIMUM);  $s0->setCellValue('B11', $g_max);
$s0->setCellValue('A12', TEXT_STATS_MEAN);     $s0->setCellValue('B12', $g_moy);
$s0->setCellValue('A13', TEXT_STATS_STD_DEV);  $s0->setCellValue('B13', $g_std);
$s0->getStyle('A9:A13')->getFont()->setBold(true);

foreach (range('A', 'B') as $col) { $s0->getColumnDimension($col)->setAutoSize(true); }


// ---- Data sheet(s) per view ----
switch ($view)
{
    // ===================== ANNUAL =====================
    case 'byyear':
    default:
        $sh = $spreadsheet->createSheet();
        $sh->setTitle(substr($view_label, 0, 28));

        $q = tep_db_query($sql_link,
            "SELECT YEAR(da.dateheure) AS year, SUM(ABS(da.valeur)) AS cumul, AVG(ABS(da.valeur)) AS moy,
                    STD(ABS(da.valeur)) AS std, MIN(ABS(da.valeur)) AS vmin, MAX(ABS(da.valeur)) AS vmax
             FROM " . TABLE_DATA_ALL  . " da
             JOIN " . TABLE_DATA_META . " dm ON da.id_meta = dm.id
             WHERE dm.id_typedata = " . $id_typedata . "
             AND   dm.id_station  = " . $cle_station . "
             AND   da.dateheure  >= '" . $format_min_x . "'
             AND   da.dateheure  <= '" . $format_max_x . "'
             AND   da.valeur NOT IN (9999, -9999, 8888, -8888, 99999, -99999, 88888, -88888)
             GROUP BY YEAR(da.dateheure) ORDER BY year DESC");

        if ($id_eq_type_data == 1) {
            $sh->fromArray([TEXT_STATS_YEAR, TEXT_STATS_CUMUL . ' (' . $axe_unite . ')'], null, 'A1');
            $sh->getStyle('A1:B1')->getFont()->setBold(true);
            $row = 2;
            while ($r = tep_db_fetch_array($q)) {
                $sh->setCellValue('A' . $row, (int)$r['year']);
                $sh->setCellValue('B' . $row, round((float)$r['cumul'], $nb_dec));
                $row++;
            }
        } else {
            $sh->fromArray([TEXT_STATS_YEAR, TEXT_STATS_MINIMUM, TEXT_STATS_MAXIMUM, TEXT_STATS_MEAN, TEXT_STATS_STD_DEV], null, 'A1');
            $sh->getStyle('A1:E1')->getFont()->setBold(true);
            $row = 2;
            while ($r = tep_db_fetch_array($q)) {
                $sh->setCellValue('A' . $row, (int)$r['year']);
                $sh->setCellValue('B' . $row, round((float)$r['vmin'], $nb_dec));
                $sh->setCellValue('C' . $row, round((float)$r['vmax'], $nb_dec));
                $sh->setCellValue('D' . $row, round((float)$r['moy'],  $nb_dec));
                $sh->setCellValue('E' . $row, round((float)$r['std'],  $nb_dec));
                $row++;
            }
        }
        foreach (range('A', 'E') as $col) { $sh->getColumnDimension($col)->setAutoSize(true); }
        break;

    // ===================== MONTHLY =====================
    case 'bymonth':
        $calc = ($id_eq_type_data == 1) ? "SUM(ABS(da.valeur))" : "AVG(ABS(da.valeur))";
        $q = tep_db_query($sql_link,
            "SELECT YEAR(da.dateheure) AS year, MONTH(da.dateheure) AS month, " . $calc . " AS v
             FROM " . TABLE_DATA_ALL  . " da
             JOIN " . TABLE_DATA_META . " dm ON da.id_meta = dm.id
             WHERE dm.id_typedata = " . $id_typedata . "
             AND   dm.id_station  = " . $cle_station . "
             AND   da.dateheure  >= '" . $format_min_x . "'
             AND   da.dateheure  <= '" . $format_max_x . "'
             AND   da.valeur NOT IN (9999, -9999, 8888, -8888, 99999, -99999, 88888, -88888)
             GROUP BY YEAR(da.dateheure), MONTH(da.dateheure)");
        $smy = array();
        while ($r = tep_db_fetch_array($q)) { $smy[(int)$r['month']][(int)$r['year']] = (float)$r['v']; }

        $all_years = array();
        foreach ($smy as $md) { $all_years = array_merge($all_years, array_keys($md)); }
        $all_years = array_unique($all_years); rsort($all_years);

        // Sheet A: statistics (rows = stat lines)
        $shA = $spreadsheet->createSheet();
        $shA->setTitle(substr(TEXT_STATS_MONTHLY_SUMMARY, 0, 28));
        $stat_lines = ['mean'=>TEXT_STATS_MEAN,'min'=>TEXT_STATS_MINIMUM,'5pc'=>TEXT_STATS_PERCENTILE_5,
                       '10pc'=>TEXT_STATS_PERCENTILE_10,'50pc'=>TEXT_STATS_MEDIAN,'90pc'=>TEXT_STATS_PERCENTILE_90,
                       '95pc'=>TEXT_STATS_PERCENTILE_95,'max'=>TEXT_STATS_MAXIMUM];
        $headerA = ['']; foreach ($mois as $l) { $headerA[] = $l; }
        $shA->fromArray($headerA, null, 'A1');
        $shA->getStyle('A1:M1')->getFont()->setBold(true);

        $sbm = array();
        foreach (range(1,12) as $mn) {
            $vals = isset($smy[$mn]) ? array_values($smy[$mn]) : [];
            $sbm[$mn] = (count($vals) > 0) ? [
                'mean'=>mean($vals),'min'=>min($vals),'max'=>max($vals),
                '5pc'=>calculate_percentile($vals,5.0),'10pc'=>calculate_percentile($vals,10.0),
                '50pc'=>calculate_percentile($vals,50.0),'90pc'=>calculate_percentile($vals,90.0),
                '95pc'=>calculate_percentile($vals,95.0),
            ] : null;
        }
        $row = 2;
        foreach ($stat_lines as $key => $label) {
            $line = [$label];
            foreach (range(1,12) as $mn) {
                $v = $sbm[$mn][$key] ?? null;
                $line[] = ($v !== null) ? round($v, $nb_dec) : '';
            }
            $shA->fromArray($line, null, 'A' . $row);
            $row++;
        }
        foreach (range('A', 'M') as $col) { $shA->getColumnDimension($col)->setAutoSize(true); }

        // Sheet B: years (rows = years)
        $shB = $spreadsheet->createSheet();
        $shB->setTitle(substr(TEXT_STATS_YEAR, 0, 28));
        $headerB = [TEXT_STATS_YEAR]; foreach ($mois as $l) { $headerB[] = $l; }
        $shB->fromArray($headerB, null, 'A1');
        $shB->getStyle('A1:M1')->getFont()->setBold(true);
        $row = 2;
        foreach ($all_years as $yr) {
            $line = [$yr];
            foreach (range(1,12) as $mn) {
                $v = $smy[$mn][$yr] ?? null;
                $line[] = ($v !== null) ? round($v, $nb_dec) : '';
            }
            $shB->fromArray($line, null, 'A' . $row);
            $row++;
        }
        foreach (range('A', 'M') as $col) { $shB->getColumnDimension($col)->setAutoSize(true); }
        break;

    // ===================== DAILY =====================
    case 'bydays':
        $year_first = (int) (new DateTime($format_min_x))->format('Y');
        $year_last  = (int) (new DateTime($format_max_x))->format('Y');
        $calc = ($id_eq_type_data == 1) ? "SUM(ABS(da.valeur))" : "AVG(ABS(da.valeur))";

        for ($yr = $year_last; $yr >= $year_first; $yr--) {
            $q = tep_db_query($sql_link,
                "SELECT DAY(da.dateheure) AS day, MONTH(da.dateheure) AS month, " . $calc . " AS v
                 FROM " . TABLE_DATA_ALL  . " da
                 JOIN " . TABLE_DATA_META . " dm ON da.id_meta = dm.id
                 WHERE dm.id_typedata = " . $id_typedata . "
                 AND   dm.id_station  = " . $cle_station . "
                 AND   da.dateheure  >= '" . $yr . "-01-01'
                 AND   da.dateheure  <= '" . $yr . "-12-31'
                 AND   da.valeur NOT IN (9999, -9999, 8888, -8888, 99999, -99999, 88888, -88888)
                 GROUP BY DAY(da.dateheure), MONTH(da.dateheure)");
            $org = array();
            while ($r = tep_db_fetch_array($q)) { $org[(int)$r['month']][(int)$r['day']] = round((float)$r['v'], $nb_dec); }
            if (empty($org)) { continue; }

            $sh = $spreadsheet->createSheet();
            $sh->setTitle((string)$yr);
            $header = [TEXT_STATS_DAY]; foreach ($mois as $l) { $header[] = $l; }
            $sh->fromArray($header, null, 'A1');
            $sh->getStyle('A1:M1')->getFont()->setBold(true);

            $row = 2;
            for ($day = 1; $day <= 31; $day++) {
                $line = [$day];
                for ($m = 1; $m <= 12; $m++) { $line[] = isset($org[$m][$day]) ? $org[$m][$day] : ''; }
                $sh->fromArray($line, null, 'A' . $row);
                $row++;
            }
            foreach (range('A', 'M') as $col) { $sh->getColumnDimension($col)->setAutoSize(true); }
        }
        break;
}

$spreadsheet->setActiveSheetIndex(0);


// ----------------------------------------------
// Save + JSON response

$writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
$writer->setPreCalculateFormulas(false);

$Filename = nettoyerNomFichier($code_station) . "_" . nettoyerNomFichier($nom_station)
          . "_" . nettoyerNomFichier($init_type_data) . "_" . nettoyerNomFichier($view_label) . ".xlsx";
$filePath = $_SERVER['DOCUMENT_ROOT'] . "/" . DIR_WS_PDF . $Filename;

$writer->save($filePath);

echo json_encode([
    'statut'  => true,
    'xlsFile' => $Filename,
], JSON_UNESCAPED_UNICODE);
?>