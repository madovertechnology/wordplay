<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Guest;
use App\Models\Game;
use App\Models\Badge;
use App\Models\Rank;
use App\Models\WordScramblePuzzle;
use App\Models\WordScrambleSubmission;
use App\Models\UserGameStats;
use App\Models\Streak;
use App\Models\Leaderboard;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create test users
        $users = [
            [
                'name' => 'Alice Johnson',
                'email' => 'alice@example.com',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Bob Smith',
                'email' => 'bob@example.com',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Carol Davis',
                'email' => 'carol@example.com',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ],
            [
                'name' => 'David Wilson',
                'email' => 'david@example.com',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Emma Brown',
                'email' => 'emma@example.com',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        ];

        foreach ($users as $userData) {
            User::create($userData);
        }

        // Create some guest users
        for ($i = 0; $i < 10; $i++) {
            Guest::factory()->create();
        }

        // Get the word scramble game
        $wordScrambleGame = Game::where('slug', 'word-scramble')->first();
        $users = User::all();
        $puzzles = WordScramblePuzzle::orderBy('date', 'desc')->take(10)->get();

        // Create realistic game data for users
        foreach ($users as $user) {
            $totalScore = 0;
            $playsCount = 0;
            $currentStreak = 0;
            $longestStreak = 0;
            $lastPlayedDate = null;

            // Simulate playing history for the last 10 days
            foreach ($puzzles as $index => $puzzle) {
                // Not all users play every day
                if (rand(1, 100) <= 70) { // 70% chance of playing
                    $wordsFound = rand(3, 12);
                    $puzzleScore = 0;

                    // Create submissions for this puzzle
                    $availableWords = $puzzle->words()->inRandomOrder()->take($wordsFound)->get();
                    
                    foreach ($availableWords as $word) {
                        WordScrambleSubmission::create([
                            'puzzle_id' => $puzzle->id,
                            'user_id' => $user->id,
                            'word' => $word->word,
                            'score' => $word->score
                        ]);
                        $puzzleScore += $word->score;
                    }

                    $totalScore += $puzzleScore;
                    $playsCount++;

                    // Update streak logic
                    if ($lastPlayedDate === null || 
                        (strtotime($puzzle->date) - strtotime($lastPlayedDate)) <= 86400) {
                        $currentStreak++;
                        $longestStreak = max($longestStreak, $currentStreak);
                    } else {
                        $currentStreak = 1;
                    }
                    
                    $lastPlayedDate = $puzzle->date;
                } else {
                    // Break streak if didn't play
                    $currentStreak = 0;
                }
            }

            // Create user game stats
            UserGameStats::create([
                'user_id' => $user->id,
                'game_id' => $wordScrambleGame->id,
                'total_score' => $totalScore,
                'plays_count' => $playsCount,
                'last_played_at' => $lastPlayedDate ? now()->parse($lastPlayedDate) : null
            ]);

            // Create streak record
            Streak::create([
                'user_id' => $user->id,
                'game_id' => $wordScrambleGame->id,
                'current_streak' => $currentStreak,
                'longest_streak' => $longestStreak,
                'last_played_date' => $lastPlayedDate
            ]);

            // Award badges based on achievements
            $this->awardBadges($user, $totalScore, $longestStreak, $playsCount);

            // Assign rank based on total score
            $this->assignRank($user, $totalScore);
        }

        // Create leaderboard entries
        $this->createLeaderboardEntries($wordScrambleGame);

        // Create some guest submissions for recent puzzles
        $this->createGuestSubmissions();
    }

    /**
     * Award badges to user based on their achievements.
     */
    private function awardBadges(User $user, int $totalScore, int $longestStreak, int $playsCount): void
    {
        $badges = Badge::all();
        
        foreach ($badges as $badge) {
            $criteria = json_decode($badge->criteria, true);
            $shouldAward = false;

            switch ($criteria['type']) {
                case 'games_played':
                    $shouldAward = $playsCount >= $criteria['count'];
                    break;
                case 'total_score':
                    $shouldAward = $totalScore >= $criteria['count'];
                    break;
                case 'streak':
                    $shouldAward = $longestStreak >= $criteria['count'];
                    break;
                case 'single_puzzle_score':
                    // Check if user has any submission with score >= criteria
                    $maxScore = WordScrambleSubmission::where('user_id', $user->id)
                        ->groupBy('puzzle_id')
                        ->selectRaw('SUM(score) as puzzle_score')
                        ->orderBy('puzzle_score', 'desc')
                        ->first();
                    $shouldAward = $maxScore && $maxScore->puzzle_score >= $criteria['count'];
                    break;
                case 'words_in_puzzle':
                    // Check if user found enough words in any single puzzle
                    $maxWords = WordScrambleSubmission::where('user_id', $user->id)
                        ->groupBy('puzzle_id')
                        ->selectRaw('COUNT(*) as word_count')
                        ->orderBy('word_count', 'desc')
                        ->first();
                    $shouldAward = $maxWords && $maxWords->word_count >= $criteria['count'];
                    break;
            }

            if ($shouldAward) {
                DB::table('user_badges')->insertOrIgnore([
                    'user_id' => $user->id,
                    'badge_id' => $badge->id,
                    'awarded_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }
    }

    /**
     * Assign rank to user based on total score.
     */
    private function assignRank(User $user, int $totalScore): void
    {
        $rank = Rank::where('threshold', '<=', $totalScore)
            ->orderBy('threshold', 'desc')
            ->first();

        if ($rank) {
            DB::table('user_ranks')->insertOrIgnore([
                'user_id' => $user->id,
                'rank_id' => $rank->id,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }

    /**
     * Create leaderboard entries.
     */
    private function createLeaderboardEntries(Game $game): void
    {
        $users = User::with(['gameStats' => function($query) use ($game) {
            $query->where('game_id', $game->id);
        }])->get();

        foreach ($users as $user) {
            $stats = $user->gameStats->first();
            if ($stats && $stats->total_score > 0) {
                // All-time leaderboard entry
                Leaderboard::create([
                    'user_id' => $user->id,
                    'game_id' => $game->id,
                    'score' => $stats->total_score,
                    'period_type' => 'all_time',
                    'period_date' => null
                ]);

                // Monthly leaderboard entry (simulate this month's score as portion of total)
                $monthlyScore = min($stats->total_score, rand(50, 200));
                Leaderboard::create([
                    'user_id' => $user->id,
                    'game_id' => $game->id,
                    'score' => $monthlyScore,
                    'period_type' => 'monthly',
                    'period_date' => now()->startOfMonth()->toDateString()
                ]);

                // Daily leaderboard entry (today's score)
                $dailyScore = min($monthlyScore, rand(10, 50));
                Leaderboard::create([
                    'user_id' => $user->id,
                    'game_id' => $game->id,
                    'score' => $dailyScore,
                    'period_type' => 'daily',
                    'period_date' => now()->toDateString()
                ]);
            }
        }
    }

    /**
     * Create guest submissions for recent puzzles.
     */
    private function createGuestSubmissions(): void
    {
        $guests = Guest::take(5)->get();
        $recentPuzzles = WordScramblePuzzle::orderBy('date', 'desc')->take(3)->get();

        foreach ($guests as $guest) {
            foreach ($recentPuzzles as $puzzle) {
                if (rand(1, 100) <= 40) { // 40% chance guest plays
                    $wordsFound = rand(1, 5);
                    $availableWords = $puzzle->words()->inRandomOrder()->take($wordsFound)->get();
                    
                    foreach ($availableWords as $word) {
                        WordScrambleSubmission::create([
                            'puzzle_id' => $puzzle->id,
                            'guest_id' => $guest->guest_token,
                            'word' => $word->word,
                            'score' => $word->score
                        ]);
                    }
                }
            }
        }
    }
}