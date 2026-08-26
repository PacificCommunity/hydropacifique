<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Page permettant de lister les stations en fonction de la date du dernier passage
- Tri par délais depuis le dernier passage
- Sélection des stations devant être revues
- Edition PDF du fiche tournée d'urgence
----------------------------------------
*/

require('include/application_top.php');

$row = 0;
$today = new DateTime(); // Crée un objet DateTime pour la date actuelle


$tournee_delai_encours = 0;
$having_delai = '';


//---------------------------------------------------------------
// Récupération des champs de formulaires pour sélection


// SELECT POUR LE TRI
// TRI (Nom station, Code Station, Commune, Type_DATA)
$tri_encours = 1;
$tri = "date_lastRa"; // tri par défault est lié à la date du dernier passage
if(isset($_POST['select_tri']))
{
	$tri_encours = $_POST['select_tri'];
	if($_POST['select_tri'] == 1){$tri = "date_heure_ra";} // tri nom station
    if($_POST['select_tri'] == 2){$tri = "s.nom_station";} // tri nom station
	if($_POST['select_tri'] == 3){$tri = "s.code_station";} // tri code station
	if($_POST['select_tri'] == 4){$tri = "s.station_type";} // tri type data (Pluie, Hydro, Piezo)
}

$tri_order_encours = 2;
$tri_order = " DESC";
if(isset($_POST['order_tri']))
{
	$tri_order_encours = $_POST['order_tri'];
	if($_POST['order_tri'] == 1){$tri_order = " ASC";} // tri croissant
	if($_POST['order_tri'] == 2){$tri_order = " DESC";} // tri décroissant
}


// Tournées Délai - Délai de passage à une station
if(isset($_POST['select_tournee_delai']) && $_POST['select_tournee_delai']!=0)
{
	$tournee_delai_encours = $_POST['select_tournee_delai'];
    // cette ligne permet de générer une partie de la requête sql, pour ne considérer que les stations dont la date du dernier passage (RA) est inférieure à 'select_tournee_delai'
	$having_delai = " HAVING DATEDIFF(NOW(), date_lastRa) > ".$tournee_delai_encours;
}


// Initialisation des var. pour les filtres les plus commun
$affiche_select_from = true;
$affiche_select_type = true;
$affiche_select_tournee = false;
$affiche_search = true;
$affiche_select_riviere = false;
$affiche_select_station = true;
$affiche_select_statut_station = true;
require(DIR_WS_FILTRE . 'filtre_stations_var.php');




//---------------------------------------------------------------
// TABLE SQL - Recupération DATA


// TABLE DELAI
$sql_tournee_periode = "SELECT DISTINCT id, periode, nb_days
				FROM ".TABLE_TOURNEE_PERIODE." 
				ORDER BY nb_days DESC";
$tournee_periode_query = tep_db_query($sql_link,$sql_tournee_periode);	
while ($tournee_periode = tep_db_fetch_array($tournee_periode_query))
{
	$tournee_periode_array[$tournee_periode['id']] = array('periode' => htmlaccent(html_entity_decode($tournee_periode['periode'] ?? '')),					
															'nb_days' => html_entity_decode($tournee_periode['nb_days'] ?? '')
															);
}





//---------------------------------------------------------------
// TABLE SQL - Recupération de la listes des Stations pour affichage

$station_array = [];
$nb_station = 0;
$nb_station_active = 0;
$nb_station_suivi = 0;
$nb_station_armee = 0;

$sql_station = "SELECT s.id_station, s.id_station_old, s.id_territoire, s.id_region, s.id_commune,
                        s.nom_station, s.code_station, s.vallee_station,
                        s.date_installation_station, s.date_fermeture_station, s.active_station,
                        s.suivi, s.armee, s.station_type, s.id_regionhydro,
                        r.date_heure_ra AS date_lastRa,
                        r.id_ra, r.etat_ra, r.ra_obs, r.ra_futur
                FROM ".TABLE_STATION." s
                LEFT JOIN ".TABLE_DATA_RA." r
                        ON r.id_ra = (
                            SELECT ra2.id_ra
                            FROM ".TABLE_DATA_RA." ra2
                            WHERE ra2.id_station = s.id_station
                            ORDER BY ra2.date_heure_ra DESC, ra2.id_ra DESC
                            LIMIT 1
                        )
				WHERE s.id_territoire=".$territoire_id . $where_and_from .
                $where_search.$where_and_regionhydro.$where_and_region.$where_and_commune.
                $where_and_riviere.$where_and_type.$where_and_tournee_2.$where_and_station.                
                $having_delai."                
				ORDER BY ".$tri.$tri_order;


$station_query = tep_db_query($sql_link,$sql_station);
while ($station = tep_db_fetch_array($station_query))
{	
	$id_station =  $station['id_station'];
	$station_type =  $station['station_type'];	
    $nom_eq_type =  $eq_type_array[$station_type]['nom_eq_type'] ;	
	$type_color_border =  $eq_type_array[$station_type]['type_color_border'];
	$nom_station =  htmlaccent(html_entity_decode($station['nom_station'] ?? ''));
	$code_station =  htmlaccent(html_entity_decode($station['code_station'] ?? ''));

	$date_installation_station =  dateus_fr($station['date_installation_station']);
	$date_fermeture_station =  dateus_fr($station['date_fermeture_station']);

    $id_regionhydro =  $station['id_regionhydro'];
    
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
			
	// --- Fin intervention

    // Date du dernier RA 
    $last_datetime_ra = '';
    $last_datetime_ra_formatted = '';
    $delais_ra = 0;
    $text_delai_ra = '';
    $ra_id =  0;
    $ra_etat =  0;
    $ra_obs =  '';
    $ra_futur = ''; 
    
    if($station['date_lastRa'] !== null)
    {
        // Crée un objet DateTime pour la dernière date valide
        $last_datetime_ra = new DateTime($station['date_lastRa']);

        // Formatte la date au format "d-m-Y"
        $last_datetime_ra_formatted = $last_datetime_ra->format('d-m-Y h:i:s');

        // Calcule la différence en jour entre latest_date_heure_ra et aujourd'hui
        $delais_ra = $today->diff($last_datetime_ra)->days;
        
        // Parcourir le tableau des délais
        foreach($tournee_periode_array as $key => $value) 
        {
            // Vérifier si le nombre de jours se trouve dans la plage de ce délai
            if ($delais_ra > $value['nb_days']) 
            {
                $text_delai_ra = htmlaccent('plus de '.$value['periode']);
                break; // Sortir de la boucle dès qu'on trouve la première correspondance
            }
        }

        // Affiche le résultat formaté
        /*
        if ($delais_ra->y > 0) {$text_delai_ra .= $delais_ra->y . " année(s) ";}
        if ($delais_ra->m > 0) {$text_delai_ra .= $delais_ra->m . " mois ";}
        if ($delais_ra->d > 0) {$text_delai_ra .= $delais_ra->d . " jour(s)";}
        */

        $ra_id =  html_entity_decode($station['id_ra'] ?? '');
        $ra_etat =  html_entity_decode($station['etat_ra'] ?? '');
        $ra_obs =  htmlaccent(html_entity_decode($station['ra_obs'] ?? ''));
        $ra_futur =  htmlaccent(html_entity_decode($station['ra_futur'] ?? ''));
    }

   
    //---
    // Tableau station avec toutes les données	
	$station_array[$id_station] = array('id_old' => $station['id_station_old'],
                                        'nom_eq_type' => $nom_eq_type,
                                        'type_color_border' => $type_color_border,
                                        'active_station' => $active_station,							 
                                        'suivi_station' => $suivi_station,                             						 
                                        'armee_station' => $armee_station,
                                        'nom_station' => $nom_station,
                                        'code_station' => $code_station,
                                        'id_regionhydro' => $id_regionhydro,                             
                                        'date_installation_station' => $date_installation_station,
                                        'date_fermeture_station' => $date_fermeture_station,
                                        'lastRA' => $last_datetime_ra_formatted,                                                                     
                                        'lastRA_days' => $delais_ra,                         
                                        'lastRA_text' => htmlaccent($text_delai_ra),    
                                        'ra_etat' => $ra_etat,                    
                                        'ra_id' => $ra_id,                                    
                                        'ra_obs' => htmlaccent($ra_obs),                        
                                        'ra_futur' => htmlaccent($ra_futur)
                                        );
                                                
	
}
$nb_station = sizeof($station_array);	


//---------------------------------------------------------------
// Edition HTML


require(DIR_WS_STRUCTURE . 'header_web.php');

echo "<body>";

require(DIR_WS_STRUCTURE . 'header.php'); // Bando Haut
include(DIR_WS_BOX . 'nav_accueil.php'); // Menu

echo "<div id='contour_general'>";	
	
	echo "<div id='contenu_centre'>";
		
		echo "<div id='contenu_box2'>";
		
			echo "<h1>";
				
				echo "<span>".htmlaccent('Suivi des actions de terrain par station')."</span>";

                //echo button_pdf(htmlaccent('Editer une prochaine tournée'),'pdf_tournee.php');

			echo "</h1>";
            
            // ----------------------------------------------------------------------------------------
            // FORMULAIRE DE SELECTION - Cadre en-tête de la page
            // Ce bloc contient les champs formulaire en liste qui permettent de sélectionner les RA en fonction de différents critères
    
                // Balise indiquant le début du formulaire
                $lien_form = tep_href_link('suivi_terrain.php');
                $name_form = 'form_gestionTournee';			
                echo "<form name='".$name_form."' action='".$lien_form."' method='post' enctype='multipart/form-data'>";			
			
                    echo "<div id='cadre_graph' style='float:left;width:272px;max-height:80vh;overflow-y: auto;'>\n"; 
						
                        echo "<div id='boxpopup' class='select-top' style='width:235px;padding:5px 3%;margin-bottom:10px;'>\n";
                    
                            // Délais depuis le dernier passage
                            echo "<p style='float:left;width:80px;margin-top:5px;color: #609966;'>".htmlaccent('Délai depuis le dernier passage')."</p>";

                            echo "<select name='select_tournee_delai' id='select_tournee_delai' onchange='".$name_form.".submit();' style='float:right;width:130px;margin-top:5px;'>";
                                    
                                echo "<option value='0'>* Tous</option>";
                                
                                $selected = '';			
                                                                
                                if(isset($tournee_periode_array))
                                {
                                    foreach($tournee_periode_array as $key => $value)
                                    {   
                                        if($value['nb_days'] == $tournee_delai_encours){$selected="selected";}	
                                        else{$selected = '';}											
                                        echo "<option value='".$value['nb_days']."' ".$selected.">".htmlaccent('plus de ').$value['periode']."</option>";
                                    }
                                }
                            echo "</select>";

                            echo "<hr>\n";

                            echo "<div style='width:100%;border-bottom:2px solid #176B87;margin-top:5px;'></div>";

                            echo "<hr>\n";

                            require(DIR_WS_FILTRE . 'filtre_stations_html.php');
                            
                            echo "<hr>";

                            // TRI DE LA TABLE
                            echo "<div style='width:100%;border-bottom:2px solid #176B87;margin-top:5px;'></div>";

                            echo "<p style='float:left;padding-top:5px;color:#186F65;margin-top:15px;'>".htmlaccent('TRIER PAR')."</p>";

                            echo "<select name='select_tri' id='select_tri' onchange='".$name_form.".submit();' style='float:right;width:130px;margin-top:15px;'>";
                                
                                $selected = ($tri_encours == 1) ? "selected" : "";
                                echo "<option value='1' ".$selected.">".htmlaccent('Date du dernier passage')."</option>";
                                $selected = ($tri_encours == 2) ? "selected" : "";
                                echo "<option value='2' ".$selected.">".htmlaccent('Nom de la station')."</option>";
                                $selected = ($tri_encours == 3) ? "selected" : "";
                                echo "<option value='3' ".$selected.">".htmlaccent('Code de la station')."</option>";
                                $selected = ($tri_encours == 4) ? "selected" : "";
                                echo "<option value='4' ".$selected.">".htmlaccent('Type de données')."</option>";
                                    
                            echo "</select>";

                            echo "<hr>";

                            echo "<div style='float:right;'>";

                                // Déterminer la valeur de l'attribut "checked" en fonction de $tri_order_encours
                                $asc_checked = ($tri_order_encours == 1) ? "checked" : "";
                                $desc_checked = ($tri_order_encours == 2) ? "checked" : "";

                                echo "<p style='float:left;width:55px;padding-top:3px;'>".htmlaccent('Croissant')."</p>";
                                echo "<input type='radio' id='asc' name='order_tri' value='1' style='float:left;' ".$asc_checked." onchange='".$name_form.".submit();' >";

                                echo "<p style='float:left;width:65px;margin-left:10px;padding-top:3px;'>".htmlaccent('Décroissant')."</p>";
                                echo "<input type='radio' id='desc' name='order_tri' value='2' style='float:left;' ".$desc_checked." onchange='".$name_form.".submit();' >";

                            echo "</div>";
                        
                            
                            // Affichage nombre de stations ; nbre stations activse ; nbre stations suivies - Cadre jaune
                            echo "<div id='contenu_infos' style='width:97%;margin-top:10px;'>";
                                                    
                                echo "<p>";
                                    echo "<span style='margin:0px;'>".htmlaccent('Nombre de stations : ').number_format($nb_station,0,'.',' ')."</span>";
                                echo "</p>";

                            echo "</div>";
                                
                        echo "</div>";	
                    
                    echo "</div>";
                    
                echo "</form>"; // Fin du formulaire
                
                
                // Affichage Tableau
                if(isset($station_array) && ($nb_station>0))
                {
                    echo "<div class='table-container' style='float:none;width:auto;height:80vh;'>";

                        echo "<div style='width:95%;height:78vh;overflow-y: auto;'>";

                            echo "<table id='table_tri' cellspacing='0' >";
                        
                                echo "<thead>";
                                    echo "<tr class='header-row'>";
                                        
                                        /*
                                        echo "<th style='width:70px;border-bottom: 1px solid #cc0000;'>";
                                            echo  "<span class='selectAll' style='cursor:pointer;border:0;' >"; //onclick='toggleCheckboxes(0,".$id_typedata.",0);'>";
                                                echo htmlaccent('Select +/-');
                                            echo "</span>";
                                        echo "</th>"; 
                                        */
                                        echo "<th style='text-align:center;width:60px;'>".htmlaccent('Lien RA')."</th>";
                                        echo "<th style='text-align:center;width:40px;'>".htmlaccent('Validé')."</th>";
                                        echo "<th style='width:100px;padding-left:10px;'>".htmlaccent('Type data')."</th>";
                                        echo "<th style='width:100px;'>".htmlaccent('Code station')."</th>";						
                                        echo "<th style='width:150px;'>".htmlaccent('Nom station')."</th>";                            
                                        echo "<th style='width:80px;' title='".htmlaccent('Délai depuis le dernier passage')."'>".htmlaccent('Délais (en j)')."</th>";
                                        echo "<th style='width:140px;' title='".htmlaccent('Date du dernier passage')."'>".htmlaccent('Date passage')."</th>";
                                        echo "<th style='width:250px;' >".htmlaccent('Observation')."</th>";                            
                                        echo "<th style='width:250px;padding-left:20px;' >".htmlaccent('A faire')."</th>";
                                        
                                    echo "</tr>";
                                echo "</thead>";
                        
                                //ligne vide dans le tableau		
                                    
                                echo "<tr>";
                                    echo "<td colspan='11' style='height:15px;'>&nbsp;</td>";
                                echo "</tr>";	
                                    
                                foreach($station_array as $key => $value)
                                {	
                                    if(fmod($row,2)==0){$row_l="class='row1' onmouseover=\"this.className='row1hover';\" onmouseout=\"this.className='row1';\" ";} 
                                    else{$row_l="class='row2' onmouseover=\"this.className='row2hover';\" onmouseout=\"this.className='row2';\" ";} 
                                    
                                    $color_type = '';
                                    if(tep_not_null($value['type_color_border'])){$color_type = 'color:'.$value['type_color_border'].';';}
                                    
                                    echo "<tr ".$row_l." style='height:20px;'>";

                                        /*
                                        echo "<td style='text-align:center;width:70px;'>";
                                            echo "<input type='checkbox' name='check_chron[]' value='check_".$key."' style='width: 15px;height: 15px;'>\n";
                                        echo "</td>\n"; 
                                        */
                                        $lien_ra = 'list_ra.php?search_st='.$value['code_station'];
                                        echo "<td style='text-align:center;width:60px;'>";
                                            echo "<a href='".$lien_ra."' target='blank_' title='".htmlaccent('Voir RA')."'>"; 
                                                echo "<img src='".DIR_WS_IMG_ICO."edit.png' style='width:20px;cursor:pointer;'>";							
                                            echo "</a>";
                                        echo "</td>\n";
                                    
                                        // Etat du Ra (Validé ou non)
                                        if($value['ra_etat'] == 1)
                                        {
                                            echo "<td style='width:40px;text-align:center;'><img src='".DIR_WS_IMG_ICO."puce_verte.png' style='width:12px;' title='".htmlaccent('Validé')."'></td>\n";
                                        }
                                        else
                                        {
                                            echo "<td style='width:40px;text-align:center;'><img src='".DIR_WS_IMG_ICO."puce_rouge.png' style='width:12px;' title='".htmlaccent('Non validé')."'></td>\n";
                                        }
                                        
                                        
                                        echo "<td style='padding-left:10px;".$color_type."' >".$value['nom_eq_type']."</td>\n"; 							
                                        echo "<td>".$value['code_station']."</td>\n";								
                                        echo "<td title='".$value['nom_station']."'>".affichelettres($value['nom_station'],20)."</td>\n";							
                                        echo "<td style='padding-left:20px;'>".$value['lastRA_days']."</td>\n";					
                                        echo "<td>".$value['lastRA']."</td>\n";
                                        echo "<td>".$value['ra_obs']."</td>\n";                            
                                        echo "<td style='padding-left:20px;'>".$value['ra_futur']."</td>\n";
                                        
                                        //echo "<td style='width:250px;'>".$value['lastData']." - ".$value['lastData_text']."</td>\n";							
                                        //echo "<td class='t_cont_m' style='text-align:right;'>" . $value['date_intervention'] . "</td>\n";
                                        
                                    echo "</tr>\n";

                                    $row++;
                                }
                                
                            echo "</table>";

                        echo "</div>";	                            
                    
                    echo "</div>";
                }
                else
                {
                    echo "<div id='boxpopup' style='margin-left: 1%;'>\n";
                        echo "<p class='alert'>".htmlaccent('Aucune - Station - n\'a été trouvée')."</p>";
                    echo "</div>";
                }
		
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
