<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * ValuationEngine
 *
 * Core financial valuation calculator using Benjamin Graham's principles.
 * Responsible for calculating intrinsic value and margin of safety metrics.
 */
class ValuationEngine
{
    /**
     * Calculate Graham Number (Intrinsic Value using Graham's Formula)
     *
     * Graham Number = √(22.5 × EPS × BVPS)
     *
     * This is Benjamin Graham's formula for estimating a stock's intrinsic value.
     * The constant 22.5 represents 15 times earnings × 1.5 times book value.
     * This conservative formula is best suited for stable, mature companies.
     *
     * @param float $eps Earnings Per Share (annual net income ÷ outstanding shares)
     * @param float $bvps Book Value Per Share (total equity ÷ outstanding shares)
     * @return float|null Graham Number (intrinsic value) or null if calculation impossible
     *
     * Example:
     * - EPS: $5.00, BVPS: $12.00
     * - Graham Number = √(22.5 × 5 × 12) = √1350 ≈ $36.74
     */
    public function calculateGrahamNumber(float $eps, float $bvps): ?float
    {
        // Edge case: Cannot calculate square root of negative number
        if ($eps <= 0 || $bvps <= 0) {
            Log::warning('ValuationEngine: Invalid EPS or BVPS for Graham Number calculation', [
                'eps' => $eps,
                'bvps' => $bvps,
            ]);
            return null;
        }

        try {
            // Graham Formula: √(22.5 × EPS × BVPS)
            $grahamNumber = sqrt(22.5 * $eps * $bvps);

            return round($grahamNumber, 2);
        } catch (\Exception $e) {
            Log::error('ValuationEngine: Error calculating Graham Number', [
                'eps' => $eps,
                'bvps' => $bvps,
                'exception' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Calculate Margin of Safety (MoS)
     *
     * Margin of Safety = ((Fair Value - Current Price) / Fair Value) × 100
     *
     * MoS represents the buffer between the stock's current price and its intrinsic value.
     * A higher MoS indicates a safer investment with less downside risk.
     *
     * Formula Interpretation:
     * - MoS > 30%: Stock is trading at significant discount (GOOD)
     * - MoS 0-30%: Stock is fairly valued
     * - MoS < 0%: Stock is overvalued (NOT RECOMMENDED)
     *
     * @param float $currentPrice Current market price of the stock
     * @param float $fairValue Calculated intrinsic/fair value of the stock
     * @return float Margin of Safety as a percentage (can be negative)
     *
     * Example:
     * - Fair Value: $50.00, Current Price: $35.00
     * - MoS = ((50 - 35) / 50) × 100 = 30%
     */
    public function calculateMarginOfSafety(float $currentPrice, float $fairValue): float
    {
        // Prevent division by zero
        if ($fairValue === 0.0) {
            Log::warning('ValuationEngine: Fair value is zero, cannot calculate MoS', [
                'current_price' => $currentPrice,
                'fair_value' => $fairValue,
            ]);
            return 0.0;
        }

        // MoS = ((Fair Value - Current Price) / Fair Value) × 100
        $marginOfSafety = (($fairValue - $currentPrice) / $fairValue) * 100;

        return round($marginOfSafety, 2);
    }

    /**
     * Calculate Fair Value using PEG Ratio method (alternative valuation)
     *
     * This is an optional helper method for additional valuation perspective.
     *
     * @param float $earnings Trailing twelve-month earnings
     * @param float $growthRate Expected annual growth rate (as decimal, e.g., 0.10 for 10%)
     * @return float|null Fair value estimate or null on invalid input
     */
    public function calculatePEGBasedValue(float $earnings, float $growthRate): ?float
    {
        if ($earnings <= 0 || $growthRate < 0) {
            Log::warning('ValuationEngine: Invalid earnings or growth rate for PEG valuation', [
                'earnings' => $earnings,
                'growth_rate' => $growthRate,
            ]);
            return null;
        }

        // Simple PEG: Fair Value = (Earnings × (1 + Growth Rate × PE Ratio)) / Discount Rate
        $peRatio = 15; // Conservative baseline PE ratio
        $discountRate = 0.10; // 10% required rate of return

        $fairValue = ($earnings * $peRatio * (1 + $growthRate)) / $discountRate;

        return round($fairValue, 2);
    }
}
