<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidSpanishDni implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value) || !preg_match('/^(\d{8})([A-Z])$/', $value, $matches)) {
            $fail('El DNI debe tener 8 numeros y una letra valida.');

            return;
        }

        $letters = 'TRWAGMYFPDXBNJZSQVHLCKE';
        $number = (int) $matches[1];
        $letter = $matches[2];
        $expectedLetter = $letters[$number % 23];

        if ($letter !== $expectedLetter) {
            $fail('La letra del DNI no coincide con el numero indicado.');
        }
    }
}
