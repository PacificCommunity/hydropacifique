<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Weight (saumon) tab — included by gestion_eq_jaugeage.php
Queries TABLE_SAUMON and renders an editable table.
One new-entry row (index 0) at the top for adding a new weight.
Each existing row has an inline Delete button (AJAX, no reload).
Note: original used closing </td> instead of </th> inside <thead> — fixed.
----------------------------------------
*/

$row = 0;

// Query: all weights ordered by number
$saumon_query = tep_db_query($sql_link,
    "SELECT DISTINCT id, num, titre, poids, distance_axe, t_air, r_dist, fabricant, obs
     FROM " . TABLE_SAUMON . "
     ORDER BY num ASC");

// Build the set of weight ids already referenced by a gauging arm.
// The delete cross is shown only for weights NOT in this set.
$saumon_used = [];
$saumon_used_query = tep_db_query($sql_link,
    "SELECT id_saumon, COUNT(*) AS nb
     FROM " . TABLE_DATA_JGE_BRAS . "
     WHERE id_saumon > 0
     GROUP BY id_saumon");
while ($u = tep_db_fetch_array($saumon_used_query))
{
    $saumon_used[(int) $u['id_saumon']] = true;
}


echo "<div id='onglet_contenu' style='height:75vh;'>\n";

    echo "<div id='boite1' class='first'>\n";

        echo "<div class='table-container' style='float:left;height:70vh;'>";

            echo "<table id='table_tri' cellspacing='0'>";

                echo "<thead>";
                    echo "<tr class='header-row' style='background-color:#eef3f8;'>";
                        echo "<th>" . TEXT_EJ_SAU_TH_NUM      . "</th>";
                        echo "<th>" . TEXT_EJ_SAU_TH_TITRE    . "</th>";
                        echo "<th>" . TEXT_EJ_SAU_TH_POIDS    . "</th>";
                        echo "<th>" . TEXT_EJ_SAU_TH_DIST_AXE . "</th>";
                        echo "<th>" . TEXT_EJ_SAU_TH_TAIR     . "</th>";
                        echo "<th>" . TEXT_EJ_SAU_TH_RDIST    . "</th>";
                        echo "<th>" . TEXT_EJ_SAU_TH_FABRICANT . "</th>";
                        echo "<th>" . TEXT_EJ_SAU_TH_OBS      . "</th>";
                        echo "<th style='text-align:center;'>&nbsp;</th>";
                    echo "</tr>";
                echo "</thead>";

                // ---- New-entry row ----
                echo "<tr><td colspan='9' style='color:#000;font-size:14px;font-weight:bold;'>"
                   . TEXT_EJ_SAU_NEW . "</td></tr>\n";

                echo "<tr>";
                    echo "<td><input type='text' class='input_texte_small' name='saumon_num_0'></td>";
                    echo "<td><input type='text' class='input_texte_200'   name='saumon_titre_0'></td>";
                    echo "<td><input type='text' class='input_texte_60'    name='saumon_poids_0'></td>";
                    echo "<td><input type='text' class='input_texte_60'    name='saumon_dist_axe_0'></td>";
                    echo "<td><input type='text' class='input_texte_60'    name='saumon_t_air_0'></td>";
                    echo "<td><input type='text' class='input_texte_60'    name='saumon_r_dist_0'></td>";
                    echo "<td><input type='text' class='input_texte_200'   name='saumon_fabricant_0'></td>";
                    echo "<td><input type='text' class='input_texte_300'   name='saumon_obs_0'></td>";
                    echo "<td>&nbsp;</td>";
                echo "</tr>";

                // Empty spacer row
                echo "<tr><td colspan='9' style='height:10px;'>&nbsp;</td></tr>";

                // ---- Existing weight rows ----
                while ($saumon_tab = tep_db_fetch_array($saumon_query))
                {
                    $saumon_id       = $saumon_tab['id'];
                    $saumon_num      = html_entity_decode($saumon_tab['num']          ?? '');
                    $saumon_titre    = html_entity_decode($saumon_tab['titre']        ?? '');
                    $saumon_poids    = html_entity_decode($saumon_tab['poids']        ?? '');
                    $saumon_dist_axe = html_entity_decode($saumon_tab['distance_axe'] ?? '');
                    $saumon_t_air    = html_entity_decode($saumon_tab['t_air']        ?? '');
                    $saumon_r_dist   = html_entity_decode($saumon_tab['r_dist']       ?? '');
                    $saumon_fabricant = html_entity_decode($saumon_tab['fabricant']   ?? '');
                    $saumon_obs      = html_entity_decode($saumon_tab['obs']          ?? '');

                    if (fmod($row, 2) == 0) {
                        $row_l = "class='row1' onmouseover=\"this.className='row1hover';\" onmouseout=\"this.className='row1';\"";
                    } else {
                        $row_l = "class='row2' onmouseover=\"this.className='row2hover';\" onmouseout=\"this.className='row2';\"";
                    }
                    $row++;

                    echo "<tr {$row_l} id='row_eqs_{$saumon_id}'>";

                        echo "<td><input type='text' class='input_texte_small' name='saumon_num_{$saumon_id}'       value='{$saumon_num}'></td>";
                        echo "<td><input type='text' class='input_texte_200'   name='saumon_titre_{$saumon_id}'     value='{$saumon_titre}'></td>";
                        echo "<td><input type='text' class='input_texte_60'    name='saumon_poids_{$saumon_id}'     value='{$saumon_poids}'></td>";
                        echo "<td><input type='text' class='input_texte_60'    name='saumon_dist_axe_{$saumon_id}'  value='{$saumon_dist_axe}'></td>";
                        echo "<td><input type='text' class='input_texte_60'    name='saumon_t_air_{$saumon_id}'     value='{$saumon_t_air}'></td>";
                        echo "<td><input type='text' class='input_texte_60'    name='saumon_r_dist_{$saumon_id}'    value='{$saumon_r_dist}'></td>";
                        echo "<td><input type='text' class='input_texte_200'   name='saumon_fabricant_{$saumon_id}' value='{$saumon_fabricant}'></td>";
                        echo "<td><input type='text' class='input_texte_300'   name='saumon_obs_{$saumon_id}'       value='{$saumon_obs}'></td>";

                        // Delete cell — cross only if not linked to a gauging arm
                        echo "<td class='t_icon' style='text-align:center;'>";
                            if (!isset($saumon_used[(int) $saumon_id]))
                            {
                                $del_label = addslashes($saumon_num);
                                echo "<a style='font-size:12px;font-weight:bold;cursor:pointer;'"
                                   . " title='" . TEXT_EJ_BTN_DELETE . "'"
                                   . " onClick=\"confirmEqDelete('saumon', '{$saumon_id}', '{$del_label}');\">X</a>";
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

    // Weight deletion is handled by the shared confirmEqDelete('saumon', ...)
    // popup defined in block_eq_del_confirm.php (included by gestion_eq_jaugeage.php).
    // The delete cross is rendered above only for weights not linked to a gauging arm.

</script>