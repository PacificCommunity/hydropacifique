<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
HP-NOMAD
Loads data from the remote server into the local Nomad database.
AJAX endpoint on the server, called from sync.php.
----------------------------------------
REVISION NOTES:
  - Speed optimization: TRUNCATE (with automatic fallback to DELETE when not
    allowed), larger insert batches, an unbuffered source query (low memory),
    and a full-batch INSERT prepared only once per table.
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
$arret    = false;
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

// Stop flag file (must match the path built in process_stop_sync.php)
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
    // Unbuffered source query: rows are streamed one by one instead of being
    // fully loaded into memory — keeps RAM usage low on large tables.
    $optionsOnline = $options;
    $connOnline = new PDO(
        "mysql:host=" . DB_SERVER_NOMAD . ";dbname=" . DB_DATABASE_NOMAD,
        DB_SERVER_USERNAME_NOMAD,
        DB_SERVER_PASSWORD_NOMAD,
        $optionsOnline
    );
    $connOnline->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
} catch (PDOException $e) {
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
// ALGORITHME DE SYNCHRONISATION : SERVEUR -> NOMAD
//
// To stay within the PHP execution-time limit, the heavy tables are filtered
// down to the ACTIVE stations only (station.active_station = 1) and everything
// that hangs off them. Reference/lookup tables are still loaded in full.
//
// Each table is described by a "mode":
//   'full'    -> loaded entirely (reference / lookup tables)
//   'station' -> filtered with: WHERE id_station IN (active station ids)
//   'cascade' -> filtered with: WHERE <fk> IN (ids kept from the parent table)
//
// For 'cascade', 'parent' is the table whose kept ids we filter on, and 'fk' is
// the foreign-key column in THIS table. Tables that are parents of a cascade
// declare a 'pk' so we can collect their kept ids while inserting them.
// Order matters: a parent must appear before its children.

$tables_config = [
    // --- Reference / lookup tables: loaded in full ---
    TABLE_AUTORISATION                  => ['mode' => 'full'],
    TABLE_SERVICE                       => ['mode' => 'full'],
    TABLE_USER                          => ['mode' => 'full'],
    TABLE_USER_ACCES                    => ['mode' => 'full'],
    TABLE_USER_MENU                     => ['mode' => 'full'],
    TABLE_AGENT                         => ['mode' => 'full'],
    TABLE_DATA_JGE_FONDLIT              => ['mode' => 'full'],
    TABLE_DATA_JGE_METHODE              => ['mode' => 'full'],
    TABLE_DATA_JGE_SITE                 => ['mode' => 'full'],
    TABLE_DATA_JGE_TYPE                 => ['mode' => 'full'],
    TABLE_DATA_QUALITE                  => ['mode' => 'full'],
    TABLE_TYPE_DATA                     => ['mode' => 'full'],
    TABLE_DATA_TYPE_AXE                 => ['mode' => 'full'],
    TABLE_HELICE                        => ['mode' => 'full'],
    TABLE_MOULINET                      => ['mode' => 'full'],
    TABLE_SAUMON                        => ['mode' => 'full'],
    TABLE_EQ_TYPE                       => ['mode' => 'full'],
    TABLE_GEO_AQUIFERE                  => ['mode' => 'full'],
    TABLE_COMMUNE                       => ['mode' => 'full'],
    TABLE_REGION                        => ['mode' => 'full'],
    TABLE_REGIONHYDRO                   => ['mode' => 'full'],
    TABLE_RIVIERE                       => ['mode' => 'full'],
    TABLE_TERRITOIRE                    => ['mode' => 'full'],
    TABLE_TOURNEE                       => ['mode' => 'full'],
    TABLE_IMPORT_FILES                  => ['mode' => 'full'],
    TABLE_OPTION_PASTEMPS               => ['mode' => 'full'],
    TABLE_TOURNEE_PERIODE               => ['mode' => 'full'],
    // These two have no station link in the current schema: copy them whole.
    TABLE_STATION_NATURE                => ['mode' => 'full'],
    TABLE_STATION_SCHEMA_TO_NATURE      => ['mode' => 'full'],
    TABLE_STATION_PIEZO_SCHEMA          => ['mode' => 'full'],
    TABLE_STATION_PROPRIO               => ['mode' => 'full'],

    // --- Station itself: filtered to active stations (and collect ids) ---
    TABLE_STATION                       => ['mode' => 'station_pivot', 'pk' => 'id_station'],

    // --- Tables with a direct id_station: filtered on active stations ---
    TABLE_STATION_ACCESS                => ['mode' => 'station'],
    TABLE_STATION_PHOTOS                => ['mode' => 'station'],
    TABLE_STATION_PIEZO_CARACTERISTIQUE => ['mode' => 'station'],
    TABLE_STATION_PIEZO_REPERE          => ['mode' => 'station'],
    TABLE_STATION_TO_TOURNEE            => ['mode' => 'station'],

    // --- Heavy data tables with a direct id_station (collect ids for children) ---
    TABLE_DATA_ETL                      => ['mode' => 'station', 'pk' => 'id'],
    TABLE_DATA_RA                       => ['mode' => 'station', 'pk' => 'id_ra'],
    TABLE_DATA_JGE                      => ['mode' => 'station', 'pk' => 'id'],

    // --- Children filtered in cascade from the parents above ---
    // Note: data_etl_data.id_etl references data_etl.id (PK is "id", not "id_etl")
    TABLE_DATA_ETL_DATA                 => ['mode' => 'cascade', 'parent' => TABLE_DATA_ETL, 'fk' => 'id_etl'],
    TABLE_DATA_RA_PIEZO_PROFIL          => ['mode' => 'cascade', 'parent' => TABLE_DATA_RA,  'fk' => 'id_ra'],
    TABLE_DATA_JGE_BRAS                 => ['mode' => 'cascade', 'parent' => TABLE_DATA_JGE,  'fk' => 'id_jge', 'pk' => 'id'],
    TABLE_DATA_JGE_PTS                  => ['mode' => 'cascade', 'parent' => TABLE_DATA_JGE_BRAS, 'fk' => 'id_bras'],
];

// Holds the kept primary-key values per table, used to filter child tables.
// e.g. $keptIds[TABLE_DATA_JGE] = [12, 13, 17, ...]
$keptIds = [];

// Empties a table: TRUNCATE first (fast, resets the table at once);
// falls back to DELETE if TRUNCATE is not permitted (e.g. missing DROP privilege).
function videTable($conn, $table) {
    // DELETE (et non TRUNCATE) : TRUNCATE force un COMMIT implicite et casse
    // la transaction globale. DELETE reste transactionnel donc annulable.
    $conn->exec("DELETE FROM `$table`");
}

// Returns true if $table has a column named $column on the given connection.
// Used to stay robust against schema surprises: a table declared as
// station-filtered but actually missing id_station is copied whole instead
// of crashing the whole download.
// Returns true if $table has a column named $column on the given connection.
function tableHasColumn($conn, $table, $column) {
    try {
        $stmt = $conn->prepare(
            "SELECT COUNT(*) 
             FROM information_schema.COLUMNS 
             WHERE TABLE_SCHEMA = DATABASE() 
               AND TABLE_NAME = ? 
               AND COLUMN_NAME = ?"
        );
        $stmt->execute([$table, $column]);
        return ((int) $stmt->fetchColumn()) > 0;
    } catch (PDOException $e) {
        return false;
    }
}
// Disable foreign-key checks to avoid ordering conflicts during the reload
$connLocal->exec("SET FOREIGN_KEY_CHECKS = 0;");

// Adaptive batch sizing.
// A multi-row INSERT must stay within two limits:
//   - the prepared-statement placeholder cap (~65535 parameters per query)
//   - max_allowed_packet (a too-large query makes the server drop the
//     connection: "MySQL server has gone away", error 2006)
// So instead of a fixed number of rows, we cap the number of PLACEHOLDERS
// per batch and derive the row count from the table's column count.
// Wide tables therefore get fewer rows per batch, narrow tables get more.
$maxPlaceholdersPerBatch = 2000; // safe margin below the 65535 hard limit
$maxRowsPerBatch         = 100;   // upper bound for narrow tables
$minRowsPerBatch         = 20;    // lower bound for very wide tables

$startTime = microtime(true);

    // Start a single local transaction wrapping the whole reload
    $connLocal->beginTransaction();

    try {

            // 1) Load the list of ACTIVE station ids once, up front.
            //    Everything station-scoped is filtered against this list.
            $activeStationIds = [];
            $stmtActive = $connOnline->query("SELECT id_station FROM `".TABLE_STATION."` WHERE active_station = 1");
            while ($r = $stmtActive->fetch(PDO::FETCH_ASSOC)) {
                $activeStationIds[] = $r['id_station'];
            }
            $stmtActive->closeCursor();
            $stmtActive = null;

            // Helper: builds a safe "IN (?, ?, ...)" clause and returns [clause, params].
            // Returns [null, []] when the id list is empty (caller then skips the table).
            $buildInClause = function(array $ids) {
                if (empty($ids)) { return [null, []]; }
                $ph = implode(',', array_fill(0, count($ids), '?'));
                return ["($ph)", array_values($ids)];
            };

            foreach ($tables_config as $table => $cfg) 
            {
                // ---- Cooperative stop checkpoint (between each table) ----
                if (arretDemande($flagFile)) { $arret = true; throw new Exception('__ARRET_UTILISATEUR__'); }

                $mode = $cfg['mode'];

                // ---- Build the source SELECT according to the table mode ----
                $params = [];
                if ($mode === 'full')
                {
                    // Reference table: load everything
                    $stmtOnline = $connOnline->query("SELECT * FROM `$table`");
                }
                elseif ($mode === 'station' || $mode === 'station_pivot')
                {
                    // Robustness: only filter if the column actually exists.
                    // If a table declared as station-filtered has no id_station,
                    // copy it whole instead of crashing the whole download.
                    if (!tableHasColumn($connOnline, $table, 'id_station'))
                    {
                        $stmtOnline = $connOnline->query("SELECT * FROM `$table`");
                        $process_info .= "Note: `$table` has no id_station column, copied in full.\n";
                    }
                    else
                    {
                        // Filter on active stations via the id_station column
                        list($inClause, $params) = $buildInClause($activeStationIds);
                        if ($inClause === null) {
                            // No active station at all: nothing to load for this table
                            videTable($connLocal, $table);
                            continue;
                        }
                        $stmtOnline = $connOnline->prepare("SELECT * FROM `$table` WHERE `id_station` IN $inClause");
                        $stmtOnline->execute($params);
                    }
                }
                else // 'cascade'
                {
                    // Robustness: only filter if the FK column actually exists.
                    if (!tableHasColumn($connOnline, $table, $cfg['fk']))
                    {
                        $stmtOnline = $connOnline->query("SELECT * FROM `$table`");
                        $process_info .= "Note: `$table` has no `".$cfg['fk']."` column, copied in full.\n";
                    }
                    else
                    {
                        // Filter on the ids kept from the parent table
                        $parent     = $cfg['parent'];
                        $parentIds  = isset($keptIds[$parent]) ? $keptIds[$parent] : [];
                        list($inClause, $params) = $buildInClause($parentIds);
                        if ($inClause === null) {
                            // Parent kept no row: this child has nothing to load
                            videTable($connLocal, $table);
                            continue;
                        }
                        $stmtOnline = $connOnline->prepare("SELECT * FROM `$table` WHERE `".$cfg['fk']."` IN $inClause");
                        $stmtOnline->execute($params);
                    }
                }

                // Empty the local table (fast TRUNCATE, or DELETE fallback)
                videTable($connLocal, $table);

                // If this table is a parent of a cascade, remember which PK to collect
                $collectPk = isset($cfg['pk']) ? $cfg['pk'] : null;
                if ($collectPk !== null) { $keptIds[$table] = []; }

                $columns          = "";
                $placeholdersRow  = "";
                $rowsToInsert     = [];
                $allPlaceholders  = [];
                $batchCount       = 0;
                $batchSize        = $maxRowsPerBatch; // recomputed once columns are known
                $stmtFull         = null;  // INSERT prepared for a FULL batch (reused across batches)

                while ($row = $stmtOnline->fetch(PDO::FETCH_ASSOC)) 
                {
                    if (empty($columns)) 
                    {
                        $colCount        = count($row);
                        $columns         = "`" . implode("`, `", array_keys($row)) . "`";
                        $placeholdersRow = "(" . implode(',', array_fill(0, $colCount, '?')) . ")";

                        // Rows per batch = placeholder budget / columns, clamped to [min, max].
                        // Example: 20000 / 150 cols -> 133 rows; 20000 / 10 cols -> 500 (capped).
                        $batchSize = (int) floor($maxPlaceholdersPerBatch / max($colCount, 1));
                        if ($batchSize > $maxRowsPerBatch) { $batchSize = $maxRowsPerBatch; }
                        if ($batchSize < $minRowsPerBatch) { $batchSize = $minRowsPerBatch; }
                    }

                    // Collect this row's PK for later cascade filtering of children
                    if ($collectPk !== null && isset($row[$collectPk])) {
                        $keptIds[$table][] = $row[$collectPk];
                    }

                    foreach ($row as $value) { $rowsToInsert[] = $value; }
                    $allPlaceholders[] = $placeholdersRow;
                    $batchCount++;

                    // Batch is full: flush it with one multi-row INSERT
                    if ($batchCount >= $batchSize) 
                    {
                        // A full batch INSERT always has the same shape, so prepare it only once and reuse it
                        if ($stmtFull === null) {
                            $sqlFull  = "INSERT INTO `$table` ($columns) VALUES " . implode(',', $allPlaceholders);
                            $stmtFull = $connLocal->prepare($sqlFull);
                        }
                        $stmtFull->execute($rowsToInsert);

                        $rowsToInsert    = [];
                        $allPlaceholders = [];
                        $batchCount      = 0;
                    }
                }

                // Insert the remainder (final rows that did not fill a full batch)
                if (!empty($allPlaceholders)) 
                {
                    $sql  = "INSERT INTO `$table` ($columns) VALUES " . implode(',', $allPlaceholders);
                    $stmt = $connLocal->prepare($sql);
                    $stmt->execute($rowsToInsert);
                    $stmt = null;
                }

                // Free memory before moving on to the next table
                $stmtOnline->closeCursor();
                $stmtOnline = null;
                $stmtFull   = null;
                unset($rowsToInsert, $allPlaceholders);
            }

            // Everything succeeded: commit the transaction
            $connLocal->commit();
            $process_info .= TEXT_SYNC_SUCCESS . "\n";

            logSyncOperation($connLocal, TABLE_SYNC_LOGS, $id_user, $datetime_now, 'download');

    } catch (Exception $e) 
    {
        if ($connLocal->inTransaction()) {
             try {
                    if ($connLocal->inTransaction()) {
                        $connLocal->rollback();
                    }
                } catch (Exception $eRollback) {
                    // le rollback peut échouer si la transaction a déjà sauté : on ignore
                }
        }

        if ($e->getMessage() === '__ARRET_UTILISATEUR__')
        {
            $arret = true;
            $process_info .= TEXT_SYNC_DL_STOPPED . "\n\n";
            $process_info .= TEXT_SYNC_DL_STOPPED_ROLLBACK . "\n";
            $process_info .= TEXT_SYNC_DL_STOPPED_RETRY . "\n";
        }
        else
        {
            $erreur = true;
            $process_info .= TEXT_SYNC_DL_FAILED . "\n";
            $process_info .= TEXT_SYNC_DL_FAILED_SAFE . "\n\n";

            if (strpos($e->getMessage(), 'gone away') !== false
                || strpos($e->getMessage(), '2006') !== false)
            {
                $process_info .= TEXT_SYNC_DL_CONNECTION_LOST . "\n\n";
            }

            $process_info .= TEXT_SYNC_TECH_DETAIL . "\n";
            $process_info .= "MESSAGE : " . $e->getMessage() . "\n";
            $process_info .= "FICHIER : " . $e->getFile() . "\n";
            $process_info .= "LIGNE   : " . $e->getLine() . "\n";
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