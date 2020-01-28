<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Validation\Rules\In;
use Illuminate\Validation\Ruleset;
use PHPUnit\Framework\TestCase;

class ValidationRulesetTest extends TestCase
{
    public function testAddsStringRules()
    {
        $ruleset = new Ruleset();
        $ruleset->rule('required');
        $this->assertSame(['required'], $ruleset->toArray());

        $ruleset = new Ruleset();
        $ruleset->required();
        $this->assertSame(['required'], $ruleset->toArray());

        $ruleset = Ruleset::required();
        $this->assertSame(['required'], $ruleset->toArray());
    }

    public function testAddsObjectRules()
    {
        $ruleset = new Ruleset();
        $rule = new In(['Laravel', 'Framework', 'PHP']);
        $ruleset->rule($rule);

        $this->assertSame([$rule], $ruleset->toArray());
    }

    public function testAddsClosureRules()
    {
        $ruleset = new Ruleset();
        $rule = function () {};
        $ruleset->rule($rule);

        $this->assertSame([$rule], $ruleset->toArray());
    }

    public function testAddsStringRulesWithParameters()
    {
        $ruleset = Ruleset::min(3);
        $this->assertSame(['min:3'], $ruleset->toArray());

        $ruleset = Ruleset::between(1, 10);
        $this->assertSame(['between:1,10'], $ruleset->toArray());
    }

    public function testAddsStringRulesWithArrayParameters()
    {
        $ruleset = Ruleset::startsWith('foo', 'bar');
        $this->assertSame(['starts_with:foo,bar'], $ruleset->toArray());

        $ruleset = Ruleset::startsWith(['foo', 'bar']);
        $this->assertSame(['starts_with:foo,bar'], $ruleset->toArray());
    }
}
