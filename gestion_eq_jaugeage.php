<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Gauging equipment configuration page
Manages the three equipment types used in velocity-area discharge
measurements:
  Tab 1 — Propellers  (form_eq_jge_helices.php)
  Tab 2 — Current meters (form_eq_jge_moulinets.php)
  Tab 3 — Weights     (form_eq_jge_saumons.php)
The single Save button serialises the whole form and posts it to
process_dataeqjge_save.php via AJAX.
On success the page reloads with ?save=true so the active tab is
restored from localStorage and a confirmation message is shown.
----------------------------------------
*/

require('include/application_top.php');

require(DIR_WS_STRUCTURE . 'header_web.php');

echo "<body>";

    // Info/feedback bar — hidden by default, shown after AJAX actions
    echo "<div id='contenu_info' style='display:none;'></div>";

    // Shared Yes/No delete-confirmation popup for the 3 equipment tabs
    require(DIR_WS_EQJGE . 'block_eq_del_confirm.php');

    // Full-page loading overlay — shown during the Save operation
    require(DIR_WS_STRUCTURE . 'block_wait.php');

    require(DIR_WS_STRUCTURE . 'header.php');
    include(DIR_WS_BOX       . 'nav_accueil.php');

    echo "<div id='contour_general'>";
        echo "<div id='contenu_centre'>";
            echo "<div id='contenu_box2'>";

                // Single form wrapping all three tabs
                echo "<form id='formEqJGE'>";

                    echo "<input type='hidden' value='" . $id_user       . "' name='id_user_agent'>";
                    echo "<input type='hidden' value='" . $territoire_id . "' name='territoire_id'>";

                    echo "<h1>";
                        echo "<span>" . TEXT_EJ_PAGE_TITLE . "</span>";
                        // Save button — top right, triggers saveEqJGE()
                        echo "<input type='submit' class='button' name='save_eqJGE' id='save_eqJGE'"
                           . " style='float:right;'"
                           . " value='" . TEXT_EJ_BTN_SAVE . "'"
                           . " onclick='saveEqJGE(event);' />";
                    echo "</h1>";

                    echo "<div id='onglet'>";
                        echo "<ul id='menu_onglet'>";
                            // Tab 1: propellers
                            echo "<li onClick=\"javascript:ChangeOnglet_2(1, 3, 'onglet-', 'contenu-');setActiveTab(1, 3);\""
                               . " id='onglet-1' class='actif'>" . TEXT_EJ_TAB_HELICES   . "</li>\n";
                            // Tab 2: current meters
                            echo "<li onClick=\"javascript:ChangeOnglet_2(2, 3, 'onglet-', 'contenu-');setActiveTab(2, 3);\""
                               . " id='onglet-2' class=''>"       . TEXT_EJ_TAB_MOULINETS . "</li>\n";
                            // Tab 3: weights
                            echo "<li onClick=\"javascript:ChangeOnglet_2(3, 3, 'onglet-', 'contenu-');setActiveTab(3, 3);\""
                               . " id='onglet-3' class=''>"       . TEXT_EJ_TAB_SAUMONS   . "</li>\n";
                        echo "</ul>";

                        echo "<div id='contenu-1' class='contenu'>";
                            require(DIR_WS_EQJGE . 'form_eq_jge_helices.php');
                        echo "</div>";

                        echo "<div id='contenu-2' class='contenu' style='display:none;'>";
                            require(DIR_WS_EQJGE . 'form_eq_jge_moulinets.php');
                        echo "</div>";

                        echo "<div id='contenu-3' class='contenu' style='display:none;'>";
                            require(DIR_WS_EQJGE . 'form_eq_jge_saumons.php');
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
    // ?save=true — show confirmation after a successful save + page reload

    var urlParams = new URLSearchParams(window.location.search);

    if (urlParams.get('save') === 'true')
    {
        contenuInfo.innerHTML     = <?php echo json_encode(TEXT_EJ_SAVE_URL_OK); ?>;
        contenuInfo.style.display = 'block';
        contenuInfo.style.border  = '2px solid #09886d';
    }


    // -----------------------------------------------
    // saveEqJGE(event)
    // Serialises formEqJGE and posts it to process_dataeqjge_save.php.
    // On success, reloads the page with ?save=true (active tab is restored
    // from localStorage by window.onload).
    // On error, shows the server message inline without reloading.

    function saveEqJGE(event)
    {
        boxWait.style.display = 'block';
        event.preventDefault();

        var formData = new FormData(document.getElementById('formEqJGE'));

        var xhr = new XMLHttpRequest();
        xhr.open("POST", "include/structure/eq_jge/process_dataeqjge_save.php", true);

        xhr.onreadystatechange = function()
        {
            if (xhr.readyState === 4 && xhr.status === 200)
            {
                var r = JSON.parse(xhr.responseText);

                if (!r['erreur'])
                {
                    // Reload with ?save=true — window.onload restores the active tab
                    var cleanUrl = window.location.origin + window.location.pathname + '?save=true';
                    window.location.href = cleanUrl;
                }
                else
                {
                    contenuInfo.innerHTML     = r['msg_info'];
                    contenuInfo.style.display = 'block';
                    contenuInfo.style.border  = '2px solid #930000';
                }

                boxWait.style.display = 'none';
            }
        };

        xhr.send(formData);
    }


    // -----------------------------------------------
    // setActiveTab(tabIndex, totalTabs)
    // Activates the given tab and saves the choice to localStorage.

    function setActiveTab(tabIndex, totalTabs)
    {
        localStorage.setItem('activeTab', tabIndex);

        for (var i = 1; i <= totalTabs; i++)
        {
            document.getElementById('onglet-'  + i).className   = (i === tabIndex) ? 'actif' : '';
            document.getElementById('contenu-' + i).style.display = (i === tabIndex) ? 'block' : 'none';
        }
    }


    // -----------------------------------------------
    // window.onload
    // Restores the previously active tab from localStorage.

    window.onload = function()
    {
        var activeTab = parseInt(localStorage.getItem('activeTab')) || 1;
        setActiveTab(activeTab, 3);
    };

</script>