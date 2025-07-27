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
        Schema::create('word_scramble_words', function (Blueprint $table) {
            $table->id();
            $table->foreignId('puzzle_id')->constrained('word_scramble_puzzles')->onDelete('cascade');
            $table->string('word');
            $table->unsignedInteger('score');
            $table->timestamps();
            
            $table->unique(['puzzle_id', 'word']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('word_scramble_words');
    }
};
