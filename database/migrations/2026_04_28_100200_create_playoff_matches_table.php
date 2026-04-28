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
        Schema::create('playoff_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained('tournaments')->cascadeOnDelete();
            $table->foreignId('playoff_round_id')->constrained('playoff_rounds')->cascadeOnDelete();
            $table->unsignedSmallInteger('round_number');
            $table->unsignedSmallInteger('position');
            $table->foreignId('home_team_id')->nullable()->constrained('tournament_teams')->nullOnDelete();
            $table->foreignId('away_team_id')->nullable()->constrained('tournament_teams')->nullOnDelete();
            $table->foreignId('winner_team_id')->nullable()->constrained('tournament_teams')->nullOnDelete();
            $table->foreignId('next_match_id')->nullable()->constrained('playoff_matches')->nullOnDelete();
            $table->string('status')->default('pending');
            $table->timestamps();

            $table->unique(['tournament_id', 'round_number', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('playoff_matches');
    }
};
