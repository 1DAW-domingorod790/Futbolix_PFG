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
        Schema::table('competition_team', function (Blueprint $table) {
            $table->unsignedInteger('points')->nullable()->after('standing');
            $table->unsignedInteger('won')->nullable()->after('points');
            $table->unsignedInteger('draw')->nullable()->after('won');
            $table->unsignedInteger('lost')->nullable()->after('draw');
            $table->integer('goal_difference')->nullable()->after('lost');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('competition_team', function (Blueprint $table) {
            $table->dropColumn([
                'points',
                'won',
                'draw',
                'lost',
                'goal_difference',
            ]);
        });
    }
};
