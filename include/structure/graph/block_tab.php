<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Ce block permet d'afficher les derniers RA entrés dans la plateforme
Cette fenêtre s'affiche quand on clique sur un lien d'information dans la page index.php
----------------------------------------
*/

echo "<div id='box_stats' class='block_view' >\n"; // style='background:transparent;'

	echo "<div id='cadre_view_2' style='float:left;width:80%;margin-top:20px;margin-left:10%;padding:0px;' >\n";

        echo "<div style='float:left;width:100%;height:90px;padding-top:5px;color:#fff;background-color:#000;'>";

            echo "<div id='title_box'style='float:left;font-size:16px;margin-left:5px;'></div>";
            
			echo "<div style='float:right;'>";
				echo "<span id='button_close' style='float:right;font-size:20px;font-weight:bold;margin-right:15px;cursor:pointer;' title='Fermer'>X</span>";
			echo "</div>";

        echo "</div>\n"; 
		
		echo "<hr>";
	
		echo "<div id='cadre_limit' style='width:100%;height:70vh;margin-top:10px;padding:0;overflow-x:hidden;overflow-y:hidden;'>";	

			echo "<div id='menu_stats' style='float:left;width:250px;margin:0 10px;'>";
			echo "</div>";

            // Cadre d'affichage
            echo "<div id='cadre_stats' style='float:none;width:auto;padding-right:12px;height:68vh;overflow-y : auto;'>";	

				echo "<div id='general_stats' class='content_stats' style='margin-top:0px;padding-bottom: 15px;'>";
				echo "</div>";

				echo "<div id='contenu_stats_graph' class='content_stats' >";					
				echo "</div>";

				echo "<div id='contenu_stats' class='content_stats' >";
				echo "</div>";
            
                echo "<div id='cadre_wait_stats' class='content_stats' >";
					echo "<div style='width:100%;margin-bottom:10px;height:50px;text-align:center;'>";	   
						echo "<img src='".DIR_WS_IMG."wait.gif' style='width:50px;'>";                 
					echo "</div>";
				echo "</div>";
            
            echo "</div>";

		echo "</div>\n";	
		
	echo "</div>\n";

echo "</div>\n";

?>

<script>

    // Ajoute un événement de clic au document
	document.addEventListener("click", function(event)
	{
		// Vérifie si l'élément cliqué est le bouton de fermeture
        if (event.target.id === 'button_close') 
		{
            // Ferme le popup et le popup d'info s'il a été ouvert
            boxStats.style.display = "none";
        } 

		// Vérifie si l'élément cliqué est à l'intérieur ou à l'extérieur du popup
		if (event.target === boxStats) 
		{
			// Ferme le popup et le popup d'info s'il a été ouvert
			boxStats.style.display = "none";
		}
	});

    // Ajout d'un gestionnaire d'événements pour la touche Echap
	document.addEventListener("keydown", function(event) 
	{
		if (event.key === "Escape") 
		{
			// Ferme le popup et le popup d'info s'il a été ouvert
			boxStats.style.display = "none";
		}
    });
    


</script>