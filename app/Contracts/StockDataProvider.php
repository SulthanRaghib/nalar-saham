<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * StockDataProvider Contract
 *
 * Interface for all stock data provider implementations.
 * Each provider fetches fundamental data from a different source.
 */
interface StockDataProvider
{
    /**
     * Fetch fundamental stock data from this provider.
     *
     * @param string $ticker Stock ticker (e.g., "BBCA", "TLKM")
     * @return array|null Standardized data or null on failure
     *
     * Expected return format:
     * [
     *     'ticker'        => 'BBCA',
     *     'company_name'  => 'PT Bank Central Asia Tbk',
     *     'eps'           => 350.0,
     *     'bvps'          => 2100.0,
     *     'roe'           => 20.5,       // percentage
     *     'der'           => 0.8,
     *     'npm'           => 35.2,       // percentage
     *     'current_price' => 9500.0,
     *     'currency'      => 'IDR',
     *     'source'        => 'idx',
     * ]
     */
    public function fetchFundamentalData(string $ticker): ?array;

    /**
     * Fetch trading data for today (price, volume, value, foreign flow).
     *
     * @param string $ticker Stock ticker
     * @return array|null Trading data or null if not supported/failed
     *
     * Expected return format:
     * [
     *     'ticker'          => 'BBCA',
     *     'open'            => 9400.0,
     *     'high'            => 9500.0,
     *     'low'             => 9350.0,
     *     'close'           => 9450.0,
     *     'previous'        => 9250.0,
     *     'change'          => 200.0,
     *     'change_percent'  => 2.16,
     *     'volume'          => 12500000,       // lot
     *     'value'           => 118100000000,    // Rp
     *     'frequency'       => 45000,
     *     'foreign_buy'     => 65000000000,     // Rp
     *     'foreign_sell'    => 19800000000,     // Rp
     *     'net_foreign'     => 45200000000,     // Rp
     *     'listed_shares'   => 24408459520,
     *     'market_cap'      => 230659042000000,
     *     'source'          => 'idx',
     * ]
     */
    public function fetchTradingData(string $ticker): ?array;

    /**
     * Fetch historical OHLCV price data.
     *
     * @param string $ticker Stock ticker
     * @param int    $days   Number of days of history (default 90)
     * @return array|null Array of candles or null if not supported/failed
     *
     * Expected return format: array of candles, each:
     * [
     *     'date'   => '2025-01-15',
     *     'open'   => 9400.0,
     *     'high'   => 9500.0,
     *     'low'    => 9350.0,
     *     'close'  => 9450.0,
     *     'volume' => 12500000,
     * ]
     */
    public function fetchHistoricalPrices(string $ticker, int $days = 90): ?array;

    /**
     * Check if this provider supports the given ticker.
     *
     * @param string $ticker Stock ticker
     * @return bool True if this provider can handle the ticker
     */
    public function supports(string $ticker): bool;

    /**
     * Get the human-readable provider name.
     *
     * @return string Provider name (e.g., "IDX Indonesia", "Yahoo Finance")
     */
    public function getProviderName(): string;
}
