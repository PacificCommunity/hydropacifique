<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
AJAX endpoint — measurement type bulk save
Called by the save button in gestion_type.php.
Receives the full management form via multipart POST.

Operations (all wrapped in a single transaction, strict mode):
  1. Update all existing measurement types (TABLE_EQ_TYPE) — duplicate-name
     check excludes the current row itself so unchanged rows also get validated.
  2. Insert a new measurement type if new_nom_eq_type is not empty.
  3. Log the action to TABLE_ACTIONS — only when every write succeeded.

Error handling:
  - Any duplicate conflict sets $erreur = true and accumulates a message.
  - At the end of the try block, if $erreur is true, the transaction is rolled
    back; otherwise it is committed and the action is logged. The action log
    is therefore only written for fully successful saves.
  - mysqli is set to throw exceptions, so any SQL error triggers ROLLBACK via
    the catch block.

Security:
  - All numeric IDs are cast to int before being injected into SQL.
  - All string inputs are sanitised via post_secure().
  - Checkbox inputs are evaluated with isset() (unchecked boxes are not posted).

Returns JSON:
  erreur   : bool   — true if any duplicate error occurred or a DB exception was thrown
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
        // ---- 1. Update existing measurement types ----

        $type_query = tep_db_query($sql_link,
            "SELECT id_eq_type, nom_eq_type FROM " . TABLE_EQ_TYPE);

        while ($typeTab = tep_db_fetch_array($type_query))
        {
            $id_type = (int) $typeTab['id_eq_type'];

            // Row not present in POST (hidden or skipped) — leave it alone
            if (!isset($_POST['nom_eq_type_' . $id_type])) { continue; }

            $nom          = post_secure($sql_link, $_POST['nom_eq_type_'           . $id_type]);
            $typemesure   = post_secure($sql_link, $_POST['select_typemesure_'     . $id_type]);
            $ordre        = post_secure($sql_link, $_POST['ordre_type_'            . $id_type]);
            $active       = isset($_POST['active_type_' . $id_type]) ? 1 : 0;
            $color_border = post_secure($sql_link, $_POST['type_color_border_'     . $id_type]);
            $color_bg     = post_secure($sql_link, $_POST['type_color_background_' . $id_type]);
            $typegraph    = post_secure($sql_link, $_POST['select_typegraph_'      . $id_type]);

            // Duplicate-name guard: look for the same label on a DIFFERENT row.
            // Excluding the current row makes the check correct whether the name
            // was changed or not.
            $verif_query = tep_db_query($sql_link,
                "SELECT EXISTS (
                    SELECT 1 FROM " . TABLE_EQ_TYPE . "
                    WHERE nom_eq_type = '" . $nom . "'
                      AND id_eq_type != " . $id_type . "
                    LIMIT 1
                 ) AS type_exists");
            $verif = tep_db_fetch_array($verif_query);

            if ($verif['type_exists'] == 1)
            {
                // Name already used by another row — block this update
                $erreur        = true;
                $msg_info_send .= TEXT_TD_SAVE_ERR_DUP_CHRON . " : " . $nom . "<br>";
            }
            else
            {
                tep_db_query($sql_link,
                    "UPDATE " . TABLE_EQ_TYPE . "
                     SET nom_eq_type           = '" . $nom          . "',
                         valeur_data_type      = '" . $typemesure   . "',
                         order_eq_type         = '" . $ordre        . "',
                         active_eq_type        = '" . $active       . "',
                         type_color_border     = '" . $color_border . "',
                         type_color_background = '" . $color_bg     . "',
                         type_graph            = '" . $typegraph    . "'
                     WHERE id_eq_type = " . $id_type);
            }
        }


        // ---- 2. Insert new measurement type (new-entry row) ----

        if (!$erreur && tep_not_null($_POST['new_nom_eq_type'] ?? ''))
        {
            $new_nom          = post_secure($sql_link, $_POST['new_nom_eq_type']);
            $new_typemesure   = post_secure($sql_link, $_POST['new_select_typemesure']);
            $new_ordre        = post_secure($sql_link, $_POST['new_ordre_type']);
            // Checkbox: absent from POST when unchecked
            $new_active       = isset($_POST['new_active_type']) ? 1 : 0;
            $new_color_border = post_secure($sql_link, $_POST['new_type_color_border']);
            $new_color_bg     = post_secure($sql_link, $_POST['new_type_color_background']);
            $new_typegraph    = post_secure($sql_link, $_POST['new_select_typegraph']);

            // No id exclusion needed here — this is a brand new row
            $verif_query = tep_db_query($sql_link,
                "SELECT EXISTS (
                    SELECT 1 FROM " . TABLE_EQ_TYPE . "
                    WHERE nom_eq_type = '" . $new_nom . "'
                    LIMIT 1
                 ) AS type_exists");
            $verif = tep_db_fetch_array($verif_query);

            if ($verif['type_exists'] == 1)
            {
                $erreur        = true;
                $msg_info_send .= TEXT_TD_SAVE_ERR_DUP_CHRON . " : " . $new_nom . "<br>";
            }
            else
            {
                tep_db_query($sql_link,
                    "INSERT INTO " . TABLE_EQ_TYPE . "
                        (nom_eq_type, valeur_data_type, order_eq_type, active_eq_type,
                         type_color_border, type_color_background, type_graph)
                     VALUES
                        ('" . $new_nom          . "', '" . $new_typemesure . "',
                         '" . $new_ordre        . "', '" . $new_active     . "',
                         '" . $new_color_border . "', '" . $new_color_bg   . "',
                         '" . $new_typegraph    . "')");
            }
        }


        // ---- 3. Commit or rollback based on overall success ----

        if ($erreur)
        {
            // At least one duplicate error — discard every write and report only the errors.
            tep_db_query($sql_link, "ROLLBACK");
        }
        else
        {
            // All writes succeeded — log the action and commit.
            $today_us    = date('Y-m-d H:i:s');
            $type_action = 13; // Platform settings action type

            tep_db_query($sql_link,
                "INSERT INTO " . TABLE_ACTIONS . " (id_user, type_action, info, dateheure)
                 VALUES (" . $id_user_agent . ", '" . $type_action . "', '" . TEXT_TD_SAVE_ACTION_LOG . "', '" . $today_us . "')");

            tep_db_query($sql_link, "COMMIT");

            $msg_info_send = TEXT_TD_SAVE_OK;
        }
    }
    catch (Exception $e)
    {
        // Unexpected SQL or runtime exception — roll back everything
        tep_db_query($sql_link, "ROLLBACK");

        $msg_info_send  = TEXT_TD_SAVE_ERR_WRITE . "<br>";
        $msg_info_send .= TEXT_TD_SAVE_ERR_DETAIL . $e->getMessage();
        $erreur         = true;
    }
}
else
{
    // Request method is not POST — should not happen in normal use
    $msg_info_send = "<span style='font-size:16px;'>" . TEXT_TD_SAVE_ERR_REQUEST . "</span><br><br>";
    $erreur        = true;
}


// Return JSON response to the client
echo json_encode([
    'erreur'   => $erreur,
    'msg_info' => $msg_info_send,
]);
?>