<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Api\Competition;
use App\Models\Api\Game;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
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
        $game = Game::query()
            ->with(['competition', 'homeTeam', 'awayTeam'])
            ->findOrFail($id);

        $homeRecentGames = $this->recentGamesForTeam(
            teamId: $game->home_team_id,
            currentGameId: $game->id,
        );

        $awayRecentGames = $this->recentGamesForTeam(
            teamId: $game->away_team_id,
            currentGameId: $game->id,
        );

        $headToHeadGames = $this->headToHeadGames(
            homeTeamId: $game->home_team_id,
            awayTeamId: $game->away_team_id,
            currentGameId: $game->id,
        );

        $matchdayGames = $this->matchdayGames(
            competitionId: $game->competition_id,
            matchday: $game->matchday,
            currentGameId: $game->id,
        );

        return Inertia::render('Api/GameShow', [
            'game' => $game,
            'recentGames' => [
                'home' => $homeRecentGames,
                'away' => $awayRecentGames,
            ],
            'headToHeadGames' => $headToHeadGames,
            'matchdayGames' => $matchdayGames,
            'teamSummaries' => [
                'home' => $this->buildTeamSummary(
                    teamId: $game->home_team_id,
                    competitionId: $game->competition_id,
                    currentGameDate: $game->utc_date,
                    includeCurrentGame: $game->status === 'FINISHED',
                ),
                'away' => $this->buildTeamSummary(
                    teamId: $game->away_team_id,
                    competitionId: $game->competition_id,
                    currentGameDate: $game->utc_date,
                    includeCurrentGame: $game->status === 'FINISHED',
                ),
            ],
        ]);
    }

    private function recentGamesForTeam(?int $teamId, int|string $currentGameId): Collection
    {
        if (!$teamId) {
            return collect();
        }

        return Game::query()
            ->with(['competition', 'homeTeam', 'awayTeam'])
            ->whereKeyNot($currentGameId)
            ->where(function (Builder $query) use ($teamId) {
                $query
                    ->where('home_team_id', $teamId)
                    ->orWhere('away_team_id', $teamId);
            })
            ->where('status', 'FINISHED')
            ->orderByDesc('utc_date')
            ->limit(5)
            ->get();
    }

    private function headToHeadGames(?int $homeTeamId, ?int $awayTeamId, int|string $currentGameId): Collection
    {
        if (!$homeTeamId || !$awayTeamId) {
            return collect();
        }

        return Game::query()
            ->with(['competition', 'homeTeam', 'awayTeam'])
            ->whereKeyNot($currentGameId)
            ->where(function (Builder $query) use ($homeTeamId, $awayTeamId) {
                $query
                    ->where(function (Builder $directQuery) use ($homeTeamId, $awayTeamId) {
                        $directQuery
                            ->where('home_team_id', $homeTeamId)
                            ->where('away_team_id', $awayTeamId);
                    })
                    ->orWhere(function (Builder $reverseQuery) use ($homeTeamId, $awayTeamId) {
                        $reverseQuery
                            ->where('home_team_id', $awayTeamId)
                            ->where('away_team_id', $homeTeamId);
                    });
            })
            ->orderByDesc('utc_date')
            ->limit(5)
            ->get();
    }

    private function matchdayGames(?int $competitionId, ?int $matchday, int|string $currentGameId): Collection
    {
        if (!$competitionId || !$matchday) {
            return collect();
        }

        return Game::query()
            ->with(['homeTeam', 'awayTeam'])
            ->where('competition_id', $competitionId)
            ->where('matchday', $matchday)
            ->whereKeyNot($currentGameId)
            ->orderBy('utc_date')
            ->get();
    }

    private function buildTeamSummary(
        ?int $teamId,
        ?int $competitionId,
        mixed $currentGameDate,
        bool $includeCurrentGame,
    ): array {
        if (!$teamId || !$competitionId) {
            return $this->emptySummary();
        }

        $games = Game::query()
            ->where('competition_id', $competitionId)
            ->where('status', 'FINISHED')
            ->where(function (Builder $query) use ($teamId) {
                $query
                    ->where('home_team_id', $teamId)
                    ->orWhere('away_team_id', $teamId);
            })
            ->when($currentGameDate, function (Builder $query) use ($currentGameDate, $includeCurrentGame) {
                $operator = $includeCurrentGame ? '<=' : '<';

                $query->where('utc_date', $operator, $currentGameDate);
            })
            ->get([
                'home_team_id',
                'away_team_id',
                'home_score',
                'away_score',
            ]);

        $summary = $this->emptySummary();

        foreach ($games as $teamGame) {
            $summary['played']++;

            $isHomeTeam = (int) $teamGame->home_team_id === $teamId;
            $goalsFor = $isHomeTeam ? (int) $teamGame->home_score : (int) $teamGame->away_score;
            $goalsAgainst = $isHomeTeam ? (int) $teamGame->away_score : (int) $teamGame->home_score;

            $summary['goals_for'] += $goalsFor;
            $summary['goals_against'] += $goalsAgainst;

            if ($goalsFor > $goalsAgainst) {
                $summary['wins']++;
                continue;
            }

            if ($goalsFor === $goalsAgainst) {
                $summary['draws']++;
                continue;
            }

            $summary['losses']++;
        }

        $summary['goal_difference'] = $summary['goals_for'] - $summary['goals_against'];

        return $summary;
    }

    private function emptySummary(): array
    {
        return [
            'played' => 0,
            'wins' => 0,
            'draws' => 0,
            'losses' => 0,
            'goals_for' => 0,
            'goals_against' => 0,
            'goal_difference' => 0,
        ];
    }
}
