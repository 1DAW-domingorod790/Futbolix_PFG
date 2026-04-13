<?php

namespace App\Models\Tournaments;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class TournamentTeam extends Model
{
    protected $appends = ['badge_url'];

    protected $fillable = [
        'code',
        'name',
        'badge',
        'tournament_id',
        'position',
        'played',
        'won',
        'drawn',
        'lost',
        'goals_for',
        'goals_against',
        'goal_difference',
        'points',
    ];

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function players(): HasMany
    {
        return $this->hasMany(TournamentPlayer::class, 'team_id');
    }

    public function homeMatches(): HasMany
    {
        return $this->hasMany(TournamentMatch::class, 'home_team_id');
    }

    public function awayMatches(): HasMany
    {
        return $this->hasMany(TournamentMatch::class, 'away_team_id');
    }

    public function getBadgeUrlAttribute(): string
    {
        if (!$this->badge) {
            return asset('tournament-avatar.png');
        }

        if (str_starts_with($this->badge, 'http')) {
            return $this->badge;
        }

        return Storage::url($this->badge);
    }
}
