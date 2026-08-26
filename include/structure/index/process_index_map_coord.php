<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
AJAX endpoint — save map viewport coordinates
Persists the user's current map zoom level and centre coordinates
(longitude / latitude) to TABLE_USER_COORD using INSERT … ON DUPLICATE KEY UPDATE.
Called from include/structure/box/nav_accueil.php.
Receives JSON: idUser, mapZoom, mapLong, mapLat.
Returns nothing (no response body needed).
----------------------------------------
*/

require('../../config.php');
require('../../database_tables.php');

require('../../function/date.php');
require('../../function/database.php');
require('../../function/html_output.php');
require('../../function/general.php');

header('Content-Type: text/html; charset=utf-8');

$sql_link = mysqli_connect(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD, DB_DATABASE)
    or die('Cannot connect to the database');
mysqli_query($sql_link, 'SET NAMES UTF8');

$data     = json_decode(file_get_contents('php://input'), true);
$id_user  = $data['idUser'];
$map_zoom = $data['mapZoom'];
$map_long = $data['mapLong'];
$map_lat  = $data['mapLat'];

$sql_coord = "INSERT INTO " . TABLE_USER_COORD . " (id_user, map_zoom, map_long, map_lat)
              VALUES (?, ?, ?, ?)
              ON DUPLICATE KEY UPDATE
                  map_zoom = VALUES(map_zoom),
                  map_long = VALUES(map_long),
                  map_lat  = VALUES(map_lat)";

$stmt = $sql_link->prepare($sql_coord);
if ($stmt)
{
    $stmt->bind_param("iddd", $id_user, $map_zoom, $map_long, $map_lat);
    $stmt->execute();
    $stmt->close();
}

$sql_link->close();
?>
