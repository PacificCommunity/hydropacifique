<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Geographic data management page
Entry point for all geographic reference data:
regions, towns, hydrological regions, rivers, aquifers, rounds.
Each tab loads its data asynchronously via AJAX on page display.
The Save button submits the entire form in a single atomic transaction
via process_datageo_save.php.
----------------------------------------
*/

require('include/application_top.php');

// -----------------------------------------------
// Query: territoire metadata
// theme_region provides the custom label used as a suffix in tab and column headers

$sql_territoire = "SELECT DISTINCT nom_territoire, init_territoire, theme_region, region_default
                   FROM " . TABLE_TERRITOIRE . "
                   WHERE id_territoire = " . $territoire_id . "
                   ORDER BY LOWER(nom_territoire) ASC";

$territoire_query = tep_db_query($sql_link, $sql_territoire);
while ($territoire = tep_db_fetch_array($territoire_query))
{
    $nom_territoire  = html_entity_decode($territoire['nom_territoire']  ?? '');
    $init_territoire = html_entity_decode($territoire['init_territoire'] ?? '');
    $theme_region    = html_entity_decode($territoire['theme_region']    ?? '');
    $region_default  = $territoire['region_default'];
}


// -----------------------------------------------
// Page structure

require(DIR_WS_STRUCTURE . 'header_web.php');

echo "<body>";

    // Info/feedback bar — hidden by default, shown after AJAX save or delete
    echo "<div id='contenu_info' style='display:none;'></div>";

    // Full-page loading overlay — shown during the global Save operation
    require(DIR_WS_STRUCTURE . 'block_wait.php');

    // Reusable confirmation popup for any geo entity deletion
    require(DIR_WS_GEO . 'block_verifdel_geo.php');

    require(DIR_WS_STRUCTURE . 'header.php');
    include(DIR_WS_BOX       . 'nav_accueil.php');

    echo "<div id='contour_general'>";
        echo "<div id='contenu_centre'>";
            echo "<div id='contenu_box2'>";

                // Single form wrapping all tabs so all data is submitted together
                echo "<form id='formDataGeo'>";

                    echo "<input type='hidden' value='" . $id_user       . "' name='id_user_agent'>";
                    echo "<input type='hidden' value='" . $territoire_id . "' name='territoire_id'>";

                    echo "<h1>";
                        echo "<span>" . TEXT_GEO_PAGE_TITLE . "</span>";
                        // Save button — top right, triggers saveDataGeo()
                        echo "<input class='button' name='save_dataGeo' id='save_dataGeo'"
                           . " style='float:right;width:110px !important;font-size:13px;font-weight:normal;padding:6px 10px;margin-top:8px;'"
                           . " value='" . TEXT_GEO_BTN_SAVE . "'"
                           . " onclick='saveDataGeo(event);' />";
                    echo "</h1>";

                    // ---- Tab navigation ----
                    echo "<div id='onglet'>";
                        echo "<ul id='menu_onglet'>";
                            // Tab 1: geographic regions — label includes the territory's custom region type name
                            echo "<li onClick=\"ChangeOnglet_2(1, 6, 'onglet-', 'contenu-');\" id='onglet-1' class='actif' style='width:150px;'>"
                               . TEXT_GEO_TAB_REGION . $theme_region . "</li>\n";
                            echo "<li onClick=\"ChangeOnglet_2(2, 6, 'onglet-', 'contenu-');\" id='onglet-2' class='' style='width:80px;'>"
                               . TEXT_GEO_TAB_COMMUNES . "</li>\n";
                            echo "<li onClick=\"ChangeOnglet_2(3, 6, 'onglet-', 'contenu-');\" id='onglet-3' class=''>"
                               . TEXT_GEO_TAB_REGIONHYDRO . "</li>\n";
                            echo "<li onClick=\"ChangeOnglet_2(4, 6, 'onglet-', 'contenu-');\" id='onglet-4' class='' style='width:80px;'>"
                               . TEXT_GEO_TAB_RIVIERES . "</li>\n";
                            echo "<li onClick=\"ChangeOnglet_2(5, 6, 'onglet-', 'contenu-');\" id='onglet-5' class='' style='width:100px;'>"
                               . TEXT_GEO_TAB_AQUIFERES . "</li>\n";
                            echo "<li onClick=\"ChangeOnglet_2(6, 6, 'onglet-', 'contenu-');\" id='onglet-6' class='' style='width:80px;'>"
                               . TEXT_GEO_TAB_TOURNEES . "</li>\n";
                        echo "</ul>";

                        // Tab content panels — each sub-file defines its own AJAX load function
                        echo "<div id='contenu-1' class='contenu'>";
                            require(DIR_WS_GEO . 'form_geo_regiongeo.php');
                        echo "</div>";

                        echo "<div id='contenu-2' class='contenu' style='display:none;'>";
                            require(DIR_WS_GEO . 'form_geo_commune.php');
                        echo "</div>";

                        echo "<div id='contenu-3' class='contenu' style='display:none;'>";
                            require(DIR_WS_GEO . 'form_geo_regionhydro.php');
                        echo "</div>";

                        echo "<div id='contenu-4' class='contenu' style='display:none;'>";
                            require(DIR_WS_GEO . 'form_geo_riviere.php');
                        echo "</div>";

                        echo "<div id='contenu-5' class='contenu' style='display:none;'>";
                            require(DIR_WS_GEO . 'form_geo_aquifere.php');
                        echo "</div>";

                        echo "<div id='contenu-6' class='contenu' style='display:none;'>";
                            require(DIR_WS_GEO . 'form_geo_tournee.php');
                        echo "</div>";

                    echo "</div>"; // onglet

                echo "</form>\n";

            echo "<hr>";
            echo "</div>"; // contenu_box2
        echo "<hr>";
        echo "</div>"; // contenu_centre
    echo "<hr>";
    echo "</div>"; // contour_general

    require('include/application_bottom.php');

echo "</body>";
echo "</html>";
?>

<script>

    // -----------------------------------------------
    // Page-level DOM references

    var boxWait     = document.getElementById('box_wait');      // Full-page loading overlay
    var contenuInfo = document.getElementById('contenu_info');  // Feedback bar


    // -----------------------------------------------
    // saveDataGeo(event)
    // Serialises the entire formDataGeo and posts it to process_datageo_save.php.
    // All six entity types (regions, towns, hydrological regions, rivers,
    // aquifers, rounds) are saved atomically in a single server-side transaction.
    // After the response arrives, all six AJAX tables are refreshed so the user
    // always sees the current database state regardless of outcome.

    function saveDataGeo(event)
    {
        boxWait.style.display = 'block';

        var formData = new FormData(document.getElementById('formDataGeo'));

        var xhr = new XMLHttpRequest();
        xhr.open("POST", "include/structure/geographie/process_datageo_save.php", true);

        xhr.onreadystatechange = function()
        {
            if (xhr.readyState === 4 && xhr.status === 200)
            {
                var r = JSON.parse(xhr.responseText);

                contenuInfo.innerHTML     = r['msg_info'];
                contenuInfo.style.border  = r['erreur'] ? '2px solid #930000' : '2px solid #09886d';
                contenuInfo.style.display = 'block';
                boxWait.style.display     = 'none';
            }

            // Always refresh all tables, even on error, to reflect actual DB state
            affiche_geo_region_data();
            affiche_geo_commune_data();
            affiche_geo_regionhydro_data();
            affiche_geo_riviere_data();
            affiche_geo_aquifere_data();
            affiche_geo_tournee_data();
        };

        xhr.send(formData);
    }

</script>
