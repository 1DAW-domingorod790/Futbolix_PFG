<?php

namespace App\Http\Requests\Tournaments;

use App\Rules\ValidSpanishDni;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTournamentPlayerRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'dni' => strtoupper(str_replace([' ', '-'], '', (string) $this->input('dni'))),
        ]);
    }

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
            'dni' => ['required', 'string', 'size:9', new ValidSpanishDni(), Rule::unique('tournament_players', 'dni')],
            'name' => ['required', 'string', 'max:120'],
            'number' => ['required', 'integer', 'min:1', 'max:99'],
            'birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'goals' => ['nullable', 'integer', 'min:0', 'max:999'],
            'photo_path' => ['nullable', 'image', 'max:2048', 'mimes:jpeg,png,jpg,webp'],
        ];
    }
}
