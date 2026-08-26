<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Editable propeller velocity-equations popup
- Included once by form_eq_jge_helices.php
- Single reusable popup: opened for a given propeller row (or the new-entry
  row, index 0) via openHeliceEq(id).
- Presents the 3 velocity tiers as EDITABLE inputs (clearer than the raw
  l1/a1/b1... table columns).
- The real form fields (helice_l1_<id> ... helice_b3_<id>) stay in the main
  form as hidden inputs and are filled in/out by JS — so the global Save
  (process_dataeqjge_save.php) keeps working unchanged.
----------------------------------------
*/

// Highlight style for a row whose equations were edited but not yet saved
// (the amber left border + tint persists until the global Save reloads the page).
echo "<style>
    #table_tri tr.eqh-unsaved td { background-color:#fff8e6 !important; }
    #table_tri tr.eqh-unsaved td:first-child { box-shadow: inset 4px 0 0 #d08700; }
</style>\n";

// Full-screen dark overlay: dims the page, blocks interaction behind, and
// centers the popup. Being fixed + flex-centered, the popup automatically
// stays centered and fits the window when it is resized (no dragging).
echo "<div id='overlay_eq_helice'
            style='display:none;position:fixed;top:0;left:0;width:100%;height:100%;
                   background:rgba(0,0,0,0.45);z-index:2000;
                   align-items:flex-start;justify-content:center;'>\n";

    echo "<div id='box_eq_helice' class='block_view'
                style='position:relative;width:90%;max-width:650px;max-height:90vh;
                        margin-top:5vh;background:none;
                        display:flex;flex-direction:column;overflow:hidden;'>\n";

        echo "<div id='cadre_view_2' style='display:flex;flex-direction:column;flex:1;overflow:hidden;padding:0;margin:0;'>\n";

            // ---- Header (teal green, distinct) ----
            echo "<p id='title_box_eq_helice'
                    style='float:left;width:100%;padding:15px 0;margin:0;
                           font-size:16px;font-weight:bold;color:#fff !important;background-color:#0f6e56 !important;
                           flex-shrink:0;'>";
                echo "<span style='margin-left:15px;'>" . TEXT_EJ_HEL_EQ_TITLE . "</span>";
                echo "<span id='name_eq_helice_box' style='margin-left:8px;font-weight:bold;'></span>";
                echo "<span id='button_close_eq_helice' style='float:right;margin-right:15px;cursor:pointer;' title='" . TEXT_EJ_HEL_EQ_CLOSE . "'>X</span>";
            echo "</p>\n";

            // Hidden marker: which propeller id this popup is currently editing
            echo "<input type='hidden' id='eqh_current_id' value=''>";

        echo "<div style='flex:1;overflow:auto;padding:18px 22px;box-sizing:border-box;'>\n";

            echo "<h2 style='margin:0 0 12px 0;color:#000;font-size:16px;'>" . TEXT_EJ_HEL_EQ_SUBTITLE . "</h2>\n";

            // Color code (matching the gauging popup) applied to INPUT BORDERS,
            // since the values here are editable fields, not static text:
            //   bound inputs (l1, l2 + read-only l1_inf/l2_inf) -> orange
            //   first coefficient input (a* = slope, the "k") -> red
            //   second coefficient input (b* = constant, the "a") -> green
            // Structural symbols (v, n, operators) are enlarged and black.
            $base_inp = "width:90px;padding:4px;font-size:15px;text-align:center;box-sizing:border-box;font-weight:bold;";

            $eq_bound = "style='" . $base_inp . "border:2px solid #ff6d00;'";                       // l1 / l2 (orange)
            $eq_boundro = "style='" . $base_inp . "border:2px solid #ff6d00;background:#f0f0f0;'";   // l1_inf / l2_inf (orange, read-only)
            $eq_a     = "style='" . $base_inp . "border:2px solid #930000;color:#930000;'";          // a* slope = k (red)
            $eq_b     = "style='" . $base_inp . "border:2px solid #0f6e56;color:#0f6e56;'";          // b* constant = a (green)

            $eq_sym   = "style='font-weight:bold;font-size:20px;color:#000;'";                       // v, n
            $eq_op    = "style='font-weight:bold;font-size:18px;color:#000;'";                       // <=, <, =, *, +
            $eq_cell  = "style='padding:6px 4px;text-align:center;'";

            // Light vertical rule between the n-interval (left) and the equation
            // (right). Applied as a LEFT border on the "v =" column, which is the
            // same boundary on all three rows -> the rule stays vertically aligned.
            $eq_sep   = "style='padding:6px 4px;text-align:center;width:45px;border-left:1px solid #bbb;'";

            echo "<table style='border-collapse:collapse;table-layout:fixed;'>";

                // Fixed column widths -> the 3 rows align vertically
                echo "<colgroup>";
                    echo "<col style='width:100px;'>"; // range start (l1_inf / l2_inf)
                    echo "<col style='width:70px;'>";  // comparison sign
                    echo "<col style='width:100px;'>"; // l value (l1 / l2)
                    echo "<col style='width:55px;'>";  // v =
                    echo "<col style='width:100px;'>"; // a value
                    echo "<col style='width:45px;'>";  // * n +
                    echo "<col style='width:100px;'>"; // b value
                echo "</colgroup>";

                // Row 1: n <= l1  ->  v = a1 * n + b1
                echo "<tr>";
                    echo "<td " . $eq_cell . "><span " . $eq_sym . ">n</span> <span " . $eq_op . ">" . TEXT_JGE_HELICE_N_LTE . "</span></td>";
                    echo "<td " . $eq_cell . ">&nbsp;</td>";
                    echo "<td " . $eq_cell . "><input type='text' id='eqh_l1' " . $eq_bound . "></td>";
                    echo "<td " . $eq_sep  . "><span " . $eq_sym . ">v</span> <span " . $eq_op . ">=</span></td>";
                    echo "<td " . $eq_cell . "><input type='text' id='eqh_a1' " . $eq_a . "></td>";
                    echo "<td " . $eq_cell . "><span " . $eq_op . ">" . TEXT_JGE_HELICE_MULT_N_PRE . "</span> <span " . $eq_sym . ">n</span> <span " . $eq_op . ">+</span></td>";
                    echo "<td " . $eq_cell . "><input type='text' id='eqh_b1' " . $eq_b . "></td>";
                echo "</tr>";

                // Row 2: l1 < n <= l2  ->  v = a2 * n + b2
                echo "<tr>";
                    echo "<td " . $eq_cell . "><input type='text' id='eqh_l1_inf' " . $eq_boundro . " readonly></td>";
                    echo "<td " . $eq_cell . "><span " . $eq_op . ">&lt;</span> <span " . $eq_sym . ">n</span> <span " . $eq_op . ">&lt;=</span></td>";
                    echo "<td " . $eq_cell . "><input type='text' id='eqh_l2' " . $eq_bound . "></td>";
                    echo "<td " . $eq_sep  . "><span " . $eq_sym . ">v</span> <span " . $eq_op . ">=</span></td>";
                    echo "<td " . $eq_cell . "><input type='text' id='eqh_a2' " . $eq_a . "></td>";
                    echo "<td " . $eq_cell . "><span " . $eq_op . ">" . TEXT_JGE_HELICE_MULT_N_PRE . "</span> <span " . $eq_sym . ">n</span> <span " . $eq_op . ">+</span></td>";
                    echo "<td " . $eq_cell . "><input type='text' id='eqh_b2' " . $eq_b . "></td>";
                echo "</tr>";

                // Row 3: n > l2  ->  v = a3 * n + b3
                echo "<tr>";
                    echo "<td " . $eq_cell . "><input type='text' id='eqh_l2_inf' " . $eq_boundro . " readonly></td>";
                    echo "<td " . $eq_cell . "><span " . $eq_op . ">" . TEXT_JGE_HELICE_N_GT . "</span> <span " . $eq_sym . ">n</span></td>";
                    echo "<td " . $eq_cell . ">&nbsp;</td>";
                    echo "<td " . $eq_sep  . "><span " . $eq_sym . ">v</span> <span " . $eq_op . ">=</span></td>";
                    echo "<td " . $eq_cell . "><input type='text' id='eqh_a3' " . $eq_a . "></td>";
                    echo "<td " . $eq_cell . "><span " . $eq_op . ">" . TEXT_JGE_HELICE_MULT_N_PRE . "</span> <span " . $eq_sym . ">n</span> <span " . $eq_op . ">+</span></td>";
                    echo "<td " . $eq_cell . "><input type='text' id='eqh_b3' " . $eq_b . "></td>";
                echo "</tr>";

            echo "</table>";

            echo "<hr style='margin:14px 0;'>";

            // ---- Calibration formula reminder ----
            // Same color code as the borders/symbols: v black, n orange,
            // k red (slope), a green (intercept).
            $var_v     = "font-weight:bold;color:#000;";
            $var_n     = "font-weight:bold;color:#ff6d00;";
            $var_red   = "font-weight:bold;color:#930000;";
            $var_green = "font-weight:bold;color:#0f6e56;";

            echo "<h2 style='margin:0 0 8px 0;color:#000;font-size:15px;'>";
                echo TEXT_JGE_HELICE_FORMULA_TITLE;
                echo "<br>";
                echo "<span style='font-size:20px;'>";
                    echo "<span style='" . $var_v     . "'>v</span> = ";
                    echo "<span style='" . $var_red   . "'>k</span> * ";
                    echo "<span style='" . $var_n     . "'>n</span> + ";
                    echo "<span style='" . $var_green . "'>a</span>";
                echo "</span>";
            echo "</h2>\n";

            echo "<div style='float:left;width:320px;padding:6px;border:1.5px solid #000;box-sizing:border-box;'>\n";
                echo "<table style='width:100%;'>";
                    echo "<tr style='height:18px;'><td style='font-size:16px;'><span style='" . $var_v     . "'>v</span>" . TEXT_JGE_HELICE_VAR_V . "</td></tr>";
                    echo "<tr style='height:18px;'><td style='font-size:16px;'><span style='" . $var_n     . "'>n</span>" . TEXT_JGE_HELICE_VAR_N . "</td></tr>";
                    echo "<tr style='height:18px;'><td style='font-size:16px;'><span style='" . $var_red   . "'>k</span>" . TEXT_JGE_HELICE_VAR_K . "</td></tr>";
                    echo "<tr style='height:18px;'><td style='font-size:16px;'><span style='" . $var_green . "'>a</span>" . TEXT_JGE_HELICE_VAR_A . "</td></tr>";
                echo "</table>";
            echo "</div>\n";

            // ---- Action buttons ----
            echo "<div style='float:right;margin-top:68px;text-align:right;'>";
                echo "<input type='button' id='cancel_eq_helice' class='button_close' value='" . TEXT_EJ_HEL_EQ_CANCEL . "' style='margin-right:8px;'>";
                echo "<input type='button' id='ok_eq_helice'     class='button'       value='" . TEXT_EJ_HEL_EQ_OK     . "'>";
            echo "</div>";

        echo "</div>\n";

    echo "</div>\n";

echo "</div>\n"; // box_eq_helice

echo "</div>\n"; // overlay_eq_helice
?>

<script type="text/javascript">

    // -----------------------------------------------
    // Editable propeller-equations popup logic
    // The 8 coefficient fields live in the main form as hidden inputs named
    // helice_<coef>_<id>. This popup loads them into working inputs on open,
    // and writes them back on OK. No change to the save process.

    var overlayEqHelice = document.getElementById('overlay_eq_helice');
    var boxEqHelice    = document.getElementById('box_eq_helice');
    var eqhCurrentId   = document.getElementById('eqh_current_id');
    var nameEqHelice   = document.getElementById('name_eq_helice_box');

    // Coefficient keys handled by the popup
    var EQH_COEFS = ['l1', 'a1', 'b1', 'l2', 'a2', 'b2', 'a3', 'b3'];


    // Open the popup for a given propeller id (0 = new-entry row)
    function openHeliceEq(id)
    {
        eqhCurrentId.value = id;

        // Load each coefficient from its hidden form field into the popup input
        EQH_COEFS.forEach(function(c)
        {
            var hidden = document.getElementById('helice_' + c + '_' + id);
            var input  = document.getElementById('eqh_' + c);
            if (input) { input.value = hidden ? hidden.value : ''; }
        });

        // Mirror l1/l2 into the read-only "range start" cells of rows 2 and 3
        syncEqRanges();

        // Show the propeller number next to the title (from the row's num field)
        var numField = document.getElementById('helice_num_' + id);
        nameEqHelice.textContent = (numField && numField.value) ? numField.value : '';

        // Show the overlay (flex-centered -> popup adapts to window size)
        overlayEqHelice.style.display = 'flex';
    }

    // Keep the read-only range-start cells (l1_inf, l2_inf) in sync with l1/l2
    function syncEqRanges()
    {
        var l1 = document.getElementById('eqh_l1').value;
        var l2 = document.getElementById('eqh_l2').value;
        document.getElementById('eqh_l1_inf').value = l1;
        document.getElementById('eqh_l2_inf').value = l2;
    }

    document.getElementById('eqh_l1').addEventListener('input', syncEqRanges);
    document.getElementById('eqh_l2').addEventListener('input', syncEqRanges);


    // Close without saving
    function closeHeliceEq()
    {
        overlayEqHelice.style.display = 'none';
    }

    // Confirm: write popup values back into the hidden form fields
    function confirmHeliceEq()
    {
        var id = eqhCurrentId.value;

        EQH_COEFS.forEach(function(c)
        {
            var hidden = document.getElementById('helice_' + c + '_' + id);
            var input  = document.getElementById('eqh_' + c);
            if (hidden && input) { hidden.value = input.value; }
        });

        // Mark the row as having unsaved changes (visual cue). The row stays
        // highlighted until the global Save runs (which reloads the page).
        var row = document.getElementById('row_eqh_' + id);
        if (row) { row.classList.add('eqh-unsaved'); }

        // Remind the user that the change is not persisted until the global Save.
        var info = document.getElementById('contenu_info');
        if (info)
        {
            info.innerHTML     = '<?= TEXT_EJ_HEL_EQ_SAVE_REMINDER ?>';
            info.style.display = 'block';
            info.style.border  = '2px solid #d08700'; // amber: action still required
        }

        closeHeliceEq();
    }

    document.getElementById('ok_eq_helice').addEventListener('click', confirmHeliceEq);
    document.getElementById('cancel_eq_helice').addEventListener('click', closeHeliceEq);

    document.addEventListener('click', function(event)
    {
        // Close only via the X button (Cancel and Escape are handled elsewhere).
        // Clicking the dark overlay does NOT close the popup, to avoid losing
        // edits by an accidental click outside.
        if (event.target.id === 'button_close_eq_helice')
        {
            closeHeliceEq();
        }
    });

    document.addEventListener('keydown', function(event)
    {
        if (event.key === 'Escape' && overlayEqHelice.style.display === 'flex') { closeHeliceEq(); }
    });

</script>