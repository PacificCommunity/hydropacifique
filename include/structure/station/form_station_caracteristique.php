<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Station form - piezo characteristics tab (borehole well)
- One observation block per record, ordered by date descending
- New observation block hidden by default, revealed by checkbox
- Blocks HTML is produced by render_caracteristique_blocs() (shared with
  process_loadcaracteristique.php so blocks can be reloaded via AJAX after save)
----------------------------------------
*/

// Shared blocks renderer (single source of truth)
require_once(DIR_WS_STATION . 'caracteristique_blocs.php');


// -----------------------------------------------
// HTML output

echo "<div id='onglet_contenu' style='overflow-y:auto;height:calc(100vh - 200px);padding:0 20px;'>";

    echo "<p class='titre_box' style='margin:10px 20px;'>";
        echo "<input type='checkbox' name='new_caract' id='new_caract'"
           . " style='float:left;width:20px;height:20px;margin-right:10px;'>";
        echo "<span style='float:left;margin-top:5px;'>" . TEXT_CARACT_NEW_OBS . "</span>";
    echo "</p>";

    echo "<hr>";

    // Blocks container: populated on page load, reloaded via AJAX after save
    echo "<div id='tab_caracteristique'>\n";
        echo render_caracteristique_blocs($sql_link, $id_station, $today_fr);
    echo "</div>\n";

echo "<hr>\n";
echo "</div>\n";
?>

<script>

    var contenuInfo = document.getElementById('contenu_info');
    var id_station  = <?php echo (int) $id_station; ?>;

    // JS strings injected from PHP translation constants
    var LANG_CARACT = {
        confirmTitle : '<?= TEXT_CARACT_DEL_CONFIRM_TITLE ?>',
        confirmMsg   : '<?= TEXT_CARACT_DEL_CONFIRM_MSG ?>',
        btnCancel    : '<?= TEXT_CARACT_DEL_BTN_CANCEL ?>',
        btnConfirm   : '<?= TEXT_CARACT_DEL_BTN_CONFIRM ?>'
    };

    // Holds the characteristics id awaiting deletion confirmation
    var pendingDeleteCaract = null;


    // -----------------------------------------------
    // New observation toggle
    // Re-bound after each table reload (the checkbox + blocks are regenerated)

    function bindNewCaractToggle()
    {
        var newCaract = document.getElementById('new_caract');
        var newBox    = document.getElementById('bloc_caract_0');
        if (!newCaract || !newBox) { return; }

        // Reset to hidden state on (re)bind
        newCaract.checked    = false;
        newBox.style.display = 'none';

        newCaract.onchange = function()
        {
            newBox.style.display = this.checked ? 'block' : 'none';
        };
    }

    bindNewCaractToggle();


    // -----------------------------------------------
    // Reload the characteristics blocks without a page refresh
    // Exposed on window so saveStation() (modif_station.php) can call it.

    function load_caracteristique()
    {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'include/structure/station/process_loadcaracteristique.php', true);
        xhr.setRequestHeader('Content-Type', 'application/json');

        xhr.onreadystatechange = function()
        {
            if (xhr.readyState === 4 && xhr.status === 200)
            {
                document.getElementById('tab_caracteristique').innerHTML =
                    JSON.parse(xhr.responseText)['tab_html'];

                // Re-bind the new-observation toggle on the regenerated blocks
                bindNewCaractToggle();
            }
        };

        xhr.send(JSON.stringify({ id_station: id_station }));
    }
    window.load_caracteristique = load_caracteristique;


    // -----------------------------------------------
    // Delete confirmation popup (Yes/No)
    // Built once and reused; styled to match list_stations.php

    var boxDelCaract = document.getElementById('box_del_caract');

    if (!boxDelCaract)
    {
        boxDelCaract = document.createElement('div');
        boxDelCaract.id = 'box_del_caract';
        boxDelCaract.style.cssText =
            'position:fixed;top:0;left:0;width:100%;height:100%;' +
            'background:rgba(0,0,0,0.45);z-index:9999;display:none;';

        boxDelCaract.innerHTML =
            "<div style='position:relative;width:460px;margin:8% auto 0 auto;" +
                "background-color:#FBF9F1;border-radius:6px;overflow:hidden;" +
                "box-shadow:0 8px 30px rgba(0,0,0,0.35);'>" +

                // Red header
                "<p style='margin:0;padding:14px 20px;font-size:17px;font-weight:bold;" +
                    "color:#fff;background-color:#a52834;'>" + LANG_CARACT.confirmTitle + "</p>" +

                "<div style='padding:18px 22px;'>" +
                    "<p style='margin:0 0 18px 0;font-size:14px;color:#333;'>" +
                        LANG_CARACT.confirmMsg + "</p>" +

                    "<div style='display:flex;justify-content:flex-end;gap:12px;'>" +
                        "<input type='button' id='cancel_del_caract' class='button_close'" +
                            " value='" + LANG_CARACT.btnCancel + "' style='width:120px;'>" +
                        "<input type='button' id='ok_del_caract' class='button'" +
                            " value='" + LANG_CARACT.btnConfirm + "' style='width:120px;'>" +
                    "</div>" +
                "</div>" +
            "</div>";

        document.body.appendChild(boxDelCaract);
    }

    var okDelCaract     = document.getElementById('ok_del_caract');
    var cancelDelCaract = document.getElementById('cancel_del_caract');


    // Open the popup for a given characteristics id
    function del_caracteristique(id_caract)
    {
        pendingDeleteCaract = id_caract;
        boxDelCaract.style.display = 'block';
    }

    // Close / reset the popup
    function closeDelCaract()
    {
        boxDelCaract.style.display = 'none';
        pendingDeleteCaract = null;
    }

    // Confirm: send the AJAX delete request
    function confirmDelCaract()
    {
        if (pendingDeleteCaract === null) { return; }

        var id_caract = pendingDeleteCaract;

        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'include/structure/station/process_delcaracteristique.php', true);
        xhr.setRequestHeader('Content-Type', 'application/json');

        xhr.onreadystatechange = function()
        {
            if (xhr.readyState === 4 && xhr.status === 200)
            {
                var jsonResponse = JSON.parse(xhr.responseText);
                var del_caract   = jsonResponse['del_caract'];
                var message_info = jsonResponse['message_info'];

                contenuInfo.innerHTML     = message_info;
                contenuInfo.style.display = 'block';

                if (del_caract)
                {
                    contenuInfo.style.border = '2px solid #09886d'; // green: success
                    var blocEl = document.getElementById('bloc_caract_' + id_caract);
                    if (blocEl) { blocEl.style.display = 'none'; }
                }
                else
                {
                    contenuInfo.style.border = '2px solid #930000'; // red: error
                }

                closeDelCaract();
            }
        };

        xhr.send(JSON.stringify({ id_caract: id_caract }));
    }

    okDelCaract.addEventListener('click', confirmDelCaract);
    cancelDelCaract.addEventListener('click', closeDelCaract);

    // Close on click outside the popup card / Escape key
    boxDelCaract.addEventListener('click', function(event)
    {
        if (event.target === boxDelCaract) { closeDelCaract(); }
    });
    document.addEventListener('keydown', function(event)
    {
        if (event.key === 'Escape' && boxDelCaract.style.display === 'block') { closeDelCaract(); }
    });

</script>