<?php

namespace App\Models\Api;

use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    protected $fillable = [
        'external_id',
        'name',
        'shortname',
        'tla',
        'crest',
        'founded',
        'lastUpdated',
        'venue',
    ];

    protected $casts = [
        'lastUpdated' => 'datetime',
    ];

    public function competitions()
    {
        return $this->belongsToMany(Competition::class)->withPivot([
            'standing',
            'points',
            'won',
            'draw',
            'lost',
            'goal_difference',
        ]);
    }

    public function homeGames()
    {
        return $this->hasMany(Game::class, 'home_team_id');
    }

    public function awayGames()
    {
        return $this->hasMany(Game::class, 'away_team_id');
    }
}
