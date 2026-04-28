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
        Schema::table('playoff_matches', function (Blueprint $table) {
            $table->unsignedTinyInteger('home_score')->nullable()->after('away_team_id');
            $table->unsignedTinyInteger('away_score')->nullable()->after('home_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('playoff_matches', function (Blueprint $table) {
            $table->dropColumn(['home_score', 'away_score']);
        });
    }
};
