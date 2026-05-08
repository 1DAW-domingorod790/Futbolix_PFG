<?php

use App\Enums\Tournaments\TournamentFormat;
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
        Schema::create('tournaments', function (Blueprint $table) {
            $table->id();
            $table->integer('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('format')->default(TournamentFormat::League->value);
            $table->unsignedSmallInteger('playoff_teams_count')->nullable();
            $table->unsignedSmallInteger('groups_count')->nullable();
            $table->unsignedSmallInteger('regular_phase_matchdays_count')->nullable();
            $table->unsignedSmallInteger('current_matchday')->nullable();
            $table->timestamp('playoff_bracket_generated_at')->nullable();
            $table->string('logo_path')->nullable();
            $table->boolean('is_public')->default(false);
            $table->foreignId('admin_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tournaments');
    }
};
