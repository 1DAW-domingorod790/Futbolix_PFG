<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->enum('plan_name', ['free', 'pro'])->default('free');
            $table->unsignedInteger('credits_balance')->default(0);
            $table->unsignedInteger('monthly_credit_limit')->default(0);
            $table->date('renewal_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_plans');
    }
};
