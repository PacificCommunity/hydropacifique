<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Piezometric diagraphy comparison page — lists all piezometric stations
that have at least one diagraphy record, lets the user check stations,
then opens a popup panel (block_diag.php) where selected well logs
are compared side-by-side on a Plotly depth vs conductivity chart.
----------------------------------------
*/

require('include/application_top.php');

$row = 0;

// -----------------------------------------------
// Defaults: sort by last diagraphy date, descending

$tri_encours = 0;
$tri         = "last_date_heure_ra";
if (isset($_POST['select_tri']))
{
    $tri_encours = $_POST['select_tri'];
    if ($tri_encours == 1) { $tri = "s.nom_station"; }
    if ($tri_encours == 2) { $tri = "s.code_station"; }
}

$tri_order_encours = 2;
$tri_order         = " DESC,";
if (isset($_POST['order_tri']))
{
    $tri_order_encours = $_POST['order_tri'];
    if ($tri_order_encours == 1) { $tri_order = " ASC,"; }
    if ($tri_order_encours == 2) { $tri_order = " DESC,"; }
}

// Only piezometric stations (station_type = 5)
$where_and_type_piezo = " AND s.station_type=5";


// -----------------------------------------------
// Station filter (injected by filtre_stations_var.php)

$affiche_select_from          = true;
$affiche_select_type          = false;
$affiche_select_tournee       = false;
$affiche_search               = true;
$affiche_select_riviere       = false;
$affiche_select_station       = true;
$affiche_select_statut_station = true;
require(DIR_WS_FILTRE . 'filtre_stations_var.php');


// -----------------------------------------------
// Station list query — only stations with at least one diagraphy

$station_array      = [];
$nb_station         = 0;
$nb_station_active  = 0;
$nb_station_suivi   = 0;
$nb_station_armee   = 0;

$station_query = tep_db_query($sql_link,
    "SELECT s.id_station, s.id_commune, s.nom_station, s.code_station,
            s.active_station, s.suivi, s.armee,
            c.nom_commune,
            COUNT(DISTINCT pp.id_ra) AS nb_diag,
            MAX(r.date_heure_ra)     AS last_date_heure_ra
     FROM "   . TABLE_STATION              . " s
     LEFT JOIN " . TABLE_STATION_TO_TOURNEE . " t  ON t.id_station  = s.id_station
     LEFT JOIN " . TABLE_COMMUNE             . " c  ON s.id_commune  = c.id_commune
     LEFT JOIN " . TABLE_DATA_RA             . " r  ON r.id_station  = s.id_station
     LEFT JOIN " . TABLE_DATA_RA_PIEZO_PROFIL . " pp ON pp.id_ra     = r.id_ra
     WHERE s.id_territoire=" . $territoire_id . $where_and_from
    . $where_search . $where_and_regionhydro . $where_and_region . $where_and_commune
    . $where_and_riviere . $where_and_type_piezo . $where_and_tournee . $where_and_station
    . $where_and_active . $where_and_suivi . $where_and_armee
    . " GROUP BY s.id_station, s.id_commune, s.nom_station, s.code_station,
                 s.active_station, s.suivi, s.armee, c.nom_commune
       HAVING nb_diag > 0
       ORDER BY " . $tri . $tri_order . " s.active_station ASC, s.suivi DESC, s.armee ASC");

while ($station = tep_db_fetch_array($station_query))
{
    $code_station = html_entity_decode($station['code_station'] ?? '');
    $nom_station  = html_entity_decode($station['nom_station']  ?? '');
    $nom_commune  = html_entity_decode($station['nom_commune']  ?? '');
    $nb_diag      = $station['nb_diag'];

    $last_date_heure_ra  = $station['last_date_heure_ra'] ?? null;
    $formatted_last_date = null;
    if (is_string($last_date_heure_ra) && $last_date_heure_ra !== '' && $last_date_heure_ra !== '0000-00-00 00:00:00')
    {
        $formatted_last_date = (new DateTime($last_date_heure_ra))->format('d-m-Y');
    }

    $active_station = 0;
    if ($station['active_station'] == 1) { $active_station = 1; $nb_station_active++; }

    $suivi_station = 0;
    if ($station['suivi'] == 1)          { $suivi_station = 1;  $nb_station_suivi++; }

    $armee_station = 0;
    if ($station['armee'] == 1)          { $armee_station = 1;  $nb_station_armee++; }

    $station_array[$station['id_station']] = [
        'code_station'       => $code_station,
        'nom_station'        => $nom_station,
        'nom_commune'        => $nom_commune,
        'nb_diag'            => $nb_diag,
        'formatted_last_date' => $formatted_last_date,
        'active_station'     => $active_station,
        'suivi_station'      => $suivi_station,
        'armee_station'      => $armee_station,
    ];
}
$nb_stations = count($station_array);


// -----------------------------------------------
// HTML output

require(DIR_WS_STRUCTURE . 'header_web.php');
echo "<body>";

    echo "<div id='contenu_info' style='display:none;'></div>";

    require(DIR_WS_DIAG    . 'block_diag.php');
    require(DIR_WS_STRUCTURE . 'header.php');
    include(DIR_WS_BOX       . 'nav_accueil.php');

    echo "<div id='contour_general'>";
        echo "<div id='contenu_centre'>";
            echo "<div id='contenu_box2'>";

                echo "<h1><span>" . TEXT_DG_PAGE_TITLE . "</span></h1>";

                $lien_form = tep_href_link('data_diag_piezo.php');
                $name_form = 'form_select_diag';
                echo "<form name='" . $name_form . "' id='" . $name_form . "'"
                   . " action='" . $lien_form . "' method='post' enctype='multipart/form-data'>";

                    echo "<div id='cadre_graph' style='float:left;width:272px;max-height:80vh;overflow-y: auto;'>\n";

                        // ---- Show well logs button ----
                        // Same sober style as the "New FR - ..." buttons
                        // in list_ra.php: a bare #button_titre <div>
                        // (thin black underline, no wrapper card). The
                        // previous heavy 'boxpopup' frame was visually
                        // out of place next to the filter panel below.
                        echo "<div style='text-align:center;margin-bottom:10px;'>";
                            echo "<div id='button_titre'"
                               . " style='display:inline-block;box-sizing:border-box;padding:6px 30px;'"
                               . " onclick='load_data_diag();'>";
                                echo TEXT_DG_BTN_SHOW;
                            echo "</div>";
                        echo "</div>";

                        // ---- Filter + sort panel ----
                        echo "<div id='boxpopup' class='select-top' style='width:235px;margin:0;padding: 0 3%;padding-top:10px;'>\n";

                            require(DIR_WS_FILTRE . 'filtre_stations_html.php');
                            echo "<hr>";

                            // Sort field
                            echo "<div style='width:100%;border-bottom:2px solid #176B87;margin-top:5px;'></div>";
                            echo "<p style='float:left;padding-top:5px;color:#186F65;margin-top:15px;'>"
                               . TEXT_DG_SORT_LABEL . "</p>";

                            echo "<select name='select_tri' id='select_tri'"
                               . " onchange='" . $name_form . ".submit();' style='float:right;width:140px;margin-top:15px;'>";
                                $selected = ($tri_encours == 1) ? 'selected' : '';
                                echo "<option value='1' " . $selected . ">" . TEXT_DG_SORT_NAME . "</option>";
                                $selected = ($tri_encours == 2) ? 'selected' : '';
                                echo "<option value='2' " . $selected . ">" . TEXT_DG_SORT_CODE . "</option>";
                            echo "</select>";
                            echo "<hr>";

                            // Sort order
                            echo "<div style='float:right;'>";
                                $asc_checked  = ($tri_order_encours == 1) ? 'checked' : '';
                                $desc_checked = ($tri_order_encours == 2) ? 'checked' : '';

                                echo "<p style='float:left;width:55px;padding-top:3px;'>" . TEXT_DG_ORDER_ASC  . "</p>";
                                echo "<input type='radio' id='asc'  name='order_tri' value='1' style='float:left;'"
                                   . " " . $asc_checked  . " onchange='" . $name_form . ".submit();'>";
                                echo "<p style='float:left;width:65px;margin-left:10px;padding-top:3px;'>" . TEXT_DG_ORDER_DESC . "</p>";
                                echo "<input type='radio' id='desc' name='order_tri' value='2' style='float:left;'"
                                   . " " . $desc_checked . " onchange='" . $name_form . ".submit();'>";
                            echo "</div>";

                            // Station count
                            echo "<div id='contenu_infos' style='width:97%;margin-top:10px;'>";
                                echo "<p><span>" . TEXT_DG_NB_STATIONS
                                   . number_format($nb_stations, 0, '.', ' ') . "</span></p>";
                            echo "</div>";

                        echo "</div>";

                    echo "</div>"; // cadre_graph

                echo "</form>";


                // -----------------------------------------------
                // Station table

                if (!empty($station_array) && $nb_stations > 0)
                {
                    echo "<div class='table-container' style='float:none;width:auto;height:75vh;'>";
                        echo "<div style='height:75vh;overflow-y: auto;'>";
                            echo "<table id='table_tri' cellspacing='0'>";

                                echo "<thead>";
                                    echo "<tr class='header-row'>";
                                        echo "<th style='width:60px;text-align:center;'"
                                           . " title='" . TEXT_DG_COL_STATUS_TITLE . "'>"  . TEXT_DG_COL_STATUS   . "</th>";
                                        echo "<th style='width:60px;text-align:center;'"
                                           . " title='" . TEXT_DG_COL_SUIVI_TITLE . "'>"   . TEXT_DG_COL_SUIVI    . "</th>";
                                        echo "<th style='width:100px;padding-left:15px;'>" . TEXT_DG_COL_CODE     . "</th>";
                                        echo "<th style='width:220px;padding-left:15px;'>" . TEXT_DG_COL_NAME     . "</th>";
                                        echo "<th style='width:120px;padding-left:15px;'>" . TEXT_DG_COL_COMMUNE  . "</th>";
                                        echo "<th style='width:80px;text-align:center;'"
                                           . " title='" . TEXT_DG_COL_NB_DIAG_TITLE . "'>" . TEXT_DG_COL_NB_DIAG  . "</th>";
                                        echo "<th style='width:120px;text-align:center;'"
                                           . " title='" . TEXT_DG_COL_LAST_DIAG_TITLE . "'>" . TEXT_DG_COL_LAST_DIAG . "</th>";
                                        echo "<th style='width:80px;text-align:center;cursor:pointer;'"
                                           . " title='" . TEXT_DG_COL_SELECT_TITLE . "' onclick='toggleCheckboxes();'>";
                                            echo "<span class='selectAll'>" . TEXT_DG_COL_SELECT . "</span>";
                                        echo "</th>";
                                    echo "</tr>";
                                echo "</thead>";

                                echo "<tr><td colspan='6' style='height:10px;'>&nbsp;</td></tr>";

                                foreach ($station_array as $key => $value)
                                {
                                    if (fmod($row, 2) == 0)
                                    { $row_l = "class='row1' onmouseover=\"this.className='row1hover';\" onmouseout=\"this.className='row1';\" "; }
                                    else
                                    { $row_l = "class='row2' onmouseover=\"this.className='row2hover';\" onmouseout=\"this.className='row2';\" "; }

                                    echo "<tr " . $row_l . ">";

                                        // Status icon
                                        echo "<td style='text-align:center;'>";
                                        if ($value['active_station'] === 1)
                                        { echo "<img src='" . DIR_WS_IMG_ICO . "puce_verte.png' style='width:12px;' title='" . TEXT_DG_STATUS_ACTIVE . "'>"; }
                                        else
                                        { echo "<img src='" . DIR_WS_IMG_ICO . "puce_rouge.png' style='width:12px;' title='" . TEXT_DG_STATUS_CLOSED . "'>"; }
                                        echo "</td>\n";

                                        // Monitoring icon
                                        echo "<td style='text-align:center;'>";
                                        if ($value['suivi_station'] === 1)
                                        { echo "<img src='" . DIR_WS_IMG_ICO . "puce_verte.png' style='width:12px;' title='" . TEXT_DG_SUIVI_CONTINU  . "'>"; }
                                        else
                                        { echo "<img src='" . DIR_WS_IMG_ICO . "puce_rouge.png' style='width:12px;' title='" . TEXT_DG_SUIVI_PONCTUEL . "'>"; }
                                        echo "</td>\n";

                                        echo "<td style='padding-left:15px;'>"  . $value['code_station'] . "</td>\n";
                                        echo "<td style='padding-left:15px;'>"  . affichelettres($value['nom_station'], 50) . "</td>\n";
                                        echo "<td style='padding-left:15px;'>"  . $value['nom_commune']       . "</td>\n";
                                        echo "<td style='text-align:center;'>"  . $value['nb_diag']           . "</td>\n";
                                        echo "<td style='text-align:center;'>"  . $value['formatted_last_date'] . "</td>\n";

                                        // Select checkbox
                                        echo "<td style='text-align:center;'>";
                                            echo "<input type='checkbox' name='check_station_diag[]' value='" . $key . "'>";
                                        echo "</td>\n";

                                    echo "</tr>\n";
                                    $row++;
                                }

                            echo "</table>";
                        echo "</div>";
                    echo "</div>";
                }
                else
                {
                    echo "<div id='boxpopup'>\n";
                        echo "<p class='alert'>" . TEXT_DG_NO_STATION . "</p>";
                    echo "</div>";
                }

            echo "<hr>";
            echo "</div>"; // contenu_box2

        echo "<hr>";
        echo "</div>"; // contenu_centre

    echo "<hr>";
    echo "</div>"; // contour_general

    require('include/application_bottom.php');
echo "</body>";
echo "</html>";
?>


<script>

    var idUser = <?php echo $id_user; ?>;

    var contenuInfo  = document.getElementById('contenu_info');
    var boxTabWait   = document.getElementById('wait_tab');
    var boxTab       = document.getElementById('cadre_data_station_lgt');
    var boxGraphWait = document.getElementById('wait_graph');
    var boxPlot      = document.getElementById('plotDiag');
    var boxDiag      = document.getElementById('box_diag');

    var msgNoDiag = <?php echo json_encode(TEXT_DG_ERR_NO_DIAG); ?>;


    // -----------------------------------------------
    // load_data_diag()
    // Collects checked station IDs, requests the diagraphy selection
    // table from process_diag_tab.php, then auto-loads the chart.

    function load_data_diag()
    {
        var checkboxes = document.querySelectorAll('input[name="check_station_diag[]"]');
        var selectedStations = [];
        checkboxes.forEach(function(cb) { if (cb.checked) { selectedStations.push(cb.value); } });

        if (selectedStations.length < 1)
        {
            contenuInfo.innerHTML     = msgNoDiag;
            contenuInfo.style.border  = '2px solid #930000';
            contenuInfo.style.display = 'block';
            return;
        }

        boxDiag.style.display    = 'block';
        boxTabWait.style.display = 'block';
        boxTab.style.display     = 'none';

        var xhr = new XMLHttpRequest();
        xhr.open("POST", "include/structure/diag/process_diag_tab.php", true);
        xhr.setRequestHeader("Content-Type", "application/json");

        xhr.onreadystatechange = function()
        {
            if (xhr.readyState === 4 && xhr.status === 200)
            {
                var r = JSON.parse(xhr.responseText);
                boxTab.innerHTML         = r['html_text'];
                boxTabWait.style.display = 'none';
                boxTab.style.display     = 'block';

                // Note: drag + resize are now handled directly in block_diag.php
                // (same pattern as the ETL popups). No call to
                // initDraggableResize() is needed here.
                load_graph_diag();
            }
        };
        xhr.send(JSON.stringify({ listStation: selectedStations.join(',') }));
    }


    // -----------------------------------------------
    // load_graph_diag()
    // Collects checked diagraphy IDs from the popup list and requests
    // the Plotly chart JS from process_diag_graph.php.

    function load_graph_diag()
    {
        var checkboxes = document.querySelectorAll('input[name="check_diag[]"]');
        var selectedDiag = [];
        checkboxes.forEach(function(cb)
        {
            if (cb.checked) { selectedDiag.push(cb.value.split('_')[1]); }
        });

        if (selectedDiag.length < 1)
        {
            contenuInfo.innerHTML     = msgNoDiag;
            contenuInfo.style.border  = '2px solid #930000';
            contenuInfo.style.display = 'block';
            return;
        }

        boxPlot.style.display        = 'none';
        boxGraphWait.style.display   = 'block';

        var xhr = new XMLHttpRequest();
        xhr.open("POST", "include/structure/diag/process_diag_graph.php", true);
        xhr.setRequestHeader("Content-Type", "application/json");

        xhr.onreadystatechange = function()
        {
            if (xhr.readyState === 4 && xhr.status === 200)
            {
                var r = JSON.parse(xhr.responseText);
                boxPlot.style.display      = 'block';
                boxGraphWait.style.display = 'none';
                eval(r['js_graph']);
                Plotly.relayout('plotDiag', {});

                // Apply the per-row tinting that replaces the now-hidden
                // chart legend: each diagraphy cell whose id_ra appears
                // in the server's colour map gets a pastel background
                // matching its trace colour. Cells not in the map (i.e.
                // well logs that were unchecked since the last refresh)
                // are reset to the default appearance.
                lastColorMap = r['colors'] || {};
                applyDiagRowColors(lastColorMap);

                // If we were in edit mode but the edited log is no longer
                // among the checked well logs, exit edit mode silently.
                if (editingRaId !== null && !lastColorMap[editingRaId]) {
                    exitEditMode();
                } else if (editingRaId !== null) {
                    // Refresh the target dropdown in case the set of
                    // checked logs changed (without losing the active one)
                    refreshEditTargetSelect();
                    applyEditVisualStyle();
                }
            }
        };
        xhr.send(JSON.stringify({ listDiag: selectedDiag.join(',') }));
    }


    // ---- Edit-mode state ----
    // editingRaId    : id_ra of the diagraphy currently in edit mode,
    //                  or null in normal/read mode
    // editDirty      : true if at least one point has been moved,
    //                  added or removed since enterEditMode()
    // lastColorMap   : { id_ra: hex, ... } returned by the last
    //                  process_diag_graph.php call; needed to restore
    //                  trace colour when we switch back to normal mode
    // editDragState  : transient state during a single drag (mousedown
    //                  → mouseup); see mousedown handler below
    var editingRaId   = null;
    var editDirty     = false;
    var lastColorMap  = {};
    var editDragState = null;


    // -----------------------------------------------
    // applyDiagRowColors(colorMap)
    // Tints every left-column cell tagged data-id-ra with the trace
    // colour of the matching diagraphy. Cells whose id_ra isn't in the
    // map are reset (background cleared). The pastel tint = trace
    // colour at ~22% opacity (hex #RRGGBB → rgba with alpha).
    //
    // We tint BOTH td.diag-cell (the date) AND td.diag-cell-check
    // (the checkbox), so the full row reads as "linked to this curve".

    function applyDiagRowColors(colorMap)
    {
        var cells = document.querySelectorAll('.diag-cell, .diag-cell-check');
        cells.forEach(function(cell) {
            var idRa = cell.getAttribute('data-id-ra');
            var hex  = idRa ? colorMap[idRa] : null;
            if (hex) {
                cell.style.backgroundColor = hexToPastel(hex);
            } else {
                cell.style.backgroundColor = '';
            }
        });
    }


    // Convert "#RRGGBB" or "RRGGBB" to a pastel rgba() string with
    // alpha=0.22 — bright enough to be readable, soft enough to keep
    // the text on top legible.
    function hexToPastel(hex)
    {
        if (!hex) { return ''; }
        var h = hex.charAt(0) === '#' ? hex.substring(1) : hex;
        if (h.length !== 6) { return ''; }
        var r = parseInt(h.substring(0, 2), 16);
        var g = parseInt(h.substring(2, 4), 16);
        var b = parseInt(h.substring(4, 6), 16);
        if (isNaN(r) || isNaN(g) || isNaN(b)) { return ''; }
        return 'rgba(' + r + ',' + g + ',' + b + ',0.22)';
    }


    // -----------------------------------------------
    // toggleCheckboxes() — select / deselect all station checkboxes

    function toggleCheckboxes()
    {
        var checkboxes = document.querySelectorAll("input[name='check_station_diag[]']");
        var allChecked = Array.from(checkboxes).every(function(cb) { return cb.checked; });
        checkboxes.forEach(function(cb) { cb.checked = !allChecked; });
    }


    // -----------------------------------------------
    // checkboxDiagSelect()
    // Propagates a station-level checkbox state to all its diagraphy
    // checkboxes in the popup list.

    function checkboxDiagSelect()
    {
        var cb         = event.target;
        var id_station = cb.value;
        var isChecked  = cb.checked;
        document.querySelectorAll(`input[name='check_diag[]'][value^='${id_station}_']`)
            .forEach(function(c) { c.checked = isChecked; });
    }


    // -----------------------------------------------
    // Accordion toggle for station groups in the popup list

    $(document).ready(function()
    {
        $(document).on('click', '.toggle-diag', function()
        {
            // The .navdiag list used to be a direct sibling of the <p>,
            // but the header now lives inside an inner flex wrapper, so
            // .navdiag is a sibling of THAT wrapper. We walk up one
            // level (.parent()) before looking forward for it.
            var navdiag = $(this).parent().nextAll('.navdiag').first();
            navdiag.slideToggle('slow', function()
            {
                var arrow = $(this).prevAll().find('.toggle-diag .arrow').first();
                arrow.html(navdiag.is(':visible') ? '&#9650;' : '&#9660;');
            });
        });

        // Lock the checkbox of the diagraphy currently being edited:
        // toggling it off would orphan the edit session.
        $(document).on('change', "input[name='check_diag[]']", function(e) {
            if (editingRaId === null) return;
            var idRa = this.value.split('_')[1];
            if (parseInt(idRa, 10) === editingRaId && !this.checked) {
                // Re-check and warn
                this.checked = true;
                customDiagAlert(msgEditCheckLock);
            }
        });

    });


    // Delete (×) click — opens the math-challenge popup before
    // deleting the well log's points. Disabled (no-op) while in
    // edit mode (the click handler exits early); also disabled via
    // the .diag-edit-locked CSS class (pointer-events:none) so the
    // hover doesn't even register.
    //
    // Vanilla-JS delegation on document (NOT inside $(document).ready)
    // so the handler is registered as soon as the script runs, and the
    // dynamically-loaded HTML from process_diag_tab.php is matched on
    // every click without depending on jQuery's plugin chain.
    document.addEventListener('click', function(e) {
        var cell = e.target && e.target.closest ? e.target.closest('.diag-cell-del') : null;
        if (!cell) return;
        if (editingRaId !== null) return;
        var locked = cell.closest('.diag-edit-locked');
        if (locked) return;
        var idRa = parseInt(cell.getAttribute('data-id-ra'), 10);
        if (!idRa) return;
        openDeleteWellLogChallenge(idRa);
    });


    // -----------------------------------------------
    // Delete-well-log flow
    // Same math-challenge UX as the save: confirm popup with an
    // arithmetic test, then POST to process_diag_del.php. On success
    // we reload the full popup (tab + chart) so the deleted well log
    // disappears from the list and the chart.

    function openDeleteWellLogChallenge(idRa)
    {
        var prev = document.getElementById('diag_del_challenge');
        if (prev) prev.remove();

        // Math challenge (same generator as save)
        var op = ['+', '-', 'x'][Math.floor(Math.random() * 3)];
        var a, b, expected;
        if (op === '+') {
            a = Math.floor(Math.random() * 16) + 5;
            b = Math.floor(Math.random() * 14) + 2;
            expected = a + b;
        } else if (op === '-') {
            a = Math.floor(Math.random() * 21) + 10;
            b = Math.floor(Math.random() * (a - 1)) + 1;
            expected = a - b;
        } else {
            a = Math.floor(Math.random() * 8) + 2;
            b = Math.floor(Math.random() * 8) + 2;
            expected = a * b;
        }

        // Build a readable target label by looking up the matching trace
        // (name) if the well log is currently plotted, otherwise just
        // show the raw id_ra so the user has something to confirm.
        var plotEl = document.getElementById('plotDiag');
        var targetName = '';
        if (plotEl && plotEl.data) {
            plotEl.data.forEach(function(tr) {
                if (tr.meta === 'ra_' + idRa) { targetName = tr.name || ''; }
            });
        }
        if (!targetName) { targetName = 'id_ra ' + idRa; }

        var overlay = document.createElement('div');
        overlay.id = 'diag_del_challenge';
        overlay.style.cssText =
            'position:fixed;top:0;left:0;width:100vw;height:100vh;'
          + 'background:rgba(0,0,0,0.4);z-index:3000;'
          + 'display:flex;align-items:center;justify-content:center;';
        overlay.innerHTML =
            '<div style="background:#FBF9F1;border-radius:4px;width:460px;max-width:90vw;'
          + 'box-shadow:0 8px 24px rgba(0,0,0,0.3);overflow:hidden;font-family:inherit;">'
          + '<div style="background:#a32d2d;color:#fff;padding:10px 14px;font-size:14px;font-weight:bold;">'
          +   msgDelConfirmTitle
          + '</div>'
          + '<div style="padding:14px 16px;font-size:13px;line-height:1.5;color:#333;">'
          +   msgDelConfirmMsg
          +   '<div style="margin:10px 0;padding:8px 10px;background:#fff5f5;border-left:3px solid #a32d2d;font-size:12px;">'
          +     '<b>' + msgEditTarget + ' :</b> ' + escapeHtml(targetName)
          +   '</div>'
          +   '<div style="margin-top:14px;padding:10px;background:#fff;border:1px solid #ddd;border-radius:3px;">'
          +     '<div style="font-size:12px;color:#666;margin-bottom:6px;">' + msgChallengeHint + '</div>'
          +     '<div style="display:flex;align-items:center;gap:8px;">'
          +       '<span style="font-size:16px;font-weight:bold;">' + a + ' ' + op + ' ' + b + ' = </span>'
          +       '<input id="diag_del_answer" type="text" style="width:60px;font-size:16px;padding:4px;" autofocus>'
          +       '<span id="diag_del_feedback" style="font-size:12px;"></span>'
          +     '</div>'
          +   '</div>'
          + '</div>'
          + '<div style="padding:8px 14px 14px;display:flex;justify-content:flex-end;gap:8px;">'
          +   '<button id="diag_del_cancel" class="button_close" style="width:120px;">' + msgBtnCancel + '</button>'
          +   '<button id="diag_del_confirm" class="button_delete" style="width:140px;opacity:0.45;cursor:not-allowed;" disabled>'
          +     msgBtnDelete
          +   '</button>'
          + '</div>'
          + '</div>';
        document.body.appendChild(overlay);

        var input      = overlay.querySelector('#diag_del_answer');
        var feedback   = overlay.querySelector('#diag_del_feedback');
        var confirmBtn = overlay.querySelector('#diag_del_confirm');
        var cancelBtn  = overlay.querySelector('#diag_del_cancel');

        // Hover feedback — same darken trick as in the save challenge.
        [confirmBtn, cancelBtn].forEach(function(btn) {
            btn.addEventListener('mouseenter', function() {
                if (btn.disabled) return;
                btn.style.filter = 'brightness(0.9)';
            });
            btn.addEventListener('mouseleave', function() {
                btn.style.filter = '';
            });
        });

        function setEnabled(on) {
            confirmBtn.disabled = !on;
            confirmBtn.style.opacity = on ? '1' : '0.45';
            confirmBtn.style.cursor  = on ? 'pointer' : 'not-allowed';
        }
        input.addEventListener('input', function() {
            var v = parseInt(input.value, 10);
            if (input.value === '' || isNaN(v)) { feedback.textContent = ''; setEnabled(false); }
            else if (v === expected)            { feedback.textContent = '✓'; feedback.style.color = '#0a7d34'; setEnabled(true); }
            else                                { feedback.textContent = '✗'; feedback.style.color = '#a32d2d'; setEnabled(false); }
        });

        function cleanup() { overlay.remove(); document.removeEventListener('keydown', onKey); }
        function onKey(e) {
            if (e.key === 'Escape') { cleanup(); }
            if (e.key === 'Enter' && !confirmBtn.disabled) { cleanup(); doDeleteWellLog(idRa); }
        }
        document.addEventListener('keydown', onKey);
        cancelBtn.addEventListener('click', cleanup);
        confirmBtn.addEventListener('click', function() { cleanup(); doDeleteWellLog(idRa); });
        setTimeout(function() { input.focus(); }, 100);
    }


    function doDeleteWellLog(idRa)
    {
        // Build the action timestamp client-side, same convention as
        // doSaveEdit (yyyy-mm-dd HH:MM:SS local time).
        var now = new Date();
        function pad(n) { return (n < 10 ? '0' : '') + n; }
        var todayTimeFormatted =
            now.getFullYear() + '-' + pad(now.getMonth() + 1) + '-' + pad(now.getDate())
          + ' '
          + pad(now.getHours()) + ':' + pad(now.getMinutes()) + ':' + pad(now.getSeconds());

        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'include/structure/diag/process_diag_del.php', true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.onreadystatechange = function() {
            if (xhr.readyState !== 4) return;
            if (xhr.status !== 200) {
                console.error('[Diag delete] HTTP', xhr.status, xhr.responseText);
                customDiagAlert(msgDelErr);
                return;
            }
            var r;
            try { r = JSON.parse(xhr.responseText); }
            catch (e) {
                console.error('[Diag delete] bad JSON:', xhr.responseText);
                customDiagAlert(msgDelErr);
                return;
            }
            if (r.valid_process) {
                // Reload the full popup — the deleted well log will
                // vanish from the list (no rows in the JOIN) and from
                // the chart.
                load_data_diag();
            } else {
                customDiagAlert(r.js_text || msgDelErr);
            }
        };
        xhr.send(JSON.stringify({
            idUser:             idUser,
            todayTimeFormatted: todayTimeFormatted,
            id_ra:              idRa
        }));
    }


    // =============================================================
    // EDIT MODE
    // =============================================================
    // The edit mode lets the user reshape ONE diagraphy at a time by
    // dragging its points, right-clicking to remove them, or right-
    // clicking an empty area to add one. The other checked well logs
    // remain visible in the background (greyed out, non-interactive)
    // for visual comparison.
    //
    // State changes go through these three transitions:
    //   normal → edit  : enterEditMode()         (Edit button click)
    //   edit → switch  : refreshEditTargetSelect() rebuilds the dropdown
    //                    on each redraw; user-driven switch is blocked
    //                    when editDirty is true
    //   edit → normal  : exitEditMode()          (Cancel edit or after Save)


    // -----------------------------------------------
    // enterEditMode()
    // Triggered by the "Edit" button. Picks the first checked log as
    // the initial edit target, swaps the action bars, applies the
    // background-only style to the other traces.

    function enterEditMode()
    {
        var plotEl = document.getElementById('plotDiag');
        if (!plotEl || !plotEl.data || plotEl.data.length < 1) {
            customDiagAlert(msgEditNoData);
            return;
        }

        // Pick the first checked diagraphy as initial target
        var firstChecked = null;
        plotEl.data.forEach(function(tr) {
            if (firstChecked === null && tr.meta && tr.meta.indexOf('ra_') === 0) {
                firstChecked = parseInt(tr.meta.substring(3), 10);
            }
        });
        if (firstChecked === null) {
            customDiagAlert(msgEditNoData);
            return;
        }

        editingRaId = firstChecked;
        editDirty   = false;

        // Swap the action bars
        document.getElementById('diag_action_bar').style.display = 'none';
        document.getElementById('edit_action_bar').style.display = 'flex';
        document.getElementById('edit_hint_line').style.display  = 'block';

        // Lock the left-column list: disable every × delete cell so the
        // user can't drop a well log while another is being edited.
        var listBox = document.getElementById('cadre_data_station_lgt');
        if (listBox) { listBox.classList.add('diag-edit-locked'); }

        // Disable Plotly drag interactions (we handle our own drag)
        try { Plotly.relayout(plotEl, { dragmode: false }); } catch (e) {}

        refreshEditTargetSelect();
        applyEditVisualStyle();
    }


    // -----------------------------------------------
    // exitEditMode()
    // Restore the normal action bar, clear the greyed-out styles and
    // reset state. Called by Cancel edit and after a successful Save.

    function exitEditMode()
    {
        editingRaId = null;
        editDirty   = false;

        document.getElementById('diag_action_bar').style.display = 'flex';
        document.getElementById('edit_action_bar').style.display = 'none';
        document.getElementById('edit_hint_line').style.display  = 'none';

        // Re-enable the × delete cells on the left-column list.
        var listBox = document.getElementById('cadre_data_station_lgt');
        if (listBox) { listBox.classList.remove('diag-edit-locked'); }

        var plotEl = document.getElementById('plotDiag');
        if (!plotEl || !plotEl.data) return;

        // Restore the original opacity + marker size on every trace,
        // and re-enable Plotly's pan dragmode.
        var updates = { opacity: [], 'marker.size': [] };
        for (var i = 0; i < plotEl.data.length; i++) {
            updates.opacity.push(1);
            updates['marker.size'].push(8);
        }
        try {
            Plotly.restyle(plotEl, updates);
            Plotly.relayout(plotEl, { dragmode: 'pan' });
        } catch (e) {}
    }


    // -----------------------------------------------
    // refreshEditTargetSelect()
    // Rebuild the dropdown of selectable edit targets from the current
    // traces. Keeps the active editingRaId selected.

    // -----------------------------------------------
    // refreshEditTargetSelect()
    // Rebuilds the custom dropdown's item list from the current traces
    // (one entry per checked diagraphy). Each entry carries its pastel
    // tint so it matches the same colour used in the left-column row
    // and on the chart.

    function refreshEditTargetSelect()
    {
        var menu   = document.getElementById('edit_target_dd_menu');
        var btn    = document.getElementById('edit_target_dd_btn');
        var plotEl = document.getElementById('plotDiag');
        if (!menu || !btn || !plotEl || !plotEl.data) return;

        menu.innerHTML = '';

        plotEl.data.forEach(function(tr) {
            if (!tr.meta || tr.meta.indexOf('ra_') !== 0) return;
            var idRa = parseInt(tr.meta.substring(3), 10);
            var hex  = lastColorMap[idRa];

            var li = document.createElement('li');
            li.setAttribute('role', 'option');
            li.setAttribute('data-id-ra', idRa);
            li.textContent = tr.name || ('id_ra ' + idRa);
            if (hex) { li.style.backgroundColor = hexToPastel(hex); }
            if (idRa === editingRaId) { li.classList.add('is-active'); }

            li.addEventListener('click', function() {
                var newId = parseInt(this.getAttribute('data-id-ra'), 10);
                closeEditDropdown();
                if (newId === editingRaId) return;
                if (editDirty) {
                    customDiagAlert(msgEditSwitchBlocked);
                    return;
                }
                editingRaId = newId;
                applyEditVisualStyle();
                updateEditSelectBackground();
                // Refresh the active class on the new entry
                refreshEditTargetSelect();
            });

            menu.appendChild(li);
        });

        updateEditSelectBackground();
        wireEditDropdownOnce();
    }


    // Update the trigger button: tint its background with the active
    // log's pastel + show its name as label.
    function updateEditSelectBackground() {
        var btn    = document.getElementById('edit_target_dd_btn');
        var label  = btn ? btn.querySelector('.diag-dd-label') : null;
        var plotEl = document.getElementById('plotDiag');
        if (!btn || !label || !plotEl || !plotEl.data) return;

        var hex      = lastColorMap[editingRaId];
        var activeNm = '';
        plotEl.data.forEach(function(tr) {
            if (tr.meta === 'ra_' + editingRaId) { activeNm = tr.name || ''; }
        });
        label.textContent = activeNm;
        btn.style.backgroundColor = hex ? hexToPastel(hex) : '#fff';
    }


    // Wire trigger / outside-click / Escape handlers — once, on first use.
    function wireEditDropdownOnce() {
        var dd  = document.getElementById('edit_target_dd');
        var btn = document.getElementById('edit_target_dd_btn');
        if (!dd || !btn || dd._wired) return;
        dd._wired = true;

        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            toggleEditDropdown();
        });

        // Click anywhere else → close the menu
        document.addEventListener('click', function(e) {
            if (!dd.contains(e.target)) { closeEditDropdown(); }
        });

        // Escape closes the menu (but doesn't exit edit mode)
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') { closeEditDropdown(); }
        });
    }

    function openEditDropdown() {
        var menu = document.getElementById('edit_target_dd_menu');
        var btn  = document.getElementById('edit_target_dd_btn');
        if (!menu || !btn) return;
        menu.hidden = false;
        btn.setAttribute('aria-expanded', 'true');
    }
    function closeEditDropdown() {
        var menu = document.getElementById('edit_target_dd_menu');
        var btn  = document.getElementById('edit_target_dd_btn');
        if (!menu || !btn) return;
        menu.hidden = true;
        btn.setAttribute('aria-expanded', 'false');
    }
    function toggleEditDropdown() {
        var menu = document.getElementById('edit_target_dd_menu');
        if (!menu) return;
        if (menu.hidden) { openEditDropdown(); } else { closeEditDropdown(); }
    }


    // -----------------------------------------------
    // applyEditVisualStyle()
    // Bring the edited trace to the foreground (opacity 1, normal
    // markers); push the other traces to the background (opacity 0.3,
    // smaller markers). Uses Plotly.restyle in a single call so the
    // update is atomic.

    function applyEditVisualStyle()
    {
        var plotEl = document.getElementById('plotDiag');
        if (!plotEl || !plotEl.data) return;

        var opacities = [];
        var sizes     = [];
        for (var i = 0; i < plotEl.data.length; i++) {
            var tr = plotEl.data[i];
            var idRa = (tr.meta && tr.meta.indexOf('ra_') === 0)
                     ? parseInt(tr.meta.substring(3), 10) : null;
            if (idRa === editingRaId) {
                opacities.push(1);
                sizes.push(10);
            } else {
                opacities.push(0.3);
                sizes.push(5);
            }
        }
        try {
            Plotly.restyle(plotEl, { opacity: opacities, 'marker.size': sizes });
        } catch (e) {}
    }


    // -----------------------------------------------
    // Hit-testing on the edited trace only
    //
    // When the user clicks on the plot we test the cursor against
    // every point of the edited diagraphy and return the closest
    // within a 14-px radius. Points from other traces are ignored —
    // they're greyed out and non-interactive.

    function editHitTestPoint(e, plotEl)
    {
        if (!plotEl._fullLayout || !plotEl.data) return -1;
        var rect = plotEl.getBoundingClientRect();
        var cx = e.clientX - rect.left;
        var cy = e.clientY - rect.top;
        var xa = plotEl._fullLayout.xaxis;
        var ya = plotEl._fullLayout.yaxis;
        if (!xa || !ya) return -1;

        // Locate the trace that matches the edited id_ra
        var targetTrace = null, targetIdx = -1;
        for (var t = 0; t < plotEl.data.length; t++) {
            var tr = plotEl.data[t];
            if (tr.meta === 'ra_' + editingRaId) { targetTrace = tr; targetIdx = t; break; }
        }
        if (!targetTrace || !targetTrace.x) return -1;

        var bestIdx = -1, bestD2 = 14 * 14 + 1;
        for (var i = 0; i < targetTrace.x.length; i++) {
            var px = xa.l2p(targetTrace.x[i]) + xa._offset;
            var py = ya.l2p(targetTrace.y[i]) + ya._offset;
            var dx = px - cx, dy = py - cy;
            var d2 = dx * dx + dy * dy;
            if (d2 < bestD2) { bestD2 = d2; bestIdx = i; }
        }
        return bestIdx;
    }


    // Translate a mouse event into (x, y) coordinates in the plot's
    // data space. Used both for drag (move) and right-click (add).
    function editPixelToData(e, plotEl)
    {
        if (!plotEl._fullLayout) return null;
        var rect = plotEl.getBoundingClientRect();
        var xa = plotEl._fullLayout.xaxis;
        var ya = plotEl._fullLayout.yaxis;
        if (!xa || !ya) return null;
        var x = xa.p2l(e.clientX - rect.left - xa._offset);
        var y = ya.p2l(e.clientY - rect.top  - ya._offset);
        return { x: x, y: y };
    }


    // -----------------------------------------------
    // Drag handlers (mousedown / mousemove / mouseup)
    //
    // Active only when editingRaId !== null and the cursor lands on
    // a point of the edited trace. We mirror the ETL drag pattern:
    // capture on mousedown (stop propagation so Plotly doesn't start
    // panning), follow on mousemove, release on mouseup.

    document.addEventListener('mousedown', function(e) {
        if (editingRaId === null) return;
        if (e.button !== 0) return; // left button only
        var plotEl = document.getElementById('plotDiag');
        if (!plotEl || !plotEl.contains(e.target)) return;

        var hit = editHitTestPoint(e, plotEl);
        if (hit === -1) return;

        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();

        editDragState = {
            pointIndex: hit,
            plotEl: plotEl,
            moved: false
        };
        plotEl.style.cursor = 'grabbing';
    }, true);

    document.addEventListener('mousemove', function(e) {
        if (!editDragState) return;
        e.stopPropagation();
        var plotEl = editDragState.plotEl;
        var coord  = editPixelToData(e, plotEl);
        if (!coord) return;

        // Find the index of the edited trace once
        var traceIdx = -1;
        for (var t = 0; t < plotEl.data.length; t++) {
            if (plotEl.data[t].meta === 'ra_' + editingRaId) { traceIdx = t; break; }
        }
        if (traceIdx === -1) return;

        var xs = plotEl.data[traceIdx].x.slice();
        var ys = plotEl.data[traceIdx].y.slice();
        xs[editDragState.pointIndex] = coord.x;
        ys[editDragState.pointIndex] = coord.y;
        Plotly.restyle(plotEl, { x: [xs], y: [ys] }, [traceIdx]);
        editDragState.moved = true;
    }, true);

    document.addEventListener('mouseup', function() {
        if (!editDragState) return;
        if (editDragState.moved) { editDirty = true; }
        editDragState.plotEl.style.cursor = '';
        editDragState = null;
    }, true);


    // -----------------------------------------------
    // Right-click handler — add / remove points
    //
    // - On a point of the edited trace → remove that point
    // - On an empty area inside the edited trace's bounds → add a
    //   new point with the current cursor coords (depth + conductivity)
    //   and temperature=null, obs='' (these will be NULL/empty in DB)
    //
    // The native browser context menu is always suppressed while in
    // edit mode (so users don't get a confusing menu when they intend
    // to add or remove).

    document.addEventListener('contextmenu', function(e) {
        if (editingRaId === null) return;
        var plotEl = document.getElementById('plotDiag');
        if (!plotEl || !plotEl.contains(e.target)) return;

        e.preventDefault();
        e.stopPropagation();

        var traceIdx = -1;
        for (var t = 0; t < plotEl.data.length; t++) {
            if (plotEl.data[t].meta === 'ra_' + editingRaId) { traceIdx = t; break; }
        }
        if (traceIdx === -1) return;

        var hit = editHitTestPoint(e, plotEl);
        if (hit !== -1) {
            // Remove this point
            var xs = plotEl.data[traceIdx].x.slice();
            var ys = plotEl.data[traceIdx].y.slice();
            var cd = (plotEl.data[traceIdx].customdata || []).slice();
            xs.splice(hit, 1);
            ys.splice(hit, 1);
            if (cd.length) { cd.splice(hit, 1); }
            if (xs.length < 1) {
                customDiagAlert(msgEditMinPoints);
                return;
            }
            Plotly.restyle(plotEl, { x: [xs], y: [ys], customdata: [cd] }, [traceIdx]);
            editDirty = true;
            return;
        }

        // Add a new point at the cursor coords with temperature=null,
        // obs=''. The new point is inserted at the position that keeps
        // the points sorted by depth (y descending in the chart, i.e.
        // ascending absolute depth).
        var coord = editPixelToData(e, plotEl);
        if (!coord || !isFinite(coord.x) || !isFinite(coord.y)) return;

        var xs = plotEl.data[traceIdx].x.slice();
        var ys = plotEl.data[traceIdx].y.slice();
        var cd = (plotEl.data[traceIdx].customdata || []).slice();

        // Insert keeping ys sorted descending (depths run -25 → 0 top)
        var insertAt = ys.length;
        for (var i = 0; i < ys.length; i++) {
            if (coord.y > ys[i]) { insertAt = i; break; }
        }
        xs.splice(insertAt, 0, coord.x);
        ys.splice(insertAt, 0, coord.y);
        cd.splice(insertAt, 0, [null, '']);

        Plotly.restyle(plotEl, { x: [xs], y: [ys], customdata: [cd] }, [traceIdx]);
        editDirty = true;
    }, true);


    // -----------------------------------------------
    // Save flow
    //
    // 1. attemptSaveEdit() collects the edited points and opens the
    //    math-challenge popup (anti-misclick).
    // 2. doSaveEdit() runs on validated challenge: POSTs to
    //    process_diag_edit_save.php with the new point set.
    // 3. On success, the chart is refreshed from the server and edit
    //    mode is exited.

    function attemptSaveEdit()
    {
        if (editingRaId === null) return;
        if (!editDirty) {
            // Nothing changed — just exit
            exitEditMode();
            return;
        }
        openSaveEditChallenge();
    }


    function attemptCancelEdit()
    {
        if (!editDirty) { exitEditMode(); return; }
        if (window.confirm(msgEditCancelConfirm)) {
            // Reload the chart from the server to discard local edits
            exitEditMode();
            load_graph_diag();
        }
    }


    function openSaveEditChallenge()
    {
        var prev = document.getElementById('diag_save_challenge');
        if (prev) prev.remove();

        // Same generator as the ETL popups
        var op = ['+', '-', 'x'][Math.floor(Math.random() * 3)];
        var a, b, expected;
        if (op === '+') {
            a = Math.floor(Math.random() * 16) + 5;
            b = Math.floor(Math.random() * 14) + 2;
            expected = a + b;
        } else if (op === '-') {
            a = Math.floor(Math.random() * 21) + 10;
            b = Math.floor(Math.random() * (a - 1)) + 1;
            expected = a - b;
        } else {
            a = Math.floor(Math.random() * 8) + 2;
            b = Math.floor(Math.random() * 8) + 2;
            expected = a * b;
        }

        var plotEl = document.getElementById('plotDiag');
        var targetName = '';
        plotEl.data.forEach(function(tr) {
            if (tr.meta === 'ra_' + editingRaId) { targetName = tr.name || ''; }
        });

        var overlay = document.createElement('div');
        overlay.id = 'diag_save_challenge';
        overlay.style.cssText =
            'position:fixed;top:0;left:0;width:100vw;height:100vh;'
          + 'background:rgba(0,0,0,0.4);z-index:3000;'
          + 'display:flex;align-items:center;justify-content:center;';
        overlay.innerHTML =
            '<div style="background:#FBF9F1;border-radius:4px;width:460px;max-width:90vw;'
          + 'box-shadow:0 8px 24px rgba(0,0,0,0.3);overflow:hidden;font-family:inherit;">'
          + '<div style="background:#176B87;color:#fff;padding:10px 14px;font-size:14px;font-weight:bold;">'
          +   msgEditConfirmTitle
          + '</div>'
          + '<div style="padding:14px 16px;font-size:13px;line-height:1.5;color:#333;">'
          +   msgEditConfirmMsg
          +   '<div style="margin:10px 0;padding:8px 10px;background:#eef5f8;border-left:3px solid #176B87;font-size:12px;">'
          +     '<b>' + msgEditTarget + ' :</b> ' + escapeHtml(targetName)
          +   '</div>'
          +   '<div style="margin-top:14px;padding:10px;background:#fff;border:1px solid #ddd;border-radius:3px;">'
          +     '<div style="font-size:12px;color:#666;margin-bottom:6px;">' + msgChallengeHint + '</div>'
          +     '<div style="display:flex;align-items:center;gap:8px;">'
          +       '<span style="font-size:16px;font-weight:bold;">' + a + ' ' + op + ' ' + b + ' = </span>'
          +       '<input id="diag_challenge_answer" type="text" style="width:60px;font-size:16px;padding:4px;" autofocus>'
          +       '<span id="diag_challenge_feedback" style="font-size:12px;"></span>'
          +     '</div>'
          +   '</div>'
          + '</div>'
          + '<div style="padding:8px 14px 14px;display:flex;justify-content:flex-end;gap:8px;">'
          +   '<button id="diag_save_cancel" class="button_close" style="width:120px;">' + msgBtnCancel + '</button>'
          +   '<button id="diag_save_confirm" class="button" style="width:140px;opacity:0.45;cursor:not-allowed;" disabled>'
          +     msgBtnSave
          +   '</button>'
          + '</div>'
          + '</div>';
        document.body.appendChild(overlay);

        var input      = overlay.querySelector('#diag_challenge_answer');
        var feedback   = overlay.querySelector('#diag_challenge_feedback');
        var confirmBtn = overlay.querySelector('#diag_save_confirm');
        var cancelBtn  = overlay.querySelector('#diag_save_cancel');

        // Hover feedback — same trick as in the ETL save challenge:
        // we darken the button slightly via filter:brightness so the
        // user gets the same affordance as on the rest of the platform
        // (the global .button / .button_close hover rules target
        // <input type='button'>, not the <button> elements used here).
        [confirmBtn, cancelBtn].forEach(function(btn) {
            btn.addEventListener('mouseenter', function() {
                if (btn.disabled) return;
                btn.style.filter = 'brightness(0.9)';
            });
            btn.addEventListener('mouseleave', function() {
                btn.style.filter = '';
            });
        });

        function setEnabled(on) {
            confirmBtn.disabled = !on;
            confirmBtn.style.opacity = on ? '1' : '0.45';
            confirmBtn.style.cursor  = on ? 'pointer' : 'not-allowed';
        }
        input.addEventListener('input', function() {
            var v = parseInt(input.value, 10);
            if (input.value === '' || isNaN(v)) { feedback.textContent = ''; setEnabled(false); }
            else if (v === expected)            { feedback.textContent = '✓'; feedback.style.color = '#0a7d34'; setEnabled(true); }
            else                                { feedback.textContent = '✗'; feedback.style.color = '#a32d2d'; setEnabled(false); }
        });

        function cleanup() { overlay.remove(); document.removeEventListener('keydown', onKey); }
        function onKey(e) {
            if (e.key === 'Escape') { cleanup(); }
            if (e.key === 'Enter' && !confirmBtn.disabled) { cleanup(); doSaveEdit(); }
        }
        document.addEventListener('keydown', onKey);
        cancelBtn.addEventListener('click', cleanup);
        confirmBtn.addEventListener('click', function() { cleanup(); doSaveEdit(); });
        setTimeout(function() { input.focus(); }, 100);
    }


    function doSaveEdit()
    {
        var plotEl = document.getElementById('plotDiag');
        if (!plotEl) return;
        var traceIdx = -1;
        for (var t = 0; t < plotEl.data.length; t++) {
            if (plotEl.data[t].meta === 'ra_' + editingRaId) { traceIdx = t; break; }
        }
        if (traceIdx === -1) return;

        var tr = plotEl.data[traceIdx];
        var xs = tr.x || [];
        var ys = tr.y || [];
        var cd = tr.customdata || [];

        // Build the payload. The chart stores depth as y * -1 (so the
        // axis runs downward); we flip the sign back to the DB
        // convention (positive depth) before sending.
        // Conductivity is xs[i], temperature is customdata[i][0],
        // obs is customdata[i][1].
        var points = [];
        for (var i = 0; i < xs.length; i++) {
            var customRow = cd[i] || [null, ''];
            points.push({
                profondeur:   -1 * ys[i],
                conductivite: xs[i],
                temperature:  customRow[0],
                obs:          customRow[1] || ''
            });
        }

        // Format the current timestamp the same way the rest of the
        // platform does ('yyyy-mm-dd HH:MM:SS' local time) for the
        // TABLE_ACTIONS log row.
        var now = new Date();
        function pad(n) { return (n < 10 ? '0' : '') + n; }
        var todayTimeFormatted =
            now.getFullYear() + '-' + pad(now.getMonth() + 1) + '-' + pad(now.getDate())
          + ' '
          + pad(now.getHours()) + ':' + pad(now.getMinutes()) + ':' + pad(now.getSeconds());

        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'include/structure/diag/process_diag_edit_save.php', true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.onreadystatechange = function() {
            if (xhr.readyState !== 4) return;
            if (xhr.status !== 200) {
                console.error('[Diag save] HTTP', xhr.status, xhr.responseText);
                customDiagAlert(msgEditSaveErr);
                return;
            }
            var r;
            try { r = JSON.parse(xhr.responseText); }
            catch (e) {
                console.error('[Diag save] bad JSON:', xhr.responseText);
                customDiagAlert(msgEditSaveErr);
                return;
            }
            if (r.valid_process) {
                exitEditMode();
                load_graph_diag();   // reload chart from DB
            } else {
                customDiagAlert(r.js_text || msgEditSaveErr);
            }
        };
        xhr.send(JSON.stringify({
            idUser:             idUser,
            todayTimeFormatted: todayTimeFormatted,
            id_ra:              editingRaId,
            points:             points
        }));
    }


    // -----------------------------------------------
    // Small helpers: HTML-safe escape, lightweight alert dialog

    function escapeHtml(s) {
        return String(s || '')
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }

    function customDiagAlert(message) {
        // Plain alert() for now — same lightweight feedback the rest
        // of the diag module already uses (see msgNoDiag).
        alert(message);
    }


    // -----------------------------------------------
    // Edit-mode strings (declared from the PHP translation table)
    var msgEditNoData         = <?php echo json_encode(TEXT_DG_EDIT_NO_DATA); ?>;
    var msgEditCheckLock      = <?php echo json_encode(TEXT_DG_EDIT_CHECK_LOCK); ?>;
    var msgEditSwitchBlocked  = <?php echo json_encode(TEXT_DG_EDIT_SWITCH_BLOCKED); ?>;
    var msgEditMinPoints      = <?php echo json_encode(TEXT_DG_EDIT_MIN_POINTS); ?>;
    var msgEditCancelConfirm  = <?php echo json_encode(TEXT_DG_EDIT_CANCEL_CONFIRM); ?>;
    var msgEditConfirmTitle   = <?php echo json_encode(TEXT_DG_EDIT_CONFIRM_TITLE); ?>;
    var msgEditConfirmMsg     = <?php echo json_encode(TEXT_DG_EDIT_CONFIRM_MSG); ?>;
    var msgEditTarget         = <?php echo json_encode(TEXT_DG_EDIT_TARGET); ?>;
    var msgEditSaveErr        = <?php echo json_encode(TEXT_DG_EDIT_SAVE_ERR); ?>;
    var msgChallengeHint      = <?php echo json_encode(TEXT_ET_NEW_CHALLENGE_HINT); ?>;
    var msgBtnSave            = <?php echo json_encode(TEXT_BTN_SAVE); ?>;
    var msgBtnCancel          = <?php echo json_encode(TEXT_BTN_CANCEL); ?>;
    var msgBtnDelete          = <?php echo json_encode(TEXT_DG_DEL_BTN_CONFIRM); ?>;
    var msgDelConfirmTitle    = <?php echo json_encode(TEXT_DG_DEL_CONFIRM_TITLE); ?>;
    var msgDelConfirmMsg      = <?php echo json_encode(TEXT_DG_DEL_CONFIRM_MSG); ?>;
    var msgDelErr             = <?php echo json_encode(TEXT_DG_DEL_ERR); ?>;

</script>