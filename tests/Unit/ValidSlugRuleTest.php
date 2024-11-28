<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Rules\ValidSlugRule;

class ValidSlugRuleTest extends TestCase
{
     /**
     * @test Verifica que un slug inválido no pasa la validación.
     */
    public function testInvalidSlugFailsValidation()
    {
        $rule = new ValidSlugRule();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('El Slug es inválido');

        $rule->validate('slug', 'invalid slug!', function ($message) {
            throw new \Exception($message);
        });
    }

    /**
     * @test Verifica que un slug con caracteres especiales no pasa la validación.
     */
    public function testSlugWithSpecialCharactersFailsValidation()
    {
        $rule = new ValidSlugRule();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('El Slug es inválido');

        $rule->validate('slug', 'slug@special#', function ($message) {
            throw new \Exception($message);
        });
    }
}
