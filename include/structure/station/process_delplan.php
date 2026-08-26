<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Access map delete handler - AJAX server-side process
- Deletes the access map image for the given station code
- Tries jpg, jpeg, png extensions
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


// -----------------------------------------------
// Load translation strings for the active language

require('../../text_content_' . LANGUAGE . '.php');


// -----------------------------------------------
// Parse JSON input from AJAX request

$dataInfo     = json_decode(file_get_contents('php://input'), true);
$code_station = isset($dataInfo['code_station']) ? $dataInfo['code_station'] : '';

// Sanitize: code_station is used to build a file path, so strip anything that
// is not a safe station-code character (prevents path traversal like ../).
$code_station = preg_replace('/[^A-Za-z0-9_\-]/', '', $code_station);


// -----------------------------------------------
// Locate and delete the access map file

$dossier_plan = '../../../' . DIR_WS_STATION_PHOTO_ACCESS;
$basePath     = $dossier_plan . $code_station . '_access';
$extensions   = ['jpg', 'jpeg', 'png'];
$del_img      = false;

foreach ($extensions as $ext)
{
    $filePath = $basePath . '.' . $ext;

    if (is_file($filePath))
    {
        unlink($filePath);
        $del_img = true;
        break; // Stop at first matching extension
    }
}


// -----------------------------------------------
// Return result message to AJAX caller

echo $del_img ? TEXT_PLAN_DELETE_SUCCESS : TEXT_PLAN_DELETE_FAIL;
?>