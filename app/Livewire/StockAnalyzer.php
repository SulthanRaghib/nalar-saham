<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Services\AnalysisHistoryService;
use App\Services\HealthScorer;
use App\Services\StockApiService;
use App\Services\ValuationEngine;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * StockAnalyzer Livewire Component
 *
 * Main component for stock fundamental analysis.
 * Handles ticker input, API data fetching, Graham valuation, and history management.
 *
 * Supports both profitable and loss-making companies:
 * - Positive EPS/BVPS → Full Graham Number analysis
 * - Negative EPS       → PBV-based alternative analysis
 * - Negative EPS+BVPS  → Risk assessment only (cannot value)
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
    public array $history = [];
    public ?int $selectedHistoryId = null;
    public string $currency = 'IDR';
    public bool $isLoading = false;
    public ?string $apiSource = null;
    public ?string $companyName = null;

    public function mount(AnalysisHistoryService $historyService): void
    {
        $this->history = $historyService->getHistory();
    }

    /**
     * Run stock analysis.
     *
     * Now handles ALL cases including negative EPS/BVPS companies.
     * Never blocks with an error — always provides useful analysis output.
     */
    public function analyze(
        StockApiService $api,
        ValuationEngine $valuation,
        HealthScorer $scorer,
        AnalysisHistoryService $historyService
    ): void {
        $this->validate($this->rules());

        $ticker = strtoupper(trim($this->ticker));

        // Remove .JK suffix for clean display
        $displayTicker = str_ends_with($ticker, '.JK')
            ? substr($ticker, 0, -3)
            : $ticker;

        $this->currency = 'IDR';

        if (!$this->isManualMode) {
            $fundamentalData = $api->fetchFundamentalData($ticker);

            if ($fundamentalData === null) {
                $this->isManualMode = true;
                $this->dispatch(
                    'toast',
                    message: "Data API untuk {$displayTicker} tidak tersedia. Silakan input data manual.",
                    type: 'warning'
                );
                return;
            }

            // Populate fields from API — accept ALL values including negatives
            $this->eps = $fundamentalData['eps'] ?? null;
            $this->bvps = $fundamentalData['bvps'] ?? null;
            $this->der = $fundamentalData['der'] ?? null;
            $this->roe = $fundamentalData['roe'] ?? null;
            $this->npm = $fundamentalData['npm'] ?? null;
            $this->currentPrice = $fundamentalData['current_price'] ?? null;
            $this->currency = $fundamentalData['currency'] ?? 'IDR';
            $this->apiSource = $fundamentalData['source'] ?? null;
            $this->companyName = $fundamentalData['company_name'] ?? null;

            // Minimum data: need at least price and some metric
            if ($this->currentPrice === null) {
                $this->isManualMode = true;
                $this->dispatch(
                    'toast',
                    message: "Harga untuk {$displayTicker} tidak tersedia. Silakan lengkapi data manual.",
                    type: 'warning'
                );
                return;
            }
        }

        // Validate required fields exist (values CAN be negative — that's valid data!)
        if (
            $this->eps === null || $this->bvps === null ||
            $this->der === null || $this->roe === null ||
            $this->npm === null || $this->currentPrice === null
        ) {
            $this->dispatch(
                'toast',
                message: 'Harap lengkapi semua data fundamental sebelum analisis.',
                type: 'warning'
            );
            return;
        }

        // ---------------------------------------------------------------
        // Run the analysis engines — these now handle ALL edge cases
        // ---------------------------------------------------------------

        // 1. Valuation (Graham Number or PBV fallback)
        $valuationResult = $valuation->analyze(
            $this->eps,
            $this->bvps,
            $this->currentPrice,
            $this->der,
            $this->roe,
            $this->npm,
        );

        // 2. Health Score (0-100 weighted scoring)
        $healthResult = $scorer->analyze(
            $this->der,
            $this->roe,
            $this->npm,
            $this->eps,
            $this->bvps,
        );

        // 3. Investment Verdict
        $verdictResult = $scorer->getInvestmentVerdict(
            $valuationResult['margin_of_safety'],
            $healthResult['score'],
            $healthResult['red_flags'],
        );

        $statusLabel = $verdictResult['status'];

        // Merge all warnings + red flags
        $allWarnings = array_merge(
            $valuationResult['warnings'],
            $healthResult['red_flags'],
        );
        $allWarnings = array_unique($allWarnings);

        $this->analysisResult = [
            'ticker'           => $displayTicker,
            'company_name'     => $this->companyName ?? $displayTicker,
            'status'           => $statusLabel,
            'verdict'          => $verdictResult['verdict'],
            'verdict_reason'   => $verdictResult['reason'],
            'input_mode'       => $this->isManualMode ? 'Manual Input' : 'Auto API (' . ($this->apiSource ?? 'API') . ')',
            'current_price'    => round($this->currentPrice, 2),
            'fair_value'       => $valuationResult['fair_value'],
            'margin_of_safety' => $valuationResult['margin_of_safety'],
            'valuation_method' => $valuationResult['method'],
            'can_value'        => $valuationResult['can_value'],
            'health_score'     => $healthResult['score'],
            'health_max'       => $healthResult['max'],
            'health_grade'     => $healthResult['grade'],
            'health_grade_label' => $healthResult['grade_label'],
            'health_breakdown' => $healthResult['breakdown'],
            'warnings'         => $allWarnings,
            'eps'              => round($this->eps, 2),
            'bvps'             => round($this->bvps, 2),
            'der'              => round($this->der, 2),
            'roe'              => round($this->roe, 2),
            'npm'              => round($this->npm, 2),
            'currency'         => $this->currency,
            'timestamp'        => now()->toDateTimeString(),
        ];

        // Save to database
        $historyService->saveHistory($displayTicker, $this->analysisResult);
        $this->history = $historyService->getHistory();

        $this->dispatch(
            'toast',
            message: "Analisis {$displayTicker} selesai — {$statusLabel}",
            type: $statusLabel === 'BUY' ? 'success' : ($statusLabel === 'AVOID' ? 'error' : 'info')
        );
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

        $this->dispatch(
            'toast',
            message: "Data {$this->ticker} dimuat dari riwayat",
            type: 'success'
        );
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

        $this->dispatch(
            'toast',
            message: "Riwayat {$ticker} dihapus",
            type: 'info'
        );
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
        $this->selectedHistoryId = null;
        $this->companyName = null;
        $this->apiSource = null;
        $this->resetManualInputs();
    }

    public function render(): View
    {
        return view('livewire.stock-analyzer');
    }

    /**
     * Validation rules.
     * Note: 'numeric' allows negative values (correct for EPS, ROE, NPM).
     * Only DER and currentPrice must be non-negative.
     */
    protected function rules(): array
    {
        $rules = [
            'ticker' => ['required', 'string', 'max:20'],
        ];

        if ($this->isManualMode) {
            $rules['eps']          = ['required', 'numeric'];        // CAN be negative
            $rules['bvps']         = ['required', 'numeric'];        // CAN be negative
            $rules['der']          = ['required', 'numeric', 'min:0']; // Always >= 0
            $rules['roe']          = ['required', 'numeric'];        // CAN be negative
            $rules['npm']          = ['required', 'numeric'];        // CAN be negative
            $rules['currentPrice'] = ['required', 'numeric', 'min:1']; // Must be > 0
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
