<?php
/*
 * ----------------------------------------
 * Copyright (c) 2024 - Vai-Natura
 * ----------------------------------------
 * Change password page  (mdp.php)
 *
 * Allows a logged-in user to update their own password:
 *   1. If the form is submitted (POST 'id' present), delegates validation
 *      and DB update to ctrl_mdp.php.
 *   2. Loads the current admin record to pass the stored hash as a hidden field.
 *   3. Renders a form with current password, new password (+ strength meter),
 *      and confirmation fields.
 * ----------------------------------------
 */

require('include/application_top.php');

// ----------------------------------------------------------------
// Initialise the user-facing message variable
// ----------------------------------------------------------------
$message_info = '';

// ----------------------------------------------------------------
// If the form was submitted, run server-side validation & update
// ----------------------------------------------------------------
if (isset($_POST['id']) && tep_not_null($_POST['id'])) {
    require(DIR_WS_FORMULAIRE . 'ctrl_mdp.php');
}

// ----------------------------------------------------------------
// Load the current admin record (needed for the old-password check)
// ----------------------------------------------------------------
$admin_query = tep_db_query(
    $sql_link,
    "SELECT * FROM " . TABLE_USER . " WHERE id = " . $tab_session['admin_id']
);
$admin = tep_db_fetch_array($admin_query);

// ----------------------------------------------------------------
// HTML output
// ----------------------------------------------------------------
require(DIR_WS_STRUCTURE . 'header_web.php');

echo "<body>";

require(DIR_WS_STRUCTURE . 'header.php');
include(DIR_WS_BOX . 'nav_accueil.php');

/* Display any validation or success message */
if (tep_not_null($message_info)) {echo "<div id='contenu_info'>" . $message_info . "</div>";}

echo "<div id='contour_general'>";

    echo "<div id='contenu_centre'>";

        echo "<div id='contenu_box2'>";

            echo "<h1>";
                echo "<span>" . LANG_MDP_TITLE . "</span>";
            echo "</h1>";

            echo "<div style='float:left;'>\n";

                echo "<div id='boxpopup' class='select-top' style='width:100%;padding:20px 0px;'>\n";

                    $lien_form = tep_href_link('mdp.php');
                    $name_form = 'mdp';

                    echo "<form name='" . $name_form . "' action='" . $lien_form . "'"
                        . " method='post' enctype='multipart/form-data'>";

                    /* Hidden: current user ID */
                    echo tep_draw_hidden_field('id', $tab_session['admin_id'], "class='input_texte'");

                    /* Hidden: stored hash — used by ctrl_mdp.php to verify the old password */
                    echo tep_draw_hidden_field('old_pass_table', $admin['password'], "class='input_texte'");

                        /* Current password */
                        echo "<div id='boite1'>";
                            echo "<h2>" . LANG_MDP_OLD_PASS . "</h2>";
                            echo tep_draw_password_field('old_pass', '', "class='titre'");
                        echo "</div>";

                        /* New password with real-time strength indicator */
                        echo "<div id='boite1'>";
                            echo "<h2>" . LANG_MDP_NEW_PASS . "</h2>";
                            echo "<input name='new_pass' maxlength='40' class='titre'"
                                . " type='password' onKeyUp='evalpass(this.value);'><br>";

                            echo "<ul class='quality_pass'>";
                                echo "<li id='faible' class='li_mdp'>" . LANG_MDP_STRENGTH_WEAK   . "</li>";
                                echo "<li id='moyen'  class='li_mdp'>" . LANG_MDP_STRENGTH_MEDIUM . "</li>";
                                echo "<li id='fort'   class='li_mdp'>" . LANG_MDP_STRENGTH_STRONG . "</li>";
                            echo "</ul>";

                        echo "<hr>";
                        echo "</div>";

                        /* Password confirmation */
                        echo "<div id='boite1' style='margin-bottom:0;' >";
                            echo "<h2>" . LANG_MDP_CONFIRM_PASS . "</h2>";
                            echo tep_draw_password_field('new_pass_confirm', '', "class='titre'");
                        echo "</div>";

                echo "</div>\n";

                /* Submit button triggers native form submission */
                echo "<input type='button' class='button' value='" . LANG_BTN_SAVE . "'"
                    . " style='margin-top:20px;' "
                    . " onClick='" . $name_form . ".submit();' />";

                echo "</form>";

            echo "</div>\n";

        echo "</div>\n";

    echo "<hr>";
    echo "</div>";

echo "<hr>";
echo "</div>";

require('include/application_bottom.php');

echo "</body>";
echo "</html>";
