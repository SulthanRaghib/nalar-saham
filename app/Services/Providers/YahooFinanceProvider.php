<?php

declare(strict_types=1);

namespace App\Services\Providers;

use App\Contracts\StockDataProvider;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Yahoo Finance Provider — Backup data source
 *
 * Uses Yahoo Finance's unofficial API as a fallback when IDX API is unavailable.
 * Supports Indonesian stocks (.JK) and US/global stocks.
 * Also provides historical OHLCV data for technical analysis.
 *
 * @package App\Services\Providers
 */
class YahooFinanceProvider implements StockDataProvider
{
    private const QUOTE_SUMMARY_URL = 'https://query2.finance.yahoo.com/v10/finance/quoteSummary';
    private const CHART_URL = 'https://query1.finance.yahoo.com/v8/finance/chart';
    private const TIMEOUT = 15;
    private const USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36';

    public function fetchFundamentalData(string $ticker): ?array
    {
        $yahooTicker = $this->toYahooTicker($ticker);

        try {
            // Try quoteSummary first (has fundamental data)
            $data = $this->fetchQuoteSummary($yahooTicker);

            if ($data !== null) {
                return $data;
            }

            // Fallback: try chart API (at least get price)
            Log::info("YahooFinanceProvider: quoteSummary failed for {$yahooTicker}, trying chart API");
            return $this->fetchFromChart($yahooTicker, $ticker);
        } catch (\Exception $e) {
            Log::error("YahooFinanceProvider: Exception for {$ticker}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Fetch trading data (current day OHLCV + change).
     */
    public function fetchTradingData(string $ticker): ?array
    {
        $yahooTicker = $this->toYahooTicker($ticker);

        try {
            $response = Http::timeout(self::TIMEOUT)
                ->withHeaders(['User-Agent' => self::USER_AGENT])
                ->get(self::CHART_URL . "/{$yahooTicker}", [
                    'range'    => '1d',
                    'interval' => '1m',
                ]);

            if (!$response->successful()) {
                return null;
            }

            $json = $response->json();
            $result = $json['chart']['result'][0] ?? null;

            if ($result === null) {
                return null;
            }

            $meta = $result['meta'] ?? [];
            $close = (float) ($meta['regularMarketPrice'] ?? 0);
            $previous = (float) ($meta['chartPreviousClose'] ?? $meta['previousClose'] ?? 0);
            $volume = (int) ($meta['regularMarketVolume'] ?? 0);

            // Get intraday OHLC
            $quotes = $result['indicators']['quote'][0] ?? [];
            $highs = array_filter($quotes['high'] ?? [], fn($v) => $v !== null);
            $lows = array_filter($quotes['low'] ?? [], fn($v) => $v !== null);
            $opens = array_filter($quotes['open'] ?? [], fn($v) => $v !== null);

            $high = !empty($highs) ? max($highs) : $close;
            $low = !empty($lows) ? min($lows) : $close;
            $open = !empty($opens) ? reset($opens) : $close;

            $change = $close - $previous;
            $changePercent = $previous > 0 ? round(($change / $previous) * 100, 2) : 0;

            $originalTicker = str_replace('.JK', '', strtoupper($yahooTicker));

            return [
                'ticker'          => $originalTicker,
                'open'            => round($open, 2),
                'high'            => round($high, 2),
                'low'             => round($low, 2),
                'close'           => round($close, 2),
                'previous'        => round($previous, 2),
                'change'          => round($change, 2),
                'change_percent'  => $changePercent,
                'volume'          => $volume,
                'value'           => null, // Yahoo doesn't provide value directly
                'frequency'       => null,
                'foreign_buy'     => null, // Not available from Yahoo
                'foreign_sell'    => null,
                'net_foreign'     => null,
                'net_ritel'       => null,
                'listed_shares'   => null,
                'market_cap'      => null,
                'source'          => 'yahoo_finance',
            ];
        } catch (\Exception $e) {
            Log::error("YahooFinanceProvider: Trading data exception for {$ticker}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Fetch historical OHLCV data for technical analysis.
     */
    public function fetchHistoricalPrices(string $ticker, int $days = 90): ?array
    {
        $yahooTicker = $this->toYahooTicker($ticker);

        try {
            // Map days to Yahoo range parameter
            $range = match (true) {
                $days <= 5   => '5d',
                $days <= 30  => '1mo',
                $days <= 90  => '3mo',
                $days <= 180 => '6mo',
                default      => '1y',
            };

            $response = Http::timeout(self::TIMEOUT)
                ->withHeaders(['User-Agent' => self::USER_AGENT])
                ->get(self::CHART_URL . "/{$yahooTicker}", [
                    'range'    => $range,
                    'interval' => '1d',
                ]);

            if (!$response->successful()) {
                Log::debug("YahooFinanceProvider: Historical data HTTP {$response->status()} for {$yahooTicker}");
                return null;
            }

            $json = $response->json();
            $result = $json['chart']['result'][0] ?? null;

            if ($result === null) {
                return null;
            }

            $timestamps = $result['timestamp'] ?? [];
            $quotes = $result['indicators']['quote'][0] ?? [];

            if (empty($timestamps) || empty($quotes)) {
                return null;
            }

            $candles = [];

            foreach ($timestamps as $i => $ts) {
                $open = $quotes['open'][$i] ?? null;
                $high = $quotes['high'][$i] ?? null;
                $low = $quotes['low'][$i] ?? null;
                $close = $quotes['close'][$i] ?? null;
                $volume = $quotes['volume'][$i] ?? null;

                // Skip null candles
                if ($open === null || $close === null) {
                    continue;
                }

                $candles[] = [
                    'date'   => date('Y-m-d', $ts),
                    'open'   => round((float) $open, 2),
                    'high'   => round((float) ($high ?? $open), 2),
                    'low'    => round((float) ($low ?? $open), 2),
                    'close'  => round((float) $close, 2),
                    'volume' => (int) ($volume ?? 0),
                ];
            }

            if (empty($candles)) {
                return null;
            }

            Log::info("YahooFinanceProvider: Fetched " . count($candles) . " candles for {$yahooTicker}");
            return $candles;
        } catch (\Exception $e) {
            Log::error("YahooFinanceProvider: Historical data exception for {$ticker}: " . $e->getMessage());
            return null;
        }
    }

    public function supports(string $ticker): bool
    {
        // Yahoo Finance supports virtually all tickers
        return !empty(trim($ticker));
    }

    public function getProviderName(): string
    {
        return 'Yahoo Finance';
    }

    /**
     * Fetch from Yahoo Finance Quote Summary API.
     */
    private function fetchQuoteSummary(string $yahooTicker): ?array
    {
        try {
            $response = Http::timeout(self::TIMEOUT)
                ->withHeaders(['User-Agent' => self::USER_AGENT])
                ->get(self::QUOTE_SUMMARY_URL . "/{$yahooTicker}", [
                    'modules' => 'defaultKeyStatistics,financialData,summaryDetail,price',
                ]);

            if (!$response->successful()) {
                Log::debug("YahooFinanceProvider: quoteSummary HTTP {$response->status()} for {$yahooTicker}");
                return null;
            }

            $json = $response->json();

            if (isset($json['quoteSummary']['error'])) {
                return null;
            }

            $result = $json['quoteSummary']['result'][0] ?? null;
            if ($result === null) {
                return null;
            }

            return $this->transformQuoteSummary($yahooTicker, $result);
        } catch (\Exception $e) {
            Log::debug("YahooFinanceProvider: quoteSummary exception for {$yahooTicker}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Fetch price data from Yahoo Finance Chart API.
     */
    private function fetchFromChart(string $yahooTicker, string $originalTicker): ?array
    {
        try {
            $response = Http::timeout(self::TIMEOUT)
                ->withHeaders(['User-Agent' => self::USER_AGENT])
                ->get(self::CHART_URL . "/{$yahooTicker}", [
                    'range'    => '1d',
                    'interval' => '1d',
                ]);

            if (!$response->successful()) {
                return null;
            }

            $json = $response->json();
            $meta = $json['chart']['result'][0]['meta'] ?? null;

            if ($meta === null) {
                return null;
            }

            $price = $meta['regularMarketPrice'] ?? $meta['previousClose'] ?? null;

            if ($price === null) {
                return null;
            }

            $currency = $meta['currency'] ?? $this->detectCurrency($yahooTicker);

            return [
                'ticker'        => strtoupper($originalTicker),
                'company_name'  => $meta['shortName'] ?? $meta['longName'] ?? strtoupper($originalTicker),
                'eps'           => null,
                'bvps'          => null,
                'roe'           => null,
                'der'           => null,
                'npm'           => null,
                'per'           => null,
                'pbv'           => null,
                'current_price' => (float) $price,
                'currency'      => $currency,
                'source'        => 'yahoo_finance',
            ];
        } catch (\Exception $e) {
            Log::debug("YahooFinanceProvider: chart exception for {$yahooTicker}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Transform Yahoo Finance quoteSummary response.
     */
    private function transformQuoteSummary(string $yahooTicker, array $data): ?array
    {
        $keyStats  = $data['defaultKeyStatistics'] ?? [];
        $financial = $data['financialData'] ?? [];
        $summary   = $data['summaryDetail'] ?? [];
        $priceData = $data['price'] ?? [];

        $eps  = $this->extractValue($keyStats, 'trailingEps');
        $bvps = $this->extractValue($keyStats, 'bookValue');
        $roe  = $this->extractValue($financial, 'returnOnEquity');
        $der  = $this->extractValue($financial, 'debtToEquity');
        $npm  = $this->extractValue($financial, 'profitMargins');
        $per  = $this->extractValue($summary, 'trailingPE');
        $pbv  = $this->extractValue($keyStats, 'priceToBook');

        $currentPrice = $this->extractValue($financial, 'currentPrice')
            ?? $this->extractValue($summary, 'regularMarketPrice')
            ?? $this->extractValue($summary, 'previousClose');

        $companyName = $priceData['shortName'] ?? $priceData['longName'] ?? null;

        // Convert decimals to percentages
        if ($roe !== null && abs($roe) < 1) {
            $roe *= 100;
        }
        if ($npm !== null && abs($npm) < 1) {
            $npm *= 100;
        }

        // Minimum validation
        if ($eps === null && $currentPrice === null) {
            return null;
        }

        $currency = $this->detectCurrency($yahooTicker);
        $originalTicker = str_replace('.JK', '', strtoupper($yahooTicker));

        return [
            'ticker'        => $originalTicker,
            'company_name'  => $companyName ?? $originalTicker,
            'eps'           => $eps,
            'bvps'          => $bvps,
            'roe'           => $roe !== null ? round($roe, 2) : null,
            'der'           => $der !== null ? round($der, 4) : null,
            'npm'           => $npm !== null ? round($npm, 2) : null,
            'per'           => $per !== null ? round($per, 2) : null,
            'pbv'           => $pbv !== null ? round($pbv, 2) : null,
            'current_price' => $currentPrice,
            'currency'      => $currency,
            'source'        => 'yahoo_finance',
        ];
    }

    /**
     * Extract numeric value from nested Yahoo Finance data.
     */
    private function extractValue(array $data, string $key): ?float
    {
        if (!isset($data[$key])) {
            return null;
        }

        $value = $data[$key];

        if (is_array($value) && isset($value['raw'])) {
            $value = $value['raw'];
        }

        return is_numeric($value) ? (float) $value : null;
    }

    /**
     * Convert ticker to Yahoo Finance format.
     */
    private function toYahooTicker(string $ticker): string
    {
        $ticker = trim(strtoupper($ticker));

        // If it doesn't contain a dot and looks like an Indonesian ticker
        if (!str_contains($ticker, '.') && preg_match('/^[A-Z]{4}$/', $ticker)) {
            return $ticker . '.JK';
        }

        return $ticker;
    }

    /**
     * Detect currency from ticker.
     */
    private function detectCurrency(string $ticker): string
    {
        return str_ends_with(strtoupper($ticker), '.JK') ? 'IDR' : 'USD';
    }
}
