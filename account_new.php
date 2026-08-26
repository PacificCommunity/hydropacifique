<?php
/*
 * ----------------------------------------
 * Copyright (c) 2024 - Vai-Natura
 * ----------------------------------------
 * New account creation page  (account_new.php)
 *
 * Renders the registration form (username, email, organisation,
 * last name, first name).  All validation is done asynchronously:
 *
 *   POST → include/structure/admin/process_account_verif-input.php
 *          (that file must include: require('../../text_content_'.LANGUAGE.'.php');)
 *
 * On success the server returns JSON with the new user ID and a
 * one-time token; the page redirects to account_confirm.php.
 * ----------------------------------------
 */

require('include/application_top_account.php');

// Création de compte autorisée uniquement en accès ouvert
if (HP_ACCES != 'Open') {
    header('Location: login.php');
    exit;
}

// ----------------------------------------------------------------
// Initialise the user-facing message variable
// ----------------------------------------------------------------
$message_info = '';

// ----------------------------------------------------------------
// HTML output
// ----------------------------------------------------------------
require(DIR_WS_STRUCTURE . 'header_web.php');

echo "<body style='background-color: #000;'>";

    /* Top banner */
    $color_top = $color_service;

    echo "<div style='margin-bottom:10px;height:45px;padding:10px 0;background-color:" . $color_top . ";'>";
        echo "<p style='float:right;margin-right:2%;padding-top:5px;font-size:28px;'>";
            echo TITRE_SMALL;
        echo "</p>";
    echo "</div>";

    /* Page background */
    $file_bkg = BACKGROUND_ACCOUNT;
    echo "<div style='background-image:url(" . $file_bkg . ");background-repeat:no-repeat;padding-top:30px;'>";

        /* Centered form card */
        echo "<div id='cadre_view_log' style='width:550px;height:390px;margin:0px auto;padding:15px;"
            . "background-color:#F8F8F8;opacity:1;"
            . "box-shadow:0px 4px 10px rgba(0,0,0,0.2);"
            . "border-radius:4px;border:none;'>";

            echo "<div id='log' style='width:95%;margin:0 10px;'>\n";

                echo "<h1 style='position:relative;margin-bottom:10px;font-size:24px;'>";
                    echo LANG_ACCT_NEW_TITLE;
                echo "</h1>";

                /* Username field */
                echo "<div style='margin-bottom:10px;'>\n";
                    echo "<p style='margin-bottom:5px;font-weight:bold;text-align:left;'>";
                        echo LANG_ACCT_NEW_LOGIN;
                        echo "<br>";
                        echo "<span style='font-weight:normal;font-size:14px;'>";
                            echo LANG_ACCT_NEW_LOGIN_HINT;
                        echo "</span>";
                    echo "</p>";
                    echo "<input name='login' id='login' class='input_account'"
                        . " placeholder='" . LANG_ACCT_NEW_LOGIN_PH . "'"
                        . " type='text' style='width:47%;'>";
                echo "<hr>\n";
                echo "</div>\n";

                /* Left column: email + organisation */
                echo "<div style='float:left;width:47%;'>\n";

                    echo "<div style='margin-bottom:10px;'>\n";
                        echo "<p style='margin-bottom:5px;font-weight:bold;text-align:left;'>";
                            echo LANG_ACCT_NEW_EMAIL;
                        echo "</p>";
                        echo "<input name='email' id='email' class='input_account'"
                            . " placeholder='" . LANG_ACCT_NEW_EMAIL_PH . "' type='text'>";
                    echo "<hr>\n";
                    echo "</div>\n";

                    echo "<div style='margin-bottom:10px;'>\n";
                        echo "<p style='margin-bottom:5px;font-weight:bold;text-align:left;'>";
                            echo LANG_ACCT_NEW_ORGA;
                        echo "</p>";
                        echo "<input name='organisation' id='organisation' class='input_account'"
                            . " placeholder='" . LANG_ACCT_NEW_ORGA_PH . "' type='text'>";
                    echo "<hr>\n";
                    echo "</div>\n";

                echo "</div>\n";

                /* Right column: last name + first name */
                echo "<div style='float:right;width:47%;'>\n";

                    echo "<div style='margin-bottom:10px;'>\n";
                        echo "<p style='margin-bottom:5px;font-weight:bold;text-align:left;'>";
                            echo LANG_ACCT_NEW_LASTNAME;
                        echo "</p>";
                        echo "<input name='lastname' id='lastname' class='input_account'"
                            . " placeholder='" . LANG_ACCT_NEW_LASTNAME_PH . "' type='text'>";
                    echo "<hr>\n";
                    echo "</div>\n";

                    echo "<div style='margin-bottom:10px;'>\n";
                        echo "<p style='margin-bottom:5px;font-weight:bold;text-align:left;'>";
                            echo LANG_ACCT_NEW_FIRSTNAME;
                        echo "</p>";
                        echo "<input name='firstname' id='firstname' class='input_account'"
                            . " placeholder='" . LANG_ACCT_NEW_FIRSTNAME_PH . "' type='text'>";
                    echo "<hr>\n";
                    echo "</div>\n";

                echo "</div>\n";

                /* Submit button + loading spinner */
                echo "<div style='float:left;width:30%;'>\n";
                    echo "<input type='button' class='button'"
                        . " id='submit_newaccount' name='submit_newaccount'"
                        . " style='width:100%;margin-top:35px;'"
                        . " value='" . LANG_BTN_SAVE . "' />";
                    echo "<img src='" . DIR_WS_IMG . "wait.gif'"
                        . " style='width:50px;display:none;' id='submit_wait'>";
                echo "</div>\n";

                /* Inline validation message area */
                echo "<div style='float:right;width:57%;margin-top:15px;"
                    . "text-align:left;font-size:16px;color:#930000;'"
                    . " id='msg_valid'>\n";
                echo "</div>\n";

            echo "<hr>\n";
            echo "</div>\n";

        echo "</div>\n";

    echo "</div>\n";

    echo "</div>";

    /* Footer image */
    echo "<div id='fond_log_img' style='margin-top:5%;'>";
        echo "<img src='" . BACKGROUND_LOG_FOOTER . "' style='width:100%;'>";
    echo "</div>";

    require('include/application_bottom.php');

echo "</body>";
echo "</html>";
?>


<script>
/*
 * On "Save" click:
 *   1. Show spinner, hide button.
 *   2. Reset any red-border highlights from a previous attempt.
 *   3. POST all fields to process_account_verif-input.php.
 *   4. success=true  → green message, redirect to account_confirm.php (2 s).
 *      success=false → restore button, highlight invalid fields in red.
 */

    var territoireId   = '<?php echo $territoire_id; ?>';
    var territoireInit = '<?php echo $territoire_init; ?>';
    var timezonePhp    = '<?php echo $timezone_php; ?>';

    document.addEventListener('DOMContentLoaded', function () {

        var btnSubmit = document.getElementById('submit_newaccount');
        var btnWait   = document.getElementById('submit_wait');
        var msgValid  = document.getElementById('msg_valid');

        btnSubmit.addEventListener('click', function (e) {

            e.preventDefault();

            btnSubmit.style.display = 'none';
            btnWait.style.display   = 'block';
            msgValid.innerHTML      = '';

            var loginField     = document.getElementById('login');
            var emailField     = document.getElementById('email');
            var orgaField      = document.getElementById('organisation');
            var lastnameField  = document.getElementById('lastname');
            var firstnameField = document.getElementById('firstname');

            /* Reset all border highlights */
            var inputs = document.getElementsByClassName('input_account');
            for (var i = 0; i < inputs.length; i++) {
                inputs[i].style.border = '';
            }

            var dataToSend = new FormData();
            dataToSend.append('territoireId',   territoireId);
            dataToSend.append('territoireInit', territoireInit);
            dataToSend.append('timezonePhp',    timezonePhp);
            dataToSend.append('login',          loginField.value);
            dataToSend.append('email',          emailField.value);
            dataToSend.append('organisation',   orgaField.value);
            dataToSend.append('lastname',       lastnameField.value);
            dataToSend.append('firstname',      firstnameField.value);

            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'include/structure/admin/process_account_verif-input.php', true);

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

                        var newLoginId = jsonResponse['loginId'];
                        var token_new  = jsonResponse['token'];

                        setTimeout(function () {
                            window.location.href =
                                'account_confirm.php?id=' + newLoginId + '&tk=' + token_new;
                        }, 2000);

                    } else {

                        btnSubmit.style.display = 'block';
                        btnWait.style.display   = 'none';

                        msgValid.innerHTML = "<span style='font-weight:bold;'>" + message + "</span>";

                        if (errors) {
                            if (errors['login']) {
                                loginField.style.border  = '1px solid #FF0000';
                                msgValid.innerHTML += errors['login'] + '<br>';
                            }
                            if (errors['email']) {
                                emailField.style.border  = '1px solid #FF0000';
                                msgValid.innerHTML += errors['email'] + '<br>';
                            }
                            if (errors['organisation']) {
                                orgaField.style.border   = '1px solid #FF0000';
                                msgValid.innerHTML += errors['organisation'];
                            }
                        }
                    }
                }
            };

            xhr.send(dataToSend);
        });
    });
</script>
