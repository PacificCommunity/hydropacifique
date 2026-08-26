<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
AJAX endpoint — preview for the new-ETL popup (rollback version, no exclusion)
----------------------------------------
*/

require('../../config.php');
require('../../database_tables.php');

require('../../function/math.php');
require('../../function/date.php');
require('../../function/database.php');
require('../../function/html_output.php');
require('../../function/general.php');

require('../../text_content_' . LANGUAGE . '.php');

@ini_set('display_errors', '0');
error_reporting(E_ERROR | E_PARSE);

header('Content-Type: application/json; charset=utf-8');

$sql_link = mysqli_connect(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD, DB_DATABASE)
    or die(json_encode(['nb_jge' => 0, 'points' => [], 'curve' => null, 'error' => 'db_connect']));
mysqli_query($sql_link, 'SET NAMES UTF8');

$data           = json_decode(file_get_contents('php://input'), true);
$id_station     = isset($data['idStation'])     ? (int)$data['idStation']     : 0;
$date1          = isset($data['date1'])         ? $data['date1']              : '';
$date2          = isset($data['date2'])         ? $data['date2']              : '';
$heure1         = isset($data['heure1'])        ? $data['heure1']             : '00:00:00';
$heure2         = isset($data['heure2'])        ? $data['heure2']             : '23:59:59';
$model          = isset($data['model'])         ? (int)$data['model']         : 1;
$h0             = isset($data['h0'])            ? (float)$data['h0']          : 0.0;
$h0_auto        = isset($data['h0_auto'])       ? (bool)$data['h0_auto']      : false;
$bornesTab      = isset($data['bornesTab'])     ? $data['bornesTab']          : [];
$excludedDates  = isset($data['excludedDates']) ? $data['excludedDates']      : [];

// Build a lookup set of excluded date strings for fast O(1) filtering
$excluded_set = [];
if (is_array($excludedDates)) {
    foreach ($excludedDates as $d) { $excluded_set[$d] = true; }
}

if (!preg_match('/^\d{2}-\d{2}-\d{4}$/', $date1)
 || !preg_match('/^\d{2}-\d{2}-\d{4}$/', $date2)
 || !preg_match('/^\d{2}:\d{2}:\d{2}$/', $heure1)
 || !preg_match('/^\d{2}:\d{2}:\d{2}$/', $heure2))
{
    echo json_encode(['nb_jge' => 0, 'points' => [], 'curve' => null, 'error' => 'bad_format']);
    exit;
}

$datetime1 = mysqli_real_escape_string($sql_link, datefr_us($date1) . ' ' . $heure1);
$datetime2 = mysqli_real_escape_string($sql_link, datefr_us($date2) . ' ' . $heure2);

$jge_query = tep_db_query($sql_link,
    "SELECT DISTINCT jge.id, jge.datetime, jge.depouil_hmoy, jge.depouil_q
     FROM " . TABLE_DATA_JGE . " jge
     WHERE jge.id_station=$id_station
     AND jge.datetime >= '$datetime1'
     AND jge.datetime <= '$datetime2'
     AND jge.depouil_hmoy REGEXP '^-?[0-9]+(\\.[0-9]+)?$'
     AND jge.depouil_hmoy < 9999
     AND jge.depouil_q   REGEXP '^-?[0-9]+(\\.[0-9]+)?$'
     ORDER BY jge.depouil_hmoy ASC");

$points = [];
$tab_h = [];
$tab_q = [];
while ($jge_tab = tep_db_fetch_array($jge_query))
{
    $h    = (float) abs($jge_tab['depouil_hmoy']);
    $q    = (float) abs($jge_tab['depouil_q']);
    $date = date('d-m-Y H:i:s', strtotime($jge_tab['datetime']));

    $is_excluded = isset($excluded_set[$date]);

    $points[] = [
        'h'        => $h,
        'q'        => $q,
        'date'     => $date,
        'id_jge'   => (int) $jge_tab['id'],
        'excluded' => $is_excluded,
    ];

    if (!$is_excluded) {
        $tab_h[] = $h;
        $tab_q[] = $q;
    }
}

$response = [
    'nb_jge'          => count($points),
    'nb_jge_used'     => count($tab_h),
    'nb_jge_excluded' => count($points) - count($tab_h),
    'points'          => $points,
    'curve'           => null,
];

if (count($tab_h) >= 2)
{
    try
    {
        if ($model === 2)
        {
            $n = count($tab_h);
            $S0 = $n; $S1 = 0; $S2 = 0; $S3 = 0; $S4 = 0;
            $T0 = 0; $T1 = 0; $T2 = 0;
            for ($i = 0; $i < $n; $i++) {
                $h  = $tab_h[$i]; $q = $tab_q[$i];
                $h2 = $h * $h; $h3 = $h2 * $h; $h4 = $h2 * $h2;
                $S1 += $h;  $S2 += $h2; $S3 += $h3; $S4 += $h4;
                $T0 += $q;  $T1 += $q * $h;  $T2 += $q * $h2;
            }
            $D  = $S4 * ($S2*$S0 - $S1*$S1) - $S3 * ($S3*$S0 - $S1*$S2) + $S2 * ($S3*$S1 - $S2*$S2);
            if (abs($D) < 1e-12) { throw new Exception('singular_matrix'); }
            $Da = $T2 * ($S2*$S0 - $S1*$S1) - $S3 * ($T1*$S0 - $T0*$S1) + $S2 * ($T1*$S1 - $T0*$S2);
            $Db = $S4 * ($T1*$S0 - $T0*$S1) - $T2 * ($S3*$S0 - $S1*$S2) + $S2 * ($S3*$T0 - $T1*$S2);
            $Dc = $S4 * ($S2*$T0 - $S1*$T1) - $S3 * ($S3*$T0 - $S2*$T1) + $T2 * ($S3*$S1 - $S2*$S2);
            $a = $Da / $D;
            $b = $Db / $D;
            $c = $Dc / $D;

            $mean_q = $T0 / $n;
            $sst = 0; $ssr = 0;
            for ($i = 0; $i < $n; $i++) {
                $h = $tab_h[$i]; $q = $tab_q[$i];
                $q_pred = $a*$h*$h + $b*$h + $c;
                $sst += pow($q - $mean_q, 2);
                $ssr += pow($q - $q_pred, 2);
            }
            $r2 = $sst > 0 ? 1 - $ssr / $sst : 1;

            $equation = sprintf('Q = %s · H² + %s · H + %s', round($a, 5), round($b, 4), round($c, 3));
            $compute = function($h) use ($a, $b, $c) { return $a*$h*$h + $b*$h + $c; };
            $coefficients = ['model' => 2, 'a' => $a, 'b' => $b, 'c' => $c];
        }
        elseif ($model === 3)
        {
            // Simple linear regression Q = a·H + b by ordinary least squares.
            // Not hydrologically ideal (a rating curve is rarely linear) but
            // requested for simple/diagnostic fits.
            $n = count($tab_h);
            $sumH = 0; $sumQ = 0; $sumHQ = 0; $sumHH = 0;
            for ($i = 0; $i < $n; $i++) {
                $h = $tab_h[$i]; $q = $tab_q[$i];
                $sumH  += $h;
                $sumQ  += $q;
                $sumHQ += $h * $q;
                $sumHH += $h * $h;
            }
            $denom = $n * $sumHH - $sumH * $sumH;
            if (abs($denom) < 1e-12) { throw new Exception('singular_matrix'); }
            $a = ($n * $sumHQ - $sumH * $sumQ) / $denom;
            $b = ($sumQ - $a * $sumH) / $n;

            $mean_q = $sumQ / $n;
            $sst = 0; $ssr = 0;
            for ($i = 0; $i < $n; $i++) {
                $h = $tab_h[$i]; $q = $tab_q[$i];
                $q_pred = $a * $h + $b;
                $sst += pow($q - $mean_q, 2);
                $ssr += pow($q - $q_pred, 2);
            }
            $r2 = $sst > 0 ? 1 - $ssr / $sst : 1;

            $equation = sprintf('Q = %s · H + %s', round($a, 5), round($b, 4));
            $compute = function($h) use ($a, $b) { return $a * $h + $b; };
            $coefficients = ['model' => 3, 'a' => $a, 'b' => $b];
        }
        else
        {
            // ---- H₀ (stage of zero flow) ----------------------------------
            // The power law Q = a·(H − H₀)^b linearises in log-log space only
            // around the *right* H₀. A wrong H₀ bends the log-log cloud and
            // inflates the exponent b. When h0_auto is on, we sweep H₀ from 0
            // up to just below the smallest gauged H and keep the value that
            // maximises the log-log R². Otherwise we use the H₀ the user set.
            $h0_safe = $h0;

            if ($h0_auto && count($tab_h) >= 2)
            {
                $h_min = min($tab_h);
                $upper = $h_min - 0.01;            // keep every dh = H − H₀ > 0
                if ($upper > 0) {
                    $step    = max(0.1, $h_min / 200.0);   // ~200 trial values
                    $best_r2 = -INF;
                    $best_h0 = 0.0;
                    for ($h0_try = 0.0; $h0_try < $upper; $h0_try += $step) {
                        $lx = []; $ly = []; $ok = 0;
                        for ($i = 0; $i < count($tab_h); $i++) {
                            $dh = $tab_h[$i] - $h0_try;
                            if ($dh <= 0 || $tab_q[$i] <= 0) continue;
                            $lx[] = log10($dh);
                            $ly[] = log10($tab_q[$i]);
                            $ok++;
                        }
                        if ($ok < 2) continue;
                        $vx = variance($lx);
                        $vy = variance($ly);
                        if ($vx <= 0 || $vy <= 0) continue;
                        $r2_try = pow(covariance($lx, $ly) / (sqrt($vx) * sqrt($vy)), 2);
                        if ($r2_try > $best_r2) { $best_r2 = $r2_try; $best_h0 = $h0_try; }
                    }
                    if ($best_r2 > -INF) { $h0_safe = $best_h0; }
                }
            }
            // ---------------------------------------------------------------

            $tab_X = []; $tab_Y = []; $tab_XX = []; $tab_XY = [];
            $usable = 0;
            for ($i = 0; $i < count($tab_h); $i++) {
                $dh = $tab_h[$i] - $h0_safe;
                if ($dh <= 0 || $tab_q[$i] <= 0) continue;
                $X = log10($dh);
                $Y = log10($tab_q[$i]);
                $tab_X[]  = $X;
                $tab_Y[]  = $Y;
                $tab_XX[] = $X * $X;
                $tab_XY[] = $X * $Y;
                $usable++;
            }
            if ($usable < 2) { throw new Exception('not_enough_positive'); }

            $moy_X  = mean($tab_X);
            $moy_Y  = mean($tab_Y);
            $moy_XX = mean($tab_XX);
            $moy_XY = mean($tab_XY);
            $var_X  = variance($tab_X);
            $var_Y  = variance($tab_Y);
            $cov_XY = covariance($tab_X, $tab_Y);

            $ap = ($moy_XY - $moy_X * $moy_Y) / ($moy_XX - pow($moy_X, 2));
            $bp = $moy_Y - $ap * $moy_X;
            $coef = pow(10, $bp);
            $r2 = ($var_X > 0 && $var_Y > 0) ? pow($cov_XY / (sqrt($var_X) * sqrt($var_Y)), 2) : 0;

            $equation = sprintf('Q = %s · (H − %s)^%s', round($coef, 5), round($h0_safe, 2), round($ap, 3));
            $compute = function($h) use ($coef, $h0_safe, $ap) {
                $dh = $h - $h0_safe;
                return $dh > 0 ? $coef * pow($dh, $ap) : 0;
            };
            $coefficients = ['model' => 1, 'coef' => $coef, 'ap' => $ap, 'h0' => $h0_safe];
        }

        // -----------------------------------------------
        // Lower clip point where Q = 0.
        //
        // For the linear and polynomial models the fit can give Q < 0 below
        // a certain H. Rather than just dropping those points (which leaves
        // the drawn line floating above the axis), we compute the exact H at
        // which Q = 0 and clip the curve there, so it reaches the axis cleanly
        // without ever going negative.
        //   - Linear  Q = a·H + b  → H(Q=0) = -b / a
        //   - Power    Q = a·(H-H₀)^b → Q→0 as H→H₀ ; the natural floor is H₀
        //     itself (handled by the existing Q>0 guard), no extra clip needed.
        //   - Polynomial: a quadratic may cross zero at up to two H; we take
        //     the relevant (rising, right-hand) root nearest the gauged range.
        $h_qzero = null; // H where Q hits 0 (null = no finite crossing to clip)

        if ($model === 3) {
            // Linear: single crossing, only meaningful if the line is rising.
            if ($a > 0) { $h_qzero = -$b / $a; }
        }
        elseif ($model === 2) {
            // Polynomial aH²+bH+c: solve a·H²+b·H+c = 0.
            $disc = $b * $b - 4 * $a * $c;
            if ($a != 0 && $disc >= 0) {
                $sq = sqrt($disc);
                $r1 = (-$b - $sq) / (2 * $a);
                $r2 = (-$b + $sq) / (2 * $a);
                // Pick the root just below the gauged H range (lower clip),
                // i.e. the largest root that is <= the smallest gauged H.
                $h_lo_jge = min($tab_h);
                $cand = null;
                foreach ([$r1, $r2] as $r) {
                    if ($r <= $h_lo_jge && ($cand === null || $r > $cand)) { $cand = $r; }
                }
                $h_qzero = $cand; // may stay null if both roots are above range
            }
        }

        // Generate curve points at the requested density intervals.
        // Each point is tagged with its rangeIndex (1..4) so the client
        // can later selectively resample a single range without touching
        // points generated by other ranges.
        $curve_points = [];
        for ($i = 0; $i < 4; $i++) {
            if (!isset($bornesTab[$i])) continue;
            $b_inf    = isset($bornesTab[$i]['inf'])    ? trim((string)$bornesTab[$i]['inf'])    : '';
            $b_sup    = isset($bornesTab[$i]['sup'])    ? trim((string)$bornesTab[$i]['sup'])    : '';
            $b_interv = isset($bornesTab[$i]['interv']) ? trim((string)$bornesTab[$i]['interv']) : '';
            if ($b_inf === '' || $b_sup === '' || $b_interv === '') continue;
            if (!is_numeric($b_inf) || !is_numeric($b_sup) || !is_numeric($b_interv)) continue;
            $inf    = (float) $b_inf;
            $sup    = (float) $b_sup;
            $interv = (float) $b_interv;
            if ($interv <= 0 || $sup < $inf) continue;
            $expected_points = ceil(($sup - $inf) / $interv) + 1;
            if ($expected_points > 5000) continue;
            $range_index = $i + 1; // 1..4
            for ($h = $inf; $h <= $sup; $h += $interv) {
                $q = $compute($h);
                if ($q > 0) {
                    $curve_points[] = ['h' => $h, 'q' => $q, 'rangeIndex' => $range_index];
                }
                elseif ($h_qzero !== null && $h_qzero >= $inf && $h_qzero <= $sup) {
                    // This sampled H gives Q <= 0, but the line crosses zero
                    // at $h_qzero inside this range. Emit the exact (h_qzero, 0)
                    // point ONCE so the drawn curve reaches the axis cleanly
                    // instead of stopping short. Guard against duplicates.
                    $already = false;
                    foreach ($curve_points as $cp) {
                        if ($cp['rangeIndex'] === $range_index && abs($cp['h'] - $h_qzero) < 1e-9) {
                            $already = true; break;
                        }
                    }
                    if (!$already) {
                        $curve_points[] = ['h' => $h_qzero, 'q' => 0.0, 'rangeIndex' => $range_index];
                    }
                }
                // else: Q <= 0 and no crossing in this range → skip (power law
                // below H₀, or a range fully under the zero-flow line).
            }
        }

        // Fallback when no bounds are filled — sampled from JGE range,
        // tagged with rangeIndex 0 (= "auto", not belonging to any user
        // range, so it gets fully regenerated on any bounds change).
        if (count($curve_points) < 2 && count($tab_h) > 0) {
            $hmin_jge = min($tab_h);
            $hmax_jge = max($tab_h);
            $step = ($hmax_jge - $hmin_jge) / 50;
            if ($step > 0) {
                $clip_emitted = false;
                for ($h = $hmin_jge; $h <= $hmax_jge; $h += $step) {
                    $q = $compute($h);
                    if ($q > 0) {
                        $curve_points[] = ['h' => $h, 'q' => $q, 'rangeIndex' => 0];
                    }
                    elseif ($h_qzero !== null && $h_qzero >= $hmin_jge
                            && $h_qzero <= $hmax_jge && !$clip_emitted) {
                        $curve_points[] = ['h' => $h_qzero, 'q' => 0.0, 'rangeIndex' => 0];
                        $clip_emitted = true;
                    }
                }
            }
        }

        // -----------------------------------------------
        // 95% prediction interval (PI) bands
        //
        // For the power-law model we compute the PI in the log-log space
        // (where the regression is linear) and transform back via 10^y.
        // The band widens away from the centroid in log space, which gives
        // the characteristic "trumpet" shape — exactly what hydrologists
        // expect when extrapolating to high water levels.
        //
        // For the polynomial model we use the rigorous matrix PI: the
        // leverage 1 + x0'(X'X)^-1 x0 captures how uncertain each prediction
        // is, given how far the new x is from the centroid of the gauging
        // points. Both models use Student's t critical value (not the normal
        // approximation), so the band is correct for small samples too.
        $band_lower = [];
        $band_upper = [];
        if (!empty($curve_points))
        {
            $pred_xs = array_column($curve_points, 'h');
            $pred_ys = array_column($curve_points, 'q');

            if ($model === 2)
            {
                // Polynomial: rigorous PI using leverage from the design matrix.
                // Bands widen at the extremes (high leverage when extrapolating)
                // and are tightest near the centroid of the gauging points.
                $band = predictionIntervalPolynomial95($tab_h, $tab_q, $pred_xs, $pred_ys);
                $band_lower = $band['lower'];
                $band_upper = $band['upper'];
            }
            elseif ($model === 3)
            {
                // Linear: direct linear PI in (H, Q) space — exactly the
                // classic formula handled by predictionInterval95().
                $band = predictionInterval95($tab_h, $tab_q, $pred_xs, $pred_ys);
                $band_lower = $band['lower'];
                $band_upper = $band['upper'];
            }
            else
            {
                // Power-law: PI in log-log space, then 10^y to original.
                // We need to compute the band at log10(H - h0) for each
                // curve point that has H > h0. Points with H <= h0 cannot
                // be evaluated in log space — fall back to zero-width band.
                $logXs = []; $logYs = []; $idxs = [];
                for ($i = 0; $i < count($pred_xs); $i++) {
                    $dh = $pred_xs[$i] - $h0_safe;
                    if ($dh > 0 && $pred_ys[$i] > 0) {
                        $logXs[] = log10($dh);
                        $logYs[] = log10($pred_ys[$i]);
                        $idxs[]  = $i;
                    }
                }
                // tab_X / tab_Y are the log-space fit points (from earlier)
                $band = predictionInterval95($tab_X, $tab_Y, $logXs, $logYs);
                // Initialise full-length arrays at the prediction itself
                $band_lower = $pred_ys;
                $band_upper = $pred_ys;
                for ($k = 0; $k < count($idxs); $k++) {
                    $i = $idxs[$k];
                    $band_lower[$i] = pow(10, $band['lower'][$k]);
                    $band_upper[$i] = pow(10, $band['upper'][$k]);
                }
            }
        }

        // Pass the zero-flow clip H to the client so its local resample
        // (on density-bound edits) can reproduce the exact same clipping.
        $coefficients['h_qzero'] = $h_qzero;

        $response['curve'] = [
            'points'        => $curve_points,
            'equation_html' => $equation,
            'r2'            => round($r2, 3),
            'model'         => $model,
            'coefficients'  => $coefficients,
            'band_lower'    => $band_lower,
            'band_upper'    => $band_upper,
        ];
    }
    catch (Exception $e)
    {
        $response['curve'] = null;
        $response['regression_error'] = $e->getMessage();
    }
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>