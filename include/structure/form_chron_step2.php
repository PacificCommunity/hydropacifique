<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Formulaire pour sélection les stations et les données que l'on veut éditer / observer - Etape 2 : Sélections Chroniques
----------------------------------------
- Une fois les stations sélectionnées, elles sont triées par type de données collecté (Hydro, Pluvio, Piezo)
- Affichage des toutes les chroniques disponibles + RA, JGE, ETL
- L'accès au données se fait par une procédure AJAX pour gérer le temps de latence de récupération des données dans la base
- Un fois les chroniques sélectionnées il est possible de la exporter ou de les visualiser.  

*/

// --------------------------------------


$nb_stations_ref = 0;
$nb_chron_all = 0;
$nb_data_all = 0;

$id_station_encours = 0;
$id_eq_type = 0;
$sql_condition_typedata = '';

/*
$select_periode = 1;
if(isset($_POST['select_periode'])){$select_periode = $_POST['select_periode'];}
*/

$min_date_all = null;
$max_date_all = null;

$print_table = '';

// --------------------------------------
// Debut HTML

require(DIR_WS_STRUCTURE . 'header_web.php');


echo "<body>";

    require(DIR_WS_BOX . 'block_verif_deletedata.php'); // Block pour permettre une confirmation de l'enregistrement des données    
    require(DIR_WS_BOX . 'block_info_chron.php'); // Block pour affichage des informations sur les Chroniques

    require(DIR_WS_STRUCTURE . 'header.php'); // Bando Haut
    include(DIR_WS_BOX . 'nav_accueil.php'); // Menu

    echo "<div id='contour_general'>";

        echo "<div id='contenu_info' style='display:none;'></div>";

        echo "<div id='contenu_centre'>";

            echo "<div id='contenu_box2'>";

                echo "<h1>";
                                
                    echo "<span>".htmlaccent('Accès aux données - Etape 2 : Sélection des chroniques de données disponibles')."</span>";
                    
                echo "</h1>";

                $lien_form = tep_href_link('data_chron.php');			
                echo "<form name='select_chron_step2' id='select_chron_step2' action='".$lien_form."' method='post' enctype='multipart/form-data' onsubmit='validateForm(event)'>";
                    
                    echo "<input type='hidden' name='select_step' value='2' />\n"; 
                    echo "<input type='hidden' name='select_type_data' value='".$select_type_encours."' />\n"; // TYPE GENERAL (Pluie, Débit, Piezo)
                    echo "<input type='hidden' name='select_list_station_txt' value='".$list_station_txt."' />\n";
                    
                    echo "<input type='hidden' name='select_type_chron' id='select_type_chron' value='".$select_type_chron."' />\n";

                    echo "<input type='hidden' name='confirmationForm_step1' id='confirmationForm_step1' value='not_confirmed' />\n";
                    echo "<input type='hidden' name='confirmationForm_step2' id='confirmationForm_step2' value='not_confirmed' />\n";

                    // Colonne de Gauche permettant d'avoir accès à différents modes de consultation des données
                    
                    echo "<div id='cadre_graph' style='float:left;width:200px;margin-right:0.5%;height:75vh;overflow-y: auto;'>\n";

                        echo "<div style='float:left;width:100%;margin-top:8px;margin-bottom:15px;'>";

                            echo "<img src='".DIR_WS_IMG_ICO."info.png' style='float:left;width:20px;margin-left:5px;margin-right:10px;'>";    
                            echo "<p style='float:left;margin-top:3px;'>";
                                echo "<a onClick='afficheBlockInfoChron();'>";
                                    echo "<span style='font-size:13px;font-weight:bold;'>".htmlaccent('Détails sur les chroniques')."</span>";
                                echo "</a>\n";
                            echo "</p>\n";

                        echo "</div>\n";

                        // SELECT PERIODE
                    
                        echo "<div id='boxpopup' class='select-top' style='width:90%;margin-bottom:10px;padding-top:10px;padding-botton:5px;padding-left:10px;'>\n";
                                    
                            /* Simplification du système de date

                                echo "<div id='boite_small' style='width:95%;margin:0;'>\n";
                                                    
                                    echo "<p style='float:left;width:25%;font-weight: bold;color: #000;font-size: 12px;margin-top:5px;'>";
                                        echo htmlaccent('Période');
                                    echo "</p>";


                                    echo "<select name='select_periode' id='select_periode'  onchange='select_periode_function();' style='float:right;width:65%;'>";

                                        $selected = '';
                                        if($select_periode == 1){$selected = 'selected';}
                                        echo "<option value='1' ".$selected.">".htmlaccent('Plusieurs années')."</option>";
                                        
                                        $selected = '';
                                        if($select_periode == 2){$selected = 'selected';}
                                        echo "<option value='2' ".$selected.">".htmlaccent('Plusieurs mois')."</option>";
                                        
                                        $selected = '';
                                        if($select_periode == 3){$selected = 'selected';}
                                        echo "<option value='3' ".$selected.">".htmlaccent('Personnaliser...')."</option>";
                                        
                                    echo "</select>";	
                                        
                                echo "</div>";

                                echo "<hr>\n";

                                // Date début aaaa ou mm/aa ou jj/mm/aaa

                                $display_month = 'display:none;';
                                $display_year = 'display:none;';                    
                                $display_date = 'display:none;';

                                if($select_periode == 1){$display_year = 'display:block;';}
                                if($select_periode == 2)
                                {
                                    $display_year = 'display:block;';
                                    $display_month = 'display:block;';
                                }
                                if($select_periode == 3){$display_date = 'display:block;';}

                                echo "<div id='boite_small' class='list_month' style='".$display_month."'>\n";
                                                
                                    echo "<p style='color:#428bca;'>".htmlaccent('1er mois')."</p>";
                                    echo select_mois('select_month_f',$month_1);
                                        
                                echo "</div>";  

                                echo "<div id='boite_small' class='list_year' style='".$display_year."'>\n";
                                                
                                    echo "<p style='color:#428bca;'>".htmlaccent('1er année')."</p>";
                                    echo "<select name='select_year_f' style='width:65px;'>";
                                        $annee_temp = 0;
                                        $annee = 0;
                                        for($a=$l_y;$a>=$f_y;$a--)
                                        {
                                            $selected='';
                                            if($a==$year_1){$selected='SELECTED';}
                                            echo "<option value='".$a."' ".$selected.">".$a."</option>";
                                            
                                        }
                                    echo "</select>";
                                        
                                echo "</div>";

                                echo "<hr>\n";

                                // Date fin aaaa ou mm/aa

                                echo "<div id='boite_small' class='list_month' style='".$display_month."'>\n";
                                                
                                    echo "<p style='color:#d9534f;'>".htmlaccent('Dernier mois')."</p>";
                                    echo select_mois('select_month_l',$month_2);
                                        
                                echo "</div>";

                                echo "<div id='boite_small' class='list_year' style='margin-right:0;".$display_year."'>\n";
                                                
                                    echo "<p style='color:#d9534f;'>".htmlaccent('Dernière année')."</p>";
                                    echo "<select name='select_year_l' style='width:65px;'>";
                                        $annee_temp = 0;
                                        $annee = 0;
                                        for($a=$l_y;$a>=$f_y;$a--)
                                        {							
                                            $selected='';
                                            if($a==$year_2){$selected='SELECTED';}
                                            echo "<option value='".$a."' ".$selected.">".$a."</option>";
                                            
                                        }
                                    echo "</select>";
                                        
                                echo "</div>";

                            */

                            // ---------------------------------------------------------
                            $select_periode = '';
                            echo "<div id='boite_small' style='width:100%;margin:0;margin-right:5%;'>\n";
                            
                                echo "<p style=''>".htmlaccent('Sélectionner une période')."</p>\n";	

                                echo "<select id='select_periode' name='select_periode' style='width:60%;margin-bottom:10px;'>";
                                       
                                        $select_periode = 'selected';
                                        echo "<option value='none' ".$select_periode.">"."All"."</option>";
                                        $select_periode = '';
                                        
                                        echo "<option value='ytd' ".$select_periode.">".htmlaccent('Année en cours')."</option>"; // Year To Date
                                        echo "<option value='6months' ".$select_periode.">".htmlaccent('6 mois')."</option>";
                                        echo "<option value='12months' ".$select_periode.">".htmlaccent('12 mois')."</option>";                                    
                                        echo "<option value='2years' ".$select_periode.">".htmlaccent('2 ans')."</option>";      
                                        
                                        echo "<option value='5years' ".$select_periode.">".htmlaccent('5 ans')."</option>";                                
                                        
                                        //$select_periode = 'selected';
                                        echo "<option value='10years' ".$select_periode.">".htmlaccent('10 ans')."</option>";        
                                        //$select_periode = '';  

                                        echo "<option value='20years'>".htmlaccent('20 ans')."</option>";
                                        
                                    echo "</select>";

                            echo "</div>\n";


                            // Date First jj-mm-aaaa
                            echo "<div id='boite_small' class='select_date' style='margin:0;margin-right:5%;'>\n";
                                
                                echo "<p style='width:70px;color:#428bca;'>".htmlaccent('Date de début')."</p>\n";	
                                //echo "<input class='input_texte' style='width:65px;padding-bottom: 4px;' name='date1_encours' id='date1_encours' type='text' value='".$date_1."' onclick=\"javascript:displayCalendar(document.forms[0].date1_encours,'dd-mm-yyyy',this);\" >\n";
                                echo "<input class='input_texte' style='width:65px;padding-bottom: 4px;' name='date1_encours' id='date1_encours' type='text' value='".$date_1."' >\n"; 											
                                        
                            echo "</div>\n";

                            // Date Fin jj-mm-aaaa
                            echo "<div id='boite_small' class='select_date' style='float:left;margin:0;'>\n";
                                
                                echo "<p style='width:70px;color:#d9534f;'>".htmlaccent('Date de fin')."</p>\n";	
                                //echo "<input class='input_texte' style='width:65px;padding-bottom: 4px;' name='date2_encours' id='date2_encours' type='text' value='".$date_2."' onclick=\"javascript:displayCalendar(document.forms[0].date2_encours,'dd-mm-yyyy',this);\" >\n"; 											
                                echo "<input class='input_texte' style='width:65px;padding-bottom: 4px;' name='date2_encours' id='date2_encours' type='text' value='".$date_2."' >\n";
                                        
                            echo "</div>\n";

                            /*
                            echo "<div id='button_load' style='float:left;width:40%;margin-top:10px;padding:4px 5px;' title='".htmlaccent('Recharger les données avec les nouvelles dates')."'>";
                                echo  "<span>".htmlaccent('Recharger')."</span>"; 
                            echo "</div>\n";
                            */

                        echo "<hr>\n";
                        echo "</div>\n";
                        


                        //---------------------------------------
                        // Visu données en ligne

                        if(!$select_export)
                        {
                            echo "<div id='boxpopup' class='select-top' style='width:90%;margin-bottom:10px;padding-top:10px;padding-botton:5px;padding-left:10px;'>\n";

                                echo "<p style='font-weight: bold;color: #005b96;font-size: 13px;'>";
                                    echo htmlaccent('Visualisation graphique');
                                echo "</p>\n";

                                echo "<p>";
                                    echo "<input type='checkbox' name='one_graph' id='one_graph' >";	
                                    echo "<span style='font-size: 11px;font-weight:bold;margin-left:5px'>";
                                        echo "Graphique combiné";
                                    echo "</span>";
                                echo "</p>\n";
                                
                                /*
                                echo "<p>";
                                    echo "<input type='checkbox' id='new_page' name='new_page' checked  onchange='this.form.target=this.checked?\"_blank\":\"_self\";'>";	
                                    echo "<span style='font-size: 12px;font-weight:bold;margin-left:5px;'>".htmlaccent('Nouvel onglet')."</span>";
                                echo "</p>\n";
                                */

                                echo "<input type='submit' class='button_graph' name='button_graph' id='button_graph_edit' style='display:none;' value='".htmlaccent('Editer')."' />";
                                
                            echo "<hr>\n";
                            echo "</div>\n";
                        }
                        
                        
                        //---------------------------------------
                        // Export CSV

                        echo "<div id='boxpopup' class='select-top' style='width:90%;margin-bottom:10px;padding-top:10px;padding-botton:5px;padding-left:10px;'>\n";

                            echo "<p style='font-weight: bold;color: #36802d;font-size: 13px;'>";
                                echo htmlaccent('Extraction des données');
                            echo "</p>\n";

                            /*

                            echo "<p>";
                                echo "<input type='checkbox' name='multi_file' id='multi_file' checked style='width:30px;' >";	
                                echo "<span style='font-size: 12px;font-weight:bold;margin-top:30px;'>".htmlaccent('Un fichier par station')."</span>";
                            echo "</p>\n";

                            echo "<p>";
                                echo "<input type='checkbox' name='format_csv' id='format_csv' style='width:30px;' >";	
                                echo "<span style='font-size: 12px;font-weight:bold;margin-top:30px;'>".htmlaccent('xlsx (par défault csv)')."</span>";
                            echo "</p>\n";
                            */

                            /* -- cette compression prend trop de temps
                            echo "<p>";
                                echo "<input type='checkbox' name='export_zip' id='export_zip' style='width:30px;' >";	
                                echo "<span style='font-size: 12px;font-weight:bold;margin-top:30px;'>".htmlaccent('Compression (format zip)')."</span>";
                            echo "</p>\n";

                            echo "<p>";
                                echo "<input type='checkbox' name='entete_col' id='entete_col' >";	
                                echo "<span style='font-size: 12px;font-weight:bold;margin-left:5px;'>".htmlaccent('Afficher : en-tête colonnes')."</span>";
                            echo "</p>\n";
                            
                            */

                            echo "<input type='submit' class='button_export' name='button_export'  id='button_export_edit' style='display:none;' value='".htmlaccent('Export')."' />";
                            
                        echo "<hr>\n";
                        echo "</div>\n";

                        //---------------------------------------
                        // Supprimer données en ligne
                        
                        //if($id_statut_user <= 3)
                        //{
                        if(!$select_export && $parametre > 0)
                        {
                            echo "<div id='boxpopup' class='select-top' style='width:90%;padding-top:10px;padding-botton:5px;padding-left:10px;'>\n";

                                echo "<p style='font-weight: bold;color: #960a00;font-size: 13px;'>";
                                    echo htmlaccent('Supprimer les données');
                                echo "</p>\n";

                                //echo "<input type='submit' class='button_delete' name='button_delete_edit'  id='button_delete_edit' style='display:none;' value='Supprimer'  />";
                                

                                echo "<div id='button_del' style='float:left;width:50%;margin-top:5px;padding:4px 5px;display:none;' title='".htmlaccent('Supprimer')."'>";
                                    echo  "<span>".htmlaccent('Supprimer')."</span>"; 
                                echo "</div>\n";

                            echo "<hr>\n";
                            echo "</div>\n";
                        }
                        
                    
                    echo "<hr>\n";
                    echo "</div>\n";

                    // On affiche ici la liste des chroniques disponibles par stations sélectionnées
                    echo "<div id='cadre_graph' style='height:75vh;overflow-y: auto;'>\n";

                        echo "<div id='onglet_contenu'>\n";

                            echo "<div id='boite1' class='first' style='margin:0px;'>\n";

                                echo "<div id='wait' style='width:100%;height:65px;margin-top:30px;text-align:center;'>";
                                    echo "<img src='".DIR_WS_IMG."hp100.gif' style='width:150px;' title='".htmlaccent('Chargement en cours ...')."'>";
                                    echo "<p style='text-align:center;color:#000;'>".htmlaccent('Chargement en cours ...')."</p>";
                                    echo "<p style='text-align:center;margin-bottom:0px;'>".htmlaccent('Si vous avez sélectionné un nombre important de stations la durée de chargement peut être supérieure à 1 minute')."</p>";
                                    echo "<p style='text-align:center;'>".htmlaccent('- Veuillez patienter -')."</p>";
                                echo "</div>\n";  

                                echo "<div id='result' style='width:100%;height:65px;text-align:center;'>";
                                echo "</div>\n"; 
                            
                            echo "<hr>\n";
                            echo "</div>\n";		
                                
                        echo "<hr>\n";
                        echo "</div>\n";	
                
                    echo "<hr>\n";
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

    msgInfo = document.getElementById('contenu_info');
        
    formSelectChron = document.getElementById('select_chron_step2');
    selectPeriode = document.getElementById('select_periode');
    date1Input = document.getElementById('date1_encours');
    date2Input = document.getElementById('date2_encours');

    resultData = document.getElementById('result');

    deleteDataButton = document.getElementById('button_del');
    graphEditButton = document.getElementById('button_graph_edit');
    exportEditButton = document.getElementById('button_export_edit');

    popupVerifDeleteData = document.getElementById('box_verif_deletedata');
    detailDel = document.getElementById('detail_del');
    detailDelText = document.getElementById('detail_del_text');
    
    okButton = document.getElementById('ok_valid_deletedata');
    noButton = document.getElementById('no_valid_deletedata');

    list_station_txt = '<?php echo $list_station_txt; ?>';
    
    

    function selectDateData()
    {
        //event.preventDefault(); // Empêcher la soumission du formulaire

        const today = new Date();
        let dateDebutSelect = new Date();
        
        switch (selectPeriode.value) 
        {
            case 'ytd':
                dateDebutSelect = new Date(today.getFullYear(), 0, 1); // 1er janvier de l'année en cours
                break;
            case '6months':
                dateDebutSelect.setMonth(today.getMonth() - 6);
                break;
            case '12months':
                dateDebutSelect.setMonth(today.getMonth() - 12);
                break;
            case '2years':
                dateDebutSelect.setFullYear(today.getFullYear() - 2);
                break;
            case '5years':
                dateDebutSelect.setFullYear(today.getFullYear() - 5);
                break;
            case '10years':
                dateDebutSelect.setFullYear(today.getFullYear() - 10);
                break;
            case '20years':
                dateDebutSelect.setFullYear(today.getFullYear() - 20);
                break;
            case 'none':
                dateDebutSelect.setFullYear(today.getFullYear() - 80);
                break;
        }

        // Formatage des dates en YYYY-MM-DD
        const formatDate = (date) => 
        {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${day}-${month}-${year}`;
        };

        date1Input.value = formatDate(dateDebutSelect);
        date2Input.value = formatDate(today);
    }

    selectPeriode.addEventListener('change', function(event) {selectDateData();});

    formSelectChron.addEventListener('submit', function(event)
    {        
        if(!isValidDatesInput() || !validateForm(event))
        {
                event.preventDefault(); // Empêcher la soumission du formulaire
                return;
        }    
    });

    // FONCTION POUR ACCEDER AU DONNEEES - METHODE CLIENT SERVEUR (AJAX)


    // Lancement de l'acquisition des données

    function load_data()
    {
        if(isValidDatesInput())
        {
            // Mise au format JSON des données
            // Créer un objet contenant les données à envoyer
            var dataToSend = {
                list_station_txt: list_station_txt, // liste des stations sélectionnées
                date_1: date1Input.value, 
                date_2: date2Input.value
            };

            // Convertir l'objet en JSON
            var jsonData = JSON.stringify(dataToSend);

            // Effectuer une requête AJAX asynchrone
            var xhr = new XMLHttpRequest();
            xhr.open("POST", "include/structure/selectdata/process_select_chron.php", true);
            xhr.setRequestHeader("Content-Type", "application/json");

            xhr.onreadystatechange = function() 
            {
                if (xhr.readyState === 4 && xhr.status === 200) 
                {
                    // Accéder aux données de retour
                    var jsonResponse = JSON.parse(xhr.responseText);
                    result = jsonResponse['result_html'];
                    
                    resultData.innerHTML = result;

                    document.getElementById('wait').style.display = 'none';
                    document.getElementById('result').style.display = 'block';
                    if(graphEditButton){graphEditButton.style.display = 'block';}
                    if(exportEditButton){exportEditButton.style.display = 'block';}
                    if(deleteDataButton){deleteDataButton.style.display = 'block';}
                    
                }
            };

            // Envoyer les données JSON au serveur
            xhr.send(jsonData);
        }
    }

    load_data();selectDateData();

    function delete_data(list_station_txt)
    {
        checkBoxesData = document.querySelectorAll('input[type="checkbox"]:not(#multi_file):not(#format_export):not(#rapport_act):not(#one_graph):not(#new_page):not(#entete_col)');
        checkedTabData = [];
        
        checkBoxesData.forEach(checkbox => {
            if (checkbox.checked) 
            {
                checkedTabData.push(checkbox.value);
            }
        });

        // Mise au format JSON des données
        // Créer un objet contenant les données à envoyer
        var dataToSend = {
            checkedTabData: checkedTabData, 
            date_1: date1Input.value, 
            date_2: date2Input.value
        };

        // Convertir l'objet en JSON
        var jsonData = JSON.stringify(dataToSend);

        // Effectuer une requête AJAX asynchrone
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "include/structure/selectdata/process_delete_chron.php", true);
        xhr.setRequestHeader("Content-Type", "application/json");

        xhr.onreadystatechange = function() 
        {
            if (xhr.readyState === 4 && xhr.status === 200) 
            {
                // Analyser la réponse JSON
                var jsonResponse = JSON.parse(xhr.responseText);

                load_data();

                // Accéder aux données récupéré coté serveur
                msgInfo.innerText = jsonResponse['js_text'];
                msgInfo.style.border = '4px solid #09886d'; // bordure en vert
                msgInfo.style.display = 'block';
            }

        };

        // Envoyer les données JSON au serveur
        xhr.send(jsonData);
    }

    // Gestion de la suppression des données 
    if(deleteDataButton)
    {
        deleteDataButton.addEventListener('click', function() 
        {
            if(validateForm(event))
            {
                if(isValidDatesInput())
                {
                    // Afficher le popup de confirmation  
                    msgInfo.style.display = 'none';       
                    popupVerifDeleteData.style.display = 'block';            
                    detailDel.style.display = 'block';

                    detailDelText.value = 'Période : du '+ date1Input.value +' au ' + date2Input.value; 

                    // Gérer le bouton "Oui"
                    okButton.addEventListener('click', function () 
                    {
                        // Sélectionner toutes les cases cochées avec le nom "check_chron[]"
                        checkboxesDel = document.querySelectorAll('input[type="checkbox"]:not(#multi_file):not(#format_export):not(#rapport_act):not(#one_graph):not(#new_page):not(#entete_col)');
                        
                        // Récupérer les valeurs des cases cochées
                        const values = Array.from(checkboxesDel).map(checkboxDel => checkboxesDel.value);
                        
                        popupVerifDeleteData.style.display = 'none'; // Fermer le popup
                        
                        delete_data();
                    });

                    // Gérer le bouton "Non"
                    noButton.addEventListener('click', function () 
                    {
                        popupVerifDeleteData.style.display = 'none'; // Fermer le popup sans rien faire
                    });                  
                }            
            }    
        });
    }

    

    // FONCTIONS DE GESTION ERGONOMIQUE ET FLUIDITE DE NAVIGATION

    // Fonction permettant de sélectionner rapidement les chroniques
    function toggleCheckboxes(id_station,id_type,id_chron) 
    {
        let checkboxes;
        //checkboxes = document.querySelectorAll('input[type="checkbox"]:not(#multi_file):not(#format_export):not(#rapport_act):not(#one_graph):not(#new_page):not(#entete_col)');
        if (id_station && id_station>0) {
            // Select automatique pour toute une station
            checkboxes = document.querySelectorAll('input[type="checkbox"][value^="'+id_station+'"]:not(#multi_file):not(#format_export):not(#rapport_act):not(#one_graph):not(#new_page):not(#entete_col)');
        }
        if (id_type && id_type>0) {
            // // Select automatique pour tout un type de données (hydro, pluvio, piézo, ...)
            checkboxes = document.querySelectorAll('input[type="checkbox"][value*="_'+id_type+'_"]:not(#multi_file):not(#format_export):not(#rapport_act):not(#one_graph):not(#new_page):not(#entete_col)');
        }
        if (id_chron && id_chron>0) {
            // Select automatique pour un type de Chronique (CI, QI, PJ, ...)
            checkboxes = document.querySelectorAll('input[type="checkbox"][value$="_'+id_chron+'"]:not(#multi_file):not(#format_export):not(#rapport_act):not(#one_graph):not(#new_page):not(#entete_col)');
        }    
        if (id_chron == 'ra') {
            // Select automatique pour un type de Chronique (CI, QI, PJ, ...)
            checkboxes = document.querySelectorAll('input[type="checkbox"][value$="_ra"]:not(#multi_file):not(#format_export):not(#rapport_act):not(#one_graph):not(#new_page):not(#entete_col)');
        }
        if (id_chron == 'jge') {
            // Select automatique pour un type de Chronique (CI, QI, PJ, ...)
            checkboxes = document.querySelectorAll('input[type="checkbox"][value$="_jge"]:not(#multi_file):not(#format_export):not(#rapport_act):not(#one_graph):not(#new_page):not(#entete_col)');
        }
        if (id_chron == 'etl') {
            // Select automatique pour un type de Chronique (CI, QI, PJ, ...)
            checkboxes = document.querySelectorAll('input[type="checkbox"][value$="_etl"]:not(#multi_file):not(#format_export):not(#rapport_act):not(#one_graph):not(#new_page):not(#entete_col)');
        }

        // Vérification de l'état des checkboxes
        let allChecked = true;
        for (let i = 0; i < checkboxes.length; i++) {
            const checkbox = checkboxes[i];
            if (!checkbox.checked) {
            allChecked = false;
            break;
            }
        }

        // Cocher ou décocher tous les éléments de la liste en fonction de l'état actuel
        if (allChecked) {
            for (let i = 0; i < checkboxes.length; i++) {
            const checkbox = checkboxes[i];
            checkbox.checked = false;
            }
        } else {
            for (let i = 0; i < checkboxes.length; i++) {
            const checkbox = checkboxes[i];
            checkbox.checked = true;
            }
        }

    }

    function handleSelectChange(select) 
    {
        var selectedIndex = select.selectedIndex;
        var selectedValue = select.options[selectedIndex].value;
        var selectedText = select.options[selectedIndex].text;

        toggleCheckboxes(0,0,selectedValue);
    }

    // Fonction pour vérifier s'il y a bien des stations sélectionner avant de soumettre le formulaire
    function validateForm(event) 
    {
        checkBoxesData = document.querySelectorAll('input[type="checkbox"]:not(#multi_file):not(#format_export):not(#rapport_act):not(#one_graph):not(#new_page):not(#entete_col)');

        // Vérifier si au moins une case à cocher est sélectionnée
        var isChecked = false;
        for (var i = 0; i < checkBoxesData.length; i++) 
        {
            if (checkBoxesData[i].checked) {
                isChecked = true;
                break;
            }
        }
        if (!isChecked) 
        {
            msgInfo.innerText = 'Vous devez sélectionner au moins une Chronique';
            msgInfo.style.display = 'block';
            event.preventDefault(); // Empêcher la soumission du formulaire
            return false; // Retourner false pour stopper le processus de soumission
        }

        // Si au moins une station est sélectionnée, le formulaire peut être soumis
        // Si la validation réussit, changer l'attribut 'target' du formulaire
        var form = document.getElementById('select_chron_step2');
        form.target = "_blank"; // Définir target sur _blank pour ouvrir dans une nouvelle fenêtre/onglet

        return true;
    }

    // Fonction pour valider une date réelle
    function isValidDate(dateString) 
    {
        // Vérifier le format avec une regex
        const dateRegex = /^(0[1-9]|[12][0-9]|3[01])-(0[1-9]|1[0-2])-(\d{4})$/;
        if (!dateRegex.test(dateString)) 
        {
            return false; // Format invalide
        }

        // Découper la date
        const [day, month, year] = dateString.split("-").map(Number);

        // Créer une date JavaScript et vérifier sa validité
        const date = new Date(year, month - 1, day); // Mois commence à 0 en JS
        return (
            date.getFullYear() === year &&
            date.getMonth() === month - 1 &&
            date.getDate() === day
        );
    }

    // Fonction pour convertir une date (format valide) en objet Date
    function parseDate(dateString) 
    {
        [day, month, year] = dateString.split("-").map(Number);
        return new Date(year, month - 1, day);
    }


    function isValidDatesInput()
    {   
        if(isValidDate(date1Input.value) && isValidDate(date2Input.value))
        {
            date1Format = parseDate(date1Input.value);
            date2Format = parseDate(date2Input.value);

            if(date1Format < date2Format){return true;}
            else
            {
                msgInfo.innerText = "La Date de début doit être antérieur à la Date de fin";
                msgInfo.style.display = 'block';

                return false;
            }
        }
        else
        {
            msgInfo.innerText = "Au moins l'une des dates saisies est invalide ou dans un mauvais format (dd-mm-yyy : format valide)";
            msgInfo.style.display = 'block';

            return false;
        }

    }

</script>
