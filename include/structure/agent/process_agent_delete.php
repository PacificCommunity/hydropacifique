<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
AJAX endpoint — delete an agent record
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

$dataInfo      = json_decode(file_get_contents('php://input'), true);
$id_agent      = $dataInfo['id_agent'];
$id_user_agent = $dataInfo['id_user_agent'];

$dateheure_action = date("Y-m-d H:i:s");
$type_action      = 1;
$msg_info         = '';
$del              = false;

$agent_query = tep_db_query($sql_link,
    "SELECT DISTINCT ag.id, ag.nom, ag.prenom FROM " . TABLE_AGENT . " ag WHERE id = " . $id_agent);
$agent_tab = tep_db_fetch_array($agent_query);

if (isset($agent_tab))
{
    $nom    = $agent_tab['nom'];
    $prenom = $agent_tab['prenom'];

    tep_db_query($sql_link, "DELETE FROM " . TABLE_AGENT . " WHERE id = " . $id_agent);

    $msg_info  = "<span style='font-size:16px;'>" . TEXT_AGENT_DEL_SUCCESS . "</span><br><br>";
    $msg_info .= TEXT_AGENT_LABEL . " : " . $nom . " " . $prenom;
    $del       = true;

    // Log the deletion action
    $info_action = post_secure($sql_link, TEXT_AGENT_DEL_ACTION_LOG . "<br>" . TEXT_AGENT_LABEL . " : " . $nom . " " . $prenom);
    tep_db_query($sql_link,
        "INSERT INTO " . TABLE_ACTIONS . " (id_user, type_action, info, dateheure)
         VALUES (" . $id_user_agent . ", '" . $type_action . "', '" . $info_action . "', '" . $dateheure_action . "')");
}
else
{
    $msg_info = "<span style='font-size:16px;'>" . TEXT_AGENT_DEL_ERROR . "</span>";
}

echo json_encode(['msg_info' => $msg_info, 'del' => $del]);
?>
