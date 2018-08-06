<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Validation\Rules\Conditional;
use PHPUnit\Framework\TestCase;

class ValidationConditionalRuleTest extends TestCase
{
    public function testItReturnsTheCorrectSetOfRules()
    {
        $rule = (new Conditional(true))
            ->passes('required', 'email')
            ->fails('nullable', 'url');

        $this->assertEquals(['required', 'email'], $rule->getRules());

        $rule = (new Conditional(false))
            ->passes('required', 'email')
            ->fails('nullable', 'url');

        $this->assertEquals(['nullable', 'url'], $rule->getRules());
    }

    public function testItAcceptsCallbacksThatReturnABool()
    {
        $callback = function () {
            return true;
        };

        $rule = (new Conditional($callback))
            ->passes(['required', 'email']);

        $this->assertEquals(['required', 'email'], $rule->getRules());
    }
}
