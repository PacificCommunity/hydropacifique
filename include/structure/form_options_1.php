<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Formulaire pour les options 
*/
$row = 0;


echo "<div id='onglet_contenu'>\n";

	echo "<div id='boite1' class='first'>\n";

		echo "<h2 style='color:#000;margin-bottom: 15px;font-size:16px;'>".htmlaccent('Timeline')."</h2>\n";
					
		// Début de l'année hydrologique mois
		echo "<div id='boite_small' style='width:250px;'>\n";
			
			echo "<h2>".htmlaccent('Début de l\'année hydrologique (mois)')."</h2>\n";
				
			echo select_mois('annee_hydro',12);					
		echo "</div>\n";
		
		// Début de la journée hydrologique
		
		echo "<div id='boite_small' style='width:330px;'>\n";
			
			echo "<h2>".htmlaccent('Heure de début de la journée')."</h2>\n";
				
			echo select_heure('heure_jour',0);					

		echo "</div>\n";

	echo "<hr>\n";
	echo "</div>\n";	

	// -----------------------------------------------------------
	
	echo "<div style='width:100%;border-bottom:2px solid #176B87;'></div>\n";
	
	// -----------------------------------------------------------

	echo "<div id='boite1' class='first'>\n";

		echo "<h2 style='color:#000;margin-bottom: 15px;font-size:16px;'>".htmlaccent('Délai depuis depuis le dernier passage')."</h2>\n";
					
		// Dernier passage
		echo "<table id='table_tri' cellspacing='0' style='margin:0;'>";

			echo "<thead>";
				echo "<th>".htmlaccent('Période')."</th>";				
				echo "<th>".htmlaccent('Nb de jours')."</th>";
				echo "<th>&nbsp;</td>";
			echo "</thead>";	

			echo "<tr>";
				echo "<td colspan='3' style='height:10px;'>&nbsp;</td>";
			echo "</tr>";
			
			if(isset($tournee_periode_array))
			{
				foreach($tournee_periode_array as $key => $value)
				{
					$periode = $value['periode'];	
					$nb_days = $value['nb_days'];					
				
					if(fmod($row,2)==0){$row_l="class='row1' onmouseover=\"this.className='row1hover';\" onmouseout=\"this.className='row1';\" ";} 
					else{$row_l="class='row2' onmouseover=\"this.className='row2hover';\" onmouseout=\"this.className='row2';\" ";} 
		
					echo "<tr ".$row_l." >";
							
						echo "<td>";
							echo "<input type='text' class='input_texte_200' name='periode_".$key."' value='".$periode."'>\n";
						echo "</td>";

						echo "<td>";
							echo "<input type='text' class='input_texte_small' name='nbdays_".$key."' value='".$nb_days."'>\n";
						echo "</td>";
						
						// Suppression code qualité
						echo "<td class='t_icon'>";
							$lien_suppr = "gestion_options.php?id_periode=".$key;
							echo "<img src='".DIR_WS_IMG_ICO."delete.png' style='width:20px;cursor:pointer;' title='".htmlaccent('Supprimer')."' onClick=\"confirm_suppr('".$lien_suppr."','la période','".$periode."');\">";
						echo "</td>\n";
					
					echo "</tr>";

					$row++;
				}
			}
			
			
			// NEW Période
			
			echo "<tr><td colspan='4' style='color:#000;font-size:14px;font-weight:bold;'>".htmlaccent('Ajouter une période')."</td></tr>\n";
							
			echo "<tr>";
			
				
				echo "<td><input type='text' class='input_texte_200' name='periode' ></td>";
				echo "<td><input type='text' class='input_texte_small' name='nbdays' ></td>";
				echo "<td>&nbsp;</td>";
				
			echo "</tr>";
			
		echo "</table>";

	echo "<hr>\n";
	echo "</div>\n";	

	

echo "<hr>\n";
echo "</div>\n";

?>
