<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Affichage de la Liste des RA avec options de sélection
----------------------------------------
*/
require('include/application_top.php');

// Initialisation des variables
$modif_ra = 1;
$id_ra_modif = 0;
$message_info = '';
$description_popup = '';
$row = 0;
$indice = 0;
$nb_ra = 0;
$nb_ra_valid = 0;

// LIMIT DU NB DE LIGNES A AFFICHER
$limit_encours = 50;
$limit_ra = " LIMIT 0, ".$limit_encours;
if(isset($_POST['select_limit'])) {
    $limit_encours = $_POST['select_limit'];
    if($limit_encours > 0) {$limit_ra = " LIMIT 0, ".$limit_encours;}
    else {$limit_ra = '';}
}

// SELECT POUR LE TRI
$tri_encours = 1;
$tri = "ra.date_heure_ra";
if(isset($_POST['select_tri'])) {
    $tri_encours = $_POST['select_tri'];
    if($_POST['select_tri'] == 1) {$tri = "ra.date_heure_ra";}
    if($_POST['select_tri'] == 2) {$tri = "s.nom_station";}
    if($_POST['select_tri'] == 3) {$tri = "s.code_station";}
    if($_POST['select_tri'] == 4) {$tri = "s.station_type";}
}

$tri_order_encours = 2;
$tri_order = " DESC,";
if(isset($_POST['order_tri'])) {
    $tri_order_encours = $_POST['order_tri'];
    if($_POST['order_tri'] == 1) {$tri_order = " ASC,";}
    if($_POST['order_tri'] == 2) {$tri_order = " DESC,";}
}

// Initialisation des filtres
$affiche_select_from = true;
$affiche_select_type = true;
$affiche_select_tournee = false;
$affiche_search = true;
$affiche_select_riviere = false;
$affiche_select_station = true;
$affiche_select_statut_station = true;
require(DIR_WS_FILTRE . 'filtre_stations_var.php');

if(isset($_POST['search_station'])) {
    $search_station = post_secure($sql_link,$_POST['search_station']);
    $where_search = search_station($search_station,'');
}


// Période de données
$select_periode_encours = 60;
if(isset($_POST['select_periode'])) {$select_periode_encours = $_POST['select_periode'];}

$where_and_periode = " AND ra.date_heure_ra >= CURDATE() - INTERVAL ".$select_periode_encours." MONTH";
if($select_periode_encours==0) {$where_and_periode = '';}


// SELECT STATION GET
$get_st = 0;
if(isset($_GET['st']) && ctype_digit($_GET['st']))
{
    $get_st = (int)$_GET['st'];
    $where_and_station = " AND s.id_station=".$get_st;

    $where_and_active = '';
    $where_and_periode = '';
}

// SELECT RA GET
$get_ra = 0;
if(isset($_GET['ra']) && ctype_digit($_GET['ra']))
{
    $get_ra = (int)$_GET['ra'];

    // Un RA precis est demande via l'URL (?ra=XXX). Comme il peut etre
    // plus ancien que les N premieres lignes affichees par defaut, on
    // debride la limite : sinon il n'apparait pas dans la liste, donc
    // pas dans $ra_nav_array, et process_ra_*_affiche.php declenche
    // "Undefined array key XXX" sur $ra_nav_array[$id_ra].
    $limit_ra = '';
}

// SELECT TYPE DATA GET
$get_typeData = 0;
if(isset($_GET['td']) && ctype_digit($_GET['td']))
{
    $get_typeData = (int)$_GET['td'];
}

// NEW RA REQUEST GET — when set to a valid RA type code, the JS will
// auto-open the new-RA popup for that type once the page is loaded.
// Used by deep links from other modules (e.g. the "+" link in the
// Well log popup → list_ra.php?st=XX&new_ra=5)
$get_newRa = 0;
if(isset($_GET['new_ra']) && ctype_digit($_GET['new_ra']))
{
    $get_newRa = (int)$_GET['new_ra'];
}

// Clauses WHERE et ORDER
$where_ra = 's.id_territoire='.$territoire_id . $where_and_from .
            $where_search.$where_and_region.$where_and_commune.
            $where_and_regionhydro.$where_and_riviere.
            $where_and_type.
            $where_and_station.
            $where_and_tournee.
            $where_and_active.$where_and_suivi.$where_and_armee.
            $where_and_periode;
$order_ra = $tri.$tri_order;

$where_station = $where_and_from . $where_search.$where_and_region.$where_and_commune.
                $where_and_regionhydro.$where_and_riviere.
                $where_and_type.
                $where_and_station.
                $where_and_tournee.
                $where_and_active.$where_and_suivi.$where_and_armee;





// TABLE AGENT
$sql_agent = "SELECT DISTINCT id, nom, prenom FROM ".TABLE_AGENT." WHERE terrain=1 ORDER BY nom ASC";
$agent_query = tep_db_query($sql_link,$sql_agent);
while($agent = tep_db_fetch_array($agent_query)) {
    $nom_agent = strtoupper(html_entity_decode($agent['nom'] ?? ''));
    $prenom_agent = htmlaccent(html_entity_decode($agent['prenom'] ?? ''));
    $agent_array[$agent['id']] = $prenom_agent." ".$nom_agent;
}






// EDITION HTML
require(DIR_WS_STRUCTURE . 'header_web.php');

echo "<body>";
    echo "<div id='contenu_info' style='display:none;'></div>";
    require(DIR_WS_STRUCTURE . 'block_wait.php');
    require(DIR_WS_RA . 'block_ra_delete.php');
    require(DIR_WS_RA . 'block_ra.php');
    require(DIR_WS_STRUCTURE . 'header.php');
    include(DIR_WS_BOX . 'nav_accueil.php');

    echo "<div id='contour_general'>";
        echo "<div id='contenu_centre'>";
            echo "<div id='contenu_box2'>";
                echo "<h1><span>".TEXT_TITLE_RA_LIST."</span></h1>";

                $lien_form = tep_href_link('list_ra.php');
                $name_form = 'form_ra';
                echo "<form name='".$name_form."' action='".$lien_form."' method='post' enctype='multipart/form-data'>";
                    echo "<div id='cadre_graph' style='float:left;width:275px;margin-right:15px;height:80vh;overflow-y: auto;'>";

                        echo "<div class='ra-new-buttons' style='width:246px;margin:0 0 15px 4px;display:flex;flex-direction:column;align-items:center;gap:6px;'>";

                            if($select_type_encours > 0) {
                                if($select_type_encours == 1)
                                    echo "<div id='button_titre' style='width:200px;text-align:center;box-sizing:border-box;' onclick='loadRA(0,1)'>".TEXT_NEW_RA_PLUVIO."</div>";
                                if($select_type_encours == 11)
                                    echo "<div id='button_titre' style='width:200px;text-align:center;box-sizing:border-box;' onclick='loadRA(0,11)'>".TEXT_NEW_RA_HYDRO."</div>";
                                if($select_type_encours == 5)
                                    echo "<div id='button_titre' style='width:200px;text-align:center;box-sizing:border-box;' onclick='loadRA(0,5)'>".TEXT_NEW_RA_PIEZO."</div>";
                            } else {
                                if(isset($eq_type_array[1]))
                                    echo "<div id='button_titre' style='width:200px;text-align:center;box-sizing:border-box;' onclick='loadRA(0,1)'>".TEXT_NEW_RA_PLUVIO."</div>";
                                if(isset($eq_type_array[11]))
                                    echo "<div id='button_titre' style='width:200px;text-align:center;box-sizing:border-box;' onclick='loadRA(0,11)'>".TEXT_NEW_RA_HYDRO."</div>";
                                if(isset($eq_type_array[5]))
                                    echo "<div id='button_titre' style='width:200px;text-align:center;box-sizing:border-box;' onclick='loadRA(0,5)'>".TEXT_NEW_RA_PIEZO."</div>";
                            }

                        echo "</div>";

                        echo "<div id='boxpopup' class='select-top' style='width:230px;padding:5px 3%;margin-bottom:10px;'>";
                            echo "<p style='float:left;width:26%;margin-top:5px;padding-top:5px;color: #609966;'>".TEXT_PERIOD_LABEL."</p>";
                            echo "<select name='select_periode' id='select_periode' onchange='".$name_form.".submit();' style='float:right;width:125px;margin-top:5px;'>";
                                $selected = ($select_periode_encours==1) ? "selected" : "";
                                echo "<option value='1' ".$selected.">".TEXT_PERIOD_1_MONTH."</option>";
                                $selected = ($select_periode_encours==3) ? "selected" : "";
                                echo "<option value='3' ".$selected.">".TEXT_PERIOD_3_MONTHS."</option>";
                                $selected = ($select_periode_encours==6) ? "selected" : "";
                                echo "<option value='6' ".$selected.">".TEXT_PERIOD_6_MONTHS."</option>";
                                $selected = ($select_periode_encours==12) ? "selected" : "";
                                echo "<option value='12' ".$selected.">".TEXT_PERIOD_1_YEAR."</option>";
                                $selected = ($select_periode_encours==24) ? "selected" : "";
                                echo "<option value='24' ".$selected.">".TEXT_PERIOD_2_YEARS."</option>";
                                $selected = ($select_periode_encours==60) ? "selected" : "";
                                echo "<option value='60' ".$selected.">".TEXT_PERIOD_5_YEARS."</option>";
                                $selected = ($select_periode_encours==120) ? "selected" : "";
                                echo "<option value='120' ".$selected.">".TEXT_PERIOD_10_YEARS."</option>";
                                $selected = ($select_periode_encours==0) ? "selected" : "";
                                echo "<option value='0' ".$selected.">".TEXT_PERIOD_ALL_DATA."</option>";
                            echo "</select>";

                            echo "<hr>";
                            echo "<div style='width:100%;border-bottom:2px solid #176B87;margin-top:0px;'></div>";
                            echo "<hr>";

                            require(DIR_WS_FILTRE . 'filtre_stations_html.php');

                            echo "<hr>";
                            echo "<div style='width:100%;border-bottom:2px solid #176B87;margin-top:5px;'></div>";
                            echo "<p style='float:left;width:auto;padding-top:5px;color:#186F65;margin-top:15px;'>".TEXT_SORT_BY."</p>";
                            echo "<select name='select_tri' id='select_tri' onchange='".$name_form.".submit();' style='float:right;width:130px;margin-top:15px;'>";
                                $selected = ($tri_encours == 1) ? "selected" : "";
                                echo "<option value='1' ".$selected.">".TEXT_SORT_LAST_VISIT."</option>";
                                $selected = ($tri_encours == 2) ? "selected" : "";
                                echo "<option value='2' ".$selected.">".TEXT_SORT_STATION_NAME."</option>";
                                $selected = ($tri_encours == 3) ? "selected" : "";
                                echo "<option value='3' ".$selected.">".TEXT_SORT_STATION_CODE."</option>";
                                $selected = ($tri_encours == 4) ? "selected" : "";
                                echo "<option value='4' ".$selected.">".TEXT_SORT_DATA_TYPE."</option>";
                            echo "</select>";

                            echo "<hr>";
                            echo "<div style='float:right;'>";
                                $asc_checked = ($tri_order_encours == 1) ? "checked" : "";
                                $desc_checked = ($tri_order_encours == 2) ? "checked" : "";
                                echo "<p style='float:left;width:55px;padding-top:3px;'>".TEXT_SORT_ASCENDING."</p>";
                                echo "<input type='radio' id='asc' name='order_tri' value='1' style='float:left;' ".$asc_checked." onchange='".$name_form.".submit();'>";
                                echo "<p style='float:left;width:65px;margin-left:10px;padding-top:3px;'>".TEXT_SORT_DESCENDING."</p>";
                                echo "<input type='radio' id='desc' name='order_tri' value='2' style='float:left;' ".$desc_checked." onchange='".$name_form.".submit();'>";
                            echo "</div>";

                            echo "<hr>";
                            echo "<p style='float:left;width:auto;color:#186F65;margin-top:10px;'>".TEXT_NB_LINES."</p>";
                            echo "<select name='select_limit' id='select_limit' onchange='".$name_form.".submit();' style='float:right;width:130px;margin-top:5px;'>";
                                $selected = ($limit_encours == 50) ? "selected" : "";
                                echo "<option value='50' ".$selected.">".TEXT_NB_LINES_50."</option>";
                                $selected = ($limit_encours == 100) ? "selected" : "";
                                echo "<option value='100' ".$selected.">".TEXT_NB_LINES_100."</option>";
                                $selected = ($limit_encours == 200) ? "selected" : "";
                                echo "<option value='200' ".$selected.">".TEXT_NB_LINES_200."</option>";
                                $selected = ($limit_encours == 300) ? "selected" : "";
                                echo "<option value='300' ".$selected.">".TEXT_NB_LINES_300."</option>";
                                $selected = ($limit_encours == 0) ? "selected" : "";
                                echo "<option value='0' ".$selected.">".TEXT_NB_LINES_ALL."</option>";
                            echo "</select>";

                            echo "<div id='contenu_infos' style='width:97%;margin-top:10px;'>";
                                echo "<div style='display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;'>";
                                    echo "<span style='font-size:12px;'>".TEXT_NB_RA_TO_VALIDATE."</span>";
                                    echo "<input type='text' id='nb_valid_ra_input' value='' readonly style='width:50px;padding:0;font-size:12px;text-align:right;background:none;border:none;'>";
                                echo "</div>";
                                echo "<div style='display:flex;justify-content:space-between;align-items:center;'>";
                                    echo "<span style='font-size:12px;'>".TEXT_NB_TOTAL_RA."</span>";
                                    echo "<input type='text' id='nb_ra_input' value='' readonly style='width:50px;padding:0;font-size:12px;text-align:right;background:none;border:none;'>";
                                echo "</div>";
                            echo "</div>";

                        echo "</div>";
                    echo "</div>";
                    echo "<input type='hidden' value='".$modif_ra."' name='info_modif_ra' id='info_modif_ra'>";
                echo "</form>";

                echo "<div id='ra_action_bar' style='margin-bottom:8px;display:none;'>";
                    echo "<span id='ra_select_count' style='font-weight:bold;margin-right:12px;'></span>";
                    echo "<button type='button' class='hp-btn' onclick='downloadRA_multi_pdf(this);'><span class='ico'>&#128196;</span> ".TEXT_RA_EXPORT_PDF."</button>";
                    echo "<button type='button' class='hp-btn' style='margin-left:8px;' onclick='downloadRA_csv(this);'><span class='ico'>&#128202;</span> ".TEXT_RA_EXPORT_CSV."</button>";
                    echo "<button type='button' class='hp-btn' style='margin-left:8px;' onclick='downloadRA_list_pdf(this);'><span class='ico'>&#128203;</span> ".TEXT_RA_EXPORT_LIST_PDF."</button>";
                echo "</div>";

                echo "<div id='result_listRa' class='table-container' style='float:none;width:auto;height:80vh;'>";
                    echo "<div style='width:100%;height:78vh;overflow-y: auto;'>";
                        echo "<table id='table_tri' cellspacing='0'>";
                            echo "<thead>";
                                echo "<tr class='header-row'>";
                                    echo "<th style='text-align:center;width:30px;'><input type='checkbox' id='ra_select_all' onclick='raSelectAll(this);'></th>";
                                    echo "<th style='text-align:center;width:50px;'>".TEXT_TABLE_HEADER_STATUS."</th>";
                                    echo "<th style='width:100px;padding-left:20px;'>".TEXT_TABLE_HEADER_DATE."</th>";
                                    echo "<th style='width:90px;'>".TEXT_TABLE_HEADER_DATA_TYPE."</th>";
                                    echo "<th style='width:100px;'>".TEXT_TABLE_HEADER_STATION_CODE."</th>";
                                    echo "<th style='width:220px;padding-right:20px;'>".TEXT_TABLE_HEADER_STATION_NAME."</th>";
                                    echo "<th style='width:120px;'>".TEXT_TABLE_HEADER_COMMUNE."</th>";
                                    echo "<th style='width:230px;'>".TEXT_TABLE_HEADER_AGENTS."</th>";
                                    echo "<th style='width:40px;text-align:center;'></th>";
                                echo "</tr>";
                                echo "<tr><td colspan='9' style='height:15px;'>&nbsp;</td></tr>";
                            echo "</thead>";
                            echo "<tbody></tbody>";
                        echo "</table>";
                    echo "</div>";
                echo "</div>";

                echo "<div id='wait' style='width:100%;height:65px;margin-top:30px;text-align:center;'>";
                    echo "<div class='hp-loader' >";
                        echo "<div class='hp-ring'></div>";
                        echo "<div class='hp-mark'><span class='h'>H</span><span class='p'>P</span></div>";
                    echo "</div>";
                    echo "<p style='text-align:center;color:#000;'>".TEXT_LOADING."</p>";
                    echo "<p style='text-align:center;'>".TEXT_PLEASE_WAIT."</p>";
                echo "</div>";

            echo "</div>";
        echo "</div>";
    echo "</div>";

    require('include/application_bottom.php');
?>
<!-- Début du JavaScript -->
<script>
    // Initialisation des variables
    var id_user = '<?php echo $id_user; ?>';
    var territoire_id = '<?php echo $territoire_id; ?>';
    var territoire_init = '<?php echo $territoire_init; ?>';
    var timezone_php = '<?php echo $timezone_php; ?>';
    var where_ra = '<?php echo $where_ra; ?>';
    var order_ra = '<?php echo $order_ra; ?>';
    var limit_ra = '<?php echo $limit_ra; ?>';
    var where_station = '<?php echo $where_station; ?>';
    var sizeAugetFix = '<?php echo $sizeAugetFix; ?>';

    var get_ra = '<?php echo $get_ra; ?>';
    var get_typeData = '<?php echo $get_typeData; ?>';
    var get_newRa = '<?php echo $get_newRa; ?>';
    var get_st = '<?php echo $get_st; ?>';

    var blockListRA = document.getElementById('result_listRa');
    var nbRa = document.getElementById('nb_ra_input');
    var nbValidRa = document.getElementById('nb_valid_ra_input');
    var boxWait = document.getElementById('box_wait');
    var wait = document.getElementById('wait');
    var box_ra = document.getElementById('box_ra');
    var box_ra_piezoprofil = document.getElementById('box_ra_piezoprofil');
    var tbody_info = document.querySelector("#table_tri tbody");
    var boxDelRa = document.getElementById('box_del_ra');
    var contenuInfo = document.getElementById('contenu_info');
    var ra_nav_json = null;

    

    // Fonction pour afficher la liste des RA
    function loadRATab() {
        return new Promise((resolve, reject) => {
            contenuInfo.style.display = 'none';
            blockListRA.style.display = 'none';
            wait.style.display = 'block';

            var dataToSend = {
                territoire_id: territoire_id,
                where_ra: where_ra,
                order_ra: order_ra,
                limit_ra: limit_ra
            };

            var xhr = new XMLHttpRequest();
            xhr.open("POST", "include/structure/ra/process_ra_tab.php", true);
            xhr.setRequestHeader("Content-Type", "application/json");

            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    var jsonResponse = JSON.parse(xhr.responseText);
                    nb_ra_valid = jsonResponse['nb_ra_valid'];
                    nb_ra = jsonResponse['nb_ra'];
                    nbValidRa.value = (nb_ra - nb_ra_valid);
                    nbRa.value = nb_ra;
                    tbody_info.innerHTML = jsonResponse['tab_html'];
                    ra_nav_json = jsonResponse['ra_nav_json'];
                    blockListRA.style.display = 'block';
                    wait.style.display = 'none';
                    resolve();

                    if (get_ra > 0) {
                        // For piezo RAs (type 5) arrived via direct URL
                        // (?ra=XXX&td=5), auto-open the conductivity
                        // profile popup once the form is fully loaded.
                        if (parseInt(get_typeData, 10) === 5) {
                            loadRA(get_ra, get_typeData, function() {
                                affiche_RA_piezoprofil();
                            });
                        } else {
                            loadRA(get_ra, get_typeData);
                        }
                        // Consume the URL params: this auto-open is for
                        // the first arrival only. After save (or any
                        // other action that re-calls loadRATab), we
                        // don't want to re-trigger the popup or even
                        // re-load the same RA — the caller is now in
                        // charge of deciding what to do.
                        get_ra       = 0;
                        get_typeData = 0;
                    } else if (parseInt(get_newRa, 10) > 0) {
                        // ?new_ra=<type> means the user followed a deep
                        // link asking to create a new RA of the given
                        // type for the station already pre-filtered by
                        // ?st=. We call loadRA(0, type) which is the
                        // same path the "New FR - ..." button uses,
                        // and pass ?st= as the station to pre-select
                        // in the form's <select> dropdown.
                        // Consumed after the first call to avoid
                        // re-triggering on subsequent loadRATab() runs
                        // (e.g. filter changes).
                        loadRA(0, parseInt(get_newRa, 10), undefined, parseInt(get_st, 10));
                        get_newRa = 0;
                        get_st    = 0;
                    }
                }
            };
            xhr.send(JSON.stringify(dataToSend));
        });
    }

    loadRATab();

    // Fonction pour afficher la fiche RA
    //
    // Params:
    //   id_ra              — RA to load (0 for a new one)
    //   id_type_ra         — RA type code (1 = pluvio, 5 = piezo, 11 = hydro)
    //   onLoaded           — optional callback fired once the form is fully ready
    //   preselectStation   — optional station id_station to pre-fill the
    //                        "Select a Station..." dropdown when creating
    //                        a new RA. Used by deep links from other
    //                        modules (e.g. the Well log popup "+").
    function loadRA(id_ra, id_type_ra, onLoaded, preselectStation) {
        contenuInfo.style.display = 'none';
        boxWait.style.display = 'block';
        var info_modif_ra = document.getElementById('info_modif_ra').value;

        // Reset the popup-edit dirty flag when loading a new (or
        // reloaded) RA. Each RA's profile starts clean; the reminder
        // banner under the Well log button (regenerated by the loadRA
        // response) is hidden by default in the HTML, so we just
        // need to make sure the JS flag is in sync.
        profilDirty = false;

        var dataToSend = {
            territoire_id: territoire_id,
            territoire_init: territoire_init,
            sizeAugetFix: sizeAugetFix,
            timezone_php: timezone_php,
            id_user: id_user,
            id_ra: id_ra,
            where_station: where_station,
            check_modif: info_modif_ra,
            ra_nav_json: ra_nav_json,
            preselect_station: (preselectStation && preselectStation > 0) ? preselectStation : 0
        };

        var xhr = new XMLHttpRequest();
        if (id_type_ra == 1) {xhr.open("POST", "include/structure/ra/process_ra_plu_affiche.php", true);}
        if (id_type_ra == 5) {xhr.open("POST", "include/structure/ra/process_ra_piezo_affiche.php", true);}
        if (id_type_ra == 11) {xhr.open("POST", "include/structure/ra/process_ra_hydro_affiche.php", true);}
        xhr.setRequestHeader("Content-Type", "application/json");

        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                var jsonResponse = JSON.parse(xhr.responseText);
                box_ra.innerHTML = jsonResponse['tab_html'];

                if (id_type_ra == 1) lastRa_Plu();
                lists_reload();

                if (id_type_ra == 5) {

                    // Live re-render the chart on every keystroke in
                    // any of the three profile columns (depth / cond /
                    // temp). The temperature column matters because
                    // the user can switch the chart to the Temperature
                    // tab and editing temp values must reflect there.
                    var inputs = document.querySelectorAll(
                        "input[name^='piezo_profil_prof_'], "
                      + "input[name^='piezo_profil_conduct_'], "
                      + "input[name^='piezo_profil_temp_']"
                    );
                    inputs.forEach(function(input) {
                        input.addEventListener('input', f_editgraph_profil);
                    });

                }

                box_ra.style.display = 'block';
                boxWait.style.display = 'none';

                document.getElementById('check_modif_ra').addEventListener('change', function() {
                    const popupNav = document.querySelector('.modif_ok');
                    if (this.checked) {
                        popupNav.style.display = 'block';
                        document.getElementById('info_modif_ra').value = 1;
                    } else {
                        popupNav.style.display = 'none';
                        document.getElementById('info_modif_ra').value = 0;
                    }
                });

                // Initialize Select2 on select_station_ra IMMEDIATELY (its options
                // are filled directly by the PHP response, no async dependency).
                initSelect2ForRA(['#select_station_ra']);

                // For the other selects, the options come from lists_reload() which
                // is async. We must wait for it to finish before initializing Select2,
                // otherwise Select2 wraps an empty <select> and never picks up the
                // options injected later via innerHTML.
                lists_reload(function() {
                    initSelect2ForRA([
                        '#select_type_appareil',
                        '#select_num_appareil',
                        '#select_hydro_num_sonde',
                        '#select_piezo_instrument',
                        '#select_piezo_num_instrument',
                        '#select_piezo_nature_repere'
                    ]);

                    // Notify the caller that the RA form is fully ready
                    // (HTML injected + Select2 instances wired). Used when
                    // arriving on the page via ?ra=XXX&td=5 to auto-open
                    // the conductivity profile popup straight away.
                    if (typeof onLoaded === 'function') { onLoaded(); }
                });
            }
        };
        xhr.send(JSON.stringify(dataToSend));
    }

    // -----------------------------------------------
    // Télécharge la fiche RA courante au format PDF
    function downloadRA_pdf(id_ra, btnEl) {
        var prevHtml = btnEl ? btnEl.innerHTML : '';
        if (btnEl) {
            btnEl.disabled  = true;
            btnEl.innerHTML = "<span class='spinner'></span> PDF";
        }
        function restoreBtn() {
            if (btnEl) {
                btnEl.disabled  = false;
                btnEl.innerHTML = prevHtml;
            }
        }

        var dataToSend = {
            territoire_id : territoire_id,
            timezone_php  : timezone_php,
            id_user       : id_user,
            id_ra         : id_ra
        };

        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'include/structure/ra/process_ra_pdf.php', true);
        xhr.setRequestHeader('Content-Type', 'application/json');

        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                var jsonResponse = JSON.parse(xhr.responseText);
                restoreBtn();

                if (jsonResponse['status'] === 'success') {
                    window.open('data/pdf/' + jsonResponse['fileName'], '_blank');
                } else {
                    contenuInfo.innerHTML     = jsonResponse['msg_info'];
                    contenuInfo.style.border  = '2px solid #930000';
                    contenuInfo.style.display = 'block';
                }
            }
        };

        xhr.send(JSON.stringify(dataToSend));
    }

    // -----------------------------------------------
    // Multi-selection handling for the RA list
    // -----------------------------------------------

    // Return the list of checked RA ids
    function getSelectedRA() {
        var ids = [];
        document.querySelectorAll('.ra_select_cb:checked').forEach(function(cb) {
            ids.push(parseInt(cb.value, 10));
        });
        return ids;
    }

    // Called whenever a row checkbox changes: refresh the action bar
    function raSelectionChanged() {
        var ids = getSelectedRA();
        var bar = document.getElementById('ra_action_bar');
        var count = document.getElementById('ra_select_count');
        if (ids.length > 0) {
            bar.style.display = 'block';
            count.innerHTML = ids.length + ' ' + TEXT_RA_SELECTED;
        } else {
            bar.style.display = 'none';
            count.innerHTML = '';
        }
        // keep the "select all" box in sync
        var all = document.getElementById('ra_select_all');
        var boxes = document.querySelectorAll('.ra_select_cb');
        if (all) { all.checked = (boxes.length > 0 && ids.length === boxes.length); }
    }

    // "Select all" header checkbox
    function raSelectAll(cb) {
        document.querySelectorAll('.ra_select_cb').forEach(function(box) {
            box.checked = cb.checked;
        });
        raSelectionChanged();
    }

    // -----------------------------------------------
    // Multi-PDF: generate one PDF for all selected RA
    function downloadRA_multi_pdf(btnEl) {
        var ids = getSelectedRA();
        if (ids.length === 0) { return; }

        var prevHtml = btnEl ? btnEl.innerHTML : '';
        if (btnEl) {
            btnEl.disabled  = true;
            btnEl.innerHTML = "<span class='spinner'></span> PDF";
        }
        function restoreBtn() {
            if (btnEl) { btnEl.disabled = false; btnEl.innerHTML = prevHtml; }
        }

        var dataToSend = {
            territoire_id : territoire_id,
            timezone_php  : timezone_php,
            id_user       : id_user,
            list_id_ra    : ids
        };

        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'include/structure/ra/process_ra_pdf.php', true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                var jsonResponse = JSON.parse(xhr.responseText);
                restoreBtn();
                if (jsonResponse['status'] === 'success') {
                    window.open('data/pdf/' + jsonResponse['fileName'], '_blank');
                } else {
                    contenuInfo.innerHTML     = jsonResponse['msg_info'];
                    contenuInfo.style.border  = '2px solid #930000';
                    contenuInfo.style.display = 'block';
                }
            }
        };
        xhr.send(JSON.stringify(dataToSend));
    }

    // -----------------------------------------------
    // CSV export of all selected RA
    function downloadRA_csv(btnEl) {
        var ids = getSelectedRA();
        if (ids.length === 0) { return; }

        var prevHtml = btnEl ? btnEl.innerHTML : '';
        if (btnEl) {
            btnEl.disabled  = true;
            btnEl.innerHTML = "<span class='spinner'></span> CSV";
        }
        function restoreBtn() {
            if (btnEl) { btnEl.disabled = false; btnEl.innerHTML = prevHtml; }
        }

        var dataToSend = {
            territoire_id : territoire_id,
            timezone_php  : timezone_php,
            id_user       : id_user,
            list_id_ra    : ids
        };

        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'include/structure/ra/process_ra_csv.php', true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                var jsonResponse = JSON.parse(xhr.responseText);
                restoreBtn();
                if (jsonResponse['status'] === 'success') {
                    window.open('data/csv/' + jsonResponse['fileName'], '_blank');
                } else {
                    contenuInfo.innerHTML     = jsonResponse['msg_info'];
                    contenuInfo.style.border  = '2px solid #930000';
                    contenuInfo.style.display = 'block';
                }
            }
        };
        xhr.send(JSON.stringify(dataToSend));
    }

    // -----------------------------------------------
    // Summary list PDF of all selected RA
    function downloadRA_list_pdf(btnEl) {
        var ids = getSelectedRA();
        if (ids.length === 0) { return; }

        var prevHtml = btnEl ? btnEl.innerHTML : '';
        if (btnEl) {
            btnEl.disabled  = true;
            btnEl.innerHTML = "<span class='spinner'></span> PDF";
        }
        function restoreBtn() {
            if (btnEl) { btnEl.disabled = false; btnEl.innerHTML = prevHtml; }
        }

        var dataToSend = {
            territoire_id : territoire_id,
            timezone_php  : timezone_php,
            id_user       : id_user,
            list_id_ra    : ids
        };

        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'include/structure/ra/process_ra_list_pdf.php', true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                var jsonResponse = JSON.parse(xhr.responseText);
                restoreBtn();
                if (jsonResponse['status'] === 'success') {
                    window.open('data/pdf/' + jsonResponse['fileName'], '_blank');
                } else {
                    contenuInfo.innerHTML     = jsonResponse['msg_info'];
                    contenuInfo.style.border  = '2px solid #930000';
                    contenuInfo.style.display = 'block';
                }
            }
        };
        xhr.send(JSON.stringify(dataToSend));
    }

    // Initialize (or re-initialize) Select2 on a list of <select> elements.
    // Safely destroys any existing Select2 instance before re-applying, which
    // is necessary because loadRA() can be called multiple times and Select2
    // throws if applied twice on the same element.
    function initSelect2ForRA(selectors) {
        var configs = {
            '#select_station_ra':         { placeholder: '<?php echo TEXT_SELECT2_STATION_PLACEHOLDER; ?>',           allowClear: true },
            '#select_type_appareil':      { placeholder: '<?php echo TEXT_SELECT2_TYPE_PLACEHOLDER; ?>',              tags: true, allowClear: true },
            '#select_num_appareil':       { placeholder: '<?php echo TEXT_SELECT2_NUMBER_PLACEHOLDER; ?>',            tags: true, allowClear: true },
            '#select_hydro_num_sonde':    { placeholder: '<?php echo TEXT_SELECT2_PROBE_NUMBER_PLACEHOLDER; ?>',      tags: true, allowClear: true },
            '#select_piezo_instrument':   { placeholder: '<?php echo TEXT_SELECT2_INSTRUMENT_PLACEHOLDER; ?>',        tags: true, allowClear: true },
            '#select_piezo_num_instrument':{ placeholder: '<?php echo TEXT_SELECT2_INSTRUMENT_NUMBER_PLACEHOLDER; ?>', tags: true, allowClear: true },
            '#select_piezo_nature_repere':   { placeholder: '<?php echo TEXT_SELECT2_BENCHMARK_TYPE_PLACEHOLDER; ?>',        tags: true, allowClear: true }
        };

        selectors.forEach(function(selector) {
            var $el = $(selector);
            if ($el.length === 0) return;
            if ($el.hasClass('select2-hidden-accessible')) {
                $el.select2('destroy');
            }
            if (configs[selector]) {
                $el.select2(configs[selector]);
            }
        });
    }

    // Autres fonctions JavaScript (inchangées)
    function lastRa_Plu() {
        let info_LastRa = document.getElementById('info_last_ra');
        let select_raIdStation = document.getElementById('select_station_ra').value;
        let saisie_raDataReleve = document.getElementById('date_releve').value;
        let saisie_raHeureReleve = document.getElementById('heure_releve').value;

        var dataToSend = {
            territoire_id: territoire_id,
            territoire_init: territoire_init,
            sizeAugetFix: sizeAugetFix,
            timezone_php: timezone_php,
            id_user: id_user,
            select_raIdStation: select_raIdStation,
            saisie_raDataReleve: saisie_raDataReleve,
            saisie_raHeureReleve: saisie_raHeureReleve,
        };

        if(territoire_init == 'NC')
        {
            var xhr = new XMLHttpRequest();
            xhr.open("POST", "include/structure/ra/process_ra_plu_last.php", true);
            xhr.setRequestHeader("Content-Type", "application/json");

            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    var jsonResponse = JSON.parse(xhr.responseText);
                    info_LastRa.innerHTML = jsonResponse['tab_html'];
                    raPlu_calculPlaceholder(sizeAugetFix);
                }
            };
            xhr.send(JSON.stringify(dataToSend));
        }
    }

    function raPlu_calculPlaceholder(sizeAuget) 
    {
        // Helper: read a numeric input value safely. Returns 0 if the element
        // doesn't exist in the DOM (typical case: first RA for a station, no
        // previous pluviometry record to compare against).
        function getVal(id) {
            var el = document.getElementById(id);
            return (el && el.value !== '') ? parseFloat(el.value) || 0 : 0;
        }

        // Helper: assign a placeholder safely. Silently no-ops if the element
        // is not present, which prevents this function from crashing the page.
        function setPlaceholder(id, value) {
            var el = document.getElementById(id);
            if (el) { el.placeholder = value; }
        }

        var last_plu_tot   = getVal('last_plu_tot');
        var last_plu_basc  = getVal('last_plu_basc');
        var plu_basc       = getVal('plu_nb_basculement');
        var plu_tot_first  = getVal('plu_tot_first');

        var calc_plu_cumul_tot = plu_tot_first - last_plu_tot;
        setPlaceholder('plu_cumul_tot', calc_plu_cumul_tot);

        var calc_plu_cumul_plu = (plu_basc - last_plu_basc) * sizeAuget;
        setPlaceholder('plu_cumul_plu', calc_plu_cumul_plu);

        var calc_diff_tot_plu = calc_plu_cumul_tot - calc_plu_cumul_plu;
        setPlaceholder('plu_diff_tot_plu', calc_diff_tot_plu);
    }

    function lists_reload(callback) {
        let select_type_appareil = document.getElementById('select_type_appareil');
        let select_num_appareil = document.getElementById('select_num_appareil');
        let select_hydro_num_sonde = document.getElementById('select_hydro_num_sonde');
        let select_piezo_instrument = document.getElementById('select_piezo_instrument');
        let select_piezo_num_instrument = document.getElementById('select_piezo_num_instrument');
        let select_piezo_nature_repere = document.getElementById('select_piezo_nature_repere');

        let typeData = document.getElementById('type_data')?.value || 0;
        let select_raIdStation = document.getElementById('select_station_ra')?.value || 0;
        let nomAppareil = document.getElementById('nom_appareil')?.value || '';
        let numAppareil = document.getElementById('num_appareil')?.value || '';
        let hydroNumSonde = document.getElementById('hydro_num_sonde')?.value || '';
        let piezoInstrument = document.getElementById('piezo_instrument')?.value || '';
        let piezoNumInstrument = document.getElementById('piezo_num_instrument')?.value || '';
        let piezoNatureRepere = document.getElementById('piezo_nature_repere')?.value || '';

        var dataToSend = {
            territoire_id: territoire_id,
            territoire_init: territoire_init,
            sizeAugetFix: sizeAugetFix,
            timezone_php: timezone_php,
            id_user: id_user,
            typeData: typeData,
            select_raIdStation: select_raIdStation,
            nomAppareil: nomAppareil,
            numAppareil: numAppareil,
            hydroNumSonde: hydroNumSonde,
            piezoInstrument: piezoInstrument,
            piezoNumInstrument: piezoNumInstrument,
            piezoNatureRepere: piezoNatureRepere
        };

        var xhr = new XMLHttpRequest();
        xhr.open("POST", "include/structure/ra/process_ra_lists.php", true);
        xhr.setRequestHeader("Content-Type", "application/json");

        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                var jsonResponse = JSON.parse(xhr.responseText);
                if (select_type_appareil) select_type_appareil.innerHTML = jsonResponse['html_typeAppareil'];
                if (select_num_appareil) select_num_appareil.innerHTML = jsonResponse['html_numAppareil'];
                if (select_hydro_num_sonde) select_hydro_num_sonde.innerHTML = jsonResponse['html_hydroNumSonde'];
                if (select_piezo_instrument) select_piezo_instrument.innerHTML = jsonResponse['html_piezoNomSondeManuelle'];
                if (select_piezo_num_instrument) select_piezo_num_instrument.innerHTML = jsonResponse['html_piezoNumSondeManuelle'];                
                if (select_piezo_nature_repere) select_piezo_nature_repere.innerHTML = jsonResponse['html_piezoNatureRepere'];

                // Notify caller that the lists are ready (used by loadRA to init
                // Select2 only after the options have been injected).
                if (typeof callback === 'function') {
                    callback();
                }
            }
        };
        xhr.send(JSON.stringify(dataToSend));
    }

    function saveRA(event) {
        boxWait.style.display = 'block';
        event.preventDefault();
        var form = document.getElementById('formRA');
        var formData = new FormData(form);
        formData.append('territoire_id', territoire_id);
        formData.append('id_user_agent', id_user);

        var xhr = new XMLHttpRequest();
        xhr.open("POST", "include/structure/ra/process_ra_save.php", true);

        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                var jsonResponse = JSON.parse(xhr.responseText);
                erreur = jsonResponse['erreur'];
                new_ra = jsonResponse['new_ra'];
                id_ra = jsonResponse['id_ra'];
                type_data = jsonResponse['type_data'];
                msg_info = jsonResponse['msg_info'];

                if (!erreur) {
                    // Save succeeded → clear the popup-edit dirty flag
                    // and any reminder banner that may have been shown.
                    // The new RA form HTML rebuilt by loadRA() will
                    // include a fresh (hidden) reminder div anyway,
                    // but we reset the JS state for correctness.
                    profilDirty = false;
                    hideProfilReminder();

                    // Close the well-log profile popup if it was open
                    // when the user clicked Save. The reload below
                    // regenerates the form HTML (including a fresh,
                    // hidden #box_ra_piezoprofil), but explicitly
                    // hiding it first avoids any flash of the old one
                    // and guarantees we don't carry stale state.
                    var boxProfil = document.getElementById('box_ra_piezoprofil');
                    if (boxProfil) { boxProfil.style.display = 'none'; }

                    loadRATab().then(() => {
                        loadRA(id_ra, type_data);
                        contenuInfo.innerHTML = msg_info;
                        contenuInfo.style.display = 'block';
                        contenuInfo.style.border = '2px solid #09886d';
                    });
                } else {
                    contenuInfo.innerHTML = msg_info;
                    contenuInfo.style.display = 'block';
                    contenuInfo.style.border = '2px solid #930000';
                    boxWait.style.display = 'none';
                }
            }
        };
        xhr.send(formData);
    }

    // Réponse attendue du challenge math en cours (popup suppression RA)
    var challengeExpectedRa = null;

    function verifDelRA(id_ra) {
        var dataToSend = { id_ra: id_ra };
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "include/structure/ra/process_ra_verifdelete.php", true);
        xhr.setRequestHeader("Content-Type", "application/json");

        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                var jsonResponse = JSON.parse(xhr.responseText);
                boxDelRa.innerHTML = jsonResponse['tab_html'];
                boxDelRa.style.display = 'block';

                // Génère le challenge math et branche la validation live.
                // Le HTML vient d'être injecté : on initialise ici pour
                // garantir l'accès aux éléments fraîchement créés.
                initRaChallenge();
            }
        };
        xhr.send(JSON.stringify(dataToSend));
    }

    // Active / désactive le bouton Supprimer de la popup RA
    function setRaConfirmEnabled(enabled) {
        var okDelRa = document.getElementById('del_ra');
        if (!okDelRa) { return; }
        okDelRa.disabled      = !enabled;
        okDelRa.style.opacity = enabled ? '1' : '0.45';
        okDelRa.style.cursor  = enabled ? 'pointer' : 'not-allowed';
    }

    // Génère un nouveau calcul aléatoire (+, -, x)
    function generateRaChallenge() {
        var operators = ['+', '-', 'x'];
        var op = operators[Math.floor(Math.random() * operators.length)];
        var a, b, result;

        if (op === '+') {
            a = Math.floor(Math.random() * 16) + 5;   // 5..20
            b = Math.floor(Math.random() * 14) + 2;   // 2..15
            result = a + b;
        } else if (op === '-') {
            a = Math.floor(Math.random() * 21) + 10;  // 10..30
            b = Math.floor(Math.random() * (a - 1)) + 1;
            result = a - b;
        } else {
            a = Math.floor(Math.random() * 8) + 2;    // 2..9
            b = Math.floor(Math.random() * 8) + 2;    // 2..9
            result = a * b;
        }

        var qEl = document.getElementById('challenge_question_ra');
        var aEl = document.getElementById('challenge_answer_ra');
        var fEl = document.getElementById('challenge_feedback_ra');

        challengeExpectedRa = result;
        if (qEl) { qEl.textContent = a + ' ' + op + ' ' + b + ' = '; }
        if (aEl) { aEl.value = ''; }
        if (fEl) { fEl.textContent = ''; }
        setRaConfirmEnabled(false);
    }

    // Valide la réponse saisie en direct
    function validateRaChallenge() {
        var aEl = document.getElementById('challenge_answer_ra');
        var fEl = document.getElementById('challenge_feedback_ra');
        if (!aEl) { return; }

        var val = parseInt(aEl.value, 10);
        if (aEl.value === '' || isNaN(val)) {
            if (fEl) { fEl.textContent = ''; }
            setRaConfirmEnabled(false);
            return;
        }
        if (val === challengeExpectedRa) {
            if (fEl) { fEl.textContent = '\u2713'; fEl.style.color = '#09886d'; }
            setRaConfirmEnabled(true);
        } else {
            if (fEl) { fEl.textContent = '\u2717'; fEl.style.color = '#a52834'; }
            setRaConfirmEnabled(false);
        }
    }

    // Initialise le challenge après injection du récap RA
    function initRaChallenge() {
        generateRaChallenge();

        var aEl = document.getElementById('challenge_answer_ra');
        if (aEl) {
            aEl.addEventListener('input', validateRaChallenge);
            aEl.addEventListener('keydown', function(event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    var okDelRa = document.getElementById('del_ra');
                    if (okDelRa && !okDelRa.disabled) { okDelRa.click(); }
                }
            });
            aEl.focus();
        }
    }

    function delRA(id_ra) {
        var dataToSend = { id_ra: id_ra, id_user_agent: id_user };
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "include/structure/ra/process_ra_delete.php", true);
        xhr.setRequestHeader("Content-Type", "application/json");

        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                loadRATab().then(() => {
                    var jsonResponse = JSON.parse(xhr.responseText);
                    boxDelRa.style.display = 'none';
                    msg_info = jsonResponse['msg_info'];
                    del = jsonResponse['del'];
                    contenuInfo.innerHTML = msg_info;
                    contenuInfo.style.display = 'block';
                    contenuInfo.style.border = del ? '2px solid #09886d' : '2px solid #930000';
                });
            }
        };
        xhr.send(JSON.stringify(dataToSend));
    }

    // =============================================================
    // Conductivity profile popup — box_ra_piezoprofil
    // =============================================================
    // Visually aligned on the Well log popup (see block_diag.php):
    //   - Draggable header (mousedown on #box_ra_piezoprofil_header)
    //   - Resizable container (CSS resize:both on #box_ra_piezoprofil)
    //   - The left table (30 rows × 3 inputs) is the source of truth.
    //   - The chart reflects the table; editing the chart writes back
    //     to the table, then re-renders.
    //
    // Total point capacity = 30 rows (matches the PHP-generated inputs).

    var PROFIL_MAX_ROWS = 30;

    // Which value is plotted on X and edited by drag/contextmenu:
    //   'cond' → conductivity × depth
    //   'temp' → temperature × depth
    // The active tab is also the one being edited.
    var profilActiveTab = 'cond';

    // Set to true as soon as the user drags, adds or removes a point
    // in the popup. When the popup is closed while dirty, a reminder
    // banner appears under the "Well log" button to nudge the user
    // to save the RA. The flag is cleared only on a successful save.
    var profilDirty = false;


    // Show / hide the under-button reminder.
    function showProfilReminder() {
        var el = document.getElementById('profil_save_reminder');
        if (el) { el.style.display = 'block'; }
    }
    function hideProfilReminder() {
        var el = document.getElementById('profil_save_reminder');
        if (el) { el.style.display = 'none'; }
    }


    // Switch the active tab. Called by the tab buttons' click handler.
    // Re-renders the chart with the new value on X.
    function profilSwitchTab(tab) {
        if (tab !== 'cond' && tab !== 'temp') return;
        profilActiveTab = tab;

        var btnCond = document.getElementById('profil_tab_cond');
        var btnTemp = document.getElementById('profil_tab_temp');
        if (btnCond && btnTemp) {
            var activeStyle   = 'background:#176B87;color:#fff;';
            var inactiveStyle = 'background:#fff;color:#176B87;';
            // Toggle the inline styles that drive the visual state.
            // (We can't rely solely on the .is-active class because the
            // initial styling is inline — see process_ra_piezo_affiche.php.)
            btnCond.classList.toggle('is-active', tab === 'cond');
            btnTemp.classList.toggle('is-active', tab === 'temp');
            applyProfilTabStyle(btnCond, tab === 'cond');
            applyProfilTabStyle(btnTemp, tab === 'temp');
        }

        f_editgraph_profil();
    }

    function applyProfilTabStyle(btn, isActive) {
        if (!btn) return;
        if (isActive) {
            btn.style.background = '#176B87';
            btn.style.color      = '#fff';
        } else {
            btn.style.background = '#fff';
            btn.style.color      = '#176B87';
        }
    }


    function affiche_RA_piezoprofil() {

        var box     = document.getElementById('box_ra_piezoprofil');
        var plotDiv = document.getElementById('plot_profil');
        if (!box || !plotDiv) return;

        // Opening the popup: the reminder is no longer relevant — the
        // user is back in the editing context. We re-hide it now; if
        // they make further changes and close again, it'll come back.
        hideProfilReminder();

        box.style.display = 'block';

        // Watch for the popup being hidden (X, Escape, click outside…)
        // and show the under-button reminder if dirty edits remain.
        // MutationObserver covers all the close paths (the inline X
        // onclick, the Escape handler, the close handler in block_ra,
        // etc.) without coupling to any of them.
        if (!box._closeObserver) {
            box._closeObserver = new MutationObserver(function() {
                if (box.style.display === 'none' && profilDirty) {
                    showProfilReminder();
                }
            });
            box._closeObserver.observe(box, { attributes: true, attributeFilter: ['style'] });
        }

        // Keep the Plotly chart responsive when the popup is resized
        if (!box._resizeWired) {
            var resizeObserver = new ResizeObserver(function() {
                if (plotDiv.data) {
                    Plotly.relayout(plotDiv, { autosize: true });
                }
            });
            resizeObserver.observe(plotDiv);
            box._resizeWired = true;
        }

        // Escape closes the popup (no state to preserve — the underlying
        // form holds the data)
        if (!box._escWired) {
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && box.style.display !== 'none') {
                    box.style.display = 'none';
                }
            });
            box._escWired = true;
        }

        // Draggable header — mirrors the Well log popup
        wireProfilDragOnce();

        // Edit-mode handlers (drag-to-move + right-click add/remove).
        // These are wired once on document with delegation, so they
        // work even though the popup HTML is re-rendered on every
        // loadRA() call.
        wireProfilEditOnce();

        // Tab switch handlers — wired each time we open the popup,
        // because the buttons are recreated by loadRA().
        var btnCond = document.getElementById('profil_tab_cond');
        var btnTemp = document.getElementById('profil_tab_temp');
        if (btnCond && !btnCond._wired) {
            btnCond.addEventListener('click', function() { profilSwitchTab('cond'); });
            btnCond._wired = true;
        }
        if (btnTemp && !btnTemp._wired) {
            btnTemp.addEventListener('click', function() { profilSwitchTab('temp'); });
            btnTemp._wired = true;
        }

        // Reset to conductivity tab when reopening (consistent default)
        profilActiveTab = 'cond';
        applyProfilTabStyle(btnCond, true);
        applyProfilTabStyle(btnTemp, false);

        f_editgraph_profil();
    }


    // -------------------------------------------------------------
    // Draggable popup
    function wireProfilDragOnce() {
        var box    = document.getElementById('box_ra_piezoprofil');
        var header = document.getElementById('box_ra_piezoprofil_header');
        if (!box || !header || header._dragWired) return;
        header._dragWired = true;

        var dragging = false, offX = 0, offY = 0;

        header.addEventListener('mousedown', function(e) {
            if (e.target && e.target.id === 'button_close') return;
            dragging = true;
            var rect = box.getBoundingClientRect();
            offX = e.clientX - rect.left;
            offY = e.clientY - rect.top;
            e.preventDefault();
        });

        document.addEventListener('mousemove', function(e) {
            if (!dragging) return;
            var newLeft = e.clientX - offX;
            var newTop  = e.clientY - offY;
            var maxLeft = window.innerWidth  - 80;
            var maxTop  = window.innerHeight - 30;
            if (newLeft < -box.offsetWidth + 80) { newLeft = -box.offsetWidth + 80; }
            if (newTop  < 0)                     { newTop  = 0; }
            if (newLeft > maxLeft)               { newLeft = maxLeft; }
            if (newTop  > maxTop)                { newTop  = maxTop; }
            box.style.left = newLeft + 'px';
            box.style.top  = newTop  + 'px';
        });

        document.addEventListener('mouseup', function() { dragging = false; });
    }


    // -------------------------------------------------------------
    // Edit-mode handlers (drag points + right-click add/remove)
    // Wired ONCE on document — the closest('#plot_profil') filter
    // ensures these handlers only act when the cursor is inside the
    // chart's area.
    var profilDragState = null;

    function wireProfilEditOnce() {
        if (window._profilEditWired) return;
        window._profilEditWired = true;

        // Mousedown on a point starts a drag (we capture early so
        // Plotly's own drag handler doesn't pan the chart)
        document.addEventListener('mousedown', function(e) {
            if (e.button !== 0) return;
            var plotEl = document.getElementById('plot_profil');
            if (!plotEl || !plotEl.contains(e.target)) return;

            var hit = profilHitTestPoint(e, plotEl);
            if (hit === null) return;

            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();

            profilDragState = { rowIndex: hit, plotEl: plotEl, moved: false };
            plotEl.style.cursor = 'grabbing';
        }, true);

        document.addEventListener('mousemove', function(e) {
            if (!profilDragState) return;
            e.stopPropagation();
            var plotEl = profilDragState.plotEl;
            var coord  = profilPixelToData(e, plotEl);
            if (!coord) return;

            // Write back to the inputs that correspond to the dragged
            // row. profilDragState.rowIndex is 1-based (matches the
            // PHP-generated input IDs).
            // The X value goes either to conduct or to temp, depending
            // on which tab is active.
            var rowIdx = profilDragState.rowIndex;
            var profEl  = document.getElementById('piezo_profil_prof_' + rowIdx);
            var valueEl = document.getElementById(
                (profilActiveTab === 'temp' ? 'piezo_profil_temp_' : 'piezo_profil_conduct_') + rowIdx
            );
            if (!profEl || !valueEl) return;

            // The chart shows depth as y * -1; flip back to positive for
            // the input. Round to a sensible precision so the input
            // value stays readable.
            profEl.value  = Math.abs(coord.y).toFixed(2);
            valueEl.value = (profilActiveTab === 'temp')
                          ? coord.x.toFixed(2)   // temperature: 2 decimals
                          : coord.x.toFixed(0);  // conductivity: integer

            // Live re-render WITHOUT touching the axes. Using restyle
            // updates only the trace's x/y arrays — the chart range
            // stays fixed at whatever Plotly.newPlot last set it to,
            // so the view doesn't jump as the user drags a point near
            // the edge.
            //
            // Find which point in the current trace matches our row
            // index (via customdata), update its x/y, push back.
            if (plotEl.data && plotEl.data[0]) {
                var tr = plotEl.data[0];
                var xs = tr.x.slice();
                var ys = tr.y.slice();
                var cd = tr.customdata || [];
                for (var i = 0; i < cd.length; i++) {
                    if (cd[i] === rowIdx) {
                        xs[i] = coord.x;
                        ys[i] = coord.y;
                        break;
                    }
                }
                Plotly.restyle(plotEl, { x: [xs], y: [ys] }, [0]);
            }
            profilDragState.moved = true;
        }, true);

        // On mouseup, we DO NOT re-render the chart — that would
        // recompute the axis ranges and recentre the view, which is
        // exactly the jump the user is trying to avoid. The trace
        // already shows the final position (set live by Plotly.restyle
        // during the mousemove). The inputs are already up to date.
        //
        // Note: if a dragged point ends up off the current axis range,
        // it will appear cropped until the next full render (which
        // happens on contextmenu add/remove, tab switch, or manual
        // input edit). That's the trade-off the user explicitly asked
        // for.
        document.addEventListener('mouseup', function() {
            if (!profilDragState) return;
            if (profilDragState.moved) { profilDirty = true; }
            profilDragState.plotEl.style.cursor = '';
            profilDragState = null;
        }, true);

        // Right-click: on a point → clear that row (3 inputs);
        // on empty area inside the chart → fill the next free row
        document.addEventListener('contextmenu', function(e) {
            var plotEl = document.getElementById('plot_profil');
            if (!plotEl || !plotEl.contains(e.target)) return;

            e.preventDefault();
            e.stopPropagation();

            var hit = profilHitTestPoint(e, plotEl);
            if (hit !== null) {
                // Clear that row (prof + conduct + temp)
                var profEl    = document.getElementById('piezo_profil_prof_'    + hit);
                var conductEl = document.getElementById('piezo_profil_conduct_' + hit);
                var tempEl    = document.getElementById('piezo_profil_temp_'    + hit);
                if (profEl)    profEl.value    = '';
                if (conductEl) conductEl.value = '';
                if (tempEl)    tempEl.value    = '';
                profilDirty = true;
                f_editgraph_profil();
                return;
            }

            // No point under cursor → try first to insert in the middle
            // of an existing segment (if the cursor is close to a line
            // between two consecutive points), otherwise append at the
            // end (next free row).
            var coord = profilPixelToData(e, plotEl);
            if (!coord || !isFinite(coord.x) || !isFinite(coord.y)) return;

            var segHit = profilHitTestSegment(e, plotEl);
            if (segHit !== null) {
                // Insert between rows segHit.afterRow and segHit.beforeRow.
                // We push down every input from segHit.beforeRow onwards
                // by one slot, then write the new point in the freed slot.
                profilInsertPointAtRow(segHit.beforeRow, coord);
                profilDirty = true;
                f_editgraph_profil();
                return;
            }

            // Plain append at the end (next free row)
            var nextRow = profilFindNextFreeRow();
            if (nextRow === -1) {
                // All 30 rows are taken — silently ignore (the user can
                // free a row by right-clicking an existing point).
                return;
            }
            var profEl  = document.getElementById('piezo_profil_prof_' + nextRow);
            var valueEl = document.getElementById(
                (profilActiveTab === 'temp' ? 'piezo_profil_temp_' : 'piezo_profil_conduct_') + nextRow
            );
            if (!profEl || !valueEl) return;
            profEl.value  = Math.abs(coord.y).toFixed(2);
            valueEl.value = (profilActiveTab === 'temp')
                          ? coord.x.toFixed(2)
                          : coord.x.toFixed(0);
            profilDirty = true;
            f_editgraph_profil();
        }, true);
    }


    // Hit-test on the SEGMENTS between consecutive points. Returns
    // { afterRow, beforeRow } — the two row indices that bracket the
    // segment the cursor is currently over (within 12 px perpendicular
    // distance), or null if the cursor isn't near any segment.
    //
    // afterRow = the row index of the upper point of the segment
    // beforeRow = the row index of the lower point (the new point
    //             will be inserted before this row).
    function profilHitTestSegment(e, plotEl) {
        if (!plotEl._fullLayout || !plotEl.data || !plotEl.data[0]) return null;
        var tr = plotEl.data[0];
        if (!tr.x || tr.x.length < 2 || !tr.customdata) return null;

        var rect = plotEl.getBoundingClientRect();
        var cx = e.clientX - rect.left;
        var cy = e.clientY - rect.top;
        var xa = plotEl._fullLayout.xaxis;
        var ya = plotEl._fullLayout.yaxis;
        if (!xa || !ya) return null;

        var THRESH = 18; // pixel distance threshold
        var best = null, bestD2 = THRESH * THRESH + 1;

        for (var i = 0; i < tr.x.length - 1; i++) {
            // Endpoints of the segment in pixel space
            var p1x = xa.l2p(tr.x[i])     + xa._offset;
            var p1y = ya.l2p(tr.y[i])     + ya._offset;
            var p2x = xa.l2p(tr.x[i + 1]) + xa._offset;
            var p2y = ya.l2p(tr.y[i + 1]) + ya._offset;

            // Compute perpendicular distance from cursor to the segment.
            // Standard formula: project cursor onto the line, clamp to
            // [0, 1] along the segment, distance to projection.
            var vx = p2x - p1x, vy = p2y - p1y;
            var len2 = vx * vx + vy * vy;
            if (len2 < 1) continue; // degenerate segment

            var t = ((cx - p1x) * vx + (cy - p1y) * vy) / len2;
            if (t < 0 || t > 1) continue;        // outside the segment span

            var projX = p1x + t * vx;
            var projY = p1y + t * vy;
            var dx = cx - projX, dy = cy - projY;
            var d2 = dx * dx + dy * dy;

            if (d2 < bestD2) {
                bestD2 = d2;
                best = {
                    afterRow:  tr.customdata[i],
                    beforeRow: tr.customdata[i + 1]
                };
            }
        }
        return best;
    }


    // Insert a new point in the input table just BEFORE the given row
    // index. All inputs from beforeRow onwards are shifted down by one
    // slot to make room, then the new prof + (cond OR temp depending
    // on active tab) are written into the freed slot. The third value
    // (the one not on the active axis) is left empty for that row —
    // the user can fill it in the table afterwards.
    //
    // If the last row is already filled, the bottom-most point is
    // silently dropped — we have at most PROFIL_MAX_ROWS slots.
    function profilInsertPointAtRow(beforeRow, coord) {
        // Shift values down from the last row to beforeRow+1
        for (var i = PROFIL_MAX_ROWS; i > beforeRow; i--) {
            var srcProf    = document.getElementById('piezo_profil_prof_'    + (i - 1));
            var srcConduct = document.getElementById('piezo_profil_conduct_' + (i - 1));
            var srcTemp    = document.getElementById('piezo_profil_temp_'    + (i - 1));
            var dstProf    = document.getElementById('piezo_profil_prof_'    + i);
            var dstConduct = document.getElementById('piezo_profil_conduct_' + i);
            var dstTemp    = document.getElementById('piezo_profil_temp_'    + i);
            if (!srcProf || !dstProf) continue;
            dstProf.value    = srcProf.value;
            dstConduct.value = srcConduct.value;
            dstTemp.value    = srcTemp.value;
        }
        // Write the new point in the freed slot at index beforeRow.
        // We fill prof + (cond OR temp), and clear the third value to
        // avoid carrying over a stale value from before the shift.
        var newProf    = document.getElementById('piezo_profil_prof_'    + beforeRow);
        var newConduct = document.getElementById('piezo_profil_conduct_' + beforeRow);
        var newTemp    = document.getElementById('piezo_profil_temp_'    + beforeRow);
        if (!newProf) return;
        newProf.value = Math.abs(coord.y).toFixed(2);
        if (profilActiveTab === 'temp') {
            if (newTemp)    newTemp.value    = coord.x.toFixed(2);
            if (newConduct) newConduct.value = '';
        } else {
            if (newConduct) newConduct.value = coord.x.toFixed(0);
            if (newTemp)    newTemp.value    = '';
        }
    }


    // Hit-test on the single conductivity-profile trace. Returns the
    // ROW INDEX (1..30, matching the input IDs) of the closest point
    // within 14 px of the cursor, or null. We use customdata (set by
    // f_editgraph_profil) to map a point in the trace back to its
    // table row.
    function profilHitTestPoint(e, plotEl) {
        if (!plotEl._fullLayout || !plotEl.data || !plotEl.data[0]) return null;
        var tr = plotEl.data[0];
        if (!tr.x || !tr.customdata) return null;

        var rect = plotEl.getBoundingClientRect();
        var cx = e.clientX - rect.left;
        var cy = e.clientY - rect.top;
        var xa = plotEl._fullLayout.xaxis;
        var ya = plotEl._fullLayout.yaxis;
        if (!xa || !ya) return null;

        var bestRow = null, bestD2 = 22 * 22 + 1;
        for (var i = 0; i < tr.x.length; i++) {
            var px = xa.l2p(tr.x[i]) + xa._offset;
            var py = ya.l2p(tr.y[i]) + ya._offset;
            var dx = px - cx, dy = py - cy;
            var d2 = dx * dx + dy * dy;
            if (d2 < bestD2) { bestD2 = d2; bestRow = tr.customdata[i]; }
        }
        return bestRow;
    }


    // Translate a mouse event into (x, y) coords in the chart's data space.
    function profilPixelToData(e, plotEl) {
        if (!plotEl._fullLayout) return null;
        var rect = plotEl.getBoundingClientRect();
        var xa = plotEl._fullLayout.xaxis;
        var ya = plotEl._fullLayout.yaxis;
        if (!xa || !ya) return null;
        var x = xa.p2l(e.clientX - rect.left - xa._offset);
        var y = ya.p2l(e.clientY - rect.top  - ya._offset);
        return { x: x, y: y };
    }


    // Return the index (1..PROFIL_MAX_ROWS) of the first row whose
    // depth input is empty, or -1 if all rows are taken. The depth
    // column drives row occupancy because every plotted point (whether
    // it's a conductivity or temperature one) needs a depth.
    function profilFindNextFreeRow() {
        for (var i = 1; i <= PROFIL_MAX_ROWS; i++) {
            var p = document.getElementById('piezo_profil_prof_' + i);
            if (!p) continue;
            if (p.value === '') { return i; }
        }
        return -1;
    }


    // -------------------------------------------------------------
    // Render / re-render the chart from the inputs.
    //
    // The active tab decides what's plotted on X:
    //   'cond' → conductivity values, rows kept = (prof AND conduct filled)
    //   'temp' → temperature values,  rows kept = (prof AND temp filled)
    //
    // Each kept point carries customdata = its row index (1..30) so the
    // edit handlers can map a clicked point back to the source row.
    function f_editgraph_profil() {
        var xData = [];
        var yData = [];
        var rows  = [];

        var isTemp = (profilActiveTab === 'temp');
        var valueInputPrefix = isTemp ? 'piezo_profil_temp_' : 'piezo_profil_conduct_';

        for (var i = 1; i <= PROFIL_MAX_ROWS; i++) {
            var profElement  = document.getElementById('piezo_profil_prof_' + i);
            var valueElement = document.getElementById(valueInputPrefix    + i);

            var profValue  = 0;
            var valueValue = 0;

            if (profElement && profElement.value !== '') {
                profValue = (-1) * parseFloat(profElement.value);
            }
            if (valueElement && valueElement.value !== '') {
                valueValue = parseFloat(valueElement.value);
            }

            // Keep rows that have BOTH the depth AND the active-tab value.
            // 0 is rejected too: a true "0 cm" depth point at the surface
            // isn't a typical profile point (and 0 conductivity / 0 °C
            // doesn't happen in practice either).
            if (!isNaN(profValue) && !isNaN(valueValue)
                && profValue !== 0 && valueValue !== 0) {
                xData.push(valueValue);
                yData.push(profValue);
                rows.push(i);
            }
        }

        var Xmax = xData.length ? Math.max.apply(null, xData) : 1;
        var Ymin = yData.length ? Math.min.apply(null, yData) : -1;

        // Axis labels and hover labels switch with the active tab
        var xAxisLabel, hoverLabel, hoverFmt;
        if (isTemp) {
            xAxisLabel = '<?php echo TEXT_TEMPERATURE; ?> [&deg;C]';
            hoverLabel = '<?php echo TEXT_TEMPERATURE; ?>';
            hoverFmt   = '%{x:.2f}';
        } else {
            xAxisLabel = 'Conductivité [&mu;S/cm]';
            hoverLabel = '<?php echo TEXT_CONDUCTIVITY; ?>';
            hoverFmt   = '%{x:.0f}';
        }

        var data_profil = {
            x: xData,
            y: yData,
            customdata: rows,         // row index 1..30 for hit-test mapping
            mode: 'markers+lines',
            type: 'scatter',
            marker: { size: 8, color: '#176B87' },
            line:   { color: '#176B87' },
            hovertemplate:
                '<b>' + hoverLabel + '</b>: ' + hoverFmt + '<br>' +
                '<b><?php echo TEXT_DEPTH; ?></b>: %{y:.2f}' +
                '<extra></extra>'
        };

        var config = {
            responsive: true,
            doubleClickDelay: 1000,
            displayModeBar: true,
            // Mouse-wheel zoom is enabled so the user can quickly focus
            // on a depth range while placing points. Drag-to-pan is
            // disabled (dragmode:false below) — our own mousedown
            // captures the drag on a point, and pan would only get in
            // the way when the user clicks just next to a point.
            scrollZoom: true,
            modeBarButtonsToRemove: ['select2d', 'lasso2d', 'autoScale2d', 'zoomIn2d', 'zoomOut2d'],
            modeBarOrientation: 'v',
            displaylogo: false
        };

        var layout_profil = {
            xaxis: {
                title: { text: xAxisLabel, standoff: 0 },
                tickfont: { size: 11 },
                titlefont: { family: 'roboto, arial, helvetica', size: 14, bold: true, color: '#000000' },
                tickangle: 0, ticklen: 5, showline: true, linewidth: 1,
                automargin: true,
                // 20% headroom on Xmax so the user has elbow room to
                // drop new points beyond the current maximum.
                range: [0, (Xmax * 1.2)],
                side: 'top'
            },
            yaxis: {
                title: { text: 'Profondeur [m]', standoff: 0 },
                tickfont: { size: 11 },
                titlefont: { family: 'roboto, arial, helvetica', size: 14, bold: true, color: '#000000' },
                tickformat: ',.1f', ticklen: 5, showline: true, linewidth: 1,
                automargin: true,
                // 20% headroom past Ymin (= deeper) for the same reason.
                range: [(Ymin * 1.2), 0]
            },
            hovermode: 'closest',
            hoverlabel: { bgcolor: '#fff', bordercolor: '#888', font: { size: 12, color: '#000' } },
            margin: { l: 80, r: 30, t: 75, b: 10 },
            showlegend: false,
            dragmode: false   // our own drag handlers below take over
        };

        Plotly.newPlot('plot_profil', [data_profil], layout_profil, config);
    }

    function hydro_calcDiff() {
        let hydro_h_echelle_1 = document.getElementById('hydro_h_echelle_1');
        let hydro_h_sonde = document.getElementById('hydro_h_sonde');
        let hech_hsonde = document.getElementById('hech_hsonde');

        let valeurEchelle = parseFloat(hydro_h_echelle_1.value) || 0;
        let valeurSonde = parseFloat(hydro_h_sonde.value) || 0;

        if (isNaN(valeurEchelle) || isNaN(valeurSonde)) {
            hech_hsonde.value = '';
            return;
        }
        let diff = valeurEchelle - valeurSonde;
        hech_hsonde.value = Math.round(diff * 10) / 10;
    }

    function display_FieldRA() {
		let displayFieldRa = document.getElementById('displayFieldRa');
		if (displayFieldRa) {
			if (displayFieldRa.style.display === 'none') {
				displayFieldRa.style.display = 'block';
				document.getElementById('toggleFieldsLink').innerHTML = '<?php echo TEXT_TOGGLE_HIDE_FIELDS ?>' ;
			} else {
				displayFieldRa.style.display = 'none';
				document.getElementById('toggleFieldsLink').innerHTML = '<?php echo TEXT_TOGGLE_SHOW_FIELDS ?>' ;
			}
		}
	}
</script>