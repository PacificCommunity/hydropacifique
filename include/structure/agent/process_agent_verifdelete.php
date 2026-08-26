<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
AJAX endpoint — build agent deletion confirmation HTML block
----------------------------------------
*/

require('../../config.php');
require('../../database_tables.php');
require('../../function/date.php');
require('../../function/database.php');
require('../../function/html_output.php');
require('../../function/general.php');

header('Content-Type: text/html; charset=utf-8');

// Load translation strings for the active language
require('../../text_content_' . LANGUAGE . '.php');

$sql_link = mysqli_connect(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD, DB_DATABASE)
    or die('Cannot connect to the database');
mysqli_query($sql_link, 'SET NAMES UTF8');

$dataInfo = json_decode(file_get_contents('php://input'), true);
$id_agent = $dataInfo['id_agent'];


// -----------------------------------------------
// Query agent name

$agent_query = tep_db_query($sql_link,
    "SELECT DISTINCT ag.id, ag.nom, ag.prenom FROM " . TABLE_AGENT . " ag WHERE id = " . $id_agent);
$agent_tab = tep_db_fetch_array($agent_query);

$nom    = $agent_tab['nom'];
$prenom = $agent_tab['prenom'];


// -----------------------------------------------
// Build confirmation HTML
$tab_html = "";

    $tab_html .= "<div style='float:left;width:100%;margin-top:25px;margin-left:10%;'>";
        $tab_html .= "<p style='width:100%;font-size:18px;'><span style='font-weight:bold;'>" . TEXT_AGENT_DEL_NOM . "</span>" . $nom . "</p>\n";
        $tab_html .= "<p style='width:100%;margin-top:15px;font-size:18px;'><span style='font-weight:bold;'>" . TEXT_AGENT_DEL_PRENOM . "</span>" . $prenom . "</p>\n";
    $tab_html .= "</div>";

    $tab_html .= "<div style='float:left;width:80%;margin-top:25px;margin-left:10%;'>";
        $tab_html .= "<div style='float:left;width:45%;'>";
            $tab_html .= "<input type='submit' class='button' id='del_agent' name='del_agent' value='" . TEXT_AGENT_BTN_DELETE . "' onClick='delAgent(" . $id_agent . ");'>";
        $tab_html .= "</div>";
        $tab_html .= "<div style='float:left;width:45%;'>";
            $tab_html .= "<input type='button' id='button_close' class='button_close' value='" . TEXT_AGENT_BTN_CANCEL . "' onClick=\"document.getElementById('box_del_agent').style.display='none'\">";
        $tab_html .= "</div>";
    $tab_html .= "<hr>";
    $tab_html .= "</div>";


echo json_encode(['tab_html' => $tab_html]);
?>
