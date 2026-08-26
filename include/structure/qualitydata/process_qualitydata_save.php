<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
AJAX endpoint — quality codes bulk save
Called by saveQualityData() in gestion_quality_data.php.
Receives the entire formQualityData via multipart POST.
For each existing quality code, updates all five fields
(init, nom, info, type, couleur).
For the new-entry row (id = 0), inserts a new record after checking that
no code with the same init label already exists.
All writes are wrapped in a transaction; any exception triggers a ROLLBACK.
Security: all POST inputs are sanitised via post_secure().
Returns JSON:
  erreur   : bool   — true if the operation failed
  msg_info : string — feedback message for the user
Note: on success the client reloads the page with ?save=true; the success
message is therefore displayed by gestion_quality_data.php on reload,
not by this endpoint.
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

header('Content-Type: text/html; charset=utf-8');

$sql_link = mysqli_connect(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD, DB_DATABASE)
    or die('Cannot connect to the database');
mysqli_query($sql_link, 'SET NAMES UTF8');


// -----------------------------------------------
// Initialise output variables

$msg_info_send = '';
$erreur        = false;


// -----------------------------------------------
// Process the POST request

if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
    $id_user_agent = $_POST['id_user_agent'] ?? '';
    $territoire_id = $_POST['territoire_id'] ?? '';

    // Wrap all writes in a transaction
    tep_db_query($sql_link, "START TRANSACTION");

    try
    {
        // ---- Update existing quality codes ----

        $quality_query = tep_db_query($sql_link,
            "SELECT DISTINCT id_data_qualite FROM " . TABLE_DATA_QUALITE);

        while ($quality = tep_db_fetch_array($quality_query))
        {
            $id_quality = $quality['id_data_qualite'];

            $init    = post_secure($sql_link, $_POST['quality_init_'        . $id_quality]);
            $nom     = post_secure($sql_link, $_POST['quality_nom_'         . $id_quality]);
            $info    = post_secure($sql_link, $_POST['quality_info_'        . $id_quality]);
            $type    = post_secure($sql_link, $_POST['quality_select_type_' . $id_quality]);
            $couleur = post_secure($sql_link, $_POST['quality_color_'       . $id_quality]);

            tep_db_query($sql_link,
                "UPDATE " . TABLE_DATA_QUALITE . "
                 SET init_qualite_data    = '" . $init    . "',
                     nom_qualite_data     = '" . $nom     . "',
                     info_qualite_data    = '" . $info    . "',
                     couleur_qualite_data = '" . $couleur . "',
                     id_eq_type           = '" . $type    . "'
                 WHERE id_data_qualite = " . $id_quality);
        }


        // ---- Insert new quality code (new-entry row, id = 0) ----

        if (tep_not_null($_POST['quality_init_0']))
        {
            $init_0    = post_secure($sql_link, $_POST['quality_init_0']);
            $nom_0     = post_secure($sql_link, $_POST['quality_nom_0']);
            $info_0    = post_secure($sql_link, $_POST['quality_info_0']);
            $type_0    = post_secure($sql_link, $_POST['quality_select_type_0']);
            $couleur_0 = post_secure($sql_link, $_POST['quality_color_0']);

            // Duplicate-label guard: a short code (init) must be unique
            $verif_query = tep_db_query($sql_link,
                "SELECT DISTINCT id_data_qualite FROM " . TABLE_DATA_QUALITE . "
                 WHERE init_qualite_data = '" . $init_0 . "'");
            $verif_tab = tep_db_fetch_array($verif_query);

            if (isset($verif_tab['id_data_qualite']) && tep_not_null($verif_tab['id_data_qualite']))
            {
                // A code with the same label already exists — block the insert
                $erreur        = true;
                $msg_info_send = TEXT_QD_SAVE_ERR_DUPLICATE . "'" . $init_0 . "'.";
            }
            else
            {
                tep_db_query($sql_link,
                    "INSERT INTO " . TABLE_DATA_QUALITE . "
                        (init_qualite_data, nom_qualite_data, info_qualite_data, couleur_qualite_data, id_eq_type)
                     VALUES
                        ('" . $init_0 . "', '" . $nom_0 . "', '" . $info_0 . "', '" . $couleur_0 . "', '" . $type_0 . "')");
            }
        }


        // ---- Log the action ----
        $type_action = 13; // Platform settings action type
        $today_us    = date('Y-m-d H:i:s');

        tep_db_query($sql_link,
            "INSERT INTO " . TABLE_ACTIONS . " (id_user, type_action, info, dateheure)
             VALUES (" . $id_user_agent . ", '" . $type_action . "', '" . TEXT_QD_SAVE_ACTION_LOG . "', '" . $today_us . "')");


        // All operations succeeded — commit
        // Note: if a duplicate was found, $erreur is already true but we
        // still commit the updates to existing codes (original behaviour preserved)
        tep_db_query($sql_link, "COMMIT");
    }
    catch (Exception $e)
    {
        // Any unexpected exception rolls back all writes
        tep_db_query($sql_link, "ROLLBACK");

        $msg_info_send  = TEXT_QD_SAVE_ERR_WRITE . "<br>";
        $msg_info_send .= TEXT_QD_SAVE_ERR_DETAIL . $e->getMessage();
        $erreur         = true;
    }
}
else
{
    // Request method is not POST — should not happen in normal use
    $msg_info_send = "<span style='font-size:16px;'>" . TEXT_QD_SAVE_ERR_REQUEST . "</span><br><br>";
    $erreur        = true;
}


// Return JSON response to the client
echo json_encode([
    'erreur'   => $erreur,
    'msg_info' => $msg_info_send,
]);
?>