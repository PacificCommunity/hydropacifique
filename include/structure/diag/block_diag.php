<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Well log comparison popup — overlaid panel included by data_diag_piezo.php.
Visually aligned on the ETL "New rating curve" popup:
- Draggable + resizable container (the platform CSS turns the first
   direct child of #cadre_view_2 into the teal header bar).
- Two-column grid body: left = selection list, right = chart.
- Top-right action bar above the chart: Refresh chart + Cancel.

Left column : station/diagraphy selection list (loaded via AJAX → process_diag_tab.php).
Right column: Plotly chart (loaded via AJAX → process_diag_graph.php).
----------------------------------------
*/

echo "<div id='box_diag' class='block_view'"
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
        echo "<div id='box_diag_header'"
           . " style='cursor:move;user-select:none;flex-shrink:0;'>\n";
            echo "<span>" . TEXT_DG_POPUP_TITLE . "</span>";
            echo "<span id='button_close' onclick=\"document.getElementById('box_diag').style.display='none';\""
               . " title='" . TEXT_DG_POPUP_CLOSE . "'>&times;</span>";
        echo "</div>\n";

        // ---- Body: two columns (grid track height constrained to popup height minus header) ----
        echo "<div style='display:grid;grid-template-columns:380px 1fr;gap:0;flex:1 1 0;min-height:0;overflow:hidden;'>\n";

            // ===== LEFT COLUMN: selection list (only this one scrolls vertically) =====
            // overflow-x:hidden — long station names wrap inside the
            // column instead of triggering a horizontal scrollbar.
            echo "<div id='cadre_liste' style='padding:14px 16px;border-right:1px solid #e0e0e0;overflow-y:auto;overflow-x:hidden;min-height:0;height:100%;box-sizing:border-box;word-break:break-word;'>\n";

                echo "<p style='font-size:13px;font-weight:bold;margin:0 0 8px;'>"
                   . TEXT_DG_LIST_TITLE . "</p>";

                // Holds the per-station accordion sections (loaded via AJAX)
                echo "<div id='cadre_data_station_lgt' style='display:none;'></div>\n";

                // Loading spinner (tab) — pure-CSS .spinner (no wait.gif)
                echo "<div id='wait_tab' style='width:100%;margin-top:80px;text-align:center;'>";
                    echo "<div class='spinner' style='margin:0 auto;display:block;width:34px;height:34px;'></div>";
                    echo "<p style='margin-top:14px;'>" . TEXT_DG_LOADING . "</p>";
                echo "</div>\n";

            echo "</div>\n";

            // ===== RIGHT COLUMN: chart (never scrolls, plot shrinks instead) =====
            echo "<div id='cadre_graph' style='padding:14px 16px;display:flex;flex-direction:column;min-height:0;overflow:hidden;'>\n";

                // ---- Top action bar (normal mode) ----
                // Visible in normal mode. In edit mode it is hidden and
                // replaced by #edit_action_bar below.
                // Layout: [ Refresh chart ] [ Edit ] (left) ... [ Cancel ] (right)
                echo "<div id='diag_action_bar' style='display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;flex-shrink:0;gap:8px;'>\n";
                    echo "<div style='display:flex;gap:8px;'>\n";
                        echo "<input type='button' class='button' style='width:160px;' value='"
                           . TEXT_DG_BTN_REFRESH . "' onclick='load_graph_diag();'>";
                        echo "<input type='button' class='button_graph' style='width:120px;' id='diag_edit_btn' value='"
                           . TEXT_DG_BTN_EDIT . "' onclick='enterEditMode();'>";
                    echo "</div>\n";
                    echo "<input type='button' class='button_close' style='width:120px;' value='"
                       . TEXT_BTN_CANCEL . "'"
                       . " onclick=\"document.getElementById('box_diag').style.display='none';\">";
                echo "</div>\n";

                // ---- Top action bar (edit mode) ----
                // Replaces #diag_action_bar while editing.
                // Layout: [Editing : <dropdown>] [Cancel edit] (left) ... [Save] (right)
                //
                // The dropdown is a custom widget (not a native <select>)
                // because Chrome/Edge don't honour background-color on
                // <option> elements when the select is collapsed. The
                // custom widget paints each entry with the matching
                // pastel tint — same convention as the left-column rows.
                echo "<div id='edit_action_bar' style='display:none;align-items:center;justify-content:space-between;margin-bottom:8px;flex-shrink:0;gap:8px;flex-wrap:wrap;'>\n";
                    echo "<div style='display:flex;align-items:center;gap:8px;font-size:12px;'>\n";
                        echo "<span style='font-weight:bold;color:#176B87;'>" . TEXT_DG_EDIT_EDITING . " :</span>";
                        echo "<div id='edit_target_dd' class='diag-dd'>\n";
                            echo "<button type='button' id='edit_target_dd_btn' class='diag-dd-trigger' aria-haspopup='listbox' aria-expanded='false'>\n";
                                echo "<span class='diag-dd-label'></span>";
                                echo "<span class='diag-dd-caret'>&#9662;</span>";
                            echo "</button>\n";
                            echo "<ul id='edit_target_dd_menu' class='diag-dd-menu' role='listbox' hidden></ul>\n";
                        echo "</div>\n";
                        echo "<input type='button' class='button_close' style='width:140px;' value='"
                           . TEXT_DG_BTN_CANCEL_EDIT . "' onclick='attemptCancelEdit();'>";
                    echo "</div>\n";
                    echo "<input type='button' class='button' style='width:120px;' value='"
                       . TEXT_BTN_SAVE . "' onclick='attemptSaveEdit();'>";
                echo "</div>\n";

                // The Plotly chart fills the remaining space.
                echo "<div id='plotDiag' style='flex:1;min-height:0;background:#fff;border:1px solid #e0e0e0;border-radius:3px;display:none;'></div>\n";

                // ---- Hint line under the chart (edit mode only) ----
                // Same pattern as the ETL popups: a single line listing
                // the three interactive gestures available on the plot.
                echo "<div id='edit_hint_line' style='display:none;margin-top:6px;font-size:11px;color:#666;flex-shrink:0;'>"
                   . TEXT_DG_EDIT_HINT_DRAG
                   . " &middot; "
                   . TEXT_DG_EDIT_HINT_RDEL
                   . " &middot; "
                   . TEXT_DG_EDIT_HINT_RADD
                   . "</div>\n";

                // Loading spinner (graph) — pure-CSS .spinner (no wait.gif)
                echo "<div id='wait_graph' style='width:100%;margin-top:80px;text-align:center;'>";
                    echo "<div class='spinner' style='margin:0 auto;display:block;width:34px;height:34px;'></div>";
                    echo "<p style='margin-top:14px;'>" . TEXT_DG_LOADING . "</p>";
                echo "</div>\n";

            echo "</div>\n";

        echo "</div>\n"; // end grid body
    echo "</div>\n";     // end cadre_view_2
echo "</div>\n";         // end box_diag
?>

<style>
    /* =============================================
       Edit-mode custom dropdown (block_diag.php)
       --------------------------------------------
       Replaces the native <select> for the "Editing : ..."
       picker so each item can carry its pastel tint (the
       same one used in the left-column rows and as the
       trace colour on the chart). Plain <option> elements
       don't honour background-color reliably on Chromium,
       so we paint our own widget.
       ============================================= */

    .diag-dd { position: relative; display: inline-block; min-width: 240px; }

    .diag-dd-trigger {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        width: 100%;
        padding: 5px 10px;
        font-family: 'Open Sans', Arial, sans-serif;
        font-size: 12px;
        color: #2c2c2a;
        background-color: #fff;
        border: 1px solid #d4d8dd;
        border-radius: 4px;
        cursor: pointer;
        transition: border-color 0.15s, box-shadow 0.15s, background-color 0.2s;
    }
    .diag-dd-trigger:hover  { border-color: #b8bfc6; }
    .diag-dd-trigger:focus  { outline: none; border-color: #176B87; box-shadow: 0 0 0 2px rgba(23,107,135,0.15); }

    .diag-dd-label { flex: 1; text-align: left; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .diag-dd-caret { color: #888; font-size: 10px; flex-shrink: 0; }

    /* Menu — absolute below the trigger */
    .diag-dd-menu {
        position: absolute;
        top: calc(100% + 4px);
        left: 0;
        right: 0;
        min-width: 100%;
        max-height: 240px;
        overflow-y: auto;
        margin: 0;
        padding: 4px 0;
        list-style: none;
        background-color: #fff;
        border: 1px solid #d4d8dd;
        border-radius: 4px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.10);
        z-index: 3001;
    }

    .diag-dd-menu li {
        padding: 6px 10px;
        font-size: 12px;
        color: #2c2c2a;
        cursor: pointer;
        transition: filter 0.12s;
        border-bottom: 1px solid rgba(0,0,0,0.04);
    }
    .diag-dd-menu li:last-child { border-bottom: none; }
    .diag-dd-menu li:hover      { filter: brightness(0.94); }
    .diag-dd-menu li.is-active  { font-weight: 700; box-shadow: inset 3px 0 0 #176B87; }


    /* =============================================
       Delete (×) button per well log
       --------------------------------------------
       The .diag-cell-del cells sit right of the checkbox in the
       left-column table. Hover paints them red as a danger
       affordance. When the chart is in edit mode, the container
       gets .diag-edit-locked which greys out and disables them
       entirely (no clicks, no hover).
       ============================================= */
    .diag-cell-del:hover {
        background-color: #a32d2d;
        color: #fff !important;
        border-radius: 3px;
    }
    .diag-edit-locked .diag-cell-del {
        color: #c8c8c8 !important;
        cursor: not-allowed !important;
        pointer-events: none;
    }
    .diag-edit-locked .diag-cell-del:hover {
        background-color: transparent;
        color: #c8c8c8 !important;
    }
</style>

<script type="text/javascript">

    // ---- Popup lifecycle ----
    // The popup is opened from data_diag_piezo.php (load_data_diag).
    // Closing is handled here: X button, Escape key, click outside
    // the panel. All three set #box_diag display to none.

    (function() {

        var boxDiag = document.getElementById('box_diag');
        var plotDiv = document.getElementById('plotDiag');

        // Keep the Plotly chart responsive when the panel is resized
        var resizeObserver = new ResizeObserver(function() {
            if (plotDiv && plotDiv.data) {
                Plotly.relayout(plotDiv, { autosize: true });
            }
        });
        if (plotDiv) { resizeObserver.observe(plotDiv); }

        // Close on Escape
        document.addEventListener("keydown", function(event) {
            if (event.key === "Escape" && boxDiag && boxDiag.style.display !== 'none') {
                boxDiag.style.display = "none";
            }
        });

    })();


    // ---- Draggable header ----
    // Mirrors the ETL popups: hold the header bar and drag the panel
    // around. The body coords are clamped to the viewport so the
    // header can never be pushed off-screen and become unclickable.

    (function() {

        var box    = document.getElementById('box_diag');
        var header = document.getElementById('box_diag_header');
        if (!box || !header) return;

        var dragging = false;
        var offX = 0, offY = 0;

        header.addEventListener('mousedown', function(e) {
            // Ignore clicks on the close button (it lives inside the header)
            if (e.target && e.target.id === 'button_close') return;
            dragging = true;
            var rect = box.getBoundingClientRect();
            offX = e.clientX - rect.left;
            offY = e.clientY - rect.top;
            e.preventDefault();
        });

        document.addEventListener('mousemove', function(e) {
            if (!dragging) return;
            var newLeft = e.clientX - offX;
            var newTop  = e.clientY - offY;
            // Clamp inside the viewport (leave a small margin so the
            // panel can always be grabbed back)
            var maxLeft = window.innerWidth  - 80;
            var maxTop  = window.innerHeight - 30;
            if (newLeft < -box.offsetWidth + 80) { newLeft = -box.offsetWidth + 80; }
            if (newTop  < 0)                     { newTop  = 0; }
            if (newLeft > maxLeft)               { newLeft = maxLeft; }
            if (newTop  > maxTop)                { newTop  = maxTop; }
            box.style.left = newLeft + 'px';
            box.style.top  = newTop  + 'px';
        });

        document.addEventListener('mouseup', function() {
            dragging = false;
        });

    })();

</script>