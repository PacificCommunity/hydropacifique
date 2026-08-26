<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Onglet code Qualité
*/

$row = 0;

// Requête sur TYPE DE MESURE
$sql_moulinet = "SELECT DISTINCT id, num, fabricant, obs 
				FROM ".TABLE_MOULINET."
				ORDER BY num ASC";
$moulinet_query = tep_db_query($sql_link,$sql_moulinet);


echo "<div id='onglet_contenu'>\n";

	echo "<div id='boite1' class='first'>\n";

		echo "<table id='table_tri' cellspacing='0' >";
		
			echo "<thead>";
				echo "<tr>";
					echo "<th>".htmlaccent('Numéro')."</th>";				
					echo "<th>".htmlaccent('Fabricant')."</th>";
					echo "<th>".htmlaccent('Observation')."</th>";
					echo "<th style='text-align:center;' >&nbsp;</th>";
				echo "</tr>";
			echo "</thead>";

			//ligne vide dans le tableau		
						
			echo "<tr>";
				echo "<td colspan='4' style='height:10px;'>&nbsp;</td>";
			echo "</tr>";
			
			while($moulinet_tab = tep_db_fetch_array($moulinet_query))
			{
				$id = $moulinet_tab['id'];
				$num = htmlaccent(html_entity_decode($moulinet_tab['num']));
				$fabricant = htmlaccent(html_entity_decode($moulinet_tab['fabricant']));
				$obs = htmlaccent(html_entity_decode($moulinet_tab['obs']));
			
				if(fmod($row,2)==0){$row_l="class='row1' onmouseover=\"this.className='row1hover';\" onmouseout=\"this.className='row1';\" ";} 
				else{$row_l="class='row2' onmouseover=\"this.className='row2hover';\" onmouseout=\"this.className='row2';\" ";} 
	
				echo "<tr ".$row_l." >";
						
					echo "<td>";
						echo "<input type='text' class='input_texte_200' name='num_".$id."' value='".$num."'>\n";
					echo "</td>";
					
					echo "<td>";
						echo "<input type='text' class='input_texte_200' name='fabricant_".$id."' value='".$fabricant."'>\n";
					echo "</td>";
					
					echo "<td>";
						echo "<input type='text' class='input_texte_300' name='obs_".$id."' value='".$obs."'>\n";
					echo "</td>";
					
					// supprimer
					echo "<td class='t_icon'>";
						$lien_suppr = "gestion_eq_jaugeage.php?del_m=".$id;
						echo "<img src='".DIR_WS_IMG_ICO."delete.png' style='width:20px;cursor:pointer;' title='".htmlaccent('Supprimer')."' onClick=\"confirm_suppr('".$lien_suppr."','Moulinet','".$num."');\">";
					echo "</td>\n";
				
				echo "</tr>";
			}
			
			
			// NEW DATA TYPE
			
			echo "<tr><td colspan='4' style='color:#000;font-size:14px;font-weight:bold;'>".htmlaccent('Ajouter un Moulinet')."</td></tr>\n";
							
			  echo "<tr>";
			  
				echo "<td><input type='text' class='input_texte_200' name='num' ></td>";
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
