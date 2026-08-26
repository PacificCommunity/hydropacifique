<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Enregistrement des données Géographique - Regions hydros et Tournées
*/
$error = 0;
$error_0 = 0;

		
/*  
----------------------------------------
ENREGISTREMENT DS LES BASES 
Gestion des tournées
----------------------------------------
*/

// On récupère l'id de la tournée en cours 
if(isset($_POST['select_tournee_maj']))
{
    $id_tournee_encours = $_POST['select_tournee_maj'];

    if(isset($_POST['target_station_ref'])) // Contenu des stations sélectionnées
    {
        tep_db_query($sql_link,"DELETE FROM station_to_tournee WHERE id_tournee=".$id_tournee_encours); 

        $target_station_tab = $_POST['target_station_ref'];

        // Parcourir le tableau avec foreach
        foreach ($target_station_tab as $id_station) 
        {
            tep_db_query($sql_link,"INSERT INTO ".TABLE_STATION_TO_TOURNEE." (id_station,id_tournee) 
													VALUES ('".$id_station."','".$id_tournee_encours."')");	
        }

        $msg_info = htmlaccent('La tournée - '.$tournee_maj_array[$id_tournee_encours]['nom'].' - a bien été mise à jour');
    }
}


?>
