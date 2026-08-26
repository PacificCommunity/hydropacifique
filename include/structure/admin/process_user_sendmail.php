<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Welcome mail sender — called via AJAX from form_user_1.php.
Generates a token and sends a welcome email with a link to
account_mail.php so the user can set their password.
Returns JSON : { erreur: bool, msg_info: string }
----------------------------------------
*/

// Prevent PHP warnings from corrupting the JSON response
ini_set('display_errors', 0);
error_reporting(E_ALL);
 
require('../../config.php');
require('../../database_tables.php');
 
require('../../function/date.php');
require('../../function/gestion_erreur.php');
require('../../function/database.php');
require('../../function/html_output.php');
require('../../function/general.php');
 
require('../../text_content_' . LANGUAGE . '.php');
 
header('Content-Type: application/json; charset=utf-8');
 
$sql_link = mysqli_connect(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD, DB_DATABASE)
    or die('Cannot connect to the database');
mysqli_query($sql_link, 'SET NAMES UTF8');
 
$response = [
    'erreur'   => false,
    'msg_info' => '',
];
 
// -----------------------------------------------
// Guard : POST only
 
if ($_SERVER['REQUEST_METHOD'] !== 'POST')
{
    $response['erreur']   = true;
    $response['msg_info'] = TEXT_AC_ERR_REQUEST;
    echo json_encode($response);
    exit;
}
 
// -----------------------------------------------
// Retrieve ref_id
 
if (!isset($_POST['ref_id']) || !tep_not_null($_POST['ref_id']))
{
    $response['erreur']   = true;
    $response['msg_info'] = TEXT_AC_ERR_REQUEST;
    echo json_encode($response);
    exit;
}
 
$ref_id = (int) post_secure($sql_link, $_POST['ref_id']);
 
// -----------------------------------------------
// Fetch user
 
$user_query = tep_db_query($sql_link,
    "SELECT DISTINCT id, login, email FROM " . TABLE_USER . " WHERE id=" . $ref_id . " LIMIT 1");
$user = tep_db_fetch_array($user_query);
 
if (tep_db_num_rows($user_query) < 1 || !tep_not_null($user['email']))
{
    $response['erreur']   = true;
    $response['msg_info'] = TEXT_US_WELCOME_ERR_NOT_FOUND;
    echo json_encode($response);
    exit;
}
 
$login = $user['login'];
$email = $user['email'];
 
// -----------------------------------------------
// Build welcome email
 
$lien_activation = HTTP_SERVER . "account_mail.php";
 
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
              <h1 style='margin:0;font-size:28px;color:#000;font-weight:bold;'>" . TEXT_US_WELCOME_MAIL_TITLE . "</h1>
            </td>
          </tr>
          <tr>
            <td align='center' style='padding-bottom:30px;'>
              <p style='margin:0;font-size:18px;color:#333;'>" . TEXT_MAIL_RESET_HELLO . " " . $login . "</p>
              <p style='margin:0;margin-top:20px;font-size:18px;color:#333;'>" . TEXT_US_WELCOME_MAIL_BODY . "</p>
            </td>
          </tr>
          <tr>
            <td align='center' style='padding-bottom:30px;'>
              <a href='" . $lien_activation . "'>" . TEXT_US_WELCOME_MAIL_BTN_LINK . "</a>
            </td>
          </tr>
          <tr>
            <td align='center' style='border-top:1px solid #eee;padding-top:20px;'>
              <p style='margin:0;font-size:16px;color:#666;font-style:italic;'>" . TEXT_MAIL_RESET_WARN_IGNORE . " <a href='mailto:" . MAIL_CONTACT . "'>" . MAIL_CONTACT . "</a></p>
              <p style='margin:0;margin-top:10px;font-size:16px;color:#666;font-style:italic;'>" . TEXT_MAIL_RESET_AUTO_GENERATED . "</p>
            </td>
          </tr>
        </table>
      </td></tr>
    </table>
    </body></html>";
 
$to             = $email;
$subject        = sprintf(TEXT_US_WELCOME_MAIL_SUBJECT, TITRE_SITE);
$from_email     = MAIL_NOREPLY;
$from_name      = MAIL_NOREPLY_NAME;
$headers        = [
    'MIME-Version: 1.0',
    'Content-type: text/html; charset=utf-8',
    'From: "' . $from_name . '" <' . $from_email . '>',
    'Reply-To: '    . $from_email,
    'Return-Path: ' . $from_email,
    'Errors-To: '   . $from_email,
    'X-Mailer: PHP/' . phpversion(),
];
$headers_string = implode("\r\n", $headers);
 
// -----------------------------------------------
// Send and respond
 
$mail_sent = mail($to, $subject, $message_mail, $headers_string, "-f" . $from_email);
 
if ($mail_sent)
{
    $response['msg_info'] = TEXT_US_WELCOME_MAIL_OK;
}
else
{
    $response['erreur']   = true;
    $response['msg_info'] = TEXT_AC_ERR_MAIL;
}
 
echo json_encode($response);
exit;