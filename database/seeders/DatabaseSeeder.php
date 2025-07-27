<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create the main test user
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
        
        // Seed all data in the correct order
        $this->call([
            GameSeeder::class,
            BadgeSeeder::class,
            WordScrambleSeeder::class,
            UserDataSeeder::class,
        ]);
        
        $this->command->info('Database seeded successfully with comprehensive test data!');
        $this->command->info('You can now test all routes and functionality.');
        $this->command->info('Login with: test@example.com / password');
    }
}
