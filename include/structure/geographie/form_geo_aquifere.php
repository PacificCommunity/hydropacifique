<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Aquifers tab — legacy version (tab 5, no territoire filter)
Included by gestion_geo.php on older installations.
The container is filled asynchronously by affiche_geo_aquifere_data().
Defines:
  - affiche_geo_aquifere_data() : fetches the table from process_tab_aquifere.php
  - delete_aquifere(id)         : deletes an aquifer and refreshes the table
----------------------------------------
*/

echo "<div id='onglet_contenu' style='overflow-y:auto;height:75vh;'>\n";
    echo "<div id='boite1' class='first'>\n";
        echo "<div id='tab_data_geoaquifere' class='table-container' style='float:left;height:70vh;'>";
        echo "</div>\n";
    echo "<hr>\n";
    echo "</div>\n";
echo "<hr>\n";
echo "</div>\n";
?>

<script>

    var tabDataGeoAquifere = document.getElementById('tab_data_geoaquifere');


    function affiche_geo_aquifere_data()
    {
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "include/structure/geographie/process_tab_aquifere.php", true);
        xhr.setRequestHeader("Content-Type", "application/json");

        xhr.onreadystatechange = function()
        {
            if (xhr.readyState === 4 && xhr.status === 200)
            {
                var r = JSON.parse(xhr.responseText);

                if (r['tab_geo_aquifere'])
                {
                    tabDataGeoAquifere.innerHTML = r['htmlcode'];
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

    affiche_geo_aquifere_data();


    function delete_aquifere(id_aquifere)
    {
        boxWait.style.display = 'block';

        var xhr = new XMLHttpRequest();
        xhr.open("POST", "include/structure/geographie/process_delaquifere.php", true);
        xhr.setRequestHeader("Content-Type", "application/json");

        xhr.onreadystatechange = function()
        {
            if (xhr.readyState === 4 && xhr.status === 200)
            {
                var r = JSON.parse(xhr.responseText);

                contenuInfo.innerHTML     = r['message_info'];
                contenuInfo.style.border  = r['del_aquifere'] ? '2px solid #09886d' : '2px solid #930000';
                contenuInfo.style.display = 'block';
                boxWait.style.display     = 'none';

                if (r['del_aquifere']) { affiche_geo_aquifere_data(); }
            }
        };

        xhr.send(JSON.stringify({ id_aquifere: id_aquifere }));
    }

</script>
