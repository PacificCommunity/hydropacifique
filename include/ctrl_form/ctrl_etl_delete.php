<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
*/

$sql_last_ETL = "SELECT id, datetime_first, datetime_end
                FROM ".TABLE_DATA_ETL." WHERE id_station=".$st_id." ORDER BY id DESC LIMIT 1";
$last_ETL_query = tep_db_query($sql_link,$sql_last_ETL);
$last_ETL = tep_db_fetch_array($last_ETL_query);

if(isset($last_ETL['id']))
{
	tep_db_query($sql_link,"DELETE FROM ".TABLE_DATA_ETL_DATA." WHERE id_etl=".$last_ETL['id']);
    tep_db_query($sql_link,"DELETE FROM ".TABLE_DATA_ETL." WHERE id=".$last_ETL['id']);

	$message_delete_ETL = htmlaccent('Le dernier ETL a bien été supprimé.');
}
else
{
	$message_delete_ETL = htmlaccent('ETL non identifié, la suppression a écouchée.');
}


?>
