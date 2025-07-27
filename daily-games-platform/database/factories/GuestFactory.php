<?php

namespace Database\Factories;

use App\Models\Guest;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Guest>
 */
class GuestFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Guest::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'guest_token' => Str::uuid(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Create a guest with an expired token.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => now()->subDays(31),
            'updated_at' => now()->subDays(31),
        ]);
    }
}