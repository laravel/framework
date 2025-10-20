<?php

namespace Illuminate\Tests\Integration\Validation\Rules;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Orchestra\Testbench\TestCase;

class PasswordValidationTest extends TestCase
{
    /**
     * @dataProvider attributeProvider
     */
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

    /**
     * @dataProvider attributeProvider
     */
    public function test_it_can_validate_attribute_as_array_when_validation_should_fails(string $attribute)
    {
        $validator = Validator::make([
            'passwords' => [
                $attribute => '12345',
            ],
        ], [
            'passwords.*' => ['required', Password::default()->min(6)],
        ]);

        $this->assertFalse($validator->passes());

        $messages = $validator->messages()->all();
        $this->assertCount(1, $messages);
        // The message should contain the proper attribute name
        $this->assertStringContainsString('passwords', $messages[0]);
    }

    public static function attributeProvider()
    {
        return [
            ['0'],
            ['.'],
            ['*'],
            ['__asterisk__'],
        ];
    }
}
