<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * TradingPlanGenerator
 *
 * Menghasilkan rencana trading otomatis: area entry, stop loss, take profit.
 * Menghitung probabilitas kenaikan dan outlook berdasarkan kombinasi indikator.
 */
class TradingPlanGenerator
{
    /**
     * Generate rencana trading lengkap.
     */
    public function generate(
        float $currentPrice,
        array $technicalData,
        array $bandarmologyData,
        ?array $valuationData = null,
    ): array {
        $support    = (float) ($technicalData['support_resistance']['support'] ?? $currentPrice * 0.95);
        $resistance = (float) ($technicalData['support_resistance']['resistance'] ?? $currentPrice * 1.05);
        $atr        = (float) ($technicalData['atr']['value'] ?? $currentPrice * 0.02);
        $ma20       = $technicalData['moving_averages']['ma20'] ?? null;
        $bbLower    = $technicalData['bollinger']['lower'] ?? null;
        $rsi        = $technicalData['rsi']['value'] ?? null;
        $trend      = $technicalData['trend']['direction'] ?? 'Sideways';
        $macdHist   = $technicalData['macd']['histogram'] ?? null;
        $accScore   = (int) ($bandarmologyData['accumulation_score'] ?? 50);
        $mos        = $valuationData['margin_of_safety'] ?? null;
        $fairValue  = $valuationData['fair_value'] ?? null;

        // === ENTRY AREA ===
        $entryLow  = max($support, $bbLower ?? $support);
        $entryHigh = $ma20 !== null ? min($currentPrice, $ma20) : $currentPrice;

        // Ensure entry range makes sense
        if ($entryLow > $entryHigh) {
            $entryLow = $currentPrice * 0.97;
            $entryHigh = $currentPrice;
        }

        $entryMid = ($entryLow + $entryHigh) / 2;

        // === STOP LOSS ===
        $slPrice = $entryLow - ($atr * 1.5);
        $slPrice = max($slPrice, $support - $atr); // Don't go too far below support
        $slPrice = round($slPrice, 0);
        $slPct = $entryMid > 0 ? round((($slPrice - $entryMid) / $entryMid) * 100, 2) : 0;

        // === TAKE PROFIT ===
        $riskPerShare = $entryMid - $slPrice;

        // TP1: Risk-Reward 1:2
        $tp1Price = round($entryMid + ($riskPerShare * 2), 0);
        $tp1Pct = $entryMid > 0 ? round((($tp1Price - $entryMid) / $entryMid) * 100, 2) : 0;

        // TP2: Risk-Reward 1:3 or resistance, whichever is closer
        $tp2FromRR = $entryMid + ($riskPerShare * 3);
        $tp2Price = round(min($tp2FromRR, $resistance > $tp1Price ? $resistance : $tp2FromRR), 0);
        $tp2Pct = $entryMid > 0 ? round((($tp2Price - $entryMid) / $entryMid) * 100, 2) : 0;

        // === RISK-REWARD RATIO ===
        $rr = $riskPerShare > 0 ? round(($tp1Price - $entryMid) / $riskPerShare, 1) : 0;

        // === PROBABILITAS KENAIKAN ===
        $prob = $this->calculateProbability($trend, $rsi, $macdHist, $accScore, $mos);

        // === OUTLOOK ===
        $outlook = $this->determineOutlook($prob, $trend, $accScore);

        // === DURASI ===
        $durasi = $this->recommendDuration($trend, $technicalData['trend']['strength'] ?? '', $mos);

        // === CATATAN ===
        $catatan = $this->generateNotes($technicalData, $bandarmologyData, $valuationData, $currentPrice);

        Log::debug('TradingPlanGenerator: Plan generated', [
            'entry' => [$entryLow, $entryHigh],
            'sl' => $slPrice,
            'tp1' => $tp1Price,
            'prob' => $prob,
        ]);

        return [
            'entry_area' => [
                'low'  => round($entryLow, 0),
                'high' => round($entryHigh, 0),
            ],
            'stop_loss' => [
                'price'      => $slPrice,
                'percentage' => $slPct,
            ],
            'take_profit' => [
                'tp1' => [
                    'price'      => $tp1Price,
                    'percentage' => $tp1Pct,
                    'label'      => 'Target 1 (R:R 1:2)',
                ],
                'tp2' => [
                    'price'      => $tp2Price,
                    'percentage' => $tp2Pct,
                    'label'      => 'Target 2 (R:R 1:3)',
                ],
            ],
            'risk_reward'        => $rr,
            'probabilitas'       => $prob,
            'outlook'            => $outlook['label'],
            'outlook_description' => $outlook['description'],
            'durasi'             => $durasi,
            'catatan'            => $catatan,
            'fair_value'         => $fairValue,
            'margin_of_safety'   => $mos,
        ];
    }

    private function calculateProbability(string $trend, ?float $rsi, ?float $macdHist, int $accScore, ?float $mos): int
    {
        $prob = 0;

        // Trend direction (0-30)
        $prob += match ($trend) {
            'Bullish' => 30,
            'Sideways' => 10,
            default => 0,
        };

        // RSI (0-25)
        if ($rsi !== null) {
            $prob += match (true) {
                $rsi < 30 => 25,   // Oversold — high reversal probability
                $rsi < 45 => 18,
                $rsi > 70 => 0,    // Overbought — low upside probability
                $rsi > 55 => 10,
                default   => 15,
            };
        }

        // MACD (0-15)
        if ($macdHist !== null) {
            $prob += $macdHist > 0 ? 15 : 0;
        }

        // Accumulation score (0-15)
        if ($accScore > 60) $prob += 15;
        elseif ($accScore > 45) $prob += 8;

        // Margin of Safety (0-15)
        if ($mos !== null) {
            if ($mos > 30) $prob += 15;
            elseif ($mos > 10) $prob += 10;
            elseif ($mos > 0) $prob += 5;
        }

        return max(0, min(100, $prob));
    }

    private function determineOutlook(int $prob, string $trend, int $accScore): array
    {
        if ($prob >= 75) {
            return [
                'label'       => 'Sangat Positif',
                'description' => 'Kombinasi tren naik, sinyal teknikal kuat, dan akumulasi smart money menunjukkan potensi kenaikan yang tinggi.',
            ];
        }

        if ($prob >= 55) {
            return [
                'label'       => 'Positif',
                'description' => 'Mayoritas indikator mendukung kenaikan harga. Potensi upside lebih besar dari downside risk.',
            ];
        }

        if ($prob >= 35) {
            return [
                'label'       => 'Netral',
                'description' => 'Sinyal campuran — sebagian indikator bullish, sebagian bearish. Tunggu konfirmasi arah sebelum entry.',
            ];
        }

        if ($prob >= 20) {
            return [
                'label'       => 'Negatif',
                'description' => 'Mayoritas indikator menunjukkan tekanan jual. Risiko penurunan lebih besar dari potensi kenaikan.',
            ];
        }

        return [
            'label'       => 'Sangat Negatif',
            'description' => 'Sinyal bearish dominan. Smart money sedang distribusi dan tren harga melemah — hindari entry.',
        ];
    }

    private function recommendDuration(string $trend, string $strength, ?float $mos): string
    {
        if ($mos !== null && $mos > 30 && $strength !== 'Lemah') {
            return 'Investasi (3-12 bulan)';
        }

        if ($trend === 'Bullish' && in_array($strength, ['Kuat', 'Sangat Kuat (Overbought)'])) {
            return 'Swing Trade (1-5 hari)';
        }

        return 'Position Trade (1-4 minggu)';
    }

    private function generateNotes(array $tech, array $bandar, ?array $val, float $price): array
    {
        $notes = [];

        $rsi = $tech['rsi']['value'] ?? null;
        if ($rsi !== null && $rsi < 30) {
            $notes[] = '📊 RSI oversold (' . round($rsi, 1) . ') — potensi rebound.';
        }
        if ($rsi !== null && $rsi > 70) {
            $notes[] = '⚠️ RSI overbought (' . round($rsi, 1) . ') — waspadai koreksi.';
        }

        $phase = $bandar['phase'] ?? '';
        if ($phase === 'Akumulasi') {
            $notes[] = '🟢 Smart money sedang akumulasi — sinyal positif untuk entry.';
        }
        if ($phase === 'Distribusi') {
            $notes[] = '🔴 Smart money sedang distribusi — waspadai penurunan harga.';
        }

        $foreignStatus = $bandar['net_foreign']['status'] ?? '';
        if ($foreignStatus === 'Net Buy') {
            $notes[] = '🌐 Asing net buy — sentimen investor asing positif.';
        }
        if ($foreignStatus === 'Net Sell') {
            $notes[] = '🌐 Asing net sell — investor asing sedang keluar.';
        }

        if ($val !== null && ($val['can_value'] ?? false)) {
            $mos = $val['margin_of_safety'] ?? 0;
            if ($mos > 30) {
                $notes[] = '💎 Saham undervalued (MoS ' . round($mos, 1) . '%) — potensi investasi jangka panjang.';
            }
            if ($mos < -20) {
                $notes[] = '⚠️ Saham overvalued (MoS ' . round($mos, 1) . '%) — harga premium.';
            }
        }

        $macd = $tech['macd']['status'] ?? '';
        if (str_contains($macd, 'Bullish')) {
            $notes[] = '📈 MACD bullish — momentum kenaikan terdeteksi.';
        }

        if (empty($notes)) {
            $notes[] = '📋 Perhatikan konfirmasi volume sebelum entry.';
        }

        return $notes;
    }
}
