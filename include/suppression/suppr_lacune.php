<?php
/*  
----------------------------------------
Copyright (c) 2015 - Vai-Natura
----------------------------------------
*/

$lac_id = mysqli_real_escape_string($sql_link,trim(addslashes($_GET['lac'])));


$sql_del = "SELECT DISTINCT * FROM ".TABLE_DATA_LACUNE." WHERE id=".$lac_id;
$del_query = tep_db_query($sql_link,$sql_del);
$del_a = tep_db_fetch_array($del_query);

if(tep_not_null($del_a['id']))
{
	tep_db_query($sql_link,"UPDATE ".TABLE_DATA_PLUVIO." h SET h.qte_lacune=h.qte, h.lacune=0 WHERE file_data='".$file_data."' AND lacune=1 AND date_heure_mesure>='".$del_a['date_deb_lacune']." ".$del_a['heure_deb_lacune']."' AND date_heure_mesure<='".$del_a['date_fin_lacune']." ".$del_a['heure_fin_lacune']."'");
	
	//supprimer les ajouts "artificielle"
	tep_db_query($sql_link,"DELETE FROM ".TABLE_DATA_PLUVIO." WHERE file_data='".$file_data."' AND qte='0'");
	
	tep_db_query($sql_link,"DELETE FROM ".TABLE_DATA_LACUNE." WHERE id=".$lac_id);
	
	
	$message_suprr_lacune = htmlaccent('La lacune entre '.$del_a['date_deb_lacune'].' '.$del_a['heure_deb_lacune'].' et '.$del_a['date_fin_lacune'].' '.$del_a['heure_fin_lacune'].' a bien été supprimée.');
}
else
{
	$message_suprr_lacune = htmlaccent('Cette lacune n\'existe pas');
}

?>
