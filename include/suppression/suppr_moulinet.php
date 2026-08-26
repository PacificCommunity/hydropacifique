<?php
/*  
----------------------------------------
Copyright (c) 2015 - Vai-Natura
----------------------------------------
*/

$moulinet_id = mysqli_real_escape_string($sql_link,trim(addslashes($_GET['id'])));


$sql_del = "SELECT DISTINCT * FROM ".TABLE_EQ_MOULINET." WHERE id=".$moulinet_id;
$del_query = tep_db_query($sql_link,$sql_del);
$del_a = tep_db_fetch_array($del_query);

if(tep_not_null($del_a['id']))
{
	$sql_del_ml = "SELECT DISTINCT * FROM ".TABLE_EQ_HELICE." WHERE id_moulinet=".$moulinet_id;
	$del_ml_query = tep_db_query($sql_link,$sql_del_ml);
	$del_ml = tep_db_fetch_array($del_ml_query);
	
	if(!tep_not_null($del_ml['id']))
	{
		tep_db_query($sql_link,"DELETE FROM ".TABLE_EQ_MOULINET." WHERE id=".$moulinet_id);
		
		$message_info = htmlaccent('Le moulinet n° '.htmlaccent($del_a['num_serie']).' a bien été supprimé.');
	}
	else
	{
		$message_info = htmlaccent('Le moulinet n° '.htmlaccent($del_a['num_serie']).' ne peut être supprimé car une ou plusieurs hélices sont liées au moulinet.');	
		$message_info .= '<br>'.htmlaccent('Il faut tout d\'abord supprimer les hélices ');	
	}
}
else
{
	$message_info = htmlaccent('Ce matériel n\'est pas référencé, il ne peut être supprimé');
}

?>
