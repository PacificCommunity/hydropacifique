<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Fonctions de recherche
----------------------------------------
*/


function search_station($mots,$id_sessions)
{	
	$mots_inutiles = array('le','les','la','un','une','des','de','à','où','l','a','au');
	$save = true;
	$save_mots = '';
	$clause_search = '';
	
	//on ne cherche pas sur les mots trop court
	if(strlen($mots) >= 0)
	{ 
		//on place chaque mot dans un tableau
		$mots_tab = explode(' ',$mots);
		
		//on enlève les mots inutiles	
		for($i=0;$i<sizeof($mots_tab);$i++)
		{
			$save = true;
			
			for($j=0;$j<sizeof($mots_inutiles);$j++)
			{if($mots_tab[$i] == $mots_inutiles[$j]){$save = false;}}
			
			if($save == true)
			{
				$mots_tab_reel[] = $mots_tab[$i];
				$save_mots .= $mots_tab[$i];
				if($i!=(sizeof($mots_tab)-1)){$save_mots .= '|';}
			}	
		}
		
		if(isset($mots_tab_reel))
		{ 
			if(sizeof($mots_tab_reel) > 0) //vérification que la recherche n'était pas faite que de mots inutiles
			{
				 //sauvegarde de la recherche dans la base		
			
				  /*
				  $search_query = tep_db_query($sql_link,"SELECT * FROM ".TABLE_SEARCH." WHERE mots='".$save_mots."' AND session_id=".$id_sessions);
				  $search_tab = tep_db_fetch_array($search_query);	
		
				  if(!tep_not_null($search_tab['id']))
				  {		
					tep_db_query($sql_link,"INSERT INTO " . TABLE_SEARCH  . " (mots,date,session_id) " . 
								 "VALUES ('".$save_mots."',now(),'".$id_sessions."')");
				 }
				 
				 	
					tep_db_query($sql_link,"INSERT INTO " . TABLE_SEARCH  . " (mots,date,session_id) " . 
								 "VALUES ('".$save_mots."',now(),'".$id_sessions."')");
				 */
				  
				 /* 
				 // Recherche mots par mots			  
				 for($k=0;$k<sizeof($mots_tab_reel);$k++)
				 {					
					//if($k==0){$clause_search .= " WHERE (";}
					if($k==0){$clause_search .= " AND (";}
					else{$clause_search .= " OR";}
					
					$clause_search .= " (nom_station LIKE \"%" . $mots_tab_reel[$k] . 
							  "%\" OR vallee_station LIKE \"%" . $mots_tab_reel[$k] .
							  "%\" OR code_station LIKE \"%" . $mots_tab_reel[$k] .
							  "%\")";
				 }
				 $clause_search .= ")";
				 */
				 
				 // Recherche par groupe de mots
				 $clause_search .= " AND (nom_station LIKE \"%" . implode(" ", $mots_tab_reel) . 
				 			  "%\" OR site_station LIKE \"%" . implode(" ", $mots_tab_reel) .
							  "%\" OR vallee_station LIKE \"%" . implode(" ", $mots_tab_reel) .
							  "%\" OR riviere_station LIKE \"%" . implode(" ", $mots_tab_reel) .
							  "%\" OR code_station LIKE \"%" . implode(" ", $mots_tab_reel) .
							  "%\")";
				  
			}
		}
		
	}
	
	return $clause_search;
}


function search_agent($mots,$id_sessions)
{	
	$mots_inutiles = array('le','les','la','un','une','des','de','à','où','l','a','au');
	$save = true;
	$save_mots = '';
	$clause_search = '';
	
	//on ne cherche pas sur les mots trop court
	if(strlen($mots) >= 0)
	{ 
		//on place chaque mot dans un tableau
		$mots_tab = explode(' ',$mots);
		
		//on enlève les mots inutiles	
		for($i=0;$i<sizeof($mots_tab);$i++)
		{
			$save = true;
			
			for($j=0;$j<sizeof($mots_inutiles);$j++)
			{if($mots_tab[$i] == $mots_inutiles[$j]){$save = false;}}
			
			if($save == true)
			{
				$mots_tab_reel[] = $mots_tab[$i];
				$save_mots .= $mots_tab[$i];
				if($i!=(sizeof($mots_tab)-1)){$save_mots .= '|';}
			}	
		}
		
		if(isset($mots_tab_reel))
		{ 
			if(sizeof($mots_tab_reel) > 0) //vérification que la recherche n'était pas faite que de mots inutiles
			{
				 // Recherche par groupe de mots
				 $clause_search .= " WHERE (nom LIKE \"%" . implode(" ", $mots_tab_reel) . 
							  "%\" OR prenom LIKE \"%" . implode(" ", $mots_tab_reel) .
							  "%\" OR raisonsociale LIKE \"%" . implode(" ", $mots_tab_reel) .
							  "%\" OR fonction LIKE \"%" . implode(" ", $mots_tab_reel) .
							  "%\")";
				  
			}
		}
		
	}
	
	return $clause_search;
}

	
