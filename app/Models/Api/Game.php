<?php

namespace App\Models\Api;

use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    protected $fillable = [
        'external_id',
        'competition_id',
        'home_team_id',
        'away_team_id',
        'matchday',
        'stage',
        'home_score',
        'away_score',
        'utc_date',
        'status',
    ];

    protected $casts = [
        'matchday' => 'integer',
        'utc_date' => 'datetime',
    ];

    public function competition()
    {
        return $this->belongsTo(Competition::class);
    }

    public function homeTeam()
    {
        return $this->belongsTo(Team::class, 'home_team_id');
    }

    public function awayTeam()
    {
        return $this->belongsTo(Team::class, 'away_team_id');
    }
}
