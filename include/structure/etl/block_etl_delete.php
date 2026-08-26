<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Ce block permet de dupliquer un ETL
Il est accessible à partir de la page modif_etl.php 
----------------------------------------
*/

echo "<div id='box_elt_delete' class='block_view' style='width:450px;height:200px;margin-left:35%;background:transparent;'>\n"; // style='background:transparent;'

    echo "<div id='cadre_view_2' style='padding:0px;background-color:#FBF9F1;' >\n";

        echo "<div>";	

            echo "<p style='float:left;width:100%;height:30px;padding-left:8px;font-size:18px;font-weight:bold;color:#fff;margin:0px;background-color:#000;'>";
                echo htmlaccent('Supprimer un ETL');
            echo "</p>\n";  

        echo "</div>\n";    
	
		echo "<div style='margin: 0 5%;margin-top:55px;'>";	

            // Choix de la courbe d'ETL à modifier  (à choisir)
            echo "<div style='width:100%;'>";

                echo "<p style='font-size:14px;font-weight:bold;'>".htmlaccent('Courbe ETL')."</p>\n";	

                echo "<select name='del_ref_etl' id='del_ref_etl' style='width:90%;height:35px;font-size:12px;'>";
					
                echo "</select>";

            echo "</div>";

            echo "<div style='float:left;margin-top:25px;'>";

                echo "<input type='submit' style='float:left;width:120px;' class='button' name='del_etl' id='del_etl' value='Supprimer' >";

                echo "<input type='button' style='float:left;width:120px;margin-left:30px;' class='button_close'  value='Annuler' 
                        onclick=\"document.getElementById('box_elt_delete').style.display='none';\" >";
                        
            echo "</div>\n";

            echo "<hr>";
		
		echo "</div>\n";	
		
	echo "</div>\n";

echo "</div>\n";

?>

<script>

    var box_elt_delete = document.getElementById('box_elt_delete');   

    // Ajout d'un gestionnaire d'événements pour la touche Echap
	document.addEventListener("keydown", function(event) 
	{
		if (event.key === "Escape") 
		{
			// Ferme le popup et le popup d'info s'il a été ouvert
			box_elt_delete.style.display = "none";
		}
    });


</script>