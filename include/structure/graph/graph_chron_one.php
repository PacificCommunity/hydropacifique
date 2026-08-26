<?php
/*
----------------------------------------
Copyright (c) 2025 - Vai-Natura
----------------------------------------
Display data in a combined graph (2 axes)
----------------------------------------
*/

// Initialize color index for graph lines
$colorIndex = 1;
// Two color sources, same convention as the "1 graph per chronicle" page:
//   - colorList()    : 18 high-contrast colors used to AUTO-ASSIGN a default
//                      color to each new trace when the table is built.
//   - colorPalette() : 42 colors organized by hue, displayed in the picker
//                      grid so the user can pick any shade freely.
$colorGraph         = colorList();
$colorPickerPalette = colorPalette();
// Count the total number of available colors
$maxColors  = count($colorGraph);

// Initialize variables for station name and code
$nom_station = '';
$code_station = '';

// Initialize variables for graph axis limits
$x_min = 0;
$x_max = 0;
$y_min = 0;
$y_max = 0;

// CSS class for table rows (clickable and selectable)
$row_l = "class='row1' onclick=\"this.className='rowSelect';\"";
$print_row = '';

// Variable to store the first graph title and all graph data
$titre_graph_first = "";
$data_graph_all = "";

// --------------------------------------
// Strip out chronicle types that the combined-graph view can't render.
//
// The combined view is designed for time series that share Y1/Y2 axes:
// classical chronicles (lines/bar/markers) and RA (manual readings,
// shown as a scatter overlay on the right Y axis of the matching
// station — hydro reads as a water height, piezo as a water table
// depth). RA is supported via dedicated SQL + Plotly branches further
// down and in process_graph_one.php.
//
// Types we drop here:
//   - 'etl' (rating curves)      — not a true time series
//   - 'rep' / 'cte' / 'diac'     — piezometer station configuration
//                                   (not loaded by button_graph anyway)
//   - '55' (lab) / '58' (tot)    — independent series with different
//                                   schemas, would need a dedicated branch
//
// RA and JGE are intentionally kept so the rest of the file (and the
// backend) can handle them as point/marker series.
$unsupported_combined = array('etl', 'rep', 'cte', 'diac', '55', '58');
if (isset($station_chron_array) && is_array($station_chron_array)) {
    foreach ($station_chron_array as $cle_station => $typedata_array) {
        foreach ($typedata_array as $cle_type_data => $typedata_sql) {
            if (in_array((string)$cle_type_data, $unsupported_combined, true)) {
                unset($station_chron_array[$cle_station][$cle_type_data]);
            }
        }
        // If a station has no supported series left after filtering,
        // drop the whole station entry to avoid empty groups downstream.
        if (empty($station_chron_array[$cle_station])) {
            unset($station_chron_array[$cle_station]);
        }
    }
}

// --------------------------------------
// Fetch distinct axis types and units from the database
$sql_data_type_axe = "SELECT DISTINCT id, axe, unite FROM " . TABLE_DATA_TYPE_AXE;
$data_type_axe_query = tep_db_query($sql_link, $sql_data_type_axe);
while ($data_type_axe = tep_db_fetch_array($data_type_axe_query)) {
    // Store axis name and unit in an array, decoding HTML entities
    $data_type_axe_array[$data_type_axe['id']] = array(
        'axe'   => html_entity_decode($data_type_axe['axe'] ?? ''),
        'unite' => html_entity_decode($data_type_axe['unite'] ?? '')
    );
}

// Array to store axis information for each data type
$tab_axe = array();

// Synthetic axis labels for marker series (RA, JGE, ...). These records
// don't live in TABLE_TYPE_DATA, so they have no axe_nom/unite to look
// up via $type_chron_array. We provide one default per (marker type,
// station type) pair:
//   - RA hydrometry → water height (cm)
//   - RA piezometry → water-table depth (cm)
//   - JGE          → streamflow (m³/s)   [hydrometry only]
// Labels are plain strings — wrap them in your TEXT_* constants if you
// want them localized later.
$marker_axis_labels = array(
    'ra' => array(
        11 => array('nom' => "Hauteur d'eau",       'unite' => 'cm',    'nb_round' => 1),  // hydrometry
         5 => array('nom' => 'Profondeur nappe',    'unite' => 'cm',    'nb_round' => 1),  // piezometry
    ),
    'jge' => array(
        11 => array('nom' => 'Débit',               'unite' => 'm³/s',  'nb_round' => 3),
    ),
);

// If there are stations with chronological data
if (isset($station_chron_array) && sizeof($station_chron_array) > 0) {
    // Loop through each station and its data types
    foreach ($station_chron_array as $cle_station => $typedata_array) {
        // Station equipment type — used below for RA-only stations to
        // pick the right axis label.
        $station_type_here = isset($station_all_array[$cle_station]['type_station'])
                             ? (int)$station_all_array[$cle_station]['type_station']
                             : 0;

        foreach ($typedata_array as $cle_type_data => $typedata_sql) {
            // -------- Marker-series branch (RA, JGE, ...) --------
            // Marker series have no row in TABLE_TYPE_DATA, so we use
            // $marker_axis_labels[<type>][<station_type>] for their
            // axis label/unit/rounding. We build a synthetic axis key
            //   <type>_<station_type>
            // so every marker series of the same type+station-type
            // shares one Y-axis entry in the picker (e.g. all hydro RA
            // values share 'ra_11' = "Hauteur d'eau (cm)").
            if (isset($marker_axis_labels[$cle_type_data])) {
                if (isset($marker_axis_labels[$cle_type_data][$station_type_here])) {
                    $marker_key = $cle_type_data . '_' . $station_type_here;
                    if (!isset($tab_axe[$marker_key])) {
                        $tab_axe[$marker_key] = $marker_axis_labels[$cle_type_data][$station_type_here];
                    }
                }
                continue;
            }

            // -------- Classic chronicle branch (numeric id) --------
            if (!isset($tab_axe[$cle_type_data]) && isset($type_chron_array[$cle_type_data])) {
                // Store axis name and unit for this data type
                $tab_axe[$cle_type_data]['nom']      = $type_chron_array[$cle_type_data]['axe_nom'];
                $tab_axe[$cle_type_data]['unite']    = $type_chron_array[$cle_type_data]['unite'];
                $tab_axe[$cle_type_data]['nb_round'] = $type_chron_array[$cle_type_data]['nb_round'];
            }
        }
    }
}

// --------------------------------------
// Start HTML page

// Include the header and common structure files
require(DIR_WS_STRUCTURE . 'header_web.php');

echo "<body>";

    // Include graph block (fullscreen display) and header (top banner)
    require(DIR_WS_STRUCTURE . 'block_graph.php');
    require(DIR_WS_STRUCTURE . 'header.php');
    include(DIR_WS_BOX . 'nav_accueil.php'); // Main navigation menu

    // Main container for the page content
    echo "<div id='contour_general'>";

        // Hidden div for additional info (if needed)
        echo "<div id='contenu_info' style='display:none;'></div>";

        // Central content area
        echo "<div id='contenu_centre'>";

            // Content box for the graph and controls
            echo "<div id='contenu_box2'>";

                // Page title
                echo "<h1>";
                    echo "<span style=font-weight:bold;>" . TEXT_GRAPH_TITLE . "</span>";
                echo "</h1>";

                echo "<div style='widht:100%;'>";

                    // Compact layout tweaks for the combined-graph left column:
                    // make the axis tables fit the column width with no
                    // horizontal scrollbar, and tidy the per-row controls.
                    echo "<style>
                        #cadre_graph .table-container { overflow-x:hidden; }
                        #cadre_graph #table_tri {
                            width:100%; min-width:0; table-layout:fixed; border-collapse:collapse;
                        }
                        #cadre_graph #table_tri th,
                        #cadre_graph #table_tri td {
                            overflow:hidden; padding:4px 3px; vertical-align:middle;
                        }
                        /* Station name: keep the full label on up to two lines,
                           wrapping instead of forcing the column wider. */
                        #cadre_graph #table_tri td:nth-child(2) {
                            white-space:normal; word-break:break-word; line-height:1.15; font-size:11px;
                        }
                        #cadre_graph #table_tri td:nth-child(3) { font-size:11px; }
                        /* Per-row control cell (colour swatch stacked over -/+). */
                        #cadre_graph #table_tri td:last-child { text-align:center; }
                        #cadre_graph .decimal_axe { min-width:0; line-height:1.1; }
                    </style>";

                    // Left column: Graph controls and data selection (380px wide, scrollable)
                    echo "<div id='cadre_graph' style='float:left;width:360px;margin:0;margin-right:10px;height:78vh;overflow-y:auto;overflow-x:hidden;'>";

                        // Top control bar: Refresh button and "Show Gaps" checkbox
                        echo "<div id='boxpopup' class='select-top' style='width:96%;margin-bottom:10px;padding:5px 1%;'>";

                            // Refresh graph button
                            echo "<div id='button_visu' style='float:left;width:180px;margin-left: 1%;' onClick='load_graph(true);'>";
                                echo TEXT_REFRESH_GRAPH;
                            echo "</div>";

                            // Checkbox to toggle display of data gaps
                            echo "<p style='float:right;margin-top:6px;margin-right: 1%;'>";
                                echo "<input type='checkbox' id='check_lac' checked>";
                                echo "<span style='margin-left:5px;font-size:11px;font-weight:normal;'>";
                                    echo TEXT_SHOW_GAPS;
                                echo "</span>";
                            echo "</p>";

                        echo "</div>";

                        // Axis 1 selection and controls
                        echo "<div id='boxpopup' class='select-top' style='width:96%;margin:0px;padding:10px 1%;'>";

                            echo "<div style='margin:0 1%;margin-bottom:10px;'>";
                                echo "<p onclick=\"toggleAxisTable(1)\" style='float:left;padding-top:3px;font-weight: bold;font-size:14px;cursor:pointer;user-select:none;'>"
                                   . "<span id='axisChevron1' style='display:inline-block;font-size:10px;margin-right:5px;transition:transform 0.2s;transform:rotate(-90deg);'>&#9660;</span>"
                                   . TEXT_AXIS_1 . "</p>";

                                if (!empty($tab_axe)) {
                                    // Log scale toggle button for Axis 1
                                    echo "<button id='log-button1' class='log_axe' style='float:left;width:52px;margin-left:8px;'>" . TEXT_LOG_SCALE_SHORT . "</button>";

                                    // Flip axis button for Axis 1
                                    echo "<button id='reverse-button1' class='log_axe' style='float:left;width:44px;margin-left:8px;'>" . TEXT_FLIP . "</button>";

                                    // Dropdown to select which data type to display on Axis 1
                                    echo "<select name='title_axe1' id='title_axe1' style='float:right;width:100px;'>";

                                        foreach ($tab_axe as $cle_type_data => $axe) {
                                            $nom   = htmlspecialchars($axe['nom']);
                                            $unite = htmlspecialchars($axe['unite']);
                                            $nb_round = htmlspecialchars($axe['nb_round']);
                                            $value = htmlspecialchars($cle_type_data);

                                            echo "<option value='" . $value . "' data-nom='" . $nom . "' data-unite='" . $unite . "' data-round='" . $nb_round . "'>"
                                                . $nom . " (" . $unite . ")"
                                            . "</option>";
                                        }

                                    echo "</select>";
                                }

                            echo "<hr>";
                            echo "</div>";

                            // Container for Axis 1 data selection table
                            echo "<div id='cadre_data_axe1' style='width:100%;margin:0;padding:0;display:none;'>";

                                echo "<div class='table-container' style='height: auto;'>";

                                    // Table to display stations and their data for Axis 1
                                    echo "<table id='table_tri' cellspacing='0'>";

                                        // Table header
                                        echo "
                                            <thead>
                                                <tr class='header-row'>
                                                    <th style='width:24px;'></th>
                                                    <th style='width:108px;font-size:11px;'>" . TEXT_STATION_NAME . "</th>
                                                    <th style='width:72px;font-size:11px;'>" . TEXT_STATION_CODE . "</th>
                                                    <th style='width:48px;font-size:11px; text-align:center;'>" . TEXT_CHRONIC . "</th>
                                                    <th style='width:56px;'></th>
                                                </tr>
                                            </thead>
                                        ";

                                        // If there are stations with data
                                        if (isset($station_chron_array) && sizeof($station_chron_array) > 0) {
                                            $row = 0;
                                            foreach ($station_chron_array as $cle_station => $typedata_array) {
                                                foreach ($typedata_array as $cle_type_data => $typedata_sql) {
                                                    // Skip empty or excluded data types.
                                                    // 'ra' is intentionally allowed — the trace will be rendered
                                                    // as scatter markers by process_graph_one.php's RA branch.
                                                    if (!empty($cle_type_data) && !in_array($cle_type_data, array('etl'))) {
                                                        // Reset color index if we've used all colors
                                                        if ($colorIndex > $maxColors) { $colorIndex = 1; }
                                                        // Check the first row by default
                                                        $checked = ($row === 0) ? 'checked' : '';

                                                        // Alternate row colors
                                                        if (fmod($row, 2) == 0) {
                                                            $row_l = "class='row1' style='height:36px;' onmouseover=\"this.className='row1hover';\" onmouseout=\"this.className='row1';\" ";
                                                        } else {
                                                            $row_l = "class='row2' style='height:36px;' onmouseover=\"this.className='row2hover';\" onmouseout=\"this.className='row2';\" ";
                                                        }

                                                        // Unique ID for this checkbox
                                                        $input_id = 'axe1_' . $cle_station . '_' . $cle_type_data;
                                                        $traceName = 'trace_' . $input_id;

                                                        // Get station name for display
                                                        $name_st = $station_all_array[$cle_station]['nom_station'] ?? '';
                                                        $title_st = $name_st;
                                                        $label_st = $name_st;

                                                        // Chronicle label. For numeric ids we look it up in
                                                        // $type_chron_array; for 'ra' / 'jge' (no row in
                                                        // TABLE_TYPE_DATA) we use the already-translated
                                                        // constants instead of strtoupper(), which would
                                                        // produce a French abbreviation in any language
                                                        // ('RA' is not the English label for 'manual stage
                                                        // reading').
                                                        if (isset($type_chron_array[$cle_type_data])) {
                                                            $chron_label_display = $type_chron_array[$cle_type_data]['init_type_data'];
                                                        } elseif ($cle_type_data === 'ra') {
                                                            $chron_label_display = TEXT_CHRON_RA;
                                                        } elseif ($cle_type_data === 'jge') {
                                                            $chron_label_display = TEXT_CHRON_JGE;
                                                        } else {
                                                            $chron_label_display = strtoupper((string)$cle_type_data);
                                                        }

                                                        // Table row for this station/data type
                                                        echo "
                                                            <tr " . $row_l . ">
                                                                <td style='text-align:left;'>
                                                                    <input type='checkbox' id='" . $input_id . "' class='axe1-item axe1-other' name='checkbox_axe1[]'
                                                                        value='" . $input_id . "' " . $checked . "
                                                                        data-typedata-sql='" . htmlspecialchars($typedata_sql, ENT_QUOTES, 'UTF-8') . "' >
                                                                </td>
                                                                <td title='" . $title_st . "'>" . $label_st . "</td>
                                                                <td>" . $station_all_array[$cle_station]['code_station'] . "</td>
                                                                <td style='text-align:center;'>" . $chron_label_display . "</td>
                                                                <td>
                                                                    <div class='color-dropdown'>
                                                                        <div id='selectedColor_" . $input_id . "' class='dropdown-selected' onclick=\"toggleDropdownColor('" . $input_id . "')\" style='background-color:" . $colorGraph[$colorIndex] . ";width:14px;height:14px;margin:0 auto;'></div>
                                                                        <div id='dropdownList_" . $input_id . "' class='color-grid'>";
                                                                            // Rich palette (42 colors) - the auto-assigned color is highlighted
                                                                            $currentColorHex = $colorGraph[$colorIndex];
                                                                            foreach ($colorPickerPalette as $id => $color) {
                                                                                $is_selected = (strcasecmp($color, $currentColorHex) === 0) ? ' is-selected' : '';
                                                                                echo "<div class='color-cell" . $is_selected . "'"
                                                                                   . " style='background-color:" . $color . "'"
                                                                                   . " title='" . $color . "'"
                                                                                   . " onclick=\"selectColor('" . $color . "','" . $input_id . "');\"></div>";
                                                                            }
                                                        echo "
                                                                        </div>
                                                                    </div>
                                                                    <input type='hidden' id='input_color_" . $input_id . "' value='" . $colorGraph[$colorIndex] . "' />
                                                                    <div style='display:flex;align-items:center;justify-content:center;gap:3px;margin-top:4px;white-space:nowrap;'>
                                                                        <button type='button' class='decimal_axe' style='padding:1px 5px;' onclick=\"bumpLineWidth('" . $input_id . "',-0.5);\">−</button>
                                                                        <button type='button' class='decimal_axe' style='padding:1px 5px;' onclick=\"bumpLineWidth('" . $input_id . "',0.5);\">+</button>
                                                                        <span id='lineWidthDisplay_" . $input_id . "' class='linew-display' style='display:none;'></span>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                        ";

                                                        $row++;
                                                        $colorIndex++;
                                                    }
                                                }
                                            }
                                        }

                                    echo "</table>";
                                echo "</div>";
                            echo "</div>";
                        echo "</div>";

                        // Axis 2 selection and controls (similar to Axis 1)
                        echo "<div id='boxpopup' class='select-top' style='width:96%;margin:0px;margin-top:10px;padding:10px 1%;'>";

                            echo "<div style='margin:0 1%;margin-bottom:10px;'>";
                                echo "<p onclick=\"toggleAxisTable(2)\" style='float:left;padding-top:3px;font-weight: bold;font-size:14px;cursor:pointer;user-select:none;'>"
                                   . "<span id='axisChevron2' style='display:inline-block;font-size:10px;margin-right:5px;transition:transform 0.2s;transform:rotate(-90deg);'>&#9660;</span>"
                                   . TEXT_AXIS_2 . "</p>";

                                if (!empty($tab_axe)) {
                                    echo "<button id='log-button2' class='log_axe' style='float:left;width:52px;margin-left:8px;'>" . TEXT_LOG_SCALE_SHORT . "</button>";
                                    echo "<button id='reverse-button2' class='log_axe' style='float:left;width:44px;margin-left:8px;'>" . TEXT_FLIP . "</button>";

                                    echo "<select name='title_axe2' id='title_axe2' style='float:right;width:100px;'>";

                                        $r = 1;
                                        foreach ($tab_axe as $cle_type_data => $axe) {
                                            $nom   = $axe['nom'];
                                            $unite = $axe['unite'];
                                            $nb_round = $axe['nb_round'];
                                            $value = $cle_type_data;

                                            $selected = '';
                                            if ($r == 2) { $selected = 'selected'; }

                                            echo "<option value='" . $value . "' data-nom='" . $nom . "' data-unite='" . $unite . "'  data-round='" . $nb_round . "' " . $selected . ">"
                                                . $nom . " (" . $unite . ")"
                                            . "</option>";

                                            $r++;
                                        }

                                    echo "</select>";
                                }

                            echo "<hr>";
                            echo "</div>";

                            // Container for Axis 2 data selection table
                            echo "<div id='cadre_data_axe2' style='width:100%;margin:0;padding:0;display:none;'>";

                                echo "<div class='table-container' style='height:auto;'>";

                                    echo "<table id='table_tri' cellspacing='0'>";

                                        echo "
                                            <thead>
                                                <tr class='header-row'>
                                                    <th style='width:24px;'></th>
                                                    <th style='width:108px;font-size:11px;'>" . TEXT_STATION_NAME . "</th>
                                                    <th style='width:72px;font-size:11px;'>" . TEXT_STATION_CODE . "</th>
                                                    <th style='width:48px;font-size:11px; text-align:center;'>" . TEXT_CHRONIC . "</th>
                                                    <th style='width:56px;'></th>
                                                </tr>
                                            </thead>
                                        ";

                                        if (isset($station_chron_array) && sizeof($station_chron_array) > 0) {
                                            $colorIndex = 1;
                                            $row = 0;
                                            foreach ($station_chron_array as $cle_station => $typedata_array) {
                                                foreach ($typedata_array as $cle_type_data => $typedata_sql) {
                                                    // 'ra' allowed — see axe 1 table comment above.
                                                    if (!empty($cle_type_data) && !in_array($cle_type_data, array('etl'))) {
                                                        if ($colorIndex > $maxColors) { $colorIndex = 1; }
                                                        $checked = ($row === 1) ? 'checked' : '';

                                                        if (fmod($row, 2) == 0) {
                                                            $row_l = "class='row1' style='height:36px;' onmouseover=\"this.className='row1hover';\" onmouseout=\"this.className='row1';\" ";
                                                        } else {
                                                            $row_l = "class='row2' style='height:36px;' onmouseover=\"this.className='row2hover';\" onmouseout=\"this.className='row2';\" ";
                                                        }

                                                        $input_id = 'axe2_' . $cle_station . '_' . $cle_type_data;
                                                        $traceName = 'trace_' . $input_id;

                                                        $name_st = $station_all_array[$cle_station]['nom_station'] ?? '';
                                                        $title_st = $name_st;
                                                        $label_st = affichelettres($name_st, 18);

                                                        // Tolerant chronicle label (see axe 1 table for rationale —
                                                        // 'ra' / 'jge' have no TABLE_TYPE_DATA row so we use the
                                                        // already-translated constants).
                                                        if (isset($type_chron_array[$cle_type_data])) {
                                                            $chron_label_display = $type_chron_array[$cle_type_data]['init_type_data'];
                                                        } elseif ($cle_type_data === 'ra') {
                                                            $chron_label_display = TEXT_CHRON_RA;
                                                        } elseif ($cle_type_data === 'jge') {
                                                            $chron_label_display = TEXT_CHRON_JGE;
                                                        } else {
                                                            $chron_label_display = strtoupper((string)$cle_type_data);
                                                        }

                                                        echo "
                                                            <tr " . $row_l . ">
                                                                <td style='text-align:left;'>
                                                                    <input type='checkbox' id='" . $input_id . "' class='axe2-item axe2-other' name='checkbox_axe2[]'
                                                                        value='" . $input_id . "' " . $checked . "
                                                                        data-typedata-sql='" . htmlspecialchars($typedata_sql, ENT_QUOTES, 'UTF-8') . "' >
                                                                </td>
                                                                <td title='" . $title_st . "'>" . $label_st . "</td>
                                                                <td>" . $station_all_array[$cle_station]['code_station'] . "</td>
                                                                <td style='text-align:center;'>" . $chron_label_display . "</td>
                                                                <td>
                                                                    <div class='color-dropdown'>
                                                                        <div id='selectedColor_" . $input_id . "' class='dropdown-selected' onclick=\"toggleDropdownColor('" . $input_id . "')\" style='background-color:" . $colorGraph[$colorIndex] . ";width:14px;height:14px;margin:0 auto;'></div>
                                                                        <div id='dropdownList_" . $input_id . "' class='color-grid'>";
                                                                            // Same rich-palette picker as axis 1 (see comment there)
                                                                            $currentColorHex = $colorGraph[$colorIndex];
                                                                            foreach ($colorPickerPalette as $id => $color) {
                                                                                $is_selected = (strcasecmp($color, $currentColorHex) === 0) ? ' is-selected' : '';
                                                                                echo "<div class='color-cell" . $is_selected . "'"
                                                                                   . " style='background-color:" . $color . "'"
                                                                                   . " title='" . $color . "'"
                                                                                   . " onclick=\"selectColor('" . $color . "','" . $input_id . "');\"></div>";
                                                                            }
                                                        echo "
                                                                        </div>
                                                                    </div>
                                                                    <input type='hidden' id='input_color_" . $input_id . "' value='" . $colorGraph[$colorIndex] . "' />
                                                                    <div style='display:flex;align-items:center;justify-content:center;gap:3px;margin-top:4px;white-space:nowrap;'>
                                                                        <button type='button' class='decimal_axe' style='padding:1px 5px;' onclick=\"bumpLineWidth('" . $input_id . "',-0.5);\">−</button>
                                                                        <button type='button' class='decimal_axe' style='padding:1px 5px;' onclick=\"bumpLineWidth('" . $input_id . "',0.5);\">+</button>
                                                                        <span id='lineWidthDisplay_" . $input_id . "' class='linew-display' style='display:none;'></span>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                        ";

                                                        $row++;
                                                        $colorIndex++;
                                                    }
                                                }
                                            }
                                        }

                                    echo "</table>";
                                echo "</div>";
                            echo "</div>";
                        echo "</div>";

                        // Zoom and axis range controls
                        echo "<div id='boxpopup' class='select-top' style='width:96%;margin:0px;margin-top:10px;padding:10px 1%;'>";

                            echo "<div style='margin:0 1%;margin-bottom:10px;'>";
                                echo "<p style='float:left;padding-top:3px;font-weight: bold;font-size:14px;'>" . TEXT_ZOOM_CONTROL . "</p>";
                                echo "<button id='ajustCoord' class='zoom_graph' style='float:left;width:110px;margin-left:8px;' onClick='updateGraphRange();'>" . TEXT_ADJUST_SCALE . "</button>";
                            echo "  </div>";

                            // Date range selection for X-axis
                            echo "<div style='margin:0 1%;'>";

                                echo "<div style='float:left;width:48%;margin-top:10px;'>";

                                    echo "<div id='boite_small' style='display:flex;align-items:center;gap:6px;'>";
                                        echo "<p style='width:36px;margin:0;font-size:11px;color:#428bca;'>" . TEXT_START_DATE . "</p>";
                                        echo "<input class='input_texte'
                                                style='width:80px;padding-bottom: 4px;'
                                                name='date_min'
                                                id='date_min'
                                                type='text'
                                                onFocus='initDatepickers(this)'
                                                placeholder='dd-mm-yyyy'
                                                value='" . $date_1 . "' >";
                                    echo "</div>";

                                    echo "<hr>";

                                    echo "<div id='boite_small' style='display:flex;align-items:center;gap:6px;'>";
                                        echo "<p style='width:36px;margin:0;font-size:11px;color:#428bca;'>" . TEXT_END_DATE . "</p>";
                                        echo "<input class='input_texte'
                                                style='width:80px;padding-bottom: 4px;'
                                                name='date_max'
                                                id='date_max'
                                                type='text'
                                                onFocus='initDatepickers(this)'
                                                placeholder='dd-mm-yyyy'
                                                value='" . $date_2 . "' >";
                                    echo "</div>";
                                echo "</div>";

                                // Y-axis range controls for both axes.
                                // Flex layout so the "Axis 1 / Axis 2" label,
                                // Y-min and Y-max fields stay vertically aligned
                                // even when the labels are wider than expected.
                                // The 'data-empty="1"' attribute on inputs is
                                // toggled by JS to grey out the axis-2 row when
                                // no series is bound to it.
                                echo "<div style='float:left;width:52%;margin-top:0px;'>";

                                    // ----- Axis 1 row -----
                                    // Wrapper carries id='zoomCtrlAxis1' so the
                                    // JS can dim it when no series uses axis 1
                                    // (mirrors the axis-2 logic below).
                                    echo "<div id='zoomCtrlAxis1' style='display:flex;align-items:flex-end;gap:10px;flex-wrap:nowrap;margin-top:10px;transition:opacity 0.15s;'>";
                                        echo "<div style='font-weight:bold;font-size:11px;padding-bottom:8px;white-space:nowrap;'>" . TEXT_AXIS_1 . "</div>";

                                        echo "<div>";
                                            echo "<p style='color:#428bca;margin:0 0 2px 0;'>" . TEXT_YMIN . "</p>";
                                            echo "<input class='input_texte' style='width:40px;padding-bottom:4px;' name='y_min_1' id='y_min_1' type='text' value='0' >";
                                        echo "</div>";

                                        echo "<div>";
                                            echo "<p style='color:#428bca;margin:0 0 2px 0;'>" . TEXT_YMAX . "</p>";
                                            echo "<input class='input_texte' style='width:40px;padding-bottom:4px;' name='y_max_1' id='y_max_1' type='text' value='0' >";
                                        echo "</div>";
                                    echo "</div>";

                                    echo "<hr>";

                                    // ----- Axis 2 row -----
                                    // Wrapper carries id='zoomCtrlAxis2' so the
                                    // JS can dim it when no series uses axis 2.
                                    echo "<div id='zoomCtrlAxis2' style='display:flex;align-items:flex-end;gap:10px;flex-wrap:nowrap;margin-top:6px;transition:opacity 0.15s;'>";
                                        echo "<div style='font-weight:bold;font-size:11px;padding-bottom:8px;white-space:nowrap;'>" . TEXT_AXIS_2 . "</div>";

                                        echo "<div>";
                                            // Reuse the same TEXT_YMIN / TEXT_YMAX
                                            // labels as axis 1 for consistency —
                                            // the previous markup omitted them.
                                            echo "<p style='color:#428bca;margin:0 0 2px 0;'>" . TEXT_YMIN . "</p>";
                                            echo "<input class='input_texte' style='width:40px;padding-bottom:4px;' name='y_min_2' id='y_min_2' type='text' value='' placeholder='—' >";
                                        echo "</div>";

                                        echo "<div>";
                                            echo "<p style='color:#428bca;margin:0 0 2px 0;'>" . TEXT_YMAX . "</p>";
                                            echo "<input class='input_texte' style='width:40px;padding-bottom:4px;' name='y_max_2' id='y_max_2' type='text' value='' placeholder='—' >";
                                        echo "</div>";
                                    echo "</div>";
                                echo "</div>";
                            echo "</div>";
                        echo "</div>";

                    echo "</div>";

                    // Right column: Graph display area
                    echo "<div id='cadre_graph' style='float:none;width:auto;margin:0;max-height:100vh;overflow-y: auto;'>";

                        echo "<div id='boxpopup' class='select' style='width:96%;margin:0;padding: 5px 10px;'>";

                            /*
                            echo "<div style='float:left;width:33%;text-align:center;'>";
                                echo "<button id='buttonFullSreen' class='b_fullsreen' style='float:left;margin-top:0px;' onclick='zoom_graph();'>" . TEXT_FULLSCREEN . "</button>";
                            echo "</div>";
                            */

                            // Zoom control checkboxes
                            echo "<div style='height:28px;margin-right:15px;'>";

                                echo "<button id='buttonFullSreen' class='b_fullsreen' style='float:left;margin:0px;' onclick='zoom_graph();'>" . TEXT_FULLSCREEN . "</button>";


                                echo "<div style='float:right;'>
                                        <input type='checkbox' id='check_zoom_x' checked onclick='zoomCTRL();'>
                                        <span style='margin-left:5px;font-size:11px;font-weight:normal;'>" . TEXT_ZOOM_MOVE_X . "</span>
                                      </div>";
                                echo "<div style='float:right;margin-right:15px;'>
                                        <input type='checkbox' id='check_zoom_y' checked onclick='zoomCTRL();'>
                                        <span style='margin-left:5px;font-size:11px;font-weight:normal;'>" . TEXT_ZOOM_MOVE_Y . "</span>
                                      </div>";
                            echo "</div>";

                            // Graph container (initially hidden)
                            echo "<div id='plot_0' class='graph' style='height:70vh;display:none;'></div>";

                            // Loading indicator
                            echo "<div id='wait_graph' style='width:100%;height:46vh;text-align:center;'>";
                                echo "<img src='" . DIR_WS_IMG . "wait.gif' style='width:50px;margin-top:10%;'>";
                                echo "<p>" . TEXT_LOADING . "</p>";
                                echo "<p>" . TEXT_LOADING_WAIT . "</p>";
                            echo "</div>";

                            // Decimal precision controls for both axes
                            echo "<div style='width:100%;'>";
                                echo "<div style='float:left;width:15%;'>";
                                    echo "<div style='float:left;margin-left:30px;'>
                                            <button id='plus_axe1' class='decimal_axe' title='" . TEXT_ADD_DECIMAL . "' onCLick=\"updateDecimals('plot_0','yaxis','+');\">+</button>
                                            <button id='moins__axe1' class='decimal_axe' title='" . TEXT_REMOVE_DECIMAL . "' onCLick=\"updateDecimals('plot_0','yaxis','-');\">-</button>
                                          </div>";
                                echo "</div>";

                                echo "<div style='float:right;width:15%;'>";
                                    echo "<div style='float:right;margin-right:30px;'>
                                            <button id='plus_axe2' class='decimal_axe' style='margin-left:20px;' title='" . TEXT_ADD_DECIMAL . "' onCLick=\"updateDecimals('plot_0','yaxis2','+');\">+</button>
                                            <button id='moins__axe1' class='decimal_axe' title='" . TEXT_REMOVE_DECIMAL . "' onCLick=\"updateDecimals('plot_0','yaxis2','-');\">-</button>
                                          </div>";
                                echo "</div>";
                            echo "</div>";

                        echo "<hr>";
                        echo "</div>";

                    echo "<hr>";
                    echo "</div>";

                echo "</div>";
            echo "</div>";
        echo "<hr>";
        echo "</div>";
    echo "<hr>";
    echo "</div>";

    // Include footer
    require('include/application_bottom.php');

echo "</body>";
echo "</html>";


?>


<script>

    // Collapse / expand an axis data table (hidden by default). The chevron in
    // the "Axis N" header rotates to reflect the open/closed state.
    function toggleAxisTable(n) {
        var box     = document.getElementById('cadre_data_axe' + n);
        var chevron = document.getElementById('axisChevron' + n);
        if (!box) { return; }
        var open = (box.style.display !== 'none');
        box.style.display = open ? 'none' : 'block';
        if (chevron) {
            chevron.style.transform = open ? 'rotate(-90deg)' : 'rotate(0deg)';
        }
    }

    // Paramétrage général - Description des variables
    msgInfo = document.getElementById('contenu_info');

    boxGraphWait = document.getElementById('wait_graph');
    boxPlot = document.getElementById('plot_0');
    
    var idPlotZoom = 0;

    // Vairable permettant de récupérer les shapes d'affichage des lacunes
    var js_shapesLacunesAxe1 = [];
    var js_shapesLacunesAxe2 = [];
    
    checkLac = document.getElementById('check_lac');

    dateFirst = document.getElementById('date_min');
    dateEnd = document.getElementById('date_max');

    yMin1 = document.getElementById('y_min_1');
    yMax1 = document.getElementById('y_max_1');
    yMin2 = document.getElementById('y_min_2');
    yMax2 = document.getElementById('y_max_2');

    min_x = '<?php echo $date_1;?>';
    max_x = '<?php echo $date_2;?>';

    nomAxe1 = '';uniteAxe1 = '';
    titleAxe1 = document.getElementById('title_axe1');

    nomAxe2 = '';uniteAxe2 = '';
    titleAxe2 = document.getElementById('title_axe2');

    
    // Variable pour suivre le nombre de décimales sur le graphique
    var decimalPlacesY1 = 1;
    var decimalPlacesY2 = 1;

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
                    name: '<?php echo TEXT_EXPORT_CSV;?>',
                    icon: monIconeDisquette,
                    direction: 'up',
                    click: function(gd) {
                        var allTraces = gd.data;
                        var visibleTraces = allTraces.filter(function(trace) {
                            return trace.visible !== 'legendonly' && trace.visible !== false;
                        });

                        if(visibleTraces.length === 0) {
                            return;
                        }

                        var data = visibleTraces;
                        var sep = ";";
                        var csvContent = "";

                        var allUniqueX = new Set();
                        data.forEach(function(trace) {
                            trace.x.forEach(function(xVal) {
                                allUniqueX.add(xVal);
                            });
                        });

                        var masterX = Array.from(allUniqueX).sort();

                        var xRange = gd.layout.xaxis.range;
                        if(xRange && xRange.length === 2) {
                            var minX = xRange[0];
                            var maxX = xRange[1];

                            masterX = masterX.filter(function(xVal) {
                                var numericX = (typeof xVal === 'number' || !isNaN(new Date(xVal).getTime())) ? (new Date(xVal).getTime() || xVal) : xVal;
                                var numericMinX = (typeof minX === 'number' || !isNaN(new Date(minX).getTime())) ? (new Date(minX).getTime() || minX) : minX;
                                var numericMaxX = (typeof maxX === 'number' || !isNaN(new Date(maxX).getTime())) ? (new Date(maxX).getTime() || maxX) : maxX;

                                return numericX >= numericMinX && numericX <= numericMaxX;
                            });
                        }

                        if(masterX.length === 0) {
                            return;
                        }

                        var lookupMaps = data.map(function(trace) {
                            var map = new Map();
                            for(var i = 0; i < trace.x.length; i++) {
                                map.set(trace.x[i], {
                                    y: trace.y[i],
                                    text: (trace.text && trace.text[i] !== undefined) ? trace.text[i] : ""
                                });
                            }
                            return map;
                        });

                        var header = ["X"];
                        data.forEach(function(trace, index) {
                            var seriesName = trace.name || "Trace " + (index + 1);
                            header.push("Y (" + seriesName + ")");
                            header.push("CodeQual (" + seriesName + ")");
                        });
                        csvContent += header.join(sep) + "\r\n";

                        masterX.forEach(function(xVal) {
                            var row = [];
                            var formattedX = String(xVal);
                            row.push(formattedX);

                            lookupMaps.forEach(function(map) {
                                var point = map.get(xVal);
                                if(point) {
                                    var yVal = String(point.y);
                                    row.push(yVal);
                                    row.push(point.text);
                                } else {
                                    row.push("");
                                    row.push("");
                                }
                            });

                            csvContent += row.join(sep) + "\r\n";
                        });

                        var blob = new Blob(["\uFEFF" + csvContent], { type: 'text/csv;charset=utf-8;' });
                        var url = URL.createObjectURL(blob);
                        var link = document.createElement("a");
                        link.setAttribute("href", url);
                        link.setAttribute("download", "HP-Data.csv");
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                    }
                },
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

    // Parse une case → { idStation, idChron, sql, color }
    //
    // The checkbox value has the form 'axe<1|2>_<idStation>_<idChron>'.
    // idChron is normally a numeric chronicle id, but can also be a
    // string token like 'ra' for manual readings. We keep idStation as
    // a number (always numeric) and idChron as-is — number for classic
    // series, string for special ones.
    function parseItem(el)
    {
        const parts     = el.value.split('_');                // ['axe1','245','ra'] or ['axe1','245','38']
        const idStation = Number(parts[1]);
        const rawChron  = parts[2];                           // e.g. 'ra' or '38'
        const idChron   = /^\d+$/.test(rawChron) ? Number(rawChron) : rawChron;

        const sql = el.dataset.typedataSql;

        // Look up the color picked for this row. The hidden input id
        // is `input_color_axe<1|2>_<idStation>_<idChron>`, so we build
        // it from the checkbox id rather than guessing.
        const row        = el.closest('tr');
        const colorInput = row
            ? row.querySelector('#input_color_' + el.id) || row.querySelector('input[id^="input_color_"]')
            : null;
        const color = colorInput ? colorInput.value : null;

        return { idStation, idChron, sql, color };
    }

    function load_graph(reload=false) 
    {
        if(!isValidDatesInput(dateFirst,dateEnd))
        {
           msgInfo.style.border = '2px solid #930000'; 
           return; // Action stoppée
        }

        // Vérifier si les valeurs sont bien des entiers
        if (!isNumber(yMin1) || !isNumber(yMax1) || !isNumber(yMin2) || !isNumber(yMax2))
        {            
            msgInfo.style.border = '2px solid #930000'; 
            return;
        }

        yMin1Value = 0;yMax1Value = 0;yMin2Value = 0;yMax2Value = 0;
       
        // pour récupérer les infos sur le nom et l'unité de l'axe
        selected1 = titleAxe1.options[titleAxe1.selectedIndex];
        nomAxe1  = selected1.getAttribute('data-nom');
        uniteAxe1    = selected1.getAttribute('data-unite');
        roundAxe1    = selected1.getAttribute('data-round');

        selected2 = titleAxe2.options[titleAxe2.selectedIndex];
        nomAxe2  = selected2.getAttribute('data-nom');
        uniteAxe2 = selected2.getAttribute('data-unite');
        roundAxe2    = selected2.getAttribute('data-round');


        boxPlot.style.display = 'none';
        boxGraphWait.style.display = 'block';

        // Récupère toutes les cases cochées pour chaque axe
        const checkedAxe1 = Array.from(document.querySelectorAll('.axe1-item:checked'));
        const checkedAxe2 = Array.from(document.querySelectorAll('.axe2-item:checked'));

        if (checkedAxe1.length === 0 || checkedAxe2.length === 0) 
        {
            console.warn('Sélectionne au moins une case pour chaque axe.');
            //return;
        }

        
        const axe1Items = checkedAxe1.map(parseItem);
        const axe2Items = checkedAxe2.map(parseItem);
        
        // Mise au format JSON des données
        // Créer un objet contenant les données à envoyer
        
        var dataToSend = {
            
            dateFirst: dateFirst.value,
            dateEnd: dateEnd.value,

            nomAxe1: nomAxe1,
            uniteAxe1: uniteAxe1,
            roundAxe1: roundAxe1,
            nomAxe2: nomAxe2,
            uniteAxe2: uniteAxe2,
            roundAxe2: roundAxe2,

            reload: reload,

            yMin1: yMin1Value,
            yMax1: yMax1Value,
            yMin2: yMin2Value,
            yMax2: yMax2Value,
            
            axe1: axe1Items,   // ex: [{idStation:12,idChron:3,sql:'...'}, ...]
            axe2: axe2Items
        };

        // Convertir l'objet en JSON
        var jsonDataGraph = JSON.stringify(dataToSend);
        
        
        // Effectuer une requête AJAX asynchrone
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "include/structure/graph/process_graph_one.php", true);
        xhr.setRequestHeader("Content-Type", "application/json");

        xhr.onreadystatechange = function() 
        {
            if (xhr.readyState === 4 && xhr.status === 200) 
            {
                boxPlot.style.display = 'block';
                boxGraphWait.style.display = 'none';
                
                // Analyser la réponse JSON
                var jsonResponse = JSON.parse(xhr.responseText);   
                
                // Accéder aux données récupéré coté serveur
                eval(jsonResponse['js_graph']); // on récupère le script généré coté serveur pour afficher les graphiques

                // Dim each axis row in the zoom control when no series
                // was bound to that axis. Y min/max inputs are already
                // emptied via the eval'd script above; this just makes
                // the disabled state visually explicit.
                var zoomCtrlAxis1 = document.getElementById('zoomCtrlAxis1');
                var zoomCtrlAxis2 = document.getElementById('zoomCtrlAxis2');
                if (zoomCtrlAxis1) {
                    var axis1Used = !!jsonResponse['axis1_used'];
                    zoomCtrlAxis1.style.opacity       = axis1Used ? '1'    : '0.4';
                    zoomCtrlAxis1.style.pointerEvents = axis1Used ? 'auto' : 'none';
                }
                if (zoomCtrlAxis2) {
                    var axis2Used = !!jsonResponse['axis2_used'];
                    zoomCtrlAxis2.style.opacity       = axis2Used ? '1'    : '0.4';
                    zoomCtrlAxis2.style.pointerEvents = axis2Used ? 'auto' : 'none';
                }

                // Permet de récupérer les données liées au lacunes pour l'affichage dans les graphs
                // -----------------
                    var shapes = boxPlot.layout.shapes;
                    // Groupement des shapes par la propriété personnalisée "customType"
                    // Si shapes est bien défini et c'est un tableau, on filtre
                    if (shapes && Array.isArray(shapes)) 
                    {
                        js_shapesLacunesAxe1 = shapes.filter(shape => shape.customType === 'axe1');
                        js_shapesLacunesAxe2 = shapes.filter(shape => shape.customType === 'axe2');
                    }
                // -----------------
                // Activer les boutons reverse echelle et log
                if(!reload)
                {
                    addLogScaleButton('plot_0','log-button1','yaxis');
                    addLogScaleButton('plot_0','log-button2','yaxis2');

                    addReverseButton('plot_0','reverse-button1','yaxis');
                    addReverseButton('plot_0','reverse-button2','yaxis2');
                }

                const resizeObserver = new ResizeObserver(function() {
                    Plotly.Plots.resize('plot_0');
                });
                resizeObserver.observe(document.getElementById('plot_0'));

            }
        };
        

        // Envoyer les données JSON au serveur
        xhr.send(jsonDataGraph);
    }

    load_graph();


    


    // Options du graphique

    // ---------------------------------
    // Nom et unités d'axe
    titleAxe1.addEventListener('change', function() {
        // Récupère les données de l'option sélectionnée
        const selectedOption = this.options[this.selectedIndex];
        const nom = selectedOption.getAttribute('data-nom');
        const unite = selectedOption.getAttribute('data-unite');

        // Met à jour le titre de l'axe Y (par exemple)
        Plotly.relayout('plot_0', {
                'yaxis.title.text': nom + ' (' + unite + ')'
            });
    });

    titleAxe2.addEventListener('change', function() {
        // Récupère les données de l'option sélectionnée
        const selectedOption = this.options[this.selectedIndex];
        const nom = selectedOption.getAttribute('data-nom');
        const unite = selectedOption.getAttribute('data-unite');

        // Met à jour le titre de l'axe Y (par exemple)
        Plotly.relayout('plot_0', {
                'yaxis2.title.text': nom + ' (' + unite + ')'
            });
    });
    
    
    // ---------------------------------
    // Couleur et épaisseur Traces
    // ---------------------------------
    // Color picker: same model as the "1 graph per chronicle" page.
    //   - dropdownList_<input_id> is a .color-grid (42-color rich palette)
    //   - it opens via position:fixed, computed from the swatch's
    //     getBoundingClientRect, so the sidebar's overflow-y:auto can't
    //     clip it.

        function toggleDropdownColor(input_id)
        {
            let dropdown = document.getElementById('dropdownList_' + input_id);
            if (!dropdown) return;

            // Close every other open grid (only one open at a time)
            document.querySelectorAll('.color-grid.is-open').forEach(function (g) {
                if (g !== dropdown) g.classList.remove('is-open');
            });

            // Toggle off if it was already open
            if (dropdown.classList.contains('is-open')) {
                dropdown.classList.remove('is-open');
                return;
            }

            // Compute fixed position from the swatch's screen coords.
            // The grid is position:fixed so it escapes any overflow:auto
            // ancestor (the sidebar in our case).
            var swatch = document.getElementById('selectedColor_' + input_id);
            if (swatch) {
                var rect       = swatch.getBoundingClientRect();
                var gridWidth  = 192;
                var gridHeight = 230;  // approx 7 rows x 28px + padding
                var margin     = 4;

                // Default: below the swatch, left-aligned
                var top  = rect.bottom + margin;
                var left = rect.left;

                // Flip above if it would overflow the viewport bottom
                if (top + gridHeight > window.innerHeight) {
                    top = Math.max(margin, rect.top - gridHeight - margin);
                }
                // Shift left if it would overflow the viewport right
                if (left + gridWidth > window.innerWidth) {
                    left = Math.max(margin, window.innerWidth - gridWidth - margin);
                }

                dropdown.style.top  = top + 'px';
                dropdown.style.left = left + 'px';
            }

            dropdown.classList.add('is-open');
        }

        // Close any open color grid when clicking outside a swatch/grid
        document.addEventListener('click', function (event) {
            if (!event.target.closest('.color-dropdown')) {
                document.querySelectorAll('.color-grid.is-open').forEach(function (g) {
                    g.classList.remove('is-open');
                });
            }
        });

        function selectColor(color, input_id)
        {
            // Update the visible swatch
            document.getElementById('selectedColor_' + input_id).style.backgroundColor = color;

            // Close the grid
            var grid = document.getElementById('dropdownList_' + input_id);
            if (grid) grid.classList.remove('is-open');

            // Persist the choice in the hidden input
            document.getElementById('input_color_' + input_id).value = color;

            // Update the "is-selected" highlight inside the grid so the next
            // open shows the right cell as picked.
            if (grid) {
                grid.querySelectorAll('.color-cell').forEach(function (cell) {
                    var cellColor = rgbToHex(cell.style.backgroundColor);
                    if (cellColor && cellColor.toLowerCase() === color.toLowerCase()) {
                        cell.classList.add('is-selected');
                    } else {
                        cell.classList.remove('is-selected');
                    }
                });
            }

            // Apply the color to every Plotly trace tagged with the matching
            // legendgroup (same as before — this is what makes a single click
            // recolor every variant of the same series, bars/lines/markers).
            const plot = document.getElementById('plot_0');
            if (!plot) return;

            const traces = (plot.data && plot.data.length ? plot.data : plot._fullData) || [];
            const targetGroup = 'tdc_' + input_id;

            const idxs = [];
            for (let i = 0; i < traces.length; i++) {
                const tr = traces[i] || {};
                if (tr.legendgroup === targetGroup) { idxs.push(i); }
            }

            if (!idxs.length) return;

            Plotly.restyle(plot, {
                'marker.color':      color,  // bars, scatter markers
                'marker.line.color': color,  // marker borders
                'line.color':        color   // lines
            }, idxs);
        }

        // Helper — converts "rgb(r, g, b)" or "rgba(...)" to "#rrggbb".
        // Browsers normalize hex colors written in `style` attributes to rgb(),
        // so we re-normalize them when reading the .color-cell background.
        function rgbToHex(rgb)
        {
            if (!rgb) return '';
            if (rgb[0] === '#') return rgb;
            var m = rgb.match(/^rgba?\((\d+),\s*(\d+),\s*(\d+)/);
            if (!m) return rgb;
            return '#' + ((1 << 24)
                | (parseInt(m[1]) << 16)
                | (parseInt(m[2]) <<  8)
                |  parseInt(m[3])).toString(16).slice(1);
        }

        function bumpLineWidth(input_id, delta)
        {
            let anyAppliedWidth = null; // pour afficher une valeur indicative dans le <span>

            const plot = document.getElementById('plot_0');
            if (!plot) return;

            const data = plot.data || [];
            const forLines = [], lineWidths = [];
            const forBars  = [], barWidths  = [];

            const traces = (plot.data && plot.data.length ? plot.data : plot._fullData) || [];
            const targetGroup = 'tdc_' + input_id;
            
            for (let i=0; i<data.length; i++)
            {
                const tr = traces[i] || {};
                const lg = tr.legendgroup;

                //console.log(lg+' - '+targetGroup)
                
                if (lg === targetGroup)
                {
                    // --- calcul des nouvelles largeurs ---
                    if ((tr.type === 'scatter' || tr.type === 'scattergl') && (tr.mode || '').includes('lines')) 
                    {
                        const current = (tr.line && typeof tr.line.width === 'number') ? tr.line.width : 2;
                        const next    = Math.max(0.1, +(current + delta).toFixed(2));
                        forLines.push(i);
                        lineWidths.push(next);
                        anyAppliedWidth = next;
                    } 
                    else if (tr.type === 'bar') 
                    {
                        const current = (tr.marker && tr.marker.line && typeof tr.marker.line.width === 'number') ? tr.marker.line.width : 0;
                        const next    = Math.max(0.1, +(current + delta).toFixed(2));
                        forBars.push(i);
                        barWidths.push(next);
                        anyAppliedWidth = next;
                    }

                }
            }   

            if (forLines.length)
            {
                // chaque valeur de lineWidths s’applique à la trace correspondante de forLines
                Plotly.restyle(plot, { 'line.width': lineWidths }, forLines);
            }
            if (forBars.length)
            {
                Plotly.restyle(plot, { 'marker.line.width': barWidths }, forBars);
            }
        }

    // ---------------------------------
    // Affichage des lacunes

        // Écouteur d'événement pour le checkbox Lacune checkLac
        check_lac.addEventListener('change', updateShapes);

        function updateShapes() 
        {
            // Initialise un tableau vide pour les formes à afficher
            var shapesToDisplay = [];

            // Ajoute les formes de l'axe 1 si la checkbox est cochée
            if (checkLac.checked) 
                {
                shapesToDisplay = shapesToDisplay
                                    .concat(js_shapesLacunesAxe1)
                                    .concat(js_shapesLacunesAxe2);
            }


            // Met à jour le tracé avec les formes combinées
            Plotly.relayout('plot_0', { 'shapes': shapesToDisplay });
        }

    // ---------------------------------
    // ECHELLE REVERSE
    
    // Fonction Echelle Inversion axe
    function addReverseButton(plotId, logButtonId, axe) 
    {
        const button = document.getElementById(logButtonId);
        const graphContainer = document.getElementById(plotId);

        var yReversed = false;

       
        button.addEventListener('click', function () 
        {
            const plotlyLayout = graphContainer._fullLayout;
             
            if(axe === 'yaxis' || axe === 'yaxis2') 
            {
                const axis = plotlyLayout[axe];

                // Vérifiez si l'axe existe dans le layout
                if(axis) 
                {
                    const current_range = axis.range;

                    // Inversez simplement les valeurs de la plage
                    const reversed_range = [current_range[1], current_range[0]];

                    Plotly.relayout(plotId,{[axe + '.range']: reversed_range});

                    if(axe === 'yaxis')
                    {
                        yMin1.value = parseInt(current_range[1]);
                        yMax1.value = parseInt(current_range[0]);
                    }
                    if(axe === 'yaxis2')
                    {
                        yMin2.value = parseInt(current_range[1]);
                        yMax2.value = parseInt(current_range[0]);
                    }

                    yReversed = !yReversed; // Inversez l'état
                }
            }
        });
    }

    // Fonction Echelle Log
    function addLogScaleButton(plotId, logButtonId, axe) 
    {
        const button = document.getElementById(logButtonId);
        const graphContainer = document.getElementById(plotId);

        let logScaleEnabled = false;

        button.addEventListener('click', function () {
            const plotlyLayout = graphContainer._fullLayout;

            if (axe === 'yaxis' || axe === 'yaxis2') {
                const axis = plotlyLayout[axe];

                // Activer/désactiver l'échelle logarithmique
                const newType = logScaleEnabled ? 'linear' : 'log';

                Plotly.relayout(plotId, { [axe + '.type']: newType });
                logScaleEnabled = !logScaleEnabled; // Inverser l'état
            }
        });
    }

    // ---------------------------------
    // Graph plein écran dans popup

    var zoom_graph_first = true;
    function zoom_graph(code_station,nom_station,type_data)
    {
        document.getElementById('box_graph').style.display='block';

        document.getElementById('titre_graph').innerHTML = '<?php echo TEXT_COMBINED_GRAPH; ?>';

        // Récupérer le nom du plot
        var plotName = 'plot_0';

        // Récupérer les données et la mise en page du plot
        var plotData = window[plotName].data;
        var plotLayout = window[plotName].layout;

        Plotly.newPlot('cadre_limit', plotData, plotLayout, config);

        if(zoom_graph_first)
        {
            /*
            addLogScaleButton('cadre_limit','log-button_gd_1','yaxis'); 
            addLogScaleButton('cadre_limit','log-button_gd_2','yaxis2'); 

            addReverseButton('cadre_limit','reverse-button_gd_1','yaxis'); 
            addReverseButton('cadre_limit','reverse-button_gd_2','yaxis2'); 
            */

            zoom_graph_first = false;
        }
        
    }


    

    // Fonction ajoutant ou enlevant des décimal sur un axe 
    function updateDecimals(plotId, axe, type) 
    {
        var newTickFormat = '';

        if(axe == 'yaxis')
        {
            if (type == '+' && decimalPlacesY1 < 6){decimalPlacesY1++;}
            if (type == '-' && decimalPlacesY1 > 0){decimalPlacesY1--;}
            newTickFormat = '.' + decimalPlacesY1 + 'f';
        }

        if(axe == 'yaxis2')
        {
            if (type == '+' && decimalPlacesY2 < 6){decimalPlacesY2++;}
            if (type == '-' && decimalPlacesY2 > 0){decimalPlacesY2--;}
            newTickFormat = '.' + decimalPlacesY2 + 'f';
        }

        Plotly.relayout(plotId, {[axe + '.tickformat']: newTickFormat});
    }


    function updateGraphRange() 
    {
        if(!isValidDatesInput(dateFirst,dateEnd))
        {
           msgInfo.style.border = '2px solid #930000'; 
           return; // Action stoppée
        }

        // Vérifier si les valeurs sont bien des entiers
        
        if (!isNumber(yMin1) || !isNumber(yMax1) || !isNumber(yMin2) || !isNumber(yMax2))
        {            
            msgInfo.style.border = '2px solid #930000'; 
            return;
        }

        // Récupérer les valeurs des champs d'entrée
        var dateFirstInput = dateFirst.value;
        var dateEndInput = dateEnd.value;

        // Convertir les dates du format 'dd-mm-yyyy' au format 'yyyy-mm-dd'
        var dateFirstParts = dateFirstInput.split('-');
        var dateEndParts = dateEndInput.split('-');

        var dateFirstFormatted = dateFirstParts[2]+'-'+dateFirstParts[1]+'-'+dateFirstParts[0];
        var dateEndFormatted = dateEndParts[2]+'-'+dateEndParts[1]+'-'+dateEndParts[0];

        // Récupérer l'état actuel des axes
        var layout = document.getElementById('plot_0')._fullLayout;


        // Vérification de l'inversion des axes
        var isXAxisValid = false;
        if (layout.xaxis) {
            isXAxisReversed = layout.xaxis.range[0] > layout.xaxis.range[1];
            isXAxisValid = true;
        }

        var isYAxisValid = false;
        if (layout.yaxis) {
            isYAxisReversed = layout.yaxis.range[0] > layout.yaxis.range[1];
            isYAxisValid = true;
        }

        var isYAxis2Valid = false;
        if (layout.yaxis2) {
            isYAxis2Reversed = layout.yaxis2.range[0] > layout.yaxis2.range[1];
            isYAxis2Valid = true;
        }

        // Mise à jour des plages des axes
        if (isYAxisValid && isYAxis2Valid) {
            Plotly.relayout('plot_0', {
                'xaxis.range': isXAxisReversed ? [dateEndFormatted, dateFirstFormatted] : [dateFirstFormatted, dateEndFormatted],
                'yaxis.range': isYAxisReversed ? [yMax1.value, yMin1.value] : [yMin1.value, yMax1.value],
                'yaxis2.range': isYAxis2Reversed ? [yMax2.value, yMin2.value] : [yMin2.value, yMax2.value]
            });
        } else if (!isYAxisValid && isYAxis2Valid) {
            Plotly.relayout('plot_0', {
                'xaxis.range': isXAxisReversed ? [dateEndFormatted, dateFirstFormatted] : [dateFirstFormatted, dateEndFormatted],
                'yaxis2.range': isYAxis2Reversed ? [yMax2.value, yMin2.value] : [yMin2.value, yMax2.value]
            });
        } else if (isYAxisValid && !isYAxis2Valid) {
            Plotly.relayout('plot_0', {
                'xaxis.range': isXAxisReversed ? [dateEndFormatted, dateFirstFormatted] : [dateFirstFormatted, dateEndFormatted],
                'yaxis.range': isYAxisReversed ? [yMax1.value, yMin1.value] : [yMin1.value, yMax1.value]
            });
        }

        
        
    }

    // Fonction Graph Full Screen
    // Pour mettre le graphique en plein écran, pas sûr que l'on utilise cette fonction
    //document.getElementById('buttonFullSreen').addEventListener('click', toggleFullScreen);
    function toggleFullScreen() 
    {
        var graphDiv = document.getElementById('plot_0');
        if (!document.fullscreenElement) 
        {
            graphDiv.classList.add("fullscreen");
            graphDiv.requestFullscreen();
        } 
        else 
        {
            document.exitFullscreen();
            graphDiv.classList.remove("fullscreen");
        }
    }


    // Fonction de gestion du zoom et du pan par l'utilisateur
    function zoomCTRL()
    {
        var checkZoomX = document.getElementById('check_zoom_x');
        var checkZoomY = document.getElementById('check_zoom_y');

        var fixedRangeX = true; // Par défaut, désactive le zoom horizontal
        var fixedRangeY = true; // Par défaut, désactive le zoom vertical
        
        if(checkZoomX.checked && checkZoomY.checked)
        {
            fixedRangeX = false; // Active le zoom horizontal
            fixedRangeY = false; // Active le zoom vertical            
        }
        else if(checkZoomX.checked)
        {
            fixedRangeX = false; // Active le zoom horizontal
            fixedRangeY = true; // Désactive le zoom vertical            
        }
        else if(checkZoomY.checked)
        {
            fixedRangeX = true; // Désactive le zoom horizontal
            fixedRangeY = false; // Active le zoom vertical
        }
        

        Plotly.relayout('plot_0', 
        {
            'xaxis.fixedrange': fixedRangeX,
            'yaxis.fixedrange': fixedRangeY,
            'yaxis2.fixedrange': fixedRangeY,
        });
    }

    // ---------------------------------
    // Fonction Vérification des Dates Heures
    
    function isValidDatesInput(date1Input,date2Input)
    {   
        // Vérifier si les dates sont valides
        if (isValidDate(date1Input.value) && isValidDate(date2Input.value))
        {
            
                // Convertir dates et heures en objets Date complets
                const date1Format = parseDate(date1Input.value); // Obtenez un objet Date à partir de la date
                const date2Format = parseDate(date2Input.value); // Obtenez un objet Date à partir de la date

                // Comparer les deux dates complètes
                if (date1Format < date2Format) 
                {
                    return true;
                } 
                else 
                {
                    msgInfo.innerText = '<?php echo TEXT_START_BEFORE_END; ?>';
                    msgInfo.style.display = 'block';

                    return false;
                }
        } 
        else 
        {
            msgInfo.innerText ='<?php echo TEXT_INVALID_DATE_FORMAT; ?>';
            msgInfo.style.display = 'block';

            return false;
        }  
    }

    // Fonction pour valider une date réelle
    function isValidDate(dateString) 
    {
        // Vérifier le format avec une regex
        const dateRegex = /^(0[1-9]|[12][0-9]|3[01])-(0[1-9]|1[0-2])-(\d{4})$/;
        if (!dateRegex.test(dateString)) 
        {
            return false; // Format invalide
        }

        // Découper la date
        const [day, month, year] = dateString.split("-").map(Number);

        // Créer une date JavaScript et vérifier sa validité
        const date = new Date(year, month - 1, day); // Mois commence à 0 en JS
        return (
            date.getFullYear() === year &&
            date.getMonth() === month - 1 &&
            date.getDate() === day
        );
    }

    // Fonction pour convertir une date (format valide) en objet Date
    function parseDate(dateString) 
    {
        [day, month, year] = dateString.split("-").map(Number);
        return new Date(year, month - 1, day);
    }

    // Fonction pour vérifier si une valeur est un entier
    function isInteger(inputElement) 
    {
        // Vérifie si la valeur de l'élément d'entrée est un entier
        if (!Number.isInteger(Number(inputElement.value))) 
        {
            // Affiche un message d'erreur
            msgInfo.innerText = "Erreur : Les champs des Axes (1 et 2) doivent être des nombres entiers.\n";
            msgInfo.style.display = 'block';
            return false;
        }
        return true;
    }

    // Fonction pour vérifier si une valeur est un nombre (entier ou flottant)
    function isNumber(inputElement) 
    {
        // Vérifie si la valeur de l'élément d'entrée est un nombre
        const value = Number(inputElement.value);
        if (isNaN(value)) {
            // Affiche un message d'erreur
            msgInfo.innerText = "Erreur : Les champs des Axes (1 et 2) doivent être des nombres.\n";
            msgInfo.style.display = 'block';
            return false;
        }
        return true;
    }


</script>