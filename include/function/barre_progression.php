<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
*/


function progression($indice)
{ 
	echo "<script>";
		echo "if (document.getElementById('box_bar')) {";
			echo "document.getElementById('box_bar').style.display = 'block';";
			echo "document.getElementById('pourcentage').style.width = '" . $indice . "%';";
		echo "}";
	echo "</script>";

	@ob_flush();
	@flush();
	@ob_flush();
	@flush();
} 



?>
