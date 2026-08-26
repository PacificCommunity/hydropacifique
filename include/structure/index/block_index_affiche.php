<?php
/*
----------------------------------------
Copyright (c) 2025 - Vai-Natura
----------------------------------------
Homepage info popup — overlaid panel included by index.php.
Displays the last field reports or imports when the user clicks an
info link on the homepage map.
----------------------------------------
*/

echo "<div id='box_data' class='block_view'"
   . " style='position:absolute;background:none;width:950px;max-height:45vh;"
   . "left:20%;display:none'>\n";

    echo "<div id='cadre_view_2' style='float:left;margin-top:20px;padding:0;'>\n";

        echo "<p id='title_box_data'"
           . " style='float:left;width:100%;padding:15px 0;"
           . "font-size:16px;font-weight:bold;color:#000;background-color:#f5f5f5;'>";
            echo "<span id='title_box_data_info' style='margin-left:15px;'></span>";
            echo "<span id='button_close' style='float:right;margin-right:15px;cursor:pointer;'"
               . " title='" . TEXT_IX_POPUP_CLOSE . "'>X</span>";
        echo "</p>\n";

        echo "<div id='cadre_index_cell' style='width:95%;margin:15px 10px;'>";
            echo "<div id='cadre_wait' style='width:100%;height:50px;margin-top:10px;text-align:center;'>";
                echo "<img src='" . DIR_WS_IMG . "wait.gif' style='width:50px;'>";
            echo "</div>";
        echo "</div>";

    echo "</div>\n";

echo "</div>\n";
?>

<script>
    document.addEventListener("click", function(event)
    {
        if (event.target.id === 'button_close') { boxData.style.display = "none"; }
        if (event.target === boxData)           { boxData.style.display = "none"; }
    });

    document.addEventListener("keydown", function(event) {
        if (event.key === "Escape") { boxData.style.display = "none"; }
    });
</script>
