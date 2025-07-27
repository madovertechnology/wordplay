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
        Schema::create('word_scramble_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('puzzle_id')->constrained('word_scramble_puzzles')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('guest_id')->nullable();
            $table->string('word');
            $table->unsignedInteger('score');
            $table->timestamps();
            
            // Ensure a user can only submit a word once per puzzle
            $table->unique(['puzzle_id', 'user_id', 'word'], 'unique_user_submission');
            
            // Ensure a guest can only submit a word once per puzzle
            $table->unique(['puzzle_id', 'guest_id', 'word'], 'unique_guest_submission');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('word_scramble_submissions');
    }
};
