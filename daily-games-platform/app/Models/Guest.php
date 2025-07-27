<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Guest extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'guest_token',
    ];

    /**
     * Get the guest data associated with the guest.
     */
    public function data(): HasMany
    {
        return $this->hasMany(GuestData::class);
    }

    /**
     * Get the word scramble submissions for the guest.
     */
    public function wordScrambleSubmissions(): HasMany
    {
        return $this->hasMany(WordScrambleSubmission::class, 'guest_id', 'guest_token');
    }

    /**
     * Store data for the guest.
     *
     * @param string $key
     * @param mixed $value
     * @return GuestData
     */
    public function storeData(string $key, $value): GuestData
    {
        return $this->data()->updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }

    /**
     * Get data for the guest.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getData(string $key, $default = null)
    {
        $data = $this->data()->where('key', $key)->first();
        
        return $data ? $data->value : $default;
    }
}
