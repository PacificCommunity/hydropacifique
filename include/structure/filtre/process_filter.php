<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
AJAX endpoint: saves a user filter to TABLE_USER_FILTER
Receives: { id_user, filter_id, filter_value }
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


// Validate required fields
if (empty($input['filter_id']) || !isset($input['id_user'], $input['filter_value']))
{
    echo json_encode(['status' => 'error', 'message' => 'Missing parameters']);
    exit;
}

$id_user      = (int)$input['id_user'];
$filter_id    = preg_replace('/[^a-z_]/', '', $input['filter_id']); // allow only a-z and _
$filter_value = substr(strip_tags($input['filter_value']), 0, 255);

// Upsert the filter value for this user
$sql = "INSERT INTO " . TABLE_USER_FILTER . " (id_user, filter_id, filter_value)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE filter_value = VALUES(filter_value)";

$stmt = $sql_link->prepare($sql);
if ($stmt)
{
    $stmt->bind_param("iss", $id_user, $filter_id, $filter_value);
    $stmt->execute();
    $stmt->close();
    echo json_encode(['status' => 'ok']);
}
else
{
    echo json_encode(['status' => 'error', 'message' => 'Query failed']);
}
exit;