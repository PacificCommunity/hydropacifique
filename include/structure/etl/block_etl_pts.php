<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Ce block permet de modifier les corrdonnées d'un point : hauteur d'eau et débit
Il est accessible à partir de la page modif_etl.php 
----------------------------------------
*/

echo "<div id='box_elt_pts' class='block_view' style='width:350px;height:380px;margin-left:40%;background:transparent;'>\n"; // style='background:transparent;'

	echo "<div id='cadre_view_2' style='padding:0px;background-color:#FBF9F1;' >\n";

        echo "<div >";	

            echo "<p style='float:left;width:100%;height:30px;padding-left:8px;font-size:18px;font-weight:bold;color:#fff;margin:0px;background-color:#000;'>";
                echo htmlaccent('Modification du point');
            echo "</p>\n";  

        echo "</div>\n";
	
		echo "<div style='margin: 0 5%;'>";	
        
            echo "<div style='margin-bottom:5px;'>";	 

                // Référence ETL
                echo "<div style='float:left;margin-top:5px;margin-bottom:20px;'>\n";
                    
                    echo "<p style='float:left;margin-right:10px;font-size:20px;font-weight:bold;'>".htmlaccent('Courbe ETL : ')."</p>\n";	
                    echo "<input style='float:left;width:150px;padding:0;padding-top:1px;font-size:20px;border:0;background:none;' name='etl_trace' id='etl_trace' type='text' value=''  readonly>\n"; 
                            
                echo "</div>\n";

            echo "</div>\n";

            // Id ETL + Id Station
            echo "<div style='float:left;'>";

                // Hauteur d'eau 
                echo "<div style='float:left;'>\n";
                            
                    echo "<p style='float:left;width:150px;padding-top:6px;font-size:14px;font-weight:bold;'>".htmlaccent('X : Hauteur d\'eau [cm]')."</p>\n";	
                    echo "<input style='float:left;width:90px;height:20px;font-size:14px;' name='etl_hauteur' id='etl_hauteur' type='text' value=''  >\n"; 
                            
                echo "</div>\n";

                // Débit
                echo "<div style='float:left;margin-top:5px;'>\n";
                            
                    echo "<p style='float:left;width:150px;padding-top:6px;font-size:14px;font-weight:bold;'>".htmlaccent('Y : Débit [m<sup>3</sup>/s]')."</p>\n";	
                    echo "<input style='float:left;width:90px;height:20px;font-size:14px;' name='elt_debit' id='elt_debit' type='text' value=''  >\n"; 
                            
                echo "</div>\n";
            
                // Code qualité
                /*
                echo "<div style='float:left;width:100%;margin-top:10px;'>\n";

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
                */

                /*
                echo "<div style='float:left;margin-top:15px;'>\n";
                            
                    echo "<p style='float:left;font-size:14px;margin-right:10px;padding-top:3px;'>".htmlaccent('Supprimer le point')."</p>\n";	
                    echo "<input type='checkbox' id='new_pts' style='float:left;width:20px;height:20px;' >";	
                            
                echo "</div>\n";
                */

            echo "</div>\n";

                
            echo "<div style='float:left;margin-top:15px;'>";

                echo "<input type='submit' style='float:left;width:120px;' class='button' name='save_etl_pts' id='save_etl_pts' value='Enregistrer' >";

                echo "<input type='button' style='float:left;width:120px;margin-left:30px;' class='button_close'  value='Annuler' onclick=\"document.getElementById('box_elt_pts').style.display='none';\" >";
                        
            echo "</div>\n";

            echo "<hr>";
		
		echo "</div>\n";	
		
	echo "</div>\n";

echo "</div>\n";

?>


<script>

    var box_elt_pts = document.getElementById('box_elt_pts'); 

    // Ajout d'un gestionnaire d'événements pour la touche Echap
	document.addEventListener("keydown", function(event) 
	{
		if (event.key === "Escape") 
		{
			// Ferme le popup et le popup d'info s'il a été ouvert
			box_elt_pts.style.display = "none";
		}
    });

   
		  
</script>