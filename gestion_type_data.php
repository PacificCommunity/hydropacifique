<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Time-series type and axis configuration page
Manages time-series definitions (CI, CIE, QI, QIE, …) and the graph axes
used to display them.
Two tabs:
  1. Time-series types (form_typedata_chron.php)
  2. Graph axes        (form_typedata_axe.php)
The single Save button serialises the whole form and posts it to
process_typedata_save.php via AJAX. Both tabs are refreshed on success.
----------------------------------------
*/

require('include/application_top.php');

$message_info       = '';
$message_suppr_chron = '';



require(DIR_WS_STRUCTURE . 'header_web.php');

echo "<body>";

    // Info/feedback bar — hidden by default, shown after AJAX actions
    echo "<div id='contenu_info' style='display:none;'></div>";

    // Full-page loading overlay — shown during the Save operation
    require(DIR_WS_STRUCTURE . 'block_wait.php');

    require(DIR_WS_STRUCTURE . 'header.php');
    include(DIR_WS_BOX       . 'nav_accueil.php');

    echo "<div id='contour_general'>";
        echo "<div id='contenu_centre'>";
            echo "<div id='contenu_box2'>";

                // Single form wrapping both tabs
                echo "<form id='formTypeData'>";

                    echo "<input type='hidden' value='" . $id_user       . "' name='id_user_agent'>";
                    echo "<input type='hidden' value='" . $territoire_id . "' name='territoire_id'>";

                    echo "<h1>";
                        echo "<span>" . TEXT_TD_PAGE_TITLE . "</span>";
                        // Save button — top right, triggers saveTypedata()
                        echo "<input class='button' name='save_typedata' id='save_typedata'"
                           . " style='float:right;'"
                           . " value='" . TEXT_TD_BTN_SAVE . "'"
                           . " onclick='saveTypedata(event);' />";
                    echo "</h1>";

                    echo "<div id='onglet'>";
                        echo "<ul id='menu_onglet'>";
                            // Tab 1: time-series types
                            echo "<li onClick=\"javascript:ChangeOnglet_2(1, 2, 'onglet-', 'contenu-');\""
                               . " id='onglet-1' class='actif'>" . TEXT_TD_TAB_CHRON . "</li>\n";
                            // Tab 2: graph axes
                            echo "<li onClick=\"javascript:ChangeOnglet_2(2, 2, 'onglet-', 'contenu-');\""
                               . " id='onglet-2' class=''>" . TEXT_TD_TAB_AXES . "</li>\n";
                        echo "</ul>";

                        echo "<div id='contenu-1' class='contenu'>";
                            require(DIR_WS_TYPEDATA . 'form_typedata_chron.php');
                        echo "</div>";

                        echo "<div id='contenu-2' class='contenu' style='display:none;'>";
                            require(DIR_WS_TYPEDATA . 'form_typedata_axe.php');
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

    var boxWait     = document.getElementById('box_wait');     // Full-page loading overlay
    var contenuInfo = document.getElementById('contenu_info'); // Feedback bar


    // -----------------------------------------------
    // saveTypedata(event)
    // Serialises formTypeData and posts it to process_typedata_save.php.
    // On completion (success or error), displays the server message inline
    // and refreshes both the time-series and axis tabs.

    function saveTypedata(event)
    {
        // Read the currently selected data type before the reload clears the DOM
        var idTypeDataSelect = document.getElementById('chron_filter').value;

        boxWait.style.display = 'block';
        event.preventDefault();

        var formData = new FormData(document.getElementById('formTypeData'));

        var xhr = new XMLHttpRequest();
        xhr.open("POST", "include/structure/typedata/process_typedata_save.php", true);

        xhr.onreadystatechange = function()
        {
            if (xhr.readyState === 4 && xhr.status === 200)
            {
                var r = JSON.parse(xhr.responseText);

                contenuInfo.innerHTML    = r['msg_info'];
                contenuInfo.style.border = r['erreur']
                    ? '2px solid #930000'
                    : '2px solid #09886d';
                contenuInfo.style.display = 'block';
                boxWait.style.display     = 'none';
            }

            // Refresh both tabs regardless of success/error
            affiche_typedata(idTypeDataSelect);
            affiche_axe();
        };

        xhr.send(formData);
    }

</script>
