<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Right-side navigation menu (modernized)
Renders all menu sections with accordion open/close state persisted
per user (TABLE_USER_MENU). Access to each section depends on
HP_VERSION ('Serveur' or 'Nomad') and $gestion_data / $parametre levels.

Sections:
  DATA      - time-series, field reports, gaugings, diagraphy, stations
  MOD       - import, export, ETL, agents, sync
  SET       - geo zones, time-series types, quality codes, equipment, export params
  ACT       - import/export tracking, corrections, action log
  RESSOURCE - homepage, conditions, data licence, contact

Modernization notes:
- Inline SVG icons next to each link (Lucide icon set, MIT license)
- Active page auto-detected via $_SERVER['SCRIPT_NAME']
- All JS hooks preserved (.toggle-header, .navigation, #toggle-arrow,
  #conditions_popup, #licence_popup, menuStates persistence)
----------------------------------------
*/

require(DIR_WS_INDEX . 'block_index_affiche.php');

// Load per-user menu open/close states
$menu_states = [];
$sql_query   = "SELECT menu_id, is_open FROM " . TABLE_USER_MENU . " WHERE id_user = ?";
$stmt = $sql_link->prepare($sql_query);
$stmt->bind_param("i", $id_user);
$stmt->execute();
$result = $stmt->get_result();
while ($row_menu = $result->fetch_assoc())
{
    $menu_states[$row_menu['menu_id']] = $row_menu['is_open'];
}
$stmt->close();

// i18n JS strings
$msgCGU     = json_encode(TEXT_MENU_POPUP_CGU);
$msgLicence = json_encode(TEXT_MENU_POPUP_LICENCE);


// -----------------------------------------------
// Detect current page for active link highlight
$current_page  = basename($_SERVER['SCRIPT_NAME']);
$current_query = isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '';

// Special case : data_chron.php uses both GET (?export=true) and POST
// (hidden field 'export' in form_chron_step1.php) to flag export mode.
// When the page is reached via POST at step 2, the URL no longer carries
// ?export=true, so we re-inject it into $current_query for the active
// state to follow correctly.
if ($current_page === 'data_chron.php' && empty($current_query))
{
    $export_flag = '';
    if (isset($_POST['export']))    { $export_flag = $_POST['export']; }
    elseif (isset($_GET['export'])) { $export_flag = $_GET['export']; }

    if (!empty($export_flag) && $export_flag !== 'false' && $export_flag !== '0')
    {
        $current_query = 'export=true';
    }
}


// -----------------------------------------------
// Helper: returns inline SVG icon by name (Lucide icons, MIT license)
function menu_icon($name)
{
    $icons = [
        // DATA section
        'time-series'  => '<path d="M3 12h4l3-9 4 18 3-9h4"/>',
        'field-report' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>',
        'gauging'      => '<path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/>',
        'diagraphy'    => '<line x1="12" y1="2" x2="12" y2="22"/><line x1="2" y1="12" x2="22" y2="12"/><line x1="6" y1="6" x2="18" y2="18"/><line x1="6" y1="18" x2="18" y2="6"/>',
        'stations'     => '<line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/>',

        // MOD section
        'import'       => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>',
        'export'       => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/>',
        'etl'          => '<polyline points="3 17 9 11 13 15 21 7"/><polyline points="14 7 21 7 21 14"/>',
        'agents'       => '<circle cx="12" cy="8" r="4"/><path d="M4 22v-2a8 8 0 0 1 16 0v2"/>',
        'sync'         => '<polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/>',

        // SET section
        'geo'          => '<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>',
        'type-data'    => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
        'quality'      => '<path d="M9 12l2 2 4-4"/><path d="M21 12c0 5-4 9-9 9s-9-4-9-9 4-9 9-9 9 4 9 9z"/>',
        'eq-jge'       => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>',
        'transfer'     => '<polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/>',

        // ACT section
        'track-import' => '<path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="7.5 4.21 12 6.81 16.5 4.21"/><polyline points="7.5 19.79 7.5 14.6 3 12"/><polyline points="21 12 16.5 14.6 16.5 19.79"/>',
        'track-export' => '<path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/>',
        'correction'   => '<path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>',
        'actions'      => '<line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/>',

        // RESSOURCE section
        'home'         => '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>',
        'terms'        => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="9" y1="15" x2="15" y2="15"/>',
        'licence'      => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
        'mail'         => '<path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/>',
    ];

    $path = isset($icons[$name]) ? $icons[$name] : '';
    return '<svg class="menu-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $path . '</svg>';
}


// -----------------------------------------------
// Helper: renders a menu link with icon + active class
// Active state matches both the page basename AND the query string,
// so e.g. data_chron.php and data_chron.php?export=true highlight
// independently rather than both at once.
function menu_link($href, $icon, $label, $current_page, $current_query = '', $extra = '')
{
    $page  = basename(parse_url($href, PHP_URL_PATH));
    $query = parse_url($href, PHP_URL_QUERY) ?? '';

    $active = '';
    if ($page === $current_page && $query === $current_query)
    {
        $active = ' class="active"';
    }

    $extra = $extra ? ' ' . $extra : '';
    return "<li class='simple'><a href='" . $href . "'" . $active . $extra . ">"
         . menu_icon($icon) . "<span>" . $label . "</span></a></li>";
}


echo "<div id='toggle-arrow' data-menu-id='all'>&laquo;</div>";

echo "<div id='col'>";

    echo "<div id='nav_col'>";

        // -----------------------------------------------
        // DATA section

        echo "<div class='section' data-section='data'>";
            echo "<h4 class='toggle-header' data-menu-id='data'>";
                echo "<span>" . TEXT_MENU_DATA . "</span>";
                echo "<span class='arrow'>&#9660;</span>";
            echo "</h4>";
            echo "<div class='navigation' style='display:none;'>";
                echo "<ul>";

                    // Time-Series Data (server only)
                    if (HP_VERSION == 'Serveur')
                    {
                        echo menu_link('data_chron.php', 'time-series', TEXT_MENU_DATA_CHRON, $current_page, $current_query);
                    }

                    // Field Reports (data managers)
                    if ($gestion_data > 0)
                    {
                        echo menu_link('list_ra.php', 'field-report', TEXT_MENU_DATA_ACTREPORT, $current_page, $current_query);
                    }

                    // Import (server + advanced data managers)
                    if (HP_VERSION == 'Serveur' && $gestion_data > 1)
                    {
                        echo menu_link('import.php', 'import', TEXT_MENU_DATA_IMPORT, $current_page, $current_query);
                    }

                    // Export (server only)
                    if (HP_VERSION == 'Serveur')
                    {
                        echo menu_link('data_chron.php?export=true', 'export', TEXT_MENU_DATA_EXPORT, $current_page, $current_query);
                    }

                echo "</ul>";
            echo "</div>";
        echo "</div>";


        // -----------------------------------------------
        // MOD section

        echo "<div class='section' data-section='mod'>";
            echo "<h4 class='toggle-header' data-menu-id='mod'>";
                echo "<span>" . TEXT_MENU_MOD . "</span>";
                echo "<span class='arrow'>&#9660;</span>";
            echo "</h4>";
            echo "<div class='navigation' style='display:none;'>";
                echo "<ul>";

                    // List Stations (always available)
                    echo menu_link('list_stations.php', 'stations', TEXT_MENU_MOD_STATION, $current_page, $current_query);

                    // Pacth for KIRIBATI TRAINING need to specify with condition width Data Type SurfaceWater is selected
                    if(INIT_T <> 'KI') 
                    {

                        // Stream Gaugings (data managers)
                        if ($gestion_data > 0)
                        {
                            echo menu_link('data_jge.php', 'gauging', TEXT_MENU_MOD_JGE, $current_page, $current_query);
                        }

                        // Rating Curves + Agents (server + advanced data managers)
                        if (HP_VERSION == 'Serveur' && $gestion_data > 1)
                        {
                            echo menu_link('data_etl.php', 'etl', TEXT_MENU_MOD_ETL, $current_page, $current_query);
                        }
                    }

                    // Well Logs / Diagraphy (server + data managers)
                    if (HP_VERSION == 'Serveur' && $gestion_data > 0)
                    {
                        echo menu_link('data_diag_piezo.php', 'diagraphy', TEXT_MENU_MOD_DIAG, $current_page, $current_query);
                    }

                    // Agents (server + advanced data managers)
                    if (HP_VERSION == 'Serveur' && $gestion_data > 1)
                    {
                        echo menu_link('list_agents.php', 'agents', TEXT_MENU_MOD_AGENTS, $current_page, $current_query);
                    }

                    // Sync (Nomad version only)
                    if (HP_VERSION == 'Nomad' && $gestion_data > 0)
                    {
                        echo menu_link('sync.php', 'sync', TEXT_MENU_DATA_SYNC, $current_page, $current_query);
                    }

                echo "</ul>";
            echo "</div>";
        echo "</div>";


        // -----------------------------------------------
        // SET section (server + admin only)

        if (HP_VERSION == 'Serveur' && $parametre > 0)
        {
            echo "<div class='section' data-section='set'>";
                echo "<h4 class='toggle-header' data-menu-id='param'>";
                    echo "<span>" . TEXT_MENU_SET . "</span>";
                    echo "<span class='arrow'>&#9660;</span>";
                echo "</h4>";
                echo "<div class='navigation'>";
                    echo "<ul>";

                        echo menu_link('gestion_geo.php',          'geo',       TEXT_MENU_SET_GEO,    $current_page, $current_query);
                        echo menu_link('gestion_type_data.php',    'type-data', TEXT_MENU_SET_TYPEC,  $current_page, $current_query);
                        echo menu_link('gestion_quality_data.php', 'quality',   TEXT_MENU_SET_QUAL,   $current_page, $current_query);
                        echo menu_link('gestion_eq_jaugeage.php',  'eq-jge',    TEXT_MENU_SET_EQJGE,  $current_page, $current_query);
                        if(INIT_T == 'NC'){echo menu_link('export_param.php',         'transfer',  TEXT_MENU_SET_TRANSF, $current_page, $current_query);}

                    echo "</ul>";
                echo "</div>";
            echo "</div>";
        }


        // -----------------------------------------------
        // ACT section - tracking (server + manager only)

        if (HP_VERSION == 'Serveur' && $gestion_data > 1)
        {
            echo "<div class='section' data-section='act'>";
                echo "<h4 class='toggle-header' data-menu-id='act'>";
                    echo "<span>" . TEXT_MENU_HP . "</span>";
                    echo "<span class='arrow'>&#9660;</span>";
                echo "</h4>";
                echo "<div class='navigation'>";
                    echo "<ul>";

                        echo menu_link('list_imports.php', 'track-import', TEXT_MENU_HP_TRACKIMPORT,    $current_page, $current_query);
                        echo menu_link('list_exports.php', 'track-export', TEXT_MENU_HP_TRACKEXPORT,    $current_page, $current_query);
                        echo menu_link('corrections.php',  'correction',   TEXT_MENU_DATA_TRACKCONNECT, $current_page, $current_query);
                        echo menu_link('list_actions.php', 'actions',      TEXT_MENU_HP_ACTIONS,        $current_page, $current_query);

                    echo "</ul>";
                echo "</div>";
            echo "</div>";
        }


        // -----------------------------------------------
        // RESSOURCE section

        echo "<div class='section' data-section='ress'>";
            echo "<h4 class='toggle-header' data-menu-id='ress'>";
                echo "<span>" . TEXT_MENU_RESSOURCE . "</span>";
                echo "<span class='arrow'>&#9660;</span>";
            echo "</h4>";
            echo "<div class='navigation'>";
                echo "<ul>";

                    echo menu_link('index.php', 'home', TEXT_MENU_RESSOURCE_FIRST, $current_page, $current_query);

                    // CGU and Licence are popup links (no href, custom JS handlers)
                    echo "<li class='simple'><a href='#' id='conditions_popup'>"
                       . menu_icon('terms') . "<span>" . TEXT_MENU_RESSOURCE_CONDITION . "</span></a></li>";

                    echo "<li class='simple'><a href='#' id='licence_popup'>"
                       . menu_icon('licence') . "<span>" . TEXT_MENU_RESSOURCE_DATA . "</span></a></li>";

                    echo "<li class='simple'><a href='mailto:" . MAIL_CONTACT . "'>"
                       . menu_icon('mail') . "<span>Contact</span></a></li>";

                echo "</ul>";
            echo "</div>";
        echo "</div>";


        // -----------------------------------------------
        // Optional partner logo

        if (!empty(LOGO_IMG) && file_exists(LOGO_IMG))
        {
            echo "<hr>";
            echo "<div class='section' style='margin-top:30px;text-align:center;'>";
                echo "<a href='" . LOGO_LINK . "' target='_blank' title='" . LOGO_INFO . "'>";
                    echo "<img src='" . LOGO_IMG . "' style='width:100px;'>";
                echo "</a>";
            echo "</div>";
        }

    echo "</div>"; // nav_col

echo "<hr>";
echo "</div>"; // col
?>

<script>

    // Pass per-user menu states to JS
    const menuStates = <?php echo json_encode($menu_states); ?>;
    const msgCGU     = <?php echo $msgCGU; ?>;
    const msgLicence = <?php echo $msgLicence; ?>;


    // -----------------------------------------------
    // Collapse/expand the whole side column

    document.addEventListener('DOMContentLoaded', function()
    {
        const toggleArrow = document.getElementById('toggle-arrow');
        const col         = document.getElementById('col');
        const id_user     = <?php echo json_encode($id_user); ?>;
        const menuId      = toggleArrow.getAttribute('data-menu-id');
        const isMenuOpen  = menuStates[menuId] === 1;

        if (isMenuOpen) { col.classList.remove('collapsed'); toggleArrow.innerHTML = '&laquo;'; }
        else            { col.classList.add('collapsed');    toggleArrow.innerHTML = '&raquo;'; }

        toggleArrow.addEventListener('click', function()
        {
            const isOpen = !col.classList.contains('collapsed');
            col.classList.toggle('collapsed');
            toggleArrow.innerHTML = col.classList.contains('collapsed') ? '&raquo;' : '&laquo;';

            const xhr = new XMLHttpRequest();
            xhr.open("POST", "include/structure/box/process_menu.php", true);
            xhr.setRequestHeader("Content-Type", "application/json");
            xhr.send(JSON.stringify({ id_user: id_user, menu_id: menuId, is_open: !isOpen }));
        });
    });


    // -----------------------------------------------
    // Accordion open/close per section (jQuery)

    $(document).ready(function()
    {
        // Apply saved states on load
        $('.toggle-header').each(function()
        {
            const menuId   = $(this).data('menu-id');
            const isOpen   = menuStates[menuId] === 1;
            const nav      = $(this).nextAll('.navigation').first();
            const arrow    = $(this).find('.arrow');
            if (isOpen) { nav.show(); arrow.html('&#9650;'); }
            else        { nav.hide(); arrow.html('&#9660;'); }
        });

        // Auto-open the section containing the active link
        const activeLink = $('.navigation a.active').first();
        if (activeLink.length)
        {
            const nav = activeLink.closest('.navigation');
            if (!nav.is(':visible'))
            {
                nav.show();
                nav.prevAll('.toggle-header').first().find('.arrow').html('&#9650;');
            }
        }

        $('.toggle-header').click(function()
        {
            const id_user = <?php echo json_encode($id_user); ?>;
            const nav     = $(this).nextAll('.navigation').first();
            const menuId  = $(this).data('menu-id');
            const isOpen  = nav.is(':visible');

            nav.slideToggle('slow', function()
            {
                const arrow = $(this).prevAll('.toggle-header').find('.arrow');
                arrow.html(nav.is(':visible') ? '&#9650;' : '&#9660;');

                const xhr = new XMLHttpRequest();
                xhr.open("POST", "include/structure/box/process_menu.php", true);
                xhr.setRequestHeader("Content-Type", "application/json");
                xhr.send(JSON.stringify({ id_user: id_user, menu_id: menuId, is_open: !isOpen }));
            });
        });
    });


    // -----------------------------------------------
    // Info popups - Terms of Use and Data Licence
    var boxData    = document.getElementById('box_data');
    var contenuBox = document.getElementById('cadre_index_cell');
    var waitBox    = document.getElementById('cadre_wait');

    function openInfoPopup(type, title)
    {
        var titleBox = document.getElementById('title_box_data_info');
        boxData.style.display = 'block';
        titleBox.innerHTML = '';
        waitBox.style.display = 'block';

        var xhr = new XMLHttpRequest();
        xhr.open("POST", "include/structure/box/process_info_document.php", true);
        xhr.setRequestHeader("Content-Type", "application/json");
        xhr.onreadystatechange = function()
        {
            if (xhr.readyState === 4 && xhr.status === 200)
            {
                titleBox.innerHTML = title;
                var r = JSON.parse(xhr.responseText);
                contenuBox.innerHTML  = r['js_html'];
                waitBox.style.display = 'none';
                initDraggable("title_box_data", "box_data");
            }
        };
        xhr.send(JSON.stringify({
            territoireId: '<?php echo $territoire_id; ?>',
            type: type
        }));
    }

    var conditionsPopup = document.getElementById('conditions_popup');
    var licencePopup    = document.getElementById('licence_popup');

    if (conditionsPopup)
    {
        conditionsPopup.onclick = function() { openInfoPopup('conditions', msgCGU); };
    }
    if (licencePopup)
    {
        licencePopup.onclick = function() { openInfoPopup('licence', msgLicence); };
    }

</script>