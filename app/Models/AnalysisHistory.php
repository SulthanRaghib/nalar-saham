<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * AnalysisHistory Model
 *
 * Persists stock analysis results in the database instead of volatile cache.
 * Each record tracks: guest UUID, ticker symbol, and the full analysis result as JSON.
 *
 * @property int    $id
 * @property string $guest_uuid
 * @property string $ticker
 * @property array  $analysis_result
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class AnalysisHistory extends Model
{
    protected $fillable = [
        'guest_uuid',
        'ticker',
        'analysis_result',
    ];

    protected function casts(): array
    {
        return [
            'analysis_result' => 'array',
        ];
    }

    /**
     * Scope: filter by guest UUID.
     */
    public function scopeForGuest(Builder $query, string $uuid): Builder
    {
        return $query->where('guest_uuid', $uuid);
    }

    /**
     * Scope: order by most recent first.
     */
    public function scopeRecent(Builder $query): Builder
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Scope: records older than given days.
     */
    public function scopeOlderThan(Builder $query, int $days): Builder
    {
        return $query->where('created_at', '<', now()->subDays($days));
    }
}
