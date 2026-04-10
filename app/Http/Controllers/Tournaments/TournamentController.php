<?php

namespace App\Http\Controllers\Tournaments;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tournaments\StoreTournamentPlayerRequest;
use App\Http\Requests\Tournaments\StoreTournamentMatchRequest;
use App\Http\Requests\Tournaments\StoreTournamentTeamRequest;
use App\Http\Requests\Tournaments\StoreTournamentRequest;
use App\Http\Requests\Tournaments\UpdateTournamentMatchResultRequest;
use App\Http\Requests\Tournaments\UpdateTournamentSettingsRequest;
use App\Models\Tournaments\Tournament;
use App\Models\Tournaments\TournamentMatch;
use App\Models\Tournaments\TournamentPlayer;
use App\Models\Tournaments\TournamentTeam;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class TournamentController extends Controller
{
    public function index(): Response
    {
        $user = auth()->user();

        return Inertia::render('Tournaments/Index', [
            'ownedTournaments' => $this->buildTournamentCards($user
                ->tournaments()
                ->with('admin:id,name')
                ->latest()
                ->get(['id', 'code', 'name', 'description', 'created_at', 'admin_id', 'logo_path', 'is_public'])),
            'publicTournaments' => $this->buildTournamentCards(Tournament::query()
                ->with('admin:id,name')
                ->where('is_public', true)
                ->where('admin_id', '!=', $user->id)
                ->latest()
                ->get(['id', 'code', 'name', 'description', 'created_at', 'admin_id', 'logo_path', 'is_public'])),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Tournaments/Create');
    }

    public function show(Tournament $tournament): Response
    {
        abort_unless($this->canAccessTournament($tournament), 403);

        $tournament->load([
            'teams.players',
            'matches.homeTeam',
            'matches.awayTeam',
            'admin:id,name',
        ]);

        $standings = $this->buildStandings($tournament);
        $topScorers = $this->buildTopScorers($tournament);
        $matches = $this->buildMatches($tournament);
        $teams = $this->buildTeams($tournament);
        $canManage = $this->canManageTournament($tournament);

        return Inertia::render('Tournaments/Show', [
            'tournament' => [
                'id' => $tournament->id,
                'code' => $tournament->code,
                'name' => $tournament->name,
                'logo_url' => $tournament->logo_url,
                'description' => $tournament->description,
                'is_public' => (bool) $tournament->is_public,
                'can_manage' => $canManage,
                'created_at' => $tournament->created_at?->toIso8601String(),
                'admin' => [
                    'id' => $tournament->admin?->id,
                    'name' => $tournament->admin?->name,
                ],
                'summary' => [
                    'teams_count' => $standings->count(),
                    'players_count' => $tournament->teams->sum(fn (TournamentTeam $team) => $team->players->count()),
                    'matches_count' => $matches->count(),
                ],
                'teams' => $teams->values(),
                'standings' => $standings->values(),
                'matches' => $matches->values(),
                'top_scorers' => $topScorers->values(),
            ],
        ]);
    }

    public function store(StoreTournamentRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $tournament = Tournament::create([
            'code' => $this->generateUniqueCode(),
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'logo_path' => $request->file('logo_path')?->store('tournaments', 'public'),
            'is_public' => false,
            'admin_id' => $request->user()->id,
        ]);

        return redirect()
            ->route('tournaments.show', $tournament)
            ->with('success', "El torneo \"{$tournament->name}\" se ha creado correctamente.");
    }

    public function update(UpdateTournamentSettingsRequest $request, Tournament $tournament): RedirectResponse
    {
        abort_unless($this->canManageTournament($tournament), 403);

        $validated = $request->validated();

        if ($request->hasFile('logo_path') && $tournament->logo_path) {
            Storage::disk('public')->delete($tournament->logo_path);
        }

        $tournament->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_public' => $validated['is_public'] ?? false,
            'logo_path' => $request->hasFile('logo_path')
                ? $request->file('logo_path')->store('tournaments', 'public')
                : $tournament->logo_path,
        ]);

        return redirect()
            ->route('tournaments.show', $tournament)
            ->with('success', 'La configuracion del torneo se ha actualizado.');
    }

    public function storeTeam(StoreTournamentTeamRequest $request, Tournament $tournament): RedirectResponse
    {
        abort_unless($this->canManageTournament($tournament), 403);

        $validated = $request->validated();

        TournamentTeam::create([
            'code' => $this->generateUniqueTeamCode(),
            'name' => $validated['name'],
            'tournament_id' => $tournament->id,
            'position' => $tournament->teams()->count() + 1,
        ]);

        return redirect()
            ->route('tournaments.show', $tournament)
            ->with('success', 'El equipo se ha anadido correctamente.');
    }

    public function storeMatch(StoreTournamentMatchRequest $request, Tournament $tournament): RedirectResponse
    {
        abort_unless($this->canManageTournament($tournament), 403);

        $validated = $request->validated();

        $homeTeam = $tournament->teams()->whereKey($validated['home_team_id'])->firstOrFail();
        $awayTeam = $tournament->teams()->whereKey($validated['away_team_id'])->firstOrFail();

        TournamentMatch::create([
            'tournament_id' => $tournament->id,
            'matchday' => $validated['matchday'],
            'scheduled_at' => $validated['scheduled_at'],
            'venue' => $validated['venue'] ?? null,
            'status' => 'SCHEDULED',
            'home_team_id' => $homeTeam->id,
            'away_team_id' => $awayTeam->id,
        ]);

        return redirect()
            ->route('tournaments.show', $tournament)
            ->with('success', 'El partido se ha anadido correctamente.');
    }

    public function storePlayer(
        StoreTournamentPlayerRequest $request,
        Tournament $tournament,
        TournamentTeam $team,
    ): RedirectResponse {
        abort_unless($this->canManageTournament($tournament), 403);
        abort_unless($team->tournament_id === $tournament->id, 404);

        $validated = $request->validated();

        TournamentPlayer::create([
            'dni' => $validated['dni'],
            'name' => $validated['name'],
            'number' => $validated['number'],
            'age' => $validated['age'] ?? null,
            'goals' => $validated['goals'] ?? 0,
            'photo_path' => $request->file('photo_path')?->store('players', 'public'),
            'team_id' => $team->id,
        ]);

        return redirect()
            ->route('tournaments.show', $tournament)
            ->with('success', 'El jugador se ha anadido correctamente.');
    }

    public function updateMatchResult(
        UpdateTournamentMatchResultRequest $request,
        Tournament $tournament,
        TournamentMatch $match,
    ): RedirectResponse {
        abort_unless($this->canManageTournament($tournament), 403);
        abort_unless($match->tournament_id === $tournament->id, 404);

        $validated = $request->validated();

        $match->update([
            'home_score' => $validated['home_score'],
            'away_score' => $validated['away_score'],
            'status' => 'FINISHED',
        ]);

        return redirect()
            ->route('tournaments.show', $tournament)
            ->with('success', 'El resultado del partido se ha guardado.');
    }

    private function generateUniqueCode(): int
    {
        do {
            $code = random_int(100000, 999999);
        } while (Tournament::where('code', $code)->exists());

        return $code;
    }

    private function generateUniqueTeamCode(): int
    {
        do {
            $code = random_int(100000, 999999);
        } while (TournamentTeam::where('code', $code)->exists());

        return $code;
    }

    private function buildStandings(Tournament $tournament): Collection
    {
        return $tournament->teams
            ->sort(function (TournamentTeam $firstTeam, TournamentTeam $secondTeam) {
                $firstPosition = $firstTeam->position ?? PHP_INT_MAX;
                $secondPosition = $secondTeam->position ?? PHP_INT_MAX;

                if ($firstPosition !== $secondPosition) {
                    return $firstPosition <=> $secondPosition;
                }

                if ($firstTeam->points !== $secondTeam->points) {
                    return $secondTeam->points <=> $firstTeam->points;
                }

                if ($firstTeam->goal_difference !== $secondTeam->goal_difference) {
                    return $secondTeam->goal_difference <=> $firstTeam->goal_difference;
                }

                return strcasecmp($firstTeam->name, $secondTeam->name);
            })
            ->map(fn (TournamentTeam $team) => [
                'id' => $team->id,
                'position' => $team->position,
                'name' => $team->name,
                'badge' => $team->badge,
                'played' => $team->played,
                'won' => $team->won,
                'drawn' => $team->drawn,
                'lost' => $team->lost,
                'goals_for' => $team->goals_for,
                'goals_against' => $team->goals_against,
                'goal_difference' => $team->goal_difference,
                'points' => $team->points,
            ]);
    }

    private function buildTeams(Tournament $tournament): Collection
    {
        return $tournament->teams
            ->sort(function (TournamentTeam $firstTeam, TournamentTeam $secondTeam) {
                $firstPosition = $firstTeam->position ?? PHP_INT_MAX;
                $secondPosition = $secondTeam->position ?? PHP_INT_MAX;

                if ($firstPosition !== $secondPosition) {
                    return $firstPosition <=> $secondPosition;
                }

                return strcasecmp($firstTeam->name, $secondTeam->name);
            })
            ->map(fn (TournamentTeam $team) => [
                'id' => $team->id,
                'name' => $team->name,
                'badge' => $team->badge,
                'position' => $team->position,
                'players' => $team->players
                    ->sortBy('number')
                    ->values()
                    ->map(fn (TournamentPlayer $player) => [
                        'id' => $player->id,
                        'name' => $player->name,
                        'number' => $player->number,
                        'age' => $player->age,
                        'goals' => $player->goals ?? 0,
                        'photo_url' => $player->photo_url,
                    ]),
            ]);
    }

    private function buildTopScorers(Tournament $tournament): Collection
    {
        return $tournament->teams
            ->flatMap(function (TournamentTeam $team) {
                return $team->players->map(fn ($player) => [
                    'id' => $player->id,
                    'name' => $player->name,
                    'number' => $player->number,
                    'age' => $player->age,
                    'goals' => $player->goals ?? 0,
                    'photo_url' => $player->photo_url,
                    'team_name' => $team->name,
                    'team_badge' => $team->badge,
                ]);
            })
            ->sort(function (array $firstPlayer, array $secondPlayer) {
                if ($firstPlayer['goals'] !== $secondPlayer['goals']) {
                    return $secondPlayer['goals'] <=> $firstPlayer['goals'];
                }

                return strcasecmp($firstPlayer['name'], $secondPlayer['name']);
            })
            ->take(10);
    }

    private function buildMatches(Tournament $tournament): Collection
    {
        return $tournament->matches
            ->sort(function ($firstMatch, $secondMatch) {
                if ($firstMatch->matchday !== $secondMatch->matchday) {
                    return ($firstMatch->matchday ?? PHP_INT_MAX) <=> ($secondMatch->matchday ?? PHP_INT_MAX);
                }

                return strtotime((string) $firstMatch->scheduled_at) <=> strtotime((string) $secondMatch->scheduled_at);
            })
            ->map(fn ($match) => [
                'id' => $match->id,
                'matchday' => $match->matchday,
                'scheduled_at' => $match->scheduled_at?->toIso8601String(),
                'venue' => $match->venue,
                'status' => $match->status,
                'home_score' => $match->home_score,
                'away_score' => $match->away_score,
                'home_team' => [
                    'id' => $match->homeTeam?->id,
                    'name' => $match->homeTeam?->name,
                    'badge' => $match->homeTeam?->badge,
                ],
                'away_team' => [
                    'id' => $match->awayTeam?->id,
                    'name' => $match->awayTeam?->name,
                    'badge' => $match->awayTeam?->badge,
                ],
            ]);
    }

    private function canManageTournament(Tournament $tournament): bool
    {
        return $tournament->admin_id === auth()->id();
    }

    private function buildTournamentCards(Collection $tournaments): Collection
    {
        return $tournaments->map(fn (Tournament $tournament) => [
            'id' => $tournament->id,
            'code' => $tournament->code,
            'name' => $tournament->name,
            'description' => $tournament->description,
            'created_at' => $tournament->created_at?->toIso8601String(),
            'is_public' => (bool) $tournament->is_public,
            'logo_url' => $tournament->logo_url,
            'admin' => [
                'id' => $tournament->admin?->id,
                'name' => $tournament->admin?->name,
            ],
        ]);
    }

    private function canAccessTournament(Tournament $tournament): bool
    {
        return $this->canManageTournament($tournament) || (bool) $tournament->is_public;
    }
}
