<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * BandarmologyAnalyzer
 *
 * Analisis smart money / bandarmology berdasarkan pola volume.
 * Mendeteksi fase akumulasi vs distribusi menggunakan indikator volume-price.
 *
 * Indikator:
 * - Accumulation/Distribution Line (A/D Line)
 * - On-Balance Volume (OBV)
 * - Money Flow Index (MFI)
 * - Volume-Price Trend (VPT)
 * - Fase Wyckoff: Akumulasi, Markup, Distribusi, Markdown
 */
class BandarmologyAnalyzer
{
    /**
     * Analisis bandarmology dari data OHLCV dan trading data.
     *
     * @param array      $candles     Array of ['date','open','high','low','close','volume']
     * @param array|null $tradingData Data trading hari ini (foreign buy/sell, etc)
     * @return array Hasil analisis bandarmology
     */
    public function analyze(array $candles, ?array $tradingData = null): array
    {
        if (count($candles) < 5) {
            Log::warning('BandarmologyAnalyzer: Data tidak cukup');
            return $this->emptyResult();
        }

        $adLine   = $this->accumulationDistributionLine($candles);
        $obv      = $this->onBalanceVolume($candles);
        $mfi      = $this->moneyFlowIndex($candles, 14);
        $vpt      = $this->volumePriceTrend($candles);

        // Accumulation Score (0-100)
        $score = $this->calculateAccumulationScore($adLine, $obv, $mfi, $vpt, $candles);

        // Phase Detection
        $phase = $this->detectPhase($score, $adLine, $obv, $candles);

        // Foreign & Ritel flow
        $foreignFlow = $this->analyzeForeignFlow($tradingData);
        $ritelFlow   = $this->analyzeRitelFlow($tradingData);

        Log::debug('BandarmologyAnalyzer: Analisis selesai', [
            'score' => $score,
            'phase' => $phase['phase'],
        ]);

        return [
            'ad_line'            => $adLine,
            'obv'                => $obv,
            'mfi'                => $mfi,
            'vpt'                => $vpt,
            'accumulation_score' => $score,
            'phase'              => $phase['phase'],
            'phase_description'  => $phase['description'],
            'net_foreign'        => $foreignFlow,
            'net_ritel'          => $ritelFlow,
        ];
    }

    // ===================================================================
    // Indicator Calculations
    // ===================================================================

    /**
     * Accumulation/Distribution Line
     * MFM = ((Close - Low) - (High - Close)) / (High - Low)
     * MFV = MFM × Volume
     * A/D = Cumulative Sum of MFV
     */
    private function accumulationDistributionLine(array $candles): array
    {
        $adValues = [];
        $cumulative = 0.0;

        foreach ($candles as $c) {
            $high  = (float) $c['high'];
            $low   = (float) $c['low'];
            $close = (float) $c['close'];
            $vol   = (float) $c['volume'];

            $range = $high - $low;
            $mfm = $range > 0 ? (($close - $low) - ($high - $close)) / $range : 0;
            $mfv = $mfm * $vol;
            $cumulative += $mfv;
            $adValues[] = $cumulative;
        }

        // Determine trend from last 5 values
        $trend = $this->determineTrendFromSeries($adValues, 5);

        return [
            'value' => round($cumulative, 0),
            'trend' => $trend,
        ];
    }

    /**
     * On-Balance Volume (OBV)
     * Adds volume on up-close days, subtracts on down-close days.
     */
    private function onBalanceVolume(array $candles): array
    {
        $obvValues = [];
        $obv = 0.0;

        for ($i = 0; $i < count($candles); $i++) {
            if ($i === 0) {
                $obv = (float) $candles[$i]['volume'];
            } else {
                $prevClose = (float) $candles[$i - 1]['close'];
                $currClose = (float) $candles[$i]['close'];
                $vol = (float) $candles[$i]['volume'];

                if ($currClose > $prevClose) {
                    $obv += $vol;
                } elseif ($currClose < $prevClose) {
                    $obv -= $vol;
                }
            }
            $obvValues[] = $obv;
        }

        $trend = $this->determineTrendFromSeries($obvValues, 5);

        return [
            'value' => round($obv, 0),
            'trend' => $trend,
        ];
    }

    /**
     * Money Flow Index (MFI) — Volume-weighted RSI.
     */
    private function moneyFlowIndex(array $candles, int $period = 14): array
    {
        if (count($candles) < $period + 1) {
            return ['value' => null, 'status' => 'N/A'];
        }

        $typicalPrices = [];
        $rawMoneyFlows = [];

        foreach ($candles as $c) {
            $tp = ((float) $c['high'] + (float) $c['low'] + (float) $c['close']) / 3;
            $typicalPrices[] = $tp;
            $rawMoneyFlows[] = $tp * (float) $c['volume'];
        }

        $posFlow = 0.0;
        $negFlow = 0.0;

        $start = count($typicalPrices) - $period;
        for ($i = max(1, $start); $i < count($typicalPrices); $i++) {
            if ($typicalPrices[$i] > $typicalPrices[$i - 1]) {
                $posFlow += $rawMoneyFlows[$i];
            } else {
                $negFlow += $rawMoneyFlows[$i];
            }
        }

        $mfi = $negFlow > 0 ? 100 - (100 / (1 + ($posFlow / $negFlow))) : 100;
        $mfi = round($mfi, 2);

        $status = match (true) {
            $mfi < 20 => 'Oversold',
            $mfi > 80 => 'Overbought',
            default   => 'Netral',
        };

        return ['value' => $mfi, 'status' => $status];
    }

    /**
     * Volume-Price Trend (VPT)
     * VPT = Previous VPT + Volume × (Close - PrevClose) / PrevClose
     */
    private function volumePriceTrend(array $candles): array
    {
        $vptValues = [];
        $vpt = 0.0;

        for ($i = 1; $i < count($candles); $i++) {
            $prevClose = (float) $candles[$i - 1]['close'];
            $close = (float) $candles[$i]['close'];
            $vol = (float) $candles[$i]['volume'];

            if ($prevClose > 0) {
                $vpt += $vol * (($close - $prevClose) / $prevClose);
            }
            $vptValues[] = $vpt;
        }

        $trend = $this->determineTrendFromSeries($vptValues, 5);

        return [
            'value' => round($vpt, 0),
            'trend' => $trend,
        ];
    }

    // ===================================================================
    // Scoring & Detection
    // ===================================================================

    private function calculateAccumulationScore(array $adLine, array $obv, array $mfi, array $vpt, array $candles): int
    {
        $score = 50; // Netral base

        // A/D Line trend (+/-15)
        match ($adLine['trend']) {
            'Naik'  => $score += 15,
            'Turun' => $score -= 15,
            default => null,
        };

        // OBV trend (+/-15)
        match ($obv['trend']) {
            'Naik'  => $score += 15,
            'Turun' => $score -= 15,
            default => null,
        };

        // MFI value (+/-15)
        if ($mfi['value'] !== null) {
            if ($mfi['value'] > 60) $score += 15;
            elseif ($mfi['value'] > 40) $score += 5;
            elseif ($mfi['value'] < 20) $score -= 15;
            else $score -= 5;
        }

        // VPT trend (+/-10)
        match ($vpt['trend']) {
            'Naik'  => $score += 10,
            'Turun' => $score -= 10,
            default => null,
        };

        // Volume surge check (+/-5)
        $recentVols = array_slice(array_column($candles, 'volume'), -5);
        $avgVol = count($recentVols) > 0 ? array_sum($recentVols) / count($recentVols) : 0;
        $prevVols = array_slice(array_column($candles, 'volume'), -20, 15);
        $prevAvgVol = count($prevVols) > 0 ? array_sum($prevVols) / count($prevVols) : 0;

        if ($prevAvgVol > 0 && $avgVol > $prevAvgVol * 1.5) {
            // Volume surge — could be accumulation or distribution
            $lastCloses = array_slice(array_column($candles, 'close'), -5);
            $priceUp = end($lastCloses) > reset($lastCloses);
            $score += $priceUp ? 5 : -5;
        }

        return max(0, min(100, $score));
    }

    private function detectPhase(int $score, array $adLine, array $obv, array $candles): array
    {
        $closes = array_column($candles, 'close');
        $recentCloses = array_slice($closes, -10);
        $priceDirection = count($recentCloses) >= 2
            ? (end($recentCloses) > reset($recentCloses) ? 'up' : 'down')
            : 'flat';

        if ($score >= 70 && $priceDirection === 'up') {
            return [
                'phase'       => 'Markup',
                'description' => 'Harga naik dengan volume tinggi. Bandar sedang mendorong kenaikan harga setelah fase akumulasi.',
            ];
        }

        if ($score >= 60) {
            return [
                'phase'       => 'Akumulasi',
                'description' => 'Smart money sedang mengumpulkan saham secara bertahap. Volume meningkat namun harga belum naik signifikan — potensi kenaikan di depan.',
            ];
        }

        if ($score <= 30 && $priceDirection === 'down') {
            return [
                'phase'       => 'Markdown',
                'description' => 'Harga turun dengan tekanan jual tinggi. Bandar sedang melepas kepemilikan — sebaiknya hindari entry.',
            ];
        }

        if ($score <= 40) {
            return [
                'phase'       => 'Distribusi',
                'description' => 'Smart money sedang menjual saham secara bertahap ke ritel. Volume tinggi tapi harga stagnan — waspadai penurunan.',
            ];
        }

        return [
            'phase'       => 'Transisi',
            'description' => 'Belum ada sinyal akumulasi atau distribusi yang jelas. Pasar dalam fase konsolidasi — tunggu konfirmasi arah.',
        ];
    }

    // ===================================================================
    // Foreign & Ritel Flow
    // ===================================================================

    private function analyzeForeignFlow(?array $tradingData): array
    {
        if ($tradingData === null) {
            return ['value' => null, 'status' => 'N/A', 'buy' => null, 'sell' => null];
        }

        $buy  = (float) ($tradingData['foreign_buy'] ?? 0);
        $sell = (float) ($tradingData['foreign_sell'] ?? 0);
        $net  = $buy - $sell;

        return [
            'value'  => $net,
            'buy'    => $buy,
            'sell'   => $sell,
            'status' => $net >= 0 ? 'Net Buy' : 'Net Sell',
        ];
    }

    private function analyzeRitelFlow(?array $tradingData): array
    {
        if ($tradingData === null) {
            return ['value' => null, 'status' => 'N/A'];
        }

        $netRitel = (float) ($tradingData['net_ritel'] ?? 0);

        // If net_ritel not provided, estimate as inverse of foreign
        if ($netRitel == 0 && isset($tradingData['net_foreign'])) {
            $netRitel = -((float) $tradingData['net_foreign']);
        }

        return [
            'value'  => $netRitel,
            'status' => $netRitel >= 0 ? 'Net Buy' : 'Net Sell',
        ];
    }

    // ===================================================================
    // Helpers
    // ===================================================================

    private function determineTrendFromSeries(array $values, int $lookback): string
    {
        $len = count($values);
        if ($len < 2) return 'Datar';

        $recent = array_slice($values, -min($lookback, $len));
        $first = reset($recent);
        $last = end($recent);

        if ($first == 0) return 'Datar';

        $change = (($last - $first) / abs($first)) * 100;

        if ($change > 3) return 'Naik';
        if ($change < -3) return 'Turun';
        return 'Datar';
    }

    private function emptyResult(): array
    {
        return [
            'ad_line'            => ['value' => 0, 'trend' => 'N/A'],
            'obv'                => ['value' => 0, 'trend' => 'N/A'],
            'mfi'                => ['value' => null, 'status' => 'N/A'],
            'vpt'                => ['value' => 0, 'trend' => 'N/A'],
            'accumulation_score' => 50,
            'phase'              => 'N/A',
            'phase_description'  => 'Data tidak cukup untuk analisis bandarmology.',
            'net_foreign'        => ['value' => null, 'status' => 'N/A', 'buy' => null, 'sell' => null],
            'net_ritel'          => ['value' => null, 'status' => 'N/A'],
        ];
    }
}
