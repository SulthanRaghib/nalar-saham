<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * TechnicalAnalyzer
 *
 * Menghitung indikator teknikal dari data OHLCV historis.
 * Menghasilkan sinyal trading berdasarkan kombinasi indikator.
 *
 * Indikator yang dihitung:
 * - Moving Averages (MA5, MA10, MA20, MA50)
 * - RSI (14-period)
 * - MACD (12, 26, 9)
 * - Bollinger Bands (20, 2)
 * - ATR (14-period)
 * - Support & Resistance (swing high/low)
 */
class TechnicalAnalyzer
{
    /**
     * Analisis teknikal lengkap dari data OHLCV.
     *
     * @param array $candles Array of ['date','open','high','low','close','volume']
     * @return array Hasil analisis teknikal
     */
    public function analyze(array $candles): array
    {
        if (count($candles) < 5) {
            Log::warning('TechnicalAnalyzer: Data tidak cukup (' . count($candles) . ' candles)');
            return $this->emptyResult();
        }

        $closes = array_column($candles, 'close');
        $highs = array_column($candles, 'high');
        $lows = array_column($candles, 'low');
        $volumes = array_column($candles, 'volume');

        $ma5  = $this->sma($closes, 5);
        $ma10 = $this->sma($closes, 10);
        $ma20 = $this->sma($closes, 20);
        $ma50 = $this->sma($closes, 50);

        $rsiValue = $this->rsi($closes, 14);
        $macdData = $this->macd($closes);
        $bbData   = $this->bollingerBands($closes, 20, 2);
        $atrValue = $this->atr($highs, $lows, $closes, 14);
        $sr       = $this->supportResistance($highs, $lows, $closes);

        $lastClose = end($closes);
        $atrPct = ($lastClose > 0 && $atrValue !== null)
            ? round(($atrValue / $lastClose) * 100, 2)
            : 0;

        // Trend
        $trend = $this->determineTrend($ma20, $ma50, $rsiValue, $lastClose);

        // Signal
        $signal = $this->generateSignal($rsiValue, $macdData, $bbData, $trend, $lastClose, $ma20);

        Log::debug('TechnicalAnalyzer: Analisis selesai', [
            'rsi' => $rsiValue,
            'trend' => $trend['direction'],
            'signal' => $signal['action'],
        ]);

        return [
            'moving_averages' => [
                'ma5'  => $ma5 !== null ? round($ma5, 2) : null,
                'ma10' => $ma10 !== null ? round($ma10, 2) : null,
                'ma20' => $ma20 !== null ? round($ma20, 2) : null,
                'ma50' => $ma50 !== null ? round($ma50, 2) : null,
            ],
            'rsi' => [
                'value'  => $rsiValue !== null ? round($rsiValue, 2) : null,
                'status' => $this->rsiStatus($rsiValue),
            ],
            'macd' => $macdData,
            'bollinger' => $bbData,
            'atr' => [
                'value'      => $atrValue !== null ? round($atrValue, 2) : null,
                'percentage' => $atrPct,
            ],
            'support_resistance' => $sr,
            'trend'  => $trend,
            'signal' => $signal,
        ];
    }

    // ===================================================================
    // Indicator Calculations
    // ===================================================================

    private function sma(array $data, int $period): ?float
    {
        if (count($data) < $period) {
            return null;
        }

        $slice = array_slice($data, -$period);
        return array_sum($slice) / $period;
    }

    private function ema(array $data, int $period): ?float
    {
        if (count($data) < $period) {
            return null;
        }

        $multiplier = 2 / ($period + 1);
        $ema = $this->sma(array_slice($data, 0, $period), $period);

        for ($i = $period; $i < count($data); $i++) {
            $ema = ($data[$i] - $ema) * $multiplier + $ema;
        }

        return $ema;
    }

    /**
     * RSI (Relative Strength Index) — Wilder Smoothing.
     */
    private function rsi(array $closes, int $period = 14): ?float
    {
        if (count($closes) < $period + 1) {
            return null;
        }

        $gains = [];
        $losses = [];

        for ($i = 1; $i < count($closes); $i++) {
            $change = $closes[$i] - $closes[$i - 1];
            $gains[] = $change > 0 ? $change : 0;
            $losses[] = $change < 0 ? abs($change) : 0;
        }

        if (count($gains) < $period) {
            return null;
        }

        // Initial average
        $avgGain = array_sum(array_slice($gains, 0, $period)) / $period;
        $avgLoss = array_sum(array_slice($losses, 0, $period)) / $period;

        // Wilder smoothing
        for ($i = $period; $i < count($gains); $i++) {
            $avgGain = (($avgGain * ($period - 1)) + $gains[$i]) / $period;
            $avgLoss = (($avgLoss * ($period - 1)) + $losses[$i]) / $period;
        }

        if ($avgLoss == 0) {
            return 100.0;
        }

        $rs = $avgGain / $avgLoss;
        return 100 - (100 / (1 + $rs));
    }

    /**
     * MACD (12, 26, 9).
     */
    private function macd(array $closes): array
    {
        $ema12 = $this->ema($closes, 12);
        $ema26 = $this->ema($closes, 26);

        if ($ema12 === null || $ema26 === null) {
            return [
                'macd_line'   => null,
                'signal_line' => null,
                'histogram'   => null,
                'status'      => 'Data tidak cukup',
            ];
        }

        $macdLine = $ema12 - $ema26;

        // Calculate MACD line series for signal line
        $macdSeries = [];
        $multiplier12 = 2 / 13;
        $multiplier26 = 2 / 27;

        $ema12Val = array_sum(array_slice($closes, 0, 12)) / 12;
        $ema26Val = array_sum(array_slice($closes, 0, 26)) / 26;

        for ($i = 26; $i < count($closes); $i++) {
            $ema12Val = ($closes[$i] - $ema12Val) * $multiplier12 + $ema12Val;
            $ema26Val = ($closes[$i] - $ema26Val) * $multiplier26 + $ema26Val;
            $macdSeries[] = $ema12Val - $ema26Val;
        }

        $signalLine = count($macdSeries) >= 9
            ? $this->emaFromSeries($macdSeries, 9)
            : $macdLine;

        $histogram = $macdLine - $signalLine;

        $status = $histogram > 0
            ? ($macdLine > 0 ? 'Bullish Kuat' : 'Bullish')
            : ($macdLine < 0 ? 'Bearish Kuat' : 'Bearish');

        return [
            'macd_line'   => round($macdLine, 2),
            'signal_line' => round($signalLine, 2),
            'histogram'   => round($histogram, 2),
            'status'      => $status,
        ];
    }

    private function emaFromSeries(array $data, int $period): float
    {
        $multiplier = 2 / ($period + 1);
        $ema = array_sum(array_slice($data, 0, $period)) / $period;

        for ($i = $period; $i < count($data); $i++) {
            $ema = ($data[$i] - $ema) * $multiplier + $ema;
        }

        return $ema;
    }

    /**
     * Bollinger Bands (20, 2).
     */
    private function bollingerBands(array $closes, int $period = 20, float $stdDev = 2): array
    {
        if (count($closes) < $period) {
            return [
                'upper'    => null,
                'middle'   => null,
                'lower'    => null,
                'position' => 'Data tidak cukup',
            ];
        }

        $slice = array_slice($closes, -$period);
        $middle = array_sum($slice) / $period;

        $squaredDiffs = array_map(fn($v) => pow($v - $middle, 2), $slice);
        $std = sqrt(array_sum($squaredDiffs) / $period);

        $upper = $middle + ($stdDev * $std);
        $lower = $middle - ($stdDev * $std);

        $lastClose = end($closes);
        $range = $upper - $lower;
        $position = 'Tengah';

        if ($range > 0) {
            $pctPosition = ($lastClose - $lower) / $range;
            if ($pctPosition <= 0.2) {
                $position = 'Dekat Lower Band (Oversold)';
            } elseif ($pctPosition >= 0.8) {
                $position = 'Dekat Upper Band (Overbought)';
            } else {
                $position = 'Tengah';
            }
        }

        return [
            'upper'    => round($upper, 2),
            'middle'   => round($middle, 2),
            'lower'    => round($lower, 2),
            'position' => $position,
        ];
    }

    /**
     * ATR (Average True Range) — 14 period.
     */
    private function atr(array $highs, array $lows, array $closes, int $period = 14): ?float
    {
        $len = count($closes);
        if ($len < $period + 1) {
            return null;
        }

        $trueRanges = [];

        for ($i = 1; $i < $len; $i++) {
            $tr = max(
                $highs[$i] - $lows[$i],
                abs($highs[$i] - $closes[$i - 1]),
                abs($lows[$i] - $closes[$i - 1])
            );
            $trueRanges[] = $tr;
        }

        // Initial ATR = simple average
        $atr = array_sum(array_slice($trueRanges, 0, $period)) / $period;

        // Wilder smoothing
        for ($i = $period; $i < count($trueRanges); $i++) {
            $atr = (($atr * ($period - 1)) + $trueRanges[$i]) / $period;
        }

        return $atr;
    }

    /**
     * Support & Resistance from swing highs/lows.
     */
    private function supportResistance(array $highs, array $lows, array $closes): array
    {
        $lookback = min(20, count($closes));

        if ($lookback < 5) {
            return ['support' => null, 'resistance' => null];
        }

        $recentHighs = array_slice($highs, -$lookback);
        $recentLows = array_slice($lows, -$lookback);
        $lastClose = end($closes);

        // Find swing lows (support) below current price
        $supports = [];
        for ($i = 1; $i < count($recentLows) - 1; $i++) {
            if ($recentLows[$i] < $recentLows[$i - 1] && $recentLows[$i] < $recentLows[$i + 1]) {
                if ($recentLows[$i] < $lastClose) {
                    $supports[] = $recentLows[$i];
                }
            }
        }

        // Find swing highs (resistance) above current price
        $resistances = [];
        for ($i = 1; $i < count($recentHighs) - 1; $i++) {
            if ($recentHighs[$i] > $recentHighs[$i - 1] && $recentHighs[$i] > $recentHighs[$i + 1]) {
                if ($recentHighs[$i] > $lastClose) {
                    $resistances[] = $recentHighs[$i];
                }
            }
        }

        // Nearest support = highest of the lows below price
        $support = !empty($supports) ? max($supports) : min($recentLows);

        // Nearest resistance = lowest of the highs above price
        $resistance = !empty($resistances) ? min($resistances) : max($recentHighs);

        return [
            'support'    => round($support, 2),
            'resistance' => round($resistance, 2),
        ];
    }

    // ===================================================================
    // Trend & Signal
    // ===================================================================

    private function determineTrend(?float $ma20, ?float $ma50, ?float $rsi, float $lastClose): array
    {
        $direction = 'Sideways';
        $strength = 'Lemah';

        if ($ma20 !== null && $ma50 !== null) {
            if ($ma20 > $ma50 && $lastClose > $ma20) {
                $direction = 'Bullish';
                $strength = ($lastClose > $ma20 * 1.02) ? 'Kuat' : 'Sedang';
            } elseif ($ma20 < $ma50 && $lastClose < $ma20) {
                $direction = 'Bearish';
                $strength = ($lastClose < $ma20 * 0.98) ? 'Kuat' : 'Sedang';
            }
        } elseif ($ma20 !== null) {
            $direction = $lastClose > $ma20 ? 'Bullish' : 'Bearish';
            $strength = 'Sedang';
        }

        // RSI confirmation
        if ($rsi !== null) {
            if ($rsi > 70 && $direction === 'Bullish') {
                $strength = 'Sangat Kuat (Overbought)';
            } elseif ($rsi < 30 && $direction === 'Bearish') {
                $strength = 'Sangat Lemah (Oversold)';
            }
        }

        return ['direction' => $direction, 'strength' => $strength];
    }

    private function generateSignal(?float $rsi, array $macd, array $bb, array $trend, float $lastClose, ?float $ma20): array
    {
        $score = 0; // -100 to +100

        // RSI
        if ($rsi !== null) {
            if ($rsi < 30) $score += 30;
            elseif ($rsi < 40) $score += 15;
            elseif ($rsi > 70) $score -= 30;
            elseif ($rsi > 60) $score -= 15;
        }

        // MACD
        if (($macd['histogram'] ?? null) !== null) {
            $score += $macd['histogram'] > 0 ? 20 : -20;
        }

        // Bollinger
        if ($bb['lower'] !== null && $bb['upper'] !== null && ($bb['upper'] - $bb['lower']) > 0) {
            $pct = ($lastClose - $bb['lower']) / ($bb['upper'] - $bb['lower']);
            if ($pct <= 0.2) $score += 20;
            elseif ($pct >= 0.8) $score -= 20;
        }

        // Trend
        if ($trend['direction'] === 'Bullish') $score += 15;
        elseif ($trend['direction'] === 'Bearish') $score -= 15;

        // MA position
        if ($ma20 !== null) {
            $score += $lastClose > $ma20 ? 10 : -10;
        }

        // Clamp
        $score = max(-100, min(100, $score));
        $confidence = (int) abs($score);

        $action = match (true) {
            $score >= 30  => 'BUY',
            $score <= -30 => 'SELL',
            default       => 'HOLD',
        };

        return ['action' => $action, 'confidence' => $confidence];
    }

    private function rsiStatus(?float $rsi): string
    {
        if ($rsi === null) return 'N/A';
        if ($rsi < 30) return 'Oversold';
        if ($rsi > 70) return 'Overbought';
        return 'Netral';
    }

    private function emptyResult(): array
    {
        return [
            'moving_averages' => ['ma5' => null, 'ma10' => null, 'ma20' => null, 'ma50' => null],
            'rsi'       => ['value' => null, 'status' => 'N/A'],
            'macd'      => ['macd_line' => null, 'signal_line' => null, 'histogram' => null, 'status' => 'N/A'],
            'bollinger' => ['upper' => null, 'middle' => null, 'lower' => null, 'position' => 'N/A'],
            'atr'       => ['value' => null, 'percentage' => 0],
            'support_resistance' => ['support' => null, 'resistance' => null],
            'trend'  => ['direction' => 'N/A', 'strength' => 'N/A'],
            'signal' => ['action' => 'HOLD', 'confidence' => 0],
        ];
    }
}
