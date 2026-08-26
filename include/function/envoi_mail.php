<?php
/*  
----------------------------------------
Copyright (c) 2015 - Vai-Natura
----------------------------------------
*/
function mail_simple($expediteur,$adresse_reponse,$destinataire,$sujet,$copie_mail,$message)
{
	$error_mail = -1;
	$entete = "";	
		
	$entete .= "From: ".$expediteur."\r\n";
	$entete .= "Reply-To: ".$adresse_reponse."\r\n";
	$entete .= 'Content-Type: text/html; charset="iso-8859-1"'."\r\n";
	$entete .= 'Content-Transfer-Encoding: 8bit';
	
	//envoi du mail nl
	if(mail($destinataire,$sujet,$message,$entete)){$error_mail = 0;}
	else{$error_mail = 0;}
	
	return $error_mail;
}	



// fonction permettant de récupérer le temps écoulé depuis l'époque UNIX ( 1 - 1 1970 )
// donc permet un calcul sur le temps d'executionr d'un script
function temps()
{
	$time = microtime();
	$tableau = explode(" ",$time);
	return ($tableau[1] + $tableau[0]);
}


?>
