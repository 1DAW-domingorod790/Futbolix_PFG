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
        Schema::create('competition_team', function (Blueprint $table) {

            $table->id();

            $table->foreignId('competition_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('team_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->unsignedInteger('standing')->nullable();
            $table->unsignedInteger('points')->nullable();
            $table->unsignedInteger('won')->nullable();
            $table->unsignedInteger('draw')->nullable();
            $table->unsignedInteger('lost')->nullable();
            $table->integer('goal_difference')->nullable();

            $table->unique(['competition_id','team_id']);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('competition_team');
    }
};
