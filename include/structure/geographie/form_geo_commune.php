<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Towns tab (tab 2)
Included by gestion_geo.php.
The container is filled asynchronously by affiche_geo_commune_data().
Defines:
  - affiche_geo_commune_data() : fetches the town table from process_tab_commune.php
  - delete_commune(id)         : deletes a town and refreshes the table
----------------------------------------
*/

echo "<div id='onglet_contenu' style='height:75vh;'>\n";
    echo "<div id='boite1' class='first'>\n";
        echo "<div id='tab_data_geocommune' class='table-container' style='float:left;height:70vh;'>";
        echo "</div>\n";
    echo "<hr>\n";
    echo "</div>\n";
echo "<hr>\n";
echo "</div>\n";
?>

<script>

    var tabDataGeoCommune = document.getElementById('tab_data_geocommune');


    function affiche_geo_commune_data()
    {
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "include/structure/geographie/process_tab_commune.php", true);
        xhr.setRequestHeader("Content-Type", "application/json");

        xhr.onreadystatechange = function()
        {
            if (xhr.readyState === 4 && xhr.status === 200)
            {
                var r = JSON.parse(xhr.responseText);

                if (r['tab_geo_commune'])
                {
                    tabDataGeoCommune.innerHTML = r['htmlcode'];
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

    affiche_geo_commune_data();


    function delete_commune(id_commune)
    {
        boxWait.style.display = 'block';

        var xhr = new XMLHttpRequest();
        xhr.open("POST", "include/structure/geographie/process_delcommune.php", true);
        xhr.setRequestHeader("Content-Type", "application/json");

        xhr.onreadystatechange = function()
        {
            if (xhr.readyState === 4 && xhr.status === 200)
            {
                var r = JSON.parse(xhr.responseText);

                contenuInfo.innerHTML     = r['message_info'];
                contenuInfo.style.border  = r['del_commune'] ? '2px solid #09886d' : '2px solid #930000';
                contenuInfo.style.display = 'block';
                boxWait.style.display     = 'none';

                if (r['del_commune']) { affiche_geo_commune_data(); }
            }
        };

        xhr.send(JSON.stringify({ id_commune: id_commune }));
    }

</script>
