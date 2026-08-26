<?php
/*
 * ----------------------------------------
 * Copyright (c) 2024 - Vai-Natura
 * ----------------------------------------
 * Login page  (login.php)
 *
 * Handles user authentication:
 *   1. Redirects to index.php if a valid session already exists.
 *   2. Validates POST credentials against the database.
 *   3. Guards against brute-force attempts via ip_login_enforce().
 *   4. On success: records the session row and redirects to index.php.
 *   5. On failure: populates $message_info with a translated error string.
 *
 * Includes either block_login.php (normal) or block_logout.php (?log=out).
 * ----------------------------------------
 */

require('include/application_top.php');

// ----------------------------------------------------------------
// Initialise the user-facing message variable
// ----------------------------------------------------------------
$message_info = '';

// ----------------------------------------------------------------
// If a valid session already exists, skip the login form
// ----------------------------------------------------------------
$tab_session = getAdminInfo($sql_link);
if (isset($tab_session['admin_id']) && tep_not_null($tab_session['admin_id'])) {
    tep_redirect('index.php');
}

// ----------------------------------------------------------------
// Process login form submission
// ----------------------------------------------------------------
if (isset($_POST['login']) && isset($_POST['password'])) {

    /* Brute-force / IP rate-limit check */
    if (ip_login_enforce($sql_link)) {
        $message_info = LANG_LOGIN_BRUTE_FORCE;
        tep_redirect('error.html');
        tep_db_close($sql_link);
        die();
    }

    /* Sanitise inputs before using them in SQL */
    $login    = mysqli_real_escape_string($sql_link, trim($_POST['login']));
    $password = mysqli_real_escape_string($sql_link, trim($_POST['password']));

    /* Fetch the matching active user (accepts login name or email) */
    $user_query = tep_db_query(
        $sql_link,
        "SELECT id, password
         FROM " . TABLE_USER . "
         WHERE active = 1
           AND (login = '" . $login . "' OR email = '" . $login . "')"
    );
    $user = tep_db_fetch_array($user_query);

    if (tep_not_null($user)) {

        /* Verify the supplied password against the stored hash */
        if (tep_validate_password($password, $user['password'])) {

            /*
             * Legacy password upgrade:
             * Old accounts used plain MD5 (no leading '$').
             * Re-hash with PASSWORD_DEFAULT on first successful login.
             */
            if (strpos($user['password'], '$') !== 0) {
                $new_secure_hash = password_hash($password, PASSWORD_DEFAULT);
                tep_db_query(
                    $sql_link,
                    "UPDATE " . TABLE_USER . "
                     SET password = '" . $new_secure_hash . "'
                     WHERE id = " . $user['id']
                );
            }

            /*
             * Single-session enforcement — the newest login wins.
             *
             * A session row outlives its browser: only logout.php deletes one,
             * so closing the tab leaves sid set until the idle window expires.
             * Refusing the login in that state made the FIRST attempt fail
             * every time (it cleared sid and told the user to retry), which is
             * why logging in twice appeared to be necessary.
             *
             * Instead, supersede the old slot. Deleting every row for this
             * account also reaps the sid='' rows the old flow left behind —
             * they used to accumulate indefinitely, one per login. A genuinely
             * concurrent browser loses its session on its next page load,
             * because getAdminInfo() no longer finds a row for its sid.
             */
            double_connexion($sql_link, $user['id'], $login); // logs to ctrl_ip_suspect

            tep_db_query(
                $sql_link,
                "DELETE FROM " . TABLE_SESSION . "
                 WHERE admin_id = " . (int) $user['id']
            );

            $now = new DateTime('now', new DateTimeZone($timezone_php));

            /* Record the successful login and create the session row */
            tep_db_query(
                $sql_link,
                "UPDATE " . TABLE_USER . "
                 SET last_log = '" . $now->format('Y-m-d H:i:s') . "', nb_log = nb_log + 1
                 WHERE id = '" . (int) $user['id'] . "'"
            );

            tep_db_query(
                $sql_link,
                "INSERT INTO " . TABLE_SESSION . "
                    (sid, admin_id, date_connect, heure_connect,
                    last_access, ip, browser)
                VALUES (
                    '" . session_id() . "',
                    " . (int) $user['id'] . ",
                    '" . $now->format('Y-m-d') . "',
                    '" . $now->format('H:i:s') . "',
                    " . time() . ",
                    '" . getIP() . "',
                    '" . getUser_agent() . "'
                )"
            );

            tep_redirect('index.php');

        } else {
            $message_info = LANG_LOGIN_BAD_CREDENTIALS;
        }

    } else {
        $message_info = LANG_LOGIN_BAD_CREDENTIALS;
    }
}

// ----------------------------------------------------------------
// HTML output
// ----------------------------------------------------------------
require(DIR_WS_STRUCTURE . 'header_web.php');

echo "<body style='background-color: #000;'>";

    /* Top banner — colour varies by application version */
    $color_top = $color_service;
    if (HP_VERSION == 'Nomad') { $color_top = '#3282B8'; }

    echo "<div style='margin-bottom:10px;height:45px;padding:10px 0;background-color:" . $color_top . ";'>";
        echo "<p style='float:right;margin-right:2%;padding-top:5px;font-size:28px;'>";
            echo TITRE_SMALL;
        echo "</p>";
    echo "</div>";

    /* Full-width background image */
    echo "<div id='fond_log_img'>";
        $file_bkg = BACKGROUND_LOG;
        if (HP_VERSION == 'Nomad') { $file_bkg = BACKGROUND_LOG_NOMAD; }
        echo "<img src='" . $file_bkg . "' style='width:100%;'>";
    echo "</div>";

    /* "Log in" trigger link that reveals the login overlay */
    echo "<div style='margin-left:43%;'>";
        echo "<a href='#' onClick=\"document.getElementById('block_view_log').style.display='block';\">";
            echo "<div id='index_bconnect'>" . LANG_BTN_LOGIN . "</div>";
        echo "</a>";
    echo "</div>";

    /* Footer decorative image */
    echo "<div id='fond_log_img' style='margin-top:15%;'>";
        echo "<img src='" . BACKGROUND_LOG_FOOTER . "' style='width:100%;'>";
    echo "</div>";

    /*
     * Include the appropriate overlay block:
     *   ?log=out  → user just logged out
     *   (default) → show the login form
     */
    if (isset($_GET['log']) && $_GET['log'] == 'out') {
        require(DIR_WS_LOG . 'block_logout.php');
    } else {
        require(DIR_WS_LOG . 'block_login.php');
    }

    require('include/application_bottom.php');

echo "</body>";
echo "</html>";
