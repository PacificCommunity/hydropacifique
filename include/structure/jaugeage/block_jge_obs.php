<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Measurement point observation popup
- Allows editing the observation note for a single JGE measurement point
----------------------------------------
*/

echo "<div id='box_jge_obs' class='block_view' style='width:500px;height:380px;margin-left:40%;background:transparent;'>\n";

    echo "<div id='cadre_view_2' style='padding:0px;background-color:#FBF9F1;'>\n";

        echo "<div>\n";
            echo "<p style='float:left;width:100%;height:40px;padding-left:8px;font-size:24px;font-weight:bold;color:#fff;margin:0px;background-color:#000;'>";
                echo TEXT_JGE_OBS_TITLE;
            echo "</p>\n";
        echo "</div>\n";

        echo "<div style='margin:0 5%;'>\n";

            // Hidden context fields (arm index and row index)
            echo "<input type='hidden' id='jge_pt_nbb' name='jge_pt_nbb' value=''>\n";
            echo "<input type='hidden' id='jge_pt_row' name='jge_pt_row' value=''>\n";

            // Point info (read-only)
            echo "<div style='float:left;'>";

                // Vertical number
                echo "<div style='float:left;width:32%;'>\n";
                    echo "<p style='float:left;width:150px;padding-top:6px;font-size:14px;font-weight:bold;'>" . TEXT_JGE_OBS_VERTICALE . "</p>\n";
                    echo "<input style='float:left;width:60px;height:15px;font-size:14px;background:transparent;border:none;' name='jge_pt_verticale' id='jge_pt_verticale' type='text' value='' readonly>\n";
                echo "</div>\n";

                // Distance from start
                echo "<div style='float:left;width:32%;'>\n";
                    echo "<p style='float:left;width:150px;padding-top:6px;font-size:14px;font-weight:bold;'>" . TEXT_JGE_OBS_DIST . "</p>\n";
                    echo "<input style='float:left;width:60px;height:15px;font-size:14px;background:transparent;border:none;' name='jge_pt_distdepart' id='jge_pt_distdepart' type='text' value='' readonly>\n";
                echo "</div>\n";

                // Measurement depth
                echo "<div style='float:left;width:32%;'>\n";
                    echo "<p style='float:left;width:150px;padding-top:6px;font-size:14px;font-weight:bold;'>" . TEXT_JGE_OBS_PROF . "</p>\n";
                    echo "<input style='float:left;width:60px;height:15px;font-size:14px;background:transparent;border:none;' name='jge_pt_prof' id='jge_pt_prof' type='text' value='' readonly>\n";
                echo "</div>\n";

            echo "</div>\n";

            // Observation text area
            echo "<div style='float:left;width:95%;margin-top:10px;'>";
                echo "<p style='float:left;width:150px;padding-top:6px;font-size:14px;font-weight:bold;'>" . TEXT_JGE_OBS_OBS . "</p>\n";
                echo "<textarea name='jge_pt_obs' id='jge_pt_obs' style='width:100%;height:80px;font-size:14px;'></textarea>\n";
            echo "</div>\n";

            // Validate button
            echo "<div style='float:left;margin-top:15px;'>";
                echo "<input type='submit' style='float:left;width:120px;' class='button' name='valid_pt_obs' id='valid_pt_obs' value='" . TEXT_JGE_OBS_VALIDATE . "' onClick='validObs();'>";
            echo "</div>\n";

            echo "<hr>";

        echo "</div>\n";

    echo "</div>\n";

echo "</div>\n";
?>

<script>

    // -----------------------------------------------
    // Write observation value back to the main table and update icon

    function validObs()
    {
        var nbb       = document.getElementById('jge_pt_nbb').value;
        var row       = document.getElementById('jge_pt_row').value;
        var jgeBoxObs = document.getElementById('jge_pt_obs');
        var jgePtObs  = document.getElementById('jge_pt_obs_' + nbb + '_' + row);
        var jgePtImg  = document.getElementById('jge_pt_img_' + nbb + '_' + row);

        jgePtObs.value = jgeBoxObs.value;

        var imagePath = "<?php echo DIR_WS_IMG_ICO; ?>";
        jgePtImg.src = (jgePtObs.value !== '') ? imagePath + 'info_v.png' : imagePath + 'info_r.png';

        boxObsJGE.style.display = 'none';
    }

</script>
