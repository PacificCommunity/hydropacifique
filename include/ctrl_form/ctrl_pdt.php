<?php
/*  
----------------------------------------
Copyright (c) 2015 - Vai-Natura
----------------------------------------
*/
$error = 0;
$error_0 = 0;

/*  
----------------------------------------
RECUPERATION DES DONNEES FORMULAIRES
----------------------------------------
*/

$min = 0;
$min_0 = 0;
$lib = '';
$lib_0 = '';
	
//nouveau pas de temps
if(isset($_POST['min']) && tep_not_null($_POST['min']))
{
	$min_0 = post_secure($sql_link,$_POST['min']);
	$lib_0 = post_secure($sql_link,$_POST['lib']);
}
	



/*  
----------------------------------------
VALIDATION ET VERIFICATION DES DONNEES FORMULAIRES 
----------------------------------------
*/

// Champ vide

if(tep_not_null($min_0) && !is_numeric($min_0))
{
	$error_0=1;
	$message_info .= htmlaccent('Le champ Minutes doit être numérique.');
}



		
/*  
----------------------------------------
ENREGISTREMENT DS LES BASES 
----------------------------------------
*/

$sql = "SELECT DISTINCT * FROM ".TABLE_EXPORT_INTERVAL." ORDER BY min";
$pdt_query = tep_db_query($sql_link,$sql);
while ($pdt = tep_db_fetch_array($pdt_query)) 
{
	$lib = post_secure($sql_link,$_POST['lib_'.$pdt['id']]);
	$min = post_secure($sql_link,$_POST['min_'.$pdt['id']]);
	
	if(tep_not_null($min) && !is_numeric($min))
	{
		$error=1;
		$message_info .= htmlaccent('Le champ Minutes doit être numérique.');
	}
	
	if(tep_not_null($min) && $error==0)
	{
		tep_db_query($sql_link,"UPDATE ".TABLE_EXPORT_INTERVAL." SET libelle='".$lib."', min='".$min."' WHERE id=".$pdt['id']);
	}
}
if($error==0){$message_info .= htmlaccent('La liste des Pas de Temps a bien été mise à jour');}


if($error_0==0 && tep_not_null($min_0) && tep_not_null($lib_0))
{
	tep_db_query($sql_link,"INSERT INTO ".TABLE_EXPORT_INTERVAL." (libelle,min) VALUES ('".$lib_0."','".$min_0."')");	
		
	$message_info .= htmlaccent('Le nouveau Pas de Temps a bien été enregistré');   
	
}

?>
