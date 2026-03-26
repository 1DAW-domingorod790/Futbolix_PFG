<?php

namespace App\Models\Tournaments;

use Illuminate\Database\Eloquent\Model;

class TournamentTeam extends Model
{
    protected $fillable = [
        'dni',
        'name',
        'number',
        'age',
        'team_id',
    ];
}
