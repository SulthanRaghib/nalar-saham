<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * StockApiService
 *
 * Professional service layer for fetching stock fundamental data.
 *
 * Strategy:
 * - Uses Alpha Vantage API (reliable, global coverage)
 * - Implements 5-minute caching to stay within free tier limits
 * - Falls back to manual input when API fails
 *
 * Features:
 * - EPS, BVPS, ROE, DER, NPM extraction
 * - Automatic data caching
 * - Comprehensive error logging
 *
 * @package App\Services
 * @version 3.1.0
 * @since 2026-04-02
 */
class StockApiService
{
    private const CACHE_TTL = 300; // 5 minutes
    private const API_TIMEOUT = 15;

    private readonly string $alphaVantageKey;
    private readonly string $alphaVantageUrl;
    private readonly int $timeout;

    public function __construct()
    {
        $this->alphaVantageKey = config('services.alpha_vantage.key', 'demo');
        $this->alphaVantageUrl = config('services.alpha_vantage.base_url', 'https://www.alphavantage.co');
        $this->timeout = config('services.alpha_vantage.timeout', self::API_TIMEOUT);
    }

    /**
     * Fetch fundamental stock data
     *
     * @param string $ticker Stock symbol (e.g., "AAPL", "MSFT", "BBCA")
     * @return array|null Standardized fundamental data or null on failure
     *
     * Returns array with keys:
     * - ticker: normalized ticker
     * - eps: earnings per share
     * - bvps: book value per share
     * - roe: return on equity (%)
     * - der: debt-to-equity ratio
     * - npm: net profit margin (%)
     * - current_price: current stock price (usually null - provide manually)
     * - source: which API provided the data
     */
    public function fetchFundamentalData(string $ticker): ?array
    {
        try {
            $ticker = $this->sanitizeTicker($ticker);

            if (empty($ticker)) {
                Log::warning('StockApiService: Empty ticker');
                return null;
            }

            // Check cache first
            $cacheKey = $this->getCacheKey($ticker);
            $cached = Cache::get($cacheKey);
            if ($cached !== null) {
                Log::info("StockApiService: Cache hit for {$ticker}");
                return $cached;
            }

            // Fetch from API
            Log::info("StockApiService: Fetching {$ticker} from Alpha Vantage");
            $data = $this->fetchFromAlphaVantage($ticker);

            if ($data === null) {
                Log::warning("StockApiService: Failed to fetch {$ticker}. " .
                    "Register real key at https://www.alphavantage.co/ or use manual input.");
                return null;
            }

            // Cache the result
            Cache::put($cacheKey, $data, self::CACHE_TTL);
            Log::info("StockApiService: Successfully cached {$ticker}");

            return $data;
        } catch (\Exception $e) {
            Log::error("StockApiService: Error for {$ticker}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Fetch from Alpha Vantage OVERVIEW endpoint
     *
     * https://www.alphavantage.co/documentation/#overview
     * Provides: EPS, BookValue, ROE, Dividend, DividendShare, DER, ProfitMargin
     */
    private function fetchFromAlphaVantage(string $ticker): ?array
    {
        try {
            $response = Http::timeout($this->timeout)->get("{$this->alphaVantageUrl}/query", [
                'function' => 'OVERVIEW',
                'symbol' => $ticker,
                'apikey' => $this->alphaVantageKey,
            ]);

            if (!$response->successful()) {
                Log::warning("StockApiService: Alpha Vantage HTTP {$response->status()} for {$ticker}");
                return null;
            }

            $data = $response->json();

            // Check for API errors
            if (isset($data['Error Message']) || isset($data['Note'])) {
                Log::warning("StockApiService: Alpha Vantage error for {$ticker}: " .
                    ($data['Error Message'] ?? $data['Note']));
                return null;
            }

            // Must have EPS for Graham Number calculation
            if (!isset($data['EPS']) || empty($data['EPS'])) {
                Log::warning("StockApiService: Alpha Vantage missing EPS for {$ticker}");
                return null;
            }

            return $this->transformAlphaVantageResponse($ticker, $data);
        } catch (\Exception $e) {
            Log::warning("StockApiService: Alpha Vantage exception for {$ticker}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Transform Alpha Vantage response to standard format
     */
    private function transformAlphaVantageResponse(string $ticker, array $data): ?array
    {
        try {
            // Clean ticker
            $ticker = str_replace('.JK', '', strtoupper(trim($ticker)));

            // Extract metrics
            $eps = $this->toFloat($data['EPS'] ?? 0);
            $bvps = $this->toFloat($data['BookValue'] ?? 0);
            $roe = $this->toFloat($data['ReturnOnEquityTTM'] ?? 0);
            $der = $this->toFloat($data['DebtToEquity'] ?? 0);
            $npm = $this->toFloat($data['ProfitMargin'] ?? 0);

            // Convert decimals to percentages if needed
            if ($roe > 0 && $roe < 1) {
                $roe *= 100;
            }
            if ($npm > 0 && $npm < 1) {
                $npm *= 100;
            }

            return [
                'ticker' => $ticker,
                'eps' => $eps,
                'bvps' => $bvps,
                'roe' => $roe,
                'der' => $der,
                'npm' => $npm,
                'current_price' => null, // Provide manually in UI
                'source' => 'alpha_vantage',
            ];
        } catch (\Exception $e) {
            Log::error("StockApiService: Transform error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Convert value to float safely
     */
    private function toFloat($value): float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }
        return 0.0;
    }

    /**
     * Normalize ticker input
     */
    private function sanitizeTicker(string $ticker): string
    {
        return trim(strtoupper($ticker));
    }

    /**
     * Generate cache key for ticker
     */
    private function getCacheKey(string $ticker): string
    {
        return "stock_data:{$ticker}";
    }

    /**
     * Clear cache for ticker (force fresh fetch)
     */
    public function clearCache(string $ticker): void
    {
        Cache::forget($this->getCacheKey($ticker));
    }
}
