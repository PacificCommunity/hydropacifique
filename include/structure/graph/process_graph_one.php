<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Dual-axis graph data loader
- Receives a JSON payload from an AJAX request
- Queries chronological data for axis 1 and axis 2
- Builds Plotly.js trace definitions, layout, and interaction handlers
- Returns the complete JS graph initialization code as a JSON response
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


// -----------------------------------------------
// Load translation strings for the active language

require('../../text_content_' . LANGUAGE . '.php');


// -----------------------------------------------
// Parse incoming JSON payload from AJAX request

$jsonDataGraph = file_get_contents('php://input');
$dataJson      = json_decode($jsonDataGraph, true);

// Extract date range (note: dateEnd/dateFirst keys are intentionally swapped in source data)
$date_first = DateTime::createFromFormat('d-m-Y', $dataJson['dateEnd']);
$date_end   = DateTime::createFromFormat('d-m-Y', $dataJson['dateFirst']);

// Axis label and unit for both Y axes
$nom_axe1   = $dataJson['nomAxe1'];
$unite_axe1 = $dataJson['uniteAxe1'];
$round_axe1 = $dataJson['roundAxe1'];
$nom_axe2   = $dataJson['nomAxe2'];
$unite_axe2 = $dataJson['uniteAxe2'];
$round_axe2 = $dataJson['roundAxe2'];

$reload = $dataJson['reload'];

// Initialize Y-axis min/max trackers for auto-scaling
$y_min1 = 99999;
$y_max1 = 0;
$y_min2 = 99999;
$y_max2 = 0;

// Trace definitions per axis, each entry: {idStation, idChron, sql, color}
$tab_axe1 = $dataJson['axe1'];
$tab_axe2 = $dataJson['axe2'];


// -----------------------------------------------
// Tooltip configuration (parity with process_graph_multi.php)
//
// TOOLTIP_WRAP_WIDTH defines after how many characters obs/obs_user text
// is wrapped in the hover tooltip. Same value as the multi-graph view
// so both modes look identical when hovering a point.
if (!defined('TOOLTIP_WRAP_WIDTH')) { define('TOOLTIP_WRAP_WIDTH', 50); }

// Quality-code lookup: maps id_codequal → short label used in tooltips
// ("Quality Code : 4-B_B"). Loaded once, reused for every trace.
$code_qual_array = array();
$sql_code_qual   = "SELECT DISTINCT id_data_qualite, init_qualite_data, nom_qualite_data
                    FROM " . TABLE_DATA_QUALITE . "
                    ORDER BY id_data_qualite";
$code_qual_query = tep_db_query($sql_link, $sql_code_qual);
while ($code_qual = tep_db_fetch_array($code_qual_query)) {
    $code_qual_array[$code_qual['id_data_qualite']] = array(
        'init_qualite_data' => html_entity_decode($code_qual['init_qualite_data'] ?? ''),
        'nom_qualite_data'  => html_entity_decode($code_qual['nom_qualite_data']  ?? ''),
    );
}


// -----------------------------------------------
// Initialize output variables

$lacune_date_first    = '';
$html_tab_lacune_temp = '';

$graph_x_axe1 = '';
$graph_y_axe1 = '';
$text_yaxis1  = '';

$graph_x_axe2 = '';
$graph_y_axe2 = '';
$text_yaxis2  = '';

$data_graph = '';
$load_data  = '';

$edit_lacune_axe1 = '';
$edit_lacune_axe2 = '';


// -----------------------------------------------
// AXIS 1 — Build Plotly traces for all axis-1 series

if (isset($tab_axe1) && sizeof($tab_axe1) > 0)
{
    $graph_x_axe1 = '';
    $graph_y_axe1 = '';
    $text_yaxis1  = '';

    $edit_lacune_temp = '';

    foreach ($tab_axe1 as $i => $row)
    {
        $idStation_trace = isset($row['idStation']) ? (int)$row['idStation']    : 0;
        // Keep idChron as-is — it can be a string ('ra') for manual readings.
        // Numeric ids are cast to int for the WHERE clause below.
        $idChron_raw     = isset($row['idChron'])   ? $row['idChron']           : '';
        $sql_trace       = isset($row['sql'])       ? (string)$row['sql']       : '';
        $color_trace     = isset($row['color'])     ? (string)$row['color']     : '';

        // -------------------------------------------------------------
        // Marker-series branches (RA, JGE).
        // These records don't live in TABLE_TYPE_DATA and don't follow
        // the (dateheure, value) schema of classic chronicles, so we
        // route them to dedicated helpers that mirror the equivalent
        // blocks in process_graph_multi.php. Each helper builds its
        // scattergl/markers Plotly trace and appends it to $data_graph
        // / $load_data, then we `continue` to the next iteration.
        // -------------------------------------------------------------
        if ($idChron_raw === 'ra')
        {
            buildRaTrace(
                $sql_link, 'axe1', 'y1',
                $idStation_trace, $sql_trace, $color_trace,
                $data_graph, $load_data,
                $y_min1, $y_max1, $date_first, $date_end,
                $text_yaxis1, $nom_axe1, $unite_axe1, $round_axe1
            );
            continue;
        }
        if ($idChron_raw === 'jge')
        {
            buildJgeTrace(
                $sql_link, 'axe1', 'y1',
                $idStation_trace, $sql_trace, $color_trace,
                $data_graph, $load_data,
                $y_min1, $y_max1, $date_first, $date_end,
                $text_yaxis1, $nom_axe1, $unite_axe1, $round_axe1
            );
            continue;
        }

        $idChron_trace = (int)$idChron_raw;

        // Query: chronological data type metadata for this axis-1 trace
        $sql_type_chron = "SELECT DISTINCT id_data_type, init_type_data, nom_type_data, id_eq_type_data, axe_data, unite, traitement, type_graph
                           FROM " . TABLE_TYPE_DATA . "
                           WHERE id_data_type = " . $idChron_trace;
        $type_chron_query = tep_db_query($sql_link, $sql_type_chron);
        $type_chron_tab   = tep_db_fetch_array($type_chron_query);

        $init_type_data  = $type_chron_tab['init_type_data'];
        $traitement_axe1 = $type_chron_tab['traitement'];
        $type_graph_axe1 = $type_chron_tab['type_graph'];

        // Query: station metadata for this axis-1 trace
        $sql_station = "SELECT DISTINCT id_station, nom_station, code_station, active_station, station_type
                        FROM " . TABLE_STATION . "
                        WHERE id_station = " . $idStation_trace;
        $station_query = tep_db_query($sql_link, $sql_station);
        $station_tab   = tep_db_fetch_array($station_query);
        $nom_station   = isset($station_tab['nom_station']) ? $station_tab['nom_station'] : '';
        $nom_station   = affichelettres($nom_station, 18);

        $graph_x = '';
        $graph_y = '';
        $cumul   = 0; // Running total used when traitement == 1 (cumulative mode)

        // Tooltip customdata accumulator. Each pushed entry must mirror
        // a (x, y) sample in $graph_x / $graph_y exactly — including gaps
        // (we push a "blank" record so indices stay aligned). The final
        // json_encode is done once after the loop.
        // Layout — same as process_graph_multi.php:
        //   [0] FR-formatted date
        //   [1] rounded value
        //   [2] quality code line (or '')
        //   [3] obs line (or '')
        //   [4] obs_user line (or '')
        $customdata_arr = array();

        // Iterate over all data records for this axis-1 trace
        $nb_data_axe      = 0;
        $data_chron_query = tep_db_query($sql_link, $sql_trace);

        while ($data_chron_tab = tep_db_fetch_array($data_chron_query))
        {
            // Parse the record timestamp into a DateTime object
            $date_chron = new DateTime($data_chron_tab['dateheure']);

            if ($nb_data_axe > 0)
            {
                $graph_x .= ',';
                $graph_y .= ',';
            }
            else
            {
                // Track the earliest date across all traces
                if ($date_chron < $date_first) { $date_first = $date_chron; }
            }

            // Track the latest date across all traces
            if ($date_chron > $date_end) { $date_end = $date_chron; }

            // Y-axis label: axis name + unit
            $text_yaxis1 = $nom_axe1 . ' (' . $unite_axe1 . ')';

            $nb_data_axe++;

            // ---- Gap (lacune) currently open: close it ----
            if (tep_not_null($lacune_date_first))
            {
                $edit_lacune_axe1 .= $edit_lacune_temp;

                // Valid value: close the gap shape at this point
                if (!in_array(abs($data_chron_tab['valeur']), [9999, 99999, 8888, 88888]))
                {
                    $graph_x .= "'" . $data_chron_tab['dateheure'] . "'";

                    if ($traitement_axe1 == 0) { $valeur  = $data_chron_tab['valeur']; } // raw value
                    if ($traitement_axe1 == 1) { $valeur += $data_chron_tab['valeur']; } // cumulative
                    //$valeur = abs($valeur); // specific NC

                    $graph_y .= $valeur;

                    if ($valeur > $y_max1) { $y_max1 = $valeur; }
                    if ($valeur < $y_min1) { $y_min1 = $valeur; }

                    $customdata_arr[] = buildPointCustomdata($data_chron_tab, $valeur, $code_qual_array, $round_axe1);

                    $edit_lacune_axe1 .= "   x1: '" . $lacune_date_first . "',";
                }
                else
                {
                    // Consecutive gap: extend the null series
                    $graph_x .= "'" . $data_chron_tab['dateheure'] . "',";
                    $graph_y .= 'null,';
                    $customdata_arr[] = ['','','','',''];  // align customdata with the null y point

                    $chron_dateheure_tab = explode(' ', $data_chron_tab['dateheure']);
                    $chron_dateheure_fr  = dateus_fr($chron_dateheure_tab[0]) . ' ' . $chron_dateheure_tab[1];

                    $edit_lacune_axe1 .= "   x1: '" . $data_chron_tab['dateheure'] . "',";
                }

                // Close the Plotly shape definition for this gap
                $edit_lacune_axe1 .= "  y1: 1,
                                        fillcolor: '" . $color_trace . "',
                                        opacity: 0.15,
                                        line: {width: 0},
                                        customType: 'axe1', // Identifies which axis this gap shape belongs to
                                    },";

                $lacune_date_first = ''; // Reset gap tracker

            }
            else // No gap currently open
            {
                // Valid value: append to graph data
                if (!in_array(abs($data_chron_tab['valeur']), [9999, 99999, 8888, 88888]))
                {
                    $graph_x .= "'" . $data_chron_tab['dateheure'] . "'";

                    if ($traitement_axe1 == 0) { $valeur  = $data_chron_tab['valeur']; } // raw value
                    if ($traitement_axe1 == 1) { $valeur += $data_chron_tab['valeur']; } // cumulative
                    //$valeur = abs($valeur); // specific NC

                    $graph_y .= $valeur;

                    if ($valeur > $y_max1) { $y_max1 = $valeur; }
                    if ($valeur < $y_min1) { $y_min1 = $valeur; }

                    $customdata_arr[] = buildPointCustomdata($data_chron_tab, $valeur, $code_qual_array, $round_axe1);
                }
                else
                {
                    // Gap value detected: open a new gap shape
                    $graph_x .= "'" . $data_chron_tab['dateheure'] . "'";
                    $graph_y .= 'null';
                    $customdata_arr[] = ['','','','',''];  // align customdata with the null y point

                    $html_tab_lacune_temp = '';
                    // Separator between gap shape definitions — only between,
                    // never before the first one. A leading comma would yield
                    // shapes: [, {...}] in the Plotly layout, which is invalid
                    // JS and silently disables the gap overlay.
                    if ($edit_lacune_axe1 !== '')
                    {
                        $edit_lacune_axe1 .= ',';
                    }

                    $edit_lacune_temp = "
                                    {
                                        type: 'rect',
                                        xref: 'x',      // x-reference is assigned to the x-values
                                        yref: 'paper',  // y-reference is assigned to the plot paper [0,1]
                                        x0: '" . $data_chron_tab['dateheure'] . "',
                                        y0: 0,
                                    ";

                    $lacune_date_first = $data_chron_tab['dateheure'];
                }
            }
        } // end while axis 1 data

        // -----------------------------------------------
        // Determine the Plotly chart type for this axis-1 trace

        $code_type_graph = '';

        if ($type_graph_axe1 == 'lines')
        {
            $code_type_graph  = "mode: 'lines',";
            $code_type_graph .= "type: 'scattergl',";
        }

        if ($type_graph_axe1 == 'markers')
        {
            // Scatter plot: each data point is shown as a dot, no line.
            // scattergl keeps performance smooth even for tens of thousands
            // of points. Marker size defaults to 6px; opacity slightly below 1
            // helps when many points overlap densely in the same area.
            $code_type_graph  = "mode: 'markers',";
            $code_type_graph .= "type: 'scattergl',";
            $code_type_graph .= "marker: { size: 6, color: '" . $color_trace . "', opacity: 0.85 },";
        }

        if ($type_graph_axe1 == 'bar')
        {
            $code_type_graph = "type: 'bar',";

            // For very large datasets (>8000 pts), use scattergl with step-line fill
            // to mimic bar chart appearance while maintaining rendering performance
            if ($nb_data_axe > 8000)
            {
                $code_type_graph = "
                                    type: 'scattergl',
                                    mode: 'lines',
                                    line: { shape: 'hv', width: 1 }, // 'hv' produces staircase steps (right angles)
                                    fill: 'tozeroy',                  // Fill to zero baseline (mimics solid bars)
                                    fillcolor: '" . $color_trace . "',
                                    ";
            }
        }

        // Build trace name and legend group as safely encoded JSON strings
        $name_js        = json_encode($nom_station . ' - ' . $init_type_data, JSON_UNESCAPED_UNICODE);
        $legendgroup_js = json_encode('tdc_axe1_' . $idStation_trace . '_' . $idChron_trace, JSON_UNESCAPED_UNICODE);

        // Customdata: JSON-encoded array of [date, value, qual, obs, obs_user]
        // entries — one per data sample. Each entry is consumed by the
        // %{customdata[N]} placeholders in the hovertemplate below.
        $customdata_js = json_encode($customdata_arr, JSON_UNESCAPED_UNICODE);

        // Rich hovertemplate (parity with the multi-graph view).
        // The trace title keeps the "<station> - <init>" wording — what
        // changes is that it's now wrapped in the trace color, so each
        // tooltip block reads visually as "its own" series in the
        // unified hover. Empty customdata fields produce no output, so
        // qual/obs/obs_user lines auto-collapse when not relevant.
        $hover_title_html = '<b><span style="color:' . $color_trace . '">'
                          . $nom_station . ' - ' . $init_type_data
                          . '</span></b>';
        $hovertemplate_axe1 = json_encode(
              $hover_title_html
            . '<br><b>' . TEXT_GRAPH_HOVER_DATE . '</b> : %{customdata[0]}'
            . '<br><b>' . $nom_axe1 . '</b> : %{customdata[1]} ' . $unite_axe1
            . '%{customdata[2]}'
            . '%{customdata[3]}'
            . '%{customdata[4]}'
            . '<extra></extra>',
            JSON_UNESCAPED_UNICODE
        );

        // -----------------------------------------------
        // Generate the Plotly trace variable for this axis-1 series

        $data_graph .= "
                        var trace_axe1_{$idStation_trace}_{$idChron_trace} =
                        {
                            hovermode: 'closest',

                            x: [{$graph_x}],
                            y: [{$graph_y}],
                            customdata: {$customdata_js},

                            {$code_type_graph} // Chart type: bar, lines, scatter, etc.

                            legendgroup: {$legendgroup_js}, // Logical group for user-controlled trace styling

                            name: {$name_js},

                            yaxis: 'y1', // Bind this trace to the left Y-axis

                            // Hover tooltip — rich format with quality code + observations
                            hovertemplate: {$hovertemplate_axe1},

                            marker: {
                                color: '{$color_trace}',
                                line: {
                                    color: '{$color_trace}',
                                    width: 2,
                                }
                            },

                            line: {
                                color: '{$color_trace}',
                                width: 1.5,
                            },

                            textposition: 'none' // Hide in-bar/in-point data labels
                        };
                    ";

        // Register this trace for chart rendering if it contains data
        if ($nb_data_axe > 0)
        {
            $load_data .= "trace_axe1_{$idStation_trace}_{$idChron_trace},";
        }
    }
}


// -----------------------------------------------
// AXIS 2 — Build Plotly traces for all axis-2 series

if (isset($tab_axe2) && sizeof($tab_axe2) > 0)
{
    $edit_lacune_temp = '';

    foreach ($tab_axe2 as $i => $row)
    {
        $idStation_trace = isset($row['idStation']) ? (int)$row['idStation']    : 0;
        $idChron_raw     = isset($row['idChron'])   ? $row['idChron']           : '';
        $sql_trace       = isset($row['sql'])       ? (string)$row['sql']       : '';
        $color_trace     = isset($row['color'])     ? (string)$row['color']     : '';

        // RA / JGE branches — see axis-1 loop for full rationale.
        if ($idChron_raw === 'ra')
        {
            buildRaTrace(
                $sql_link, 'axe2', 'y2',
                $idStation_trace, $sql_trace, $color_trace,
                $data_graph, $load_data,
                $y_min2, $y_max2, $date_first, $date_end,
                $text_yaxis2, $nom_axe2, $unite_axe2, $round_axe2
            );
            continue;
        }
        if ($idChron_raw === 'jge')
        {
            buildJgeTrace(
                $sql_link, 'axe2', 'y2',
                $idStation_trace, $sql_trace, $color_trace,
                $data_graph, $load_data,
                $y_min2, $y_max2, $date_first, $date_end,
                $text_yaxis2, $nom_axe2, $unite_axe2, $round_axe2
            );
            continue;
        }

        $idChron_trace = (int)$idChron_raw;

        // Query: chronological data type metadata for this axis-2 trace
        $sql_type_chron = "SELECT DISTINCT id_data_type, init_type_data, nom_type_data, id_eq_type_data, axe_data, unite, traitement, type_graph
                           FROM " . TABLE_TYPE_DATA . "
                           WHERE id_data_type = " . $idChron_trace;
        $type_chron_query = tep_db_query($sql_link, $sql_type_chron);
        $type_chron_tab   = tep_db_fetch_array($type_chron_query);

        $init_type_data  = $type_chron_tab['init_type_data'];
        $traitement_axe2 = $type_chron_tab['traitement'];
        $type_graph_axe2 = $type_chron_tab['type_graph'];

        // Query: station metadata for this axis-2 trace
        $sql_station = "SELECT DISTINCT id_station, nom_station, code_station, active_station, station_type
                        FROM " . TABLE_STATION . "
                        WHERE id_station = " . $idStation_trace;
        $station_query = tep_db_query($sql_link, $sql_station);
        $station_tab   = tep_db_fetch_array($station_query);
        $nom_station   = isset($station_tab['nom_station']) ? $station_tab['nom_station'] : '';
        $nom_station   = affichelettres($nom_station, 18);

        $graph_x = '';
        $graph_y = '';
        $cumul   = 0; // Running total used when traitement == 1 (cumulative mode)

        // Tooltip customdata accumulator — see axis-1 loop for full
        // schema. Same 5-element layout.
        $customdata_arr = array();

        // Iterate over all data records for this axis-2 trace
        $nb_data_axe      = 0;
        $data_chron_query = tep_db_query($sql_link, $sql_trace);

        while ($data_chron_tab = tep_db_fetch_array($data_chron_query))
        {
            $date_chron = new DateTime($data_chron_tab['dateheure']);

            if ($nb_data_axe > 0)
            {
                $graph_x .= ',';
                $graph_y .= ',';
            }
            else
            {
                // Track the earliest date across all traces
                if ($date_chron < $date_first) { $date_first = $date_chron; }
            }

            // Track the latest date across all traces
            if ($date_chron > $date_end) { $date_end = $date_chron; }

            // Y-axis label: axis name + unit
            $text_yaxis2 = $nom_axe2 . ' (' . $unite_axe2 . ')';

            $nb_data_axe++;

            // ---- Gap (lacune) currently open: close it ----
            if (tep_not_null($lacune_date_first))
            {
                $edit_lacune_axe2 .= $edit_lacune_temp;

                // Valid value: close the gap shape at this point
                if (!in_array(abs($data_chron_tab['valeur']), [9999, 99999, 8888, 88888]))
                {
                    $graph_x .= "'" . $data_chron_tab['dateheure'] . "'";

                    if ($traitement_axe2 == 0) { $valeur  = $data_chron_tab['valeur']; } // raw value
                    if ($traitement_axe2 == 1) { $valeur += $data_chron_tab['valeur']; } // cumulative
                    //$valeur = abs($valeur); // specific NC

                    $graph_y .= $valeur;

                    if ($valeur > $y_max2) { $y_max2 = $valeur; }
                    if ($valeur < $y_min2) { $y_min2 = $valeur; }

                    $customdata_arr[] = buildPointCustomdata($data_chron_tab, $valeur, $code_qual_array, $round_axe2);

                    $edit_lacune_axe2 .= "   x1: '" . $lacune_date_first . "',";
                }
                else
                {
                    // Consecutive gap: extend the null series
                    $graph_x .= "'" . $data_chron_tab['dateheure'] . "',";
                    $graph_y .= 'null,';
                    $customdata_arr[] = ['','','','',''];

                    $chron_dateheure_tab = explode(' ', $data_chron_tab['dateheure']);
                    $chron_dateheure_fr  = dateus_fr($chron_dateheure_tab[0]) . ' ' . $chron_dateheure_tab[1];

                    $edit_lacune_axe2 .= "   x1: '" . $data_chron_tab['dateheure'] . "',";
                }

                // Close the Plotly shape definition for this gap
                $edit_lacune_axe2 .= "  y1: 1,
                                        fillcolor: '" . $color_trace . "',
                                        opacity: 0.15,
                                        line: {width: 0},
                                        customType: 'axe2', // Identifies which axis this gap shape belongs to
                                    },";

                $lacune_date_first = ''; // Reset gap tracker

            }
            else // No gap currently open
            {
                // Valid value: append to graph data
                if (!in_array(abs($data_chron_tab['valeur']), [9999, 99999, 8888, 88888]))
                {
                    $graph_x .= "'" . $data_chron_tab['dateheure'] . "'";

                    if ($traitement_axe2 == 0) { $valeur  = $data_chron_tab['valeur']; } // raw value
                    if ($traitement_axe2 == 1) { $valeur += $data_chron_tab['valeur']; } // cumulative
                    //$valeur = abs($valeur); // specific NC

                    $graph_y .= $valeur;

                    if ($valeur > $y_max2) { $y_max2 = $valeur; }
                    if ($valeur < $y_min2) { $y_min2 = $valeur; }

                    $customdata_arr[] = buildPointCustomdata($data_chron_tab, $valeur, $code_qual_array, $round_axe2);
                }
                else
                {
                    // Gap value detected: open a new gap shape
                    $graph_x .= "'" . $data_chron_tab['dateheure'] . "'";
                    $graph_y .= 'null';
                    $customdata_arr[] = ['','','','',''];

                    $html_tab_lacune_temp = '';

                    $edit_lacune_temp = "
                                    {
                                        type: 'rect',
                                        xref: 'x',      // x-reference is assigned to the x-values
                                        yref: 'paper',  // y-reference is assigned to the plot paper [0,1]
                                        x0: '" . $data_chron_tab['dateheure'] . "',
                                        y0: 0,
                                    ";

                    $lacune_date_first = $data_chron_tab['dateheure'];
                }
            }
        } // end while axis 2 data

        // -----------------------------------------------
        // Determine the Plotly chart type for this axis-2 trace

        $code_type_graph = '';

        if ($type_graph_axe2 == 'lines')
        {
            $code_type_graph  = "mode: 'lines',";
            $code_type_graph .= "type: 'scattergl',";
        }

        if ($type_graph_axe2 == 'markers')
        {
            // Same scatter-plot setup as axis 1 — see the axis-1 branch above
            // for the full rationale (scattergl performance, opacity, marker size).
            $code_type_graph  = "mode: 'markers',";
            $code_type_graph .= "type: 'scattergl',";
            $code_type_graph .= "marker: { size: 6, color: '" . $color_trace . "', opacity: 0.85 },";
        }

        if ($type_graph_axe2 == 'bar')
        {
            $code_type_graph = "type: 'bar',";

            // For very large datasets (>8000 pts), use scattergl with step-line fill
            // to mimic bar chart appearance while maintaining rendering performance
            if ($nb_data_axe > 8000)
            {
                $code_type_graph = "
                                    type: 'scattergl',
                                    mode: 'lines',
                                    line: { shape: 'hv', width: 1 }, // 'hv' produces staircase steps (right angles)
                                    fill: 'tozeroy',                  // Fill to zero baseline (mimics solid bars)
                                    fillcolor: '" . $color_trace . "',
                                    ";
            }
        }

        // Build trace name and legend group as safely encoded JSON strings
        $name_js        = json_encode($nom_station . ' - ' . $init_type_data, JSON_UNESCAPED_UNICODE);
        $legendgroup_js = json_encode('tdc_axe2_' . $idStation_trace . '_' . $idChron_trace, JSON_UNESCAPED_UNICODE);

        // Customdata + rich hovertemplate (parity with axis-1 loop)
        $customdata_js = json_encode($customdata_arr, JSON_UNESCAPED_UNICODE);

        $hover_title_html = '<b><span style="color:' . $color_trace . '">'
                          . $nom_station . ' - ' . $init_type_data
                          . '</span></b>';
        $hovertemplate_axe2 = json_encode(
              $hover_title_html
            . '<br><b>' . TEXT_GRAPH_HOVER_DATE . '</b> : %{customdata[0]}'
            . '<br><b>' . $nom_axe2 . '</b> : %{customdata[1]} ' . $unite_axe2
            . '%{customdata[2]}'
            . '%{customdata[3]}'
            . '%{customdata[4]}'
            . '<extra></extra>',
            JSON_UNESCAPED_UNICODE
        );

        // -----------------------------------------------
        // Generate the Plotly trace variable for this axis-2 series

        $data_graph .= "
                        var trace_axe2_{$idStation_trace}_{$idChron_trace} =
                        {
                            hovermode: 'closest',

                            x: [{$graph_x}],
                            y: [{$graph_y}],
                            customdata: {$customdata_js},

                            {$code_type_graph} // Chart type: bar, lines, scatter, etc.

                            legendgroup: {$legendgroup_js}, // Logical group for user-controlled trace styling

                            name: {$name_js},

                            yaxis: 'y2', // Bind this trace to the right Y-axis

                            // Hover tooltip — rich format with quality code + observations
                            hovertemplate: {$hovertemplate_axe2},

                            marker: {
                                color: '{$color_trace}',
                                line: {
                                    color: '{$color_trace}',
                                    width: 2,
                                }
                            },

                            line: {
                                color: '{$color_trace}',
                                width: 1.5,
                            },

                            textposition: 'none' // Hide in-bar/in-point data labels
                        };
                    ";

        // Register this trace for chart rendering if it contains data
        if ($nb_data_axe > 0)
        {
            $load_data .= "trace_axe2_{$idStation_trace}_{$idChron_trace},";
        }
    }
}


// -----------------------------------------------
// Finalize trace list: remove trailing comma

$load_data = substr($load_data, 0, -1);


// -----------------------------------------------
// Build gap (lacune) shapes config for Plotly layout.
//
// Both per-axis buffers ($edit_lacune_axe1, $edit_lacune_axe2) are
// concatenations of fully-formed `{...}` shape literals separated by
// commas. We must only insert a comma BETWEEN the two buffers when
// both carry content — otherwise we'd end up with one of these
// invalid JS forms (which silently disable the whole shapes array):
//   shapes: [, {...}]
//   shapes: [{...}, ]
//   shapes: [,]
$lac_parts = [];
if (trim($edit_lacune_axe1) !== '') { $lac_parts[] = $edit_lacune_axe1; }
if (trim($edit_lacune_axe2) !== '') { $lac_parts[] = $edit_lacune_axe2; }
$affiche_lac = "shapes: [" . implode(',', $lac_parts) . "],";


// -----------------------------------------------
// Compute Y-axis display ranges with padding

$date_first_str = $date_first->format('Y-m-d');
$date_end_str   = $date_end->format('Y-m-d');

// ---- Detect for each axis whether any trace was actually bound to it ----
// $y_min* / $y_max* are initialized to sentinel values (99999 / 0) at the
// top of the file. If no trace ran the data loop for an axis, they keep
// those sentinels and would render as a confusing "0 → 99998" range in
// the front-end inputs. Detect that case and emit empty values + a flag
// per axis.
$axis1_used = !($y_min1 === 99999 && $y_max1 === 0);
$axis2_used = !($y_min2 === 99999 && $y_max2 === 0);

if ($axis1_used) {
    $pad_y1          = max(0.5, 0.1 * ($y_max1 - $y_min1));
    $y1_min_graph    = $y_min1 - $pad_y1;
    $y1_max_graph    = 1.1 * ($y_max1 + $pad_y1);
    $y1_min_input_js = "parseInt({$y1_min_graph})";
    $y1_max_input_js = "parseInt({$y1_max_graph})";
} else {
    $y1_min_graph    = 0;
    $y1_max_graph    = 1;          // Plotly still needs a valid range
    $y1_min_input_js = "''";       // empty inputs in the front-end
    $y1_max_input_js = "''";
}

if ($axis2_used) {
    $pad_y2          = max(0.5, 0.1 * ($y_max2 - $y_min2));
    $y2_min_graph    = $y_min2 - $pad_y2;
    $y2_max_graph    = 1.1 * ($y_max2 + $pad_y2);
    $y2_min_input_js = "parseInt({$y2_min_graph})";
    $y2_max_input_js = "parseInt({$y2_max_graph})";
} else {
    $y2_min_graph    = 0;
    $y2_max_graph    = 1;
    $y2_min_input_js = "''";
    $y2_max_input_js = "''";
}


// -----------------------------------------------
// Build Plotly layout configuration

// Safely encode the Y-axis titles for JS embedding. These can contain
// apostrophes ("Hauteur d'eau (cm)") or other punctuation that would
// break a hand-built single-quoted JS string literal. json_encode
// returns a fully-quoted, escape-safe JS literal we can drop directly
// into the layout template.
$text_yaxis1_js = json_encode($text_yaxis1, JSON_UNESCAPED_UNICODE);
$text_yaxis2_js = json_encode($text_yaxis2, JSON_UNESCAPED_UNICODE);

$layout_graph = "
    var layout =
    {
        xaxis:
        {
            title: {
                standoff: 5 // Distance between axis title and tick labels
            },

            rangeslider: {
                visible: true,
                thickness: 0.05,
                bgcolor: '#F2F2F2',
            },

            type: 'date',

            showgrid: true,
            gridcolor: '#ddd',
            gridwidth: 1,

            autorange: false,
            range: ['{$date_first_str}', '{$date_end_str}'],

            tickfont: { size: 12 },

            titlefont: { family: 'roboto, arial, helvetica', size: 1, bold: true, color: '#000000' },
            tickangle: 0,
            ticklen: 5,
            showline: true,
            linewidth: 1,
            automargin: true,
            fixedrange: false
        },

        yaxis:
        {
            title: {
                text: {$text_yaxis1_js},
                standoff: 15
            },

            autorange: false,
            range: [{$y1_min_graph}, {$y1_max_graph}],

            tickfont: { size: 11 },
            titlefont: { family: 'roboto, arial, helvetica', size: 14, bold: true, color: '#000000' },
            tickformat: '.1f',
            ticklen: 5,
            showline: true,
            linewidth: 1,
            automargin: true,
            fixedrange: false
        },

        yaxis2:
        {
            title: {
                text: {$text_yaxis2_js},
                standoff: 15
            },

            autorange: false,
            range: [{$y2_min_graph}, {$y2_max_graph}],

            tickfont: { size: 11 },
            titlefont: { family: 'roboto, arial, helvetica', size: 14, bold: true, color: '#000000' },
            tickformat: '.1f',
            ticklen: 5,
            showline: true,
            linewidth: 1,

            overlaying: 'y',  // Overlay on the primary Y-axis
            side: 'right',    // Position the secondary axis on the right side

            automargin: true,
            fixedrange: false
        },

        hovermode: 'x unified',
        hoverdistance: 10,
        uirevision: 'true',

        hoverlabel: { bgcolor: '#fff', font: { size: 12, color: '#000' } },
        margin: { l: 60, r: 10, t: 10, b: 10 },

        barmode: 'group',

        showlegend: true,
        legend:
        {
            x: 0,
            y: 1,
            orientation: 'h',
            font: { size: 11 },
        },

        // Gap (lacune) highlight shapes
        {$affiche_lac}
    };
";


// -----------------------------------------------
// Build the Plotly render call

$editGraph = "Plotly.newPlot('plot_0', [{$load_data}], layout, config);";


// -----------------------------------------------
// Build post-render interaction handlers:
// - Sync input fields on axis range changes (drag, rangeslider, zoom)
// - Real-time field updates during drag (plotly_relayouting)
// - Double-click reset to initial ranges

$actionGraph = "
    dateFirst.value = '" . $date_first->format('d-m-Y') . "';
    dateEnd.value   = '" . $date_end->format('d-m-Y') . "';
    yMin1.value     = {$y1_min_input_js};
    yMax1.value     = {$y1_max_input_js};
    yMin2.value     = {$y2_min_input_js};
    yMax2.value     = {$y2_max_input_js};

    var gd = document.getElementById('plot_0');
    if (gd) {

        // ---- plotly_relayout: fired after zoom/pan/rangeslider interaction ends ----
        gd.on('plotly_relayout', function(eventData)
        {
            var x1 = eventData['xaxis.range[0]'];
            var x2 = eventData['xaxis.range[1]'];

            // Support array format returned by rangeslider
            if ((x1 === undefined || x2 === undefined) && Array.isArray(eventData['xaxis.range'])) {
                x1 = eventData['xaxis.range'][0];
                x2 = eventData['xaxis.range'][1];
            }

            // X autorange reset: restore initial date boundaries
            if (eventData['xaxis.autorange'] === true) {
                x1 = '" . $date_first->format('Y-m-d') . "';
                x2 = '" . $date_end->format('Y-m-d') . "';
            }

            // Fallback: read from current layout if not found in event
            if ((x1 === undefined || x2 === undefined) && gd.layout && gd.layout.xaxis && Array.isArray(gd.layout.xaxis.range)) {
                x1 = gd.layout.xaxis.range[0];
                x2 = gd.layout.xaxis.range[1];
            }

            // Convert X values to dd-mm-yyyy for input fields
            if (x1 && typeof x1 === 'string') { dateFirst.value = x1.split(' ')[0].split('-').reverse().join('-'); }
            if (x2 && typeof x2 === 'string') { dateEnd.value   = x2.split(' ')[0].split('-').reverse().join('-'); }

            // Y1 sync (only if axis exists in layout)
            if (gd._fullLayout && gd._fullLayout.yaxis) {
                var y1 = eventData['yaxis.range[0]'];
                var y2 = eventData['yaxis.range[1]'];

                if ((y1 === undefined || y2 === undefined) && Array.isArray(eventData['yaxis.range'])) {
                    y1 = eventData['yaxis.range'][0];
                    y2 = eventData['yaxis.range'][1];
                }

                if (eventData['yaxis.autorange'] === true && gd.layout && gd.layout.yaxis && Array.isArray(gd.layout.yaxis.range)) {
                    y1 = gd.layout.yaxis.range[0];
                    y2 = gd.layout.yaxis.range[1];
                }

                if (typeof y1 !== 'undefined' && !isNaN(y1)) { yMin1.value = parseInt(y1); }
                if (typeof y2 !== 'undefined' && !isNaN(y2)) { yMax1.value = parseInt(y2); }
            }

            // Y2 sync (only if axis exists in layout)
            if (gd._fullLayout && gd._fullLayout.yaxis2) {
                var y1_2 = eventData['yaxis2.range[0]'];
                var y2_2 = eventData['yaxis2.range[1]'];

                if ((y1_2 === undefined || y2_2 === undefined) && Array.isArray(eventData['yaxis2.range'])) {
                    y1_2 = eventData['yaxis2.range'][0];
                    y2_2 = eventData['yaxis2.range'][1];
                }

                if (eventData['yaxis2.autorange'] === true && gd.layout && gd.layout.yaxis2 && Array.isArray(gd.layout.yaxis2.range)) {
                    y1_2 = gd.layout.yaxis2.range[0];
                    y2_2 = gd.layout.yaxis2.range[1];
                }

                if (typeof y1_2 !== 'undefined' && !isNaN(y1_2)) { yMin2.value = parseInt(y1_2); }
                if (typeof y2_2 !== 'undefined' && !isNaN(y2_2)) { yMax2.value = parseInt(y2_2); }
            }
        });

        // ---- plotly_relayouting: real-time field updates during drag ----
        gd.on('plotly_relayouting', function(eventData)
        {
            // X axis
            var xr = eventData['xaxis.range'] || [eventData['xaxis.range[0]'], eventData['xaxis.range[1]']];
            if (Array.isArray(xr) && xr[0] !== undefined && xr[1] !== undefined) {
                if (typeof xr[0] === 'string') { dateFirst.value = xr[0].split(' ')[0].split('-').reverse().join('-'); }
                if (typeof xr[1] === 'string') { dateEnd.value   = xr[1].split(' ')[0].split('-').reverse().join('-'); }
            }

            // Y1 axis (if present)
            if (gd._fullLayout && gd._fullLayout.yaxis) {
                var yr1 = eventData['yaxis.range'] || [eventData['yaxis.range[0]'], eventData['yaxis.range[1]']];
                if (Array.isArray(yr1) && yr1[0] !== undefined && yr1[1] !== undefined) {
                    if (!isNaN(yr1[0])) { yMin1.value = parseInt(yr1[0]); }
                    if (!isNaN(yr1[1])) { yMax1.value = parseInt(yr1[1]); }
                }
            }

            // Y2 axis (if present)
            if (gd._fullLayout && gd._fullLayout.yaxis2) {
                var yr2 = eventData['yaxis2.range'] || [eventData['yaxis2.range[0]'], eventData['yaxis2.range[1]']];
                if (Array.isArray(yr2) && yr2[0] !== undefined && yr2[1] !== undefined) {
                    if (!isNaN(yr2[0])) { yMin2.value = parseInt(yr2[0]); }
                    if (!isNaN(yr2[1])) { yMax2.value = parseInt(yr2[1]); }
                }
            }
        });

        // ---- plotly_doubleclick: reset all axes to initial ranges ----
        gd.on('plotly_doubleclick', function()
        {
            dateFirst.value = '" . $date_first->format('d-m-Y') . "';
            dateEnd.value   = '" . $date_end->format('d-m-Y') . "';
            yMin1.value     = {$y1_min_input_js};
            yMax1.value     = {$y1_max_input_js};
            yMin2.value     = {$y2_min_input_js};
            yMax2.value     = {$y2_max_input_js};

            var updates = {};

            if (gd._fullLayout && gd._fullLayout.yaxis) {
                updates['yaxis.autorange'] = false;
                updates['yaxis.range']     = [{$y1_min_graph}, {$y1_max_graph}];
            }

            if (gd._fullLayout && gd._fullLayout.yaxis2) {
                updates['yaxis2.autorange'] = false;
                updates['yaxis2.range']     = [{$y2_min_graph}, {$y2_max_graph}];
            }

            if (Object.keys(updates).length) {
                Plotly.relayout(gd, updates);
            }
        });
    }
";


// -----------------------------------------------
// Encode and return the complete graph JS as a JSON response

$responseData = [
    'js_graph'    => $data_graph . $layout_graph . $editGraph . $actionGraph,
    // Flags consumed by the front-end to dim the per-axis zoom-control
    // row when no series was bound to that axis.
    'axis1_used'  => $axis1_used,
    'axis2_used'  => $axis2_used,
];

echo json_encode($responseData);


// =============================================================================
// HELPER — Build the tooltip customdata for a single classical chronicle
// point. Same payload schema as process_graph_multi.php so both views
// render the same hover information:
//
//   [0] date in dd-mm-YYYY HH:MM:SS
//   [1] value rounded to $round_axe decimals
//   [2] quality-code line ('<br><b>Quality Code</b> : 4-B_B' or '')
//   [3] obs line          ('<br><b>...</b> : ...' or '')
//   [4] obs_user line     ('<br><b>...</b> : ...' or '')
//
// Pre-formatting empty fields as '' lets Plotly's hovertemplate
// auto-collapse the corresponding lines — hovertemplate doesn't support
// conditionals so the trick is to put the entire "<br><b>label</b> :
// value" prefix into the field itself.
//
// @param array      $row          One data row from the trace SQL query
// @param float      $valeur       Value computed by the caller (raw or
//                                 cumulated)
// @param array      $qual_array   Quality-code lookup keyed by id
// @param int|string $round_axe    Number of decimals for value formatting
// @return array                   5-element customdata payload
// =============================================================================
function buildPointCustomdata($row, $valeur, $qual_array, $round_axe)
{
    $dt = new DateTime($row['dateheure']);
    $date_formatee = $dt->format('d-m-Y H:i:s');

    // Quality field — prefix included if not empty
    $qual_init = (isset($qual_array[$row['id_codequal']])
        ? $qual_array[$row['id_codequal']]['init_qualite_data']
        : '');
    $field_qual = tep_not_null($qual_init)
        ? '<br><b>' . TEXT_GRAPH_HOVER_QUALCODE . '</b> : ' . $qual_init
        : '';

    // Obs field — wordwrap on spaces every TOOLTIP_WRAP_WIDTH chars
    $obs_value  = $row['obs'] ?? '';
    $field_obs  = tep_not_null($obs_value)
        ? '<br><b>' . TEXT_GRAPH_HOVER_CORRECTION . '</b> : ' . wordwrap($obs_value, TOOLTIP_WRAP_WIDTH, '<br>', false)
        : '';

    // User obs field — lab/tot rows don't have obs_user, hence the
    // null-coalescing fallback.
    $obs_user_value = $row['obs_user'] ?? '';
    $field_obs_user = tep_not_null($obs_user_value)
        ? '<br><b>' . TEXT_GRAPH_HOVER_CORRECTION_OBS . '</b> : ' . wordwrap($obs_user_value, TOOLTIP_WRAP_WIDTH, '<br>', false)
        : '';

    $round_safe = max(0, (int)$round_axe);

    return array(
        $date_formatee,                         // [0]
        round((float)$valeur, $round_safe),     // [1]
        $field_qual,                            // [2]
        $field_obs,                             // [3]
        $field_obs_user,                        // [4]
    );
}


// =============================================================================
// HELPER — Build a Plotly trace for an RA (manual reading) series.
//
// RA is special: the records live in TABLE_DATA_RA and the displayed
// value depends on the station's equipment type:
//   - id_eq_type 11 (Hydrometry) → height of water in cm
//     fallback chain: hydro_h_echelle_1 → hydro_h_echelle_2 → hydro_h_sonde
//   - id_eq_type  5 (Piezometry) → water-table depth in cm
//     piezo_prof_toitnappe is stored in metres, displayed in cm
//   - Other types (e.g. rainfall) → RA has no scalar value to plot,
//     we skip the trace silently.
//
// The function mutates $data_graph / $load_data (string accumulators
// that the rest of the file builds up trace by trace) and updates the
// y-axis range trackers and X-axis bounds shared with the rest of the
// loop. The trace name is built like the classic ones so it appears
// in the legend with the same '<station> - RA' convention.
//
// @param mysqli   $sql_link        DB handle
// @param string   $axe_id          'axe1' or 'axe2' — used in trace var name + legendgroup
// @param string   $yaxis           'y1' or 'y2'    — Plotly axis binding
// @param int      $idStation       Station id (numeric)
// @param string   $sql_trace       SQL query (already built by data_chron.php)
// @param string   $color_trace     Hex color picked in the front-end picker
// @param string   &$data_graph     Output: JS variable declarations for traces
// @param string   &$load_data      Output: comma-separated trace var names
// @param float    &$y_min          Output: running min for this axis
// @param float    &$y_max          Output: running max for this axis
// @param DateTime &$date_first     Output: earliest X across all traces
// @param DateTime &$date_end       Output: latest   X across all traces
// @param string   &$text_yaxis     Output: Y-axis label ("Hauteur d'eau (cm)" etc.)
// @param string   $nom_axe         Axis label sent by the front (overridden for RA)
// @param string   $unite_axe       Axis unit  sent by the front (overridden for RA)
// @param string   $round_axe       Decimals for the hover tooltip (kept as-is)
function buildRaTrace(
    $sql_link, $axe_id, $yaxis,
    $idStation, $sql_trace, $color_trace,
    &$data_graph, &$load_data,
    &$y_min, &$y_max, &$date_first, &$date_end,
    &$text_yaxis, $nom_axe, $unite_axe, $round_axe
) {
    // Resolve the station's equipment type so we know which column to read.
    $sql_station = "SELECT id_station, nom_station, station_type
                    FROM " . TABLE_STATION . "
                    WHERE id_station = " . (int)$idStation;
    $station_query = tep_db_query($sql_link, $sql_station);
    $station_tab   = tep_db_fetch_array($station_query);
    if (!$station_tab) { return; }

    $station_type = (int)$station_tab['station_type'];
    $nom_station  = affichelettres($station_tab['nom_station'] ?? '', 18);

    // Choose axis label + unit + field-extractor based on station type.
    // For unsupported types (no scalar RA value), we skip the trace.
    if ($station_type === 11) {
        $axis_nom   = "Hauteur d'eau";
        $axis_unite = "cm";
        $extract    = function ($r) {
            $v = (float)$r['hydro_h_echelle_1'];
            if (!tep_not_null($v)) { $v = (float)$r['hydro_h_echelle_2']; }
            if (!tep_not_null($v)) { $v = (float)$r['hydro_h_sonde']; }
            return $v;
        };
    } elseif ($station_type === 5) {
        $axis_nom   = "Profondeur nappe";
        $axis_unite = "cm";
        $extract    = function ($r) {
            // Stored in metres in DB, shown in cm in the UI.
            return (float)$r['piezo_prof_toitnappe'] * 100;
        };
    } else {
        // Rainfall and any other station type: no usable scalar value
        // for the RA graph. Silently skip — the user keeps the rest of
        // their selection rendered without warnings.
        return;
    }

    // Set the Y-axis label only if it hasn't been chosen by the front yet.
    // (The front sends nomAxe1/uniteAxe1 from its <select>; if the user
    // hasn't touched it, we provide a sensible default for RA-only graphs.)
    if (!tep_not_null($text_yaxis)) {
        $text_yaxis = $axis_nom . ' (' . $axis_unite . ')';
    }

    // Accumulators (arrays then implode — same pattern as the rest of the file).
    $x_arr = [];
    $y_arr = [];
    $nb    = 0;

    $data_query = tep_db_query($sql_link, $sql_trace);
    while ($r = tep_db_fetch_array($data_query)) {
        $valeur = $extract($r);
        if (!tep_not_null($valeur)) { continue; }

        $x_arr[] = "'" . $r['date_heure_ra'] . "'";
        $y_arr[] = $valeur;
        $nb++;

        // Update y-axis range trackers
        if ($valeur > $y_max) { $y_max = $valeur; }
        if ($valeur < $y_min) { $y_min = $valeur; }

        // Update x-axis bounds (the rest of the file uses DateTime for this).
        $dt = new DateTime($r['date_heure_ra']);
        if ($dt < $date_first) { $date_first = $dt; }
        if ($dt > $date_end)   { $date_end   = $dt; }
    }

    if ($nb === 0) { return; }

    $x_csv = implode(',', $x_arr);
    $y_csv = implode(',', $y_arr);

    // Build a JS-safe legend name + legendgroup.
    // The literal 'RA' used to be hard-coded here; we switch to the
    // already-translated TEXT_CHRON_RA constant so the English UI
    // shows the proper "Manual stage reading" label instead of the
    // French abbreviation.
    $name_js        = json_encode($nom_station . ' - ' . TEXT_CHRON_RA, JSON_UNESCAPED_UNICODE);
    $legendgroup_js = json_encode('tdc_' . $axe_id . '_' . $idStation . '_ra', JSON_UNESCAPED_UNICODE);

    // Trace variable name (mirrors the classic ones, e.g. trace_axe1_245_ra)
    $trace_var = 'trace_' . $axe_id . '_' . $idStation . '_ra';

    // Hovertemplate — title shows "<station> - <RA label>" in the trace color
    // (parity with the multi-graph view and with classic chronicles in
    // the combined view). json_encode keeps the JS literal safe against
    // apostrophes (e.g. "Hauteur d'eau") and other special characters.
    $hover_html =
        '<b><span style="color:' . $color_trace . '">' . $nom_station . ' - ' . TEXT_CHRON_RA . '</span></b>'
      . '<br><b>' . TEXT_GRAPH_HOVER_DATE . '</b> : %{x|%d-%m-%Y %H:%M:%S}'
      . '<br><b>' . $axis_nom . '</b> : %{y:.1f} ' . $axis_unite
      . '<extra></extra>';
    $hover = json_encode($hover_html, JSON_UNESCAPED_UNICODE);

    $data_graph .= "
        var {$trace_var} =
        {
            x: [{$x_csv}],
            y: [{$y_csv}],
            type: 'scattergl',
            mode: 'markers',
            marker: { size: 9, color: '{$color_trace}', symbol: 'x', line: { color: '{$color_trace}', width: 2 } },
            legendgroup: {$legendgroup_js},
            name: {$name_js},
            yaxis: '{$yaxis}',
            hovertemplate: {$hover},
            textposition: 'none'
        };
    ";

    $load_data .= "{$trace_var},";
}

// =============================================================================
// HELPER — Build a Plotly trace for a JGE (jaugeage / streamflow) series.
//
// JGE records live in TABLE_DATA_JGE (joined with TABLE_DATA_JGE_BRAS by
// the SQL prepared in data_chron.php). The displayed value is `depouil_q`
// in m³/s. JGE applies to hydrometry stations only (station_type 11) —
// for any other type the SQL would still run, but the field has no
// meaning, so we skip the trace silently.
//
// Structure is intentionally parallel to buildRaTrace() so both behave
// the same in the rest of the file.
// =============================================================================
function buildJgeTrace(
    $sql_link, $axe_id, $yaxis,
    $idStation, $sql_trace, $color_trace,
    &$data_graph, &$load_data,
    &$y_min, &$y_max, &$date_first, &$date_end,
    &$text_yaxis, $nom_axe, $unite_axe, $round_axe
) {
    // Resolve station info (mainly to filter out non-hydrometric stations
    // and to label the trace with the station name).
    $sql_station = "SELECT id_station, nom_station, station_type
                    FROM " . TABLE_STATION . "
                    WHERE id_station = " . (int)$idStation;
    $station_query = tep_db_query($sql_link, $sql_station);
    $station_tab   = tep_db_fetch_array($station_query);
    if (!$station_tab) { return; }

    $station_type = (int)$station_tab['station_type'];
    $nom_station  = affichelettres($station_tab['nom_station'] ?? '', 18);

    // JGE only makes sense for hydrometry stations.
    if ($station_type !== 11) { return; }

    $axis_nom   = 'Débit';
    $axis_unite = 'm³/s';

    if (!tep_not_null($text_yaxis)) {
        $text_yaxis = $axis_nom . ' (' . $axis_unite . ')';
    }

    // Accumulators
    $x_arr = [];
    $y_arr = [];
    $nb    = 0;

    $data_query = tep_db_query($sql_link, $sql_trace);
    while ($r = tep_db_fetch_array($data_query)) {
        $valeur = (float)$r['depouil_q'];
        if (!tep_not_null($valeur)) { continue; }

        // JGE rows expose the timestamp as 'datetime' (see the SQL built
        // in data_chron.php). RA uses 'date_heure_ra' — the only reason
        // these two functions don't share a body is exactly this kind
        // of schema divergence.
        $x_arr[] = "'" . $r['datetime'] . "'";
        $y_arr[] = $valeur;
        $nb++;

        if ($valeur > $y_max) { $y_max = $valeur; }
        if ($valeur < $y_min) { $y_min = $valeur; }

        $dt = new DateTime($r['datetime']);
        if ($dt < $date_first) { $date_first = $dt; }
        if ($dt > $date_end)   { $date_end   = $dt; }
    }

    if ($nb === 0) { return; }

    $x_csv = implode(',', $x_arr);
    $y_csv = implode(',', $y_arr);

    $name_js        = json_encode($nom_station . ' - ' . TEXT_CHRON_JGE, JSON_UNESCAPED_UNICODE);
    $legendgroup_js = json_encode('tdc_' . $axe_id . '_' . $idStation . '_jge', JSON_UNESCAPED_UNICODE);

    $trace_var = 'trace_' . $axe_id . '_' . $idStation . '_jge';

    // Hovertemplate — built safely via json_encode (see buildRaTrace
    // for the rationale).
    $hover_html =
        '<b><span style="color:' . $color_trace . '">' . $nom_station . ' - ' . TEXT_CHRON_JGE . '</span></b>'
      . '<br><b>' . TEXT_GRAPH_HOVER_DATE . '</b> : %{x|%d-%m-%Y %H:%M:%S}'
      . '<br><b>' . $axis_nom . '</b> : %{y:.3f} ' . $axis_unite
      . '<extra></extra>';
    $hover = json_encode($hover_html, JSON_UNESCAPED_UNICODE);

    $data_graph .= "
        var {$trace_var} =
        {
            x: [{$x_csv}],
            y: [{$y_csv}],
            type: 'scattergl',
            mode: 'markers',
            marker: { size: 12, color: '{$color_trace}', symbol: 'x', line: { color: '{$color_trace}', width: 2 } },
            legendgroup: {$legendgroup_js},
            name: {$name_js},
            yaxis: '{$yaxis}',
            hovertemplate: {$hover},
            textposition: 'none'
        };
    ";

    $load_data .= "{$trace_var},";
}
?>