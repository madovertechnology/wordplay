<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('leaderboards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('score');
            $table->enum('period_type', ['daily', 'monthly', 'all_time']);
            $table->date('period_date')->nullable();
            $table->timestamps();
            
            // Composite index for efficient leaderboard queries
            $table->index(['game_id', 'period_type', 'period_date', 'score']);
            
            // Ensure a user can only have one entry per game per period
            $table->unique(['game_id', 'user_id', 'period_type', 'period_date'], 'unique_leaderboard_entry');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leaderboards');
    }
};
