<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
*/


// Fermeture de session 

if($autorisation){regenerer_id($sql_link);}


tep_db_close($sql_link);
tep_session_end();

?>

<script type="text/javascript">
		
	infoMsg = document.getElementById('contenu_info');
	if(infoMsg)
	{
		infoMsg.addEventListener('click', function() {
			infoMsg.style.display = 'none';
		});

		// Ajout d'un gestionnaire d'événements pour la touche Echap
		document.addEventListener("keydown", function(event) 
		{
			if (event.key === "Escape") 
			{
				// Ferme le popup et le popup d'info s'il a été ouvert
				infoMsg.style.display = "none";
			}
		});

	}
	

	
	
</script>