<?php

namespace App\Models\Api;

use Illuminate\Database\Eloquent\Model;

class Competition extends Model
{
    protected $fillable = [
        'external_id',
        'name',
        'code',
        'type',
        'emblem',
        'startDate',
        'endDate',
        'lastUpdated',
        'currentMatchDay',
    ];

    protected $casts = [
        'startDate' => 'date',
        'endDate' => 'date',
    ];

    public function teams()
    {
        return $this->belongsToMany(Team::class)->withPivot([
            'standing',
            'points',
            'won',
            'draw',
            'lost',
            'goal_difference',
        ]);
    }

    public function games()
    {
        return $this->hasMany(Game::class);
    }
}
