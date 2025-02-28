<?php

namespace Illuminate\Tests\Integration\Validation\Rules;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\TestWith;

class PasswordValidationTest extends TestCase
{
    #[TestWith(['0'])]
    #[TestWith(['.'])]
    #[TestWith(['*'])]
    #[TestWith(['__asterisk__'])]
    public function test_it_can_validate_attribute_as_array(string $attribute)
    {
        $validator = Validator::make([
            'passwords' => [
                $attribute => 'secret',
            ],
        ], [
            'passwords.*' => ['required', Password::default()->min(6)],
        ]);

        $this->assertTrue($validator->passes());
    }

    #[TestWith(['0'])]
    #[TestWith(['.'])]
    #[TestWith(['*'])]
    #[TestWith(['__asterisk__'])]
    public function test_it_can_validate_attribute_as_array_when_validation_should_fails(string $attribute)
    {
        $validator = Validator::make([
            'passwords' => [
                $attribute => 'secret',
            ],
        ], [
            'passwords.*' => ['required', Password::default()->min(8)],
        ]);

        $this->assertFalse($validator->passes());

        $this->assertSame([
            0 => sprintf('The passwords.%s field must be at least 8 characters.', str_replace('_', ' ', $attribute)),
        ], $validator->messages()->all());
    }
}
