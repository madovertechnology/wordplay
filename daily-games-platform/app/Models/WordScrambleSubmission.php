<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WordScrambleSubmission extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'puzzle_id',
        'user_id',
        'guest_id',
        'word',
        'score',
    ];

    /**
     * Get the puzzle that the submission belongs to.
     */
    public function puzzle(): BelongsTo
    {
        return $this->belongsTo(WordScramblePuzzle::class, 'puzzle_id');
    }

    /**
     * Get the user that made the submission.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the guest that made the submission.
     */
    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class, 'guest_id', 'guest_token');
    }

    /**
     * Check if this submission was made by a user (not a guest).
     *
     * @return bool
     */
    public function isUserSubmission(): bool
    {
        return !is_null($this->user_id);
    }

    /**
     * Check if this submission was made by a guest.
     *
     * @return bool
     */
    public function isGuestSubmission(): bool
    {
        return !is_null($this->guest_id);
    }

    /**
     * Get submissions for a specific user and puzzle.
     *
     * @param int $userId
     * @param int $puzzleId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function forUserAndPuzzle(int $userId, int $puzzleId)
    {
        return self::where('user_id', $userId)
                   ->where('puzzle_id', $puzzleId)
                   ->get();
    }

    /**
     * Get submissions for a specific guest and puzzle.
     *
     * @param string $guestId
     * @param int $puzzleId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function forGuestAndPuzzle(string $guestId, int $puzzleId)
    {
        return self::where('guest_id', $guestId)
                   ->where('puzzle_id', $puzzleId)
                   ->get();
    }
}
