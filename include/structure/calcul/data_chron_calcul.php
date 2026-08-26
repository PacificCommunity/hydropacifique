<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Chronological data correction page
- Left panel: period selector and correction tools
- Main panel: interactive Plotly graph per series
- Bottom panel: current corrections table + save/save-as buttons
----------------------------------------
*/

// -----------------------------------------------
// Initialize variables

$nb_data              = 0;
$min_y                = 0;
$max_y                = 0;
$min_x                = '';
$max_x                = '';
$id_correction        = 0;
$id_station_encours   = 0;
$id_typedata_encours  = 0;
$id_chron_encours     = 0;
$id_eq_type           = $typedata_encours;
$id_typedata_encours  = $typedata_encours;
$lacune_date_first    = '';
$edit_lacune_temp     = '';
$nb_lacunes           = 0;
$js_syncAbsc_var      = '';
$js_syncOrdon_var     = '';


// -----------------------------------------------
// Read correction ID from POST if present

if (isset($_POST['id_correction'])) { $id_correction = $_POST['id_correction']; }


// -----------------------------------------------
// Color mapping for each curve type

$colorMapping = [
    'data_init'        => '#000000', // Black — base series to correct
    'calcul'           => '#1f77b4', // Electric blue — linear correction aY+b
                                     // (also used by Duplicate: same type, formula 1Y+0)
    'decalage_date'    => '#2ca02c', // Green — date shift
    'lissage'          => '#ff7f0e', // Orange — smoothing
    'lacune'           => '#EA1179', // Pink — gap insertion
    'calcul_pastemps'  => '#17becf', // Cyan/Turquoise — time-step resampling
    'calcul_chron_new' => '#d62728', // Red — new series generation
];


// -----------------------------------------------
// HTML output

require(DIR_WS_STRUCTURE . 'header_web.php');

echo "<body>";

    echo "<div id='contenu_info' style='display:none;'></div>";

    require(DIR_WS_BOX       . 'block_info_chron.php');
    require(DIR_WS_CALCUL    . 'block_calcul_options.php');
    require(DIR_WS_STRUCTURE . 'block_wait.php');
    require(DIR_WS_CALCUL    . 'block_verif_savedata_calc.php');
    require(DIR_WS_STRUCTURE . 'block_graph.php');
    require(DIR_WS_STRUCTURE . 'block_lacunes_info.php');
    require(DIR_WS_STRUCTURE . 'header.php');
    include(DIR_WS_BOX       . 'nav_accueil.php');

    echo "<div id='contour_general'>";
        echo "<div id='contenu_centre'>";
            echo "<div id='contenu_box2'>";

                // ---- Page title ----
                echo "<h1 id='h1_graph'>";
                    echo "<span>" . TEXT_CORRECT_TITLE . "</span>";

                    if (isset($station_chron_array) && sizeof($station_chron_array) > 0)
                    {
                        foreach ($station_chron_array as $cle_station => $typedata_array)
                        {
                            $id_station_encours  = $cle_station;
                            $id_typedata_encours = $station_all_array[$cle_station]['type_station'];
                            foreach ($typedata_array as $typedata_chron => $sql_chron)
                            {
                                echo "<span style='margin:0 10px;'>&#x25CF</span>";
                                echo TEXT_CORRECT_STATION
                                   . $station_all_array[$cle_station]['code_station'] . " - "
                                   . $station_all_array[$cle_station]['nom_station'];
                                echo "<span style='margin:0 10px;'>&#x25CF</span>";
                                echo TEXT_CORRECT_SERIES
                                   . $type_chron_array[$typedata_chron]['init_type_data'] . " - "
                                   . $type_chron_array[$typedata_chron]['nom_type_data'];

                                // Raw series: append "(Raw)" to the page title (no colour here;
                                // the red badge lives in the graph header band only).
                                if (isset($type_chron_array[$typedata_chron]['raw_data'])
                                    && $type_chron_array[$typedata_chron]['raw_data'] == 1) {
                                    echo " (" . TEXT_CHRON_RAW_DATA . ")";
                                }
                            }
                        }
                    }
                echo "</h1>";


                // ---- Left panel: correction controls ----
                echo "<div id='cadre_graph' style='float:left;width:220px;margin-right:0.5%;height:78vh;overflow-y:auto;'>\n";

                    // Series info link
                    echo "<div style='float:left;width:90%;margin-top:8px;margin-bottom:15px;'>";
                        echo "<img src='" . DIR_WS_IMG_ICO . "info.png'"
                           . " style='float:left;width:20px;margin-left:5px;margin-right:10px;'>";
                        echo "<p style='float:left;margin-top:3px;'>";
                            echo "<a onClick='afficheBlockInfoChron();'>";
                                echo "<span style='font-size:13px;font-weight:bold;'>"
                                   . TEXT_CORRECT_SERIES_DETAILS . "</span>";
                            echo "</a>\n";
                        echo "</p>\n";
                    echo "</div>\n";

                    // ---- Period to correct ----
                    echo "<div id='boxpopup' class='select-top' style='width:88%;margin:0;padding:10px;'>\n";

                        echo "<p><span style='font-weight:bold;font-size:13px;width:150px;'>"
                           . TEXT_CORRECT_PERIOD_TITLE . "</span></p>";

                        // Row 1: start date + time + Y min
                        echo "<div style='float:left;width:100%;margin-bottom:10px;'>\n";

                            echo "<div id='boite_small' class='select_date' style='margin-right:1%;'>\n";
                                echo "<p style='width:80px;color:#428bca;'>" . TEXT_CORRECT_DATE_START . "</p>\n";
                                echo "<input class='input_texte' style='width:65px;padding-bottom:4px;'"
                                   . " name='x1Zoom' id='x1Zoom' type='text'"
                                   . " onfocus='initDatepickers(this)'"
                                   . " placeholder='dd-mm-yyyy' value=''>\n";
                            echo "</div>\n";

                            echo "<div id='boite_small' class='select_date' style='margin-right:0;'>\n";
                                echo "<p style='width:80px;color:#428bca;'>" . TEXT_CORRECT_HOUR . "</p>\n";
                                echo "<input class='input_texte' style='width:50px;padding-bottom:4px;'"
                                   . " name='x1Zoom_h' id='x1Zoom_h' type='text' value=''>\n";
                            echo "</div>\n";

                        echo "</div>\n";

                        // Row 2: end date + time
                        echo "<div style='float:left;width:100%;margin-bottom:0;'>\n";

                            echo "<div id='boite_small' class='select_date' style='margin-right:1%;'>\n";
                                echo "<p style='width:80px;color:#428bca;'>" . TEXT_CORRECT_DATE_END . "</p>\n";
                                echo "<input class='input_texte' style='width:65px;padding-bottom:4px;'"
                                   . " name='x2Zoom' id='x2Zoom' type='text' value=''"
                                   . " onfocus='initDatepickers(this)'"
                                   . " placeholder='dd-mm-yyyy' value=''>\n";
                            echo "</div>\n";

                            echo "<div id='boite_small' class='select_date' style='margin-right:0;'>\n";
                                echo "<p style='width:80px;color:#428bca;'>" . TEXT_CORRECT_HOUR . "</p>\n";
                                echo "<input class='input_texte' style='width:50px;padding-bottom:4px;'"
                                   . " name='x2Zoom_h' id='x2Zoom_h' type='text' value=''>\n";
                            echo "</div>\n";

                        echo "</div>\n";

                        echo "<div style='float:left;'>\n";
                            echo "<button id='ajustCoord' class='zoom_graph'"
                               . " style='float:none;width:130px;margin-top:10px;margin-bottom:0;'"
                               . " onClick='applyPeriodBand();'>" . TEXT_CORRECT_APPLY_PERIOD . "</button>\n";
                        echo "</div>\n";

                        // ---- Line width control (applies to all traces) ----
                        // Small +/- module that drives Plotly.restyle on every
                        // line trace currently in the plot. Default width=1.
                        // Range is clamped 1..6 in the JS handler below.
                        //
                        // Buttons reuse the `.decimal_axe` class (same one used
                        // for the +/- buttons under the Y axis) so the look is
                        // consistent — small black square buttons.
                        echo "<div style='clear:both;float:left;width:100%;margin-top:10px;'>\n";
                            echo "<p style='float:left;width:auto;font-size:11px;color:#428bca;"
                               . "margin:0 8px 0 0;padding-top:5px;font-weight:bold;'>"
                               . (defined('TEXT_CORRECT_LINE_WIDTH') ? TEXT_CORRECT_LINE_WIDTH : 'Line width')
                               . "</p>\n";
                            echo "<button id='line_width_minus' class='decimal_axe'"
                               . " style='float:left;'"
                               . " title='" . (defined('TEXT_CORRECT_LINE_WIDTH_DEC') ? TEXT_CORRECT_LINE_WIDTH_DEC : 'Thinner') . "'>-</button>\n";
                            echo "<button id='line_width_plus' class='decimal_axe'"
                               . " style='float:left;margin-left:4px;'"
                               . " title='" . (defined('TEXT_CORRECT_LINE_WIDTH_INC') ? TEXT_CORRECT_LINE_WIDTH_INC : 'Thicker') . "'>+</button>\n";
                        echo "</div>\n";

                    echo "</div>\n"; // period box

                    // ---- Correction options button ----
                    echo "<div id='boxpopup' class='select-top' style='width:88%;margin-top:10px;padding:10px;'>\n";

                        echo "<div style='float:left;width:70%;'>\n";
                            echo "<p style='float:left;width:100%;padding-top:5px;font-size:13px;'>"
                               . TEXT_CORRECT_OPTIONS_TITLE . "</p>\n";
                        echo "</div>\n";

                        echo "<div style='float:right;'>\n";
                            echo "<button id='popup_modif' class='inverse_axe'"
                               . " style='width:40px;height:30px;text-align:center;color:#000;'"
                               . " title='" . TEXT_CORRECT_OPTIONS_OPEN . "'"
                               . " onClick='affiche_options_calcul();'>";
                                echo "<img src='" . DIR_WS_IMG_ICO . "calcul.png'"
                                   . " style='float:left;width:20px;margin-left:5px;margin-right:10px;'>";
                            echo "</button>\n";
                        echo "</div>\n";

                    echo "</div>\n";

                    // ---- Correction tools ----
                    echo "<div id='boxpopup' class='select-top' style='width:88%;margin-top:10px;padding:10px;'>\n";

                        // Duplicate series
                        /*
                        echo "<div style='float:left;width:100%;padding-bottom:10px;border-bottom:1px solid #ddd;'>\n";
                            echo "<div style='float:left;width:70%;'>\n";
                                echo "<p style='float:left;width:100%;padding-top:5px;font-size:13px;'>"
                                   . TEXT_CORRECT_DUPLICATE . "</p>\n";
                            echo "</div>\n";
                            echo "<div style='float:right;'>\n";
                                echo "<button id='calcul_copy' class='inverse_axe'"
                                   . " style='width:40px;padding:0;color:" . $colorMapping['calcul'] . ";'"
                                   . " title='" . TEXT_CORRECT_DUPLICATE . "'>";
                                    echo "<span style='font-size:22px;margin:0;'>&#x25CF></span>";
                                echo "</button>\n";
                            echo "</div>\n";
                        echo "</div>\n";
                        */

                        // ----------------------------------------------------------
                        // Unified time-step resampling
                        //
                        // One small form with:
                        //   - interval value (numeric, default 5)
                        //   - unit  dropdown : minute / hour / day / month / year
                        //   - mode  dropdown : mean / cumul
                        //
                        // Backend (process_chron_calcul.php) routes internally
                        // depending on the unit:
                        //   - min / hour / day → sliding-window resampling
                        //                        (UNIX_TIMESTAMP DIV bucket)
                        //   - month / year     → calendar-true aggregation
                        //                        (DATE_FORMAT '%Y-%m-01' / '%Y-01-01'),
                        //                        n is forced to 1 on the front
                        //                        (handled by JS below).
                        // ----------------------------------------------------------
                        echo "<div style='float:left;width:100%;padding-bottom:10px;"
                           . "border-bottom:1px solid #ddd;'>\n";

                            echo "<div style='float:left;width:75%;'>\n";

                                echo "<p style='float:left;width:100%;padding-top:5px;font-size:13px;'>"
                                   . TEXT_CORRECT_TIMESTEP . "</p>\n";

                                // ---- Interval value + unit ----
                                // Flex row + fixed label width to align horizontally
                                // with the "Calc. mode" row below (same label column).
                                echo "<div style='clear:both;display:flex;align-items:center;gap:6px;"
                                   . "margin-top:6px;'>\n";
                                    echo "<span style='flex:0 0 50px;font-size:11px;color:#428bca;'>"
                                       . TEXT_CORRECT_INTERVAL . "</span>\n";
                                    echo "<input type='text' class='input_texte_xsmall'"
                                       . " id='input_pastemps' style='width:18  px;float:none;margin:0;' value='5'>\n";
                                    echo "<select name='select_pastemps_unit' id='select_pastemps_unit'"
                                       . " style='width:70px;font-size:12px;float:none;margin:0;'>\n";
                                        echo "<option value='min'  selected>" . TEXT_CORRECT_UNIT_MIN   . "</option>\n";
                                        echo "<option value='h'>"             . TEXT_CORRECT_UNIT_HOUR  . "</option>\n";
                                        echo "<option value='d'>"             . TEXT_CORRECT_UNIT_DAY   . "</option>\n";
                                        echo "<option value='m'>"             . TEXT_CORRECT_UNIT_MONTH . "</option>\n";
                                        echo "<option value='y'>"             . TEXT_CORRECT_UNIT_YEAR  . "</option>\n";
                                    echo "</select>\n";
                                echo "</div>\n";

                                // ---- Calc mode (mean / cumul) ----
                                echo "<div style='clear:both;display:flex;align-items:center;gap:6px;"
                                   . "margin-top:8px;'>\n";
                                    echo "<span style='flex:0 0 50px;font-size:11px;color:#428bca;'>"
                                       . TEXT_CORRECT_CALC_MODE . "</span>\n";
                                    echo "<select name='select_pastemps_mode' id='select_pastemps_mode'"
                                       . " style='width:85px;float:none;margin:0;'>\n";
                                        echo "<option value='moy'>"   . TEXT_CORRECT_CALC_MEAN  . "</option>\n";
                                        echo "<option value='cumul'>" . TEXT_CORRECT_CALC_CUMUL . "</option>\n";
                                    echo "</select>\n";
                                echo "</div>\n";

                                // ---- Gap threshold (only relevant for Mean) ----
                                // Visible by default (Mean is the default mode);
                                // JS below toggles its display when the user
                                // switches between mean and cumul.
                                echo "<div id='cadre_gap_threshold' style='clear:both;display:flex;"
                                   . "align-items:center;gap:6px;margin-top:8px;'>\n";
                                    echo "<span style='flex:0 0 50px;font-size:11px;color:#428bca;'"
                                       . " title='" . TEXT_CORRECT_GAP_THRESHOLD_TITLE . "'>"
                                       . TEXT_CORRECT_GAP_THRESHOLD . "</span>\n";
                                    echo "<input type='number' id='input_gap_threshold'"
                                       . " min='0' max='100' step='1' value='10'"
                                       . " style='width:55px;font-size:12px;'>\n";
                                    echo "<span style='font-size:11px;color:#888;'>%</span>";
                                echo "</div>\n";

                            echo "</div>\n";

                            echo "<div style='float:right;'>\n";
                                echo "<button id='create_chron_min' class='inverse_axe'"
                                   . " style='width:40px;padding:0;margin-top:62px;color:" . $colorMapping['calcul_pastemps'] . ";'"
                                   . " title='" . TEXT_CORRECT_NEW_SERIES_BTN . "'>";
                                    echo "<span style='font-size:22px;margin:0;'>&#x25CF></span>";
                                echo "</button>\n";
                            echo "</div>\n";

                        echo "</div>\n";

                    echo "</div>\n"; // correction tools box

                echo "<hr>\n";
                echo "</div>\n"; // left panel


                // ---- Main graph panel ----
                echo "<div id='cadre_graph' style='float:none;width:auto;height:calc(100vh - 150px);min-height:300px;overflow-y:auto;overflow-x:hidden;'>\n";

                    if (isset($station_chron_array) && sizeof($station_chron_array) > 0)
                    {
                        foreach ($station_chron_array as $cle_station => $typedata_array)
                        {
                            $id_station_encours  = $cle_station;
                            foreach ($typedata_array as $typedata_chron => $sql_chron)
                            {
                                $id_chron_encours        = $typedata_chron;
                                $id_typedata_encours     = $station_all_array[$cle_station]['type_station'];
                                $nom_type_data           = $eq_type_array[$station_all_array[$cle_station]['type_station']]['nom_eq_type'];
                                $to_periode_encours      = $type_chron_array[$typedata_chron]['to_periode'];
                                $id_create_chron_encours = $type_chron_array[$typedata_chron]['id_chon_periode'];
                                $text_chron_encours      = $type_chron_array[$typedata_chron]['init_type_data']
                                                         . " - " . $type_chron_array[$typedata_chron]['nom_type_data'];

                                // Is this series raw? Used below to render a red badge in the
                                // graph header band (and to suffix the Plotly title text).
                                $chron_is_raw = (isset($type_chron_array[$typedata_chron]['raw_data'])
                                                 && $type_chron_array[$typedata_chron]['raw_data'] == 1);

                                echo "<input type='hidden' id='text_chron_" . $id_chron_encours . "'"
                                   . " value='" . $text_chron_encours . "'>";

                                echo "<div id='boxpopup' class='select'"
                                   . " style='width:99%;margin:0;padding:0;border-radius:2px;border:1px solid #F5788B;'>\n";

                                    // Header band: full-width red ribbon with the series title,
                                    // plus a position:absolute action area on the right for the
                                    // Enlarge / Gaps / H→Q buttons. Mirrors the layout introduced
                                    // in graph_chron.php so both views feel consistent.
                                    echo "<div class='graph-header' style='position:relative;border-radius:2px 4px 0 0;'>";

                                        // Series title bar — full width, vertically centered text
                                        echo "<p class='titre graph-title'"
                                           . " style='margin:0;padding:6px 220px 6px 12px;font-size:14px;"
                                           . "border-radius:2px 4px 0 0;background-color:#F5788B;color:#fff;'>";
                                            echo "<span style='font-size:20px;'>" . TEXT_CORRECT_IN_PROGRESS . "</span>";
                                            echo "<br>";
                                            echo TEXT_CORRECT_STATION
                                               . $station_all_array[$cle_station]['code_station'] . " - "
                                               . $station_all_array[$cle_station]['nom_station'];
                                            echo "<span style='margin:0 10px;'>&#x25CF</span>";
                                            echo TEXT_CORRECT_SERIES;

                                            // Raw series: acronym shown as a red badge (white
                                            // text on #A32D2D), same as the Step 2 selection
                                            // table. The badge reads well even on the pink
                                            // ribbon. Non-raw series keep the plain acronym.
                                            $band_acro = $type_chron_array[$typedata_chron]['init_type_data'];
                                            $band_name = $type_chron_array[$typedata_chron]['nom_type_data'];
                                            if ($chron_is_raw) {
                                                echo "<span style='display:inline-block;background:#A32D2D;color:#fff;"
                                                   . "font-weight:bold;padding:1px 8px;border-radius:4px;'>" . $band_acro . "</span>";
                                                echo " - " . $band_name . " (" . TEXT_CHRON_RAW_DATA . ")";
                                            } else {
                                                echo $band_acro . " - " . $band_name;
                                            }
                                        echo "</p>";

                                        // Action area: floats over the ribbon, vertically centered.
                                        // Mirrors graph_chron.php: a Full Screen button plus a
                                        // single "Tools" dropdown (Gaps / Data qualification /
                                        // Export CSV).
                                        echo "<div class='graph-actions'"
                                           . " style='position:absolute;top:50%;right:10px;transform:translateY(-50%);"
                                           . "display:flex;gap:6px;align-items:center;'>";

                                            // Full Screen button (reuses the existing zoom_graph()
                                            // enlarge view, which is the full-screen display here).
                                            echo "<button type='button' class='hp-btn-tools'"
                                               . " onclick=\"zoom_graph('"
                                               . $cle_station . "','"
                                               . htmlspecialchars($station_all_array[$cle_station]['code_station'], ENT_QUOTES) . "','"
                                               . htmlspecialchars($station_all_array[$cle_station]['nom_station'], ENT_QUOTES) . "','"
                                               . htmlspecialchars($nom_type_data, ENT_QUOTES) . "');\""
                                               . " title='" . TEXT_FULLSCREEN . "'>"
                                               . TEXT_FULLSCREEN . "</button>";

                                            // Tools dropdown
                                            echo "<div class='hp-menu' id='hp_menu_" . $cle_station . "'>";
                                                echo "<button type='button' class='hp-btn-tools'"
                                                   . " onclick=\"hpToggleMenu('" . $cle_station . "');\">"
                                                   . TEXT_TOOLS
                                                   . " <span class='hp-caret'>&#9660;</span></button>";
                                                echo "<div class='hp-menu-list' id='hp_menu_list_" . $cle_station . "'>";

                                                    // Gaps -> modern gaps modal (hidden until a
                                                    // continuous series is loaded; toggled by JS).
                                                    echo "<div class='hp-menu-item' id='button_lacune_" . $cle_station . "'"
                                                       . " style='display:none;'"
                                                       . " onclick=\"hpMenuAction('" . $cle_station . "','gaps');\""
                                                       . " title='" . TEXT_GAPS_TABLE . "'>"
                                                       . "<span class='hp-mi-ico'>&#128201;</span>" . TEXT_GAPS . "</div>";

                                                    // Data qualification -> modern qualif modal.
                                                    echo "<div class='hp-menu-item'"
                                                       . " onclick=\"hpMenuAction('" . $cle_station . "','qualif');\">"
                                                       . "<span class='hp-mi-ico'>&#9745;</span>" . TEXT_DATA_QUALIF . "</div>";

                                                    // Export chart data as CSV (was in the Plotly modebar).
                                                    echo "<div class='hp-menu-item'"
                                                       . " onclick=\"hpMenuAction('" . $cle_station . "','export_csv');\">"
                                                       . "<span class='hp-mi-ico'>&#11015;</span>" . TEXT_EXPORT_GRAPH_CSV
                                                       . "<span class='hp-mi-fmt'>CSV</span></div>";

                                                echo "</div>"; // .hp-menu-list
                                            echo "</div>"; // .hp-menu

                                            // Hidden helper carrying the chart labels for the CSV
                                            // export filename (read by hpExportChartCsv).
                                            echo "<div id='button_visu' style='display:none;'"
                                               . " data-code='" . htmlspecialchars($station_all_array[$cle_station]['code_station'], ENT_QUOTES) . "'"
                                               . " data-nom='"  . htmlspecialchars($station_all_array[$cle_station]['nom_station'], ENT_QUOTES) . "'"
                                               . " data-type='" . htmlspecialchars($nom_type_data, ENT_QUOTES) . "'></div>";

                                        echo "</div>"; // .graph-actions
                                    echo "</div>"; // .graph-header

                                    // ---- Current corrections + save panel ----
                                    //
                                    // Compact collapsible card placed INSIDE the
                                    // graph box, ABOVE the zoom controls. The
                                    // chevron in the header toggles the body's
                                    // visibility; Save / Save as... live on the
                                    // right. Open by default; click anywhere on
                                    // the header (except the action buttons,
                                    // which stopPropagation) to collapse/expand.
                                    echo "<div id='block_valid_correction'"
                                       . " style='margin:6px 10px 10px;"
                                       . "border:1px solid #d0d4d9;border-radius:6px;background:#fff;"
                                       . "box-shadow:0 1px 3px rgba(0,0,0,0.06);'>\n";

                                        // ---- Card header: chevron + title + action buttons ----
                                        echo "<div id='card_header_corrections'"
                                           . " style='display:flex;align-items:center;justify-content:space-between;"
                                           . "padding:6px 12px;border-bottom:1px solid #c8d0db;background:#e6ebf2;"
                                           . "border-radius:6px 6px 0 0;cursor:pointer;user-select:none;'>\n";

                                            echo "<div style='display:flex;align-items:center;gap:8px;'>\n";
                                                echo "<span id='card_chevron_corrections'"
                                                   . " style='display:inline-block;font-size:10px;color:#393E46;"
                                                   . "transition:transform 0.2s ease;transform:rotate(0deg);'>&#x25BC;</span>";
                                                echo "<span style='font-weight:bold;font-size:13px;color:#393E46;'>"
                                                   . TEXT_CORRECT_TABLE_TITLE . "</span>";
                                            echo "</div>\n";

                                            echo "<div style='display:flex;align-items:center;gap:8px;'>\n";

                                                // Save (wait spinner + button)
                                                // A raw-data series must never be overwritten in place:
                                                // the Save button is hidden, only "Save as..." remains.
                                                $is_raw_chron = (isset($type_chron_array[$id_chron_encours]['raw_data'])
                                                                 && $type_chron_array[$id_chron_encours]['raw_data'] == 1);
                                                if (!$is_raw_chron)
                                                {
                                                    echo "<div style='display:flex;align-items:center;gap:6px;'>\n";
                                                        echo "<img src='" . DIR_WS_IMG . "wait.gif'"
                                                           . " style='width:18px;display:none;' id='wait_valid_save'"
                                                           . " title='" . TEXT_CORRECT_PROCESSING . "'>";
                                                        echo "<button id='button_save' class='valid'"
                                                           . " style='padding:3px 10px;font-size:11px;min-width:0;width:auto;'"
                                                           . " onCLick='event.stopPropagation();saveCorrection(false);'"
                                                           . " title='" . TEXT_CORRECT_SAVE_TITLE . "'>"
                                                           . TEXT_CORRECT_SAVE . "</button>\n";
                                                    echo "</div>\n";
                                                }

                                                // Save as... (wait spinner + button)
                                                echo "<div style='display:flex;align-items:center;gap:6px;'>\n";
                                                    echo "<img src='" . DIR_WS_IMG . "wait.gif'"
                                                       . " style='width:18px;display:none;' id='wait_valid_saveas'"
                                                       . " title='" . TEXT_CORRECT_PROCESSING . "'>";
                                                    echo "<button id='button_saveas' class='validunder'"
                                                       . " style='padding:3px 10px;font-size:11px;min-width:0;width:auto;'"
                                                       . " onCLick='event.stopPropagation();saveCorrection(true);'"
                                                       . " title='" . TEXT_CORRECT_SAVEAS_TITLE . "'>"
                                                       . TEXT_CORRECT_SAVEAS . "</button>\n";
                                                echo "</div>\n";

                                            echo "</div>\n";
                                        echo "</div>\n";

                                        // ---- Card body: corrections table (collapsible) ----
                                        echo "<div id='card_body_corrections'"
                                           . " style='padding:6px 12px;max-height:120px;overflow-y:auto;'>\n";
                                            echo "<table id='table_info_correction' cellspacing='0'"
                                               . " style='width:100%;font-size:12px;'>\n";
                                                echo "<thead>\n";
                                                    echo "<th style='width:30%;'>"                  . TEXT_CORRECT_COL_TYPE  . "</th>\n";
                                                    echo "<th style='width:25%;'>"                  . TEXT_CORRECT_COL_START . "</th>\n";
                                                    echo "<th style='width:25%;'>"                  . TEXT_CORRECT_COL_END   . "</th>\n";
                                                    echo "<th style='width:8%;text-align:center;'>&nbsp;</th>\n";
                                                    echo "<th style='width:4%;text-align:center;'>&nbsp;</th>\n";
                                                    echo "<th style='width:4%;text-align:center;'>&nbsp;</th>\n";
                                                    echo "<th style='width:4%;text-align:center;'>&nbsp;</th>\n";
                                                echo "</thead>\n";
                                                echo "<tbody></tbody>";
                                            echo "</table>\n";
                                        echo "</div>\n";

                                    echo "</div>\n";

                                    // Zoom controls
                                    echo "<div style='height:25px;margin-right:15px;'>";

                                        echo "<div style='float:right;'>";
                                            echo "<input type='checkbox' id='check_zoom_x_" . $cle_station . "'"
                                               . " checked onclick='zoomCTRL(" . $cle_station . ");'>";
                                            echo "<span style='margin-left:5px;font-size:11px;font-weight:normal;'>"
                                               . TEXT_CORRECT_ZOOM_X . "</span>";
                                        echo "</div>";

                                        echo "<div style='float:right;margin-right:15px;'>";
                                            echo "<input type='checkbox' id='check_zoom_y_" . $cle_station . "'"
                                               . " checked onclick='zoomCTRL(" . $cle_station . ");'>";
                                            echo "<span style='margin-left:5px;font-size:11px;font-weight:normal;'>"
                                               . TEXT_CORRECT_ZOOM_Y . "</span>";
                                        echo "</div>";

                                    echo "</div>";

                                    // Graph div + loading spinner
                                    echo "<div id='plot_" . $cle_station . "' class='graph'"
                                       . " style='height:50vh;margin:0 10px;'></div>\n";

                                    // Quality-code meta timeline (replaces the rangeslider).
                                    // Filled by drawMetaTimelineCore() after each graph load,
                                    // kept x-synced with the chart on zoom/pan.
                                    echo "<div id='plot_meta_" . $cle_station . "'"
                                       . " style='width:98%;margin:0 1%;display:none;'></div>\n";

                                    echo "<div id='wait_" . $cle_station . "'"
                                       . " style='width:100%;height:50vh;text-align:center;'>";
                                        echo "<img src='" . DIR_WS_IMG . "wait.gif'"
                                           . " style='width:50px;margin-top:10%;'"
                                           . " title='" . TEXT_CORRECT_LOADING . "'>";
                                        echo "<p>" . TEXT_CORRECT_LOADING . "</p>";
                                    echo "</div>\n";

                                    // Accumulate Plotly sync commands for scale updates
                                    $js_syncAbsc_var .= "Plotly.relayout('plot_" . $cle_station
                                                      . "', {'xaxis.range': [x1_format, x2_format]});";
                                    $js_syncOrdon_var .= "Plotly.relayout('plot_" . $cle_station
                                                       . "', {'yaxis.range': [y1, y2]});";

                                    // ---- Graph toolbar ----
                                    // Left group: decimals (+/-) on top, Log scale below.
                                    // Right of it: the Points checkbox, vertically centred.
                                    echo "<div id='box_options_" . $cle_station . "'"
                                       . " style='float:left;margin-left:25px;display:flex;align-items:center;gap:12px;'>";

                                        // Axis controls stacked: +/- then Log scale.
                                        echo "<div style='display:flex;flex-direction:column;gap:3px;'>";
                                            echo "<div>";
                                                echo "<button id='plus_" . $cle_station . "' class='decimal_axe'"
                                                   . " title='" . TEXT_CORRECT_ADD_DECIMAL . "'"
                                                   . " onCLick=\"updateDecimals('plot_" . $cle_station . "','yaxis','+');\">+</button>\n";
                                                echo "<button id='moins_" . $cle_station . "' class='decimal_axe'"
                                                   . " title='" . TEXT_CORRECT_REMOVE_DECIMAL . "'"
                                                   . " onCLick=\"updateDecimals('plot_" . $cle_station . "','yaxis','-');\">-</button>\n";
                                            echo "</div>";
                                            echo "<button id='log_" . $cle_station . "' class='log_axe'"
                                               . " style='margin:0;'"
                                               . " title='" . TEXT_CORRECT_LOG_SCALE_TITLE . "'>"
                                               . TEXT_CORRECT_LOG_SCALE . "</button>\n";
                                        echo "</div>";

                                        // Previous-zoom button. Steps back one level in the zoom
                                        // history each click. Disabled (greyed) when the history
                                        // is empty. Applies the stored range via Plotly.relayout,
                                        // exactly like a user zoom, so the markers recount and the
                                        // meta timeline refresh through their existing listeners.
                                        echo "<button id='zoom_back_" . $cle_station . "' class='log_axe'"
                                           . " style='margin:0;opacity:0.4;cursor:not-allowed;' disabled"
                                           . " title='" . TEXT_CORRECT_ZOOM_BACK_TITLE . "'"
                                           . " onclick=\"zoomBack('" . $cle_station . "');\">"
                                           . TEXT_CORRECT_ZOOM_BACK . "</button>\n";

                                        // Forward-zoom (redo) button. Re-applies the view we left
                                        // when stepping back. Disabled (greyed) when there is
                                        // nothing to redo. Any new user zoom clears the redo stack.
                                        echo "<button id='zoom_fwd_" . $cle_station . "' class='log_axe'"
                                           . " style='margin:0;opacity:0.4;cursor:not-allowed;' disabled"
                                           . " title='" . TEXT_CORRECT_ZOOM_FORWARD_TITLE . "'"
                                           . " onclick=\"zoomForward('" . $cle_station . "');\">"
                                           . TEXT_CORRECT_ZOOM_FORWARD . "</button>\n";

                                        // Markers toggle to the right (hidden until a line series loads).
                                        echo "<span id='box_markers_" . $cle_station . "'"
                                           . " style='display:none;align-items:center;'>\n";
                                            echo "<label style='font-size:11px;color:#428bca;cursor:pointer;user-select:none;white-space:nowrap;'"
                                               . " title='" . TEXT_CORRECT_MARKERS_TITLE . "'>";
                                                echo "<input type='checkbox' id='check_markers_" . $cle_station . "'"
                                                   . " style='margin-right:4px;vertical-align:middle;'"
                                                   . " onchange=\"toggleMarkers('" . $cle_station . "', this.checked);\">";
                                                echo TEXT_CORRECT_MARKERS_LABEL;
                                                echo " <span id='markers_hint_" . $cle_station . "'"
                                                   . " style='font-size:10px;color:#999;'></span>";
                                            echo "</label>\n";
                                        echo "</span>\n";

                                        // ---- Point deletion panel (discreet, appears once
                                        //      a point is removed via right-click) ----
                                        echo "<span id='box_del_" . $cle_station . "'"
                                           . " style='display:none;align-items:center;gap:10px;margin-left:14px;'>\n";
                                            echo "<span style='width:1px;height:30px;background:#ddd;'></span>";
                                            echo "<span id='del_hint_" . $cle_station . "'"
                                               . " style='font-size:11px;color:#EA1179;font-weight:bold;white-space:nowrap;'></span>\n";
                                            // Undo / Save stacked vertically, both at Undo's width.
                                            echo "<span style='display:inline-flex;flex-direction:column;gap:3px;align-items:stretch;'>\n";
                                                echo "<button id='del_undo_" . $cle_station . "' class='decimal_axe'"
                                                   . " style='font-size:11px;text-align:center;'"
                                                   . " title='" . TEXT_CORRECT_DEL_UNDO_TITLE . "'"
                                                   . " onclick=\"undoDeletePointBtn('" . $cle_station . "');\">"
                                                   . TEXT_CORRECT_DEL_UNDO . "</button>\n";
                                                echo "<button id='del_save_" . $cle_station . "' class='decimal_axe'"
                                                   . " style='font-size:11px;text-align:center;color:#09886d;font-weight:bold;'"
                                                   . " title='" . TEXT_CORRECT_DEL_SAVE_TITLE . "'"
                                                   . " onclick=\"saveDeletePoints('" . $cle_station . "');\">"
                                                   . TEXT_CORRECT_DEL_SAVE . "</button>\n";
                                            echo "</span>\n";
                                        echo "</span>\n";

                                    echo "</div>";

                                    // ---- User guidance lines ----
                                    // Sit on the SAME row as box_options (which floats left):
                                    // this block floats right, so the +/-/Log controls stay at
                                    // the left and the hints align to the right at the same
                                    // height. The trailing <hr> clears both floats.
                                    // Inside, an inline-grid keeps the two icons in one column
                                    // (fixed width, centred) and both texts left-aligned, so
                                    // the text starts at the same x despite the crosshair and
                                    // the yellow square differing in width.

                                    // Crosshair icon (recalls the mouse cursor over the graph).
                                    $icon_crosshair = "<svg width='13' height='13' viewBox='0 0 13 13'"
                                        . " style='vertical-align:middle;'>"
                                        . "<line x1='6.5' y1='0' x2='6.5' y2='13' stroke='#016A70' stroke-width='1.5'/>"
                                        . "<line x1='0' y1='6.5' x2='13' y2='6.5' stroke='#016A70' stroke-width='1.5'/>"
                                        . "</svg>";

                                    echo "<div style='float:right;margin-right:2%;padding:8px 0;box-sizing:border-box;'>";

                                        echo "<div style='display:inline-grid;grid-template-columns:18px auto;"
                                           . "gap:6px 6px;align-items:center;text-align:left;"
                                           . "font-size:13px;color:#666;font-style:italic;'>";

                                            // Row 1 — period selection (Shift + drag)
                                            echo "<span style='justify-self:center;'>" . $icon_crosshair . "</span>";
                                            echo "<span>" . TEXT_CORRECT_SELECT_PERIOD_HINT . "</span>";

                                            // Row 2 — field-visit yellow marker
                                            echo "<span style='justify-self:center;'>"
                                               . "<span style='display:inline-block;width:11px;height:11px;"
                                               . "background:#FFE100;border:1px solid #000;vertical-align:middle;'></span>"
                                               . "</span>";
                                            echo "<span>" . TEXT_GRAPH_FR_CTRLCLICK_HINT . "</span>";

                                        echo "</div>";

                                    echo "</div>\n";

                                echo "<hr style='clear:both;'>\n";
                                echo "</div>\n";
                            }
                        }
                    }
                    else
                    {
                        echo "<div id='boxpopup'>\n";
                            echo "<p class='alert'>" . TEXT_CORRECT_NO_DATA . "</p>";
                        echo "<hr>";
                        echo "</div>";
                    }


                    // ---- (Corrections table moved into the graph box, above
                    //       the plot div — see the foreach loop above.)


                echo "<hr>\n";
                echo "</div>\n"; // main graph panel

            echo "</div>";
        echo "</div>";
    echo "</div>";

    require('include/application_bottom.php');

echo "</body>";
echo "</html>";
?>

<style>
    /* Tools dropdown (ported from graph_chron.php for a consistent UI) */
    /* Raise the action area above the title <p> and neutralise any
       float/pointer rules formulaire.css may impose inside it. */
    .graph-header { position:relative; }
    .graph-header .graph-title { position:relative; z-index:1; }
    .graph-actions { z-index:1000 !important; }
    .graph-actions, .graph-actions * { pointer-events:auto; }
    .hp-menu { position:relative; display:inline-block; float:none; }
    .hp-btn-tools {
        background:#fff; color:#2c2c2a; border:0;
        border-radius:6px; padding:7px 14px;
        font-size:13px; font-weight:500; cursor:pointer;
        display:inline-flex; align-items:center; gap:6px; line-height:1;
        float:none; margin:0; min-width:0; width:auto;
    }
    .hp-btn-tools:hover { background:#f0f0f0; }
    .hp-caret { font-size:10px; }

    .hp-menu-list {
        display:none; position:absolute; right:0; top:calc(100% + 4px);
        min-width:185px; background:#fff;
        border:1px solid #d4d4d4; border-radius:8px;
        box-shadow:0 4px 16px rgba(0,0,0,.18);
        overflow:hidden; z-index:100000; float:none;
    }
    .hp-menu-list.open { display:block !important; }
    .hp-menu-item {
        display:flex; align-items:center; gap:9px;
        padding:9px 12px; font-size:13px; color:#2c2c2a;
        cursor:pointer; white-space:nowrap;
        border-bottom:1px solid #f0f0f0; float:none;
    }
    .hp-menu-item:last-child { border-bottom:0; }
    .hp-menu-item:hover { background:#f3f7f9; }
    .hp-mi-ico { font-size:14px; width:18px; text-align:center; color:#666; }

    /* ---- Data popups (gaps) — modern modal ---- */
    .hp-modal-overlay {
        display:none; position:fixed; inset:0;
        background:rgba(15,23,30,.45);
        z-index:100001; align-items:flex-start; justify-content:center;
    }
    .hp-modal-overlay.open { display:flex; }
    .hp-modal {
        background:#fff; width:min(880px,92vw); max-height:86vh;
        margin-top:6vh; border-radius:12px; overflow:hidden;
        display:flex; flex-direction:column;
        box-shadow:0 18px 50px rgba(0,0,0,.30);
        font-family:'Open Sans',Arial,sans-serif;
    }
    .hp-modal-head {
        display:flex; align-items:flex-start; justify-content:space-between;
        gap:12px; padding:16px 20px; background:#176B87; color:#fff;
    }
    .hp-modal-head h3 { margin:0; font-size:20px; font-weight:bold; color:#fff; }
    .hp-modal-head .hp-sub { font-size:12px; opacity:.9; margin-top:4px; color:#fff; }
    .hp-modal-close {
        background:transparent; border:0; color:#fff; font-size:20px;
        cursor:pointer; line-height:1; padding:0 2px;
    }
    .hp-modal-toolbar {
        display:flex; gap:8px; padding:10px 20px;
        border-bottom:1px solid #eee; background:#fafafa;
    }
    .hp-modal-toolbar button {
        background:#fff; border:1px solid #d4d4d4; border-radius:6px;
        padding:6px 12px; font-size:13px; cursor:pointer;
        display:inline-flex; align-items:center; gap:6px; color:#2c2c2a;
    }
    .hp-modal-toolbar button:hover { background:#f0f4f6; }
    .hp-btn-spinner {
        display:inline-block; width:13px; height:13px;
        border:2px solid #c9d4da; border-top-color:#3c8da5;
        border-radius:50%; vertical-align:-2px;
        animation:hpBtnSpin 0.7s linear infinite;
    }
    @keyframes hpBtnSpin { to { transform:rotate(360deg); } }
    .hp-modal-body { overflow:auto; padding:0 20px 18px; }
    .hp-table { width:100%; border-collapse:collapse; font-size:13px; margin-top:12px; }
    .hp-table th {
        position:sticky; top:0; background:#eef3f8; color:#2c2c2a;
        text-align:left; font-weight:bold; padding:8px 10px;
        border-bottom:2px solid #d4dde6; white-space:nowrap;
    }
    .hp-table td { padding:7px 10px; border-bottom:1px solid #eee; vertical-align:top; }
    .hp-table tr:hover td { background:#f7fbfd; }
    .hp-qc-swatch {
        display:inline-block; width:12px; height:12px; border-radius:2px;
        border:1px solid #000; vertical-align:middle; margin-right:6px;
    }
    .hp-empty { padding:30px; text-align:center; color:#888; font-style:italic; }
</style>

<script>

    var territoire_id         = "<?php echo $territoire_id; ?>";
    var timezone_php          = "<?php echo $timezone_php; ?>";
    var territoire_lang       = "<?php echo LANGUAGE; ?>";

    var boxWait               = document.getElementById('box_wait');
    var id_correction         = <?php echo $id_correction; ?>;
    var id_user               = <?php echo $id_user; ?>;
    var id_station_encours    = "<?php echo $id_station_encours; ?>";
    var id_type_station_encours = "<?php echo $id_typedata_encours; ?>";
    var id_chron_encours      = "<?php echo $id_chron_encours; ?>";
    var to_periode_encours    = <?php echo (int)($to_periode_encours ?? 0); ?>;
    var id_create_chron_encours = <?php echo (int)($id_create_chron_encours ?? 0); ?>;
    var tab_type_data_array   = <?php echo json_encode($typedata_array); ?>;
    var colorTab              = <?php echo json_encode($colorMapping); ?>;
    var text_create_chron_encours = "<?php
        echo ($id_create_chron_encours > 0)
            ? $type_chron_array[$id_create_chron_encours]['init_type_data']
              . ' - ' . $type_chron_array[$id_create_chron_encours]['nom_type_data']
            : '';
    ?>";

    var tbody_info            = document.querySelector('#table_info_correction tbody');
    var contenuMsg            = document.getElementById('contenu_info');
    var blockValidCorrection  = document.getElementById('block_valid_correction');
    var buttonSave            = document.getElementById('button_save');
    var buttonSaveAs          = document.getElementById('button_saveas');
    var waitValidSave         = document.getElementById('wait_valid_save');
    var decimalPlaces         = 1;

    // JS error/info messages injected from PHP constants
    var LANG_CORRECT = {
        errGenerate  : '<?= TEXT_CORRECT_JS_ERR_GENERATE ?>',
        errServer    : '<?= TEXT_CORRECT_JS_ERR_SERVER ?>',
        errSelectOne : '<?= TEXT_CORRECT_JS_ERR_SELECT_ONE ?>',
        errDateOrder : '<?= TEXT_CORRECT_JS_ERR_DATE_ORDER ?>',
        errTimeFmt   : '<?= TEXT_CORRECT_JS_ERR_TIME_FMT ?>',
        errDateFmt   : '<?= TEXT_CORRECT_JS_ERR_DATE_FMT ?>',
        errYNum      : '<?= TEXT_CORRECT_JS_ERR_Y_NUM ?>',
        errNoTargetChron : '<?= TEXT_CORRECT_JS_ERR_NO_TARGET ?>'
    };

    // (The 'bloc_create_chron' / 'id_create_chron' / 'text_create_chron'
    // population block was removed along with the Temporal-aggregation
    // form. id_create_chron_encours / text_create_chron_encours are kept
    // as JS variables so the AJAX payload below stays backward-compatible
    // with any other caller that might still rely on calcul_chron_new.)


    // -----------------------------------------------
    // Generate a correction via AJAX

    function correctionData(id_station, id_chron, type_correction, calcul_correction, axe_correction, pastemps=0, modecalcul='none', unite='min', gapThreshold=10)
    {
        document.getElementById('wait_' + id_station).style.display = 'block';
        document.getElementById('plot_' + id_station).style.display = 'none';

        var datetime_first = document.getElementById('x1Zoom').value + ' ' + document.getElementById('x1Zoom_h').value;
        var datetime_end   = document.getElementById('x2Zoom').value + ' ' + document.getElementById('x2Zoom_h').value;

        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'include/structure/calcul/process_chron_calcul.php', true);
        xhr.setRequestHeader('Content-Type', 'application/json');

        xhr.onreadystatechange = function()
        {
            if (xhr.readyState === 4 && xhr.status === 200)
            {
                var jsonResponse = JSON.parse(xhr.responseText);

                contenuMsg.innerHTML    = jsonResponse['msg_newCorrection'];
                contenuMsg.style.border  = '2px solid #09886d';
                contenuMsg.style.display = 'block';

                id_correction = jsonResponse['id_correction'];
                afficheCorrection(id_correction);
            }
        };

        // 'unite' is consumed by the backend's calcul_pastemps branch to
        // pick the right SQL routing (sliding window vs calendar-true).
        // 'gapThreshold' is used by calcul_pastemps in 'moy' mode to decide
        // whether a bucket with N% gap should be marked as a gap itself
        // (above threshold) or just annotated in obs (below threshold).
        xhr.send(JSON.stringify({
            id_user, id_correction, id_station, id_chron,
            datetime_first, datetime_end,
            page_window_first, page_window_last,
            type_correction, calcul_correction, axe_correction,
            pastemps, modecalcul, unite, gapThreshold,
            to_periode_encours, id_create_chron_encours
        }));
    }


    // -----------------------------------------------
    // Delete a correction entry

    function delCorrection(id_meta)
    {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'include/structure/calcul/process_chron_calcul_del.php', true);
        xhr.setRequestHeader('Content-Type', 'application/json');

        xhr.onreadystatechange = function()
        {
            if (xhr.readyState === 4 && xhr.status === 200)
            {
                var jsonResponse = JSON.parse(xhr.responseText);

                contenuMsg.innerHTML    = jsonResponse['msg_del'];
                contenuMsg.style.border  = '2px solid #09886d';
                contenuMsg.style.display = 'block';

                id_correction = jsonResponse['id_correction'];
                afficheCorrection(id_correction);
            }
        };

        xhr.send(JSON.stringify({ id_meta: id_meta }));
    }


    // -----------------------------------------------
    // Display corrections in table + reload graph

    function afficheCorrection(id_correction)
    {
        // Preserve the current zoom across the graph reload this triggers, so
        // loading / recalculating / deleting / validating a correction keeps
        // the user's current view instead of snapping back to the full range.
        // On the very first page load there is no chart yet, so this captures
        // nothing (the try/catch and the autorange check both guard that).
        try {
            var gdNow = document.getElementById('plot_' + id_station_encours);
            var fx    = gdNow && gdNow._fullLayout ? gdNow._fullLayout.xaxis : null;
            if (fx && fx.range && fx.range.length === 2 && !fx.autorange) {
                __preserveRange[id_station_encours] = [fx.range[0], fx.range[1]];
            }
        } catch (e) { /* fall back to full view if anything fails */ }

        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'include/structure/calcul/process_chron_calcul_view.php', true);
        xhr.setRequestHeader('Content-Type', 'application/json');

        xhr.onreadystatechange = function()
        {
            if (xhr.readyState === 4 && xhr.status === 200)
            {
                var jsonResponse = JSON.parse(xhr.responseText);

                tbody_info.innerHTML = jsonResponse['tab_html'];
                var id_meta          = jsonResponse['id_meta'];

                if (id_meta > 0)
                {
                    blockValidCorrection.style.display = 'block';
                    if (buttonSave) buttonSave.style.display = 'block';
                    buttonSaveAs.style.display         = 'block';
                }
                else
                {
                    blockValidCorrection.style.display = 'none';
                    if (buttonSave) buttonSave.style.display = 'none';
                    buttonSaveAs.style.display         = 'none';
                }

                load_graph(id_station_encours, id_type_station_encours, tab_type_data_array);
            }
        };

        xhr.send(JSON.stringify({ id_correction: id_correction }));
    }


    // -----------------------------------------------
    // Validate and save corrections to database

    function validCorrection(tabIdMeta)
    {
        var idTypeChron = document.getElementById('id_modif_chron').value;
        var idCodeQual  = document.getElementById('select_qual_chron').value;
        var obsUser     = document.getElementById('obs_user').value;

        if (buttonSave) buttonSave.style.display = 'none';
        buttonSaveAs.style.display = 'none';
        if (waitValidSave) waitValidSave.style.display = 'block';

       
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'include/structure/calcul/process_chron_calcul_valid.php', true);
        xhr.setRequestHeader('Content-Type', 'application/json');

        xhr.onreadystatechange = function()
        {
            if (xhr.readyState === 4 && xhr.status === 200)
            {
                var jsonResponse = JSON.parse(xhr.responseText);

                if (waitValidSave) waitValidSave.style.display = 'none';
                if (buttonSave) buttonSave.style.display = 'block';
                buttonSaveAs.style.display  = 'block';

                contenuMsg.innerHTML    = jsonResponse['msg_valid'];
                contenuMsg.style.border  = '2px solid #09886d';
                contenuMsg.style.display = 'block';

                afficheCorrection(id_correction);
            }
        };

        xhr.send(JSON.stringify({
            territoire_id, timezone_php, territoire_lang: territoire_lang,
            id_correction, tabIdMeta, idTypeChron, idCodeQual, obsUser
        }));
    }


    // -----------------------------------------------
    // Download a corrected series as CSV

    function download_chron(id_meta_correct)
    {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'include/structure/calcul/process_chron_calcul_download.php', true);
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
                        var downloadLink      = document.createElement('a');
                        downloadLink.href     = 'data/export/temp/' + jsonResponse['csvFile'];
                        downloadLink.download = jsonResponse['csvFile'];
                        document.body.appendChild(downloadLink);
                        downloadLink.click();
                        document.body.removeChild(downloadLink);
                    }
                    else
                    {
                        contenuMsg.innerHTML    = LANG_CORRECT.errGenerate;
                        contenuMsg.style.border  = '2px solid #930000';
                        contenuMsg.style.display = 'block';
                    }
                }
                else
                {
                    contenuMsg.innerHTML    = LANG_CORRECT.errServer;
                    contenuMsg.style.border  = '2px solid #930000';
                    contenuMsg.style.display = 'block';
                }
            }
        };

        xhr.send(JSON.stringify({ id_meta_correct: id_meta_correct }));
    }


    // -----------------------------------------------
    // Save correction (with or without save-as dialog)

    function saveCorrection(saveas)
    {
        contenuMsg.style.display = 'none';
        
        var popup_verif_savedata = document.getElementById('box_verif_savedata');
        var cadre_modif_chron    = document.getElementById('cadre_modif_chron');

        var checkboxes   = document.querySelectorAll('input[name="checkCorrection[]"]:checked');

        if (checkboxes.length > 0)
        {
            popup_verif_savedata.style.display = 'block';
            cadre_modif_chron.style.display    = 'block';
            
            initDraggable("title_verif_savedata", "box_verif_savedata");

            var selectChron = document.getElementById('select_type_chron');
            selectChron.style.display = saveas ? 'block' : 'none';

            if (saveas)
            {
                // Save as : la cible doit être choisie explicitement par
                // l'utilisateur. On part d'une sélection vide (l'option
                // placeholder disabled du <select>) et on ne pré-remplit
                // PAS les champs cachés — la validation vérifiera qu'une
                // chronique d'accueil a bien été sélectionnée.
                selectChron.selectedIndex = 0; // option vide / placeholder
                textSelectChron.value     = '';
                idSelectChron.value       = '';
            }
            else
            {
                // Save : on enregistre dans la chronique courante.
                var input_text_chron  = document.getElementById('text_chron_' + id_chron_encours).value;
                textSelectChron.value = input_text_chron;
                idSelectChron.value   = id_chron_encours;
            }

            var okButton = document.getElementById('ok_valid_savedata');
            var noButton = document.getElementById('no_valid_savedata');

            if (!okButton.dataset.listenerAdded)
            {
                okButton.dataset.listenerAdded = true;
                okButton.addEventListener('click', function()
                {
                    // Déterminer le mode au moment du clic (le listener
                    // n'est attaché qu'une fois ; se fier à la closure
                    // 'saveas' figerait le mode du premier appel). Le
                    // <select> n'est visible qu'en Save as.
                    var isSaveAs = (selectChron.style.display !== 'none');

                    // En Save as, exiger le choix d'une chronique d'accueil.
                    // idSelectChron reste vide tant que l'utilisateur n'a
                    // pas sélectionné une série cible dans le <select>.
                    if (isSaveAs && (!idSelectChron.value || idSelectChron.value === ''))
                    {
                        contenuMsg.innerHTML     = LANG_CORRECT.errNoTargetChron;
                        contenuMsg.style.border  = '2px solid #930000';
                        contenuMsg.style.display = 'block';
                        return; // on NE ferme PAS le popup, on NE valide PAS
                    }

                    // Relire les checkboxes au moment du clic, pas à la création du listener
                    var checkboxes   = document.querySelectorAll('input[name="checkCorrection[]"]:checked');
                    var tabCheckData = [];
                    checkboxes.forEach(function(cb)
                    {
                        var tab_check = cb.value.split('_');
                        tabCheckData.push(tab_check[1]); 
                    });

                    popup_verif_savedata.style.display = 'none';
                    validCorrection(tabCheckData);
                });
            }

            if (!noButton.dataset.listenerAdded)
            {
                noButton.dataset.listenerAdded = true;
                noButton.addEventListener('click', function()
                {
                    popup_verif_savedata.style.display = 'none';
                });
                
            }
        }
        else
        {
            contenuMsg.innerHTML    = LANG_CORRECT.errSelectOne;
            contenuMsg.style.border  = '2px solid #930000';
            contenuMsg.style.display = 'block';
        }
    }


    // -----------------------------------------------
    // Button event listeners for correction actions

    document.getElementById('calcul_valeur').addEventListener('click', function()
    {
        var valeur_a = document.getElementById('valeur_a').value;
        var valeur_b = document.getElementById('valeur_b').value;
        correctionData(id_station_encours, id_chron_encours, 'calcul', valeur_a + 'Y + ' + valeur_b, 'Ord. (Y)');
    });

    /*
    document.getElementById('calcul_copy').addEventListener('click', function()
    {
        correctionData(id_station_encours, id_chron_encours, 'calcul', '1Y + 0', 'Ord. (Y)');
    });
    */

    document.getElementById('calcul_date').addEventListener('click', function()
    {
        var operateur_x        = document.getElementById('operateur_x').value;
        var valeur_operation_x = document.getElementById('valeur_operation_x').value;
        correctionData(id_station_encours, id_chron_encours, 'decalage_date', operateur_x + valeur_operation_x, 'Ord. (X)');
    });

    document.getElementById('calcul_lissage').addEventListener('click', function()
    {
        var seuilLiss = document.getElementById('seuil_liss').value;
        correctionData(id_station_encours, id_chron_encours, 'lissage', seuilLiss, 'Ord. (Y)');
    });

    document.getElementById('calcul_lacune').addEventListener('click', function()
    {
        correctionData(id_station_encours, id_chron_encours, 'lacune', '', '-');
    });

    document.getElementById('create_chron_min').addEventListener('click', function()
    {
        var selectPastempsMode = document.getElementById('select_pastemps_mode').value;
        var selectPastempsUnit = document.getElementById('select_pastemps_unit').value;
        var inputPastemps      = parseInt(document.getElementById('input_pastemps').value, 10);

        if (isNaN(inputPastemps) || inputPastemps < 1) {
            alert('Interval value must be a positive integer.');
            return;
        }

        // Month/year use calendar-true aggregation server-side, which only
        // makes sense for n=1 (every-N months requires a custom MySQL boucle
        // we haven't implemented yet). Force-clamp n to 1 and notify the
        // user via the input so they see what was sent.
        if ((selectPastempsUnit === 'm' || selectPastempsUnit === 'y') && inputPastemps !== 1) {
            inputPastemps = 1;
            document.getElementById('input_pastemps').value = '1';
        }

        // Gap threshold (only meaningful in 'moy' mode). Default 50 and
        // clamp to a sensible range so the backend never sees garbage.
        var gapThreshold = parseInt(document.getElementById('input_gap_threshold').value, 10);
        if (isNaN(gapThreshold) || gapThreshold < 0)   { gapThreshold = 0;   }
        if (gapThreshold > 100)                        { gapThreshold = 100; }
        document.getElementById('input_gap_threshold').value = gapThreshold;

        // Human-readable label used as the obs/info for the correction row.
        // Kept simple so each correction is unambiguous in the saved table.
        var unitLabel = { 'min':'min', 'h':'h', 'd':'d', 'm':'month', 'y':'year' }[selectPastempsUnit] || selectPastempsUnit;
        var calcul_correction = 'Génération Chronique <br> pas de temps (' + selectPastempsMode + ') : '
                              + inputPastemps + ' ' + unitLabel;

        // The backend (process_chron_calcul.php, calcul_pastemps branch)
        // reads the unit from the dedicated 'unite' field. We pass it
        // alongside the existing pastemps/modecalcul arguments.
        correctionData(
            id_station_encours,
            id_chron_encours,
            'calcul_pastemps',
            calcul_correction,
            'Abs. (X)',
            inputPastemps,
            selectPastempsMode,
            selectPastempsUnit,
            gapThreshold
        );
    });

    // Toggle the Gap threshold row visibility based on the calc mode.
    // The threshold only makes sense in 'moy' mode — in 'cumul' mode the
    // rule is hard ("≥1 gap = gap"), so hide the row to avoid confusion.
    (function bindGapThresholdToggle() {
        var modeSelect      = document.getElementById('select_pastemps_mode');
        var thresholdBlock  = document.getElementById('cadre_gap_threshold');
        if (!modeSelect || !thresholdBlock) { return; }

        function refreshThresholdVisibility() {
            thresholdBlock.style.display = (modeSelect.value === 'moy') ? 'flex' : 'none';
        }

        modeSelect.addEventListener('change', refreshThresholdVisibility);
        refreshThresholdVisibility(); // initial state
    })();

    // (create_chron_dmy / Temporal aggregation handler removed — the unified
    // time-step form now covers month/year cases via the 'unite' field.)

    // Sync lacune period fields when date inputs change
    function syncPeriodeLacune()
    {
        var x1 = document.getElementById('x1Zoom').value;
        var x1h = document.getElementById('x1Zoom_h').value;
        var x2 = document.getElementById('x2Zoom').value;
        var x2h = document.getElementById('x2Zoom_h').value;

        if (isValidDate(x1) && isValidDate(x2) && isValidTime(x1h) && isValidTime(x2h))
        {
            document.getElementById('periode_lacune_first').value = 'du ' + x1 + ' à ' + x1h;
            document.getElementById('periode_lacune_end').value   = 'au ' + x2 + ' à ' + x2h;
        }
    }

    document.getElementById('x1Zoom').addEventListener('change', syncPeriodeLacune);
    document.getElementById('x2Zoom').addEventListener('change', syncPeriodeLacune);


    // -----------------------------------------------
    // Line-width control (+/-) applied to every line trace
    //
    // The trick: 'line.width' means different things depending on the
    // trace type.
    //   - For scatter/scattergl with mode='lines' or 'lines+markers',
    //     it's the curve thickness.
    //   - For bar traces, 'line.width' is IGNORED — barres use
    //     'marker.line.width' for the outline thickness.
    //   - For 'scatter' with mode='markers' only, line.width doesn't
    //     apply either.
    //
    // We must:
    //   1. Filter traces by type so we only target the relevant ones.
    //   2. Apply 'line.width' or 'marker.line.width' depending on type.
    //   3. Use Plotly.restyle with EXPLICIT trace-index list (4th arg)
    //      so Plotly doesn't fall back to layout-level interpretation.
    (function bindLineWidthControl() {
        var minusBtn = document.getElementById('line_width_minus');
        var plusBtn  = document.getElementById('line_width_plus');
        if (!minusBtn || !plusBtn) {
            console.warn('[line_width] buttons not found');
            return;
        }

        var MIN_WIDTH = 1;
        var MAX_WIDTH = 6;

        window.__currentLineWidth = window.__currentLineWidth || 1.5;

        function applyWidth() {
            var newW = window.__currentLineWidth;
            var plots = document.querySelectorAll("[id^='plot_']");
            console.log('[line_width] applying width=' + newW + ' to ' + plots.length + ' plot(s)');

            plots.forEach(function (div) {
                if (!div || !div.data || div.data.length === 0) { return; }

                // Partition trace indices by type so we send the
                // right attribute path to each subset.
                var lineIndices = [];
                var barIndices  = [];

                div.data.forEach(function (trace, idx) {
                    var t = trace.type || 'scatter';

                    // Skip helper/utility traces that have no user-visible
                    // name. The backend adds "invisible" scattergl traces
                    // (e.g. a 2-point baseline line at y=0 used for visual
                    // alignment) and tiny marker traces — both pollute the
                    // restyle if we hit them. A trace without 'name' is
                    // never something the user cares about visually.
                    if (!trace.name || trace.name === '') {
                        return;
                    }

                    // scattergl / scatter (with line mode) -> line.width
                    if (t === 'scatter' || t === 'scattergl') {
                        // Only restyle if the trace actually draws lines.
                        // 'markers' mode has no line to thicken.
                        if (trace.mode && trace.mode.indexOf('lines') === -1) {
                            return;
                        }
                        lineIndices.push(idx);
                    } else if (t === 'bar') {
                        barIndices.push(idx);
                    } else {
                        // Other types (heatmap, histogram, etc.) — skip.
                    }
                });

                console.log('  plot', div.id, 'line traces:', lineIndices, 'bar traces:', barIndices);

                if (lineIndices.length > 0) {
                    Plotly.restyle(div, { 'line.width': newW }, lineIndices);
                }
                if (barIndices.length > 0) {
                    Plotly.restyle(div, { 'marker.line.width': newW }, barIndices);
                }
            });
        }

        // Expose globally so load_graph() can re-apply after newPlot.
        // A freshly drawn plot resets all trace widths to their backend
        // defaults; we need to push the user-chosen width back in.
        window.__reapplyLineWidth = applyWidth;

        minusBtn.addEventListener('click', function () {
            if (window.__currentLineWidth > MIN_WIDTH) {
                window.__currentLineWidth--;
                applyWidth();
            }
        });

        plusBtn.addEventListener('click', function () {
            if (window.__currentLineWidth < MAX_WIDTH) {
                window.__currentLineWidth++;
                applyWidth();
            }
        });
    })();


    // -----------------------------------------------
    // Collapsible corrections table
    //
    // The card body shows / hides on a click on the header. The chevron
    // rotates 90° to indicate state (down = open, right = collapsed).
    // The Save / Save as... buttons stop event propagation so clicking
    // them doesn't collapse the panel.
    (function bindCorrectionsToggle() {
        var header  = document.getElementById('card_header_corrections');
        var body    = document.getElementById('card_body_corrections');
        var chevron = document.getElementById('card_chevron_corrections');
        if (!header || !body || !chevron) { return; }

        var isOpen = true; // open by default

        header.addEventListener('click', function () {
            isOpen = !isOpen;
            body.style.display       = isOpen ? 'block' : 'none';
            chevron.style.transform  = isOpen ? 'rotate(0deg)' : 'rotate(-90deg)';
        });
    })();


    // -----------------------------------------------
    // Graph rendering

    var idPlotZoom       = 0;
    var js_syncAbsc_var  = "<?php echo $js_syncAbsc_var; ?>";
    var js_syncOrdon_var = "<?php echo $js_syncOrdon_var; ?>";
    var min_x            = '<?php echo $date_2; ?>';
    var max_x            = '<?php echo $date_1; ?>';

    // i18n strings used by the point-deletion panel (built dynamically in JS).
    var TEXT_CORRECT_DEL_COUNT_1 = "<?php echo TEXT_CORRECT_DEL_COUNT_1; ?>";
    var TEXT_CORRECT_DEL_COUNT_N = "<?php echo TEXT_CORRECT_DEL_COUNT_N; ?>";

    // Fenêtre figée au CHARGEMENT de la page (avant toute manip graphe :
    // zoom, pan, édition des champs date). Renseignée une seule fois, au
    // premier retour de load_graph(), puis jamais réécrite. Sert de socle
    // pour la copie intégrale de la série source à la validation
    // (process_chron_calcul_valid.php) quand la cible diffère de la source.
    var page_window_first = '';
    var page_window_last  = '';

    // Range (ISO [min,max]) to re-apply after the NEXT load_graph() of a given
    // station, so a reload triggered by a correction validation keeps the
    // user's current zoom instead of snapping back to the full view.
    // { cle_station: [isoMin, isoMax] }
    var __preserveRange = {};

    // Custom Plotly toolbar icons
    const monIconeDisquette = { width:1000, height:1000, path:'M833.3,166.7v666.6H166.7V166.7H833.3 M833.3,83.3H166.7c-46,0-83.3,37.3-83.3,83.3v666.6c0,46,37.3,83.3,83.3,83.3h666.6c46,0,83.3-37.3,83.3-83.3V166.7C916.7,120.7,879.3,83.3,833.3,83.3L833.3,83.3z M500,333.3c-92,0-166.7,74.7-166.7,166.7c0,92,74.7,166.7,166.7,166.7s166.7-74.7,166.7-166.7C666.7,408,592,333.3,500,333.3L500,333.3z' };
    const iconPencil        = { width:1000, height:1000, path:'M713.6,125.4c-42.9-42.9-112.4-42.9-155.3,0l-433,433c-3.1,3.1-5.3,7-6.4,11.3l-43.3,173.2c-2.3,9.1,0.4,18.8,7.1,25.5c5.3,5.3,12.5,8.2,19.8,8.2c1.9,0,3.8-0.2,5.7-0.7l173.2-43.3c4.3-1.1,8.2-3.3,11.3-6.4l433-433c42.9-42.9,42.9-112.4,0-155.3L713.6,125.4z M176.6,680.1l-61.9,15.5l15.5-61.9l365-365l46.4,46.4L176.6,680.1z M587.9,268.7l-46.4-46.4l61.8-61.8c17.1-17.1,44.9-17.1,62,0l46.7,46.7c17.1,17.1,17.1,44.9,0,62L649.7,330.5L587.9,268.7z' };
    const iconCamera        = { width:1000, height:1000, path:'M900,200H700l-50-100H350l-50,100H100c-55,0-100,45-100,100v500c0,55,45,100,100,100h800c55,0,100-45,100-100V300C1000,245,955,200,900,200z M500,750c-138.1,0-250-111.9-250-250s111.9-250,250-250s250,111.9,250,250S638.1,750,500,750z M500,350c-82.8,0-150,67.2-150,150s67.2,150,150,150s150-67.2,150-150S582.8,350,500,350z' };

    var config = {
        responsive      : true,
        doubleClickDelay: 1000,
        scrollZoom      : true,
        displaylogo     : false,
        modeBarOrientation : 'v',
        displayModeBar  : true,
        modeBarButtons  : [[
            { name: 'Export SVG', icon: iconPencil, click: function(gd) { Plotly.downloadImage(gd, { format: 'svg', filename: 'HP-Graph' }); } },
            { name: 'Export PNG', icon: iconCamera, click: function(gd) { Plotly.downloadImage(gd, { format: 'png', filename: 'HP-Graph' }); } },
            'zoom2d', 'select2d', 'pan2d', 'resetScale2d'
        ]],
        modeBarButtonsToRemove: ['lasso2d', 'autoScale2d', 'zoomIn2d', 'zoomOut2d']
    };


    // -----------------------------------------------
    // Shift-held: temporarily switch every graph to range-selection mode
    // (dragmode 'select') so the user can highlight a period WITHOUT zooming,
    // then back to 'zoom' on release. The toolbar buttons (zoom2d / select2d)
    // remain available for a persistent mode change.

    var __shiftSelectActive = false;

    // True once the user has actually chosen a period (range selection or the
    // "Apply period" button). Used so the enlarged graph only shows the green
    // band when there's a real selection — not for the default full-period
    // prefill that sits in the date fields at load time.
    var __hasUserSelection = false;

    function __setDragmodeAllPlots(mode)
    {
        var plots = document.querySelectorAll('.graph');
        plots.forEach(function(div)
        {
            if (div && div.data && typeof Plotly !== 'undefined') {
                try { Plotly.relayout(div, { dragmode: mode }); } catch (e) {}
            }
        });
        // Also the enlarged graph if it is currently shown.
        var big = document.getElementById('cadre_limit');
        if (big && big.data && typeof Plotly !== 'undefined') {
            try { Plotly.relayout(big, { dragmode: mode }); } catch (e) {}
        }
    }

    document.addEventListener('keydown', function(e)
    {
        if (e.key === 'Shift' && !__shiftSelectActive) {
            __shiftSelectActive = true;
            __setDragmodeAllPlots('select');
        }
    });

    document.addEventListener('keyup', function(e)
    {
        if (e.key === 'Shift' && __shiftSelectActive) {
            __shiftSelectActive = false;
            // Delay the switch back to zoom: Plotly fires the selection's
            // relayout burst right after mouse-release, and the relayout handler
            // skips field sync only while dragmode is still 'select'. Returning
            // to zoom too early would let that burst overwrite the period fields.
            setTimeout(function(){ __setDragmodeAllPlots('zoom'); }, 250);
        }
    });

    // Safety: if focus is lost while Shift is held (e.g. alt-tab), reset to zoom.
    window.addEventListener('blur', function()
    {
        if (__shiftSelectActive) {
            __shiftSelectActive = false;
            __setDragmodeAllPlots('zoom');
        }
    });


    // =================================================================
    // MARKERS TOGGLE
    //
    // Discreet checkbox to show data points on a line series. Markers are
    // available only when the base-trace point count is <= MARKERS_THRESHOLD
    // (denser series would clutter the line and slow rendering).
    //
    // updateMarkersState() is called after every load_graph() so the widget
    // always reflects the current series. toggleMarkers() flips the base
    // trace (index 0) between 'lines' and 'lines+markers' via Plotly.restyle.
    // =================================================================

    var MARKERS_THRESHOLD = 5000;

    // Per-station state: remembers the user's last choice across reloads.
    var __markersState = {};

    function toggleMarkers(idStation, enabled)
    {
        var gd = document.getElementById('plot_' + idStation);
        if (!gd || !gd.data || gd.data.length === 0) { return; }

        try {
            Plotly.restyle(gd, { mode: enabled ? 'lines+markers' : 'lines' }, [0]);
        } catch (e) {
            console.warn('[toggleMarkers] restyle failed:', e);
        }

        if (!__markersState[idStation]) { __markersState[idStation] = {}; }
        __markersState[idStation].enabled = enabled;
    }

    function updateMarkersState(idStation, nbPoints, isLines)
    {
        var boxEl  = document.getElementById('box_markers_'   + idStation);
        var cbEl   = document.getElementById('check_markers_' + idStation);
        var hintEl = document.getElementById('markers_hint_'  + idStation);
        if (!boxEl || !cbEl || !hintEl) { return; }

        // Only relevant for line-type series.
        if (!isLines) { boxEl.style.display = 'none'; return; }
        boxEl.style.display = 'inline-flex';

        if (!__markersState[idStation]) { __markersState[idStation] = { enabled: false }; }

        if (nbPoints > MARKERS_THRESHOLD) {
            // Too dense: disable the checkbox, keep the line clean.
            cbEl.checked  = false;
            cbEl.disabled = true;
            cbEl.style.cursor = 'not-allowed';
            hintEl.textContent = '(' + nbPoints + ' pts)';
            toggleMarkers(idStation, false);
            __markersState[idStation].enabled = false;
        } else {
            cbEl.disabled = false;
            cbEl.style.cursor = 'pointer';
            hintEl.textContent = '(' + nbPoints + ' pts)';
            // Restore the user's previous choice if they had enabled markers.
            var wasEnabled = __markersState[idStation].enabled || false;
            cbEl.checked = wasEnabled;
            if (wasEnabled) { toggleMarkers(idStation, true); }
        }
    }

    /**
     * Count non-null points of the base trace (index 0) within [xMin, xMax]
     * (ISO/date strings or timestamps). Returns the count.
     */
    function countVisiblePoints(gd, xMin, xMax)
    {
        try {
            var trace = gd.data && gd.data[0];
            if (!trace || !trace.x || !trace.x.length) { return 0; }
            var tMin = new Date(xMin).getTime();
            var tMax = new Date(xMax).getTime();
            if (isNaN(tMin) || isNaN(tMax)) { return trace.x.length; }
            var n = 0;
            for (var i = 0; i < trace.x.length; i++) {
                if (trace.y[i] === null || trace.y[i] === undefined) { continue; }
                var t = new Date(trace.x[i]).getTime();
                if (t >= tMin && t <= tMax) { n++; }
            }
            return n;
        } catch (e) { return 0; }
    }


    // =================================================================
    // ZOOM HISTORY  ("Previous zoom" button)
    //
    // A simple per-station stack of [xMin, xMax] ranges. Each user zoom/pan
    // pushes the range we are LEAVING; the button pops one level and re-applies
    // it. The applied range goes through Plotly.relayout exactly like a normal
    // user zoom, so the markers recount and the meta timeline refresh through
    // their OWN existing listeners — we never touch those.
    //
    // __zoomApplying guards the single relayout WE trigger, so it is not pushed
    // back onto the stack (otherwise we could never move backwards).
    // =================================================================

    var __zoomStack    = {};   // { idStation: [[min,max], ...] }  (back / undo)
    var __zoomRedo     = {};   // { idStation: [[min,max], ...] }  (forward / redo)
    var __zoomApplying = {};   // { idStation: bool }
    var __zoomCurrent  = {};   // { idStation: [min,max] } currently displayed

    // Push the range we are leaving, before recording the new one.
    function zoomHistoryOnRelayout(idStation, newMin, newMax) {
        if (__zoomApplying[idStation]) { return; } // our own relayout: skip push
        if (!__zoomStack[idStation]) { __zoomStack[idStation] = []; }
        var prev = __zoomCurrent[idStation];
        if (prev && (prev[0] !== newMin || prev[1] !== newMax)) {
            __zoomStack[idStation].push(prev);
            if (__zoomStack[idStation].length > 50) { __zoomStack[idStation].shift(); }
            // A brand-new user zoom invalidates any forward history.
            __zoomRedo[idStation] = [];
        }
        __zoomCurrent[idStation] = [newMin, newMax];
        refreshZoomBackBtn(idStation);
    }

    // Initialise the current range (called once after the graph loads).
    function zoomHistoryInit(idStation, xMin, xMax) {
        __zoomStack[idStation]   = [];
        __zoomRedo[idStation]    = [];
        __zoomCurrent[idStation] = [xMin, xMax];
        refreshZoomBackBtn(idStation);
    }

    // Enable/disable (grey) BOTH history buttons depending on stack contents.
    // (Name kept for backward compatibility with existing callers.)
    function refreshZoomBackBtn(idStation) {
        var back = document.getElementById('zoom_back_' + idStation);
        if (back) {
            var hasBack = __zoomStack[idStation] && __zoomStack[idStation].length > 0;
            back.disabled = !hasBack;
            back.style.opacity = hasBack ? '1' : '0.4';
            back.style.cursor  = hasBack ? 'pointer' : 'not-allowed';
        }
        var fwd = document.getElementById('zoom_fwd_' + idStation);
        if (fwd) {
            var hasFwd = __zoomRedo[idStation] && __zoomRedo[idStation].length > 0;
            fwd.disabled = !hasFwd;
            fwd.style.opacity = hasFwd ? '1' : '0.4';
            fwd.style.cursor  = hasFwd ? 'pointer' : 'not-allowed';
        }
    }

    // Apply a stored range as a normal relayout, guarded so it is not
    // recorded as a fresh user zoom. Shared by zoomBack / zoomForward.
    function applyZoomRange(idStation, target) {
        var gd = document.getElementById('plot_' + idStation);
        if (!gd) { return; }
        __zoomCurrent[idStation] = target;
        __zoomApplying[idStation] = true;
        Plotly.relayout(gd, {
            'xaxis.range[0]' : target[0],
            'xaxis.range[1]' : target[1],
            'xaxis.autorange': false
        }).then(function() {
            __zoomApplying[idStation] = false;
            refreshZoomBackBtn(idStation);
        });
    }

    // Back handler: push the view we are leaving onto the redo stack,
    // then pop one level off the back stack and apply it.
    function zoomBack(idStation) {
        var stack = __zoomStack[idStation];
        if (!stack || stack.length === 0) { return; }
        if (!__zoomRedo[idStation]) { __zoomRedo[idStation] = []; }
        var leaving = __zoomCurrent[idStation];
        if (leaving) {
            __zoomRedo[idStation].push(leaving);
            if (__zoomRedo[idStation].length > 50) { __zoomRedo[idStation].shift(); }
        }
        applyZoomRange(idStation, stack.pop());
    }

    // Forward handler: push the view we are leaving back onto the back
    // stack, then pop one level off the redo stack and apply it.
    function zoomForward(idStation) {
        var redo = __zoomRedo[idStation];
        if (!redo || redo.length === 0) { return; }
        if (!__zoomStack[idStation]) { __zoomStack[idStation] = []; }
        var leaving = __zoomCurrent[idStation];
        if (leaving) {
            __zoomStack[idStation].push(leaving);
            if (__zoomStack[idStation].length > 50) { __zoomStack[idStation].shift(); }
        }
        applyZoomRange(idStation, redo.pop());
    }


    /**
     * Attach a plotly_relayout listener that recounts the points visible in
     * the current zoom window and refreshes the markers checkbox accordingly.
     * Zoom into a dense series until <= MARKERS_THRESHOLD → checkbox activates.
     * The base trace point count is the upper bound (full view).
     */
    function attachMarkersRecount(idStation, fullCount)
    {
        var gd = document.getElementById('plot_' + idStation);
        if (!gd) { return; }

        // Avoid stacking duplicate listeners across reloads.
        if (typeof gd.removeAllListeners === 'function') {
            gd.removeAllListeners('plotly_relayout');
        }

        gd.on('plotly_relayout', function(eventData) {
            // Determine the visible x-range.
            var xMin, xMax;
            if (eventData && typeof eventData['xaxis.range[0]'] !== 'undefined') {
                xMin = eventData['xaxis.range[0]'];
                xMax = eventData['xaxis.range[1]'];
            } else if (eventData && typeof eventData['xaxis.range'] !== 'undefined') {
                xMin = eventData['xaxis.range'][0];
                xMax = eventData['xaxis.range'][1];
            } else if (eventData && eventData['xaxis.autorange'] === true) {
                // Back to full view → use the full series count directly.
                // Also record the full range in the zoom history.
                var fx = gd._fullLayout && gd._fullLayout.xaxis;
                if (fx && fx.range) { zoomHistoryOnRelayout(idStation, fx.range[0], fx.range[1]); }
                updateMarkersState(idStation, fullCount, true);
                return;
            } else {
                return; // not an x-range change (shapes, yaxis, etc.)
            }

            // Zoom history: record the range we are leaving (no-op for our own
            // button-triggered relayout, guarded inside the function).
            zoomHistoryOnRelayout(idStation, xMin, xMax);

            var n = countVisiblePoints(gd, xMin, xMax);
            updateMarkersState(idStation, n, true);
        });
    }


    // =================================================================
    // POINT DELETION  (right-click to remove, Ctrl+Z / button to undo)
    //
    // When markers are shown (<= MARKERS_THRESHOLD), right-clicking a point
    // removes it from the base trace. Removed points are kept on an undo
    // stack (with their original index) so they can be restored exactly.
    // A discreet panel appears under the chart: "N point(s) retiré(s)" with
    // Undo + Save buttons. Save creates a 'suppression' correction (same
    // lifecycle as every other correction) spanning [first removed, last
    // removed], holding the source series minus the removed points.
    // =================================================================

    // Per-station state: { stack: [{x, y, idx, customdata}], wired: bool }
    var __delState = {};

    function setupPointDeletion(idStation)
    {
        if (!__delState[idStation]) { __delState[idStation] = { stack: [], wired: false }; }
        if (__delState[idStation].wired) { return; }

        var gd = document.getElementById('plot_' + idStation);
        if (!gd) { return; }

        // Right-click → remove nearest point. Plotly has no native right-click
        // event for scattergl, so we hit-test against the data ourselves.
        //
        // We attach to `gd` (the stable plot container) in the CAPTURE phase so
        // our handlers run before Plotly's, and survive the dragLayer being
        // recreated on every redraw. Plotly reacts to the right button on
        // mousedown/mouseup/dblclick (pan/zoom artefacts) — we swallow those
        // for button 2; only contextmenu performs the deletion.
        function swallowRightButton(ev) {
            if (ev.button === 2 || ev.buttons === 2) {
                ev.preventDefault();
                ev.stopPropagation();
                if (typeof ev.stopImmediatePropagation === 'function') {
                    ev.stopImmediatePropagation();
                }
            }
        }
        ['mousedown', 'mouseup', 'dblclick', 'pointerdown', 'pointerup'].forEach(function(evt) {
            gd.addEventListener(evt, swallowRightButton, true);
        });

        gd.addEventListener('contextmenu', function(ev) {
            ev.preventDefault();
            ev.stopPropagation();
            // Only when markers are actually displayed.
            if (!__markersState[idStation] || !__markersState[idStation].enabled) { return; }
            if (removeNearestPoint(idStation, ev.clientX, ev.clientY)) {
                refreshDeletePanel(idStation);
            }
        }, true);

        // Ctrl+Z (only while this plot is visible and has removed points).
        document.addEventListener('keydown', function(ev) {
            if ((ev.ctrlKey || ev.metaKey) && (ev.key === 'z' || ev.key === 'Z')) {
                var g = document.getElementById('plot_' + idStation);
                if (g && g.offsetParent !== null &&
                    __delState[idStation] && __delState[idStation].stack.length > 0) {
                    ev.preventDefault();
                    undoDeletePoint(idStation);
                }
            }
        });

        __delState[idStation].wired = true;
    }

    // Remove the base-trace point nearest to the cursor (within 15px).
    function removeNearestPoint(idStation, clientX, clientY)
    {
        var gd = document.getElementById('plot_' + idStation);
        if (!gd || !gd.data || !gd.data[0]) { return false; }
        var trace = gd.data[0], fl = gd._fullLayout;
        if (!fl || !fl.xaxis || !fl.yaxis) { return false; }

        var bb = gd.getBoundingClientRect();
        var px = clientX - bb.left, py = clientY - bb.top;
        var xAx = fl.xaxis, yAx = fl.yaxis;
        var xOff = xAx._offset || 0, yOff = yAx._offset || 0;

        // Restrict to the visible x-window for performance.
        var xr = xAx.range;
        var tMin = new Date(xr[0]).getTime(), tMax = new Date(xr[1]).getTime();

        var bestIdx = -1, bestDist = Infinity, maxPix = 15;
        for (var i = 0; i < trace.x.length; i++) {
            if (trace.y[i] === null || trace.y[i] === undefined) { continue; }
            var t = new Date(trace.x[i]).getTime();
            if (t < tMin || t > tMax) { continue; }
            var dx = (xOff + xAx.d2p(trace.x[i])) - px;
            var dy = (yOff + yAx.d2p(trace.y[i])) - py;
            var d = Math.sqrt(dx * dx + dy * dy);
            if (d < bestDist) { bestDist = d; bestIdx = i; }
        }
        if (bestIdx === -1 || bestDist > maxPix) { return false; }

        var snap = { x: trace.x[bestIdx], y: trace.y[bestIdx], idx: bestIdx };
        var hasCustom = Array.isArray(trace.customdata);
        if (hasCustom) { snap.customdata = trace.customdata[bestIdx]; }

        trace.x.splice(bestIdx, 1);
        trace.y.splice(bestIdx, 1);
        if (hasCustom) { trace.customdata.splice(bestIdx, 1); }

        __delState[idStation].stack.push(snap);
        Plotly.redraw(gd);
        return true;
    }

    // Restore the most recently removed point at its original index.
    function undoDeletePoint(idStation)
    {
        var st = __delState[idStation];
        if (!st || st.stack.length === 0) { return; }
        var gd = document.getElementById('plot_' + idStation);
        if (!gd || !gd.data || !gd.data[0]) { return; }

        var snap = st.stack.pop(), trace = gd.data[0];
        var at = Math.min(snap.idx, trace.x.length);
        trace.x.splice(at, 0, snap.x);
        trace.y.splice(at, 0, snap.y);
        if (Array.isArray(trace.customdata) && typeof snap.customdata !== 'undefined') {
            trace.customdata.splice(at, 0, snap.customdata);
        }
        Plotly.redraw(gd);
        refreshDeletePanel(idStation);
    }

    // Show/update the discreet deletion panel (count + Undo + Save).
    function refreshDeletePanel(idStation)
    {
        var n = (__delState[idStation] && __delState[idStation].stack.length) || 0;
        var box  = document.getElementById('box_del_'  + idStation);
        var hint = document.getElementById('del_hint_' + idStation);
        if (!box || !hint) { return; }
        if (n > 0) {
            box.style.display = 'inline-flex';
            hint.textContent = n + ' ' + (n > 1 ? TEXT_CORRECT_DEL_COUNT_N : TEXT_CORRECT_DEL_COUNT_1);
        } else {
            box.style.display = 'none';
            hint.textContent = '';
        }
    }

    // Public (button handler): undo last deletion.
    function undoDeletePointBtn(idStation) { undoDeletePoint(idStation); }

    // Public (button handler): persist removed points as a 'suppression' correction.
    function saveDeletePoints(idStation)
    {
        var st = __delState[idStation];
        if (!st || st.stack.length === 0) { return; }

        // Span = first..last removed point, by date.
        var pts = st.stack.slice().sort(function(a, b) {
            return new Date(a.x).getTime() - new Date(b.x).getTime();
        });

        // Plotly trace.x dates are ISO 'yyyy-mm-dd hh:mm:ss'. The backend
        // (like every other correction) expects FR 'dd-mm-yyyy hh:mm:ss' and
        // runs datefr_us() on it — so convert the date part of each removed point.
        function isoDtToFr(s) {
            var p = String(s).split(' ');
            var d = p[0].split('-');                 // [yyyy, mm, dd]
            var t = p[1] || '00:00:00';
            return (d.length === 3 && d[0].length === 4)
                 ? (d[2] + '-' + d[1] + '-' + d[0] + ' ' + t)
                 : String(s);
        }
        var deleted = pts.map(function(p) { return isoDtToFr(p.x); });

        // The correction spans the WHOLE loaded window (page_window_*), not just
        // the removed points: it is a full duplicate of the displayed series
        // minus the removed points. page_window_first/last are already FR
        // 'dd-mm-yyyy hh:mm:ss' (frozen at page load), exactly what the backend
        // expects for datetime_first/end.
        var first = page_window_first;
        var last  = page_window_last;

        document.getElementById('wait_' + idStation).style.display = 'block';
        document.getElementById('plot_' + idStation).style.display = 'none';

        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'include/structure/calcul/process_chron_calcul.php', true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                var r = JSON.parse(xhr.responseText);
                contenuMsg.innerHTML     = r['msg_newCorrection'];
                contenuMsg.style.border  = '2px solid #09886d';
                contenuMsg.style.display = 'block';

                // Reset the deletion stack — the change now lives in the correction.
                __delState[idStation].stack = [];
                refreshDeletePanel(idStation);

                id_correction = r['id_correction'];
                afficheCorrection(id_correction);
            }
        };
        xhr.send(JSON.stringify({
            id_user, id_correction,
            id_station: id_station_encours,
            id_chron:   id_chron_encours,
            datetime_first: first,
            datetime_end:   last,
            page_window_first, page_window_last,
            type_correction: 'suppression',
            calcul_correction: '', axe_correction: '-',
            deleted_points: deleted,
            pastemps: 0, modecalcul: 'none', unite: 'min', gapThreshold: 10,
            to_periode_encours, id_create_chron_encours
        }));
    }


    // =================================================================
    // QUALITY-CODE META TIMELINE  (replaces the rangeslider)
    //
    // A thin SVG band below the chart, one coloured segment per data_meta
    // record (quality-code colour + obs popup on hover). Generated server-side
    // by process_graph_meta.php and kept x-synced with the chart on zoom/pan.
    // =================================================================

    var metaLastRange = {};

    // Plotly ISO 'yyyy-mm-dd hh:mm:ss' (or timestamp) → FR 'dd-mm-yyyy'.
    function isoToFr(value) {
        if (value === undefined || value === null) return '';
        if (typeof value === 'string') {
            var datePart = value.split(' ')[0];
            var parts = datePart.split('-');
            if (parts.length === 3 && parts[0].length === 4) {
                return parts[2] + '-' + parts[1] + '-' + parts[0];
            }
            return '';
        }
        if (typeof value === 'number') {
            var d = new Date(value);
            if (isNaN(d.getTime())) return '';
            var dd = String(d.getDate()).padStart(2, '0');
            var mm = String(d.getMonth() + 1).padStart(2, '0');
            var yy = d.getFullYear();
            return dd + '-' + mm + '-' + yy;
        }
        return '';
    }

    function drawMetaTimelineCore(metaDiv, chartDiv, key, idStation, typedataChron, xMinFr, xMaxFr) {
        if (!metaDiv || !chartDiv) { return; }

        metaLastRange[key] = {
            min: xMinFr || '', max: xMaxFr || '',
            station: idStation, typedata: typedataChron
        };

        var metaRect  = metaDiv.getBoundingClientRect();
        var chartRect = chartDiv.getBoundingClientRect();
        // svgWidth: meta div width, falling back to the chart width if the meta
        // div momentarily reads 0 (can happen on the very first paint).
        var svgWidth  = Math.round(metaRect.width || metaDiv.clientWidth
                                   || chartRect.width || chartDiv.clientWidth || 0);

        var padLeft = -1, padRight = -1;
        try {
            var fx = chartDiv._fullLayout && chartDiv._fullLayout.xaxis;
            if (fx && typeof fx._offset === 'number' && typeof fx._length === 'number') {
                var plotLeftScreen  = chartRect.left + fx._offset;
                var plotRightScreen = chartRect.left + fx._offset + fx._length;
                padLeft  = plotLeftScreen - metaRect.left;
                padRight = metaRect.right - plotRightScreen;
            }
        } catch (e) { /* server defaults */ }

        var payload = {
            timezone_php:  Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC',
            idStation:     idStation,
            typedataChron: typedataChron,
            plotKey:       key,
            svgWidth:      svgWidth,
            padLeft:       padLeft,
            padRight:      padRight,
            xDateMin:      xMinFr || '',
            xDateMax:      xMaxFr || ''
        };
        var xhrMeta = new XMLHttpRequest();
        xhrMeta.open('POST', 'include/structure/graph/process_graph_meta.php', true);
        xhrMeta.setRequestHeader('Content-Type', 'application/json');
        xhrMeta.onreadystatechange = function() {
            if (xhrMeta.readyState === 4 && xhrMeta.status === 200) {
                try {
                    var rMeta = JSON.parse(xhrMeta.responseText);
                    if (rMeta && rMeta['js_text']) { eval(rMeta['js_text']); }
                } catch (e) { /* keep previous on error */ }
            }
        };
        xhrMeta.send(JSON.stringify(payload));
    }

    function attachMetaTimeline(idStation, typedataChron, minDateFr, maxDateFr) {
        var metaDiv  = document.getElementById('plot_meta_' + idStation);
        var chartDiv = document.getElementById('plot_'      + idStation);
        if (!metaDiv || !chartDiv) { return; }
        metaDiv.style.display = 'block';

        function draw(xMinFr, xMaxFr) {
            drawMetaTimelineCore(metaDiv, chartDiv, idStation, idStation, typedataChron, xMinFr, xMaxFr);
        }

        // Initial draw. Plotly.newPlot (run by the eval) is asynchronous, so
        // we wait for the plot-area geometry (xaxis._length) to be ready before
        // the first draw — a short poll, capped for safety.
        var initMinFr = minDateFr || '', initMaxFr = maxDateFr || '';
        var tries = 0;
        (function waitForGeometry() {
            var fx = chartDiv._fullLayout && chartDiv._fullLayout.xaxis;
            if (fx && typeof fx._length === 'number' && fx._length > 0) {
                draw(initMinFr, initMaxFr);
            } else if (++tries < 80) {
                setTimeout(waitForGeometry, 100);
            }
        })();

        // Redraw on zoom/pan (debounced).
        var redrawTimer = null;
        chartDiv.on('plotly_relayout', function(ev) {
            var x1 = null, x2 = null;
            if (ev['xaxis.range[0]'] !== undefined && ev['xaxis.range[1]'] !== undefined) {
                x1 = ev['xaxis.range[0]']; x2 = ev['xaxis.range[1]'];
            } else if (ev['xaxis.autorange'] || ev['autosize']) {
                x1 = ''; x2 = '';
            } else {
                return;
            }
            var xMinFr = (x1 === '' ? '' : isoToFr(x1));
            var xMaxFr = (x2 === '' ? '' : isoToFr(x2));
            clearTimeout(redrawTimer);
            redrawTimer = setTimeout(function() { draw(xMinFr, xMaxFr); }, 150);
        });
    }


    // -----------------------------------------------
    // Load graph data via AJAX

    function load_graph(cle_station, type_station, typedata_array)
    {
        document.getElementById('plot_' + cle_station).style.display = 'none';
        document.getElementById('wait_' + cle_station).style.display = 'block';

        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'include/structure/calcul/process_chron_calcul_graph.php', true);
        xhr.setRequestHeader('Content-Type', 'application/json');

        xhr.onreadystatechange = function()
        {
            if (xhr.readyState === 4 && xhr.status === 200)
            {
                document.getElementById('wait_' + cle_station).style.display = 'none';
                document.getElementById('plot_' + cle_station).style.display = 'block';
                document.getElementById('button_lacune_' + cle_station).style.display = 'flex';

                var jsonResponse = JSON.parse(xhr.responseText);

                eval(jsonResponse['js_text']); // Execute server-generated Plotly graph code

                // Re-apply the user-chosen line thickness — Plotly.newPlot()
                // (executed by the eval above) resets every trace to its
                // backend default width, so we have to push the chosen
                // value back in. Function is exposed by the +/- handler.
                if (typeof window.__reapplyLineWidth === 'function') {
                    window.__reapplyLineWidth();
                }

                ecoute_lacune(cle_station, jsonResponse['text_lacunes']);

                // Markers checkbox: enable/disable based on the point count the
                // server reported for the base trace. nb_points_base is 0 for
                // non-line (bar/cumul) series, which hides the checkbox.
                var nbPtsBase = jsonResponse['nb_points_base'] || 0;
                updateMarkersState(cle_station, nbPtsBase, nbPtsBase > 0);

                // Recount visible points on zoom/pan so the checkbox activates
                // when the user zooms into a window with <= MARKERS_THRESHOLD pts.
                // The relayout listener also feeds the zoom-history stack, so it
                // is attached for every series (not only line ones). For non-line
                // series updateMarkersState simply keeps the checkbox hidden.
                attachMarkersRecount(cle_station, nbPtsBase);
                if (nbPtsBase > 0) {
                    setupPointDeletion(cle_station);
                }

                min_x = jsonResponse['min_x'];
                var min_dt = min_x.split(' ');
                var min_t  = min_dt[1] || '00:00:00';
                document.getElementById('x1Zoom').value   = min_dt[0];
                document.getElementById('x1Zoom_h').value = min_t;

                max_x = jsonResponse['max_x'];
                var max_dt = max_x.split(' ');
                var max_t  = max_dt[1] || '23:59:59';
                document.getElementById('x2Zoom').value   = max_dt[0];
                document.getElementById('x2Zoom_h').value = max_t;

                // Figer la fenêtre du chargement une seule fois. Le garde
                // sur '' empêche tout rechargement ultérieur du graphe
                // (après zoom/pan/correction) de réécrire ces bornes.
                if (page_window_first === '') {
                    page_window_first = min_dt[0] + ' ' + min_t;
                    page_window_last  = max_dt[0] + ' ' + max_t;
                }

                document.getElementById('periode_lacune_first').value = 'du ' + min_dt[0] + ' à ' + min_t;
                document.getElementById('periode_lacune_end').value   = 'au ' + max_dt[0] + ' à ' + max_t;

                // Initialise the zoom history with the current (full) range.
                // The values fed to Plotly are ISO; convert FR date + time.
                var zMin = min_dt[0].split('-').reverse().join('-') + ' ' + min_t;
                var zMax = max_dt[0].split('-').reverse().join('-') + ' ' + max_t;
                zoomHistoryInit(cle_station, zMin, zMax);

                // Quality-code meta timeline (below chart, replaces rangeslider).
                // typedataChron = the single key of typedata_array.
                // min_x / max_x are already FR-formatted (dd-mm-yyyy) by the
                // server, exactly what process_graph_meta.php expects — pass the
                // DATE part as-is (do NOT reverse it to US, that was the bug that
                // produced an empty band at load time).
                var typedataChron = Object.keys(typedata_array)[0];
                attachMetaTimeline(cle_station, typedataChron, min_dt[0], max_dt[0]);

                // If a zoom range was stashed before this reload (e.g. a
                // correction validation), re-apply it so the view stays put
                // instead of snapping back to the full range. The relayout is
                // marked as applying so it is not pushed onto the zoom history.
                if (__preserveRange[cle_station]) {
                    var pr = __preserveRange[cle_station];
                    delete __preserveRange[cle_station];
                    var gdRestore = document.getElementById('plot_' + cle_station);
                    if (gdRestore && pr && pr.length === 2) {
                        __zoomApplying[cle_station] = true;
                        Plotly.relayout(gdRestore, {
                            'xaxis.range[0]' : pr[0],
                            'xaxis.range[1]' : pr[1],
                            'xaxis.autorange': false
                        }).then(function() {
                            __zoomApplying[cle_station] = false;
                        });
                    }
                }
            }
        };

        xhr.send(JSON.stringify({
            territoireId : <?php echo $territoire_id; ?>,
            lang         : '<?php echo LANGUAGE; ?>',
            cle_station, type_station, typedata_array,
            colorTab, min_x, max_x, id_correction
        }));
    }


    // -----------------------------------------------
    // Tools dropdown + modern gaps modal (ported from graph_chron.php)
    //
    // The legacy draggable box_lacunes_info popup is replaced by the same
    // modal used on the visualisation page. ecoute_lacune() is kept as a
    // no-op so the existing call in the graph-load handler stays valid;
    // the gaps table is now opened through hpMenuAction('gaps').

    function ecoute_lacune(id_station, text_lacunes) { /* legacy popup removed */ }

    // Toggle a graph's Tools menu (closing any other open one first).
    // The list is position:absolute and anchored to .hp-menu via CSS, so no
    // JS coordinate maths is needed.
    function hpToggleMenu(cle) {
        var list = document.getElementById('hp_menu_list_' + cle);
        if (!list) { return; }
        var wasOpen = list.classList.contains('open');
        document.querySelectorAll('.hp-menu-list.open').forEach(function(l){ l.classList.remove('open'); });
        if (!wasOpen) { list.classList.add('open'); }
    }

    // Close any open Tools menu when clicking outside of it.
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.hp-menu')) {
            document.querySelectorAll('.hp-menu-list.open').forEach(function(l){ l.classList.remove('open'); });
        }
    });

    // Route a Tools menu choice to the matching action.
    function hpMenuAction(cle, action) {
        var list = document.getElementById('hp_menu_list_' + cle);
        if (list) { list.classList.remove('open'); }

        if (action === 'gaps') {
            hpOpenGaps(cle);
        }
        else if (action === 'qualif') {
            hpOpenQualif(cle);
        }
        else if (action === 'export_csv') {
            hpExportChartCsv(cle);
        }
    }

    // -----------------------------------------------
    // Generic modal helpers (gaps popup). One overlay element is reused.
    function hpGetModal() {
        var ov = document.getElementById('hp_modal_overlay');
        if (!ov) {
            ov = document.createElement('div');
            ov.id = 'hp_modal_overlay';
            ov.className = 'hp-modal-overlay';
            ov.innerHTML =
                "<div class='hp-modal'>"
              + "  <div class='hp-modal-head'>"
              + "    <div><h3 id='hp_modal_title'></h3><div class='hp-sub' id='hp_modal_sub'></div></div>"
              + "    <button class='hp-modal-close' aria-label='Close' id='hp_modal_close'>&times;</button>"
              + "  </div>"
              + "  <div class='hp-modal-toolbar' id='hp_modal_toolbar'></div>"
              + "  <div class='hp-modal-body' id='hp_modal_body'></div>"
              + "</div>";
            document.body.appendChild(ov);
            ov.addEventListener('click', function(e) {
                if (e.target === ov || e.target.id === 'hp_modal_close') {
                    ov.classList.remove('open');
                }
            });
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') { ov.classList.remove('open'); }
            });
        }
        return ov;
    }

    function hpOpenModal(title, sub) {
        var ov = hpGetModal();
        document.getElementById('hp_modal_title').textContent = title || '';
        document.getElementById('hp_modal_sub').textContent   = sub   || '';
        document.getElementById('hp_modal_toolbar').innerHTML = '';
        document.getElementById('hp_modal_body').innerHTML    = '';
        ov.classList.add('open');
        return ov;
    }

    // CSV download from an array of arrays. ; separator + UTF-8 BOM (Excel FR).
    function hpDownloadCsv(filename, headerArr, rowsArr) {
        function esc(v) {
            v = (v === null || v === undefined) ? '' : String(v);
            return '"' + v.replace(/"/g, '""') + '"';
        }
        var lines = [];
        lines.push(headerArr.map(esc).join(';'));
        rowsArr.forEach(function(r) { lines.push(r.map(esc).join(';')); });
        var csv  = '\ufeff' + lines.join('\r\n');
        var blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        var url  = URL.createObjectURL(blob);
        var a    = document.createElement('a');
        a.href = url; a.download = filename;
        document.body.appendChild(a); a.click();
        document.body.removeChild(a);
        setTimeout(function() { URL.revokeObjectURL(url); }, 1000);
    }

    function hpEsc(s) {
        if (s === null || s === undefined) { return ''; }
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    // Filename-safe slug: strip accents, keep [A-Za-z0-9].
    function hpSlug(s) {
        if (s === null || s === undefined) { return ''; }
        return String(s)
            .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
            .replace(/[^A-Za-z0-9]+/g, '');
    }

    // -----------------------------------------------
    // Data gaps popup (lacunes) + CSV / PDF export.
    // Reuses the same backend as the visualisation page (process_graph_gaps.php).
    function hpOpenGaps(cle_station) {
        var idStation     = parseInt(cle_station, 10);
        var meta          = (typeof metaLastRange !== 'undefined' && metaLastRange[cle_station])
                          ? metaLastRange[cle_station] : null;
        var typedataChron = meta ? meta.typedata : '';
        var rMin          = meta ? (meta.min || '') : '';
        var rMax          = meta ? (meta.max || '') : '';

        hpOpenModal('<?php echo addslashes(TEXT_GAPS); ?>', '');
        document.getElementById('hp_modal_body').innerHTML =
            "<div class='hp-empty'><?php echo addslashes(TEXT_LOADING); ?></div>";

        var reqParams = {
            idStation:     idStation,
            typedataChron: typedataChron,
            xDateMin:      rMin,
            xDateMax:      rMax
        };

        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'include/structure/graph/process_graph_gaps.php', true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                var resp;
                try { resp = JSON.parse(xhr.responseText); }
                catch (e) {
                    document.getElementById('hp_modal_body').innerHTML =
                        "<div class='hp-empty'>Error</div>";
                    return;
                }
                resp._req = reqParams;
                hpRenderGaps(resp);
            }
        };
        xhr.send(JSON.stringify(reqParams));
    }

    function hpRenderGaps(resp) {
        document.getElementById('hp_modal_sub').textContent =
            (resp.station || '') + (resp.chronique ? '  \u00b7  ' + resp.chronique : '');

        var rows = resp.rows || [];

        var H = {
            qc:    '<?php echo addslashes(TEXT_GRAPH_HOVER_QUALCODE); ?>',
            start: '<?php echo addslashes(TEXT_GRAPH_META_START); ?>',
            end:   '<?php echo addslashes(TEXT_GRAPH_META_END); ?>',
            nb:    '<?php echo addslashes(TEXT_GRAPH_META_NBPTS); ?>',
            corr:  '<?php echo addslashes(TEXT_GRAPH_HOVER_CORRECTION); ?>',
            comm:  '<?php echo addslashes(TEXT_GRAPH_HOVER_CORRECTION_OBS); ?>'
        };

        var tb = document.getElementById('hp_modal_toolbar');
        tb.innerHTML = "<button id='hp_gaps_csv'><span>&#11015;</span> CSV</button>"
                     + "<button id='hp_gaps_pdf'><span>&#128196;</span> PDF</button>";
        document.getElementById('hp_gaps_csv').onclick = function() {
            var header = [H.qc, H.start, H.end, H.nb, H.corr, H.comm];
            var data = rows.map(function(r) {
                var qc = (r.qual_init || '') + (r.qual_nom ? ' - ' + r.qual_nom : '');
                return [qc, r.date_first, r.date_end, r.nb_points, r.obs, r.obs_user];
            });
            var fname = hpSlug(resp.station_code) + '_' + hpSlug(resp.station_nom)
                      + '_' + hpSlug(resp.chron_init) + '_<?php echo addslashes(TEXT_FILE_GAPS); ?>.csv';
            hpDownloadCsv(fname, header, data);
        };
        document.getElementById('hp_gaps_pdf').onclick = function() {
            hpGapsPdf(resp._req);
        };

        var body = document.getElementById('hp_modal_body');
        if (!rows.length) {
            body.innerHTML = "<div class='hp-empty'>\u2014</div>";
            return;
        }

        var html = "<table class='hp-table'><thead><tr>"
                 + "<th>" + H.qc + "</th><th>" + H.start + "</th><th>" + H.end
                 + "</th><th>" + H.nb + "</th><th>" + H.corr + "</th><th>" + H.comm
                 + "</th></tr></thead><tbody>";
        rows.forEach(function(r) {
            var sw = r.color
                ? "<span class='hp-qc-swatch' style='background:" + r.color + "'></span>"
                : "";
            var qc = (r.qual_init || '') + (r.qual_nom ? ' - ' + r.qual_nom : '');
            html += "<tr>"
                 + "<td>" + sw + hpEsc(qc) + "</td>"
                 + "<td>" + hpEsc(r.date_first) + "</td>"
                 + "<td>" + hpEsc(r.date_end) + "</td>"
                 + "<td>" + r.nb_points + "</td>"
                 + "<td>" + hpEsc(r.obs) + "</td>"
                 + "<td>" + hpEsc(r.obs_user) + "</td>"
                 + "</tr>";
        });
        html += "</tbody></table>";
        body.innerHTML = html;
    }

    function hpGapsPdf(reqParams) {
        if (!reqParams) { return; }
        var btn = document.getElementById('hp_gaps_pdf');
        if (btn) { btn.disabled = true; btn.style.opacity = '0.5'; }

        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'include/structure/graph/process_graph_gaps_pdf.php', true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                if (btn) { btn.disabled = false; btn.style.opacity = '1'; }
                var resp;
                try { resp = JSON.parse(xhr.responseText); } catch (e) { return; }
                if (resp && resp.status === 'success' && resp.fileName) {
                    var a = document.createElement('a');
                    a.href = '<?php echo DIR_WS_PDF; ?>' + resp.fileName;
                    a.target = '_blank';
                    a.rel = 'noopener';
                    document.body.appendChild(a); a.click();
                    document.body.removeChild(a);
                }
            }
        };
        xhr.send(JSON.stringify(reqParams));
    }

    // -----------------------------------------------
    // Data qualification popup (data_meta blocks) + CSV / PDF export.
    // Reuses the same backend as the visualisation page.
    function hpOpenQualif(cle_station) {
        var idStation     = parseInt(cle_station, 10);
        var meta          = (typeof metaLastRange !== 'undefined' && metaLastRange[cle_station])
                          ? metaLastRange[cle_station] : null;
        var typedataChron = meta ? meta.typedata : '';
        var rMin          = meta ? (meta.min || '') : '';
        var rMax          = meta ? (meta.max || '') : '';

        hpOpenModal('<?php echo addslashes(TEXT_DATA_QUALIF); ?>', '');
        document.getElementById('hp_modal_body').innerHTML =
            "<div class='hp-empty'><?php echo addslashes(TEXT_LOADING); ?></div>";

        var reqParams = {
            idStation:     idStation,
            typedataChron: typedataChron,
            xDateMin:      rMin,
            xDateMax:      rMax
        };

        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'include/structure/graph/process_graph_qualif.php', true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                var resp;
                try { resp = JSON.parse(xhr.responseText); }
                catch (e) {
                    document.getElementById('hp_modal_body').innerHTML =
                        "<div class='hp-empty'>Error</div>";
                    return;
                }
                resp._req = reqParams;
                hpRenderQualif(resp);
            }
        };
        xhr.send(JSON.stringify(reqParams));
    }

    function hpRenderQualif(resp) {
        document.getElementById('hp_modal_sub').textContent =
            (resp.station || '') + (resp.chronique ? '  \u00b7  ' + resp.chronique : '');

        var rows = resp.rows || [];

        var H = {
            qc:    '<?php echo addslashes(TEXT_GRAPH_HOVER_QUALCODE); ?>',
            start: '<?php echo addslashes(TEXT_GRAPH_META_START); ?>',
            end:   '<?php echo addslashes(TEXT_GRAPH_META_END); ?>',
            nb:    '<?php echo addslashes(TEXT_GRAPH_META_NBPTS); ?>',
            corr:  '<?php echo addslashes(TEXT_GRAPH_HOVER_CORRECTION); ?>',
            comm:  '<?php echo addslashes(TEXT_GRAPH_HOVER_CORRECTION_OBS); ?>'
        };

        var tb = document.getElementById('hp_modal_toolbar');
        tb.innerHTML = "<button id='hp_qualif_csv'><span>&#11015;</span> CSV</button>"
                     + "<button id='hp_qualif_pdf'><span>&#128196;</span> PDF</button>";
        document.getElementById('hp_qualif_csv').onclick = function() {
            var header = [H.qc, H.start, H.end, H.nb, H.corr, H.comm];
            var data = rows.map(function(r) {
                var qc = (r.qual_init || '') + (r.qual_nom ? ' - ' + r.qual_nom : '');
                return [qc, r.date_first, r.date_end, r.nb_points, r.obs, r.obs_user];
            });
            var fname = hpSlug(resp.station_code) + '_' + hpSlug(resp.station_nom)
                      + '_' + hpSlug(resp.chron_init) + '_<?php echo addslashes(TEXT_FILE_QUALIF); ?>.csv';
            hpDownloadCsv(fname, header, data);
        };
        document.getElementById('hp_qualif_pdf').onclick = function() {
            hpQualifPdf(resp._req);
        };

        var body = document.getElementById('hp_modal_body');
        if (!rows.length) {
            body.innerHTML = "<div class='hp-empty'>\u2014</div>";
            return;
        }

        var html = "<table class='hp-table'><thead><tr>"
                 + "<th>" + H.qc + "</th><th>" + H.start + "</th><th>" + H.end
                 + "</th><th>" + H.nb + "</th><th>" + H.corr + "</th><th>" + H.comm
                 + "</th></tr></thead><tbody>";
        rows.forEach(function(r) {
            var sw = r.color
                ? "<span class='hp-qc-swatch' style='background:" + r.color + "'></span>"
                : "";
            var qc = (r.qual_init || '') + (r.qual_nom ? ' - ' + r.qual_nom : '');
            html += "<tr>"
                 + "<td>" + sw + hpEsc(qc) + "</td>"
                 + "<td>" + hpEsc(r.date_first) + "</td>"
                 + "<td>" + hpEsc(r.date_end) + "</td>"
                 + "<td>" + r.nb_points + "</td>"
                 + "<td>" + hpEsc(r.obs) + "</td>"
                 + "<td>" + hpEsc(r.obs_user) + "</td>"
                 + "</tr>";
        });
        html += "</tbody></table>";
        body.innerHTML = html;
    }

    function hpQualifPdf(reqParams) {
        if (!reqParams) { return; }
        var btn = document.getElementById('hp_qualif_pdf');
        if (btn) { btn.disabled = true; btn.style.opacity = '0.5'; }

        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'include/structure/graph/process_graph_qualif_pdf.php', true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                if (btn) { btn.disabled = false; btn.style.opacity = '1'; }
                var resp;
                try { resp = JSON.parse(xhr.responseText); } catch (e) { return; }
                if (resp && resp.status === 'success' && resp.fileName) {
                    var a = document.createElement('a');
                    a.href = '<?php echo DIR_WS_PDF; ?>' + resp.fileName;
                    a.target = '_blank';
                    a.rel = 'noopener';
                    document.body.appendChild(a); a.click();
                    document.body.removeChild(a);
                }
            }
        };
        xhr.send(JSON.stringify(reqParams));
    }

    // -----------------------------------------------
    // Export the chart's VISIBLE data (current x-range, visible traces) to CSV.
    // Same logic that used to live in the Plotly modebar "Export Data CSV"
    // button (now removed from the modebar in favour of the Tools menu).
    function hpExportChartCsv(cle_station) {
        var gd = document.getElementById('plot_' + cle_station);
        if (!gd || !gd.data) { return; }

        var visibleTraces = gd.data.filter(function(trace) {
            return trace.visible !== 'legendonly' && trace.visible !== false;
        });
        if (visibleTraces.length === 0) { return; }

        var data = visibleTraces;
        var sep  = ";";
        var csvContent = "";

        var allUniqueX = new Set();
        data.forEach(function(trace) { (trace.x || []).forEach(function(xVal) { allUniqueX.add(xVal); }); });
        var masterX = Array.from(allUniqueX).sort();

        var xRange = gd.layout && gd.layout.xaxis ? gd.layout.xaxis.range : null;
        if (xRange && xRange.length === 2) {
            var minX = xRange[0], maxX = xRange[1];
            masterX = masterX.filter(function(xVal) {
                var numericX    = (new Date(xVal).getTime()    || xVal);
                var numericMinX = (new Date(minX).getTime()    || minX);
                var numericMaxX = (new Date(maxX).getTime()    || maxX);
                return numericX >= numericMinX && numericX <= numericMaxX;
            });
        }
        if (masterX.length === 0) { return; }

        var lookupMaps = data.map(function(trace) {
            var map = new Map();
            for (var i = 0; i < trace.x.length; i++) {
                map.set(trace.x[i], { y: trace.y[i], text: (trace.text && trace.text[i] !== undefined) ? trace.text[i] : "" });
            }
            return map;
        });

        var header = ["X"];
        data.forEach(function(trace, index) {
            var seriesName = trace.name || ("Trace " + (index + 1));
            header.push("Y (" + seriesName + ")");
            header.push("CodeQual (" + seriesName + ")");
        });
        csvContent += header.join(sep) + "\r\n";

        masterX.forEach(function(xVal) {
            var row = [String(xVal)];
            lookupMaps.forEach(function(map) {
                var point = map.get(xVal);
                if (point) { row.push(String(point.y)); row.push(point.text); }
                else       { row.push(""); row.push(""); }
            });
            csvContent += row.join(sep) + "\r\n";
        });

        var blob = new Blob(["\uFEFF" + csvContent], { type: 'text/csv;charset=utf-8;' });
        var url  = URL.createObjectURL(blob);
        var link = document.createElement("a");
        link.setAttribute("href", url);
        link.setAttribute("download", "HP-Data.csv");
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        setTimeout(function() { URL.revokeObjectURL(url); }, 1000);
    }

    afficheCorrection(id_correction); // Initial load


    // -----------------------------------------------
    // Zoom axis lock controls

    function zoomCTRL(idStation)
    {
        var checkZoomX   = document.getElementById('check_zoom_x_' + idStation).checked;
        var checkZoomY   = document.getElementById('check_zoom_y_' + idStation).checked;

        Plotly.relayout('plot_' + idStation, {
            'xaxis.fixedrange': !checkZoomX,
            'yaxis.fixedrange': !checkZoomY
        });
    }

    function zoom_graph(id_station, code_station, nom_station, type_data)
    {
        document.getElementById('box_graph').style.display  = 'block';
        document.getElementById('titre_graph').innerHTML    = code_station + ' - ' + nom_station + ' - ' + type_data;
        idPlotZoom = id_station;

        var plotName   = 'plot_' + id_station;
        Plotly.newPlot('cadre_limit', window[plotName].data, window[plotName].layout, config);
        addLogScaleButton('cadre_limit', 'log-button_gd_1', 'yaxis');

        var big = document.getElementById('cadre_limit');

        // Same interaction defaults as inline graphs.
        try { Plotly.relayout(big, { dragmode: 'zoom', selectdirection: 'h' }); } catch (e) {}

        // Wire selection on the enlarged graph, then paint the current state.
        // renderBandFromInputs() shows the band only if __hasUserSelection is
        // true, so the default full-period prefill never paints the graph green.
        attachSelectionTo(big);
        renderBandFromInputs();

        // Field-visit (yellow squares) click/hover, same as the inline graph.
        attachFieldVisitTo(big, id_station);
    }

    // Attach field-visit hover/click to a graph div (used by the enlarged graph
    // on the correction page). Mirrors the inline-graph behaviour: pointer
    // cursor + open the field sheet, with a vertical guard so only a click ON
    // the yellow square reacts (hovermode 'x unified' matches the whole column).
    function attachFieldVisitTo(gd, stationId)
    {
        if (!gd) { return; }
        if (typeof gd.removeAllListeners === 'function') {
            gd.removeAllListeners('plotly_hover');
            gd.removeAllListeners('plotly_unhover');
            gd.removeAllListeners('plotly_click');
        }
        var dragLayer_fv = gd.querySelector('.nsewdrag') || gd;

        function nearSquareY(p, orig) {
            try {
                var fl  = gd._fullLayout;
                var yAx = p.yaxis || (fl && fl.yaxis);
                if (orig && yAx && typeof yAx.d2p === 'function') {
                    var bb = gd.getBoundingClientRect();
                    var cursorY = orig.clientY - bb.top;
                    var markerY = (yAx._offset || 0) + yAx.d2p(p.y);
                    return Math.abs(cursorY - markerY) <= 12;
                }
            } catch (e) {}
            return true;
        }

        gd.on('plotly_hover', function(eventData) {
            if (!eventData || !eventData.points || !eventData.points.length) { return; }
            var orig = eventData.event;
            for (var i = 0; i < eventData.points.length; i++) {
                var p = eventData.points[i];
                if (!p.data || p.data.meta !== 'fieldVisit') { continue; }
                if (!nearSquareY(p, orig)) { continue; }
                dragLayer_fv.style.setProperty('cursor', 'pointer', 'important');
                return;
            }
            dragLayer_fv.style.setProperty('cursor', '', '');
        });

        gd.on('plotly_unhover', function() {
            dragLayer_fv.style.setProperty('cursor', '', '');
        });

        gd.on('plotly_click', function(eventData) {
            if (!eventData || !eventData.points || !eventData.points.length) { return; }
            var pt = null;
            for (var i = 0; i < eventData.points.length; i++) {
                if (eventData.points[i].data && eventData.points[i].data.meta === 'fieldVisit') {
                    pt = eventData.points[i]; break;
                }
            }
            if (!pt) { return; }
            var cd = pt.customdata;
            if (!cd || cd.length < 4) { return; }
            var idRa = parseInt(cd[2], 10);
            var td   = parseInt(cd[3], 10);
            if (!idRa || idRa <= 0 || !td || td <= 0) { return; }

            var orig = eventData.event;
            if (!nearSquareY(pt, orig)) { return; }

            if (orig && orig.preventDefault)  { orig.preventDefault(); }
            if (orig && orig.stopPropagation) { orig.stopPropagation(); }

            var __ra_url = 'list_ra.php?st=' + parseInt(stationId, 10) + '&ra=' + idRa + '&td=' + td;
            setTimeout(function() {
                var __ra_a    = document.createElement('a');
                __ra_a.href   = __ra_url;
                __ra_a.target = '_blank';
                __ra_a.rel    = 'noopener';
                document.body.appendChild(__ra_a);
                __ra_a.click();
                document.body.removeChild(__ra_a);
            }, 0);
        });
    }

    function addLogScaleButton(plotId, logButtonId, axe)
    {
        const button         = document.getElementById(logButtonId);
        const graphContainer = document.getElementById(plotId);
        let logScaleEnabled  = false;

        button.addEventListener('click', function()
        {
            const newType   = logScaleEnabled ? 'linear' : 'log';
            Plotly.relayout(plotId, { [axe + '.type']: newType });
            logScaleEnabled = !logScaleEnabled;
        });
    }

    function updateDecimals(plotId, axe, type)
    {
        if (type == '+' && decimalPlaces < 6) { decimalPlaces++; }
        if (type == '-' && decimalPlaces > 0) { decimalPlaces--; }
        Plotly.relayout(plotId, { [axe + '.tickformat']: '.' + decimalPlaces + 'f' });
    }


    // =================================================================
    // SELECTION STATE — single source of truth
    //
    // The correction period lives ONLY in the input fields
    // (x1Zoom / x1Zoom_h / x2Zoom / x2Zoom_h) plus the boolean
    // __hasUserSelection. Every graph (inline plot_* and the enlarged
    // cadre_limit) is just a VIEW of that state.
    //
    // Rules:
    //  - A selection (on ANY graph) writes the input fields + sets the flag,
    //    then calls renderBandFromInputs().
    //  - renderBandFromInputs() is the ONLY function that paints graphs: it
    //    reads the inputs and applies/removes the band on EVERY graph present.
    //  - No graph ever paints another graph directly. This removes the
    //    cross-talk that caused inconsistent fullscreen/inline behaviour.
    // =================================================================

    // Build a 'YYYY-MM-DD HH:MM:SS' string from a Plotly axis value
    // (string or millisecond number, UTC to match the date axis).
    function __plotlyValToStr(v)
    {
        if (typeof v === 'string') { return v.replace('T', ' ').split('.')[0]; }
        var d = new Date(v);
        var p = function(n){ return (n < 10 ? '0' : '') + n; };
        return d.getUTCFullYear() + '-' + p(d.getUTCMonth()+1) + '-' + p(d.getUTCDate())
             + ' ' + p(d.getUTCHours()) + ':' + p(d.getUTCMinutes()) + ':' + p(d.getUTCSeconds());
    }

    // List every live graph div (inline graphs + the enlarged one if shown).
    function __allGraphDivs()
    {
        var list = [];
        document.querySelectorAll('.graph').forEach(function(d){
            if (d && d.data) { list.push(d); }
        });
        var big = document.getElementById('cadre_limit');
        if (big && big.data) { list.push(big); }
        return list;
    }

    // WRITE STATE: store a selection in the input fields and raise the flag.
    // bounds are 'YYYY-MM-DD HH:MM:SS' strings.
    function setSelectionState(sx1, sx2)
    {
        if (sx1 > sx2) { var tmp = sx1; sx1 = sx2; sx2 = tmp; }

        var s1 = sx1.split(' ');
        var s1_date = s1[0].split('-').reverse().join('-');
        var s1_time = (s1[1] || '00:00:00');
        var s2 = sx2.split(' ');
        var s2_date = s2[0].split('-').reverse().join('-');
        var s2_time = (s2[1] || '23:59:59');

        var e1 = document.getElementById('x1Zoom');   if (e1)  { e1.value  = s1_date; }
        var e1h = document.getElementById('x1Zoom_h'); if (e1h) { e1h.value = s1_time; }
        var e2 = document.getElementById('x2Zoom');   if (e2)  { e2.value  = s2_date; }
        var e2h = document.getElementById('x2Zoom_h'); if (e2h) { e2h.value = s2_time; }
        if (e1) { e1.dispatchEvent(new Event('change')); }
        if (e2) { e2.dispatchEvent(new Event('change')); }

        if (document.getElementById('periode_lacune_first')) {
            document.getElementById('periode_lacune_first').value = 'du '+s1_date+' à '+s1_time;
        }
        if (document.getElementById('periode_lacune_end')) {
            document.getElementById('periode_lacune_end').value = 'au '+s2_date+' à '+s2_time;
        }

        __hasUserSelection = true;
        renderBandFromInputs();
    }

    // CLEAR STATE: no active selection.
    function clearSelectionState()
    {
        __hasUserSelection = false;
        renderBandFromInputs();
    }

    // RENDER: the ONLY painter. Reads the inputs + flag and applies the band to
    // every graph, or removes it everywhere when there is no active selection.
    function renderBandFromInputs()
    {
        var divs = __allGraphDivs();

        if (!__hasUserSelection) {
            divs.forEach(function(div){
                if (div.layout && Array.isArray(div.layout.shapes)) {
                    var kept = div.layout.shapes.filter(function(s){ return s.name !== 'hp_selection_band'; });
                    if (kept.length !== div.layout.shapes.length) {
                        try { Plotly.relayout(div, { shapes: kept }); } catch (e) {}
                    }
                }
            });
            return;
        }

        var x1  = document.getElementById('x1Zoom').value;
        var x1h = document.getElementById('x1Zoom_h').value || '00:00:00';
        var x2  = document.getElementById('x2Zoom').value;
        var x2h = document.getElementById('x2Zoom_h').value || '23:59:59';
        if (!x1 || !x2) { return; }

        var sx1 = x1.split('-').reverse().join('-') + ' ' + x1h;
        var sx2 = x2.split('-').reverse().join('-') + ' ' + x2h;
        if (sx1 > sx2) { var tmp = sx1; sx1 = sx2; sx2 = tmp; }

        var band = {
            name: 'hp_selection_band',
            type: 'rect', xref: 'x', yref: 'paper',
            x0: sx1, x1: sx2, y0: 0, y1: 1,
            fillcolor: 'rgba(29,158,117,0.15)',
            line: { width: 0 },
            layer: 'below'
        };

        divs.forEach(function(div){
            var shapes = (div.layout && div.layout.shapes) ? div.layout.shapes.slice() : [];
            shapes = shapes.filter(function(s){ return s.name !== 'hp_selection_band'; });
            shapes.push(band);
            try { Plotly.relayout(div, { shapes: shapes, selections: [] }); } catch (e) {}
        });
    }

    // Attach range-selection handlers to a graph div. Used by BOTH the enlarged
    // graph and (via the server-generated code) the inline graphs, so all
    // selections funnel through setSelectionState -> renderBandFromInputs.
    function attachSelectionTo(gd)
    {
        if (!gd) { return; }

        if (typeof gd.removeAllListeners === 'function') {
            gd.removeAllListeners('plotly_selecting');
            gd.removeAllListeners('plotly_selected');
            gd.removeAllListeners('plotly_doubleclick');
        }

        function extractBounds(eventData) {
            if (!eventData) { return null; }
            if (eventData.range && eventData.range.x) { return eventData.range.x; }
            if (Array.isArray(eventData.selections) && eventData.selections.length
                && typeof eventData.selections[0].x0 !== 'undefined') {
                return [eventData.selections[0].x0, eventData.selections[0].x1];
            }
            return null;
        }

        gd.on('plotly_selecting', function(ev){
            var xr = extractBounds(ev);
            if (xr) { setSelectionState(__plotlyValToStr(xr[0]), __plotlyValToStr(xr[1])); }
        });
        gd.on('plotly_selected', function(ev){
            var xr = extractBounds(ev);
            if (xr) { setSelectionState(__plotlyValToStr(xr[0]), __plotlyValToStr(xr[1])); }
        });

        // Double-click resets the view: clear the active selection everywhere
        // (same single-source-of-truth path as the inline graph).
        gd.on('plotly_doubleclick', function(){
            if (typeof clearSelectionState === 'function') { clearSelectionState(); }
        });
    }


    // -----------------------------------------------
    // Apply the correction period (fields -> green band on the graph)
    // Draws/updates the persistent selection band from the typed dates WITHOUT
    // zooming. Used by the period button and after manual date edits, so the
    // highlighted zone always matches the period that will be corrected.

    function applyPeriodBand()
    {
        var x1  = document.getElementById('x1Zoom').value;
        var x1h = document.getElementById('x1Zoom_h').value || '00:00:00';
        var x2  = document.getElementById('x2Zoom').value;
        var x2h = document.getElementById('x2Zoom_h').value || '23:59:59';

        if (!isValidDatesInput(x1, x2, x1h, x2h)) {
            contenuMsg.innerHTML    = (typeof LANG_CORRECT !== 'undefined' && LANG_CORRECT.errBadDates)
                                      ? LANG_CORRECT.errBadDates : 'Dates invalides.';
            contenuMsg.style.border  = '2px solid #930000';
            contenuMsg.style.display = 'block';
            return;
        }

        var sx1 = x1.split('-').reverse().join('-') + ' ' + x1h;
        var sx2 = x2.split('-').reverse().join('-') + ' ' + x2h;
        // The user explicitly applied a typed period -> treat as a selection.
        setSelectionState(sx1, sx2);
    }


    function isValidDatesInput(date1Input, date2Input, heure1Input, heure2Input)
    {
        if (isValidDate(date1Input) && isValidDate(date2Input))
        {
            if (isValidTime(heure1Input) && isValidTime(heure2Input))
            {
                const d1 = parseDate(date1Input);
                const [h1, m1, s1] = parseTime(heure1Input);
                d1.setHours(h1, m1, s1);

                const d2 = parseDate(date2Input);
                const [h2, m2, s2] = parseTime(heure2Input);
                d2.setHours(h2, m2, s2);

                if (d1 < d2) { return true; }

                contenuMsg.innerText    = LANG_CORRECT.errDateOrder;
                contenuMsg.style.display = 'block';
                return false;
            }
            contenuMsg.innerText    = LANG_CORRECT.errTimeFmt;
            contenuMsg.style.display = 'block';
            return false;
        }
        contenuMsg.innerText    = LANG_CORRECT.errDateFmt;
        contenuMsg.style.display = 'block';
        return false;
    }

    function isValidDate(dateString)
    {
        const dateRegex = /^(0[1-9]|[12][0-9]|3[01])-(0[1-9]|1[0-2])-(\d{4})$/;
        if (!dateRegex.test(dateString)) { return false; }
        const [day, month, year] = dateString.split("-").map(Number);
        const date = new Date(year, month - 1, day);
        return date.getFullYear() === year && date.getMonth() === month - 1 && date.getDate() === day;
    }

    function isValidTime(timeString)
    {
        const timeRegex = /^([01]\d|2[0-3]):([0-5]\d)(:[0-5]\d)?$/;
        if (!timeRegex.test(timeString)) { return false; }
        const [hours, minutes, seconds] = timeString.split(":").map(Number);
        if (hours > 23 || minutes > 59 || (seconds !== undefined && seconds > 59)) { return false; }
        const s = seconds !== undefined ? seconds : 0;
        return String(hours).padStart(2,'0') + ':' + String(minutes).padStart(2,'0') + ':' + String(s).padStart(2,'0');
    }

    function parseDate(dateString)
    {
        const [day, month, year] = dateString.split("-").map(Number);
        return new Date(year, month - 1, day);
    }

    function parseTime(timeString)
    {
        const [hours, minutes, seconds = 0] = timeString.split(":").map(Number);
        return [hours, minutes, seconds];
    }

    function isNumber(inputElement)
    {
        const value = Number(inputElement);
        if (isNaN(value))
        {
            contenuMsg.innerText    = LANG_CORRECT.errYNum;
            contenuMsg.style.display = 'block';
            return false;
        }
        return true;
    }

</script>