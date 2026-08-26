<?php
/*  
----------------------------------------
Copyright (c) 2015 - Vai-Natura
----------------------------------------
*/

$nb_data_del = 0;

$sql_del = "SELECT DISTINCT * FROM ".TABLE_DATA_JAUGEAGE." WHERE id_station=".$select_station;
$del_query = tep_db_query($sql_link,$sql_del);
while($del_a = tep_db_fetch_array($del_query))
{
	if(isset($_POST['check_del_'.$del_a['id']]))
	{
		tep_db_query($sql_link,"DELETE FROM ".TABLE_DATA_JAUGEAGE_DATA." WHERE id_jaugeage=".$del_a['id']);
		tep_db_query($sql_link,"DELETE FROM ".TABLE_DATA_JAUGEAGE." WHERE id=".$del_a['id']);
	
		$nb_data_del++;
	}

	$message_info = htmlaccent('Les données sélectionnées ont bien été supprimées.');
}

if($nb_data_del==0){$message_info = htmlaccent('Aucune donnée n\'a été trouvée');}


?>
