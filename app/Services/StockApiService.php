<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * StockApiService
 *
 * Responsible for fetching raw financial data from third-party APIs.
 * Integrates with external data sources and handles API failures gracefully.
 */
class StockApiService
{
    /**
     * Base URL for financial API (example: Alpha Vantage, Yahoo Finance, etc.)
     */
    private const API_BASE_URL = 'https://query1.finance.yahoo.com/v8/finance/chart/';

    /**
     * API timeout in seconds
     */
    private const API_TIMEOUT = 10;

    /**
     * Fetch fundamental financial data for a given stock ticker.
     *
     * @param string $ticker The stock ticker symbol (e.g., "AAPL", "GOOGL")
     * @return array|null Array containing fundamental data or null on failure
     *
     * Example return structure:
     * [
     *     'ticker' => 'AAPL',
     *     'eps' => 6.05,
     *     'bvps' => 3.28,
     *     'roe' => 84.5,
     *     'der' => 0.62,
     *     'npm' => 25.5,
     *     'current_price' => 150.25
     * ]
     */
    public function fetchFundamentalData(string $ticker): ?array
    {
        try {
            // Sanitize ticker input
            $ticker = strtoupper(trim($ticker));

            if (empty($ticker)) {
                Log::warning('StockApiService: Empty ticker provided');
                return null;
            }

            /**
             * Make API request using Laravel's HTTP client
             * Replace endpoint with actual service (Yahoo Finance, Alpha Vantage, etc.)
             */
            $response = Http::timeout(self::API_TIMEOUT)
                ->get(self::API_BASE_URL . $ticker, [
                    'apikey' => config('services.stock_api.key'),
                ])
                ->throw(); // Throw exception on HTTP error

            $data = $response->json();

            // Validate that required fields are present
            if (!$this->validateFundamentalData($data)) {
                Log::warning("StockApiService: Invalid data structure for ticker {$ticker}");
                return null;
            }

            return [
                'ticker' => $ticker,
                'eps' => (float) $data['earnings_per_share'] ?? 0.0,
                'bvps' => (float) $data['book_value_per_share'] ?? 0.0,
                'roe' => (float) $data['return_on_equity'] ?? 0.0,
                'der' => (float) $data['debt_to_equity_ratio'] ?? 0.0,
                'npm' => (float) $data['net_profit_margin'] ?? 0.0,
                'current_price' => (float) $data['current_price'] ?? 0.0,
            ];
        } catch (\Illuminate\Http\Client\RequestException $e) {
            Log::error("StockApiService: API request failed for ticker {$ticker}", [
                'status' => $e->response->status() ?? 'unknown',
                'message' => $e->getMessage(),
            ]);
            return null;
        } catch (\Exception $e) {
            Log::error("StockApiService: Unexpected error fetching data for ticker {$ticker}", [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    /**
     * Validate the structure of fundamental data from API response.
     *
     * @param mixed $data Data to validate
     * @return bool True if data structure is valid
     */
    private function validateFundamentalData(mixed $data): bool
    {
        $requiredFields = [
            'earnings_per_share',
            'book_value_per_share',
            'return_on_equity',
            'debt_to_equity_ratio',
            'net_profit_margin',
            'current_price',
        ];

        if (!is_array($data)) {
            return false;
        }

        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                return false;
            }
        }

        return true;
    }
}
