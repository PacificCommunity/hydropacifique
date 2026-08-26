<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Diagraphies comparées - Affiché sur un graphique
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

$row_l="class='row1' onclick=\"this.className='rowSelect';\"";
$print_row = '';   

$titre_graph = "";
$data_graph_all = ""; // Variables données pour les graphiques 


// --------------------------------------
$list_stations = '';
echo 'cdklqhd';

    
    // Vérifiez si des cases à cocher ont été soumises
    if (isset($_POST['check_station_diac']) && is_array($_POST['check_station_diac'])) 
    {echo 'mldshf';
        // Récupérez les valeurs des cases cochées
        $list_stations .= $_POST['check_station_diac'].',';
    }
$list_stations = trim($list_stations);
echo $list_stations;


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
            
            echo "<h1 id='h1_graph'>";
                            
                echo "<span>".htmlaccent('Diagraphies comparées')."</span>";
                
            echo "</h1>";
            
            
            // Colonne de gauche pour afficher la liste des Diagraphies sélectionnées classées et affichés par station
            echo "<div id='cadre_graph' style='float:left;width:24%;'>\n";

                echo "<div id='boxpopup' class='select-top' style='width:96%;margin:0px;padding:10px 1%;'>\n";
                    
                    echo "<p style='margin-left:1%;'>";
                        echo "<span style='font-weight: bold;font-size:13px;'>".htmlaccent('Liste des Stations')."</span>";
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

            
            // Block Graphique
            echo "<div id='cadre_graph' style='float:left;width:75%;margin-left:0.5%;height:75vh;overflow-y: auto;'>\n";
                
                echo "<div id='boxpopup' class='select' style='width:99%;margin:0;padding:0;border:1px solid #000;'>\n";

                    echo "<p class='titre' style='height:5px;'></p>";

                    // Cadre Graph
                    echo "<div id='plot' class='graph' style='height:50vh;margin:0 5px;display:none;'></div>\n";
                    
                    echo "<div id='wait_graph' style='width:100%;height:65px;text-align:center;'>";
                        echo "<img src='".DIR_WS_IMG."wait.gif' style='width:50px;'>";
                        echo "<p>".htmlaccent('Chargement en cours ...')."</p>";
                    echo "</div>\n"; 

                echo "<hr>\n";
                echo "</div>\n";	

                
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

    // ---------------------------------------------
    // Procédure Ajax pour mise à jour du graphique
    // ---------------------------------------------

    // Paramétrage général
    idUser = <?php echo $id_user;?>;

    // Bouton des popups    
    boxTabWait = document.getElementById('wait_tab');
    boxTab = document.getElementById('cadre_data_station_lgt');
    boxGraphWait = document.getElementById('wait_graph');
    boxPlot = document.getElementById('plot');
    
    
    // Actions liées à l'affichage du tableau des diagraphies à afficher et du graphique des diagraphies
    
    // Lancement de la génération de graph
    function load_tab()
    {
        boxTab.style.display = 'none';
        boxTabWait.style.display = 'block';

        // ETAPE 1 : Récupérer tous les checkboxes sélectionnés
		var listStation = '<?php echo $list_stations;?>';

        console.log(listStation);
        // Mise au format JSON des données
        // Créer un objet contenant les données à envoyer
        var dataToSend = {
                            listStation: listStation
                        };


        // Convertir l'objet en JSON
        var jsonDataTab = JSON.stringify(dataToSend);

        // Effectuer une requête AJAX asynchrone
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "include/structure/diac/process_diag_tab.php", true);
        xhr.setRequestHeader("Content-Type", "application/json");

        xhr.onreadystatechange = function() 
        {
            if (xhr.readyState === 4 && xhr.status === 200) 
            {
                
                
                // Analyser la réponse JSON
                var jsonResponse = JSON.parse(xhr.responseText);
                
                // Accéder aux données récupéré coté serveur
                boxTab.innerHTML = jsonResponse['html_text'];

                boxTab.style.display = 'block';
                boxTabWait.style.display = 'none';
                /*
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
                    */               

                //load_graph(); // On lance l'édition du graph quand la liste des ETL est affichée
            }
        };

        // Envoyer les données JSON au serveur
        xhr.send(jsonDataTab);        
    }

    // Lancement de la génération de graph
    function load_graph()
    {
        /*
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
            dateToday: todayFrDateFormatted,
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

                newDateFirst.value = jsonResponse['date_first'];

                // Accéder aux données récupéré coté serveur
                eval(jsonResponse['js_text']); // on récupère le script généré coté serveur pour afficher les graphiques
            }
        };

        // Envoyer les données JSON au serveur
        xhr.send(jsonDataGraph);

        */
        
    }

    load_tab();

    // Fonction pour récupérer les valeurs cochées dans la liste des Diagraphie
    function getCheckedValues() 
    {
        // Sélectionner toutes les cases à cocher avec le nom 'check_ETL[]'
        const checkboxes = document.querySelectorAll("input[name='check_ETL[]']:checked");
        
        // Récupérer les valeurs des cases cochées
        const values = Array.from(checkboxes).map(checkbox => checkbox.value);
        
        // Afficher ou retourner les valeurs
        return values;
    }



    

</script>

