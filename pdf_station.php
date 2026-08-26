<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Page pour générer une fiche station en PDF
*/

//---------------------------------------------------------------
// Appel du fichier contenant les infos de connexions et de configuration chargée à chaque page
require('include/application_top.php');


require 'vendor/autoload.php'; // Inclut la librairie mPDF

use Mpdf\Mpdf;


// Récupération de l'identifiant station 
$id_station = 0;

if(isset($_GET['st'])){$id_station = mysqli_real_escape_string($sql_link,trim(addslashes($_GET['st'])));$modif=true;}


// Variables générales
$today = date('d-m-Y H:i:s');; // Crée un objet DateTime pour la date actuelle


// Récupération des données et mise en forme

//---------------------------------------------------------------
// TABLE SQL - TABLE pour récupérer les infos générales 
//---------------------------------------------------------------
// TABLE SQL - Recupération DATA

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


// TABLE REGION / TERRITOIRE : Province pour NC - Iles pour PF et WF
$sql_region = "SELECT DISTINCT id_region, nom_region 
				FROM ".TABLE_REGION." 
				WHERE id_territoire=".$territoire_id;
$region_query = tep_db_query($sql_link,$sql_region);
while ($region = tep_db_fetch_array($region_query))
{
	$region_array[$region['id_region']] = htmlaccent(html_entity_decode($region['nom_region'] ?? ''));
}

// TABLE COMMUNE
$sql_commune = "SELECT DISTINCT c.id_commune, c.nom_commune 
				FROM ".TABLE_COMMUNE." c
				JOIN ".TABLE_REGION." r ON c.id_region=r.id_region
				WHERE r.id_territoire=".$territoire_id."
				ORDER BY c.nom_commune ASC";

$commune_query = tep_db_query($sql_link,$sql_commune);
while ($commune = tep_db_fetch_array($commune_query))
{
	$commune_array[$commune['id_commune']] = htmlaccent(html_entity_decode($commune['nom_commune'] ?? ''));
}

// TABLE TOURNEE
/*
$sql_tournee = "SELECT DISTINCT t.id, t.nom, t.id_territoire 
				FROM ".TABLE_TOURNEE." t
				WHERE t.id_territoire=".$territoire_id." 
				ORDER BY nom ASC";
$tournee_query = tep_db_query($sql_link,$sql_tournee);
while ($tournee = tep_db_fetch_array($tournee_query))
{
	$tournee_array[$tournee['id']] = htmlaccent(html_entity_decode($tournee['nom'] ?? ''));
}
*/

// TABLE REGION HYDROLOGIQUE
$sql_regionhydro = "SELECT DISTINCT rh.id, rh.nom, rh.id_territoire FROM ".TABLE_REGIONHYDRO." rh
					WHERE rh.id_territoire=".$territoire_id." 
					ORDER BY nom ASC";
$regionhydro_query = tep_db_query($sql_link,$sql_regionhydro);
while ($regionhydro = tep_db_fetch_array($regionhydro_query))
{
	$regionhydro_array[$regionhydro['id']] = htmlaccent(html_entity_decode($regionhydro['nom'] ?? ''));
}



// TABLE SQL - Recupération DATA STATION
	
// requête sql pour récupérer les données de la station
$sql_station = "SELECT DISTINCT s.id_station, s.id_station_old, s.nom_station, s.nom_court, s.code_station, s.num_irh, 
								s.id_region, s.id_commune, s.site_station, s.id_regionhydro, s.vallee_station, s.riviere_station, 
								s.altitude_station, s.orientation_station, s.longitude_station, s.latitude_station, 
								s.utm_station_x, s.utm_station_y, s.ign_station_x, s.ign_station_y, s.lamb_station_x, 
								s.lamb_station_y, s.source_info, s.station_type, s.transmission_station, 
								s.active_station, s.suivi, s.armee,
								s.id_tournee, s.id_regionhydro,
								s.date_installation_station, s.date_fermeture_station, s.description_station
				FROM ".TABLE_STATION." s
				WHERE s.id_station=".$id_station;

$station_query = tep_db_query($sql_link,$sql_station);
$station = tep_db_fetch_array($station_query);

$id_eq_type = 0;
$id_regionhydro = 0;
$id_region = 0;
$id_commune = 0;
$id_tournee = 0;

if(isset($station))
{	
	$id_station_old = $station['id_station_old'];

	$nom_station =  htmlaccent(html_entity_decode($station['nom_station'] ?? ''));	
	$nom_court =  htmlaccent(html_entity_decode($station['nom_court'] ?? ''));
	$code_station =  html_entity_decode($station['code_station'] ?? '');	
	$num_irh = html_entity_decode($station['num_irh'] ?? '');	
	
	$id_region =  $station['id_region']; // Ile pour PF - Province pour NC
	$text_region = '-';
	if(isset($region_array[$id_region])){$text_region = $region_array[$id_region];}
	
	$id_commune =  $station['id_commune'];	
	$text_commune = '-';
	if(isset($region_array[$id_commune])){$text_commune = $commune_array[$id_commune];}

	$site_station =  htmlaccent(html_entity_decode($station['site_station'] ?? ''));	
	
	$id_eq_type =  $station['station_type']; // Type de données	
	$text_eq_type = '-';
	$type_color_border = '';
	
	if(isset($eq_type_array[$id_eq_type]))
	{
		$text_eq_type = $eq_type_array[$id_eq_type]['nom_eq_type'];
		$type_color_border =  $eq_type_array[$id_eq_type]['type_color_border'];
	}
		
		
	

	//$vallee_station =  htmlaccent(html_entity_decode($station['vallee_station'] ?? ''));	
	$riviere_station =  htmlaccent(html_entity_decode($station['riviere_station'] ?? '-'));	

	$id_regionhydro = $station['id_regionhydro'];
	$text_regionhydro = '-';
	if(isset($regionhydro_array[$id_regionhydro])){$text_regionhydro = $regionhydro_array[$id_regionhydro];}

	/*
	$id_tournee = $station['id_tournee'];
	$text_tournee = '-';
	if(isset($tournee_array[$id_tournee])){$text_tournee = $tournee_array[$id_tournee];}
	*/
	
	$altitude_station = '-';
	if(tep_not_null($station['altitude_station'])){$altitude_station =  str_replace(',', '.', $station['altitude_station']);}	
	if(is_numeric($altitude_station)){$altitude_station =  number_format(floatval($altitude_station),3);}
	
	// -------------------
	// Coordonnées GPS
	$longitude_station = '-';
	if(tep_not_null($station['longitude_station'])){$longitude_station =  str_replace(',', '.', $station['longitude_station']);}	
	$longitude_station = str_replace(["Ã", "", "Â"], "", $longitude_station);

	$latitude_station =  '-';
	if(tep_not_null($station['latitude_station'])){$latitude_station =  str_replace(',', '.', $station['latitude_station']);}
	$latitude_station = str_replace(["Ã", "", "Â"], "", $latitude_station);
	
	$utm_station_x = '-';
	if(tep_not_null($station['utm_station_x'])){$longutm_station_xitude_station =  str_replace(',', '.', $station['utm_station_x']);}	
	
    $utm_station_y =  '-';
	if(tep_not_null($station['utm_station_y'])){$utm_station_y =  str_replace(',', '.', $station['utm_station_y']);}	
	
	$ign_station_x = '-';
	if(tep_not_null($station['ign_station_x'])){$ign_station_x =  str_replace(',', '.', $station['ign_station_x']);}		
	if(is_numeric($ign_station_x)){$ign_station_x =  number_format(floatval($ign_station_x),3);}

	$ign_station_y =  '-';
	if(tep_not_null($station['ign_station_y'])){$ign_station_y =  str_replace(',', '.', $station['ign_station_y']);}	
	if(is_numeric($ign_station_y)){$ign_station_y =  number_format(floatval($ign_station_y),3);}

	$lamb_station_x = '-';
	if(tep_not_null($station['lamb_station_x'])){$lamb_station_x =  str_replace(',', '.', $station['lamb_station_x']);}	

	$lamb_station_y =  '-';
	if(tep_not_null($station['lamb_station_y'])){$lamb_station_y =  str_replace(',', '.', $station['lamb_station_y']);}	
	
	// -------------------



	$source_info = $station['source_info']; // GPS ... à mettre dans formulaire un peu plus tard
	
	// Active / Suivie / Armee

	$active_station = $station['active_station'];
    $text_active_station = 'Historique (fermée)';
    if($active_station == 1){$text_active_station = 'Active';}

	$suivi_station = $station['suivi'];
    $text_suivi_station = 'Mesures ponctuelles';
    if($suivi_station == 1){$text_suivi_station = 'Mesures continues';}

	$armee_station = $station['armee'];
    $text_armee_station = 'En fonctionnement';
    if($armee_station == 1){$text_armee_station = 'En panne';}
	
    
	$transmission_station = $station['transmission_station']; // Je ne sais plus à quoi cela correspond


	// Dates
	$date_installation_station =  "-";			
	if($station['date_installation_station']!='0000-00-00')
	{$date_installation_station =  dateus_fr($station['date_installation_station']);}
	
	$date_fermeture_station =  "-";			
	if($station['date_fermeture_station']!='0000-00-00')
	{$date_fermeture_station =  dateus_fr($station['date_fermeture_station']);}
	
	$description_station =  '-';
	if(tep_not_null($station['description_station'])){$description_station =  str_replace(',', '.', $station['description_station']);}
}	


$sql_ra = "SELECT DISTINCT ra.id_ra, ra.id_agent_user, 
							ra.date_heure_ra, ra.etat_ra,
							ra.ra_obs, ra.ra_futur, ra.pre_marquant, ra.fait_marquant, 
							ra.agents_complement
		   FROM ".TABLE_DATA_RA." ra 
		   WHERE id_station=".$id_station."
		   ORDER BY date_heure_ra DESC
		   LIMIT 10";
$ra_query = tep_db_query($sql_link,$sql_ra);
while($ra_tab = tep_db_fetch_array($ra_query))
{
	$tab_date_heure_ra =  explode(" ",$ra_tab['date_heure_ra']);
	$date_ra =  dateus_fr($tab_date_heure_ra[0]);	
	
	$ra_array[$ra_tab['id_ra']] = array('id_agent' => $ra_tab['id_agent_user'],
										'date_ra' => $date_ra, 
										'etat_ra' => $ra_tab['etat_ra'],
										'ra_obs' => $ra_tab['ra_obs'],
										'ra_futur' => $ra_tab['ra_futur'],
										'agents_complement' => $ra_tab['agents_complement'],
										); 
}


// Création du PDF

try {
    // Initialisation de mPDF
    $mpdf = new Mpdf([
                        'margin_left' => 10, // Marge gauche en mm
                        'margin_right' => 10, // Marge droite en mm
                        'margin_top' => 10, // Marge haut en mm
                        'margin_bottom' => 10, // Marge bas en mm
                    ]);

    // Charger le CSS depuis un fichier
    $stylesheet = file_get_contents('css/pdf_css.css');

    // Contenu HTML pour le PDF
    
    $html = "

        <img src='".DIR_WS_IMG_PDF."bando.png' style='200%;'>

        <h1>
			Fiche station
			<span style='color:".$type_color_border.";'>".$text_eq_type."</span>
		</h1>

        <div id='bloc' style='margin-top:0px;'>
            
            <p>
                <span>Edité le</span> :  ".$today."
            </p>
            <p>
                <span>par</span> : ".$prenom_user." ".$nom_user." - ".$info_user."
            </p>

        </div>


        <div id='bloc' style='font-size:20px;margin-top:20px;'>
            
            <p style='width:400px;'>
                <span>Nom station</span>
				<br>
				".$nom_station."
            </p>
            <p>
                <span>Code station</span>
				<br>
				".$code_station."
            </p>

        </div>

		<div id='bloc' >
            
            <p style='width:250px;'>
                <span>Nom abrégé</span> : ".$nom_court."
            </p>
            <p style='width:300px;'>
                <span>Num IRH</span> : ".$num_irh."
            </p>

		</div>
		<div id='bloc' style='margin-top:30px;'>	
            
            <p style='width:100px;'>
                <span>Statut</span> : ".$text_active_station."
            </p>
            <p style='width:160px;'>
                <span>Suivi</span> : ".$text_suivi_station."
            </p>
            <p style='width:200px;'>
                <span>Equipement</span> : ".$text_armee_station."
            </p>

        </div>

		<h2>
			Situation Géographique
		</h2>

		<div id='bloc' style='width:40%;margin-top:20px;'>	
            
			<p>
                <span>Territoire</span> : ".$territoire_nom."
            </p>
            <p>
                <span>".$territoire_region."</span> : ".$text_region."
            </p>
            <p>
                <span>Commune</span> : ".$text_commune."
            </p>
            <p>
                <span>Site</span> : ".$site_station."
            </p>

        </div>

		<div id='bloc' style='width:50%;margin-top:0px;'>	
            
            <p>
                <span>Région hydrologique / BV</span> : ".$text_regionhydro."
            </p>
            <p>
                <span>Rivière</span> : ".$riviere_station."
            </p>		
            <p>
                <span>Atlitude (en m)</span> : ".$altitude_station."
            </p>

        </div>
		

		<div id='bloc' >	
            
            <table>
				<tr>
                    <td colspan='2' class='entete'>Coordonnées géographiques</td>
                </tr>
				<tr>
                    <td style='width:150px;'>Longitude</td>
					<td style='width:120px;padding-left:10px;'>".$longitude_station."</td>
                </tr>
				<tr>
                    <td style='width:150px;'>Latitude</td>
					<td style='width:120px;padding-left:10px;'>".$latitude_station."</td>
                </tr>
				<tr>
                    <td style='width:150px;'>UTM - X (WGS 84)</td>
					<td style='width:120px;padding-left:10px;'>".$utm_station_x."</td>
                </tr>
				<tr>
                    <td style='width:150px;'>UTM - Y (WGS 84)</td>
					<td style='width:120px;padding-left:10px;'>".$utm_station_y."</td>
                </tr>
				<tr>
                    <td style='width:150px;'>Lambert - X (RGNC 91)</td>
					<td style='width:120px;padding-left:10px;'>".$lamb_station_x."</td>
                </tr>
				<tr>
                    <td style='width:150px;'>Lambert - Y (RGNC 91)</td>
					<td style='width:120px;padding-left:10px;'>".$lamb_station_y."</td>
                </tr>
			</table>
				
        </div>

		<h2>
			Informations
		</h2>

		<div id='bloc' style='margin-top:20px;'>	
            
			<p>
                <span>Date d'installation</span> : ".$date_installation_station."
            </p>
            <p>
                <span>Date de démontage</span> : ".$date_fermeture_station."
        </div>

		<div id='bloc' style='margin-top:20px;'>	
            <p>
                <span>Description</span> 
            </p>
			".$description_station."
        </div>


		<pagebreak /> <!-- Saut de page -->
		";
		
		if(isset($ra_array))
		{
			$html .= "
				<h2>
					Derniers passages (Rapports d'Activité - Fiches terrain)
				</h2>

				<div id='bloc' style='margin-top:20px;'>	
					
					<table>
						<tr>
							<td style='width:50px;' class='entete'>Date</td>
							<td style='width:350px;' class='entete'>Observation</td>
							<td style='width:350px;' class='entete'>A faire</td>
							<td style='width:150px;' class='entete'>Agents présents</td>
						</tr>
						";

						
							foreach($ra_array as $key => $value)
							{
								$html .= " 
								
								<tr>
									<td>".$value['date_ra']."</td>
									<td>".$value['ra_obs']."</td>
									<td>".$value['ra_futur']."</td>
									<td>".$value['agents_complement']."</td>
								</tr>
								";
							}
				$html .= "			
					</table>
						
				</div>";
		}

    // Appliquer le CSS
    $mpdf->WriteHTML($stylesheet, \Mpdf\HTMLParserMode::HEADER_CSS);

    // Écriture du contenu dans le PDF
    $mpdf->WriteHTML($html);

    // Sortie directe dans le navigateur
    $mpdf->Output('mon_pdf.pdf', 'I'); // 'I' pour affichage dans le navigateur
} catch (\Mpdf\MpdfException $e) {
    echo "Erreur lors de la création du PDF : " . $e->getMessage();
}
?>
