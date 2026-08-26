<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Pour Gérer la timeLine
*/

require('include/application_top.php');

$message_info = '';
$message_suprr_eq_jge = '';

if(isset($_POST['button_save'])){require(DIR_WS_FORMULAIRE . 'ctrl_options.php');}

//---------------------------------------------------------------
// TABLE SQL - Recupération DATA

// TABLE TYPE DATA (DEBIT, PLUIE, PIEZO, ...)
$sql_eq_type = "SELECT DISTINCT id_eq_type, nom_eq_type, type_color_border, type_color_background 
				FROM ".TABLE_EQ_TYPE." 
				WHERE active_eq_type=1 
				ORDER BY order_eq_type ASC";
$eq_type_query = tep_db_query($sql_link,$sql_eq_type);	
while ($eq_type = tep_db_fetch_array($eq_type_query))
{
	$eq_type_array[$eq_type['id_eq_type']] = array('nom_eq_type' => htmlaccent(html_entity_decode($eq_type['nom_eq_type'] ?? '')),
													'type_color_border' => htmlaccent(html_entity_decode($eq_type['type_color_border'] ?? '')),							
													'type_color_background' => htmlaccent(html_entity_decode($eq_type['type_color_background'] ?? ''))
													);
}

// TABLE DELAI
$sql_tournee_periode = "SELECT DISTINCT id, periode, nb_days
				FROM ".TABLE_TOURNEE_PERIODE." 
				ORDER BY nb_days ASC";
$tournee_periode_query = tep_db_query($sql_link,$sql_tournee_periode);	
while ($tournee_periode = tep_db_fetch_array($tournee_periode_query))
{
	$tournee_periode_array[$tournee_periode['id']] = array('periode' => htmlaccent(html_entity_decode($tournee_periode['periode'] ?? '')),					
															'nb_days' => html_entity_decode($tournee_periode['nb_days'] ?? '')
															);
}


require(DIR_WS_STRUCTURE . 'header_web.php');

echo "<body>";

require(DIR_WS_STRUCTURE . 'header.php'); // Bando Haut
include(DIR_WS_BOX . 'nav_accueil.php'); // Menu

echo "<div id='contour_general'>";

	echo "<div id='contenu_centre'>";
	
		echo "<div id='contenu_box2'>";
		
			//FORMULAIRE
			$lien_form = tep_href_link('gestion_options.php');
			$name_form = 'form_options';
			
			echo "<form name='".$name_form."' action='".$lien_form."' method='post' enctype='multipart/form-data'>";
				
				echo "<h1>";
					
					echo "<span>".htmlaccent('Options : Timeline / Alertes stations')."</span>";	

					echo "<input type='submit' class='button' name='button_save' style='float:right;margin-right:0px;' value='Enregistrer' />";
					
				echo "</h1>";		
				
				if(tep_not_null($message_info)){echo "<div id='contenu_info'>".$message_info."</div>";}
		
				
				echo "<div id='onglet'>";
					echo "<ul id='menu_onglet'>";
					
						echo "<li onClick=\"javascript:ChangeOnglet_2(1, 1, 'onglet-', 'contenu-');\" id='onglet-1' class='actif'>".htmlaccent('Paramètres')."</li>\n";

					echo "</ul>";
					
					echo "<div id='contenu-1' class='contenu'>";
					
						require(DIR_WS_STRUCTURE . 'form_options_1.php');
				
					echo "</div>";

					
				echo "</div>";
					
					
				//Boutton
				echo "<br>";
				
				echo "<input type='submit' class='button' name='button_save' value='Enregistrer' />";
				
			
			echo "</form>\n";
	
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
