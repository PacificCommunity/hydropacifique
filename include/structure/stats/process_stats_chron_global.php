<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Global Statistics Module
Computes overall statistics and optionally displays a Gumbel return period
analysis (enabled only when INIT_T == 'PF')
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


// Query TABLE EQ_TYPE (data type: rainfall, streamflow, ...)
$sql_eq_type = "SELECT DISTINCT id_eq_type, nom_eq_type, unite_eq_type, valeur_data_type, type_color_border, type_color_background, type_graph
                FROM " . TABLE_EQ_TYPE . "
                WHERE active_eq_type=1
                AND id_eq_type = " . $type_station . "
                ORDER BY order_eq_type ASC";
$eq_type_query = tep_db_query($sql_link, $sql_eq_type);
$eq_type_tab   = tep_db_fetch_array($eq_type_query);

    $id_eq_type           = isset($eq_type_tab['id_eq_type'])           ? $eq_type_tab['id_eq_type']           : '';
    $nom_eq_type          = isset($eq_type_tab['nom_eq_type'])          ? $eq_type_tab['nom_eq_type']          : '';
    $unite_eq_type        = isset($eq_type_tab['unite_eq_type'])        ? $eq_type_tab['unite_eq_type']        : '';
    $valeur_data_type     = isset($eq_type_tab['valeur_data_type'])     ? $eq_type_tab['valeur_data_type']     : '';
    $type_color_border    = isset($eq_type_tab['type_color_border'])    ? $eq_type_tab['type_color_border']    : '';
    $type_color_background = isset($eq_type_tab['type_color_background']) ? $eq_type_tab['type_color_background'] : '';
    $type_graph           = isset($eq_type_tab['type_graph'])           ? $eq_type_tab['type_graph']           : '';

// Query TABLE DATA_CHRON (chronology type: CI, PI, CIE, ...)
$sql_type_chron = "SELECT DISTINCT id_data_type, init_type_data, nom_type_data, id_eq_type_data, axe_data, unite, nb_round,
                                to_periode, id_chon_periode, traitement, type_graph
                    FROM " . TABLE_TYPE_DATA . "
                    WHERE id_data_type = " . $id_typedata . "
                    ORDER BY init_type_data ASC";
$type_chron_query = tep_db_query($sql_link, $sql_type_chron);
$type_chron_tab   = tep_db_fetch_array($type_chron_query);

    $init_type_data  = isset($type_chron_tab['init_type_data'])  ? $type_chron_tab['init_type_data']  : '';
    $nom_type_data   = isset($type_chron_tab['nom_type_data'])   ? $type_chron_tab['nom_type_data']   : '';
    $axe_data        = isset($type_chron_tab['axe_data'])        ? $type_chron_tab['axe_data']        : '';
    $unite           = isset($type_chron_tab['unite'])           ? $type_chron_tab['unite']           : '';
    $nb_round        = isset($type_chron_tab['unite'])           ? $type_chron_tab['nb_round']        : '';
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

    $axe_name    = isset($data_type_axe_tab['axe'])      ? $data_type_axe_tab['axe']      : '';
    $axe_unite   = isset($data_type_axe_tab['unite'])    ? $data_type_axe_tab['unite']    : '';
    $axe_nb_round = isset($data_type_axe_tab['nb_round']) ? $data_type_axe_tab['nb_round'] : '';


// -------------------------------------------------------------
// GLOBAL STATISTICS — CALCULATION AND DISPLAY

    $nb_dec = 0;
    if (isset($axe_nb_round) && !empty($axe_nb_round)) { $nb_dec = $axe_nb_round; }

    // Query: overall aggregates (mean, cumul, std, min, max)
    $sql_stats = "
                    SELECT
                        AVG(ABS(da.valeur)) AS moy,    -- Mean
                        SUM(ABS(da.valeur)) AS cumul,  -- Cumulative sum
                        STD(ABS(da.valeur)) AS std,    -- Standard deviation
                        MIN(ABS(da.valeur)) AS min,
                        MAX(ABS(da.valeur)) AS max
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
                    ";
    $stats_query = tep_db_query($sql_link, $sql_stats);
    $stats_tab   = tep_db_fetch_array($stats_query);

    $moy_all        = $stats_tab['moy'];
    $moy_all_format = round((float)$moy_all, $nb_dec);
    $std_all        = $stats_tab['std'];
    $std_all_format = round((float)$std_all, $nb_dec);
    $min_all        = $stats_tab['min'];
    $min_all_format = round((float)$min_all, $nb_dec);
    $max_all        = $stats_tab['max'];
    $max_all_format = round((float)$max_all, $nb_dec);
    $cumul_all      = $stats_tab['cumul'];
    $cumul_all_format = round((float)$cumul_all, $nb_dec);

    // Query: individual values sorted descending for percentile computation
    $sql_data = "
                    SELECT da.dateheure, ABS(da.valeur) as valeur
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
                    ORDER BY ABS(da.valeur) DESC
                    ";
    $data_query = tep_db_query($sql_link, $sql_data);

    $data_tab = array();
    while ($row = tep_db_fetch_array($data_query))
    {
        $data_tab[] = $row['valeur'];
    }

    $p25        = calculerPercentile($data_tab, 25);
    $p25_format = round((float)$p25, $nb_dec);

    $p50        = calculerPercentile($data_tab, 50);
    $p50_format = round((float)$p50, $nb_dec);

    $p75        = calculerPercentile($data_tab, 75);
    $p75_format = round((float)$p75, $nb_dec);


    // Dates of the min and max values (shown under the column headers).
    $date_min_val = '-';
    $date_max_val = '-';
    $q_minmax = tep_db_query($sql_link,
        "SELECT da.dateheure, ABS(da.valeur) AS v
         FROM " . TABLE_DATA_ALL  . " da
         JOIN " . TABLE_DATA_META . " dm ON da.id_meta = dm.id
         WHERE dm.id_typedata = " . $id_typedata . "
         AND   dm.id_station  = " . $cle_station . "
         AND   da.dateheure  >= '" . $format_min_x . "'
         AND   da.dateheure  <= '" . $format_max_x . "'
         AND   da.valeur NOT IN (9999, -9999, 8888, -8888, 99999, -99999, 88888, -88888)
         ORDER BY ABS(da.valeur) ASC LIMIT 1");
    if ($r = tep_db_fetch_array($q_minmax)) { $date_min_val = date('d/m/Y', strtotime($r['dateheure'])); }

    $q_max = tep_db_query($sql_link,
        "SELECT da.dateheure, ABS(da.valeur) AS v
         FROM " . TABLE_DATA_ALL  . " da
         JOIN " . TABLE_DATA_META . " dm ON da.id_meta = dm.id
         WHERE dm.id_typedata = " . $id_typedata . "
         AND   dm.id_station  = " . $cle_station . "
         AND   da.dateheure  >= '" . $format_min_x . "'
         AND   da.dateheure  <= '" . $format_max_x . "'
         AND   da.valeur NOT IN (9999, -9999, 8888, -8888, 99999, -99999, 88888, -88888)
         ORDER BY ABS(da.valeur) DESC LIMIT 1");
    if ($r = tep_db_fetch_array($q_max)) { $date_max_val = date('d/m/Y', strtotime($r['dateheure'])); }


    // Build HTML output
    $html_stats       = "";
    $html_stats_graph = "";
    $stat_graph       = true;
    $js_graph         = "";

    $html_stats_graph .= "
            <p class='info_stats'>
                " . TEXT_STATS_GLOBAL_DATA . "
            </p>";

    if ($id_eq_type == 1)
    {
        // Cumulative type (e.g. rainfall): show min, max, cumul
        $html_stats_graph .= "
            <table id='table_tri' style='font-size:12px;' >
                <tr>
                    <th style='width:150px;text-align:center;font-size:12px;'><span>" . TEXT_STATS_MINIMUM . "</span><br><span style='font-weight:normal;font-size:13px;color:#555;'>" . $date_min_val . "</span></th>
                    <th style='width:150px;text-align:center;font-size:12px;'><span>" . TEXT_STATS_MAXIMUM . "</span><br><span style='font-weight:normal;font-size:13px;color:#555;'>" . $date_max_val . "</span></th>
                    <th style='width:150px;text-align:center;font-size:12px;color:#930000;'><span>" . TEXT_STATS_CUMUL . "</span></th>
                </tr>
                <tr>
                    <td style='text-align:center;'>" . $min_all_format . "</td>
                    <td style='text-align:center;'>" . $max_all_format . "</td>
                    <td style='text-align:center;'>" . $cumul_all_format . "</td>
                </tr>
            </table>
        ";
    }
    else
    {
        // Other types (e.g. streamflow): show full descriptive statistics
        $html_stats_graph .= "
            <table id='table_tri' style='font-size:12px;' >
                <tr>
                    <th style='width:150px;text-align:center;font-size:12px;'><span>" . TEXT_STATS_MINIMUM . "</span><br><span style='font-weight:normal;font-size:13px;color:#555;'>" . $date_min_val . "</span></th>
                    <th style='width:150px;text-align:center;font-size:12px;'><span>" . TEXT_STATS_QUARTILE_25 . "</span></th>
                    <th style='width:150px;text-align:center;font-size:12px;'><span>" . TEXT_STATS_MEDIAN . "</span></th>
                    <th style='width:150px;text-align:center;font-size:12px;'><span>" . TEXT_STATS_QUARTILE_75 . "</span></th>
                    <th style='width:150px;text-align:center;font-size:12px;'><span>" . TEXT_STATS_MAXIMUM . "</span><br><span style='font-weight:normal;font-size:13px;color:#555;'>" . $date_max_val . "</span></th>
                    <th style='width:150px;text-align:center;font-size:12px;color:#930000;'><span>" . TEXT_STATS_MEAN . "</span></th>
                    <th style='width:150px;text-align:center;font-size:12px;color:#930000;'><span>" . TEXT_STATS_STD_DEV . "</span></th>
                </tr>
                <tr>
                    <td style='text-align:center;'>" . $min_all_format . "</td>
                    <td style='text-align:center;'>" . $p25_format . "</td>
                    <td style='text-align:center;'>" . $p50_format . "</td>
                    <td style='text-align:center;'>" . $p75_format . "</td>
                    <td style='text-align:center;'>" . $max_all_format . "</td>
                    <td style='text-align:center;'>" . $moy_all_format . "</td>
                    <td style='text-align:center;'>" . $std_all_format . "</td>
                </tr>
            </table>
        ";
    }


    // -----------------------------------------------------------------
    // Complementary indicators (shown as a 2-column table under General Data,
    // styled exactly like #table_tri for a uniform look)
    // -----------------------------------------------------------------

    // Effective sample size (already loaded in $data_tab).
    $nb_values = count($data_tab);

    // Completeness = measured days / calendar days over the period.
    $d1 = new DateTime($format_min_x);
    $d2 = new DateTime($format_max_x);
    $nb_days_period = $d1->diff($d2)->days + 1;
    $completeness   = ($nb_days_period > 0)
                    ? round($nb_values / $nb_days_period * 100, 1)
                    : 0;

    // Coefficient of variation (std / mean), as %.
    $cv = ((float)$moy_all != 0.0)
        ? round((float)$std_all / (float)$moy_all * 100, 1)
        : 0;

    // Range (max - min).
    $etendue_format = round((float)$max_all - (float)$min_all, $nb_dec);

    // Build the indicator rows (label / value pairs).
    $ind_rows = [];
    $ind_rows[] = [TEXT_STATS_CARD_N,            number_format($nb_values, 0, '.', ' ')];
    $ind_rows[] = [TEXT_STATS_CARD_COMPLETENESS, $completeness . ' %'];

    if ($id_eq_type == 1)
    {
        // --- Rainfall: mean annual total + drought indices ---
        $nb_years     = max(1, $nb_days_period / 365.25);
        $cumul_moy_an = round((float)$cumul_all / $nb_years, $nb_dec);

        // Max consecutive days with value <= threshold (drought spells),
        // scanned in date order. Thresholds: <= 1, <= 5, <= 20 mm.
        $max_le1 = $max_le5 = $max_le20 = 0;
        $cur_le1 = $cur_le5 = $cur_le20 = 0;
        $q_dry = tep_db_query($sql_link,
            "SELECT ABS(da.valeur) AS v
             FROM " . TABLE_DATA_ALL  . " da
             JOIN " . TABLE_DATA_META . " dm ON da.id_meta = dm.id
             WHERE dm.id_typedata = " . $id_typedata . "
             AND   dm.id_station  = " . $cle_station . "
             AND   da.dateheure  >= '" . $format_min_x . "'
             AND   da.dateheure  <= '" . $format_max_x . "'
             AND   da.valeur NOT IN (9999, -9999, 8888, -8888, 99999, -99999, 88888, -88888)
             ORDER BY da.dateheure ASC");
        while ($r = tep_db_fetch_array($q_dry)) {
            $v = (float)$r['v'];
            if ($v <= 1)  { $cur_le1++;  if ($cur_le1  > $max_le1)  { $max_le1  = $cur_le1;  } } else { $cur_le1  = 0; }
            if ($v <= 5)  { $cur_le5++;  if ($cur_le5  > $max_le5)  { $max_le5  = $cur_le5;  } } else { $cur_le5  = 0; }
            if ($v <= 20) { $cur_le20++; if ($cur_le20 > $max_le20) { $max_le20 = $cur_le20; } } else { $cur_le20 = 0; }
        }

        $u = ' ' . TEXT_STATS_CARD_DAYS_UNIT;
        $ind_rows[] = [TEXT_STATS_CARD_CUMUL_YEAR, $cumul_moy_an . ' ' . $axe_unite];
        $ind_rows[] = [TEXT_STATS_CARD_DRY_LE1,    $max_le1  . $u];
        $ind_rows[] = [TEXT_STATS_CARD_DRY_LE5,    $max_le5  . $u];
        $ind_rows[] = [TEXT_STATS_CARD_DRY_LE20,   $max_le20 . $u];
    }
    else
    {
        // --- Streamflow / generic: CV + range ---
        $ind_rows[] = [TEXT_STATS_CARD_CV,    $cv . ' %'];
        $ind_rows[] = [TEXT_STATS_CARD_RANGE, $etendue_format . ' ' . $axe_unite];
    }

    // Render as a uniform 2-column table (Indicator | Value).
    $html_stats_graph .= "
        <p class='info_stats' style='margin-top:25px;'>
            " . TEXT_STATS_COMPLEMENTARY . "
        </p>
        <table id='table_tri' style='font-size:12px;max-width:520px;'>
            <tr>
                <th style='text-align:left;font-size:12px;'><span>" . TEXT_STATS_INDICATOR . "</span></th>
                <th style='text-align:right;font-size:12px;'><span>" . TEXT_STATS_VALUE . "</span></th>
            </tr>
    ";
    $rr = 0;
    foreach ($ind_rows as $ir) {
        $cls = (fmod($rr, 2) == 0) ? 'row1' : 'row2';
        $html_stats_graph .= "
            <tr class='" . $cls . "'>
                <td style='text-align:left;'>" . $ir[0] . "</td>
                <td style='text-align:right;font-weight:bold;'>" . $ir[1] . "</td>
            </tr>";
        $rr++;
    }
    $html_stats_graph .= "</table>";


    // GUMBEL RETURN PERIOD ANALYSIS — Only for flood frequency (INIT_T == 'PF')
    if (INIT_T != 'NC')
    {
        // Query: annual maxima for Gumbel fitting
        $sql_data = "
                        SELECT
                            YEAR(da.dateheure) as annee,
                            MAX(ABS(da.valeur)) as max_valeur
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
                            YEAR(da.dateheure)
                        ORDER BY
                            annee ASC
                        ";

        $maxima_annuels = [];
        $data_query     = tep_db_query($sql_link, $sql_data);
        while ($data_tab = tep_db_fetch_array($data_query))
        {
            $maxima_annuels[] = (float)$data_tab['max_valeur'];
        }

        // Sort in ascending order for non-exceedance probability
        sort($maxima_annuels);
        $n = count($maxima_annuels);

        if ($n > 5)
        {
            // Series statistics
            $mean              = round(mean($maxima_annuels), $nb_dec);
            $standardDeviation = round(sqrt(variance($maxima_annuels)), $nb_dec);

            // Gumbel distribution parameters
            $eulerConstant = 0.5772156649;
            $a = (sqrt(6) * $standardDeviation) / pi(); // Scale parameter
            $u = $mean - ($eulerConstant * $a);          // Location parameter

            // Standard errors for confidence intervals
            $se_u = ($a / sqrt($n)) * 1.0806;  // SE of location parameter
            $se_a = ($a / sqrt($n)) * 0.8944;  // SE of scale parameter

            $z        = 1.96; // 95% CI
            $IC_bas_u  = $u - ($z * $se_u);
            $IC_haut_u = $u + ($z * $se_u);
            $IC_bas_a  = max(0.0001, $a - ($z * $se_a));
            $IC_haut_a = $a + ($z * $se_a);

            // Return period values
            $periodes_tab = [2, 5, 10, 20, 30, 40, 50, 100];

            foreach ($periodes_tab as $T)
            {
                $p            = 1 - (1 / $T);
                $valeur_xT    = $u - ($a * log(-log($p)));
                $valeurs_T[$T] = $valeur_xT;
            }

            // Generate theoretical curve points (100 points)
            $x_obs = [];
            $y_obs = [];
            foreach ($maxima_annuels as $index => $valeur)
            {
                $x_obs[] = (($index + 1) - 0.5) / $n; // Hazen plotting position
                $y_obs[] = $valeur;
            }

            // Build theoretical curve and confidence interval
            $x_theo   = [];
            $y_theo   = [];
            $y_ic_haut = [];
            $y_ic_bas  = [];

            for ($i = 1; $i <= 99; $i++)
            {
                $p     = $i / 100;
                $y_p   = -log(-log($p));
                $val_x_p = $u + ($a * $y_p);
                $se    = ($a / sqrt($n)) * sqrt(1 + 1.1396 * $y_p + 1.1 * pow($y_p, 2));

                $x_theo[]   = $p;
                $y_theo[]   = $val_x_p;
                $y_ic_haut[] = $val_x_p + (1.96 * $se);
                $y_ic_bas[]  = max(0, $val_x_p - (1.96 * $se));
            }

            // Observed data trace
            $traces_array[] = [
                'x'    => $x_obs,
                'y'    => $y_obs,
                'mode' => 'markers',
                'name' => TEXT_STATS_OBSERVED_DATA,
                'marker' => ['color' => '#000', 'size' => 3, 'symbol' => 'circle', 'line' => ['width' => 0]],
                'type' => 'scatter',
                'hovertemplate' => '<b>' . TEXT_STATS_NON_EXCEEDANCE_PROB . '</b> : %{x:.1f} % <br><b>' . $axe_name . '</b> : %{y:.' . $nb_dec . 'f} (' . $axe_unite . ')'
            ];

            // Confidence interval ribbon trace
            $traces_array[] = [
                'x'         => array_merge($x_theo, array_reverse($x_theo)),
                'y'         => array_merge($y_ic_haut, array_reverse($y_ic_bas)),
                'fill'      => 'toself',
                'fillcolor' => 'rgba(173, 216, 230, 0.3)',
                'line'      => ['color' => 'transparent'],
                'showlegend' => false,
                'mode'      => 'line',
                'name'      => TEXT_STATS_CI_95
            ];

            // Theoretical Gumbel curve trace
            $traces_array[] = [
                'x'    => $x_theo,
                'y'    => $y_theo,
                'mode' => 'lines',
                'name' => TEXT_STATS_GUMBEL_LAW,
                'line' => ['color' => '#86B0BD', 'width' => 2],
                'type' => 'scatter',
                'hovertemplate' => '<b>' . TEXT_STATS_NON_EXCEEDANCE_PROB . '</b> : %{x:.1f} % <br><b>' . $axe_name . '</b> : %{y:.' . $nb_dec . 'f} (' . $axe_unite . ')'
            ];

            $json_traces = json_encode($traces_array);
            $max_y_val   = max($y_obs) * 1.1;

            // Build Plotly.js chart code for Gumbel
            $js_graph .= "
                const dataGumbel = " . $json_traces . ";

                const layoutGumbel =
                    {
                        xaxis: {
                            title: { text: '" . TEXT_STATS_NON_EXCEEDANCE_FREQ . "', standoff: 5 },
                            showgrid: true, gridcolor: '#ddd', gridwidth: 1,
                            dtick: 0.1, tickformat: '.1f',
                            titlefont: {family: 'roboto, arial, helvetica', size: 10, bold: true, color: '#000000'},
                            tickangle: 0, ticklen: 5, showline: true, linewidth: 1,
                            fixedrange: true, range: [0,1]
                        },
                        yaxis: {
                            title: { text: '" . $axe_name . " (" . $axe_unite . ")', standoff: 5 },
                            titlefont: {family: 'roboto, arial, helvetica', size: 10, bold: true, color: '#000000'},
                            showline: true, linewidth: 1, automargin: true, fixedrange: true
                        },
                        showlegend: false,
                        hovermode:'xy',
                        hoverlabel: { bgcolor: '#fff', font: { size: 10, color: '#000' } },
                        margin: {l: 60, r: 10, t: 30, b:50}
                    };

                var configGumbel = {
                    responsive: true, scrollZoom: false, displaylogo: false,
                    displayModeBar: true,
                    modeBarButtons: [[
                        {
                            name: 'Export Data CSV', icon: Plotly.Icons.disk, direction: 'up',
                            click: function(gd) {
                                var data = gd.data; var sep = ';'; var csvContent = '';
                                if (data.length === 0) { return; }
                                var allUniqueX = new Set();
                                data.forEach(function(trace) {
                                    if (Array.isArray(trace.x) && trace.x.length > 0) {
                                        trace.x.forEach(function(xVal) { allUniqueX.add(xVal); });
                                    }
                                });
                                var masterX = Array.from(allUniqueX).sort();
                                if (masterX.length === 0) { return; }
                                var lookupMaps = data.map(function(trace) {
                                    var map = new Map();
                                    for (var i = 0; i < (trace.x ? trace.x.length : 0); i++) {
                                        map.set(trace.x[i], {y: trace.y[i]});
                                    }
                                    return map;
                                });
                                var header = ['X'];
                                data.forEach(function(trace, index) {
                                    header.push('Y (' + (trace.name || 'Trace ' + (index + 1)) + ')');
                                });
                                csvContent += header.join(sep) + '\\r\\n';
                                masterX.forEach(function(xVal) {
                                    var row = [Number(xVal).toFixed(3)];
                                    lookupMaps.forEach(function(map) {
                                        var point = map.get(xVal);
                                        row.push(point ? Number(point.y).toFixed(3) : '');
                                    });
                                    csvContent += row.join(sep) + '\\r\\n';
                                });
                                var blob = new Blob(['\\uFEFF' + csvContent], { type: 'text/csv;charset=utf-8;' });
                                var url = URL.createObjectURL(blob);
                                var link = document.createElement('a');
                                link.setAttribute('href', url);
                                link.setAttribute('download', 'HP-Data_TimePeriod.csv');
                                document.body.appendChild(link); link.click(); document.body.removeChild(link);
                            }
                        },
                        {
                            name: 'Export SVG', icon: Plotly.Icons.pencil,
                            click: function(gd) { Plotly.downloadImage(gd, {format: 'svg', filename: 'graph_timeperiod'}); }
                        },
                        {
                            name: 'Export PNG', icon: Plotly.Icons.camera,
                            click: function(gd) { Plotly.downloadImage(gd, {format: 'png', filename: 'graph_timeperiod'}); }
                        }
                    ]],
                    modeBarButtonsToRemove: ['select2d', 'lasso2d', 'autoScale2d', 'zoomIn2d', 'zoom2d','pan2d','resetScale2d', 'zoomOut2d']
                };

                Plotly.newPlot('plotGumbel', dataGumbel, layoutGumbel, configGumbel);
            ";

            // HTML — Return period table and Gumbel chart
            $html_stats .= "
                    <div style=''>
                        <div style='float:left;margin-bottom:25px;'>

                            <p class='info_stats'>
                                " . TEXT_STATS_EXTREME_RETURN . "
                            </p>

                            <div style='margin-bottom:25px;'>
                                <table id='table_tri' style='font-size:12px;'>
                                    <tr>
                                        <th style='width:80px; text-align:left; border-bottom: 1px solid #ddd;'></th>";

                                        foreach ($periodes_tab as $T)
                                        {
                                            $html_stats .= "
                                                <th style='width:90px; text-align:center; border-bottom: 1px solid #ddd;'>
                                                    " . $T . " " . TEXT_STATS_YEARS . "
                                                </th>";
                                        }

                                $html_stats .= "
                                    </tr>
                                    <tr style=''>
                                        <td style='height:20px;text-align:center;font-weight:bold;'>" . $axe_name . " (" . $axe_unite . ")</td>";

                                        foreach ($periodes_tab as $T)
                                        {
                                            $html_stats .= "
                                                <td style='height:20px;text-align:center;'>
                                                    " . round($valeurs_T[$T], $nb_dec) . "
                                                </td>";
                                        }

                                $html_stats .= "
                                    </tr>
                                    <tr style=''>
                                        <td style='height:20px;text-align:center;font-weight:bold;'>" . TEXT_STATS_CI_95 . "</td>";

                                        foreach ($periodes_tab as $T)
                                        {
                                            $p_T   = 1 - (1 / $T);
                                            $y_T   = -log(-log($p_T));
                                            $x_T   = $u + ($a * $y_T);
                                            $se_T  = ($a / sqrt($n)) * sqrt(1 + 1.1396 * $y_T + 1.1 * pow($y_T, 2));
                                            $IC_inf = max(0, $x_T - (1.96 * $se_T));
                                            $IC_sup = $x_T + (1.96 * $se_T);

                                            $html_stats .= "
                                                <td style='height:20px;text-align:center;font-size:10px;'>
                                                    [" . round($IC_inf, $nb_dec) . " ; " . round($IC_sup, $nb_dec) . "]
                                                </td>";
                                        }

                                $html_stats .= "
                                    </tr>
                                </table>
                            </div>

                            <p class='info_stats' style='margin-top:45px;'>
                                " . TEXT_STATS_GUMBEL_PARAMS . "
                            </p>

                            <div style=''>
                                <table id='table_tri' style='font-size:12px;'>
                                    <tr>
                                        <th style='border-bottom: 1px solid #ddd;'>&nbsp;</th>
                                        <th style='width:80px; text-align:center;border-bottom: 1px solid #ddd;'>" . TEXT_STATS_ESTIMATE . "</th>
                                        <th style='width:80px; text-align:center;border-bottom: 1px solid #ddd;'>" . TEXT_STATS_STD_ERROR . "</th>
                                        <th style='width:80px; text-align:center;border-bottom: 1px solid #ddd;'>" . TEXT_STATS_CI_LOW . "</th>
                                        <th style='width:80px; text-align:center;border-bottom: 1px solid #ddd;'>" . TEXT_STATS_CI_HIGH . "</th>
                                    </tr>
                                    <tr>
                                        <td style='height:20px; text-align:left; font-weight:bold;'>" . TEXT_STATS_GUMBEL_U . "</td>
                                        <td style='text-align:center;'>" . round($u, $nb_dec) . "</td>
                                        <td style='text-align:center; color:#666;'>" . round($se_u, $nb_dec) . "</td>
                                        <td style='text-align:center;'>" . round($IC_bas_u, $nb_dec) . "</td>
                                        <td style='text-align:center;'>" . round($IC_haut_u, $nb_dec) . "</td>
                                    </tr>
                                    <tr>
                                        <td style='height:20px; text-align:left; font-weight:bold;'>" . TEXT_STATS_GUMBEL_A . "</td>
                                        <td style='text-align:center;'>" . round($a, $nb_dec) . "</td>
                                        <td style='text-align:center; color:#666;'>" . round($se_a, $nb_dec) . "</td>
                                        <td style='text-align:center;'>" . round($IC_bas_a, $nb_dec) . "</td>
                                        <td style='text-align:center;'>" . round($IC_haut_a, $nb_dec) . "</td>
                                    </tr>
                                </table>
                            </div>

                        </div>

                        <div style='float:left;margin-left:4%;width:400px;'>
                            <p class='info_stats'>
                                " . TEXT_STATS_GUMBEL_CHART . "
                            </p>
                            <div id='plotGumbel' class='graph_stats' style='height:250px;'></div>
                        </div>

                    <hr>
                    </div>
                    ";
        }
    }


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