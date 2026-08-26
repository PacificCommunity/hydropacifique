<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Formulaire des fiches RA
Profil piézométrique
----------------------------------------
*/
$today = date('d-m-Y'); 
$time = date('H:i');
$today_time = date('d-m-Y H:i');

$day = date('d');
$month = date('m');
$year = date('Y');

$display_box = '';

echo "<div id='box_ra_piezoprofil' class='block_view' >\n";


echo "</div>\n";

?>


<script type="text/javascript">
	
    // Récupère le popup et le bouton qui l'ouvre
    var popup2 = document.getElementById('cadre_view_2');
    var box2 = document.getElementById('box_ra_piezoprofil');
        
    // Ajoute un événement de clic au document
    document.addEventListener("click", function(event)
    {
        // Vérifie si l'élément cliqué est à l'intérieur ou à l'extérieur du popup
        if (event.target !== popup2 && event.target === box2) 
        {
            // Ferme le popup
            box2.style.display = "none";
        }
    });

    
		  
</script>