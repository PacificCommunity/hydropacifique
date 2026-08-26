<?php
/*
----------------------------------------
Copyright (c) 2025 - Vai-Natura
----------------------------------------
Return Period Statistics Module
Calculates return periods using Gumbel distribution
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
$dataGraph = json_decode($jsonData, true);

// Extract values from the decoded array
$territoire_id = $dataGraph['territoireId'];
$cle_station   = $dataGraph['cle_station'];
$type_station  = $dataGraph['type_station'];
$id_typedata   = $dataGraph['id_typedata'];
$min_x         = $dataGraph['min_x'];
$max_x         = $dataGraph['max_x'];

$min_date    = DateTime::createFromFormat('d-m-Y', $min_x);
$format_min_x = $min_date->format('Y-m-d');

$max_date    = DateTime::createFromFormat('d-m-Y', $max_x);
$format_max_x = $max_date->format('Y-m-d');

// Validate the data interval
$interval   = $min_date->diff($max_date);
$total_days = $interval->days + 1;

$MIN_YEARS = 5;
$MIN_DAYS  = $MIN_YEARS * 365.25;

$metriques_tpsretour = false;
if ($total_days >= $MIN_DAYS) { $metriques_tpsretour = true; }


// Query TABLE EQ_TYPE (data type: rainfall, streamflow, ...)
$sql_eq_type = "SELECT DISTINCT id_eq_type, nom_eq_type, unite_eq_type, valeur_data_type, type_color_border, type_color_background, type_graph
                FROM " . TABLE_EQ_TYPE . "
                WHERE active_eq_type=1
                AND id_eq_type = " . $type_station . "
                ORDER BY order_eq_type ASC";
$eq_type_query = tep_db_query($sql_link, $sql_eq_type);
$eq_type_tab   = tep_db_fetch_array($eq_type_query);

    $id_eq_type           = isset($eq_type_tab['id_eq_type'])           ? $eq_type_tab['id_eq_type']           : '';
    $nom_eq_type          = isset($eq_type_tab['nom_eq_type'])          ? $eq_type_tab['nom_eq_type']          : '';
    $unite_eq_type        = isset($eq_type_tab['unite_eq_type'])        ? $eq_type_tab['unite_eq_type']        : '';
    $valeur_data_type     = isset($eq_type_tab['valeur_data_type'])     ? $eq_type_tab['valeur_data_type']     : '';
    $type_color_border    = isset($eq_type_tab['type_color_border'])    ? $eq_type_tab['type_color_border']    : '';
    $type_color_background = isset($eq_type_tab['type_color_background']) ? $eq_type_tab['type_color_background'] : '';
    $type_graph           = isset($eq_type_tab['type_graph'])           ? $eq_type_tab['type_graph']           : '';

// Query TABLE DATA_CHRON (chronology type: CI, PI, CIE, ...)
$sql_type_chron = "SELECT DISTINCT id_data_type, init_type_data, nom_type_data, id_eq_type_data, axe_data, unite,
                                to_periode, id_chon_periode, traitement, type_graph
                    FROM " . TABLE_TYPE_DATA . "
                    WHERE id_data_type = " . $id_typedata . "
                    ORDER BY init_type_data ASC";
$type_chron_query = tep_db_query($sql_link, $sql_type_chron);
$type_chron_tab   = tep_db_fetch_array($type_chron_query);

    $init_type_data  = isset($type_chron_tab['init_type_data'])  ? $type_chron_tab['init_type_data']  : '';
    $nom_type_data   = isset($type_chron_tab['nom_type_data'])   ? $type_chron_tab['nom_type_data']   : '';
    $axe_data        = isset($type_chron_tab['axe_data'])        ? $type_chron_tab['axe_data']        : '';
    $unite           = isset($type_chron_tab['unite'])           ? $type_chron_tab['unite']           : '';
    $to_periode      = isset($type_chron_tab['to_periode'])      ? $type_chron_tab['to_periode']      : '';
    $id_chon_periode = isset($type_chron_tab['id_chon_periode']) ? $type_chron_tab['id_chon_periode'] : '';
    $traitement      = isset($type_chron_tab['traitement'])      ? $type_chron_tab['traitement']      : '';
    $typegraph       = isset($type_chron_tab['type_graph'])      ? $type_chron_tab['type_graph']      : '';

// Query TABLE DATA_TYPE_AXE (axis definition)
$sql_data_type_axe = "SELECT DISTINCT id, axe, unite
                        FROM " . TABLE_DATA_TYPE_AXE . "
                        WHERE id = " . $axe_data;
$data_type_axe_query = tep_db_query($sql_link, $sql_data_type_axe);
$data_type_axe_tab   = tep_db_fetch_array($data_type_axe_query);

    $axe          = isset($data_type_axe_tab['axe'])   ? $data_type_axe_tab['axe']   : '';
    $typegruniteaph = isset($data_type_axe_tab['unite']) ? $data_type_axe_tab['unite'] : '';


$html_stats          = "";
$html_tab_returnPeriod = '';


// -------------------------------------------------------------
// RETURN PERIOD STATISTICS — CALCULATION AND DISPLAY

    $sql_data = "
                    SELECT
                        YEAR(da.dateheure) as annee,
                        MAX(da.valeur) as max_valeur
                    FROM
                        " . TABLE_DATA_ALL . " da
                    JOIN
                        " . TABLE_DATA_META . " dm ON da.id_meta=dm.id
                    WHERE
                        dm.id_typedata = " . $id_typedata . "
                        AND dm.id_station = " . $cle_station . "
                        AND da.valeur NOT IN (9999, -9999, 8888, -8888, 99999, -99999, 88888, -88888)
                        AND da.dateheure >= '" . $format_min_x . "'
                        AND da.dateheure <= '" . $format_max_x . "'
                    GROUP BY
                        YEAR(da.dateheure)
                    ORDER BY
                        annee ASC
                    ";

    $maxima_annuels = [];
    $data_query = tep_db_query($sql_link, $sql_data);
    while ($data_tab = tep_db_fetch_array($data_query))
    {
        $val = (float)$data_tab['max_valeur'];
        // Only apply log transform for positive values
        if ($val > 0) {
            $maxima_annuels[] = log($val);
        }
    }

    // Sort data in ascending order (required for non-exceedance probability)
    sort($maxima_annuels);

    // Number of years (n)
    $n = count($maxima_annuels);

    if ($n > 5)
    {
        // Series mean and standard deviation
        $mean              = round(mean($maxima_annuels), 3);
        $standardDeviation = round(sqrt(variance($maxima_annuels)), 3);

        // Gumbel distribution parameters
        $eulerConstant = 0.5772156649;
        $a = (sqrt(6) * $standardDeviation) / pi(); // Scale parameter
        $u = $mean - ($eulerConstant * $a);          // Location parameter

        // Return period definitions (in years)
        $periodes   = [2, 5, 10, 20, 30, 40, 50, 100];
        $resultats  = [];

        foreach ($periodes as $T)
        {
            // Non-exceedance probability
            $p = 1 - (1 / $T);

            // Inverse Gumbel formula in log space
            // ln(xT) = u - a * ln(-ln(p))
            $log_xT = $u - ($a * log(-log($p)));

            // Back-transform to the actual flow value (series was log-transformed)
            $valeur_xT = exp($log_xT);

            $valeurs_T[$T] = $valeur_xT;
        }


        $html_tab_returnPeriod .= "

            <div style='margin-bottom:25px;'>

                <table id='table_tri' style='font-size:12px;'>
                    <tr>
                        <th style='width:140px;text-align:center;'></th>
                        <th style='width:70px;text-align:center;'>" . TEXT_STATS_MEAN . "</th>
                        <th style='width:70px;text-align:center;'>" . TEXT_STATS_STD_DEV . "</th>
                        <th style='width:80px;text-align:center;'>" . TEXT_STATS_RETURN_2Y . "</th>
                        <th style='width:80px;text-align:center;'>" . TEXT_STATS_RETURN_5Y . "</th>
                        <th style='width:80px;text-align:center;'>" . TEXT_STATS_RETURN_10Y . "</th>
                        <th style='width:80px;text-align:center;'>" . TEXT_STATS_RETURN_20Y . "</th>
                        <th style='width:80px;text-align:center;'>" . TEXT_STATS_RETURN_30Y . "</th>
                        <th style='width:80px;text-align:center;'>" . TEXT_STATS_RETURN_40Y . "</th>
                        <th style='width:80px;text-align:center;'>" . TEXT_STATS_RETURN_50Y . "</th>
                        <th style='width:80px;text-align:center;'>" . TEXT_STATS_RETURN_100Y . "</th>
                    <tr>

                    <tr style=''>
                        <td style='height:20px;text-align:center;font-weight:bold;'>" . TEXT_STATS_COMPUTED_VALUES . "</td>
                        <td style='height:20px;text-align:center;'>" . round($mean, 3) . "</td>
                        <td style='height:20px;text-align:center;'>" . round($standardDeviation, 3) . "</td>
                        <td style='height:20px;text-align:center;'>" . round($valeurs_T[2], 3) . "</td>
                        <td style='height:20px;text-align:center;'>" . round($valeurs_T[5], 3) . "</td>
                        <td style='height:20px;text-align:center;'>" . round($valeurs_T[10], 3) . "</td>
                        <td style='height:20px;text-align:center;'>" . round($valeurs_T[20], 3) . "</td>
                        <td style='height:20px;text-align:center;'>" . round($valeurs_T[30], 3) . "</td>
                        <td style='height:20px;text-align:center;'>" . round($valeurs_T[40], 3) . "</td>
                        <td style='height:20px;text-align:center;'>" . round($valeurs_T[50], 3) . "</td>
                        <td style='height:20px;text-align:center;'>" . round($valeurs_T[100], 3) . "</td>
                    </tr>

                </table>

            </div>

        ";

    }
    else
    {
        // Not enough data — no output
    }


$html_stats .= $html_tab_returnPeriod;
$stat_graph       = false;
$html_stats_graph = '';
$js_graph         = '';

$responseData = array(
    'html_stats'       => $html_stats,
    'stat_graph'       => $stat_graph,
    'html_stats_graph' => $html_stats_graph,
    'js_graph'         => $js_graph
);

// Encode response as JSON
$jsonResponse = json_encode($responseData);

// Send response to the client
echo $jsonResponse;
?>