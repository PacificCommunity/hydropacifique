<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
*/

require('include/application_top.php');

$message_suprr_eq = '';
$row = 0;

$select_type_format = 0;
$where_and_type = '';
$sql_page = ''; // pour conserver la sélection si plusieurs pages d'affichage

// Suppression d'un import de données

if(isset($_GET['id']) && tep_not_null($_GET['id'])){require(DIR_WS_SUPPRIMER . 'suppr_eq.php');}


if(isset($_POST['select_eq_type']) && tep_not_null($_POST['select_eq_type']) && $_POST['select_eq_type']!=0)
{
	$select_type_format = $_POST['select_eq_type'];
	$where_and_type = "WHERE e.type_eq=".$select_type_format;
	$sql_page .= '&type_eq='.$select_type_format;
}

// Requête sql pour récupérer les données articles
$sql_eq = "SELECT DISTINCT e.id, e.designation, e.type_eq, e.fabricant, e.description FROM ".TABLE_EQUIPEMENT." e ".$where_and_type." ORDER BY e.designation";
$equipement_query = tep_db_query($sql_link,$sql_eq);
while ($equipement = tep_db_fetch_array($equipement_query))
{	
	$designation =  htmlaccent(html_entity_decode($equipement['designation']));
	
	$type_eq =  htmlaccent(html_entity_decode($equipement['type_eq']));
	$sql_eq_type = "SELECT DISTINCT * FROM ".TABLE_EQ_TYPE." WHERE id=".$type_eq;
	$eq_type_query = tep_db_query($sql_link,$sql_eq_type);
	$eq_type = tep_db_fetch_array($eq_type_query);
	
	$fabricant =  htmlaccent(html_entity_decode($equipement['fabricant']));
	$description =  htmlaccent(html_entity_decode($equipement['description']));
	
	
	$equipement_array[] = array('id' => $equipement['id'],
							   'designation' => $designation,
							   'type_eq' => $type_eq,
						   	   'designation_eq' => $eq_type['designation'],
							   'fabricant' => $fabricant,
							   'description' => $description);
	
}
require(DIR_WS_STRUCTURE . 'header_web.php');


echo "<body>";

require(DIR_WS_STRUCTURE . 'header.php'); // Bando Haut
include(DIR_WS_BOX . 'nav_accueil.php'); // Menu

echo "<div id='contour_general'>";
	
	
	echo "<div id='contenu_centre'>";
		
		
		echo "<div id='contenu_box2'>";
		
			echo "<h1>";
				
				echo "<span>".htmlaccent('Liste des formats de fichiers pouvant être importés')."</span>";
				echo button_pdf('export_pdf.php?type=list_eq');
				
			echo "</h1>";
			
			if(tep_not_null($message_suprr_eq)){echo "<div id='contenu_info'>".$message_suprr_eq."</div>";}
						
			// Tri des Type de mesure
			
			echo "<div id='contenu_option'>";
		
			echo "<div id='contenu_tri' >";
				
				
				$lien_form = tep_href_link('list_equipements.php');			
				echo "<form name='form_select_type' action='".$lien_form."' method='post' enctype='multipart/form-data'>";
					
					
				  echo "<table id='stats_select' cellspacing='0' style='width:500px;'>";
		  
					  echo "<tr>";
						  
						  echo "<td class='bold' style='width: 100px;'>".htmlaccent('Sélectionner : ')."</td>";
						  echo "<td>";
							  
							  echo "<select name='select_eq_type' id='select_eq_type' onchange='form_select_type.submit();'>";
									
									echo "<option value='0'>-</option>";
									
									$selected = '';
									// active_type est là pour n'afficher que les type de mesure qui sont exploités par le service
									
									$sql_eq_type = "SELECT DISTINCT * FROM ".TABLE_EQ_TYPE." WHERE active_type=1 ORDER BY designation ASC";
									$eq_type_query = tep_db_query($sql_link,$sql_eq_type);									
									while ($eq_type_tab = tep_db_fetch_array($eq_type_query))
									{			
										if($eq_type_tab['id'] == $select_type_format){$selected="selected";}	
										else{$selected = '';}											
										echo "<option value='".$eq_type_tab['id']."' ".$selected.">".htmlaccent(html_entity_decode($eq_type_tab['designation']))."</option>";
									}
								echo "</select>";
								
							  echo "<p style='width:150px'>".htmlaccent('Type de données')."</p>";
						  echo "</td>";
						 
						  
					  echo "</tr>";
					  
					  
				  echo "</table>";
				  
					//echo tep_draw_pull_down_menu_admin_root('rub', tep_get_rubrique_tree($table_rub,$table_rubcontent),'tous',$current_category_id,"onChange='javascript:tri_rub.submit();'");						
				echo "</form>";
				
			echo "<hr>";
			echo "</div>";	
			
		
		echo "<hr>";
		echo "</div>";
		
		// Fin Tri Type	
			
			
		echo "<table id='table_tri' cellspacing='0'>";
	
			echo "<thead>";
				echo "<tr>";
													
					echo "<th>".htmlaccent('Désignation')."</th>";
					echo "<th>".htmlaccent('Type de données')."</th>";
					echo "<th>".htmlaccent('Fabricant')."</th>";
					echo "<th>".htmlaccent('')."</th>";
			   echo "</tr>";
			echo "</thead>";
	
	
	
			//ligne vide dans le tableau		
				echo "<tr>";
					echo "<td>&nbsp;</td>";
					echo "<td>&nbsp;</td>";
					echo "<td>&nbsp;</td>";
					echo "<td>&nbsp;</td>";
				echo "</tr>";
				
									
											
					
				if(isset($equipement_array))
				{
					for($c=0;$c<sizeof($equipement_array);$c++)
					{	
						if(fmod($row,2)==0){$row_l="class='row1' onmouseover=\"this.className='row1hover';\" onmouseout=\"this.className='row1';\" ";} 
						else{$row_l="class='row2' onmouseover=\"this.className='row2hover';\" onmouseout=\"this.className='row2';\" ";} 
						
						
						echo "<tr ".$row_l." >";
						
							$lien_modif = "modif_equipement.php?ref=".$equipement_array[$c]['id'];	
							echo "<td class='t_cont'><a href='".$lien_modif."'>" . $equipement_array[$c]['designation'] . "</a></td>\n";
							
							$style_type='';
							if($equipement_array[$c]['type_eq']==1){$style_type="style='color:#336699;'";}
							if($equipement_array[$c]['type_eq']==2){$style_type="style='color:#3fa03d;'";}
							if($equipement_array[$c]['type_eq']==3){$style_type="style='color:#fe9b00;'";}
							if($equipement_array[$c]['type_eq']==4){$style_type="style='color:#D3732F;'";}
							if($equipement_array[$c]['type_eq']==5){$style_type="style='color:#C03000;'";}
							echo "<td class='t_cont' ".$style_type.">" . $equipement_array[$c]['designation_eq'] . "</td>\n";
							
							echo "<td class='t_cont'>" . $equipement_array[$c]['fabricant'] . "</td>\n";
							
								
							// supprimer
							echo "<td class='t_icon'>";
								$lien_suppr = "list_equipements.php?id=".$equipement_array[$c]['id'];
								echo "<img src='".DIR_WS_IMG_ICO."delete.png' style='width:16px;cursor:pointer;' title='".htmlaccent('Supprimer')."' onClick=\"confirm_suppr('".$lien_suppr."','le matériel','".$equipement_array[$c]['designation']."');\">";
							echo "</td>\n";
						
						echo "</tr>\n";
					}
				}
		
		echo "</table>";
			
		
	  echo "<hr>";
	  echo "</div>";
		
		
	echo "<hr>";
	echo "</div>";
	
	
echo "<hr>";
echo "</div>";
	require('include/application_bottom.php'); 
echo "</body>";

echo "</html>";

?>	
