/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Fonctions JS pour Remplir sans recharger la page les fiches RA,
*/

function select_boxRA_typeData(id_type_ra) 
{
	if(id_type_ra < 1){id_type_ra = 1;}
	
	
	// Récupère tous les éléments avec les différents classes "elt_boite_plu, elt_boite_hydro"
	// Cela permet d'afficher les champs en fonction des différents types (Plu, Débit, PIEZO)
	
	const class_plu = document.querySelectorAll('.elt_boite_plu');
	const class_hydro = document.querySelectorAll('.elt_boite_hydro');
	const class_pluhydro = document.querySelectorAll('.elt_boite_pluhydro'); // Elts à la fois dans plu et hydro
	const class_piezo = document.querySelectorAll('.elt_boite_piezo');

	// Les RA ne sont prévus que pour Hydro / Pluvio et Piézo. L'affichage de la box RA dépend du type de données
	// Possible de rajouter d'autre type de données mais ce doit être fait dans ce code.

	// La condition parcours tous les éléments et ajoute un écouteur d'événements "click" à chacun d'entre eux
	switch(id_type_ra) 
	{		  
		case '1':				
			for (i = 0; i < class_plu.length; i++){class_plu[i].style.display='block';}
			for (j = 0; j < class_hydro.length; j++){class_hydro[j].style.display='none';}				
			for (j = 0; j < class_pluhydro.length; j++){class_pluhydro[j].style.display='block';}	
			for (j = 0; j < class_piezo.length; j++){class_piezo[j].style.display='none';}
			break;
		case '5':
			for (i = 0; i < class_plu.length; i++){class_plu[i].style.display='none';}
			for (j = 0; j < class_hydro.length; j++){class_hydro[j].style.display='none';}			
			for (j = 0; j < class_pluhydro.length; j++){class_pluhydro[j].style.display='none';}	
			for (j = 0; j < class_piezo.length; j++){class_piezo[j].style.display='block';}
			break;
		case '11':
			for (i = 0; i < class_plu.length; i++){class_plu[i].style.display='none';}
			for (j = 0; j < class_hydro.length; j++){class_hydro[j].style.display='block';}		
			for (j = 0; j < class_pluhydro.length; j++){class_pluhydro[j].style.display='block';}		
			for (j = 0; j < class_piezo.length; j++){class_piezo[j].style.display='none';}
			break;		 
		default:
			for (i = 0; i < class_plu.length; i++){class_plu[i].style.display='block';}
			for (j = 0; j < class_hydro.length; j++){class_hydro[j].style.display='none';}		
			for (j = 0; j < class_pluhydro.length; j++){class_pluhydro[j].style.display='block';}		
			for (j = 0; j < class_piezo.length; j++){class_piezo[j].style.display='none';}
			break;
	}

	// Permet précisé dans la liste des type de données (Pluie, CI, Piezo), celui qui est sélectionné
	document.getElementById('select_type_ra').value = id_type_ra;

	// Coloration de la bordure et du fond de la boxRa en fonction du type sélectionné
	document.getElementById('cadre_view').style.border = "6px solid " + document.getElementById('type_color_border_'+id_type_ra).value;	
	document.getElementById('cadre_view').style.backgroundColor  = document.getElementById('type_color_background_'+id_type_ra).value;	
}




// Navigation instantannée entre les RA
function affiche_RA(id_ra)
{		
	// Affichage de la box_ra
	document.getElementById('box_ra').style.display='block';
	document.getElementById('box_ra').style.zIndex='1600';	
	if(document.getElementById('contenu_info')){document.getElementById('contenu_info').style.zIndex='2000';}
	

	// Ici on récupère les valeurs dans les champs caché affiché dans la page list_ra.php pour les afficher dans les champs du formulaire quand on sélectionne un RA
	if(id_ra>0)
	{	
		// ID_RA
		document.getElementById('id_ra').value = id_ra;

		document.getElementById('id_agent_user').value = document.getElementById('id_agent_user_'+id_ra).value;

		document.getElementById('bloc_valid_ra').style.display='block'; // On affiche la possibilité de validation

		// Infos Tournée
		document.getElementById('date_saisie').value = document.getElementById('date_heure_ra_'+id_ra).value;
		document.getElementById('select_station_ra').value = document.getElementById('id_station_'+id_ra).value;
		
		// Type Data (Pluie, CI, Piezo)
		id_type_ra = document.getElementById('id_eq_type_'+id_ra).value;

		select_boxRA_typeData(id_type_ra);
				
		// ALL - Etat RA : Validé ou à Valider
		// Puce verte ou rouge + Case à cocher
		if(document.getElementById('etat_ra_'+id_ra).value==1)
		{
			document.getElementById('valid_puce_ok').style.display='block';
			document.getElementById('valid_puce_no').style.display='none';
			
			document.getElementById('check_valid_ra').checked=true;
		}
		else
		{
			document.getElementById('valid_puce_ok').style.display='none';
			document.getElementById('valid_puce_no').style.display='block';

			document.getElementById('check_valid_ra').checked=false;
		}
		
		// ALL - Relève
		document.getElementById('date_releve').value = document.getElementById('date_ra_'+id_ra).value;
		document.getElementById('heure_releve').value = document.getElementById('heure_ra_'+id_ra).value;
		document.getElementById('fichier_releve').value = document.getElementById('name_file_data_'+id_ra).value;
		
		// ALL - Appareil
		document.getElementById('type_appareil').value = document.getElementById('type_appareil_'+id_ra).value;
		document.getElementById('num_appareil').value = document.getElementById('num_appareil_'+id_ra).value;
		document.getElementById('heure_appareil').value = document.getElementById('heure_appareil_'+id_ra).value;
		
		// ALL - Etat appareil
		document.getElementById('hydro_num_sonde').value = document.getElementById('hydro_num_sonde_'+id_ra).value;
		document.getElementById('nb_octet').value = document.getElementById('nb_octet_'+id_ra).value;
		document.getElementById('num_batterie').value = document.getElementById('num_batterie_'+id_ra).value;
		document.getElementById('tension_batterie').value = document.getElementById('tension_batterie_'+id_ra).value;

		// ALL - Nouvelle Cassette (ancien matériel, obsolète dans le futur)
		document.getElementById('num_cassette').value = document.getElementById('num_cassette_'+id_ra).value;
		document.getElementById('heure_init_cassette').value = document.getElementById('heure_init_cassette_'+id_ra).value;
		document.getElementById('hydro_h_sonde_cassette').value = document.getElementById('hydro_h_sonde_cassette_'+id_ra).value;		
		document.getElementById('plu_heure_bascul1_cassette').value = document.getElementById('plu_heure_bascul1_cassette_'+id_ra).value;

		// PLU - Totalisateur
		document.getElementById('plu_tot_type').value = document.getElementById('plu_tot_type_'+id_ra).value;
		document.getElementById('plu_tot_first').value = document.getElementById('plu_tot_first_'+id_ra).value;
		document.getElementById('plu_tot_last').value = document.getElementById('plu_tot_last_'+id_ra).value;
		document.getElementById('plu_tot_heure_basc').value = document.getElementById('plu_tot_heure_basc_'+id_ra).value;

		// PLU - Contrôle 
		document.getElementById('plu_cumul_tot').value = document.getElementById('plu_cumul_tot_'+id_ra).value;
		document.getElementById('plu_cumul_plu').value = document.getElementById('plu_cumul_plu_'+id_ra).value;
		document.getElementById('plu_recalage_heure_plu').value = document.getElementById('plu_recalage_heure_plu_'+id_ra).value;
		document.getElementById('plu_test_auget').value = document.getElementById('plu_test_auget_'+id_ra).value;
		
		document.getElementById('plu_nb_basculement').value = document.getElementById('plu_nb_basculement_'+id_ra).value;
		
		// HYDRO - Côte limnimétrique
		document.getElementById('hydro_heure_cote').value = document.getElementById('hydro_heure_cote_'+id_ra).value;
		document.getElementById('hydro_h_sonde').value = document.getElementById('hydro_h_sonde_'+id_ra).value;
		document.getElementById('hydro_h_echelle_1').value = document.getElementById('hydro_h_echelle_1_'+id_ra).value;
		document.getElementById('hydro_h_echelle_2').value = document.getElementById('hydro_h_echelle_2_'+id_ra).value;
		
		// Contrôle des mesure de hauteur
		document.getElementById('hech_hsonde').value = document.getElementById('hydro_h_diff_'+id_ra).value; // Le calcul est fait automatiquement = h_echelle - hsonde
		document.getElementById('hydro_recalage_sonde').value = document.getElementById('hydro_recalage_sonde_'+id_ra).value;
		document.getElementById('hydro_recalage_heure_sonde').value = document.getElementById('hydro_recalage_heure_sonde_'+id_ra).value;	
		if(document.getElementById('hydro_purge_sonde_'+id_ra).value==1){document.getElementById('check_purge_sonde').checked=true;}
		else{document.getElementById('check_purge_sonde').checked=false;}

		// Piézo - Relevés Puits
		document.getElementById('piezo_conductivite').value = document.getElementById('piezo_conductivite_'+id_ra).value;
		document.getElementById('piezo_temperature').value = document.getElementById('piezo_temperature_'+id_ra).value;
		document.getElementById('piezo_recalage_diff').value = document.getElementById('piezo_recalage_diff_'+id_ra).value;

		// Piézo - Mesure Nappe
		document.getElementById('piezo_nature_repere').value = document.getElementById('piezo_nature_repere_'+id_ra).value;
		document.getElementById('piezo_instrument').value = document.getElementById('piezo_instrument_'+id_ra).value;
		document.getElementById('piezo_num_instrument').value = document.getElementById('piezo_num_instrument_'+id_ra).value;
		document.getElementById('piezo_prof_toitnappe').value = document.getElementById('piezo_prof_toitnappe_'+id_ra).value;
		document.getElementById('piezo_prof_totale').value = document.getElementById('piezo_prof_totale_'+id_ra).value;

		// Piézo - Position de la mesure
		document.getElementById('piezo_x_terrain').value = document.getElementById('piezo_x_terrain_'+id_ra).value;
		document.getElementById('piezo_y_terrain').value = document.getElementById('piezo_y_terrain_'+id_ra).value;
		document.getElementById('piezo_gps_precision').value = document.getElementById('piezo_gps_precision_'+id_ra).value;
		document.getElementById('piezo_systeme_coord').value = document.getElementById('piezo_systeme_coord_'+id_ra).value;
			
		// ALL - Observations / Actions
		
		// ALL
		document.getElementById('ra_obs').value = document.getElementById('ra_obs_'+id_ra).value;

		// FAIT MARQUANT
		if(document.getElementById('fait_marquant_'+id_ra).value==1){document.getElementById('check_faitmarquant').checked=true;}
		else{document.getElementById('check_faitmarquant').checked=false;}

		// PREVOIR MARQUANT
		if(document.getElementById('pre_marquant_'+id_ra).value==1){document.getElementById('check_premarquant').checked=true;}
		else{document.getElementById('check_premarquant').checked=false;}
				
		// HYDRO
		if(document.getElementById('hydro_ra_jaugeage_'+id_ra).value==1){document.getElementById('check_jaugeage').checked=true;}
		else{document.getElementById('check_jaugeage').checked=false;}

		// PLU
		if(document.getElementById('plu_ra_bouchage_'+id_ra).value==1){document.getElementById('check_bouchage').checked=true;}
		else{document.getElementById('check_bouchage').checked=false;}
		
		// PLU
		if(document.getElementById('plu_ra_huile_tot_'+id_ra).value==1){document.getElementById('check_huile').checked=true;}
		else{document.getElementById('check_huile').checked=false;}
		
		// PLU + HYDRO
		if(document.getElementById('ra_debroussaillage_'+id_ra).value==1){document.getElementById('check_debrouss').checked=true;}
		else{document.getElementById('check_debrouss').checked=false;}
		
		// PLU + HYDRO
		if(document.getElementById('ra_eau_batterie_'+id_ra).value==1){document.getElementById('check_eaubat').checked=true;}
		else{document.getElementById('check_eaubat').checked=false;}
		
		// PLU + HYDRO
		if(document.getElementById('ra_transfert_data_'+id_ra).value==1){document.getElementById('check_transfert').checked=true;}
		else{document.getElementById('check_transfert').checked=false;}
		
		// PLU + HYDRO
		if(document.getElementById('ra_delete_memory_'+id_ra).value==1){document.getElementById('check_deletememory').checked=true;}
		else{document.getElementById('check_deletememory').checked=false;}

		// PIEZO
		if(document.getElementById('piezo_pompage_encours_'+id_ra).value==1){document.getElementById('check_pompage_encours').checked=true;}
		else{document.getElementById('check_pompage_encours').checked=false;}

		if(document.getElementById('piezo_pompage_proche_'+id_ra).value==1){document.getElementById('check_pompage_proche').checked=true;}
		else{document.getElementById('check_pompage_proche').checked=false;}

		if(document.getElementById('piezo_pluie_crue_'+id_ra).value==1){document.getElementById('check_piezo_pluie_crue').checked=true;}
		else{document.getElementById('check_piezo_pluie_crue').checked=false;}

		/* Pour le moment je ne sais pas à quoi cela correspond
		if(document.getElementById('piezo_temps_sec_'+id_ra).value==1){document.getElementById('check_piezo_temps_sec').checked=true;}
		else{document.getElementById('check_piezo_temps_sec').checked=false;}
		*/

		if(document.getElementById('piezo_photos_'+id_ra).value==1){document.getElementById('check_piezo_photos').checked=true;}
		else{document.getElementById('check_piezo_photos').checked=false;}


		// ALL - Texte à faire lors du prochain passage
		document.getElementById('ra_futur').value = document.getElementById('ra_futur_'+id_ra).value;
		
		// ALL - Agents
		// Sélectionnez tous les éléments dont l'ID commence par "check_agent_"
		var checkboxes = document.querySelectorAll('[id^="check_agent_"]');
		// Parcourez les cases à cocher et décochez-les
		for (var i = 0; i < checkboxes.length; i++) {checkboxes[i].checked = false;}

		var valueAgent = document.getElementById('agent_select_'+id_ra).value;				
		var agentIds = valueAgent.split(',');
		for (var i = 0; i < agentIds.length; i++) 
		{
			var agentId = agentIds[i];			
			var checkbox = document.getElementById('check_agent_' + agentId);
			
			if (checkbox) {checkbox.checked = true;}
		}
		
		// Agents complémentaires (non engregistrés) lors du passage
		document.getElementById('agents_complement').value = document.getElementById('agents_complement_'+id_ra).value;


		// -------------------------------------------------
		// On charge les données pour le profil piézométrique si on est avec un RA piezo

		for (let i = 1; i <= 15; i++) 
		{
			if(document.getElementById('piezo_profil_prof_'+i+'_'+id_ra))
			{
				document.getElementById('piezo_profil_prof_'+i).value = document.getElementById('piezo_profil_prof_'+i+'_'+id_ra).value;
				document.getElementById('piezo_profil_conduct_'+i).value = document.getElementById('piezo_profil_conduct_'+i+'_'+id_ra).value;
				document.getElementById('piezo_profil_temp_'+i).value = document.getElementById('piezo_profil_temp_'+i+'_'+id_ra).value;
			}
			else
			{
				document.getElementById('piezo_profil_prof_'+i).value = '';
				document.getElementById('piezo_profil_conduct_'+i).value = '';
				document.getElementById('piezo_profil_temp_'+i).value = '';				
			}
		}


		// -------------------------------------------------
		// Calibration des flèches de navigation

		// Récupérer tous les éléments avec la classe 'content_arrow'
		var elements = document.getElementsByClassName('content_arrow');
		// Parcourir tous les éléments et les masquer
		for(var i = 0; i < elements.length; i++) {elements[i].style.display = 'block';}

		var first_id = document.getElementById('first_ra').value;
		var previous_id = document.getElementById('previous_ra_'+id_ra).value;
		if(previous_id != -1)
		{
			document.getElementById('arrow_previous').style.display='block';
			document.getElementById('arrow_previous_a').href = 'javascript:affiche_RA('+previous_id+');displayOff_comment();';
			document.getElementById('arrow_first_a').href = 'javascript:affiche_RA('+first_id+');displayOff_comment();';	
		}
		else{document.getElementById('arrow_previous').style.display='none';}
		
		
		var last_id = document.getElementById('last_ra').value;
		var next_id = document.getElementById('next_ra_'+id_ra).value;	
		if(next_id != -1)
		{
			document.getElementById('arrow_next').style.display='block';
			document.getElementById('arrow_next_a').href = 'javascript:affiche_RA('+next_id+');displayOff_comment();';
			document.getElementById('arrow_last_a').href = 'javascript:affiche_RA('+last_id+');displayOff_comment();';		
		}
		else{document.getElementById('arrow_next').style.display='none';}
		
		var num_ra = document.getElementById('num_ra_'+id_ra).value;
		var nb_ra = document.getElementById('nb_ra').value;
			
		document.getElementById('num_fiche').value = num_ra+' / '+nb_ra;		
	}
}


// Petite fonction pour ne plus afficher le commentaire si on change de RA
function displayOff_comment()
{
	if(document.getElementById('contenu_info')){document.getElementById('contenu_info').style.display='none';}
}

// Fonction pour afficher la box profil et l'ancer l'édition du graphique
function affiche_RA_piezoprofil()
{
	document.getElementById('box_ra_piezoprofil').style.display='block';
	document.getElementById('box_ra_piezoprofil').style.zIndex='1700';

	id_ra = document.getElementById('id_ra').value;

	f_editgraph_profil(id_ra);
}

// Fonction pour édition interactive des graphs Piezo Profil dans RA
function f_editgraph_profil(id_ra,update = false)
{
	var xData = [];
    var yData = [];
		
	for (let i = 1; i <= 15; i++) 
	{
		var profElement = document.getElementById('piezo_profil_prof_'+i);
		var conductElement = document.getElementById('piezo_profil_conduct_'+i);

		var profValue = 0;
		var conductValue = 0;

		if (profElement && profElement.value !== '') {
			profValue = (-1)*parseFloat(profElement.value);
		}

		if (conductElement && conductElement.value !== '') {
			conductValue = parseFloat(conductElement.value);
		}

		if((profValue !== 0) && (conductValue !== 0))
		{
			xData.push(conductValue);
        	yData.push(profValue);
		}
	}

	var Xmax = Math.max(...xData);
	var Ymin = Math.min(...yData);
	
	var data_profil = 
	{ 
		x: xData,
		y: yData,    

		mode: 'markers+lines', // type de trace (scatter plot)
		type: 'scatter', // type de graphique
		marker: { size: 8}, // taille des marqueurs   
	};  

	// Pour l'édition du graphique
    var config = 
    {
        responsive: true,
        doubleClickDelay: 1000, //Delay du zoom
        
        displayModeBar: true, // Affichage constant du menu de la figure
        scrollZoom: false, // Zoom avec la roulette de la souris

        modeBarButtonsToRemove: ['select2d','lasso2d','autoScale2d','zoomIn2d','zoomOut2d'],
        modeBarOrientation: 'v',

        displaylogo: false
    };

    var layout_profil = 
    {
        xaxis: 
        {
            title: {
                text: 'Conductivité [&mu;S/cm]',
                standoff: 20 // Ajuster la distance entre le titre et l'axe
            },                
            tickfont: {size: 11}, // Taille des caractères des graduations
            titlefont: {family: 'roboto, arial, helvetica',
                size: 14,
                bold: true,
                color: '#000000'},
                
            tickangle: 0,
            ticklen: 5,
            showline: true,
            linewidth: 1,
            automargin: true,  
			//autorange: true, // Ajustement automatique de l'échelle de l'axe x			
			range: [0, (Xmax*1.1)], // Définir la plage de l'axe x
			side: 'top' // Placer l'axe x en haut du graphique
        },

        yaxis:
        {
            title: {
                text: 'Profondeur [m]',
                standoff: 10 // Ajuster la distance entre le titre et l'axe
            },
            tickfont: {size: 11}, // Taille des caractères des graduations
			titlefont: {family: 'roboto, arial, helvetica',
                    size: 14,
                    bold: true,
                    color: '#000000'},
            tickformat: ',.1f',
            ticklen: 5,
            showline: true,
            linewidth: 1,
			automargin: true,
			//autorange: true, // Ajustement automatique de l'échelle de l'axe y
			range: [(Ymin*1.1),0], // Définir la plage de l'axe y
        }
    };
    
    Plotly.newPlot('plot_profil', [data_profil], layout_profil, config);

}

