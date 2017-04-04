<?php

use Mockery as m;

class ValidationSometimesRuleTest extends PHPUnit_Framework_TestCase
{
    public function testSometimesRuleCorrectlyApplied()
    {
        $rule = new Illuminate\Validation\Rules\Sometimes('required', 'ValidationSometimesRuleTestRule');

        $validator = m::mock(Illuminate\Validation\Validator::class.'[sometimes]', [
            m::mock(Symfony\Component\Translation\TranslatorInterface::class),
            [],
            ['field' => 'test'],
        ])->makePartial();

        $rule->apply($validator, 'field');
        $validator->shouldHaveReceived('sometimes')->once();
    }
}

function ValidationSometimesRuleTestRule()
{
    return true;
}
