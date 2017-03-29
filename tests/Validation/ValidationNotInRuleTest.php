<?php

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\NotIn;

class ValidationNotInRuleTest extends PHPUnit_Framework_TestCase
{
    public function testItCorrectlyFormatsAStringVersionOfTheRule()
    {
        $rule = new NotIn(['Laravel', 'Framework', 'PHP']);

        $this->assertEquals('not_in:Laravel,Framework,PHP', (string) $rule);

        $rule = Rule::notIn([1, 2, 3, 4]);

        $this->assertEquals('not_in:1,2,3,4', (string) $rule);
    }

    public function testNotInRuleWorksWithArrayKeys()
    {
        $rule = Rule::notInKeys(['zero' => 0, 'one' => 1, 'two' => 2]);

        $this->assertEquals('not_in:zero,one,two', (string) $rule);
    }
}
