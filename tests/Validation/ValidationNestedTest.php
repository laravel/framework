<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class ValidationNestedTest extends TestCase
{
    public function testNestedCallbacksCanProperlySegmentRules()
    {
        $data = [
            'items' => [
                // Contains duplicate ID.
                ['discounts' => [['id' => 1], ['id' => 1], ['id' => 2]]],
                ['discounts' => [['id' => 1], ['id' => 2]]],
            ],
        ];

        $rules = [
            'items.*' => Rule::nested(function () {
                return ['discounts.*.id' => 'distinct'];
            }),
        ];

        $trans = $this->getIlluminateArrayTranslator();

        $v = new Validator($trans, $data, $rules);

        $this->assertFalse($v->passes());

        $this->assertEquals([
            'items.0.discounts.0.id' => ['validation.distinct'],
            'items.0.discounts.1.id' => ['validation.distinct'],
        ], $v->getMessageBag()->toArray());
    }

    public function testNestedCallbacksCanBeRecursivelyNested()
    {
        $data = [
            'items' => [
                // Contains duplicate ID.
                ['discounts' => [['id' => 1], ['id' => 1], ['id' => 2]]],
                ['discounts' => [['id' => 1], ['id' => 2]]],
            ],
        ];

        $rules = [
            'items.*' => Rule::nested(function () {
                return [
                    'discounts.*.id' => Rule::nested(function () {
                        return 'distinct';
                    }),
                ];
            }),
        ];

        $trans = $this->getIlluminateArrayTranslator();

        $v = new Validator($trans, $data, $rules);

        $this->assertFalse($v->passes());

        $this->assertEquals([
            'items.0.discounts.0.id' => ['validation.distinct'],
            'items.0.discounts.1.id' => ['validation.distinct'],
        ], $v->getMessageBag()->toArray());
    }

    public function testNestedCallbacksCanReturnMultipleValidationRules()
    {
        $data = [
            'items' => [
                [
                    'discounts' => [
                        ['id' => 1, 'percent' => 30, 'discount' => 1400],
                        ['id' => 1, 'percent' => -1, 'discount' => 12300],
                        ['id' => 2, 'percent' => 120, 'discount' => 1200],
                    ],
                ],
                [
                    'discounts' => [
                        ['id' => 1, 'percent' => 30, 'discount' => 'invalid'],
                        ['id' => 2, 'percent' => 'invalid', 'discount' => 1250],
                        ['id' => 3, 'percent' => 'invalid', 'discount' => 'invalid'],
                    ],
                ],
            ],
        ];
        $rules = [
            'items.*' => Rule::nested(function () {
                return [
                    'discounts.*.id' => 'distinct',
                    'discounts.*' => Rule::nested(function () {
                        return [
                            'id' => 'distinct',
                            'percent' => 'numeric|min:0|max:100',
                            'discount' => 'numeric',
                        ];
                    }),
                ];
            }),
        ];

        $trans = $this->getIlluminateArrayTranslator();

        $v = new Validator($trans, $data, $rules);

        $this->assertFalse($v->passes());

        $this->assertEquals([
            'items.0.discounts.0.id' => ['validation.distinct'],
            'items.0.discounts.1.id' => ['validation.distinct'],
            'items.0.discounts.1.percent' => ['validation.min.numeric'],
            'items.0.discounts.2.percent' => ['validation.max.numeric'],
            'items.1.discounts.0.discount' => ['validation.numeric'],
            'items.1.discounts.1.percent' => ['validation.numeric'],
            'items.1.discounts.2.percent' => ['validation.numeric'],
            'items.1.discounts.2.discount' => ['validation.numeric'],
        ], $v->getMessageBag()->toArray());
    }

    protected function getTranslator()
    {
        return m::mock(TranslatorContract::class);
    }

    public function getIlluminateArrayTranslator()
    {
        return new Translator(
            new ArrayLoader, 'en'
        );
    }
}
