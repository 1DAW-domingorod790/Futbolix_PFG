<?php

namespace App\Models\Tournaments;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Tournament extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'admin_id',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function teams(): HasMany
    {
        return $this->hasMany(TournamentTeam::class);
    }
}
