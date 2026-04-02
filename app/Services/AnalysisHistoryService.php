<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class AnalysisHistoryService
{
    private const CACHE_PREFIX = 'analysis_history_';

    public function saveHistory(string $ticker, array $analysisResult): void
    {
        $guestUuid = request()->cookie('guest_uuid');

        if (!is_string($guestUuid) || $guestUuid === '') {
            return;
        }

        $cacheKey = self::CACHE_PREFIX . $guestUuid;
        $history = Cache::get($cacheKey, []);

        if (!is_array($history)) {
            $history = [];
        }

        array_unshift($history, [
            'ticker' => strtoupper(trim($ticker)),
            'analysis_result' => $analysisResult,
            'timestamp' => now()->toDateTimeString(),
        ]);

        Cache::put($cacheKey, $history, now()->addDay());
    }

    public function getHistory(): array
    {
        $guestUuid = request()->cookie('guest_uuid');

        if (!is_string($guestUuid) || $guestUuid === '') {
            return [];
        }

        $history = Cache::get(self::CACHE_PREFIX . $guestUuid, []);

        return is_array($history) ? $history : [];
    }
}
