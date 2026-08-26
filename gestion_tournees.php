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
$msg_info = '';


// Initialisation des var. pour les filtres les plus commun
$affiche_select_from = true;
$affiche_select_type = true;
$affiche_select_tournee = false;
$affiche_search = true;
$affiche_select_riviere = false;
$affiche_select_station = false;
$affiche_select_statut_station = true;
require(DIR_WS_FILTRE . 'filtre_stations_var.php');




//---------------------------------------------------------------
// TABLE SQL - Recupération DATA

// Requête sur TABLE_TOURNEE
$first_tournee = true;
$select_tournee_encours=0;

$sql_tournee_maj = "SELECT DISTINCT id, nom, description FROM ".TABLE_TOURNEE." WHERE id_territoire=".$territoire_id." ORDER BY LOWER(nom) ASC";
$tournee_maj_query = tep_db_query($sql_link,$sql_tournee_maj);
while ($tournee_maj = tep_db_fetch_array($tournee_maj_query))
{		
    if($first_tournee){$select_tournee_encours=$tournee_maj['id'];}
    
    $nom =  htmlaccent(html_entity_decode($tournee_maj['nom'] ?? ''));	
	$description =  htmlaccent(html_entity_decode($tournee_maj['description'] ?? ''));

	$tournee_maj_array[$tournee_maj['id']] = array('nom' => $nom,
                                                'description' => $description);
    
    $first_tournee=false;
} 



//---------------------------------------------------------------
// Enregistrement des données de la station
if(isset($_POST['valid_tournee'])){require(DIR_WS_FORMULAIRE . 'ctrl_gestion_tournees.php');}




// SELECT STATION de la liste de la tournée en cours de modificaiton bloc de gauche
if(isset($_POST['select_tournee_maj']) && $_POST['select_tournee_maj']>0){$select_tournee_encours = $_POST['select_tournee_maj'];}

$nb_stations_tournee = 0;

$sql_station_tournee = "SELECT DISTINCT s.id_station, s.nom_station, s.code_station, s.active_station, s.station_type, st.id_tournee
                FROM ".TABLE_STATION." s
                JOIN ".TABLE_STATION_TO_TOURNEE." st ON st.id_station = s.id_station AND st.id_tournee = ".$select_tournee_encours."
                WHERE s.id_territoire=".$territoire_id." 
                ORDER BY s.nom_station";
$station_tournee_query = tep_db_query($sql_link,$sql_station_tournee);
while ($station_tournee = tep_db_fetch_array($station_tournee_query))
{	
    $nom_eq_type =  htmlaccent(html_entity_decode($station_tournee['nom_eq_type'] ?? ''));	
    $nom_station =  htmlaccent(html_entity_decode($station_tournee['nom_station'] ?? ''));
    $code_station =  htmlaccent(html_entity_decode($station_tournee['code_station'] ?? ''));

    $id_station_type = $station_tournee['station_type'];
    $nom_eq_type =  $eq_type_array[$id_station_type]['nom_eq_type'];
    $type_color_border =  $eq_type_array[$id_station_type]['type_color_border'];
    $type_color_background =  $eq_type_array[$id_station_type]['type_color_background'];
    
    $station_tournee_array[$station_tournee['id_station']] = array('nom_eq_type' => $nom_eq_type,
                                                                'type_color_border' => $type_color_border,
                                                                'type_color_background' => $type_color_background,
                                                                'active_station' => $station_tournee['active_station'],
                                                                'nom_station' => $nom_station,
                                                                'code_station' => $code_station);

    $nb_stations_tournee++;
}


// SELECT STATION de la liste complète bloc de droite
$nb_stations = 0;

$sql_station = "SELECT DISTINCT s.id_station, s.nom_station, s.code_station, s.active_station, s.station_type 
				FROM ".TABLE_STATION." s
                LEFT JOIN ".TABLE_STATION_TO_TOURNEE." t ON t.id_station = s.id_station	
				WHERE s.id_territoire=".$territoire_id . $where_and_from .
                $where_search.$where_and_regionhydro.$where_and_region.$where_and_commune.$where_and_riviere.$where_and_type.$where_and_tournee.
                $where_and_active.$where_and_suivi.$where_and_armee." 
				ORDER BY s.nom_station";
$station_query = tep_db_query($sql_link,$sql_station);
while ($station = tep_db_fetch_array($station_query))
{	
    if(!isset($station_tournee_array[$station['id_station']])) // si la station n'est pas déjà référencé dans la tournée
    {
        $nom_eq_type =  htmlaccent(html_entity_decode($station['nom_eq_type'] ?? ''));	
        $nom_station =  htmlaccent(html_entity_decode($station['nom_station'] ?? ''));
        $code_station =  htmlaccent(html_entity_decode($station['code_station'] ?? ''));

        $id_station_type = $station['station_type'];
        $nom_eq_type =  $eq_type_array[$id_station_type]['nom_eq_type'];
        $type_color_border =  $eq_type_array[$id_station_type]['type_color_border'];
        $type_color_background =  $eq_type_array[$id_station_type]['type_color_background'];
        
        $station_array[$station['id_station']] = array('nom_eq_type' => $nom_eq_type,
                                                    'type_color_border' => $type_color_border,
                                                    'type_color_background' => $type_color_background,
                                                    'active_station' => $station['active_station'],
                                                    'nom_station' => $nom_station,
                                                    'code_station' => $code_station);

        $nb_stations++;
    }
}






//---------------------------------------------------------------
// Edition HTML


require(DIR_WS_STRUCTURE . 'header_web.php');

echo "<body>";

require(DIR_WS_STRUCTURE . 'header.php'); // Bando Haut
include(DIR_WS_BOX . 'nav_accueil.php'); // Menu

echo "<div id='contour_general'>";	
	
    if(tep_not_null($msg_info)){echo "<div id='contenu_info' >".$msg_info."</div>";}
    else{echo "<div id='contenu_info' style='display:none;'></div>";}
	
	echo "<div id='contenu_centre'>";
		
		echo "<div id='contenu_box2'>";
		
			echo "<h1>";
				
				echo "<span>".htmlaccent('Gestion des tournées')."</span>";

			echo "</h1>";

            
            
            // ----------------------------------------------------------------------------------------
            // FORMULAIRE DE SELECTION - Cadre en-tête de la page
            // Ce bloc contient les champs formulaire en liste qui permettent de sélectionner les RA en fonction de différents critères
    
            // Balise indiquant le début du formulaire
            $lien_form = tep_href_link('gestion_tournees.php');
            $name_form = 'form_gestionTournee';			
            echo "<form name='".$name_form."' action='".$lien_form."' method='post' enctype='multipart/form-data'>";			
        
                echo "<div id='cadre_graph' style='float:left;width:280px;max-height:80vh;overflow-y: auto;'>\n"; 
                    
                    echo "<div id='boxpopup' style='width:235px;padding:10px 10px;margin-bottom:10px;'>\n";
                                    
                        echo "<div id='boite_small' style='width:100%;'>\n";
                                        
                            echo "<p style='float:left;width:100%;font-size:14px;color: #930000;'>";
                                echo htmlaccent('Tournée à mettre à jour');
                            echo "</p>";

                            echo "<select name='select_tournee_maj' id='select_tournee_maj' style='float:left;width:180px;' onchange='".$name_form.".submit();'>";
                                            
                                foreach ($tournee_maj_array as $key => $value)
                                {                            
                                    $selected = '';
                                    if($select_tournee_encours == $key){$selected = 'Selected';}

                                    echo "<option value='".$key."' ".$selected.">".$value['nom']."</option>";
                                }
                                
                            echo "</select>";		
                                
                        echo "</div>";

                    echo "</div>";
                
                    echo "<div id='boxpopup' style='width:235px;padding:5px 10px;padding-top:10px;'>\n";
                
                        require(DIR_WS_FILTRE . 'filtre_stations_html.php');
                    
                    echo "<hr>\n";
                    echo "</div>";	
                
                echo "</div>";
                
            echo "</form>"; // Fin du formulaire
                
            $lien_form = tep_href_link('gestion_tournees.php');
            $name_form = 'form_tournee';			
            echo "<form name='".$name_form."' id='".$name_form."' action='".$lien_form."' method='post' enctype='multipart/form-data' onsubmit='validateForm(event)'>";	

                echo "<input type='hidden' id='select_tournee_maj' name='select_tournee_maj' value='".$select_tournee_encours."' />\n";

                echo "<div style='float:none;width:auto;height:45vh;'>";

                    echo "<div id='cadre_graph' style='float:left;width:55%;height:78vh;margin-left:15px;'>\n";
                        
                        // Select Stations
                        echo "<div id='boxpopup' class='select' style='margin-top: 0px;'>\n";
                            
                            echo "<div style='float:left;width:45%;'>\n";

                                echo "<h2 id='selected_refcount' style='float:left;width:100%;'>";
                                    echo htmlaccent('Nombre de stations à sélectionner : ').$nb_stations.htmlaccent(' stations');
                                echo "</h2>";
                                
                                // Première liste déroulante avec les stations à sélectionner
                                echo "<select name='select_station_ref[]' id='select_station_ref' multiple='multiple' style='width:100%;height:60vh;'>";
                                            
                                    if(isset($station_array))
                                    {
                                        foreach($station_array as $key => $value)
                                        {
                                            echo "<option value='".$key."' style='background-color:".$value['type_color_background'].";'>".$value['nom_eq_type']." - ".$value['code_station']." - ".$value['nom_station']."</option>";
                                        }
                                    }
                                                                            
                                echo "</select>";

                            echo "</div>\n";


                            // Boutons pour déplacer les éléments
                                echo "<div style='float:left;width:4%;margin:0 2%;margin-top:20vh;'>\n";
                                // echo "<button type='button' class='multiselect' onclick=\"moveItems('select_station_ref', 'target_station_ref')\" >&gt;</button>";   
                                    echo "<img src='".DIR_WS_IMG_ICO."arrow_next.png' style='width:30px;cursor:pointer;' title='".htmlaccent('Sélectionner les Stations')."'
                                            onclick=\"moveItems('select_station_ref', 'target_station_ref')\"
                                            onmouseover=\"this.src='".DIR_WS_IMG_ICO."arrow_next_over.png';\" onmouseout=\"this.src='".DIR_WS_IMG_ICO."arrow_next.png';\" >"; 
                                    
                                    
                                    echo "<img src='".DIR_WS_IMG_ICO."arrow_previous.png' style='width:30px;cursor:pointer;' title='".htmlaccent('Supprimer la sélection')."'
                                            onclick=\"moveItems('target_station_ref', 'select_station_ref')\" 
                                            onmouseover=\"this.src='".DIR_WS_IMG_ICO."arrow_previous_over.png';\" onmouseout=\"this.src='".DIR_WS_IMG_ICO."arrow_previous.png';\" >";
                                    //echo "<button type='button' class='multiselect' onclick=\"moveItems('target_station_ref', 'select_station_ref')\">&lt;</button>";
                                echo "</div>";

                            echo "<div style='float:right;width:45%;'>\n";

                                echo "<h2 id='selected_targetcount' style='float:left;width:100%;'>";
                                    echo htmlaccent('Nombre de stations sélectionnées : ').$nb_stations_tournee.htmlaccent(' stations');
                                echo "</h2>";

                                // Deuxième liste déroulante après sélection multiple
                                echo "<select name='target_station_ref[]' id='target_station_ref' multiple='multiple' style='width:100%;height:60vh;'>";
                                    
                                    if(isset($station_tournee_array))
                                    {
                                        foreach($station_tournee_array as $key => $value)
                                        {
                                            echo "<option value='".$key."' style='background-color:".$value['type_color_background'].";'>".$value['nom_eq_type']." - ".$value['code_station']." - ".$value['nom_station']."</option>";
                                        }
                                    }

                                echo "</select>";
                            
                            echo "</div>\n";

                        echo "<hr>\n";
                        echo "</div>\n";

                    echo "<hr>\n";
                    echo "</div>\n";    
                
                    echo "<div id='cadre_graph' style='float:left;width:8%;height:60vh;margin-left:30px;'>\n";

                        echo "<div style='width:100%;padding:0 5%;'>\n";

                            echo "<input type='submit' class='button' name='valid_tournee' value='Valider' style='width:100%;margin:0;' />";	
                        
                        echo "</div>\n";
                    
                    echo "<hr>\n";
                    echo "</div>\n";
                  
                echo "</div>\n";
                        
                
            echo "</form >\n";
		
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


<script id="source" type="text/javascript">

	function moveItems(fromSelectId, toSelectId) 
    {
        var fromSelect = document.getElementById(fromSelectId);
        var toSelect = document.getElementById(toSelectId);

        // Récupérer les éléments sélectionnés
        var selectedOptions = Array.from(fromSelect.selectedOptions);

        // Les ajouter à l'autre liste
        selectedOptions.forEach(option => {
            fromSelect.removeChild(option); // Retirer de la liste d'origine
            toSelect.appendChild(option); // Ajouter à la liste de destination
        });

        // Mettre à jour le nombre d'options dans la liste cible
        updateSelectedCount();
    }

    // Mettre à jour le nombre d'options dans la liste 'target_station_ref'
    function updateSelectedCount() 
    {
        var refSelect = document.getElementById('select_station_ref');
        var targetSelect = document.getElementById('target_station_ref');
        var refCount = refSelect.options.length; // Nombre d'options
        var targetCount = targetSelect.options.length; // Nombre d'options
        var refCountDisplay = document.getElementById('selected_refcount');
        var targetCountDisplay = document.getElementById('selected_targetcount');

        refCountDisplay.innerText = `Nombre de stations à sélectionner : ${refCount} stations`;
        targetCountDisplay.innerText = `Nombre de stations sélectionnées : ${targetCount} stations`;
    }


    // Fonction pour vérifier s'il y a bien des stations sélectionner avant de soumettre le formulaire
    function validateForm(event) 
    {
        var targetSelect = document.getElementById('target_station_ref');
        var numSelected = targetSelect.options.length; // Nombre d'options        
        var msgInfo = document.getElementById('contenu_info');

        // Si aucune station n'est sélectionnée, empêcher la soumission du formulaire
        if (numSelected === 0) 
        {
            msgInfo.innerText = 'Vous devez sélectionner au moins une station';
            msgInfo.style.display = 'block';
            event.preventDefault(); // Empêcher la soumission du formulaire
            return false; // Retourner false pour stopper le processus de soumission
        }

        // Sélectionner toutes les options (nécessaire pour éviter des bugs de sélection)
        for (var i = 0; i < targetSelect.options.length; i++) {
            targetSelect.options[i].selected = true; // Marque l'option comme sélectionnée
        }

        return true;
    }

</script>