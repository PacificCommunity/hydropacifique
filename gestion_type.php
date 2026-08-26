<?php
/*
----------------------------------------
Copyright (c) 2025 - Vai-Natura
----------------------------------------
Measurement type configuration page — lists and edits eq_type records
(Rain, Flow, etc.). Save and delete are handled via AJAX endpoints:
  - process_savetype.php : bulk save (triggered by the Save button)
  - process_deltype.php  : single-row delete (triggered by confirm_suppr)
  - process_tab_type.php : re-renders the table (called on load and after
                           every successful save/delete)
----------------------------------------
*/

require('include/application_top.php');

$message_info = '';
$msg_del_type = '';

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

                echo "<form name='formType' id='formType'>";

                    echo "<input type='hidden' value='" . $id_user       . "' name='id_user_agent'>";
                    echo "<input type='hidden' value='" . $territoire_id . "' name='territoire_id'>";

                    echo "<h1>";

                        echo "<p style='float:left;margin-right:25px;'>";
                            echo "<span>" . TEXT_US_TYPE_PAGE_TITLE . "</span>";
                        echo "</p>";

                        echo button_return('gestion.php');

                        echo "<input class='button' name='button_save' id='button_save'"
                                . " style='float:right;margin-right:20px;'"
                                . " value='" . TEXT_US_TYPE_SAVE . "'"
                                . " onclick='saveType(event);' />";

                    echo "</h1>";


                    echo "<div id='onglet'>";
                        echo "<ul id='menu_onglet'>";
                            echo "<li id='onglet-0' class='actif'>" . TEXT_US_TYPE_TAB_LABEL . "</li>\n";
                        echo "</ul>";

                        echo "<div id='contenu-0' class='contenu'>";
                            require(DIR_WS_ADMIN . 'form_type_1.php');
                        echo "</div>";
                    echo "</div>";

                echo "</form>\n";

            echo "<hr>";
            echo "</div>";
        echo "<hr>";
        echo "</div>";
    echo "<hr>";
    echo "</div>";

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
    // saveType(event)
    // Submits the full form to the bulk-save endpoint via AJAX.
    // Prevents the native submit (no page reload), shows the loading
    // overlay, then refreshes the table after the response arrives.
    function saveType(event)
    {
        event.preventDefault();
        boxWait.style.display = 'block';

        var formType = new FormData(document.getElementById('formType'));

        var xhr = new XMLHttpRequest();
        xhr.open("POST", "include/structure/admin/process_type_save.php", true);

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

                // Refresh the table to reflect the committed changes
                affiche_type();
            }
        };

        xhr.send(formType);
    }

    // Bind the submit handler — using addEventListener rather than an
    // inline onsubmit keeps the HTML clean and avoids double-binding.
    document.getElementById('formType').addEventListener('submit', saveType);

</script>