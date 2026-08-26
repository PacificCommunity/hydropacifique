<?php
/*  
----------------------------------------
Copyright (c) 2015 - Vai-Natura
----------------------------------------
*/

require('include/application_top.php');

$message_info = '';
$row = 0;
$reference = '';
$libelle = '';
$type_eq = 0;
$interval_type = 0;
$champ_datefirst = 0;
$champ_dateend = 0;
$today = date('d-m-Y'); 
$today_us = date('Y-m-d'); 

$modif=false;

if(isset($_GET['ref']) && tep_not_null($_GET['ref'])){$ref_id = mysqli_real_escape_string($sql_link,trim(addslashes($_GET['ref'])));$modif=true;}
if(isset($_POST['button_save'])){require(DIR_WS_FORMULAIRE . 'ctrl_equipement.php');}

if($modif)
{
	$sql = "SELECT DISTINCT * FROM ".TABLE_EQUIPEMENT;
	$where = " WHERE id=".$ref_id;
	$eq_query = tep_db_query($sql_link,$sql.$where);
	$equipement = tep_db_fetch_array($eq_query);

	$designation = post_secure($sql_link,$equipement['designation']);
	$type_eq = post_secure($sql_link,$equipement['type_eq']);
	$fabricant = post_secure($sql_link,$equipement['fabricant']);
	$description = $equipement['description'];
	
	$ext_file = post_secure($sql_link,$equipement['ext_file']);
	$qte = post_secure($sql_link,$equipement['qte']);
	$format_eq = $equipement['format_eq'];
	$champ_datefirst = post_secure($sql_link,$equipement['champ_datefirst']);
	$champ_dateend = post_secure($sql_link,$equipement['champ_dateend']);
}


require(DIR_WS_STRUCTURE . 'header_web.php');


echo "<body>";

echo "<div id='contour_general'>";


	// en-tête 
	require(DIR_WS_STRUCTURE . 'header.php'); 

	include(DIR_WS_BOX . 'nav_accueil.php'); 
	
	
	echo "<div id='contenu_centre'>";
		
		
		echo "<div id='contenu_box2'>";
		
			if($modif){echo "<h1><span>".htmlaccent('Format de fichier : '.$designation)."</span></h1>";}
			else{echo "<h1><span>".htmlaccent('Nouvel format de fichier')."</span></h1>";}
			
			
			if(tep_not_null($message_info)){echo "<div id='contenu_info'>".$message_info."</div>";} 
	
			echo "<hr>";
	
			//FORMULAIRE
			if($modif){$lien_form = tep_href_link('modif_equipement.php?ref='.$ref_id);}
			else{$lien_form = tep_href_link('modif_equipement.php');}
			$name_form = 'produit';
			
			echo "<form name='" . $name_form . "' action='" . $lien_form . "' method='post' enctype='multipart/form-data'>";
	
			
				echo "<div id='onglet'>";
					echo "<ul id='menu_onglet'>";
					
						if($modif)
						{
							echo "<li onClick=\"javascript:ChangeOnglet_2(1, 2, 'onglet-', 'contenu-');\" id='onglet-1' class='actif'>".htmlaccent('Description')."</li>\n";
							echo "<li onClick=\"javascript:ChangeOnglet_2(2, 2, 'onglet-', 'contenu-');\" id='onglet-2'>".htmlaccent('Format du fichier brut')."</li>\n";
						}
						else
						{
							echo "<li><a href=\"javascript:ChangeOnglet_2(1, 1, 'onglet-', 'contenu-');\" id='onglet-1'>".htmlaccent('Description')."</a></li>\n";
						}
						
						
										
					echo "</ul>";
					
					echo "<div id='contenu-1' class='contenu'>";
					
						require(DIR_WS_STRUCTURE . 'form_materiel_1.php');
				
					echo "</div>";
				
					if($modif)
					{
						echo "<div id='contenu-2' class='contenu' style='display:none;'>";
					
							require(DIR_WS_STRUCTURE . 'form_materiel_2.php');
				
						echo "</div>";
					}
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
