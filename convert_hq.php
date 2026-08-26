<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
H→Q conversion page — displays the water-level and flow-rate time
series for a hydrometric station and provides a batch conversion
workflow: run → preview → validate.

URL parameter: ?st=<id_station>

Left panel:
  - Time-series selectors (height to convert, target flow-rate series)
  - Gap display toggles
  - Convert / Validate / Wait buttons
  - Zoom coordinate controls

Right area:
  - Plotly chart: 3 stacked subplots (stage / discharge / ETL timeline) — plot_0
  - ETL coverage timeline             — bottom subplot of plot_0
----------------------------------------
*/

require('include/application_top.php');

$message_info = '';
$valid        = false;
$nom_station  = '';
$code_station = '';

$x_date_min  = 0;
$x_date_max  = 0;


// -----------------------------------------------
// Helper — render an inline color picker in the graph_chron style
// (current swatch + grid popup). The picker is keyed by $key (string)
// so the JS can target it uniquely:
//   - #selectedColor_<key>  : the small clickable swatch
//   - #dropdownList_<key>   : the floating color grid (.color-grid)
//   - #input_color_<key>    : hidden input holding the current #RRGGBB
// Plotly traces tagged with legendgroup="tdc_<key>" will receive the
// new color when the user picks one (see selectColor() in the script).

function render_color_picker($key, $default_color)
{
    $palette = colorPalette();

    echo "<div class='color-dropdown'>";
        echo "<div id='selectedColor_" . $key . "'"
           . " class='dropdown-selected'"
           . " onclick=\"toggleDropdownColor('" . $key . "')\""
           . " style='background-color:" . $default_color . ";'></div>";

        echo "<div id='dropdownList_" . $key . "' class='color-grid'>";
            foreach ($palette as $color)
            {
                $is_selected = (strcasecmp($color, $default_color) === 0) ? ' is-selected' : '';
                echo "<div class='color-cell" . $is_selected . "'"
                   . " style='background-color:" . $color . "'"
                   . " title='" . $color . "'"
                   . " onclick=\"selectColor('" . $color . "','" . $key . "')\"></div>";
            }
        echo "</div>";
    echo "</div>";

    echo "<input type='hidden' id='input_color_" . $key . "' value='" . $default_color . "'>";
}


// -----------------------------------------------
// Load station data

if (isset($_GET['st']))
{
    // Station id is numeric — cast directly, no need to escape.
    $id_station = (int)$_GET['st'];
 
    if ($id_station > 0)
    {
        $station_query = tep_db_query($sql_link,
            "SELECT DISTINCT s.id_station, s.nom_station, s.nom_court, s.code_station,
                             s.id_region, s.id_commune
             FROM " . TABLE_STATION . " s WHERE s.id_station=" . $id_station);
        $station = tep_db_fetch_array($station_query);
 
        if (isset($station['id_station']))
        {
            $nom_station  = html_entity_decode($station['nom_station']);
            $code_station = html_entity_decode($station['code_station']);
            $valid        = true;
        }
        else { $message_info .= TEXT_HQ_ERR_STATION; }
    }
    else { $message_info .= TEXT_HQ_ERR_STATION; }
}
else { $message_info .= TEXT_HQ_ERR_NO_ID; }


// -----------------------------------------------
// Optional GET params — used by the volume-guard chunk links to reopen
// the module pre-filled on a shorter period (and same H/Q series).
//   date_1 / date_2 : dd-mm-yyyy period bounds
//   th / tq         : preselected H / Q time-series type ids
$prefill_date_1 = (isset($_GET['date_1']) && preg_match('/^\d{2}-\d{2}-\d{4}$/', $_GET['date_1']))
    ? $_GET['date_1'] : '';
$prefill_date_2 = (isset($_GET['date_2']) && preg_match('/^\d{2}-\d{2}-\d{4}$/', $_GET['date_2']))
    ? $_GET['date_2'] : '';
$prefill_th = isset($_GET['th']) ? (int)$_GET['th'] : 0;
$prefill_tq = isset($_GET['tq']) ? (int)$_GET['tq'] : 0;


// -----------------------------------------------
// Data setup (only when station is valid)

if ($valid)
{
    // Clean up any leftover conversion temp data for this station
    $sql_meta_del = "SELECT DISTINCT dm.id FROM " . TABLE_DATA_META_CORRECTION . " dm
                     WHERE dm.id_station=" . (int)$id_station . " AND dm.source='Conversion'";
    $meta_del_query = tep_db_query($sql_link, $sql_meta_del);
    $ids_to_delete  = [];
    while ($meta_del = tep_db_fetch_array($meta_del_query))
    {
        $ids_to_delete[] = (int)$meta_del['id'];
    }
    if (!empty($ids_to_delete))
    {
        $ids_list = implode(',', $ids_to_delete);
        tep_db_query($sql_link, "START TRANSACTION");
            tep_db_query($sql_link, "DELETE FROM " . TABLE_DATA_ALL_CORRECTION    . " WHERE id_meta IN ($ids_list)");
            tep_db_query($sql_link, "DELETE FROM " . TABLE_DATA_META_CORRECTION   . " WHERE id IN ($ids_list)");
        tep_db_query($sql_link, "COMMIT");
    }

    // Height / gauge series (axis id = 1)
    $cote_array  = [];
    $cote_query  = tep_db_query($sql_link,
        "SELECT DISTINCT td.id_data_type, td.init_type_data, td.nom_type_data, ta.axe, ta.unite
         FROM " . TABLE_DATA_META . " dm
         JOIN " . TABLE_TYPE_DATA . " td ON td.id_data_type = dm.id_typedata
         JOIN " . TABLE_DATA_TYPE_AXE . " ta ON ta.id = td.axe_data
         WHERE dm.id_station=$id_station AND ta.id=1
         ORDER BY td.init_type_data DESC");
    while ($cote_tab = tep_db_fetch_array($cote_query))
    {
        $id = $cote_tab['id_data_type'];
        $cote_array[$id] = [
            'init_type_data' => html_entity_decode($cote_tab['init_type_data'] ?? ''),
            'nom_type_data'  => html_entity_decode($cote_tab['nom_type_data']  ?? ''),
            'axe'            => $cote_tab['axe'],
            'unite'          => $cote_tab['unite'],
        ];
    }

    // Flow-rate target series (axis id = 5, instantaneous only: to_periode = 1)
    $debit_array = [];
    $debit_query = tep_db_query($sql_link,
        "SELECT DISTINCT td.id_data_type, td.init_type_data, td.nom_type_data, ta.axe, ta.unite
         FROM " . TABLE_TYPE_DATA . " td
         JOIN " . TABLE_DATA_TYPE_AXE . " ta ON ta.id = td.axe_data
         WHERE ta.id=5 AND td.raw_data=1
         ORDER BY td.init_type_data DESC");
    while ($debit_tab = tep_db_fetch_array($debit_query))
    {
        $id = $debit_tab['id_data_type'];
        $debit_array[$id] = [
            'init_type_data' => html_entity_decode($debit_tab['init_type_data'] ?? ''),
            'nom_type_data'  => html_entity_decode($debit_tab['nom_type_data']  ?? ''),
            'axe'            => $debit_tab['axe'],
            'unite'          => $debit_tab['unite'],
        ];
    }
}


// -----------------------------------------------
// HTML output

require(DIR_WS_STRUCTURE . 'header_web.php');
echo "<body>";

require(DIR_WS_STRUCTURE . 'header.php');
include(DIR_WS_BOX       . 'nav_accueil.php');

echo "<div id='contour_general'>";

    echo "<div id='contenu_info' style='display:none;'></div>";

    // -----------------------------------------------
    // Local CSS — left panel layout + floating conversion log popup.
    // The .color-dropdown / .dropdown-selected / .color-grid / .color-cell
    // classes are styled globally (see general.css). Here we only:
    //   - lay out the dropdown + picker on a flex row
    //   - shrink the swatch to fit next to the <select>
    //   - style the bottom-right log popup (#hq_log)
    echo "<style>
        .hq-trace-row {
            display: flex; align-items: center; gap: 6px;
            margin-top: 3px; margin-left: 1%; width: 97%;
        }
        .hq-trace-row select { flex: 1 1 auto; width: 100%; min-width: 0; }
        .hq-trace-row .color-dropdown {
            flex: 0 0 auto;
            width: auto !important;
            margin: 0 !important;
        }

        /* ---- Floating conversion log (bottom-right) ----
           Fixed height (NOT max-height) so the popup never grows beyond
           its slot, even when many log lines are appended. Internal
           scroll on .hq-log-body keeps the recent lines visible. */
        #hq_log {
            width: 230px; margin: 10px 0 0 0;
            flex: 1 1 auto; height: auto; min-height: 120px; max-height: 300px;
            background: #fff; border: 1px solid #d4d4d4;
            border-radius: 8px; overflow: hidden;
            font-family: 'Open Sans', Arial, sans-serif;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            display: none; flex-direction: column;
            box-sizing: border-box;
        }
        #hq_log.is-open       { display: flex; }
        #hq_log.is-minimized  { height: 32px !important; flex: 0 0 auto !important; min-height: 0 !important; }
        #hq_log.is-minimized  .hq-log-body { display: none; }

        #hq_log .hq-log-head {
            background: #f8f8f8; padding: 6px 10px;
            display: flex; align-items: center; justify-content: space-between;
            border-bottom: 1px solid #e0e0e0;
            font-size: 12px; color: #333; user-select: none;
        }
        #hq_log .hq-log-title { font-weight: 500; }
        #hq_log .hq-log-btns  { display: flex; gap: 2px; }
        #hq_log .hq-log-btn {
            display: inline-flex; align-items: center; justify-content: center;
            width: 22px; height: 22px;
            background: transparent; border: 0;
            color: #555; cursor: pointer;
            border-radius: 3px; padding: 0;
        }
        #hq_log .hq-log-btn:hover { background: #e0e0e0; color: #000; }
        #hq_log .hq-log-btn svg   { width: 14px; height: 14px; display: block; }

        #hq_log .hq-log-body {
            padding: 8px 10px; color: #333; font-size: 11px; line-height: 1.6;
            overflow-y: auto; flex: 1;
        }
        #hq_log .hq-log-line       { white-space: pre-wrap; }
        #hq_log .hq-log-line .ts   { color: #999; margin-right: 4px; }

        /* Visual separator between consecutive conversion / save runs.
           Pure CSS — the JS just adds the .is-run-sep modifier class
           to the first line of a new run. */
        #hq_log .hq-log-line.is-run-sep {
            margin-top: 8px; padding-top: 6px;
            border-top: 1px dashed #d4d4d4;
        }

        #hq_log .hq-log-sum {
            margin-top: 8px; padding: 8px;
            background: #f5f5f5; border-radius: 4px;
            font-size: 11px;
        }
        #hq_log .hq-log-sum-title {
            font-weight: 600; margin-bottom: 4px; color: #333;
        }
        #hq_log .hq-log-sum-row {
            display: flex; justify-content: space-between; align-items: center;
            padding: 1px 0;
        }
        #hq_log .hq-log-sum-row .label { display: flex; align-items: center; gap: 4px; }
        #hq_log .hq-log-sum-row .label svg { width: 13px; height: 13px; flex: 0 0 auto; }
        #hq_log .hq-log-sum-row .val   { font-weight: 600; }
        #hq_log .hq-log-sum-row.ok      .label { color: #1D9E75; }
        #hq_log .hq-log-sum-row.warn    .label { color: #BA7517; }
        #hq_log .hq-log-sum-row.danger  .label { color: #A32D2D; }
        #hq_log .hq-log-sum-row.mute    .label,
        #hq_log .hq-log-sum-row.mute    .val   { color: #888; }

        /* ---- Pulsing dots loader (Convert / Save in progress) ----
           Three dots that fade in/out in sequence, like a typing
           indicator. Inherits its color from the parent button text
           (currentColor), so it stays white on the red button without
           any extra styling. */
        .hq-wait-dots {
            display: inline-flex; align-items: center; gap: 4px;
            vertical-align: middle;
        }
        .hq-wait-dots span {
            width: 6px; height: 6px; border-radius: 50%;
            background: currentColor;
            opacity: 0.3;
            animation: hqWaitPulse 1.2s ease-in-out infinite;
        }
        .hq-wait-dots span:nth-child(2) { animation-delay: 0.2s; }
        .hq-wait-dots span:nth-child(3) { animation-delay: 0.4s; }

        @keyframes hqWaitPulse {
            0%, 100% { opacity: 0.3; transform: scale(0.85); }
            50%      { opacity: 1;   transform: scale(1.15); }
        }

        /* ---- Graph loading spinner (modern, harmonised with the page) ---- */
        .hq-spinner {
            width: 38px; height: 38px;
            border: 4px solid rgba(23,107,135,0.18);
            border-top-color: #176B87;
            border-radius: 50%;
            animation: hqSpin 0.8s linear infinite;
            margin: 0 auto;
        }
        @keyframes hqSpin { to { transform: rotate(360deg); } }
        /* Larger spinner for the full-area initial loading. */
        .hq-spinner-lg {
            width: 64px; height: 64px; border-width: 6px;
        }
        .hq-loading-text {
            margin-top: 10px; font-size: 13px; color: #555;
            letter-spacing: 0.3px;
        }
        /* Centered card so the spinner stays readable over the chart line. */
        .hq-loading-card {
            display: inline-block;
            padding: 18px 26px 14px;
            background: rgba(255,255,255,0.92);
            border: 1px solid rgba(23,107,135,0.15);
            border-radius: 12px;
            box-shadow: 0 6px 22px rgba(0,0,0,0.12);
            backdrop-filter: blur(2px);
            -webkit-backdrop-filter: blur(2px);
        }
        .hq-loading-card .hq-loading-text {
            margin-top: 8px; margin-bottom: 0; font-weight: 500; color: #176B87;
        }

        /* ---- Success card (centered, bold, shown after a save) ---- */
        .hq-success-card {
            display: inline-block;
            padding: 22px 34px 20px;
            background: #ffffff;
            border: 1px solid rgba(9,136,109,0.35);
            border-top: 5px solid #09886d;
            border-radius: 14px;
            box-shadow: 0 10px 32px rgba(0,0,0,0.18);
            animation: hqSuccessPop 0.32s cubic-bezier(0.18,0.89,0.32,1.28);
        }
        .hq-success-icon {
            width: 54px; height: 54px; margin: 0 auto 8px;
            border-radius: 50%;
            background: #09886d; color: #fff;
            display: flex; align-items: center; justify-content: center;
        }
        .hq-success-icon svg { width: 30px; height: 30px; }
        .hq-success-text {
            margin: 0; font-size: 17px; font-weight: 700; color: #0c6b56;
            letter-spacing: 0.2px;
        }
        @keyframes hqSuccessPop {
            0%   { opacity: 0; transform: scale(0.85); }
            100% { opacity: 1; transform: scale(1); }
        }
        #hq_success.hq-fade-out { transition: opacity 0.5s ease; opacity: 0; }

        /* ---- Save in progress — green variant ----
           The HQ palette already uses #A32D2D for Convert (red). We
           reuse the same button geometry for Save and just override
           the background colour to green via .hq-wait-save. */
        #save_wait.hq-wait-save {
            background: #1D9E75 !important;
            border-color: #157C5A !important;
            color: #fff !important;
        }

        /* ---- Workflow stepper (Data display / Conversion / Save) ----
           Replaces the old empty black title strip above the chart.
           Full-width grey strip, centered content (3 .hq-step pills
           separated by .hq-step-link bars). States are driven from JS
           by toggling .is-pending / .is-active / .is-done / .is-error
           on each .hq-step. */
        .hq-stepper {
            display: flex; align-items: center; justify-content: center;
            gap: 12px;
            padding: 8px 16px;
            background: #fafafa; border-bottom: 1px solid #e0e0e0;
            font-family: 'Open Sans', Arial, sans-serif;
        }
        .hq-step {
            display: flex; align-items: center; gap: 8px;
            font-size: 12px; font-weight: 600;
            white-space: nowrap; flex: 0 0 auto;
        }
        .hq-step-dot {
            width: 10px; height: 10px; border-radius: 50%;
            background: #d4d4d4; flex: 0 0 auto;
            transition: background 0.2s, box-shadow 0.2s;
        }
        .hq-step-link {
            width: 90px; flex: 0 0 90px; height: 3px;
            background: #d4d4d4; border-radius: 2px;
            transition: background 0.2s;
        }

        /* ---- pending: muted grey ---- */
        .hq-step.is-pending             .hq-step-label { color: #999; }
        .hq-step.is-pending             .hq-step-dot   { background: #d4d4d4; }

        /* ---- active: blue, dot glows + halo pulse ---- */
        .hq-step.is-active              .hq-step-label { color: #176B87; }
        .hq-step.is-active              .hq-step-dot {
            background: #176B87;
            animation: hqStepGlow 1.4s ease-in-out infinite;
        }
        @keyframes hqStepGlow {
            0%, 100% { box-shadow: 0 0 0 3px rgba(23,107,135,0.18); }
            50%      { box-shadow: 0 0 0 7px rgba(23,107,135,0.32); }
        }

        /* ---- done: green ---- */
        .hq-step.is-done                .hq-step-label { color: #1D9E75; }
        .hq-step.is-done                .hq-step-dot   { background: #1D9E75; }

        /* ---- error: red ---- */
        .hq-step.is-error               .hq-step-label { color: #A32D2D; }
        .hq-step.is-error               .hq-step-dot   { background: #A32D2D; }

        /* ---- link colouring follows the step on its LEFT ----
           A link is green if the step to its left is done, blue while
           that step is active (with a left-to-right gradient toward grey
           for the segment that hasn't been reached yet), grey otherwise. */
        .hq-step.is-done    + .hq-step-link { background: #1D9E75; }
        .hq-step.is-active  + .hq-step-link {
            background: linear-gradient(to right, #176B87 0%, #176B87 50%, #d4d4d4 50%, #d4d4d4 100%);
        }
        .hq-step.is-error   + .hq-step-link { background: #A32D2D; }

        /* ---- ETL timeline hover popup (same look as the old SVG version) ---- */
        #etl_popup {
            position: fixed; z-index: 9999; display: none;
            background: #fff; border: 1px solid #bbb;
            border-radius: 4px; padding: 8px 10px;
            font-family: 'Open Sans', Arial, sans-serif; font-size: 11px; color: #222;
            box-shadow: 0 2px 6px rgba(0,0,0,0.15);
            pointer-events: none; max-width: 280px;
        }
        #etl_popup .etl_pop_title { font-weight: bold; font-size: 12px; margin-bottom: 4px; color: #4a3270; }
        #etl_popup .etl_pop_row { margin: 1px 0; }
        #etl_popup .etl_pop_row span { font-weight: bold; color: #222; }
        #etl_popup .etl_pop_hint {
            margin-top: 6px; padding-top: 4px; border-top: 1px dashed #ddd;
            color: #888; font-style: italic; font-size: 10px;
        }
    </style>\n";

    echo "<div id='contenu_centre'>";
        echo "<div id='contenu_box2'>";

            // ---- Page title ----
            echo "<h1 id='h1_graph'>";
                echo "<span style='font-weight:bold;'>" . TEXT_HQ_PAGE_TITLE_PREFIX . "</span>";
                echo "<span style='color:#000;'>" . TEXT_HQ_PAGE_TITLE_STATION
                   . $code_station . " - " . $nom_station . "</span>";
            echo "</h1>";


            if ($valid)
            {
                // ---- Left panel ----
                echo "<div id='cadre_graph' style='float:left;width:250px;margin-right:10px;height:calc(100vh - 200px);max-height:calc(100vh - 200px);display:flex;flex-direction:column;overflow-y:auto;overflow-x:hidden;'>\n";

                    echo "<div id='boxpopup' class='select-top' style='width:210px;padding:10px;'>\n";

                        // Height series selector
                        echo "<p style='margin-left:1%;'>";
                            echo "<span style='font-weight:bold;font-size:13px;'>"
                               . TEXT_HQ_CHRON_H_LABEL . "</span>";
                        echo "</p>";

                        // Dropdown + color picker on the same row
                        echo "<div class='hq-trace-row'>";
                            echo "<select name='select_chron_h' id='select_chron_h'>";
                            if (!empty($cote_array))
                            {
                                foreach ($cote_array as $key => $value)
                                {
                                    $sel_h = ($prefill_th > 0 && $prefill_th == $key) ? ' selected' : '';
                                    echo "<option value='" . $key . "'" . $sel_h . ">"
                                       . $value['init_type_data'] . " - " . $value['nom_type_data'] . "</option>";
                                }
                            }
                            echo "</select>";

                            $palette_default = colorList();
                            render_color_picker('h', $palette_default[1]);
                        echo "</div>";

                        echo "<p style='margin-left:1%;'>";
                            echo "<input type='checkbox' id='check_lac_axe1'>";
                            echo "<span style='margin-left:5px;font-size:11px;font-weight:normal;'>"
                               . TEXT_HQ_SHOW_GAPS . "</span>";
                        echo "</p>";

                        // Flow-rate target series selector
                        echo "<p style='margin-top:15px;margin-left:1%;'>";
                            echo "<span style='font-weight:bold;font-size:13px;'>"
                               . TEXT_HQ_CHRON_Q_LABEL . "</span>";
                        echo "</p>";

                        // Dropdown + color picker on the same row
                        echo "<div class='hq-trace-row'>";
                            echo "<select name='select_chron_q' id='select_chron_q'>";
                            if (!empty($debit_array))
                            {
                                foreach ($debit_array as $key => $value)
                                {
                                    $sel_q = ($prefill_tq > 0 && $prefill_tq == $key) ? ' selected' : '';
                                    echo "<option value='" . $key . "'" . $sel_q . ">"
                                       . $value['init_type_data'] . " - " . $value['nom_type_data'] . "</option>";
                                }
                            }
                            echo "</select>";

                            render_color_picker('q', $palette_default[2]);
                        echo "</div>";

                        echo "<p style='margin-left:1%;'>";
                            echo "<input type='checkbox' id='check_lac_axe2'>";
                            echo "<span style='margin-left:5px;font-size:11px;font-weight:normal;'>"
                               . TEXT_HQ_SHOW_GAPS . "</span>";
                        echo "</p>";

                        // Action buttons (only if height series exist)
                        if (!empty($cote_array))
                        {
                            echo "<div id='button_convert' style='float:left;width:90%;margin-top:15px;margin-left:1%;padding:4px 5px;'"
                               . " title='" . TEXT_HQ_BTN_CONVERT_TITLE . "'>";
                                echo "<span>" . TEXT_HQ_BTN_CONVERT . "</span>";
                            echo "</div>\n";

                            echo "<div id='button_modif' style='float:left;width:90%;margin-top:15px;padding:4px 5px;display:none;'"
                               . " title='" . TEXT_HQ_BTN_CONVERT_TITLE . "'>";
                                echo "<span>" . TEXT_HQ_BTN_VALIDATE . "</span>";
                            echo "</div>\n";

                            echo "<div id='convert_wait' style='float:left;width:90%;margin-top:15px;padding:4px 5px;display:none;'"
                               . " title='" . TEXT_HQ_BTN_CONVERT_TITLE . "'>";
                                echo "<span class='hq-wait-dots' aria-hidden='true'>"
                                   . "<span></span><span></span><span></span>"
                                   . "</span>";
                                echo "<span style='margin-left:10px;'>" . TEXT_HQ_BTN_WAIT_LABEL . "</span>";
                            echo "</div>\n";

                            echo "<div id='save_wait' class='hq-wait-save' style='float:left;width:90%;margin-top:15px;padding:4px 5px;display:none;'"
                               . " title='" . TEXT_HQ_BTN_SAVE_TITLE . "'>";
                                echo "<span class='hq-wait-dots' aria-hidden='true'>"
                                   . "<span></span><span></span><span></span>"
                                   . "</span>";
                                echo "<span style='margin-left:10px;'>" . TEXT_HQ_BTN_SAVE_WAIT_LABEL . "</span>";
                            echo "</div>\n";
                        }

                    echo "</div>\n";

                    // Period Select (selection-driven, no zoom)
                    echo "<div id='boxpopup' class='select-top' style='width:210px;padding:10px;margin-top:10px;'>\n";

                        echo "<p><span style='font-weight:bold;font-size:13px;width:150px;'>"
                           . (defined('TEXT_HQ_PERIOD_SELECT_LABEL') ? TEXT_HQ_PERIOD_SELECT_LABEL : 'Period Select') . "</span></p>";

                        echo "<div id='boite_small' class='select_date'>\n";
                            echo "<p style='width:80px;color:#428bca;'>" . TEXT_HQ_DATE_MIN_LABEL . "</p>\n";
                            echo "<input class='input_texte' style='width:70px;padding-bottom:4px;'"
                               . " name='x_date_min' id='x_date_min' type='text' value='" . htmlspecialchars($prefill_date_1) . "'"
                               . " onFocus='initDatepickers(this)' placeholder='dd-mm-yyyy'>\n";
                        echo "</div>\n";

                        echo "<div id='boite_small' class='select_date' style='margin-right:0;'>\n";
                            echo "<p style='width:80px;color:#428bca;'>" . TEXT_HQ_DATE_MAX_LABEL . "</p>\n";
                            echo "<input class='input_texte' style='width:70px;padding-bottom:4px;'"
                               . " name='x_date_max' id='x_date_max' type='text' value='" . htmlspecialchars($prefill_date_2) . "'"
                               . " onFocus='initDatepickers(this)' placeholder='dd-mm-yyyy'>\n";
                        echo "</div>\n";

                        echo "<hr>\n";

                        echo "<button id='ajustCoord' class='zoom_graph'"
                           . " style='width:140px;margin-top:5px;margin-bottom:5px;text-align:left;'"
                           . " onClick='applyPeriodFromFields();'>"
                           . (defined('TEXT_HQ_BTN_APPLY_PERIOD') ? TEXT_HQ_BTN_APPLY_PERIOD : 'Apply period') . "</button>\n";

                    echo "</div>\n";

                    // ---- Conversion log (in the left column, under Period Select) ----
                    // Inline SVG icons for the log toolbar (defined here so they
                    // exist when the log is rendered in the left column).
                    $svg_copy  = "<svg viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><rect x='9' y='9' width='13' height='13' rx='2'/><path d='M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1'/></svg>";
                    $svg_trash = "<svg viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><polyline points='3 6 5 6 21 6'/><path d='M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6'/><path d='M10 11v6M14 11v6'/><path d='M9 6V4a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2'/></svg>";
                    $svg_min   = "<svg viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><line x1='5' y1='12' x2='19' y2='12'/></svg>";
                    $svg_close = "<svg viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><line x1='18' y1='6' x2='6' y2='18'/><line x1='6' y1='6' x2='18' y2='18'/></svg>";

                    echo "<div id='hq_log'>";
                        echo "<div class='hq-log-head'>";
                            echo "<span class='hq-log-title'>" . TEXT_HQ_CONSOLE_TITLE . "</span>";
                            echo "<span class='hq-log-btns'>";
                                echo "<button type='button' id='hq_log_copy'  class='hq-log-btn' title='" . TEXT_HQ_CONSOLE_COPY  . "'>" . $svg_copy  . "</button>";
                                echo "<button type='button' id='hq_log_clear' class='hq-log-btn' title='" . TEXT_HQ_CONSOLE_CLEAR . "'>" . $svg_trash . "</button>";
                                echo "<button type='button' id='hq_log_min'   class='hq-log-btn' title='" . TEXT_HQ_CONSOLE_MIN   . "'>" . $svg_min   . "</button>";
                                echo "<button type='button' id='hq_log_close' class='hq-log-btn' title='" . TEXT_HQ_CONSOLE_CLOSE . "'>" . $svg_close . "</button>";
                            echo "</span>";
                        echo "</div>";
                        echo "<div id='hq_log_body' class='hq-log-body'></div>";
                    echo "</div>";

                echo "<hr>\n";
                echo "</div>\n"; // left panel


                // ---- Chart area ----
                echo "<div id='cadre_graph' style='float:none;width:auto;height:calc(100vh - 150px);display:flex;flex-direction:column;overflow:hidden;'>\n";

                    echo "<div id='boxpopup' class='select' style='width:99%;margin:0;padding:0;border:1px solid #000;display:flex;flex-direction:column;flex:1;min-height:0;'>\n";

                        // -----------------------------------------------
                        // Workflow stepper — replaces the legacy black
                        // title strip (was an empty `<p class='titre'>`).
                        // Three segments, no clicks; pure status display.
                        echo "<div id='hq_stepper' class='hq-stepper'>";
                            echo "<div class='hq-step is-done' data-step='1'>"
                               . "<span class='hq-step-dot'></span>"
                               . "<span class='hq-step-label'>" . TEXT_HQ_STEP_1_LABEL . "</span>"
                               . "</div>";
                            echo "<div class='hq-step-link'></div>";
                            echo "<div class='hq-step is-pending' data-step='2'>"
                               . "<span class='hq-step-dot'></span>"
                               . "<span class='hq-step-label'>" . TEXT_HQ_STEP_2_LABEL . "</span>"
                               . "</div>";
                            echo "<div class='hq-step-link'></div>";
                            echo "<div class='hq-step is-pending' data-step='3'>"
                               . "<span class='hq-step-dot'></span>"
                               . "<span class='hq-step-label'>" . TEXT_HQ_STEP_3_LABEL . "</span>"
                               . "</div>";
                        echo "</div>";

                        // Period-selection hint — above the chart, right-aligned.
                        // Same wording/crosshair icon as the chronicle-correction
                        // screen. Only the Shift+drag line here (no yellow-marker
                        // hint in the convert module).
                        echo "<div style='text-align:right;padding:6px 12px 2px 0;"
                           . "font-size:13px;color:#666;font-style:italic;'>";
                            echo "<svg width='13' height='13' viewBox='0 0 13 13'"
                               . " style='vertical-align:middle;margin-right:6px;'>"
                               . "<line x1='6.5' y1='0' x2='6.5' y2='13' stroke='#016A70' stroke-width='1.5'/>"
                               . "<line x1='0' y1='6.5' x2='13' y2='6.5' stroke='#016A70' stroke-width='1.5'/>"
                               . "</svg>";
                            echo TEXT_CORRECT_SELECT_PERIOD_HINT;
                        echo "</div>\n";

                        echo "<div id='plot_0' class='graph' style='flex:1;min-height:320px;margin:0 10px;display:none;position:relative;'></div>\n";

                        // Localized loading overlay shown over the discharge band
                        // during conversion (stage + timeline stay visible).
                        echo "<div id='discharge_loading' style='display:none;position:absolute;"
                           . "left:0;right:0;text-align:center;z-index:50;pointer-events:none;'>"
                           . "<div class='hq-loading-card'>"
                           . "<div class='hq-spinner'></div>"
                           . "<p class='hq-loading-text'>" . TEXT_HQ_LOADING . "</p>"
                           . "</div>"
                           . "</div>\n";

                        // Centered success card shown after a save (replaces the
                        // small top banner with a bold, well-centered message).
                        echo "<div id='hq_success' style='display:none;position:absolute;"
                           . "left:0;right:0;text-align:center;z-index:60;pointer-events:none;'>"
                           . "<div class='hq-success-card'>"
                           . "<div class='hq-success-icon'>"
                           . "<svg viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2.5'"
                           . " stroke-linecap='round' stroke-linejoin='round'><polyline points='20 6 9 17 4 12'/></svg>"
                           . "</div>"
                           . "<p class='hq-success-text'></p>"
                           . "</div>"
                           . "</div>\n";

                        // Volume-guard message: shown when the H series is too
                        // large to render. Filled by load_graph() with the
                        // server's msg_noLoad (message + chunk links).
                        echo "<div id='box_msg_noload_hq' style='display:none;margin:10px;text-align:center;color:#333;'></div>\n";

                        echo "<div id='wait_graph' style='flex:1;min-height:320px;width:100%;display:none;"
                           . "flex-direction:column;align-items:center;justify-content:center;'>";
                            echo "<div class='hq-spinner hq-spinner-lg'></div>";
                            echo "<p class='hq-loading-text'>" . TEXT_HQ_LOADING . "</p>";
                        echo "</div>\n";

                        // ETL coverage timeline is now the 3rd subplot inside
                        // #plot_0 (no separate SVG container). The old progress
                        // bar was removed (progress now shown via the log + the
                        // localized discharge spinner).

                    echo "</div>\n";

                echo "<hr>\n";
                echo "</div>\n"; // chart area
            }
            else
            {
                echo "<div id='boxpopup'>\n";
                    echo "<p class='alert'>" . TEXT_HQ_NO_DATA . "</p>";
                echo "<hr>";
                echo "</div>";
            }

        echo "<hr>";
        echo "</div>"; // contenu_box2

    echo "<hr>";
    echo "</div>"; // contenu_centre

echo "<hr>";
echo "</div>"; // contour_general

// -----------------------------------------------
// Validate-Save confirmation popup (target series + math challenge).
// $typedata_chron_q is initialised to the first entry of $debit_array,
// which matches what the left-panel <select> shows when the page loads.
// The JS in block_verif_save_hq.php then keeps the dropdown in sync
// with the left panel via the openSaveHqPopup() helper below.

$typedata_chron_q = !empty($debit_array) ? array_key_first($debit_array) : 0;
require(DIR_WS_STRUCTURE . 'converthq/block_verif_save_hq.php');

require('include/application_bottom.php');
echo "</body>";
echo "</html>";
?>


<script>

    var msgInfo = document.getElementById('contenu_info');

    var selectH = document.getElementById('select_chron_h');
    var selectQ = document.getElementById('select_chron_q');

    var boxGraphWait = document.getElementById('wait_graph');
    var boxPlot      = document.getElementById('plot_0');
    var boxMsgNoLoad = document.getElementById('box_msg_noload_hq');

    var checkLacH = document.getElementById('check_lac_axe1');
    var checkLacQ = document.getElementById('check_lac_axe2');

    var xDateMin    = document.getElementById('x_date_min');
    var xDateMax    = document.getElementById('x_date_max');

    // Full (unzoomed) data range, in dd-mm-yyyy. Captured from the server's
    // full data bounds on every graph load. Used to restore the global view
    // after a conversion that was launched on a zoomed sub-period: the
    // conversion itself runs on the zoom, but the chart then reloads on the
    // full range so the user keeps their overall view.
    var globalDateMin = '';
    var globalDateMax = '';

    var bConvert = document.getElementById('button_convert');
    var bValid   = document.getElementById('button_modif');

    var convertWait       = document.getElementById('convert_wait');
    var saveWait       = document.getElementById('save_wait');
    var barreProgress     = document.getElementById('barre_progress');
    var pourcentageCompil = document.getElementById('pourcentage_compil');

    var pourcentage = 0;
    var nb_data_all = 0;

    var typedataChronH = selectH.value;
    var typedataChronQ = selectQ.value;

    var colorH = document.getElementById('input_color_h');
    var colorQ = document.getElementById('input_color_q');

    var id_meta_correction = 0;
    // dateFirstProcess is the SQL cursor sent to the server. It MUST be
    // in MySQL DATETIME format (yyyy-mm-dd hh:ii:ss). xDateMin.value is
    // a dd-mm-yyyy string from the FR-formatted date picker, so we
    // reverse the segments before appending the time.
    var dateFirstProcess = xDateMin.value.split('-').reverse().join('-') + ' 00:00:00';
    var offSet = 0; // On garde offSet uniquement pour la barre de progression

    // i18n strings from PHP
    var msgSaved      = <?php echo json_encode(TEXT_HQ_JS_SAVED); ?>;
    var msgErrOrder   = <?php echo json_encode(TEXT_HQ_JS_ERR_DATE_ORDER); ?>;
    var msgErrFormat  = <?php echo json_encode(TEXT_HQ_JS_ERR_DATE_FORMAT); ?>;

    // i18n strings for the floating conversion log popup
    var LOG_TXT = {
        start:        <?php echo json_encode(TEXT_HQ_LOG_START); ?>,
        bad_response: <?php echo json_encode(TEXT_HQ_LOG_BAD_RESPONSE); ?>,
        etl_found:    <?php echo json_encode(TEXT_HQ_LOG_ETL_FOUND); ?>,
        seg_ready:    <?php echo json_encode(TEXT_HQ_LOG_SEGMENTS_READY); ?>,
        cvt_start:    <?php echo json_encode(TEXT_HQ_LOG_CONVERT_START); ?>,
        cvt_done:     <?php echo json_encode(TEXT_HQ_LOG_CONVERT_DONE); ?>,
        summary:      <?php echo json_encode(TEXT_HQ_LOG_SUMMARY); ?>,
        converted:    <?php echo json_encode(TEXT_HQ_LOG_CONVERTED); ?>,
        nocov:        <?php echo json_encode(TEXT_HQ_LOG_NO_COVERAGE); ?>,
        above:        <?php echo json_encode(TEXT_HQ_LOG_STAGE_ABOVE); ?>,
        below:        <?php echo json_encode(TEXT_HQ_LOG_STAGE_BELOW); ?>,
        gaps:         <?php echo json_encode(TEXT_HQ_LOG_SOURCE_GAPS); ?>,
        ready_valid:  <?php echo json_encode(TEXT_HQ_LOG_READY_VALID); ?>,

        save_start:        <?php echo json_encode(TEXT_HQ_LOG_SAVE_START); ?>,
        save_bad_response: <?php echo json_encode(TEXT_HQ_LOG_SAVE_BAD_RESPONSE); ?>,
        save_done:         <?php echo json_encode(TEXT_HQ_LOG_SAVE_DONE); ?>,
        save_success:      <?php echo json_encode(TEXT_HQ_LOG_SAVE_SUCCESS); ?>
    };


    // -----------------------------------------------
    // hqLog — floating bottom-right console.
    //
    // Used by both the Convert flow and the Validate flow to keep the
    // user informed of what is happening server-side. Open the popup
    // explicitly with hqLog.show(); it stays open until the user closes
    // it. line() appends a timestamped entry; summary() renders the
    // categorised totals block (with green / amber / red icons).

    var hqLog = (function() {
        var box  = document.getElementById('hq_log');
        var body = document.getElementById('hq_log_body');

        function ts()
        {
            var d  = new Date();
            var hh = String(d.getHours()).padStart(2, '0');
            var mm = String(d.getMinutes()).padStart(2, '0');
            var ss = String(d.getSeconds()).padStart(2, '0');
            return hh + ':' + mm + ':' + ss;
        }

        function show()
        {
            box.classList.remove('is-minimized');
            box.classList.add('is-open');
        }

        function hide()
        {
            box.classList.remove('is-open');
        }

        function clear()
        {
            body.innerHTML = '';
        }

        function toggleMin()
        {
            box.classList.toggle('is-minimized');
        }

        // Append a single timestamped line.
        // isRunSep=true adds a dashed top border so successive runs are
        // visually separated in the log without clearing the history.
        function line(message, isRunSep)
        {
            var div = document.createElement('div');
            div.className = 'hq-log-line' + (isRunSep ? ' is-run-sep' : '');
            div.innerHTML = "<span class='ts'>" + ts() + "</span>" + message;
            body.appendChild(div);
            body.scrollTop = body.scrollHeight;
        }

        // Render the categorised summary block at the end of a run.
        // stats = { converted, gaps_source, above, below, nocov }
        // Icons are inline SVG so they always render.
        function summary(stats)
        {
            var SVG_CHECK = "<svg viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><polyline points='20 6 9 17 4 12'/></svg>";
            var SVG_WARN  = "<svg viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><path d='M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z'/><line x1='12' y1='9'  x2='12' y2='13'/><line x1='12' y1='17' x2='12.01' y2='17'/></svg>";
            var SVG_X     = "<svg viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><line x1='18' y1='6' x2='6' y2='18'/><line x1='6' y1='6' x2='18' y2='18'/></svg>";

            var rows =
                "<div class='hq-log-sum-title'>" + LOG_TXT.summary + "</div>" +
                "<div class='hq-log-sum-row ok'>"
              +     "<span class='label'>" + SVG_CHECK + LOG_TXT.converted + "</span>"
              +     "<span class='val'>" + stats.converted.toLocaleString() + "</span>"
              + "</div>" +
                "<div class='hq-log-sum-row warn'>"
              +     "<span class='label'>" + SVG_WARN + LOG_TXT.above + "</span>"
              +     "<span class='val'>" + stats.above.toLocaleString() + "</span>"
              + "</div>" +
                "<div class='hq-log-sum-row warn'>"
              +     "<span class='label'>" + SVG_WARN + LOG_TXT.below + "</span>"
              +     "<span class='val'>" + stats.below.toLocaleString() + "</span>"
              + "</div>" +
                "<div class='hq-log-sum-row danger'>"
              +     "<span class='label'>" + SVG_X + LOG_TXT.nocov + "</span>"
              +     "<span class='val'>" + stats.nocov.toLocaleString() + "</span>"
              + "</div>" +
                "<div class='hq-log-sum-row mute'>"
              +     "<span class='label'>" + LOG_TXT.gaps + "</span>"
              +     "<span class='val'>" + stats.gaps_source.toLocaleString() + "</span>"
              + "</div>";

            var div = document.createElement('div');
            div.className = 'hq-log-sum';
            div.innerHTML = rows;
            body.appendChild(div);
            body.scrollTop = body.scrollHeight;
        }

        function copyAll()
        {
            navigator.clipboard.writeText(body.innerText || '');
        }

        // Wire the header buttons
        document.getElementById('hq_log_copy' ).addEventListener('click', copyAll);
        document.getElementById('hq_log_clear').addEventListener('click', clear);
        document.getElementById('hq_log_min'  ).addEventListener('click', toggleMin);
        document.getElementById('hq_log_close').addEventListener('click', hide);

        return { show: show, hide: hide, clear: clear, line: line, summary: summary };
    })();


    // -----------------------------------------------
    // hqStepper — visual workflow indicator above the chart.
    //
    // 3 steps: 1=Data display, 2=Conversion, 3=Save.
    // Each step has 4 states:
    //   'pending' (grey), 'active' (blue + pulsing halo),
    //   'done'    (green), 'error'  (red).
    //
    // Usage:
    //   hqStepper.set(1, 'done');
    //   hqStepper.set(2, 'active');
    //
    // set(step) without a second arg defaults to 'active' and also
    // marks every prior step as 'done', so the common case of
    // "advance to step N" is a one-liner.

    var hqStepper = (function()
    {
        var steps = [
            null, // index 0 unused, steps are 1-based to match data-step="N"
            document.querySelector('.hq-step[data-step="1"]'),
            document.querySelector('.hq-step[data-step="2"]'),
            document.querySelector('.hq-step[data-step="3"]')
        ];

        function applyState(el, state)
        {
            if (!el) { return; }
            el.classList.remove('is-pending', 'is-active', 'is-done', 'is-error');
            el.classList.add('is-' + state);
        }

        function set(stepIndex, state)
        {
            if (state === undefined) { state = 'active'; }

            for (var i = 1; i <= 3; i++)
            {
                if (i < stepIndex)
                {
                    // Don't downgrade a step that was explicitly marked
                    // 'error' or 'done' on a previous call — just leave
                    // it alone if it's already past pending.
                    if (steps[i] && steps[i].classList.contains('is-pending'))
                    {
                        applyState(steps[i], 'done');
                    }
                }
                else if (i === stepIndex)
                {
                    applyState(steps[i], state);
                }
                else
                {
                    applyState(steps[i], 'pending');
                }
            }
        }

        function reset()
        {
            applyState(steps[1], 'done');     // graph displayed by default
            applyState(steps[2], 'pending');
            applyState(steps[3], 'pending');
        }

        return { set: set, reset: reset };
    })();


    // -----------------------------------------------
    // load_graph(reload, onDone) — fetch and render the main chart.
    //
    // onDone is an optional callback fired after the graph has actually
    // been rendered (Plotly.newPlot returned). Use it to keep "in progress"
    // UI visible until the graph itself is back, not just until the
    // conversion finished server-side.

    function load_graph(reload, onDone, captureGlobalView)
    {
        reload = reload || false;
        // Default: a load captures the global view UNLESS told otherwise
        // (the post-conversion reload restores the global range itself and
        // does not want it overwritten by a zoomed reload).
        if (typeof captureGlobalView === 'undefined') { captureGlobalView = true; }

        boxPlot.style.display      = 'none';
        boxGraphWait.style.display = 'flex';
        msgInfo.style.display      = 'none';
        if (boxMsgNoLoad) { boxMsgNoLoad.style.display = 'none'; boxMsgNoLoad.innerHTML = ''; }

        typedataChronH = selectH.value;
        typedataChronQ = selectQ.value;

        var xhr = new XMLHttpRequest();
        xhr.open("POST", "include/structure/converthq/process_convert_graph.php", true);
        xhr.setRequestHeader("Content-Type", "application/json");

        xhr.onreadystatechange = function()
        {
            if (xhr.readyState === 4 && xhr.status === 200)
            {
                var r = JSON.parse(xhr.responseText);

                // Volume guard: too many H rows for the requested period.
                // Show the message + chunk links instead of a chart.
                if (r['graph_load'] === false)
                {
                    boxPlot.style.display      = 'none';
                    boxGraphWait.style.display = 'none';
                    if (barreProgress) { barreProgress.style.display = 'none'; }
                    if (boxMsgNoLoad)
                    {
                        boxMsgNoLoad.innerHTML     = r['msg_noLoad'] || '';
                        boxMsgNoLoad.style.display = 'block';
                    }
                    if (typeof onDone === 'function') { onDone(); }
                    return;
                }

                boxPlot.style.display       = 'block';
                nb_data_all                 = r['nb_data_all'];

                // New structured response — Plotly.newPlot is fed the traces,
                // layout and config straight from JSON (no eval of a huge JS
                // string for 100k+ data points). Only a short post-render JS
                // block (zoom-sync + shape exposure) still goes through eval().
                if (r.traces && r.layout) {
                    // Re-attach the custom modeBar buttons that carry JS
                    // function references (can't survive JSON serialisation).
                    var cfg = r.config || {};
                    cfg.modeBarButtons = [[
                        { name: 'Export SVG', icon: Plotly.Icons.disk,
                          click: function(gd) {
                              Plotly.downloadImage(gd, { format: 'svg', filename: 'convert_hq' });
                          }
                        },
                        'toImage', 'zoom2d', 'pan2d', 'select2d', 'resetScale2d'
                    ]];
                    cfg.responsive = true;
                    Plotly.newPlot('plot_0', r.traces, r.layout, cfg);
                    if (r.js_text_post) { eval(r.js_text_post); }

                    // Keep the chart sized to its (flex) container and reflow it
                    // when the window is resized. Registered once.
                    if (!window.__hqResizeBound) {
                        window.__hqResizeBound = true;
                        window.addEventListener('resize', function() {
                            var g = document.getElementById('plot_0');
                            if (g && g.data) { try { Plotly.Plots.resize(g); } catch (e) {} }
                        });
                    }
                    // Initial fit (container just became visible).
                    try { Plotly.Plots.resize(document.getElementById('plot_0')); } catch (e) {}

                    // Server-provided full data bounds for this load.
                    var fullMinIso = (r.layout && r.layout.xaxis && r.layout.xaxis.range)
                                   ? r.layout.xaxis.range[0] : null;
                    var fullMaxIso = (r.layout && r.layout.xaxis && r.layout.xaxis.range)
                                   ? r.layout.xaxis.range[1] : null;

                    function isoToFr(v) {
                        if (!v) { return ''; }
                        var datePart = String(v).split(' ')[0];
                        var p = datePart.split('-');
                        if (p.length !== 3) { return ''; }
                        return p[2] + '-' + p[1] + '-' + p[0]; // dd-mm-yyyy
                    }

                    // Remember the global (unzoomed) range whenever this load
                    // is a "full view" load — i.e. not a reload restricted to a
                    // zoomed sub-period. captureGlobalView is set by the caller.
                    if (captureGlobalView) {
                        globalDateMin = isoToFr(fullMinIso);
                        globalDateMax = isoToFr(fullMaxIso);
                    }

                    // -----------------------------------------------------------
                    // Period is owned by SELECTION + manual typing only.
                    // Zoom/pan no longer writes the date fields (like the
                    // chronicle-correction module). The green band is drawn on
                    // the STAGE subplot (y) and the fields define the period the
                    // H→Q conversion will process.
                    (function() {
                        var gd = document.getElementById('plot_0');
                        if (!gd || !gd.on) { return; }

                        // ---- ETL timeline bar click → open modif_etl.php ----
                        gd.on('plotly_click', function(ev) {
                            if (!ev || !ev.points || !ev.points.length) { return; }
                            var pt = null;
                            for (var i = 0; i < ev.points.length; i++) {
                                if (ev.points[i].data && ev.points[i].data.meta === 'etlBar') {
                                    pt = ev.points[i]; break;
                                }
                            }
                            if (!pt || !pt.customdata) { return; }
                            var idEtl = parseInt(pt.customdata[4], 10);
                            if (!idEtl || idEtl <= 0) { return; }
                            var url = 'modif_etl.php?st=<?php echo (int)$id_station; ?>&id_etl=' + idEtl;
                            var a = document.createElement('a');
                            a.href = url; a.target = '_blank'; a.rel = 'noopener';
                            document.body.appendChild(a); a.click(); document.body.removeChild(a);
                        });

                        // Pointer cursor when hovering an ETL bar marker.
                        var dragLayerEtl = gd.querySelector('.nsewdrag') || gd;
                        gd.on('plotly_hover', function(ev) {
                            if (!ev || !ev.points || !ev.points.length) { return; }
                            for (var i = 0; i < ev.points.length; i++) {
                                if (ev.points[i].data && ev.points[i].data.meta === 'etlBar') {
                                    dragLayerEtl.style.setProperty('cursor', 'pointer', 'important');
                                    return;
                                }
                            }
                        });
                        gd.on('plotly_unhover', function() {
                            dragLayerEtl.style.setProperty('cursor', '', '');
                        });

                        // ---- Range selection on the STAGE subplot (no zoom) ----
                        attachStageSelection(gd);
                        renderStageBandFromInputs(gd);
                    })();
                } else if (r['js_text']) {
                    // Backwards-compat fallback for legacy responses.
                    eval(r['js_text']);
                }

                boxGraphWait.style.display  = 'none';
                if (barreProgress) { barreProgress.style.display = 'none'; }

                if (typeof onDone === 'function') { onDone(); }
            }
        };
        xhr.send(JSON.stringify({
            timezone_php:   '<?php echo $timezone_php ?>',
            idStation:      <?php echo $id_station; ?>,
            typedataChronH: typedataChronH,
            typedataChronQ: typedataChronQ,
            colorH:         colorH.value,
            colorQ:         colorQ.value,
            checkLacH:      checkLacH.checked,
            checkLacQ:      checkLacQ.checked,
            reload:         reload,
            xDateMin:       xDateMin.value,
            xDateMax:       xDateMax.value
        }));
    }


    // =================================================================
    // STAGE selection (no zoom) — drives the conversion period.
    //
    // Same single-source-of-truth model as the chronicle-correction module:
    // the date fields (#x_date_min / #x_date_max, dd-mm-yyyy) ARE the state.
    // A shift-drag (or the select toolbar tool) on the stage subplot writes
    // the fields and draws a green band; the band is rendered from the fields.
    // =================================================================

    var __hqHasSelection = false;

    function __hqIsoToFr(v) {
        if (!v) { return ''; }
        var d = String(v).split(' ')[0].split('-');
        if (d.length !== 3) { return ''; }
        return d[2] + '-' + d[1] + '-' + d[0];
    }

    function __hqValToStr(v) {
        if (typeof v === 'string') { return v.replace('T', ' ').split('.')[0]; }
        var d = new Date(v);
        var p = function(n){ return (n < 10 ? '0' : '') + n; };
        return d.getUTCFullYear() + '-' + p(d.getUTCMonth()+1) + '-' + p(d.getUTCDate())
             + ' ' + p(d.getUTCHours()) + ':' + p(d.getUTCMinutes()) + ':' + p(d.getUTCSeconds());
    }

    // WRITE STATE from two 'YYYY-MM-DD HH:MM:SS' bounds.
    function setStageSelection(sx1, sx2) {
        if (sx1 > sx2) { var t = sx1; sx1 = sx2; sx2 = t; }
        xDateMin.value = __hqIsoToFr(sx1);
        xDateMax.value = __hqIsoToFr(sx2);
        __hqHasSelection = true;
        var gd = document.getElementById('plot_0');
        renderStageBandFromInputs(gd);
    }

    function clearStageSelection() {
        __hqHasSelection = false;
        var gd = document.getElementById('plot_0');
        renderStageBandFromInputs(gd);
    }

    // RENDER the green band on the STAGE subplot only (xref x / yref y domain).
    function renderStageBandFromInputs(gd) {
        if (!gd || !gd.layout) { return; }
        var shapes = (gd.layout.shapes || []).filter(function(s){ return s.name !== 'hp_stage_band'; });

        if (__hqHasSelection && xDateMin.value && xDateMax.value) {
            var sx1 = xDateMin.value.split('-').reverse().join('-') + ' 00:00:00';
            var sx2 = xDateMax.value.split('-').reverse().join('-') + ' 23:59:59';
            if (sx1 > sx2) { var t = sx1; sx1 = sx2; sx2 = t; }
            shapes.push({
                name: 'hp_stage_band',
                type: 'rect', xref: 'x', yref: 'y domain',
                x0: sx1, x1: sx2, y0: 0, y1: 1,
                fillcolor: 'rgba(29,158,117,0.15)',
                line: { width: 0 }, layer: 'below'
            });
        }
        // Only updates the band shape. The native selection rectangle is
        // cleared at the END of a selection (plotly_selected), not here —
        // doing it during the continuous 'selecting' stream would fight the
        // ongoing drag.
        try { Plotly.relayout(gd, { shapes: shapes }); } catch (e) {}
    }

    // Attach range-selection handlers to the stage subplot.
    function attachStageSelection(gd) {
        if (!gd) { return; }
        if (typeof gd.removeAllListeners === 'function') {
            gd.removeAllListeners('plotly_selecting');
            gd.removeAllListeners('plotly_selected');
            gd.removeAllListeners('plotly_doubleclick');
        }
        // Only ever read the CURRENT drag rectangle (ev.range.x). We avoid
        // ev.selections because, across successive selections, Plotly can keep
        // previous ones and report their union — which made a 2nd conversion
        // span "start of the 1st → end of the 2nd". Each selection must map to
        // exactly one conversion period.
        function bounds(ev) {
            if (ev && ev.range && ev.range.x) { return ev.range.x; }
            return null;
        }
        // A selection counts when the user explicitly selected: either Shift is
        // held, or the box-select tool is the active drag mode. A plain zoom
        // (dragmode 'zoom') never emits plotly_selecting, so it can't write the
        // period.
        function selectionAllowed() {
            if (__hqShiftActive) { return true; }
            var dm = gd._fullLayout && gd._fullLayout.dragmode;
            return (dm === 'select' || dm === 'lasso');
        }
        gd.on('plotly_selecting', function(ev){
            if (!selectionAllowed()) { return; }
            var x = bounds(ev); if (x) { setStageSelection(__hqValToStr(x[0]), __hqValToStr(x[1])); }
        });
        gd.on('plotly_selected',  function(ev){
            if (!selectionAllowed()) {
                try { Plotly.relayout(gd, { selections: [] }); } catch (e) {}
                return;
            }
            var x = bounds(ev);
            if (x) { setStageSelection(__hqValToStr(x[0]), __hqValToStr(x[1])); }
            // Drop the native selection so the next drag starts fresh (no union).
            try { Plotly.relayout(gd, { selections: [] }); } catch (e) {}
        });
        gd.on('plotly_doubleclick', function(){ clearStageSelection(); });
    }

    // Shift held → temporarily switch to horizontal range-select on the stage.
    var __hqShiftActive = false;
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Shift' && !__hqShiftActive) {
            __hqShiftActive = true;
            var gd = document.getElementById('plot_0');
            if (gd && gd.data) { try { Plotly.relayout(gd, { dragmode: 'select', selectdirection: 'h' }); } catch (er) {} }
        }
    });
    document.addEventListener('keyup', function(e) {
        if (e.key === 'Shift' && __hqShiftActive) {
            var gd = document.getElementById('plot_0');
            // Delay clearing the flag + dragmode so the selection's final
            // 'plotly_selected' burst (which can fire just after keyup) is still
            // attributed to a Shift selection and writes the period.
            setTimeout(function(){
                __hqShiftActive = false;
                if (gd && gd.data) { try { Plotly.relayout(gd, { dragmode: 'zoom' }); } catch (er) {} }
            }, 300);
        }
    });

    if (typedataChronH > 0) { load_graph(); }

    selectH.addEventListener('change', function() { load_graph(true); });
    selectQ.addEventListener('change', function() { load_graph(true); });

    function applyGapShapes()
    {
        var gd = document.getElementById('plot_0');
        if (!gd || !gd.layout) { return; }

        // Start from the CURRENT shapes and drop only the gap shapes we own
        // (tagged hp_gap_*). This preserves the ETL timeline bars/hatches
        // (yref y3) and the green selection band (hp_stage_band).
        var shapes = (gd.layout.shapes || []).filter(function(s){
            return !(s && typeof s.name === 'string' && s.name.indexOf('hp_gap_') === 0);
        });

        // Re-add the checked series' gaps, tagged so we can find them next time.
        if (checkLacH.checked && window.gapShapesH) {
            window.gapShapesH.forEach(function(s, i){
                var c = Object.assign({}, s); c.name = 'hp_gap_h_' + i; shapes.push(c);
            });
        }
        if (checkLacQ.checked && window.gapShapesQ) {
            window.gapShapesQ.forEach(function(s, i){
                var c = Object.assign({}, s); c.name = 'hp_gap_q_' + i; shapes.push(c);
            });
        }

        Plotly.relayout(gd, { shapes: shapes });
    }

    checkLacH.addEventListener('click', applyGapShapes);
    checkLacQ.addEventListener('click', applyGapShapes);


    // -----------------------------------------------
    // convertCQ() — batch conversion (recursive)
    //
    // The recursive call accumulates per-batch counters into convertStats
    // (defined just above, reset at the start of each run). When the
    // server reports remaining=false, we render the final summary block
    // in the floating log popup.

    var convertStats = null;

    function convertCQ()
    {
        // First batch only: reset counters and open the log popup.
        // We DO NOT clear the log: history persists across runs until
        // the user explicitly clicks the trash button (or reloads).
        if (offSet === 0)
        {
            convertStats = { converted: 0, gaps_source: 0, above: 0, below: 0, nocov: 0 };
            hqLog.show();
            hqLog.line(LOG_TXT.start + " : " + xDateMin.value + " → " + xDateMax.value, true);
            // Workflow indicator: step 2 (Conversion) becomes active.
            hqStepper.set(2, 'active');
        }

        msgInfo.style.display       = 'none';
        bConvert.style.display      = 'none';
        bValid.style.display        = 'none';
        saveWait.style.display      = 'none';
        convertWait.style.display   = 'block';
        // Keep the chart (stage + timeline) visible during conversion; show a
        // localized loading overlay over the discharge band only.
        showDischargeLoading(true);
        if (barreProgress) { barreProgress.style.display = 'block'; }

        typedataChronH = selectH.value;
        typedataChronQ = selectQ.value;

        var xhr = new XMLHttpRequest();
        xhr.open("POST", "include/structure/converthq/process_convert_data.php", true);
        xhr.setRequestHeader("Content-Type", "application/json");

        xhr.onreadystatechange = function()
        {
            if (xhr.readyState !== 4) { return; }
            if (xhr.status !== 200)
            {
                hqLog.line("<b style='color:#A32D2D'>" + LOG_TXT.bad_response + "</b>");
                hqStepper.set(2, 'error');
                return;
            }

            var r;
            try   { r = JSON.parse(xhr.responseText); }
            catch (e)
            {
                hqLog.line("<b style='color:#A32D2D'>" + LOG_TXT.bad_response + "</b>");
                hqStepper.set(2, 'error');
                return;
            }

            // First-batch-only info — log ETL discovery
            if (r['nb_etl'] && r['nb_etl'] > 0)
            {
                hqLog.line(r['nb_etl']      + " " + LOG_TXT.etl_found);
                hqLog.line(r['nb_segments'] + " " + LOG_TXT.seg_ready);
                hqLog.line(LOG_TXT.cvt_start);
            }

            // Accumulate per-batch counters into the running totals
            convertStats.converted   += (r['nb_converted']   | 0);
            convertStats.gaps_source += (r['nb_gaps_source'] | 0);
            convertStats.above       += (r['nb_above']       | 0);
            convertStats.below       += (r['nb_below']       | 0);
            convertStats.nocov       += (r['nb_nocov']       | 0);

            if (r['remaining'])
            {
                dateFirstProcess   = r['newCursorDate'];
                id_meta_correction = r['id_meta_correction'];

                offSet += 10000;
                var pc = Math.min(((offSet / nb_data_all) * 100), 100);
                if (pourcentageCompil) { pourcentageCompil.style.width = pc + '%'; }
                convertCQ();
            }
            else
            {
                id_meta_correction = r['id_meta_correction'];

                hqLog.line(LOG_TXT.cvt_done);
                hqLog.summary(convertStats);
                hqLog.line("<b>" + LOG_TXT.ready_valid + "</b>");

                // Step 2 done — step 3 (Save) is now ready to be triggered.
                hqStepper.set(2, 'done');

                // ---- Refresh ONLY the discharge "pending" trace ----
                // Instead of a full reload (which would reset the zoom and move
                // the stage / timeline subplots), fetch just the converted
                // discharge series and restyle the existing pending trace in
                // place. The current view (zoom/pan) is left untouched.
                refreshPendingDischarge(function()
                {
                    showDischargeLoading(false);
                    bConvert.style.display    = 'block';
                    bValid.style.display      = 'block';
                    convertWait.style.display = 'none';
                });
            }
        };
        xhr.send(JSON.stringify({
            timezone_php:       '<?php echo $timezone_php ?>',
            dateFirstProcess:   dateFirstProcess,
            id_meta_correction: id_meta_correction,
            isFirstBatch:       (offSet === 0),
            typedataChronH:     typedataChronH,
            typedataChronQ:     typedataChronQ,
            xDateMin:           xDateMin.value,
            xDateMax:           xDateMax.value,
            idStation:          <?php echo $id_station; ?>,
            id_user:            <?php echo $id_user; ?>
        }));
    }


    // -----------------------------------------------
    // showDischargeLoading(on) — toggle a small loading overlay positioned over
    // the discharge (middle) subplot, leaving stage + timeline visible.

    function showDischargeLoading(on)
    {
        var ov = document.getElementById('discharge_loading');
        var gd = document.getElementById('plot_0');
        if (!ov || !gd) { return; }

        if (!on) { ov.style.display = 'none'; return; }

        // Discharge band ≈ y2 domain [0.16, 0.54] → from top that is
        // (1-0.54)=46% .. (1-0.16)=84% of the plot height. Centre ≈ 60%.
        var h = gd.offsetHeight || 0;
        ov.style.top     = Math.round(h * 0.52) + 'px';
        ov.style.display = 'block';
    }


    // -----------------------------------------------
    // showSuccessCard(msg) — bold, centered confirmation over the discharge
    // band after a save. Pops in, holds, then fades out.

    var __hqSuccessTimer = null;
    function showSuccessCard(msg)
    {
        var box = document.getElementById('hq_success');
        var gd  = document.getElementById('plot_0');
        if (!box || !gd) { return; }

        var txt = box.querySelector('.hq-success-text');
        if (txt) { txt.textContent = msg || ''; }

        var h = gd.offsetHeight || 0;
        box.style.top = Math.round(h * 0.46) + 'px';
        box.classList.remove('hq-fade-out');
        box.style.display = 'block';

        if (__hqSuccessTimer) { clearTimeout(__hqSuccessTimer); }
        __hqSuccessTimer = setTimeout(function() {
            box.classList.add('hq-fade-out');
            setTimeout(function() {
                box.style.display = 'none';
                box.classList.remove('hq-fade-out');
            }, 550);
        }, 2600);
    }


    // -----------------------------------------------
    // refreshPendingDischarge(onDone) — update only the discharge "pending"
    // trace after a conversion, without re-rendering the whole chart.

    function refreshPendingDischarge(onDone)
    {
        var gd = document.getElementById('plot_0');
        if (!gd || !gd.data) { if (typeof onDone === 'function') { onDone(); } return; }

        var xhr = new XMLHttpRequest();
        xhr.open("POST", "include/structure/converthq/process_convert_graph_q.php", true);
        xhr.setRequestHeader("Content-Type", "application/json");

        xhr.onreadystatechange = function()
        {
            if (xhr.readyState !== 4) { return; }

            var applied = false;
            if (xhr.status === 200)
            {
                try {
                    var r = JSON.parse(xhr.responseText);

                    // Find the existing pending trace (legendgroup 'tdc_pending').
                    var idx = -1;
                    for (var i = 0; i < gd.data.length; i++) {
                        if (gd.data[i].legendgroup === 'tdc_pending') { idx = i; break; }
                    }

                    if (idx >= 0) {
                        Plotly.restyle(gd, {
                            x:          [r.x],
                            y:          [r.y],
                            customdata: [r.customdata]
                        }, [idx]);
                        applied = true;
                    } else if (r.nb > 0) {
                        Plotly.addTraces(gd, {
                            x: r.x, y: r.y, customdata: r.customdata,
                            name: '<?php echo addslashes(defined("TEXT_HQ_TRACE_PENDING") ? TEXT_HQ_TRACE_PENDING : "(pending)"); ?>',
                            xaxis: 'x2', yaxis: 'y2',
                            legendgroup: 'tdc_pending',
                            mode: 'lines',
                            line: { color: '#D946EF' },
                            type: 'scattergl'
                        });
                        applied = true;
                    } else {
                        // Endpoint returned 0 points. If a pending trace already
                        // exists, clear it; otherwise fall back to a full reload
                        // (the conversion data should be there — don't show blank).
                        if (idx >= 0) {
                            Plotly.restyle(gd, { x: [[]], y: [[]], customdata: [[]] }, [idx]);
                            applied = true;
                        } else {
                            applied = false; // triggers the full-reload fallback below
                        }
                    }
                } catch (e) {
                    if (window.console) { console.error('refreshPendingDischarge parse error:', e, xhr.responseText); }
                }
            }

            // Fallback: if the partial refresh could not be applied (endpoint
            // error / bad JSON), do a full reload so the conversion still shows.
            if (!applied) {
                load_graph(false, function() { if (typeof onDone === 'function') { onDone(); } });
                return;
            }

            if (typeof onDone === 'function') { onDone(); }
        };
        xhr.send(JSON.stringify({
            idStation:      <?php echo $id_station; ?>,
            typedataChronQ: typedataChronQ
        }));
    }


    // -----------------------------------------------
    // refreshDischargeAfterSave(onDone) — after a save, the converted data is
    // now the OFFICIAL series and the pending proposal is gone. Update the
    // official discharge trace (tdc_q) and clear the pending trace (tdc_pending)
    // in place, leaving stage / timeline / zoom untouched.

    function refreshDischargeAfterSave(onDone)
    {
        var gd = document.getElementById('plot_0');
        if (!gd || !gd.data) { if (typeof onDone === 'function') { onDone(); } return; }

        var xhr = new XMLHttpRequest();
        xhr.open("POST", "include/structure/converthq/process_convert_graph_q.php", true);
        xhr.setRequestHeader("Content-Type", "application/json");

        xhr.onreadystatechange = function()
        {
            if (xhr.readyState !== 4) { return; }

            var applied = false;
            if (xhr.status === 200)
            {
                try {
                    var r = JSON.parse(xhr.responseText);

                    var idxQ = -1, idxP = -1;
                    for (var i = 0; i < gd.data.length; i++) {
                        if (gd.data[i].legendgroup === 'tdc_q')       { idxQ = i; }
                        if (gd.data[i].legendgroup === 'tdc_pending') { idxP = i; }
                    }

                    // Update the official discharge series.
                    if (idxQ >= 0) {
                        Plotly.restyle(gd, { x: [r.x_q], y: [r.y_q], customdata: [r.customdata_q] }, [idxQ]);
                        applied = true;
                    }
                    // Clear the pending proposal (now validated / merged).
                    if (idxP >= 0) {
                        Plotly.restyle(gd, { x: [[]], y: [[]], customdata: [[]] }, [idxP]);
                    }
                } catch (e) {
                    if (window.console) { console.error('refreshDischargeAfterSave parse error:', e, xhr.responseText); }
                }
            }

            // Fallback to a full reload if the official trace could not update.
            if (!applied) {
                load_graph(true, function() { if (typeof onDone === 'function') { onDone(); } });
                return;
            }

            if (typeof onDone === 'function') { onDone(); }
        };
        xhr.send(JSON.stringify({
            idStation:      <?php echo $id_station; ?>,
            typedataChronQ: typedataChronQ
        }));
    }

    if (bConvert)
    {
        bConvert.addEventListener('click', function()
        {
            // Reset all state before a fresh conversion run.
            //
            // Why every var matters:
            //   - id_meta_correction : if left non-zero, the first batch on
            //     the server reuses the previous meta record instead of
            //     creating a new one.
            //   - offSet             : drives the progress bar and the
            //     "first batch" flag (offSet === 0).
            //   - dateFirstProcess   : the SQL cursor. If left at the end
            //     of the previous run, the new run finds no rows to process.
            //   - pourcentageCompil  : visual reset to 0% width.
            id_meta_correction            = 0;
            offSet                        = 0;
            dateFirstProcess              = xDateMin.value.split('-').reverse().join('-') + ' 00:00:00';
            if (pourcentageCompil) { pourcentageCompil.style.width = '0%'; }

            convertCQ();
        });
    }


    // -----------------------------------------------
    // convertValid() — save the converted data series

    function convertValid()
    {
        msgInfo.style.display      = 'none';
        bConvert.style.display     = 'none';
        bValid.style.display       = 'none';
        convertWait.style.display  = 'none';
        saveWait.style.display     = 'block';
        // Keep the chart (stage + timeline) visible during save; only the
        // discharge band shows a loading overlay — same UX as a conversion.
        showDischargeLoading(true);

        // Reuse the same floating log popup as the conversion flow
        hqLog.show();
        hqLog.line(LOG_TXT.save_start, true);
        // Workflow indicator: step 3 (Save) becomes active.
        hqStepper.set(3, 'active');

        typedataChronQ = selectQ.value;

        var xhr = new XMLHttpRequest();
        xhr.open("POST", "include/structure/converthq/process_convert_valid.php", true);
        xhr.setRequestHeader("Content-Type", "application/json");

        xhr.onreadystatechange = function()
        {
            if (xhr.readyState !== 4) { return; }
            if (xhr.status !== 200)
            {
                showDischargeLoading(false);
                hqLog.line("<b style='color:#A32D2D'>" + LOG_TXT.save_bad_response + "</b>");
                hqStepper.set(3, 'error');
                return;
            }

            hqLog.line(LOG_TXT.save_done);
            hqLog.line("<b style='color:#1D9E75'>" + LOG_TXT.save_success + "</b>");

            // All three steps now complete — clean run end state.
            hqStepper.set(3, 'done');

            // After save, the converted data became the OFFICIAL series and the
            // pending proposal is gone. Refresh only the discharge traces
            // (official + clear pending); stage, timeline and zoom stay put.
            refreshDischargeAfterSave(function()
            {
                showDischargeLoading(false);
                saveWait.style.display = 'none';
                bConvert.style.display = 'block';

                // Bold, centered confirmation over the discharge band.
                showSuccessCard(msgSaved);
            });

            id_meta_correction = 0;
            offSet             = 0;
        };
        xhr.send(JSON.stringify({
            timezone_php:       '<?php echo $timezone_php ?>',
            typedataChronQ:     typedataChronQ,
            xDateMin:           xDateMin.value,
            xDateMax:           xDateMax.value,
            idStation:          <?php echo $id_station; ?>,
            id_user:            <?php echo $id_user; ?>,
            id_meta_correction: id_meta_correction
        }));
    }

    // -----------------------------------------------
    // Validate flow — confirmation popup before saving.
    //
    // Clicking the "Validate" button no longer runs convertValid()
    // directly. Instead, it opens the box_verif_save_hq popup, which:
    //   1. Pre-selects the same Q target series the left-panel <select>
    //      currently points at.
    //   2. Asks the user to solve a small math challenge.
    //   3. On Confirm, synchronises the left-panel <select> with the
    //      (possibly modified) selection from the popup, then calls
    //      convertValid().

    function openSaveHqPopup()
    {
        var popupSelect    = document.getElementById('select_type_chron_hq');
        var currentLabel   = document.getElementById('current_chron_hq_label');
        var leftPanelValue = selectQ.value;
        var leftPanelText  = selectQ.options[selectQ.selectedIndex]
                                ? selectQ.options[selectQ.selectedIndex].text
                                : '';

        // Mirror the left-panel selection inside the popup dropdown.
        if (popupSelect)
        {
            for (var i = 0; i < popupSelect.options.length; i++)
            {
                if (popupSelect.options[i].value == leftPanelValue)
                {
                    popupSelect.selectedIndex = i;
                    break;
                }
            }
        }

        // Refresh the "Current series" banner with the left-panel text.
        if (currentLabel) { currentLabel.textContent = leftPanelText; }

        // Show the popup — the MutationObserver in block_verif_save_hq.php
        // detects this and generates a fresh math challenge.
        boxVerifSaveHq.style.display = 'block';
    }

    if (bValid)
    {
        bValid.addEventListener('click', openSaveHqPopup);
    }

    if (okValidSaveHq)
    {
        okValidSaveHq.addEventListener('click', function()
        {
            if (okValidSaveHq.disabled) { return; }

            var popupSelect = document.getElementById('select_type_chron_hq');
            var chosenValue = popupSelect ? popupSelect.value : selectQ.value;

            // Synchronise the left-panel <select> with the popup choice
            // so subsequent code paths read the correct target series.
            for (var i = 0; i < selectQ.options.length; i++)
            {
                if (selectQ.options[i].value == chosenValue)
                {
                    selectQ.selectedIndex = i;
                    break;
                }
            }

            // Close the popup and trigger the actual save.
            boxVerifSaveHq.style.display = 'none';
            convertValid();
        });
    }


    // -----------------------------------------------
    // Adjust scale button

    // "Apply period" — draw the green selection band from the typed dates,
    // WITHOUT zooming. The fields define the conversion period.
    function applyPeriodFromFields()
    {
        if (!isValidDatesInput()) { return; }
        var sx1 = xDateMin.value.trim().split('-').reverse().join('-') + ' 00:00:00';
        var sx2 = xDateMax.value.trim().split('-').reverse().join('-') + ' 23:59:59';
        setStageSelection(sx1, sx2);
    }


    // -----------------------------------------------
    // Date validation helpers

    function isValidDate(ds)
    {
        if (!/^(0[1-9]|[12][0-9]|3[01])-(0[1-9]|1[0-2])-(\d{4})$/.test(ds)) { return false; }
        var p = ds.split('-').map(Number);
        var d = new Date(p[2], p[1] - 1, p[0]);
        return d.getFullYear() === p[2] && d.getMonth() === p[1] - 1 && d.getDate() === p[0];
    }

    function parseDate(ds)
    {
        var p = ds.split('-').map(Number);
        return new Date(p[2], p[1] - 1, p[0]);
    }

    function isValidDatesInput()
    {
        if (isValidDate(xDateMin.value) && isValidDate(xDateMax.value))
        {
            if (parseDate(xDateMin.value) < parseDate(xDateMax.value)) { return true; }
            msgInfo.innerText     = msgErrOrder;
            msgInfo.style.display = 'block';
            return false;
        }
        msgInfo.innerText     = msgErrFormat;
        msgInfo.style.display = 'block';
        return false;
    }


    // -----------------------------------------------
    // Color picker — toggle/select/close handlers
    //
    // Mirrors the implementation used on graph_chron so the visual
    // behaviour is identical here:
    //   - the .color-grid popup is position:fixed so it escapes the
    //     left sidebar's overflow:auto;
    //   - toggleDropdownColor() computes top/left from the swatch's
    //     screen coordinates and flips above / shifts left if the
    //     popup would overflow the viewport;
    //   - selectColor() updates the swatch, the hidden input, the
    //     "is-selected" highlight, and re-styles every matching Plotly
    //     trace via its legendgroup ("tdc_h" or "tdc_q") so that the
    //     change applies regardless of trace index.

    function toggleDropdownColor(key)
    {
        var dropdown = document.getElementById('dropdownList_' + key);
        if (!dropdown) { return; }

        // Close any other open grid (only one at a time)
        document.querySelectorAll('.color-grid.is-open').forEach(function(g) {
            if (g !== dropdown) { g.classList.remove('is-open'); }
        });

        var isOpen = dropdown.classList.contains('is-open');
        if (isOpen) { dropdown.classList.remove('is-open'); return; }

        var swatch = document.getElementById('selectedColor_' + key);
        if (swatch)
        {
            var rect       = swatch.getBoundingClientRect();
            var gridWidth  = 192;
            var gridHeight = 230;
            var margin     = 4;

            var top  = rect.bottom + margin;
            var left = rect.left;

            if (top + gridHeight > window.innerHeight)
            {
                top = Math.max(margin, rect.top - gridHeight - margin);
            }
            if (left + gridWidth > window.innerWidth)
            {
                left = Math.max(margin, window.innerWidth - gridWidth - margin);
            }

            dropdown.style.top  = top  + 'px';
            dropdown.style.left = left + 'px';
        }

        dropdown.classList.add('is-open');
    }

    function selectColor(color, key)
    {
        document.getElementById('selectedColor_' + key).style.backgroundColor = color;
        document.getElementById('dropdownList_'  + key).classList.remove('is-open');
        document.getElementById('input_color_'   + key).value = color;

        // Refresh the "is-selected" highlight inside the grid
        var grid = document.getElementById('dropdownList_' + key);
        if (grid)
        {
            grid.querySelectorAll('.color-cell').forEach(function(cell) {
                var cellColor = rgbToHex(cell.style.backgroundColor);
                if (cellColor && cellColor.toLowerCase() === color.toLowerCase())
                {
                    cell.classList.add('is-selected');
                }
                else
                {
                    cell.classList.remove('is-selected');
                }
            });
        }

        // Apply the color to every trace tagged with the matching legendgroup
        var plotDiv = document.getElementById('plot_0');
        if (plotDiv && plotDiv.data)
        {
            var idxs = [];
            for (var i = 0; i < plotDiv.data.length; i++)
            {
                if ((plotDiv.data[i] || {}).legendgroup === ('tdc_' + key)) { idxs.push(i); }
            }
            if (idxs.length)
            {
                Plotly.restyle(plotDiv, {
                    'marker.color':      color,
                    'marker.line.color': color,
                    'line.color':        color
                }, idxs);
            }
        }

        // Keep the gap shapes (window.gapShapesH / Q) in sync with the
        // trace colour. The shapes were generated server-side with a
        // fixed fillcolor, so we walk the array and overwrite it before
        // re-pushing through applyGapShapes() — which will respect the
        // checkLacH / checkLacQ checkboxes the user may have toggled.
        var gapBucket = (key === 'h') ? window.gapShapesH
                       : (key === 'q') ? window.gapShapesQ
                       : null;
        if (Array.isArray(gapBucket))
        {
            for (var g = 0; g < gapBucket.length; g++)
            {
                if (gapBucket[g]) { gapBucket[g].fillcolor = color; }
            }
            applyGapShapes();
        }
    }

    // Helper — convert "rgb(r, g, b)" to "#rrggbb" (browsers normalize hex
    // colors in style attributes to rgb()).
    function rgbToHex(rgb)
    {
        if (!rgb) { return ''; }
        if (rgb[0] === '#') { return rgb; }
        var match = rgb.match(/^rgba?\((\d+),\s*(\d+),\s*(\d+)/);
        if (!match) { return rgb; }
        return '#' + ((1 << 24)
                    + (parseInt(match[1]) << 16)
                    + (parseInt(match[2]) <<  8)
                    +  parseInt(match[3]))
                    .toString(16).slice(1);
    }

    // Close any open grid when clicking outside a picker
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.color-dropdown'))
        {
            document.querySelectorAll('.color-grid.is-open').forEach(function(g) {
                g.classList.remove('is-open');
            });
        }
    });

</script>