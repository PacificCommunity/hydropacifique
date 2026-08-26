<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Hydrological regions tab (tab 3)
Included by gestion_geo.php.
The container is filled asynchronously by affiche_geo_regionhydro_data().
Defines:
  - affiche_geo_regionhydro_data() : fetches the table from process_tab_regionhydro.php
  - delete_regionhydro(id)         : deletes a hydrological region and refreshes
                                     both the hydrological region and river tables
                                     (rivers hold a FK to hydrological regions)
----------------------------------------
*/

echo "<div id='onglet_contenu' style='height:75vh;'>\n";
    echo "<div id='boite1' class='first'>\n";
        echo "<div id='tab_data_georegionhydro' class='table-container' style='float:left;height:70vh;'>";
        echo "</div>\n";
    echo "<hr>\n";
    echo "</div>\n";
echo "<hr>\n";
echo "</div>\n";
?>

<script>

    var tabDataGeoRegionhydro = document.getElementById('tab_data_georegionhydro');


    function affiche_geo_regionhydro_data()
    {
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "include/structure/geographie/process_tab_regionhydro.php", true);
        xhr.setRequestHeader("Content-Type", "application/json");

        xhr.onreadystatechange = function()
        {
            if (xhr.readyState === 4 && xhr.status === 200)
            {
                var r = JSON.parse(xhr.responseText);

                if (r['tab_geo_regionhydro'])
                {
                    tabDataGeoRegionhydro.innerHTML = r['htmlcode'];
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

    affiche_geo_regionhydro_data();


    function delete_regionhydro(id_regionhydro)
    {
        boxWait.style.display = 'block';

        var xhr = new XMLHttpRequest();
        xhr.open("POST", "include/structure/geographie/process_delregionhydro.php", true);
        xhr.setRequestHeader("Content-Type", "application/json");

        xhr.onreadystatechange = function()
        {
            if (xhr.readyState === 4 && xhr.status === 200)
            {
                var r = JSON.parse(xhr.responseText);

                contenuInfo.innerHTML     = r['message_info'];
                contenuInfo.style.border  = r['del_regionhydro'] ? '2px solid #09886d' : '2px solid #930000';
                contenuInfo.style.display = 'block';
                boxWait.style.display     = 'none';

                if (r['del_regionhydro'])
                {
                    affiche_geo_regionhydro_data();
                    affiche_geo_riviere_data(); // Rivers may reference the deleted hydrological region
                }
            }
        };

        xhr.send(JSON.stringify({ id_regionhydro: id_regionhydro }));
    }

</script>
