<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Popup — Save / Validate confirmation for the H -> Q conversion module.

Slimmed-down variant of block_verif_savedata_calc.php for the convert_hq
flow. We only need:
  - The target discharge series dropdown (so the user can confirm or
    change which Q series the converted data will be written into)
  - A math challenge (+, -, x) that gates the Confirm button

Visual style is aligned with block_verif_savedata_calc.php for
project-wide consistency (cyan header band, grouped grey input card,
yellow challenge bar, Confirm/Cancel buttons).

Expected to be require'd from convert_hq.php with these PHP variables
already in scope:
  $type_chron_array       (associative array of all chronicle types)
  $debit_array            (filtered list of Q types eligible as target)
  $typedata_chron_q       (id of the chronicle currently selected as target)

IDs exposed for the JS in convert_hq.php:
  box_verif_save_hq, select_type_chron_hq,
  save_hq_challenge_question, save_hq_challenge_answer,
  save_hq_challenge_feedback,
  ok_valid_save_hq, no_valid_save_hq.
----------------------------------------
*/


// -----------------------------------------------
// HTML: validation popup

echo "<div id='box_verif_save_hq' class='block_view'
            style='position:absolute;width:560px;top:20px;left:25%;background:none;
                   display:none;z-index:9500;'>\n";

    echo "<div style='padding:0;margin:0;background:#fff;border-radius:6px;"
       . "box-shadow:0 4px 20px rgba(0,0,0,0.18);overflow:hidden;'>";

        // ---- Header band (cyan, draggable) ----
        echo "<p id='title_verif_save_hq'"
           . " style='float:left;width:100%;margin:0;padding:14px 0;"
           . "font-size:16px;font-weight:bold;color:#fff;"
           . "background-color:#176B87;cursor:move;'>";

            echo "<span style='margin-left:18px;'>" . TEXT_SAVEDATA_CONFIRM_TITLE . "</span>";

            echo "<span id='button_close_save_hq' style='float:right;margin-right:18px;cursor:pointer;'"
               . " title='" . TEXT_POPUP_CLOSE . "'>X</span>";

        echo "</p>\n";


        // ---- Inputs card (grouped grey area, only the target series here) ----
        echo "<div style='float:left;width:90%;margin:14px 5% 6px;
                         background-color:#fafafa;
                         border:1px solid #e0e0e0;
                         border-radius:4px;
                         padding:12px 14px;
                         box-sizing:border-box;'>";

            echo "<div style='width:100%;'>";

                echo "<p style='margin:0 0 6px 0;font-size:13px;font-weight:600;color:#176B87;'>"
                   . TEXT_SAVEDATA_CHRON_LABEL . "</p>";

                // Current-series highlight banner (same as block_verif_savedata_calc.php).
                $current_chron_label_hq = '';
                if (isset($type_chron_array[$typedata_chron_q]))
                {
                    $current_chron_label_hq =
                          $type_chron_array[$typedata_chron_q]['init_type_data']
                        . ' - '
                        . $type_chron_array[$typedata_chron_q]['nom_type_data'];
                }

                echo "<div style='margin:0 0 8px 0;padding:6px 10px;"
                   . "background-color:#e6f1f5;border-left:3px solid #176B87;"
                   . "border-radius:3px;font-size:12px;color:#0f4d63;'>";
                    echo "<span style='font-weight:600;'>"
                       . (defined('TEXT_SAVEDATA_CURRENT_SERIES')
                            ? TEXT_SAVEDATA_CURRENT_SERIES
                            : 'Current series') . " :</span> ";
                    echo "<span id='current_chron_hq_label' style='font-weight:bold;'>"
                       . htmlspecialchars($current_chron_label_hq) . "</span>";
                echo "</div>";

                // Target discharge series dropdown — same options as the
                // left-panel dropdown, with the current selection starred.
                echo "<select name='select_type_chron_hq' id='select_type_chron_hq'"
                   . " style='width:100%;padding:6px;font-size:14px;border:1px solid #d4d4d4;"
                   . "border-radius:3px;background:#fff;'>";

                    if (!empty($debit_array))
                    {
                        foreach ($debit_array as $id_type_chron_hq => $type_chron_hq)
                        {
                            if ($id_type_chron_hq != $typedata_chron_q)
                            {
                                echo "<option value='" . $id_type_chron_hq . "'>"
                                   . $type_chron_hq['init_type_data'] . " - "
                                   . $type_chron_hq['nom_type_data'] . "</option>\n";
                            }
                            else
                            {
                                // Currently-targeted series — starred + bold.
                                // (CSS on <option> is honoured by Firefox/Safari;
                                // the star is the reliable cue across browsers.)
                                echo "<option value='" . $id_type_chron_hq . "'"
                                   . " title='" . (defined('TEXT_SAVEDATA_CHRON_CURRENT')
                                                    ? TEXT_SAVEDATA_CHRON_CURRENT : '') . "'"
                                   . " style='font-weight:bold;color:#176B87;background:#e6f1f5;'"
                                   . " selected>★ "
                                   . $type_chron_hq['init_type_data'] . " - "
                                   . $type_chron_hq['nom_type_data']
                                   . "  ("
                                   . (defined('TEXT_SAVEDATA_CURRENT_BADGE')
                                        ? TEXT_SAVEDATA_CURRENT_BADGE
                                        : 'current')
                                   . ")</option>\n";
                            }
                        }
                    }
                echo "</select>";

            echo "</div>";

        echo "</div>"; // /inputs card


        // ---- Overwrite warning (prominent red alert box) ----
        // Mirrors the alarming style used by the chronicle-correction /
        // delete-data popups: red-tinted box, bold border, warning icon, and
        // explicit wording that existing data will be permanently overwritten.
        echo "<div style='float:left;width:90%;margin:12px 5% 0;box-sizing:border-box;
                          padding:12px 14px;
                          background-color:#fdeaea;
                          border:1px solid #e3a0a0;
                          border-left:5px solid #930000;
                          border-radius:5px;
                          display:flex;align-items:flex-start;gap:10px;'>";

            // Warning triangle icon (inline SVG, red)
            echo "<span style='flex:0 0 auto;margin-top:1px;color:#930000;'>"
               . "<svg width='22' height='22' viewBox='0 0 24 24' fill='none' stroke='currentColor'"
               . " stroke-width='2' stroke-linecap='round' stroke-linejoin='round'>"
               . "<path d='M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z'/>"
               . "<line x1='12' y1='9' x2='12' y2='13'/><line x1='12' y1='17' x2='12.01' y2='17'/></svg>"
               . "</span>";

            echo "<div style='flex:1 1 auto;'>";
                echo "<p style='margin:0 0 3px 0;font-size:15px;font-weight:700;color:#930000;'>"
                   . (defined('TEXT_HQ_SAVE_WARNING_TITLE') ? TEXT_HQ_SAVE_WARNING_TITLE : 'Warning — irreversible action')
                   . "</p>";
                echo "<p style='margin:0;font-size:13px;font-weight:500;color:#7a1414;line-height:1.5;'>"
                   . TEXT_SAVEDATA_OVERWRITE_WARNING . "</p>";
            echo "</div>";

        echo "</div>";


        // ---- Math challenge (same pattern as block_verif_savedata_calc.php) ----
        echo "<div id='save_hq_challenge' style='float:left;width:auto;max-width:340px;
                                                 margin:12px 0 0 5%;
                                                 padding:8px 12px;
                                                 background-color:#fff8e6;
                                                 border:1px solid #f0d89a;
                                                 border-left:4px solid #d97706;
                                                 border-radius:4px;
                                                 display:flex;align-items:center;gap:10px;
                                                 font-size:13px;'>";

            echo "<span style='color:#7a4a00;font-weight:600;'>";
                echo defined('TEXT_POPUP_DELETE_CHALLENGE_LABEL')
                    ? TEXT_POPUP_DELETE_CHALLENGE_LABEL
                    : 'Solve to confirm:';
            echo "</span>";

            echo "<span id='save_hq_challenge_question'"
               . " style='font-size:15px;font-weight:bold;color:#2c2c2a;'></span>";

            echo "<input type='text' id='save_hq_challenge_answer'"
               . " inputmode='numeric' autocomplete='off'"
               . " style='width:55px;padding:4px 6px;font-size:14px;text-align:center;"
               . "border:1px solid #d4d4d4;border-radius:3px;'>";

            echo "<span id='save_hq_challenge_feedback' style='font-size:14px;font-weight:600;'></span>";

        echo "</div>";


        // ---- Buttons (Confirm starts disabled, enabled by challenge) ----
        echo "<div style='float:left;width:90%;margin:18px 5% 14px;'>";

            echo "<div style='float:left;width:45%;'>";
                echo "<input type='button' class='button' id='ok_valid_save_hq'"
                   . " value='" . TEXT_SAVEDATA_BTN_CONFIRM . "' disabled"
                   . " style='opacity:0.45;cursor:not-allowed;'>";
            echo "</div>";

            echo "<div style='float:left;width:45%;'>";
                echo "<input type='button' id='no_valid_save_hq' class='button_close'"
                   . " value='" . TEXT_SAVEDATA_BTN_CANCEL . "'>";
            echo "</div>";

        echo "</div>";

    echo "</div>"; // /inner box

echo "</div>"; // /box_verif_save_hq
?>


<script type="text/javascript">

    // -----------------------------------------------
    // Math-challenge logic for the H -> Q save popup.
    // Mirrors generateSaveChallenge() from block_verif_savedata_calc.php
    // but is scoped with the _hq suffix so both popups can co-exist on
    // the same page if needed.

    var boxVerifSaveHq            = document.getElementById('box_verif_save_hq');
    var saveHqChallengeQuestion   = document.getElementById('save_hq_challenge_question');
    var saveHqChallengeAnswer     = document.getElementById('save_hq_challenge_answer');
    var saveHqChallengeFeedback   = document.getElementById('save_hq_challenge_feedback');
    var okValidSaveHq             = document.getElementById('ok_valid_save_hq');
    var saveHqChallengeExpected   = null;


    function generateSaveHqChallenge()
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

        saveHqChallengeExpected             = result;
        saveHqChallengeQuestion.textContent = a + ' ' + op + ' ' + b + ' = ?';
        saveHqChallengeAnswer.value         = '';
        saveHqChallengeFeedback.textContent = '';
        saveHqChallengeFeedback.style.color = '';
        setSaveHqConfirmEnabled(false);
    }


    function setSaveHqConfirmEnabled(enabled)
    {
        if (!okValidSaveHq) { return; }
        okValidSaveHq.disabled      = !enabled;
        okValidSaveHq.style.opacity = enabled ? '1'       : '0.45';
        okValidSaveHq.style.cursor  = enabled ? 'pointer' : 'not-allowed';
    }


    // Live-validate the math answer as the user types
    if (saveHqChallengeAnswer)
    {
        saveHqChallengeAnswer.addEventListener('input', function()
        {
            var raw = saveHqChallengeAnswer.value.trim();

            if (raw === '')
            {
                saveHqChallengeFeedback.textContent = '';
                setSaveHqConfirmEnabled(false);
                return;
            }

            var typed = parseInt(raw, 10);

            if (!isNaN(typed) && typed === saveHqChallengeExpected)
            {
                saveHqChallengeFeedback.textContent = '\u2713';
                saveHqChallengeFeedback.style.color = '#09886d';
                setSaveHqConfirmEnabled(true);
            }
            else
            {
                saveHqChallengeFeedback.textContent = '\u2717';
                saveHqChallengeFeedback.style.color = '#930000';
                setSaveHqConfirmEnabled(false);
            }
        });
    }


    // Regenerate a fresh challenge each time the popup is opened.
    // Same MutationObserver pattern as block_verif_savedata_calc.php.
    if (boxVerifSaveHq)
    {
        var lastDisplayHq = boxVerifSaveHq.style.display;
        var observerHq    = new MutationObserver(function()
        {
            var current = boxVerifSaveHq.style.display;
            if (current !== lastDisplayHq)
            {
                lastDisplayHq = current;
                if (current === 'block')
                {
                    generateSaveHqChallenge();
                    setTimeout(function() { saveHqChallengeAnswer.focus(); }, 100);

                    if (typeof initDraggable === 'function')
                    {
                        initDraggable('title_verif_save_hq', 'box_verif_save_hq');
                    }
                }
            }
        });
        observerHq.observe(boxVerifSaveHq, { attributes: true, attributeFilter: ['style'] });
    }


    // Close popup on close-X button, Cancel button, or outside click
    document.addEventListener("click", function(event)
    {
        if (event.target.id === 'button_close_save_hq' || event.target.id === 'no_valid_save_hq')
        {
            boxVerifSaveHq.style.display = "none";
        }
        if (event.target === boxVerifSaveHq)
        {
            boxVerifSaveHq.style.display = "none";
        }
    });

    // Close popup on Escape key
    document.addEventListener("keydown", function(event)
    {
        if (event.key === "Escape")
        {
            boxVerifSaveHq.style.display = "none";
        }
    });

</script>