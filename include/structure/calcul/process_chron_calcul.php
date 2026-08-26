<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Correction calculation handler - AJAX server-side process
- Builds new correction series from an existing one
- Supports: linear function (aY+b), time offset, smoothing,
            gap insertion, time-step resampling, period aggregation
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

$dataCalcul              = json_decode(file_get_contents('php://input'), true);
$id_user                 = $dataCalcul['id_user'];
$id_correction           = $dataCalcul['id_correction'];
$id_station              = $dataCalcul['id_station'];
$id_chron                = $dataCalcul['id_chron'];
$type_correction         = $dataCalcul['type_correction'];
$calcul_correction       = $dataCalcul['calcul_correction'];
$axe_correction          = $dataCalcul['axe_correction'];
$pastemps                = $dataCalcul['pastemps'];
$modecalcul              = $dataCalcul['modecalcul'];
$to_periode_encours      = $dataCalcul['to_periode_encours'];
$id_create_chron_encours = $dataCalcul['id_create_chron_encours'];

// Removed datetimes (type_correction == 'suppression' only), FR-formatted
// 'dd-mm-yyyy hh:mm:ss' like datetime_first/end. Converted to US below.
$deleted_points = (isset($dataCalcul['deleted_points']) && is_array($dataCalcul['deleted_points']))
                ? $dataCalcul['deleted_points'] : [];

$datetime_first          = $dataCalcul['datetime_first'];
$datetime_first_tab      = explode(' ', $datetime_first);
$datetime_first_formated = datefr_us($datetime_first_tab[0]) . ' ' . $datetime_first_tab[1];

$datetime_end            = $dataCalcul['datetime_end'];
$datetime_end_tab        = explode(' ', $datetime_end);
$datetime_end_formated   = datefr_us($datetime_end_tab[0]) . ' ' . $datetime_end_tab[1];

// Fenêtre figée au chargement de la page (bornes d'origine, avant tout
// zoom/pan/édition). Utilisée UNIQUEMENT pour l'en-tête du bloc de
// correction (datetime_first/end de TABLE_DATA_CORRECTION), afin que la
// copie du socle à la validation porte sur la fenêtre du chargement et
// non sur la sous-période réellement corrigée. Repli sur les dates de la
// correction si l'appelant ne fournit pas ces champs (rétro-compat).
$page_window_first = isset($dataCalcul['page_window_first']) && $dataCalcul['page_window_first'] !== ''
                   ? $dataCalcul['page_window_first'] : $datetime_first;
$page_window_first_tab      = explode(' ', $page_window_first);
$page_window_first_formated = datefr_us($page_window_first_tab[0]) . ' ' . ($page_window_first_tab[1] ?? '00:00:00');

$page_window_last = isset($dataCalcul['page_window_last']) && $dataCalcul['page_window_last'] !== ''
                  ? $dataCalcul['page_window_last'] : $datetime_end;
$page_window_last_tab      = explode(' ', $page_window_last);
$page_window_last_formated = datefr_us($page_window_last_tab[0]) . ' ' . ($page_window_last_tab[1] ?? '23:59:59');

// Time-step unit for calcul_pastemps. The unified front-end form sends
// 'min' / 'h' / 'd' / 'm' / 'y'. Older callers may not send 'unite' at
// all — in that case we fall back to 'min' to preserve the previous
// behaviour (interval expressed in minutes).
$unite_pastemps = isset($dataCalcul['unite']) && $dataCalcul['unite'] !== ''
                  ? $dataCalcul['unite']
                  : 'min';

$datetime_now_us   = date('Y-m-d H:i:s');
$datetime_now_fr   = date('d-m-Y H:i:s');
$min_y             = 0;
$max_y             = 0;
$msg_newCorrection = '';


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
// Query: Series types lookup table

$type_chron_query = tep_db_query($sql_link,
    "SELECT DISTINCT id_data_type, init_type_data, nom_type_data, id_eq_type_data, axe_data, unite, to_periode, id_chon_periode
     FROM " . TABLE_TYPE_DATA . " ORDER BY init_type_data ASC");
while ($type_chron_tab = tep_db_fetch_array($type_chron_query))
{
    $axe_nom = isset($data_type_axe_array[$type_chron_tab['axe_data']]['axe'])
               ? $data_type_axe_array[$type_chron_tab['axe_data']]['axe'] : '';

    $type_chron_array[$type_chron_tab['id_data_type']] = [
        'init_type_data'  => $type_chron_tab['init_type_data'],
        'nom_type_data'   => $type_chron_tab['nom_type_data'],
        'id_eq_type_data' => $type_chron_tab['id_eq_type_data'],
        'axe_nom'         => $axe_nom,
        'unite'           => $type_chron_tab['unite'],
        'to_periode'      => $type_chron_tab['to_periode'],
        'id_chon_periode' => $type_chron_tab['id_chon_periode'],
    ];
}


// -----------------------------------------------
// Create correction header record if first run

if ($id_correction == 0)
{
    // L'en-tête du bloc fige la fenêtre du CHARGEMENT de la page
    // (page_window_*), pas la sous-période corrigée. C'est cette fenêtre
    // qui sera copiée comme socle à la validation (cible <> source).
    tep_db_query($sql_link,
        "INSERT INTO " . TABLE_DATA_CORRECTION
        . " (id_user, datetime_correction, id_station, id_chron_init, datetime_first, datetime_end)"
        . " VALUES (" . $id_user . ", '" . $datetime_now_us . "', " . $id_station . ", " . $id_chron
        . ", '" . $page_window_first_formated . "', '" . $page_window_last_formated . "')");
    $id_correction = mysqli_insert_id($sql_link);
}


// -----------------------------------------------
// Build SQL correction parameters from type

$source_info       = 'Correction';
$sql_calcul        = 'da.valeur';
$sql_date_decalage = 'da.dateheure';
$info_correction   = '';

if ($type_correction == 'calcul')
{
    $sql_calcul      = str_replace('Y', '*da.valeur', $calcul_correction);
    $info_correction = $calcul_correction;
}
if ($type_correction == 'decalage_date')
{
    $sql_date_decalage = "DATE_ADD(da.dateheure, INTERVAL " . $calcul_correction . " SECOND)";
    // "%s second(s)" — %s is the offset value (may be signed, e.g. -3600).
    $tpl_offset = defined('TEXT_CORRECT_INFO_OFFSET') ? TEXT_CORRECT_INFO_OFFSET : '%s second(s)';
    $info_correction = sprintf($tpl_offset, $calcul_correction);
}
if ($type_correction == 'lissage')
{
    // "Smoothing - threshold: %s %%" — %s is the threshold percentage.
    $tpl_smooth = defined('TEXT_CORRECT_INFO_SMOOTHING') ? TEXT_CORRECT_INFO_SMOOTHING : 'Smoothing - threshold: %s %%';
    $info_correction = sprintf($tpl_smooth, $calcul_correction);
}
if ($type_correction == 'lacune')
{
    $info_correction = defined('TEXT_CORRECT_INFO_GAP') ? TEXT_CORRECT_INFO_GAP : 'Gap';
}
if ($type_correction == 'suppression')
{
    $info_correction = sprintf(TEXT_CORRECT_INFO_DELETE, count($deleted_points));
}

// Unified label for the time-step branch.
//
// Since the front-end was merged into a single form, calcul_pastemps and
// calcul_chron_new now route through the same backend branch and produce
// the same kind of correction. The label must reflect the actual unit
// chosen by the user (min/h/day/month/year), not a hard-coded "min".
//
// TEXT_CORRECT_INFO_TIMESTEP is a sprintf template like:
//   "Nouvelle chron. <br>pas de temps (%s) : %s %s"
// taking, in order: calc mode (moy/cumul), interval value, unit label.
if ($type_correction == 'calcul_pastemps' || $type_correction == 'calcul_chron_new')
{
    // Localized human-readable label for the unit code received from the
    // front. Singular vs plural is decided from $pastemps.
    //
    // Each unit has two language constants:
    //   TEXT_CORRECT_UNIT_<X>         (singular, e.g. "day", "jour")
    //   TEXT_CORRECT_UNIT_<X>_PLURAL  (plural,   e.g. "days", "jours")
    //
    // Falls back to the singular form if the plural constant is missing
    // (e.g. for languages where they're identical or new units added
    // without updating all translation files).
    $pastemps_int = (int)$pastemps;
    $is_plural    = ($pastemps_int > 1);

    $unit_singular = [
        'min' => defined('TEXT_CORRECT_UNIT_MIN')   ? TEXT_CORRECT_UNIT_MIN   : 'min',
        'h'   => defined('TEXT_CORRECT_UNIT_HOUR')  ? TEXT_CORRECT_UNIT_HOUR  : 'h',
        'd'   => defined('TEXT_CORRECT_UNIT_DAY')   ? TEXT_CORRECT_UNIT_DAY   : 'day',
        'm'   => defined('TEXT_CORRECT_UNIT_MONTH') ? TEXT_CORRECT_UNIT_MONTH : 'month',
        'y'   => defined('TEXT_CORRECT_UNIT_YEAR')  ? TEXT_CORRECT_UNIT_YEAR  : 'year',
    ];
    $unit_plural = [
        'min' => defined('TEXT_CORRECT_UNIT_MIN_PLURAL')   ? TEXT_CORRECT_UNIT_MIN_PLURAL   : $unit_singular['min'],
        'h'   => defined('TEXT_CORRECT_UNIT_HOUR_PLURAL')  ? TEXT_CORRECT_UNIT_HOUR_PLURAL  : $unit_singular['h'],
        'd'   => defined('TEXT_CORRECT_UNIT_DAY_PLURAL')   ? TEXT_CORRECT_UNIT_DAY_PLURAL   : $unit_singular['d'],
        'm'   => defined('TEXT_CORRECT_UNIT_MONTH_PLURAL') ? TEXT_CORRECT_UNIT_MONTH_PLURAL : $unit_singular['m'],
        'y'   => defined('TEXT_CORRECT_UNIT_YEAR_PLURAL')  ? TEXT_CORRECT_UNIT_YEAR_PLURAL  : $unit_singular['y'],
    ];

    $unit_label_display = $is_plural
        ? ($unit_plural[$unite_pastemps]   ?? $unite_pastemps)
        : ($unit_singular[$unite_pastemps] ?? $unite_pastemps);

    // Same idea for the calc mode: the front sends short codes ('moy', 'cumul')
    // but the user expects to see the localized label ('Mean', 'Sum').
    $mode_labels = [
        'moy'   => defined('TEXT_CORRECT_CALC_MEAN')  ? TEXT_CORRECT_CALC_MEAN  : 'moy',
        'cumul' => defined('TEXT_CORRECT_CALC_CUMUL') ? TEXT_CORRECT_CALC_CUMUL : 'cumul',
    ];
    $mode_label_display = $mode_labels[$modecalcul] ?? $modecalcul;

    $info_correction = sprintf(
        TEXT_CORRECT_INFO_TIMESTEP,
        $mode_label_display,
        $pastemps_int,
        $unit_label_display
    );
}

$obs = $info_correction;


// -----------------------------------------------
// Insert meta correction record

tep_db_query($sql_link,
    "INSERT INTO " . TABLE_DATA_META_CORRECTION
    . " (id_station, id_typedata, id_user, source, obs, id_correction, type_correction,"
    . " info_correction, axe_correction, datetime_first, datetime_end)"
    . " VALUES (" . $id_station . ", " . $id_chron . ", " . $id_user . ", '" . $source_info . "',"
    . " '" . $obs . "', " . $id_correction . ", '" . $type_correction . "', '" . $info_correction . "',"
    . " '" . $axe_correction . "', '" . $datetime_first_formated . "', '" . $datetime_end_formated . "')");
$id_meta = mysqli_insert_id($sql_link);


// -----------------------------------------------
// Generate correction data by type

// ---- Linear function or time offset ----
if ($type_correction == 'calcul' || $type_correction == 'decalage_date')
{
    // Gap-preserving update: any source point whose ABS(valeur) is a
    // sentinel (9999, 99999, 8888, 88888 — sign-agnostic) is rewritten
    // as -99999 in the corrected table. The user's correction formula
    // is only applied to genuine valid points.
    tep_db_query($sql_link,
        "INSERT INTO " . TABLE_DATA_ALL_CORRECTION . " (dateheure, valeur, id_meta)
         SELECT " . $sql_date_decalage . " AS nouvelle_dateheure,
                CASE WHEN ABS(da.valeur) IN (8888, 9999, 88888, 99999) THEN -99999
                     ELSE " . $sql_calcul . "
                END AS nouvelle_valeur,
                '" . $id_meta . "' AS nouvelle_id_meta
         FROM " . TABLE_DATA_ALL . " da
         JOIN " . TABLE_DATA_META . " dm ON da.id_meta = dm.id
         WHERE dm.id_typedata = " . $id_chron . "
           AND dm.id_station  = " . $id_station . "
           AND da.dateheure  >= '" . $datetime_first_formated . "'
           AND da.dateheure  <= '" . $datetime_end_formated . "'
         ORDER BY da.dateheure ASC");
}

// ---- Smoothing ----
if ($type_correction == 'lissage')
{
    $data_brutes_query = tep_db_query($sql_link,
        "SELECT da.dateheure, da.valeur
         FROM " . TABLE_DATA_ALL . " da
         JOIN " . TABLE_DATA_META . " dm ON da.id_meta = dm.id
         WHERE dm.id_typedata = " . $id_chron . "
           AND dm.id_station  = " . $id_station . "
           AND da.dateheure  >= '" . $datetime_first_formated . "'
           AND da.dateheure  <= '" . $datetime_end_formated . "'
         ORDER BY da.dateheure ASC");

    $seuil         = $calcul_correction / 100;
    $data_lissees  = [];
    $insert_values = [];
    $precedent     = null;
    $courant       = null;

    // Helper to spot sentinel values (sign-agnostic).
    $is_lacune = function ($v) {
        return in_array(abs((float)$v), [8888, 9999, 88888, 99999], true);
    };

    while ($data_brutes_tab = tep_db_fetch_array($data_brutes_query))
    {
        $raw_val = floatval($data_brutes_tab['valeur']);

        // Gap point: emit it as-is (normalized to -99999) and reset the
        // smoothing state so the next valid run starts fresh. The
        // smoothing logic relies on 3-point ratios that would otherwise
        // produce wild values when one of the neighbours is a sentinel.
        if ($is_lacune($raw_val))
        {
            $data_lissees[] = [
                'dateheure' => $data_brutes_tab['dateheure'],
                'valeur'    => -99999,
            ];
            $precedent = $courant = null;
            continue;
        }

        $suivant = ['dateheure' => $data_brutes_tab['dateheure'], 'valeur' => $raw_val];

        if (is_null($precedent))
        {
            $data_lissees[] = $suivant;
            $precedent = $courant = $suivant;
            continue;
        }

        if (!is_null($courant))
        {
            $var_precedent = ($precedent['valeur'] != 0) ? abs(($courant['valeur'] - $precedent['valeur']) / $precedent['valeur']) : 0;
            $var_suivant   = ($suivant['valeur']   != 0) ? abs(($suivant['valeur']  - $courant['valeur'])  / $suivant['valeur'])   : 0;

            if ($var_precedent < $seuil && $var_suivant < $seuil)
            {
                $precedent = $courant;
                continue;
            }
        }

        $data_lissees[] = $courant;
        $precedent      = $courant;
        $courant        = $suivant;
    }
    if (!is_null($courant)) { $data_lissees[] = $courant; }

    foreach ($data_lissees as $data)
    {
        $insert_values[] = "('" . $data['dateheure'] . "', " . $data['valeur'] . ", " . $id_meta . ")";
    }

    if (!empty($insert_values))
    {
        tep_db_query($sql_link,
            "INSERT INTO " . TABLE_DATA_ALL_CORRECTION . " (dateheure, valeur, id_meta) VALUES "
            . implode(',', $insert_values));
    }
}

// ---- Gap insertion ----
if ($type_correction == 'lacune')
{
    tep_db_query($sql_link,
        "INSERT INTO " . TABLE_DATA_ALL_CORRECTION . " (dateheure, valeur, id_meta) VALUES"
        . " ('" . $datetime_first_formated . "', -99999, " . $id_meta . "),"
        . " ('" . $datetime_end_formated   . "', -99999, " . $id_meta . ")");
}

// ---- Point deletion ----
// The corrected series over [first, last] is the SOURCE series minus the
// removed points. Gap sentinels are normalised to -99999 like the other
// corrections. Removed datetimes arrive FR-formatted; convert to US and
// build a NOT IN exclusion clause.
if ($type_correction == 'suppression')
{
    $deleted_us = [];
    foreach ($deleted_points as $dp)
    {
        $dp_tab = explode(' ', trim($dp));
        if (count($dp_tab) < 2) { continue; }
        $deleted_us[] = "'" . datefr_us($dp_tab[0]) . ' ' . $dp_tab[1] . "'";
    }

    $not_in_clause = count($deleted_us) > 0
                   ? " AND da.dateheure NOT IN (" . implode(',', $deleted_us) . ")"
                   : "";

    tep_db_query($sql_link,
        "INSERT INTO " . TABLE_DATA_ALL_CORRECTION . " (dateheure, valeur, id_meta)
         SELECT da.dateheure,
                CASE WHEN ABS(da.valeur) IN (8888, 9999, 88888, 99999) THEN -99999
                     ELSE da.valeur
                END,
                " . $id_meta . "
         FROM " . TABLE_DATA_ALL . " da
         JOIN " . TABLE_DATA_META . " dm ON da.id_meta = dm.id
         WHERE dm.id_typedata = " . $id_chron . "
           AND dm.id_station  = " . $id_station . "
           AND da.dateheure  >= '" . $datetime_first_formated . "'
           AND da.dateheure  <= '" . $datetime_end_formated . "'"
           . $not_in_clause . "
         ORDER BY da.dateheure ASC");
}

// ---- Time-step resampling — routed by unit ----
//
// The unified front-end form sends:
//   - pastemps : integer interval value
//   - unite    : 'min' / 'h' / 'd' / 'm' / 'y'
//
// We pick the right SQL pattern depending on the unit:
//
//   min / h / d    → sliding-window resampling, exact and fast:
//                    UNIX_TIMESTAMP DIV (bucket_seconds)
//                    The bucket is `pastemps × {60, 3600, 86400}` seconds.
//
//   m (month=1)    → calendar-true grouping by DATE_FORMAT '%Y-%m-01'.
//                    Front-end clamps pastemps to 1 for this unit, so
//                    "every N months" is intentionally not supported in
//                    this first pass (it would require a PHP loop
//                    because MySQL has no native multi-month grouping).
//
//   y (year=1)     → calendar-true grouping by DATE_FORMAT '%Y-01-01'.
//                    Same n=1 constraint as month.
//
// The aggregation function (SUM vs AVG) is decided the same way in
// every branch: default from valeur_data_type of the equipment, then
// overridden by the user's modecalcul choice if provided.

// =====================================================================
// ---- calcul_pastemps : aggregated resampling with gap handling ----
// =====================================================================
//
// Behaviour spec (validated with the user):
//   1. Gap detection : any source point with ABS(valeur) in
//      {9999, 99999, 8888, 88888}. Sign is ignored (historical imports
//      sometimes use positive sentinels).
//      EMPTY BUCKETS (no source point at all in the interval) are NOT
//      gaps — they just mean the sensor didn't sample anything in this
//      slot, which is normal when the sensor's native cadence differs
//      from the requested bucket size. They are skipped silently.
//
//   2. Bucket alignment is ALWAYS calendar-true for ALL units:
//        min   -> XX:00, XX:N*min, XX:2N*min ...
//        hour  -> XX:00:00
//        day   -> 00:00:00 of the day
//        month -> 1st day of month 00:00:00
//        year  -> Jan 1st 00:00:00
//
//   3. Partial buckets (calendar bucket NOT 100% inside the user
//      selection) are SILENTLY IGNORED — not generated, not marked
//      as a gap. The reasoning: the source data outside the selection
//      MIGHT be valid; we just can't see it from this AJAX call.
//
//   4. Complete buckets are evaluated for gap density :
//        - CUMUL mode  : >=1 gap in the bucket  -> bucket = gap
//        - MOY   mode  : gap% > user_threshold  -> bucket = gap
//                        gap% <= threshold      -> AVG(valid points)
//                        + obs annotation if gap% > 0
//
//   5. If NO complete bucket can be produced, the response carries an
//      error flag so the front shows a red message in the top box.
// =====================================================================
if ($type_correction == 'calcul_pastemps')
{
    // Gap-density threshold for the 'moy' mode (0..100, default 10).
    // Ignored when modecalcul == 'cumul' (strict rule applies there).
    $gap_threshold = isset($dataCalcul['gapThreshold'])
                   ? max(0, min(100, (int)$dataCalcul['gapThreshold']))
                   : 10;

    // Cast to int for SQL safety — pastemps comes from a free-form
    // user input field.
    $pastemps_int = max(1, (int)$pastemps);

    // -------------------------------------------------------------
    // Detect partial buckets at the SELECTION edges.
    //
    // The user-selection bounds ($datetime_first_formated /
    // $datetime_end_formated) are arbitrary timestamps. We compute the
    // FIRST and LAST "complete" bucket inside that window, then later
    // restrict the SQL aggregation to that complete range.
    //
    // A bucket [B_start, B_end) is "complete" relative to selection
    // [S_start, S_end] when B_start >= S_start AND B_end <= S_end + 1s
    // (we accept buckets whose end equals selection end exactly).
    // -------------------------------------------------------------
    $sel_start_ts = strtotime($datetime_first_formated);
    $sel_end_ts   = strtotime($datetime_end_formated);

    // seconds-per-unit lookup for SUB-DAY units only. 'd' is NOT here
    // anymore because adding 86400 seconds via strtotime+date can land
    // on the wrong hour when the PHP server timezone differs from MySQL's
    // (or after a DST transition for zones that have one). It is handled
    // separately below via DateTime calendar arithmetic — DST-safe and
    // timezone-safe.
    $seconds_map = ['min' => 60, 'h' => 3600];

    $first_complete_start_ts = null;
    $last_complete_end_ts    = null;
    $total_buckets_planned   = 0;
    $partial_buckets_count   = 0;

    if (isset($seconds_map[$unite_pastemps]))
    {
        // Sub-day units: simple seconds arithmetic.
        $bucket_seconds = $pastemps_int * $seconds_map[$unite_pastemps];

        // First bucket boundary at or AFTER the selection start.
        $aligned_start = (int)(ceil($sel_start_ts / $bucket_seconds) * $bucket_seconds);
        // Last bucket boundary at or BEFORE the selection end.
        $aligned_end   = (int)(floor(($sel_end_ts + 1) / $bucket_seconds) * $bucket_seconds);

        if ($aligned_end > $aligned_start)
        {
            $first_complete_start_ts = $aligned_start;
            $last_complete_end_ts    = $aligned_end;
        }

        // For partial counting we look at edges
        if ($aligned_start > $sel_start_ts) { $partial_buckets_count++; }
        if ($aligned_end   < $sel_end_ts + 1) { $partial_buckets_count++; }
    }
    else if ($unite_pastemps === 'd')
    {
        // Day: complete = whole calendar day [00:00:00 ; 23:59:59].
        // Calendar arithmetic via DateTime — DST-safe AND timezone-safe.
        // Adding 86400 seconds with strtotime/date would land on the
        // wrong hour when PHP and MySQL timezones differ (the source of
        // a hard-to-reproduce bug where every other day-bucket ended up
        // counted as "empty" because the cursor key didn't match the
        // SQL DATE_FORMAT output).
        $sel_start_dt = new DateTime($datetime_first_formated);
        $sel_end_dt   = new DateTime($datetime_end_formated);

        if ($sel_start_dt->format('H:i:s') === '00:00:00')
        {
            $first_complete_start = clone $sel_start_dt;
        }
        else
        {
            $first_complete_start = (clone $sel_start_dt)->modify('+1 day')->setTime(0, 0, 0);
            $partial_buckets_count++;
        }

        if ($sel_end_dt->format('H:i:s') === '23:59:59')
        {
            $last_complete_end = clone $sel_end_dt;
        }
        else
        {
            $last_complete_end = (clone $sel_end_dt)->modify('-1 day')->setTime(23, 59, 59);
            $partial_buckets_count++;
        }

        if ($first_complete_start <= $last_complete_end)
        {
            $first_complete_start_ts = $first_complete_start->getTimestamp();
            $last_complete_end_ts    = $last_complete_end->getTimestamp();
        }
    }
    else if ($unite_pastemps === 'm')
    {
        // Month: complete = whole calendar month.
        // First complete = if selection starts on the 1st of a month at
        // 00:00:00 -> that month; otherwise the next month's 1st.
        $sel_start_dt = new DateTime($datetime_first_formated);
        $sel_end_dt   = new DateTime($datetime_end_formated);

        if ($sel_start_dt->format('d H:i:s') === '01 00:00:00')
        {
            $first_complete_start = clone $sel_start_dt;
        }
        else
        {
            $first_complete_start = (clone $sel_start_dt)->modify('first day of next month')->setTime(0,0,0);
            $partial_buckets_count++;
        }

        // Last complete = last calendar day of the last covered month
        // at 23:59:59. Equivalent: first day of next month minus 1s.
        $next_month = (clone $sel_end_dt)->modify('first day of next month')->setTime(0,0,0);
        $expected_end_of_last_month = (clone $next_month)->modify('-1 second');
        if ($sel_end_dt >= $expected_end_of_last_month)
        {
            $last_complete_end = $expected_end_of_last_month;
        }
        else
        {
            $last_complete_end = (clone $sel_end_dt)->modify('first day of this month')->modify('-1 second');
            $partial_buckets_count++;
        }

        if ($first_complete_start <= $last_complete_end)
        {
            $first_complete_start_ts = $first_complete_start->getTimestamp();
            $last_complete_end_ts    = $last_complete_end->getTimestamp();
        }
    }
    else if ($unite_pastemps === 'y')
    {
        // Year: complete = whole calendar year.
        $sel_start_dt = new DateTime($datetime_first_formated);
        $sel_end_dt   = new DateTime($datetime_end_formated);

        if ($sel_start_dt->format('m-d H:i:s') === '01-01 00:00:00')
        {
            $first_complete_start = clone $sel_start_dt;
        }
        else
        {
            $first_complete_start = new DateTime(($sel_start_dt->format('Y') + 1) . '-01-01 00:00:00');
            $partial_buckets_count++;
        }

        $expected_end_of_year = new DateTime($sel_end_dt->format('Y') . '-12-31 23:59:59');
        if ($sel_end_dt >= $expected_end_of_year)
        {
            $last_complete_end = $expected_end_of_year;
        }
        else
        {
            $last_complete_end = new DateTime(($sel_end_dt->format('Y') - 1) . '-12-31 23:59:59');
            $partial_buckets_count++;
        }

        if ($first_complete_start <= $last_complete_end)
        {
            $first_complete_start_ts = $first_complete_start->getTimestamp();
            $last_complete_end_ts    = $last_complete_end->getTimestamp();
        }
    }

    // No complete bucket fits in the user selection -> hard error.
    if ($first_complete_start_ts === null)
    {
        // Map the technical unit code to a human label. We use the
        // same constants the UI dropdown already exposes so the
        // message stays in sync with the picker labels (and obeys
        // the active language).
        $unit_label_map = [
            'min' => defined('TEXT_CORRECT_UNIT_MIN_PLURAL')   ? TEXT_CORRECT_UNIT_MIN_PLURAL   : 'minutes',
            'h'   => defined('TEXT_CORRECT_UNIT_HOUR_PLURAL')  ? TEXT_CORRECT_UNIT_HOUR_PLURAL  : 'hours',
            'd'   => defined('TEXT_CORRECT_UNIT_DAY_PLURAL')   ? TEXT_CORRECT_UNIT_DAY_PLURAL   : 'days',
            'm'   => defined('TEXT_CORRECT_UNIT_MONTH_PLURAL') ? TEXT_CORRECT_UNIT_MONTH_PLURAL : 'month',
            'y'   => defined('TEXT_CORRECT_UNIT_YEAR_PLURAL')  ? TEXT_CORRECT_UNIT_YEAR_PLURAL  : 'year',
        ];
        $unit_label = isset($unit_label_map[$unite_pastemps])
                    ? $unit_label_map[$unite_pastemps]
                    : $unite_pastemps;

        // "No complete bucket in your selection. Please select at least
        //  one full %s." — %s is the localized unit label (plural form).
        $tpl_nobucket = defined('TEXT_CORRECT_ERR_NO_BUCKET')
                      ? TEXT_CORRECT_ERR_NO_BUCKET
                      : 'No complete bucket in your selection. Please select at least one full %s.';

        $msg_newCorrection = "<span style='color:#930000;font-weight:600;'>"
                           . sprintf($tpl_nobucket, $unit_label)
                           . "</span>";

        echo json_encode([
            'id_correction'     => $id_correction,
            'msg_newCorrection' => $msg_newCorrection,
            'error'             => true,
        ]);
        exit;
    }

    // -------------------------------------------------------------
    // Bucket aggregation SQL (restricted to the complete range)
    //
    // We emit, per bucket, the metadata needed to apply the gap rule
    // in PHP:
    //   - nb_total  : raw points found in the bucket
    //   - nb_lacune : how many of those carry a sentinel value
    //   - val_sum   : sum over valid (non-sentinel) points only
    //   - val_avg   : avg over valid (non-sentinel) points only
    //
    // The SQL excludes nothing — it's the PHP loop below that decides
    // what to insert and what the bucket's final value should be.
    // -------------------------------------------------------------
    $complete_first_str = date('Y-m-d H:i:s', $first_complete_start_ts);
    $complete_last_str  = date('Y-m-d H:i:s', $last_complete_end_ts);

    // Pre-compute the bucket-key expression depending on the unit.
    // The key is also used as the output timestamp.
    //
    // For "round" sub-day intervals (1 min, 1 h, 1 d), we use an explicit
    // DATE_FORMAT instead of UNIX_TIMESTAMP arithmetic. The UNIX route
    // assumes the server timezone equals the storage timezone — which
    // isn't true on a UTC server reading NC-local timestamps, where
    // "00:00:00 UTC" gets rendered back as "14:00:00 local" and shifts
    // every bucket by 14 hours. The calendar format is timezone-agnostic.
    if (isset($seconds_map[$unite_pastemps]))
    {
        if ($pastemps_int === 1 && $unite_pastemps === 'min')
        {
            $bucket_expr    = "DATE_FORMAT(da.dateheure, '%Y-%m-%d %H:%i:00')";
            $bucket_seconds = 60;
        }
        else if ($pastemps_int === 1 && $unite_pastemps === 'h')
        {
            $bucket_expr    = "DATE_FORMAT(da.dateheure, '%Y-%m-%d %H:00:00')";
            $bucket_seconds = 3600;
        }
        else
        {
            // N-step sub-day intervals (e.g. 5 min, 6 h): fall back to
            // UNIX_TIMESTAMP arithmetic. This keeps the behaviour
            // identical to the previous version for non-1 intervals;
            // the timezone shift is consistent because $bucket_end_for()
            // uses the same strtotime() basis.
            $bucket_seconds = $pastemps_int * $seconds_map[$unite_pastemps];
            $bucket_expr    = "DATE_FORMAT("
                            . "FROM_UNIXTIME(FLOOR(UNIX_TIMESTAMP(da.dateheure) / " . $bucket_seconds . ") * " . $bucket_seconds . ")"
                            . ", '%Y-%m-%d %H:%i:00')";
        }
    }
    else if ($unite_pastemps === 'd')
    {
        // Day: calendar-true bucket key. DATE_FORMAT on the textual
        // representation, no timezone shift. Matches the PHP cursor
        // generated by $bucket_next_for() below (also calendar-based).
        $bucket_expr = "DATE_FORMAT(da.dateheure, '%Y-%m-%d 00:00:00')";
    }
    else if ($unite_pastemps === 'm')
    {
        $bucket_expr = "DATE_FORMAT(da.dateheure, '%Y-%m-01 00:00:00')";
    }
    else // 'y'
    {
        $bucket_expr = "DATE_FORMAT(da.dateheure, '%Y-01-01 00:00:00')";
    }

    // Sentinel-detection expression : ABS(valeur) in our set.
    $sentinel_expr = "(ABS(da.valeur) IN (9999, 99999, 8888, 88888))";

    $bucket_query = tep_db_query($sql_link, "
        SELECT
            " . $bucket_expr . " AS bucket_ts,
            COUNT(*)             AS nb_total,
            SUM(CASE WHEN "  . $sentinel_expr . " THEN 1 ELSE 0 END) AS nb_lacune,
            SUM(CASE WHEN NOT " . $sentinel_expr . " THEN da.valeur ELSE 0 END) AS val_sum,
            AVG(CASE WHEN NOT " . $sentinel_expr . " THEN da.valeur ELSE NULL END) AS val_avg
        FROM " . TABLE_DATA_ALL . " da
        JOIN " . TABLE_DATA_META . " dm ON da.id_meta = dm.id
        WHERE dm.id_typedata = " . $id_chron . "
          AND dm.id_station  = " . $id_station . "
          AND da.dateheure  >= '" . $complete_first_str . "'
          AND da.dateheure  <= '" . $complete_last_str  . "'
        GROUP BY " . $bucket_expr . "
        ORDER BY bucket_ts ASC
    ");

    // -------------------------------------------------------------
    // Helper: compute the timestamp of the LAST second of a bucket.
    // -------------------------------------------------------------
    $bucket_end_for = function ($bucket_start_str) use ($unite_pastemps, $pastemps_int, $seconds_map) {
        if (isset($seconds_map[$unite_pastemps]))
        {
            $bucket_seconds = $pastemps_int * $seconds_map[$unite_pastemps];
            $end_ts = strtotime($bucket_start_str) + $bucket_seconds - 1;
            return date('Y-m-d H:i:s', $end_ts);
        }
        if ($unite_pastemps === 'd')
        {
            // Calendar-day end: 23:59:59 same day. DST-safe and TZ-safe.
            $dt = new DateTime($bucket_start_str);
            $dt->setTime(23, 59, 59);
            return $dt->format('Y-m-d H:i:s');
        }
        if ($unite_pastemps === 'm')
        {
            $dt = new DateTime($bucket_start_str);
            $dt->modify('first day of next month')->setTime(0, 0, 0)->modify('-1 second');
            return $dt->format('Y-m-d H:i:s');
        }
        if ($unite_pastemps === 'y')
        {
            $dt = new DateTime($bucket_start_str);
            $dt->setDate((int)$dt->format('Y'), 12, 31)->setTime(23, 59, 59);
            return $dt->format('Y-m-d H:i:s');
        }
        return $bucket_start_str;
    };

    // Helper: advance to the next calendar bucket start, given the current one.
    $bucket_next_for = function ($bucket_start_str) use ($unite_pastemps, $pastemps_int, $seconds_map) {
        if (isset($seconds_map[$unite_pastemps]))
        {
            $bucket_seconds = $pastemps_int * $seconds_map[$unite_pastemps];
            return date('Y-m-d H:i:s', strtotime($bucket_start_str) + $bucket_seconds);
        }
        if ($unite_pastemps === 'd')
        {
            // Next calendar day at 00:00:00. DateTime::modify('+1 day') is
            // calendar-arithmetic, not seconds-arithmetic — stays at
            // midnight even across DST transitions or PHP/MySQL timezone
            // differences. This is what makes the cursor key match the
            // SQL DATE_FORMAT output exactly.
            $dt = new DateTime($bucket_start_str);
            $dt->modify('+1 day')->setTime(0, 0, 0);
            return $dt->format('Y-m-d H:i:s');
        }
        if ($unite_pastemps === 'm')
        {
            $dt = new DateTime($bucket_start_str);
            $dt->modify('first day of next month')->setTime(0, 0, 0);
            return $dt->format('Y-m-d H:i:s');
        }
        if ($unite_pastemps === 'y')
        {
            $dt = new DateTime($bucket_start_str);
            $dt->setDate((int)$dt->format('Y') + 1, 1, 1)->setTime(0, 0, 0);
            return $dt->format('Y-m-d H:i:s');
        }
        return $bucket_start_str;
    };

    // =============================================================
    // PASS 0 — Detect "gap zones" inferred from the raw sentinel
    // points themselves.
    //
    // Why this exists:
    //   A user-edited gap is stored as just TWO sentinel points (one
    //   at the gap start, one at the gap end). There are usually no
    //   rows at all between those two markers.
    //   With only PASS 1 + PASS 2, the buckets in between would be
    //   "no source data" and PASS 2 would silently SKIP them — making
    //   the corrected series look continuous across what is, in fact,
    //   a real multi-day gap edited by the user.
    //
    // What this pass does:
    //   We pull every sentinel point of the series in chronological
    //   order (across the full station period, not just inside the
    //   user's selection — a sentinel on Sept 30 closes a zone that
    //   started Aug 15, even if the user only selected the middle).
    //   Then we walk them and accumulate "gap zones" — intervals
    //   bounded by two sentinels with NO valid data in between.
    //
    // How a zone is built:
    //   When we see a sentinel, we open (or extend) a run by tracking
    //   the most recent sentinel timestamp. The run is sealed when
    //   the NEXT row we see is a sentinel — the zone runs from the
    //   first sentinel's time to that next sentinel's time. The
    //   "between" check is delegated to a quick COUNT against the
    //   raw table for any valid point strictly between two sentinels;
    //   if there is one, we know the previous run has been broken
    //   by valid data and we don't merge into one zone.
    //
    // Cost note:
    //   We make TWO queries beyond PASS 1: (a) the sentinel list,
    //   typically tiny (a few dozen rows per station-decade); and
    //   (b) a single bounded COUNT(*) between each consecutive pair
    //   of sentinels. Even on big stations this stays under a few
    //   hundred milliseconds because the sentinel set is small.
    //
    // Output:
    //   $gap_zones = [ ['start' => 'YYYY-MM-DD HH:MM:SS',
    //                   'end'   => 'YYYY-MM-DD HH:MM:SS'], ... ]
    //   Used by PASS 2: an "empty bucket" (no source data) whose
    //   timestamp falls inside one of these zones is converted to a
    //   gap bucket rather than silently skipped.
    // =============================================================
    $gap_zones = [];

    $sentinel_query = tep_db_query($sql_link, "
        SELECT da.dateheure
        FROM " . TABLE_DATA_ALL . " da
        JOIN " . TABLE_DATA_META . " dm ON da.id_meta = dm.id
        WHERE dm.id_typedata = " . $id_chron . "
          AND dm.id_station  = " . $id_station . "
          AND " . $sentinel_expr . "
          AND da.dateheure  >= '" . $complete_first_str . "'
          AND da.dateheure  <= '" . $complete_last_str  . "'
        ORDER BY da.dateheure ASC
    ");

    $sentinel_list = [];
    while ($srow = tep_db_fetch_array($sentinel_query))
    {
        $sentinel_list[] = $srow['dateheure'];
    }

    // Walk consecutive pairs. Between two consecutive sentinels, if
    // there's no valid data point at all, they delimit a real gap zone.
    // (We accept "back-to-back" sentinels with a tiny gap too — Plotly
    // gap-rendering will fuse them anyway, so the resulting bucket flags
    // are conservative.)
    for ($si = 0; $si < count($sentinel_list) - 1; $si++)
    {
        $s_start = $sentinel_list[$si];
        $s_end   = $sentinel_list[$si + 1];

        // Are there any valid (non-sentinel) points strictly between
        // these two sentinels? If yes, this pair does NOT form a zone.
        $check_row = tep_db_fetch_array(tep_db_query($sql_link, "
            SELECT COUNT(*) AS nb_valid
            FROM " . TABLE_DATA_ALL . " da
            JOIN " . TABLE_DATA_META . " dm ON da.id_meta = dm.id
            WHERE dm.id_typedata = " . $id_chron . "
              AND dm.id_station  = " . $id_station . "
              AND NOT " . $sentinel_expr . "
              AND da.dateheure > '" . $s_start . "'
              AND da.dateheure < '" . $s_end   . "'
            LIMIT 1
        "));

        if ((int)$check_row['nb_valid'] === 0)
        {
            // Real gap zone bounded by two sentinels.
            $gap_zones[] = ['start' => $s_start, 'end' => $s_end];
        }
    }

    // Merge adjacent / overlapping zones to simplify lookups in PASS 2.
    // (Two zones from different sentinel pairs may share an endpoint if
    //  a sentinel got reused as both the end of one zone and the start
    //  of the next.)
    if (count($gap_zones) > 1)
    {
        $merged = [];
        $cur    = $gap_zones[0];
        for ($gi = 1; $gi < count($gap_zones); $gi++)
        {
            if ($gap_zones[$gi]['start'] <= $cur['end'])
            {
                if ($gap_zones[$gi]['end'] > $cur['end'])
                {
                    $cur['end'] = $gap_zones[$gi]['end'];
                }
            }
            else
            {
                $merged[] = $cur;
                $cur      = $gap_zones[$gi];
            }
        }
        $merged[]  = $cur;
        $gap_zones = $merged;
    }

    // Quick lookup: is the bucket key inside any gap zone?
    $is_in_gap_zone = function ($bucket_start, $bucket_end_str) use ($gap_zones) {
        foreach ($gap_zones as $z)
        {
            // Overlap test: bucket [bs, be] overlaps zone [zs, ze]
            // iff bs <= ze AND be >= zs.
            if ($bucket_start <= $z['end'] && $bucket_end_str >= $z['start'])
            {
                return true;
            }
        }
        return false;
    };


    // =============================================================
    // PASS 1 — Classify each bucket coming back from SQL.
    //
    // We collect everything in $bucket_status keyed by bucket_ts:
    //   - 'kind'  : 'value' | 'gap'
    //   - 'value' : the numeric value (when kind='value')
    //
    // Buckets that exist in the calendar range but have NO source
    // data at all are simply absent from $bucket_status. PASS 2 will
    // then check if they fall inside one of the gap zones detected by
    // PASS 0 — if so they become gap buckets; otherwise they are
    // skipped (sensor cadence artefact, not a real gap).
    // =============================================================
    $bucket_status      = [];
    $generated_count    = 0;
    $generated_with_gap = 0;

    while ($brow = tep_db_fetch_array($bucket_query))
    {
        $bucket_ts = $brow['bucket_ts'];
        $nb_total  = (int)$brow['nb_total'];
        $nb_lacune = (int)$brow['nb_lacune'];
        $val_sum   = (float)$brow['val_sum'];
        $val_avg   = ($brow['val_avg'] === null) ? null : (float)$brow['val_avg'];
        $gap_pct   = $nb_total > 0 ? ($nb_lacune / $nb_total) * 100 : 100;

        if ($modecalcul === 'cumul')
        {
            // Strict rule: any gap in bucket -> bucket is a gap.
            if ($nb_lacune > 0)
            {
                $bucket_status[$bucket_ts] = ['kind' => 'gap'];
            }
            else
            {
                $bucket_status[$bucket_ts] = ['kind' => 'value', 'value' => $val_sum];
                $generated_count++;
            }
        }
        else // 'moy' semantics
        {
            if ($gap_pct > $gap_threshold || $val_avg === null)
            {
                $bucket_status[$bucket_ts] = ['kind' => 'gap'];
            }
            else
            {
                $bucket_status[$bucket_ts] = ['kind' => 'value', 'value' => round($val_avg, 4)];
                $generated_count++;
                if ($nb_lacune > 0) { $generated_with_gap++; }
            }
        }
    }

    // =============================================================
    // PASS 2 — Walk the FULL calendar range bucket by bucket.
    //
    // Three cases per bucket:
    //   A. status 'value' -> emit 1 row at bucket_ts with the value.
    //   B. status 'gap'   -> REAL gap (source contains sentinel values
    //                        above the threshold). Open / extend a gap
    //                        run, emit -99999 markers so the renderer
    //                        draws a gap shape. Consecutive gap buckets
    //                        merge into ONE shape (2 rows: -99999 at
    //                        run start, -99999 at run end).
    //   C. NO status      -> bucket is empty in the source. This is
    //                        NOT a gap — the sensor just didn't sample
    //                        anything in this interval (a 5h bucket on
    //                        a sensor that emits every 4h will have
    //                        empty buckets routinely). We SKIP, no row
    //                        emitted. Any open real-gap run is closed
    //                        at the last gap bucket's end (its closing
    //                        marker doesn't belong here).
    // =============================================================
    $insert_rows         = [];
    $gap_buckets_count   = 0; // counted as runs, not individual buckets
    $gap_run_open        = false;
    $gap_run_start_ts    = null;
    $gap_run_last_ts     = null;  // last bucket_ts inside the current run
    $skipped_empty_count = 0;     // count of C-cases for the summary

    // Walk: bucket_cursor advances calendar-step by calendar-step
    $cursor_str = date('Y-m-d H:i:s', $first_complete_start_ts);
    $end_str    = date('Y-m-d H:i:s', $last_complete_end_ts);

    // Safety bound — prevents infinite loop if a misconfigured unit
    // causes $bucket_next_for() to return the same value.
    $max_iter   = 200000;
    $iter       = 0;

    while ($cursor_str <= $end_str && $iter < $max_iter)
    {
        $iter++;
        $has_status = isset($bucket_status[$cursor_str]);

        if (!$has_status)
        {
            // No source data in this bucket. Two possibilities:
            //
            //   (1) The bucket sits INSIDE a gap zone detected by PASS 0
            //       (i.e. between two sentinels with no valid data in
            //       between). The user expects this to render as a gap
            //       on the chart, so we treat it like case B.
            //
            //   (2) The bucket is genuinely empty between two valid
            //       measurements — the sensor just didn't sample
            //       anything in this slot. NOT a gap; we skip.
            $bucket_end_check = $bucket_end_for($cursor_str);

            if ($is_in_gap_zone($cursor_str, $bucket_end_check))
            {
                // CASE B (via gap-zone inheritance) — extend / open run.
                if (!$gap_run_open)
                {
                    $insert_rows[]    = ['dh' => $cursor_str, 'val' => -99999];
                    $gap_run_open     = true;
                    $gap_run_start_ts = $cursor_str;
                    $gap_buckets_count++;
                }
                $gap_run_last_ts = $cursor_str;
            }
            else
            {
                // CASE C — sensor cadence artefact, skip silently.
                // Close any pending real-gap run first (its closing
                // marker belongs at the end of the LAST gap bucket).
                if ($gap_run_open)
                {
                    $insert_rows[] = [
                        'dh'  => $bucket_end_for($gap_run_last_ts),
                        'val' => -99999,
                    ];
                    $gap_run_open    = false;
                    $gap_run_last_ts = null;
                }
                $skipped_empty_count++;
            }
        }
        else if ($bucket_status[$cursor_str]['kind'] === 'gap')
        {
            // CASE B — real gap (sentinel-driven, density above threshold).
            if (!$gap_run_open)
            {
                $insert_rows[]    = ['dh' => $cursor_str, 'val' => -99999];
                $gap_run_open     = true;
                $gap_run_start_ts = $cursor_str;
                $gap_buckets_count++;
            }
            $gap_run_last_ts = $cursor_str;
        }
        else // 'value'
        {
            // CASE A — emit the computed value. Close any pending gap run.
            if ($gap_run_open)
            {
                $insert_rows[] = [
                    'dh'  => $bucket_end_for($gap_run_last_ts),
                    'val' => -99999,
                ];
                $gap_run_open    = false;
                $gap_run_last_ts = null;
            }

            $insert_rows[] = [
                'dh'  => $cursor_str,
                'val' => $bucket_status[$cursor_str]['value'],
            ];
        }

        // Advance cursor to next calendar bucket.
        $cursor_str = $bucket_next_for($cursor_str);
    }

    // If the loop ended while a gap run was still open, close it at
    // the end of the last bucket in the run.
    if ($gap_run_open && $gap_run_last_ts !== null)
    {
        $insert_rows[] = [
            'dh'  => $bucket_end_for($gap_run_last_ts),
            'val' => -99999,
        ];
    }

    // Batch INSERT (one shot).
    if (!empty($insert_rows))
    {
        $values_sql = [];
        foreach ($insert_rows as $r)
        {
            $values_sql[] = "('" . $r['dh'] . "', " . $r['val'] . ", " . $id_meta . ")";
        }
        tep_db_query($sql_link,
            "INSERT INTO " . TABLE_DATA_ALL_CORRECTION
            . " (dateheure, valeur, id_meta) VALUES " . implode(',', $values_sql));
    }

    // -------------------------------------------------------------
    // Build a user-facing summary appended to msg_newCorrection
    // so the existing top-box mechanism in data_chron_calcul.php
    // shows the result. The base message stays whatever the surrounding
    // code emits — we just enrich it.
    // -------------------------------------------------------------
    $summary_bits = [];

    // Each segment is a sprintf template taking the relevant count as %d.
    // Fallbacks keep English wording if the language file isn't updated.
    $tpl_complete  = defined('TEXT_CORRECT_SUMMARY_COMPLETE')  ? TEXT_CORRECT_SUMMARY_COMPLETE  : '%d complete bucket(s) generated';
    $tpl_annotated = defined('TEXT_CORRECT_SUMMARY_ANNOTATED') ? TEXT_CORRECT_SUMMARY_ANNOTATED : '%d bucket(s) annotated with gap warning';
    $tpl_gapzone   = defined('TEXT_CORRECT_SUMMARY_GAPZONE')   ? TEXT_CORRECT_SUMMARY_GAPZONE   : '%d gap zone(s) generated';
    $tpl_empty     = defined('TEXT_CORRECT_SUMMARY_EMPTY')     ? TEXT_CORRECT_SUMMARY_EMPTY     : '%d empty bucket(s) skipped (no source data)';
    $tpl_partial   = defined('TEXT_CORRECT_SUMMARY_PARTIAL')   ? TEXT_CORRECT_SUMMARY_PARTIAL   : '%d partial bucket(s) ignored';

    $summary_bits[] = sprintf($tpl_complete, $generated_count);
    if ($generated_with_gap > 0)
    {
        $summary_bits[] = sprintf($tpl_annotated, $generated_with_gap);
    }
    if ($gap_buckets_count > 0)
    {
        // gap_buckets_count is the number of RUNS (consecutive gap
        // buckets are merged into a single visual gap zone). Wording
        // 'gap zone(s)' makes that clear to the user.
        $summary_bits[] = sprintf($tpl_gapzone, $gap_buckets_count);
    }
    if ($skipped_empty_count > 0)
    {
        // Empty buckets are a normal artefact of resampling at a step
        // finer than the sensor's native cadence. We surface the count
        // so the user understands why the output series may be slightly
        // less dense than the calendar would suggest.
        $summary_bits[] = sprintf($tpl_empty, $skipped_empty_count);
    }
    if ($partial_buckets_count > 0)
    {
        $summary_bits[] = sprintf($tpl_partial, $partial_buckets_count);
    }

    // Attach to the running info_correction so the META obs records
    // the operation breakdown. Frontend will display via msg_newCorrection.
    $pastemps_summary = implode(' · ', $summary_bits);
}

// ---- Period aggregation (day / month / year) ----
if ($type_correction == 'calcul_chron_new')
{
    $type_data  = $type_chron_array[$id_chron]['id_eq_type_data'];
    $agregation = ($eq_type_array[$type_data]['valeur_data_type'] == 2) ? 'SUM' : 'AVG';

    $date_format_map = [
        1 => 'DATE(da.dateheure)',
        2 => "DATE_FORMAT(da.dateheure, '%Y-%m-01')",
        3 => "DATE_FORMAT(da.dateheure, '%Y-01-01')",
    ];

    if (isset($date_format_map[$to_periode_encours]))
    {
        tep_db_query($sql_link,
            "INSERT INTO " . TABLE_DATA_ALL_CORRECTION . " (dateheure, valeur, id_meta)
             SELECT " . $date_format_map[$to_periode_encours] . " AS nouvelle_dateheure,
                    " . $agregation . "(da.valeur) AS nouvelle_valeur,
                    '" . $id_meta . "' AS id_meta
             FROM " . TABLE_DATA_ALL . " da
             JOIN " . TABLE_DATA_META . " dm ON da.id_meta = dm.id
             WHERE da.valeur NOT IN (-8888, -9999, -88888, -99999)
               AND dm.id_typedata = " . $id_chron . "
               AND dm.id_station  = " . $id_station . "
               AND da.dateheure  >= '" . $datetime_first_formated . "'
               AND da.dateheure  <= '" . $datetime_end_formated . "'
             GROUP BY " . $date_format_map[$to_periode_encours] . "
             ORDER BY nouvelle_dateheure ASC");
    }
}


// -----------------------------------------------
// Build success message

// Period range phrasing — "du %s au %s" in French. Templated so the
// connector words ("from ... to ...") follow the active language.
$tpl_period_range = defined('TEXT_CALCUL_SUCCESS_RANGE')
                  ? TEXT_CALCUL_SUCCESS_RANGE
                  : 'from %s to %s';

$msg_newCorrection = "<div style='font-weight:normal;'>
    <span style='font-size:16px;font-weight:bold;'>" . TEXT_CALCUL_SUCCESS_TITLE . "</span>
    <br><br>
    <span style='font-weight:bold;'>" . TEXT_CALCUL_SUCCESS_TYPE   . "</span> : " . $obs . "
    <br>
    <span style='font-weight:bold;'>" . TEXT_CALCUL_SUCCESS_PERIOD . "</span> : "
    . sprintf($tpl_period_range, $datetime_first, $datetime_end) . "
</div>";

// Append the pastemps breakdown (complete/gap/partial bucket counts)
// when the calcul_pastemps branch was taken — it sets $pastemps_summary.
if (!empty($pastemps_summary))
{
    $msg_newCorrection .= "<div style='margin-top:8px;font-size:12px;color:#555;'>"
                        . htmlspecialchars($pastemps_summary)
                        . "</div>";
}


// -----------------------------------------------
// Return correction ID and message as JSON

echo json_encode([
    'id_correction'     => $id_correction,
    'msg_newCorrection' => $msg_newCorrection,
]);
?>