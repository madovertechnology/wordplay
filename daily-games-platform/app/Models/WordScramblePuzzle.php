<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WordScramblePuzzle extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'letters',
        'date',
        'possible_words_count',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date' => 'date',
    ];

    /**
     * Get the words for the puzzle.
     */
    public function words(): HasMany
    {
        return $this->hasMany(WordScrambleWord::class, 'puzzle_id');
    }

    /**
     * Get the submissions for the puzzle.
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(WordScrambleSubmission::class, 'puzzle_id');
    }

    /**
     * Get the puzzle for a specific date.
     *
     * @param string $date
     * @return self|null
     */
    public static function forDate(string $date): ?self
    {
        return self::whereDate('date', $date)->first();
    }

    /**
     * Get today's puzzle.
     *
     * @return self|null
     */
    public static function today(): ?self
    {
        return self::forDate(now()->toDateString());
    }

    /**
     * Get user submissions for this puzzle.
     *
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUserSubmissions(int $userId)
    {
        return $this->submissions()->where('user_id', $userId)->get();
    }

    /**
     * Get guest submissions for this puzzle.
     *
     * @param string $guestId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getGuestSubmissions(string $guestId)
    {
        return $this->submissions()->where('guest_id', $guestId)->get();
    }

    /**
     * Get total unique words found by all players for this puzzle.
     *
     * @return int
     */
    public function getTotalUniqueWordsFound(): int
    {
        return $this->submissions()->distinct('word')->count('word');
    }

    /**
     * Get total score for a user on this puzzle.
     *
     * @param int $userId
     * @return int
     */
    public function getUserTotalScore(int $userId): int
    {
        return $this->submissions()->where('user_id', $userId)->sum('score');
    }

    /**
     * Get total score for a guest on this puzzle.
     *
     * @param string $guestId
     * @return int
     */
    public function getGuestTotalScore(string $guestId): int
    {
        return $this->submissions()->where('guest_id', $guestId)->sum('score');
    }
}
