<?php

namespace Database\Seeders;

use App\Models\WordScramblePuzzle;
use App\Models\WordScrambleWord;
use Illuminate\Database\Seeder;

class WordScrambleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create puzzles for the last 30 days and next 7 days
        $puzzles = [
            // Today's puzzle
            [
                'letters' => 'GAMESPOT',
                'date' => now()->format('Y-m-d'),
                'words' => ['GAME', 'GAMES', 'SPOT', 'POST', 'MOST', 'STOP', 'TOPS', 'POTS', 'MAPS', 'TAME', 'TEAM', 'MEAT', 'MATE', 'STEAM', 'STAGE', 'GATES', 'PAGES']
            ],
            // Yesterday's puzzle
            [
                'letters' => 'PLATFORM',
                'date' => now()->subDay()->format('Y-m-d'),
                'words' => ['PLATFORM', 'FORM', 'PART', 'PORT', 'TRAP', 'LAMP', 'PALM', 'PLOT', 'MORAL', 'POLAR', 'PATROL', 'PORTAL']
            ],
            // Day before yesterday
            [
                'letters' => 'CHALLENGE',
                'date' => now()->subDays(2)->format('Y-m-d'),
                'words' => ['CHALLENGE', 'CHANGE', 'ANGEL', 'CAGE', 'LACE', 'LANE', 'LEAN', 'CLEAN', 'GLANCE', 'LANCE', 'CANCEL']
            ],
            // More historical puzzles
            [
                'letters' => 'WORDPLAY',
                'date' => now()->subDays(3)->format('Y-m-d'),
                'words' => ['WORD', 'PLAY', 'WORLD', 'LORD', 'ROAD', 'LOAD', 'DRAW', 'WRAP', 'PRAY', 'ROYAL', 'POORLY']
            ],
            [
                'letters' => 'SCRAMBLE',
                'date' => now()->subDays(4)->format('Y-m-d'),
                'words' => ['SCRAMBLE', 'SCRAM', 'CREAM', 'CLEAR', 'SCALE', 'SCARE', 'RACE', 'CARE', 'BEAR', 'BEAM', 'BLAME', 'CABLE']
            ],
            [
                'letters' => 'LETTERS',
                'date' => now()->subDays(5)->format('Y-m-d'),
                'words' => ['LETTERS', 'LETTER', 'SETTLE', 'STEEL', 'RESET', 'STEER', 'TREES', 'STREET', 'TESTER']
            ],
            [
                'letters' => 'PUZZLES',
                'date' => now()->subDays(6)->format('Y-m-d'),
                'words' => ['PUZZLE', 'PUZZLES', 'PULSE', 'SPELL', 'PULLS', 'PLUS', 'ZEST', 'PEST', 'STEP', 'PETS']
            ],
            // Tomorrow's puzzle (for testing future dates)
            [
                'letters' => 'VICTORY',
                'date' => now()->addDay()->format('Y-m-d'),
                'words' => ['VICTORY', 'CITY', 'RIOT', 'TROY', 'IVORY', 'COVER', 'VOICE', 'CIVIC', 'VICTOR']
            ]
        ];

        foreach ($puzzles as $puzzleData) {
            $puzzle = WordScramblePuzzle::create([
                'letters' => $puzzleData['letters'],
                'date' => $puzzleData['date'],
                'possible_words_count' => count($puzzleData['words'])
            ]);

            // Create words for this puzzle
            foreach ($puzzleData['words'] as $word) {
                WordScrambleWord::create([
                    'puzzle_id' => $puzzle->id,
                    'word' => $word,
                    'score' => $this->calculateWordScore($word)
                ]);
            }
        }

        // Create additional historical puzzles for the past month
        for ($i = 7; $i <= 30; $i++) {
            $letterSets = [
                'CHAMPION', 'CREATIVE', 'BUILDING', 'THINKING', 'LEARNING', 'TEACHING',
                'COMPUTER', 'KEYBOARD', 'INTERNET', 'SOFTWARE', 'HARDWARE', 'NETWORK',
                'LANGUAGE', 'SPEAKING', 'WRITING', 'READING', 'GRAMMAR', 'SPELLING'
            ];
            
            $letters = $letterSets[array_rand($letterSets)];
            $date = now()->subDays($i)->format('Y-m-d');
            
            $puzzle = WordScramblePuzzle::create([
                'letters' => $letters,
                'date' => $date,
                'possible_words_count' => rand(15, 35)
            ]);

            // Create some sample words for historical puzzles
            $sampleWords = $this->generateSampleWords($letters);
            foreach ($sampleWords as $word) {
                WordScrambleWord::create([
                    'puzzle_id' => $puzzle->id,
                    'word' => $word,
                    'score' => $this->calculateWordScore($word)
                ]);
            }
        }
    }

    /**
     * Calculate score for a word based on length.
     */
    private function calculateWordScore(string $word): int
    {
        $length = strlen($word);
        
        if ($length <= 3) return $length;
        if ($length == 4) return 4;
        if ($length == 5) return 6;
        if ($length == 6) return 8;
        if ($length == 7) return 12;
        if ($length >= 8) return 16;
        
        return $length;
    }

    /**
     * Generate sample words from letters (simplified for seeding).
     */
    private function generateSampleWords(string $letters): array
    {
        $commonWords = [
            'THE', 'AND', 'FOR', 'ARE', 'BUT', 'NOT', 'YOU', 'ALL', 'CAN', 'HER', 'WAS', 'ONE',
            'OUR', 'HAD', 'BY', 'HOT', 'WORD', 'WHAT', 'SOME', 'WE', 'IT', 'DO', 'GO', 'NO',
            'WAY', 'COULD', 'MY', 'THAN', 'FIRST', 'BEEN', 'CALL', 'WHO', 'ITS', 'NOW', 'FIND',
            'LONG', 'DOWN', 'DAY', 'DID', 'GET', 'HAS', 'HIM', 'HIS', 'HOW', 'MAN', 'NEW', 'OLD',
            'SEE', 'TWO', 'WHO', 'BOY', 'DID', 'ITS', 'LET', 'PUT', 'SAY', 'SHE', 'TOO', 'USE'
        ];

        $validWords = [];
        $lettersArray = str_split(strtoupper($letters));
        
        foreach ($commonWords as $word) {
            if ($this->canFormWord($word, $lettersArray)) {
                $validWords[] = $word;
                if (count($validWords) >= 10) break; // Limit for seeding
            }
        }

        return $validWords;
    }

    /**
     * Check if a word can be formed from available letters.
     */
    private function canFormWord(string $word, array $availableLetters): bool
    {
        $wordLetters = str_split(strtoupper($word));
        $letterCount = array_count_values($availableLetters);
        
        foreach ($wordLetters as $letter) {
            if (!isset($letterCount[$letter]) || $letterCount[$letter] <= 0) {
                return false;
            }
            $letterCount[$letter]--;
        }
        
        return true;
    }
}