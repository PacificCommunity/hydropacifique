<?php
/*
----------------------------------------
Copyright (c) 2025 - Vai-Natura
----------------------------------------
File    : process_my_account_save.php
Purpose : Personal-account save processor — called via XHR POST from
          block_my_account.php (the popup opened from the top-header
          user block).

Key difference from process_user_save.php:
  - Target user id is ALWAYS the signed-in user (taken from session),
    NEVER from POST. A malicious payload trying to slip a different
    ref_id, email, login or id_service is silently dropped.
  - Login, email and id_service are intentionally not editable here.
    Even if posted, they are ignored.
  - Access rights (gestion_data / parametre / config) are not touched —
    those belong to admins only.

Response (JSON):
  {
      "erreur"       : bool,
      "msg_info"     : string,
      "lang_changed" : bool   -- triggers a reload client-side so
                                 translations refresh everywhere
  }

Security:
  - CSRF token verified against $_SESSION before any processing.
  - $id_user (signed-in user) is taken from application_top, never from POST.
  - All string values passed through post_secure().
  - The lang field is validated against $languages_array before persisting.
----------------------------------------
*/

require('../../config.php');
require('../../database_tables.php');

require('../../function/date.php');
require('../../function/gestion_erreur.php');
require('../../function/database.php');
require('../../function/html_output.php');
require('../../function/general.php');

// Session helpers — needed to identify the signed-in user without
// going through application_top.php (which would redirect via
// noaccess.html and break our JSON response).
require('../../function/sessions.php');

// Load translation strings for the active language
require('../../text_content_' . LANGUAGE . '.php');


header('Content-Type: application/json; charset=utf-8');

$sql_link = mysqli_connect(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD, DB_DATABASE)
    or die(json_encode(['erreur' => true, 'msg_info' => 'Cannot connect to the database.']));

mysqli_query($sql_link, 'SET NAMES UTF8');


// Start the session if it isn't already (same defensive pattern as
// application_top.php). Required before reading any $_SESSION value
// or calling the session helpers below.
if (session_status() === PHP_SESSION_NONE)
{
    session_start();
}


header('Content-Type: application/json; charset=utf-8');

$sql_link = mysqli_connect(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD, DB_DATABASE)
    or die(json_encode(['erreur' => true, 'msg_info' => 'Cannot connect to the database.']));

mysqli_query($sql_link, 'SET NAMES UTF8');

$response = [
    'erreur'       => false,
    'msg_info'     => '',
    'lang_changed' => false,
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
// CSRF token verification — same scheme as process_user_save.php

$csrf_token = $_SESSION['csrf_token'] ?? '';

if (empty($_POST['csrf_token']) || !hash_equals($csrf_token, $_POST['csrf_token']))
{
    $response['erreur']   = true;
    $response['msg_info'] = TEXT_AC_ERR_REQUEST;
    echo json_encode($response);
    exit;
}


// -----------------------------------------------
// Identify the signed-in user.
//
// Defence-in-depth strategy:
//   1. suiviSession() validates the session fingerprint against the
//      stored one (anti-hijack). If it fails, no $id_user comes back.
//   2. getAdminInfo() reads the user id BOUND TO THE SESSION — this
//      is the only authoritative source. NEVER trust POST for the
//      target user id.
//   3. The hidden id_user_agent posted from the form is compared with
//      the session value as a sanity check. A mismatch means either
//      the form was tampered with, or the user signed out and back in
//      with a different account while the page was still open. In
//      both cases we refuse the write.
//
// CSRF token (checked just above) already binds the request to the
// session — this triple check is belt-and-braces.

if (!suiviSession($sql_link))
{
    $response['erreur']   = true;
    $response['msg_info'] = TEXT_MYACC_ERR_NO_SESSION;
    echo json_encode($response);
    exit;
}

$session_info = getAdminInfo($sql_link);
$id_user      = isset($session_info['admin_id']) ? (int) $session_info['admin_id'] : 0;

if ($id_user <= 0)
{
    $response['erreur']   = true;
    $response['msg_info'] = TEXT_MYACC_ERR_NO_SESSION;
    echo json_encode($response);
    exit;
}

// Cross-check the hidden form field. The posted id_user_agent MUST
// match the session-resolved id. If it doesn't, the form was either
// tampered with or the session changed under the user's feet.
$posted_id = isset($_POST['id_user_agent']) ? (int) $_POST['id_user_agent'] : 0;

if ($posted_id !== $id_user)
{
    $response['erreur']   = true;
    $response['msg_info'] = TEXT_MYACC_ERR_NO_SESSION;
    echo json_encode($response);
    exit;
}

$target_id = $id_user;


// -----------------------------------------------
// Fetch the current row — we need the existing language to detect a
// change and the existing email/login for the action log message.

$current = tep_db_fetch_array(tep_db_query(
    $sql_link,
    "SELECT id, login, email, lang FROM " . TABLE_USER
    . " WHERE id = " . $target_id . " LIMIT 1"
));

if (!$current)
{
    $response['erreur']   = true;
    $response['msg_info'] = TEXT_AC_ERR_REQUEST;
    echo json_encode($response);
    exit;
}


// -----------------------------------------------
// Sanitise editable fields. Read-only fields (login/email/id_service)
// are NOT read from POST — even if a malicious client sends them, they
// won't reach the UPDATE statement.

$nom       = post_secure($sql_link, trim($_POST['nom']       ?? ''));
$prenom    = post_secure($sql_link, trim($_POST['prenom']    ?? ''));
$info_user = post_secure($sql_link, trim($_POST['info_user'] ?? ''));

// ---- Language validation ----
// Fall back to the platform language if the posted value is not in the
// supported set — same defensive pattern as in process_user_save.php.
$lang = post_secure($sql_link, trim($_POST['lang'] ?? ''));
if (!array_key_exists($lang, $languages_array))
{
    $lang = LANGUAGE_TERRITOIRE;
}

$lang_changed = ($lang !== ($current['lang'] ?? ''));


// -----------------------------------------------
// Database write

// Timezone — same defensive validation as process_user_save.php
$timezone_php = $_POST['timezone'] ?? 'Pacific/Tahiti';
if (!in_array($timezone_php, timezone_identifiers_list(), true))
{
    $timezone_php = 'Pacific/Tahiti';
}

$now      = new DateTime('now', new DateTimeZone($timezone_php));
$today_us = $now->format('Y-m-d H:i:s');

tep_db_query(
    $sql_link,
    "UPDATE " . TABLE_USER . "
     SET nom        = '" . $nom        . "',
         prenom     = '" . $prenom     . "',
         info       = '" . $info_user  . "',
         lang       = '" . $lang       . "',
         date_modif = '" . $today_us   . "'
     WHERE id = " . $target_id
);


// -----------------------------------------------
// Action log — same type_action code (31) as the admin save flow so the
// audit trail stays consistent. The message makes clear this was a
// self-service edit, not an admin-initiated one.

$info_action = mysqli_real_escape_string(
    $sql_link,
    "Self-service profile edit: "
    . ($current['login'] ?? '')
    . " (" . $prenom . " " . $nom . ") - " . $info_user
    . " - lang=" . $lang
);

tep_db_query(
    $sql_link,
    "INSERT INTO " . TABLE_ACTIONS . " (id_user, type_action, info, dateheure)
     VALUES (
         "  . $target_id   . ",
         31,
         '" . $info_action . "',
         '" . $today_us    . "'
     )"
);


// -----------------------------------------------
// Success response

$response['msg_info']     = TEXT_MYACC_SAVE_OK;
$response['lang_changed'] = $lang_changed;

echo json_encode($response);
exit;