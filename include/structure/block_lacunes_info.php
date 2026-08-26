<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Ce block permet d'afficher la listes des lacunes qui vont s'afficher sur un graphique
Cette fenêtre s'affiche quand on clique sur un boutton Tableau des Lacunes dans l'en-tête des graphiques
----------------------------------------
*/

echo "<div id='box_lacunes_info' class='block_view' style='position:absolute;max-width:50%;height:500px;top:20px;left:15%;background:none;'>\n";

    echo "<div id='cadre_view_2' style='float:left;width:100%;padding:0px;margin:0;' >\n";

        echo "<p  id='title_box_lac' 
                        style='float:left;width:100%;padding:15px 0;
                                font-size:16px;font-weight:bold;
                                color:#000;background-color:#f5f5f5;'>";

            echo "<span id='title_box_span' style='margin-left:15px;'>";
                echo TEXT_LAC_HEADER_TITLE;
            echo "</span>";

            echo "<span id='button_close_lac' style='float:right;margin-right:15px;cursor:pointer;' title='Fermer'>X</span>";
        echo "</p>\n";  
	
		echo "<div id='cadre_tab_lacune' style='height:100%;margin-top: 0px;padding:10px 5px;'>";	
		echo "</div>\n";	
		
	echo "</div>\n";

echo "</div>\n";

?>


<script>

    document.addEventListener('DOMContentLoaded', function() 
    {
        const boxLac = document.getElementById('box_lacunes_info');            

        // Ajoute un événement de clic au document
        document.addEventListener("click", function(event)
        {
            // Vérifie si l'élément cliqué est le bouton de fermeture
            if (event.target.id === 'button_close_lac') 
            {
                // Ferme le popup et le popup d'info s'il a été ouvert
                boxLac.style.display = "none";
            } 

            // Vérifie si l'élément cliqué est à l'intérieur ou à l'extérieur du popup
            if (event.target === boxData) 
            {
                // Ferme le popup et le popup d'info s'il a été ouvert
                boxLac.style.display = "none";
            }
        });

        // Ajout d'un gestionnaire d'événements pour la touche Echap
        document.addEventListener("keydown", function(event) 
        {
            if (event.key === "Escape") 
            {
                // Ferme le popup et le popup d'info s'il a été ouvert
                boxLac.style.display = "none";
            }
        });

    });
    

</script>