<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Quick gauging entry popup
- Allows fast entry of a water level and flow rate for a JGE
- Accessible from data_jge.php
- Saves via AJAX (process_jge_simple_save.php)
----------------------------------------
*/

echo "<div id='box_jge_simple' class='block_view'
            style='position:absolute;width:700px;height:350px;top:50px;left:32%;background:none;
                    display:none;flex-direction:column;overflow:hidden;'>\n";

    echo "<div id='cadre_view_2' style='padding:0px;margin:0;
                                        display:flex;flex-direction:column;flex:1;overflow:hidden;'>\n";

        echo "<p id='title_box_jge_simple'
                style='float:left;width:100%;padding:15px 0;
                       font-size:16px;font-weight:bold;
                       color:#fff;
                       flex-shrink:0;'>";

            echo "<span id='title_box_span' style='margin-left:15px;'>" . TEXT_JGE_SIMPLE_TITLE . "</span>";
            echo "<span id='button_close_jge_simple' style='float:right;margin-right:15px;cursor:pointer;' title='" . TEXT_JGE_PTS_CLOSE . "'>X</span>";

        echo "</p>\n";

        echo "<div style='flex:1;overflow:auto;padding:15px 20px;padding-bottom:0px;box-sizing:border-box;'>\n";

            echo "<div style='float:left;'>";

                // Station name
                echo "<p style='float:left;width:90px;font-size:18px;font-weight:bold;'>" . TEXT_JGE_SIMPLE_STATION . "</p>\n";
                echo "<input style='float:left;width:400px;padding:0;font-size:18px;border:0;background:none;' name='jge_station' id='jge_station' type='text' value='' readonly>\n";

            echo "</div>\n";

            echo "<hr>";

            // Hidden IDs
            echo "<input type='hidden' name='jge_id'         id='jge_id'         value=''>";
            echo "<input type='hidden' name='jge_id_station' id='jge_id_station' value=''>";

            echo "<div style='float:left;margin-top:20px;margin-bottom:10px;'>";

                echo "<div style='float:left;width:50%;'>";

                    // Flow rate
                    echo "<div style='float:left;width:40%;margin-right:5%;'>\n";
                        echo "<p style='font-size:14px;font-weight:bold;'>" . TEXT_JGE_SIMPLE_DEBIT . "</p>\n";
                        echo "<input style='width:80px;height:15px;font-size:14px;' name='jge_debit' id='jge_debit' type='text' value=''>\n";
                    echo "</div>\n";

                    // Water level
                    echo "<div style='float:left;width:40%;'>\n";
                        echo "<p style='font-size:14px;font-weight:bold;'>" . TEXT_JGE_SIMPLE_HAUTEUR . "</p>\n";
                        echo "<input style='width:80px;height:15px;font-size:14px;' name='jge_hauteur' id='jge_hauteur' type='text' value=''>\n";
                    echo "</div>\n";

                    echo "<hr>";

                    echo "<div style='float:left;width:100%;margin-top:10px;'>\n";

                        // Date
                        echo "<div style='float:left;width:40%;margin-right:5%;'>\n";
                            echo "<p style='font-size:14px;font-weight:bold;'>" . TEXT_JGE_SIMPLE_DATE . "</p>\n";
                            echo "<input style='width:80px;'"
                                   . " name='jge_date' id='jge_date' type='text'"
                                   . " onfocus='initDatepickers(this)'"
                                   . " placeholder='dd-mm-yyyy' value=''>\n";
                        echo "</div>\n";

                        // Time
                        echo "<div style='float:left;width:40%;'>\n";
                            echo "<p style='font-size:14px;font-weight:bold;'>" . TEXT_JGE_SIMPLE_HEURE . "</p>";
                            echo "<input style='width:80px;' name='jge_heure' id='jge_heure' type='text' value=''>";
                        echo "</div>\n";

                    echo "</div>\n";

                echo "</div>\n";

                echo "<div style='float:right;width:50%;'>";

                    // Observation
                    echo "<p style='float:left;font-size:14px;font-weight:bold;'>" . TEXT_JGE_SIMPLE_OBS . "</p>\n";
                    echo "<textarea name='jge_obs' id='jge_obs' style='width:98%;height:60px;'></textarea>\n";

                    // Quality code
                    echo "<div style='float:right;margin-top:10px;'>\n";
                        echo "<p style='float:left;width:100px;padding-top:9px;font-size:14px;font-weight:bold;color:#930000;'>" . TEXT_JGE_SIMPLE_CODE_QUAL . "</p>\n";
                        echo "<select name='select_jge_code_qual' id='select_jge_code_qual' style='float:right;width:200px;'>";
                            echo "<option value='0'>-</option>";
                            if (isset($code_qual_array))
                            {
                                foreach ($code_qual_array as $key => $value)
                                {
                                    echo "<option value='" . $key . "' title='" . $code_qual_array[$key]['nom_qualite_data'] . "'>";
                                        echo $code_qual_array[$key]['init_qualite_data'] . " " . $code_qual_array[$key]['nom_qualite_data'];
                                    echo "</option>";
                                }
                            }
                        echo "</select>";
                    echo "</div>\n";

                    // Action buttons (Cancel + Save)
                    echo "<div style='float:right;margin-top:30px;text-align:right;'>";
                        echo "<input type='button' id='cancel_jge_simple' class='button_close' value='" . TEXT_JGE_VERIFDEL_CANCEL . "' style='margin-right:8px;'>";
                        echo "<input type='button' id='save_jge_simple'   class='button'       value='" . TEXT_JGE_SIMPLE_SAVE     . "' onclick='saveJgeSimple();'>";
                    echo "</div>";

                echo "</div>\n";

            echo "</div>\n";

        echo "</div>\n";

    echo "</div>\n";

echo "</div>\n";
?>

<script type="text/javascript">

    var boxJGESimple = document.getElementById('box_jge_simple');

    // -----------------------------------------------
    // Close popup: X button, Cancel button, or Escape

    document.addEventListener("click", function(event)
    {
        if (event.target.id === 'button_close_jge_simple'
         || event.target.id === 'cancel_jge_simple')
        {
            boxJGESimple.style.display = "none";
        }
    });

    document.addEventListener("keydown", function(event)
    {
        if (event.key === "Escape") { boxJGESimple.style.display = "none"; }
    });

    $(document).ready(function()
    {
        $('#select_jge_code_qual').select2({
            placeholder: '<?php echo TEXT_JGE_SIMPLE_CODE_QUAL_PLACEHOLDER; ?>',
            allowClear: true,
        });
    });


    // -----------------------------------------------
    // saveJgeSimple() — AJAX save of a quick JGE
    // Sends the popup fields to process_jge_simple_save.php and displays
    // the result in #contenu_info (same pattern as ETL).

    function saveJgeSimple()
    {
        var btn     = document.getElementById('save_jge_simple');
        var msgInfo = document.getElementById('contenu_info');

        btn.disabled = true;

        var dataToSend = {
            idUser             : <?php echo isset($id_user)            ? json_encode($id_user)            : 0; ?>,
            todayTimeFormatted : '<?php echo isset($today_time_formatted) ? $today_time_formatted : date('Y-m-d H:i:s'); ?>',
            territoireId       : <?php echo isset($territoire_id)      ? json_encode($territoire_id)      : 0; ?>,
            idJge              : document.getElementById('jge_id').value,
            idStation          : document.getElementById('jge_id_station').value,
            hauteur            : document.getElementById('jge_hauteur').value,
            debit              : document.getElementById('jge_debit').value,
            date               : document.getElementById('jge_date').value,
            heure              : document.getElementById('jge_heure').value,
            codeQual           : document.getElementById('select_jge_code_qual').value,
            obs                : document.getElementById('jge_obs').value
        };

        var xhr = new XMLHttpRequest();
        xhr.open("POST", "include/structure/jaugeage/process_jge_simple_save.php", true);
        xhr.setRequestHeader("Content-Type", "application/json");

        xhr.onreadystatechange = function()
        {
            if (xhr.readyState === 4 && xhr.status === 200)
            {
                btn.disabled = false;

                var r = JSON.parse(xhr.responseText);

                msgInfo.innerHTML     = r['js_text'];
                msgInfo.style.display = 'block';
                msgInfo.style.zIndex  = '3000';

                if (r['valid_process'])
                {
                    msgInfo.style.border = '2px solid #09886d';

                    // Update table row in place, then reload to resync the list
                    updateJgeRow(r['id_jge'], r);
                    document.getElementById('jge_id').value = r['id_jge'];

                    setTimeout(function()
                    {
                        boxJGESimple.style.display = 'none';
                        window.location.reload();
                    }, 1500);
                }
                else
                {
                    msgInfo.style.border = '2px solid #930000';
                }
            }
        };

        xhr.send(JSON.stringify(dataToSend));
    }


    // -----------------------------------------------
    // updateJgeRow() — refresh visible cells and hidden fields of a JGE row

    function updateJgeRow(jgeId, data)
    {
        // Update hidden fields so the popup re-opens with fresh data
        var setVal = function(id, value)
        {
            var el = document.getElementById(id);
            if (el) { el.value = value; }
        };

        setVal('date_'    + jgeId, data.date);
        setVal('heure_'   + jgeId, data.heure);
        setVal('hauteur_' + jgeId, data.hauteur);
        setVal('debit_'   + jgeId, data.debit);

        // Find the row using the hidden id_jge_<id> marker
        var marker = document.getElementById('id_jge_' + jgeId);
        if (!marker) { return; }

        // Hidden inputs sit just before the <tr> — walk siblings to find it
        var tr = marker.closest('tr');
        if (!tr)
        {
            var node = marker.nextElementSibling;
            while (node && node.tagName !== 'TR') { node = node.nextElementSibling; }
            tr = node;
        }
        if (!tr) { return; }

        // Column order in data_jge.php:
        // 0 = type, 1 = code, 2 = nom, 3 = date, 4 = heure, 5 = nb_bras, 6 = debit, 7 = hauteur
        var cells = tr.getElementsByTagName('td');
        if (cells.length >= 8)
        {
            cells[3].innerHTML = data.date;
            cells[4].innerHTML = data.heure;
            cells[6].innerHTML = data.debit;
            cells[7].innerHTML = data.hauteur;
        }
    }

</script>