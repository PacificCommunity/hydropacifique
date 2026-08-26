<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Helical flowmeter equation popup
- Displays the helical velocity equation coefficients
  for the selected flowmeter on a gauging arm
----------------------------------------
*/

echo "<div id='box_jge_helice' class='block_view'
            style='position:absolute;width:660px;height:470px;top:75px;left:35%;background:none;
                    display:none;flex-direction:column;overflow:hidden;z-index:1600;'>\n";

    echo "<div id='cadre_view_2' style='display:flex;flex-direction:column;flex:1;overflow:hidden;padding:0;margin:0;'>\n";

        echo "<p id='title_box_helice'"
                . " style='flex-shrink:0;width:100%;padding:15px 0;margin:0;"
                . "font-size:16px;font-weight:bold;color:#fff !important;background-color:#0f6e56 !important;"
                . "box-sizing:border-box;'>";

            echo "<span style='font-size:18px;font-weight:bold;margin-left:5px;'>";
                echo TEXT_JGE_HELICE_TITLE;
            echo "</span>";

            echo "<span id='name_helice_box' style='font-size:18px;font-weight:bold;margin-left:5px;'></span>";

            echo "<span id='button_close_helice' style='float:right;font-size:18px;font-weight:bold;margin-right:15px;cursor:pointer;' title='" . TEXT_JGE_HELICE_CLOSE . "'>X</span>";

        echo "</p>\n";


        echo "<div style='flex:1;overflow:auto;padding:15px 20px 25px 20px;box-sizing:border-box;'>\n";

            // ---- Velocity equation coefficients table ----
            echo "<h2 style='margin:0 0 8px 0;color:#000;font-size:16px;'>" . TEXT_JGE_HELICE_EQ_TITLE . "</h2>\n";

            echo "<div style='width:600px;padding:8px;border:1px solid #000;box-sizing:border-box;margin-bottom:10px;'>\n";

                echo "<table style='width:600px;table-layout:fixed;border-collapse:collapse;'>";

                    // Enlarged, neutral structural symbols (v, n, operators).
                    // Color code applied to the NUMBERS:
                    //   bounds (l1, l2 — the limits of n) -> flash orange
                    //   slope coefficient (k)             -> red
                    //   intercept coefficient (a)         -> green
                    // Symbols v, n and operators stay black.
                    $eq_v     = "font-weight:bold;font-size:20px;color:#000;";              // v
                    $eq_n     = "font-weight:bold;font-size:20px;color:#000;";              // n
                    $eq_op    = "font-weight:bold;font-size:18px;color:#000;";              // <=, <, =, *, +
                    $coef_k   = "style='width:100%;padding:2px;font-size:18px;font-weight:bold;color:#930000;border:0;background:none;box-sizing:border-box;text-align:center;'"; // k (slope, red)
                    $coef_a   = "style='width:100%;padding:2px;font-size:18px;font-weight:bold;color:#0f6e56;border:0;background:none;box-sizing:border-box;text-align:center;'"; // a (intercept, green)
                    $bound    = "style='width:100%;padding:2px;font-size:18px;font-weight:bold;color:#ff6d00;border:0;background:none;box-sizing:border-box;text-align:center;'"; // l1 / l2 bounds (flash orange)

                    // Light vertical rule separating the n-interval (left) from
                    // the velocity equation (right). Applied as a right border on
                    // the spacer column that sits just before "v =".
                    $sep = "style='width:3%;border-right:1px solid #bbb;'";

                    // Row 1: n <= l1  →  v = a1 * n + b1
                    echo "<tr style='height:34px;'>";
                        echo "<td style='width:10%;'>&nbsp;</td>";
                        echo "<td style='width:12%;text-align:center;'><span style='" . $eq_n . "'>n</span> <span style='" . $eq_op . "'>" . TEXT_JGE_HELICE_N_LTE . "</span></td>";
                        echo "<td style='width:12%;'><input type='text' " . $bound . " name='l1_bras' id='l1_bras' value='' readonly></td>";
                        echo "<td " . $sep . ">&nbsp;</td>";
                        echo "<td style='width:11%;text-align:center;'><span style='" . $eq_v . "'>v</span> <span style='" . $eq_op . "'>=</span></td>";
                        echo "<td style='width:18%;'><input type='text' " . $coef_k . " name='a1_bras' id='a1_bras' value='' readonly></td>";
                        echo "<td style='width:12%;text-align:center;'><span style='" . $eq_op . "'>" . TEXT_JGE_HELICE_MULT_N_PRE . "</span> <span style='" . $eq_n . "'>n</span> <span style='" . $eq_op . "'>+</span></td>";
                        echo "<td style='width:18%;'><input type='text' " . $coef_a . " name='b1_bras' id='b1_bras' value='' readonly></td>";
                    echo "</tr>";

                    // Row 2: l1_inf <= n <= l2  →  v = a2 * n + b2
                    echo "<tr style='height:34px;'>";
                        echo "<td><input type='text' " . $bound . " name='l1_inf_bras' id='l1_inf_bras' value='' readonly></td>";
                        echo "<td style='text-align:center;'><span style='" . $eq_op . "'><span id='lsign_pre' style='" . $eq_op . "'></span> <span style='" . $eq_n . "'>n</span> <span id='lsign' style='" . $eq_op . "'></span></span></td>";
                        echo "<td><input type='text' " . $bound . " name='l2_bras' id='l2_bras' value='' readonly></td>";
                        echo "<td " . $sep . ">&nbsp;</td>";
                        echo "<td style='text-align:center;'><span style='" . $eq_v . "'>v</span> <span style='" . $eq_op . "'>=</span></td>";
                        echo "<td><input type='text' " . $coef_k . " name='a2_bras' id='a2_bras' value='' readonly></td>";
                        echo "<td style='text-align:center;'><span style='" . $eq_op . "'>" . TEXT_JGE_HELICE_MULT_N_PRE . "</span> <span style='" . $eq_n . "'>n</span> <span style='" . $eq_op . "'>+</span></td>";
                        echo "<td><input type='text' " . $coef_a . " name='b2_bras' id='b2_bras' value='' readonly></td>";
                    echo "</tr>";

                    // Row 3 (hidden): l2_inf < n  →  v = a3 * n + b3
                    echo "<tr id='hidden_helice' style='visibility:hidden;height:34px;'>";
                        echo "<td><input type='text' " . $bound . " name='l2_inf_bras' id='l2_inf_bras' value='' readonly></td>";
                        echo "<td style='text-align:center;'><span style='" . $eq_op . "'>" . TEXT_JGE_HELICE_N_GT . "</span> <span style='" . $eq_n . "'>n</span></td>";
                        echo "<td>&nbsp;</td>";
                        echo "<td " . $sep . ">&nbsp;</td>";
                        echo "<td style='text-align:center;'><span style='" . $eq_v . "'>v</span> <span style='" . $eq_op . "'>=</span></td>";
                        echo "<td><input type='text' " . $coef_k . " name='a3_bras' id='a3_bras' value='' readonly></td>";
                        echo "<td style='text-align:center;'><span style='" . $eq_op . "'>" . TEXT_JGE_HELICE_MULT_N_PRE . "</span> <span style='" . $eq_n . "'>n</span> <span style='" . $eq_op . "'>+</span></td>";
                        echo "<td><input type='text' " . $coef_a . " name='b3_bras' id='b3_bras' value='' readonly></td>";
                    echo "</tr>";

                echo "</table>";

            echo "</div>\n";

            echo "<hr style='margin:10px 0;'>";

            // ---- Calibration formula info box ----
            // In the formula/legend, only the COEFFICIENT letters carry color:
            //   k -> red (slope)   a -> green (intercept).
            // v and n are symbols (kept black); orange is reserved for the
            // numeric bounds of n in the equation table above.
            $var_v     = "font-weight:bold;color:#000;";
            $var_n     = "font-weight:bold;color:#ff6d00;";
            $var_red   = "font-weight:bold;color:#930000;";
            $var_green = "font-weight:bold;color:#0f6e56;";

            echo "<h2 style='margin:0 0 8px 0;color:#000;font-size:16px;'>";
                echo TEXT_JGE_HELICE_FORMULA_TITLE;
                echo "<br>";
                echo "<span style='font-size:20px;'>";
                    echo "<span style='" . $var_v     . "'>v</span> = ";
                    echo "<span style='" . $var_red   . "'>k</span> * ";
                    echo "<span style='" . $var_n     . "'>n</span> + ";
                    echo "<span style='" . $var_green . "'>a</span>";
                echo "</span>";
            echo "</h2>\n";

            echo "<div style='width:300px;padding:5px;border:1.5px solid #000;box-sizing:border-box;'>\n";

                echo "<table style='width:100%;'>";
                    echo "<tr style='height:18px;'><td style='font-size:16px;'><span style='" . $var_v     . "'>v</span>" . TEXT_JGE_HELICE_VAR_V . "</td></tr>";
                    echo "<tr style='height:18px;'><td style='font-size:16px;'><span style='" . $var_n     . "'>n</span>" . TEXT_JGE_HELICE_VAR_N . "</td></tr>";
                    echo "<tr style='height:18px;'><td style='font-size:16px;'><span style='" . $var_red   . "'>k</span>" . TEXT_JGE_HELICE_VAR_K . "</td></tr>";
                    echo "<tr style='height:18px;'><td style='font-size:16px;'><span style='" . $var_green . "'>a</span>" . TEXT_JGE_HELICE_VAR_A . "</td></tr>";
                echo "</table>";

            echo "</div>\n";

        echo "</div>\n";

    echo "</div>\n";

echo "</div>\n";
?>

<script>

    var boxJgeHelice = document.getElementById('box_jge_helice');

    // Close only the helice popup. Clicking its own close button or its
    // backdrop must not bubble to the points-popup handlers (which would
    // otherwise close box_jge_pts underneath).
    document.addEventListener("click", function(event)
    {
        if (event.target.id === 'button_close_helice' || event.target === boxJgeHelice)
        {
            boxJgeHelice.style.display = "none";
            event.stopPropagation();
        }
    });

    // Escape: if the helice popup is open, close it first and swallow the
    // event so the points popup below stays open.
    document.addEventListener("keydown", function(event)
    {
        if (event.key === "Escape" && boxJgeHelice.style.display === 'block')
        {
            boxJgeHelice.style.display = "none";
            event.stopPropagation();
        }
    }, true);

</script>