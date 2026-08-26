<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Station form tab - data entry and validation
- Validates field data and station uniqueness
----------------------------------------
*/

$is_nc = ($territoire_init == 'NC');

// Wrapper: scrolls vertically when the form is taller than the viewport.
// The two cards keep their natural heights (no flex stretch).
echo "<div id='onglet_contenu' style='overflow-y:auto;height:calc(100vh - 200px);padding:0 20px;'>";

    echo "<div class='form-card form-card-main' style='float:left;width:820px;margin-top:20px;margin-right:20px;padding:20px;'>";

        // ---- Measurement type + station state ----
        echo "<div style='float:left;padding-left:15px;'>";

            // Measurement type
            echo "<div id='boite_small'>\n";
                echo "<h2>" . TEXT_FORM1_MEASURE_TYPE . "</h2>\n";
                echo "<select name='select_type_mesure' id='select_type_mesure'"
                   . " style='width:150px;height:35px;font-size:16px;font-weight:bold;'>";
                if (isset($eq_type_array))
                {
                    foreach ($eq_type_array as $key => $value)
                    {
                        $selected = ($key == $id_eq_type) ? 'selected' : '';
                        echo "<option value='" . $key . "' " . $selected . ">" . $value['nom_eq_type'] . "</option>";
                    }
                }
                echo "</select>";
            echo "</div>\n";

            // Status (active / historical)
            echo "<div id='boite_small' style='margin:0;'>\n";
                echo "<h2 style='float:left;'>" . TEXT_FORM1_STATUS . "</h2>";
                echo "<select name='select_statut_station' id='select_statut_station' style='width:160px;'>";
                    $sel = ($modif && $active_station == 1) ? 'selected' : '';
                    echo "<option value='1' " . $sel . ">" . TEXT_FORM1_STATUS_ACTIVE . "</option>";
                    $sel = ($modif && $active_station == 0) ? 'selected' : '';
                    echo "<option value='0' " . $sel . ">" . TEXT_FORM1_STATUS_HISTORICAL . "</option>";
                echo "</select>";
            echo "</div>\n";

            // Monitoring mode (continuous / spot)
            echo "<div id='boite_small' style='margin:0;'>\n";
                echo "<h2 style='float:left;'>" . TEXT_FORM1_MONITORING . "</h2>";
                echo "<select name='select_suivi_station' id='select_suivi_station' style='width:170px;'>";
                    $sel = ($modif && $suivi_station == 1) ? 'selected' : '';
                    echo "<option value='1' " . $sel . ">" . TEXT_FORM1_MONITORING_CONT . "</option>";
                    $sel = ($modif && $suivi_station == 0) ? 'selected' : '';
                    echo "<option value='0' " . $sel . ">" . TEXT_FORM1_MONITORING_SPOT . "</option>";
                echo "</select>";
            echo "</div>\n";

            // Equipment out of service (checkbox)
            echo "<div id='boite_small' style='margin:0;'>\n";
                echo "<h2 style='float:left;width:120px;'>" . TEXT_FORM1_EQUIPMENT_FAULT . "</h2><br>";
                $check = ($modif && $armee_station == 1) ? 'checked' : '';
                echo "<input type='checkbox' name='check_armee_station' id='check_armee_station'"
                   . " " . $check . " style='float:left;width:20px;height:20px;'>";
            echo "</div>\n";

        echo "</div>\n";

        // ---- Station code and name ----
        echo "<div style='float:left;margin-top:30px;padding-left:15px;'>";

            echo "<table>";

                echo "<tr>";
                    echo "<td style='width:140px;'><h2 style='color:#930000;'>" . TEXT_FORM1_CODE . "</h2></td>";
                    $val = $modif ? $code_station : '';
                    echo "<td><input name='code_station' id='code_station' value='" . $val . "' style='width:180px;' type='text'></td>";
                echo "</tr>";

                echo "<tr>";
                    echo "<td><h2 style='color:#930000;'>" . TEXT_FORM1_NAME . "</h2></td>";
                    $val = $modif ? $nom_station : '';
                    echo "<td><input name='nom_station' id='nom_station' value='" . $val . "' style='width:180px;' type='text'></td>";
                echo "</tr>";

                if ($is_nc) {
                    echo "<tr>";
                        echo "<td><h2>" . TEXT_FORM1_IRH . "</h2></td>";
                        $val = $modif ? $num_irh : '';
                        echo "<td><input name='num_irh' id='num_irh' value='" . $val . "' style='width:180px;' type='text'></td>";
                    echo "</tr>";

                    echo "<tr>";
                        echo "<td><h2>" . TEXT_FORM1_SHORT_NAME . "</h2></td>";
                        $val = $modif ? $nom_court : '';
                        echo "<td><input name='nom_court' id='nom_court' value='" . $val . "' style='width:180px;' type='text'></td>";
                    echo "</tr>";
                }

            echo "</table>";

        echo "</div>\n";


        // ---- Geographic location ----    

        echo "<div style='float:left;margin-top:30px;margin-left:50px;'>";

            echo "<table>";
                // Administrative region
                echo "<tr>";
                    echo "<td style='width:140px;'><h2>" . $territoire_region  . "</h2></td>";
                    echo "<td>";
                        $sql_region   = "SELECT id_region, nom_region FROM " . TABLE_REGION . " WHERE id_territoire=" . $territoire_id;
                        $regions_query = tep_db_query($sql_link, $sql_region);
                        echo "<select name='select_region' id='select_region' style='width:200px;'>";
                            //. "import_select_region_commune_ajax();\">";

                            //echo "<option value=''></option>"; // Aucune Option vide par défaut                   
                            foreach ($region_array as $key_region => $value_region)
                            {
                                $selected        = ($id_region == $key_region) ? 'selected' : '';
                                echo "<option value='" . $key_region . "' " . $selected . ">";
                                    echo $value_region;
                                echo "</option>";
                            }
                        echo "</select>";
                    echo "</td>";
                echo "</tr>";

                echo "<tr>";
                    echo "<td><h2>" . TEXT_FORM1_MUNICIPALITY . "</h2></td>";
                    echo "<td>";
                        echo "<select name='select_commune' id='select_commune' style='width:200px;'>";

                            echo "<option value=''></option>"; // Option vide par défaut 
                            foreach ($commune_array as $key_commune => $value_commune)
                            {
                                $selected        = ($id_commune == $key_commune) ? 'selected' : '';
                                echo "<option value='" . $key_commune . "' " . $selected . ">";
                                    echo $value_commune;
                                echo "</option>";
                            }
                            
                        echo "</select>";
                    echo "</td>";
                echo "</tr>";

                echo "<tr>";
                    echo "<td><h2>" . TEXT_FORM1_SITE . "</h2></td>";
                    $val = $modif ? $site_station : '';
                    echo "<td><input name='site_station' id='site_station' value='" . $val . "' style='width:180px;' type='text'></td>";
                echo "</tr>";

            echo "</table>";

        echo "</div>\n";

        echo "<hr>";
            
        
        // ---- Hydrographic context ----

        echo "<div style='float:left;margin-top:20px;padding-left:15px;'>";

            echo "<table>";
                // Watershed (région hydrologique)
                echo "<tr>";
                    echo "<td style='width:140px;'><h2>" . TEXT_FORM1_WATERSHED  . "</h2></td>";
                    echo "<td>";
                        echo "<select name='select_regionhydro' id='select_regionhydro' style='width:200px;'>";
                            echo "<option value=''></option>"; // Option vide par défaut 
                            if (isset($regionhydro_array))
                            {
                                foreach ($regionhydro_array as $key => $value)
                                {
                                    $selected = ($key == $id_regionhydro) ? 'selected' : '';
                                    echo "<option value='" . $key . "' " . $selected . ">" . $value . "</option>";
                                }
                            }
                        echo "</select>";
                    echo "</td>";
                echo "</tr>";

                
                // River
                echo "<tr>";
                    echo "<td style='width:140px;'><h2>" . TEXT_FORM1_RIVER . "</h2></td>";

                    // ---- Ancien champ texte libre (conservé en base : riviere_station) ----
                    // Remplacé par le select lié à geo_riviere (id_riviere).
                    // Décommenter pour revenir à la saisie texte.
                    /*
                    $val = $modif ? $nom_court : '';
                    echo "<td><input name='riviere_station' id='riviere_station' value='" . $val . "' style='width:180px;' type='text'></td>";
                    */

                    echo "<td>";
                        echo "<select name='select_riviere' id='select_riviere' style='width:200px;'>";
                            echo "<option value=''></option>"; // Option vide par défaut
                            if (isset($river_array))
                            {
                                foreach ($river_array as $key => $value)
                                {
                                    $selected = ($key == $id_riviere) ? 'selected' : '';
                                    echo "<option value='" . $key . "' " . $selected . ">" . $value . "</option>";
                                }
                            }
                        echo "</select>";
                    echo "</td>";
                echo "</tr>";

                

                // Watershed orientation
                echo "<input type='hidden' name='orientation_station' id='orientation_station' value=''>";

                /*
                echo "<tr>";
                    echo "<td><h2>" . TEXT_FORM1_ORIENTATION . "</h2></td>";
                    echo "<td>";
                        echo "<select name='orientation_station' id='orientation_station' style='width:213px;'>";
                            echo "<option value=''></option>"; // Option vide par défaut 
                            for ($i = 0; $i < count($tab_orientation); $i++)
                            {
                                $selected = ($modif && $orientation_station == $tab_orientation[$i]) ? 'selected' : '';
                                echo "<option value='" . $tab_orientation[$i] . "' " . $selected . ">" . $tab_orientation[$i] . "</option>";
                            }
                        echo "</select>";
                    echo "</td>";
                echo "</tr>";
                */

            echo "</table>";

        echo "</div>\n";

                
        echo "<div style='float:left;margin-top:20px;margin-left:50px;'>";

            echo "<table>";

                // Aquifer
                echo "<tr>";
                    echo "<td style='width:140px;'><h2>" . TEXT_FORM1_AQUIFER . "</h2></td>";
                    echo "<td>";
                        echo "<select name='select_aquifere' id='select_aquifere' style='width:200px;'>";
                           echo "<option value=''></option>"; // Option vide par défaut 
                            if (isset($aquifere_array))
                            {
                                foreach ($aquifere_array as $key => $value)
                                {
                                    $selected = ($key == $id_aquifere) ? 'selected' : '';
                                    echo "<option value='" . $key . "' " . $selected . ">" . $value . "</option>";
                                }
                            }
                        echo "</select>";
                    echo "</td>";
                echo "</tr>";

            echo "</table>";

        echo "</div>\n";        


        echo "<hr>";


        // ---- Coordinates (GPS, UTM, IGN, Lambert) ----
        echo "<div style='float:left;margin-top:25px;padding-left:15px;'>";

            echo "<table>";
                // GPS: Longitude
                echo "<tr>";
                    echo "<td style='width:140px;'><h2>" . TEXT_FORM1_LONGITUDE . "</h2></td>";
                    $val = $modif ? $longitude_station : '';
                    echo "<td><input name='longitude_station' id='longitude_station' value='" . $val . "' style='width:180px;' type='text'></td>";
                echo "</tr>";

                // GPS: Latitude
                echo "<tr>";
                    echo "<td style='width:140px;'><h2>" . TEXT_FORM1_LATITUDE . "</h2></td>";
                    $val = $modif ? $latitude_station : '';
                    echo "<td><input name='latitude_station' id='latitude_station' value='" . $val . "' style='width:180px;' type='text'></td>";
                echo "</tr>";

                echo "<tr><td colspan='2'><hr></td></tr>";

                // Altitude
                echo "<tr>";
                    echo "<td><h2>" . TEXT_FORM1_ALTITUDE . "</h2></td>";
                    $val = $modif ? $altitude_station : '';
                    echo "<td><input name='altitude_station' id='altitude_station' value='" . $val . "' style='width:80px;' type='text'></td>";
                echo "</tr>";

            echo "</table>";

        echo "</div>";

        echo "<div style='float:left;margin-top:25px;margin-left:50px;'>";

            echo "<table>";

                if ($territoire_init == 'PF')
                {
                    // UTM: X
                    echo "<tr>";
                        echo "<td style='width:140px;'><h2>" . TEXT_FORM1_UTM_X . "</h2></td>";
                        $val = $modif ? $utm_station_x : '';
                        echo "<td><input name='utm_station_x' id='utm_station_x' value='" . $val . "' style='width:180px;' type='text'></td>";
                    echo "</tr>";

                    // UTM: Y
                    echo "<tr>";
                        echo "<td><h2>" . TEXT_FORM1_UTM_Y . "</h2></td>";
                        $val = $modif ? $utm_station_y : '';
                        echo "<td><input name='utm_station_y' id='utm_station_y' value='" . $val . "' style='width:180px;' type='text'></td>";
                    echo "</tr>";
                }

                // IGN + Lambert 
                if ($territoire_init == 'NC')
                {
                    // IGN: X
                    echo "<tr>";
                        echo "<td style='width:140px;'><h2>" . TEXT_FORM1_IGN_X . "</h2></td>";
                        $val = $modif ? $ign_station_x : '';
                        echo "<td><input name='ign_station_x' id='ign_station_x' value='" . $val . "' style='width:180px;' type='text'></td>";
                    echo "</tr>";

                    // IGN: Y
                    echo "<tr>";
                        echo "<td><h2>" . TEXT_FORM1_IGN_Y . "</h2></td>";
                        $val = $modif ? $ign_station_y : '';
                        echo "<td><input name='ign_station_y' id='ign_station_y' value='" . $val . "' style='width:180px;' type='text'></td>";
                    echo "</tr>";


                    // Lambert: X
                    echo "<tr>";
                        echo "<td style='width:140px;'><h2>" . TEXT_FORM1_LAMB_X . "</h2></td>";
                        $val = $modif ? $lamb_station_x : '';
                        echo "<td><input name='lamb_station_x' id='lamb_station_x' value='" . $val . "' style='width:180px;' type='text'></td>";
                    echo "</tr>";

                    // Lambert: Y
                    echo "<tr>";
                        echo "<td><h2>" . TEXT_FORM1_LAMB_Y . "</h2></td>";
                        $val = $modif ? $lamb_station_y : '';
                        echo "<td><input name='lamb_station_y' id='lamb_station_y' value='" . $val . "' style='width:180px;' type='text'></td>";
                    echo "</tr>";
                }

            echo "</table>";

        echo "</div>";

    echo "</div>\n"; // Left card


    // ---- Description card ----
    echo "<div class='form-card form-card-aside' style='overflow:hidden;margin-top:20px;padding:20px;'>";

        // Station manager
        echo "<div style='float:left;width:100%;'>\n";
            echo "<div id='boite_small'>\n";
                echo "<h2>" . TEXT_FORM1_MANAGER . "</h2>\n";
                echo "<select name='select_fromData' id='select_fromData' style='width:270px;'>";
                foreach ($fromData_array as $id_fromData => $data)
                {
                    $selected = ($id_fromData == $id_service_user || $id_fromData == $id_service) ? 'selected' : '';
                    echo "<option value='" . $id_fromData . "' " . $selected . ">"
                       . $data['name'] . " - " . $data['description'] . "</option>";
                }
                echo "</select>\n";
            echo "</div>\n";
        echo "</div>\n";

        // Dates
        echo "<div style='float:left;width:320px;margin-top:20px;'>\n";

            // Installation date
            echo "<div id='boite_small' style='float:left;width:150px;margin:0;'>\n";
                echo "<h2>" . TEXT_FORM1_DATE_INSTALL . "</h2>\n";
                $val = $modif ? $date_installation_station : $today_fr;
                echo "<input style='width:80px;' name='date_installation_station' value='" . $val . "'"
                   . " onFocus='initDatepickers(this)' type='text' placeholder='" . TEXT_FORM1_DATE_PLACEHOLDER . "'>";
            echo "</div>\n";

            // Decommissioning date
            echo "<div id='boite_small' style='float:right;width:150px;margin:0;'>\n";
                echo "<h2>" . TEXT_FORM1_DATE_REMOVAL . "</h2>\n";
                $val = $modif ? $date_fermeture_station : '';
                echo "<input style='width:80px;' name='date_fermeture_station' value='" . $val . "'"
                   . " onFocus='initDatepickers(this)' type='text' placeholder='" . TEXT_FORM1_DATE_PLACEHOLDER . "'>";
            echo "</div>\n";

        echo "</div>\n";

        echo "<hr>\n";

        // Description textarea
        echo "<div style='width:100%;margin-top:20px;'>\n";
            echo "<h2>" . TEXT_FORM1_DESCRIPTION . "</h2>\n";
            $val = $modif ? $description_station : '';
            echo "<textarea name='description_station' id='description_station'"
               . " style='width:80%;height:325px;'>" . $val . "</textarea>\n";
        echo "</div>\n";

    echo "</div>\n";

echo "</div>\n";
?>

<script>
    $(document).ready(function()
    {
        $('#select_region').select2({
            //placeholder : '<?= TEXT_FORM1_SELECT2_REGION ?>',
            //allowClear  : true,
        });

        $('#select_commune').select2({
            placeholder : '<?= TEXT_FORM1_SELECT2_COMMUNE ?>',
            allowClear  : true,
        });

        $('#select_regionhydro').select2({
            placeholder : '<?= TEXT_FORM1_SELECT2_REGIONHYDRO ?>',
            allowClear  : true,
        });

        $('#select_riviere').select2({
            placeholder: '<?= TEXT_FORM1_SELECT2_RIVER ?>',
            allowClear: true
        });

        $('#select_aquifere').select2({
            placeholder : '<?= TEXT_FORM1_SELECT2_AQUIFER ?>',
            allowClear  : true,
        });
    });
</script>