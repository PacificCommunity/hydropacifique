<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Station photo upload handler
- Saves a station photo uploaded from form_station_photos.php
- Validates date format, file extension and file size before saving
- Registers the new photo in TABLE_STATION_PHOTOS
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
// Read POST input

$id_station = $_POST['id_station'];
$desc_photo = $_POST['desc_photo'];
$date_photo = $_POST['date_photo'];


// -----------------------------------------------
// Initialize variables

$upload_valid     = true;
$message_info     = '';
$date_photo_us    = '';
$date_format      = 'd-m-Y';
$valid_extensions = ['jpeg', 'jpg', 'png'];
$max_file_size    = 4 * 1024 * 1024; // 4 MB in bytes
$chemin_dossier   = '../../../' . DIR_WS_DATA_PHOTOS;


// -----------------------------------------------
// Query: Station code (used to build filename)

$sql_station   = "SELECT DISTINCT s.id_station, s.nom_station, s.code_station
                  FROM " . TABLE_STATION . " s
                  WHERE s.id_station = " . $id_station;
$station_query = tep_db_query($sql_link, $sql_station);
$station_tab   = tep_db_fetch_array($station_query);
$code_station  = $station_tab['code_station'];


// -----------------------------------------------
// Query: Last photo ID for this station (used to increment filename)

$sql_num_photos   = "SELECT id FROM " . TABLE_STATION_PHOTOS
                  . " WHERE id_station = " . $id_station
                  . " ORDER BY id DESC LIMIT 1";
$num_photos_query = tep_db_query($sql_link, $sql_num_photos);
$num_photos_data  = tep_db_fetch_array($num_photos_query);
$num_photos       = isset($num_photos_data['id']) ? $num_photos_data['id'] : 0;

$name_photo = $code_station . '_' . ($num_photos + 1); // e.g. STN001_3


// -----------------------------------------------
// Validate date format (dd-mm-yyyy) if provided

if (tep_not_null($date_photo))
{
    $date_photo_format = DateTime::createFromFormat($date_format, $date_photo);

    if ($date_photo_format && $date_photo_format->format($date_format) === $date_photo)
    {
        $date_photo_us = $date_photo_format->format('Y-m-d'); // Convert to DB format
    }
    else
    {
        $upload_valid  = false;
        $message_info .= TEXT_PHOTO_ERR_DATE;
    }
}


// -----------------------------------------------
// File upload validation and save

if ($upload_valid)
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file_photo']))
    {
        $file_name      = $_FILES['file_photo']['name'];
        $file_tmp       = $_FILES['file_photo']['tmp_name'];
        $file_size      = $_FILES['file_photo']['size'];
        $file_error     = $_FILES['file_photo']['error'];
        $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);

        // ---- Validate file extension ----
        if (!in_array(strtolower($file_extension), $valid_extensions))
        {
            $upload_valid  = false;
            if (tep_not_null($message_info)) { $message_info .= '<br>'; }
            $message_info .= TEXT_PHOTO_ACCESS_ERR_FORMAT;
        }

        // ---- Validate file size ----
        if ($file_size > $max_file_size)
        {
            $upload_valid  = false;
            if (tep_not_null($message_info)) { $message_info .= '<br>'; }
            $message_info .= TEXT_PHOTO_ACCESS_ERR_SIZE;
        }

        // ---- Move file and register in database ----
        if ($upload_valid && $file_error === UPLOAD_ERR_OK)
        {
            $file_name_new = $name_photo . '.' . strtolower($file_extension);
            $destination   = $chemin_dossier . $file_name_new;
            move_uploaded_file($file_tmp, $destination);

            $query_newphoto = "INSERT INTO " . TABLE_STATION_PHOTOS
                            . " (id_station, date_photo, description_photo, file_photo)"
                            . " VALUES ('$id_station', '$date_photo_us', '$desc_photo', '$file_name_new')";
            tep_db_query($sql_link, $query_newphoto);

            if (tep_not_null($message_info)) { $message_info .= '<br>'; }
            $message_info .= TEXT_PHOTO_ACCESS_SUCCESS;
        }
        else
        {
            $upload_valid = false;
            if (tep_not_null($message_info)) { $message_info .= '<br>-<br>'; }
            $message_info .= TEXT_PHOTO_ACCESS_ERR_UPLOAD;
        }
    }
    else
    {
        // No file uploaded or wrong request method
        $upload_valid = false;
        if (tep_not_null($message_info)) { $message_info .= '<br>'; }
        $message_info .= TEXT_PHOTO_ACCESS_ERR_NO_FILE;
    }
}

// Return a JSON payload so the client can pick the right border colour
// (green on success, red on error). $upload_valid is true only when the
// file passed every check and was saved.
header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'success'      => $upload_valid,
    'message_info' => $message_info,
]);
?>