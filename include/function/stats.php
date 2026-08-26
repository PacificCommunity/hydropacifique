<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
All functions related to statistical computations
*/


// --------------------------------------------------------
// I. GENERAL STATISTICS
// --------------------------------------------------------

    // YEAR

    // Build the statistics summary table (last year / 10 years / all time)
    function statsEditYear($sql_link,$id_station,$type_chron_years) 
    {
        $results_lastyear = stats_lastyear($sql_link,$id_station,$type_chron_years);
        $results_10years = stats_10years($sql_link,$id_station,$type_chron_years);
        $results_Allyears = stats_Allyears($sql_link,$id_station,$type_chron_years);

        $textedit_Tab = "";

            $textedit_Tab .= "<table style='width:100%;' >";
                $textedit_Tab .= "<tr>";
                    $textedit_Tab .= "<td>&nbsp;</td>";
                    $textedit_Tab .= "<td style='width:150px;'><span>".htmlaccent('Dernière année')."</span></td>";
                    $textedit_Tab .= "<td style='width:150px;'><span>".htmlaccent('10 dernière années')."</span></td>";
                    $textedit_Tab .= "<td style='width:150px;'><span>".htmlaccent('Tous le temps')."</span></td>";
                $textedit_Tab .= "</tr>";

                $textedit_Tab .= "<tr>";
                    $textedit_Tab .= "<td><span>".htmlaccent('Moy.')."</span></td>";
                    $textedit_Tab .= "<td>".$results_lastyear['moy']."</td>";
                    $textedit_Tab .= "<td>".$results_10years['moy']."</td>";
                    $textedit_Tab .= "<td>".$results_Allyears['moy']."</td>";
                $textedit_Tab .= "</tr>";

                $textedit_Tab .= "<tr>";
                    $textedit_Tab .= "<td><span>".htmlaccent('Max.')."</span></td>";
                    $textedit_Tab .= "<td>-</td>";
                    $textedit_Tab .= "<td>".$results_10years['max']."</td>";
                    $textedit_Tab .= "<td>".$results_Allyears['max']."</td>";
                $textedit_Tab .= "</tr>";

                $textedit_Tab .= "<tr>";
                    $textedit_Tab .= "<td><span>".htmlaccent('Min.')."</span></td>";
                    $textedit_Tab .= "<td>-</td>";
                    $textedit_Tab .= "<td>".$results_10years['min']."</td>";
                    $textedit_Tab .= "<td>".$results_Allyears['min']."</td>";
                $textedit_Tab .= "</tr>";

                $textedit_Tab .= "<tr>";
                    $textedit_Tab .= "<td><span>".htmlaccent('Période')."</span></td>";
                    $textedit_Tab .= "<td>".$results_lastyear['year']."</td>";
                    $textedit_Tab .= "<td>".$results_10years['annee_min']." - ".$results_10years['annee_max']."</td>";
                    $textedit_Tab .= "<td>".$results_Allyears['annee_min']." - ".$results_Allyears['annee_max']."</td>";
                $textedit_Tab .= "</tr>";

            $textedit_Tab .= "</table>";


        return $textedit_Tab;
    }

    // Statistics for the most recent year
    function stats_lastyear($sql_link,$id_station,$type_chron) 
    {
        $results_lastyear = array(); // Initialize the results array

        $results_lastyear['moy'] = '-';   
        $results_lastyear['min'] = '-';  
        $results_lastyear['max'] = '-';  
        $results_lastyear['year'] = '-';  
        
        // SQL query for the statistics
        $sql_stats_lastyear = "
        SELECT 
            YEAR(MAX(da.dateheure)) AS year,
            AVG(da.valeur) AS moy,  -- Calcule la moyenne des valeurs
            MIN(da.valeur) AS min,
            MAX(da.valeur) AS max
        FROM 
            ".TABLE_DATA_ALL." da
        JOIN 
            ".TABLE_DATA_META." dm ON da.id_meta=dm.id
        WHERE 
            dm.id_typedata = ".$type_chron."
            AND dm.id_station = ".$id_station."
            AND da.valeur > 0 
            AND da.valeur <= 99999 -- pour ne pas prendre en compte les lacunes
        GROUP BY YEAR(da.dateheure) 
        ORDER BY da.dateheure DESC
        ";

        $stats_lastyear_query = tep_db_query($sql_link,$sql_stats_lastyear);
        $stats_lastyear_tab = tep_db_fetch_array($stats_lastyear_query);
        
        if(isset($stats_lastyear_tab) && isset($stats_lastyear_tab['moy']))
        {
            $results_lastyear['moy'] = number_format((float)$stats_lastyear_tab['moy'], 3, '.', ' ');   
            $results_lastyear['min'] = number_format((float)$stats_lastyear_tab['min'], 3, '.', ' ');  
            $results_lastyear['max'] = number_format((float)$stats_lastyear_tab['max'], 3, '.', ' ');  
            $results_lastyear['year'] = $stats_lastyear_tab['year'];
        }

        return $results_lastyear;
    }

    // Statistics over the last 10 years relative to the current year
    function stats_10years($sql_link,$id_station,$type_chron) 
    {
        
        $results_10years = array(); // Initialize the results array

        // SQL query for the statistics
        $sql_stats_10years = "
        SELECT
            AVG(da.valeur) AS moy,
            MIN(da.valeur) AS min,
            MAX(da.valeur) AS max,
            COUNT(da.valeur) AS nb_data,
            MIN(YEAR(da.dateheure)) AS annee_min,
            MAX(YEAR(da.dateheure)) AS annee_max
        FROM 
            ".TABLE_DATA_ALL." da
        JOIN 
            ".TABLE_DATA_META." dm ON da.id_meta=dm.id
        WHERE 
            dm.id_typedata = ".$type_chron."
            AND dm.id_station = ".$id_station."
            AND da.valeur > 0
            AND da.valeur <= 99999 -- pour ne pas prendre en compte les lacunes
            AND YEAR(da.dateheure) >= YEAR(CURRENT_DATE) - 10
        ORDER BY 
            da.dateheure DESC;
        ";


        $stats_10years_query = tep_db_query($sql_link,$sql_stats_10years);
        $stats_10years_tab = tep_db_fetch_array($stats_10years_query);

        if(isset($stats_10years_tab))
        {
            $results_10years['moy'] = number_format((float)$stats_10years_tab['moy'], 3, '.', ' ');
            $results_10years['max'] = number_format((float)$stats_10years_tab['max'], 3, '.', ' '); 
            $results_10years['min'] = number_format((float)$stats_10years_tab['min'], 3, '.', ' ');   
            $results_10years['annee_min'] = $stats_10years_tab['annee_min'];  
            $results_10years['annee_max'] = $stats_10years_tab['annee_max'];  
            $results_10years['nb_data'] = number_format((float)$stats_10years_tab['nb_data'], 0, '.', ' ');    
        }

        return $results_10years;

    }

    // Statistics over the whole record
    function stats_Allyears($sql_link,$id_station,$type_chron) 
    {
        
        $results_Allyears = array(); // Initialize the results array

        // SQL query for the statistics
        $sql_stats_Allyears = "
        SELECT
            AVG(da.valeur) AS moy,
            MIN(da.valeur) AS min,
            MAX(da.valeur) AS max,
            COUNT(da.valeur) AS nb_data,
            MIN(YEAR(da.dateheure)) AS annee_min,
            MAX(YEAR(da.dateheure)) AS annee_max
        FROM 
            ".TABLE_DATA_ALL." da
        JOIN 
            ".TABLE_DATA_META." dm ON da.id_meta=dm.id
        WHERE 
            dm.id_typedata = ".$type_chron."
            AND dm.id_station = ".$id_station."
            AND da.valeur > 0
            AND da.valeur <= 99999 -- pour ne pas prendre en compte les lacunes
        ORDER BY 
            da.dateheure DESC;
        ";

        $stats_Allyears_query = tep_db_query($sql_link,$sql_stats_Allyears);
        $stats_Allyears_tab = tep_db_fetch_array($stats_Allyears_query);

        if(isset($stats_Allyears_tab))
        {
            $results_Allyears['moy'] = number_format((float)$stats_Allyears_tab['moy'], 3, '.', ' ');
            $results_Allyears['max'] = number_format((float)$stats_Allyears_tab['max'], 3, '.', ' '); 
            $results_Allyears['min'] = number_format((float)$stats_Allyears_tab['min'], 3, '.', ' ');   
            $results_Allyears['annee_min'] = $stats_Allyears_tab['annee_min'];  
            $results_Allyears['annee_max'] = $stats_Allyears_tab['annee_max'];  
            $results_Allyears['nb_data'] = number_format((float)$stats_Allyears_tab['nb_data'], 0, '.', ' ');  
        }

        return $results_Allyears;

    }


    // MONTH

    // Build the monthly statistics summary table
    function statsEditMonth($sql_link,$id_station,$type_chron_years) {

        //$results_lastyear = stats_lastyearMonth($sql_link,$id_station,$type_chron_years);
        /*
        $results_10years = stats_10years($sql_link,$id_station,$type_chron_years);
        $results_Allyears = stats_Allyears($sql_link,$id_station,$type_chron_years);
        */

        $textedit_Tab = "";

            $textedit_Tab .= "<table style=\"width:80%;\" id=\"tab_stat_map\">";
                $textedit_Tab .= "<tr>";
                    $textedit_Tab .= "<td>&nbsp;</td>";
                    $textedit_Tab .= "<td><span>".htmlaccent("Last Year")."</span></td>";
                    $textedit_Tab .= "<td><span>".htmlaccent("10 ans")."</span></td>";
                    $textedit_Tab .= "<td><span>".htmlaccent("All")."</span></td>";
                $textedit_Tab .= "</tr>";

                $textedit_Tab .= "<tr>";
                    $textedit_Tab .= "<td><span>".htmlaccent("Moy.")."</span></td>";
                    //$textedit_Tab .= "<td>".$results_lastyear["moy"]."</td>";
                    $textedit_Tab .= "<td>-</td>";
                    $textedit_Tab .= "<td>-</td>";
                    $textedit_Tab .= "<td>-</td>";
                $textedit_Tab .= "</tr>";

                $textedit_Tab .= "<tr>";
                    $textedit_Tab .= "<td><span>".htmlaccent("Max.")."</span></td>";
                    $textedit_Tab .= "<td>-</td>";
                    $textedit_Tab .= "<td>-</td>";
                    $textedit_Tab .= "<td>-</td>";
                $textedit_Tab .= "</tr>";

                $textedit_Tab .= "<tr>";
                    $textedit_Tab .= "<td><span>".htmlaccent("Min.")."</span></td>";
                    $textedit_Tab .= "<td>-</td>";
                    $textedit_Tab .= "<td>-</td>";
                    $textedit_Tab .= "<td>-</td>";
                $textedit_Tab .= "</tr>";

                $textedit_Tab .= "<tr>";
                    $textedit_Tab .= "<td><span>".htmlaccent("Info.")."</span></td>";
                    //$textedit_Tab .= "<td>".$results_lastyear["last_year"]." - ".$results_lastyear["nb_data"]." mois</td>";
                    $textedit_Tab .= "<td>-</td>";
                        $textedit_Tab .= "<td>-</td>";
                    $textedit_Tab .= "<td>-</td>";
                $textedit_Tab .= "</tr>";

            $textedit_Tab .= "</table>";


        return $textedit_Tab;
    }


    // Monthly statistics for the most recent year
    function stats_lastyearMonth($sql_link,$id_station,$type_chron) {
        
        $results_lastyear = array(); // Initialize the results array

        // SQL query for the statistics
        $sql_stats_lastyear = "
        SELECT 
            MAX(YEAR(da.dateheure)) AS last_year,
            AVG(da.valeur) AS moy,
            COUNT(da.valeur) AS nb_data
        FROM 
            ".TABLE_DATA_ALL." da
        JOIN 
            ".TABLE_DATA_META." dm ON da.id_meta=dm.id
        WHERE 
            dm.id_typedata = ".$type_chron."
            AND dm.id_station = ".$id_station."
            AND da.valeur > 0
            AND YEAR(da.dateheure) = (
                                        SELECT MAX(YEAR(da_inner.dateheure))
                                        FROM ".TABLE_DATA_ALL." da_inner
                                        JOIN ".TABLE_DATA_META." dm_inner ON da_inner.id_meta = dm_inner.id
                                        WHERE dm_inner.id_typedata = dm.id_typedata
                                        AND dm_inner.id_station = dm.id_station
                                    )
        ";

        $stats_lastyear_query = tep_db_query($sql_link,$sql_stats_lastyear);
        $stats_lastyear_tab = tep_db_fetch_array($stats_lastyear_query);

        if(isset($stats_lastyear_tab))
        {
            $results_lastyear["moy"] = number_format((float)$stats_lastyear_tab["moy"], 3, ".", "");   
        //$results_lastyear["month"] = $stats_lastyear_tab["month"];
            $results_lastyear["last_year"] = $stats_lastyear_tab["last_year"];
            $results_lastyear["nb_data"] = $stats_lastyear_tab["nb_data"];
        }

        return $results_lastyear;
    }



// --------------------------------------------------------
// II. LOW-FLOW STATISTICS
// --------------------------------------------------------


    
    use MathPHP\Statistics\Descriptive;
    use MathPHP\Probability\Distribution\Continuous\LogNormal;
    use MathPHP\Probability\Distribution\Continuous\Normal;
    use MathPHP\Probability\Distribution\Continuous\StudentT;
    use MathPHP\Probability\Distribution\Continuous\ChiSquared;
    //use MathPHP\Probability\Distribution\Table\ChiSquared as TableChiSquared;

    // ------------
    // PERCENTILE - 10th of module (P10), 20th of module (P20)
    // ------------
    function calculate_percentile(array $data_array, float $percentile): float 
    {
        // Descriptive::percentile() returns the percentile of a series.
        // Values must be numeric.
        try {
            return Descriptive::percentile($data_array, $percentile);
        } catch (\Exception $e) {
            return 0.0;
        }
    }

    // ------------
    // QMNA
    // ------------

     
    // Compute the Annual Minimum Monthly Flow (QMNA) for each year of the record.
    // @param array $Qj_data_by_date Associative array ['YYYY-MM-DD' => Qj] of daily flows.
    // @return array Associative array ['YYYY' => annual_QMNA] of computed QMNA values.    
    function calculate_qmna_annual(array $Qj_data_by_date): array 
    {
        $data_grouped = [];
        $qmnas_annuels = [];

        // Group daily flows by year and month
        foreach ($Qj_data_by_date as $date_str => $qj) {
            // Use DateTime to extract year and month (robust method)
            $date_obj = new DateTime($date_str);
            $year = $date_obj->format('Y');
            $month = $date_obj->format('m');
            
            // Store the daily flow in its year/month bucket
            $data_grouped[$year][$month][] = $qj;
        }

        // Compute the QMNA for each year
        foreach ($data_grouped as $year => $months_data) {
            $monthly_averages = [];
            
            // Compute the mean for each month of the year
            foreach ($months_data as $month => $qj_values) {
                // Compute the monthly mean
                $monthly_averages[$month] = array_sum($qj_values) / count($qj_values);
            }

            // The QMNA is the minimum of all monthly means for that year
            if (!empty($monthly_averages)) {
                $qmnas_annuels[$year] = min($monthly_averages);
            }
        }
        
        return $qmnas_annuels;
    }

    // --- Usage example ---
    // $qmna_annuels = calculate_qmna_annual($Qj_data); 
    // $qmna_annuels will contain [ '2023' => 0.54, '2024' => 0.61, ... ]


    // ------------
    // DCE (Q355)
    // Characteristic Low-flow Discharge (DCE), also called DCE355, 
    // is the minimum daily flow equalled or not exceeded over 10 days within a year. 
    // It is the lowest flow observed over a 10-day period in the year.
    // ------------
    function calculate_dce_annual(array $Qj_data_by_date, int $N_days_annual): array 
    {
        $qnd_annuels = [];
        $data_grouped = [];

        // 1. Group daily flows by year 
        foreach ($Qj_data_by_date as $date_str => $qj) {
            $year = (new DateTime($date_str))->format('Y');
            $data_grouped[$year][] = $qj;
        }

        // 2. Compute the Q_ND N/year for each year
        foreach ($data_grouped as $year => $qj_values) {
            $N_days_in_year = count($qj_values);
            
            // --- Validation ---
            // Must have at least N_days_annual days in the year.
            if ($N_days_in_year < $N_days_annual) {
                continue; 
            }

            // --- Sort (crucial step) ---
            // Sort the year's daily flow series in ASCENDING order.
            sort($qj_values); 
            
            // --- Determine the target rank ---
            // Rank R is the flow not exceeded R times.
            // For a 10-day non-exceedance, we take the 10th lowest flow.
            $R_cible = $N_days_annual;
            
            // Ensure the rank is within array bounds
            if ($R_cible <= 0 || $R_cible > $N_days_in_year) {
                continue;
            }

            // --- Identify the flow ---
            // PHP index is R_cible - 1
            $Q_ND_annual = $qj_values[$R_cible - 1]; 

            $qnd_annuels[$year] = $Q_ND_annual;
        }        
        return $qnd_annuels;
    }

    // Usage example:
    // $qnd10_annuels = calculate_qnd_annual($Qj_data, 10);

    // ------------
    // QMNA
    // ------------


    // Compute the annual series of Minimum Consecutive Volumes (VCN-N) for a duration N.
    // @param array $qj_array Daily flow series (Qj) in chronological order.
    // @param array $dates_array Dates matching the Qj values.
    // @param int $N_days Sliding window length (e.g. 3, 7, 10, 30 days).
    // @return array Associative array ['YYYY' => annual VCN-N].
    
    function calculate_vcn_series(array $qj_array, array $dates_array, int $N_days): array 
    {
        // Stores the minimum VCN found for each year.
        $min_annual_vcn = [];
        $total_days = count($qj_array);

        // Robustness check: ensure there are enough days to form the window.
        if ($total_days < $N_days) 
        {
            return [];
        }

        // Main loop using the sliding-window approach.
        // The loop stops once the last complete N-day window can be formed.
        for ($i = 0; $i <= $total_days - $N_days; $i++) 
        {
            // Determine the reference year from the END date of the window.
            // Convention: assign the minimum to the year the event ends in.
            $end_date_str = $dates_array[$i + $N_days - 1];
            $annee_ref = (new DateTime($end_date_str))->format('Y');

            // Extract the N consecutive days starting at index $i.
            $window = array_slice($qj_array, $i, $N_days);
            
            // Compute the mean flow (VCN) of this window.
            $current_average = array_sum($window) / $N_days;

            // Update the annual minimum found so far (this year's VCN-N).
            // If the year is not recorded yet OR the current mean is smaller
            if (!isset($min_annual_vcn[$annee_ref]) || $current_average < $min_annual_vcn[$annee_ref]) {
                $min_annual_vcn[$annee_ref] = $current_average;
            }
        }
        
        // Return the annual VCN-N series (minimum mean flows over N days).
        return $min_annual_vcn; 
    }       


    // ------------
    // LOG-NORMAL FUNCTION
    // ------------

     
    // Compute the return-period-T flow (Qt) by fitting the annual series to a Log-Normal law.
    // @param array $annual_minima Annual minima series (e.g. annual QMNA).
    // @param float $Target_T Target return period (e.g. 2.0 or 5.0).
    // @return float The estimated Qt flow.
  
    
    function calculate_low_flow_statistics(array $annual_minima, float $Target_T): array 
    {
        // --- 1. Data preparation and moment computation ---
    
        $Q_series = array_filter($annual_minima, fn($Q) => $Q > 0);
        $Y_series = array_map('log', $Q_series); // Logarithmic transform: Y = ln(Q)

        $N = count($Y_series);
        if ($N < 5) 
        {
            return ['error' => 'Not enough data (N < 5)'];
        }

        try {
            $mu_Y    = mean($Y_series);        // Mean (mu_Y)
            $sigma_Y = Descriptive::sd($Y_series);          // Standard deviation (sigma_Y)
        } catch (\Exception $e) {
            // Handle failure (e.g. fewer than 2 points for the standard deviation)
            return ['error' => 'Statistical moments calculation failed'];
        }

        // --- Median parameters (quantile method) ---
        $quantile_params = calculate_params_by_quantile($annual_minima);
        $mu_Y_quantile = $quantile_params['mu_quantile'];
        $sigma_Y_quantile = $quantile_params['sigma_quantile'];

        // --- 2. Compute flow Q_T and the median (Q2) ---
    
        $P_non_depassement = 1.0 / $Target_T;
        
        try {
            $logNormalDistribution = new LogNormal($mu_Y, $sigma_Y);
            
            // Estimated flow for return period T
            $Q_T = $logNormalDistribution->inverse($P_non_depassement);
            
            // Biennial / median flow (Q2)
            $Q_mediane = $logNormalDistribution->inverse(1.0 / 2.0);
            
        } catch (\Exception $e) {
            return ['error' => 'Distribution calculation failed: ' . $e->getMessage()];
        }


        // --- 3. Confidence intervals (CI) ---

        // a) CI of the Log-Normal parameters (mu_Y and sigma_Y)
        // Parameter CI (used by the upper table)
        $IC_params = calculate_ic_mu_sigma($mu_Y, $sigma_Y, $N, 0.95);

        // b) CI of flow Q_T (used by the results table)
        // Quantile Q_T CI
        $IC_QT = calculate_ic_qt($mu_Y, $sigma_Y, $N, $Target_T, 0.95);



        // --- 4. Prepare observed points for the chart ---
    
        sort($Q_series); // Sort the flows
        $points_observes = [];
        $N_q = count($Q_series);
        
        for ($i = 0; $i < $N_q; $i++) {
            // Weibull formula for the empirical frequency
            $F_i = ($i + 1) / ($N_q + 1); 
            $points_observes[] = [
                'Q' => $Q_series[$i],
                'F_empirique' => $F_i
            ];
        }



        // --- 5. Assemble the detailed output ---
    
        $output = [
            // The metric value, easily accessible
            'QT_value' => $Q_T, 
            
            // A. Log-Normal law parameters (upper table)
            'params_log' => [
                'N_points' => $N,

                'Moyenne-log-u' => $mu_Y,
                'Ecart-type-log-sigma' => $sigma_Y,

                'Mediane-log-u' => $mu_Y_quantile, 
                'Ecart-type-log-sigma_mediane' => $sigma_Y_quantile,
                
                // Parameter CI (bounds for the upper table)
                'IC_bas_mu' => $IC_params['mu_bas'],
                'IC_haut_mu' => $IC_params['mu_haut'],
                'IC_bas_sigma' => $IC_params['sigma_bas'],
                'IC_haut_sigma' => $IC_params['sigma_haut'],
            ],
            
            // B. QT metric result (lower table)
            'metrique_result' => [
                'T_ans' => $Target_T,
                'QT_valeur' => $Q_T, 
                'IC_bas' => $IC_QT['bas'],
                'IC_haut' => $IC_QT['haut'],
            ],
            
            // C. Chart data (observed points)
            'points_observes' => $points_observes
        ];

        
        return $output;
    }



    
    // Compute the confidence interval (CI) for the mean (mu) of the log-transformed series.
    // The standard-deviation CI uses the Chi-squared law.
    // @param float $mu_Y Mean of the log-flow.
    // @param float $sigma_Y Standard deviation of the log-flow.
    // @param int $N Number of points in the series.
    // @param float $confidence Confidence level (e.g. 0.95).
    // @return array ['mu_bas', 'mu_haut', 'sigma_bas', 'sigma_haut']
     
    function calculate_ic_mu_sigma(float $mu_Y, float $sigma_Y, int $N, float $confidence = 0.95): array
    {
        if ($N <= 1) {
            return ['mu_bas' => 0.0, 'mu_haut' => 0.0, 'sigma_bas' => 0.0, 'sigma_haut' => 0.0];
        }
        
        $alpha = 1.0 - $confidence;
        $degrees_of_freedom = $N - 1;

        try {
            $studentT = new StudentT($degrees_of_freedom);
            
            // Critical t value: t-law quantile for 1 - alpha/2 (e.g. t_0.025, N-1)
            // MathPHP's inverse() computes the inverse CDF.
            $t_critical = $studentT->inverse(1.0 - ($alpha / 2.0));
            
            // Standard error of the mean
            $standard_error = $sigma_Y / sqrt($N);
            
            // Margin of error
            $margin_of_error = $t_critical * $standard_error;
            
            // CI of the mean (mu_Y)
            $mu_bas = $mu_Y - $margin_of_error;
            $mu_haut = $mu_Y + $margin_of_error;

            // CI of the standard deviation (sigma_Y) via the Chi-squared law
            $chiSquare = new ChiSquared($degrees_of_freedom);
            $chi_low = $chiSquare->inverse($alpha / 2.0);
            $chi_high = $chiSquare->inverse(1.0 - ($alpha / 2.0));

            $sigma_bas = $sigma_Y * sqrt($degrees_of_freedom / $chi_high);
            $sigma_haut = $sigma_Y * sqrt($degrees_of_freedom / $chi_low);

        } catch (\Exception $e) {
            // On library failure, fall back to a wide default CI
            return [
                        'mu_bas' => $mu_Y * 0.5,
                        'mu_haut' => $mu_Y * 1.5,
                        'sigma_bas' => $sigma_Y * 0.8,
                        'sigma_haut' => $sigma_Y * 1.2
                    ];
        }
        
        
        return [
                    'mu_bas' => $mu_bas,
                    'mu_haut' => $mu_haut,
                    'sigma_bas' => $sigma_bas, 
                    'sigma_haut' => $sigma_haut,
                ];
    }



    /*
        Confidence interval (CI) for the return-period quantile Q_T.

        Uses a log-normal fit: ln(Q_T) is estimated as mu_Y + sigma_Y * K_T,
        with K_T the standard-normal quantile of the non-exceedance probability,
        and the CI is obtained by exponentiating the Student-based bounds.

        @param float $mu_Y    Mean of the log-flows (ln(Q)).
        @param float $sigma_Y Standard deviation of the log-flows (ln(Q)).
        @param int   $N        Sample size (number of years of data).
        @param float $Target_T Target return period (e.g. 5).
        @param float $confidence Confidence level (e.g. 0.95 for 95%).

        @return array ['bas' => lower bound of Q_T, 'haut' => upper bound of Q_T].
    */
    function calculate_ic_qt(float $mu_Y, float $sigma_Y, int $N, float $Target_T, float $confidence = 0.95): array
    {
        if ($N <= 1) {
            return ['bas' => 0.0, 'haut' => 0.0];
        }
        
        $alpha = 1.0 - $confidence;
        $degrees_of_freedom = $N - 1;
        $P_non_depassement = 1.0 / $Target_T;

        try {
            $studentT = new StudentT($degrees_of_freedom);
            $standardNormal = new Normal(0, 1); // Standard normal law N(0, 1)

            // Critical t value (same as for mu)
            $t_critical = $studentT->inverse(1.0 - ($alpha / 2.0));
            
            // K_T (Z-score): inverse CDF of the standard normal for probability P
            $K_T = $standardNormal->inverse($P_non_depassement); 
            
            // 1. Standard error of the Q_T quantile estimate (Sy,Kt)
            $term1 = 1.0 / $N;
            $term2 = ($K_T * $K_T) / (2.0 * $N); // Approximation for minimum flows
            
            $S_Y_KT = $sigma_Y * sqrt($term1 + $term2);
            
            // 2. Margin of error of ln(Q_T)
            $margin_of_error_log = $t_critical * $S_Y_KT;
            
            // 3. CI of ln(Q_T)
            $log_QT_estimation = $mu_Y + $sigma_Y * $K_T;
            
            $log_IC_bas = $log_QT_estimation - $margin_of_error_log;
            $log_IC_haut = $log_QT_estimation + $margin_of_error_log;
            
            // 4. CI of Q_T (exponentiation)
            $IC_bas = exp($log_IC_bas);
            $IC_haut = exp($log_IC_haut);
            
        } catch (\Exception $e) {
            // On failure, return a wide default CI
            $QT_estimation = exp($mu_Y + $sigma_Y * $K_T);
            return ['bas' => $QT_estimation * 0.8, 'haut' => $QT_estimation * 1.2];
        }
        
        return [
            'bas' => $IC_bas,
            'haut' => $IC_haut
        ];
    }


    /*
        Estimate the log-normal parameters (mu, sigma) by the quantile method.

        Fits a linear regression ln(Q) = mu + sigma * K_T on the sorted log-flows,
        where K_T are the theoretical standard-normal Z-scores of the Weibull
        plotting positions. The intercept gives mu_quantile, the slope sigma_quantile.

        @param array $annual_minima Annual minima series.

        @return array ['mu_quantile' => intercept, 'sigma_quantile' => slope].
    */
    
    function calculate_params_by_quantile(array $annual_minima): array
    {
        $Q_series = array_filter($annual_minima, fn($Q) => $Q > 0);
        $Y_series = array_map('log', $Q_series);
        sort($Y_series); // Must be sorted
        $N = count($Y_series);

        if ($N < 3) 
        {
            return ['mu_quantile' => 0.0, 'sigma_quantile' => 0.0];
        }
        
        $normal = new Normal(0, 1);
        $X_series_Z_scores = []; // Theoretical Z-scores (K_T)
        
        for ($i = 0; $i < $N; $i++) 
        {
            $rank = $i + 1;
            // 1. Empirical frequency (Weibull)
            $F_i = $rank / ($N + 1); 
            
            // 2. Z-score K_T = inverse CDF of the normal law
            $K_T = $normal->inverse($F_i); 
            $X_series_Z_scores[] = $K_T;
        }
        
        // Linear regression: Y = a + b*X (ln(Q) = mu' + sigma' * K_T)
        // a = mu_quantile (intercept) and b = sigma_quantile (slope).
        
        $mean_Y = mean($Y_series);
        $mean_X = mean($X_series_Z_scores);

        // Slope (sigma_quantile)
        $numerator = 0;
        $denominator = 0;
        for ($i = 0; $i < $N; $i++) 
        {
            $numerator += ($Y_series[$i] - $mean_Y) * ($X_series_Z_scores[$i] - $mean_X);
            $denominator += ($X_series_Z_scores[$i] - $mean_X) * ($X_series_Z_scores[$i] - $mean_X);
        }
        
        $sigma_quantile = ($denominator != 0) ? $numerator / $denominator : 0;
        
        // Intercept (mu_quantile)
        $mu_quantile = $mean_Y - $sigma_quantile * $mean_X;
        
        return [
            'mu_quantile' => $mu_quantile,
            'sigma_quantile' => $sigma_quantile,
        ];
    }


    // Compute the non-exceedance frequency (P) of a given flow Q_T,
    // using the fitted Log-Normal law.
    // @param array $annual_minima Annual minima series (used to fit the law).
    // @param float $Q_T Target flow (e.g. QMNA2).
    // @return float Non-exceedance frequency as a percentage (0 to 100).
    
    function calculate_q_frequency(array $Qj_data_array, float $Q_T): float 
    {
        $N = count($Qj_data_array);
        if ($N == 0) { return 0.0; }
        
        $count_below_qt = 0;
        
        // Count the days where flow is less than or equal to Q_T
        foreach ($Qj_data_array as $Qj) 
        {
            if ($Qj <= $Q_T) {
                $count_below_qt++;
            }
        }
        
        // Non-exceedance frequency (%): F = (days <= Q_T) / N * 100
        return ($count_below_qt / $N) * 100;
    }

    

// --------------------------------------------------------
// III. FLOW-DURATION CURVE CHARTS
// --------------------------------------------------------    



    // Generate X (frequency) and Y (flow) data for the Flow-Duration Curve (FDC).
    // @param array $Qj_data_array Daily flow values (values only).
    // @return array ['X' => frequencies, 'Y' => sorted flows].
    
    function generate_cdc_data(array $Qj_data_array): array 
    {
        $N = count($Qj_data_array);

        // 1. Sort flows (PHP sort gives ascending order)
        $Qj_sorted = $Qj_data_array;
        sort($Qj_sorted); 
        
        $P_non_depassement = [];
        
        // 2. Compute the non-exceedance frequency (P) for each rank
        foreach ($Qj_sorted as $rank => $Qj) 
        {
            // Frequency P (decimal): P = rank / (N + 1)
            // Rank starts at 0, so use (rank + 1)
            $P = ($rank + 1) / ($N + 1);
            
            // The FDC uses the EXCEEDANCE frequency. 
            // Non-exceedance frequency (X axis): P_FDC = 1 - P
            // Or, more simply, use (rank / N) for the axis.
            // Use the empirical non-exceedance frequency:
            $F = (($rank + 1) / $N) * 100; // As a percentage (0 to 100%)

            $P_non_depassement[] = $F; // X axis
        }

        return ['X' => $P_non_depassement, 'Y' => $Qj_sorted];
    }


    // Generate a series of (Q, F) points for the theoretical curve and its CI.
    // @param float $mu_Y mu parameter of the Log-Normal law.
    // @param float $sigma_Y sigma parameter of the Log-Normal law.
    // @param int $N Number of data points used (for the CI computation).
    // @return array Series for Plotly (Q_modele, F, Q_IC_bas, Q_IC_haut).
    
    function generate_series_logNormal(float $mu_Y, float $sigma_Y, int $N): array
    {
        $logNormalDistribution = new LogNormal($mu_Y, $sigma_Y);
        $studentT = new StudentT($N - 1);
        $standardNormal = new Normal(0, 1);
        
        $F_series = [];
        $Q_modele_series = [];
        $Q_IC_bas_series = [];
        $Q_IC_haut_series = [];

        // Generate 100 points across the frequency range F
        for ($i = 1; $i <= 100; $i++) {
            $F = $i / 101.0; 
            $F_series[] = $F;

            // 1. Model flow (Qt): inverse CDF
            $Q_T = $logNormalDistribution->inverse($F);
            $Q_modele_series[] = $Q_T;

            // 2. CI for each F point (same logic as calculate_ic_qt)
            $T_ans = 1.0 / $F;
            
            $alpha = 0.05;
            $t_critical = $studentT->inverse(1.0 - ($alpha / 2.0));
            $K_T = $standardNormal->inverse($F); 
            
            $term1 = 1.0 / $N;
            $term2 = ($K_T * $K_T) / (2.0 * $N);
            $S_Y_KT = $sigma_Y * sqrt($term1 + $term2);
            
            $margin_of_error_log = $t_critical * $S_Y_KT;
            $log_QT_estimation = $mu_Y + $sigma_Y * $K_T;
            
            $log_IC_bas = $log_QT_estimation - $margin_of_error_log;
            $log_IC_haut = $log_QT_estimation + $margin_of_error_log;
            
            $Q_IC_bas_series[] = exp($log_IC_bas);
            $Q_IC_haut_series[] = exp($log_IC_haut);
        }
        
        return [
            'F' => $F_series,
            'Q_modele' => $Q_modele_series,
            'Q_IC_bas' => $Q_IC_bas_series,
            'Q_IC_haut' => $Q_IC_haut_series,
        ];
    }


    

    

?>