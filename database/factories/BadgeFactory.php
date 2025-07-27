<?php

namespace Database\Factories;

use App\Models\Badge;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Badge>
 */
class BadgeFactory extends Factory
{
    protected $model = Badge::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $badges = [
            [
                'name' => 'First Steps',
                'description' => 'Complete your first word scramble puzzle',
                'icon' => '🎯',
                'criteria' => ['type' => 'games_played', 'count' => 1]
            ],
            [
                'name' => 'Word Master',
                'description' => 'Find 10 words in a single puzzle',
                'icon' => '📚',
                'criteria' => ['type' => 'words_in_puzzle', 'count' => 10]
            ],
            [
                'name' => 'Streak Starter',
                'description' => 'Maintain a 3-day playing streak',
                'icon' => '🔥',
                'criteria' => ['type' => 'streak', 'count' => 3]
            ],
            [
                'name' => 'Dedicated Player',
                'description' => 'Maintain a 7-day playing streak',
                'icon' => '⭐',
                'criteria' => ['type' => 'streak', 'count' => 7]
            ],
            [
                'name' => 'Score Hunter',
                'description' => 'Achieve a score of 100 in a single puzzle',
                'icon' => '🏆',
                'criteria' => ['type' => 'single_puzzle_score', 'count' => 100]
            ],
            [
                'name' => 'High Scorer',
                'description' => 'Reach a total score of 1000 points',
                'icon' => '💎',
                'criteria' => ['type' => 'total_score', 'count' => 1000]
            ]
        ];

        $badge = $this->faker->randomElement($badges);
        
        return [
            'name' => $badge['name'],
            'description' => $badge['description'],
            'icon' => $badge['icon'],
            'criteria' => json_encode($badge['criteria']),
        ];
    }
}