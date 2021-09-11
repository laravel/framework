<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Dimensions;
use PHPUnit\Framework\TestCase;

class ValidationDimensionsRuleTest extends TestCase
{
    public function testItCorrectlyFormatsAStringVersionOfTheRule()
    {
        $rule = new Dimensions(['min_width' => 100, 'min_height' => 100]);

        $this->assertSame('dimensions:min_width=100,min_height=100', (string) $rule);

        $rule = Rule::dimensions()->width(200)->height(100);

        $this->assertSame('dimensions:width=200,height=100', (string) $rule);

        $rule = Rule::dimensions()->maxWidth(1000)->maxHeight(500)->ratio(3 / 2);

        $this->assertSame('dimensions:max_width=1000,max_height=500,ratio=1.5', (string) $rule);

        $rule = new Dimensions(['ratio' => '2/3']);

        $this->assertSame('dimensions:ratio=2/3', (string) $rule);

        $rule = Rule::dimensions()->minWidth(300)->minHeight(400);

        $this->assertSame('dimensions:min_width=300,min_height=400', (string) $rule);

        $rule = Rule::dimensions()
            ->when(true, function ($rule) {
                $rule->height('100');
            })
            ->unless(true, function ($rule) {
                $rule->width('200');
            });
        $this->assertSame('dimensions:height=100', (string) $rule);
    }
}
