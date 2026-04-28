<?php

namespace App\Http\Controllers\Tournaments;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tournaments\GeneratePlayoffDrawRequest;
use App\Http\Requests\Tournaments\StoreManualPlayoffMatchesRequest;
use App\Http\Requests\Tournaments\UpdatePlayoffMatchResultRequest;
use App\Models\Tournaments\PlayoffMatch;
use App\Models\Tournaments\Tournament;
use App\Services\Tournaments\PlayoffBracketService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PlayoffController extends Controller
{
    public function show(Tournament $tournament): RedirectResponse
    {
        abort_unless($this->canAccessTournament($tournament), 403);

        return redirect()->route('tournaments.show', $tournament);
    }

    public function generate(Tournament $tournament, PlayoffBracketService $playoffBracketService): RedirectResponse
    {
        abort_unless($this->canManageTournament($tournament), 403);

        $playoffBracketService->generateAutomaticallyIfReady($tournament, true);

        return redirect()
            ->route('tournaments.show', $tournament)
            ->with('success', 'El cuadro de playoffs se ha generado correctamente.');
    }

    public function draw(
        GeneratePlayoffDrawRequest $request,
        Tournament $tournament,
        PlayoffBracketService $playoffBracketService,
    ): RedirectResponse {
        abort_unless($this->canManageTournament($tournament), 403);

        $playoffBracketService->generateDraw($tournament, $request->validated('team_ids'));

        return redirect()
            ->route('tournaments.show', $tournament)
            ->with('success', 'El sorteo de playoffs se ha generado correctamente.');
    }

    public function manual(
        StoreManualPlayoffMatchesRequest $request,
        Tournament $tournament,
        PlayoffBracketService $playoffBracketService,
    ): RedirectResponse {
        abort_unless($this->canManageTournament($tournament), 403);

        $playoffBracketService->generateManual($tournament, $request->validated('matches'));

        return redirect()
            ->route('tournaments.show', $tournament)
            ->with('success', 'Las eliminatorias manuales se han guardado correctamente.');
    }

    public function showMatch(Tournament $tournament, PlayoffMatch $match): Response
    {
        abort_unless($this->canAccessTournament($tournament), 403);
        abort_unless($match->tournament_id === $tournament->id, 404);

        $match->load(['round', 'homeTeam', 'awayTeam', 'winnerTeam']);

        return Inertia::render('Tournaments/PlayoffMatchShow', [
            'tournament' => [
                'id' => $tournament->id,
                'name' => $tournament->name,
                'code' => $tournament->code,
                'can_manage' => $this->canManageTournament($tournament),
            ],
            'match' => $this->serializeMatch($match),
        ]);
    }

    public function updateResult(
        UpdatePlayoffMatchResultRequest $request,
        Tournament $tournament,
        PlayoffMatch $match,
    ): RedirectResponse {
        abort_unless($this->canManageTournament($tournament), 403);
        abort_unless($match->tournament_id === $tournament->id, 404);

        $validated = $request->validated();
        $match->loadMissing('homeTeam', 'awayTeam', 'nextMatch');

        if (!$match->home_team_id || !$match->away_team_id) {
            throw ValidationException::withMessages([
                'home_score' => 'No se puede guardar resultado hasta que la eliminatoria tenga dos equipos asignados.',
            ]);
        }

        $winnerId = (int) $validated['home_score'] > (int) $validated['away_score']
            ? $match->home_team_id
            : $match->away_team_id;

        $match->update([
            'home_score' => $validated['home_score'],
            'away_score' => $validated['away_score'],
            'winner_team_id' => $winnerId,
            'status' => PlayoffMatch::STATUS_FINISHED,
        ]);

        $this->moveWinnerToNextMatch($match, $winnerId);

        return redirect()
            ->route('tournaments.playoffs.matches.show', [$tournament, $match])
            ->with('success', 'El resultado del partido de playoffs se ha guardado.');
    }

    private function canManageTournament(Tournament $tournament): bool
    {
        return $tournament->admin_id === auth()->id();
    }

    private function canAccessTournament(Tournament $tournament): bool
    {
        return $this->canManageTournament($tournament) || (bool) $tournament->is_public;
    }

    private function moveWinnerToNextMatch(PlayoffMatch $match, ?int $winnerId): void
    {
        if (!$winnerId || !$match->next_match_id) {
            return;
        }

        $nextMatch = PlayoffMatch::query()->find($match->next_match_id);

        if (!$nextMatch) {
            return;
        }

        $field = $match->position % 2 === 1 ? 'home_team_id' : 'away_team_id';
        $nextMatch->{$field} = $winnerId;
        $nextMatch->status = $nextMatch->home_team_id && $nextMatch->away_team_id
            ? PlayoffMatch::STATUS_READY
            : $nextMatch->status;
        $nextMatch->save();
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeMatch(PlayoffMatch $match): array
    {
        return [
            'id' => $match->id,
            'round_number' => $match->round_number,
            'position' => $match->position,
            'round_name' => $match->round?->name,
            'status' => $match->status,
            'home_score' => $match->home_score,
            'away_score' => $match->away_score,
            'home_team' => $this->serializeTeam($match->homeTeam),
            'away_team' => $this->serializeTeam($match->awayTeam),
            'winner_team' => $this->serializeTeam($match->winnerTeam),
        ];
    }

    /**
     * @return array{id:int, name:string, badge:?string}|null
     */
    private function serializeTeam($team): ?array
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
