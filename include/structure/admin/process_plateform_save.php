<?php
/*
----------------------------------------
Copyright (c) 2025 - Vai-Natura
----------------------------------------
AJAX endpoint — plateform (territoire) save
Called by the save button in gestion_plateform.php.
Receives the full management form via multipart POST.
Operations (wrapped in a single transaction, strict mode):
  1. Validate the posted territoire settings (theme_region,
     region_default, service_hydro, timezone_php, lang, mapLong,
     mapLat, mapZoom, mapMinZoom).
  2. Update the unique territoire row identified by territoire_id.
     init_territoire and nom_territoire are read-only on the client
     and are NOT updated here even if posted.
  3. Log the action to TABLE_ACTIONS — only when the update succeeded.
Validation rules:
  - theme_region must not be empty.
  - service_hydro must not be empty.
  - region_default must reference an existing id_region belonging to
    the same territoire.
  - timezone_php must be a valid PHP timezone identifier
    (DateTimeZone::listIdentifiers()).
  - lang must be one of the supported codes ('fr', 'en').
  - mapLong must be a float in [-180, 180].
  - mapLat must be a float in [-90, 90].
  - mapZoom must be an integer in [2, 16].
  - mapMinZoom must be an integer in [2, 5] and <= mapZoom.
Error handling:
  - Any validation failure sets $erreur = true and accumulates a message.
  - At the end of the try block, if $erreur is true, the transaction is
    rolled back; otherwise it is committed and the action is logged.
  - mysqli is set to throw exceptions, so any SQL error triggers ROLLBACK
    via the catch block.
Security:
  - All numeric IDs are cast to int before being injected into SQL.
  - All string inputs are sanitised via post_secure().
  - Numeric values are cast/validated before use.
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

    // Sanitise string inputs
    $theme_region   = post_secure($sql_link, $_POST['theme_region']   ?? '');
    $service_hydro  = post_secure($sql_link, $_POST['service_hydro']  ?? '');
    $timezone_php   = post_secure($sql_link, $_POST['timezone_php']   ?? '');
    $lang           = post_secure($sql_link, $_POST['lang']           ?? '');

    // Numeric inputs — cast / validated below
    $region_default = (int) ($_POST['region_default'] ?? 0);
    $mapLong        = isset($_POST['mapLong']) ? (float) $_POST['mapLong'] : null;
    $mapLat         = isset($_POST['mapLat'])  ? (float) $_POST['mapLat']  : null;
    $mapZoom        = (int) ($_POST['mapZoom']    ?? 0);
    $mapMinZoom     = (int) ($_POST['mapMinZoom'] ?? 0);

    // Wrap all writes in a transaction — committed only if no error occurs
    tep_db_query($sql_link, "START TRANSACTION");

    try
    {
        // ---- 1a. Region theme is mandatory ----
        // Use trim() to reject whitespace-only strings, which post_secure
        // does not strip on its own.
        if (trim($theme_region) === '')
        {
            $erreur        = true;
            $msg_info_send .= TEXT_PF_SAVE_ERR_THEME . "<br>";
        }

        // ---- 1b. Hydro service is mandatory ----
        if (trim($service_hydro) === '')
        {
            $erreur        = true;
            $msg_info_send .= TEXT_PF_SAVE_ERR_SERVICE_HYDRO . "<br>";
        }

        // ---- 1c. Default region must belong to this territoire ----
        $verif_region_query = tep_db_query($sql_link,
            "SELECT EXISTS (
                SELECT 1 FROM " . TABLE_REGION . "
                WHERE id_region     = " . $region_default . "
                  AND id_territoire = " . $territoire_id . "
                LIMIT 1
             ) AS region_exists");
        $verif_region = tep_db_fetch_array($verif_region_query);

        if ($verif_region['region_exists'] != 1)
        {
            $erreur        = true;
            $msg_info_send .= TEXT_PF_SAVE_ERR_REGION . "<br>";
        }

        // ---- 1d. Timezone must be a valid PHP identifier ----
        if (!in_array($timezone_php, DateTimeZone::listIdentifiers(), true))
        {
            $erreur        = true;
            $msg_info_send .= TEXT_PF_SAVE_ERR_TIMEZONE . " : " . $timezone_php . "<br>";
        }

        // ---- 1e. Language must be one of the supported codes ----
        $allowed_langs = array('fr', 'en');
        if (!in_array($lang, $allowed_langs, true))
        {
            $erreur        = true;
            $msg_info_send .= TEXT_PF_SAVE_ERR_LANG . " : " . $lang . "<br>";
        }

        // ---- 1f. Longitude must be in [-180, 180] ----
        if ($mapLong === null || $mapLong < -180 || $mapLong > 180)
        {
            $erreur        = true;
            $msg_info_send .= TEXT_PF_SAVE_ERR_LONG . "<br>";
        }

        // ---- 1g. Latitude must be in [-90, 90] ----
        if ($mapLat === null || $mapLat < -90 || $mapLat > 90)
        {
            $erreur        = true;
            $msg_info_send .= TEXT_PF_SAVE_ERR_LAT . "<br>";
        }

        // ---- 1h. Default zoom must be in [2, 16] ----
        if ($mapZoom < 2 || $mapZoom > 16)
        {
            $erreur        = true;
            $msg_info_send .= TEXT_PF_SAVE_ERR_ZOOM . "<br>";
        }

        // ---- 1i. Minimum zoom must be in [2, 5] and <= default zoom ----
        if ($mapMinZoom < 2 || $mapMinZoom > 5)
        {
            $erreur        = true;
            $msg_info_send .= TEXT_PF_SAVE_ERR_MIN_ZOOM . "<br>";
        }
        elseif ($mapMinZoom > $mapZoom)
        {
            $erreur        = true;
            $msg_info_send .= TEXT_PF_SAVE_ERR_ZOOM_ORDER . "<br>";
        }

        // ---- 2. Apply the update only if every check passed ----
        if (!$erreur)
        {
            tep_db_query($sql_link,
                "UPDATE " . TABLE_TERRITOIRE . "
                 SET theme_region   = '" . $theme_region   . "',
                     region_default = "  . $region_default . ",
                     service_hydro  = '" . $service_hydro  . "',
                     timezone_php   = '" . $timezone_php   . "',
                     lang           = '" . $lang           . "',
                     mapLong        = "  . $mapLong        . ",
                     mapLat         = "  . $mapLat         . ",
                     mapZoom        = "  . $mapZoom        . ",
                     mapMinZoom     = "  . $mapMinZoom     . "
                 WHERE id_territoire = " . $territoire_id);
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
            $type_action = 14; // Plateform settings action type

            tep_db_query($sql_link,
                "INSERT INTO " . TABLE_ACTIONS . " (id_user, type_action, info, dateheure)
                 VALUES (" . $id_user_agent . ", '" . $type_action . "', '" . TEXT_PF_SAVE_ACTION_LOG . "', '" . $today_us . "')");

            tep_db_query($sql_link, "COMMIT");

            $msg_info_send = TEXT_PF_SAVE_OK;
        }
    }
    catch (Exception $e)
    {
        // Unexpected SQL or runtime exception — roll back everything
        tep_db_query($sql_link, "ROLLBACK");
        $msg_info_send  = TEXT_PF_SAVE_ERR_WRITE . "<br>";
        $msg_info_send .= TEXT_PF_SAVE_ERR_DETAIL . $e->getMessage();
        $erreur         = true;
    }
}
else
{
    // Request method is not POST — should not happen in normal use
    $msg_info_send = "<span style='font-size:16px;'>" . TEXT_PF_SAVE_ERR_REQUEST . "</span><br><br>";
    $erreur        = true;
}

// Return JSON response to the client
echo json_encode([
    'erreur'   => $erreur,
    'msg_info' => $msg_info_send,
]);
?>