<?php
/*
----------------------------------------
Copyright (c) 2025 - Vai-Natura
----------------------------------------
Account / password reset — step 2: verification code check
Checks token validity, code expiry (15-minute window), and the
submitted 6-digit code. On success, issues a new token and clears the
code, allowing the client to proceed to password entry. Returns JSON.
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

    // Expiry threshold: 15 minutes ago
    $date_limite = new DateTime();
    $date_limite->modify('-15 minutes');
    $date_limite_formatted = $date_limite->format('Y-m-d H:i:s');

    $id_login     = isset($_POST['loginId'])      ? (int)$_POST['loginId']             : '';
    $token        = isset($_POST['token'])         ? trim($_POST['token'])              : '';
    $code_confirm = isset($_POST['codeConfirm'])   ? trim($_POST['codeConfirm'])        : '';

    // Load current user state
    $validUser_query = tep_db_query($sql_link,
        "SELECT token, date_code_verif, code_verif_account FROM " . TABLE_USER
        . " WHERE id=" . $id_login . " LIMIT 1");
    $validUser = tep_db_fetch_array($validUser_query);

    if (!isset($validUser))
    {
        $response['errors']['user'] = TEXT_AC_VALID_ERR_USER;
    }
    else
    {
        if ($token !== $validUser['token'] || empty($validUser['token']))
        {
            $response['errors']['token'] = TEXT_AC_VALID_ERR_TOKEN;
        }
        if ($validUser['date_code_verif'] < $date_limite_formatted)
        {
            $response['errors']['date'] = TEXT_AC_VALID_ERR_DATE;
        }
        if ($code_confirm !== $validUser['code_verif_account'] || empty($validUser['code_verif_account']))
        {
            $response['errors']['code'] = TEXT_AC_VALID_ERR_CODE;
        }
    }

    if (!empty($response['errors']))
    {
        $response['success'] = false;
        $response['message'] = TEXT_AC_ERR_VALIDATION;
    }
    else
    {
        // Issue a fresh token and clear the one-time code
        $new_token       = bin2hex(random_bytes(16));
        $clean_new_token = mysqli_real_escape_string($sql_link, $new_token);

        tep_db_query($sql_link,
            "UPDATE " . TABLE_USER
            . " SET code_verif_account='', token='" . $clean_new_token . "'"
            . " WHERE id=" . $id_login);

        $response['success'] = true;
        $response['token']   = $clean_new_token;
        $response['message'] = TEXT_AC_VALID_MSG_OK;
        $response['message'] .= "<br>";
        $response['message'] .= TEXT_AC_VALID_MSG_REDIRECT;
    }
}
else
{
    $response['message'] = TEXT_AC_ERR_FORM;
}

echo json_encode($response);
exit;
?>
