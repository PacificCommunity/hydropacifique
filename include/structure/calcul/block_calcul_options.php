<?php
/*
----------------------------------------
Copyright (c) 2025 - Vai-Natura
----------------------------------------
Calculation options popup
- Draggable overlay panel with 4 correction tools:
  linear function, time offset, gap insertion, smoothing
----------------------------------------
*/

echo "<div id='box_calcul_options' class='block_view'"
   . " style='position:absolute;width:450px;height:480px;top:20px;left:10%;background:none;'>\n";

    echo "<div id='cadre_view_2' style='padding:0;margin:0;border-radius: 2px;'>\n";

        echo "<p id='title_box_options'"
           . " style='float:left;width:100%;padding:15px 0;font-size:16px;font-weight:bold;"
           . "color:#000;background-color:#f5f5f5;'>";
            echo "<span style='margin-left:15px;'>" . TEXT_CALCUL_OPT_TITLE . "</span>";
            echo "<span id='button_close_options' style='float:right;margin-right:15px;cursor:pointer;'"
               . " title='" . TEXT_CALCUL_OPT_CLOSE . "'>X</span>";
        echo "</p>\n";

        echo "<div id='cadre_option' style='width:98%;padding:10px 0;padding-left:10px;max-height:70vh;overflow-y:auto;'>";

            // ---- Linear function correction ----
            echo "<div style='float:left;padding:10px 3%;border:1.5px solid #000;width:88%;'>\n";

                echo "<div style='float:left;width:80%;'>\n";
                    echo "<p style='float:left;width:100%;margin-bottom:10px;font-size:14px;font-weight:bold;'>";
                        echo TEXT_CALCUL_OPT_LINEAR_TITLE;
                        echo "<br><span style='font-size:15px;'>" . TEXT_CALCUL_OPT_LINEAR_FN . "</span>";
                    echo "</p>\n";
                    echo "<p style='float:left;font-size:14px;color:#428bca;padding-top:5px;'>a = </p>\n";
                    echo "<input type='text' class='input_texte_xsmall' id='valeur_a'"
                       . " style='float:left;margin-left:5px;margin-right:20px;' value='1'>\n";
                    echo "<p style='float:left;font-size:14px;color:#428bca;padding-top:5px;'>b = </p>\n";
                    echo "<input type='text' class='input_texte_xsmall' id='valeur_b'"
                       . " style='float:left;margin-left:5px;' value='0'>\n";
                echo "</div>\n";

                echo "<div style='float:right;margin-top:25px;'>\n";
                    echo "<button id='calcul_valeur' class='inverse_axe'"
                       . " style='float:right;width:50px;height:35px;padding:0;color:" . $colorMapping['calcul'] . ";'"
                       . " title='" . TEXT_CALCUL_OPT_LINEAR_BTN . "'>";
                        echo "<span style='font-size:22px;margin:0;'>&#x25CF></span>";
                    echo "</button>\n";
                echo "</div>\n";

            echo "</div>\n";


            // ---- Time offset ----
            echo "<div style='float:left;margin-top:10px;padding:10px 3%;border:1.5px solid #000;width:88%;'>\n";

                echo "<div style='float:left;width:80%;'>\n";
                    echo "<div id='boite_small' style='margin:0;'>\n";
                        echo "<p style='float:left;width:100%;margin-bottom:10px;font-size:14px;font-weight:bold;'>"
                           . TEXT_CALCUL_OPT_OFFSET_TITLE . "</p>\n";
                        echo "<select name='operateur_x' id='operateur_x'"
                           . " style='float:left;width:45px;font-weight:bold;font-size:16px;'>\n";
                            echo "<option value='+'>+</option>\n";
                            echo "<option value='-'>-</option>\n";
                        echo "</select>\n";
                        echo "<input type='text' class='input_texte_xsmall' id='valeur_operation_x'"
                           . " style='float:left;' value='0'>\n";
                        echo "<p style='float:left;width:100px;font-size:14px;color:#428bca;margin-left:5px;padding-top:7px;'>"
                           . TEXT_CALCUL_OPT_SECONDS . "</p>\n";
                    echo "</div>\n";
                echo "</div>\n";

                echo "<div style='float:right;margin-top:15px;'>\n";
                    echo "<button id='calcul_date' class='inverse_axe'"
                       . " style='float:right;width:50px;height:35px;padding:0;color:" . $colorMapping['decalage_date'] . ";'"
                       . " title='" . TEXT_CALCUL_OPT_OFFSET_BTN . "'>";
                        echo "<span style='font-size:22px;margin:0;'>&#x25CF></span>";
                    echo "</button>\n";
                echo "</div>\n";

            echo "</div>\n";


            // ---- Gap insertion ----
            echo "<div style='float:left;margin-top:10px;padding:10px 3%;border:1.5px solid #000;width:88%;'>\n";

                echo "<div style='float:left;width:80%;'>\n";
                    echo "<div id='boite_small' style='width:90%;margin:0;'>\n";
                        echo "<p style='float:left;width:100%;margin-bottom:10px;font-size:14px;font-weight:bold;'>"
                           . TEXT_CALCUL_OPT_GAP_TITLE . "</p>\n";
                        echo "<input type='text' id='periode_lacune_first' name='periode_lacune_first'"
                           . " style='float:left;width:200px;padding:5px 0;padding-left:5px;border:0;font-size:14px;color:#5E686D;'"
                           . " readonly value=''>\n";
                        echo "<input type='text' id='periode_lacune_end' name='periode_lacune_end'"
                           . " style='float:left;width:200px;padding:5px 0;padding-left:5px;border:0;font-size:14px;color:#5E686D;'"
                           . " readonly value=''>\n";
                    echo "</div>\n";
                echo "</div>\n";

                echo "<div style='float:right;margin-top:25px;'>\n";
                    echo "<button id='calcul_lacune' class='inverse_axe'"
                       . " style='float:right;width:50px;height:35px;padding:0;color:#EA1179;'"
                       . " title='" . TEXT_CALCUL_OPT_GAP_BTN . "'>";
                        echo "<span style='font-size:22px;margin:0;'>&#x25CF></span>";
                    echo "</button>\n";
                echo "</div>\n";

            echo "</div>\n";


            // ---- Smoothing (line series only) ----
            $display = ($type_chron_array[$typedata_chron]['type_graph'] != 'lines') ? 'display:none;' : '';

            echo "<div style='float:left;margin-top:10px;padding:10px 3%;border:1.5px solid #000;width:88%;" . $display . "'>\n";

                echo "<div style='float:left;width:300px;'>\n";
                    echo "<div id='boite_small' style='width:100%;margin:0;'>\n";
                        echo "<p style='float:left;width:100%;margin-bottom:10px;font-size:14px;font-weight:bold;'>"
                           . TEXT_CALCUL_OPT_SMOOTH_TITLE . "</p>\n";
                        
                        echo "<select name='lissage' id='lissage' style='float:left;width:150px;'>\n";
                            echo "<option value='1'>" . TEXT_CALCUL_OPT_SMOOTH_LOW . "</option>\n";
                        echo "</select>\n";
                        
                        echo "<p style='float:left;width:80px;text-align:right;font-size:14px;color:#428bca;padding-top:7px;'>"
                           . TEXT_CALCUL_OPT_SMOOTH_THRESH . "</p>\n";
                        
                           echo "<input type='text' id='seuil_liss' style='float:left;width:25px;margin-left:10px;' value='0'>\n";
                        
                           echo "<p style='float:left;width:10px;font-size:14px;color:#428bca;margin-left:5px;padding-top:7px;'>"
                           . "%</p>\n";

                    echo "</div>\n";
                echo "</div>\n";

                echo "<div style='float:right;margin-top:18px;'>\n";
                    echo "<button id='calcul_lissage' class='inverse_axe'"
                       . " style='float:right;width:50px;height:35px;padding:0;color:" . $colorMapping['lissage'] . ";'"
                       . " title='" . TEXT_CALCUL_OPT_SMOOTH_BTN . "'>";
                        echo "<span style='font-size:22px;margin:0;'>&#x25CF></span>";
                    echo "</button>\n";
                echo "</div>\n";

            echo "</div>\n";

        echo "<hr>\n";
        echo "</div>\n";

    echo "</div>\n";

echo "</div>\n";
?>

<script type="text/javascript">

    var box_calcul_options = document.getElementById('box_calcul_options');

    // -----------------------------------------------
    // Close popup on X button or outside click

    document.addEventListener('click', function(event)
    {
        if (event.target.id === 'button_close_options' || event.target === box_calcul_options)
        {
            box_calcul_options.style.display = 'none';
        }
    });

    // Close popup on Escape key
    document.addEventListener('keydown', function(event)
    {
        if (event.key === 'Escape') { box_calcul_options.style.display = 'none'; }
    });


    // -----------------------------------------------
    // Show popup and make it draggable

    function affiche_options_calcul()
    {
        box_calcul_options.style.display = 'block';
        initDraggable('title_box_options', 'box_calcul_options');
    }

</script>