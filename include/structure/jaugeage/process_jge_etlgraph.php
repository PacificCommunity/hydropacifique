<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
JGE / ETL graph handler - AJAX server-side process
- Generates the rating curve (ETL) + gauging points (JGE) Plotly graph
- Called from the ETL page
----------------------------------------
*/

// -----------------------------------------------
// Core dependencies: config, DB tables, functions

require('../../config.php');
require('../../database_tables.php');

require('../../function/date.php');
require('../../function/database.php');
require('../../function/html_output.php');
require('../../function/general.php');

// Ensure proper UTF-8 encoding for accented characters
header('Content-Type: text/html; charset=utf-8');

// Database connection
$sql_link = mysqli_connect(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD, DB_DATABASE)
    or die('Impossible de se connecter à la base de données!');
mysqli_query($sql_link, 'SET NAMES UTF8');

// Load translation strings for the active language
require('../../text_content_' . LANGUAGE . '.php');


// -----------------------------------------------
// Parse JSON input from AJAX request

$dataJson   = json_decode(file_get_contents('php://input'), true);

// Sanitize inputs. These values are interpolated into SQL and into the
// generated Plotly JS, so each is constrained to its expected type:
//  - id_station: integer identifier
//  - jge_hmoy / jge_q: numeric values injected into the graph (cast to float)
//  - jge_date / jge_heure: strings used in SQL and JS (escaped where used)
$id_station = isset($dataJson['idStation']) ? (int) $dataJson['idStation'] : 0;
$jge_hmoy   = isset($dataJson['jgeHmoy'])   ? (float) $dataJson['jgeHmoy']  : 0;
$jge_q      = isset($dataJson['jgeQ'])      ? (float) $dataJson['jgeQ']     : 0;
$jge_date   = isset($dataJson['jgeDate'])   ? $dataJson['jgeDate']          : '';
$jge_heure  = isset($dataJson['jgeHeure'])  ? $dataJson['jgeHeure']         : '';

// Swap-axes preference (1 = Q on X / H on Y, 0 = default H on X / Q on Y).
// When on, every trace's x/y are exchanged, along with the hovertemplate
// placeholders and the axis titles/ranges further down.
$swap_axes = isset($dataJson['swapAxes']) ? (int) $dataJson['swapAxes'] : 0;

// Hovertemplate placeholders for H and Q. With swap on, H reads from the Y
// channel and Q from the X channel (and vice-versa). Centralised here so the
// three traces below stay consistent without duplicating the logic.
$ph_h = $swap_axes ? '%{y:.0f}' : '%{x:.0f}';
$ph_q = $swap_axes ? '%{x:.3f}' : '%{y:.3f}';


// -----------------------------------------------
// Initialize result variables

$text_info  = '';
$edit_graph = false;
$js_graph   = '';


// -----------------------------------------------
// Query: Station info

$station_tab = tep_db_fetch_array(tep_db_query($sql_link,
    "SELECT DISTINCT s.id_station, s.nom_station, s.code_station, s.active_station, s.id_region
     FROM " . TABLE_STATION . " s WHERE s.id_station = " . $id_station));

$nom_station  = html_entity_decode($station_tab['nom_station']  ?? '');
$code_station = html_entity_decode($station_tab['code_station'] ?? '');

$text_info .= "<p style='margin-bottom:5px;font-size:16px;'>
                    <span style='font-weight:bold;'>" . TEXT_JGE_ETL_STATION . " </span>
                    " . $code_station . " - " . $nom_station . "
               </p>";


// -----------------------------------------------
// Initialize graph data variables

$data_graph     = '';
$data_graph_jge = '';
$load_data      = '';

$min_h_fix  = 100;
$max_h_fix  = 0;
$min_q_fix  = 50;
$max_q_fix  = 0;
$colorEtl   = "#3B6790";


// -----------------------------------------------
// Step 1: Find the rating curve (ETL) covering the gauging date

// Convert and escape the gauging date once for safe use in the SQL below
$jge_date_us_safe = mysqli_real_escape_string($sql_link, dateus_fr($jge_date));

$ETL_query = tep_db_query($sql_link,
    "SELECT DISTINCT id, datetime_first, datetime_end
     FROM " . TABLE_DATA_ETL . " etl
     WHERE id_station = " . $id_station . "
       AND datetime_first <= '" . $jge_date_us_safe . "'
       AND datetime_end   >= '" . $jge_date_us_safe . "'
     ORDER BY datetime_end DESC");

if (mysqli_num_rows($ETL_query) > 0)
{
    $ETL_tab        = tep_db_fetch_array($ETL_query);
    $id_etl         = $ETL_tab['id'];
    $datetime_first = $ETL_tab['datetime_first'];
    $datetime_end   = $ETL_tab['datetime_end'];

    $formatted_date_first = date('d-m-Y', strtotime($datetime_first));
    $formatted_date_end   = date('d-m-Y', strtotime($datetime_end));

    $text_info .= "<p style='margin-bottom:5px;font-size:14px;'>
                        <span style='font-weight:bold;'>" . TEXT_JGE_ETL_PERIODE . " </span>
                        " . TEXT_JGE_ETL_DU . " " . $formatted_date_first . " " . TEXT_JGE_ETL_AU . " " . $formatted_date_end . "
                   </p>";


    // -----------------------------------------------
    // Step 2: Load ETL data points for this rating curve

    $nb_pts  = 0;
    $graph_x = '';
    $graph_y = '';

    $ETL_data_query = tep_db_query($sql_link,
        "SELECT DISTINCT id, hauteur, debit, code_qualite
         FROM " . TABLE_DATA_ETL_DATA . "
         WHERE id_etl = " . $id_etl . " ORDER BY hauteur ASC");

    while ($ETL_data_tab = tep_db_fetch_array($ETL_data_query))
    {
        $graph_x .= $ETL_data_tab['hauteur'] . ',';
        $graph_y .= $ETL_data_tab['debit']   . ',';
        $nb_pts++;
    }

    $graph_x = rtrim($graph_x, ',');
    $graph_y = rtrim($graph_y, ',');

    if ($nb_pts > 0)
    {
        $data_graph .= "
            var data_etl =
            {
                x: [" . ($swap_axes ? $graph_y : $graph_x) . "],
                y: [" . ($swap_axes ? $graph_x : $graph_y) . "],
                name: '".TEXT_JGE_ETL_BOX_TITLE."',
                hovertemplate: '<span style=\"color:#C44545\"><b>".TEXT_JGE_ETL_BOX_TITLE."</b></span><br>' +
                            '<b>".TEXT_JGE_ETL_AXIS_H." :</b> " . $ph_h . " <br>' +
                            '<b>".TEXT_JGE_ETL_AXIS_Q." :</b> " . $ph_q . " ' +
                            '<extra></extra>',
                hoverlabel: {
                    bgcolor: 'rgba(255, 255, 255, 0.95)',
                    bordercolor: '#C44545',
                    font: { family: 'Arial, sans-serif', size: 14, color: '#333' },
                    align: 'left'
                },
                mode: 'markers+lines',
                type: 'scatter',
                marker: { size: 8, symbol: 'square', color: '#C44545' },
                line:   { color: '#C44545' }
            };
        ";
    }

    $load_data .= "data_etl,";

    // SQL to retrieve gauging points within the ETL validity period
    $sql_jge = "SELECT DISTINCT jge.id, jge.datetime, jge.depouil_hmoy, jge.depouil_q
                FROM " . TABLE_DATA_JGE . " jge
                WHERE jge.id_station = " . $id_station . "
                  AND jge.datetime  >= '" . $datetime_first . "'
                  AND jge.datetime  <= '" . $datetime_end   . "'
                  AND jge.depouil_hmoy REGEXP '^-?[0-9]+(\\.[0-9]+)?$'
                  AND jge.depouil_hmoy < 9999
                  AND jge.depouil_q   REGEXP '^-?[0-9]+(\\.[0-9]+)?$'
                ORDER BY jge.datetime ASC";
}
else
{
    // No rating curve covers the gauging date — load all gauging points for the station
    $sql_jge = "SELECT DISTINCT jge.id, jge.datetime, jge.depouil_hmoy, jge.depouil_q
                FROM " . TABLE_DATA_JGE . " jge
                WHERE jge.id_station = " . $id_station . "
                  AND jge.depouil_hmoy REGEXP '^-?[0-9]+(\\.[0-9]+)?$'
                  AND jge.depouil_hmoy < 9999
                  AND jge.depouil_q   REGEXP '^-?[0-9]+(\\.[0-9]+)?$'
                ORDER BY jge.datetime ASC";

    $text_info .= "<p style='font-size:14px;'>
                        <span style='font-weight:bold;'>" . TEXT_JGE_ETL_NO_ETL . "</span>
                   </p>";
}


// -----------------------------------------------
// Step 3: Load gauging points matching the ETL period (or all if no ETL)

$graph_x_jge    = '';
$graph_y_jge    = '';
$graph_date_jge = '';

$min_h_fix  = 99999;
$max_h_fix  = 0;
$min_q_fix  = 99999;
$max_q_fix  = 0;
$nb_pts_jge = 0;

$jge_query = tep_db_query($sql_link, $sql_jge);

if (mysqli_num_rows($jge_query) > 0)
{
    while ($jge_tab = tep_db_fetch_array($jge_query))
    {
        $h_jge              = abs($jge_tab['depouil_hmoy']);
        $q_jge              = abs($jge_tab['depouil_q']);
        $date_jge_raw       = $jge_tab['datetime'];
        $date_jge_formatee  = date("d-m-Y H:i:s", strtotime($date_jge_raw));

        $graph_x_jge    .= $h_jge . ',';
        $graph_y_jge    .= $q_jge . ',';
        $graph_date_jge .= "'" . $date_jge_formatee . "',";

        if ($min_h_fix > $h_jge) { $min_h_fix = $h_jge; }
        if ($max_h_fix < $h_jge) { $max_h_fix = $h_jge; }
        if ($min_q_fix > $q_jge) { $min_q_fix = $q_jge; }
        if ($max_q_fix < $q_jge) { $max_q_fix = $q_jge; }

        $nb_pts_jge++;
    }
}
else
{
    $text_info .= "<p style='font-size:14px;'>
                        <span style='font-weight:bold;'>" . TEXT_JGE_ETL_NO_JGE . "</span>
                   </p>";
}

$graph_x_jge    = rtrim($graph_x_jge,    ',');
$graph_y_jge    = rtrim($graph_y_jge,    ',');
$graph_date_jge = rtrim($graph_date_jge, ',');


// -----------------------------------------------
// Y-axis auto-scaling (Flow rate)
$q_range = $max_q_fix - $min_q_fix;

$q_top_padding    = $q_range * 0.10;   // 10% top margin
$q_bottom_padding = $q_range * 0.15;   // 15% bottom margin

$max_q_fix = $max_q_fix + $q_top_padding;
$min_q_fix = $min_q_fix - $q_bottom_padding;

// Limit how far below zero we go (proportional to the max, prevents -50 when max=100)
$zero_floor = -$max_q_fix * 0.03;  // max 3% of the top value below zero
if ($min_q_fix < $zero_floor) {
    $min_q_fix = $zero_floor;
}

// -----------------------------------------------
// X-axis auto-scaling (Height)
$h_range = $max_h_fix - $min_h_fix;
$h_padding = $h_range * 0.15;  // 15% padding on each side

$min_h_fix = $min_h_fix - $h_padding;
$max_h_fix = $max_h_fix + $h_padding;

// -----------------------------------------------
// Step 4: Build Plotly traces for gauging points

if ($nb_pts_jge > 0)
{
    $text_info .= "<p style='font-size:14px;'>
                        <span style='font-weight:bold;'>" . TEXT_JGE_ETL_NB_PTS . " </span>
                        " . $nb_pts_jge . "
                   </p>";

    $data_graph_jge .= "
        var data_jge =
        {
            x: [" . ($swap_axes ? $graph_y_jge : $graph_x_jge) . "],
            y: [" . ($swap_axes ? $graph_x_jge : $graph_y_jge) . "],
            customdata: [" . $graph_date_jge . "],
            name: '".TEXT_GAUGING."',
            hovertemplate: '<span style=\"color:" . $colorEtl . "\"><b>".TEXT_GAUGING."</b></span><br>' +
                        '<b>Date :</b> %{customdata}<br>' +
                        '<b>".TEXT_JGE_ETL_AXIS_H." :</b> " . $ph_h . " <br>' +
                        '<b>".TEXT_JGE_ETL_AXIS_Q." :</b> " . $ph_q . " ' +
                        '<extra></extra>',
            hoverlabel: {
                bgcolor: 'rgba(255, 255, 255, 0.95)',
                bordercolor: '" . $colorEtl . "',
                font: { family: 'Arial, sans-serif', size: 14, color: '#333' },
                align: 'left'
            },
            mode: 'markers',
            type: 'scatter',
            marker: {
                size: 14,
                symbol: 'star',
                color: '" . $colorEtl . "',
                line: { color: 'black', width: 1 }
            }
        };

        var special_pts =
        {
            x: [" . ($swap_axes ? $jge_q    : $jge_hmoy) . "],
            y: [" . ($swap_axes ? $jge_hmoy : $jge_q)    . "],
            customdata: [" . json_encode($jge_date . " " . $jge_heure) . "],
            name: '".TEXT_GAUGING." - " . TEXT_JGE_ETL_ENCOURS . "',
            hovertemplate: '<span style=\"color:#EFB036\"><b>".TEXT_GAUGING." - " . TEXT_JGE_ETL_ENCOURS . "</b></span><br>' +
                        '<b>Date :</b> %{customdata}<br>' +
                        '<b>".TEXT_JGE_ETL_AXIS_H." :</b> " . $ph_h . " <br>' +
                        '<b>".TEXT_JGE_ETL_AXIS_Q." :</b> " . $ph_q . " ' +
                        '<extra></extra>',
            hoverlabel: {
                bgcolor: 'rgba(255, 255, 255, 0.95)',
                bordercolor: '#EFB036',
                font: { family: 'Arial, sans-serif', size: 14, color: '#333' },
                align: 'left'
            },
            mode: 'markers',
            type: 'scatter',
            marker: {
                size: 26,
                symbol: 'star',
                color: '#EFB036',
                line: { color: 'black', width: 1 }
            }
        };
    ";

    $load_data .= "data_jge,special_pts,";
}

$load_data     = rtrim($load_data, ',');
$load_data_all = "[" . $load_data . "]";


// -----------------------------------------------
// Build Plotly layout

$layout_graph = "
    var layout =
    {
        xaxis:
        {
            title: {
                text: '" . ($swap_axes ? TEXT_JGE_ETL_AXIS_Q : TEXT_JGE_ETL_AXIS_H) . "',
                standoff: 20,
                font: { family: 'roboto, arial, helvetica', size: 14, bold: true, color: '#000000' }
            },
            autorange: false,
            range: [" . ($swap_axes ? $min_q_fix : $min_h_fix) . ", " . ($swap_axes ? $max_q_fix : $max_h_fix) . "],
        },
        yaxis:
        {
            title: {
                text: '" . ($swap_axes ? TEXT_JGE_ETL_AXIS_H : TEXT_JGE_ETL_AXIS_Q) . "',
                standoff: 30,
                font: { family: 'roboto, arial, helvetica', size: 16, bold: true, color: '#000000' }
            },
            autorange: false,
            range: [" . ($swap_axes ? $min_h_fix : $min_q_fix) . ", " . ($swap_axes ? $max_h_fix : $max_q_fix) . "],
        },
        autosize: true,
        
        hovermode: '',
        hoverlabel: { bgcolor: '#fff', font: { size: 16, color: '#000' } },
        margin: { l: 50, r: 10, t: 20, b: 40 },
        showlegend: true,
        legend: { x: 0.01, y: 1.05, orientation: 'v' },
    };
";


// -----------------------------------------------
// Build Plotly config

$config_graph = "
    var config =
    {
        responsive: true,
        doubleClickDelay: 1000,
        displaylogo: false,
        displayModeBar: true,
        scrollZoom: true,
        modeBarOrientation: 'v',
        modeBarButtons: [
            [
                {
                    name: 'Export SVG',
                    icon: Plotly.Icons.disk,
                    click: function(gd) {
                        Plotly.downloadImage(gd, { format: 'svg', filename: 'mon_grap' });
                    }
                },
                'toImage', 'zoom2d', 'pan2d', 'resetScale2d'
            ]
        ],
        modeBarButtonsToRemove: ['select2d', 'lasso2d', 'autoScale2d', 'zoomIn2d', 'zoomOut2d']
    };
";


// -----------------------------------------------
// Assemble final graph JS

$createGraph = "Plotly.newPlot('plot_etl', " . $load_data_all . ", layout, config);";


$js_graph   = $config_graph . $data_graph . $data_graph_jge . $layout_graph . $createGraph;
$edit_graph = true;


// -----------------------------------------------
// Return result as JSON

echo json_encode([
    'js_text'    => $text_info,
    'edit_graph' => $edit_graph,
    'js_graph'   => $js_graph,
]);
?>