<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Validation\Rule;
use PHPUnit\Framework\TestCase;
use Illuminate\Validation\Rules\In;

class ValidationInRuleTest extends TestCase
{
    public function testItCorrectlyFormatsAStringVersionOfTheRule()
    {
        $rule = new In(['Laravel', 'Framework', 'PHP']);

        $this->assertEquals('in:Laravel,Framework,PHP', (string) $rule);

        $rule = Rule::in([1, 2, 3, 4]);

        $this->assertEquals('in:1,2,3,4', (string) $rule);
    }
}
