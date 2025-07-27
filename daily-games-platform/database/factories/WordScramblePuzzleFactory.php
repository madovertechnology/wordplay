<?php

namespace Database\Factories;

use App\Models\WordScramblePuzzle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WordScramblePuzzle>
 */
class WordScramblePuzzleFactory extends Factory
{
    protected $model = WordScramblePuzzle::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $letterSets = [
            'GAMESPOT',
            'PLATFORM',
            'CHALLENGE',
            'WORDPLAY',
            'SCRAMBLE',
            'LETTERS',
            'PUZZLES',
            'VICTORY',
            'CHAMPION',
            'CREATIVE'
        ];

        return [
            'letters' => $this->faker->randomElement($letterSets),
            'date' => $this->faker->dateTimeBetween('-30 days', '+7 days')->format('Y-m-d'),
            'possible_words_count' => $this->faker->numberBetween(15, 50),
        ];
    }

    /**
     * Create a puzzle for today.
     */
    public function today(): static
    {
        return $this->state(fn (array $attributes) => [
            'date' => now()->format('Y-m-d'),
        ]);
    }

    /**
     * Create a puzzle for yesterday.
     */
    public function yesterday(): static
    {
        return $this->state(fn (array $attributes) => [
            'date' => now()->subDay()->format('Y-m-d'),
        ]);
    }
}