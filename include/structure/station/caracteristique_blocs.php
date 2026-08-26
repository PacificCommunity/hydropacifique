<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Piezo characteristics blocks renderer (shared)
- Single source of truth for the characteristics observation blocks
- Used by:
    * form_station_caracteristique.php  (initial page render)
    * process_loadcaracteristique.php   (AJAX reload after save, no page refresh)
----------------------------------------
*/

if (!function_exists('render_caracteristique_blocs'))
{
    /**
     * Build and return the characteristics observation blocks for a station.
     *
     * @param mysqli $sql_link    Active database connection
     * @param int    $id_station  Station ID
     * @param string $today_fr    Today's date (dd-mm-yyyy) for the new-observation block
     * @return string             Blocks HTML
     */
    function render_caracteristique_blocs($sql_link, $id_station, $today_fr)
    {
        $id_station = (int) $id_station; // cast: used directly in the WHERE clause

        // -----------------------------------------------
        // Data loading
        $nb_caract = 0;

        $tab_etat = [
            '',
            TEXT_ETAT_BON,
            TEXT_ETAT_MOYEN,
            TEXT_ETAT_MAUVAIS,
            TEXT_ETAT_ABANDONNE,
            TEXT_ETAT_COLMATE,
            TEXT_ETAT_REBOUCHE,
            TEXT_ETAT_NON_ACCESSIBLE,
            TEXT_ETAT_DISPARU,
        ];

        // -----------------------------------------------
        // Query: Borehole head schemas

        $sql_piezo_schema   = "SELECT DISTINCT id, nom_schema, img, capot, dist_ct, dist_td, dist_ds
                               FROM " . TABLE_STATION_PIEZO_SCHEMA . "
                               ORDER BY id ASC";
        $piezo_schema_query = tep_db_query($sql_link, $sql_piezo_schema);
        while ($piezo_schema_tab = tep_db_fetch_array($piezo_schema_query))
        {
            $piezo_schema_array[$piezo_schema_tab['id']] = [
                'nom_schema' => $piezo_schema_tab['nom_schema'],
                'img'        => $piezo_schema_tab['img'],
                'capot'      => $piezo_schema_tab['capot'],
                'dist_ct'    => $piezo_schema_tab['dist_ct'],
                'dist_td'    => $piezo_schema_tab['dist_td'],
                'dist_ds'    => $piezo_schema_tab['dist_ds'],
            ];
        }


        // -----------------------------------------------
        // Default empty observation (key 0 = new record form)

        $caract_array[0] = [
            'date_caract'             => $today_fr,
            'prof'                    => '',
            'materiaux_tete'          => '',
            'dim_tete_ext'            => '',
            'materiaux_tub_inter'     => '',
            'diam_tub_inter'          => '',
            'materiaux_dalle'         => '',
            'dim_dalle'               => '',
            'dist_capto_tube'         => '',
            'dist_tube_dalle'         => '',
            'dist_dalle_sol'          => '',
            'presence_capot'          => 0,
            'etat'                    => '',
            'activite'                => 0,
            'utilisation'             => '',
            'equipement_exploitation' => '',
            'schema_tete'             => 0,
            'schema_protect'          => 0,
            'obs'                     => '',
        ];

        $hidden_id_caract = '';


        // -----------------------------------------------
        // Query: Existing characteristics records for this station

        $sql_caract   = "SELECT DISTINCT c.id, c.date, c.prof, c.materiaux_tete, c.dim_tete_ext,
                                c.materiaux_tub_inter, c.diam_tub_inter, c.materiaux_dalle, c.dim_dalle,
                                c.dist_capto_tube, c.dist_tube_dalle, c.dist_dalle_sol, c.presence_capot,
                                c.etat, c.activite, c.utilisation, c.equipement_exploitation,
                                c.schema_tete, c.schema_protect, c.obs
                         FROM " . TABLE_STATION_PIEZO_CARACTERISTIQUE . " c
                         WHERE id_station = " . $id_station . "
                         ORDER BY date DESC";
        $caract_query = tep_db_query($sql_link, $sql_caract);

        while ($caract_tab = tep_db_fetch_array($caract_query))
        {
            $nb_caract++;
            $id_caract         = $caract_tab['id'];
            $hidden_id_caract .= $id_caract . '_';

            $date_caract = ($caract_tab['date'] == '0000-00-00') ? '' : dateus_fr($caract_tab['date']);

            $diam_tub_inter  = round(floatval(str_replace(',', '.', html_entity_decode($caract_tab['diam_tub_inter']  ?? ''))), 3);
            $dist_capto_tube = round(floatval(str_replace(',', '.', html_entity_decode($caract_tab['dist_capto_tube'] ?? ''))), 3);
            $dist_tube_dalle = round(floatval(str_replace(',', '.', html_entity_decode($caract_tab['dist_tube_dalle'] ?? ''))), 3);
            $dist_dalle_sol  = round(floatval(str_replace(',', '.', html_entity_decode($caract_tab['dist_dalle_sol']  ?? ''))), 3);

            $caract_array[$id_caract] = [
                'date_caract'             => $date_caract,
                'prof'                    => html_entity_decode($caract_tab['prof']                    ?? ''),
                'materiaux_tete'          => html_entity_decode($caract_tab['materiaux_tete']          ?? ''),
                'dim_tete_ext'            => html_entity_decode($caract_tab['dim_tete_ext']            ?? ''),
                'materiaux_tub_inter'     => html_entity_decode($caract_tab['materiaux_tub_inter']     ?? ''),
                'diam_tub_inter'          => $diam_tub_inter,
                'materiaux_dalle'         => html_entity_decode($caract_tab['materiaux_dalle']         ?? ''),
                'dim_dalle'               => html_entity_decode($caract_tab['dim_dalle']               ?? ''),
                'dist_capto_tube'         => $dist_capto_tube,
                'dist_tube_dalle'         => $dist_tube_dalle,
                'dist_dalle_sol'          => $dist_dalle_sol,
                'presence_capot'          => $caract_tab['presence_capot'],
                'etat'                    => html_entity_decode($caract_tab['etat']                    ?? ''),
                'activite'                => $caract_tab['activite'],
                'utilisation'             => html_entity_decode($caract_tab['utilisation']             ?? ''),
                'equipement_exploitation' => html_entity_decode($caract_tab['equipement_exploitation'] ?? ''),
                'schema_tete'             => html_entity_decode($caract_tab['schema_tete']             ?? ''),
                'schema_protect'          => $caract_tab['schema_protect'],
                'obs'                     => nettoyer_et_echapper($caract_tab['obs']                   ?? ''),
            ];
        }

        $hidden_id_caract = rtrim($hidden_id_caract, '_');

        // -----------------------------------------------
        // HTML output (buffered into $html)
        $html = '';

            foreach ($caract_array as $key => $value)
            {
                // Default: existing observation (teal accent on the left)
                $accent_class   = 'caract-existing';
                $titre          = TEXT_CARACT_OBS_TITLE;
                $display_newBox = '';

                if ($key == 0) // New observation block (red accent on the left)
                {
                    $accent_class   = 'caract-new';
                    $titre          = TEXT_CARACT_NEW_OBS_TITLE;
                    $display_newBox = 'display:none;';
                }

                $check_schema_protect = ($value['schema_protect'] == 1) ? 'checked' : '';
                $check_presence_capot = ($value['presence_capot'] == 1) ? 'checked' : '';
                $check_enActivite     = ($value['activite']       == 1) ? 'checked' : '';

                // ---- Condition dropdown options ----
                $etat_options = '';
                foreach ($tab_etat as $option_etat)
                {
                    $selected      = ($value['etat'] == $option_etat) ? 'selected' : '';
                    $etat_options .= "<option value='" . $option_etat . "' " . $selected . ">"
                                   . $option_etat . "</option>";
                }

                // ---- Schema dropdown options ----
                $schema_options = "<option value=''></option>";
                foreach ($piezo_schema_array as $key_schema => $value_schema)
                {
                    $selected        = ($value['schema_tete'] == $key_schema) ? 'selected' : '';
                    $schema_options .= "<option value='" . $key_schema . "' " . $selected . ">"
                                     . $value_schema['nom_schema'] . "</option>";
                }

                $html .= "<div id='bloc_caract_" . $key . "' class='bloc-caract " . $accent_class . "'"
                   . " style='margin:10px;padding:10px 15px;background-color:#fff;" . $display_newBox . "'>\n";

                    // ---- Delete button (round SVG, grey at rest, red on hover) ----
                    $html .= "<button type='button' class='caract-delete'"
                       . " onclick=\"del_caracteristique('" . $key . "');\""
                       . " title='" . TEXT_CARACT_DELETE_TITLE . "'>";
                        $html .= "<svg viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'>";
                            $html .= "<line x1='6' y1='6' x2='18' y2='18'/>";
                            $html .= "<line x1='6' y1='18' x2='18' y2='6'/>";
                        $html .= "</svg>";
                    $html .= "</button>";

                    // ---- Left column: well construction data ----
                    $html .= "<div style='float:left;width:640px;margin-right:3%;'>\n";

                        $html .= "<div id='boite1' class='first' style='float:left;width:100%;margin:0;"
                           . "padding-bottom:5px;border-bottom:2px solid #176B87;'>\n";
                            $html .= "<p class='titre_box'>";
                                $html .= "<span style='float:left;'>" . $titre . "</span>";
                                $html .= "<input class='input_texte' style='width:80px;margin-left:10px;margin-top:-5px;text-align:center;'
                                   		name='date_caract_" . $key . "' id='date_caract_" . $key . "'
                                   		value='" . $value['date_caract'] . "' type='text'
        								onfocus='initDatepickers(this)'
                                		placeholder='".TEXT_CARACT_DATE_PLACEHOLDER."'>";
                            $html .= "</p>";
                        $html .= "</div>\n";

                        $html .= "<hr>";

                        // Left sub-column
                        $html .= "<div id='boite1' style='float:left;width:300px;margin:0;'>\n";

                            $html .= "<div id='boite_small' style='width:300px;'>\n";
                                $html .= "<h2 style='float:left;width:150px;padding-top:5px;'>" . TEXT_CARACT_DEPTH . "</h2>\n";
                                $html .= "<input name='prof_" . $key . "' id='prof_" . $key . "'"
                                   . " value='" . $value['prof'] . "' class='input_texte' style='width:80px;' type='text'>";
                            $html .= "</div>\n";

                            $html .= "<hr>\n";

                            $html .= "<div id='boite_small' style='width:300px;'>\n";
                                $html .= "<h2 style='float:left;width:150px;padding-top:5px;'>" . TEXT_CARACT_HEAD_MATERIAL . "</h2>\n<br>";
                                $html .= "<input name='materiaux_tete_" . $key . "' id='materiaux_tete_" . $key . "'"
                                   . " value='" . $value['materiaux_tete'] . "' class='input_texte' style='width:280px;' type='text'>";
                            $html .= "</div>\n";

                            $html .= "<hr>\n";

                            $html .= "<div id='boite_small' style='width:300px;'>\n";
                                $html .= "<h2 style='float:left;width:150px;padding-top:5px;'>" . TEXT_CARACT_EXT_DIM . "</h2>\n";
                                $html .= "<input name='dim_tete_ext_" . $key . "' id='dim_tete_ext_" . $key . "'"
                                   . " value='" . $value['dim_tete_ext'] . "' class='input_texte' type='text'>";
                            $html .= "</div>\n";

                            $html .= "<hr>\n";

                            $html .= "<div id='boite_small' style='width:300px;'>\n";
                                $html .= "<h2 style='float:left;width:150px;padding-top:5px;'>" . TEXT_CARACT_CASING_MATERIAL . "</h2>\n";
                                $html .= "<input name='materiaux_tub_inter_" . $key . "' id='materiaux_tub_inter_" . $key . "'"
                                   . " value='" . $value['materiaux_tub_inter'] . "' class='input_texte' type='text'>";
                            $html .= "</div>\n";

                            $html .= "<hr>\n";

                            $html .= "<div id='boite_small' style='width:300px;'>\n";
                                $html .= "<h2 style='float:left;width:150px;padding-top:5px;'>" . TEXT_CARACT_CASING_DIM . "</h2>\n";
                                $html .= "<input name='diam_tub_inter_" . $key . "' id='diam_tub_inter_" . $key . "'"
                                   . " value='" . $value['diam_tub_inter'] . "' class='input_texte' style='width:80px;' type='text'>";
                            $html .= "</div>\n";

                            $html .= "<hr>\n";

                            $html .= "<div id='boite_small' style='width:300px;'>\n";
                                $html .= "<h2 style='float:left;width:150px;padding-top:5px;'>" . TEXT_CARACT_SCHEMA . "</h2>\n";
                                $html .= "<select name='schema_tete_" . $key . "' id='schema_tete_" . $key . "' style='width:120px;'>"
                                   . $schema_options . "</select>";
                            $html .= "</div>\n";

                            $html .= "<hr>\n";

                            $html .= "<div id='boite_small' style='width:300px;'>\n";
                                $html .= "<h2 style='float:left;width:150px;padding-top:5px;'>" . TEXT_CARACT_PROTECTION . "</h2>\n";
                                $html .= "<input type='checkbox' name='schema_protect_" . $key . "' id='schema_protect_" . $key . "'"
                                   . " style='float:left;width:20px;height:20px;margin:0;' " . $check_schema_protect . ">";
                            $html .= "</div>\n";

                        $html .= "</div>\n"; // Left sub-column

                        // Right sub-column
                        $html .= "<div id='boite1' style='float:left;width:300px;'>\n";

                            $html .= "<div id='boite_small' style='width:300px;'>\n";
                                $html .= "<h2 style='float:left;width:150px;padding-top:5px;'>" . TEXT_CARACT_SLAB_MATERIAL . "</h2>\n";
                                $html .= "<input name='materiaux_dalle_" . $key . "' id='materiaux_dalle_" . $key . "'"
                                   . " value='" . $value['materiaux_dalle'] . "' class='input_texte' type='text'>";
                            $html .= "</div>\n";

                            $html .= "<hr>\n";

                            $html .= "<div id='boite_small' style='width:300px;'>\n";
                                $html .= "<h2 style='float:left;width:150px;padding-top:5px;'>" . TEXT_CARACT_SLAB_DIM . "</h2>\n";
                                $html .= "<input name='dim_dalle_" . $key . "' id='dim_dalle_" . $key . "'"
                                   . " value='" . $value['dim_dalle'] . "' class='input_texte' type='text'>";
                            $html .= "</div>\n";

                            $html .= "<hr>\n";

                            $html .= "<div id='boite_small' style='width:300px;'>\n";
                                $html .= "<h2 style='float:left;width:150px;padding-top:5px;'>" . TEXT_CARACT_CAP_PRESENT . "</h2>\n";
                                $html .= "<input type='checkbox' name='presence_capot_" . $key . "' id='presence_capot_" . $key . "'"
                                   . " style='float:left;width:20px;height:20px;margin:0;' " . $check_presence_capot . ">";
                            $html .= "</div>\n";

                            $html .= "<hr>\n";

                            $html .= "<div id='boite_small' style='width:300px;'>\n";
                                $html .= "<h2 style='float:left;width:150px;padding-top:5px;'>" . TEXT_CARACT_DIST_CAP_TUBE . "</h2>\n";
                                $html .= "<input name='dist_capto_tube_" . $key . "' id='dist_capto_tube_" . $key . "'"
                                   . " value='" . $value['dist_capto_tube'] . "' class='input_texte' style='width:80px;' type='text'>";
                            $html .= "</div>\n";

                            $html .= "<hr>\n";

                            $html .= "<div id='boite_small' style='width:300px;'>\n";
                                $html .= "<h2 style='float:left;width:150px;padding-top:5px;'>" . TEXT_CARACT_DIST_TUBE_SLAB . "</h2>\n";
                                $html .= "<input name='dist_tube_dalle_" . $key . "' id='dist_tube_dalle_" . $key . "'"
                                   . " value='" . $value['dist_tube_dalle'] . "' class='input_texte' style='width:80px;' type='text'>";
                            $html .= "</div>\n";

                            $html .= "<hr>\n";

                            $html .= "<div id='boite_small' style='width:300px;'>\n";
                                $html .= "<h2 style='float:left;width:150px;padding-top:5px;'>" . TEXT_CARACT_DIST_SLAB_GROUND . "</h2>\n";
                                $html .= "<input name='dist_dalle_sol_" . $key . "' id='dist_dalle_sol_" . $key . "'"
                                   . " value='" . $value['dist_dalle_sol'] . "' class='input_texte' style='width:80px;' type='text'>";
                            $html .= "</div>\n";

                        $html .= "</div>\n"; // Right sub-column

                    $html .= "</div>\n"; // Left column

                    // ---- Right column: usage data ----
                    $html .= "<div style='float:left;width:320px;'>\n";

                        $html .= "<div id='boite1' class='first' style='float:left;width:100%;margin:0;"
                           . "padding-bottom:1px;border-bottom:2px solid #176B87;'>\n";
                            $html .= "<p class='titre_box'>" . TEXT_CARACT_USAGE_TITLE . "</p>";
                        $html .= "</div>\n";

                        $html .= "<hr>";

                        $html .= "<div id='boite1' style='float:left;width:300px;margin:0;'>\n";

                            $html .= "<div id='boite_small' style='width:300px;'>\n";
                                $html .= "<h2 style='float:left;width:150px;padding-top:5px;'>" . TEXT_CARACT_STATE . "</h2>\n";
                                $html .= "<select name='etat_" . $key . "' id='etat_" . $key . "' style='width:120px;'>"
                                   . $etat_options . "</select>";
                            $html .= "</div>\n";

                            $html .= "<hr>\n";

                            $html .= "<div id='boite_small' style='width:300px;'>\n";
                                $html .= "<h2 style='float:left;width:150px;padding-top:5px;'>" . TEXT_CARACT_ACTIVE . "</h2>\n";
                                $html .= "<input type='checkbox' name='activite_" . $key . "' id='activite_" . $key . "'"
                                   . " style='float:left;width:20px;height:20px;margin:0;' " . $check_enActivite . ">";
                            $html .= "</div>\n";

                            $html .= "<hr>\n";

                            $html .= "<div id='boite_small' style='width:300px;'>\n";
                                $html .= "<h2 style='float:left;width:150px;padding-top:5px;'>" . TEXT_CARACT_USAGE . "</h2>\n";
                                $html .= "<input name='utilisation_" . $key . "' id='utilisation_" . $key . "'"
                                   . " value='" . $value['utilisation'] . "' class='input_texte' type='text'>";
                            $html .= "</div>\n";

                            $html .= "<hr>\n";

                            $html .= "<div id='boite_small' style='width:300px;'>\n";
                                $html .= "<h2 style='float:left;width:150px;padding-top:5px;'>" . TEXT_CARACT_EQUIPMENT . "</h2>\n";
                                $html .= "<input name='equipement_exploitation_" . $key . "' id='equipement_exploitation_" . $key . "'"
                                   . " value='" . $value['equipement_exploitation'] . "' class='input_texte' type='text'>";
                            $html .= "</div>\n";

                            $html .= "<hr>\n";

                            $html .= "<div id='boite_small' style='width:300px;'>\n";
                                $html .= "<h2 style='float:left;width:150px;padding-top:5px;'>" . TEXT_CARACT_OBSERVATIONS . "</h2>\n<br>\n";
                                $html .= "<textarea name='obs_" . $key . "' id='obs_" . $key . "'"
                                   . " style='width:100%;height:80px;'>" . $value['obs'] . "</textarea>\n";
                            $html .= "</div>\n";

                        $html .= "</div>\n";

                    $html .= "</div>\n"; // Right column

                $html .= "<hr>";
                $html .= "</div>\n"; // bloc_caract
    

            } // foreach

        return $html;
    }
}
?>