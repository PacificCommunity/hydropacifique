<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Station edit page
- Entry point for all station-related data
- Displays series data and activity report summary
- For piezo stations: adds Benchmarks and Well Characteristics tabs
- Manages station photos
----------------------------------------
*/

// -----------------------------------------------
// Bootstrap: config, session, application init

require('include/application_top.php');


// -----------------------------------------------
// Initialize variables

$action                    = false;
$message_info              = '';
$message_suprr_liaison     = '';
$error_station             = false;
$row                       = 0;
$reference                 = '';
$libelle                   = '';
$today                     = new DateTime();
$today_us                  = date('Y-m-d');
$today_fr                  = date('d-m-Y');
$date_format               = 'd-m-Y';
$id_region                 = $region_default;
$id_commune                = 0;
$id_station                = 0;
$id_station_old            = '';
$nb_ra_Avalider            = 0;
$nb_ra_valid               = 0;
$nb_ra                     = 0;
$last_datetime_ra_Avalider = '';
$last_datetime_ra_valid    = '';
$html_tab_data_station     = '';
$html_tab_code_cal         = '';
$chron_data_array          = [];

$tab_orientation = ['Nord', 'Nord-Est', 'Est', 'Sud-Est', 'Sud', 'Sud-Ouest', 'Ouest', 'Nord-Ouest'];

$modif = false;


// -----------------------------------------------
// Read station ID from GET (edit mode)

if (isset($_GET['ref']))
{
    $id_station = mysqli_real_escape_string($sql_link, trim(addslashes($_GET['ref'])));
    $modif = true;
}


// -----------------------------------------------
// Query: Equipment / data types (Pluvio, Hydro, Piezo, ...)

$sql_eq_type   = "SELECT DISTINCT id_eq_type, nom_eq_type, type_color_border, type_color_background
                  FROM " . TABLE_EQ_TYPE . "
                  WHERE active_eq_type = 1
                  ORDER BY order_eq_type ASC";
$eq_type_query = tep_db_query($sql_link, $sql_eq_type);
while ($eq_type = tep_db_fetch_array($eq_type_query))
{
    $eq_type_array[$eq_type['id_eq_type']] = [
        'nom_eq_type'            => html_entity_decode($eq_type['nom_eq_type']            ?? ''),
        'type_color_border'      => html_entity_decode($eq_type['type_color_border']      ?? ''),
        'type_color_background'  => html_entity_decode($eq_type['type_color_background']  ?? ''),
    ];
}


// -----------------------------------------------
// Query: Data origin / service

$sql_fromData   = "SELECT DISTINCT id_service, name, description
                   FROM " . TABLE_SERVICE . "
                   ORDER BY id_service ASC";
$fromData_query = tep_db_query($sql_link, $sql_fromData);
while ($fromData = tep_db_fetch_array($fromData_query))
{
    $fromData_array[$fromData['id_service']] = [
        'name'        => html_entity_decode($fromData['name']        ?? ''),
        'description' => html_entity_decode($fromData['description'] ?? ''),
    ];
}


// -----------------------------------------------
// Query: Administrative regions (Province for NC, Islands for PF/WF)

$sql_region   = "SELECT DISTINCT id_region, nom_region
                 FROM " . TABLE_REGION . "
                 WHERE id_territoire = " . $territoire_id;
$region_query = tep_db_query($sql_link, $sql_region);
while ($region = tep_db_fetch_array($region_query))
{
    $region_array[$region['id_region']] = html_entity_decode($region['nom_region'] ?? '');
}


// -----------------------------------------------
// Query: Municipalities

$sql_commune   = "SELECT DISTINCT c.id_commune, c.nom_commune
                  FROM " . TABLE_COMMUNE . " c
                  JOIN " . TABLE_REGION  . " r ON c.id_region = r.id_region
                  WHERE r.id_territoire = " . $territoire_id . "
                  ORDER BY c.nom_commune ASC";
$commune_query = tep_db_query($sql_link, $sql_commune);
while ($commune = tep_db_fetch_array($commune_query))
{
    $commune_array[$commune['id_commune']] = html_entity_decode($commune['nom_commune'] ?? '');
}


// -----------------------------------------------
// Query: Field rounds (tournées)

$sql_tournee   = "SELECT DISTINCT t.id, t.nom, t.id_territoire
                  FROM " . TABLE_TOURNEE . " t
                  WHERE t.id_territoire = " . $territoire_id . "
                  ORDER BY nom ASC";
$tournee_query = tep_db_query($sql_link, $sql_tournee);
while ($tournee = tep_db_fetch_array($tournee_query))
{
    $tournee_array[$tournee['id']] = html_entity_decode($tournee['nom'] ?? '');
}


// -----------------------------------------------
// Query: Hydrological regions

$sql_regionhydro   = "SELECT DISTINCT rh.id, rh.nom, rh.id_territoire
                      FROM " . TABLE_REGIONHYDRO . " rh
                      WHERE rh.id_territoire = " . $territoire_id . "
                      ORDER BY nom ASC";
$regionhydro_query = tep_db_query($sql_link, $sql_regionhydro);
while ($regionhydro = tep_db_fetch_array($regionhydro_query))
{
    $regionhydro_array[$regionhydro['id']] = html_entity_decode($regionhydro['nom'] ?? '');
}

// -----------------------------------------------
// Query: River

$sql_river   = "SELECT DISTINCT rv.id, rv.nom, rv.id_territoire
                      FROM " . TABLE_RIVIERE . " rv
                      WHERE rv.id_territoire = " . $territoire_id . "
                      ORDER BY nom ASC";
$river_query = tep_db_query($sql_link, $sql_river);
while ($river = tep_db_fetch_array($river_query))
{
    $river_array[$river['id']] = html_entity_decode($river['nom'] ?? '');
}

// -----------------------------------------------
// Query: Aquifer units

$sql_aquifere   = "SELECT DISTINCT ga.id, ga.nom, ga.description
                   FROM " . TABLE_GEO_AQUIFERE . " ga
                   ORDER BY nom ASC";
$aquifere_query = tep_db_query($sql_link, $sql_aquifere);
while ($aquifere = tep_db_fetch_array($aquifere_query))
{
    $aquifere_array[$aquifere['id']] = $aquifere['nom'];
}


// -----------------------------------------------
// Query: Station data

$sql_station = "SELECT DISTINCT s.id_station, s.id_station_old, s.id_service, s.nom_station, s.nom_court,
                       s.code_station, s.num_irh, s.id_region, s.id_commune, s.site_station, s.vallee_station,
                       s.riviere_station, s.id_riviere, s.id_aquifere, s.altitude_station, s.orientation_station,
                       s.longitude_station, s.latitude_station, s.utm_station_x, s.utm_station_y,
                       s.ign_station_x, s.ign_station_y, s.lamb_station_x, s.lamb_station_y,
                       s.source_info, s.station_type, s.transmission_station,
                       s.active_station, s.suivi, s.armee, s.id_regionhydro,
                       s.date_installation_station, s.date_fermeture_station, s.description_station
                FROM " . TABLE_STATION . " s
                WHERE s.id_station = " . $id_station;

$station_query = tep_db_query($sql_link, $sql_station);
$station       = tep_db_fetch_array($station_query);

$id_eq_type     = 0;
$id_regionhydro = 0;
$id_riviere     = 0;
$id_region      = 0;
$id_commune     = 0;

if (isset($station))
{
    $id_service       = $station['id_service'];
    $from_name        = html_entity_decode($fromData_array[$id_service]['name']        ?? '');
    $from_description = html_entity_decode($fromData_array[$id_service]['description'] ?? '');

    $id_station_old  = $station['id_station_old'];
    $nom_station     = html_entity_decode($station['nom_station']  ?? '');
    $nom_court       = html_entity_decode($station['nom_court']    ?? '');
    $code_station    = html_entity_decode($station['code_station'] ?? '');
    $num_irh         = html_entity_decode($station['num_irh']      ?? '');

    $id_region      = $station['id_region'];
    $id_commune     = $station['id_commune'];
    $site_station   = html_entity_decode($station['site_station']  ?? '');
    $id_aquifere    = $station['id_aquifere'];

    $id_eq_type         = $station['station_type'];
    $nom_data_type      = $eq_type_array[$id_eq_type]['nom_eq_type'];
    $type_color_border  = $eq_type_array[$id_eq_type]['type_color_border'];

    $vallee_station  = html_entity_decode($station['vallee_station']  ?? '');
    $riviere_station = html_entity_decode($station['riviere_station'] ?? '');
    $id_riviere      = $station['id_riviere'] ?? 0;
    $id_regionhydro  = $station['id_regionhydro'];

    // ---- Altitude ----
    $altitude_station = '';
    if (tep_not_null($station['altitude_station']))
    {
        $altitude_station = str_replace(',', '.', $station['altitude_station']);
        $altitude_station = is_numeric($altitude_station)
            ? number_format(floatval($altitude_station), 0)
            : '';
    }

    $orientation_station = $station['orientation_station'];

    // ---- GPS coordinates: comma → dot, strip encoding artefacts ----
    $longitude_station = '';
    if (tep_not_null($station['longitude_station']))
    {
        $longitude_station = str_replace(["Ã", "", "Â", ","], ["", "", "", "."], $station['longitude_station']);
    }

    $latitude_station = '';
    if (tep_not_null($station['latitude_station']))
    {
        $latitude_station = str_replace(["Ã", "", "Â", ","], ["", "", "", "."], $station['latitude_station']);
    }

    // ---- UTM / IGN / Lambert coordinates ----
    $utm_station_x = '';
    if (tep_not_null($station['utm_station_x']))
    {
        $utm_station_x = str_replace(',', '.', $station['utm_station_x']);
    }

    $utm_station_y = '';
    if (tep_not_null($station['utm_station_y']))
    {
        $utm_station_y = str_replace(',', '.', $station['utm_station_y']);
    }

    $ign_station_x = '';
    if (tep_not_null($station['ign_station_x']))
    {
        $ign_station_x = str_replace(',', '.', $station['ign_station_x']);
    }

    $ign_station_y = '';
    if (tep_not_null($station['ign_station_y']))
    {
        $v = str_replace(',', '.', $station['ign_station_y']);
        $ign_station_y = is_numeric($v) ? number_format(floatval($v), 3) : '';
    }

    $lamb_station_x = '';
    if (tep_not_null($station['lamb_station_x']))
    {
        $lamb_station_x = str_replace(',', '.', $station['lamb_station_x']);
    }

    $lamb_station_y = '';
    if (tep_not_null($station['lamb_station_y']))
    {
        $lamb_station_y = str_replace(',', '.', $station['lamb_station_y']);
    }

    $source_info          = $station['source_info'];
    $active_station       = $station['active_station'];
    $suivi_station        = $station['suivi'];
    $armee_station        = $station['armee'];
    $transmission_station = $station['transmission_station'];

    // ---- Dates ----
    $date_installation_station = ($station['date_installation_station'] == '0000-00-00')
        ? '' : dateus_fr($station['date_installation_station']);

    $date_fermeture_station = ($station['date_fermeture_station'] == '0000-00-00')
        ? '' : dateus_fr($station['date_fermeture_station']);

    $description_station = $station['description_station'];
}
else
{
    $error_station = true;
}

// New station creation: suppress the not-found error
if (isset($_GET['new']) && $_GET['new'] == 1) { $error_station = false; }


// -----------------------------------------------
// HTML output

require(DIR_WS_STRUCTURE . 'header_web.php');

echo "<body>";

    // Shared toolbar-button styling for the station tabs (PDF / Excel exports).
    // Mirrors the look used in the statistics module: text buttons with an
    // entity icon and an inline spinner during long actions.
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
        .hp-btn-spinner {
            display:inline-block; width:13px; height:13px;
            border:2px solid #c9d4da; border-top-color:#3c8da5;
            border-radius:50%; vertical-align:-2px;
            animation:hpBtnSpin 0.7s linear infinite;
        }
        @keyframes hpBtnSpin { to { transform:rotate(360deg); } }
    </style>";

    echo "<div id='contenu_info' style='display:none;'></div>";

    require(DIR_WS_STRUCTURE . 'block_wait.php');
    require(DIR_WS_BOX . 'block_img.php');
    require(DIR_WS_BOX . 'block_info_chron.php');
    require(DIR_WS_BOX . 'block_history_chron.php');

    require(DIR_WS_STRUCTURE . 'header.php');
    include(DIR_WS_BOX . 'nav_accueil.php');

    echo "<div id='contour_general'>";
        echo "<div id='contenu_centre'>";

            if (!$error_station)
            {
                echo "<form id='formStation'>";

                    echo "<input type='hidden' value='" . $id_station    . "' name='id_station' id='id_station'>";
                    echo "<input type='hidden' value='" . $id_user       . "' name='id_user_agent'>";
                    echo "<input type='hidden' value='" . $territoire_id . "' name='territoire_id'>";

                    // ---- Page title ----
                    echo "<h1>";

                    if ($modif)
                    {
                        echo "<span>" . $nom_data_type . TEXT_STATION_EDIT_TITLE_TYPE . "</span>";
                        echo "<span style='color:#000;'>" . $code_station . ' - ' . $nom_station . "</span>";

                        if ($gestion_data > 0)
                        {
                            echo "<input type='button' class='button' id='save_station' name='save_station'"
                               . " style='float:right;' value='" . TEXT_STATION_EDIT_SAVE . "'>";
                        }
                    }
                    else
                    {
                        echo "<span>" . TEXT_STATION_EDIT_TITLE_NEW . "</span>";
                        echo "<input type='button' class='button' id='save_station' name='save_station'"
                           . " style='float:right;' value='" . TEXT_STATION_EDIT_SAVE . "'>";
                    }

                    echo "</h1>";

                    // ---- Tab navigation ----
                    echo "<div id='onglet'>";
                        echo "<ul id='menu_onglet'>";

                        if ($modif)
                        {
                            if ($id_eq_type == 5) // Piezo station: show benchmark + characteristics tabs
                            {
                                echo "<li onClick=\"javascript:ChangeOnglet_2(2, 6, 'onglet-', 'contenu-');\" id='onglet-2' style='width:50px;' class='actif'>"
                                   . TEXT_STATION_TAB_MONITORING . "</li>\n";

                                if ($gestion_data > 0)
                                {
                                    echo "<li onClick=\"javascript:ChangeOnglet_2(1, 6, 'onglet-', 'contenu-');\" id='onglet-1' style='width:50px;'>"
                                       . TEXT_STATION_TAB_FORM . "</li>\n";
                                    echo "<li onClick=\"javascript:ChangeOnglet_2(3, 6, 'onglet-', 'contenu-');\" id='onglet-3' style='width:110px;'>"
                                       . TEXT_STATION_TAB_BENCHMARK . "</li>\n";
                                    echo "<li onClick=\"javascript:ChangeOnglet_2(4, 6, 'onglet-', 'contenu-');\" id='onglet-4' style='width:110px;'>"
                                       . TEXT_STATION_TAB_CHARACTERISTICS . "</li>\n";
                                    echo "<li onClick=\"javascript:ChangeOnglet_2(5, 6, 'onglet-', 'contenu-');\" id='onglet-5' style='width:50px;'>"
                                       . TEXT_STATION_TAB_ACCESS . "</li>\n";
                                    echo "<li onClick=\"javascript:ChangeOnglet_2(6, 6, 'onglet-', 'contenu-');\" id='onglet-6' style='width:50px;'>"
                                       . TEXT_STATION_TAB_PHOTOS . "</li>\n";
                                }
                            }
                            else // Standard station: no piezo-specific tabs
                            {
                                echo "<li onClick=\"javascript:ChangeOnglet_2(2, 4, 'onglet-', 'contenu-');\" id='onglet-2' style='width:50px;' class='actif'>"
                                   . TEXT_STATION_TAB_MONITORING . "</li>\n";

                                if ($gestion_data > 0)
                                {
                                    echo "<li onClick=\"javascript:ChangeOnglet_2(1, 4, 'onglet-', 'contenu-');\" id='onglet-1' style='width:50px;'>"
                                       . TEXT_STATION_TAB_FORM . "</li>\n";
                                    echo "<li onClick=\"javascript:ChangeOnglet_2(3, 4, 'onglet-', 'contenu-');\" id='onglet-3' style='width:50px;'>"
                                       . TEXT_STATION_TAB_ACCESS . "</li>\n";
                                    echo "<li onClick=\"javascript:ChangeOnglet_2(4, 4, 'onglet-', 'contenu-');\" id='onglet-4' style='width:50px;'>"
                                       . TEXT_STATION_TAB_PHOTOS . "</li>\n";
                                }
                            }
                        }
                        else // New station: form tab only
                        {
                            echo "<li onClick=\"javascript:ChangeOnglet_2(1, 1, 'onglet-', 'contenu-');\" id='onglet-1' style='width:50px;' class='actif'>"
                               . TEXT_STATION_TAB_FORM . "</li>\n";
                        }

                        echo "</ul>";

                        // ---- Tab content panels ----
                        if ($modif)
                        {
                            echo "<div id='contenu-2' class='contenu'>";
                                require(DIR_WS_STATION . 'form_station_2.php');
                            echo "</div>";

                            if ($gestion_data > 0)
                            {
                                echo "<div id='contenu-1' class='contenu' style='display:none;'>";
                                    require(DIR_WS_STATION . 'form_station_1.php');
                                echo "</div>";

                                $num_onglet = 3;

                                if ($id_eq_type == 5) // Piezo: inject benchmark + characteristics panels
                                {
                                    echo "<div id='contenu-" . $num_onglet . "' class='contenu' style='display:none;'>";
                                        require(DIR_WS_STATION . 'form_station_repere.php');
                                    echo "</div>";
                                    $num_onglet++;

                                    echo "<div id='contenu-" . $num_onglet . "' class='contenu' style='display:none;'>";
                                        require(DIR_WS_STATION . 'form_station_caracteristique.php');
                                    echo "</div>";
                                    $num_onglet++;
                                }

                                echo "<div id='contenu-" . $num_onglet . "' class='contenu' style='display:none;'>";
                                    require(DIR_WS_STATION . 'form_station_access.php');
                                echo "</div>";
                                $num_onglet++;

                                echo "<div id='contenu-" . $num_onglet . "' class='contenu' style='display:none;'>";
                                    require(DIR_WS_STATION . 'form_station_photos.php');
                                echo "</div>";
                            }
                        }
                        else
                        {
                            echo "<div id='contenu-1' class='contenu'>";
                                require(DIR_WS_STATION . 'form_station_1.php');
                            echo "</div>";
                        }

                    echo "</div>"; // #onglet

                echo "</form>\n";
            }
            else
            {
                // ---- Station not found ----
                echo "<h1><span>" . TEXT_STATION_EDIT_ERROR_TITLE . "</span></h1>";

                echo "<div id='boxpopup' style='padding:10px;'>\n";
                    echo "<p class='alert'>" . TEXT_STATION_EDIT_NOT_FOUND . "</p>";
                    echo "<p style='margin-top:15px;'>";
                        echo "<a href='list_stations.php' style='font-size:12px;'>"
                           . TEXT_STATION_EDIT_BACK_TO_LIST . "</a>";
                    echo "</p>";
                echo "</div>";
            }

        echo "<hr>";
        echo "</div>"; // #contenu_centre

    echo "<hr>";
    echo "</div>"; // #contour_general

    require('include/application_bottom.php');

echo "</body>";
echo "</html>";
?>

<script>

    var boxWait       = document.getElementById('box_wait');
    var contenuInfo   = document.getElementById('contenu_info');
    var plot          = document.getElementById('plot');
    var buttonSaveStation = document.getElementById('save_station');
    var buttonPDF     = document.getElementById('img_pdf');

    var idTerritoire  = <?php echo $territoire_id; ?>;
    var idStation     = <?php echo $id_station; ?>;
    var idEqType      = <?php echo $id_eq_type; ?>;

    // JS error messages injected from PHP constants (shared with list_stations.php)
    var LANG_STATION = {
        errGenerate : '<?= TEXT_STATION_JS_ERR_GENERATE ?>',
        errServer   : '<?= TEXT_STATION_JS_ERR_SERVER ?>'
    };


    // -----------------------------------------------
    // Save station via AJAX

    if (buttonSaveStation)
    {
        buttonSaveStation.addEventListener('click', function(event) { saveStation(event); });
    }

    function saveStation(event)
    {
        event.preventDefault();
        boxWait.style.display = 'block';

        var form     = document.getElementById('formStation');
        var formData = new FormData(form);

        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'include/structure/station/process_station_save.php', true);

        xhr.onreadystatechange = function()
        {
            if (xhr.readyState === 4 && xhr.status === 200)
            {
                var jsonResponse = JSON.parse(xhr.responseText);

                var erreur     = jsonResponse['erreur'];
                var newStation = jsonResponse['new_station'];
                idStation      = jsonResponse['id_station'];
                var msg_info   = jsonResponse['msg_info'];

                contenuInfo.innerHTML    = msg_info;
                contenuInfo.style.display = 'block';

                if (!erreur)
                {
                    document.getElementById('id_station').value = idStation;
                    contenuInfo.style.border = '2px solid #09886d'; // green border on success
                    if (newStation)
                    {
                        window.location.href = 'modif_station.php?ref=' + idStation;
                    }
                    else
                    {
                        // Reload the benchmark / characteristics tables so a newly
                        // added row or block appears without a full page refresh
                        // (piezo tabs only; functions are defined by their form
                        // includes when present).
                        if (typeof load_repere === 'function')         { load_repere(); }
                        if (typeof load_caracteristique === 'function') { load_caracteristique(); }
                    }
                }
                else
                {
                    contenuInfo.style.border = '2px solid #930000'; // red border on error
                }

                boxWait.style.display = 'none';
            }
        };

        xhr.send(formData);
    }


    // -----------------------------------------------
    // Download station info as XLS

    function downloadStation_xls(listStation, btnEl)
    {
        var prevXlsHtml = btnEl ? btnEl.innerHTML : '';
        if (btnEl) {
            btnEl.disabled  = true;
            btnEl.innerHTML = "<span class='hp-btn-spinner'></span> Excel";
        }
        function restoreXlsBtn() {
            if (btnEl) {
                btnEl.disabled  = false;
                btnEl.innerHTML = prevXlsHtml;
            }
        }

        var cheminFolder = 'data/export/temp';

        var dataToSend = {
            idTerritoire : idTerritoire,
            listStation  : listStation,
            cheminFolder : cheminFolder
        };

        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'include/structure/export/process_station_download_xls.php', true);
        xhr.setRequestHeader('Content-Type', 'application/json');

        xhr.onreadystatechange = function()
        {
            if (xhr.readyState === 4)
            {
                restoreXlsBtn();

                if (xhr.status === 200)
                {
                    var jsonResponse = JSON.parse(xhr.responseText);

                    if (jsonResponse['statut'])
                    {
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
                        contenuInfo.style.border  = '2px solid #930000';
                        contenuInfo.style.display = 'block';
                    }
                }
                else
                {
                    contenuInfo.innerHTML    = LANG_STATION.errServer;
                    contenuInfo.style.border  = '2px solid #930000';
                    contenuInfo.style.display = 'block';
                }
            }
        };

        xhr.send(JSON.stringify(dataToSend));
    }


    // -----------------------------------------------
    // Generate and open station PDF

    if (buttonPDF)
    {
        buttonPDF.addEventListener('click', function()
        {
            if (plot && plot.data && plot.data.length > 0)
            {
                var bbox         = plot.getBoundingClientRect();
                var targetWidth  = bbox.width;
                var targetHeight = (bbox.height / bbox.width) * targetWidth;

                Plotly.toImage(plot, { format: 'png', width: targetWidth, height: targetHeight })
                    .then(function(dataUrl) { envoyerPDF(dataUrl); });
            }
            else
            {
                envoyerPDF(null); // No graph: send request without image
            }
        });
    }

    function envoyerPDF(graphImage)
    {
        var prevPdfHtml = buttonPDF ? buttonPDF.innerHTML : '';
        if (buttonPDF) {
            buttonPDF.disabled  = true;
            buttonPDF.innerHTML = "<span class='hp-btn-spinner'></span> PDF";
        }
        function restorePdfBtn() {
            if (buttonPDF) {
                buttonPDF.disabled  = false;
                buttonPDF.innerHTML = prevPdfHtml;
            }
        }

        var dataToSend = {
            territoire_id     : <?php echo $territoire_id; ?>,
            territoire_nom    : <?php echo json_encode($territoire_nom); ?>,
            territoire_region : <?php echo json_encode($territoire_region); ?>,
            timezone_php      : <?php echo json_encode($timezone_php); ?>,
            id_user           : <?php echo $id_user; ?>,
            html_tab_data_station : html_tab_data,
            html_tab_code_cal : html_tab_code_cal,
            chron_data_array  : <?php echo json_encode($chron_data_array); ?>,
            graphImage        : graphImage,
            idStation         : <?php echo $id_station; ?>
        };

        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'include/structure/station/process_station_pdf.php', true);
        xhr.setRequestHeader('Content-Type', 'application/json');

        xhr.onreadystatechange = function()
        {
            if (xhr.readyState === 4 && xhr.status === 200)
            {
                var jsonResponse = JSON.parse(xhr.responseText);
                restorePdfBtn();

                if (jsonResponse['status'] === 'success')
                {
                    window.open('data/pdf/' + jsonResponse['fileName'], '_blank');
                }
                else
                {
                    contenuInfo.innerHTML    = jsonResponse['msg_info'];
                    contenuInfo.style.border  = '2px solid #930000';
                    contenuInfo.style.display = 'block';
                }
            }
        };

        xhr.send(JSON.stringify(dataToSend));
    }


    // -----------------------------------------------
    // Display a station photo in the lightbox overlay

    function affichePhoto(src)
    {
        let box   = document.getElementById('box_img');
        let cadre = document.getElementById('cadre_view_img');

        let oldImg = cadre.querySelector('img');
        if (oldImg) { oldImg.remove(); }

        const img    = document.createElement('img');
        img.src      = src;
        // Cap the image to the viewport so the popup never overflows the screen
        img.style.cssText = 'max-width:90vw;max-height:85vh;width:auto;height:auto;display:block;';
        cadre.appendChild(img);

        box.style.display = 'block';

        // Center the popup in the viewport once its real size is known.
        // Done after display so the box has measurable dimensions; using
        // pixel top/left (not transform) so initDraggable can take over cleanly.
        function centerBox()
        {
            let rect = box.getBoundingClientRect();
            let left = Math.max(10, (window.innerWidth  - rect.width)  / 2);
            let top  = Math.max(10, (window.innerHeight - rect.height) / 2);
            box.style.left = left + 'px';
            box.style.top  = top  + 'px';
        }

        if (img.complete) { centerBox(); }
        else { img.onload = centerBox; }

        initDraggable('title_img', 'box_img');
    }

</script>