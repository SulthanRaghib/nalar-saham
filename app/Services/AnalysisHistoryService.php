<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AnalysisHistory;
use Illuminate\Support\Facades\Log;

/**
 * AnalysisHistoryService
 *
 * Manages analysis history using database persistence instead of volatile cache.
 * Data persists for 30 days (configurable) and survives cache clears and restarts.
 *
 * @package App\Services
 */
class AnalysisHistoryService
{
    private const MAX_HISTORY_PER_GUEST = 50;
    private const RETENTION_DAYS = 30;

    /**
     * Save an analysis result to history.
     */
    public function saveHistory(string $ticker, array $analysisResult): void
    {
        $guestUuid = $this->getGuestUuid();

        if ($guestUuid === null) {
            return;
        }

        try {
            AnalysisHistory::create([
                'guest_uuid'      => $guestUuid,
                'ticker'          => strtoupper(trim($ticker)),
                'analysis_result' => $analysisResult,
            ]);

            // Prune old entries if exceeding limit
            $this->pruneExcess($guestUuid);
        } catch (\Exception $e) {
            Log::error('AnalysisHistoryService: Save failed — ' . $e->getMessage());
        }
    }

    /**
     * Get analysis history for current guest.
     *
     * @return array List of history items, most recent first
     */
    public function getHistory(): array
    {
        $guestUuid = $this->getGuestUuid();

        if ($guestUuid === null) {
            return [];
        }

        try {
            return AnalysisHistory::forGuest($guestUuid)
                ->recent()
                ->limit(self::MAX_HISTORY_PER_GUEST)
                ->get()
                ->map(fn (AnalysisHistory $item) => [
                    'id'              => $item->id,
                    'ticker'          => $item->ticker,
                    'analysis_result' => $item->analysis_result,
                    'timestamp'       => $item->created_at->toDateTimeString(),
                ])
                ->toArray();
        } catch (\Exception $e) {
            Log::error('AnalysisHistoryService: Get history failed — ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Delete a specific history item.
     */
    public function deleteHistoryItem(int $id): void
    {
        $guestUuid = $this->getGuestUuid();

        if ($guestUuid === null) {
            return;
        }

        try {
            AnalysisHistory::where('id', $id)
                ->where('guest_uuid', $guestUuid)
                ->delete();
        } catch (\Exception $e) {
            Log::error('AnalysisHistoryService: Delete failed — ' . $e->getMessage());
        }
    }

    /**
     * Cleanup old records across all guests.
     * Can be called from a scheduled command.
     */
    public function cleanup(): int
    {
        try {
            return AnalysisHistory::olderThan(self::RETENTION_DAYS)->delete();
        } catch (\Exception $e) {
            Log::error('AnalysisHistoryService: Cleanup failed — ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get the current guest UUID from cookies.
     */
    private function getGuestUuid(): ?string
    {
        $guestUuid = request()->cookie('guest_uuid');

        if (!is_string($guestUuid) || $guestUuid === '') {
            return null;
        }

        return $guestUuid;
    }

    /**
     * Remove excess history entries for a guest.
     */
    private function pruneExcess(string $guestUuid): void
    {
        $count = AnalysisHistory::forGuest($guestUuid)->count();

        if ($count > self::MAX_HISTORY_PER_GUEST) {
            $toDelete = $count - self::MAX_HISTORY_PER_GUEST;

            AnalysisHistory::forGuest($guestUuid)
                ->orderBy('created_at', 'asc')
                ->limit($toDelete)
                ->delete();
        }
    }
}
