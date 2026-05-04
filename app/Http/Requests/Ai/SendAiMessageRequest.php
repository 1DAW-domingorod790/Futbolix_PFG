<?php

namespace App\Http\Requests\Ai;

use Illuminate\Foundation\Http\FormRequest;

class SendAiMessageRequest extends FormRequest
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
            'message' => ['required', 'string', 'min:2', 'max:8000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'message.required' => 'Escribe un mensaje para Futbolix AI.',
            'message.min' => 'El mensaje debe tener al menos 2 caracteres.',
            'message.max' => 'El mensaje no puede superar los 8000 caracteres.',
        ];
    }
}
