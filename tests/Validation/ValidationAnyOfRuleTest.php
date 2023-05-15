<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Validation\Rules\AnyOf;
use Illuminate\Validation\Validator;
use PHPUnit\Framework\TestCase;

class ValidationAnyOfRuleTest extends TestCase
{
    public function testValidationPassesWithCorrectAnyOfRule()
    {
        $validator = new Validator(
            resolve('translator'),
            [
                'value' => 'Laravel',
            ],
            [
                'value' => new AnyOf(['string', 'decimal', 'integer']),
            ]
        );

        $this->assertTrue($validator->passes());
    }

    public function testValidationFailsWithWrongAnyOfRule()
    {
        $validator = new Validator(
            resolve('translator'),
            [
                'value' => ['Laravel', 'Other'],
            ],
            [
                'value' => new AnyOf(['string', 'decimal', 'integer']),
            ]
        );

        $this->assertFalse($validator->passes());
    }
}
