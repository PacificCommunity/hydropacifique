<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Page d'accueil de la plateforme
----------------------------------------
*/

// Appel du fichier de configuration - Sécurité - Identique pour chaque Page
require('include/application_top.php');

$today = new DateTime(); // Crée un objet DateTime pour la date actuelle

// Initialisation des variables conditions pour les tris

$check_active_station = 1;
$where_and_active = " AND s.active_station=1";

$check_suivi_station = 1;
$where_and_suivi = " AND s.suivi=1";

$check_armee_station = 0;
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


//---------------------------------------------------------------
// Récupération des champs de formulaires pour sélection
// Récupération des données TABLES SQL

// Initialisation des var. pour les filtres les plus commun
$affiche_select_from = true;
$affiche_select_type = true;
$affiche_select_tournee = true;
$affiche_search = false;
$affiche_select_station = false;
$affiche_select_statut_station = true;
require(DIR_WS_FILTRE . 'filtre_stations_var.php');



//---------------------------------------------------------------
// TABLE SQL - Recupération DATA
// Chargement des données de la TABLE CHRONIQUE

// TABLE CHRONIQUE (QI, QIE, PI, PIE, ...)
$sql_chron = "SELECT DISTINCT id_data_type, init_type_data, nom_type_data, id_eq_type_data, axe_data, unite 
				FROM ".TABLE_TYPE_DATA;
$chron_query = tep_db_query($sql_link,$sql_chron);	
while ($chron_tab = tep_db_fetch_array($chron_query))
{
	$chron_array[$chron_tab['id_data_type']] = array('init_type_data' => html_entity_decode($chron_tab['init_type_data'] ?? $default_string),
                                                    'nom_type_data' => htmlaccent(html_entity_decode($chron_tab['nom_type_data'] ?? $default_string)),							
                                                    'id_eq_type_data' => html_entity_decode($chron_tab['init_type_data'] ?? $default_string),					
                                                    'axe_data' => html_entity_decode($chron_tab['axe_data'] ?? $default_string),					
                                                    'unite' => html_entity_decode($chron_tab['unite'] ?? $default_string)
                                                    );
}



//-------------------------------
// Récupération de la liste des stations à afficher sur la carte
$station = [];
$nb_station = 0;
$nb_station_active = 0;
$nb_station_suivi = 0;
$nb_station_armee = 0;

// Initialisation des variables nécessaires à la construction de la carte
$var_carte_coord_all = ''; // var texte pour définir les coordonnées des stations
$var_marker_all = ''; // var texte pour définir les markers des stations
$var_marker_group_all ='' ; // var texte pour définir lier un markers à un group de markers.

$legendPlu=0;$legendPluNonActive=0;$legendPluDesarmee=0;$legendPluPonctuel=0;
$legendHydro=0;$legendHydroNonActive=0;$legendHydroDesarmee=0;$legendHydroPonctuel=0;
$legendPiezo=0;$legendPiezoNonActive=0;$legendPiesoDesarmee=0;$legendPiezoPonctuel=0;


// TABLE STATION avec les conditions des différents champs de sélection 
$sql_station = "SELECT DISTINCT s.id_station, s.nom_station, s.code_station, s.vallee_station, s.id_commune, s.riviere_station,
                                s. active_station, s.station_type, s.suivi, s.armee,
                                s.date_installation_station, 
                                s.lamb_station_x, s.lamb_station_y, s.utm_station_x, s.utm_station_y, 
                                s.latitude_station, s.longitude_station 
				FROM ".TABLE_STATION." s
                LEFT JOIN ".TABLE_STATION_TO_TOURNEE." st ON st.id_station = s.id_station
				WHERE s.id_territoire=".$territoire_id.
                        $where_and_regionhydro.$where_and_region.$where_and_commune.$where_and_riviere.$where_and_type.$where_and_tournee.
                        $where_and_active.$where_and_suivi.$where_and_armee." 
				ORDER BY code_station DESC";
$station_query = tep_db_query($sql_link,$sql_station);
while ($station = tep_db_fetch_array($station_query))
{	
	$nb_station++;	
    
    $id_station = $station['id_station'];
    
    $nom_station = htmlaccent(html_entity_decode($station['nom_station'] ?? $default_string));
    $code_station = html_entity_decode($station['code_station'] ?? $default_string);    
    
    $nom_regionhydro = htmlaccent(html_entity_decode($station['vallee_station'] ?? $default_string));    
    /* Pour le moment vallée (ou regionhydro) est un champs de texte mais il va être modifier en int avec id_regionhydro et relié à la table Geo_regionhydro
    $id_regionhydro = html_entity_decode($station['id_commune'] ?? $default_string);
    $nom_regionhydro = $regionhydro_array[$id_regionhydro];
    */

    $id_commune = html_entity_decode($station['id_commune'] ?? $default_string);
    $nom_commune = '';
    if(tep_not_null($id_commune) && isset($commune_array[$id_commune])){$nom_commune = $commune_array[$id_commune];}
    
    $nom_riviere = htmlaccent(html_entity_decode($station['riviere_station'] ?? $default_string));
    /* Pour le moment riviere_station est un champs de texte mais il va être modifier en int avec id_rivière et relié à la table Geo_rivietre qui n'existe pas encore
    $id_riviere = html_entity_decode($station['id_riviere'] ?? $default_string);
    $nom_riviere = $riviere_array[$id_riviere];
    */

    $type_data_station = $station['station_type'];  
    
    $active_station = 0;
	if($station['active_station'] == 1)
	{
		$active_station = 1;
		$nb_station_active++;
	}

	$suivi_station = 0;
	if($station['suivi'] == 1)
	{
		$suivi_station = 1;
		$nb_station_suivi++;
	}
	
	$armee_station = 0;
	if($station['armee'] == 1)
	{
		$armee_station = 1;
		$nb_station_armee++;
	}

    $date_installation = dateus_fr($station['date_installation_station']); 

    $latitude = str_replace(",", ".",$station['latitude_station']);  
    $longitude = str_replace(",", ".",$station['longitude_station']);  
    $lamb_station_x = str_replace(",", ".",$station['lamb_station_x']);
    $lamb_station_y = str_replace(",", ".",$station['lamb_station_y']);  
    $utm_station_x = str_replace(",", ".",$station['utm_station_x']);  
    $utm_station_y = str_replace(",", ".",$station['utm_station_y']);  

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

        // Délai en nombre de jours
        $text_delais_last_ra = $delais_last_ra->days . " jour(s) ";

        // Affiche le résultat formaté
        /*
        if ($delais_last_ra->y > 0) {$text_delais_last_ra .= $delais_last_ra->y . " année(s) ";}
        if ($delais_last_ra->m > 0) {$text_delais_last_ra .= $delais_last_ra->m . " mois ";}
        if ($delais_last_ra->d > 0) {$text_delais_last_ra .= $delais_last_ra->d . " jour(s) ";}
        */
    }

    

    // ---------------------------------------
    // Récupération des derniers données élaborés donc valides
    // Il faut mieux automatiser ça et surtout bien évaluer les valeurs que le veut voir apparaitre
/*
    $valeur_annee = 0;$annee = 0;
    $valeur_mois = 0;$mois = 0;

    // Test dm.id_typedata = 6 => Chronique QAE
    $sql_stats_an = "SELECT YEAR(da.dateheure) as annee, da.valeur, dm.id_station, dm.id, dm.id_typedata
                    FROM ".TABLE_DATA_ALL." da
                    JOIN ".TABLE_DATA_META." dm ON da.id_meta=dm.id
                    WHERE dm.id_typedata = 6
                    AND dm.id_station = ".$id_station."
                    AND da.valeur > 0
                    ORDER BY da.dateheure DESC
                    LIMIT 1";

    $stats_an_query = tep_db_query($sql_link,$sql_stats_an);
    $stats_an_tab = tep_db_fetch_array($stats_an_query);

    if(isset($stats_an_tab['valeur']) && ($stats_an_tab['valeur']>0))
    {
        $annee = $stats_an_tab['annee'];
        $valeur_annee = $stats_an_tab['valeur'];
        
        // Vérifier si la valeur contient une virgule
        if (strpos($valeur_annee, '.') !== false) 
        {
            // Si oui, formater avec 3 chiffres après la virgule
            $valeur_annee = number_format((float)$valeur_annee, 3, '.', '');
        }
    }
    
    // Test dm.id_typedata = 6 => Chronique QAE
    $sql_stats_mois = "SELECT YEAR(da.dateheure) as annee, MONTH(da.dateheure) as mois, da.valeur, dm.id_station, dm.id, dm.id_typedata
                    FROM ".TABLE_DATA_ALL." da
                    JOIN ".TABLE_DATA_META." dm ON da.id_meta=dm.id
                    WHERE dm.id_typedata = 10
                    AND dm.id_station = ".$id_station."
                    AND da.valeur > 0
                    ORDER BY da.dateheure DESC
                    LIMIT 1";

    $stats_mois_query = tep_db_query($sql_link,$sql_stats_mois);
    $stats_mois_tab = tep_db_fetch_array($stats_mois_query);

    if(isset($stats_mois_tab['annee']))
    {
        $annee = $stats_mois_tab['annee'];

        $mois = $stats_mois_tab['mois'];
        $nom_mois = convert_name_mois_fr($mois); // Fonction à retrouver dans include/function/date.php
        
        $valeur_mois = $stats_mois_tab['valeur'];

        // Vérifier si la valeur contient une virgule
        if (strpos($valeur_mois, '.') !== false) 
        {
            // Si oui, formater avec 3 chiffres après la virgule
            $valeur_mois = number_format((float)$valeur_mois, 3, '.', '');
        }
    }
*/
    
    // ---------------------------------------

    // On va générer le code JS qui sera compiler en bas de la page

    // Récupération des coordonnées des stations
    // Model de projection Lamb pour la NC - On le convertit ensuite dans le code d'édition de la carte dynamique
    $var_carte_coord = "";

    // Configuration des projections pour chaque territoire
    $projection_wgs84 = 'EPSG:4326'; // WGS84 (latitude et longitude)

    $fromProjectionNC = 'EPSG:29891'; // Lambert Nouvelle-Calédonie
    $fromProjectionPF = 'EPSG:2975'; // Hypothétique Lambert pour la Polynésie française (à ajuster si nécessaire)
    $fromProjectionWF = 'EPSG:4687'; // Hypothèse pour Wallis-et-Futuna (à ajuster avec la bonne projection si différente)

    if ($territoire_init == 'PF')  // Polynésie française
    {
        if (tep_not_null($latitude) && tep_not_null($longitude)) 
        {
            $latitude_js = str_replace("'", "\'", $latitude);
            $longitude_js = str_replace("'", "\'", $longitude);
    
            $var_carte_coord = "
                                var coords_latitude_dms = '" . $latitude_js . "'; 
                                var coords_longitude_dms = '" . $longitude_js . "';
                        
                                var coords_latitude = dmsToDecimal(coords_latitude_dms, 'S');
                                var coords_longitude = dmsToDecimal(coords_longitude_dms, 'W');
                        
                                var convertedCoords = [coords_longitude, coords_latitude];
                                ";
        } 
        else 
        {
            if (tep_not_null($lamb_station_x) && tep_not_null($lamb_station_y)) 
            {
                $var_carte_coord = "
                                    var coords_lamb_X = " . $lamb_station_x . "; 
                                    var coords_lamb_Y = " . $lamb_station_y . ";
                        
                                    // Convertir les coordonnées Lambert en latitude et longitude pour PF
                                    var convertedCoords = proj4('$fromProjectionPF', '$projection_wgs84', [coords_lamb_X, coords_lamb_Y]);
                                    ";
            }
    
            // Utilisation de UTM si disponible (non implémenté pour l'instant)
            if (tep_not_null($utm_station_x) && tep_not_null($utm_station_y)) 
            {
                $var_carte_coord = "
                                        var coords_utm_X = " . $utm_station_x . ";
                                        var coords_utm_Y = " . $utm_station_y . ";
                            
                                        // Convertir les coordonnées UTM en latitude et longitude
                                        var convertedCoords = projUtmPF(fromProjection, toProjection, [coords_utm_X, coords_utm_Y]);
                                    ";
            }
        }
    }
    elseif ($territoire_init == 'WF') // Wallis-et-Futuna
    { 
        if (tep_not_null($latitude) && tep_not_null($longitude)) 
        {
            $latitude_js = str_replace("'", "\'", $latitude);
            $longitude_js = str_replace("'", "\'", $longitude);
    
            $var_carte_coord = "
                                var coords_latitude = " . $latitude_js . "; 
                                var coords_longitude = " . $longitude_js . ";
                        
                                var convertedCoords = [coords_latitude, coords_longitude];
                                ";
        } else 
        {
            if (tep_not_null($lamb_station_x) && tep_not_null($lamb_station_y)) {
                $var_carte_coord = "
                                    var coords_lamb_X = " . $lamb_station_x . "; 
                                    var coords_lamb_Y = " . $lamb_station_y . ";
                        
                                    // Convertir les coordonnées Lambert en latitude et longitude pour WF
                                    var convertedCoords = proj4('$fromProjectionWF', '$projection_wgs84', [coords_lamb_X, coords_lamb_Y]);
                                    ";
            }
    
            // Utilisation de UTM si disponible (non implémenté pour l'instant)
            if (tep_not_null($utm_station_x) && tep_not_null($utm_station_y)) 
            {
                $var_carte_coord = "
                                    var coords_utm_X = " . $utm_station_x . ";
                                    var coords_utm_Y = " . $utm_station_y . ";
                        
                                    // Convertir les coordonnées UTM en latitude et longitude pour WF
                                    var convertedCoords = projUtmWF(fromProjection, toProjection, [coords_utm_X, coords_utm_Y]);
                                    ";
            }
        }
    } 
    elseif ($territoire_init == 'NC') // Nouvelle-Calédonie
    { 
        if (tep_not_null($latitude) && tep_not_null($longitude)) 
        {
            $var_carte_coord = "
                                var coords_latitude = " . $latitude . "; 
                                var coords_longitude = " . $longitude . ";
                        
                                var convertedCoords = [coords_latitude, coords_longitude];
                                ";
        } 
        else 
        {
            if (tep_not_null($lamb_station_x) && tep_not_null($lamb_station_y)) 
            {
                $var_carte_coord = "
                                    var coords_lamb_X = " . $lamb_station_x . "; 
                                    var coords_lamb_Y = " . $lamb_station_y . ";
                        
                                    // Convertir les coordonnées Lambert en latitude et longitude pour NC
                                    var convertedCoords = proj4('$fromProjectionNC', '$projection_wgs84', [coords_lamb_X, coords_lamb_Y]);
                                    ";
            }
    
            // Utilisation de UTM si disponible (non implémenté pour l'instant)
            if (tep_not_null($utm_station_x) && tep_not_null($utm_station_y)) 
            {
                $var_carte_coord = "
                                    var coords_utm_X = " . $utm_station_x . ";
                                    var coords_utm_Y = " . $utm_station_y . ";
                        
                                    // Convertir les coordonnées UTM en latitude et longitude pour NC
                                    var convertedCoords = projUtmNC(fromProjection, toProjection, [coords_utm_X, coords_utm_Y]);
                                    ";
            }
        }
    } 

    /*
    if(tep_not_null($latitude) && tep_not_null($longitude)) // Si on a pas les données de Lat. et Long. alors on va chercher les coord Lamb ou UTM si dispo
    {
       $var_carte_coord ="
        var coords_latitude = ".$latitude."; 
        var coords_longitude = ".$longitude.";

        var convertedCoords = [coords_latitude, coords_longitude];
        ";
    }
    else
    {
        if(tep_not_null($lamb_station_x) && tep_not_null($lamb_station_y))
        {
            $var_carte_coord ="
            var coords_lamb_X = ".$lamb_station_x."; 
            var coords_lamb_Y = ".$lamb_station_y.";

            // Convertir les coordonnées Lamb en latitude et longitude
	        var convertedCoords = proj4(fromProjection, toProjection, [coords_lamb_X, coords_lamb_Y]);

            ";
        }

        // Pour le moment pas mise en action
        if(tep_not_null($utm_station_x) && tep_not_null($utm_station_x))
        {
            $var_carte_coord ="
            var coords_utm_X = ".$utm_station_x.";
            var coords_utm_Y = ".$utm_station_y.";

            // Convertir les coordonnées UTM en latitude et longitude
	        var convertedCoords = projUtmPF(fromProjection, toProjection, [coords_utm_X, coords_utm_Y]);
            ";
        }
    }
    */
       

    $text_toolTip = '';
    $text_statut = '';
    $var_marker = '';
    $var_marker_group = '';
    $icon_type = '';

    if(tep_not_null($var_carte_coord))
    {             
        // On trouve le statut de la station

        if(($type_data_station == 1) && $active_station==1){$icon_type = 'iconPluActive';$legendPlu++;$text_statut .= ' Active ';}
        if(($type_data_station == 1) && $suivi_station==0){$icon_type = 'iconPluPonctuel';$legendPluPonctuel++;$text_statut .= ' Ponctuelle ';}
        if(($type_data_station == 1) && $armee_station==0){$icon_type = 'iconPluDesarmee';$legendPluDesarmee++;$text_statut .= ' <span style=\"color:#B80000;\">Désarmée</span> ';}
        if(($type_data_station == 1) && $active_station==0){$icon_type = 'iconPluNonActive';$legendPluNonActive++;$text_statut .= ' Inactive ';} // Dire historique plutôt ?

        if(($type_data_station == 11) && $active_station==1){$icon_type = 'iconHydroActive';$legendHydro++;$text_statut .= ' Active ';}
        if(($type_data_station == 11) && $suivi_station==0){$icon_type = 'iconHydroPonctuel';$legendHydroPonctuel++;$text_statut .= ' Ponctuelle ';}
        if(($type_data_station == 11) && $armee_station==0){$icon_type = 'iconHydroDesarmee';$legendHydroDesarmee++;$text_statut .= ' <span style=\"color:#B80000;\">Désarmée</span> ';}
        if(($type_data_station == 11) && $active_station==0){$icon_type = 'iconHydroNonActive';$legendHydroNonActive++;$text_statut .= ' Inactive ';}
        
        if(($type_data_station == 5) && $active_station==1){$icon_type = 'iconPiezoActive';$legendPiezo++;$text_statut .= ' Active ';}
        if(($type_data_station == 5) && $suivi_station==0){$icon_type = 'iconPiezoDesarmee';$legendPiezoPonctuel++;$text_statut .= ' Ponctuelle ';}
        if(($type_data_station == 5) && $armee_station==0){$icon_type = 'iconHydroDesarmee';$legendPiesoDesarmee++;$text_statut .= ' <span style=\"color:#B80000;\">Désarmée</span> ';}
        if(($type_data_station == 5) && $active_station==0){$icon_type = 'iconPiezoNonActive';$legendPiezoNonActive++;$text_statut .= ' Inactive ';}

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
        
        $text_toolTip .= '<p style=\"margin-top:10px;\"><span>'.htmlaccent("Statut : ").'</span>'.$text_statut.'</p>';                            
        
        if(tep_not_null($date_installation))
        {
            $text_toolTip .= '<p style=\"margin-top:10px;\"><span>'.htmlaccent("Date installation : ").'</span>'.$date_installation.'</p>';
        } 

        if(tep_not_null($last_date_ra_formatted))
        {
            $text_toolTip .= '<p style=\"margin-top:10px;\"><span>'.htmlaccent("Date du dernier passage : ").'</span>'.$last_date_ra_formatted.'</p>';
            $text_toolTip .= '<p><span>'.htmlaccent("Délai depuis le dernier passage : ").'</span>'.$text_delais_last_ra.'</p>';
        } 
        
        $text_toolTip .= '  </div>'.
                        '</div>';   


        // ------------------------------------------- 
        // Popup plus complet qui s'affiche quand on clique sur un marker
        $text_popup = 
                    '<div class=\"tooltip-map\" >'. 
                        '<h2><span>'.$eq_type_array[$type_data_station]['nom_eq_type'].'</span></h2>'.
                        
                        '<div class=\"tooltip-item\">'.
                            '<div style=\"float:left;width:55%;\">'.
                                '<p><span>'.htmlaccent("Nom station : ").'</span>'.$nom_station.'</p>'.
                                '<p><span>'.htmlaccent("Code station : ").'</span>'.$code_station.'</p>';

                                 
                                if(tep_not_null($last_date_ra_formatted))
                                {
                                    $text_popup .= '<p style=\"margin-top:10px;\"><span>'.htmlaccent("Date du dernier passage : ").'</span>'.$last_date_ra_formatted.'</p>';
                                    $text_popup .= '<p><span>'.htmlaccent("Délais depuis le dernier passage : ").'</span>'.$text_delais_last_ra.'</p>';
                                }

                            $text_popup .= '</div>';

                            $text_popup .= 
                            '<div style=\"float:left;width:45%;\">'.
                                '<p><span>'.htmlaccent("Commune : ").'</span>'.$nom_commune.'</p>'. 
                                '<p><span>'.htmlaccent("Région Hydrologique (BV) : ").'</span>'.$nom_regionhydro.'</p>';

                                if(tep_not_null($nom_riviere))
                                {
                                    $text_popup .= '<p><span>'.htmlaccent("Rivière : ").'</span>'.$nom_riviere.'</p>';
                                }
                                
                                $text_popup .= '<p style=\"margin-top:10px;\"><span>'.htmlaccent("Statut : ").'</span>'.$text_statut.'</p>';                            
                                
                                if(tep_not_null($date_installation))
                                {
                                    $text_popup .= '<p style=\"margin-top:10px;\"><span>'.htmlaccent("Date installation : ").'</span>'.$date_installation.'</p>';
                                }

                            $text_popup .= '</div>';

                        $text_popup .= '<hr></div>';


                        $text_popup .= '<div class=\"tooltip-ligne\"></div>';                            
                        
                        $text_popup .= '<div class=\"tooltip-item\">';

                            $text_popup .= '<div style=\"float:left;width:55%;\">';

                                // Edition des statistiques Annuelles (Voir /include/function/stats.php)
                                $type_chron_years = 6; // => Chronique QAE
                                //$id_eq_temp = $chron_array[$type_chron_years]['id_eq_type_data'];
                                //$nom_data_type = $eq_type_array[$type_data_station]['nom_eq_type'];
                                $text_popup .= '<p><span>'.
                                                        htmlaccent("Moyennes Annuelles ").
                                                        ' ['.$chron_array[$type_chron_years]['unite'].']'.
                                                '</span></p>';
                                $text_popup .= statsEditYear($sql_link,$id_station,$type_chron_years);

                            $text_popup .= '</div>';


                            $text_popup .= '<div style=\"float:left;width:45%;\">';

                                // Edition des statistiques Mensuelles (Voir /include/function/stats.php)
                                $type_chron_years = 10; // => Chronique QME
                                //$id_eq_temp = $chron_array[$type_chron_years]['id_eq_type_data'];
                                //$nom_data_type = $eq_type_array[$type_data_station]['nom_eq_type'];
                                $text_popup .= '<p><span>'.
                                                        htmlaccent("Moyennes Mensuelles ").
                                                        ' ['.$chron_array[$type_chron_years]['unite'].']'.
                                                '</span></p>';
                                $text_popup .= statsEditMonth($sql_link,$id_station,$type_chron_years);

                            $text_popup .= '</div>';
                        
                        $text_popup .= '<hr></div>';

                           
                        $text_popup .= '<div class=\"tooltip-ligne\"></div>';  

                        $text_popup .= '<div class=\"tooltip-item\">';

                                $text_popup .= '<p><a href=\"modif_station.php?ref='.$id_station.'\" target=\"_blank\">'.htmlaccent("Fiche station").'</a></p>';
                                if($id_ra_last > 0)
                                {   
                                    $text_popup .= '<p><a href=\"list_ra.php?id_ra='.$id_ra_last.'\" target=\"_blank\">'.htmlaccent("Dernier Rapport Activité").'</a></p>';  
                                }  
                            
                        $text_popup .= '<hr></div>'; 
                    
                    $text_popup .= '</div>'; 


        // Définition du marker
        $var_marker = "        
                        var marker".$id_station." = L.marker([convertedCoords[1], convertedCoords[0]],{icon: ".$icon_type."})
                                                    .bindTooltip(\"". $text_toolTip."\")
                                                    .bindPopup(\"". $text_popup."\", { minWidth: 600 })
                                                    .addTo(mymap);


                        ";	
    }
    
    $var_carte_coord_all .= $var_carte_coord.$var_marker;
    $var_marker_group_all .= $var_marker_group;                                           
}


// On prépare la légende pour la carte
$text_legend = 'var legendHTML = \'<div class="legend">' .
'<h4>Légende</h4>';
  
    if ($legendPlu > 0) {$text_legend .= '<div class="legend-item"><img src="' . DIR_WS_IMG_ICO . 'hydro/iconplu.png">' . htmlaccent('Station Pluviométrique Active') . '</div>';} 
    if ($legendPluNonActive > 0) {$text_legend .= '<div class="legend-item"><img src="' . DIR_WS_IMG_ICO . 'hydro/iconplunone.png">' . htmlaccent('Station Pluviométrique Inactive') . '</div>';} 
    if ($legendPluPonctuel > 0) {$text_legend .= '<div class="legend-item"><img src="' . DIR_WS_IMG_ICO . 'hydro/iconpluponctuel.png">' . htmlaccent('Station Pluviométrique Ponctuelle') . '</div>';} 
    if ($legendPluDesarmee > 0) {$text_legend .= '<div class="legend-item"><img src="' . DIR_WS_IMG_ICO . 'hydro/iconplualert.png">' . htmlaccent('Station Pluviométrique Désarmée') . '</div>';}    

    if ($legendHydro > 0) {$text_legend .= '<div class="legend-item"><img src="' . DIR_WS_IMG_ICO . 'hydro/iconhydro.png">' . htmlaccent('Station Hydrométrique Active') . '</div>';} 
    if ($legendHydroNonActive > 0) {$text_legend .= '<div class="legend-item"><img src="' . DIR_WS_IMG_ICO . 'hydro/iconhydronone.png">' . htmlaccent('Station Hydrométrique Inactive') . '</div>';} 
    if ($legendHydroPonctuel > 0) {$text_legend .= '<div class="legend-item"><img src="' . DIR_WS_IMG_ICO . 'hydro/iconhydroponctuel.png">' . htmlaccent('Station Hydrométrique Ponctuelle') . '</div>';} 
    if ($legendHydroDesarmee > 0) {$text_legend .= '<div class="legend-item"><img src="' . DIR_WS_IMG_ICO . 'hydro/iconhydroalert.png">' . htmlaccent('Station Hydrométrique Désarmée') . '</div>';}

    if ($legendPiezo > 0) {$text_legend .= '<div class="legend-item"><img src="' . DIR_WS_IMG_ICO . 'hydro/iconpiezo.png">' . htmlaccent('Station Piézométrique Active') . '</div>';} 
    if ($legendPiezoNonActive > 0) {$text_legend .= '<div class="legend-item"><img src="' . DIR_WS_IMG_ICO . 'hydro/iconpiezonone.png">' . htmlaccent('Station Piézométrique Inactive') . '</div>';} 
    if ($legendPiezoPonctuel > 0) {$text_legend .= '<div class="legend-item"><img src="' . DIR_WS_IMG_ICO . 'hydro/iconpiezoponctuel.png">' . htmlaccent('Station Piézométrique Ponctuelle') . '</div>';} 
    if ($legendPiesoDesarmee > 0) {$text_legend .= '<div class="legend-item"><img src="' . DIR_WS_IMG_ICO . 'hydro/iconpiezoalert.png">' . htmlaccent('Station Piézométrique Désarmée') . '</div>';}

$text_legend .= '</div>\';';

/* 
PAs sûr que l'on en ait besoin sur cette page 
// TABLE AGENT - Appel pour stocker l'info dans un tableau
$sql_agent = "SELECT DISTINCT id, nom, prenom 
                FROM ".TABLE_AGENT." 
                WHERE terrain=1 
				ORDER BY nom ASC";
$agent_query = tep_db_query($sql_link,$sql_agent);
while($agent = tep_db_fetch_array($agent_query))
{
	$nom_agent =  ucfirst(html_entity_decode($agent['nom'] ?? $default_string));
	$prenom_agent =  htmlaccent(html_entity_decode($agent['prenom'] ?? $default_string));

	$initial_agent = strtoupper(substr($prenom_agent, 0, 1));

	$agent_array[$agent['id']] = $initial_agent.".".$nom_agent;
}
*/

/*
// TABLE RA - 15 derniers enregistrements 
$sql_RA = "SELECT DISTINCT ra.id_ra, s.id_station, s.code_station, s.nom_station, s.id_commune, 
							ra.date_heure_ra, ra.id_eq_type, ra.etat_ra
			FROM ".TABLE_DATA_RA." ra
			JOIN ".TABLE_STATION." s ON ra.id_station=s.id_station
			AND s.id_territoire=".$territoire_id."
			ORDER BY ra.date_heure_ra DESC
			LIMIT 15";

$RA_query = tep_db_query($sql_link,$sql_RA);
while($RA_tab = tep_db_fetch_array($RA_query))
{
	$id_ra =  $RA_tab['id_ra'];

	$id_station =  $RA_tab['id_station'];
	$code_station =  nettoyer_et_echapper($RA_tab['code_station']);
	$nom_station =  nettoyer_et_echapper($RA_tab['nom_station']);
	$id_commune =  $RA_tab['id_commune'];
	
	// Date RA
	$tab_date_heure_ra =  explode(" ",$RA_tab['date_heure_ra']);
	$date_ra =  dateus_fr($tab_date_heure_ra[0]);
	$heure_ra =  $tab_date_heure_ra[1];
	$date_heure_ra =  $date_ra.' '.$heure_ra;
	
	// Equipement
	$id_eq_type_ra = $RA_tab['id_eq_type']; // Débit, pluie, piezo

	// Etat du ra
	$etat_ra = $RA_tab['etat_ra']; 


	// Agents
	$list_agents = '';

	$sql_agents_ra = "SELECT DISTINCT id_agent FROM ".TABLE_DATA_RA_TO_AGENT." WHERE id_ra=".$id_ra;
	$agents_ra_query = tep_db_query($sql_link,$sql_agents_ra);
	while($agents_ra = tep_db_fetch_array($agents_ra_query))
	{
		$list_agents .= $agent_array[$agents_ra['id_agent']].' , ';
	}
	$list_agents = rtrim($list_agents, ', ');
	



	$ra_array[$id_ra] = array('etat_ra' => $etat_ra,
							'date_heure_ra' => $date_heure_ra,
							'id_eq_type' => $id_eq_type_ra,
							'id_station' => $id_station,
							'code_station' => $code_station,
							'nom_station' => $nom_station,
							'id_commune' => $id_commune,
							'list_agents' => $list_agents
							);
}
*/




// ----------------------------------------
// Edition HTML

// En-Tête HTML
require(DIR_WS_STRUCTURE . 'header_web.php');


echo "<body>";

require(DIR_WS_STRUCTURE . 'header.php'); // Bando Haut
include(DIR_WS_BOX . 'nav_accueil.php'); // Menu

echo "<div id='contour_general'>";

    echo "<div id='contenu_info' style='display:none;'></div>";
		
    echo "<div id='contenu_centre'>";
		
        echo "<div id='contenu_box2'>";

            echo "<h1>";
                
                echo "<span>".htmlaccent('Cartographie')."</span>";

            echo "</h1>";	
                                
            $lien_form = tep_href_link('carto.php');	
            $name_form = 'form_carto_stations';					
            echo "<form name='".$name_form."' action='".$lien_form."' method='post' enctype='multipart/form-data'>";
                    
                echo "<div id='cadre_graph' style='float:left;width:17.5%;height:70vh;overflow-y: auto;'>\n"; 
                            
                    echo "<div id='boxpopup' class='select-top' style='width:92%;margin:0px;padding: 0 3%;'>\n";
                
                        require(DIR_WS_FILTRE . 'filtre_stations_html.php');

                        // Affichage nombre de stations ; nbre stations activse ; nbre stations suivies - Cadre jaune
                        echo "<div id='contenu_infos'>";
                                        
                            echo "<p>";
                                echo "<span style='margin:0px;'>".htmlaccent('Nombre de stations : ').number_format($nb_station,0,'.',' ')."</span>";
                                echo "<hr>";
                                echo "<span style='margin:0px;'>".htmlaccent('Nombre de station actives : ').number_format($nb_station_active,0,'.',' ')."</span>";		
                                echo "<hr>";
                                echo "<span style='margin:0px;'>".htmlaccent('Nombre de station avec mesure continu : ').number_format($nb_station_suivi,0,'.',' ')."</span>";
                                echo "<hr>";
                                echo "<span style='margin:0px;'>".htmlaccent('Nombre de station en panne : ').number_format($nb_station_armee,0,'.',' ')."</span>";
                            echo "</p>";

                        echo "</div>";

                        echo "<hr>\n";
                                                            
                    echo "<hr>\n";
                    echo "</div>\n";	                        
                
                echo "<hr>\n";
                echo "</div>\n";

            echo "</form>";


            // CADRE DE LA CARTE INTERACTIVE

            echo "<div id='cadre_graph' style='float:left;width:81%;height:78vh;margin-left:1%;'>\n";

                // Conteneur pour la carte 
                echo "<div id='map' style='width: 100%; height: 90%;'></div>";

            echo "<hr>\n";
            echo "</div>\n";
    
        echo "<hr>";
        echo "</div>";  		
			
    echo "<hr>";
    echo "</div>";	
	
echo "<hr>";
echo "</div>";

	require('include/application_bottom.php'); 

echo "</body>";

echo "</html>";


?>	




<script>

var territoire_init = '<?php echo $territoire_init; ?>';

// Script pour vérifier si le navigateur est connecté et afficher un message d'information le cas échéant

	// Permet de savoir si on le navigateur est onLine
	function updateConnectionStatus() 
	{
		if (!navigator.onLine) 
		{
            document.getElementById('contenu_info').style.display = 'block';
			document.getElementById('contenu_info').innerText = 'Vous n\'êtes pas connecté à Internet. \n Certaines fonctionnalités pourraient ne pas être disponibles. \n Les fonds de cartes ne pourront pas s\'afficher.';
        }
    }

    // Mettre à jour le statut de connexion au chargement de la page
    updateConnectionStatus();




// Script pour édition de la carte interactive

    // Function pour la création d'icone 
    function createCustomIcon(iconUrl) {
        return L.icon({
            iconUrl: iconUrl,
            iconSize: [15, 15],
            iconAnchor: [0, 0],
            popupAnchor: [8, -5],
            tooltipAnchor: [15, 10]
        });
    }

	// Initialiser la carte Leaflet centrée sur la Nouvelle-Calédonie
	
    //Variable de centrage
    var centerData = [];
    var zoomFirst = 8;
    var minZoomData = 4;

    // Nouvelle Calédonie
    if(territoire_init == 'NC') 
    {
        centerData = [-21.5, 165.5];
        zoomFirst = 8;
        minZoomData = 7;
    }
    
    // Polynésie française
    if(territoire_init == 'PF')
    {
        centerData = [-17.6509, -149.4260];
        zoomFirst = 10;
        minZoomData = 6;
    }

    // Wallis-et-Futuna
    if(territoire_init == 'WF') 
    {
        centerData = [-13.692110, -177.185692];
        zoomFirst = 9;
        minZoomData = 9;
    }
    
    var mymap = L.map('map', {
            center: centerData, // Corrdonées centrale
            zoom: zoomFirst, // Zoom par défaut
            minZoom: minZoomData // Limiter le zoom out à la vue initiale de la carte
        });

	// -----------------------------------
	// Fonds de cartes

    // Ajouter des couches de fond de carte
	var osmLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
		attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    	maxZoom: 18
	}).addTo(mymap);

	var cartoLayer = L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
		attribution: '&copy; <a href="https://www.carto.com/">Carto</a>',
    	maxZoom: 18
	}).addTo(mymap);

	var satelliteLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
		attribution: '&copy; <a href="https://www.arcgisonline.com/">ArcgisOnline</a>',
    	maxZoom: 18
    }).addTo(mymap);


    // -----------------------------------
	// Créer un groupe de clusters de marqueurs
    /*
	var markerGroup_StationActive = L.markerClusterGroup().addTo(mymap);
    var markerGroup_StationNonActive = L.markerClusterGroup().addTo(mymap);
    var markerGroup_StationDesarmee = L.markerClusterGroup().addTo(mymap);
    var markerGroup_StationPonctuel = L.markerClusterGroup().addTo(mymap); // Non suivies
    */

    //var markerClusterGroup2 = L.markerClusterGroup().addTo(mymap);

	// -----------------------------------
	// Configuration des Marks 
	
	// Définition des icônes personnalisées

    // Plu
    var iconPluActive = createCustomIcon('<?php echo DIR_WS_IMG_ICO."hydro/iconplu.png"; ?>');
    var iconPluNonActive = createCustomIcon('<?php echo DIR_WS_IMG_ICO."hydro/iconplunone.png"; ?>');
    var iconPluPonctuel = createCustomIcon('<?php echo DIR_WS_IMG_ICO."hydro/iconpluponctuel.png"; ?>');
    var iconPluDesarmee = createCustomIcon('<?php echo DIR_WS_IMG_ICO."hydro/iconplualert.png"; ?>');

    // Hydro
    var iconHydroActive = createCustomIcon('<?php echo DIR_WS_IMG_ICO."hydro/iconhydro.png"; ?>');
    var iconHydroNonActive = createCustomIcon('<?php echo DIR_WS_IMG_ICO."hydro/iconhydronone.png"; ?>');
    var iconHydroPonctuel = createCustomIcon('<?php echo DIR_WS_IMG_ICO."hydro/iconhydroponctuel.png"; ?>');
    var iconHydroDesarmee = createCustomIcon('<?php echo DIR_WS_IMG_ICO."hydro/iconhydroalert.png"; ?>');

    // Piezo
    var iconPiezoActive = createCustomIcon('<?php echo DIR_WS_IMG_ICO."hydro/iconpiezo.png"; ?>');
    var iconPiezoNonActive = createCustomIcon('<?php echo DIR_WS_IMG_ICO."hydro/iconpiezonone.png"; ?>');
    var iconPiezoPonctuel = createCustomIcon('<?php echo DIR_WS_IMG_ICO."hydro/iconpiezoponctuel.png"; ?>');
    var iconPiezoDesarmee = createCustomIcon('<?php echo DIR_WS_IMG_ICO."hydro/iconpiezoalert.png"; ?>');

	// -----------------------------------
	// Coordonnées géographiques et convertion 

	
    // Définir la projection de départ (Lambert 93) et d'arrivée (WGS84)
    // Conversion pour la NC
    proj4.defs("EPSG:29891", "+proj=lcc +lat_1=-20.66666666666667 +lat_2=-22.33333333333333 +lat_0=-21.5 +lon_0=166 +x_0=400000 +y_0=300000 +ellps=intl +towgs84=197.025,-193.922,175.185,0,0,0,0 +units=m +no_defs");
	var fromProjection = 'EPSG:29891'; // Lambert Nouvelle-Calédonie
	var toProjection = 'EPSG:4326';   // WGS84 (latitude et longitude)
    

    // Fonction pour convertir DMS en degrés décimaux (PF)
	function dmsToDecimal(dms, direction) 
	{
		var parts = dms.match(/(\d+)\D+(\d+)\D+(\d+)\D+/);
		var degrees = parseInt(parts[1], 10);
		var minutes = parseInt(parts[2], 10) / 60;
		var seconds = parseFloat(parts[3].replace(',', '.')) / 3600;

		var decimal = degrees + minutes + seconds;
		if (direction === "S" || direction === "W") {
			decimal = decimal * -1;
		}
		return decimal;
	}

    <?php 
        echo $var_carte_coord_all; // var texte pour définir les coordonnées des stations
        //echo $var_marker_all; // var texte pour définir les markers des stations
        echo $var_marker_group_all; // var texte pour définir lier un markers à un group de markers
    ?> 

	// Ajouter le groupe de clusters de marqueurs aux contrôles de basemap
    /*
	var overlayMaps = {
		"Stations Actives": markerGroup_StationActive,
        "Stations Désarmées": markerGroup_StationDesarmee,
        "Stations Ponctuelles": markerGroup_StationPonctuel,
        "Stations Non Actives": markerGroup_StationNonActive,
	};
    */

	// Ajouter les contrôles de basemap pour permettre à l'utilisateur de choisir
	var baseMaps = {
		"OpenStreetMap": osmLayer,
		"Carto": cartoLayer,
    	"Satellite": satelliteLayer
	};


	// -----------------------------------
    // Créez une légende HTML
    <?php echo $text_legend;?>

    // Créez un contrôle de légende personnalisé Leaflet
    var legendControl = L.Control.extend({
        onAdd: function(map) {
            var div = L.DomUtil.create('div', 'leaflet-control legend-control');
            div.innerHTML = legendHTML;
            return div;
        }
    });

    // Ajoutez la légende à la carte
    var legend = new legendControl({ position: 'bottomleft' });
    legend.addTo(mymap);




    // -----------------------------------
    // On génère la carte

	//L.control.layers(baseMaps, overlayMaps).addTo(mymap);
    L.control.layers(baseMaps).addTo(mymap);




</script>