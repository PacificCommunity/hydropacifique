<?php
/*
 * ----------------------------------------
 * Copyright (c) 2024 - Vai-Natura
 * ----------------------------------------
 * Set / reset password page  (account_valid.php)
 *
 * Third and final step of the account-creation / password-reset flow.
 * The user sets their password after verifying the emailed code.
 *
 * URL parameters (both required):
 *   ?id=<int>   : user ID
 *   &tk=<string>: one-time token issued after code validation
 *
 * Guard: invalid id / token pair → redirect to account_new.php.
 *
 * AJAX endpoint:
 *   POST → include/structure/admin/process_account_pass_valid.php
 *          (that file must include: require('../../text_content_'.LANGUAGE.'.php');)
 *
 * On success redirects to login.php?log=1.
 * Includes a show/hide password toggle (eye icon) for both fields.
 * ----------------------------------------
 */

require('include/application_top_account.php');

// ----------------------------------------------------------------
// Initialise the user-facing message variable
// ----------------------------------------------------------------
$message_info = '';

// ----------------------------------------------------------------
// Validate URL parameters: id and token
// ----------------------------------------------------------------
$login_get   = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$token_url   = isset($_GET['tk']) ? $_GET['tk'] : '';

$clean_token = mysqli_real_escape_string($sql_link, $token_url);
$login_id    = mysqli_real_escape_string($sql_link, $login_get);

/* Verify the id / token pair still exists in the database */
$sql_check = "SELECT id
              FROM " . TABLE_USER . "
              WHERE id    = " . $login_id . "
                AND token = '" . $clean_token . "'
              LIMIT 1";

$res_check = tep_db_query($sql_link, $sql_check);

if (tep_db_num_rows($res_check) < 1) {
    header('Location: account_new.php');
    exit;
}

// ----------------------------------------------------------------
// HTML output
// ----------------------------------------------------------------
require(DIR_WS_STRUCTURE . 'header_web.php');

echo "<body style='background-color: #000;'>";

    $color_top = $color_service;

    echo "<div style='margin-bottom:10px;height:45px;padding:10px 0;background-color:" . $color_top . ";'>";
        echo "<p style='float:right;margin-right:2%;padding-top:5px;font-size:28px;'>";
            echo TITRE_SMALL;
        echo "</p>";
    echo "</div>";

    $file_bkg = BACKGROUND_ACCOUNT;
    echo "<div style='background-image:url(" . $file_bkg . ");background-repeat:no-repeat;padding-top:30px;'>";

        echo "<div id='cadre_view_log' style='width:450px;height:400px;margin-left:500px;padding:15px;"
            . "background-color:#F8F8F8;opacity:1;"
            . "box-shadow:0px 4px 10px rgba(0,0,0,0.2);"
            . "border-radius:4px;border:none;'>";

            echo "<div id='log' style='width:95%;margin:0 10px;'>\n";

                echo "<h1 style='position:relative;margin-bottom:5px;font-size:24px;'>";
                    echo LANG_VALID_TITLE;
                echo "</h1>";

                /* Password requirements notice */
                echo "<div style='margin-bottom:10px;'>\n";
                    echo "<p style='margin-bottom:5px;font-weight:bold;text-align:left;'>";
                        echo LANG_VALID_PASS_RULE;
                        echo "<br>";
                        echo "<span style='font-weight:normal;font-size:14px;'>";
                            echo LANG_VALID_PASS_HINT;
                        echo "</span>";
                    echo "</p>";
                echo "<hr>\n";
                echo "</div>\n";

                /* New password field with visibility toggle */
                echo "<div style='margin-bottom:20px;'>\n";

                    echo "<p style='margin-bottom:5px;font-weight:bold;text-align:left;'>";
                        echo LANG_VALID_PASS_FIRST;
                    echo "</p>";

                    echo "<div style='float:left;width:70%;position:relative;'>\n";
                        echo "<input type='password' id='pass_first' name='pass_first'"
                            . " style='width:100%;height:40px;padding:0 15px;font-size:16px;box-sizing:border-box;'"
                            . " placeholder='" . LANG_VALID_PASS_FIRST_PH . "' />";

                        echo "<span onclick=\"togglePass('pass_first', this)\""
                            . " style='position:absolute;right:12px;top:8px;cursor:pointer;opacity:0.6;'>";
                        echo '<svg id="icon_passFirst" width="24" height="24" viewBox="0 0 24 24"'
                            . ' fill="none" stroke="currentColor" stroke-width="2"'
                            . ' stroke-linecap="round" stroke-linejoin="round">'
                            . '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>'
                            . '<circle cx="12" cy="12" r="3"></circle></svg>';
                        echo "</span>";
                    echo "</div>\n";

                echo "<hr>\n";
                echo "</div>\n";

                /* Password confirmation field with visibility toggle */
                echo "<div style='margin-bottom:10px;'>\n";

                    echo "<p style='margin-bottom:5px;font-weight:bold;text-align:left;'>";
                        echo LANG_VALID_PASS_CONFIRM;
                    echo "</p>";

                    echo "<div style='float:left;width:70%;position:relative;'>\n";
                        echo "<input type='password' id='pass_confirm' name='pass_confirm'"
                            . " style='width:100%;height:40px;padding:0 15px;font-size:16px;box-sizing:border-box;'"
                            . " placeholder='" . LANG_VALID_PASS_CONFIRM_PH . "' />";

                        echo "<span onclick=\"togglePass('pass_confirm', this)\""
                            . " style='position:absolute;right:12px;top:8px;cursor:pointer;opacity:0.6;'>";
                        echo '<svg id="icon_passConfirm" width="24" height="24" viewBox="0 0 24 24"'
                            . ' fill="none" stroke="currentColor" stroke-width="2"'
                            . ' stroke-linecap="round" stroke-linejoin="round">'
                            . '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>'
                            . '<circle cx="12" cy="12" r="3"></circle></svg>';
                        echo "</span>";
                    echo "</div>\n";

                echo "<hr>\n";
                echo "</div>\n";

                /* Submit button + spinner */
                echo "<div style='width:100%;'>\n";
                    echo "<input type='button' class='button'"
                        . " id='submit_confirmpass' name='submit_confirmpass'"
                        . " style='width:50%;'"
                        . " value='" . LANG_BTN_VALIDATE . "' />";
                    echo "<img src='" . DIR_WS_IMG . "wait.gif'"
                        . " style='width:50px;display:none;' id='submit_wait'>";
                echo "</div>\n";

                echo "<hr>\n";

                /* Inline validation message area */
                echo "<div id='msg_valid' style='width:100%;text-align:left;font-size:16px;color:#930000;'>\n";
                echo "</div>\n";

            echo "<hr>\n";
            echo "</div>\n";

        echo "</div>\n";

    echo "</div>\n";

    echo "</div>";

    echo "<div id='fond_log_img' style='margin-top:5%;'>";
        echo "<img src='" . BACKGROUND_LOG_FOOTER . "' style='width:100%;'>";
    echo "</div>";

    require('include/application_bottom.php');

echo "</body>";
echo "</html>";
?>


<script>
/*
 * On "Validate" click:
 *   1. Show spinner, hide button.
 *   2. POST both password values + token to process_account_pass_valid.php.
 *   3. success=true  → green message, redirect to login.php?log=1 (2 s).
 *      success=false → restore button, show per-field errors.
 *
 * togglePass(inputId, element):
 *   Switches a field between password/text and updates the eye icon SVG.
 */

    var territoireId   = '<?php echo $territoire_id; ?>';
    var territoireInit = '<?php echo $territoire_init; ?>';
    var timezonePhp    = '<?php echo $timezone_php; ?>';

    var loginId = '<?php echo $login_id; ?>';
    var token   = '<?php echo $clean_token; ?>';

    document.addEventListener('DOMContentLoaded', function () {

        var btnSubmit   = document.getElementById('submit_confirmpass');
        var btnWait     = document.getElementById('submit_wait');
        var msgValid    = document.getElementById('msg_valid');
        var passFirst   = document.getElementById('pass_first');
        var passConfirm = document.getElementById('pass_confirm');

        btnSubmit.addEventListener('click', function (e) {

            e.preventDefault();

            btnSubmit.style.display = 'none';
            btnWait.style.display   = 'block';
            msgValid.innerHTML      = '';

            var dataToSend = new FormData();
            dataToSend.append('territoireId',   territoireId);
            dataToSend.append('territoireInit', territoireInit);
            dataToSend.append('timezonePhp',    timezonePhp);
            dataToSend.append('loginId',        loginId);
            dataToSend.append('token',          token);
            dataToSend.append('passFirst',      passFirst.value);
            dataToSend.append('passConfirm',    passConfirm.value);

            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'include/structure/admin/process_account_pass_valid.php', true);

            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status === 200) {

                    try {
                        var jsonResponse = JSON.parse(xhr.responseText);
                    } catch (e) {
                        console.error('JSON parse error', e);
                        return;
                    }

                    var success = jsonResponse['success'];
                    var message = jsonResponse['message'];
                    var errors  = jsonResponse['errors'];

                    if (success) {

                        msgValid.style.color = '#28a745';
                        msgValid.innerHTML   = message;

                        setTimeout(function () {
                            window.location.href = 'login.php?log=1';
                        }, 2000);

                    } else {

                        btnSubmit.style.display = 'block';
                        btnWait.style.display   = 'none';

                        msgValid.style.color = '#930000';
                        msgValid.innerHTML   = "<span style='font-weight:bold;'>" + message + "</span>";

                        if (errors) {
                            if (errors['token']) {
                                msgValid.innerHTML += '<br>' + errors['token'] + '<br>';
                            }
                            if (errors['pass']) {
                                msgValid.innerHTML += '<br>' + errors['pass'] + '<br>';
                            }
                        }
                    }
                }
            };

            xhr.send(dataToSend);
        });
    });

    function togglePass(inputId, element) {

        var input = document.getElementById(inputId);
        var svg   = element.querySelector('svg');

        if (input.type === 'password') {
            input.type = 'text';
            svg.innerHTML =
                '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8'
                + 'a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4'
                + 'c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07'
                + 'a3 3 0 1 1-4.24-4.24"></path>'
                + '<line x1="1" y1="1" x2="23" y2="23"></line>';
        } else {
            input.type = 'password';
            svg.innerHTML =
                '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>'
                + '<circle cx="12" cy="12" r="3"></circle>';
        }
    }
</script>
