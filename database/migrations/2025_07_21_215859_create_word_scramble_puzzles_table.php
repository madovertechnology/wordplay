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
        Schema::create('word_scramble_puzzles', function (Blueprint $table) {
            $table->id();
            $table->string('letters');
            $table->date('date')->unique();
            $table->unsignedInteger('possible_words_count')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('word_scramble_puzzles');
    }
};
