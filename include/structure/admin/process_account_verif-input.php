<?php
/*
----------------------------------------
Copyright (c) 2025 - Vai-Natura
----------------------------------------
Account creation — step 1: form field validation
Validates login, email, and organisation. If valid, inserts the new
user record, creates a 6-digit verification code, and emails it to the
supplied address. Returns JSON to the calling JS.
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

    $login        = isset($_POST['login'])        ? trim($_POST['login'])        : '';
    $email        = isset($_POST['email'])        ? trim($_POST['email'])        : '';
    $organisation = isset($_POST['organisation']) ? trim($_POST['organisation']) : '';
    $lastname     = isset($_POST['lastname'])     ? trim($_POST['lastname'])     : '';
    $firstname    = isset($_POST['firstname'])    ? trim($_POST['firstname'])    : '';

    // ---- Login validation ----
    if (empty($login))
    {
        $response['errors']['login'] = TEXT_AC_CREATE_ERR_LOGIN_EMPTY;
    }
    elseif (!preg_match('/^[a-zA-Z0-9]+$/', $login))
    {
        $response['errors']['login'] = TEXT_AC_CREATE_ERR_LOGIN_CHARS;
    }
    else
    {
        $cleanLogin       = mysqli_real_escape_string($sql_link, $login);
        $verifLogin_query = tep_db_query($sql_link,
            "SELECT DISTINCT id FROM " . TABLE_USER . " WHERE login='" . $cleanLogin . "' LIMIT 1");
        if (tep_db_num_rows($verifLogin_query) > 0)
        {
            $response['errors']['login'] = TEXT_AC_CREATE_ERR_LOGIN_DUP;
        }
    }

    // ---- Email validation ----
    if (empty($email))
    {
        $response['errors']['email'] = TEXT_AC_CREATE_ERR_EMAIL_EMPTY;
    }
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL))
    {
        $response['errors']['email'] = TEXT_AC_CREATE_ERR_EMAIL_FORMAT;
    }
    else
    {
        $cleanEmail      = mysqli_real_escape_string($sql_link, $email);
        $verifMail_query = tep_db_query($sql_link,
            "SELECT DISTINCT id FROM " . TABLE_USER . " WHERE email='" . $cleanEmail . "' LIMIT 1");
        if (tep_db_num_rows($verifMail_query) > 0)
        {
            $response['errors']['email'] = TEXT_AC_CREATE_ERR_EMAIL_DUP;
        }
    }

    // ---- Organisation validation ----
    if (empty($organisation))
    {
        $response['errors']['organisation'] = TEXT_AC_CREATE_ERR_ORG_EMPTY;
    }

    // ---- All checks passed ----
    if (empty($response['errors']))
    {
        $code_verif = (string)random_int(100000, 999999);
        $token      = bin2hex(random_bytes(16));

        $data = [
            'login'   => mysqli_real_escape_string($sql_link, $login),
            'nom'     => mysqli_real_escape_string($sql_link, $lastname),
            'prenom'  => mysqli_real_escape_string($sql_link, $firstname),
            'email'   => mysqli_real_escape_string($sql_link, $email),
            'info'    => mysqli_real_escape_string($sql_link, $organisation),
            'token'   => mysqli_real_escape_string($sql_link, $token),
        ];

        $sql_insert = "INSERT INTO " . TABLE_USER
            . " (login, nom, prenom, email, info, code_verif_account, date_code_verif, token, active)"
            . " VALUES ('" . $data['login']  . "','" . $data['nom']    . "','" . $data['prenom']
            . "','"  . $data['email']  . "','" . $data['info']   . "','" . $code_verif
            . "','"  . $today_time_formatted  . "','" . $data['token'] . "', 0)";

        if (tep_db_query($sql_link, $sql_insert))
        {
            $id_login = mysqli_insert_id($sql_link);

            tep_db_query($sql_link,
                "INSERT INTO " . TABLE_USER_ACCES . " (id, gestion_data, parametre, config)"
                . " VALUES (" . $id_login . ", 0, 0, 0)");
            tep_db_query($sql_link,
                "INSERT INTO " . TABLE_USER_MENU . " (id_user, menu_id, is_open)"
                . " VALUES (" . $id_login . ", 'all', 1)");

            // ---- Verification email ----
            $message_mail = "
                <!DOCTYPE html>
                <html><head><meta charset='UTF-8'></head>
                <body style='margin:0;padding:20px;background-color:#ffffff;font-family:Arial,sans-serif;'>
                <table width='100%' border='0' cellspacing='0' cellpadding='0'>
                  <tr><td align='center'>
                    <table width='1000' border='0' cellspacing='0' cellpadding='0'>
                      <tr>
                        <td align='center' style='padding-bottom:20px;border-bottom:1px solid #000;'>
                          <h1 style='margin:0;font-size:28px;color:#000;font-weight:bold;'>" . TITRE_SITE . "</h1>
                          <p style='margin-top:10px;font-size:26px;color:#000;'>" . TITRE_SMALL . "</p>
                        </td>
                      </tr>
                      <tr>
                        <td align='center' style='padding:20px 0;'>
                          <h1 style='margin:0;font-size:28px;color:#000;font-weight:bold;'>Confirmation code</h1>
                          <p style='margin-top:0;font-size:24px;color:#000;'>Account creation</p>
                        </td>
                      </tr>
                      <tr>
                        <td align='center' style='padding-bottom:20px;'>
                          <p style='margin:0;font-size:18px;color:#333;'>Hello " . $login . "</p>
                          <p style='margin:0;margin-top:20px;font-size:18px;color:#333;'>Use the following code to verify your identity.</p>
                          <p style='margin:0;margin-top:5px;font-size:18px;color:#333;'>This code is valid for 15 minutes.</p>
                        </td>
                      </tr>
                      <tr>
                        <td align='center' style='padding-bottom:40px;'>
                          <span style='font-size:42px;font-weight:bold;font-family:sans-serif;'>" . $code_verif . "</span>
                        </td>
                      </tr>
                      <tr>
                        <td align='center' style='padding-bottom:30px;'>
                          Enter your code here:
                          <a href='" . HTTP_SERVER . "account_confirm.php?id=" . $id_login . "&tk=" . $token . "'>Verify your identity</a>
                        </td>
                      </tr>
                      <tr>
                        <td align='center' style='border-top:1px solid #eee;padding-top:20px;'>
                          <p style='margin:0;font-size:16px;color:#666;font-style:italic;'>If you did not initiate this request, your email address may have been misused.</p>
                          <p style='margin:0;font-size:16px;color:#666;font-style:italic;'>Ignore this email or contact support: <a href='mailto:" . MAIL_CONTACT . "'>" . MAIL_CONTACT . "</a></p>
                          <p style='margin:0;margin-top:10px;font-size:16px;color:#666;font-style:italic;'>This email was generated automatically. Please do not reply.</p>
                        </td>
                      </tr>
                    </table>
                  </td></tr>
                </table>
                </body></html>";

            $to           = $data['email'];
            $subject      = "Account creation on " . TITRE_SITE . " — Confirmation code";
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
                $response['message'] = TEXT_AC_CREATE_MSG_OK;
            }
            else
            {
                $response['success'] = false;
                $response['message'] = TEXT_AC_CREATE_ERR_MAIL;
            }
        }
    }
    else
    {
        $response['message']  = TEXT_AC_ERR_FORM;
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
