<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Current meter tab — included by gestion_eq_jaugeage.php
Queries TABLE_MOULINET and renders an editable table.
One new-entry row (index 0) at the top for adding a new current meter.
Each existing row has an inline Delete button (AJAX, no reload).
----------------------------------------
*/

$row = 0;

// Query: all current meters ordered by number
$moulinet_query = tep_db_query($sql_link,
    "SELECT DISTINCT id, num, fabricant, obs
     FROM " . TABLE_MOULINET . "
     ORDER BY num ASC");
while ($moulinet = tep_db_fetch_array($moulinet_query))
{
    $moulinet_array[$moulinet['id']] = [
        'num'       => html_entity_decode($moulinet['num']       ?? ''),
        'fabricant' => html_entity_decode($moulinet['fabricant'] ?? ''),
        'obs'       => html_entity_decode($moulinet['obs']       ?? ''),
    ];
}

// Tri des moulinets : C2 en premier, puis C31, puis les autres (tri alphabétique à l'intérieur)
uasort($moulinet_array, function($a, $b) {
    // Extraire le préfixe (premier mot avant l'espace) de chaque num
    $prefixA = strtoupper(explode(' ', trim($a['num']))[0]);
    $prefixB = strtoupper(explode(' ', trim($b['num']))[0]);

    // Fonction qui donne un "poids" de tri selon le préfixe
    $poids = function($prefix) {
        if ($prefix === 'C2')  return 1;
        if ($prefix === 'C31') return 2;
        return 3; // tous les autres
    };

    $pA = $poids($prefixA);
    $pB = $poids($prefixB);

    // Si les catégories diffèrent, on trie par catégorie
    if ($pA !== $pB) return $pA - $pB;

    // Sinon, tri alphabétique (naturel, pour que "C2 9" vienne avant "C2 10")
    return strnatcasecmp($a['num'], $b['num']);
});

// Build the set of current-meter ids already referenced by a gauging arm.
// The delete cross is shown only for current meters NOT in this set.
$moulinet_used = [];
$moulinet_used_query = tep_db_query($sql_link,
    "SELECT id_moulinet, COUNT(*) AS nb
     FROM " . TABLE_DATA_JGE_BRAS . "
     WHERE id_moulinet > 0
     GROUP BY id_moulinet");
while ($u = tep_db_fetch_array($moulinet_used_query))
{
    $moulinet_used[(int) $u['id_moulinet']] = true;
}



echo "<div id='onglet_contenu' style='height:75vh;'>\n";

    echo "<div id='boite1' class='first'>\n";

        echo "<div class='table-container' style='float:left;height:70vh;'>";

            echo "<table id='table_tri' cellspacing='0'>";

                echo "<thead>";
                    echo "<tr class='header-row' style='background-color:#eef3f8;'>";
                        echo "<th>" . TEXT_EJ_MOUL_TH_NUM       . "</th>";
                        echo "<th>" . TEXT_EJ_MOUL_TH_FABRICANT . "</th>";
                        echo "<th>" . TEXT_EJ_MOUL_TH_OBS       . "</th>";
                        echo "<th style='text-align:center;'>&nbsp;</th>";
                    echo "</tr>";
                echo "</thead>";

                // ---- New-entry row ----
                echo "<tr><td colspan='4' style='color:#000;font-size:14px;font-weight:bold;'>"
                   . TEXT_EJ_MOUL_NEW . "</td></tr>\n";

                echo "<tr>";
                    echo "<td><input type='text' class='input_texte_200' style='border:2px solid #609966;' name='moul_num_0'></td>";
                    echo "<td><input type='text' class='input_texte_200' style='border:2px solid #609966;' name='moul_fabricant_0'></td>";
                    echo "<td><input type='text' class='input_texte_300' style='border:2px solid #609966;' name='moul_obs_0'></td>";
                    echo "<td>&nbsp;</td>";
                echo "</tr>";

                // Empty spacer row
                echo "<tr><td colspan='4' style='height:10px;'>&nbsp;</td></tr>";

                // ---- Existing current meter rows ----
                if (isset($moulinet_array))
                {
                    foreach ($moulinet_array as $key => $value)
                    {
                
                        $id_moulinet        = $key;
                        $num_moulinet       = $moulinet_array[$key]['num']       ?? '';
                        $fabricant_moulinet = $moulinet_array[$key]['fabricant'] ?? '';
                        $obs_moulinet       = $moulinet_array[$key]['obs']       ?? '';

                        if (fmod($row, 2) == 0) {
                            $row_l = "class='row1' onmouseover=\"this.className='row1hover';\" onmouseout=\"this.className='row1';\"";
                        } else {
                            $row_l = "class='row2' onmouseover=\"this.className='row2hover';\" onmouseout=\"this.className='row2';\"";
                        }
                        $row++;

                        echo "<tr {$row_l} id='row_eqm_{$id_moulinet}'>";

                            echo "<td>";
                                echo "<input type='text' class='input_texte_200'"
                                . " name='moul_num_{$id_moulinet}' value='{$num_moulinet}'>\n";
                            echo "</td>";

                            echo "<td>";
                                echo "<input type='text' class='input_texte_200'"
                                . " name='moul_fabricant_{$id_moulinet}' value='{$fabricant_moulinet}'>\n";
                            echo "</td>";

                            echo "<td>";
                                echo "<input type='text' class='input_texte_300'"
                                . " name='moul_obs_{$id_moulinet}' value='{$obs_moulinet}'>\n";
                            echo "</td>";

                            // Delete cell — cross only if not linked to a gauging arm
                            echo "<td class='t_icon' style='text-align:center;'>";
                                if (!isset($moulinet_used[(int) $id_moulinet]))
                                {
                                    $del_label = addslashes($num_moulinet);
                                    echo "<a style='font-size:12px;font-weight:bold;cursor:pointer;'"
                                       . " title='" . TEXT_EJ_BTN_DELETE . "'"
                                       . " onClick=\"confirmEqDelete('moulinet', '{$id_moulinet}', '{$del_label}');\">X</a>";
                                }
                            echo "</td>\n";

                        echo "</tr>";
                    }
                }

            echo "</table>";

        echo "</div>\n";

    echo "<hr>\n";
    echo "</div>\n";

echo "<hr>\n";
echo "</div>\n";
?>

<script>

    // Current meter deletion is handled by the shared confirmEqDelete('moulinet', ...)
    // popup defined in block_eq_del_confirm.php (included by gestion_eq_jaugeage.php).
    // The delete cross is rendered above only for current meters not linked to a gauging arm.

</script>