<?php

namespace App\Models\Api;

use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    
    protected $fillable = ['external_id', 'name', 'short_name', 'tla', 'crest', 'founded', 'lastUpdated', 'venue'];    

    public function competitions()
    {
        return $this->belongsToMany(Competition::class);
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
