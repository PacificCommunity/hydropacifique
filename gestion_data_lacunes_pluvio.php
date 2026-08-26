<?php
/*  
----------------------------------------
Copyright (c) 2015 - Vai-Natura
----------------------------------------
*/

require('include/application_top.php');

$message_info = '';
$row = 0;

$today = date('d-m-Y'); 


$sql_an = "SELECT DISTINCT YEAR(date_first) as year FROM ".TABLE_IMPORT." UNION SELECT YEAR(date_end) FROM ".TABLE_IMPORT." ORDER BY year DESC";
$annee_query = tep_db_query($sql_link,$sql_an);
while ($annee_t = tep_db_fetch_array($annee_query))
{
	$tab_annee[] = $annee_t['year'];
}

require(DIR_WS_STRUCTURE . 'header_web.php');


echo "<body>";

require(DIR_WS_STRUCTURE . 'header.php'); // Bando Haut
include(DIR_WS_BOX . 'nav_accueil.php'); // Menu

echo "<div id='contour_general'>";
	
	echo "<div id='contenu_centre'>";
		
		
		echo "<div id='contenu_box2'>";
		
			echo "<h1><span>".htmlaccent('Correction des données pluviométriques - Traitement des lacunes')."</span></h1>"; 	
				
			echo "<div id='onglet_contenu' class='first'>\n";
			
				echo "<div id='box_result' style='padding:10px 0;'>\n";
				
					echo "<form name='gestion_data_lacunes_pluvio' action='gestion_data_lacunes_pluvio_result.php' method='post' enctype='multipart/form-data'>";
					echo "<input type='hidden' name='id_user_f' id='id_user_f' value='".$id_user."'>";
					
					echo "<table id='stats_select' cellspacing='0' style='width: 750px;'>";
					
						echo "<tr>";
							
							echo "<td class='bold'>".htmlaccent('Station à corriger')."</td>";
							echo "<td style='width: 100px;'>";
								
								$sql_regions = "SELECT DISTINCT r.id, r.nom_region FROM ".TABLE_REGION. " r, ".TABLE_STATION." s WHERE r.id=s.id_region";
								$regions_query = tep_db_query($sql_link,$sql_regions);
								
								echo "<select name='select_region' id='select_region' onchange='import_select_region_ajax()'>";
									//echo "<option value='-1'>".htmlaccent('Choisir une île')."</option>";
									
									while($regions = tep_db_fetch_array($regions_query))
									{		
										$selected = '';					
										if($regions['id'] == $region_default){$selected = 'selected';}
										//else{if($regions['id'] == $region_default){$selected = 'selected';}}
										
										echo "<option value='".$regions['id']."' ".$selected.">".htmlaccent($regions['nom_region'])."</option>";
									}
									
								echo "</select>";
								echo "<p>".htmlaccent('Ile / Province')."</p>";
							echo "</td>";
							
							echo "<td id='station'>";
								
								$w = 0;
								$first_station=1;
								
								$sql_stations = "SELECT DISTINCT s.id, s.nom_station FROM ".TABLE_STATION." s, ".TABLE_EQ_TYPE." et, ".TABLE_EQUIPEMENT." e, ".TABLE_STATION_TO_EQUIPEMENT." ste WHERE s.id=ste.id_station AND ste.id_eq=e.id AND et.id=e.type_eq AND et.id=1 AND id_region=".$region_default." ORDER BY s.nom_station";
								$stations_query = tep_db_query($sql_link,$sql_stations);
								
								echo "<select name='select_station' id='select_station'>";
									while($stations = tep_db_fetch_array($stations_query))
									{		
										$selected = '';					
										if($w==0){$first_station=$stations['id'];}
										
										echo "<option value='".$stations['id']."' ".$selected.">".htmlaccent($stations['nom_station'])."</option>";
										$w++;
									}					
								echo "</select>";
								echo "<p>".htmlaccent('Station de mesure')."</p>";
							echo "</td>";
						
						
						echo "</tr>";
						
						echo "<tr>";
							echo "<td class='bold'>".htmlaccent('Stations de référence')."</td>";
							echo "<td colspan='2'>";
								
								
								$sql_stations = "SELECT DISTINCT s.id, s.nom_station FROM ".TABLE_STATION." s, ".TABLE_EQ_TYPE." et, ".TABLE_EQUIPEMENT." e, ".TABLE_STATION_TO_EQUIPEMENT." ste WHERE s.id=ste.id_station AND ste.id_eq=e.id AND et.id=e.type_eq AND et.id=1 AND id_region=".$region_default." ORDER BY s.nom_station";
								$stations_query = tep_db_query($sql_link,$sql_stations);
								
								echo "<select name='select_station_ref[]' id='select_station_ref' multiple='multiple'>";
									
									while($stations = tep_db_fetch_array($stations_query))
									{		
										echo "<option value='".$stations['id']."' >".htmlaccent($stations['nom_station'])."</option>";
									}
									
								echo "</select>";
								echo "<p>".htmlaccent('Choisir une ou plusieurs stations (4 max)')."</p>";
							
							echo "</td>";
						
						echo "</tr>";
						
						echo "<tr>";
							
							echo "<td class='bold'>".htmlaccent('Intervalle de mesures')."</td>";
							
							// date importation
							echo "<td colspan='2'>";
							
								$sql_interval = "SELECT DISTINCT * FROM ".TABLE_EXPORT_INTERVAL." ORDER BY min";
								$interval_query = tep_db_query($sql_link,$sql_interval);
								
								echo "<div style='float:left;width:100%;' id='data_interval'>\n";	
									echo "<select name='select_interval' id='select_interval' >";
										echo "<option value='1440' >".htmlaccent('1 jour')."</option>";
										/*
										echo "<option value='0' >".htmlaccent('données brutes')."</option>";
										//if($first_interval_type==1)
										//{	
											while($interval = tep_db_fetch_array($interval_query))
											{
												echo "<option value='".$interval['min']."' >".htmlaccent($interval['libelle'])."</option>";
											}
										//}
										*/
									echo "</select>";
									echo "<p>".htmlaccent('Choisir l\'intervalle')."</p>";
								echo "</div>\n";
							
							
							echo "</td>";
							
							echo "<td>&nbsp;</td>";
							
						echo "</tr>";
						
					echo "</table>";
					
				echo "<hr>\n";
				echo "</div>\n";		
				
				echo "<hr>\n";
				
				//Boutton					
				echo "<div id='boite1' style='margin-left:0px;'>\n";
				
					echo "<input type='submit' class='button' name='button_lacunes_pluvio' value=\"Corriger\" />";
					
				echo "<hr>\n";
				echo "</div>\n";
			
				echo "</form >\n";
				
			
			echo "<hr>\n";
			echo "</div>\n";
			
			
			
		echo "<hr>\n";
		echo "</div>\n";
	
	
	echo "<hr>";
	echo "</div>";
	
	//require(DIR_WS_BOX . 'box_news.php');
	
	echo "<hr>";
echo "</div>";
	require('include/application_bottom.php'); 
echo "</body>";

echo "</html>";

?>
