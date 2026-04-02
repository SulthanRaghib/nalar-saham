<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * HealthScorer
 *
 * Evaluates a company's fundamental financial health using key metrics.
 * Provides investment verdicts based on valuation and health scores.
 */
class HealthScorer
{
    /**
     * Minimum health score for "good" investment
     */
    private const MINIMUM_HEALTH_SCORE = 2;

    /**
     * Calculate Fundamental Health Score
     *
     * Scoring Logic:
     * - Start with 0 points
     * - Add 1 point if Debt-to-Equity Ratio (DER) < 1.0 (Low leverage)
     * - Add 1 point if Return on Equity (ROE) > 15% (Strong profitability)
     * - Add 1 point if Net Profit Margin (NPM) > 10% (High efficiency)
     * - Maximum score: 3
     *
     * Ratios Explanation:
     *
     * DER (Debt-to-Equity) = Total Debt / Total Equity
     *   - Measures financial leverage and solvency risk
     *   - Below 1.0 = company uses less debt than equity (conservative, healthy)
     *   - Above 1.0 = high debt burden relative to equity (risky)
     *
     * ROE (Return on Equity) = Net Income / Shareholders' Equity
     *   - Measures how efficiently the company generates profits from shareholder capital
     *   - Above 15% = company is generating strong returns
     *   - Below 5% = weak capital efficiency
     *
     * NPM (Net Profit Margin) = Net Income / Total Revenue
     *   - Measures what % of revenue becomes profit
     *   - Above 10% = highly efficient at converting sales to profit
     *   - Below 5% = low profitability relative to sales
     *
     * @param float $der Debt-to-Equity Ratio (total debt ÷ total equity)
     * @param float $roe Return on Equity as percentage (e.g., 20.5 for 20.5%)
     * @param float $npm Net Profit Margin as percentage (e.g., 15.3 for 15.3%)
     * @return int Health score from 0 to 3
     *
     * Example:
     * - DER = 0.85 (< 1.0) → +1
     * - ROE = 22% (> 15%) → +1
     * - NPM = 12% (> 10%) → +1
     * - Total Score = 3 (Excellent health)
     */
    public function calculateScore(float $der, float $roe, float $npm): int
    {
        $score = 0;

        // Check Debt-to-Equity Ratio threshold
        if ($der < 1.0) {
            $score++;
        }

        // Check Return on Equity threshold
        if ($roe > 15.0) {
            $score++;
        }

        // Check Net Profit Margin threshold
        if ($npm > 10.0) {
            $score++;
        }

        Log::debug('HealthScorer: Health score calculated', [
            'der' => $der,
            'roe' => $roe,
            'npm' => $npm,
            'score' => $score,
        ]);

        return $score;
    }

    /**
     * Generate Investment Verdict
     *
     * Combines Margin of Safety with Fundamental Health Score to provide
     * a clear investment recommendation using Benjamin Graham's margin of safety principle.
     *
     * Decision Logic:
     * 1. If Margin of Safety > 30% AND Health Score >= 2
     *    → "Undervalued" (Strong candidate for investment)
     *    Rationale: Stock trades at significant discount with solid fundamentals
     *
     * 2. If Margin of Safety between 0% and 30%
     *    → "Fairly Valued" (Neutral stance)
     *    Rationale: Stock is priced close to intrinsic value; wait for better entry
     *
     * 3. If Margin of Safety < 0%
     *    → "Overvalued" (Avoid purchase)
     *    Rationale: Stock trades above intrinsic value; downside risk exists
     *
     * @param float $marginOfSafety Margin of Safety percentage (can be negative)
     * @param int $healthScore Fundamental health score (0-3)
     * @return string Investment verdict: "Undervalued", "Fairly Valued", or "Overvalued"
     *
     * Example Scenarios:
     * 1. MoS = 35%, Score = 3 → "Undervalued" ✓ (Excellent entry point)
     * 2. MoS = 20%, Score = 3 → "Fairly Valued" (Fair price, but not compelling)
     * 3. MoS = -5%, Score = 3 → "Overvalued" (Avoid despite good fundamentals)
     */
    public function getInvestmentVerdict(float $marginOfSafety, int $healthScore): string
    {
        // Use match expression for cleaner conditional logic (PHP 8.0+)
        $verdict = match (true) {
            // Undervalued: Strong margin of safety + healthy fundamentals
            $marginOfSafety > 30 && $healthScore >= self::MINIMUM_HEALTH_SCORE => 'Undervalued',

            // Overvalued: Stock trading above intrinsic value
            $marginOfSafety < 0 => 'Overvalued',

            // Fairly Valued: Everything between 0% and 30% MoS
            default => 'Fairly Valued',
        };

        Log::info('HealthScorer: Investment verdict generated', [
            'margin_of_safety' => $marginOfSafety,
            'health_score' => $healthScore,
            'verdict' => $verdict,
        ]);

        return $verdict;
    }

    /**
     * Get detailed health analysis breakdown
     *
     * Returns an associative array with detailed scoring breakdown.
     * Useful for frontend display of scoring logic.
     *
     * @param float $der Debt-to-Equity Ratio
     * @param float $roe Return on Equity percentage
     * @param float $npm Net Profit Margin percentage
     * @return array Detailed breakdown of health scoring
     */
    public function getHealthBreakdown(float $der, float $roe, float $npm): array
    {
        $leverageHealthy = $der < 1.0;
        $profitabilityStrong = $roe > 15.0;
        $efficiencyHigh = $npm > 10.0;

        $score = $this->calculateScore($der, $roe, $npm);

        return [
            'debt_to_equity' => [
                'value' => round($der, 2),
                'threshold' => 1.0,
                'is_healthy' => $leverageHealthy,
                'points' => $leverageHealthy ? 1 : 0,
                'interpretation' => $leverageHealthy
                    ? 'Low leverage - conservative capital structure'
                    : 'High leverage - elevated financial risk',
            ],
            'return_on_equity' => [
                'value' => round($roe, 2),
                'threshold' => 15.0,
                'is_healthy' => $profitabilityStrong,
                'points' => $profitabilityStrong ? 1 : 0,
                'interpretation' => $profitabilityStrong
                    ? 'Strong returns on equity capital'
                    : 'Weak capital efficiency',
            ],
            'net_profit_margin' => [
                'value' => round($npm, 2),
                'threshold' => 10.0,
                'is_healthy' => $efficiencyHigh,
                'points' => $efficiencyHigh ? 1 : 0,
                'interpretation' => $efficiencyHigh
                    ? 'High operational efficiency'
                    : 'Low profitability relative to sales',
            ],
            'total_score' => $score,
            'max_score' => 3,
            'rating' => match ($score) {
                0, 1 => 'Poor Health',
                2 => 'Fair Health',
                3 => 'Excellent Health',
                default => 'Unknown',
            },
        ];
    }
}
