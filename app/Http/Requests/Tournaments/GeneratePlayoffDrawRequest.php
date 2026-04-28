<?php

namespace App\Http\Requests\Tournaments;

use Illuminate\Foundation\Http\FormRequest;

class GeneratePlayoffDrawRequest extends FormRequest
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
            'team_ids' => ['required', 'array', 'min:2'],
            'team_ids.*' => ['integer', 'distinct', 'exists:tournament_teams,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'team_ids' => 'equipos participantes',
            'team_ids.*' => 'equipo participante',
        ];
    }
}
