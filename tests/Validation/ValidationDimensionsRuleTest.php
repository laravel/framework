<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Http\UploadedFile;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Dimensions;
use Illuminate\Validation\Validator;
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

        $rule = Rule::dimensions()
            ->minRatio(1 / 2)
            ->maxRatio(1 / 3);
        $this->assertSame('dimensions:min_ratio=0.5,max_ratio=0.33333333333333', (string) $rule);

        $rule = Rule::dimensions()
            ->ratioBetween(min: 1 / 2, max: 1 / 3);
        $this->assertSame('dimensions:min_ratio=0.5,max_ratio=0.33333333333333', (string) $rule);
    }

    public function testItCorrectlyFormatsWithSpecialValues()
    {
        $rule = new Dimensions();

        $this->assertSame('dimensions:', (string) $rule);

        $rule = Rule::dimensions()->width(-100)->height(-200);

        $this->assertSame('dimensions:width=-100,height=-200', (string) $rule);

        $rule = Rule::dimensions()->width('300')->height('400');

        $this->assertSame('dimensions:width=300,height=400', (string) $rule);
    }

    public function testDimensionsRuleMaintainsCorrectOrder()
    {
        $rule = Rule::dimensions()->minWidth(100)->width(200)->maxWidth(300);

        $this->assertSame('dimensions:min_width=100,width=200,max_width=300', (string) $rule);
    }

    public function testOverridingValues()
    {
        $rule = Rule::dimensions()->width(100)->width(500);

        $this->assertSame('dimensions:width=500', (string) $rule);
    }

    public function testRatioBetweenOverridesMinAndMaxRatio()
    {
        $rule = Rule::dimensions()->minRatio(0.5)->maxRatio(2.0)->ratioBetween(1, 1.5);

        $this->assertSame('dimensions:min_ratio=1,max_ratio=1.5', (string) $rule);
    }

    public function testGeneratesTheCorrectValidationMessages()
    {
        $rule = Rule::dimensions()
            ->width(100)->height(100)
            ->ratioBetween(min: 1 / 2, max: 2 / 5);

        $trans = new Translator(new ArrayLoader, 'en');

        $image = UploadedFile::fake();

        $validator = new Validator(
            $trans,
            ['image' => $image],
            ['image' => $rule]
        );

        $this->assertSame(
            $trans->get('validation.dimensions', ['width' => 100, 'height' => 100, 'min_ratio' => 0.5, 'max_ratio' => 0.4]),
            $validator->errors()->first('image')
        );

        $validator = new Validator(
            $trans,
            ['image' => $image],
            ['image' => [$rule]]
        );

        $this->assertSame(
            $trans->get('validation.dimensions', ['width' => 100, 'height' => 100, 'min_ratio' => 0.5, 'max_ratio' => 0.4]),
            $validator->errors()->first('image')
        );
    }
}
