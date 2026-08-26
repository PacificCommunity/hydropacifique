<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Rounds tab (tab 6)
Included by gestion_geo.php.
The container is filled asynchronously by affiche_geo_tournee_data().
Defines:
  - affiche_geo_tournee_data() : fetches the round table from process_tab_tournee.php
  - delete_tournee(id)         : deletes a round and refreshes the table
----------------------------------------
*/

echo "<div id='onglet_contenu' style='overflow-y:auto;height:75vh;'>\n";
    echo "<div id='boite1' class='first'>\n";
        echo "<div id='tab_data_geotournee' class='table-container' style='float:left;height:70vh;'>";
        echo "</div>\n";
    echo "<hr>\n";
    echo "</div>\n";
echo "<hr>\n";
echo "</div>\n";
?>

<script>

    var tabDataGeoTournee = document.getElementById('tab_data_geotournee');


    function affiche_geo_tournee_data()
    {
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "include/structure/geographie/process_tab_tournee.php", true);
        xhr.setRequestHeader("Content-Type", "application/json");

        xhr.onreadystatechange = function()
        {
            if (xhr.readyState === 4 && xhr.status === 200)
            {
                var r = JSON.parse(xhr.responseText);

                if (r['tab_geo_tournee'])
                {
                    tabDataGeoTournee.innerHTML = r['htmlcode'];
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

    affiche_geo_tournee_data();


    function delete_tournee(id_tournee)
    {
        boxWait.style.display = 'block';

        var xhr = new XMLHttpRequest();
        xhr.open("POST", "include/structure/geographie/process_deltournee.php", true);
        xhr.setRequestHeader("Content-Type", "application/json");

        xhr.onreadystatechange = function()
        {
            if (xhr.readyState === 4 && xhr.status === 200)
            {
                var r = JSON.parse(xhr.responseText);

                contenuInfo.innerHTML     = r['message_info'];
                contenuInfo.style.border  = r['del_tournee'] ? '2px solid #09886d' : '2px solid #930000';
                contenuInfo.style.display = 'block';
                boxWait.style.display     = 'none';

                if (r['del_tournee']) { affiche_geo_tournee_data(); }
            }
        };

        xhr.send(JSON.stringify({ id_tournee: id_tournee }));
    }

</script>
