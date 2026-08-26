<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Formulaire pour sélection des fichiers à importer
----------------------------------------
*/

// Création d'un identification unique de l'exportation

$timestamp = time(); // Obtenir le timestamp Unix actuel
$id_import = date('YmdHis', $timestamp); // Formater le timestamp pour obtenir 'yyyymmjjhhmmss'
$id_import = 'ID_' . $id_import; // Option : Ajouter un préfixe ou suffixe pour garantir l'unicité et éviter les interprétations comme date


// --------------------------------------
// Debut page HTML

require(DIR_WS_STRUCTURE . 'header_web.php');


echo "<body>";

require(DIR_WS_STRUCTURE . 'block_import_info.php'); // Block pour affichage des consignes pour l'import des fichiers
require(DIR_WS_STRUCTURE . 'header.php'); // Bando Haut
include(DIR_WS_BOX . 'nav_accueil.php'); // Menu

echo "<div id='contour_general'>";
	
	if(tep_not_null($message_info)){echo "<div id='contenu_info'>".$message_info."</div>";}
	
	echo "<div id='contenu_centre'>";
		
		echo "<div id='contenu_box2'>";

            echo "<h1>";
                            
                echo "<span>".htmlaccent('Importation de données - Etape 1 : Sélection des fichiers')."</span>";
                
            echo "</h1>";


            echo "<div style='float:left;width:32%;height:80vh;overflow-y: auto;' >\n";

                $lien_form = tep_href_link('import.php');			
                //echo "<form name='select_import_step1' action='import.php' method='post' enctype='multipart/form-data'>";
                        
                    echo "<div id='boxpopup' class='select-top' style='width:92%;padding:10px;'>\n";
                        
                        echo "<input type='file' id='fileInput' name='fileInput[]' multiple style='width:90%;'>"; 	

                        echo "<p style='margin-top:10px;'>";
                            echo "<input type='checkbox' name='entete' id='entete' style='width:30px;' >";	
                            echo "<span style='font-size:13px;font-weight:bold;'>".htmlaccent('En-tête de colonne')."</span>";
                        echo "</p>\n";
                    
                    echo "</div>\n";

                    echo "<hr>\n";
                        
                    echo "<a style='float:left;cursor:pointer;' onClick='afficheBlockInfo();'>";
                            echo htmlaccent('-> Instruction pour l\'importation <-');
                    echo "</a>\n";      
                    
                    // Afifchage de la liste des fichiers sélectionnées
                    echo "<div id='boxpopup' class='select' style='width:92%;border:1px solid #000;'>\n";
                    
                        echo "<p>";
                            echo "<span style='font-weight: bold;'>".htmlaccent('Processus de traitement')."</span>";
                        echo "</p>";
                        echo "<textarea id='fileListInfo' style='width:98%;height:30vh;'></textarea>";

                    echo "</div>\n";		

                    echo "<hr>\n";

                    echo "<div id='loadWaitImg1' style='float:right; width:50%;margin-right:6%;display:none;'>\n";
                        echo "<p style='float:right;margin-left:10px;'>".htmlaccent('Téléversement des fichiers - Veuillez patienter ...')."</p>";
                        echo "<img src='".DIR_WS_IMG."hp100.gif' style='float:right;width:100px;'>";
                    echo "</div>\n";	
                    echo "<input type='submit' class='button' name='uploadButton' id='uploadButton' value='".htmlaccent('Charger les fichiers')."' style='float:right;margin-right:6%;display:none;'/>";
                    
                //echo "</form >\n";

            echo "<hr>\n";
            echo "</div>\n";

            // Colonne de droite - Liste des chroniques disponibles à l'importation à partir des fichiers sélectionnés

            echo "<div style='float:left;width:50%;margin-left:1%;padding-right:2%;height:70vh;overflow-y: auto;' >\n";

                echo "<table id='table_import_chron' cellspacing='0' >\n";
                                                    
                    echo "<thead>\n";
                        echo "<tr>\n";
                            echo "<th style='width:150px;'>".htmlaccent('Fichier')."</th>\n";  
                            echo "<th style='width:280px;'>".htmlaccent('Station')."</th>\n";  
                            echo "<th style='width:100px;text-align:center;'>".htmlaccent('Chronique')."</th>\n";                             
                            echo "<th style='width:100px;text-align:center;'>".htmlaccent('Unité')."</th>\n";  									
                            echo "<th style='width:100px;font-size:12px;color:#000;text-align:center;border:none;cursor:pointer;' onclick=\"toggleCheckboxes();\">";
                                echo "<span class='selectAll'>".htmlaccent('Select +/-')."</span>";
                            echo "</th>\n";
                            echo "<th style='width:40px;text-align:center;border:none;'>&nbsp;</th>\n"; 
                            echo "<th style='width:40px;text-align:center;border:none;'>&nbsp;</th>\n"; 
                            echo "<th style='width:40px;text-align:center;border:none;'>&nbsp;</th>\n"; 
                        echo "</tr>\n";
                    echo "</thead>\n";
                    
                    //echo "<tr><td colspan='8' style='height:10px;'></td></tr>\n"; // Ligne vide sans contenu

                echo "</table>\n";

                echo "<div id='loadWaitImg2' style='float:right; width:35%;margin-top:25px;display:none;'>\n";
                        echo "<p style='float:right;margin-left:10px;'>".htmlaccent('Enregistrement des données - Veuillez patienter ...')."</p>";
                        echo "<img src='".DIR_WS_IMG."hp100.gif' style='float:right;width:100px;'>";
                    echo "</div>\n";
                echo "<input type='submit' class='button' name='loadDataButton' id='loadDataButton' value='".htmlaccent('Importer les données')."' style='float:right;margin-top:25px;display:none;'/>";

            echo "<hr>\n";
            echo "</div>\n";


            echo "<div style='float:left;width:99%;margin-top:0px;' >\n";

                
                          
            echo "<hr>\n";
            echo "</div>\n";

        echo "<hr>\n";
		echo "</div>\n";
	
	echo "<hr>";
	echo "</div>";
	
echo "<hr>";
echo "</div>";
	
require('include/application_bottom.php'); 
	
echo "</body>";

echo "</html>";
									



?>

<script>

    // Sélectionnez le champ d'entrée de fichier et le bouton de téléversement
    var numLoadData = 0;
    var tabLoadData = [];

    const fileInput = document.getElementById('fileInput');
    const uploadButton = document.getElementById('uploadButton');
    const loadDataButton = document.getElementById('loadDataButton');
    const loadWaitImg1 = document.getElementById('loadWaitImg1');
    const loadWaitImg2 = document.getElementById('loadWaitImg2');

    const checkedValuesChron = []; // Créer un tableau pour stocker les valeurs des cases cochées (Chronique à sélectionner pour l'import)

    const fileListInfo = document.getElementById('fileListInfo');
    const table_import_chron = document.getElementById("table_import_chron");

    const jsonMeta = {
                        id_user: '<?php echo $id_user; ?>',
                        id_import: '<?php echo $id_import; ?>'
                    };
    const jsonMetaString = JSON.stringify(jsonMeta); // Nécessaire pour Sérialiser jsonMeta en chaîne JSON


    // Première étape, vérifier les fichiers qui ont été sélectionnés. 
    fileInput.addEventListener('change', function(e) {
        
        var fileList = e.target.files;
        var fileText = 'Liste des fichiers sélectionnés\n\n';

        uploadButton.style.display = 'block';

        for (var i = 0; i < fileList.length; i++) 
        {
            fileSize = fileList[i].size;
            fileSizeInKB = fileSize / 1024;        
            fileSizeInMB = fileSizeInKB / 1024;
            //fileNames += fileList[i].name + ' - ' + Math.floor(fileSizeInMB,0).toLocaleString() + ' Mo \n';
            fileText += fileList[i].name + ' - ' + Math.floor(fileSizeInKB,0).toLocaleString() + ' Ko \n';       
        }
        fileText += '\n--\n\n';
        
        fileListInfo.value = fileText;
        fileListInfo.scrollTop = fileListInfo.scrollHeight; // Descendre automatiquement le scroll du textarea jusqu'en bas

        fileListInfo.readOnly = true;
    });


    // ---------------------------------------------------------
    // Téléversement des fichiers un par un avec traiment coté serveur 1 fichier à la fois


    // Attacher l'événement de clic au bouton de téléversement


    // Fonction pour téléverser un fichier avec Procédure AJAX pour lecture des fichiers côté serveur
    function uploadFile(file, callback) 
    {
        uploadButton.style.display='none'; 
        loadWaitImg1.style.display='block'; 
        
        const formData = new FormData();
        formData.append('file', file); // Ajouter le fichier au FormData

        // Ajouter des données supplémentaires au FormData
        formData.append('meta', jsonMetaString); // Ajouter les champs supplémentaires


        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'include/structure/import/load_file.php', true);

        xhr.onreadystatechange = function () 
        {
            if (xhr.readyState === 4) 
            {
                if (xhr.status === 200) 
                {
                    try 
                    {
                        var reponse = JSON.parse(xhr.responseText);
                        msg_info = reponse.msg_info;
                        tab_html = reponse.tab_html;

                        fileListInfo.value += msg_info;
                        fileListInfo.scrollTop = fileListInfo.scrollHeight; // Descendre automatiquement le scroll du textarea jusqu'en bas
                        
                        table_import_chron.insertAdjacentHTML('beforeend', tab_html); // Ajoute la ligne dans le tableau des fichiers importables

                        if (callback) callback(null, reponse); // Appeler le callback en cas de succès
                    } 
                    catch (e) 
                    {
                        fileListInfo.value += 'Erreur lors du parsing de la réponse: ';
                        fileListInfo.scrollTop = fileListInfo.scrollHeight; // Descendre automatiquement le scroll du textarea jusqu'en bas
                        if (callback) callback(e, null); // Appeler le callback en cas d'erreur de parsing
                    }
                } 
                else 
                {
                    const errorMsg = 'Erreur lors du téléversement: ' + xhr.status + ' ' + xhr.statusText;
                    fileListInfo.value += errorMsg;
                    fileListInfo.scrollTop = fileListInfo.scrollHeight; // Descendre automatiquement le scroll du textarea jusqu'en bas
                    if (callback) callback(new Error(errorMsg), null); // Appeler le callback en cas d'erreur
                }
            }
        };

        xhr.send(formData); // Envoyer le fichier
    }



    // Fonction pour téléverser les fichiers un par un
    function uploadFilesSequentially(files) 
    {
        let index = 0; // Index du fichier à téléverser

        // Fonction récursive pour téléverser les fichiers un par un
        function uploadNext() {
            if (index < files.length) { // S'il y a encore des fichiers à téléverser
                // Téléverser le fichier courant et appeler la fonction de rappel
                uploadFile(files[index], () => {
                    index++; // Passer au fichier suivant
                    uploadNext(); // Appeler récursivement pour téléverser le fichier suivant
                });
            } else {

                fileListInfo.value += '\n-- CHARGEMENT DES FICHIERS TERMINE --\n\n';
                fileListInfo.scrollTop = fileListInfo.scrollHeight;
                
                // Action à la fin des téléversement
                uploadButton.style.display='none';
                loadWaitImg1.style.display='none';
                loadDataButton.style.display='block';            

            }
        }

        uploadNext(); // Démarrer le téléversement du premier fichier
    }


    // Ecoute du boutton uploadButton qui permet de charger les fichiers sélectionnés et de vérifier s'ils sont conformes
    uploadButton.addEventListener('click', function() 
    {
        // nettoyer le tableau : table_import_chron = document.getElementById("table_import_chron");
        var tbodies = table_import_chron.getElementsByTagName("tbody"); // Obtenir tous les tbody
        for (var i = 0; i < tbodies.length; i++) 
        {
            var tbody = tbodies[i];
            while (tbody.firstChild) 
            {
                tbody.removeChild(tbody.firstChild); // Vider tous les enfants
            }
        }

        const files = fileInput.files; // Récupérer les fichiers sélectionnés
        if (files.length > 0) {
            uploadFilesSequentially(files); // Téléverser les fichiers un par un
            fileListInfo.value += '\n-- CHARGEMENT DES FICHIERS EN COURS --\n';
            fileListInfo.scrollTop = fileListInfo.scrollHeight;
        } else {
            fileListInfo.value += 'Aucun fichier sélectionné';
        }
    });

    // -----------------------------
    // LOAD DATA
    // Import des données à partir des fichiers sélectionnés. 


    function loadData()
    {
        // Pour le chargement des données du premier fichier : affichage du gif d'attente
        if(numLoadData == 0)
        {
            checkId = document.getElementById('check_'+tabLoadData[numLoadData]);
            waitId = document.getElementById('wait_'+tabLoadData[numLoadData]);
            if(checkId){waitId.style.display = 'block';}
        }
        
        var xhrLoadData = new XMLHttpRequest();
            
        xhrLoadData.open('POST', 'include/structure/import/load_data.php', true);

        xhrLoadData.setRequestHeader('Content-Type', 'application/json');

        xhrLoadData.onreadystatechange = function() 
        {
            if (xhrLoadData.readyState === 4 && xhrLoadData.status === 200) 
            {                  
                // Traitement de la réponse du serveur
                var reponse = JSON.parse(xhrLoadData.responseText);                                
                
                fileListInfo.value += reponse;            
                fileListInfo.scrollTop = fileListInfo.scrollHeight; // Descendre automatiquement le scroll jusqu'en bas

                checkId = document.getElementById('check_'+tabLoadData[numLoadData]);
                waitId = document.getElementById('wait_'+tabLoadData[numLoadData]);
                noteId = document.getElementById('note_'+tabLoadData[numLoadData]);            
                graphId = document.getElementById('graph_'+tabLoadData[numLoadData]);
                if(checkId)
                {
                    waitId.style.display = 'none';
                    checkId.style.display = 'block';
                    noteId.style.display = 'block';
                    graphId.style.display = 'block';
                }

                if(numLoadData < tabLoadData.length - 1) // Vérifier si numLoadData dépasse la longueur de la table contenant les id des chroniques à traiter
                {   
                    numLoadData++; // On incrémente pour passer à la chronique suivante    
                    waitId = document.getElementById('wait_'+tabLoadData[numLoadData]); 
                    if(waitId){waitId.style.display = 'block';}
                    
                    loadData(); // On lance le prochain traitement d'une chronique                               
                }
                else
                {
                    numLoadData=0; // permet de réinitialisé le comptage du tableau si on souhaite charger les données d'autres chroniques dans un second temps

                    loadDataButton.style.display='block'; 
                    loadWaitImg2.style.display='none'; // fin du chargement des data

                    fileListInfo.value += '\n\n-- ENREGISTREMENT DES DONNEES TERMINE --\n';
                    fileListInfo.scrollTop = fileListInfo.scrollHeight; // Descendre automatiquement le scroll jusqu'en bas
                
                } 
            }
        };

        // Envoyer la requête AJAX pour charger les données dans la BDD        
        xhrLoadData.send(JSON.stringify(tabLoadData[numLoadData])); // On lance pour le premier fichier à importer
    }


    loadDataButton.addEventListener('click', function() 
    {
        let checkboxes;
        checkboxes = document.querySelectorAll('input[name="checkFile[]"]'); // value^= signifie dont le texte commence par import_    
        
        if(checkboxes.length > 0) // Vérifier si checkboxes n'est pas vide
        {        
            tabLoadData = []; // Vider complètement le tableau

            checkboxes.forEach(checkbox => { // Parcourir les cases à cocher pour trouver celles qui sont cochées
                
                tab_check = checkbox.value.split('_'); // checkbox.value = 'import_n' et on veut récupérer n 
                checkId = document.getElementById('check_'+tab_check[1]);                
                waitId = document.getElementById('wait_'+tab_check[1]);
                noteId = document.getElementById('note_'+tab_check[1]);            
                graphId = document.getElementById('graph_'+tab_check[1]);
                if(checkId)
                {
                    waitId.style.display = 'none';
                    checkId.style.display = 'none';
                    noteId.style.display = 'none';
                    graphId.style.display = 'none';
                }

                if (checkbox.checked) // Si la case est cochée
                { 
                    tab_check = checkbox.value.split('_'); // checkbox.value = 'import_n' et on veut récupérer n  
                    tabLoadData.push(tab_check[1]); // Ajouter la valeur au tableau                
                }
            });        
        }

        if(tabLoadData.length > 0)
        {
            loadDataButton.style.display='none';
            loadWaitImg2.style.display='block';

            fileListInfo.value += '\n-- ENREGISTREMENT DES DONNEES --\n\n';
            fileListInfo.scrollTop = fileListInfo.scrollHeight; // Descendre automatiquement le scroll jusqu'en bas

            loadData();
        }
    });







    // -----------------------------

    // Fonction pour la sélection des checkbox dans la liste des chroniques à importer
    function toggleCheckboxes() 
    {
        let checkboxes;
        checkboxes = document.querySelectorAll('input[type="checkbox"][value^="import"]'); // value^= signifie dont le texte commence par id_station

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


    // Fonction pour l'affichage d'un bloc avec les consignes d'importation des fichiers.

    function afficheBlockInfo() 
    {
        var blockId = document.getElementById('box_import_info'); 
        blockId.style.display = 'block';
    }

    var popupCadre = document.getElementById('cadre_view_2');
    document.addEventListener("click", function(event)
    {
        // Vérifie si l'élément cliqué est à l'intérieur ou à l'extérieur du popup
        var blockId = document.getElementById('box_import_info'); 
        if (event.target !== popupCadre && event.target === blockId) 
        {
            // Ferme le popup
            blockId.style.display = "none";
        }
    });

</script>