<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Ce block permet la saisie rapide d'un JGE : hauteur d'eau et débit
Il est accessible à partir de la page data_jge.php 
----------------------------------------
*/
$today = date('d-m-Y'); 
$time = date('H:i');
$today_time = date('d-m-Y H:i');

$day = date('d');
$month = date('m');
$year = date('Y');

$display_box = '';

echo "<div id='box_jge_simple' class='block_view' >\n"; // style='background:transparent;'

	echo "<div id='cadre_view_2' style='width:800px;padding:10px;background-color:#FBF9F1;' >\n";

            echo "<p style='float:left;width:820px;height:40px;padding-left:8px;font-size:24px;font-weight:bold;color:#fff;margin:-10px;background-color:#000;'>";
                echo htmlaccent('Saisir le résultat d\'un Jaugeage');
            echo "</p>\n";  
	
		echo "<div id='cadre_limit' style='height:250px;margin-top: 0px;'>";	
        
            echo "<div id='cadre_titre' style='margin-bottom:5px;'>";	 

                // Nom Station
                echo "<div style='float:left;width:100%;margin-top:20px;margin-bottom:20px;'>\n";
                    
                    echo "<p style='float:left;width:90px;font-size:18px;font-weight:bold;'>".htmlaccent('Station : ')."</p>\n";	
                    echo "<input style='float:left;width:55%;padding:0;font-size:18px;border:0;background:none;' name='jge_station' id='jge_station' type='text' value=''  readonly>\n"; 
                            
                echo "</div>\n";

            echo "</div>\n";

            echo "<hr>";

            // Id JGE + Id Station
            echo "<input type='hidden' name='jge_id' id='jge_id' value=''/>";
            echo "<input type='hidden' name='jge_id_station' id='jge_id_station' value=''/>";

            echo "<div style='float:left;width:49%;'>";

                // Débit
                echo "<div style='float:left;width:40%;margin-right:5%;'>\n";
                            
                    echo "<p style='margin-top:-4px;font-size:14px;font-weight:bold;'>".htmlaccent('Débit [m<sup>3</sup>/s]')."</p>\n";	
                    echo "<input style='width:80px;height:15px;font-size:14px;' name='jge_debit' id='jge_debit' type='text' value=''  >\n"; 
                            
                echo "</div>\n";
                
                // Hauteur d'eau 
                echo "<div style='float:left;width:40%;'>\n";
                            
                    echo "<p style='font-size:14px;font-weight:bold;'>".htmlaccent('Hauteur d\'eau [cm]')."</p>\n";	
                    echo "<input style='width:80px;height:15px;font-size:14px;' name='jge_hauteur' id='jge_hauteur' type='text' value=''  >\n"; 
                            
                echo "</div>\n";

                echo "<hr>";
            
                // Date et Heure
                echo "<div style='float:left;width:100%;margin-top:10px;'>\n";

                    // Date
                    echo "<div style='float:left;width:40%;margin-right:5%;'>\n";
                    
                        echo "<p style='font-size:14px;font-weight:bold;'>".htmlaccent('Date (jj-mm-aaaa)')."</p>\n";	
                        echo "<input style='width:80px;' name='jge_date' id='jge_date' type='text' value='' >"; 

                    echo "</div>\n";

                    // Heure
                    echo "<div style='float:left;width:40%;'>\n";

                        echo "<p style='font-size:14px;font-weight:bold;'>".htmlaccent('Heure (hh:mm:ss)')."</p>";
                        echo "<input style='width:80px;' name='jge_heure' id='jge_heure' type='text' value='' >";

                    echo "</div>\n";
                            
                echo "</div>\n";


                    // Code Qualité
                    echo "<div style='float:left;margin-top:20px;'>\n";
                            
                    echo "<p style='float:left;width:100px;padding-top:5px;font-size:14px;font-weight:bold;color:#930000;'>".htmlaccent('Code Qualité')."</p>\n";	
                    
                    echo "<select name='select_jge_code_qual' id='select_jge_code_qual' style='float:left;width:90px;' >";
                                    
                        echo "<option value='0'>-</option>";
                            
                        $selected = '';									
                        
                        if(isset($code_qual_array))
                        {
                            foreach ($code_qual_array as $key => $value)
                            {
                                echo "<option value='".$key."' ".$selected." title='".$code_qual_array[$key]['nom_qualite_data']."'>".$code_qual_array[$key]['init_qualite_data']."</option>";
                            }
                        }
                    
                    echo "</select>";
                            
                echo "</div>\n";

            echo "</div>\n";

                
            echo "<div style='float:left;width:49%;'>";

                // Observation                                
                echo "<p style='float:left;font-size:14px;font-weight:bold;'>".htmlaccent('Observation')."</p>\n";	
                    
                echo "<textarea name='jge_obs' id='jge_obs' style='width:95%;height:70px;'></textarea>\n"; 


                echo "<div style='float:left;width:100%;margin-top:20px;'>\n";

                    echo "<input type='submit' style='float:left;' class='button' name='save_jge' value='Enregistrer' >";

                    echo "<input type='button' style='float:right;' class='button_close'  value='Annuler' onclick=\"document.getElementById('box_jge_simple').style.display='none';\" >";

                echo "</div>\n";    
                            
            echo "</div>\n";

            echo "</div>\n";
        
            echo "<hr>";
		
		echo "</div>\n";	
		
	echo "</div>\n";

echo "</div>\n";

?>


<script type="text/javascript">
	
    // Récupère le popup et le bouton qui l'ouvre
    // Le pb c'est qu'en cliquant dans le blanc on perd les modifications éventuelles non enregistrées
    /*
    var popupJGE = document.getElementById('cadre_view_2');
    
    var boxJGE = document.getElementById('box_jge_simple');
        
    // Ajoute un événement de clic au document
    document.addEventListener("click", function(event)
    {
        // Vérifie si l'élément cliqué est à l'intérieur ou à l'extérieur du popup
        if (event.target !== popupJGE && event.target === boxJGE) 
        {
            // Ferme le popup
            boxJGE.style.display = "none";
        }
    });
    */
    
		  
</script>