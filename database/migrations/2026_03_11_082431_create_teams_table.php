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
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->integer('external_id')->unique();
            $table->string('name');
            $table->string('shortname')->nullable();
            $table->string('tla')->nullable();
            $table->string('crest')->nullable();
            $table->integer('founded')->nullable();
            $table->string('venue')->nullable();
            $table->timestamp('lastUpdated')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};
