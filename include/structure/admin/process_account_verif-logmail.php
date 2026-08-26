<?php
/*
----------------------------------------
Copyright (c) 2025 - Vai-Natura
----------------------------------------
Password reset — step 1: login / email check
Finds the account matching the supplied login or email, generates a
new verification code and token, and emails the code. Returns JSON.
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

    $today_time           = new DateTime();
    $today_time_formatted = $today_time->format('Y-m-d H:i:s');

    $verifCptField = isset($_POST['verifCptField']) ? trim($_POST['verifCptField']) : '';

    // ---- Account lookup ----
    if (empty($verifCptField))
    {
        $response['errors']['compte'] = TEXT_AC_RESET_ERR_FIELD_EMPTY;
    }
    else
    {
        $cleanField     = mysqli_real_escape_string($sql_link, $verifCptField);
        $verifCpt_query = tep_db_query($sql_link,
            "SELECT DISTINCT id, login, email FROM " . TABLE_USER
            . " WHERE login='" . $cleanField . "' OR email='" . $cleanField . "' LIMIT 1");
        $userCpt = tep_db_fetch_array($verifCpt_query);

        if (tep_db_num_rows($verifCpt_query) < 1)
        {
            $response['errors']['compte'] = TEXT_AC_RESET_ERR_NOT_FOUND;
        }
        else
        {
            $id_login = $userCpt['id'];
            $login    = $userCpt['login'];
            $email    = $userCpt['email'];
        }
    }

    // ---- Send verification code ----
    if (empty($response['errors']))
    {
        $code_verif = (string)random_int(100000, 999999);
        $token      = bin2hex(random_bytes(16));
        $cleanToken = mysqli_real_escape_string($sql_link, $token);

        tep_db_query($sql_link,
            "UPDATE " . TABLE_USER . " SET"
            . " code_verif_account='" . $code_verif          . "',"
            . " date_code_verif='"   . $today_time_formatted . "',"
            . " token='"             . $cleanToken           . "'"
            . " WHERE id=" . $id_login);

        // ---- Verification email ----
        $message_mail = "
            <!DOCTYPE html>
            <html><head><meta charset='UTF-8'></head>
            <body style='margin:0;padding:20px;background-color:#ffffff;font-family:Arial,sans-serif;'>
            <table width='100%' border='0' cellspacing='0' cellpadding='0'>
              <tr><td align='center'>
                <table width='1000' border='0' cellspacing='0' cellpadding='0'>
                  <tr>
                    <td align='center' style='padding-bottom:5px;border-bottom:1px solid #000;'>
                      <h1 style='margin:0;font-size:28px;color:#000;font-weight:bold;'>" . TITRE_SITE . "</h1>
                      <p style='margin-top:10px;font-size:26px;color:#000;'>" . TITRE_SMALL . "</p>
                    </td>
                  </tr>
                  <tr>
                    <td align='center' style='padding:20px 0;'>
                      <h1 style='margin:0;font-size:28px;color:#000;font-weight:bold;'>" . TEXT_MAIL_RESET_TITLE . "</h1>
                      <p style='margin-top:0;font-size:24px;color:#000;'>" . TEXT_MAIL_RESET_SUBTITLE . "</p>
                    </td>
                  </tr>
                  <tr>
                    <td align='center' style='padding-bottom:20px;'>
                      <p style='margin:0;font-size:18px;color:#333;'>" . TEXT_MAIL_RESET_HELLO . " " . $login . "</p>
                      <p style='margin:0;margin-top:20px;font-size:18px;color:#333;'>" . TEXT_MAIL_RESET_INSTRUCTION . "</p>
                      <p style='margin:0;margin-top:5px;font-size:18px;color:#333;'>" . TEXT_MAIL_RESET_VALIDITY . "</p>
                    </td>
                  </tr>
                  <tr>
                    <td align='center' style='padding-bottom:40px;'>
                      <span style='font-size:42px;font-weight:bold;font-family:sans-serif;'>" . $code_verif . "</span>
                    </td>
                  </tr>
                  <tr>
                    <td align='center' style='padding-bottom:30px;'>
                      " . TEXT_MAIL_RESET_ENTER_CODE . "
                      <a href='" . HTTP_SERVER . "account_confirm.php?id=" . $id_login . "&tk=" . $token . "'>" . TEXT_MAIL_RESET_LINK_LABEL . "</a>
                    </td>
                  </tr>
                  <tr>
                    <td align='center' style='border-top:1px solid #eee;padding-top:20px;'>
                      <p style='margin:0;font-size:16px;color:#666;font-style:italic;'>" . TEXT_MAIL_RESET_WARN_MISUSE . "</p>
                      <p style='margin:0;font-size:16px;color:#666;font-style:italic;'>" . TEXT_MAIL_RESET_WARN_IGNORE . " <a href='mailto:" . MAIL_CONTACT . "'>" . MAIL_CONTACT . "</a></p>
                      <p style='margin:0;margin-top:10px;font-size:16px;color:#666;font-style:italic;'>" . TEXT_MAIL_RESET_AUTO_GENERATED . "</p>
                    </td>
                  </tr>
                </table>
              </td></tr>
            </table>
            </body></html>";

        $to           = $email;
        $subject      = sprintf(TEXT_MAIL_RESET_SUBJECT, TITRE_SITE);
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
            $response['loginId'] = $id_login;
            $response['token']   = $token;
            $response['message'] = TEXT_AC_RESET_MSG_OK;
            $response['message'] .= "<br>";
            $response['message'] .= TEXT_AC_RESET_MSG_REDIRECT;
        }
        else
        {
            $response['success'] = false;
            $response['message'] = TEXT_AC_ERR_MAIL;
        }
    }
    else
    {
        $response['message']  = TEXT_AC_ERR_VALIDATION;
        $response['message'] .= "<br>";
    }
}
else
{
    $response['message'] = TEXT_AC_ERR_REQUEST;
}

echo json_encode($response);
exit;
?>