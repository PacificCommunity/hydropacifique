<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
*/

require('include/application_top.php');

$message_info = '';
$where_station = '';
$where_region = '';
$where_and_station = '';
$where_and_region = '';
$select_region = 0;
$select_station = 0;
$sql_page = '';
$row = 0;


if(isset($_POST['select_region']) && tep_not_null($_POST['select_region']) && $_POST['select_region']!=0)
{
	$select_region = $_POST['select_region'];
	$where_and_region = "AND st.id_region=".$select_region;
	$sql_page .= '&select_region='.$select_region;
}

if(isset($_POST['select_station']) && tep_not_null($_POST['select_station']) && $_POST['select_station']!=0)
{
	$select_station = $_POST['select_station'];
	
	$sql_verif_st = "SELECT DISTINCT * FROM ".TABLE_STATION." WHERE id_region=".$select_region." AND id=".$select_station;
	$verif_st_query = tep_db_query($sql_link,$sql_verif_st);
	$verif_st = tep_db_fetch_array($verif_st_query);
	
	if(isset($verif_st['id']))
	{
		$where_and_station = "AND im.id_station=".$select_station;
		$sql_page .= '&select_station='.$select_station;
	}
	else
	{$select_station = 0;}
}

/*
if(isset($_GET['select_region']) && tep_not_null($_GET['select_region']) && $_GET['select_region']!=0)
{
	$select_region = $_GET['select_region'];
	$where_and_region = "AND st.id_region=".$select_region;
	$sql_page .= '&select_region='.$select_region;
}
if(isset($_GET['select_station']) && tep_not_null($_GET['select_station']) && $_GET['select_station']!=0)
{
	$select_station = $_GET['select_station'];
	
	$sql_verif_st = "SELECT DISTINCT * FROM ".TABLE_STATION." WHERE id_region=".$select_region." AND id=".$select_station;
	$verif_st_query = tep_db_query($sql_link,$sql_verif_st);
	$verif_st = tep_db_fetch_array($verif_st_query);
	
	if(isset($verif_st['id']))
	{
		$where_and_station = "AND im.id_station=".$select_station;
		$sql_page .= '&select_station='.$select_station;
	}
	else
	{$select_station = 0;}
}
*/

// nbre de ligne par page
$deb=0;
if(isset($_GET['deb']) && tep_not_null($_GET['deb']))
{
	$deb = post_secure($sql_link,$_GET['deb']);
	$limitArt = " LIMIT ".$deb.", ".NB_LIGNE_PAGE;
}
else
{$limitArt = " LIMIT 0, ".NB_LIGNE_PAGE;}	


if(isset($_GET['id']) && tep_not_null($_GET['id'])){require(DIR_WS_SUPPRIMER . 'suppr_import.php');}


/* requête sql pour récupérer les données d'importation */
//$sql_imp = "SELECT DISTINCT st.nom_station, im.id as im_id, im.id_technicien, im.id_materiel, im.file_data, im.nb_data, im.cumul, im.date_first, im.date_end FROM ".TABLE_IMPORT." im, ".TABLE_STATION." st WHERE im.id_station=st.id ".$where_and_region." ".$where_and_station." ORDER BY st.nom_station, im.date_first DESC ".$limitArt;
//$sql_imp = "SELECT DISTINCT st.nom_station, im.id as im_id, im.id_technicien, im.id_materiel, im.file_data, im.nb_data, im.cumul, im.date_first, im.date_end, e.designation, e.type_eq, eq.designation as type_name, u.nom as nom_user, u.prenom as prenom_user FROM ".TABLE_IMPORT." im, ".TABLE_STATION." st, ".TABLE_EQUIPEMENT." e, ".TABLE_USER." u, ".TABLE_USER_ACCES_TYPE." uat, ".TABLE_EQ_TYPE." eq WHERE e.type_eq=uat.id_type AND uat.acces=1 AND uat.id_user=".$id_user." AND u.id=".$id_user." AND e.id=im.id_materiel AND e.type_eq=eq.id AND im.id_station=st.id ".$where_and_region." ".$where_and_station." ORDER BY st.nom_station, im.date_first DESC ".$limitArt;

$sql_imp = "SELECT DISTINCT st.nom_station, im.id as im_id, im.id_technicien, im.id_materiel, im.date_import, im.file_data, im.nb_data, im.cumul, im.date_first, im.date_end, e.designation, e.type_eq, eq.designation as type_name FROM ".TABLE_IMPORT." im, ".TABLE_STATION." st, ".TABLE_EQUIPEMENT." e, ".TABLE_EQ_TYPE." eq WHERE e.id=im.id_materiel AND e.type_eq=eq.id AND im.id_station=st.id ".$where_and_region." ".$where_and_station." ORDER BY im.date_first DESC, st.nom_station ASC ".$limitArt;



$imp_query = tep_db_query($sql_link,$sql_imp);

while($imp = tep_db_fetch_array($imp_query))
{
	//$sql_station = "SELECT DISTINCT * FROM ".TABLE_STATION." WHERE id=".$imp['id_station'];
	//$station_query = tep_db_query($sql_link,$sql_station);
	//$station = tep_db_fetch_array($station_query);
	$nom_station =  htmlaccent(html_entity_decode($imp['nom_station']));
	
	/*
	$sql_eq = "SELECT DISTINCT * FROM ".TABLE_EQUIPEMENT." WHERE id=".$imp['id_materiel'];
	$eq_query = tep_db_query($sql_link,$sql_eq);
	$equipement = tep_db_fetch_array($eq_query);
	$eq_info =  htmlaccent(html_entity_decode($equipement['designation']));
	$type_eq =  htmlaccent(html_entity_decode($equipement['type_eq']));
	*/
	
	if(tep_not_null($imp['id_technicien']))
	{
		$sql_tech = "SELECT DISTINCT * FROM ".TABLE_USER." WHERE id=".$imp['id_technicien'];
		$tech_query = tep_db_query($sql_link,$sql_tech);
		$agent = tep_db_fetch_array($tech_query);
		$tech_nom_pren = $agent['prenom'].' '.$agent['nom'];
	}
	
	
	$file_data = htmlaccent(post_secure($sql_link,$imp['file_data']));
	$date_import = dateus_fr($imp['date_import']);
	$nb_data = htmlaccent(post_secure($sql_link,$imp['nb_data']));
	$cumul = htmlaccent(post_secure($sql_link,$imp['cumul']));
	$eq_info =  htmlaccent(post_secure($sql_link,$imp['designation']));
	$type_eq =  htmlaccent(post_secure($sql_link,$imp['type_eq']));
	$type_name =  htmlaccent(post_secure($sql_link,$imp['type_name']));
	$date_first = dateus_fr($imp['date_first']);
	$date_end = dateus_fr($imp['date_end']);
	
	//$nom_user =  htmlaccent(post_secure($sql_link,$imp['nom_user']));
	//$prenom_user =  htmlaccent(post_secure($sql_link,$imp['prenom_user']));
	
	$import_array[] = array('id' => $imp['im_id'],
							 'nom_station' => $nom_station,
							 'eq_info' => $eq_info,
							 'type_eq' => $type_eq,
							 'type_name' => $type_name,
							 'user' => $tech_nom_pren,
							 'file_data' => $file_data,
							 'date_import' => $date_import,
						   	 'nb_data' => $nb_data,
							 'cumul' => $cumul,
							 'date_first' => $date_first,
							 'date_end' => $date_end);
	
}

$sql_nb = "SELECT DISTINCT count(*) as nb_import FROM ".TABLE_IMPORT." im, ".TABLE_STATION." st, ".TABLE_EQUIPEMENT." e WHERE e.id=im.id_materiel AND im.id_station=st.id ".$where_and_region." ".$where_and_station;
$nb_query = tep_db_query($sql_link,$sql_nb);
$nb_imp = tep_db_fetch_array($nb_query);
$nb_import = $nb_imp['nb_import'];


require(DIR_WS_STRUCTURE . 'header_web.php');


echo "<body>";

require(DIR_WS_STRUCTURE . 'header.php'); // Bando Haut
include(DIR_WS_BOX . 'nav_accueil.php'); // Menu

echo "<div id='contour_general'>";
	
	
	echo "<div id='contenu_centre'>";
		
		
		
		echo "<div id='contenu_box2'>";
		
			//echo "<h1><span>".htmlaccent('Liste des imports - Pluviomètres & Limnimètres')."</span></h1>"; 
			echo "<h1><span>".htmlaccent('Liste des données importées - Pluviomètres & Limnimètres')."</span></h1>"; 
		
			if(tep_not_null($message_info)){echo "<div id='contenu_info'>".$message_info."</div>";}
						
			
			echo "<div id='contenu_option'>";
			
				echo "<div id='contenu_tri' >";
					
					
					$lien_form = tep_href_link('import_list.php');			
					echo "<form name='form_station' action='".$lien_form."' method='post' enctype='multipart/form-data'>";
						
						
					  echo "<table id='stats_select' cellspacing='0' style='width:500px;'>";
			  
						  echo "<tr>";
							  
							  echo "<td class='bold' style='width: 100px;'>".htmlaccent('Sélectionner : ')."</td>";
							  echo "<td style='width: 100px;' >";
								  
								  $sql_regions = "SELECT DISTINCT r.id, r.nom_region FROM ".TABLE_REGION. " r, ".TABLE_STATION." s WHERE r.id=s.id_region";
								  $regions_query = tep_db_query($sql_link,$sql_regions);
								  $region_default = 39;
								  
								  echo "<select name='select_region' id='select_region' onchange='import_select_region_ajax();form_station.submit();'>";
									  
									  while($regions = tep_db_fetch_array($regions_query))
									  {		
										  $selected = '';					
										  if($regions['id'] == $select_region){$selected = 'selected';}
										  //else{if($regions['id'] == $region_default){$selected = 'selected';}}
										  
										  echo "<option value='".$regions['id']."' ".$selected.">".htmlaccent($regions['nom_region'])."</option>";
									  }
									  
								  echo "</select>";
								  echo "<p style='width:80px'>".$territoire_region."</p>";
							  echo "</td>";
							  
							  echo "<td id='station' style='width: 60px;'>";
								  
								  $w = 0;
								  $first_station=1;
								  $region_default = 39;
								  if($select_region==0){$select_region=$region_default;}
								  $sql_stations = "SELECT * FROM ".TABLE_STATION." WHERE id_region=".$select_region." ORDER BY nom_station";
								  $stations_query = tep_db_query($sql_link,$sql_stations);
								  
								  echo "<select name='select_station' id='select_station' onchange='form_station.submit();'>";
									  
									  echo "<option value='0'>".htmlaccent('Toutes')."</option>";
									  while($stations = tep_db_fetch_array($stations_query))
									  {		
										  $selected = '';					
										  if($stations['id']==$select_station){$selected='selected';}
										  
										  echo "<option value='".$stations['id']."' ".$selected.">".htmlaccent($stations['nom_station'])."</option>";
										  $w++;
									  }					
								  echo "</select>";
								  echo "<p>".htmlaccent('Station de mesure')."</p>";
							  echo "</td>";
							  
							  
							  
						  echo "</tr>";
						  
						  
					  echo "</table>";
					  
						//echo tep_draw_pull_down_menu_admin_root('rub', tep_get_rubrique_tree($table_rub,$table_rubcontent),'tous',$current_category_id,"onChange='javascript:tri_rub.submit();'");						
					echo "</form>";
					
				echo "<hr>";
				echo "</div>";	
				
			
			echo "<hr>";
			echo "</div>";
			
			
			echo "<table id='table_tri' cellspacing='0'>";
		
				echo "<thead>";
					echo "<tr>";
						
						echo "<th>".htmlaccent('Fichier')."</th>";								
						echo "<th>".htmlaccent('Station')."</th>";
						echo "<th>".htmlaccent('Date importation')."</th>";
						echo "<th>".htmlaccent('Appareil (format des données)')."</th>";
						echo "<th>".htmlaccent('Agent')."</th>";
						echo "<th style='text-align:center;'>".htmlaccent('Nb de données')."</th>";
						echo "<th style='text-align:center;'>".htmlaccent('Cumul (mm)')."</th>";
						echo "<th style='text-align:center;'>".htmlaccent('Premier enreg.')."</th>";
						echo "<th style='text-align:center;'>".htmlaccent('Dernier enreg.')."</th>";
						echo "<th>".htmlaccent('')."</th>";
				   echo "</tr>";
				echo "</thead>";
		
		
		
				//ligne vide dans le tableau		
					echo "<tr>";
						echo "<td>&nbsp;</td>";
						echo "<td>&nbsp;</td>";
						echo "<td>&nbsp;</td>";
						echo "<td>&nbsp;</td>";
						echo "<td>&nbsp;</td>";
						echo "<td>&nbsp;</td>";
						echo "<td>&nbsp;</td>";
						echo "<td>&nbsp;</td>";
						echo "<td>&nbsp;</td>";
						echo "<td>&nbsp;</td>";
						echo "<td>&nbsp;</td>";
					echo "</tr>";
					
										
												
						
					if(isset($import_array))
					{
						for($c=0;$c<sizeof($import_array);$c++)
						{	
							if(fmod($row,2)==0){$row_l="class='row1' onmouseover=\"this.className='row1hover';\" onmouseout=\"this.className='row1';\" ";} 
							else{$row_l="class='row2' onmouseover=\"this.className='row2hover';\" onmouseout=\"this.className='row2';\" ";} 
							
							
							echo "<tr ".$row_l." >";
							
								echo "<td class='t_cont'><a href='import_result.php?im=".$import_array[$c]['id']."'>" . $import_array[$c]['file_data'] . "</a></td>\n";
								echo "<td class='t_cont'><a href='import_result.php?im=".$import_array[$c]['id']."'>" . $import_array[$c]['nom_station'] . "</a></td>\n";
								
								$style_type='';
								if($import_array[$c]['type_eq']==1){$style_type="style='color:#336699;'";}
								if($import_array[$c]['type_eq']==2){$style_type="style='color:#3fa03d;'";}
								
								//echo "<td class='t_cont' ".$style_type.">" . $import_array[$c]['type_name'] . "</td>\n";
								echo "<td class='t_cont_m' style='text-align:center;'>" . $import_array[$c]['date_import'] . "</td>\n";
								
								echo "<td class='t_cont'>" . $import_array[$c]['eq_info'] . "</td>\n";
								echo "<td class='t_cont'>" . $import_array[$c]['user'] . "</td>\n";
								
								
								echo "<td class='t_cont_m' style='text-align:center;'>" . $import_array[$c]['nb_data'] . "</td>\n";
								
								if($import_array[$c]['cumul']>0){echo "<td class='t_cont' style='text-align:center;'>" . $import_array[$c]['cumul'] . "</td>\n";}
								else{echo "<td class='t_cont' style='text-align:center;'>-</td>\n";}
								
								echo "<td class='t_cont_m' style='text-align:center;'>" . $import_array[$c]['date_first'] . "</td>\n";
								echo "<td class='t_cont_m' style='text-align:center;'>" . $import_array[$c]['date_end'] . "</td>\n";
								
									
								// supprimer
								echo "<td class='t_cont_s' style='text-align:right;'>";
									$lien_suppr = "import_list.php?id=".$import_array[$c]['id'].$sql_page;
									//echo "<span class='contenu_plus' style='color:#e40000;cursor:pointer;' onClick=\"confirm_suppr('" . $lien_suppr . "','la import','" . $import_array[$c]['nom_import'] . "');\" title='Supprimer'>x</span>";						
									echo "<img src='".DIR_WS_IMG_ICO."delete.png' style='width:16px;cursor:pointer;' title='".htmlaccent('Supprimer')."' onClick=\"confirm_suppr('" . $lien_suppr . "','l\'import','" . $import_array[$c]['file_data'] . "');\">";
								echo "</td>\n";
							
							echo "</tr>\n";
						}
					}
			
			echo "</table>";
			
			
		if($nb_import > NB_LIGNE_PAGE)
		{
			echo "<div id='bottom_c'>\n";	
				echo "<div id='pagination'>\n";
					echo pagination($deb,NB_LIGNE_PAGE,$nb_import,'import_list',$sql_page);
				echo "</div>\n";
			echo "</div>\n";
		}	
		
		echo "<hr>";
		echo "</div>";
		
		
	echo "<hr>";
	echo "</div>";
	
	
	echo "<hr>";
echo "</div>";
	require('include/application_bottom.php'); 
echo "</body>";

echo "</html>";

?>	
