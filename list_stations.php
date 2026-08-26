<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Station list page with selection options
- Station deletion
- Station status update (active / monitoring / out of service)
- Left panel: filters and sort controls
----------------------------------------
*/

// -----------------------------------------------
// Bootstrap: config, session, application init

require('include/application_top.php');


// -----------------------------------------------
// Initialize variables

$message_modifEtat_station = '';
$row = 0;


// -----------------------------------------------
// Sort field selection

$tri_encours = 1;
$tri         = "s.nom_station";

if (isset($_POST['select_tri']))
{
    $tri_encours = $_POST['select_tri'];
    if ($_POST['select_tri'] == 1) { $tri = "s.nom_station"; }
    if ($_POST['select_tri'] == 2) { $tri = "s.code_station"; }
    if ($_POST['select_tri'] == 3) { $tri = "c.nom_commune"; }
    if ($_POST['select_tri'] == 4) { $tri = "s.station_type"; }
}

$tri_order_encours = 1;
$tri_order         = " ASC,";

if (isset($_POST['order_tri']))
{
    $tri_order_encours = $_POST['order_tri'];
    if ($_POST['order_tri'] == 1) { $tri_order = " ASC,"; }
    if ($_POST['order_tri'] == 2) { $tri_order = " DESC,"; }
}


// -----------------------------------------------
// Filter visibility flags

$affiche_select_from         = true;
$affiche_select_type         = true;
$affiche_select_tournee      = false; //($gestion_data > 0);
$affiche_search              = false;
$affiche_select_riviere      = false;
$affiche_select_station      = false;
$affiche_select_statut_station = true;

require(DIR_WS_FILTRE . 'filtre_stations_var.php');


// -----------------------------------------------
// Query: Station list for display

$station_array      = [];
$nb_station         = 0;
$nb_station_active  = 0;
$nb_station_suivi   = 0;
$nb_station_armee   = 0;

$sql_station = "SELECT DISTINCT s.id_station, s.id_station_old, s.id_region, s.id_commune,
                       s.nom_station, s.code_station, s.vallee_station,
                       s.date_installation_station, s.date_fermeture_station,
                       s.active_station, s.suivi, s.armee, s.station_type,
                       s.id_tournee, s.id_regionhydro, s.id_riviere
                FROM " . TABLE_STATION . " s
                LEFT JOIN " . TABLE_STATION_TO_TOURNEE . " t ON t.id_station = s.id_station
                WHERE s.id_territoire = " . $territoire_id . $where_and_from
                . $where_search . $where_and_regionhydro . $where_and_region
                . $where_and_commune . $where_and_riviere . $where_and_type
                . $where_and_tournee . $where_and_active . $where_and_suivi . $where_and_armee
                . " ORDER BY " . $tri . $tri_order . " s.active_station DESC, s.suivi DESC, s.armee ASC";

$station_query = tep_db_query($sql_link, $sql_station);

while ($station = tep_db_fetch_array($station_query))
{
    $id_region   = $station['id_region'];
    $id_eq_type  = $station['station_type'];
    $nom_station = html_entity_decode($station['nom_station']  ?? '');
    $code_station= html_entity_decode($station['code_station'] ?? '');

    $id_commune  = $station['id_commune'];
    $nom_commune = isset($commune_array[$id_commune]) ? $commune_array[$id_commune] : '';

    $nom_region  = isset($region_array[$id_region]) ? $region_array[$id_region] : '';

    $id_regionhydro  = $station['id_regionhydro'];
    $nom_regionhydro = isset($regionhydro_array[$id_regionhydro]) ? $regionhydro_array[$id_regionhydro] : '';

    $id_riviere  = $station['id_riviere'];
    $nom_riviere = isset($riviere_array[$id_riviere]) ? $riviere_array[$id_riviere] : '';

    $date_installation_station = dateus_fr($station['date_installation_station']);
    $date_fermeture_station    = dateus_fr($station['date_fermeture_station']);

    $active_station = 0;
    if ($station['active_station'] == 1) { $active_station = 1; $nb_station_active++; }

    $suivi_station = 0;
    if ($station['suivi'] == 1) { $suivi_station = 1; $nb_station_suivi++; }

    $armee_station = 0;
    if ($station['armee'] == 1) { $armee_station = 1; $nb_station_armee++; }

    $station_array[$station['id_station']] = [
        'id_old'                   => $station['id_station_old'],
        'active_station'           => $active_station,
        'suivi_station'            => $suivi_station,
        'armee_station'            => $armee_station,
        'nom_station'              => $nom_station,
        'code_station'             => $code_station,
        'id_eq_type'               => $id_eq_type,
        'nom_region'               => $nom_region,
        'nom_regionhydro'          => $nom_regionhydro,
        'nom_riviere'              => $nom_riviere,
        'id_commune'               => $id_commune,
        'nom_commune'              => $nom_commune,
        'id_region'                => $id_region,
        'date_installation_station'=> $date_installation_station,
        'date_fermeture_station'   => $date_fermeture_station,
    ];
}
$nb_station = sizeof($station_array);


// -----------------------------------------------
// Query: Number of distinct series per station (from TABLE_DATA_META)

$sql_meta_data = "SELECT COUNT(DISTINCT m.id_typedata) AS nb_diff_typedata, s.id_station
                  FROM " . TABLE_DATA_META . " m
                  JOIN " . TABLE_STATION   . " s ON m.id_station  = s.id_station
                  JOIN " . TABLE_REGION    . " r ON s.id_region   = r.id_region
                  JOIN " . TABLE_COMMUNE   . " c ON s.id_commune  = c.id_commune
                  WHERE r.id_territoire = " . $territoire_id
                . $where_search . $where_and_type . $where_and_region . $where_and_commune . $where_and_active
                . " GROUP BY m.id_station";

$meta_query = tep_db_query($sql_link, $sql_meta_data);
while ($meta_tab = tep_db_fetch_array($meta_query))
{
    $nb_meta_array[$meta_tab['id_station']] = ['nb_diff_typedata' => $meta_tab['nb_diff_typedata']];
}


// -----------------------------------------------
// Query: Activity report count and most recent date per station

$sql_ra = "SELECT COUNT(*) AS nb_ra, s.id_station, MAX(ra.date_heure_ra) AS date_heure_ra_recente
           FROM " . TABLE_DATA_RA . " ra
           JOIN " . TABLE_STATION . " s ON ra.id_station = s.id_station
           JOIN " . TABLE_REGION  . " r ON s.id_region  = r.id_region
           JOIN " . TABLE_COMMUNE . " c ON s.id_commune = c.id_commune
           WHERE r.id_territoire = " . $territoire_id
         . $where_search . $where_and_type . $where_and_region . $where_and_commune . $where_and_active
         . " GROUP BY ra.id_station";

$ra_query = tep_db_query($sql_link, $sql_ra);
while ($nb_ra_tab = tep_db_fetch_array($ra_query))
{
    $nb_ra_array[$nb_ra_tab['id_station']] = $nb_ra_tab['nb_ra'];

    $date_parts = explode(' ', $nb_ra_tab['date_heure_ra_recente']);
    $last_ra_array[$nb_ra_tab['id_station']] = dateus_fr($date_parts[0]);
}


// -----------------------------------------------
// HTML output

require(DIR_WS_STRUCTURE . 'header_web.php');

echo "<body>";

    // Toolbar-button styling, aligned with the station sheet (PDF/Excel exports):
    // text button with an entity icon and the shared .spinner during long actions.
    echo "
    <style>
        .hp-btn {
            background:#fff; border:1px solid #d4d4d4; border-radius:6px;
            padding:6px 12px; font-size:13px; cursor:pointer;
            display:inline-flex; align-items:center; gap:6px; color:#2c2c2a;
            line-height:1; margin-right:8px;
        }
        .hp-btn:hover { background:#f0f4f6; }
        .hp-btn[disabled] { opacity:0.7; cursor:default; }
        .hp-btn span.ico { font-size:15px; }
        .hp-btn .spinner { width:14px; height:14px; border-width:2px; margin-right:0; }
    </style>";

    echo "<div id='contenu_info' style='display:none;'></div>";

    require(DIR_WS_STRUCTURE . 'header.php');
    include(DIR_WS_BOX . 'nav_accueil.php');

    echo "<div id='contour_general'>";
        echo "<div id='contenu_centre'>";

            // ---- Page title + XLS download button ----
            echo "<h1>";
                echo "<span style='float:left;'>" . TEXT_STATION_LIST_TITLE . "</span>";

                echo "<div style='float:left;margin-left:15px;'>";
                    echo "<button type='button' class='hp-btn' id='img_file'"
                       . " title='" . TEXT_STATION_LIST_DOWNLOAD_TITLE . "'"
                       . " onClick='downloadStation_xls(this);'>"
                       . "<span class='ico'>&#128202;</span> Excel</button>";
                echo "</div>";
            echo "</h1>";


            // ---- Left panel: filters + sort + counters ----
            echo "<div style='float:left;width:272px;height:calc(100% - 115px);'>\n";

                if (HP_VERSION == 'Serveur' && $gestion_data > 0)
                {
                    echo "<div style='width:245px;margin-bottom:10px;display:flex;justify-content:center;'>\n";
                        echo "<div id='button_titre' style='width:200px;text-align:center;box-sizing:border-box;'"
                           . " onclick=\"window.open('modif_station.php?new=1', '_blank');\">\n";
                            echo TEXT_STATION_LIST_NEW;
                        echo "</div>\n";
                    echo "</div>";
                }

                echo "<div id='boxpopup' class='select-top'"
                   . " style='width:230px;max-height:calc(100% - 20px);overflow-y:auto;margin:0;margin-bottom:5px;padding:0 8px;padding-top:10px;'>\n";

                    $lien_form = tep_href_link('list_stations.php');
                    $name_form = 'form_station';

                    echo "<form name='" . $name_form . "' action='" . $lien_form . "' method='post' enctype='multipart/form-data'>";
                        echo "<div style='width:220px;'>";

                            require(DIR_WS_FILTRE . 'filtre_stations_html.php');

                            echo "<hr>";

                            // ---- Sort controls ----
                            echo "<div style='width:100%;border-bottom:2px solid #176B87;margin-top:0;'></div>";

                            echo "<p style='float:left;width:60px;padding-top:5px;margin-top:15px;'>"
                               . TEXT_STATION_SORT_BY . "</p>";

                            echo "<select name='select_tri' id='select_tri'"
                               . " onchange='" . $name_form . ".submit();'"
                               . " style='float:right;width:140px;margin-top:15px;'>";

                                $selected = ($tri_encours == 1) ? 'selected' : '';
                                echo "<option value='1' " . $selected . ">" . TEXT_STATION_SORT_NAME  . "</option>";

                                $selected = ($tri_encours == 2) ? 'selected' : '';
                                echo "<option value='2' " . $selected . ">" . TEXT_STATION_SORT_CODE  . "</option>";

                                $selected = ($tri_encours == 4) ? 'selected' : '';
                                echo "<option value='4' " . $selected . ">" . TEXT_STATION_SORT_TYPE  . "</option>";

                            echo "</select>";

                            echo "<hr>";

                            echo "<div style='float:right;'>";
                                $asc_checked  = ($tri_order_encours == 1) ? 'checked' : '';
                                $desc_checked = ($tri_order_encours == 2) ? 'checked' : '';

                                echo "<p style='float:left;width:55px;padding-top:3px;'>"  . TEXT_STATION_SORT_ASC  . "</p>";
                                echo "<input type='radio' id='asc'  name='order_tri' value='1' style='float:left;' " . $asc_checked
                                   . " onchange='" . $name_form . ".submit();'>";

                                echo "<p style='float:left;width:65px;margin-left:10px;padding-top:3px;'>" . TEXT_STATION_SORT_DESC . "</p>";
                                echo "<input type='radio' id='desc' name='order_tri' value='2' style='float:left;' " . $desc_checked
                                   . " onchange='" . $name_form . ".submit();'>";
                            echo "</div>";

                            // ---- Station counters ----
                            echo "<div id='contenu_infos' style='width:97%;margin:10px 0;'>";
                                echo "<p>";
                                    echo "<span style='display:block;'>" . TEXT_STATION_NB_TOTAL  . " : " . number_format($nb_station,        0, '.', ' ') . "</span>";
                                    echo "<span style='display:block;'>" . TEXT_STATION_NB_ACTIVE . " : " . number_format($nb_station_active, 0, '.', ' ') . "</span>";
                                    echo "<span style='display:block'>" . TEXT_STATION_NB_SUIVI  . " : " . number_format($nb_station_suivi,  0, '.', ' ') . "</span>";
                                    echo "<span>" . TEXT_STATION_NB_ARMEE  . " : " . number_format($nb_station_armee,  0, '.', ' ') . "</span>";
                                echo "</p>";
                            echo "</div>";

                            echo "<hr>";

                        echo "</div>";
                    echo "</form>";
                echo "</div>";
            echo "</div>";


            // ---- Station table ----
            if (isset($station_array) && ($nb_station > 0))
            {
                echo "<div style='float:none;height:calc(100% - 100px);overflow-y:auto;margin-top:10px;'>";
                    echo "<table id='table_tri' cellspacing='0'>";

                        // ---- Table header ----
                        echo "<thead><tr class='header-row'>";

                            echo "<th style='width:100px;padding-left:20px;'>"  . TEXT_STATION_COL_TYPE    . "</th>";
                            echo "<th style='width:120px;'>"                    . TEXT_STATION_COL_CODE    . "</th>";
                            echo "<th style='width:220px;'>"                    . TEXT_STATION_COL_NOM     . "</th>";
                            echo "<th style='width:150px;padding-left:5px;'>"   . TEXT_STATION_COL_COMMUNE . "</th>";

                            if ($gestion_data > 0)
                            {
                                echo "<th style='width:140px;padding-left:5px;' title='" . TEXT_STATION_COL_REGIONHYDRO_TITLE . "'>"
                                   . TEXT_STATION_COL_REGIONHYDRO . "</th>";
                            }

                            echo "<th style='width:120px;padding-left:5px;'>" . $territoire_region . "</th>";

                            echo "<th style='width:100px;' title='" . TEXT_STATION_COL_INSTALLATION_TITLE . "'>"
                               . TEXT_STATION_COL_INSTALLATION . "</th>";

                            if ($gestion_data > 0)
                            {
                                echo "<th style='width:100px;' title='" . TEXT_STATION_COL_VISITE_TITLE . "'>"
                                   . TEXT_STATION_COL_VISITE . "</th>";
                                echo "<th style='width:50px;text-align:center;' title='" . TEXT_STATION_COL_NB_RA_TITLE . "'>"
                                   . TEXT_STATION_COL_NB_RA . "</th>";
                            }

                            echo "<th style='width:50px;text-align:center;' title='" . TEXT_STATION_COL_EXPORT_TITLE . "'>"
                               . TEXT_STATION_COL_EXPORT . "</th>";

                            if (HP_VERSION == 'Serveur' && $gestion_data > 0)
                            {
                                echo "<th style='width:50px;'>&nbsp;</th>";
                            }

                        echo "</tr></thead>";


                        // ---- Table rows ----
                        foreach ($station_array as $key => $value)
                        {
                            $row_l = (fmod((int)$row, 2) == 0)
                                ? "class='row1' onmouseover=\"this.className='row1hover';\" onmouseout=\"this.className='row1';\""
                                : "class='row2' onmouseover=\"this.className='row2hover';\" onmouseout=\"this.className='row2';\"";

                            $color_type = '';
                            if (tep_not_null($eq_type_array[$value['id_eq_type']]['type_color_border']))
                            {
                                $color_type = 'color:' . $eq_type_array[$value['id_eq_type']]['type_color_border'] . ';';
                            }

                            $nb_ra            = isset($nb_ra_array[$key])   ? $nb_ra_array[$key]   : 0;
                            $nb_diff_typedata = isset($nb_meta_array[$key]) ? $nb_meta_array[$key]['nb_diff_typedata'] : 0;
                            $last_ra          = isset($last_ra_array[$key]) ? $last_ra_array[$key] : '';

                            $lien_modif   = "modif_station.php?ref=" . $key;
                            $click_action = "onClick=\"location.href='" . $lien_modif . "'\" style='cursor:pointer;'";

                            echo "<tr id='station_row_" . $key . "' " . $row_l . ">";

                                echo "<td style='padding-left:20px;" . $color_type . "cursor:pointer;' " . $click_action . ">"
                                   . $eq_type_array[$value['id_eq_type']]['nom_eq_type'] . "</td>\n";

                                echo "<td style='cursor:pointer;' " . $click_action . ">" . $value['code_station'] . "</td>\n";

                                echo "<td style='cursor:pointer;' " . $click_action . " title='" . $value['nom_station'] . "'>"
                                   . affichelettres($value['nom_station'], 50) . "</td>\n";

                                echo "<td style='padding-left:5px;cursor:pointer;' " . $click_action . " title='" . $value['nom_commune'] . "'>"
                                   . affichelettres($value['nom_commune'], 30) . "</td>\n";

                                if ($gestion_data > 0)
                                {
                                    echo "<td style='padding-left:5px;cursor:pointer;' " . $click_action . ">"
                                       . $value['nom_regionhydro'] . "</td>\n";
                                }

                                echo "<td style='padding-left:5px;cursor:pointer;' " . $click_action . ">"
                                   . $value['nom_region'] . "</td>\n";

                                echo "<td style='padding-left:5px;cursor:pointer;' " . $click_action . ">"
                                   . $value['date_installation_station'] . "</td>\n";

                                if ($gestion_data > 0)
                                {
                                    echo "<td style='padding-left:5px;'>" . $last_ra . "</td>\n";
                                    echo "<td style='text-align:center;cursor:pointer;' " . $click_action . ">" . $nb_ra . "</td>\n";
                                }

                                echo "<td style='text-align:center;'>";
                                    echo "<input type='checkbox' name='check_export_" . $key . "' value='" . $key . "' checked>";
                                echo "</td>\n";

                                if (HP_VERSION == 'Serveur' && $gestion_data > 0)
                                {
                                    echo "<td style='text-align:center;'>";
                                    if ($nb_ra < 1 && $nb_diff_typedata < 1)
                                    {
                                        echo "<a style='font-size:12px;font-weight:bold;cursor:pointer;'"
                                            . " title='" . TEXT_STATION_COL_DELETE_TITLE . "'"
                                            . " onClick=\"openStationDelete('" . $key . "','" . htmlspecialchars($value['nom_station'], ENT_QUOTES) . "')\""
                                            . ">X</a>";
                                    }
                                    else { echo "-"; }
                                    echo "</td>\n";
                                }

                            echo "</tr>\n";
                            $row++;
                        }

                    echo "</table>";
                echo "</div>";
            }
            else
            {
                echo "<div id='boxpopup' style='margin-left:1%;'>\n";
                    echo "<p class='alert'>" . TEXT_STATION_NONE_FOUND . "</p>";
                echo "</div>";
            }

        echo "</div>";
    echo "</div>";


    // -----------------------------------------------
    // Station deletion confirmation popup (with math challenge)
    // Replaces the native confirm_suppr() dialog. On confirm, the JS
    // sends an AJAX request to process_station_delete.php, then removes
    // the station row from the table on success (no page reload).

    echo "<div id='box_del_station' class='block_view'"
       . " style='position:fixed;top:0;left:0;width:100%;height:100%;"
       . "background:rgba(0,0,0,0.45);z-index:9999;display:none;'>\n";

        echo "<div style='position:relative;width:520px;margin:8% auto 0 auto;"
           . "background-color:#FBF9F1;border-radius:6px;overflow:hidden;"
           . "box-shadow:0 8px 30px rgba(0,0,0,0.35);'>\n";

            // Red header
            echo "<p style='margin:0;padding:14px 20px;font-size:17px;font-weight:bold;"
               . "color:#fff;background-color:#a52834;'>"
               . TEXT_STATION_DEL_CONFIRM_TITLE . "</p>\n";

            echo "<div style='padding:18px 22px;'>";

                // Warning message
                echo "<p style='margin:0 0 14px 0;font-size:14px;color:#333;'>"
                   . TEXT_STATION_DEL_CONFIRM_MSG . "</p>";

                // Station highlighted block (red left border, light red bg)
                echo "<div style='border-left:4px solid #a52834;background-color:#fbeaec;"
                   . "padding:10px 14px;margin-bottom:18px;'>";
                    echo "<span style='font-size:14px;font-weight:bold;color:#333;'>"
                       . TEXT_STATION_DEL_STATION_LABEL . " : </span>";
                    echo "<span id='del_station_name' style='font-size:14px;color:#333;'></span>";
                echo "</div>";

                // Math challenge block
                echo "<div style='border:1px solid #ddd;border-radius:4px;"
                   . "padding:14px 16px;margin-bottom:18px;background:#fff;'>";
                    echo "<p style='margin:0 0 10px 0;font-size:13px;color:#666;'>"
                       . TEXT_STATION_DEL_CHALLENGE_LABEL . "</p>";
                    echo "<div style='display:flex;align-items:center;gap:10px;'>";
                        echo "<span id='challenge_question_st' style='font-size:18px;"
                           . "font-weight:bold;color:#000;'></span>";
                        echo "<input type='text' id='challenge_answer_st' autocomplete='off'"
                           . " style='width:80px;height:30px;font-size:16px;text-align:center;'>";
                        echo "<span id='challenge_feedback_st' style='font-size:18px;"
                           . "font-weight:bold;'></span>";
                    echo "</div>";
                echo "</div>";

                // Buttons
                echo "<div style='display:flex;justify-content:flex-end;gap:12px;'>";
                    echo "<input type='button' id='cancel_del_station' class='button_close'"
                       . " value='" . TEXT_STATION_DEL_BTN_CANCEL . "' style='width:120px;'>";
                    echo "<input type='button' id='ok_del_station' class='button'"
                       . " value='" . TEXT_STATION_DEL_BTN_CONFIRM . "' disabled"
                       . " style='width:120px;opacity:0.45;cursor:not-allowed;'>";
                echo "</div>";

            echo "</div>";

        echo "</div>";
    echo "</div>";


    require('include/application_bottom.php');

echo "</body>";
echo "</html>";
?>

<script>

    var idTerritoire = <?php echo $territoire_id; ?>;
    var id_user      = '<?php echo $id_user; ?>';

    // JS error/status messages injected from PHP constants
    var LANG_STATION = {
        noSelection : '<?= TEXT_STATION_JS_NO_SELECTION ?>',
        errGenerate : '<?= TEXT_STATION_JS_ERR_GENERATE ?>',
        errServer   : '<?= TEXT_STATION_JS_ERR_SERVER ?>'
    };

    var contenuInfo = document.getElementById('contenu_info');
    var imgFile     = document.getElementById('img_file');


    // -----------------------------------------------
    // Download selected stations as XLS file

    function downloadStation_xls(btnEl)
    {
        var prevXlsHtml = btnEl ? btnEl.innerHTML : '';
        if (btnEl)
        {
            btnEl.disabled  = true;
            btnEl.innerHTML = "<span class='spinner'></span> Excel";
        }
        function restoreXlsBtn()
        {
            if (btnEl)
            {
                btnEl.disabled  = false;
                btnEl.innerHTML = prevXlsHtml;
            }
        }

        // Step 1: Collect checked station IDs
        var checkboxes       = document.querySelectorAll("input[type='checkbox'][name^='check_export_']");
        var selectedStations = [];

        checkboxes.forEach(function(checkbox)
        {
            if (checkbox.checked) { selectedStations.push(checkbox.value); }
        });

        if (selectedStations.length === 0)
        {
            contenuInfo.innerHTML    = LANG_STATION.noSelection;
            contenuInfo.style.border = '2px solid #930000';
            contenuInfo.style.display = 'block';
            restoreXlsBtn();
            return;
        }

        // Step 2: Send station list to server for XLS generation
        var cheminFolder = 'data/export/temp';

        var dataToSend = {
            idTerritoire : idTerritoire,
            listStation  : selectedStations.join(','),
            cheminFolder : cheminFolder
        };

        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'include/structure/export/process_station_download_xls.php', true);
        xhr.setRequestHeader('Content-Type', 'application/json');

        xhr.onreadystatechange = function()
        {
            if (xhr.readyState === 4)
            {
                if (xhr.status === 200)
                {
                    var jsonResponse = JSON.parse(xhr.responseText);

                    if (jsonResponse['statut'])
                    {
                        // Trigger download via invisible link
                        var downloadLink      = document.createElement('a');
                        downloadLink.href     = cheminFolder + '/' + jsonResponse['xlsFile'];
                        downloadLink.download = jsonResponse['xlsFile'];
                        document.body.appendChild(downloadLink);
                        downloadLink.click();
                        document.body.removeChild(downloadLink);
                    }
                    else
                    {
                        contenuInfo.innerHTML    = LANG_STATION.errGenerate;
                        contenuInfo.style.border = '2px solid #930000';
                        contenuInfo.style.display = 'block';
                    }
                }
                else
                {
                    contenuInfo.innerHTML    = LANG_STATION.errServer;
                    contenuInfo.style.border = '2px solid #930000';
                    contenuInfo.style.display = 'block';
                }

                restoreXlsBtn();
            }
        };

        xhr.send(JSON.stringify(dataToSend));
    }


    // -----------------------------------------------
    // Station deletion popup with math challenge
    // -----------------------------------------------

    var boxDelStation     = document.getElementById('box_del_station');
    var delStationName     = document.getElementById('del_station_name');
    var okDelStation       = document.getElementById('ok_del_station');
    var cancelDelStation   = document.getElementById('cancel_del_station');

    var challengeQuestionSt = document.getElementById('challenge_question_st');
    var challengeAnswerSt   = document.getElementById('challenge_answer_st');
    var challengeFeedbackSt = document.getElementById('challenge_feedback_st');

    // Current expected answer + the station id to delete on confirm
    var challengeExpectedSt = null;
    var delStationId        = null;


    // Open the popup for a given station (called from the "X" link)
    function openStationDelete(idStation, nom)
    {
        delStationId               = idStation;
        delStationName.textContent = nom;
        boxDelStation.style.display = 'block';
        generateStationChallenge();
        challengeAnswerSt.focus();
    }


    // Toggle the Confirm button enabled / disabled
    function setStationConfirmEnabled(enabled)
    {
        if (!okDelStation) return;
        okDelStation.disabled      = !enabled;
        okDelStation.style.opacity = enabled ? '1' : '0.45';
        okDelStation.style.cursor  = enabled ? 'pointer' : 'not-allowed';
    }


    // Generate a new random challenge (+, -, or x); small numbers, easy
    // mental math but enough to block a reflex click.
    function generateStationChallenge()
    {
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

        challengeExpectedSt          = result;
        challengeQuestionSt.textContent = a + ' ' + op + ' ' + b + ' = ';
        challengeAnswerSt.value         = '';
        challengeFeedbackSt.textContent = '';
        setStationConfirmEnabled(false);
    }


    // Live-validate the typed answer
    function validateStationChallenge()
    {
        var val = parseInt(challengeAnswerSt.value, 10);
        if (challengeAnswerSt.value === '' || isNaN(val)) {
            challengeFeedbackSt.textContent = '';
            setStationConfirmEnabled(false);
            return;
        }
        if (val === challengeExpectedSt) {
            challengeFeedbackSt.textContent = '\u2713';   // ✓
            challengeFeedbackSt.style.color = '#609966';
            setStationConfirmEnabled(true);
        } else {
            challengeFeedbackSt.textContent = '\u2717';   // ✗
            challengeFeedbackSt.style.color = '#a52834';
            setStationConfirmEnabled(false);
        }
    }


    // Close / reset the popup
    function closeStationDelete()
    {
        boxDelStation.style.display = 'none';
        delStationId         = null;
        challengeExpectedSt  = null;
    }


    // Perform the deletion via AJAX, then remove the row on success
    function confirmStationDelete()
    {
        if (okDelStation.disabled || !delStationId) { return; }

        // Lock the button during the request to avoid double-submit
        setStationConfirmEnabled(false);

        var idToDelete = delStationId;

        var dataToSend = {
            id_station    : idToDelete,
            id_user_agent : id_user
        };

        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'include/structure/station/process_station_delete.php', true);
        xhr.setRequestHeader('Content-Type', 'application/json');

        xhr.onreadystatechange = function()
        {
            if (xhr.readyState !== 4) { return; }

            if (xhr.status === 200)
            {
                var jsonResponse;
                try { jsonResponse = JSON.parse(xhr.responseText); }
                catch (e) { jsonResponse = null; }

                if (jsonResponse && jsonResponse.del)
                {
                    // Success: remove the station row, close the popup
                    var row = document.getElementById('station_row_' + idToDelete);
                    if (row) { row.parentNode.removeChild(row); }

                    closeStationDelete();

                    // Show the confirmation message returned by the server
                    contenuInfo.innerHTML     = jsonResponse.msg_info;
                    contenuInfo.style.border  = '2px solid #09886d';
                    contenuInfo.style.display = 'block';
                }
                else
                {
                    // Server refused the deletion (records present / not found)
                    contenuInfo.innerHTML = (jsonResponse && jsonResponse.msg_info)
                                          ? jsonResponse.msg_info
                                          : LANG_STATION.errServer;
                    contenuInfo.style.border  = '2px solid #930000';
                    contenuInfo.style.display = 'block';
                    closeStationDelete();
                }
            }
            else
            {
                contenuInfo.innerHTML     = LANG_STATION.errServer;
                contenuInfo.style.border  = '2px solid #930000';
                contenuInfo.style.display = 'block';
                closeStationDelete();
            }
        };

        xhr.send(JSON.stringify(dataToSend));
    }


    if (challengeAnswerSt) {
        challengeAnswerSt.addEventListener('input', validateStationChallenge);

        // Enter key validates and confirms if the answer is correct
        challengeAnswerSt.addEventListener('keydown', function(event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                confirmStationDelete();
            }
        });
    }

    if (okDelStation) {
        okDelStation.addEventListener('click', confirmStationDelete);
    }

    if (cancelDelStation) {
        cancelDelStation.addEventListener('click', closeStationDelete);
    }

    // Close on click outside the popup card / Escape key
    if (boxDelStation) {
        boxDelStation.addEventListener('click', function(event) {
            if (event.target === boxDelStation) { closeStationDelete(); }
        });
    }
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' && boxDelStation
            && boxDelStation.style.display === 'block') {
            closeStationDelete();
        }
    });

</script>