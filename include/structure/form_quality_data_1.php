<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Onglet code Qualité
*/

$row = 0;

// Requête sur Code Qualité
$sql_quality = "SELECT DISTINCT id_data_qualite, init_qualite_data, nom_qualite_data, info_qualite_data, id_eq_type 
				FROM ".TABLE_DATA_QUALITE."
				WHERE init_qualite_data<>'' ORDER BY init_qualite_data ASC";
$quality_query = tep_db_query($sql_link,$sql_quality);


echo "<div id='onglet_contenu'>\n";

	echo "<div id='boite1' class='first'>\n";

		echo "<table id='table_tri' cellspacing='0' >";
		
			echo "<thead>";
				echo "<th>".htmlaccent('Intitulé')."</th>";				
				echo "<th>".htmlaccent('Nom long')."</th>";
				echo "<th>".htmlaccent('Description')."</th>";
				echo "<th>".htmlaccent('Type de données')."</th>";
				echo "<th>&nbsp;</td>";
			echo "</thead>";	

			echo "<tr>";
					echo "<td colspan='4' style='height:10px;'>&nbsp;</td>";
				echo "</tr>";
			
			while($quality_tab = tep_db_fetch_array($quality_query))
			{
				$id_data_qualite = $quality_tab['id_data_qualite'];
				$init_qualite_data = htmlaccent(html_entity_decode($quality_tab['init_qualite_data']));
				$nom_qualite_data = htmlaccent(html_entity_decode($quality_tab['nom_qualite_data']));
				$info_qualite_data = htmlaccent(html_entity_decode($quality_tab['info_qualite_data']));
				$id_eq_type = $quality_tab['id_eq_type'];
			
				if(fmod($row,2)==0){$row_l="class='row1' onmouseover=\"this.className='row1hover';\" onmouseout=\"this.className='row1';\" ";} 
				else{$row_l="class='row2' onmouseover=\"this.className='row2hover';\" onmouseout=\"this.className='row2';\" ";} 
	
				echo "<tr ".$row_l." >";
						
					echo "<td>";
						echo "<input type='text' class='input_texte_small' name='init_".$id_data_qualite."' value='".$init_qualite_data."'>\n";
					echo "</td>";
					
					echo "<td>";
						echo "<input type='text' class='input_texte_200' name='nom_".$id_data_qualite."' value='".$nom_qualite_data."'>\n";
					echo "</td>";
					
					echo "<td>";
						echo "<input type='text' class='input_texte_450' name='info_".$id_data_qualite."' value='".$info_qualite_data."'>\n";
					echo "</td>";

					// Type de données liée Hydro, Plu, Piezo
					echo "<td>";
					
						echo "<select name='select_type_".$id_data_qualite."' id='select_type_".$id_data_qualite."' style='width:120px;' onchange=''>";
										
							echo "<option value='0'>Tous les types</option>";
							
							$selected = '';		
							if(isset($eq_type_array))
							{
								foreach($eq_type_array as $key => $value)
								{
									if($id_eq_type == $key){$selected="selected";}	
									else{$selected = '';}											
									echo "<option value='".$key."' ".$selected." >".$value."</option>";
								}
							}
							
						echo "</select>";
						
					echo "</td>";
					
					// Suppression code qualité
					echo "<td class='t_icon'>";
						$lien_suppr = "gestion_quality_data.php?id_cq=".$id_data_qualite;
						echo "<img src='".DIR_WS_IMG_ICO."delete.png' style='width:20px;cursor:pointer;' title='".htmlaccent('Supprimer')."' onClick=\"confirm_suppr('".$lien_suppr."','le code qualité','".$nom_qualite_data."');\">";
					echo "</td>\n";
				
				echo "</tr>";
			}
			
			
			// NEW QUALITE DATA
			
			echo "<tr><td colspan='4' style='color:#000;font-size:14px;font-weight:bold;'>".htmlaccent('Ajouter un Code Qualité')."</td></tr>\n";
							
			echo "<tr>";
			  
				echo "<td><input type='text' class='input_texte_small' name='init' ></td>";
				echo "<td><input type='text' class='input_texte_200' name='nom' ></td>";
				echo "<td><input type='text' class='input_texte_450' name='info' ></td>";
				echo "<td>";					
					echo "<select name='select_type' id='select_type' style='width:120px;' onchange=''>";
									
						echo "<option value='0'>Tous les types</option>";
						
						$selected = '';		
						if(isset($eq_type_array))
						{
							foreach($eq_type_array as $key => $value)
							{
								echo "<option value='".$key."' >".$value."</option>";
							}
						}
						
					echo "</select>";					
				echo "</td>";
				
				echo "<td>&nbsp;</td>";
				
			echo "</tr>";
			
		echo "</table>";

	echo "<hr>\n";
	echo "</div>\n";
	
	
echo "<hr>\n";
echo "</div>\n";
?>
