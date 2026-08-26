<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Toutes les fonctions liées au traitement des dates
*/


// Verifie si la date est valide au format Excel (fonction pour l'importation de fichier)
function isValidExcelDate($value) {
    if (is_numeric($value)) {
        // Les dates Excel sont représentées sous forme de nombres.
        // Un nombre positif ou nul peut être une date valide.
        // On vérifie également si le nombre n'est pas trop grand, pour éviter d'éventuels débordements.
        return ($value >= 0 && $value < 2958466); // 2958466 est une date équivalente au 31 décembre 9999 en format Excel.
    }

    return false;
}

// Convertir la date Excel en format PHP
function excelDateToPhpDate($value) 
{
    // Conversion de la date Excel en une date lisible par PHP
	$timestamp = ($value - 25569) * 86400;
	$date_temp = new DateTime("@$timestamp");
	// Formatage de la date selon le format souhaité (01/01/2018 18:06:27)
	//$date_validExcel = $date_temp->format('d/m/Y H:i:s');

	return $date_temp;
}



// Vérifie à la fois si la date au format texte valable et renvoyer un format pour enregistrement dans la base 'Y-m-d H:i:s'
function isValidDateImport($cellValue)
{	
	$cellValue = preg_replace('/[[:^print:]]/', '', $cellValue); // Supprimer les espaces éventuels autour de la valeur
	$cellValue = trim($cellValue, '"'); // Enlever les guillemets doubles autour de la date si présents

	// Sauvegarder le fuseau horaire actuel
    $originalTimeZone = date_default_timezone_get();
    // Forcer UTC pour éviter les problèmes liés au DST
    date_default_timezone_set('UTC');

	// Liste des formats de dates possibles
    $dateFormats = [
        'd/m/Y H:i:s', // 25/12/2023 12:34:56
        'd-m-Y H:i:s', // 25-12-2023 12:34:56
        'Y-m-d H:i:s', // 2023-12-25 12:34:56
        'Y/m/d H:i:s', // 2023/12/25 12:34:56
		'd/m/Y H:i', // 25/12/2023 12:34
        'd-m-Y H:i', // 25-12-2023 12:34:56
        'Y-m-d H:i', // 2023-12-25 12:34:56
        'Y/m/d H:i', // 2023/12/25 12:34:56
		'd/m/Y',
		'Y/m/d',
		'd-m-Y',
		'Y-m-d'
    ];

    foreach ($dateFormats as $format) 
	{
        $dateTime = DateTime::createFromFormat($format, $cellValue);

        //if ($dateTime !== false && $dateTime->format($format) === $cellValue) 
		if ($dateTime !== false) 
		{
			// Restaurer le fuseau horaire d'origine
            date_default_timezone_set($originalTimeZone);
            // Si la date est valide, la formater en Y-m-d H:i:s
            return $dateTime->format('Y-m-d H:i:s');
        }
    }
	
	// Restaurer le fuseau horaire d'origine
    date_default_timezone_set($originalTimeZone);

    return 'Invalid';
}

function isValidTimeImport($cellValue)
{
    $cellValue = trim($cellValue, '"'); // Enlever les guillemets doubles autour de l'heure si présents
    $cellValue = preg_replace('/[[:^print:]]/', '', $cellValue); // Supprimer les espaces éventuels autour de la valeur

    // Liste des formats d'heure possibles
    $timeFormats = [
        'H:i:s', // 23:59:59
        'H:i',   // 23:59
    ];

    foreach ($timeFormats as $format) {
        $time = DateTime::createFromFormat($format, $cellValue);

        if ($time !== false && $time->format($format) === $cellValue) {
            // Si l'heure est valide, la retourner formatée en H:i:s
            return $time->format('H:i:s');
        }
    }

    return 'Invalid';
}

function heure_to($heure) 
{ 
	$heure_return='';
	
	if(tep_not_null($heure))
	{
		$hh = substr($heure,0,2);
		$mm = substr($heure,2,2); 
		$ss = substr($heure,4,2); 
		
		$heure_return = $hh.':'.$mm.':'.$ss;
	}		
	return $heure_return; 
} 

// Convertir une date fr en format us
function datefr_us($date_verif) 
{ 
	if (tep_not_null($date_verif)) 
	{
        $date = DateTime::createFromFormat('d-m-Y', $date_verif);
        if ($date) 
		{
            return $date->format('Y-m-d');
        }
    }
} 

// Convertir une date us format fr
function dateus_fr($date) 
{ 
	$date_return='';
	
	if(tep_not_null($date))
	{
		$split = explode('-',$date); 
		$annee = $split[0]; 
		$mois = $split[1]; 
		$jour = $split[2]; 
		
		$date_return=$jour.'-'.$mois.'-'.$annee;
	}		
	
	return $date_return; 	
} 

function convert_name_mois_fr($num_mois)
{
	$texte_mois = array('janvier','f&eacute;vrier','mars','avril','mai','juin','juillet','ao&ucirc;t','septembre','octobre','novembre','d&eacute;cembre');
	return $texte_mois[$num_mois-1];
}

//mise en forme des dates avec un ajout de jour possible, si on ne veut pas d'ajout $plus=0
function date_mise_en_forme($date,$plus=0) 
{ 
	
	$texte_mois = array('janvier','f&eacute;vrier','mars','avril','mai','juin','juillet','ao&ucirc;t','septembre','octobre','novembre','d&eacute;cembre');

	$split = explode("-",$date); 
	$nb_annee = $split[0]; 	
	$nb_mois = $split[1]; 
	$nb_jour = $split[2];


	$date_mef = date("d-m-Y", mktime(0, 0, 0, $nb_mois, $nb_jour + $plus, $nb_annee));
	
	$split = explode("-",$date_mef); 
	$jour = $split[0]; 	
	$mois = $split[1]; 
	$annee = $split[2];	
	
	return $jour . " " . $texte_mois[(int)$mois - 1] . " " . $annee; 
}


// Fonction qui permet de changer Y-m-j en un type mktime
function date_mktime($date) 
{ 
	$split = explode("-",$date); 
	$nb_annee = $split[0]; 	
	$nb_mois = $split[1]; 
	$nb_jour = $split[2];


	return mktime(0, 0, 0, $nb_mois, $nb_jour, $nb_annee);
}


// Permet de générer une liste avec les mois sans mois vide
function select_mois($name_select,$en_cours=1,$form=null)
{
	if(tep_not_null($form)){$form_click = 'onchange=\'' . $form . '.submit();\''; }
	else{$form_click = '';}
	
	$mois = array('Janvier','F&eacute;vrier','Mars','Avril','Mai','Juin','Juillet','Ao&ucirc;t','Septembre','Octobre','Novembre','D&eacute;cembre');
	
	
	$info_select = "<select name='".$name_select."' id='".$name_select."' style='width:90px;' ".$form_click.">";
		
		for($m=1;$m<=12;$m++)
		{		
			if($m==$en_cours){$selected='selected';}
			else{$selected='';}
			$mm=$m;if($m<10){$mm='0'.$m;}
			
			$info_select .= "<option value='".$mm."' ".$selected."  >".htmlaccent($mois[($m-1)])."</option>";
		}
	$info_select .= "</select>";
	
	
	return $info_select;
}	

// Permet de générer une liste avec les mois avec mois vide
function select_mois_vide($name_select)
{
	$mois = array('Janvier','F&eacute;vrier','Mars','Avril','Mai','Juin','Juillet','Ao&ucirc;t','Septembre','Octobre','Novembre','D&eacute;cembre');
	
	$info_select = "<select name='".$name_select."' id='".$name_select."' style='width:80px;' >";
		
	$info_select .= "<option value='0'>-</option>";

		for($m=1;$m<=12;$m++)
		{		
			//$mm=$m;if($m<10){$mm='0'.$m;}
			
			$info_select .= "<option value='".$m."' >".htmlaccent($mois[($m-1)])."</option>";
		}
	$info_select .= "</select>";
	
	
	return $info_select;
}	

// Permet de générer une liste avec les heures de la journée
function select_heure($name_select,$en_cours,$form=null)
{
	if(tep_not_null($form)){$form_click = 'onchange=\'' . $form . '.submit();\''; }
	else{$form_click = '';}
	
	$info_select = "<select name='".$name_select."' id='".$name_select."' style='width:50px;' ".$form_click.">";
		
		for($h=0;$h<=23;$h++)
		{		
			if($h==$en_cours){$selected='selected';}
			else{$selected='';}
			$hh=$h;if($h<10){$hh='0'.$h;}
			
			$info_select .= "<option value='".$h."' ".$selected."  >".$hh."</option>";
		}
	$info_select .= "</select>";
	
	
	return $info_select;
}	

// Fonction qui permet de trouver les jours de l'année . Les jours sont fournis dans un tableau
function the_days_of_year($year)
{
	// Janvier
	for($days=1;$days<=31;$days++)
	{
		if($days<10){$d='0'.$days;}
		else{$d=$days;}
		$mois = '01';
		$tab_days[] = $year.'-'.$mois.'-'.$d;
	}
	
	// Février	
	$nb_days=28;
	//if($year%400){$nb_days=29;}
	for($days=1;$days<=$nb_days;$days++)
	{
		if($days<10){$d='0'.$days;}
		else{$d=$days;}
		$mois = '02';
		$tab_days[] = $year.'-'.$mois.'-'.$d;
	}
	
	// Mars
	for($days=1;$days<=31;$days++)
	{
		if($days<10){$d='0'.$days;}
		else{$d=$days;}
		$mois = '03';
		$tab_days[] = $year.'-'.$mois.'-'.$d;
	}
	
	// Avril
	for($days=1;$days<=30;$days++)
	{
		if($days<10){$d='0'.$days;}
		else{$d=$days;}
		$mois = '04';
		$tab_days[] = $year.'-'.$mois.'-'.$d;
	}
	
	// Mai
	for($days=1;$days<=31;$days++)
	{
		if($days<10){$d='0'.$days;}
		else{$d=$days;}
		$mois = '05';
		$tab_days[] = $year.'-'.$mois.'-'.$d;
	}
	
	// Juin
	for($days=1;$days<=30;$days++)
	{
		if($days<10){$d='0'.$days;}
		else{$d=$days;}
		$mois = '06';
		$tab_days[] = $year.'-'.$mois.'-'.$d;
	}
	
	// Juillet
	for($days=1;$days<=31;$days++)
	{
		if($days<10){$d='0'.$days;}
		else{$d=$days;}
		$mois = '07';
		$tab_days[] = $year.'-'.$mois.'-'.$d;
	}
	
	// Aout
	for($days=1;$days<=31;$days++)
	{
		if($days<10){$d='0'.$days;}
		else{$d=$days;}
		$mois = '08';
		$tab_days[] = $year.'-'.$mois.'-'.$d;
	}
	
	// Septembre
	for($days=1;$days<=30;$days++)
	{
		if($days<10){$d='0'.$days;}
		else{$d=$days;}
		$mois = '09';
		$tab_days[] = $year.'-'.$mois.'-'.$d;
	}
	
	// Octobre
	for($days=1;$days<=31;$days++)
	{
		if($days<10){$d='0'.$days;}
		else{$d=$days;}
		$mois = 10;
		$tab_days[] = $year.'-'.$mois.'-'.$d;
	}
	
	// Novembre
	for($days=1;$days<=30;$days++)
	{
		if($days<10){$d='0'.$days;}
		else{$d=$days;}
		$mois = 11;
		$tab_days[] = $year.'-'.$mois.'-'.$d;
	}
	
	// Décembre
	for($days=1;$days<=31;$days++)
	{
		if($days<10){$d='0'.$days;}
		else{$d=$days;}
		$mois = 12;
		$tab_days[] = $year.'-'.$mois.'-'.$d;
	} 



	return $tab_days;
}


// Fonction qui permet d'avoir le nombre de jours par mois de l'année en intégrant février avec la gestion des années bisextiles
function list_month($year)
{
	$list_month[] = array('month' => htmlaccent('janvier'),
						  'nb_days' => 31);
	$nb_days=28;
	if($year%400){$nb_days=29;}					  
	$list_month[] = array('month' => htmlaccent('février'),
						  'nb_days' => $nb_days);					  
	$list_month[] = array('month' => htmlaccent('mars'),
						  'nb_days' => 31);
	$list_month[] = array('month' => htmlaccent('avril'),
						  'nb_days' => 30);					  
	$list_month[] = array('month' => htmlaccent('mai'),
						  'nb_days' => 31);
	$list_month[] = array('month' => htmlaccent('juin'),
						  'nb_days' => 30);
	$list_month[] = array('month' => htmlaccent('juillet'),
						  'nb_days' => 31);
	$list_month[] = array('month' => htmlaccent('août'),
						  'nb_days' => 31);					  
	$list_month[] = array('month' => htmlaccent('septembre'),
						  'nb_days' => 30);
	$list_month[] = array('month' => htmlaccent('octobre'),
						  'nb_days' => 31);					  
	$list_month[] = array('month' => htmlaccent('novembre'),
						  'nb_days' => 30);
	$list_month[] = array('month' => htmlaccent('décembre'),
						  'nb_days' => 31);
						  
						  
	return $list_month;


}


?>
