<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
JGE deletion confirmation popup
- Asks the user to confirm before deleting a JGE record
- Includes a small math challenge (same pattern as ETL deletion) to
  prevent accidental clicks on the X button
- On confirm: calls process_jge_delete.php via AJAX
- Updates the table in place on success (no page reload)
- Style aligned with block_jge_simple.php (header colored, X top-right,
  buttons bottom-right)
----------------------------------------
*/

echo "<div id='box_del_jge' class='block_view'
            style='position:absolute;width:500px;height:auto;top:50px;left:38%;background:none;
                    display:none;flex-direction:column;overflow:hidden;'>\n";

    echo "<div id='cadre_view_2' style='padding:0;margin:0;
                                        display:flex;flex-direction:column;flex:1;overflow:hidden;'>\n";

        // ---- Header (must be the first direct child of #cadre_view_2 to
        //      inherit the blue header style from the global CSS rule:
        //      #cadre_view_2 > *:first-child { background-color: #176B87 ... }
        //      The hidden input must therefore come AFTER the header) ----
        echo "<p id='title_box_jge_simple'
                style='float:left;width:100%;padding:15px 0;
                       font-size:16px;font-weight:bold;
                       color:#000;background-color:#f5f5f5;
                       flex-shrink:0;'>";

            echo "<span style='margin-left:15px;'>" . TEXT_JGE_VERIFDEL_TITLE . "</span>";
            echo "<span id='button_close_del_jge' style='float:right;margin-right:15px;cursor:pointer;' title='" . TEXT_JGE_PTS_CLOSE . "'>X</span>";

        echo "</p>\n";

        // Hidden id of the JGE to delete (placed after the header on purpose)
        echo "<input type='hidden' name='del_jge_id' id='del_jge_id' value=''>";

        // ---- Body ----
        echo "<div style='flex:1;padding:20px 25px;box-sizing:border-box;'>\n";

            echo "<p style='font-size:16px;margin:0 0 15px 0;'>";
                echo TEXT_JGE_VERIFDEL_IRREVERSIBLE;
            echo "</p>\n";

            // Math challenge box
            echo "<div style='padding:10px 12px;background:#fff;border:1px solid #ddd;border-radius:3px;'>";

                echo "<div style='font-size:12px;color:#666;margin-bottom:8px;'>";
                    echo TEXT_JGE_VERIFDEL_CHALLENGE_HINT;
                echo "</div>";

                echo "<div style='display:flex;align-items:center;gap:8px;'>";
                    echo "<span id='del_jge_challenge_a'  style='font-size:16px;font-weight:bold;'></span>";
                    echo "<span id='del_jge_challenge_op' style='font-size:16px;font-weight:bold;'></span>";
                    echo "<span id='del_jge_challenge_b'  style='font-size:16px;font-weight:bold;'></span>";
                    echo "<span style='font-size:16px;font-weight:bold;'>=</span>";
                    echo "<input type='text' id='del_jge_answer' style='width:60px;font-size:16px;padding:4px;' autocomplete='off'>";
                    echo "<span id='del_jge_feedback' style='font-size:14px;font-weight:bold;'></span>";
                echo "</div>";

            echo "</div>";

            // Action buttons - aligned right like block_jge_simple
            echo "<div style='margin-top:20px;text-align:right;'>";
                echo "<input type='button' id='no_valid_del'  class='button_close' value='" . TEXT_JGE_VERIFDEL_CANCEL . "' style='margin-right:8px;'>";
                echo "<input type='button' id='ok_valid_del'  class='button'       value='" . TEXT_JGE_VERIFDEL_OK     . "' style='opacity:0.45;cursor:not-allowed;' disabled>";
            echo "</div>";

        echo "</div>\n";

    echo "</div>\n";

echo "</div>\n";
?>

<script type="text/javascript">

    var popup_del         = document.getElementById('box_del_jge');
    var button_cancel_del = document.getElementById('no_valid_del');
    var button_close_del  = document.getElementById('button_close_del_jge');
    var button_ok_del     = document.getElementById('ok_valid_del');

    var challengeAnswerInput = document.getElementById('del_jge_answer');
    var challengeFeedback    = document.getElementById('del_jge_feedback');
    var challengeExpected    = 0;


    // -----------------------------------------------
    // generateJgeDelChallenge() — pick a random math operation
    // Called every time the popup opens (see verifDelJGE in data_jge.php)

    function generateJgeDelChallenge()
    {
        var ops = ['+', '-', 'x'];
        var op  = ops[Math.floor(Math.random() * ops.length)];
        var a, b;

        if (op === '+')
        {
            a = Math.floor(Math.random() * 16) + 5;
            b = Math.floor(Math.random() * 14) + 2;
            challengeExpected = a + b;
        }
        else if (op === '-')
        {
            a = Math.floor(Math.random() * 21) + 10;
            b = Math.floor(Math.random() * (a - 1)) + 1;
            challengeExpected = a - b;
        }
        else
        {
            a = Math.floor(Math.random() * 8) + 2;
            b = Math.floor(Math.random() * 8) + 2;
            challengeExpected = a * b;
        }

        document.getElementById('del_jge_challenge_a').textContent  = a;
        document.getElementById('del_jge_challenge_op').textContent = op;
        document.getElementById('del_jge_challenge_b').textContent  = b;
        challengeAnswerInput.value    = '';
        challengeFeedback.textContent = '';
        setDelButtonEnabled(false);

        setTimeout(function() { challengeAnswerInput.focus(); }, 100);
    }


    // -----------------------------------------------
    // Enable/disable the OK button based on the challenge answer

    function setDelButtonEnabled(on)
    {
        button_ok_del.disabled       = !on;
        button_ok_del.style.opacity  = on ? '1'       : '0.45';
        button_ok_del.style.cursor   = on ? 'pointer' : 'not-allowed';
    }

    challengeAnswerInput.addEventListener('input', function()
    {
        var v = parseInt(challengeAnswerInput.value, 10);
        if (challengeAnswerInput.value === '' || isNaN(v))
        {
            challengeFeedback.textContent = '';
            setDelButtonEnabled(false);
        }
        else if (v === challengeExpected)
        {
            challengeFeedback.textContent = '✓';
            challengeFeedback.style.color = '#09886d';
            setDelButtonEnabled(true);
        }
        else
        {
            challengeFeedback.textContent = '✗';
            challengeFeedback.style.color = '#930000';
            setDelButtonEnabled(false);
        }
    });


    // -----------------------------------------------
    // Close popup: X button, Cancel button, Escape, or Enter to submit

    document.addEventListener("click", function(event)
    {
        if (event.target === button_cancel_del || event.target === button_close_del)
        {
            popup_del.style.display = "none";
        }
    });

    document.addEventListener("keydown", function(event)
    {
        if (popup_del.style.display !== 'block') { return; }
        if (event.key === "Escape") { popup_del.style.display = "none"; }
        if (event.key === "Enter"  && !button_ok_del.disabled) { button_ok_del.click(); }
    });


    // -----------------------------------------------
    // Confirm: AJAX delete of the JGE

    button_ok_del.addEventListener('click', function()
    {
        if (button_ok_del.disabled) { return; }

        var jgeId   = document.getElementById('del_jge_id').value;
        var msgInfo = document.getElementById('contenu_info');

        if (!jgeId || jgeId == '0') { return; }

        button_ok_del.disabled = true;

        var dataToSend = {
            idUser             : <?php echo isset($id_user)               ? json_encode($id_user)            : 0; ?>,
            todayTimeFormatted : '<?php echo isset($today_time_formatted) ? $today_time_formatted            : date('Y-m-d H:i:s'); ?>',
            territoireId       : <?php echo isset($territoire_id)         ? json_encode($territoire_id)      : 0; ?>,
            idJge              : parseInt(jgeId, 10)
        };

        var xhr = new XMLHttpRequest();
        xhr.open("POST", "include/structure/jaugeage/process_jge_delete.php", true);
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
                    removeJgeRow(r['id_jge']);
                }
                else
                {
                    msgInfo.style.border = '2px solid #930000';
                }
            }
        };

        xhr.send(JSON.stringify(dataToSend));
    });


    // -----------------------------------------------
    // removeJgeRow() — remove the row from the table and decrement the count

    function removeJgeRow(jgeId)
    {
        // Find the row using the hidden id_jge_<id> marker
        var marker = document.getElementById('id_jge_' + jgeId);
        if (!marker) { return; }

        var tr = marker.closest('tr');
        if (!tr)
        {
            var node = marker.nextElementSibling;
            while (node && node.tagName !== 'TR') { node = node.nextElementSibling; }
            tr = node;
        }
        if (!tr) { return; }

        // Remove all hidden inputs for this JGE that sit just before the row
        var hiddenIds = ['id_jge_', 'id_station_', 'code_station_', 'nom_station_',
                         'date_', 'heure_', 'debit_', 'hauteur_',
                         'code_qualite_', 'obs_'];
        hiddenIds.forEach(function(prefix) {
            var el = document.getElementById(prefix + jgeId);
            if (el && el.parentNode) { el.parentNode.removeChild(el); }
        });

        // Remove the row itself
        tr.parentNode.removeChild(tr);

        // Update the record count displayed in the sidebar
        var countDiv = document.getElementById('jge_count_value');
        if (countDiv)
        {
            var n = parseInt(countDiv.textContent.replace(/\s/g, ''), 10);
            if (!isNaN(n) && n > 0)
            {
                countDiv.textContent = (n - 1).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
            }
        }
    }

</script>