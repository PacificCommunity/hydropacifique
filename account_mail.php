<?php
/*
 * ----------------------------------------
 * Copyright (c) 2024 - Vai-Natura
 * ----------------------------------------
 * Forgot password page  (account_mail.php)
 *
 * The user enters their username or email; the server looks up the
 * account, generates a token, and sends a confirmation code by email.
 *
 * AJAX endpoint:
 *   POST → include/structure/admin/process_account_verif-logmail.php
 *          (that file must include: require('../../text_content_'.LANGUAGE.'.php');)
 *
 * On success redirects to account_confirm.php with the user ID and token.
 * ----------------------------------------
 */

require('include/application_top_account.php');

// ----------------------------------------------------------------
// Initialise the user-facing message variable
// ----------------------------------------------------------------
$message_info = '';

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
    echo "<div style='height:60%;background-image:url(" . $file_bkg . ");background-repeat:no-repeat;padding-top:30px;'>";

        echo "<div id='cadre_view_log' style='width:450px;height:300px;margin:0px auto;padding:15px;"
            . "background-color:#F8F8F8;opacity:1;"
            . "box-shadow:0px 4px 10px rgba(0,0,0,0.2);"
            . "border-radius:4px;border:none;'>";

            echo "<div id='log' style='width:95%;margin:0 10px;'>\n";

                echo "<h1 style='position:relative;margin-bottom:10px;font-size:24px;'>";
                    echo LANG_MAIL_TITLE;
                echo "</h1>";

                /* Username / email field */
                echo "<div style='margin-bottom:10px;'>\n";

                    echo "<p style='margin-bottom:5px;font-weight:bold;text-align:left;'>";
                        echo LANG_MAIL_FIELD_LABEL;
                    echo "</p>";

                    echo "<div style='width:90%;'>\n";
                        echo "<input type='text' id='verif_cpt' name='verif_cpt'"
                            . " style='width:100%;height:40px;padding:0 15px;font-size:16px;box-sizing:border-box;'"
                            . " placeholder='" . LANG_MAIL_FIELD_PH . "' />";
                    echo "</div>\n";

                echo "<hr>\n";
                echo "</div>\n";

                /* Submit button + spinner */
                echo "<div style='width:100%;'>\n";
                    echo "<input type='button' class='button'"
                        . " id='submit_newpass' name='submit_newpass'"
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
 *   2. POST the username/email to process_account_verif-logmail.php.
 *   3. success=true  → green message, redirect to account_confirm.php (2 s).
 *      success=false → restore button, highlight field in red.
 */

    var territoireId   = '<?php echo $territoire_id; ?>';
    var territoireInit = '<?php echo $territoire_init; ?>';
    var timezonePhp    = '<?php echo $timezone_php; ?>';

    document.addEventListener('DOMContentLoaded', function () {

        var btnSubmit = document.getElementById('submit_newpass');
        var btnWait   = document.getElementById('submit_wait');
        var msgValid  = document.getElementById('msg_valid');

        btnSubmit.addEventListener('click', function (e) {

            e.preventDefault();

            btnSubmit.style.display = 'none';
            btnWait.style.display   = 'block';
            msgValid.innerHTML      = '';

            var verifCptField = document.getElementById('verif_cpt');
            verifCptField.style.border = '';

            var dataToSend = new FormData();
            dataToSend.append('territoireId',   territoireId);
            dataToSend.append('territoireInit', territoireInit);
            dataToSend.append('timezonePhp',    timezonePhp);
            dataToSend.append('verifCptField',  verifCptField.value);

            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'include/structure/admin/process_account_verif-logmail.php', true);

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

                        var loginId   = jsonResponse['loginId'];
                        var token_new = jsonResponse['token'];

                        setTimeout(function () {
                            window.location.href =
                                'account_confirm.php?id=' + loginId + '&tk=' + token_new;
                        }, 2000);

                    } else {

                        btnSubmit.style.display = 'block';
                        btnWait.style.display   = 'none';

                        msgValid.innerHTML = "<span style='font-weight:bold;'>" + message + "</span>";

                        if (errors && errors['compte']) {
                            verifCptField.style.border = '1px solid #FF0000';
                            msgValid.innerHTML += errors['compte'] + '<br>';
                        }
                    }
                }
            };

            xhr.send(dataToSend);
        });
    });
</script>
