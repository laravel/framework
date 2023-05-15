<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Validation\Rules\OrRule;
use Illuminate\Validation\Validator;
use PHPUnit\Framework\TestCase;

class ValidationOrRuleTest extends TestCase
{
    public function testValidationPassesWithCorrectOrRule()
    {
        $validator = new Validator(
            resolve('translator'),
            [
                'name' => 'Taylor Otwell',
            ],
            [
                'name' => new OrRule('starts_with:Otwell', 'ends_with:Otwell'),
            ]
        );

        $this->assertTrue($validator->passes());
    }

    public function testValidationFailsWithWrongOrRule()
    {
        $validator = new Validator(
            resolve('translator'),
            [
                'name' => 'Thomas Omweri',
            ],
            [
                'name' => new OrRule('starts_with:Otwell', 'ends_with:Otwell'),
            ]
        );

        $this->assertFalse($validator->passes());
    }
}
