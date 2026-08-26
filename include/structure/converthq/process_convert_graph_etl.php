<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
AJAX endpoint — ETL coverage timeline (SVG + HTML popup)
Receives JSON: timezone_php, xDateMin, xDateMax, idStation.
Builds an SVG timeline showing every ETL of the station as a single
horizontal bar on a year axis. Each bar shows its height range
(Hmin - Hmax cm), exposes a styled HTML popup on hover (dates, height
range, gauging points, hint) and is clickable to open modif_etl.php
in a new tab. Periods not covered by any rating curve are filled with
red hatches.
Returns JSON: { js_text }
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
$timezone_php   = $data['timezone_php'];
$min_x          = $data['xDateMin'];
$max_x          = $data['xDateMax'];
$station_chron  = $data['idStation'];

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
// Load all ETLs of the station, with H range and number of gauging points

$etl_list = [];

$etl_query = tep_db_query($sql_link,
    "SELECT etl.id, etl.datetime_first, etl.datetime_end,
            MIN(ed.hauteur) AS h_min, MAX(ed.hauteur) AS h_max,
            COUNT(ed.id)    AS nb_jaugeages
     FROM " . TABLE_DATA_ETL . " etl
     LEFT JOIN " . TABLE_DATA_ETL_DATA . " ed ON ed.id_etl = etl.id
     WHERE etl.id_station=$station_chron
     AND etl.datetime_first <= '$date_2 23:59:59'
     AND etl.datetime_end   >= '$date_1 00:00:00'
     GROUP BY etl.id, etl.datetime_first, etl.datetime_end
     ORDER BY etl.datetime_first ASC");

while ($etl_tab = tep_db_fetch_array($etl_query))
{
    $etl_list[] = [
        'id'           => (int)$etl_tab['id'],
        'date_first'   => $etl_tab['datetime_first'],
        'date_end'     => $etl_tab['datetime_end'],
        'h_min'        => $etl_tab['h_min'],
        'h_max'        => $etl_tab['h_max'],
        'nb_jaugeages' => (int)$etl_tab['nb_jaugeages'],
    ];
}


// -----------------------------------------------
// SVG geometry — margins match the Plotly chart above (l:50/r:10 + automargin)
// The effective Plotly drawing area starts ~70px from the left and stops
// ~65px from the right because of automargin on yaxis / yaxis2 titles.

$svg_w        = 1000;   // logical width  (viewBox, responsive)
$pad_left     = 50;     // align with Plotly drawing area (left)
$pad_right    = 50;     // align with Plotly drawing area (right)
$bar_y        = 6;
$bar_h        = 14;     // thinner bar
$axis_y       = $bar_y + $bar_h + 7;
$title_y      = $axis_y + 26;
$svg_h_full   = $title_y + 6;
$plot_w       = $svg_w - $pad_left - $pad_right;

$ts_min       = strtotime($date_1 . ' 00:00:00');
$ts_max       = strtotime($date_2 . ' 23:59:59');
$ts_range     = max(1, $ts_max - $ts_min);


// -----------------------------------------------
// Helper — format a height value: integer if whole, max 1 decimal otherwise

function fmt_h($v)
{
    $f = (float)$v;
    if (floor($f) == $f) { return (string)(int)$f; }
    return rtrim(rtrim(number_format($f, 1, '.', ''), '0'), '.');
}


// -----------------------------------------------
// Helper — convert a unix timestamp to an x coordinate

function ts_to_x($ts, $ts_min, $ts_range, $pad_left, $plot_w)
{
    return $pad_left + ($ts - $ts_min) / $ts_range * $plot_w;
}


// -----------------------------------------------
// Build covered segments (clipped to the visible range) and detect gaps

$covered_segments = [];

foreach ($etl_list as $etl)
{
    $ts_f = max($ts_min, strtotime($etl['date_first']));
    $ts_e = min($ts_max, strtotime($etl['date_end']));
    if ($ts_e <= $ts_f) { continue; }

    $covered_segments[] = [
        'ts_first'     => $ts_f,
        'ts_end'       => $ts_e,
        'id'           => $etl['id'],
        'h_min'        => $etl['h_min'],
        'h_max'        => $etl['h_max'],
        'nb_jaugeages' => $etl['nb_jaugeages'],
        'date_first'   => $etl['date_first'],
        'date_end'     => $etl['date_end'],
    ];
}

// Compute uncovered gaps between covered segments (and before / after)
$gaps   = [];
$cursor = $ts_min;
foreach ($covered_segments as $seg)
{
    if ($seg['ts_first'] > $cursor)
    {
        $gaps[] = ['ts_first' => $cursor, 'ts_end' => $seg['ts_first']];
    }
    $cursor = max($cursor, $seg['ts_end']);
}
if ($cursor < $ts_max)
{
    $gaps[] = ['ts_first' => $cursor, 'ts_end' => $ts_max];
}


// -----------------------------------------------
// Build the SVG content

$svg_bars  = '';
$svg_gaps  = '';
$svg_ticks = '';

// Two alternating purple shades
$colors = ['#9B7EBD', '#7B5BA3'];

// Gaps first (so bars stay on top if anything overlaps)
foreach ($gaps as $g)
{
    $x1 = ts_to_x($g['ts_first'], $ts_min, $ts_range, $pad_left, $plot_w);
    $x2 = ts_to_x($g['ts_end'],   $ts_min, $ts_range, $pad_left, $plot_w);
    $w  = max(1, $x2 - $x1);

    $popup_html_gap = htmlspecialchars(
        "<div class='etl_pop_title' style='color:#c25050;'>"
        . TEXT_HQ_ETL_NO_COVERAGE
        . "</div>"
        . "<div class='etl_pop_hint'>" . TEXT_HQ_ETL_GAP_HINT . "</div>",
        ENT_QUOTES, 'UTF-8'
    );

    $svg_gaps .= "
        <rect x='$x1' y='$bar_y' width='$w' height='$bar_h'
              fill='url(#etl_gap_hatch)' stroke='rgba(200,80,80,0.4)' stroke-width='0.5'
              data-popup=\"$popup_html_gap\"
              class='etl_zone etl_zone_gap'/>";
}

// Covered ETL bars
$idx = 0;
foreach ($covered_segments as $seg)
{
    $x1 = ts_to_x($seg['ts_first'], $ts_min, $ts_range, $pad_left, $plot_w);
    $x2 = ts_to_x($seg['ts_end'],   $ts_min, $ts_range, $pad_left, $plot_w);
    $w  = max(2, $x2 - $x1);

    $h_min_fmt = fmt_h($seg['h_min']);
    $h_max_fmt = fmt_h($seg['h_max']);
    $label     = $h_min_fmt . ' - ' . $h_max_fmt . ' cm';

    $date_f_fr = date('d/m/Y', strtotime($seg['date_first']));
    $date_e_fr = date('d/m/Y', strtotime($seg['date_end']));

    // HTML popup content (escaped for the data-popup attribute)
    $popup_html = htmlspecialchars(
        "<div class='etl_pop_title'>" . TEXT_HQ_ETL_TOOLTIP_CURVE . "</div>"
        . "<div class='etl_pop_row'><span>" . TEXT_HQ_ETL_TOOLTIP_PERIOD . "</span> : $date_f_fr → $date_e_fr</div>"
        . "<div class='etl_pop_row'><span>" . TEXT_HQ_ETL_RANGE_PREFIX   . "</span> : " . $h_min_fmt . " - " . $h_max_fmt . " cm</div>"
        . "<div class='etl_pop_row'><span>" . TEXT_GAUGING . "</span> : "           . $seg['nb_jaugeages'] . "</div>"
        . "<div class='etl_pop_hint'>" . TEXT_HQ_ETL_TOOLTIP_HINT . "</div>",
        ENT_QUOTES, 'UTF-8'
    );

    $href  = 'modif_etl.php?st=' . $station_chron . '&id_etl=' . $seg['id'];
    $color = $colors[$idx % 2];
    $idx++;

    $show_label = ($w >= 55);

    $svg_bars .= "
        <a href='$href' target='_blank'>
            <rect x='$x1' y='$bar_y' width='$w' height='$bar_h'
                  rx='3' ry='3'
                  fill='$color' stroke='#000' stroke-width='0.5'
                  data-popup=\"$popup_html\"
                  class='etl_zone etl_zone_bar'
                  style='cursor:pointer;'/>";

    if ($show_label)
    {
        $x_text = $x1 + $w / 2;
        $y_text = $bar_y + $bar_h / 2 + 3;
        $svg_bars .= "
            <text x='$x_text' y='$y_text'
                  text-anchor='middle'
                  font-family='roboto, arial, helvetica' font-size='8' fill='#fff'
                  style='pointer-events:none;'>$label</text>";
    }

    $svg_bars .= "
        </a>";
}


// -----------------------------------------------
// X-axis ticks (adaptive year step)

$y_first = (int)date('Y', $ts_min);
$y_last  = (int)date('Y', $ts_max);
$y_span  = max(1, $y_last - $y_first);

if     ($y_span <= 12) { $step = 1; }
elseif ($y_span <= 25) { $step = 2; }
elseif ($y_span <= 60) { $step = 5; }
else                   { $step = 10; }

for ($y = $y_first; $y <= $y_last; $y += $step)
{
    $ts_y = strtotime($y . '-01-01');
    if ($ts_y < $ts_min || $ts_y > $ts_max) { continue; }

    $x = ts_to_x($ts_y, $ts_min, $ts_range, $pad_left, $plot_w);
    $svg_ticks .= "
        <line x1='$x' y1='$axis_y' x2='$x' y2='" . ($axis_y + 4) . "'
              stroke='#000' stroke-width='0.5'/>
        <text x='$x' y='" . ($axis_y + 14) . "'
              text-anchor='middle'
              font-family='Open Sans, Arial, sans-serif' font-size='8' fill='#000'>$y</text>";
}

// Axis baseline
$svg_ticks .= "
    <line x1='$pad_left' y1='$axis_y' x2='" . ($pad_left + $plot_w) . "' y2='$axis_y'
          stroke='#000' stroke-width='0.5'/>";


// -----------------------------------------------
// Title under the axis

$svg_title = "
    <text x='" . ($svg_w / 2) . "' y='$title_y'
          text-anchor='middle'
          font-family='Open Sans, Arial, sans-serif' font-size='8' fill='#000'>"
       . TEXT_HQ_ETL_COVERAGE_TITLE . "</text>";


// -----------------------------------------------
// Hatch pattern (used to fill uncovered periods)

$defs = "
    <defs>
        <pattern id='etl_gap_hatch' patternUnits='userSpaceOnUse' width='6' height='6'
                 patternTransform='rotate(45)'>
            <rect width='6' height='6' fill='rgba(200,80,80,0.15)'/>
            <line x1='0' y1='0' x2='0' y2='6' stroke='rgba(200,80,80,0.6)' stroke-width='1.5'/>
        </pattern>
    </defs>";


// -----------------------------------------------
// CSS (popup styling) + JS (popup behaviour) — injected once into the page

$popup_css = "
    <style>
        #etl_popup {
            position: fixed; z-index: 9999; display: none;
            background: #fff; border: 1px solid #bbb;
            border-radius: 4px; padding: 8px 10px;
            font-family: Open Sans, Arial, sans-serif; font-size: 11px; color: #222;
            box-shadow: 0 2px 6px rgba(0,0,0,0.15);
            pointer-events: none; max-width: 280px;
        }
        #etl_popup .etl_pop_title {
            font-weight: bold; font-size: 12px;margin-bottom: 4px; color: #4a3270;
        }
        #etl_popup .etl_pop_row { margin: 1px 0; }
        #etl_popup .etl_pop_row span { font-weight: bold; color: #222; }
        #etl_popup .etl_pop_hint {
            margin-top: 6px; padding-top: 4px;
            border-top: 1px dashed #ddd;
            color: #888; font-style: italic; font-size: 10px;
        }
    </style>";

$popup_js = "
    (function() {
        // Ensure a single popup element exists
        var pop = document.getElementById('etl_popup');
        if (!pop) {
            pop = document.createElement('div');
            pop.id = 'etl_popup';
            document.body.appendChild(pop);
        }

        var container = document.getElementById('plot_etl');
        if (!container) { return; }

        container.querySelectorAll('.etl_zone').forEach(function(el) {
            el.addEventListener('mouseenter', function(e) {
                pop.innerHTML = el.getAttribute('data-popup') || '';
                pop.style.display = 'block';
            });
            el.addEventListener('mousemove', function(e) {
                // Offset top-right of the cursor
                var x = e.clientX + 14;
                var y = e.clientY - 10 - pop.offsetHeight;
                // Keep the popup inside the viewport
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
// Final SVG — injected directly into #plot_etl via innerHTML

$svg = "<svg xmlns='http://www.w3.org/2000/svg'
             viewBox='0 0 $svg_w $svg_h_full'
             preserveAspectRatio='xMidYMid meet'
             style='width:100%;height:auto;display:block;'>
            $defs
            $svg_gaps
            $svg_bars
            $svg_ticks
            $svg_title
        </svg>";

// Escape the SVG + CSS for safe JS string concatenation
$payload = $popup_css . $svg;
$payload_js = str_replace(
    ["\\", "'", "\r", "\n"],
    ["\\\\", "\\'", '',  ''],
    $payload
);

$editGraph = "document.getElementById('plot_etl').innerHTML = '$payload_js';" . $popup_js;

echo json_encode([
    'js_text' => $editGraph,
], JSON_UNESCAPED_UNICODE);
?>  