<?php

namespace App\Services\Ai;

use App\Models\Ai\UserPlan;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

class AiCreditService
{
    public function planFor(User $user): UserPlan
    {
        $plan = $user->aiPlan;

        if (!$plan) {
            $plan = $this->createDefaultPlan($user);
            $user->setRelation('aiPlan', $plan);
        }

        if ($plan->renewal_date && $plan->renewal_date->isPast()) {
            $plan = $this->renew($plan);
            $user->setRelation('aiPlan', $plan);
        }

        return $plan;
    }

    public function assertHasCredits(UserPlan $plan, int $estimatedCredits): void
    {
        if ($plan->credits_balance < $estimatedCredits) {
            throw ValidationException::withMessages([
                'credits' => 'No tienes creditos suficientes para enviar este mensaje.',
            ]);
        }
    }

    public function estimateCreditsForMessage(string $content): int
    {
        $estimatedTokens = max(
            (int) ceil(mb_strlen($content) / 4),
            (int) config('futbolix_ai.estimated_tokens_per_message', 600),
        );

        return $this->creditsForTokens($estimatedTokens);
    }

    public function creditsForTokens(?int $totalTokens): int
    {
        $tokens = max(1, (int) $totalTokens);
        $tokensPerCredit = max(1, (int) config('futbolix_ai.tokens_per_credit', 750));
        $minimum = max(1, (int) config('futbolix_ai.minimum_credits_per_message', 1));
        $maximum = max($minimum, (int) config('futbolix_ai.maximum_credits_per_message', 5));

        return min($maximum, max($minimum, (int) ceil($tokens / $tokensPerCredit)));
    }

    public function deduct(UserPlan $plan, int $credits): UserPlan
    {
        $credits = max(0, $credits);

        if ($plan->credits_balance < $credits) {
            throw ValidationException::withMessages([
                'credits' => 'No tienes creditos suficientes para completar esta respuesta.',
            ]);
        }

        $plan->decrement('credits_balance', $credits);

        return $plan->fresh();
    }

    private function createDefaultPlan(User $user): UserPlan
    {
        $limit = (int) config('futbolix_ai.plans.free.monthly_credit_limit', 50);

        return UserPlan::create([
            'user_id' => $user->id,
            'plan_name' => UserPlan::PLAN_FREE,
            'credits_balance' => $limit,
            'monthly_credit_limit' => $limit,
            'renewal_date' => CarbonImmutable::now()->addMonth()->toDateString(),
        ]);
    }

    private function renew(UserPlan $plan): UserPlan
    {
        $limit = (int) config("futbolix_ai.plans.{$plan->plan_name}.monthly_credit_limit", $plan->monthly_credit_limit);

        $plan->update([
            'credits_balance' => $limit,
            'monthly_credit_limit' => $limit,
            'renewal_date' => CarbonImmutable::now()->addMonth()->toDateString(),
        ]);

        return $plan->fresh();
    }
}
