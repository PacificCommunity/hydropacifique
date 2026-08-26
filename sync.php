<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Nomad <-> Server data synchronization page
----------------------------------------
*/

// Load the configuration file - Security - Same for every page
require('include/application_top.php');


//---------------

// Retrieve the actions performed on NOMAD

$formatted_date_sync_nomad = '';
$nb_agent_nomad = 0;
$nb_ra_nomad = 0;
$nb_jge_nomad = 0;

// TABLE AGENT 
$sql_agent_nomad = "SELECT DISTINCT COUNT(*) as nb_agent_nomad
                FROM ".TABLE_AGENT."
                WHERE from_nomad=1 AND hp_load=0";
$agent_nomad_query = tep_db_query($sql_link,$sql_agent_nomad);
$agent_nomad = tep_db_fetch_array($agent_nomad_query);
$nb_agent_nomad = $agent_nomad['nb_agent_nomad'];

// TABLE RA 
$sql_ra_nomad = "SELECT DISTINCT COUNT(*) as nb_ra_nomad
                FROM ".TABLE_DATA_RA."
                WHERE from_nomad=1 AND hp_load=0";
$ra_nomad_query = tep_db_query($sql_link,$sql_ra_nomad);
$ra_nomad = tep_db_fetch_array($ra_nomad_query);
$nb_ra_nomad = $ra_nomad['nb_ra_nomad'];

// TABLE JGE
$sql_jge_nomad = "SELECT DISTINCT COUNT(*) as nb_jge_nomad
                FROM ".TABLE_DATA_JGE."
                WHERE from_nomad=1 AND hp_load=0";
$jge_nomad_query = tep_db_query($sql_link,$sql_jge_nomad);
$jge_nomad = tep_db_fetch_array($jge_nomad_query);
$nb_jge_nomad = $jge_nomad['nb_jge_nomad'];

// TABLE SYNC
$sql_info_sync_nomad = "SELECT sync_date
                FROM ".TABLE_SYNC_LOGS."
                WHERE sync_direction='download'
                ORDER BY sync_date DESC
                LIMIT 1";
$info_sync_nomad_query = tep_db_query($sql_link,$sql_info_sync_nomad);
$info_sync_nomad = tep_db_fetch_array($info_sync_nomad_query);
$date_sync_nomad = $info_sync_nomad['sync_date'] ?? null;

if(!empty($date_sync_nomad))
{
    $dateTime = new DateTime($date_sync_nomad);
    $formatted_date_sync_nomad = $dateTime->format('d/m/Y H:i:s');
}


// FROM DATA - ORIGINE DES DONNEES - TABLE DATA FROM
$sql_fromData = "SELECT DISTINCT id_service, name, description
				FROM ".TABLE_SERVICE." 				
				ORDER BY id_service ASC";
$fromData_query = tep_db_query($sql_link,$sql_fromData);	
while ($fromData = tep_db_fetch_array($fromData_query))
{
	$fromData_array[$fromData['id_service']] = array('name' => html_entity_decode($fromData['name'] ?? ''),
													'description' => html_entity_decode($fromData['description'] ?? '')
													);
}





// ----------------------------------------
// Edition HTML

// HTML header
require(DIR_WS_STRUCTURE . 'header_web.php');


echo "<body>";

	echo "<div id='contenu_info' style='display:none;'></div>";

	require(DIR_WS_STRUCTURE . 'header.php'); // Bando Haut
	include(DIR_WS_BOX . 'nav_accueil.php'); // Menu

	echo "<div id='contour_general'>";
		
		echo "<div id='contenu_centre'>";

            // Titre de la page
			echo "<h1>";
				
				echo "<span style='float:left;'>".TEXT_SYNC_PAGE_TITLE."</span>";

			echo "</h1>";	
			
			//-------------------------
			// Nomad <-> platform update area
			echo "<div id='cadre_index_cell_map' style='float:left;width:800px;padding-bottom:0;'>";

                echo "<div style='float:left;width:300px;'>";
                
                    echo "<div id='button_tonomad' style='width:300px;' onclick=''>";
                        echo "<img src='".DIR_WS_IMG_ICO."upload.png' style='float:left;width:22px;margin-right:5px;'>";
                        echo "<p style='padding-top:5px;'>";
                            echo TEXT_SYNC_BTN_TO_NOMAD;
                        echo "</p>";
                    echo "</div>";

                    echo "<hr>";

                    echo "<p style='margin-bottom:10px;'>";
                        echo "<span style='font-weight:bold;'>".TEXT_SYNC_LAST_LOAD."</span>";
                        echo "<input type='text' id='last_date_sync_nomad' 
                                    style='font-size:11px;border:none;' 
                                    value='".$formatted_date_sync_nomad."' readonly>";
                    echo "</p>";

                echo "</div>";

                echo "<div style='float:right;width:300px;'>";

                    echo "<div id='button_toserveur' style='width:300px;' onclick=''>";
                        echo "<img src='".DIR_WS_IMG_ICO."download.png' style='float:left;width:22px;margin-right:5px;'>";
                        echo "<p style='padding-top:5px;'>";
                            echo TEXT_SYNC_BTN_TO_SERVER;
                        echo "</p>";
                    echo "</div>";                    

                    echo "<hr>";

                    echo "<p style='margin-bottom:10px;display:none;' id='info_toserveur'>";
                        echo "<span style='font-weight:bold;'>".TEXT_SYNC_NB_AGENTS."</span>";
                        echo "<input type='text' id='nb_agent_sync_nomad' style='width:80px;margin:0;font-size:11px;border:none;' value='".$nb_agent_nomad."' readonly>";

                        echo "<br>";

                        echo "<span style='font-weight:bold;'>".TEXT_SYNC_NB_RA."</span>";
                        echo "<input type='text' id='nb_ra_sync_nomad' style='width:80px;margin:0;font-size:11px;border:none;' value='".$nb_ra_nomad."' readonly>";

                        echo "<br>";
                        
                        echo "<span style='font-weight:bold;'>".TEXT_SYNC_NB_JGE."</span>";
                        echo "<input type='text' id='nb_jge_sync_nomad' style='width:80px;margin:0;font-size:11px;border:none;' value='".$nb_jge_nomad."' readonly>";
                    echo "</p>";

                echo "</div>";

            echo "</div>";

            echo "<hr>";

            echo "<div id='cadre_index_cell_map' style='float:left;width:800px;overflow-y: auto;'>";
                
                echo "<div style='float:left;margin-top:0px;'>";

                    echo "<p style='float:left;font-size:14px;font-weight:bold;margin-top: 5px;'>";
                        echo TEXT_SYNC_PROCESS_RUNNING;
                    echo "</p>";

                    echo "<div id='wait-spin' class='spinner' style='margin-left:10px;visibility:hidden;' title='...'>";
                    echo "</div>";

                    // Synchronization stop button (hidden until a process is running)
                    echo "<div id='button_stop' style='float:left;margin-left:15px;display:none;cursor:pointer;padding:3px 12px;background:#c0392b;color:#fff;border-radius:4px;font-size:13px;font-weight:bold;'>";
                        echo TEXT_SYNC_BTN_STOP;
                    echo "</div>";

                echo "</div>";
                
                
                echo "<textarea id='info_processData' style='width:97%;height:210px;margin-top:5px;' readonly>";
                echo "</textarea>";

            echo "</div>";
		
		echo "</div>";

	echo "</div>";


echo "</body>";

echo "</html>";

?>	


<script>


	var id_user = <?php echo json_encode($id_user); ?>;

	var nbAgentNomad = <?php echo json_encode($nb_agent_nomad); ?>;
	var nbRaNomad = <?php echo json_encode($nb_ra_nomad); ?>;
	var nbJgeNomad = <?php echo json_encode($nb_jge_nomad); ?>;

	// Translation strings injected from PHP so the JavaScript stays language-aware.
	// json_encode handles quoting/escaping safely for any language.
	var T = {
		noConnection:   <?php echo json_encode(TEXT_SYNC_JS_NO_CONNECTION); ?>,
		processStopped: <?php echo json_encode(TEXT_SYNC_JS_PROCESS_STOPPED); ?>,
		connectionOk:   <?php echo json_encode(TEXT_SYNC_JS_CONNECTION_OK); ?>,
		loadingFrom:    <?php echo json_encode(TEXT_SYNC_JS_LOADING_FROM); ?>,
		pushingTo:      <?php echo json_encode(TEXT_SYNC_JS_PUSHING_TO); ?>,
		connectFailed:  <?php echo json_encode(TEXT_SYNC_JS_CONNECT_FAILED); ?>,
		pleaseWait:     <?php echo json_encode(TEXT_SYNC_JS_PLEASE_WAIT); ?>,
		stopRequested:  <?php echo json_encode(TEXT_SYNC_JS_STOP_REQUESTED); ?>
	};


	// --------------------------------------------------
	// Scripts DATA SYNC - NOMAD VERSION
	// --------------------------------------------------
		
	var waitSpin = document.getElementById('wait-spin');
	var buttonStop = document.getElementById('button_stop');

	var processEnCours = null; // 'toNomad' | 'toServeur' | null

    var toNomad = document.getElementById('button_tonomad');
    var toServeur = document.getElementById('button_toserveur');

    var infoProcessData = document.getElementById('info_processData');
    var lastDateSyncNomad = document.getElementById('last_date_sync_nomad');

    var infoToserveur = document.getElementById('info_toserveur');
    var nbAgentSyncNomad = document.getElementById('nb_agent_sync_nomad');
    var nbRaSyncNomad = document.getElementById('nb_ra_sync_nomad');
    var nbJgeSyncNomad = document.getElementById('nb_jge_sync_nomad');

    if(nbAgentNomad > 0 || nbRaNomad > 0 || nbJgeNomad > 0)
    {
        nbAgentSyncNomad.value = nbAgentNomad;
        nbRaSyncNomad.value = nbRaNomad;
        nbJgeSyncNomad.value = nbJgeNomad;

        infoToserveur.style.display = 'block';
        
        disableButton(toNomad);
    }
    else{disableButton(toServeur);}
    
    toNomad.onclick = function() 
    {
        disableButton(toNomad);
        disableButton(toServeur);
        dbConnect('toNomad');
    };

    toServeur.onclick = function() 
    {
        disableButton(toNomad);
        disableButton(toServeur);
        dbConnect('toServeur');
    };
    
    function dbConnect(process) 
    {		
        waitSpin.style.visibility = 'visible';

        if (!navigator.onLine)
        {
            infoProcessData.innerHTML = T.noConnection;
            infoProcessData.innerHTML += `\n\n`;
            infoProcessData.innerHTML += T.processStopped;

            waitSpin.style.visibility = 'hidden';

            if(process == 'toServeur'){enableButton(toServeur);}
            if(process == 'toNomad'){enableButton(toNomad);}
            return; // on n'enclenche pas la suite hors-ligne
        }
        else
        {
            infoProcessData.innerHTML = T.connectionOk + `\n\n`;	
            if(process == 'toNomad'){infoProcessData.innerHTML += T.loadingFrom;}
            if(process == 'toServeur'){infoProcessData.innerHTML += T.pushingTo;}	
            infoProcessData.innerHTML += `\n\n`;
        }

        // Mark the current process, clear any leftover stop flag,
        // then show the "Stop" button
        processEnCours = process;
        setStopFlag('clear');
        buttonStop.style.display = 'block';
        enableButton(buttonStop); // in case it was disabled during a previous stop

        // Send an asynchronous AJAX request
        var xhrConnectNomad = new XMLHttpRequest();
        xhrConnectNomad.open("POST", "include/structure/nomad/process_connect.php", true);
        xhrConnectNomad.setRequestHeader("Content-Type", "application/json");

        xhrConnectNomad.onreadystatechange = function() 
        {
            if (xhrConnectNomad.readyState === 4 && xhrConnectNomad.status === 200) 
            {	
                // Parse the JSON response
                var jsonResponse = JSON.parse(xhrConnectNomad.responseText);
                var erreurConnect = jsonResponse.erreur; // PHP key: 'erreur'
                processInfo = jsonResponse.process_info;
                
                infoProcessData.innerHTML += processInfo;
                infoProcessData.scrollTop = infoProcessData.scrollHeight; // Descendre automatiquement le scroll jusqu'en bas

                if (erreurConnect)
                {
                    // Connection failed: stop cleanly here.
                    infoProcessData.innerHTML += `\n` + T.connectFailed + `\n`;
                    infoProcessData.scrollTop = infoProcessData.scrollHeight;

                    buttonStop.style.display = 'none';
                    waitSpin.style.visibility = 'hidden';
                    processEnCours = null;
                    setStopFlag('clear');

                    if(process == 'toServeur'){ enableButton(toServeur); }
                    if(process == 'toNomad'){ enableButton(toNomad); }
                    return;
                }

                dataLoad(process);
				

            }
        };

        // Convertir l'objet JavaScript en format JSON et l'envoyer au serveur
        xhrConnectNomad.send();
    };

    function dataLoad(process)
    {
        // Build the JavaScript object holding the data to send
        var dataToSend = {
                            timezone_php : '<?php echo $timezone_php ?>',
                            idUser: '<?php echo $id_user; ?>'
                        };
        
        infoProcessData.innerHTML += `\n` + T.pleaseWait + `\n\n`;			
        infoProcessData.scrollTop = infoProcessData.scrollHeight; // Descendre automatiquement le scroll jusqu'en bas	

        // Send an asynchronous AJAX request
        var xhrProcess = new XMLHttpRequest();
        if(process == 'toNomad'){xhrProcess.open("POST", "include/structure/nomad/process_tonomad.php", true);}
        if(process == 'toServeur'){xhrProcess.open("POST", "include/structure/nomad/process_toserveur.php", true);}
        xhrProcess.setRequestHeader("Content-Type", "application/json");

        xhrProcess.onreadystatechange = function() 
        {
            if (xhrProcess.readyState === 4 && xhrProcess.status === 200) 
            {	
                // Parse the JSON response
                var jsonResponse = JSON.parse(xhrProcess.responseText);
                var erreurEtat = jsonResponse.erreur;   // PHP key: 'erreur'
                var arretEtat  = jsonResponse.arret;    // PHP key: 'arret'
                processInfo = jsonResponse.process_info;
                processDatetime = jsonResponse.process_datetime;
                
                infoProcessData.innerHTML += processInfo;
                infoProcessData.scrollTop = infoProcessData.scrollHeight; // Descendre automatiquement le scroll jusqu'en bas

                // Process finished: hide the stop button and the spinner
                buttonStop.style.display = 'none';
                waitSpin.style.visibility = 'hidden';
                processEnCours = null;

                if (erreurEtat)
                {
                    // ERROR: nothing was changed. Put the buttons back into a usable state.
                    if(process == 'toServeur'){ enableButton(toServeur); disableButton(toNomad); }
                    if(process == 'toNomad'){ enableButton(toNomad); }
                }
                else if (arretEtat)
                {
                    // VOLUNTARY STOP: like an error state-wise, nothing was changed.
                    if(process == 'toServeur'){ enableButton(toServeur); disableButton(toNomad); }
                    if(process == 'toNomad'){ enableButton(toNomad); }
                }
                else
                {
                    // SUCCESS
                    if(process == 'toServeur')
                    {
                        nbAgentSyncNomad.value = 0;
                        nbRaSyncNomad.value = 0;
                        nbJgeSyncNomad.value = 0;
                        infoToserveur.style.display = 'none';
                        enableButton(toNomad);
                    }
                    if(process == 'toNomad')
                    {
                        enableButton(toNomad);
                        lastDateSyncNomad.value = processDatetime;
                    }
                }

            }
        };

        // Convertir l'objet JavaScript en format JSON et l'envoyer au serveur
        xhrProcess.send(JSON.stringify(dataToSend));
    }
		

    // Raise ('set') or remove ('clear') the stop flag on the server side
    function setStopFlag(action)
    {
        var xhrStop = new XMLHttpRequest();
        xhrStop.open("POST", "include/structure/nomad/process_stop_sync.php", true);
        xhrStop.setRequestHeader("Content-Type", "application/json");
        xhrStop.send(JSON.stringify({ action: action, idUser: '<?php echo $id_user; ?>' }));
    }

    // Click on "Stop": raise the flag. The PHP process will notice it at the
    // next step and roll everything back cleanly.
    buttonStop.onclick = function()
    {
        if (processEnCours === null) { return; }

        setStopFlag('set');

        // Immediate visual feedback (PHP will confirm the stop in its final message)
        infoProcessData.innerHTML += `\n` + T.stopRequested + `\n`;
        infoProcessData.scrollTop = infoProcessData.scrollHeight;

        disableButton(buttonStop);
    };

    function disableButton(button) 
    {
        button.style.opacity = '0.5'; // Dim the button for a greyed-out look
        button.style.pointerEvents = 'none'; // Disable clicking
        button.style.cursor = 'default'; // Change le curseur
    }

    function enableButton(button) 
    {
        button.style.opacity = '1'; // Restore normal opacity
        button.style.pointerEvents = 'auto'; // Re-enable clicking
        button.style.cursor = 'pointer'; // Restore the hand cursor
    }



</script>