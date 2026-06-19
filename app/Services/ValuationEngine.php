<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * ValuationEngine
 *
 * Calculates intrinsic value using Benjamin Graham's principles.
 * Handles edge cases: negative EPS, negative BVPS, loss-making companies.
 *
 * When Graham Number cannot be computed (negative EPS/BVPS), it falls back
 * to PBV-based analysis and provides meaningful diagnostics instead of failing.
 */
class ValuationEngine
{
    /**
     * Full analysis: tries Graham Number first, falls back to PBV-based analysis.
     *
     * Returns a structured result that ALWAYS provides a verdict,
     * even for loss-making companies with negative fundamentals.
     *
     * @return array{
     *     fair_value: ?float,
     *     margin_of_safety: ?float,
     *     method: string,
     *     warnings: string[],
     *     can_value: bool,
     * }
     */
    public function analyze(
        float $eps,
        float $bvps,
        float $currentPrice,
        float $der,
        float $roe,
        float $npm,
    ): array {
        $warnings = [];

        // ---------------------------------------------------------------
        // 1. Flag problematic metrics
        // ---------------------------------------------------------------
        if ($eps < 0) {
            $warnings[] = 'EPS negatif — perusahaan sedang merugi.';
        }
        if ($bvps < 0) {
            $warnings[] = 'BVPS negatif — ekuitas pemegang saham negatif (defisiensi modal).';
        }
        if ($roe < 0) {
            $warnings[] = 'ROE negatif — perusahaan tidak menghasilkan keuntungan dari modal.';
        }
        if ($npm < 0) {
            $warnings[] = 'NPM negatif — perusahaan merugi secara operasional.';
        }
        if ($npm > 100) {
            $warnings[] = 'NPM di atas 100% — kemungkinan ada keuntungan non-operasional (one-time gain).';
        }
        if ($der > 2) {
            $warnings[] = 'DER sangat tinggi (>' . number_format($der, 2) . ') — risiko kebangkrutan lebih besar.';
        }

        // ---------------------------------------------------------------
        // 2. Try Graham Number (requires both EPS > 0 AND BVPS > 0)
        // ---------------------------------------------------------------
        if ($eps > 0 && $bvps > 0) {
            $fairValue = $this->calculateGrahamNumber($eps, $bvps);
            $mos = $this->calculateMarginOfSafety($currentPrice, $fairValue);

            return [
                'fair_value'       => $fairValue,
                'margin_of_safety' => $mos,
                'method'           => 'Graham Number',
                'warnings'         => $warnings,
                'can_value'        => true,
            ];
        }

        // ---------------------------------------------------------------
        // 3. Fallback: PBV-based valuation (only needs BVPS > 0)
        // ---------------------------------------------------------------
        if ($bvps > 0) {
            $fairValue = $this->calculatePbvFairValue($bvps);
            $mos = $this->calculateMarginOfSafety($currentPrice, $fairValue);

            $warnings[] = 'Graham Number tidak dapat dihitung (EPS ≤ 0). Menggunakan metode PBV sebagai alternatif.';

            return [
                'fair_value'       => $fairValue,
                'margin_of_safety' => $mos,
                'method'           => 'PBV Analysis',
                'warnings'         => $warnings,
                'can_value'        => true,
            ];
        }

        // ---------------------------------------------------------------
        // 4. Cannot value: both EPS and BVPS are negative
        // ---------------------------------------------------------------
        $warnings[] = 'EPS dan BVPS keduanya negatif — tidak ada metode valuasi yang berlaku.';

        return [
            'fair_value'       => null,
            'margin_of_safety' => null,
            'method'           => 'Tidak Dapat Divaluasi',
            'warnings'         => $warnings,
            'can_value'        => false,
        ];
    }

    /**
     * Graham Number = sqrt(22.5 × EPS × BVPS)
     *
     * Classic Benjamin Graham formula.
     * The constant 22.5 = 15 (max PE) × 1.5 (max PBV).
     */
    public function calculateGrahamNumber(float $eps, float $bvps): ?float
    {
        if ($eps <= 0 || $bvps <= 0) {
            return null;
        }

        return round(sqrt(22.5 * $eps * $bvps), 2);
    }

    /**
     * PBV-based Fair Value (fallback for negative-EPS companies).
     *
     * Uses conservative PBV = 1.0 as fair value benchmark.
     * If stock trades at PBV < 1.0, it's below book value (potentially undervalued).
     * If PBV > 1.5, it's trading at premium (potentially overvalued).
     *
     * Fair Value = BVPS × 1.0 (conservative: price = book value)
     */
    public function calculatePbvFairValue(float $bvps): ?float
    {
        if ($bvps <= 0) {
            return null;
        }

        // Conservative: fair price = 1× book value for loss-making companies
        return round($bvps * 1.0, 2);
    }

    /**
     * Margin of Safety = ((Fair Value - Price) / Fair Value) × 100
     */
    public function calculateMarginOfSafety(float $currentPrice, ?float $fairValue): float
    {
        if ($fairValue === null || $fairValue === 0.0) {
            return -100.0; // Worst case
        }

        return round((($fairValue - $currentPrice) / $fairValue) * 100, 2);
    }
}
