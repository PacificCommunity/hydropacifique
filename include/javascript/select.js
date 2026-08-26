/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Fonctions JS pour Remplir sans recharger la page Agents. 
Il y a égaleemnt la fonction pour les formulaires des dates
*/


function f_info_agent(id_agent)
{		
	document.getElementById('box_agent').style.display='block';
	//document.getElementById('box_agent').style.zIndex='1600';	
	
	if(id_agent!=0)
	{			
		// Identifiant
		document.getElementById('id_agent').value = id_agent;
		
		document.getElementById('nom').value = document.getElementById('nom_'+id_agent).value;
		document.getElementById('nom_marital').value = document.getElementById('nom_marital_'+id_agent).value;
		document.getElementById('prenom').value = document.getElementById('prenom_'+id_agent).value;
	
		// Work 
		document.getElementById('raisonsociale').value = document.getElementById('raisonsociale_'+id_agent).value;
		document.getElementById('numinscription').value = document.getElementById('numinscription_'+id_agent).value;
		document.getElementById('fonction').value = document.getElementById('fonction_'+id_agent).value;
		
		// Coordonnées
		document.getElementById('tel').value = document.getElementById('tel_'+id_agent).value;
		document.getElementById('mobile').value = document.getElementById('mobile_'+id_agent).value;
		document.getElementById('fax').value = document.getElementById('fax_'+id_agent).value;
		document.getElementById('email').value = document.getElementById('email_'+id_agent).value;		
		document.getElementById('siteweb').value = document.getElementById('siteweb_'+id_agent).value;
		
		// Adresse
		document.getElementById('adresse').value = document.getElementById('adresse_'+id_agent).value;
		document.getElementById('lieudit').value = document.getElementById('lieudit_'+id_agent).value;
		document.getElementById('bp').value = document.getElementById('bp_'+id_agent).value;
		document.getElementById('codepostal').value = document.getElementById('codepostal_'+id_agent).value;
		document.getElementById('select_commune').value = document.getElementById('id_commune_'+id_agent).value;
		
		
		// Calibration des flèches de navigation
		var first_id = document.getElementById('first_agent').value;
		var previous_id = document.getElementById('previous_agent_'+id_agent).value;
		if(previous_id != -1)
		{
			//alert(arrow_previous);
			document.getElementById('arrow_previous').style.display='block';
			document.getElementById('arrow_previous_a').href = 'javascript:f_info_agent('+previous_id+');';
			document.getElementById('arrow_first_a').href = 'javascript:f_info_agent('+first_id+');';	
		}
		else{document.getElementById('arrow_previous').style.display='none';}
		
		
		var last_id = document.getElementById('last_agent').value;
		var next_id = document.getElementById('next_agent_'+id_agent).value;	
		if(next_id != -1)
		{
			//alert(arrow_previous);
			document.getElementById('arrow_next').style.display='block';
			document.getElementById('arrow_next_a').href = 'javascript:f_info_agent('+next_id+');';
			document.getElementById('arrow_last_a').href = 'javascript:f_info_agent('+last_id+');';		
		}
		else{document.getElementById('arrow_next').style.display='none';}
		
		var num_agent_ = document.getElementById('num_agent_'+id_agent).value;
		var nb_agents = document.getElementById('nb_agents').value;
			
		document.getElementById('num_fiche').value = num_agent_+' / '+nb_agents;
		
		
		var service = document.getElementById('niveau_'+id_agent).value;		
		
		if(service == 1){document.getElementById('cadre_view').style.border = "6px solid "+document.getElementById('color_service').value;}
		else{document.getElementById('cadre_view').style.border = "6px solid #000";}
	}
	
}

// ---------------------------------
// Selection du type de période dans formulaire


function select_periode_function() 
 {	
	const class_date = document.querySelectorAll('.select_date');
	const class_month = document.querySelectorAll('.list_month');
	const class_year = document.querySelectorAll('.list_year');
	
	switch (document.getElementById('select_periode').value) 
	{
	  case '1':
		
		for (i = 0; i < class_date.length; i++){class_date[i].style.display='none'};
		for (j = 0; j < class_date.length; j++){class_month[j].style.display='none'};
		for (k = 0; k < class_date.length; k++){class_year[k].style.display='block'};
		break;
	  case '2':
		for (i = 0; i < class_date.length; i++){class_date[i].style.display='none'};
		for (j = 0; j < class_date.length; j++){class_month[j].style.display='block'};
		for (k = 0; k < class_date.length; k++){class_year[k].style.display='block'};
		break;	
	  case '3':
		for (i = 0; i < class_date.length; i++){class_date[i].style.display='block'};
		for (j = 0; j < class_date.length; j++){class_month[j].style.display='none'};
		for (k = 0; k < class_date.length; k++){class_year[k].style.display='none'};
		break;
	}
 }


