<?php

namespace App\Services\Tournaments;

use App\Enums\Tournaments\TournamentFormat;
use App\Models\Tournaments\PlayoffMatch;
use App\Models\Tournaments\PlayoffRound;
use App\Models\Tournaments\Tournament;
use App\Models\Tournaments\TournamentTeam;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PlayoffBracketService
{
    /**
     * @return array{state:string, is_generated:bool, can_generate:bool, message:string, current_matchday:?int, total_matchdays:?int, playoff_teams_count:?int, generated_at:?string}
     */
    public function statusFor(Tournament $tournament): array
    {
        $format = $tournament->format ?? TournamentFormat::League;

        if (!$format->hasPlayoffs()) {
            return [
                'state' => 'no_playoffs',
                'is_generated' => false,
                'can_generate' => false,
                'message' => 'Este torneo no tiene fase de playoffs.',
                'current_matchday' => $tournament->current_matchday,
                'total_matchdays' => $tournament->regular_phase_matchdays_count,
                'playoff_teams_count' => $tournament->playoff_teams_count,
                'generated_at' => null,
            ];
        }

        $isGenerated = $this->hasBracket($tournament);

        if ($isGenerated) {
            return [
                'state' => $this->resolveBracketState($tournament),
                'is_generated' => true,
                'can_generate' => false,
                'message' => 'El cuadro de playoffs ya esta generado.',
                'current_matchday' => $tournament->current_matchday,
                'total_matchdays' => $tournament->regular_phase_matchdays_count,
                'playoff_teams_count' => $tournament->playoff_teams_count,
                'generated_at' => $tournament->playoff_bracket_generated_at?->toIso8601String(),
            ];
        }

        if ($format === TournamentFormat::Playoffs) {
            return [
                'state' => 'not_generated',
                'is_generated' => false,
                'can_generate' => true,
                'message' => 'Puedes crear las eliminatorias manualmente o sortear el cuadro automaticamente.',
                'current_matchday' => null,
                'total_matchdays' => null,
                'playoff_teams_count' => $tournament->playoff_teams_count,
                'generated_at' => null,
            ];
        }

        $regularPhaseFinished = $this->regularPhaseFinished($tournament);

        return [
            'state' => 'not_generated',
            'is_generated' => false,
            'can_generate' => $regularPhaseFinished,
            'message' => $regularPhaseFinished
                ? 'La fase previa ha terminado. El cuadro puede generarse automaticamente.'
                : 'El cuadro de playoffs se generara automaticamente cuando finalice la ultima jornada.',
            'current_matchday' => $this->currentMatchday($tournament),
            'total_matchdays' => $tournament->regular_phase_matchdays_count,
            'playoff_teams_count' => $tournament->playoff_teams_count,
            'generated_at' => null,
        ];
    }

    public function generateAutomaticallyIfReady(Tournament $tournament, bool $throwWhenBlocked = false): ?Tournament
    {
        $tournament->loadMissing('teams', 'matches');
        $format = $tournament->format ?? TournamentFormat::League;

        if (!$format->hasRegularPhase()) {
            if ($throwWhenBlocked) {
                throw ValidationException::withMessages([
                    'playoffs' => 'Este formato no genera playoffs automaticamente desde una fase previa.',
                ]);
            }

            return null;
        }

        if ($this->hasBracket($tournament)) {
            if ($throwWhenBlocked) {
                throw ValidationException::withMessages([
                    'playoffs' => 'El cuadro de playoffs ya esta generado.',
                ]);
            }

            return null;
        }

        if (!$this->regularPhaseFinished($tournament)) {
            if ($throwWhenBlocked) {
                throw ValidationException::withMessages([
                    'playoffs' => 'No se puede generar el cuadro hasta finalizar la ultima jornada de la fase previa.',
                ]);
            }

            return null;
        }

        try {
            $teams = $format === TournamentFormat::LeaguePlayoffs
                ? $this->getQualifiedTeamsFromLeague($tournament)
                : $this->getQualifiedTeamsFromGroups($tournament);

            $pairings = $format === TournamentFormat::LeaguePlayoffs
                ? $this->buildSeededPairings($teams)
                : $this->buildDrawPairings($teams);

            return $this->createBracket($tournament, $pairings);
        } catch (ValidationException $exception) {
            if ($throwWhenBlocked) {
                throw $exception;
            }

            return null;
        }
    }

    /**
     * @param  array<int, int>  $teamIds
     */
    public function generateDraw(Tournament $tournament, array $teamIds): Tournament
    {
        $this->ensureDirectPlayoffs($tournament);
        $teams = $this->teamsFromIds($tournament, $teamIds)->shuffle()->values();

        return $this->createBracket($tournament, $this->buildDrawPairings($teams));
    }

    /**
     * @param  array<int, array{home_team_id:mixed, away_team_id:mixed}>  $matches
     */
    public function generateManual(Tournament $tournament, array $matches): Tournament
    {
        $this->ensureDirectPlayoffs($tournament);

        $teamIds = collect($matches)
            ->flatMap(fn (array $match) => [(int) $match['home_team_id'], (int) $match['away_team_id']])
            ->values();

        if ($teamIds->duplicates()->isNotEmpty()) {
            throw ValidationException::withMessages([
                'matches' => 'No puedes repetir un equipo en dos eliminatorias.',
            ]);
        }

        $this->teamsFromIds($tournament, $teamIds->all());

        $pairings = collect($matches)
            ->map(fn (array $match) => [
                'home_team_id' => (int) $match['home_team_id'],
                'away_team_id' => (int) $match['away_team_id'],
            ])
            ->values();

        return $this->createBracket($tournament, $pairings);
    }

    private function hasBracket(Tournament $tournament): bool
    {
        return $tournament->playoff_bracket_generated_at !== null
            || $tournament->playoffRounds()->exists();
    }

    private function resolveBracketState(Tournament $tournament): string
    {
        $matches = $tournament->relationLoaded('playoffMatches')
            ? $tournament->playoffMatches
            : $tournament->playoffMatches()->get();

        if ($matches->isNotEmpty() && $matches->every(fn (PlayoffMatch $match) => $match->status === PlayoffMatch::STATUS_FINISHED)) {
            return 'finished';
        }

        if ($matches->contains(fn (PlayoffMatch $match) => $match->winner_team_id !== null)) {
            return 'in_progress';
        }

        return 'generated';
    }

    private function regularPhaseFinished(Tournament $tournament): bool
    {
        $totalMatchdays = $tournament->regular_phase_matchdays_count;

        if (!$totalMatchdays || $totalMatchdays < 1) {
            return false;
        }

        $matches = $tournament->matches()
            ->whereBetween('matchday', [1, $totalMatchdays])
            ->get(['id', 'matchday', 'status']);

        if ($matches->isEmpty()) {
            return false;
        }

        $hasLastMatchday = $matches->contains(fn ($match) => (int) $match->matchday === $totalMatchdays);
        $hasPendingMatches = $matches->contains(fn ($match) => $match->status !== 'FINISHED');

        return $hasLastMatchday && !$hasPendingMatches;
    }

    private function currentMatchday(Tournament $tournament): ?int
    {
        return $tournament->matches()
            ->where('status', 'FINISHED')
            ->max('matchday') ?: $tournament->current_matchday;
    }

    /**
     * @return Collection<int, TournamentTeam>
     */
    private function getQualifiedTeamsFromLeague(Tournament $tournament): Collection
    {
        return $this->rankedTeams($tournament)->take((int) $tournament->playoff_teams_count)->values();
    }

    /**
     * @return Collection<int, TournamentTeam>
     */
    private function getQualifiedTeamsFromGroups(Tournament $tournament): Collection
    {
        // TODO: sustituir por clasificacion real por grupos cuando TournamentTeam guarde grupo.
        return $this->rankedTeams($tournament)
            ->take((int) $tournament->playoff_teams_count)
            ->shuffle()
            ->values();
    }

    /**
     * @return Collection<int, TournamentTeam>
     */
    private function rankedTeams(Tournament $tournament): Collection
    {
        return $tournament->teams()
            ->orderByRaw('position is null')
            ->orderBy('position')
            ->orderByDesc('points')
            ->orderByDesc('goal_difference')
            ->orderByDesc('goals_for')
            ->orderBy('name')
            ->get();
    }

    /**
     * @param  Collection<int, TournamentTeam>  $teams
     * @return Collection<int, array{home_team_id:int, away_team_id:int}>
     */
    private function buildSeededPairings(Collection $teams): Collection
    {
        $this->validateTeamsCount($teams->count());

        return $teams
            ->take($teams->count() / 2)
            ->values()
            ->map(fn (TournamentTeam $team, int $index) => [
                'home_team_id' => $team->id,
                'away_team_id' => $teams[$teams->count() - 1 - $index]->id,
            ]);
    }

    /**
     * @param  Collection<int, TournamentTeam>  $teams
     * @return Collection<int, array{home_team_id:int, away_team_id:int}>
     */
    private function buildDrawPairings(Collection $teams): Collection
    {
        $this->validateTeamsCount($teams->count());

        return $teams
            ->values()
            ->chunk(2)
            ->map(function (Collection $pair) {
                $pair = $pair->values();

                return [
                    'home_team_id' => $pair[0]->id,
                    'away_team_id' => $pair[1]->id,
                ];
            })
            ->values();
    }

    /**
     * @param  Collection<int, array{home_team_id:int, away_team_id:int}>  $pairings
     */
    private function createBracket(Tournament $tournament, Collection $pairings): Tournament
    {
        return DB::transaction(function () use ($tournament, $pairings) {
            $lockedTournament = Tournament::query()
                ->whereKey($tournament->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($this->hasBracket($lockedTournament)) {
                throw ValidationException::withMessages([
                    'playoffs' => 'El cuadro de playoffs ya esta generado.',
                ]);
            }

            $firstRoundMatchesCount = $pairings->count();
            $roundsCount = (int) log($firstRoundMatchesCount * 2, 2);
            $rounds = collect();
            $matchesByRound = [];

            for ($roundNumber = 1; $roundNumber <= $roundsCount; $roundNumber++) {
                $matchesCount = (int) ($firstRoundMatchesCount / (2 ** ($roundNumber - 1)));
                $round = PlayoffRound::create([
                    'tournament_id' => $lockedTournament->id,
                    'round_number' => $roundNumber,
                    'name' => $this->roundName($matchesCount),
                    'matches_count' => $matchesCount,
                ]);

                $rounds->put($roundNumber, $round);
                $matchesByRound[$roundNumber] = [];

                for ($position = 1; $position <= $matchesCount; $position++) {
                    $pairing = $roundNumber === 1 ? $pairings[$position - 1] : null;
                    $match = PlayoffMatch::create([
                        'tournament_id' => $lockedTournament->id,
                        'playoff_round_id' => $round->id,
                        'round_number' => $roundNumber,
                        'position' => $position,
                        'home_team_id' => $pairing['home_team_id'] ?? null,
                        'away_team_id' => $pairing['away_team_id'] ?? null,
                        'status' => $roundNumber === 1 ? PlayoffMatch::STATUS_READY : PlayoffMatch::STATUS_PENDING,
                    ]);

                    $matchesByRound[$roundNumber][$position] = $match;
                }
            }

            for ($roundNumber = 1; $roundNumber < $roundsCount; $roundNumber++) {
                foreach ($matchesByRound[$roundNumber] as $position => $match) {
                    $nextPosition = (int) ceil($position / 2);
                    $match->update([
                        'next_match_id' => $matchesByRound[$roundNumber + 1][$nextPosition]->id,
                    ]);
                }
            }

            $lockedTournament->update([
                'current_matchday' => $this->currentMatchday($lockedTournament),
                'playoff_bracket_generated_at' => now(),
            ]);

            return $lockedTournament->fresh(['playoffRounds.matches.homeTeam', 'playoffRounds.matches.awayTeam']);
        });
    }

    private function ensureDirectPlayoffs(Tournament $tournament): void
    {
        if ($tournament->format !== TournamentFormat::Playoffs) {
            throw ValidationException::withMessages([
                'playoffs' => 'Solo los torneos de playoffs directos permiten crear el cuadro manualmente o por sorteo.',
            ]);
        }

        if ($this->hasBracket($tournament)) {
            throw ValidationException::withMessages([
                'playoffs' => 'El cuadro de playoffs ya esta generado.',
            ]);
        }
    }

    /**
     * @param  array<int, int>  $teamIds
     * @return Collection<int, TournamentTeam>
     */
    private function teamsFromIds(Tournament $tournament, array $teamIds): Collection
    {
        $uniqueTeamIds = collect($teamIds)->map(fn ($teamId) => (int) $teamId)->unique()->values();

        if ($uniqueTeamIds->count() !== count($teamIds)) {
            throw ValidationException::withMessages([
                'team_ids' => 'No puedes seleccionar el mismo equipo mas de una vez.',
            ]);
        }

        $this->validateTeamsCount($uniqueTeamIds->count());

        if ((int) $tournament->playoff_teams_count !== $uniqueTeamIds->count()) {
            throw ValidationException::withMessages([
                'team_ids' => "Debes seleccionar {$tournament->playoff_teams_count} equipos para este cuadro.",
            ]);
        }

        $teams = $tournament->teams()
            ->whereIn('id', $uniqueTeamIds)
            ->get()
            ->keyBy('id');

        if ($teams->count() !== $uniqueTeamIds->count()) {
            throw ValidationException::withMessages([
                'team_ids' => 'Todos los equipos seleccionados deben pertenecer a este torneo.',
            ]);
        }

        return $uniqueTeamIds->map(fn (int $teamId) => $teams[$teamId])->values();
    }

    private function validateTeamsCount(int $teamsCount): void
    {
        if ($teamsCount < 2 || $teamsCount % 2 !== 0) {
            throw ValidationException::withMessages([
                'team_ids' => 'El cuadro necesita un numero par de equipos.',
            ]);
        }

        if (!in_array($teamsCount, [2, 4, 8, 16, 32, 64], true)) {
            throw ValidationException::withMessages([
                'team_ids' => 'El numero de equipos debe ser una potencia de 2: 2, 4, 8, 16, 32 o 64.',
            ]);
        }
    }

    private function roundName(int $matchesCount): string
    {
        return match ($matchesCount) {
            1 => 'Final',
            2 => 'Semifinales',
            4 => 'Cuartos de final',
            8 => 'Octavos de final',
            16 => 'Dieciseisavos de final',
            default => 'Ronda de '.($matchesCount * 2),
        };
    }
}
