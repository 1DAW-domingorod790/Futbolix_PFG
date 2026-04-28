<?php

namespace App\Http\Requests\Tournaments;

use Illuminate\Foundation\Http\FormRequest;

class StoreManualPlayoffMatchesRequest extends FormRequest
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
            'matches' => ['required', 'array', 'min:1'],
            'matches.*.home_team_id' => ['required', 'integer', 'different:matches.*.away_team_id', 'exists:tournament_teams,id'],
            'matches.*.away_team_id' => ['required', 'integer', 'exists:tournament_teams,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'matches' => 'eliminatorias',
            'matches.*.home_team_id' => 'equipo local',
            'matches.*.away_team_id' => 'equipo visitante',
        ];
    }
}
