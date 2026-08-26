<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Propeller tab — included by gestion_eq_jaugeage.php
Queries TABLE_HELICE and renders an editable table with all calibration
coefficients (l1/a1/b1, l2/a2/b2, a3/b3).
One new-entry row (index 0) at the top for adding a new propeller.
Each existing row has an inline Delete button (AJAX, no reload).

Modernization notes:
- Removed input_texte_* classes in favor of inline width styles
  to keep the table compact without affecting the global form layout.
- Number column   : 70px
- Numeric coeffs  : 55px
- Manufacturer    : 140px
- Observation     : 220px
----------------------------------------
*/

$row = 0;

// Query: all propellers ordered by number
$helice_query = tep_db_query($sql_link,
    "SELECT DISTINCT id, num, diametre, pas, l1, a1, b1, l2, a2, b2, a3, b3, fabricant, obs
     FROM " . TABLE_HELICE . "
     ORDER BY num ASC");

// Build the set of propeller ids already referenced by a gauging arm.
// A single grouped query (instead of one count per row) keeps it efficient.
// The delete cross is shown only for propellers NOT in this set.
$helice_used = [];
$helice_used_query = tep_db_query($sql_link,
    "SELECT id_helice, COUNT(*) AS nb
     FROM " . TABLE_DATA_JGE_BRAS . "
     WHERE id_helice > 0
     GROUP BY id_helice");
while ($u = tep_db_fetch_array($helice_used_query))
{
    $helice_used[(int) $u['id_helice']] = true;
}


echo "<div id='onglet_contenu' style='height:75vh;'>\n";

    // Inline SVG info button styling (opens the velocity-equations popup).
    // Same sober look as the gauging module's info button.
    echo "<style>
        .eqh_info_btn {
            display:inline-flex;
            align-items:center;
            justify-content:center;
            color:#0f6e56;
            cursor:pointer;
            vertical-align:middle;
            transition:color 0.15s, transform 0.15s;
        }
        .eqh_info_btn:hover { color:#930000; transform:scale(1.12); }
    </style>\n";

    // Editable velocity-equations popup (single instance, reused for every row)
    require(DIR_WS_EQJGE . 'block_eq_helice_edit.php');

    echo "<div id='boite1' class='first'>\n";

        echo "<div class='table-container' style='float:left;height:70vh;'>";

            echo "<table id='table_tri' cellspacing='0'>";

                echo "<thead>";
                    echo "<tr class='header-row' style='background-color:#eef3f8;'>";
                        echo "<th>" . TEXT_EJ_HEL_TH_NUM       . "</th>";
                        echo "<th>" . TEXT_EJ_HEL_TH_DIAM      . "</th>";
                        echo "<th>" . TEXT_EJ_HEL_TH_PAS       . "</th>";
                        echo "<th style='text-align:center;'>" . TEXT_EJ_HEL_TH_EQUATIONS . "</th>";
                        echo "<th>" . TEXT_EJ_HEL_TH_FABRICANT . "</th>";
                        echo "<th>" . TEXT_EJ_HEL_TH_OBS       . "</th>";
                        echo "<th style='text-align:center;'>&nbsp;</th>";
                    echo "</tr>";
                echo "</thead>";

                // ---- New-entry row ----
                echo "<tr><td colspan='7' style='color:#000;font-size:14px;font-weight:bold;'>"
                   . TEXT_EJ_HEL_NEW . "</td></tr>\n";

                // Style commun pour les inputs de la nouvelle ligne (bordure verte)
                $w_num    = "width:70px;";
                $w_coef   = "width:55px;";
                $w_fabri  = "width:140px;";
                $w_obs    = "width:220px;";
                $new_brd  = "border:2px solid #609966;";

                echo "<tr>";
                    echo "<td><input type='text' style='{$w_num}{$new_brd}'   name='helice_num_0' id='helice_num_0'></td>";
                    echo "<td><input type='text' style='{$w_coef}{$new_brd}'  name='helice_diam_0'></td>";
                    echo "<td><input type='text' style='{$w_coef}{$new_brd}'  name='helice_pas_0'></td>";

                    // Coefficients are edited through the equations popup; kept here as
                    // hidden fields so the global Save still posts them unchanged.
                    echo "<td style='text-align:center;'>";
                        echo "<input type='hidden' name='helice_l1_0' id='helice_l1_0'>";
                        echo "<input type='hidden' name='helice_a1_0' id='helice_a1_0'>";
                        echo "<input type='hidden' name='helice_b1_0' id='helice_b1_0'>";
                        echo "<input type='hidden' name='helice_l2_0' id='helice_l2_0'>";
                        echo "<input type='hidden' name='helice_a2_0' id='helice_a2_0'>";
                        echo "<input type='hidden' name='helice_b2_0' id='helice_b2_0'>";
                        echo "<input type='hidden' name='helice_a3_0' id='helice_a3_0'>";
                        echo "<input type='hidden' name='helice_b3_0' id='helice_b3_0'>";
                        echo "<span class='eqh_info_btn' title='" . TEXT_EJ_HEL_EQ_BTN . "' onClick='openHeliceEq(0);'>"
                           . "<svg viewBox='0 0 24 24' width='18' height='18' fill='none' xmlns='http://www.w3.org/2000/svg' aria-hidden='true'>"
                           . "<circle cx='12' cy='12' r='9' stroke='currentColor' stroke-width='2'/>"
                           . "<line x1='12' y1='11' x2='12' y2='16.5' stroke='currentColor' stroke-width='2' stroke-linecap='round'/>"
                           . "<circle cx='12' cy='7.5' r='1.25' fill='currentColor'/>"
                           . "</svg>"
                           . "</span>";
                    echo "</td>";

                    echo "<td><input type='text' style='{$w_fabri}{$new_brd}' name='helice_fabricant_0'></td>";
                    echo "<td><input type='text' style='{$w_obs}{$new_brd}'   name='helice_obs_0'></td>";
                    echo "<td>&nbsp;</td>";
                echo "</tr>";

                // Empty spacer row
                echo "<tr><td colspan='7' style='height:10px;'>&nbsp;</td></tr>";

                // ---- Existing propeller rows ----
                while ($helice_tab = tep_db_fetch_array($helice_query))
                {
                    $helice_id        = $helice_tab['id'];
                    $helice_num       = html_entity_decode($helice_tab['num']       ?? '');
                    $helice_diam      = html_entity_decode($helice_tab['diametre']  ?? '');
                    $helice_pas       = html_entity_decode($helice_tab['pas']       ?? '');
                    $helice_l1        = html_entity_decode($helice_tab['l1']        ?? '');
                    $helice_a1        = html_entity_decode($helice_tab['a1']        ?? '');
                    $helice_b1        = html_entity_decode($helice_tab['b1']        ?? '');
                    $helice_l2        = html_entity_decode($helice_tab['l2']        ?? '');
                    $helice_a2        = html_entity_decode($helice_tab['a2']        ?? '');
                    $helice_b2        = html_entity_decode($helice_tab['b2']        ?? '');
                    $helice_a3        = html_entity_decode($helice_tab['a3']        ?? '');
                    $helice_b3        = html_entity_decode($helice_tab['b3']        ?? '');
                    $helice_fabricant = html_entity_decode($helice_tab['fabricant'] ?? '');
                    $helice_obs       = html_entity_decode($helice_tab['obs']       ?? '');

                    if (fmod($row, 2) == 0) {
                        $row_l = "class='row1' onmouseover=\"this.className='row1hover';\" onmouseout=\"this.className='row1';\"";
                    } else {
                        $row_l = "class='row2' onmouseover=\"this.className='row2hover';\" onmouseout=\"this.className='row2';\"";
                    }
                    $row++;

                    echo "<tr {$row_l} id='row_eqh_{$helice_id}'>";

                        echo "<td><input type='text' style='{$w_num}'   name='helice_num_{$helice_id}'       id='helice_num_{$helice_id}' value='{$helice_num}'></td>";
                        echo "<td><input type='text' style='{$w_coef}'  name='helice_diam_{$helice_id}'      value='{$helice_diam}'></td>";
                        echo "<td><input type='text' style='{$w_coef}'  name='helice_pas_{$helice_id}'       value='{$helice_pas}'></td>";

                        // Coefficients edited via the equations popup; kept as hidden
                        // fields (with their current value) so the global Save posts them.
                        echo "<td style='text-align:center;'>";
                            echo "<input type='hidden' name='helice_l1_{$helice_id}' id='helice_l1_{$helice_id}' value='{$helice_l1}'>";
                            echo "<input type='hidden' name='helice_a1_{$helice_id}' id='helice_a1_{$helice_id}' value='{$helice_a1}'>";
                            echo "<input type='hidden' name='helice_b1_{$helice_id}' id='helice_b1_{$helice_id}' value='{$helice_b1}'>";
                            echo "<input type='hidden' name='helice_l2_{$helice_id}' id='helice_l2_{$helice_id}' value='{$helice_l2}'>";
                            echo "<input type='hidden' name='helice_a2_{$helice_id}' id='helice_a2_{$helice_id}' value='{$helice_a2}'>";
                            echo "<input type='hidden' name='helice_b2_{$helice_id}' id='helice_b2_{$helice_id}' value='{$helice_b2}'>";
                            echo "<input type='hidden' name='helice_a3_{$helice_id}' id='helice_a3_{$helice_id}' value='{$helice_a3}'>";
                            echo "<input type='hidden' name='helice_b3_{$helice_id}' id='helice_b3_{$helice_id}' value='{$helice_b3}'>";
                            echo "<span class='eqh_info_btn' title='" . TEXT_EJ_HEL_EQ_BTN . "' onClick='openHeliceEq({$helice_id});'>"
                               . "<svg viewBox='0 0 24 24' width='18' height='18' fill='none' xmlns='http://www.w3.org/2000/svg' aria-hidden='true'>"
                               . "<circle cx='12' cy='12' r='9' stroke='currentColor' stroke-width='2'/>"
                               . "<line x1='12' y1='11' x2='12' y2='16.5' stroke='currentColor' stroke-width='2' stroke-linecap='round'/>"
                               . "<circle cx='12' cy='7.5' r='1.25' fill='currentColor'/>"
                               . "</svg>"
                               . "</span>";
                        echo "</td>";

                        echo "<td><input type='text' style='{$w_fabri}' name='helice_fabricant_{$helice_id}' value='{$helice_fabricant}'></td>";
                        echo "<td><input type='text' style='{$w_obs}'   name='helice_obs_{$helice_id}'       value='{$helice_obs}'></td>";

                        // Delete cell — cross only shown if the propeller is NOT
                        // linked to any gauging arm. Otherwise the cell is empty.
                        echo "<td class='t_icon' style='text-align:center;'>";
                            if (!isset($helice_used[(int) $helice_id]))
                            {
                                $del_label = addslashes($helice_num);
                                echo "<a style='font-size:12px;font-weight:bold;cursor:pointer;'"
                                   . " title='" . TEXT_EJ_BTN_DELETE . "'"
                                   . " onClick=\"confirmEqDelete('helice', '{$helice_id}', '{$del_label}');\">X</a>";
                            }
                        echo "</td>\n";

                    echo "</tr>";
                }

            echo "</table>";

        echo "</div>\n";

    echo "<hr>\n";
    echo "</div>\n";

echo "<hr>\n";
echo "</div>\n";
?>

<script>

    // Propeller deletion is handled by the shared confirmEqDelete('helice', ...)
    // popup defined in block_eq_del_confirm.php (included by gestion_eq_jaugeage.php).
    // The delete cross is rendered above only for propellers not linked to a gauging arm.

</script>