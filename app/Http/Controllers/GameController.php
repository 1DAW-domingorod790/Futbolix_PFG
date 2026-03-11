<?php

namespace App\Http\Controllers;
use App\Models\Api\Game;
use Inertia\Inertia;
use Illuminate\Http\Request;

class GameController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('Matches/Index', [
            'games' => Game::all(),
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
