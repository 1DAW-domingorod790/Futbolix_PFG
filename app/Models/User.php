<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use App\Models\Role;
use App\Models\Tournaments\Tournament;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'role_id',
        'password',
        'avatar_path'
    ];

    protected static function booted()
    {

        static::creating(function ($user) {
            if (!$user->role_id){
                $user->role_id = Role::where('name', 'user')->first()->id;
            }
        });
    }

    public function isAdmin(): bool
    {
        return $this->role_id == Role::ADMIN;
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function tournaments(): HasMany
    {
        return $this->hasMany(Tournament::class, 'admin_id');
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    protected $appends = ['avatar_url'];

    public function getAvatarUrlAttribute()
    {
        if (!$this->avatar_path) {
            return null;
        }

        if (str_starts_with($this->avatar_path, 'http')) {
            return $this->avatar_path;
        }

        return Storage::url($this->avatar_path);
    }
}
