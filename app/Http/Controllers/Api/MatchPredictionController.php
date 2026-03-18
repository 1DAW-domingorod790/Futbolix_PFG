<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use App\Models\Api\Game;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class MatchPredictionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'match' => ['required', 'array'],
            'match.id' => ['nullable'],
            'match.utc_date' => ['nullable', 'string'],
            'match.matchday' => ['nullable'],
            'match.status' => ['nullable', 'string'],
            'match.home_score' => ['nullable'],
            'match.away_score' => ['nullable'],
            'match.home_team' => ['nullable', 'array'],
            'match.home_team.name' => ['nullable', 'string'],
            'match.home_team.shortname' => ['nullable', 'string'],
            'match.homeTeam' => ['nullable', 'array'],
            'match.homeTeam.name' => ['nullable', 'string'],
            'match.homeTeam.shortname' => ['nullable', 'string'],
            'match.away_team' => ['nullable', 'array'],
            'match.away_team.name' => ['nullable', 'string'],
            'match.away_team.shortname' => ['nullable', 'string'],
            'match.awayTeam' => ['nullable', 'array'],
            'match.awayTeam.name' => ['nullable', 'string'],
            'match.awayTeam.shortname' => ['nullable', 'string'],
            'competition' => ['required', 'array'],
            'competition.name' => ['required', 'string'],
            'competition.type' => ['nullable', 'string'],
            'competition.currentMatchDay' => ['nullable'],
        ]);

        $apiKey = (string) config('services.groq.api_key');

        if ($apiKey === '') {
            return response()->json([
                'message' => 'La clave de Groq no está configurada en el servidor.',
            ], 500);
        }

        $game = Game::query()
            ->with(['competition', 'homeTeam', 'awayTeam'])
            ->findOrFail($validated['match']['id']);

        [$systemPrompt, $userPrompt] = $this->buildPrompts(
            $validated['match'],
            $validated['competition'],
            $game
        );

        try {
            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->timeout(20)
                ->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model' => 'llama-3.3-70b-versatile',
                    'temperature' => 0.4,
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => $systemPrompt,
                        ],
                        [
                            'role' => 'user',
                            'content' => $userPrompt,
                        ],
                    ],
                ])
                ->throw();
        } catch (RequestException $exception) {
            report($exception);

            return response()->json([
                'message' => 'No se pudo generar la predicción en este momento.',
            ], 502);
        }

        $content = data_get($response->json(), 'choices.0.message.content');

        if (!is_string($content) || trim($content) === '') {
            return response()->json([
                'message' => 'La IA no devolvió una predicción válida.',
            ], 502);
        }

        try {
            $prediction = $this->normalizePrediction($content);
        } catch (ValidationException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 502);
        }

        return response()->json($prediction);
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

    private function buildPrompts(array $match, array $competition, Game $game): array
    {
        $homeTeam = $this->teamName($match['homeTeam'] ?? $match['home_team'] ?? []);
        $awayTeam = $this->teamName($match['awayTeam'] ?? $match['away_team'] ?? []);

        $homeRecentGames = $this->recentGamesForTeam(
            teamId: $game->home_team_id,
            currentGameId: $game->id,
        );

        $awayRecentGames = $this->recentGamesForTeam(
            teamId: $game->away_team_id,
            currentGameId: $game->id,
        );

        $systemPrompt = <<<'PROMPT'
        Eres un analista experto en fútbol europeo.

        Tu tarea es predecir el resultado de un partido usando únicamente los datos proporcionados.
        Debes ser prudente, coherente y realista.

        Siempre debes devolver únicamente un JSON válido, sin texto adicional, sin markdown y sin explicaciones fuera del JSON.
        PROMPT;

        $payload = [
            'home_team' => $homeTeam,
            'away_team' => $awayTeam,
            'home_team_last_5' => $homeRecentGames,
            'away_team_last_5' => $awayRecentGames,
            'competition' => $competition['name'] ?? null,
            'competition_type' => $competition['type'] ?? null,
            'current_matchday' => $competition['currentMatchDay'] ?? null,
            'matchday' => $match['matchday'] ?? null,
            'match_date' => $match['utc_date'] ?? null,
            'status' => $match['status'] ?? null,
            'home_score' => $match['home_score'] ?? null,
            'away_score' => $match['away_score'] ?? null,
        ];

        $userPrompt = "Analiza este partido y genera una prediccion previa al encuentro.\n\n"
            ."Debes estimar la probabilidad de:\n"
            ."- victoria del equipo local\n"
            ."- empate\n"
            ."- victoria del equipo visitante\n\n"
            ."Instrucciones:\n"
            ."1. Usa unicamente los datos proporcionados.\n"
            ."2. No inventes informacion que no aparezca en los datos.\n"
            ."3. Los porcentajes deben ser enteros.\n"
            ."4. La suma de home_win, draw y away_win debe ser exactamente 100.\n"
            ."5. Incluye un marcador exacto con goles enteros no negativos en home_goals y away_goals.\n"
            ."6. El marcador exacto debe ser coherente con las probabilidades y con el partido propuesto.\n"
            ."7. Si faltan datos relevantes, haz una prediccion conservadora.\n\n"
            ."Devuelve un JSON con esta estructura exacta:\n"
            .json_encode([
                'home_win' => 0,
                'draw' => 0,
                'away_win' => 0,
                'home_goals' => 0,
                'away_goals' => 0,
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
            ."\n\nDatos del partido:\n"
            .json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        return [$systemPrompt, $userPrompt];
    }

    private function teamName(array $team): ?string
    {
        return $team['shortname'] ?? $team['name'] ?? null;
    }

    private function normalizePrediction(string $content): array
    {
        $sanitized = trim($content);
        $sanitized = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $sanitized) ?? $sanitized;

        $decoded = json_decode($sanitized, true);

        if (!is_array($decoded)) {
            throw ValidationException::withMessages([
                'prediction' => 'La IA devolvio un formato de respuesta invalido.',
            ]);
        }

        $homeWin = $this->toInteger(data_get($decoded, 'home_win'));
        $draw = $this->toInteger(data_get($decoded, 'draw'));
        $awayWin = $this->toInteger(data_get($decoded, 'away_win'));
        $homeGoals = $this->toInteger(data_get($decoded, 'home_goals'));
        $awayGoals = $this->toInteger(data_get($decoded, 'away_goals'));

        if ($homeWin < 0 || $draw < 0 || $awayWin < 0 || $homeGoals < 0 || $awayGoals < 0) {
            throw ValidationException::withMessages([
                'prediction' => 'La IA devolvio datos incompletos para la prediccion.',
            ]);
        }

        $probabilities = [$homeWin, $draw, $awayWin];
        $sum = array_sum($probabilities);

        if ($sum <= 0) {
            throw ValidationException::withMessages([
                'prediction' => 'La IA devolvio probabilidades invalidas.',
            ]);
        }

        if ($sum !== 100) {
            $largestIndex = array_keys($probabilities, max($probabilities), true)[0];
            $probabilities[$largestIndex] += 100 - $sum;
        }

        if (min($probabilities) < 0 || array_sum($probabilities) !== 100) {
            throw ValidationException::withMessages([
                'prediction' => 'No se pudo normalizar la prediccion devuelta por la IA.',
            ]);
        }

        return [
            'home_win' => $probabilities[0],
            'draw' => $probabilities[1],
            'away_win' => $probabilities[2],
            'home_goals' => $homeGoals,
            'away_goals' => $awayGoals,
        ];
    }

    private function toInteger(mixed $value): int
    {
        if (filter_var($value, FILTER_VALIDATE_INT) === false) {
            throw ValidationException::withMessages([
                'prediction' => 'La IA devolvio porcentajes no enteros.',
            ]);
        }

        return (int) $value;
    }
}
