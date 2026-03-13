<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Api\Competition;
use App\Models\Api\Game;
use Inertia\Inertia;

class GameController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('Api/Games', [
            'games' => Game::query()
                ->with(['competition', 'homeTeam', 'awayTeam'])
                ->orderBy('utc_date')
                ->get(),
            'competitions' => Competition::query()
                ->orderBy('id')
                ->get(),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }
}
