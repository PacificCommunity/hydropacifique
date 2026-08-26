<?php
/*
----------------------------------------
Copyright (c) 2025 - Vai-Natura
----------------------------------------
Plateform information tab — included by modif_plateform.php.
Shows territoire settings: region theme, default region, and map
configuration (center coordinates, zoom levels).
Values come from the unique territoire record matching INIT_T.
Initials and name are read-only — they identify the territoire and
must not be changed from this screen.
----------------------------------------
*/

// ----------------------------------------
// Load regions list for the "Default region" dropdown
// (regions belonging to the current territoire)
// ----------------------------------------
$sql_regions = "SELECT id_region, nom_region
                FROM " . TABLE_REGION . "
                WHERE id_territoire = '" . $territoire['id_territoire'] . "'
                ORDER BY nom_region ASC";
$regions_query = tep_db_query($sql_link, $sql_regions);

$regions_array = array();
while ($r = tep_db_fetch_array($regions_query))
{
    $regions_array[$r['id_region']] = $r['nom_region'];
}

// ----------------------------------------
// PHP timezone list — all available timezones, grouped by region
// (Africa/, America/, Asia/, Europe/, Pacific/, etc.)
// ----------------------------------------
$timezones_list = DateTimeZone::listIdentifiers();



?>

<div id='onglet_contenu' style='padding:0 20px;padding-top:10px;overflow-y:auto;max-height:calc(100vh - 200px);'>

    <div style='float:left;width:700px;margin-bottom:20px;padding:15px 0;
                border:1px solid #000;border-radius:4px;background-color:#fff;
                box-shadow:5px 20px 38px -27px #232323;'>

        <div style='float:left;padding-left:15px;'>

            <?php
            // ============================================================
            // Block 1 — Territoire identification (read-only)
            // ============================================================
            ?>
            <table>
                <!-- Territoire initials (NC, PF, WF) — read-only -->
                <tr>
                    <td style='width:140px;'><h2 style='color:#930000;'><?php echo TEXT_PF_F1_INIT; ?></h2></td>
                    <td style='width:250px;'>
                        <span style='font-weight:bold;'><?php echo $territoire['init_territoire']; ?></span>
                    </td>
                </tr>

                <!-- Territoire full name — read-only -->
                <tr>
                    <td><h2><?php echo TEXT_PF_F1_NOM; ?></h2></td>
                    <td>
                        <span style='font-weight:bold;'><?php echo $territoire['nom_territoire']; ?></span>
                    </td>
                </tr>
            </table>

            <hr>

            <?php
            // ============================================================
            // Block 2 — Regional settings
            // ============================================================
            ?>
            <table>
                <!-- Region theme -->
                <tr>
                    <td style='width:140px;'><h2><?php echo TEXT_PF_F1_THEME; ?></h2></td>
                    <td>
                        <input name='theme_region' id='theme_region'
                               value='<?php echo $territoire['theme_region']; ?>'
                               style='width:250px;' type='text'>
                    </td>
                </tr>

                <!-- Default region — dropdown sourced from TABLE_REGION -->
                <tr>
                    <td><h2><?php echo TEXT_PF_F1_REGION_DEFAULT; ?></h2></td>
                    <td>
                        <select name='region_default' id='region_default' style='width:262px;'>
                            <?php
                            foreach ($regions_array as $id_region => $nom_region)
                            {
                                $selected = ($id_region == $territoire['region_default']) ? 'selected' : '';
                                echo "<option value='" . $id_region . "' " . $selected . ">"
                                        . $nom_region . "</option>";
                            }
                            ?>
                        </select>
                    </td>
                </tr>

                <!-- Hydro service -->
                <tr>
                    <td><h2><?php echo TEXT_PF_F1_SERVICE_HYDRO; ?></h2></td>
                    <td>
                        <input name='service_hydro' id='service_hydro'
                               value='<?php echo $territoire['service_hydro']; ?>'
                               style='width:250px;' type='text'>
                    </td>
                </tr>
            </table>

            <hr>

            <?php
            // ============================================================
            // Block 3 — Locale and language
            // ============================================================
            ?>
            <table>
                <!-- PHP timezone — full list from DateTimeZone::listIdentifiers() -->
                <tr>
                    <td style='width:140px;'><h2><?php echo TEXT_PF_F1_TIMEZONE; ?></h2></td>
                    <td>
                        <select name='timezone_php' id='timezone_php' style='width:262px;'>
                            <?php
                            foreach ($timezones_list as $tz)
                            {
                                $selected = ($tz == $territoire['timezone_php']) ? 'selected' : '';
                                echo "<option value='" . $tz . "' " . $selected . ">" . $tz . "</option>";
                            }
                            ?>
                        </select>
                    </td>
                </tr>

                <!-- Language — display "code - Label", store code only -->
                <tr>
                    <td><h2><?php echo TEXT_PF_F1_LANG; ?></h2></td>
                    <td>
                        <select name='lang' id='lang' style='width:262px;'>
                            <?php
                            foreach ($languages_array as $code => $label)
                            {
                                $selected = ($code == $territoire['lang']) ? 'selected' : '';
                                echo "<option value='" . $code . "' " . $selected . ">" . $label . "</option>";
                            }
                            ?>
                        </select>
                    </td>
                </tr>
            </table>

            <hr>

            <?php
            // ============================================================
            // Block 4 — Map configuration
            // ============================================================
            ?>
            <table>
                <!-- Longitude (map center) -->
                <tr>
                    <td style='width:140px;'><h2><?php echo TEXT_PF_F1_MAP_LONG; ?></h2></td>
                    <td>
                        <input name='mapLong' id='mapLong'
                               value='<?php echo $territoire['mapLong']; ?>'
                               style='width:120px;' type='text'>
                    </td>
                </tr>

                <!-- Latitude (map center) -->
                <tr>
                    <td><h2><?php echo TEXT_PF_F1_MAP_LAT; ?></h2></td>
                    <td>
                        <input name='mapLat' id='mapLat'
                               value='<?php echo $territoire['mapLat']; ?>'
                               style='width:120px;' type='text'>
                    </td>
                </tr>

                <!-- Default zoom level (2 to 16) -->
                <tr>
                    <td><h2><?php echo TEXT_PF_F1_MAP_ZOOM; ?></h2></td>
                    <td>
                        <select name='mapZoom' id='mapZoom' style='width:80px;'>
                            <?php
                            for ($z = 2; $z <= 16; $z++)
                            {
                                $selected = ($z == $territoire['mapZoom']) ? 'selected' : '';
                                echo "<option value='" . $z . "' " . $selected . ">" . $z . "</option>";
                            }
                            ?>
                        </select>
                    </td>
                </tr>

                <!-- Minimum zoom level (2 to 5) -->
                <tr>
                    <td><h2><?php echo TEXT_PF_F1_MAP_MIN_ZOOM; ?></h2></td>
                    <td>
                        <select name='mapMinZoom' id='mapMinZoom' style='width:80px;'>
                            <?php
                            for ($z = 2; $z <= 5; $z++)
                            {
                                $selected = ($z == $territoire['mapMinZoom']) ? 'selected' : '';
                                echo "<option value='" . $z . "' " . $selected . ">" . $z . "</option>";
                            }
                            ?>
                        </select>
                    </td>
                </tr>
            </table>

        </div>

    </div>

<hr>
</div>


<script>
    $(document).ready(function()
    {
        $('#timezone_php').select2({
            //placeholder : '',
            //allowClear  : true,
        });

        $('#lang').select2({
            //placeholder : '',
            //allowClear  : true,
        });

        $('#region_default').select2({
            //placeholder : '',
            //allowClear  : true,
        });
    });
</script>