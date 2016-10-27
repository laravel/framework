<?php

class ValidationExistsRuleTest extends PHPUnit_Framework_TestCase
{
    public function testItCorrectlyFormatsAStringVersionOfTheRule()
    {
        $rule = new Illuminate\Validation\Rules\Exists('table');
        $rule->where('foo', 'bar');
        $this->assertEquals('exists:table,NULL,foo,bar', (string) $rule);

        $rule = new Illuminate\Validation\Rules\Exists('table', 'column');
        $rule->where('foo', 'bar');
        $this->assertEquals('exists:table,column,foo,bar', (string) $rule);
    }
}
