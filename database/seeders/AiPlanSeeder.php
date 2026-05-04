<?php

namespace Database\Seeders;

use App\Models\Ai\UserPlan;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

class AiPlanSeeder extends Seeder
{
    public function run(): void
    {
        $limit = (int) config('futbolix_ai.plans.free.monthly_credit_limit', 50);

        User::query()->each(function (User $user) use ($limit) {
            UserPlan::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'plan_name' => UserPlan::PLAN_FREE,
                    'credits_balance' => $limit,
                    'monthly_credit_limit' => $limit,
                    'renewal_date' => CarbonImmutable::now()->addMonth()->toDateString(),
                ],
            );
        });
    }
}
