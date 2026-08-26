<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Correction graph data handler - AJAX server-side process
- Builds Plotly graph JS code for the correction page
- Handles gaps, field visits, and multi-trace overlays (base + corrections)
- Returns Plotly.js graph code + UI HTML fragments as a JSON response

----------------------------------------
ALIGNMENT WITH MAIN GRAPH (data_chron_graph)
----------------------------------------
This handler shares the same graphical UX as the main chronological graph:
  - layout (axes, spikes, rangeslider, hoverformat, tickformat dynamique)
  - navigation handlers (relayout / relayouting / doubleclick)
  - native hovertemplate with customdata payload
  - colorized trace titles in unified hover mode
  - per-trace hoverlabel styling

The data-loading logic (SQL queries) is preserved unchanged — only the
in-memory accumulation and tooltip enrichment have been refactored.

----------------------------------------
MEMORY OPTIMIZATION
----------------------------------------
String concatenation with .= is O(n²) in PHP; on long correction periods
this can exhaust memory. Fix: accumulate into PHP arrays ([]= is O(1)),
then implode() once at the end. Same pattern as the main graph handler.

----------------------------------------
TOOLTIP REFACTORING (native hovertemplate + customdata)
----------------------------------------
Tooltips are built server-side into a customdata array and rendered by
Plotly's native hovertemplate. Conditional fields (quality, obs) are
pre-built with their label included; empty fields disappear naturally.
Long text is pre-wrapped via wordwrap() to constrain tooltip width.
----------------------------------------
*/

// Safety net for very long correction periods
ini_set('memory_limit', '2048M');

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
// Tooltip wrap width (characters per line, shared across all traces).
// wordwrap() inserts <br> on spaces only — long words are never broken.
define('TOOLTIP_WRAP_WIDTH', 50);


// -----------------------------------------------
// Parse JSON input from AJAX request

$dataGraph     = json_decode(file_get_contents('php://input'), true);
// Defensive read: territoireId may be absent if the caller's payload
// didn't include it (e.g. an empty $territoire_id at page render time
// produced 'territoireId : ,' in the JSON). Default to '' so PHP doesn't
// raise an "Undefined array key" warning.
$territoire_id = $dataGraph['territoireId'] ?? '';

// Load translation strings for the active language
require('../../text_content_' . LANGUAGE . '.php');

$cle_station    = $dataGraph['cle_station']    ?? '';
$id_typedata    = $dataGraph['type_station']   ?? '';
$typedata_array = $dataGraph['typedata_array'] ?? [];
$color_tab      = $dataGraph['colorTab']       ?? [];
$min_x          = $dataGraph['min_x']          ?? '';
$max_x          = $dataGraph['max_x']          ?? '';
$id_correction  = $dataGraph['id_correction']  ?? 0;

$min_y       = 9999;
$max_y       = 0;
$color_chron = '#7FB3D5';


// -----------------------------------------------
// Query: Equipment types lookup table

$eq_type_query = tep_db_query($sql_link,
    "SELECT DISTINCT id_eq_type, nom_eq_type, unite_eq_type, valeur_data_type,
            type_color_border, type_color_background, type_graph
     FROM " . TABLE_EQ_TYPE . " WHERE active_eq_type=1 ORDER BY order_eq_type ASC");
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
// Query: Axis types lookup table (label, unit, rounding)
// nb_round is now exploited for the dynamic tickformat and rounded tooltips

$data_type_axe_query = tep_db_query($sql_link,
    "SELECT DISTINCT id, axe, unite, nb_round FROM " . TABLE_DATA_TYPE_AXE);
while ($data_type_axe = tep_db_fetch_array($data_type_axe_query))
{
    $data_type_axe_array[$data_type_axe['id']] = [
        'axe'      => $data_type_axe['axe'],
        'unite'    => $data_type_axe['unite'],
        'nb_round' => $data_type_axe['nb_round'],
    ];
}


// -----------------------------------------------
// Query: Series types lookup table

$type_chron_query = tep_db_query($sql_link,
    "SELECT DISTINCT id_data_type, init_type_data, nom_type_data, id_eq_type_data, axe_data, unite,
            to_periode, id_chon_periode, traitement, type_graph
     FROM " . TABLE_TYPE_DATA . " ORDER BY init_type_data ASC");
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
// Query: Quality codes (used in customdata tooltips)

$sql_code_qual = "SELECT DISTINCT id_data_qualite, init_qualite_data, nom_qualite_data
                  FROM " . TABLE_DATA_QUALITE . "
                  ORDER BY id_data_qualite";
$code_qual_query = tep_db_query($sql_link, $sql_code_qual);
$code_qual_array = [];
while ($code_qual = tep_db_fetch_array($code_qual_query))
{
    $code_qual_array[$code_qual['id_data_qualite']] = [
        'init_qualite_data' => html_entity_decode($code_qual['init_qualite_data'] ?? ''),
        'nom_qualite_data'  => html_entity_decode($code_qual['nom_qualite_data']  ?? ''),
    ];
}


// -----------------------------------------------
// Build graph data array (base series + correction overlays)

$array_keys     = array_keys($typedata_array);
$typedata_chron = $array_keys[0];

// Resolve series-level metadata once (used by tooltip and axis labels)
$nb_dec     = $type_chron_array[$typedata_chron]['nbRound'];
$unite      = $type_chron_array[$typedata_chron]['unite'];
$axe_nom    = $type_chron_array[$typedata_chron]['axe_nom'];
$text_yaxis = $axe_nom . ' (' . $unite . ')';

$tab_data_graph[] = [
    'sql'         => $typedata_array[$typedata_chron],
    'description' => '',
    'correction'  => 0,
    'new_lacune'  => 0,
    'color'       => $color_tab['data_init'],
];

// Add active correction traces if any
if ($id_correction > 0)
{
    $correction_query = tep_db_query($sql_link,
        "SELECT mc.id, c.datetime_correction, mc.id_correction, mc.type_correction,
                mc.info_correction, mc.axe_correction, mc.datetime_first, mc.datetime_end
         FROM " . TABLE_DATA_META_CORRECTION . " mc
         JOIN " . TABLE_DATA_CORRECTION . " c ON c.id = mc.id_correction
         WHERE mc.id_correction = " . $id_correction . " ORDER BY mc.id DESC");

    while ($correction_tab = tep_db_fetch_array($correction_query))
    {
        // Detect a manual gap correction from the TECHNICAL type code
        // ('lacune'), never from info_correction. info_correction is now
        // localized (e.g. "Gap" / "Lacune"), so a string match against
        // 'Lacune' would silently fail in English and the gap zone would
        // fall back to the chronicle color (blue). type_correction is a
        // stable internal value stored in DB, immune to translation.
        $new_lacune = ($correction_tab['type_correction'] == 'lacune') ? 1 : 0;

        $tab_data_graph[] = [
            'sql'         => "SELECT da.dateheure, da.valeur, dm.id_station, dm.id, dm.id_typedata
                              FROM " . TABLE_DATA_ALL_CORRECTION . " da
                              JOIN " . TABLE_DATA_META_CORRECTION . " dm ON da.id_meta = dm.id
                              WHERE dm.id = " . $correction_tab['id'] . "
                                AND dm.id_station = " . $cle_station . "
                              ORDER BY da.dateheure ASC",
            'description' => $correction_tab['info_correction'],
            'correction'  => 1,
            'new_lacune'  => $new_lacune,
            'color'       => $color_tab[$correction_tab['type_correction']],
        ];
    }
}


// -----------------------------------------------
// Initialize per-station graph variables

$lacune_date_first = '';

${'js_config_trace_' . $cle_station} = '';
${'js_load_trace_'   . $cle_station} = '';
${'edit_lacune_'     . $cle_station} = '';
${'html_tab_lacune_' . $cle_station} = '';
${'min_'             . $cle_station} = 9999;
${'max_'             . $cle_station} = 0;
${'nb_chron_'        . $cle_station} = 0;
${'hidden_check_chron_' . $cle_station} = '';

// Track total row count across all traces — used to decide whether to
// fall back to scattergl/step-line rendering for high-density bar series.
$total_rows_all_traces = 0;

// Number of points plotted on the base trace (cle_graph === 0), exposed to
// the front-end so the markers checkbox can be enabled/disabled by density.
$nb_points_base = 0;


// -----------------------------------------------
// Build traces: data points and gap rectangles

foreach ($tab_data_graph as $cle_graph => $value_tab)
{
    $sql_chron  = $value_tab['sql'];
    $init_chron = $type_chron_array[$typedata_chron]['init_type_data'];
    $name_chron = $type_chron_array[$typedata_chron]['nom_type_data'];
    if ($value_tab['correction'] > 0) { $init_chron .= ' -> (' . $value_tab['description'] . ')'; }

    ${'nb_chron_'                          . $cle_station}++;
    ${'nb_data_'    . $cle_station . '_' . $typedata_chron} = 0;
    ${'min_'        . $cle_station . '_' . $typedata_chron} = 9999;
    ${'max_'        . $cle_station . '_' . $typedata_chron} = 0;
    ${'nb_lacunes_' . $cle_station . '_' . $typedata_chron} = 0;
    ${'tab_lacunes_'. $cle_station . '_' . $typedata_chron} = [];

    // ----- MEMORY OPTIMISATION -----
    // Use arrays instead of concatenated strings for X, Y, customdata.
    // Each []= push is O(1); implode() at the end produces the same JS string
    // but avoids the O(n²) reallocation caused by .= inside a long loop.
    ${'graph_x_arr_'          . $cle_station . '_' . $typedata_chron} = [];
    ${'graph_y_arr_'          . $cle_station . '_' . $typedata_chron} = [];
    ${'graph_customdata_arr_' . $cle_station . '_' . $typedata_chron} = [];

    ${'edit_lacune_'      . $cle_station . '_' . $typedata_chron} = '';
    ${'html_tab_lacune_'  . $cle_station . '_' . $typedata_chron} = '';

    ${'hidden_check_chron_' . $cle_station} .=
        "<input type='hidden' name='check_chron[]' value='"
        . $cle_station . '_' . $id_typedata . '_' . $typedata_chron . "' />\n";

    $valeur            = 0;
    $lacune_date_first = '';

    // -----------------------------------------------
    // Pre-compute ISO bounds outside the loop.
    // Comparing ISO strings is lexicographic-safe for "YYYY-MM-DD HH:MM:SS"
    // and avoids creating one DateTime per row (was a hot spot in the old code).
    $min_x_iso = datefr_us($min_x);
    $max_x_iso = datefr_us($max_x);

    // -----------------------------------------------
    // Main data loop — iterate over every chronological record
    $data_chron_query = tep_db_query($sql_link, $sql_chron);
    while ($data_chron_tab = tep_db_fetch_array($data_chron_query))
    {
        ${'nb_data_' . $cle_station . '_' . $typedata_chron}++;
        $total_rows_all_traces++;

        $dh = $data_chron_tab['dateheure']; // ex: "2023-04-15 10:30:00"

        // Lexicographic comparison on ISO timestamps — no DateTime allocation.
        // $min_x / $max_x stay in FR format (dd-mm-yyyy) as received in the payload;
        // $min_x_iso / $max_x_iso hold the ISO equivalent for the comparison.
        if ($dh < $min_x_iso) {
            $min_x_iso = $dh;
            $min_x     = dateus_fr(substr($dh, 0, 10)); // FR date only, no time
        }
        if ($dh > $max_x_iso) {
            $max_x_iso = $dh;
            $max_x     = dateus_fr(substr($dh, 0, 10)); // FR date only, no time
        }

        if (tep_not_null($lacune_date_first))
        {
            // ---- Closing a gap ----
            ${'edit_lacune_'     . $cle_station . '_' . $typedata_chron} .= $edit_lacune_temp;
            ${'html_tab_lacune_' . $cle_station . '_' . $typedata_chron} .= $html_tab_lacune_temp;

            if (!in_array(abs($data_chron_tab['valeur']), [9999, 99999, 8888, 88888]))
            {
                // Valid value: close the gap shape and append data point
                ${'graph_x_arr_' . $cle_station . '_' . $typedata_chron}[] = "'" . $dh . "'";

                if ($type_chron_array[$typedata_chron]['traitement'] == 0) { $valeur  = $data_chron_tab['valeur']; }
                if ($type_chron_array[$typedata_chron]['traitement'] == 1) { $valeur += $data_chron_tab['valeur']; }

                ${'graph_y_arr_' . $cle_station . '_' . $typedata_chron}[] = (float)$valeur;

                if ($valeur > ${'max_' . $cle_station . '_' . $typedata_chron}) { ${'max_' . $cle_station . '_' . $typedata_chron} = $valeur; }
                if ($valeur < ${'min_' . $cle_station . '_' . $typedata_chron}) { ${'min_' . $cle_station . '_' . $typedata_chron} = $valeur; }

                ${'edit_lacune_'     . $cle_station . '_' . $typedata_chron} .= "   x1: '" . $lacune_date_first . "',";
                ${'html_tab_lacune_' . $cle_station . '_' . $typedata_chron} .= "<td style='height:15px;'>" . $lacune_date_first_fr . "</td></tr>";
            }
            else
            {
                // Consecutive gap: extend the null series.
                // BUGFIX: previously written as the string 'null' (with quotes),
                // which Plotly interpreted as a literal text value rather than
                // a missing point. Now using a real null token in the implode().
                ${'graph_x_arr_' . $cle_station . '_' . $typedata_chron}[] = "'" . $dh . "'";
                ${'graph_y_arr_' . $cle_station . '_' . $typedata_chron}[] = 'null';

                $chron_dateheure_tab = explode(' ', $dh);
                $chron_dateheure_fr  = dateus_fr($chron_dateheure_tab[0]) . ' ' . $chron_dateheure_tab[1];

                ${'edit_lacune_'     . $cle_station . '_' . $typedata_chron} .= "   x1: '" . $dh . "',";
                ${'html_tab_lacune_' . $cle_station . '_' . $typedata_chron} .= "<td style='height:15px;'>" . $chron_dateheure_fr . "</td></tr>";
                ${'tab_lacunes_'     . $cle_station . '_' . $typedata_chron}[${'nb_lacunes_' . $cle_station . '_' . $typedata_chron}]['date_end'] = $dh;
            }

            // Gap-zone fill color:
            //   - New manual 'Gap' correction (new_lacune > 0) -> always
            //     pink (#EA1179), matching the 'lacune' palette entry and
            //     the pink Insert-Gap button. We hard-code it here rather
            //     than relying on $value_tab['color'] because the latter
            //     can carry the resampling trace color (cyan) in some
            //     correction chains, which produced a blue gap zone.
            //   - Native gap already present in the source series
            //     (new_lacune == 0) -> keep the base chronicle color.
            $color_lacune = ($value_tab['new_lacune'] > 0) ? '#EA1179' : $color_chron;

            // Close the Plotly gap shape definition
            ${'edit_lacune_' . $cle_station . '_' . $typedata_chron} .=
                "       y1: 1,
                        fillcolor: '" . $color_lacune . "',
                        opacity: 0.15,
                        line: { width: 0 }
                    },";

            ${'nb_lacunes_' . $cle_station . '_' . $typedata_chron}++;
            $lacune_date_first = '';
        }
        else
        {
            // ---- Normal data point or gap opening ----
            if (!in_array(abs($data_chron_tab['valeur']), [9999, 99999, 8888, 88888]))
            {
                // Valid value
                ${'graph_x_arr_' . $cle_station . '_' . $typedata_chron}[] = "'" . $dh . "'";

                if ($type_chron_array[$typedata_chron]['traitement'] == 0) { $valeur  = $data_chron_tab['valeur']; }
                if ($type_chron_array[$typedata_chron]['traitement'] == 1) { $valeur += $data_chron_tab['valeur']; }

                ${'graph_y_arr_' . $cle_station . '_' . $typedata_chron}[] = (float)$valeur;

                // NB: original code compared raw `valeur` (not the cumulative sum)
                // for min/max here, which differed from the gap-closing branch.
                // We now use the resolved $valeur consistently to match the main graph.
                if ($valeur > ${'max_' . $cle_station . '_' . $typedata_chron}) { ${'max_' . $cle_station . '_' . $typedata_chron} = $valeur; }
                if ($valeur < ${'min_' . $cle_station . '_' . $typedata_chron}) { ${'min_' . $cle_station . '_' . $typedata_chron} = $valeur; }
            }
            else
            {
                // Gap opening — same null fix as above
                ${'graph_x_arr_' . $cle_station . '_' . $typedata_chron}[] = "'" . $dh . "'";
                ${'graph_y_arr_' . $cle_station . '_' . $typedata_chron}[] = 'null';

                $html_tab_lacune_temp = '';

                if (${'nb_lacunes_' . $cle_station . '_' . $typedata_chron} < 1)
                {
                    $html_tab_lacune_temp = "
                        <div class='table-container' style='float:left;width:300px;height:60vh;margin:0;margin-bottom:5px;'>
                            <table id='table_tri' cellspacing='0'>
                                <thead>
                                    <tr class='header-row'>
                                        <th style='width:50px;text-align:center;font-size:11px;'>" . TEXT_CALCUL_VIEW_GAP_COL_SERIES . "</th>
                                        <th style='width:200px;font-size:11px;'>"                  . TEXT_CALCUL_VIEW_GAP_COL_START  . "</th>
                                        <th style='width:200px;font-size:11px;'>"                  . TEXT_CALCUL_VIEW_GAP_COL_END    . "</th>
                                    </tr>
                                </thead>
                    ";
                }

                $edit_lacune_temp = "
                    {
                        type: 'rect',
                        xref: 'x',
                        yref: 'paper',
                        x0: '" . $dh . "',
                        y0: 0,
                ";

                $lacune_date_first     = $dh;
                $lacune_date_first_tab = explode(' ', $lacune_date_first);
                $lacune_date_first_fr  = dateus_fr($lacune_date_first_tab[0]) . ' ' . $lacune_date_first_tab[1];

                ${'tab_lacunes_' . $cle_station . '_' . $typedata_chron}[${'nb_lacunes_' . $cle_station . '_' . $typedata_chron}]['date_first'] = $lacune_date_first;

                $html_tab_lacune_temp .= "<tr>
                    <td style='height:15px;text-align:center;'>" . $type_chron_array[$typedata_chron]['init_type_data'] . "</td>
                    <td style='height:15px;'>" . $lacune_date_first_fr . "</td>
                ";
            }
        }

        // -----------------------------------------------
        // Build the customdata payload for the native hovertemplate.
        //
        // Conditional fields (quality, obs) are pre-built with their
        // "<br><b>Label</b> : " prefix included. Empty fields produce
        // empty strings, so the corresponding lines disappear from the
        // tooltip naturally.
        //
        // PERF: pushing into a PHP array is O(1); json_encode() runs
        // once after the loop on the whole accumulator.
        $date_formatee = substr($dh, 8, 2) . '-' . substr($dh, 5, 2) . '-' . substr($dh, 0, 4) . ' ' . substr($dh, 11);

        // Quality field — only present on the base data trace (correction
        // tables don't carry id_codequal); guard the lookup safely.
        $field_qual = '';
        if (isset($data_chron_tab['id_codequal']))
        {
            $qual_init = (isset($code_qual_array[$data_chron_tab['id_codequal']])
                ? $code_qual_array[$data_chron_tab['id_codequal']]['init_qualite_data']
                : '');
            if (tep_not_null($qual_init))
            {
                $field_qual = '<br><b>' . TEXT_GRAPH_HOVER_QUALCODE . '</b> : ' . $qual_init;
            }
        }

        // Observation field — only present on the base data trace
        $field_obs = '';
        if (isset($data_chron_tab['obs']) && tep_not_null($data_chron_tab['obs']))
        {
            $field_obs = '<br><b>' . TEXT_GRAPH_HOVER_CORRECTION . '</b> : '
                       . wordwrap($data_chron_tab['obs'], TOOLTIP_WRAP_WIDTH, '<br>', false);
        }

        ${'graph_customdata_arr_' . $cle_station . '_' . $typedata_chron}[] = [
            $date_formatee,           // [0] FR-formatted date
            round($valeur, $nb_dec),  // [1] rounded value
            $field_qual,              // [2] quality line (empty or pre-formatted)
            $field_obs,               // [3] obs line (empty or pre-formatted)
        ];
    } // end while: data records for this trace

    // -----------------------------------------------
    // Collapse the accumulator arrays into flat JS-ready strings.
    // This single implode() call replaces what was previously many .= appends.
    ${'graph_x_'          . $cle_station . '_' . $typedata_chron} =
        implode(',', ${'graph_x_arr_' . $cle_station . '_' . $typedata_chron});
    ${'graph_y_'          . $cle_station . '_' . $typedata_chron} =
        implode(',', ${'graph_y_arr_' . $cle_station . '_' . $typedata_chron});

    ${'graph_customdata_' . $cle_station . '_' . $typedata_chron} =
        json_encode(${'graph_customdata_arr_' . $cle_station . '_' . $typedata_chron});

    // Free the accumulator arrays immediately — they are no longer needed
    // and would otherwise occupy memory until end-of-script.
    // (Capture the base-trace point count first — used by the markers toggle.)
    if ($cle_graph === 0)
    {
        $nb_points_base = count(${'graph_x_arr_' . $cle_station . '_' . $typedata_chron});
    }
    unset(${'graph_x_arr_'          . $cle_station . '_' . $typedata_chron});
    unset(${'graph_y_arr_'          . $cle_station . '_' . $typedata_chron});
    unset(${'graph_customdata_arr_' . $cle_station . '_' . $typedata_chron});

    // -----------------------------------------------
    // Choose chart type (line or bar) — same logic as main graph.
    // For very large bar series (>30k points) we fall back to a scattergl
    // step-line filled to zero, which mimics solid bars but renders in WebGL.
    $code_type_graph = '';
    $trace_color     = $value_tab['color'];

    if ($type_chron_array[$typedata_chron]['typegraph'] == 'lines')
    {
        $code_type_graph  = "mode: 'lines',";
        $code_type_graph .= "type: 'scattergl',";
        // Markers stay hidden by default; the front-end checkbox switches the
        // mode to 'lines+markers' via Plotly.restyle. $nb_points_base (captured
        // above, before the accumulators were freed) drives the checkbox state.
    }
    if ($type_chron_array[$typedata_chron]['typegraph'] == 'bar')
    {
        $code_type_graph = "type: 'bar',";

        // High-density fallback — same threshold as main graph.
        // Counts are evaluated on the cumulative total of ALL traces
        // because the correction page can stack 2+ traces on the same period.
        if ($total_rows_all_traces > 30000)
        {
            $code_type_graph = "
                type: 'scattergl',
                mode: 'lines',
                line: { shape: 'hv', width: 1 }, // 'hv' produces staircase steps
                fill: 'tozeroy',                  // fill to zero (mimics bars)
                fillcolor: '" . $trace_color . "',
            ";
        }
    }

    // -----------------------------------------------
    // Update global Y-axis scale with this trace's extremes
    if (${'max_' . $cle_station . '_' . $typedata_chron} > ${'max_' . $cle_station}) { ${'max_' . $cle_station} = ${'max_' . $cle_station . '_' . $typedata_chron}; }
    if (${'min_' . $cle_station . '_' . $typedata_chron} < ${'min_' . $cle_station}) { ${'min_' . $cle_station} = ${'min_' . $cle_station . '_' . $typedata_chron}; }

    // -----------------------------------------------
    // Build the hovertemplate string for this trace.
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
    $trace_title_full = $init_chron . ' - ' . $name_chron;

    $hovertemplate_js =
        "'<b><span style=\"color:" . $trace_color . "\">" . addslashes($trace_title_full) . "</span></b>' + " .
        "'<br><b>" . TEXT_GRAPH_HOVER_DATE . "</b> : %{customdata[0]}' + " .
        "'<br><b>" . $axe_nom . "</b> : %{customdata[1]} " . $unite . "' + " .
        "'%{customdata[2]}' + " .
        "'%{customdata[3]}' + " .
        "'<extra></extra>'";

    // -----------------------------------------------
    // Generate the Plotly trace variable for this series
    ${'js_config_trace_' . $cle_station} .= "
        var trace_" . $cle_station . "_" . $typedata_chron . "_" . $cle_graph . " =
        {
            x: [" . ${'graph_x_' . $cle_station . '_' . $typedata_chron} . "],
            y: [" . ${'graph_y_' . $cle_station . '_' . $typedata_chron} . "],
            customdata: " . ${'graph_customdata_' . $cle_station . '_' . $typedata_chron} . ",

            xaxis: 'x',
            {$code_type_graph}
            legendgroup: 'tdc_{$typedata_chron}_{$cle_graph}',
            name: '" . $trace_title_full . "',

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

    ${'js_load_trace_' . $cle_station} .= "trace_" . $cle_station . "_" . $typedata_chron . "_" . $cle_graph . ",";

    ${'edit_lacune_'     . $cle_station} .= ${'edit_lacune_'     . $cle_station . '_' . $typedata_chron};
    ${'html_tab_lacune_' . $cle_station} .= ${'html_tab_lacune_' . $cle_station . '_' . $typedata_chron} . "</table></div>";

    // Free the inlined JS strings — recover memory before next trace
    unset(${'graph_x_'          . $cle_station . '_' . $typedata_chron});
    unset(${'graph_y_'          . $cle_station . '_' . $typedata_chron});
    unset(${'graph_customdata_' . $cle_station . '_' . $typedata_chron});
}


// -----------------------------------------------
// Update global Y scale and compute display range with padding

if (${'max_' . $cle_station} > $max_y) { $max_y = ${'max_' . $cle_station}; }
if (${'min_' . $cle_station} < $min_y) { $min_y = ${'min_' . $cle_station}; }

${'edit_lacune_' . $cle_station} = rtrim(${'edit_lacune_' . $cle_station}, ',');

$pad_y        = max(0.5, 0.1 * ($max_y - $min_y));
$min_y_graph  = $min_y - $pad_y;
$max_y_graph  = 1.1 * ($max_y + $pad_y);
$fieldVisit_y = $min_y_graph + (abs($pad_y) * 0.2);


// -----------------------------------------------
// Query: Field visit markers (notable events)
//
// Title color uses a darker shade than the marker (#FFE100 yellow is
// unreadable on a white tooltip background — we use a deep gold instead).

$min_x_dt = new DateTime($min_x);
$max_x_dt = new DateTime($max_x);

$fv_title_color    = '#B8A100';
$nb_fieldVisit     = 0;
$fv_x_arr          = [];
$fv_y_arr          = [];
$fv_customdata_arr = [];

$fieldVisit_query = tep_db_query($sql_link,
    "SELECT DISTINCT ra.id_ra, ra.date_heure_ra, ra.id_eq_type, ra.type_appareil, ra.num_appareil,
            ra.etat_ra, ra.piezo_pompage_encours, ra.piezo_pompage_proche, ra.piezo_pluie_crue,
            ra.piezo_temps_sec, ra.piezo_photos, ra.ra_obs, ra.ra_futur, ra.name_file_data,
            ra.obs_file_data, ra.pre_marquant, ra.fait_marquant, ra.agents_complement
     FROM " . TABLE_DATA_RA . " ra
     WHERE ra.id_station    = " . $cle_station . "
       AND ra.date_heure_ra >= '" . $min_x_dt->format('Y-m-d') . " 00:00:00'
       AND ra.date_heure_ra <= '" . $max_x_dt->format('Y-m-d') . " 23:59:59'
       AND ra.fait_marquant  = 1
     ORDER BY ra.date_heure_ra DESC");

while ($fieldVisit_tab = tep_db_fetch_array($fieldVisit_query))
{
    $fv_x_arr[] = "'" . $fieldVisit_tab['date_heure_ra'] . "'";
    $fv_y_arr[] = $fieldVisit_y;
    $nb_fieldVisit++;

    // customdata for native hovertemplate + click navigation:
    // [0] date FR (dd-mm-yyyy HH:mm:ss), [1] obs (wrapped on spaces),
    // [2] id_ra, [3] id_eq_type  (the last two open the matching field sheet)
    $dh_fv = $fieldVisit_tab['date_heure_ra'];
    $date_fv_fr = substr($dh_fv, 8, 2) . '-' . substr($dh_fv, 5, 2) . '-' . substr($dh_fv, 0, 4) . ' ' . substr($dh_fv, 11);
    $fv_customdata_arr[] = [
        $date_fv_fr,
        wordwrap($fieldVisit_tab['ra_obs'] ?? '', TOOLTIP_WRAP_WIDTH, '<br>', false),
        (int) $fieldVisit_tab['id_ra'],
        (int) $fieldVisit_tab['id_eq_type']
    ];
}

// Add field visit trace if any notable events found
if ($nb_fieldVisit > 0)
{
    $graph_x_fieldVisit          = implode(',', $fv_x_arr);
    $graph_y_fieldVisit          = implode(',', $fv_y_arr);
    $graph_customdata_fieldVisit = json_encode($fv_customdata_arr);
    unset($fv_x_arr, $fv_y_arr, $fv_customdata_arr);

    // Native hovertemplate for field visits — title colorized for consistency
    $fv_hovertemplate =
        "'<b><span style=\"color:" . $fv_title_color . "\">" . TEXT_CHRON_RA . "</span></b>' + " .
        "'<br><b>" . TEXT_GRAPH_HOVER_DATE . "</b> : %{customdata[0]}' + " .
        "'<br><b>" . TEXT_GRAPH_HOVER_OBS . "</b> : %{customdata[1]}' + " .
        "'<extra></extra>'";

    ${'js_config_trace_' . $cle_station} .= "
        var trace_" . $cle_station . "_fieldVisit =
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
    ${'js_load_trace_' . $cle_station} .= "trace_" . $cle_station . "_fieldVisit,";

    unset($graph_x_fieldVisit, $graph_y_fieldVisit, $graph_customdata_fieldVisit);
}
else
{
    unset($fv_x_arr, $fv_y_arr, $fv_customdata_arr);
}

// -----------------------------------------------
// Virtual zero trace on xaxis2
// Required to maintain smooth navigation on the rangeslider
// without blocking interaction on the primary x-axis
${'js_config_trace_' . $cle_station} .= "
    var trace_" . $cle_station . "_0 =
    {
        hovermode: 'closest',
        x: ['" . datefr_us($min_x) . "', '" . datefr_us($max_x) . "'],
        y: [0, 0],
        xaxis: 'x2',
        type: 'scattergl',
        mode: 'lines',
        line: { width: 0 },
        hoverinfo: 'skip',
        showlegend: false,
        rangeslider: { visible: false }
    };
";
${'js_load_trace_' . $cle_station} .= "trace_" . $cle_station . "_0,";


// -----------------------------------------------
// Build Plotly layout JS — aligned with main graph

${'js_layout_' . $cle_station} = "
    var layout_" . $cle_station . " =
    {
        xaxis:
        {
            title: { standoff: 5 },
            // Rangeslider disabled: the quality-code meta timeline is shown
            // below the chart instead (see plot_meta_ container + drawMetaTimelineCore).
            rangeslider: { visible: false },
            type: 'date',
            showgrid: true,
            gridcolor: '#ddd',
            gridwidth: 1,
            range: ['" . datefr_us($min_x) . "', '" . datefr_us($max_x) . "'],
            tickfont: { size: 12, family: 'roboto, arial, helvetica' },
            titlefont: { family: 'roboto, arial, helvetica', size: 12, bold: true, color: '#000000' },
            tickangle: 0,
            ticklen: 5,
            showline: true,
            linewidth: 2,
            automargin: true,
            showspikes: true,
            spikemode: 'across',
            spikedash: 'dot',
            spikecolor: '#000',
            spikethickness: 0.25,
            hoverformat: '%d-%m-%Y %H:%M:%S',
        },
        // Secondary X-axis: overlaid, hidden — used for field visits & ghost
        xaxis2:
        {
            overlaying: 'x',
            matches: 'x',
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
        selectdirection: 'h',
        hovermode: 'x unified',
        hoverdistance: 50,
        uirevision: 'true',
        cursor: 'pointer',
        margin: { l: 50, r: 10, t: 30, b: 0 },
        barmode: 'group',
        bargap: 0.1,
        bargroupgap: 1.1,
        showlegend: true,
        legend: { x: 0, y: 1.1, orientation: 'h', font: { size: 11 } },

        // Gap (lacune) highlight shapes
        shapes: [" . ${'edit_lacune_' . $cle_station} . "],
    };
";


// -----------------------------------------------
// Assemble the final data array and Plotly render call

${'data_' . $cle_station} = "var data_" . $cle_station . " = ["
    . substr(${'js_load_trace_' . $cle_station}, 0, -1) . // Remove trailing comma
    "];";

$textGraph  = ${'js_config_trace_' . $cle_station};
$textGraph .= ${'data_'            . $cle_station};
$textGraph .= ${'js_layout_'       . $cle_station};
$textGraph .= "
    if (typeof adjustBarWidths !== 'function') {
        // Adaptive bar width = ~80% of the MEDIAN spacing between consecutive
        // points (robust across scales; see process_graph_multi.php).
        window.adjustBarWidths = function(plotId) {
            var gd = document.getElementById(plotId);
            if (!gd || !gd.data) { return; }
            var widths = [], idxs = [];
            for (var t = 0; t < gd.data.length; t++) {
                var tr = gd.data[t];
                if (!tr || tr.type !== 'bar' || !tr.x || tr.x.length < 2) { continue; }
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
$textGraph .= "Plotly.newPlot('plot_" . $cle_station . "', data_" . $cle_station . ", layout_" . $cle_station . ", config);";
$textGraph .= "addLogScaleButton('plot_" . $cle_station . "', 'log_" . $cle_station . "', 'yaxis');";
$textGraph .= "if (typeof adjustBarWidths === 'function') { adjustBarWidths('plot_" . $cle_station . "'); }";
$textGraph .= "
    Plotly.addTraces('plot_" . $cle_station . "', {
        x: [null],
        y: [null],
        xaxis: 'x2',
        mode: 'markers',
        marker: { size: 14, color: 'black', line: { color: 'black', width: 5 }, symbol: 'circle' },
        hoverinfo: 'skip',
        showlegend: false
    });
    var ghostIndex_" . $cle_station . " = document.getElementById('plot_" . $cle_station . "').data.length - 1;
";


// -----------------------------------------------
// Plotly interaction handlers — aligned with main graph
//
// - plotly_relayout:    sync input fields after zoom/pan/rangeslider ends
// - plotly_relayouting: real-time field updates during drag
// - plotly_doubleclick: reset axes to initial ranges
//
// SPECIFICITY OF THIS HANDLER (vs main graph) :
// the correction page exposes additional fields x1Zoom_h / x2Zoom_h for
// the time-of-day, plus periode_lacune_first / periode_lacune_end for the
// gap creation form. These remain wired in.

$textGraphFonction = "
    var gd = document.getElementById('plot_" . $cle_station . "');

    // ---- Native hover/click on field-visit markers (open the field sheet) ----
    // Same approach as the visualisation graphs: rely on Plotly's own hit-test
    // so only a click/hover ON the yellow square reacts.
    (function(){
        var dragLayer_fv = gd.querySelector('.nsewdrag') || gd;
        gd.on('plotly_hover', function(eventData) {
            if (!eventData || !eventData.points || !eventData.points.length) { return; }
            var orig = eventData.event;
            for (var i = 0; i < eventData.points.length; i++) {
                var p = eventData.points[i];
                if (!p.data || p.data.meta !== 'fieldVisit') { continue; }
                // Vertical guard (same as click): only when near the square.
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
                dragLayer_fv.style.setProperty('cursor', 'pointer', 'important');
                return;
            }
            dragLayer_fv.style.setProperty('cursor', '', '');
        });
        gd.on('plotly_unhover', function() {
            dragLayer_fv.style.setProperty('cursor', '', '');
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

            // Vertical guard. With hovermode 'x unified', a click anywhere in the
            // marker's X column reports the field-visit point — so a click far
            // ABOVE/BELOW the square would still match. Require the cursor to be
            // vertically close to the square (the marker sits on the main yaxis).
            var orig = eventData.event;
            try {
                var fl = gd._fullLayout;
                if (orig && fl && fl.yaxis && typeof fl.yaxis.d2p === 'function') {
                    var bb = gd.getBoundingClientRect();
                    var yAx = pt.yaxis || fl.yaxis;
                    var cursorY = orig.clientY - bb.top;
                    var markerY = (yAx._offset || 0) + yAx.d2p(pt.y);
                    if (Math.abs(cursorY - markerY) > 12) { return; } // ~marker half-size + margin
                }
            } catch (e) {}

            if (orig && orig.preventDefault)  { orig.preventDefault(); }
            if (orig && orig.stopPropagation) { orig.stopPropagation(); }

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
    })();

    // Flag used to suppress relayout events triggered by the double-click reset
    var __isDoubleClickReset = false;

    // NOTE: plotly_relayout / plotly_relayouting handlers were removed.
    // Zoom/pan no longer feeds any input field: the correction period is owned
    // solely by the range SELECTION (shift-drag or select toolbar button), by
    // manual typing, and the 'Apply period' button. The former Y-scale fields
    // (y1Zoom / y2Zoom) have been removed from the UI.

    // ---- plotly_doubleclick: reset all axes to their initial ranges ----
    gd.on('plotly_doubleclick', function()
    {
        __isDoubleClickReset = true;
        var xMin = '" . $min_x . "';
        var xMax = '" . $max_x . "';
        var x1_dt = xMin.split(' ');
        var x2_dt = xMax.split(' ');
        var x1_t  = (x1_dt[1]||'').split('.')[0]; if (!x1_t) { x1_t = '00:00:00'; }
        var x2_t  = (x2_dt[1]||'').split('.')[0]; if (!x2_t) { x2_t = '23:59:59'; }
        document.getElementById('x1Zoom').value   = x1_dt[0];
        document.getElementById('x1Zoom_h').value = x1_t;
        document.getElementById('x2Zoom').value   = x2_dt[0];
        document.getElementById('x2Zoom_h').value = x2_t;
        if (document.getElementById('periode_lacune_first')) {
            document.getElementById('periode_lacune_first').value = 'du '+x1_dt[0]+' à '+x1_t;
        }
        if (document.getElementById('periode_lacune_end')) {
            document.getElementById('periode_lacune_end').value = 'du '+x2_dt[0]+' à '+x2_t;
        }

        // Release the lock after Plotly's internal event burst has settled
        setTimeout(function(){ __isDoubleClickReset = false; }, 60);

        // Reset clears any active selection everywhere (single source of truth).
        if (typeof clearSelectionState === 'function') {
            clearSelectionState();
        } else if (gd.layout && Array.isArray(gd.layout.shapes)) {
            var keptShapes = gd.layout.shapes.filter(function(s){ return s.name !== 'hp_selection_band'; });
            if (keptShapes.length !== gd.layout.shapes.length) {
                Plotly.relayout(gd, { shapes: keptShapes });
            }
        }
    });

    // ---- Range selection WITHOUT zoom (shift-drag or select toolbar button) ----
    // All selection logic lives in the page-level single-source-of-truth API
    // (setSelectionState -> renderBandFromInputs in data_chron_calcul.php).
    // The handlers here just extract the X bounds and delegate, so inline and
    // enlarged graphs behave identically and never paint each other directly.
    function __extractSelBounds(eventData)
    {
        if (!eventData) { return null; }
        if (eventData.range && eventData.range.x) { return eventData.range.x; }
        if (Array.isArray(eventData.selections) && eventData.selections.length
            && typeof eventData.selections[0].x0 !== 'undefined') {
            return [eventData.selections[0].x0, eventData.selections[0].x1];
        }
        return null;
    }

    function __selValToStr(v)
    {
        if (typeof v === 'string') { return v.replace('T', ' ').split('.')[0]; }
        var d = new Date(v);
        var p = function(n){ return (n < 10 ? '0' : '') + n; };
        return d.getUTCFullYear() + '-' + p(d.getUTCMonth()+1) + '-' + p(d.getUTCDate())
             + ' ' + p(d.getUTCHours()) + ':' + p(d.getUTCMinutes()) + ':' + p(d.getUTCSeconds());
    }

    function __applySelection(eventData)
    {
        var xr = __extractSelBounds(eventData);
        if (!xr) { return; }
        if (typeof setSelectionState === 'function') {
            setSelectionState(__selValToStr(xr[0]), __selValToStr(xr[1]));
        }
    }

    // Real-time update while dragging, and final update on release.
    gd.on('plotly_selecting', function(eventData) { __applySelection(eventData); });
    gd.on('plotly_selected',  function(eventData) { __applySelection(eventData); });
";


// -----------------------------------------------
// Encode and return all graph data as a JSON response

echo json_encode([
    'js_layout'      => ${'js_layout_' . $cle_station},
    'js_text'        => $textGraph . $textGraphFonction,
    'text_lacunes'   => ${'html_tab_lacune_' . $cle_station},
    'min_x'          => $min_x,
    'max_x'          => $max_x,
    'min_y'          => $min_y_graph,
    'max_y'          => $max_y_graph,
    // Markers toggle: number of points on the base trace. The front-end
    // shows a checkbox to display markers when this is <= the threshold.
    'nb_points_base' => $nb_points_base,
]);
?>