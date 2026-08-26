<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Daily Statistics — Year Selector Module
Generates the year selection dropdown and navigation arrows
for the daily statistics view
----------------------------------------
*/

// ----------------------------------------------
// Required files for script configuration

require('../../config.php');
require('../../database_tables.php');

require('../../function/date.php');
require('../../function/database.php');
require('../../function/html_output.php');
require('../../function/general.php');

require('../../function/math.php');

// Set UTF-8 charset header
header('Content-Type: text/html; charset=utf-8');

// Database connection
$sql_link = mysqli_connect(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD, DB_DATABASE)
    or die('Impossible de se connecter à la base de données!');
mysqli_query($sql_link, 'SET NAMES UTF8');

// -----------------------------------------------
// Load translation strings for the active language
require('../../text_content_' . LANGUAGE . '.php');

// Retrieve JSON data sent from the AJAX request
$jsonData = file_get_contents('php://input');

// Decode JSON data into a PHP associative array
$dataGraph = json_decode($jsonData, true);

// Extract values from the decoded array
$territoire_id = $dataGraph['territoireId'];
$cle_station   = $dataGraph['cle_station'];
$type_station  = $dataGraph['type_station'];
$id_typedata   = $dataGraph['id_typedata'];
$min_x         = $dataGraph['min_x'];
$max_x         = $dataGraph['max_x'];

$date        = DateTime::createFromFormat('d-m-Y', $min_x);
$year_min_x  = $date->format('Y');

$date        = DateTime::createFromFormat('d-m-Y', $max_x);
$year_max_x  = $date->format('Y');


$html_stats = "";

if ($year_min_x <= $year_max_x)
{
    $html_stats .= "

                <p class='info_stats'>
                    " . TEXT_STATS_DAILY_SUMMARY . "
                </p>

                <select id='yearSelect' style='float:left;width:65px;margin-left:10.5%;font-size:13px;' onchange='statsChronDays(this.value);'>";

                    for ($year = $year_max_x; $year >= $year_min_x; $year--)
                    {
                        $html_stats .= "<option value='$year'>$year</option>";
                    }

    $html_stats .= "

                </select>

                <div style='float:left;margin-left:10px;padding-top:0px;'>

                        <img src='" . DIR_WS_IMG_ICO . "arrow_previous.png' style='width:20px;cursor:pointer;'
                                title='" . TEXT_STATS_PREV_YEAR . "'
                                onclick='prevYear()'
                                onmouseover=\"this.src='" . DIR_WS_IMG_ICO . "arrow_previous_over.png';\" onmouseout=\"this.src='" . DIR_WS_IMG_ICO . "arrow_previous.png';\" >

                    <img src='" . DIR_WS_IMG_ICO . "arrow_next.png' style='width:20px;margin-left:15px;cursor:pointer;'
                            title='" . TEXT_STATS_NEXT_YEAR . "'
                            onclick='nextYear()'
                            onmouseover=\"this.src='" . DIR_WS_IMG_ICO . "arrow_next_over.png';\" onmouseout=\"this.src='" . DIR_WS_IMG_ICO . "arrow_next.png';\" >

                </div>

                <hr>

                <div id='contenu_stats_days' ></div>

                    ";
}

    $stat_graph       = true;
    $html_stats_graph = '';
    $js_graph         = '';


$responseData = array(
    'html_stats'       => $html_stats,
    'stat_graph'       => $stat_graph,
    'html_stats_graph' => $html_stats_graph,
    'js_graph'         => $js_graph
);

// Encode response as JSON
$jsonResponse = json_encode($responseData);

// Send response to the client
echo $jsonResponse;
?>
