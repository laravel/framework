<?php

use Illuminate\Validation\Rule;

class ValidationRuleTest extends PHPUnit_Framework_TestCase
{
    public function testMacroable()
    {
        // phone macro : validate a phone number
        Rule::macro('phone', function () {
            return 'regex:/^([0-9\s\-\+\(\)]*)$/';
        });
        $c = Rule::phone();
        $this->assertSame('regex:/^([0-9\s\-\+\(\)]*)$/', $c);
    }

    public function testChainingFluentRules()
    {
        $this->assertSame(
            'in:1,2,3|not_in:5,6',
            (string) Rule::in([1, 2, 3])->also()->notIn([5, 6])
        );

        $this->assertSame(
            'exists:users,name,foo,bar|in:1,2',
            (string) Rule::exists('users', 'name')->where('foo', 'bar')->also()->in([1, 2])
        );
    }
}
