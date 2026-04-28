<?php

namespace App\Http\Controllers\Tournaments;

use App\Enums\Tournaments\TournamentFormat;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tournaments\StoreTournamentPlayerRequest;
use App\Http\Requests\Tournaments\StoreTournamentMatchRequest;
use App\Http\Requests\Tournaments\StoreTournamentTeamRequest;
use App\Http\Requests\Tournaments\StoreTournamentRequest;
use App\Http\Requests\Tournaments\UpdateTournamentMatchResultRequest;
use App\Http\Requests\Tournaments\UpdateTournamentSettingsRequest;
use App\Http\Requests\Tournaments\UpdateTournamentTeamRequest;
use App\Models\Tournaments\Tournament;
use App\Models\Tournaments\TournamentMatch;
use App\Models\Tournaments\TournamentPlayer;
use App\Models\Tournaments\TournamentTeam;
use App\Services\Tournaments\PlayoffBracketService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

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
                ->get([
                    'id',
                    'code',
                    'name',
                    'description',
                    'format',
                    'playoff_teams_count',
                    'groups_count',
                    'regular_phase_matchdays_count',
                    'current_matchday',
                    'playoff_bracket_generated_at',
                    'created_at',
                    'admin_id',
                    'logo_path',
                    'is_public',
                ])),
            'publicTournaments' => $this->buildTournamentCards(Tournament::query()
                ->with('admin:id,name')
                ->where('is_public', true)
                ->where('admin_id', '!=', $user->id)
                ->latest()
                ->get([
                    'id',
                    'code',
                    'name',
                    'description',
                    'format',
                    'playoff_teams_count',
                    'groups_count',
                    'regular_phase_matchdays_count',
                    'current_matchday',
                    'playoff_bracket_generated_at',
                    'created_at',
                    'admin_id',
                    'logo_path',
                    'is_public',
                ])),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Tournaments/Create', [
            'formatOptions' => TournamentFormat::options(),
        ]);
    }

    public function show(Tournament $tournament, PlayoffBracketService $playoffBracketService): Response
    {
        abort_unless($this->canAccessTournament($tournament), 403);

        $tournament->load([
            'teams.players',
            'matches.homeTeam',
            'matches.homeTeam.players',
            'matches.awayTeam',
            'matches.awayTeam.players',
            'admin:id,name',
            'playoffRounds.matches.homeTeam',
            'playoffRounds.matches.awayTeam',
            'playoffRounds.matches.winnerTeam',
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
                'format' => $this->serializeTournamentFormat($tournament),
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
                'playoffs' => $this->serializePlayoffs($tournament, $playoffBracketService),
            ],
        ]);
    }

    public function showTeam(Tournament $tournament, TournamentTeam $team): Response
    {
        abort_unless($this->canAccessTournament($tournament), 403);
        abort_unless($team->tournament_id === $tournament->id, 404);

        $team->load([
            'players',
            'homeMatches.homeTeam',
            'homeMatches.awayTeam',
            'awayMatches.homeTeam',
            'awayMatches.awayTeam',
            'tournament:id,name,code',
        ]);

        $canManage = $this->canManageTournament($tournament);
        $recentMatches = $team->homeMatches
            ->concat($team->awayMatches)
            ->sort(function (TournamentMatch $firstMatch, TournamentMatch $secondMatch) {
                return strtotime((string) $secondMatch->scheduled_at) <=> strtotime((string) $firstMatch->scheduled_at);
            })
            ->take(5)
            ->values()
            ->map(fn (TournamentMatch $match) => [
                'id' => $match->id,
                'scheduled_at' => $match->scheduled_at?->toIso8601String(),
                'status' => $match->status,
                'home_score' => $match->home_score,
                'away_score' => $match->away_score,
                'home_team' => [
                    'id' => $match->homeTeam?->id,
                    'name' => $match->homeTeam?->name,
                    'badge' => $match->homeTeam?->badge_url,
                ],
                'away_team' => [
                    'id' => $match->awayTeam?->id,
                    'name' => $match->awayTeam?->name,
                    'badge' => $match->awayTeam?->badge_url,
                ],
            ]);

        return Inertia::render('Tournaments/TeamShow', [
            'tournament' => [
                'id' => $tournament->id,
                'name' => $tournament->name,
                'code' => $tournament->code,
                'can_manage' => $canManage,
            ],
            'team' => [
                'id' => $team->id,
                'code' => $team->code,
                'name' => $team->name,
                'badge' => $team->badge_url,
                'position' => $team->position,
                'created_at' => $team->created_at?->toIso8601String(),
                'stats' => [
                    'played' => $team->played,
                    'won' => $team->won,
                    'drawn' => $team->drawn,
                    'lost' => $team->lost,
                    'goals_for' => $team->goals_for,
                    'goals_against' => $team->goals_against,
                    'goal_difference' => $team->goal_difference,
                    'points' => $team->points,
                ],
                'players' => $team->players
                    ->sortBy('number')
                    ->values()
                    ->map(fn (TournamentPlayer $player) => [
                        'id' => $player->id,
                        'name' => $player->name,
                        'dni' => $player->dni,
                        'number' => $player->number,
                        'age' => $player->age,
                        'goals' => $player->goals ?? 0,
                        'photo_url' => $player->photo_url,
                    ]),
                'recent_matches' => $recentMatches,
            ],
        ]);
    }

    public function showTeamBadge(TournamentTeam $team): SymfonyResponse
    {
        $team->loadMissing('tournament');
        abort_unless($team->tournament && $this->canAccessTournament($team->tournament), 403);

        $badgePath = $team->getRawOriginal('badge');

        if (!$badgePath) {
            abort(404);
        }

        if (str_starts_with($badgePath, 'http')) {
            return redirect()->away($badgePath);
        }

        abort_unless(Storage::disk('public')->exists($badgePath), 404);

        return response()->file(Storage::disk('public')->path($badgePath), [
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    public function showMatch(Tournament $tournament, TournamentMatch $match): Response
    {
        abort_unless($this->canAccessTournament($tournament), 403);
        abort_unless($match->tournament_id === $tournament->id, 404);

        $match->load([
            'homeTeam.players',
            'awayTeam.players',
        ]);

        return Inertia::render('Tournaments/MatchShow', [
            'tournament' => [
                'id' => $tournament->id,
                'name' => $tournament->name,
                'code' => $tournament->code,
                'can_manage' => $this->canManageTournament($tournament),
            ],
            'match' => $this->serializeTournamentMatch($match),
        ]);
    }

    public function store(StoreTournamentRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $tournament = Tournament::create([
            'code' => $this->generateUniqueCode(),
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'format' => $validated['format'],
            'playoff_teams_count' => $this->playoffTeamsCountFor($validated),
            'groups_count' => $this->groupsCountFor($validated),
            'regular_phase_matchdays_count' => $this->regularPhaseMatchdaysCountFor($validated),
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

    public function updateTeam(
        UpdateTournamentTeamRequest $request,
        Tournament $tournament,
        TournamentTeam $team,
    ): RedirectResponse {
        abort_unless($this->canManageTournament($tournament), 403);
        abort_unless($team->tournament_id === $tournament->id, 404);

        if ($request->hasFile('badge') && $team->getRawOriginal('badge') && !str_starts_with((string) $team->getRawOriginal('badge'), 'http')) {
            Storage::disk('public')->delete((string) $team->getRawOriginal('badge'));
        }

        $team->update([
            'badge' => $request->hasFile('badge')
                ? $request->file('badge')->store('teams', 'public')
                : $team->getRawOriginal('badge'),
        ]);

        return redirect()
            ->route('tournaments.teams.show', [$tournament, $team])
            ->with('success', 'El escudo del equipo se ha actualizado.');
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

        if ($request->boolean('stay_on_team')) {
            return redirect()
                ->route('tournaments.teams.show', [$tournament, $team])
                ->with('success', 'El jugador se ha anadido correctamente.');
        }

        return redirect()
            ->route('tournaments.show', $tournament)
            ->with('success', 'El jugador se ha anadido correctamente.');
    }

    public function updateMatchResult(
        UpdateTournamentMatchResultRequest $request,
        Tournament $tournament,
        TournamentMatch $match,
        PlayoffBracketService $playoffBracketService,
    ): RedirectResponse {
        abort_unless($this->canManageTournament($tournament), 403);
        abort_unless($match->tournament_id === $tournament->id, 404);

        $validated = $request->validated();
        $match->loadMissing('homeTeam.players', 'awayTeam.players');

        $homeScorers = $this->normalizeScorers($validated['home_scorers'] ?? []);
        $awayScorers = $this->normalizeScorers($validated['away_scorers'] ?? []);
        $previousHomeScorers = $this->normalizeScorers($match->home_scorers ?? []);
        $previousAwayScorers = $this->normalizeScorers($match->away_scorers ?? []);

        $this->validateMatchScorers($homeScorers, $match->homeTeam, (int) $validated['home_score'], 'home_scorers');
        $this->validateMatchScorers($awayScorers, $match->awayTeam, (int) $validated['away_score'], 'away_scorers');

        DB::transaction(function () use (
            $match,
            $tournament,
            $validated,
            $homeScorers,
            $awayScorers,
            $previousHomeScorers,
            $previousAwayScorers,
        ) {
            $match->update([
                'home_score' => $validated['home_score'],
                'away_score' => $validated['away_score'],
                'home_scorers' => $homeScorers,
                'away_scorers' => $awayScorers,
                'status' => 'FINISHED',
            ]);

            $this->recalculateTournamentStandings($tournament);
            $this->syncPlayerGoalsFromMatchUpdate(
                $previousHomeScorers,
                $previousAwayScorers,
                $homeScorers,
                $awayScorers,
            );
        });

        $tournament->update([
            'current_matchday' => $tournament->matches()->where('status', 'FINISHED')->max('matchday') ?: null,
        ]);

        $playoffBracketService->generateAutomaticallyIfReady($tournament->fresh());

        return redirect()
            ->back()
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
                'badge' => $team->badge_url,
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
                'badge' => $team->badge_url,
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
                    'team_badge' => $team->badge_url,
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
                'home_scorers' => collect($match->home_scorers ?? [])
                    ->map(fn (array $scorer) => [
                        'player_id' => (int) ($scorer['player_id'] ?? 0),
                        'goals' => (int) ($scorer['goals'] ?? 0),
                    ])
                    ->filter(fn (array $scorer) => $scorer['player_id'] > 0 && $scorer['goals'] > 0)
                    ->values()
                    ->all(),
                'away_scorers' => collect($match->away_scorers ?? [])
                    ->map(fn (array $scorer) => [
                        'player_id' => (int) ($scorer['player_id'] ?? 0),
                        'goals' => (int) ($scorer['goals'] ?? 0),
                    ])
                    ->filter(fn (array $scorer) => $scorer['player_id'] > 0 && $scorer['goals'] > 0)
                    ->values()
                    ->all(),
                'home_team' => [
                    'id' => $match->homeTeam?->id,
                    'name' => $match->homeTeam?->name,
                    'badge' => $match->homeTeam?->badge_url,
                    'players' => ($match->homeTeam?->players ?? collect())
                        ->sortBy('number')
                        ->values()
                        ->map(fn (TournamentPlayer $player) => [
                            'id' => $player->id,
                            'name' => $player->name,
                            'number' => $player->number,
                            'goals' => $player->goals ?? 0,
                        ])
                        ->all(),
                ],
                'away_team' => [
                    'id' => $match->awayTeam?->id,
                    'name' => $match->awayTeam?->name,
                    'badge' => $match->awayTeam?->badge_url,
                    'players' => ($match->awayTeam?->players ?? collect())
                        ->sortBy('number')
                        ->values()
                        ->map(fn (TournamentPlayer $player) => [
                            'id' => $player->id,
                            'name' => $player->name,
                            'number' => $player->number,
                            'goals' => $player->goals ?? 0,
                        ])
                        ->all(),
                ],
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeTournamentMatch(TournamentMatch $match): array
    {
        return [
            'id' => $match->id,
            'matchday' => $match->matchday,
            'scheduled_at' => $match->scheduled_at?->toIso8601String(),
            'venue' => $match->venue,
            'status' => $match->status,
            'home_score' => $match->home_score,
            'away_score' => $match->away_score,
            'home_scorers' => $this->serializeScorers($match->home_scorers ?? []),
            'away_scorers' => $this->serializeScorers($match->away_scorers ?? []),
            'home_team' => $this->serializeMatchTeam($match->homeTeam),
            'away_team' => $this->serializeMatchTeam($match->awayTeam),
        ];
    }

    /**
     * @return array<int, array{player_id:int, goals:int}>
     */
    private function serializeScorers(array $scorers): array
    {
        return collect($scorers)
            ->map(fn (array $scorer) => [
                'player_id' => (int) ($scorer['player_id'] ?? 0),
                'goals' => (int) ($scorer['goals'] ?? 0),
            ])
            ->filter(fn (array $scorer) => $scorer['player_id'] > 0 && $scorer['goals'] > 0)
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeMatchTeam(?TournamentTeam $team): array
    {
        return [
            'id' => $team?->id,
            'name' => $team?->name,
            'badge' => $team?->badge_url,
            'players' => ($team?->players ?? collect())
                ->sortBy('number')
                ->values()
                ->map(fn (TournamentPlayer $player) => [
                    'id' => $player->id,
                    'name' => $player->name,
                    'number' => $player->number,
                    'goals' => $player->goals ?? 0,
                ])
                ->all(),
        ];
    }

    /**
     * @param  array<int, array{player_id:int, goals:int}>  $scorers
     */
    private function validateMatchScorers(
        array $scorers,
        ?TournamentTeam $team,
        int $expectedGoals,
        string $attribute,
    ): void {
        if (!$team) {
            throw ValidationException::withMessages([
                $attribute => 'No se ha podido resolver el equipo asociado a los goleadores.',
            ]);
        }

        $teamPlayerIds = $team->players->pluck('id');
        $scorerPlayerIds = collect($scorers)->pluck('player_id');
        $invalidPlayers = $scorerPlayerIds
            ->filter(fn (int $playerId) => !$teamPlayerIds->contains($playerId))
            ->values();
        $goalsTotal = collect($scorers)->sum('goals');
        $messages = [];

        if ($invalidPlayers->isNotEmpty()) {
            $messages[$attribute][] = 'Todos los goleadores seleccionados deben pertenecer al equipo correspondiente.';
        }

        if ($goalsTotal !== $expectedGoals) {
            $messages[$attribute][] = 'La suma de goles de los goleadores debe coincidir con el marcador del equipo.';
        }

        if ($messages !== []) {
            throw ValidationException::withMessages($messages);
        }
    }

    /**
     * @param  array<int, array{player_id:mixed, goals:mixed}>  $scorers
     * @return array<int, array{player_id:int, goals:int}>
     */
    private function normalizeScorers(array $scorers): array
    {
        return collect($scorers)
            ->filter(fn (array $scorer) => !empty($scorer['player_id']) && !empty($scorer['goals']))
            ->map(fn (array $scorer) => [
                'player_id' => (int) $scorer['player_id'],
                'goals' => (int) $scorer['goals'],
            ])
            ->values()
            ->all();
    }

    private function recalculateTournamentStandings(Tournament $tournament): void
    {
        $tournament->load('teams', 'matches');

        $teamStats = [];

        foreach ($tournament->teams as $team) {
            $teamStats[$team->id] = [
                'name' => $team->name,
                'played' => 0,
                'won' => 0,
                'drawn' => 0,
                'lost' => 0,
                'goals_for' => 0,
                'goals_against' => 0,
                'goal_difference' => 0,
                'points' => 0,
            ];
        }

        foreach ($tournament->matches as $match) {
            if (
                $match->status !== 'FINISHED'
                || $match->home_score === null
                || $match->away_score === null
                || !isset($teamStats[$match->home_team_id], $teamStats[$match->away_team_id])
            ) {
                continue;
            }

            $homeTeamStats = &$teamStats[$match->home_team_id];
            $awayTeamStats = &$teamStats[$match->away_team_id];

            $homeTeamStats['played']++;
            $awayTeamStats['played']++;
            $homeTeamStats['goals_for'] += $match->home_score;
            $homeTeamStats['goals_against'] += $match->away_score;
            $awayTeamStats['goals_for'] += $match->away_score;
            $awayTeamStats['goals_against'] += $match->home_score;

            if ($match->home_score > $match->away_score) {
                $homeTeamStats['won']++;
                $homeTeamStats['points'] += 3;
                $awayTeamStats['lost']++;
            } elseif ($match->home_score < $match->away_score) {
                $awayTeamStats['won']++;
                $awayTeamStats['points'] += 3;
                $homeTeamStats['lost']++;
            } else {
                $homeTeamStats['drawn']++;
                $awayTeamStats['drawn']++;
                $homeTeamStats['points']++;
                $awayTeamStats['points']++;
            }

            unset($homeTeamStats, $awayTeamStats);
        }

        foreach ($teamStats as $teamId => &$stats) {
            $stats['goal_difference'] = $stats['goals_for'] - $stats['goals_against'];
        }
        unset($stats);

        $positions = collect(array_keys($teamStats))
            ->sort(function (int $firstTeamId, int $secondTeamId) use ($teamStats) {
                $firstTeam = $teamStats[$firstTeamId];
                $secondTeam = $teamStats[$secondTeamId];

                if ($firstTeam['points'] !== $secondTeam['points']) {
                    return $secondTeam['points'] <=> $firstTeam['points'];
                }

                if ($firstTeam['goal_difference'] !== $secondTeam['goal_difference']) {
                    return $secondTeam['goal_difference'] <=> $firstTeam['goal_difference'];
                }

                if ($firstTeam['goals_for'] !== $secondTeam['goals_for']) {
                    return $secondTeam['goals_for'] <=> $firstTeam['goals_for'];
                }

                return strcasecmp($firstTeam['name'], $secondTeam['name']);
            })
            ->values()
            ->flip()
            ->map(fn (int $index) => $index + 1);

        foreach ($tournament->teams as $team) {
            $team->update([
                ...$teamStats[$team->id],
                'position' => $positions[$team->id] ?? null,
            ]);
        }
    }

    /**
     * @param  array<int, array{player_id:int, goals:int}>  $previousHomeScorers
     * @param  array<int, array{player_id:int, goals:int}>  $previousAwayScorers
     * @param  array<int, array{player_id:int, goals:int}>  $homeScorers
     * @param  array<int, array{player_id:int, goals:int}>  $awayScorers
     */
    private function syncPlayerGoalsFromMatchUpdate(
        array $previousHomeScorers,
        array $previousAwayScorers,
        array $homeScorers,
        array $awayScorers,
    ): void {
        $goalDeltas = [];

        foreach ([$previousHomeScorers, $previousAwayScorers] as $scorers) {
            foreach ($scorers as $scorer) {
                $playerId = $scorer['player_id'];
                $goalDeltas[$playerId] = ($goalDeltas[$playerId] ?? 0) - $scorer['goals'];
            }
        }

        foreach ([$homeScorers, $awayScorers] as $scorers) {
            foreach ($scorers as $scorer) {
                $playerId = $scorer['player_id'];
                $goalDeltas[$playerId] = ($goalDeltas[$playerId] ?? 0) + $scorer['goals'];
            }
        }

        if ($goalDeltas === []) {
            return;
        }

        $players = TournamentPlayer::query()
            ->whereIn('id', array_keys($goalDeltas))
            ->get()
            ->keyBy('id');

        foreach ($goalDeltas as $playerId => $delta) {
            /** @var TournamentPlayer|null $player */
            $player = $players->get($playerId);

            if (!$player) {
                continue;
            }

            $player->update([
                'goals' => max(0, ((int) $player->goals) + $delta),
            ]);
        }
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
            'format' => $this->serializeTournamentFormat($tournament),
            'playoffs' => [
                'state' => $tournament->playoff_bracket_generated_at ? 'generated' : 'not_generated',
                'generated_at' => $tournament->playoff_bracket_generated_at?->toIso8601String(),
            ],
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

    /**
     * @param  array<string, mixed>  $validated
     */
    private function playoffTeamsCountFor(array $validated): ?int
    {
        $format = TournamentFormat::from($validated['format']);

        return $format->hasPlayoffs()
            ? (int) $validated['playoff_teams_count']
            : null;
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function groupsCountFor(array $validated): ?int
    {
        $format = TournamentFormat::from($validated['format']);

        return $format->hasGroups()
            ? (int) $validated['groups_count']
            : null;
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function regularPhaseMatchdaysCountFor(array $validated): ?int
    {
        $format = TournamentFormat::from($validated['format']);

        return $format->hasRegularPhase()
            ? (int) $validated['regular_phase_matchdays_count']
            : null;
    }

    /**
     * @return array{value:string, label:string, has_playoffs:bool, has_groups:bool, has_regular_phase:bool, playoff_teams_count:?int, groups_count:?int, regular_phase_matchdays_count:?int}
     */
    private function serializeTournamentFormat(Tournament $tournament): array
    {
        $format = $tournament->format ?? TournamentFormat::League;

        return [
            'value' => $format->value,
            'label' => $format->label(),
            'has_playoffs' => $format->hasPlayoffs(),
            'has_groups' => $format->hasGroups(),
            'has_regular_phase' => $format->hasRegularPhase(),
            'playoff_teams_count' => $tournament->playoff_teams_count,
            'groups_count' => $tournament->groups_count,
            'regular_phase_matchdays_count' => $tournament->regular_phase_matchdays_count,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializePlayoffs(Tournament $tournament, PlayoffBracketService $playoffBracketService): array
    {
        return [
            ...$playoffBracketService->statusFor($tournament),
            'rounds' => $tournament->playoffRounds
                ->map(fn ($round) => [
                    'id' => $round->id,
                    'name' => $round->name,
                    'round_number' => $round->round_number,
                    'matches_count' => $round->matches_count,
                    'matches' => $round->matches
                        ->map(fn ($match) => [
                            'id' => $match->id,
                            'round_number' => $match->round_number,
                            'position' => $match->position,
                            'status' => $match->status,
                            'home_score' => $match->home_score,
                            'away_score' => $match->away_score,
                            'home_team' => $this->serializePlayoffTeam($match->homeTeam),
                            'away_team' => $this->serializePlayoffTeam($match->awayTeam),
                            'winner_team' => $this->serializePlayoffTeam($match->winnerTeam),
                            'next_match_id' => $match->next_match_id,
                        ])
                        ->values(),
                ])
                ->values(),
        ];
    }

    /**
     * @return array{id:int, name:string, badge:?string}|null
     */
    private function serializePlayoffTeam(?TournamentTeam $team): ?array
    {
        if (!$team) {
            return null;
        }

        return [
            'id' => $team->id,
            'name' => $team->name,
            'badge' => $team->badge_url,
        ];
    }
}
