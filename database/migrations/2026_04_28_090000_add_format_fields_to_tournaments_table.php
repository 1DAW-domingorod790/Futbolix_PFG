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
        Schema::table('tournaments', function (Blueprint $table) {
            $table->string('format')->default(TournamentFormat::League->value)->after('description');
            $table->unsignedSmallInteger('playoff_teams_count')->nullable()->after('format');
            $table->unsignedSmallInteger('groups_count')->nullable()->after('playoff_teams_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {
            $table->dropColumn(['format', 'playoff_teams_count', 'groups_count']);
        });
    }
};
