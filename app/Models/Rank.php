<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Rank extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'threshold',
        'icon',
    ];

    /**
     * Get the users that have this rank.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_ranks')
            ->withTimestamps();
    }
}
