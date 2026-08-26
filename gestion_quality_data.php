<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Quality code configuration page
Allows users to create, edit, and delete data quality codes (codes qualité).
The form is submitted via AJAX to process_qualitydata_save.php.
On success, the page reloads with ?save=true so the confirmation message
persists across the redirect.
----------------------------------------
*/

require('include/application_top.php');

require(DIR_WS_STRUCTURE . 'header_web.php');

echo "<body>";

    // Info/feedback bar — hidden by default, shown after AJAX actions or on page reload
    echo "<div id='contenu_info' style='display:none;'></div>";

    // Full-page loading overlay — shown during the Save operation
    require(DIR_WS_STRUCTURE . 'block_wait.php');

    require(DIR_WS_STRUCTURE . 'header.php');
    include(DIR_WS_BOX       . 'nav_accueil.php');

    echo "<div id='contour_general'>";
        echo "<div id='contenu_centre'>";
            echo "<div id='contenu_box2'>";

                // Single form wrapping the quality code table
                echo "<form id='formQualityData'>";

                    echo "<input type='hidden' value='" . $id_user       . "' name='id_user_agent'>";
                    echo "<input type='hidden' value='" . $territoire_id . "' name='territoire_id'>";

                    echo "<h1>";
                        echo "<span>" . TEXT_QD_PAGE_TITLE . "</span>";
                        // Save button — top right, triggers saveQualityData()
                        echo "<input type='submit' class='button' name='save_dataQuality' id='save_dataQuality'"
                           . " style='float:right;'"
                           . " value='" . TEXT_QD_BTN_SAVE . "'"
                           . " onclick='saveQualityData(event);' />";
                    echo "</h1>";

                    echo "<div id='onglet'>";
                        echo "<ul id='menu_onglet'>";
                            echo "<li id='onglet-0' class='actif'>" . TEXT_QD_TAB_LABEL . "</li>\n";
                        echo "</ul>";

                        echo "<div id='contenu-0' class='contenu'>";
                            require(DIR_WS_QUALITYDATA . 'form_qualitydata.php');
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
    // On page load: check for the ?save=true URL parameter.
    // When a save succeeds, the server redirects here with this flag so the
    // confirmation message survives the page reload.

    const urlParams = new URLSearchParams(window.location.search);

    if (urlParams.get('save') === 'true')
    {
        contenuInfo.innerHTML     = "<?php echo TEXT_QD_SAVE_URL_OK; ?>";
        contenuInfo.style.border  = '2px solid #09886d';
        contenuInfo.style.display = 'block';
    }


    // -----------------------------------------------
    // saveQualityData(event)
    // Serialises formQualityData and posts it to process_qualitydata_save.php.
    // On success, reloads the page with ?save=true so the confirmation is shown.
    // On error, displays the error message inline without reloading.

    function saveQualityData(event)
    {
        boxWait.style.display = 'block';
        event.preventDefault();

        var formData = new FormData(document.getElementById('formQualityData'));

        var xhr = new XMLHttpRequest();
        xhr.open("POST", "include/structure/qualitydata/process_qualitydata_save.php", true);

        xhr.onreadystatechange = function()
        {
            if (xhr.readyState === 4 && xhr.status === 200)
            {
                var r = JSON.parse(xhr.responseText);

                if (!r['erreur'])
                {
                    // Reload with the save flag so the success message survives
                    var cleanUrl = window.location.origin + window.location.pathname + '?save=true';
                    window.location.href = cleanUrl;
                }
                else
                {
                    contenuInfo.innerHTML     = r['msg_info'];
                    contenuInfo.style.border  = '2px solid #930000';
                    contenuInfo.style.display = 'block';
                }

                boxWait.style.display = 'none';
            }
        };

        xhr.send(formData);
    }

</script>
