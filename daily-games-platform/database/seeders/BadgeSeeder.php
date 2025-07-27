<?php

namespace Database\Seeders;

use App\Models\Badge;
use App\Models\Rank;
use Illuminate\Database\Seeder;

class BadgeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create badges
        $badges = [
            [
                'name' => 'First Steps',
                'description' => 'Complete your first word scramble puzzle',
                'icon' => '🎯',
                'criteria' => json_encode(['type' => 'games_played', 'count' => 1])
            ],
            [
                'name' => 'Word Finder',
                'description' => 'Find 5 words in a single puzzle',
                'icon' => '🔍',
                'criteria' => json_encode(['type' => 'words_in_puzzle', 'count' => 5])
            ],
            [
                'name' => 'Word Master',
                'description' => 'Find 10 words in a single puzzle',
                'icon' => '📚',
                'criteria' => json_encode(['type' => 'words_in_puzzle', 'count' => 10])
            ],
            [
                'name' => 'Vocabulary Expert',
                'description' => 'Find 15 words in a single puzzle',
                'icon' => '🧠',
                'criteria' => json_encode(['type' => 'words_in_puzzle', 'count' => 15])
            ],
            [
                'name' => 'Streak Starter',
                'description' => 'Maintain a 3-day playing streak',
                'icon' => '🔥',
                'criteria' => json_encode(['type' => 'streak', 'count' => 3])
            ],
            [
                'name' => 'Dedicated Player',
                'description' => 'Maintain a 7-day playing streak',
                'icon' => '⭐',
                'criteria' => json_encode(['type' => 'streak', 'count' => 7])
            ],
            [
                'name' => 'Streak Legend',
                'description' => 'Maintain a 30-day playing streak',
                'icon' => '👑',
                'criteria' => json_encode(['type' => 'streak', 'count' => 30])
            ],
            [
                'name' => 'Score Hunter',
                'description' => 'Achieve a score of 100 in a single puzzle',
                'icon' => '🏆',
                'criteria' => json_encode(['type' => 'single_puzzle_score', 'count' => 100])
            ],
            [
                'name' => 'High Scorer',
                'description' => 'Reach a total score of 1000 points',
                'icon' => '💎',
                'criteria' => json_encode(['type' => 'total_score', 'count' => 1000])
            ],
            [
                'name' => 'Elite Player',
                'description' => 'Reach a total score of 5000 points',
                'icon' => '🌟',
                'criteria' => json_encode(['type' => 'total_score', 'count' => 5000])
            ]
        ];

        foreach ($badges as $badge) {
            Badge::create($badge);
        }

        // Create ranks
        $ranks = [
            ['name' => 'Novice', 'threshold' => 0, 'icon' => '🥉'],
            ['name' => 'Apprentice', 'threshold' => 100, 'icon' => '🥈'],
            ['name' => 'Expert', 'threshold' => 500, 'icon' => '🥇'],
            ['name' => 'Master', 'threshold' => 1000, 'icon' => '🏆'],
            ['name' => 'Grandmaster', 'threshold' => 2500, 'icon' => '👑'],
            ['name' => 'Legend', 'threshold' => 5000, 'icon' => '⭐'],
        ];

        foreach ($ranks as $rank) {
            Rank::create($rank);
        }
    }
}