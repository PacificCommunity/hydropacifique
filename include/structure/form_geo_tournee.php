<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Gestion des tournées
----------------------------------------
*/

$row = 0;

echo "<div id='onglet_contenu'>\n";

	echo "<div id='boite1' class='first'>\n";

		echo "<table id='table_tri' cellspacing='0' style=''>";
		
			echo "<thead>";
				echo "<tr>";
					echo "<th>".htmlaccent('Intitulé')."</th>";	
                    echo "<th>".htmlaccent('Description')."</th>";	
					echo "<th>&nbsp;</td>";
				echo "</tr>";
			echo "</thead>";	

			echo "<tr>";
				echo "<td class='lignevide'>&nbsp;</td>";
				echo "<td class='lignevide'>&nbsp;</td>";		
			echo "</tr>";
			
            // Mise en forme des lignes pour affichage des tournées
			if(isset($tournee_array))
			{
				foreach($tournee_array as $id => $nom)
				{				
					if(fmod($row,2)==0){$row_l="class='row1' onmouseover=\"this.className='row1hover';\" onmouseout=\"this.className='row1';\" ";} 
					else{$row_l="class='row2' onmouseover=\"this.className='row2hover';\" onmouseout=\"this.className='row2';\" ";} 
		
					echo "<tr ".$row_l." >";
						
						echo "<td class='t_cont_xl'>";
							echo "<input type='text' class='input_texte_plus' style='' name='tournee_nom_".$id."' value='".$tournee_array[$id]['nom']."'>\n";
						echo "</td>";

						echo "<td class='t_cont_xxl'>";
							echo "<input type='text' class='input_texte_450' style='' name='tournee_description_".$id."' value='".$tournee_array[$id]['description']."'>\n";
						echo "</td>";
						
						// supprimer
						echo "<td class='t_cont_s' style='text-align:center;'>";
							$lien_suppr = "gestion_geo.php?id_tr=".$id;
							echo "<img src='".DIR_WS_IMG_ICO."delete.png' style='width:20px;cursor:pointer;' title='".htmlaccent('Supprimer')."' onClick=\"confirm_suppr('".$lien_suppr."','la région hydrologique','".$tournee_array[$id]['nom']."');\">";
						echo "</td>\n";
					
					echo "</tr>";					
				}
			}
			
			
			// NEW DATA TOURNEE
			
			echo "<tr><td colspan='4' style='color:#000;font-size:14px;font-weight:bold;'>".htmlaccent('Ajouter une Tournée')."</td></tr>\n";
							
			  echo "<tr>";
			  
				echo "<td><input type='text' class='input_texte_plus' style='' name='tournee_nom' ></td>";
                echo "<td><input type='text' class='input_texte_450' style='' name='tournee_description' ></td>";
				
				echo "<td>&nbsp;</td>";
				
			echo "<tr>";
			
		
		echo "</table>";

	echo "<hr>\n";
	echo "</div>\n";
	
	
echo "<hr>\n";
echo "</div>\n";
?>
