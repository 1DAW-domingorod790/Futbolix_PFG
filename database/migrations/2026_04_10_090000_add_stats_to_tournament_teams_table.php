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
        Schema::table('tournament_teams', function (Blueprint $table) {
            $table->unsignedInteger('position')->nullable()->after('tournament_id');
            $table->unsignedInteger('played')->default(0)->after('position');
            $table->unsignedInteger('won')->default(0)->after('played');
            $table->unsignedInteger('drawn')->default(0)->after('won');
            $table->unsignedInteger('lost')->default(0)->after('drawn');
            $table->unsignedInteger('goals_for')->default(0)->after('lost');
            $table->unsignedInteger('goals_against')->default(0)->after('goals_for');
            $table->integer('goal_difference')->default(0)->after('goals_against');
            $table->unsignedInteger('points')->default(0)->after('goal_difference');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tournament_teams', function (Blueprint $table) {
            $table->dropColumn([
                'position',
                'played',
                'won',
                'drawn',
                'lost',
                'goals_for',
                'goals_against',
                'goal_difference',
                'points',
            ]);
        });
    }
};
