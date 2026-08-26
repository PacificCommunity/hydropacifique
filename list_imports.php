<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Recent data imports — lists all import records with filters for user
and recency (delay). Each row links to a detail file and to the
corresponding time-series chart.
----------------------------------------
*/

require('include/application_top.php');

$message_suprr = '';
$row           = 0;
$today         = new DateTime();

$select_user_encours    = 0;
$where_and_user         = '';

$select_station_encours = 0;
$where_and_station      = '';

// Default delay: 31 days on first load
$delai_encours = 31;
$having_delai  = " HAVING DATEDIFF(NOW(), dateheure) <= " . $delai_encours;


// -----------------------------------------------
// Form filter values

if (isset($_POST['select_user']) && $_POST['select_user'] != 0)
{
    $select_user_encours = $_POST['select_user'];
    $where_and_user      = " AND id_user=" . $select_user_encours;
}

if (isset($_POST['select_station']) && $_POST['select_station'] != 0)
{
    $select_station_encours = $_POST['select_station'];
    $where_and_station      = " AND id_station=" . $select_station_encours;
}

if (isset($_POST['select_delai']) && $_POST['select_delai'] != 0)
{
    $delai_encours = $_POST['select_delai'];
    $having_delai  = " HAVING DATEDIFF(NOW(), dateheure) <= " . $delai_encours;
}
if (isset($_POST['select_delai']) && $_POST['select_delai'] == 0)
{
    $delai_encours = 0;
    $having_delai  = '';
}


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

// All stations (for optional station filter — currently commented out in the form)
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

// Time-series types
$type_chron_array = [];
$type_chron_query = tep_db_query($sql_link,
    "SELECT DISTINCT id_data_type, init_type_data, nom_type_data, id_eq_type_data, unite
     FROM " . TABLE_TYPE_DATA . "
     ORDER BY init_type_data ASC");
while ($type_chron_tab = tep_db_fetch_array($type_chron_query))
{
    $type_chron_array[$type_chron_tab['id_data_type']] = [
        'init_type_data' => $type_chron_tab['init_type_data'],
        'nom_type_data'  => html_entity_decode($type_chron_tab['nom_type_data'] ?? ''),
        'unite'          => $type_chron_tab['unite'],
        'id_eq_type_data' => $type_chron_tab['id_eq_type_data'],
    ];
}


// -----------------------------------------------
// Import list query

$nb_imports   = 0;
$import_array = [];

$import_query = tep_db_query($sql_link,
    "SELECT DISTINCT id, id_import, file_import, dateheure, id_station, id_chron, id_user,
                     nb_data, datetime_first, datetime_end
     FROM " . TABLE_IMPORT_SUIVI . "
     WHERE import = 1"
    . $where_and_user . $where_and_station . $having_delai . "
     ORDER BY dateheure DESC");

while ($import_tab = tep_db_fetch_array($import_query))
{
    $id         = $import_tab['id'];
    $id_station = $import_tab['id_station'];

    if (!isset($station_all_array[$id_station])) { continue; }

    $dateheure    = new DateTime($import_tab['dateheure']);
    $delai_action = $today->diff($dateheure)->days;

    // Find the matching delay-period label
    $text_delai = '';
    foreach ($delai_array as $value)
    {
        if ($delai_action <= $value['nb_days'])
        {
            $text_delai = TEXT_LS_DELAY_LESS . $value['periode'];
            break;
        }
        $text_delai = TEXT_LS_DELAY_MORE . $value['periode'];
    }

    $code_station = $station_all_array[$id_station]['code_station'];
    $nom_station  = $station_all_array[$id_station]['nom_station'];
    $station_type = $station_all_array[$id_station]['station_type'];

    $id_chron   = $import_tab['id_chron'];
    $init_chron = isset($type_chron_array[$id_chron]) ? $type_chron_array[$id_chron]['init_type_data'] : '';
    $nom_chron  = isset($type_chron_array[$id_chron]) ? $type_chron_array[$id_chron]['nom_type_data']  : '';

    $id_user     = $import_tab['id_user'];
    $datetime_first = new DateTime($import_tab['datetime_first']);
    $datetime_end   = new DateTime($import_tab['datetime_end']);

    $file_info_link = DIR_WS_DATA_IMPORT
        . $import_tab['id_import'] . '_' . $id . '_' . $init_chron . '.txt';

    $import_array[$id] = [
        'id_import'              => $import_tab['id_import'],
        'file_import'            => $import_tab['file_import'],
        'dateheure_formatted'    => $dateheure->format('d-m-Y H:i:s'),
        'text_delai'             => $text_delai,
        'code_station'           => $code_station,
        'nom_station'            => $nom_station,
        'graph_chron_link'       => $id_station . '_' . $station_type . '_' . $id_chron,
        'init_chron'             => $init_chron,
        'nom_chron'              => $nom_chron,
        'login_user'             => $user_list_array[$id_user]['login']  ?? '',
        'nom_user'               => $user_list_array[$id_user]['nom']    ?? '',
        'prenom_user'            => $user_list_array[$id_user]['prenom'] ?? '',
        'nb_data'                => $import_tab['nb_data'],
        'date_first_formatted'   => $datetime_first->format('d-m-Y'),
        'datetime_first_formatted' => $datetime_first->format('d-m-Y H:i:s'),
        'date_end_formatted'     => $datetime_end->format('d-m-Y'),
        'datetime_end_formatted' => $datetime_end->format('d-m-Y H:i:s'),
        'file_exist_txt'         => file_exists($file_info_link),
        'file_info_link'         => $file_info_link,
    ];
}
if (!empty($import_array)) { $nb_imports = count($import_array); }


// -----------------------------------------------
// HTML output

require(DIR_WS_STRUCTURE . 'header_web.php');
echo "<body>";

require(DIR_WS_STRUCTURE . 'header.php');
include(DIR_WS_BOX       . 'nav_accueil.php');

echo "<div id='contour_general'>";
    echo "<div id='contenu_centre'>";
        echo "<div id='contenu_box2'>";

            echo "<h1><span>" . TEXT_LS_IMP_PAGE_TITLE . "</span></h1>";

            $lien_form = tep_href_link('list_imports.php');
            echo "<form name='form_imports' action='" . $lien_form . "' method='post' enctype='multipart/form-data'>";

                echo "<div id='cadre_graph' style='float:left;width:230px;margin-left:1%;height:70vh;overflow-y: auto;'>\n";
                    echo "<div id='boxpopup' class='select-top' style='width:92%;margin:0px;padding: 0 3%;'>\n";

                        // ---- User filter ----
                        echo "<p style='float:left;width:60px;margin-top:15px;padding-top:5px;'>"
                           . TEXT_LS_FILTER_USER . "</p>";
                        echo "<select name='select_user' id='select_user' onchange='form_imports.submit();'"
                           . " style='float:right;width:140px;margin-top:15px;'>";
                            echo "<option value='0'>-</option>";
                            if (!empty($user_list_array))
                            {
                                foreach ($user_list_array as $key => $value)
                                {
                                    $sel = ($key == $select_user_encours) ? 'selected' : '';
                                    echo "<option value='" . $key . "' " . $sel . ">"
                                       . $value['prenom'] . " " . $value['nom'] . "</option>";
                                }
                            }
                        echo "</select>";
                        echo "<hr>\n";

                        /*
                        // Station filter (disabled — kept for future use)
                        echo "<p style='float:left;width:60px;padding-top:5px;'>" . TEXT_LS_FILTER_STATION . "</p>";
                        echo "<select name='select_station' id='select_station' onchange='form_imports.submit();' style='float:right;width:140px;'>";
                            echo "<option value='0'>-</option>";
                            if (!empty($station_all_array))
                            {
                                foreach ($station_all_array as $key => $value)
                                {
                                    $sel = ($key == $select_station_encours) ? 'selected' : '';
                                    echo "<option value='" . $key . "' " . $sel . ">"
                                       . $value['code_station'] . " - " . $value['nom_station'] . "</option>";
                                }
                            }
                        echo "</select>";
                        */
                        echo "<hr>\n";

                        // ---- Delay filter ----
                        echo "<p style='float:left;width:60px;padding-top:5px;'>"
                           . TEXT_LS_FILTER_DELAY . "</p>";
                        echo "<select name='select_delai' id='select_delai' onchange='form_imports.submit();'"
                           . " style='float:right;width:140px;'>";
                            echo "<option value='0'>-</option>";
                            if (!empty($delai_array))
                            {
                                foreach ($delai_array as $value)
                                {
                                    $sel = ($value['nb_days'] == $delai_encours) ? 'selected' : '';
                                    echo "<option value='" . $value['nb_days'] . "' " . $sel . ">"
                                       . TEXT_LS_DELAY_LESS . $value['periode'] . "</option>";
                                }
                            }
                        echo "</select>";
                        echo "<hr>";

                    echo "</div>";
                echo "</div>";

            echo "</form>";


            // ---- Results table ----
            if (!empty($import_array) && $nb_imports > 0)
            {
                echo "<div class='table-container' style='float:none;width:auto;height:75vh;'>";
                    echo "<table id='table_tri' cellspacing='0'>";
                        echo "<thead>";
                            echo "<tr class='header-row'>";
                                echo "<th style='width:90px;font-size:12px;padding-left:5px;'>"   . TEXT_LS_COL_LOGIN      . "</th>";
                                echo "<th style='width:150px;font-size:12px;'>"                   . TEXT_LS_COL_NAME       . "</th>";
                                echo "<th style='width:170px;font-size:12px;'>"                   . TEXT_LS_COL_DATE       . "</th>";
                                echo "<th style='width:150px;font-size:12px;'>"                   . TEXT_LS_IMP_COL_FILE   . "</th>";
                                echo "<th style='width:250px;font-size:12px;'>"                   . TEXT_LS_COL_STATION    . "</th>";
                                echo "<th style='width:240px;font-size:12px;'>"                   . TEXT_LS_IMP_COL_CHRON  . "</th>";
                                echo "<th style='width:90px;font-size:12px;'>"                    . TEXT_LS_IMP_COL_NBDATA . "</th>";
                                echo "<th style='width:170px;font-size:12px;'>"                   . TEXT_LS_IMP_COL_DATE_S . "</th>";
                                echo "<th style='width:170px;font-size:12px;'>"                   . TEXT_LS_IMP_COL_DATE_E . "</th>";
                                echo "<th style='width:80px;font-size:12px;text-align: center;'>" . TEXT_LS_COL_DETAILS    . "</th>";
                                echo "<th style='width:80px;font-size:12px;text-align: center;'>" . TEXT_LS_COL_CONSULT    . "</th>";
                            echo "</tr>";
                        echo "</thead>";

                        echo "<tr><td colspan='11' style='height:15px;'>&nbsp;</td></tr>";

                        foreach ($import_array as $key => $value)
                        {
                            $tabForm = [
                                ['name' => 'graph_chron',    'value' => $value['graph_chron_link']],
                                ['name' => 'date1_encours',  'value' => $value['date_first_formatted']],
                                ['name' => 'date2_encours',  'value' => $value['date_end_formatted']],
                            ];
                            $tabFormJson = htmlspecialchars(json_encode($tabForm), ENT_QUOTES, 'UTF-8');

                            $consult_import = "<img src='" . DIR_WS_IMG_ICO . "graph.png'"
                                . " style='width:15px;cursor:pointer;'"
                                . " onclick=\"event.preventDefault();linkSubmitForm('data_chron.php'," . $tabFormJson . ");\">";

                            if (fmod($row, 2) == 0)
                            { $row_l = "class='row1' onmouseover=\"this.className='row1hover';\" onmouseout=\"this.className='row1';\" "; }
                            else
                            { $row_l = "class='row2' onmouseover=\"this.className='row2hover';\" onmouseout=\"this.className='row2';\" "; }

                            echo "<tr " . $row_l . " style='height:20px;'>";
                                echo "<td style='padding-left:5px;'>"  . $value['login_user']  . "</td>\n";
                                echo "<td>" . $value['prenom_user'] . " " . $value['nom_user'] . "</td>\n";
                                echo "<td>" . $value['dateheure_formatted']  . "</td>\n";
                                echo "<td>" . $value['file_import']          . "</td>\n";
                                echo "<td title='" . $value['nom_station'] . "'>"
                                   . $value['code_station'] . ' - ' . affichemots($value['nom_station'], 2) . "</td>\n";
                                echo "<td>" . $value['init_chron'] . ' - ' . $value['nom_chron'] . "</td>\n";
                                echo "<td>" . $value['nb_data']              . "</td>\n";
                                echo "<td>" . $value['datetime_first_formatted'] . "</td>\n";
                                echo "<td>" . $value['datetime_end_formatted']   . "</td>\n";

                                // Detail (.txt) link
                                echo "<td style='text-align: center;'>";
                                if ($value['file_exist_txt'])
                                {
                                    echo "<a href='" . $value['file_info_link'] . "' target='blank_'>";
                                        echo "<img src='" . DIR_WS_IMG_ICO . "detail.png' style='width:20px;cursor:pointer;'>";
                                    echo "</a>";
                                }
                                else { echo '-'; }
                                echo "</td>\n";

                                // Graph link
                                echo "<td style='text-align: center;'>" . $consult_import . "</td>\n";

                            echo "</tr>\n";
                            $row++;
                        }
                    echo "</table>";
                echo "</div>";
            }
            else
            {
                echo "<div id='boxpopup' style='margin-left: 1%;'>\n";
                    echo "<p class='alert'>" . TEXT_LS_IMP_NO_RESULT . "</p>";
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
