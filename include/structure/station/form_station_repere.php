<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Station form - piezo benchmark tab (borehole reference points)
- Editable table of benchmark records ordered by validity start date
- Inline add (row 0) and delete per row
- Table HTML is produced by render_repere_table() (shared with
  process_loadrepere.php so the table can be reloaded via AJAX after save)
----------------------------------------
*/

// Shared table renderer (single source of truth)
require_once(DIR_WS_STATION . 'repere_table.php');


// -----------------------------------------------
// HTML output

echo "<div id='onglet_contenu' style='overflow-y:auto;height:calc(100vh - 200px);padding:0 20px;'>\n";

    echo "<div id='boite1' class='first repere-table-wrap' style='margin-top:15px;'>\n";

        // Table container: populated on page load, reloaded via AJAX after save
        echo "<div id='tab_repere'>\n";
            echo render_repere_table($sql_link, $id_station);
        echo "</div>\n";

    echo "<hr>\n";
    echo "</div>\n";

echo "<hr>\n";
echo "</div>\n";
?>

<style>
    /* More vertical breathing room between benchmark rows.
       Selector is made specific (table id + row classes) so it wins over
       the existing #table_tri / .row1 / .row2 cell rules. */
    #table_tri.table-repere td,
    #table_tri.table-repere tr.row1 td,
    #table_tri.table-repere tr.row2 td { padding-top:11px; padding-bottom:11px; }

    /* Extra space below the table (under the "Add a reference" row) */
    .table-repere { margin-bottom:30px; }
</style>

<script>

    var contenuInfo = document.getElementById('contenu_info');
    var id_station  = <?php echo (int) $id_station; ?>;

    // JS strings injected from PHP translation constants
    var LANG_REPERE = {
        confirmTitle : '<?= TEXT_REPERE_DEL_CONFIRM_TITLE ?>',
        confirmMsg   : '<?= TEXT_REPERE_DEL_CONFIRM_MSG ?>',
        btnCancel    : '<?= TEXT_REPERE_DEL_BTN_CANCEL ?>',
        btnConfirm   : '<?= TEXT_REPERE_DEL_BTN_CONFIRM ?>'
    };

    // Holds the benchmark id awaiting deletion confirmation
    var pendingDeleteRepere = null;


    // -----------------------------------------------
    // Reload the benchmark table without a page refresh
    // Exposed on window so saveStation() (modif_station.php) can call it.

    function load_repere()
    {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'include/structure/station/process_loadrepere.php', true);
        xhr.setRequestHeader('Content-Type', 'application/json');

        xhr.onreadystatechange = function()
        {
            if (xhr.readyState === 4 && xhr.status === 200)
            {
                document.getElementById('tab_repere').innerHTML =
                    JSON.parse(xhr.responseText)['tab_html'];
            }
        };

        xhr.send(JSON.stringify({ id_station: id_station }));
    }
    window.load_repere = load_repere;


    // -----------------------------------------------
    // Delete confirmation popup (Yes/No)
    // Built once and reused; styled to match list_stations.php

    var boxDelRepere = document.getElementById('box_del_repere');

    if (!boxDelRepere)
    {
        boxDelRepere = document.createElement('div');
        boxDelRepere.id = 'box_del_repere';
        boxDelRepere.style.cssText =
            'position:fixed;top:0;left:0;width:100%;height:100%;' +
            'background:rgba(0,0,0,0.45);z-index:9999;display:none;';

        boxDelRepere.innerHTML =
            "<div style='position:relative;width:460px;margin:8% auto 0 auto;" +
                "background-color:#FBF9F1;border-radius:6px;overflow:hidden;" +
                "box-shadow:0 8px 30px rgba(0,0,0,0.35);'>" +

                // Red header
                "<p style='margin:0;padding:14px 20px;font-size:17px;font-weight:bold;" +
                    "color:#fff;background-color:#a52834;'>" + LANG_REPERE.confirmTitle + "</p>" +

                "<div style='padding:18px 22px;'>" +
                    "<p style='margin:0 0 18px 0;font-size:14px;color:#333;'>" +
                        LANG_REPERE.confirmMsg + "</p>" +

                    "<div style='display:flex;justify-content:flex-end;gap:12px;'>" +
                        "<input type='button' id='cancel_del_repere' class='button_close'" +
                            " value='" + LANG_REPERE.btnCancel + "' style='width:120px;'>" +
                        "<input type='button' id='ok_del_repere' class='button'" +
                            " value='" + LANG_REPERE.btnConfirm + "' style='width:120px;'>" +
                    "</div>" +
                "</div>" +
            "</div>";

        document.body.appendChild(boxDelRepere);
    }

    var okDelRepere     = document.getElementById('ok_del_repere');
    var cancelDelRepere = document.getElementById('cancel_del_repere');


    // Open the popup for a given benchmark id
    function delete_repere(id_repere)
    {
        pendingDeleteRepere = id_repere;
        boxDelRepere.style.display = 'block';
    }

    // Close / reset the popup
    function closeDelRepere()
    {
        boxDelRepere.style.display = 'none';
        pendingDeleteRepere = null;
    }

    // Confirm: send the AJAX delete request
    function confirmDelRepere()
    {
        if (pendingDeleteRepere === null) { return; }

        var id_repere = pendingDeleteRepere;

        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'include/structure/station/process_delrepere.php', true);
        xhr.setRequestHeader('Content-Type', 'application/json');

        xhr.onreadystatechange = function()
        {
            if (xhr.readyState === 4 && xhr.status === 200)
            {
                var jsonResponse = JSON.parse(xhr.responseText);
                var del_repere   = jsonResponse['del_repere'];
                var message_info = jsonResponse['message_info'];

                contenuInfo.innerHTML     = message_info;
                contenuInfo.style.display = 'block';

                if (del_repere)
                {
                    contenuInfo.style.border = '2px solid #09886d'; // green: success
                    var rowEl = document.getElementById('row_' + id_repere);
                    if (rowEl) { rowEl.style.display = 'none'; }
                }
                else
                {
                    contenuInfo.style.border = '2px solid #930000'; // red: error
                }

                closeDelRepere();
            }
        };

        xhr.send(JSON.stringify({ id_repere: id_repere }));
    }

    okDelRepere.addEventListener('click', confirmDelRepere);
    cancelDelRepere.addEventListener('click', closeDelRepere);

    // Close on click outside the popup card / Escape key
    boxDelRepere.addEventListener('click', function(event)
    {
        if (event.target === boxDelRepere) { closeDelRepere(); }
    });
    document.addEventListener('keydown', function(event)
    {
        if (event.key === 'Escape' && boxDelRepere.style.display === 'block') { closeDelRepere(); }
    });

</script>