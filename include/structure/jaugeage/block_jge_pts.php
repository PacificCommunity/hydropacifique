<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Gauging measurement points entry popup
- Draggable popup for entering velocity/depth data per vertical
- Supports 3 input modes: TOPs count, TOPs/sec, or direct velocity
----------------------------------------
*/

require(DIR_WS_JAUGEAGE  . 'block_jge_helice.php');        // Propeller info popup

// Semi-transparent backdrop: blocks interaction with the page behind the
// popup. Shown/hidden together with the popup (see open/close handlers).
echo "<div id='overlay_jge_pts'
            style='display:none;position:fixed;top:0;left:0;width:100%;height:100%;
                   background:rgba(0,0,0,0.45);z-index:1500;'></div>\n";

echo "<div id='box_jge_pts' class='block_view'
            style='position:absolute;width:95%;max-width:1800px;height:92vh;top:2vh;left:2.5%;background:none;
                    display:none;flex-direction:column;overflow:hidden;z-index:1501;
                    min-width:760px;min-height:380px;'>\n";    echo "<div id='cadre_view_2' style='height:100%;padding:0px;margin:0;
                                        display:flex;flex-direction:column;flex:1;overflow:hidden;'>\n";

        echo "<div id='title_box_jge'
                style='float:left;width:100%;padding:15px 0;
                       font-size:16px;font-weight:bold;
                       color:#000;background-color:#f5f5f5;
                       flex-shrink:0;'>";

            echo "<span id='title_box_span' style='margin-left:15px;'>" . TEXT_JGE_PTS_TITLE . "</span>";

            echo "<div id='button_titre'
                        style='float:left;padding:15px 40px;font-size:16px;'
                        title='" . TEXT_JGE_PTS_CALC_TITLE . "'
                        onClick='maj_JgePts();'>\n";
                echo TEXT_JGE_PTS_CALC_BTN;
            echo "</div>\n";


            echo "<span id='button_close' style='float:right;margin-right:15px;cursor:pointer;' title='" . TEXT_JGE_PTS_CLOSE . "'>X</span>";

        echo "</div>\n";


        
        echo "<div id='cadre_option' style='padding:8px 6px;margin-top:0px;
                                            display:flex;flex-direction:column;flex:1;overflow:hidden;'>";

            // -----------------------------------------------
            // Top bar in two columns aligned with the split below:
            //   LEFT  (table width)  : "Data to enter" + selector, calc button
            //                          aligned on the table's right edge.
            //   RIGHT (graph width)  : live computed results panel.

            echo "<div style='display:flex;flex-direction:row;gap:6px;margin:0 2px;flex-shrink:0;'>\n";

                // ---- LEFT column: selector + calc button (table width) ----
                echo "<div style='flex:1 1 45%;min-width:0;display:flex;align-items:flex-start;'>\n";

                    echo "<div id='boite_small' style='width:210px;margin-right:40px;'>\n";
                        echo "<span style='font-size:12px;font-weight:bold;'>" . TEXT_JGE_PTS_INPUT_LABEL . "</span>";
                        echo "<select name='select_saisie' id='select_saisie' style='width:200px;' onchange='toggleFieldsJge();'>";
                            echo "<option value='1'>" . TEXT_JGE_PTS_OPT_TOPS     . "</option>";
                            echo "<option value='2'>" . TEXT_JGE_PTS_OPT_TOPS_SEC . "</option>";
                            echo "<option value='3'>" . TEXT_JGE_PTS_OPT_VITESSE  . "</option>";
                        echo "</select>";
                    echo "</div>";

                    // -----------------------------------------------
                    // Equipment fields (shared, fixed ids).
                    // The popup edits ONE arm at a time. These controls use
                    // fixed ids (no per-arm suffix); their values are loaded
                    // from / written back to the per-arm hidden fields in
                    // form_jge_bras.php (select_moulinet_<bras> etc.) via
                    // loadBrasEquipment() / saveBrasEquipment(). Options are
                    // rendered without 'selected'; the active arm's value is
                    // applied on open.

                    // Current meter (moulinet)
                    echo "<div id='boite_small' style='width:160px;'>\n";
                        echo "<span style='font-size:12px;font-weight:bold;'>" . TEXT_JGE_BRAS_MOULINET . "</span>";
                        echo "<select id='select_moulinet_pts' style='width:140px;' '>";
                            echo "<option value='0'>-</option>";
                            if (isset($moulinet_array))
                            {
                                foreach ($moulinet_array as $key => $value)
                                {
                                    echo "<option value='" . $key . "'>" . $moulinet_array[$key]['num'] . "</option>";
                                }
                            }
                        echo "</select>";
                    echo "</div>\n";

                    // Helical flowmeter
                    echo "<div id='box_helice_pts' style='width:160px;border-radius:4px;'>\n";

                        echo "<div id='boite_small' style=''>\n";
                            echo "<span style='font-size:12px;font-weight:bold;'>" . TEXT_JGE_BRAS_HELICE . "</span>";
                            // Info button: opens the velocity-equation detail popup.
                            // Inline SVG (no image asset) with a hover colour change.
                            echo "<span class='jge_info_btn' title='" . TEXT_JGE_HELICE_INFO_TITLE . "' onclick='view_helice_eq();'>"
                               . "<svg viewBox='0 0 24 24' width='14' height='14' fill='none' xmlns='http://www.w3.org/2000/svg' aria-hidden='true'>"
                               . "<circle cx='12' cy='12' r='9' stroke='currentColor' stroke-width='2'/>"
                               . "<line x1='12' y1='11' x2='12' y2='16.5' stroke='currentColor' stroke-width='2' stroke-linecap='round'/>"
                               . "<circle cx='12' cy='7.5' r='1.25' fill='currentColor'/>"
                               . "</svg>"
                               . "</span>";
                            echo "<select id='select_helice_pts' style='width:140px;' '>";
                                echo "<option value='0'>-</option>";
                                if (isset($helice_array))
                                {
                                    foreach ($helice_array as $key => $value)
                                    {
                                        echo "<option value='" . $key . "'>" . $helice_array[$key]['num'] . " - " . $helice_array[$key]['fabricant'] . "</option>";
                                    }
                                }
                            echo "</select>";
                        echo "</div>\n";

                    echo "</div>\n";

                    // Rod diameter
                    if(INIT_T === 'NC')
                    {
                        echo "<div id='boite_small' style='width:140px;'>\n";
                            echo "<p style='font-size:12px;font-weight:bold;' title='" . TEXT_JGE_BRAS_PERCHE_TITLE . "'>" . TEXT_JGE_BRAS_PERCHE_LABEL . "</p>";
                            echo "<input type='text' style='float:left;width:30px;' id='perche_diam_pts' value='' onChange='saveBrasEquipment();'>\n";
                        echo "</div>\n";
                    }

                echo "</div>\n";
                

                // ---- RIGHT column: live computed results (graph width) ----
                echo "<div style='flex:1 1 55%;min-width:0;'>\n";

                    echo "<div id='popup_results_panel'
                                style='display:grid;grid-template-rows:repeat(2, auto);grid-auto-flow:column;
                                       grid-auto-columns:minmax(140px, max-content);gap:3px 18px;
                                       justify-content:end;width:100%;padding-right:10px;box-sizing:border-box;
                                       overflow-x:auto;
                                       background-color:#fff;'>\n";

                        $popup_result_cell = function($id, $label, $colour = '#000')
                        {
                            echo "<div style='display:flex;align-items:center;justify-content:space-between;gap:6px;'>\n";
                                echo "<span style='font-weight:bold;font-size:11px;color:" . $colour . ";white-space:nowrap;'>" . $label . "</span>";
                                echo "<input type='text' readonly style='width:44px;height:12px;font-size:11px;
                                             border:1px solid #ddd;background-color:#FFFFDD;flex-shrink:0;'
                                             id='popup_" . $id . "' value=''>\n";
                            echo "</div>\n";
                        };

                        $popup_result_cell('depouil_bras_q',         TEXT_JGE_BRAS_Q_LABEL,        '#930000');
                        $popup_result_cell('depouil_bras_hmoy',      TEXT_JGE_BRAS_HMOY_LABEL,     '#930000');
                        $popup_result_cell('depouil_bras_vmoy',      TEXT_JGE_BRAS_VMOY_LABEL);
                        $popup_result_cell('depouil_bras_vsurf',     TEXT_JGE_BRAS_VSURF_LABEL);
                        $popup_result_cell('depouil_bras_surfmouil', TEXT_JGE_BRAS_SURFMOUIL_LABEL);
                        $popup_result_cell('depouil_bras_perimouil', TEXT_JGE_BRAS_PERIMOUIL_LABEL);
                        $popup_result_cell('depouil_bras_profmoy',   TEXT_JGE_BRAS_PROFMOY_LABEL);
                        $popup_result_cell('depouil_bras_distmax',   TEXT_JGE_BRAS_DISTMAX_LABEL);
                        $popup_result_cell('depouil_bras_rh',        TEXT_JGE_BRAS_RH_LABEL);

                    echo "</div>\n";

                echo "</div>\n";

            echo "</div>\n";
            

            // -----------------------------------------------
            // Split layout: data table (left) + live cross-section graph (right)

            echo "<div id='cadre_limit' style='margin-top:5px;padding:0;
                                                display:flex;flex-direction:row;flex:1;overflow:hidden;gap:10px;'>";

                // ---- LEFT: data table. Fixed width = its natural size, so the
                //      table never needs a horizontal scrollbar; the graph on
                //      the right takes whatever space remains. ----
                echo "<div id='jge_pts_table_pane'
                            style='flex:0 0 auto;width:760px;display:flex;flex-direction:column;
                                   padding:6px;overflow:hidden;'>";

                    echo "<div style='flex-shrink:0;'>\n";
                        echo "<table id='table_tri' cellspacing='0' >";
                            echo "<thead>";
                                echo "<tr class='header-row'>";
                                    echo "<th style='width:65px;height:25px;padding:0;color:#000;font-size:11px;border-bottom:1px solid #000;border-top:1px solid #000;' title='" . TEXT_JGE_PTS_COL_VERT_TITLE . "'>"    . TEXT_JGE_PTS_COL_VERT    . "</th>";
                                    echo "<th style='width:65px;padding:0;color:#000;font-size:11px;border-bottom:1px solid #000;border-top:1px solid #000;' title='" . TEXT_JGE_PTS_COL_DIST_TITLE . "'>"                . TEXT_JGE_PTS_COL_DIST    . "</th>";
                                    echo "<th style='width:65px;padding:0;color:#000;font-size:11px;border-bottom:1px solid #000;border-top:1px solid #000;' title='" . TEXT_JGE_PTS_COL_PROFMAX_TITLE . "'>"             . TEXT_JGE_PTS_COL_PROFMAX . "</th>";
                                    echo "<th style='width:70px;padding:0;color:#000;font-size:11px;border-bottom:1px solid #000;border-top:1px solid #000;' title='" . TEXT_JGE_PTS_COL_PROFMESURE_TITLE . "'>"          . TEXT_JGE_PTS_COL_PROFMESURE . "</th>";
                                    echo "<th style='width:70px;padding:0;color:#000;font-size:11px;border-bottom:1px solid #000;border-top:1px solid #000;' title='" . TEXT_JGE_PTS_COL_TOPS_TITLE . "'>"                . TEXT_JGE_PTS_COL_TOPS    . "</th>";
                                    echo "<th style='width:70px;padding:0;color:#000;font-size:11px;border-bottom:1px solid #000;border-top:1px solid #000;' title='" . TEXT_JGE_PTS_COL_TEMPS_TITLE . "'>"               . TEXT_JGE_PTS_COL_TEMPS   . "</th>";
                                    echo "<th style='width:70px;padding:0;color:#000;font-size:11px;border-bottom:1px solid #000;border-top:1px solid #000;' title='" . TEXT_JGE_PTS_COL_TOPS_SEC_TITLE . "'>"            . TEXT_JGE_PTS_COL_TOPS_SEC . "</th>";
                                    echo "<th style='width:70px;padding:0;color:#000;font-size:11px;border-bottom:1px solid #000;border-top:1px solid #000;' title='" . TEXT_JGE_PTS_COL_VITESSE_TITLE . "'>"             . TEXT_JGE_PTS_COL_VITESSE . "</th>";
                                    echo "<th style='width:170px;padding:0;color:#000;font-size:11px;border-bottom:1px solid #000;border-top:1px solid #000;'>" . TEXT_JGE_PTS_COL_OBS     . "</th>";
                                    echo "<th style='width:40pxpadding:0;color:#000;text-align:center;border-bottom:1px solid #000;border-top:1px solid #000;'></th>";
                                echo "</tr>";
                            echo "</thead>";
                        echo "</table>";
                    echo "</div>";

                    echo "<div id='table_content_pts' style='flex:1;overflow-y:auto;overflow-x:hidden;margin-top:5px;'>\n";
                    echo "</div>";

                echo "</div>";

                // ---- RIGHT: live cross-section graph, takes remaining space ----
                echo "<div id='jge_pts_graph_pane'
                            style='flex:1 1 auto;min-width:0;display:flex;flex-direction:column;
                                   padding:6px 6px 6px 0;'>";

                    echo "<div id='plot_jge_popup'
                                style='flex:1;min-height:0;padding:12px;border:1px solid #000;border-radius:4px;background-color:#fff;'></div>";

                echo "</div>";

            echo "</div>\n";

        echo "</div>\n";
        

    echo "</div>\n";

echo "</div>\n";


// -----------------------------------------------
// Close-confirmation dialog (3 choices): close without saving / calculate
// then close / cancel. Sits above the points popup (z-index 1502).

echo "<div id='box_confirm_close_jge'
            style='display:none;position:fixed;top:0;left:0;width:100%;height:100%;
                   background:rgba(0,0,0,0.45);z-index:1502;
                   align-items:center;justify-content:center;'>\n";

    echo "<div style='width:520px;max-width:92%;background:#fff;border-radius:6px;
                       box-shadow:0 4px 24px rgba(0,0,0,0.3);overflow:hidden;'>\n";

        echo "<p style='margin:0;padding:15px 20px;font-size:16px;font-weight:bold;
                        color:#fff;background-color:#930000;'>"
            . TEXT_JGE_PTS_CONFIRM_TITLE . "</p>\n";

        echo "<div style='padding:20px;'>\n";
            echo "<p style='margin:0 0 20px 0;font-size:14px;line-height:1.5;'>"
                . TEXT_JGE_PTS_CONFIRM_CLOSE . "</p>\n";

            echo "<div style='display:flex;flex-wrap:nowrap;gap:10px;justify-content:flex-end;'>\n";
                echo "<button type='button' id='confirm_cancel'      class='btn_confirm btn_neutral_outline'>" . TEXT_JGE_PTS_CONFIRM_CANCEL      . "</button>";
                echo "<button type='button' id='confirm_close_only'  class='btn_confirm btn_danger_outline'>"  . TEXT_JGE_PTS_CONFIRM_CLOSE_ONLY  . "</button>";
                echo "<button type='button' id='confirm_calc_close'  class='btn_confirm btn_success_outline'>" . TEXT_JGE_PTS_CONFIRM_CALC_CLOSE  . "</button>";
            echo "</div>\n";
        echo "</div>\n";

    echo "</div>\n";

echo "</div>\n";
?>

<style>

    .btn_confirm {
        font-size: 13px;
        font-weight: bold;
        height: 36px;
        padding: 0 16px;
        border-radius: 4px;
        border: 1px solid;
        background-color: #fff;
        cursor: pointer;
        white-space: nowrap;
        box-sizing: border-box;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: background-color 0.15s, color 0.15s;
    }
    .btn_neutral_outline { color: #444; border-color: #999; }
    .btn_neutral_outline:hover { background-color: #444; color: #fff; }

    .btn_danger_outline { color: #930000; border-color: #930000; }
    .btn_danger_outline:hover { background-color: #930000; color: #fff; }

    .btn_success_outline { color: #0f6e56; border-color: #1d9e75; }
    .btn_success_outline:hover { background-color: #1d9e75; color: #fff; }

    /* Info button next to the propeller select (opens the equation popup) */
    .jge_info_btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #176B87;
        cursor: pointer;
        vertical-align: middle;
        margin: 0 4px;
        transition: color 0.15s, transform 0.15s;
    }
    .jge_info_btn:hover { color: #930000; transform: scale(1.12); }
</style>

<script>

    

    document.addEventListener('DOMContentLoaded', function()
    {
        const selectSaisie  = document.getElementById('select_saisie');
        const boxJgePts     = document.getElementById('box_jge_pts');
        const overlayJgePts = document.getElementById('overlay_jge_pts');
        const boxConfirm    = document.getElementById('box_confirm_close_jge');

        // Restore previously saved input mode
        const defaultValue = menuStates['jge-pts-saisie'];
        if (defaultValue) { selectSaisie.value = defaultValue; }

        // Initialize Select2 on the shared equipment dropdowns. They keep
        // fixed ids; their value is set per arm on open (loadBrasEquipment).
        $('#select_moulinet_pts').select2({ placeholder: '<?php echo TEXT_JGE_BRAS_MOULINET_PLACEHOLDER; ?>', allowClear: true });
        $('#select_moulinet_pts').on('change', function() { saveBrasEquipment(); });

        $('#select_helice_pts').select2({ placeholder: '<?php echo TEXT_JGE_BRAS_HELICE_PLACEHOLDER; ?>', allowClear: true });
        $('#select_helice_pts').on('change', function() { helice_eq(); saveBrasEquipment(); });

        // Actually hide the points popup + backdrop.
        function hideJgePts()
        {
            boxJgePts.style.display = "none";
            if (overlayJgePts) { overlayJgePts.style.display = "none"; }
            if (boxConfirm)    { boxConfirm.style.display    = "none"; }
        }

        // Ask before closing only if the table was modified; otherwise close.
        function askCloseJgePts()
        {
            if (typeof jgePtsDirty !== 'undefined' && !jgePtsDirty)
            {
                hideJgePts();
                return;
            }
            if (boxConfirm) { boxConfirm.style.display = "flex"; }
            else            { hideJgePts(); }
        }

        // X button or click on the backdrop overlay -> ask first.
        document.addEventListener("click", function(event)
        {
            if (event.target.id === 'button_close' || event.target === overlayJgePts)
            {
                askCloseJgePts();
            }

            // --- confirmation dialog buttons ---
            if (event.target.id === 'confirm_cancel')
            {
                if (boxConfirm) { boxConfirm.style.display = "none"; }
            }
            else if (event.target.id === 'confirm_close_only')
            {
                hideJgePts();
            }
            else if (event.target.id === 'confirm_calc_close')
            {
                // Validate + compute flow, then close.
                if (typeof maj_JgePts === 'function') { maj_JgePts(); }
                hideJgePts();
            }
        });

        document.addEventListener("keydown", function(event)
        {
            if (event.key !== "Escape") { return; }

            // Escape closes the confirmation dialog first if it is open,
            // otherwise it asks to close the points popup.
            if (boxConfirm && boxConfirm.style.display === 'flex')
            {
                boxConfirm.style.display = "none";
            }
            else if (boxJgePts.style.display !== 'none' && boxJgePts.style.display !== '')
            {
                askCloseJgePts();
            }
        });

        // Keep the popup cross-section graph fitted when the popup is resized
        // or dragged.
        var plotPopup = document.getElementById('plot_jge_popup');
        if (plotPopup && typeof ResizeObserver !== 'undefined')
        {
            var roPopup = new ResizeObserver(function()
            {
                if (plotPopup.data && typeof Plotly !== 'undefined')
                {
                    Plotly.relayout(plotPopup, { autosize: true });
                }
            });
            roPopup.observe(plotPopup);
        }
    });



    // -----------------------------------------------
    // Enable/disable fields based on selected input mode

    function toggleFieldsJge()
    {
        const selectedValue = document.getElementById('select_saisie').value;

        const topsFields    = document.querySelectorAll("input[name^='jge_bra_nbtour']");
        const tempsFields   = document.querySelectorAll("input[name^='jge_bra_tps']");
        const topsSecFields = document.querySelectorAll("input[name^='jge_bra_tourssec_']");
        const vitesseFields = document.querySelectorAll("input[name^='jge_bra_vitesse_']");

        function setFieldsState(fields, isDisabled)
        {
            fields.forEach(field => {
                field.readOnly           = isDisabled;
                field.style.backgroundColor = isDisabled ? '#e0e0e0' : '';
                field.style.opacity         = isDisabled ? '0.6'     : '1';
            });
        }

        if (selectedValue === '3')
        {
            // Direct velocity input
            setFieldsState([...topsFields, ...tempsFields, ...topsSecFields], true);
            setFieldsState(vitesseFields, false);
        }
        else if (selectedValue === '2')
        {
            // TOPs per second
            setFieldsState(topsSecFields, false);
            setFieldsState([...topsFields, ...tempsFields, ...vitesseFields], true);
        }
        else
        {
            // TOPs count (default)
            setFieldsState([...topsFields, ...tempsFields], false);
            setFieldsState([...topsSecFields, ...vitesseFields], true);
        }

        // Persist selected mode to server
        const id_user = <?php echo json_encode($id_user); ?>;
        const xhr     = new XMLHttpRequest();
        xhr.open("POST", "include/structure/box/process_menu.php", true);
        xhr.setRequestHeader("Content-Type", "application/json");
        xhr.send(JSON.stringify({ id_user: id_user, menu_id: 'jge-pts-saisie', is_open: selectedValue }));
    }

</script>