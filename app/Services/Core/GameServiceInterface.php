<?php

namespace App\Services\Core;

use App\Models\Game;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface GameServiceInterface
{
    /**
     * Get all games.
     *
     * @return Collection
     */
    public function getAllGames(): Collection;
    
    /**
     * Get all active games.
     *
     * @return Collection
     */
    public function getActiveGames(): Collection;
    
    /**
     * Get a game by its slug.
     *
     * @param string $slug
     * @return Game|null
     */
    public function getGameBySlug(string $slug): ?Game;
    
    /**
     * Get a game by its ID.
     *
     * @param int $id
     * @return Game|null
     */
    public function getGameById(int $id): ?Game;
    
    /**
     * Toggle the active status of a game.
     *
     * @param int $id
     * @return Game
     */
    public function toggleGameActive(int $id): Game;
    
    /**
     * Get user stats for a game.
     *
     * @param User $user
     * @param Game $game
     * @return array
     */
    public function getUserGameStats(User $user, Game $game): array;
    
    /**
     * Get user streak for a game.
     *
     * @param User $user
     * @param Game $game
     * @return array
     */
    public function getUserGameStreak(User $user, Game $game): array;
    
    /**
     * Get daily leaderboard for a game.
     *
     * @param Game $game
     * @param int $limit
     * @return array
     */
    public function getDailyLeaderboard(Game $game, int $limit = 10): array;
    
    /**
     * Get monthly leaderboard for a game.
     *
     * @param Game $game
     * @param int $limit
     * @return array
     */
    public function getMonthlyLeaderboard(Game $game, int $limit = 10): array;
    
    /**
     * Get all-time leaderboard for a game.
     *
     * @param Game $game
     * @param int $limit
     * @return array
     */
    public function getAllTimeLeaderboard(Game $game, int $limit = 10): array;
}