<?php

namespace App\Models\Ai;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPlan extends Model
{
    public const PLAN_FREE = 'free';
    public const PLAN_PRO = 'pro';

    protected $fillable = [
        'user_id',
        'plan_name',
        'credits_balance',
        'monthly_credit_limit',
        'renewal_date',
    ];

    protected function casts(): array
    {
        return [
            'credits_balance' => 'integer',
            'monthly_credit_limit' => 'integer',
            'renewal_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
