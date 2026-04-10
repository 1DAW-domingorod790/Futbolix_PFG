<?php

namespace App\Http\Requests\Tournaments;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTournamentPlayerRequest extends FormRequest
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
            'dni' => ['required', 'string', 'max:20', Rule::unique('tournament_players', 'dni')],
            'name' => ['required', 'string', 'max:120'],
            'number' => ['required', 'integer', 'min:1', 'max:99'],
            'age' => ['nullable', 'integer', 'min:1', 'max:99'],
            'goals' => ['nullable', 'integer', 'min:0', 'max:999'],
            'photo_path' => ['nullable', 'image', 'max:2048', 'mimes:jpeg,png,jpg,webp'],
        ];
    }
}
