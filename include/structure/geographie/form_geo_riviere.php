<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Rivers tab (tab 4)
Included by gestion_geo.php.
The container is filled asynchronously by affiche_geo_riviere_data().
Defines:
  - affiche_geo_riviere_data() : fetches the river table from process_tab_riviere.php
  - delete_riviere(id)         : deletes a river and refreshes the table
----------------------------------------
*/

echo "<div id='onglet_contenu' style='height:75vh;'>\n";
    echo "<div id='boite1' class='first'>\n";
        echo "<div id='tab_data_georiviere' class='table-container' style='float:left;height:70vh;'>";
        echo "</div>\n";
    echo "<hr>\n";
    echo "</div>\n";
echo "<hr>\n";
echo "</div>\n";
?>

<script>

    var tabDataGeoRiviere = document.getElementById('tab_data_georiviere');


    function affiche_geo_riviere_data()
    {
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "include/structure/geographie/process_tab_riviere.php", true);
        xhr.setRequestHeader("Content-Type", "application/json");

        xhr.onreadystatechange = function()
        {
            if (xhr.readyState === 4 && xhr.status === 200)
            {
                var r = JSON.parse(xhr.responseText);

                if (r['tab_geo_riviere'])
                {
                    tabDataGeoRiviere.innerHTML = r['htmlcode'];
                }
                else
                {
                    contenuInfo.innerHTML     = r['message_info'];
                    contenuInfo.style.border  = '2px solid #930000';
                    contenuInfo.style.display = 'block';
                }
            }
        };

        xhr.send(JSON.stringify({ territoireId: <?php echo $territoire_id; ?> }));
    }

    affiche_geo_riviere_data();


    function delete_riviere(id_riviere)
    {
        boxWait.style.display = 'block';

        var xhr = new XMLHttpRequest();
        xhr.open("POST", "include/structure/geographie/process_delriviere.php", true);
        xhr.setRequestHeader("Content-Type", "application/json");

        xhr.onreadystatechange = function()
        {
            if (xhr.readyState === 4 && xhr.status === 200)
            {
                var r = JSON.parse(xhr.responseText);

                contenuInfo.innerHTML     = r['message_info'];
                contenuInfo.style.border  = r['del_riviere'] ? '2px solid #09886d' : '2px solid #930000';
                contenuInfo.style.display = 'block';
                boxWait.style.display     = 'none';

                if (r['del_riviere']) { affiche_geo_riviere_data(); }
            }
        };

        xhr.send(JSON.stringify({ id_riviere: id_riviere }));
    }

</script>
