<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Enregistrement d'une fiche agent (Modification ou Création)
- Récupération des données du formulaire
- Vérification du bon format de données
- Sauvegarde dans les bases

*/

// Récupération des données du formulaire soumis qui correspondentt à une fiche agent
$nom = post_secure($sql_link,$_POST['nom']);
$nom_marital = post_secure($sql_link,$_POST['nom_marital']);
$prenom = post_secure($sql_link,$_POST['prenom']);

$raisonsociale = post_secure($sql_link,$_POST['raisonsociale']);
$numinscription = post_secure($sql_link,$_POST['numinscription']);
$fonction = post_secure($sql_link,$_POST['fonction']);

$tel = post_secure($sql_link,$_POST['tel']);
$mobile = post_secure($sql_link,$_POST['mobile']);
$fax = post_secure($sql_link,$_POST['fax']);
$email = post_secure($sql_link,$_POST['email']);
$siteweb = post_secure($sql_link,$_POST['siteweb']);

$adresse = post_secure($sql_link,$_POST['adresse']);
$lieudit = post_secure($sql_link,$_POST['lieudit']);
$bp = post_secure($sql_link,$_POST['bp']);
$codepostal = post_secure($sql_link,$_POST['codepostal']);
$select_commune ='';
if(isset($_POST['select_commune'])){$select_commune = post_secure($sql_link,$_POST['select_commune']);}

$check_terrain = 0;
if(isset($_POST['check_terrain'])){$check_terrain = 1;}
$check_service_hydro = 0;
if(isset($_POST['check_service_hydro'])){$check_service_hydro = 1;}

// Vérification d'un agent portant déjà le même nom et le même prénom 
$sql_verif_agent = "SELECT EXISTS (
										SELECT 1 FROM ".TABLE_AGENT." WHERE nom='".$nom."' AND nom='".$prenom."' LIMIT 1
									) AS agent_exists";
$verif_agent_query = tep_db_query($sql_link,$sql_verif_agent);	
$verif_agent_array = tep_db_fetch_array($verif_agent_query);


if($_POST['id_agent'] > 0) // La fiche existe, elle est à modifier
{
	$id_agent_post = post_secure($sql_link,$_POST['id_agent']);

	if($verif_agent_array['agent_exists'] == 1)	// Si l'Agent (Nom et Prénom identiques) existe déjà
	{
		$message_info .= htmlaccent('Un Agent avec les mêmes Nom et Prénom - '.$nom.' '.$prenom.' - existe déjà. Il ne peut pas être ajouté une seconde fois.');
	}
	else
	{
		tep_db_query($sql_link,"UPDATE ".TABLE_AGENT." SET 
														nom='".$nom."',
														nom_marital='".$nom_marital."',
														prenom='".$prenom."',
														raisonsociale='".$raisonsociale."',
														numinscription='".$numinscription."',
														fonction='".$fonction."',
														tel='".$tel."',
														mobile='".$mobile."',
														fax='".$fax."',
														email='".$email."',
														siteweb='".$siteweb."',
														adresse='".$adresse."',
														lieudit='".$lieudit."',
														bp='".$bp."',
														codepostal='".$codepostal."',
														id_commune='".$select_commune."'
														terrain='".$check_terrain."',
														niveau='".$check_service_hydro."',									
														WHERE id=".$id_agent_post);

		$message_info .= htmlaccent('La fiche Agent - '.$nom.' '.$prenom.' - a bien été mise à jour');
	}
}
else // Nouvelle Fiche Agent
{
	if($verif_agent_array['agent_exists'] == 1)	// Si l'Agent (Nom et Prénom identiques) existe déjà
	{
		$message_info .= htmlaccent('Un Agent avec les mêmes Nom et Prénom - '.$nom.' '.$prenom.' - existe déjà. Il ne peut pas être ajouté une seconde fois.');
	}
	else
	{
		tep_db_query($sql_link,"INSERT INTO ".TABLE_AGENT." (nom,
															nom_marital,
															prenom,
															raisonsociale,
															numinscription,
															fonction,
															tel,
															mobile,
															fax,
															email,
															siteweb,
															adresse,
															lieudit,
															bp,
															codepostal,
															id_commune) 
													VALUES ('".$nom."',
															'".$nom_marital."',
															'".$prenom."',
															'".$raisonsociale."',
															'".$numinscription."',
															'".$fonction."',
															'".$tel."',
															'".$mobile."',
															'".$fax."',
															'".$email."',
															'".$siteweb."',
															'".$adresse."',
															'".$lieudit."',
															'".$bp."',
															'".$codepostal."',
															'".$select_commune."')");	

		$message_info .= htmlaccent('La nouvelle fiche Agent - '.$nom.' '.$prenom.' - a bien été créée');
	}
}




?>