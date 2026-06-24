<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\StockDataProvider;
use App\Services\Providers\IdxProvider;
use App\Services\Providers\YahooFinanceProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * StockApiService — Orchestrator
 *
 * Manages a chain of stock data providers with automatic fallback.
 * Provider priority: IDX Indonesia → Yahoo Finance → null (manual input)
 *
 * Features:
 * - Multi-provider fallback chain
 * - Differentiated caching: fundamental (5min), trading (2min), historical (1hr)
 * - Transparent provider switching with logging
 * - Clean interface-based architecture
 *
 * @package App\Services
 */
class StockApiService
{
    private const CACHE_TTL_FUNDAMENTAL = 300;  // 5 minutes
    private const CACHE_TTL_TRADING = 120;       // 2 minutes
    private const CACHE_TTL_HISTORICAL = 3600;   // 1 hour

    /** @var StockDataProvider[] */
    private array $providers;

    public function __construct()
    {
        $this->providers = [
            new IdxProvider(),
            new YahooFinanceProvider(),
        ];
    }

    /**
     * Fetch fundamental stock data.
     *
     * Iterates through all registered providers until one succeeds.
     * Results are cached for 5 minutes to reduce API load.
     *
     * @param string $ticker Stock ticker (e.g., "BBCA", "TLKM", "BBRI")
     * @return array|null Standardized fundamental data or null if all providers fail
     */
    public function fetchFundamentalData(string $ticker): ?array
    {
        $ticker = $this->sanitizeTicker($ticker);

        if (empty($ticker)) {
            Log::warning('StockApiService: Empty ticker provided');
            return null;
        }

        // Check cache
        $cacheKey = "stock_fundamental:{$ticker}";
        $cached = Cache::get($cacheKey);

        if ($cached !== null) {
            Log::info("StockApiService: Cache hit for {$ticker} (source: {$cached['source']})");
            return $cached;
        }

        // Try each provider in order
        foreach ($this->providers as $provider) {
            if (!$provider->supports($ticker)) {
                Log::debug("StockApiService: {$provider->getProviderName()} does not support {$ticker}, skipping");
                continue;
            }

            Log::info("StockApiService: Trying {$provider->getProviderName()} for {$ticker}");

            try {
                $data = $provider->fetchFundamentalData($ticker);

                if ($data !== null) {
                    Log::info("StockApiService: ✓ {$provider->getProviderName()} returned data for {$ticker}");
                    Cache::put($cacheKey, $data, self::CACHE_TTL_FUNDAMENTAL);
                    return $data;
                }

                Log::info("StockApiService: ✗ {$provider->getProviderName()} returned null for {$ticker}");
            } catch (\Exception $e) {
                Log::error("StockApiService: {$provider->getProviderName()} threw exception for {$ticker}: " . $e->getMessage());
            }
        }

        Log::warning("StockApiService: All providers failed for {$ticker}. Manual input required.");
        return null;
    }

    /**
     * Fetch trading data for today (price, volume, value, foreign flow).
     *
     * @param string $ticker Stock ticker
     * @return array|null Trading data or null if all providers fail
     */
    public function fetchTradingData(string $ticker): ?array
    {
        $ticker = $this->sanitizeTicker($ticker);

        if (empty($ticker)) {
            return null;
        }

        $cacheKey = "stock_trading:{$ticker}";
        $cached = Cache::get($cacheKey);

        if ($cached !== null) {
            return $cached;
        }

        foreach ($this->providers as $provider) {
            if (!$provider->supports($ticker)) {
                continue;
            }

            try {
                $data = $provider->fetchTradingData($ticker);

                if ($data !== null) {
                    Log::info("StockApiService: ✓ Trading data from {$provider->getProviderName()} for {$ticker}");
                    Cache::put($cacheKey, $data, self::CACHE_TTL_TRADING);
                    return $data;
                }
            } catch (\Exception $e) {
                Log::error("StockApiService: Trading data error from {$provider->getProviderName()} for {$ticker}: " . $e->getMessage());
            }
        }

        return null;
    }

    /**
     * Fetch historical OHLCV data for technical analysis.
     *
     * @param string $ticker Stock ticker
     * @param int    $days   Number of days of history
     * @return array|null Array of candles or null if all providers fail
     */
    public function fetchHistoricalPrices(string $ticker, int $days = 90): ?array
    {
        $ticker = $this->sanitizeTicker($ticker);

        if (empty($ticker)) {
            return null;
        }

        $cacheKey = "stock_historical:{$ticker}:{$days}";
        $cached = Cache::get($cacheKey);

        if ($cached !== null) {
            Log::info("StockApiService: Cache hit for historical {$ticker} ({$days} days)");
            return $cached;
        }

        foreach ($this->providers as $provider) {
            if (!$provider->supports($ticker)) {
                continue;
            }

            try {
                $data = $provider->fetchHistoricalPrices($ticker, $days);

                if ($data !== null && count($data) > 0) {
                    Log::info("StockApiService: ✓ Historical data from {$provider->getProviderName()} for {$ticker} (" . count($data) . " candles)");
                    Cache::put($cacheKey, $data, self::CACHE_TTL_HISTORICAL);
                    return $data;
                }
            } catch (\Exception $e) {
                Log::error("StockApiService: Historical data error from {$provider->getProviderName()} for {$ticker}: " . $e->getMessage());
            }
        }

        return null;
    }

    /**
     * Clear cached data for a ticker.
     */
    public function clearCache(string $ticker): void
    {
        $ticker = $this->sanitizeTicker($ticker);
        Cache::forget("stock_fundamental:{$ticker}");
        Cache::forget("stock_trading:{$ticker}");
        Cache::forget("stock_historical:{$ticker}:90");
    }

    /**
     * Normalize and sanitize ticker input.
     *
     * - Strips .JK suffix (we add it internally when needed)
     * - Converts to uppercase
     * - Trims whitespace
     */
    private function sanitizeTicker(string $ticker): string
    {
        $ticker = trim(strtoupper($ticker));

        // Remove .JK suffix — used only internally by Yahoo Finance provider
        if (str_ends_with($ticker, '.JK')) {
            $ticker = substr($ticker, 0, -3);
        }

        return $ticker;
    }
}
