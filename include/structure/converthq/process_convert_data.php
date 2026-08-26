<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
AJAX endpoint — H→Q batch conversion (called recursively with offSet)
Receives JSON: timezone_php, offSet, typedataChronH, typedataChronQ,
               xDateMin, xDateMax, idStation, id_user, id_meta_correction.

On the first call (offSet = 0):
  1. Builds the ETL interpolation coefficients table (TABLE_DATA_ETL_CORRECTION).
  2. Creates a new temporary meta record in TABLE_DATA_META_CORRECTION.
  3. Clears any previous data for that meta ID.

On every call:
  4. Inserts one batch (10000 rows) of converted values from TABLE_DATA_ALL
     into TABLE_DATA_ALL_CORRECTION using the interpolated ETL coefficients.
  5. Also inserts gap markers (-99999) for source gaps.
  6. Counts the points lost in this batch by category:
       - converted   : H within an ETL segment at this date    → Q computed
       - gaps_source : source H is a gap marker (-8888..-99999) → reproduced as -99999
       - above       : H >  Hmax of the ETL valid at this date (out of range)
       - below       : H <  Hmin of the ETL valid at this date (out of range)
       - nocov       : no ETL at all is valid at this date
  7. Checks whether more data remains (using OFFSET on the count query).

Returns JSON: {
    remaining, newCursorDate, id_meta_correction,
    nb_etl, nb_segments,                              (first batch only)
    nb_converted, nb_gaps_source, nb_above, nb_below, nb_nocov   (every batch)
}
----------------------------------------
*/

require('../../config.php');
require('../../database_tables.php');

require('../../function/date.php');
require('../../function/database.php');
require('../../function/html_output.php');
require('../../function/general.php');
require('../../function/stats.php');

require('../../text_content_' . LANGUAGE . '.php');

header('Content-Type: text/html; charset=utf-8');

$sql_link = mysqli_connect(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD, DB_DATABASE)
    or die('Cannot connect to the database');
mysqli_query($sql_link, 'SET NAMES UTF8');

// Axis type lookup (used in future extension)
$data_type_axe_array = [];
$data_type_axe_query = tep_db_query($sql_link,
    "SELECT DISTINCT id, axe, unite FROM " . TABLE_DATA_TYPE_AXE);
while ($r = tep_db_fetch_array($data_type_axe_query))
{
    $data_type_axe_array[$r['id']] = ['axe' => $r['axe'], 'unite' => $r['unite']];
}

$data             = json_decode(file_get_contents('php://input'), true);
$timezone_php     = $data['timezone_php'];
$typedata_chron_h = $data['typedataChronH'];
$typedata_chron_q = $data['typedataChronQ'];
$date_1           = $data['xDateMin'];
$datetime_1       = datefr_us($date_1) . " 00:00:00";
$date_2           = $data['xDateMax'];
$datetime_2       = datefr_us($date_2) . " 23:59:59";
$station_chron    = $data['idStation'];
$id_user          = $data['id_user'];
$id_meta_new      = $data['id_meta_correction'];

$date_cursor  = $data['dateFirstProcess'];
$is_first     = $data['isFirstBatch'];

// Counters returned to the front-end at every batch.
// First-batch-only fields (nb_etl, nb_segments) stay at 0 on subsequent batches.
$nb_etl          = 0;
$nb_segments     = 0;
$nb_converted    = 0;
$nb_gaps_source  = 0;
$nb_above        = 0;
$nb_below        = 0;
$nb_nocov        = 0;


// -----------------------------------------------
// First batch only: build ETL coefficients table and meta record

if ($is_first)
{
    $etl_id  = null;
    $etl_h1  = null;
    $etl_q1  = null;
    $tab_etl = [];

    // On initialise les dates par défaut au cas où la requête ETL soit vide
    $dateFirst = $datetime_1;
    $dateEnd   = $datetime_2;

    // Load all ETL data points for the requested station and period

    $sql_etl = "SELECT DISTINCT etl.id, etl.datetime_first, etl.datetime_end, ed.hauteur, ed.debit
                FROM " . TABLE_DATA_ETL . " etl
                JOIN " . TABLE_DATA_ETL_DATA . " ed ON ed.id_etl = etl.id
                WHERE etl.id_station=$station_chron
                AND etl.datetime_first <= '$datetime_2'
                AND etl.datetime_end   >= '$datetime_1'
                ORDER BY datetime_first ASC, hauteur ASC";

    $etl_data_query = tep_db_query($sql_link,$sql_etl);
    while ($etl_data_tab = tep_db_fetch_array($etl_data_query))
    {
        $new_etl = ($etl_id !== $etl_data_tab['id']);
        $etl_id  = $etl_data_tab['id'];
        $etl_h2  = $etl_data_tab['hauteur'];
        $etl_q2  = $etl_data_tab['debit'];

        if (!$new_etl && $etl_h1 !== null && $etl_q1 !== null)
        {
            // Linear interpolation coefficients between two consecutive H points
            if ($etl_h2 !== $etl_h1)
            {
                $coef_a = round(($etl_q2 - $etl_q1) / ($etl_h2 - $etl_h1), 2);
                $coef_b = round($etl_q1 - $coef_a * $etl_h1, 2);
            }
            else
            {
                $coef_a = 0;
                $coef_b = $etl_q1;
            }

            // Calcul des dates effectives
            $dtF = new DateTime($etl_data_tab['datetime_first']);
            $dtE = new DateTime($etl_data_tab['datetime_end']);
            $d1  = new DateTime($datetime_1);
            $d2  = new DateTime($datetime_2);

            $dateFirst = ($dtF < $d1) ? $datetime_1 : $dtF->format('Y-m-d H:i:s');
            $dateEnd   = ($dtE > $d2) ? $datetime_2 : $dtE->format('Y-m-d H:i:s');

            $tab_etl[] = [
                'dateFirst' => $dateFirst,
                'dateEnd'   => $dateEnd,
                'h1' => $etl_h1, 'h2' => $etl_h2,
                'q1' => $etl_q1, 'q2' => $etl_q2,
                'a'  => $coef_a, 'b'  => $coef_b,
            ];
        }
        $etl_h1 = $etl_h2;
        $etl_q1 = $etl_q2;
    }

    // Create meta record for this conversion run
    if ($id_meta_new == 0)
    {
        // First, drop ANY previous "Conversion" proposal for this station
        // (meta + data). Otherwise chained conversion attempts accumulate:
        // process_convert_graph_q.php reads every source='Conversion' row, so
        // a stale proposal would still be drawn alongside the new one. Same
        // cleanup pattern as process_convert_valid.php.
        $sql_prev = "SELECT DISTINCT id FROM " . TABLE_DATA_META_CORRECTION
                  . " WHERE id_station=" . (int)$station_chron . " AND source='Conversion'";
        $prev_ids = [];
        $res_prev = tep_db_query($sql_link, $sql_prev);
        while ($rp = tep_db_fetch_array($res_prev)) { $prev_ids[] = (int)$rp['id']; }
        if (count($prev_ids) > 0)
        {
            $ids_list = implode(',', $prev_ids);
            tep_db_query($sql_link, "DELETE FROM " . TABLE_DATA_ALL_CORRECTION  . " WHERE id_meta IN ($ids_list)");
            tep_db_query($sql_link, "DELETE FROM " . TABLE_DATA_META_CORRECTION . " WHERE id IN ($ids_list)");
        }

        $sql_meta_correction = "INSERT INTO " . TABLE_DATA_META_CORRECTION
                                . " (id_station, id_typedata, id_user, source, obs, id_correction,"
                                . "  info_correction, datetime_first, datetime_end)"
                                . " VALUES ($station_chron, $typedata_chron_q, $id_user,"
                                . " 'Conversion', '', 0,"
                                . " 'Conversion Height(H) -> Flow rate(Q)',"
                                . " '$dateFirst', '$dateEnd')";

        tep_db_query($sql_link,$sql_meta_correction);
        $id_meta_new = mysqli_insert_id($sql_link);
    }

    // Rebuild the ETL coefficient table from scratch
    tep_db_query($sql_link, "TRUNCATE TABLE " . TABLE_DATA_ETL_CORRECTION);

    foreach ($tab_etl as $value)
    {
        $sql_data_etl_correction = "INSERT INTO " . TABLE_DATA_ETL_CORRECTION
                                    . " (id_station, id_typedata, datetime_first, datetime_end, h1, h2, a, b)"
                                    . " VALUES ($station_chron, $typedata_chron_h,"
                                    . " '" . $value['dateFirst'] . "', '" . $value['dateEnd'] . "',"
                                    . " " . $value['h1'] . ", " . $value['h2'] . ","
                                    . " " . $value['a']  . ", " . $value['b']  . ")";

        tep_db_query($sql_link,$sql_data_etl_correction);
    }

    // Count distinct ETLs covering the period — used in the front-end log
    $sql_count_etl = "SELECT COUNT(DISTINCT id) AS n FROM " . TABLE_DATA_ETL
                   . " WHERE id_station=$station_chron"
                   . "   AND datetime_first <= '$datetime_2'"
                   . "   AND datetime_end   >= '$datetime_1'";
    $r_count = tep_db_fetch_array(tep_db_query($sql_link, $sql_count_etl));
    $nb_etl      = (int)($r_count['n'] ?? 0);
    $nb_segments = count($tab_etl);

    // Clear any previous data for this meta ID
    $sql_del_data_all_correction = "DELETE FROM " . TABLE_DATA_ALL_CORRECTION . " WHERE id_meta=$id_meta_new";
    tep_db_query($sql_link,$sql_del_data_all_correction);
}

// -----------------------------------------------
// Every batch: insert converted values and gap markers

$operator = $is_first ? '>=' : '>';
$batch_size   = 10000;

// Converted values (Q = a*H + b)
$sql_insert = "INSERT INTO " . TABLE_DATA_ALL_CORRECTION . " (dateheure, valeur, id_meta)
                SELECT da.dateheure,CASE 
                        WHEN da.valeur <= -8888 THEN -99999
                        ELSE ROUND((ec.a * da.valeur + ec.b), 3)
                        END AS valeur,
                        $id_meta_new AS id_meta
                FROM " . TABLE_DATA_ALL . " da
                JOIN " . TABLE_DATA_META . " dm
                    ON da.id_meta = dm.id
                    AND dm.id_station=$station_chron
                    AND dm.id_typedata=$typedata_chron_h
                LEFT JOIN " . TABLE_DATA_ETL_CORRECTION . " ec
                    ON ec.id_station=$station_chron
                    AND ec.id_typedata=$typedata_chron_h
                    AND da.valeur >= ec.h1
                    AND da.valeur <  ec.h2
                    AND da.dateheure >= ec.datetime_first
                    AND da.dateheure <  ec.datetime_end
                WHERE da.dateheure $operator '$date_cursor'
                    AND da.dateheure <= '$datetime_2'
                    AND (da.valeur <= -8888 OR ec.a IS NOT NULL)
                ORDER BY da.dateheure ASC
                LIMIT $batch_size";
tep_db_query($sql_link, $sql_insert);

// Number of rows actually written by the INSERT above
// = converted points + source gaps (both are inserted with a value).
// We split them apart with a dedicated COUNT below.
$nb_inserted = (int)mysqli_affected_rows($sql_link);


// -----------------------------------------------
// Find the source date that bounds this batch — used both to advance
// dateFirstProcess for the next AJAX call AND to limit every COUNT
// below to the exact same window the INSERT just processed.

$sql_cursor = " SELECT da.dateheure 
                FROM " . TABLE_DATA_ALL . " da
                JOIN " . TABLE_DATA_META . " dm ON da.id_meta = dm.id
                WHERE dm.id_station = $station_chron 
                AND dm.id_typedata = $typedata_chron_h
                AND da.dateheure $operator '$date_cursor'
                AND da.dateheure <= '$datetime_2'
                ORDER BY da.dateheure ASC
                LIMIT 1 OFFSET " . ($batch_size - 1);

$res_cursor = tep_db_fetch_array(tep_db_query($sql_link, $sql_cursor));

// If no Nth row was found, we have reached the end of the series.
if (!$res_cursor)
{
    $newCursorDate = $datetime_2;
    $remaining     = false;
}
else
{
    $newCursorDate = $res_cursor['dateheure'];
    $remaining     = true;
}


// -----------------------------------------------
// Per-batch counters by category — same date window as the INSERT above.
// One COUNT per category, all bounded by [$date_cursor, $newCursorDate].
//
// The $operator (>= on first batch, > on later batches) and the upper
// bound $newCursorDate together describe exactly the rows the INSERT
// considered. This guarantees the counters cumulatively sum to the
// full series, with no overlap and no missed point.

// Source gaps in this batch
$sql_count_gaps = " SELECT COUNT(*) AS n
                    FROM " . TABLE_DATA_ALL . " da
                    JOIN " . TABLE_DATA_META . " dm ON da.id_meta = dm.id
                    WHERE dm.id_station = $station_chron
                    AND dm.id_typedata = $typedata_chron_h
                    AND da.dateheure $operator '$date_cursor'
                    AND da.dateheure <= '$newCursorDate'
                    AND da.valeur <= -8888";
$r = tep_db_fetch_array(tep_db_query($sql_link, $sql_count_gaps));
$nb_gaps_source = (int)($r['n'] ?? 0);

// Converted points = total inserted - source gaps
$nb_converted = max(0, $nb_inserted - $nb_gaps_source);

// Points lost — H is a real value (not a gap) but no ETL segment covers it.
// We need to know WHY: above curve max, below curve min, or no ETL at all.
//
// Strategy: for every (date, H) row that did NOT get converted, look up
// the ETL that is valid at this date, derive Hmin/Hmax of that ETL from
// its gauging points, and classify:
//   - no ETL valid at this date  → nocov
//   - H > Hmax of that ETL       → above
//   - H < Hmin of that ETL       → below

// 1) nocov — H is real but no ETL at all overlaps this date
$sql_count_nocov = " SELECT COUNT(*) AS n
                     FROM " . TABLE_DATA_ALL . " da
                     JOIN " . TABLE_DATA_META . " dm ON da.id_meta = dm.id
                     LEFT JOIN " . TABLE_DATA_ETL . " etl
                         ON etl.id_station = $station_chron
                         AND da.dateheure >= etl.datetime_first
                         AND da.dateheure <  etl.datetime_end
                     WHERE dm.id_station = $station_chron
                     AND dm.id_typedata = $typedata_chron_h
                     AND da.dateheure $operator '$date_cursor'
                     AND da.dateheure <= '$newCursorDate'
                     AND da.valeur >  -8888
                     AND etl.id IS NULL";
$r = tep_db_fetch_array(tep_db_query($sql_link, $sql_count_nocov));
$nb_nocov = (int)($r['n'] ?? 0);

// 2) above — H > Hmax of the ETL valid at this date
//    Hmax of an ETL = MAX(hauteur) over its gauging points (TABLE_DATA_ETL_DATA).
$sql_count_above = " SELECT COUNT(*) AS n
                     FROM " . TABLE_DATA_ALL . " da
                     JOIN " . TABLE_DATA_META . " dm ON da.id_meta = dm.id
                     JOIN " . TABLE_DATA_ETL . " etl
                         ON etl.id_station = $station_chron
                         AND da.dateheure >= etl.datetime_first
                         AND da.dateheure <  etl.datetime_end
                     JOIN (
                         SELECT id_etl, MAX(hauteur) AS h_max, MIN(hauteur) AS h_min
                         FROM " . TABLE_DATA_ETL_DATA . "
                         GROUP BY id_etl
                     ) edx ON edx.id_etl = etl.id
                     WHERE dm.id_station = $station_chron
                     AND dm.id_typedata = $typedata_chron_h
                     AND da.dateheure $operator '$date_cursor'
                     AND da.dateheure <= '$newCursorDate'
                     AND da.valeur >  -8888
                     AND da.valeur >= edx.h_max";
$r = tep_db_fetch_array(tep_db_query($sql_link, $sql_count_above));
$nb_above = (int)($r['n'] ?? 0);

// 3) below — H < Hmin of the ETL valid at this date
$sql_count_below = " SELECT COUNT(*) AS n
                     FROM " . TABLE_DATA_ALL . " da
                     JOIN " . TABLE_DATA_META . " dm ON da.id_meta = dm.id
                     JOIN " . TABLE_DATA_ETL . " etl
                         ON etl.id_station = $station_chron
                         AND da.dateheure >= etl.datetime_first
                         AND da.dateheure <  etl.datetime_end
                     JOIN (
                         SELECT id_etl, MAX(hauteur) AS h_max, MIN(hauteur) AS h_min
                         FROM " . TABLE_DATA_ETL_DATA . "
                         GROUP BY id_etl
                     ) edx ON edx.id_etl = etl.id
                     WHERE dm.id_station = $station_chron
                     AND dm.id_typedata = $typedata_chron_h
                     AND da.dateheure $operator '$date_cursor'
                     AND da.dateheure <= '$newCursorDate'
                     AND da.valeur >  -8888
                     AND da.valeur <  edx.h_min";
$r = tep_db_fetch_array(tep_db_query($sql_link, $sql_count_below));
$nb_below = (int)($r['n'] ?? 0);


echo json_encode([
    'remaining'          => $remaining,
    'newCursorDate'      => $newCursorDate,
    'id_meta_correction' => $id_meta_new,
    'nb_etl'             => $nb_etl,         // first batch only (0 after)
    'nb_segments'        => $nb_segments,    // first batch only (0 after)
    'nb_converted'       => $nb_converted,
    'nb_gaps_source'     => $nb_gaps_source,
    'nb_above'           => $nb_above,
    'nb_below'           => $nb_below,
    'nb_nocov'           => $nb_nocov,
], JSON_UNESCAPED_UNICODE);
?>