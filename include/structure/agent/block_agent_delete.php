<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Agent deletion confirmation popup (content injected via AJAX)
----------------------------------------
*/

$day   = date('d');
$month = date('m');
$year  = date('Y');

echo "<div id='box_del_agent' class='block_view'
            style='position:absolute;width:530px;top:15px;left:30%;background:none;
                    display:none;flex-direction:column;overflow:hidden;'>\n";

    //echo "<div id='cadre_view_del' style='width:600px;margin-top:100px;padding:0;background-color:#FBF9F1;'>";  
    echo "<div id='cadre_view_del' style='width:500px;height:100%;margin-top:100px;padding:0;'>";

        echo "<p id='title_box_jge'"
                . " style='flex-shrink:0;width:100%;padding:15px 0;text-align:center;"
                . "font-size:16px;font-weight:bold;color:#000;background-color:#f5f5f5;"
                . "box-sizing:border-box;'>";
            echo "<span style='font-size:16px;font-weight:bold;margin-left:5px;'>";
                echo TEXT_AGENT_DEL_CONFIRM_TITLE;
            echo "</span>";
        echo "</p>\n";

        echo "<div id='content_del_agent'></div>"; 

    echo "<hr>";
    echo "</div>";

echo "</div>";
?>

<script type="text/javascript">

    let boxDel   = document.getElementById('box_del_agent');
    let buttonCancel = document.getElementById('button_close');


    document.addEventListener("click", function(event)
    {
        if (event.target === buttonCancel)
        {
            boxDel.style.display = "none";
        }
    });

    document.addEventListener("keydown", function(event)
    {
        if (event.key === "Escape") {boxDel.style.display = "none"; }
    });

</script>
