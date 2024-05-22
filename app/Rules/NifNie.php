<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NifNie implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $nifRexp = "^[0-9]{8}[A-Z]$";
        $nieRexp = "^[XYZ][0-9]{7}[A-Z]$";
        $str = strtoupper($value);

        if (preg_match("/$nifRexp/i", $str) || preg_match("/$nieRexp/i", $str)) {
            $nie = str_replace(array('X', 'Y', 'Z'), array('0', '1', '2'), $str);
            $letter = substr($nie, -1);
            $num = substr($nie, 0, 8);
            $correctLetter = substr('TRWAGMYFPDXBNJZSQVHLCKE', strtr($num, 'XYZ', '012') % 23, 1);
            if (strtoupper($letter) !== $correctLetter) {
                $fail('El NIF/NIE es inválido');
            }
        } else {
            $fail('El NIF/NIE es inválido');
        }
    }
}
