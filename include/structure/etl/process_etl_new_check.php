<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
AJAX endpoint — check ETL period conflicts before saving a new ETL
Receives JSON: { idStation, date1, heure1, date2, heure2 }
Returns JSON: {
    conflicts:        [ {id, num, datetime_first, datetime_end, action}, ... ],
    blocking:         bool,    // true if the new ETL falls entirely inside
                               // an existing one — we refuse this case
    blocking_reason:  string
}

Conflict actions:
- 'delete'         : existing ETL fully inside the new one → drop it
- 'truncate_right' : existing ETL's right edge overlaps → shorten its end
- 'truncate_left'  : existing ETL's left edge overlaps  → shift its start
- 'blocking'       : new ETL is fully inside this existing one → refused
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
    or die(json_encode(['conflicts' => [], 'blocking' => false, 'error' => 'db_connect']));
mysqli_query($sql_link, 'SET NAMES UTF8');

$data       = json_decode(file_get_contents('php://input'), true);
$id_station = isset($data['idStation']) ? (int)$data['idStation'] : 0;
$date1      = isset($data['date1'])     ? $data['date1']          : '';
$date2      = isset($data['date2'])     ? $data['date2']          : '';
$heure1     = isset($data['heure1'])    ? $data['heure1']         : '00:00:00';
$heure2     = isset($data['heure2'])    ? $data['heure2']         : '23:59:59';
// excludeId — when called from the Edit popup, this is the id of the RC
// being edited; it must be excluded from the conflict scan so the RC
// doesn't flag itself as a conflict. 0/absent = New popup, no exclusion.
$exclude_id = isset($data['excludeId']) ? (int)$data['excludeId'] : 0;

if (!preg_match('/^\d{2}-\d{2}-\d{4}$/', $date1)
 || !preg_match('/^\d{2}-\d{2}-\d{4}$/', $date2))
{
    echo json_encode(['conflicts' => [], 'blocking' => false, 'error' => 'bad_format']);
    exit;
}

$datetime1 = datefr_us($date1) . ' ' . $heure1;
$datetime2 = datefr_us($date2) . ' ' . $heure2;

// Fetch all existing ETLs for this station that overlap the new period.
// Overlap formula: NOT (end <= new_start OR start >= new_end) — same as
// the existing modules.
$conflicts = [];
$blocking  = false;
$blocking_reason = '';

$dt1_safe = mysqli_real_escape_string($sql_link, $datetime1);
$dt2_safe = mysqli_real_escape_string($sql_link, $datetime2);

// When called from Edit, exclude the RC being edited from the scan.
$exclude_clause = $exclude_id > 0 ? " AND id <> $exclude_id" : '';

$sql = "SELECT id, datetime_first, datetime_end
        FROM " . TABLE_DATA_ETL . "
        WHERE id_station = $id_station
        $exclude_clause
        AND NOT (datetime_end <= '$dt1_safe' OR datetime_first >= '$dt2_safe')
        ORDER BY datetime_first ASC";

$query = tep_db_query($sql_link, $sql);

// We also need ETL numbering (Ref) — same as modif_etl uses: ordered by
// datetime_end DESC across the full station. Build a num map.
$num_map = [];
$num_query = tep_db_query($sql_link,
    "SELECT id FROM " . TABLE_DATA_ETL
    . " WHERE id_station=$id_station ORDER BY datetime_end DESC");
$counter = 0;
while ($r = tep_db_fetch_array($num_query)) {
    $counter++;
    $num_map[$r['id']] = $counter;
}

while ($row = tep_db_fetch_array($query))
{
    $old_start = $row['datetime_first'];
    $old_end   = $row['datetime_end'];

    $action    = '';
    $new_first = $old_start; // post-resolution start (defaults to unchanged)
    $new_end   = $old_end;   // post-resolution end   (defaults to unchanged)

    if      ($old_start >= $datetime1 && $old_end <= $datetime2)
    {
        // Fully inside the new ETL → will be deleted
        $action    = 'delete';
        $new_first = null;
        $new_end   = null;
    }
    elseif  ($old_start <  $datetime1 && $old_end >  $datetime2)
    {
        // New ETL is fully inside this one → blocking
        $action = 'blocking';
    }
    elseif  ($old_start <  $datetime1 && $old_end <= $datetime2)
    {
        // Right edge of old overlaps → truncate end to (new_start - 1 second)
        $action  = 'truncate_right';
        $new_end = date('Y-m-d H:i:s', strtotime($datetime1) - 1);
    }
    elseif  ($old_start >= $datetime1 && $old_end >  $datetime2)
    {
        // Left edge of old overlaps → shift start to (new_end + 1 second)
        $action    = 'truncate_left';
        $new_first = date('Y-m-d H:i:s', strtotime($datetime2) + 1);
    }

    $conflict = [
        'id'             => (int) $row['id'],
        'num'            => isset($num_map[$row['id']]) ? $num_map[$row['id']] : 0,
        'datetime_first' => $old_start,
        'datetime_end'   => $old_end,
        'new_first'      => $new_first, // null if action = 'delete'
        'new_end'        => $new_end,   // null if action = 'delete'
        'action'         => $action,
    ];

    if ($action === 'blocking') {
        $blocking = true;
        $blocking_reason = $row['id'];
    }

    $conflicts[] = $conflict;
}

echo json_encode([
    'conflicts'       => $conflicts,
    'blocking'        => $blocking,
    'blocking_reason' => $blocking_reason,
], JSON_UNESCAPED_UNICODE);
?>