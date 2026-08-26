<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
AJAX endpoint — service bulk save
Called by the save button in gestion_service.php.
Receives the full management form via multipart POST.

Operations (all wrapped in a single transaction, strict mode):
  1. Update all existing services (TABLE_SERVICE). For each row, two
     uniqueness checks are performed against the OTHER rows:
       - name must be unique
       - contact_mail must be unique
     The email is also validated for format (only if non-empty).
     If any check fails for a given row, only that row is skipped; the
     loop continues so every row gets its own error feedback.
  2. Insert a new service if new_sv_name is not empty — same uniqueness
     and validation rules applied.
  3. Log the action to TABLE_ACTIONS — only when every write succeeded.

Error handling:
  - Any validation failure sets $erreur = true and accumulates a message.
  - A per-iteration $erreur_ligne flag is used so one bad row does NOT
    prevent the others from being processed.
  - At the end of the try block, if $erreur is true, the transaction is
    rolled back; otherwise it is committed and the action is logged. The
    action log is therefore only written for fully successful saves.
  - mysqli is set to throw exceptions, so any SQL error triggers ROLLBACK
    via the catch block.

Security:
  - All numeric IDs are cast to int before being injected into SQL.
  - All string inputs are sanitised via post_secure().
  - Email format is verified with filter_var(..., FILTER_VALIDATE_EMAIL).
  - Checkbox inputs are evaluated with isset() (unchecked boxes are not posted).

Returns JSON:
  erreur   : bool   — true if any validation error occurred or a DB exception was thrown
  msg_info : string — feedback message(s) for the user
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

// Response is JSON, not HTML
header('Content-Type: application/json; charset=utf-8');

// Make mysqli throw exceptions on errors so the catch block actually catches SQL failures
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$sql_link = mysqli_connect(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD, DB_DATABASE)
    or die('Cannot connect to the database');
mysqli_query($sql_link, 'SET NAMES utf8mb4');


// -----------------------------------------------
// Initialise output variables

$msg_info_send = '';
$erreur        = false;


// -----------------------------------------------
// Process the POST request

if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
    // Numeric IDs must be cast, never concatenated as raw strings
    $id_user_agent = (int) ($_POST['id_user_agent'] ?? 0);
    $territoire_id = (int) ($_POST['territoire_id'] ?? 0);

    // Wrap all writes in a transaction — committed only if no error occurs
    tep_db_query($sql_link, "START TRANSACTION");

    try
    {
        // ---- 1. Update existing services ----

        $service_query = tep_db_query($sql_link,
            "SELECT id_service FROM " . TABLE_SERVICE);

        while ($serviceTab = tep_db_fetch_array($service_query))
        {
            $id_service = (int) $serviceTab['id_service'];

            // Row not present in POST (hidden or skipped) — leave it alone
            if (!isset($_POST['sv_name_' . $id_service])) { continue; }

            $name         = post_secure($sql_link, $_POST['sv_name_'        . $id_service]);
            $desc         = post_secure($sql_link, $_POST['sv_desc_'        . $id_service]);
            $contact      = post_secure($sql_link, $_POST['sv_contact_'     . $id_service]);
            $contact_mail = post_secure($sql_link, $_POST['sv_contactmail_' . $id_service]);
            // Checkbox: absent from POST when unchecked
            $local        = isset($_POST['sv_local_' . $id_service]) ? 1 : 0;

            // Per-row error flag — lets the loop continue processing other rows
            // even when the current one has a problem.
            $erreur_ligne = false;

            // ---- 1a. Duplicate-name guard: same name on a DIFFERENT row ----
            $verif_name_query = tep_db_query($sql_link,
                "SELECT EXISTS (
                    SELECT 1 FROM " . TABLE_SERVICE . "
                    WHERE name = '" . $name . "'
                      AND id_service != " . $id_service . "
                    LIMIT 1
                 ) AS name_exists");
            $verif_name = tep_db_fetch_array($verif_name_query);

            if ($verif_name['name_exists'] == 1)
            {
                $erreur        = true;
                $erreur_ligne  = true;
                $msg_info_send .= TEXT_SV_SAVE_ERR_DUP_NAME . " : " . $name . "<br>";
            }

            // ---- 1b. Duplicate-email guard: same email on a DIFFERENT row ----
            // Only check if an email was actually provided.
            if ($contact_mail !== '')
            {
                $verif_mail_query = tep_db_query($sql_link,
                    "SELECT EXISTS (
                        SELECT 1 FROM " . TABLE_SERVICE . "
                        WHERE contact_mail = '" . $contact_mail . "'
                          AND id_service  != " . $id_service . "
                        LIMIT 1
                     ) AS mail_exists");
                $verif_mail = tep_db_fetch_array($verif_mail_query);

                if ($verif_mail['mail_exists'] == 1)
                {
                    $erreur        = true;
                    $erreur_ligne  = true;
                    $msg_info_send .= TEXT_SV_SAVE_ERR_DUP_MAIL . " : " . $contact_mail . "<br>";
                }

                // ---- 1c. Email format validation ----
                // Note the leading "!" — filter_var returns the email when valid,
                // and false when invalid, so we flag an error on "not valid".
                if (!filter_var($contact_mail, FILTER_VALIDATE_EMAIL))
                {
                    $erreur        = true;
                    $erreur_ligne  = true;
                    $msg_info_send .= TEXT_SV_SAVE_ERR_MAIL . " : " . $contact_mail . "<br>";
                }
            }

            // ---- 1d. Apply the update only if the row passed every check ----
            if (!$erreur_ligne)
            {
                tep_db_query($sql_link,
                    "UPDATE " . TABLE_SERVICE . "
                     SET name         = '" . $name         . "',
                         description  = '" . $desc         . "',
                         local        = '" . $local        . "',
                         contact      = '" . $contact      . "',
                         contact_mail = '" . $contact_mail . "'
                     WHERE id_service = " . $id_service);
            }
        }


        // ---- 2. Insert new service (new-entry row) ----

        if (tep_not_null($_POST['new_sv_name'] ?? ''))
        {
            $new_name         = post_secure($sql_link, $_POST['new_sv_name']);
            $new_desc         = post_secure($sql_link, $_POST['new_sv_desc']);
            $new_contact      = post_secure($sql_link, $_POST['new_sv_contact']);
            $new_contact_mail = post_secure($sql_link, $_POST['new_sv_contactmail']);
            // Checkbox: absent from POST when unchecked
            $new_local        = isset($_POST['new_sv_local']) ? 1 : 0;

            // Per-row flag for the new entry — we still set $erreur globally
            // so the transaction rolls back, but we use $erreur_new locally
            // to decide whether to run the INSERT.
            $erreur_new = false;

            // ---- 2a. Duplicate-name guard ----
            // No id exclusion — this is a brand new row, so any match is a duplicate.
            $verif_name_query = tep_db_query($sql_link,
                "SELECT EXISTS (
                    SELECT 1 FROM " . TABLE_SERVICE . "
                    WHERE name = '" . $new_name . "'
                    LIMIT 1
                 ) AS name_exists");
            $verif_name = tep_db_fetch_array($verif_name_query);

            if ($verif_name['name_exists'] == 1)
            {
                $erreur        = true;
                $erreur_new    = true;
                $msg_info_send .= TEXT_SV_SAVE_ERR_DUP_NAME . " : " . $new_name . "<br>";
            }

            // ---- 2b. Duplicate-email guard ----
            if ($new_contact_mail !== '')
            {
                $verif_mail_query = tep_db_query($sql_link,
                    "SELECT EXISTS (
                        SELECT 1 FROM " . TABLE_SERVICE . "
                        WHERE contact_mail = '" . $new_contact_mail . "'
                        LIMIT 1
                     ) AS mail_exists");
                $verif_mail = tep_db_fetch_array($verif_mail_query);

                if ($verif_mail['mail_exists'] == 1)
                {
                    $erreur        = true;
                    $erreur_new    = true;
                    $msg_info_send .= TEXT_SV_SAVE_ERR_DUP_MAIL . " : " . $new_contact_mail . "<br>";
                }

                // ---- 2c. Email format validation ----
                if (!filter_var($new_contact_mail, FILTER_VALIDATE_EMAIL))
                {
                    $erreur        = true;
                    $erreur_new    = true;
                    $msg_info_send .= TEXT_SV_SAVE_ERR_MAIL . " : " . $new_contact_mail . "<br>";
                }
            }

            // ---- 2d. Apply the insert only if every check passed ----
            if (!$erreur_new)
            {
                tep_db_query($sql_link,
                    "INSERT INTO " . TABLE_SERVICE . "
                        (name, description, local, contact, contact_mail)
                     VALUES
                        ('" . $new_name         . "', '" . $new_desc . "',
                         '" . $new_local        . "', '" . $new_contact . "',
                         '" . $new_contact_mail . "')");
            }
        }


        // ---- 3. Commit or rollback based on overall success ----

        if ($erreur)
        {
            // At least one validation error — discard every write and report only the errors.
            tep_db_query($sql_link, "ROLLBACK");
        }
        else
        {
            // All writes succeeded — log the action and commit.
            $today_us    = date('Y-m-d H:i:s');
            $type_action = 13; // Platform settings action type

            tep_db_query($sql_link,
                "INSERT INTO " . TABLE_ACTIONS . " (id_user, type_action, info, dateheure)
                 VALUES (" . $id_user_agent . ", '" . $type_action . "', '" . TEXT_SV_SAVE_ACTION_LOG . "', '" . $today_us . "')");

            tep_db_query($sql_link, "COMMIT");

            $msg_info_send = TEXT_SV_SAVE_OK;
        }
    }
    catch (Exception $e)
    {
        // Unexpected SQL or runtime exception — roll back everything
        tep_db_query($sql_link, "ROLLBACK");

        $msg_info_send  = TEXT_SV_SAVE_ERR_WRITE . "<br>";
        $msg_info_send .= TEXT_SV_SAVE_ERR_DETAIL . $e->getMessage();
        $erreur         = true;
    }
}
else
{
    // Request method is not POST — should not happen in normal use
    $msg_info_send = "<span style='font-size:16px;'>" . TEXT_SV_SAVE_ERR_REQUEST . "</span><br><br>";
    $erreur        = true;
}


// Return JSON response to the client
echo json_encode([
    'erreur'   => $erreur,
    'msg_info' => $msg_info_send,
]);
?>