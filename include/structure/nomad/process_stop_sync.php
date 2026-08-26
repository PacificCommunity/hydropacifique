<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
HP-NOMAD
Manages the cooperative stop flag for the synchronization process.
Called via AJAX from sync.php (lives alongside the other process_*.php files).

Actions:
  - 'set'   : raise the flag (the user clicked "Stop")
  - 'clear' : remove the flag (when a new synchronization starts)

The running process (process_tonomad.php / process_toserveur.php) checks this
flag between each table and cleanly rolls back if it is present. This file does
not display any user-facing text; it only returns a JSON status.
----------------------------------------
*/

require('../../config.php');

header('Content-Type: application/json; charset=utf-8');

// Read and decode the JSON payload
$jsonDataInfo = file_get_contents('php://input');
$dataInfo     = json_decode($jsonDataInfo, true);

$action  = isset($dataInfo['action']) ? $dataInfo['action']      : '';
$id_user = isset($dataInfo['idUser']) ? (int)$dataInfo['idUser'] : 0;

// The flag file is per-user, stored in the system temp directory, so two users
// never interfere with each other and nothing is written to the database.
$flagFile = sys_get_temp_dir() . '/vainatura_stop_sync_' . $id_user . '.flag';

$ok = false;

if ($action === 'set') {
    // Raise the flag; the leading @ suppresses warnings on a permission issue
    $ok = (@file_put_contents($flagFile, date('Y-m-d H:i:s')) !== false);
}
elseif ($action === 'clear') {
    // Remove the flag if it exists
    if (file_exists($flagFile)) {
        $ok = @unlink($flagFile);
    } else {
        $ok = true; // nothing to remove = already in the desired state
    }
}

echo json_encode(['ok' => $ok, 'action' => $action]);
?>