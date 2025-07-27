<?php

namespace Database\Seeders;

use App\Models\Game;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class GameSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create the Word Scramble game
        Game::create([
            'slug' => 'word-scramble',
            'name' => 'Daily Word Scramble',
            'description' => 'Form as many words as you can from the given letters. Each day brings a new set of letters to challenge your vocabulary.',
            'is_active' => true,
        ]);
        
        // Add placeholder for future games (inactive until implemented)
        Game::create([
            'slug' => 'daily-trivia',
            'name' => 'Daily Trivia',
            'description' => 'Test your knowledge with daily trivia questions across various categories.',
            'is_active' => false,
        ]);
        
        Game::create([
            'slug' => 'math-puzzle',
            'name' => 'Math Puzzle',
            'description' => 'Challenge your mathematical skills with daily number puzzles.',
            'is_active' => false,
        ]);
    }
}
