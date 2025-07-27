<?php

namespace App\Services\Game;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DictionaryService
{
    /**
     * Cache key for dictionary words
     */
    const DICTIONARY_CACHE_KEY = 'dictionary_words';

    /**
     * Cache TTL in seconds (1 week)
     */
    const CACHE_TTL = 604800;
    
    /**
     * Cache TTL for possible words (24 hours)
     */
    const POSSIBLE_WORDS_CACHE_TTL = 86400;
    
    /**
     * Cache key prefix for possible words
     */
    const POSSIBLE_WORDS_CACHE_PREFIX = 'dictionary.possible_words';
    
    /**
     * Cache TTL for word validation (24 hours)
     */
    const WORD_VALIDATION_CACHE_TTL = 86400;
    
    /**
     * Cache key prefix for word validation
     */
    const WORD_VALIDATION_CACHE_PREFIX = 'dictionary.word_validation';

    /**
     * Minimum word length for valid words
     */
    const MIN_WORD_LENGTH = 3;

    /**
     * Check if a word is valid (exists in the dictionary)
     *
     * @param string $word
     * @return bool
     */
    public function isValidWord(string $word): bool
    {
        $word = strtolower(trim($word));
        
        // Check minimum length requirement
        if (strlen($word) < self::MIN_WORD_LENGTH) {
            return false;
        }
        
        // Check cache first
        $cacheKey = $this->getWordValidationCacheKey($word);
        if (Cache::has($cacheKey)) {
            Log::info("Word validation cache hit for: {$word}");
            return Cache::get($cacheKey);
        }
        
        // Check if the word exists in our dictionary
        $dictionary = $this->getDictionary();
        $isValid = in_array($word, $dictionary);
        
        // Cache the result
        Cache::put($cacheKey, $isValid, self::WORD_VALIDATION_CACHE_TTL);
        Log::info("Cached word validation for: {$word}, valid: " . ($isValid ? 'true' : 'false'));
        
        return $isValid;
    }
    
    /**
     * Get the cache key for word validation
     *
     * @param string $word
     * @return string
     */
    protected function getWordValidationCacheKey(string $word): string
    {
        return self::WORD_VALIDATION_CACHE_PREFIX . "." . md5($word);
    }

    /**
     * Get all possible words that can be formed from the given letters
     *
     * @param string $letters
     * @return array
     */
    public function getPossibleWords(string $letters): array
    {
        $letters = strtolower($letters);
        
        // Check cache first
        $cacheKey = $this->getPossibleWordsCacheKey($letters);
        if (Cache::has($cacheKey)) {
            Log::info("Possible words cache hit for letters: {$letters}");
            return Cache::get($cacheKey);
        }
        
        $dictionary = $this->getDictionary();
        $possibleWords = [];
        
        foreach ($dictionary as $word) {
            if ($this->canFormWord($letters, $word)) {
                $possibleWords[] = $word;
            }
        }
        
        // Cache the result
        Cache::put($cacheKey, $possibleWords, self::POSSIBLE_WORDS_CACHE_TTL);
        Log::info("Cached possible words for letters: {$letters}, found: " . count($possibleWords) . " words");
        
        return $possibleWords;
    }
    
    /**
     * Get the cache key for possible words
     *
     * @param string $letters
     * @return string
     */
    protected function getPossibleWordsCacheKey(string $letters): string
    {
        // Sort the letters to ensure consistent cache keys regardless of letter order
        $sortedLetters = str_split($letters);
        sort($sortedLetters);
        $sortedLetters = implode('', $sortedLetters);
        
        return self::POSSIBLE_WORDS_CACHE_PREFIX . "." . $sortedLetters;
    }

    /**
     * Check if a word can be formed using the given letters
     *
     * @param string $letters
     * @param string $word
     * @return bool
     */
    public function canFormWord(string $letters, string $word): bool
    {
        // Convert to lowercase for consistency
        $letters = strtolower($letters);
        $word = strtolower($word);
        
        // Check minimum length requirement
        if (strlen($word) < self::MIN_WORD_LENGTH) {
            return false;
        }
        
        // Count the frequency of each letter in the available letters
        $letterCounts = [];
        for ($i = 0; $i < strlen($letters); $i++) {
            $letter = $letters[$i];
            if (!isset($letterCounts[$letter])) {
                $letterCounts[$letter] = 0;
            }
            $letterCounts[$letter]++;
        }
        
        // Check if the word can be formed using the available letters
        $wordLetterCounts = [];
        for ($i = 0; $i < strlen($word); $i++) {
            $letter = $word[$i];
            
            // If the letter is not in the available letters, the word cannot be formed
            if (!isset($letterCounts[$letter])) {
                return false;
            }
            
            // Count the frequency of each letter in the word
            if (!isset($wordLetterCounts[$letter])) {
                $wordLetterCounts[$letter] = 0;
            }
            $wordLetterCounts[$letter]++;
            
            // If the word uses more of a letter than is available, the word cannot be formed
            if ($wordLetterCounts[$letter] > $letterCounts[$letter]) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Calculate the score for a word based on its length
     *
     * @param string $word
     * @return int
     */
    public function calculateWordScore(string $word): int
    {
        $length = strlen(trim($word));
        
        // Scrabble-style scoring based on word length
        switch ($length) {
            case 3:
                return 1;
            case 4:
                return 2;
            case 5:
                return 4;
            case 6:
                return 7;
            case 7:
                return 10;
            default:
                return $length > 7 ? 15 : 0;
        }
    }

    /**
     * Get the dictionary of valid words
     *
     * @return array
     */
    protected function getDictionary(): array
    {
        // Try to get the dictionary from cache
        if (Cache::has(self::DICTIONARY_CACHE_KEY)) {
            return Cache::get(self::DICTIONARY_CACHE_KEY);
        }
        
        // If not in cache, load from file or API
        $dictionary = $this->loadDictionary();
        
        // Cache the dictionary
        Cache::put(self::DICTIONARY_CACHE_KEY, $dictionary, self::CACHE_TTL);
        
        return $dictionary;
    }

    /**
     * Load the dictionary from file or API
     *
     * @return array
     */
    protected function loadDictionary(): array
    {
        // For now, we'll use a small set of common English words
        // In a production environment, this would be replaced with a proper dictionary API or file
        $dictionaryPath = storage_path('app/dictionary/english_words.txt');
        
        if (file_exists($dictionaryPath)) {
            $words = file($dictionaryPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            return array_map('strtolower', $words);
        }
        
        // If the file doesn't exist, create a directory and file with some sample words
        if (!file_exists(dirname($dictionaryPath))) {
            mkdir(dirname($dictionaryPath), 0755, true);
        }
        
        // Sample dictionary for development purposes
        // This includes many words that can be formed from common letter combinations
        $sampleDictionary = [
            // Words with common letters a, e, r, t, s, i, n
            'art', 'eat', 'rat', 'sat', 'sit', 'set', 'net', 'ten',
            'tin', 'tan', 'tea', 'sea', 'eat', 'ate', 'are', 'ear',
            'air', 'tie', 'sit', 'its', 'sir', 'rise', 'rain', 'train',
            'stain', 'strain', 'retain', 'remain', 'near', 'tear', 'rate',
            'star', 'stare', 'taser', 'seat', 'east', 'earn', 'learn',
            'neat', 'sane', 'inert', 'insert', 'instead', 'inside', 'raise',
            'arise', 'artist', 'artisan', 'retina', 'stair', 'saint', 'satin',
            
            // Words with common letters a, o, u, t, p, s, d
            'top', 'pot', 'stop', 'spot', 'post', 'tops', 'pots', 'opts',
            'out', 'put', 'up', 'us', 'sat', 'at', 'to', 'do', 'so',
            'pad', 'sad', 'ads', 'spa', 'tap', 'pat', 'apt', 'past',
            'dust', 'stud', 'spud', 'spout', 'pouts', 'stoup', 'doubt',
            'proud', 'spout', 'about', 'scout', 'south', 'mouth', 'youth',
            
            // Words with common letters e, a, c, h, t, r, s
            'cat', 'hat', 'rat', 'sat', 'eat', 'tea', 'sea', 'ace',
            'car', 'arc', 'care', 'race', 'each', 'ache', 'hear', 'here',
            'hare', 'share', 'chase', 'reach', 'teach', 'chart', 'earth',
            'heart', 'search', 'create', 'trace', 'react', 'cater', 'crate',
            
            // Words with common letters o, n, l, i, g, h, t
            'on', 'no', 'in', 'it', 'to', 'go', 'hi', 'oh', 'lot',
            'hot', 'hit', 'lit', 'log', 'hog', 'tog', 'not', 'got',
            'gin', 'tin', 'ton', 'into', 'onto', 'lion', 'light', 'night',
            'tight', 'fight', 'sight', 'thing', 'think', 'thong', 'tong',
            'long', 'loin', 'join', 'joint', 'point', 'going', 'doing',
            
            // Additional common words
            'cat', 'dog', 'bat', 'rat', 'hat', 'mat', 'sat', 'fat',
            'cab', 'tab', 'lab', 'dab', 'fab', 'jab',
            'bad', 'dad', 'had', 'lad', 'mad', 'pad', 'sad',
            'ace', 'face', 'lace', 'mace', 'pace', 'race',
            'back', 'hack', 'jack', 'lack', 'pack', 'rack', 'sack', 'tack',
            'badge', 'cadge', 'hedge', 'ledge', 'sedge', 'wedge',
            'cafe', 'safe', 'life', 'wife', 'knife',
            'cage', 'page', 'rage', 'sage', 'wage',
            'cake', 'bake', 'fake', 'lake', 'make', 'rake', 'sake', 'take', 'wake',
        ];
        
        // Remove duplicates
        $sampleDictionary = array_unique($sampleDictionary);
        
        file_put_contents($dictionaryPath, implode(PHP_EOL, $sampleDictionary));
        
        return $sampleDictionary;
    }
}