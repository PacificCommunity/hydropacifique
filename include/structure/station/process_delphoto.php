<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Station photo delete handler - AJAX server-side process
- Called from include/structure/form_station_photos.php
- Deletes the photo record from TABLE_STATION_PHOTOS and removes the file from disk
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

$dataInfo = json_decode(file_get_contents('php://input'), true);
$id_photo = isset($dataInfo['id_photo']) ? (int) $dataInfo['id_photo'] : 0;


// -----------------------------------------------
// Initialize variables

$dossier_photos = '../../../' . DIR_WS_DATA_PHOTOS;
$image_path     = '';
$del_img        = true;


// -----------------------------------------------
// Query: Retrieve photo record

$sql_photo   = "SELECT DISTINCT id, id_station, date_photo, file_photo
                FROM " . TABLE_STATION_PHOTOS . "
                WHERE id = " . $id_photo;
$photo_query = tep_db_query($sql_link, $sql_photo);
$photo_tab   = tep_db_fetch_array($photo_query);


// -----------------------------------------------
// Delete database record and file

if (isset($photo_tab))
{
    $image_path = $dossier_photos . $photo_tab['file_photo'];

    tep_db_query($sql_link, "DELETE FROM " . TABLE_STATION_PHOTOS . " WHERE id = " . $id_photo);
}
else
{
    $del_img = false;
}

// Delete the physical file if it exists
if (is_file($image_path)) { unlink($image_path); }
else                       { $del_img = false; }


// -----------------------------------------------
// Return result message to AJAX caller

echo $del_img
    ? sprintf(TEXT_PHOTO_DELETE_SUCCESS, $photo_tab['file_photo'])
    : TEXT_PHOTO_DELETE_FAIL;
?>