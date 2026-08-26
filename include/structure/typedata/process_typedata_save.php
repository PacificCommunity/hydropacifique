<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
AJAX endpoint — time-series type and axis bulk save
Called by the save button in the parent page (gestion_typedata.php or equivalent).
Receives the full management form via multipart POST.

Operations (all wrapped in a single transaction, strict mode):
  1. Update all existing time-series types (TABLE_TYPE_DATA) — duplicate-acronym
     check excludes the current row itself so unchanged rows also get validated.
  2. Insert a new time-series type if chron_init_0 is not empty.
  3. Update all existing axes (TABLE_DATA_TYPE_AXE) — same duplicate-name guard.
  4. Insert a new axis if axe_nom_0 is not empty.
  5. Log the action to TABLE_ACTIONS — only when every write succeeded.

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
        // ---- 1. Update existing time-series types ----

        $typedata_query = tep_db_query($sql_link,
            "SELECT id_data_type, init_type_data FROM " . TABLE_TYPE_DATA);

        while ($typedata = tep_db_fetch_array($typedata_query))
        {
            $id_chron = (int) $typedata['id_data_type'];

            // Row not present in POST (hidden or skipped) — leave it alone
            if (!isset($_POST['chron_init_' . $id_chron])) { continue; }

            $chron_init      = post_secure($sql_link, $_POST['chron_init_'                . $id_chron]);
            $chron_nom       = post_secure($sql_link, $_POST['chron_nom_'                 . $id_chron]);
            $chron_type      = post_secure($sql_link, $_POST['chron_select_type_'         . $id_chron]);
            $chron_axe       = post_secure($sql_link, $_POST['chron_select_axe_'          . $id_chron]);
            $chron_unite     = post_secure($sql_link, $_POST['chron_unite_'               . $id_chron]);
            $chron_round     = post_secure($sql_link, $_POST['chron_nb_round_'            . $id_chron]);
            $chron_timescale = post_secure($sql_link, $_POST['chron_select_timescale_'    . $id_chron]);
            $chron_trait     = post_secure($sql_link, $_POST['chron_select_traitement_'   . $id_chron]);
            $chron_typegraph = post_secure($sql_link, $_POST['chron_select_typegraph_'    . $id_chron]);

            // Raw-data flag: an unchecked checkbox sends nothing, so a
            // missing field means 0. Cast to a strict 0/1 integer.
            $chron_raw_data  = isset($_POST['chron_raw_data_' . $id_chron]) ? 1 : 0;

            // (Period transformation / Chronicle transformation fields
            // are no longer rendered in the management UI, so we don't
            // read them here. The corresponding BDD columns
            // 'to_periode' and 'id_chon_periode' are left untouched
            // by the UPDATE below — their existing values are
            // preserved for any legacy consumer that may still read
            // them.)

            // Duplicate-acronym guard: look for the same label on a DIFFERENT row.
            // Excluding the current row makes the check correct whether the acronym
            // was changed or not.
            $verif_query = tep_db_query($sql_link,
                "SELECT EXISTS (
                    SELECT 1 FROM " . TABLE_TYPE_DATA . "
                    WHERE init_type_data = '" . $chron_init . "'
                      AND id_data_type  != " . $id_chron . "
                    LIMIT 1
                 ) AS typedata_exists");
            $verif = tep_db_fetch_array($verif_query);

            if ($verif['typedata_exists'] == 1)
            {
                // Acronym already used by another row — block this update
                $erreur        = true;
                $msg_info_send .= TEXT_TD_SAVE_ERR_DUP_CHRON . "'{$chron_init}'.<br>";
            }
            else
            {
                tep_db_query($sql_link,
                    "UPDATE " . TABLE_TYPE_DATA . "
                     SET init_type_data   = '" . $chron_init      . "',
                         nom_type_data    = '" . $chron_nom       . "',
                         id_eq_type_data  = '" . $chron_type      . "',
                         axe_data         = '" . $chron_axe       . "',
                         unite            = '" . $chron_unite     . "',
                         nb_round         = '" . $chron_round     . "',
                         time_scale       = '" . $chron_timescale . "',
                         traitement       = '" . $chron_trait     . "',
                         type_graph       = '" . $chron_typegraph . "',
                         raw_data         = " . $chron_raw_data . "
                     WHERE id_data_type = " . $id_chron);
            }
        }


        // ---- 2. Insert new time-series type (new-entry row, id = 0) ----

        if (tep_not_null($_POST['chron_init_0'] ?? ''))
        {
            $chron_init_0      = post_secure($sql_link, $_POST['chron_init_0']);
            $chron_nom_0       = post_secure($sql_link, $_POST['chron_nom_0']);
            $chron_type_0      = post_secure($sql_link, $_POST['chron_select_type_mesure_0']);
            $chron_axe_0       = post_secure($sql_link, $_POST['chron_select_axe_0']);
            $chron_unite_0     = post_secure($sql_link, $_POST['chron_unite_0']);
            $chron_round_0     = post_secure($sql_link, $_POST['chron_nb_round_0']);
            $chron_timescale_0 = post_secure($sql_link, $_POST['chron_select_timescale_0']);
            $chron_trait_0     = post_secure($sql_link, $_POST['chron_select_traitement_0']);
            $chron_typegraph_0 = post_secure($sql_link, $_POST['chron_select_typegraph_0']);

            // Raw-data flag for the new row (unchecked = 0)
            $chron_raw_data_0  = isset($_POST['chron_raw_data_0']) ? 1 : 0;

            // (to_periode / id_chon_periode are not collected anymore —
            // the BDD columns default to 0 on INSERT.)

            // No id exclusion needed here — this is a brand new row
            $verif_query = tep_db_query($sql_link,
                "SELECT EXISTS (
                    SELECT 1 FROM " . TABLE_TYPE_DATA . "
                    WHERE init_type_data = '" . $chron_init_0 . "'
                    LIMIT 1
                 ) AS typedata_exists");
            $verif = tep_db_fetch_array($verif_query);

            if ($verif['typedata_exists'] == 1)
            {
                $erreur        = true;
                $msg_info_send .= TEXT_TD_SAVE_ERR_DUP_CHRON . "'{$chron_init_0}'.<br><br>";
            }
            else
            {
                tep_db_query($sql_link,
                    "INSERT INTO " . TABLE_TYPE_DATA . "
                        (init_type_data, nom_type_data, id_eq_type_data, axe_data,
                         unite, nb_round, time_scale, traitement, type_graph, raw_data)
                     VALUES
                        ('" . $chron_init_0      . "', '" . $chron_nom_0       . "',
                         '" . $chron_type_0      . "', '" . $chron_axe_0       . "',
                         '" . $chron_unite_0     . "', '" . $chron_round_0     . "',
                         '" . $chron_timescale_0 . "', '" . $chron_trait_0     . "',
                         '" . $chron_typegraph_0 . "', " . $chron_raw_data_0 . ")");
            }
        }


        // ---- 3. Update existing axes ----

        $data_typeaxe_query = tep_db_query($sql_link,
            "SELECT id, axe, unite, nb_round FROM " . TABLE_DATA_TYPE_AXE);

        while ($datatype_axe = tep_db_fetch_array($data_typeaxe_query))
        {
            $id_axe = (int) $datatype_axe['id'];

            // Row not present in POST — leave it alone
            if (!isset($_POST['axe_nom_' . $id_axe])) { continue; }

            $axe_nom   = post_secure($sql_link, $_POST['axe_nom_'      . $id_axe]);
            $axe_unite = post_secure($sql_link, $_POST['axe_unite_'    . $id_axe]);
            $axe_round = post_secure($sql_link, $_POST['axe_nb_round_' . $id_axe]);

            // Duplicate-name guard: same label on a different row
            $verif_axe_query = tep_db_query($sql_link,
                "SELECT EXISTS (
                    SELECT 1 FROM " . TABLE_DATA_TYPE_AXE . "
                    WHERE axe = '" . $axe_nom . "'
                      AND id != " . $id_axe . "
                    LIMIT 1
                 ) AS axe_exists");
            $verif_axe = tep_db_fetch_array($verif_axe_query);

            if ($verif_axe['axe_exists'] == 1)
            {
                $erreur        = true;
                $msg_info_send .= TEXT_TD_SAVE_ERR_DUP_AXE . "'{$axe_nom}'.<br>";
            }
            else
            {
                tep_db_query($sql_link,
                    "UPDATE " . TABLE_DATA_TYPE_AXE . "
                     SET axe      = '" . $axe_nom   . "',
                         unite    = '" . $axe_unite . "',
                         nb_round = '" . $axe_round . "'
                     WHERE id = " . $id_axe);
            }
        }


        // ---- 4. Insert new axis (new-entry row, id = 0) ----

        if (tep_not_null($_POST['axe_nom_0'] ?? ''))
        {
            $axe_nom_0   = post_secure($sql_link, $_POST['axe_nom_0']);
            $axe_unite_0 = post_secure($sql_link, $_POST['axe_unite_0']);
            $axe_round_0 = post_secure($sql_link, $_POST['axe_nb_round_0']);

            $verif_axe_query = tep_db_query($sql_link,
                "SELECT EXISTS (
                    SELECT 1 FROM " . TABLE_DATA_TYPE_AXE . "
                    WHERE axe = '" . $axe_nom_0 . "'
                    LIMIT 1
                 ) AS axe_exists");
            $verif_axe = tep_db_fetch_array($verif_axe_query);

            if ($verif_axe['axe_exists'] == 1)
            {
                $erreur        = true;
                $msg_info_send .= TEXT_TD_SAVE_ERR_DUP_AXE . "'{$axe_nom_0}'.<br>";
            }
            else
            {
                tep_db_query($sql_link,
                    "INSERT INTO " . TABLE_DATA_TYPE_AXE . " (axe, unite, nb_round)
                     VALUES ('" . $axe_nom_0 . "', '" . $axe_unite_0 . "', '" . $axe_round_0 . "')");
            }
        }


        // ---- 5. Commit or rollback based on overall success ----

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