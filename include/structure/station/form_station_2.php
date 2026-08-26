<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Station general information tab
- Metadata display (type, name, code, dates, geographic info, state)
- Data available for the station (series summary table + graph)
- Links to data pages (chronological data, activity reports, JGE, ETL)
- Export station record as PDF
----------------------------------------
*/

$row = 0;

$color_type = '';
if (tep_not_null($type_color_border)) { $color_type = 'color:' . $type_color_border . ';'; }

//echo "<div id='onglet_contenu' style='overflow-y:auto;height:75vh;'>\n";
echo "<div id='onglet_contenu' style='overflow-y:auto;height:calc(100vh - 200px);padding:0 20px;'>";


    // ---- Metadata panel ----
    echo "<div id='boite1' class='first' style='margin-left:0;margin-bottom:0;'>\n";

        echo "<p class='titre_box'>";
            echo "<span style='float:left;'>" . TEXT_STATION2_METADATA_TITLE . "</span>";

            if ($parametre > 0)
            {
                echo "<button type='button' class='hp-btn'"
                   . " style='float:left;margin-left:10px;'"
                   . " onClick=\"downloadStation_xls('" . $id_station . "', this);\">"
                   . "<span class='ico'>&#128202;</span> Excel</button>";

                echo "<button type='button' class='hp-btn' id='img_pdf'"
                   . " style='float:left;'>"
                   . "<span class='ico'>&#128196;</span> PDF</button>";
            }
        echo "</p>\n";
        echo "<hr>\n";

        // ---- General info ----
        echo "<div id='boxpopup' style='padding:15px;padding-bottom:10px;margin-right:25px;'>\n";

            echo "<p style='margin-bottom:10px;font-weight:normal;font-size:16px;'>";
                echo "<span style='font-weight:bold;'>" . TEXT_STATION_TYPE . " : </span>";
                echo "<span style='" . $color_type . "'>" . $nom_data_type . "</span>";
            echo "</p>";

            echo "<p style='margin-bottom:10px;font-weight:normal;font-size:13px;'>";
                echo "<span style='font-weight:bold;'>" . TEXT_FROM_DATA . " : </span>";
                echo "<span>" . $from_name . " - " . $from_description . "</span>";
            echo "</p>";

            echo "<p style='font-weight:normal;font-size:13px;'>";
                echo "<span style='font-weight:bold;'>" . TEXT_STATION_NOM . " : </span>";
                echo "<span>" . $nom_station . "</span>";
            echo "</p>";

            echo "<p style='margin-bottom:10px;font-weight:normal;font-size:13px;'>";
                echo "<span style='font-weight:bold;'>" . TEXT_STATION_CODE . " : </span>";
                echo "<span>" . $code_station . "</span>";
            echo "</p>";

            if (isset($date_installation_station) && !empty($date_installation_station))
            {
                echo "<p style='font-weight:normal;font-size:13px;'>";
                    echo "<span style='font-weight:bold;'>" . TEXT_STATION_DATE_INSTALL . " : </span>";
                    echo "<span>" . $date_installation_station . "</span>";
                echo "</p>";
            }

            if (isset($date_fermeture_station) && !empty($date_fermeture_station))
            {
                echo "<p style='font-weight:normal;font-size:13px;'>";
                    echo "<span style='font-weight:bold;'>" . TEXT_STATION_DATE_CLOSING . " : </span>";
                    echo "<span>" . $date_fermeture_station . "</span>";
                echo "</p>";
            }

        echo "</div>\n";

        // ---- Geographic info ----
        echo "<div id='boxpopup' style='padding:15px;padding-bottom:10px;margin-right:25px;'>\n";

            if (isset($region_array[$id_region]))
            {
                echo "<p style='font-weight:normal;font-size:13px;'>";
                    echo "<span style='font-weight:bold;'>" . $territoire_region . " : </span>";
                    echo "<span>" . $region_array[$id_region] . "</span>";
                echo "</p>";
            }

            if (isset($commune_array[$id_commune]))
            {
                echo "<p style='margin-bottom:10px;font-weight:normal;font-size:13px;'>";
                    echo "<span style='font-weight:bold;'>" . TEXT_FILTER_CITY . " : </span>";
                    echo "<span>" . $commune_array[$id_commune] . "</span>";
                echo "</p>";
            }

            if (isset($regionhydro_array[$id_regionhydro]))
            {
                echo "<p style='margin-bottom:10px;font-weight:normal;font-size:13px;'>";
                    echo "<span style='font-weight:bold;'>" . TEXT_FILTER_BV . " : </span>";
                    echo "<span>" . $regionhydro_array[$id_regionhydro] . "</span>";
                echo "</p>";
            }

            if (isset($aquifere_array[$id_aquifere]))
            {
                echo "<p style='margin-bottom:10px;font-weight:normal;font-size:13px;'>";
                    echo "<span style='font-weight:bold;'>" . TEXT_FILTER_AQUIFERE . " : </span>";
                    echo "<span>" . $aquifere_array[$id_aquifere] . "</span>";
                echo "</p>";
            }

            if (tep_not_null($longitude_station) && tep_not_null($latitude_station))
            {
                echo "<p style='margin-bottom:10px;font-weight:normal;font-size:13px;'>";
                    echo "<span style='font-weight:bold;'>" . TEXT_MAP_LONG . " : </span>";
                    echo "<span>" . round((float)$longitude_station, 3) . "</span>";
                    echo "<span style='margin-left:5px;font-weight:bold;'>" . TEXT_MAP_LAT . " : </span>";
                    echo "<span>" . round((float)$latitude_station, 3) . "</span>";
                echo "</p>";
            }

            if (tep_not_null($altitude_station))
            {
                echo "<p style='margin-bottom:10px;font-weight:normal;font-size:13px;'>";
                    echo "<span style='font-weight:bold;'>" . TEXT_MAP_ALT . " : </span>";
                    echo "<span>" . $altitude_station . " m</span>";
                echo "</p>";
            }

        echo "</div>\n";

        // ---- Station state ----
        echo "<div id='boxpopup' style='padding:15px;padding-bottom:10px;margin-right:25px;'>\n";

            $text_active = ($active_station > 0) ? TEXT_FILTER_STATUTACTIVE : TEXT_FILTER_STATUTHISTORIQUE;
            echo "<p style='margin-bottom:10px;font-weight:normal;font-size:13px;'>";
                echo "<span style='font-weight:bold;'>" . TEXT_STATION_STATUT . " : </span>";
                echo "<span>" . $text_active . "</span>";
            echo "</p>";

            $text_suivi = ($suivi_station > 0) ? TEXT_FILTER_SUIVICONTINU : TEXT_FILTER_SUIVIPONCTUEL;
            echo "<p style='margin-bottom:10px;font-weight:normal;font-size:13px;'>";
                echo "<span style='font-weight:bold;'>" . TEXT_STATION_SUIVI . " : </span>";
                echo "<span>" . $text_suivi . "</span>";
            echo "</p>";

            if ($gestion_data > 0)
            {
                $text_armee = ($armee_station > 0) ? TEXT_FILTER_ETATPANNE : TEXT_FILTER_ETATFONCTIONNEMENT;
                echo "<p style='margin-bottom:10px;font-weight:normal;font-size:13px;'>";
                    echo "<span style='font-weight:bold;'>" . TEXT_FILTER_ETATEQ . " : </span>";
                    echo "<span>" . $text_armee . "</span>";
                echo "</p>";
            }

        echo "</div>\n";

        // ---- Links panel ----
        echo "<div id='boxpopup' style='padding:15px;padding-bottom:10px;'>\n";

            echo "<p style='font-weight:normal;margin-bottom:10px;'>"
               . "<span style='font-size:12px;font-weight:bold;'>" . TEXT_STATION2_LINKS . "</span></p>";

            echo "<p style='margin-bottom:5px;'>";
                echo "<a href='data_chron.php?id_st=" . $id_station . "' style='font-size:12px;' target='_blank'>";
                    echo TEXT_STATION2_LINK_DATA;
                echo "</a>";
            echo "</p>";

            if ($parametre > 0)
            {
                // Link to activity reports
                $tabFormJson = htmlspecialchars(json_encode([
                    ['name' => 'search_station', 'value' => $code_station],
                    ['name' => 'select_periode',  'value' => 0],
                ]), ENT_QUOTES, 'UTF-8');

                echo "<p style='margin-bottom:5px;'>";
                    echo "<a href='#' style='font-size:12px;'"
                       . " onclick=\"event.preventDefault();linkSubmitForm('list_ra.php', " . $tabFormJson . ");\">";
                        echo TEXT_STATION2_LINK_RA;
                    echo "</a>";
                echo "</p>";

                // Hydro station: check for JGE and ETL data
                if ($id_eq_type == 11)
                {
                    $stmt_ETL = mysqli_prepare($sql_link,
                        "SELECT DISTINCT id FROM " . TABLE_DATA_ETL . " WHERE id_station=? LIMIT 1");
                    mysqli_stmt_bind_param($stmt_ETL, 'i', $id_station);
                    mysqli_stmt_execute($stmt_ETL);
                    $ETL_query = mysqli_stmt_get_result($stmt_ETL);

                    $stmt_JGE = mysqli_prepare($sql_link,
                        "SELECT DISTINCT id FROM " . TABLE_DATA_JGE . " WHERE id_station=? LIMIT 1");
                    mysqli_stmt_bind_param($stmt_JGE, 'i', $id_station);
                    mysqli_stmt_execute($stmt_JGE);
                    $JGE_query = mysqli_stmt_get_result($stmt_JGE);

                    if (mysqli_num_rows($JGE_query) > 0)
                    {
                        $tabFormJson = htmlspecialchars(json_encode([
                            ['name' => 'search_station', 'value' => $code_station],
                            ['name' => 'select_periode',  'value' => 0],
                        ]), ENT_QUOTES, 'UTF-8');

                        echo "<p style='margin-bottom:5px;'>";
                            echo "<a href='#' style='font-size:12px;'"
                               . " onclick=\"event.preventDefault();linkSubmitForm('data_jge.php', " . $tabFormJson . ");\">";
                                echo TEXT_STATION2_LINK_JGE;
                            echo "</a>";
                        echo "</p>";
                    }

                    if (mysqli_num_rows($ETL_query) > 0 || mysqli_num_rows($JGE_query) > 0)
                    {
                        $tabFormJson = htmlspecialchars(json_encode([
                            ['name' => 'st', 'value' => $id_station],
                        ]), ENT_QUOTES, 'UTF-8');

                        echo "<p>";
                            echo "<a href='#' style='font-size:12px;'"
                               . " onclick=\"event.preventDefault();linkSubmitForm('modif_etl.php', " . $tabFormJson . ");\">";
                                echo TEXT_STATION2_LINK_ETL;
                            echo "</a>";
                        echo "</p>";
                    }
                }
            }

        echo "</div>\n";

    echo "</div>\n"; // #boite1 metadata

    echo "<hr>\n";

    // ---- Available data panel ----
    echo "<div id='boite1' class='first' style='margin-left:0;margin-bottom:0;'>\n";

        // Title with inline action links (Series details + History)
        echo "<p class='titre_box' style='margin-bottom:10px;'>";
            echo "<span>" . TEXT_STATION2_DATA_TITLE . "</span>";

            echo "<span class='available-data-actions'>";
                // Series details — info icon (inline SVG)
                echo "<a onClick='afficheBlockInfoChron();'>";
                    echo "<svg viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'>";
                        echo "<circle cx='12' cy='12' r='10'/>";
                        echo "<line x1='12' y1='16' x2='12' y2='12'/>";
                        echo "<line x1='12' y1='8' x2='12.01' y2='8'/>";
                    echo "</svg>";
                    echo "<span>" . TEXT_STATION2_SERIES_DETAILS . "</span>";
                echo "</a>";

                if ($gestion_data > 0)
                {
                    // Modification history — clock-rewind icon (inline SVG)
                    echo "<a onClick='afficheBlockHistoryChron();'>";
                        echo "<svg viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'>";
                            echo "<path d='M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8'/>";
                            echo "<path d='M3 3v5h5'/>";
                            echo "<path d='M12 7v5l4 2'/>";
                        echo "</svg>";
                        echo "<span>" . TEXT_STATION2_MODIF_HISTORY . "</span>";
                    echo "</a>";
                }
            echo "</span>";
        echo "</p>\n";

        echo "<div style='display:flex;flex-direction:row;align-items:stretch;'>\n";

            // Left column: data table only (action links moved next to the title)
            echo "<div style='width:330px;min-width:330px;margin-right:20px;'>\n";

                echo "<div id='cadre_data_station' style='border:1px solid #e0e0e0;'>\n";
                    echo "<div id='cadre_wait_tab' style='width:100%;height:50px;margin-top:10px;text-align:center;'>";
                        echo "<span class='spinner' style='width:30px;height:30px;'></span>";
                    echo "</div>";
                echo "</div>\n";

            echo "</div>\n"; // Left column

            // Centre column: graph
            echo "<div id='cadre_graph' style='flex-grow:1;min-width:0;margin:0;'>\n";
                echo "<div id='boxpopup' class='select'"
                   . " style='width:100%;height:40vh;margin:0;padding:0;padding-top:10px;"
                   . "display:flex;flex-direction:column;'>\n";
                    echo "<div id='cadre_wait_graph' style='width:100%;height:50px;margin-top:10px;text-align:center;'>";
                        echo "<span class='spinner' style='width:30px;height:30px;'></span>";
                    echo "</div>";
                    echo "<div id='plot' style='flex:0 0 95%;width:97%;'></div>\n";
                echo "</div>\n";
            echo "</div>\n"; // Centre column

            // Right column: quality code table
            echo "<div id='cadre_code_qual' style='width:200px;height:38.5vh;margin-left:1%;'>\n";
                echo "<div id='cadre_wait_tab' style='width:100%;height:50px;margin-top:10px;text-align:center;'>";
                    echo "<span class='spinner' style='width:30px;height:30px;'></span>";
                echo "</div>";
            echo "</div>\n"; // Right column

        echo "</div>\n"; // flex row

    echo "</div>\n"; // #boite1 data

echo "</div>\n"; // #onglet_contenu
?>

<script>

    var cadreData     = document.getElementById('cadre_data_station');
    var cadreGraph    = document.getElementById('cadre_graph');
    var cadreCodeQual = document.getElementById('cadre_code_qual');
    var waitBoxTab    = document.getElementById('cadre_wait_tab');
    var waitBoxGraph  = document.getElementById('cadre_wait_graph');

    var idStation = <?php echo $id_station; ?>;
    var idEqType  = <?php echo $id_eq_type; ?>;

    // Shared with envoyerPDF() in modif_station.php. Initialized here so they
    // always exist, even when the station has no data (otherwise the PDF
    // button would throw "html_tab_data is not defined").
    var html_tab_data     = '';
    var html_tab_code_cal = '';

    // JS string injected from PHP constant
    var LANG_STATION2 = {
        noData : '<?= TEXT_STATION2_JS_NO_DATA ?>'
    };

    loadData();

    function loadData()
    {
        waitBoxTab.style.display   = 'block';
        waitBoxGraph.style.display = 'block';

        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'include/structure/station/process_loaddata.php', true);
        xhr.setRequestHeader('Content-Type', 'application/json');

        xhr.onreadystatechange = function()
        {
            if (xhr.readyState === 4 && xhr.status === 200)
            {
                var jsonResponse = JSON.parse(xhr.responseText);
                var nb_chron     = jsonResponse['nb_chron'];

                if (nb_chron > 1)
                {
                    // ---- Data available: show graph + quality code table ----
                    cadreGraph.style.display    = '';
                    cadreCodeQual.style.display = '';

                    html_tab_data = jsonResponse['js_tab_data'];

                    var plotDiv       = document.getElementById('plot');
                    plotDiv.style.height = '42vh';
                    eval(jsonResponse['js_graph']); // Execute server-generated Plotly graph code

                    html_tab_code_cal       = jsonResponse['js_tab_code_cal'];
                    cadreCodeQual.innerHTML = html_tab_code_cal;

                    cadreData.innerHTML = html_tab_data;
                }
                else
                {
                    // ---- No data: hide graph + quality columns ----
                    // The message is only shown in the left column (cadreData),
                    // wrapped in a span for bold + slightly larger text
                    cadreGraph.style.display    = 'none';
                    cadreCodeQual.style.display = 'none';

                    // Keep the shared globals consistent for the PDF export
                    html_tab_data     = LANG_STATION2.noData;
                    html_tab_code_cal = '';

                    cadreData.innerHTML =
                        "<span style='font-weight:bold;font-size:14px;'>"
                        + LANG_STATION2.noData
                        + "</span>";
                }

                // Hide every spinner regardless of the branch taken
                waitBoxTab.style.display   = 'none';
                waitBoxGraph.style.display = 'none';
            }
        };

        xhr.send(JSON.stringify({ idStation: idStation, idEqType: idEqType }));
    }

</script>