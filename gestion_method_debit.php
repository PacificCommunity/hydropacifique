<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
*/

require('include/application_top.php');

$message_info = '';
$message_suprr_type = '';

if(isset($_GET['id']) && tep_not_null($_GET['id'])){require(DIR_WS_SUPPRIMER . 'suppr_method_debit.php');}
if(isset($_POST['button_save'])){require(DIR_WS_FORMULAIRE . 'ctrl_method_debit.php');}

require(DIR_WS_STRUCTURE . 'header_web.php');


echo "<body>";

echo "<div id='contour_general'>";


	// en-tête 
	require(DIR_WS_STRUCTURE . 'header.php'); 

	include(DIR_WS_BOX . 'nav_accueil.php'); 
	
	
	echo "<div id='contenu_centre'>";
	
		
		echo "<div id='contenu_box2'>";
		
			echo "<h1>";
				
				echo "<span>".htmlaccent('Configuration des Méthodes de Mesure de Débit')."</span>";	
				//echo button_return('gestion.php');
				
			echo "</h1>";
						
			
			if(tep_not_null($message_info)){echo "<div id='contenu_info'>".$message_info."</div>";}
			if(tep_not_null($message_suprr_type)){echo "<div id='contenu_info'>".$message_suprr_type."</div>";}
	
			echo "<hr>";
	
			//FORMULAIRE
			$lien_form = tep_href_link('gestion_method_debit.php');
			$name_form = 'method';
			
			echo "<form name='" . $name_form . "' action='" . $lien_form . "' method='post' enctype='multipart/form-data'>";
	
			
			echo "<div id='onglet'>";
				echo "<ul id='menu_onglet'>";
				
					echo "<li id='onglet-0' class='actif'>".htmlaccent('Information')."</li>\n";
									
				echo "</ul>";
				
				echo "<div id='contenu-0' class='contenu'>";
				
					require(DIR_WS_STRUCTURE . 'form_method_debit_1.php');
			
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
