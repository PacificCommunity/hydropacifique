<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
AJAX endpoint — save edited points AND period of an existing RC
Receives JSON: {
    idUser, todayTimeFormatted,
    id, idStation,
    date1, heure1, date2, heure2,        // new period
    points:    [ {h, q}, ... ],
    conflicts: [ {id, action}, ... ]     // user-validated conflict resolutions
                                         //   for OTHER RCs whose period collides
                                         //   with the new period of this RC
}

Workflow:
1. Validate inputs (period format, points count, station match)
2. Re-check current conflicts against the database, excluding the RC
    being edited from the scan; refuse if a new (unaccepted) one appeared
    since the user's check (concurrent edit guard)
3. Apply each conflict resolution (delete / truncate_right / truncate_left)
    to the OTHER RCs
4. Update the RC header (datetime_first / datetime_end)
5. Replace the RC's curve points (DELETE then INSERT)
6. Log the action

Returns JSON: { valid_process, js_text }
----------------------------------------
*/

require('../../config.php');
require('../../database_tables.php');

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
$id_user            = isset($data['idUser'])             ? (int)$data['idUser']        : 0;
$todayTimeFormatted = isset($data['todayTimeFormatted']) ? $data['todayTimeFormatted'] : '';
$id                 = isset($data['id'])                 ? (int)$data['id']            : 0;
$id_station         = isset($data['idStation'])          ? (int)$data['idStation']     : 0;
$date1              = isset($data['date1'])              ? $data['date1']              : '';
$date2              = isset($data['date2'])              ? $data['date2']              : '';
$heure1             = isset($data['heure1'])             ? $data['heure1']             : '00:00:00';
$heure2             = isset($data['heure2'])             ? $data['heure2']             : '23:59:59';
$points             = isset($data['points'])    && is_array($data['points'])    ? $data['points']    : [];
$accepted_conflicts = isset($data['conflicts']) && is_array($data['conflicts']) ? $data['conflicts'] : [];

// ---- Defensive validation ----

if ($id <= 0) {
    echo json_encode(['valid_process' => false, 'js_text' => 'Invalid RC id']);
    exit;
}
if (count($points) < 2) {
    echo json_encode(['valid_process' => false, 'js_text' => 'Need at least 2 points']);
    exit;
}
if (!preg_match('/^\d{2}-\d{2}-\d{4}$/', $date1)
 || !preg_match('/^\d{2}-\d{2}-\d{4}$/', $date2))
{
    echo json_encode(['valid_process' => false, 'js_text' => 'Invalid date format']);
    exit;
}

// Defensive: verify the RC belongs to the expected station
$check = tep_db_fetch_array(tep_db_query($sql_link,
    "SELECT id_station FROM " . TABLE_DATA_ETL . " WHERE id=$id"));
if (!$check || (int)$check['id_station'] !== $id_station) {
    echo json_encode(['valid_process' => false, 'js_text' => 'Station mismatch']);
    exit;
}

$datetime1 = datefr_us($date1) . ' ' . $heure1;
$datetime2 = datefr_us($date2) . ' ' . $heure2;

$dt1_safe = mysqli_real_escape_string($sql_link, $datetime1);
$dt2_safe = mysqli_real_escape_string($sql_link, $datetime2);

// ---- Concurrent-edit guard: re-check overlaps right now ----
// Same as in process_etl_new_save.php, but we EXCLUDE the RC being
// edited (id=$id) — it would otherwise flag itself as a conflict
// against its own previous period.
$accepted_ids = [];
foreach ($accepted_conflicts as $c) {
    if (isset($c['id'])) { $accepted_ids[(int)$c['id']] = true; }
}

$check_query = tep_db_query($sql_link,
    "SELECT id FROM " . TABLE_DATA_ETL
    . " WHERE id_station = $id_station"
    . " AND id <> $id"
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

// ---- Apply conflict resolutions to OTHER RCs ----
// Same logic as in process_etl_new_save.php — re-derive the action
// from each conflict's current period rather than trusting the client.
foreach ($accepted_conflicts as $c) {
    if (!isset($c['id'])) continue;
    $cid = (int) $c['id'];
    if ($cid === $id) continue; // safety: never resolve against self

    $row = tep_db_fetch_array(tep_db_query($sql_link,
        "SELECT datetime_first, datetime_end FROM " . TABLE_DATA_ETL . " WHERE id=$cid"));
    if (!$row) continue;
    $old_start = $row['datetime_first'];
    $old_end   = $row['datetime_end'];

    if ($old_start >= $datetime1 && $old_end <= $datetime2)
    {
        // Fully inside the new period → delete it (and its points)
        tep_db_query($sql_link, "DELETE FROM " . TABLE_DATA_ETL_DATA . " WHERE id_etl=$cid");
        tep_db_query($sql_link, "DELETE FROM " . TABLE_DATA_ETL      . " WHERE id    =$cid");
    }
    elseif ($old_start < $datetime1 && $old_end > $datetime2)
    {
        // New period fully inside another RC — same refusal as creation.
        echo json_encode([
            'valid_process' => false,
            'js_text'       => 'Cannot place an RC fully inside another',
        ]);
        exit;
    }
    elseif ($old_start < $datetime1 && $old_end <= $datetime2)
    {
        // Truncate right: shorten old RC's end to right before the new starts.
        $new_end = date('Y-m-d H:i:s', strtotime($datetime1) - 1);
        $ne = mysqli_real_escape_string($sql_link, $new_end);
        tep_db_query($sql_link,
            "UPDATE " . TABLE_DATA_ETL . " SET datetime_end='$ne' WHERE id=$cid");
    }
    elseif ($old_start >= $datetime1 && $old_end > $datetime2)
    {
        // Truncate left: shift old RC's start to right after the new ends.
        $new_start = date('Y-m-d H:i:s', strtotime($datetime2) + 1);
        $ns = mysqli_real_escape_string($sql_link, $new_start);
        tep_db_query($sql_link,
            "UPDATE " . TABLE_DATA_ETL . " SET datetime_first='$ns' WHERE id=$cid");
    }
    // else: not actually overlapping — skip
}

// ---- Update the RC header (new period) ----
$update_ok = tep_db_query($sql_link,
    "UPDATE " . TABLE_DATA_ETL
    . " SET datetime_first='$dt1_safe', datetime_end='$dt2_safe'"
    . " WHERE id=$id");

if (!$update_ok) {
    echo json_encode(['valid_process' => false, 'js_text' => 'Failed to update RC header']);
    exit;
}

// ---- Replace all curve points: DELETE then INSERT ----
tep_db_query($sql_link, "DELETE FROM " . TABLE_DATA_ETL_DATA . " WHERE id_etl=$id");

foreach ($points as $p) {
    if (!isset($p['h']) || !isset($p['q'])) continue;
    $h = (float) $p['h'];
    $q = (float) $p['q'];
    tep_db_query($sql_link,
        "INSERT INTO " . TABLE_DATA_ETL_DATA . " (id_etl, hauteur, debit)"
        . " VALUES ($id, '$h', '$q')");
}

// ---- Log the action — type 33 = "Rating curve - RC" ----
$nb_conflicts  = count($accepted_conflicts);
$conflict_note = $nb_conflicts > 0 ? " (resolved $nb_conflicts conflict(s))" : '';
$info_action   = "RC edited - Station: $id_station"
               . " - RC id: $id"
               . " - Period: $datetime1 -> $datetime2"
               . " - Points: " . count($points)
               . $conflict_note;
$info_safe  = mysqli_real_escape_string($sql_link, $info_action);
$today_safe = mysqli_real_escape_string($sql_link, $todayTimeFormatted);
tep_db_query($sql_link,
    "INSERT INTO " . TABLE_ACTIONS . " (id_user, type_action, info, dateheure)"
    . " VALUES ($id_user, 33, '$info_safe', '$today_safe')");

echo json_encode([
    'valid_process' => true,
    'js_text'       => 'RC updated',
], JSON_UNESCAPED_UNICODE);
?>