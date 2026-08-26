<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Ce block permet de générer de nouvelles chroniques à partr de chroniques existantes (PJ, PM, PA, QJ, QM, QA, ...)
----------------------------------------
*/
$today = date('d-m-Y'); 
$time = date('H:i');
$today_time = date('d-m-Y H:i');

$day = date('d');
$month = date('m');
$year = date('Y');

$periode_transf[1] = 'DAY';
$periode_transf[2] = 'MONTH';
$periode_transf[3] = 'YEAR';

$display_box = '';

echo "<div id='box_generation_chron' class='block_view' >\n";

    echo "<div id='cadre_view_2' style='width:800px;padding:10px;background-color:#FBF9F1;' >\n";

        echo "<p style='float:left;width:820px;height:40px;padding-left:8px;font-size:24px;font-weight:bold;color:#fff;margin:-10px;background-color:#000;'>";
            echo htmlaccent('Générer une nouvelle Chronique');
        echo "</p>\n";  
	
		echo "<div id='cadre_limit' style='height:250px;margin-top: 0px;'>";	

            // Titre nom Station
            echo "<div style='float:left;width:100%;margin-top:20px;margin-bottom:20px;'>\n";
                        
                echo "<p style='float:left;width:90px;font-size:18px;font-weight:bold;'>".htmlaccent('Station : ')."</p>\n";	
                echo "<input style='float:left;width:75%;padding:0;font-size:18px;border:0;background:none;' name='titre_station' id='titre_station' type='text' value='' readonly>\n"; 
                        
            echo "</div>\n";

            echo "<div style='float:left;width:100%;margin-top:20px;margin-bottom:20px;'>\n";
                            
                    echo "<p style='float:left;width:200px;padding-top:5px;font-size:14px;font-weight:bold;'>";
                        echo htmlaccent('Chronique de données initiale : ');
                    echo "</p>\n";	
                    
                    echo "<select name='select_chron' id='select_chron' style='float:left;width:200px;' >";
                         
                    echo "</select>";

            echo "</div>\n";

            echo "<div style='float:left;width:100%;margin-bottom:20px;'>\n";
                            
                    echo "<p style='float:left;width:200px;padding-top:5px;font-size:14px;font-weight:bold;'>";
                        echo htmlaccent('Transformation : ');
                    echo "</p>\n";	
                    
                    echo "<select name='select_chron_new' id='select_chron_new' style='float:left;width:120px;' >";

                        for($p=1;$p<=3;$p++)
                        {
                            echo "<option value='".$p."' ".$selected." >by ".$periode_transf[$p]."</option>";
                        }
                         
                    echo "</select>";

            echo "</div>\n";

            echo "<div style='float:left;width:60%;margin-top:20px;'>\n";

                echo "<input type='submit' style='float:left;' class='button' name='save_jge' value='Enregistrer' >";

                echo "<input type='button' style='float:right;' class='button_close'  value='Annuler' onclick=\"document.getElementById('box_generation_chron').style.display='none';\" >";

            echo "</div>\n";  



        
        echo "</div>\n";
        
        
           
		
		
		
	echo "</div>\n";
	

echo "</div>\n";

?>


<script type="text/javascript">
	
    // Récupère le popup et le bouton qui l'ouvre
    var popupGen = document.getElementById('cadre_view_2');
    var boxGen = document.getElementById('box_generation_chron');
        
    // Ajoute un événement de clic au document
    document.addEventListener("click", function(event)
    {
        // Vérifie si l'élément cliqué est à l'intérieur ou à l'extérieur du popup
        if (event.target !== popupGen && event.target === boxGen) 
        {
            // Ferme le popup
            boxGen.style.display = "none";
        }
    });
    
		  
</script>