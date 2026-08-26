<?php
/*  
----------------------------------------
Copyright (c) 2015 - Vai-Natura
----------------------------------------
*/

$helice_id = mysqli_real_escape_string($sql_link,trim(addslashes($_GET['hl'])));


$sql_del = "SELECT DISTINCT * FROM ".TABLE_EQ_HELICE." WHERE id=".$helice_id;
$del_query = tep_db_query($sql_link,$sql_del);
$del_a = tep_db_fetch_array($del_query);

if(tep_not_null($del_a['id']))
{
	$sql_del_hl = "SELECT DISTINCT * FROM ".TABLE_DATA_JAUGEAGE." WHERE id_helice=".$helice_id;
	$del_hl_query = tep_db_query($sql_link,$sql_del_hl);
	$del_hl = tep_db_fetch_array($del_hl_query);
	
	if(!tep_not_null($del_hl['id']))
	{
		tep_db_query($sql_link,"DELETE FROM ".TABLE_EQ_HELICE_TO_EQUATION." WHERE id_helice=".$helice_id);
		tep_db_query($sql_link,"DELETE FROM ".TABLE_EQ_HELICE." WHERE id=".$helice_id);
		
		$message_info = htmlaccent('L\'hélice  n° '.htmlaccent($del_a['num_serie_helice']).' a bien été supprimée.');
	}
	else
	{
		$message_info = htmlaccent('L\'hélice '.htmlaccent($del_a['num_serie_helice']).' ne peut être supprimée car elle a été utilisée pour des jaugeages.');	
	}
}
else
{
	$message_info = htmlaccent('Cette hélice n\'est pas référencée, elle ne peut être supprimée');
}

?>
