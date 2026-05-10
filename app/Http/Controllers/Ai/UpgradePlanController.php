<?php

namespace App\Http\Controllers\Ai;

use App\Http\Controllers\Controller;
use App\Models\Ai\UserPlan;
use Illuminate\Http\RedirectResponse;

class UpgradePlanController extends Controller
{
    public function __invoke(): RedirectResponse
    {
        $user = auth()->user();

        $plan = $user->aiPlan;

        $plan->update([
            'plan_name' => UserPlan::PLAN_PRO,
            'credits_balance' => 500,
            'monthly_credit_limit' => 500,
        ]);

        return redirect()
            ->back()
            ->with('success', 'Ahora eres usuario PRO.');
    }
}