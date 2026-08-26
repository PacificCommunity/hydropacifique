<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
*/

echo "<div id='onglet_contenu'>\n";

	//Station
	echo "<div id='boite1' class='first'>\n";
				
		echo "<div id='boite_small'>\n";		
		
			echo "<h2>".htmlaccent('Désignation du format')."</h2>\n";
				
			if($modif){echo "<input name='designation' id='designation' value='".$designation."' class='titre' type='text'>";}
			else{echo "<input name='designation' id='designation' value='' class='titre' type='text'>";}
		
		echo "</div>\n";				
		
						
	echo "<hr>\n";
	echo "</div>\n";
	
	
	$sql_eq_type = "SELECT DISTINCT * FROM ".TABLE_EQ_TYPE." WHERE active_type=1";
	$eq_type_query = tep_db_query($sql_link,$sql_eq_type);
	
	
	echo "<div id='boite1' class='first'>\n";
				
					
		echo "<div id='boite_small'>\n";
			
			echo "<h2>".htmlaccent('Type de données')."</h2>\n";
				
			echo "<select name='type_eq' id='type_eq' onchange='format_file();'>";
				$ee=0;
				while($eq_type = tep_db_fetch_array($eq_type_query))
				{
					
					$selected = '';	
					if($ee==0){$interval_type = $eq_type['interval_type'];}		
					if($eq_type['id'] == $type_eq)
					{
						$selected = 'selected';
						$interval_type = $eq_type['interval_type'];
					}
					
					echo "<option value='".$eq_type['id']."_".$eq_type['interval_type']."' ".$selected.">".htmlaccent($eq_type['designation'])."</option>";
					$ee++;
				}
			echo "</select>";	
				
						
		echo "</div>\n";
	
		echo "<div id='boite_small'>\n";
			
			echo "<h2>".htmlaccent('Origine / Fabricant')."</h2>\n";
				
			if($modif){echo tep_draw_input_field('fabricant',$fabricant,'class=\'input_texte_m\'');}
			else{echo tep_draw_input_field('fabricant','','class=\'input_texte_m\'');}
						
		echo "</div>\n";	
				
	
	echo "<hr>\n";
	echo "</div>\n";
	
	
	
	//Description						
	echo "<div id='boite1'>\n";
	echo "<h2>".htmlaccent('Description')."</h2>\n";
	
		if($modif){echo "<textarea name='description' style='width:90%;'>".$description."</textarea>\n";}
		else{echo "<textarea name='description' style='width:90%;'></textarea>\n";}
	
	echo "<hr>\n";
	echo "</div>\n";
	
	
	
echo "<hr>\n";
echo "</div>\n";
?>
