<?php
/*  
----------------------------------------
Copyright (c) 2025 - Vai-Natura
----------------------------------------
Toutes les fonctions liées au mise à jour de la base
Synchronisation
*/


// Enregistre une opération de synchronisation dans la table sync_logs
// Compatible 100% PDO

function logSyncOperation($pdo, $dbTable, $idUser, $sync_date, $syncDirection, $message = "") 
{
    $stmt = $pdo->prepare("
        INSERT INTO $dbTable 
        (id_user, sync_date, sync_direction, message)
        VALUES (?, ?, ?, ?)
    ");
    // En PDO, execute() accepte directement le tableau
    $stmt->execute([$idUser, $sync_date, $syncDirection, $message]);
}




?>
