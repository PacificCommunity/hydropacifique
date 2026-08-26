<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
AJAX endpoint — save a quick JGE (water level + flow rate)
Aligned with process_jge_save.php (reference implementation).

Receives JSON: {
    idUser, territoireId,
    idJge, idStation,
    hauteur, debit, date, heure,
    codeQual, obs
}

Workflow:
1. Validate inputs (date / time / numeric / station)
2. Insert (new) or Update (existing) the JGE record, inside a transaction
3. Flag Nomad records (from_nomad / new_nomad) when HP_VERSION == 'Nomad'
4. Log the action (server-side timestamp)

Returns JSON: {
    valid_process, js_text,
    id_jge, id_station,
    date, heure, hauteur, debit
}
----------------------------------------
*/

require('../../config.php');
require('../../database_tables.php');

require('../../function/date.php');
require('../../function/gestion_erreur.php');
require('../../function/database.php');
require('../../function/html_output.php');
require('../../function/general.php');

require('../../text_content_' . LANGUAGE . '.php');

@ini_set('display_errors', '0');
error_reporting(E_ERROR | E_PARSE);

header('Content-Type: application/json; charset=utf-8');

$sql_link = mysqli_connect(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD, DB_DATABASE)
    or die(json_encode(['valid_process' => false, 'js_text' => TEXT_JGE_SIMPLE_ERR_DB]));
mysqli_query($sql_link, 'SET NAMES UTF8');


// -----------------------------------------------
// Process POST request only (same guard as process_jge_save.php)

if ($_SERVER['REQUEST_METHOD'] !== 'POST')
{
    echo json_encode([
        'valid_process' => false,
        'js_text'       => TEXT_JGE_SAVE_ERR_METHOD,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$data          = json_decode(file_get_contents('php://input'), true);
$id_user       = isset($data['idUser'])       ? (int)$data['idUser']       : 0;
$territoire_id = isset($data['territoireId']) ? (int)$data['territoireId'] : 0;
$id_jge        = isset($data['idJge'])        ? (int)$data['idJge']        : 0;
$id_station    = isset($data['idStation'])    ? (int)$data['idStation']    : 0;
$hauteur       = isset($data['hauteur'])      ? $data['hauteur']           : '';
$debit         = isset($data['debit'])        ? $data['debit']             : '';
$date_jge      = isset($data['date'])         ? $data['date']              : '';
$heure_jge     = isset($data['heure'])        ? $data['heure']             : '';
$code_qual     = isset($data['codeQual'])     ? (int)$data['codeQual']     : 0;
$obs           = isset($data['obs'])          ? $data['obs']               : '';

$is_new = ($id_jge < 1);


// -----------------------------------------------
// Defensive validation
// (unlike process_jge_save.php where empty numeric values are allowed,
//  the quick-entry form requires all four fields to be filled)

$js_text = '';

if (!validNumeric($hauteur) || trim((string)$hauteur) === '')
{
    $js_text .= TEXT_JGE_SIMPLE_ERR_HMOY . "<br>";
}

if (!validNumeric($debit) || trim((string)$debit) === '')
{
    $js_text .= TEXT_JGE_SIMPLE_ERR_Q . "<br>";
}

if (!validDate($date_jge))
{
    $js_text .= TEXT_JGE_SIMPLE_ERR_DATE . "<br>";
}

if (!validTime($heure_jge))
{
    $js_text .= TEXT_JGE_SIMPLE_ERR_HEURE . "<br>";
}


// -----------------------------------------------
// Station lookup (filtered by territory, defensive — prevents
// saving on a station outside the user's territory)

$station_array = [];
if ($id_station > 0 && $territoire_id > 0)
{
    $station_query = tep_db_query($sql_link,
        "SELECT DISTINCT s.id_station, s.nom_station, s.code_station
         FROM " . TABLE_STATION . " s
         JOIN " . TABLE_REGION  . " r ON s.id_region = r.id_region
         WHERE s.station_type = 11
           AND r.id_territoire = $territoire_id
           AND s.id_station    = $id_station");

    while ($s = tep_db_fetch_array($station_query))
    {
        $station_array[$s['id_station']] = [
            'nom_station'  => html_entity_decode($s['nom_station']  ?? ''),
            'code_station' => html_entity_decode($s['code_station'] ?? ''),
        ];
    }
}

if (!isset($station_array[$id_station]))
{
    $js_text .= TEXT_JGE_SIMPLE_ERR_STATION . "<br>";
}


// -----------------------------------------------
// Bail out on any validation error

if ($js_text !== '')
{
    echo json_encode([
        'valid_process' => false,
        'js_text'       => $js_text,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}


// -----------------------------------------------
// Save to database (wrapped in a transaction, same pattern
// as process_jge_save.php)

$info_station   = $station_array[$id_station]['code_station'] . ' - ' . $station_array[$id_station]['nom_station'];
$mysql_datetime = datefr_us($date_jge) . ' ' . $heure_jge;

// DATETIME field: escape only (kept quoted)
$dt_safe = mysqli_real_escape_string($sql_link, $mysql_datetime);

// Free-text field: sanitized via post_secure (kept quoted)
$obs_safe = post_secure($sql_link, $obs);

// Numeric columns (FLOAT): empty -> NULL, comma -> dot, returned already
// quoted or NULL by preparerSQL(). They MUST be written WITHOUT surrounding
// quotes in the SQL below (preparerSQL adds its own). This replaces the
// previous real_escape_string approach, which passed comma decimals as-is
// and could trigger "Data truncated" on numeric columns.
$sql_depouil_q    = preparerSQL($debit);
$sql_depouil_hmoy = preparerSQL($hauteur);

tep_db_query($sql_link, "START TRANSACTION");

try
{
    // ---- Create or update the JGE record ----
    if ($is_new)
    {
        // Let MySQL auto_increment generate the id
        tep_db_query($sql_link,
            "INSERT INTO " . TABLE_DATA_JGE . " (id_station) VALUES ($id_station)");
        $id_jge = mysqli_insert_id($sql_link);

        if (HP_VERSION == 'Nomad')
        {
            tep_db_query($sql_link, "UPDATE " . TABLE_DATA_JGE . " SET from_nomad=1, new_nomad=1 WHERE id=" . $id_jge);
        }

        $type_action = 10; // JGE creation
        $info_action = TEXT_JGE_SIMPLE_LOG_CREATE . ' - ' . $info_station . ' - ' . $mysql_datetime;
        $js_text     = sprintf(TEXT_JGE_SIMPLE_CREATED, $info_station);
    }
    else
    {
        $type_action = 11; // JGE update
        $info_action = TEXT_JGE_SIMPLE_LOG_UPDATE . ' - ' . $info_station . ' - ' . $mysql_datetime;
        $js_text     = sprintf(TEXT_JGE_SIMPLE_UPDATED, $info_station);
    }

    tep_db_query($sql_link,
        "UPDATE " . TABLE_DATA_JGE . " SET
            id_station   = $id_station,
            datetime     = '$dt_safe',
            depouil_q    = $sql_depouil_q,
            depouil_hmoy = $sql_depouil_hmoy,
            code_qualite = $code_qual,
            obs          = '$obs_safe'
         WHERE id = $id_jge");

    if (HP_VERSION == 'Nomad')
    {
        tep_db_query($sql_link, "UPDATE " . TABLE_DATA_JGE . " SET from_nomad=1 WHERE id=" . $id_jge);
    }


    // ---- Log the action — type 10 = JGE creation, 11 = JGE update ----
    // Server-side timestamp, same as process_jge_save.php

    $today_us    = date('Y-m-d H:i:s');
    $info_action = post_secure($sql_link, $info_action);
    tep_db_query($sql_link,
        "INSERT INTO " . TABLE_ACTIONS . " (id_user, type_action, info, dateheure)
         VALUES ($id_user, $type_action, '$info_action', '$today_us')");

    tep_db_query($sql_link, "COMMIT");
}
catch (Exception $e)
{
    // ROLLBACK can be enabled here once tep_db_query's error behavior is
    // confirmed (it must throw an exception for this catch to trigger).
    // tep_db_query($sql_link, "ROLLBACK");
    echo json_encode([
        'valid_process' => false,
        'js_text'       => TEXT_JGE_SAVE_ERR_TRANSACTION . "<br><br>" . TEXT_JGE_SAVE_ERR_EXCEPTION . $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}


// -----------------------------------------------
// Return result as JSON

echo json_encode([
    'valid_process' => true,
    'js_text'       => $js_text,
    'id_jge'        => $id_jge,
    'id_station'    => $id_station,
    // Echo back saved values so the JS can refresh the table row in place
    'date'          => $date_jge,
    'heure'         => $heure_jge,
    'hauteur'       => $hauteur,
    'debit'         => $debit,
], JSON_UNESCAPED_UNICODE);
?>