<?php
/*
 * ----------------------------------------
 * Copyright (c) 2024 - Vai-Natura
 * ----------------------------------------
 * Account confirmation page  (account_confirm.php)
 *
 * Second step of the account-creation / password-reset flow.
 * The user enters the 6-digit code received by email.
 *
 * URL parameters (both required):
 *   ?id=<int>   : new user ID
 *   &tk=<string>: one-time security token
 *
 * Guard: invalid id or token → redirect to account_new.php.
 *
 * AJAX endpoints:
 *   POST process_account_valid.php         → validate the entered code
 *        (must include: require('../../text_content_'.LANGUAGE.'.php');)
 *   POST process_account_verif-logmail.php → resend the confirmation code
 *        (must include: require('../../text_content_'.LANGUAGE.'.php');)
 *
 * On code validation success the page redirects to account_valid.php.
 * ----------------------------------------
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require('include/application_top_account.php');

// ----------------------------------------------------------------
// Initialise the user-facing message variable
// ----------------------------------------------------------------
$message_info = '';

// ----------------------------------------------------------------
// Validate URL parameters: id and token
// ----------------------------------------------------------------
$login_get = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$login_id  = mysqli_real_escape_string($sql_link, $login_get);

$email     = '';
$token_url = '';

if ($login_id <= 0) {
    header('Location: account_new.php');
    exit;
}

$token_url   = isset($_GET['tk']) ? $_GET['tk'] : '';
$clean_token = mysqli_real_escape_string($sql_link, $token_url);

$sql_verifLogin = "SELECT DISTINCT id, login, email
                   FROM " . TABLE_USER . "
                   WHERE id = " . $login_id . "
                     AND token = '" . $clean_token . "'
                   LIMIT 1";

$verifLogin_query = tep_db_query($sql_link, $sql_verifLogin);
$verifLogin       = tep_db_fetch_array($verifLogin_query);

if (tep_db_num_rows($verifLogin_query) < 1) {
    header('Location: account_new.php');
    exit;
} else {
    $email = $verifLogin['email'];
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

        echo "<div id='cadre_view_log' style='width:520px;height:380px;margin:0px auto;padding:15px;"
            . "background-color:#F8F8F8;opacity:1;"
            . "box-shadow:0px 4px 10px rgba(0,0,0,0.2);"
            . "border-radius:4px;border:none;'>";

            echo "<div id='log' style='width:95%;margin:0 10px;'>\n";

                echo "<h1 style='position:relative;margin-bottom:10px;"
                    . "text-align:center;font-size:24px;'>";
                    echo LANG_CONFIRM_TITLE;
                echo "</h1>";

                /* Email sent notice + code input */
                echo "<div style='margin-bottom:10px;'>\n";

                    echo "<p style='margin-bottom:5px;text-align:center;'>";
                        echo LANG_CONFIRM_EMAIL_SENT;
                    echo "</p>";

                    echo "<p style='margin-bottom:20px;text-align:center;font-size:18px;font-weight:bold;'>";
                        echo $email;
                    echo "</p>";

                    echo "<p style='margin-bottom:5px;font-weight:bold;text-align:center;font-size:20px;'>";
                        echo LANG_CONFIRM_ENTER_CODE;
                    echo "</p>";

                    echo "<input name='code_confirm' id='code_confirm' class='input_account'"
                        . " placeholder='" . LANG_CONFIRM_CODE_PH . "'"
                        . " style='margin-top:10px;padding:20px 0;font-size:22px;text-align:center;'"
                        . " type='text'>";

                echo "<hr>\n";
                echo "</div>\n";

                /* Resend link */
                echo "<div style='margin-bottom:10px;'>\n";
                    echo "<p style='font-size:16px;'>";
                        echo LANG_CONFIRM_RESEND_PRE;
                        echo "<a class='link' style='color:#28a745;' onclick='sendCode();'>";
                            echo LANG_CONFIRM_RESEND;
                        echo "</a>";
                    echo "</p>";
                echo "<hr>\n";
                echo "</div>\n";

                /* Validate button + spinner */
                echo "<div style='width:100%;'>\n";
                    echo "<input type='button' class='button'"
                        . " id='submit_confirmaccount' name='submit_confirmaccount'"
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
 * Validate button:
 *   - Client-side guard: code must be >= 6 chars.
 *   - POST to process_account_valid.php.
 *   - success=true  → redirect to account_valid.php (2 s).
 *   - success=false → highlight field, show errors.
 *
 * sendCode():
 *   - POST the registered email to process_account_verif-logmail.php.
 *   - success=true  → reload account_confirm.php with the new token.
 */

    var territoireId   = '<?php echo $territoire_id; ?>';
    var territoireInit = '<?php echo $territoire_init; ?>';
    var timezonePhp    = '<?php echo $timezone_php; ?>';

    var loginId = '<?php echo $login_id; ?>';
    var token   = '<?php echo $clean_token; ?>';

    var btnSubmit = document.getElementById('submit_confirmaccount');
    var btnWait   = document.getElementById('submit_wait');
    var msgValid  = document.getElementById('msg_valid');

    document.addEventListener('DOMContentLoaded', function () {

        btnSubmit.addEventListener('click', function (e) {

            e.preventDefault();

            btnSubmit.style.display = 'none';
            btnWait.style.display   = 'block';
            msgValid.innerHTML      = '';

            var codeConfirm = document.getElementById('code_confirm');
            codeConfirm.style.border = '';

            /* Client-side minimum length check */
            if (codeConfirm.value.length < 6) {
                btnSubmit.style.display  = 'block';
                btnWait.style.display    = 'none';
                msgValid.innerHTML       = '<?php echo LANG_CONFIRM_CODE_SHORT; ?>';
                codeConfirm.style.border = '1px solid #FF0000';
                return;
            }

            var dataToSend = new FormData();
            dataToSend.append('territoireId',   territoireId);
            dataToSend.append('territoireInit', territoireInit);
            dataToSend.append('timezonePhp',    timezonePhp);
            dataToSend.append('loginId',        loginId);
            dataToSend.append('token',          token);
            dataToSend.append('codeConfirm',    codeConfirm.value);

            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'include/structure/admin/process_account_valid.php', true);

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

                        var token_new = jsonResponse['token'];

                        setTimeout(function () {
                            window.location.href =
                                'account_valid.php?id=' + loginId + '&tk=' + token_new;
                        }, 2000);

                    } else {

                        btnSubmit.style.display = 'block';
                        btnWait.style.display   = 'none';

                        msgValid.style.color = '#930000';
                        msgValid.innerHTML   = "<span style='font-weight:bold;'>" + message + "</span>";

                        if (errors) {
                            if (errors['token']) {
                                msgValid.innerHTML += '<br>' + errors['token'];
                            }
                            if (errors['code']) {
                                codeConfirm.style.border = '1px solid #FF0000';
                                msgValid.innerHTML += '<br>' + errors['code'];
                            }
                        }
                    }
                }
            };

            xhr.send(dataToSend);
        });
    });

    function sendCode() {

        btnSubmit.style.display = 'none';
        btnWait.style.display   = 'block';
        msgValid.innerHTML      = '';

        var verifCptField = '<?php echo $email; ?>';

        var dataToSend = new FormData();
        dataToSend.append('territoireId',   territoireId);
        dataToSend.append('territoireInit', territoireInit);
        dataToSend.append('timezonePhp',    timezonePhp);
        dataToSend.append('verifCptField',  verifCptField);

        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'include/structure/admin/process_account_verif-logmail.php', true);

        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4 && xhr.status === 200) {

                btnSubmit.style.display = 'block';
                btnWait.style.display   = 'none';

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

                    var loginId   = jsonResponse['loginId'];
                    var token_new = jsonResponse['token'];

                    setTimeout(function () {
                        window.location.href =
                            'account_confirm.php?id=' + loginId + '&tk=' + token_new;
                    }, 2000);

                } else {

                    msgValid.innerHTML = "<span style='font-weight:bold;'>" + message + "</span>";

                    if (errors && errors['compte']) {
                        msgValid.innerHTML += errors['compte'];
                    }
                }
            }
        };

        xhr.send(dataToSend);
    }
</script>
