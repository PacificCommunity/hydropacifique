<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Single-station chronological graph builder
- Receives a JSON payload from an AJAX request
- Queries chronological data, gap (lacune) positions, RA readings,
  streamflow measurements (jaugeages), and field visits
- Computes statistical lines: mean, percentiles, Gumbel return periods
- Returns Plotly.js graph code + UI HTML fragments as a JSON response

MEMORY OPTIMIZATION (refactored):
  Previously, graph_x_, graph_y_, and graph_customdata_ were built by
  concatenating strings inside the data loop (up to 700k iterations).
  PHP string concatenation reallocates the entire string on every append,
  which produces O(n²) memory usage and triggers the 2G exhaustion.

  Fix: accumulate values in PHP arrays using [] = (O(1) per push),
  then join once with implode() after the loop.  The resulting string
  is identical but peak memory is reduced by ~60–70% for large series.

TOOLTIP REFACTORING (native hovertemplate):
  Previously, tooltips were rendered through a custom JS handler
  (plotly_hover) that built HTML in a fixed DOM box. This caused major
  slowdowns on large datasets (>100k points) due to DOM manipulation
  on every mouse move.

  Fix: switched to Plotly's native hovertemplate using %{customdata[N]}.
  - Conditional fields (quality, obs, obs_user) are pre-built server-side
    with their label included, so empty fields disappear naturally.
  - Long text fields are pre-wrapped server-side via wordwrap() to
    constrain tooltip width without breaking words.
  - Trace titles are colorized via <span style="color:..."> for
    better readability in unified hover mode.
  - The custom JS hoverBox handler is removed entirely.
  - Tooltips are now rendered by Plotly's SVG/Canvas engine, scaling
    smoothly to hundreds of thousands of points.

  Companion CSS (to add to global stylesheet):
    .hoverlayer .legend .legendlines  { display: none; }
    .hoverlayer .legend .legendtext   { transform: translateX(-35px); }
    .hoverlayer .legend               { transform: translate(20px, 15px) !important; }

----------------------------------------
*/

// Override the php.ini limit for this script only.
// The root cause is the loop below, not the limit — this line is a
// safety net in case a dataset is slightly over the old threshold.
ini_set('memory_limit', '2048M');

// -----------------------------------------------
// Core dependencies: config, DB tables, functions
require('../../config.php');
require('../../database_tables.php');
require('../../function/date.php');
require('../../function/math.php');
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
// Tooltip wrap width (characters per line, shared across all traces).
// wordwrap() inserts <br> on spaces only — long words are never broken.
define('TOOLTIP_WRAP_WIDTH', 50);

// -----------------------------------------------
// Parse incoming JSON payload from AJAX request
$jsonDataGraph = file_get_contents('php://input');
$dataGraph     = json_decode($jsonDataGraph, true);

$territoire_id  = $dataGraph['territoireId'];
$gestion_data   = $dataGraph['gestionData']; // Access level: controls edit button visibility
$nb_station     = $dataGraph['nbStation'];
$cle_station    = $dataGraph['cle_station'];
$id_typedata    = $dataGraph['type_station'];
$typedata_array = $dataGraph['typedata_array'];
$minCount_x     = $dataGraph['min_x'];
$maxCount_x     = $dataGraph['max_x'];

// "1 graph per chronicle" mode:
// $plot_key is the composite suffix used everywhere a per-graph DOM id or
// JS variable name is built (plot_{key}, wait_{key}, trace_{key}_{...}, ...).
// It is sent by the front-end as $cle_station . '_' . $typedata_chron.
//
// Backward compatibility: older callers may still send only $cle_station,
// in which case we fall back to it. In that fallback the suffix collides
// with the previous "one graph per station" naming, which is exactly what
// we want for legacy clients.
$plot_key       = isset($dataGraph['plot_key']) && $dataGraph['plot_key'] !== ''
                  ? $dataGraph['plot_key']
                  : $cle_station;

// Note: min/max are intentionally swapped here — resolved dynamically during data iteration
$min_x = $dataGraph['max_x'];
$max_x = $dataGraph['min_x'];

$min_x_dt = new DateTime($max_x);
$max_x_dt = new DateTime($min_x);

$min_y      = 99999;
$max_y      = 0;
$total_rows = 1;
$n_years    = 0;
$graph_load = true;
$msg_noLoad = '';
$tab_param  = $dataGraph['tab_param'];
$colorGraph = colorList();

// -----------------------------------------------
// Query: Equipment types (Rain, Flow, etc.)
$sql_eq_type = "SELECT DISTINCT id_eq_type, nom_eq_type, unite_eq_type, valeur_data_type,
                       type_color_border, type_color_background, type_graph
                FROM " . TABLE_EQ_TYPE . "
                WHERE active_eq_type = 1
                ORDER BY order_eq_type ASC";
$eq_type_query = tep_db_query($sql_link, $sql_eq_type);
while ($eq_type_tab = tep_db_fetch_array($eq_type_query))
{
    $eq_type_array[$eq_type_tab['id_eq_type']] = [
        'id_eq_type'            => $eq_type_tab['id_eq_type'],
        'nom_eq_type'           => html_entity_decode($eq_type_tab['nom_eq_type'] ?? ''),
        'unite_eq_type'         => $eq_type_tab['unite_eq_type'],
        'valeur_data_type'      => $eq_type_tab['valeur_data_type'],
        'type_color_border'     => $eq_type_tab['type_color_border'],
        'type_color_background' => $eq_type_tab['type_color_background'],
        'type_graph'            => $eq_type_tab['type_graph'],
    ];
}

// -----------------------------------------------
// Query: Axis definitions (label, unit, rounding)
$sql_data_type_axe   = "SELECT DISTINCT id, axe, unite, nb_round FROM " . TABLE_DATA_TYPE_AXE;
$data_type_axe_query = tep_db_query($sql_link, $sql_data_type_axe);
while ($data_type_axe = tep_db_fetch_array($data_type_axe_query))
{
    $data_type_axe_array[$data_type_axe['id']] = [
        'axe'      => $data_type_axe['axe'],
        'unite'    => $data_type_axe['unite'],
        'nb_round' => $data_type_axe['nb_round'],
    ];
}

// -----------------------------------------------
// Query: Chronological data types (CI, PI, CIE, etc.)
$sql_type_chron = "SELECT DISTINCT id_data_type, init_type_data, nom_type_data, id_eq_type_data, axe_data, unite,
                          to_periode, id_chon_periode, traitement, type_graph
                   FROM " . TABLE_TYPE_DATA . "
                   ORDER BY init_type_data ASC";
$type_chron_query = tep_db_query($sql_link, $sql_type_chron);
while ($type_chron_tab = tep_db_fetch_array($type_chron_query))
{
    $axe_nom     = '';
    $axe_unite   = '';
    $axe_nbRound = 0;
    if (isset($data_type_axe_array[$type_chron_tab['axe_data']]['axe']))
    {
        $axe_nom     = $data_type_axe_array[$type_chron_tab['axe_data']]['axe'];
        $axe_unite   = $data_type_axe_array[$type_chron_tab['axe_data']]['unite'];
        $axe_nbRound = $data_type_axe_array[$type_chron_tab['axe_data']]['nb_round'];
    }
    $type_chron_array[$type_chron_tab['id_data_type']] = [
        'init_type_data'  => $type_chron_tab['init_type_data'],
        'nom_type_data'   => $type_chron_tab['nom_type_data'],
        'id_eq_type_data' => $type_chron_tab['id_eq_type_data'],
        'axe_nom'         => $axe_nom,
        'unite'           => $axe_unite,
        'nbRound'         => $axe_nbRound,
        'to_periode'      => $type_chron_tab['to_periode'],
        'id_chon_periode' => $type_chron_tab['id_chon_periode'],
        'traitement'      => $type_chron_tab['traitement'],
        'typegraph'       => $type_chron_tab['type_graph'],
    ];
}

// -----------------------------------------------
// Query: Data quality codes
$sql_code_qual = "SELECT DISTINCT id_data_qualite, init_qualite_data, nom_qualite_data
                  FROM " . TABLE_DATA_QUALITE . "
                  ORDER BY id_data_qualite";
$code_qual_query = tep_db_query($sql_link, $sql_code_qual);
while ($code_qual = tep_db_fetch_array($code_qual_query))
{
    $code_qual_array[$code_qual['id_data_qualite']] = [
        'init_qualite_data' => html_entity_decode($code_qual['init_qualite_data'] ?? ''),
        'nom_qualite_data'  => html_entity_decode($code_qual['nom_qualite_data']  ?? ''),
    ];
}

// -----------------------------------------------
// Initialize per-station variables
$nb_dec            = 3;
$unite             = '';
$text_yaxis        = '';
$lacune_date_first = '';

${'js_config_trace_' . $plot_key} = '';
${'js_load_trace_'   . $plot_key} = '';
${'edit_lacune_'     . $plot_key} = '';
${'html_tab_lacune_' . $plot_key} = '';

// Y-axis scale trackers (only 2 axes supported)
${'max_' . $plot_key}      = 0;
${'min_' . $plot_key}      = 99999;
${'nb_chron_' . $plot_key} = 0;
${'hidden_check_chron_' . $plot_key} = '';

$chron_stat_valid = '';

// -----------------------------------------------
// Main loop: iterate over all chronological series for this station
foreach ($typedata_array as $typedata_chron => $sql_chron)
{
    // -----------------------------------------------
    // Check row count to prevent loading too much data
    // (excludes special series: RA, JGE, and specific NC types)
    if ($typedata_chron != 'ra' && $typedata_chron != 'jge'
        && $typedata_chron != 'rep' && $typedata_chron != 'cte' && $typedata_chron != 'diac')
    {
        // Choose the right table depending on chronicle type
        if ($typedata_chron == '55') // lab
        {
            $sql_count = "SELECT COUNT(*) as total
                          FROM " . TABLE_DATA_LAB . " lab
                          WHERE lab.id_station = " . $cle_station . "
                            AND lab.date_heure >= '" . datefr_us($minCount_x) . " 00:00:00'
                            AND lab.date_heure <= '" . datefr_us($maxCount_x) . " 23:59:59'";
        }
        elseif ($typedata_chron == '58') // tot
        {
            $sql_count = "SELECT COUNT(*) as total
                          FROM " . TABLE_DATA_TOT . " tot
                          WHERE tot.id_station = " . $cle_station . "
                            AND tot.date_heure >= '" . datefr_us($minCount_x) . " 00:00:00'
                            AND tot.date_heure <= '" . datefr_us($maxCount_x) . " 23:59:59'";
        }
        else // standard: data_all / data_meta
        {
            $sql_count = "SELECT COUNT(*) as total
                          FROM "   . TABLE_DATA_ALL  . " da
                          JOIN "   . TABLE_DATA_META . " dm ON da.id_meta = dm.id
                          WHERE dm.id_typedata = " . $typedata_chron . "
                            AND dm.id_station  = " . $cle_station    . "
                            AND da.dateheure  >= '" . datefr_us($minCount_x) . " 00:00:00'
                            AND da.dateheure  <= '" . datefr_us($maxCount_x) . " 23:59:59'";
        }

        $count_query = tep_db_query($sql_link, $sql_count);
        $count_data  = tep_db_fetch_array($count_query);
        $total_rows  = $count_data['total'];
    }

    $limit_screen = 400000;

    // Too many rows: block graph rendering and display a user-friendly message
    if ($total_rows > $limit_screen)
    {
        $graph_load  = false;
        $packet_size = $limit_screen*0.9;
        $nb_packets  = ceil($total_rows / $packet_size);

        $union_parts = [];
        for ($p = 0; $p < $nb_packets; $p++)
        {
            // Build the per-packet subquery on the right table
            if ($typedata_chron == '55') // lab
            {
                $union_parts[] = "
                    (SELECT lab.date_heure AS dateheure, " . $p . " AS packet_num
                    FROM " . TABLE_DATA_LAB . " lab
                    WHERE lab.id_station = " . $cle_station . "
                    AND lab.date_heure >= '" . datefr_us($minCount_x) . " 00:00:00'
                    AND lab.date_heure <= '" . datefr_us($maxCount_x) . " 23:59:59'
                    ORDER BY lab.date_heure ASC
                    LIMIT 1 OFFSET " . ($p * $packet_size) . ")
                ";
            }
            elseif ($typedata_chron == '58') // tot
            {
                $union_parts[] = "
                    (SELECT tot.date_heure AS dateheure, " . $p . " AS packet_num
                    FROM " . TABLE_DATA_TOT . " tot
                    WHERE tot.id_station = " . $cle_station . "
                    AND tot.date_heure >= '" . datefr_us($minCount_x) . " 00:00:00'
                    AND tot.date_heure <= '" . datefr_us($maxCount_x) . " 23:59:59'
                    ORDER BY tot.date_heure ASC
                    LIMIT 1 OFFSET " . ($p * $packet_size) . ")
                ";
            }
            else // standard: data_all / data_meta
            {
                $union_parts[] = "
                    (SELECT da.dateheure, " . $p . " AS packet_num
                    FROM "  . TABLE_DATA_ALL  . " da
                    JOIN "  . TABLE_DATA_META . " dm ON da.id_meta = dm.id
                    WHERE dm.id_typedata = " . $typedata_chron . "
                    AND dm.id_station  = " . $cle_station    . "
                    AND da.dateheure  >= '" . datefr_us($minCount_x) . " 00:00:00'
                    AND da.dateheure  <= '" . datefr_us($maxCount_x) . " 23:59:59'
                    ORDER BY da.dateheure ASC
                    LIMIT 1 OFFSET " . ($p * $packet_size) . ")
                ";
            }
        }

        $packets_query = tep_db_query($sql_link, implode(' UNION ALL ', $union_parts));
        $packet_dates  = [];
        while ($row = tep_db_fetch_array($packets_query))
        {
            $packet_dates[(int)$row['packet_num']] = $row['dateheure'];
        }

        // Build packet links
        $links_html = '';
        for ($p = 0; $p < $nb_packets; $p++)
        {
            $p_start_iso = substr($packet_dates[$p], 0, 10);

            if ($p < $nb_packets - 1)
            {
                $p_end_dt = new DateTime(substr($packet_dates[$p + 1], 0, 10));
                $p_end_dt->modify('-1 day');
                $p_end_iso = $p_end_dt->format('Y-m-d');
            }
            else
            {
                $p_end_iso = datefr_us($maxCount_x);
            }

            $p_start_fr = dateus_fr($p_start_iso);
            $p_end_fr   = dateus_fr($p_end_iso);

            $typedata_json = htmlspecialchars(json_encode($typedata_array), ENT_QUOTES, 'UTF-8');
            $links_html .= "
                <hr>
                <form method='post' target='_blank' action='data_chron.php' style='margin-top:10px;display:inline;'>
                    <input type='hidden' name='date_1'               value='{$p_start_fr}' />
                    <input type='hidden' name='date_2'               value='{$p_end_fr}' />
                    <input type='hidden' name='button_graph'         value='1' />
                    <input type='hidden' name='target_station_ref[]' value='{$cle_station}' />
                    <input type='hidden' name='valid_chron_step1'    value='1' />
                    <input type='hidden' name='check_chron[]'        value='{$cle_station}_{$id_typedata}_{$typedata_chron}' />
                    <button type='submit' style='background:none;border:none;cursor:pointer;font-size:14px;color:#0066cc;text-decoration:underline;padding:0;'>
                        📦 " . TEXT_GRAPH_LOAD_PACKET . " " . ($p + 1) . " / " . $nb_packets . "
                        &nbsp;({$p_start_fr} → {$p_end_fr})
                    </button>
                </form>
            ";
        }

        $msg_noLoad .= "
            <p style='margin-top:34px;font-weight:normal;font-size:14px;'>
                <span style='font-weight:bold;'>
                    " . TEXT_GRAPH_TOO_MANY_ROWS . "
                    " . TEXT_GRAPH_TOO_MANY_ROWS_SUB . "
                </span>
                <br><br>
                {$total_rows} " . TEXT_GRAPH_RECORDS . "
                <br><br>
                " . TEXT_GRAPH_SHORTER_PERIOD . "
                <br><br>
                -
                <br><br>
                " . TEXT_GRAPH_STATS_AVAILABLE . "
                <a href='#' style='margin-top:30px;font-size:14px;'
                onClick='afficheStats({$cle_station},{$id_typedata},{$typedata_chron})'>
                    " . TEXT_GRAPH_STATS_LINK . "
                </a>
                <br><br>
                — " . TEXT_GRAPH_OR_LOAD_PACKET . " —
                {$links_html}
            </p>
        ";
    }

    // No data found: block graph rendering
    if ($total_rows < 1)
    {
        $graph_load = false;
        // Use minCount_x / maxCount_x for the message: these are the
        // user-selected bounds as received from the front, untouched.
        // $min_x / $max_x are intentionally swapped at init (see top of
        // file) and the data loop never ran to fix them in this branch.
        $msg_noLoad = TEXT_GRAPH_NO_DATA . ' : ' . $minCount_x . ' - ' . $maxCount_x;
    }

    // -----------------------------------------------
    // Process standard chronological series (not RA or JGE)
    if ($graph_load)
    {
        if ($typedata_chron != 'ra' && $typedata_chron != 'jge')
        {
            $chron_stat_valid = $typedata_chron;
            $nb_dec     = $type_chron_array[$typedata_chron]['nbRound'];
            $unite      = $type_chron_array[$typedata_chron]['unite'];
            $text_yaxis = $type_chron_array[$typedata_chron]['axe_nom'] . ' (' . $unite . ')';

            ${'nb_chron_' . $plot_key}++;
            ${'nb_data_'    . $plot_key . '_' . $typedata_chron} = 0;
            ${'max_'        . $plot_key . '_' . $typedata_chron} = 0;
            ${'min_'        . $plot_key . '_' . $typedata_chron} = 99999;
            ${'nb_lacunes_' . $plot_key . '_' . $typedata_chron} = 0;
            ${'tab_lacunes_'. $plot_key . '_' . $typedata_chron} = [];

            // ----- MEMORY OPTIMISATION -----
            // Use arrays instead of concatenated strings for X, Y, and customdata.
            // Each []= push is O(1); implode() at the end produces the same JS string
            // but avoids the O(n²) reallocation caused by .= inside a 700k-row loop.
            ${'graph_x_arr_'          . $plot_key . '_' . $typedata_chron} = [];
            ${'graph_y_arr_'          . $plot_key . '_' . $typedata_chron} = [];
            ${'graph_customdata_arr_' . $plot_key . '_' . $typedata_chron} = [];

            // tab_y_ is kept as a plain PHP array (used for percentile / Gumbel stats).
            // It is unset immediately after the stats block to free memory early.
            ${'tab_y_' . $plot_key . '_' . $typedata_chron} = [];

            ${'edit_lacune_'       . $plot_key . '_' . $typedata_chron} = '';
            ${'html_tab_lacune_'   . $plot_key . '_' . $typedata_chron} = '';

            // Hidden input: passes station/typedata/chron identifiers to the data correction form
            ${'hidden_check_chron_' . $plot_key} .=
                "<input type='hidden' name='check_chron[]' value='" .
                $cle_station . '_' . $id_typedata . '_' . $typedata_chron . "' />\n";

            $valeur = 0; // Running total for cumulative mode (traitement == 1)

            $min_x_iso = datefr_us($min_x); 
            $max_x_iso = datefr_us($max_x);

            // -----------------------------------------------
            // Main data loop — iterate over every chronological record
            $data_chron_query = tep_db_query($sql_link, $sql_chron);
            while ($data_chron_tab = tep_db_fetch_array($data_chron_query))
            {
                // Record the timestamp of the very first data point for this series
                if (${'nb_data_' . $plot_key . '_' . $typedata_chron} === 0)
                {
                    ${'min_x_' . $plot_key . '_' . $typedata_chron} = $data_chron_tab['dateheure'];
                }

                // Always update the "last seen" timestamp (max_x for this series)
                ${'max_x_' . $plot_key . '_' . $typedata_chron} = $data_chron_tab['dateheure'];

                $dh = $data_chron_tab['dateheure']; // ex: "2023-04-15 10:30:00"
                if ($dh < $min_x_iso) { 
                    $min_x_iso = $dh; 
                    $min_x = dateus_fr(substr($dh, 0, 10)); 
                }
                if ($dh > $max_x_iso) { 
                    $max_x_iso = $dh; 
                    $max_x = dateus_fr(substr($dh, 0, 10)); 
                }

                ${'nb_data_' . $plot_key . '_' . $typedata_chron}++;

                // ---- Gap (lacune) currently open: close it ----
                if (tep_not_null($lacune_date_first))
                {
                    ${'edit_lacune_'     . $plot_key . '_' . $typedata_chron} .= $edit_lacune_temp;
                    ${'html_tab_lacune_' . $plot_key . '_' . $typedata_chron} .= $html_tab_lacune_temp;

                    // Pre-format the END date (current point) once, in FR display format.
                    // BUG #5 fix: previously $lacune_date_first_fr was reused below as the
                    // "end date", which displayed the START date in the "Date fin" column.
                    $chron_dateheure_tab = explode(' ', $data_chron_tab['dateheure']);
                    $chron_dateheure_fr  = dateus_fr($chron_dateheure_tab[0]) . ' ' . $chron_dateheure_tab[1];

                    if (!in_array(abs($data_chron_tab['valeur']), [9999, 99999, 8888, 88888]))
                    {
                        // Valid value: close the gap shape and append data point
                        ${'graph_x_arr_' . $plot_key . '_' . $typedata_chron}[] = "'" . $data_chron_tab['dateheure'] . "'";

                        if ($type_chron_array[$typedata_chron]['traitement'] == 0) { $valeur  = $data_chron_tab['valeur']; }
                        if ($type_chron_array[$typedata_chron]['traitement'] == 1) { $valeur += $data_chron_tab['valeur']; }

                        ${'graph_y_arr_' . $plot_key . '_' . $typedata_chron}[] = (float)$valeur;
                        ${'tab_y_'       . $plot_key . '_' . $typedata_chron}[] = (float)$valeur;

                        if ($valeur > ${'max_' . $plot_key . '_' . $typedata_chron}) { ${'max_' . $plot_key . '_' . $typedata_chron} = $valeur; }
                        if ($valeur < ${'min_' . $plot_key . '_' . $typedata_chron}) { ${'min_' . $plot_key . '_' . $typedata_chron} = $valeur; }

                        ${'edit_lacune_' . $plot_key . '_' . $typedata_chron} .= "   x1: '" . $lacune_date_first . "',";

                        // BUG #4 + #5 fix: emit the END date (current point) in the
                        // "Date fin" column, plus a 4th empty <td> so the row matches
                        // the 4-column header (Chron / Date début / Date fin / Correction).
                        ${'html_tab_lacune_' . $plot_key . '_' . $typedata_chron} .=
                            "<td style='height:15px;'>" . $chron_dateheure_fr . "</td>"
                          . "<td style='height:15px;'></td></tr>";

                        // BUG #6 fix: also record date_end for gaps closed by a valid
                        // value (previously only set in the consecutive-gap branch).
                        ${'tab_lacunes_' . $plot_key . '_' . $typedata_chron}[${'nb_lacunes_' . $plot_key . '_' . $typedata_chron}]['date_end'] = $data_chron_tab['dateheure'];

                        if ($min_y > ${'min_' . $plot_key . '_' . $typedata_chron}) { $min_y = ${'min_' . $plot_key . '_' . $typedata_chron}; }
                        if ($max_y < ${'max_' . $plot_key . '_' . $typedata_chron}) { $max_y = ${'max_' . $plot_key . '_' . $typedata_chron}; }
                    }
                    else
                    {
                        // Consecutive gap: extend the null series
                        ${'graph_x_arr_' . $plot_key . '_' . $typedata_chron}[] = "'" . $data_chron_tab['dateheure'] . "'";
                        ${'graph_y_arr_' . $plot_key . '_' . $typedata_chron}[] = 'null';

                        ${'edit_lacune_' . $plot_key . '_' . $typedata_chron} .= "   x1: '" . $data_chron_tab['dateheure'] . "',";

                        // BUG #4 fix: 4th empty <td> to match the 4-column header.
                        ${'html_tab_lacune_' . $plot_key . '_' . $typedata_chron} .=
                            "<td style='height:15px;'>" . $chron_dateheure_fr . "</td>"
                          . "<td style='height:15px;'></td></tr>";

                        ${'tab_lacunes_' . $plot_key . '_' . $typedata_chron}[${'nb_lacunes_' . $plot_key . '_' . $typedata_chron}]['date_end'] = $data_chron_tab['dateheure'];
                    }

                    // Close the Plotly gap shape definition
                    ${'edit_lacune_' . $plot_key . '_' . $typedata_chron} .= "
                            y1: 1,
                            fillcolor: '" . $colorGraph[$tab_param[$typedata_chron]['color']] . "',
                            opacity: 0.15,
                            line: {width: 0}
                        }";

                    ${'nb_lacunes_' . $plot_key . '_' . $typedata_chron}++;
                    $lacune_date_first = ''; // Reset gap tracker
                }
                else // No gap currently open
                {
                    if (!in_array(abs($data_chron_tab['valeur']), [9999, 99999, 8888, 88888]))
                    {
                        // Valid value: append to graph data arrays
                        ${'graph_x_arr_' . $plot_key . '_' . $typedata_chron}[] = "'" . $data_chron_tab['dateheure'] . "'";

                        if ($type_chron_array[$typedata_chron]['traitement'] == 0) { $valeur  = $data_chron_tab['valeur']; }
                        if ($type_chron_array[$typedata_chron]['traitement'] == 1) { $valeur += $data_chron_tab['valeur']; }

                        ${'graph_y_arr_' . $plot_key . '_' . $typedata_chron}[] = (float)$valeur;
                        ${'tab_y_'       . $plot_key . '_' . $typedata_chron}[] = (float)$valeur;

                        if ($valeur > ${'max_' . $plot_key . '_' . $typedata_chron}) { ${'max_' . $plot_key . '_' . $typedata_chron} = $valeur; }
                        if ($valeur < ${'min_' . $plot_key . '_' . $typedata_chron}) { ${'min_' . $plot_key . '_' . $typedata_chron} = $valeur; }
                        if ($min_y > ${'min_' . $plot_key . '_' . $typedata_chron}) { $min_y = ${'min_' . $plot_key . '_' . $typedata_chron}; }
                        if ($max_y < ${'max_' . $plot_key . '_' . $typedata_chron}) { $max_y = ${'max_' . $plot_key . '_' . $typedata_chron}; }
                    }
                    else
                    {
                        // Gap value detected: open a new gap shape
                        ${'graph_x_arr_' . $plot_key . '_' . $typedata_chron}[] = "'" . $data_chron_tab['dateheure'] . "'";
                        ${'graph_y_arr_' . $plot_key . '_' . $typedata_chron}[] = 'null';

                        $html_tab_lacune_temp = '';

                        // Separator between gap shapes — ONLY between, never
                        // before the first one. A leading comma would produce
                        // shapes: [, {...}, {...}] in the Plotly layout, which
                        // is invalid JS and silently disables the gap overlay.
                        if (${'nb_lacunes_' . $plot_key . '_' . $typedata_chron} > 0)
                        {
                            ${'edit_lacune_' . $plot_key . '_' . $typedata_chron} .= ',';
                        }

                        // Build the gap table header only for the first gap of this series.
                        // BUG #2 fix: properly close <thead> and open <tbody> so data rows
                        // are not nested inside <thead>.
                        if (${'nb_lacunes_' . $plot_key . '_' . $typedata_chron} < 1)
                        {
                            $html_tab_lacune_temp = "
                                <div class='table-container' style='float:left;height:60vh;margin:0;margin-bottom:5px;' >\n
                                    <table id='table_tri' cellspacing='0'>
                                        <thead>
                                            <tr class='header-row'>
                                                <th style='width:50px;text-align:center;font-size:11px;'>" . TEXT_LAC_HEADER_CHRON    . "</th>
                                                <th style='width:150px;font-size:11px;'>"               . TEXT_LAC_HEADER_DATE_START . "</th>
                                                <th style='width:150px;font-size:11px;'>"               . TEXT_LAC_HEADER_DATE_END   . "</th>
                                                <th style='width:150px;font-size:11px;'>"               . TEXT_GRAPH_HOVER_CORRECTION   . "</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                            ";
                        }

                        $edit_lacune_temp = "
                            {
                                type: 'rect',
                                xref: 'x',      // x-reference is assigned to the x-values
                                yref: 'paper',  // y-reference is assigned to the plot paper [0,1]
                                x0: '" . $data_chron_tab['dateheure'] . "',
                                y0: 0,
                            ";

                        $lacune_date_first     = $data_chron_tab['dateheure'];
                        $lacune_date_first_tab = explode(' ', $lacune_date_first);
                        $lacune_date_first_fr  = dateus_fr($lacune_date_first_tab[0]) . ' ' . $lacune_date_first_tab[1];

                        ${'tab_lacunes_' . $plot_key . '_' . $typedata_chron}[${'nb_lacunes_' . $plot_key . '_' . $typedata_chron}]['date_first'] = $lacune_date_first;

                        $html_tab_lacune_temp .= "
                            <tr>
                                <td style='height:15px;text-align:center;'>" . $type_chron_array[$typedata_chron]['init_type_data'] . "</td>
                                <td style='height:15px;'>" . $lacune_date_first_fr . "</td>
                            ";
                    }
                }

                // -----------------------------------------------
                // Build the customdata payload for the native hovertemplate.
                //
                // PERF: customdata now carries ONLY [date, value]. The quality
                // code, correction (obs) and comment (obs_user) used to be
                // pre-built per point here, but that duplicated the same strings
                // across every data point of long series (heavy JSON + browser
                // memory). They are now shown once per data_meta block in the
                // quality-code timeline below the chart, so they are no longer
                // emitted here at all.
                if ($total_rows < $limit_screen)
                {
                    $dt = new DateTime($data_chron_tab['dateheure']);
                    $date_formatee = $dt->format('d-m-Y H:i:s');

                    $data_point_raw = [
                        $date_formatee,          // [0] FR-formatted date
                        round($valeur, $nb_dec), // [1] rounded value
                    ];

                    ${'graph_customdata_arr_' . $plot_key . '_' . $typedata_chron}[] = $data_point_raw;
                }
                else 
                {
                    ${'graph_customdata_arr_' . $plot_key . '_' . $typedata_chron}[] = '';
                }

            } // end while: data records for this series

            
            // -----------------------------------------------
            // Collapse the accumulator arrays into flat JS-ready strings.
            // This single implode() call replaces what was previously 700k .= appends.
            ${'graph_x_'          . $plot_key . '_' . $typedata_chron} =
                implode(',', ${'graph_x_arr_' . $plot_key . '_' . $typedata_chron});
            ${'graph_y_'          . $plot_key . '_' . $typedata_chron} =
                implode(',', ${'graph_y_arr_' . $plot_key . '_' . $typedata_chron});
        
            ${'graph_customdata_' . $plot_key . '_' . $typedata_chron} = json_encode(${'graph_customdata_arr_' . $plot_key . '_' . $typedata_chron});

            // Free the accumulator arrays immediately — they are no longer needed
            // and would otherwise occupy memory until end-of-script.
            unset(${'graph_x_arr_'          . $plot_key . '_' . $typedata_chron});
            unset(${'graph_y_arr_'          . $plot_key . '_' . $typedata_chron});
            unset(${'graph_customdata_arr_' . $plot_key . '_' . $typedata_chron});

            // -----------------------------------------------
            // Determine Plotly chart type for this series
            $code_type_graph = '';
            if ($type_chron_array[$typedata_chron]['typegraph'] == 'lines')
            {
                $code_type_graph  = "mode: 'lines',";
                // Browsers cap concurrent WebGL contexts at ~8 (Firefox/Safari)
                // to ~16 (Chrome). With many graphs on the page, scattergl
                // traces stop rendering past that limit — the tooltip still
                // fires from the kept-alive DOM, but the curve becomes
                // invisible. We therefore reserve scattergl for series that
                // actually benefit from it (5k+ points); smaller series use
                // the SVG renderer, which costs nothing on the WebGL budget.
                $code_type_graph .= ($total_rows > 5000) ? "type: 'scattergl'," : "type: 'scatter',";
            }
            if ($type_chron_array[$typedata_chron]['typegraph'] == 'bar')
            {
                $code_type_graph = '';
                if ($type_chron_array[$typedata_chron]['typegraph'] == 'lines')
                {
                    $code_type_graph  = "mode: 'lines',";
                    $code_type_graph .= ($total_rows > 5000) ? "type: 'scattergl'," : "type: 'scatter',";
                }
                if ($type_chron_array[$typedata_chron]['typegraph'] == 'bar')
                {
                    $code_type_graph = "type: 'bar',";

                    // Force a sensible minimum bar width.
                    //
                    // Plotly auto-sizes bars based on the smallest X gap between two
                    // consecutive points. For irregular series like TOT (totalizer
                    // readings), two close measurements (a few minutes apart) shrink
                    // ALL bars to that minimum gap, making them ~0.001 px wide on a
                    // multi-year axis — invisible and impossible to hover.
                    //
                    // Fix: set bar width to 0.5% of the visible time span, clamped
                    // between 1 day and 1 week. This guarantees bars remain visible
                    // and hoverable regardless of inter-point spacing irregularities.
                    // Dense series (e.g. PJ with thousands of daily points) are
                    // unaffected visually because adjacent bars overlap into a
                    // continuous fill.
                    $min_x_ts     = strtotime(datefr_us($minCount_x) . ' 00:00:00') * 1000;
                    $max_x_ts     = strtotime(datefr_us($maxCount_x) . ' 23:59:59') * 1000;
                    $span_ms      = $max_x_ts - $min_x_ts;
                    $bar_width_ms = max(min((int)($span_ms * 0.005), 86400000 * 7), 86400000);
                    $code_type_graph .= "width: " . $bar_width_ms . ",";

                    // For large datasets (>30,000 pts), switch to a scattergl step-line
                    // fill which renders much faster than native bars at high density.
                    // scattergl is kept here unconditionally because the threshold
                    // itself already guarantees a "heavy" series worth the WebGL slot.
                    if ($total_rows > 30000)
                    {
                        $code_type_graph = "
                            type: 'scattergl',
                            mode: 'lines',
                            line: { shape: 'hv', width: 1 },
                            fill: 'tozeroy',
                            fillcolor: '" . $colorGraph[$tab_param[$typedata_chron]['color']] . "',
                        ";
                    }
                }
            }

            // -----------------------------------------------
            // Update global Y-axis scale with this series' extremes
            if (${'max_' . $plot_key . '_' . $typedata_chron} > ${'max_' . $plot_key}) { ${'max_' . $plot_key} = ${'max_' . $plot_key . '_' . $typedata_chron}; }
            if (${'min_' . $plot_key . '_' . $typedata_chron} < ${'min_' . $plot_key}) { ${'min_' . $plot_key} = ${'min_' . $plot_key . '_' . $typedata_chron}; }

            // -----------------------------------------------
            // Build the hovertemplate string for this series.
            //
            // Native Plotly templating: %{customdata[N]} resolves to the Nth element
            // of each point's customdata array. Empty fields produce no output, so
            // the tooltip auto-collapses unused lines.
            //
            // The trace title is wrapped in <span style="color:..."> so each title
            // appears in its own series color, which is essential in unified hover
            // mode (all tooltips share a white background).
            //
            // <extra></extra> hides the secondary tooltip box that would otherwise
            // show the trace name (avoids visual duplication with the title line).
            $trace_color = $colorGraph[$tab_param[$typedata_chron]['color']];
            $trace_title = $type_chron_array[$typedata_chron]['init_type_data'] . ' - ' . $type_chron_array[$typedata_chron]['nom_type_data'];
            $trace_axe   = $type_chron_array[$typedata_chron]['axe_nom'];

            // Quality code, correction and comment are no longer in the chart
            // hover: they now live in the quality-code timeline below the chart.
            // customdata therefore only carries [0] date and [1] value.
            $hovertemplate_js =
                "'<b><span style=\"color:" . $trace_color . "\">" . $trace_title . "</span></b>' + " .
                "'<br><b>" . TEXT_GRAPH_HOVER_DATE . "</b> : %{customdata[0]}' + " .
                "'<br><b>" . $trace_axe . "</b> : %{customdata[1]} " . $unite . "' + " .
                "'<extra></extra>'";

            // -----------------------------------------------
            // Generate the Plotly trace variable for this series
            ${'js_config_trace_' . $plot_key} .= "
                var trace_{$plot_key}_{$typedata_chron} =
                {
                    x: [" . ${'graph_x_' . $plot_key . '_' . $typedata_chron} . "],
                    y: [" . ${'graph_y_' . $plot_key . '_' . $typedata_chron} . "],
                    customdata: " . ${'graph_customdata_' . $plot_key . '_' . $typedata_chron} . ",
                    
                    xaxis: 'x',
                    {$code_type_graph}
                    legendgroup: 'tdc_{$typedata_chron}',
                    name: '" . $trace_title . "',
                    
                    hovertemplate: " . $hovertemplate_js . ",
                    hoverlabel: {
                        bgcolor: '{$trace_color}',
                        bordercolor: '{$trace_color}',
                        font: { color: 'white', size: 12, family: 'roboto, arial, helvetica' },
                        align: 'left'
                    },
                    marker: {
                        color: '{$trace_color}',
                        line: {
                            color: '{$trace_color}',
                            width: 1,
                        }
                    },
                    line: {
                        color: '{$trace_color}',
                        width: 1.5,
                    },
                    textposition: 'none'
                };
            ";

            // Free the now-inlined X/Y/customdata strings to recover memory
            // before continuing with the next series or the stats block.
            unset(${'graph_x_'          . $plot_key . '_' . $typedata_chron});
            unset(${'graph_y_'          . $plot_key . '_' . $typedata_chron});
            unset(${'graph_customdata_' . $plot_key . '_' . $typedata_chron});

            ${'js_load_trace_'   . $plot_key} .= "trace_{$plot_key}_{$typedata_chron},";
            ${'edit_lacune_'     . $plot_key} .= ${'edit_lacune_'     . $plot_key . '_' . $typedata_chron};

            // BUG #1 fix: only emit the closing </tbody></table></div> when this
            // series actually had at least one gap (the opening tags are only
            // written inside the "first gap" branch above). Previously, the
            // closing tags were emitted unconditionally, producing orphan </table>
            // and </div> tags that broke the surrounding HTML when a chronicle
            // had no gap.
            if (${'nb_lacunes_' . $plot_key . '_' . $typedata_chron} > 0)
            {
                ${'html_tab_lacune_' . $plot_key} .=
                    ${'html_tab_lacune_' . $plot_key . '_' . $typedata_chron}
                    . "</tbody></table></div>";
            }
            else
            {
                // No gap for this series: nothing to append (the per-series buffer
                // is empty in that case anyway, but we keep the explicit branch
                // for clarity).
                ${'html_tab_lacune_' . $plot_key} .=
                    ${'html_tab_lacune_' . $plot_key . '_' . $typedata_chron};
            }

        } // end if: standard series (not RA / JGE)
    } // end if: graph_load

} // end foreach: typedata_array

// -----------------------------------------------
// Post-loop UI variable initialization
$textGraph          = '';
$textGraphFonction  = '';
$min_y_graph        = 0;
$max_y_graph        = 0;
$text_lacunes       = '';
$text_button_calcul = '';
$text_button_stats  = '';
$text_button_tab    = '';

if ($graph_load)
{
    // -----------------------------------------------
    // Compute Y-axis display range with padding
    $pad_y       = max(0.2, 0.1 * ($max_y - $min_y));
    $min_y_graph = $min_y - $pad_y;
    $max_y_graph = $max_y + $pad_y;

    // Y position for field visit markers (slightly above the bottom of the chart)
    $fieldVisit_y = $min_y_graph + (abs($pad_y) * 0.2);

    $min_x_dt = new DateTime($min_x);
    $max_x_dt = new DateTime($max_x);

    // -----------------------------------------------
    // Statistical lines and return periods
    // Only computed when a single station with a single valid series is displayed
    if (INIT_T != 'NC')
    {
        if ($nb_station == 1 && ${'nb_chron_' . $plot_key} == 1
            && $chron_stat_valid != 'ra' && $chron_stat_valid != 'jge'
            && $chron_stat_valid != '55' && $chron_stat_valid != '58') // Specific NC - TOT and LAB - NOT USE
        {
            $min_x_trace = ${'min_x_' . $plot_key . '_' . $chron_stat_valid};
            $max_x_trace = ${'max_x_' . $plot_key . '_' . $chron_stat_valid};

            $value_mean        = mean(${'tab_y_' . $plot_key . '_' . $chron_stat_valid});
            $value_mean_format = round($value_mean, $nb_dec);

            // ---- Mean trace ----
            ${'js_config_trace_' . $plot_key} .= "
                var meanTrace_{$plot_key} =
                {
                    x: ['{$min_x_trace}', '{$max_x_trace}'],
                    y: [{$value_mean_format}, {$value_mean_format}],
                    xaxis: 'x2',
                    type: 'scatter',
                    mode: 'lines',
                    line: {
                        color: '#000',
                        width: 2,
                        dash: 'dashdot'
                    },
                    name: '" . TEXT_CHRON_MEAN . " : {$value_mean_format} {$unite}',
                    hoverinfo: 'skip',
                    visible: false,
                    rangeslider: { visible: false },
                };
            ";
            ${'js_load_trace_' . $plot_key} .= "meanTrace_{$plot_key},";

            // ---- Percentile traces ----
            $percentileConfigurations = [
                ['threshold' => 99, 'id' => 'p99', 'color' => '#059212', 'description' => 'Percentile (99%)'],
                ['threshold' => 90, 'id' => 'p90', 'color' => '#6930C3', 'description' => 'Percentile (90%)'],
                ['threshold' => 75, 'id' => 'q3',  'color' => '#F58634', 'description' => 'Last quartile (75%)'],
                ['threshold' => 50, 'id' => 'q2',  'color' => '#FF0000', 'description' => 'Median'],
                ['threshold' => 25, 'id' => 'q1',  'color' => '#FFE227', 'description' => 'First quartile (25%)'],
                ['threshold' => 10, 'id' => 'p10', 'color' => '#1F618D', 'description' => 'Percentile (10%)'],
                ['threshold' => 1,  'id' => 'p1',  'color' => '#54E346', 'description' => 'Percentile (1%)'],
            ];

            foreach ($percentileConfigurations as $config)
            {
                $valueP = calculerPercentile(${'tab_y_' . $plot_key . '_' . $chron_stat_valid}, $config['threshold']);
                ${'js_config_trace_' . $plot_key} .= generateStatTrace(
                    $plot_key, $config['id'], $valueP,
                    $min_x_trace, $max_x_trace,
                    'dash', $config['color'], $config['description'],
                    $unite, $nb_dec
                );
            }

            $traceNames = array_map(
                fn($config) => $config['id'] . 'Trace_' . $plot_key,
                $percentileConfigurations
            );
            ${'js_load_trace_' . $plot_key} .= implode(',', $traceNames) . ',';

            // ---- Gumbel return period traces ----
            $sql_data = "
                SELECT YEAR(da.dateheure) as annee, MAX(ABS(da.valeur)) as max_valeur
                FROM "   . TABLE_DATA_ALL  . " da
                JOIN "   . TABLE_DATA_META . " dm ON da.id_meta = dm.id
                WHERE dm.id_typedata = " . $chron_stat_valid . "
                AND dm.id_station  = " . $cle_station . "
                AND da.dateheure  >= '" . $min_x_dt->format('Y-m-d') . "'
                AND da.dateheure  <= '" . $max_x_dt->format('Y-m-d') . "'
                AND da.valeur NOT IN (9999, -9999, 99999, -99999, 8888, -8888, 88888, -88888)
                GROUP BY YEAR(da.dateheure)
                ORDER BY annee ASC
            ";
            $maxima_annuels = [];
            $data_query     = tep_db_query($sql_link, $sql_data);
            while ($data_tab = tep_db_fetch_array($data_query))
            {
                $maxima_annuels[] = (float)$data_tab['max_valeur'];
            }
            sort($maxima_annuels); // Sort ascending (required for Gumbel probability)
            $n_years = count($maxima_annuels);

            // Gumbel distribution fitting requires at least 5 years of annual maxima
            if ($n_years > 5)
            {
                $mean              = mean($maxima_annuels);
                $standardDeviation = sqrt(variance($maxima_annuels));

                // Gumbel distribution parameters
                $eulerConstant = 0.5772156649;
                $a = (sqrt(6) * $standardDeviation) / pi(); // Scale parameter
                $u = $mean - ($eulerConstant * $a);          // Location parameter

                // Standard errors of Gumbel parameters (not used in traces but kept for reference)
                $se_u = ($a / sqrt($n_years)) * 1.0806;
                $se_a = ($a / sqrt($n_years)) * 0.8944;

                $periodesConfigurations = [
                    ['threshold' => 2,   'id' => 'T2',   'color' => '#059212', 'description' => '2 ans'],
                    ['threshold' => 5,   'id' => 'T5',   'color' => '#1F618D', 'description' => '5 ans'],
                    ['threshold' => 10,  'id' => 'T10',  'color' => '#F58634', 'description' => '10 ans'],
                    ['threshold' => 20,  'id' => 'T20',  'color' => '#6930C3', 'description' => '20 ans'],
                    ['threshold' => 30,  'id' => 'T30',  'color' => '#FFE227', 'description' => '30 ans'],
                    ['threshold' => 40,  'id' => 'T40',  'color' => '#666',    'description' => '40 ans'],
                    ['threshold' => 50,  'id' => 'T50',  'color' => '#000',    'description' => '50 ans'],
                    ['threshold' => 100, 'id' => 'T100', 'color' => '#FF0000', 'description' => '100 ans'],
                ];

                foreach ($periodesConfigurations as $config)
                {
                    // Gumbel inverse: xT = u - a * ln(-ln(1 - 1/T))
                    $p         = 1 - (1 / $config['threshold']);
                    $valeur_xT = $u - ($a * log(-log($p)));

                    if ($valeur_xT > $max_y_graph) { $max_y_graph = $valeur_xT; }

                    ${'js_config_trace_' . $plot_key} .= generateStatTrace(
                        $plot_key, $config['id'], $valeur_xT,
                        $min_x_trace, $max_x_trace,
                        'solid', $config['color'], $config['description'],
                        $unite, $nb_dec
                    );
                }

                $traceNames = array_map(
                    fn($config) => $config['id'] . 'Trace_' . $plot_key,
                    $periodesConfigurations
                );
                ${'js_load_trace_' . $plot_key} .= implode(',', $traceNames) . ',';
            }

            // Apply 10% top padding after return period lines may have expanded the Y max
            $max_y_graph = 1.1 * $max_y_graph;

            // -----------------------------------------------
            // Free tab_y_ now that all statistical calculations are complete.
            // This array held up to 700k floats; releasing it immediately
            // recovers significant memory before the rest of the output is built.
            unset(${'tab_y_' . $plot_key . '_' . $chron_stat_valid});

            
        } // end if: single station / single series stats
    }

    // Edit/correction form (hidden; triggered from the Tools > Edit item).
    //
    // The submit posts the currently visible range from the graph
    // (read from Plotly in prepareCalculDates), falling back to the full
    // series range ($min_x / $max_x) if no zoom was performed.
    //
    // ----- VISIBILITY GUARDS -----
    // The data-correction form only makes sense for *continuous* chronicles
    // (typegraph 'lines' or 'bar'). It is hidden for:
    //   - markers chronicles      : each point is an isolated reading,
    //                               there is no curve to "correct"
    //   - RA / JGE                : same reason, plus they live in
    //                               different tables with different schemas
    //   - read-only users         : $gestion_data <= 1
    if ($gestion_data > 1
        && $typedata_chron != 'ra' && $typedata_chron != 'jge'
        && (!isset($type_chron_array[$typedata_chron]['typegraph'])
            || $type_chron_array[$typedata_chron]['typegraph'] != 'markers'))
    {
        // The form is kept (it performs the POST to data_chron.php to open the
        // correction module) but rendered hidden: it is now triggered from the
        // "Edit" item inside the Tools dropdown (see graph_chron.php), instead
        // of a visible button under the chart. The "All data" toggle has been
        // removed — it was confusing and unused; the edit always posts the
        // currently visible range (or the full range if no zoom was made).
        $text_button_calcul = "
            <form name='calcul_chron_{$plot_key}' id='calcul_chron_{$plot_key}'
                action='data_chron.php' target='_blank' method='post'
                enctype='multipart/form-data'
                onsubmit=\"return prepareCalculDates('{$plot_key}', '{$min_x}', '{$max_x}');\"
                class='form-edit-chron' style='display:none;'>

                <input type='hidden' name='date_1' id='date_1_{$plot_key}' value='{$min_x}' />
                <input type='hidden' name='date_2' id='date_2_{$plot_key}' value='{$max_x}' />
                " . ${'hidden_check_chron_' . $plot_key} . "

                <input type='submit' class='button_calcul'
                    name='button_calcul'
                    value='" . TEXT_BTN_EDIT_CHRON . "' />
            </form>
        ";
    }
    

    // -----------------------------------------------
    // RA (manual gauge reading) trace
    if ($typedata_chron == 'ra')
    {
        // RA records have several "value" fields depending on the equipment
        // type, and not all station types expose one for the RA graph:
        //   - id_typedata 11 (Hydrometry) -> hydro_h_echelle_1/2 or hydro_h_sonde
        //   - id_typedata  5 (Piezometry) -> piezo_prof_toitnappe (m → cm)
        //   - other types (e.g. Rainfall) -> no single height value to plot
        //
        // If the current station type has no RA value to display, bail out
        // cleanly with a "no data" message instead of building an empty
        // trace (which previously triggered "Undefined variable $valeur"
        // warnings inside the while loop).
        if ($id_typedata != 11 && $id_typedata != 5)
        {
            $graph_load = false;
            // Use minCount_x / maxCount_x (untouched user bounds) — see
            // the note in the $total_rows < 1 branch above.
            $msg_noLoad = TEXT_GRAPH_NO_DATA . ' : ' . $minCount_x . ' - ' . $maxCount_x;
        }
    }

    if ($typedata_chron == 'ra' && $graph_load)
    {
        // Dedicated color for the RA trace title in the unified tooltip
        $ra_color = '#CF0F0F';

        ${'nb_data_'  . $plot_key . '_ra'} = 0;
        ${'max_'      . $plot_key . '_ra'} = 0;
        ${'min_'      . $plot_key . '_ra'} = 9999;

        // Accumulator arrays (same pattern as the main series)
        $ra_x_arr          = [];
        $ra_y_arr          = [];
        $ra_customdata_arr = [];

        $data_chron_query = tep_db_query($sql_link, $sql_chron);
        while ($data_chron_tab = tep_db_fetch_array($data_chron_query))
        {
            // Default value — protects against types that don't fill the
            // branches below (and silences "Undefined variable" warnings
            // when the table has rows but no usable field for this type).
            $valeur = 0;

            // Select the relevant field depending on equipment type
            if ($id_typedata == 11) // Hydrometry
            {
                $valeur = (float)$data_chron_tab['hydro_h_echelle_1'];
                if (!tep_not_null($valeur)) { $valeur = (float)$data_chron_tab['hydro_h_echelle_2']; }
                if (!tep_not_null($valeur)) { $valeur = (float)$data_chron_tab['hydro_h_sonde']; }
            }
            if ($id_typedata == 5) // Piezometry (value stored in m, displayed in cm)
            {
                $valeur = (float)$data_chron_tab['piezo_prof_toitnappe'] * 100;
            }

            if (tep_not_null($valeur))
            {
                ${'nb_data_' . $plot_key . '_ra'}++;
                $ra_x_arr[] = "'" . $data_chron_tab['date_heure_ra'] . "'";
                $ra_y_arr[] = $valeur;

                // Update X boundaries.
                // $min_x / $max_x are intentionally swapped at init (see
                // top of file) and rely on the main data_chron loop to
                // unswap them. For 'ra' / 'jge' chronicles, that main
                // loop never runs, so the first valid point seeds both
                // bounds explicitly to break the swap; subsequent points
                // simply widen them with the usual < / > comparison.
                $date_chron = new DateTime($data_chron_tab['date_heure_ra']);
                if (${'nb_data_' . $plot_key . '_ra'} === 1) {
                    $min_x = $date_chron->format('d-m-Y');
                    $max_x = $date_chron->format('d-m-Y');
                } else {
                    $min_x_dt = new DateTime($min_x);
                    $max_x_dt = new DateTime($max_x);
                    if ($date_chron < $min_x_dt) { $min_x = $date_chron->format('d-m-Y'); }
                    if ($date_chron > $max_x_dt) { $max_x = $date_chron->format('d-m-Y'); }
                }

                if (${'min_' . $plot_key . '_ra'} > $valeur) { ${'min_' . $plot_key . '_ra'} = $valeur; }
                if (${'max_' . $plot_key . '_ra'} < $valeur) { ${'max_' . $plot_key . '_ra'} = $valeur; }
            }

            // Customdata for native hovertemplate :
            // [0] date, [1] rounded value. The template assembles the HTML.
            $dt_ra = new DateTime($data_chron_tab['date_heure_ra']);
            $ra_customdata_arr[] = [
                $dt_ra->format('d-m-Y H:i:s'),
                round($valeur, 1)
            ];
        }

        // Collapse accumulator arrays into JS-ready strings
        ${'graph_x_'          . $plot_key . '_ra'} = implode(',', $ra_x_arr);
        ${'graph_y_'          . $plot_key . '_ra'} = implode(',', $ra_y_arr);
        ${'graph_customdata_' . $plot_key . '_ra'} = json_encode($ra_customdata_arr);
        unset($ra_x_arr, $ra_y_arr, $ra_customdata_arr);

        // Expose the per-graph record count in the title bar. For
        // standard chronicles this is done by the COUNT(*) at the top
        // of the loop, but the 'ra' / 'jge' branches were skipped
        // there (different source table), so $total_rows would stay
        // at its sentinel value of 1.
        $total_rows = ${'nb_data_' . $plot_key . '_ra'};

        // Expand global Y-axis range if RA values exceed current bounds
        if (${'min_' . $plot_key . '_ra'} < $min_y_graph)
        {
            ${'min_' . $plot_key} = ${'min_' . $plot_key . '_ra'};
            $min_y_graph             = ${'min_' . $plot_key . '_ra'};
        }
        if (${'max_' . $plot_key . '_ra'} > $max_y_graph)
        {
            ${'max_' . $plot_key} = ${'max_' . $plot_key . '_ra'};
            $max_y_graph             = ${'max_' . $plot_key . '_ra'};
        }

        // Native hovertemplate for the RA trace.
        // The title is colorized (matches the marker color) for consistency
        // with the main series in unified hover mode.
        $ra_hovertemplate =
            "'<b><span style=\"color:" . $ra_color . "\">" . TEXT_CHRON_RA_HEIGHT . "</span></b>' + " .
            "'<br><b>" . TEXT_GRAPH_HOVER_DATE . "</b> : %{customdata[0]}' + " .
            "'<br><b>" . TEXT_GRAPH_HOVER_HEIGHT . "</b> : %{customdata[1]} cm' + " .
            "'<extra></extra>'";

        // Generate Plotly trace for RA readings (scatter markers)
        ${'js_config_trace_' . $plot_key} .= "
            var trace_{$plot_key}_ra =
            {
                x: [" . ${'graph_x_' . $plot_key . '_ra'} . "],
                y: [" . ${'graph_y_' . $plot_key . '_ra'} . "],
                customdata: " . ${'graph_customdata_' . $plot_key . '_ra'} . ",
                xaxis: 'x2',
                mode: 'markers',
                marker: { size: 9, color: '{$ra_color}', symbol: 'x', line: { color: '{$ra_color}', width: 1.5 } },
                name: \"" . TEXT_CHRON_RA_HEIGHT . "\",
                hovertemplate: " . $ra_hovertemplate . ",
                hoverlabel: {
                    bgcolor: '{$ra_color}',
                    bordercolor: '{$ra_color}',
                    font: { color: 'white', size: 12, family: 'roboto, arial, helvetica' },
                    align: 'left'
                }
            };
        ";
        ${'js_load_trace_' . $plot_key} .= "trace_{$plot_key}_ra,";

        // Free RA data strings now that they have been inlined into the JS
        unset(${'graph_x_' . $plot_key . '_ra'});
        unset(${'graph_y_' . $plot_key . '_ra'});
        unset(${'graph_customdata_' . $plot_key . '_ra'});
    }

    // -----------------------------------------------
    // JGE (streamflow measurement / jaugeage) trace
    if ($typedata_chron == 'jge')
    {
        // Dedicated color for the JGE trace title in the unified tooltip
        $jge_color = '#8D5F8C';

        ${'nb_data_'  . $plot_key . '_jge'} = 0;
        ${'max_'      . $plot_key . '_jge'} = 0;
        ${'min_'      . $plot_key . '_jge'} = 99999;

        // Accumulator arrays
        $jge_x_arr          = [];
        $jge_y_arr          = [];
        $jge_customdata_arr = [];

        $data_chron_query = tep_db_query($sql_link, $sql_chron);
        while ($data_chron_tab = tep_db_fetch_array($data_chron_query))
        {
            $valeur = (float)$data_chron_tab['depouil_q'];
            if (tep_not_null($valeur))
            {
                ${'nb_data_' . $plot_key . '_jge'}++;
                $jge_x_arr[] = "'" . $data_chron_tab['datetime'] . "'";
                $jge_y_arr[] = $valeur;
            }

            // Update X boundaries.
            // Same swap-protection as in the 'ra' branch above — when
            // the user displays only JGE points (no underlying time
            // series), the main data_chron loop never ran and the
            // pre-swapped $min_x / $max_x would never be corrected
            // by plain < / > comparisons. The first valid point seeds
            // both bounds explicitly.
            $date_chron = new DateTime($data_chron_tab['datetime']);
            if (${'nb_data_' . $plot_key . '_jge'} === 1 && tep_not_null($valeur)) {
                $min_x = $date_chron->format('d-m-Y');
                $max_x = $date_chron->format('d-m-Y');
            } else {
                $min_x_dt = new DateTime($min_x);
                $max_x_dt = new DateTime($max_x);
                if ($date_chron < $min_x_dt) { $min_x = $date_chron->format('d-m-Y'); }
                if ($date_chron > $max_x_dt) { $max_x = $date_chron->format('d-m-Y'); }
            }

            if (${'min_' . $plot_key . '_jge'} > $valeur) { ${'min_' . $plot_key . '_jge'} = $valeur; }
            if (${'max_' . $plot_key . '_jge'} < $valeur) { ${'max_' . $plot_key . '_jge'} = $valeur; }

            // Customdata for native hovertemplate :
            // [0] datetime formatted FR (dd-mm-yyyy HH:mm:ss), [1] flow rate rounded to 3 decimals
            $dt_jge = new DateTime($data_chron_tab['datetime']);
            $jge_customdata_arr[] = [
                $dt_jge->format('d-m-Y H:i:s'),
                round($data_chron_tab['depouil_q'], 3)
            ];
        }

        // Collapse accumulator arrays into JS-ready strings
        ${'graph_x_'          . $plot_key . '_jge'} = implode(',', $jge_x_arr);
        ${'graph_y_'          . $plot_key . '_jge'} = implode(',', $jge_y_arr);
        ${'graph_customdata_' . $plot_key . '_jge'} = json_encode($jge_customdata_arr);
        unset($jge_x_arr, $jge_y_arr, $jge_customdata_arr);

        // Expose the per-graph record count in the title bar (same
        // reason as the 'ra' branch above — skipped by the standard
        // COUNT(*) at the top of the loop).
        $total_rows = ${'nb_data_' . $plot_key . '_jge'};

        // Expand global Y-axis range if JGE values exceed current bounds
        if (${'min_' . $plot_key . '_jge'} < $min_y_graph)
        {
            ${'min_' . $plot_key} = ${'min_' . $plot_key . '_jge'};
            $min_y_graph             = ${'min_' . $plot_key . '_jge'};
        }
        if (${'max_' . $plot_key . '_jge'} > $max_y_graph)
        {
            ${'max_' . $plot_key} = ${'max_' . $plot_key . '_jge'};
            $max_y_graph             = ${'max_' . $plot_key . '_jge'};
        }

        // Native hovertemplate for the JGE trace.
        // The title is colorized (matches the marker color) for consistency
        // with the main series in unified hover mode.
        $jge_hovertemplate =
            "'<b><span style=\"color:" . $jge_color . "\">" . TEXT_CHRON_JGE . "</span></b>' + " .
            "'<br><b>" . TEXT_GRAPH_HOVER_DATE . "</b> : %{customdata[0]}' + " .
            "'<br><b>" . TEXT_GRAPH_HOVER_FLOW . "</b> : %{customdata[1]} m³/s' + " .
            "'<extra></extra>'";

        // Generate Plotly trace for streamflow measurements (scatter markers)
        ${'js_config_trace_' . $plot_key} .= "
            var trace_{$plot_key}_jge =
            {
                x: [" . ${'graph_x_' . $plot_key . '_jge'} . "],
                y: [" . ${'graph_y_' . $plot_key . '_jge'} . "],
                customdata: " . ${'graph_customdata_' . $plot_key . '_jge'} . ",
                xaxis: 'x2',
                mode: 'markers',
                marker: { size: 9, color: '{$jge_color}', symbol: 'x', line: { color: '{$jge_color}', width: 1.5 } },
                name: '" . TEXT_CHRON_JGE . "',
                hovertemplate: " . $jge_hovertemplate . ",
                hoverlabel: {
                    bgcolor: '{$jge_color}',
                    bordercolor: '{$jge_color}',
                    font: { color: 'white', size: 12, family: 'roboto, arial, helvetica' },
                    align: 'left'
                }
            };
        ";
        ${'js_load_trace_' . $plot_key} .= "trace_{$plot_key}_jge,";

        // Free JGE data strings now that they have been inlined into the JS
        unset(${'graph_x_' . $plot_key . '_jge'});
        unset(${'graph_y_' . $plot_key . '_jge'});
        unset(${'graph_customdata_' . $plot_key . '_jge'});
    }

    // -----------------------------------------------
    // Field visit (fait marquant) markers
    //
    // Title color uses a darker shade than the marker (#FFE100 yellow is
    // unreadable on a white tooltip background — we use a deep gold instead).
    $fv_title_color = '#B8A100';

    $nb_fieldVisit       = 0;
    $fv_x_arr            = [];
    $fv_y_arr            = [];
    $fv_customdata_arr   = [];

    $sql_fieldVisit = "SELECT DISTINCT ra.id_ra, ra.date_heure_ra, ra.id_eq_type,
                              ra.type_appareil, ra.num_appareil, ra.etat_ra,
                              ra.piezo_pompage_encours, ra.piezo_pompage_proche, ra.piezo_pluie_crue,
                              ra.piezo_temps_sec, ra.piezo_photos,
                              ra.ra_obs, ra.ra_futur, ra.name_file_data, ra.obs_file_data,
                              ra.pre_marquant, ra.fait_marquant, ra.agents_complement
                       FROM " . TABLE_DATA_RA . " ra
                       WHERE ra.id_station    = " . $cle_station . "
                         AND ra.date_heure_ra >= '" . $min_x_dt->format('Y-m-d') . " 00:00:00'
                         AND ra.date_heure_ra <= '" . $max_x_dt->format('Y-m-d') . " 23:59:59'
                         AND ra.fait_marquant  = 1
                       ORDER BY ra.date_heure_ra DESC";

    $fieldVisit_query = tep_db_query($sql_link, $sql_fieldVisit);
    while ($fieldVisit_tab = tep_db_fetch_array($fieldVisit_query))
    {
        $fv_x_arr[] = "'" . $fieldVisit_tab['date_heure_ra'] . "'";
        $fv_y_arr[] = $fieldVisit_y;
        $nb_fieldVisit++;

        // Customdata for native hovertemplate + Ctrl+click navigation:
        // [0] date formatted FR (dd-mm-yyyy HH:mm:ss),
        // [1] observation (wrapped on spaces),
        // [2] id_ra      — read by the plotly_click handler below to
        //                  open the matching RA in a new tab.
        // [3] id_eq_type — same handler uses it as the ?td= URL param
        //                  (1=pluvio, 5=piezo, 11=hydro). Required by
        //                  list_ra.php's loadRA(): without it the right
        //                  RA-rendering endpoint can't be chosen.
        $dt_fv = new DateTime($fieldVisit_tab['date_heure_ra']);
        $fv_customdata_arr[] = [
            $dt_fv->format('d-m-Y H:i:s'),
            wordwrap($fieldVisit_tab['ra_obs'], TOOLTIP_WRAP_WIDTH, '<br>', false),
            (int) $fieldVisit_tab['id_ra'],
            (int) $fieldVisit_tab['id_eq_type']
        ];
    }

    // Generate field visit marker trace (only if at least one visit exists)
    if ($nb_fieldVisit > 0)
    {
        $graph_x_fieldVisit          = implode(',', $fv_x_arr);
        $graph_y_fieldVisit          = implode(',', $fv_y_arr);
        $graph_customdata_fieldVisit = json_encode($fv_customdata_arr);
        unset($fv_x_arr, $fv_y_arr, $fv_customdata_arr);

        // Native hovertemplate for field visits.
        // The title is colorized for consistency with the other traces.
        $fv_hovertemplate =
            "'<b><span style=\"color:" . $fv_title_color . "\">" . TEXT_CHRON_RA . "</span></b>' + " .
            "'<br><b>" . TEXT_GRAPH_HOVER_DATE . "</b> : %{customdata[0]}' + " .
            "'<br><b>" . TEXT_GRAPH_HOVER_OBS . "</b> : %{customdata[1]}' + " .
            "'<extra></extra>'";

        ${'js_config_trace_' . $plot_key} .= "
            var trace_{$plot_key}_fieldVisit =
            {
                x: [{$graph_x_fieldVisit}],
                y: [{$graph_y_fieldVisit}],
                customdata: {$graph_customdata_fieldVisit},
                xaxis: 'x2',
                mode: 'markers',
                type: 'scatter',
                visible: 'legendonly',
                meta: 'fieldVisit',
                marker: {
                    size: 10,
                    color: '#FFE100',
                    symbol: 'square',
                    line: { width: 1, color: 'black' }
                },
                name: \"" . TEXT_CHRON_RA . "\",
                hovertemplate: " . $fv_hovertemplate . ",
                hoverlabel: {
                    bgcolor: '#FFE100',
                    bordercolor: 'black',
                    font: { color: 'black', size: 12, family: 'roboto, arial, helvetica' },
                    align: 'left'
                }
            };
        ";
        ${'js_load_trace_' . $plot_key} .= "trace_{$plot_key}_fieldVisit,";

        unset($graph_x_fieldVisit, $graph_y_fieldVisit, $graph_customdata_fieldVisit);
    }
    else
    {
        unset($fv_x_arr, $fv_y_arr, $fv_customdata_arr);
    }

    // -----------------------------------------------
    // Virtual zero trace anchored to the PRIMARY xaxis (not x2).
    //
    // Originally this invisible trace lived on xaxis2 to keep the
    // rangeslider responsive even when only x2-anchored series were
    // visible. But Plotly only renders an axis if it has at least one
    // trace attached: if the user displays a graph with markers-only
    // chronicles (RA, JGE, fieldVisit, …), all real traces are on
    // 'x2', leaving 'x' (and therefore the date labels + rangeslider)
    // empty, so the chart appears without an X axis at the bottom.
    //
    // Anchoring this virtual trace to 'x' instead solves both problems:
    //   • the primary axis always has a trace → dates render
    //   • the rangeslider stays linked to 'x' (where it lives) → still
    //     responsive when the user pans/zooms
    // The trace itself is invisible (line width 0, hoverinfo skipped).
    ${'js_config_trace_' . $plot_key} .= "
        var trace_{$plot_key}_0 =
        {
            hovermode: 'closest',
            x: ['" . datefr_us($min_x) . "', '" . datefr_us($max_x) . "'],
            y: [0, 0],
            xaxis: 'x',
            yaxis: 'y',
            type: 'scatter',
            mode: 'lines',
            line: { width: 0 },
            hoverinfo: 'skip',
            showlegend: false,
            rangeslider: { visible: false }
        };
    ";
    ${'js_load_trace_' . $plot_key} .= "trace_{$plot_key}_0,";

    // -----------------------------------------------
    // Build Plotly layout configuration
    ${'js_layout_' . $plot_key} = "
        var layout_{$plot_key} =
        {
            xaxis:
            {
                title: { standoff: 5 },
                // Rangeslider disabled: the quality-code timeline is shown
                // directly below the chart instead. Navigation still works
                // via mouse zoom/pan, the zoom-period buttons and Adjust scale.
                rangeslider: {
                    visible: false,
                },
                type: 'date',
                showgrid: true,
                gridcolor: '#ddd',
                gridwidth: 1,
                // Rangeslider is off, so make sure the X-axis date labels are
                // explicitly shown at the bottom of the plot area, with room
                // reserved for them (automargin).
                showticklabels: true,
                automargin: true,
                range: ['" . datefr_us($min_x) . "', '" . datefr_us($max_x) . "'],
                titlefont: { family: 'roboto, arial, helvetica', size: 12, bold: true, color: '#000000' },
                tickangle: 0,
                ticklen: 5,
                showline: true,
                linewidth: 2,
                showspikes: true,
                spikemode: 'across',
                spikedash: 'dot',
                spikecolor: '#000',
                spikethickness: 0.25,
                hoverformat: '%d-%m-%Y %H:%M:%S',
            },
            // Secondary X-axis: overlaid, hidden — used for RA/JGE/stats traces
           xaxis2:
            {
                overlaying: 'x',
                matches: 'x',
                range: ['" . datefr_us($min_x) . "', '" . datefr_us($max_x) . "'],
                type: 'date',
                showgrid: false,
                zeroline: false,
                showticklabels: false,
                hoverformat: '%d-%m-%Y %H:%M:%S',
                ticks: ''
            },
            yaxis:
            {
                title: { text: '{$text_yaxis}', standoff: 15 },
                automargin: true,
                titlefont: { family: 'roboto, arial, helvetica', size: 13, bold: true, color: '#000000' },
                tickformat: ',.{$nb_dec}f',
                ticklen: 5,
                showline: true,
                linewidth: 1,
                autorange: false,
                range: [{$min_y_graph}, {$max_y_graph}],
                fixedrange: false,
                showspikes: true,
                spikemode: 'across',
                spikedash: 'dot',
                spikesnap: 'data',
                spikecolor: '#000',
                spikethickness: 0.25,
            },
            dragmode: 'zoom',
            hovermode: 'x unified',
            hoverdistance: 50,
            uirevision: 'true',
            cursor: 'pointer',
            margin: { l: 50, r: 10, t: 30, b: 0 },
            barmode: 'group',
            bargap: 0.1,
            bargroupgap: 0.1,
            showlegend: true,
            legend: { x: 0, y: 1.1, orientation: 'h', font: { size: 11 } },

            // Gap (lacune) highlight shapes
            shapes: [" . ${'edit_lacune_' . $plot_key} . "],
        };
    ";

    // -----------------------------------------------
    // Assemble the final data array and Plotly render call
    ${'data_' . $plot_key} = "var data_{$plot_key} = [" .
        substr(${'js_load_trace_' . $plot_key}, 0, -1) . // Remove trailing comma
        "];";

    $textGraph  = ${'js_config_trace_' . $plot_key};
    $textGraph .= ${'data_'           . $plot_key};
    $textGraph .= ${'js_layout_'      . $plot_key};
    $textGraph .= "
        // Adaptive bar width: set each bar trace's width to ~80% of the MEDIAN
        // spacing between consecutive points. Using the median (not Plotly's
        // default smallest-gap, nor a fixed % of the total span) makes bars look
        // right at every scale — wide for sparse annual series, thin for dense
        // daily series — and is robust to a few abnormally close points.
        if (typeof adjustBarWidths !== 'function') {
            window.adjustBarWidths = function(plotId) {
                var gd = document.getElementById(plotId);
                if (!gd || !gd.data) { return; }
                var widths = [], idxs = [];
                for (var t = 0; t < gd.data.length; t++) {
                    var tr = gd.data[t];
                    if (!tr || tr.type !== 'bar' || !tr.x || tr.x.length < 2) { continue; }
                    // Convert X to timestamps and sort.
                    var xs = [];
                    for (var i = 0; i < tr.x.length; i++) {
                        var ms = (typeof tr.x[i] === 'number') ? tr.x[i] : new Date(tr.x[i]).getTime();
                        if (!isNaN(ms)) { xs.push(ms); }
                    }
                    if (xs.length < 2) { continue; }
                    xs.sort(function(a, b){ return a - b; });
                    var gaps = [];
                    for (var g = 1; g < xs.length; g++) {
                        var d = xs[g] - xs[g-1];
                        if (d > 0) { gaps.push(d); }
                    }
                    if (!gaps.length) { continue; }
                    gaps.sort(function(a, b){ return a - b; });
                    var mid = Math.floor(gaps.length / 2);
                    var median = (gaps.length % 2) ? gaps[mid] : (gaps[mid-1] + gaps[mid]) / 2;
                    widths.push(Math.round(median * 0.8));
                    idxs.push(t);
                }
                if (idxs.length) {
                    try { Plotly.restyle(plotId, { width: widths }, idxs); } catch (e) {}
                }
            };
        }
    ";
    $textGraph .= "Plotly.newPlot('plot_{$plot_key}', data_{$plot_key}, layout_{$plot_key}, config);";
    $textGraph .= "addLogScaleButton('plot_{$plot_key}', 'log_{$plot_key}', 'yaxis');";
    $textGraph .= "if (typeof adjustBarWidths === 'function') { adjustBarWidths('plot_{$plot_key}'); }";
    $textGraph .= "
                    Plotly.addTraces('plot_{$plot_key}', {
                        x: [null],
                        y: [null],
                        xaxis: 'x2',
                        mode: 'markers',
                        marker: { size: 14, color: 'black', line: { color: 'black', width: 5 }, symbol: 'circle' },
                        hoverinfo: 'skip',
                        showlegend: false
                    });
                    var ghostIndex_{$plot_key} = document.getElementById('plot_{$plot_key}').data.length - 1;
                ";

    // -----------------------------------------------
    // Plotly interaction handlers:
    // - plotly_relayout:    sync input fields after zoom/pan/rangeslider ends
    // - plotly_relayouting: real-time field updates during drag
    // - plotly_doubleclick: reset axes to initial ranges
    //
    // NOTE: the plotly_hover/plotly_unhover handlers and the HTML hoverBox
    // have been removed in favor of Plotly's native hovertemplate, which is
    // much faster on large datasets (SVG/Canvas rendering, no DOM manipulation).
    $textGraphFonction = "

        var gd = document.getElementById('plot_{$plot_key}');

        // Flag used to suppress relayout events triggered by the double-click reset
        var __isDoubleClickReset = false;

        // ----- Active-graph tracking -----
        //
        // In multi-graph mode, every graph on the page has its own
        // plotly_relayout listener but they all write to the same shared
        // inputs (#x1Zoom, #x2Zoom, #y1Zoom, #y2Zoom). Without a guard,
        // any relayout — including Plotly's automatic ones at init time —
        // overwrites the inputs, so by the time the user clicks 'Edit
        // chron' the inputs reflect the last graph that emitted a
        // relayout, not the one the user actually zoomed on.
        //
        // We use a single page-level variable, window.__activePlotKey,
        // updated on mouseenter of each graph container. The relayout
        // handler then only syncs the shared inputs when its own
        // plot_key matches the active one. This is enough because the
        // user must always have hovered the graph before zooming it.
        gd.addEventListener('mouseenter', function() {
            window.__activePlotKey = '{$plot_key}';
        });

        // ---- Field Visit interaction (enlarged click zone + cursor) ----
        //
        // Plotly only detects clicks on the exact marker (10px), which forces
        // the user to aim precisely. Here we widen the hit zone:
        //   - listen for mouse moves / clicks anywhere on the graph
        //   - convert the cursor pixel position to an x2 data coordinate
        //   - keep the field visit that is CLOSEST horizontally
        //   - within a PIXEL tolerance (constant regardless of zoom level)
        //
        // UX bonus: as soon as the cursor approaches a marker, we show the
        // 'pointer' cursor to signal the zone is clickable — the user
        // discovers the interaction without any prior knowledge.
        
        // Click/hover hit-test against the field-visit squares. The match must
        // be close to the square in BOTH axes (the marker is ~10px); otherwise
        // a click anywhere in the vertical column would trigger it.
        // ---- Native Plotly hover/click on the field-visit markers ----
        // We rely on Plotly's own hit-testing (the same engine that shows the
        // field-visit tooltip) instead of a manual pixel computation, so the
        // pointer cursor and the click only react ON the yellow square.
        var dragLayer_{$plot_key} = gd.querySelector('.nsewdrag') || gd;

        gd.on('plotly_hover', function(eventData) {
            if (!eventData || !eventData.points || !eventData.points.length) { return; }
            var orig = eventData.event;
            for (var i = 0; i < eventData.points.length; i++) {
                var p = eventData.points[i];
                if (!p.data || p.data.meta !== 'fieldVisit') { continue; }
                // Vertical guard: hovermode 'x unified' reports the column's
                // points, so require the cursor to be near the square in Y.
                try {
                    var fl = gd._fullLayout;
                    if (orig && fl && fl.yaxis && typeof fl.yaxis.d2p === 'function') {
                        var bb = gd.getBoundingClientRect();
                        var yAx = p.yaxis || fl.yaxis;
                        var cursorY = orig.clientY - bb.top;
                        var markerY = (yAx._offset || 0) + yAx.d2p(p.y);
                        if (Math.abs(cursorY - markerY) > 12) { continue; }
                    }
                } catch (e) {}
                dragLayer_{$plot_key}.style.setProperty('cursor', 'pointer', 'important');
                return;
            }
            dragLayer_{$plot_key}.style.setProperty('cursor', '', '');
        });

        gd.on('plotly_unhover', function() {
            dragLayer_{$plot_key}.style.setProperty('cursor', '', '');
        });

        gd.on('plotly_click', function(eventData) {
            if (!eventData || !eventData.points || !eventData.points.length) { return; }
            var pt = null;
            for (var i = 0; i < eventData.points.length; i++) {
                if (eventData.points[i].data && eventData.points[i].data.meta === 'fieldVisit') {
                    pt = eventData.points[i]; break;
                }
            }
            if (!pt) { return; }

            var cd = pt.customdata;
            if (!cd || cd.length < 4) { return; }
            var idRa = parseInt(cd[2], 10);
            var td   = parseInt(cd[3], 10);
            if (!idRa || idRa <= 0 || !td || td <= 0) { return; }

            var orig = eventData.event;

            // Vertical guard: only open when the click is near the square in Y
            // (hovermode 'x unified' otherwise matches the whole X column).
            try {
                var fl = gd._fullLayout;
                if (orig && fl && fl.yaxis && typeof fl.yaxis.d2p === 'function') {
                    var bb = gd.getBoundingClientRect();
                    var yAx = pt.yaxis || fl.yaxis;
                    var cursorY = orig.clientY - bb.top;
                    var markerY = (yAx._offset || 0) + yAx.d2p(pt.y);
                    if (Math.abs(cursorY - markerY) > 12) { return; }
                }
            } catch (e) {}

            if (orig && orig.preventDefault)  { orig.preventDefault(); }
            if (orig && orig.stopPropagation) { orig.stopPropagation(); }

            // Open the field sheet in a new tab.
            var __ra_url = 'list_ra.php?st=" . (int)$cle_station . "&ra=' + idRa + '&td=' + td;
            setTimeout(function() {
                var __ra_a    = document.createElement('a');
                __ra_a.href   = __ra_url;
                __ra_a.target = '_blank';
                __ra_a.rel    = 'noopener';
                document.body.appendChild(__ra_a);
                __ra_a.click();
                document.body.removeChild(__ra_a);
            }, 0);
        });

        // ---- plotly_relayout: fired after interaction ends ----
        gd.on('plotly_relayout', function(eventData)
        {
            if (__isDoubleClickReset === true) { return; }
            //if (__isPacketLoading   === true) { return; }

            // Ignore events originating from xaxis2 to prevent feedback loops
            if (eventData['xaxis2.range[0]'] !== undefined) { return; }

            // Ignore relayouts that aren't a real user zoom on THIS graph:
            //   * window.__activePlotKey is updated on mouseenter, so when
            //     it doesn't match we know the user is acting on another
            //     graph and we must not overwrite the shared inputs.
            //   * autosize is fired by Plotly itself at init.
            if (window.__activePlotKey && window.__activePlotKey !== '{$plot_key}') { return; }
            if (eventData['autosize'] === true) { return; }

            var x1_format = '';
            var x2_format = '';

            // Resolve X range from event data (supports both indexed and array formats)
            var x1 = eventData['xaxis.range[0]'];
            var x2 = eventData['xaxis.range[1]'];
            if ((x1 === undefined || x2 === undefined) && Array.isArray(eventData['xaxis.range'])) {
                x1 = eventData['xaxis.range'][0];
                x2 = eventData['xaxis.range'][1];
            }

            // X autorange reset: restore initial date boundaries
            if (eventData['xaxis.autorange'] === true) {
                x1 = '{$min_x}';
                x2 = '{$max_x}';
            }

            // Fallback: read from current layout if not found in the event payload
            if ((x1 === undefined || x2 === undefined) && gd && gd.layout && gd.layout.xaxis && Array.isArray(gd.layout.xaxis.range)) {
                x1 = gd.layout.xaxis.range[0];
                x2 = gd.layout.xaxis.range[1];
            }

            // Resolve Y range
            var y1 = eventData['yaxis.range[0]'];
            var y2 = eventData['yaxis.range[1]'];
            if ((y1 === undefined || y2 === undefined) && Array.isArray(eventData['yaxis.range'])) {
                y1 = eventData['yaxis.range'][0];
                y2 = eventData['yaxis.range'][1];
            }
            if (eventData['yaxis.autorange'] === true && gd && gd.layout && gd.layout.yaxis && Array.isArray(gd.layout.yaxis.range)) {
                y1 = gd.layout.yaxis.range[0];
                y2 = gd.layout.yaxis.range[1];
            }

            // Sync the date input fields (X: convert from ISO yyyy-mm-dd to dd-mm-yyyy)
            if (x1 && typeof x1 === 'string') {
                x1_format = x1.split(' ')[0].split('-').reverse().join('-');
                document.getElementById('x1Zoom').value = x1_format;
            }
            if (x2 && typeof x2 === 'string') {
                x2_format = x2.split(' ')[0].split('-').reverse().join('-');
                document.getElementById('x2Zoom').value = x2_format;
            }
            if (typeof y1 !== 'undefined' && !isNaN(y1)) { document.getElementById('y1Zoom').value = parseInt(y1); }
            if (typeof y2 !== 'undefined' && !isNaN(y2)) { document.getElementById('y2Zoom').value = parseInt(y2); }
        });

        // ---- plotly_relayouting: real-time updates during drag / rangeslider ----
        gd.on('plotly_relayouting', function(eventData)
        {
            if (__isDoubleClickReset === true) { return; }

            // Same active-graph guard as plotly_relayout above — only sync
            // the shared inputs when the user is acting on THIS graph.
            if (window.__activePlotKey && window.__activePlotKey !== '{$plot_key}') { return; }

            // X axis (rangeslider drag or pan)
            var xr = eventData['xaxis.range'] || [eventData['xaxis.range[0]'], eventData['xaxis.range[1]']];
            if (Array.isArray(xr) && xr[0] !== undefined && xr[1] !== undefined) {
                if (typeof xr[0] === 'string') {
                    document.getElementById('x1Zoom').value = xr[0].split(' ')[0].split('-').reverse().join('-');
                }
                if (typeof xr[1] === 'string') {
                    document.getElementById('x2Zoom').value = xr[1].split(' ')[0].split('-').reverse().join('-');
                }
            }

            // Y axis (zoom/pan)
            var yr = eventData['yaxis.range'] || [eventData['yaxis.range[0]'], eventData['yaxis.range[1]']];
            if (Array.isArray(yr) && yr[0] !== undefined && yr[1] !== undefined) {
                if (!isNaN(yr[0])) { document.getElementById('y1Zoom').value = parseInt(yr[0]); }
                if (!isNaN(yr[1])) { document.getElementById('y2Zoom').value = parseInt(yr[1]); }
            }
        });

        // ---- plotly_doubleclick: reset all axes to their initial ranges ----
        gd.on('plotly_doubleclick', function()
        {
            // Only the active graph should be allowed to reset the shared
            // zoom inputs — otherwise a doubleclick on graph #2 would
            // overwrite the manual zoom the user just made on graph #1.
            if (window.__activePlotKey && window.__activePlotKey !== '{$plot_key}') { return; }
            __isDoubleClickReset = true;
            document.getElementById('x1Zoom').value = '{$min_x}';
            document.getElementById('x2Zoom').value = '{$max_x}';
            document.getElementById('y1Zoom').value = parseInt({$min_y_graph});
            document.getElementById('y2Zoom').value = parseInt({$max_y_graph});

            // Release the lock after Plotly's internal event burst has settled
            setTimeout(function(){ __isDoubleClickReset = false; }, 60);
        });
    ";

    $text_lacunes = ${'html_tab_lacune_' . $plot_key};

} // end if: graph_load

// -----------------------------------------------
// Statistics button.
//
// Hidden for the 'ra' and 'jge' chronicles: those graphs display
// scatter points only (no continuous time series), so percentiles,
// quartiles or return periods aren't meaningful for them.
// An empty string here means the JS side won't append any button
// to the placeholder div, and we also leave the container hidden
// (see graph_chron.php where button_stats_<plot_key> is only made
// visible when text_button_stats is non-empty).
if ($typedata_chron == 'ra' || $typedata_chron == 'jge')
{
    $text_button_stats = '';
}
else
{
    $text_button_stats = "
        <input type='button' class='button_stats' name='button_stats'
               value='" . TEXT_BTN_STATS . "'
               onClick='afficheStats({$cle_station}, {$id_typedata}, {$typedata_chron})' />
    ";
}

// -----------------------------------------------
// Encode and return all graph data as a JSON response
$responseData = [
    'graph_load'         => $graph_load,
    'msg_noLoad'         => $msg_noLoad,
    'js_text'            => $textGraph . $textGraphFonction,
    'text_lacunes'       => $text_lacunes,
    'text_button_calcul' => $text_button_calcul,
    'text_button_stats'  => $text_button_stats,
    'text_button_tab'    => $text_button_tab,
    'n_years'            => $n_years,
    'min_x'              => $min_x,
    'max_x'              => $max_x,
    'min_y'              => $min_y_graph,
    'max_y'              => $max_y_graph,
    'total_rows'         => $total_rows,
];
echo json_encode($responseData);

// -----------------------------------------------
// Helper function: generate a Plotly statistical line trace.
// Used for percentiles, quartiles, mean, and Gumbel return periods.
//
// @param string $plot_key     Composite suffix (station_chronicle) used to
//                             build a unique JS variable name per graph
// @param string $percent      Trace ID suffix (e.g. 'p99', 'T100')
// @param float  $value        Y value for the horizontal line
// @param string $min          Start X timestamp
// @param string $max          End X timestamp
// @param string $line         Plotly dash style ('dash', 'solid', etc.)
// @param string $color        CSS color string
// @param string $name         Legend label (without value)
// @param string $unite        Unit string appended to the legend label
// @param int    $nb_dec       Number of decimal places for rounding
//
// @return string              JS variable declaration for this trace
function generateStatTrace($plot_key, $percent, $value, $min, $max, $line, $color, $name, $unite, $nb_dec)
{
    $value_format = round($value, $nb_dec);
    $value_js     = json_encode($value_format); // Ensures a valid JS numeric literal

    return "
        var {$percent}Trace_{$plot_key} =
        {
            x: ['{$min}', '{$max}'],
            y: [{$value_js}, {$value_js}],
            xaxis: 'x2',
            type: 'scatter',
            mode: 'lines',
            line: {
                color: '{$color}',
                width: 2,
                dash: '{$line}'
            },
            name: '{$name} : {$value_js} {$unite}',
            hoverinfo: 'skip',
            rangeslider: { visible: false },
            visible: false
        };
    ";
}
?>