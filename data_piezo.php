<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Affichage de la Liste des stations avec options de sélection
Uniquement les stations piézométriques
----------------------------------------
*/

require('include/application_top.php');

$row = 0;

$search_station = '';
$where_search = '';

$select_type_encours = 0;
$where_and_type = '';

$active_station = 1;
$where_and_active = " AND s.active_station=1";

$select_region_encours = 0;
$where_and_region = '';

$select_commune_encours = 0;
$where_and_commune = '';

$select_station_encours = 0;
$where_and_station = '';

$station_array = [];
$act_station = false;


// TABLE REGION
$sql_region = "SELECT DISTINCT id_region, nom_region 
				FROM ".TABLE_REGION." 
				WHERE id_territoire=".$territoire_id;
$region_query = tep_db_query($sql_link,$sql_region);
while ($region = tep_db_fetch_array($region_query))
{
	$region_array[] = array('id_region' => $region['id_region'],
							'nom_region' => htmlaccent(html_entity_decode($region['nom_region'] ?? $default_string))
							);
}

// SELECT STATION



// recherche des stations
if(isset($_POST['search_station']) || isset($_GET['search_station']))
{
	if(isset($_POST['search_station'])){$search_station = post_secure($sql_link,$_POST['search_station']);}
	if(isset($_GET['search_station'])){$search_station = post_secure($sql_link,$_GET['search_station']);}
	
	$where_search = search_station($search_station,'');
	
	
	// recherche des stations
	if(!isset($_POST['active_station']))// || isset($_GET['search_active']) à voir si utile à l'usage
	{
		$active_station = 0;
		$where_and_active = ""; // pour la selection des stations active	
	}
}

if(isset($_POST['select_region']) && $_POST['select_region']!=0)
{
	$select_region_encours = $_POST['select_region'];
	$where_and_region = " AND r.id_region=".$select_region_encours; // pour la selection région	
}

if(isset($_POST['select_commune']) && $_POST['select_commune']!=0)
{
	$select_commune_encours = $_POST['select_commune'];
	$where_and_commune = " AND c.id_commune=".$select_commune_encours; // pour la selection commune	
}

$where_and_type = " AND s.station_type=5"; // Uniquement des stations piézométrique

// TABLE COMMUNE
$sql_commune = "SELECT DISTINCT c.id_commune, c.nom_commune 
				FROM ".TABLE_COMMUNE." c
				JOIN ".TABLE_REGION." r ON c.id_region=r.id_region
				WHERE r.id_territoire=".$territoire_id.$where_and_region." 
				ORDER BY c.nom_commune ASC";

$commune_query = tep_db_query($sql_link,$sql_commune);
while ($commune = tep_db_fetch_array($commune_query))
{
	$commune_array[] = array('id_commune' => $commune['id_commune'],
							'nom_commune' => htmlaccent(html_entity_decode($commune['nom_commune'] ?? $default_string))
							);
}


/* REQUETE SQL - DATA STATION */

$sql_station = "SELECT DISTINCT s.id_station, s.id_station_old, s.id_region, r.nom_region, s.id_commune, c.nom_commune, s.nom_station, s.code_station, s.vallee_station, 
								s.date_installation_station, s.date_fermeture_station, s.active_station, s.station_type, et.nom_eq_type, 
								s.piezo_sonde, s.piezo_suivi,
								et.type_color_border 
				FROM ".TABLE_STATION." s
				JOIN ".TABLE_REGION." r ON s.id_region=r.id_region				
				JOIN ".TABLE_COMMUNE." c ON s.id_commune=c.id_commune 
				JOIN ".TABLE_EQ_TYPE." et ON s.station_type=et.id_eq_type 
				WHERE r.id_territoire=".$territoire_id.$where_search.$where_and_type.$where_and_region.$where_and_commune.$where_and_active." 
				ORDER BY s.active_station DESC, s.nom_station";

$station_query = tep_db_query($sql_link,$sql_station);
while ($station = tep_db_fetch_array($station_query))
{	
	$id_region =  html_entity_decode($station['id_region'] ?? $default_string);
	$nom_region = htmlaccent(html_entity_decode($station['nom_region'] ?? $default_string));
	$id_commune =  html_entity_decode($station['id_commune'] ?? $default_string);	
	$nom_commune = htmlaccent(html_entity_decode($station['nom_commune'] ?? $default_string));
    $nom_eq_type =  htmlaccent(html_entity_decode($station['nom_eq_type'] ?? $default_string));	
	$type_color_border =  htmlaccent(html_entity_decode($station['type_color_border'] ?? $default_string));
	$nom_station =  htmlaccent(html_entity_decode($station['nom_station'] ?? $default_string));
	$code_station =  htmlaccent(html_entity_decode($station['code_station'] ?? $default_string));
	$vallee_station =  htmlaccent(html_entity_decode($station['vallee_station'] ?? $default_string));
	$date_installation_station =  dateus_fr($station['date_installation_station']);
	$date_fermeture_station =  dateus_fr($station['date_fermeture_station']);
	
	$act_station = false;
	if($station['active_station'] == 1){$act_station = true;}

	$piezo_sonde = false;
	if($station['piezo_sonde'] == 1){$piezo_sonde = true;}

	$piezo_suivi = false;
	if($station['piezo_suivi'] == 1){$piezo_suivi = true;}
		
		
	$station_array[] = array('id' => $station['id_station'],
							 'id_old' => $station['id_station_old'],
							 'nom_eq_type' => $station['nom_eq_type'],
							 'type_color_border' => $station['type_color_border'],
							 'nom_region' => $nom_region,
							 'nom_commune' => $nom_commune,
							 'act_station' => $act_station,
							 'nom_station' => $nom_station,
						   	 'code_station' => $code_station,
							 'vallee_station' => $vallee_station,
							 'piezo_sonde' => $piezo_sonde,
							 'piezo_suivi' => $piezo_suivi
							);
	
}
$nb_stations = sizeof($station_array);	


// Nbre Fichier de données / TABLE Meta
/*
$sql_meta_data = "SELECT COUNT(DISTINCT m.id_typedata) AS nb_diff_typedata, s.id_station 
				FROM ".TABLE_DATA_META." m
				JOIN ".TABLE_STATION." s ON m.id_station=s.id_station 
				JOIN ".TABLE_REGION." r ON s.id_region=r.id_region 							
				JOIN ".TABLE_COMMUNE." c ON s.id_commune=c.id_commune 
				WHERE r.id_territoire=".$territoire_id.$where_search.$where_and_type.$where_and_region.$where_and_commune.$where_and_active." 
				GROUP BY m.id_station";

$meta_query = tep_db_query($sql_link,$sql_meta_data);
while ($meta_tab = tep_db_fetch_array($meta_query))
{	
	$nb_meta_array[$meta_tab['id_station']] = array('nb_diff_typedata' => $meta_tab['nb_diff_typedata']);
}
*/


// Nombre Repère 
/*
$sql_pr = "SELECT COUNT(*) as nb_repere, s.id_station 
			FROM ".TABLE_DATA_PIEZO_REPERE." pr			
			JOIN ".TABLE_STATION." s ON etl.id_station=s.id_station
			WHERE s.station_type=11 
			GROUP BY pr.id_station";// On ne s'intéresse qu'aux stations hydrométriques  

$pr_query = tep_db_query($sql_link,$sql_pr);
while ($nb_pr_tab = tep_db_fetch_array($pr_query))
{	
	$nb_pr_array[$nb_pr_tab['id_station']] = array('nb_pr' => $nb_pr_tab['nb_etl']);
}
*/

require(DIR_WS_STRUCTURE . 'header_web.php');

echo "<body>";

require(DIR_WS_STRUCTURE . 'header.php'); // Bando Haut
include(DIR_WS_BOX . 'nav_accueil.php'); // Menu

echo "<div id='contour_general'>";	

	echo "<div id='contenu_centre'>";
		
		echo "<div id='contenu_box2'>";
		
			echo "<h1>";
				
				echo "<span>".htmlaccent('Repères et Profils piézométriques - Liste des Stations')."</span>";
				//echo button_pdf('export_pdf.php?type=list_st');

			echo "</h1>";

            echo "<hr>";

			echo "<div id='boxpopup' class='select-top' style='padding:5px 15px;'>\n";
			
				echo "<div id='contenu_tri' >";
					
					$lien_form = tep_href_link('data_piezo.php');			
					echo "<form name='form_station' action='".$lien_form."' method='post' enctype='multipart/form-data'>";
					  
						echo "<div style='float:left;width:90%;' >";
						
							echo "<p style='float:left;padding-top:5px;margin-right:5px;font-weight:bold;'>".htmlaccent('Rechercher')."</p>\n";
							
							echo "<input name='search_station' type='text' value='".$search_station."' style='float:left;'>";
							echo "<img src='".DIR_WS_IMG_ICO."arrow.png' alt='rechercher' onclick='form_station.submit();' style='float:left;margin-right:30px;'/>";
							
						
							// Selection Territoire (Province / Ile)
							
							echo "<p style='float:left;padding-top:5px;margin-right:5px;font-weight:bold;'>".htmlaccent($territoire_region)."</p>";
							
							echo "<select name='select_region' id='select_region' onchange='form_station.submit();' style='float:left;width:110px;margin-right: 20px;'>";
												
								echo "<option value='0'>-</option>";
								
								$selected = '';		
								if(isset($region_array))
								{
									for($c=0;$c<sizeof($region_array);$c++)
									{
										if($region_array[$c]['id_region'] == $select_region_encours){$selected="selected";}	
										else{$selected = '';}											
										echo "<option value='".$region_array[$c]['id_region']."' ".$selected." >".$region_array[$c]['nom_region']."</option>";
									}
								}
								
							echo "</select>";
							

							// Selection  par commune
							
							echo "<p style='float:left;padding-top:5px;margin-right:5px;font-weight:bold;'>".htmlaccent('Commune')."</p>";
							
							echo "<select name='select_commune' id='select_commune' onchange='form_station.submit();' style='float:left;width:130px;margin-right: 20px;'>";
												
								echo "<option value='0'>-</option>";
																		
								$selected = '';		
								if(isset($commune_array))
								{
									for($c=0;$c<sizeof($commune_array);$c++)
									{
										if($commune_array[$c]['id_commune'] == $select_commune_encours){$selected="selected";}	
										else{$selected = '';}											
										echo "<option value='".$commune_array[$c]['id_commune']."' ".$selected." >".$commune_array[$c]['nom_commune']."</option>";
									}
								}
								
							echo "</select>";
																	
							echo "<p style='float:left;padding-top:5px;margin-right:5px;font-weight:bold;'>".htmlaccent('Stations actives')."</p>";
							
							$check = 'checked';
							if($active_station==0){$check = '';}
							echo "<input type='checkbox' name='active_station' id='active_station' ".$check." style='float:left;width:15px;height:15px;' onchange='form_station.submit();' >";	
									
							//echo "<input type='checkbox' name='active_station' id='active_station' ".$check." style='width:50px;margin-top:25px;' onchange='form_station.submit();' >";	
						
						echo "<hr>";
						echo "</div>";	
	
						echo "<div id='contenu_infos'>";
									
							echo "<p>";
								echo "<span>".htmlaccent('Nombre de stations : ').number_format($nb_stations,0,'.',' ')."</span>";
							echo "</p>";
						
						echo "<hr>";
						echo "</div>";	

                        echo "</form>";
								
				echo "</div>";	
			
			echo "</div>";
			
			
			// Affichage Table
			if(isset($station_array) && ($nb_stations>0))
			{
				echo "<table id='table_tri' cellspacing='0' style='margin-top:10px;'>";
			
					echo "<thead>";
						echo "<tr>";
															
							echo "<th>".htmlaccent('Actif')."</th>";
							echo "<th>".htmlaccent('Code station')."</th>";						
							echo "<th>".htmlaccent('Nom de la station')."</th>";
							echo "<th>".htmlaccent('Commune')."</th>";
							echo "<th>".$territoire_region."</th>";							                            		
							echo "<th style='text-align:center;'>".htmlaccent('Suivi')."</th>"; // Ponctuel ou continu
							echo "<th style='text-align:center;'>".htmlaccent('Sonde')."</th>"; // Equipé ou non
							echo "<th>&nbsp;</th>";			
							echo "<th>&nbsp;</th>";			
					echo "</tr>";
					echo "</thead>";
			
					//ligne vide dans le tableau		
						
					echo "<tr>";
						echo "<td colspan='10' style='height:10px;'>&nbsp;</td>";
					echo "</tr>";	
						
					for($c=0;$c<sizeof($station_array);$c++)
					{	
						if(fmod($row,2)==0){$row_l="class='row1' onmouseover=\"this.className='row1hover';\" onmouseout=\"this.className='row1';\" ";} 
						else{$row_l="class='row2' onmouseover=\"this.className='row2hover';\" onmouseout=\"this.className='row2';\" ";} 
						
						$color_type = '';
						if(tep_not_null($station_array[$c]['type_color_border'])){$color_type = 'color:'.$station_array[$c]['type_color_border'].';';}
						
						echo "<tr ".$row_l." >";
						
							//statut activité
							if($station_array[$c]['act_station'])
							{
								echo "<td class='t_cont_xs' ><img src='".DIR_WS_IMG_ICO."puce_verte.png' style='width:12px;' title='".htmlaccent('En activité')."'></td>\n";									
							}
							else
							{
								echo "<td class='t_cont_xs' ><img src='".DIR_WS_IMG_ICO."puce_rouge.png' style='width:12px;' title='".htmlaccent('Fermée')."'></td>\n";	
							}			
							
							$lien_modif = "modif_etl.php?st=".$station_array[$c]['id'];
							
							echo "<td class='t_cont_m' >".$station_array[$c]['code_station']."</td>\n";								
							echo "<td class='t_cont_xl' style='cursor:pointer;' title='".$station_array[$c]['nom_station']."'>".affichelettres($station_array[$c]['nom_station'],60)."</td>\n";															
							echo "<td style='width:110px;'>".$station_array[$c]['nom_commune']."</td>\n";
							echo "<td style='width:100px;'>".$station_array[$c]['nom_region']."</td>\n";

							if($station_array[$c]['piezo_suivi'])
							{
								echo "<td class='t_cont_xs' style='text-align:center;'><img src='".DIR_WS_IMG_ICO."puce_verte.png' style='width:12px;' title='".htmlaccent('Equipé')."'></td>\n";									
							}
							else
							{
								echo "<td class='t_cont_xs' style='text-align:center;'><img src='".DIR_WS_IMG_ICO."puce_rouge.png' style='width:12px;' title='".htmlaccent('Non équipé')."'></td>\n";	
							}	

							if($station_array[$c]['piezo_sonde'])
							{
								echo "<td class='t_cont_xs' style='text-align:center;'><img src='".DIR_WS_IMG_ICO."puce_verte.png' style='width:12px;' title='".htmlaccent('Régulier')."'></td>\n";									
							}
							else
							{
								echo "<td class='t_cont_xs' style='text-align:center;'><img src='".DIR_WS_IMG_ICO."puce_rouge.png' style='width:12px;' title='".htmlaccent('Ponctuel')."'></td>\n";	
							}	

							echo "<td style='width:100px;'>".htmlaccent('Repères')."</td>\n";

							echo "<td style='width:100px;'>".htmlaccent('Profil de conductivité')."</td>\n";
							
						echo "</tr>\n";
					}

				echo "</table>";
			}
			else
			{
				echo "<div id='boxpopup' >\n";
					echo "<p class='alert'>".htmlaccent('Aucune station n\'a été trouvée')."</p>";
				echo "<hr>";
				echo "</div>";
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
