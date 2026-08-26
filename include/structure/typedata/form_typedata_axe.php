<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Graph axis tab — included by gestion_type_data.php
Renders an empty container #tab_axe which is populated by affiche_axe()
via AJAX (process_tab_axe.php).
Defines:
  affiche_axe()   — loads the axis table into #tab_axe
  delete_axe(id)  — deletes one axis and refreshes both tabs
----------------------------------------
*/
?>

<div id='onglet_contenu' style='height:75vh;'>

    <div id='boite1' class='first'>

        <div id='tab_axe' class='table-container' style='float:left;height:70vh;'>
        </div>

    <hr>
    </div>

<hr>
</div>

<script>

    var tabAxe = document.getElementById('tab_axe'); // Container for AJAX-loaded axis table


    // -----------------------------------------------
    // affiche_axe()
    // Fetches the full axis table HTML from process_tab_axe.php and
    // injects it into #tab_axe. No filter payload needed — all axes are shown.

    function affiche_axe()
    {
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "include/structure/typedata/process_tab_axe.php", true);
        xhr.setRequestHeader("Content-Type", "application/json");

        xhr.onreadystatechange = function()
        {
            if (xhr.readyState === 4 && xhr.status === 200)
            {
                var r = JSON.parse(xhr.responseText);

                if (r['tab_axedata'])
                {
                    tabAxe.innerHTML = r['htmlcode'];
                }
                else
                {
                    contenuInfo.innerHTML     = r['message_info'];
                    contenuInfo.style.border  = '2px solid #930000';
                    contenuInfo.style.display = 'block';
                }
            }
        };

        // No payload required — send an empty JSON object
        xhr.send(JSON.stringify({}));
    }

    affiche_axe(); // Load axis table on tab display


    // -----------------------------------------------
    // delete_axe(id_axe)
    // Sends an AJAX delete request for the given axis.
    // On success, refreshes both the time-series tab (for its axis dropdowns)
    // and the axis tab.

    function delete_axe(id_axe)
    {
        // Keep track of the currently selected data type for the time-series refresh
        var idTypeDataSelect = document.getElementById('chron_filter').value;

        var xhr = new XMLHttpRequest();
        xhr.open("POST", "include/structure/typedata/process_delaxe.php", true);
        xhr.setRequestHeader("Content-Type", "application/json");

        xhr.onreadystatechange = function()
        {
            if (xhr.readyState === 4 && xhr.status === 200)
            {
                var r = JSON.parse(xhr.responseText);

                contenuInfo.innerHTML     = r['message_info'];
                contenuInfo.style.display = 'block';

                if (r['del_axe'])
                {
                    contenuInfo.style.border = '2px solid #09886d';
                    // Refresh both tabs — axis dropdown in the time-series tab must update
                    affiche_typedata(idTypeDataSelect);
                    affiche_axe();
                }
                else
                {
                    contenuInfo.style.border = '2px solid #930000';
                }
            }
        };

        xhr.send(JSON.stringify({ id_axe: id_axe }));
    }

</script>
