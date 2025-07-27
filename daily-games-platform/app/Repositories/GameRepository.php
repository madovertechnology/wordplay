<?php

namespace App\Repositories;

use App\Models\Game;

class GameRepository extends BaseRepository
{
    /**
     * GameRepository constructor.
     *
     * @param Game $model
     */
    public function __construct(Game $model)
    {
        parent::__construct($model);
    }

    /**
     * Get all active games.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllActive()
    {
        return $this->model->where('is_active', true)->get();
    }

    /**
     * Find a game by its slug.
     *
     * @param string $slug
     * @return Game|null
     */
    public function findBySlug(string $slug)
    {
        return $this->model->where('slug', $slug)->first();
    }

    /**
     * Toggle the active status of a game.
     *
     * @param int $id
     * @return Game
     */
    public function toggleActive(int $id)
    {
        $game = $this->find($id);
        $game->is_active = !$game->is_active;
        $game->save();
        
        return $game;
    }
}