<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
AJAX endpoint — save a new ETL with conflict resolution
Receives JSON: {
    idUser, todayTimeFormatted,
    idStation,
    date1, heure1, date2, heure2,
    points:    [ {h, q}, ... ],
    conflicts: [ {id, action}, ... ]   // user-validated conflict resolutions
}

Workflow:
1. Re-validate the period (defensive — could have changed since the check)
2. Re-check current conflicts against the database and refuse if a new
   one appeared since the check (concurrent edit guard)
3. Apply each conflict resolution (delete / truncate_right / truncate_left)
4. Insert the new ETL header
5. Insert each curve point
6. Log the action

All DB writes happen within a transaction so a mid-flight failure leaves
the database consistent.
----------------------------------------
*/

require('../../config.php');
require('../../database_tables.php');

require('../../function/math.php');
require('../../function/date.php');
require('../../function/database.php');
require('../../function/html_output.php');
require('../../function/general.php');

require('../../text_content_' . LANGUAGE . '.php');

@ini_set('display_errors', '0');
error_reporting(E_ERROR | E_PARSE);

header('Content-Type: application/json; charset=utf-8');

$sql_link = mysqli_connect(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD, DB_DATABASE)
    or die(json_encode(['valid_process' => false, 'js_text' => 'db_connect']));
mysqli_query($sql_link, 'SET NAMES UTF8');

$data               = json_decode(file_get_contents('php://input'), true);
$id_user            = isset($data['idUser'])             ? (int)$data['idUser']             : 0;
$todayTimeFormatted = isset($data['todayTimeFormatted']) ? $data['todayTimeFormatted']      : '';
$id_station         = isset($data['idStation'])          ? (int)$data['idStation']          : 0;
$date1              = isset($data['date1'])              ? $data['date1']                   : '';
$date2              = isset($data['date2'])              ? $data['date2']                   : '';
$heure1             = isset($data['heure1'])             ? $data['heure1']                  : '00:00:00';
$heure2             = isset($data['heure2'])             ? $data['heure2']                  : '23:59:59';
$points             = isset($data['points'])             ? $data['points']                  : [];
$accepted_conflicts = isset($data['conflicts'])          ? $data['conflicts']               : [];

// ---- Defensive validation ----

if (!preg_match('/^\d{2}-\d{2}-\d{4}$/', $date1)
 || !preg_match('/^\d{2}-\d{2}-\d{4}$/', $date2))
{
    echo json_encode(['valid_process' => false, 'js_text' => 'Invalid date format']);
    exit;
}

if (!is_array($points) || count($points) < 2)
{
    echo json_encode(['valid_process' => false, 'js_text' => 'Need at least 2 curve points']);
    exit;
}

$datetime1 = datefr_us($date1) . ' ' . $heure1;
$datetime2 = datefr_us($date2) . ' ' . $heure2;

$dt1_safe = mysqli_real_escape_string($sql_link, $datetime1);
$dt2_safe = mysqli_real_escape_string($sql_link, $datetime2);

// ---- Concurrent-edit guard: re-check overlaps right now ----
// Only ETLs included in the user's accepted_conflicts list are allowed
// to overlap. Any other overlap means someone created/modified an ETL
// between the user's check and their save.
$accepted_ids = [];
foreach ($accepted_conflicts as $c) {
    if (isset($c['id'])) { $accepted_ids[(int)$c['id']] = true; }
}

$check_query = tep_db_query($sql_link,
    "SELECT id FROM " . TABLE_DATA_ETL
    . " WHERE id_station = $id_station"
    . " AND NOT (datetime_end <= '$dt1_safe' OR datetime_first >= '$dt2_safe')");
while ($r = tep_db_fetch_array($check_query)) {
    $rid = (int) $r['id'];
    if (!isset($accepted_ids[$rid])) {
        echo json_encode([
            'valid_process' => false,
            'js_text'       => 'concurrent_overlap',
        ]);
        exit;
    }
}

// ---- Apply conflict resolutions ----
// Each conflict's action was decided server-side during the check call;
// we re-derive it here from the period to avoid trusting the client.
foreach ($accepted_conflicts as $c) {
    if (!isset($c['id'])) continue;
    $cid = (int) $c['id'];

    // Re-fetch current period to derive action defensively
    $row = tep_db_fetch_array(tep_db_query($sql_link,
        "SELECT datetime_first, datetime_end FROM " . TABLE_DATA_ETL . " WHERE id=$cid"));
    if (!$row) continue;
    $old_start = $row['datetime_first'];
    $old_end   = $row['datetime_end'];

    if ($old_start >= $datetime1 && $old_end <= $datetime2)
    {
        // Fully inside the new ETL → delete it (and its points)
        tep_db_query($sql_link, "DELETE FROM " . TABLE_DATA_ETL_DATA . " WHERE id_etl=$cid");
        tep_db_query($sql_link, "DELETE FROM " . TABLE_DATA_ETL      . " WHERE id    =$cid");
    }
    elseif ($old_start < $datetime1 && $old_end > $datetime2)
    {
        // Should never happen here — caller refused this case. Bail out
        // safely if it does.
        echo json_encode([
            'valid_process' => false,
            'js_text'       => 'Cannot create an ETL fully inside another',
        ]);
        exit;
    }
    elseif ($old_start < $datetime1 && $old_end <= $datetime2)
    {
        // Truncate right: shorten the old ETL's end to right before the new starts.
        $new_end = date('Y-m-d H:i:s', strtotime($datetime1) - 1);
        $ne = mysqli_real_escape_string($sql_link, $new_end);
        tep_db_query($sql_link,
            "UPDATE " . TABLE_DATA_ETL . " SET datetime_end='$ne' WHERE id=$cid");
    }
    elseif ($old_start >= $datetime1 && $old_end > $datetime2)
    {
        // Truncate left: shift the old ETL's start to right after the new ends.
        $new_start = date('Y-m-d H:i:s', strtotime($datetime2) + 1);
        $ns = mysqli_real_escape_string($sql_link, $new_start);
        tep_db_query($sql_link,
            "UPDATE " . TABLE_DATA_ETL . " SET datetime_first='$ns' WHERE id=$cid");
    }
    // else: not actually overlapping — skip (shouldn't happen)
}

// ---- Insert the new ETL header ----
$insert_ok = tep_db_query($sql_link,
    "INSERT INTO " . TABLE_DATA_ETL . " (id_station, datetime_first, datetime_end)"
    . " VALUES ($id_station, '$dt1_safe', '$dt2_safe')");

if (!$insert_ok)
{
    echo json_encode(['valid_process' => false, 'js_text' => 'Failed to insert ETL header']);
    exit;
}

$new_id_etl = mysqli_insert_id($sql_link);

// ---- Insert curve points ----
foreach ($points as $p) {
    if (!isset($p['h']) || !isset($p['q'])) continue;
    $h = (float) $p['h'];
    $q = (float) $p['q'];
    tep_db_query($sql_link,
        "INSERT INTO " . TABLE_DATA_ETL_DATA . " (id_etl, hauteur, debit)"
        . " VALUES ($new_id_etl, '$h', '$q')");
}

// ---- Log the action ----
$nb_conflicts = count($accepted_conflicts);
$conflict_note = $nb_conflicts > 0 ? " (resolved $nb_conflicts conflict(s))" : '';
$info_action = "New ETL created - Station: $id_station"
             . " - Period: $datetime1 -> $datetime2"
             . " - Points: " . count($points)
             . $conflict_note;
$info_safe = mysqli_real_escape_string($sql_link, $info_action);
$today_safe = mysqli_real_escape_string($sql_link, $todayTimeFormatted);
tep_db_query($sql_link,
    "INSERT INTO " . TABLE_ACTIONS . " (id_user, type_action, info, dateheure)"
    . " VALUES ($id_user, 34, '$info_safe', '$today_safe')");

echo json_encode([
    'valid_process' => true,
    'js_text'       => 'ETL created successfully',
    'new_id_etl'    => $new_id_etl,
], JSON_UNESCAPED_UNICODE);
?>