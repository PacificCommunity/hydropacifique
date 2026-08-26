<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
AJAX endpoint — gauging equipment bulk save
Called by saveEqJge() in gestion_eq_jaugeage.php.
Receives all form fields (FormData POST) for the three equipment tabs:
  - Current meters (TABLE_MOULINET)   — fields: moul_num_*, moul_fabricant_*, moul_obs_*
  - Propellers    (TABLE_HELICE)      — fields: helice_num_*, helice_diam_*, helice_pas_*,
                                               helice_l1..b3_*, helice_fabricant_*, helice_obs_*
  - Weights       (TABLE_SAUMON)      — fields: saumon_num_*, saumon_titre_*, saumon_poids_*,
                                               saumon_dist_axe_*, saumon_t_air_*, saumon_r_dist_*,
                                               saumon_fabricant_*, saumon_obs_*
Index 0 = new entry (INSERT); existing ids = UPDATE.
All six operations run inside a single transaction; any exception triggers
a ROLLBACK and an error message to the client.
Returns JSON:
  erreur   : bool   — true when an error occurred
  msg_info : string — feedback message for the user
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
// Global variables

$msg_info_send = '';
$erreur        = false;


// -----------------------------------------------
// Main block — only processes POST requests

if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
    $id_user_agent = $_POST['id_user_agent'] ?? '';
    $territoire_id = $_POST['territoire_id'] ?? '';

    tep_db_query($sql_link, "START TRANSACTION");

    try
    {
        // -----------------------------------------------
        // Current meters — update existing rows

        $moulinet_query = tep_db_query($sql_link,
            "SELECT DISTINCT id FROM " . TABLE_MOULINET);

        while ($moulinet = tep_db_fetch_array($moulinet_query))
        {
            $id_moulinet    = $moulinet['id'];
            $moul_num       = post_secure($sql_link, $_POST['moul_num_'       . $id_moulinet]);
            $moul_fabricant = post_secure($sql_link, $_POST['moul_fabricant_' . $id_moulinet]);
            $moul_obs       = post_secure($sql_link, $_POST['moul_obs_'       . $id_moulinet]);

            tep_db_query($sql_link,
                "UPDATE " . TABLE_MOULINET . "
                 SET num       = '" . $moul_num       . "',
                     fabricant = '" . $moul_fabricant . "',
                     obs       = '" . $moul_obs       . "'
                 WHERE id = " . $id_moulinet);
        }

        // Current meters — insert new row (if num_0 is not empty)
        if (tep_not_null($_POST['moul_num_0']))
        {
            $moul_num_0       = post_secure($sql_link, $_POST['moul_num_0']);
            $moul_fabricant_0 = post_secure($sql_link, $_POST['moul_fabricant_0']);
            $moul_obs_0       = post_secure($sql_link, $_POST['moul_obs_0']);

            tep_db_query($sql_link,
                "INSERT INTO " . TABLE_MOULINET . " (num, fabricant, obs)
                 VALUES ('" . $moul_num_0 . "', '" . $moul_fabricant_0 . "', '" . $moul_obs_0 . "')");
        }


        // -----------------------------------------------
        // Propellers — update existing rows

        $helice_query = tep_db_query($sql_link,
            "SELECT DISTINCT id FROM " . TABLE_HELICE);

        while ($helice = tep_db_fetch_array($helice_query))
        {
            $id_helice         = $helice['id'];
            $helice_num        = post_secure($sql_link, $_POST['helice_num_'       . $id_helice]);
            $helice_diam       = post_secure($sql_link, $_POST['helice_diam_'      . $id_helice]);
            $helice_pas        = post_secure($sql_link, $_POST['helice_pas_'       . $id_helice]);
            $helice_l1         = post_secure($sql_link, $_POST['helice_l1_'        . $id_helice]);
            $helice_a1         = post_secure($sql_link, $_POST['helice_a1_'        . $id_helice]);
            $helice_b1         = post_secure($sql_link, $_POST['helice_b1_'        . $id_helice]);
            $helice_l2         = post_secure($sql_link, $_POST['helice_l2_'        . $id_helice]);
            $helice_a2         = post_secure($sql_link, $_POST['helice_a2_'        . $id_helice]);
            $helice_b2         = post_secure($sql_link, $_POST['helice_b2_'        . $id_helice]);
            $helice_a3         = post_secure($sql_link, $_POST['helice_a3_'        . $id_helice]);
            $helice_b3         = post_secure($sql_link, $_POST['helice_b3_'        . $id_helice]);
            $helice_fabricant  = post_secure($sql_link, $_POST['helice_fabricant_' . $id_helice]);
            $helice_obs        = post_secure($sql_link, $_POST['helice_obs_'       . $id_helice]);

            tep_db_query($sql_link,
                "UPDATE " . TABLE_HELICE . "
                 SET num       = '" . $helice_num       . "',
                     diametre  = '" . $helice_diam      . "',
                     pas       = '" . $helice_pas       . "',
                     l1        = '" . $helice_l1        . "',
                     a1        = '" . $helice_a1        . "',
                     b1        = '" . $helice_b1        . "',
                     l2        = '" . $helice_l2        . "',
                     a2        = '" . $helice_a2        . "',
                     b2        = '" . $helice_b2        . "',
                     a3        = '" . $helice_a3        . "',
                     b3        = '" . $helice_b3        . "',
                     fabricant = '" . $helice_fabricant . "',
                     obs       = '" . $helice_obs       . "'
                 WHERE id = " . $id_helice);
        }

        // Propellers — insert new row (if num_0 is not empty)
        if (tep_not_null($_POST['helice_num_0']))
        {
            $helice_num_0       = post_secure($sql_link, $_POST['helice_num_0']);
            $helice_diam_0      = post_secure($sql_link, $_POST['helice_diam_0']);
            $helice_pas_0       = post_secure($sql_link, $_POST['helice_pas_0']);
            $helice_l1_0        = post_secure($sql_link, $_POST['helice_l1_0']);
            $helice_a1_0        = post_secure($sql_link, $_POST['helice_a1_0']);
            $helice_b1_0        = post_secure($sql_link, $_POST['helice_b1_0']);
            $helice_l2_0        = post_secure($sql_link, $_POST['helice_l2_0']);
            $helice_a2_0        = post_secure($sql_link, $_POST['helice_a2_0']);
            $helice_b2_0        = post_secure($sql_link, $_POST['helice_b2_0']);
            $helice_a3_0        = post_secure($sql_link, $_POST['helice_a3_0']);
            $helice_b3_0        = post_secure($sql_link, $_POST['helice_b3_0']);
            $helice_fabricant_0 = post_secure($sql_link, $_POST['helice_fabricant_0']);
            $helice_obs_0       = post_secure($sql_link, $_POST['helice_obs_0']);

            tep_db_query($sql_link,
                "INSERT INTO " . TABLE_HELICE . "
                    (num, diametre, pas, l1, a1, b1, l2, a2, b2, a3, b3, fabricant, obs)
                 VALUES
                    ('" . $helice_num_0       . "',
                     '" . $helice_diam_0      . "',
                     '" . $helice_pas_0       . "',
                     '" . $helice_l1_0        . "',
                     '" . $helice_a1_0        . "',
                     '" . $helice_b1_0        . "',
                     '" . $helice_l2_0        . "',
                     '" . $helice_a2_0        . "',
                     '" . $helice_b2_0        . "',
                     '" . $helice_a3_0        . "',
                     '" . $helice_b3_0        . "',
                     '" . $helice_fabricant_0 . "',
                     '" . $helice_obs_0       . "')");
        }


        // -----------------------------------------------
        // Weights — update existing rows

        $saumon_query = tep_db_query($sql_link,
            "SELECT DISTINCT id FROM " . TABLE_SAUMON);

        while ($saumon = tep_db_fetch_array($saumon_query))
        {
            $id_saumon        = $saumon['id'];
            $saumon_num       = post_secure($sql_link, $_POST['saumon_num_'       . $id_saumon]);
            $saumon_titre     = post_secure($sql_link, $_POST['saumon_titre_'     . $id_saumon]);
            $saumon_poids     = post_secure($sql_link, $_POST['saumon_poids_'     . $id_saumon]);
            $saumon_dist_axe  = post_secure($sql_link, $_POST['saumon_dist_axe_'  . $id_saumon]);
            $saumon_t_air     = post_secure($sql_link, $_POST['saumon_t_air_'     . $id_saumon]);
            $saumon_r_dist    = post_secure($sql_link, $_POST['saumon_r_dist_'    . $id_saumon]);
            $saumon_fabricant = post_secure($sql_link, $_POST['saumon_fabricant_' . $id_saumon]);
            $saumon_obs       = post_secure($sql_link, $_POST['saumon_obs_'       . $id_saumon]);

            tep_db_query($sql_link,
                "UPDATE " . TABLE_SAUMON . "
                 SET num          = '" . $saumon_num       . "',
                     titre        = '" . $saumon_titre     . "',
                     poids        = '" . $saumon_poids     . "',
                     distance_axe = '" . $saumon_dist_axe  . "',
                     t_air        = '" . $saumon_t_air     . "',
                     r_dist       = '" . $saumon_r_dist    . "',
                     fabricant    = '" . $saumon_fabricant . "',
                     obs          = '" . $saumon_obs       . "'
                 WHERE id = " . $id_saumon);
        }

        // Weights — insert new row (if num_0 is not empty)
        if (tep_not_null($_POST['saumon_num_0']))
        {
            $saumon_num_0       = post_secure($sql_link, $_POST['saumon_num_0']);
            $saumon_titre_0     = post_secure($sql_link, $_POST['saumon_titre_0']);
            $saumon_poids_0     = post_secure($sql_link, $_POST['saumon_poids_0']);
            $saumon_dist_axe_0  = post_secure($sql_link, $_POST['saumon_dist_axe_0']);
            $saumon_t_air_0     = post_secure($sql_link, $_POST['saumon_t_air_0']);
            $saumon_r_dist_0    = post_secure($sql_link, $_POST['saumon_r_dist_0']);
            $saumon_fabricant_0 = post_secure($sql_link, $_POST['saumon_fabricant_0']);
            $saumon_obs_0       = post_secure($sql_link, $_POST['saumon_obs_0']);

            tep_db_query($sql_link,
                "INSERT INTO " . TABLE_SAUMON . "
                    (num, titre, poids, distance_axe, t_air, r_dist, fabricant, obs)
                 VALUES
                    ('" . $saumon_num_0       . "',
                     '" . $saumon_titre_0     . "',
                     '" . $saumon_poids_0     . "',
                     '" . $saumon_dist_axe_0  . "',
                     '" . $saumon_t_air_0     . "',
                     '" . $saumon_r_dist_0    . "',
                     '" . $saumon_fabricant_0 . "',
                     '" . $saumon_obs_0       . "')");
        }


        // -----------------------------------------------
        // Action log

        $today_us    = date('Y-m-d H:i:s');
        $type_action = 13; // Configuration action type
        $info_action = TEXT_EJ_SAVE_ACTION_LOG;

        tep_db_query($sql_link,
            "INSERT INTO " . TABLE_ACTIONS . " (id_user, type_action, info, dateheure)
             VALUES (" . $id_user_agent . ", '" . $type_action . "', '" . $info_action . "', '" . $today_us . "')");


        tep_db_query($sql_link, "COMMIT");

        $msg_info_send = TEXT_EJ_SAVE_OK;

    }
    catch (Exception $e)
    {
        tep_db_query($sql_link, "ROLLBACK");

        $msg_info_send  = TEXT_EJ_SAVE_ERR_WRITE;
        $msg_info_send .= "<br>" . TEXT_EJ_SAVE_ERR_DETAIL . $e->getMessage();
        $erreur = true;
    }
}
else
{
    $msg_info_send = TEXT_EJ_SAVE_ERR_REQUEST;
    $erreur        = true;
}


// Return JSON response to the client
echo json_encode([
    'erreur'   => $erreur,
    'msg_info' => $msg_info_send,
]);
?>
