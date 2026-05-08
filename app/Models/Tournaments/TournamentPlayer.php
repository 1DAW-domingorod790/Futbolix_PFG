<?php

namespace App\Models\Tournaments;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class TournamentPlayer extends Model
{
    protected $appends = ['photo_url'];

    protected $fillable = [
        'dni',
        'name',
        'number',
        'birth_date',
        'photo_path',
        'team_id',
        'goals',
    ];

    protected $casts = [
        'birth_date' => 'date',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(TournamentTeam::class, 'team_id');
    }

    public function getPhotoUrlAttribute(): string
    {
        if (!$this->photo_path) {
            return asset('player-avatar.png');
        }

        if (str_starts_with($this->photo_path, 'http')) {
            return $this->photo_path;
        }

        return Storage::url($this->photo_path);
    }
}
