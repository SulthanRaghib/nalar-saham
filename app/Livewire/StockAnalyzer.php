<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Services\AiAnalysisService;
use App\Services\AnalysisHistoryService;
use App\Services\BandarmologyAnalyzer;
use App\Services\HealthScorer;
use App\Services\StockApiService;
use App\Services\TechnicalAnalyzer;
use App\Services\TradingPlanGenerator;
use App\Services\ValuationEngine;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * StockAnalyzer Livewire Component — Pro Version
 *
 * Main component for comprehensive stock analysis.
 * Supports: fundamental, technical, bandarmology, trading plan, and AI analysis.
 */
class StockAnalyzer extends Component
{
    // --- Input State ---
    public string $ticker = '';
    public bool $isManualMode = false;
    public ?float $eps = null;
    public ?float $bvps = null;
    public ?float $der = null;
    public ?float $roe = null;
    public ?float $npm = null;
    public ?float $currentPrice = null;

    // --- Output State ---
    public ?array $analysisResult = null;
    public ?array $tradingData = null;
    public ?array $technicalData = null;
    public ?array $bandarmologyData = null;
    public ?array $tradingPlan = null;
    public ?array $aiAnalysis = null;
    public array $history = [];
    public ?int $selectedHistoryId = null;
    public string $currency = 'IDR';
    public bool $isLoading = false;
    public bool $isAiLoading = false;
    public ?string $apiSource = null;
    public ?string $companyName = null;
    public bool $aiAvailable = false;

    public function mount(AnalysisHistoryService $historyService, AiAnalysisService $ai): void
    {
        $this->history = $historyService->getHistory();
        $this->aiAvailable = $ai->isAvailable();
    }

    /**
     * Run comprehensive stock analysis.
     */
    public function analyze(
        StockApiService $api,
        ValuationEngine $valuation,
        HealthScorer $scorer,
        TechnicalAnalyzer $technical,
        BandarmologyAnalyzer $bandarmology,
        TradingPlanGenerator $planGenerator,
        AnalysisHistoryService $historyService,
    ): void {
        $this->validate($this->rules());

        $ticker = strtoupper(trim($this->ticker));
        $displayTicker = str_ends_with($ticker, '.JK')
            ? substr($ticker, 0, -3)
            : $ticker;

        $this->currency = 'IDR';
        $this->aiAnalysis = null;

        // ---------------------------------------------------------------
        // 1. Fetch Fundamental Data
        // ---------------------------------------------------------------
        if (!$this->isManualMode) {
            $fundamentalData = $api->fetchFundamentalData($ticker);

            if ($fundamentalData === null) {
                $this->isManualMode = true;
                $this->dispatch('toast', message: "Data API untuk {$displayTicker} tidak tersedia. Silakan input data manual.", type: 'warning');
                return;
            }

            $this->eps = $fundamentalData['eps'] ?? null;
            $this->bvps = $fundamentalData['bvps'] ?? null;
            $this->der = $fundamentalData['der'] ?? null;
            $this->roe = $fundamentalData['roe'] ?? null;
            $this->npm = $fundamentalData['npm'] ?? null;
            $this->currentPrice = $fundamentalData['current_price'] ?? null;
            $this->currency = $fundamentalData['currency'] ?? 'IDR';
            $this->apiSource = $fundamentalData['source'] ?? null;
            $this->companyName = $fundamentalData['company_name'] ?? null;

            if ($this->currentPrice === null) {
                $this->isManualMode = true;
                $this->dispatch('toast', message: "Harga untuk {$displayTicker} tidak tersedia. Silakan lengkapi data manual.", type: 'warning');
                return;
            }
        }

        if (
            $this->eps === null || $this->bvps === null ||
            $this->der === null || $this->roe === null ||
            $this->npm === null || $this->currentPrice === null
        ) {
            $this->dispatch('toast', message: 'Harap lengkapi semua data fundamental sebelum analisis.', type: 'warning');
            return;
        }

        // ---------------------------------------------------------------
        // 2. Fetch Trading Data (price, volume, value, foreign flow)
        // ---------------------------------------------------------------
        $this->tradingData = $api->fetchTradingData($ticker);

        // ---------------------------------------------------------------
        // 3. Fetch Historical Prices (OHLCV for technical analysis)
        // ---------------------------------------------------------------
        $historicalPrices = $api->fetchHistoricalPrices($ticker, 90);

        // ---------------------------------------------------------------
        // 4. Run Technical Analysis
        // ---------------------------------------------------------------
        if ($historicalPrices !== null && count($historicalPrices) >= 5) {
            $this->technicalData = $technical->analyze($historicalPrices);
        } else {
            $this->technicalData = null;
        }

        // ---------------------------------------------------------------
        // 5. Run Bandarmology Analysis
        // ---------------------------------------------------------------
        if ($historicalPrices !== null && count($historicalPrices) >= 5) {
            $this->bandarmologyData = $bandarmology->analyze($historicalPrices, $this->tradingData);
        } else {
            $this->bandarmologyData = null;
        }

        // ---------------------------------------------------------------
        // 6. Run Valuation & Health Score (existing)
        // ---------------------------------------------------------------
        $valuationResult = $valuation->analyze(
            $this->eps, $this->bvps, $this->currentPrice,
            $this->der, $this->roe, $this->npm,
        );

        $healthResult = $scorer->analyze(
            $this->der, $this->roe, $this->npm, $this->eps, $this->bvps,
        );

        $verdictResult = $scorer->getInvestmentVerdict(
            $valuationResult['margin_of_safety'],
            $healthResult['score'],
            $healthResult['red_flags'],
        );

        // ---------------------------------------------------------------
        // 7. Generate Trading Plan
        // ---------------------------------------------------------------
        if ($this->technicalData !== null && $this->bandarmologyData !== null) {
            $this->tradingPlan = $planGenerator->generate(
                $this->currentPrice,
                $this->technicalData,
                $this->bandarmologyData,
                $valuationResult,
            );
        } else {
            $this->tradingPlan = null;
        }

        // ---------------------------------------------------------------
        // 8. Build Result
        // ---------------------------------------------------------------
        $statusLabel = $verdictResult['status'];
        $allWarnings = array_unique(array_merge(
            $valuationResult['warnings'],
            $healthResult['red_flags'],
        ));

        $this->analysisResult = [
            'ticker'             => $displayTicker,
            'company_name'       => $this->companyName ?? $displayTicker,
            'status'             => $statusLabel,
            'verdict'            => $verdictResult['verdict'],
            'verdict_reason'     => $verdictResult['reason'],
            'input_mode'         => $this->isManualMode ? 'Manual Input' : 'Auto API (' . ($this->apiSource ?? 'API') . ')',
            'current_price'      => round($this->currentPrice, 2),
            'fair_value'         => $valuationResult['fair_value'],
            'margin_of_safety'   => $valuationResult['margin_of_safety'],
            'valuation_method'   => $valuationResult['method'],
            'can_value'          => $valuationResult['can_value'],
            'health_score'       => $healthResult['score'],
            'health_max'         => $healthResult['max'],
            'health_grade'       => $healthResult['grade'],
            'health_grade_label' => $healthResult['grade_label'],
            'health_breakdown'   => $healthResult['breakdown'],
            'warnings'           => $allWarnings,
            'eps'                => round($this->eps, 2),
            'bvps'               => round($this->bvps, 2),
            'der'                => round($this->der, 2),
            'roe'                => round($this->roe, 2),
            'npm'                => round($this->npm, 2),
            'currency'           => $this->currency,
            'timestamp'          => now()->toDateTimeString(),
        ];

        // Save to database
        $historyService->saveHistory($displayTicker, $this->analysisResult);
        $this->history = $historyService->getHistory();

        // Set selected history to the latest entry
        $this->selectedHistoryId = $this->history[0]['id'] ?? null;

        // Clear form input after successful analysis
        $this->ticker = '';

        $this->dispatch(
            'toast',
            message: "Analisis {$displayTicker} selesai — {$statusLabel}",
            type: $statusLabel === 'BUY' ? 'success' : ($statusLabel === 'AVOID' ? 'error' : 'info')
        );
    }

    /**
     * Generate AI analysis (lazy-loaded on demand).
     */
    public function generateAiAnalysis(AiAnalysisService $ai): void
    {
        if ($this->analysisResult === null) {
            return;
        }

        $this->isAiLoading = true;

        $context = [
            'company_name'   => $this->analysisResult['company_name'] ?? '',
            'current_price'  => $this->analysisResult['current_price'] ?? 0,
            'change_percent' => $this->tradingData['change_percent'] ?? 'N/A',
            'volume'         => $this->tradingData['volume'] ?? null,
            'value'          => $this->tradingData['value'] ?? null,
            'fundamental'    => [
                'eps' => $this->analysisResult['eps'] ?? null,
                'bvps' => $this->analysisResult['bvps'] ?? null,
                'der' => $this->analysisResult['der'] ?? null,
                'roe' => $this->analysisResult['roe'] ?? null,
                'npm' => $this->analysisResult['npm'] ?? null,
            ],
            'technical'    => $this->technicalData,
            'bandarmology' => $this->bandarmologyData,
            'valuation'    => [
                'fair_value'       => $this->analysisResult['fair_value'] ?? null,
                'margin_of_safety' => $this->analysisResult['margin_of_safety'] ?? null,
            ],
            'trading_plan' => $this->tradingPlan,
        ];

        $this->aiAnalysis = $ai->generateAnalysis(
            $this->analysisResult['ticker'],
            $context,
        );

        $this->isAiLoading = false;

        if ($this->aiAnalysis !== null) {
            $this->dispatch('toast', message: 'Analisis AI berhasil dibuat!', type: 'success');
        } else {
            $this->dispatch('toast', message: 'Gagal menghasilkan analisis AI. Coba lagi nanti.', type: 'error');
        }
    }

    /**
     * Load analysis from history.
     */
    public function loadFromHistory(int $id, AnalysisHistoryService $historyService): void
    {
        $this->history = $historyService->getHistory();
        $item = collect($this->history)->firstWhere('id', $id);

        if ($item === null) {
            return;
        }

        $this->selectedHistoryId = $id;
        $result = $item['analysis_result'] ?? [];

        $this->ticker       = $result['ticker'] ?? '';
        $this->eps          = isset($result['eps']) ? (float) $result['eps'] : null;
        $this->bvps         = isset($result['bvps']) ? (float) $result['bvps'] : null;
        $this->der          = isset($result['der']) ? (float) $result['der'] : null;
        $this->roe          = isset($result['roe']) ? (float) $result['roe'] : null;
        $this->npm          = isset($result['npm']) ? (float) $result['npm'] : null;
        $this->currentPrice = isset($result['current_price']) ? (float) $result['current_price'] : null;
        $this->currency     = $result['currency'] ?? 'IDR';
        $this->companyName  = $result['company_name'] ?? null;
        $this->analysisResult = $result;
        $this->isManualMode = isset($result['input_mode']) && str_contains($result['input_mode'], 'Manual');

        // Reset pro data (not stored in history)
        $this->tradingData = null;
        $this->technicalData = null;
        $this->bandarmologyData = null;
        $this->tradingPlan = null;
        $this->aiAnalysis = null;

        $this->dispatch('toast', message: "Data {$this->ticker} dimuat dari riwayat", type: 'success');
    }

    /**
     * Delete a history item by database ID.
     */
    public function deleteHistory(int $id, AnalysisHistoryService $historyService): void
    {
        $item = collect($this->history)->firstWhere('id', $id);
        $ticker = $item['ticker'] ?? 'N/A';

        $historyService->deleteHistoryItem($id);
        $this->history = $historyService->getHistory();

        if ($this->selectedHistoryId === $id) {
            $this->selectedHistoryId = null;
            $this->analysisResult = null;
        }

        $this->dispatch('toast', message: "Riwayat {$ticker} dihapus", type: 'info');
    }

    /**
     * Toggle between auto/manual mode.
     */
    public function toggleMode(): void
    {
        $this->isManualMode = !$this->isManualMode;

        if (!$this->isManualMode) {
            $this->resetManualInputs();
        }
    }

    /**
     * Reset analysis — clear all results.
     */
    public function resetAnalysis(): void
    {
        $this->ticker = '';
        $this->analysisResult = null;
        $this->tradingData = null;
        $this->technicalData = null;
        $this->bandarmologyData = null;
        $this->tradingPlan = null;
        $this->aiAnalysis = null;
        $this->selectedHistoryId = null;
        $this->companyName = null;
        $this->apiSource = null;
        $this->resetManualInputs();
    }

    public function render(): View
    {
        return view('livewire.stock-analyzer');
    }

    protected function rules(): array
    {
        $rules = [
            'ticker' => ['required', 'string', 'max:20'],
        ];

        if ($this->isManualMode) {
            $rules['eps']          = ['required', 'numeric'];
            $rules['bvps']         = ['required', 'numeric'];
            $rules['der']          = ['required', 'numeric', 'min:0'];
            $rules['roe']          = ['required', 'numeric'];
            $rules['npm']          = ['required', 'numeric'];
            $rules['currentPrice'] = ['required', 'numeric', 'min:1'];
        }

        return $rules;
    }

    protected function messages(): array
    {
        return [
            'ticker.required'       => 'Kode saham wajib diisi.',
            'eps.required'          => 'EPS wajib diisi.',
            'bvps.required'         => 'BVPS wajib diisi.',
            'der.required'          => 'DER wajib diisi.',
            'der.min'               => 'DER tidak boleh negatif.',
            'roe.required'          => 'ROE wajib diisi.',
            'npm.required'          => 'NPM wajib diisi.',
            'currentPrice.required' => 'Harga saham wajib diisi.',
            'currentPrice.min'      => 'Harga saham harus minimal Rp 1.',
        ];
    }

    /**
     * Format large numbers for display (e.g., 1.2M, 45.3B).
     */
    public function formatNumber(?float $number): string
    {
        if ($number === null) {
            return 'N/A';
        }

        $abs = abs($number);

        if ($abs >= 1_000_000_000_000) {
            return number_format($number / 1_000_000_000_000, 1, ',', '.') . 'T';
        }
        if ($abs >= 1_000_000_000) {
            return number_format($number / 1_000_000_000, 1, ',', '.') . 'B';
        }
        if ($abs >= 1_000_000) {
            return number_format($number / 1_000_000, 1, ',', '.') . 'M';
        }
        if ($abs >= 1_000) {
            return number_format($number / 1_000, 1, ',', '.') . 'K';
        }

        return number_format($number, 0, ',', '.');
    }

    private function resetManualInputs(): void
    {
        $this->eps = null;
        $this->bvps = null;
        $this->der = null;
        $this->roe = null;
        $this->npm = null;
        $this->currentPrice = null;
    }
}
