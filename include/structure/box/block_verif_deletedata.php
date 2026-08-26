<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Popup — Confirmation before deleting data
----------------------------------------
- Lists the time-series that will be deleted (grouped by station)
- Requires the user to solve a small random math operation (+, -, x)
  before the Confirm button becomes active. Prevents accidental deletion.
----------------------------------------
*/


echo "<div id='box_verif_deletedata' class='block_view'
            style='position:absolute;width:600px;top:20px;left:25%;background:none;
                    display:none'>\n";

    echo "<div id='cadre_view_2' style='float:left;margin-top:20px;padding:0px;' >\n";

        // ---- Header (red: destructive action) ----
        echo "<p id='title_info_chron_del'
                        style='float:left;width:100%;padding:15px 0;
                            font-size:16px;font-weight:bold;
                            color:#fff !important;background-color:#A32D2D !important;'>";

            echo "<span style='margin-left:15px;'>" . TEXT_POPUP_DELETE_CONFIRM . "</span>";

            echo "<span id='button_close_del' style='float:right;margin-right:15px;cursor:pointer;' title='" . TEXT_POPUP_CLOSE . "'>X</span>";

        echo "</p>\n";


        // ---- Period info ----
        echo "<div id='detail_del' style='float:left;width:90%;margin-top:10px;margin-left:5%;display:none;'>";

            echo "<p style='width:100%;'>";
                echo "<input type='text' id='detail_del_text' style='width:100%;font-size:16px;border:none;background:none;' readonly>";
            echo  "</p>\n";

        echo "</div>";


        // ---- List of time-series to be deleted (filled dynamically by JS) ----
        echo "<div id='delete_list_container' style='float:left;width:90%;margin-left:5%;margin-top:10px;
                                                     max-height:200px;overflow-y:auto;
                                                     background-color:#fafafa;
                                                     border:1px solid #e0e0e0;
                                                     border-radius:4px;
                                                     padding:10px 14px;
                                                     box-sizing:border-box;
                                                     font-size:13px;line-height:1.6;'>";
            echo "<div id='delete_list_content'></div>";
        echo "</div>";


        // ---- Irreversible warning (emphasized alert box) ----
        echo "<div style='float:left;width:90%;margin-left:5%;margin-top:15px;'>";

            echo "<div style='display:flex;align-items:flex-start;gap:10px;"
               . "padding:12px 14px;background-color:#FCEBEB;"
               . "border:1px solid #E24B4A;border-left:5px solid #A32D2D;"
               . "border-radius:4px;box-sizing:border-box;'>";

                echo "<span style='font-size:22px;line-height:1;color:#A32D2D;flex-shrink:0;'>&#9888;</span>";

                echo "<span style='font-size:15px;font-weight:bold;color:#A32D2D;line-height:1.4;'>";
                    echo TEXT_POPUP_DELETE_IRREVERSIBLE;
                echo "</span>";

            echo "</div>";

        echo "</div>";


        // ---- Math challenge (compact, single line) ----
        echo "<div id='delete_challenge' style='float:left;width:auto;max-width:340px;margin-left:5%;margin-top:5px;
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

            echo "<span id='challenge_question'
                        style='font-size:15px;font-weight:bold;color:#2c2c2a;'></span>";

            echo "<input type='text' id='challenge_answer'
                        inputmode='numeric' autocomplete='off'
                        style='width:55px;padding:4px 6px;font-size:14px;
                                text-align:center;
                                border:1px solid #d4d4d4;border-radius:3px;'>";

            echo "<span id='challenge_feedback' style='font-size:14px;font-weight:600;'></span>";

        echo "</div>";


        // ---- Buttons ----
        echo "<div style='float:left;width:90%;margin:15px 0;margin-left:5%;'>";

                echo "<div style='float:left;width:45%;'>";
                    echo "<input type='button' class='button' id='ok_valid_deletedata'
                                value='" . TEXT_POPUP_VALIDATE . "' disabled
                                style='opacity:0.45;cursor:not-allowed;'>";
                echo "</div>";

                echo "<div style='float:left;width:45%;'>";
                    echo "<input type='button' id='no_valid_deletedata' class='button_close' value='" . TEXT_POPUP_CANCEL . "'>";
                echo "</div>";

        echo "</div>";

    echo "</div>";


echo "</div>";

?>


<script type="text/javascript">

    // Get the popup and its control elements
    var box_verif_deletedata = document.getElementById('box_verif_deletedata');
    var button_close_del     = document.getElementById('button_close_del');
    var no_valid_deletedata  = document.getElementById('no_valid_deletedata');

    // Math challenge elements
    var challengeQuestion = document.getElementById('challenge_question');
    var challengeAnswer   = document.getElementById('challenge_answer');
    var challengeFeedback = document.getElementById('challenge_feedback');
    var okValidDelete     = document.getElementById('ok_valid_deletedata');

    // Delete list element
    var deleteListContent = document.getElementById('delete_list_content');

    // The current expected answer (set when a new challenge is generated)
    var challengeExpected = null;


    /**
     * Generates a new random math challenge (+, -, or x).
     */
    function generateDeleteChallenge()
    {
        var operators = ['+', '-', 'x'];
        var op = operators[Math.floor(Math.random() * operators.length)];
        var a, b, result;

        if (op === '+') {
            a = Math.floor(Math.random() * 16) + 5;
            b = Math.floor(Math.random() * 14) + 2;
            result = a + b;
        }
        else if (op === '-') {
            a = Math.floor(Math.random() * 21) + 10;
            b = Math.floor(Math.random() * (a - 1)) + 1;
            result = a - b;
        }
        else {
            a = Math.floor(Math.random() * 8) + 2;
            b = Math.floor(Math.random() * 8) + 2;
            result = a * b;
        }

        challengeExpected = result;
        challengeQuestion.textContent = a + ' ' + op + ' ' + b + ' = ?';
        challengeAnswer.value = '';
        challengeFeedback.textContent = '';
        challengeFeedback.style.color = '';
        setConfirmEnabled(false);
    }


    /**
     * Toggles the Confirm button between enabled and disabled states.
     */
    function setConfirmEnabled(enabled)
    {
        if (!okValidDelete) return;
        okValidDelete.disabled = !enabled;
        okValidDelete.style.opacity = enabled ? '1' : '0.45';
        okValidDelete.style.cursor  = enabled ? 'pointer' : 'not-allowed';
    }


    /**
     * Builds the list of time-series that will be deleted, grouped by station.
     * Reads directly from the DOM:
     * - Station name comes from `.ts-card-title` of the closest `.ts-card`
     * - Series code (CI, QI, etc.) comes from the first `<td>` of the row
     */
    function buildDeleteList()
    {
        var checkedBoxes = document.querySelectorAll(
            'input[type="checkbox"]:checked' +
            ':not(#multi_file):not(#format_export):not(#rapport_act)' +
            ':not(#one_graph):not(#new_page):not(#entete_col)'
        );

        // Group series by station
        var grouped = {};

        checkedBoxes.forEach(function(cb) {
            var row  = cb.closest('tr');
            var card = cb.closest('.ts-card');
            if (!row || !card) return;

            var titleEl = card.querySelector('.ts-card-title');
            var stationName = titleEl ? titleEl.textContent.trim() : '?';

            var firstTd = row.querySelector('td');
            var seriesCode = firstTd ? firstTd.textContent.trim() : '?';

            if (!grouped[stationName]) {
                grouped[stationName] = [];
            }
            grouped[stationName].push(seriesCode);
        });

        var stationNames = Object.keys(grouped);

        if (stationNames.length === 0) {
            deleteListContent.innerHTML = '<em style="color:#999;">No series selected</em>';
            return;
        }

        var html = '';
        stationNames.forEach(function(stationName) {
            // Series codes as prominent badges; station name larger and bold.
            var badges = grouped[stationName].map(function(code) {
                return '<span style="display:inline-block;background:#5F5E5A;color:#fff;'
                     + 'font-weight:bold;font-size:14px;padding:2px 9px;border-radius:4px;'
                     + 'margin:2px 4px 2px 0;">' + escapeHtml(code) + '</span>';
            }).join('');

            html += '<div style="margin-bottom:10px;">';
            html += '<div style="font-size:15px;font-weight:bold;color:#176B87;margin-bottom:4px;">'
                  + escapeHtml(stationName) + '</div>';
            html += '<div>' + badges + '</div>';
            html += '</div>';
        });

        deleteListContent.innerHTML = html;
    }


    /**
     * Minimal HTML escape for safe injection of station/series names.
     */
    function escapeHtml(str)
    {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }


    /**
     * Live-validates the answer as the user types.
     */
    if (challengeAnswer) {
        challengeAnswer.addEventListener('input', function() {
            var raw = challengeAnswer.value.trim();

            if (raw === '') {
                challengeFeedback.textContent = '';
                setConfirmEnabled(false);
                return;
            }

            var typed = parseInt(raw, 10);

            if (!isNaN(typed) && typed === challengeExpected) {
                challengeFeedback.textContent = '\u2713';
                challengeFeedback.style.color = '#09886d';
                setConfirmEnabled(true);
            } else {
                challengeFeedback.textContent = '\u2717';
                challengeFeedback.style.color = '#930000';
                setConfirmEnabled(false);
            }
        });
    }


    // ----------------------------------------------------------------
    // MutationObserver: regenerate a fresh challenge AND rebuild the
    // series list each time the popup is opened.
    // ----------------------------------------------------------------
    if (box_verif_deletedata) {
        var lastDisplay = box_verif_deletedata.style.display;
        var observer = new MutationObserver(function(mutations) {
            var current = box_verif_deletedata.style.display;
            if (current !== lastDisplay) {
                lastDisplay = current;
                if (current === 'block') {
                    buildDeleteList();
                    generateDeleteChallenge();
                    setTimeout(function() { challengeAnswer.focus(); }, 100);

                    // Make the popup draggable via the title bar.
                    // Called here (after display becomes 'block') so the
                    // container has real dimensions when initDraggable reads them.
                    if (typeof initDraggable === 'function') {
                        initDraggable('title_info_chron_del', 'box_verif_deletedata');
                    }
                }
            }
        });
        observer.observe(box_verif_deletedata, { attributes: true, attributeFilter: ['style'] });
    }


    // Close the popup when the close button or cancel button is clicked
    document.addEventListener("click", function(event)
    {
        if (event.target.id === 'button_close_del' || event.target.id === 'no_valid_deletedata')
        {
            box_verif_deletedata.style.display = "none";
        }

        // Close if clicking outside the popup
        if (event.target === box_verif_deletedata)
        {
            box_verif_deletedata.style.display = "none";
        }
    });

    // Close the popup on Escape key
    document.addEventListener("keydown", function(event)
    {
        if (event.key === "Escape")
        {
            box_verif_deletedata.style.display = "none";
        }
    });

</script>