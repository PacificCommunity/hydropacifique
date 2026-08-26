<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
RC point editor popup — included by modif_etl.php
Lets the user edit (drag + edit-popup) the points of a single existing RC.
The period is shown but not editable. Save replaces all points atomically.
----------------------------------------
*/

echo "<div id='box_elt_edit' class='block_view'"
   . " style='display:none;position:fixed;top:5vh;left:5vw;width:90vw;height:82vh;"
   . "background:transparent;z-index:2000;resize:both;overflow:hidden;min-width:680px;min-height:400px;"
   . "border:1px solid #c0c0c0;border-radius:4px;box-shadow:0 4px 16px rgba(0,0,0,0.15);'>\n";

    echo "<div id='cadre_view_2' style='padding:0;margin:0 !important;margin-top:0 !important;background-color:#FBF9F1;"
       . "border-radius:4px;overflow:hidden;height:100%;display:flex;flex-direction:column;'>\n";

        // ---- Header (drag handle, styled by .block_view CSS) ----
        echo "<div id='box_elt_edit_header'"
           . " style='cursor:move;user-select:none;flex-shrink:0;margin:0;'>\n";
            echo "<span>" . TEXT_ET_EDIT_TITLE . "</span>";
            echo "<span id='button_close' onclick=\"document.getElementById('box_elt_edit').style.display='none';\">&times;</span>";
        echo "</div>\n";

        // ---- Body: just the plot + info + buttons ----
        echo "<div style='flex:1 1 0;min-height:0;display:flex;flex-direction:column;padding:14px 16px;'>\n";

            // Top row: RC info + editable period + buttons (always visible)
            // Layout: [RC N] [date1] [h1] → [date2] [h2] [counts] ............ [Cancel][Save]
            // Datepicker wiring is identical to the New popup:
            // onFocus='initDatepickers(this)' on the two date inputs.
            echo "<div style='display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;flex-shrink:0;gap:8px;flex-wrap:wrap;'>\n";
                echo "<div style='display:flex;align-items:center;gap:6px;font-size:12px;flex-wrap:wrap;'>\n";
                    echo "<span id='edit_rc_num' style='font-size:13px;font-weight:bold;'></span>";
                    echo "<input style='width:95px;box-sizing:border-box;'"
                       . " id='edit_date_debut_periode' type='text'"
                       . " onFocus='initDatepickers(this)' placeholder='dd-mm-yyyy'>";
                    echo "<input style='width:75px;box-sizing:border-box;'"
                       . " id='edit_heure_debut_periode' type='text' value='00:00:00'>";
                    echo "<span style='color:#666;'>&rarr;</span>";
                    echo "<input style='width:95px;box-sizing:border-box;'"
                       . " id='edit_date_fin_periode' type='text' data-allow-future='1'"
                       . " onFocus='initDatepickers(this)' placeholder='dd-mm-yyyy'>";
                    echo "<input style='width:75px;box-sizing:border-box;'"
                       . " id='edit_heure_fin_periode' type='text' value='23:59:59'>";
                    echo "<span id='edit_rc_counts' style='color:#666;'></span>";
                echo "</div>\n";
                echo "<div style='display:flex;gap:8px;'>\n";
                    echo "<input type='button' class='button_close' style='width:120px;' value='"
                       . TEXT_BTN_CANCEL . "'"
                       . " onclick=\"document.getElementById('box_elt_edit').style.display='none';\">";
                    echo "<input type='button' class='button' style='width:160px;' id='edit_rc_save_btn'"
                       . " value='" . TEXT_BTN_SAVE . "' onclick='attemptSaveEditRC();'>";
                echo "</div>\n";
            echo "</div>\n";

            // Chart container
            echo "<div id='edit_preview_plot' style='flex:1;min-height:0;background:#fff;border:1px solid #e0e0e0;border-radius:3px;'></div>\n";

            // Hint line under the chart
            echo "<div style='margin-top:6px;font-size:11px;color:#666;flex-shrink:0;'>"
               . TEXT_ET_EDIT_HINT_DRAG
               . " &middot; "
               . TEXT_ET_EDIT_HINT_RCLICK
               . "</div>\n";

        echo "</div>\n";

    echo "</div>\n";

echo "</div>\n";
?>

<script type="text/javascript">

    // -----------------------------------------------
    // RC Edit popup state

    var editRcId         = null;    // ID of the RC being edited
    var editRcStation    = null;    // its id_station (for save validation)
    var editCurvePoints  = [];      // [{h, q}] — source of truth, modified by drag/edit
    var editJgePoints    = [];      // [{h, q, date, id_jge}] — read-only reference SG points
    var editDragState    = null;    // {pointIndex, plotEl} during drag


    // -----------------------------------------------
    // Drag the popup by its header (same pattern as New popup)

    (function() {
        var header = document.getElementById('box_elt_edit_header');
        var popup  = document.getElementById('box_elt_edit');
        if (!header || !popup) return;
        var dragging = false;
        var offX = 0, offY = 0;
        header.addEventListener('mousedown', function(e) {
            if (e.target.tagName === 'SPAN' && e.target.textContent === '×') return;
            dragging = true;
            var rect = popup.getBoundingClientRect();
            offX = e.clientX - rect.left;
            offY = e.clientY - rect.top;
            document.body.style.userSelect = 'none';
        });
        document.addEventListener('mousemove', function(e) {
            if (!dragging) return;
            var newLeft = e.clientX - offX;
            var newTop  = e.clientY - offY;
            var w = popup.offsetWidth, h = popup.offsetHeight;
            var vw = window.innerWidth, vh = window.innerHeight;
            if (newLeft < 0)       { newLeft = 0; }
            if (newTop  < 0)       { newTop  = 0; }
            if (newLeft + w > vw)  { newLeft = vw - w; }
            if (newTop  + h > vh)  { newTop  = vh - h; }
            popup.style.left = newLeft + 'px';
            popup.style.top  = newTop  + 'px';
        });
        document.addEventListener('mouseup', function() {
            if (!dragging) return;
            dragging = false;
            document.body.style.userSelect = '';
        });
    })();


    // -----------------------------------------------
    // Resize plot when popup is resized

    (function() {
        if (!window.ResizeObserver) return;
        var plotEl = document.getElementById('edit_preview_plot');
        if (!plotEl) return;
        var deb = null;
        var ro = new ResizeObserver(function() {
            clearTimeout(deb);
            deb = setTimeout(function() {
                if (plotEl.offsetWidth > 0 && plotEl.children.length > 0) {
                    Plotly.Plots.resize(plotEl);
                }
            }, 80);
        });
        ro.observe(plotEl);
    })();


    // -----------------------------------------------
    // Close the popup on Escape key (but only when the popup is visible
    // and no inner overlay/popup is open, so Escape doesn't accidentally
    // close the main popup when the user just wanted to dismiss a sub-popup).

    document.addEventListener('keydown', function(e) {
        if (e.key !== 'Escape') return;
        var popup = document.getElementById('box_elt_edit');
        if (!popup || popup.style.display !== 'block') return;
        // Skip if the save challenge sub-popup is currently shown.
        if (document.getElementById('edit_save_challenge')) return;
        popup.style.display = 'none';
    });


    // -----------------------------------------------
    // Open the popup for a given RC id

    function openEditRC(idRc)
    {
        editRcId        = idRc;
        editCurvePoints = [];

        // Show the popup empty while we fetch
        document.getElementById('box_elt_edit').style.display = 'block';
        document.getElementById('edit_rc_num').textContent = '<?php echo TEXT_ET_EDIT_LOADING; ?>';
        document.getElementById('edit_rc_counts').textContent = '';
        // Clear the period fields while loading so old values don't briefly
        // show through if the user clicks fast.
        document.getElementById('edit_date_debut_periode').value  = '';
        document.getElementById('edit_heure_debut_periode').value = '';
        document.getElementById('edit_date_fin_periode').value    = '';
        document.getElementById('edit_heure_fin_periode').value   = '';

        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'include/structure/etl/process_etl_edit_load.php', true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.onreadystatechange = function() {
            if (xhr.readyState !== 4) return;
            if (xhr.status !== 200) {
                console.error('[Edit RC] HTTP', xhr.status, xhr.responseText);
                return;
            }
            var r;
            try { r = JSON.parse(xhr.responseText); }
            catch (e) {
                console.error('[Edit RC] bad JSON:', xhr.responseText);
                return;
            }
            if (r.error) {
                document.getElementById('edit_rc_num').textContent = '<?php echo TEXT_ET_EDIT_LOAD_ERR; ?>';
                return;
            }
            editRcStation   = r.id_station;
            editCurvePoints = (r.points || []).map(function(p) { return { h: p.h, q: p.q }; });
            editJgePoints   = (r.jge_points || []).map(function(p) {
                return { h: p.h, q: p.q, date: p.date, id_jge: p.id_jge };
            });

            // RC label + counts (separated from the period inputs which sit between them)
            document.getElementById('edit_rc_num').textContent = 'RC ' + r.num;
            document.getElementById('edit_rc_counts').textContent =
                '(' + editCurvePoints.length + ' <?php echo TEXT_ET_DEL_POINTS; ?>'
              + ' · ' + editJgePoints.length + ' SG)';

            // Pre-fill the period fields with the RC's current period.
            // datetime_first / datetime_end come as SQL 'yyyy-mm-dd HH:MM:SS';
            // we split them into dd-mm-yyyy (date) + HH:MM:SS (time).
            // The datepicker (flatpickr via initDatepickers) is wired by
            // onFocus on each input, exactly as in the New popup.
            var p1 = splitSqlDatetime(r.datetime_first);
            var p2 = splitSqlDatetime(r.datetime_end);
            document.getElementById('edit_date_debut_periode').value  = p1.date;
            document.getElementById('edit_heure_debut_periode').value = p1.time;
            document.getElementById('edit_date_fin_periode').value    = p2.date;
            document.getElementById('edit_heure_fin_periode').value   = p2.time;

            editPlotRender();
        };
        xhr.send(JSON.stringify({ id: idRc }));
    }


    // Split a SQL datetime 'yyyy-mm-dd HH:MM:SS' into { date:'dd-mm-yyyy', time:'HH:MM:SS' }.
    // Falls back to empty strings if the format doesn't match.
    function splitSqlDatetime(s) {
        if (!s) return { date: '', time: '' };
        var m = String(s).match(/^(\d{4})-(\d{2})-(\d{2}) (\d{2}:\d{2}:\d{2})/);
        if (!m) return { date: '', time: '' };
        return { date: m[3] + '-' + m[2] + '-' + m[1], time: m[4] };
    }


    // Format SQL datetime "yyyy-mm-dd HH:MM:SS" → "dd-mm-yyyy HH:MM"
    function fmtSqlDateForEdit(s) {
        if (!s) return '';
        var m = String(s).match(/^(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2})/);
        if (!m) return s;
        return m[3] + '-' + m[2] + '-' + m[1] + ' ' + m[4] + ':' + m[5];
    }


    // -----------------------------------------------
    // Render the chart: only the editable curve, nothing else
    // (no JGE, no regression — pure point editing).

    function editPlotRender()
    {
        var plotEl = document.getElementById('edit_preview_plot');
        if (!plotEl || typeof Plotly === 'undefined') return;

        if (editCurvePoints.length < 2) {
            try { Plotly.purge(plotEl); } catch (e) {}
            plotEl.innerHTML = '<div style="padding:30px;text-align:center;color:#666;">'
                             + '<?php echo TEXT_ET_EDIT_TOO_FEW_PTS; ?></div>';
            return;
        }

        var unitH = '<?php echo TEXT_ET_COORD_UNIT_H; ?>';
        var unitQ = '<?php echo TEXT_ET_COORD_UNIT_Q; ?>';

        // Read the global swap-axes toggle (same source as in the New popup).
        var swapAxes = document.getElementById('swap_axes')
                     ? document.getElementById('swap_axes').checked : false;
        var phH = swapAxes ? '%{y:.2f}' : '%{x:.2f}';
        var phQ = swapAxes ? '%{x:.3f}' : '%{y:.3f}';

        // Trace 0: editable curve (must stay at index 0 — hit-test and
        // drag logic below assume it).
        var curveTrace = {
            x: editCurvePoints.map(function(p) { return swapAxes ? p.q : p.h; }),
            y: editCurvePoints.map(function(p) { return swapAxes ? p.h : p.q; }),
            mode: 'markers+lines', type: 'scatter',
            name: '<?php echo TEXT_ET_NEW_CURVE_LABEL; ?>',
            line:   { color: '#D85A30', width: 2 },
            marker: { size: 9, symbol: 'square', color: '#D85A30',
                      line: { color: '#702c10', width: 1 } },
            hovertemplate: '<b><?php echo TEXT_ET_NEW_CURVE_LABEL; ?></b>'
                         + '<br><span style="font-size:9px;color:#aaa">────────────</span>'
                         + '<br><b><?php echo TEXT_ET_TOOLTIP_H; ?></b> ' + phH + ' ' + unitH
                         + '<br><b><?php echo TEXT_ET_TOOLTIP_Q; ?></b> ' + phQ + ' ' + unitQ
                         + '<br><span style="font-size:10px;color:#888"><?php echo TEXT_ET_EDIT_CURVE_HINT; ?></span>'
                         + '<extra></extra>'
        };

        // Trace 1: SG (gauging) reference points — read-only blue stars
        var traces = [curveTrace];
        if (editJgePoints.length > 0) {
            var jgeTrace = {
                x: editJgePoints.map(function(p) { return swapAxes ? p.q : p.h; }),
                y: editJgePoints.map(function(p) { return swapAxes ? p.h : p.q; }),
                customdata: editJgePoints.map(function(p) { return [p.date, p.id_jge]; }),
                mode: 'markers', type: 'scatter',
                name: '<?php echo TEXT_ET_LABEL_JGE; ?>',
                marker: { size: 11, symbol: 'star', color: '#1f3b6b',
                          line: { color: '#0a1a3a', width: 1 } },
                hoverinfo: 'skip', // we use hovertemplate instead
                hovertemplate: '<b><?php echo TEXT_ET_LABEL_JGE; ?></b>'
                             + '<br><span style="font-size:9px;color:#aaa">────────────</span>'
                             + '<br><b><?php echo TEXT_ET_TOOLTIP_DATE; ?></b> %{customdata[0]}'
                             + '<br><b><?php echo TEXT_ET_TOOLTIP_H; ?></b> ' + phH + ' ' + unitH
                             + '<br><b><?php echo TEXT_ET_TOOLTIP_Q; ?></b> ' + phQ + ' ' + unitQ
                             + '<br><span style="font-size:10px;color:#888">'
                             + '<?php echo TEXT_ET_SG_OPEN_HINT; ?></span>'
                             + '<extra></extra>'
            };
            traces.push(jgeTrace);
        }

        var titleH = '<?php echo TEXT_ET_AXIS_H; ?>';
        var titleQ = '<?php echo TEXT_ET_AXIS_Q; ?>';

        var layout = {
            xaxis: { title: { text: swapAxes ? titleQ : titleH, standoff: 15 }, autorange: true },
            yaxis: { title: { text: swapAxes ? titleH : titleQ, standoff: 15 }, autorange: true },
            hovermode: 'closest',
            hoverlabel: { bgcolor: '#fff', bordercolor: '#888', font: { size: 12, color: '#000' } },
            margin: { l: 60, r: 15, t: 15, b: 55 },
            // Interactive legend — click an item to toggle the corresponding
            // trace, double-click to isolate it. Placed inside the plot area
            // (top-left, semi-transparent background) for consistency with
            // the New popup.
            showlegend: editJgePoints.length > 0,
            legend: {
                x: 0.1, y: 0.99,
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

        Plotly.newPlot(plotEl, traces, layout, config);
    }


    // -----------------------------------------------
    // Drag a single curve point with the mouse
    // (capture phase + Plotly.relayout to suppress chart pan, same as New)

    // Shift+Click on an SG point opens the SG page in a new tab.
    // We handle this at the document level in capture phase BEFORE the
    // drag logic runs — otherwise the drag's hit-test (on the curve)
    // would swallow the event if the click landed near a curve point.
    document.addEventListener('mousedown', function(e) {
        if (!e.shiftKey) return;
        var plotEl = document.getElementById('edit_preview_plot');
        if (!plotEl || !plotEl.contains(e.target)) return;
        if (!editJgePoints || editJgePoints.length === 0) return;

        var idJge = editHitTestSg(e, plotEl);
        if (idJge === null) return;

        // Found a SG within click distance → open it and stop everything
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();
        window.open('modif_jge.php?ref=' + encodeURIComponent(idJge), '_blank');
    }, true);

    // Hit-test on SG points only (returns the id_jge of the closest SG
    // within 14 px, or null). Mirrors editHitTestPoint's structure.
    function editHitTestSg(e, plotEl) {
        if (!plotEl._fullLayout) return null;
        var rect = plotEl.getBoundingClientRect();
        var cx = e.clientX - rect.left;
        var cy = e.clientY - rect.top;
        var xa = plotEl._fullLayout.xaxis;
        var ya = plotEl._fullLayout.yaxis;
        if (!xa || !ya) return null;
        var swapAxes = document.getElementById('swap_axes')
                     ? document.getElementById('swap_axes').checked : false;
        var bestId = null, bestD2 = 14 * 14 + 1;
        for (var i = 0; i < editJgePoints.length; i++) {
            var p  = editJgePoints[i];
            var px = xa.l2p(swapAxes ? p.q : p.h) + xa._offset;
            var py = ya.l2p(swapAxes ? p.h : p.q) + ya._offset;
            var dx = px - cx, dy = py - cy;
            var d2 = dx * dx + dy * dy;
            if (d2 < bestD2) { bestD2 = d2; bestId = p.id_jge; }
        }
        return bestId;
    }

    document.addEventListener('mousedown', function(e) {
        // Left button only — right-click is reserved for add/remove via contextmenu.
        if (e.button !== 0) return;
        var plotEl = document.getElementById('edit_preview_plot');
        if (!plotEl || !plotEl.contains(e.target)) return;
        if (editCurvePoints.length < 2) return;

        var hit = editHitTestPoint(e, plotEl);
        if (hit === -1) return;

        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();

        var swapAxes = document.getElementById('swap_axes')
                     ? document.getElementById('swap_axes').checked : false;

        var prevDragmode = (plotEl._fullLayout && plotEl._fullLayout.dragmode) || 'pan';
        try { Plotly.relayout(plotEl, { dragmode: false }); } catch (err) {}

        editDragState = {
            pointIndex: hit,
            plotEl: plotEl,
            swapAxes: swapAxes,
            moved: false,
            prevDragmode: prevDragmode
        };
        plotEl.style.cursor = 'grabbing';
    }, true);

    document.addEventListener('mousemove', function(e) {
        if (!editDragState) return;
        e.stopPropagation();
        var plotEl = editDragState.plotEl;
        var coord  = editPixelToData(e, plotEl);
        if (!coord) return;
        // Translate plot coords (x, y) back to metric (h, q) using the
        // swap state captured at mousedown.
        var p = editCurvePoints[editDragState.pointIndex];
        if (editDragState.swapAxes) { p.q = coord.x; p.h = coord.y; }
        else                         { p.h = coord.x; p.q = coord.y; }
        editDragState.moved = true;
        var xs = editCurvePoints.map(function(pp) { return editDragState.swapAxes ? pp.q : pp.h; });
        var ys = editCurvePoints.map(function(pp) { return editDragState.swapAxes ? pp.h : pp.q; });
        Plotly.restyle(plotEl, { x: [xs], y: [ys] }, [0]);
    }, true);

    document.addEventListener('mouseup', function(e) {
        if (!editDragState) return;
        e.stopPropagation();
        var plotEl = editDragState.plotEl;
        plotEl.style.cursor = '';
        var prevDragmode = editDragState.prevDragmode;
        editDragState = null;
        try { Plotly.relayout(plotEl, { dragmode: prevDragmode }); } catch (err) {}
    });


    // -----------------------------------------------
    // Right-click on the chart — add or remove a curve point.
    //
    //   - Right-click on an existing curve point  → remove it
    //   - Right-click anywhere else on the chart  → add a new point at
    //     the cursor position. The point is inserted at the right index
    //     so the array stays sorted by H (keeps the line drawing clean).
    //
    // We always preventDefault() so the browser context menu doesn't
    // appear over the chart. A minimum of 2 points is enforced on delete.

    document.addEventListener('contextmenu', function(e) {
        var plotEl = document.getElementById('edit_preview_plot');
        if (!plotEl || !plotEl.contains(e.target)) return;

        e.preventDefault();
        e.stopPropagation();

        // Try to delete an existing point first
        if (editCurvePoints.length >= 2) {
            var hit = editHitTestPoint(e, plotEl);
            if (hit !== -1) {
                if (editCurvePoints.length <= 2) {
                    // Keep at least 2 points — a curve with fewer can't be saved
                    alert('<?php echo TEXT_ET_EDIT_MIN_PTS; ?>');
                    return;
                }
                editCurvePoints.splice(hit, 1);
                editRefreshCurveTrace(plotEl);
                editUpdatePointsCount();
                return;
            }
        }

        // Otherwise add a new point at the cursor position
        var coord = editPixelToData(e, plotEl);
        if (!coord || !isFinite(coord.x) || !isFinite(coord.y)) return;

        var swapAxes = document.getElementById('swap_axes')
                     ? document.getElementById('swap_axes').checked : false;
        var newPt = swapAxes
            ? { h: coord.y, q: coord.x }
            : { h: coord.x, q: coord.y };

        // Insert at the right index so the array stays sorted by H —
        // Plotly draws the line in array order, so an out-of-order point
        // would create a zig-zag.
        var insertAt = editCurvePoints.length;
        for (var i = 0; i < editCurvePoints.length; i++) {
            if (newPt.h < editCurvePoints[i].h) { insertAt = i; break; }
        }
        editCurvePoints.splice(insertAt, 0, newPt);
        editRefreshCurveTrace(plotEl);
        editUpdatePointsCount();
    }, true);


    // Push editCurvePoints into Plotly's trace 0 (the editable curve).
    function editRefreshCurveTrace(plotEl) {
        var swapAxes = document.getElementById('swap_axes')
                     ? document.getElementById('swap_axes').checked : false;
        var xs = editCurvePoints.map(function(p) { return swapAxes ? p.q : p.h; });
        var ys = editCurvePoints.map(function(p) { return swapAxes ? p.h : p.q; });
        Plotly.restyle(plotEl, { x: [xs], y: [ys] }, [0]);
    }

    // Update the "(N points · M SG)" label in the popup header.
    // Now that the counts live in their own #edit_rc_counts span (instead
    // of being concatenated into a single info line), we just rewrite it
    // wholesale — simpler and immune to regex-fragility.
    function editUpdatePointsCount() {
        var counts = document.getElementById('edit_rc_counts');
        if (!counts) return;
        counts.textContent =
            '(' + editCurvePoints.length + ' <?php echo TEXT_ET_DEL_POINTS; ?>'
          + ' · ' + editJgePoints.length + ' SG)';
    }


    function editHitTestPoint(e, plotEl) {
        if (!plotEl._fullLayout) return -1;
        var rect = plotEl.getBoundingClientRect();
        var cx = e.clientX - rect.left;
        var cy = e.clientY - rect.top;
        var xa = plotEl._fullLayout.xaxis;
        var ya = plotEl._fullLayout.yaxis;
        if (!xa || !ya) return -1;
        var swapAxes = document.getElementById('swap_axes')
                     ? document.getElementById('swap_axes').checked : false;
        var bestIdx = -1, bestD2 = 12 * 12 + 1;
        for (var i = 0; i < editCurvePoints.length; i++) {
            var p = editCurvePoints[i];
            var px = xa.l2p(swapAxes ? p.q : p.h) + xa._offset;
            var py = ya.l2p(swapAxes ? p.h : p.q) + ya._offset;
            var dx = px - cx, dy = py - cy;
            var d2 = dx * dx + dy * dy;
            if (d2 < bestD2) { bestD2 = d2; bestIdx = i; }
        }
        return bestIdx;
    }

    function editPixelToData(e, plotEl) {
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
    // Save flow:
    //   1. Validate current state (period format, points count)
    //   2. POST to process_etl_new_check.php to detect period overlaps
    //      with OTHER existing RCs (the server must exclude editRcId from
    //      the search, otherwise the RC would conflict with itself)
    //   3. Show the confirmation popup (math challenge + conflicts summary)
    //   4. On valid challenge → POST to process_etl_edit_save.php

    function attemptSaveEditRC()
    {
        if (!editRcId || editCurvePoints.length < 2) {
            alert('<?php echo TEXT_ET_NEW_REG_NEED_PTS; ?>');
            return;
        }

        // Period format check — same regex as the New popup.
        var d1 = document.getElementById('edit_date_debut_periode').value;
        var h1 = document.getElementById('edit_heure_debut_periode').value;
        var d2 = document.getElementById('edit_date_fin_periode').value;
        var h2 = document.getElementById('edit_heure_fin_periode').value;
        var dateOk = /^\d{2}-\d{2}-\d{4}$/.test(d1) && /^\d{2}-\d{2}-\d{4}$/.test(d2);
        var timeOk = /^\d{2}:\d{2}:\d{2}$/.test(h1) && /^\d{2}:\d{2}:\d{2}$/.test(h2);
        if (!dateOk || !timeOk) {
            alert('<?php echo TEXT_ET_NEW_DATE_HINT; ?>');
            return;
        }

        // Ask the server for any conflicts with OTHER RCs (excluding self).
        // Reuses the same endpoint as the New popup; the server must honour
        // the `excludeId` parameter so the RC being edited is not flagged.
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'include/structure/etl/process_etl_new_check.php', true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.onreadystatechange = function() {
            if (xhr.readyState !== 4) return;
            if (xhr.status !== 200) {
                console.error('[Edit save] check HTTP', xhr.status, xhr.responseText);
                return;
            }
            var r;
            try { r = JSON.parse(xhr.responseText); }
            catch (e) {
                console.error('[Edit save] bad JSON from check:', xhr.responseText);
                return;
            }

            // Blocking conflict — same UX as the New popup.
            if (r.blocking) {
                customConfirmMessageEdit(
                    '<?php echo TEXT_ET_NEW_BLOCKING_TITLE; ?>',
                    '<?php echo TEXT_ET_NEW_BLOCKING_MSG; ?>'
                );
                return;
            }

            openEditSaveChallenge(r.conflicts || [], { d1: d1, h1: h1, d2: d2, h2: h2 });
        };
        xhr.send(JSON.stringify({
            idStation: editRcStation,
            excludeId: editRcId,
            date1: d1, heure1: h1,
            date2: d2, heure2: h2
        }));
    }


    // Simple info popup (used when the new period collides with a blocking RC)
    function customConfirmMessageEdit(title, message)
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


    function openEditSaveChallenge(conflicts, newPeriod)
    {
        conflicts = conflicts || [];
        var prev = document.getElementById('edit_save_challenge');
        if (prev) prev.remove();

        // Math challenge (same generator as New / Delete)
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

        // New period banner — strip seconds from time strings if present
        var hStart = (newPeriod && newPeriod.h1) ? newPeriod.h1.substring(0, 5) : '';
        var hEnd   = (newPeriod && newPeriod.h2) ? newPeriod.h2.substring(0, 5) : '';
        var newPeriodHtml = '';
        if (newPeriod) {
            newPeriodHtml = '<div style="margin:10px 0;padding:8px 10px;background:#eef5f8;border-left:3px solid #176B87;font-size:12px;">'
                          + '<b><?php echo TEXT_ET_NEW_CHALLENGE_NEW_PERIOD; ?></b><br>'
                          + newPeriod.d1 + ' ' + hStart + '  &rarr;  ' + newPeriod.d2 + ' ' + hEnd
                          + '</div>';
        }

        // Conflicts summary — same Before / After format as the New popup.
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
                var beforeTxt = fmtSqlDateForEdit(c.datetime_first) + ' &rarr; ' + fmtSqlDateForEdit(c.datetime_end);
                var afterTxt;
                if (c.action === 'delete') {
                    afterTxt = '<span style="color:#a32d2d;font-style:italic;"><?php echo TEXT_ET_NEW_CHALLENGE_DELETED; ?></span>';
                } else {
                    afterTxt = fmtSqlDateForEdit(c.new_first) + ' &rarr; ' + fmtSqlDateForEdit(c.new_end);
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
        overlay.id = 'edit_save_challenge';
        overlay.style.cssText =
            'position:fixed;top:0;left:0;width:100vw;height:100vh;'
          + 'background:rgba(0,0,0,0.4);z-index:3000;'
          + 'display:flex;align-items:center;justify-content:center;';
        overlay.innerHTML =
            '<div style="background:#FBF9F1;border-radius:4px;width:480px;max-width:90vw;'
          + 'box-shadow:0 8px 24px rgba(0,0,0,0.3);overflow:hidden;font-family:inherit;">'
          + '<div style="background:#176B87;color:#fff;padding:10px 14px;font-size:14px;font-weight:bold;">'
          +   '<?php echo TEXT_ET_EDIT_CONFIRM_TITLE; ?>'
          + '</div>'
          + '<div style="padding:14px 16px;font-size:13px;line-height:1.5;color:#333;">'
          +   '<?php echo TEXT_ET_EDIT_CONFIRM_MSG; ?>'
          +   newPeriodHtml
          +   conflictHtml
          +   '<div style="margin-top:14px;padding:10px;background:#fff;border:1px solid #ddd;border-radius:3px;">'
          +     '<div style="font-size:12px;color:#666;margin-bottom:6px;"><?php echo TEXT_ET_NEW_CHALLENGE_HINT; ?></div>'
          +     '<div style="display:flex;align-items:center;gap:8px;">'
          +       '<span style="font-size:16px;font-weight:bold;">' + a + ' ' + op + ' ' + b + ' = </span>'
          +       '<input id="edit_challenge_answer" type="text" style="width:60px;font-size:16px;padding:4px;" autofocus>'
          +       '<span id="edit_challenge_feedback" style="font-size:12px;"></span>'
          +     '</div>'
          +   '</div>'
          + '</div>'
          + '<div style="padding:8px 14px 14px;display:flex;justify-content:flex-end;gap:8px;">'
          +   '<button id="edit_save_cancel" class="button_close" style="width:120px;"><?php echo TEXT_BTN_CANCEL; ?></button>'
          +   '<button id="edit_save_confirm" class="button" style="width:140px;opacity:0.45;cursor:not-allowed;" disabled>'
          +     '<?php echo TEXT_BTN_SAVE; ?>'
          +   '</button>'
          + '</div>'
          + '</div>';
        document.body.appendChild(overlay);

        var input      = overlay.querySelector('#edit_challenge_answer');
        var feedback   = overlay.querySelector('#edit_challenge_feedback');
        var confirmBtn = overlay.querySelector('#edit_save_confirm');
        var cancelBtn  = overlay.querySelector('#edit_save_cancel');

        [confirmBtn, cancelBtn].forEach(function(btn) {
            btn.addEventListener('mouseenter', function() {
                if (btn.disabled) return;
                btn.style.filter = 'brightness(0.9)';
            });
            btn.addEventListener('mouseleave', function() { btn.style.filter = ''; });
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
            if (e.key === 'Enter' && !confirmBtn.disabled) { cleanup(); doSaveEditRC(conflicts); }
        }
        document.addEventListener('keydown', onKey);
        cancelBtn.addEventListener('click', cleanup);
        confirmBtn.addEventListener('click', function() { cleanup(); doSaveEditRC(conflicts); });
        setTimeout(function() { input.focus(); }, 100);
    }


    function doSaveEditRC(conflicts)
    {
        var d1 = document.getElementById('edit_date_debut_periode').value;
        var h1 = document.getElementById('edit_heure_debut_periode').value;
        var d2 = document.getElementById('edit_date_fin_periode').value;
        var h2 = document.getElementById('edit_heure_fin_periode').value;

        var ptsForServer = editCurvePoints.map(function(p) { return { h: p.h, q: p.q }; });

        // Strip conflicts down to {id, action} — same shape as the New popup.
        var conflictsForServer = (conflicts || []).map(function(c) {
            return { id: c.id, action: c.action };
        });

        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'include/structure/etl/process_etl_edit_save.php', true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.onreadystatechange = function() {
            if (xhr.readyState !== 4) return;
            if (xhr.status !== 200) {
                console.error('[Edit save] HTTP', xhr.status, xhr.responseText);
                alert('<?php echo TEXT_ET_EDIT_SAVE_ERR; ?>');
                return;
            }
            var r;
            try { r = JSON.parse(xhr.responseText); }
            catch (e) {
                console.error('[Edit save] bad JSON:', xhr.responseText);
                alert('<?php echo TEXT_ET_EDIT_SAVE_ERR; ?>');
                return;
            }
            if (r.valid_process) {
                document.getElementById('box_elt_edit').style.display = 'none';
                window.location.reload();
            } else {
                alert(r.js_text || '<?php echo TEXT_ET_EDIT_SAVE_ERR; ?>');
            }
        };
        xhr.send(JSON.stringify({
            idUser: idUser,
            todayTimeFormatted: todayTimeFormatted,
            id: editRcId,
            idStation: editRcStation,
            date1: d1, heure1: h1,
            date2: d2, heure2: h2,
            points: ptsForServer,
            conflicts: conflictsForServer
        }));
    }

</script>