<?php
/*  
----------------------------------------
Copyright (c) 2015 - Vai-Natura
----------------------------------------
*/
$sql_del = "SELECT DISTINCT * FROM ".TABLE_DATA_JAUGEAGE." WHERE id=".$ref_id;
$del_query = tep_db_query($sql_link,$sql_del);
$del_a = tep_db_fetch_array($del_query);

if(tep_not_null($del_a['id']))
{	
	tep_db_query($sql_link,"DELETE FROM ".TABLE_DATA_JAUGEAGE_DATA." WHERE id_jaugeage=".$ref_id);
	$message_info = htmlaccent('Les données du jaugeage ont bien été effacées.');
}
else
{
	$message_info = htmlaccent('Ce jaugeage n\'existe pas, les données ne peuvent être effacées.');
}

?>
