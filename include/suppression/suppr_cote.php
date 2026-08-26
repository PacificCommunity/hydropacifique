<?php
/*  
----------------------------------------
Copyright (c) 2015 - Vai-Natura
----------------------------------------
*/

$cote_id = mysqli_real_escape_string($sql_link,trim(addslashes($_GET['cote'])));


$sql_del = "SELECT DISTINCT * FROM ".TABLE_DATA_COTE." WHERE id=".$cote_id;
$del_query = tep_db_query($sql_link,$sql_del);
$del_a = tep_db_fetch_array($del_query);

if(tep_not_null($del_a['id']))
{
	tep_db_query($sql_link,"DELETE FROM ".TABLE_DATA_COTE." WHERE id=".$cote_id);
	
	$message_suprr_tot = htmlaccent('La mesure relevée manuellement datée du '.dateus_fr($del_a['date_cote']).' '.$del_a['heure_cote'].' a bien été supprimée.');
}
else
{
	$message_suprr_tot = htmlaccent('Cette mesure n\'existe pas');
}

?>
