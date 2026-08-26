<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
AJAX endpoint — ETL chart JS generator
Receives JSON: firstLoad, tabIdEtl, dateToday, xMin/xMax/yMin/yMax, idStation.
Builds Plotly JS code for ETL curves + corresponding JGE scatter points.
Returns JSON: { js_text, date_first, min_h, max_h, min_q, max_q }
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

$data       = json_decode(file_get_contents('php://input'), true);
$firstLoad  = $data['firstLoad'];
$date_today = $data['dateToday'];
$tabIdEtl   = $data['tabIdEtl'];
$min_h      = $data['xMin'];
$max_h      = $data['xMax'];
$min_q      = $data['yMin'];
$max_q      = $data['yMax'];
$id_station = $data['idStation'];
$swapAxes   = !empty($data['swapAxes']);

$min_h_fix = 99999;
$max_h_fix = 0;
$min_q_fix = 99999;
$max_q_fix = 0;

$colorGraph       = colorList();
$data_graph       = '';
$data_graph_jge   = '';
$load_data        = '';
$load_data_jge    = '';
$date_first       = $date_today;

// Unit suffixes for tooltips
$unit_h = TEXT_ET_COORD_UNIT_H;
$unit_q = TEXT_ET_COORD_UNIT_Q;

// When axes are swapped: Q is on X, H is on Y. Otherwise H on X, Q on Y.
$ph_h = $swapAxes ? '%{y:.2f}' : '%{x:.2f}';   // placeholder for H value
$ph_q = $swapAxes ? '%{x:.2f}' : '%{y:.2f}';   // placeholder for Q value


if (count($tabIdEtl) > 0)
{
    $tabIdEtl = array_reverse($tabIdEtl);

    foreach ($tabIdEtl as $info_etl)
    {
        $tab_info_etl = explode('_', $info_etl);
        $id_etl   = $tab_info_etl[0];
        $ref_etl  = $tab_info_etl[1];
        $colorEtl = $colorGraph[($ref_etl - 1) % 18 + 1];

        // ETL curve data points
        $index_pts = $graph_x = $graph_y = '';
        $nb_pts    = 0;

        $ETL_data_query = tep_db_query($sql_link,
            "SELECT DISTINCT etl.id, etl.hauteur, etl.debit, etl.code_qualite
             FROM " . TABLE_DATA_ETL_DATA . " etl
             WHERE id_etl=$id_etl ORDER BY hauteur ASC");
        while ($ETL_data_tab = tep_db_fetch_array($ETL_data_query))
        {
            $h_etl = (float) $ETL_data_tab['hauteur'];
            $q_etl = (float) $ETL_data_tab['debit'];

            $index_pts .= $ETL_data_tab['id']      . ',';
            $graph_x   .= $ETL_data_tab['hauteur'] . ',';
            $graph_y   .= $ETL_data_tab['debit']   . ',';
            $nb_pts++;

            // Track bounds from the RC points themselves too. The autoscale
            // below mixes JGE + RC bounds; if there are no JGE points
            // (curve-only display), the min/max would otherwise stay at
            // their sentinel values (99999 / 0) and produce a wildly
            // inverted axis range.
            if ($min_h_fix > $h_etl) { $min_h_fix = $h_etl; }
            if ($max_h_fix < $h_etl) { $max_h_fix = $h_etl; }
            if ($min_q_fix > $q_etl) { $min_q_fix = $q_etl; }
            if ($max_q_fix < $q_etl) { $max_q_fix = $q_etl; }
        }
        $index_pts = rtrim($index_pts, ',');
        $graph_x   = rtrim($graph_x, ',');
        $graph_y   = rtrim($graph_y, ',');

        if ($nb_pts > 0)
        {
            // Swap x/y when the user requested inverted axes
            $tr_x = $swapAxes ? $graph_y : $graph_x;
            $tr_y = $swapAxes ? $graph_x : $graph_y;

            // Rich tooltip: colored title, separator, labeled rows
            $hover_etl = "'<b><span style=\"color:{$colorEtl}\">" . TEXT_ET_LABEL_ETL_REF . " {$ref_etl}</span></b>"
                       . "<br><span style=\"font-size:9px;color:#aaa\">────────────</span>"
                       . "<br><b>" . TEXT_ET_TOOLTIP_H . "</b> {$ph_h} {$unit_h}"
                       . "<br><b>" . TEXT_ET_TOOLTIP_Q . "</b> {$ph_q} {$unit_q}"
                       . "<extra></extra>'";

            $data_graph .= "
                var data_{$id_etl} = {
                    x: [{$tr_x}], y: [{$tr_y}], ids: [{$index_pts}],
                    name: '" . TEXT_ET_LABEL_ETL_REF . " {$ref_etl}',
                    hovertemplate: {$hover_etl},
                    mode: 'markers+lines', type: 'scatter',
                    marker: { size: 8, symbol: 'square', color: '{$colorEtl}' },
                    line:   { color: '{$colorEtl}' }
                };
            ";
            $load_data .= "data_{$id_etl},";
        }

        // Corresponding JGE scatter points
        $etl_query    = tep_db_query($sql_link,
            "SELECT DISTINCT id, datetime_first, datetime_end FROM " . TABLE_DATA_ETL
            . " WHERE id=$id_etl ORDER BY datetime_end DESC");
        $etl_tab      = tep_db_fetch_array($etl_query);
        $datetime_first = $etl_tab['datetime_first'];
        $datetime_end   = $etl_tab['datetime_end'];

        $nb_pts_jge = 0;
        $graph_x_jge = $graph_y_jge = $graph_date_jge = '';

        $jge_query = tep_db_query($sql_link,
            "SELECT DISTINCT jge.id, jge.datetime, jge.depouil_hmoy, jge.depouil_q
             FROM " . TABLE_DATA_JGE . " jge
             WHERE jge.id_station=$id_station
             AND jge.datetime >= '$datetime_first'
             AND jge.datetime <= '$datetime_end'
             AND jge.depouil_hmoy REGEXP '^-?[0-9]+(\.[0-9]+)?$'
             AND jge.depouil_hmoy < 9999
             AND jge.depouil_q   REGEXP '^-?[0-9]+(\.[0-9]+)?$'
             ORDER BY jge.datetime ASC");

        while ($jge_tab = tep_db_fetch_array($jge_query))
        {
            $h_jge   = abs($jge_tab['depouil_hmoy']);
            $q_jge   = abs($jge_tab['depouil_q']);
            $dt_fmt  = date('d-m-Y H:i:s', strtotime($jge_tab['datetime']));

            if ($nb_pts_jge < 1) { $date_first = date('d-m-Y', strtotime($jge_tab['datetime'])); }

            $graph_x_jge    .= $h_jge  . ',';
            $graph_y_jge    .= $q_jge  . ',';
            $graph_date_jge .= "'" . $dt_fmt . "',";

            if ($min_h_fix > $h_jge) { $min_h_fix = $h_jge; }
            if ($max_h_fix < $h_jge) { $max_h_fix = $h_jge; }
            if ($min_q_fix > $q_jge) { $min_q_fix = $q_jge; }
            if ($max_q_fix < $q_jge) { $max_q_fix = $q_jge; }
            $nb_pts_jge++;
        }

        $graph_x_jge    = rtrim($graph_x_jge, ',');
        $graph_y_jge    = rtrim($graph_y_jge, ',');
        $graph_date_jge = rtrim($graph_date_jge, ',');

        if ($nb_pts_jge > 0)
        {
            $tr_x = $swapAxes ? $graph_y_jge : $graph_x_jge;
            $tr_y = $swapAxes ? $graph_x_jge : $graph_y_jge;

            // Rich tooltip with date (from customdata) + H + Q
            $hover_jge = "'<b><span style=\"color:{$colorEtl}\">" . TEXT_ET_LABEL_JGE_REF . " {$ref_etl}</span></b>"
                       . "<br><span style=\"font-size:9px;color:#aaa\">────────────</span>"
                       . "<br><b>" . TEXT_ET_TOOLTIP_DATE . "</b> %{customdata}"
                       . "<br><b>" . TEXT_ET_TOOLTIP_H    . "</b> {$ph_h} {$unit_h}"
                       . "<br><b>" . TEXT_ET_TOOLTIP_Q    . "</b> {$ph_q} {$unit_q}"
                       . "<extra></extra>'";

            $data_graph_jge .= "
                var data_jge_{$id_etl} = {
                    x: [{$tr_x}], y: [{$tr_y}], customdata: [{$graph_date_jge}],
                    name: '" . TEXT_ET_LABEL_JGE_REF . " {$ref_etl}',
                    hovertemplate: {$hover_jge},
                    mode: 'markers', type: 'scatter',
                    marker: { size: 12, symbol: 'star', color: '{$colorEtl}', line: { color: 'black', width: 1 } }
                };
            ";
            $load_data_jge .= "data_jge_{$id_etl},";
        }
    }
    $load_data_all = "[" . rtrim($load_data, ',') . "," . rtrim($load_data_jge, ',') . "]";
}
else
{
    // No ETL selected — show all JGE points for the station
    $id_etl = 0;
    $nb_pts_jge = 0;
    $graph_x_jge = $graph_y_jge = $graph_date_jge = '';

    $jge_query = tep_db_query($sql_link,
        "SELECT DISTINCT jge.id, jge.datetime, jge.depouil_hmoy, jge.depouil_q
         FROM " . TABLE_DATA_JGE . " jge
         WHERE jge.id_station=$id_station
         AND jge.depouil_hmoy REGEXP '^-?[0-9]+(\.[0-9]+)?$'
         AND jge.depouil_hmoy < 9999
         AND jge.depouil_q   REGEXP '^-?[0-9]+(\.[0-9]+)?$'
         ORDER BY jge.datetime ASC");

    while ($jge_tab = tep_db_fetch_array($jge_query))
    {
        $h_jge  = abs($jge_tab['depouil_hmoy']);
        $q_jge  = abs($jge_tab['depouil_q']);
        $dt_fmt = date('d-m-Y H:i:s', strtotime($jge_tab['datetime']));

        if ($nb_pts_jge < 1) { $date_first = date('d-m-Y', strtotime($jge_tab['datetime'])); }

        $graph_x_jge    .= $h_jge  . ',';
        $graph_y_jge    .= $q_jge  . ',';
        $graph_date_jge .= "'" . $dt_fmt . "',";

        if ($min_h_fix > $h_jge) { $min_h_fix = $h_jge; }
        if ($max_h_fix < $h_jge) { $max_h_fix = $h_jge; }
        if ($min_q_fix > $q_jge) { $min_q_fix = $q_jge; }
        if ($max_q_fix < $q_jge) { $max_q_fix = $q_jge; }
        $nb_pts_jge++;
    }

    $graph_x_jge    = rtrim($graph_x_jge, ',');
    $graph_y_jge    = rtrim($graph_y_jge, ',');
    $graph_date_jge = rtrim($graph_date_jge, ',');

    if ($nb_pts_jge > 0)
    {
        $tr_x = $swapAxes ? $graph_y_jge : $graph_x_jge;
        $tr_y = $swapAxes ? $graph_x_jge : $graph_y_jge;

        $hover_jge_all = "'<b>" . TEXT_ET_LABEL_JGE . "</b>"
                       . "<br><span style=\"font-size:9px;color:#aaa\">────────────</span>"
                       . "<br><b>" . TEXT_ET_TOOLTIP_DATE . "</b> %{customdata}"
                       . "<br><b>" . TEXT_ET_TOOLTIP_H    . "</b> {$ph_h} {$unit_h}"
                       . "<br><b>" . TEXT_ET_TOOLTIP_Q    . "</b> {$ph_q} {$unit_q}"
                       . "<extra></extra>'";

        $data_graph_jge = "
            var data_jge_0 = {
                x: [{$tr_x}], y: [{$tr_y}], customdata: [{$graph_date_jge}],
                name: '" . TEXT_ET_LABEL_JGE . "',
                hovertemplate: {$hover_jge_all},
                mode: 'markers', type: 'scatter',
                marker: { size: 10, symbol: 'star', color: '#000' }
            };
        ";
        $load_data_jge = "data_jge_0,";
    }
    $load_data_all = "[" . rtrim($load_data_jge, ',') . "]";
}

// ---- Sanity guard before auto-scale ----
// If neither RC nor JGE points were found (or only one type with extreme
// values), the sentinels (99999 / 0) leak through and produce inverted
// or nonsensical axis ranges. Reset to a neutral [0..1] frame in that
// case — the chart will at least display the axes and the title.
if ($min_h_fix >= $max_h_fix) { $min_h_fix = 0; $max_h_fix = 1; }
if ($min_q_fix >= $max_q_fix) { $min_q_fix = 0; $max_q_fix = 1; }

// ---- Auto-scale ----
// Q: when the lowest value is small, drop the bottom of the chart well
// below zero so the points have visible room beneath. Otherwise just
// pull the bottom down by half. Top always gets +50% headroom.
$amp_q = $max_q_fix - $min_q_fix;
if ($min_q_fix < $amp_q * 0.10) {
    // Small min relative to amplitude → push it negative by 40% of amplitude
    $min_q_fix = -$amp_q * 0.40;
    $max_q_fix *= 1.5;
} else {
    $min_q_fix *= 0.5;
    $max_q_fix *= 1.5;
}

// H: similar logic. If the lowest H is small relative to amplitude,
// push it slightly negative. Otherwise just leave a normal margin.
$amp_h = $max_h_fix - $min_h_fix;
if ($min_h_fix < $amp_h * 0.10) {
    $min_h_fix = -$amp_h * 0.10;
} else {
    $min_h_fix -= $amp_h * 0.3;
}
$max_h_fix += $amp_h * 0.6;

// On subsequent loads, keep the user's current zoom — only auto-scale at
// first load (or if the client sent invalid / empty bounds).
if (!$firstLoad
    && is_numeric($min_h) && is_numeric($max_h)
    && is_numeric($min_q) && is_numeric($max_q)
    && $max_h > $min_h && $max_q > $min_q)
{
    $min_h_fix = $min_h;
    $max_h_fix = $max_h;
    $min_q_fix = $min_q;
    $max_q_fix = $max_q;
}


// ---- Plotly layout ----
// ---- Plotly layout (supports axis swap, keeps H/Q bounds independent) ----
$title_h = TEXT_ET_AXIS_H;
$title_q = TEXT_ET_AXIS_Q;

// The H bounds always come from xMin/xMax inputs, Q bounds from yMin/yMax.
// When swap is on, Q goes on the X axis and H on the Y axis — bounds follow.
if ($swapAxes) {
    $x_title = $title_q;  $x_range = "[{$min_q_fix}, {$max_q_fix}]";
    $y_title = $title_h;  $y_range = "[{$min_h_fix}, {$max_h_fix}]";
} else {
    $x_title = $title_h;  $x_range = "[{$min_h_fix}, {$max_h_fix}]";
    $y_title = $title_q;  $y_range = "[{$min_q_fix}, {$max_q_fix}]";
}

$layout_graph = "
    var layout = {
        xaxis: {
            title: { text: '{$x_title}', standoff: 20, font: { size: 13, bold: true, color: '#000' } },
            autorange: false, range: {$x_range}
        },
        yaxis: {
            title: { text: '{$y_title}', standoff: 20,
                     font: { family: 'roboto, arial, helvetica', size: 14, bold: true, color: '#000' } },
            font:  { family: 'roboto, arial, helvetica', size: 12, bold: true, color: '#000' },
            autorange: false, range: {$y_range}
        },
        hovermode: 'closest',
        hoverlabel: { bgcolor: '#fff', bordercolor: '#888', font: { size: 12, color: '#000' }, align: 'left' },
        margin: { l: 70, r: 10, t: 20, b: 40 },
        showlegend: false
    };
";

// ---- Plotly config ----
$config_graph = "
    var config = {
        responsive: true, doubleClickDelay: 1000, scrollZoom: true,
        displaylogo: false, modeBarOrientation: 'v', displayModeBar: true,
        modeBarButtons: [[
            { name: 'Export SVG', icon: Plotly.Icons.disk,
              click: function(gd) { Plotly.downloadImage(gd, { format: 'svg', filename: 'etl' }); } },
            { name: 'Export PNG', icon: Plotly.Icons.camera,
              click: function(gd) { Plotly.downloadImage(gd, { format: 'png', filename: 'etl' }); } },
            'zoom2d', 'pan2d', 'resetScale2d'
        ]],
        modeBarButtonsToRemove: ['select2d', 'lasso2d', 'autoScale2d', 'zoomIn2d', 'zoomOut2d']
    };
";

// ---- Dynamic chart interactions (zoom sync + scale adjust) ----
$textGraphFonction = "
    document.getElementById('plot').on('plotly_relayout', function(eventData) {
        if (eventData['xaxis.range[0]'] !== undefined) {
            xMin.value = parseFloat(eventData['xaxis.range[0]']).toFixed(1);
            xMax.value = parseFloat(eventData['xaxis.range[1]']).toFixed(1);
        }
        if (eventData['yaxis.range[0]'] !== undefined) {
            yMin.value = parseFloat(eventData['yaxis.range[0]']).toFixed(1);
            yMax.value = parseFloat(eventData['yaxis.range[1]']).toFixed(1);
        }
    });
    document.getElementById('plot').on('plotly_doubleclick', function() {
        xMin.value = parseFloat({$min_h_fix}).toFixed(1);
        xMax.value = parseFloat({$max_h_fix}).toFixed(1);
        yMin.value = parseFloat({$min_q_fix}).toFixed(1);
        yMax.value = parseFloat({$max_q_fix}).toFixed(1);
    });
    document.getElementById('ajustCoord').addEventListener('click', function() {
        const x0 = parseFloat(xMin.value), x1 = parseFloat(xMax.value);
        const y0 = parseFloat(yMin.value), y1 = parseFloat(yMax.value);
        if (!isNaN(x0) && !isNaN(x1) && !isNaN(y0) && !isNaN(y1)) {
            Plotly.relayout('plot', { 'xaxis.range': [x0, x1], 'yaxis.range': [y0, y1] });
        }
    });
";

$editGraph = "Plotly.newPlot('plot', {$load_data_all}, layout, config);";

echo json_encode([
    'js_text'   => $config_graph . $data_graph . $data_graph_jge . $layout_graph . $editGraph . $textGraphFonction,
    'date_first' => $date_first,
    'min_h'     => $min_h_fix,
    'max_h'     => $max_h_fix,
    'min_q'     => $min_q_fix,
    'max_q'     => $max_q_fix,
], JSON_UNESCAPED_UNICODE);
?>