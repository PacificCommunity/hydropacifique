<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
File    : list_users.php
Purpose : Admin page — lists all non-admin accounts with their status.

Features:
  - Bulk active / inactive toggle via form submission (button_save)
  - Individual deletion via GET request (?del=id&csrf_token=...)
  - CSRF protection on both GET and POST actions
  - Input validation before any database operation
----------------------------------------
*/

require('include/application_top.php');

$action = false;

// -----------------------------------------------
// CSRF token — generated once per session and reused throughout.
// hash_equals() is used for comparison to prevent timing attacks.
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];


// -----------------------------------------------
// Individual user deletion (GET request)
if (isset($_GET['del']) && tep_not_null($_GET['del']))
{
    // Reject the request if the CSRF token is missing or does not match
    if (empty($_GET['csrf_token']) || !hash_equals($csrf_token, $_GET['csrf_token'])) {
        die("Unauthorized action.");
    }

    // Accept only positive integers as a valid user id
    $del_id = filter_var($_GET['del'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($del_id === false) {
        die("Invalid identifier.");
    }

    require(DIR_WS_ADMIN . 'suppr_user.php');
}


// -----------------------------------------------
// Bulk active / inactive status update (POST form submission)
if (isset($_POST['button_save']))
{
    // Reject the request if the CSRF token is missing or does not match
    if (empty($_POST['csrf_token']) || !hash_equals($csrf_token, $_POST['csrf_token'])) {
        die("Unauthorized action.");
    }

    require(DIR_WS_ADMIN . 'ctrl_user_active.php');
}


// -----------------------------------------------
// Fetch all non-admin users from the database.
// Text fields are sanitized via post_secure() then decoded for display.

$sql_fromData   = "SELECT DISTINCT id_service, name, description
                   FROM " . TABLE_SERVICE . "
                   ORDER BY id_service ASC";
$fromData_query = tep_db_query($sql_link, $sql_fromData);
while ($fromData = tep_db_fetch_array($fromData_query))
{
    $fromData_array[$fromData['id_service']] = [
        'name'        => html_entity_decode($fromData['name']        ?? ''),
        'description' => html_entity_decode($fromData['description'] ?? ''),
    ];
}

$user_array = [];
$user_query = tep_db_query($sql_link,
    "SELECT DISTINCT id, id_service, login, nom, prenom, email, info, date_creation, last_log, nb_log, active
     FROM " . TABLE_USER . "
     WHERE admin < 1
     ORDER BY login"
);

while ($user = tep_db_fetch_array($user_query))
{
    $name_service = $fromData_array[$user['id_service']]['name'];
    
    $user_array[] = [
        'id'            => (int) $user['id'],
        'name_service'  => $name_service,
        'login'         => html_entity_decode(post_secure($sql_link, $user['login'])),
        'nom'           => html_entity_decode(post_secure($sql_link, $user['nom'])),
        'prenom'        => html_entity_decode(post_secure($sql_link, $user['prenom'])),
        'email'         => html_entity_decode(post_secure($sql_link, $user['email'])),
        'info'          => html_entity_decode(post_secure($sql_link, $user['info'])),
        'date_creation' => post_secure($sql_link, $user['date_creation']),
        'last_log'      => post_secure($sql_link, $user['last_log']),
        'active'        => (int) post_secure($sql_link, $user['active']),
        'nb_log'        => (int) post_secure($sql_link, $user['nb_log']),
    ];
}




// -----------------------------------------------
// HTML output — page structure, action banner, and user table

require(DIR_WS_STRUCTURE . 'header_web.php');

// Row-link style — makes the <a> fill the entire cell so the whole cell is clickable
echo "<style>
    #table_tri td a.row-link {
        display: block;
        width: 100%;
        height: 100%;
        color: inherit;
        text-decoration: none;
    }
    #table_tri td a.row-link:hover {
        text-decoration: underline;
    }
</style>";
echo "<body>";

require(DIR_WS_STRUCTURE . 'header.php');
include(DIR_WS_BOX       . 'nav_accueil.php');

echo "<div id='contour_general'>";
    echo "<div id='contenu_centre'>";
        echo "<div id='contenu_box2'>";

            // Action feedback banner — green border on success, red on failure
            if ($action)
            {
                $border_info = $action_result
                    ? 'border:2px solid #09886d;'
                    : 'border:2px solid #930000;';
                echo "<div id='contenu_info' style='" . $border_info . "'>" . $message_action . "</div>";
            }

            $lien_form = tep_href_link('list_users.php');
            $name_form = 'user';

            // No file upload in this form — multipart/form-data is not needed
            echo "<form name='" . $name_form . "' action='" . $lien_form . "' method='post'>";

                // Embed CSRF token as a hidden field to protect the POST action
                echo "<input type='hidden' name='csrf_token' value='" . htmlspecialchars($csrf_token, ENT_QUOTES) . "'>";

                echo "<h1>";
                    echo "<span>" . TEXT_US_LIST_PAGE_TITLE . "</span>";      
                    
                    
                    echo "<input type='submit' class='button' name='button_save' value='" . TEXT_STATION_EDIT_SAVE . "'"
                        . " style='float:right;'/>";                

                echo "</h1>";

                echo "<div style='width:100%;height:25px;margin-bottom:10px;'>";
                    echo button_return('gestion.php');
                    echo "<p style='float:left;margin-left:25px;margin-top:5px;'>";
                        echo "<a href='modif_user.php' target='_blank' style='font-size:14px;'>" . TEXT_US_MENU_USER_NEW   . "</a>";
                    echo "</p>";
                echo "</div>";


                echo "<div style='max-width:95%;max-height:65vh;overflow-y:auto;'>";
                    echo "<table id='table_tri' cellspacing='0'>";
                        echo "<thead>";
                            echo "<tr class='header-row'>";
                                echo "<th style='width:150px;'>" . TEXT_US_LIST_COL_FROM           . "</th>";
                                echo "<th style='width:100px;'>" . TEXT_US_LIST_COL_LOGIN       . "</th>";
                                echo "<th style='width:120px;'>" . TEXT_US_LIST_COL_NOM         . "</th>";
                                echo "<th style='width:120px;'>" . TEXT_US_LIST_COL_PRENOM      . "</th>";
                                echo "<th style='width:180px;'>" . TEXT_US_LIST_COL_EMAIL       . "</th>";
                                echo "<th style='width:180px;'>" . TEXT_US_LIST_COL_INFO        . "</th>";
                                echo "<th style='width:150px;'>" . TEXT_US_LIST_COL_DATE_CREATE . "</th>";
                                echo "<th style='width:150px;'>" . TEXT_US_LIST_COL_LAST_LOG    . "</th>";
                                echo "<th style='text-align:center;width:80px;'>" . TEXT_US_LIST_COL_NB_LOG . "</th>";
                                echo "<th style='text-align:center;width:80px;'>" . TEXT_US_LIST_COL_ACTIVE . "</th>";
                                echo "<th style='text-align:center;width:80px;'>&nbsp;</th>";
                            echo "</tr>";
                        echo "</thead>";
                        echo "<tbody>";

                        echo "<tr><td class='lignevide' colspan='10'></td></tr>";

                        if (!empty($user_array))
                        {
                            $row = 0;
                            foreach ($user_array as $u)
                            {
                                $row_class  = ($row % 2 === 0) ? 'row1' : 'row2';
                                $row_hover  = ($row % 2 === 0) ? 'row1hover' : 'row2hover';
                                $row_attr   = "class='" . $row_class . "'"
                                            . " onmouseover=\"this.className='" . $row_hover . "';\""
                                            . " onmouseout=\"this.className='"  . $row_class . "';\"";

                                $lien_modif = "modif_user.php?ref=" . $u['id'];

                                // Append the CSRF token to the deletion URL (GET-based action)
                                $lien_suppr = "list_users.php?del=" . $u['id']
                                            . "&csrf_token=" . urlencode($csrf_token);

                                // Build a unique id/name for each checkbox to avoid duplicates
                                $checkbox_id = 'active_' . $u['id'];
                                $checked     = ($u['active'] === 1) ? 'checked' : '';

                                echo "<tr " . $row_attr . ">";
                                    // Clickable columns — <a> fills the entire cell for proper semantics,
                                    // keyboard accessibility, and native new-tab support (target=_blank)
                                    foreach (['name_service','login', 'nom', 'prenom', 'email', 'info', 'date_creation', 'last_log'] as $col)
                                    {
                                        echo "<td>"
                                           . "<a class='row-link' href='" . $lien_modif . "' target='_blank' rel='noopener noreferrer'>"
                                           . htmlspecialchars((string) $u[$col], ENT_QUOTES)
                                           . "</a></td>\n";
                                    }

                                    // Login count — same row-link pattern, centred
                                    echo "<td style='text-align:center;'>"
                                       . $u['nb_log']
                                       . "</td>\n";

                                    // Active status — checkbox state reflects current db value;
                                    // all checkboxes are submitted together on button_save
                                    echo "<td class='t_cont' style='text-align:center;'>";
                                        echo "<input type='checkbox'"
                                           . " name='" . $checkbox_id . "'"
                                           . " id='"   . $checkbox_id . "'"
                                           . " " . $checked . ">";
                                    echo "</td>\n";

                                    // Delete button — requires JS confirmation before following the link
                                    echo "<td class='t_icon' style='text-align:center;'>";
                                        if($u['nb_log'] < 1)
                                        {
                                            echo "<a style='font-size:12px;font-weight:bold;cursor:pointer;'"
                                            . " title='" . TEXT_US_LIST_BTN_DELETE . "'"
                                            . " href='" . $lien_suppr . "'"
                                            . ">X</a>";
                                        }
                                        else{echo "-";}
                                    echo "</td>\n";

                                echo "</tr>\n";
                                $row++;
                            }
                        }

                        echo "</tbody>";
                    echo "</table>";


                echo "</div>";

                

            echo "</form>";

        echo "</div>";
    echo "</div>";
echo "</div>";

require('include/application_bottom.php');
echo "</body>";
echo "</html>";
?>