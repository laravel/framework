<?php

use Illuminate\Validation\Rule;

class ValidationFluentRulesTest extends PHPUnit_Framework_TestCase
{
    public function testItCorrectlyFormatsAStringVersionOfTheDimensionsRule()
    {
        $rule = new Illuminate\Validation\Rules\Dimensions(['min_width' => 100, 'min_height' => 100]);

        $this->assertEquals('dimensions:min_width=100,min_height=100', (string) $rule);

        $rule = Rule::dimensions()->width(200)->height(100);

        $this->assertEquals('dimensions:width=200,height=100', (string) $rule);

        $rule = Rule::dimensions()->maxWidth(1000)->maxHeight(500)->ratio(3 / 2);

        $this->assertEquals('dimensions:max_width=1000,max_height=500,ratio=1.5', (string) $rule);
    }

    public function testItCorrectlyFormatsAStringVersionOfTheExistsRule()
    {
        $rule = new Illuminate\Validation\Rules\Exists('table');
        $rule->where('foo', 'bar');
        $this->assertEquals('exists:table,NULL,foo,bar', (string) $rule);

        $rule = new Illuminate\Validation\Rules\Exists('table', 'column');
        $rule->where('foo', 'bar');
        $this->assertEquals('exists:table,column,foo,bar', (string) $rule);
    }

    public function testItCorrectlyFormatsAStringVersionOfTheInRule()
    {
        $rule = new Illuminate\Validation\Rules\In(['Laravel', 'Framework', 'PHP']);

        $this->assertEquals('in:Laravel,Framework,PHP', (string) $rule);

        $rule = Rule::in([1, 2, 3, 4]);

        $this->assertEquals('in:1,2,3,4', (string) $rule);
    }

    public function testItCorrectlyFormatsAStringVersionOfTheNotInRule()
    {
        $rule = new Illuminate\Validation\Rules\NotIn(['Laravel', 'Framework', 'PHP']);

        $this->assertEquals('not_in:Laravel,Framework,PHP', (string) $rule);

        $rule = Rule::notIn([1, 2, 3, 4]);

        $this->assertEquals('not_in:1,2,3,4', (string) $rule);
    }

    public function testItCorrectlyFormatsAStringVersionOfTheUniqueRule()
    {
        $rule = new Illuminate\Validation\Rules\Unique('table');
        $rule->where('foo', 'bar');
        $this->assertEquals('unique:table,NULL,NULL,id,foo,bar', (string) $rule);

        $rule = new Illuminate\Validation\Rules\Unique('table', 'column');
        $rule->ignore('Taylor, Otwell', 'id_column');
        $rule->where('foo', 'bar');
        $this->assertEquals('unique:table,column,"Taylor, Otwell",id_column,foo,bar', (string) $rule);

        $rule = new Illuminate\Validation\Rules\Unique('table', 'column');
        $rule->ignore(null, 'id_column');
        $rule->where('foo', 'bar');
        $this->assertEquals('unique:table,column,NULL,id_column,foo,bar', (string) $rule);
    }
}
