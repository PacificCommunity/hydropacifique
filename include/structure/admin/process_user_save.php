<?php
/*
----------------------------------------
Copyright (c) 2025 - Vai-Natura
----------------------------------------
File    : process_user_save.php
Purpose : User save processor — called via XHR POST from modif_user.php.
          Validates the submitted form, then inserts a new user or updates
          an existing one together with their access rights (TABLE_USER_ACCES).
          Logs every write to TABLE_ACTIONS (type_action = 31).

Response (JSON):
  {
      "erreur"   : bool,
      "msg_info" : string,
      "id"   : int|null   -- only set on successful creation
  }

Security:
  - CSRF token verified against $_SESSION before any processing.
  - All ids cast to (int).
  - All string values passed through post_secure() before SQL insertion.
  - $info_action escaped via mysqli_real_escape_string() before insertion.
  - New user id retrieved via mysqli_insert_id() — safe under concurrent load.
  - The lang field is validated against $languages_array (defined in
    config.php) before being persisted.
----------------------------------------
*/

require('../../config.php');
require('../../database_tables.php');

require('../../function/date.php');
require('../../function/gestion_erreur.php');
require('../../function/database.php');
require('../../function/html_output.php');
require('../../function/general.php');

// Load translation strings for the active language
require('../../text_content_' . LANGUAGE . '.php');


header('Content-Type: application/json; charset=utf-8');

$sql_link = mysqli_connect(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD, DB_DATABASE)
    or die(json_encode(['erreur' => true, 'msg_info' => 'Cannot connect to the database.']));

mysqli_query($sql_link, 'SET NAMES UTF8');

$response = [
    'erreur'   => false,
    'msg_info' => '',
    'id'       => null,
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
// CSRF token verification

$csrf_token = $_SESSION['csrf_token'] ?? '';

if (empty($_POST['csrf_token']) || !hash_equals($csrf_token, $_POST['csrf_token']))
{
    $response['erreur']   = true;
    $response['msg_info'] = TEXT_AC_ERR_REQUEST;
    echo json_encode($response);
    exit;
}


// -----------------------------------------------
// Detect mode : edit (ref_id present) or new user

$modif  = false;
$ref_id = null;

if (isset($_POST['ref_id']) && tep_not_null($_POST['ref_id']))
{
    $ref_id = filter_var($_POST['ref_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

    if ($ref_id === false)
    {
        $response['erreur']   = true;
        $response['msg_info'] = TEXT_AC_ERR_REQUEST;
        echo json_encode($response);
        exit;
    }

    $modif = true;
}


// -----------------------------------------------
// Sanitise form input

$login      = post_secure($sql_link, trim($_POST['login']            ?? ''));
$nom        = post_secure($sql_link, trim($_POST['nom']              ?? ''));
$prenom     = post_secure($sql_link, trim($_POST['prenom']           ?? ''));
$email      = post_secure($sql_link, trim($_POST['email']            ?? ''));
$info_user  = post_secure($sql_link, trim($_POST['info_user']        ?? ''));
$id_service = post_secure($sql_link, trim($_POST['select_idService'] ?? ''));

// ---- Language ----
// Validate against $languages_array (defined in config.php — single source
// of truth for supported languages). Fall back to the platform language
// if the posted value is missing or unexpected.
$lang = post_secure($sql_link, trim($_POST['lang'] ?? ''));

if (!array_key_exists($lang, $languages_array))
{
    $lang = LANGUAGE_TERRITOIRE;
}

// Access rights — default to 0; elevated values set by checkbox presence
$gestion_data_u = 0;
$parametre_u    = 0;
$config_u       = 0;

if (isset($_POST['gestion_data_expert'])) { $gestion_data_u = 2; }
elseif (isset($_POST['gestion_data']))    { $gestion_data_u = 1; }
if (isset($_POST['parametre']))           { $parametre_u    = 1; }
if (isset($_POST['config']))              { $config_u       = 1; }


// -----------------------------------------------
// Validation

$errors = [];

// ---- Login ----
if (!tep_not_null($login))
{
    $errors[] = TEXT_US_CTRL_ERR_LOGIN_EMPTY;
}
else
{
    // Only letters, digits and dots are allowed
    if (preg_match('/([^.a-z0-9]+)/i', $login))
    {
        $errors[] = TEXT_US_CTRL_ERR_LOGIN_CHARS;
    }

    // Uniqueness check — on creation, the login must not exist at all;
    // on edit, it must not be taken by a different user
    if (empty($errors))
    {
        $log_query = tep_db_query(
            $sql_link,
            "SELECT id FROM " . TABLE_USER . "
             WHERE login = '" . $login . "'
             LIMIT 1"
        );
        $log = tep_db_fetch_array($log_query);

        if ($log && tep_not_null($log['id']) && (!$modif || (int) $log['id'] !== (int) $ref_id))
        {
            $errors[] = TEXT_US_CTRL_ERR_LOGIN_DUP;
        }
    }
}

// ---- Email ----
if (!tep_not_null($email))
{
    $errors[] = TEXT_US_CTRL_ERR_MAIL_EMPTY;
}
elseif (!filter_var($email, FILTER_VALIDATE_EMAIL))
{
    $errors[] = TEXT_US_CTRL_ERR_MAIL_INVALID;
}


// -----------------------------------------------
// Early exit on validation error

if (!empty($errors))
{
    $response['erreur']   = true;
    $response['msg_info'] = implode('<br>', $errors);
    echo json_encode($response);
    exit;
}


// -----------------------------------------------
// Database write
// Use DateTime with the application timezone — consistent with the rest of the project

// Timezone transmitted by the calling page via formData
$timezone_php = $_POST['timezone'] ?? 'Pacific/Tahiti';

// Validate against PHP's known timezone list to prevent any invalid value
if (!in_array($timezone_php, timezone_identifiers_list(), true))
{
    $timezone_php = 'Pacific/Tahiti';
}

$now      = new DateTime('now', new DateTimeZone($timezone_php));
$today_us = $now->format('Y-m-d H:i:s');

if (!$modif)
{
    // -----------------------------------------------
    // Insert new user

    tep_db_query(
        $sql_link,
        "INSERT INTO " . TABLE_USER . " (id_service, login, nom, prenom, email, info, lang, date_creation, active)
         VALUES (
             '" . $id_service . "', 
             '" . $login      . "',
             '" . $nom        . "',
             '" . $prenom     . "',
             '" . $email      . "',
             '" . $info_user  . "',
             '" . $lang       . "',
             '" . $today_us   . "',
             1
         )"
    );

    // Use mysqli_insert_id() — safe under concurrent load, unlike SELECT MAX(id)
    $ref_id = (int) mysqli_insert_id($sql_link);

    tep_db_query(
        $sql_link,
        "INSERT INTO " . TABLE_USER_ACCES . " (id, gestion_data, parametre, config)
         VALUES (
             "  . $ref_id         . ",
             '" . $gestion_data_u . "',
             '" . $parametre_u    . "',
             '" . $config_u       . "'
         )"
    );

    $message_action  = TEXT_US_CTRL_MSG_CREATED;
    $response['id']  = (int) $ref_id;
    $info_action     = mysqli_real_escape_string(
        $sql_link,
        "New user created: " . $login . " (" . $prenom . " " . $nom . ") - " . $info_user
    );
}
else
{
    // -----------------------------------------------
    // Update existing user

    tep_db_query(
        $sql_link,
        "UPDATE " . TABLE_USER . "
         SET id_service  = '" . $id_service . "',
             login       = '" . $login      . "',
             nom         = '" . $nom        . "',
             prenom      = '" . $prenom     . "',
             email       = '" . $email      . "',
             info        = '" . $info_user  . "',
             lang        = '" . $lang       . "',
             date_modif  = '" . $today_us   . "'
         WHERE id = " . (int) $ref_id
    );

    tep_db_query(
        $sql_link,
        "UPDATE " . TABLE_USER_ACCES . "
         SET gestion_data = '" . $gestion_data_u . "',
             parametre    = '" . $parametre_u    . "',
             config       = '" . $config_u       . "'
         WHERE id = " . (int) $ref_id
    );

    $message_action = TEXT_US_CTRL_MSG_UPDATED;
    $info_action    = mysqli_real_escape_string(
        $sql_link,
        "User info updated: " . $login . " (" . $prenom . " " . $nom . ") - " . $info_user
    );
}


// -----------------------------------------------
// Log the action
// id_user = the admin performing the action (set by application_top / session),
// not the id of the user being edited.

tep_db_query(
    $sql_link,
    "INSERT INTO " . TABLE_ACTIONS . " (id_user, type_action, info, dateheure)
     VALUES (
         "   . (int) ($id_user ?? 0) . ",
         31,
         '"  . $info_action . "',
         '"  . $today_us    . "'
     )"
);


// -----------------------------------------------
// Success response

$response['msg_info'] = $message_action;
echo json_encode($response);
exit;