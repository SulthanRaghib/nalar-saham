<?php

declare(strict_types=1);

namespace App\Services\Providers;

use App\Contracts\StockDataProvider;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * IDX Provider — Indonesia Stock Exchange Internal API
 *
 * Fetches fundamental data directly from idx.co.id's internal endpoints.
 * These are the same endpoints used by the IDX website frontend.
 *
 * Endpoints used:
 * 1. DigitalStatistic/GetApiDataPaginated — EPS, ROE, DER, NPM, PER, PBV
 * 2. TradingSummary/GetStockSummary       — Current price, volume, market data
 * 3. ListedCompany/GetCompanyProfilesDetail — Company name, profile
 *
 * @package App\Services\Providers
 */
class IdxProvider implements StockDataProvider
{
    private const BASE_URL = 'https://www.idx.co.id/primary';
    private const TIMEOUT = 15;
    private const USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36';

    public function fetchFundamentalData(string $ticker): ?array
    {
        $ticker = $this->normalizeTicker($ticker);

        try {
            // Step 1: Fetch financial ratios (EPS, ROE, DER, NPM)
            $ratios = $this->fetchFinancialRatios($ticker);

            if ($ratios === null) {
                Log::warning("IdxProvider: No financial ratios found for {$ticker}");
                return null;
            }

            // Step 2: Fetch current price from stock summary
            $price = $this->fetchCurrentPrice($ticker);

            // Step 3: Fetch company name
            $companyName = $this->fetchCompanyName($ticker);

            // Calculate DER from balance sheet data if available
            $der = $this->calculateDer($ratios);

            return [
                'ticker'        => strtoupper($ticker),
                'company_name'  => $companyName ?? strtoupper($ticker),
                'eps'           => $this->safeFloat($ratios['eps'] ?? null),
                'bvps'          => $this->calculateBvps($ratios),
                'roe'           => $this->safeFloat($ratios['roe'] ?? null),
                'der'           => $der,
                'npm'           => $this->safeFloat($ratios['npm'] ?? null),
                'per'           => $this->safeFloat($ratios['per'] ?? null),
                'pbv'           => $this->safeFloat($ratios['priceBV'] ?? null),
                'current_price' => $price,
                'currency'      => 'IDR',
                'source'        => 'idx',
            ];
        } catch (\Exception $e) {
            Log::error("IdxProvider: Exception for {$ticker}: " . $e->getMessage());
            return null;
        }
    }

    public function supports(string $ticker): bool
    {
        $ticker = $this->normalizeTicker($ticker);
        // IDX supports Indonesian tickers (no dots, or ending with .JK)
        return !empty($ticker) && strlen($ticker) <= 10;
    }

    public function getProviderName(): string
    {
        return 'IDX Indonesia';
    }

    /**
     * Fetch financial ratios from IDX DigitalStatistic API.
     *
     * Tries current quarter first, then falls back to previous quarters.
     */
    private function fetchFinancialRatios(string $ticker): ?array
    {
        $currentYear = (int) date('Y');
        $currentMonth = (int) date('n');

        // Determine the latest available quarter
        // Q1 reports available around April-May, Q2 around July-Aug, etc.
        $quarters = $this->getQuartersToTry($currentYear, $currentMonth);

        foreach ($quarters as [$year, $quarter]) {
            $data = $this->callFinancialRatioApi($ticker, $year, $quarter);
            if ($data !== null) {
                Log::info("IdxProvider: Found ratios for {$ticker} Q{$quarter}/{$year}");
                return $data;
            }
        }

        return null;
    }

    /**
     * Generate list of quarters to try, most recent first.
     */
    private function getQuartersToTry(int $currentYear, int $currentMonth): array
    {
        $quarters = [];

        // Current year quarters (descending)
        $maxQuarter = match (true) {
            $currentMonth >= 10 => 3,   // Q3 data likely available by Oct
            $currentMonth >= 7  => 2,   // Q2 data likely available by Jul
            $currentMonth >= 4  => 1,   // Q1 data likely available by Apr
            default             => 0,
        };

        for ($q = $maxQuarter; $q >= 1; $q--) {
            $quarters[] = [$currentYear, $q];
        }

        // Previous year Q4 and Q3 as fallback
        $quarters[] = [$currentYear - 1, 4];
        $quarters[] = [$currentYear - 1, 3];

        return $quarters;
    }

    /**
     * Call the IDX DigitalStatistic API for financial ratios.
     */
    private function callFinancialRatioApi(string $ticker, int $year, int $quarter): ?array
    {
        try {
            $response = Http::timeout(self::TIMEOUT)
                ->withHeaders($this->getHeaders())
                ->get(self::BASE_URL . '/DigitalStatistic/GetApiDataPaginated', [
                    'periodQuarter' => $quarter,
                    'periodYear'    => $year,
                    'type'          => 'quarterly',
                    'urlName'       => 'LINK_FINANCIAL_DATA_RATIO',
                    'isPrint'       => 'false',
                    'cumulative'    => 'false',
                    'pageSize'      => 10,
                    'pageNumber'    => 1,
                    'search'        => strtoupper($ticker),
                    'orderBy'       => 'no',
                ]);

            if (!$response->successful()) {
                Log::debug("IdxProvider: Financial ratio API returned HTTP {$response->status()} for {$ticker} Q{$quarter}/{$year}");
                return null;
            }

            $data = $response->json();
            $items = $data['data'] ?? $data['Data'] ?? $data['results'] ?? $data['Results'] ?? [];

            if (empty($items)) {
                return null;
            }

            // Find exact ticker match
            foreach ($items as $item) {
                $itemTicker = strtoupper(trim($item['ticker'] ?? $item['Ticker'] ?? $item['kodeEmiten'] ?? ''));
                if ($itemTicker === strtoupper($ticker)) {
                    return $item;
                }
            }

            // If only one result and search was specific, use it
            if (count($items) === 1) {
                return $items[0];
            }

            return null;
        } catch (\Exception $e) {
            Log::debug("IdxProvider: Financial ratio API error for {$ticker} Q{$quarter}/{$year}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Fetch current stock price from IDX Trading Summary.
     */
    private function fetchCurrentPrice(string $ticker): ?float
    {
        try {
            $response = Http::timeout(self::TIMEOUT)
                ->withHeaders($this->getHeaders())
                ->get(self::BASE_URL . '/TradingSummary/GetStockSummary', [
                    'length' => 9999,
                    'start'  => 0,
                ]);

            if (!$response->successful()) {
                return null;
            }

            $data = $response->json();
            $stocks = $data['data'] ?? $data['Data'] ?? [];

            foreach ($stocks as $stock) {
                $code = strtoupper(trim($stock['StockCode'] ?? $stock['stockCode'] ?? ''));
                if ($code === strtoupper($ticker)) {
                    return $this->safeFloat($stock['Close'] ?? $stock['close'] ?? $stock['Previous'] ?? null);
                }
            }

            return null;
        } catch (\Exception $e) {
            Log::debug("IdxProvider: Stock price fetch error for {$ticker}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Fetch company name from IDX Company Profile API.
     */
    private function fetchCompanyName(string $ticker): ?string
    {
        try {
            $response = Http::timeout(self::TIMEOUT)
                ->withHeaders($this->getHeaders())
                ->get(self::BASE_URL . '/ListedCompany/GetCompanyProfilesDetail', [
                    'KodeEmiten' => strtoupper($ticker),
                    'language'   => 'id-id',
                ]);

            if (!$response->successful()) {
                return null;
            }

            $data = $response->json();

            return $data['NamaEmiten']
                ?? $data['namaEmiten']
                ?? $data['CompanyName']
                ?? $data['companyName']
                ?? null;
        } catch (\Exception $e) {
            Log::debug("IdxProvider: Company name fetch error for {$ticker}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Calculate Debt-to-Equity Ratio from balance sheet data.
     */
    private function calculateDer(array $ratios): ?float
    {
        // Try direct DER field
        $der = $this->safeFloat($ratios['der'] ?? $ratios['DER'] ?? null);
        if ($der !== null) {
            return $der;
        }

        // Calculate from liabilities and equity
        $liabilities = $this->safeFloat($ratios['liabilities'] ?? $ratios['Liabilities'] ?? null);
        $equity = $this->safeFloat($ratios['equity'] ?? $ratios['Equity'] ?? null);

        if ($equity !== null && $equity > 0 && $liabilities !== null) {
            return round($liabilities / $equity, 4);
        }

        return null;
    }

    /**
     * Calculate BVPS from equity and shares data.
     */
    private function calculateBvps(array $ratios): ?float
    {
        // Try direct BVPS or book value field
        $bvps = $this->safeFloat($ratios['bvps'] ?? $ratios['bookValue'] ?? null);
        if ($bvps !== null) {
            return $bvps;
        }

        // Derive from PBV and current price if available
        // PBV = Price / BVPS → BVPS = Price / PBV
        // This is a fallback — we'll get price later
        $pbv = $this->safeFloat($ratios['priceBV'] ?? $ratios['PriceBV'] ?? null);
        $eps = $this->safeFloat($ratios['eps'] ?? null);
        $per = $this->safeFloat($ratios['per'] ?? null);

        if ($pbv !== null && $pbv > 0 && $eps !== null && $per !== null && $per > 0) {
            $estimatedPrice = $eps * $per;
            return round($estimatedPrice / $pbv, 2);
        }

        return null;
    }

    /**
     * Remove .JK suffix and normalize ticker.
     */
    private function normalizeTicker(string $ticker): string
    {
        $ticker = trim(strtoupper($ticker));

        // Strip .JK suffix for IDX API
        if (str_ends_with($ticker, '.JK')) {
            $ticker = substr($ticker, 0, -3);
        }

        return $ticker;
    }

    /**
     * Safely convert value to float.
     */
    private function safeFloat(mixed $value): ?float
    {
        if ($value === null || $value === '' || $value === '-') {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        return null;
    }

    /**
     * Get HTTP headers mimicking browser request.
     */
    private function getHeaders(): array
    {
        return [
            'User-Agent' => self::USER_AGENT,
            'Referer'    => 'https://www.idx.co.id/',
            'Accept'     => 'application/json, text/plain, */*',
            'Origin'     => 'https://www.idx.co.id',
        ];
    }
}
