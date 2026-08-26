<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Mathematical helpers for series of numbers held in arrays.

These functions are used across the platform for statistical analysis of
hydrological data — gauging points, rating-curve regressions, percentile
indicators, linear trends, etc.

Convention:
  - Inputs are PHP arrays of numeric values.
  - All functions are indexing-agnostic (work with both 0-based and 1-based
    arrays). Earlier versions had a covariance() that required 1-based
    indexing — this is now fixed.
  - Variance and covariance both use Bessel's correction (division by n-1
    instead of n). This is the standard convention when the array is a
    *sample* of a larger population — which is almost always the case in
    hydrology (a set of gaugings sampled from a continuous flow regime).
  - Functions never throw on empty / singleton inputs; they return 0 to
    keep callers simple.
----------------------------------------
*/


/**
 * Arithmetic mean of an array of numbers.
 *
 * Indexing-agnostic. Empty array → returns 0 (rather than dividing by zero).
 *
 * @param  array $values  numeric values
 * @return float          mean, or 0 if the array is empty
 */
function mean(array $values)
{
    $count = count($values);
    if ($count === 0) {
        return 0;
    }
    return array_sum($values) / $count;
}


/**
 * Sample variance with Bessel's correction (divide by n-1).
 *
 * Bessel's correction (n-1 instead of n) compensates for the fact that the
 * sample mean is only an estimate of the true population mean. For a sample
 * of gauging points used to characterize a river's flow regime, n-1 is the
 * statistically correct denominator.
 *
 * Indexing-agnostic. Returns 0 for arrays of fewer than 2 elements (variance
 * is not defined on a single point).
 *
 * @param  array $values  numeric values
 * @return float          sample variance, or 0 if fewer than 2 values
 */
function variance(array $values)
{
    $count = count($values);
    if ($count < 2) {
        return 0;
    }
    $m = mean($values);
    $squared_diffs = array_map(function ($x) use ($m) {
        return ($x - $m) ** 2;
    }, $values);
    return array_sum($squared_diffs) / ($count - 1);
}


/**
 * Sample covariance with Bessel's correction (divide by n-1).
 *
 * Measures how two series vary together. Used in linear regression
 * (slope = cov(X,Y) / var(X)) and in correlation analysis
 * (R = cov(X,Y) / sqrt(var(X) · var(Y))).
 *
 * Indexing-agnostic (iterates via array_values() rather than $array[$i]).
 * Returns 0 if the two arrays have different lengths, or fewer than 2
 * elements.
 *
 * @param  array $x_values  first series
 * @param  array $y_values  second series, same length as $x_values
 * @return float            sample covariance, or 0 on invalid input
 */
function covariance(array $x_values, array $y_values)
{
    $count = count($x_values);
    if ($count < 2 || $count !== count($y_values)) {
        return 0;
    }

    // Reindex to 0-based numeric arrays so paired access is unambiguous
    // regardless of how the caller built the input arrays.
    $xs = array_values($x_values);
    $ys = array_values($y_values);

    $mean_x = mean($xs);
    $mean_y = mean($ys);

    $sum = 0;
    for ($i = 0; $i < $count; $i++) {
        $sum += ($xs[$i] - $mean_x) * ($ys[$i] - $mean_y);
    }
    return $sum / ($count - 1);
}


/**
 * Percentile of a numeric array using linear interpolation between
 * the two closest ranks (equivalent to type 7 in R / NumPy default).
 *
 * Useful for the median ($percentile = 50), Q1 (25), Q3 (75), or any
 * other quantile-based indicator on a series of measurements.
 *
 * Caveat: this function sorts a *copy* of the input via PHP's sort(), but
 * because the input is passed by value the caller's array is untouched.
 * Returns 0 for an empty array.
 *
 * @param  array $data        numeric values, any indexing
 * @param  float $percentile  0..100 (e.g. 50 for median)
 * @return float              the interpolated percentile value
 */
function calculerPercentile($data, $percentile)
{
    if (count($data) === 0) {
        return 0;
    }
    sort($data); // re-indexes 0..n-1 as a side effect, which is what we need

    $count    = count($data);
    $index    = ($percentile / 100) * ($count - 1);
    $floor    = (int) floor($index);
    $fraction = $index - $floor;

    if ($floor + 1 < $count) {
        return $data[$floor] + ($data[$floor + 1] - $data[$floor]) * $fraction;
    }
    return $data[$floor];
}


/**
 * Simple linear regression: fits y = a·x + b on the known points, then
 * predicts y for each of the provided new x values.
 *
 * Mirrors Excel's LINEST/TREND behavior. Used when you have a known
 * (x, y) calibration and want to predict y on new x values.
 *
 * Indexing-agnostic. Returns null on invalid input (different lengths,
 * or a degenerate x series where all values are identical).
 *
 * @param  array $knownYs  observed y values
 * @param  array $knownXs  observed x values, same length as $knownYs
 * @param  array $newXs    x values to predict y for
 * @return array|null      array of predicted y values, or null on error
 */
function linearTrendPhp($knownYs, $knownXs, $newXs)
{
    $n = count($knownYs);
    if ($n === 0 || $n !== count($knownXs)) {
        return null;
    }

    // Reindex so paired access [$i] works regardless of input keys
    $xs = array_values($knownXs);
    $ys = array_values($knownYs);

    $sumX  = array_sum($xs);
    $sumY  = array_sum($ys);
    $sumXY = 0;
    $sumX2 = 0;
    for ($i = 0; $i < $n; $i++) {
        $sumXY += $xs[$i] * $ys[$i];
        $sumX2 += $xs[$i] * $xs[$i];
    }

    $denom = $n * $sumX2 - $sumX * $sumX;
    if ($denom == 0) {
        return null; // all x values identical → vertical line, undefined slope
    }

    $a = ($n * $sumXY - $sumX * $sumY) / $denom;
    $b = ($sumY - $a * $sumX) / $n;

    $newYs = [];
    foreach ($newXs as $newX) {
        $newYs[] = $a * $newX + $b;
    }
    return $newYs;
}


/**
 * Returns the two-sided 95% critical value of Student's t distribution
 * for a given number of degrees of freedom.
 *
 * For df > 30 the value is within 1% of z = 1.96 (normal limit), so we
 * just return 1.96 there. For df = 1..30 we use a static table — these
 * are the standard values you'd find in any stats textbook. For df < 1
 * (degenerate case) we return Infinity to flag the issue.
 *
 * Used by the prediction-interval helpers below.
 *
 * @param  int   $df  degrees of freedom (positive integer)
 * @return float      two-sided 95% critical value
 */
function tStudentCritical95($df)
{
    static $table = [
        1 => 12.706, 2 => 4.303, 3 => 3.182, 4 => 2.776, 5 => 2.571,
        6 => 2.447,  7 => 2.365, 8 => 2.306, 9 => 2.262, 10 => 2.228,
        11 => 2.201, 12 => 2.179, 13 => 2.160, 14 => 2.145, 15 => 2.131,
        16 => 2.120, 17 => 2.110, 18 => 2.101, 19 => 2.093, 20 => 2.086,
        21 => 2.080, 22 => 2.074, 23 => 2.069, 24 => 2.064, 25 => 2.060,
        26 => 2.056, 27 => 2.052, 28 => 2.048, 29 => 2.045, 30 => 2.042,
    ];
    $df = (int) $df;
    if ($df < 1)   { return INF; }
    if ($df > 30)  { return 1.96; }
    return $table[$df];
}


/**
 * 95% prediction interval for a fitted curve y = f(x), computed in the
 * space where the regression was fitted (linear / log space).
 *
 * The prediction interval at a new point x0 is:
 *
 *     y_hat(x0) ± t(n-2, 0.025) * s * sqrt(1 + 1/n + (x0 - x_mean)^2 / SSx)
 *
 * where:
 *   - t(n-2, 0.025) is the two-sided 95% critical value of Student's t
 *     with n-2 degrees of freedom. For small n this is significantly
 *     larger than the normal-limit z = 1.96 (e.g. t = 2.78 for n = 5).
 *   - s = sqrt(SSR / (n - 2)) is the residual standard error
 *   - SSx = sum( (xi - x_mean)^2 ) is the sum of squared x deviations
 *
 * The interval widens away from x_mean, which visually conveys the
 * uncertainty growth when extrapolating (e.g. high-water levels far
 * from the gauging range — a critical concern for rating curves).
 *
 * @param  array $fitXs    x values of the fitted points (same space as $fitYs)
 * @param  array $fitYs    y values of the fitted points
 * @param  array $predXs   x values at which to compute the band
 * @param  array $predYs   y predictions at $predXs (already computed from the model)
 * @return array           [ 'lower' => [...], 'upper' => [...] ] same size as $predXs
 *                         Returns equal-to-prediction bands (zero width) if n < 3
 *                         or if SSx is zero.
 */
function predictionInterval95(array $fitXs, array $fitYs, array $predXs, array $predYs)
{
    $fitXs  = array_values($fitXs);
    $fitYs  = array_values($fitYs);
    $predXs = array_values($predXs);
    $predYs = array_values($predYs);

    $n = count($fitXs);

    // Initialise output to the prediction line (zero-width band) — used as
    // a safe fallback when the maths is degenerate.
    $lower = $predYs;
    $upper = $predYs;
    if ($n < 3) {
        return [ 'lower' => $lower, 'upper' => $upper ];
    }

    // x_mean and SSx
    $xMean = mean($fitXs);
    $SSx   = 0;
    for ($i = 0; $i < $n; $i++) {
        $d = $fitXs[$i] - $xMean;
        $SSx += $d * $d;
    }
    if ($SSx == 0) {
        return [ 'lower' => $lower, 'upper' => $upper ];
    }

    // Residual sum of squares — we need the y_hat at each fit point.
    // The slope/intercept of the OLS line through (fitXs, fitYs) gives us
    // those y_hats; using OLS here (rather than the caller's model) is the
    // standard way to derive s for linear models, and works exactly for the
    // power-law case (which IS linear after log transform).
    $sumX = $sumY = $sumXY = $sumX2 = 0;
    for ($i = 0; $i < $n; $i++) {
        $sumX  += $fitXs[$i];
        $sumY  += $fitYs[$i];
        $sumXY += $fitXs[$i] * $fitYs[$i];
        $sumX2 += $fitXs[$i] * $fitXs[$i];
    }
    $denom = $n * $sumX2 - $sumX * $sumX;
    if ($denom == 0) {
        return [ 'lower' => $lower, 'upper' => $upper ];
    }
    $aLin = ($n * $sumXY - $sumX * $sumY) / $denom;
    $bLin = ($sumY - $aLin * $sumX) / $n;

    $SSR = 0;
    for ($i = 0; $i < $n; $i++) {
        $yhat = $aLin * $fitXs[$i] + $bLin;
        $r    = $fitYs[$i] - $yhat;
        $SSR += $r * $r;
    }
    $s = sqrt($SSR / ($n - 2));

    // Two-sided 95% critical value (Student's t with n-2 degrees of freedom)
    $tCrit = tStudentCritical95($n - 2);

    // Build the band, widening with distance from x_mean
    $lower = [];
    $upper = [];
    for ($i = 0; $i < count($predXs); $i++) {
        $d  = $predXs[$i] - $xMean;
        $se = $s * sqrt(1 + 1.0 / $n + ($d * $d) / $SSx);
        $half = $tCrit * $se;
        $lower[] = $predYs[$i] - $half;
        $upper[] = $predYs[$i] + $half;
    }
    return [ 'lower' => $lower, 'upper' => $upper ];
}


/**
 * 95% prediction interval for a degree-2 polynomial regression
 *   y = a*x^2 + b*x + c
 * fitted by ordinary least squares on (fitXs, fitYs).
 *
 * The exact PI uses the leverage of each prediction point:
 *
 *     y_hat(x0) ± t(n-3, 0.025) * s * sqrt(1 + x0' (X'X)^-1 x0)
 *
 * where:
 *   - X is the (n x 3) design matrix with rows [xi^2, xi, 1]
 *   - x0 is the column vector [x0^2, x0, 1]'
 *   - s = sqrt(SSR / (n-3)) is the residual standard error
 *   - t(n-3, 0.025) is the two-sided 95% Student critical value
 *
 * The 3x3 matrix (X'X)^-1 is computed analytically via cofactors.
 * The resulting band widens at the extremes (high leverage) and is
 * tightest near the centroid of the gauging points — exactly the
 * "trumpet" shape expected when extrapolating outside the data range.
 *
 * @param  array $fitXs    x values used for the fit (H values)
 * @param  array $fitYs    y values used for the fit (Q values)
 * @param  array $predXs   x values at which to compute the band
 * @param  array $predYs   y predictions at $predXs (from the polynomial)
 * @return array           [ 'lower' => [...], 'upper' => [...] ]
 *                         Zero-width band if n < 4 or matrix is singular.
 */
function predictionIntervalPolynomial95(array $fitXs, array $fitYs, array $predXs, array $predYs)
{
    $fitXs  = array_values($fitXs);
    $fitYs  = array_values($fitYs);
    $predXs = array_values($predXs);
    $predYs = array_values($predYs);

    $n = count($fitXs);

    // Safe fallback to zero-width band
    $lower = $predYs;
    $upper = $predYs;
    if ($n < 4) {
        // Need at least 4 points to estimate the 3 coefficients AND have
        // 1 residual degree of freedom (n - 3 >= 1).
        return [ 'lower' => $lower, 'upper' => $upper ];
    }

    // Build X'X (3x3 symmetric) and X'y (3x1)
    //   X has rows [xi^2, xi, 1]
    //   X'X[0][0] = sum xi^4    [0][1] = sum xi^3    [0][2] = sum xi^2
    //   X'X[1][1] = sum xi^2    [1][2] = sum xi
    //   X'X[2][2] = n
    $S0 = $n;   // sum 1
    $S1 = 0;    // sum xi
    $S2 = 0;    // sum xi^2
    $S3 = 0;    // sum xi^3
    $S4 = 0;    // sum xi^4
    $T0 = 0;    // sum yi
    $T1 = 0;    // sum xi*yi
    $T2 = 0;    // sum xi^2*yi
    for ($i = 0; $i < $n; $i++) {
        $x  = $fitXs[$i];
        $y  = $fitYs[$i];
        $x2 = $x * $x;
        $S1 += $x;
        $S2 += $x2;
        $S3 += $x2 * $x;
        $S4 += $x2 * $x2;
        $T0 += $y;
        $T1 += $x * $y;
        $T2 += $x2 * $y;
    }

    // The X'X matrix (3x3, symmetric, indexed by powers of x descending:
    // row/col 0 = x^2 coefficient, 1 = x, 2 = constant)
    $M = [
        [ $S4, $S3, $S2 ],
        [ $S3, $S2, $S1 ],
        [ $S2, $S1, $S0 ],
    ];

    // Determinant via cofactor expansion along the first row
    $a = $M[0][0]; $b = $M[0][1]; $c = $M[0][2];
    $d = $M[1][0]; $e = $M[1][1]; $f = $M[1][2];
    $g = $M[2][0]; $h = $M[2][1]; $iEl = $M[2][2];

    $det = $a * ($e * $iEl - $f * $h)
         - $b * ($d * $iEl - $f * $g)
         + $c * ($d * $h - $e * $g);

    if (abs($det) < 1e-30) {
        // Singular (all x identical, or numerical underflow on huge x)
        return [ 'lower' => $lower, 'upper' => $upper ];
    }

    // Inverse via the adjugate (transpose of the cofactor matrix), scaled by 1/det
    // For a 3x3 matrix M, M^-1[i][j] = cofactor(M)[j][i] / det
    // (the transpose is critical — not doing it gives the wrong inverse).
    $invDet = 1.0 / $det;
    $Minv = [
        [
             ($e * $iEl - $f * $h) * $invDet,
            -($b * $iEl - $c * $h) * $invDet,
             ($b * $f  - $c * $e) * $invDet,
        ],
        [
            -($d * $iEl - $f * $g) * $invDet,
             ($a * $iEl - $c * $g) * $invDet,
            -($a * $f  - $c * $d) * $invDet,
        ],
        [
             ($d * $h  - $e * $g) * $invDet,
            -($a * $h  - $b * $g) * $invDet,
             ($a * $e  - $b * $d) * $invDet,
        ],
    ];

    // Fitted coefficients: beta = M^-1 * X'y, with X'y = [T2, T1, T0]'
    $aHat = $Minv[0][0] * $T2 + $Minv[0][1] * $T1 + $Minv[0][2] * $T0;
    $bHat = $Minv[1][0] * $T2 + $Minv[1][1] * $T1 + $Minv[1][2] * $T0;
    $cHat = $Minv[2][0] * $T2 + $Minv[2][1] * $T1 + $Minv[2][2] * $T0;

    // Residual sum of squares
    $SSR = 0;
    for ($i = 0; $i < $n; $i++) {
        $x  = $fitXs[$i];
        $y  = $fitYs[$i];
        $yh = $aHat * $x * $x + $bHat * $x + $cHat;
        $r  = $y - $yh;
        $SSR += $r * $r;
    }
    $s = sqrt($SSR / ($n - 3));

    // Critical value
    $tCrit = tStudentCritical95($n - 3);

    // Build the band: at each prediction point x0, compute
    //     leverage = x0' (X'X)^-1 x0
    //     se       = s * sqrt(1 + leverage)
    //     band     = y_hat(x0) ± tCrit * se
    $lower = [];
    $upper = [];
    for ($k = 0; $k < count($predXs); $k++) {
        $x0  = $predXs[$k];
        $x02 = $x0 * $x0;
        // v = [x0^2, x0, 1]; leverage = v' Minv v
        // We compute it explicitly (3x3 symmetric, so 6 distinct terms)
        $lev =   $Minv[0][0] * $x02 * $x02
             + 2 * $Minv[0][1] * $x02 * $x0
             + 2 * $Minv[0][2] * $x02
             +     $Minv[1][1] * $x0 * $x0
             + 2 * $Minv[1][2] * $x0
             +     $Minv[2][2];
        // Numerical safety: leverage should be >= 0 in theory; floor at 0
        if ($lev < 0) { $lev = 0; }
        $se   = $s * sqrt(1 + $lev);
        $half = $tCrit * $se;
        $lower[] = $predYs[$k] - $half;
        $upper[] = $predYs[$k] + $half;
    }

    return [ 'lower' => $lower, 'upper' => $upper ];
}
?>