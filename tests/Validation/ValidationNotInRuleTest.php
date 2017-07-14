<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Validation\Rule;
use Illuminate\Tests\AbstractTestCase as TestCase;
use Illuminate\Validation\Rules\NotIn;

class ValidationNotInRuleTest extends TestCase
{
    public function testItCorrectlyFormatsAStringVersionOfTheRule()
    {
        $rule = new NotIn(['Laravel', 'Framework', 'PHP']);

        $this->assertEquals('not_in:Laravel,Framework,PHP', (string) $rule);

        $rule = Rule::notIn([1, 2, 3, 4]);

        $this->assertEquals('not_in:1,2,3,4', (string) $rule);
    }
}
