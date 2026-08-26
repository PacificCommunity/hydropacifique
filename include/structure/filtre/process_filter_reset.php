<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
AJAX endpoint: resets the user's station filters to their default state.
Deletes the saved filter rows in TABLE_USER_FILTER so that, on reload,
filtre_stations_var.php falls back to its built-in defaults
(Status = Active, everything else = All).

Data Owner (from_services) is intentionally preserved, as are any
non-filter user preferences (e.g. info_base_prefs / map layers).

Receives: { id_user }
----------------------------------------
*/

require('../../config.php');
require('../../database_tables.php');

require('../../function/date.php');
require('../../function/database.php');
require('../../function/html_output.php');
require('../../function/general.php');

header('Content-Type: application/json');

// Database connection
$sql_link = mysqli_connect(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD, DB_DATABASE)
    or die('Impossible de se connecter à la base de données!');
mysqli_query($sql_link, 'SET NAMES UTF8');

// -----------------------------------------------
// Load translation strings for the active language
require('../../text_content_' . LANGUAGE . '.php');

// Retrieve JSON data sent from the AJAX request
$input = json_decode(file_get_contents('php://input'), true);

// Validate required field
if (!isset($input['id_user']))
{
    echo json_encode(['status' => 'error', 'message' => 'Missing parameters']);
    exit;
}

$id_user = (int)$input['id_user'];

// Whitelist of filter ids to reset. Data Owner (from_services) and any
// non-filter preference (info_base_prefs, ...) are deliberately excluded.
$filters_to_reset = [
    'select_type_data',
    'select_region',
    'select_commune',
    'select_tournee',
    'select_regionhydro',
    'select_riviere',
    'select_station',
    'select_active',
    'select_suivi',
    'select_armee',
];

// Build the placeholder list ( ?, ?, ... ) for the IN clause
$placeholders = implode(',', array_fill(0, count($filters_to_reset), '?'));

$sql = "DELETE FROM " . TABLE_USER_FILTER . "
        WHERE id_user = ?
        AND filter_id IN (" . $placeholders . ")";

$stmt = $sql_link->prepare($sql);
if ($stmt)
{
    // Bind: first the user id (i), then each filter id (s)
    $types  = 'i' . str_repeat('s', count($filters_to_reset));
    $params = array_merge([$id_user], $filters_to_reset);
    $stmt->bind_param($types, ...$params);

    $stmt->execute();
    $stmt->close();
    echo json_encode(['status' => 'ok']);
}
else
{
    echo json_encode(['status' => 'error', 'message' => 'Query failed']);
}
exit;