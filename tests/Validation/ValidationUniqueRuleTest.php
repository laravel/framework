<?php

class ValidationUniqueRuleTest extends PHPUnit_Framework_TestCase
{
    public function testItCorrectlyFormatsAStringVersionOfTheRule()
    {
        $rule = new Illuminate\Validation\Rules\Unique('table');
        $rule->where('foo', 'bar');
        $this->assertEquals('unique:table,NULL,NULL,id,foo,bar', (string) $rule);

        $rule = new Illuminate\Validation\Rules\Unique('table', 'column');
        $rule->ignore(1, 'id_column');
        $rule->where('foo', 'bar');
        $this->assertEquals('unique:table,column,1,id_column,foo,bar', (string) $rule);
    }
}
