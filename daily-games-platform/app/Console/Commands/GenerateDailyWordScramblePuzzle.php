<?php

namespace App\Console\Commands;

use App\Services\Game\WordScramblePuzzleService;
use Illuminate\Console\Command;

class GenerateDailyWordScramblePuzzle extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'game:generate-word-scramble-puzzle {--days=1 : Number of days to generate puzzles for}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate daily Word Scramble puzzles';

    /**
     * Execute the console command.
     */
    public function handle(WordScramblePuzzleService $puzzleService)
    {
        $days = (int) $this->option('days');
        
        $this->info("Generating Word Scramble puzzles for the next {$days} day(s)...");
        
        // Use forTesting=true in development environment
        $forTesting = app()->environment('local', 'testing');
        $puzzles = $puzzleService->generateFuturePuzzles($days, $forTesting);
        
        foreach ($puzzles as $puzzle) {
            $this->info("Generated puzzle for {$puzzle->date->format('Y-m-d')}: {$puzzle->letters} with {$puzzle->possible_words_count} possible words");
        }
        
        $this->info('Done!');
        
        return Command::SUCCESS;
    }
}