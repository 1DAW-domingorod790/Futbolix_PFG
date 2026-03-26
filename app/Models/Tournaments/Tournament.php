<?php

namespace App\Models\Tournaments;

use Illuminate\Database\Eloquent\Model;

class Tournament extends Model
{
    protected $fillable = [
        'code',
        'name',
        'admin_id',
    ];
}
