<?php

namespace App\Services\Ai;

use App\Models\Api\Competition;
use App\Models\Api\Game;
use App\Models\Api\Team;
use App\Models\Tournaments\Tournament;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AiBusinessContextService
{
    public function buildFor(User $user, ?string $question = null): string
    {
        $teams = $this->matchingTeams($question);
        $competitions = $this->matchingCompetitions($question, $teams);

        $payload = [
            'fuente' => 'Base de datos local de Futbolix: API de futbol de las 5 grandes ligas y torneos del usuario.',
            'pregunta' => $question,
            'competiciones_api' => $this->competitionContext($competitions),
            'equipos_api' => $this->teamContext($teams),
            'torneos_usuario' => $this->tournamentContext($user),
            'instruccion' => 'Usa estos datos antes que conocimiento general. Si la respuesta no aparece en este contexto, dilo claramente.',
        ];

        Log::debug('Futbolix AI business context prepared', [
            'question' => $question,
            'competitions' => $competitions->pluck('code')->values()->all(),
            'teams' => $teams->pluck('name')->values()->all(),
            'team_games' => $teams->mapWithKeys(fn (Team $team) => [
                $team->name => [
                    'upcoming' => $this->teamGamesQuery($team, upcoming: true)->count(),
                    'finished' => $this->teamGamesQuery($team, upcoming: false)->count(),
                ],
            ])->all(),
        ]);

        return json_encode($payload, JSON_UNESCAPED_UNICODE) ?: '{}';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function competitionContext(Collection $competitions): array
    {
        return $competitions
            ->map(fn (Competition $competition) => [
                'id' => $competition->id,
                'nombre' => $competition->name,
                'codigo' => $competition->code,
                'jornada_actual' => $competition->currentMatchDay,
                'clasificacion_top_5' => $this->standingsFor($competition),
                'ultimos_resultados' => $this->gamesFor($competition, 'FINISHED'),
                'proximos_partidos' => $this->gamesFor($competition, null),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function teamContext(Collection $teams): array
    {
        return $teams
            ->map(fn (Team $team) => $this->serializeTeamContext($team))
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, Team>
     */
    private function matchingTeams(?string $question): Collection
    {
        if (!$question) {
            return collect();
        }

        $normalizedQuestion = $this->normalizeText($question);
        $words = collect(preg_split('/[^a-z0-9]+/i', $normalizedQuestion) ?: [])
            ->filter(fn (string $word) => mb_strlen($word) >= 4)
            ->take(8)
            ->values();

        if (str_contains($normalizedQuestion, 'betis')) {
            $words->push('betis');
        }

        if ($words->isEmpty()) {
            return collect();
        }

        $teams = Team::query()
            ->where(function (Builder $query) use ($words) {
                foreach ($words as $word) {
                    $query->orWhere('name', 'like', "%{$word}%")
                        ->orWhere('shortname', 'like', "%{$word}%")
                        ->orWhere('tla', 'like', "%{$word}%");
                }
            })
            ->with(['competitions' => fn ($query) => $query->limit(2)])
            ->limit((int) config('futbolix_ai.max_teams_context', 8))
            ->get();

        if ($teams->isEmpty()) {
            $teams = Team::query()
                ->with(['competitions' => fn ($query) => $query->limit(3)])
                ->get()
                ->filter(function (Team $team) use ($words) {
                    $haystack = Str::lower(Str::ascii(implode(' ', [
                        $team->name,
                        $team->shortname,
                        $team->tla,
                    ])));

                    return $words->contains(fn (string $word) => str_contains($haystack, $word));
                })
                ->take((int) config('futbolix_ai.max_teams_context', 8))
                ->values();
        }

        return $teams;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function tournamentContext(User $user): array
    {
        return Tournament::query()
            ->where('admin_id', $user->id)
            ->latest()
            ->limit(1)
            ->select([
                'id',
                'name',
                'description',
                'format',
                'current_matchday',
                'is_public',
            ])
            ->withCount(['teams', 'matches'])
            ->get()
            ->map(fn (Tournament $tournament) => [
                'id' => $tournament->id,
                'nombre' => str($tournament->name)->limit(80)->toString(),
                'descripcion' => $tournament->description ? str($tournament->description)->limit(80)->toString() : null,
                'formato' => $tournament->format?->value ?? (string) $tournament->format,
                'jornada_actual' => $tournament->current_matchday,
                'equipos' => $tournament->teams_count,
                'partidos' => $tournament->matches_count,
            ])
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, Competition>
     */
    private function matchingCompetitions(?string $question, Collection $teams): Collection
    {
        $question = $this->normalizeText((string) $question);
        $aliases = [
            'PL' => ['premier', 'premier league', 'inglaterra', 'english'],
            'PD' => ['laliga', 'la liga', 'liga espanola', 'primera division', 'espana', 'spain', 'betis', 'real betis'],
            'SA' => ['serie a', 'italia', 'italy'],
            'BL1' => ['bundesliga', 'alemania', 'germany'],
            'FL1' => ['ligue 1', 'francia', 'france'],
        ];

        $codes = collect($aliases)
            ->filter(fn (array $terms) => collect($terms)->contains(fn (string $term) => str_contains($question, $term)))
            ->keys()
            ->values();

        $teamCompetitionIds = $teams
            ->flatMap(fn (Team $team) => $team->competitions->pluck('id'))
            ->filter()
            ->unique()
            ->values();

        $query = Competition::query()->orderBy('name');

        if ($codes->isNotEmpty() || $teamCompetitionIds->isNotEmpty()) {
            return $query
                ->where(function (Builder $builder) use ($codes, $teamCompetitionIds) {
                    if ($codes->isNotEmpty()) {
                        $builder->whereIn('code', $codes);
                    }

                    if ($teamCompetitionIds->isNotEmpty()) {
                        $method = $codes->isNotEmpty() ? 'orWhereIn' : 'whereIn';
                        $builder->{$method}('id', $teamCompetitionIds);
                    }
                })
                ->limit((int) config('futbolix_ai.max_competitions_context', 5))
                ->get();
        }

        if ($question !== '') {
            $words = collect(preg_split('/[^a-z0-9]+/i', $question) ?: [])
                ->filter(fn (string $word) => mb_strlen($word) >= 4)
                ->take(6);

            $query->where(function (Builder $builder) use ($words) {
                foreach ($words as $word) {
                    $builder->orWhere('name', 'like', "%{$word}%")
                        ->orWhere('code', 'like', "%{$word}%");
                }
            });

            $matches = $query->limit(2)->get();

            if ($matches->isNotEmpty()) {
                return $matches;
            }
        }

        return Competition::query()
            ->whereIn('code', ['PL', 'PD', 'SA', 'BL1', 'FL1'])
            ->orderBy('name')
            ->limit((int) config('futbolix_ai.max_competitions_context', 5))
            ->get();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function standingsFor(Competition $competition): array
    {
        return $competition->teams()
            ->orderByPivot('standing')
            ->limit((int) config('futbolix_ai.max_standings_context', 25))
            ->get()
            ->map(fn (Team $team) => [
                'posicion' => $team->pivot->standing,
                'equipo' => $team->name,
                'puntos' => $team->pivot->points,
                'ganados' => $team->pivot->won,
                'empatados' => $team->pivot->draw,
                'perdidos' => $team->pivot->lost,
                'diferencia_goles' => $team->pivot->goal_difference,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function gamesFor(Competition $competition, ?string $status): array
    {
        return Game::query()
            ->with(['homeTeam:id,name,shortname', 'awayTeam:id,name,shortname'])
            ->where('competition_id', $competition->id)
            ->when($status, fn (Builder $query) => $query->where('status', $status))
            ->when(!$status, fn (Builder $query) => $query->where('status', '!=', 'FINISHED'))
            ->orderBy($status === 'FINISHED' ? 'utc_date' : 'utc_date', $status === 'FINISHED' ? 'desc' : 'asc')
            ->limit((int) config('futbolix_ai.max_competition_games_context', 30))
            ->get()
            ->map(fn (Game $game) => [
                'jornada' => $game->matchday,
                'fecha' => $game->utc_date?->toDateString(),
                'estado' => $game->status,
                'local' => $game->homeTeam?->name,
                'visitante' => $game->awayTeam?->name,
                'marcador' => $game->home_score !== null && $game->away_score !== null
                    ? "{$game->home_score}-{$game->away_score}"
                    : null,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeTeamContext(Team $team): array
    {
        return [
            'nombre' => $team->name,
            'nombre_corto' => $team->shortname,
            'tla' => $team->tla,
            'estadio' => $team->venue,
            'competiciones' => $team->competitions->map(fn (Competition $competition) => [
                'nombre' => $competition->name,
                'codigo' => $competition->code,
                'posicion' => $competition->pivot->standing,
                'puntos' => $competition->pivot->points,
                'ganados' => $competition->pivot->won,
                'empatados' => $competition->pivot->draw,
                'perdidos' => $competition->pivot->lost,
                'diferencia_goles' => $competition->pivot->goal_difference,
            ])->values(),
            'proximos_partidos' => $this->gamesForTeam($team, upcoming: true),
            'ultimos_resultados' => $this->gamesForTeam($team, upcoming: false),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function gamesForTeam(Team $team, bool $upcoming): array
    {
        return $this->teamGamesQuery($team, $upcoming)
            ->limit((int) config('futbolix_ai.max_team_games_context', 80))
            ->get()
            ->map(fn (Game $game) => [
                'competicion' => $game->competition?->name,
                'jornada' => $game->matchday,
                'fecha' => $game->utc_date?->toDateString(),
                'estado' => $game->status,
                'local' => $game->homeTeam?->name,
                'visitante' => $game->awayTeam?->name,
                'marcador' => $game->home_score !== null && $game->away_score !== null
                    ? "{$game->home_score}-{$game->away_score}"
                    : null,
            ])
            ->values()
            ->all();
    }

    private function teamGamesQuery(Team $team, bool $upcoming): Builder
    {
        return Game::query()
            ->with(['competition:id,name,code', 'homeTeam:id,name,shortname', 'awayTeam:id,name,shortname'])
            ->where(function (Builder $query) use ($team) {
                $query
                    ->where('home_team_id', $team->id)
                    ->orWhere('away_team_id', $team->id);
            })
            ->when(
                $upcoming,
                fn (Builder $query) => $query->where('status', '!=', 'FINISHED')->orderBy('utc_date'),
                fn (Builder $query) => $query->where('status', 'FINISHED')->orderByDesc('utc_date'),
            );
    }

    private function normalizeText(string $value): string
    {
        return Str::lower(Str::ascii($value));
    }
}
