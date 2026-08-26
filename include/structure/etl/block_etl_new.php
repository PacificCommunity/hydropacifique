<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
New ETL popup (v2) — included by modif_etl.php
Wide popup with two columns:
  - Left  : controls (period, model, density bounds, conflicts, R²)
  - Right : live preview chart (JGE points + fitted curve)
This is step A: only "period" and "preview" are wired. Other sections
are placeholders for steps B and C.
----------------------------------------
*/

echo "<div id='box_elt_new' class='block_view'"
   . " style='display:none;position:fixed;top:5vh;left:5vw;width:90vw;height:82vh;"
   . "background:transparent;z-index:2000;resize:both;overflow:hidden;min-width:680px;min-height:400px;"
   . "border:1px solid #c0c0c0;border-radius:4px;box-shadow:0 4px 16px rgba(0,0,0,0.15);'>\n";

    echo "<div id='cadre_view_2' style='padding:0;margin:0 !important;margin-top:0 !important;background-color:#FBF9F1;border-radius:4px;"
       . "overflow:hidden;height:100%;display:flex;flex-direction:column;'>\n";

        // ---- Header (drag handle) ----
        // The platform CSS (#cadre_view_2 > *:first-child) automatically
        // turns the first direct child into a teal header bar with white
        // text. The close button uses id="button_close" to inherit the
        // platform's hover style (rounded white-transparent tile).
        echo "<div id='box_elt_new_header'"
           . " style='cursor:move;user-select:none;flex-shrink:0;'>\n";
            echo "<span>" . TEXT_ET_NEW_TITLE . "</span>";
            echo "<span id='button_close' onclick=\"document.getElementById('box_elt_new').style.display='none';\">&times;</span>";
        echo "</div>\n";

        // ---- Body: two columns (grid track height is constrained to popup height minus header) ----
        echo "<div style='display:grid;grid-template-columns:340px 1fr;gap:0;flex:1 1 0;min-height:0;overflow:hidden;'>\n";

            // ===== LEFT COLUMN: controls (only this one scrolls) =====
            echo "<div style='padding:14px 16px;border-right:1px solid #e0e0e0;overflow-y:auto;min-height:0;height:100%;box-sizing:border-box;'>\n";

                // -- Section 1: Analysis period --
                echo "<p style='font-size:13px;font-weight:bold;margin:0 0 8px;'>"
                   . TEXT_ET_NEW_STEP1 . "</p>";

                echo "<div style='margin-bottom:6px;'>\n";
                    echo "<label style='display:block;font-size:11px;color:#666;margin-bottom:2px;'>"
                       . TEXT_ET_POPUP_PERIOD_START . "</label>";
                    echo "<div style='display:flex;gap:4px;'>";
                        echo "<input style='width:95px;box-sizing:border-box;'"
                           . " id='new_date_debut_periode' type='text'"
                           . " onFocus='initDatepickers(this)' placeholder='dd-mm-yyyy'>";
                        echo "<input style='width:75px;box-sizing:border-box;'"
                           . " id='new_heure_debut_periode' type='text' value='00:00:00'>";
                    echo "</div>";
                echo "</div>\n";

                echo "<div style='margin-bottom:8px;'>\n";
                    echo "<label style='display:block;font-size:11px;color:#666;margin-bottom:2px;'>"
                       . TEXT_ET_POPUP_PERIOD_END . "</label>";
                    echo "<div style='display:flex;gap:4px;'>";
                        echo "<input style='width:95px;box-sizing:border-box;'"
                           . " id='new_date_fin_periode' type='text' data-allow-future='1'"
                           . " onFocus='initDatepickers(this)' placeholder='dd-mm-yyyy'>";
                        echo "<input style='width:75px;box-sizing:border-box;'"
                           . " id='new_heure_fin_periode' type='text' value='23:59:59'>";
                    echo "</div>";
                echo "</div>\n";

                echo "<p id='new_jge_count' style='font-size:11px;color:#666;margin:0 0 14px;font-style:italic;'>—</p>";

                // -- Section 2: Model -- (initially hidden, revealed by the
                //    "Add regression" trigger below; user sees only the
                //    gauging points first, decides on a regression actively.)
                echo "<div id='new_regression_trigger' style='margin:4px 0 14px;text-align:left;'>";
                    echo "<div style='font-size:11px;color:#888;margin-bottom:6px;'>"
                       . TEXT_ET_NEW_ADD_REGRESSION_HINT . "</div>";
                    echo "<button type='button' id='btn_add_regression'"
                       . " style='display:inline-flex;align-items:center;gap:8px;padding:8px 18px;"
                       . "font-size:13px;font-weight:600;color:#fff;background:#D85A30;border:none;"
                       . "border-radius:4px;cursor:pointer;box-shadow:0 1px 3px rgba(0,0,0,0.12);"
                       . "transition:background 0.15s,transform 0.05s,box-shadow 0.15s;'>";
                        // Inline SVG plus icon — sharper and easier to style than a character
                        echo "<svg width='14' height='14' viewBox='0 0 24 24' fill='none'"
                           . " stroke='currentColor' stroke-width='2.5' stroke-linecap='round'>"
                           . "<line x1='12' y1='5' x2='12' y2='19'/>"
                           . "<line x1='5' y1='12' x2='19' y2='12'/></svg>";
                        echo "<span>" . TEXT_ET_NEW_ADD_REGRESSION . "</span>";
                    echo "</button>";
                echo "</div>\n";

                echo "<div id='new_regression_section' style='display:none;'>\n";

                echo "<p style='font-size:13px;font-weight:bold;margin:0 0 8px;'>"
                   . TEXT_ET_NEW_STEP2 . "</p>";

                // Model name (dark red) + equation, all bold, on one line.
                $eq_style    = "font-family:'Consolas','Menlo',monospace;font-size:12px;color:#1a1a1a;font-weight:bold;";
                $model_style = "color:#930000;font-weight:bold;";

                echo "<div style='display:flex;flex-direction:column;gap:8px;margin-bottom:10px;font-size:12px;'>\n";
                    // -- Power law --
                    echo "<label style='display:flex;align-items:center;cursor:pointer;'>"
                       . "<input type='radio' name='new_eq_etl' value='1' checked style='margin:0 6px 0 0;'>"
                       . "<span style='" . $model_style . "'>" . TEXT_ET_NEW_MODEL_POWER . " : </span>"
                       . "<span style='" . $eq_style . "'>&nbsp;Q = a·(H − H₀)<sup>b</sup></span>"
                       . "</label>";

                    // H0 sits directly under Power law (only meaningful there);
                    // shown/hidden by JS depending on the selected model.
                    echo "<div id='new_h0_wrap' style='margin-left:22px;'>\n";
                        // Narrow field + label + unit on one tidy row.
                        echo "<div style='display:flex;align-items:center;gap:6px;'>\n";
                            echo "<label style='font-size:11px;color:#666;'>"
                               . TEXT_ET_NEW_H0_LABEL . "</label>";
                            echo "<input id='origine_h0' type='text' value='0'"
                               . " style='width:52px;text-align:right;box-sizing:border-box;background:#eee;' disabled>";
                            echo "<span style='font-size:11px;color:#999;'>cm</span>";
                        echo "</div>\n";

                        // Optimise H₀ toggle — sweeps H₀ to maximise the
                        // log-log fit instead of using the entered value.
                        echo "<label style='display:flex;align-items:center;gap:6px;font-size:11px;color:#555;margin-top:5px;cursor:pointer;'>";
                            echo "<input type='checkbox' id='new_h0_auto' style='margin:0;' checked>";
                            echo TEXT_ET_NEW_H0_AUTO;
                        echo "</label>";
                    echo "</div>\n";

                    // -- Polynomial --
                    echo "<label style='display:flex;align-items:center;cursor:pointer;'>"
                       . "<input type='radio' name='new_eq_etl' value='2' style='margin:0 6px 0 0;'>"
                       . "<span style='" . $model_style . "'>" . TEXT_ET_NEW_MODEL_POLY . " : </span>"
                       . "<span style='" . $eq_style . "'>&nbsp;Q = aH² + bH + c</span>"
                       . "</label>";

                    // -- Linear (added on request; not hydrologically ideal
                    //    but sometimes requested for simple/diagnostic fits) --
                    echo "<label style='display:flex;align-items:center;cursor:pointer;'>"
                       . "<input type='radio' name='new_eq_etl' value='3' style='margin:0 6px 0 0;'>"
                       . "<span style='" . $model_style . "'>" . TEXT_ET_NEW_MODEL_LINEAR . " : </span>"
                       . "<span style='" . $eq_style . "'>&nbsp;Q = a·H + b</span>"
                       . "</label>";
                echo "</div>\n";

                echo "</div>\n"; // end #new_regression_section

                // Sections 3 to 5 + Save button are all gated behind the
                // "Add regression" trigger. Initially hidden, they appear
                // when the user explicitly activates the regression workflow.
                echo "<div id='new_regression_dependent' style='display:none;'>\n";

                // -- Section 3: Regression result --
                echo "<p style='font-size:13px;font-weight:bold;margin:0 0 6px;'>"
                   . TEXT_ET_NEW_STEP3 . "</p>";

                echo "<div id='new_regression_box' style='background:#f3efe2;padding:8px 10px;"
                   . "border-radius:3px;font-size:12px;margin-bottom:6px;min-height:42px;color:#666;'>"
                   . TEXT_ET_NEW_REGRESSION_HINT
                   . "</div>\n";

                // Toggle for the 95% prediction interval band
                echo "<label style='display:flex;align-items:center;gap:6px;font-size:11px;color:#555;margin-bottom:14px;cursor:pointer;'>";
                    echo "<input type='checkbox' id='new_show_pi' style='margin:0;'>";
                    echo TEXT_ET_NEW_SHOW_PI;
                echo "</label>";

                // -- Section 4: Density intervals --
                echo "<p style='font-size:13px;font-weight:bold;margin:0 0 6px;'>"
                   . TEXT_ET_NEW_STEP4 . "</p>";

                echo "<table style='font-size:11px;width:100%;margin-bottom:14px;'>\n";
                    echo "<thead><tr style='color:#666;'>";
                        echo "<th style='text-align:left;font-weight:normal;width:33%;'>"
                           . TEXT_ET_NEW_BORNE_INF . "</th>";
                        echo "<th style='text-align:left;font-weight:normal;width:33%;'>"
                           . TEXT_ET_NEW_BORNE_SUP . "</th>";
                        echo "<th style='text-align:left;font-weight:normal;width:33%;'>"
                           . TEXT_ET_NEW_INTERVAL . "</th>";
                    echo "</tr></thead>";
                    echo "<tbody>\n";
                    $defaults = [['0','100','10'],['110','200','20'],['','',''],['','','']];
                    foreach ($defaults as $i => $d) {
                        $n = $i + 1;
                        echo "<tr>";
                            echo "<td><input class='new_density' id='inf_{$n}'    type='text' value='{$d[0]}' style='width:90%;'></td>";
                            echo "<td><input class='new_density' id='sup_{$n}'    type='text' value='{$d[1]}' style='width:90%;'></td>";
                            echo "<td><input class='new_density' id='interv_{$n}' type='text' value='{$d[2]}' style='width:90%;'></td>";
                        echo "</tr>";
                    }
                    echo "</tbody>";
                echo "</table>\n";

                // -- Section 5: Period conflicts (filled dynamically when Save is clicked) --
                echo "<p style='font-size:13px;font-weight:bold;margin:14px 0 6px;'>"
                   . TEXT_ET_NEW_STEP5 . "</p>";
                echo "<div id='new_conflicts_box' style='font-size:11px;color:#666;'>"
                   . TEXT_ET_NEW_CONFLICTS_HINT
                   . "</div>";

                echo "</div>\n"; // end #new_regression_dependent

            echo "</div>\n";

            // ===== RIGHT COLUMN: preview chart (never scrolls, plot shrinks instead) =====
            echo "<div style='padding:14px 16px;display:flex;flex-direction:column;min-height:0;height:100%;box-sizing:border-box;overflow:hidden;'>\n";

                // Top row: title + action buttons (always visible)
                echo "<div style='display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;flex-shrink:0;gap:8px;'>\n";
                    echo "<div style='display:flex;align-items:center;gap:14px;'>\n";
                        echo "<span style='font-size:13px;font-weight:bold;'>"
                           . TEXT_ET_NEW_PREVIEW_TITLE . "</span>";
                        // Log-log axes toggle — switches both axes to a log
                        // scale. In log-log a power law plots as a straight
                        // line, making it easy to judge how well the gaugings
                        // align. Purely a display option (no recompute).
                        echo "<label style='display:flex;align-items:center;gap:5px;font-size:11px;color:#555;cursor:pointer;'>";
                            echo "<input type='checkbox' id='new_loglog' style='margin:0;'>";
                            echo TEXT_ET_NEW_LOGLOG_AXES;
                        echo "</label>";
                    echo "</div>\n";
                    echo "<div style='display:flex;gap:8px;'>\n";
                        echo "<input type='button' class='button_close' style='width:120px;' value='"
                           . TEXT_BTN_CANCEL . "'"
                           . " onclick=\"document.getElementById('box_elt_new').style.display='none';\">";
                        echo "<input type='button' class='button' style='width:160px;display:none;' id='new_etl' value='"
                           . TEXT_BTN_SAVE . "' onclick='attemptSaveNewETL();'>";
                    echo "</div>\n";
                echo "</div>\n";

                echo "<div id='new_preview_plot' style='flex:1;min-height:0;background:#fff;border:1px solid #e0e0e0;border-radius:3px;'></div>\n";

                // Hint line under the chart — explains the 3 interactive
                // gestures available on the preview plot. Reuses constants
                // already defined for the JGE click action, the curve
                // edit/drag hint, and the right-click add/remove hint.
                echo "<div style='margin-top:6px;font-size:11px;color:#666;flex-shrink:0;'>"
                   . TEXT_ET_NEW_JGE_CLICK_HINT
                   . " &middot; "
                   . TEXT_ET_NEW_CURVE_HINT
                   . " &middot; "
                   . TEXT_ET_EDIT_HINT_RCLICK
                   . "</div>\n";

            echo "</div>\n";

        echo "</div>\n";

    echo "</div>\n";

echo "</div>\n";
?>

<script>
    var box_elt_new = document.getElementById('box_elt_new');

    document.addEventListener("keydown", function(event) {
        if (event.key === "Escape" && box_elt_new.style.display !== 'none') {
            box_elt_new.style.display = "none";
        }
    });

    // Reset the one-shot validity-range auto-fill each time the popup opens,
    // so the next regression re-derives the ranges from the fresh data.
    if (box_elt_new) {
        var _lastDispNewEtl = box_elt_new.style.display;
        new MutationObserver(function() {
            var d = box_elt_new.style.display;
            if (d !== _lastDispNewEtl) {
                _lastDispNewEtl = d;
                if (d === 'block') { newRangeAutoFilled = false; }
            }
        }).observe(box_elt_new, { attributes: true, attributeFilter: ['style'] });
    }

    // -----------------------------------------------
    // Drag the popup by its header

    (function() {
        var header = document.getElementById('box_elt_new_header');
        if (!header) return;
        var dragging = false;
        var offX = 0, offY = 0;
        header.addEventListener('mousedown', function(e) {
            // Don't start dragging when clicking the close button (×)
            if (e.target.tagName === 'SPAN' && e.target.textContent === '×') return;
            dragging = true;
            var rect = box_elt_new.getBoundingClientRect();
            offX = e.clientX - rect.left;
            offY = e.clientY - rect.top;
            document.body.style.userSelect = 'none';
        });
        document.addEventListener('mousemove', function(e) {
            if (!dragging) return;
            var newLeft = e.clientX - offX;
            var newTop  = e.clientY - offY;

            // Clamp the popup inside the viewport so the user can't drag
            // it off-screen. We allow it to flush with the edges (left:0,
            // top:0) but not go beyond.
            var w = box_elt_new.offsetWidth;
            var h = box_elt_new.offsetHeight;
            var vw = window.innerWidth;
            var vh = window.innerHeight;

            if (newLeft < 0)            { newLeft = 0; }
            if (newTop  < 0)            { newTop  = 0; }
            if (newLeft + w > vw)       { newLeft = vw - w; }
            if (newTop  + h > vh)       { newTop  = vh - h; }

            box_elt_new.style.left = newLeft + 'px';
            box_elt_new.style.top  = newTop  + 'px';
        });
        document.addEventListener('mouseup', function() {
            if (!dragging) return;
            dragging = false;
            document.body.style.userSelect = '';
        });
    })();

    // -----------------------------------------------
    // Resize the preview plot when the popup is resized
    // Also clamps the popup so it never exceeds the viewport (which would
    // happen if the user drags the resize handle outside the screen).

    (function() {
        if (!window.ResizeObserver) return;
        var plotEl = document.getElementById('new_preview_plot');
        if (!plotEl) return;
        var resizeDebounce = null;
        var ro = new ResizeObserver(function() {
            clearTimeout(resizeDebounce);
            resizeDebounce = setTimeout(function() {
                if (plotEl.offsetWidth > 0 && plotEl.children.length > 0) {
                    Plotly.Plots.resize(plotEl);
                }
            }, 80);
        });
        ro.observe(plotEl);

        // Clamp popup dimensions + position so it stays fully visible
        // when the user drags the resize handle.
        var popup = document.getElementById('box_elt_new');
        if (!popup) return;
        var clampObserver = new ResizeObserver(function() {
            var rect = popup.getBoundingClientRect();
            var vw = window.innerWidth;
            var vh = window.innerHeight;
            var changed = false;

            // Cap size at viewport (minus a tiny margin to keep scrollbars
            // from creeping in).
            var maxW = vw - 4;
            var maxH = vh - 4;
            if (rect.width > maxW) {
                popup.style.width = maxW + 'px';
                changed = true;
            }
            if (rect.height > maxH) {
                popup.style.height = maxH + 'px';
                changed = true;
            }

            // If after resizing the popup overflows on the right or bottom,
            // pull it back into view.
            if (rect.left + rect.width > vw) {
                popup.style.left = Math.max(0, vw - rect.width) + 'px';
                changed = true;
            }
            if (rect.top + rect.height > vh) {
                popup.style.top  = Math.max(0, vh - rect.height) + 'px';
                changed = true;
            }
            // Ensure left/top never go negative.
            if (rect.left < 0) { popup.style.left = '0px'; }
            if (rect.top  < 0) { popup.style.top  = '0px'; }
        });
        clampObserver.observe(popup);

        // Window resize / fullscreen toggle: re-clamp the popup so it
        // stays visible if the viewport shrinks below the popup size.
        window.addEventListener('resize', function() {
            if (popup.style.display === 'none') return;
            var rect = popup.getBoundingClientRect();
            var vw = window.innerWidth, vh = window.innerHeight;
            if (rect.width  > vw - 4) { popup.style.width  = (vw - 4) + 'px'; }
            if (rect.height > vh - 4) { popup.style.height = (vh - 4) + 'px'; }
            // Refresh rect after potential size cap
            rect = popup.getBoundingClientRect();
            if (rect.left + rect.width  > vw) { popup.style.left = Math.max(0, vw - rect.width)  + 'px'; }
            if (rect.top  + rect.height > vh) { popup.style.top  = Math.max(0, vh - rect.height) + 'px'; }
            if (rect.left < 0) { popup.style.left = '0px'; }
            if (rect.top  < 0) { popup.style.top  = '0px'; }
        });
    })();

    // -----------------------------------------------
    // Preview chart for the new-ETL popup
    // Fetches JGE points for the selected period and renders them with the
    // same axis convention (H or Q on X) as the main chart.

    var newPreviewDebounce = null;
    function newPreviewSchedule() {
        clearTimeout(newPreviewDebounce);
        newPreviewDebounce = setTimeout(newPreviewLoad, 250);
    }

    function newPreviewLoad()
    {
        var d1 = document.getElementById('new_date_debut_periode').value;
        var h1 = document.getElementById('new_heure_debut_periode').value;
        var d2 = document.getElementById('new_date_fin_periode').value;
        var h2 = document.getElementById('new_heure_fin_periode').value;

        var dateOk = /^\d{2}-\d{2}-\d{4}$/.test(d1) && /^\d{2}-\d{2}-\d{4}$/.test(d2);
        if (!dateOk) {
            document.getElementById('new_jge_count').textContent =
                '<?php echo TEXT_ET_NEW_DATE_HINT; ?>';
            Plotly.purge('new_preview_plot');
            return;
        }

        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'include/structure/etl/process_etl_new_preview.php', true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.onreadystatechange = function() {
            if (xhr.readyState !== 4) return;
            if (xhr.status !== 200) {
                console.error('[ETL preview] HTTP', xhr.status, xhr.responseText);
                return;
            }
            var r;
            try { r = JSON.parse(xhr.responseText); }
            catch (e) {
                console.error('[ETL preview] JSON parse failed:', e.message);
                console.error('[ETL preview] Response was:', xhr.responseText.slice(0, 500));
                var regBox = document.getElementById('new_regression_box');
                if (regBox) {
                    regBox.style.color = '#a32d2d';
                    regBox.textContent = 'Erreur serveur — voir la console (F12).';
                }
                return;
            }

            var hint = document.getElementById('new_jge_count');
            if (r.nb_jge_used < 2) {
                hint.style.color = '#a32d2d';
                hint.textContent = r.nb_jge_used + ' '
                                 + (r.nb_jge_used > 1
                                    ? '<?php echo TEXT_ET_NEW_JGE_FEW; ?>'
                                    : '<?php echo TEXT_ET_NEW_JGE_NONE; ?>');
            } else {
                hint.style.color = '#666';
                var extra = (r.nb_jge_excluded > 0)
                    ? ' (' + r.nb_jge_excluded + ' <?php echo TEXT_ET_NEW_JGE_EXCLUDED; ?>)'
                    : '';
                hint.textContent = r.nb_jge_used + ' <?php echo TEXT_ET_NEW_JGE_FOUND; ?>' + extra;
            }

            newPreviewRender(r);
        };

        var model = 1;
        var modelRadio = document.querySelector("input[name='new_eq_etl']:checked");
        if (modelRadio) { model = parseInt(modelRadio.value, 10); }
        var h0 = parseFloat(document.getElementById('origine_h0').value) || 0;
        var h0Auto = !!(document.getElementById('new_h0_auto')
                        && document.getElementById('new_h0_auto').checked);
        var bornesTab = [];
        for (var i = 1; i <= 4; i++) {
            var inf    = document.getElementById('inf_' + i).value;
            var sup    = document.getElementById('sup_' + i).value;
            var interv = document.getElementById('interv_' + i).value;
            bornesTab.push({ inf: inf, sup: sup, interv: interv });
        }

        xhr.send(JSON.stringify({
            idStation:     idStation,
            date1:         d1, heure1: h1,
            date2:         d2, heure2: h2,
            model:         model,
            h0:            h0,
            h0_auto:       h0Auto,
            bornesTab:     bornesTab,
            excludedDates: Object.keys(excludedJgeDates)
        }));
    }

    // -----------------------------------------------
    // Editable curve points
    //
    // The regression produces an initial curve. The user can then edit those
    // points (drag with mouse, or click to open a popup for keyboard input).
    // newCurvePoints is the source of truth for the curve from that point on;
    // the regression is just a starting guide.
    //
    // newCurveDirty tracks whether the user has manually edited any point —
    // used to warn before discarding edits when changing period / model.

    var newCurvePoints = null;   // null until a regression has been received
    var newCurveDirty  = false;
    var newCurveServerKey = '';  // identifies which "regression run" produced
                                 // the points currently in newCurvePoints
    var newCurveCoeffs = null;   // last regression coefficients, used to
                                 // resample curve points locally when only
                                 // the density bounds change (no need to
                                 // hit the server)

    // One-shot guard: the validity range (section 4) is auto-filled from the
    // optimised H₀ and the gauging Hmax on the FIRST regression after the
    // popup opens, then left alone so the user's manual edits are preserved.
    var newRangeAutoFilled = false;

    // Regression visibility: when false, the popup only displays the
    // gauging points (initial state — user hasn't clicked "Add regression"
    // yet). When true, the curve trace and PI band are drawn normally.
    var regressionEnabled = false;

    // 95% prediction interval bands, parallel to newCurvePoints
    // (same length, same order). Recomputed only by the server on a fresh
    // regression — manual point edits do not update the bands (they remain
    // a reference of the original regression).
    var newCurveBandLower = null;
    var newCurveBandUpper = null;

    // Set of JGE point dates (as displayed strings) that the user excluded
    // from the regression by clicking on them. Points stay drawn on the
    // chart but with an "excluded" style, and don't feed the regression.
    var excludedJgeDates = {};

    function newCurveKeyFromServer(r) {
        // A fingerprint of the regression inputs — period/model/h0/bornes
        // AND the set of excluded JGE points. Same key → same regression
        // context, we can keep user edits. Including the excluded set is
        // essential: when the user toggles a JGE in/out, the inputs above
        // don't change but the regression does — without this, the cached
        // points would mask the new fit.
        if (!r || !r.curve) return '';
        var excludedKey = Object.keys(excludedJgeDates).sort().join(',');
        return [
            document.getElementById('new_date_debut_periode').value,
            document.getElementById('new_heure_debut_periode').value,
            document.getElementById('new_date_fin_periode').value,
            document.getElementById('new_heure_fin_periode').value,
            (document.querySelector("input[name='new_eq_etl']:checked") || {}).value,
            document.getElementById('origine_h0').value,
            (document.getElementById('new_h0_auto') || {}).checked,
            document.getElementById('inf_1').value, document.getElementById('sup_1').value, document.getElementById('interv_1').value,
            document.getElementById('inf_2').value, document.getElementById('sup_2').value, document.getElementById('interv_2').value,
            document.getElementById('inf_3').value, document.getElementById('sup_3').value, document.getElementById('interv_3').value,
            document.getElementById('inf_4').value, document.getElementById('sup_4').value, document.getElementById('interv_4').value,
            excludedKey
        ].join('|');
    }


    function newPreviewRender(r)
    {
        // Update the equation/R² info box
        var regBox = document.getElementById('new_regression_box');
        if (regBox) {
            if (r.curve) {
                regBox.style.color = '#000';
                regBox.innerHTML =
                    '<div style="margin-bottom:4px;"><span style="color:#666;"><?php echo TEXT_ET_NEW_EQ_LABEL; ?></span> '
                  + '<b>' + r.curve.equation_html + '</b></div>'
                  + '<div><span style="color:#666;"><?php echo TEXT_ET_NEW_R2_LABEL; ?></span> '
                  + '<b>' + r.curve.r2 + '</b></div>'
                  + ((document.getElementById('new_h0_auto')
                      && document.getElementById('new_h0_auto').checked
                      && r.curve.coefficients && r.curve.coefficients.h0 !== undefined)
                      ? '<div style="margin-top:4px;color:#016A70;font-size:11px;">H₀ '
                        + '<?php echo TEXT_ET_NEW_H0_AUTO_RESULT; ?> '
                        + (Math.round(r.curve.coefficients.h0 * 100) / 100) + '</div>'
                      : '')
                  + (newCurveDirty
                      ? '<div data-edited-warning="1" style="margin-top:6px;color:#BA7517;font-size:11px;">'
                      + '⚠ <?php echo TEXT_ET_NEW_MANUAL_EDIT; ?></div>'
                      : '');
            } else if (r.regression_error) {
                regBox.style.color = '#a32d2d';
                regBox.textContent = '<?php echo TEXT_ET_NEW_REG_FAILED; ?> (' + r.regression_error + ').';
            } else {
                regBox.style.color = '#666';
                regBox.textContent = '<?php echo TEXT_ET_NEW_REG_NEED_PTS; ?>';
            }
        }

        // Update the curve points cache: replace only when the regression
        // context has changed (otherwise we'd discard user edits on
        // every preview refresh).
        if (r.curve && r.curve.points) {
            var newKey = newCurveKeyFromServer(r);
            if (newKey !== newCurveServerKey || newCurvePoints === null) {
                newCurvePoints = r.curve.points.slice();
                newCurveServerKey = newKey;
                newCurveDirty = false;
            }
            // Always cache coefficients (even if we kept user-edited points)
            // so density-bound changes can resample locally without going
            // back to the server.
            if (r.curve.coefficients) {
                newCurveCoeffs = r.curve.coefficients;
            }
            // Cache the PI bands — they accompany the freshly returned curve.
            // Length always matches r.curve.points.
            newCurveBandLower = r.curve.band_lower || null;
            newCurveBandUpper = r.curve.band_upper || null;
        } else if (r.curve === null) {
            newCurvePoints = null;
            newCurveCoeffs = null;
            newCurveBandLower = null;
            newCurveBandUpper = null;
        }

        newPreviewDraw(r);

        // Reflect the auto-optimised H₀ into the (disabled) input
        var h0AutoCb = document.getElementById('new_h0_auto');
        if (h0AutoCb && h0AutoCb.checked && r.curve
            && r.curve.coefficients && r.curve.coefficients.h0 !== undefined) {
            var h0In = document.getElementById('origine_h0');
            if (h0In) { h0In.value = Math.round(r.curve.coefficients.h0 * 100) / 100; }
        }

        // First regression after opening, in auto-H₀ mode: pre-fill the
        // validity range (section 4) from H₀ and the gauging Hmax. Done once
        // so later manual edits are preserved.
        if (!newRangeAutoFilled && h0AutoCb && h0AutoCb.checked
            && r.curve && r.curve.coefficients
            && r.curve.coefficients.h0 !== undefined
            && r.points && r.points.length) {
            autoFillValidityRange(r.curve.coefficients.h0, r.points);
            newRangeAutoFilled = true;
        }
    }


    // Round a value to the nearest ten (bornes lisibles).
    function roundTen(v) { return Math.round(v / 10) * 10; }

    // Pick a "nice" rounded step (1,2,5,10,20,50,100,...) so a span yields
    // AT LEAST the requested number of points. We round the raw step DOWN to
    // the nearest nice palier: a smaller step means more points, so the curve
    // is always at least as smooth as asked, whatever the data range.
    function niceStep(span, targetPts) {
        if (!(span > 0) || !(targetPts > 0)) { return 10; }
        var raw  = span / targetPts;
        var pow10 = Math.pow(10, Math.floor(Math.log(raw) / Math.LN10));
        var frac  = raw / pow10;
        var nice;
        if      (frac >= 5) { nice = 5; }
        else if (frac >= 2) { nice = 2; }
        else                { nice = 1; }
        var step = nice * pow10;
        return step < 1 ? 1 : Math.round(step);
    }

    // Auto-fill section 4 from the optimised H₀ and the gauging points:
    //   Line 1 : H₀(round 10) → 1.02·Hmax(round 10), ~60 points
    //            (extrapolation capped at +2% above the highest gauging)
    //   Line 2 : Min seeded with line 1's Max (a convenience starting point
    //            if the user wants to extend further), Max/Step left empty
    //   Lines 3,4 : cleared
    // Then resample the curve so the preview matches the new ranges.
    function autoFillValidityRange(h0, points) {
        var hs = points.filter(function(p) { return !p.excluded && isFinite(p.h); })
                       .map(function(p) { return p.h; });
        if (!hs.length) { return; }
        var hMax = Math.max.apply(null, hs);

        var l1min = roundTen(h0);
        var l1max = roundTen(hMax * 1.02);
        if (l1max <= l1min) { l1max = l1min + 10; }
        var l1step = niceStep(l1max - l1min, 60);

        function setRow(n, mn, mx, st) {
            var a = document.getElementById('inf_' + n);
            var b = document.getElementById('sup_' + n);
            var c = document.getElementById('interv_' + n);
            if (a) { a.value = (mn === '' ? '' : mn); }
            if (b) { b.value = (mx === '' ? '' : mx); }
            if (c) { c.value = (st === '' ? '' : st); }
        }

        setRow(1, l1min, l1max, l1step);
        // Line 2: only seed the Min (= line 1 Max) as a convenience; leave the
        // rest empty so no points are generated beyond +10% by default.
        setRow(2, l1max, '', '');
        setRow(3, '', '', '');
        setRow(4, '', '', '');

        // Re-run with the new ranges so the drawn curve reflects them.
        if (typeof newPreviewScheduleConfirmed === 'function') {
            newPreviewScheduleConfirmed();
        }
    }


    // Pure draw step — does not touch newCurvePoints, just renders them.
    function newPreviewDraw(r)
    {
        // Cache for local re-render (e.g. PI checkbox toggle, no server call)
        window.__lastPreviewResponse = r;

        var plotEl = document.getElementById('new_preview_plot');
        if (!plotEl) { return; }

        if (typeof Plotly === 'undefined') {
            console.error('Plotly is not loaded — cannot render preview.');
            plotEl.innerHTML = '<div style="padding:30px;text-align:center;color:#a32d2d;">'
                             + '<?php echo TEXT_ET_NEW_PLOTLY_MISSING; ?></div>';
            return;
        }

        if (!r.points || r.points.length === 0) {
            try { Plotly.purge('new_preview_plot'); } catch (e) {}
            plotEl.innerHTML = '<div style="padding:30px;text-align:center;color:#666;font-size:13px;">'
                             + '<?php echo TEXT_ET_NEW_JGE_NONE_PERIOD; ?></div>';
            return;
        }

        var swapAxes = document.getElementById('swap_axes')
                     ? document.getElementById('swap_axes').checked : false;

        var unitH = '<?php echo TEXT_ET_COORD_UNIT_H; ?>';
        var unitQ = '<?php echo TEXT_ET_COORD_UNIT_Q; ?>';
        var phH = swapAxes ? '%{y:.2f}' : '%{x:.2f}';
        var phQ = swapAxes ? '%{x:.2f}' : '%{y:.2f}';

        // Separate JGE points into included (used by regression) and
        // excluded (ignored by regression but still clickable to toggle back).
        var ptsIncluded = r.points.filter(function(p) { return !p.excluded; });
        var ptsExcluded = r.points.filter(function(p) { return  p.excluded; });

        // Included JGE trace — blue stars
        var traceJge = {
            x: ptsIncluded.map(function(p) { return swapAxes ? p.q : p.h; }),
            y: ptsIncluded.map(function(p) { return swapAxes ? p.h : p.q; }),
            customdata: ptsIncluded.map(function(p) { return [p.date, p.id_jge]; }),
            name: '<?php echo TEXT_ET_LABEL_JGE; ?>',
            meta: 'jge_included',
            mode: 'markers', type: 'scatter',
            marker: { size: 11, symbol: 'star', color: '#185FA5', line: { color: 'black', width: 1 } },
            hovertemplate: '<b><?php echo TEXT_ET_LABEL_JGE; ?></b>'
                         + '<br><span style="font-size:9px;color:#aaa">────────────</span>'
                         + '<br><b><?php echo TEXT_ET_TOOLTIP_DATE; ?></b> %{customdata[0]}'
                         + '<br><b><?php echo TEXT_ET_TOOLTIP_H; ?></b> ' + phH + ' ' + unitH
                         + '<br><b><?php echo TEXT_ET_TOOLTIP_Q; ?></b> ' + phQ + ' ' + unitQ
                         + '<br><span style="font-size:10px;color:#888">'
                         + '<?php echo TEXT_ET_NEW_JGE_CLICK_HINT; ?></span>'
                         + '<br><span style="font-size:10px;color:#888">'
                         + '<?php echo TEXT_ET_SG_OPEN_HINT; ?></span>'
                         + '<extra></extra>'
        };

        // Excluded JGE trace — empty red circles
        var traceJgeExcluded = {
            x: ptsExcluded.map(function(p) { return swapAxes ? p.q : p.h; }),
            y: ptsExcluded.map(function(p) { return swapAxes ? p.h : p.q; }),
            customdata: ptsExcluded.map(function(p) { return [p.date, p.id_jge]; }),
            name: 'JGE excluded',
            meta: 'jge_excluded',
            mode: 'markers', type: 'scatter',
            marker: { size: 14, symbol: 'circle-open', color: '#E24B4A',
                      line: { color: '#E24B4A', width: 2 } },
            hovertemplate: '<b><span style="color:#E24B4A"><?php echo TEXT_ET_NEW_JGE_EXCLUDED_LABEL; ?></span></b>'
                         + '<br><span style="font-size:9px;color:#aaa">────────────</span>'
                         + '<br><b><?php echo TEXT_ET_TOOLTIP_DATE; ?></b> %{customdata[0]}'
                         + '<br><b><?php echo TEXT_ET_TOOLTIP_H; ?></b> ' + phH + ' ' + unitH
                         + '<br><b><?php echo TEXT_ET_TOOLTIP_Q; ?></b> ' + phQ + ' ' + unitQ
                         + '<br><span style="font-size:10px;color:#888">'
                         + '<?php echo TEXT_ET_NEW_JGE_REINCLUDE_HINT; ?></span>'
                         + '<br><span style="font-size:10px;color:#888">'
                         + '<?php echo TEXT_ET_SG_OPEN_HINT; ?></span>'
                         + '<extra></extra>'
        };

        var traces = [];

        // -- 95% Prediction Interval band (drawn FIRST so it sits behind
        //    every other trace) --
        // Only drawn when:
        //   - the regression workflow has been activated by the user
        //   - the checkbox is on
        //   - we have band data from the server (band_lower/band_upper)
        //   - the user has not manually edited the points (the bands match
        //     the original regression, not the edited curve)
        var showPi = document.getElementById('new_show_pi');
        if (regressionEnabled && showPi && showPi.checked
            && newCurvePoints && newCurvePoints.length > 1
            && newCurveBandLower && newCurveBandUpper
            && newCurveBandLower.length === newCurvePoints.length
            && !newCurveDirty)
        {
            // Filter to finite values only (same protective filter as the curve)
            var bandSafe = [];
            for (var bi = 0; bi < newCurvePoints.length; bi++) {
                var pp = newCurvePoints[bi];
                var lo = newCurveBandLower[bi];
                var up = newCurveBandUpper[bi];
                if (isFinite(pp.h) && isFinite(pp.q)
                    && isFinite(lo) && isFinite(up)) {
                    bandSafe.push({ h: pp.h, lower: lo, upper: up });
                }
            }
            if (bandSafe.length > 1) {
                // Lower bound trace — invisible, no fill, just an anchor
                var traceBandLower = {
                    x: bandSafe.map(function(p) { return swapAxes ? p.lower : p.h; }),
                    y: bandSafe.map(function(p) { return swapAxes ? p.h     : p.lower; }),
                    name: '<?php echo TEXT_ET_NEW_PI_BAND; ?>',
                    meta: 'pi_band',
                    mode: 'lines', type: 'scatter',
                    line: { width: 0, color: 'rgba(120,120,120,0)' },
                    hoverinfo: 'skip',
                    showlegend: false
                };
                // Upper bound trace — fills downward to the previous trace
                // (= the lower bound) → produces the grey band. The line is
                // given a thin grey colour (not transparent) so the legend
                // entry shows the band colour rather than a blank swatch;
                // the 1px stroke also makes the band edge slightly crisper.
                var traceBandUpper = {
                    x: bandSafe.map(function(p) { return swapAxes ? p.upper : p.h; }),
                    y: bandSafe.map(function(p) { return swapAxes ? p.h     : p.upper; }),
                    name: '<?php echo TEXT_ET_NEW_PI_BAND; ?>',
                    meta: 'pi_band',
                    mode: 'lines', type: 'scatter',
                    line: { width: 1, color: 'rgba(120,120,120,0.45)' },
                    fill: 'tonexty',
                    fillcolor: 'rgba(120,120,120,0.18)',
                    hoverinfo: 'skip',
                    showlegend: true
                };
                traces.push(traceBandLower, traceBandUpper);
            }
        }

        // Editable curve trace (only when the user has activated the
        // regression workflow — initial state shows gauging points only).
        // Pushed BEFORE the JGE traces so the gauging stars stay on top —
        // the curve must never visually cover them, otherwise it gets
        // hard to see where each gauging actually lies relative to the fit.
        if (regressionEnabled && newCurvePoints && newCurvePoints.length > 1) {
            var safePoints = newCurvePoints.filter(function(p) {
                return isFinite(p.h) && isFinite(p.q);
            });
            if (safePoints.length > 1) {
                var traceCurve = {
                    x: safePoints.map(function(p) { return swapAxes ? p.q : p.h; }),
                    y: safePoints.map(function(p) { return swapAxes ? p.h : p.q; }),
                    name: 'Curve',
                    meta: 'curve',
                    mode: 'markers+lines', type: 'scatter',
                    line:   { color: '#D85A30', width: 2 },
                    marker: { size: 9, symbol: 'square', color: '#D85A30',
                              line: { color: '#702c10', width: 1 } },
                    hovertemplate: '<b><span style="color:#D85A30"><?php echo TEXT_ET_NEW_CURVE_LABEL; ?></span></b>'
                                 + '<br><span style="font-size:9px;color:#aaa">────────────</span>'
                                 + '<br><b><?php echo TEXT_ET_TOOLTIP_H; ?></b> ' + phH + ' ' + unitH
                                 + '<br><b><?php echo TEXT_ET_TOOLTIP_Q; ?></b> ' + phQ + ' ' + unitQ
                                 + '<br><span style="font-size:10px;color:#888">'
                                 + '<?php echo TEXT_ET_NEW_CURVE_DRAG_HINT; ?></span>'
                                 + '<extra></extra>'
                };
                traces.push(traceCurve);
            }
        }

        // JGE pushed LAST so the stars sit visually above the curve.
        traces.push(traceJge, traceJgeExcluded);

        var titleH = '<?php echo TEXT_ET_AXIS_H; ?>';
        var titleQ = '<?php echo TEXT_ET_AXIS_Q; ?>';

        // Log-log display toggle. When on, both axes use a logarithmic scale,
        // so a power law appears as a straight line. Values <= 0 (e.g. the
        // Q = 0 clip point) can't be drawn on a log axis and are silently
        // skipped by Plotly — acceptable, the gaugings and curve are > 0.
        var logLog = document.getElementById('new_loglog')
                   ? document.getElementById('new_loglog').checked : false;
        var axisType = logLog ? 'log' : 'linear';

        // Frame the view on the GAUGING points (± 10%), not on the generated
        // curve — the curve extends to ~1.10·Hmax (section 4), but the
        // user wants to keep the gaugings centered. We compute H and Q bounds
        // from every gauging point, pad by 10%, and set explicit axis ranges.
        // For a log axis the range must be given as log10 of the bounds.
        var allH = r.points.filter(function(p){ return isFinite(p.h); }).map(function(p){ return p.h; });
        var allQ = r.points.filter(function(p){ return isFinite(p.q); }).map(function(p){ return p.q; });

        function paddedRange(vals, isLog) {
            if (!vals.length) { return null; }
            var lo = Math.min.apply(null, vals);
            var hi = Math.max.apply(null, vals);
            var span = hi - lo;
            var pad  = span > 0 ? span * 0.10 : (Math.abs(hi) * 0.10 || 1);
            var rlo  = lo - pad;
            var rhi  = hi + pad;
            if (isLog) {
                // log axis can't show <= 0; clamp the low bound to a small
                // positive fraction of the high bound.
                if (rlo <= 0) { rlo = (hi > 0 ? hi : 1) * 0.001; }
                return [Math.log(rlo) / Math.LN10, Math.log(rhi) / Math.LN10];
            }
            return [rlo, rhi];
        }

        var rangeH = paddedRange(allH, axisType === 'log');
        var rangeQ = paddedRange(allQ, axisType === 'log');
        // X carries H (or Q if axes swapped); Y carries the other one.
        var rangeX = swapAxes ? rangeQ : rangeH;
        var rangeY = swapAxes ? rangeH : rangeQ;

        var xAxisCfg = { title: { text: swapAxes ? titleQ : titleH, standoff: 15 }, type: axisType };
        var yAxisCfg = { title: { text: swapAxes ? titleH : titleQ, standoff: 15 }, type: axisType };
        if (rangeX) { xAxisCfg.range = rangeX; xAxisCfg.autorange = false; } else { xAxisCfg.autorange = true; }
        if (rangeY) { yAxisCfg.range = rangeY; yAxisCfg.autorange = false; } else { yAxisCfg.autorange = true; }

        var layout = {
            xaxis: xAxisCfg,
            yaxis: yAxisCfg,
            hovermode: 'closest',
            hoverlabel: { bgcolor: '#fff', bordercolor: '#888', font: { size: 12, color: '#000' }, align: 'left' },
            margin: { l: 60, r: 15, t: 15, b: 55 },
            // Interactive legend — click an item to toggle the corresponding
            // trace, double-click to isolate it. Placed inside the plot area
            // (top-left, semi-transparent background) so it doesn't eat into
            // the chart's usable space.
            showlegend: true,
            legend: {
                x: 0.01, y: 0.99,
                xanchor: 'left', yanchor: 'top',
                bgcolor: 'rgba(255,255,255,0.85)',
                bordercolor: '#c0c0c0',
                borderwidth: 1,
                font: { size: 11 }
            },
            dragmode: 'pan'
        };

        var config = {
            responsive: true, displaylogo: false,
            scrollZoom: true,
            modeBarButtonsToRemove: ['select2d', 'lasso2d', 'autoScale2d', 'zoomIn2d', 'zoomOut2d']
        };

        try {
            Plotly.newPlot('new_preview_plot', traces, layout, config).then(function() {
                wireCurveEditing();
            });
        } catch (err) {
            console.error('Plotly newPlot error:', err);
            plotEl.innerHTML = '<div style="padding:30px;text-align:center;color:#a32d2d;font-size:13px;">'
                             + 'Erreur lors du rendu du graphe : ' + err.message + '</div>';
        }
    }


    // -----------------------------------------------
    // Curve point editing — click to open popup, drag to move

    var curveDragState = null;  // {pointIndex, plotEl, swapAxes} during drag

    // Find the index of a trace by its `meta` tag (set when building traces).
    // Returns -1 if no trace matches. Needed because the trace order depends
    // on whether the PI band is being drawn (2 extra traces inserted before
    // the JGE/curve traces).
    function findTraceIndex(plotEl, metaTag) {
        if (!plotEl || !plotEl.data) return -1;
        for (var i = 0; i < plotEl.data.length; i++) {
            if (plotEl.data[i] && plotEl.data[i].meta === metaTag) return i;
        }
        return -1;
    }

    function wireCurveEditing()
    {
        var plotEl = document.getElementById('new_preview_plot');
        if (!plotEl || !plotEl.on) { return; }

        // CLICK on:
        //   - JGE (included or excluded) → toggle exclusion
        //     (Ctrl+Click is intercepted by the capture-phase mousedown
        //      handler above to open the SG page in a new tab — by the
        //      time we get here, plain clicks only)
        //   - fitted curve → open the edit popup
        // Each trace carries a `meta` tag (set when traces are built),
        // so the handler doesn't depend on the trace index — which
        // changes depending on whether the PI band is being drawn.
        plotEl.on('plotly_click', function(eventData) {
            if (!eventData || !eventData.points || !eventData.points.length) return;
            var pt = eventData.points[0];
            var kind = (pt.data && pt.data.meta) ? pt.data.meta : '';

            if (kind === 'jge_included' || kind === 'jge_excluded') {
                // customdata is [date, id_jge]
                var cd = pt.customdata;
                var date = Array.isArray(cd) ? cd[0] : cd;
                if (!date) return;
                toggleJgeExclusion(date);
                return;
            }
        });
    }

    // Toggle a JGE point's exclusion status, then reload the regression.
    // Uses the same confirmation guard as the other regression-changing
    // actions: if the user has manually edited the curve, ask before
    // discarding those edits.
    function toggleJgeExclusion(date)
    {
        var wasExcluded = !!excludedJgeDates[date];

        function applyToggle() {
            if (wasExcluded) { delete excludedJgeDates[date]; }
            else             { excludedJgeDates[date] = true; }
            newPreviewSchedule();
        }

        if (newCurveDirty) {
            customConfirm('<?php echo TEXT_ET_NEW_CONFIRM_DISCARD; ?>',
                function onYes() {
                    newCurveDirty  = false;
                    newCurvePoints = null;
                    applyToggle();
                },
                function onNo() { /* keep current state */ }
            );
        } else {
            applyToggle();
        }
    }

    // Start the drag on mousedown over a curve marker
    // Shift+Click on a JGE point opens the SG page in a new tab.
    // Capture-phase mousedown BEFORE Plotly gets the event. We read JGE
    // positions directly from Plotly's internal trace data (no need for a
    // separate global array).
    document.addEventListener('mousedown', function(e) {
        if (!e.shiftKey) return;
        var plotEl = document.getElementById('new_preview_plot');
        if (!plotEl || !plotEl.contains(e.target)) return;

        var idJge = newHitTestJge(e, plotEl);
        if (idJge === null) return;

        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();
        window.open('modif_jge.php?ref=' + encodeURIComponent(idJge), '_blank');
    }, true);

    // Hit-test on all JGE traces (both included and excluded) — returns
    // the id_jge of the closest point within 14 px, or null. Reads data
    // directly from the live Plotly traces so it works regardless of
    // which traces (PI band, curve, etc.) are currently drawn.
    function newHitTestJge(e, plotEl) {
        if (!plotEl._fullLayout || !plotEl.data) return null;
        var rect = plotEl.getBoundingClientRect();
        var cx = e.clientX - rect.left;
        var cy = e.clientY - rect.top;
        var xa = plotEl._fullLayout.xaxis;
        var ya = plotEl._fullLayout.yaxis;
        if (!xa || !ya) return null;

        var swapAxes = document.getElementById('swap_axes')
                     ? document.getElementById('swap_axes').checked : false;

        var bestId = null, bestD2 = 14 * 14 + 1;
        for (var t = 0; t < plotEl.data.length; t++) {
            var tr = plotEl.data[t];
            if (!tr || (tr.meta !== 'jge_included' && tr.meta !== 'jge_excluded')) continue;
            if (!tr.x || !tr.customdata) continue;
            for (var i = 0; i < tr.x.length; i++) {
                // customdata is [date, id_jge]; recover x/y in data space
                var dataX = tr.x[i], dataY = tr.y[i];
                var px = xa.l2p(dataX) + xa._offset;
                var py = ya.l2p(dataY) + ya._offset;
                var dx = px - cx, dy = py - cy;
                var d2 = dx * dx + dy * dy;
                if (d2 < bestD2) {
                    bestD2 = d2;
                    var cd = tr.customdata[i];
                    bestId = Array.isArray(cd) ? cd[1] : null;
                }
            }
        }
        return bestId;
    }

    // Twin of newHitTestJge that returns the date string (key into
    // excludedJgeDates) of the closest JGE point within 14 px, or null.
    // Same hit-test logic; we just keep customdata[0] (date) instead of [1].
    function newHitTestJgeDate(e, plotEl) {
        if (!plotEl._fullLayout || !plotEl.data) return null;
        var rect = plotEl.getBoundingClientRect();
        var cx = e.clientX - rect.left;
        var cy = e.clientY - rect.top;
        var xa = plotEl._fullLayout.xaxis;
        var ya = plotEl._fullLayout.yaxis;
        if (!xa || !ya) return null;

        var bestDate = null, bestD2 = 14 * 14 + 1;
        for (var t = 0; t < plotEl.data.length; t++) {
            var tr = plotEl.data[t];
            if (!tr || (tr.meta !== 'jge_included' && tr.meta !== 'jge_excluded')) continue;
            if (!tr.x || !tr.customdata) continue;
            for (var i = 0; i < tr.x.length; i++) {
                var dataX = tr.x[i], dataY = tr.y[i];
                var px = xa.l2p(dataX) + xa._offset;
                var py = ya.l2p(dataY) + ya._offset;
                var dx = px - cx, dy = py - cy;
                var d2 = dx * dx + dy * dy;
                if (d2 < bestD2) {
                    bestD2 = d2;
                    var cd = tr.customdata[i];
                    bestDate = Array.isArray(cd) ? cd[0] : null;
                }
            }
        }
        return bestDate;
    }

    document.addEventListener('mousedown', function(e) {
        // Left button only — right-click is reserved for add/remove via contextmenu.
        if (e.button !== 0) return;
        var plotEl = document.getElementById('new_preview_plot');
        if (!plotEl || !plotEl.contains(e.target)) return;
        if (!newCurvePoints || newCurvePoints.length < 2) return;

        // Hit-test: find the closest curve point within 12 px
        var hit = hitTestCurvePoint(e, plotEl);
        if (hit === -1) return;

        // Stop the event from reaching Plotly's drag handlers (otherwise
        // the chart pans / zooms at the same time we move the point).
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();

        var swapAxes = document.getElementById('swap_axes')
                     ? document.getElementById('swap_axes').checked : false;

        // Temporarily disable Plotly's drag (pan/zoom) so only our point moves.
        // We restore the previous dragmode on mouseup.
        var prevDragmode = (plotEl._fullLayout && plotEl._fullLayout.dragmode) || 'pan';
        try { Plotly.relayout(plotEl, { dragmode: false }); } catch (err) {}

        curveDragState = {
            pointIndex: hit,
            plotEl: plotEl,
            swapAxes: swapAxes,
            moved: false,
            prevDragmode: prevDragmode
        };
        plotEl.style.cursor = 'grabbing';
    }, true); // capture phase, so we run before Plotly's listeners

    document.addEventListener('mousemove', function(e) {
        if (!curveDragState) return;
        e.stopPropagation();

        var plotEl = curveDragState.plotEl;
        var coord  = pixelToData(e, plotEl);
        if (!coord) return;
        // Translate plot coords (x,y) → metric coords (h,q)
        var p = newCurvePoints[curveDragState.pointIndex];
        if (curveDragState.swapAxes) { p.q = coord.x; p.h = coord.y; }
        else                          { p.h = coord.x; p.q = coord.y; }
        curveDragState.moved = true;
        newCurveDirty = true;

        // Update only the curve trace in place — much faster than newPlot
        var xs = newCurvePoints.map(function(pp) { return curveDragState.swapAxes ? pp.q : pp.h; });
        var ys = newCurvePoints.map(function(pp) { return curveDragState.swapAxes ? pp.h : pp.q; });
        var curveIdx = findTraceIndex(plotEl, 'curve');
        if (curveIdx >= 0) {
            Plotly.restyle(plotEl, { x: [xs], y: [ys] }, [curveIdx]);
        }
    }, true);

    document.addEventListener('mouseup', function(e) {
        if (!curveDragState) return;
        e.stopPropagation();
        var plotEl = curveDragState.plotEl;
        plotEl.style.cursor = '';
        var wasMoved     = curveDragState.moved;
        var prevDragmode = curveDragState.prevDragmode;
        curveDragState = null;

        // Restore the chart's previous drag behavior (pan/zoom)
        try { Plotly.relayout(plotEl, { dragmode: prevDragmode }); } catch (err) {}

        if (wasMoved) {
            // Refresh the regression box to show the "manually edited" warning
            var regBox = document.getElementById('new_regression_box');
            if (regBox && !regBox.querySelector('[data-edited-warning]')) {
                regBox.innerHTML += '<div data-edited-warning="1" style="margin-top:6px;color:#BA7517;font-size:11px;">'
                                  + '⚠ <?php echo TEXT_ET_NEW_MANUAL_EDIT; ?></div>';
            }
        }
    });


    // -----------------------------------------------
    // Right-click on the chart — add or remove a curve point.
    //
    //   - On a JGE point   → block the browser menu and do nothing
    //                        (inclusion/exclusion stays on left-click).
    //   - On a curve point → remove it (keeping at least 2 points).
    //   - Elsewhere        → add a new point at the cursor position,
    //                        inserted at the right index so the points
    //                        array stays sorted by H.
    //
    // Only fires inside the preview plot — anywhere else, the native
    // browser menu still works.

    document.addEventListener('contextmenu', function(e) {
        var plotEl = document.getElementById('new_preview_plot');
        if (!plotEl || !plotEl.contains(e.target)) return;

        e.preventDefault();
        e.stopPropagation();

        // Need a curve to add/remove points on
        if (!newCurvePoints) return;

        // 1) On a JGE point → toggle its exclusion. Same effect as the
        //    plain left-click on a JGE: delegates to toggleJgeExclusion
        //    which handles the dirty-curve confirmation prompt if needed.
        if (newHitTestJge(e, plotEl) !== null) {
            var date = newHitTestJgeDate(e, plotEl);
            if (date) {
                toggleJgeExclusion(date);
            }
            return;
        }

        // 2) On a curve point → remove it
        if (newCurvePoints.length >= 2) {
            var hit = hitTestCurvePoint(e, plotEl);
            if (hit !== -1) {
                if (newCurvePoints.length <= 2) {
                    alert('<?php echo TEXT_ET_EDIT_MIN_PTS; ?>');
                    return;
                }
                newCurvePoints.splice(hit, 1);
                // Mirror the band arrays so they stay aligned with newCurvePoints
                if (Array.isArray(newCurveBandLower) && newCurveBandLower.length > hit) {
                    newCurveBandLower.splice(hit, 1);
                }
                if (Array.isArray(newCurveBandUpper) && newCurveBandUpper.length > hit) {
                    newCurveBandUpper.splice(hit, 1);
                }
                newCurveDirty = true;
                newRefreshCurveTrace(plotEl);
                newAddManualEditWarning();
                return;
            }
        }

        // 3) Empty area → add a new point at the cursor position.
        //    pixelToData returns plot-space (x, y); when swap is active
        //    the X axis is Q and Y is H, so we translate accordingly.
        var coord = pixelToData(e, plotEl);
        if (!coord || !isFinite(coord.x) || !isFinite(coord.y)) return;

        var swapAxes = document.getElementById('swap_axes')
                     ? document.getElementById('swap_axes').checked : false;

        var newPt = swapAxes
            ? { h: coord.y, q: coord.x }
            : { h: coord.x, q: coord.y };

        // Insert at the right index (keep array sorted by H)
        var insertAt = newCurvePoints.length;
        for (var i = 0; i < newCurvePoints.length; i++) {
            if (newPt.h < newCurvePoints[i].h) { insertAt = i; break; }
        }
        newCurvePoints.splice(insertAt, 0, newPt);
        // Keep PI band arrays length-aligned: there's no PI value for the
        // new point, so insert NaN placeholders. The PI shape can be
        // re-drawn by the user toggling the option or relaunching the
        // regression — we don't try to extrapolate it.
        if (Array.isArray(newCurveBandLower)) {
            newCurveBandLower.splice(insertAt, 0, NaN);
        }
        if (Array.isArray(newCurveBandUpper)) {
            newCurveBandUpper.splice(insertAt, 0, NaN);
        }
        newCurveDirty = true;
        newRefreshCurveTrace(plotEl);
        newAddManualEditWarning();
    }, true);


    // Push newCurvePoints into the live curve trace (handles swap).
    function newRefreshCurveTrace(plotEl) {
        var swapAxes = document.getElementById('swap_axes')
                     ? document.getElementById('swap_axes').checked : false;
        var xs = newCurvePoints.map(function(p) { return swapAxes ? p.q : p.h; });
        var ys = newCurvePoints.map(function(p) { return swapAxes ? p.h : p.q; });
        var curveIdx = findTraceIndex(plotEl, 'curve');
        if (curveIdx >= 0) {
            Plotly.restyle(plotEl, { x: [xs], y: [ys] }, [curveIdx]);
        }
    }

    // Add (once) the "manually edited" warning to the regression box.
    function newAddManualEditWarning() {
        var regBox = document.getElementById('new_regression_box');
        if (regBox && !regBox.querySelector('[data-edited-warning]')) {
            regBox.innerHTML += '<div data-edited-warning="1" style="margin-top:6px;color:#BA7517;font-size:11px;">'
                              + '⚠ <?php echo TEXT_ET_NEW_MANUAL_EDIT; ?></div>';
        }
    }


    // Returns the index of the closest curve point under the cursor (within
    // 12 px), or -1 if none.
    function hitTestCurvePoint(e, plotEl)
    {
        if (!newCurvePoints || !plotEl._fullLayout) return -1;
        var rect = plotEl.getBoundingClientRect();
        var cx = e.clientX - rect.left;
        var cy = e.clientY - rect.top;

        var xa = plotEl._fullLayout.xaxis;
        var ya = plotEl._fullLayout.yaxis;
        if (!xa || !ya) return -1;

        var swapAxes = document.getElementById('swap_axes')
                     ? document.getElementById('swap_axes').checked : false;

        var bestIdx = -1, bestD2 = 12 * 12 + 1;
        for (var i = 0; i < newCurvePoints.length; i++) {
            var p = newCurvePoints[i];
            var dataX = swapAxes ? p.q : p.h;
            var dataY = swapAxes ? p.h : p.q;
            var px = xa.l2p(dataX) + xa._offset;
            var py = ya.l2p(dataY) + ya._offset;
            var dx = px - cx, dy = py - cy;
            var d2 = dx * dx + dy * dy;
            if (d2 < bestD2) { bestD2 = d2; bestIdx = i; }
        }
        return bestIdx;
    }

    // Convert pixel coords on the plot to data coords (x, y in Plotly axes)
    function pixelToData(e, plotEl)
    {
        if (!plotEl._fullLayout) return null;
        var rect = plotEl.getBoundingClientRect();
        var xa = plotEl._fullLayout.xaxis;
        var ya = plotEl._fullLayout.yaxis;
        if (!xa || !ya) return null;
        return {
            x: xa.p2l(e.clientX - rect.left - xa._offset),
            y: ya.p2l(e.clientY - rect.top  - ya._offset)
        };
    }


    // -----------------------------------------------
    // Custom confirm popup — replaces the native JS confirm() with a styled
    // dialog that matches the rest of the platform. The popup uses
    // callbacks (onYes / onNo) instead of returning a boolean, because we
    // need it to be modal-but-non-blocking.

    function customConfirm(message, onYes, onNo)
    {
        // If a confirm popup is already up, ignore this new request so the
        // first one stays valid and its onYes/onNo callbacks fire as
        // intended. Avoids duplicate popups from same-tick events.
        if (document.getElementById('etl_custom_confirm')) { return; }

        var overlay = document.createElement('div');
        overlay.id = 'etl_custom_confirm';
        overlay.style.cssText =
            'position:fixed;top:0;left:0;width:100vw;height:100vh;'
          + 'background:rgba(0,0,0,0.35);z-index:3000;'
          + 'display:flex;align-items:center;justify-content:center;';
        overlay.innerHTML =
            '<div style="background:#FBF9F1;border-radius:4px;width:420px;max-width:90vw;'
          + 'box-shadow:0 8px 24px rgba(0,0,0,0.3);overflow:hidden;font-family:inherit;">'
          + '<div style="background:#000;color:#fff;padding:10px 14px;font-size:14px;font-weight:bold;">'
          +   '<?php echo TEXT_ET_NEW_CONFIRM_TITLE; ?>'
          + '</div>'
          + '<div style="padding:18px 16px;font-size:13px;line-height:1.5;color:#333;">'
          +   message
          + '</div>'
          + '<div style="padding:8px 14px 14px;display:flex;justify-content:flex-end;gap:8px;">'
          +   '<button id="etl_confirm_no"  class="button_close" style="width:120px;">'
          +     '<?php echo TEXT_BTN_CANCEL; ?>'
          +   '</button>'
          +   '<button id="etl_confirm_yes" class="button"       style="width:140px;">'
          +     '<?php echo TEXT_ET_NEW_CONFIRM_OK; ?>'
          +   '</button>'
          + '</div>'
          + '</div>';
        document.body.appendChild(overlay);

        function cleanup() { overlay.remove(); document.removeEventListener('keydown', onKey); }
        function onKey(e) {
            if (e.key === 'Escape') { cleanup(); if (onNo)  { onNo();  } }
            if (e.key === 'Enter')  { cleanup(); if (onYes) { onYes(); } }
        }
        document.addEventListener('keydown', onKey);

        overlay.querySelector('#etl_confirm_yes').addEventListener('click', function() {
            cleanup(); if (onYes) { onYes(); }
        });
        overlay.querySelector('#etl_confirm_no').addEventListener('click', function() {
            cleanup(); if (onNo) { onNo(); }
        });

        // Hover effect on both buttons (darken slightly via filter so it
        // works regardless of the base background color from .button CSS).
        ['#etl_confirm_yes', '#etl_confirm_no'].forEach(function(sel) {
            var btn = overlay.querySelector(sel);
            btn.addEventListener('mouseenter', function() { btn.style.filter = 'brightness(0.9)'; });
            btn.addEventListener('mouseleave', function() { btn.style.filter = ''; });
        });
        overlay.addEventListener('click', function(e) {
            if (e.target === overlay) { cleanup(); if (onNo) { onNo(); } }
        });
    }


    // -----------------------------------------------
    // Local curve resampling — no server round-trip.
    //
    // Triggered when the user changes one of the density bounds inputs
    // (section 4: Validity range). The regression coefficients haven't
    // changed; we just need to regenerate the curve points for the
    // modified range, without touching points belonging to the other
    // ranges (which may have been manually edited).
    //
    // Strategy: each point carries a `rangeIndex` (1..4) telling which
    // bounds row produced it. We drop only the points tagged with the
    // modified row's index, then regenerate that row from scratch.

    function resampleCurveLocally(modifiedRangeIndex)
    {
        // No coefficients cached yet → can't resample, fall back to a
        // full server call (which will compute the regression too).
        if (!newCurveCoeffs || !newCurvePoints) { newPreviewSchedule(); return; }

        // Build the compute function from the cached coefficients.
        var compute;
        if (newCurveCoeffs.model === 2) {
            var a = newCurveCoeffs.a, b = newCurveCoeffs.b, c = newCurveCoeffs.c;
            compute = function(h) { return a*h*h + b*h + c; };
        } else if (newCurveCoeffs.model === 3) {
            var la = newCurveCoeffs.a, lb = newCurveCoeffs.b;
            compute = function(h) { return la*h + lb; };
        } else {
            var coef = newCurveCoeffs.coef, ap = newCurveCoeffs.ap, h0c = newCurveCoeffs.h0;
            compute = function(h) {
                var dh = h - h0c;
                return dh > 0 ? coef * Math.pow(dh, ap) : 0;
            };
        }

        // Read the modified bounds row from the UI.
        var infEl    = document.getElementById('inf_'    + modifiedRangeIndex);
        var supEl    = document.getElementById('sup_'    + modifiedRangeIndex);
        var intervEl = document.getElementById('interv_' + modifiedRangeIndex);
        if (!infEl || !supEl || !intervEl) return;
        var infStr    = (infEl.value    || '').trim();
        var supStr    = (supEl.value    || '').trim();
        var intervStr = (intervEl.value || '').trim();

        // Drop the existing points that belonged to this range.
        var kept = newCurvePoints.filter(function(p) {
            return p.rangeIndex !== modifiedRangeIndex;
        });

        // If any of the three inputs is blank/invalid, the user is mid-edit:
        // just drop the row's points (no regeneration). They'll come back
        // once all 3 fields are valid again.
        var newPts = [];
        if (infStr && supStr && intervStr
            && !isNaN(parseFloat(infStr))
            && !isNaN(parseFloat(supStr))
            && !isNaN(parseFloat(intervStr)))
        {
            var inf    = parseFloat(infStr);
            var sup    = parseFloat(supStr);
            var interv = parseFloat(intervStr);
            if (interv > 0 && sup >= inf && ((sup - inf) / interv) <= 5000) {
                // Zero-flow clip H provided by the server (null for power law,
                // or when there's no finite crossing). Used to land the curve
                // exactly on Q = 0 instead of stopping short of the axis.
                var hQZero = (newCurveCoeffs && newCurveCoeffs.h_qzero != null)
                           ? newCurveCoeffs.h_qzero : null;
                var clipEmitted = false;
                for (var h = inf; h <= sup; h += interv) {
                    var q = compute(h);
                    if (q > 0) {
                        newPts.push({ h: h, q: q, rangeIndex: modifiedRangeIndex });
                    } else if (hQZero != null && hQZero >= inf && hQZero <= sup && !clipEmitted) {
                        // Curve crosses zero inside this range: emit the exact
                        // (h_qzero, 0) point once so the line reaches the axis.
                        newPts.push({ h: hQZero, q: 0, rangeIndex: modifiedRangeIndex });
                        clipEmitted = true;
                    }
                    // else: Q <= 0 and no crossing here → skip.
                }
            }
        }

        // Merge kept + new, sorted by H (so the line connecting them is
        // drawn left-to-right without zig-zagging).
        var merged = kept.concat(newPts).sort(function(p1, p2) { return p1.h - p2.h; });
        if (merged.length < 2) return;

        newCurvePoints = merged;

        // Refresh the chart with Plotly.restyle on the curve trace.
        var plotEl = document.getElementById('new_preview_plot');
        if (!plotEl || typeof Plotly === 'undefined') return;
        var swapAxes = document.getElementById('swap_axes')
                     ? document.getElementById('swap_axes').checked : false;
        var xs = newCurvePoints.map(function(p) { return swapAxes ? p.q : p.h; });
        var ys = newCurvePoints.map(function(p) { return swapAxes ? p.h : p.q; });
        var curveIdx = findTraceIndex(plotEl, 'curve');
        if (curveIdx >= 0) {
            try { Plotly.restyle(plotEl, { x: [xs], y: [ys] }, [curveIdx]); } catch (e) {}
        }
    }

    // Debounced wrapper: from the input event, deduce which row was
    // modified (from the element's id: inf_3 / sup_3 / interv_3 → row 3)
    // and schedule a selective resample.
    var resampleTimer = null;
    function scheduleResample(e) {
        var id = (e && e.target && e.target.id) || '';
        var match = id.match(/^(?:inf|sup|interv)_(\d+)$/);
        if (!match) return;
        var rangeIndex = parseInt(match[1], 10);

        if (resampleTimer) clearTimeout(resampleTimer);
        resampleTimer = setTimeout(function() {
            resampleCurveLocally(rangeIndex);
        }, 200);
    }


    // -----------------------------------------------
    // Confirmation guard before refreshing the regression when the user
    // has already manually edited the curve or H0.

    function newPreviewScheduleConfirmed(e) {
        if (newCurveDirty) {
            var revertRadio = null;
            if (e && e.target && e.target.name === 'new_eq_etl') {
                var prevModel = String(curveDragState_savedModel || '1');
                revertRadio = document.querySelector("input[name='new_eq_etl'][value='" + prevModel + "']");
            }

            customConfirm('<?php echo TEXT_ET_NEW_CONFIRM_DISCARD; ?>',
                function onYes() {
                    newCurveDirty  = false;
                    newCurvePoints = null;
                    var picked = document.querySelector("input[name='new_eq_etl']:checked");
                    curveDragState_savedModel = picked ? picked.value : '1';
                    newPreviewSchedule();
                },
                function onNo() {
                    if (revertRadio) { revertRadio.checked = true; }
                }
            );
            return;
        }
        var picked = document.querySelector("input[name='new_eq_etl']:checked");
        curveDragState_savedModel = picked ? picked.value : '1';
        newPreviewSchedule();
    }

    var curveDragState_savedModel = '1';

    // -----------------------------------------------
    // Period inputs — wired separately from the model/H₀ handler.
    //
    // Changing the period means the set of gauging points (étoiles) on
    // the preview chart MUST be refreshed: points whose date no longer
    // falls within the new period have to disappear, and any point that
    // enters the period must show up. They must NOT be kept in the
    // regression either.
    //
    // Behaviour split by whether a regression has been computed yet:
    //
    //  • No regression yet (initial state, before clicking "Add
    //    regression") → silently re-fetch the points; this is purely a
    //    visual update.
    //
    //  • A regression is already in place → inform the user with the
    //    same alert style used elsewhere in this module, then re-run
    //    the full preview (server-side regression on the new set of
    //    points). Manual edits and exclusions are dropped because they
    //    refer to a now-stale gauging set.
    //
    // The debounce on newPreviewSchedule() (250 ms) prevents firing
    // mid-typing in date fields.

    function newPeriodChangedHandler()
    {
        // Wait until both dates look complete before doing anything —
        // otherwise typing the second character of the day already
        // triggers a server call with a malformed date.
        var d1 = document.getElementById('new_date_debut_periode').value;
        var d2 = document.getElementById('new_date_fin_periode').value;
        var dateOk = /^\d{2}-\d{2}-\d{4}$/.test(d1)
                  && /^\d{2}-\d{2}-\d{4}$/.test(d2);
        if (!dateOk) { return; }

        if (regressionEnabled)
        {
            // Ask the user whether to recompute the regression. Either
            // way the gauging set is refreshed (points outside the new
            // period must visually disappear). The difference is:
            //  • Yes → drop manual edits/exclusions, run a fresh
            //          regression on the new gauging set.
            //  • No  → keep the existing curve coefficients as-is; the
            //          regression will look slightly offset from the
            //          updated stars, which is the user's choice.
            customConfirm(
                '<?php echo TEXT_ET_NEW_PERIOD_CHANGED_MSG; ?>',
                function onYes() {
                    // Drop manual edits and cached state, then trigger
                    // a full preview reload which will refit on the
                    // new gauging set.
                    newCurveDirty  = false;
                    newCurvePoints = null;
                    newCurveCoeffs = null;
                    try { window.__lastPreviewResponse = null; } catch (e) {}
                    newPreviewSchedule();
                },
                function onNo() {
                    // Refresh stars only: re-fetch the gauging points
                    // from the server but keep the existing curve
                    // visible by NOT clearing newCurveCoeffs. The
                    // preview-render side reuses the cached curve when
                    // the response carries no fresh one.
                    newPreviewSchedule();
                }
            );
            return;
        }

        // No regression yet — purely visual update.
        newPreviewSchedule();
    }

    ['new_date_debut_periode','new_heure_debut_periode',
     'new_date_fin_periode','new_heure_fin_periode'].forEach(function(id) {
        var el = document.getElementById(id);
        if (el) {
            el.addEventListener('input',  newPeriodChangedHandler);
            el.addEventListener('change', newPeriodChangedHandler);
        }
    });

    // H₀ still triggers the confirmed-refresh because it directly
    // changes the regression result (the power-law fit linearises in
    // log(H − H₀)).
    ['origine_h0'].forEach(function(id) {
        var el = document.getElementById(id);
        if (el) {
            el.addEventListener('input',  newPreviewScheduleConfirmed);
            el.addEventListener('change', newPreviewScheduleConfirmed);
        }
    });

    // Optimise-H₀ checkbox: disable the manual field when on, and re-run
    // the regression (the fit itself changes, so it's a "confirmed" refresh).
    (function() {
        var cb  = document.getElementById('new_h0_auto');
        var fld = document.getElementById('origine_h0');
        if (!cb) return;
        cb.addEventListener('change', function() {
            if (fld) {
                fld.disabled = cb.checked;
                fld.style.background = cb.checked ? '#eee' : '';
            }
            newPreviewScheduleConfirmed();
        });
    })();

    // Model radios — only 'change' fires once per selection.
    document.querySelectorAll("input[name='new_eq_etl']").forEach(function(el) {
        el.addEventListener('change', newPreviewScheduleConfirmed);
    });

    // Density inputs (section 4: Min / Max / Interval) — changing these
    // doesn't affect the regression coefficients, just the sampling of
    // the curve. We resample client-side from the cached coefficients,
    // avoiding a server round-trip.
    document.querySelectorAll('input.new_density').forEach(function(el) {
        el.addEventListener('input',  scheduleResample);
        el.addEventListener('change', scheduleResample);
    });

    // Validity-range chaining (section 4):
    //   when the Max (sup_N) of a row is set, the Min (inf_{N+1}) of the
    //   next row is auto-filled with that same value (so ranges connect).
    //   The user can still freely edit any field afterwards.
    (function() {
        for (var n = 1; n <= 4; n++) {
            (function(rowN) {
                var supEl = document.getElementById('sup_' + rowN);
                if (!supEl) return;
                supEl.addEventListener('change', function() {
                    var supVal = parseFloat(supEl.value);
                    if (!isFinite(supVal)) return;
                    var nextInf = document.getElementById('inf_' + (rowN + 1));
                    if (nextInf) {
                        nextInf.value = supVal;
                        nextInf.dispatchEvent(new Event('change'));
                    }
                });
            })(n);
        }
    })();

    // Hide H0 input when polynomial is selected (only used by power law)
    document.querySelectorAll("input[name='new_eq_etl']").forEach(function(radio) {
        radio.addEventListener('change', function() {
            var wrap = document.getElementById('new_h0_wrap');
            if (!wrap) return;
            var picked = document.querySelector("input[name='new_eq_etl']:checked");
            wrap.style.display = (picked && picked.value === '1') ? 'block' : 'none';
        });
    });

    // PI band toggle — pure render toggle, no server call needed
    (function() {
        var cb = document.getElementById('new_show_pi');
        if (!cb) return;
        cb.addEventListener('change', function() {
            // Render with the current state (newCurvePoints + bands cached).
            // We rebuild a minimal "response-like" object to feed newPreviewDraw.
            if (typeof window.__lastPreviewResponse === 'object' && window.__lastPreviewResponse) {
                newPreviewDraw(window.__lastPreviewResponse);
            }
        });
    })();

    // Log-log axes toggle — pure display toggle, just redraw with the
    // cached response (no recompute, no server call).
    (function() {
        var cb = document.getElementById('new_loglog');
        if (!cb) return;
        cb.addEventListener('change', function() {
            if (typeof window.__lastPreviewResponse === 'object' && window.__lastPreviewResponse) {
                newPreviewDraw(window.__lastPreviewResponse);
            }
        });
    })();

    // "Add regression" trigger — initial state shows only gauging points.
    // When clicked: reveal the model section, enable the curve/PI display,
    // and re-render using the data already in memory (no server roundtrip
    // needed because newPreviewLoad has already populated newCurvePoints
    // in the background).
    (function() {
        var btn = document.getElementById('btn_add_regression');
        var trigger = document.getElementById('new_regression_trigger');
        var section = document.getElementById('new_regression_section');
        if (!btn || !trigger || !section) return;

        // Subtle hover effect (consistent with the math-challenge popup style)
        btn.addEventListener('mouseenter', function() {
            btn.style.filter = 'brightness(0.92)';
            btn.style.boxShadow = '0 2px 6px rgba(0,0,0,0.18)';
        });
        btn.addEventListener('mouseleave', function() {
            btn.style.filter = '';
            btn.style.boxShadow = '0 1px 3px rgba(0,0,0,0.12)';
        });
        btn.addEventListener('mousedown', function() {
            btn.style.transform = 'translateY(1px)';
        });
        btn.addEventListener('mouseup', function() {
            btn.style.transform = '';
        });

        btn.addEventListener('click', function() {
            regressionEnabled = true;

            // Hide trigger, reveal section 2
            trigger.style.display = 'none';
            section.style.display = 'block';

            // Reveal everything that depends on a regression: sections 3-5
            // and the Save button (otherwise the user could try to save
            // without ever having set up a regression).
            var dependent = document.getElementById('new_regression_dependent');
            var saveBtn   = document.getElementById('new_etl');
            if (dependent) { dependent.style.display = 'block'; }
            if (saveBtn)   { saveBtn.style.display   = ''; }

            // Subtle fade-in: start slightly transparent, then settle
            section.style.opacity = '0';
            section.style.transition = 'opacity 0.25s ease-out';
            if (dependent) {
                dependent.style.opacity = '0';
                dependent.style.transition = 'opacity 0.25s ease-out';
            }
            // Force reflow so the transition fires
            void section.offsetWidth;
            section.style.opacity = '1';
            if (dependent) { dependent.style.opacity = '1'; }

            // Re-render with the data we already have. If newPreviewLoad
            // has not yet returned (e.g. user is fast), schedule a fresh
            // load so the curve appears as soon as data is ready.
            if (typeof window.__lastPreviewResponse === 'object' && window.__lastPreviewResponse) {
                newPreviewDraw(window.__lastPreviewResponse);
            } else {
                newPreviewSchedule();
            }
        });
    })();
    // -----------------------------------------------
    // STEP C — Save flow:
    //   1. Validate current state (period, points)
    //   2. POST to process_etl_new_check.php to detect period overlaps
    //   3. Show a confirmation popup with the math challenge
    //   4. On valid challenge, POST to process_etl_new_save.php

    // Format a SQL datetime 'yyyy-mm-dd HH:MM:SS' as 'dd-mm-yyyy HH:MM'.
    // Returns the input unchanged if it doesn't match the SQL pattern.
    function fmtSqlDate(s) {
        if (!s) return '';
        var m = String(s).match(/^(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2})/);
        if (!m) return s;
        return m[3] + '-' + m[2] + '-' + m[1] + ' ' + m[4] + ':' + m[5];
    }

    function attemptSaveNewETL()
    {
        // Period must be valid
        var d1 = document.getElementById('new_date_debut_periode').value;
        var h1 = document.getElementById('new_heure_debut_periode').value;
        var d2 = document.getElementById('new_date_fin_periode').value;
        var h2 = document.getElementById('new_heure_fin_periode').value;
        var dateOk = /^\d{2}-\d{2}-\d{4}$/.test(d1) && /^\d{2}-\d{2}-\d{4}$/.test(d2);
        if (!dateOk) {
            alert('<?php echo TEXT_ET_NEW_DATE_HINT; ?>');
            return;
        }
        // Need at least 2 curve points
        if (!newCurvePoints || newCurvePoints.length < 2) {
            alert('<?php echo TEXT_ET_NEW_REG_NEED_PTS; ?>');
            return;
        }

        // Ask the server for any conflicts
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'include/structure/etl/process_etl_new_check.php', true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.onreadystatechange = function() {
            if (xhr.readyState !== 4) return;
            if (xhr.status !== 200) {
                console.error('[ETL save] check HTTP', xhr.status, xhr.responseText);
                return;
            }
            var r;
            try { r = JSON.parse(xhr.responseText); }
            catch (e) {
                console.error('[ETL save] bad JSON from check:', xhr.responseText);
                return;
            }

            // Update section 5 with conflicts summary
            renderConflictsBox(r.conflicts || [], !!r.blocking);

            // Blocking case → refuse, show explanation
            if (r.blocking) {
                customConfirmMessage(
                    '<?php echo TEXT_ET_NEW_BLOCKING_TITLE; ?>',
                    '<?php echo TEXT_ET_NEW_BLOCKING_MSG; ?>'
                );
                return;
            }

            // Otherwise: open the save confirmation popup with the math challenge
            openSaveChallengePopup(r.conflicts || [], { d1: d1, h1: h1, d2: d2, h2: h2 });
        };
        xhr.send(JSON.stringify({
            idStation: idStation,
            date1: d1, heure1: h1,
            date2: d2, heure2: h2
        }));
    }


    function renderConflictsBox(conflicts, blocking)
    {
        var box = document.getElementById('new_conflicts_box');
        if (!box) return;
        if (!conflicts || conflicts.length === 0) {
            box.style.color = '#0a7d34';
            box.innerHTML = '✓ <?php echo TEXT_ET_NEW_CONFLICTS_NONE; ?>';
            return;
        }
        box.style.color = '#333';
        var html = '';
        for (var i = 0; i < conflicts.length; i++) {
            var c = conflicts[i];
            var label = '<?php echo TEXT_ET_NEW_CONFLICT_ACTION; ?>: ';
            var color = '#BA7517';
            switch (c.action) {
                case 'delete':         label += '<?php echo TEXT_ET_NEW_CONFLICT_DELETE;    ?>'; color = '#a32d2d'; break;
                case 'truncate_right': label += '<?php echo TEXT_ET_NEW_CONFLICT_TRUNC_R;  ?>'; break;
                case 'truncate_left':  label += '<?php echo TEXT_ET_NEW_CONFLICT_TRUNC_L;  ?>'; break;
                case 'blocking':       label += '<?php echo TEXT_ET_NEW_CONFLICT_BLOCKING; ?>'; color = '#a32d2d'; break;
            }
            var beforeTxt = fmtSqlDate(c.datetime_first) + ' → ' + fmtSqlDate(c.datetime_end);
            var afterTxt = '';
            if (c.action === 'delete') {
                afterTxt = '<i style="color:#a32d2d;"><?php echo TEXT_ET_NEW_CHALLENGE_DELETED; ?></i>';
            } else if (c.action !== 'blocking') {
                afterTxt = fmtSqlDate(c.new_first) + ' → ' + fmtSqlDate(c.new_end);
            }
            html += '<div style="margin:6px 0;padding:6px 8px;border-left:3px solid ' + color + ';background:#fafafa;">'
                  + '<b>RC ' + c.num + '</b><br>'
                  + '<span style="color:' + color + ';font-size:11px;">' + label + '</span>'
                  + '<br><span style="font-size:10px;color:#666;"><?php echo TEXT_ET_NEW_CHALLENGE_BEFORE; ?> :</span> '
                  + '<span style="font-size:10px;">' + beforeTxt + '</span>';
            if (afterTxt) {
                html += '<br><span style="font-size:10px;color:#0a7d34;"><?php echo TEXT_ET_NEW_CHALLENGE_AFTER; ?> :</span> '
                      + '<span style="font-size:10px;">' + afterTxt + '</span>';
            }
            html += '</div>';
        }
        box.innerHTML = html;
    }


    // -----------------------------------------------
    // Save confirmation popup with math challenge.
    // Reuses the same anti-misclick pattern as block_verif_deletedata.

    function openSaveChallengePopup(conflicts, newPeriod)
    {
        // Remove any previous instance
        var prev = document.getElementById('etl_save_challenge');
        if (prev) prev.remove();

        // Build the question/answer
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

        // New RC period banner — strip seconds from time strings if present
        var hStart = (newPeriod && newPeriod.h1) ? newPeriod.h1.substring(0, 5) : '';
        var hEnd   = (newPeriod && newPeriod.h2) ? newPeriod.h2.substring(0, 5) : '';
        var newPeriodHtml = '';
        if (newPeriod) {
            newPeriodHtml = '<div style="margin:10px 0;padding:8px 10px;background:#eef5f8;border-left:3px solid #176B87;font-size:12px;">'
                          + '<b><?php echo TEXT_ET_NEW_CHALLENGE_NEW_PERIOD; ?></b><br>'
                          + newPeriod.d1 + ' ' + hStart + '  &rarr;  ' + newPeriod.d2 + ' ' + hEnd
                          + '</div>';
        }

        // Conflicts summary with Before / After dates
        var conflictHtml = '';
        if (conflicts.length > 0) {
            conflictHtml = '<div style="margin:10px 0;padding:8px 10px;background:#fff7e6;border-left:3px solid #BA7517;font-size:12px;">'
                         + '<b><?php echo TEXT_ET_NEW_CHALLENGE_CONFLICTS; ?></b>'
                         + '<ul style="margin:6px 0 0 18px;padding:0;list-style:disc;">';
            for (var i = 0; i < conflicts.length; i++) {
                var c = conflicts[i];
                var actionTxt = '';
                switch (c.action) {
                    case 'delete':         actionTxt = '<?php echo TEXT_ET_NEW_CONFLICT_DELETE;   ?>'; break;
                    case 'truncate_right': actionTxt = '<?php echo TEXT_ET_NEW_CONFLICT_TRUNC_R; ?>'; break;
                    case 'truncate_left':  actionTxt = '<?php echo TEXT_ET_NEW_CONFLICT_TRUNC_L; ?>'; break;
                }
                var beforeTxt = fmtSqlDate(c.datetime_first) + ' &rarr; ' + fmtSqlDate(c.datetime_end);
                var afterTxt;
                if (c.action === 'delete') {
                    afterTxt = '<span style="color:#a32d2d;font-style:italic;"><?php echo TEXT_ET_NEW_CHALLENGE_DELETED; ?></span>';
                } else {
                    afterTxt = fmtSqlDate(c.new_first) + ' &rarr; ' + fmtSqlDate(c.new_end);
                }
                conflictHtml += '<li style="margin-bottom:6px;">'
                              + '<b>RC ' + c.num + '</b> &mdash; ' + actionTxt
                              + '<br><span style="color:#666;font-size:11px;"><?php echo TEXT_ET_NEW_CHALLENGE_BEFORE; ?> :</span> ' + beforeTxt
                              + '<br><span style="color:#0a7d34;font-size:11px;"><?php echo TEXT_ET_NEW_CHALLENGE_AFTER;  ?> :</span> ' + afterTxt
                              + '</li>';
            }
            conflictHtml += '</ul></div>';
        }

        var overlay = document.createElement('div');
        overlay.id = 'etl_save_challenge';
        overlay.style.cssText =
            'position:fixed;top:0;left:0;width:100vw;height:100vh;'
          + 'background:rgba(0,0,0,0.4);z-index:3000;'
          + 'display:flex;align-items:center;justify-content:center;';
        overlay.innerHTML =
            '<div style="background:#FBF9F1;border-radius:4px;width:480px;max-width:90vw;'
          + 'box-shadow:0 8px 24px rgba(0,0,0,0.3);overflow:hidden;font-family:inherit;">'
          + '<div style="background:#176B87;color:#fff;padding:10px 14px;font-size:14px;font-weight:bold;">'
          +   '<?php echo TEXT_ET_NEW_SAVE_CONFIRM_TITLE; ?>'
          + '</div>'
          + '<div style="padding:14px 16px;font-size:13px;line-height:1.5;color:#333;">'
          +   '<?php echo TEXT_ET_NEW_SAVE_CONFIRM_MSG; ?>'
          +   newPeriodHtml
          +   conflictHtml
          +   '<div style="margin-top:14px;padding:10px;background:#fff;border:1px solid #ddd;border-radius:3px;">'
          +     '<div style="font-size:12px;color:#666;margin-bottom:6px;">'
          +       '<?php echo TEXT_ET_NEW_CHALLENGE_HINT; ?>'
          +     '</div>'
          +     '<div style="display:flex;align-items:center;gap:8px;">'
          +       '<span style="font-size:16px;font-weight:bold;">' + a + ' ' + op + ' ' + b + ' = </span>'
          +       '<input id="etl_challenge_answer" type="text" style="width:60px;font-size:16px;padding:4px;" autofocus>'
          +       '<span id="etl_challenge_feedback" style="font-size:12px;"></span>'
          +     '</div>'
          +   '</div>'
          + '</div>'
          + '<div style="padding:8px 14px 14px;display:flex;justify-content:flex-end;gap:8px;">'
          +   '<button id="etl_save_cancel" class="button_close" style="width:120px;"><?php echo TEXT_BTN_CANCEL; ?></button>'
          +   '<button id="etl_save_confirm" class="button" style="width:140px;opacity:0.45;cursor:not-allowed;" disabled>'
          +     '<?php echo TEXT_BTN_SAVE; ?>'
          +   '</button>'
          + '</div>'
          + '</div>';
        document.body.appendChild(overlay);

        var input    = overlay.querySelector('#etl_challenge_answer');
        var feedback = overlay.querySelector('#etl_challenge_feedback');
        var confirmBtn = overlay.querySelector('#etl_save_confirm');
        var cancelBtn  = overlay.querySelector('#etl_save_cancel');

        // Hover effect: darken the button background slightly on mouseover.
        // We don't know the original background (it comes from .button /
        // .button_close global CSS), so we just overlay a translucent dark
        // tint via a CSS filter, which works regardless of the base color.
        function wireHover(btn) {
            btn.addEventListener('mouseenter', function() {
                if (btn.disabled) return;
                btn.style.filter = 'brightness(0.9)';
            });
            btn.addEventListener('mouseleave', function() {
                btn.style.filter = '';
            });
        }
        wireHover(cancelBtn);
        wireHover(confirmBtn);

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
                doSaveNewETL(conflicts);
            }
        }
        document.addEventListener('keydown', onKey);

        cancelBtn.addEventListener('click', cleanup);
        confirmBtn.addEventListener('click', function() {
            cleanup();
            doSaveNewETL(conflicts);
        });

        setTimeout(function() { input.focus(); }, 100);
    }


    function doSaveNewETL(conflicts)
    {
        var d1 = document.getElementById('new_date_debut_periode').value;
        var h1 = document.getElementById('new_heure_debut_periode').value;
        var d2 = document.getElementById('new_date_fin_periode').value;
        var h2 = document.getElementById('new_heure_fin_periode').value;

        // Strip the rangeIndex tag — server only needs h/q
        var ptsForServer = newCurvePoints.map(function(p) {
            return { h: p.h, q: p.q };
        });

        // Pass only the bare minimum about conflicts (id + action)
        var conflictsForServer = conflicts.map(function(c) {
            return { id: c.id, action: c.action };
        });

        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'include/structure/etl/process_etl_new_save.php', true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.onreadystatechange = function() {
            if (xhr.readyState !== 4) return;
            if (xhr.status !== 200) {
                console.error('[ETL save] HTTP', xhr.status, xhr.responseText);
                alert('<?php echo TEXT_ET_NEW_SAVE_ERR; ?>');
                return;
            }
            var r;
            try { r = JSON.parse(xhr.responseText); }
            catch (e) {
                console.error('[ETL save] bad JSON:', xhr.responseText);
                alert('<?php echo TEXT_ET_NEW_SAVE_ERR; ?>');
                return;
            }
            if (r.valid_process) {
                // Close the popup and reload the page so the new ETL appears
                document.getElementById('box_elt_new').style.display = 'none';
                window.location.reload();
            } else {
                if (r.js_text === 'concurrent_overlap') {
                    customConfirmMessage(
                        '<?php echo TEXT_ET_NEW_CONCURRENT_TITLE; ?>',
                        '<?php echo TEXT_ET_NEW_CONCURRENT_MSG; ?>'
                    );
                } else {
                    alert(r.js_text || '<?php echo TEXT_ET_NEW_SAVE_ERR; ?>');
                }
            }
        };
        xhr.send(JSON.stringify({
            idUser: idUser,
            todayTimeFormatted: todayTimeFormatted,
            idStation: idStation,
            date1: d1, heure1: h1,
            date2: d2, heure2: h2,
            points: ptsForServer,
            conflicts: conflictsForServer
        }));
    }


    // Simple info popup (no buttons, just a message + close)
    function customConfirmMessage(title, message)
    {
        var overlay = document.createElement('div');
        overlay.style.cssText =
            'position:fixed;top:0;left:0;width:100vw;height:100vh;'
          + 'background:rgba(0,0,0,0.4);z-index:3000;'
          + 'display:flex;align-items:center;justify-content:center;';
        overlay.innerHTML =
            '<div style="background:#FBF9F1;border-radius:4px;width:420px;max-width:90vw;'
          + 'box-shadow:0 8px 24px rgba(0,0,0,0.3);overflow:hidden;font-family:inherit;">'
          + '<div style="background:#a32d2d;color:#fff;padding:10px 14px;font-size:14px;font-weight:bold;">'
          +   title
          + '</div>'
          + '<div style="padding:16px;font-size:13px;line-height:1.5;color:#333;">'
          +   message
          + '</div>'
          + '<div style="padding:8px 14px 14px;display:flex;justify-content:flex-end;">'
          +   '<button class="button" style="width:120px;"><?php echo TEXT_BTN_CANCEL; ?></button>'
          + '</div>'
          + '</div>';
        document.body.appendChild(overlay);
        var btn = overlay.querySelector('button');
        btn.addEventListener('click', function() { overlay.remove(); });
        btn.addEventListener('mouseenter', function() { btn.style.filter = 'brightness(0.9)'; });
        btn.addEventListener('mouseleave', function() { btn.style.filter = ''; });
    }

</script>