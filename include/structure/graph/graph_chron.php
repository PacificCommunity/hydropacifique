<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
DISPLAY DATA BY GRAPH - Multi Graphs
This page displays chronicle graphs
- One graph per station
- Multiple chronicles possible per station/graph
- If only one chronicle, it can be corrected or used to generate other chronicles (QJ, QM, QA, etc.)
- Graphs can be enlarged for better visibility
- The left column allows for precise and simple navigation through graphs
- Zoom updates date limits and y-axis
- Clicking on Synchronize or Generate adjusts all graphs to the same scales simultaneously
----------------------------------------
*/

// INIT VAR
$nb_data = 0;
$graph_x = '';
$graph_y = '';
$min_y = 0;
$max_y = 0;
$min_x = $date_2;
$max_x = $date_1;
$id_station_encours = 0;
$lacune_date_first = '';
$edit_lacune_temp = '';
$nb_lacunes = 0;
$js_syncAbsc_var = '';
$js_syncOrdon_var = '';

// Table for initial curve/bar configuration by chronicle type
$tab_param = [];
$colorIndex = 1;
$colorGraph = colorList();          // High-contrast palette (auto-assignment, 18 colors)
$colorPickerPalette = colorPalette(); // Rich palette (manual picker, 42 colors)
$maxColors = count($colorGraph);

// Statistics configuration: ID => Label
$stats = [
    TEXT_STATS_MEAN,
    TEXT_STATS_PERCENTILE_99,
    TEXT_STATS_PERCENTILE_90,
    TEXT_STATS_QUARTILE_75,
    TEXT_STATS_MEDIAN,
    TEXT_STATS_QUARTILE_25,
    TEXT_STATS_PERCENTILE_10,
    TEXT_STATS_PERCENTILE_1
];

$periodes = [2, 5, 10, 20, 30, 40, 50, 100]; // Return periods array
$html_param = '';

if(isset($station_chron_array) && sizeof($station_chron_array) > 0) {
    foreach($station_chron_array as $cle_station => $typedata_array) {
        foreach($typedata_array as $typedata_chron => $sql) {
            if(!isset($tab_param[$typedata_chron])) {
                if($colorIndex > $maxColors) {
                    $colorIndex = 1; // Restart from beginning if exceeded
                }

                // Assign current color (auto-assignment from colorList)
                $tab_param[$typedata_chron]['color'] = $colorIndex;
                $tab_param[$typedata_chron]['line'] = 2;

                // Move to next color in list
                $colorIndex++;
            }
        }
    }
}


// =============================================================================
// CURVE PARAMETERS TABLE
// One row per chronicle (CI, QI, PJE, ...)
//   - Column 1 : code of the chronicle (init_type_data)
//   - Column 2 : color picker (current swatch -> opens 6-column grid)
//   - Column 3 : line width control (- value +)
//
// Two color sources are used:
//   - $colorGraph         (= colorList())    : 18 high-contrast colors used to
//                                              AUTO-ASSIGN a default color to
//                                              each new chronicle.
//   - $colorPickerPalette (= colorPalette()) : 42 colors organized by hue,
//                                              displayed in the grid for the
//                                              user to pick any nuance freely.
// =============================================================================
$html_param = "<table id='table_tri' class='curve-param-table' style='width:100%;'>";

    foreach($tab_param as $typedata_chron => $param)
    {
        // RA and JGE rows are not styled curves, skip them
        if($typedata_chron == 'ra' || $typedata_chron == 'jge') {
            continue;
        }

        $row_l            = "class='row1' onmouseover=\"this.className='row1hover';\" onmouseout=\"this.className='row1';\"";
        $idCurrentColor   = $param['color'];
        $currentColorHex  = $colorGraph[$idCurrentColor];
        $currentLineWidth = isset($param['line']) ? $param['line'] : 2;

        $html_param .= "<tr ".$row_l.">";

            // ---- Code de la chronique ----
            $html_param .= "<td style='width:50px;font-weight:600;'>"
                         . $type_chron_array[$typedata_chron]['init_type_data']
                         . "</td>";

            // ---- Color picker (current swatch + grid popup) ----
            $html_param .= "<td style='width:50px;'>";

                $html_param .= "<div class='color-dropdown'>";

                    // Current color swatch (clickable)
                    $html_param .= "<div id='selectedColor_".$typedata_chron."'"
                                 . " class='dropdown-selected'"
                                 . " onclick='toggleDropdownColor(".$typedata_chron.")'"
                                 . " style='background-color:".$currentColorHex.";'></div>";

                    // Color grid (uses the rich palette for free choice)
                    $html_param .= "<div id='dropdownList_".$typedata_chron."' class='color-grid'>";
                        foreach($colorPickerPalette as $id => $color) {
                            // Highlight the cell if it matches the current color (case-insensitive)
                            $is_selected = (strcasecmp($color, $currentColorHex) === 0) ? ' is-selected' : '';
                            $html_param .= "<div class='color-cell".$is_selected."'"
                                         . " style='background-color:".$color."'"
                                         . " title='".$color."'"
                                         . " onclick=\"selectColor('".$color."',".$typedata_chron.");\"></div>";
                        }
                    $html_param .= "</div>";

                $html_param .= "</div>";

                $html_param .= "<input type='hidden' id='input_color_".$typedata_chron."'"
                             . " value='".$currentColorHex."'>";

            $html_param .= "</td>";

            // ---- Line width control (- value +) ----
            $html_param .= "<td style='width:90px;'>";

                $html_param .= "<div class='linew-control'>";
                    $html_param .= "<button type='button' class='linew-btn'"
                                 . " title='-0.5'"
                                 . " onclick=\"bumpLineWidth('".$typedata_chron."',-0.5);\">−</button>";

                    $html_param .= "<button type='button' class='linew-btn'"
                                 . " title='+0.5'"
                                 . " onclick=\"bumpLineWidth('".$typedata_chron."',0.5);\">+</button>";
                $html_param .= "</div>";

            $html_param .= "</td>";

        $html_param .= "</tr>";
    }

$html_param .= "</table>";


// Start HTML
require(DIR_WS_STRUCTURE . 'header_web.php');

echo "<body>";

    require(DIR_WS_STRUCTURE . 'block_wait.php'); // Waiting block during server queries
    require(DIR_WS_STRUCTURE . 'block_graph.php'); // Full screen graph display
    require(DIR_WS_STRUCTURE . 'block_lacunes_info.php'); // Data gaps information display
    require(DIR_WS_GRAPH . 'block_stats.php'); // Block for displaying chronicle statistics
    require(DIR_WS_GRAPH . 'block_tab.php'); // Block for displaying data in table format

    require(DIR_WS_STRUCTURE . 'header.php'); // Top banner
    include(DIR_WS_BOX . 'nav_accueil.php'); // Menu

    echo "<div id='contour_general'>";
        echo "<div id='contenu_info' style='display:none;'></div>";

        echo "<div id='contenu_centre'>";
            echo "<div id='contenu_box2'>";
                echo "<h1>";
                    echo "<span>".TEXT_DATA_VISUALIZATION."</span>";
                echo "</h1>";

                // Left column
                echo "<div id='cadre_graph' style='float:left;width:210px;margin-right:0.5%;height:calc(98vh - 140px);overflow-y: auto;'>";

                    echo "<div id='boxpopup' class='select-top' style='width:185px;margin:0px;padding:5px;'>";
                        echo "<p style='margin-left:1%;'>";
                            echo "<input type='checkbox' id='check_lac' checked>";
                            echo "<span style='margin-left:5px;font-size:11px;font-weight:normal;'>";
                                echo TEXT_SHOW_GAPS;
                            echo "</span>";
                        echo "</p>";

                        if(INIT_T != 'NC') {
                            // Sidebar stats (mean, percentiles, return periods)
                            // only make sense for a single time series. In the old
                            // mode that meant "1 station". In the new "1 graph per
                            // chronicle" mode, that means "exactly 1 graph total"
                            // (i.e. 1 station AND 1 chronicle selected).
                            $nb_graph_total_for_sidebar = 0;
                            if(isset($station_chron_array)) {
                                foreach($station_chron_array as $tmp_typedata_array) {
                                    $nb_graph_total_for_sidebar += sizeof($tmp_typedata_array);
                                }
                            }
                            if($nb_graph_total_for_sidebar == 1) {
                                $nb_trace_line = 0;

                                echo "<div style='width:180px;margin: 10px 0;margin-left:2px;'>";
                                    echo "<p class='toggle-graph' data-menu-graph='stats' style='font-size:12px;color:#000;padding-top:5px;'>";
                                        echo "<span style='font-weight:normal;font-size: 11px;'>";
                                            echo TEXT_STATS_LINES;
                                        echo "</span>";
                                        echo "<span class='arrow' style='cursor:pointer;'>&#9650;</span>";
                                    echo "</p>";

                                    // Stats List
                                    echo "<div class='navMenuGraph' style='margin-left:10%;display:none;'>";
                                        foreach($stats as $label) {
                                            $nb_trace_line++;
                                            echo "<p>";
                                                echo "<input type='checkbox' id='checkStat_1_".$nb_trace_line."' onclick='visibleTrace(1,".$nb_trace_line.");'>";
                                                echo "<span style='margin-left:5px; font-size:11px; font-weight:normal;'>".$label."</span>";
                                            echo "</p>";
                                        }
                                    echo "</div>";
                                echo "</div>";

                                echo "<div id='timePeriod'>";
                                    echo "<div style='width:180px;margin: 10px 0;margin-left:2px;'>";
                                        echo "<p class='toggle-graph' data-menu-graph='timeperiod' style='font-size:12px;color:#000;padding-top:5px;'>";
                                            echo "<span style='font-weight:normal;font-size: 11px;'>";
                                                echo TEXT_RETURN_PERIOD;
                                            echo "</span>";
                                            echo "<span class='arrow' style='cursor:pointer;'>&#9650;</span>";
                                        echo "</p>";

                                        // Return Period List
                                        echo "<div class='navMenuGraph navReturnPeriod' style='display:flex;flex-wrap:wrap;'>";
                                            foreach($periodes as $annees) {
                                                $nb_trace_line++;
                                                echo "<p style='width:50%;margin:2px 0;display:flex;align-items:center;'>";
                                                    echo "<input type='checkbox' id='checkStat_2_".$nb_trace_line."' onclick='visibleTrace(2,".$nb_trace_line.");'>";
                                                    echo "<span style='margin-left:5px;font-size:11px;font-weight:normal;white-space:nowrap;'>".$annees." ".TEXT_YEARS."</span>";
                                                echo "</p>";
                                            }
                                        echo "</div>";
                                    echo "</div>";
                                echo "</div>";
                            }
                        }

                    echo "</div>";

                    // Graph management options (Dynamic zoom and scale control)
                    echo "<div id='boxpopup' class='select-top' style='width:185px;margin:0px;margin-top:10px;padding-top:5px;padding-left:10px;'>";
                        echo "<p>";
                            echo "<span style='font-weight: bold;font-size:13px;width:150px;'>".TEXT_ZOOM_CONTROL."</span>";
                        echo "</p>";

                        // Start date zoom
                        echo "<div id='boite_small' class='select_date' style='margin-right:10px;'>";
                            echo "<p style='width:80px;color:#428bca;'>".TEXT_START_DATE."</p>";
                            echo "<input class='input_texte' 
                                            style='width:65px;padding-bottom: 4px;' 
                                            name='x1Zoom' id='x1Zoom'
                                            onFocus='initDatepickers(this)'
                                            placeholder='dd-mm-yyyy'
                                            type='text'>";
                        echo "</div>";

                        // End date zoom
                        echo "<div id='boite_small' class='select_date' style='margin-right:0px;'>";
                            echo "<p style='width:80px;color:#428bca;'>".TEXT_END_DATE."</p>";
                            echo "<input class='input_texte' 
                                            style='width:65px;padding-bottom: 4px;' 
                                            name='x2Zoom' id='x2Zoom' 
                                            onFocus='initDatepickers(this)'
                                            placeholder='dd-mm-yyyy'
                                            type='text'>";
                        echo "</div>";

                        echo "<hr>";

                        // Scale
                        echo "<div id='boite_small' class='select_date' style='margin-right:10px;'>";
                            echo "<p style='width:50px;color:#428bca;' >".TEXT_Y_MIN."</p>";
                            echo "<input type='text' style='width:45px;' id='y1Zoom' value=''/>";
                        echo "</div>";

                        echo "<div id='boite_small' class='select_date' style='margin-right:0px;'>";
                            echo "<p style='width:50px;color:#428bca;' >".TEXT_Y_MIN."</p>";
                            echo "<input type='text' style='width:45px;' id='y2Zoom' value=''/>";
                        echo "</div>";

                        echo "<hr>";

                        // Button to synchronize zoom on x-axis
                        echo "<button id='ajustCoord' class='zoom_graph' style='width:125px;margin-top:5px;margin-bottom:10px;' title='".TEXT_ADJUST_SCALE."'>";
                            echo TEXT_ADJUST_SCALE;
                        echo "</button>";
                    echo "</div>";

                    // Time navigation box by year and month
                    echo "<div id='boxpopup' class='select-top' style='width:185px;margin:0px;margin-top:10px;padding-top:5px;padding-left:10px;'>";
                        echo "<p style='width:80px;'>";
                            echo "<span style='font-weight: bold;font-size:13px;'>".TEXT_FIXED_PERIOD."</span>";
                        echo "</p>";

                        echo "<div id='boite_small' class='list_year' style='margin-right:10px;'>";
                            echo "<p style='color:#428bca;'>".TEXT_YEAR."</p>";
                            echo "<select id='select_year_zoom' name='select_year_zoom' style='width:58px;'>";
                                echo "<option value='0'>-</option>";

                                for($a=$year_2; $a>=$year_1; $a--) {
                                    echo "<option value='".$a."'>".$a."</option>";
                                }
                            echo "</select>";
                        echo "</div>";

                        echo "<div id='boite_small' class='list_month'>";
                            echo "<p style='color:#428bca;'>".TEXT_MONTH."</p>";
                            echo select_mois_vide('select_month_zoom');
                        echo "</div>";

                        echo "<hr>";
                        echo "<button id='zoomPeriode' class='zoom_graph' style='width:70px;margin-top:10px;margin-bottom:14px;margin-left:0px;'>".TEXT_GENERATE."</button>";
                        echo "<button id='zoomPeriode_previous' class='zoom_graph' style='width:30px;margin-top:10px;margin-bottom:14px;margin-left:0px;'><<</button>";
                        echo "<button id='zoomPeriode_next' class='zoom_graph' style='width:30px;margin-top:10px;margin-bottom:14px;margin-left:0px;'>>></button>";
                    echo "</div>";

                    echo "<div id='boxpopup' class='select-top' style='width:185px;margin:0px;margin-top:10px;padding:5px 0;padding-left:10px;'>";
                        echo "<p class='toggle-graph' data-menu-graph='param' style='width:175px;font-size:12px;color:#000;padding-top:5px;'>";
                            echo "<span style='font-weight: bold;font-size:13px;'>";
                                echo TEXT_CONFIGURATION;
                            echo "</span>";
                            echo "<span class='arrow' style='cursor:pointer;'>&#9660;</span>";
                        echo "</p>";

                        // Box to display list of chronicles in graph with curve color configuration
                        echo "<div id='curve-param' class='navMenuGraph' style='display:none;'>";
                            echo "<p style='width:80px;margin-top:10px;color:#428bca;'>";
                                echo TEXT_TRACES;
                            echo "</p>";
                            echo $html_param;
                        echo "<hr>";
                        echo "</div>";
                    echo "</div>";
                echo "<hr>";
                echo "</div>";

                // Graph block
                echo "<div id='cadre_graph' style='float:none;width:auto;'>";
                    echo "<div style='width:auto;height:calc(98vh - 140px);overflow-y: auto;'>";

                        $load_graph_function = '';

                        if(isset($station_chron_array) && sizeof($station_chron_array) > 0) {
                            
                            $num_graph = 1;

                            // -----------------------------------------------
                            // MODE "1 graph per chronicle (time series)"
                            // -----------------------------------------------
                            // We now produce ONE graph for EACH chronicle (typedata)
                            // of EACH station, instead of one graph per station with
                            // every chronicle stacked as separate Plotly traces.
                            //
                            // To avoid clashes between DOM ids and JS variable names
                            // when several chronicles share the same station, the
                            // suffix used everywhere has changed from
                            //     $cle_station
                            // to a composite key
                            //     $plot_key = $cle_station . '_' . $typedata_chron
                            // PHP variable names cannot contain dashes, so the
                            // underscore is fine here and the key is a valid
                            // identifier on both PHP (variable variables) and JS
                            // (DOM ids) sides.
                            //
                            // NB: anything that targets the *real station* (SQL
                            // queries, modif_station.php link, station code/name
                            // shown in the graph title) keeps using $cle_station.
                            // -----------------------------------------------

                            // Total number of graphs across all stations - drives the
                            // 1-column vs 2-column layout below.
                            $nb_graph_total = 0;
                            foreach($station_chron_array as $tmp_typedata_array) {
                                $nb_graph_total += sizeof($tmp_typedata_array);
                            }

                            foreach($station_chron_array as $cle_station => $typedata_array) {

                                // Per-station init (kept for the few places that still
                                // reference it - the multi-chronicle stats block, etc.)
                                ${'hidden_check_chron_'.$cle_station} = '';

                                $nom_type_data = $eq_type_array[$station_all_array[$cle_station]['type_station']]['nom_eq_type'];

                                foreach($typedata_array as $typedata_chron => $sql_chron) {

                                    // Composite key used as suffix for every DOM id
                                    // and every per-graph JS variable. Plot div is now
                                    // plot_{station}_{chronicle} instead of plot_{station}.
                                    $plot_key = $cle_station.'_'.$typedata_chron;

                                    // INIT axis scales (per-graph, indexed by composite key)
                                    ${'max_'.$plot_key} = 0;
                                    ${'min_'.$plot_key} = 0;

                                    // Dimensions for displaying multiple graphs
                                    $width_boxGraph = 'width:49%;';
                                    $marginright_boxGraph = '';
                                    $margintop_boxGraph = '';
                                    $height_plot = 'height:40vh;';

                                    if(($num_graph % 2) == 0) {
                                        $marginright_boxGraph = 'margin-left:1%;';
                                    }
                                    if($num_graph > 2) {
                                        $margintop_boxGraph = 'margin-top:20px;';
                                    }

                                    // Single graph on screen → take the whole width.
                                    if($nb_graph_total == 1) {
                                        $width_boxGraph = 'width:99%;';
                                        $height_plot = 'height:calc(75vh - 160px);';
                                    }

                                    // Short label of the chronicle (e.g. PJE, QI, ...)
                                    // displayed next to the equipment-type name in the
                                    // graph header.
                                    $chron_label = '';
                                    if(isset($type_chron_array[$typedata_chron])) {
                                        $chron_label = $type_chron_array[$typedata_chron]['init_type_data'];
                                        if(!empty($type_chron_array[$typedata_chron]['nom_type_data'])) {
                                            $chron_label .= ' - '.$type_chron_array[$typedata_chron]['nom_type_data'];
                                        }
                                    }
                                    // Special non-standard chronicles (RA, JGE) use the
                                    // already-translated constants instead of strtoupper(),
                                    // which would produce a French abbreviation in any
                                    // language ('RA' looks like a code but it's not the
                                    // English label of 'manual stage reading').
                                    if($chron_label == '') {
                                        if($typedata_chron === 'ra') {
                                            $chron_label = TEXT_CHRON_RA;
                                        } elseif($typedata_chron === 'jge') {
                                            $chron_label = TEXT_CHRON_JGE;
                                        } else {
                                            // Fallback for any future non-standard
                                            // chronicle not handled above.
                                            $chron_label = strtoupper((string)$typedata_chron);
                                        }
                                    }

                                    // Title text passed to zoom_graph() — used in the
                                    // popup title bar. We append the chronicle label so
                                    // the popup tells which time series is shown.
                                    $popup_title_data = $nom_type_data.' - '.$chron_label;

                                    // Raw series: render the acronym as a red badge (white
                                    // on #A32D2D) and append "(Raw)", mirroring the Step 2
                                    // selection table and the correction page. Only applies
                                    // when a standard acronym exists (init_type_data); the
                                    // RA/JGE/fallback labels keep their plain rendering.
                                    $chron_is_raw = (isset($type_chron_array[$typedata_chron]['raw_data'])
                                                     && $type_chron_array[$typedata_chron]['raw_data'] == 1);
                                    $chron_label_html = $chron_label; // default: plain text
                                    if ($chron_is_raw
                                        && isset($type_chron_array[$typedata_chron]['init_type_data'])
                                        && $type_chron_array[$typedata_chron]['init_type_data'] !== '')
                                    {
                                        $raw_acro = $type_chron_array[$typedata_chron]['init_type_data'];
                                        $raw_name = $type_chron_array[$typedata_chron]['nom_type_data'];
                                        $chron_label_html = "<span style='display:inline-block;background:#A32D2D;"
                                                          . "color:#fff;font-weight:bold;padding:1px 8px;border-radius:4px;'>"
                                                          . $raw_acro . "</span>";
                                        if (!empty($raw_name)) {
                                            $chron_label_html .= " - " . $raw_name;
                                        }
                                        $chron_label_html .= " (" . TEXT_CHRON_RAW_DATA . ")";
                                    }

                                    echo "<div id='boxpopup' class='select graph-card' style='margin:0;".$width_boxGraph.$marginright_boxGraph.$margintop_boxGraph."padding:0;border-radius: 2px;'>";

                                        // ----------------------------------------
                                        // Graph header — flex row:
                                        //   left  : <p class='titre'> with station / chronicle / row count
                                        //   right : action buttons (Enlarge, Data gaps, Stats)
                                        // The buttons themselves keep their classes
                                        // (#button_visu, .button_lacune, etc.), so all
                                        // their colors stay intact. The float:right that
                                        // is hard-coded on each of these classes in
                                        // form.css is neutralized inside .graph-actions
                                        // via the small CSS snippet shipped with this
                                        // refactor (see the comment below).
                                        // ----------------------------------------
                                        echo "<div class='graph-header'>";

                                            echo "<p class='titre graph-title' style='margin:0;padding:4px 10px;font-size:14px;border-radius: 2px 4px 0 0;'>";
                                                echo $nom_type_data." - ".$chron_label_html;
                                                echo "<br>";
                                                echo "<a href='modif_station.php?ref=".$cle_station."' target='_blank' style='font-size:14px;'>";
                                                    echo $station_all_array[$cle_station]['code_station']." - ".$station_all_array[$cle_station]['nom_station'];
                                                echo "</a>";
                                                echo "<span id='total_rows_".$plot_key."' style='margin-left:25px;font-size:14px;font-weight:normal;color:#fff;'></span>";
                                            echo "</p>";

                                            echo "<div class='graph-actions'>";

                                                // Expose station / chronicle labels for the CSV
                                                // export filename (used by hpExportChartCsv).
                                                echo "<script>"
                                                   . "window.hpChartMeta = window.hpChartMeta || {};"
                                                   . "hpChartMeta['".$plot_key."'] = {"
                                                   . "code:'".addslashes($station_all_array[$cle_station]['code_station'])."',"
                                                   . "nom:'".addslashes($station_all_array[$cle_station]['nom_station'])."',"
                                                   . "init:'".addslashes($type_chron_array[$typedata_chron]['init_type_data'] ?? '')."'"
                                                   . "};</script>";

                                                // ----------------------------------------
                                                // New header controls (Option A):
                                                //   - a Full screen button
                                                //   - a single "Tools" dropdown grouping
                                                //     Statistics / Data qualification / Gaps /
                                                //     Export CSV.
                                                // The legacy buttons (#button_visu, gaps, stats,
                                                // tab) are kept in the DOM but hidden; the menu
                                                // entries just trigger their existing handlers
                                                // (.click()), so none of their wiring changes.
                                                // ----------------------------------------

                                                // Full screen (reuses zoom_graph directly)
                                                echo "<button type='button' class='hp-btn-fs'"
                                                   . " onclick=\"zoom_graph('".$plot_key."','".$station_all_array[$cle_station]['code_station']."','".$station_all_array[$cle_station]['nom_station']."','".addslashes($popup_title_data)."');\">"
                                                   . "<i class='ico-fs'></i>"
                                                   . TEXT_FULLSCREEN
                                                   . "</button>";

                                                // Tools dropdown
                                                echo "<div class='hp-menu' id='hp_menu_".$plot_key."'>";
                                                    echo "<button type='button' class='hp-btn-tools' onclick=\"hpToggleMenu('".$plot_key."');\">"
                                                       . TEXT_TOOLS
                                                       . " <span class='hp-caret'>&#9662;</span></button>";

                                                    echo "<div class='hp-menu-list' id='hp_menu_list_".$plot_key."'>";

                                                        // Edit / Correct the series -> submits the hidden
                                                        // correction form (opens data_chron.php in a new tab).
                                                        // The form is generated by the server only for editable
                                                        // continuous chronicles (see process_graph_multi.php).
                                                        // We render the menu item with the SAME guards the old
                                                        // button used (gestion_data + not ra/jge); the no-markers
                                                        // case is handled server-side by the form simply not
                                                        // existing, so the edit action is then a no-op.
                                                        if ($gestion_data > 1
                                                            && $typedata_chron != 'ra' && $typedata_chron != 'jge'
                                                            && (!isset($type_chron_array[$typedata_chron]['typegraph'])
                                                                || $type_chron_array[$typedata_chron]['typegraph'] != 'markers'))
                                                        {
                                                            echo "<div class='hp-menu-item' onclick=\"hpMenuAction('".$plot_key."','edit');\">"
                                                               . "<span class='hp-mi-ico'>&#9998;</span>"
                                                               . TEXT_BTN_EDIT_CHRON
                                                               . "</div>";
                                                        }

                                                        // Statistics -> triggers the injected stats button
                                                        echo "<div class='hp-menu-item' onclick=\"hpMenuAction('".$plot_key."','stats');\">"
                                                           . "<span class='hp-mi-ico'>&#128202;</span>"
                                                           . TEXT_STATS_TITLE
                                                           . "</div>";

                                                        // Data qualification (data_meta summary) — wired in a later step
                                                        echo "<div class='hp-menu-item' onclick=\"hpMenuAction('".$plot_key."','qualif');\">"
                                                           . "<span class='hp-mi-ico'>&#9745;</span>"
                                                           . TEXT_DATA_QUALIF
                                                           . "</div>";

                                                        // Gaps -> triggers the existing gaps popup
                                                        echo "<div class='hp-menu-item' onclick=\"hpMenuAction('".$plot_key."','gaps');\">"
                                                           . "<span class='hp-mi-ico'>&#128201;</span>"
                                                           . TEXT_GAPS
                                                           . "</div>";

                                                        // Export chart data as CSV — wired in a later step
                                                        echo "<div class='hp-menu-item' onclick=\"hpMenuAction('".$plot_key."','export_csv');\">"
                                                           . "<span class='hp-mi-ico'>&#11015;</span>"
                                                           . TEXT_EXPORT_GRAPH_CSV
                                                           . "<span class='hp-mi-fmt'>CSV</span></div>";

                                                    echo "</div>"; // .hp-menu-list
                                                echo "</div>"; // .hp-menu

                                                // ---- Legacy buttons kept but hidden (still wired) ----
                                                echo "<div style='display:none;'>";
                                                    echo "<div id='button_visu' onclick=\"zoom_graph('".$plot_key."','".$station_all_array[$cle_station]['code_station']."','".$station_all_array[$cle_station]['nom_station']."','".addslashes($popup_title_data)."');\">";
                                                        echo TEXT_ENLARGE;
                                                    echo "</div>";

                                                    if($gestion_data > 0) {
                                                        echo "<div id='button_lacune_".$plot_key."' class='button_lacune' title='".TEXT_GAPS_TABLE."'>";
                                                            echo TEXT_GAPS;
                                                        echo "</div>";
                                                    }

                                                    echo "<div id='button_tab_".$plot_key."'></div>";
                                                    echo "<div id='button_stats_".$plot_key."'></div>";
                                                echo "</div>";

                                            echo "</div>"; // .graph-actions

                                        echo "</div>"; // .graph-header

                                        // Zoom control bar (unchanged)
                                        echo "<div style='height:25px;margin-right:15px;'>";
                                            echo "<div style='float:right;'>";
                                                echo "<input type='checkbox' id='check_zoom_x_".$plot_key."' checked onclick=\"zoomCTRL('".$plot_key."');\">";
                                                echo "<span style='margin-left:5px;font-size:11px;font-weight:normal;'>".TEXT_ZOOM_MOVE_X."</span>";
                                            echo "</div>";

                                            echo "<div style='float:right;margin-right:15px;'>";
                                                echo "<input type='checkbox' id='check_zoom_y_".$plot_key."' checked onclick=\"zoomCTRL('".$plot_key."');\">";
                                                echo "<span style='margin-left:5px;font-size:11px;font-weight:normal;'>".TEXT_ZOOM_MOVE_Y."</span>";
                                            echo "</div>";
                                        echo "</div>";

                                        echo "<div id='plot_".$plot_key."' class='graph' style='".$height_plot."margin:0 1%;display:none;'></div>";

                                        // Meta coverage timeline (quality-code colors + obs),
                                        // drawn under the chart and kept x-synced with it.
                                        echo "<div id='plot_meta_".$plot_key."' style='width:98%;margin:0 1%;display:none;'></div>";

                                        echo "<div id='wait_".$plot_key."' style='width:100%;".$height_plot."text-align:center;'>";
                                            echo "<div class='spinner' style='width:50px; height:50px;margin:10% auto 0;'></div>";
                                            echo "<p style='margin:15px 0;font-size:14px;'>".TEXT_LOADING."</p>";
                                            echo "<p style='font-size:14px;'>".TEXT_LONG_LOADING_WARNING."</p>";
                                        echo "</div>";

                                        echo "<div id='msg_".$plot_key."' style='width:100%;margin-top:25px;".$height_plot."text-align:center;display:none;'>";
                                        echo "</div>";

                                        // The backend still expects an associative
                                        // typedata_array (it iterates with foreach),
                                        // so we pass a single-entry object holding
                                        // only this chronicle. The backend loop just
                                        // runs once.
                                        $single_typedata_array = array($typedata_chron => $sql_chron);
                                        $load_graph_function .= "load_graph('".$plot_key."',".$cle_station.",".$station_all_array[$cle_station]['type_station']."," .json_encode($single_typedata_array).");";

                                        $graphDiv = "document.getElementById('plot_".$plot_key."')";
                                        $js_syncAbsc_var .= "if(".$graphDiv." && ".$graphDiv.".style.display == 'block'){Plotly.relayout('plot_".$plot_key."', {'xaxis.range': [x1_format, x2_format]});}";
                                        $js_syncOrdon_var .= "if(".$graphDiv." && ".$graphDiv.".style.display == 'block'){Plotly.relayout('plot_".$plot_key."', {'yaxis.range': [y1, y2]});}";

                                        // box_options height is reserved (min-height) so that all
                                        // graph cards keep the same overall height regardless of
                                        // whether the "Edit or Correct" button is rendered inside
                                        // (it is hidden for markers / RA / JGE chronicles). Without
                                        // this reservation, cards without the button were ~40px
                                        // shorter and the float layout reflowed the next card to
                                        // the right of an unrelated card above.
                                        echo "<div id='box_options_".$plot_key."' style='float:left;width:98%;margin-top:0;margin-left:2%;'>";
                                            echo "<div style='float:left;' id='axeChange_".$plot_key."'>";
                                                echo "<button id='plus_".$plot_key."' class='decimal_axe' style='margin-left:10px;' title='".TEXT_ADD_DECIMAL."' onCLick=\"updateDecimals('plot_".$plot_key."','yaxis','+');\">+</button>";
                                                echo "<button id='moins_".$plot_key."' class='decimal_axe' title='".TEXT_REMOVE_DECIMAL."' onCLick=\"updateDecimals('plot_".$plot_key."','yaxis','-');\">-</button>";
                                                echo "<hr style='margin:2px 0;border:0;height:0;'>";
                                                echo "<button id='log_".$plot_key."' class='log_axe' style='float:left;' title='".TEXT_LOG_SCALE."'>";
                                                    echo TEXT_LOG_SCALE_SHORT;
                                                echo "</button>";

                                                // Zoom history (undo / redo). Back steps one
                                                // level in this graph's zoom history; Forward
                                                // re-applies a view we stepped back from. Both
                                                // start disabled (greyed) and are managed by
                                                // the zoom-history module below.
                                                echo "<button id='zoom_back_".$plot_key."' class='log_axe'"
                                                   . " style='float:left;margin-left:4px;opacity:0.4;cursor:not-allowed;' disabled"
                                                   . " title='".TEXT_CORRECT_ZOOM_BACK_TITLE."'"
                                                   . " onclick=\"zoomBack('".$plot_key."');\">".TEXT_CORRECT_ZOOM_BACK."</button>";
                                                echo "<button id='zoom_fwd_".$plot_key."' class='log_axe'"
                                                   . " style='float:left;margin-left:4px;opacity:0.4;cursor:not-allowed;' disabled"
                                                   . " title='".TEXT_CORRECT_ZOOM_FORWARD_TITLE."'"
                                                   . " onclick=\"zoomForward('".$plot_key."');\">".TEXT_CORRECT_ZOOM_FORWARD."</button>";
                                            echo "</div>";
                                            echo "<div style='float:left;margin-top:10px;margin-left:10%;'>";
                                                echo "<div id='button_calcul_".$plot_key."' style='float:right;border:0;margin:10px auto;'></div>";
                                            echo "</div>";

                                            // Field-report click hint — floated right, on the
                                            // SAME row as the controls. clear:right drops it
                                            // just under the Edit/All-data block (which also
                                            // floats right) instead of beside it, keeping the
                                            // right column tidy.
                                            echo "<div style='float:right;clear:right;text-align:right;"
                                               . "margin-top:30px;padding:0 10px 8px 0;font-size:13px;color:#666;font-style:italic;box-sizing:border-box;'>";
                                                echo "<span style='display:inline-block;width:11px;height:11px;background:#FFE100;"
                                                . "border:1px solid #000;vertical-align:middle;margin-right:6px;'></span>";
                                                echo TEXT_GRAPH_FR_CTRLCLICK_HINT;
                                            echo "</div>";

                                        echo "</div>";

                                    //echo "<hr style='clear:both;'>";
                                    echo "</div>";

                                    $num_graph++;
                                } // end foreach typedata_chron
                            } // end foreach station
                        } else {
                            echo "<div id='boxpopup'>";
                                echo "<p class='alert'>".TEXT_NO_DATA_FOUND."</p>";
                                echo "<hr>";
                            echo "</div>";
                        }
                    echo "</div>";
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

<style>
    /* ---- Graph header controls (Option A bandeau) ---- */
    /* .graph-header / .graph-actions positioning lives in formulaire.css.
       A modest z-index lets the OPEN dropdown clear the Plotly modebar. The
       inline header is explicitly hidden while the full-screen overlay is
       open (see hpHideHeadersForFullscreen) so it never floats over it. */
    .graph-header  { z-index:50; }
    .graph-actions { z-index:50; }

    /* Full screen button: outlined on the dark header */
    .hp-btn-fs {
        background:transparent; color:#fff;
        border:1px solid rgba(255,255,255,.55);
        border-radius:6px; padding:6px 12px;
        font-size:13px; cursor:pointer;
        display:inline-flex; align-items:center; gap:6px;
        line-height:1;
    }
    .hp-btn-fs:hover { background:rgba(255,255,255,.12); }
    .hp-btn-fs .ico-fs {
        width:13px; height:13px; display:inline-block;
        border:1.5px solid currentColor; border-radius:2px;
        position:relative;
    }

    /* Tools dropdown */
    .hp-menu { position:relative; display:inline-block; }
    .hp-btn-tools {
        background:#fff; color:#2c2c2a; border:0;
        border-radius:6px; padding:7px 14px;
        font-size:13px; font-weight:500; cursor:pointer;
        display:inline-flex; align-items:center; gap:6px; line-height:1;
    }
    .hp-btn-tools:hover { background:#f0f0f0; }
    .hp-caret { font-size:10px; }

    .hp-menu-list {
        display:none; position:absolute; right:0; top:calc(100% + 4px);
        min-width:185px; background:#fff;
        border:1px solid #d4d4d4; border-radius:8px;
        box-shadow:0 4px 16px rgba(0,0,0,.18);
        overflow:hidden; z-index:100000;
    }
    .hp-menu-list.open { display:block; }
    .hp-menu-item {
        display:flex; align-items:center; gap:9px;
        padding:9px 12px; font-size:13px; color:#2c2c2a;
        cursor:pointer; white-space:nowrap;
        border-bottom:1px solid #f0f0f0;
    }
    .hp-menu-item:last-child { border-bottom:0; }
    .hp-menu-item:hover { background:#f3f7f9; }
    .hp-mi-ico { font-size:14px; width:18px; text-align:center; color:#666; }
    .hp-mi-fmt {
        margin-left:auto; font-size:11px; color:#9aa0a6;
        border:1px solid #e0e0e0; border-radius:3px; padding:0 5px;
    }

    /* ---- Data popups (qualification / gaps) — modern modal ---- */
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
    /* Small inline spinner shown inside a button during a long action. */
    .hp-btn-spinner {
        display:inline-block; width:13px; height:13px;
        border:2px solid #c9d4da; border-top-color:#3c8da5;
        border-radius:50%; vertical-align:-2px;
        animation:hpBtnSpin 0.7s linear infinite;
    }
    @keyframes hpBtnSpin { to { transform:rotate(360deg); } }
    /* Low-flow methodology popup */
    .hp-lf-help .lf-h {
        font-size:14px; color:#176B87; font-weight:bold;
        margin:10px 0 4px; padding:6px 8px; background:#f1f5f8; border-radius:6px;
    }
    .hp-lf-help .lf-h:before { content:'\25B8\00a0'; color:#176B87; }
    .hp-lf-help .lf-h[data-open='1']:before { content:'\25BE\00a0'; }
    .hp-lf-help .lf-body { font-size:13px; line-height:1.5; padding:2px 10px 8px; }
    .hp-lf-help .lf-body ul { margin:6px 0; padding-left:18px; }
    .hp-lf-help .lf-body li { margin-bottom:4px; }
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

    /* Collapsible menu headers: keep the arrow flush-right of the box */
    .toggle-graph { display:flex; align-items:center; justify-content:space-between; }
    .toggle-graph .arrow { margin-left:auto; flex:0 0 auto; }
</style>

<script>

    // Declare JavaScript constants from PHP constants
    var TEXT_EXPORT_CSV = '<?php echo addslashes(TEXT_EXPORT_CSV); ?>';
    var TEXT_EXPORT_SVG = '<?php echo addslashes(TEXT_EXPORT_SVG); ?>';
    var TEXT_EXPORT_PNG = '<?php echo addslashes(TEXT_EXPORT_PNG); ?>';
    var TEXT_START_BEFORE_END = '<?php echo addslashes(TEXT_START_BEFORE_END); ?>';
    var TEXT_INVALID_DATE_FORMAT = '<?php echo addslashes(TEXT_INVALID_DATE_FORMAT); ?>';
    var TEXT_NUMERIC_ERROR = '<?php echo addslashes(TEXT_NUMERIC_ERROR); ?>';


    // General configuration
    var msgInfo = document.getElementById('contenu_info');
    var boxWait = document.getElementById('box_wait');
    var boxStats = document.getElementById('box_stats');
    var checkLac = document.getElementById('check_lac');
    var checkStats = document.getElementById('check_stats');
    var checkZoomX = document.getElementById('check_zoom_x');
    var checkZoomY = document.getElementById('check_zoom_y');
    var js_traceStation = [];
    var js_shapesLacunesData = [];
    // Meta coverage timeline state, keyed by plot_key:
    //   metaDrawFns[plot_key]   = function(xMinFr, xMaxFr) that redraws it
    //   metaLastRange[plot_key] = last x-range used (so resize can redraw it)
    var metaDrawFns   = {};
    var metaLastRange = {};
    var metaResizeTimer = null;
    // The meta-timeline SVG is generated for a fixed logical width, so a
    // browser-window resize would otherwise stretch it. Redraw every visible
    // timeline (debounced) on its last known range when the window resizes.
    window.addEventListener('resize', function() {
        clearTimeout(metaResizeTimer);
        metaResizeTimer = setTimeout(function() {
            for (var pk in metaDrawFns) {
                if (!metaDrawFns.hasOwnProperty(pk)) { continue; }
                var r = metaLastRange[pk] || { min: '', max: '' };
                metaDrawFns[pk](r.min, r.max);
            }
        }, 200);
    });

    // -----------------------------------------------------------------
    // drawMetaTimelineCore(metaDiv, chartDiv, key, idStation, typedataChron, xMinFr, xMaxFr)
    // Generic meta-timeline renderer shared by the inline graphs and the
    // full-screen (zoom_graph) view. It measures the chart plot area, posts
    // to process_graph_meta.php and injects the returned SVG into metaDiv.
    //   - metaDiv  : the <div> that will receive the SVG (id = 'plot_meta_'+key)
    //   - chartDiv : the Plotly graph div to align the X axis with
    //   - key      : DOM key (plot_key inline, or plot_key+'_fs' full screen)
    // xMinFr/xMaxFr are FR dd-mm-yyyy strings (or '' = full range).
    function drawMetaTimelineCore(metaDiv, chartDiv, key, idStation, typedataChron, xMinFr, xMaxFr) {
        if (!metaDiv || !chartDiv) { return; }

        metaLastRange[key] = {
            min: xMinFr || '',
            max: xMaxFr || '',
            station: idStation,
            typedata: typedataChron
        };

        // Align the timeline X axis with the chart plot area (see inline notes).
        var metaRect  = metaDiv.getBoundingClientRect();
        var chartRect = chartDiv.getBoundingClientRect();
        var svgWidth  = Math.round(metaRect.width || metaDiv.clientWidth || 0);

        var padLeft = -1, padRight = -1;
        try {
            var fx = chartDiv._fullLayout && chartDiv._fullLayout.xaxis;
            if (fx && typeof fx._offset === 'number' && typeof fx._length === 'number') {
                var plotLeftScreen  = chartRect.left + fx._offset;
                var plotRightScreen = chartRect.left + fx._offset + fx._length;
                padLeft  = plotLeftScreen  - metaRect.left;
                padRight = metaRect.right   - plotRightScreen;
            }
        } catch (e) { /* fall back to server defaults */ }

        var payload = {
            timezone_php:   Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC',
            idStation:      idStation,
            typedataChron:  typedataChron,
            plotKey:        key,
            svgWidth:       svgWidth,
            padLeft:        padLeft,
            padRight:       padRight,
            xDateMin:       xMinFr || '',
            xDateMax:       xMaxFr || ''
        };
        var xhrMeta = new XMLHttpRequest();
        xhrMeta.open('POST', 'include/structure/graph/process_graph_meta.php', true);
        xhrMeta.setRequestHeader('Content-Type', 'application/json');
        xhrMeta.onreadystatechange = function() {
            if (xhrMeta.readyState === 4 && xhrMeta.status === 200) {
                try {
                    var rMeta = JSON.parse(xhrMeta.responseText);
                    if (rMeta && rMeta['js_text']) { eval(rMeta['js_text']); }
                } catch (e) { /* keep previous timeline on parse error */ }
            }
        };
        xhrMeta.send(JSON.stringify(payload));
    }
    var idPlotZoom = 0;
    var js_syncAbsc_var = "<?php echo $js_syncAbsc_var;?>";
    var js_syncOrdon_var = "<?php echo $js_syncOrdon_var;?>";
    var min_x = '<?php echo $date_1;?>';
    var max_x = '<?php echo $date_2;?>';
    var nbStation = "<?php echo sizeof($station_chron_array);?>";
    var tab_param = <?php echo json_encode($tab_param);?>;

    const monIconeDisquette = {
        'width': 1000,
        'height': 1000,
        'path': 'M833.3,166.7v666.6H166.7V166.7H833.3 M833.3,83.3H166.7c-46,0-83.3,37.3-83.3,83.3v666.6c0,46,37.3,83.3,83.3,83.3h666.6c46,0,83.3-37.3,83.3-83.3V166.7C916.7,120.7,879.3,83.3,833.3,83.3L833.3,83.3z M500,333.3c-92,0-166.7,74.7-166.7,166.7c0,92,74.7,166.7,166.7,166.7s166.7-74.7,166.7-166.7C666.7,408,592,333.3,500,333.3L500,333.3z'
    };

    const iconPencil = {
        'width': 1000,
        'height': 1000,
        'path': 'M713.6,125.4c-42.9-42.9-112.4-42.9-155.3,0l-433,433c-3.1,3.1-5.3,7-6.4,11.3l-43.3,173.2c-2.3,9.1,0.4,18.8,7.1,25.5c5.3,5.3,12.5,8.2,19.8,8.2c1.9,0,3.8-0.2,5.7-0.7l173.2-43.3c4.3-1.1,8.2-3.3,11.3-6.4l433-433c42.9-42.9,42.9-112.4,0-155.3L713.6,125.4z M176.6,680.1l-61.9,15.5l15.5-61.9l365-365l46.4,46.4L176.6,680.1z M587.9,268.7l-46.4-46.4l61.8-61.8c17.1-17.1,44.9-17.1,62,0l46.7,46.7c17.1,17.1,17.1,44.9,0,62L649.7,330.5L587.9,268.7z'
    };

    const iconCamera = {
        'width': 1000,
        'height': 1000,
        'path': 'M900,200H700l-50-100H350l-50,100H100c-55,0-100,45-100,100v500c0,55,45,100,100,100h800c55,0,100-45,100-100V300C1000,245,955,200,900,200z M500,750c-138.1,0-250-111.9-250-250s111.9-250,250-250s250,111.9,250,250S638.1,750,500,750z M500,350c-82.8,0-150,67.2-150,150s67.2,150,150,150s150-67.2,150-150S582.8,350,500,350z'
    };

    var config = {
        responsive: true,
        doubleClickDelay: 1000,
        scrollZoom: true,
        displaylogo: false,
        modeBarOrientation: 'v',
        displayModeBar: true,
        modeBarButtons: [
            [
                {
                    name: '<?php echo TEXT_EXPORT_SVG;?>',
                    icon: iconPencil,
                    click: function(gd) {
                        Plotly.downloadImage(gd, {format: 'svg', filename: 'HP-Graph'});
                    }
                },
                {
                    name: '<?php echo TEXT_EXPORT_PNG;?>',
                    icon: iconCamera,
                    click: function(gd) {
                        Plotly.downloadImage(gd, {format: 'png', filename: 'HP-Graph'});
                    }
                },
                'zoom2d',
                'pan2d',
                'resetScale2d'
            ]
        ],
        modeBarButtonsToRemove: ['select2d', 'lasso2d', 'autoScale2d', 'zoomIn2d', 'zoomOut2d']
    };

    // =================================================================
    // ZOOM HISTORY  ("Previous zoom" / "Next zoom" buttons)
    //
    // Per-graph (keyed by plot_key) back + redo stacks of [xMin, xMax]
    // ranges, in Plotly's native ISO string form. Each genuine user
    // zoom/pan pushes the range we are LEAVING onto the back stack and
    // clears the redo stack. zoomBack pops the back stack (saving the
    // current view to redo); zoomForward does the reverse. Every applied
    // range goes through Plotly.relayout exactly like a normal user zoom,
    // so the meta timeline refreshes through its own existing listener.
    //
    // __zoomApplying guards the single relayout WE trigger so it is not
    // recorded as a new user zoom (otherwise we could never move around).
    // =================================================================
    var __zoomStack    = {};   // { plot_key: [[min,max], ...] }  (back / undo)
    var __zoomRedo     = {};   // { plot_key: [[min,max], ...] }  (forward / redo)
    var __zoomApplying = {};   // { plot_key: bool }
    var __zoomCurrent  = {};   // { plot_key: [min,max] } currently displayed

    // Record a relayout: push the range we are leaving, store the new one,
    // and (for a genuine new zoom) invalidate the redo history.
    function zoomHistoryOnRelayout(plot_key, newMin, newMax) {
        if (__zoomApplying[plot_key]) { return; } // our own relayout: skip push
        if (!__zoomStack[plot_key]) { __zoomStack[plot_key] = []; }
        var prev = __zoomCurrent[plot_key];
        if (prev && (prev[0] !== newMin || prev[1] !== newMax)) {
            __zoomStack[plot_key].push(prev);
            if (__zoomStack[plot_key].length > 50) { __zoomStack[plot_key].shift(); }
            __zoomRedo[plot_key] = []; // a brand-new user zoom kills forward history
        }
        __zoomCurrent[plot_key] = [newMin, newMax];
        refreshZoomBtns(plot_key);
    }

    // Initialise current range once after the graph loads.
    function zoomHistoryInit(plot_key, xMin, xMax) {
        __zoomStack[plot_key]   = [];
        __zoomRedo[plot_key]    = [];
        __zoomCurrent[plot_key] = [xMin, xMax];
        refreshZoomBtns(plot_key);
    }

    // Enable/disable (grey) both history buttons for a graph.
    function refreshZoomBtns(plot_key) {
        var back = document.getElementById('zoom_back_' + plot_key);
        if (back) {
            var hasBack = __zoomStack[plot_key] && __zoomStack[plot_key].length > 0;
            back.disabled = !hasBack;
            back.style.opacity = hasBack ? '1' : '0.4';
            back.style.cursor  = hasBack ? 'pointer' : 'not-allowed';
        }
        var fwd = document.getElementById('zoom_fwd_' + plot_key);
        if (fwd) {
            var hasFwd = __zoomRedo[plot_key] && __zoomRedo[plot_key].length > 0;
            fwd.disabled = !hasFwd;
            fwd.style.opacity = hasFwd ? '1' : '0.4';
            fwd.style.cursor  = hasFwd ? 'pointer' : 'not-allowed';
        }
    }

    // Apply a stored range as a guarded relayout (shared by back / forward).
    function applyZoomRange(plot_key, target) {
        var gd = document.getElementById('plot_' + plot_key);
        if (!gd) { return; }
        __zoomCurrent[plot_key] = target;
        __zoomApplying[plot_key] = true;
        Plotly.relayout(gd, {
            'xaxis.range[0]' : target[0],
            'xaxis.range[1]' : target[1],
            'xaxis.autorange': false
        }).then(function() {
            __zoomApplying[plot_key] = false;
            refreshZoomBtns(plot_key);
        });
    }

    // Back: save current view to redo, then apply the previous one.
    function zoomBack(plot_key) {
        var stack = __zoomStack[plot_key];
        if (!stack || stack.length === 0) { return; }
        if (!__zoomRedo[plot_key]) { __zoomRedo[plot_key] = []; }
        var leaving = __zoomCurrent[plot_key];
        if (leaving) {
            __zoomRedo[plot_key].push(leaving);
            if (__zoomRedo[plot_key].length > 50) { __zoomRedo[plot_key].shift(); }
        }
        applyZoomRange(plot_key, stack.pop());
    }

    // Forward: push current view back onto the back stack, then redo one.
    function zoomForward(plot_key) {
        var redo = __zoomRedo[plot_key];
        if (!redo || redo.length === 0) { return; }
        if (!__zoomStack[plot_key]) { __zoomStack[plot_key] = []; }
        var leaving = __zoomCurrent[plot_key];
        if (leaving) {
            __zoomStack[plot_key].push(leaving);
            if (__zoomStack[plot_key].length > 50) { __zoomStack[plot_key].shift(); }
        }
        applyZoomRange(plot_key, redo.pop());
    }

    // Launch graph generation
    //
    // Signature changed to "1 graph per chronicle":
    //   - plot_key      : composite suffix used for every DOM id and JS variable
    //                     for THIS graph (e.g. "245_38" = station 245, chronicle 38)
    //   - cle_station   : real station id (kept for SQL lookups on the backend)
    //   - type_station  : equipment type id of the station
    //   - typedata_array: object with EXACTLY one entry { typedata_chron: sql_chron }
    function load_graph(plot_key, cle_station, type_station, typedata_array) 
    {
        var dataToSend = {
            territoireId: <?php echo $territoire_id;?>,
            lang: '<?php echo LANGUAGE;?>',
            gestionData: '<?php echo $gestion_data;?>',
            nbStation: nbStation,
            plot_key: plot_key,
            cle_station: cle_station,
            type_station: type_station,
            typedata_array: typedata_array,
            min_x: min_x,
            max_x: max_x,
            tab_param: tab_param
        };

        var jsonDataGraph = JSON.stringify(dataToSend);
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "include/structure/graph/process_graph_multi.php", true);
        xhr.setRequestHeader("Content-Type", "application/json");

        xhr.onreadystatechange = function() {
            if(xhr.readyState === 4 && xhr.status === 200) {
                document.getElementById('wait_'+plot_key).style.display = 'none';
                document.getElementById('button_stats_'+plot_key).style.display = 'block';

                var jsonResponse = JSON.parse(xhr.responseText);
                var graphLoad = jsonResponse['graph_load'];

                min_x = jsonResponse['min_x'];
                min_date = min_x;
                max_x = jsonResponse['max_x'];
                max_date = max_x;

                var totalRows = jsonResponse['total_rows'];                
                if (totalRows !== undefined) 
                {
                    var el = document.getElementById('total_rows_' + plot_key);
                    if (el) 
                    {
                        el.innerHTML = ' - ' + totalRows.toLocaleString() + ' ' + '<?= TEXT_GRAPH_RECORDS ?>' + ' - ';
                    }
                }

                text_button_stats = jsonResponse['text_button_stats'];
                document.getElementById('button_stats_'+plot_key).insertAdjacentHTML('beforeend', text_button_stats);

                if(graphLoad)
                {
                    document.getElementById('plot_'+plot_key).style.display = 'block';
                    document.getElementById('box_options_'+plot_key).style.display = 'block';
                    document.getElementById('button_tab_'+plot_key).style.display = 'block';

                    // 'Data gaps' is only meaningful for continuous time
                    // series; it is hidden for scatter-only chronicles
                    // (RA, JGE). We detect that case via the server-side
                    // signal: when text_button_stats is empty, the chart
                    // contains no continuous data and 'Data gaps' would
                    // be misleading too — keep its existing display:none.
                    var isScatterOnly = !text_button_stats || text_button_stats.trim() === '';
                    if(!isScatterOnly && document.getElementById('button_lacune_'+plot_key)) {
                        document.getElementById('button_lacune_'+plot_key).style.display = 'block';
                    }

                    eval(jsonResponse['js_text']);

                    var plotDivId = 'plot_'+plot_key;
                    var graphDiv = document.getElementById(plotDivId);
                    var shapes = graphDiv.layout.shapes;
                    var nbTrace = graphDiv.data.length;

                    // Index per-graph data by plot_key (was cle_station before).
                    // Multiple graphs can now belong to the same station, so
                    // station id is no longer unique enough as a key.
                    js_traceStation[plot_key] = nbTrace;
                    js_shapesLacunesData[plot_key] = shapes;

                    // ---- Meta coverage timeline (quality-code colors + obs) ----
                    // Show its container and draw it for the current x-range,
                    // then keep it x-synced with the chart on every relayout,
                    // and redraw on window resize (the SVG is width-dependent).
                    (function() {
                        var metaDiv = document.getElementById('plot_meta_' + plot_key);
                        if (!metaDiv) { return; }
                        metaDiv.style.display = 'block';

                        // Resolve this graph's single chronicle id (typedata_array
                        // holds exactly one entry { typedata_chron: sql }).
                        var typedataChron = Object.keys(typedata_array)[0];

                        // Draw / redraw the timeline for a given x-range.
                        // xMinFr / xMaxFr are FR dd-mm-yyyy strings (or '' = full range).
                        // The last range used for this plot is remembered in
                        // metaLastRange so the global resize handler can redraw it.
                        function drawMetaTimeline(xMinFr, xMaxFr) {
                            // Delegate to the shared renderer (also used by the
                            // full-screen view) so both stay perfectly in sync.
                            drawMetaTimelineCore(metaDiv, graphDiv, plot_key,
                                                 cle_station, typedataChron, xMinFr, xMaxFr);
                        }
                        // Expose this plot's draw function so the global resize
                        // handler can call it back with the remembered range.
                        metaDrawFns[plot_key] = drawMetaTimeline;

                        // Initial draw on the current visible range.
                        drawMetaTimeline(min_date, max_date);

                        // Seed the zoom history with this graph's full range
                        // (ISO bounds Plotly resolved), so 'previous zoom'
                        // can always return to the initial full view.
                        (function() {
                            var fx = graphDiv._fullLayout && graphDiv._fullLayout.xaxis;
                            if (fx && fx.range) {
                                zoomHistoryInit(plot_key, fx.range[0], fx.range[1]);
                            }
                        })();

                        // Auto-sync: when the chart's x-axis changes (mouse zoom,
                        // pan, double-click reset...), redraw the timeline on the
                        // new range. metaRedrawTimer debounces rapid relayouts.
                        var metaRedrawTimer = null;
                        graphDiv.on('plotly_relayout', function(ev) {
                            var x1 = null, x2 = null;
                            if (ev['xaxis.range[0]'] !== undefined && ev['xaxis.range[1]'] !== undefined) {
                                x1 = ev['xaxis.range[0]'];
                                x2 = ev['xaxis.range[1]'];
                                // Feed the zoom history with the new ISO range.
                                zoomHistoryOnRelayout(plot_key, x1, x2);
                            } else if (ev['xaxis.autorange'] || ev['autosize']) {
                                // Reset to full range -> let the server use the data bounds.
                                // Record the resolved full range so 'back' can return here.
                                var fx = graphDiv._fullLayout && graphDiv._fullLayout.xaxis;
                                if (fx && fx.range) {
                                    zoomHistoryOnRelayout(plot_key, fx.range[0], fx.range[1]);
                                }
                                x1 = ''; x2 = '';
                            } else {
                                return; // not an x-axis change we care about
                            }

                            // Convert Plotly 'yyyy-mm-dd hh:mm:ss' range bounds to
                            // the FR dd-mm-yyyy the endpoint expects (isoToFr).
                            var xMinFr = (x1 === '' ? '' : isoToFr(x1));
                            var xMaxFr = (x2 === '' ? '' : isoToFr(x2));

                            clearTimeout(metaRedrawTimer);
                            metaRedrawTimer = setTimeout(function() {
                                drawMetaTimeline(xMinFr, xMaxFr);
                            }, 150);
                        });
                    })();

                    min_y = parseInt(jsonResponse['min_y']);
                    max_y = parseInt(jsonResponse['max_y']);
                    document.getElementById('y1Zoom').value = min_y;
                    document.getElementById('y2Zoom').value = max_y;

                    document.getElementById('ajustCoord').removeEventListener('click', ajustCoord);
                    document.getElementById('ajustCoord').addEventListener('click', ajustCoord);

                    document.getElementById('zoomPeriode').removeEventListener('click', zoomPeriode);
                    document.getElementById('zoomPeriode').addEventListener('click', zoomPeriode);

                    document.getElementById('zoomPeriode_previous').removeEventListener('click', zoomPeriode_previous);
                    document.getElementById('zoomPeriode_previous').addEventListener('click', zoomPeriode_previous);

                    document.getElementById('zoomPeriode_next').removeEventListener('click', zoomPeriode_next);
                    document.getElementById('zoomPeriode_next').addEventListener('click', zoomPeriode_next);

                    text_button_tab = jsonResponse['text_button_tab'];
                    document.getElementById('button_tab_'+plot_key).insertAdjacentHTML('beforeend', text_button_tab);

                    text_button_calcul = jsonResponse['text_button_calcul'];
                    document.getElementById('button_calcul_'+plot_key).insertAdjacentHTML('beforeend', text_button_calcul);

                    text_lacunes = jsonResponse['text_lacunes'];
                    if(document.getElementById('button_lacune_'+plot_key)) 
                    {
                        ecoute_lacune(plot_key, text_lacunes);
                    }

                    nbYears = jsonResponse['n_years'];
                    timePeriod = document.getElementById('timePeriod');
                    if(nbYears < 6 && timePeriod) {
                        timePeriod.style.display = 'none';
                    }
                } else {
                    document.getElementById('axeChange_'+plot_key).style.display = 'none';
                    var msg_noLoad = jsonResponse['msg_noLoad'];
                    boxmsgNoLoad = document.getElementById('msg_'+plot_key);
                    boxmsgNoLoad.style.display = 'block';
                    boxmsgNoLoad.innerHTML = msg_noLoad;

                    temp_date = max_date;
                    max_date = min_date;
                    min_date = temp_date;
                }

                document.getElementById('x1Zoom').value = min_date;
                document.getElementById('x2Zoom').value = max_date;
            }
        };
        xhr.send(jsonDataGraph);
    }

    function loadGraphPacket(plot_key, cle_station, type_station, typedata_array, date_start, date_end)
    {
        min_x = date_start;
        max_x = date_end;
        min_date = date_start;
        max_date = date_end;

        document.getElementById('x1Zoom').value = date_start;
        document.getElementById('x2Zoom').value = date_end;

        document.getElementById('msg_'  + plot_key).style.display = 'none';
        document.getElementById('wait_' + plot_key).style.display = 'block';

        load_graph(plot_key, cle_station, type_station, typedata_array);
    }


    // Gaps display management.
    // Note: js_shapesLacunesData is now indexed by plot_key (composite
    // station_id + chronicle_id) since several graphs can share the same
    // station. The variable used to be called id_station when there was
    // only one graph per station.
    checkLac.addEventListener('change', function() {
        checkLac = this.checked;
        for(var plot_key in js_shapesLacunesData) {
            var shapes = checkLac ? js_shapesLacunesData[plot_key] : [];
            Plotly.relayout('plot_' + plot_key, {'shapes': shapes});
        }
    });

    // Function to toggle trace visibility
    function visibleTrace(key, numTrace) {
        idCheckStat = document.getElementById('checkStat_'+key+'_'+numTrace);
        checkStat = idCheckStat.checked;

        for(var plot_key in js_traceStation) {
            waitGif = document.getElementById('wait_'+plot_key);
            nbTrace = js_traceStation[plot_key];
            plotDiv = document.getElementById('plot_'+plot_key);

            if(plotDiv) {
                plotDiv.style.display = 'none';
                waitGif.style.display = 'block';

                plotDiv.once('plotly_afterplot', function() {
                    waitGif.style.display = 'none';
                    plotDiv.style.display = 'block';
                });

                setTimeout(function() {
                    Plotly.restyle(plotDiv, { 'visible': checkStat }, [numTrace]);
                }, 10);
            }
        }
    }


    // =========================================================================
    // CURVE PARAMETERS - Color picker (grid) + line width control
    // =========================================================================

    /**
     * Toggles the color grid open/closed.
     * Closes any other open grid first (only one at a time).
     *
     * Because the grid is `position:fixed` (to escape the sidebar's
     * `overflow:auto`), its top/left are computed dynamically from the
     * swatch position via getBoundingClientRect.
     */
    function toggleDropdownColor(index) {
        let dropdown = document.getElementById('dropdownList_' + index);
        if (!dropdown) return;

        // Close all other open grids
        document.querySelectorAll('.color-grid.is-open').forEach(function(g) {
            if (g !== dropdown) g.classList.remove('is-open');
        });

        var isOpen = dropdown.classList.contains('is-open');

        if (isOpen) {
            dropdown.classList.remove('is-open');
            return;
        }

        // Compute fixed position from the swatch's screen coordinates
        var swatch = document.getElementById('selectedColor_' + index);
        if (swatch) {
            var rect = swatch.getBoundingClientRect();
            var gridWidth = 192;
            var gridHeight = 230; // approx 7 rows x 28px + padding
            var margin = 4;

            // Default position: below the swatch, aligned left
            var top  = rect.bottom + margin;
            var left = rect.left;

            // If the grid would overflow the bottom of the viewport, flip above
            if (top + gridHeight > window.innerHeight) {
                top = Math.max(margin, rect.top - gridHeight - margin);
            }

            // If the grid would overflow the right of the viewport, shift left
            if (left + gridWidth > window.innerWidth) {
                left = Math.max(margin, window.innerWidth - gridWidth - margin);
            }

            dropdown.style.top  = top  + 'px';
            dropdown.style.left = left + 'px';
        }

        dropdown.classList.add('is-open');
    }


    /**
     * Applies the chosen color to all matching traces on every plot.
     * Updates the visible swatch + the hidden input + the "is-selected"
     * highlight inside the grid.
     */
    function selectColor(color, index_tdc) {
        // Update the swatch
        document.getElementById('selectedColor_' + index_tdc).style.backgroundColor = color;

        // Close the grid
        document.getElementById('dropdownList_' + index_tdc).classList.remove('is-open');

        // Update hidden input
        document.getElementById('input_color_' + index_tdc).value = color;

        // Update the "is-selected" highlight inside the grid
        var grid = document.getElementById('dropdownList_' + index_tdc);
        if (grid) {
            grid.querySelectorAll('.color-cell').forEach(function(cell) {
                var cellColor = rgbToHex(cell.style.backgroundColor);
                if (cellColor && cellColor.toLowerCase() === color.toLowerCase()) {
                    cell.classList.add('is-selected');
                } else {
                    cell.classList.remove('is-selected');
                }
            });
        }

        // Apply the color to all matching traces on every plot
        document.querySelectorAll("[id^='plot_']").forEach(function(plotDiv) {
            const data = plotDiv.data || [];
            const idxs = [];
            for (let i = 0; i < data.length; i++) {
                const tr = data[i] || {};
                const lg = tr.legendgroup;

                if (lg === ('tdc_' + index_tdc)) {
                    idxs.push(i);
                }
            }

            if (idxs.length) {
                Plotly.restyle(plotDiv, {
                    'marker.color': color,
                    'marker.line.color': color,
                    'line.color': color
                }, idxs);
            }
        });
    }


    /**
     * Adjusts line width and updates the visible value display.
     */
    function bumpLineWidth(index_tdc, delta) {
        let appliedWidth = null;

        document.querySelectorAll("[id^='plot_']").forEach(function(plotDiv) {
            const data = plotDiv.data || [];
            const forLines = [], lineWidths = [];
            const forBars  = [], barWidths  = [];

            for (let i = 0; i < data.length; i++) {
                const tr = data[i] || {};
                const lg = tr.legendgroup;

                if (lg === ('tdc_' + index_tdc)) {
                    if ((tr.type === 'scatter' || tr.type === 'scattergl') && (tr.mode || '').includes('lines')) {
                        const current = (tr.line && typeof tr.line.width === 'number') ? tr.line.width : 2;
                        const next = Math.max(0.1, +(current + delta).toFixed(2));
                        forLines.push(i);
                        lineWidths.push(next);
                        appliedWidth = next;
                    } else if (tr.type === 'bar') {
                        const current = (tr.marker && tr.marker.line && typeof tr.marker.line.width === 'number') ? tr.marker.line.width : 0;
                        const next = Math.max(0.1, +(current + delta).toFixed(2));
                        forBars.push(i);
                        barWidths.push(next);
                        appliedWidth = next;
                    }
                }
            }

            if (forLines.length) {
                Plotly.restyle(plotDiv, { 'line.width': lineWidths }, forLines);
            }
            if (forBars.length) {
                Plotly.restyle(plotDiv, { 'marker.line.width': barWidths }, forBars);
            }
        });
    }


    /**
     * Helper: converts "rgb(r, g, b)" to "#rrggbb".
     * Browsers normalize hex colors written in style attributes to rgb().
     */
    function rgbToHex(rgb) {
        if (!rgb) return '';
        if (rgb[0] === '#') return rgb;
        var match = rgb.match(/^rgba?\((\d+),\s*(\d+),\s*(\d+)/);
        if (!match) return rgb;
        return '#' + ((1 << 24) + (parseInt(match[1]) << 16) + (parseInt(match[2]) << 8) + parseInt(match[3]))
                    .toString(16).slice(1);
    }


    // Close any open color grid when clicking outside of a color picker
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.color-dropdown')) {
            document.querySelectorAll('.color-grid.is-open').forEach(function(g) {
                g.classList.remove('is-open');
            });
        }
    });


    // Function to synchronize x-axes of displayed graphs
    function ajustCoord() {
        x1_value = document.getElementById('x1Zoom').value;
        x2_value = document.getElementById('x2Zoom').value;

        if(!isValidDatesInput(x1_value, x2_value)) {
            msgInfo.style.border = '2px solid #930000';
            return;
        }

        y1 = document.getElementById('y1Zoom').value;
        y2 = document.getElementById('y2Zoom').value;

        if(!isNumber(y1) || !isNumber(y2)) {
            msgInfo.style.border = '2px solid #930000';
            return;
        }

        x1_format = new Date(x1_value.split('-').reverse().join('-'));
        x2_format = new Date(x2_value.split('-').reverse().join('-'));

        eval(js_syncAbsc_var);
        eval(js_syncOrdon_var);
    }

    function zoomCTRL(idStation) {
        var checkZoomX = document.getElementById('check_zoom_x_'+idStation);
        var checkZoomY = document.getElementById('check_zoom_y_'+idStation);

        var fixedRangeX = true;
        var fixedRangeY = true;

        if(checkZoomX.checked && checkZoomY.checked) {
            fixedRangeX = false;
            fixedRangeY = false;
        } else if(checkZoomX.checked) {
            fixedRangeX = false;
            fixedRangeY = true;
        } else if(checkZoomY.checked) {
            fixedRangeX = true;
            fixedRangeY = false;
        }

        Plotly.relayout('plot_' + idStation, {
            'xaxis.fixedrange': fixedRangeX,
            'yaxis.fixedrange': fixedRangeY
        });
    }

    // Function to synchronize graphs on specific month or year for comparison
    function zoomPeriode() {
        year_zoom = document.getElementById('select_year_zoom').value;
        month_zoom = document.getElementById('select_month_zoom').value;

        if(year_zoom > 0) {
            if(month_zoom > 0) {
                numberOfDays = getDaysInMonth(month_zoom, year_zoom);

                if(month_zoom < 10) {
                    month = '0'+month_zoom;
                } else {
                    month = month_zoom;
                }

                x1_format = year_zoom+'-'+month+'-01';
                x2_format = year_zoom+'-'+month+'-'+numberOfDays;
            } else {
                x1_format = year_zoom+'-01-01';
                x2_format = year_zoom+'-12-31';
            }

            x1_format_value = x1_format.split(' ')[0].split('-').reverse().join('-');
            x2_format_value = x2_format.split(' ')[0].split('-').reverse().join('-');

            document.getElementById('x1Zoom').value = x1_format_value;
            document.getElementById('x2Zoom').value = x2_format_value;

            eval(js_syncAbsc_var);
        }
    }

    // Function to move zoom to previous period
    function zoomPeriode_previous() {
        year_zoom = document.getElementById('select_year_zoom').value;
        month_zoom = document.getElementById('select_month_zoom').value;

        if(year_zoom > 0) {
            if(month_zoom > 0) {
                month = parseInt(month_zoom)-1;
                if(month == 0) {
                    month = 12;
                    year = parseInt(year_zoom)-1;
                } else {
                    year = year_zoom;
                }
                nbDayMonth = getDaysInMonth(month, year);

                if(month < 10) {
                    month_string = '0'+month;
                } else {
                    month_string = month;
                }

                if(nbDayMonth < 10) {
                    nbDayMonth_string = '0'+nbDayMonth;
                } else {
                    nbDayMonth_string = nbDayMonth;
                }

                x1_format = year+'-'+month_string+'-01';
                x2_format = year+'-'+month_string+'-'+nbDayMonth_string;
            } else {
                year = parseInt(year_zoom)-1;
                month = month_zoom;

                x1_format = year+'-01-01';
                x2_format = year+'-12-31';
            }

            x1_format_value = x1_format.split(' ')[0].split('-').reverse().join('-');
            x2_format_value = x2_format.split(' ')[0].split('-').reverse().join('-');

            document.getElementById('x1Zoom').value = x1_format_value;
            document.getElementById('x2Zoom').value = x2_format_value;

            document.getElementById('select_year_zoom').value = year;
            document.getElementById('select_month_zoom').value = month;

            eval(js_syncAbsc_var);
        }
    }

    // Function to move zoom to next period
    function zoomPeriode_next() {
        year_zoom = document.getElementById('select_year_zoom').value;
        month_zoom = document.getElementById('select_month_zoom').value;

        if(year_zoom > 0) {
            if(month_zoom > 0) {
                month = parseInt(month_zoom)+1;
                if(month > 12) {
                    month = 1;
                    year = parseInt(year_zoom)+1;
                } else {
                    year = year_zoom;
                }
                nbDayMonth = getDaysInMonth(month, year);

                if(month < 10) {
                    month_string = '0'+month;
                } else {
                    month_string = month;
                }

                if(nbDayMonth < 10) {
                    nbDayMonth_string = '0'+nbDayMonth;
                } else {
                    nbDayMonth_string = nbDayMonth;
                }

                x1_format = year+'-'+month_string+'-01';
                x2_format = year+'-'+month_string+'-'+nbDayMonth_string;
            } else {
                year = parseInt(year_zoom)+1;
                month = month_zoom;

                x1_format = year+'-01-01';
                x2_format = year+'-12-31';
            }

            x1_format_value = x1_format.split(' ')[0].split('-').reverse().join('-');
            x2_format_value = x2_format.split(' ')[0].split('-').reverse().join('-');

            document.getElementById('x1Zoom').value = x1_format_value;
            document.getElementById('x2Zoom').value = x2_format_value;

            document.getElementById('select_year_zoom').value = year;
            document.getElementById('select_month_zoom').value = month;

            eval(js_syncAbsc_var);
        }
    }

    // Function to create event listener for gaps button to open popup
    // -----------------------------------------------
    // Generic modal helpers (qualification / gaps popups)
    // A single overlay element is reused; content is rebuilt per call.
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
            // Close on overlay click / X / Escape.
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

    // Build and trigger a CSV download from an array of arrays (rows).
    // Values are quoted; ; separator + UTF-8 BOM for Excel (FR locale).
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

    // -----------------------------------------------
    // Data qualification popup (data_meta blocks) + CSV export.
    function hpOpenQualif(plot_key) {
        var parts          = String(plot_key).split('_');
        var idStation      = parseInt(parts[0], 10);
        var typedataChron  = parts[1];

        // Use the plot's current visible x-range if we tracked it, else full.
        var r = (typeof metaLastRange !== 'undefined' && metaLastRange[plot_key])
              ? metaLastRange[plot_key] : { min: '', max: '' };

        hpOpenModal('<?php echo addslashes(TEXT_DATA_QUALIF); ?>', '');
        document.getElementById('hp_modal_body').innerHTML =
            "<div class='hp-empty'><?php echo addslashes(TEXT_LOADING); ?></div>";

        var reqParams = {
            idStation:     idStation,
            typedataChron: typedataChron,
            xDateMin:      r.min || '',
            xDateMax:      r.max || ''
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
                resp._req = reqParams;   // keep params for the PDF export
                hpRenderQualif(resp);
            }
        };
        xhr.send(JSON.stringify(reqParams));
    }

    function hpRenderQualif(resp) {
        document.getElementById('hp_modal_sub').textContent =
            (resp.station || '') + (resp.chronique ? '  ·  ' + resp.chronique : '');

        var rows = resp.rows || [];

        var H = {
            qc:    '<?php echo addslashes(TEXT_GRAPH_HOVER_QUALCODE); ?>',
            start: '<?php echo addslashes(TEXT_GRAPH_META_START); ?>',
            end:   '<?php echo addslashes(TEXT_GRAPH_META_END); ?>',
            nb:    '<?php echo addslashes(TEXT_GRAPH_META_NBPTS); ?>',
            corr:  '<?php echo addslashes(TEXT_GRAPH_HOVER_CORRECTION); ?>',
            comm:  '<?php echo addslashes(TEXT_GRAPH_HOVER_CORRECTION_OBS); ?>'
        };

        // Toolbar with CSV + PDF export.
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
            body.innerHTML = "<div class='hp-empty'>—</div>";
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

    function hpEsc(s) {
        if (s === null || s === undefined) { return ''; }
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    // Generate the qualification PDF server-side (mPDF) then download it.
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
                    // Download the generated file from the PDF output directory.
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

    // Filename-safe slug: strip accents, keep [A-Za-z0-9], collapse the rest to nothing.
    function hpSlug(s) {
        if (s === null || s === undefined) { return ''; }
        return String(s)
            .normalize('NFD').replace(/[\u0300-\u036f]/g, '') // drop diacritics
            .replace(/[^A-Za-z0-9]+/g, '')                    // keep alphanumerics only
            ;
    }

    // -----------------------------------------------
    // Data gaps popup (lacunes) + CSV / PDF export.
    function hpOpenGaps(plot_key) {
        var parts         = String(plot_key).split('_');
        var idStation     = parseInt(parts[0], 10);
        var typedataChron = parts[1];

        var r = (typeof metaLastRange !== 'undefined' && metaLastRange[plot_key])
              ? metaLastRange[plot_key] : { min: '', max: '' };

        hpOpenModal('<?php echo addslashes(TEXT_GAPS); ?>', '');
        document.getElementById('hp_modal_body').innerHTML =
            "<div class='hp-empty'><?php echo addslashes(TEXT_LOADING); ?></div>";

        var reqParams = {
            idStation:     idStation,
            typedataChron: typedataChron,
            xDateMin:      r.min || '',
            xDateMax:      r.max || ''
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
            (resp.station || '') + (resp.chronique ? '  ·  ' + resp.chronique : '');

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
            body.innerHTML = "<div class='hp-empty'>—</div>";
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
    // Export the chart's VISIBLE data (current x-range, visible traces) to CSV.
    // Reuses the logic that used to live in the Plotly modebar CSV button.
    function hpExportChartCsv(plot_key) {
        var gd = document.getElementById('plot_' + plot_key);
        if (!gd || !gd.data) { return; }

        var visibleTraces = gd.data.filter(function(trace) {
            return trace.visible !== 'legendonly' && trace.visible !== false;
        });
        if (visibleTraces.length === 0) { return; }

        var data = visibleTraces;
        var sep  = ';';
        var csvContent = '';

        // Master X = union of all traces' X values.
        var allUniqueX = new Set();
        data.forEach(function(trace) {
            (trace.x || []).forEach(function(xVal) { allUniqueX.add(xVal); });
        });
        var masterX = Array.from(allUniqueX).sort();

        // Clip to the currently visible x-range.
        var xRange = gd.layout && gd.layout.xaxis ? gd.layout.xaxis.range : null;
        if (xRange && xRange.length === 2) {
            var minX = xRange[0], maxX = xRange[1];
            masterX = masterX.filter(function(xVal) {
                var nx  = (typeof xVal === 'number' || !isNaN(new Date(xVal).getTime())) ? (new Date(xVal).getTime() || xVal) : xVal;
                var nmn = (typeof minX === 'number' || !isNaN(new Date(minX).getTime())) ? (new Date(minX).getTime() || minX) : minX;
                var nmx = (typeof maxX === 'number' || !isNaN(new Date(maxX).getTime())) ? (new Date(maxX).getTime() || maxX) : maxX;
                return nx >= nmn && nx <= nmx;
            });
        }
        if (masterX.length === 0) { return; }

        var lookupMaps = data.map(function(trace) {
            var map = new Map();
            for (var i = 0; i < trace.x.length; i++) {
                map.set(trace.x[i], {
                    y:    trace.y[i],
                    text: (trace.text && trace.text[i] !== undefined) ? trace.text[i] : ''
                });
            }
            return map;
        });

        var header = ['X'];
        data.forEach(function(trace, index) {
            var seriesName = trace.name || ('Trace ' + (index + 1));
            header.push('Y (' + seriesName + ')');
            header.push('CodeQual (' + seriesName + ')');
        });
        csvContent += header.join(sep) + '\r\n';

        masterX.forEach(function(xVal) {
            var row = [String(xVal)];
            lookupMaps.forEach(function(map) {
                var point = map.get(xVal);
                if (point) { row.push(String(point.y)); row.push(point.text); }
                else       { row.push(''); row.push(''); }
            });
            csvContent += row.join(sep) + '\r\n';
        });

        var blob = new Blob(['\uFEFF' + csvContent], { type: 'text/csv;charset=utf-8;' });
        var url  = URL.createObjectURL(blob);
        var link = document.createElement('a');
        link.setAttribute('href', url);
        // Filename: code_nom_init_chart.csv (falls back to HP-Data if meta absent).
        var meta  = (window.hpChartMeta && window.hpChartMeta[plot_key]) ? window.hpChartMeta[plot_key] : null;
        var fname = meta
            ? (hpSlug(meta.code) + '_' + hpSlug(meta.nom) + '_' + hpSlug(meta.init) + '_chart.csv')
            : 'HP-Data.csv';
        link.setAttribute('download', fname);
        document.body.appendChild(link); link.click();
        document.body.removeChild(link);
        setTimeout(function() { URL.revokeObjectURL(url); }, 1000);
    }

    // -----------------------------------------------
    // Hide the inline graph headers while the full-screen overlay (#box_graph)
    // is open, and restore them once it is closed again.
    var hpFsObserverWired = false;
    function hpHideHeadersForFullscreen() {
        document.querySelectorAll('.graph-header').forEach(function(h) {
            h.dataset.hpPrevDisplay = h.style.display || '';
            h.style.display = 'none';
        });
        var box = document.getElementById('box_graph');
        if (box && !hpFsObserverWired) {
            hpFsObserverWired = true;
            var obs = new MutationObserver(function() {
                // When the overlay is hidden again, restore the headers.
                if (box.style.display === 'none' || box.style.display === '') {
                    document.querySelectorAll('.graph-header').forEach(function(h) {
                        h.style.display = h.dataset.hpPrevDisplay || '';
                    });
                }
            });
            obs.observe(box, { attributes: true, attributeFilter: ['style'] });
        }
    }

    // -----------------------------------------------
    // Header "Tools" dropdown menu (Option A bandeau)
    // Opens/closes the per-plot menu and routes each entry to the existing
    // (now hidden) buttons so their wiring is untouched.
    function hpToggleMenu(plot_key) {
        var list = document.getElementById('hp_menu_list_' + plot_key);
        if (!list) { return; }
        var isOpen = list.classList.contains('open');
        // Close any other open menu first.
        document.querySelectorAll('.hp-menu-list.open').forEach(function(l){ l.classList.remove('open'); });
        if (!isOpen) { list.classList.add('open'); }
    }

    function hpMenuAction(plot_key, action) {
        // Close the menu after a choice.
        var list = document.getElementById('hp_menu_list_' + plot_key);
        if (list) { list.classList.remove('open'); }

        if (action === 'edit') {
            // Submit the hidden correction form: it POSTs the visible range to
            // data_chron.php (new tab) to open the time-series editor.
            // IMPORTANT: we click the submit button itself (not form.submit()
            // nor requestSubmit() without arg) so that the button's
            // name=button_calcul value is included in the POST — data_chron.php
            // routes to the editor only when $_POST['button_calcul'] is present.
            var editForm = document.getElementById('calcul_chron_' + plot_key);
            if (editForm) {
                var submitBtn = editForm.querySelector("input[type='submit'][name='button_calcul'], button[name='button_calcul']");
                if (submitBtn) {
                    submitBtn.click(); // includes button_calcul + triggers onsubmit
                } else if (typeof editForm.requestSubmit === 'function') {
                    editForm.requestSubmit();
                } else {
                    editForm.submit();
                }
            }
        }
        else if (action === 'stats') {
            // The stats button HTML is injected into #button_stats_<plot_key>;
            // its first clickable child carries the original handler.
            var sBox = document.getElementById('button_stats_' + plot_key);
            var sBtn = sBox ? (sBox.querySelector('[onclick], button, a, div') || sBox) : null;
            if (sBtn) { sBtn.click(); }
        }
        else if (action === 'gaps') {
            // New gaps popup (modern modal + CSV/PDF), replacing the legacy
            // box_lacunes_info popup.
            hpOpenGaps(plot_key);
        }
        else if (action === 'qualif') {
            // Wired in a later step (data_meta qualification popup + CSV).
            if (typeof hpOpenQualif === 'function') { hpOpenQualif(plot_key); }
        }
        else if (action === 'export_csv') {
            // Wired in a later step (chart data CSV export).
            if (typeof hpExportChartCsv === 'function') { hpExportChartCsv(plot_key); }
        }
    }

    // Close the menu when clicking outside of it.
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.hp-menu')) {
            document.querySelectorAll('.hp-menu-list.open').forEach(function(l){ l.classList.remove('open'); });
        }
    });

    function ecoute_lacune(id_station, text_lacunes) {
        var bouttonLacunes = document.getElementById('button_lacune_'+id_station);
        
        bouttonLacunes.addEventListener('click', function() {
            document.getElementById('cadre_tab_lacune').innerHTML = text_lacunes;
            document.getElementById('box_lacunes_info').style.display = 'block';            
            initDraggable("title_box_lac", "box_lacunes_info"); 
        });
    }

    // Launch graph loading
    <?php echo $load_graph_function; ?>

    function zoom_graph(id_plot, code_station, nom_station, type_data)
    {
        // id_plot is the composite key plot_key (e.g. "245_38") that suffixes
        // the source graph div. We look it up via getElementById rather than
        // window[id] because composite keys with underscores are not always
        // exposed as window properties on every browser, and getElementById
        // is the safe, intentional API for this.
        document.getElementById('box_graph').style.display = 'block';
        document.getElementById('titre_graph').innerHTML = code_station + ' - ' + nom_station + ' - ' + type_data;
        idPlotZoom = id_plot;

        // Hide the inline graph headers (Full screen / Tools bandeau) while the
        // full-screen overlay is open, so they don't float over it. They are
        // restored when #box_graph is hidden again (Close / Escape), watched
        // via a one-time MutationObserver on its style attribute.
        hpHideHeadersForFullscreen();

        var sourceDiv  = document.getElementById('plot_' + id_plot);
        if(!sourceDiv) { return; }
        var plotData   = sourceDiv.data;
        var plotLayout = sourceDiv.layout;

        Plotly.newPlot('cadre_limit', plotData, plotLayout, config);
        addLogScaleButton('cadre_limit', 'log-button_gd_1', 'yaxis');
        if (typeof adjustBarWidths === 'function') { adjustBarWidths('cadre_limit'); }

        // Wire Shift+click navigation on the enlarged graph too. The
        // fieldVisit trace was copied as-is from the source plot (its
        // meta='fieldVisit' tag and customdata[id_ra, id_eq_type] are
        // preserved), so we just need to attach the same listener as
        // on the original graphs (which lives in process_graph_multi.php).
        //
        // id_plot follows the convention "<id_station>_<typedata>" (see
        // load_graph signature) so the first underscore-separated part
        // gives us the station id to plug into the URL.
        var enlargedDiv  = document.getElementById('cadre_limit');
        var idStationFor = parseInt(String(id_plot).split('_')[0], 10);

        // ---- Full-screen quality-code timeline ----
        // Mirror the inline timeline under the enlarged chart. id_plot is
        // '<idStation>_<typedataChron>', so part [1] is the chronicle id.
        // We use a dedicated DOM key '<id_plot>_fs' so its container id and
        // resize/redraw state never collide with the inline timeline.
        (function() {
            var typedataChronFs = String(id_plot).split('_')[1];
            var fsKey = id_plot + '_fs';

            // Hide any previously created full-screen timeline (from another
            // graph opened earlier) so only the current one shows.
            document.querySelectorAll("[id^='plot_meta_'][id$='_fs']").forEach(function(el) {
                el.style.display = 'none';
            });

            // Create (once) a meta container right after #cadre_limit.
            var metaFs = document.getElementById('plot_meta_' + fsKey);
            if (!metaFs) {
                metaFs = document.createElement('div');
                metaFs.id = 'plot_meta_' + fsKey;
                metaFs.style.width  = '100%';
                metaFs.style.margin = '15px 0 0 0';
                enlargedDiv.parentNode.insertBefore(metaFs, enlargedDiv.nextSibling);
            }
            metaFs.style.display = 'block';

            // Initial draw on the enlarged chart's current x-range.
            function fsRange() {
                var r = { min: '', max: '' };
                try {
                    var fx = enlargedDiv._fullLayout && enlargedDiv._fullLayout.xaxis;
                    if (fx && Array.isArray(fx.range)) {
                        r.min = isoToFr(fx.range[0]);
                        r.max = isoToFr(fx.range[1]);
                    }
                } catch (e) {}
                return r;
            }
            var r0 = fsRange();
            // Register a redraw fn for the global window-resize handler too,
            // so the full-screen timeline restays aligned on resize.
            metaDrawFns[fsKey] = function(xMinFr, xMaxFr) {
                if (metaFs.style.display === 'none') { return; }
                drawMetaTimelineCore(metaFs, enlargedDiv, fsKey,
                                     idStationFor, typedataChronFs, xMinFr, xMaxFr);
            };
            // Defer slightly so Plotly has finished laying out #cadre_limit
            // (so _fullLayout.xaxis offsets are available for alignment).
            setTimeout(function() {
                drawMetaTimelineCore(metaFs, enlargedDiv, fsKey,
                                     idStationFor, typedataChronFs, r0.min, r0.max);
            }, 60);

            // Keep it x-synced with the enlarged chart on zoom/pan/reset.
            var fsTimer = null;
            enlargedDiv.on('plotly_relayout', function(ev) {
                var x1 = null, x2 = null;
                if (ev['xaxis.range[0]'] !== undefined && ev['xaxis.range[1]'] !== undefined) {
                    x1 = ev['xaxis.range[0]']; x2 = ev['xaxis.range[1]'];
                } else if (ev['xaxis.autorange'] || ev['autosize']) {
                    x1 = ''; x2 = '';
                } else { return; }
                var xMinFr = (x1 === '' ? '' : isoToFr(x1));
                var xMaxFr = (x2 === '' ? '' : isoToFr(x2));
                clearTimeout(fsTimer);
                fsTimer = setTimeout(function() {
                    drawMetaTimelineCore(metaFs, enlargedDiv, fsKey,
                                         idStationFor, typedataChronFs, xMinFr, xMaxFr);
                }, 150);
            });
        })();
        if (enlargedDiv && enlargedDiv.on) {
            enlargedDiv.on('plotly_click', function(eventData) {
                if (!eventData || !eventData.points || !eventData.points.length) { return; }
                var pt    = eventData.points[0];
                var trace = pt.data;
                if (!trace || trace.meta !== 'fieldVisit') { return; }

                var orig = eventData.event;

                var cd = pt.customdata;
                if (!cd || cd.length < 4) { return; }
                var idRa = parseInt(cd[2], 10);
                var td   = parseInt(cd[3], 10);
                if (!idRa || idRa <= 0) { return; }
                if (!td   || td   <= 0) { return; }

                // Vertical guard: only open when the click is near the square in
                // Y (hovermode 'x unified' otherwise matches the whole X column).
                // Use the point's OWN y axis (pt.yaxis) and its plot-area offset
                // so the marker's pixel position is correct on any axis.
                try {
                    var yAx = pt.yaxis || (enlargedDiv._fullLayout && enlargedDiv._fullLayout.yaxis);
                    if (orig && yAx && typeof yAx.d2p === 'function') {
                        var bb = enlargedDiv.getBoundingClientRect();
                        var cursorY = orig.clientY - bb.top;               // cursor in graph px
                        var markerY = (yAx._offset || 0) + yAx.d2p(pt.y);  // marker in graph px
                        if (Math.abs(cursorY - markerY) > 12) { return; }
                    }
                } catch (e) {}

                if (orig && orig.preventDefault)  { orig.preventDefault(); }
                if (orig && orig.stopPropagation) { orig.stopPropagation(); }

                // Open the field sheet in a new tab (setTimeout defers the
                // synthetic click to the next tick for reliable new-tab open).
                var __ra_url = 'list_ra.php?st=' + idStationFor + '&ra=' + idRa + '&td=' + td;
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

        // Tooltip is now rendered natively by Plotly via hovertemplate.
        // No custom hover handler needed here.
    }
    
    /**
     * Resolve which X range should be sent to data_chron.php when the user
     * clicks "Edit or Correct the Series".
     *
     *   - checkbox checked        → full series range (server-rendered min_x / max_x)
     *   - checkbox unchecked + zoomed inputs filled → use x1Zoom / x2Zoom
     *   - checkbox unchecked + zoomed inputs empty  → fallback to full range
     *
     * The function returns true so the form actually submits;
     * it never blocks the submission.
     *
     * @param {string|number} cleStation  Station id, used to scope the checkbox
     * @param {string}        minX        Full-range start date (dd-mm-yyyy)
     * @param {string}        maxX        Full-range end date   (dd-mm-yyyy)
     * @returns {boolean}                 Always true (submit proceeds)
     */
    function prepareCalculDates(cleStation, minX, maxX)
    {
        // Per-form date inputs (suffixed with plot_key to avoid ID
        // collisions when several forms coexist on the page).
        var date1Input = document.getElementById('date_1_' + cleStation)
                      || document.getElementById('date_1');
        var date2Input = document.getElementById('date_2_' + cleStation)
                      || document.getElementById('date_2');

        // Source of truth for the zoom range = the Plotly graph itself.
        //
        // Reading #x1Zoom / #x2Zoom is unreliable in multi-graph mode
        // because load_graph() writes the full series bounds into those
        // shared inputs at the end of every AJAX load, which can race
        // with — or overwrite — the user's zoom right before the click
        // on "Edit chron". Plotly's own gd.layout.xaxis.range, however,
        // belongs only to THIS graph and reflects exactly the visible
        // range. We read it and format it back to dd-mm-yyyy.
        var gd = document.getElementById('plot_' + cleStation);
        if (gd && gd.layout && gd.layout.xaxis && Array.isArray(gd.layout.xaxis.range)) {
            var x1raw = gd.layout.xaxis.range[0];
            var x2raw = gd.layout.xaxis.range[1];

            // Plotly stores dates as ISO strings "yyyy-mm-dd hh:mm:ss"
            // (or just "yyyy-mm-dd"). The receiving page expects
            // dd-mm-yyyy, same format as minX / maxX above.
            date1Input.value = isoToFr(x1raw) || minX;
            date2Input.value = isoToFr(x2raw) || maxX;
            return true;
        }

        // Fallback: shared inputs (used by the date picker in the
        // sidebar) if for some reason the graph div isn't reachable.
        var x1 = document.getElementById('x1Zoom');
        var x2 = document.getElementById('x2Zoom');

        var x1val = (x1 && x1.value) ? x1.value.trim() : '';
        var x2val = (x2 && x2.value) ? x2.value.trim() : '';

        date1Input.value = x1val || minX;
        date2Input.value = x2val || maxX;
        return true;
    }

    /**
     * Convert a Plotly axis range value to the dd-mm-yyyy format the
     * data_chron.php form expects. Accepts ISO strings ("yyyy-mm-dd"
     * or "yyyy-mm-dd hh:mm:ss") and numeric timestamps. Returns an
     * empty string for anything unparseable so the caller falls back
     * to the full-range default.
     */
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

    // Log scale function
    function addLogScaleButton(plotId, logButtonId, axe) {
        const button = document.getElementById(logButtonId);
        const graphContainer = document.getElementById(plotId);
        let logScaleEnabled = false;

        button.addEventListener('click', function() {
            const plotlyLayout = graphContainer._fullLayout;
            if(axe === 'yaxis' || axe === 'yaxis2') {
                const axis = plotlyLayout[axe];
                const newType = logScaleEnabled ? 'linear' : 'log';
                Plotly.relayout(plotId, { [axe + '.type']: newType });
                logScaleEnabled = !logScaleEnabled;
            }
        });
    }

    // Function to add/remove decimals on axis
    var decimalPlaces = 0;

    function updateDecimals(plotId, axe, type) 
    {
        if(type == '+' && decimalPlaces < 6) {
            decimalPlaces++;
        }
        if(type == '-' && decimalPlaces > 0) {
            decimalPlaces--;
        }

        var newTickFormat = '.' + decimalPlaces + 'f';
        Plotly.relayout(plotId, {[axe + '.tickformat']: newTickFormat});
    }

    // Function to get number of days in a month
    function getDaysInMonth(monthNumber, year) {
        return new Date(year, monthNumber, 0).getDate();
    }

    // Statistics display functions
    var dataToSendStats = null;
    var titleBoxStats = document.getElementById('title_box');
    var menuStats = document.getElementById('menu_stats');
    var waitBoxStats = document.getElementById('cadre_wait_stats');
    var generalStats = document.getElementById('general_stats');
    var contenuStats = document.getElementById('contenu_stats');
    var contenuStatsGraph = document.getElementById('contenu_stats_graph');

    function afficheStats(cle_station, type_station, id_typedata) {
        x1Zoom = document.getElementById('x1Zoom').value;
        x2Zoom = document.getElementById('x2Zoom').value;

        boxStats.style.display = 'block';
        waitBoxStats.style.display = 'block';
        contenuStats.style.display = 'none';
        contenuStatsGraph.style.display = 'none';

        dataToSendStats = {
            territoireId: '<?php echo $territoire_id; ?>',
            lang: '<?php echo LANGUAGE;?>',
            id_statut: '<?php echo $id_statut;?>',
            cle_station: cle_station,
            type_station: type_station,
            id_typedata: id_typedata,
            min_x: x1Zoom,
            max_x: x2Zoom
        };

        var xhr = new XMLHttpRequest();
        xhr.open("POST", "include/structure/graph/process_graph_stats.php", true);
        xhr.setRequestHeader("Content-Type", "application/json");

        xhr.onreadystatechange = function() {
            if(xhr.readyState === 4 && xhr.status === 200) {
                var jsonResponse = JSON.parse(xhr.responseText);
                html_stats_title = jsonResponse['html_stats_title'];
                html_stats_menu = jsonResponse['html_stats_menu'];
                html_stats_general = jsonResponse['html_stats_general'];

                // Keep station / chronicle identifiers for export filenames.
                window.hpStatsMeta = {
                    code: jsonResponse['station_code'] || '',
                    nom:  jsonResponse['station_nom']  || '',
                    init: jsonResponse['chron_init']   || ''
                };

                titleBoxStats.innerHTML = html_stats_title;
                menuStats.innerHTML = html_stats_menu;
                generalStats.innerHTML = html_stats_general;

                statsChron('global');
            }
        };
        xhr.send(JSON.stringify(dataToSendStats));
    }

    // -----------------------------------------------
    // Stats views export (CSV / PDF) — annual / monthly / daily
    var hpCurrentStatView = 'global';

    // Build a filename slug base for the current station/chronicle.
    function hpStatsFileBase() {
        var m = (typeof window.hpStatsMeta !== 'undefined') ? window.hpStatsMeta : null;
        if (m) { return hpSlug(m.code) + '_' + hpSlug(m.nom) + '_' + hpSlug(m.init); }
        return 'stats';
    }

    // Map a view key to a translated label (for filenames / PDF titles).
    function hpStatViewLabel(view) {
        switch (view) {
            case 'byyear':  return '<?php echo addslashes(TEXT_STATS_BTN_BYYEAR); ?>';
            case 'bymonth': return '<?php echo addslashes(TEXT_STATS_BTN_BYMONTH); ?>';
            case 'bydays':  return '<?php echo addslashes(TEXT_STATS_BTN_BYDAYS); ?>';
            default:        return '<?php echo addslashes(TEXT_STATS_TITLE); ?>';
        }
    }

    // Show/hide and wire a FIXED export toolbar that sits above both the
    // chart (#contenu_stats_graph) and the table (#contenu_stats), so it
    // covers the whole view. Created once, then just updated per view.
    function hpInjectStatsToolbar(view) {
        var bar = document.getElementById('hp_stats_toolbar');

        // Create it once, inserted right before the graph container.
        if (!bar) {
            var cadre = document.getElementById('cadre_stats');
            var graph = document.getElementById('contenu_stats_graph');
            if (!cadre || !graph) { return; }

            bar = document.createElement('div');
            bar.id = 'hp_stats_toolbar';
            bar.className = 'hp-modal-toolbar';
            bar.style.cssText = 'display:none;gap:8px;padding:4px 0 8px;border:0;background:transparent;';
            bar.innerHTML =
                "<button class='hp-stats-csv'><span>&#11015;</span> CSV</button>"
              + "<button class='hp-stats-pdf'><span>&#128196;</span> PDF</button>"
              + "<button class='hp-stats-xls'><span>&#128202;</span> Excel</button>"
              + "<button class='hp-global-pdf'><span>&#128196;</span> PDF</button>"
              + "<button class='hp-lf-pdf-sum'><span>&#128196;</span> <?php echo addslashes(TEXT_LOWFLOW_PDF_SUMMARY); ?></button>"
              + "<button class='hp-lf-pdf-full'><span>&#128196;</span> <?php echo addslashes(TEXT_LOWFLOW_PDF_FULL); ?></button>"
              + "<button class='hp-stats-help' title='" + "<?php echo addslashes(TEXT_STATS_METHODOLOGY); ?>" + "'><span>&#9432;</span> <?php echo addslashes(TEXT_STATS_METHODOLOGY); ?></button>";
            cadre.insertBefore(bar, graph);

            bar.querySelector('.hp-stats-csv').onclick = function(){ hpStatsCsv(hpCurrentStatView); };
            bar.querySelector('.hp-stats-pdf').onclick = function(){ hpStatsPdf(hpCurrentStatView); };
            bar.querySelector('.hp-stats-xls').onclick = function(){ hpStatsXls(hpCurrentStatView); };
            bar.querySelector('.hp-global-pdf').onclick  = function(){ hpGlobalPdf(); };
            bar.querySelector('.hp-lf-pdf-sum').onclick  = function(){ hpLowflowPdf('summary'); };
            bar.querySelector('.hp-lf-pdf-full').onclick = function(){ hpLowflowPdf('full'); };
            bar.querySelector('.hp-stats-help').onclick = function(){ hpLowflowHelp(); };
        }

        // Export buttons: data views only. Low-flow: 2 PDF buttons + Methodology.
        // General view: a single PDF button.
        var isData   = (view === 'byyear' || view === 'bymonth' || view === 'bydays');
        var isLow    = (view === 'lowflow');
        var isGlobal = (view === 'global');
        bar.style.display = (isData || isLow || isGlobal) ? 'flex' : 'none';

        var bCsv = bar.querySelector('.hp-stats-csv');
        var bPdf = bar.querySelector('.hp-stats-pdf');
        var bXls = bar.querySelector('.hp-stats-xls');
        var bGlob = bar.querySelector('.hp-global-pdf');
        var bSum = bar.querySelector('.hp-lf-pdf-sum');
        var bFull = bar.querySelector('.hp-lf-pdf-full');
        var bHelp = bar.querySelector('.hp-stats-help');
        if (bCsv)  bCsv.style.display  = isData ? 'inline-flex' : 'none';
        if (bXls)  bXls.style.display  = isData ? 'inline-flex' : 'none';
        if (bPdf)  bPdf.style.display  = isData ? 'inline-flex' : 'none';
        if (bGlob) bGlob.style.display = isGlobal ? 'inline-flex' : 'none';
        if (bSum)  bSum.style.display  = isLow  ? 'inline-flex' : 'none';
        if (bFull) bFull.style.display = isLow  ? 'inline-flex' : 'none';
        if (bHelp) bHelp.style.display = isLow  ? 'inline-flex' : 'none';
    }

    // CSV export: dump every data table of the current view. Tables live in
    // #contenu_stats (annual/monthly synthesis) and #contenu_stats_days
    // (daily grid), all under #cadre_stats — so we read the whole zone.
    function hpStatsCsv(view) {
        var host  = document.getElementById('cadre_stats');
        var tables = host ? host.querySelectorAll('table') : [];
        if (!tables.length) { return; }

        var rows = [];
        tables.forEach(function(table) {
            table.querySelectorAll('tr').forEach(function(tr) {
                var cells = [];
                tr.querySelectorAll('th,td').forEach(function(c) {
                    cells.push((c.innerText || c.textContent || '').replace(/\s+/g, ' ').trim());
                });
                if (cells.some(function(x){ return x !== ''; })) { rows.push(cells); }
            });
            rows.push([]); // blank line between tables
        });

        var header = rows.shift() || [];
        var fname  = hpStatsFileBase() + '_' + hpSlug(hpStatViewLabel(view)) + '.csv';
        hpDownloadCsv(fname, header, rows);
    }

    // PDF export: capture the Plotly chart (if any) as PNG, then POST to the
    // server-side mPDF generator together with the request params + view.
    function hpStatsPdf(view) {
        var btn = document.querySelector('#hp_stats_toolbar .hp-stats-pdf');
        // Show a spinner inside the button while the PDF is being generated.
        var btnHtml = btn ? btn.innerHTML : '';
        function setLoading(on) {
            if (!btn) { return; }
            if (on) {
                btn.disabled = true;
                btn.style.opacity = '0.7';
                btn.innerHTML = "<span class='hp-btn-spinner'></span> PDF";
            } else {
                btn.disabled = false;
                btn.style.opacity = '1';
                btn.innerHTML = btnHtml;
            }
        }
        setLoading(true);

        function send(imgData) {
            var payload = Object.assign({}, dataToSendStats, {
                view:     view,
                chartPng: imgData || '',
                yearSelect: (document.getElementById('yearSelect') ? document.getElementById('yearSelect').value : '')
            });
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'include/structure/stats/process_stats_pdf.php', true);
            xhr.setRequestHeader('Content-Type', 'application/json');
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    setLoading(false);
                    if (xhr.status === 200) {
                        var resp; try { resp = JSON.parse(xhr.responseText); } catch(e){ return; }
                        if (resp && resp.status === 'success' && resp.fileName) {
                            var a = document.createElement('a');
                            a.href = '<?php echo DIR_WS_PDF; ?>' + resp.fileName;
                            a.target = '_blank'; a.rel = 'noopener';
                            document.body.appendChild(a); a.click(); document.body.removeChild(a);
                        }
                    }
                }
            };
            xhr.send(JSON.stringify(payload));
        }

        var plot = document.getElementById('plotStats');
        try {
            if (plot && typeof Plotly !== 'undefined' && Plotly.toImage) {
                Plotly.toImage(plot, { format: 'png', width: 900, height: 400 })
                    .then(function(dataUrl){ send(dataUrl); })
                    .catch(function(){ send(''); });
            } else {
                send('');
            }
        } catch (e) {
            send('');
        }
    }

    // Excel export: POST params + view to the server-side PhpSpreadsheet
    // generator, then download the .xlsx.
    function hpStatsXls(view) {
        var btn = document.querySelector('#hp_stats_toolbar .hp-stats-xls');
        var btnHtml = btn ? btn.innerHTML : '';
        function setLoading(on) {
            if (!btn) { return; }
            if (on)  { btn.disabled = true;  btn.style.opacity = '0.7'; btn.innerHTML = "<span class='hp-btn-spinner'></span> Excel"; }
            else     { btn.disabled = false; btn.style.opacity = '1';   btn.innerHTML = btnHtml; }
        }
        setLoading(true);

        var payload = Object.assign({}, dataToSendStats, { view: view });
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'include/structure/stats/process_stats_xls.php', true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                setLoading(false);
                if (xhr.status === 200) {
                    var resp; try { resp = JSON.parse(xhr.responseText); } catch(e){ return; }
                    if (resp && resp.statut && resp.xlsFile) {
                        var a = document.createElement('a');
                        a.href = '<?php echo DIR_WS_PDF; ?>' + resp.xlsFile;
                        a.target = '_blank'; a.rel = 'noopener';
                        document.body.appendChild(a); a.click(); document.body.removeChild(a);
                    }
                }
            }
        };
        xhr.send(JSON.stringify(payload));
    }

    // Low-flow methodology popup: loads the localized theoretical note,
    // renders it with collapsible sections, and offers a PDF download.
    function hpLowflowHelp() {
        hpOpenModal('<?php echo addslashes(TEXT_STATS_METHODOLOGY); ?>', '');
        var body = document.getElementById('hp_modal_body');
        body.innerHTML = "<div style='padding:14px;'><span class='hp-btn-spinner'></span></div>";

        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'include/structure/stats/process_lowflow_help.php', true);
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                var resp; try { resp = JSON.parse(xhr.responseText); } catch(e){ return; }
                document.getElementById('hp_modal_title').textContent = resp.title || '';

                // Toolbar (PDF) + content
                var html = "<div class='hp-modal-toolbar' style='padding:0 0 12px;border:0;background:transparent;'>"
                         + "<button id='hp_lf_help_pdf'><span>&#128196;</span> PDF</button></div>"
                         + "<div id='hp_lf_help_content' class='hp-lf-help'>" + resp.html + "</div>";
                body.innerHTML = html;

                // Make each section collapsible (collapsed by default except first).
                var secs = body.querySelectorAll('.lf-sec');
                secs.forEach(function(sec, idx) {
                    var h = sec.querySelector('.lf-h');
                    var c = sec.querySelector('.lf-body');
                    if (!h || !c) { return; }
                    h.style.cursor = 'pointer';
                    h.style.userSelect = 'none';
                    var open = (idx === 0);
                    function apply(){ c.style.display = open ? 'block' : 'none'; h.setAttribute('data-open', open ? '1':'0'); }
                    apply();
                    h.onclick = function(){ open = !open; apply(); };
                });

                document.getElementById('hp_lf_help_pdf').onclick = function(){
                    var btn = this; var prev = btn.innerHTML;
                    btn.disabled = true; btn.innerHTML = "<span class='hp-btn-spinner'></span> PDF";
                    var x2 = new XMLHttpRequest();
                    x2.open('POST', 'include/structure/stats/process_lowflow_help_pdf.php', true);
                    x2.onreadystatechange = function() {
                        if (x2.readyState === 4) {
                            btn.disabled = false; btn.innerHTML = prev;
                            if (x2.status === 200) {
                                var r; try { r = JSON.parse(x2.responseText); } catch(e){ return; }
                                if (r && r.status === 'success' && r.fileName) {
                                    var a = document.createElement('a');
                                    a.href = '<?php echo DIR_WS_PDF; ?>' + r.fileName;
                                    a.target = '_blank'; a.rel = 'noopener';
                                    document.body.appendChild(a); a.click(); document.body.removeChild(a);
                                }
                            }
                        }
                    };
                    x2.send('{}');
                };
            }
        };
        xhr.send('{}');
    }

    // Low-flow results PDF. Builds a clean, report-style payload:
    //  - clones #contenu_stats, strips Plotly modebars / svg / event handlers,
    //  - replaces each chart div by a [[CHART:id]] marker (kept in place, so the
    //    server re-inserts the captured image exactly where the metric sits),
    //  - captures each chart to PNG (modebar hidden),
    //  - sends { mode, images, tablesHtml } to the server.
    // mode = 'summary' (synthèse) or 'full' (développé).
    function hpLowflowPdf(mode) {
        var sel = (mode === 'full') ? '.hp-lf-pdf-full' : '.hp-lf-pdf-sum';
        var btn = document.querySelector('#hp_stats_toolbar ' + sel);
        var prev = btn ? btn.innerHTML : '';
        if (btn) { btn.disabled = true; btn.innerHTML = "<span class='hp-btn-spinner'></span> PDF"; }
        function restore(){ if (btn) { btn.disabled = false; btn.innerHTML = prev; } }

        var contenu = document.getElementById('contenu_stats');
        var contenuGraph = document.getElementById('contenu_stats_graph');
        if (!contenu) { restore(); return; }

        // Charts live in two containers: plotCDC is in #contenu_stats_graph,
        // the per-metric plots are in #contenu_stats. Capture from both.
        var charts = [];
        [contenuGraph, contenu].forEach(function(host){
            if (!host) { return; }
            host.querySelectorAll("[id='plotCDC'], [id^='plot_']").forEach(function(d){
                if (d && d.id) { charts.push(d); }
            });
        });

        // Capture each chart to PNG, modebar hidden, decent resolution.
        var captures = charts.map(function(div){
            if (typeof Plotly !== 'undefined' && Plotly.toImage) {
                var w = (div.id === 'plotCDC') ? 900 : 760;
                var h = (div.id === 'plotCDC') ? 420 : 340;
                return Plotly.toImage(div, { format:'png', width:w, height:h })
                    .then(function(png){ return { id: div.id, png: png }; })
                    .catch(function(){ return { id: div.id, png: '' }; });
            }
            return Promise.resolve({ id: div.id, png: '' });
        });

        Promise.all(captures).then(function(images){
            // Clone both containers and clean them for print.
            function cleanClone(node) {
                if (!node) { return ''; }
                var c = node.cloneNode(true);
                c.querySelectorAll("[id='plotCDC'], [id^='plot_']").forEach(function(d){
                    var marker = document.createElement('div');
                    marker.textContent = '[[CHART:' + d.id + ']]';
                    d.parentNode.replaceChild(marker, d);
                });
                c.querySelectorAll('.modebar, .modebar-container, .js-plotly-plot, svg, input, label, button, select').forEach(function(el){
                    el.parentNode && el.parentNode.removeChild(el);
                });
                c.querySelectorAll('*').forEach(function(el){
                    for (var i = el.attributes.length - 1; i >= 0; i--) {
                        var n = el.attributes[i].name;
                        if (n.indexOf('on') === 0) { el.removeAttribute(n); }
                    }
                });
                return c.innerHTML;
            }

            // Graph container first (CDC), then the tables/metrics container.
            var tablesHtml = cleanClone(contenuGraph) + cleanClone(contenu);

            var payload = Object.assign({}, dataToSendStats, {
                mode:       (mode === 'full' ? 'full' : 'summary'),
                images:     images,
                tablesHtml: tablesHtml
            });

            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'include/structure/stats/process_lowflow_pdf.php', true);
            xhr.setRequestHeader('Content-Type', 'application/json');
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    restore();
                    if (xhr.status === 200) {
                        var resp; try { resp = JSON.parse(xhr.responseText); } catch(e){ return; }
                        if (resp && resp.status === 'success' && resp.fileName) {
                            var a = document.createElement('a');
                            a.href = '<?php echo DIR_WS_PDF; ?>' + resp.fileName;
                            a.target = '_blank'; a.rel = 'noopener';
                            document.body.appendChild(a); a.click(); document.body.removeChild(a);
                        }
                    }
                }
            };
            xhr.send(JSON.stringify(payload));
        }).catch(function(){ restore(); });
    }

    // General view PDF: General data + (when computed) the
    // "Return periods of extreme maximum events" block (table + CI + Gumbel
    // params + Gumbel chart). Same capture-and-clone mechanism as low-flow.
    function hpGlobalPdf() {
        var btn = document.querySelector('#hp_stats_toolbar .hp-global-pdf');
        var prev = btn ? btn.innerHTML : '';
        if (btn) { btn.disabled = true; btn.innerHTML = "<span class='hp-btn-spinner'></span> PDF"; }
        function restore(){ if (btn) { btn.disabled = false; btn.innerHTML = prev; } }

        var contenu = document.getElementById('contenu_stats');
        var contenuGraph = document.getElementById('contenu_stats_graph');

        // Charts that may exist in the general view: plotStats (top synthesis
        // chart) and plotGumbel (extreme return periods).
        var charts = [];
        [contenuGraph, contenu].forEach(function(host){
            if (!host) { return; }
            host.querySelectorAll("[id='plotStats'], [id='plotGumbel'], [id^='plot_']").forEach(function(d){
                if (d && d.id) { charts.push(d); }
            });
        });

        var captures = charts.map(function(div){
            if (typeof Plotly !== 'undefined' && Plotly.toImage) {
                return Plotly.toImage(div, { format:'png', width:820, height:360 })
                    .then(function(png){ return { id: div.id, png: png }; })
                    .catch(function(){ return { id: div.id, png: '' }; });
            }
            return Promise.resolve({ id: div.id, png: '' });
        });

        Promise.all(captures).then(function(images){
            function cleanClone(node) {
                if (!node) { return ''; }
                var c = node.cloneNode(true);
                c.querySelectorAll("[id='plotStats'], [id='plotGumbel'], [id^='plot_']").forEach(function(d){
                    var marker = document.createElement('div');
                    marker.textContent = '[[CHART:' + d.id + ']]';
                    d.parentNode.replaceChild(marker, d);
                });
                c.querySelectorAll('.modebar, .modebar-container, .js-plotly-plot, svg, input, label, button, select').forEach(function(el){
                    el.parentNode && el.parentNode.removeChild(el);
                });
                c.querySelectorAll('*').forEach(function(el){
                    for (var i = el.attributes.length - 1; i >= 0; i--) {
                        var n = el.attributes[i].name;
                        if (n.indexOf('on') === 0) { el.removeAttribute(n); }
                    }
                });
                return c.innerHTML;
            }

            var tablesHtml = cleanClone(contenuGraph) + cleanClone(contenu);

            var payload = Object.assign({}, dataToSendStats, {
                images:     images,
                tablesHtml: tablesHtml
            });

            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'include/structure/stats/process_global_pdf.php', true);
            xhr.setRequestHeader('Content-Type', 'application/json');
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    restore();
                    if (xhr.status === 200) {
                        var resp; try { resp = JSON.parse(xhr.responseText); } catch(e){ return; }
                        if (resp && resp.status === 'success' && resp.fileName) {
                            var a = document.createElement('a');
                            a.href = '<?php echo DIR_WS_PDF; ?>' + resp.fileName;
                            a.target = '_blank'; a.rel = 'noopener';
                            document.body.appendChild(a); a.click(); document.body.removeChild(a);
                        }
                    }
                }
            };
            xhr.send(JSON.stringify(payload));
        }).catch(function(){ restore(); });
    }

    function statsChron(typeStat='global') {
        hpCurrentStatView = typeStat;
        waitBoxStats.style.display = 'block';
        contenuStats.style.display = 'none';
        contenuStatsGraph.style.display = 'none';

        bydays = false;

        switch(typeStat) {
            case 'global':
                processFile = 'process_stats_chron_global.php';
                break;
            case 'byyear':
                processFile = 'process_stats_chron_byyear.php';
                break;
            case 'bymonth':
                processFile = 'process_stats_chron_bymonth.php';
                break;
            case 'bydays':
                processFile = 'process_stats_chron_bydays_selectyear.php';
                bydays = true;
                break;
            case 'workbymonth':
                processFile = 'process_stats_chron_global.php';
                break;
            case 'lowflow':
                processFile = 'process_stats_chron_lowflow.php';
                break;
            case 'returnperiod':
                processFile = 'process_stats_chron_returnperiod.php';
                break;
            default:
                processFile = 'process_stats_chron_global.php';
        }

        var buttons = document.querySelectorAll('.bstats');
        buttons.forEach(function(button) {
            button.classList.remove('active');
        });

        var buttonClick = document.getElementById(typeStat);
        if(buttonClick) {
            buttonClick.classList.add('active');
        }

        var xhr = new XMLHttpRequest();
        xhr.open("POST", "include/structure/stats/"+processFile, true);
        xhr.setRequestHeader("Content-Type", "application/json");

        xhr.onreadystatechange = function() {
            if(xhr.readyState === 4 && xhr.status === 200) {
                var jsonResponse = JSON.parse(xhr.responseText);
                html_stats = jsonResponse['html_stats'];
                stat_graph = jsonResponse['stat_graph'];
                html_stats_graph = jsonResponse['html_stats_graph'];
                js_graph = jsonResponse['js_graph'];

                waitBoxStats.style.display = 'none';
                contenuStats.style.display = 'block';
                contenuStats.innerHTML = html_stats;

                // Add an export toolbar (CSV / PDF) above the table for the
                // annual / monthly / daily views (not the general summary).
                hpInjectStatsToolbar(typeStat);

                if(bydays) {
                    yearSelect = document.getElementById('yearSelect').value;
                    statsChronDays(yearSelect);
                }

                if(stat_graph) {
                    contenuStatsGraph.style.display = 'block';
                    contenuStatsGraph.innerHTML = html_stats_graph;
                    eval(js_graph);
                }
            }
        };
        xhr.send(JSON.stringify(dataToSendStats));
    }

    function statsChronDays(year_select) {
        var contenuStatsDays = document.getElementById('contenu_stats_days');

        var dataToSendStatsDays = {
            stats: dataToSendStats,
            yearSelect: year_select
        };

        var xhr = new XMLHttpRequest();
        xhr.open("POST", "include/structure/stats/process_stats_chron_bydays.php", true);
        xhr.setRequestHeader("Content-Type", "application/json");

        xhr.onreadystatechange = function() {
            if(xhr.readyState === 4 && xhr.status === 200) {
                var jsonResponse = JSON.parse(xhr.responseText);
                html_stats = jsonResponse['html_stats'];

                contenuStatsDays.style.display = 'block';
                contenuStatsDays.innerHTML = html_stats;
            }
        };
        xhr.send(JSON.stringify(dataToSendStatsDays));
    }

    function prevYear() {
        var yearSelect = document.getElementById('yearSelect');
        var selectedIndex = yearSelect.selectedIndex;
        if(selectedIndex < yearSelect.options.length - 1) {
            yearSelect.selectedIndex = selectedIndex + 1;
            statsChronDays(yearSelect.value);
        }
    }

    function nextYear() {
        var yearSelect = document.getElementById('yearSelect');
        var selectedIndex = yearSelect.selectedIndex;
        if(selectedIndex > 0) {
            yearSelect.selectedIndex = selectedIndex - 1;
            statsChronDays(yearSelect.value);
        }
    }

    // Date validation functions
    function isValidDatesInput(date1Input, date2Input) {
        if(isValidDate(date1Input) && isValidDate(date2Input)) {
            const date1Format = parseDate(date1Input);
            const date2Format = parseDate(date2Input);

            if(date1Format < date2Format) {
                return true;
            } else {
                msgInfo.innerText = TEXT_START_BEFORE_END;
                msgInfo.style.display = 'block';
                return false;
            }
        } else {
            msgInfo.innerText = TEXT_INVALID_DATE_FORMAT;
            msgInfo.style.display = 'block';
            return false;
        }
    }

    function isValidDate(dateString) {
        const dateRegex = /^(0[1-9]|[12][0-9]|3[01])-(0[1-9]|1[0-2])-(\d{4})$/;
        if(!dateRegex.test(dateString)) {
            return false;
        }

        const [day, month, year] = dateString.split("-").map(Number);
        const date = new Date(year, month - 1, day);
        return (
            date.getFullYear() === year &&
            date.getMonth() === month - 1 &&
            date.getDate() === day
        );
    }

    function parseDate(dateString) {
        [day, month, year] = dateString.split("-").map(Number);
        return new Date(year, month - 1, day);
    }

    function isNumber(inputElement) {
        const value = Number(inputElement);
        if(isNaN(value)) {
            msgInfo.innerText = TEXT_NUMERIC_ERROR;
            msgInfo.style.display = 'block';
            return false;
        }
        return true;
    }

    // Menu toggle functionality
    $(document).ready(function() {
        $('.toggle-graph').each(function() {
            const menuId = $(this).data('menu-graph');
            const isOpen = menuStates[menuId] === 1;
            const navigation = $(this).nextAll('.navMenuGraph').first();
            const arrow = $(this).find('.arrow');

            if(isOpen) {
                navigation.show();
                arrow.html('&#9650;');
            } else {
                navigation.hide();
                arrow.html('&#9660;');
            }
        });

        $(document).on('click', '.toggle-graph', function() {
            const id_user = <?php echo json_encode($id_user); ?>;
            const navdiag = $(this).nextAll('.navMenuGraph').first();
            const menuId = $(this).data('menu-graph');
            const isOpen = navdiag.is(':visible');

            navdiag.slideToggle('slow', function() {
                const arrow = $(this).prevAll('.toggle-graph').find('.arrow');
                if(navdiag.is(':visible')) {
                    arrow.html('&#9650;');
                } else {
                    arrow.html('&#9660;');
                }

                const dataToSend = {
                    id_user: id_user,
                    menu_id: menuId,
                    is_open: !isOpen
                };

                const jsonData = JSON.stringify(dataToSend);
                const xhr = new XMLHttpRequest();
                xhr.open("POST", "include/structure/box/process_menu.php", true);
                xhr.setRequestHeader("Content-Type", "application/json");
                xhr.send(jsonData);
            });
        });
    });
</script>