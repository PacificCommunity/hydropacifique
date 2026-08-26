<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Onglet code Qualité
*/

$row = 0;

// Requête sur TYPE DE MESURE
$sql_saumon = "SELECT DISTINCT id, num, titre, poids, distance_axe, t_air, r_dist, fabricant, obs
				FROM ".TABLE_SAUMON."
				ORDER BY num ASC";
$saumon_query = tep_db_query($sql_link,$sql_saumon);


echo "<div id='onglet_contenu'>\n";

	echo "<div id='boite1' class='first'>\n";

		echo "<table id='table_tri' cellspacing='0' >";
		
			echo "<thead>";
				echo "<tr>";
					echo "<th>".htmlaccent('Num')."</td>";	
					echo "<th>".htmlaccent('Titre')."</td>";	
					echo "<th>".htmlaccent('Poids')."</td>";	
					echo "<th>".htmlaccent('Distance Axe')."</td>";	
					echo "<th>".htmlaccent('Tair')."</td>";	
					echo "<th>".htmlaccent('Rdist')."</td>";	
					echo "<th>".htmlaccent('Fabricant')."</td>";
					echo "<th>".htmlaccent('Observation')."</td>";
					echo "<th style='text-align:center;' >&nbsp;</td>";
				echo "</tr>";
			echo "</thead>";			

			//ligne vide dans le tableau		
						
			echo "<tr>";
				echo "<td colspan='4' style='height:10px;'>&nbsp;</td>";
			echo "</tr>";
			
			while($saumon_tab = tep_db_fetch_array($saumon_query))
			{
				$id = $saumon_tab['id'];
				$num = htmlaccent(html_entity_decode($saumon_tab['num']));
				$titre = htmlaccent(html_entity_decode($saumon_tab['titre']));
				$poids = htmlaccent(html_entity_decode($saumon_tab['poids']));
				$dist_axe = htmlaccent(html_entity_decode($saumon_tab['distance_axe']));
				$t_air = htmlaccent(html_entity_decode($saumon_tab['t_air']));
				$r_dist = htmlaccent(html_entity_decode($saumon_tab['r_dist']));
				$fabricant = htmlaccent(html_entity_decode($saumon_tab['fabricant']));
				$obs = htmlaccent(html_entity_decode($saumon_tab['obs']));
			
				if(fmod($row,2)==0){$row_l="class='row1' onmouseover=\"this.className='row1hover';\" onmouseout=\"this.className='row1';\" ";} 
				else{$row_l="class='row2' onmouseover=\"this.className='row2hover';\" onmouseout=\"this.className='row2';\" ";} 
	
				echo "<tr ".$row_l." >";
						
					echo "<td>";
						echo "<input type='text' class='input_texte_small' name='num_".$id."' value='".$num."'>\n";
					echo "</td>";

					echo "<td>";
						echo "<input type='text' class='input_texte_200' name='titre_".$id."' value='".$titre."'>\n";
					echo "</td>";
					
					echo "<td>";
						echo "<input type='text' class='input_texte_60' name='poids_".$id."' value='".$poids."'>\n";
					echo "</td>";
					
					echo "<td>";
						echo "<input type='text' class='input_texte_60' name='dist_axe_".$id."' value='".$dist_axe."'>\n";
					echo "</td>";
					
					echo "<td>";
						echo "<input type='text' class='input_texte_60' name='r_dist_".$id."' value='".$t_air."'>\n";
					echo "</td>";
					
					echo "<td>";
						echo "<input type='text' class='input_texte_60' name='r_dist_".$id."' value='".$r_dist."'>\n";
					echo "</td>";

					echo "<td>";
						echo "<input type='text' class='input_texte_200' name='fabricant_".$id."' value='".$fabricant."'>\n";
					echo "</td>";
					
					echo "<td>";
						echo "<input type='text' class='input_texte_300' name='obs_".$id."' value='".$obs."'>\n";
					echo "</td>";
					
					// supprimer
					echo "<td class='t_icon'>";
						$lien_suppr = "gestion_eq_jaugeage.php?del_s=".$id;
						echo "<img src='".DIR_WS_IMG_ICO."delete.png' style='width:20px;cursor:pointer;' title='".htmlaccent('Supprimer')."' onClick=\"confirm_suppr('".$lien_suppr."','Saumon','".$num."');\">";
					echo "</td>\n";
				
				echo "</tr>";
			}
			
			
			// NEW DATA TYPE
			
			echo "<tr><td colspan='13' style='color:#ABB2B9;font-size:14px;'>".htmlaccent('Ajouter une saumon')."</td></tr>\n";
							
			  echo "<tr>";
			  
				echo "<td><input type='text' class='input_texte_small' name='num' ></td>";
				echo "<td><input type='text' class='input_texte_200' name='titre' ></td>";
				echo "<td><input type='text' class='input_texte_60' name='poids' ></td>";
				echo "<td><input type='text' class='input_texte_60' name='dist_axe' ></td>";
				echo "<td><input type='text' class='input_texte_60' name='t_air' ></td>";
				echo "<td><input type='text' class='input_texte_60' name='r_dist' ></td>";
				echo "<td><input type='text' class='input_texte_200' name='fabricant' ></td>";
				echo "<td><input type='text' class='input_texte_300' name='obs' ></td>";
				
				echo "<td>&nbsp;</td>";
				
			echo "<tr>";
			
		
		echo "</table>";

	echo "<hr>\n";
	echo "</div>\n";
	
	
echo "<hr>\n";
echo "</div>\n";
?>
