<?php

namespace App\Http\Controllers\Ai;

use App\Http\Controllers\Controller;
use App\Services\Ai\AiCreditService;
use Inertia\Inertia;
use Inertia\Response;

class FutbolixAiPageController extends Controller
{
    public function __invoke(AiCreditService $credits): Response
    {
        $user = auth()->user();
        $plan = $credits->planFor($user);

        return Inertia::render('FutbolixAi/Index', [
            'initialConversations' => $user->aiConversations()
                ->withCount('messages')
                ->latest('updated_at')
                ->get()
                ->map(fn ($conversation) => [
                    'id' => $conversation->id,
                    'title' => $conversation->title,
                    'messages_count' => $conversation->messages_count,
                    'updated_at' => $conversation->updated_at?->toIso8601String(),
                ]),
            'plan' => $this->serializePlan($plan),
            'creditRules' => [
                'tokens_per_credit' => (int) config('futbolix_ai.tokens_per_credit'),
                'minimum_credits_per_message' => (int) config('futbolix_ai.minimum_credits_per_message'),
                'estimated_tokens_per_message' => (int) config('futbolix_ai.estimated_tokens_per_message'),
            ],
        ]);
    }

    private function serializePlan($plan): array
    {
        return [
            'plan_name' => $plan->plan_name,
            'credits_balance' => $plan->credits_balance,
            'monthly_credit_limit' => $plan->monthly_credit_limit,
            'renewal_date' => $plan->renewal_date?->toDateString(),
        ];
    }
}
