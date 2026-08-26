<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Popup de vérification avant suppression de fiche RA
----------------------------------------
*/
$day = date('d');
$month = date('m');
$year = date('Y');
$display_box = '';


echo "<div id='box_del_ra' class='block_view'"
   . " style='position:fixed;top:0;left:0;width:100%;height:100%;"
   . "background:rgba(0,0,0,0.45);z-index:9999;display:none;'>\n";


echo "</div>";

?>

<script>

	// Récupère l'overlay de la popup de suppression RA
	var box_del = document.getElementById('box_del_ra');

	// Ferme le popup quand on clique sur l'overlay (en dehors de la carte)
	document.addEventListener("click", function(event) {
		if (event.target === box_del) {
			box_del.style.display = "none";
		}
	});

	// Ferme le popup avec la touche Echap
	document.addEventListener("keydown", function(event)
	{
		if (event.key === "Escape")
		{
			box_del.style.display = "none";
		}
    });

</script>