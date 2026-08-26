<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Recent data exports — lists all export actions recorded in the last
24 months, with links to download the generated files.
----------------------------------------
*/

require('include/application_top.php');

$message_suprr = '';
$row           = 0;
$today         = new DateTime();

$select_user_encours   = 0;
$where_and_user        = '';

$select_action_encours = 0;
$where_and_action      = '';

$delai_encours = 0;
$having_delai  = '';


// -----------------------------------------------
// Form filter values

if (isset($_POST['select_user']) && $_POST['select_user'] != 0)
{
    $select_user_encours = $_POST['select_user'];
    $where_and_user      = " AND id_user=" . $select_user_encours;
}

if (isset($_POST['select_action']) && $_POST['select_action'] != 0)
{
    $select_action_encours = $_POST['select_action'];
    $where_and_action      = " AND type=" . $select_action_encours;
}

if (isset($_POST['select_delai']) && $_POST['select_delai'] != 0)
{
    $delai_encours = $_POST['select_delai'];
    $having_delai  = " HAVING DATEDIFF(NOW(), dateheure) <= " . $delai_encours;
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

// Action types
$action_type_array = [];
$action_type_query = tep_db_query($sql_link,
    "SELECT DISTINCT id, type FROM " . TABLE_ACTIONS_TYPE . " ORDER BY type ASC");
while ($action_type = tep_db_fetch_array($action_type_query))
{
    $action_type_array[$action_type['id']] = html_entity_decode($action_type['type'] ?? '');
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


// -----------------------------------------------
// Export action list query (type_action = 36, last 24 months)

$nb_actions   = 0;
$action_array = [];

$action_query = tep_db_query($sql_link,
    "SELECT DISTINCT id, id_user, type_action, info, dateheure, file_export
     FROM " . TABLE_ACTIONS . "
     WHERE type_action = 36
     AND dateheure >= DATE_SUB(NOW(), INTERVAL 24 MONTH)
     ORDER BY dateheure DESC");

while ($action_tab = tep_db_fetch_array($action_query))
{
    $id          = $action_tab['id'];
    $file_export = $action_tab['file_export'];
    // Companion .txt info file shares the basename with a .txt extension
    $file_info   = strstr($file_export, '.', true) . '.txt';

    $dateheure    = new DateTime($action_tab['dateheure']);
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

    $action_array[$id] = [
        'id_user'       => $action_tab['id_user'],
        'info'          => html_entity_decode($action_tab['info'] ?? ''),
        'dateheure'     => $dateheure->format('d-m-Y H:i:s'),
        'file_export'   => $file_export,
        'file_exist'    => file_exists(DIR_WS_DATA_EXPORT . $file_export),
        'file_info'     => $file_info,
        'file_exist_txt' => file_exists(DIR_WS_DATA_EXPORT . $file_info),
    ];
}
if (!empty($action_array)) { $nb_actions = count($action_array); }


// -----------------------------------------------
// HTML output

require(DIR_WS_STRUCTURE . 'header_web.php');
echo "<body>";

require(DIR_WS_STRUCTURE . 'header.php');
include(DIR_WS_BOX       . 'nav_accueil.php');

echo "<div id='contour_general'>";
    echo "<div id='contenu_centre'>";
        echo "<div id='contenu_box2'>";

            echo "<h1><span>" . TEXT_LS_EXP_PAGE_TITLE . "</span></h1>";

            // ---- Info banner ----
            echo "<div id='cadre_graph' style='float:left;width:18%;height:70vh;overflow-y: auto;padding: 2px;'>\n";
                echo "<div style='float:left;background-color:#fff;margin:1%;padding: 3% 5%;box-shadow: 1px 1px 6px #555;'>";
                    echo "<img src='" . DIR_WS_IMG_ICO . "time.png' style='float:left;width:50px;margin-top:0px;'>";
                    echo "<p style='float:left;width:75%;margin-left:5%;text-align:left;font-size:14px;font-weight:bold;'>"
                       . TEXT_LS_EXP_AVAIL_INFO . "</p>";
                echo "</div>";
            echo "</div>";


            // ---- Results table ----
            if (!empty($action_array) && $nb_actions > 0)
            {
                echo "<div class='table-container' style='float:left;width:60%;height:75vh;margin-left:2%;'>";
                    echo "<table id='table_tri' cellspacing='0'>";
                        echo "<thead>";
                            echo "<tr class='header-row'>";
                                echo "<th style='width:90px;font-size:12px;padding-left:20px;'>"  . TEXT_LS_COL_LOGIN   . "</th>";
                                echo "<th style='width:150px;font-size:12px;'>"                   . TEXT_LS_COL_NAME    . "</th>";
                                echo "<th style='width:150px;font-size:12px;'>"                   . TEXT_LS_COL_DATE    . "</th>";
                                echo "<th style='width:100px;font-size:12px;text-align: center;'>" . TEXT_LS_COL_DETAILS . "</th>";
                                echo "<th style='width:150px;font-size:12px;'>"                   . TEXT_LS_EXP_COL_FILE . "</th>";
                            echo "</tr>";
                        echo "</thead>";

                        echo "<tr><td colspan='6' style='height:15px;'>&nbsp;</td></tr>";

                        foreach ($action_array as $key => $value)
                        {
                            if (fmod($row, 2) == 0)
                            { $row_l = "class='row1' onmouseover=\"this.className='row1hover';\" onmouseout=\"this.className='row1';\" "; }
                            else
                            { $row_l = "class='row2' onmouseover=\"this.className='row2hover';\" onmouseout=\"this.className='row2';\" "; }

                            echo "<tr " . $row_l . " style='height:20px;'>";
                                echo "<td style='width:90px;padding-left:20px;'>"
                                   . $user_list_array[$value['id_user']]['login'] . "</td>\n";
                                echo "<td style='width:150px;'>"
                                   . $user_list_array[$value['id_user']]['prenom'] . " "
                                   . $user_list_array[$value['id_user']]['nom'] . "</td>\n";
                                echo "<td style='width:150px;'>" . $value['dateheure'] . "</td>\n";

                                // Details link (companion .txt file)
                                echo "<td style='width:100px;text-align: center;'>";
                                if ($value['file_exist_txt'])
                                {
                                    echo "<a href='" . DIR_WS_DATA_EXPORT . $value['file_info'] . "' target='blank_'>";
                                        echo "<img src='" . DIR_WS_IMG_ICO . "detail.png' style='width:20px;cursor:pointer;'>";
                                    echo "</a>";
                                }
                                else { echo '-'; }
                                echo "</td>\n";

                                // Download link
                                echo "<td style='width:300px;'>";
                                if ($value['file_exist'])
                                {
                                    echo "<a href='" . DIR_WS_DATA_EXPORT . $value['file_export'] . "' download>"
                                       . $value['file_export'] . "</a>";
                                }
                                else { echo '-'; }
                                echo "</td>\n";

                            echo "</tr>\n";
                            $row++;
                        }
                    echo "</table>";
                echo "</div>";
            }
            else
            {
                echo "<div id='boxpopup' style='margin-left: 1%;'>\n";
                    echo "<p class='alert'>" . TEXT_LS_EXP_NO_RESULT . "</p>";
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
