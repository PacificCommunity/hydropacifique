<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Script de suppression d'une station
A améliorer
*/

$sta_id = mysqli_real_escape_string($sql_link,trim(addslashes($_GET['del'])));


$sql_del = "SELECT DISTINCT id_station, station_type, nom_station FROM ".TABLE_STATION." WHERE id_station=".$sta_id;
$del_query = tep_db_query($sql_link,$sql_del);
$del_a = tep_db_fetch_array($del_query);

if(isset($del_a['id_station']))
{
	$sql_del_meta = "SELECT DISTINCT id FROM ".TABLE_DATA_META." WHERE id_station=".$sta_id." LIMIT 1";
	$del_meta_query = tep_db_query($sql_link,$sql_del_meta);
	$del_meta = tep_db_fetch_array($del_meta_query);

	$sql_del_ra = "SELECT DISTINCT id_ra FROM ".TABLE_DATA_RA." WHERE id_station=".$sta_id." LIMIT 1";
	$del_ra_query = tep_db_query($sql_link,$sql_del_ra);
	$del_ra = tep_db_fetch_array($del_ra_query);
	
	if(!isset($del_meta['id']) && !isset($del_ra['id_ra']))
	{
		tep_db_query($sql_link,"DELETE FROM ".TABLE_STATION." WHERE id_station=".$sta_id);
		tep_db_query($sql_link,"DELETE FROM ".TABLE_STATION_PHOTOS." WHERE id_station=".$sta_id);
		tep_db_query($sql_link,"DELETE FROM ".TABLE_STATION_PIEZO_REPERE." WHERE id_station=".$sta_id);
		tep_db_query($sql_link,"DELETE FROM ".TABLE_STATION_PIEZO_CARACTERISTIQUE." WHERE id_station=".$sta_id);
		
		$message_suprr_station = htmlaccent('La station '.htmlaccent($del_a['nom_station']).' a bien été supprimée.');
	}
	else
	{
		$message_suprr_station = htmlaccent('La station '.htmlaccent($del_a['nom_station']).' ne peut être supprimée car elle contient des enregistrements.');	
	}
}
else
{
	$message_suprr_station = htmlaccent('Cette station n\'existe pas, elle ne peut être supprimée');
}

?>
