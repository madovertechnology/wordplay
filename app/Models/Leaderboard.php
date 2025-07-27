<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Leaderboard extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'game_id',
        'user_id',
        'score',
        'period_type',
        'period_date',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'period_date' => 'date',
    ];

    /**
     * Get the game that the leaderboard entry belongs to.
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    /**
     * Get the user that the leaderboard entry belongs to.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include daily leaderboard entries.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string|null $date
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDaily($query, $date = null)
    {
        $date = $date ?? now()->toDateString();
        return $query->where('period_type', 'daily')->where('period_date', $date);
    }

    /**
     * Scope a query to only include monthly leaderboard entries.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string|null $yearMonth
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeMonthly($query, $yearMonth = null)
    {
        $yearMonth = $yearMonth ?? now()->format('Y-m');
        return $query->where('period_type', 'monthly')
            ->whereRaw("DATE_FORMAT(period_date, '%Y-%m') = ?", [$yearMonth]);
    }

    /**
     * Scope a query to only include all-time leaderboard entries.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAllTime($query)
    {
        return $query->where('period_type', 'all_time');
    }
}
