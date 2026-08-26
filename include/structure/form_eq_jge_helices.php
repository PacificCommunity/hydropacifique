<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Onglet Hélice
*/

$row = 0;

// Requête sur TYPE DE MESURE
$sql_helice = "SELECT DISTINCT id, num, diametre, pas, l1, a1, b1, l2, a2, b2, a3, b3, fabricant, obs
				FROM ".TABLE_HELICE."
				ORDER BY num ASC";
$helice_query = tep_db_query($sql_link,$sql_helice);


echo "<div id='onglet_contenu'>\n";

	echo "<div id='boite1' class='first'>\n";

		echo "<table id='table_tri' cellspacing='0' >";
		
			echo "<thead>";
				echo "<tr>";
					echo "<th>".htmlaccent('Numéro')."</th>";	
					echo "<th>".htmlaccent('Diamètre')."</th>";	
					echo "<th>".htmlaccent('Pas')."</th>";	
					echo "<th>".htmlaccent('l1')."</th>";	
					echo "<th>".htmlaccent('a1')."</th>";	
					echo "<th>".htmlaccent('b1')."</th>";
					echo "<th>".htmlaccent('l2')."</th>";	
					echo "<th>".htmlaccent('a2')."</th>";	
					echo "<th>".htmlaccent('b2')."</th>";	
					echo "<th>".htmlaccent('a3')."</th>";	
					echo "<th>".htmlaccent('b3')."</th>";				
					echo "<th>".htmlaccent('Fabricant')."</th>";
					echo "<th>".htmlaccent('Observation')."</th>";
					echo "<th style='text-align:center;' >&nbsp;</th>";
				echo "</tr>";
			echo "</thead>";			

			//ligne vide dans le tableau	
			echo "<tr>";
				echo "<td colspan='14' style='height:10px;'>&nbsp;</td>";
			echo "</tr>";
			
			while($helice_tab = tep_db_fetch_array($helice_query))
			{
				$id = $helice_tab['id'];
				$num = htmlaccent(html_entity_decode($helice_tab['num'] ?? $default_string));
				$diam = html_entity_decode($helice_tab['diametre'] ?? $default_string);
				$pas = html_entity_decode($helice_tab['pas'] ?? $default_string);
				$l1 = html_entity_decode($helice_tab['l1'] ?? $default_string);
				$a1 = html_entity_decode($helice_tab['a1'] ?? $default_string);
				$b1 = html_entity_decode($helice_tab['b1'] ?? $default_string);
				$l2 = html_entity_decode($helice_tab['l2'] ?? $default_string);
				$a2 = html_entity_decode($helice_tab['a2'] ?? $default_string);
				$b2 = html_entity_decode($helice_tab['b2'] ?? $default_string);
				$a3 = html_entity_decode($helice_tab['a3'] ?? $default_string);
				$b3 = html_entity_decode($helice_tab['b3'] ?? $default_string);				
				$fabricant = htmlaccent(html_entity_decode($helice_tab['fabricant'] ?? $default_string));
				$obs = htmlaccent(html_entity_decode($helice_tab['obs'] ?? $default_string));
			
				if(fmod($row,2)==0){$row_l="class='row1' onmouseover=\"this.className='row1hover';\" onmouseout=\"this.className='row1';\" ";} 
				else{$row_l="class='row2' onmouseover=\"this.className='row2hover';\" onmouseout=\"this.className='row2';\" ";} 
	
				echo "<tr ".$row_l." >";
						
					echo "<td>";
						echo "<input type='text' class='input_texte_small' name='num_".$id."' value='".$num."'>\n";
					echo "</td>";

					echo "<td>";
						echo "<input type='text' class='input_texte_60' name='diam_".$id."' value='".$diam."'>\n";
					echo "</td>";
					
					echo "<td>";
						echo "<input type='text' class='input_texte_60' name='pas_".$id."' value='".$pas."'>\n";
					echo "</td>";
					
					echo "<td>";
						echo "<input type='text' class='input_texte_60' name='l1_".$id."' value='".$l1."'>\n";
					echo "</td>";
					
					echo "<td>";
						echo "<input type='text' class='input_texte_60' name='a1_".$id."' value='".$a1."'>\n";
					echo "</td>";
					
					echo "<td>";
						echo "<input type='text' class='input_texte_60' name='b1_".$id."' value='".$b1."'>\n";
					echo "</td>";
					
					echo "<td>";
						echo "<input type='text' class='input_texte_60' name='l2_".$id."' value='".$l2."'>\n";
					echo "</td>";
					
					echo "<td>";
						echo "<input type='text' class='input_texte_60' name='a2_".$id."' value='".$a2."'>\n";
					echo "</td>";
					
					echo "<td>";
						echo "<input type='text' class='input_texte_60' name='b2_".$id."' value='".$b2."'>\n";
					echo "</td>";
					
					echo "<td>";
						echo "<input type='text' class='input_texte_60' name='a3_".$id."' value='".$a3."'>\n";
					echo "</td>";
					
					echo "<td>";
						echo "<input type='text' class='input_texte_60' name='b3_".$id."' value='".$b3."'>\n";
					echo "</td>";
					
					echo "<td>";
						echo "<input type='text' class='input_texte_200' name='fabricant_".$id."' value='".$fabricant."'>\n";
					echo "</td>";
					
					echo "<td>";
						echo "<input type='text' class='input_texte_300' name='obs_".$id."' value='".$obs."'>\n";
					echo "</td>";
					
					// supprimer
					echo "<td class='t_icon'>";
						$lien_suppr = "gestion_eq_jaugeage.php?del_h=".$id;
						echo "<img src='".DIR_WS_IMG_ICO."delete.png' style='width:20px;cursor:pointer;' title='".htmlaccent('Supprimer')."' onClick=\"confirm_suppr('".$lien_suppr."','Helice','".$num."');\">";
					echo "</td>\n";
				
				echo "</tr>";
			}
			
			
			// NEW DATA TYPE
			
			echo "<tr><td colspan='13' style='color:#ABB2B9;font-size:14px;'>".htmlaccent('Ajouter une helice')."</td></tr>\n";
							
			  echo "<tr>";
			  
				echo "<td><input type='text' class='input_texte_small' name='num' ></td>";
				echo "<td><input type='text' class='input_texte_60' name='diam' ></td>";
				echo "<td><input type='text' class='input_texte_60' name='pas' ></td>";
				echo "<td><input type='text' class='input_texte_60' name='l1' ></td>";
				echo "<td><input type='text' class='input_texte_60' name='a1' ></td>";
				echo "<td><input type='text' class='input_texte_60' name='b1' ></td>";
				echo "<td><input type='text' class='input_texte_60' name='l2' ></td>";
				echo "<td><input type='text' class='input_texte_60' name='a2' ></td>";
				echo "<td><input type='text' class='input_texte_60' name='b2' ></td>";
				echo "<td><input type='text' class='input_texte_60' name='a3' ></td>";
				echo "<td><input type='text' class='input_texte_60' name='b3' ></td>";
				echo "<td><input type='text' class='input_texte_200' style='width:200px;' name='fabricant' ></td>";
				echo "<td><input type='text' class='input_texte_300' name='obs' ></td>";
				
				echo "<td>&nbsp;</td>";
				
			echo "<tr>";
			
		
		echo "</table>";

	echo "<hr>\n";
	echo "</div>\n";
	
	
echo "<hr>\n";
echo "</div>\n";
?>
