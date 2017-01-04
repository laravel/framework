<?php

namespace Illuminate\Tests\Validation;

use PHPUnit\Framework\TestCase;

class ValidationUniqueRuleTest extends TestCase
{
    public function testItCorrectlyFormatsAStringVersionOfTheRule()
    {
        $rule = new \Illuminate\Validation\Rules\Unique('table');
        $rule->where('foo', 'bar');
        $this->assertEquals('unique:table,NULL,NULL,id,foo,bar', (string) $rule);

        $rule = new \Illuminate\Validation\Rules\Unique('table', 'column');
        $rule->ignore('Taylor, Otwell', 'id_column');
        $rule->where('foo', 'bar');
        $this->assertEquals('unique:table,column,"Taylor, Otwell",id_column,foo,bar', (string) $rule);

        $rule = new \Illuminate\Validation\Rules\Unique('table', 'column');
        $rule->ignore(null, 'id_column');
        $rule->where('foo', 'bar');
        $this->assertEquals('unique:table,column,NULL,id_column,foo,bar', (string) $rule);
    }
}
