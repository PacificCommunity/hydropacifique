<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Enregistrement des Type de Chroniques (CI, CIE, QI, ...)
*/

		
// Modification des Chroniques existantes

$sql_type_data = "SELECT DISTINCT id_data_type, init_type_data FROM ".TABLE_TYPE_DATA;
$type_data_query = tep_db_query($sql_link,$sql_type_data);
while ($type_data = tep_db_fetch_array($type_data_query)) 
{
	$id_chron = $type_data['id_data_type'];
	
	${'init_'.$id_chron} = post_secure($sql_link,$_POST['init_'.$id_chron]);
	${'nom_'.$id_chron} = post_secure($sql_link,$_POST['nom_'.$id_chron]);
	${'select_type_'.$id_chron} = post_secure($sql_link,$_POST['select_type_'.$id_chron]);
	${'select_axe_'.$id_chron} = post_secure($sql_link,$_POST['select_axe_'.$id_chron]);
	${'chron_unite_'.$id_chron} = post_secure($sql_link,$_POST['chron_unite_'.$id_chron]);
	${'select_to_periode_'.$id_chron} = post_secure($sql_link,$_POST['select_to_periode_'.$id_chron]);
	${'select_chron_periode_'.$id_chron} = post_secure($sql_link,$_POST['select_chron_periode_'.$id_chron]);

	// On vérifie qu'il n'existe pas une chronique avec le même acronyme
	
	$sql_verif_typedata = "SELECT EXISTS (
											SELECT 1 FROM ".TABLE_TYPE_DATA." WHERE init_type_data='".${'init_'.$id_chron}."' LIMIT 1
										) AS typedata_exists";
	$verif_typedata_query = tep_db_query($sql_link,$sql_verif_typedata);	
	$verif_typedata_array = tep_db_fetch_array($verif_typedata_query);

	if((${'init_'.$id_chron} != $type_data['init_type_data']) && ($verif_typedata_array['typedata_exists'] == 1))	// Si le type de données existe déjà
	{		
		$message_info .= htmlaccent('Une Chronique avec un intitulé identique - '.${'init_'.$id_chron}.' - existe déjà. Elle ne peut pas être ajoutée une seconde fois.');
		$message_info .= "<br>";
	}
	else
	{
		$sql_udpate_chron = "UPDATE ".TABLE_TYPE_DATA." SET init_type_data='".${'init_'.$id_chron}."',
															nom_type_data='".${'nom_'.$id_chron}."',
															id_eq_type_data='".${'select_type_'.$id_chron}."',
															axe_data='".${'select_axe_'.$id_chron}."',
															unite='".${'chron_unite_'.$id_chron}."',
															to_periode='".${'select_to_periode_'.$id_chron}."',
															id_chon_periode='".${'select_chron_periode_'.$id_chron}."'
														WHERE id_data_type=".$id_chron;

		tep_db_query($sql_link,$sql_udpate_chron);
	}	
}
$message_info .= htmlaccent('La liste - Chroniques - a bien été mise à jour');


// Nouveau Type de Chronique

if(tep_not_null($_POST['init']))
{
	$init_0 = post_secure($sql_link,$_POST['init']);
	$nom_0 = post_secure($sql_link,$_POST['nom']);
	$select_type_mesure_0 = post_secure($sql_link,$_POST['select_type_mesure']);
	$unite_0 = post_secure($sql_link,$_POST['chron_unite']);
	$select_to_periode_0 = post_secure($sql_link,$_POST['select_to_periode']);
	$select_chron_periode_0 = post_secure($sql_link,$_POST['select_chron_periode']);

	$sql_verif_typedata = "SELECT EXISTS (
											SELECT 1 FROM ".TABLE_TYPE_DATA." WHERE init_type_data='".$init_0."' LIMIT 1
										) AS typedata_exists";
	$verif_typedata_query = tep_db_query($sql_link,$sql_verif_typedata);	
	$verif_typedata_array = tep_db_fetch_array($verif_typedata_query);

	if($verif_typedata_array['typedata_exists'] == 1)	// Si le type de données existe déjà
    {
		$message_info .= "<br>";     
		$message_info .= htmlaccent('Une Chronique avec un intitulé identique - '.$init_0.' - existe déjà. Elle ne peut pas être ajoutée une seconde fois.');
	}
	else
	{
		tep_db_query($sql_link,"INSERT INTO ".TABLE_TYPE_DATA." (init_type_data,
																nom_type_data,
																id_eq_type_data,
																unite,
																to_periode,
																id_chon_periode) 
													VALUES ('".$init_0."',
															'".$nom_0."',
															'".$select_type_mesure_0."',
															'".$unite_0."',
															'".$select_to_periode_0."',
															'".$select_chron_periode_0."'
															)");	
												
		$message_info .= "<br>";     
		$message_info .= htmlaccent('La nouvelle Chronique - '.$init_0.' - a bien été enregistrée.');
	}	
}


// -------------------------------

// Modification des Axes de données

$sql_data_type_axe = "SELECT DISTINCT id, axe, unite FROM ".TABLE_DATA_TYPE_AXE;
$data_type_axe_query = tep_db_query($sql_link,$sql_data_type_axe);
while ($data_type_axe = tep_db_fetch_array($data_type_axe_query)) 
{
	$id_axe = $data_type_axe['id'];
	
	${'axe_'.$id_axe} = post_secure($sql_link,$_POST['axe_'.$id_axe]);
	${'unite_'.$id_axe} = post_secure($sql_link,$_POST['unite_'.$id_axe]);

	tep_db_query($sql_link,"UPDATE ".TABLE_DATA_TYPE_AXE." SET axe ='".${'axe_'.$id_axe}."',
																unite='".${'unite_'.$id_axe}."'
															WHERE id=".$id_axe);
}
$message_info .= "<br><br>";     
$message_info .= htmlaccent('La liste - Axes de données - a bien été mise à jour.');


// Nouvel Axe de données

if(tep_not_null($_POST['axe']))
{
	$axe_0 = post_secure($sql_link,$_POST['axe']);
	$unite_0 = post_secure($sql_link,$_POST['unite']);

	$sql_verif_axe = "SELECT EXISTS (
										SELECT 1 FROM ".TABLE_DATA_TYPE_AXE." WHERE axe='".$axe_0."' LIMIT 1
									) AS axe_exists";
	$verif_axe_query = tep_db_query($sql_link,$sql_verif_axe);	
	$verif_axe_array = tep_db_fetch_array($verif_axe_query);

	if($verif_axe_array['axe_exists'] == 1)	// Si l'Axe existe déjà
    {
		$message_info .= "<br><br>";     
		$message_info .= htmlaccent('Un Axe de données avec un intitulé identique - '.$axe_0.' - existe déjà. Il ne peut pas être ajouté une seconde fois.');
	}
	else
	{
		tep_db_query($sql_link,"INSERT INTO ".TABLE_DATA_TYPE_AXE." (axe,unite) 
													VALUES ('".$axe_0."','".$unite_0."')");	
												
		$message_info .= "<br><br>";     
		$message_info .= htmlaccent('Le nouvel Axe de données - '.$axe_0.' - a bien été enregistré.');
	}	
}

?>
