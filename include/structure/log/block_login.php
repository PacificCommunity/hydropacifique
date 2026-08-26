<?php
/*
 * ----------------------------------------
 * Copyright (c) 2025 - Vai-Natura
 * ----------------------------------------
 * Login overlay block  (block_login.php)
 *
 * Included by login.php. The card is hidden by default and revealed:
 *   - By clicking the "Log in" link on the landing page, or
 *   - Automatically when $message_info is set (login error), or
 *   - Automatically when ?log=1 is present (post-account-creation redirect).
 *
 * The card contains:
 *   - Username / email + password fields
 *   - Error message area ($message_info)
 *   - Connect / Cancel buttons
 *   - Forgot password link → account_mail.php
 *   - Create account link  → account_new.php (only when HP_ACCES === 'Open')
 *
 * Keyboard: Escape closes the overlay.
 * ----------------------------------------
 */

$today = date('d-m-Y');

// ----------------------------------------------------------------
// Determine whether the overlay should be visible on load
// ----------------------------------------------------------------
$style_display = '';

if (!empty($message_info)) {
    $style_display = 'display:block;';
}
if (isset($_GET['log']) && $_GET['log'] == 1) {
    $style_display = 'display:block;';
}

// ----------------------------------------------------------------
// HTML output
// ----------------------------------------------------------------
echo "<div id='block_view_log' class='block_view'"
    . " style='top:13%;background:none;" . $style_display . "'>\n";

    echo "<div id='cadre_view_log' style='width:400px;height:380px;margin:0px auto;padding:15px;"
        . "background-color:#F8F8F8;opacity:1;"
        . "box-shadow:0px 4px 10px rgba(0,0,0,0.2);"
        . "border-radius:4px;border:none;'>";

        echo "<div id='log' style='margin-top:0px;'>\n";

            echo "<h1 style='position:relative;text-align:center;"
                . "margin-bottom:10px;font-size:24px;'>";

                echo "<span>";
                    echo LANG_BLOCK_LOGIN_TITLE;
                echo "</span>";
            
            echo "</h1>";

            /* Login form — POSTs to login.php for server-side processing */
            echo "<form name='login' action='login.php' method='post'"
                . " enctype='multipart/form-data'>\n";

                /* Username or email */
                echo "<input name='login' maxlength='40'"
                    . " placeholder='" . LANG_BLOCK_LOGIN_FIELD_PH . "'"
                    . " type='text'>\n";

                echo "<hr>\n";

                /* Password */
                echo "<input name='password' maxlength='40'"
                    . " type='password'"
                    . " placeholder='" . LANG_BLOCK_LOGIN_PASS_PH . "'>\n";

                echo "<hr>\n";

                /* Error message area */
                echo "<div style='height:50px;margin-top:5px;'>\n";
                    echo "<p style='font-size:14px;color:#930000;'>";
                        if (!empty($message_info)) { echo $message_info; }
                    echo "</p>";
                echo "</div>\n";

                /* Submit button — type='submit' posts the form on its own.
                   No onClick handler here: the form is name='login' and so is the
                   username field, and an inline handler resolves `login` against
                   the form's named elements first, so `login.submit()` hits the
                   input (TypeError) rather than the form. Calling form.submit()
                   would also skip HTML validation and any onsubmit handler. */
                echo "<input type='submit' class='button'"
                    . " value='" . LANG_BLOCK_LOGIN_BTN_CONNECT . "' />\n";

                echo "<hr>\n";

                /* Cancel link — hides the overlay */
                echo "<p><a onClick=\"document.getElementById('block_view_log').style.display='none';\">";
                    echo LANG_BLOCK_LOGIN_BTN_CANCEL;
                echo "</a></p>\n";

            echo "</form>\n";

            /* Secondary links */
            echo "<div style='margin-top:10px;padding-top:20px;border-top:1px solid #ccc;'>\n";

                echo "<p>";
                    echo "<a class='link' href='account_mail.php' target='_blank'>"
                        . LANG_BLOCK_LOGIN_FORGOT_PASS
                        . "</a>";
                echo "</p>";

                /* "Create an account" shown only for open-registration applications */
                if (HP_ACCES == 'Open') {
                    echo "<p style='margin-top:15px;'>";
                        echo "<a class='link' href='account_new.php' target='_blank'>"
                            . LANG_BLOCK_LOGIN_CREATE_ACCT
                            . "</a>";
                    echo "</p>";
                }

            echo "</div>\n";

        echo "</div>\n";

    echo "</div>\n";

echo "</div>\n";

echo "</div>\n";
?>


<script type="text/javascript">

    var blockLog = document.getElementById('block_view_log');

    /* Pressing Escape closes the login overlay */
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            blockLog.style.display = 'none';
        }
    });

</script>
