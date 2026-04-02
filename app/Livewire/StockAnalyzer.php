<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Services\AnalysisHistoryService;
use App\Services\HealthScorer;
use App\Services\StockApiService;
use App\Services\ValuationEngine;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class StockAnalyzer extends Component
{
    public string $ticker = '';

    public ?array $analysisResult = null;

    public array $history = [];

    public bool $isManualMode = false;

    public ?float $eps = null;

    public ?float $bvps = null;

    public ?float $der = null;

    public ?float $roe = null;

    public ?float $npm = null;

    public ?float $currentPrice = null;

    public function mount(AnalysisHistoryService $historyService): void
    {
        $this->history = $historyService->getHistory();
    }

    public function toggleMode(): void
    {
        $this->isManualMode = ! $this->isManualMode;

        if (! $this->isManualMode) {
            $this->resetManualInputs();
        }
    }

    public function analyze(
        StockApiService $api,
        ValuationEngine $valuation,
        HealthScorer $scorer,
        AnalysisHistoryService $historyService
    ): void {
        $this->validate($this->rules());

        $ticker = strtoupper(trim($this->ticker));

        if ($ticker === '') {
            return;
        }

        if (! $this->isManualMode) {
            $fundamentalData = $api->fetchFundamentalData($ticker);

            if ($fundamentalData === null) {
                $this->isManualMode = true;
                $this->dispatch(
                    'toast',
                    message: 'API data could not be loaded. Switch to manual input and try again.',
                    type: 'warning'
                );

                return;
            }

            $this->eps = isset($fundamentalData['eps']) ? (float) $fundamentalData['eps'] : null;
            $this->bvps = isset($fundamentalData['bvps']) ? (float) $fundamentalData['bvps'] : null;
            $this->der = isset($fundamentalData['der']) ? (float) $fundamentalData['der'] : null;
            $this->roe = isset($fundamentalData['roe']) ? (float) $fundamentalData['roe'] : null;
            $this->npm = isset($fundamentalData['npm']) ? (float) $fundamentalData['npm'] : null;
            $this->currentPrice = isset($fundamentalData['current_price']) ? (float) $fundamentalData['current_price'] : null;
        }

        if (
            $this->eps === null ||
            $this->bvps === null ||
            $this->der === null ||
            $this->roe === null ||
            $this->npm === null ||
            $this->currentPrice === null
        ) {
            $this->dispatch(
                'toast',
                message: 'Please complete all fundamental metrics before running the analysis.',
                type: 'warning'
            );

            return;
        }

        $fairValue = $valuation->calculateGrahamNumber($this->eps, $this->bvps);

        if ($fairValue === null) {
            $this->dispatch(
                'toast',
                message: 'Unable to calculate fair value from the provided EPS and BVPS.',
                type: 'error'
            );

            return;
        }

        $marginOfSafety = $valuation->calculateMarginOfSafety($this->currentPrice, $fairValue);
        $healthScore = $scorer->calculateScore($this->der, $this->roe, $this->npm);
        $verdict = $scorer->getInvestmentVerdict($marginOfSafety, $healthScore);
        $statusLabel = match ($verdict) {
            'Undervalued' => 'BUY',
            'Fairly Valued' => 'HOLD',
            'Overvalued' => 'AVOID',
            default => 'REVIEW',
        };

        $this->analysisResult = [
            'ticker' => $ticker,
            'status' => $statusLabel,
            'verdict' => $verdict,
            'input_mode' => $this->isManualMode ? 'Manual Input' : 'Auto API Fetch',
            'current_price' => round($this->currentPrice, 2),
            'fair_value' => round($fairValue, 2),
            'margin_of_safety' => round($marginOfSafety, 2),
            'health_score' => $healthScore,
            'score_max' => 3,
            'eps' => round($this->eps, 2),
            'bvps' => round($this->bvps, 2),
            'der' => round($this->der, 2),
            'roe' => round($this->roe, 2),
            'npm' => round($this->npm, 2),
            'timestamp' => now()->toDateTimeString(),
        ];

        $historyService->saveHistory($ticker, $this->analysisResult);
        $this->history = $historyService->getHistory();
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
            $rules['eps'] = ['required', 'numeric'];
            $rules['bvps'] = ['required', 'numeric'];
            $rules['der'] = ['required', 'numeric'];
            $rules['roe'] = ['required', 'numeric'];
            $rules['npm'] = ['required', 'numeric'];
            $rules['currentPrice'] = ['required', 'numeric'];
        }

        return $rules;
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
