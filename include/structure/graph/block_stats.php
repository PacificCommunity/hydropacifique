<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Stats panel - Modal layout
- Displays the statistics popup window when the user clicks
  an information link on the index.php page
- Contains the menu sidebar, content zones, and a loading spinner
----------------------------------------
*/

// -----------------------------------------------
// Outer modal overlay container

echo "<div id='box_stats' class='block_view' style=''>\n";

    // Inner centered content wrapper
    echo "<div id='cadre_view_2' class='stats-modal' style='float:left;width:90%;margin:3% 5%;padding:0;'>\n";

        // Header bar: icon + title on the left, close button on the right
        echo "<div id='title_info_chron_stats' class='stats-head' style='background:#2f3a44 !important;'>";

            echo "<div class='stats-head-left'>";
                echo "<div class='stats-head-ico'>"
                   . "<svg width='20' height='20' viewBox='0 0 24 24' fill='none' stroke='#fff' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'>"
                   . "<path d='M3 3v18h18'/><rect x='7' y='12' width='3' height='6'/><rect x='12' y='8' width='3' height='10'/><rect x='17' y='4' width='3' height='14'/>"
                   . "</svg>"
                   . "</div>";
                echo "<div>";
                    echo "<div class='stats-head-title'>" . TEXT_STATS_TITLE . "</div>";
                    echo "<div id='title_box' class='stats-head-sub'></div>";
                echo "</div>";
            echo "</div>";

            echo "<span id='button_close' class='stats-head-close' title='" . TEXT_STATS_CLOSE . "'>&times;</span>";

        echo "</div>\n";

        // Main body: sidebar menu + scrollable content area
        echo "<div id='cadre_limit' class='stats-body'>";

            // Left sidebar: navigation buttons injected dynamically via JS
            echo "<div id='menu_stats' class='stats-menu'>";
            echo "</div>";

            // Right content area: scrollable, flex-grows to fill remaining space
            echo "<div id='cadre_stats' class='stats-content'>";

                // General statistics summary block (period, station, data type)
                echo "<div id='general_stats' class='content_stats' style='margin-top:0;padding-bottom:0;'>";
                echo "</div>";

                // Graph rendering zone (Plotly charts injected here)
                echo "<div id='contenu_stats_graph' class='content_stats'>";
                echo "</div>";

                // Tabular / computed statistics content zone
                echo "<div id='contenu_stats' class='content_stats'>";
                echo "</div>";

                // Loading spinner — shown while async data is being fetched
                echo "<div id='cadre_wait_stats' class='content_stats'>";
                    echo "<div style='width:100%;margin-bottom:10px;text-align:center;'>";
                        echo "<div class='spinner' style='width:50px; height:50px;margin:10% auto 0;'></div>";
                    echo "</div>";
                echo "</div>";

            echo "</div>"; // #cadre_stats

        echo "</div>\n"; // #cadre_limit

    echo "</div>\n"; // #cadre_view_2

echo "</div>\n"; // #box_stats
?>

<style>
    /* ===== Stats modal — modern dashboard look =====
       Selectors are scoped to #cadre_view_2 so they out-specify the base
       button.bstats / .content_stats / p.info_stats rules in formulaire.css
       and stats.css without needing !important. */
    #cadre_view_2.stats-modal {
        background:#fff;
        border-radius:14px;
        overflow:hidden;
        box-shadow:0 18px 50px rgba(0,0,0,.30);
        font-family:'Open Sans', Arial, sans-serif;
        box-sizing:border-box;
    }

    /* Header */
    #cadre_view_2 .stats-head {
        display:flex; align-items:center; justify-content:space-between;
        padding:16px 22px; background:#2f3a44; color:#fff;
    }
    #cadre_view_2 .stats-head-left { display:flex; align-items:center; gap:12px; }
    #cadre_view_2 .stats-head-ico {
        width:38px; height:38px; border-radius:9px; background:#3c8da5;
        display:flex; align-items:center; justify-content:center;
        flex:0 0 auto;
    }
    #cadre_view_2 .stats-head-title { font-size:19px; font-weight:bold; line-height:1.2; }
    #cadre_view_2 .stats-head-sub   { font-size:12px; opacity:.75; margin-top:2px; }
    #cadre_view_2 .stats-head-close {
        font-size:24px; line-height:1; cursor:pointer; opacity:.8; padding:0 4px;
    }
    #cadre_view_2 .stats-head-close:hover { opacity:1; }

    /* Body layout */
    #cadre_view_2 .stats-body {
        width:100%; padding:0; height:78vh;
        display:flex; overflow:hidden; box-sizing:border-box;
    }

    /* Sidebar menu */
    #cadre_view_2 .stats-menu {
        width:210px; flex:0 0 auto; padding:16px 12px;
        background:#f6f8fa; border-right:0.5px solid #e6e6e6;
        overflow-y:auto; box-sizing:border-box;
    }
    /* Menu buttons — override the base button.bstats look entirely */
    #cadre_view_2 button.bstats,
    #cadre_view_2 button.bstats-adv {
        display:flex; align-items:center; justify-content:flex-start; gap:9px;
        width:100%; margin:0 0 7px 0; padding:10px 12px;
        text-align:left; font-size:13px; font-weight:500;
        border:0; border-radius:9px; box-shadow:none;
        background:transparent; cursor:pointer;
        font-family:'Open Sans', Arial, sans-serif;
        transition:background .12s, color .12s;
    }
    #cadre_view_2 button.bstats { color:#3a4750; }
    #cadre_view_2 button.bstats:hover { background:#e9eef2; color:#3a4750; }
    #cadre_view_2 button.bstats.active { background:#3c8da5; color:#fff; }

    #cadre_view_2 button.bstats-adv {
        color:#993c1d; background:#faece7; margin-top:12px;
    }
    #cadre_view_2 button.bstats-adv:hover { background:#f5ddd2; color:#993c1d; }
    #cadre_view_2 button.bstats-adv.active { background:#d85a30; color:#fff; }

    /* Content area */
    #cadre_view_2 .stats-content {
        flex:1 1 auto; padding:20px 24px; height:100%;
        overflow-y:auto; background:#fff; box-sizing:border-box;
    }
    /* Neutralise the floating white card / drop shadow from stats.css */
    #cadre_view_2 .content_stats {
        width:100%; margin:0 0 18px 0; padding:0;
        background:transparent; border-radius:0; box-shadow:none;
    }

    /* Section titles (out-specify p.info_stats) */
    #cadre_view_2 .info_stats,
    #cadre_view_2 p.info_stats {
        display:flex; align-items:center; gap:8px;
        font-size:13px; color:#3c8da5; font-weight:bold;
        margin:0 0 10px 0;
    }

    /* Summary metric cards */
    #cadre_view_2 .stats-cards {
        display:grid; grid-template-columns:repeat(auto-fit, minmax(150px,1fr));
        gap:14px; margin-bottom:8px;
    }
    #cadre_view_2 .stats-card {
        border:0.5px solid #e6e6e6; border-radius:10px; padding:14px;
    }
    #cadre_view_2 .stats-card-label { font-size:12px; color:#8a8a85; }
    #cadre_view_2 .stats-card-value { font-size:18px; font-weight:500; margin-top:5px; color:#2c2c2a; }

    /* Tables */
    #cadre_view_2 #table_tri {
        width:100%; border-collapse:collapse; font-size:13px;
    }
    #cadre_view_2 #table_tri th {
        text-align:center; padding:9px 10px;
        border-bottom:2px solid #3c8da5; color:#3a4750; font-weight:bold;
        background:#fff;
    }
    #cadre_view_2 #table_tri td { padding:8px 10px; border-bottom:1px solid #eee; }
    #cadre_view_2 #table_tri .row1 { background:#fff; }
    #cadre_view_2 #table_tri .row2 { background:#f6fafb; }
    #cadre_view_2 #table_tri .row1hover,
    #cadre_view_2 #table_tri .row2hover { background:#eaf3f6; }

    #cadre_view_2 .graph_stats { width:100%; }
</style>

<script>

    // -----------------------------------------------
    // Inject PHP language constants into JS global scope
    // (allows translated strings to be used in dynamic JS interactions)
    const LANG_STATS = {
        close: "<?= TEXT_STATS_CLOSE ?>"
    };

    // -----------------------------------------------
    // Close modal on click:
    // - on the close button (#button_close)
    // - on the overlay background (#box_stats itself)
    document.addEventListener("click", function(event)
    {
        if (event.target.id === 'button_close' || event.target === boxStats)
        {
            boxStats.style.display = "none";
        }
    });

    // -----------------------------------------------
    // Close modal when the Escape key is pressed
    document.addEventListener("keydown", function(event)
    {
        if (event.key === "Escape")
        {
            boxStats.style.display = "none";
        }
    });

    // -----------------------------------------------
    // Toggle the visibility of a specific Plotly trace
    // Used by metric checkboxes in the low-flow stats panel (stats_chron_lowflow)
    //
    // @param {string} plotId   - ID of the target Plotly chart div
    // @param {string} traceId  - Suffix used to find the checkbox (#checkMetrique_<traceId>)
    function toggleTraceVisibility(plotId, traceId)
    {
        const idCheckMet = document.getElementById('checkMetrique_' + traceId);
        const traceName  = idCheckMet.getAttribute('data-trace-id');
        const visibility = idCheckMet.checked ? true : false;

        const graphDiv = document.getElementById(plotId);
        const data     = graphDiv.data;

        // Find the index of the matching Plotly trace by its 'name' attribute
        let traceIndex = -1;
        for (let i = 0; i < data.length; i++)
        {
            if (data[i].name === traceName)
            {
                traceIndex = i;
                break;
            }
        }

        // Apply visibility update to the identified trace via Plotly.restyle
        Plotly.restyle(plotId, 'visible', visibility, [traceIndex]);
    }

</script>