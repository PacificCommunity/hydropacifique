<?php
/*  
----------------------------------------
Copyright (c) 2015 - Vai-Natura
----------------------------------------
*/
$error = -1;
$new_id = -1;



/*  
----------------------------------------
RECUPERATION DES DONNEES FORMULAIRES
----------------------------------------
*/

for($n=1;$n<3;$n++)
{
	${'id_eq_'.$n} = post_secure($sql_link,$_POST['id_eq_'.$n]);
	
	${'date_inscription_'.$n} = datefr_us($_POST['date_inscription_'.$n]);

	${'nom_'.$n} = post_secure($sql_link,$_POST['nom_'.$n]);
	${'prenom_'.$n} = post_secure($sql_link,$_POST['prenom_'.$n]);
	${'sexe_'.$n} = post_secure($sql_link,$_POST['sexe_'.$n]);	
	${'date_naissance_'.$n} = datefr_us($_POST['date_naissance_'.$n]);
	
	
	${'tel_'.$n} = post_secure($sql_link,$_POST['tel_'.$n]);
	${'email_'.$n} = post_secure($sql_link,$_POST['email_'.$n]);
	${'adresse_'.$n} = post_secure($sql_link,$_POST['adresse_'.$n]);
	${'ville_'.$n} = post_secure($sql_link,$_POST['ville_'.$n]);	
	${'ile_'.$n} = post_secure($sql_link,$_POST['ile_'.$n]);
	//$pays = post_secure($sql_link,$_POST['pays']);
	
	${'certificat_'.$n} = post_secure($sql_link,$_POST['certificat_'.$n]);
	${'club_'.$n} = post_secure($sql_link,$_POST['club_'.$n]);	
	${'entreprise_'.$n} = post_secure($sql_link,$_POST['entreprise_'.$n]);	
	${'soiree_'.$n} = post_secure($sql_link,$_POST['soiree_'.$n]);		
	${'teeshirt_'.$n} = post_secure($sql_link,$_POST['teeshirt_'.$n]);
	${'reglement_'.$n} = post_secure($sql_link,$_POST['reglement_'.$n]);
}



/*  
----------------------------------------
VALIDATION ET VERIFICATION DES DONNEES FORMULAIRES 
----------------------------------------
*/

// Champ vide

if(!tep_not_null($nom_1))
{
	$error=1;
	$message_info .= gestion_erreur_text('Nom de l\'équipier n°1',$error);
}



		
/*  
----------------------------------------
ENREGISTREMENT DS LES BASES 
----------------------------------------
*/

if($error<=0)
{
	if(!$modif)
	{
		$sql = "SELECT max(dossard) as last FROM ".TABLE_INSCRIPTION." ORDER BY dossard=".$dossard;
		$last_query = tep_db_query($sql_link,$sql);
		$last = tep_db_fetch_array($last_query);
		
		if(tep_not_null($last['last'])){$dossard=$last['last']+1;}
	
	
		for($n=1;$n<3;$n++)
		{
			tep_db_query($sql_link,"INSERT INTO ".TABLE_INSCRIPTION." (date_inscription,dossard,nom,prenom,sexe,date_naissance,adresse,tel,email,ville,ile,club,entreprise,certificat,soiree,teeshirt,reglement) ".
															" VALUES ('".${'date_inscription_'.$n}.
															"','".$dossard.
															"', '".${'nom_'.$n}.
															"', '".${'prenom_'.$n}.
															"', '".${'sexe_'.$n}.
															"', '".${'date_naissance_'.$n}.
															"', '".${'adresse_'.$n}.
															"', '".${'tel_'.$n}.
															"', '".${'email_'.$n}.
															"', '".${'ville_'.$n}.
															"', '".${'ile_'.$n}.
															"', '".${'club_'.$n}.
															"', '".${'entreprise_'.$n}.
															"', '".${'certificat_'.$n}.
															"', '".${'soiree_'.$n}.
															"', '".${'teeshirt_'.$n}.
															"', '".${'reglement_'.$n}."')");	
		}		
	
		$modif = true;
	
	}	
	else
	{		
		
		
		
		for($n=1;$n<3;$n++)
		{
			tep_db_query($sql_link,"UPDATE ".TABLE_INSCRIPTION." SET date_inscription='" . ${'date_inscription_'.$n}.
													"', nom='" . ${'nom_'.$n} . 
													"', prenom='" . ${'prenom_'.$n} . 
													"', sexe='" . ${'sexe_'.$n} . 
													"', date_naissance='" . ${'date_naissance_'.$n} . 
											        "', adresse='" . ${'adresse_'.$n} . 
													"', tel='" . ${'tel_'.$n} . 
													"', email='" . ${'email_'.$n} . 
													"', ville='" . ${'ville_'.$n} . 
													"', ile='" . ${'ile_'.$n} . 
											        "', club='" . ${'club_'.$n} . 
											        "', entreprise='" . ${'entreprise_'.$n} . 
													"', certificat='" . ${'certificat_'.$n} . 
													"', soiree='" . ${'soiree_'.$n} . 
													"', teeshirt='" . ${'teeshirt_'.$n} . 
													"', reglement='" . ${'reglement_'.$n} . 											
								        			"' WHERE id=" . ${'id_eq_'.$n}); 
			
		}															
		
	
	
	}	
	
	
	
	$message_info .= gestion_erreur_text('Inscription',0);    
	
}


?>
