<?php
/*  
----------------------------------------
Copyright (c) 2015 - Vai-Natura
----------------------------------------
*/

$tot_id = mysqli_real_escape_string($sql_link,trim(addslashes($_GET['tot'])));


$sql_del = "SELECT DISTINCT * FROM ".TABLE_DATA_TOTALISATEUR." WHERE id=".$tot_id;
$del_query = tep_db_query($sql_link,$sql_del);
$del_a = tep_db_fetch_array($del_query);

if(tep_not_null($del_a['id']))
{
	tep_db_query($sql_link,"DELETE FROM ".TABLE_DATA_TOTALISATEUR." WHERE id=".$tot_id);
	
	$message_suprr_tot = htmlaccent('La donnée du totalisateur datée du '.dateus_fr($del_a['date_deb_tot']).' '.$del_a['heure_deb_tot'].' a bien été supprimée.');
}
else
{
	$message_suprr_tot = htmlaccent('Cette donnée du totalisateur n\'existe pas');
}

?>
