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
        // Add indexes for leaderboards table
        Schema::table('leaderboards', function (Blueprint $table) {
            $table->index(['game_id', 'period_type', 'period_date', 'score'], 'leaderboards_game_period_score_idx');
            $table->index(['user_id', 'game_id', 'period_type'], 'leaderboards_user_game_period_idx');
            $table->index(['period_date', 'period_type'], 'leaderboards_period_idx');
        });

        // Add indexes for streaks table
        Schema::table('streaks', function (Blueprint $table) {
            $table->index(['user_id', 'game_id'], 'streaks_user_game_idx');
            $table->index(['last_played_date'], 'streaks_last_played_idx');
            $table->index(['current_streak'], 'streaks_current_idx');
            $table->index(['longest_streak'], 'streaks_longest_idx');
        });

        // Add indexes for user_game_stats table
        Schema::table('user_game_stats', function (Blueprint $table) {
            $table->index(['user_id', 'game_id'], 'user_game_stats_user_game_idx');
            $table->index(['last_played_at'], 'user_game_stats_last_played_idx');
            $table->index(['total_score'], 'user_game_stats_score_idx');
        });

        // Add indexes for word_scramble_submissions table
        Schema::table('word_scramble_submissions', function (Blueprint $table) {
            $table->index(['user_id', 'puzzle_id'], 'word_scramble_submissions_user_puzzle_idx');
            $table->index(['puzzle_id', 'word'], 'word_scramble_submissions_puzzle_word_idx');
            $table->index(['created_at'], 'word_scramble_submissions_created_idx');
        });

        // Add indexes for word_scramble_puzzles table
        Schema::table('word_scramble_puzzles', function (Blueprint $table) {
            $table->index(['date'], 'word_scramble_puzzles_date_idx');
        });

        // Add indexes for word_scramble_words table
        Schema::table('word_scramble_words', function (Blueprint $table) {
            $table->index(['puzzle_id'], 'word_scramble_words_puzzle_idx');
            $table->index(['word'], 'word_scramble_words_word_idx');
            $table->index(['score'], 'word_scramble_words_score_idx');
        });

        // Add indexes for guest_data table
        Schema::table('guest_data', function (Blueprint $table) {
            $table->index(['guest_id', 'key'], 'guest_data_guest_key_idx');
        });

        // Add indexes for users table
        Schema::table('users', function (Blueprint $table) {
            $table->index(['provider', 'provider_id'], 'users_provider_idx');
            $table->index(['created_at'], 'users_created_idx');
        });

        // Add indexes for badges and user_badges tables if they exist
        if (Schema::hasTable('user_badges')) {
            Schema::table('user_badges', function (Blueprint $table) {
                $table->index(['user_id'], 'user_badges_user_idx');
                $table->index(['badge_id'], 'user_badges_badge_idx');
                $table->index(['awarded_at'], 'user_badges_awarded_idx');
            });
        }

        if (Schema::hasTable('user_ranks')) {
            Schema::table('user_ranks', function (Blueprint $table) {
                $table->index(['user_id'], 'user_ranks_user_idx');
                $table->index(['rank_id'], 'user_ranks_rank_idx');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes for leaderboards table
        Schema::table('leaderboards', function (Blueprint $table) {
            $table->dropIndex('leaderboards_game_period_score_idx');
            $table->dropIndex('leaderboards_user_game_period_idx');
            $table->dropIndex('leaderboards_period_idx');
        });

        // Drop indexes for streaks table
        Schema::table('streaks', function (Blueprint $table) {
            $table->dropIndex('streaks_user_game_idx');
            $table->dropIndex('streaks_last_played_idx');
            $table->dropIndex('streaks_current_idx');
            $table->dropIndex('streaks_longest_idx');
        });

        // Drop indexes for user_game_stats table
        Schema::table('user_game_stats', function (Blueprint $table) {
            $table->dropIndex('user_game_stats_user_game_idx');
            $table->dropIndex('user_game_stats_last_played_idx');
            $table->dropIndex('user_game_stats_score_idx');
        });

        // Drop indexes for word_scramble_submissions table
        Schema::table('word_scramble_submissions', function (Blueprint $table) {
            $table->dropIndex('word_scramble_submissions_user_puzzle_idx');
            $table->dropIndex('word_scramble_submissions_puzzle_word_idx');
            $table->dropIndex('word_scramble_submissions_created_idx');
        });

        // Drop indexes for word_scramble_puzzles table
        Schema::table('word_scramble_puzzles', function (Blueprint $table) {
            $table->dropIndex('word_scramble_puzzles_date_idx');
        });

        // Drop indexes for word_scramble_words table
        Schema::table('word_scramble_words', function (Blueprint $table) {
            $table->dropIndex('word_scramble_words_puzzle_idx');
            $table->dropIndex('word_scramble_words_word_idx');
            $table->dropIndex('word_scramble_words_score_idx');
        });

        // Drop indexes for guest_data table
        Schema::table('guest_data', function (Blueprint $table) {
            $table->dropIndex('guest_data_guest_key_idx');
        });

        // Drop indexes for users table
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_provider_idx');
            $table->dropIndex('users_created_idx');
        });

        // Drop indexes for badges and user_badges tables if they exist
        if (Schema::hasTable('user_badges')) {
            Schema::table('user_badges', function (Blueprint $table) {
                $table->dropIndex('user_badges_user_idx');
                $table->dropIndex('user_badges_badge_idx');
                $table->dropIndex('user_badges_awarded_idx');
            });
        }

        if (Schema::hasTable('user_ranks')) {
            Schema::table('user_ranks', function (Blueprint $table) {
                $table->dropIndex('user_ranks_user_idx');
                $table->dropIndex('user_ranks_rank_idx');
            });
        }
    }
};
