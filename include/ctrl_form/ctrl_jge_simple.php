<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Contrôle des données JGE Simple
Enregistrement dans la base
----------------------------------------
*/
$error = -1;
$new_id = -1;
$mysql_datetime_action = date('Y-m-d H:i:s');

/*  
----------------------------------------
RECUPERATION DES DONNEES FORMULAIRES STATIONS
----------------------------------------
*/

$jge_id =  post_secure($sql_link,$_POST['jge_id']);
$jge_id_station = post_secure($sql_link,$_POST['jge_id_station']);
$jge_hauteur = post_secure($sql_link,$_POST['jge_hauteur']);
$jge_debit = post_secure($sql_link,$_POST['jge_debit']);
$jge_date = post_secure($sql_link,$_POST['jge_date']);
$jge_heure = post_secure($sql_link,$_POST['jge_heure']);
//$jge_code_qual = post_secure($sql_link,$_POST['select_jge_code_qual']);
$jge_code_qual = '';
$jge_obs = post_secure($sql_link,$_POST['jge_obs']);


// ----------------------------------------
// CONTROLE DE LA SAISIE

// Les champs Hauteur et Débits doivent être saisis
if(!tep_not_null($jge_hauteur) || !tep_not_null($jge_debit))
{
    
    if($error>0){$message_info .= "<br>";}	
    $message_info .= htmlaccent('Les champs Hauteur et Débit doivent être renseignés.');
    $error=1;
}
else
{
    if(!is_numeric(trim($jge_hauteur))) // la fonction trim permet du supprimer les espaces qui pourraient apparaitre
    {
        if($error>0){$message_info .= "<br>";}	
        $message_info .= htmlaccent('Le champs Hauteur doit être un nombre entier ou décimal.');
        $error=1;
    }

    if(!is_numeric(trim($jge_debit)))
    {
        if($error>0){$message_info .= "<br>";}	
        $message_info .= htmlaccent('Le champs Débit doit être un nombre entier ou décimal.');
        $error=1;
    }
}


// Vérification du format date
if(tep_not_null($jge_date))
{
	$jge_date_format = DateTime::createFromFormat($date_format, $jge_date); // date_format est défini dans modif_station.php
	if ($jge_date_format && $jge_date_format->format($date_format) === $jge_date) 	
	{
		$jge_date_format_string = $jge_date_format->format('d-m-Y');
        $jge_date_format_string_us = $jge_date_format->format('Y-m-d');
	}
	else
	{
		if($error>0){$message_info .= "<br>";}	
		$message_info .= htmlaccent('Le format de la date installation n\'est pas valide. Veuillez vérifier votre saisie : dd-mm-aaaa');
        $error=1;
	}
}

// Vérification du format heure
if(tep_not_null($jge_heure))
{
	$jge_heure_format = DateTime::createFromFormat($heure_format, $jge_heure); // heure_format est défini dans modif_station.php
    $jge_heure_format_string = $jge_heure_format->format('H:i:s'); // Formater l'heure
	if ($jge_heure_format === false) 	
	{
		if($error>0){$message_info .= "<br>";}		
		$message_info .= htmlaccent('Le format de l\'heure n\'est pas valide. Veuillez vérifier votre saisie : hh:mm:ss');
        $error=1;
	}
}


/*  
----------------------------------------
ENREGISTREMENT DS LES BASES 
----------------------------------------
*/

if($error<1)
{
    $info_station = $station_array[$jge_id_station]['code_station'].' - '.$station_array[$jge_id_station]['nom_station'];
    $mysql_datetime = $jge_date_format_string_us.' '.$jge_heure_format_string;
    
    if($jge_id == 0)
    {
        $last_id_query = tep_db_query($sql_link,"SELECT max(id) as id FROM ".TABLE_DATA_JGE);
		$last_id_tab = tep_db_fetch_array($last_id_query);
		$last_id = $last_id_tab['last_id'];	

		$jge_id = $last_id+1;

		$query = "INSERT INTO " . TABLE_DATA_JGE . " (id) VALUES ('$jge_id')";
		tep_db_query($sql_link, $query);	

        $type_action = 10; // Création JGE
        $info_jge = htmlaccent('Création Jaugeage pour la station : '.$info_station.' le '.$mysql_datetime);
    }
    else
    {
        $type_action = 11; // Modification JGE
        $info_jge = htmlaccent('Modification Jaugeage pour la station : '.$info_station.' le '.$mysql_datetime);
    }
    
    $query = "UPDATE ".TABLE_DATA_JGE." SET id_station='$jge_id_station', 
                                                datetime='$mysql_datetime', 
                                                depouil_q='$jge_debit', 
                                                depouil_hmoy='$jge_hauteur', 
                                                code_qualite='$jge_code_qual', 
                                                obs='$jge_obs' 
                                            WHERE id=$jge_id";

    tep_db_query($sql_link, $query);			

    // Enregistrement de l'action dans la Table Actions
    tep_db_query($sql_link,"INSERT INTO ".TABLE_ACTIONS." (id_user,type_action,info,dateheure) ".
                                                        " VALUES ('".$id_user.
                                                                "','".$type_action.
                                                                "', '".$info_jge.
                                                                "', '".$mysql_datetime_action."')");

    $message_info = htmlaccent('Le Jaugeage du '.$jge_date_format_string.' a bien été enregistrée pour la station '. $info_station);    
}

$id_jge_modif = $jge_id;
$modif_jge = true; // pour revenir après l'enregistrement sur la bonne feuille de RA

?>
