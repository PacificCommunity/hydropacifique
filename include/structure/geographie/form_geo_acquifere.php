<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Gestion des Acquifères
----------------------------------------
*/



echo "<div id='onglet_contenu' style='overflow-y: auto;height:75vh;'>\n";

	echo "<div id='boite1' class='first'>\n";

		echo "<div id='tab_data_geoacquifere' class='table-container' style='float:left;height:70vh;'>";
		echo "</div>\n";

	echo "<hr>\n";
	echo "</div>\n";
	
	
echo "<hr>\n";
echo "</div>\n";
?>

<script>

	var tabDataGeoacquiferee = document.getElementById('tab_data_geoacquifere'); // contenant des données collectées par AJAX		

	// Fonction de lancement de la procédure AJAX permettant d'afficher la liste des données
	function affiche_geo_acquifere_data()
	{
		// Créer un objet JavaScript contenant les données à envoyer
		var dataToSend = {
							territoireId: <?php echo $territoire_id;?>
						};
		
		// Effectuer une requête AJAX asynchrone
		var xhr = new XMLHttpRequest();
		xhr.open("POST", "include/structure/geographie/process_tab_acquifere.php", true);
		xhr.setRequestHeader("Content-Type", "application/json");

		xhr.onreadystatechange = function() 
		{
			if (xhr.readyState === 4 && xhr.status === 200) 
			{
				// Analyser la réponse JSON
				var jsonResponse = JSON.parse(xhr.responseText);

				tab_geo_acquifere = jsonResponse['tab_geo_acquifere'];
				message_info = jsonResponse['message_info'];

				if(tab_geo_acquifere)
				{
					htmlcode = jsonResponse['htmlcode'];				
					tabDataGeoacquiferee.innerHTML = htmlcode; // Ajoute la ligne dans le tableau des fichiers importables
				}
				else
				{
					contenuInfo.innerHTML = message_info;							
					contenuInfo.style.display = 'block';

					contenuInfo.style.border = '4px solid #930000'; // bordure en rouge
				}
			}
		};

		// Convertir l'objet JavaScript en format JSON et l'envoyer au serveur
		xhr.send(JSON.stringify(dataToSend));
	}

	affiche_geo_acquifere_data(); // Lancement de la fonction permettant d'aller chercher les données de types de chroniques

	// Fonction de lancement de la procédure AJAX permettant de supprimer une saisie de caractéristique
	function delete_acquifere(id_acquifere)
	{
		// Créer un objet JavaScript contenant les données à envoyer
		var dataToSend = {
							id_acquifere: id_acquifere
						};
		
		// Effectuer une requête AJAX asynchrone
		var xhr = new XMLHttpRequest();
		xhr.open("POST", "include/structure/geographie/process_delacquifere.php", true);
		xhr.setRequestHeader("Content-Type", "application/json");

		xhr.onreadystatechange = function() 
		{
			if (xhr.readyState === 4 && xhr.status === 200) 
			{
				// Analyser la réponse JSON
				var jsonResponse = JSON.parse(xhr.responseText);

				del_acquifere = jsonResponse['del_acquifere'];
				message_info = jsonResponse['message_info'];

				if(del_acquifere)
				{
					contenuInfo.style.border = '4px solid #09886d'; // bordure en vert

					affiche_geo_acquifere_data();
				}
				else
				{
					contenuInfo.style.border = '4px solid #930000'; // bordure en rouge
				}

				contenuInfo.innerHTML = message_info;							
				contenuInfo.style.display = 'block';
			}
		};

		// Convertir l'objet JavaScript en format JSON et l'envoyer au serveur
		xhr.send(JSON.stringify(dataToSend));
	}

	
</script>