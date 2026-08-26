<?php
/*  
----------------------------------------
Copyright (c) 2015 - Vai-Natura
----------------------------------------
*/


/***************************************************/
/* fonctions validation formulaire        	   */
/***************************************************/


//VALID URL

function valid_url($url) 
{
	$valid = false;
	
	$regExp = "^(https?://)"
        	. "?(([0-9a-z_!~*'().&=+$%-]+: )?[0-9a-z_!~*'().&=+$%-]+@)?" //user@
        	. @"(([0-9]{1,3}\.){3}[0-9]{1,3}" // IP- 199.194.52.184
        	. "|" // allows either IP or domain
        	. @"([0-9a-z_!~*'()-]+\.)*" // tertiary domain(s)- www.
        	. @"([0-9a-z][0-9a-z-]{0,61})?[0-9a-z]\." // second level domain
        	. "[a-z]{2,6})" // first level domain- .com or .museum
        	. "(:[0-9]{1,4})?" // port number- :80
        	. "((/?)|" // a slash isn't required if there is no file name
        	. "(/[0-9a-z_!~*'().;?:@&=+$,%#-]+)+/?)$";
	
	if(eregi($regExp,$url)){$valid=true;}

	return $valid;	
}


function valid_mail($email)
{
	$valid = false;

	$atom   = '[-a-z0-9!#$%&\'*+\\/=?^_`{|}~]';   // caractères autorisés avant l'arobase
	$domain = '([a-z0-9]([-a-z0-9]*[a-z0-9]+)?)'; // caractères autorisés après l'arobase (nom de domaine)
                               
	$regex = '/^' . $atom . '+' .   // Une ou plusieurs fois les caractères autorisés avant l'arobase
	'(\.' . $atom . '+)*' .         // Suivis par zéro point ou plus
                                	// séparés par des caractères autorisés avant l'arobase
	'@' .                           // Suivis d'un arobase
	'(' . $domain . '{1,63}\.)+' .  // Suivis par 1 à 63 caractères autorisés pour le nom de domaine
                                	// séparés par des points
	$domain . '{2,63}$/i';          // Suivi de 2 à 63 caractères autorisés pour le nom de domaine



	// test de l'adresse e-mail
	if (preg_match($regex, $email)){$valid = true;} 
	
	return $valid;
}



?>
