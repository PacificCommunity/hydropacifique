<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
AJAX endpoint — last 30 field reports for the homepage popup
Receives JSON: territoireId.
Returns JSON: { js_html } — an HTML table of the 30 most recent
field reports (RA) with validation status, type, station, and agents.
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
// Measurement types lookup

$eq_type_array = [];
$eq_type_query = tep_db_query($sql_link,
    "SELECT DISTINCT id_eq_type, nom_eq_type, unite_eq_type, valeur_data_type,
                     type_color_border, type_color_background, type_graph
     FROM " . TABLE_EQ_TYPE . "
     WHERE active_eq_type=1 ORDER BY order_eq_type ASC");
while ($r = tep_db_fetch_array($eq_type_query))
{
    $eq_type_array[$r['id_eq_type']] = [
        'id_eq_type'            => $r['id_eq_type'],
        'nom_eq_type'           => html_entity_decode($r['nom_eq_type'] ?? ''),
        'unite_eq_type'         => $r['unite_eq_type'],
        'valeur_data_type'      => $r['valeur_data_type'],
        'type_color_border'     => $r['type_color_border'],
        'type_color_background' => $r['type_color_background'],
        'type_graph'            => $r['type_graph'],
    ];
}


// -----------------------------------------------
// Last 30 field reports

$ra_array = [];
$RA_query = tep_db_query($sql_link,
    "SELECT DISTINCT ra.id_ra, s.id_station, s.code_station, s.nom_station, s.id_commune,
                     ra.date_heure_ra, ra.id_eq_type, ra.etat_ra, ra.agents_complement
     FROM " . TABLE_DATA_RA . " ra
     JOIN " . TABLE_STATION . " s ON ra.id_station = s.id_station
         AND s.id_territoire=$territoire_id
     ORDER BY ra.date_heure_ra DESC
     LIMIT 30");

while ($RA_tab = tep_db_fetch_array($RA_query))
{
    $id_ra = $RA_tab['id_ra'];

    $tab_date = explode(" ", $RA_tab['date_heure_ra']);
    $date_ra  = dateus_fr($tab_date[0]);
    $heure_ra = $tab_date[1];

    $ra_array[$id_ra] = [
        'etat_ra'       => $RA_tab['etat_ra'],
        'date_ra'       => $date_ra,
        'date_heure_ra' => $date_ra . ' ' . $heure_ra,
        'id_eq_type'    => $RA_tab['id_eq_type'],
        'id_station'    => $RA_tab['id_station'],
        'code_station'  => nettoyer_et_echapper($RA_tab['code_station']),
        'nom_station'   => nettoyer_et_echapper($RA_tab['nom_station']),
        'id_commune'    => $RA_tab['id_commune'],
        'list_agents'   => nettoyer_et_echapper($RA_tab['agents_complement']),
    ];
}


// -----------------------------------------------
// Build HTML table

$html = '';

if (!empty($ra_array))
{
    $html .= "<div class='table-container' style='height:60vh;'>"
           . "<table id='table_tri' cellspacing='0'>"
           . "<thead><tr class='header-row'>"
           . "<th style='text-align:center;width:25px;font-size:12px;'></th>"
           . "<th style='width:150px;padding-left:20px;font-size:12px;'>" . TEXT_IX_RA_COL_DATE    . "</th>"
           . "<th style='width:120px;font-size:12px;'>"                   . TEXT_IX_RA_COL_TYPE    . "</th>"
           . "<th style='width:300px;font-size:12px;'>"                   . TEXT_IX_RA_COL_STATION . "</th>"
           . "<th style='width:300px;font-size:12px;'>"                   . TEXT_IX_RA_COL_AGENTS  . "</th>"
           . "</tr></thead>";

    $row = 1;
    foreach ($ra_array as $key => $value)
    {
        $row++;
        $row_l = (fmod($row, 2) == 0)
            ? "class='row1' onmouseover=\"this.className='row1hover';\" onmouseout=\"this.className='row1';\" "
            : "class='row2' onmouseover=\"this.className='row2hover';\" onmouseout=\"this.className='row2';\" ";

        $color_type = "color:" . $eq_type_array[$value['id_eq_type']]['type_color_border'] . ";";
        $lien_ra    = "list_ra.php?st={$value['id_station']}&ra={$key}&td={$value['id_eq_type']}";

        $html .= "<tr " . $row_l . ">";

        // Status icon
        $html .= "<td style='text-align:center;cursor:pointer;'>";
        if ($value['etat_ra'] == 1)
        { $html .= "<img src='" . DIR_WS_IMG_ICO . "puce_verte.png' style='width:12px;' title='" . TEXT_IX_RA_STATUS_VALID   . "'>"; }
        else
        { $html .= "<img src='" . DIR_WS_IMG_ICO . "puce_rouge.png' style='width:12px;' title='" . TEXT_IX_RA_STATUS_PENDING . "'>"; }
        $html .= "</td>";

        // Date
        $html .= "<td style='padding-left:20px;cursor:pointer;'>"
               . "<a href='" . $lien_ra . "' target='_blank' style='font-size:11px;'>"
               . $value['date_ra'] . "</a></td>";

        // Measurement type
        $html .= "<td style='cursor:pointer;'>"
               . "<a href='" . $lien_ra . "' target='_blank' style='font-size:11px;'>"
               . "<span style='" . $color_type . "'>" . $eq_type_array[$value['id_eq_type']]['nom_eq_type'] . "</span>"
               . "</a></td>";

        // Station
        $html .= "<td style='cursor:pointer;' id='link_popup'"
               . " title='" . $value['code_station'] . " - " . $value['nom_station'] . "'>"
               . "<a href='" . $lien_ra . "' target='_blank' style='font-size:11px;'>"
               . affichelettres($value['nom_station'], 40)
               . "</a></td>";

        // Agents
        $html .= "<td style='cursor:pointer;' id='link_popup'"
               . " title='" . $value['list_agents'] . "'>"
               . "<a href='" . $lien_ra . "' target='_blank' style='font-size:11px;'>"
               . $value['list_agents']
               . "</a></td>";

        $html .= "</tr>";
    }

    $html .= "</table></div>";
}

echo json_encode(['js_html' => $html], JSON_UNESCAPED_UNICODE);
?>
