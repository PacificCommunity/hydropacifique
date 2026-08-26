<?php
/*
 * ----------------------------------------
 * Copyright (c) 2015 - Vai-Natura
 * ----------------------------------------
 * Logout overlay block  (block_logout.php)
 *
 * Included by login.php when ?log=out is present.
 * Displays a logout confirmation and a button to return to login.php.
 *
 * Side-effect on load:
 *   If an active session row is found, its sid is cleared so the slot
 *   cannot be reused without a fresh login.
 * ----------------------------------------
 */

$today = date('d-m-Y');

$class_bv = "style='display:block;'";

// ----------------------------------------------------------------
// Invalidate the current session row in the database
// ----------------------------------------------------------------
$tab_session = getAdminInfo($sql_link);

if (isset($tab_session['id'])) {
    /*
     * Clear sid rather than deleting the row so that the
     * double-connexion check can still detect the recently-closed slot.
     */
    tep_db_query(
        $sql_link,
        "UPDATE " . TABLE_SESSION . "
         SET sid = ''
         WHERE id = " . $tab_session['id']
    );
}

// ----------------------------------------------------------------
// HTML output — logout confirmation card
// ----------------------------------------------------------------
echo "<div id='block_view_log' class='block_view' " . $class_bv . ">";

    /* "You have been logged out of <App>" */
    echo "<p style='margin-top:100px;"
        . "font-weight:300;"
        . "font-size:38px;"
        . "-webkit-font-smoothing:antialiased;"
        . "color:#000;"
        . "background-color:#fff;"
        . "padding:20px 0;'>";
        echo LANG_LOGOUT_CONFIRMED . ' ' . TITRE_SMALL;
    echo "</p>";

    echo "<div id='cadre_view' class='cadre_view'"
        . " style='width:335px;height:80px;margin-top:0px;'>";

        echo "<div id='cadre_limit'>";

            echo "<div id='log' style='margin-top:0px;'>\n";

                echo "<input type='submit' class='button'"
                    . " value='" . LANG_LOGOUT_BTN_BACK . "'"
                    . " onClick=\"location.replace('login.php')\" />\n";

                echo "</form>\n";

            echo "</div>\n";

        echo "</div>\n";

    echo "</div>\n";

echo "</div>\n";
