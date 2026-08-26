<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Popup — Save / Save as... confirmation for the correction module.

Visual layout aligned with block_verif_deletedata.php for project-wide
consistency:
  - Cyan / dark-blue header band with white title and close X
  - Grouped grey "card" containing the 3 input zones
      * Time series to modify / create (dropdown + current chron label)
      * Quality code dropdown
      * Observation textarea
  - Overwrite warning in red
  - Math challenge (+, -, x) that must be solved before the Confirm
    button becomes active — same UX as the delete popup.
  - Confirm / Cancel buttons at the bottom.

IDs preserved (consumed by saveCorrection() / validation flow in
data_chron_calcul.php):
  box_verif_savedata, cadre_modif_chron, cadre_chron_qual,
  select_type_chron, id_modif_chron, text_modif_chron,
  select_qual_chron, obs_user,
  ok_valid_savedata, no_valid_savedata.
----------------------------------------
*/


// -----------------------------------------------
// Query: Quality codes for the current data type

$quality_query = tep_db_query($sql_link,
    "SELECT DISTINCT id_data_qualite, init_qualite_data, nom_qualite_data, info_qualite_data, id_eq_type
     FROM " . TABLE_DATA_QUALITE . "
     WHERE (id_eq_type = " . $id_typedata_encours . " OR id_eq_type = '') AND init_qualite_data <> ''
     ORDER BY init_qualite_data ASC");

while ($quality_tab = tep_db_fetch_array($quality_query))
{
    $quality_array[$quality_tab['id_data_qualite']] = [
        'init_qualite_data' => html_entity_decode($quality_tab['init_qualite_data'] ?? ''),
        'nom_qualite_data'  => html_entity_decode($quality_tab['nom_qualite_data']  ?? ''),
        'info_qualite_data' => html_entity_decode($quality_tab['info_qualite_data'] ?? ''),
        'id_eq_type'        => html_entity_decode($quality_tab['id_eq_type']        ?? ''),
    ];
}


// -----------------------------------------------
// HTML: Save / Save as confirmation popup

echo "<div id='box_verif_savedata' class='block_view'
            style='position:absolute;width:620px;top:20px;left:25%;background:none;
                    display:none;'>\n";

    echo "<div id='cadre_view' style='padding:0;margin:0;background:#fff;border-radius:6px;"
       . "box-shadow:0 4px 20px rgba(0,0,0,0.18);overflow:hidden;'>";

        // ----------------------------------------------------------
        // ---- Header band (cyan, same as block_verif_deletedata) ----
        // ----------------------------------------------------------
        echo "<p id='title_verif_savedata'"
           . " style='float:left;width:100%;margin:0;padding:14px 0;"
           . "font-size:16px;font-weight:bold;color:#fff;"
           . "background-color:#BA7517;cursor:move;'>";

            echo "<span style='margin-left:18px;'>" . TEXT_SAVEDATA_CONFIRM_TITLE . "</span>";

            echo "<span id='button_close_save' style='float:right;margin-right:18px;cursor:pointer;'"
               . " title='" . TEXT_POPUP_CLOSE . "'>X</span>";

        echo "</p>\n";


        // ----------------------------------------------------------
        // ---- Inputs card (grouped grey area) ----
        // ----------------------------------------------------------
        echo "<div style='float:left;width:90%;margin:14px 5% 6px;
                         background-color:#fafafa;
                         border:1px solid #e0e0e0;
                         border-radius:4px;
                         padding:12px 14px;
                         box-sizing:border-box;'>";

            // ---- Time series to modify / create ----
            echo "<div id='cadre_modif_chron' style='width:100%;margin-bottom:14px;display:none;'>";

                echo "<p style='margin:0 0 6px 0;font-size:13px;font-weight:600;color:#176B87;'>"
                   . TEXT_SAVEDATA_CHRON_LABEL . "</p>";

                // Highlight banner for the currently-edited time series.
                // The user lands on the Save popup with the current chronicle
                // pre-selected in the dropdown below; this banner makes it
                // obvious which one it is even before opening the list, and
                // keeps the info visible after the user picks another series.
                $current_chron_label = '';
                if (isset($type_chron_array[$typedata_chron]))
                {
                    $current_chron_label =
                          $type_chron_array[$typedata_chron]['init_type_data']
                        . ' - '
                        . $type_chron_array[$typedata_chron]['nom_type_data'];
                }

                echo "<div style='margin:0 0 8px 0;padding:6px 10px;"
                   . "background-color:#e6f1f5;border-left:3px solid #176B87;"
                   . "border-radius:3px;font-size:12px;color:#0f4d63;'>";
                    echo "<span style='font-weight:600;'>"
                       . (defined('TEXT_SAVEDATA_CURRENT_SERIES')
                            ? TEXT_SAVEDATA_CURRENT_SERIES
                            : 'Current series') . " :</span> ";
                    echo "<span style='font-weight:bold;'>"
                       . htmlspecialchars($current_chron_label) . "</span>";
                echo "</div>";

                echo "<select name='select_type_chron' id='select_type_chron'"
                   . " style='width:100%;padding:6px;font-size:14px;border:1px solid #d4d4d4;"
                   . "border-radius:3px;background:#fff;'>";
                    // Option vide par défaut : force l'utilisateur à choisir
                    // explicitement la chronique d'accueil de la correction
                    // (pas de validation par inadvertance sur une cible
                    // pré-sélectionnée).
                    echo "<option value='' selected disabled>"
                       . (defined('TEXT_SAVEDATA_CHRON_PLACEHOLDER')
                            ? TEXT_SAVEDATA_CHRON_PLACEHOLDER
                            : '-- Choisir la chronique d\'accueil --')
                       . "</option>\n";
                    if (isset($type_chron_array))
                    {
                        foreach ($type_chron_array as $id_type_chron => $type_chron)
                        {
                            if ($type_chron['id_eq_type_data'] == $id_typedata_encours)
                            {
                                // On EXCLUT la chronique d'origine de la
                                // correction ($typedata_chron) : Save as ne
                                // doit enregistrer que vers une AUTRE série.
                                // La série courante reste affichée dans le
                                // bandeau « Current series » ci-dessus, mais
                                // n'est plus proposée comme cible.
                                //
                                // On EXCLUT aussi les séries brutes (raw_data) :
                                // une série brute ne doit jamais recevoir de
                                // correction, elle ne peut donc pas être cible.
                                $is_raw_target = (isset($type_chron['raw_data']) && $type_chron['raw_data'] == 1);

                                if ($id_type_chron != $typedata_chron && !$is_raw_target)
                                {
                                    echo "<option value='" . $id_type_chron . "'>"
                                       . $type_chron['init_type_data'] . " - "
                                       . $type_chron['nom_type_data'] . "</option>\n";
                                }
                            }
                        }
                    }
                echo "</select>";

                echo "<input type='hidden' id='id_modif_chron'>";
                // Echo de la chronique sélectionnée — conservé en hidden
                // pour la compat avec le JS consommateur (text_modif_chron),
                // mais plus affiché : le nom de la série cible se lit
                // directement dans le <select> ci-dessus, et la série
                // d'origine ne doit pas être ré-affichée ici.
                echo "<input type='hidden' id='text_modif_chron'>";

            echo "</div>";


            // ---- Quality code ----
            echo "<div id='cadre_chron_qual' style='width:100%;margin-bottom:14px;'>";

                echo "<p style='margin:0 0 6px 0;font-size:13px;font-weight:600;color:#176B87;'>"
                   . TEXT_SAVEDATA_QUAL_LABEL . "</p>";

                echo "<select name='select_qual_chron' id='select_qual_chron'"
                   . " style='width:260px;padding:6px;font-size:14px;border:1px solid #d4d4d4;"
                   . "border-radius:3px;background:#fff;'>";
                    echo "<option value='0'>-</option>\n";
                    if (isset($quality_array))
                    {
                        foreach ($quality_array as $id_quality => $quality_data)
                        {
                            echo "<option value='" . $id_quality . "'>"
                               . $quality_data['init_qualite_data'] . " - "
                               . $quality_data['nom_qualite_data'] . "</option>\n";
                        }
                    }
                echo "</select>";

            echo "</div>";


            // ---- Observation ----
            echo "<div style='width:100%;'>";

                echo "<p style='margin:0 0 6px 0;font-size:13px;font-weight:600;color:#176B87;'>"
                   . TEXT_SAVEDATA_OBS_LABEL . "</p>";

                echo "<textarea id='obs_user' name='obs_user'"
                   . " style='width:100%;height:70px;font-size:13px;padding:6px;"
                   . "border:1px solid #d4d4d4;border-radius:3px;background:#fff;"
                   . "box-sizing:border-box;resize:vertical;'></textarea>";

            echo "</div>";

        echo "</div>"; // /inputs card


        // ----------------------------------------------------------
        // ---- Overwrite warning (red, single line) ----
        // ----------------------------------------------------------
        echo "<div style='float:left;width:90%;margin:6px 5% 0;'>";

            echo "<p style='margin:0;font-size:14px;font-weight:600;color:#930000;'>"
               . TEXT_SAVEDATA_OVERWRITE_WARNING . "</p>";

        echo "</div>";


        // ----------------------------------------------------------
        // ---- Math challenge (same pattern as block_verif_deletedata) ----
        // ----------------------------------------------------------
        echo "<div id='save_challenge' style='float:left;width:auto;max-width:340px;
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

            echo "<span id='save_challenge_question'"
               . " style='font-size:15px;font-weight:bold;color:#2c2c2a;'></span>";

            echo "<input type='text' id='save_challenge_answer'"
               . " inputmode='numeric' autocomplete='off'"
               . " style='width:55px;padding:4px 6px;font-size:14px;text-align:center;"
               . "border:1px solid #d4d4d4;border-radius:3px;'>";

            echo "<span id='save_challenge_feedback' style='font-size:14px;font-weight:600;'></span>";

        echo "</div>";


        // ----------------------------------------------------------
        // ---- Buttons (Confirm starts disabled, enabled by challenge) ----
        // ----------------------------------------------------------
        echo "<div style='float:left;width:90%;margin:18px 5% 14px;'>";

            echo "<div style='float:left;width:45%;'>";
                echo "<input type='button' class='button' id='ok_valid_savedata'"
                   . " value='" . TEXT_SAVEDATA_BTN_CONFIRM . "' disabled"
                   . " style='opacity:0.45;cursor:not-allowed;'>";
            echo "</div>";

            echo "<div style='float:left;width:45%;'>";
                echo "<input type='button' id='no_valid_savedata' class='button_close'"
                   . " value='" . TEXT_SAVEDATA_BTN_CANCEL . "'>";
            echo "</div>";

        echo "</div>";

    echo "</div>"; // /cadre_view

echo "</div>"; // /box_verif_savedata
?>


<script type="text/javascript">

    var popup              = document.getElementById('cadre_view');
    var box_verif_savedata = document.getElementById('box_verif_savedata');
    var idSelectChron      = document.getElementById('id_modif_chron');
    var textSelectChron    = document.getElementById('text_modif_chron');

    // Math-challenge elements (same UX as block_verif_deletedata)
    var saveChallengeQuestion = document.getElementById('save_challenge_question');
    var saveChallengeAnswer   = document.getElementById('save_challenge_answer');
    var saveChallengeFeedback = document.getElementById('save_challenge_feedback');
    var okValidSave           = document.getElementById('ok_valid_savedata');

    // Current expected answer (reset every time the popup is opened)
    var saveChallengeExpected = null;


    /**
     * Generate a new random math challenge (+, -, or x).
     * Mirrors generateDeleteChallenge() from block_verif_deletedata.
     */
    function generateSaveChallenge()
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

        saveChallengeExpected             = result;
        saveChallengeQuestion.textContent = a + ' ' + op + ' ' + b + ' = ?';
        saveChallengeAnswer.value         = '';
        saveChallengeFeedback.textContent = '';
        saveChallengeFeedback.style.color = '';
        setSaveConfirmEnabled(false);
    }


    /**
     * Toggle the Confirm button enabled/disabled state.
     */
    function setSaveConfirmEnabled(enabled)
    {
        if (!okValidSave) return;
        okValidSave.disabled      = !enabled;
        okValidSave.style.opacity = enabled ? '1'       : '0.45';
        okValidSave.style.cursor  = enabled ? 'pointer' : 'not-allowed';
    }


    // Live-validate the math answer as the user types
    if (saveChallengeAnswer) {
        saveChallengeAnswer.addEventListener('input', function() {
            var raw = saveChallengeAnswer.value.trim();

            if (raw === '') {
                saveChallengeFeedback.textContent = '';
                setSaveConfirmEnabled(false);
                return;
            }

            var typed = parseInt(raw, 10);

            if (!isNaN(typed) && typed === saveChallengeExpected) {
                saveChallengeFeedback.textContent = '\u2713';
                saveChallengeFeedback.style.color = '#09886d';
                setSaveConfirmEnabled(true);
            } else {
                saveChallengeFeedback.textContent = '\u2717';
                saveChallengeFeedback.style.color = '#930000';
                setSaveConfirmEnabled(false);
            }
        });
    }


    // ----------------------------------------------------------------
    // MutationObserver: regenerate a fresh challenge each time the
    // popup is opened (display goes from 'none' to 'block'). Same
    // pattern as block_verif_deletedata.php.
    // ----------------------------------------------------------------
    if (box_verif_savedata) {
        var lastDisplay = box_verif_savedata.style.display;
        var observer    = new MutationObserver(function(mutations) {
            var current = box_verif_savedata.style.display;
            if (current !== lastDisplay) {
                lastDisplay = current;
                if (current === 'block') {
                    generateSaveChallenge();
                    setTimeout(function() { saveChallengeAnswer.focus(); }, 100);

                    // Make the popup draggable via its title bar.
                    if (typeof initDraggable === 'function') {
                        initDraggable('title_verif_savedata', 'box_verif_savedata');
                    }
                }
            }
        });
        observer.observe(box_verif_savedata, { attributes: true, attributeFilter: ['style'] });
    }


    // Close popup on close-X button, Cancel button, or outside click
    document.addEventListener("click", function(event)
    {
        if (event.target.id === 'button_close_save' || event.target.id === 'no_valid_savedata')
        {
            box_verif_savedata.style.display = "none";
        }

        if (event.target === box_verif_savedata)
        {
            box_verif_savedata.style.display = "none";
        }
    });

    // Close popup on Escape key
    document.addEventListener("keydown", function(event)
    {
        if (event.key === "Escape")
        {
            box_verif_savedata.style.display = "none";
        }
    });

    // Update hidden/text fields when chronicle selection changes
    var selectChron = document.getElementById('select_type_chron');
    if (selectChron) {
        selectChron.addEventListener('change', chooseChron);
        // Pas d'init au chargement : l'option vide (disabled) doit rester
        // active pour forcer un choix explicite. Les champs cachés
        // restent vides tant que l'utilisateur n'a pas sélectionné une
        // chronique d'accueil — ce qui est vérifié à la validation.
    }

    function chooseChron()
    {
        var selectedOption    = selectChron.options[selectChron.selectedIndex];
        idSelectChron.value   = selectedOption.value;
        textSelectChron.value = selectedOption.text;
    }

</script>