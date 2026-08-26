<?php
/*  
----------------------------------------
Copyright (c) 2015 - Vai-Natura
----------------------------------------
*/

$id_station = mysqli_real_escape_string($sql_link,trim(addslashes($_GET['del'])));


$sql_del = "SELECT DISTINCT t.id, s.nom_station FROM ".TABLE_DATA_TARAGE." t, ".TABLE_STATION." s WHERE t.id_station=s.id AND t.id_station=".$id_station;
$del_query = tep_db_query($sql_link,$sql_del);
$del_a = tep_db_fetch_array($del_query);

if(tep_not_null($del_a['id']))
{
	// DELETE DATA JAUGEAGE
	$sql_del_j = "SELECT DISTINCT * FROM ".TABLE_DATA_JAUGEAGE." WHERE source_data=2 AND id_station=".$id_station;
	$del_j_query = tep_db_query($sql_link,$sql_del_j);
	while($del_j = tep_db_fetch_array($del_j_query))
	{
		tep_db_query($sql_link,"DELETE FROM ".TABLE_DATA_JAUGEAGE_DATA." WHERE id_jaugeage=".$del_j['id']);
	}
	
	// DELETE JAUGEAGE
	tep_db_query($sql_link,"DELETE FROM ".TABLE_DATA_JAUGEAGE." WHERE id_station=".$id_station);
	// DELETE TARAGE
	tep_db_query($sql_link,"DELETE FROM ".TABLE_DATA_TARAGE." WHERE id_station=".$id_station);
	
	
	$message_info = htmlaccent('Les données de tarage de la station '.htmlaccent($del_a['nom_station']).' ont bien été supprimées.');
	
}
else
{
	$message_info = htmlaccent('Ces données n\'existent pas, elles ne peuvent être supprimée');
}

?>
