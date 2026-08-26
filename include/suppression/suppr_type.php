<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Suppression des lignes Zones géographiques (Régions Hydrologiques - Tournées)
*/


// Région Hydrologiques

$id_rh = mysqli_real_escape_string($sql_link,trim(addslashes($_GET['id_rh'])));


$sql_del_rh = "SELECT DISTINCT * FROM ".TABLE_EQ_TYPE." WHERE id=".$type_id;
$del_query = tep_db_query($sql_link,$sql_del);
$del_a = tep_db_fetch_array($del_query);


if(tep_not_null($del_a['id']))
{
	$sql_del_eq = "SELECT DISTINCT * FROM ".TABLE_EQUIPEMENT." WHERE type_eq=".$type_id;
	$del_eq_query = tep_db_query($sql_link,$sql_del_eq);
	$del_eq = tep_db_fetch_array($del_eq_query);
	
	if(!tep_not_null($del_eq['id']))
	{
		tep_db_query($sql_link,"DELETE FROM ".TABLE_EQ_TYPE." WHERE id=".$type_id);
		$message_suprr_type = htmlaccent('Le Type de Matériel '.htmlaccent($del_a['designation']).' a bien été supprimé.');
	}
	else
	{
		$message_suprr_type = htmlaccent('Le Type de Matériel '.htmlaccent($del_a['designation']).' ne peut être supprimé car il est liée à au moins un équipement.');	
	}
	
	
}
else
{
	$message_suprr_type = htmlaccent('Le Type de Matériel n\'existe pas, il ne peut être supprimé');
}
	


?>
