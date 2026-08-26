<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Correction delete handler - AJAX server-side process
- Deletes a correction entry from TABLE_DATA_META_CORRECTION
  and its data from TABLE_DATA_ALL_CORRECTION
- Called from graph_correct_chron.php
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

$dataInfo = json_decode(file_get_contents('php://input'), true);
$id_meta  = $dataInfo['id_meta'];


// -----------------------------------------------
// Initialize variables

$id_correction = 0;
$msg_del       = '';


// -----------------------------------------------
// Query: Retrieve correction entry before deleting

$meta_correction_tab = tep_db_fetch_array(tep_db_query($sql_link,
    "SELECT id, obs, id_correction, datetime_first, datetime_end
     FROM " . TABLE_DATA_META_CORRECTION . " WHERE id = " . $id_meta));


// -----------------------------------------------
// Delete data and meta records

if (isset($meta_correction_tab))
{
    $id_correction = $meta_correction_tab['id_correction'];

    $datetime_first_tab      = explode(' ', $meta_correction_tab['datetime_first']);
    $datetime_first_formated = dateus_fr($datetime_first_tab[0]) . ' ' . $datetime_first_tab[1];

    $datetime_end_tab      = explode(' ', $meta_correction_tab['datetime_end']);
    $datetime_end_formated = dateus_fr($datetime_end_tab[0]) . ' ' . $datetime_end_tab[1];

    tep_db_query($sql_link, "DELETE FROM " . TABLE_DATA_ALL_CORRECTION . " WHERE id_meta = " . $id_meta);
    tep_db_query($sql_link, "DELETE FROM " . TABLE_DATA_META_CORRECTION . " WHERE id = "     . $id_meta);

    $msg_del = sprintf(TEXT_CALCUL_DEL_SUCCESS,
        $meta_correction_tab['obs'],
        $datetime_first_formated,
        $datetime_end_formated);
}
else
{
    $msg_del = TEXT_CALCUL_DEL_FAIL;
}


// -----------------------------------------------
// Return result as JSON

echo json_encode([
    'id_correction' => $id_correction,
    'msg_del'       => $msg_del,
]);
?>
