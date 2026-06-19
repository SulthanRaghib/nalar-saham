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
