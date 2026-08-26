<?php
/*  
----------------------------------------
Copyright (c) 2015 - Vai-Natura
----------------------------------------
*/

function tep_string_to_int($string) 
{return (int)$string;}


/* correction et verification de la variable cPath */
function tep_parse_category_path($cPath) 
{
 	//pour être sûr que cPath est un int
    	$cPath_array = array_map('tep_string_to_int', explode('_', $cPath));
	
    	$tmp_array = array();
    	$n = sizeof($cPath_array);
    	for ($i=0; $i<$n; $i++) 
    	{
      		if (!in_array($cPath_array[$i], $tmp_array)) 
      		{
        		$tmp_array[] = $cPath_array[$i];
      		}
    	}

    	return $tmp_array;
}

function tep_show_rubriques($counter) 
{
  	global $tree, $rubriques_string, $cPath_array;
  
  
  	for ($i=0; $i<$tree[$counter]['level']; $i++) 
  	{
    		$rubriques_string .= "&nbsp;&nbsp;&nbsp;&nbsp;";
  	}

  	$rubriques_string .= "<a href='";

  	if ($tree[$counter]['parent'] == 0) 
  	{
    		$cPath_new = 'cPath=' . $counter;
  	} 
  	else 
  	{
    		$cPath_new = 'cPath=' . $tree[$counter]['path'];
  	}

  	$rubriques_string .= tep_href_link('magazine.php', $cPath_new) . "' title=\"" . $tree[$counter]['titre'] . "\">";

  	if (isset($cPath_array) && in_array($counter, $cPath_array)) 
  	{
    		$rubriques_string .= '<b>';
  	}


  	$rubriques_string .= affichemots($tree[$counter]['titre'],4);

  	if (isset($cPath_array) && in_array($counter, $cPath_array)) 
  	{
    		$rubriques_string .= '</b>';
  	}

  	$rubriques_string .= '</a>';

 	$rubriques_string .= '<br>';

  	if ($tree[$counter]['next_id'] != false) 
  	{
    		tep_show_rubriques($tree[$counter]['next_id']);
  	}
}



/* pour obtenir une variable du style : "A Tahiti >> Manifestation >> expositions" */
function suite_rubrique($id_rub)
{
	global $rubrique_article, $cPath_rebuilt;
	
	$rubrique_query = tep_db_query($sql_link,"SELECT r.id, r.parent, rc.titre FROM ".TABLE_RUBRIQUE." r, ".TABLE_RUBRIQUECONTENT." rc " .
				       "WHERE r.id = rc.rub_id AND rc.langue_id=1 AND r.id=" . $id_rub);

	$rubrique = tep_db_fetch_array($rubrique_query);
	
	if($rubrique['parent'] != 0)
	{
		$rubrique_article = '&nbsp;&raquo;&nbsp;' . $rubrique['titre'] . $rubrique_article;
		$cPath_rebuilt = '_' . $rubrique['id'] . $cPath_rebuilt;
		suite_rubrique($rubrique['parent']);
	}
	else
	{
		$rubrique_article = $rubrique['titre'] . $rubrique_article;
		$cPath_rebuilt = $rubrique['id'] . $cPath_rebuilt;
	}	
}



/* pour obtenir une variable du style : "expositions" - simplement le nom de la rubrique*/
function suite_rubrique_simple($id_rub, $table_rub, $table_rubriquecontent)
{
	global $rubrique_article, $cPath_rebuilt;
	
	$rubrique_query = tep_db_query($sql_link,"SELECT r.id, r.parent, rc.titre FROM ".$table_rub." r, ".$table_rubriquecontent." rc " .
				       "WHERE r.id = rc.rub_id AND rc.langue_id=1 AND r.id=" . $id_rub);

	$rubrique = tep_db_fetch_array($rubrique_query);
	
	return $rubrique['titre'];	
}


/* version tahitipearlregatta avec nom des tables en paramètre */
function tep_get_rubrique_tree($table_rub, $table_rubriquecontent, $parent_id = '0', $spacing = '', $exclude = '', $rubrique_tree_array = '') 
{
    	global $id_langue;
				
    	if(!is_array($rubrique_tree_array)){$rubrique_tree_array = array();}

		$rubrique_query = tep_db_query($sql_link,"SELECT r.id, r.parent, rc.titre FROM ".$table_rub." r, ".$table_rubriquecontent." rc " . 
    				       "WHERE r.id = rc.rub_id AND rc.langue_id = " . (int)$id_langue . " AND r.parent = " . (int)$parent_id . 
    				       " ORDER BY r.ordre, rc.titre");
		
    	while ($rubrique = tep_db_fetch_array($rubrique_query)) 
    	{
      		if ($exclude != $rubrique['id']){$rubrique_tree_array[] = array('id' => $rubrique['id'], 'text' => $spacing . $rubrique['titre']);}
			
      		$rubrique_tree_array = tep_get_rubrique_tree($table_rub, $table_rubriquecontent, $rubrique['id'], $spacing . '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', $exclude, $rubrique_tree_array);
    	}
	
	return $rubrique_tree_array;
}


function tep_input_rubrique_tree($id_langue, $parent_id = 0, $left=10, $exclude = '', $rubrique_tree_array = '') 
{
	if($parent_id!=0){$left += 50;}
	else
	{
		$text='';
		$text.="<img style='width: 18px;' src='".DIR_WS_IMG_ICO."fleche_gauche.png' title='nouvelle rubrique - parent: origine' onClick=\"affiche_newrub(0,".$id_langue.")\">\n";	
		$rubrique_tree_array[] = array('id' => 0, 'text' => $text);
	}

    	if(!is_array($rubrique_tree_array)){$rubrique_tree_array = array();}


		

    	$rubrique_query = tep_db_query($sql_link,"SELECT r.id, r.parent, rc.titre FROM ".TABLE_RUBRIQUE." r, ".TABLE_RUBRIQUECONTENT." rc " . 
    				       "WHERE r.id = rc.rub_id AND rc.langue_id = " . (int)$id_langue . " AND r.parent = " . (int)$parent_id .
    				       " ORDER BY r.ordre, rc.titre");

	
	$div_space_new="<div id='new_div_".$id_langue."_".$parent_id."' style='margin-top:5px;margin-left:".$left."px;'> \n";
	
	$text='';
	$text.=$div_space_new."<input type='text' class='input_texte' name='rub_".$id_langue."_".$parent_id."_new' >\n";
	$text.="</div>\n";
	$rubrique_tree_array[] = array('id' => -1, 'text' => $text);

	
	//liste des rubriques pour 1 niveau
    $div_space="<div style='margin-top:5px;margin-left:".$left."px;'>\n";
    while ($rubrique = tep_db_fetch_array($rubrique_query)) 
    {
    	$text='';
    	if ($exclude != $rubrique['id'])
    	{
     			
    		$text.=$div_space."<input type='text' class='input_texte' name='rub_".$id_langue."_".$rubrique['id']."' value='".$rubrique['titre']."'>\n";
    		$text.="<img style='width: 18px;' src='".DIR_WS_IMG_ICO."fleche_gauche.png' title='nouvelle rubrique - parent: ".$rubrique['titre']."' onClick=\"affiche_newrub(".$rubrique['id'].",".$id_langue.")\">\n";
    		$text.="</div>\n";
      			
    		$rubrique_tree_array[] = array('id' => $rubrique['id'], 'text' => $text);
      	}
      	
		$rubrique_tree_array = tep_input_rubrique_tree($id_langue,$rubrique['id'], $left, $exclude, $rubrique_tree_array);
    }
	
	return $rubrique_tree_array;
}



?>
