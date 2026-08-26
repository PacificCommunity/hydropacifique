/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Fonctions JS liée à la saisie d'un JGE simple
Uniquement résultats : hauteur et débit
*/

function affiche_JGE(id_jge)
{
	// Affichage de la box_ra
	document.getElementById('box_jge_simple').style.display='block';
	document.getElementById('box_jge_simple').style.zIndex='2500';

    initDraggable('title_box_jge_simple', 'box_jge_simple');

    // On remplit les champs de la box
    document.getElementById('jge_id').value = document.getElementById('id_jge_'+id_jge).value;
    document.getElementById('jge_id_station').value = document.getElementById('id_station_'+id_jge).value;
    document.getElementById('jge_station').value = document.getElementById('code_station_'+id_jge).value + ' - ' + document.getElementById('nom_station_'+id_jge).value;
    document.getElementById('jge_date').value = document.getElementById('date_'+id_jge).value;
    document.getElementById('jge_heure').value = document.getElementById('heure_'+id_jge).value;
    document.getElementById('jge_hauteur').value = document.getElementById('hauteur_'+id_jge).value;
    document.getElementById('jge_debit').value = document.getElementById('debit_'+id_jge).value;    
    document.getElementById('select_jge_code_qual').value = document.getElementById('code_qualite_'+id_jge).value;
    document.getElementById('jge_obs').value = document.getElementById('obs_'+id_jge).value;
}