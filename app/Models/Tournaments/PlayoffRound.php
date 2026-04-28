<?php

namespace App\Models\Tournaments;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlayoffRound extends Model
{
    protected $fillable = [
        'tournament_id',
        'round_number',
        'name',
        'matches_count',
    ];

    protected function casts(): array
    {
        return [
            'round_number' => 'integer',
            'matches_count' => 'integer',
        ];
    }

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function matches(): HasMany
    {
        return $this->hasMany(PlayoffMatch::class)->orderBy('position');
    }
}
