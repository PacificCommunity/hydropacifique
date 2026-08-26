<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Ce block permet de dupliquer un ETL
Il est accessible à partir de la page modif_etl.php 
----------------------------------------
*/

echo "<div id='box_elt_duplic' class='block_view' style='width:450px;height:300px;margin-left:35%;background:transparent;'>\n"; // style='background:transparent;'

    echo "<div id='cadre_view_2' style='padding:0px;background-color:#FBF9F1;' >\n";

        echo "<div>";	

            echo "<p style='float:left;width:100%;height:30px;padding-left:8px;font-size:18px;font-weight:bold;color:#fff;margin:0px;background-color:#000;'>";
                echo htmlaccent('Dupliquer un ETL');
            echo "</p>\n";  

        echo "</div>\n";    
	
		echo "<div style='margin: 0 5%;margin-top:55px;'>";	

            // Choix de la courbe d'ETL à modifier  (à choisir)
            echo "<div style='width:100%;'>";

                echo "<p style='font-size:14px;font-weight:bold;'>".htmlaccent('Courbe ETL')."</p>\n";	

                echo "<select name='duplic_ref_etl' id='duplic_ref_etl' style='width:90%;height:35px;font-size:12px;' onchange='duplicUpdateFieldsDate(this.value)'>";
					
                echo "</select>";

            echo "</div>";


            // Domaine de validité et densité de points
            echo "<div style='width:100%;margin-top:25px;'>";

                    echo "<p style='font-size:14px;font-weight:bold;'>".htmlaccent('Début période')."</p>\n";	

                    echo "<table style='font-size:12px;'>";

                        echo "<tr>";

                            echo "<td style='width:120px;'>".htmlaccent('Date (dd-mm-yyyy)')."</td>";
                            echo "<td style='width:120px;'>".htmlaccent('Heure (hh:mm:ss)')."</td>";

                        echo "</tr>";

                        echo "<tr>";
                            echo "<td>";
                                echo "<input style='width:90px;height:20px;font-size:14px;' id='duplic_date_debut_periode' type='text' value=''  >\n"; 
                            echo "</td>";
                            echo "<td>";
                                echo "<input style='width:90px;height:20px;font-size:14px;' id='duplic_heure_debut_periode' type='text' value=''>\n"; 
                            echo "</td>";
                        echo "</tr>";

                    echo "</table>";
                            
                    echo "<p style='font-size:14px;font-weight:bold;margin-top:10px;'>".htmlaccent('Fin période')."</p>\n";	

                    echo "<table style='font-size:12px;'>";

                        echo "<tr>";

                            echo "<td style='width:120px;'>".htmlaccent('Date (dd-mm-yyyy)')."</td>";
                            echo "<td style='width:120px;'>".htmlaccent('Heure (hh:mm:ss)')."</td>";

                        echo "</tr>";

                        echo "<tr>";
                            echo "<td>";
                                echo "<input style='width:90px;height:20px;font-size:14px;' id='duplic_date_fin_periode' type='text' value=''  >\n"; 
                            echo "</td>";
                            echo "<td>";
                                echo "<input style='width:90px;height:20px;font-size:14px;' id='duplic_heure_fin_periode' type='text' value=''>\n"; 
                            echo "</td>";
                        echo "</tr>";

                    echo "</table>";

            echo "</div>\n";
                
            echo "<div style='float:left;margin-top:25px;'>";

                echo "<input type='submit' style='float:left;width:120px;' class='button' name='duplic_etl' id='duplic_etl' value='Dupliquer' >";

                echo "<input type='button' style='float:left;width:120px;margin-left:30px;' class='button_close'  value='Annuler' 
                        onclick=\"document.getElementById('box_elt_duplic').style.display='none';\" >";
                        
            echo "</div>\n";

            echo "<hr>";
		
		echo "</div>\n";	
		
	echo "</div>\n";

echo "</div>\n";

?>


<script>

    var box_elt_duplic = document.getElementById('box_elt_duplic'); 

    // Ajout d'un gestionnaire d'événements pour la touche Echap
	document.addEventListener("keydown", function(event) 
	{
		if (event.key === "Escape") 
		{
			// Ferme le popup et le popup d'info s'il a été ouvert
			box_elt_duplic.style.display = "none";
		}
    });

	
    duplicDateDebut = document.getElementById('duplic_date_debut_periode');
    duplicHeureDebut = document.getElementById('duplic_heure_debut_periode');
    duplicDateFin = document.getElementById('duplic_date_fin_periode');
    duplicHeureFin = document.getElementById('duplic_heure_fin_periode');

    // Fonction pour une gestion fluide des champs dans les box
    function duplicUpdateFieldsDate(selectedValue) 
    {
        if (!selectedValue) return;

        const idETL = selectedValue.split('_')[0];
        
        if (ETL_array[idETL]) 
        {
            duplicDateDebut.value = ETL_array[idETL].datetime_first.split(' ')[0];
            duplicHeureDebut.value = ETL_array[idETL].datetime_first.split(' ')[1];
            duplicDateFin.value = ETL_array[idETL].datetime_end.split(' ')[0];
            duplicHeureFin.value = ETL_array[idETL].datetime_end.split(' ')[1];
        } 
        else 
        {
            duplicDateDebut.value = '';
            duplicHeureDebut.value = '';
            duplicDateFin.value = '';
            duplicHeureFin.value = '';
        }
    }    
		  
</script>