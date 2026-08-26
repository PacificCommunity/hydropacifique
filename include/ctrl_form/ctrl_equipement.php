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

$designation = post_secure($sql_link,$_POST['designation']);

$tab_type = explode('_',post_secure($sql_link,$_POST['type_eq']));
$type = $tab_type[0];
/*
if($type==1){$type_eq=post_secure($sql_link,'Pluviomètre');}
if($type==2){$type_eq=post_secure($sql_link,'Limnimètre');}
if($type==3){$type_eq=post_secure($sql_link,'Débitmètre');}
*/

$fabricant = post_secure($sql_link,$_POST['fabricant']);
$description = post_secure($sql_link,$_POST['description']);


if($modif)
{
	$ext_file = post_secure($sql_link,$_POST['ext_file']);
	$qte = str_replace(',','.',post_secure($sql_link,$_POST['qte']));
	$format_eq = $_POST['format'];
	$format_eq = str_replace("'", "&quot;", $format_eq);
		
	$champ_datefirst = 0;
	if(isset($_POST['champ_datefirst'])){$champ_datefirst = 1;}	
	
	$champ_dateend = 0;
	if(isset($_POST['champ_dateend'])){$champ_dateend = 1;}	
}

/*  
----------------------------------------
VALIDATION ET VERIFICATION DES DONNEES FORMULAIRES 
----------------------------------------
*/

// Champ vide

if(!tep_not_null($designation))
{
	$error=1;
	$message_info .= gestion_erreur_text('Désignation du format',$error);
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
		tep_db_query($sql_link,"INSERT INTO ".TABLE_EQUIPEMENT." (designation,type_eq,fabricant,description) ".
													  " VALUES ('".$designation.
													  "','".$type.
													  "', '".$fabricant.
													  "', '".$description."')");	
													  
		$new_id_query = tep_db_query($sql_link,"SELECT max(id) as last_id FROM ".TABLE_EQUIPEMENT);
		$new_id = tep_db_fetch_array($new_id_query);
		$ref_id = $new_id['last_id'];		
																						  
													  
		$modif = true;
	}	
	else
	{		
		tep_db_query($sql_link,"UPDATE ".TABLE_EQUIPEMENT." SET designation='" . $designation.
												  "', type_eq='" . $type . 
												  "', fabricant='" . $fabricant . 
												  "', description='" . $description . 
												  "', format_eq='" . $format_eq . 
												  "', ext_file='" . $ext_file . 
												  "', qte='" . $qte . 
												  "', champ_datefirst='" . $champ_datefirst . 
												  "', champ_dateend='" . $champ_dateend . 
												  "' WHERE id=" . $ref_id); 
	
	}
	
	$message_info .= htmlaccent('Le format '.$designation.' a bien été enregistré.');    
	
}

//tep_redirect('list_equipements.php');


?>
