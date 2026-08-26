<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
HP-NOMAD
Uploads the data captured on the local (NOMAD) version to the remote server.
AJAX endpoint on the server, called from sync.php.
----------------------------------------
SAFETY NOTES (revision):
  - Symmetric dual transaction (local AND online). The local hp_load UPDATEs are
    committed ONLY if the online upload succeeds. On error or stop, both sides are
    rolled back: no field record is wrongly flagged as "synchronized", so it will
    be picked up again on the next run.
  - Cooperative stop: a flag file is checked between each table.
  - Explicit messages for every outcome (success / stop / error).
----------------------------------------
*/

// ----------------------------------------------
// Core configuration and shared helper libraries required by this script

require('../../config.php');
require('../../database_tables.php');

require('../../function/date.php');	
require('../../function/database.php');	
require('../../function/html_output.php');
require('../../function/general.php');
require('../../function/update_db.php');

// Load the translation strings for the active language
require('../../text_content_' . LANGUAGE . '.php');

// Set UTF-8 so accented characters render correctly
header('Content-Type: text/html; charset=utf-8');

$erreur   = false;
$arret    = false; // true once the user has requested a stop
$process_info = '';

// -----------------------------------------------------
// Read and decode the JSON payload sent by the AJAX request
$jsonDataInfo = file_get_contents('php://input');
$dataInfo     = json_decode($jsonDataInfo, true);
$id_user      = isset($dataInfo['idUser']) ? (int)$dataInfo['idUser'] : 0;

$timezone_php = $dataInfo['timezone_php'];
date_default_timezone_set($timezone_php);
$datetime_now    = date('Y-m-d H:i:s');     // current datetime (SQL format)
$datetime_now_fr = date('d/m/Y H:i:s');     // current datetime (display format)

// Stop flag file (must match the path built in stop_sync.php)
$flagFile = sys_get_temp_dir() . '/vainatura_stop_sync_' . $id_user . '.flag';

// Returns true if the user requested a stop (the flag file exists)
function arretDemande($flagFile) {
    return file_exists($flagFile);
}

// -----------------------------------------------------
// Database connections

// Shared PDO options for both connections
$options = [
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    // Local connection (NOMAD / offline database)
    $connLocal = new PDO(
        "mysql:host=" . DB_SERVER . ";dbname=" . DB_DATABASE,
        DB_SERVER_USERNAME,
        DB_SERVER_PASSWORD,
        $options
    );

    // Online connection (SERVER / remote database)
    $connOnline = new PDO(
        "mysql:host=" . DB_SERVER_NOMAD . ";dbname=" . DB_DATABASE_NOMAD,
        DB_SERVER_USERNAME_NOMAD,
        DB_SERVER_PASSWORD_NOMAD,
        $options
    );
} catch (PDOException $e) {
    // Connection failed: return a clear message and stop here.
    $result_info = [
        'erreur'           => true,
        'arret'            => false,
        'process_info'     => TEXT_SYNC_DB_CONNECT_FAIL . "\n" . TEXT_SYNC_DB_CONNECT_RETRY . "\n",
        'process_datetime' => $datetime_now_fr
    ];
    echo json_encode($result_info);
    exit;
}

// -----------------------------------------------------------------------
// ALGORITHME DE SYNCHRONISATION : NOMAD -> SERVEUR

// Counters (initialized here so they are always defined in the final messages)
$nb_agents_modif = 0;
$nb_ra_modif     = 0;
$nb_jge_modif    = 0;

// Disable foreign-key checks to avoid ordering conflicts during inserts
$connLocal->exec("SET FOREIGN_KEY_CHECKS = 0;");

$startTime = microtime(true);

    try {
            // Start BOTH transactions (symmetric: local + online)
            $connOnline->beginTransaction();
            $connLocal->beginTransaction();

            //--
            // Create the sync log rows and capture their IDs on each side
            logSyncOperation($connOnline, TABLE_SYNC_LOGS, $id_user, $datetime_now, 'fromNomad');
            $id_logSync_Online = $connOnline->lastInsertId();

            logSyncOperation($connLocal, TABLE_SYNC_LOGS, $id_user, $datetime_now, 'toServeur');
            $id_logSync_Local = $connLocal->lastInsertId(); // FIXED: read from $connLocal (was $connOnline)


            //========================================================
            // Table Agent
            //========================================================

                // 1. Get the local table column list (excluding the auto 'id')
                $stmt_columns_TabAgent = $connLocal->query("SHOW COLUMNS FROM ".TABLE_AGENT);
                $columns_TabAgent = [];
                while ($row = $stmt_columns_TabAgent->fetch(PDO::FETCH_ASSOC)) 
                {
                    $columns_TabAgent[] = $row['Field'];
                }
                $columns_TabAgent = array_diff($columns_TabAgent, ['id']); // Drop auto-managed columns

                // 2. Build the dynamic INSERT query
                $cols_TabAgent         = implode(', ', $columns_TabAgent);
                $placeholders_TabAgent = implode(', ', array_fill(0, count($columns_TabAgent), '?'));
                $insertQuery_TabAgent  = "INSERT INTO ".TABLE_AGENT." ($cols_TabAgent) VALUES ($placeholders_TabAgent)";

                // Prepare the INSERT once and reuse it for every row
                $stmt_insert        = $connOnline->prepare($insertQuery_TabAgent);
                $stmt_local_update  = $connLocal->prepare("UPDATE ".TABLE_AGENT." SET hp_load=? WHERE id=?");
                $stmt_online_update = $connOnline->prepare("UPDATE ".TABLE_AGENT." SET hp_load=? WHERE id=?");

                // 3. Map of local id => server id, plus variable init
                $mappingIds_TabAgent = [];
                $id_agent_locaux     = [];

                // 4. Fetch the local agents that still need syncing
                $stmt_local = $connLocal->query("SELECT * FROM ".TABLE_AGENT." WHERE from_nomad=1 AND new_nomad=1 AND hp_load<1");

                // 5. Sync the agents
                while ($agent = $stmt_local->fetch(PDO::FETCH_ASSOC))
                {
                    $values = [];
                    foreach ($columns_TabAgent as $col) {$values[] = $agent[$col];}

                    $stmt_insert->execute($values);
                    $mappingIds_TabAgent[$agent['id']] = $connOnline->lastInsertId();

                    // Update hp_load with the sync log id (each side with ITS OWN log id)
                    $stmt_local_update->execute([$id_logSync_Local,  $agent['id']]);
                    $stmt_online_update->execute([$id_logSync_Online, $mappingIds_TabAgent[$agent['id']]]);

                    $nb_agents_modif++;
                }

                if (!empty($mappingIds_TabAgent)) 
                {
                    $id_agent_locaux = array_keys($mappingIds_TabAgent);
                }

            // ---- Cooperative stop checkpoint ----
            if (arretDemande($flagFile)) { $arret = true; throw new Exception('__ARRET_UTILISATEUR__'); }


            //========================================================
            // Table RA
            //========================================================

                // 1. Columns (excluding 'id_ra')
                $stmt_columns_TabRa = $connLocal->query("SHOW COLUMNS FROM ".TABLE_DATA_RA);
                $columns_TabRa = [];
                while ($row = $stmt_columns_TabRa->fetch(PDO::FETCH_ASSOC)) 
                {
                    $columns_TabRa[] = $row['Field'];
                }
                $columns_TabRa = array_diff($columns_TabRa, ['id_ra']);

                // 2. Dynamic INSERT
                $cols_TabRa         = implode(', ', $columns_TabRa);
                $placeholders_TabRa = implode(', ', array_fill(0, count($columns_TabRa), '?'));
                $insertQuery_TabRa  = "INSERT INTO ".TABLE_DATA_RA." ($cols_TabRa) VALUES ($placeholders_TabRa)";

                $stmt_insert        = $connOnline->prepare($insertQuery_TabRa);
                $stmt_local_update  = $connLocal->prepare("UPDATE ".TABLE_DATA_RA." SET hp_load=? WHERE id_ra=?");
                $stmt_online_update = $connOnline->prepare("UPDATE ".TABLE_DATA_RA." SET hp_load=? WHERE id_ra=?");

                // 3. Mapping
                $mappingIds_TabRa  = [];
                $id_ra_locaux      = [];

                // 4. Local activity reports to sync
                $stmt_local = $connLocal->query("SELECT * FROM ".TABLE_DATA_RA." WHERE from_nomad=1 AND new_nomad=1 AND hp_load<1");

                // 5. Sync the activity reports
                while ($ra = $stmt_local->fetch(PDO::FETCH_ASSOC))
                {
                    $values = [];
                    foreach ($columns_TabRa as $col) {$values[] = $ra[$col];}

                    $stmt_insert->execute($values);
                    $mappingIds_TabRa[$ra['id_ra']] = $connOnline->lastInsertId();

                    $stmt_local_update->execute([$id_logSync_Local,  $ra['id_ra']]);
                    $stmt_online_update->execute([$id_logSync_Online, $mappingIds_TabRa[$ra['id_ra']]]);

                    $nb_ra_modif++;
                }

                if (!empty($mappingIds_TabRa)) 
                {
                    $id_ra_locaux = array_keys($mappingIds_TabRa);
                }

            // ---- Cooperative stop checkpoint ----
            if (arretDemande($flagFile)) { $arret = true; throw new Exception('__ARRET_UTILISATEUR__'); }


            //========================================================
            // Table RA_PIEZO_PROFIL
            //========================================================

                if (!empty($id_ra_locaux))
                {
                    // 1. Columns (excluding 'id')
                    $stmt_columns_TabRaPiezoProfil = $connLocal->query("SHOW COLUMNS FROM ".TABLE_DATA_RA_PIEZO_PROFIL);
                    $columns_TabRaPiezoProfil = [];
                    while ($row = $stmt_columns_TabRaPiezoProfil->fetch(PDO::FETCH_ASSOC)) 
                    {
                        $columns_TabRaPiezoProfil[] = $row['Field'];
                    }
                    $columns_TabRaPiezoProfil = array_diff($columns_TabRaPiezoProfil, ['id']);

                    // 2. Dynamic INSERT
                    $cols_TabRaPiezoProfil         = implode(', ', $columns_TabRaPiezoProfil);
                    $placeholders_TabRaPiezoProfil = implode(', ', array_fill(0, count($columns_TabRaPiezoProfil), '?'));
                    $insertQuery_TabRaPiezoProfil  = "INSERT INTO ".TABLE_DATA_RA_PIEZO_PROFIL." ($cols_TabRaPiezoProfil) VALUES ($placeholders_TabRaPiezoProfil)";
                    $stmt_insert = $connOnline->prepare($insertQuery_TabRaPiezoProfil);

                    // 3. Select child rows via a prepared statement (safe IN clause)
                    $ph_in = implode(',', array_fill(0, count($id_ra_locaux), '?'));
                    $stmt_local = $connLocal->prepare("SELECT * FROM ".TABLE_DATA_RA_PIEZO_PROFIL." WHERE id_ra IN ($ph_in)");
                    $stmt_local->execute(array_values($id_ra_locaux));

                    // 4. Sync RA_PIEZO_PROFIL
                    while ($rapiezo = $stmt_local->fetch(PDO::FETCH_ASSOC))
                    {
                        $rapiezo['id_ra'] = $mappingIds_TabRa[$rapiezo['id_ra']]; // remap id_ra to the server id

                        $values = [];
                        foreach ($columns_TabRaPiezoProfil as $col) {$values[] = $rapiezo[$col];}

                        $stmt_insert->execute($values);
                    }
                }

            // ---- Cooperative stop checkpoint ----
            if (arretDemande($flagFile)) { $arret = true; throw new Exception('__ARRET_UTILISATEUR__'); }


            //========================================================
            // Table JGE
            //========================================================

                // 1. Colonnes (hors 'id')
                $stmt_columns_TabJGE = $connLocal->query("SHOW COLUMNS FROM ".TABLE_DATA_JGE);
                $columns_TabJGE = [];
                while ($row = $stmt_columns_TabJGE->fetch(PDO::FETCH_ASSOC)) 
                {
                    $columns_TabJGE[] = $row['Field'];
                }
                $columns_TabJGE = array_diff($columns_TabJGE, ['id']);

                // 2. INSERT dynamique
                $cols_TabJGE         = implode(', ', $columns_TabJGE);
                $placeholders_TabJGE = implode(', ', array_fill(0, count($columns_TabJGE), '?'));
                $insertQuery_TabJGE  = "INSERT INTO ".TABLE_DATA_JGE." ($cols_TabJGE) VALUES ($placeholders_TabJGE)";

                $stmt_insert        = $connOnline->prepare($insertQuery_TabJGE);
                $stmt_local_update  = $connLocal->prepare("UPDATE ".TABLE_DATA_JGE." SET hp_load=? WHERE id=?");
                $stmt_online_update = $connOnline->prepare("UPDATE ".TABLE_DATA_JGE." SET hp_load=? WHERE id=?");

                // 3. Mapping
                $mappingIds_TabJGE = [];
                $id_jge_locaux     = [];

                // 4. Local gaugings to sync
                $stmt_local = $connLocal->query("SELECT * FROM ".TABLE_DATA_JGE." WHERE from_nomad=1 AND new_nomad=1 AND hp_load<1");

                // 5. Sync the gaugings
                while ($jge = $stmt_local->fetch(PDO::FETCH_ASSOC))
                {
                    $values = [];
                    foreach ($columns_TabJGE as $col) {$values[] = $jge[$col];}

                    $stmt_insert->execute($values);
                    $mappingIds_TabJGE[$jge['id']] = $connOnline->lastInsertId();

                    $stmt_local_update->execute([$id_logSync_Local,  $jge['id']]);
                    $stmt_online_update->execute([$id_logSync_Online, $mappingIds_TabJGE[$jge['id']]]);

                    $nb_jge_modif++;
                }

                if (!empty($mappingIds_TabJGE)) 
                {
                    $id_jge_locaux = array_keys($mappingIds_TabJGE);
                }

            // ---- Cooperative stop checkpoint ----
            if (arretDemande($flagFile)) { $arret = true; throw new Exception('__ARRET_UTILISATEUR__'); }


            //========================================================
            // Table JGE_BRAS
            //========================================================

                $id_jgebras_locaux = [];

                if (!empty($id_jge_locaux))
                {
                    // 1. Colonnes (hors 'id')
                    $stmt_columns_TabJGEbras = $connLocal->query("SHOW COLUMNS FROM ".TABLE_DATA_JGE_BRAS);
                    $columns_TabJGEbras = [];
                    while ($row = $stmt_columns_TabJGEbras->fetch(PDO::FETCH_ASSOC)) 
                    {
                        $columns_TabJGEbras[] = $row['Field'];
                    }
                    $columns_TabJGEbras = array_diff($columns_TabJGEbras, ['id']);

                    // 2. INSERT dynamique
                    $cols_TabJGEbras         = implode(', ', $columns_TabJGEbras);
                    $placeholders_TabJGEbras = implode(', ', array_fill(0, count($columns_TabJGEbras), '?'));
                    $insertQuery_TabJGEbras  = "INSERT INTO ".TABLE_DATA_JGE_BRAS." ($cols_TabJGEbras) VALUES ($placeholders_TabJGEbras)";
                    $stmt_insert = $connOnline->prepare($insertQuery_TabJGEbras);

                    // 3. Mapping
                    $mappingIds_TabJGEbras = [];

                    // 4. Local jge_bras rows to sync (safe IN clause)
                    $ph_in = implode(',', array_fill(0, count($id_jge_locaux), '?'));
                    $stmt_local = $connLocal->prepare("SELECT * FROM ".TABLE_DATA_JGE_BRAS." WHERE id_jge IN ($ph_in)");
                    $stmt_local->execute(array_values($id_jge_locaux));

                    // 5. Sync JGE_BRAS
                    while ($jgebras = $stmt_local->fetch(PDO::FETCH_ASSOC))
                    {
                        $jgebras['id_jge'] = $mappingIds_TabJGE[$jgebras['id_jge']]; // remap id_jge to the server id

                        $values = [];
                        foreach ($columns_TabJGEbras as $col) {$values[] = $jgebras[$col];}

                        $stmt_insert->execute($values);
                        $mappingIds_TabJGEbras[$jgebras['id']] = $connOnline->lastInsertId();
                    }

                    if (!empty($mappingIds_TabJGEbras)) 
                    {
                        $id_jgebras_locaux = array_keys($mappingIds_TabJGEbras);
                    }
                }


            //========================================================
            // Table JGE_PTS
            //========================================================

                if (!empty($id_jgebras_locaux))
                {
                    // 1. Colonnes (hors 'id')
                    $stmt_columns_TabJGEpts = $connLocal->query("SHOW COLUMNS FROM ".TABLE_DATA_JGE_PTS);
                    $columns_TabJGEpts = [];
                    while ($row = $stmt_columns_TabJGEpts->fetch(PDO::FETCH_ASSOC)) 
                    {
                        $columns_TabJGEpts[] = $row['Field'];
                    }
                    $columns_TabJGEpts = array_diff($columns_TabJGEpts, ['id']);

                    // 2. INSERT dynamique
                    $cols_TabJGEpts         = implode(', ', $columns_TabJGEpts);
                    $placeholders_TabJGEpts = implode(', ', array_fill(0, count($columns_TabJGEpts), '?'));
                    $insertQuery_TabJGEpts  = "INSERT INTO ".TABLE_DATA_JGE_PTS." ($cols_TabJGEpts) VALUES ($placeholders_TabJGEpts)";
                    $stmt_insert = $connOnline->prepare($insertQuery_TabJGEpts);

                    // 3. Local jge_pts rows to sync (safe IN clause)
                    $ph_in = implode(',', array_fill(0, count($id_jgebras_locaux), '?'));
                    $stmt_local = $connLocal->prepare("SELECT * FROM ".TABLE_DATA_JGE_PTS." WHERE id_bras IN ($ph_in)");
                    $stmt_local->execute(array_values($id_jgebras_locaux));

                    // 4. Sync JGE_PTS
                    while ($jgepts = $stmt_local->fetch(PDO::FETCH_ASSOC))
                    {
                        $jgepts['id_bras'] = $mappingIds_TabJGEbras[$jgepts['id_bras']]; // remap id_bras to the server id

                        $values = [];
                        foreach ($columns_TabJGEpts as $col) {$values[] = $jgepts[$col];}

                        $stmt_insert->execute($values);
                    }
                }


            //========================================================
            // Validation : on commit l'EN LIGNE d'abord, puis le LOCAL.
            // If the online commit succeeds, the local hp_load markings are
            // committed as well. Consistency is guaranteed.
            //========================================================
            $connOnline->commit();
            $connLocal->commit();

            $process_info .= TEXT_SYNC_SUCCESS . "\n\n";
            $process_info .= TEXT_SYNC_UP_NB_AGENTS . $nb_agents_modif . "\n";
            $process_info .= TEXT_SYNC_UP_NB_RA . $nb_ra_modif . "\n";
            $process_info .= TEXT_SYNC_UP_NB_JGE . $nb_jge_modif . "\n";

    } catch (Exception $e) 
    {
        // Roll back BOTH transactions (nothing is left flagged as synchronized)
        if ($connOnline->inTransaction()) { $connOnline->rollback(); }
        if ($connLocal->inTransaction())  { $connLocal->rollback(); }

        if ($e->getMessage() === '__ARRET_UTILISATEUR__')
        {
            // Voluntary stop: this is not an error.
            $arret = true;
            $process_info .= TEXT_SYNC_UP_STOPPED . "\n\n";
            $process_info .= TEXT_SYNC_UP_STOPPED_ROLLBACK . "\n";
            $process_info .= TEXT_SYNC_UP_STOPPED_RETRY . "\n";
        }
        else
        {
            // Real error: clear message + technical detail (useful for support)
            $erreur = true;
            $process_info .= TEXT_SYNC_UP_FAILED . "\n";
            $process_info .= TEXT_SYNC_UP_FAILED_SAFE . "\n\n";
            $process_info .= TEXT_SYNC_TECH_DETAIL . "\n";
            $process_info .= $e->getMessage()."\n";
        }
    }

// Re-enable foreign-key checks
$connLocal->exec("SET FOREIGN_KEY_CHECKS = 1;");

// Clean up the stop flag file in all cases (success, stop or error)
if (file_exists($flagFile)) { @unlink($flagFile); }

$endTime = microtime(true);
$executionTime = number_format($endTime - $startTime, 1);

$process_info .= "-- \n";
$process_info .= TEXT_SYNC_PROCESSING_TIME . $executionTime . " " . TEXT_SYNC_SECONDS_SHORT . "\n";

// ------------------------------

$result_info = [
                    'erreur'           => $erreur,
                    'arret'            => $arret,
                    'process_info'     => $process_info,
                    'process_datetime' => $datetime_now_fr
                ];

echo json_encode($result_info);

?>