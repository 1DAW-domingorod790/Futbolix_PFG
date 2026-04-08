<?php

namespace App\Http\Controllers\Tournaments;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tournaments\StoreTournamentRequest;
use App\Models\Tournaments\Tournament;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TournamentController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Tournaments/Index', [
            'tournaments' => auth()->user()
                ->tournaments()
                ->latest()
                ->get(['id', 'code', 'name', 'description', 'created_at']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Tournaments/Create');
    }

    public function store(StoreTournamentRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $tournament = Tournament::create([
            'code' => $this->generateUniqueCode(),
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'admin_id' => $request->user()->id,
        ]);

        return redirect()
            ->route('tournaments.index')
            ->with('success', "El torneo \"{$tournament->name}\" se ha creado correctamente.");
    }

    private function generateUniqueCode(): int
    {
        do {
            $code = random_int(100000, 999999);
        } while (Tournament::where('code', $code)->exists());

        return $code;
    }
}
