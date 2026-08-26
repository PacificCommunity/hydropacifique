<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Export 
- Ce script permet de générer les données de la carte dans la page des index.php (la page d'accueil)
----------------------------------------
*/

// ----------------------------------------------
// nécessaire pour la configuration du script

require('../../config.php');
require('../../database_tables.php');

require('../../function/date.php');	
require('../../function/database.php');	
require('../../function/html_output.php');
require('../../function/general.php');


// pour solutionner les pb d'accents
header('Content-Type: text/html; charset=utf-8');


use proj4php\Proj4php;
use proj4php\Point;
use proj4php\Proj;

// Initialiser Proj4php
$proj4 = new Proj4php();

// Gestion des conversion coté serveur
$projWGS84 = new Proj('EPSG:4326', $proj4); // WGS84


// connexion à la base de données	
$sql_link = mysqli_connect(DB_SERVER,DB_SERVER_USERNAME,DB_SERVER_PASSWORD,DB_DATABASE) or die('Impossible de se connecter à la base de données!');
mysqli_query ($sql_link,'SET NAMES UTF8');


// Récupération des données JSON envoyées depuis la requête AJAX
$jsonDataMap = file_get_contents('php://input');

// Décoder les données JSON en un tableau associatif PHP
$dataJson = json_decode($jsonDataMap, true);

// Accéder aux données du tableau récupérer
$territoire_id = $dataJson['territoireId'];

// RECUPERATION DU TEXT - POUR LA TRADUCTION
require(DIR_WS_INCLUDE . 'text_content_'.LANGUAGE.'.php');

// Initialisation de variables - Récupération de données Génériques dans la table

$sql_territoire = "SELECT DISTINCT t.id_territoire, t.init_territoire, t.nom_territoire, t.timezone_php
					FROM ".TABLE_TERRITOIRE." t 
					WHERE t.id_territoire='".$territoire_id."'";
$territoire_query = tep_db_query($sql_link,$sql_territoire);
$territoire = tep_db_fetch_array($territoire_query);

$territoire_init = $territoire['init_territoire'];
$timezone_php = $territoire['timezone_php'];

date_default_timezone_set($timezone_php); 
$today = new DateTime(); // Crée un objet DateTime pour la date actuelle

// TABLE TYPE DATA (DEBIT, PLUIE, PIEZO, ...)
$sql_eq_type = "SELECT DISTINCT id_eq_type, nom_eq_type, type_color_border, type_color_background 
				FROM ".TABLE_EQ_TYPE." 
				WHERE active_eq_type=1 
				ORDER BY order_eq_type ASC";
$eq_type_query = tep_db_query($sql_link,$sql_eq_type);	
while ($eq_type = tep_db_fetch_array($eq_type_query))
{
	$eq_type_array[$eq_type['id_eq_type']] = array('nom_eq_type' => htmlaccent(html_entity_decode($eq_type['nom_eq_type'] ?? '')),
													'type_color_border' => htmlaccent(html_entity_decode($eq_type['type_color_border'] ?? '')),							
													'type_color_background' => htmlaccent(html_entity_decode($eq_type['type_color_background'] ?? '')),
													);
}

// Initialisation de fonction pour la gestion des coordonnées 

// Tableau des projections pour chaque territoire
// Gestion coté Serveur 


$territoriesProjections['NC']['lambert'] = new Proj(
    '+proj=lcc +lat_1=-20.66666666666667 +lat_2=-22.33333333333333 +lat_0=-21.5 +lon_0=166 ' .
    '+x_0=400000 +y_0=300000 +ellps=intl +towgs84=197.025,-193.922,175.185,0,0,0,0 +units=m +no_defs',
    $proj4
);


// Définir la projection UTM Zone 58S (EPSG:32758) pour la Nouvelle-Calédonie
$territoriesProjections['NC']['utm']  = new Proj(
	'+proj=utm +zone=58 +south +datum=WGS84 +units=m +no_defs', 
	$proj4
); 
$territoriesProjections['PF']['utm'] = new Proj(
    '+proj=utm +zone=6 +south +datum=WGS84 +units=m +no_defs', 
    $proj4
);
$territoriesProjections['WF']['utm'] = new Proj(
    '+proj=utm +zone=1 +south +datum=WGS84 +units=m +no_defs', 
    $proj4
);


// Fonction pour effectuer la conversion des coordonnées quelques soient leur origine (Lambert ou UTM) pour arriver au format universel Latitude / Longitude
function convertCoordinates($x, $y, $projFrom, $projTo, $proj4) 
{
    $point = new Point(floatval($x), floatval($y), $projFrom);
    $pointWGS84 = $proj4->transform($projTo, $point);
    return [$pointWGS84->x, $pointWGS84->y];  // Format [lat, long]
}

function dmsToDecimal($dms, $direction) 
{
    // Extraire les degrés, minutes et secondes avec une expression régulière
    preg_match('/(\d+)\D+(\d+)\D+(\d+(\.\d+)?)/', $dms, $parts);

    // Convertir les parties en valeurs numériques
    $degrees = (int)$parts[1];
    $minutes = (int)$parts[2] / 60;
    $seconds = (float)str_replace(',', '.', $parts[3]) / 3600;

    // Calculer la valeur décimale
    $decimal = $degrees + $minutes + $seconds;

    // Inverser le signe si la direction est Sud ou Ouest
    if ($direction === 'S' || $direction === 'W') {
        $decimal *= -1;
    }

    return $decimal;
}

// ------------------------------------------------------            
// Récupération des données Station à afficher

// TABLE STATION
// Pour la navigation cartographique

// Initialisation des variables de base pour la sélection des stations
$select_active_encours = 1;
$where_and_active = " AND s.active_station=1";

$select_suivi_encours = 1;
$where_and_suivi = " AND s.suivi=1";

$select_armee_encours = 0;
$where_and_armee = "";

$select_region_encours = 0;
$where_and_region = '';

$select_tournee_encours = 0;
$where_and_tournee = '';

$select_regionhydro_encours = 0;
$where_and_regionhydro = '';

$select_commune_encours = 0;
$where_and_commune = '';
$where_and_commune_station = '';

$where_and_type = '';
$where_and_type_data = '';

// Initialisation des variables nécessaires à la construction de la carte
$var_carte_coord_all = ''; // var texte pour définir les coordonnées des stations
$var_marker_all = ''; // var texte pour définir les markers des stations
$var_marker_group_all ='' ; // var texte pour définir lier un markers à un group de markers.

$legendPlu=0;$legendPluNonActive=0;$legendPluDesarmee=0;$legendPluPonctuel=0;
$legendHydro=0;$legendHydroNonActive=0;$legendHydroDesarmee=0;$legendHydroPonctuel=0;
$legendPiezo=0;$legendPiezoNonActive=0;$legendPiesoDesarmee=0;$legendPiezoPonctuel=0;

$nb_station = 0;
$nb_station_active = 0;
$nb_station_suivi = 0;
$nb_station_panne = 0;

// TABLE STATION avec les conditions des différents champs de sélection 
$sql_station = "SELECT DISTINCT s.id_station, s.id_service, s.nom_station, s.code_station, 
								s.id_commune, s.vallee_station, s.riviere_station,
                                s. active_station, s.station_type, s.suivi, s.armee,
                                s.date_installation_station, 
                                s.lamb_station_x, s.lamb_station_y, s.utm_station_x, s.utm_station_y, 
                                s.latitude_station, s.longitude_station 
				FROM ".TABLE_STATION." s
				WHERE s.id_territoire=".$territoire_id.$where_and_type.
										$where_and_regionhydro.$where_and_region.$where_and_commune.$where_and_tournee.
										$where_and_active.$where_and_suivi.$where_and_armee."
				ORDER BY code_station DESC";
$station_query = tep_db_query($sql_link,$sql_station);
while ($station = tep_db_fetch_array($station_query))
{	
	$id_station = $station['id_station'];
    
    $nom_station = $station['nom_station'];
    $code_station = $station['code_station'];    

	$nom_regionhydro = $station['vallee_station'];    
    //$id_regionhydro = html_entity_decode($station['id_commune'] ?? '');
    //$nom_regionhydro = $regionhydro_array[$id_regionhydro];
    
    
    $id_commune = $station['id_commune'];
    $nom_commune = '';
    if(tep_not_null($id_commune) && isset($commune_array[$id_commune])){$nom_commune = $commune_array[$id_commune];}
    
    $nom_riviere = $station['riviere_station'];
    /* Pour le moment riviere_station est un champs de texte mais il va être modifier en int avec id_rivière et relié à la table Geo_rivietre qui n'existe pas encore
    $id_riviere = html_entity_decode($station['id_riviere'] ?? '');
    $nom_riviere = $riviere_array[$id_riviere];
    */

    $type_data_station = $station['station_type'];  
    
    $active_station = $station['active_station'];  
    $suivi_station = $station['suivi'];  
    $armee_station = $station['armee'];  

	$nb_station++;
	if($active_station > 0){$nb_station_active++;}
	if($suivi_station > 0){$nb_station_suivi++;}
	if($armee_station > 0){$nb_station_panne++;}

    $date_installation = dateus_fr($station['date_installation_station']); 

    
	// ---------------------------------------
    // Récupération des infos du dernier RA, dernier passage

    // On compte le nombre de RA qui ont été validés
    $sql_last_ra = "SELECT id_ra, MAX(date_heure_ra) as latest_date_heure_ra
                    FROM ".TABLE_DATA_RA."						
                    WHERE id_station=".$id_station;

    $last_ra_query = tep_db_query($sql_link,$sql_last_ra);
    $last_ra_tab = tep_db_fetch_array($last_ra_query);

    // Petit code permettant d'obtenir le délais depuis le dernier RA validé
    $last_date_ra_formatted = '';
    $text_delais_last_ra = '';
    $id_ra_last = 0;

    if(isset($last_ra_tab['latest_date_heure_ra']) && ($last_ra_tab['latest_date_heure_ra'] !== null))
    {
        $id_ra_last = $last_ra_tab['id_ra'];
        
        // Crée un objet DateTime pour la dernière date valide
        $last_datetime_ra = new DateTime($last_ra_tab['latest_date_heure_ra']);

        // Formatte la date au format "d-m-Y"
        $last_date_ra_formatted = $last_datetime_ra->format('d-m-Y');

        // Calcule la différence entre latest_date_heure_ra et aujourd'hui
        $delais_last_ra = $today->diff($last_datetime_ra);

        // Affiche le résultat formaté
        if ($delais_last_ra->y > 0) {$text_delais_last_ra .= $delais_last_ra->y . " année(s) ";}
        if ($delais_last_ra->m > 0) {$text_delais_last_ra .= $delais_last_ra->m . " mois ";}
        if ($delais_last_ra->d > 0) {$text_delais_last_ra .= $delais_last_ra->d . " jour(s) ";}
    }

    // ---------------------------------------
    // Récupération et conversion (si nécessaire) des coordonnées géographiques des stations
	
	$var_carte_coord='';

	// On récupère les données de la station
	$latitude = str_replace(",", ".",$station['latitude_station']);  
	$longitude = str_replace(",", ".",$station['longitude_station']);  
	$utm_station_x = str_replace(",", ".",$station['utm_station_x']);  
	$utm_station_y = str_replace(",", ".",$station['utm_station_y']);  
	$lamb_station_x = str_replace(",", ".",$station['lamb_station_x']);
	$lamb_station_y = str_replace(",", ".",$station['lamb_station_y']);  

	
	if(tep_not_null($latitude) && tep_not_null($longitude)) // Cas où latitude et longitude sont disponibles directement
	{		
		if(is_numeric($latitude)){$coords_latitude = $latitude;}
		else{$coords_latitude = dmsToDecimal($latitude, 'S');}
		
		if(is_numeric($longitude)){$coords_longitude = $longitude;}
		else{$coords_longitude = dmsToDecimal($longitude, 'W');}
		
		$convertedCoords = [floatval($coords_longitude), floatval($coords_latitude)];  // Format [lon, lat]
	} 
	else 
	{	
		if (tep_not_null($utm_station_x) && tep_not_null($utm_station_y)) // Utilisation de UTM si disponible 
		{
			// Si les coordonnées UTM sont présentes pour le territoire choisi
			$convertedCoords = convertCoordinates($utm_station_x, $utm_station_y,$territoriesProjections[$territoire_init]['utm'], $projWGS84, $proj4);
			
		}
		else if (tep_not_null($lamb_station_x) && tep_not_null($lamb_station_y)) // Sinon, conversion en latitude/longitude depuis Lambert
		{			
			// Version conversion sur le serveur
			$convertedCoords = convertCoordinates($lamb_station_x, $lamb_station_y,$territoriesProjections[$territoire_init]['lambert'], $projWGS84, $proj4);
		}
	}
 
	// Introduction des coordonnées dans le js pour affichage dans leaflet si la station à des coordonnées
	if(isset($convertedCoords)) 
	{
        $var_carte_coord = "var convertedCoords = ".json_encode($convertedCoords).";";
    
		// Gestion des légendes et des marks sur la carte

		$text_toolTip = '';
		$text_statut = '';
		$var_marker = '';
		$var_marker_group = '';
		$icon_type = '';
            
        // On trouve le statut de la station

        if(($type_data_station == 1) && $active_station==1){$icon_type = 'iconPluActive';$legendPlu++;$text_statut .= ' Active ';}
        if(($type_data_station == 1) && $active_station==0){$icon_type = 'iconPluNonActive';$legendPluNonActive++;$text_statut .= ' Historique ';} 
        if(($type_data_station == 1) && $suivi_station==0){$icon_type = 'iconPluPonctuel';$legendPluPonctuel++;$text_statut .= ' Ponctuelle ';}
        if(($type_data_station == 1) && $armee_station==1){$icon_type = 'iconPluDesarmee';$legendPluDesarmee++;$text_statut .= ' <span style=\"color:#B80000;\">En panne</span> ';}

        if(($type_data_station == 11) && $active_station==1){$icon_type = 'iconHydroActive';$legendHydro++;$text_statut .= ' Active ';}
        if(($type_data_station == 11) && $active_station==0){$icon_type = 'iconHydroNonActive';$legendHydroNonActive++;$text_statut .= ' Historique ';}
        if(($type_data_station == 11) && $suivi_station==0){$icon_type = 'iconHydroPonctuel';$legendHydroPonctuel++;$text_statut .= ' Ponctuelle ';}
        if(($type_data_station == 11) && $armee_station==1){$icon_type = 'iconHydroDesarmee';$legendHydroDesarmee++;$text_statut .= ' <span style=\"color:#B80000;\">En panne</span> ';}
        
        if(($type_data_station == 5) && $active_station==1){$icon_type = 'iconPiezoActive';$legendPiezo++;$text_statut .= ' Active ';}
        if(($type_data_station == 5) && $active_station==0){$icon_type = 'iconPiezoNonActive';$legendPiezoNonActive++;$text_statut .= ' Historique ';}
        if(($type_data_station == 5) && $suivi_station==0){$icon_type = 'iconPiezoPonctuel';$legendPiezoPonctuel++;$text_statut .= ' Ponctuelle ';}
        if(($type_data_station == 5) && $armee_station==1){$icon_type = 'iconPiezoDesarmee';$legendPiesoDesarmee++;$text_statut .= ' <span style=\"color:#B80000;\">En panne</span> ';}

        // Box qui s'affiche au survol de la carte sur les markers
		$text_toolTip = 
                    '<div class=\"tooltip-map\">'. 
                        '<h2><span>'.$eq_type_array[$type_data_station]['nom_eq_type'].'</span></h2>'.
                        '<div class=\"tooltip-item\">'.
                            '<p><span>'.htmlaccent("Nom station : ").'</span>'.$nom_station.'</p>'.
                            '<p><span>'.htmlaccent("Code station : ").'</span>'.$code_station.'</p>'. 

                            '<p style=\"margin-top:10px;\"><span>'.htmlaccent("Commune : ").'</span>'.$nom_commune.'</p>'. 
                            '<p><span>'.htmlaccent("Région Hydrologique (BV) : ").'</span>'.$nom_regionhydro.'</p>';

							
                           
			if(tep_not_null($nom_riviere))
			{
				$text_toolTip .= '<p><span>'.htmlaccent("Rivière : ").'</span>'.$nom_riviere.'</p>';
			}

			$text_toolTip .= '<p style=\"margin-top:10px;\">'.
								'<span>'.htmlaccent("Long. : ").'</span>'.round($convertedCoords[0],3).
								'<span style=\"margin-left:5px;\">'.htmlaccent("Lat. : ").'</span>'.round($convertedCoords[1],3).	
							'</p>'; 
			
			$text_toolTip .= '<p style=\"margin-top:10px;\"><span>'.htmlaccent("Statut : ").'</span>'.$text_statut.'</p>';                            
			
			if(tep_not_null($date_installation))
			{
				$text_toolTip .= '<p style=\"margin-top:10px;\"><span>'.htmlaccent("Date installation : ").'</span>'.$date_installation.'</p>';
			} 

			if(tep_not_null($last_date_ra_formatted))
			{
				$text_toolTip .= '<p style=\"margin-top:10px;\"><span>'.htmlaccent("Date du dernier passage : ").'</span>'.$last_date_ra_formatted.'</p>';
				$text_toolTip .= '<p><span>'.htmlaccent("Délais depuis le dernier passage : ").'</span>'.$text_delais_last_ra.'</p>';
			} 
        
        $text_toolTip .= '  </div>'.
                        '<hr></div>';   


        // Popup plus complet qui s'affiche quand on clique sur un marker
        $text_popup = 
                    '<div class=\"tooltip-map\" >'. 
                        '<h2><span>'.$eq_type_array[$type_data_station]['nom_eq_type'].'</span></h2>'.
                        
						'<div class=\"tooltip-item\">'.

							'<p><span>'.htmlaccent("Nom station : ").'</span>'.$nom_station.'</p>'.
							'<p><span>'.htmlaccent("Code station : ").'</span>'.$code_station.'</p>'. 
							'<p style=\"margin-top:10px;\"><span>'.htmlaccent("Commune : ").'</span>'.$nom_commune.'</p>'. 
							'<p><span>'.htmlaccent("Région Hydrologique (BV) : ").'</span>'.$nom_regionhydro.'</p>';
					   
				if(tep_not_null($nom_riviere))
				{
					$text_popup .= '<p><span>'.htmlaccent("Rivière : ").'</span>'.$nom_riviere.'</p>';
				}

				$text_popup .= '<p style=\"margin-top:10px;\">'.
								'<span>'.htmlaccent("Long. : ").'</span>'.round($convertedCoords[0],3).
								'<span style=\"margin-left:5px;\">'.htmlaccent("Lat. : ").'</span>'.round($convertedCoords[1],3).	
							'</p>'; 
				
				$text_popup .= '<p style=\"margin-top:10px;\"><span>'.htmlaccent("Statut : ").'</span>'.$text_statut.'</p>';                            
				
				if(tep_not_null($date_installation))
				{
					$text_popup .= '<p style=\"margin-top:10px;\"><span>'.htmlaccent("Date installation : ").'</span>'.$date_installation.'</p>';
				} 

				if(tep_not_null($last_date_ra_formatted))
				{
					$text_popup .= '<p style=\"margin-top:10px;\"><span>'.htmlaccent("Date du dernier passage : ").'</span>'.$last_date_ra_formatted.'</p>';
					$text_popup .= '<p><span>'.htmlaccent("Délais depuis le dernier passage : ").'</span>'.$text_delais_last_ra.'</p>';
				} 
		
		$text_popup .= '<div class=\"tooltip-ligne\"></div>';     

		$text_popup .= '<p><a href=\"modif_station.php?ref='.$id_station.'\" target=\"_blank\">'.htmlaccent(">> Fiche station").'</a></p>';
		$text_popup .= '<p><a href=\"data_chron.php?id_st='.$id_station.'\" target=\"_blank\">'.htmlaccent(">> Données de la station").'</a></p>';
        if($id_ra_last > 0)
        {   
            $text_popup .= '<p><a href=\"list_ra.php?search_st='.$code_station.'\" target=\"_blank\">'.htmlaccent(">> Derniers Rapports d\'Activité").'</a></p>';  
        }  
        

        $text_popup .= '  <hr></div>'.
                        '</div>';    


        // Définition du marker
        $var_marker = "        
                        var marker".$id_station." = L.marker([convertedCoords[1], convertedCoords[0]],{icon: createCustomIcon(".$icon_type.", [18, 18])})
                                                    .bindTooltip(\"". $text_toolTip."\")
                                                    .bindPopup(\"". $text_popup."\", { minWidth: 400 })
                                                    .addTo(mymap);

						// On insère dans un tableau contenant la liste des markers		
						marker".$id_station.".iconUrl = ".$icon_type.";			
						markers.push(marker".$id_station.");
                        ";	
  

		$var_carte_coord_all .= $var_carte_coord.$var_marker;
  

		$var_carte_coord_all .= $var_carte_coord.$var_marker;
	}
                                   
}



$responseData = array(
    'js_map' => $var_carte_coord_all
);


// Encodage du tableau associatif en JSON
$jsonResponse = json_encode($responseData);

// Envoi des données coté Client
echo $jsonResponse;

?>