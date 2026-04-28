<?php

namespace App\Http\Requests\Tournaments;

use App\Enums\Tournaments\TournamentFormat;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreTournamentRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'format' => ['required', Rule::enum(TournamentFormat::class)],
            'playoff_teams_count' => ['nullable', 'integer', 'min:2', 'max:64'],
            'groups_count' => ['nullable', 'integer', 'min:1', 'max:64'],
            'regular_phase_matchdays_count' => ['nullable', 'integer', 'min:1', 'max:100'],
            'logo_path' => ['nullable', 'image', 'max:2048', 'mimes:jpeg,png,jpg,webp'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $format = TournamentFormat::tryFrom((string) $this->input('format'));

                if (!$format) {
                    return;
                }

                if ($format->hasPlayoffs() && !$this->filled('playoff_teams_count')) {
                    $validator->errors()->add(
                        'playoff_teams_count',
                        'Indica el numero de equipos que participaran en playoffs.'
                    );
                }

                if (
                    $format->hasPlayoffs()
                    && $this->filled('playoff_teams_count')
                    && !$this->isPowerOfTwo((int) $this->input('playoff_teams_count'))
                ) {
                    $validator->errors()->add(
                        'playoff_teams_count',
                        'El numero de equipos de playoffs debe ser una potencia de 2: 2, 4, 8, 16, 32 o 64.'
                    );
                }

                if ($format->hasGroups() && !$this->filled('groups_count')) {
                    $validator->errors()->add(
                        'groups_count',
                        'Indica el numero de grupos de la primera fase.'
                    );
                }

                if ($format->hasRegularPhase() && !$this->filled('regular_phase_matchdays_count')) {
                    $validator->errors()->add(
                        'regular_phase_matchdays_count',
                        'Indica el numero de jornadas de la fase previa.'
                    );
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nombre del torneo',
            'description' => 'descripcion',
            'format' => 'formato del torneo',
            'playoff_teams_count' => 'equipos de playoffs',
            'groups_count' => 'numero de grupos',
            'regular_phase_matchdays_count' => 'numero de jornadas de la fase previa',
            'logo_path' => 'logo del torneo',
        ];
    }

    private function isPowerOfTwo(int $value): bool
    {
        return in_array($value, [2, 4, 8, 16, 32, 64], true);
    }
}
