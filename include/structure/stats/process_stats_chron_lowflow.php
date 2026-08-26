<?php
/*
----------------------------------------
Copyright (c) 2025 - Vai-Natura
----------------------------------------
Low Flow Statistics Module
Computes low flow metrics (QMNA, DCE, VCN) using log-normal distribution
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


$html_stats        = "";
$html_tab_metriques = '';


// -------------------------------------------------------------
// LOW FLOW STATISTICS — CALCULATION AND DISPLAY

    // Query: daily flow values (Qj) over the selected period
    $sql_lowflow = "
                    SELECT
                        DATE(da.dateheure) AS date_day,
                        da.valeur AS Qj
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
                    ORDER BY
                        date_day ASC
                    ";

    $Qj_data      = [];
    $lowflow_query = tep_db_query($sql_link, $sql_lowflow);
    while ($lowflow_tab = tep_db_fetch_array($lowflow_query))
    {
        $day        = $lowflow_tab['date_day'];
        $Qj         = floatval($lowflow_tab['Qj']);
        $Qj_data[$day] = $Qj;
    }

    // Flat array of flow values for statistical functions
    $Qj_values_only = array_values($Qj_data);
    $min_y_cdc      = min($Qj_values_only);
    $max_y_cdc      = max($Qj_values_only);


    // FLOW MODULE AND DERIVED THRESHOLDS
    // Module = interannual mean flow
    $Q_moyen  = round(mean($Qj_values_only), 3);
    $Q_mod_10 = $Q_moyen / 10;  // 10% of module
    $Q_mod_20 = $Q_moyen / 20;  // 5% of module

    $Q_MOD10 = isset($Q_mod_10) ? round($Q_mod_10, 3) : '-';
    $Q_MOD20 = isset($Q_mod_20) ? round($Q_mod_20, 3) : '-';

    $PERCENT_MOD10 = isset($Q_MOD10) ? round(($Q_MOD10 / $Q_moyen), 2) : '-';
    $PERCENT_MOD20 = isset($Q_MOD20) ? round($Q_MOD20 / $Q_moyen, 2)   : '-';

    $F_Q_MOD   = calculate_q_frequency($Qj_values_only, $Q_moyen);
    $F_Q_MOD10 = calculate_q_frequency($Qj_values_only, $Q_MOD10);
    $F_Q_MOD20 = calculate_q_frequency($Qj_values_only, $Q_MOD20);


    // QMNA (minimum monthly mean) — by year
    $qmnas_annuels = calculate_qmna_annual($Qj_data);

    // DCE (Q355 — 10 lowest days)
    $dce_annuels = calculate_dce_annual($Qj_data, 10);

    // VCN series (N = 3, 7, 10, 30 consecutive days)
    $qj_array    = array_values($Qj_data);
    $dates_array = array_keys($Qj_data);

    $vcn3_annuels  = calculate_vcn_series($qj_array, $dates_array, 3);
    $vcn7_annuels  = calculate_vcn_series($qj_array, $dates_array, 7);
    $vcn10_annuels = calculate_vcn_series($qj_array, $dates_array, 10);
    $vcn30_annuels = calculate_vcn_series($qj_array, $dates_array, 30);


    // LOG-NORMAL DISTRIBUTION FITTING
    // Return periods: T=2 (biennial), T=5 (quinquennial), T=10, T=20
    const T2  = 2.0;
    const T5  = 5.0;
    const T10 = 10.0;
    const T20 = 20.0;

    $max_metrique = 0;
    $min_metrique = 0;

    if ($metriques_tpsretour)
    {
        // QMNA metrics
        $results_QMNA_2  = calculate_low_flow_statistics($qmnas_annuels, T2);
        $QMNA_2  = (!isset($results_QMNA_2['error']))  ? $results_QMNA_2['QT_value']  : null;
        $results_QMNA_5  = calculate_low_flow_statistics($qmnas_annuels, T5);
        $QMNA_5  = (!isset($results_QMNA_5['error']))  ? $results_QMNA_5['QT_value']  : null;
        $results_QMNA_10 = calculate_low_flow_statistics($qmnas_annuels, T10);
        $QMNA_10 = (!isset($results_QMNA_10['error'])) ? $results_QMNA_10['QT_value'] : null;
        $results_QMNA_20 = calculate_low_flow_statistics($qmnas_annuels, T20);
        $QMNA_20 = (!isset($results_QMNA_20['error'])) ? $results_QMNA_20['QT_value'] : null;

        // DCE metrics
        $results_DCE_2   = calculate_low_flow_statistics($dce_annuels, T2);
        $DCE_2   = (!isset($results_DCE_2['error']))   ? $results_DCE_2['QT_value']   : null;
        $results_DCE_5   = calculate_low_flow_statistics($dce_annuels, T5);
        $DCE_5   = (!isset($results_DCE_5['error']))   ? $results_DCE_5['QT_value']   : null;
        $results_DCE_10  = calculate_low_flow_statistics($dce_annuels, T10);
        $DCE_10  = (!isset($results_DCE_10['error']))  ? $results_DCE_10['QT_value']  : null;
        $results_DCE_20  = calculate_low_flow_statistics($dce_annuels, T20);
        $DCE_20  = (!isset($results_DCE_20['error']))  ? $results_DCE_20['QT_value']  : null;

        // VCN10 metrics
        $results_VCN10_2  = calculate_low_flow_statistics($vcn10_annuels, T2);
        $VCN10_2  = (!isset($results_VCN10_2['error']))  ? $results_VCN10_2['QT_value']  : null;
        $results_VCN10_5  = calculate_low_flow_statistics($vcn10_annuels, T5);
        $VCN10_5  = (!isset($results_VCN10_5['error']))  ? $results_VCN10_5['QT_value']  : null;
        $results_VCN10_10 = calculate_low_flow_statistics($vcn10_annuels, T10);
        $VCN10_10 = (!isset($results_VCN10_10['error'])) ? $results_VCN10_10['QT_value'] : null;
        $results_VCN10_20 = calculate_low_flow_statistics($vcn10_annuels, T20);
        $VCN10_20 = (!isset($results_VCN10_20['error'])) ? $results_VCN10_20['QT_value'] : null;

        // VCN3 metrics
        $results_VCN3_2  = calculate_low_flow_statistics($vcn3_annuels, T2);
        $VCN3_2  = (!isset($results_VCN3_2['error']))  ? $results_VCN3_2['QT_value']  : null;
        $results_VCN3_5  = calculate_low_flow_statistics($vcn3_annuels, T5);
        $VCN3_5  = (!isset($results_VCN3_5['error']))  ? $results_VCN3_5['QT_value']  : null;
        $results_VCN3_10 = calculate_low_flow_statistics($vcn3_annuels, T10);
        $VCN3_10 = (!isset($results_VCN3_10['error'])) ? $results_VCN3_10['QT_value'] : null;
        $results_VCN3_20 = calculate_low_flow_statistics($vcn3_annuels, T20);
        $VCN3_20 = (!isset($results_VCN3_20['error'])) ? $results_VCN3_20['QT_value'] : null;

        // VCN7 metrics
        $results_VCN7_2  = calculate_low_flow_statistics($vcn7_annuels, T2);
        $VCN7_2  = (!isset($results_VCN7_2['error']))  ? $results_VCN7_2['QT_value']  : null;
        $results_VCN7_5  = calculate_low_flow_statistics($vcn7_annuels, T5);
        $VCN7_5  = (!isset($results_VCN7_5['error']))  ? $results_VCN7_5['QT_value']  : null;
        $results_VCN7_10 = calculate_low_flow_statistics($vcn7_annuels, T10);
        $VCN7_10 = (!isset($results_VCN7_10['error'])) ? $results_VCN7_10['QT_value'] : null;
        $results_VCN7_20 = calculate_low_flow_statistics($vcn7_annuels, T20);
        $VCN7_20 = (!isset($results_VCN7_20['error'])) ? $results_VCN7_20['QT_value'] : null;

        // VCN30 metrics
        $results_VCN30_2  = calculate_low_flow_statistics($vcn30_annuels, T2);
        $VCN30_2  = (!isset($results_VCN30_2['error']))  ? $results_VCN30_2['QT_value']  : null;
        $results_VCN30_5  = calculate_low_flow_statistics($vcn30_annuels, T5);
        $VCN30_5  = (!isset($results_VCN30_5['error']))  ? $results_VCN30_5['QT_value']  : null;
        $results_VCN30_10 = calculate_low_flow_statistics($vcn30_annuels, T10);
        $VCN30_10 = (!isset($results_VCN30_10['error'])) ? $results_VCN30_10['QT_value'] : null;
        $results_VCN30_20 = calculate_low_flow_statistics($vcn30_annuels, T20);
        $VCN30_20 = (!isset($results_VCN30_20['error'])) ? $results_VCN30_20['QT_value'] : null;

        // Format all metric values
        $array_metriques = [$Q_MOD10, $QMNA_2, $DCE_2, $VCN30_2];
        $max_metrique    = max($array_metriques);

        $QMNA_2  = isset($QMNA_2)  ? round($QMNA_2, 3)  : '-';
        $QMNA_5  = isset($QMNA_5)  ? round($QMNA_5, 3)  : '-';
        $DCE_2   = isset($DCE_2)   ? round($DCE_2, 3)   : '-';
        $DCE_5   = isset($DCE_5)   ? round($DCE_5, 3)   : '-';
        $VCN10_2 = isset($VCN10_2) ? round($VCN10_2, 3) : '-';
        $VCN10_5 = isset($VCN10_5) ? round($VCN10_5, 3) : '-';
        $VCN3_2  = isset($VCN3_2)  ? round($VCN3_2, 3)  : '-';
        $VCN3_5  = isset($VCN3_5)  ? round($VCN3_5, 3)  : '-';
        $VCN7_2  = isset($VCN7_2)  ? round($VCN7_2, 3)  : '-';
        $VCN7_5  = isset($VCN7_5)  ? round($VCN7_5, 3)  : '-';
        $VCN30_2 = isset($VCN30_2) ? round($VCN30_2, 3) : '-';
        $VCN30_5 = isset($VCN30_5) ? round($VCN30_5, 3) : '-';

        // Percentage of interannual module
        $PERCENT_QMNA_2  = isset($QMNA_2)  ? round($QMNA_2  / $Q_moyen, 2) : '-';
        $PERCENT_QMNA_5  = isset($QMNA_5)  ? round($QMNA_5  / $Q_moyen, 2) : '-';
        $PERCENT_DCE_2   = isset($DCE_2)   ? round($DCE_2   / $Q_moyen, 2) : '-';
        $PERCENT_DCE_5   = isset($DCE_5)   ? round($DCE_5   / $Q_moyen, 2) : '-';
        $PERCENT_VCN10_2 = isset($VCN10_2) ? round($VCN10_2 / $Q_moyen, 2) : '-';
        $PERCENT_VCN10_5 = isset($VCN10_5) ? round($VCN10_5 / $Q_moyen, 2) : '-';
        $PERCENT_VCN3_2  = isset($VCN3_2)  ? round($VCN3_2  / $Q_moyen, 2) : '-';
        $PERCENT_VCN3_5  = isset($VCN3_5)  ? round($VCN3_5  / $Q_moyen, 2) : '-';
        $PERCENT_VCN7_2  = isset($VCN7_2)  ? round($VCN7_2  / $Q_moyen, 2) : '-';
        $PERCENT_VCN7_5  = isset($VCN7_5)  ? round($VCN7_5  / $Q_moyen, 2) : '-';
        $PERCENT_VCN30_2 = isset($VCN30_2) ? round($VCN30_2 / $Q_moyen, 2) : '-';
        $PERCENT_VCN30_5 = isset($VCN30_5) ? round($VCN30_5 / $Q_moyen, 2) : '-';

        // Non-exceedance frequencies
        $F_QMNA_2  = calculate_q_frequency($Qj_values_only, $QMNA_2);
        $F_QMNA_5  = calculate_q_frequency($Qj_values_only, $QMNA_5);
        $F_DCE_2   = calculate_q_frequency($Qj_values_only, $DCE_2);
        $F_DCE_5   = calculate_q_frequency($Qj_values_only, $DCE_5);

        // 50% of DCE-2 threshold
        $Q_50_DCE_2      = $DCE_2 > 0 ? $DCE_2 * 0.5 : 0.0;
        $PERCENT_50_DCE_2 = isset($Q_50_DCE_2) ? round($Q_50_DCE_2 / $Q_moyen, 2) : '-';
        $F_50_DCE_2      = calculate_q_frequency($Qj_values_only, $Q_50_DCE_2);

        $F_VCN10_2 = calculate_q_frequency($Qj_values_only, $VCN10_2);
        $F_VCN10_5 = calculate_q_frequency($Qj_values_only, $VCN10_5);
        $F_VCN3_2  = calculate_q_frequency($Qj_values_only, $VCN3_2);
        $F_VCN3_5  = calculate_q_frequency($Qj_values_only, $VCN3_5);
        $F_VCN7_2  = calculate_q_frequency($Qj_values_only, $VCN7_2);
        $F_VCN7_5  = calculate_q_frequency($Qj_values_only, $VCN7_5);
        $F_VCN30_2 = calculate_q_frequency($Qj_values_only, $VCN30_2);
        $F_VCN30_5 = calculate_q_frequency($Qj_values_only, $VCN30_5);

        $array_freq = [$F_Q_MOD20, $F_QMNA_2, $F_DCE_2, $F_VCN30_2];
        $max_freq   = max($array_freq);


        // Build HTML — full metrics table
        $html_tab_metriques .= "

            <div style='margin-bottom:25px;'>
                <table id='table_tri' style='font-size:12px;'>
                    <tr>
                        <th style='width:140px;text-align:center;'></th>
                        <th style='width:70px;text-align:center;'>" . TEXT_LOWFLOW_MODULE . "</th>
                        <th style='width:70px;text-align:center;'>" . TEXT_LOWFLOW_MODULE_10 . "</th>
                        <th style='width:70px;text-align:center;'>" . TEXT_LOWFLOW_MODULE_20 . "</th>
                        <th style='width:70px;text-align:center;'>" . TEXT_LOWFLOW_QMNA_2 . "</th>
                        <th style='width:70px;text-align:center;'>" . TEXT_LOWFLOW_QMNA_5 . "</th>
                        <th style='width:70px;text-align:center;'>" . TEXT_LOWFLOW_DCE_2 . "</th>
                        <th style='width:70px;text-align:center;'>" . TEXT_LOWFLOW_DCE_2_50 . "</th>
                        <th style='width:70px;text-align:center;'>" . TEXT_LOWFLOW_DCE_5 . "</th>
                        <th style='width:70px;text-align:center;'>" . TEXT_LOWFLOW_VCN10_2 . "</th>
                        <th style='width:70px;text-align:center;'>" . TEXT_LOWFLOW_VCN10_5 . "</th>
                        <th style='width:70px;text-align:center;'>" . TEXT_LOWFLOW_VCN3_2 . "</th>
                        <th style='width:70px;text-align:center;'>" . TEXT_LOWFLOW_VCN3_5 . "</th>
                        <th style='width:70px;text-align:center;'>" . TEXT_LOWFLOW_VCN7_2 . "</th>
                        <th style='width:70px;text-align:center;'>" . TEXT_LOWFLOW_VCN7_5 . "</th>
                        <th style='width:70px;text-align:center;'>" . TEXT_LOWFLOW_VCN30_2 . "</th>
                        <th style='width:70px;text-align:center;'>" . TEXT_LOWFLOW_VCN30_5 . "</th>
                    <tr>

                    <tr style=''>
                        <td style='height:20px;text-align:center;font-weight:bold;'>" . TEXT_LOWFLOW_METRIC_VALUES . "</td>
                        <td style='height:20px;text-align:center;'>" . htmlspecialchars($Q_moyen) . "</td>
                        <td style='height:20px;text-align:center;'>" . htmlspecialchars($Q_MOD10) . "</td>
                        <td style='height:20px;text-align:center;'>" . htmlspecialchars($Q_MOD20) . "</td>
                        <td style='height:20px;text-align:center;'>" . htmlspecialchars($QMNA_2) . "</td>
                        <td style='height:20px;text-align:center;'>" . htmlspecialchars($QMNA_5) . "</td>
                        <td style='height:20px;text-align:center;'>" . htmlspecialchars($DCE_2) . "</td>
                        <td style='height:20px;text-align:center;'>" . htmlspecialchars($Q_50_DCE_2) . "</td>
                        <td style='height:20px;text-align:center;'>" . htmlspecialchars($DCE_5) . "</td>
                        <td style='height:20px;text-align:center;'>" . htmlspecialchars($VCN10_2) . "</td>
                        <td style='height:20px;text-align:center;'>" . htmlspecialchars($VCN10_5) . "</td>
                        <td style='height:20px;text-align:center;'>" . htmlspecialchars($VCN3_2) . "</td>
                        <td style='height:20px;text-align:center;'>" . htmlspecialchars($VCN3_5) . "</td>
                        <td style='height:20px;text-align:center;'>" . htmlspecialchars($VCN7_2) . "</td>
                        <td style='height:20px;text-align:center;'>" . htmlspecialchars($VCN7_5) . "</td>
                        <td style='height:20px;text-align:center;'>" . htmlspecialchars($VCN30_2) . "</td>
                        <td style='height:20px;text-align:center;'>" . htmlspecialchars($VCN30_5) . "</td>
                    </tr>

                    <tr class='row2' style=''>
                        <td style='height:20px;text-align:center;font-weight:bold;'>" . TEXT_LOWFLOW_PCT_MODULE . "</td>
                        <td style='height:20px;text-align:center;'>100%</td>
                        <td style='height:20px;text-align:center;'>" . htmlspecialchars($PERCENT_MOD10 * 100) . "%</td>
                        <td style='height:20px;text-align:center;'>" . htmlspecialchars($PERCENT_MOD20 * 100) . "%</td>
                        <td style='height:20px;text-align:center;'>" . htmlspecialchars($PERCENT_QMNA_2 * 100) . "%</td>
                        <td style='height:20px;text-align:center;'>" . htmlspecialchars($PERCENT_QMNA_5 * 100) . "%</td>
                        <td style='height:20px;text-align:center;'>" . htmlspecialchars($PERCENT_DCE_2 * 100) . "%</td>
                        <td style='height:20px;text-align:center;'>" . htmlspecialchars($PERCENT_50_DCE_2 * 100) . "%</td>
                        <td style='height:20px;text-align:center;'>" . htmlspecialchars($PERCENT_DCE_5 * 100) . "%</td>
                        <td style='height:20px;text-align:center;'>" . htmlspecialchars($PERCENT_VCN10_2 * 100) . "%</td>
                        <td style='height:20px;text-align:center;'>" . htmlspecialchars($PERCENT_VCN10_5 * 100) . "%</td>
                        <td style='height:20px;text-align:center;'>" . htmlspecialchars($PERCENT_VCN3_2 * 100) . "%</td>
                        <td style='height:20px;text-align:center;'>" . htmlspecialchars($PERCENT_VCN3_5 * 100) . "%</td>
                        <td style='height:20px;text-align:center;'>" . htmlspecialchars($PERCENT_VCN7_2 * 100) . "%</td>
                        <td style='height:20px;text-align:center;'>" . htmlspecialchars($PERCENT_VCN7_5 * 100) . "%</td>
                        <td style='height:20px;text-align:center;'>" . htmlspecialchars($PERCENT_VCN30_2 * 100) . "%</td>
                        <td style='height:20px;text-align:center;'>" . htmlspecialchars($PERCENT_VCN30_5 * 100) . "%</td>
                    </tr>

                    <tr style=''>
                        <td style='height:20px;text-align:center;font-weight:bold;'>" . TEXT_LOWFLOW_NON_EXCEEDANCE . "</td>
                        <td style='height:20px;text-align:center;'>" . htmlspecialchars(round($F_Q_MOD, 1)) . "%</td>
                        <td style='height:20px;text-align:center;'>" . htmlspecialchars(round($F_Q_MOD10, 1)) . "%</td>
                        <td style='height:20px;text-align:center;'>" . htmlspecialchars(round($F_Q_MOD20, 1)) . "%</td>
                        <td style='height:20px;text-align:center;'>" . htmlspecialchars(round($F_QMNA_2, 1)) . "%</td>
                        <td style='height:20px;text-align:center;'>" . htmlspecialchars(round($F_QMNA_5, 1)) . "%</td>
                        <td style='height:20px;text-align:center;'>" . htmlspecialchars(round($F_DCE_2, 1)) . "%</td>
                        <td style='height:20px;text-align:center;'>" . htmlspecialchars(round($F_50_DCE_2, 1)) . "%</td>
                        <td style='height:20px;text-align:center;'>" . htmlspecialchars(round($F_DCE_5, 1)) . "%</td>
                        <td style='height:20px;text-align:center;'>" . htmlspecialchars(round($F_VCN10_2, 1)) . "%</td>
                        <td style='height:20px;text-align:center;'>" . htmlspecialchars(round($F_VCN10_5, 1)) . "%</td>
                        <td style='height:20px;text-align:center;'>" . htmlspecialchars(round($F_VCN3_2, 1)) . "%</td>
                        <td style='height:20px;text-align:center;'>" . htmlspecialchars(round($F_VCN3_5, 1)) . "%</td>
                        <td style='height:20px;text-align:center;'>" . htmlspecialchars(round($F_VCN7_2, 1)) . "%</td>
                        <td style='height:20px;text-align:center;'>" . htmlspecialchars(round($F_VCN7_5, 1)) . "%</td>
                        <td style='height:20px;text-align:center;'>" . htmlspecialchars(round($F_VCN30_2, 1)) . "%</td>
                        <td style='height:20px;text-align:center;'>" . htmlspecialchars(round($F_VCN30_5, 1)) . "%</td>
                    </tr>

                </table>
            </div>
            ";
    }
    else
    {
        // Simplified table when series is too short for return period metrics
        $array_metriques = [$Q_MOD10, $Q_MOD20];
        $max_metrique    = max($array_metriques);
        $array_freq      = [$F_Q_MOD20];
        $max_freq        = max($array_freq);

        $html_tab_metriques .= "

            <div style='margin-bottom:25px;'>
                <table id='table_tri' style='font-size:12px;'>
                    <tr>
                        <th style='width:140px;text-align:center;'></th>
                        <th style='width:70px;text-align:center;'>" . TEXT_LOWFLOW_MODULE . "</th>
                        <th style='width:70px;text-align:center;'>" . TEXT_LOWFLOW_MODULE_10 . "</th>
                        <th style='width:70px;text-align:center;'>" . TEXT_LOWFLOW_MODULE_20 . "</th>
                    <tr>

                    <tr style=''>
                        <td style='height:20px;text-align:center;font-weight:bold;'>" . TEXT_LOWFLOW_METRIC_VALUES . "</td>
                        <td style='height:20px;text-align:center;'>" . htmlspecialchars($Q_moyen) . "</td>
                        <td style='height:20px;text-align:center;'>" . htmlspecialchars($Q_MOD10) . "</td>
                        <td style='height:20px;text-align:center;'>" . htmlspecialchars($Q_MOD20) . "</td>
                    </tr>

                    <tr class='row2' style=''>
                        <td style='height:20px;text-align:center;font-weight:bold;'>" . TEXT_LOWFLOW_PCT_MODULE . "</td>
                        <td style='height:20px;text-align:center;'>100%</td>
                        <td style='height:20px;text-align:center;'>" . htmlspecialchars($PERCENT_MOD10 * 100) . "%</td>
                        <td style='height:20px;text-align:center;'>" . htmlspecialchars($PERCENT_MOD20 * 100) . "%</td>
                    </tr>

                    <tr style=''>
                        <td style='height:20px;text-align:center;font-weight:bold;'>" . TEXT_LOWFLOW_NON_EXCEEDANCE . "</td>
                        <td style='height:20px;text-align:center;'>" . htmlspecialchars(round($F_Q_MOD, 1)) . "%</td>
                        <td style='height:20px;text-align:center;'>" . htmlspecialchars(round($F_Q_MOD10, 1)) . "%</td>
                        <td style='height:20px;text-align:center;'>" . htmlspecialchars(round($F_Q_MOD20, 1)) . "%</td>
                    </tr>

                </table>
            </div>
            ";
    }


    // FLOW DURATION CURVE (CDC) — GRAPH

    $stat_graph       = true;
    $html_stats_graph = "";
    $js_graph         = "";

    $colorGraph = colorList();
    $cdc_data   = generate_cdc_data($Qj_values_only);

    // CDC trace
    $trace_cdc = [
        'x'            => $cdc_data['X'],
        'y'            => $cdc_data['Y'],
        'hovertemplate' => '<b>' . TEXT_LOWFLOW_FREQUENCY . '</b> : %{x:.2f} % <br><b>' . TEXT_LOWFLOW_FLOW_CDC . '</b> : %{y:.3f} (m<sup>3</sup>/s)<extra></extra>',
        'hoverlabel'   => ['font' => ['size' => 14]],
        'mode'         => 'lines',
        'name'         => TEXT_LOWFLOW_FLOW_CDC,
        'line'         => ['color' => $colorGraph[1], 'width' => 3]
    ];

    // Metric point traces
    $traces_points = [];

    function add_point_trace(
        array &$traces_array,
        float $Q_float,
        float $F_float,
        string $name,
        string $color,
        bool $isVisible = true
    )
    {
        if ($Q_float > 0)
        {
            $traces_array[] = [
                'x'             => [$F_float],
                'y'             => [$Q_float],
                'tickformat'    => '.3f',
                'mode'          => 'markers+text',
                'hovertemplate' => '<b>Fréquence</b> : %{x:.2f} % <br><b>' . $name . '</b> : %{y:.3f} (m<sup>3</sup>/s)<extra></extra>',
                'hoverlabel'    => ['font' => ['size' => 14]],
                'name'          => $name,
                'visible'       => $isVisible,
                'text'          => [$name],
                'textposition'  => 'bottom right',
                'textfont'      => ['color' => $color, 'size' => 12],
                'marker'        => ['color' => $color, 'size' => 13]
            ];
        }
    }

    add_point_trace($traces_points, $Q_moyen, $F_Q_MOD,   TEXT_LOWFLOW_MODULE,    $colorGraph[2]);
    add_point_trace($traces_points, $Q_MOD10, $F_Q_MOD10, TEXT_LOWFLOW_MODULE_10, $colorGraph[3]);
    add_point_trace($traces_points, $Q_MOD20, $F_Q_MOD20, TEXT_LOWFLOW_MODULE_20, $colorGraph[4]);

    if ($metriques_tpsretour)
    {
        add_point_trace($traces_points, $QMNA_2,     $F_QMNA_2,    TEXT_LOWFLOW_QMNA_2,  $colorGraph[5]);
        add_point_trace($traces_points, $QMNA_5,     $F_QMNA_5,    TEXT_LOWFLOW_QMNA_5,  $colorGraph[6]);
        add_point_trace($traces_points, $DCE_2,      $F_DCE_2,     TEXT_LOWFLOW_DCE_2,   $colorGraph[7]);
        add_point_trace($traces_points, $DCE_5,      $F_DCE_5,     TEXT_LOWFLOW_DCE_5,   $colorGraph[8]);
        add_point_trace($traces_points, $VCN10_2,    $F_VCN10_2,   TEXT_LOWFLOW_VCN10_2, $colorGraph[9]);
        add_point_trace($traces_points, $VCN10_5,    $F_VCN10_5,   TEXT_LOWFLOW_VCN10_5, $colorGraph[10]);
        add_point_trace($traces_points, $VCN3_2,     $F_VCN3_2,    TEXT_LOWFLOW_VCN3_2,  $colorGraph[11], false);
        add_point_trace($traces_points, $VCN3_5,     $F_VCN3_5,    TEXT_LOWFLOW_VCN3_5,  $colorGraph[12], false);
        add_point_trace($traces_points, $VCN7_2,     $F_VCN7_2,    TEXT_LOWFLOW_VCN7_2,  $colorGraph[13], false);
        add_point_trace($traces_points, $VCN7_5,     $F_VCN7_5,    TEXT_LOWFLOW_VCN7_5,  $colorGraph[14], false);
        add_point_trace($traces_points, $VCN30_2,    $F_VCN30_2,   TEXT_LOWFLOW_VCN30_2, $colorGraph[15], false);
        add_point_trace($traces_points, $VCN30_5,    $F_VCN30_5,   TEXT_LOWFLOW_VCN30_5, $colorGraph[16], false);
        add_point_trace($traces_points, $Q_50_DCE_2, $F_50_DCE_2,  TEXT_LOWFLOW_DCE_2_50,$colorGraph[17]);
    }

    $plot_traces  = array_merge([$trace_cdc], $traces_points);
    $json_traces  = json_encode($plot_traces);

    // Warning text when series is too short
    $text_noMetrique = '';
    if (!$metriques_tpsretour)
    {
        $text_noMetrique .= "<br><span style='color:#B22222;'>" . TEXT_LOWFLOW_SERIES_TOO_SHORT . "</span>";
    }

    $html_stats_graph .= "
                            <div style='float: left; '>
                                <p class='info_stats'>
                                    " . TEXT_LOWFLOW_CDC_TITLE . "
                                    " . $text_noMetrique . "
                                </p>
                            </div>
                        ";

    // Checkbox controls for metric visibility
    if ($metriques_tpsretour)
    {
        $html_stats_graph .= "
                            <div class='navMenuGraph' style='float:right;'>

                                <div style='float: left; margin-right: 30px;'>
                                    <div style=''>
                                        <input type='checkbox' id='checkMetrique_1' onClick=\"toggleTraceVisibility('plotCDC',1);\" data-trace-id='Module' checked>
                                        <span style='margin-left:5px;font-size:11px;font-weight:normal;'>" . TEXT_LOWFLOW_MODULE . "</span>
                                    </div>
                                    <div style='margin-top: 5px;'>
                                        <input type='checkbox' id='checkMetrique_2' onClick=\"toggleTraceVisibility('plotCDC',2);\" data-trace-id='50% DCE2' checked>
                                        <span style='margin-left:5px;font-size:11px;font-weight:normal;'>50% - DCE2</span>
                                    </div>
                                </div>

                                <div style='float: left; margin-right: 30px;'>
                                    <div style=''>
                                        <input type='checkbox' id='checkMetrique_3' onClick=\"toggleTraceVisibility('plotCDC',3);\" data-trace-id='Module/10' checked>
                                        <span style='margin-left:5px;font-size:11px;font-weight:normal;'>" . TEXT_LOWFLOW_MODULE_10 . "</span>
                                    </div>
                                    <div style='margin-top: 5px;'>
                                        <input type='checkbox' id='checkMetrique_4' onClick=\"toggleTraceVisibility('plotCDC',4);\" data-trace-id='Module/20' checked>
                                        <span style='margin-left:5px;font-size:11px;font-weight:normal;'>" . TEXT_LOWFLOW_MODULE_20 . "</span>
                                    </div>
                                </div>

                                <div style='float: left; margin-right: 30px;'>
                                    <div style=''>
                                        <input type='checkbox' id='checkMetrique_7' onClick=\"toggleTraceVisibility('plotCDC',5);\" data-trace-id='DCE-2' checked>
                                        <span style='margin-left:5px;font-size:11px;font-weight:normal;'>" . TEXT_LOWFLOW_DCE_2 . "</span>
                                    </div>
                                    <div style='margin-top: 5px;'>
                                        <input type='checkbox' id='checkMetrique_8' onClick=\"toggleTraceVisibility('plotCDC',6);\" data-trace-id='DCE-5' checked>
                                        <span style='margin-left:5px;font-size:11px;font-weight:normal;'>" . TEXT_LOWFLOW_DCE_5 . "</span>
                                    </div>
                                </div>

                                <div style='float: left; margin-right: 30px;'>
                                    <div style=''>
                                        <input type='checkbox' id='checkMetrique_5' onClick=\"toggleTraceVisibility('plotCDC',7);\" data-trace-id='QMNA-2' checked>
                                        <span style='margin-left:5px;font-size:11px;font-weight:normal;'>" . TEXT_LOWFLOW_QMNA_2 . "</span>
                                    </div>
                                    <div style='margin-top: 5px;'>
                                        <input type='checkbox' id='checkMetrique_6' onClick=\"toggleTraceVisibility('plotCDC',8);\" data-trace-id='QMNA-5' checked>
                                        <span style='margin-left:5px;font-size:11px;font-weight:normal;'>" . TEXT_LOWFLOW_QMNA_5 . "</span>
                                    </div>
                                </div>

                                <div style='float: left; margin-right: 30px;'>
                                    <div style=''>
                                        <input type='checkbox' id='checkMetrique_9' onClick=\"toggleTraceVisibility('plotCDC',9);\" data-trace-id='VCN10-2' checked>
                                        <span style='margin-left:5px;font-size:11px;font-weight:normal;'>" . TEXT_LOWFLOW_VCN10_2 . "</span>
                                    </div>
                                    <div style='margin-top: 5px;'>
                                        <input type='checkbox' id='checkMetrique_10' onClick=\"toggleTraceVisibility('plotCDC',10);\" data-trace-id='VCN10-5' checked>
                                        <span style='margin-left:5px;font-size:11px;font-weight:normal;'>" . TEXT_LOWFLOW_VCN10_5 . "</span>
                                    </div>
                                </div>

                                <div style='float: left; margin-right: 30px;'>
                                    <div style=''>
                                        <input type='checkbox' id='checkMetrique_11' onClick=\"toggleTraceVisibility('plotCDC',11);\" data-trace-id='VCN3-2' >
                                        <span style='margin-left:5px;font-size:11px;font-weight:normal;'>" . TEXT_LOWFLOW_VCN3_2 . "</span>
                                    </div>
                                    <div style='margin-top: 5px;'>
                                        <input type='checkbox' id='checkMetrique_12' onClick=\"toggleTraceVisibility('plotCDC',12);\" data-trace-id='VCN3-5' >
                                        <span style='margin-left:5px;font-size:11px;font-weight:normal;'>" . TEXT_LOWFLOW_VCN3_5 . "</span>
                                    </div>
                                </div>

                                <div style='float: left; margin-right: 30px;'>
                                    <div style=''>
                                        <input type='checkbox' id='checkMetrique_13' onClick=\"toggleTraceVisibility('plotCDC',13);\" data-trace-id='VCN7-2' >
                                        <span style='margin-left:5px;font-size:11px;font-weight:normal;'>" . TEXT_LOWFLOW_VCN7_2 . "</span>
                                    </div>
                                    <div style='margin-top: 5px;'>
                                        <input type='checkbox' id='checkMetrique_14' onClick=\"toggleTraceVisibility('plotCDC',14);\" data-trace-id='VCN7-5' >
                                        <span style='margin-left:5px;font-size:11px;font-weight:normal;'>" . TEXT_LOWFLOW_VCN7_5 . "</span>
                                    </div>
                                </div>

                                <div style='float: left; margin-right: 30px;'>
                                    <div style=''>
                                        <input type='checkbox' id='checkMetrique_15' onClick=\"toggleTraceVisibility('plotCDC',15);\" data-trace-id='VCN30-2' >
                                        <span style='margin-left:5px;font-size:11px;font-weight:normal;'>" . TEXT_LOWFLOW_VCN30_2 . "</span>
                                    </div>
                                    <div style='margin-top: 5px;'>
                                        <input type='checkbox' id='checkMetrique_16' onClick=\"toggleTraceVisibility('plotCDC',16);\" data-trace-id='VCN30-5' >
                                        <span style='margin-left:5px;font-size:11px;font-weight:normal;'>" . TEXT_LOWFLOW_VCN30_5 . "</span>
                                    </div>
                                </div>

                            </div>
                        ";
    }
    else
    {
        $html_stats_graph .= "
                            <div class='navMenuGraph' style='float:right;'>
                                <div style='float: left; margin-right: 30px;'>
                                    <div style=''>
                                        <input type='checkbox' id='checkMetrique_1' onClick=\"toggleTraceVisibility('plotCDC',1);\" data-trace-id='Module' checked>
                                        <span style='margin-left:5px;font-size:11px;font-weight:normal;'>" . TEXT_LOWFLOW_MODULE . "</span>
                                    </div>
                                </div>
                                <div style='float: left; margin-right: 30px;'>
                                    <div style=''>
                                        <input type='checkbox' id='checkMetrique_2' onClick=\"toggleTraceVisibility('plotCDC',2);\" data-trace-id='Module/10' checked>
                                        <span style='margin-left:5px;font-size:11px;font-weight:normal;'>" . TEXT_LOWFLOW_MODULE_10 . "</span>
                                    </div>
                                    <div style='margin-top: 5px;'>
                                        <input type='checkbox' id='checkMetrique_3' onClick=\"toggleTraceVisibility('plotCDC',3);\" data-trace-id='Module/20' checked>
                                        <span style='margin-left:5px;font-size:11px;font-weight:normal;'>" . TEXT_LOWFLOW_MODULE_20 . "</span>
                                    </div>
                                </div>
                            </div>
                        ";
    }

    $html_stats_graph .= "
                            <div id='plotCDC' class='graph_stats' style='height:370px;margin-top:10px;margin-bottom:20px;'>
                            </div>
                        ";

    // Plotly.js chart code for the CDC
    $max_y = log10(1.5 * $max_y_cdc);
    $min_y_temp = min($min_y_cdc, $Q_MOD20);
    $min_y      = is_numeric($min_y_temp) && $min_y_temp > 0 ? (float)$min_y_temp : 0.001;
    $min_y      = log10(0.5 * $min_y);
    $max_x_cdc  = 102;

    $js_graph .= "
                const dataCDC = " . $json_traces . ";

                const layoutCDCGraph =
                    {
                        yaxis: {
                            title: { text: '" . TEXT_LOWFLOW_DAILY_FLOW_AXIS . "', standoff: 5 },
                            type: 'log',
                            titlefont: {family: 'roboto, arial, helvetica', size: 14, bold: true, color: '#000000'},
                            ticklen: 5, showline: true, linewidth: 1, automargin: true,
                            range: [" . $min_y . "," . $max_y . "], fixedrange: false
                        },
                        xaxis: {
                            title: { text: '" . TEXT_STATS_NON_EXCEEDANCE_FREQ . "', standoff: 5 },
                            showgrid: true, gridcolor: '#ddd', gridwidth: 1,
                            range: [-5, " . $max_x_cdc . "],
                            tickformat: '.2f',
                            titlefont: {family: 'roboto, arial, helvetica', size: 14, bold: true, color: '#000000'},
                            tickangle: 0, ticklen: 5, showline: true, linewidth: 1, fixedrange: false
                        },
                        showlegend: false,
                        hovermode:'xy',
                        hoverlabel: { bgcolor: '#fff', font: { size: 12, color: '#000' } },
                        margin: {l: 60, r: 10, t: 20, b:60},
                    };

                Plotly.newPlot('plotCDC', dataCDC, layoutCDCGraph, config);
            ";


    // ANNEXES — Log-normal detail plots and annual minima table

    $metriques_config = [
        'QMNA'  => ['titre' => 'QMNA'],
        'DCE'   => ['titre' => 'Q355 (DCE)'],
        'VCN3'  => ['titre' => 'VCN 3 jours'],
        'VCN7'  => ['titre' => 'VCN 7 jours'],
        'VCN10' => ['titre' => 'VCN 10 jours'],
        'VCN30' => ['titre' => 'VCN 30 jours']
    ];

    if ($metriques_tpsretour)
    {
        $html_stats .= "
                        <div style='width:100%;'>
                            <p class='info_stats'>
                                " . TEXT_LOWFLOW_ANNEX_METRICS . "
                            </p>
                        ";

        foreach ($metriques_config as $code_metrique => $config)
        {
            $titre_metrique = $config['titre'];

            // Dynamic variable lookup for each metric
            $var_name_2  = "results_" . $code_metrique . "_2";
            $var_name_5  = "results_" . $code_metrique . "_5";
            $var_name_10 = "results_" . $code_metrique . "_10";
            $var_name_20 = "results_" . $code_metrique . "_20";

            if (!isset($$var_name_2)) { continue; }

            $res_2  = $$var_name_2;
            $res_5  = $$var_name_5;
            $res_10 = $$var_name_10;
            $res_20 = $$var_name_20;

            // Extract log-normal parameters
            $params_log = $res_2['params_log'];
            $N_points   = $params_log['N_points'];

            $QT_2       = number_format($res_2['metrique_result']['QT_valeur'], 3, '.', '');
            $IC_2_bas   = number_format($res_2['metrique_result']['IC_bas'], 3, '.', '');
            $IC_2_haut  = number_format($res_2['metrique_result']['IC_haut'], 3, '.', '');
            $IC_2_display = "[" . $IC_2_bas . " ; " . $IC_2_haut . "]";

            $QT_5       = number_format($res_5['metrique_result']['QT_valeur'], 3, '.', '');
            $IC_5_bas   = number_format($res_5['metrique_result']['IC_bas'], 3, '.', '');
            $IC_5_haut  = number_format($res_5['metrique_result']['IC_haut'], 3, '.', '');
            $IC_5_display = "[" . $IC_5_bas . " ; " . $IC_5_haut . "]";

            $QT_10      = number_format($res_10['metrique_result']['QT_valeur'], 3, '.', '');
            $IC_10_bas  = number_format($res_10['metrique_result']['IC_bas'], 3, '.', '');
            $IC_10_haut = number_format($res_10['metrique_result']['IC_haut'], 3, '.', '');
            $IC_10_display = "[" . $IC_10_bas . " ; " . $IC_10_haut . "]";

            $QT_20      = number_format($res_20['metrique_result']['QT_valeur'], 3, '.', '');
            $IC_20_bas  = number_format($res_20['metrique_result']['IC_bas'], 3, '.', '');
            $IC_20_haut = number_format($res_20['metrique_result']['IC_haut'], 3, '.', '');
            $IC_20_display = "[" . $IC_20_bas . " ; " . $IC_20_haut . "]";

            $mu_Y    = $params_log['Moyenne-log-u'];
            $sigma_Y = $params_log['Ecart-type-log-sigma'];

            $log_mediane     = $params_log['Mediane-log-u'];
            $sigma_Y_mediane = $params_log['Ecart-type-log-sigma_mediane'];

            $IC_bas_mu    = $params_log['IC_bas_mu'];
            $IC_haut_mu   = $params_log['IC_haut_mu'];
            $IC_bas_sigma = $params_log['IC_bas_sigma'];
            $IC_haut_sigma = $params_log['IC_haut_sigma'];

            $graph_id = "plot_" . $code_metrique;

            // HTML — per-metric detail panel
            $html_stats .= "
                            <div style='float:left;width:30%;margin-right:2%;margin-bottom:20px;padding-bottom:20px;border-bottom: 1px solid #000;'>

                                <p style='text-align:center;font-weight:bold;font-size:14px;color:#930000;'>
                                    " . $titre_metrique . "
                                </p>

                                <div id='" . $graph_id . "' class='graph_stats' style='width:100%;height:320px;margin-bottom:0px;'>
                                </div>

                                <div style='width:100%;margin-bottom:30px;'>
                                    <p style='margin-bottom:5px;text-align:center;font-weight:bold;font-size:11px;'>
                                        " . TEXT_LOWFLOW_LOGNORMAL_PARAMS . "
                                    </p>
                                    <table id='table_tri_" . $code_metrique . "' style='margin:0 auto;font-size:11px;'>
                                        <thead>
                                            <tr>
                                                <th>&nbsp</th>
                                                <th style='width:60px;text-align:center;'>" . TEXT_STATS_MEAN . "</th>
                                                <th style='width:60px;text-align:center;'>" . TEXT_STATS_MEDIAN . "</th>
                                                <th style='width:60px;text-align:center;'>" . TEXT_STATS_CI_LOW . "</th>
                                                <th style='width:60px;text-align:center;'>" . TEXT_STATS_CI_HIGH . "</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td style='height:15px;text-align:left;font-weight:bold;'>" . TEXT_LOWFLOW_LOG_MU . "</td>
                                                <td style='height:15px;text-align:center;'>" . number_format($mu_Y, 4) . "</td>
                                                <td style='height:15px;text-align:center;'>" . number_format($log_mediane, 4) . "</td>
                                                <td style='height:15px;text-align:center;'>" . number_format($IC_bas_mu, 4) . "</td>
                                                <td style='height:15px;text-align:center;'>" . number_format($IC_haut_mu, 4) . "</td>
                                            </tr>
                                            <tr>
                                                <td style='height:15px;text-align: left;font-weight:bold;'>" . TEXT_LOWFLOW_LOG_SIGMA . "</td>
                                                <td style='height:15px;text-align:center;'>" . number_format($sigma_Y, 4) . "</td>
                                                <td style='height:15px;text-align:center;'>" . number_format($sigma_Y_mediane, 4) . "</td>
                                                <td style='height:15px;text-align:center;'>" . number_format($IC_bas_sigma, 4) . "</td>
                                                <td style='height:15px;text-align:center;'>" . number_format($IC_haut_sigma, 4) . "</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div style='width:100%;'>
                                    <p style='margin-bottom:5px;text-align:center;font-weight:bold;font-size:11px;'>
                                        " . TEXT_LOWFLOW_RESULTS_TITLE . " " . $titre_metrique . " (m³/s)
                                    </p>
                                    <p style='margin-bottom:5px;text-align:center;font-size:11px;'>
                                        " . TEXT_LOWFLOW_N_POINTS . " : " . $N_points . "
                                    </p>
                                    <table id='table_res_" . $code_metrique . "' style='margin:0 auto;font-size:11px;border: 1px solid #ccc;'>
                                        <tr>
                                            <td style='font-weight: bold;color: #054b96;'>" . TEXT_LOWFLOW_BIENNIAL . "</td>
                                            <td>" . $QT_2 . "<br>" . $IC_2_display . "</td>
                                        </tr>
                                        <tr class='row2'>
                                            <td style='font-weight: bold;color: #054b96;'>" . TEXT_LOWFLOW_QUINQUENNIAL . "</td>
                                            <td>" . $QT_5 . "<br>" . $IC_5_display . "</td>
                                        </tr>
                                        <tr>
                                            <td style='font-weight: bold;color: #054b96;'>" . TEXT_LOWFLOW_DECENNIAL . "</td>
                                            <td>" . $QT_10 . "<br>" . $IC_10_display . "</td>
                                        </tr>
                                        <tr class='row2'>
                                            <td style='font-weight: bold;color: #054b96;'>" . TEXT_LOWFLOW_VICENNIAL . "</td>
                                            <td>" . $QT_20 . "<br>" . $IC_20_display . "</td>
                                        </tr>
                                    </table>
                                </div>

                            </div>
                        ";


            // JavaScript — log-normal fit plot
            $plotly_series = generate_series_logNormal($mu_Y, $sigma_Y, $N_points);
            $Q_obs = array_column($res_2['points_observes'], 'Q');
            $F_obs = array_column($res_2['points_observes'], 'F_empirique');

            $plotly_data = json_encode([
                'F_modele'  => $plotly_series['F'],
                'Q_modele'  => $plotly_series['Q_modele'],
                'Q_IC_bas'  => $plotly_series['Q_IC_bas'],
                'Q_IC_haut' => $plotly_series['Q_IC_haut'],
                'F_obs'     => $F_obs,
                'Q_obs'     => $Q_obs
            ]);

            $js_graph .= "
                // --- JS block: " . $titre_metrique . " ---
                (function() {
                    var rawData = " . $plotly_data . ";

                    var ic_trace = {
                        x: rawData.F_modele.concat(rawData.F_modele.slice().reverse()),
                        y: rawData.Q_IC_haut.concat(rawData.Q_IC_bas.slice().reverse()),
                        fill: 'toself', fillcolor: 'rgba(173, 216, 230, 0.3)',
                        line: { color: 'transparent' }, mode: 'lines', showlegend: false, name: 'IC 95%',
                    };

                    var modele_trace = {
                        x: rawData.F_modele, y: rawData.Q_modele,
                        mode: 'lines', line: { color: '#86B0BD', width: 2 },
                        name: '" . TEXT_LOWFLOW_LOGNORMAL_LAW . "',
                        hovertemplate: '<b>" . TEXT_LOWFLOW_FREQUENCY . "</b> : %{x:.2f} % <br><b>" . TEXT_LOWFLOW_FLOW_LABEL . " </b> : %{y:.3f} (m<sup>3</sup>/s)',
                    };

                    var obs_trace = {
                        x: rawData.F_obs, y: rawData.Q_obs,
                        mode: 'markers', marker: { color: '#000', size: 3, symbol: 'circle', line: {width: 0} },
                        name: '" . TEXT_LOWFLOW_OBSERVED_POINTS . "',
                        hovertemplate: '<b>" . TEXT_LOWFLOW_FREQUENCY . "</b> : %{x:.2f} % <br><b>" . TEXT_LOWFLOW_FLOW_LABEL . " </b> : %{y:.3f} (m<sup>3</sup>/s)',
                    };

                    var data_metrique = [ic_trace, modele_trace, obs_trace];

                    var layout = {
                        xaxis: {
                            title: { text: '" . TEXT_LOWFLOW_FREQUENCY . "', standoff: 5 },
                            titlefont: {family: 'roboto, arial, helvetica', size: 10, bold: true, color: '#000000'},
                            range: [0, 1], showgrid: true, gridcolor: '#ddd', gridwidth: 1,
                            dtick: 0.1, tickformat: '.1f', showline: true, linewidth: 1, fixedrange: true
                        },
                        yaxis: {
                            title: '" . TEXT_LOWFLOW_FLOW_AXIS . "',
                            titlefont: {family: 'roboto, arial, helvetica', size: 12, bold: true, color: '#000000'},
                            showline: true, linewidth: 1, automargin: true, fixedrange: true
                        },
                        showlegend: false, hovermode:'xy',
                        hoverlabel: { bgcolor: '#fff', font: { size: 12, color: '#000' } },
                        margin: {l: 60, r: 10, t: 30, b:60}
                    };

                    var configDetails = {
                        responsive: true, scrollZoom: false, displaylogo: false, displayModeBar: true,
                        modeBarButtons: [[
                            {
                                name: 'Export Data CSV', icon: Plotly.Icons.disk, direction: 'up',
                                click: function(gd) {
                                    var data = gd.data; var sep = ';'; var csvContent = '';
                                    if (data.length === 0) { return; }
                                    var allUniqueX = new Set();
                                    data.forEach(function(trace) {
                                        if (Array.isArray(trace.x) && trace.x.length > 0) {
                                            trace.x.forEach(function(xVal) { allUniqueX.add(xVal); });
                                        }
                                    });
                                    var masterX = Array.from(allUniqueX).sort();
                                    if (masterX.length === 0) { return; }
                                    var lookupMaps = data.map(function(trace) {
                                        var map = new Map();
                                        for (var i = 0; i < (trace.x ? trace.x.length : 0); i++) {
                                            map.set(trace.x[i], {y: trace.y[i]});
                                        }
                                        return map;
                                    });
                                    var header = ['X'];
                                    data.forEach(function(trace, index) {
                                        header.push('Y (' + (trace.name || 'Trace ' + (index + 1)) + ')');
                                    });
                                    csvContent += header.join(sep) + '\\r\\n';
                                    masterX.forEach(function(xVal) {
                                        var row = [Number(xVal).toFixed(3)];
                                        lookupMaps.forEach(function(map) {
                                            var point = map.get(xVal);
                                            row.push(point ? Number(point.y).toFixed(3) : '');
                                        });
                                        csvContent += row.join(sep) + '\\r\\n';
                                    });
                                    var blob = new Blob(['\\uFEFF' + csvContent], { type: 'text/csv;charset=utf-8;' });
                                    var url = URL.createObjectURL(blob);
                                    var link = document.createElement('a');
                                    link.setAttribute('href', url);
                                    link.setAttribute('download', 'HP-Data_" . $code_metrique . ".csv');
                                    document.body.appendChild(link); link.click(); document.body.removeChild(link);
                                }
                            },
                            {
                                name: 'Export SVG', icon: Plotly.Icons.pencil,
                                click: function(gd) { Plotly.downloadImage(gd, {format: 'svg', filename: 'graph_" . $code_metrique . "'}); }
                            },
                            {
                                name: 'Export PNG', icon: Plotly.Icons.camera,
                                click: function(gd) { Plotly.downloadImage(gd, {format: 'png', filename: 'graph_" . $code_metrique . "'}); }
                            }
                        ]],
                        modeBarButtonsToRemove: ['select2d', 'lasso2d', 'autoScale2d', 'zoomIn2d', 'zoom2d','pan2d','resetScale2d', 'zoomOut2d']
                    };

                    Plotly.newPlot('" . $graph_id . "', data_metrique, layout, configDetails);
                })();
            ";
        }

        $html_stats .= "<hr></div>";

        // Annual minima details table
        $all_years = array_keys($qmnas_annuels + $dce_annuels + $vcn3_annuels + $vcn7_annuels + $vcn10_annuels + $vcn30_annuels);
        rsort($all_years);

        $html_stats .= "

                <div style='width:100%;margin-bottom:20px;'>

                    <p class='info_stats'>
                        " . TEXT_LOWFLOW_ANNEX_ANNUAL_MINIMA . "
                    </p>

                    <table id='table_tri' style='font-size:12px;'>
                        <tr>
                            <th style='width:50px;text-align:center;'>" . TEXT_STATS_YEAR . "</th>
                            <th style='width:70px;text-align:center;'>" . TEXT_LOWFLOW_QMNA_LABEL . "</th>
                            <th style='width:70px;text-align:center;'>" . TEXT_LOWFLOW_DCE_LABEL . "</th>
                            <th style='width:70px;text-align:center;'>" . TEXT_LOWFLOW_VCN3_LABEL . "</th>
                            <th style='width:70px;text-align:center;'>" . TEXT_LOWFLOW_VCN7_LABEL . "</th>
                            <th style='width:70px;text-align:center;'>" . TEXT_LOWFLOW_VCN10_LABEL . "</th>
                            <th style='width:70px;text-align:center;'>" . TEXT_LOWFLOW_VCN30_LABEL . "</th>
                        <tr>
                ";

                $row = 0;
                foreach ($all_years as $year)
                {
                    $qmna  = isset($qmnas_annuels[$year])  ? number_format($qmnas_annuels[$year], 3)  : '-';
                    $dce   = isset($dce_annuels[$year])    ? number_format($dce_annuels[$year], 3)    : '-';
                    $vcn3  = isset($vcn3_annuels[$year])   ? number_format($vcn3_annuels[$year], 3)   : '-';
                    $vcn7  = isset($vcn7_annuels[$year])   ? number_format($vcn7_annuels[$year], 3)   : '-';
                    $vcn10 = isset($vcn10_annuels[$year])  ? number_format($vcn10_annuels[$year], 3)  : '-';
                    $vcn30 = isset($vcn30_annuels[$year])  ? number_format($vcn30_annuels[$year], 3)  : '-';

                    if (fmod($row, 2) == 0) { $row_l = "class='row1' onmouseover=\"this.className='row1hover';\" onmouseout=\"this.className='row1';\" "; }
                    else                    { $row_l = "class='row2' onmouseover=\"this.className='row2hover';\" onmouseout=\"this.className='row2';\" "; }

                    $html_stats .= "
                                    <tr " . $row_l . ">
                                        <td style='height:25px;padding:0;text-align:center;font-size:12px;font-weight:bold;'>
                                            <span>" . htmlspecialchars($year) . "</span>
                                        </td>
                                        <td style='text-align:center;height:10px;'>" . htmlspecialchars($qmna) . "</td>
                                        <td style='text-align:center;height:10px;'>" . htmlspecialchars($dce) . "</td>
                                        <td style='text-align:center;height:10px;'>" . htmlspecialchars($vcn3) . "</td>
                                        <td style='text-align:center;height:10px;'>" . htmlspecialchars($vcn7) . "</td>
                                        <td style='text-align:center;height:10px;'>" . htmlspecialchars($vcn10) . "</td>
                                        <td style='text-align:center;height:10px;'>" . htmlspecialchars($vcn30) . "</td>
                                    </tr>
                                ";

                    $row++;
                }

                $html_stats .= "</table>";
        $html_stats .= "</div>";
    }

$html_stats_graph .= $html_tab_metriques;


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