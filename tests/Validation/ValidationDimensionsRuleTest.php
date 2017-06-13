<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Validation\Rule;
use PHPUnit\Framework\TestCase;
use Illuminate\Validation\Rules\Dimensions;

class ValidationDimensionsRuleTest extends TestCase
{
    public function testItCorrectlyFormatsAStringVersionOfTheRule()
    {
        $rule = new Dimensions(['min_width' => 100, 'min_height' => 100]);

        $this->assertEquals('dimensions:min_width=100,min_height=100', (string) $rule);

        $rule = Rule::dimensions()->width(200)->height(100);

        $this->assertEquals('dimensions:width=200,height=100', (string) $rule);

        $rule = Rule::dimensions()->maxWidth(1000)->maxHeight(500)->ratio(3 / 2);

        $this->assertEquals('dimensions:max_width=1000,max_height=500,ratio=1.5', (string) $rule);

        $rule = new Dimensions(['ratio' => '2/3']);

        $this->assertEquals('dimensions:ratio=2/3', (string) $rule);
    }
}
