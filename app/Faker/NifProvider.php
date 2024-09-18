<?php

namespace App\Faker;

use Faker\Provider\Base;

class NifProvider extends Base
{
    /**
     * Generate a valid Spanish NIF.
     *
     * @return string
     */
    public function nif(): string
    {
        $letters = 'TRWAGMYFPDXBNJZSQVHLCKE';
        $numbers = str_pad((string)random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
        $letter = $letters[$numbers % 23];

        return $numbers . $letter;
    }
}
