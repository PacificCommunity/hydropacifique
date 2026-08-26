<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Platform activity log — lists all recorded actions with filters for
user, action type, and recency (delay).
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


// -----------------------------------------------
// Form filter values

// User filter
if (isset($_POST['select_user']) && $_POST['select_user'] != 0)
{
    $select_user_encours = $_POST['select_user'];
    $where_and_user      = " AND id_user=" . $select_user_encours;
}

// Action type filter
if (isset($_POST['select_action']) && $_POST['select_action'] != 0)
{
    $select_action_encours = $_POST['select_action'];
    $where_and_action      = " AND type_action=" . $select_action_encours;
}

// Recency filter (delay in days; default 182 on first load)
$delai_encours = 182;
if (isset($_POST['select_delai']))
{
    $delai_encours = $_POST['select_delai'];
}
$having_delai = '';
if ($delai_encours > 0) { $having_delai = " HAVING DATEDIFF(NOW(), dateheure) <= " . $delai_encours; }


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
$delai_array  = [];
$delai_query  = tep_db_query($sql_link,
    "SELECT DISTINCT id, periode, nb_days FROM " . TABLE_TOURNEE_PERIODE . " ORDER BY nb_days ASC");
while ($delai_tab = tep_db_fetch_array($delai_query))
{
    $delai_array[$delai_tab['id']] = [
        'periode' => html_entity_decode($delai_tab['periode'] ?? ''),
        'nb_days' => $delai_tab['nb_days'],
    ];
}


// -----------------------------------------------
// Action list query

$nb_actions   = 0;
$action_array = [];

$action_query = tep_db_query($sql_link,
    "SELECT DISTINCT id, id_user, type_action, info, dateheure
     FROM " . TABLE_ACTIONS . "
     WHERE 1=1" . $where_and_user . $where_and_action . $having_delai . "
     ORDER BY dateheure DESC");

while ($action_tab = tep_db_fetch_array($action_query))
{
    $id          = $action_tab['id'];
    $dateheure   = new DateTime($action_tab['dateheure']);
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
        'id_user'      => $action_tab['id_user'],
        'type_action'  => $action_tab['type_action'],
        'info'         => html_entity_decode($action_tab['info'] ?? ''),
        'dateheure'    => $dateheure->format('d-m-Y H:i:s'),
        'delai_action' => $delai_action,
        'text_delai'   => $text_delai,
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

            echo "<h1><span>" . TEXT_LS_ACT_PAGE_TITLE . "</span></h1>";

            $lien_form = tep_href_link('list_actions.php');
            echo "<form name='form_actions' action='" . $lien_form . "' method='post' enctype='multipart/form-data'>";

                echo "<div id='cadre_graph' style='float:left;width:14%;height:70vh;overflow-y: auto;'>\n";
                    echo "<div id='boxpopup' class='select-top' style='width:92%;margin:0px;padding: 0 3%;'>\n";

                        // ---- User filter ----
                        echo "<p style='float:left;width:70px;margin-top:15px;padding-top:5px;'>"
                           . TEXT_LS_FILTER_USER . "</p>";
                        echo "<select name='select_user' id='select_user' onchange='form_actions.submit();'"
                           . " style='float:right;width:120px;margin-top:15px;'>";
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

                        // ---- Action type filter ----
                        echo "<p style='float:left;width:70px;padding-top:5px;'>"
                           . TEXT_LS_FILTER_ACTION . "</p>";
                        echo "<select name='select_action' id='select_action' onchange='form_actions.submit();'"
                           . " style='float:right;width:120px;'>";
                            echo "<option value='0'>-</option>";
                            if (!empty($action_type_array))
                            {
                                foreach ($action_type_array as $key => $value)
                                {
                                    $sel = ($key == $select_action_encours) ? 'selected' : '';
                                    echo "<option value='" . $key . "' " . $sel . ">" . $value . "</option>";
                                }
                            }
                        echo "</select>";
                        echo "<hr>\n";

                        // ---- Delay filter ----
                        echo "<p style='float:left;width:70px;padding-top:5px;'>"
                           . TEXT_LS_FILTER_DELAY . "</p>";
                        echo "<select name='select_delai' id='select_delai' onchange='form_actions.submit();'"
                           . " style='float:right;width:120px;'>";
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

                        // ---- Action count ----
                        echo "<div id='contenu_infos'>";
                            echo "<p><span style='margin:0px;'>"
                               . TEXT_LS_ACT_NB_ACTIONS
                               . number_format($nb_actions, 0, '.', ' ')
                               . "</span></p>";
                        echo "</div>";
                        echo "<hr>";

                    echo "</div>";
                echo "</div>";

            echo "</form>";


            // ---- Results table ----
            if (!empty($action_array) && $nb_actions > 0)
            {
                echo "<div class='table-container' style='float:left;width:80%;height:75vh;margin-left:1%;'>";
                    echo "<table id='table_tri' cellspacing='0'>";
                        echo "<thead>";
                            echo "<tr class='header-row'>";
                                echo "<th style='width:90px;font-size:12px;padding-left:20px;'>"  . TEXT_LS_COL_LOGIN   . "</th>";
                                echo "<th style='width:150px;font-size:12px;'>"                   . TEXT_LS_COL_NAME    . "</th>";
                                echo "<th style='width:130px;font-size:12px;'>"                   . TEXT_LS_COL_TYPE    . "</th>";
                                echo "<th style='width:90px;font-size:12px;'>"                    . TEXT_LS_ACT_COL_DELAY  . "</th>";
                                echo "<th style='width:150px;font-size:12px;'>"                   . TEXT_LS_ACT_COL_DATE   . "</th>";
                                echo "<th style='width:500px;font-size:12px;'>"                   . TEXT_LS_ACT_COL_DETAIL . "</th>";
                            echo "</tr>";
                        echo "</thead>";

                        echo "<tr><td colspan='6' style='height:15px;'>&nbsp;</td></tr>";

                        foreach ($action_array as $key => $value)
                        {
                            
                            if (fmod($row, 2) == 0)
                            { $row_l = "class='row1' onmouseover=\"this.className='row1hover';\" onmouseout=\"this.className='row1';\" "; }
                            else
                            { $row_l = "class='row2' onmouseover=\"this.className='row2hover';\" onmouseout=\"this.className='row2';\" "; }

                            $id_user = $value['id_user'] ?? 0;
                            $login   = $user_list_array[$id_user]['login']  ?? '-';
                            $prenom  = $user_list_array[$id_user]['prenom'] ?? '';
                            $nom     = $user_list_array[$id_user]['nom']    ?? '';

                            echo "<tr " . $row_l . " style='height:20px;'>";
                                echo "<td style='padding-left:20px;'>" . $login . "</td>\n";
                                echo "<td>" . trim($prenom . " " . $nom) . "</td>\n";
                                echo "<td>"
                                    . (isset($action_type_array[$value['type_action']])
                                        ? $action_type_array[$value['type_action']] : '-')
                                . "</td>\n";
                                echo "<td style='padding-left:25px;'>" . ($value['delai_action'] ?? '') . "</td>\n";
                                echo "<td>" . ($value['dateheure'] ?? '') . "</td>\n";
                                echo "<td>" . ($value['info']      ?? '') . "</td>\n";
                            echo "</tr>\n";
                            $row++;
                        }
                    echo "</table>";
                echo "</div>";
            }
            else
            {
                echo "<div id='boxpopup' style='margin-left: 1%;'>\n";
                    echo "<p class='alert'>" . TEXT_LS_ACT_NO_RESULT . "</p>";
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
