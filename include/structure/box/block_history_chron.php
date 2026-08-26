<?php
/*
----------------------------------------
Copyright (c) 2025 - Vai-Natura
----------------------------------------
Popup — Data series modification history display
----------------------------------------
*/

// Query measurement types (hydrometry, pluviometry, piezometry, ...)
$sql_eq_type_boxinfo = "SELECT DISTINCT id_eq_type, nom_eq_type FROM " . TABLE_EQ_TYPE . " WHERE active_eq_type=1 ORDER BY order_eq_type ASC";
$eq_type_boxinfo_query = tep_db_query($sql_link, $sql_eq_type_boxinfo);
while ($eq_type_boxinfo = tep_db_fetch_array($eq_type_boxinfo_query))
{
    $eq_type_boxinfo_array[$eq_type_boxinfo['id_eq_type']] = $eq_type_boxinfo['nom_eq_type'];
}


echo "<div id='box_history_chron' class='block_view'
            style='position:absolute;background:none;width:1050px;left:20%;
                    display:none'>\n";

    echo "<div id='cadre_view_2' style='float:left;margin-top:20px;padding:0px;'>\n";

        echo "<p id='title_history_chron'
                        style='float:left;width:100%;padding:15px 0;
                            font-size:16px;font-weight:bold;
                            color:#000;background-color:#f5f5f5;'>";

            echo "<span id='title_history' style='margin-left:15px;'></span>";

            echo "<span id='button_close_history' style='float:right;margin-right:15px;cursor:pointer;' title='" . TEXT_POPUP_CLOSE . "'>X</span>";

        echo "</p>\n";

        // Display container
        echo "<div id='cadre_history_chron_cell' style='width:95%;margin:15px 10px;'>";

            echo "<div id='cadre_wait_history_chron' style='width:100%;height:80px;margin-top:20px;text-align:center;'>";
                echo "<img src='" . DIR_WS_IMG . "wait.gif' style='width:50px;'>";
            echo "</div>";

        echo "</div>";

    echo "</div>\n";

echo "</div>\n";

?>


<script>

    var box_historyChron      = document.getElementById('box_history_chron');
    var titleBox_historyChron = document.getElementById('title_history');
    var contenuBox_historyChron = document.getElementById('cadre_history_chron_cell');
    var waitBox_historyChron  = document.getElementById('cadre_wait_history_chron');

    // Close the popup when the close button is clicked or when clicking outside
    document.addEventListener("click", function(event)
    {
        if (event.target.id === 'button_close_history')
        {
            box_historyChron.style.display = "none";
        }

        if (event.target === box_historyChron)
        {
            box_historyChron.style.display = "none";
        }
    });

    // Close the popup on Escape key
    document.addEventListener("keydown", function(event)
    {
        if (event.key === "Escape")
        {
            box_historyChron.style.display = "none";
        }
    });

    function affiche_history_chron()
    {
        titleBox_historyChron.textContent = '<?php echo TEXT_BLOCK_HISTORY_CHRON_TITLE; ?>';
        waitBox_historyChron.style.display = 'block';

        var dataToSend = {
            idStation: '<?php echo $id_station; ?>'
        };

        var xhr = new XMLHttpRequest();
        xhr.open("POST", "include/structure/box/process_history_chron.php", true);
        xhr.setRequestHeader("Content-Type", "application/json");

        xhr.onreadystatechange = function()
        {
            if (xhr.readyState === 4 && xhr.status === 200)
            {
                var jsonResponse = JSON.parse(xhr.responseText);
                contenuBox_historyChron.innerHTML = jsonResponse['js_html'];
                waitBox_historyChron.style.display = 'none';
            }
        };

        xhr.send(JSON.stringify(dataToSend));
    };

    function afficheBlockHistoryChron()
    {
        box_historyChron.style.display = 'block';
        affiche_history_chron();
        initDraggable("title_history_chron", "box_history_chron");
    }

</script>
