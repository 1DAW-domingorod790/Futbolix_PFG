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
        Schema::table('tournaments', function (Blueprint $table) {
            $table->unsignedSmallInteger('regular_phase_matchdays_count')->nullable()->after('groups_count');
            $table->unsignedSmallInteger('current_matchday')->nullable()->after('regular_phase_matchdays_count');
            $table->timestamp('playoff_bracket_generated_at')->nullable()->after('current_matchday');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {
            $table->dropColumn([
                'regular_phase_matchdays_count',
                'current_matchday',
                'playoff_bracket_generated_at',
            ]);
        });
    }
};
