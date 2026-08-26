<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
AJAX endpoint — last 40 imports for the homepage popup
Receives JSON: territoireId.
Returns JSON: { js_html } — an HTML table of the 40 most recent
successful imports, with links to the corresponding time-series chart.
----------------------------------------
*/

require('../../config.php');
require('../../database_tables.php');

require('../../function/date.php');
require('../../function/database.php');
require('../../function/html_output.php');
require('../../function/general.php');

require('../../text_content_' . LANGUAGE . '.php');

header('Content-Type: text/html; charset=utf-8');

$sql_link = mysqli_connect(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD, DB_DATABASE)
    or die('Cannot connect to the database');
mysqli_query($sql_link, 'SET NAMES UTF8');

$data          = json_decode(file_get_contents('php://input'), true);
$territoire_id = $data['territoireId'];


// -----------------------------------------------
// Lookup data

// Measurement types
$eq_type_array = [];
$eq_type_query = tep_db_query($sql_link,
    "SELECT DISTINCT id_eq_type, nom_eq_type, unite_eq_type, valeur_data_type,
                     type_color_border, type_color_background, type_graph
     FROM " . TABLE_EQ_TYPE . "
     WHERE active_eq_type=1 ORDER BY order_eq_type ASC");
while ($r = tep_db_fetch_array($eq_type_query))
{
    $eq_type_array[$r['id_eq_type']] = [
        'id_eq_type'             => $r['id_eq_type'],
        'nom_eq_type'            => html_entity_decode($r['nom_eq_type'] ?? ''),
        'unite_eq_type'          => $r['unite_eq_type'],
        'valeur_data_type'       => $r['valeur_data_type'],
        'type_color_border'      => $r['type_color_border'],
        'type_color_background'  => $r['type_color_background'],
        'type_graph'             => $r['type_graph'],
    ];
}

// Time-series types
$type_chron_array = [];
$type_chron_query = tep_db_query($sql_link,
    "SELECT DISTINCT id_data_type, init_type_data, nom_type_data, id_eq_type_data,
                     axe_data, unite, to_periode, id_chon_periode
     FROM " . TABLE_TYPE_DATA . " ORDER BY init_type_data ASC");
while ($tc = tep_db_fetch_array($type_chron_query))
{
    $type_chron_array[$tc['id_data_type']] = [
        'init_type_data'  => html_entity_decode($tc['init_type_data']  ?? ''),
        'nom_type_data'   => html_entity_decode($tc['nom_type_data']   ?? ''),
        'id_eq_type_data' => html_entity_decode($tc['id_eq_type_data'] ?? ''),
        'axe_nom'         => html_entity_decode($data_type_axe_array[$tc['axe_data']]['axe'] ?? ''),
        'unite'           => html_entity_decode($tc['unite']           ?? ''),
        'to_periode'      => html_entity_decode($tc['to_periode']      ?? ''),
        'id_chon_periode' => html_entity_decode($tc['id_chon_periode'] ?? ''),
    ];
}

// Stations
$station_array = [];
$station_query = tep_db_query($sql_link,
    "SELECT DISTINCT s.id_station, s.nom_station, s.code_station, s.station_type
     FROM " . TABLE_STATION . " s
     WHERE s.id_territoire=$territoire_id ORDER BY code_station DESC");
while ($station = tep_db_fetch_array($station_query))
{
    $station_array[$station['id_station']] = [
        'nom_station'  => html_entity_decode($station['nom_station']  ?? ''),
        'code_station' => html_entity_decode($station['code_station'] ?? ''),
        'station_type' => $station['station_type'],
    ];
}

// Users
$user_list_array = [];
$user_list_query = tep_db_query($sql_link,
    "SELECT DISTINCT id, id_statut, login, nom, prenom FROM " . TABLE_USER);
while ($u = tep_db_fetch_array($user_list_query))
{
    $user_list_array[$u['id']] = [
        'id_statut' => $u['id_statut'],
        'login'     => html_entity_decode($u['login']  ?? ''),
        'nom'       => ucfirst(strtolower(html_entity_decode($u['nom']    ?? ''))),
        'prenom'    => ucfirst(strtolower(html_entity_decode($u['prenom'] ?? ''))),
    ];
}


// -----------------------------------------------
// Import list — last 40 successful imports

$import_array = [];
$import_query = tep_db_query($sql_link,
    "SELECT DISTINCT id, id_import, file_import, dateheure, id_station, id_chron,
                     id_user, nb_data, datetime_first, datetime_end
     FROM " . TABLE_IMPORT_SUIVI . "
     WHERE import=1 ORDER BY dateheure DESC LIMIT 40");

while ($import_tab = tep_db_fetch_array($import_query))
{
    $id         = $import_tab['id'];
    $id_station = $import_tab['id_station'];
    $id_chron   = $import_tab['id_chron'];
    $id_user_hp = $import_tab['id_user'];

    $dateheure_formatted  = (new DateTime($import_tab['dateheure']))->format('d-m-Y');
    $date_first_formatted = (new DateTime($import_tab['datetime_first']))->format('d-m-Y');
    $date_end_formatted   = (new DateTime($import_tab['datetime_end']))->format('d-m-Y');

    $code_station = $station_array[$id_station]['code_station']    ?? '';
    $nom_station  = $station_array[$id_station]['nom_station']     ?? '';
    $station_type = $station_array[$id_station]['station_type']    ?? 0;

    $init_chron = $type_chron_array[$id_chron]['init_type_data'] ?? '';
    $nom_chron  = $type_chron_array[$id_chron]['nom_type_data']  ?? '';

    $file_info_link = DIR_WS_DATA_IMPORT . $import_tab['id_import'] . '_' . $init_chron . '.txt';

    $import_array[$id] = [
        'id_import'            => $import_tab['id_import'],
        'file_import'          => $import_tab['file_import'],
        'dateheure_formatted'  => $dateheure_formatted,
        'date_first_formatted' => $date_first_formatted,
        'date_end_formatted'   => $date_end_formatted,
        'code_station'         => $code_station,
        'nom_station'          => $nom_station,
        'graph_chron_link'     => $id_station . '_' . $station_type . '_' . $id_chron,
        'init_chron'           => $init_chron,
        'nom_chron'            => $nom_chron,
        'login_user_hp'        => $user_list_array[$id_user_hp]['login']  ?? '',
        'nom_user_hp'          => $user_list_array[$id_user_hp]['nom']    ?? '',
        'prenom_user_hp'       => $user_list_array[$id_user_hp]['prenom'] ?? '',
        'nb_data'              => $import_tab['nb_data'],
        'file_exist_txt'       => file_exists($file_info_link),
        'file_info_link'       => $file_info_link,
    ];
}


// -----------------------------------------------
// Build HTML table

$html = '';

if (!empty($import_array))
{
    $html .= "<div class='table-container' style='height:60vh;'>"
           . "<table id='table_tri' cellspacing='0'>"
           . "<thead><tr class='header-row'>"
           . "<th style='width:150px;padding-left:20px;font-size:12px;'>" . TEXT_IX_IMP_COL_DATE    . "</th>"
           . "<th style='width:200px;font-size:12px;'>"                   . TEXT_IX_IMP_COL_USER    . "</th>"
           . "<th style='width:250px;font-size:12px;'>"                   . TEXT_IX_IMP_COL_STATION . "</th>"
           . "<th style='width:200px;font-size:12px;'>"                   . TEXT_IX_IMP_COL_CHRON   . "</th>"
           . "<th style='width:80px;font-size:12px;'>"                    . TEXT_IX_IMP_COL_CONSULT . "</th>"
           . "</tr></thead>";

    $row = 1;
    foreach ($import_array as $key => $value)
    {
        $row++;
        $row_l = (fmod($row, 2) == 0)
            ? "class='row1' onmouseover=\"this.className='row1hover';\" onmouseout=\"this.className='row1';\" "
            : "class='row2' onmouseover=\"this.className='row2hover';\" onmouseout=\"this.className='row2';\" ";

        $html .= "<tr " . $row_l . ">";
        $html .= "<td style='center'>"                          . $value['dateheure_formatted'] . "</td>";
        $html .= "<td>"                                         . $value['prenom_user_hp'] . " " . $value['nom_user_hp'] . "</td>";
        $html .= "<td style='cursor:pointer;' id='link_popup'>" . $value['code_station'] . " - " . $value['nom_station'] . "</td>";
        $html .= "<td>"                                         . $value['init_chron'] . " " . $value['nom_chron'] . "</td>";

        $html .= "<td style='text-align:center;'>";
        if (tep_not_null($value['init_chron']))
        {
            $tabForm = [
                ['name' => 'graph_chron',    'value' => $value['graph_chron_link']],
                ['name' => 'date1_encours',  'value' => $value['date_first_formatted']],
                ['name' => 'date2_encours',  'value' => $value['date_end_formatted']],
            ];
            $tabFormJson = htmlspecialchars(json_encode($tabForm), ENT_QUOTES, 'UTF-8');
            $html .= "<img src='" . DIR_WS_IMG_ICO . "graph.png' style='width:15px;cursor:pointer;'"
                   . " title='" . TEXT_IX_IMP_LINK_TITLE . "'"
                   . " onclick=\"event.preventDefault();linkSubmitForm('data_chron.php', " . $tabFormJson . ");\">";
        }
        else { $html .= "-"; }
        $html .= "</td>";

        $html .= "</tr>";
    }

    $html .= "</table></div>";
}

echo json_encode(['js_html' => $html], JSON_UNESCAPED_UNICODE);
?>
