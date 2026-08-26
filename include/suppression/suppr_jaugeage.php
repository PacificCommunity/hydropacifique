<?php
/*  
----------------------------------------
Copyright (c) 2015 - Vai-Natura
----------------------------------------
*/

$del = mysqli_real_escape_string($sql_link,trim(addslashes($_GET['del'])));


echo $sql_del = "SELECT DISTINCT id FROM ".TABLE_DATA_JGE." WHERE id=".$del;
$del_query = tep_db_query($sql_link,$sql_del);
$del_a = tep_db_fetch_array($del_query);

if(isset($del_a) && tep_not_null($del_a['id']))
{
	tep_db_query($sql_link,"DELETE FROM ".TABLE_DATA_JGE." WHERE id=".$del);
	$message_info = htmlaccent('La mesure de débit du '.dateus_fr($del_a['date_jaugeage']).' a bien été supprimée.');
	
	//tep_db_query($sql_link,"DELETE FROM ".TABLE_DATA_JGE." WHERE id_jaugeage=".$del);
}
else
{
	$message_info = htmlaccent('Cette mesure de débit n\'existe pas, elle ne peut être supprimée');
}

?>
