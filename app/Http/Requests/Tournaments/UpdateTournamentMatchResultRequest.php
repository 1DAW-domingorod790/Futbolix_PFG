<?php

namespace App\Http\Requests\Tournaments;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTournamentMatchResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'home_score' => ['required', 'integer', 'min:0', 'max:99'],
            'away_score' => ['required', 'integer', 'min:0', 'max:99'],
            'home_scorers' => ['nullable', 'array'],
            'home_scorers.*.player_id' => ['required', 'integer', 'distinct', 'exists:tournament_players,id'],
            'home_scorers.*.goals' => ['required', 'integer', 'min:1', 'max:99'],
            'away_scorers' => ['nullable', 'array'],
            'away_scorers.*.player_id' => ['required', 'integer', 'distinct', 'exists:tournament_players,id'],
            'away_scorers.*.goals' => ['required', 'integer', 'min:1', 'max:99'],
        ];
    }
}
