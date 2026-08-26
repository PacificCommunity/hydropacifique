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
   . " style='display:none;width:92vw;max-width:1100px;height:auto;margin:2vh auto;left:4vw;background:transparent;'>\n";

    echo "<div id='cadre_view_2' style='padding:0;background-color:#FBF9F1;border-radius:4px;overflow:hidden;'>\n";

        // ---- Header ----
        echo "<div style='background:#000;color:#fff;padding:10px 16px;display:flex;align-items:center;justify-content:space-between;'>\n";
            echo "<span style='font-size:16px;font-weight:bold;'>" . TEXT_ET_NEW_TITLE . "</span>";
            echo "<span style='cursor:pointer;font-size:22px;line-height:1;' onclick=\"document.getElementById('box_elt_new').style.display='none';\">&times;</span>";
        echo "</div>\n";

        // ---- Body: two columns ----
        echo "<div style='display:grid;grid-template-columns:340px 1fr;gap:0;'>\n";

            // ===== LEFT COLUMN: controls =====
            echo "<div style='padding:14px 16px;border-right:1px solid #e0e0e0;max-height:80vh;overflow-y:auto;'>\n";

                // -- Section 1: Analysis period --
                echo "<p style='font-size:13px;font-weight:bold;margin:0 0 8px;'>"
                   . (defined('TEXT_ET_NEW_STEP1') ? TEXT_ET_NEW_STEP1 : '1. Analysis period') . "</p>";

                echo "<div style='margin-bottom:6px;'>\n";
                    echo "<label style='display:block;font-size:11px;color:#666;margin-bottom:2px;'>"
                       . TEXT_ET_POPUP_PERIOD_START . "</label>";
                    echo "<div style='display:flex;gap:4px;'>";
                        echo "<input style='width:95px;box-sizing:border-box;'"
                           . " id='new_date_debut_periode' type='text' placeholder='dd-mm-yyyy'>";
                        echo "<input style='width:75px;box-sizing:border-box;'"
                           . " id='new_heure_debut_periode' type='text' value='00:00:00'>";
                    echo "</div>";
                echo "</div>\n";

                echo "<div style='margin-bottom:8px;'>\n";
                    echo "<label style='display:block;font-size:11px;color:#666;margin-bottom:2px;'>"
                       . TEXT_ET_POPUP_PERIOD_END . "</label>";
                    echo "<div style='display:flex;gap:4px;'>";
                        echo "<input style='width:95px;box-sizing:border-box;'"
                           . " id='new_date_fin_periode' type='text' placeholder='dd-mm-yyyy'>";
                        echo "<input style='width:75px;box-sizing:border-box;'"
                           . " id='new_heure_fin_periode' type='text' value='23:59:59'>";
                    echo "</div>";
                echo "</div>\n";

                echo "<p id='new_jge_count' style='font-size:11px;color:#666;margin:0 0 14px;font-style:italic;'>—</p>";

                // -- Section 2: Model (placeholder for step B) --
                echo "<p style='font-size:13px;font-weight:bold;margin:0 0 8px;color:#999;'>"
                   . (defined('TEXT_ET_NEW_STEP2') ? TEXT_ET_NEW_STEP2 : '2. Regression model')
                   . " <span style='font-weight:normal;font-size:11px;'>(coming next)</span></p>";

                // Hidden legacy fields kept for step B/C compatibility
                echo "<input type='hidden' id='new_eq_etl' value='1'>";
                echo "<input type='hidden' id='origine_h0' value='0'>";

                // -- Section 3: Regression result (placeholder for step B) --
                echo "<p style='font-size:13px;font-weight:bold;margin:14px 0 6px;color:#999;'>"
                   . (defined('TEXT_ET_NEW_STEP3') ? TEXT_ET_NEW_STEP3 : '3. Regression result')
                   . " <span style='font-weight:normal;font-size:11px;'>(coming next)</span></p>";

                // -- Section 4: Density intervals (placeholder for step B) --
                echo "<p style='font-size:13px;font-weight:bold;margin:14px 0 6px;color:#999;'>"
                   . (defined('TEXT_ET_NEW_STEP4') ? TEXT_ET_NEW_STEP4 : '4. Density intervals')
                   . " <span style='font-weight:normal;font-size:11px;'>(coming next)</span></p>";

                $defaults = [['0','100','10'],['110','200','20'],['250','500','50'],['550','1000','100']];
                foreach ($defaults as $i => $d) {
                    $n = $i + 1;
                    echo "<input type='hidden' id='inf_{$n}' value='{$d[0]}'>";
                    echo "<input type='hidden' id='sup_{$n}' value='{$d[1]}'>";
                    echo "<input type='hidden' id='interv_{$n}' value='{$d[2]}'>";
                }

                // -- Section 5: Conflicts (placeholder for step C) --
                echo "<p style='font-size:13px;font-weight:bold;margin:14px 0 6px;color:#999;'>"
                   . TEXT_ET_NEW_STEP5
                   . " <span style='font-weight:normal;font-size:11px;'>(coming next)</span></p>";

            echo "</div>\n";

            // ===== RIGHT COLUMN: preview chart =====
            echo "<div style='padding:14px 16px;display:flex;flex-direction:column;'>\n";

                echo "<div style='display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;'>\n";
                    echo "<span style='font-size:13px;font-weight:bold;'>"
                       . (defined('TEXT_ET_NEW_PREVIEW_TITLE') ? TEXT_ET_NEW_PREVIEW_TITLE : 'Preview') . "</span>";
                    echo "<span id='new_preview_hint' style='font-size:11px;color:#666;'></span>";
                echo "</div>\n";

                echo "<div id='new_preview_plot' style='flex:1;min-height:420px;background:#fff;border:1px solid #e0e0e0;border-radius:3px;'></div>\n";

                echo "<div style='display:flex;justify-content:flex-end;gap:8px;margin-top:14px;'>\n";
                    echo "<input type='button' class='button_close' style='width:120px;' value='"
                       . (defined('TEXT_BTN_CANCEL') ? TEXT_BTN_CANCEL : 'Annuler') . "'"
                       . " onclick=\"document.getElementById('box_elt_new').style.display='none';\">";
                    echo "<input type='submit' class='button' style='width:160px;' name='new_etl' id='new_etl' value='"
                       . TEXT_ET_BTN_NEW . "' disabled title='"
                       . (defined('TEXT_ET_NEW_DISABLED_HINT') ? TEXT_ET_NEW_DISABLED_HINT : 'Available after step B')
                       . "'>";
                echo "</div>\n";

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
                'Renseignez deux dates au format dd-mm-yyyy.';
            Plotly.purge('new_preview_plot');
            return;
        }

        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'process/etl/process_etl_new_preview.php', true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.onreadystatechange = function() {
            if (xhr.readyState !== 4 || xhr.status !== 200) return;
            try { var r = JSON.parse(xhr.responseText); } catch(e) { return; }

            var hint = document.getElementById('new_jge_count');
            if (r.nb_jge < 2) {
                hint.style.color = '#a32d2d';
                hint.textContent = r.nb_jge + ' point' + (r.nb_jge > 1 ? 's' : '')
                                 + ' de jaugeage — au moins 2 sont nécessaires pour calibrer une courbe.';
            } else {
                hint.style.color = '#666';
                hint.textContent = r.nb_jge + ' points de jaugeage trouvés sur la période.';
            }

            newPreviewRender(r);
        };

        xhr.send(JSON.stringify({
            idStation: idStation,
            date1:     d1, heure1: h1,
            date2:     d2, heure2: h2
        }));
    }

    function newPreviewRender(r)
    {
        if (!r.points || r.points.length === 0) {
            Plotly.purge('new_preview_plot');
            return;
        }

        var swapAxes = document.getElementById('swap_axes')
                     ? document.getElementById('swap_axes').checked : false;

        var xs = r.points.map(function(p) { return swapAxes ? p.q : p.h; });
        var ys = r.points.map(function(p) { return swapAxes ? p.h : p.q; });
        var ds = r.points.map(function(p) { return p.date; });

        var unitH = '<?php echo defined("TEXT_ET_COORD_UNIT_H") ? TEXT_ET_COORD_UNIT_H : "cm"; ?>';
        var unitQ = '<?php echo defined("TEXT_ET_COORD_UNIT_Q") ? TEXT_ET_COORD_UNIT_Q : "m³/s"; ?>';
        var phH = swapAxes ? '%{y:.2f}' : '%{x:.2f}';
        var phQ = swapAxes ? '%{x:.2f}' : '%{y:.2f}';

        var trace = {
            x: xs, y: ys, customdata: ds,
            mode: 'markers', type: 'scatter',
            marker: { size: 11, symbol: 'star', color: '#185FA5', line: { color: 'black', width: 1 } },
            hovertemplate: '<b>JGE</b>'
                         + '<br><span style="font-size:9px;color:#aaa">────────────</span>'
                         + '<br><b>Date :</b> %{customdata}'
                         + '<br><b>H :</b> ' + phH + ' ' + unitH
                         + '<br><b>Q :</b> ' + phQ + ' ' + unitQ
                         + '<extra></extra>'
        };

        var titleH = '<?php echo TEXT_ET_AXIS_H; ?>';
        var titleQ = '<?php echo TEXT_ET_AXIS_Q; ?>';

        var layout = {
            xaxis: { title: { text: swapAxes ? titleQ : titleH, standoff: 15 }, autorange: true },
            yaxis: { title: { text: swapAxes ? titleH : titleQ, standoff: 15 }, autorange: true },
            hovermode: 'closest',
            hoverlabel: { bgcolor: '#fff', bordercolor: '#888', font: { size: 12, color: '#000' }, align: 'left' },
            margin: { l: 60, r: 15, t: 15, b: 45 },
            showlegend: false
        };

        var config = {
            responsive: true, displaylogo: false,
            modeBarButtonsToRemove: ['select2d', 'lasso2d', 'autoScale2d']
        };

        Plotly.newPlot('new_preview_plot', [trace], layout, config);
    }

    ['new_date_debut_periode','new_heure_debut_periode',
     'new_date_fin_periode','new_heure_fin_periode'].forEach(function(id) {
        var el = document.getElementById(id);
        if (el) {
            el.addEventListener('input',  newPreviewSchedule);
            el.addEventListener('change', newPreviewSchedule);
        }
    });
</script>