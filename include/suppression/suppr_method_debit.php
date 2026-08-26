<?php
/*  
----------------------------------------
Copyright (c) 2015 - Vai-Natura
----------------------------------------
*/

$method_id = mysqli_real_escape_string($sql_link,trim(addslashes($_GET['id'])));


$sql_del = "SELECT DISTINCT * FROM ".TABLE_DATA_METHOD_DEBIT." WHERE id=".$method_id;
$del_query = tep_db_query($sql_link,$sql_del);
$del_a = tep_db_fetch_array($del_query);


if(tep_not_null($del_a['id']))
{
	if($del_a['modif']==1)
	{
		tep_db_query($sql_link,"DELETE FROM ".TABLE_DATA_METHOD_DEBIT." WHERE id=".$method_id);
		$message_suprr_type = htmlaccent('La Méthode de Mesure de Débit : '.htmlaccent($del_a['method']).', a bien été supprimée.');
	}
	else
	{
		$message_suprr_type = htmlaccent('La Méthode de Mesure de Débit : '.htmlaccent($del_a['method']).', ne peut être supprimée.');	
	}
	
	
}
else
{
	$message_suprr_type = htmlaccent('Cette a Méthode de Mesure de Débit n\'existe pas, il ne peut être supprimé');
}
	


?>
