<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
AJAX endpoint — data_meta coverage timeline (SVG + HTML popup)

Lightweight companion to process_graph_multi.php, modelled on
process_convert_graph_etl.php. For a given station + chronicle type it
draws, on a year axis, one horizontal band per data_meta record (id_meta).
Each band is colored with the quality-code color (couleur_qualite_data)
and exposes a styled HTML popup on hover showing:
  - the quality code (init + name)
  - the observation (data_meta.obs)
  - the covered period

The X axis is meant to be kept in sync with the Plotly chart above it:
the caller passes the visible range (xDateMin / xDateMax, FR dd-mm-yyyy)
and the bands are clipped to that range. On zoom, the caller simply
re-invokes this endpoint with the new range.

Receives JSON:
  timezone_php, xDateMin, xDateMax, idStation, typedataChron, plotKey
Returns JSON:
  { js_text }   — JS that injects the SVG into #plot_meta_<plotKey>
                  and wires the hover popup.
----------------------------------------
*/

require('../../config.php');
require('../../database_tables.php');

require('../../function/date.php');
require('../../function/database.php');
require('../../function/html_output.php');
require('../../function/general.php');

require('../../text_content_' . LANGUAGE . '.php');

header('Content-Type: text/html; charset=utf-8');

$sql_link = mysqli_connect(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD, DB_DATABASE)
    or die('Cannot connect to the database');
mysqli_query($sql_link, 'SET NAMES UTF8');

$data           = json_decode(file_get_contents('php://input'), true);
$timezone_php   = $data['timezone_php']   ?? 'UTC';
$min_x          = $data['xDateMin']       ?? '';
$max_x          = $data['xDateMax']       ?? '';
$station_chron  = (int)($data['idStation']      ?? 0);
$typedata_chron = (int)($data['typedataChron']  ?? 0);
$plot_key       = $data['plotKey']        ?? '';
$svg_px_width   = (int)($data['svgWidth']  ?? 0);   // real container width in px (0 = default)
// Plot area offsets measured on the Plotly chart (px, in the same pixel space
// as svgWidth). Let the timeline's drawing area match the chart's exactly.
$plot_off_left  = isset($data['padLeft'])  ? (float)$data['padLeft']  : -1;
$plot_off_right = isset($data['padRight']) ? (float)$data['padRight'] : -1;

// Sanitise plot_key (used only to build a DOM id): keep word chars / dashes
$plot_key = preg_replace('/[^A-Za-z0-9_\-]/', '', $plot_key);

date_default_timezone_set($timezone_php);
$today  = new DateTime();
$date_1 = '1950-01-01';
$date_2 = $today->format('Y-m-d');

if (tep_not_null($min_x) && tep_not_null($max_x))
{
    $date_1 = datefr_us($min_x);
    $date_2 = datefr_us($max_x);
}


// -----------------------------------------------
// Quality code lookup — init / name / color, keyed by id_data_qualite

$code_qual_array = [];

$code_qual_query = tep_db_query($sql_link,
    "SELECT DISTINCT id_data_qualite, init_qualite_data, nom_qualite_data, couleur_qualite_data
     FROM " . TABLE_DATA_QUALITE . "
     ORDER BY id_data_qualite");

while ($cq = tep_db_fetch_array($code_qual_query))
{
    $code_qual_array[$cq['id_data_qualite']] = [
        'init_qualite_data'    => html_entity_decode($cq['init_qualite_data'] ?? ''),
        'nom_qualite_data'     => html_entity_decode($cq['nom_qualite_data']  ?? ''),
        'couleur_qualite_data' => $cq['couleur_qualite_data'] ?? '',
    ];
}


// -----------------------------------------------
// Load one band per data_meta record (id_meta), with its covered period.
// The period is the min / max dateheure of the data points attached to it,
// clipped to the visible window via the WHERE clause.

$meta_list = [];

$meta_query = tep_db_query($sql_link,
    "SELECT dm.id          AS id_meta,
            dm.id_codequal AS id_codequal,
            dm.obs         AS obs,
            dm.obs_user    AS obs_user,
            MIN(da.dateheure) AS date_first,
            MAX(da.dateheure) AS date_end,
            COUNT(*)          AS nb_points
     FROM " . TABLE_DATA_META . " dm
     JOIN " . TABLE_DATA_ALL  . " da ON da.id_meta = dm.id
     WHERE dm.id_station  = " . $station_chron  . "
     AND   dm.id_typedata = " . $typedata_chron . "
     AND   da.dateheure  <= '" . $date_2 . " 23:59:59'
     AND   da.dateheure  >= '" . $date_1 . " 00:00:00'
     GROUP BY dm.id, dm.id_codequal, dm.obs, dm.obs_user
     ORDER BY date_first ASC");

while ($m = tep_db_fetch_array($meta_query))
{
    $meta_list[] = [
        'id_meta'     => (int)$m['id_meta'],
        'id_codequal' => $m['id_codequal'],
        'obs'         => $m['obs'],
        'obs_user'    => $m['obs_user'] ?? '',
        'date_first'  => $m['date_first'],
        'date_end'    => $m['date_end'],
        'nb_points'   => (int)$m['nb_points'],
    ];
}


// -----------------------------------------------
// SVG geometry — margins aligned with the Plotly chart drawing area
// (same approach as process_convert_graph_etl.php).

// Logical width follows the real container width (sent by the client) so the
// SVG is generated at the right aspect ratio and never stretched. Falls back
// to 1000 when the client could not measure it (e.g. hidden container).
$svg_w      = ($svg_px_width >= 200 && $svg_px_width <= 6000) ? $svg_px_width : 1000;

// Align the timeline drawing area with the Plotly chart's plot area.
// When the client measured the chart offsets (padLeft = left edge of the plot
// area, padRight = gap from the right edge), use them so both X axes line up
// exactly. Otherwise fall back to the ETL-timeline defaults (50 / 50).
$pad_left  = ($plot_off_left  >= 0 && $plot_off_left  < $svg_w) ? $plot_off_left  : 50;
$pad_right = ($plot_off_right >= 0 && $plot_off_right < $svg_w) ? $plot_off_right : 50;
// Safety: keep a positive drawing width even with odd measurements.
if (($svg_w - $pad_left - $pad_right) < 50)
{
    $pad_left  = 50;
    $pad_right = 50;
}
$bar_y      = 4;
$bar_h      = 14;                 // thicker band so the quality code is readable
$title_y    = $bar_y + $bar_h + 16; // baseline of the (larger) title text
$svg_h_full = $title_y + 6;
$plot_w     = $svg_w - $pad_left - $pad_right;

$ts_min   = strtotime($date_1 . ' 00:00:00');
$ts_max   = strtotime($date_2 . ' 23:59:59');
$ts_range = max(1, $ts_max - $ts_min);


// -----------------------------------------------
// Helper — unix timestamp to x coordinate

function meta_ts_to_x($ts, $ts_min, $ts_range, $pad_left, $plot_w)
{
    return $pad_left + ($ts - $ts_min) / $ts_range * $plot_w;
}


// -----------------------------------------------
// Default band color when the quality code has no color defined
$default_band_color = '#cccccc';


// -----------------------------------------------
// Build the SVG bands (one per id_meta)

$svg_bars  = '';
$svg_ticks = '';

foreach ($meta_list as $seg)
{
    $ts_f = max($ts_min, strtotime($seg['date_first']));
    $ts_e = min($ts_max, strtotime($seg['date_end']));
    if ($ts_e <= $ts_f) { continue; }

    $x1 = meta_ts_to_x($ts_f, $ts_min, $ts_range, $pad_left, $plot_w);
    $x2 = meta_ts_to_x($ts_e, $ts_min, $ts_range, $pad_left, $plot_w);
    $w  = max(2, $x2 - $x1);

    // Resolve quality code -> color / labels.
    // A segment is "unqualified" when it has no quality code at all (no row,
    // empty init). Such segments are drawn as a white box with a black outline
    // and a "No quality code" label, rather than a filled grey band.
    $cq        = $code_qual_array[$seg['id_codequal']] ?? null;
    $qual_init = $cq ? $cq['init_qualite_data'] : '';
    $qual_nom  = $cq ? $cq['nom_qualite_data']  : '';
    $has_qual  = ($qual_init !== '');

    if ($has_qual)
    {
        $color      = tep_not_null($cq['couleur_qualite_data'])
                    ? $cq['couleur_qualite_data']
                    : $default_band_color;
        $qual_label = trim($qual_init . ($qual_nom !== '' ? ' - ' . $qual_nom : ''));
        $band_label = $qual_init;   // short code shown inside the band
    }
    else
    {
        // Unqualified segment: white fill, black outline, explicit label.
        $color      = '#ffffff';
        $qual_label = TEXT_GRAPH_META_NO_QUALCODE;
        $band_label = TEXT_GRAPH_META_NO_QUALCODE;
    }

    $date_f_fr = date('d/m/Y', strtotime($seg['date_first']));
    $date_e_fr = date('d/m/Y', strtotime($seg['date_end']));

    // Correction (data_meta.obs) and Comment (data_meta.obs_user) lines,
    // shown only when present. These details now live ONLY in the timeline
    // tooltip (removed from the chart hover to avoid duplicating them on
    // every single data point).
    $obs_html = tep_not_null($seg['obs'])
        ? "<div class='meta_pop_row'><span>" . TEXT_GRAPH_HOVER_CORRECTION . "</span> : "
          . htmlspecialchars($seg['obs'], ENT_QUOTES, 'UTF-8') . "</div>"
        : "";

    $obs_user_html = tep_not_null($seg['obs_user'])
        ? "<div class='meta_pop_row'><span>" . TEXT_GRAPH_HOVER_CORRECTION_OBS . "</span> : "
          . htmlspecialchars($seg['obs_user'], ENT_QUOTES, 'UTF-8') . "</div>"
        : "";

    // HTML popup content (escaped for the data-popup attribute)
    $popup_html = htmlspecialchars(
        "<div class='meta_pop_row'><span>" . TEXT_GRAPH_HOVER_QUALCODE . "</span> : " . $qual_label . "</div>"
        . "<div class='meta_pop_row'><span>" . TEXT_HQ_ETL_TOOLTIP_PERIOD . "</span> : $date_f_fr → $date_e_fr</div>"
        . $obs_html
        . $obs_user_html,
        ENT_QUOTES, 'UTF-8'
    );

    // A readable text color on top of the band (black/white by luminance)
    $text_color = '#fff';
    if (preg_match('/^#([0-9A-Fa-f]{6})$/', $color, $mm))
    {
        $r = hexdec(substr($mm[1], 0, 2));
        $g = hexdec(substr($mm[1], 2, 2));
        $b = hexdec(substr($mm[1], 4, 2));
        $lum = (0.299 * $r + 0.587 * $g + 0.114 * $b);
        $text_color = ($lum > 150) ? '#222' : '#fff';
    }

    // Tooltip border color = the band color. For unqualified (white) bands,
    // a white border would be invisible, so fall back to a neutral grey.
    $popup_border_color = $has_qual ? $color : '#888';

    $svg_bars .= "
        <rect x='$x1' y='$bar_y' width='$w' height='$bar_h'
              rx='3' ry='3'
              fill='$color' stroke='#000' stroke-width='0.5'
              data-popup=\"$popup_html\"
              data-popup-color='$popup_border_color'
              class='meta_zone meta_zone_bar'/>";

    // Label inside the band when wide enough.
    //  - qualified segment: short code, large bold font (needs ~26px)
    //  - unqualified segment: longer "No quality code" text, smaller font,
    //    only drawn when the box is wide enough to fit it (otherwise the
    //    plain white outlined box speaks for itself).
    $label_font  = $has_qual ? 12 : 9;
    $label_min_w = $has_qual ? 26 : 80;

    if ($w >= $label_min_w && $band_label !== '')
    {
        $x_text = $x1 + $w / 2;
        $y_text = $bar_y + $bar_h / 2 + ($has_qual ? 5 : 3);
        $svg_bars .= "
            <text x='$x_text' y='$y_text'
                  text-anchor='middle'
                  font-family='roboto, arial, helvetica' font-size='$label_font' font-weight='bold' fill='$text_color'
                  style='pointer-events:none;'>" . htmlspecialchars($band_label, ENT_QUOTES, 'UTF-8') . "</text>";
    }
}


// -----------------------------------------------
// (X-axis ticks removed — the Plotly chart above already shows the X axis,
//  so the timeline no longer draws its own ticks or baseline.)


// -----------------------------------------------
// Title under the band

$svg_title = "
    <text x='" . ($svg_w / 2) . "' y='$title_y'
          text-anchor='middle'
          font-family='Open Sans, Arial, sans-serif' font-size='12' font-weight='bold' fill='#000'>"
       . TEXT_GRAPH_META_COVERAGE_TITLE . "</text>";


// -----------------------------------------------
// CSS (popup styling) — injected once into the page

$popup_css = "
    <style>
        #meta_popup {
            position: fixed; z-index: 9999; display: none;
            background: #fff; border: 2px solid #bbb;
            border-radius: 4px; padding: 8px 10px;
            font-family: Open Sans, Arial, sans-serif; font-size: 11px; color: #222;
            box-shadow: 0 2px 6px rgba(0,0,0,0.15);
            pointer-events: none; max-width: 320px;
        }
        #meta_popup .meta_pop_title {
            font-weight: bold; font-size: 12px; margin-bottom: 4px; color: #176B87;
        }
        #meta_popup .meta_pop_row { margin: 1px 0; }
        #meta_popup .meta_pop_row span { font-weight: bold; color: #222; }
        .meta_zone_bar { cursor: default; }
    </style>";


// -----------------------------------------------
// JS (popup behaviour) — scoped to this plot's container.
// A single #meta_popup element is shared across all meta timelines.

$container_id = 'plot_meta_' . $plot_key;

$popup_js = "
    (function() {
        var pop = document.getElementById('meta_popup');
        if (!pop) {
            pop = document.createElement('div');
            pop.id = 'meta_popup';
            document.body.appendChild(pop);
        }

        var container = document.getElementById('" . $container_id . "');
        if (!container) { return; }

        container.querySelectorAll('.meta_zone').forEach(function(el) {
            el.addEventListener('mouseenter', function() {
                pop.innerHTML = el.getAttribute('data-popup') || '';
                // Border takes the quality-code color of this band.
                pop.style.borderColor = el.getAttribute('data-popup-color') || '#bbb';
                pop.style.display = 'block';
            });
            el.addEventListener('mousemove', function(e) {
                var x = e.clientX + 14;
                var y = e.clientY - 10 - pop.offsetHeight;
                if (x + pop.offsetWidth > window.innerWidth - 8) {
                    x = e.clientX - pop.offsetWidth - 14;
                }
                if (y < 8) { y = e.clientY + 18; }
                pop.style.left = x + 'px';
                pop.style.top  = y + 'px';
            });
            el.addEventListener('mouseleave', function() {
                pop.style.display = 'none';
            });
        });
    })();
";


// -----------------------------------------------
// Final SVG — injected into #plot_meta_<plotKey> via innerHTML

$svg = "<svg xmlns='http://www.w3.org/2000/svg'
             viewBox='0 0 $svg_w $svg_h_full'
             preserveAspectRatio='xMidYMid meet'
             style='width:100%;height:" . $svg_h_full . "px;display:block;'>
            $svg_bars
            $svg_ticks
            $svg_title
        </svg>";

// Escape the SVG + CSS for safe JS string concatenation
$payload    = $popup_css . $svg;
$payload_js = str_replace(
    ["\\", "'", "\r", "\n"],
    ["\\\\", "\\'", '',  ''],
    $payload
);

$editGraph = "document.getElementById('" . $container_id . "').innerHTML = '$payload_js';" . $popup_js;

echo json_encode([
    'js_text' => $editGraph,
], JSON_UNESCAPED_UNICODE);
?>