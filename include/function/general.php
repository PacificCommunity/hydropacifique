<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Fonctions assez générale, utilisé pour contrôler des valeurs ou modifier des affichages. 
*/

function colorList()
{
    return [
         1 => '#1F77B4',  // Strong blue
         2 => '#FF7F0E',  // Strong orange
         3 => '#2CA02C',  // Strong green
         4 => '#D62728',  // Strong red
         5 => '#9467BD',  // Strong purple
         6 => '#8C564B',  // Brown
         7 => '#E377C2',  // Pink
         8 => '#17BECF',  // Cyan
         9 => '#BCBD22',  // Olive yellow
        10 => '#7F7F7F',  // Mid grey
 
        11 => '#0D47A1',  // Dark blue
        12 => '#E65100',  // Dark orange
        13 => '#1B5E20',  // Dark green
        14 => '#7B1818',  // Dark red
        15 => '#4A148C',  // Dark purple
        16 => '#3E2723',  // Dark brown
        17 => '#AD1457',  // Magenta
        18 => '#006064',  // Dark teal
    ];
}
 
 
/**
 * Returns the MANUAL PICKER palette (rich, organized by hue).
 *
 * 6-column visual grid, 10 rows:
 *
 *   Row 1  (1-6)   : Blues          (light → dark)
 *   Row 2  (7-12)  : Cyans / teals
 *   Row 3  (13-18) : Greens
 *   Row 4  (19-24) : Yellows / olives
 *   Row 5  (25-30) : Oranges
 *   Row 6  (31-36) : Reds
 *   Row 7  (37-42) : Purples / violets
 *   Row 8  (43-48) : Pinks
 *   Row 9  (49-54) : Browns
 *   Row 10 (55-60) : Greys / black
 *
 * @return array<int,string>  [1..60 => '#RRGGBB']
 */
function colorPalette()
{
    return [
        // Row 1 — Blues
         1 => '#AED6F1',  2 => '#7FB3D5',  3 => '#5499C7',  4 => '#2E86C1',  5 => '#2471A3',  6 => '#1F618D',

        // Row 2 — Cyans / teals
         7 => '#A3E4D7',  8 => '#76D7C4',  9 => '#48C9B0', 10 => '#1ABC9C', 11 => '#117A65', 12 => '#0E6251',

        // Row 3 — Greens
        13 => '#A9DFBF', 14 => '#7DCEA0', 15 => '#52BE80', 16 => '#28B463', 17 => '#1E8449', 18 => '#145A32',

        // Row 4 — Yellows / olives
        19 => '#FCF3CF', 20 => '#F9E79F', 21 => '#F7DC6F', 22 => '#F1C40F', 23 => '#B7950B', 24 => '#7D6608',

        // Row 5 — Oranges
        25 => '#FAD7A0', 26 => '#F5B041', 27 => '#F39C12', 28 => '#E67E22', 29 => '#D35400', 30 => '#A04000',

        // Row 6 — Reds
        31 => '#F1948A', 32 => '#E74C3C', 33 => '#C0392B', 34 => '#A93226', 35 => '#922B21', 36 => '#641E16',

        // Row 7 — Purples / violets
        37 => '#D7BDE2', 38 => '#BB8FCE', 39 => '#A569BD', 40 => '#8E44AD', 41 => '#6C3483', 42 => '#4A235A',

        // Row 8 — Pinks
        43 => '#FADBD8', 44 => '#F5B7B1', 45 => '#EC7063', 46 => '#E84393', 47 => '#C2185B', 48 => '#880E4F',

        // Row 9 — Browns
        49 => '#E6D2B5', 50 => '#D2B48C', 51 => '#C19A6B', 52 => '#A1887F', 53 => '#795548', 54 => '#4E342E',

        // Row 10 — Greys / black
        55 => '#FAFAFA', 56 => '#BDBDBD', 57 => '#7F8C8D', 58 => '#34495E', 59 => '#2C3E50', 60 => '#000000',
    ];
}


function affichemots($texte,$mots)   
{   
   $NewString = '';
   	
   $StringTab = explode(" ",$texte);   
   
   if(sizeof($StringTab) < $mots)
   {
   	$mots = sizeof($StringTab);
   }
   
   for($i=0;$i<$mots;$i++)   
   {   
      $NewString .= " " . $StringTab[$i];   
   } 
     
   if(sizeof($StringTab) > $mots)
   {
      $NewString.=" ...";
   }

   return $NewString;  
} 

function affichelettres($phrase,$lettre)     
{   
   $truncatedPhrase = substr($phrase, 0, $lettre);
	if (strlen($phrase) > $lettre)
	{
		$truncatedPhrase .= "...";
	} 
	return $truncatedPhrase;
} 


function nettoyer_et_echapper($text)
{
    if (is_string($text)) {
        $text = str_replace(["\n", "\r"], ' ', $text); // Remplace les retours chariots et nouvelles lignes par des espaces
        $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); // Échappe les caractères spéciaux
    }
    return $text;
}


function un_htmlspecialchars($chaine) 
{
	if(tep_not_null($chaine))
	{
		if( strlen($chaine) < 4 ) 
		{
			return $chaine;
		} 
		else 
		{
			$string=str_replace('&nbsp;', ' ', $chaine);
			$string=str_replace('&quot;', '"', $chaine);
			$string=str_replace('&#039;', "'", $chaine);
			$string=str_replace('&gt;', '>', $chaine);
			$string=str_replace('&lt;', '<', $chaine);
			$string=str_replace('&amp;', '&', $chaine);
			
			return $string;
		}
	}
	else{return $chaine;}
}

function htmlaccent($chaine)
{
  	if(tep_not_null($chaine))
	{
		$chaine=str_replace("'","&acute;",$chaine);
		$chaine=str_replace("À","&Agrave;",$chaine);
		$chaine=str_replace("Á","&Aacute;",$chaine);
		$chaine=str_replace("Â","&Acirc;",$chaine);
		$chaine=str_replace("Ã","&Atilde;",$chaine);
		$chaine=str_replace("Ä","&Auml;",$chaine);
		$chaine=str_replace("Å","&Aring;",$chaine);
		$chaine=str_replace("à","&agrave;",$chaine);
		$chaine=str_replace("á","&aacute;",$chaine);
		$chaine=str_replace("â","&acirc;",$chaine);
		$chaine=str_replace("ã","&atilde;",$chaine);
		$chaine=str_replace("ä","&auml;",$chaine);
		$chaine=str_replace("å","&aring;",$chaine);
		$chaine=str_replace("Ò","&Ograve;",$chaine);
		$chaine=str_replace("Ó","&Oacute;",$chaine);
		$chaine=str_replace("Ô","&Ocirc;",$chaine);
		$chaine=str_replace("Õ","&Otilde;",$chaine);
		$chaine=str_replace("Ö","&Ouml;",$chaine);
		$chaine=str_replace("Ø","&Oslash;",$chaine);
		$chaine=str_replace("ò","&ograve;",$chaine);
		$chaine=str_replace("ó","&oacute;",$chaine);
		$chaine=str_replace("ô","&ocirc;",$chaine);
		$chaine=str_replace("õ","&otilde;",$chaine);
		$chaine=str_replace("ö","&ouml;",$chaine);
		$chaine=str_replace("ø","&oslash;",$chaine);
		$chaine=str_replace("È","&Egrave;",$chaine);
		$chaine=str_replace("É","&Eacute;",$chaine);
		$chaine=str_replace("Ê","&Ecirc;",$chaine);
		$chaine=str_replace("Ë","&Euml;",$chaine);
		$chaine=str_replace("è","&egrave;",$chaine);
		$chaine=str_replace("é","&eacute;",$chaine);
		$chaine=str_replace("ê","&ecirc;",$chaine);
		$chaine=str_replace("ë","&euml;",$chaine);
		$chaine=str_replace("Ç","&Ccedil;",$chaine);
		$chaine=str_replace("ç","&ccedil;",$chaine);
		$chaine=str_replace("Ì","&Igrave;",$chaine);
		$chaine=str_replace("Í","&Iacute;",$chaine);
		$chaine=str_replace("Î","&Icirc;",$chaine);
		$chaine=str_replace("Ï","&Iuml;",$chaine);
		$chaine=str_replace("ì","&igrave;",$chaine);
		$chaine=str_replace("í","&iacute;",$chaine);
		$chaine=str_replace("î","&icirc;",$chaine);
		$chaine=str_replace("ï","&iuml;",$chaine);
		$chaine=str_replace("Ù","&Ugrave;",$chaine);
		$chaine=str_replace("Ú","&Uacute;",$chaine);
		$chaine=str_replace("Û","&Ucirc;",$chaine);
		$chaine=str_replace("Ü","&Uuml;",$chaine);
		$chaine=str_replace("ù","&ugrave;",$chaine);
		$chaine=str_replace("ú","&uacute;",$chaine);
		$chaine=str_replace("û","&ucirc;",$chaine);
		$chaine=str_replace("ü","&uuml;",$chaine);
		$chaine=str_replace("ÿ","&yacute;",$chaine);
		$chaine=str_replace("Ñ","&Ntilde;",$chaine);
		$chaine=str_replace("ñ","&ntilde;",$chaine);
		

		//$chaine = nl2br(htmlspecialchars($chaine));
		$chaine = nl2br($chaine);
	}

  	return $chaine;
}

//enlever les accents
function noaccent($chaine)
{  
 	  $chaine = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $chaine); 
    $chaine = preg_replace('/[^A-Za-z0-9\- ]/', '', $chaine);
   
   	return $chaine;
}

function search_accent($chaine)
{  
 	//echo $chaine;
	if(preg_match("ÀÁÂÃÄÅàáâãäåÒÓÔÕÖØòóôõöøÈÉÊËèéêëÇçÌÍÎÏìíîïÙÚÛÜùúûüÿÑñ",$chaine)){return true;}
	else{return false;}  
}


// verifie si une valeur est null
/*
function tep_not_null($value) 
{
    if (is_array($value)) 
    {
      if (sizeof($value) > 0) 
      {
        return true;
      } 
      else 
      {
        return false;
      }
    } 
    else 
    {
      if ( (is_string($value) || is_int($value)) && ($value != '') && ($value != 'NULL') && (strlen(trim($value)) > 0)) 
      {
        return true;
      } 
      else 
      {
        return false;
      }
    }
}
*/
// Fonction amélioré en 2024
function tep_not_null($value) 
{
  if (is_array($value)) {
      // Vérifier si le tableau est non vide
      return !empty($value);
  }
  
  // Vérifier si la valeur est une chaîne ou un entier non vide
  return (is_string($value) || is_numeric($value)) && trim($value) !== '' && $value !== 'NULL';
}


function tep_output_string($string, $translate = false, $protected = false) 
{
    if ($protected == true) 
    {
      return htmlspecialchars($string);
    } 
    else 
    {
      if ($translate == false) 
      {
        return tep_parse_input_field_data($string, array('"' => '&quot;'));
      } 
      else 
      {
        return tep_parse_input_field_data($string, $translate);
      }
    }
}

// Parse the data used in the html tags to ensure the tags will not break
function tep_parse_input_field_data($data, $parse) 
{
    return strtr(trim($data), $parse);
}

// Fonction pour vérifier et convertir les valeurs
function validate_and_convert(&$variable, &$data_valid_flag, $type = 'int') 
{
  if ($variable !== '') {
      if (is_numeric($variable)) {
          // Convertir selon le type spécifié
          if ($type === 'int') {
              $variable = (int)$variable;
          } elseif ($type === 'float') {
              $variable = floatval($variable);
          }
      } else {
          // Marquer la validation comme échouée si la valeur n'est pas numérique
          $data_valid_flag = false;
      }
  }
}

// Vérification qu'une valeur est bien un nombre décimal
function isDecimal($value) 
{
    // Vérifier si la chaîne est vide ou uniquement des espaces blancs
    if(trim($value) === '') 
    {
        return false; // Une chaîne vide n'est pas un nombre décimal
    }

    // Remplacer les virgules par des points 
    $normalizedValue = str_replace(',', '.', $value);

    // Vérifier si la chaîne nettoyée est un nombre
    if (is_numeric($normalizedValue)) 
    {
        // Utiliser une expression régulière pour confirmer le format
        return preg_match('/^-?\d+(\.\d+)?$/', $normalizedValue) === 1;
    }   

  return false; // Si ce n'est pas un nombre valide
}



// Redirection de pafe
function tep_redirect($url) 
{
    header('Location: ' . $url);
    exit;
}


/* valeur aléatoire - utilisée pour le hash des mdp */
function tep_rand($min = null, $max = null) 
{
    static $seeded;

    if (!$seeded) 
    {
      mt_srand((double)microtime()*1000000);
      $seeded = true;
    }

    if (isset($min) && isset($max)) 
    {
      if ($min >= $max) 
      {
        return $min;
      } 
      else 
      {
        return mt_rand($min, $max);
      }
    } 
    else 
    {
      return mt_rand();
    }
}


// Sécurisé des données avant de les afficher
function post_secure($sql_link,$var)
{
	$var_s = '';
	if (isset($var) && !empty($var)) 
  {
      $var_s = mysqli_real_escape_string($sql_link, $var);
  }
	
	return $var_s;
}


// Générateur de couleurs distinctes
function getRandomColor() 
{
    $r = mt_rand(0, 255); // Rouge entre 0 et 255
    $g = mt_rand(0, 255); // Vert entre 0 et 255
    $b = mt_rand(0, 255); // Bleu entre 0 et 255

    // Convertir les valeurs RVB en format hexadécimal
    $colorHex = sprintf("#%02X%02X%02X", $r, $g, $b);

    return $colorHex;
}

function nettoyerNomFichier($nomStation) 
{
    // 1. Remplacer les espaces par des tirets
    $nomFichier = str_replace(' ', '-', $nomStation);

    // 2. Supprimer les caractères non autorisés (garder lettres, chiffres, tirets et underscores)
    $nomFichier = preg_replace('/[^A-Za-z0-9_\-]/', '', $nomFichier);

    // 3. Convertir en minuscule pour uniformité
    $nomFichier = strtolower($nomFichier);

    // 4. Limiter la longueur si nécessaire (optionnel)
    $nomFichier = substr($nomFichier, 0, 100);

    return $nomFichier;
}

?>