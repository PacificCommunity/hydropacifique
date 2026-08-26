<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Correction validation handler - AJAX server-side process
- Applies validated corrections to the main data table
- Wraps all operations in a DB transaction
- Called from graph_correct_chron.php
----------------------------------------
*/

// -----------------------------------------------
// Core dependencies: config, DB tables, functions

require('../../config.php');
require('../../database_tables.php');

require('../../function/date.php');
require('../../function/database.php');
require('../../function/html_output.php');
require('../../function/general.php');
require('../../function/sql_function.php');

// Ensure proper UTF-8 encoding for accented characters
header('Content-Type: text/html; charset=utf-8');

// Database connection
$sql_link = mysqli_connect(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD, DB_DATABASE)
    or die('Impossible de se connecter à la base de données!');
mysqli_query($sql_link, 'SET NAMES UTF8');

// Load translation strings for the active language
require('../../text_content_' . LANGUAGE . '.php');


// -----------------------------------------------
// Parse JSON input from AJAX request

$dataInfo      = json_decode(file_get_contents('php://input'), true);
$territoire_id = $dataInfo['territoire_id'];
$timezone_php  = $dataInfo['timezone_php'];

date_default_timezone_set($timezone_php);
$now              = new DateTime();
$now_us_formatted = $now->format('Y-m-d H:i:s');
$now_fr_formatted = $now->format('d-m-Y H:i:s');

$territoire_lang = $dataInfo['territoire_lang'];
$id_correction   = $dataInfo['id_correction'];
$tabIdMeta       = $dataInfo['tabIdMeta'];
$idChronEncours  = $dataInfo['idTypeChron'];  // Target series ID
$idCodeQual      = $dataInfo['idCodeQual'];   // Quality code ID
$obsUser         = post_secure($sql_link, $dataInfo['obsUser']);

$msg_valid = '';


// -----------------------------------------------
// Apply each validated correction inside a transaction

if (isset($tabIdMeta))
{
    tep_db_query($sql_link, "START TRANSACTION");

    try
    {
        // ---------------------------------------------------------------
        // Socle « série complète » (cible <> source uniquement).
        //
        // Quand l'utilisateur enregistre la correction dans une chronique
        // DIFFÉRENTE de la chronique d'origine du bloc, la cible doit
        // recevoir TOUTE la série source sur la fenêtre du chargement de
        // la page (datetime_first/end de l'en-tête du bloc), AVANT que les
        // portions corrigées soient appliquées par-dessus dans la boucle.
        //
        // Sans cela, la cible ne contiendrait que les sous-périodes
        // corrigées. La copie se fait sous un meta « base » dédié ; chaque
        // correction garde ensuite son propre meta (delete-then-insert sur
        // sa plage écrase la portion correspondante du socle).
        //
        // Les sentinelles de lacune (ABS = 8888/9999/88888/99999) sont
        // normalisées à -99999, comme dans process_chron_calcul.php.
        // ---------------------------------------------------------------
        $correction_header = tep_db_fetch_array(tep_db_query($sql_link,
            "SELECT id, id_user, id_station, id_chron_init, datetime_first, datetime_end
             FROM " . TABLE_DATA_CORRECTION . " WHERE id = " . (int)$id_correction));

        $id_chron_init = (int)$correction_header['id_chron_init'];

        if ($id_chron_init > 0 && $idChronEncours != $id_chron_init)
        {
            $base_id_station       = (int)$correction_header['id_station'];
            $base_id_user          = (int)$correction_header['id_user'];
            $base_datetime_first   = $correction_header['datetime_first'];
            $base_datetime_end     = $correction_header['datetime_end'];

            // Meta « base » pour le socle complet.
            tep_db_query($sql_link,
                "INSERT INTO " . TABLE_DATA_META
                . " (id_station, id_typedata, id_codequal, id_user, source, obs, obs_user)"
                . " VALUES (" . $base_id_station . ", " . $idChronEncours . ", " . $idCodeQual
                . ", " . $base_id_user . ", 'Correction', '" . TEXT_CALCUL_VALID_BASE_OBS . "', '" . $obsUser . "')");
            $base_meta_id = mysqli_insert_id($sql_link);

            // Purge de la cible sur la fenêtre du socle, puis copie de la
            // série source (sentinelles préservées).
            deleteDataAndMeta($sql_link, $base_id_station, $idChronEncours,
                $base_datetime_first, $base_datetime_end);

            tep_db_query($sql_link,
                "INSERT INTO " . TABLE_DATA_ALL . " (dateheure, valeur, id_meta)
                 SELECT da.dateheure,
                        CASE WHEN ABS(da.valeur) IN (8888, 9999, 88888, 99999) THEN -99999
                             ELSE da.valeur
                        END,
                        " . $base_meta_id . "
                 FROM " . TABLE_DATA_ALL . " da
                 JOIN " . TABLE_DATA_META . " dm ON da.id_meta = dm.id
                 WHERE dm.id_typedata = " . $id_chron_init . "
                   AND dm.id_station  = " . $base_id_station . "
                   AND da.dateheure  >= '" . $base_datetime_first . "'
                   AND da.dateheure  <= '" . $base_datetime_end . "'
                 ORDER BY da.dateheure ASC");
        }

        foreach ($tabIdMeta as $idValue)
        {
            $idValue = (int)$idValue; // Cast to int for security

            // ---- Retrieve correction meta record ----
            $meta_correction_tab = tep_db_fetch_array(tep_db_query($sql_link,
                "SELECT id, id_station, id_typedata, id_user, obs, info_correction, datetime_first, datetime_end
                 FROM " . TABLE_DATA_META_CORRECTION . " WHERE id = " . $idValue));

            $id_station      = $meta_correction_tab['id_station'];
            $id_chron        = $meta_correction_tab['id_typedata'];
            $id_user         = $meta_correction_tab['id_user'];
            $obs             = $meta_correction_tab['obs'];
            $info_correction = $meta_correction_tab['info_correction'];

            $datetime_first          = $meta_correction_tab['datetime_first'];
            $datetime_first_formated = new DateTime($datetime_first);
            $datetime_end            = $meta_correction_tab['datetime_end'];
            $datetime_end_formated   = new DateTime($datetime_end);

            // ---- Get actual date range from correction data ----
            // (needed for time-offset corrections which may shift dates beyond the original period)
            $limit_data_delete_tab = tep_db_fetch_array(tep_db_query($sql_link,
                "SELECT MIN(dateheure) AS first_date, MAX(dateheure) AS last_date
                 FROM " . TABLE_DATA_ALL_CORRECTION . " WHERE id_meta = " . $idValue));

            $datetime_correction_first          = $limit_data_delete_tab['first_date'];
            $datetime_correction_first_formated = new DateTime($datetime_correction_first);
            $datetime_correction_end            = $limit_data_delete_tab['last_date'];
            $datetime_correction_end_formated   = new DateTime($datetime_correction_end);

            // Expand date range to cover all affected data
            if ($datetime_correction_first_formated > $datetime_first_formated) { $datetime_correction_first = $datetime_first; }
            if ($datetime_correction_end_formated   < $datetime_end_formated)   { $datetime_correction_end   = $datetime_end; }

            // ---- Insert meta record in production table ----
            tep_db_query($sql_link,
                "INSERT INTO " . TABLE_DATA_META
                . " (id_station, id_typedata, id_codequal, id_user, source, obs, obs_user)"
                . " VALUES (" . $id_station . ", " . $idChronEncours . ", " . $idCodeQual
                . ", " . $id_user . ", 'Correction', '" . $obs . "', '" . $obsUser . "')");
            $meta_id_encours = mysqli_insert_id($sql_link);

            $sql_copyData = "INSERT INTO " . TABLE_DATA_ALL . " (dateheure, valeur, id_meta)
                             SELECT dateheure, valeur, " . $meta_id_encours . "
                             FROM " . TABLE_DATA_ALL_CORRECTION . " WHERE id_meta = " . $idValue;

            // ---- Delete existing data in target range, then insert corrected data ----
            deleteDataAndMeta($sql_link, $id_station, $idChronEncours,
                $datetime_correction_first, $datetime_correction_end);
            tep_db_query($sql_link, $sql_copyData);

            // ---- Mark correction as validated ----
            tep_db_query($sql_link,
                "UPDATE " . TABLE_DATA_META_CORRECTION
                . " SET valid=1, datetime_correction='" . $now_us_formatted . "',"
                . " id_chron_modif=" . $idChronEncours . ", obs_user='" . $obsUser . "'"
                . " WHERE id=" . $idValue);
        }

        tep_db_query($sql_link, "COMMIT");
        $msg_valid = TEXT_CALCUL_VALID_SUCCESS;
    }
    catch (Exception $e)
    {
        tep_db_query($sql_link, "ROLLBACK");
        $msg_valid = TEXT_CALCUL_VALID_ERROR_WRITE
                   . "<br>" . TEXT_CALCUL_VALID_ERROR_DETAIL . $e->getMessage();
    }
}
else
{
    $msg_valid = TEXT_CALCUL_VALID_NO_DATA;
}


// -----------------------------------------------
// Return result as JSON

echo json_encode([
    'tabIdMeta' => $tabIdMeta,
    'msg_valid' => $msg_valid,
]);
?>