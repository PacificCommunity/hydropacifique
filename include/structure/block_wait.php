<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Block d'attente
Simplement pour lancer une boucle d'attente
----------------------------------------
*/


echo "<div id='box_wait' class='block_view' style='height:100%;z-index: 2200;background-image:none;'>\n";

        echo "<div style='width:300px;margin: 0 auto;margin-top:12%;text-align:center;' \">";
        
           echo "<div class='hp-loader' >";
                echo "<div class='hp-ring'></div>";
                echo "<div class='hp-mark'><span class='h'>H</span><span class='p'>P</span></div>";
            echo "</div>";
            echo "<p style='text-align:center;color:#000;'>".TEXT_LOADING."</p>";
            echo "<p style='text-align:center;'>".TEXT_PLEASE_WAIT."</p>";

        echo "</div>\n";    

echo "</div>";

?>
