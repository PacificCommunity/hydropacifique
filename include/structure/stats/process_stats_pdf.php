<?php
/*
----------------------------------------
Copyright (c) 2025 - Vai-Natura
----------------------------------------
Statistics views PDF generator (factored by 'view')
- Builds a PDF for a stats view (annual / monthly / daily) with:
    * a "General data" foreword (period, duration, station, chronicle,
      min / max / mean / std)
    * the chart captured client-side from Plotly (PNG, base64)
    * the view's data table
- Modeled on process_station_pdf.php (same mPDF shell / header / footer / CSS).
----------------------------------------
*/

// ----------------------------------------------
// Required files for script configuration

require('../../config.php');
require('../../database_tables.php');

require('../../function/date.php');
require('../../function/database.php');
require('../../function/html_output.php');
require('../../function/general.php');

require('../../function/math.php');
require('../../function/stats.php');

use Mpdf\Mpdf;

header('Content-Type: text/html; charset=utf-8');

$sql_link = mysqli_connect(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD, DB_DATABASE)
    or die('Impossible de se connecter à la base de données!');
mysqli_query($sql_link, 'SET NAMES UTF8');

require('../../text_content_' . LANGUAGE . '.php');


// ----------------------------------------------
// Parse incoming JSON payload

$jsonData  = file_get_contents('php://input');
$dataGraph = json_decode($jsonData, true);

$territoire_id = $dataGraph['territoireId'];
$cle_station   = $dataGraph['cle_station'];
$type_station  = $dataGraph['type_station'];
$id_typedata   = $dataGraph['id_typedata'];
$min_x         = $dataGraph['min_x'];
$max_x         = $dataGraph['max_x'];
$view          = isset($dataGraph['view'])     ? $dataGraph['view']     : 'byyear';
$chartPng      = isset($dataGraph['chartPng']) ? $dataGraph['chartPng'] : '';

$date         = DateTime::createFromFormat('d-m-Y', $min_x);
$format_min_x = $date->format('Y-m-d');
$date         = DateTime::createFromFormat('d-m-Y', $max_x);
$format_max_x = $date->format('Y-m-d');


// ----------------------------------------------
// Station info

$sql_station = "SELECT DISTINCT id_station, nom_station, code_station
                FROM " . TABLE_STATION . "
                WHERE id_station = " . $cle_station;
$station_query = tep_db_query($sql_link, $sql_station);
$station_tab   = tep_db_fetch_array($station_query);

$nom_station  = html_entity_decode($station_tab['nom_station']  ?? '');
$code_station = html_entity_decode($station_tab['code_station'] ?? '');


// ----------------------------------------------
// Chronicle type + axis (label / unit / rounding)

$sql_type_chron = "SELECT DISTINCT id_data_type, init_type_data, nom_type_data, id_eq_type_data, axe_data, unite
                   FROM " . TABLE_TYPE_DATA . "
                   WHERE id_data_type = " . $id_typedata . "
                   ORDER BY init_type_data ASC";
$type_chron_query = tep_db_query($sql_link, $sql_type_chron);
$type_chron_tab   = tep_db_fetch_array($type_chron_query);

$id_eq_type_data = $type_chron_tab['id_eq_type_data'] ?? '';
$init_type_data  = $type_chron_tab['init_type_data']  ?? '';
$nom_type_data   = $type_chron_tab['nom_type_data']   ?? '';
$axe_data        = $type_chron_tab['axe_data']        ?? '';

$sql_data_type_axe = "SELECT DISTINCT id, axe, unite, nb_round
                      FROM " . TABLE_DATA_TYPE_AXE . "
                      WHERE id = " . $axe_data;
$data_type_axe_query = tep_db_query($sql_link, $sql_data_type_axe);
$data_type_axe_tab   = tep_db_fetch_array($data_type_axe_query);

$axe         = $data_type_axe_tab['axe']      ?? '';
$axe_unite   = $data_type_axe_tab['unite']    ?? '';
$nb_dec      = isset($data_type_axe_tab['nb_round']) ? (int)$data_type_axe_tab['nb_round'] : 0;


// ----------------------------------------------
// Foreword: general statistics over the whole period

$sql_overall = "SELECT
                    COUNT(ABS(da.valeur)) AS n,
                    MIN(ABS(da.valeur))   AS vmin,
                    MAX(ABS(da.valeur))   AS vmax,
                    AVG(ABS(da.valeur))   AS vmoy,
                    STD(ABS(da.valeur))   AS vstd,
                    SUM(ABS(da.valeur))   AS vcumul
                FROM " . TABLE_DATA_ALL  . " da
                JOIN " . TABLE_DATA_META . " dm ON da.id_meta = dm.id
                WHERE dm.id_typedata = " . $id_typedata . "
                AND   dm.id_station  = " . $cle_station . "
                AND   da.dateheure  >= '" . $format_min_x . "'
                AND   da.dateheure  <= '" . $format_max_x . "'
                AND   da.valeur NOT IN (9999, -9999, 8888, -8888, 99999, -99999, 88888, -88888)";
$overall_query = tep_db_query($sql_link, $sql_overall);
$overall_tab   = tep_db_fetch_array($overall_query);

$g_n     = (int)($overall_tab['n'] ?? 0);
$g_min   = round((float)($overall_tab['vmin']   ?? 0), $nb_dec);
$g_max   = round((float)($overall_tab['vmax']   ?? 0), $nb_dec);
$g_moy   = round((float)($overall_tab['vmoy']   ?? 0), $nb_dec);
$g_std   = round((float)($overall_tab['vstd']   ?? 0), $nb_dec);
$g_cumul = round((float)($overall_tab['vcumul'] ?? 0), $nb_dec);

// Human-readable duration
$d1 = new DateTime($format_min_x);
$d2 = new DateTime($format_max_x);
$itv = $d1->diff($d2);
$duree = $itv->y . ' ' . TEXT_STATS_DURATION_YEARS;
if ($itv->m > 0) { $duree .= ' ' . TEXT_STATS_DURATION_AND . ' ' . $itv->m . ' ' . TEXT_STATS_DURATION_MONTHS; }


// ----------------------------------------------
// View-specific data table (HTML)

$view_label = TEXT_STATS_TITLE;
$table_html = '';

switch ($view)
{
    // ===================== ANNUAL =====================
    case 'byyear':
    default:
        $view_label = TEXT_STATS_BTN_BYYEAR;

        $sql_year = "SELECT
                        YEAR(da.dateheure) AS year,
                        SUM(ABS(da.valeur)) AS cumul,
                        AVG(ABS(da.valeur)) AS moy,
                        STD(ABS(da.valeur)) AS std,
                        MIN(ABS(da.valeur)) AS vmin,
                        MAX(ABS(da.valeur)) AS vmax
                     FROM " . TABLE_DATA_ALL  . " da
                     JOIN " . TABLE_DATA_META . " dm ON da.id_meta = dm.id
                     WHERE dm.id_typedata = " . $id_typedata . "
                     AND   dm.id_station  = " . $cle_station . "
                     AND   da.dateheure  >= '" . $format_min_x . "'
                     AND   da.dateheure  <= '" . $format_max_x . "'
                     AND   da.valeur NOT IN (9999, -9999, 8888, -8888, 99999, -99999, 88888, -88888)
                     GROUP BY YEAR(da.dateheure)
                     ORDER BY year DESC";
        $year_query = tep_db_query($sql_link, $sql_year);

        // Collect rows, then group by calendar decade (recent first).
        $rows_year = array();
        while ($r = tep_db_fetch_array($year_query)) {
            $rows_year[(int)$r['year']] = $r;
        }

        $by_decade = array();
        foreach ($rows_year as $yr => $r) {
            $dec = (int)(floor($yr / 10) * 10);
            $by_decade[$dec][$yr] = $r;
        }
        krsort($by_decade);

        // Render one small table per decade.
        $decade_tables = array();
        foreach ($by_decade as $dec => $years) {
            krsort($years);
            $label = $dec . " - " . ($dec + 9);

            if ($id_eq_type_data == 1) {
                $t = "<table border='1' cellspacing='0' cellpadding='3'
                         style='width:100%;border-collapse:collapse;font-size:10px;'>
                        <tr style='background-color:#eef3f8;font-weight:bold;'>
                            <th colspan='2' style='text-align:center;'>" . $label . "</th>
                        </tr>
                        <tr style='background-color:#f4f7fa;font-weight:bold;'>
                            <th style='text-align:center;'>" . TEXT_STATS_YEAR . "</th>
                            <th style='text-align:center;'>" . TEXT_STATS_CUMUL . "</th>
                        </tr>";
                foreach ($years as $yr => $r) {
                    $t .= "<tr>"
                        . "<td style='text-align:center;font-weight:bold;'>" . $yr . "</td>"
                        . "<td style='text-align:center;'>" . round((float)$r['cumul'], $nb_dec) . "</td>"
                        . "</tr>";
                }
                $t .= "</table>";
            } else {
                $t = "<table border='1' cellspacing='0' cellpadding='3'
                         style='width:100%;border-collapse:collapse;font-size:10px;'>
                        <tr style='background-color:#eef3f8;font-weight:bold;'>
                            <th colspan='5' style='text-align:center;'>" . $label . "</th>
                        </tr>
                        <tr style='background-color:#f4f7fa;font-weight:bold;'>
                            <th style='text-align:center;'>" . TEXT_STATS_YEAR . "</th>
                            <th style='text-align:center;'>" . TEXT_STATS_MINIMUM . "</th>
                            <th style='text-align:center;'>" . TEXT_STATS_MAXIMUM . "</th>
                            <th style='text-align:center;'>" . TEXT_STATS_MEAN . "</th>
                            <th style='text-align:center;'>" . TEXT_STATS_STD_DEV . "</th>
                        </tr>";
                foreach ($years as $yr => $r) {
                    $t .= "<tr>"
                        . "<td style='text-align:center;font-weight:bold;'>" . $yr . "</td>"
                        . "<td style='text-align:center;'>" . round((float)$r['vmin'], $nb_dec) . "</td>"
                        . "<td style='text-align:center;'>" . round((float)$r['vmax'], $nb_dec) . "</td>"
                        . "<td style='text-align:center;'>" . round((float)$r['moy'],  $nb_dec) . "</td>"
                        . "<td style='text-align:center;'>" . round((float)$r['std'],  $nb_dec) . "</td>"
                        . "</tr>";
                }
                $t .= "</table>";
            }
            $decade_tables[] = $t;
        }

        // Lay the decade tables side by side using a container table
        // (mPDF renders inline-block poorly, a table row is reliable).
        // Group them N per row so they don't overflow the page width.
        $per_row = ($id_eq_type_data == 1) ? 3 : 2;
        $cell_w  = ($id_eq_type_data == 1) ? 33 : 49;
        $table_html = "<table style='width:100%;border-collapse:collapse;border:none;'><tr>";
        $i = 0;
        foreach ($decade_tables as $t) {
            if ($i > 0 && $i % $per_row == 0) {
                $table_html .= "</tr><tr>";
            }
            $table_html .= "<td style='width:" . $cell_w . "%;vertical-align:top;padding:0 12px 14px 0;border:none;'>" . $t . "</td>";
            $i++;
        }
        $table_html .= "</tr></table>";
        break;

    // ===================== MONTHLY =====================
    case 'bymonth':
        $view_label = TEXT_STATS_BTN_BYMONTH;

        $calc_type = ($id_eq_type_data == 1) ? "SUM(ABS(da.valeur))" : "AVG(ABS(da.valeur))";

        $sql_my = "SELECT YEAR(da.dateheure) AS year, MONTH(da.dateheure) AS month,
                          " . $calc_type . " AS calc_valeur
                   FROM " . TABLE_DATA_ALL  . " da
                   JOIN " . TABLE_DATA_META . " dm ON da.id_meta = dm.id
                   WHERE dm.id_typedata = " . $id_typedata . "
                   AND   dm.id_station  = " . $cle_station . "
                   AND   da.dateheure  >= '" . $format_min_x . "'
                   AND   da.dateheure  <= '" . $format_max_x . "'
                   AND   da.valeur NOT IN (9999, -9999, 8888, -8888, 99999, -99999, 88888, -88888)
                   GROUP BY YEAR(da.dateheure), MONTH(da.dateheure)
                   ORDER BY month DESC, year DESC";
        $my_query = tep_db_query($sql_link, $sql_my);

        $smy = array();   // [month][year] = value
        while ($r = tep_db_fetch_array($my_query)) {
            $smy[(int)$r['month']][(int)$r['year']] = (float)$r['calc_valeur'];
        }

        $all_years = array();
        foreach ($smy as $md) { $all_years = array_merge($all_years, array_keys($md)); }
        $all_years = array_unique($all_years); rsort($all_years);
        $all_months = range(1, 12);

        $mois = [1=>TEXT_MONTH_SHORT_JAN,2=>TEXT_MONTH_SHORT_FEB,3=>TEXT_MONTH_SHORT_MAR,
                 4=>TEXT_MONTH_SHORT_APR,5=>TEXT_MONTH_SHORT_MAY,6=>TEXT_MONTH_SHORT_JUN,
                 7=>TEXT_MONTH_SHORT_JUL,8=>TEXT_MONTH_SHORT_AUG,9=>TEXT_MONTH_SHORT_SEP,
                 10=>TEXT_MONTH_SHORT_OCT,11=>TEXT_MONTH_SHORT_NOV,12=>TEXT_MONTH_SHORT_DEC];

        $stat_lines = [
            'mean'=>TEXT_STATS_MEAN, 'min'=>TEXT_STATS_MINIMUM,
            '5pc'=>TEXT_STATS_PERCENTILE_5, '10pc'=>TEXT_STATS_PERCENTILE_10,
            '50pc'=>TEXT_STATS_MEDIAN, '90pc'=>TEXT_STATS_PERCENTILE_90,
            '95pc'=>TEXT_STATS_PERCENTILE_95, 'max'=>TEXT_STATS_MAXIMUM,
        ];

        // Per-month statistic lines
        $sbm = array();
        foreach ($all_months as $mn) {
            $vals = isset($smy[$mn]) ? array_values($smy[$mn]) : [];
            if (count($vals) > 0) {
                $sbm[$mn] = [
                    'mean'=>mean($vals), 'min'=>min($vals), 'max'=>max($vals),
                    '5pc'=>calculate_percentile($vals,5.0),  '10pc'=>calculate_percentile($vals,10.0),
                    '50pc'=>calculate_percentile($vals,50.0),'90pc'=>calculate_percentile($vals,90.0),
                    '95pc'=>calculate_percentile($vals,95.0),
                ];
            } else { $sbm[$mn] = null; }
        }

        $hl_lo = "background-color:#A8F1FF;";
        $hl_hi = "background-color:#FFDCDC;";

        // ----- Table 1: Statistics (rows = stat lines, cols = months) -----
        $t1 = "<table border='1' cellspacing='0' cellpadding='3'
                  style='width:100%;border-collapse:collapse;font-size:9px;'>
                 <tr style='background-color:#eef3f8;font-weight:bold;'>
                   <th style='text-align:left;'></th>";
        foreach ($all_months as $mn) { $t1 .= "<th style='text-align:center;'>" . $mois[$mn] . "</th>"; }
        $t1 .= "</tr>";
        foreach ($stat_lines as $key => $label) {
            // per-row extremes
            $rv = [];
            foreach ($all_months as $mn) { $v = $sbm[$mn][$key] ?? null; if ($v !== null) { $rv[] = $v; } }
            $rlo = !empty($rv) ? min($rv) : null;
            $rhi = !empty($rv) ? max($rv) : null;
            $t1 .= "<tr><td style='text-align:left;font-weight:bold;'>" . $label . "</td>";
            foreach ($all_months as $mn) {
                $v = $sbm[$mn][$key] ?? null;
                $bg = '';
                if ($v !== null && $rhi != $rlo) { $bg = ($v == $rlo) ? $hl_lo : (($v == $rhi) ? $hl_hi : ''); }
                $disp = ($v !== null) ? round($v, $nb_dec) : '-';
                $t1 .= "<td style='text-align:center;" . $bg . "'>" . $disp . "</td>";
            }
            $t1 .= "</tr>";
        }
        $t1 .= "</table>";

        // ----- Table 2: Years (rows = years, cols = months) -----
        $col_lo = $col_hi = [];
        foreach ($all_months as $mn) {
            $cv = [];
            foreach ($all_years as $yr) { $v = $smy[$mn][$yr] ?? null; if ($v !== null) { $cv[] = $v; } }
            $col_lo[$mn] = !empty($cv) ? min($cv) : null;
            $col_hi[$mn] = !empty($cv) ? max($cv) : null;
        }

        $t2 = "<table border='1' cellspacing='0' cellpadding='3'
                  style='width:100%;border-collapse:collapse;font-size:9px;'>
                 <tr style='background-color:#eef3f8;font-weight:bold;'>
                   <th style='text-align:center;'>" . TEXT_STATS_YEAR . "</th>";
        foreach ($all_months as $mn) { $t2 .= "<th style='text-align:center;'>" . $mois[$mn] . "</th>"; }
        $t2 .= "</tr>";
        foreach ($all_years as $yr) {
            $t2 .= "<tr><td style='text-align:center;font-weight:bold;'>" . $yr . "</td>";
            foreach ($all_months as $mn) {
                $v = $smy[$mn][$yr] ?? null;
                $bg = '';
                if ($v !== null && $col_hi[$mn] != $col_lo[$mn]) { $bg = ($v == $col_lo[$mn]) ? $hl_lo : (($v == $col_hi[$mn]) ? $hl_hi : ''); }
                $disp = ($v !== null) ? round($v, $nb_dec) : '-';
                $t2 .= "<td style='text-align:center;" . $bg . "'>" . $disp . "</td>";
            }
            $t2 .= "</tr>";
        }
        $t2 .= "</table>";

        $table_html = "<h3 style='margin:14px 0 4px;'>" . TEXT_STATS_MONTHLY_SUMMARY . "</h3>" . $t1
                    . "<h3 style='margin:18px 0 4px;'>" . TEXT_STATS_YEAR . "</h3>" . $t2;
        break;

    // ===================== DAILY =====================
    case 'bydays':
        $view_label = TEXT_STATS_BTN_BYDAYS;

        $year_first = (int) (new DateTime($format_min_x))->format('Y');
        $year_last  = (int) (new DateTime($format_max_x))->format('Y');

        $mois = [1=>TEXT_MONTH_SHORT_JAN,2=>TEXT_MONTH_SHORT_FEB,3=>TEXT_MONTH_SHORT_MAR,
                 4=>TEXT_MONTH_SHORT_APR,5=>TEXT_MONTH_SHORT_MAY,6=>TEXT_MONTH_SHORT_JUN,
                 7=>TEXT_MONTH_SHORT_JUL,8=>TEXT_MONTH_SHORT_AUG,9=>TEXT_MONTH_SHORT_SEP,
                 10=>TEXT_MONTH_SHORT_OCT,11=>TEXT_MONTH_SHORT_NOV,12=>TEXT_MONTH_SHORT_DEC];

        $calc_type = ($id_eq_type_data == 1) ? "SUM(ABS(da.valeur))" : "AVG(ABS(da.valeur))";

        $hl_lo = "background-color:#A8F1FF;";
        $hl_hi = "background-color:#FFDCDC;";

        $table_html = '';
        $first_year = true;

        // One table per year, most recent first.
        for ($yr = $year_last; $yr >= $year_first; $yr--)
        {
            $startDate = $yr . "-01-01";
            $endDate   = $yr . "-12-31";

            $sql_d = "SELECT DAY(da.dateheure) AS day, MONTH(da.dateheure) AS month,
                             " . $calc_type . " AS calc_valeur
                      FROM " . TABLE_DATA_ALL  . " da
                      JOIN " . TABLE_DATA_META . " dm ON da.id_meta = dm.id
                      WHERE dm.id_typedata = " . $id_typedata . "
                      AND   dm.id_station  = " . $cle_station . "
                      AND   da.dateheure  >= '" . $startDate . "'
                      AND   da.dateheure  <= '" . $endDate . "'
                      AND   da.valeur NOT IN (9999, -9999, 8888, -8888, 99999, -99999, 88888, -88888)
                      GROUP BY DAY(da.dateheure), MONTH(da.dateheure)
                      ORDER BY month, day";
            $d_query = tep_db_query($sql_link, $sql_d);

            $org = array();
            while ($r = tep_db_fetch_array($d_query)) {
                $val = rtrim(rtrim(round((float)$r['calc_valeur'], 3), '0'), '.');
                $org[(int)$r['month']][(int)$r['day']] = $val;
            }
            if (empty($org)) { continue; } // skip years with no data

            $maxV = $minV = $monthAgg = array();
            foreach ($org as $m => $days) {
                $maxV[$m] = max($days);
                $minV[$m] = min($days);
                $monthAgg[$m] = ($id_eq_type_data == 1) ? array_sum($days) : mean($days);
            }

            // Year heading + page break before every year table, so each
            // year starts on a fresh page (the first one too, after the foreword).
            $table_html .= "<pagebreak />";
            $first_year = false;
            $table_html .= "<h3 style='margin:0 0 4px;'>" . $yr . "</h3>";

            $t = "<table border='1' cellspacing='0' cellpadding='2'
                     style='width:100%;border-collapse:collapse;font-size:8px;'>
                    <tr style='background-color:#eef3f8;font-weight:bold;'>
                      <th style='text-align:center;'>" . TEXT_STATS_DAY . "</th>";
            foreach ($mois as $mn => $lbl) { $t .= "<th style='text-align:center;'>" . $lbl . "</th>"; }
            $t .= "</tr>";

            for ($day = 1; $day <= 31; $day++) {
                $t .= "<tr><td style='text-align:center;font-weight:bold;'>" . $day . "</td>";
                for ($m = 1; $m <= 12; $m++) {
                    if (isset($org[$m][$day])) {
                        $v  = $org[$m][$day];
                        $bg = '';
                        if ($v !== '' && $minV[$m] != $maxV[$m]) {
                            if ($v == $minV[$m])      { $bg = $hl_lo; }
                            elseif ($v == $maxV[$m])  { $bg = $hl_hi; }
                        }
                        $t .= "<td style='text-align:center;" . $bg . "'>" . $v . "</td>";
                    } else {
                        $t .= "<td style='text-align:center;'>-</td>";
                    }
                }
                $t .= "</tr>";
            }

            // Aggregate / max / min rows
            $t .= "<tr><td style='text-align:center;font-weight:bold;'>" . $axe . "</td>";
            for ($m = 1; $m <= 12; $m++) { $t .= "<td style='text-align:center;'>" . (isset($monthAgg[$m]) ? round($monthAgg[$m], 2) : '-') . "</td>"; }
            $t .= "</tr>";

            $t .= "<tr><td style='text-align:center;font-weight:bold;background-color:#FFDCDC;'>" . TEXT_STATS_MAXIMUM . "</td>";
            for ($m = 1; $m <= 12; $m++) { $t .= "<td style='text-align:center;'>" . (isset($maxV[$m]) ? $maxV[$m] : '-') . "</td>"; }
            $t .= "</tr>";

            if ($id_eq_type_data > 1) {
                $t .= "<tr><td style='text-align:center;font-weight:bold;background-color:#A8F1FF;'>" . TEXT_STATS_MINIMUM . "</td>";
                for ($m = 1; $m <= 12; $m++) { $t .= "<td style='text-align:center;'>" . (isset($minV[$m]) ? $minV[$m] : '-') . "</td>"; }
                $t .= "</tr>";
            }

            $t .= "</table>";
            $table_html .= $t;
        }
        break;
}


// ----------------------------------------------
// PDF GENERATION

try {

    $header = "<img src='../../../" . DIR_WS_IMG_PDF . "bando.png' style='100%;'>";

    $footer = "
        <div style='text-align:center;font-size:10px;border-top:1px solid #000;padding-top:5px;'>
            " . TEXT_PDF_FOOTER_PAGE . " {PAGENO} " . TEXT_PDF_FOOTER_OF . " {nbpg}
        </div>";

    // Foreword block (General data) — laid out as a clean 2-column table so
    // long station names / dates don't wrap awkwardly.
    $foreword = "
        <h1>" . TEXT_STATS_TITLE . " &ndash; " . $view_label . "</h1>

        <table style='width:100%;font-size:13px;margin-top:8px;border-collapse:collapse;border:none;'>
            <tr>
                <td style='width:50%;padding:6px 8px 6px 0;vertical-align:top;border:none;'>
                    <span style='font-weight:bold;'>" . TEXT_STATS_STATION . "</span> : " . $code_station . " - " . html_entity_decode($nom_station) . "
                </td>
                <td style='width:50%;padding:6px 0;vertical-align:top;border:none;'>
                    <span style='font-weight:bold;'>" . TEXT_STATS_CHRONIQUE . "</span> : " . $init_type_data . " - " . $nom_type_data . "
                </td>
            </tr>
            <tr>
                <td style='padding:6px 8px 6px 0;vertical-align:top;border:none;'>
                    <span style='font-weight:bold;'>" . TEXT_STATS_PERIOD . "</span> : " . $min_x . " &rarr; " . $max_x . "
                </td>
                <td style='padding:6px 0;vertical-align:top;border:none;'>
                    <span style='font-weight:bold;'>" . TEXT_STATS_DURATION . "</span> : " . $duree . "
                </td>
            </tr>
            <tr>
                <td style='padding:6px 8px 6px 0;vertical-align:top;border:none;'>
                    <span style='font-weight:bold;'>" . TEXT_STATS_DATA . "</span> : " . $axe . " (" . $axe_unite . ")
                </td>
                <td style='padding:6px 0;border:none;'></td>
            </tr>
        </table>

        <table border='1' cellspacing='0' cellpadding='4'
               style='width:100%;border-collapse:collapse;font-size:11px;margin-top:12px;'>
            <thead>
                <tr style='background-color:#eef3f8;font-weight:bold;'>
                    <th>" . TEXT_STATS_CARD_N . "</th>
                    <th>" . TEXT_STATS_MINIMUM . "</th>
                    <th>" . TEXT_STATS_MAXIMUM . "</th>
                    <th>" . TEXT_STATS_MEAN . "</th>
                    <th>" . TEXT_STATS_STD_DEV . "</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style='text-align:center;'>" . number_format($g_n, 0, '.', ' ') . "</td>
                    <td style='text-align:center;'>" . $g_min . "</td>
                    <td style='text-align:center;'>" . $g_max . "</td>
                    <td style='text-align:center;'>" . $g_moy . "</td>
                    <td style='text-align:center;'>" . $g_std . "</td>
                </tr>
            </tbody>
        </table>";

    // Chart (captured client-side as a PNG data URL). mPDF handles a real
    // file on disk far more reliably than a long base64 data-URI, so we
    // decode it to a temporary PNG and reference it by path.
    $chart_block = '';
    $tmp_chart   = '';
    if (!empty($chartPng) && strpos($chartPng, 'data:image') === 0) {
        $b64 = substr($chartPng, strpos($chartPng, ',') + 1);
        $bin = base64_decode($b64);
        if ($bin !== false) {
            $tmp_chart = $_SERVER['DOCUMENT_ROOT'] . "/" . DIR_WS_PDF
                       . "tmp_chart_" . uniqid() . ".png";
            file_put_contents($tmp_chart, $bin);
            $chart_block = "
                <div style='margin-top:18px;text-align:center;'>
                    <img src='" . $tmp_chart . "' style='width:100%;max-width:680px;' />
                </div>";
        }
    }

    // Table title
    $table_title = "<h2 style='margin-top:18px;'>" . $view_label . "</h2>";

    $html = $foreword . $chart_block . $table_title . $table_html;

    $fileName = nettoyerNomFichier($code_station) . "_" . nettoyerNomFichier($nom_station)
              . "_" . nettoyerNomFichier($init_type_data) . "_" . nettoyerNomFichier($view_label) . ".pdf";
    $filePath = $_SERVER['DOCUMENT_ROOT'] . "/" . DIR_WS_PDF . $fileName;

    $mpdf = new \Mpdf\Mpdf([
        'margin_left'   => 10,
        'margin_right'  => 10,
        'margin_top'    => 30,
        'margin_bottom' => 18,
        'margin_footer' => 6
    ]);

    $stylesheet = file_get_contents('../../../css/pdf_css.css');
    $mpdf->WriteHTML($stylesheet, \Mpdf\HTMLParserMode::HEADER_CSS);
    $mpdf->SetHTMLHeader($header);
    $mpdf->SetHTMLFooter($footer);
    $mpdf->WriteHTML($html);

    $mpdf->Output($filePath, \Mpdf\Output\Destination::FILE);

    // Clean up the temporary chart image.
    if (!empty($tmp_chart) && is_file($tmp_chart)) { @unlink($tmp_chart); }

    echo json_encode([
        'status'   => 'success',
        'fileName' => $fileName
    ]);

} catch (\Mpdf\MpdfException $e) {
    echo json_encode([
        'status' => 'error',
        'msg'    => $e->getMessage()
    ]);
}
?>