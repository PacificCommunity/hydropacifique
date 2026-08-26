<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Fonctions pour vérifier la validité des champs formulaires
*/



// Valider une date au format dd-mm-YYYY
function validDate($value)
{
	// Retourner true si la valeur est vide
    //if ($value === '') {return true;}

	// Convertir la date en timestamp en utilisant strtotime
	$timestamp = strtotime($value);

	// Vérifier si la date est valide et au format attendu "dd-mm-YYYY"
	if ($timestamp !== false && date('d-m-Y', $timestamp) === $value){return true;}
	else{return false;}
}

function validTime($value)
{
    // Retourner true si la valeur est vide
    if ($value === '') {return true;}
	
	// Vérifier si l'heure est au format "hh:mm:ss" ou au format "hh:mm"
	if (preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $value)){return true;}
	else{return false;}
}

function validNumeric($value)
{
    if($value === '') {return true;}
    // Accepte le séparateur décimal virgule comme le point
    if(is_numeric(str_replace(',', '.', $value))) {return true;}
    else {return false;}
}

function preparerSQL($val) 
{
    if ($val === '' || $val === NULL) return "NULL";
    return "'" . str_replace(',', '.', $val) . "'";
}


// ----------------------------------------
// Gestion des Erreurs form text
function gestion_erreur_text($type,$error)	
{
	$message='';

	switch($error)
	{
		case 0: //enregistrement ok
		 	$message .= "<p><b>" . $type . "</b> a été enregistré</p>";   	
           		break;  
           	case 1: //champ vide
           		$message .= "<p>Erreur - le champ <b>" . $type . "</b> est obligatoire</p>";   	
           		break;  	
		case 2: //extension non vapde
           		$message .= "<p>Erreur - l'url  <b>" . $type . "</b> n'est pas valide</p>";   	
           		break;        	
           	case 3: //email non vapde
           		$message .= "<p>Erreur - l'adresse mail <b>" . $type . "</b> n'est pas valide</p>";     	
           		break; 	
           	case 4: //login déjà existant
           		$message .= "<p>Erreur - <b>" . $type . "</b> : déjà utilis;</p>";   	
           		break;      
		case 5: //Test si nombre entier
           		$message .= "<p>Erreur - il faut saisir un nombre entier dans le champs <b>" . $type . "</b></p>";   	
           		break;                		       		
       }//fin switch
       
       return $message;
}


// ----------------------------------------
// Gestion des Erreurs password

function gestion_erreur_pass($type,$error)	
{
	$message='';

	switch($error)
	{
		case 0: //enregistrement ok
		 	$message .= "<p><b>" . $type . "</b> a été enregistré</p>";      	
           		break;  
           	case 1: //ancien password mauvais
           		$message .= "<p>Erreur <b>" . $type . "</b> : ancien mot de passe invalide</p>";   	
           		break;  
           	case 2: //new password trop court
           		$message .= "<p>Erreur <b>" . $type . "</b> : 6 caractères minimum</p>";   	
           		break;  	
		case 3: //2 new mot de passe différent
           		$message .= "<p>Erreur <b>" . $type . "</b> : nouveaux mots de passe diff&eacute;rents</p>";   	
           		break;     
           	case 4: //3 champs obpgatoires
           		$message .= "<p>Erreur <b>" . $type . "</b> : les 3 champs sont obligatoires</p>";   	
           		break;   		
       }//fin switch
       
       return $message;
}




?>
