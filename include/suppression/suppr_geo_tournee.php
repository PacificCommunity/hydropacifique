<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Suppression des lignes Tournées
*/


// Région Hydrologiques

// Récupération de la variable indiquant l'identifiant à supprimer
$id_tr = mysqli_real_escape_string($sql_link,trim(addslashes($_GET['id_tr'])));


$sql_del_tr = "SELECT DISTINCT id, nom FROM ".TABLE_TOURNEE." WHERE id=".$id_tr;
$del_query = tep_db_query($sql_link,$sql_del_tr);
$del_a = tep_db_fetch_array($del_query);


if(isset($del_a['id']) && tep_not_null($del_a['id']))
{
    // On vérifie si la tournée est liée à une station hydrologique
	$sql_station_tr = "SELECT EXISTS (
										SELECT 1 FROM ".TABLE_STATION." WHERE id_tournee=".$id_tr." LIMIT 1
									 ) AS station_exists";
	$station_tr_query = tep_db_query($sql_link,$sql_station_tr);
	$del_station_tr = tep_db_fetch_array($station_tr_query);
	
	if($del_station_tr['station_exists'] == 1)	// Si la région est liée à une station on ne peut pas supprimée la tournée
    {
        $message_suprr_geo = htmlaccent('La Tournée - '.htmlaccent($del_a['nom']).' - ne peut pas être supprimée car elle est liée à au moins une station de mesure.');
    }
    else // Si la tournée n'est liée à aucune station
    {
		tep_db_query($sql_link,"DELETE FROM ".TABLE_TOURNEE." WHERE id=".$id_tr);
		$message_suprr_geo = htmlaccent('La Tournée - '.htmlaccent($del_a['nom']).' - a bien été supprimée.');
	}
}
else
{
	$message_suprr_geo = htmlaccent('La Tournée n\'existe pas, elle ne peut pas être supprimée.');
}
	


?>
