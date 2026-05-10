<?php

namespace App\Http\Controllers\Ai;

use App\Http\Controllers\Controller;
use App\Services\Ai\AiCreditService;
use Inertia\Inertia;
use Inertia\Response;

class AiPlanPageController extends Controller
{
    public function __invoke(AiCreditService $credits): Response
    {
        $plan = $credits->planFor(auth()->user());

        return Inertia::render('FutbolixAi/Plans', [
            'currentPlan' => [
                'plan_name' => $plan->plan_name,
                'credits_balance' => $plan->credits_balance,
                'monthly_credit_limit' => $plan->monthly_credit_limit,
            ],
        ]);
    }
}