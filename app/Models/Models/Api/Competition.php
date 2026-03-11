<?php

namespace App\Models\Models\Api;

use Illuminate\Database\Eloquent\Model;

class Competition extends Model
{
    //
    public function teams()
    {
        return $this->belongsToMany(Team::class);
    }

    public function games()
    {
        return $this->hasMany(Game::class);
    }
}
