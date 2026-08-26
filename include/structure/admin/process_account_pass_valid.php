<?php
/*
----------------------------------------
Copyright (c) 2025 - Vai-Natura
----------------------------------------
Account / password reset — step 3: new password submission
Validates the token, checks password strength rules, hashes and stores
the new password, activates the account, and sends a confirmation email.
Returns JSON.
----------------------------------------
*/

require('../../config.php');
require('../../database_tables.php');

require('../../function/date.php');
require('../../function/database.php');
require('../../function/html_output.php');
require('../../function/general.php');

require('../../text_content_' . LANGUAGE . '.php');

header('Content-Type: text/html; charset=utf-8');

$sql_link = mysqli_connect(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD, DB_DATABASE)
    or die('Cannot connect to the database');
mysqli_query($sql_link, 'SET NAMES UTF8');

$response = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
    $timezone_php = isset($_POST['timezonePhp']) ? $_POST['timezonePhp'] : 'UTC';
    date_default_timezone_set($timezone_php);

    $date_save           = new DateTime();
    $date_save_formatted = $date_save->format('Y-m-d');

    $id_login     = isset($_POST['loginId'])     ? (int)$_POST['loginId']      : '';
    $token        = isset($_POST['token'])        ? trim($_POST['token'])       : '';
    $pass_first   = isset($_POST['passFirst'])    ? $_POST['passFirst']         : '';
    $pass_confirm = isset($_POST['passConfirm'])  ? $_POST['passConfirm']       : '';
    $cleanToken   = mysqli_real_escape_string($sql_link, $token);

    if (empty($cleanToken))
    {
        $response['message']           = TEXT_AC_ERR_VALIDATION;
        $response['errors']['token']   = TEXT_AC_ERR_SESSION;
    }
    else
    {
        // Verify token and check that the code has already been cleared (code_verif_account='')
        $verifLogin_query = tep_db_query($sql_link,
            "SELECT DISTINCT u.id, u.email, u.login FROM " . TABLE_USER . " u"
            . " WHERE id=" . $id_login
            . " AND token='" . $cleanToken . "'"
            . " AND code_verif_account=''"
            . " LIMIT 1");
        $userData = tep_db_fetch_array($verifLogin_query);

        if (tep_db_num_rows($verifLogin_query) < 1)
        {
            $response['message']         = TEXT_AC_ERR_VALIDATION;
            $response['errors']['token'] = TEXT_AC_ERR_SESSION;
        }
        else
        {
            $user_login = $userData['login'];
            $user_email = $userData['email'];

            // ---- Password strength checks ----
            $errors = [];

            if ($pass_first !== $pass_confirm)
            {
                $errors['pass'] = TEXT_AC_PASS_ERR_MISMATCH;
            }
            elseif (strlen($pass_first) < 8)
            {
                $errors['pass'] = TEXT_AC_PASS_ERR_TOO_SHORT;
            }
            elseif (
                !preg_match("/[A-Z]/", $pass_first) ||
                !preg_match("/[a-z]/", $pass_first) ||
                !preg_match("/[!@#$%^&*(),.?\":{}|<>]/", $pass_first)
            )
            {
                $errors['pass'] = TEXT_AC_PASS_ERR_COMPLEXITY;
            }

            if (!empty($errors))
            {
                $response['success'] = false;
                $response['message'] = TEXT_AC_PASS_ERR_NOT_COMPLIANT;
                $response['errors']  = $errors;
            }
            else
            {
                // ---- Finalise: hash password, activate account, clear token ----
                $password_hashed = password_hash($pass_first, PASSWORD_BCRYPT);

                $sql_update = "UPDATE " . TABLE_USER . " SET"
                    . " password='"       . mysqli_real_escape_string($sql_link, $password_hashed) . "',"
                    . " active=1,"
                    . " token='',"
                    . " date_creation='"  . $date_save_formatted . "'"
                    . " WHERE id=" . $id_login;

                if (tep_db_query($sql_link, $sql_update))
                {
                    // ---- Confirmation email ----
                    $message_mail = "
                        <!DOCTYPE html>
                        <html><head><meta charset='UTF-8'></head>
                        <body style='margin:0;padding:20px;background-color:#ffffff;font-family:Arial,sans-serif;'>
                        <table width='100%' border='0' cellspacing='0' cellpadding='0'>
                          <tr><td>
                            <table width='600' border='0' cellspacing='0' cellpadding='0'>
                              <tr>
                                <td style='padding:20px 0;'>
                                  Hello " . $user_login . ",
                                  <br><br>
                                  Welcome to " . TITRE_SITE . ", which provides access to " . TITRE_SMALL . " data.
                                </td>
                              </tr>
                              <tr>
                                <td style='padding:20px 0;'>
                                  Access the platform login page:
                                  <a href='" . HTTP_SERVER . "'>" . TITRE_SMALL . "</a>
                                </td>
                              </tr>
                              <tr>
                                <td style='border-top:1px solid #eee;padding-top:20px;'>
                                  <p style='margin:0;font-size:12px;color:#666;font-style:italic;'>
                                    If you did not initiate this operation, your email address may have been misused.
                                    <br>
                                    Ignore this email or contact support: <a href='mailto:" . MAIL_CONTACT . "'>" . MAIL_CONTACT . "</a>
                                    <br><br>
                                    This email was generated automatically. Please do not reply.
                                  </p>
                                </td>
                              </tr>
                            </table>
                          </td></tr>
                        </table>
                        </body></html>";

                    $to           = $user_email;
                    $subject      = "Account activated on " . TITRE_SITE;
                    $from_email   = MAIL_NOREPLY;
                    $from_name    = MAIL_NOREPLY_NAME;
                    $headers      = [
                        'MIME-Version: 1.0',
                        'Content-type: text/html; charset=utf-8',
                        'From: "' . $from_name . '" <' . $from_email . '>',
                        'Reply-To: '    . $from_email,
                        'Return-Path: ' . $from_email,
                        'Errors-To: '   . $from_email,
                        'X-Mailer: PHP/' . phpversion(),
                    ];
                    $headers_string = implode("\r\n", $headers);

                    $mail_sent = mail($to, $subject, $message_mail, $headers_string, "-f" . $from_email);
                    if ($mail_sent)
                    {
                        $response['success'] = true;
                        $response['message'] = TEXT_AC_PASS_MSG_OK;
                        $response['message'] .= "<br>";
                        $response['message'] .= TEXT_AC_PASS_MSG_REDIRECT;
                    }
                    else
                    {
                        $response['success'] = false;
                        $response['message'] = TEXT_AC_PASS_ERR_TECH;
                    }
                }
            }
        }
    }
}
else
{
    $response['message'] = TEXT_AC_ERR_FORM;
}

echo json_encode($response);
exit;
?>
