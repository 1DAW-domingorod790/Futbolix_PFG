<?php

namespace App\Http\Requests\Tournaments;

use Illuminate\Foundation\Http\FormRequest;

class StoreTournamentMatchRequest extends FormRequest
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
            'matchday' => ['required', 'integer', 'min:1', 'max:99'],
            'scheduled_at' => ['required', 'date'],
            'venue' => ['nullable', 'string', 'max:120'],
            'home_team_id' => ['required', 'integer', 'different:away_team_id'],
            'away_team_id' => ['required', 'integer'],
        ];
    }
}
