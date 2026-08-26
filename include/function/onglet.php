<?php
/*  
----------------------------------------
Copyright (c) 2015 - Vai-Natura
----------------------------------------
*/
function textContenu($lang_id)
{
	$sql = "SELECT * FROM ".TABLE_TEXT." t,".TABLE_TEXT_CONTENT." tc ".
	       "WHERE t.id=tc.text_id AND tc.langue_id=".$lang_id;
	
	$text_contenu_query = tep_db_query($sql_link,$sql);      	
    while ($text_contenu = tep_db_fetch_array($text_contenu_query))
	{
		define($text_contenu['name_var'],$text_contenu['text']);
	}
}

?>
