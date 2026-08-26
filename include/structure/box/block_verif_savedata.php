<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Popup — Confirmation before saving data
----------------------------------------
*/


$day   = date('d');
$month = date('m');
$year  = date('Y');

echo "<div id='box_verif_savedata' class='block_view'
            style='position:absolute;width:550px;top:20px;left:25%;background:none;
                    display:none'>\n";

    echo "<div id='cadre_view_2' style='float:left;margin-top:20px;padding:0px;' >\n";

        echo "<p id='title_info_chron_save'
                        style='float:left;width:100%;padding:15px 0;
                            font-size:16px;font-weight:bold;
                            color:#000;background-color:#f5f5f5;'>";

            echo "<span style='margin-left:15px;'>" . TEXT_POPUP_SAVE_CONFIRM . "</span>";

            echo "<span id='button_close_save' style='float:right;margin-right:15px;cursor:pointer;' title='" . TEXT_POPUP_CLOSE . "'>X</span>";

        echo "</p>\n";

        echo "<div style='float:left;width:80%;margin-top:15px;margin-left:10%;'>";

            echo "<p style='width:100%;font-size:18px;'>";
                echo TEXT_POPUP_SAVE_OVERWRITE;
            echo  "</p>\n";

        echo "</div>";

        // ---- Math challenge (anti-misclick before integrating data) ----
        // The OK button stays disabled until the user solves a small
        // +/-/x question. Same pattern as the H->Q convert module.
        echo "<div id='savedata_challenge'
                    style='float:left;width:auto;max-width:340px;
                           margin:6px 0 0 10%;padding:8px 12px;
                           background-color:#fff8e6;border:1px solid #f0d89a;
                           border-left:4px solid #d97706;border-radius:4px;
                           display:flex;align-items:center;gap:10px;font-size:13px;'>";

            echo "<span style='color:#7a4a00;font-weight:600;'>"
               . TEXT_POPUP_DELETE_CHALLENGE_LABEL . "</span>";

            echo "<span id='savedata_challenge_question'
                        style='font-size:15px;font-weight:bold;color:#2c2c2a;'></span>";

            echo "<input type='text' id='savedata_challenge_answer'
                        inputmode='numeric' autocomplete='off'
                        style='width:55px;padding:4px 6px;font-size:14px;text-align:center;
                               border:1px solid #d4d4d4;border-radius:3px;'>";

            echo "<span id='savedata_challenge_feedback'
                        style='font-size:14px;font-weight:600;'></span>";

        echo "</div>";

        echo "<div style='float:left;width:80%;margin:20px 0;margin-left:10%;'>";

                echo "<div style='float:left;width:45%;'>";
                    echo "<input type='button' class='button' id='ok_valid_savedata' value='" . TEXT_POPUP_VALIDATE . "'"
                       . " disabled style='opacity:0.45;cursor:not-allowed;'>";
                echo "</div>";

                echo "<div style='float:left;width:45%;'>";
                    echo "<input type='button' id='no_valid_savedata' class='button_close' value='" . TEXT_POPUP_CANCEL . "'>";
                echo "</div>";

        echo "</div>";

    echo "</div>";


echo "</div>";

?>


<script type="text/javascript">

    // Get the popup and its control elements
    var box_verif_savedata  = document.getElementById('box_verif_savedata');
    var button_close_save   = document.getElementById('button_close_save');
    var no_valid_savedata   = document.getElementById('no_valid_savedata');


    // -----------------------------------------------
    // Math challenge — gates the OK button so data is never integrated
    // by an accidental click. A fresh +/-/x question is generated each
    // time the popup opens; OK stays disabled until it is solved.

    var savedataChallengeQuestion = document.getElementById('savedata_challenge_question');
    var savedataChallengeAnswer   = document.getElementById('savedata_challenge_answer');
    var savedataChallengeFeedback = document.getElementById('savedata_challenge_feedback');
    var okValidSavedata           = document.getElementById('ok_valid_savedata');
    var savedataChallengeExpected = null;

    function setSavedataOkEnabled(enabled)
    {
        if (!okValidSavedata) { return; }
        okValidSavedata.disabled      = !enabled;
        okValidSavedata.style.opacity = enabled ? '1'       : '0.45';
        okValidSavedata.style.cursor  = enabled ? 'pointer' : 'not-allowed';
    }

    function generateSavedataChallenge()
    {
        var operators = ['+', '-', 'x'];
        var op = operators[Math.floor(Math.random() * operators.length)];
        var a, b, result;

        if (op === '+')
        {
            a = Math.floor(Math.random() * 16) + 5;
            b = Math.floor(Math.random() * 14) + 2;
            result = a + b;
        }
        else if (op === '-')
        {
            a = Math.floor(Math.random() * 21) + 10;
            b = Math.floor(Math.random() * (a - 1)) + 1;
            result = a - b;
        }
        else
        {
            a = Math.floor(Math.random() * 8) + 2;
            b = Math.floor(Math.random() * 8) + 2;
            result = a * b;
        }

        savedataChallengeExpected = result;
        if (savedataChallengeQuestion) { savedataChallengeQuestion.textContent = a + ' ' + op + ' ' + b + ' = ?'; }
        if (savedataChallengeAnswer)   { savedataChallengeAnswer.value = ''; }
        if (savedataChallengeFeedback) { savedataChallengeFeedback.textContent = ''; }
        setSavedataOkEnabled(false);
    }

    // Live-validate the answer as the user types
    if (savedataChallengeAnswer)
    {
        savedataChallengeAnswer.addEventListener('input', function()
        {
            var raw = savedataChallengeAnswer.value.trim();

            if (raw === '')
            {
                savedataChallengeFeedback.textContent = '';
                setSavedataOkEnabled(false);
                return;
            }

            var typed = parseInt(raw, 10);
            if (!isNaN(typed) && typed === savedataChallengeExpected)
            {
                savedataChallengeFeedback.textContent = '\u2713';
                savedataChallengeFeedback.style.color = '#09886d';
                setSavedataOkEnabled(true);
            }
            else
            {
                savedataChallengeFeedback.textContent = '\u2717';
                savedataChallengeFeedback.style.color = '#930000';
                setSavedataOkEnabled(false);
            }
        });
    }

    // Regenerate a fresh challenge each time the popup is shown
    // (display toggled from 'none' to 'block' by form_import_step1.php).
    if (box_verif_savedata)
    {
        var lastDisplaySavedata = box_verif_savedata.style.display;
        var observerSavedata    = new MutationObserver(function()
        {
            var current = box_verif_savedata.style.display;
            if (current !== lastDisplaySavedata)
            {
                lastDisplaySavedata = current;
                if (current === 'block')
                {
                    generateSavedataChallenge();
                    setTimeout(function() {
                        if (savedataChallengeAnswer) { savedataChallengeAnswer.focus(); }
                    }, 100);
                }
            }
        });
        observerSavedata.observe(box_verif_savedata, { attributes: true, attributeFilter: ['style'] });
    }


    // Close the popup when the close button or cancel button is clicked
    document.addEventListener("click", function(event)
    {
        if (event.target.id === 'button_close_save' || event.target.id === 'no_valid_savedata')
        {
            box_verif_savedata.style.display = "none";
        }

        // Close if clicking outside the popup
        if (event.target === box_verif_savedata)
        {
            box_verif_savedata.style.display = "none";
        }
    });

    // Close the popup on Escape key
    document.addEventListener("keydown", function(event)
    {
        if (event.key === "Escape")
        {
            box_verif_savedata.style.display = "none";
        }
    });

</script>