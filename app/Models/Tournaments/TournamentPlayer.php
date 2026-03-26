<?php

namespace App\Models\Tournaments;

use Illuminate\Database\Eloquent\Model;

class TournamentPlayer extends Model
{
    protected $fillable = [
        'code',
        'name',
        'badge',
        'tournament_id',
    ];
}
