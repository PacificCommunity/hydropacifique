<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Piezo benchmark table renderer (shared)
- Single source of truth for the benchmark table HTML
- Used by:
    * form_station_repere.php   (initial page render)
    * process_loadrepere.php    (AJAX reload after save, no page refresh)
----------------------------------------
*/

if (!function_exists('render_repere_table'))
{
    /**
     * Build and return the benchmark table HTML for a given station.
     *
     * @param mysqli $sql_link    Active database connection
     * @param int    $id_station  Station ID
     * @return string             Table HTML
     */
    function render_repere_table($sql_link, $id_station)
    {
        $id_station = (int) $id_station; // cast: used directly in the WHERE clause
        $row        = 0;

        $tab_code_repere = ['', 'C', 'T', 'D', 'S', 'A'];

        // -----------------------------------------------
        // Query: Benchmark records for this station

        $sql_repere   = "SELECT DISTINCT id, nature_repere, code_repere, z_repere, precision_repere,
                                date_debut_valid, date_fin_valid,
                                nature_repere_1, z_repere_g1, nature_repere_2, z_repere_g2, obs
                         FROM " . TABLE_STATION_PIEZO_REPERE . "
                         WHERE id_station = " . $id_station . "
                         ORDER BY date_debut_valid DESC";
        $repere_query = tep_db_query($sql_link, $sql_repere);

        // -----------------------------------------------
        // HTML output (buffered into $html)

        $html = '';

        $html .= "<table id='table_tri' class='table-repere' cellspacing='0'>";

            // ---- Column group headers ----
            $html .= "<tr>";
                $html .= "<th colspan='2' style='font-size:14px;'>" . TEXT_REPERE_COL_VALIDITY  . "</th>";
                $html .= "<th colspan='4' style='font-size:14px;'>" . TEXT_REPERE_COL_BENCHMARK . "</th>";
                $html .= "<th colspan='4' style='font-size:14px;'>" . TEXT_REPERE_COL_SURVEYOR  . "</th>";
                $html .= "<th colspan='1' style='font-size:14px;'>&nbsp;</th>";
            $html .= "</tr>";


            // ---- Column headers ----
            $html .= "<tr>";
                $html .= "<th style='color:#34495E;font-size:13px;border:0;'>" . TEXT_REPERE_COL_DATE_START  . "</th>";
                $html .= "<th style='color:#34495E;font-size:13px;border:0;'>" . TEXT_REPERE_COL_DATE_END    . "</th>";
                $html .= "<th style='color:#34495E;font-size:13px;border:0;'>" . TEXT_REPERE_COL_NATURE      . "</th>";

               if(INIT_T == 'NC')
               {
                  $html .= "<th style='color:#34495E;font-size:13px;border:0;'>" . TEXT_REPERE_COL_CODE        . "</th>";
               }
                $html .= "<th style='color:#34495E;font-size:13px;border:0;'>" . TEXT_REPERE_COL_Z           . "</th>";
                $html .= "<th style='color:#34495E;font-size:13px;border:0;'>" . TEXT_REPERE_COL_PRECISION   . "</th>";
                $html .= "<th style='color:#34495E;font-size:13px;border:0;'>" . TEXT_REPERE_COL_BENCHMARK1  . "</th>";
                $html .= "<th style='color:#34495E;font-size:13px;border:0;'>" . TEXT_REPERE_COL_Z           . "</th>";
                $html .= "<th style='color:#34495E;font-size:13px;border:0;'>" . TEXT_REPERE_COL_BENCHMARK2  . "</th>";
                $html .= "<th style='color:#34495E;font-size:13px;border:0;'>" . TEXT_REPERE_COL_Z           . "</th>";
                $html .= "<th style='color:#34495E;font-size:13px;border:0;'>" . TEXT_REPERE_COL_OBS         . "</th>";
                $html .= "<th style='width:40px;border:0;'>&nbsp;</th>";
            $html .= "</tr>";


            // ---- Existing benchmark rows ----
            while ($repere_tab = tep_db_fetch_array($repere_query))
            {
                $id = $repere_tab['id'];

                $date_debut_valid = dateus_fr($repere_tab['date_debut_valid']);
                if ($date_debut_valid == '00-00-0000') { $date_debut_valid = ''; }

                $date_fin_valid = dateus_fr($repere_tab['date_fin_valid']);
                if ($date_fin_valid == '00-00-0000') { $date_fin_valid = ''; }

                $nature_repere    = html_entity_decode($repere_tab['nature_repere']    ?? '');
                $code_repere      = html_entity_decode($repere_tab['code_repere']      ?? '');
                $precision_repere = html_entity_decode($repere_tab['precision_repere'] ?? '');
                $nature_repere_1  = html_entity_decode($repere_tab['nature_repere_1']  ?? '');
                $nature_repere_2  = html_entity_decode($repere_tab['nature_repere_2']  ?? '');
                $obs              = html_entity_decode($repere_tab['obs']              ?? '');

                $z_repere    = ($repere_tab['z_repere']    == '0') ? '' : $repere_tab['z_repere'];
                $z_repere_g1 = ($repere_tab['z_repere_g1'] == '0') ? '' : $repere_tab['z_repere_g1'];
                $z_repere_g2 = ($repere_tab['z_repere_g2'] == '0') ? '' : $repere_tab['z_repere_g2'];

                // ---- Code dropdown for this row ----
                $code_repere_options = '';
                foreach ($tab_code_repere as $option_code_repere)
                {
                    $selected             = ($code_repere == $option_code_repere) ? 'selected' : '';
                    $code_repere_options .= "<option value='" . $option_code_repere . "' "
                                         . $selected . ">" . $option_code_repere . "</option>";
                }

                // ---- Alternating row style ----
                $row_l = (fmod($row, 2) == 0)
                    ? "class='row1' onmouseover=\"this.className='row1hover';\" onmouseout=\"this.className='row1';\""
                    : "class='row2' onmouseover=\"this.className='row2hover';\" onmouseout=\"this.className='row2';\"";

                $html .= "<tr " . $row_l . " id='row_" . $id . "'>";

                    // Validity dates
                    $html .= "<td><input class='input_texte' style='width:65px;'"
                       . " name='date_debut_valid_" . $id . "' id='date_debut_valid_" . $id . "'"
                       . " value='" . $date_debut_valid . "' type='text'"
                       . " onfocus='initDatepickers(this)'"
                       . " placeholder='" . TEXT_REPERE_DATE_PLACEHOLDER . "'>"
                       . "</td>";

                    $html .= "<td><input class='input_texte' style='width:65px;'"
                       . " name='date_fin_valid_" . $id . "' id='date_fin_valid_" . $id . "'"
                       . " value='" . $date_fin_valid . "' type='text'"
                       . " onfocus='initDatepickers(this)'"
                       . " placeholder='" . TEXT_REPERE_DATE_PLACEHOLDER . "'>"
                       . "</td>";

                    // Benchmark fields
                    $html .= "<td><input class='input_texte' style='width:150px;'"
                       . " name='nature_repere_" . $id . "' value='" . $nature_repere . "'></td>";

                  if(INIT_T == 'NC')
                  {
                     $html .= "<td><select name='code_repere_" . $id . "' id='code_repere_" . $id . "' style='width:50px;'>"
                        . $code_repere_options . "</select></td>";
                  }

                    $html .= "<td><input class='input_texte' style='width:50px;'"
                       . " name='z_repere_" . $id . "' value='" . $z_repere . "'></td>";

                    $html .= "<td><input class='input_texte' style='width:110px;'"
                       . " name='precision_repere_" . $id . "' value='" . $precision_repere . "'></td>";

                    // Surveyor measurements
                    $html .= "<td><input class='input_texte' style='width:130px;'"
                       . " name='nature_repere_1_" . $id . "' value='" . $nature_repere_1 . "'></td>";

                    $html .= "<td><input class='input_texte' style='width:50px;'"
                       . " name='z_repere_g1_" . $id . "' value='" . $z_repere_g1 . "'></td>";

                    $html .= "<td><input class='input_texte' style='width:130px;'"
                       . " name='nature_repere_2_" . $id . "' value='" . $nature_repere_2 . "'></td>";

                    $html .= "<td><input class='input_texte' style='width:50px;'"
                       . " name='z_repere_g2_" . $id . "' value='" . $z_repere_g2 . "'></td>";

                    $html .= "<td><input class='input_texte' style='width:250px;'"
                       . " name='obs_" . $id . "' value='" . $obs . "'></td>";

                    // Delete: plain "X" link, consistent with list_stations.php.
                    // Calls delete_repere() which opens the Yes/No confirmation popup.
                    $html .= "<td class='t_icon' style='text-align:center;'>"
                        . "<a style='font-size:12px;font-weight:bold;cursor:pointer;'"
                        . " title='" . TEXT_REPERE_DELETE_TITLE . "'"
                        . " onClick=\"delete_repere('" . $id . "');\">X</a>"
                        . "</td>";

                $html .= "</tr>";

                $row++;
            }


            // ---- New benchmark row (key 0) ----
            $code_repere_options = '';
            foreach ($tab_code_repere as $option_code_repere)
            {
                $code_repere_options .= "<option value='" . $option_code_repere . "'>"
                                     . $option_code_repere . "</option>";
            }

            $html .= "<tr><td colspan='13' style='color:#ABB2B9;font-size:14px;'>"
               . TEXT_REPERE_ADD_ROW . "</td></tr>\n";

            $html .= "<tr>";

                $html .= "<td><input class='input_texte' style='width:65px;'"
                   . " name='date_debut_valid_0' id='date_debut_valid_0' value='' type='text'"
                   . " onfocus='initDatepickers(this)'"
                   . " placeholder='" . TEXT_REPERE_DATE_PLACEHOLDER . "'>"
                   . "</td>";

                $html .= "<td><input class='input_texte' style='width:65px;'"
                   . " name='date_fin_valid_0' id='date_fin_valid_0' value='' type='text'"
                   . " onfocus='initDatepickers(this)'"
                   . " placeholder='" . TEXT_REPERE_DATE_PLACEHOLDER . "'>"
                   . "</td>";

                $html .= "<td><input class='input_texte' style='width:150px;' name='nature_repere_0' value=''></td>";

               if(INIT_T == 'NC')
               {
                  $html .= "<td><select name='code_repere_0' id='code_repere_0' style='width:50px;'>"
                     . $code_repere_options . "</select></td>";
               }

                $html .= "<td><input class='input_texte' style='width:50px;'  name='z_repere_0'         value=''></td>";
                $html .= "<td><input class='input_texte' style='width:110px;' name='precision_repere_0' value=''></td>";
                $html .= "<td><input class='input_texte' style='width:130px;' name='nature_repere_1_0'  value=''></td>";
                $html .= "<td><input class='input_texte' style='width:50px;'  name='z_repere_g1_0'      value=''></td>";
                $html .= "<td><input class='input_texte' style='width:130px;' name='nature_repere_2_0'  value=''></td>";
                $html .= "<td><input class='input_texte' style='width:50px;'  name='z_repere_g2_0'      value=''></td>";
                $html .= "<td><input class='input_texte' style='width:250px;' name='obs_0'              value=''></td>";
                $html .= "<td>&nbsp;</td>";

            $html .= "</tr>";

        $html .= "</table>";

        return $html;
    }
}
?>