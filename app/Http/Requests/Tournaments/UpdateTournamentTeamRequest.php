<?php

namespace App\Http\Requests\Tournaments;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTournamentTeamRequest extends FormRequest
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
            'badge' => ['nullable', 'image', 'max:2048', 'mimes:jpeg,png,jpg,webp'],
        ];
    }
}
