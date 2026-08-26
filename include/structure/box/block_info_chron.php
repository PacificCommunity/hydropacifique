<?php
/*
----------------------------------------
Copyright (c) 2025 - Vai-Natura
----------------------------------------
Popup — Data series information display
----------------------------------------
*/

// Query measurement types (hydrometry, pluviometry, piezometry, ...)
$sql_eq_type_boxinfo = "SELECT DISTINCT id_eq_type, nom_eq_type FROM " . TABLE_EQ_TYPE . " WHERE active_eq_type=1 ORDER BY order_eq_type ASC";
$eq_type_boxinfo_query = tep_db_query($sql_link, $sql_eq_type_boxinfo);
while ($eq_type_boxinfo = tep_db_fetch_array($eq_type_boxinfo_query))
{
    $eq_type_boxinfo_array[$eq_type_boxinfo['id_eq_type']] = $eq_type_boxinfo['nom_eq_type'];
}


echo "<div id='box_info_chron' class='block_view'
            style='position:absolute;width:750px;top:20px;left:20%;background:none;
                    display:none'>\n";

    echo "<div id='cadre_view_2' style='float:left;margin-top:20px;padding:0px;'>\n";

        echo "<p id='title_info_chron_details'
                        style='float:left;padding:15px 0;
                            font-size:16px;font-weight:bold;
                            color:#000;background-color:#f5f5f5;'>";

            echo "<span style='margin-left:15px;'>" . TEXT_BLOCK_INFO_CHRON_TITLE . "</span>";

            echo "<span id='button_close_info_chron' style='float:right;margin-right:15px;cursor:pointer;' title='" . TEXT_POPUP_CLOSE . "'>X</span>";

        echo "</p>\n";

        // Display container
        echo "<div id='cadre_info_chron_cell' style='width:97%;margin:15px 10px;'>";

            echo "<div id='cadre_wait_info_chron' style='width:100%;height:80px;margin-top:20px;text-align:center;'>";
                echo "<img src='" . DIR_WS_IMG . "wait.gif' style='width:50px;'>";
            echo "</div>";

        echo "</div>";

    echo "</div>\n";

echo "</div>\n";

?>


<script>

    var box_infoChron      = document.getElementById('box_info_chron');
    var titleBox_infoChron = document.getElementById('title_info_chron_details');
    var contenuBox_infoChron = document.getElementById('cadre_info_chron_cell');
    var waitBox_infoChron  = document.getElementById('cadre_wait_info_chron');

    // Close the popup when the close button is clicked or when clicking outside
    document.addEventListener("click", function(event)
    {
        if (event.target.id === 'button_close_info_chron')
        {
            box_infoChron.style.display = "none";
        }

        if (event.target === box_infoChron)
        {
            box_infoChron.style.display = "none";
        }
    });

    // Close the popup on Escape key
    document.addEventListener("keydown", function(event)
    {
        if (event.key === "Escape")
        {
            box_infoChron.style.display = "none";
        }
    });

    function affiche_info_chron()
    {
        waitBox_infoChron.style.display = 'block';

        var dataToSend = {
            idTypeData: '<?php echo $id_eq_type; ?>'
        };

        var xhr = new XMLHttpRequest();
        xhr.open("POST", "include/structure/box/process_info_chron.php", true);
        xhr.setRequestHeader("Content-Type", "application/json");

        xhr.onreadystatechange = function()
        {
            if (xhr.readyState === 4 && xhr.status === 200)
            {
                var jsonResponse = JSON.parse(xhr.responseText);
                contenuBox_infoChron.innerHTML = jsonResponse['js_html'];
                waitBox_infoChron.style.display = 'none';
            }
        };

        xhr.send(JSON.stringify(dataToSend));
    };

    function afficheBlockInfoChron()
    {
        box_infoChron.style.display = 'block';
        affiche_info_chron();
        initDraggable('title_info_chron_details', 'box_info_chron');
    }

</script>
