<?php
/*  
----------------------------------------
Copyright (c) 2015 - Vai-Natura
----------------------------------------
*/

$pdt_id = mysqli_real_escape_string($sql_link,trim(addslashes($_GET['id'])));


$sql_del = "SELECT DISTINCT * FROM ".TABLE_EXPORT_INTERVAL." WHERE id=".$pdt_id;
$del_query = tep_db_query($sql_link,$sql_del);
$del_a = tep_db_fetch_array($del_query);


if(tep_not_null($del_a['id']))
{
	tep_db_query($sql_link,"DELETE FROM ".TABLE_EXPORT_INTERVAL." WHERE id=".$pdt_id);
	$message_suprr_pdt = htmlaccent('Le Pas de Temps '.htmlaccent($del_a['libelle']).' a bien été supprimé.');
}
else
{
	$message_suprr_pdt = htmlaccent('Le Pas de Temps n\'existe pas, il ne peut être supprimé');
}
	


?>
