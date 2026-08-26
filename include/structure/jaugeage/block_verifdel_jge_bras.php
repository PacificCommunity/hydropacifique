<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Gauging arm deletion confirmation popup
- Asks user to confirm before deleting a JGE arm (bras)
- On confirm: calls process_jge_bras_delete.php via AJAX, then reloads
  the page so the tab numbering is rebuilt cleanly by the server
----------------------------------------
*/

echo "<div id='box_del_jge_bras' class='block_view'
            style='position:absolute;width:660px;height:400px;top:20px;left:30%;background:none;
                    display:none;flex-direction:column;overflow:hidden;'>\n";

    echo "<div id='cadre_view_del' style='width:500px;margin-top:100px;padding:0;'>";

        echo "<input type='hidden' name='del_bras' id='del_bras' value=''>";

        echo "<p id='title_box_jge_bras'"
                . " style='flex-shrink:0;width:100%;padding:15px 0;text-align:center;"
                . "font-size:16px;font-weight:bold;color:#fff;background-color:#176B87;"
                . "box-sizing:border-box;'>";
                
            echo "<span style='font-size:18px;font-weight:bold;margin-left:5px;'>";
                echo TEXT_JGE_BRAS_VERIFDEL_TITLE;
            echo "</span>";
        echo "</p>\n";

        echo "<div style='float:left;width:80%;margin-top:10px;margin-left:10%;'>";

            echo "<p style='width:100%;margin-top:15px;font-size:18px;'>";
                echo TEXT_JGE_VERIFDEL_IRREVERSIBLE;
            echo "</p>\n";

            echo "<p style='width:100%;margin-top:5px;font-size:15px;'>";
                echo TEXT_JGE_BRAS_VERIFDEL_WARNING;
                echo "<br>";
                echo TEXT_JGE_BRAS_VERIFDEL_UNSAVED;
            echo "</p>\n";

        echo "</div>";

        echo "<div style='float:left;width:80%;margin-top:25px;margin-left:10%;'>";

            echo "<div style='float:left;width:45%;'>";
                echo "<input type='button' class='button' id='ok_valid_del' value='" . TEXT_JGE_VERIFDEL_OK . "'>";
            echo "</div>";

            echo "<div style='float:left;width:45%;'>";
                echo "<input type='button' id='no_valid_del' class='button_close' value='" . TEXT_JGE_VERIFDEL_CANCEL . "'>";
            echo "</div>";

        echo "<hr>";
        echo "</div>";

    echo "<hr>";
    echo "</div>";

echo "</div>";
?>

<script type="text/javascript">

    var idJGE             = <?php echo $id_jge; ?>;
    var popup_del         = document.getElementById('box_del_jge_bras');
    var button_cancel_del = document.getElementById('no_valid_del');
    var button_del        = document.getElementById('ok_valid_del');

    document.addEventListener("click", function(event)
    {
        // Cancel: close popup
        if (event.target === button_cancel_del)
        {
            popup_del.style.display = "none";
        }

        // Confirm: AJAX delete of the arm
        if (event.target === button_del)
        {
            var idBras  = document.getElementById('del_bras').value;
            var msgInfo = document.getElementById('contenu_info');

            if (!idBras || idBras == '0') { return; }

            button_del.disabled = true;

            var dataToSend = {
                idUser             : <?php echo isset($id_user)               ? json_encode($id_user)       : 0; ?>,
                todayTimeFormatted : '<?php echo isset($today_time_formatted) ? $today_time_formatted       : date('Y-m-d H:i:s'); ?>',
                territoireId       : <?php echo isset($territoire_id)         ? json_encode($territoire_id) : 0; ?>,
                idJge              : idJGE,
                idBras             : parseInt(idBras, 10)
            };

            var xhr = new XMLHttpRequest();
            xhr.open("POST", "include/structure/jaugeage/process_jge_bras_delete.php", true);
            xhr.setRequestHeader("Content-Type", "application/json");

            xhr.onreadystatechange = function()
            {
                if (xhr.readyState === 4 && xhr.status === 200)
                {
                    popup_del.style.display = "none";

                    var r = JSON.parse(xhr.responseText);

                    msgInfo.innerHTML     = r['js_text'];
                    msgInfo.style.display = 'block';
                    msgInfo.style.zIndex  = '3000';

                    if (r['valid_process'])
                    {
                        msgInfo.style.border = '2px solid #09886d';

                        // Reload so the server rebuilds the arm tabs with clean
                        // sequential numbering (avoids stale _<nbb> suffixes)
                        setTimeout(function()
                        {
                            window.location.href = 'modif_jge.php?ref=' + idJGE;
                        }, 1000);
                    }
                    else
                    {
                        msgInfo.style.border = '2px solid #930000';
                        button_del.disabled  = false;
                    }
                }
            };

            xhr.send(JSON.stringify(dataToSend));
        }
    });

    document.addEventListener("keydown", function(event)
    {
        if (event.key === "Escape") { popup_del.style.display = "none"; }
    });

</script>