<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Gauging tab (JGE) - One sheet per arm (bras)
- Data entry and display for a single gauging arm
- Includes helical flowmeter equation, measurement points,
  cross-section graph, and computed hydraulic results
----------------------------------------
*/


// -----------------------------------------------
// Initialize helical flowmeter equation variables

$l1 = 0; $a1 = 0; $b1 = 0;
$l2 = 0; $a2 = 0; $b2 = 0;
$a3 = 0; $b3 = 0;


// -----------------------------------------------
// Query: Retrieve measurement points for this arm
// $nbb is the arm number variable passed from the parent

$modif_bras = false;
if (isset($jge_bras_array[$nbb])) { $modif_bras = true; }

$jge_pts_array       = [];
$num_vert_encours    = 0;
$dist_encours        = 0;
$prof_max_encours    = 0;

$html_hidden_jge_pts = '';
$nb_jge_pts_all      = 100;
$nb_jge_pts          = 0;

if ($modif_bras)
{
    if (isset($jge_bras_array[$nbb]))
    {
        $sql_jge_pts = "SELECT DISTINCT id, id_bras, num_vert, dist_depart, prof_max, prof_pts,
                                        nb_tours, tps_pts, obs, dist_calc, debit_lam, prof_calc,
                                        vitesse_calc, vitesse_surf, vitesse_fond, vitesse_moy
                        FROM " . TABLE_DATA_JGE_PTS . "
                        WHERE id_bras=" . $jge_bras_array[$nbb]['id_bras'] . "
                        ORDER BY dist_depart ASC, prof_pts DESC";

        $jge_pts_query = tep_db_query($sql_link, $sql_jge_pts);
        while ($jge_pts = tep_db_fetch_array($jge_pts_query))
        {
            // Input data
            $dist_depart = round(floatval($jge_pts['dist_depart']), 3);
            $prof_max    = round(floatval($jge_pts['prof_max']),    3);
            $prof_pts    = round(floatval($jge_pts['prof_pts']),    3);
            $nb_tours    = intval($jge_pts['nb_tours']);
            $tps_pts     = intval($jge_pts['tps_pts']);

            // Auto-renumber verticals by distance
            if ($dist_depart > $dist_encours)
            {
                $num_vert_encours++;
                $dist_encours     = $dist_depart;
                $prof_max_encours = $prof_max;
            }
            else
            {
                $dist_depart = $dist_encours;
                $prof_max    = $prof_max_encours;
            }
            $num_vert = $num_vert_encours;

            // Computed data
            $dist_calc    = round(floatval($jge_pts['dist_calc']),    3);
            $debit_lam    = round(floatval($jge_pts['debit_lam']),    3);
            $prof_calc    = round(floatval($jge_pts['prof_calc']),    3);
            $vitesse_calc = round(floatval($jge_pts['vitesse_calc']), 3);
            $vitesse_surf = round(floatval($jge_pts['vitesse_surf']), 3);
            $vitesse_fond = round(floatval($jge_pts['vitesse_fond']), 3);
            $vitesse_moy  = round(floatval($jge_pts['vitesse_moy']),  3);

            $obs = html_entity_decode($jge_pts['obs'] ?? '');

            $nb_tour_sec = ($tps_pts > 0) ? round($nb_tours / $tps_pts, 3) : 0;

            // Build hidden input fields for this point
            $html_hidden_jge_pts .= "<input type='hidden' id='jge_bra_vert_"     . $nbb . "_" . $nb_jge_pts . "' name='jge_bra_vert_"     . $nbb . "_" . $nb_jge_pts . "' value='" . $num_vert    . "'>\n";
            $html_hidden_jge_pts .= "<input type='hidden' id='jge_bra_dist_"     . $nbb . "_" . $nb_jge_pts . "' name='jge_bra_dist_"     . $nbb . "_" . $nb_jge_pts . "' value='" . $dist_depart . "'>\n";
            $html_hidden_jge_pts .= "<input type='hidden' id='jge_bra_profmax_"  . $nbb . "_" . $nb_jge_pts . "' name='jge_bra_profmax_"  . $nbb . "_" . $nb_jge_pts . "' value='" . $prof_max    . "'>\n";
            $html_hidden_jge_pts .= "<input type='hidden' id='jge_bra_profmesure_" . $nbb . "_" . $nb_jge_pts . "' name='jge_bra_profmesure_" . $nbb . "_" . $nb_jge_pts . "' value='" . $prof_pts . "'>\n";
            $html_hidden_jge_pts .= "<input type='hidden' id='jge_bra_nbtour_"   . $nbb . "_" . $nb_jge_pts . "' name='jge_bra_nbtour_"   . $nbb . "_" . $nb_jge_pts . "' value='" . $nb_tours   . "'>\n";
            $html_hidden_jge_pts .= "<input type='hidden' id='jge_bra_tps_"      . $nbb . "_" . $nb_jge_pts . "' name='jge_bra_tps_"      . $nbb . "_" . $nb_jge_pts . "' value='" . $tps_pts    . "'>\n";
            $html_hidden_jge_pts .= "<input type='hidden' id='jge_bra_tourssec_" . $nbb . "_" . $nb_jge_pts . "' name='jge_bra_tourssec_" . $nbb . "_" . $nb_jge_pts . "' value='" . $nb_tour_sec . "'>\n";
            $html_hidden_jge_pts .= "<input type='hidden' id='jge_bra_vitesse_"  . $nbb . "_" . $nb_jge_pts . "' name='jge_bra_vitesse_"  . $nbb . "_" . $nb_jge_pts . "' value='" . $vitesse_calc . "'>\n";
            $html_hidden_jge_pts .= "<input type='hidden' id='jge_bra_obs_"      . $nbb . "_" . $nb_jge_pts . "' name='jge_bra_obs_"      . $nbb . "_" . $nb_jge_pts . "' value='" . $obs         . "'>\n";

            $nb_jge_pts++;
        }
    }
}

// Fill remaining hidden fields up to the maximum
// (indices 0 to 99: process_jge_save.php loops $num_pts < 100)
for ($i = $nb_jge_pts; $i < $nb_jge_pts_all; $i++)
{
    $html_hidden_jge_pts .= "<input type='hidden' id='jge_bra_vert_"     . $nbb . "_" . $i . "' name='jge_bra_vert_"     . $nbb . "_" . $i . "' value=''>\n";
    $html_hidden_jge_pts .= "<input type='hidden' id='jge_bra_dist_"     . $nbb . "_" . $i . "' name='jge_bra_dist_"     . $nbb . "_" . $i . "' value=''>\n";
    $html_hidden_jge_pts .= "<input type='hidden' id='jge_bra_profmax_"  . $nbb . "_" . $i . "' name='jge_bra_profmax_"  . $nbb . "_" . $i . "' value=''>\n";
    $html_hidden_jge_pts .= "<input type='hidden' id='jge_bra_profmesure_" . $nbb . "_" . $i . "' name='jge_bra_profmesure_" . $nbb . "_" . $i . "' value=''>\n";
    $html_hidden_jge_pts .= "<input type='hidden' id='jge_bra_nbtour_"   . $nbb . "_" . $i . "' name='jge_bra_nbtour_"   . $nbb . "_" . $i . "' value=''>\n";
    $html_hidden_jge_pts .= "<input type='hidden' id='jge_bra_tps_"      . $nbb . "_" . $i . "' name='jge_bra_tps_"      . $nbb . "_" . $i . "' value=''>\n";
    $html_hidden_jge_pts .= "<input type='hidden' id='jge_bra_tourssec_" . $nbb . "_" . $i . "' name='jge_bra_tourssec_" . $nbb . "_" . $i . "' value=''>\n";
    $html_hidden_jge_pts .= "<input type='hidden' id='jge_bra_vitesse_"  . $nbb . "_" . $i . "' name='jge_bra_vitesse_"  . $nbb . "_" . $i . "' value=''>\n";
    $html_hidden_jge_pts .= "<input type='hidden' id='jge_bra_obs_"      . $nbb . "_" . $i . "' name='jge_bra_obs_"      . $nbb . "_" . $i . "' value=''>\n";
}


// -----------------------------------------------
// Tab layout

echo "<div id='onglet_contenu' style='display:flex;flex-direction:column;height:calc(100vh - 240px);padding:10px;overflow-y:auto;'>\n";

    // Hidden arm ID
    if ($modif_bras) { echo "<input type='hidden' name='id_bras_" . $nbb . "' id='id_bras_" . $nbb . "' value='" . $jge_bras_array[$nbb]['id_bras'] . "'>"; }
    else             { echo "<input type='hidden' name='id_bras_" . ($nb_bras_tab + 1) . "' id='id_bras_" . ($nb_bras_tab + 1) . "' value='0'>"; }

    echo "<div id='boite1' class='first' style='margin:10px 0;margin-top:0px;'>\n";

        // ---- Time and gauge readings ----
        echo "<div style='float:left;width:200px;'>\n";

            // Start time
            echo "<div id='boite_small' style='width:100%'>\n";
                echo "<p style='float:left;font-weight:bold;color:#000;margin-top:8px;margin-bottom:10px;font-size:12px;'>" . TEXT_JGE_BRAS_HEURE_FIRST . "</p>";
                $value = $modif_bras ? $jge_bras_array[$nbb]['heure_first'] : '';
                echo "<input type='text' style='float:right;width:55px;height:10px;border:1px solid #930000;' 
                            id='heure_first_" . $nbb . "' name='heure_first_" . $nbb . "' 
                            placeholder='hh:mm:ss' 
                            value='" . $value . "'>\n";
            echo "</div>\n";

            // Start gauge
            echo "<div id='boite_small' style='width:100%'>\n";
                echo "<p style='float:left;font-weight:bold;color:#000;margin-top:8px;margin-bottom:10px;font-size:12px;'>" . TEXT_JGE_BRAS_ECH_FIRST . "</p>";
                $value = $modif_bras ? $jge_bras_array[$nbb]['h_ech_first'] : '';
                echo "<input type='text' style='float:right;width:55px;height:10px;border:1px solid #930000;' id='h_ech_first_" . $nbb . "' name='h_ech_first_" . $nbb . "' value='" . $value . "'>\n";
            echo "</div>\n";

            // End time
            echo "<div id='boite_small' style='width:100%'>\n";
                echo "<p style='float:left;font-weight:bold;color:#000;margin-top:8px;margin-bottom:10px;font-size:12px;'>" . TEXT_JGE_BRAS_HEURE_END . "</p>";
                $value = $modif_bras ? $jge_bras_array[$nbb]['heure_end'] : '';
                echo "<input type='text' style='float:right;width:55px;height:10px;border:1px solid #ddd;' 
                            id='heure_end_" . $nbb . "' name='heure_end_" . $nbb . "' 
                            placeholder='hh:mm:ss' 
                            value='" . $value . "'>\n";
            echo "</div>\n";

            // End gauge
            echo "<div id='boite_small' style='width:100%'>\n";
                echo "<p style='float:left;font-weight:bold;color:#000;margin-top:8px;margin-bottom:10px;font-size:12px;'>" . TEXT_JGE_BRAS_ECH_END . "</p>";
                $value = $modif_bras ? $jge_bras_array[$nbb]['h_ech_end'] : '';
                echo "<input type='text' style='float:right;width:55px;height:10px;border:1px solid #ddd;' id='h_ech_end_" . $nbb . "' name='h_ech_end_" . $nbb . "' value='" . $value . "'>\n";
            echo "</div>\n";

        echo "</div>\n";

        // ---- Riverbed type ----
        echo "<div style='float:left;width:300px;margin-left:40px;'>\n";

            echo "<div id='boite_small' style='width:100%;margin:0'>\n";

                echo "<p style='float:left;font-weight:bold;color:#000;margin-top:8px;margin-bottom:0px;font-size:12px;'>" . TEXT_JGE_BRAS_FOND . "</p>";

                $value = $modif_bras ? $jge_bras_array[$nbb]['fond_text'] : '';
                echo "<input type='text' style='float:left;width:200px;margin-left:15px;' id='fond_text_" . $nbb . "' name='fond_text_" . $nbb . "' value='" . $value . "'>\n";

                echo "<hr>";

                echo "<div style='float:left;width:100%;'>\n";

                    $value = $modif_bras ? $jge_bras_array[$nbb]['fond_text'] : '';

                    if (isset($data_jge_fondlit_array))
                    {
                        foreach ($data_jge_fondlit_array as $key => $value_fond)
                        {
                            $checked = (strpos($value, $value_fond['titre']) !== false) ? 'checked' : '';
                            echo "<div style='float:left;'>";
                                echo "<input type='checkbox' id='check_fondlit_" . $nbb . "_" . $key . "' name='check_fondlit_" . $nbb . "_" . $key . "' value='" . $key . "' data-value='" . $value_fond['titre'] . "' onchange='updateSelectedFond(" . $nbb . ");' " . $checked . "> ";
                                echo "<span style='font-size:10px;'>" . $value_fond['titre'] . "</span>";
                            echo "</div>";
                        }
                    }
                echo "</div>\n";   
                
                // Starting bank
                echo "<div id='boite_small' style='width:80%;margin-top:20px;'>\n";
                    echo "<p style='float:left;margin:0;font-weight:bold;color:#000;margin-top:8px;font-size:12px;'>" . TEXT_JGE_BRAS_BERGE . "</p>";
                    echo "<select name='select_berge1_" . $nbb . "' id='select_berge1_" . $nbb . "' style='float:right;width:100px;'>";
                        $selected = ($modif_bras && $jge_bras_array[$nbb]['berge_depart'] == 1) ? 'selected' : '';
                        echo "<option value='1' " . $selected . ">" . TEXT_JGE_BRAS_RIVE_GAUCHE . "</option>";
                        $selected = ($modif_bras && $jge_bras_array[$nbb]['berge_depart'] == 2) ? 'selected' : '';
                        echo "<option value='2' " . $selected . ">" . TEXT_JGE_BRAS_RIVE_DROITE . "</option>";
                    echo "</select>";
                echo "</div>\n";

            echo "</div>\n";

        echo "</div>\n";

        // ---- Bank and observation ----
        echo "<div style='float:left;width:260px;margin-left:40px;'>\n";

            // Observation
            echo "<div id='boite_small' style='width:100%;'>\n";
                echo "<p style='float:left;font-weight:bold;color:#000;margin-top:8px;margin-bottom:10px;font-size:12px;'>" . TEXT_JGE_BRAS_OBS . "</p>";
                $value = $modif_bras ? $jge_bras_array[$nbb]['bras_obs'] : '';
                echo "<textarea name='bras_obs_" . $nbb . "' id='bras_obs_" . $nbb . "' style='width:100%;height:70px;font-size:11px;'>" . $value . "</textarea>\n";
            echo "</div>\n";

        echo "</div>\n";

        // Delete arm button (server or nomad mode only)
        if ($modif_bras && ((HP_VERSION == 'Serveur') || ($from_nomad > 0 && $hp_load < 1)))
        {
            echo "<div id='del' style='float:right;margin-right:10px;'
                        onclick=\"delBras('" . $jge_bras_array[$nbb]['id_bras'] . "');\">
                    <span style='font-size:16px;font-weight:bold;cursor:pointer;'
                        title='" . TEXT_JGE_BRAS_DELETE . "'>
                        X
                    </span>
                </div>";
        }

    echo "</div>\n";

    // Section divider
    echo "<div style='width:100%;border-bottom:2px solid #176B87;'></div>\n";


    // -----------------------------------------------
    // Gauging data entry section

    echo "<div style='margin-top:10px;display:inline-flex;align-items:center;gap:15px;'>\n";
        echo "<h2 style='margin:0;text-align:left;font-size:14px;width:auto;'>";
        // . TEXT_JGE_BRAS_DEPOUIL_TITLE . 

            // Data entry popup button.
            // Wrapped in its own box so the StreamPro switch can hide it: the
            // inner div keeps the shared #button_titre styling.
            echo "<div id='box_saisie_" . $nbb . "' style='display:inline-block;'>\n";
                echo "<div id='button_titre' style='width:200px;box-sizing:border-box;text-align:center;' onclick='view_jge_pts(" . $nbb . ");'>\n";
                    echo TEXT_JGE_BRAS_SAISIE_BTN;
                echo "</div>\n";
            echo "</div>\n";

        echo "</h2>\n";

        // ---- StreamPro switch ----
        // The StreamPro ADCP reports its own aggregated results: there is no
        // point-by-point entry and no cross-section to draw, so ticking the box
        // hides the entry button and the graph, leaving only the result bar.
        //
        // This is NOT a stored field. A StreamPro gauging already carries its
        // coefficient in the arm comment - "StreamPro - coef. : x" from now on,
        // "Coef. SP : x" in the legacy New Caledonia records - so the comment is
        // the single source of truth and jgeApplyStreamPro() (js_jge.js) ticks
        // the box from it on load. No name attribute on purpose: nothing is
        // posted, nothing is saved beyond the comment itself.
        //
        // TERRITORY RESTRICTION: this device is used in New Caledonia only.
        // To limit the block to NC, wrap the whole echo below in:
        //     if (INIT_T == 'NC') { ... }
        // (left unconditional on purpose - same idiom as the panels in modif_jge.php)
        echo "<span style='display:inline-flex;align-items:center;gap:5px;white-space:nowrap;'>\n";
            echo "<input type='checkbox' id='streampro_" . $nbb . "' onchange='toggleStreamPro(" . $nbb . ");'>";
            echo "<label for='streampro_" . $nbb . "'
                        style='font-weight:bold;color:#000;font-size:12px;cursor:pointer;'>"
                        . TEXT_JGE_BRAS_STREAMPRO . "</label>";
        echo "</span>\n";


        echo "<span id='unsaved_warning_" . $nbb . "' style='display:none;background-color:#FFF59D;color:#333;
                    padding:4px 10px;border-radius:4px;font-size:12px;font-weight:bold;
                    border:1px solid #F9A825;white-space:nowrap;'>
            ⚠ " . TEXT_UNSAVED_CHANGES . 
        "</span>\n";
    echo "</div>";

    // Hidden measurement point inputs
    echo $html_hidden_jge_pts;

    // -----------------------------------------------
    // Per-arm equipment (current meter / propeller / rod diameter)
    // The editable UI now lives in the single points popup (block_jge_pts.php)
    // with fixed ids. These per-arm hidden fields are the source of truth:
    //   - they keep one value PER arm (the popup edits one arm at a time),
    //   - their names match what process_jge_save.php expects on save
    //     (select_moulinet_<nbb>, select_helice_<nbb>, perche_diam_<nbb>).
    // The popup loads them on open and writes them back on change
    // (see loadBrasEquipment / saveBrasEquipment in js_jge.js).
    $val_moulinet = $modif_bras ? $jge_bras_array[$nbb]['id_moulinet'] : 0;
    $val_helice   = $modif_bras ? $jge_bras_array[$nbb]['id_helice']   : 0;
    $val_perche   = $modif_bras ? $jge_bras_array[$nbb]['perche_diam'] : '';

    echo "<input type='hidden' id='select_moulinet_" . $nbb . "' name='select_moulinet_" . $nbb . "' value='" . $val_moulinet . "'>\n";
    echo "<input type='hidden' id='select_helice_"   . $nbb . "' name='select_helice_"   . $nbb . "' value='" . $val_helice   . "'>\n";
    echo "<input type='hidden' id='perche_diam_"     . $nbb . "' name='perche_diam_"     . $nbb . "' value='" . $val_perche   . "'>\n";

    echo "<div style='margin-top:5px;width:100%;display:flex;flex:1;'>\n";

        /*
        echo "<div style='float:left;width:270px;margin-right:0px;'>\n";

            echo "<div style='float:left;width:90%;'>\n";

                echo "<div style='float:left;width:100%;'>\n";

                    // Rod diameter
                    echo "<div id='boite_small' style='width:100%;margin:0;'>\n";
                        echo "<p style='float:left;font-weight:bold;color:#000;margin-top:7px;margin-bottom:0px;font-size:12px;' title='" . TEXT_JGE_BRAS_PERCHE_TITLE . "'>" . TEXT_JGE_BRAS_PERCHE_LABEL . "</p>";
                        $value = $modif_bras ? $jge_bras_array[$nbb]['perche_diam'] : '';
                        echo "<input type='text' style='float:right;width:30px;' id='perche_diam_" . $nbb . "' name='perche_diam_" . $nbb . "' value='" . $value . "'>\n";
                    echo "</div>\n";

                    // Current meter (moulinet)
                    echo "<div id='boite_small' style='width:100%;margin:0;margin-top:10px;display:flex;justify-content:space-between;align-items:center;'>\n";
                        echo "<span style='font-weight:bold;color:#000;margin-top:2px;'>" . TEXT_JGE_BRAS_MOULINET . "</span>";
                        echo "<select name='select_moulinet_" . $nbb . "' id='select_moulinet_" . $nbb . "' style='width:160px;' );'>";
                            echo "<option value='0'>-</option>";
                            if (isset($moulinet_array))
                            {
                                foreach ($moulinet_array as $key => $value)
                                {
                                    $selected = ($modif_bras && $key == $jge_bras_array[$nbb]['id_moulinet']) ? 'selected' : '';
                                    echo "<option value='" . $key . "' " . $selected . ">" . $moulinet_array[$key]['num'] . "</option>";
                                }
                            }
                        echo "</select>";
                    echo "</div>\n";

                    echo "<hr>\n";  

                    // Helical flowmeter
                    echo "<div id='box_helice_" . $nbb . "' style='margin-top:10px;padding:5px;padding-bottom:0px;border-radius:4px;'>\n";

                        echo "<div id='boite_small' style='width:100%;margin:0;display:flex;justify-content:space-between;align-items:center;'>\n";
                            echo "<span style='font-weight:bold;color:#000;margin-top:2px;'>" . TEXT_JGE_BRAS_HELICE . "</span>";
                            echo "<img src='" . DIR_WS_IMG_ICO . "info.png' style='width:15px;cursor:pointer;' onclick='view_helice_eq(" . $nbb . ");'>";
                            echo "<select name='select_helice_" . $nbb . "' id='select_helice_" . $nbb . "' style='width:160px;' onChange='helice_eq(" . $nbb . ");'>";
                                echo "<option value='0'>-</option>";
                                if (isset($helice_array))
                                {
                                    foreach ($helice_array as $key => $value)
                                    {
                                        $selected = ($modif_bras && $key == $jge_bras_array[$nbb]['id_helice']) ? 'selected' : '';
                                        echo "<option value='" . $key . "' " . $selected . ">" . $helice_array[$key]['num'] . " - " . $helice_array[$key]['fabricant'] . "</option>";
                                    }
                                }
                            echo "</select>";

                            // Load helical equation coefficients for existing arm
                            if ($modif_bras)
                            {
                                $id_helice_encours = $jge_bras_array[$nbb]['id_helice'];
                                if (isset($helice_array[$id_helice_encours]))
                                {
                                    $l1 = $helice_array[$id_helice_encours]['l1'];
                                    $a1 = $helice_array[$id_helice_encours]['a1'];
                                    $b1 = $helice_array[$id_helice_encours]['b1'];
                                    $l2 = $helice_array[$id_helice_encours]['l2'];
                                    $a2 = $helice_array[$id_helice_encours]['a2'];
                                    $b2 = $helice_array[$id_helice_encours]['b2'];
                                    $a3 = $helice_array[$id_helice_encours]['a3'];
                                    $b3 = $helice_array[$id_helice_encours]['b3'];
                                }
                            }
                            else
                            {
                                $l1 = ''; $a1 = ''; $b1 = '';
                                $l2 = ''; $a2 = ''; $b2 = '';
                                $a3 = ''; $b3 = '';
                            }

                        echo "</div>\n";

                    echo "<hr>\n";    
                    echo "</div>\n";

                echo "</div>\n";

                // Hidden measurement point inputs
                echo $html_hidden_jge_pts;

            echo "</div>\n";

        echo "</div>\n";
        */

        // -----------------------------------------------
        // Computed hydraulic results panel
        // CSS grid filled COLUMN by column over 2 rows: each pair is stacked
        // vertically (Flow above Mean height, etc.). The number of visible
        // columns still adapts to the window width, and the panel stays as
        // short as possible to leave room for the graph.

        echo "<div style='flex:1;min-width:0;display:flex;flex-direction:column;'>\n";

            echo "<div style='display:grid;grid-template-rows:repeat(2, auto);grid-auto-flow:column;
                              grid-auto-columns:minmax(150px, max-content);
                              gap:3px 22px;padding:8px 12px;flex-shrink:0;justify-content:start;
                              overflow-x:auto;border:1px solid #000;border-radius:4px;background-color:#fff;'>\n";

                // ---- Reusable cell renderer (label left / input right) ----
                $jge_result_cell = function($id, $label, $title, $val, $w = '40px', $colour = '#000') use ($nbb)
                {
                    echo "<div id='boite_small' style='display:flex;align-items:center;justify-content:space-between;gap:6px;margin:0;'>\n";
                        echo "<span title='" . $title . "'
                                style='font-weight:bold;font-size:12px;color:" . $colour . ";white-space:nowrap;'>" . $label . "</span>";
                        echo "<input type='text'
                                style='width:" . $w . ";height:12px;font-size:12px;
                                       border:1px solid #ddd;background-color:#FFFFDD;flex-shrink:0;'
                                id='" . $id . "_" . $nbb . "' name='" . $id . "_" . $nbb . "' value='" . $val . "'>\n";
                    echo "</div>\n";
                };

                // $jge_bras_array only exists for an existing gauging (modif).
                // Default it to an empty array so the closure's `use` doesn't
                // warn on a new gauging.
                if (!isset($jge_bras_array)) { $jge_bras_array = array(); }

                $v = function($field) use ($modif_bras, $jge_bras_array, $nbb) {
                    return ($modif_bras && isset($jge_bras_array[$nbb][$field]))
                        ? $jge_bras_array[$nbb][$field]
                        : '';
                };

                // Column 1: Flow (Q) above Mean height — both red, as before
                $jge_result_cell('depouil_bras_q',         TEXT_JGE_BRAS_Q_LABEL,         TEXT_JGE_BRAS_Q_TITLE,         $v('depouil_bras_q'),         '46px', '#930000');
                $jge_result_cell('depouil_bras_hmoy',      TEXT_JGE_BRAS_HMOY_LABEL,      TEXT_JGE_BRAS_HMOY_TITLE,      $v('depouil_bras_hmoy'),      '40px', '#930000');

                // Column 2: velocities
                $jge_result_cell('depouil_bras_vmoy',      TEXT_JGE_BRAS_VMOY_LABEL,      TEXT_JGE_BRAS_VMOY_TITLE,      $v('depouil_bras_vmoy'));
                $jge_result_cell('depouil_bras_vsurf',     TEXT_JGE_BRAS_VSURF_LABEL,     TEXT_JGE_BRAS_VSURF_TITLE,     $v('depouil_bras_vsurf'));

                // Column 3: wetted area / perimeter
                $jge_result_cell('depouil_bras_surfmouil', TEXT_JGE_BRAS_SURFMOUIL_LABEL, TEXT_JGE_BRAS_SURFMOUIL_TITLE, $v('depouil_bras_surfmouil'));
                $jge_result_cell('depouil_bras_perimouil', TEXT_JGE_BRAS_PERIMOUIL_LABEL, TEXT_JGE_BRAS_PERIMOUIL_TITLE, $v('depouil_bras_perimouil'));

                // Column 4: mean depth / total width
                $jge_result_cell('depouil_bras_profmoy',   TEXT_JGE_BRAS_PROFMOY_LABEL,   TEXT_JGE_BRAS_PROFMOY_TITLE,   $v('depouil_bras_profmoy'));
                $jge_result_cell('depouil_bras_distmax',   TEXT_JGE_BRAS_DISTMAX_LABEL,   TEXT_JGE_BRAS_DISTMAX_TITLE,   $v('depouil_bras_distmax'));

                // Column 5: hydraulic radius (alone, top row)
                $jge_result_cell('depouil_bras_rh',        TEXT_JGE_BRAS_RH_LABEL,        TEXT_JGE_BRAS_RH_TITLE,        $v('depouil_bras_rh'));

            echo "</div>\n";

            echo "<div style='height:8px;'></div>\n";

            // Cross-section graph placeholder
            echo "<div id='plot_jge_bras_" . $nbb . "'
                    style='flex:1;min-height:0;padding:10px 0;border:1px solid #000;border-radius:4px;background-color:#fff;'>
                    <p id='plot_text_" . $nbb . "'
                        style='width:100%;margin-top:5%;text-align:center;font-size:22px;color:#000;'>
                        " . TEXT_JGE_BRAS_GRAPH_PLACEHOLDER . "
                    </p>
                </div>\n";

        echo "</div>\n";

    echo "</div>\n";

echo "</div>\n";
?>

<script type="text/javascript">

    var bras = <?php echo $nbb; ?>;

    // -----------------------------------------------
    // Show helical flowmeter equation popup
    // The propeller select now lives in the points popup with a fixed id,
    // so helice_eq() reads it directly (no per-arm suffix needed).

    function view_helice_eq()
    {
        document.getElementById('box_jge_helice').style.display = 'block';
        // Guarded: a missing/failed drag helper must not block the popup.
        if (typeof initDraggableResize === 'function')
        {
            try { initDraggableResize('title_box_helice', 'box_jge_helice'); } catch (e) {}
        }
        helice_eq();
    }


    // -----------------------------------------------
    // Show measurement points popup

    function view_jge_pts(bras)
    {
        // Open the points popup + backdrop
        const boxJgePts = document.getElementById('box_jge_pts');
        const overlayJgePts = document.getElementById('overlay_jge_pts');
        if (overlayJgePts) { overlayJgePts.style.display = 'block'; }
        boxJgePts.style.display = 'block';

        // Load this arm's equipment (meter / propeller / rod) into the popup's
        // fixed-id fields, then render its measurement points.
        loadBrasEquipment(bras);
        showDataJge(bras);
    }


    // -----------------------------------------------
    // Update riverbed text field from checkboxes

    function updateSelectedFond(bras)
    {
        var checkboxes = Array.from(document.querySelectorAll('input[type="checkbox"][name^="check_fondlit_' + bras + '"]'));

        var selectedValues = checkboxes
            .filter(function(checkbox) { return checkbox.checked; })
            .map(function(checkbox) { return checkbox.getAttribute('data-value').trim(); });

        var currentText = document.getElementById('fond_text_' + bras).value;

        var manualText  = currentText
            .split(' / ')
            .map(function(value) { return value.trim(); })
            .filter(function(value) {
                return value !== '' &&
                    !selectedValues.includes(value) &&
                    !checkboxes.some(function(chk) { return chk.getAttribute('data-value').trim() === value; });
            });

        document.getElementById('fond_text_' + bras).value = manualText.concat(selectedValues).join(' / ');
    }


    // -----------------------------------------------
    // Render cross-section graph on load (bed profile + gauging points).

    f_editgraph_jge(bras);

</script>