<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Data series correction log — lists all recorded corrections with
station/type filters and sortable table. Each row links to a detail
file and to the corresponding time-series chart.
----------------------------------------
*/

require('include/application_top.php');

$row   = 0;
$today = new DateTime();

$tournee_delai_encours = 0;
$having_delai          = '';


// -----------------------------------------------
// Sort field and order

$tri_encours = 1;
$tri         = '';
if (isset($_POST['select_tri']))
{
    $tri_encours = $_POST['select_tri'];
    if ($_POST['select_tri'] == 2) { $tri = "s.nom_station"; }
    if ($_POST['select_tri'] == 3) { $tri = "s.code_station"; }
    if ($_POST['select_tri'] == 4) { $tri = "s.station_type"; }
}

$tri_order_encours = 2;
$tri_order         = " DESC,";
if (isset($_POST['order_tri']))
{
    $tri_order_encours = $_POST['order_tri'];
    if ($_POST['order_tri'] == 1) { $tri_order = " ASC,"; }
    if ($_POST['order_tri'] == 2) { $tri_order = " DESC,"; }
}


// -----------------------------------------------
// Station filter configuration (injected by filtre_stations_var.php)

$affiche_select_from          = true;
$affiche_select_type          = true;
$affiche_select_tournee       = false;
$affiche_search               = true;
$affiche_select_riviere       = false;
$affiche_select_station       = true;
$affiche_select_statut_station = true;
require(DIR_WS_FILTRE . 'filtre_stations_var.php');


// -----------------------------------------------
// Lookup data

// Users
$user_list_array = [];
$user_list_query = tep_db_query($sql_link,
    "SELECT DISTINCT id, id_statut, login, nom, prenom FROM " . TABLE_USER);
while ($user_list = tep_db_fetch_array($user_list_query))
{
    $id = $user_list['id'];
    $user_list_array[$id] = [
        'id_statut' => $user_list['id_statut'],
        'login'     => html_entity_decode($user_list['login']  ?? ''),
        'nom'       => ucfirst(strtolower(html_entity_decode($user_list['nom']    ?? ''))),
        'prenom'    => ucfirst(strtolower(html_entity_decode($user_list['prenom'] ?? ''))),
    ];
}

// Delay periods
$delai_array = [];
$delai_query = tep_db_query($sql_link,
    "SELECT DISTINCT id, periode, nb_days FROM " . TABLE_TOURNEE_PERIODE . " ORDER BY nb_days ASC");
while ($delai_tab = tep_db_fetch_array($delai_query))
{
    $delai_array[$delai_tab['id']] = [
        'periode' => html_entity_decode($delai_tab['periode'] ?? ''),
        'nb_days' => $delai_tab['nb_days'],
    ];
}

// All stations
$station_all_array = [];
$station_all_query = tep_db_query($sql_link,
    "SELECT DISTINCT id_station, nom_station, code_station, station_type, active_station
     FROM " . TABLE_STATION . "
     ORDER BY nom_station ASC");
while ($station_all = tep_db_fetch_array($station_all_query))
{
    $station_all_array[$station_all['id_station']] = [
        'code_station' => $station_all['code_station'],
        'nom_station'  => html_entity_decode($station_all['nom_station'] ?? ''),
        'station_type' => $station_all['station_type'],
    ];
}

// Measurement types (eq_type)
$eq_type_array  = [];
$eq_type_query  = tep_db_query($sql_link,
    "SELECT DISTINCT id_eq_type, nom_eq_type, unite_eq_type, valeur_data_type, type_color_border, type_graph
     FROM " . TABLE_EQ_TYPE . "
     WHERE active_eq_type = 1
     ORDER BY order_eq_type ASC");
while ($eq_type_tab = tep_db_fetch_array($eq_type_query))
{
    $eq_type_array[$eq_type_tab['id_eq_type']] = [
        'nom_eq_type'       => $eq_type_tab['nom_eq_type'],
        'unite_eq_type'     => $eq_type_tab['unite_eq_type'],
        'valeur_data_type'  => $eq_type_tab['valeur_data_type'],
        'type_graph'        => $eq_type_tab['type_graph'],
        'type_color_border' => $eq_type_tab['type_color_border'],
    ];
}

// Time-series types
$type_chron_array = [];
$type_chron_query = tep_db_query($sql_link,
    "SELECT DISTINCT id_data_type, init_type_data, nom_type_data, id_eq_type_data,
                     axe_data, unite, to_periode, id_chon_periode
     FROM " . TABLE_TYPE_DATA . "
     ORDER BY init_type_data ASC");
while ($type_chron_tab = tep_db_fetch_array($type_chron_query))
{
    $axe_nom = '';
    if (isset($data_type_axe_array[$type_chron_tab['axe_data']]['axe']))
    {
        $axe_nom = $data_type_axe_array[$type_chron_tab['axe_data']]['axe'];
    }
    $type_chron_array[$type_chron_tab['id_data_type']] = [
        'init_type_data'  => $type_chron_tab['init_type_data'],
        'nom_type_data'   => $type_chron_tab['nom_type_data'],
        'id_eq_type_data' => $type_chron_tab['id_eq_type_data'],
        'axe_nom'         => $axe_nom,
        'unite'           => $type_chron_tab['unite'],
        'to_periode'      => $type_chron_tab['to_periode'],
        'id_chon_periode' => $type_chron_tab['id_chon_periode'],
    ];
}


// -----------------------------------------------
// Correction list query

$correction_array = [];
$nb_correction    = 0;

$correction_query = tep_db_query($sql_link,
    "SELECT c.id, c.id_user, c.datetime_correction, c.id_station, c.id_chron_init,
            s.id_region, s.id_commune, s.nom_station, s.code_station, s.vallee_station,
            s.active_station, s.suivi, s.armee, s.station_type,
            s.id_regionhydro, s.id_riviere
     FROM " . TABLE_DATA_CORRECTION . " c
     LEFT JOIN " . TABLE_STATION . " s ON s.id_station = c.id_station
     WHERE 1=1 "
    . $where_and_from . $where_search . $where_and_regionhydro . $where_and_region . $where_and_commune
    . $where_and_riviere . $where_and_type . $where_and_active . $where_and_suivi . $where_and_armee
    . " ORDER BY c.datetime_correction DESC");

while ($correction_tab = tep_db_fetch_array($correction_query))
{
    $nb_correction++;

    $id_correction = $correction_tab['id'];
    $id_station    = $correction_tab['id_station'];
    $id_chron_init = $correction_tab['id_chron_init'];
    $station_type  = $correction_tab['station_type'];
    $code_station  = $correction_tab['code_station'];
    $nom_station   = $correction_tab['nom_station'];

    // Format correction date
    $datetime_correction_tab      = explode(' ', $correction_tab['datetime_correction']);
    $datetime_correction_formated = dateus_fr($datetime_correction_tab[0]) . ' ' . $datetime_correction_tab[1];

    $init_chron = isset($type_chron_array[$id_chron_init])
        ? $type_chron_array[$id_chron_init]['init_type_data'] : '';

    $type_mesure       = isset($eq_type_array[$station_type]['nom_eq_type'])
        ? $eq_type_array[$station_type]['nom_eq_type'] : '';
    $type_color_border = isset($eq_type_array[$station_type]['type_color_border'])
        ? $eq_type_array[$station_type]['type_color_border'] : '';

    $correction_array[$id_correction] = [
        'login_user'                  => $user_list_array[$correction_tab['id_user']]['login']  ?? '',
        'prenom_user'                 => $user_list_array[$correction_tab['id_user']]['prenom'] ?? '',
        'nom_user'                    => $user_list_array[$correction_tab['id_user']]['nom']    ?? '',
        'datetime_correction_formated' => $datetime_correction_formated,
        'id_station'                  => $id_station,
        'code_station'                => $code_station,
        'nom_station'                 => $nom_station,
        'graph_chron_link'            => $id_station . '_' . $station_type . '_' . $id_chron_init,
        'text_chron_link'             => DIR_WS_DATA_CORRECTIONS
                                          . $code_station . '_' . $init_chron . '_' . $id_correction . '.txt',
        'type_mesure'                 => $type_mesure,
        'type_color_border'           => $type_color_border,
    ];
}


// -----------------------------------------------
// HTML output

require(DIR_WS_STRUCTURE . 'header_web.php');
echo "<body>";

require(DIR_WS_STRUCTURE . 'header.php');
include(DIR_WS_BOX       . 'nav_accueil.php');

echo "<div id='contour_general'>";
    echo "<div id='contenu_centre'>";
        echo "<div id='contenu_box2'>";

            echo "<h1><span>" . TEXT_LS_COR_PAGE_TITLE . "</span></h1>";

            $lien_form = tep_href_link('corrections.php');
            $name_form = 'form_correction';
            echo "<form name='" . $name_form . "' action='" . $lien_form . "' method='post' enctype='multipart/form-data'>";

                echo "<div id='cadre_graph' style='float:left;width:272px;max-height:80vh;overflow-y: auto;'>\n";
                    echo "<div id='boxpopup' class='select-top' style='width:235px;margin:0px;padding: 0 3%;padding-top:10px;'>\n";

                        require(DIR_WS_FILTRE . 'filtre_stations_html.php');
                        echo "<hr>";

                        // ---- Sort field ----
                        echo "<div style='width:100%;border-bottom:2px solid #176B87;margin-top:5px;'></div>";
                        echo "<p style='float:left;width:auto;padding-top:5px;color:#186F65;margin-top:15px;'>"
                           . TEXT_LS_COR_SORT_LABEL . "</p>";

                        echo "<select name='select_tri' id='select_tri' onchange='" . $name_form . ".submit();'"
                           . " style='float:right;width:130px;margin-top:15px;'>";

                            $sel = ($tri_encours == 1) ? 'selected' : '';
                            echo "<option value='1' " . $sel . ">" . TEXT_LS_COR_SORT_DATE . "</option>";
                            $sel = ($tri_encours == 2) ? 'selected' : '';
                            echo "<option value='2' " . $sel . ">" . TEXT_LS_COR_SORT_NAME . "</option>";
                            $sel = ($tri_encours == 3) ? 'selected' : '';
                            echo "<option value='3' " . $sel . ">" . TEXT_LS_COR_SORT_CODE . "</option>";
                            $sel = ($tri_encours == 4) ? 'selected' : '';
                            echo "<option value='4' " . $sel . ">" . TEXT_LS_COR_SORT_TYPE . "</option>";

                        echo "</select>";
                        echo "<hr>";

                        // ---- Sort order (ASC / DESC) ----
                        echo "<div style='float:right;'>";
                            $asc_checked  = ($tri_order_encours == 1) ? 'checked' : '';
                            $desc_checked = ($tri_order_encours == 2) ? 'checked' : '';

                            echo "<p style='float:left;width:55px;padding-top:3px;'>"
                               . TEXT_LS_COR_ORDER_ASC . "</p>";
                            echo "<input type='radio' id='asc' name='order_tri' value='1' style='float:left;'"
                               . " " . $asc_checked . " onchange='" . $name_form . ".submit();'>";

                            echo "<p style='float:left;width:65px;margin-left:10px;padding-top:3px;'>"
                               . TEXT_LS_COR_ORDER_DESC . "</p>";
                            echo "<input type='radio' id='desc' name='order_tri' value='2' style='float:left;'"
                               . " " . $desc_checked . " onchange='" . $name_form . ".submit();'>";
                        echo "</div>";

                        // ---- Correction count ----
                        echo "<div id='contenu_infos' style='width:97%;margin-top:10px;'>";
                            echo "<p><span style='margin:0px;'>"
                               . TEXT_LS_COR_NB_CORR
                               . number_format($nb_correction, 0, '.', ' ')
                               . "</span></p>";
                        echo "</div>";
                        echo "<hr>";

                    echo "</div>";
                echo "</div>";

            echo "</form>";


            // ---- Results table ----
            if (!empty($correction_array))
            {
                echo "<div class='table-container' style='float:none;width:auto;height:80vh;'>";
                    echo "<table id='table_tri' cellspacing='0'>";
                        echo "<thead>";
                            echo "<tr class='header-row'>";
                                echo "<th style='width:90px;font-size:12px;padding-left:5px;'>"  . TEXT_LS_COL_LOGIN    . "</th>";
                                echo "<th style='width:150px;font-size:12px;'>"                  . TEXT_LS_COL_NAME     . "</th>";
                                echo "<th style='width:150px;' title='" . TEXT_LS_COL_DATE . "'>" . TEXT_LS_COL_DATE    . "</th>";
                                echo "<th style='width:100px;'>"                                 . TEXT_LS_COR_COL_CODE . "</th>";
                                echo "<th style='width:350px;'>"                                 . TEXT_LS_COR_COL_NAME . "</th>";
                                echo "<th style='width:100px;'>"                                 . TEXT_LS_COR_COL_TYPE . "</th>";
                                echo "<th style='width:80px;text-align: center;'>"               . TEXT_LS_COL_DETAILS  . "</th>";
                                echo "<th style='width:80px;text-align: center;'>"               . TEXT_LS_COL_CONSULT  . "</th>";
                            echo "</tr>";
                        echo "</thead>";

                        echo "<tr><td colspan='9' style='height:15px;'>&nbsp;</td></tr>";

                        foreach ($correction_array as $key => $value)
                        {
                            if (fmod($row, 2) == 0)
                            { $row_l = "class='row1' onmouseover=\"this.className='row1hover';\" onmouseout=\"this.className='row1';\" "; }
                            else
                            { $row_l = "class='row2' onmouseover=\"this.className='row2hover';\" onmouseout=\"this.className='row2';\" "; }

                            $color_type  = tep_not_null($value['type_color_border'])
                                ? 'color:' . $value['type_color_border'] . ';' : '';

                            $detail_text = '-';
                            if (file_exists($value['text_chron_link']))
                            {
                                $detail_text = "<a href='" . $value['text_chron_link'] . "' target='blank_'>"
                                    . "<img src='" . DIR_WS_IMG_ICO . "detail.png' style='width:20px;cursor:pointer;'>"
                                    . "</a>";
                            }

                            $tabForm = [
                                ['name' => 'graph_chron',   'value' => $value['graph_chron_link']],
                                ['name' => 'button_calcul', 'value' => true],
                                ['name' => 'id_correction', 'value' => $key],
                            ];
                            $tabFormJson = htmlspecialchars(json_encode($tabForm), ENT_QUOTES, 'UTF-8');

                            $consult_text = "<img src='" . DIR_WS_IMG_ICO . "graph.png'"
                                . " style='width:15px;cursor:pointer;'"
                                . " onclick=\"event.preventDefault();linkSubmitForm('data_chron.php'," . $tabFormJson . ");\">";

                            echo "<tr " . $row_l . " style='height:20px;'>";
                                echo "<td style='padding-left:5px;'>" . $value['login_user']  . "</td>\n";
                                echo "<td>" . $value['prenom_user'] . " " . $value['nom_user'] . "</td>\n";
                                echo "<td>" . $value['datetime_correction_formated'] . "</td>\n";
                                echo "<td>" . $value['code_station'] . "</td>\n";
                                echo "<td title='" . $value['nom_station'] . "'>"
                                   . affichelettres($value['nom_station'], 40) . "</td>\n";
                                echo "<td style='" . $color_type . "'>" . $value['type_mesure'] . "</td>\n";
                                echo "<td style='text-align: center;'>" . $detail_text   . "</td>";
                                echo "<td style='text-align: center;'>" . $consult_text  . "</td>";
                            echo "</tr>\n";
                            $row++;
                        }
                    echo "</table>";
                echo "</div>";
            }
            else
            {
                echo "<div id='boxpopup' style='margin-left: 1%;'>\n";
                    echo "<p class='alert'>" . TEXT_LS_COR_NO_RESULT . "</p>";
                echo "</div>";
            }

        echo "<hr>";
        echo "</div>";
    echo "<hr>";
    echo "</div>";
echo "<hr>";
echo "</div>";

require('include/application_bottom.php');
echo "</body>";
echo "</html>";
?>
