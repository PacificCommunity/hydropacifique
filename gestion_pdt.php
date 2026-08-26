<?php
/*  
----------------------------------------
Copyright (c) 2015 - Vai-Natura
----------------------------------------
*/

require('include/application_top.php');

$message_info = '';
$message_suprr_pdt = '';

if(isset($_GET['id']) && tep_not_null($_GET['id'])){require(DIR_WS_SUPPRIMER . 'suppr_pdt.php');}
if(isset($_POST['button_save'])){require(DIR_WS_FORMULAIRE . 'ctrl_pdt.php');}

require(DIR_WS_STRUCTURE . 'header_web.php');


echo "<body>";

require(DIR_WS_STRUCTURE . 'header.php'); // Bando Haut
include(DIR_WS_BOX . 'nav_accueil.php'); // Menu

echo "<div id='contour_general'>";	
	
	echo "<div id='contenu_centre'>";
	
		
		echo "<div id='contenu_box2'>";
		
			echo "<h1>";
				
				echo "<span>".htmlaccent('Configuration des Pas de Temps (exportation)')."</span>";	
				//echo button_return('gestion.php');
				
			echo "</h1>";
						
			
			if(tep_not_null($message_info)){echo "<div id='contenu_info'>".$message_info."</div>";}
			if(tep_not_null($message_suprr_pdt)){echo "<div id='contenu_info'>".$message_suprr_pdt."</div>";}
	
			echo "<hr>";
	
			//FORMULAIRE
			$lien_form = tep_href_link('gestion_pdt.php');
			$name_form = 'pdt';
			
			echo "<form name='" . $name_form . "' action='" . $lien_form . "' method='post' enctype='multipart/form-data'>";
	
			
			echo "<div id='onglet'>";
				echo "<ul id='menu_onglet'>";
				
					echo "<li id='onglet-0' class='actif'>".htmlaccent('Information')."</li>\n";
									
				echo "</ul>";
				
				echo "<div id='contenu-0' class='contenu'>";
				
					require(DIR_WS_STRUCTURE . 'form_pdt_1.php');
			
				echo "</div>";
				
			echo "</div>";
				
				
			//Boutton
			echo "<br>";
			
			echo "<input type='submit' class='button' name='button_save' value=\"Enregistrer\" />";
				
			
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
