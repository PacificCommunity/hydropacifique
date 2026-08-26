<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
AJAX endpoint — H→Q conversion chart generator
 
Receives JSON: timezone_php, idStation, typedataChronH, typedataChronQ,
               colorH, colorQ, checkLacH, checkLacQ, reload,
               xDateMin, xDateMax.
 
Builds Plotly JS for three possible time series:
  data_h    — water-level series (y-axis 1)
  data_q    — existing flow-rate series (y-axis 2)
  data_q_n  — pending conversion preview (y-axis 2, green)
 
Hover behaviour:
  - hovermode 'x unified' with one colored hover-box per series
  - crosshair spikelines on both X and Y axes (vertical + horizontal)
  - native hovertemplate showing Date / value / Quality code / Correction
    / Correction obs (empty fields collapse automatically)

Returns JSON: { js_text, nb_data_h }
----------------------------------------
*/
 
require('../../config.php');
require('../../database_tables.php');
require('../../function/date.php');
require('../../function/database.php');
require('../../function/html_output.php');
require('../../function/general.php');
require('../../text_content_' . LANGUAGE . '.php');
 
header('Content-Type: application/json; charset=utf-8');
 
$sql_link = mysqli_connect(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD, DB_DATABASE)
    or die('Cannot connect to the database');
mysqli_query($sql_link, 'SET NAMES UTF8');
 
 
// -----------------------------------------------
// Constants
 
define('PENDING_TRACE_COLOR', '#D946EF'); // colour for pending conversion preview (magenta — signals "draft / to validate")
define('VALUE_MIN_THRESHOLD', -8888);
define('VALUE_MAX_THRESHOLD', 99999);
define('TOOLTIP_WRAP_WIDTH',  50);        // wordwrap width for long obs fields


// -----------------------------------------------
// Helper — JS-safe string for embedding into a single-quoted JS string literal
 
function js_escape_sq($s)
{
    return str_replace(
        ['\\',   "'",   "\r", "\n", "</"],
        ['\\\\', "\\'", '',   ' ',  '<\\/'],
        (string)$s
    );
}
 
 
// -----------------------------------------------
// Axis lookup
 
$data_type_axe_array = [];
$data_type_axe_query = tep_db_query($sql_link,
    "SELECT DISTINCT id, axe, unite, nb_round FROM " . TABLE_DATA_TYPE_AXE);
while ($r = tep_db_fetch_array($data_type_axe_query))
{
    $data_type_axe_array[$r['id']] = ['axe' => $r['axe'], 'unite' => $r['unite'], 'nb_round' => $r['nb_round']];
}
 
 
// -----------------------------------------------
// Time-series type lookup
 
$type_chron_array = [];
$type_chron_query = tep_db_query($sql_link,
    "SELECT DISTINCT id_data_type, init_type_data, nom_type_data, id_eq_type_data,
                     axe_data, unite, to_periode, id_chon_periode
     FROM " . TABLE_TYPE_DATA . " ORDER BY init_type_data ASC");
while ($tc = tep_db_fetch_array($type_chron_query))
{
    $id_axe       = $tc['axe_data'];
    $axe_nom      = $data_type_axe_array[$id_axe]['axe']      ?? '';
    $axe_unite    = $data_type_axe_array[$id_axe]['unite']    ?? '';
    $axe_nb_round = $data_type_axe_array[$id_axe]['nb_round'] ?? '';
    $type_chron_array[$tc['id_data_type']] = [
        'init_type_data'  => $tc['init_type_data'],
        'nom_type_data'   => $tc['nom_type_data'],
        'id_eq_type_data' => $tc['id_eq_type_data'],
        'axe_nom'         => $axe_nom,
        'axe_unite'       => $axe_unite,
        'axe_nb_round'    => $axe_nb_round,
        'to_periode'      => $tc['to_periode'],
        'id_chon_periode' => $tc['id_chon_periode'],
    ];
}


// -----------------------------------------------
// Quality code lookup — used to display the qualifier (e.g. "4-B") in tooltips

$code_qual_array = [];
$code_qual_query = tep_db_query($sql_link,
    "SELECT DISTINCT id_data_qualite, init_qualite_data, nom_qualite_data
     FROM " . TABLE_DATA_QUALITE);
while ($cq = tep_db_fetch_array($code_qual_query))
{
    $code_qual_array[$cq['id_data_qualite']] = [
        'init_qualite_data' => $cq['init_qualite_data'],
        'nom_qualite_data'  => $cq['nom_qualite_data'],
    ];
}
 
 
// -----------------------------------------------
// Read & sanitize input
 
$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) { $data = []; }
 
$timezone_php     = $data['timezone_php']   ?? '';
$station_chron    = (int)($data['idStation']      ?? 0);
$typedata_chron_h = (int)($data['typedataChronH'] ?? 0);
$typedata_chron_q = (int)($data['typedataChronQ'] ?? 0);
 
// Colours — only allow #RRGGBB
$color_h = preg_match('/^#[0-9A-Fa-f]{6}$/', $data['colorH'] ?? '') ? $data['colorH'] : '#000000';
$color_q = preg_match('/^#[0-9A-Fa-f]{6}$/', $data['colorQ'] ?? '') ? $data['colorQ'] : '#000000';
$color_qcal = PENDING_TRACE_COLOR;
 
$check_lac_h = !empty($data['checkLacH']);
$check_lac_q = !empty($data['checkLacQ']);
$reload      = !empty($data['reload']);
 
$date_first = DateTime::createFromFormat('d-m-Y', $data['xDateMin'] ?? '');
$date_end   = DateTime::createFromFormat('d-m-Y', $data['xDateMax'] ?? '');
 
// Y-axis ranges are no longer driven by the front-end (the inputs were
// removed from the left panel). They are always derived from the dataset.
$y_min1 = null; $y_max1 = null;
$y_min2 = null; $y_max2 = null;
 
// Y-axis ranges are always derived from the dataset (the front-end Min/Max
// inputs were removed). We use null sentinels so the first valid value
// sets the bounds, regardless of whether this is a fresh load or a reload.
$y_h_auto = true;
$y_q_auto = true;
 
 
// -----------------------------------------------
// Working buffers
 
$data_graph    = '';
$load_data     = '';

// Date filter applied to every data query (H, Q and pending-conversion Q).
// Previously this was always empty, so the queries loaded the WHOLE series
// regardless of the selected period — which is why the chunk links (that
// pass date_1/date_2) still loaded everything. We now build it from the
// requested period when both bounds are valid DateTime objects.
$condition_date = '';
if ($date_first instanceof DateTime && $date_end instanceof DateTime)
{
    $cd_from = $date_first->format('Y-m-d') . ' 00:00:00';
    $cd_to   = $date_end->format('Y-m-d')   . ' 23:59:59';
    $condition_date = " AND da.dateheure >= '" . mysqli_real_escape_string($sql_link, $cd_from) . "'"
                    . " AND da.dateheure <= '" . mysqli_real_escape_string($sql_link, $cd_to)   . "'";
}
 
$x_h = []; $y_h = []; $cd_h = [];
$x_q = []; $y_q = []; $cd_q = [];
$x_q_n = []; $y_q_n = []; $cd_q_n = [];
 
$nb_data_h = $nb_data_q = $nb_data_q_n = 0;
 
$yaxis_nom = $yaxis_unite = $yaxis2_nom = $yaxis2_unite = '';
 
$edit_lacune_h = '';
$edit_lacune_q = '';

// Volume guard: when the H series for the requested period is too large to
// render comfortably, we don't load it — instead we return a message and a
// set of links splitting the period into smaller chunks (same UX as the
// chronicle module in process_graph_multi.php).
$graph_load   = true;   // false when the volume guard blocks the render
$total_rows_h = 0;      // number of H rows for the requested period
$msg_noLoad   = '';     // HTML message + chunk links shown when blocked
$limit_screen = 200000; // row threshold above which we split into chunks
 
 
// -----------------------------------------------
// Helper — build the customdata array for one data point
// Pre-formats every field with its <br><b>Label</b> : prefix so that empty
// fields collapse naturally in the tooltip (hovertemplate has no "if").
// Returns a 3-element array:
//   [0] FR-formatted date    [1] rounded value    [2] quality line (or '')
//
// Performance: this runs once per data point — 100k+ times on big stations.
// We avoid DateTime here (creating a DateTime object + calling format() is
// ~20x slower than string slicing) since MySQL always returns DATETIME in
// the fixed "YYYY-MM-DD HH:MM:SS" shape — we just rearrange the components.

function build_customdata($dateheure, $valeur, $nb_dec, $id_codequal, $code_qual_array)
{
    // "YYYY-MM-DD HH:MM:SS" → "DD-MM-YYYY HH:MM:SS" via substr (zero objects allocated)
    $date_formatee = substr($dateheure, 8, 2) . '-'
                   . substr($dateheure, 5, 2) . '-'
                   . substr($dateheure, 0, 4) . ' '
                   . substr($dateheure, 11, 8);

    $qual_init = isset($code_qual_array[$id_codequal])
        ? $code_qual_array[$id_codequal]['init_qualite_data']
        : '';
    // Inline emptiness check — avoids one function call per point.
    $field_qual = ($qual_init !== '' && $qual_init !== null)
        ? '<br><b>' . TEXT_GRAPH_HOVER_QUALCODE . '</b> : ' . $qual_init
        : '';

    return [
        $date_formatee,
        round((float)$valeur, $nb_dec),
        $field_qual,
    ];
}


// -----------------------------------------------
// Per-series gap-shading state machine
// Builds Plotly `shape` rectangles spanning consecutive null periods.
// Also accumulates customdata in $cd_arr for the native hovertemplate.

function process_point(&$x_arr, &$y_arr, &$cd_arr, &$nb, &$y_min, &$y_max, &$y_auto,
                       &$lac_first, &$lac_temp, &$shapes, $color,
                       $dateheure, $valeur, $id_codequal, $nb_dec, $code_qual_array,
                       $yref = 'y domain')
{
    $is_valid = ($valeur > VALUE_MIN_THRESHOLD && $valeur < VALUE_MAX_THRESHOLD);
 
    if ($is_valid)
    {
        $x_arr[]  = $dateheure;
        $y_arr[]  = $valeur;
        $cd_arr[] = build_customdata($dateheure, $valeur, $nb_dec, $id_codequal, $code_qual_array);
 
        if ($y_auto)
        {
            if ($y_min === null || $valeur < $y_min) { $y_min = $valeur; }
            if ($y_max === null || $valeur > $y_max) { $y_max = $valeur; }
        }
        else
        {
            if ($valeur < $y_min) { $y_min = $valeur; }
            if ($valeur > $y_max) { $y_max = $valeur; }
        }
        $nb++;
 
        // Close any open gap shape
        if ($lac_first !== '')
        {
            if ($shapes !== '') { $shapes .= ','; }
            $shapes .= $lac_temp
                     . " x1: '" . $dateheure . "',"
                     . " y1: 1, fillcolor: '" . $color . "', opacity: 0.15, line: { width: 0 } }";
            $lac_first = '';
            $lac_temp  = '';
        }
    }
    else
    {
        $x_arr[]  = $dateheure;
        $y_arr[]  = null;
        $cd_arr[] = ['', '', ''];   // placeholder so customdata stays aligned with x/y (3 fields: date/value/qual)
 
        // Open a new gap shape if one is not already open. yref targets the
        // series' own subplot (y domain = stage, y2 domain = discharge) so a
        // gap never spills onto the other chart.
        if ($lac_first === '')
        {
            $lac_temp  = "{ type:'rect', xref:'x', yref:'" . $yref . "', x0:'" . $dateheure . "', y0:0,";
            $lac_first = $dateheure;
        }
    }
}
 
// -----------------------------------------------
// Close any gap that is still open at the end of a series.
// Called after each data-fetching loop to handle the case where
// the series ends on invalid values (no valid point after the gap
// triggers the close in process_point).
 
function close_open_gap(&$lac_first, &$lac_temp, &$shapes, $color, $last_dateheure)
{
    if ($lac_first !== '' && $last_dateheure !== '')
    {
        if ($shapes !== '') { $shapes .= ','; }
        $shapes .= $lac_temp
                 . " x1: '" . $last_dateheure . "',"
                 . " y1: 1, fillcolor: '" . $color . "', opacity: 0.15, line: { width: 0 } }";
        $lac_first = '';
        $lac_temp  = '';
    }
}
 
// -----------------------------------------------
// Data extraction (only when a height series is selected and station is valid)
 
if ($typedata_chron_h > 0 && $station_chron > 0)
{
    $yaxis_nom      = $type_chron_array[$typedata_chron_h]['axe_nom']      ?? '';
    $yaxis_unite    = " (" . ($type_chron_array[$typedata_chron_h]['axe_unite'] ?? '') . " )";
    $yaxis_nb_round = (int)($type_chron_array[$typedata_chron_h]['axe_nb_round'] ?? 0);

    // -----------------------------------------------
    // Volume guard — count the H rows for the requested period BEFORE
    // loading them. If the count exceeds $limit_screen, we abort the
    // render and instead build a "too many rows" message with links that
    // reload convert_hq on smaller sub-periods.
    //
    // The COUNT is bounded by the user-selected period when both dates are
    // present; otherwise it spans the whole series (first-load case).
    $count_cond = '';
    if ($date_first instanceof DateTime && $date_end instanceof DateTime)
    {
        $df = $date_first->format('Y-m-d') . ' 00:00:00';
        $de = $date_end->format('Y-m-d')   . ' 23:59:59';
        $count_cond = " AND da.dateheure >= '" . mysqli_real_escape_string($sql_link, $df) . "'"
                    . " AND da.dateheure <= '" . mysqli_real_escape_string($sql_link, $de) . "'";
    }

    $sql_count_h = "SELECT COUNT(*) AS total
                    FROM " . TABLE_DATA_ALL . " da
                    JOIN " . TABLE_DATA_META . " dm ON da.id_meta = dm.id
                    WHERE dm.id_typedata = " . $typedata_chron_h . "
                      AND dm.id_station  = " . $station_chron
                    . $count_cond;
    $count_h_res  = tep_db_fetch_array(tep_db_query($sql_link, $sql_count_h));
    $total_rows_h = (int)($count_h_res['total'] ?? 0);

    if ($total_rows_h > $limit_screen)
    {
        $graph_load  = false;

        // Number of chunks needed (each ≤ ~90% of the threshold).
        $packet_size = (int) floor($limit_screen * 0.9);
        $nb_packets  = (int) ceil($total_rows_h / $packet_size);

        // Find each chunk's start date by jumping OFFSET rows into the
        // ordered series, in a single UNION ALL query (cheap: LIMIT 1
        // per chunk). Same technique as process_graph_multi.php.
        $union_parts = [];
        for ($p = 0; $p < $nb_packets; $p++)
        {
            $union_parts[] = "
                (SELECT da.dateheure AS dateheure, " . $p . " AS packet_num
                 FROM " . TABLE_DATA_ALL . " da
                 JOIN " . TABLE_DATA_META . " dm ON da.id_meta = dm.id
                 WHERE dm.id_typedata = " . $typedata_chron_h . "
                   AND dm.id_station  = " . $station_chron
                 . $count_cond . "
                 ORDER BY da.dateheure ASC
                 LIMIT 1 OFFSET " . ($p * $packet_size) . ")";
        }

        $packet_dates = [];
        if (!empty($union_parts))
        {
            $packets_query = tep_db_query($sql_link, implode(' UNION ALL ', $union_parts));
            while ($prow = tep_db_fetch_array($packets_query))
            {
                $packet_dates[(int)$prow['packet_num']] = $prow['dateheure'];
            }
        }

        // Build the chunk links. Each link reloads convert_hq.php (same
        // station + same H/Q series) on a shorter date range, passed via
        // GET so the page can pre-fill its date inputs.
        $links_html = '';
        for ($p = 0; $p < $nb_packets; $p++)
        {
            if (!isset($packet_dates[$p])) { continue; }

            $p_start_iso = substr($packet_dates[$p], 0, 10);

            if ($p < $nb_packets - 1 && isset($packet_dates[$p + 1]))
            {
                $p_end_dt = new DateTime(substr($packet_dates[$p + 1], 0, 10));
                $p_end_dt->modify('-1 day');
                $p_end_iso = $p_end_dt->format('Y-m-d');
            }
            else
            {
                $p_end_iso = ($date_end instanceof DateTime)
                    ? $date_end->format('Y-m-d')
                    : $p_start_iso;
            }

            $p_start_fr = dateus_fr($p_start_iso);
            $p_end_fr   = dateus_fr($p_end_iso);

            $q_start = rawurlencode($p_start_fr);
            $q_end   = rawurlencode($p_end_fr);
            $href = 'convert_hq.php?st=' . $station_chron
                  . '&th=' . $typedata_chron_h
                  . '&tq=' . $typedata_chron_q
                  . '&date_1=' . $q_start
                  . '&date_2=' . $q_end;

            $links_html .= "
                <hr>
                <a href='" . $href . "' target='_blank'
                   style='font-size:14px;color:#0066cc;text-decoration:underline;'>
                    \xF0\x9F\x93\xA6 " . TEXT_HQ_LOAD_PACKET . " " . ($p + 1) . " / " . $nb_packets . "
                    &nbsp;(" . $p_start_fr . " &rarr; " . $p_end_fr . ")
                </a>";
        }

        $msg_noLoad =
            "<p style='margin-top:24px;font-size:14px;'>"
            . "<span style='font-weight:bold;'>" . TEXT_HQ_TOO_MANY_ROWS . "</span>"
            . "<br><br>"
            . $total_rows_h . " " . TEXT_HQ_RECORDS
            . "<br><br>"
            . TEXT_HQ_SHORTER_PERIOD
            . "<br><br>"
            . "&mdash; " . TEXT_HQ_OR_LOAD_PACKET . " &mdash;"
            . $links_html
            . "</p>";
    }

    // ---- Water-level series ----
    // SELECT only the columns we actually use downstream:
    //   - da.dateheure, da.valeur : the point itself
    //   - dm.id_codequal          : looked up in $code_qual_array for the tooltip
    // We dropped id_station/id/id_typedata (unused) and obs/obs_user (no
    // longer shown in tooltips), which roughly halves the SQL → PHP transfer
    // size and the PHP memory footprint on big stations.
    // Only loaded when the volume guard didn't block ($graph_load === true).
    if ($graph_load)
    {
    $lac_first = ''; $lac_temp = '';
    $last_dateheure_h = '';
    $first_dateheure_h = '';
    $chron_h_query = tep_db_query($sql_link,
        "SELECT da.dateheure, da.valeur, dm.id_codequal
         FROM " . TABLE_DATA_ALL . " da
         JOIN " . TABLE_DATA_META . " dm ON da.id_meta = dm.id
         WHERE dm.id_typedata=" . $typedata_chron_h . "
         AND dm.id_station=" . $station_chron
        . $condition_date
        . " ORDER BY da.dateheure ASC");

    while ($chron_h_tab = tep_db_fetch_array($chron_h_query))
    {
        $valeur    = $chron_h_tab['valeur'];
        $dateheure = $chron_h_tab['dateheure'];
        if ($first_dateheure_h === '') { $first_dateheure_h = $dateheure; }
        $last_dateheure_h = $dateheure;

        process_point($x_h, $y_h, $cd_h, $nb_data_h, $y_min1, $y_max1, $y_h_auto,
                      $lac_first, $lac_temp, $edit_lacune_h, $color_h,
                      $dateheure, $valeur, $chron_h_tab['id_codequal'], $yaxis_nb_round, $code_qual_array,
                      'y domain');
    }
    close_open_gap($lac_first, $lac_temp, $edit_lacune_h, $color_h, $last_dateheure_h);

    // Derive the x-axis bounds from the actual data range (rows are
    // ORDER BY dateheure ASC, so first/last are correct without parsing).
    // Only when the user did not explicitly pin a range via the inputs.
    if (!$reload && $first_dateheure_h !== '')
    {
        $first_dt_obj = DateTime::createFromFormat('Y-m-d H:i:s', $first_dateheure_h);
        $last_dt_obj  = DateTime::createFromFormat('Y-m-d H:i:s', $last_dateheure_h);
        if ($first_dt_obj !== false && (!$date_first || $first_dt_obj < $date_first)) {
            $date_first = $first_dt_obj;
        }
        if ($last_dt_obj !== false && (!$date_end || $last_dt_obj > $date_end)) {
            $date_end = $last_dt_obj;
        }
    }
    elseif ($first_dateheure_h !== '')
    {
        // First-load case: just initialise if still unset.
        if (!$date_first) { $date_first = DateTime::createFromFormat('Y-m-d H:i:s', $first_dateheure_h); }
        if (!$date_end)   { $date_end   = DateTime::createFromFormat('Y-m-d H:i:s', $last_dateheure_h); }
    }
 
    // ---- Existing flow-rate series ----
    $lac_first = ''; $lac_temp = '';
    $last_dateheure_q = '';
    if ($typedata_chron_q > 0)
    {
        $yaxis2_nb_round = (int)($type_chron_array[$typedata_chron_q]['axe_nb_round'] ?? 0);

        $chron_q_query = tep_db_query($sql_link,
            "SELECT da.dateheure, da.valeur, dm.id_codequal
             FROM " . TABLE_DATA_ALL . " da
             JOIN " . TABLE_DATA_META . " dm ON da.id_meta = dm.id
             WHERE dm.id_typedata=" . $typedata_chron_q . "
             AND dm.id_station=" . $station_chron
            . $condition_date
            . " ORDER BY da.dateheure ASC");

        while ($chron_q_tab = tep_db_fetch_array($chron_q_query))
        {
            $last_dateheure_q = $chron_q_tab['dateheure'];
            process_point($x_q, $y_q, $cd_q, $nb_data_q, $y_min2, $y_max2, $y_q_auto,
                          $lac_first, $lac_temp, $edit_lacune_q, $color_q,
                          $chron_q_tab['dateheure'], $chron_q_tab['valeur'],
                          $chron_q_tab['id_codequal'], $yaxis2_nb_round, $code_qual_array,
                          'y2 domain');
        }
        close_open_gap($lac_first, $lac_temp, $edit_lacune_q, $color_q, $last_dateheure_q);
 
        $yaxis2_nom   = $type_chron_array[$typedata_chron_q]['axe_nom']   ?? '';
        $yaxis2_unite = " (" . ($type_chron_array[$typedata_chron_q]['axe_unite'] ?? '') . " )";
 
        // ---- Pending conversion preview series ----
        $chron_q_n_query = tep_db_query($sql_link,
            "SELECT da.dateheure, da.valeur, dm.id_codequal
             FROM " . TABLE_DATA_ALL_CORRECTION . " da
             JOIN " . TABLE_DATA_META_CORRECTION . " dm ON da.id_meta = dm.id
             WHERE dm.id_typedata=" . $typedata_chron_q . "
             AND dm.id_station=" . $station_chron . "
             AND dm.source='Conversion'"
            . $condition_date
            . " ORDER BY da.dateheure ASC");

        while ($chron_q_n_tab = tep_db_fetch_array($chron_q_n_query))
        {
            $valeur    = $chron_q_n_tab['valeur'];
            $dateheure = $chron_q_n_tab['dateheure'];

            if ($valeur > VALUE_MIN_THRESHOLD && $valeur < VALUE_MAX_THRESHOLD)
            {
                $x_q_n[]  = $dateheure;
                $y_q_n[]  = $valeur;
                $cd_q_n[] = build_customdata($dateheure, $valeur, $yaxis2_nb_round,
                                             $chron_q_n_tab['id_codequal'], $code_qual_array);

                if ($y_q_auto)
                {
                    if ($y_min2 === null || $valeur < $y_min2) { $y_min2 = $valeur; }
                    if ($y_max2 === null || $valeur > $y_max2) { $y_max2 = $valeur; }
                }
                else
                {
                    if ($valeur < $y_min2) { $y_min2 = $valeur; }
                    if ($valeur > $y_max2) { $y_max2 = $valeur; }
                }
                $nb_data_q_n++;
            }
            else
            {
                $x_q_n[]  = $dateheure;
                $y_q_n[]  = null;
                $cd_q_n[] = ['', '', ''];   // placeholder (3 fields: date/value/qual)
            }
        }
    }
    } // end if ($graph_load)
}
 
 
// -----------------------------------------------
// Fallback y-ranges when no data was loaded
 
if ($y_min1 === null) { $y_min1 = 0; }
if ($y_max1 === null) { $y_max1 = 1; }
if ($y_min2 === null) { $y_min2 = 0; }
if ($y_max2 === null) { $y_max2 = 1; }
 

// -----------------------------------------------
// Volume guard short-circuit: if the H series was too large, skip the
// whole trace-building step and return the "too many rows" payload now.
// The front (convert_hq.php) reads graph_load === false and shows the
// message + chunk links instead of a chart.
if (!$graph_load)
{
    echo json_encode([
        'traces'      => [],
        'layout'      => null,
        'config'      => null,
        'js_text_post' => '',
        'nb_data_h'   => 0,
        'nb_data_all' => 0,
        'graph_load'  => false,
        'total_rows'  => $total_rows_h,
        'msg_noLoad'  => $msg_noLoad,
    ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    exit;
}

 
// -----------------------------------------------
// Build Plotly trace JSON via json_encode (safe against quotes / </script> in labels)
 
$ht_init     = $type_chron_array[$typedata_chron_h]['init_type_data'] ?? '';
$ht_nom      = $type_chron_array[$typedata_chron_h]['nom_type_data']  ?? '';
$ht_axe      = $type_chron_array[$typedata_chron_h]['axe_nom']        ?? '';
$ht_unit     = $type_chron_array[$typedata_chron_h]['axe_unite']      ?? '';
$ht_nb_round = $type_chron_array[$typedata_chron_h]['axe_nb_round']   ?? '0';
 
$qt_init     = $type_chron_array[$typedata_chron_q]['init_type_data'] ?? '';
$qt_nom      = $type_chron_array[$typedata_chron_q]['nom_type_data']  ?? '';
$qt_axe      = $type_chron_array[$typedata_chron_q]['axe_nom']        ?? '';
$qt_unit     = $type_chron_array[$typedata_chron_q]['axe_unite']      ?? '';
$qt_nb_round = $type_chron_array[$typedata_chron_q]['axe_nb_round']   ?? '0';
 
$JSON_FLAGS = JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;


// -----------------------------------------------
// Helper — build the hovertemplate string for one series.
// Title is wrapped in <span style="color:..."> so each box appears with
// its series color in unified hover mode. <extra></extra> hides the
// duplicate side label that Plotly adds by default.

function build_hovertemplate($trace_title, $trace_color, $trace_axe, $unite)
{
    return "<b><span style=\"color:" . $trace_color . "\">" . $trace_title . "</span></b>"
         . "<br><b>" . TEXT_GRAPH_HOVER_DATE . "</b> : %{customdata[0]}"
         . "<br><b>" . $trace_axe . "</b> : %{customdata[1]} " . $unite
         . "%{customdata[2]}"
         . "<extra></extra>";
}


// -----------------------------------------------
// Traces — built as a plain PHP array of trace objects, then sent as
// JSON directly. The client passes this straight to Plotly.newPlot(),
// avoiding the cost of generating + eval()ing a JavaScript string for
// each trace (which scaled badly past ~50k points).
//
// All trace types are switched from 'scatter' (SVG renderer) to
// 'scattergl' (WebGL renderer). On hydro chronologies of 100k+ points
// this turns a multi-second freeze into an instantaneous render —
// the SVG path was generating one DOM element per point.

$traces_array = [];

if ($nb_data_h > 0)
{
    $title_h = $ht_init . ' - ' . $ht_nom;
    $traces_array[] = [
        'x'             => $x_h,
        'y'             => $y_h,
        'customdata'    => $cd_h,
        'name'          => $title_h,
        'yaxis'         => 'y',
        'legendgroup'   => 'tdc_h',
        'hovertemplate' => build_hovertemplate($title_h, $color_h, $ht_axe, $ht_unit),
        'hoverlabel'    => [
            'bgcolor'     => '#ffffff',
            'bordercolor' => $color_h,
            'font'        => ['color' => '#222222', 'size' => 10, 'family' => 'roboto, arial, helvetica'],
            'align'       => 'left',
        ],
        'mode'          => 'lines',
        'line'          => ['color' => $color_h],
        'type'          => 'scattergl',
    ];
}

if ($nb_data_q > 0)
{
    $title_q = $qt_init . ' - ' . $qt_nom;
    $traces_array[] = [
        'x'             => $x_q,
        'y'             => $y_q,
        'customdata'    => $cd_q,
        'name'          => $title_q,
        'xaxis'         => 'x2',
        'yaxis'         => 'y2',
        'legendgroup'   => 'tdc_q',
        'hovertemplate' => build_hovertemplate($title_q, $color_q, $qt_axe, $qt_unit),
        'hoverlabel'    => [
            'bgcolor'     => '#ffffff',
            'bordercolor' => $color_q,
            'font'        => ['color' => '#222222', 'size' => 10, 'family' => 'roboto, arial, helvetica'],
            'align'       => 'left',
        ],
        'mode'          => 'lines',
        'line'          => ['color' => $color_q],
        'type'          => 'scattergl',
    ];
}

if ($nb_data_q_n > 0)
{
    $pending_suffix = defined('TEXT_HQ_TRACE_PENDING') ? TEXT_HQ_TRACE_PENDING : '(pending)';
    $title_q_n = $qt_init . ' ' . $pending_suffix;
    $traces_array[] = [
        'x'             => $x_q_n,
        'y'             => $y_q_n,
        'customdata'    => $cd_q_n,
        'name'          => $title_q_n,
        'xaxis'         => 'x2',
        'yaxis'         => 'y2',
        'legendgroup'   => 'tdc_pending',
        'hovertemplate' => build_hovertemplate($title_q_n, $color_qcal, $qt_axe, $qt_unit),
        'hoverlabel'    => [
            'bgcolor'     => '#ffffff',
            'bordercolor' => $color_qcal,
            'font'        => ['color' => '#222222', 'size' => 12, 'family' => 'roboto, arial, helvetica'],
            'align'       => 'left',
        ],
        'mode'          => 'lines',
        'line'          => ['color' => $color_qcal],
        'type'          => 'scattergl',
    ];
}

// Append the ETL timeline hover/click trace (always present; empty if no ETL).
// Defined further below, so we add it after the ETL build section.
 
 
// -----------------------------------------------
// ETL coverage timeline (3rd subplot, axis y3 / shared x)
// -----------------------------------------------
// Rebuilt natively in Plotly (was a separate hand-made SVG). Each rating
// curve (ETL) is drawn as a rounded rectangle shape spanning its period,
// on the thin bottom subplot. Uncovered periods get a red hatch fill.
// A single invisible marker trace (one point at each bar centre) carries
// the hover tooltip and the click-to-open-modif_etl behaviour.

$etl_bars       = [];   // [{ts_f, ts_e, id, h_min, h_max, nb, date_f, date_e}]
$etl_gaps       = [];   // [{ts_f, ts_e}]

$etl_ts_min = ($date_first instanceof DateTime) ? $date_first->getTimestamp() : strtotime('1950-01-01');
$etl_ts_max = ($date_end   instanceof DateTime) ? $date_end->getTimestamp()   : time();

if ($station_chron > 0)
{
    $etl_d1 = date('Y-m-d', $etl_ts_min);
    $etl_d2 = date('Y-m-d', $etl_ts_max);

    $etl_q = tep_db_query($sql_link,
        "SELECT etl.id, etl.datetime_first, etl.datetime_end,
                MIN(ed.hauteur) AS h_min, MAX(ed.hauteur) AS h_max,
                COUNT(ed.id)    AS nb_jaugeages
         FROM " . TABLE_DATA_ETL . " etl
         LEFT JOIN " . TABLE_DATA_ETL_DATA . " ed ON ed.id_etl = etl.id
         WHERE etl.id_station = " . (int)$station_chron . "
         AND etl.datetime_first <= '$etl_d2 23:59:59'
         AND etl.datetime_end   >= '$etl_d1 00:00:00'
         GROUP BY etl.id, etl.datetime_first, etl.datetime_end
         ORDER BY etl.datetime_first ASC");

    while ($e = tep_db_fetch_array($etl_q))
    {
        $ts_f = max($etl_ts_min, strtotime($e['datetime_first']));
        $ts_e = min($etl_ts_max, strtotime($e['datetime_end']));
        if ($ts_e <= $ts_f) { continue; }

        $etl_bars[] = [
            'ts_f'   => $ts_f,
            'ts_e'   => $ts_e,
            'id'     => (int)$e['id'],
            'h_min'  => $e['h_min'],
            'h_max'  => $e['h_max'],
            'nb'     => (int)$e['nb_jaugeages'],
            'date_f' => $e['datetime_first'],
            'date_e' => $e['datetime_end'],
        ];
    }

    // Uncovered gaps (before / between / after covered segments)
    $cursor = $etl_ts_min;
    foreach ($etl_bars as $b)
    {
        if ($b['ts_f'] > $cursor) { $etl_gaps[] = ['ts_f' => $cursor, 'ts_e' => $b['ts_f']]; }
        $cursor = max($cursor, $b['ts_e']);
    }
    if ($cursor < $etl_ts_max) { $etl_gaps[] = ['ts_f' => $cursor, 'ts_e' => $etl_ts_max]; }
}

// Helper: integer if whole, else max 1 decimal
function hq_fmt_h($v)
{
    $f = (float)$v;
    if (floor($f) == $f) { return (string)(int)$f; }
    return rtrim(rtrim(number_format($f, 1, '.', ''), '0'), '.');
}

// Build the timeline shapes (rectangles) on x / y3. y3 runs 0..1 within its
// own small domain, so bars occupy a fixed vertical band.
$etl_shapes_arr = [];
$etl_colors     = ['#9B7EBD', '#7B5BA3'];
$bar_y0 = 0.20;
$bar_y1 = 0.85;

// Gap hatches first (so bars overlay cleanly)
foreach ($etl_gaps as $g)
{
    $etl_shapes_arr[] = [
        'type' => 'rect', 'xref' => 'x', 'yref' => 'y3',
        'x0' => date('Y-m-d H:i:s', $g['ts_f']),
        'x1' => date('Y-m-d H:i:s', $g['ts_e']),
        'y0' => $bar_y0, 'y1' => $bar_y1,
        'fillcolor' => 'rgba(200,80,80,0.18)',
        'line' => ['color' => 'rgba(200,80,80,0.45)', 'width' => 0.5],
        'layer' => 'below',
    ];
}

// Covered ETL bars + invisible hover/click points
$etl_pts_x = [];
$etl_pts_y = [];
$etl_pts_cd = [];   // [date_f_fr, date_e_fr, "hmin-hmax", nb, id]
$etl_annot = [];

// No rating curve at all on the visible range → show a centered note in the
// timeline subplot so the empty band reads as "intentionally empty" rather
// than "still loading".
if (empty($etl_bars))
{
    $etl_annot[] = [
        'xref' => 'x domain', 'yref' => 'y3 domain',
        'x' => 0.5, 'y' => 0.5,
        'text' => defined('TEXT_HQ_ETL_NO_RC') ? TEXT_HQ_ETL_NO_RC : 'No defined RC',
        'showarrow' => false,
        'font' => ['family' => 'roboto, arial, helvetica', 'size' => 11, 'color' => '#A32D2D'],
        'xanchor' => 'center', 'yanchor' => 'middle',
    ];
}

$idx = 0;
foreach ($etl_bars as $b)
{
    $col = $etl_colors[$idx % 2];
    $idx++;

    $etl_shapes_arr[] = [
        'type' => 'rect', 'xref' => 'x', 'yref' => 'y3',
        'x0' => date('Y-m-d H:i:s', $b['ts_f']),
        'x1' => date('Y-m-d H:i:s', $b['ts_e']),
        'y0' => $bar_y0, 'y1' => $bar_y1,
        'fillcolor' => $col,
        'line' => ['width' => 0],
        'layer' => 'above',
    ];

    $h_lab = hq_fmt_h($b['h_min']) . ' - ' . hq_fmt_h($b['h_max']) . ' cm';

    // Hover + click are carried by invisible marker points (Plotly shapes
    // aren't clickable). A single centre point only makes the centre of the
    // bar interactive. To make the WHOLE covered width respond, we seed a row
    // of points evenly spread from ts_f to ts_e — all sharing the same
    // customdata, so the tooltip and the click target (ETL id) are identical
    // wherever the user clicks along the bar.
    $date_f_fr = date('d/m/Y', strtotime($b['date_f']));
    $date_e_fr = date('d/m/Y', strtotime($b['date_e']));
    $cd_row    = [$date_f_fr, $date_e_fr, $h_lab, $b['nb'], $b['id']];

    $span = max(1, $b['ts_e'] - $b['ts_f']);
    // ~1 point every 10 days, clamped to a sane range so short bars still get
    // a couple of points and very long bars don't explode the trace.
    $n_pts = (int) round($span / (10 * 86400));
    if ($n_pts < 2)   { $n_pts = 2; }
    if ($n_pts > 400) { $n_pts = 400; }

    for ($k = 0; $k <= $n_pts; $k++)
    {
        $ts_k = (int)($b['ts_f'] + $span * $k / $n_pts);
        $etl_pts_x[]  = date('Y-m-d H:i:s', $ts_k);
        $etl_pts_y[]  = ($bar_y0 + $bar_y1) / 2;
        $etl_pts_cd[] = $cd_row;
    }

    // No in-bar text label: full info is shown in the hover tooltip.
}

// Invisible marker trace carrying ETL hover + click (meta tags it). Uses a
// native hovertemplate so the tooltip shows up reliably.
$etl_hover_tpl =
    "<b><span style=\"color:#4a3270\">" . TEXT_HQ_ETL_TOOLTIP_CURVE . "</span></b>"
    . "<br><b>" . TEXT_HQ_ETL_TOOLTIP_PERIOD . "</b> : %{customdata[0]} → %{customdata[1]}"
    . "<br><b>" . TEXT_HQ_ETL_RANGE_PREFIX   . "</b> : %{customdata[2]}"
    . "<br><b>" . TEXT_GAUGING . "</b> : %{customdata[3]}"
    . "<br><i style=\"color:#888;font-size:10px\">" . TEXT_HQ_ETL_TOOLTIP_HINT . "</i>"
    . "<extra></extra>";

$etl_trace_obj = [
    'x'             => $etl_pts_x,
    'y'             => $etl_pts_y,
    'customdata'    => $etl_pts_cd,
    'xaxis'         => 'x',
    'yaxis'         => 'y3',
    'mode'          => 'markers',
    'type'          => 'scatter',
    'meta'          => 'etlBar',
    'marker'        => ['size' => 18, 'color' => 'rgba(0,0,0,0)', 'symbol' => 'square'],
    'hovertemplate' => $etl_hover_tpl,
    'hoverlabel'    => [
        'bgcolor'     => '#ffffff',
        'bordercolor' => '#7B5BA3',
        'font'        => ['color' => '#222222', 'size' => 11, 'family' => 'Open Sans, Arial, sans-serif'],
        'align'       => 'left',
    ],
    'showlegend'    => false,
];

// Now that it exists, append it to the traces sent to Plotly.
$traces_array[] = $etl_trace_obj;


// -----------------------------------------------
// -----------------------------------------------
// Gap shading (shapes) — exposed as JS arrays so the front-end
// can toggle them via Plotly.relayout without reloading data.
//
// Kept as JS-literal strings (rather than PHP arrays) because each
// shape is already built piecewise inside process_point() / close_open_gap(),
// and gaps are sparse (only a handful per series typically), so the
// JS-eval cost is negligible compared to the 100k+ data points.

$shapes_h_js = ($edit_lacune_h !== '') ? "[{$edit_lacune_h}]" : "[]";
$shapes_q_js = ($edit_lacune_q !== '') ? "[{$edit_lacune_q}]" : "[]";

$expose_shapes = "
    window.gapShapesH = {$shapes_h_js};
    window.gapShapesQ = {$shapes_q_js};
";


// -----------------------------------------------
// Plotly layout — built as a PHP array, then sent as JSON. The client
// passes it straight to Plotly.newPlot() (no eval() of a JS string).
//
// Spikelines:
//   - showspikes on xaxis  → vertical dotted line follows the cursor
//   - showspikes on yaxis  → horizontal dotted line follows the cursor
//   - together they form a crosshair viseur at the hovered point
//
// Hovermode:
//   - 'x unified' shows all series sharing the same x at once, each in
//     its own colored hover-box (color comes from hoverlabel.bordercolor)

$date_first_str  = ($date_first instanceof DateTime) ? $date_first->format('Y-m-d') : '';
$date_end_str    = ($date_end   instanceof DateTime) ? $date_end->format('Y-m-d')   : '';
$y_max1_with_pad = $y_max1 * 1.1;
$y_max2_with_pad = $y_max2 * 1.1;

$axis_title_font = ['family' => 'roboto, arial, helvetica', 'size' => 14, 'color' => '#000'];
$tick_font_small = ['size' => 11];

$layout_obj = [
    // ---- Stage subplot (top), axis x / y ----
    'xaxis' => [
        'autorange'  => false,
        'range'      => [$date_first_str, $date_end_str],
        'domain'     => [0.0, 1.0],
        'anchor'     => 'y',
        'matches'    => 'x3',          // share zoom/pan with the other subplots
        'showticklabels' => true,      // dates shown on the stage axis too
        'tickfont'   => $tick_font_small,
        'tickangle'  => 0, 'ticklen' => 4, 'showline' => true, 'linewidth' => 1,
        'showspikes' => true, 'spikemode' => 'across', 'spikedash' => 'dot',
        'spikecolor' => '#000', 'spikethickness' => 1,
        'hoverformat' => '%d-%m-%Y %H:%M:%S',
    ],
    'yaxis' => [
        'title'      => ['text' => $yaxis_nom . $yaxis_unite, 'standoff' => 10],
        'autorange'  => false,
        'range'      => [$y_min1, $y_max1_with_pad],
        'domain'     => [0.58, 1.0],
        'anchor'     => 'x',
        'tickfont'   => $tick_font_small,
        'titlefont'  => $axis_title_font,
        'tickformat' => '.0f', 'ticklen' => 5, 'showline' => true, 'linewidth' => 1, 'automargin' => true,
        'showspikes' => true, 'spikemode' => 'across', 'spikedash' => 'dot',
        'spikesnap'  => 'data', 'spikecolor' => '#000', 'spikethickness' => 1,
    ],

    // ---- Discharge subplot (middle), axis x2 / y2 ----
    'xaxis2' => [
        'autorange'  => false,
        'range'      => [$date_first_str, $date_end_str],
        'domain'     => [0.0, 1.0],
        'anchor'     => 'y2',
        'matches'    => 'x3',
        'showticklabels' => true,      // dates shown on the discharge axis too
        'tickfont'   => $tick_font_small,
        'tickangle'  => 0,
        'ticklen' => 4, 'showline' => true, 'linewidth' => 1,
        'showspikes' => true, 'spikemode' => 'across', 'spikedash' => 'dot',
        'spikecolor' => '#000', 'spikethickness' => 1,
        'hoverformat' => '%d-%m-%Y %H:%M:%S',
    ],
    'yaxis2' => [
        'title'      => ['text' => $yaxis2_nom . $yaxis2_unite, 'standoff' => 10],
        'autorange'  => false,
        'range'      => [$y_min2, $y_max2_with_pad],
        'domain'     => [0.16, 0.54],
        'anchor'     => 'x2',
        'tickfont'   => $tick_font_small,
        'titlefont'  => $axis_title_font,
        'tickformat' => '.0f', 'ticklen' => 5, 'showline' => true, 'linewidth' => 1, 'automargin' => true,
        'showspikes' => true, 'spikemode' => 'across', 'spikedash' => 'dot',
        'spikesnap'  => 'data', 'spikecolor' => '#000', 'spikethickness' => 1,
    ],

    // ---- ETL coverage timeline (bottom, thin), axis x3 / y3 ----
    'xaxis3' => [
        'title'      => ['text' => 'Date', 'standoff' => 5],
        'autorange'  => false,
        'range'      => [$date_first_str, $date_end_str],
        'domain'     => [0.0, 1.0],
        'anchor'     => 'y3',
        'tickfont'   => $tick_font_small,
        'titlefont'  => $axis_title_font,
        'tickangle'  => 0, 'ticklen' => 5, 'showline' => true, 'linewidth' => 1, 'automargin' => true,
        'showgrid'   => false,
        'hoverformat' => '%d-%m-%Y',
    ],
    'yaxis3' => [
        'title'      => ['text' => defined('TEXT_HQ_ETL_COVERAGE_TITLE') ? TEXT_HQ_ETL_COVERAGE_TITLE : 'Rating curve', 'standoff' => 8],
        'autorange'  => false,
        'range'      => [0, 1],
        'domain'     => [0.0, 0.10],
        'anchor'     => 'x3',
        'showticklabels' => false,
        'showgrid'   => false,
        'zeroline'   => false,
        'fixedrange' => true,
        'titlefont'  => ['family' => 'roboto, arial, helvetica', 'size' => 11, 'color' => '#000'],
    ],

    'shapes'        => $etl_shapes_arr,
    'annotations'   => $etl_annot,
    'autosize'      => true,
    'selectdirection' => 'h',
    'hovermode'     => 'x unified',
    'hoverdistance' => 50,
    'margin'        => ['l' => 55, 'r' => 15, 't' => 50, 'b' => 40],
    'showlegend'    => true,
    'legend'        => ['x' => 0, 'y' => 1.08, 'orientation' => 'h'],
];


// -----------------------------------------------
// Plotly config — array form. The "Export SVG" custom button has a
// JS-function as `click`, which can't be serialised through JSON — it's
// stitched back on by the client (see convert_hq.php's load_graph()).

$config_obj = [
    'responsive'              => true,
    'doubleClickDelay'        => 1000,
    'displaylogo'             => false,
    'displayModeBar'          => true,
    'scrollZoom'              => true,
    'modeBarOrientation'      => 'v',
    'modeBarButtonsToRemove'  => ['lasso2d', 'autoScale2d', 'zoomIn2d', 'zoomOut2d'],
    // 'modeBarButtons' is attached client-side because one entry is a JS function.
];


// -----------------------------------------------
// Post-render JS — interactions that genuinely need eval()ed code:
//   - the zoom-relayout listener (uses xDateMin/xDateMax inputs)
//   - the double-click listener
//   - the gap-shape exposure on window
//
// This block stays small (a few hundred bytes) regardless of dataset size,
// so its eval() cost is constant and negligible.

$dfStr = ($date_first instanceof DateTime) ? $date_first->format('d-m-Y') : '';
$deStr = ($date_end   instanceof DateTime) ? $date_end->format('d-m-Y')   : '';

// Post-render JS. IMPORTANT: zoom/pan must NEVER write the Period Select
// fields — only a Shift selection (or manual typing) defines the period.
// So this block only PREFILLS the fields with the full default range; the
// selection logic and double-click handling live in convert_hq.php.
$textGraphFonction = "
    if (typeof xDateMin !== 'undefined' && xDateMin && !xDateMin.value) { xDateMin.value = '{$dfStr}'; }
    if (typeof xDateMax !== 'undefined' && xDateMax && !xDateMax.value) { xDateMax.value = '{$deStr}'; }
";


// -----------------------------------------------
// Final response — structured JSON (no JS code to eval for the heavy
// data payload; only the small zoom-sync block needs eval()).

echo json_encode([
    'traces'      => $traces_array,
    'layout'      => $layout_obj,
    'config'      => $config_obj,
    'js_text_post' => $expose_shapes . $textGraphFonction,
    'nb_data_h'   => $nb_data_h,
    'nb_data_all' => $nb_data_h, // legacy alias
    'graph_load'  => $graph_load,
    'total_rows'  => $total_rows_h,
    'msg_noLoad'  => $msg_noLoad,
], $JSON_FLAGS);
?>