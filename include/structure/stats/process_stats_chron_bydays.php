<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Daily Statistics Module
Computes and displays daily statistics by month for a selected year
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
$jsonData = file_get_contents('php://input');

// Decode JSON data into a PHP associative array
$dataStats = json_decode($jsonData, true);

$year_select = $dataStats['yearSelect'];
$dataGraph   = $dataStats['stats'];

// Extract values from the decoded array
$territoire_id = $dataGraph['territoireId'];
$cle_station   = $dataGraph['cle_station'];
$type_station  = $dataGraph['type_station'];
$id_typedata   = $dataGraph['id_typedata'];
$min_x         = $dataGraph['min_x'];
$max_x         = $dataGraph['max_x'];

$date         = DateTime::createFromFormat('d-m-Y', $min_x);
$format_min_x = $date->format('Y-m-d');

$date         = DateTime::createFromFormat('d-m-Y', $max_x);
$format_max_x = $date->format('Y-m-d');


// Query TABLE DATA_CHRON (chronology type: CI, PI, CIE, ...)
$sql_type_chron = "SELECT DISTINCT id_data_type, init_type_data, nom_type_data, id_eq_type_data, axe_data, unite,
                                to_periode, id_chon_periode, traitement, type_graph
                    FROM " . TABLE_TYPE_DATA . "
                    WHERE id_data_type = " . $id_typedata . "
                    ORDER BY init_type_data ASC";
$type_chron_query = tep_db_query($sql_link, $sql_type_chron);
$type_chron_tab   = tep_db_fetch_array($type_chron_query);

    $id_eq_type_data = isset($type_chron_tab['id_eq_type_data']) ? $type_chron_tab['id_eq_type_data'] : '';
    $init_type_data  = isset($type_chron_tab['init_type_data'])  ? $type_chron_tab['init_type_data']  : '';
    $nom_type_data   = isset($type_chron_tab['nom_type_data'])   ? $type_chron_tab['nom_type_data']   : '';
    $axe_data        = isset($type_chron_tab['axe_data'])        ? $type_chron_tab['axe_data']        : '';
    $unite           = isset($type_chron_tab['unite'])           ? $type_chron_tab['unite']           : '';
    $to_periode      = isset($type_chron_tab['to_periode'])      ? $type_chron_tab['to_periode']      : '';
    $id_chon_periode = isset($type_chron_tab['id_chon_periode']) ? $type_chron_tab['id_chon_periode'] : '';
    $traitement      = isset($type_chron_tab['traitement'])      ? $type_chron_tab['traitement']      : '';
    $typegraph       = isset($type_chron_tab['type_graph'])      ? $type_chron_tab['type_graph']      : '';

// Query TABLE DATA_TYPE_AXE (axis definition)
$sql_data_type_axe = "SELECT DISTINCT id, axe, unite, nb_round
                        FROM " . TABLE_DATA_TYPE_AXE . "
                        WHERE id = " . $axe_data;
$data_type_axe_query = tep_db_query($sql_link, $sql_data_type_axe);
$data_type_axe_tab   = tep_db_fetch_array($data_type_axe_query);

    $axe         = isset($data_type_axe_tab['axe'])      ? $data_type_axe_tab['axe']      : '';
    $axe_unite   = isset($data_type_axe_tab['unite'])    ? $data_type_axe_tab['unite']    : '';
    $axe_nbRound = isset($data_type_axe_tab['nb_round']) ? $data_type_axe_tab['nb_round'] : '';


$html_stats = "";


// -------------------------------------------------------------
// DAILY STATISTICS — CALCULATION AND DISPLAY

$startDate = "$year_select-01-01";
$endDate   = "$year_select-12-31";

// Use SUM for cumulative types (e.g. rainfall), AVG for others
$calc_type = "AVG(ABS(da.valeur))";
if ($id_eq_type_data == 1) { $calc_type = "SUM(ABS(da.valeur))"; }

// Query: daily values grouped by day and month
    $sql_stats = "
                    SELECT
                        DAY(da.dateheure) AS day,
                        MONTH(da.dateheure) AS month,
                        " . $calc_type . " AS calc_valeur
                    FROM
                        " . TABLE_DATA_ALL . " da
                    JOIN
                        " . TABLE_DATA_META . " dm ON da.id_meta=dm.id
                    WHERE
                        dm.id_typedata = " . $id_typedata . "
                        AND dm.id_station = " . $cle_station . "
                        AND da.dateheure >= '" . $startDate . "'
                        AND da.dateheure <= '" . $endDate . "'
                        AND da.valeur NOT IN (9999, -9999, 8888, -8888, 99999, -99999, 88888, -88888)
                    GROUP BY
                        DAY(da.dateheure),
                        MONTH(da.dateheure)
                    ORDER BY
                        month, day
                    ";

    $organizedData = [];

    $stats_query = tep_db_query($sql_link, $sql_stats);
    while ($stats_tab = tep_db_fetch_array($stats_query))
    {
        $month = $stats_tab['month'];
        $day   = $stats_tab['day'];

        $valeur        = $stats_tab['calc_valeur'];
        $valeur_format = rtrim(rtrim(round($valeur, 3), '0'), '.');

        if (!isset($organizedData[$month])) { $organizedData[$month] = []; }

        $organizedData[$month][$day] = $valeur_format;
    }

    // Determine monthly max and min values for highlighting
    $maxValues   = [];
    $minValues   = [];
    $monthlyData = [];

    foreach ($organizedData as $month => $days)
    {
        $maxValues[$month] = max($days);
        $minValues[$month] = min($days);

        if ($id_eq_type_data == 1) { $monthlyData[$month] = array_sum($days); }
        else                       { $monthlyData[$month] = mean($days); }
    }

    // Short month names (translated via constants)
    $mois_noms_courts = [
        1  => TEXT_MONTH_SHORT_JAN, 2  => TEXT_MONTH_SHORT_FEB, 3  => TEXT_MONTH_SHORT_MAR,
        4  => TEXT_MONTH_SHORT_APR, 5  => TEXT_MONTH_SHORT_MAY, 6  => TEXT_MONTH_SHORT_JUN,
        7  => TEXT_MONTH_SHORT_JUL, 8  => TEXT_MONTH_SHORT_AUG, 9  => TEXT_MONTH_SHORT_SEP,
        10 => TEXT_MONTH_SHORT_OCT, 11 => TEXT_MONTH_SHORT_NOV, 12 => TEXT_MONTH_SHORT_DEC
    ];


    // Build HTML table
    $html_stats .= "

            <div style='margin-left:10%;margin-bottom:20px;'>

                <table id='table_tri' style='width:85%;font-size:12px;'>
                    <tr>
                        <th style='width:2%;text-align:center;font-weight:bold;color:#000;'>" . TEXT_STATS_DAY . "</th>
                ";

                foreach ($mois_noms_courts as $mois)
                {
                    $html_stats .= "
                                    <th style='width:5%;text-align:center;font-size:13px;'><span>" . $mois . "</span></th>
                                    ";
                }

                $html_stats .= "
                                </tr>
                                ";

                $daysInMonth = cal_days_in_month(CAL_GREGORIAN, 1, $year_select);
                $row = 1;
                for ($day = 1; $day <= $daysInMonth; $day++)
                {
                    if (fmod($row, 2) == 0) { $row_l = "class='row1' onmouseover=\"this.className='row1hover';\" onmouseout=\"this.className='row1';\" "; }
                    else                    { $row_l = "class='row2' onmouseover=\"this.className='row2hover';\" onmouseout=\"this.className='row2';\" "; }

                    $html_stats .= "<tr " . $row_l . ">
                                    <td style='height:20px;text-align:center;font-weight:bold;'>" . $day . "</td>";

                        for ($month = 1; $month <= 12; $month++)
                        {
                            if (isset($organizedData[$month][$day]))
                            {
                                $value    = $organizedData[$month][$day];
                                $style_bg = "";
                                $style    = "";

                                if (!empty($value))
                                {
                                    if ($value == $minValues[$month]) { $style_bg = "background-color:#A8F1FF;"; }
                                    if ($value == $maxValues[$month]) { $style_bg = "background-color:#FFDCDC;"; }

                                    $style = ($value == $maxValues[$month]) ? "style='font-size:13px;font-weight:bold;color:#930000;'" : "";
                                }

                                $html_stats .= "<td style='height:20px;text-align:center;" . $style_bg . "'>";
                                    $html_stats .= "<span " . $style . ">" . $value . "</span>";
                                $html_stats .= "</td>";
                            }
                            else
                            {
                                $html_stats .= "<td style='height:20px;text-align:center;'>";
                                    $html_stats .= "-";
                                $html_stats .= "</td>";
                            }
                        }

                    $html_stats .= "</tr>";
                    $row++;
                }

                $html_stats .= "<tr><td colspan='13' style='height: 15px;'></td></tr>";

                // Monthly aggregate row (sum or average)
                $html_stats .= "<tr>";
                    $html_stats .= "<td style='height:20px;text-align:center;font-weight:bold;'>" . $axe . "</td>";
                    for ($month = 1; $month <= 12; $month++)
                    {
                        $html_stats .= "<td style='text-align:center;'>";
                            $html_stats .= isset($monthlyData[$month]) ? round($monthlyData[$month], 2) : '-';
                        $html_stats .= "</td>";
                    }
                $html_stats .= "</tr>";

                // Monthly maximum row
                $html_stats .= "<tr class='row2'>";
                    $html_stats .= "<td style='height:20x;text-align:center;font-weight:bold;background-color:#FFDCDC;'>" . TEXT_STATS_MAXIMUM . "</td>";
                    for ($month = 1; $month <= 12; $month++)
                    {
                        $html_stats .= "<td style='text-align:center;'>";
                            $html_stats .= isset($maxValues[$month]) ? $maxValues[$month] : '-';
                        $html_stats .= "</td>";
                    }
                $html_stats .= "</tr>";

                // Monthly minimum row (only for non-cumulative types)
                if ($id_eq_type_data > 1)
                {
                    $html_stats .= "<tr>";
                        $html_stats .= "<td style='height:20px;text-align:center;font-weight:bold;background-color:#A8F1FF;'>" . TEXT_STATS_MINIMUM . "</td>";
                        for ($month = 1; $month <= 12; $month++)
                        {
                            $html_stats .= "<td style='text-align:center;'>";
                                $html_stats .= isset($minValues[$month]) ? $minValues[$month] : '-';
                            $html_stats .= "</td>";
                        }
                    $html_stats .= "</tr>";
                }

        $html_stats .= "

                        </table>
                    </div>
                    ";


$responseData = array(
    'html_stats' => $html_stats
);

// Encode response as JSON
$jsonResponse = json_encode($responseData);

// Send response to the client
echo $jsonResponse;
?>
