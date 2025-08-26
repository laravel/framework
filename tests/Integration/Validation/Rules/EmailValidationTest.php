<?php

namespace Illuminate\Tests\Integration\Validation\Rules;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Email;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\TestWith;

class EmailValidationTest extends TestCase
{
    #[TestWith(['0'])]
    #[TestWith(['.'])]
    #[TestWith(['*'])]
    #[TestWith(['__asterisk__'])]
    public function test_it_can_validate_attribute_as_array(string $attribute)
    {
        $validator = Validator::make([
            'emails' => [
                $attribute => 'taylor@laravel.com',
            ],
        ], [
            'emails.*' => ['required', Email::default()->rfcCompliant()],
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
            'emails' => [
                $attribute => 'taylor[at]laravel.com',
            ],
        ], [
            'emails.*' => ['required', Email::default()->rfcCompliant()],
        ]);

        $this->assertFalse($validator->passes());

        $this->assertSame([
            0 => __('validation.email', ['attribute' => sprintf('emails.%s', str_replace('_', ' ', $attribute))]),
        ], $validator->messages()->all());
    }
}
