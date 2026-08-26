<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
*/


$row = 0;


echo "<div id='onglet_contenu'>\n";

	echo "<div id='boite1' class='first'>\n";

		$sql_type = "SELECT DISTINCT id_eq_type, nom_eq_type, valeur_data_type, order_eq_type, active_eq_type, type_color_border, type_color_background FROM ".TABLE_EQ_TYPE." ORDER BY active_eq_type DESC, order_eq_type ASC, nom_eq_type ASC";
		$eq_type_query = tep_db_query($sql_link,$sql_type);
		
		echo "<table id='table_tri' cellspacing='0' style=''>";
		
			echo "<thead>";
				echo "<th>".htmlaccent('Désignation')."</th>";			
				echo "<th>".htmlaccent('Mesure')."</th>";	
				echo "<th>".htmlaccent('Ordre')."</th>";
				echo "<th>".htmlaccent('Actif')."</th>";
				echo "<th>".htmlaccent('Color bordure')."</th>";	
				echo "<th>".htmlaccent('Color background')."</th>";					
				echo "<th>".htmlaccent('Type graph')."</th>";	
				echo "<th>&nbsp;</th>";
			echo "</thead>";

			echo "<tr>";
				echo "<td>&nbsp;</td>";
				echo "<td>&nbsp;</td>";
				echo "<td>&nbsp;</td>";
				echo "<td>&nbsp;</td>";	
				echo "<td>&nbsp;</td>";
				echo "<td>&nbsp;</td>";
				echo "<td>&nbsp;</td>";
				echo "<td>&nbsp;</td>";				
			echo "</tr>";
			
			$sql_type = "SELECT DISTINCT id_eq_type, nom_eq_type, valeur_data_type, order_eq_type, active_eq_type, type_color_border, type_color_background, type_graph 
						FROM ".TABLE_EQ_TYPE." 
						ORDER BY active_eq_type DESC, order_eq_type ASC, nom_eq_type ASC";
			$eq_type_query = tep_db_query($sql_link,$sql_type);
			while($eq_type = tep_db_fetch_array($eq_type_query)) 
			{
				$nom_eq_type = htmlaccent($eq_type['nom_eq_type']);
				$eq_typemesure_encours = $eq_type['valeur_data_type'];
				$ordre_type = $eq_type['order_eq_type'];
				$active_type = $eq_type['active_eq_type'];				
				$type_color_border = $eq_type['type_color_border'];
				$type_color_background = $eq_type['type_color_background'];
				$eq_typegraph_encours = $eq_type['type_graph'];
				
				
				if(fmod($row,2)==0){$row_l="class='row1' onmouseover=\"this.className='row1hover';\" onmouseout=\"this.className='row1';\" ";} 
				else{$row_l="class='row2' onmouseover=\"this.className='row2hover';\" onmouseout=\"this.className='row2';\" ";} 
	
				echo "<tr ".$row_l." >";
						
					echo "<td class='t_cont_xl'>";
						echo "<input type='text' class='input_texte_plus' name='nom_eq_type_".$eq_type['id_eq_type']."' value='".$nom_eq_type."'>\n";
					echo "</td>";
					
					echo "<td class='t_cont_m'>";
					
						$selected = '';
					
						echo "<select name='select_typemesure_".$eq_type['id_eq_type']."' id='select_typemesure_".$eq_type['id_eq_type']."' style='width:90px;' >";
										
						 	if($eq_typemesure_encours == 1){$selected="selected";}	
							else{$selected = '';}												
							echo "<option value='1' ".$selected." >".htmlaccent('Ponctuelle')."</option>";
							
							if($eq_typemesure_encours == 2){$selected="selected";}	
							else{$selected = '';}
							echo "<option value='2' ".$selected." >".htmlaccent('Cumulée')."</option>";
						  
						echo "</select>";
						
					echo "</td>";
					
					echo "<td class='t_cont_s'>";
						echo "<input type='text' class='input_texte_xsmall' style='text-align: center;' name='ordre_type_".$eq_type['id_eq_type']."' value='".$ordre_type."'>\n";
					echo "</td>";
					
					
					echo "<td  class='t_cont_xs'>";
						$check = '';
						if($active_type == 1){$check = 'checked';}
						echo "<input type='checkbox' name='active_type_".$eq_type['id_eq_type']."' ".$check." >";
					echo "</td>";
					
					
					echo "<td class='t_cont_m'>";
						echo "<input type='text' class='input_texte' style='width:80px;' name='type_color_border_".$eq_type['id_eq_type']."' value='".$type_color_border."'>\n";
					echo "</td>";
					
					
					echo "<td class='t_cont_m'>";
						echo "<input type='text' class='input_texte' style='width:80px;' name='type_color_background_".$eq_type['id_eq_type']."' value='".$type_color_background."'>\n";
					echo "</td>";

					echo "<td class='t_cont_m'>";
					
						$selected = '';
					
						echo "<select name='select_typegraph_".$eq_type['id_eq_type']."' id='select_typegraph_".$eq_type['id_eq_type']."' style='width:90px;' >";
										
							if($eq_typegraph_encours == 'lines'){$selected="selected";}	
							else{$selected = '';}												
							echo "<option value='lines' ".$selected." >".htmlaccent('lines')."</option>";
							
							if($eq_typegraph_encours == 'bar'){$selected="selected";}	
							else{$selected = '';}
							echo "<option value='bar' ".$selected." >".htmlaccent('bar')."</option>";
						  
						echo "</select>";
						
					echo "</td>";
					
					// supprimer
					echo "<td class='t_cont_xs'>";
						$lien_suppr = "gestion_type.php?id=".$eq_type['id_eq_type'];
						echo "<img src='".DIR_WS_IMG_ICO."delete.png' style='width:20px;cursor:pointer;' title='".htmlaccent('Supprimer')."' onClick=\"confirm_suppr('" . $lien_suppr . "','le type de mesure','".$nom_eq_type."');\">";
					echo "</td>\n";
				
				echo "</tr>";					
			}
			
			
			
			echo "<tr><td colspan='5' style='color:#ABB2B9;font-size:14px;'>".htmlaccent('Ajouter un type')."</td></tr>\n";
							
							
			  echo "<tr>";
				echo "<td><input type='text' class='input_texte_plus' name='nom_eq_type' ></td>\n";
				
				echo "<td>";
					
					echo "<select name='select_typemesure' id='select_typemesure' style='width:90px;' >";
										
						echo "<option value='1' >".htmlaccent('Ponctuel')."</option>";
						echo "<option value='2' >".htmlaccent('Cumul')."</option>";
				  
					echo "</select>";
					
				echo "</td>\n";
				
				echo "<td><input type='text' class='input_texte_xsmall' style='text-align: center;' name='ordre_type' ></td>\n";
				
				echo "<td><input type='checkbox' name='active_type' ></td>\n";
				
				echo "<td><input type='text' class='input_texte' name='type_color_border' style='width:80px;'></td>\n";
				
				echo "<td><input type='text' class='input_texte' name='type_color_background' style='width:80px;'></td>\n";

				echo "<td>";
					
					echo "<select name='select_typegraph' id='select_typegraph' style='width:90px;' >";
										
						echo "<option value='lines' >".htmlaccent('lines')."</option>";
						echo "<option value='bar' >".htmlaccent('bar')."</option>";
				  
					echo "</select>";
					
				echo "</td>\n";
				
				echo "<td>&nbsp;</td>";
			echo "<tr>";
			
		
		echo "</table>";

	echo "<hr>\n";
	echo "</div>\n";
	
	
echo "<hr>\n";
echo "</div>\n";
?>
