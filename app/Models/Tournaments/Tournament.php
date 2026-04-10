<?php

namespace App\Models\Tournaments;

use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Tournament extends Model
{
    protected $appends = ['logo_url'];

    protected $fillable = [
        'code',
        'name',
        'description',
        'admin_id',
        'logo_path',
        'is_public',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function teams(): HasMany
    {
        return $this->hasMany(TournamentTeam::class);
    }

    public function matches(): HasMany
    {
        return $this->hasMany(TournamentMatch::class);
    }

    public function getLogoUrlAttribute(): string
    {
        if (!$this->logo_path) {
            return asset('tournament-avatar.png');
        }

        if (str_starts_with($this->logo_path, 'http')) {
            return $this->logo_path;
        }

        return Storage::url($this->logo_path);
    }
}
