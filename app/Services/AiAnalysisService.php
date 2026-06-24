<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * AiAnalysisService
 *
 * Integrasi opsional dengan Google Gemini API (free tier) untuk narasi analisis saham
 * dalam bahasa Indonesia. Jika API key tidak tersedia, service akan gracefully degrade.
 */
class AiAnalysisService
{
    private const CACHE_TTL = 1800; // 30 menit
    private const TIMEOUT = 30;

    /**
     * Cek apakah AI analysis tersedia (API key diset).
     */
    public function isAvailable(): bool
    {
        return !empty(config('services.gemini.api_key', env('GEMINI_API_KEY', '')));
    }

    /**
     * Generate analisis AI untuk saham tertentu.
     *
     * @param string $ticker Kode saham
     * @param array  $context Data analisis lengkap
     * @return array|null Hasil analisis AI atau null jika gagal
     */
    public function generateAnalysis(string $ticker, array $context): ?array
    {
        if (!$this->isAvailable()) {
            Log::info('AiAnalysisService: API key tidak tersedia, skip analisis AI');
            return null;
        }

        // Check cache
        $cacheKey = "ai_analysis:{$ticker}";
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            Log::info("AiAnalysisService: Cache hit untuk {$ticker}");
            return $cached;
        }

        try {
            $prompt = $this->buildPrompt($ticker, $context);
            $response = $this->callGeminiApi($prompt);

            if ($response === null) {
                return null;
            }

            $result = [
                'summary'      => $response,
                'strengths'    => $this->extractSection($response, 'KEKUATAN'),
                'weaknesses'   => $this->extractSection($response, 'KELEMAHAN'),
                'outlook'      => $this->extractSingleLine($response, 'OUTLOOK'),
                'generated_at' => now()->toDateTimeString(),
                'model'        => env('GEMINI_MODEL', 'gemini-2.0-flash'),
            ];

            Cache::put($cacheKey, $result, self::CACHE_TTL);
            Log::info("AiAnalysisService: Analisis AI berhasil untuk {$ticker}");

            return $result;
        } catch (\Exception $e) {
            Log::error("AiAnalysisService: Error — " . $e->getMessage());
            return null;
        }
    }

    /**
     * Build structured prompt untuk Gemini.
     */
    private function buildPrompt(string $ticker, array $context): string
    {
        $companyName = $context['company_name'] ?? $ticker;
        $price = number_format($context['current_price'] ?? 0, 0, ',', '.');
        $changePct = $context['change_percent'] ?? 'N/A';
        $volume = isset($context['volume']) ? number_format($context['volume'], 0, ',', '.') : 'N/A';
        $value = isset($context['value']) ? 'Rp ' . number_format($context['value'], 0, ',', '.') : 'N/A';

        // Fundamental
        $fund = $context['fundamental'] ?? [];
        $eps = $fund['eps'] ?? 'N/A';
        $bvps = $fund['bvps'] ?? 'N/A';
        $der = $fund['der'] ?? 'N/A';
        $roe = $fund['roe'] ?? 'N/A';
        $npm = $fund['npm'] ?? 'N/A';

        // Technical
        $tech = $context['technical'] ?? [];
        $rsi = $tech['rsi']['value'] ?? 'N/A';
        $macd = $tech['macd']['status'] ?? 'N/A';
        $trend = $tech['trend']['direction'] ?? 'N/A';
        $signal = $tech['signal']['action'] ?? 'N/A';

        // Bandarmology
        $bandar = $context['bandarmology'] ?? [];
        $accScore = $bandar['accumulation_score'] ?? 'N/A';
        $phase = $bandar['phase'] ?? 'N/A';
        $foreignStatus = $bandar['net_foreign']['status'] ?? 'N/A';

        // Valuation
        $val = $context['valuation'] ?? [];
        $fairValue = isset($val['fair_value']) ? 'Rp ' . number_format($val['fair_value'], 0, ',', '.') : 'N/A';
        $mos = $val['margin_of_safety'] ?? 'N/A';

        // Trading Plan
        $plan = $context['trading_plan'] ?? [];
        $entryLow = $plan['entry_area']['low'] ?? 'N/A';
        $entryHigh = $plan['entry_area']['high'] ?? 'N/A';
        $sl = $plan['stop_loss']['price'] ?? 'N/A';
        $tp1 = $plan['take_profit']['tp1']['price'] ?? 'N/A';
        $prob = $plan['probabilitas'] ?? 'N/A';
        $outlook = $plan['outlook'] ?? 'N/A';

        return <<<PROMPT
Kamu adalah analis saham profesional Indonesia. Analisis saham berikut dan berikan ringkasan dalam Bahasa Indonesia yang mudah dipahami oleh investor ritel.

DATA SAHAM: {$ticker} ({$companyName})
- Harga: Rp {$price} ({$changePct}%)
- Volume: {$volume} lot | Value: {$value}

FUNDAMENTAL:
- EPS: {$eps} | BVPS: {$bvps} | DER: {$der} | ROE: {$roe}% | NPM: {$npm}%

TEKNIKAL:
- RSI: {$rsi} | MACD: {$macd} | Tren: {$trend} | Sinyal: {$signal}

BANDARMOLOGY:
- Skor Akumulasi: {$accScore}/100 | Fase: {$phase} | Asing: {$foreignStatus}

VALUASI:
- Nilai Wajar: {$fairValue} | Margin of Safety: {$mos}%

TRADING PLAN:
- Entry: {$entryLow} - {$entryHigh} | SL: {$sl} | TP1: {$tp1}
- Probabilitas Kenaikan: {$prob}% | Outlook: {$outlook}

FORMAT JAWABAN (gunakan format ini persis):

RINGKASAN:
[Tulis ringkasan analisis 3-5 kalimat yang mencakup kondisi fundamental, teknikal, dan bandarmology]

KEKUATAN:
- [Poin 1]
- [Poin 2]
- [Poin 3]

KELEMAHAN:
- [Poin 1]
- [Poin 2]

OUTLOOK:
[Satu kalimat outlook ke depan]

REKOMENDASI:
[Satu paragraf rekomendasi aksi untuk investor]
PROMPT;
    }

    /**
     * Call Gemini API.
     */
    private function callGeminiApi(string $prompt): ?string
    {
        $apiKey = config('services.gemini.api_key', env('GEMINI_API_KEY', ''));
        $model = env('GEMINI_MODEL', 'gemini-2.0-flash');

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";

        $response = Http::timeout(self::TIMEOUT)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post("{$url}?key={$apiKey}", [
                'contents' => [
                    ['parts' => [['text' => $prompt]]],
                ],
                'generationConfig' => [
                    'temperature'     => 0.7,
                    'maxOutputTokens' => 1024,
                ],
            ]);

        if (!$response->successful()) {
            Log::error("AiAnalysisService: Gemini API error HTTP {$response->status()}");
            return null;
        }

        $json = $response->json();
        $text = $json['candidates'][0]['content']['parts'][0]['text'] ?? null;

        return $text;
    }

    /**
     * Extract bullet-point section from AI response.
     */
    private function extractSection(string $text, string $sectionName): array
    {
        $pattern = "/{$sectionName}:\s*\n((?:- .+\n?)+)/i";
        if (preg_match($pattern, $text, $matches)) {
            $lines = explode("\n", trim($matches[1]));
            return array_values(array_filter(
                array_map(fn($l) => trim(ltrim(trim($l), '-')), $lines),
                fn($l) => !empty($l)
            ));
        }

        return [];
    }

    /**
     * Extract a single line after a label.
     */
    private function extractSingleLine(string $text, string $label): string
    {
        $pattern = "/{$label}:\s*\n?(.+)/i";
        if (preg_match($pattern, $text, $matches)) {
            return trim($matches[1]);
        }

        return '';
    }
}
