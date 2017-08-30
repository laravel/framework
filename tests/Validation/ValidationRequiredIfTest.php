<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Validation\Rule;
use PHPUnit\Framework\TestCase;
use Illuminate\Validation\Rules\RequiredIf;

class ValidationRequiredIfRuleTest extends TestCase
{
    public function testItCorrectlyFormatsAStringVersionOfTheRule()
    {
        $rule = new RequiredIf('name', 'Mateus');

        $this->assertEquals('required_if:name,Mateus', (string) $rule);

        $rule = Rule::requiredIf('name', 'Taylor');

        $this->assertEquals('required_if:name,Taylor', (string) $rule);
    }
}
