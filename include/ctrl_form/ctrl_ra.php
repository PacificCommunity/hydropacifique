<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Enregistrement d'une fiche RA (Modification ou Création)
- post_secure : est une fonction accessible dans include/function/ permettant de contrôler et de corriger les entrées depuis des formulaires. Focntion de Sécurité contre insertion JS et PHP
- Pour la vérification des champs valeur numéric, date ou heure, les valeurs vide sont valides. Il faut se référer aux fonctions de contrôle validDate(), validTime(), valid
*/

// pour vérifier que l'enregistrement est en cours 
$message_erreur = '';
$message_action = '';

// --------------------------------------------------------------------------
// Récupération des données du formulaire soumis qui correspond à une fiche RA

$id_ra_modif = post_secure($sql_link,$_POST['id_ra']);

$valid_ra=0;
if(isset($_POST['check_valid_ra'])){$valid_ra=1;}

// Infos Général RA

$id_agent_user = post_secure($sql_link,$_POST['id_agent_user']);

//$date_saisie = post_secure($sql_link,$_POST['date_saisie']); // Vérifier format date // N'est pas pris en compte je ne comprends pas pour le moment
$select_station_ra = post_secure($sql_link,$_POST['select_station_ra']);

// Type Data (Pluie, CI, Piezo)
$select_type_ra = post_secure($sql_link,$_POST['select_type_ra']);

// ALL - Relève
$date_releve = post_secure($sql_link,$_POST['date_releve']); 
if(!validDate($date_releve)) // Vérifier format date
{
    $message_erreur .= htmlaccent('La date de relève n\'est pas au bon format : dd-mm-YYYY')."<br>"; 
    $date_releve = '';
}

$heure_releve = post_secure($sql_link,$_POST['heure_releve']); 
if(!validTime($heure_releve)) // Vérifier format heure
{
    $message_erreur .= htmlaccent('L\'heure de relève n\'est pas au bon format : hh:mm:ss ou hh:mm')."<br>";
    $heure_releve = '';
}
if($date_releve !== '')
{
    $datetime = $date_releve . ($heure_releve !== '' ? ' ' . $heure_releve : ''); // Concaténer date et heure (si $heure_ra n'est pas vide)
    $date_heure_ra = date('Y-m-d H:i:s', strtotime($datetime)); // Formater la date et l'heure
}

$fichier_releve = post_secure($sql_link,$_POST['fichier_releve']);




?>