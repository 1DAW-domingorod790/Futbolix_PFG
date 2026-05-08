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
        Schema::create('tournament_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('matchday')->nullable();
            $table->dateTime('scheduled_at')->nullable();
            $table->string('venue')->nullable();
            $table->string('status')->default('SCHEDULED');
            $table->foreignId('home_team_id')->constrained('tournament_teams')->cascadeOnDelete();
            $table->foreignId('away_team_id')->constrained('tournament_teams')->cascadeOnDelete();
            $table->unsignedInteger('home_score')->nullable();
            $table->unsignedInteger('away_score')->nullable();
            $table->json('home_scorers')->nullable();
            $table->json('away_scorers')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tournament_matches');
    }
};
