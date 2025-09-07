<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use PHPUnit\Framework\TestCase;

class ValidationForEachTest extends TestCase
{
    public function testForEachCallbacksCanProperlySegmentRules()
    {
        $data = [
            'items' => [
                // Contains duplicate ID.
                ['discounts' => [['id' => 1], ['id' => 1], ['id' => 2]]],
                ['discounts' => [['id' => 1], ['id' => 2]]],
            ],
        ];

        $rules = [
            'items.*' => Rule::forEach(function () {
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

    public function testForEachCallbacksCanBeRecursivelyNested()
    {
        $data = [
            'items' => [
                // Contains duplicate ID.
                ['discounts' => [['id' => 1], ['id' => 1], ['id' => 2]]],
                ['discounts' => [['id' => 1], ['id' => 2]]],
            ],
        ];

        $rules = [
            'items.*' => Rule::forEach(function () {
                return [
                    'discounts.*.id' => Rule::forEach(function () {
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

    public function testForEachCallbacksCanReturnMultipleValidationRules()
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
            'items.*' => Rule::forEach(function () {
                return [
                    'discounts.*.id' => 'distinct',
                    'discounts.*' => Rule::forEach(function () {
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

    public function testForEachCallbacksCanReturnArraysOfValidationRules()
    {
        $data = [
            'items' => [
                // Contains duplicate ID.
                ['discounts' => [['id' => 1], ['id' => 1], ['id' => 2]]],
                ['discounts' => [['id' => 1], ['id' => 'invalid']]],
            ],
        ];

        $rules = [
            'items.*' => Rule::forEach(function () {
                return ['discounts.*.id' => ['distinct', 'numeric']];
            }),
        ];

        $trans = $this->getIlluminateArrayTranslator();

        $v = new Validator($trans, $data, $rules);

        $this->assertFalse($v->passes());

        $this->assertEquals([
            'items.0.discounts.0.id' => ['validation.distinct'],
            'items.0.discounts.1.id' => ['validation.distinct'],
            'items.1.discounts.1.id' => ['validation.numeric'],
        ], $v->getMessageBag()->toArray());
    }

    public function testForEachCallbacksCanReturnDifferentRules()
    {
        $data = [
            'items' => [
                [
                    'discounts' => [
                        ['id' => 1, 'type' => 'percent', 'discount' => 120],
                        ['id' => 1, 'type' => 'absolute', 'discount' => 100],
                        ['id' => 2, 'type' => 'percent', 'discount' => 50],
                    ],
                ],
                [
                    'discounts' => [
                        ['id' => 2, 'type' => 'percent', 'discount' => 'invalid'],
                        ['id' => 3, 'type' => 'absolute', 'discount' => 2000],
                    ],
                ],
            ],
        ];

        $rules = [
            'items.*' => Rule::forEach(function () {
                return [
                    'discounts.*.id' => 'distinct',
                    'discounts.*.type' => 'in:percent,absolute',
                    'discounts.*' => Rule::forEach(function ($value) {
                        return $value['type'] === 'percent'
                            ? ['discount' => 'numeric|min:0|max:100']
                            : ['discount' => 'numeric'];
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
            'items.0.discounts.0.discount' => ['validation.max.numeric'],
            'items.1.discounts.0.discount' => ['validation.numeric'],
        ], $v->getMessageBag()->toArray());
    }

    public function testForEachCallbacksDoNotBreakRegexRules()
    {
        $data = [
            'items' => [
                ['users' => [['type' => 'super'], ['type' => 'invalid']]],
            ],
        ];

        $rules = [
            'items.*' => Rule::forEach(function () {
                return ['users.*.type' => 'regex:/^(super)$/i'];
            }),
        ];

        $trans = $this->getIlluminateArrayTranslator();

        $v = new Validator($trans, $data, $rules);

        $this->assertFalse($v->passes());

        $this->assertEquals([
            'items.0.users.1.type' => ['validation.regex'],
        ], $v->getMessageBag()->toArray());
    }

    public function testForEachCallbacksCanContainMultipleRegexRules()
    {
        $data = [
            'items' => [
                ['users' => [['type' => 'super'], ['type' => 'invalid']]],
            ],
        ];

        $rules = [
            'items.*' => Rule::forEach(function () {
                return ['users.*.type' => [
                    'regex:/^(super)$/i',
                    'notregex:/^(invalid)$/i',
                ]];
            }),
        ];

        $trans = $this->getIlluminateArrayTranslator();

        $v = new Validator($trans, $data, $rules);

        $this->assertFalse($v->passes());

        $this->assertEquals([
            'items.0.users.1.type' => [
                'validation.regex',
                'validation.notregex',
            ],
        ], $v->getMessageBag()->toArray());
    }

    public function testConditionalRulesCanBeAddedToForEachWithAssociativeArray()
    {
        $v = new Validator(
            $this->getIlluminateArrayTranslator(),
            [
                'foo' => [
                    ['bar' => true],
                    ['bar' => false],
                ],
            ],
            [
                'foo.*' => Rule::forEach(fn (mixed $value, string $attribute) => [
                    'bar' => Rule::when(true, ['accepted'], ['declined']),
                ]),
            ]
        );

        $this->assertEquals([
            'foo.1.bar' => ['validation.accepted'],
        ], $v->getMessageBag()->toArray());
    }

    public function testConditionalRulesCanBeAddedToForEachWithList()
    {
        $v = new Validator(
            $this->getIlluminateArrayTranslator(),
            [
                'foo' => [
                    ['bar' => true],
                    ['bar' => false],
                ],
            ],
            [
                'foo.*.bar' => Rule::forEach(fn (mixed $value, string $attribute) => [
                    Rule::when(true, ['accepted'], ['declined']),
                ]),
            ]);

        $this->assertEquals([
            'foo.1.bar' => ['validation.accepted'],
        ], $v->getMessageBag()->toArray());
    }

    public function testConditionalRulesCanBeAddedToForEachWithObject()
    {
        $v = new Validator(
            $this->getIlluminateArrayTranslator(),
            [
                'foo' => [
                    ['bar' => true],
                    ['bar' => false],
                ],
            ],
            [
                'foo.*.bar' => Rule::forEach(fn (mixed $value, string $attribute) => Rule::when(true, ['accepted'], ['declined']),
                ),
            ]);

        $this->assertEquals([
            'foo.1.bar' => ['validation.accepted'],
        ], $v->getMessageBag()->toArray());
    }

    public function testForEachWithEmptyAndNullValues()
    {
        $data = [
            'items' => [
                ['discounts' => null],
                ['discounts' => []],
                ['discounts' => [null]],
            ],
        ];

        $rules = [
            'items.*' => Rule::forEach(function () {
                return [
                    'discounts' => 'required|array',
                    'discounts.*' => 'required|array',
                ];
            }),
        ];

        $v = new Validator($this->getIlluminateArrayTranslator(), $data, $rules);
        $this->assertFalse($v->passes());
        $this->assertEquals(
            [
                'items.0.discounts' => ['validation.required'],
                'items.1.discounts' => ['validation.required'],
                'items.2.discounts.0' => ['validation.required'],
            ],
            $v->getMessageBag()->toArray()
        );
    }

    public function getIlluminateArrayTranslator()
    {
        return new Translator(
            new ArrayLoader, 'en'
        );
    }
}
