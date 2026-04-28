<?php

namespace App\Models\Tournaments;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayoffMatch extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_READY = 'ready';

    public const STATUS_FINISHED = 'finished';

    protected $fillable = [
        'tournament_id',
        'playoff_round_id',
        'round_number',
        'position',
        'home_team_id',
        'away_team_id',
        'home_score',
        'away_score',
        'winner_team_id',
        'next_match_id',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'round_number' => 'integer',
            'position' => 'integer',
            'home_score' => 'integer',
            'away_score' => 'integer',
        ];
    }

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function round(): BelongsTo
    {
        return $this->belongsTo(PlayoffRound::class, 'playoff_round_id');
    }

    public function homeTeam(): BelongsTo
    {
        return $this->belongsTo(TournamentTeam::class, 'home_team_id');
    }

    public function awayTeam(): BelongsTo
    {
        return $this->belongsTo(TournamentTeam::class, 'away_team_id');
    }

    public function winnerTeam(): BelongsTo
    {
        return $this->belongsTo(TournamentTeam::class, 'winner_team_id');
    }

    public function nextMatch(): BelongsTo
    {
        return $this->belongsTo(self::class, 'next_match_id');
    }
}
