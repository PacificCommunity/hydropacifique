<?php
/*  
----------------------------------------
Copyright (c) 2015 - Vai-Natura
----------------------------------------
*/


$row = 0;


echo "<div id='onglet_contenu'>\n";

	echo "<div id='boite1' class='first'>\n";

		$sql = "SELECT DISTINCT * FROM ".TABLE_EXPORT_INTERVAL." ORDER BY min";
		$pdt_query = tep_db_query($sql_link,$sql);
		
		echo "<table id='table_tri' cellspacing='0' style='width:40%;'>";
		
			echo "<tr>";
				echo "<td style='color:#ccc;font-size:14px;'>".htmlaccent('Libellé')."</td>";
				echo "<td style='color:#ccc;font-size:14px;'>".htmlaccent('Pas de temps (en min)')."</td>";
				echo "<td>&nbsp;</td>";
			echo "</tr>";
			
			
			while ($pdt = tep_db_fetch_array($pdt_query)) 
			{
				$libelle = post_secure($sql_link,$pdt['libelle']);
				$min = post_secure($sql_link,$pdt['min']);
				
				if(fmod($row,2)==0){$row_l="class='row1' onmouseover=\"this.className='row1hover';\" onmouseout=\"this.className='row1';\" ";} 
				else{$row_l="class='row2' onmouseover=\"this.className='row2hover';\" onmouseout=\"this.className='row2';\" ";} 
	
				echo "<tr ".$row_l." >";
						
					echo "<td>";
						echo "<input type='text' class='input_texte_plus' name='lib_".$pdt['id']."' value='".$libelle."'>\n";
					echo "</td>";
					
					
					echo "<td>";
						echo "<input type='text' class='input_texte_small' name='min_".$pdt['id']."' value='".$min."'>\n";
					echo "</td>";
					
					// supprimer
					echo "<td class='t_icon'>";
						$lien_suppr = "gestion_pdt.php?id=".$pdt['id'];
						echo "<img src='".DIR_WS_IMG_ICO."delete.png' style='width:16px;cursor:pointer;' title='".htmlaccent('Supprimer')."' onClick=\"confirm_suppr('" . $lien_suppr . "','le Pas de Temps','".$libelle."');\">";
					echo "</td>\n";
				
				echo "</tr>";					
			}
			
			
			
			echo "<tr><td colspan='2' style='color:#ccc;'>".htmlaccent('Ajouter un Pas de Temps')."</td></tr>\n";
							
							
			  echo "<tr>";
				echo "<td><input type='text' class='input_texte_plus' name='lib' ></td>\n";
				echo "<td><input type='text' class='input_texte_small' name='min'></td>\n";
				echo "<td>&nbsp;</td>";
			echo "<tr>";
			
		
		echo "</table>";

	echo "<hr>\n";
	echo "</div>\n";
	
	
echo "<hr>\n";
echo "</div>\n";
?>
