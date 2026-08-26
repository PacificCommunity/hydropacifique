<?php
/*  
----------------------------------------
Copyright (c) 2015 - Vai-Natura
----------------------------------------
*/




echo "<div id='onglet_contenu'>\n";

	echo "<div id='boite1' class='first'>\n";
				
		echo "<div id='boite_small'>\n";		
		
			echo "<h2>".htmlaccent('Nom du agent')."</h2>\n";
				
			if($modif){echo "<input name='nom' id='nom' value='".$nom."' class='titre' type='text'>";}
			else{echo "<input name='nom' id='nom' value='' class='titre' type='text'>";}
		
		echo "</div>\n";				
		
				
	echo "<hr>\n";
	echo "</div>\n";
	
	echo "<div id='boite1'>\n";
				
		echo "<div id='boite_small'>\n";		
		
			echo "<h2>".htmlaccent('Prénom du agent')."</h2>\n";
				
			if($modif){echo "<input name='prenom' id='prenom' value='".$prenom."' class='titre' type='text'>";}
			else{echo "<input name='prenom' id='prenom' value='' class='titre' type='text'>";}
		
		echo "</div>\n";				
		
					
	
	echo "<hr>\n";
	echo "</div>\n";
	
	echo "<div id='boite1'>\n";
				
		echo "<div id='boite_small'>\n";		
		
			echo "<h2>".htmlaccent('Poste')."</h2>\n";
				
			if($modif){echo "<input name='poste' id='poste' value='".$poste."' class='titre' type='text'>";}
			else{echo "<input name='poste' id='poste' value='' class='titre' type='text'>";}
		
		echo "</div>\n";	
		
	echo "<hr>\n";
	echo "</div>\n";
	
	echo "<div id='boite1'>\n";
		
		echo "<div id='boite_small'>\n";		
		
			echo "<h2>".htmlaccent('Service')."</h2>\n";
				
			if($modif){echo "<input name='service' id='service' value='".$service."' class='titre' type='text'>";}
			else{echo "<input name='service' id='service' value='' class='titre' type='text'>";}
		
		echo "</div>\n";				
		
	
	echo "<hr>\n";
	echo "</div>\n";
	
	
echo "<hr>\n";
echo "</div>\n";
?>
