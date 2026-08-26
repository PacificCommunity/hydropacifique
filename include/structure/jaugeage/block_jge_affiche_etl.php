<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Rating curve (ETL) graph popup
- Displays the calibration curve linked to the current gauging
- Accessible from data_jge.php
----------------------------------------
*/

// -----------------------------------------------
// Per-user "Swap axes" preference.
// Stored in TABLE_USER_MENU (menu_id='etl_swap_axes') via the shared
// process_menu.php upsert endpoint — SAME key as the Rating Curve module
// (modif_etl.php), so the choice is shared between both modules.
// is_open: 0 = H on X axis (default), 1 = Q on X axis.
// store_result() + explicit (int) cast guard against mysqli state
// conflicts between sequential prepared statements on the same link.
$swap_axes_state = 0;
$sql_swap_state  = "SELECT is_open FROM " . TABLE_USER_MENU . "
                    WHERE id_user = ? AND menu_id = 'etl_swap_axes'";
if ($stmt = $sql_link->prepare($sql_swap_state))
{
    $id_user_int = (int) $id_user;
    $stmt->bind_param("i", $id_user_int);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($swap_axes_state_db);
    if ($stmt->fetch()) { $swap_axes_state = (int) $swap_axes_state_db; }
    $stmt->close();
}

echo "<div id='box_jge_affiche_etl' class='block_view'
            style='position:absolute;width:70%;height:60vh;top:20px;left:10%;background:none;'>\n";

    echo "<div id='cadre_view_2' style='height:100%;padding:0;margin:0;'>\n";

       echo "<p id='title_box_etl'"
           . " style='float:left;width:100%;padding:15px 0;font-size:16px;font-weight:bold;"
           . "color:#000;background-color:#f5f5f5;'>";
            echo "<span style='margin-left:15px;'>" . TEXT_JGE_ETL_BOX_TITLE . "</span>";

            // Close button (far right)
            echo "<span id='button_close_etl' style='float:right;margin-right:15px;cursor:pointer;' title='" . TEXT_JGE_ETL_CLOSE . "'>X</span>";

        echo "</p>\n";

        echo "<div id='cadre_limit' style='flex:1;overflow:auto;margin-top:10px;display:flex;flex-direction:column;'>";

            echo "<div id='info_etl' style='padding:0 10px;margin-bottom:5px;'></div>\n";

            echo "<div id='plot_etl'
                        style='flex:1;margin:15px 10px;margin-top:5px;padding:10px;
                               background-color:#fff;border:1px solid #000;border-radius:4px;'></div>\n";

            // Swap-axes toggle — bottom-left, under the graph.
            echo "<div style='padding:0 10px 8px 12px;'>";
                echo "<label style='cursor:pointer;font-size:13px;color:#333;'>";
                    echo "<input type='checkbox' id='swap_axes_jge' "
                       . ($swap_axes_state == 1 ? "checked" : "")
                       . " style='vertical-align:middle;margin-right:5px;'>";
                    echo TEXT_ET_OPT_SWAP;
                echo "</label>";
            echo "</div>\n";

            echo "<div id='wait_graph' style='width:100%;height:65px;margin-top:150px;text-align:center;'>";
                echo "<img src='" . DIR_WS_IMG . "wait.gif' style='width:50px;'>";
                echo "<p>" . TEXT_JGE_ETL_LOADING . "</p>";
            echo "</div>\n";

        echo "</div>\n";

    echo "</div>\n";

echo "</div>\n";
?>

<script type="text/javascript">

    var popupETL = document.getElementById('cadre_view_2');
    var boxETL   = document.getElementById('box_jge_affiche_etl');
    var bClose   = document.getElementById('button_close_etl');

    var plotDiv = document.getElementById('plot_etl');

    var resizeObserver = new ResizeObserver(function() {
        if (plotDiv.data) {
            Plotly.relayout(plotDiv, { autosize: true });
        }
    });

    resizeObserver.observe(plotDiv);

    document.addEventListener("click", function(event)
    {
        if (event.target === bClose)
        {
            boxETL.style.display = "none";
        }

        // Close on outside click (not on inner panel)
        if (event.target !== popupETL && event.target === boxETL)
        {
            boxETL.style.display = "none";
        }
    });

    document.addEventListener("keydown", function(event)
    {
        if (event.key === "Escape") { boxETL.style.display = "none"; }
    });


    // -----------------------------------------------
    // Swap-axes toggle: redraws the curve with axes swapped and persists
    // the user's choice.
    //
    // The graph is built server-side (process_jge_etlgraph.php), so swapping
    // means re-fetching: afficheETL() reads the checkbox and sends it as
    // swapAxes, and the server returns the trace already oriented.
    // Persistence uses the shared process_menu.php endpoint under the SAME
    // key as the Rating Curve module (TABLE_USER_MENU, menu_id='etl_swap_axes'),
    // so the preference is shared between the gauging popup and modif_etl.php.
    // is_open: 1 = Q on X, 0 = H on X. The initial checkbox state is restored
    // server-side from that shared preference above.
    (function() {
        var swapCb = document.getElementById('swap_axes_jge');
        if (!swapCb) return;

        swapCb.addEventListener('change', function() {
            // 1) Redraw via the existing loader (re-fetch with the new orientation)
            if (typeof afficheETL === 'function') { afficheETL(); }

            // 2) Persist the choice (shared key with the Rating Curve module)
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'include/structure/box/process_menu.php', true);
            xhr.setRequestHeader('Content-Type', 'application/json');
            xhr.send(JSON.stringify({
                id_user: <?php echo json_encode((int) $id_user); ?>,
                menu_id: 'etl_swap_axes',
                is_open: swapCb.checked ? 1 : 0
            }));
        });
    })();


</script>