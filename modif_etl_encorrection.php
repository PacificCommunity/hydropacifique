<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Modification des ETL (Etalonnages - Stations hydrométrique)
----------------------------------------
*/

require('include/application_top.php');

// --------------------------------------
// INIT VAR

$modif=false;

// Régler le fuseau horaire sur votre emplacement
//date_default_timezone_set('Europe/Paris');
$date_now = date('d-m-Y');

$nom_station = '';	
$code_station = '';

$x_min = 0;
$x_max = 0;
$y_min = 0;
$y_max = 0;

$id_etl = 0;
$id_etl_first = 0;

//$row_l="class='row1' onmouseover=\"this.className='row1hover';\" onmouseout=\"this.className='row1';\" onclick=\"this.className='rowSelect';\"";
$row_l="class='row1' onclick=\"this.className='rowSelect';\"";
$print_row = '';   

$titre_graph_first = "";
$data_graph_all = ""; // Variables données pour les graphiques 


// --------------------------------------

if(isset($_GET['st']))
{
    $st_id = mysqli_real_escape_string($sql_link,trim(addslashes($_GET['st'])));
    
    $sql_station = "SELECT DISTINCT s.id_station, s.nom_station, s.nom_court, s.code_station, 
									s.id_region, s.id_commune
					FROM ".TABLE_STATION." s 
					WHERE s.id_station=".$st_id;
	
	$station_query = tep_db_query($sql_link,$sql_station);
	$station = tep_db_fetch_array($station_query);

    if(isset($station['id_station']))
    {
        $nom_station = htmlaccent($station['nom_station']);	
        $code_station = htmlaccent($station['code_station']);
    }
    else{$message_info .= htmlaccent('La station n\'est pas identifiée.');}
}


// --------------------------------------
// Debut page HTML

require(DIR_WS_STRUCTURE . 'header_web.php');


echo "<body>";

require(DIR_WS_STRUCTURE . 'header.php'); // Bando Haut
include(DIR_WS_BOX . 'nav_accueil.php'); // Menu

/*
require(DIR_WS_ETL . 'block_etl_pts.php'); // Block pour affichage des coordonnées d'un point de la courbe
require(DIR_WS_ETL . 'block_etl_new.php'); // Block pour création d'un nouveau ETL (équation)
require(DIR_WS_ETL . 'block_etl_modif.php'); // Block pour la modification d'un ETL
require(DIR_WS_ETL . 'block_etl_duplic.php'); // Block pour la duplication d'un ETL
require(DIR_WS_ETL . 'block_etl_delete.php'); // Block pour la suppression d'un ETL
*/

echo "<div id='contour_general'>";

	echo "<div id='contenu_info' style='display:none;'></div>";
	
	echo "<div id='contenu_centre'>";

        echo "<div id='contenu_box2'>";
            
            echo "<h1 id='h1_graph'>";
                            
                echo "<span style=font-weight:bold;>".htmlaccent('Relation d\'Etalonnage (ETL)')."</span>";
                echo "<span>".htmlaccent(' - Station hydrométrique : '.$code_station.' - '.$nom_station)."</span>";
                
            echo "</h1>";
            
            
            // Colonne de gauche pour afficher la liste des ETL de la station
            echo "<div id='cadre_graph' style='float:left;width:18%;'>\n";

                echo "<div id='boxpopup' class='select-top' style='width:96%;margin:0px;padding:10px 1%;'>\n";
                    
                    echo "<p style='margin-left:1%;'>";
                        echo "<span style='font-weight: bold;font-size:13px;'>".htmlaccent('Liste des ETL')."</span>";
                    echo "</p>";
                    
                    echo "<div id='cadre_data_station_lgt' style='width:100%;margin:0;padding:0;display:none;'>\n";
                    echo "</div>\n";

                    echo "<div id='wait_tab' style='width:100%;height:65px;text-align:center;'>";
                        echo "<img src='".DIR_WS_IMG."wait.gif' style='width:50px;'>";
                        echo "<p>".htmlaccent('Chargement en cours ...')."</p>";
                    echo "</div>\n"; 
                    
                echo "<hr>\n";
                echo "</div>\n";

            echo "<hr>\n";
            echo "</div>\n";

            /*
            // Block Graphique
            echo "<div id='cadre_graph' style='float:left;width:81%;margin-left:0.5%;height:75vh;overflow-y: auto;'>\n";
                
                echo "<div id='boxpopup' class='select' style='width:99%;margin:0;padding:0;border:1px solid #000;'>\n";

                    // Boutons d'action

                    echo "<div id='button_newChron' style='float:left;width:80px;margin-left:5px;padding:4px 5px;' title='".htmlaccent('Créer un nouvel ETL')."'>\n";	
                        echo  htmlaccent('Nouveau'); 
                    echo "</div>\n";
                    
                    echo "<div id='button_modif' style='float:left;width:80px;margin-left:5px;padding:4px 5px;display:none;' title='".htmlaccent('Modifier l\'un des ETL sélectionnés')."'>";
                        echo  htmlaccent('Modifier'); 
                    echo "</div>";
                    
                    echo "<div id='button_visu' style='float:left;width:80px;margin-left:5px;padding:4px 5px;display:none;' title='".htmlaccent('Dupliquer l\'un des ETL sélectionnés')."'>\n";	
                        echo  htmlaccent('Dupliquer'); 
                    echo "</div>\n";
                    
                    echo "<div id='button_del' style='float:left;width:80px;margin-left:5px;padding:4px 5px;display:none;' title='".htmlaccent('Supprimer l\'un des ETL sélectionnés')."'>";
                        echo  htmlaccent('Supprimer'); 
                    echo "</div>";

                    echo "<p class='titre' style='height:20px;'></p>";

                    // Cadre Graph
                    
                    echo "<div id='plot' class='graph' style='height:50vh;margin:0 5px;display:none;'></div>\n";
                    
                    echo "<div id='wait_graph' style='width:100%;height:65px;text-align:center;'>";
                        echo "<img src='".DIR_WS_IMG."wait.gif' style='width:50px;'>";
                        echo "<p>".htmlaccent('Chargement en cours ...')."</p>";
                    echo "</div>\n"; 

                echo "<hr>\n";
                echo "</div>\n";	


                // ---------------------------------
                // Suivi des coordonnées
                echo "<div id='boxpopup' class='select' style='margin-top:10px;padding:10px;'>\n";

                    // Options de gestion du graphique (Contrôle dynamique du zoom et des échelles)
                    echo "<div style='float:left;width:200px;'>\n";
                                            
                        echo "<p>";
                            echo "<span style='font-weight: bold;font-size:13px;width:150px;'>".htmlaccent('Coordonnées - X : Hauteur (cm)')."</span>";
                        echo "</p>";

                        // Hauteur Min X
                        echo "<div id='boite_small'>\n";
                                
                            echo "<p style='width:80px;color:#428bca;'>".htmlaccent('Xmin')."</p>\n";	
                            echo "<input class='input_texte' style='width:60px;padding-bottom: 4px;' name='x_min' id='x_min' type='text' value='".$x_min."' >\n";

                        echo "</div>\n";

                        // Hauteur Max X
                        echo "<div id='boite_small'>\n";
                                
                            echo "<p style='width:80px;color:#428bca;'>".htmlaccent('Xmax')."</p>\n";	
                            echo "<input class='input_texte' style='width:60px;padding-bottom: 4px;' name='x_max' id='x_max' type='text' value='".$x_max."' >\n";

                        echo "</div>\n";

                    echo "</div>\n";

                    echo "<div style='float:left;width:200px;margin-left:10px;'>\n";

                        echo "<p>";
                            echo "<span style='font-weight: bold;font-size:13px;width:150px;'>".htmlaccent('Coordonnées - Y : Débit (m3/s)')."</span>";
                        echo "</p>";

                        // Debit Min Y
                        echo "<div id='boite_small'>\n";
                                
                            echo "<p style='width:80px;color:#428bca;'>".htmlaccent('Ymin')."</p>\n";	
                            echo "<input class='input_texte' style='width:60px;padding-bottom: 4px;' name='y_min' id='y_min' type='text' value='".$y_min."' >\n";

                        echo "</div>\n";

                        // Debit Max Y
                        echo "<div id='boite_small'>\n";
                                
                            echo "<p style='width:80px;color:#428bca;'>".htmlaccent('Ymax')."</p>\n";	
                            echo "<input class='input_texte' style='width:60px;padding-bottom: 4px;' name='y_max' id='y_max' type='text' value='".$y_max."' >\n";

                        echo "</div>\n";

                    echo "</div>\n";

                    echo "<div style='float:left;width:120px;margin-left:10px;margin-top:37px;'>\n";

                        // Bouton pour ajuster l'échelle
                        echo "<button id='ajustCoord' class='zoom_graph' style='width:100%;'>";
                            echo htmlaccent('Ajuster l\'échelle');
                        echo "</button>\n";

                    echo "</div>\n";


                echo "<hr>\n";
                echo "</div>\n";	
                
            echo "<hr>\n";
            echo "</div>\n";  
            */


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

    // ---------------------------------------------
    // Procédure Ajax pour mise à jour du graphique
    // ---------------------------------------------

    // Paramétrage général
    idUser = <?php echo $id_user;?>;
    todayTimeFormatted = '<?php echo $today_time_formatted;?>';
    msgInfo = document.getElementById('contenu_info');

    // Bouton des popups    
    boxTabWait = document.getElementById('wait_tab');
    boxTab = document.getElementById('cadre_data_station_lgt');
    boxGraphWait = document.getElementById('wait_graph');
    boxPlot = document.getElementById('plot');

    boxPts = document.getElementById('box_elt_pts');

    buttonNew = document.getElementById('button_newChron'); 
    boxNew = document.getElementById('box_elt_new');
    bValidNewETL = document.getElementById('new_etl'); 

    buttonModif = document.getElementById('button_modif'); 
    boxModif = document.getElementById('box_elt_modif');
    bValidModifETL = document.getElementById('modif_etl'); 

    buttonDuplic = document.getElementById('button_visu'); 
    boxDuplic = document.getElementById('box_elt_duplic');
    bValidDuplicETL = document.getElementById('duplic_etl');

    buttonDel = document.getElementById('button_del'); 
    boxDel = document.getElementById('box_elt_delete');    
    bValidDelETL = document.getElementById('del_etl');
    
    // Actions sur les boutons de modification / Création / Duplication des ETL 
    if(boxPlot)
    {
        buttonNew.addEventListener('click', function()
        {
            boxNew.style.display='block';
        });

        buttonModif.addEventListener('click', function(){
            boxModif.style.display='block';

            checkedValues = getCheckedValues();
            updateSelectBox('modif_ref_etl',checkedValues,modifDateDebut,modifDateFin,modifHeureDebut,modifHeureFin);

        });
        buttonDuplic.addEventListener('click', function(){
            boxDuplic.style.display='block';

            checkedValues = getCheckedValues();
            updateSelectBox('duplic_ref_etl',checkedValues,duplicDateDebut,duplicDateFin,duplicHeureDebut,duplicHeureFin);

        });
        
        buttonDel.addEventListener('click', function(){
            boxDel.style.display='block';

            checkedValues = getCheckedValues();
            updateSelectBox('del_ref_etl',checkedValues);
        });

        // --------------------------
        // Actions lors de la soumission des boutons de validation 

        if(bValidNewETL)
        {    
            bValidNewETL.addEventListener('click', function(){        
                actionETL('new',newDateDebut,newDateFin,newHeureDebut,newHeureFin);
                // Les id newDateDebut,newDateFin,newHeureDebut,newHeureFin sont définies dans block_etl_new.php
            });
        }
        bValidModifETL.addEventListener('click', function(){        
            actionETL('modif',modifDateDebut,modifDateFin,modifHeureDebut,modifHeureFin);
            // Les id modifDateDebut,modifDateFin,modifHeureDebut,modifHeureFin sont définies dans block_etl_modif.php
        });
        bValidDuplicETL.addEventListener('click', function(){        
            actionETL('duplic',duplicDateDebut,duplicDateFin,duplicHeureDebut,duplicHeureFin);
            // Les id duplicDateDebut,duplicDateFin,duplicHeureDebut,duplicHeureFin sont définies dans block_etl_duplic.php
        });
        bValidDelETL.addEventListener('click', function(){actionETL('del');});
    }

    xMin = document.getElementById('x_min');
    xMax = document.getElementById('x_max');
    yMin = document.getElementById('y_min');
    yMax = document.getElementById('y_max');

    var ETL_array = {};

    // Actions liées à l'affichage du tableau des ETL et du graphique des ETL

    idStation = <?php echo $st_id; ?>;
    
    // Lancement de la génération de graph
    function load_tab()
    {
        boxTab.style.display = 'none';
        boxTabWait.style.display = 'block';

        // Mise au format JSON des données
        // Créer un objet contenant les données à envoyer
        var dataToSend = {
            idStation: idStation
        };

        // Convertir l'objet en JSON
        var jsonDataTab = JSON.stringify(dataToSend);
        

        // Effectuer une requête AJAX asynchrone
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "include/structure/etl/process_etl_tab.php", true);
        xhr.setRequestHeader("Content-Type", "application/json");

        xhr.onreadystatechange = function() 
        {
            if (xhr.readyState === 4 && xhr.status === 200) 
            {
                boxTab.style.display = 'block';
                boxTabWait.style.display = 'none';
                
                // Analyser la réponse JSON
                var jsonResponse = JSON.parse(xhr.responseText);
                
                // Accéder aux données récupéré coté serveur
                boxTab.innerHTML = jsonResponse['html_text'];
                ETL_array = jsonResponse['ETL_array'];
                
                if(ETL_array && Object.keys(ETL_array).length > 0)
                {
                    bSelectAll = document.querySelector('.selectAll');
                    bSelectAll.addEventListener('click', function() {
                        // Récupérer toutes les cases à cocher de la classe 'check_ETL[]'
                        let checkboxes = document.querySelectorAll('input[name="check_ETL[]"]');

                        // Vérifier si toutes les cases sont déjà cochées
                        let allChecked = true;
                        checkboxes.forEach(function(checkbox) {if (!checkbox.checked) {allChecked = false;}});

                        // Si toutes les cases sont cochées, on les décochera, sinon on les cochera
                        checkboxes.forEach(function(checkbox) {checkbox.checked = !allChecked;});
                        load_graph(); 
                    });
                                        
                    buttonModif.style.display='block';
                    buttonDuplic.style.display='block';
                    buttonDel.style.display='block';
                }
                else
                {
                    buttonModif.style.display='none';
                    buttonDuplic.style.display='none';
                    buttonDel.style.display='none';
                }                

                load_graph(); // On lance l'édition du graph quand la liste des ETL est affichée
            }
        };

        // Envoyer les données JSON au serveur
        xhr.send(jsonDataTab);        
    }

    // Lancement de la génération de graph
    function load_graph()
    {
        var check_ETL = document.getElementsByName('check_ETL[]');
        var tabIdEtl = []; // Tableau pour stocker les valeurs sélectionnées

        // Parcourir toutes les ETL sélectionnés
        for (var i = 0; i < check_ETL.length; i++) 
        {
            // Vérifier si la case est cochée
            if (check_ETL[i].checked) {
                tabIdEtl.push(check_ETL[i].value); // Ajouter la valeur au tableau
            }
        }

        boxNew.style.display='none';
        boxModif.style.display='none';
        boxDuplic.style.display='none';
        boxDel.style.display='none';
        
        boxPlot.style.display = 'none';
        boxGraphWait.style.display = 'block';


        // Mise au format JSON des données
        // Créer un objet contenant les données à envoyer
        var dataToSend = {
            tabIdEtl: tabIdEtl,
            xMin: parseInt(xMin.value),
            xMax: parseInt(xMax.value),
            yMin: parseInt(yMin.value),
            yMax: parseInt(yMax.value),
            idStation: <?php echo $st_id; ?>
        };

        // Convertir l'objet en JSON
        var jsonDataGraph = JSON.stringify(dataToSend);
        
        // Effectuer une requête AJAX asynchrone
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "include/structure/etl/process_etl_graph.php", true);
        xhr.setRequestHeader("Content-Type", "application/json");

        xhr.onreadystatechange = function() 
        {
            if (xhr.readyState === 4 && xhr.status === 200) 
            {
                boxPlot.style.display = 'block';
                boxGraphWait.style.display = 'none';
                
                // Analyser la réponse JSON
                var jsonResponse = JSON.parse(xhr.responseText);
                
                xMin.value = jsonResponse['min_h'].toFixed(1);
                xMax.value = jsonResponse['max_h'].toFixed(1);
                yMin.value = jsonResponse['min_q'].toFixed(1);
                yMax.value = jsonResponse['max_q'].toFixed(1);

                // Accéder aux données récupéré coté serveur
                eval(jsonResponse['js_text']); // on récupère le script généré coté serveur pour afficher les graphiques
            }
        };

        // Envoyer les données JSON au serveur
        xhr.send(jsonDataGraph);
        
    }

    //load_tab();

    // ---------------------------------------------
    // Actions sur le graphique
    // ---------------------------------------------

    function actionPtsGraph()
    {
        var divPlot = document.getElementById('plot');

        var etlTrace = document.getElementById('etl_trace');
        var xPts = document.getElementById('etl_hauteur');
        var yPts = document.getElementById('elt_debit');

        // Gestionnaire d'événements pour les clics sur les points
        divPlot.on('plotly_click', function(eventData) 
        {
            boxPts.style.display = 'block';

            // Vérifier si un point a été cliqué
            if (eventData && eventData.points && eventData.points.length > 0) 
            {
                // Récupérer les coordonnées du point cliqué
                const point = eventData.points[0]; // Prend le premier point (en cas de chevauchement)
                const xValue = point.x; // Coordonnée X du point
                const yValue = point.y; // Coordonnée Y du point
                const traceName = point.data.name; // Nom de la courbe
                const traceIndex = point.curveNumber; // Index de la trace
                const pointIndex = point.pointIndex; // Index du point cliqué dans la trace

                // Obtenir les données actuelles de la courbe
                const traceData = point.data;

                // Récupérer la couleur directement depuis les données de l'événement
                
                let pointColor;
                if (point.fullData.marker && Array.isArray(point.fullData.marker.color)) 
                {
                    pointColor = point.fullData.marker.color[point.pointIndex]; // Couleur définie pour le point
                } else 
                {
                    pointColor = point.fullData.line.color;
                }

                if(pointColor != 'red')
                {    
                    const totalPoints = point.fullData.x.length; // Nombre total de points dans la courbe
                    const colors = Array(totalPoints).fill(pointColor); // Construire un tableau de couleurs avec la couleur d'origine

                    // Modifier uniquement la couleur du point cliqué
                    colors[pointIndex] = 'red'; // Nouvelle couleur pour le point cliqué


                    // Mettre à jour uniquement la couleur du point cliqué
                    Plotly.restyle('plot', { 'marker.color': [colors] }, [traceIndex]);
                }

                xPts.value = xValue;
                yPts.value = yValue;
                etlTrace.value = traceName;

            }
        });
    }


    // Définissez la fonction pour le gestionnaire d'événements
    var xValues; // Déclarer xValues en dehors de la fonction
    var yValues; // Déclarer yValues en dehors de la fonction
    var idsValues; // Déclarer id_values (id_data) en dehors de la fonction
    var pointIndex; // Déclarer pointIndex en dehors de la fonction
    var id_etl_encours; // Déclarer id_etl_encours en dehors de la fonction

    // pas encore utilisé
    function updateDataAndGraph(new_x, new_y) 
    {
        var ids_Value = idsValues[pointIndex];

        // Mettre à jour les données avec les nouvelles coordonnées
        if (!isNaN(new_x) && !isNaN(new_y)) 
        {
            xValues[pointIndex] = new_x;
            yValues[pointIndex] = new_y;

            // Modification dans le tableau
            var inputHauteurKey = "input_hauteur_" + id_etl_encours + "_" + ids_Value; 
            document.getElementById(inputHauteurKey).value = new_x;

            var inputDebitKey = "input_debit_" + id_etl_encours + "_" + ids_Value; 
            document.getElementById(inputDebitKey).value = new_y;

            // Mettre à jour le graphique avec les nouvelles données
            Plotly.update('plot', { x: [xValues], y: [yValues] });
            Plotly.update('cadre_limit', { x: [xValues], y: [yValues] });
        }
    }

    // Fonction pour récupérer les valeurs cochées dans la liste des ETL
    function getCheckedValues() 
    {
        // Sélectionner toutes les cases à cocher avec le nom 'check_ETL[]'
        const checkboxes = document.querySelectorAll("input[name='check_ETL[]']:checked");
        
        // Récupérer les valeurs des cases cochées
        const values = Array.from(checkboxes).map(checkbox => checkbox.value);
        
        // Afficher ou retourner les valeurs
        return values;
    }

    // Fonction pour mettre à jour les listes déroulantes de choix des ETL dans les popup d'action
    function updateSelectBox(selectBoxName,values,boxDateDebut='',boxDateFin='',boxHeureDebut='',boxHeureFin='') 
    {
        // Récupérer l'élément <select> par son nom
        const selectBox = document.querySelector(`select[name='${selectBoxName}']`);

        // Vider les options existantes dans la liste déroulante
        selectBox.innerHTML = "";

        idETLFirst = 0;
        firstETL = true;

        // Ajouter chaque valeur comme une option
        values.forEach(value => {
            idETL = value.split('_')[0]; // Extraire la partie 'id' avant '_'
            if(firstETL){idETLNew = idETL;firstETL=false;}
            
            numETL = value.split('_')[1]; // Extraire la partie 'id' après '_'
            option = document.createElement("option"); // Créer un élément <option>
            option.value = value;                           // Définir la valeur de l'option
            // Définir le texte affiché de l'option
            option.textContent = `ETL-${numETL} : ${ETL_array[idETL].datetime_first} → ${ETL_array[idETL].datetime_end}`;
            
            selectBox.appendChild(option);                 // Ajouter l'option au <select>
        });

        if(selectBoxName != 'del_ref_etl')
        {    
            boxDateDebut.value = ETL_array[idETLNew].datetime_first.split(' ')[0];
            boxHeureDebut.value = ETL_array[idETLNew].datetime_first.split(' ')[1];
            boxDateFin.value = ETL_array[idETLNew].datetime_end.split(' ')[0];
            boxHeureFin.value = ETL_array[idETLNew].datetime_end.split(' ')[1];
        }
    }


    // Fonction d'action sur les ETL 
    function actionETL(action,date1Input=null,date2Input=null,heure1Input=null,heure2Input=null)     
    {         
        actionLoad=false;

        idEtlAction = 0;
        numEtlAction = 0;

        if(action == 'new')
        {
            linkProcess = "include/structure/etl/process_etl_new.php";
            origineH0 = document.getElementById('origine_h0');
            
            if(isValidDatesInput(date1Input,date2Input,heure1Input,heure2Input))
            {
                let bornesTab = densitePts();

                if(!bornesTab)
                {
                    actionLoad=false;
                    msgInfo.style.border = '4px solid #930000'; // bordure en rouge
                }
                else
                {
                    var dataToSend = 
                    {
                        idUser: idUser,
                        todayTimeFormatted: todayTimeFormatted,
                        date1: date1Input.value,
                        date2: date2Input.value,
                        heure1: heure1Input.value,
                        heure2: heure2Input.value,
                        origineH0: origineH0.value,
                        bornesTab: bornesTab,
                        idStation: idStation
                    };
                    actionLoad=true; 
                }
            }
            else{msgInfo.style.border = '4px solid #930000'; }// bordure en rouge
        }

        if(action == 'modif')
        {
            linkProcess = "include/structure/etl/process_etl_modif.php";
            idEtlTemp = document.getElementById('modif_ref_etl').value;
            idEtlAction = idEtlTemp.split('_')[0];
            numEtlAction = idEtlTemp.split('_')[1];

            if(isValidDatesInput(date1Input,date2Input,heure1Input,heure2Input))
            {
                var dataToSend = 
                {
                    idUser: idUser,
                    todayTimeFormatted: todayTimeFormatted,
                    idEtl: idEtlAction,
                    numEtl: numEtlAction,
                    date1: date1Input.value,
                    date2: date2Input.value,
                    heure1: heure1Input.value,
                    heure2: heure2Input.value,
                    idStation: idStation
                };
                actionLoad=true;
            }
            else{msgInfo.style.border = '4px solid #930000'; }// bordure en rouge
        }


        if(action == 'duplic')
        {
            linkProcess = "include/structure/etl/process_etl_duplic.php";
            idEtlTemp = document.getElementById('duplic_ref_etl').value;
            idEtlAction = idEtlTemp.split('_')[0];
            numEtlAction = idEtlTemp.split('_')[1];

            if(isValidDatesInput(date1Input,date2Input,heure1Input,heure2Input))
            {
                var dataToSend = 
                {
                    idUser: idUser,
                    todayTimeFormatted: todayTimeFormatted,
                    idEtl: idEtlAction,
                    numEtl: numEtlAction,
                    date1: date1Input.value,
                    date2: date2Input.value,
                    heure1: heure1Input.value,
                    heure2: heure2Input.value,
                    idStation: idStation
                };
                actionLoad=true;
            }
            else{msgInfo.style.border = '4px solid #930000'; }// bordure en rouge
        }
        
        if(action == 'del')   
        {   
            linkProcess = "include/structure/etl/process_etl_delete.php";
            idEtlTemp = document.getElementById('del_ref_etl').value;
            idEtlAction = idEtlTemp.split('_')[0];
            numEtlAction = idEtlTemp.split('_')[1];

            // Mise au format JSON des données
            // Créer un objet contenant les données à envoyer
            var dataToSend = 
            {
                idUser: idUser,
                todayTimeFormatted: todayTimeFormatted,
                idEtl: idEtlAction,
                numEtl: numEtlAction,
                idStation: idStation
            };
            actionLoad=true;
        }


        if(actionLoad)
        {
            // Convertir l'objet en JSON
            var jsonData = JSON.stringify(dataToSend);

            // Effectuer une requête AJAX asynchrone
            var xhr = new XMLHttpRequest();
            xhr.open("POST", linkProcess, true);
            xhr.setRequestHeader("Content-Type", "application/json");

            xhr.onreadystatechange = function() 
            {
                if (xhr.readyState === 4 && xhr.status === 200) 
                {
                    boxPlot.style.display = 'block';
                    boxGraphWait.style.display = 'none';
                    
                    // Analyser la réponse JSON
                    var jsonResponse = JSON.parse(xhr.responseText);

                    msgInfo.innerText = jsonResponse['js_text'];
                    msgInfo.style.display = 'block';                    

                    validProcess = jsonResponse['valid_process'];

                    if(validProcess)
                    {
                        msgInfo.style.border = '4px solid #09886d'; // bordure en vert
                        load_tab();
                    }
                    else
                    {
                        msgInfo.style.border = '4px solid #930000'; // bordure en rouge
                    }
                }
            };

            // Envoyer les données JSON au serveur
            xhr.send(jsonData);
        }
    }


    // Fonction de validation des champs des différents formulaires de construction ou de modification d'un ETL

    function densitePts()
    {
        bornes = []; // Tableau pour stocker les bornes
        inputError = false;

        for (i = 1; i <= 4; i++) 
        {
            // Récupérer les champs pour chaque ligne
            const inf = document.getElementById(`inf_${i}`);
            const sup = document.getElementById(`sup_${i}`);
            const interv = document.getElementById(`interv_${i}`);

            // Récupérer les valeurs des champs, trim pour éviter les espaces inutiles
            const infValue = inf.value.trim();
            const supValue = sup.value.trim();
            const intervValue = interv.value.trim();
            
            // Si tous les champs sont vides, on ignore la ligne
            if (infValue === "" && supValue === "" && intervValue === ""){continue;}

            // Si au moins un des champs est rempli, tous doivent être remplis
            if (infValue === "" || supValue === "" || intervValue === "") {
                msgInfo.innerText += "Erreur : Tous les champs de la ligne ${i} (inf, sup, interv) doivent être remplis.\n";
                inputError = true;
                break;
            }

            // Vérifier si les valeurs sont bien des entiers
            if (!isInteger(infValue) || !isInteger(supValue) || !isInteger(intervValue)) 
            {
                msgInfo.innerText += "Erreur : Les champs de la ligne ${i} doivent contenir uniquement des nombres entiers.\n";
                inputError = true;
                break;
            }

            // Convertir les valeurs en entier pour les comparaisons
            const infInt = parseInt(infValue, 10);
            const supInt = parseInt(supValue, 10);
            const intervInt = parseInt(intervValue, 10);

            // Vérification 1 : supValue doit être strictement supérieur à infValue sur la même ligne
            if (supInt <= infInt) 
            {
                msgInfo.innerText += "Erreur : Sur la ligne ${i}, la borne supérieure (sup) doit être strictement supérieure à la borne inférieure (inf).\n";
                inputError = true;
                break;
            }

            // Vérification 2 : infValue doit être supérieur à supValue de la ligne précédente (à partir de la ligne 2)
            if (i > 1 && prevSupValue !== null && infInt <= prevSupValue) 
            {
                msgInfo.innerText += "Erreur : Sur la ligne ${i}, la borne inférieure (inf) doit être strictement supérieure à la borne supérieure (sup) de la ligne précédente.\n";
                inputError = true;
                break;
            }

            // Ajouter les valeurs au tableau des bornes
            bornes.push({
                            inf: infInt,
                            sup: supInt,
                            interv: intervInt,
                        });            
                        
            
            // Mettre à jour la valeur de supValue pour la ligne suivante
            prevSupValue = supInt;
        
        }

        // Si une erreur a été détectée, arrêter ici
        if (inputError) {return;}

        return bornes;
    }

    // Fonction pour vérifier si une valeur est un entier
    function isInteger(value) {return Number.isInteger(Number(value));}

    function isValidDatesInput(date1Input,date2Input,heure1Input,heure2Input)
    {   
        // Vérifier si les dates sont valides
        if (isValidDate(date1Input.value) && isValidDate(date2Input.value))
        {
            // Vérifier si les heures sont valides
            if (isValidTime(heure1Input.value) && isValidTime(heure2Input.value)) 
            {
                // Convertir dates et heures en objets Date complets
                const date1Format = parseDate(date1Input.value); // Obtenez un objet Date à partir de la date
                const [hour1, minute1, second1] = parseTime(heure1Input.value); // Extraire l'heure
                date1Format.setHours(hour1, minute1, second1); // Ajouter l'heure à la date
                
                const date2Format = parseDate(date2Input.value); // Obtenez un objet Date à partir de la date
                const [hour2, minute2, second2] = parseTime(heure2Input.value); // Extraire l'heure
                date2Format.setHours(hour2, minute2, second2); // Ajouter l'heure à la date

                // Comparer les deux dates complètes
                if (date1Format < date2Format) 
                {
                    return true;
                } 
                else 
                {
                    msgInfo.innerText = "'Date et Heure de début' doivent être antérieures à 'Date et Heure de fin'";
                    msgInfo.style.display = 'block';

                    return false;
                }
            } 
            else 
            {
                msgInfo.innerText = "Au moins l'une des heures saisies est invalide ou dans un mauvais format (HH:MM ou HH:MM:SS : formats valides)";
                msgInfo.style.display = 'block';

                return false;
            }
        } 
        else 
        {
            msgInfo.innerText = "Au moins l'une des dates saisies est invalide ou dans un mauvais format (dd-mm-yyyy : format valide)";
            msgInfo.style.display = 'block';

            return false;
        }  
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

    // Fonction pour valider et normaliser une heure
    function isValidTime(timeString) 
    {
        // Vérifier le format avec une regex (HH:MM ou HH:MM:SS)
        const timeRegex = /^([01]\d|2[0-3]):([0-5]\d)(:[0-5]\d)?$/;
        if (!timeRegex.test(timeString)) 
        {
            return false; // Format invalide
        }

        // Découper les composants de l'heure
        const [hours, minutes, seconds] = timeString.split(":").map(Number);

        // Vérifier les limites des heures, minutes et secondes
        if (
            hours < 0 || hours > 23 ||
            minutes < 0 || minutes > 59 ||
            (seconds !== undefined && (seconds < 0 || seconds > 59))
        ) 
        {
            return false; // Valeur hors des limites
        }

        // Ajouter "00" pour les secondes si elles ne sont pas présentes
        const formattedTime = seconds !== undefined
            ? `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`
            : `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:00`;

        return formattedTime;
    }


    // Fonction pour convertir une date (format valide) en objet Date
    function parseDate(dateString) 
    {
        [day, month, year] = dateString.split("-").map(Number);
        return new Date(year, month - 1, day);
    }

    // Fonction pour analyser une heure au format HH:MM:SS ou HH:MM
    function parseTime(timeString) 
    {
        const [hours, minutes, seconds = 0] = timeString.split(":").map(Number);
        return [hours, minutes, seconds];
    }

    

</script>

