<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Annual Statistics Module
Computes and displays annual statistics with a Plotly chart
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
    $axe_nbRound = isset($data_type_axe_tab['nb_round']) ? $data_type_axe_tab['nb_round'] : 0;


$html_stats = "";


// -------------------------------------------------------------
// ANNUAL STATISTICS — CALCULATION AND DISPLAY

    $nb_dec      = $axe_nbRound;
    $js_typegraph = "
        mode: 'lines',
        type: 'scatter',
        ";
    if ($typegraph == 'bar') { $js_typegraph = "type: 'bar',"; }
    $js_typegraph = "type: 'bar',"; // Force bar chart

    // Query: annual statistics (cumul, mean, std, min, max)
    $sql_stats_by_year = "
                            SELECT
                                YEAR(da.dateheure) AS year,
                                SUM(ABS(da.valeur)) AS cumul,
                                AVG(ABS(da.valeur)) AS moy,
                                STD(ABS(da.valeur)) AS std,
                                MIN(ABS(da.valeur)) AS min,
                                MAX(ABS(da.valeur)) AS max
                            FROM
                                " . TABLE_DATA_ALL . " da
                            JOIN
                                " . TABLE_DATA_META . " dm ON da.id_meta=dm.id
                            WHERE
                                dm.id_typedata = " . $id_typedata . "
                                AND dm.id_station = " . $cle_station . "
                                AND da.dateheure >= '" . $format_min_x . "'
                                AND da.dateheure <= '" . $format_max_x . "'
                                AND da.valeur NOT IN (9999, -9999, 8888, -8888, 99999, -99999, 88888, -88888)
                            GROUP BY
                                YEAR(da.dateheure)
                            ORDER BY
                                year DESC
                        ";

    $stats_by_year       = array();
    $stats_by_year_query = tep_db_query($sql_link, $sql_stats_by_year);
    while ($stats_by_year_tab = tep_db_fetch_array($stats_by_year_query))
    {
        $year = $stats_by_year_tab['year'];

        $cumul        = (float)$stats_by_year_tab['cumul'];
        $cumul_format = round($cumul, $nb_dec);

        $moy        = (float)$stats_by_year_tab['moy'];
        $moy_format = round($moy, $nb_dec);

        $std        = (float)$stats_by_year_tab['std'];
        $std_format = round($std, $nb_dec);

        $min        = (float)$stats_by_year_tab['min'];
        $min_format = round($min, $nb_dec);

        $max        = (float)$stats_by_year_tab['max'];
        $max_format = round($max, $nb_dec);

        $stats_by_year[$year] = array(
            'cumul'        => $cumul,
            'cumul_format' => $cumul_format,
            'moy'          => $moy,
            'moy_format'   => $moy_format,
            'std'          => $std,
            'std_format'   => $std_format,
            'min'          => $min,
            'min_format'   => $min_format,
            'max'          => $max,
            'max_format'   => $max_format
        );
    }

    // Compute interannual median — cumul for rainfall, mean for others
    if ($id_eq_type_data == 1)
    {
        $cumuls_array        = array_column($stats_by_year, 'cumul');
        $metrique_data       = calculate_percentile($cumuls_array, 50.0);
        $metrique_data_format = round((float)$metrique_data, $nb_dec);
        $titre_metrique      = TEXT_STATS_MEDIAN;
    }
    else
    {
        $mean_array          = array_column($stats_by_year, 'moy');
        $metrique_data       = calculate_percentile($mean_array, 50.0);
        $metrique_data_format = round((float)$metrique_data, $nb_dec);
        $titre_metrique      = TEXT_STATS_MEDIAN;
    }

    // Build HTML — Annual statistics table
    $x_tab_graph   = '';
    $x_first_graph = '';
    $x_last_graph  = '';
    $y_tab_graph   = '';

    // Column extremes for highlighting (lowest = blue, highest = light red),
    // computed over all displayed years (non-cumulative types only).
    $col_min_lo = $col_min_hi = null;
    $col_max_lo = $col_max_hi = null;
    $col_moy_lo = $col_moy_hi = null;
    if ($id_eq_type_data != 1 && count($stats_by_year) > 0)
    {
        $col_min_vals = array_column($stats_by_year, 'min');
        $col_max_vals = array_column($stats_by_year, 'max');
        $col_moy_vals = array_column($stats_by_year, 'moy');
        $col_min_lo = min($col_min_vals); $col_min_hi = max($col_min_vals);
        $col_max_lo = min($col_max_vals); $col_max_hi = max($col_max_vals);
        $col_moy_lo = min($col_moy_vals); $col_moy_hi = max($col_moy_vals);
    }
    $hl_lo = "background-color:#A8F1FF;";
    $hl_hi = "background-color:#FFDCDC;";

    // Build the graph series (years ascending order independent of display).
    // $stats_by_year is ordered by year DESC; first/last for the median line.
    $row = 0;
    foreach ($stats_by_year as $year => $stats)
    {
        $val = ($id_eq_type_data == 1) ? $stats['cumul'] : $stats['moy'];
        $x_tab_graph .= $year . ',';
        $y_tab_graph .= $val . ',';
        if ($row < 1) { $x_first_graph = $year; }
        $x_last_graph = $year;
        $row++;
    }

    // Group years by calendar decade (e.g. 2020 -> 2020-2029), display the
    // most recent decade first, decades side by side.
    $by_decade = array();
    foreach ($stats_by_year as $year => $stats)
    {
        $dec = (int)(floor($year / 10) * 10);
        $by_decade[$dec][$year] = $stats;
    }
    krsort($by_decade); // most recent decade first

    // Small helper: render one decade table.
    $render_decade = function($dec, $years_stats) use ($id_eq_type_data, $col_min_lo, $col_min_hi, $col_max_lo, $col_max_hi, $col_moy_lo, $col_moy_hi, $hl_lo, $hl_hi) {
        krsort($years_stats); // recent year first within the decade
        $label = $dec . " - " . ($dec + 9);

        $h = "<div style='display:inline-block;vertical-align:top;margin:0 18px 18px 0;'>";
        $h .= "<table id='table_tri' style='font-size:12px;'>";
        $h .= "<tr><th colspan='" . ($id_eq_type_data == 1 ? 2 : 5) . "' style='text-align:center;font-size:12px;background:#eef3f8;'>" . $label . "</th></tr>";

        if ($id_eq_type_data == 1)
        {
            $h .= "<tr>
                     <th style='width:70px;text-align:center;font-size:12px;'><span>" . TEXT_STATS_YEAR . "</span></th>
                     <th style='width:90px;text-align:center;font-size:12px;color:#930000;'><span>" . TEXT_STATS_CUMUL . "</span></th>
                   </tr>";
        }
        else
        {
            $h .= "<tr>
                     <th style='width:60px;text-align:center;font-size:12px;'><span>" . TEXT_STATS_YEAR . "</span></th>
                     <th style='width:70px;text-align:center;font-size:12px;'><span>" . TEXT_STATS_MINIMUM . "</span></th>
                     <th style='width:70px;text-align:center;font-size:12px;'><span>" . TEXT_STATS_MAXIMUM . "</span></th>
                     <th style='width:70px;text-align:center;font-size:12px;color:#930000;'><span>" . TEXT_STATS_MEAN . "</span></th>
                     <th style='width:70px;text-align:center;font-size:12px;color:#930000;'><span>" . TEXT_STATS_STD_DEV . "</span></th>
                   </tr>";
        }

        $rr = 0;
        foreach ($years_stats as $year => $st)
        {
            $cls = (fmod($rr, 2) == 0) ? 'row1' : 'row2';
            if ($id_eq_type_data == 1)
            {
                $h .= "<tr class='" . $cls . "'>
                         <td style='text-align:center;font-weight:bold;'>" . $year . "</td>
                         <td style='text-align:center;'>" . $st['cumul_format'] . "</td>
                       </tr>";
            }
            else
            {
                $bg_min = ($col_min_hi != $col_min_lo) ? (($st['min'] == $col_min_lo) ? $hl_lo : (($st['min'] == $col_min_hi) ? $hl_hi : '')) : '';
                $bg_max = ($col_max_hi != $col_max_lo) ? (($st['max'] == $col_max_lo) ? $hl_lo : (($st['max'] == $col_max_hi) ? $hl_hi : '')) : '';
                $bg_moy = ($col_moy_hi != $col_moy_lo) ? (($st['moy'] == $col_moy_lo) ? $hl_lo : (($st['moy'] == $col_moy_hi) ? $hl_hi : '')) : '';

                $h .= "<tr class='" . $cls . "'>
                         <td style='text-align:center;font-weight:bold;'>" . $year . "</td>
                         <td style='text-align:center;" . $bg_min . "'>" . $st['min_format'] . "</td>
                         <td style='text-align:center;" . $bg_max . "'>" . $st['max_format'] . "</td>
                         <td style='text-align:center;" . $bg_moy . "'>" . $st['moy_format'] . "</td>
                         <td style='text-align:center;'>" . $st['std_format'] . "</td>
                       </tr>";
            }
            $rr++;
        }
        $h .= "</table></div>";
        return $h;
    };

    $html_stats .= "
        <p class='info_stats'>
            " . TEXT_STATS_ANNUAL_SUMMARY . "
        </p>
        <div style='margin-bottom:30px;'>";
    foreach ($by_decade as $dec => $years_stats) {
        $html_stats .= $render_decade($dec, $years_stats);
    }
    $html_stats .= "</div>";


    // Graph section
    $stat_graph       = true;
    $html_stats_graph = '';

    $x_tab_graph = rtrim($x_tab_graph, ',');
    $y_tab_graph = rtrim($y_tab_graph, ',');

    if ($id_eq_type_data == 1)
    {
        $html_stats_graph .= "
                                <p class='info_stats'>
                                    " . TEXT_STATS_ANNUAL_CUMUL_CHART . "
                                </p>

                                <div id='plotStats' class='graph_stats' style='height:350px;'>
                                </div>
                            ";
    }
    else
    {
        $html_stats_graph .= "
                                <p class='info_stats'>
                                    " . TEXT_STATS_ANNUAL_SUMMARY_CHART . "
                                </p>

                                <div id='plotStats' class='graph_stats' style='height:300px;'>
                                </div>
                            ";
    }


    // Build Plotly.js chart code
    $js_graph = "
                    const dataStatGraph =
                    [
                        {
                            x: [" . $x_tab_graph . "],
                            y: [" . $y_tab_graph . "],
                            " . $js_typegraph . "
                            name: '" . $axe . "',
                            marker: {
                                        color:'#9BB4C0',
                                        line:{ color:'#000', width: 1.1 }
                                    },
                            hoverinfo: 'x+y',
                            hoverlabel: { bgcolor: '#fff', font: { size: 14, color: '#000' } },
                            hovertemplate: '<b>" . $axe . "</b> : %{y:." . $nb_dec . "f} " . $axe_unite . "<extra></extra>',
                        },

                        {
                            x: [" . $x_first_graph . "," . $x_last_graph . "],
                            y: [" . $metrique_data . "," . $metrique_data . "],
                            name: '" . $titre_metrique . " : " . $metrique_data_format . " (" . $axe_unite . ")',
                            type: 'scatter',
                            mode: 'lines',
                            line: { color: '#930000', width: 2, dash: 'dot' },
                            hoverinfo: 'y+name',
                            hoverlabel: false,
                            hovertemplate: '<b>" . $titre_metrique . "</b> : %{y:." . $nb_dec . "f} " . $axe_unite . "<extra></extra>',
                        }
                    ]


                    const layoutStatGraph =
                    {
                        xaxis: { tickmode: 'linear', fixedrange: true },
                        yaxis: {
                            title: '" . $axe . " (" . $axe_unite . ")',
                            titlefont: {family: 'roboto, arial, helvetica', size: 13, bold: true, color: '#000000'},
                            tickfont: {size: 11},
                            ticklen: 5,
                            tickformat: '." . $nb_dec . "f',
                            showline: true,
                            linewidth: 1,
                        },
                        showlegend: true,
                        legend: { x: 0, y: 1.1, orientation: 'h', font: {size: 11} },
                        barmode: 'group',
                        bargap: 0.4,
                        bargroupgap: 0,
                        hovermode: 'x',
                        hoverlabel: {bgcolor: '#fff', font: { size: 12, color: '#000' } },
                        margin: {l: 60, r: 10, t: 10, b: 40},
                    };

                    Plotly.newPlot('plotStats', dataStatGraph, layoutStatGraph, config);
                ";


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