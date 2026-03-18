<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Api\Competition;
use App\Models\Api\Game;
use Inertia\Inertia;

class CompetitionController extends Controller
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
                ->orderBy('name')
                ->get(),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $competition = Competition::query()
            ->with([
                'teams' => fn ($query) => $query->orderBy('name'),
                'games' => fn ($query) => $query
                    ->with(['homeTeam', 'awayTeam'])
                    ->orderBy('matchday')
                    ->orderBy('utc_date'),
            ])
            ->withCount(['teams', 'games'])
            ->findOrFail($id);

        return Inertia::render('Api/CompetitionShow', [
            'competition' => $competition,
        ]);
    }
}
