<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Formulaire pour sélection les stations et les données que l'on veut éditer / observer - Etape 1 : Sélection Station, Période
----------------------------------------
- La colonne de gauche propose les filtres permettant de pré-sélectionner les stations
- Les stations sont à sélectionner dans la liste au centre
- La période de données est à préciser dans la colonne de droite
*/

// Initialisation des var. pour les filtres les plus commun
$affiche_select_from = true;
$affiche_select_type = true;
$affiche_select_tournee = true;
$affiche_search = true;
$affiche_select_riviere = false;
$affiche_select_station = true;
$affiche_select_statut_station = true;
require(DIR_WS_FILTRE . 'filtre_stations_var.php');

// Initialisation : tableau vide
$stations_selected_list = '';
$stations_selected = [];
$station_array = [];

$where_in = '';


if(isset($_POST['target_station_save']) && !empty($_POST['target_station_save'])) 
{
    $stations_selected_list = $_POST['target_station_save'];
    $stations_selected = explode(',', $_POST['target_station_save']);
    $where_in = " AND s.id_station IN (".$_POST['target_station_save'].") ";
}


// SELECT STATION
$nb_stations=0;
$sql_station = "SELECT DISTINCT s.id_station, s.nom_station, s.code_station, s.active_station, s.station_type 
				FROM ".TABLE_STATION." s
                LEFT JOIN ".TABLE_STATION_TO_TOURNEE." t ON t.id_station = s.id_station
				WHERE s.id_territoire=".$territoire_id.
                $where_search.$where_and_regionhydro.$where_and_region.$where_and_commune.$where_and_riviere.$where_and_type.$where_and_tournee.$where_and_station.
                $where_and_active.$where_and_suivi.$where_and_armee." 
				ORDER BY s.nom_station";
$station_query = tep_db_query($sql_link,$sql_station);
while ($station = tep_db_fetch_array($station_query))
{	
	$nom_eq_type =  htmlaccent(html_entity_decode($station['nom_eq_type'] ?? $default_string));	
	$nom_station =  htmlaccent(html_entity_decode($station['nom_station'] ?? $default_string));
	$code_station =  htmlaccent(html_entity_decode($station['code_station'] ?? $default_string));

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

// SELECT STATION TARGET
$nb_stations_target=0;
if(!empty($where_in))
{
    $sql_station_target = "SELECT DISTINCT s.id_station, s.nom_station, s.code_station, s.active_station, s.station_type 
                    FROM ".TABLE_STATION." s
                    WHERE s.id_territoire=".$territoire_id.
                    $where_in."
                    ORDER BY s.nom_station";
                  
    $station_target_query = tep_db_query($sql_link,$sql_station_target);
    while ($station_target = tep_db_fetch_array($station_target_query))
    {	
        $nom_eq_type =  htmlaccent(html_entity_decode($station_target['nom_eq_type'] ?? $default_string));	
        $nom_station =  htmlaccent(html_entity_decode($station_target['nom_station'] ?? $default_string));
        $code_station =  htmlaccent(html_entity_decode($station_target['code_station'] ?? $default_string));

        $id_station_type = $station_target['station_type'];
        $nom_eq_type =  $eq_type_array[$id_station_type]['nom_eq_type'];
        $type_color_border =  $eq_type_array[$id_station_type]['type_color_border'];
        $type_color_background =  $eq_type_array[$id_station_type]['type_color_background'];
        
        $station_target_array[$station_target['id_station']] = array('nom_eq_type' => $nom_eq_type,
                                                                    'type_color_border' => $type_color_border,
                                                                    'type_color_background' => $type_color_background,
                                                                    'active_station' => $station_target['active_station'],
                                                                    'nom_station' => $nom_station,
                                                                    'code_station' => $code_station);

        $nb_stations_target++;
    }
}


// --------------------------------------
// Debut page HTML

require(DIR_WS_STRUCTURE . 'header_web.php');


echo "<body>";

require(DIR_WS_STRUCTURE . 'header.php'); // Bando Haut
include(DIR_WS_BOX . 'nav_accueil.php'); // Menu

echo "<div id='contour_general'>";
	
	echo "<div id='contenu_info' style='display:none;'></div>";
	
	echo "<div id='contenu_centre'>";

        echo "<div id='contenu_box2'>";
		
            echo "<h1>";
                            
                echo "<span>".htmlaccent('Accès aux données - Etape 1 : Sélection des stations')."</span>";
                
            echo "</h1>";
                            
            $lien_form = tep_href_link('data_chron.php');
            $name_form = 'form_filtre';			
            echo "<form name='".$name_form."' id='".$name_form."' action='".$lien_form."' method='post' enctype='multipart/form-data' >";	
                    
                echo "<div id='cadre_graph' style='float:left;width:280px;max-height:80vh;overflow-y: auto;'>\n";       
						
                    echo "<div id='boxpopup' style='width:235px;padding:10px 10px;margin-bottom:10px;'>\n";

                        echo "<input type='hidden' name='target_station_save' id='target_station_save' value='".$stations_selected_list."'>";
                    
                        require(DIR_WS_FILTRE . 'filtre_stations_html.php');
                        
                        /*            
                        // Un seul type de données (CI, CIE, PJ, PJE, ...)
                        if($select_type_encours > 0)
                        {
                            echo "<div id='boxpopup' class='select' style='width:240px;margin-top: 0px;margin-left: 10px;background-color: #f7f7f7;'>\n";
                                
                                echo "<div id='boite_small'>\n";		
                            
                                    echo "<p>".htmlaccent('Sélectionner un seul type de chronique')."</p>";	
                                
                                    echo "<select name='select_type_chron' id='select_type_chron' style='width:200px;margin-right: 20px;'>";
                                                                
                                        echo "<option value='0' >".htmlaccent('-')."</option>";
                                        
                                        $selected = '';	
                                        
                                        if(isset($type_chron_array))
                                        {
                                            foreach ($type_chron_array as $id_type_chron => $type_chron)
                                            {
                                                $selected = '';
                                                if($type_chron['id_eq_type_data']==$select_type_encours)
                                                {echo "<option value='".$id_type_chron."' ".$selected." >".$type_chron['init_type_data']." - ".$type_chron['nom_type_data']."</option>\n";}
                                            }
                                        }
                                        
                                    echo "</select>";
                                        
                                echo "</div>\n";
                                        
                            echo "<hr>\n";
                            echo "</div>\n";	
                        }
                        */
                    echo "</div>\n";	                        
                
                echo "<hr>\n";
                echo "</div>\n";
            
            echo "</form >\n";

            $lien_form = tep_href_link('data_chron.php');
            $name_form = 'form_valid';			
            echo "<form name='".$name_form."' id='".$name_form."' action='".$lien_form."' method='post' enctype='multipart/form-data' onsubmit='validateForm(event)'>";	

                echo "<input type='hidden' name='export' id='export' value='".$select_export."'>";

                echo "<div style='float:none;width:auto;height:45vh;'>";

                    echo "<div id='cadre_graph' style='float:left;width:55%;height:78vh;margin-left:15px;'>\n";
                        
                        // Select Stations
                        echo "<div id='boxpopup' class='select' style='width:100%;margin-top: 0px;'>\n";
                            
                            echo "<div style='float:left;width:45%;'>\n";

                                echo "<h2 id='selected_refcount' style='float:left;width:100%;'>";
                                    echo "Nombre de stations à sélectionner : ".$nb_stations." station(s)";
                                echo "</h2>";
                                
                                // Première liste déroulante avec les stations à sélectionner
                                echo "<select name='select_station_ref[]' id='select_station_ref' multiple='multiple' style='width:100%;height:60vh;' ondblclick=\"moveItems('select_station_ref', 'target_station_ref')\">";
                                            
                                    if(isset($station_array))
                                    {
                                        foreach($station_array as $key => $value)
                                        {
                                            // Vérifie si l'id est dans la liste à exclure
                                            if (in_array($key, $stations_selected)) {continue;} // on passe à la suivante
                                            
                                            echo "<option value='".$key."' style='background-color:".$value['type_color_background'].";'>";
                                                echo $value['nom_eq_type']." - ".$value['code_station']." - ".$value['nom_station'];
                                            echo "</option>";
                                        }
                                    }
                                                                            
                                echo "</select>";

                            echo "</div>\n";

                            // Boutons pour déplacer les éléments
                            echo "<div style='float:left;width:4%;margin:0 2%;margin-top:20vh;'>\n";
                                echo "<img src='".DIR_WS_IMG_ICO."arrow_next.png' style='width:30px;cursor:pointer;' title='".htmlaccent('Sélectionner les Stations')."'
                                        onclick=\"moveItems('select_station_ref', 'target_station_ref')\"
                                        onmouseover=\"this.src='".DIR_WS_IMG_ICO."arrow_next_over.png';\" onmouseout=\"this.src='".DIR_WS_IMG_ICO."arrow_next.png';\" >"; 
                                
                                
                                echo "<img src='".DIR_WS_IMG_ICO."arrow_previous.png' style='width:30px;cursor:pointer;' title='".htmlaccent('Supprimer la sélection')."'
                                        onclick=\"moveItems('target_station_ref', 'select_station_ref')\" 
                                        onmouseover=\"this.src='".DIR_WS_IMG_ICO."arrow_previous_over.png';\" onmouseout=\"this.src='".DIR_WS_IMG_ICO."arrow_previous.png';\" >";
                            echo "</div>";

                            echo "<div style='float:left;width:45%;'>\n";

                                echo "<h2 id='selected_targetcount' style='float:left;width:100%;'>";
                                    echo "Nombre de stations sélectionnées : ".$nb_stations_target." station(s)";
                                echo "</h2>";

                                // Deuxième liste déroulante après sélection multiple
                                echo "<select name='target_station_ref[]' id='target_station_ref' multiple='multiple' style='width:100%;height:60vh;' ondblclick=\"moveItems('target_station_ref','select_station_ref')\">";
                                
                                    foreach($stations_selected as $key)
                                    {
                                        if (isset($station_target_array[$key])) 
                                        {
                                            echo "<option value='".$key."' style='background-color:".$station_target_array[$key]['type_color_background'].";'>".$station_target_array[$key]['nom_eq_type']." - ".$station_target_array[$key]['code_station']." - ".$station_target_array[$key]['nom_station']."</option>";
                                        }
                                    }
                                
                                echo "</select>";
                            
                            echo "</div>\n";

                        echo "<hr>\n";
                        echo "</div>\n";

                    echo "<hr>\n";
                    echo "</div>\n";    
                    
                    echo "<div id='cadre_graph' style='float:left;width:8%;height:60vh;margin-left:30px;'>\n";

                        // Periode de données	
                        // Mise en commentaire - JMM n'en veut pas
                        /*
                        echo "<div id='boxpopup' class='select-top' style='width:100%;margin:0px;padding: 15px 3%;'>\n";
                                
                            echo "<div id='boite_small' style='width:100%;'>\n";
                                            
                                echo "<p style='float:left;width:20%;padding-top:5px;'>";
                                    echo htmlaccent('Période');
                                echo "</p>";

                                echo "<select name='select_periode' id='select_periode' onchange='select_periode_function();' style='float:right;width:130px;'>";
                                    
                                    echo "<option value='1' selected>".htmlaccent('Plusieurs années')."</option>";
                                    echo "<option value='2' >".htmlaccent('Plusieurs mois')."</option>";
                                    echo "<option value='3' >".htmlaccent('Personnaliser...')."</option>";
                                    
                                echo "</select>";	
                                    
                            echo "</div>";

                            echo "<hr>\n";

                                            
                            // Date début aaaa ou mm/aa ou jj/mm/aaa
                            
                            echo "<div id='boite_small' class='list_month' style='display:none;'>\n";
                                            
                                echo "<p style='color:#428bca;'>".htmlaccent('1er mois')."</p>";
                                echo select_mois('select_month_f',1);
                                    
                            echo "</div>";
                            
                            echo "<div id='boite_small' class='list_year' >\n";
                                            
                                echo "<p style='color:#428bca;'>".htmlaccent('1er année')."</p>";
                                echo "<select name='select_year_f' style='width:65px;'>";
                                    $annee_temp = 0;
                                    $annee = 0;
                                    for($a=$l_y;$a>=$f_y;$a--)
                                    {
                                        $selected='';
                                        if($a==$year_first){$selected='SELECTED';}
                                        echo "<option value='".$a."' ".$selected.">".$a."</option>";
                                        
                                    }
                                echo "</select>";
                                    
                            echo "</div>";

                            echo "<hr>\n";
                            
                            
                            // Date fin aaaa ou mm/aa
                            
                            echo "<div id='boite_small' class='list_month' style='display:none;'>\n";
                                            
                                echo "<p style='color:#d9534f;'>".htmlaccent('Dernier mois')."</p>";
                                echo select_mois('select_month_l',1);
                                    
                            echo "</div>";
                            
                            echo "<div id='boite_small' class='list_year' style='margin-right:0;'>\n";
                                            
                                echo "<p style='color:#d9534f;'>".htmlaccent('Dernière année')."</p>";
                                echo "<select name='select_year_l' style='width:65px;'>";
                                    $annee_temp = 0;
                                    $annee = 0;
                                    for($a=$l_y;$a>=$f_y;$a--)
                                    {							
                                        $selected='';
                                        if($a==$year_today){$selected='SELECTED';}
                                        echo "<option value='".$a."' ".$selected.">".$a."</option>";
                                        
                                    }
                                echo "</select>";
                                    
                            echo "</div>";

                            // -----------------------

                            // Date First jj/mm/aaaa
                            echo "<div id='boite_small' class='select_date' style='display:none;'>\n";
                                
                                echo "<p style='width:80px;color:#428bca;'>".htmlaccent('Date de début')."</p>\n";	
                                echo "<input class='input_texte' style='width:80px;padding-bottom: 4px;' name='date_f' id='date_f' type='text' value='".$date_1."' onclick=\"javascript:displayCalendar(document.forms[0].date_f,'dd-mm-yyyy',this);\" >\n"; 											
                                        
                            echo "</div>\n";
                        
                            echo "<hr>\n";
                            
                            // Date Fin jj/mm/aaaa
                            echo "<div id='boite_small' class='select_date' style='display:none;'>\n";
                                
                                echo "<p style='width:80px;color:#d9534f;'>".htmlaccent('Date de fin')."</p>\n";	
                                echo "<input class='input_texte' style='width:80px;padding-bottom: 4px;' name='date_l' id='date_l' type='text' value='".$date_2."' onclick=\"javascript:displayCalendar(document.forms[0].date_l,'dd-mm-yyyy',this);\" >\n"; 											
                                        
                            echo "</div>\n";
                                

                        echo "</div>\n";
                        */


                        echo "<div style='width:100%;padding:0 3%;'>\n";

                            //echo "<input type='submit' class='button' name='valid_chron_step1' value='Valider' style='width:100%;margin:20px 0;' />";	
                            echo "<input type='submit' class='button' name='valid_chron_step1' value='Valider' style='width:100%;margin:0px 0;' />";	
                        
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

        // Tri des options sélectionnées par ordre alphabétique
        selectedOptions.sort((a, b) => a.text.localeCompare(b.text));

        // Les ajouter à l'autre liste
        selectedOptions.forEach(option => {
            fromSelect.removeChild(option); // Retirer de la liste d'origine
            toSelect.appendChild(option); // Ajouter à la liste de destination
        });

        // Mettre à jour la liste des stations sélectionnées pour être récupérable après un second filtre.
        syncHidden();

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

        // Si au moins une station est sélectionnée, le formulaire peut être soumis
        // Si la validation réussit, changer l'attribut 'target' du formulaire
        var form = document.getElementById('form_valid');
        form.target = "_blank"; // Définir target sur _blank pour ouvrir dans une nouvelle fenêtre/onglet

        return true;
    }

    // Fonction pour sauvegarder les stations sélectionner quand on fitre dans un second temps
    function syncHidden() 
    {
        const selectTarget = document.getElementById('target_station_ref');
        const hiddenSelect = document.getElementById('target_station_save');
        const valuesSelect = Array.from(selectTarget.options).map(opt => opt.value);
        hiddenSelect.value = valuesSelect.join(',');
    }
    


</script>