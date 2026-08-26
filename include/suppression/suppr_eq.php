<?php
/*  
----------------------------------------
Copyright (c) 2015 - Vai-Natura
----------------------------------------
*/

$eq_id = mysqli_real_escape_string($sql_link,trim(addslashes($_GET['id'])));


$sql_del = "SELECT DISTINCT * FROM ".TABLE_EQUIPEMENT." WHERE id=".$eq_id;
$del_query = tep_db_query($sql_link,$sql_del);
$del_a = tep_db_fetch_array($del_query);

if(tep_not_null($del_a['id']))
{
	$sql_del_sta = "SELECT DISTINCT * FROM ".TABLE_STATION_TO_EQUIPEMENT." WHERE id_eq=".$eq_id;
	$del_sta_query = tep_db_query($sql_link,$sql_del_sta);
	$del_sta = tep_db_fetch_array($del_sta_query);
	
	if(!tep_not_null($del_sta['id']))
	{
		tep_db_query($sql_link,"DELETE FROM ".TABLE_EQUIPEMENT." WHERE id=".$eq_id);
		$message_suprr_eq = htmlaccent('Le matériel '.htmlaccent($del_a['designation']).' a bien été supprimé.');
	}
	else
	{
		$message_suprr_eq = htmlaccent('Le matériel '.htmlaccent($del_a['designation']).' ne peut être supprimé car il est utilisé et référencé pour au moins une station.');	
	}
}
else
{
	$message_suprr_eq = htmlaccent('Ce matériel n\'est pas référencé, il ne peut être supprimé');
}

?>
