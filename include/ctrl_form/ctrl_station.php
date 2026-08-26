<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Enregistrement du formulaire Station (tous les onglets sont enregistré en même temps)
----------------------------------------
*/

$action = true;
$action_result = true;
$message_action = '';
$nb_error = 0;


$new_id = -1;

/*  
----------------------------------------
RECUPERATION DES DONNEES FORMULAIRES STATIONS
----------------------------------------
*/

// Fiche

//$id_station = post_secure($sql_link,$_POST['id_station_old']);

// Général
$code_station = post_secure($sql_link,$_POST['code_station']);
$nom_station = post_secure($sql_link,$_POST['nom_station']);
$nom_court = post_secure($sql_link,$_POST['nom_court']);
$num_irh = post_secure($sql_link,$_POST['num_irh']);

$select_region = post_secure($sql_link,$_POST['select_region']); // Province en Calédonie / Iles en PF et WF
$select_commune = post_secure($sql_link,$_POST['select_commune']);
$site_station = post_secure($sql_link,$_POST['site_station']);

//Type de station et Etats 


$station_type = post_secure($sql_link,$_POST['select_type_mesure']); 

$active_station = 0;$suivi_station = 0;$armee_station = 0;

if($_SERVER['REQUEST_METHOD'] === 'POST')
{
	if(isset($_POST['check_active_station'])){$active_station = 1;}
	if(isset($_POST['check_suivi_station'])){$suivi_station = 1;}
	if(isset($_POST['check_armee_station'])){$armee_station = 1;}
}

// Géographie

$vallee_station = post_secure($sql_link,$_POST['vallee_station']); 
$riviere_station = post_secure($sql_link,$_POST['riviere_station']);

$regionhydro_station = post_secure($sql_link,$_POST['select_regionhydro']);
$tournee_station = post_secure($sql_link,$_POST['select_tournee']);

$altitude_station = post_secure($sql_link,$_POST['altitude_station']);
$orientation_station = post_secure($sql_link,$_POST['orientation_station']);

// Data GPS
$latitude_station = post_secure($sql_link,$_POST['latitude_station']);
$longitude_station = post_secure($sql_link,$_POST['longitude_station']);
$utm_station_x = post_secure($sql_link,$_POST['utm_station_x']);
$utm_station_y = post_secure($sql_link,$_POST['utm_station_y']);
$ign_station_x = post_secure($sql_link,$_POST['ign_station_x']);
$ign_station_y = post_secure($sql_link,$_POST['ign_station_y']);
$lamb_station_x = post_secure($sql_link,$_POST['lamb_station_x']);
$lamb_station_y = post_secure($sql_link,$_POST['lamb_station_y']);

// DATE

$date_1_station = post_secure($sql_link,$_POST['date_installation_station']);
$date_1_station_us = null;
$date_2_station = post_secure($sql_link,$_POST['date_fermeture_station']);
$date_2_station_us = null;

$description_station = post_secure($sql_link,$_POST['description_station']);

//A ajouter un peu plus tard
$source_info = '';
$transmission_station = '';
//$source_info = post_secure($sql_link,$_POST['source_info']);
//$transmission_station = post_secure($sql_link,$_POST['transmission_station']);

// Contrôle si une station avec le même code existe déjà lorsque l'on en crée une nouvelle
if(!$modif)
{	
	// requête sql pour récupérer les données articles
	$sql_station_verif = "SELECT DISTINCT s.id_station
						FROM ".TABLE_STATION." s 
						WHERE s.code_station='".$code_station."'";
	
	$station_verif_query = tep_db_query($sql_link,$sql_station_verif);
	$station_verif = tep_db_fetch_array($station_verif_query);
	
	if(isset($station_verif))
	{	
		$action_result=false;
		if($nb_error>0){$message_action .= "<br>";}
		$nb_error++;
		$message_action .= htmlaccent('Ce code '.$code_station.' est déjà attribué, il n\'est pas possible de créer cette station');
	}
}

// Vérification des formats date
if(tep_not_null($date_1_station))
{
	$date_1_format = DateTime::createFromFormat($date_format, $date_1_station); // date_format est défini dans modif_station.php
	if ($date_1_format && $date_1_format->format($date_format) === $date_1_station) 	
	{
		$date_1_station_us = $date_1_format->format('Y-m-d');
	}
	else
	{
		$action_result=false;
		if($nb_error>0){$message_action .= "<br>";}
		$nb_error++;
		$message_action .= htmlaccent('Le format de la date installation n\'est pas valide. Veuillez vérifier votre saisie : dd-mm-aaaa');
	}
}

if(tep_not_null($date_2_station))
{
	$date_2_format = DateTime::createFromFormat($date_format, $date_2_station); // date_format est défini dans modif_station.php
	if ($date_2_format && $date_2_format->format($date_format) === $date_2_station) 	
	{
		if($date_2_format > $date_1_format)
		{
			$date_2_station_us = $date_2_format->format('Y-m-d');
		}
		else
		{
			$action_result=false;
			if($nb_error>0){$message_action .= "<br>";}
			$nb_error++;
			$message_action .= htmlaccent('La date de fermeture de la station ne peut pas être antérieure à la date d\'installation.');
		}
	}
	else
	{
		$action_result=false;
		if($nb_error>0){$message_action .= "<br>";}
		$nb_error++;
		$message_action .= htmlaccent('Le format de la date installation n\'est pas valide. Veuillez vérifier votre saisie : dd-mm-aaaa');
	}
}


/*  
----------------------------------------
VALIDATION ET VERIFICATION DES DONNEES FORMULAIRES 
----------------------------------------
*/

// Champ vide
if(!tep_not_null($code_station))
{
	$action_result=false;
	if($nb_error>0){$message_action .= "<br>";}
	$nb_error++;
	$message_action .= htmlaccent('Le code de la station doit-être renseigné');
}
if(!tep_not_null($nom_station))
{
	$action_result=false;
	if($nb_error>0){$message_action .= "<br>";}
	$nb_error++;
	$message_action .= htmlaccent('Le nom de la station doit-être renseigné');
}
		
/*  
----------------------------------------
ENREGISTREMENT DS LES BASES 
----------------------------------------
*/

if($action_result)
{
	if(!$modif)
	{
		$last_id_query = tep_db_query($sql_link,"SELECT max(id_station) as last_id FROM ".TABLE_STATION);
		$last_id_tab = tep_db_fetch_array($last_id_query);
		$last_id = $last_id_tab['last_id'];	

		$ref_id = $last_id+1;
		$id_station_old = 'N_'.$ref_id;

		$query = "INSERT INTO " . TABLE_STATION . " (id_station,id_station_old, code_station) VALUES ('$ref_id','$id_station_old','$code_station')";
		tep_db_query($sql_link, $query);	
		
		$message_action = htmlaccent('La nouvelle station a bien été enregistrée.');  	
		
		// Enregistrement de l'action Administration
		$type_action = 38;
		$info_action = "Création d\'une nouvelle station : ".$code_station." - ".$nom_station;
	}	
	else
	{	
		if($nb_error>0){$message_action .= "<br>";}
		$nb_error++;$message_action .= htmlaccent('Les données de la station ont bien été mise à jour.');  

		// Enregistrement de l'action Administration
		$type_action = 38;
		$info_action = "Modification des données station : ".$code_station." - ".$nom_station;
	}

	$query = "UPDATE ".TABLE_STATION." SET 
					nom_station='$nom_station', 
					nom_court='$nom_court', 
					code_station='$code_station', 
					num_irh='$num_irh', 
					id_territoire='$territoire_id', 
					id_region='$select_region',
					id_commune='$select_commune', 
					vallee_station='$vallee_station', 
					riviere_station='$riviere_station',
					id_tournee='$tournee_station',
					id_regionhydro='$regionhydro_station',
					altitude_station='$altitude_station', 
					orientation_station='$orientation_station',
					longitude_station='$longitude_station', 
					latitude_station='$latitude_station',
					utm_station_x='$utm_station_x', 
					utm_station_y='$utm_station_y',
					ign_station_x='$ign_station_x', 
					ign_station_y='$ign_station_y',
					lamb_station_x='$lamb_station_x', 
					lamb_station_y='$lamb_station_y',					
					station_type='$station_type',
					date_installation_station=" . ($date_1_station_us === null ? 'NULL' : "'$date_1_station_us'") . ",
					date_fermeture_station=" . ($date_2_station_us === null ? 'NULL' : "'$date_2_station_us'") . ", 
					description_station='$description_station',
					active_station='$active_station', 
					suivi='$suivi_station', 					
					armee='$armee_station', 
					source_info='$source_info',
					transmission_station='$transmission_station'
					WHERE id_station=$ref_id";
		
		
	tep_db_query($sql_link, $query);			
	
	
	// Enregistrement de l'action
	$today_us = date('Y-m-d H:i:s'); 

	$query = "INSERT INTO ".TABLE_ACTIONS." (id_user, type_action, info, dateheure) 
										VALUES (".$id_user.",'".$type_action."','".$info_action."','".$today_us."')";
	tep_db_query($sql_link,$query);
}
else
{
	$message_action = htmlaccent('Les modifications n\'ont pas pû être enregistrée : ') . "<br><br>" . $message_action;
}
?>