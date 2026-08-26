<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Statistics Index Module
Displays the station summary panel: interannual median,
record min/max, and a monthly bar chart
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
$dataIndex = json_decode($jsonData, true);

// Extract values from the decoded array
$territoire_id = $dataIndex['territoireId'];
$cle_station   = $dataIndex['idStation'];


// Query TABLE DATA_TYPE_AXE — load all axis definitions into a lookup array
$sql_data_type_axe   = "SELECT DISTINCT id, axe, unite, nb_round FROM " . TABLE_DATA_TYPE_AXE;
$data_type_axe_query = tep_db_query($sql_link, $sql_data_type_axe);
while ($data_type_axe = tep_db_fetch_array($data_type_axe_query))
{
    $axe_name    = isset($data_type_axe['axe'])      ? $data_type_axe['axe']      : '';
    $axe_unite   = isset($data_type_axe['unite'])    ? $data_type_axe['unite']    : '';
    $axe_nb_round = isset($data_type_axe['nb_round']) ? $data_type_axe['nb_round'] : '';

    $data_type_axe_array[$data_type_axe['id']] = array(
        'axe_name'    => $axe_name,
        'axe_unite'   => $axe_unite,
        'axe_nb_round' => $axe_nb_round,
    );
}

// Initialise output variables
$html_text = '';
$nb_dec    = 3;
$unite     = '';

// Query TABLE STATION — station details
$sql_station = "SELECT DISTINCT id_station, id_service, code_station, nom_station, station_type
                FROM " . TABLE_STATION . "
                WHERE id_station=" . $cle_station;
$station_query = tep_db_query($sql_link, $sql_station);
$station_tab   = tep_db_fetch_array($station_query);

    $id_station   = isset($station_tab['id_station'])  ? $station_tab['id_station']  : '';
    $code_station = isset($station_tab['code_station']) ? $station_tab['code_station'] : '';
    $nom_station  = isset($station_tab['nom_station'])  ? $station_tab['nom_station']  : '';
    $type_station = isset($station_tab['station_type']) ? $station_tab['station_type'] : '';


    // ------------------------------------------------------------------
    // ANNUAL DATA — Interannual median and records

        $data_valid = true;

        // Query: metadata for the most relevant chronology type (time_scale 1–3)
        $sql_metaData = "SELECT DISTINCT dm.id_station, dm.id_typedata, td.id_eq_type_data,
                                        td.init_type_data, td.nom_type_data, td.axe_data
                        FROM
                            " . TABLE_DATA_META . " dm
                        JOIN
                            " . TABLE_TYPE_DATA . " td ON td.id_data_type=dm.id_typedata
                        WHERE
                            dm.id_station=" . $cle_station . "
                            AND td.time_scale IN (1,2,3)
                        ORDER BY td.time_scale DESC
                        LIMIT 1
                        ";

        $metaData_query = tep_db_query($sql_link, $sql_metaData);
        if (tep_db_num_rows($metaData_query) > 0)
        {
            $metaData_tab = tep_db_fetch_array($metaData_query);

            $id_service    = isset($metaData_tab['id_service'])    ? $metaData_tab['id_service']    : '';
            $id_eq         = isset($metaData_tab['id_eq_type_data']) ? $metaData_tab['id_eq_type_data'] : '';
            $id_typedata   = isset($metaData_tab['id_typedata'])   ? $metaData_tab['id_typedata']   : '';
            $init_type_data = isset($metaData_tab['init_type_data']) ? $metaData_tab['init_type_data'] : '';
            $nom_type_data = isset($metaData_tab['nom_type_data']) ? $metaData_tab['nom_type_data'] : '';
            $axe_type_data = isset($metaData_tab['axe_data'])      ? $metaData_tab['axe_data']      : 0;
            $time_scale    = isset($metaData_tab['time_scale'])    ? $metaData_tab['time_scale']    : 0;

            // Retrieve axis properties from the lookup array
            $nb_dec      = (int)$data_type_axe_array[$axe_type_data]['axe_nb_round'];
            $unite       = $data_type_axe_array[$axe_type_data]['axe_unite'];
            $type_valeur = $data_type_axe_array[$axe_type_data]['axe_name'];

            // Use SUM for cumulative types (e.g. rainfall), AVG for others
            $calc_type = "AVG(ABS(da.valeur))";
            if ($id_eq == 1) { $calc_type = "SUM(ABS(da.valeur))"; }

            $stats_by_year = [];

            // Query: annual statistics (cumul/mean, min, max)
            $sql_Data = "SELECT
                            YEAR(da.dateheure) AS year,
                            " . $calc_type . " AS calc_valeur,
                            MIN(ABS(da.valeur)) AS min,
                            MAX(ABS(da.valeur)) AS max
                        FROM
                            " . TABLE_DATA_ALL . " da
                        JOIN
                            " . TABLE_DATA_META . " dm ON da.id_meta=dm.id
                        WHERE
                            dm.id_typedata = " . $id_typedata . "
                            AND dm.id_station = " . $cle_station . "
                            AND da.valeur NOT IN (9999, -9999, 8888, -8888, 99999, -99999, 88888, -88888)
                        GROUP BY
                            year
                        ORDER BY
                            year ASC
                        ";
            $data_query = tep_db_query($sql_link, $sql_Data);

            while ($data_tab = tep_db_fetch_array($data_query))
            {
                $year = $data_tab['year'];

                $calc_valeur        = (float)$data_tab['calc_valeur'];
                $calc_valeur_format = round($calc_valeur, $nb_dec);

                $min        = (float)$data_tab['min'];
                $min_format = round($min, $nb_dec);

                $max        = (float)$data_tab['max'];
                $max_format = round($max, $nb_dec);

                $stats_by_year[$year] = array(
                    'calc_valeur'        => $calc_valeur,
                    'calc_valeur_format' => $calc_valeur_format,
                    'min'                => $min,
                    'min_format'         => $min_format,
                    'max'                => $max,
                    'max_format'         => $max_format
                );
            }

            // Remove first and last year (usually incomplete)
            if (count($stats_by_year) >= 3)
            {
                $stats_filtered = array_slice($stats_by_year, 1, -1, true);
            }
            else
            {
                $stats_filtered = $stats_by_year;
            }

            $year_first = array_key_first($stats_filtered);
            $year_last  = array_key_last($stats_filtered);

            // Compute interannual median
            $valeur_array  = array_column($stats_filtered, 'calc_valeur');
            $metrique_data = calculate_percentile($valeur_array, 50.0);

            $metrique_data_format = round((float)$metrique_data, $nb_dec);

            $titre_metrique = TEXT_STATS_MEDIAN_LABEL;
            $stat_name      = $type_valeur . ' ' . TEXT_INDEX_ANNUAL_STAT_LABEL;

            // Initialise record tracking
            $record_max = -1;
            $year_max   = '';
            $record_min = PHP_FLOAT_MAX;
            $year_min   = '';

            if (!empty($stats_filtered))
            {
                foreach ($stats_filtered as $year => $data)
                {
                    if ($data['calc_valeur'] > $record_max)
                    {
                        $record_max = $data['calc_valeur'];
                        $year_max   = $year;
                    }
                    if ($data['calc_valeur'] < $record_min)
                    {
                        $record_min = $data['calc_valeur'];
                        $year_min   = $year;
                    }
                }
            }

            $record_max = round($record_max, $nb_dec);
            $record_min = round($record_min, $nb_dec);


            // Build HTML — Summary panel
            $html_text .= "<div class='tooltip-item' style='padding:0px;'>";

            if (!empty($stats_filtered))
            {
                $html_text .= "<table style='width:100%; border-collapse: collapse;'>";
                $html_text .= "  <tr>";

                    // Column 1: Main metric (interannual median)
                    $html_text .= "    <td style='vertical-align: middle; padding-right: 10px; width: 60%;'>";
                    $html_text .= "      <span style='font-size: 28px; font-weight: bold;'>";
                    $html_text .=            $metrique_data_format . " " . $unite;
                    $html_text .= "      </span>";
                    $html_text .= "      <br>";
                    $html_text .= "      <span style='font-size: 12px; color: #9DB2BF;'>";
                    $html_text .=            $stat_name;
                    $html_text .= "         <br>";
                    $html_text .=            TEXT_INDEX_BETWEEN . ' ' . $year_first . " " . TEXT_INDEX_AND . " " . $year_last;
                    $html_text .= "      </span>";
                    $html_text .= "    </td>";

                    // Column 2: Min/Max records
                    $html_text .= "    <td style='vertical-align: middle; padding: 10px; border-left: 1px solid #eee;'>";
                    $html_text .= "      <div style='font-size: 12px; margin-bottom: 5px;'>";
                    $html_text .= "        <strong>" . TEXT_STATS_MINIMUM . "</strong>";
                    $html_text .= "        <br> " . $record_min . " " . $unite . " (" . $year_min . ")";
                    $html_text .= "      </div>";
                    $html_text .= "      <div style='font-size: 12px;'>";
                    $html_text .= "        <strong>" . TEXT_STATS_MAXIMUM . "</strong>";
                    $html_text .= "        <br> " . $record_max . " " . $unite . " (" . $year_max . ")";
                    $html_text .= "      </div>";
                    $html_text .= "    </td>";

                $html_text .= "  </tr>";
                $html_text .= "</table>";
            }
            else
            {
                $data_valid = false;

                $html_text .= "<p style='margin-top:3px;'>";
                    $html_text .= TEXT_INDEX_DATA_UNUSABLE;
                $html_text .= "</p>";
            }
        }
        else
        {
            $data_valid = false;

            $html_text .= "<p style='margin-top:3px;'>";
                $html_text .= TEXT_INDEX_DATA_MISSING;
            $html_text .= "</p>";
        }

        $html_text .= "</div>";


    // ------------------------------------------------------------------
    // MONTHLY CHART — Mean monthly values with interannual median reference line

        $js_graph = "";
        $nb_dec   = 0;

        if ($data_valid)
        {
            // Query: metadata for the best available sub-daily or daily resolution
            $sql_metaData = "SELECT DISTINCT dm.id_station, dm.id_typedata, td.id_eq_type_data,
                                            td.init_type_data, td.nom_type_data, td.axe_data
                            FROM
                                " . TABLE_DATA_META . " dm
                            JOIN
                                " . TABLE_TYPE_DATA . " td ON td.id_data_type=dm.id_typedata
                            WHERE
                                dm.id_station=" . $cle_station . "
                                AND td.time_scale IN (1,2)
                            ORDER BY td.time_scale DESC
                            LIMIT 1
                            ";

            $metaData_query = tep_db_query($sql_link, $sql_metaData);
            if (tep_db_num_rows($metaData_query) > 0)
            {
                $metaData_tab = tep_db_fetch_array($metaData_query);

                $id_eq         = isset($metaData_tab['id_eq_type_data']) ? $metaData_tab['id_eq_type_data'] : '';
                $id_typedata   = isset($metaData_tab['id_typedata'])   ? $metaData_tab['id_typedata']   : '';
                $init_type_data = isset($metaData_tab['init_type_data']) ? $metaData_tab['init_type_data'] : '';
                $nom_type_data = isset($metaData_tab['nom_type_data']) ? $metaData_tab['nom_type_data'] : '';
                $axe_type_data = isset($metaData_tab['axe_data'])      ? $metaData_tab['axe_data']      : 0;
                $time_scale    = isset($metaData_tab['time_scale'])    ? $metaData_tab['time_scale']    : 0;

                $nb_dec      = (int)$data_type_axe_array[$axe_type_data]['axe_nb_round'];
                $unite       = $data_type_axe_array[$axe_type_data]['axe_unite'];
                $type_valeur = $data_type_axe_array[$axe_type_data]['axe_name'];

                // Label and aggregation depend on data type
                $type_valeur = TEXT_INDEX_MEAN_FLOW;
                $calc_type   = "AVG(ABS(da.valeur))";
                if ($id_eq == 1)
                {
                    $type_valeur = TEXT_INDEX_CUMUL;
                    $calc_type   = "SUM(ABS(da.valeur))";
                }

                // Query: monthly mean values by year
                $sql_Data = "
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
                                AND da.valeur NOT IN (9999, -9999, 8888, -8888, 99999, -99999, 88888, -88888)
                            GROUP BY
                                year, month
                            ORDER BY
                                month DESC, year DESC
                            ";

                $stats_by_month_years = [];
                $stats_by_month       = [];
                $mean_by_month        = [];

                $data_query = tep_db_query($sql_link, $sql_Data);
                while ($data_tab = tep_db_fetch_array($data_query))
                {
                    $year  = $data_tab['year'];
                    $month = $data_tab['month'];
                    $stats_by_month_years[$month][$year] = (float)$data_tab['calc_valeur'];
                }

                // Compute mean per month across all years
                foreach ($stats_by_month_years as $month_num => $annual_data)
                {
                    $values_for_month = array_values($annual_data);
                    if (!empty($values_for_month))
                    {
                        $mean_by_month[$month_num] = mean($values_for_month);
                    }
                }

                $all_months = range(1, 12);

                // Short month names (translated via constants)
                $mois_noms_courts = [
                    1  => TEXT_MONTH_SHORT_JAN, 2  => TEXT_MONTH_SHORT_FEB, 3  => TEXT_MONTH_SHORT_MAR,
                    4  => TEXT_MONTH_SHORT_APR, 5  => TEXT_MONTH_SHORT_MAY, 6  => TEXT_MONTH_SHORT_JUN,
                    7  => TEXT_MONTH_SHORT_JUL, 8  => TEXT_MONTH_SHORT_AUG, 9  => TEXT_MONTH_SHORT_SEP,
                    10 => TEXT_MONTH_SHORT_OCT, 11 => TEXT_MONTH_SHORT_NOV, 12 => TEXT_MONTH_SHORT_DEC
                ];

                // Build Plotly data arrays
                foreach ($all_months as $month_num)
                {
                    $plotly_data['x'][] = $mois_noms_courts[$month_num];

                    if (isset($mean_by_month[$month_num]))
                    {
                        $stats = $mean_by_month[$month_num] ?? null;
                        $plotly_data['y'][] = ($stats !== null) ? round((float)$stats, $nb_dec) : null;
                    }
                    else
                    {
                        $plotly_data['y'][] = null;
                    }
                }

                $mediane_value = calculate_percentile($mean_by_month, 50.0);
                $json_plotly_data = json_encode($plotly_data);

                // Build Plotly.js chart code
                $js_graph .= "
                        // Load monthly data
                        const monthly_stats_data = " . $json_plotly_data . ";

                        // Compute reference line (interannual median)
                        const mediane_val    = " . $mediane_value . ";
                        const metrique_titre = '" . TEXT_STATS_MEDIAN . "';

                        // Trace definitions
                        const data =
                                    [
                                        // Trace 1: Monthly bars
                                        {
                                            x: monthly_stats_data.x,
                                            y: monthly_stats_data.y,
                                            type: 'bar',
                                            name: '" . $type_valeur . " (" . $unite . ")',
                                            marker: {
                                                        color:'#9DB2BF',
                                                        line:{ color:'#ccc', width: 0 }
                                                    },
                                            hoverinfo: 'x+y',
                                            hoverlabel: { bgcolor: '#fff', font: { size: 14, color: '#000' } },
                                            hovertemplate: '<b>" . $type_valeur . "</b> : %{y:." . $nb_dec . "f} " . $unite . "<extra></extra>',
                                        },

                                        // Trace 2: Median reference line
                                        {
                                            x: [monthly_stats_data.x[0], monthly_stats_data.x[monthly_stats_data.x.length - 1]],
                                            y: [mediane_val, mediane_val],
                                            name: metrique_titre + ' : " . $type_valeur . " (" . $unite . ")',
                                            type: 'scatter',
                                            mode: 'lines',
                                            line: { color: '#930000', width: 2, dash: 'dot' },
                                            hoverinfo: 'none',
                                        }
                                    ];

                        // Chart layout
                        const layout = {
                                            xaxis: {
                                                        tickmode: 'linear',
                                                        showline: false, showgrid: false, ticklen: 0,
                                                        mirror: false, tickfont: { size: 9 }, fixedrange: true,
                                                    },
                                            yaxis: {
                                                        showticklabels: false, showgrid: false,
                                                        showline: false, fixedrange: true
                                                    },
                                            showlegend: false,
                                            dragmode: false,
                                            barmode: 'group', bargap: 0.4, bargroupgap: 0,
                                            hovermode: 'x',
                                            hoverlabel: {bgcolor: '#fff', font: { size: 11, color: '#000' } },
                                            margin: {l: 0, r: 0, t: 0, b: 25},
                                        };

                        var config = {
                            responsive: false,
                            scrollZoom: true,
                            displaylogo: false,
                            displayModeBar: false,
                        };

                        // Render with a short delay to ensure the container is ready
                        setTimeout(function() {
                            Plotly.newPlot('plotStats', data, layout, config);
                        }, 200);
                ";


                $html_text .=
                            "
                                <div id='plotStats' class='graph_stats' style='height:140px;'>
                                </div>

                                <p class='info_stats'>
                                    " . $type_valeur . " " . TEXT_INDEX_MONTHLY_LABEL . " (" . TEXT_INDEX_BETWEEN . " " . $year_first . " " . TEXT_INDEX_AND . " " . $year_last . ")
                                </p>

                                <p class='info_stats' style='color:#930000;'>
                                    " . TEXT_STATS_MEDIAN . " : " . round($mediane_value, $nb_dec) . " " . $unite . "
                                </p>
                            ";
            }
        }


$responseData = array(
    'html_text' => $html_text,
    'js_graph'  => $js_graph,
);

// Encode response as JSON
$jsonResponse = json_encode($responseData);

// Send response to the client
echo $jsonResponse;
?>
