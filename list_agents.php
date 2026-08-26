<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Agent list page — AJAX-driven actions for create, edit, delete
----------------------------------------
*/

require('include/application_top.php');

$message_info      = '';
$row               = 0;
$search_agent      = '';
$where_search      = '';
$nb_agents         = 0;
$nb_agents_service = 0;
$nb_agents_terrain = 0;

// Search filter
if (isset($_POST['search_agent']) || isset($_GET['search_agent']))
{
    if (isset($_POST['search_agent'])) { $search_agent = post_secure($sql_link, $_POST['search_agent']); }
    $where_search = search_agent($search_agent, '');
}


// -----------------------------------------------
// HTML output

require(DIR_WS_STRUCTURE . 'header_web.php');

echo "<body>";

    echo "<div id='contenu_info' style='display:none;'></div>";

    require(DIR_WS_AGENT     . 'block_agent_delete.php'); // Agent deletion confirmation popup
    require(DIR_WS_AGENT     . 'block_agent.php');        // Agent record popup form
    require(DIR_WS_STRUCTURE . 'header.php');
    include(DIR_WS_BOX       . 'nav_accueil.php');

    echo "<div id='contour_general'>";
        echo "<div id='contenu_centre'>";
            echo "<div id='contenu_box2'>";

                echo "<h1><span>" . TEXT_AGENT_LIST_TITLE . "</span></h1>";

                $lien_form = tep_href_link('list_agents.php');
                $name_form = 'form_agents';
                echo "<form name='" . $name_form . "' action='" . $lien_form . "' method='post' enctype='multipart/form-data'>";

                    // ---- Left sidebar ----
                    echo "<div id='cadre_graph' style='float:left;width:250px;margin-right:1%;height:70vh;overflow-y:auto;'>\n";

                        // New agent button
                        echo "<div id='boxpopup' class='select-top' style='width:92%;padding:10px 3%;margin-bottom:10px;'>\n";
                            echo "<div id='button_titre' style='margin-left:19%;' onClick='loadFicheAgent(0)'>";
                                echo TEXT_AGENT_LIST_NEW_BTN;
                            echo "</div>\n";
                        echo "</div>";

                        // Search box + counters
                        echo "<div id='boxpopup' class='select-top' style='width:92%;margin:0;padding:0 3%;'>\n";

                            echo "<p style='float:left;width:90%;margin-top:15px;padding-top:5px;color:#609966;'>" . TEXT_AGENT_LIST_SEARCH . "</p>";

                            echo "<div id='contenu_search' style='float:left;width:90%;'>";
                                echo "<input name='search_agent' type='text' value='" . $search_agent . "' style='float:left;width:70%;'>";
                                echo "<img src='" . DIR_WS_IMG_ICO . "arrow.png' alt='" . TEXT_AGENT_LIST_SEARCH . "' onclick='form_agents.submit();' style='float:left;width:28px;margin-left:10px;'/>";
                            echo "</div>";

                            echo "<div id='contenu_infos'>";
                                echo "<p>";
                                    echo "<span style='float:left;font-size:12px;'>" . TEXT_AGENT_LIST_COUNT . "</span>";
                                    echo "<input type='text' id='nb_agents' value='' readonly style='float:right;width:50px;padding:0;font-size:12px;background:none;border:none;'>";
                                    echo "<br><br>";
                                    echo "<span style='float:left;font-size:12px;'>" . TEXT_AGENT_LIST_COUNT_SERVICE . $service_hydro . " : </span>";
                                    echo "<input type='text' id='nb_agents_service' value='' readonly style='float:right;width:50px;padding:0;font-size:12px;background:none;border:none;'>";
                                    echo "<br><br>";
                                    echo "<span style='float:left;font-size:12px;'>" . TEXT_AGENT_LIST_COUNT_TERRAIN . "</span>";
                                    echo "<input type='text' id='nb_agents_terrain' value='' readonly style='float:right;width:50px;padding:0;font-size:12px;background:none;border:none;'>";
                                echo "</p>";
                            echo "<hr>";
                            echo "</div>";

                        echo "</div>";

                    echo "</div>";

                echo "</form>";

                // ---- Agent list table ----
                echo "<div id='result_listAgents' class='table-container' style='float:none;width:auto;height:80vh;'>";
                    echo "<div style='width:95%;height:78vh;overflow-y:auto;'>";
                        echo "<table id='table_tri' cellspacing='0'>";
                            echo "<thead>";
                                echo "<tr class='header-row'>";
                                    echo "<th style='width:130px;'>"               . TEXT_AGENT_TH_NOM         . "</th>";
                                    echo "<th style='width:130px;'>"               . TEXT_AGENT_TH_PRENOM      . "</th>";
                                    echo "<th style='width:180px;'>"               . TEXT_AGENT_TH_EMAIL       . "</th>";
                                    echo "<th style='width:110px;'>"               . TEXT_AGENT_TH_TEL         . "</th>";
                                    echo "<th style='width:180px;'>"               . TEXT_AGENT_TH_INSTITUTION . "</th>";
                                    echo "<th style='width:200px;'>"               . TEXT_AGENT_TH_FONCTION    . "</th>";
                                    echo "<th style='width:20px;text-align:center;'>" . TEXT_AGENT_TH_SERVICE . $service_hydro . "</th>";
                                    echo "<th style='width:20px;text-align:center;'>" . TEXT_AGENT_TH_TERRAIN  . "</th>";
                                    echo "<th style='width:40px;text-align:center;'></th>";
                                echo "</tr>";
                                echo "<tr><td colspan='8' style='height:15px;'>&nbsp;</td></tr>";
                            echo "</thead>";
                            echo "<tbody></tbody>";
                        echo "</table>";
                    echo "</div>";

                    // Loading indicator
                    echo "<div id='wait' style='width:100%;height:65px;margin-top:30px;text-align:center;'>";
                        echo "<div class='hp-loader' title='" . TEXT_AGENT_LOADING . "'>";
                            echo "<div class='hp-ring'></div>";
                            echo "<div class='hp-mark'><span class='h'>H</span><span class='p'>P</span></div>";
                        echo "</div>";
                        echo "<p style='text-align:center;color:#000;'>" . TEXT_AGENT_LOADING . "</p>";
                        echo "<p style='text-align:center;'>" . TEXT_AGENT_LOADING_WAIT . "</p>";
                    echo "</div>\n";

                echo "<hr>";
                echo "</div>";

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

    // -----------------------------------------------
    // Global variables

    var id_user_agent = '<?php echo $id_user; ?>';
    var territoire_id = '<?php echo $territoire_id; ?>';
    var hpVersion = '<?php echo HP_VERSION; ?>';
    var where_agents  = '<?php echo $where_search; ?>';

    var blockListAgent = document.getElementById('result_listAgents');
    var nbAgents       = document.getElementById('nb_agents');
    var wait           = document.getElementById('wait');
    var tbody_info     = document.querySelector('#table_tri tbody');
    var boxAgent       = document.getElementById('box_agent');
    var formAgent      = document.forms['formAgent'];    
    var contenuInfo    = document.getElementById('contenu_info');


    // -----------------------------------------------
    // Load agent list table via AJAX

    function loadAgentsTab()
    {
        contenuInfo.style.display    = 'none';
        blockListAgent.style.display = 'none';
        wait.style.display           = 'block';

        var xhr = new XMLHttpRequest();
        xhr.open("POST", "include/structure/agent/process_agents_tab.php", true);
        xhr.setRequestHeader("Content-Type", "application/json");

        xhr.onreadystatechange = function()
        {
            if (xhr.readyState === 4 && xhr.status === 200)
            {
                var jsonResponse = JSON.parse(xhr.responseText);

                nbAgents.value       = jsonResponse['nb_agents'];
                tbody_info.innerHTML = jsonResponse['tab_html'];
                formAgent.insertAdjacentHTML('afterbegin', jsonResponse['hidden_html_agent']);

                blockListAgent.style.display = 'block';
                wait.style.display           = 'none';
            }
        };

        xhr.send(JSON.stringify({ territoire_id: territoire_id, where_agents: where_agents }));
    }

    loadAgentsTab(); // Initial load


    // -----------------------------------------------
    // Save agent record via AJAX

    function saveAgent(event)
    {
        event.preventDefault();

        var formData = new FormData(formAgent);
        formData.append('territoire_id', territoire_id);
        formData.append('id_user_agent', id_user_agent);

        var xhr = new XMLHttpRequest();
        xhr.open("POST", "include/structure/agent/process_agent_save.php", true);

        xhr.onreadystatechange = function()
        {
            if (xhr.readyState === 4 && xhr.status === 200)
            {
                var jsonResponse = JSON.parse(xhr.responseText);
                var erreur   = jsonResponse['erreur'];
                var msg_info = jsonResponse['msg_info'];

                contenuInfo.innerHTML     = msg_info;

                if (!erreur)
                {
                    contenuInfo.style.border = '2px solid #09886d';
                    loadAgentsTab();
                }
                else
                {
                    contenuInfo.style.border = '2px solid #930000';
                }

                contenuInfo.style.display = 'block';
            }
        };

        xhr.send(formData);
    }


    // -----------------------------------------------
    // Load delete confirmation popup via AJAX

    function verifDelAgent(id_agent)
    {
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "include/structure/agent/process_agent_verifdelete.php", true);
        xhr.setRequestHeader("Content-Type", "application/json");

        xhr.onreadystatechange = function()
        {
            if (xhr.readyState === 4 && xhr.status === 200)
            {
                var jsonResponse = JSON.parse(xhr.responseText);
                
                let contentDelAgent = document.getElementById('content_del_agent');
                contentDelAgent.innerHTML     = jsonResponse['tab_html'];

                document.getElementById('box_del_agent').style.display = 'block';
            }
        };

        xhr.send(JSON.stringify({ id_agent: id_agent }));
    }


    // -----------------------------------------------
    // Delete agent via AJAX

    function delAgent(id_agent)
    {
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "include/structure/agent/process_agent_delete.php", true);
        xhr.setRequestHeader("Content-Type", "application/json");

        xhr.onreadystatechange = function()
        {
            if (xhr.readyState === 4 && xhr.status === 200)
            {
                loadAgentsTab();

                var jsonResponse = JSON.parse(xhr.responseText);
                document.getElementById('box_del_agent').style.display = 'none';

                var msg_info = jsonResponse['msg_info'];
                var del      = jsonResponse['del'];

                contenuInfo.innerHTML     = msg_info;
                contenuInfo.style.display = 'block';
                contenuInfo.style.border  = del ? '2px solid #09886d' : '2px solid #930000';
            }
        };

        xhr.send(JSON.stringify({ id_agent: id_agent, id_user_agent: id_user_agent }));
    }


    // -----------------------------------------------
    // Open agent record popup and populate fields

    function loadFicheAgent(id_agent)
    {
        boxAgent.style.display = 'block';

        if (id_agent > 0)
        {
            
            if(hpVersion == 'Nomad')
            {
                let fromNomad = document.getElementById('from_nomad_' + id_agent).value;
                let hpLoad    = document.getElementById('hp_load_'    + id_agent).value;

                document.getElementById('save_agent').style.display = (fromNomad > 0 && hpLoad < 1) ? 'block' : 'none';
            }

            let nomAgent    = document.getElementById('nom_'    + id_agent).value;
            let prenomAgent = document.getElementById('prenom_' + id_agent).value;

            let titreAgent = '<?php echo TEXT_AGENT_FICHE_TITLE; ?> ' + nomAgent + ' ' + prenomAgent;

            document.getElementById('titre_fiche_agent').value          = titreAgent;
            document.getElementById('title_box_agent_text').textContent = titreAgent;
            document.getElementById('id_agent_fiche').value    = id_agent;
            document.getElementById('nom').value               = nomAgent;
            document.getElementById('nom_marital').value       = document.getElementById('nom_marital_'    + id_agent).value;
            document.getElementById('prenom').value            = prenomAgent;
            document.getElementById('raisonsociale').value     = document.getElementById('raisonsociale_'  + id_agent).value;
            document.getElementById('numinscription').value    = document.getElementById('numinscription_' + id_agent).value;
            document.getElementById('fonction').value          = document.getElementById('fonction_'       + id_agent).value;
            document.getElementById('adresse').value           = document.getElementById('adresse_'        + id_agent).value;
            document.getElementById('lieudit').value           = document.getElementById('lieudit_'        + id_agent).value;
            document.getElementById('bp').value                = document.getElementById('bp_'             + id_agent).value;
            document.getElementById('codepostal').value        = document.getElementById('codepostal_'     + id_agent).value;
            document.getElementById('tel').value               = document.getElementById('tel_'            + id_agent).value;
            document.getElementById('mobile').value            = document.getElementById('mobile_'         + id_agent).value;
            document.getElementById('fax').value               = document.getElementById('fax_'            + id_agent).value;
            document.getElementById('email').value             = document.getElementById('email_'          + id_agent).value;
            document.getElementById('siteweb').value           = document.getElementById('siteweb_'        + id_agent).value;

            // Commune select
            let select_commune = document.getElementById('id_commune_' + id_agent).value;
            let listCommune    = document.getElementById('select_commune');
            listCommune.selectedIndex = 0;
            for (let i = 0; i < listCommune.options.length; i++)
            {
                if (listCommune.options[i].value == select_commune) { listCommune.selectedIndex = i; break; }
            }

            // Checkboxes
            document.getElementById('check_service_hydro').checked = (document.getElementById('service_hydro_' + id_agent).value > 0);
            document.getElementById('check_terrain').checked       = (document.getElementById('terrain_'       + id_agent).value > 0);
        }
        else
        {
            // New agent — clear all fields
            document.getElementById('titre_fiche_agent').value          = '';
            document.getElementById('title_box_agent_text').textContent = '<?php echo TEXT_AGENT_FICHE_NEW; ?>';
            document.getElementById('id_agent_fiche').value    = 0;

            let fields = ['nom','nom_marital','prenom','raisonsociale','numinscription','fonction',
                          'adresse','lieudit','bp','codepostal','tel','mobile','fax','email','siteweb'];
            fields.forEach(function(f) { document.getElementById(f).value = ''; });

            document.getElementById('select_commune').selectedIndex    = 0;
            document.getElementById('check_service_hydro').checked     = false;
            document.getElementById('check_terrain').checked           = false;
        }
    }

</script>
