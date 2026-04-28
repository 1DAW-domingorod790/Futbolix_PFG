<?php

namespace App\Http\Requests\Tournaments;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdatePlayoffMatchResultRequest extends FormRequest
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
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ((int) $this->input('home_score') === (int) $this->input('away_score')) {
                    $validator->errors()->add('away_score', 'Un partido de playoffs necesita un ganador. No puede terminar empatado.');
                }
            },
        ];
    }
}
