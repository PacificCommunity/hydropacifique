<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Monthly Statistics Module
Computes and displays monthly statistics with a Plotly chart
----------------------------------------
*/

// ----------------------------------------------
// Required files for script configuration

require('../../config.php');
require('../../database_tables.php');

require('../../function/date.php');
require('../../function/database.php');
require('../../function/html_output.php');
require('../../function/general.php');

require('../../function/math.php');
require('../../function/stats.php');

// Set UTF-8 charset header
header('Content-Type: text/html; charset=utf-8');

// Database connection
$sql_link = mysqli_connect(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD, DB_DATABASE)
    or die('Impossible de se connecter à la base de données!');
mysqli_query($sql_link, 'SET NAMES UTF8');

// -----------------------------------------------
// Load translation strings for the active language
require('../../text_content_' . LANGUAGE . '.php');

// Retrieve JSON data sent from the AJAX request
$jsonData = file_get_contents('php://input');

// Decode JSON data into a PHP associative array
$dataGraph = json_decode($jsonData, true);

// Extract values from the decoded array
$territoire_id = $dataGraph['territoireId'];
$cle_station   = $dataGraph['cle_station'];
$type_station  = $dataGraph['type_station'];
$id_typedata   = $dataGraph['id_typedata'];
$min_x         = $dataGraph['min_x'];
$max_x         = $dataGraph['max_x'];

$date         = DateTime::createFromFormat('d-m-Y', $min_x);
$format_min_x = $date->format('Y-m-d');

$date         = DateTime::createFromFormat('d-m-Y', $max_x);
$format_max_x = $date->format('Y-m-d');


// Query TABLE DATA_CHRON (chronology type: CI, PI, CIE, ...)
$sql_type_chron = "SELECT DISTINCT id_data_type, init_type_data, nom_type_data, id_eq_type_data, axe_data, unite, nb_round
                                to_periode, id_chon_periode, traitement, type_graph
                    FROM " . TABLE_TYPE_DATA . "
                    WHERE id_data_type = " . $id_typedata . "
                    ORDER BY init_type_data ASC";
$type_chron_query = tep_db_query($sql_link, $sql_type_chron);
$type_chron_tab   = tep_db_fetch_array($type_chron_query);

    $id_eq_type_data = isset($type_chron_tab['id_eq_type_data']) ? $type_chron_tab['id_eq_type_data'] : '';
    $init_type_data  = isset($type_chron_tab['init_type_data'])  ? $type_chron_tab['init_type_data']  : '';
    $nom_type_data   = isset($type_chron_tab['nom_type_data'])   ? $type_chron_tab['nom_type_data']   : '';
    $axe_data        = isset($type_chron_tab['axe_data'])        ? $type_chron_tab['axe_data']        : '';
    $unite           = isset($type_chron_tab['unite'])           ? $type_chron_tab['unite']           : '';
    $to_periode      = isset($type_chron_tab['to_periode'])      ? $type_chron_tab['to_periode']      : '';
    $id_chon_periode = isset($type_chron_tab['id_chon_periode']) ? $type_chron_tab['id_chon_periode'] : '';
    $traitement      = isset($type_chron_tab['traitement'])      ? $type_chron_tab['traitement']      : '';
    $typegraph       = isset($type_chron_tab['type_graph'])      ? $type_chron_tab['type_graph']      : '';

// Query TABLE DATA_TYPE_AXE (axis definition)
$sql_data_type_axe = "SELECT DISTINCT id, axe, unite, nb_round
                        FROM " . TABLE_DATA_TYPE_AXE . "
                        WHERE id = " . $axe_data;
$data_type_axe_query = tep_db_query($sql_link, $sql_data_type_axe);
$data_type_axe_tab   = tep_db_fetch_array($data_type_axe_query);

    $axe         = isset($data_type_axe_tab['axe'])      ? $data_type_axe_tab['axe']      : '';
    $axe_unite   = isset($data_type_axe_tab['unite'])    ? $data_type_axe_tab['unite']    : '';
    $axe_nbRound = isset($data_type_axe_tab['nb_round']) ? $data_type_axe_tab['nb_round'] : '';


$html_stats = "";


// -------------------------------------------------------------
// MONTHLY STATISTICS — CALCULATION AND DISPLAY

    $nb_dec = $axe_nbRound;

    // Use SUM for cumulative types (e.g. rainfall), AVG for others
    $calc_type   = "AVG(ABS(da.valeur))";
    $js_typegraph = "
        mode: 'lines',
        type: 'scatter',
        ";

    if ($id_eq_type_data == 1) { $calc_type = "SUM(ABS(da.valeur))"; }
    if ($typegraph == 'bar')   { $js_typegraph = "type: 'bar',"; }
    $js_typegraph = "type: 'bar',"; // Force bar chart

    // Query: monthly aggregate values grouped by year and month
    $sql_stats_by_month_year = "
                            SELECT
                                YEAR(da.dateheure) AS year,
                                MONTH(da.dateheure) AS month,
                                " . $calc_type . " AS calc_valeur
                            FROM
                                " . TABLE_DATA_ALL . " da
                            JOIN
                                " . TABLE_DATA_META . " dm ON da.id_meta=dm.id
                            WHERE
                                dm.id_typedata = " . $id_typedata . "
                                AND dm.id_station = " . $cle_station . "
                                AND da.dateheure >= '" . $format_min_x . "'
                                AND da.dateheure <= '" . $format_max_x . "'
                                AND da.valeur NOT IN (9999, -9999, 8888, -8888, 99999, -99999, 88888, -88888)
                            GROUP BY
                                YEAR(da.dateheure), MONTH(da.dateheure)
                            ORDER BY
                                month DESC, year DESC
                            ";

    $stats_by_month_year       = array();
    $stats_by_month_year_query = tep_db_query($sql_link, $sql_stats_by_month_year);
    while ($stats_by_month_year_tab = tep_db_fetch_array($stats_by_month_year_query))
    {
        $year  = $stats_by_month_year_tab['year'];
        $month = $stats_by_month_year_tab['month'];

        $valeur_bymonth = $stats_by_month_year_tab['calc_valeur'];

        $stats_by_month_year[$month][$year] = (float)$valeur_bymonth;
    }


    // Build the unique year list from all data
    $all_years = [];
    foreach ($stats_by_month_year as $month_data)
    {
        $all_years = array_merge($all_years, array_keys($month_data));
    }
    $all_years = array_unique($all_years);
    rsort($all_years);

    // Month range (1 to 12) for column order
    $all_months = range(1, 12);

    // Short month names (translated via constants)
    $mois_noms_courts = [
        1  => TEXT_MONTH_SHORT_JAN, 2  => TEXT_MONTH_SHORT_FEB, 3  => TEXT_MONTH_SHORT_MAR,
        4  => TEXT_MONTH_SHORT_APR, 5  => TEXT_MONTH_SHORT_MAY, 6  => TEXT_MONTH_SHORT_JUN,
        7  => TEXT_MONTH_SHORT_JUL, 8  => TEXT_MONTH_SHORT_AUG, 9  => TEXT_MONTH_SHORT_SEP,
        10 => TEXT_MONTH_SHORT_OCT, 11 => TEXT_MONTH_SHORT_NOV, 12 => TEXT_MONTH_SHORT_DEC
    ];

    // Statistical row definitions
    $stat_lines = [
        'mean'  => TEXT_STATS_MEAN,
        'min'   => TEXT_STATS_MINIMUM,
        '5pc'   => TEXT_STATS_PERCENTILE_5,
        '10pc'  => TEXT_STATS_PERCENTILE_10,
        '50pc'  => TEXT_STATS_MEDIAN,
        '90pc'  => TEXT_STATS_PERCENTILE_90,
        '95pc'  => TEXT_STATS_PERCENTILE_95,
        'max'   => TEXT_STATS_MAXIMUM,
    ];


    // Compute per-month statistics
    foreach ($stats_by_month_year as $month_num => $annual_data)
    {
        $annual_data      = $stats_by_month_year[$month_num] ?? [];
        $values_for_month = array_values($annual_data);

        if (!empty($values_for_month))
        {
            try {
                $stats_by_month[$month_num]['mean'] = mean($values_for_month);
                $stats_by_month[$month_num]['min']  = min($values_for_month);
                $stats_by_month[$month_num]['max']  = max($values_for_month);
                $stats_by_month[$month_num]['5pc']  = calculate_percentile($values_for_month, 5.0);
                $stats_by_month[$month_num]['10pc'] = calculate_percentile($values_for_month, 10.0);
                $stats_by_month[$month_num]['50pc'] = calculate_percentile($values_for_month, 50.0);
                $stats_by_month[$month_num]['90pc'] = calculate_percentile($values_for_month, 90.0);
                $stats_by_month[$month_num]['95pc'] = calculate_percentile($values_for_month, 95.0);
            } catch (\Exception $e) {
                $stats_by_month[$month_num] = [
                    'mean' => null, 'min' => null, 'max' => null,
                    '5pc'  => null, '10pc' => null, '50pc' => null,
                    '90pc' => null, '95pc' => null
                ];
            }
        }
        else
        {
            $stats_by_month[$month_num] = null;
        }
    }


    // Build HTML — Statistics summary table
    $html_stats .= "

            <p class='info_stats'>
                " . TEXT_STATS_MONTHLY_SUMMARY . "
            </p>

            <table id='table_tri' style='font-size:12px;margin-bottom:40px;'>
                <tr>
                    <th style='width:170px;text-align:center;font-size:13px;color:#000;'>" . TEXT_STATS_STATISTIC . "</th>

            ";
            foreach ($mois_noms_courts as $mois)
            {
                $html_stats .=
                "
                    <th style='width:7%;text-align:center;font-size:13px;'><span>" . $mois . "</span></th>
                    ";
            }

        $html_stats .=
        "
            </tr>
        ";
            $row = 0;
            foreach ($stat_lines as $stat_key => $stat_label)
            {
                if (fmod($row, 2) == 0) { $row_l = "class='row1' onmouseover=\"this.className='row1hover';\" onmouseout=\"this.className='row1';\" "; }
                else                    { $row_l = "class='row2' onmouseover=\"this.className='row2hover';\" onmouseout=\"this.className='row2';\" "; }

                $html_stats .=
                "
                    <tr " . $row_l . ">
                        <td style='height:25px;text-align:center;' >
                            " . $stat_label . "
                        </td>
                ";

                // Per-row extremes: across the 12 months of this stat line,
                // lowest = blue, highest = light red (like the "by days" view).
                $row_vals = [];
                foreach ($all_months as $mn) {
                    $v = $stats_by_month[$mn][$stat_key] ?? null;
                    if ($v !== null) { $row_vals[] = $v; }
                }
                $row_lo = !empty($row_vals) ? min($row_vals) : null;
                $row_hi = !empty($row_vals) ? max($row_vals) : null;

                foreach ($all_months as $month_num)
                {
                    $value = $stats_by_month[$month_num][$stat_key] ?? null;

                    if ($value !== null) {
                        $display_value = round($value, $nb_dec);
                        $class = 'data-present';
                    } else {
                        $display_value = '-';
                        $class = 'data-missing';
                    }

                    // Highlight the lowest / highest month on this row.
                    $bg = '';
                    if ($value !== null && $row_hi != $row_lo) {
                        if ($value == $row_lo)     { $bg = 'background-color:#A8F1FF;'; }
                        elseif ($value == $row_hi) { $bg = 'background-color:#FFDCDC;'; }
                    }

                    $html_stats .=
                    "
                        <td style='height:25px;text-align:center;" . $bg . "' class='" . $class . "'>
                            " . $display_value . "
                        </td>
                    ";
                }

                $row++;
            }

        $html_stats .=
        "
                </tr>
            </table>


        <table id='table_tri' style='font-size:12px;margin-bottom:40px;'>
            <tr>
                <th style='width:170px;text-align:center;font-size:13px;color:#000;'>" . TEXT_STATS_YEAR . "</th>
        ";

            foreach ($mois_noms_courts as $mois)
            {
                $html_stats .=
                "
                    <th style='width:7%;text-align:center;font-size:13px;'><span>" . $mois . "</span></th>
                    ";
            }

            $html_stats .=
            "
                </tr>
                ";

            // Per-column (per-month) extremes across all years:
            // lowest = blue, highest = light red.
            $col_lo = [];
            $col_hi = [];
            foreach ($all_months as $mn) {
                $col_vals = [];
                foreach ($all_years as $yr) {
                    $v = $stats_by_month_year[$mn][$yr] ?? null;
                    if ($v !== null) { $col_vals[] = $v; }
                }
                $col_lo[$mn] = !empty($col_vals) ? min($col_vals) : null;
                $col_hi[$mn] = !empty($col_vals) ? max($col_vals) : null;
            }

            $row = 0;
            foreach ($all_years as $year)
            {
                if (fmod($row, 2) == 0) { $row_l = "class='row1' onmouseover=\"this.className='row1hover';\" onmouseout=\"this.className='row1';\" "; }
                else                    { $row_l = "class='row2' onmouseover=\"this.className='row2hover';\" onmouseout=\"this.className='row2';\" "; }

                $html_stats .=
                "
                    <tr " . $row_l . ">
                        <td style='height:25px;text-align:center;' >
                            " . $year . "
                        </td>
                ";

                    foreach ($all_months as $month_num)
                    {
                        $value = $stats_by_month_year[$month_num][$year] ?? null;

                        if ($value !== null) {
                            $display_value = round($value, $nb_dec);
                            $class = 'data-present';
                        } else {
                            $display_value = '-';
                            $class = 'data-missing';
                        }

                        // Highlight the lowest / highest year for this month.
                        $bg = '';
                        if ($value !== null && $col_hi[$month_num] != $col_lo[$month_num]) {
                            if ($value == $col_lo[$month_num])     { $bg = 'background-color:#A8F1FF;'; }
                            elseif ($value == $col_hi[$month_num]) { $bg = 'background-color:#FFDCDC;'; }
                        }

                        $html_stats .=
                        "
                            <td style='height:25px;text-align:center;" . $bg . "' class='" . $class . "'>
                                " . $display_value . "
                            </td>
                        ";
                    }

                $row++;

                $html_stats .=
                "
                    </tr>
                ";
            }

        $html_stats .=
        "
            </table>
        ";


    // Graph section — Box Plot
    $html_stats_graph = "";

    $html_stats_graph .=
    "
        <p class='info_stats'>
            " . TEXT_STATS_MONTHLY_CHART . "
        </p>

        <div id='plotStats' class='graph_stats' style='height:350px;'>
        </div>
    ";

    $stat_graph = true;
    $js_graph   = "";

    $interannual_metrics = [];
    $plotly_data = ['x' => []];

    foreach (array_keys($stat_lines) as $key)
    {
        $plotly_data[$key] = [];
    }

    // Build Plotly data arrays (one value per month, ordered 1–12)
    foreach ($all_months as $month_num)
    {
        $plotly_data['x'][] = $mois_noms_courts[$month_num];

        if (isset($stats_by_month[$month_num]) && is_array($stats_by_month[$month_num]))
        {
            $stats = $stats_by_month[$month_num];

            foreach (array_keys($stat_lines) as $stat_key)
            {
                $value = $stats[$stat_key] ?? null;
                $plotly_data[$stat_key][] = ($value !== null) ? round((float)$value, $nb_dec) : null;
            }
        }
        else
        {
            foreach (array_keys($stat_lines) as $stat_key)
            {
                $plotly_data[$stat_key][] = null;
            }
        }
    }


    // Compute interannual medians for each statistic
    foreach (array_keys($stat_lines) as $stat_key)
    {
        $values_for_interannual_calc = [];
        foreach ($all_months as $month_num)
        {
            if (isset($stats_by_month[$month_num]) && isset($stats_by_month[$month_num][$stat_key]))
            {
                $values_for_interannual_calc[] = $stats_by_month[$month_num][$stat_key];
            }
        }

        if (!empty($values_for_interannual_calc))
        {
            $mediane_value = calculate_percentile($values_for_interannual_calc, 50.0);
            $interannual_metrics[$stat_key]['value'] = round((float)$mediane_value, $nb_dec);
        }
        else
        {
            $interannual_metrics[$stat_key]['value'] = null;
        }
    }


    $json_plotly_data         = json_encode($plotly_data);
    $json_stat_labels         = json_encode($stat_lines);
    $json_interannual_metrics = json_encode($interannual_metrics);


    // Build Plotly.js chart code
    $js_graph .=
    "
        // Load chart data
        const monthly_stats_data = " . $json_plotly_data . ";
        const stat_label_map = " . $json_stat_labels . ";
        const interannual_metrics = " . $json_interannual_metrics . ";

        // Default statistic displayed on load
        const initial_stat_key   = 'mean';
        const initial_stat_label = stat_label_map[initial_stat_key];

        // Compute reference line value for the initial statistic
        const initial_mediane_val = interannual_metrics[initial_stat_key].value;
        const metrique_titre = '" . TEXT_STATS_INTERANNUAL_MEDIAN . "';

        // Initial trace definitions
        const data =
                    [
                        // Trace 1: Bars (monthly statistic)
                        {
                            x: monthly_stats_data.x,
                            y: monthly_stats_data[initial_stat_key],
                            " . $js_typegraph . "
                            name: initial_stat_label + ' (" . $axe_unite . ")',
                            marker: {
                                        color:'#9BB4C0',
                                        line:{
                                            color:'#000',
                                            width: 1.1
                                            }
                                    },
                            hoverinfo: 'x+y',
                            hoverlabel: { bgcolor: '#fff', font: { size: 14, color: '#000' } },
                            hovertemplate: '<b>" . $axe . "</b> : %{y:." . $nb_dec . "f} " . $axe_unite . "<extra></extra>',
                        },

                        // Trace 2: Reference line (interannual median)
                        {
                            x: [monthly_stats_data.x[0], monthly_stats_data.x[monthly_stats_data.x.length - 1]],
                            y: [initial_mediane_val, initial_mediane_val],
                            name: metrique_titre + ' : ' + initial_mediane_val + ' (" . $axe_unite . ")',
                            type: 'scatter',
                            mode: 'lines',
                            line: {
                                color: '#930000',
                                width: 2,
                                dash: 'dot'
                            },
                            hoverinfo: 'y+name',
                            hoverlabel: { bgcolor: '#fff', font: { size: 14, color: '#930000' } },
                            hovertemplate: '<b>' + metrique_titre + '</b> : %{y:." . $nb_dec . "f} " . $axe_unite . "<extra></extra>',
                        }
                    ];

        // Build dropdown buttons for statistic selection
        const dropdown_buttons = Object.keys(stat_label_map).map(statKey => {
            const mediane_val = interannual_metrics[statKey].value;

            return {
                method: 'update',
                args: [
                    {
                        y: [
                            monthly_stats_data[statKey],
                            [mediane_val, mediane_val]
                        ],
                        name: [
                            stat_label_map[statKey] + ' (" . $axe_unite . ")',
                            metrique_titre + ' : ' + mediane_val + ' (" . $axe_unite . ")'
                        ],
                    },
                    { 'yaxis.autorange': true }
                ],
                label: stat_label_map[statKey]
            };
        });

        // Chart layout
        const layout = {
            xaxis: { tickmode: 'linear', fixedrange: true },
            yaxis: {
                        title: '" . $axe . " (" . $axe_unite . ")',
                        titlefont: {family: 'roboto, arial, helvetica', size: 13, bold: true, color: '#000000'},
                        tickfont: {size: 11},
                        ticklen: 5,
                        tickformat: '." . $nb_dec . "f',
                        showline: true,
                        linewidth: 1,
                    },
            showlegend: true,
            legend: { x: 0.25, y: 1.18, orientation: 'h', font: {size: 11} },
            barmode: 'group',
            bargap: 0.4,
            bargroupgap: 0,
            hovermode: 'x',
            hoverlabel: {bgcolor: '#fff', font: { size: 12, color: '#000' } },
            margin: {l: 50, r: 10, t: 10, b: 40},
            updatemenus: [{
                            buttons: dropdown_buttons,
                            direction: 'down',
                            pad: {r: 0, t: 0 },
                            showactive: true,
                            x: 0,
                            xanchor: 'left',
                            y: 1.18,
                            yanchor: 'top'
                        }]
        };

        Plotly.newPlot('plotStats', data, layout, config);
    ";



$responseData = array(
    'html_stats'       => $html_stats,
    'stat_graph'       => $stat_graph,
    'html_stats_graph' => $html_stats_graph,
    'js_graph'         => $js_graph
);

// Encode response as JSON
$jsonResponse = json_encode($responseData);

// Send response to the client
echo $jsonResponse;
?>