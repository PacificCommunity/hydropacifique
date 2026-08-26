<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
AJAX endpoint — Well log chart JS generator
Receives JSON: listDiag (comma-separated ra IDs).
Queries conductivity/depth/temperature profiles and builds the Plotly
JS code string that is eval()'d by the client.
Returns JSON: {
    js_graph: <string>,
    colors:   { id_ra: hex, ... }   // map used by the client to colour
                                    //   the left-column rows so they
                                    //   visually link to each curve
                                    //   (the chart legend is hidden).
}
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
$list_diag  = $data['listDiag'];

// Defensive: keep only digits/commas (the client builds this from
// checkbox values like '<id_station>_<id_ra>') so we never inject
// arbitrary content into the IN(...) clause.
$list_diag = preg_replace('/[^0-9,]/', '', $list_diag);
if ($list_diag === '') {
    echo json_encode(['js_graph' => '', 'colors' => new stdClass()], JSON_UNESCAPED_UNICODE);
    exit;
}

$data_graph_diag = '';
$load_data_diag  = '';
$color_map       = []; // id_ra => hex colour (returned to the client)
$colorGraph      = colorList();
$nb_diag         = 1;
$xmax            = 0;
$ymin            = 0;

$ra_diag_query = tep_db_query($sql_link,
    "SELECT DISTINCT r.id_ra, r.date_heure_ra, r.id_station,
                     s.code_station, s.nom_station
     FROM " . TABLE_DATA_RA . " r
     JOIN " . TABLE_STATION . " s ON s.id_station = r.id_station
     WHERE r.id_ra IN (" . $list_diag . ")
     ORDER BY code_station DESC, date_heure_ra DESC");

while ($ra_diag = tep_db_fetch_array($ra_diag_query))
{
    $id_ra             = (int) $ra_diag['id_ra'];
    $date_ra           = DateTime::createFromFormat('Y-m-d H:i:s', $ra_diag['date_heure_ra']);
    $formatted_date_ra = $date_ra->format('d-m-Y');
    $code_station      = $ra_diag['code_station'];
    $nom_station       = $ra_diag['nom_station'];

    $graph_x_diag         = '';
    $graph_y_diag         = '';
    $combined_custom_data = [];

    $diag_query = tep_db_query($sql_link,
        "SELECT DISTINCT id_ra, profondeur, conductivite, temperature, obs
         FROM " . TABLE_DATA_RA_PIEZO_PROFIL . "
         WHERE id_ra=" . $id_ra . "
         ORDER BY profondeur ASC");

    while ($diag_tab = tep_db_fetch_array($diag_query))
    {
        $profondeur   = (-1) * $diag_tab['profondeur'];
        $conductivite = $diag_tab['conductivite'];
        $temperature  = $diag_tab['temperature'];
        $obs          = empty($diag_tab['obs']) ? '-' : $diag_tab['obs'];

        if ($xmax < $conductivite) { $xmax = $conductivite; }
        if ($ymin > $profondeur)   { $ymin = $profondeur; }

        $graph_x_diag .= $conductivite . ',';
        $graph_y_diag .= $profondeur   . ',';
        // customdata columns:
        //   [0] temperature
        //   [1] obs
        //   [2] station (code - name)   -> same for every point of the trace
        //   [3] profile date            -> same for every point of the trace
        $combined_custom_data[] = [
            $temperature,
            $obs,
            $code_station . ' - ' . $nom_station,
            $formatted_date_ra,
        ];
    }

    $graph_x_diag              = rtrim($graph_x_diag, ',');
    $graph_y_diag              = rtrim($graph_y_diag, ',');
    $combined_custom_data_json = json_encode($combined_custom_data);

    $colorEtl = $colorGraph[$nb_diag % 18 + 1];
    $color_map[$id_ra] = $colorEtl;

    // Defensive: any value that ends up inside the JS string we eval()
    // on the client MUST be JSON-encoded — a stray apostrophe in
    // code_station or in the date would otherwise break parsing (and
    // could be a script-injection vector).
    $name_js = json_encode($code_station . ' - ' . $formatted_date_ra, JSON_UNESCAPED_UNICODE);

    // Hover template — harmonised with the ETL hover style.
    // NOTE: the TEXT_DG_HOVER_* constants ALREADY contain the bold
    // label, the colon AND the %{...} value placeholder, so we must
    // NOT re-add %{x}/%{y}/etc. here (that caused each value to be
    // printed twice). We only concatenate the constants and append
    // the unit. Station + profile date are prepended from customdata.
    $hover_tpl = '<b>%{customdata[2]}</b>'
               . '<br>' . TEXT_DG_HOVER_DATE . '%{customdata[3]}'
               . '<br>' . TEXT_DG_HOVER_COND . TEXT_DG_UNIT_COND
               . '<br>' . TEXT_DG_HOVER_PROF . TEXT_DG_UNIT_PROF
               . '<br>' . TEXT_DG_HOVER_TEMP . TEXT_DG_UNIT_TEMP
               . '<br>' . TEXT_DG_HOVER_OBS
               . '<extra></extra>';
    $hover_tpl_js = json_encode($hover_tpl, JSON_UNESCAPED_UNICODE);

    // Build the Plotly data series JS block for this RA
    $data_graph_diag .= "
        var data_diag_{$id_ra} = {
            hovermode: 'closest',
            x: [{$graph_x_diag}],
            y: [{$graph_y_diag}],
            customdata: {$combined_custom_data_json},
            name: {$name_js},
            meta: 'ra_{$id_ra}',
            hovertemplate: {$hover_tpl_js},
            mode: 'markers+lines',
            type: 'scatter',
            marker: { size: 8, symbol: 'circle', color: '{$colorEtl}' },
            line:   { color: '{$colorEtl}' }
        };
    ";

    $load_data_diag .= "data_diag_{$id_ra},";
    $nb_diag++;
}
$load_data_diag = rtrim($load_data_diag, ', ');


// ---- Plotly layout ----
// Legend hidden — the left-column row backgrounds now serve as the
// legend (each row's tint matches its trace colour).
$layout_graph = "
    var layout = {
        xaxis: {
            title: {
                text: '" . TEXT_DG_AXIS_X . "',
                standoff: 20,
                font: { family: 'roboto, arial, helvetica', size: 14, bold: true, color: '#000000' }
            },
            tickformat: ',d',
            autorange: false,
            range: [({$xmax}*-0.1), ({$xmax}*1.1)],
            side: 'top'
        },
        yaxis: {
            title: {
                text: '" . TEXT_DG_AXIS_Y . "',
                standoff: 25,
                font: { family: 'roboto, arial, helvetica', size: 14, bold: true, color: '#000000' }
            },
            tickformat: 'd',
            autorange: false,
            range: [({$ymin}*1.1), 0]
        },
        hovermode: 'closest',
        hoverlabel: { bgcolor: '#fff', bordercolor: '#888', font: { size: 12, color: '#000' }, align: 'left' },
        margin: { l: 80, r: 10, t: 75, b: 40 },
        showlegend: false,
        dragmode: 'pan'
    };
";


// ---- Plotly config ----
$config_graph = "
    var config = {
        responsive: true,
        doubleClickDelay: 1000,
        displaylogo: false,
        displayModeBar: true,
        scrollZoom: true,
        modeBarOrientation: 'v',
        modeBarButtons: [[
            { name: 'Export SVG', icon: Plotly.Icons.disk,
              click: function(gd) { Plotly.downloadImage(gd, { format: 'svg', filename: 'Well log' }); } },
            'toImage', 'zoom2d', 'pan2d', 'resetScale2d'
        ]],
        modeBarButtonsToRemove: ['select2d', 'lasso2d', 'autoScale2d', 'zoomIn2d', 'zoomOut2d']
    };
";


// ---- Plot call ----
$editGraph = "Plotly.newPlot('plotDiag', [{$load_data_diag}], layout, config);";

echo json_encode([
    'js_graph' => $config_graph . $data_graph_diag . $layout_graph . $editGraph,
    'colors'   => (object) $color_map,
], JSON_UNESCAPED_UNICODE);
?>