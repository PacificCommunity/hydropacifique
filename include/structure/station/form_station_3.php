<?php
/*  
----------------------------------------
Copyright (c) 2015 - Vai-Natura
----------------------------------------
*/


/*
if($modif)
{	
	// requête sql pour récupérer les données articles
	$sql = "SELECT DISTINCT * FROM ".$table_op;
	$where = " WHERE id=".$ref_id;
		
	$produit_query = tep_db_query($sql_link,$sql.$where);
	$produit = tep_db_fetch_array($produit_query);
	
	if(isset($produit))
	{	
		//référence code barre
		$ref_code_barre = htmlaccent(html_entity_decode($produit['ref_code_barre']));
		
		//référence magasin
		$ref_magasin = htmlaccent(html_entity_decode($produit['ref_magasin']));
		
		//zone du magasin
		$id_zone = htmlaccent(html_entity_decode($produit['id_zone']));
		
		//libelle
		$libelle = htmlaccent(html_entity_decode($produit['libelle']));
		
		//descriptiop
		$description = htmlaccent(html_entity_decode($produit['description']));
		
		//Id rubrique
		$rub_id =  htmlaccent(html_entity_decode($produit['rub_id']));
		
		//Id fournisseur
		$fournisseur_id =  htmlaccent(html_entity_decode($produit['fournisseur_id']));
	}	
}
*/

$sql_tech = "SELECT DISTINCT * FROM ".TABLE_AGENT;
$agent_query = tep_db_query($sql_link,$sql_tech);
while ($agent = tep_db_fetch_array($agent_query))
{
	$nom =  htmlaccent(html_entity_decode($agent['tech_nom']));
	$prenom =  htmlaccent(html_entity_decode($agent['tech_prenom']));
	
	
	$agent_array[] = array('id' => $agent['id'],
							   'nom' => $nom,
						   	   'prenom' => $prenom);
}

echo "<div id='onglet_contenu'>\n";

	echo "<div id='boite1' class='first'>\n";
	
		echo "<table id='table_tri' cellspacing='0' style='width:600px;'>";
				
			echo "<thead>";
				echo "<tr>";
													
					echo "<th>".htmlaccent('Type d\'intervention')."</th>";
					echo "<th>".htmlaccent('Nom du agent')."</th>";
					echo "<th>".htmlaccent('Date de l\'intervention')."</th>";
					echo "<th>&nbsp;</th>";
			   echo "</tr>";
			echo "</thead>";
			
			echo "<tr>";
				echo "<td>&nbsp;</td>";
				echo "<td>&nbsp;</td>";
				echo "<td>&nbsp;</td>";
				echo "<td>&nbsp;</td>";
			echo "</tr>";
			
			
			echo "<tr>";
													
				echo "<td class='t_cont'>";
					
					echo "<input class='input_texte_medium' name='type_intervention' id='type_intervention' value='' type='text'";
				
				echo "</td>\n";
				
				echo "<td class='t_cont'>";
					
					echo "<select name='nom_agent' >";
							
						echo "<option value=''>-</option>";
						if(isset($agent_array))
						{
							for($c=0;$c<sizeof($agent_array);$c++)
							{
								echo "<option value='".$agent_array[$c]['id']."' >".$agent_array[$c]['nom']." ".$agent_array[$c]['prenom']."</option>";
							}
						}
					
					echo "</select>";
				
				echo "</td>\n";
				
				echo "<td class='t_cont'>";
					
					echo "<input class='input_texte' name='date_intervention' id='date_intervention' value='' type='text' onFocus='this.blur()' onclick=\"javascript:displayCalendar(document.forms[0].date_intervention,'dd-mm-yyyy',this);\" >";
				
				echo "</td>\n";
				
				
				echo "<td>&nbsp;</td>";
									
			echo "</tr>\n";	
			
			
			echo "<tr>";
				echo "<td>&nbsp;</td>";
				echo "<td>&nbsp;</td>";
				echo "<td>&nbsp;</td>";
				echo "<td>&nbsp;</td>";
			echo "</tr>";
			
		echo "</table>";

			
			
		echo "<table id='table_tri' cellspacing='0' style='width:620px;background-color:#ededeb;'>";
			
			if($modif)
			{	
				$sql_intervention = "SELECT DISTINCT * FROM ".TABLE_INTERVENTION_STATION." WHERE id_station=".$station['id']." ORDER BY date_intervention DESC";
				$intervention_query = tep_db_query($sql_link,$sql_intervention);
				while($intervention = tep_db_fetch_array($intervention_query))
				{
					
					if(fmod($row,2)==0){$row_l="class='row1' onmouseover=\"this.className='row1hover';\" onmouseout=\"this.className='row1';\" ";} 
					else{$row_l="class='row2' onmouseover=\"this.className='row2hover';\" onmouseout=\"this.className='row2';\" ";} 
					
					
					echo "<tr ".$row_l." >";
													
						echo "<td class='t_cont' style='padding-left:10px;'>";
							echo htmlaccent(html_entity_decode($intervention['type_intervention']));
						echo "</td>\n";
						
						echo "<td class='t_cont' style='text-align:right;padding-right:30px;'>";
						
							
							$sql_inter_techn = "SELECT DISTINCT * FROM ".TABLE_AGENT." WHERE id=".$intervention['agent_intervention'];
							$inter_techn_query = tep_db_query($sql_link,$sql_inter_techn);
							$inter_techn = tep_db_fetch_array($inter_techn_query);
							
							if(isset($inter_techn['id'])){echo htmlaccent(html_entity_decode($inter_techn['tech_prenom'].' '.$inter_techn['tech_nom']));}
							else{echo '-';}
						
						echo "</td>\n";
						
						echo "<td class='t_cont' style='text-align:right;padding-right:30px;'>";
							echo dateus_fr($intervention['date_intervention']);
						echo "</td>\n";
						
						echo "<td style='text-align:right;'>";
							$lien_suppr = "list_stations.php?del3=".$inter_techn['id'];
							echo "<img src='".DIR_WS_IMG_ICO."delete.png' style='width:16px;' title='".htmlaccent('Supprimer')."' onClick=\"confirm_suppr('" . $lien_suppr . "','l\'intervention','" . $intervention['type_intervention'] . "');\">";
						echo "</td>\n";
											
					echo "</tr>\n";	
					
				}
			}
		
		
		
		echo "</table>";
	
	echo "</div>\n";
	
echo "<hr>\n";
echo "</div>\n";
?>
