<?php

namespace App\Models\Tournaments;

use Illuminate\Database\Eloquent\Model;

class TournamentTeam extends Model
{
    protected $fillable = [
        'code',
        'name',
        'badge',
        'tournament_id',
    ];
}
