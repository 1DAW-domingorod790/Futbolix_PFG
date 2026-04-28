<?php

namespace App\Models\Tournaments;

use App\Enums\Tournaments\TournamentFormat;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Tournament extends Model
{
    protected $appends = ['logo_url'];

    protected $fillable = [
        'code',
        'name',
        'description',
        'format',
        'playoff_teams_count',
        'groups_count',
        'regular_phase_matchdays_count',
        'current_matchday',
        'playoff_bracket_generated_at',
        'admin_id',
        'logo_path',
        'is_public',
    ];

    protected function casts(): array
    {
        return [
            'format' => TournamentFormat::class,
            'playoff_teams_count' => 'integer',
            'groups_count' => 'integer',
            'regular_phase_matchdays_count' => 'integer',
            'current_matchday' => 'integer',
            'playoff_bracket_generated_at' => 'datetime',
            'is_public' => 'boolean',
        ];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function teams(): HasMany
    {
        return $this->hasMany(TournamentTeam::class);
    }

    public function matches(): HasMany
    {
        return $this->hasMany(TournamentMatch::class);
    }

    public function playoffRounds(): HasMany
    {
        return $this->hasMany(PlayoffRound::class)->orderBy('round_number');
    }

    public function playoffMatches(): HasMany
    {
        return $this->hasMany(PlayoffMatch::class)->orderBy('round_number')->orderBy('position');
    }

    public function hasPlayoffs(): bool
    {
        return $this->format?->hasPlayoffs() ?? false;
    }

    public function hasRegularPhaseBeforePlayoffs(): bool
    {
        return $this->format?->hasRegularPhase() ?? false;
    }

    public function getLogoUrlAttribute(): string
    {
        if (!$this->logo_path) {
            return asset('tournament-avatar.png');
        }

        if (str_starts_with($this->logo_path, 'http')) {
            return $this->logo_path;
        }

        return Storage::url($this->logo_path);
    }
}
