<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'provider',
        'provider_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    
    /**
     * Check if the user has a social login.
     *
     * @return bool
     */
    public function hasSocialLogin(): bool
    {
        return !empty($this->provider) && !empty($this->provider_id);
    }
    
    /**
     * Get the user's game stats.
     */
    public function gameStats()
    {
        return $this->hasMany(UserGameStats::class);
    }
    
    /**
     * Get the user's streaks.
     */
    public function streaks()
    {
        return $this->hasMany(Streak::class);
    }
    
    /**
     * Get the user's badges.
     */
    public function badges()
    {
        return $this->belongsToMany(Badge::class, 'user_badges')
            ->withPivot('awarded_at')
            ->withTimestamps();
    }
    
    /**
     * Get the user's rank.
     */
    public function ranks()
    {
        return $this->belongsToMany(Rank::class, 'user_ranks')
            ->withTimestamps();
    }
    
    /**
     * Get the user's current rank.
     */
    public function currentRank()
    {
        return $this->ranks()->orderByPivot('created_at', 'desc')->first();
    }
}
