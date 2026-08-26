<?php
/*
 * ----------------------------------------
 * Copyright (c) 2024 - Vai-Natura
 * ----------------------------------------
 * Logout page  (logout.php)
 *
 * Terminates the current user session:
 *   1. Deletes the session row from the database.
 *   2. Displays a confirmation message with a link back to login.php.
 *   3. Regenerates the session ID if the user had elevated privileges,
 *      then destroys the PHP session.
 *   4. Closes the database connection.
 * ----------------------------------------
 */

require('include/application_top.php');

// ----------------------------------------------------------------
// Delete the active session row for the current user
// ----------------------------------------------------------------
tep_db_query(
    $sql_link,
    "DELETE FROM " . TABLE_SESSION . "
     WHERE id = " . $tab_session['id']
);

// ----------------------------------------------------------------
// HTML output
// ----------------------------------------------------------------
require(DIR_WS_STRUCTURE . 'header_web.php');

echo "<body>";

    echo "<div id='contour_general' style='width:100%;margin:0;'>";

        echo "<div id='log_out'>";

            /* "You are on the application <App>" */
            echo "<p>"
                . LANG_LOGOUT_ON_APP
                . " <span style='color:#336699;'>" . TITRE_SMALL . "</span>"
                . "</p>";

            /* Session-ended confirmation */
            echo "<p><span style='font-weight:bold;'>"
                . LANG_LOGOUT_SESSION_ENDED
                . "</span></p>";

            /* Link back to the login page */
            echo "<p style='margin-bottom:30px;'>"
                . " - <a href='login.php'>" . LANG_LOGOUT_LINK_LOGIN . "</a> - "
                . "</p>";

        echo "</div>";

        /*
         * Security clean-up:
         *   - Regenerate session ID if the user had admin privileges.
         *   - Close the database connection.
         *   - Destroy the PHP session.
         */
        if ($autorisation) { regenerer_id($sql_link); }
        tep_db_close($sql_link);
        tep_session_end();

    echo "</div>";

echo "</body>";
echo "</html>";
