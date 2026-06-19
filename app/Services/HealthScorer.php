<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * HealthScorer
 *
 * Evaluates fundamental financial health using a weighted scoring system (0–100).
 * Properly handles negative metrics, anomalous values, and loss-making companies.
 *
 * Score Breakdown (max 100):
 * - Leverage (DER):     0–25 points
 * - Profitability (ROE): 0–30 points (largest weight — most important)
 * - Efficiency (NPM):   0–25 points
 * - Earnings Quality:   0–20 points (EPS positivity + consistency signals)
 */
class HealthScorer
{
    /**
     * Calculate comprehensive health score (0–100).
     *
     * @param float      $der  Debt-to-Equity Ratio
     * @param float      $roe  Return on Equity (percentage, e.g. 20.5)
     * @param float      $npm  Net Profit Margin (percentage, e.g. 15.3)
     * @param float|null $eps  Earnings Per Share (optional but improves accuracy)
     * @param float|null $bvps Book Value Per Share (optional)
     * @return array{score: int, max: int, grade: string, breakdown: array, red_flags: string[]}
     */
    public function analyze(
        float $der,
        float $roe,
        float $npm,
        ?float $eps = null,
        ?float $bvps = null,
    ): array {
        $breakdown = [];
        $redFlags = [];
        $totalScore = 0;

        // ---------------------------------------------------------------
        // 1. LEVERAGE — DER (0–25 points)
        // ---------------------------------------------------------------
        $leverageScore = $this->scoreLeverage($der);
        $breakdown['leverage'] = [
            'label'    => 'Leverage (DER)',
            'value'    => round($der, 2),
            'score'    => $leverageScore,
            'max'      => 25,
            'status'   => $leverageScore >= 20 ? 'Sangat Baik' : ($leverageScore >= 10 ? 'Cukup' : 'Berisiko'),
        ];
        $totalScore += $leverageScore;

        if ($der > 2) {
            $redFlags[] = 'DER > 2.0 — Hutang sangat tinggi, risiko gagal bayar tinggi.';
        }

        // ---------------------------------------------------------------
        // 2. PROFITABILITY — ROE (0–30 points)
        // ---------------------------------------------------------------
        $roeScore = $this->scoreRoe($roe);
        $breakdown['profitability'] = [
            'label'    => 'Profitabilitas (ROE)',
            'value'    => round($roe, 2) . '%',
            'score'    => $roeScore,
            'max'      => 30,
            'status'   => $roeScore >= 25 ? 'Sangat Baik' : ($roeScore >= 15 ? 'Cukup' : ($roeScore > 0 ? 'Lemah' : 'Merugi')),
        ];
        $totalScore += $roeScore;

        if ($roe < 0) {
            $redFlags[] = 'ROE negatif — Perusahaan merugi, modal pemegang saham menyusut.';
        }

        // ---------------------------------------------------------------
        // 3. EFFICIENCY — NPM (0–25 points)
        // ---------------------------------------------------------------
        $npmScore = $this->scoreNpm($npm);
        $breakdown['efficiency'] = [
            'label'    => 'Efisiensi (NPM)',
            'value'    => round($npm, 2) . '%',
            'score'    => $npmScore,
            'max'      => 25,
            'status'   => $npmScore >= 20 ? 'Sangat Baik' : ($npmScore >= 10 ? 'Cukup' : ($npmScore > 0 ? 'Lemah' : 'Merugi')),
        ];
        $totalScore += $npmScore;

        if ($npm > 100) {
            $redFlags[] = 'NPM > 100% — Anomali: kemungkinan one-time gain, bukan laba operasional berkelanjutan.';
        }
        if ($npm < 0) {
            $redFlags[] = 'NPM negatif — Perusahaan tidak menghasilkan laba dari pendapatan.';
        }

        // ---------------------------------------------------------------
        // 4. EARNINGS QUALITY (0–20 points)
        // ---------------------------------------------------------------
        $earningsScore = $this->scoreEarningsQuality($eps, $bvps);
        $breakdown['earnings_quality'] = [
            'label'    => 'Kualitas Laba',
            'value'    => $eps !== null ? round($eps, 2) : 'N/A',
            'score'    => $earningsScore,
            'max'      => 20,
            'status'   => $earningsScore >= 15 ? 'Baik' : ($earningsScore >= 5 ? 'Cukup' : 'Buruk'),
        ];
        $totalScore += $earningsScore;

        if ($eps !== null && $eps < 0) {
            $redFlags[] = 'EPS negatif — Perusahaan sedang merugi per lembar saham.';
        }
        if ($bvps !== null && $bvps < 0) {
            $redFlags[] = 'BVPS negatif — Defisiensi modal, ekuitas lebih kecil dari hutang.';
        }

        // ---------------------------------------------------------------
        // 5. Grade
        // ---------------------------------------------------------------
        $grade = match (true) {
            $totalScore >= 80 => 'A',  // Excellent
            $totalScore >= 60 => 'B',  // Good
            $totalScore >= 40 => 'C',  // Fair
            $totalScore >= 20 => 'D',  // Poor
            default           => 'F',  // Failing
        };

        $gradeLabel = match ($grade) {
            'A' => 'Sangat Sehat',
            'B' => 'Sehat',
            'C' => 'Cukup',
            'D' => 'Lemah',
            'F' => 'Kritis',
        };

        Log::debug('HealthScorer: Analysis complete', [
            'score' => $totalScore,
            'grade' => $grade,
            'flags' => count($redFlags),
        ]);

        return [
            'score'      => $totalScore,
            'max'        => 100,
            'grade'      => $grade,
            'grade_label' => $gradeLabel,
            'breakdown'  => $breakdown,
            'red_flags'  => $redFlags,
        ];
    }

    /**
     * Get investment verdict considering both valuation and health.
     */
    public function getInvestmentVerdict(
        ?float $marginOfSafety,
        int $healthScore,
        array $redFlags = [],
    ): array {
        // If we can't value the stock
        if ($marginOfSafety === null) {
            return [
                'status'  => 'AVOID',
                'verdict' => 'Tidak Dapat Divaluasi',
                'reason'  => 'Data fundamental tidak memadai untuk menghitung nilai wajar.',
            ];
        }

        // Critical red flags → automatic AVOID regardless of valuation
        if (count($redFlags) >= 3) {
            return [
                'status'  => 'AVOID',
                'verdict' => 'Terlalu Berisiko',
                'reason'  => 'Terlalu banyak red flag fundamental (' . count($redFlags) . ' masalah terdeteksi).',
            ];
        }

        // Primary logic based on Margin of Safety + Health Score
        if ($marginOfSafety > 30 && $healthScore >= 60) {
            return [
                'status'  => 'BUY',
                'verdict' => 'Undervalued',
                'reason'  => 'Harga di bawah nilai wajar dengan fundamental kuat.',
            ];
        }

        if ($marginOfSafety > 30 && $healthScore >= 40) {
            return [
                'status'  => 'HOLD',
                'verdict' => 'Undervalued (Fundamental Lemah)',
                'reason'  => 'Harga murah tapi fundamental perlu perbaikan.',
            ];
        }

        if ($marginOfSafety >= 0 && $marginOfSafety <= 30) {
            return [
                'status'  => 'HOLD',
                'verdict' => 'Fairly Valued',
                'reason'  => 'Harga dekat dengan nilai wajar, tunggu koreksi untuk entry yang lebih baik.',
            ];
        }

        if ($marginOfSafety < -30) {
            return [
                'status'  => 'AVOID',
                'verdict' => 'Sangat Overvalued',
                'reason'  => 'Harga terlalu tinggi di atas nilai wajar (MoS: ' . number_format($marginOfSafety, 1) . '%).',
            ];
        }

        // MoS between -30% and 0%
        return [
            'status'  => 'AVOID',
            'verdict' => 'Overvalued',
            'reason'  => 'Harga di atas nilai wajar, risiko penurunan harga.',
        ];
    }

    // ===================================================================
    // Scoring Functions
    // ===================================================================

    /**
     * DER Scoring (0–25):
     * < 0.5 → 25 (very conservative)
     * 0.5 – 1.0 → 20
     * 1.0 – 1.5 → 12
     * 1.5 – 2.0 → 5
     * > 2.0 → 0
     */
    private function scoreLeverage(float $der): int
    {
        return match (true) {
            $der < 0.5 => 25,
            $der < 1.0 => 20,
            $der < 1.5 => 12,
            $der < 2.0 => 5,
            default    => 0,
        };
    }

    /**
     * ROE Scoring (0–30):
     * > 20% → 30 (excellent)
     * 15–20% → 25
     * 10–15% → 18
     * 5–10%  → 10
     * 0–5%   → 5 (barely profitable)
     * < 0%   → 0 (loss-making)
     */
    private function scoreRoe(float $roe): int
    {
        return match (true) {
            $roe > 20  => 30,
            $roe > 15  => 25,
            $roe > 10  => 18,
            $roe > 5   => 10,
            $roe >= 0  => 5,
            default    => 0, // negative ROE
        };
    }

    /**
     * NPM Scoring (0–25):
     * > 100% → 5 (anomalous — one-time gain likely, not sustainable)
     * 20–100% → 25
     * 10–20%  → 20
     * 5–10%   → 12
     * 0–5%    → 5
     * < 0%    → 0
     */
    private function scoreNpm(float $npm): int
    {
        // Anomaly: NPM > 100% is suspicious — likely one-time gain
        if ($npm > 100) {
            return 5;
        }

        return match (true) {
            $npm > 20  => 25,
            $npm > 10  => 20,
            $npm > 5   => 12,
            $npm >= 0  => 5,
            default    => 0, // negative NPM
        };
    }

    /**
     * Earnings Quality (0–20):
     * EPS > 0 AND BVPS > 0 → 20
     * EPS > 0 only          → 12
     * BVPS > 0 only         → 8
     * Both negative          → 0
     */
    private function scoreEarningsQuality(?float $eps, ?float $bvps): int
    {
        $epsPositive = $eps !== null && $eps > 0;
        $bvpsPositive = $bvps !== null && $bvps > 0;

        if ($epsPositive && $bvpsPositive) {
            return 20;
        }
        if ($epsPositive) {
            return 12;
        }
        if ($bvpsPositive) {
            return 8;
        }
        return 0;
    }
}
