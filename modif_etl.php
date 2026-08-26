<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
ETL rating-curve editor — loaded with ?st=<id_station> or POST st=.
Displays an ETL list (left column) and a Plotly H/Q chart (right).
Popup blocks for create / edit points / delete
are included and controlled via JS.

Optional URL parameter ?id_etl=<id> — if present, the ETL list is
loaded with only that one curve pre-checked (used by the H→Q
conversion module to land directly on a specific rating curve).
----------------------------------------
*/

require('include/application_top.php');

$modif        = false;
$date_now     = date('d-m-Y');
$nom_station  = '';
$code_station = '';
$x_min = $x_max = $y_min = $y_max = 0;
$id_etl = $id_etl_first = 0;

$data_graph_all = '';

// -----------------------------------------------
// Load station data

if (isset($_POST['st']) || isset($_GET['st']))
{
    $st_id = isset($_GET['st'])
        ? mysqli_real_escape_string($sql_link, trim(addslashes($_GET['st'])))
        : mysqli_real_escape_string($sql_link, trim(addslashes($_POST['st'])));

    $station_query = tep_db_query($sql_link,
        "SELECT DISTINCT s.id_station, s.nom_station, s.nom_court, s.code_station,
                         s.id_region, s.id_commune
         FROM " . TABLE_STATION . " s WHERE s.id_station=" . $st_id);
    $station = tep_db_fetch_array($station_query);

    if (isset($station['id_station']))
    {
        $nom_station  = html_entity_decode($station['nom_station']);
        $code_station = html_entity_decode($station['code_station']);
    }
    else
    {
        $message_info .= TEXT_ET_ERR_STATION;
    }
}

// Optional ?id_etl=<id> — used to pre-select a specific curve when arriving
// from the H→Q conversion module. Sanitized to a positive integer; any other
// value falls back to 0 (= no pre-selection).
$preselected_etl_id = isset($_GET['id_etl']) ? (int)$_GET['id_etl'] : 0;
if ($preselected_etl_id < 0) { $preselected_etl_id = 0; }


// -----------------------------------------------
// Load the user's persisted "swap axes" preference.
// Stored in TABLE_USER_MENU under the menu_id 'etl_swap_axes'
// (reusing the same table the side-nav uses for its open/close state —
// is_open=1 means "swap axes enabled", is_open=0 means "normal").
// Scope is global per user: one value applies to the whole ETL module
// regardless of station.
$swap_axes_pref = 0;
$pref_stmt = $sql_link->prepare(
    "SELECT is_open FROM " . TABLE_USER_MENU
    . " WHERE id_user = ? AND menu_id = 'etl_swap_axes' LIMIT 1"
);
if ($pref_stmt) {
    $pref_stmt->bind_param("i", $id_user);
    $pref_stmt->execute();
    $pref_res = $pref_stmt->get_result();
    if ($pref_row = $pref_res->fetch_assoc()) {
        $swap_axes_pref = (int) $pref_row['is_open'];
    }
    $pref_stmt->close();
}


// -----------------------------------------------
// Page output

require(DIR_WS_STRUCTURE . 'header_web.php');
echo "<body>";

require(DIR_WS_STRUCTURE . 'header.php');
include(DIR_WS_BOX       . 'nav_accueil.php');

// Popup blocks (all hidden by default)
require(DIR_WS_ETL . 'block_etl_new.php');
require(DIR_WS_ETL . 'block_etl_edit.php');

echo "<div id='contour_general'>";

    echo "<div id='contenu_info' style='display:none;'></div>";

    echo "<div id='contenu_centre'>";

        // ---- Page title ----
        echo "<h1 id='h1_graph'>";
            echo "<span style='font-weight:bold;'>" . TEXT_ET_TITLE . "</span>";
            echo "<span>" .TEXT_ET_TITLE_STATION . " " . $code_station . " - " . $nom_station . "</span>";
        echo "</h1>";


        // ---- Left column: ETL list + scale controls ----
        echo "<div id='cadre_graph' style='float:left;width:310px;margin-right:30px;'>\n";

            echo "<div id='boxpopup' class='select-top' style='padding-top:10px;padding-left:15px;'>\n";

                echo "<p><span style='font-weight:bold;font-size:14px;'>"
                   . TEXT_ET_LIST_TITLE . "</span></p>";

                echo "<div id='button_visu' style='float:left;width:160px;' onclick='load_graph();'>"
                   . TEXT_ET_BTN_REFRESH . "</div>\n";

                echo "<div id='cadre_data_station_lgt' style='margin:10px 0;padding:0;display:none;'>\n";
                echo "</div>\n";

                echo "<div id='wait_tab' style='height:65px;margin:10px 0;text-align:center;'>";
                    echo "<img src='" . DIR_WS_IMG . "wait.gif' style='width:50px;'>";
                    echo "<p>" . TEXT_ET_LOADING . "</p>";
                echo "</div>\n";

            echo "<hr>\n";
            echo "</div>\n";

            // ---- Scale controls (compact, in left column) ----
            echo "<div id='boxpopup' class='select' style='width:100%;margin-top:10px;padding:8px 8px;'>\n";
                echo "<p style='margin:0 0 6px;font-weight:bold;font-size:13px;'>" . TEXT_ET_COORD . "</p>";

                // H line: Hmin | Hmax | H +/- decimals
                echo "<div style='display:flex;gap:8px;margin-bottom:8px;align-items:flex-end;'>\n";
                    echo "<div style='flex:1;'>";
                        echo "<label style='display:block;color:#428bca;font-size:11px;margin-bottom:2px;'>"
                           . TEXT_ET_COORD_HMIN . "</label>";
                        echo "<div style='display:flex;align-items:center;gap:4px;'>";
                            echo "<input style='width:60px;box-sizing:border-box;'"
                               . " name='x_min' id='x_min' type='text' value='" . $x_min . "'>";
                            echo "<span style='font-size:11px;color:#666;'>"
                               . TEXT_ET_COORD_UNIT_H . "</span>";
                        echo "</div>";
                    echo "</div>";
                    echo "<div style='flex:1;'>";
                        echo "<label style='display:block;color:#428bca;font-size:11px;margin-bottom:2px;'>"
                           . TEXT_ET_COORD_HMAX . "</label>";
                        echo "<div style='display:flex;align-items:center;gap:4px;'>";
                            echo "<input style='width:60px;box-sizing:border-box;'"
                               . " name='x_max' id='x_max' type='text' value='" . $x_max . "'>";
                            echo "<span style='font-size:11px;color:#666;'>"
                               . TEXT_ET_COORD_UNIT_H . "</span>";
                        echo "</div>";
                    echo "</div>";
                    echo "<div style='display:flex;gap:2px;'>";
                        echo "<button class='decimal_axe' title='" . TEXT_ET_BTN_DECIMAL_PLUS . " H'"
                           . " onclick=\"updateDecimals('plot','H','+');\">+</button>";
                        echo "<button class='decimal_axe' title='" . TEXT_ET_BTN_DECIMAL_MINUS . " H'"
                           . " onclick=\"updateDecimals('plot','H','-');\">-</button>";
                    echo "</div>";
                echo "</div>\n";

                // Q line: Qmin | Qmax | Q +/- decimals
                echo "<div style='display:flex;gap:8px;margin-bottom:10px;align-items:flex-end;'>\n";
                    echo "<div style='flex:1;'>";
                        echo "<label style='display:block;color:#428bca;font-size:11px;margin-bottom:2px;'>"
                           . TEXT_ET_COORD_QMIN . "</label>";
                        echo "<div style='display:flex;align-items:center;gap:4px;'>";
                            echo "<input style='width:60px;box-sizing:border-box;'"
                               . " name='y_min' id='y_min' type='text' value='" . $y_min . "'>";
                            echo "<span style='font-size:11px;color:#666;'>"
                               . TEXT_ET_COORD_UNIT_Q . "</span>";
                        echo "</div>";
                    echo "</div>";
                    echo "<div style='flex:1;'>";
                        echo "<label style='display:block;color:#428bca;font-size:11px;margin-bottom:2px;'>"
                           . TEXT_ET_COORD_QMAX . "</label>";
                        echo "<div style='display:flex;align-items:center;gap:4px;'>";
                            echo "<input style='width:60px;box-sizing:border-box;'"
                               . " name='y_max' id='y_max' type='text' value='" . $y_max . "'>";
                            echo "<span style='font-size:11px;color:#666;'>"
                               . TEXT_ET_COORD_UNIT_Q . "</span>";
                        echo "</div>";
                    echo "</div>";
                    echo "<div style='display:flex;gap:2px;'>";
                        echo "<button class='decimal_axe' title='" . TEXT_ET_BTN_DECIMAL_PLUS . " Q'"
                           . " onclick=\"updateDecimals('plot','Q','+');\">+</button>";
                        echo "<button class='decimal_axe' title='" . TEXT_ET_BTN_DECIMAL_MINUS . " Q'"
                           . " onclick=\"updateDecimals('plot','Q','-');\">-</button>";
                    echo "</div>";
                echo "</div>\n";

                // Bottom row: Adjust scale button + swap axes checkbox
                echo "<div style='display:flex;align-items:center;justify-content:space-between;margin-top:6px;'>\n";
                    echo "<button id='ajustCoord' class='zoom_graph' style='width:auto;padding:4px 14px;'>"
                       . TEXT_ET_BTN_ADJUST . "</button>";
                    echo "<label style='display:flex;align-items:center;gap:5px;cursor:pointer;font-size:12px;'>";
                        $swap_checked = $swap_axes_pref ? ' checked' : '';
                        echo "<input type='checkbox' id='swap_axes' style='margin:0;'" . $swap_checked . ">";
                        echo TEXT_ET_OPT_SWAP;
                    echo "</label>";
                echo "</div>\n";

            echo "</div>\n";

        echo "<hr>\n";
        echo "</div>\n";


        // ---- Right area: chart + action buttons ----
        echo "<div id='cadre_graph' style='float:none;width:auto;height:75vh;overflow-y: auto;'>\n";

            echo "<div id='boxpopup' class='select' style='width:99%;margin:0;padding:0;border:1px solid #000;'>\n";

                // Action buttons
                echo "<div id='button_newChron' style='float:left;width:80px;margin-top:5px;margin-left:15px;padding:4px 5px;'"
                   . " title='" . TEXT_ET_BTN_NEW_TITLE . "'>\n"
                   . TEXT_ET_BTN_NEW . "</div>\n";

                echo "<div id='button_modif' style='float:left;width:80px;margin-top:5px;margin-left:5px;padding:4px 5px;display:none;'"
                   . " title='" . TEXT_ET_BTN_EDIT_TITLE . "'>"
                   . TEXT_ET_BTN_EDIT . "</div>";

                echo "<div id='button_del' style='float:left;width:80px;margin-top:5px;margin-left:5px;padding:4px 5px;display:none;'"
                   . " title='" . TEXT_ET_BTN_DEL_TITLE . "'>"
                   . TEXT_ET_BTN_DEL . "</div>";

                echo "<p class='titre' style='height:35px;padding:0;border-top-left-radius:1px;border-top-right-radius:1px;'></p>";

                // ETL timeline (rendered client-side from ETL_array).
                // Single-row layout, always expanded — no collapse handle
                // since the timeline is now compact enough.
                echo "<div id='etl_timeline_wrap' style='margin:10px 25px 0;border:1px solid #e0e0e0;background:#fbf9f1;display:none;'>\n";
                    echo "<div style='display:flex;align-items:center;justify-content:space-between;"
                       . "padding:6px 10px;background:#f3efe2;user-select:none;'>\n";
                        echo "<span style='font-weight:bold;font-size:13px;'>" . TEXT_ET_TIMELINE_TITLE . "</span>\n";
                        echo "<span style='font-size:11px;color:#666;'>" . TEXT_ET_TIMELINE_HINT . "</span>\n";
                    echo "</div>\n";
                    echo "<div style='padding:10px;'>\n";
                        echo "<div id='etl_timeline'></div>\n";
                    echo "</div>\n";
                    echo "<div id='etl_timeline_tooltip' style='position:fixed;display:none;background:#fff;border:1px solid #333;"
                       . "padding:5px 7px;font-size:11px;line-height:1.4;pointer-events:none;z-index:1000;"
                       . "box-shadow:0 2px 4px rgba(0,0,0,0.15);white-space:nowrap;'></div>\n";
                echo "</div>\n";

                // Chart container
                echo "<div id='plot' class='graph' style='height:52vh;margin:0 25px;display:none;'></div>\n";

                echo "<div id='wait_graph' style='width:100%;height:65px;text-align:center;'>";
                    echo "<img src='" . DIR_WS_IMG . "wait.gif' style='width:50px;'>";
                    echo "<p>" . TEXT_ET_LOADING . "</p>";
                echo "</div>\n";

            echo "<hr>\n";
            echo "</div>\n";

        echo "</div>\n";

    echo "</div>";

echo "</div>";

require('include/application_bottom.php');
echo "</body>";
echo "</html>";
?>


<script>

    // -----------------------------------------------
    // Global vars

    var idUser              = <?php echo $id_user; ?>;
    var todayFrDateFormatted = '<?php echo $today_fr_formatted; ?>';
    var todayTimeFormatted   = '<?php echo $today_time_formatted; ?>';
    var msgInfo             = document.getElementById('contenu_info');
    var idStation           = <?php echo $st_id; ?>;

    // Optional ETL to pre-select when the page is opened via ?id_etl=<id>
    // (e.g. from a click on the H→Q timeline). 0 means "no pre-selection".
    var preselectedEtlId    = <?php echo (int)$preselected_etl_id; ?>;

    // Prefix used by process_etl_graph.php to name JGE traces (e.g. "JGE 1").
    // We match traces by this prefix in buttonNew.click to harvest their
    // customdata (date strings) and auto-fill the New popup period.
    var JGE_TRACE_PREFIX     = '<?php echo TEXT_ET_LABEL_JGE_REF; ?>';

    var boxTabWait   = document.getElementById('wait_tab');
    var boxTab       = document.getElementById('cadre_data_station_lgt');
    var boxGraphWait = document.getElementById('wait_graph');
    var boxPlot      = document.getElementById('plot');

    var newDateFirst = document.getElementById('new_date_debut_periode');
    var buttonNew    = document.getElementById('button_newChron');
    var boxNew       = document.getElementById('box_elt_new');
    var bValidNewETL = document.getElementById('new_etl');

    var buttonEdit   = document.getElementById('button_modif');
    var buttonDel    = document.getElementById('button_del');

    var xMin = document.getElementById('x_min');
    var xMax = document.getElementById('x_max');
    var yMin = document.getElementById('y_min');
    var yMax = document.getElementById('y_max');

    var ETL_array = {};

    // -----------------------------------------------
    // Button event listeners

    if (boxPlot)
    {
        buttonNew.addEventListener('click', function() {
            // Auto-fill the period from the currently CHECKED rating
            // curves in the left-hand list. We pick the earliest
            // datetime_first across them as the period start, and the
            // latest datetime_end as the period end. This is much more
            // intuitive than deriving from JGE points: the user explicitly
            // checked the RCs they're interested in, and the popup opens
            // with a period that spans exactly those RCs.
            //
            // ETL_array is populated when the station's list is loaded
            // (see process_etl_tab.php). Each entry has datetime_first
            // and datetime_end in "dd-mm-yyyy HH:MM:SS" form.

            // Parse "dd-mm-yyyy HH:MM:SS" → Date (or null)
            function parseEtlDate(s) {
                if (!s) { return null; }
                var m = String(s).match(/^(\d{2})-(\d{2})-(\d{4}) (\d{2}):(\d{2}):(\d{2})$/);
                if (!m) { return null; }
                return new Date(+m[3], +m[2] - 1, +m[1], +m[4], +m[5], +m[6]);
            }
            function fmtDate(d) {
                var dd = String(d.getDate()).padStart(2, '0');
                var mm = String(d.getMonth() + 1).padStart(2, '0');
                return dd + '-' + mm + '-' + d.getFullYear();
            }
            function fmtTime(d) {
                var hh = String(d.getHours()).padStart(2, '0');
                var mn = String(d.getMinutes()).padStart(2, '0');
                var ss = String(d.getSeconds()).padStart(2, '0');
                return hh + ':' + mn + ':' + ss;
            }

            // Collect the period bounds from each checked RC.
            // The checkbox values are "<id_etl>_<num>" — we only need
            // the id_etl part to look it up in ETL_array.
            var minStart = null, maxEnd = null;
            var checkedBoxes = document.querySelectorAll("input[name='check_ETL[]']:checked");
            checkedBoxes.forEach(function(cb) {
                var parts = String(cb.value).split('_');
                var idEtl = parts[0];
                if (!ETL_array || !ETL_array[idEtl]) { return; }
                var dStart = parseEtlDate(ETL_array[idEtl].datetime_first);
                var dEnd   = parseEtlDate(ETL_array[idEtl].datetime_end);
                if (dStart && (minStart === null || dStart < minStart)) { minStart = dStart; }
                if (dEnd   && (maxEnd   === null || dEnd   > maxEnd))   { maxEnd   = dEnd; }
            });

            var d1 = document.getElementById('new_date_debut_periode');
            var d2 = document.getElementById('new_date_fin_periode');
            var h1 = document.getElementById('new_heure_debut_periode');
            var h2 = document.getElementById('new_heure_fin_periode');
            if (minStart && maxEnd) {
                if (d1) { d1.value = fmtDate(minStart); }
                if (d2) { d2.value = fmtDate(maxEnd); }
                if (h1) { h1.value = fmtTime(minStart); }
                if (h2) { h2.value = fmtTime(maxEnd); }
            } else {
                // Fallback when no RC is checked: default to "1 year
                // ending today" so the popup still opens with sensible
                // defaults instead of empty fields.
                var today   = new Date();
                var yearAgo = new Date(today.getFullYear() - 1, today.getMonth(), today.getDate());
                if (d1 && !d1.value) { d1.value = fmtDate(yearAgo); }
                if (d2 && !d2.value) { d2.value = fmtDate(today); }
                if (h1 && !h1.value) { h1.value = '00:00:00'; }
                if (h2 && !h2.value) { h2.value = '23:59:59'; }
            }

            boxNew.style.display = 'block';

            // Reset regression workflow: hide section 2, sections 3-5,
            // hide Save button, show the trigger button. The user always
            // starts with "JGE only" and explicitly activates the regression.
            if (typeof regressionEnabled !== 'undefined') {
                regressionEnabled = false;
            }
            var regSection   = document.getElementById('new_regression_section');
            var regTrigger   = document.getElementById('new_regression_trigger');
            var regDependent = document.getElementById('new_regression_dependent');
            var regSaveBtn   = document.getElementById('new_etl');
            if (regSection)   { regSection.style.display = 'none';   regSection.style.opacity = ''; }
            if (regDependent) { regDependent.style.display = 'none'; regDependent.style.opacity = ''; }
            if (regTrigger)   { regTrigger.style.display = 'block'; }
            if (regSaveBtn)   { regSaveBtn.style.display = 'none'; }

            // Also reset any cached preview response so the previous
            // session's curve/PI doesn't flash up.
            if (typeof window.__lastPreviewResponse !== 'undefined') {
                window.__lastPreviewResponse = null;
            }
            if (typeof newCurvePoints !== 'undefined') {
                newCurvePoints = null;
                newCurveBandLower = null;
                newCurveBandUpper = null;
                newCurveDirty = false;
            }

            if (typeof newPreviewLoad === 'function') {
                setTimeout(newPreviewLoad, 50); // let the popup paint first
            }
        });

        buttonDel.addEventListener('click', function() {
            attemptDeleteRC();
        });

        if (buttonEdit) {
            buttonEdit.addEventListener('click', function() {
                // We only reach this click handler if EXACTLY one RC is
                // checked (updateRowColors ensures the button is hidden
                // otherwise) — but we still guard defensively.
                var checked = getCheckedValues();
                if (checked.length !== 1) { return; }
                var idRc = parseInt(checked[0].split('_')[0], 10);
                if (isNaN(idRc)) { return; }
                openEditRC(idRc);
            });
        }

        // The New button is wired via onclick="attemptSaveNewETL();" in
        // block_etl_new.php — no listener here.
    }


    // -----------------------------------------------
    // load_tab() — fetch and display the ETL list

    function load_tab()
    {
        boxTab.style.display     = 'none';
        boxTabWait.style.display = 'block';

        var xhr = new XMLHttpRequest();
        xhr.open("POST", "include/structure/etl/process_etl_tab.php", true);
        xhr.setRequestHeader("Content-Type", "application/json");

        xhr.onreadystatechange = function()
        {
            if (xhr.readyState === 4 && xhr.status === 200)
            {
                var r = JSON.parse(xhr.responseText);
                boxTab.innerHTML         = r['html_text'];
                ETL_array                = r['ETL_array'];
                boxTab.style.display     = 'block';
                boxTabWait.style.display = 'none';

                // If we arrived with ?id_etl=<id> in the URL, pre-check ONLY
                // that one curve. Used by the H→Q timeline to land directly
                // on a specific rating curve.
                if (preselectedEtlId > 0) {
                    var cbs = document.querySelectorAll("input[name='check_ETL[]']");
                    cbs.forEach(function(c) { c.checked = false; });
                    var target = document.querySelector(
                        "input[name='check_ETL[]'][value^='" + preselectedEtlId + "_']"
                    );
                    if (target) {
                        target.checked = true;
                    }
                    // Only act on the first load — clear so user toggling later
                    // isn't fought by this auto-selection.
                    preselectedEtlId = 0;
                }

                if (ETL_array && Object.keys(ETL_array).length > 0)
                {
                    var bSel = document.querySelector('.selectAll');
                    bSel.addEventListener('click', function() {
                        var cbs = document.querySelectorAll('input[name="check_ETL[]"]');
                        var allChecked = Array.from(cbs).every(function(c) { return c.checked; });
                        cbs.forEach(function(c) { c.checked = !allChecked; });
                        updateRowColors();
                        renderTimeline();
                    });
                    buttonDel.style.display    = 'block';
                }
                else
                {
                    buttonDel.style.display    = 'none';
                }
                renderTimeline();
                updateRowColors();
                load_graph();
            }
        };
        xhr.send(JSON.stringify({ idStation: idStation }));
    }


    // -----------------------------------------------
    // load_graph() — fetch and render the Plotly chart

    var firstLoad = true;
    function load_graph()
    {
        var check_ETL = document.getElementsByName('check_ETL[]');
        var tabIdEtl  = [];
        for (var i = 0; i < check_ETL.length; i++) {
            if (check_ETL[i].checked) { tabIdEtl.push(check_ETL[i].value); }
        }

        boxNew.style.display = 'none';
        boxPlot.style.display      = 'none';
        boxGraphWait.style.display = 'block';

        var xhr = new XMLHttpRequest();
        xhr.open("POST", "include/structure/etl/process_etl_graph.php", true);
        xhr.setRequestHeader("Content-Type", "application/json");

        xhr.onreadystatechange = function()
        {
            if (xhr.readyState === 4 && xhr.status === 200)
            {
                var r = JSON.parse(xhr.responseText);
                boxPlot.style.display      = 'block';
                boxGraphWait.style.display = 'none';

                // Only auto-fit the scale on the very first load — afterwards
                // we keep whatever bounds the user has set.
                if (firstLoad) {
                    xMin.value = r['min_h'].toFixed(1);
                    xMax.value = r['max_h'].toFixed(1);
                    yMin.value = r['min_q'].toFixed(1);
                    yMax.value = r['max_q'].toFixed(1);
                }

                newDateFirst.value = r['date_first'];
                eval(r['js_text']);
                applyDecimals('plot');
                firstLoad = false;

                // Now that Plotly has rendered the traces with their real
                // server-side colours, re-render the timeline AND refresh
                // the table row pastels so both pick those colours up via
                // etlColor() instead of the static JS fallback palette.
                renderTimeline();
                updateRowColors();
            }
        };
        var swapAxes = document.getElementById('swap_axes').checked;
        xhr.send(JSON.stringify({
            firstLoad:  firstLoad,
            tabIdEtl:   tabIdEtl,
            dateToday:  todayFrDateFormatted,
            xMin:       parseFloat(xMin.value),
            xMax:       parseFloat(xMax.value),
            yMin:       parseFloat(yMin.value),
            yMax:       parseFloat(yMax.value),
            swapAxes:   swapAxes,
            idStation:  idStation
        }));
    }

    load_tab();


    // -----------------------------------------------
    // updateDecimals() — add / remove decimal places on an axis.
    // Tracked per metric dimension (H, Q), not per physical axis (X, Y),
    // so the user's choice survives axis swaps.

    var decimalPlaces = { H: 1, Q: 1 };

    function applyDecimals(plotId)
    {
        var swap = document.getElementById('swap_axes');
        var swapped = swap && swap.checked;
        var axisOfH = swapped ? 'yaxis' : 'xaxis';
        var axisOfQ = swapped ? 'xaxis' : 'yaxis';
        Plotly.relayout(plotId, {
            [axisOfH + '.tickformat']: '.' + decimalPlaces.H + 'f',
            [axisOfQ + '.tickformat']: '.' + decimalPlaces.Q + 'f'
        });
    }

    function updateDecimals(plotId, dim, type)
    {
        if (type === '+' && decimalPlaces[dim] < 6) { decimalPlaces[dim]++; }
        if (type === '-' && decimalPlaces[dim] > 0) { decimalPlaces[dim]--; }
        applyDecimals(plotId);
    }


    // -----------------------------------------------
    // getCheckedValues() — return values of checked ETL checkboxes

    function getCheckedValues()
    {
        return Array.from(document.querySelectorAll("input[name='check_ETL[]']:checked"))
                    .map(function(cb) { return cb.value; });
    }


    // -----------------------------------------------
    // updateSelectBox() — populate a popup <select> with checked ETL options

    function updateSelectBox(selectBoxName, values, boxDateDebut, boxDateFin, boxHeureDebut, boxHeureFin)
    {
        var selectBox = document.querySelector("select[name='" + selectBoxName + "']");
        selectBox.innerHTML = '';
        var idETLNew = 0;
        var firstETL = true;

        values.forEach(function(value)
        {
            var idETL  = value.split('_')[0];
            var numETL = value.split('_')[1];
            if (firstETL) { idETLNew = idETL; firstETL = false; }
            var opt = document.createElement('option');
            opt.value       = value;
            opt.textContent = '<?php echo TEXT_ET_TIMELINE_TT_RC; ?>-' + numETL + ' : ' + ETL_array[idETL].datetime_first + ' \u2192 ' + ETL_array[idETL].datetime_end;
            selectBox.appendChild(opt);
        });

        if (selectBoxName !== 'del_ref_etl' && boxDateDebut)
        {
            boxDateDebut.value  = ETL_array[idETLNew].datetime_first.split(' ')[0];
            boxHeureDebut.value = ETL_array[idETLNew].datetime_first.split(' ')[1];
            boxDateFin.value    = ETL_array[idETLNew].datetime_end.split(' ')[0];
            boxHeureFin.value   = ETL_array[idETLNew].datetime_end.split(' ')[1];
        }
    }


    // -----------------------------------------------
    // actionETL() — dispatch ETL edit period / delete

    function actionETL(action, date1Input, date2Input, heure1Input, heure2Input)
    {
        var actionLoad  = false;
        var linkProcess = '';
        var dataToSend  = {};
        var idEtlAction = 0, numEtlAction = 0;

        if (action === 'new')
        {
            linkProcess = "include/structure/etl/process_etl_new.php";
            var origineH0 = document.getElementById('origine_h0');
            if (isValidDatesInput(date1Input, date2Input, heure1Input, heure2Input))
            {
                var bornesTab = densitePts();
                if (bornesTab)
                {
                    dataToSend = { idUser: idUser, todayTimeFormatted: todayTimeFormatted,
                        date1: date1Input.value, date2: date2Input.value,
                        heure1: heure1Input.value, heure2: heure2Input.value,
                        origineH0: origineH0.value, bornesTab: bornesTab, idStation: idStation };
                    actionLoad = true;
                }
                else { msgInfo.style.border = '2px solid #930000'; }
            }
            else { msgInfo.style.border = '2px solid #930000'; }
        }

        if (action === 'del')
        {
            linkProcess = "include/structure/etl/process_etl_delete.php";
            var etlVal = document.getElementById('del_ref_etl').value;
            idEtlAction  = etlVal.split('_')[0];
            numEtlAction = etlVal.split('_')[1];
            dataToSend   = { idUser: idUser, todayTimeFormatted: todayTimeFormatted,
                idEtl: idEtlAction, numEtl: numEtlAction, idStation: idStation };
            actionLoad = true;
        }

        if (actionLoad)
        {
            var xhr = new XMLHttpRequest();
            xhr.open("POST", linkProcess, true);
            xhr.setRequestHeader("Content-Type", "application/json");

            xhr.onreadystatechange = function()
            {
                if (xhr.readyState === 4 && xhr.status === 200)
                {
                    var r = JSON.parse(xhr.responseText);
                    msgInfo.innerHTML     = r['js_text'];
                    msgInfo.style.display = 'block';

                    if (r['valid_process'])
                    {
                        msgInfo.style.border = '2px solid #09886d';
                        load_tab();
                    }
                    else { msgInfo.style.border = '2px solid #930000'; }
                }
            };
            xhr.send(JSON.stringify(dataToSend));
        }
    }


    // -----------------------------------------------
    // densitePts() — validate and collect density interval inputs

    function densitePts()
    {
        var bornes     = [];
        var inputError = false;
        var prevSupValue = null;

        for (var i = 1; i <= 4; i++)
        {
            var inf    = document.getElementById('inf_' + i);
            var sup    = document.getElementById('sup_' + i);
            var interv = document.getElementById('interv_' + i);

            var infVal    = inf.value.trim();
            var supVal    = sup.value.trim();
            var intervVal = interv.value.trim();

            if (infVal === '' && supVal === '' && intervVal === '') { continue; }

            if (infVal === '' || supVal === '' || intervVal === '') {
                msgInfo.innerText += 'Error: all fields on row ' + i + ' (inf, sup, interv) must be filled.\n';
                inputError = true; break;
            }
            if (!isInteger(infVal) || !isInteger(supVal) || !isInteger(intervVal)) {
                msgInfo.innerText += 'Error: fields on row ' + i + ' must be integers.\n';
                inputError = true; break;
            }
            var infInt = parseInt(infVal, 10), supInt = parseInt(supVal, 10), intervInt = parseInt(intervVal, 10);
            if (supInt <= infInt) {
                msgInfo.innerText += 'Error: row ' + i + ' — upper bound must be > lower bound.\n';
                inputError = true; break;
            }
            if (i > 1 && prevSupValue !== null && infInt <= prevSupValue) {
                msgInfo.innerText += 'Error: row ' + i + ' — lower bound must be > previous upper bound.\n';
                inputError = true; break;
            }
            bornes.push({ inf: infInt, sup: supInt, interv: intervInt });
            prevSupValue = supInt;
        }
        if (inputError) { return null; }
        return bornes;
    }


    // -----------------------------------------------
    // Date / time validation helpers

    function isInteger(v) { return Number.isInteger(Number(v)); }

    function isValidDatesInput(d1, d2, h1, h2)
    {
        if (isValidDate(d1.value) && isValidDate(d2.value))
        {
            if (isValidTime(h1.value) && isValidTime(h2.value))
            {
                var dt1 = parseDate(d1.value);
                var t1  = parseTime(h1.value); dt1.setHours(t1[0], t1[1], t1[2]);
                var dt2 = parseDate(d2.value);
                var t2  = parseTime(h2.value); dt2.setHours(t2[0], t2[1], t2[2]);
                if (dt1 < dt2) { return true; }
                msgInfo.innerText     = "'Start date and time' must be earlier than 'End date and time'";
                msgInfo.style.display = 'block';
                return false;
            }
            msgInfo.innerText     = "At least one of the entered times is invalid (HH:MM or HH:MM:SS accepted)";
            msgInfo.style.display = 'block';
            return false;
        }
        msgInfo.innerText     = "At least one of the entered dates is invalid (dd-mm-yyyy required)";
        msgInfo.style.display = 'block';
        return false;
    }

    function isValidDate(ds)
    {
        if (!/^(0[1-9]|[12][0-9]|3[01])-(0[1-9]|1[0-2])-(\d{4})$/.test(ds)) { return false; }
        var parts = ds.split('-').map(Number);
        var d = new Date(parts[2], parts[1] - 1, parts[0]);
        return d.getFullYear() === parts[2] && d.getMonth() === parts[1] - 1 && d.getDate() === parts[0];
    }

    function isValidTime(ts)
    {
        if (!/^([01]\d|2[0-3]):([0-5]\d)(:[0-5]\d)?$/.test(ts)) { return false; }
        var p = ts.split(':').map(Number);
        return !(p[0] < 0 || p[0] > 23 || p[1] < 0 || p[1] > 59 || (p[2] !== undefined && (p[2] < 0 || p[2] > 59)));
    }

    function parseDate(ds)
    {
        var p = ds.split('-').map(Number);
        return new Date(p[2], p[1] - 1, p[0]);
    }

    function parseTime(ts)
    {
        var p = ts.split(':').map(Number);
        return [p[0], p[1], p[2] || 0];
    }


    // -----------------------------------------------
    // ETL timeline — horizontal Gantt-style view of validity periods
    // Renders an SVG from ETL_array. Bars are clickable (toggle checkbox
    // + refresh graph). Selected bars are filled; unselected are dimmed.

    // Palette of 18 colors — used as a fallback before the Plotly chart is
    // rendered for the first time. Once the chart exists, etlColor() prefers
    // the actual trace color (marker.color) so the timeline bars always match
    // the chart traces regardless of how the server picked the palette.
    var ETL_COLORS = [
        '#7F77DD', '#D85A30', '#639922', '#E24B4A', '#378ADD', '#EF9F27',
        '#1D9E75', '#D4537E', '#534AB7', '#993C1D', '#3B6D11', '#A32D2D',
        '#185FA5', '#BA7517', '#0F6E56', '#993556', '#888780', '#444441'
    ];

    // Resolve the colour of ETL #numRef:
    //   1. preferred: look up the actual Plotly trace named "<TEXT_ET_LABEL_ETL_REF> <numRef>"
    //      and return its marker.color — this is the value the server actually
    //      drew, so the timeline cannot drift from the chart.
    //   2. fallback: use the static ETL_COLORS palette (covers the very first
    //      render, before load_graph() has populated plotDiv.data).
    var ETL_LABEL_PREFIX = '<?php echo TEXT_ET_LABEL_ETL_REF; ?>';
    function etlColor(numRef) {
        var plotDiv = document.getElementById('plot');
        if (plotDiv && plotDiv.data) {
            var target = ETL_LABEL_PREFIX + ' ' + numRef;
            for (var i = 0; i < plotDiv.data.length; i++) {
                var tr = plotDiv.data[i];
                if (tr && tr.name === target && tr.marker && tr.marker.color) {
                    return tr.marker.color;
                }
            }
        }
        return ETL_COLORS[((numRef - 1) % 18 + 18) % 18];
    }

    // Convert a hex color to a soft pastel background (mix with white).
    function pastelOf(hex, mix) {
        if (mix === undefined) { mix = 0.78; } // 0 = full color, 1 = white
        var r = parseInt(hex.substr(1, 2), 16);
        var g = parseInt(hex.substr(3, 2), 16);
        var b = parseInt(hex.substr(5, 2), 16);
        r = Math.round(r + (255 - r) * mix);
        g = Math.round(g + (255 - g) * mix);
        b = Math.round(b + (255 - b) * mix);
        return 'rgb(' + r + ',' + g + ',' + b + ')';
    }

    // Apply colored backgrounds to ETL table rows that are checked.
    function updateRowColors() {
        var rows = document.querySelectorAll("tr[data-etl-num]");
        rows.forEach(function(tr) {
            var num = parseInt(tr.getAttribute('data-etl-num'), 10);
            var cb  = tr.querySelector("input[name='check_ETL[]']");
            if (cb && cb.checked) {
                var color = etlColor(num);
                tr.style.backgroundColor = pastelOf(color, 0.78);
                // Hover stays readable: store the stronger tint for hover via JS
                tr.onmouseover = function() { tr.style.backgroundColor = pastelOf(color, 0.62); };
                tr.onmouseout  = function() { tr.style.backgroundColor = pastelOf(color, 0.78); };
            } else {
                tr.style.backgroundColor = '';
                tr.onmouseover = null;
                tr.onmouseout  = null;
            }
        });

        // Edit button is only visible when EXACTLY ONE RC is checked.
        // Del was already shown by load_tab() when the table has at
        // least one row — we only manage Edit's exact-1 case here.
        if (buttonEdit) {
            var nbChecked = document.querySelectorAll("input[name='check_ETL[]']:checked").length;
            buttonEdit.style.display = (nbChecked === 1) ? 'block' : 'none';
        }
    }

    // Parse "dd-mm-yyyy HH:MM:SS" → JS Date
    function parseEtlDateTime(s) {
        if (!s || s === '-') { return null; }
        var parts = s.split(' ');
        var d = parts[0].split('-').map(Number);
        var t = (parts[1] || '00:00:00').split(':').map(Number);
        return new Date(d[2], d[1] - 1, d[0], t[0] || 0, t[1] || 0, t[2] || 0);
    }

    // Build [{id, num, t1, t2, checked, label1, label2}, ...] sorted by t1 asc
    function buildTimelineRows() {
        if (!ETL_array) { return []; }

        // First, map id → row number using the table order (same logic as PHP)
        var checkboxes = document.querySelectorAll("input[name='check_ETL[]']");
        var idToNum = {};
        var idToChecked = {};
        checkboxes.forEach(function(cb) {
            var parts = cb.value.split('_');
            idToNum[parts[0]] = parseInt(parts[1], 10);
            idToChecked[parts[0]] = cb.checked;
        });

        var rows = [];
        Object.keys(ETL_array).forEach(function(id) {
            var info = ETL_array[id];
            var t1 = parseEtlDateTime(info.datetime_first);
            var t2 = parseEtlDateTime(info.datetime_end);
            if (t1 && t2) {
                rows.push({
                    id: id,
                    num: idToNum[id] || 0,
                    t1: t1,
                    t2: t2,
                    checked: !!idToChecked[id],
                    label1: info.datetime_first,
                    label2: info.datetime_end
                });
            }
        });
        rows.sort(function(a, b) { return a.t1 - b.t1; });
        return rows;
    }

    // Format a duration in ms → human string
    function formatDuration(ms) {
        var days = Math.round(ms / 86400000);
        if (days < 31)  { return days + ' <?php echo TEXT_ET_TIMELINE_UNIT_DAYS; ?>'; }
        if (days < 365) { return Math.round(days / 30) + ' <?php echo TEXT_ET_TIMELINE_UNIT_MONTHS; ?>'; }
        var years  = Math.floor(days / 365);
        var months = Math.round((days % 365) / 30);
        var yearUnit = years > 1
            ? '<?php echo TEXT_ET_TIMELINE_UNIT_YEARS;    ?>'
            : '<?php echo TEXT_ET_TIMELINE_UNIT_YEAR;     ?>';
        return months > 0 ? years + ' ' + yearUnit + ' ' + months + ' <?php echo TEXT_ET_TIMELINE_UNIT_MONTHS; ?>'
                          : years + ' ' + yearUnit;
    }

    function renderTimeline()
    {
        var wrap   = document.getElementById('etl_timeline_wrap');
        var holder = document.getElementById('etl_timeline');
        var rows   = buildTimelineRows();

        if (rows.length === 0) {
            wrap.style.display = 'none';
            return;
        }
        wrap.style.display = 'block';

        // Domain: global min t1, max t2
        var tMin = rows[0].t1, tMax = rows[0].t2;
        rows.forEach(function(r) {
            if (r.t1 < tMin) { tMin = r.t1; }
            if (r.t2 > tMax) { tMax = r.t2; }
        });
        // Add 2% padding on each side
        var span = tMax - tMin;
        var pad  = span * 0.02;
        var dMin = new Date(tMin.getTime() - pad);
        var dMax = new Date(tMax.getTime() + pad);

        // Layout — single-row timeline.
        // ETL periods never overlap, so we can stack every bar on the same
        // horizontal lane. The bar itself carries the "ETL N" label inline.
        var width        = holder.clientWidth || 800;
        var leftMargin   = 20;
        var rightMargin  = 20;
        var topMargin    = 8;
        var bottomMargin = 28;
        var barHeight    = 22;
        var plotW  = width - leftMargin - rightMargin;
        var plotH  = barHeight;
        var totalH = plotH + topMargin + bottomMargin;

        var domainMs = dMax - dMin;
        function xOf(date) { return leftMargin + ((date - dMin) / domainMs) * plotW; }

        // Year ticks
        var ticks = [];
        var y1 = dMin.getFullYear(), y2 = dMax.getFullYear();
        var step = (y2 - y1 > 12) ? 2 : 1;
        for (var y = y1; y <= y2; y += step) {
            ticks.push(new Date(y, 0, 1));
        }

        var svg = '<svg width="100%" viewBox="0 0 ' + width + ' ' + totalH + '"'
                + ' xmlns="http://www.w3.org/2000/svg" style="display:block;">';

        // Baseline
        var baseY = topMargin + plotH + 4;
        svg += '<line x1="' + leftMargin + '" y1="' + baseY
             + '" x2="' + (leftMargin + plotW) + '" y2="' + baseY
             + '" stroke="#888" stroke-width="0.5"/>';

        // Year ticks + labels
        ticks.forEach(function(t) {
            var x = xOf(t);
            if (x >= leftMargin && x <= leftMargin + plotW) {
                svg += '<line x1="' + x + '" y1="' + topMargin + '" x2="' + x + '" y2="' + baseY
                     + '" stroke="#ddd" stroke-width="0.5"/>';
                svg += '<text x="' + x + '" y="' + (baseY + 14)
                     + '" font-size="10" fill="#666" text-anchor="middle">' + t.getFullYear() + '</text>';
            }
        });

        // Bars — all on the same row at y = topMargin
        rows.forEach(function(r) {
            var x  = xOf(r.t1);
            var w  = Math.max(2, xOf(r.t2) - xOf(r.t1));
            var y  = topMargin;
            var fill   = r.checked ? etlColor(r.num) : '#e8e8e8';
            var stroke = r.checked ? etlColor(r.num) : '#bbb';
            var textColor = r.checked ? '#fff' : '#666';
            var opacity   = r.checked ? '1' : '0.7';

            svg += '<g class="etl_bar" data-id="' + r.id + '" data-num="' + r.num
                 + '" style="cursor:pointer;opacity:' + opacity + ';">';
            // Bar
            svg += '<rect x="' + x + '" y="' + y + '" width="' + w + '" height="' + barHeight
                 + '" rx="3" fill="' + fill + '" stroke="' + stroke + '" stroke-width="1"/>';

            // Inline "RC N" label, centered in the bar. Skip when the bar
            // is too narrow to read it (will still be reachable via tooltip).
            if (w > 26) {
                svg += '<text x="' + (x + w / 2) + '" y="' + (y + barHeight * 0.68)
                     + '" font-size="11" font-weight="600" fill="' + textColor
                     + '" text-anchor="middle" style="pointer-events:none;">'
                     + '<?php echo TEXT_ET_TIMELINE_TT_RC; ?> ' + r.num + '</text>';
            }
            svg += '</g>';
        });

        svg += '</svg>';
        holder.innerHTML = svg;

        // Wire interactions
        var tooltip = document.getElementById('etl_timeline_tooltip');
        holder.querySelectorAll('.etl_bar').forEach(function(g) {
            g.addEventListener('click', function() {
                var id = g.getAttribute('data-id');
                var cb = document.querySelector("input[name='check_ETL[]'][value^='" + id + "_']");
                if (cb) {
                    cb.checked = !cb.checked;
                    load_graph();
                    renderTimeline();
                    updateRowColors();
                }
            });
            g.addEventListener('mousemove', function(e) {
                var id  = g.getAttribute('data-id');
                var num = g.getAttribute('data-num');
                var info = ETL_array[id];
                if (!info) { return; }
                var t1 = parseEtlDateTime(info.datetime_first);
                var t2 = parseEtlDateTime(info.datetime_end);
                var dur = formatDuration(t2 - t1);
                tooltip.innerHTML = '<b><?php echo TEXT_ET_TIMELINE_TT_RC; ?> ' + num + '</b><br>'
                                  + '<?php echo TEXT_ET_TIMELINE_TT_START; ?> : ' + info.datetime_first + '<br>'
                                  + '<?php echo TEXT_ET_TIMELINE_TT_END;   ?>&nbsp;&nbsp;&nbsp; : ' + info.datetime_end + '<br>'
                                  + '<?php echo TEXT_ET_TIMELINE_TT_DUR;   ?> : ' + dur;
                tooltip.style.display = 'block';
                tooltip.style.left = (e.clientX + 6) + 'px';
                tooltip.style.top  = (e.clientY + 6) + 'px';
            });
            g.addEventListener('mouseleave', function() {
                tooltip.style.display = 'none';
            });
        });
    }

    // Re-render the timeline when checkboxes are toggled from the table
    document.addEventListener('change', function(e) {
        if (e.target && e.target.name === 'check_ETL[]') {
            renderTimeline();
            updateRowColors();
        }
    });

    // Reload the chart when display options (swap) change, AND persist
    // the new state to TABLE_USER_MENU so the choice survives reloads.
    // Same endpoint and contract as the side-nav accordion sections —
    // menu_id 'etl_swap_axes' is a dedicated key for this preference.
    document.addEventListener('change', function(e) {
        if (e.target && e.target.id === 'swap_axes') {
            load_graph();

            var xhr = new XMLHttpRequest();
            xhr.open("POST", "include/structure/box/process_menu.php", true);
            xhr.setRequestHeader("Content-Type", "application/json");
            xhr.send(JSON.stringify({
                id_user: idUser,
                menu_id: 'etl_swap_axes',
                is_open: e.target.checked
            }));
        }
    });

    // Re-render on window resize (debounced)
    var _resizeTimer = null;
    window.addEventListener('resize', function() {
        clearTimeout(_resizeTimer);
        _resizeTimer = setTimeout(renderTimeline, 150);
    });

    // -----------------------------------------------
    // Delete RC flow (modern: detailed preview + math challenge)
    //
    // Triggered by the "Delete" button. Reads the checked RCs from the
    // table, fetches details from the server (periods + nb points),
    // shows a confirmation popup with a math challenge, then calls
    // the server to delete on confirm.

    function attemptDeleteRC()
    {
        var checked = getCheckedValues(); // array of "id_num" strings
        if (!checked || checked.length === 0) {
            contenuInfo.innerHTML    = '<?php echo TEXT_ET_DEL_NO_SELECTION; ?>';
            contenuInfo.style.border = '2px solid #930000';
            contenuInfo.style.display = 'block';
            return;
        }

        // Extract bare ids ("12_3" -> 12)
        var ids = checked.map(function(v) { return parseInt(v.split('_')[0], 10); })
                         .filter(function(n) { return !isNaN(n); });

        if (ids.length === 0) { return; }

        // Fetch info from server (periods + nb points)
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'include/structure/etl/process_etl_delete_info.php', true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.onreadystatechange = function() {
            if (xhr.readyState !== 4) return;
            if (xhr.status !== 200) {
                console.error('[Delete RC] HTTP', xhr.status, xhr.responseText);
                return;
            }
            var r;
            try { r = JSON.parse(xhr.responseText); }
            catch (e) {
                console.error('[Delete RC] bad JSON:', xhr.responseText);
                return;
            }
            if (!r.items || r.items.length === 0) { return; }
            openDeleteChallengePopup(r.items);
        };
        xhr.send(JSON.stringify({ ids: ids }));
    }


    // Format SQL datetime "yyyy-mm-dd HH:MM:SS" → "dd-mm-yyyy HH:MM"
    function fmtSqlDateForDelete(s) {
        if (!s) return '';
        var m = String(s).match(/^(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2})/);
        if (!m) return s;
        return m[3] + '-' + m[2] + '-' + m[1] + ' ' + m[4] + ':' + m[5];
    }


    function openDeleteChallengePopup(items)
    {
        var prev = document.getElementById('rc_delete_challenge');
        if (prev) prev.remove();

        // Generate math challenge (same logic as Save popup)
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

        // Build the list of RCs to be deleted
        var listHtml = '<ul style="margin:6px 0 0 18px;padding:0;list-style:disc;">';
        for (var i = 0; i < items.length; i++) {
            var it = items[i];
            var beforeTxt = fmtSqlDateForDelete(it.datetime_first) + ' &rarr; ' + fmtSqlDateForDelete(it.datetime_end);
            listHtml += '<li style="margin-bottom:6px;">'
                      + '<b>RC ' + it.num + '</b> &mdash; '
                      + '<span style="font-size:11px;color:#666;">' + it.nb_points + ' <?php echo TEXT_ET_DEL_POINTS; ?></span>'
                      + '<br><span style="color:#666;font-size:11px;"><?php echo TEXT_ET_NEW_CHALLENGE_BEFORE; ?> :</span> ' + beforeTxt
                      + '<br><span style="color:#a32d2d;font-size:11px;"><?php echo TEXT_ET_NEW_CHALLENGE_AFTER;  ?> :</span> '
                      + '<i><?php echo TEXT_ET_NEW_CHALLENGE_DELETED; ?></i>'
                      + '</li>';
        }
        listHtml += '</ul>';

        var overlay = document.createElement('div');
        overlay.id = 'rc_delete_challenge';
        overlay.style.cssText =
            'position:fixed;top:0;left:0;width:100vw;height:100vh;'
          + 'background:rgba(0,0,0,0.4);z-index:3000;'
          + 'display:flex;align-items:center;justify-content:center;';
        overlay.innerHTML =
            '<div style="background:#FBF9F1;border-radius:4px;width:520px;max-width:90vw;'
          + 'box-shadow:0 8px 24px rgba(0,0,0,0.3);overflow:hidden;font-family:inherit;">'
          + '<div style="background:#a32d2d;color:#fff;padding:10px 14px;font-size:14px;font-weight:bold;">'
          +   '<?php echo TEXT_ET_DEL_CONFIRM_TITLE; ?>'
          + '</div>'
          + '<div style="padding:14px 16px;font-size:13px;line-height:1.5;color:#333;">'
          +   '<?php echo TEXT_ET_DEL_CONFIRM_MSG; ?>'
          +   '<div style="margin:10px 0;padding:8px 10px;background:#fdecec;border-left:3px solid #a32d2d;font-size:12px;">'
          +     '<b>' + items.length + ' <?php echo TEXT_ET_DEL_RC_TO_DELETE; ?></b>'
          +     listHtml
          +   '</div>'
          +   '<div style="margin-top:14px;padding:10px;background:#fff;border:1px solid #ddd;border-radius:3px;">'
          +     '<div style="font-size:12px;color:#666;margin-bottom:6px;">'
          +       '<?php echo TEXT_ET_NEW_CHALLENGE_HINT; ?>'
          +     '</div>'
          +     '<div style="display:flex;align-items:center;gap:8px;">'
          +       '<span style="font-size:16px;font-weight:bold;">' + a + ' ' + op + ' ' + b + ' = </span>'
          +       '<input id="rc_delete_answer" type="text" style="width:60px;font-size:16px;padding:4px;" autofocus>'
          +       '<span id="rc_delete_feedback" style="font-size:12px;"></span>'
          +     '</div>'
          +   '</div>'
          + '</div>'
          + '<div style="padding:8px 14px 14px;display:flex;justify-content:flex-end;gap:8px;">'
          +   '<button id="rc_delete_cancel" class="button_close" style="width:120px;"><?php echo TEXT_BTN_CANCEL; ?></button>'
          +   '<button id="rc_delete_confirm" class="button" style="width:140px;opacity:0.45;cursor:not-allowed;" disabled>'
          +     '<?php echo TEXT_ET_DEL_BTN_CONFIRM; ?>'
          +   '</button>'
          + '</div>'
          + '</div>';
        document.body.appendChild(overlay);

        var input      = overlay.querySelector('#rc_delete_answer');
        var feedback   = overlay.querySelector('#rc_delete_feedback');
        var confirmBtn = overlay.querySelector('#rc_delete_confirm');
        var cancelBtn  = overlay.querySelector('#rc_delete_cancel');

        // Hover effects
        [confirmBtn, cancelBtn].forEach(function(btn) {
            btn.addEventListener('mouseenter', function() {
                if (btn.disabled) return;
                btn.style.filter = 'brightness(0.9)';
            });
            btn.addEventListener('mouseleave', function() { btn.style.filter = ''; });
        });

        function setEnabled(on) {
            confirmBtn.disabled = !on;
            confirmBtn.style.opacity = on ? '1'   : '0.45';
            confirmBtn.style.cursor  = on ? 'pointer' : 'not-allowed';
        }

        input.addEventListener('input', function() {
            var v = parseInt(input.value, 10);
            if (input.value === '' || isNaN(v)) {
                feedback.textContent = '';
                setEnabled(false);
            } else if (v === expected) {
                feedback.textContent = '✓';
                feedback.style.color = '#0a7d34';
                setEnabled(true);
            } else {
                feedback.textContent = '✗';
                feedback.style.color = '#a32d2d';
                setEnabled(false);
            }
        });

        function cleanup() { overlay.remove(); document.removeEventListener('keydown', onKey); }
        function onKey(e) {
            if (e.key === 'Escape') { cleanup(); }
            if (e.key === 'Enter' && !confirmBtn.disabled) {
                cleanup();
                doDeleteRC(items);
            }
        }
        document.addEventListener('keydown', onKey);

        cancelBtn.addEventListener('click', cleanup);
        confirmBtn.addEventListener('click', function() {
            cleanup();
            doDeleteRC(items);
        });

        setTimeout(function() { input.focus(); }, 100);
    }


    function doDeleteRC(items)
    {
        var ids = items.map(function(it) { return it.id; });
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'include/structure/etl/process_etl_delete_multi.php', true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.onreadystatechange = function() {
            if (xhr.readyState !== 4) return;
            if (xhr.status !== 200) {
                console.error('[Delete RC] save HTTP', xhr.status, xhr.responseText);
                alert('<?php echo TEXT_ET_DEL_ERR; ?>');
                return;
            }
            var r;
            try { r = JSON.parse(xhr.responseText); }
            catch (e) {
                console.error('[Delete RC] save bad JSON:', xhr.responseText);
                alert('<?php echo TEXT_ET_DEL_ERR; ?>');
                return;
            }
            if (r.valid_process) {
                window.location.reload();
            } else {
                alert(r.js_text || '<?php echo TEXT_ET_DEL_ERR; ?>');
            }
        };
        xhr.send(JSON.stringify({
            idUser: idUser,
            todayTimeFormatted: todayTimeFormatted,
            idStation: idStation,
            ids: ids
        }));
    }

</script>