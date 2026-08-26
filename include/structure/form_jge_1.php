<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Formulaire Jaugeage - Onglet 1 - Informations générales
*/


echo "<div id='onglet_contenu'>\n";

    echo "<div id='boite1' class='first'>\n";

        // STATION
        echo "<div id='boite_small'>\n";
                
            echo "<h2>".htmlaccent('Station hydrométrique')."</h2>\n";
                        
            echo "<select name='select_station' id='select_station' style='width:350px;' >";// onchange='form_jge.submit();' >";
											  
                echo "<option value='0'>-</option>";
                    
                $selected = '';									
                if(isset($station_array))
                {
                    for($c=0;$c<sizeof($station_array);$c++)
                    {
                        if($station_array[$c]['id'] == $id_station){$selected="selected";}	
                        else{$selected = '';}											
                        echo "<option value='".$station_array[$c]['id']."' ".$selected." >".$station_array[$c]['code_station']." - ".$station_array[$c]['nom_station']."</option>";
                    }
                }  
            
            echo "</select>";
                    
        echo "</div>\n";

        // Date du Jaugeage
        echo "<div id='boite_small' style='width:130px;'>\n";
                
            echo "<h2>".htmlaccent('Date du jaugeage')."</h2>\n";
                        
            if($modif){echo "<input class='input_texte' style='width:80px;' name='date_jge' id='date_jge' value='".$date_jge."' type='text'  onclick=\"javascript:displayCalendar(document.forms[0].date_jge,'dd-mm-yyyy',this);\" >";}
			else{echo "<input class='input_texte' style='width:80px;' name='date_jge' id='date_jge' value='".$today."' type='text'  onclick=\"javascript:displayCalendar(document.forms[0].date_jge,'dd-mm-yyyy',this);\" >";}
                    
        echo "</div>\n";

        // Heure du Jaugeage
        echo "<div id='boite_small' style='width:130px;'>\n";
                    
            echo "<h2>".htmlaccent('Heure du jaugeage')."</h2>\n";
                    
            if($modif){echo tep_draw_input_field('heure_jge',$heure_jge,'class=\'input_texte_60\'');}
            else{echo tep_draw_input_field('heure_jge','','class=\'input_texte_60\'');}
                    
        echo "</div>\n";

        // Code Qualité
        echo "<div id='boite_small' style='width:130px;'>\n";
                    
            echo "<h2 style='color:#930000;'>".htmlaccent('Code Qualité')."</h2>\n";

            echo "<select name='select_code_qual' id='select_code_qual' style='width:100px;' >";// onchange='form_jge.submit();' >";
											  
                echo "<option value='0'>-</option>";
                    
                $selected = '';									
                
                if(isset($code_qual_array))
                {
                    foreach ($code_qual_array as $key => $value)
                    {
                        if($key == $code_qualite){$selected="selected";}	
                        else{$selected = '';}											
                        echo "<option value='".$key."' ".$selected." title='".$code_qual_array[$key]['nom_qualite_data']."'>".$code_qual_array[$key]['init_qualite_data']."</option>";
                    }
                }
            
            echo "</select>";
                    
        echo "</div>\n";

    echo "<hr>\n";
	echo "</div>\n";

    echo "<div id='boite1' class='first'>\n";

        // Distance du site (de la sonde ou de l'échelle Limnimétrique)
        echo "<div id='boite_small' style='width:140px;'>\n";
                    
            echo "<h2>".htmlaccent('Distance du site [m]')."</h2>\n";
                    
            if($modif){echo tep_draw_input_field('dist_site',$dist_site,'class=\'input_texte_60\'');}
            else{echo tep_draw_input_field('dist_site','','class=\'input_texte_60\'');}
                    
        echo "</div>\n";

        // Site (Amont, Aval, Pont, Station)
        echo "<div id='boite_small' style='width:110px;'>\n";
                
            echo "<h2>".htmlaccent('Site - Précision')."</h2>\n";

            echo "<select name='select_site_jge' id='select_site_jge' style='width:100px;' >";// onchange='form_jge.submit();' >";
											  
                echo "<option value='0'>-</option>";
                    
                $selected = '';									
                if(isset($data_jge_site_array))
                {
                    if(isset($data_jge_site_array))
                    {
                        foreach ($data_jge_site_array as $key => $value)
                        {
                            if($key == $id_sitejge){$selected="selected";}	
                            else{$selected = '';}											
                            echo "<option value='".$key."' ".$selected." title='".$data_jge_site_array[$key]['obs']."'>".$data_jge_site_array[$key]['titre']."</option>";
                        }
                    }
                }  
            
            echo "</select>";
                    
        echo "</div>\n";
    
        // GPS - X
        echo "<div id='boite_small'>\n";
                
            echo "<h2>".htmlaccent('Coordonnée X (GPS)')."</h2>\n";
                    
            if($modif){echo tep_draw_input_field('x_gps',$x_gps,'class=\'input_texte\'');}
            else{echo tep_draw_input_field('x_gps','','class=\'input_texte\'');}
                    
        echo "</div>\n";

        // GPS - Y
        echo "<div id='boite_small'>\n";
                
            echo "<h2>".htmlaccent('Coordonnée Y (GPS)')."</h2>\n";
                    
            if($modif){echo tep_draw_input_field('y_gps',$y_gps,'class=\'input_texte\'');}
            else{echo tep_draw_input_field('y_gps','','class=\'input_texte\'');}
                    
        echo "</div>\n";

        // Type de prise de mesure
        echo "<div id='boite_small' style='width:170px;'>\n";
                
            echo "<h2>".htmlaccent('Type de prise de mesure')."</h2>\n";

            echo "<select name='select_type_jge' id='select_type_jge' style='width:100px;' >";// onchange='form_jge.submit();' >";
											  
                echo "<option value='0'>-</option>";
                    
                $selected = '';									
                if(isset($data_jge_type_array))
                {
                    if(isset($data_jge_type_array))
                    {
                        foreach ($data_jge_type_array as $key => $value)
                        {
                            if($key == $id_typejge){$selected="selected";}	
                            else{$selected = '';}											
                            echo "<option value='".$key."' ".$selected." title='".$data_jge_type_array[$key]['obs']."'>".$data_jge_type_array[$key]['titre']."</option>";
                        }
                    }
                }  
            
            echo "</select>";
                    
        echo "</div>\n";
    
        // Type de Méthode (par points)
        echo "<div id='boite_small'>\n";
                
            echo "<h2>".htmlaccent('Méthode')."</h2>\n";

            echo "<select name='select_methode_jge' id='select_methode_jge' style='width:100px;' >";// onchange='form_jge.submit();' >";
                                            
                echo "<option value='0'>-</option>";
                    
                $selected = '';									
                if(isset($data_jge_methode_array))
                {
                    foreach ($data_jge_methode_array as $key => $value)
                    {
                        if($key == $id_methode){$selected="selected";}	
                        else{$selected = '';}											
                        echo "<option value='".$key."' ".$selected." title='".$data_jge_methode_array[$key]['obs']."'>".$data_jge_methode_array[$key]['titre']."</option>";
                    }
                }  
            
            echo "</select>";
                
        echo "</div>\n";       


    echo "<hr>\n";
	echo "</div>\n";

    // -----------------------------------------------------------
	
	echo "<div style='width:100%;border-bottom:2px solid #176B87;'></div>\n";
	
	// -----------------------------------------------------------
	
    // Résultats Généraux

    echo "<div id='boite1' class='first'>\n";

        // Hauteur
        echo "<div id='boite_small' style='width:140px;'>\n";
                    
            echo "<h2>".htmlaccent('Hauteur moy. [cm]')."</h2>\n";
                    
            if($modif){echo "<input class='input_texte_60' name='jge_hmoy' id='jge_hmoy' value='".$depouil_hmoy."' type='text' style='border: 0px;' readonly>";}
            else{echo "<input class='input_texte_60' name='jge_hmoy' id='jge_hmoy' value='' type='text' style='border: 0px;' readonly>";}
                    
        echo "</div>\n";

        // Débit
        echo "<div id='boite_small' style='width:140px;'>\n";
                    
            echo "<h2>".htmlaccent('Débit [m3/s]')."</h2>\n";
            
            if($modif){echo "<input class='input_texte_60' name='jge_q' id='jge_q' value='".$depouil_q."' type='text' style='border: 0px;' readonly>";}
            else{echo "<input class='input_texte_60' name='jge_q' id='jge_q' value='' type='text' style='border: 0px;' readonly>";}
                    
        echo "</div>\n";
    
    echo "<hr>\n";
    echo "</div>\n";

echo "<hr>\n";
echo "</div>\n";
?>