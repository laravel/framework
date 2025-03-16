<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Support\Fluent;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationRuleParser;
use PHPUnit\Framework\TestCase;

class ValidationRuleParserTest extends TestCase
{
    public function testConditionalRulesAreProperlyExpandedAndFiltered()
    {
        $isAdmin = true;

        $rules = ValidationRuleParser::filterConditionalRules([
            'name' => Rule::when($isAdmin, ['required', 'min:2']),
            'email' => Rule::unless($isAdmin, ['required', 'min:2']),
            'password' => Rule::when($isAdmin, 'required|min:2'),
            'username' => ['required', Rule::when($isAdmin, ['min:2'])],
            'address' => ['required', Rule::unless($isAdmin, ['min:2'])],
            'city' => ['required', Rule::when(function (Fluent $input) {
                return true;
            }, ['min:2'])],
            'state' => ['required', Rule::when($isAdmin, function (Fluent $input) {
                return 'min:2';
            })],
            'zip' => ['required', Rule::when($isAdmin, function (Fluent $input) {
                return ['min:2'];
            })],
            'when_cb_true' => Rule::when(fn () => true, ['required'], ['nullable']),
            'when_cb_false' => Rule::when(fn () => false, ['required'], ['nullable']),
            'unless_cb_true' => Rule::unless(fn () => true, ['required'], ['nullable']),
            'unless_cb_false' => Rule::unless(fn () => false, ['required'], ['nullable']),
        ]);

        $this->assertEquals([
            'name' => ['required', 'min:2'],
            'email' => [],
            'password' => ['required', 'min:2'],
            'username' => ['required', 'min:2'],
            'address' => ['required'],
            'city' => ['required', 'min:2'],
            'state' => ['required', 'min:2'],
            'zip' => ['required', 'min:2'],
            'when_cb_true' => ['required'],
            'when_cb_false' => ['nullable'],
            'unless_cb_true' => ['nullable'],
            'unless_cb_false' => ['required'],
        ], $rules);
    }

    public function testEmptyRulesArePreserved()
    {
        $isAdmin = true;

        $rules = ValidationRuleParser::filterConditionalRules([
            'name' => [],
            'email' => '',
            'password' => Rule::when($isAdmin, 'required|min:2'),
            'gender' => Rule::unless($isAdmin, 'required'),
        ]);

        $this->assertEquals([
            'name' => [],
            'email' => '',
            'password' => ['required', 'min:2'],
            'gender' => [],
        ], $rules);
    }

    public function testEmptyRulesCanBeExploded()
    {
        $parser = new ValidationRuleParser(['foo' => 'bar']);

        $this->assertIsObject($parser->explode(['foo' => []]));
    }

    public function testConditionalRulesWithDefault()
    {
        $isAdmin = true;

        $rules = ValidationRuleParser::filterConditionalRules([
            'name' => Rule::when($isAdmin, ['required', 'min:2'], ['string', 'max:10']),
            'email' => Rule::unless($isAdmin, ['required', 'min:2'], ['string', 'max:10']),
            'password' => Rule::unless($isAdmin, 'required|min:2', 'string|max:10'),
            'username' => ['required', Rule::when($isAdmin, ['min:2'], ['string', 'max:10'])],
            'address' => ['required', Rule::unless($isAdmin, ['min:2'], ['string', 'max:10'])],
        ]);

        $this->assertEquals([
            'name' => ['required', 'min:2'],
            'email' => ['string', 'max:10'],
            'password' => ['string', 'max:10'],
            'username' => ['required', 'min:2'],
            'address' => ['required', 'string', 'max:10'],
        ], $rules);
    }

    public function testEmptyConditionalRulesArePreserved()
    {
        $isAdmin = true;

        $rules = ValidationRuleParser::filterConditionalRules([
            'name' => Rule::when($isAdmin, '', ['string', 'max:10']),
            'email' => Rule::unless($isAdmin, ['required', 'min:2']),
            'password' => Rule::unless($isAdmin, 'required|min:2', 'string|max:10'),
        ]);

        $this->assertEquals([
            'name' => [],
            'email' => [],
            'password' => ['string', 'max:10'],
        ], $rules);
    }

    public function testExplodeFailsParsingSingleRegexRuleContainingPipe()
    {
        $data = ['items' => [['type' => 'foo']]];

        $exploded = (new ValidationRuleParser($data))->explode(
            ['items.*.type' => 'regex:/^(foo|bar)$/i']
        );

        $this->assertSame('regex:/^(foo', $exploded->rules['items.0.type'][0]);
        $this->assertSame('bar)$/i', $exploded->rules['items.0.type'][1]);
    }

    public function testExplodeProperlyParsesSingleRegexRuleNotContainingPipe()
    {
        $data = ['items' => [['type' => 'foo']]];

        $exploded = (new ValidationRuleParser($data))->explode(
            ['items.*.type' => 'regex:/^[\d\-]*$/|max:20']
        );

        $this->assertSame('regex:/^[\d\-]*$/', $exploded->rules['items.0.type'][0]);
        $this->assertSame('max:20', $exploded->rules['items.0.type'][1]);
    }

    public function testExplodeProperlyParsesRegexWithArrayOfRules()
    {
        $data = ['items' => [['type' => 'foo']]];

        $exploded = (new ValidationRuleParser($data))->explode(
            ['items.*.type' => ['in:foo', 'regex:/^(foo|bar)$/i']]
        );

        $this->assertSame('in:foo', $exploded->rules['items.0.type'][0]);
        $this->assertSame('regex:/^(foo|bar)$/i', $exploded->rules['items.0.type'][1]);
    }

    public function testExplodeProperlyParsesRegexThatDoesNotContainPipe()
    {
        $data = ['items' => [['type' => 'foo']]];

        $exploded = (new ValidationRuleParser($data))->explode(
            ['items.*.type' => 'in:foo|regex:/^(bar)$/i']
        );

        $this->assertSame('in:foo', $exploded->rules['items.0.type'][0]);
        $this->assertSame('regex:/^(bar)$/i', $exploded->rules['items.0.type'][1]);
    }

    public function testExplodeFailsParsingRegexWithOtherRulesInSingleString()
    {
        $data = ['items' => [['type' => 'foo']]];

        $exploded = (new ValidationRuleParser($data))->explode(
            ['items.*.type' => 'in:foo|regex:/^(foo|bar)$/i']
        );

        $this->assertSame('in:foo', $exploded->rules['items.0.type'][0]);
        $this->assertSame('regex:/^(foo', $exploded->rules['items.0.type'][1]);
        $this->assertSame('bar)$/i', $exploded->rules['items.0.type'][2]);
    }

    public function testExplodeGeneratesNestedRules()
    {
        $parser = (new ValidationRuleParser([
            'users' => [
                ['name' => 'Taylor Otwell', 'email' => 'taylor@laravel.com'],
            ],
        ]));

        $results = $parser->explode([
            'users.*.name' => Rule::forEach(function ($value, $attribute, $data, $context) {
                $this->assertSame('Taylor Otwell', $value);
                $this->assertSame('users.0.name', $attribute);
                $this->assertEquals($data['users.0.name'], 'Taylor Otwell');
                $this->assertEquals(['name' => 'Taylor Otwell', 'email' => 'taylor@laravel.com'], $context);

                return [Rule::requiredIf(true)];
            }),
        ]);

        $this->assertEquals(['users.0.name' => ['required']], $results->rules);
        $this->assertEquals(['users.*.name' => ['users.0.name']], $results->implicitAttributes);
    }

    public function testExplodeGeneratesNestedRulesForNonNestedData()
    {
        $parser = (new ValidationRuleParser([
            'name' => 'Taylor Otwell',
            'email' => 'taylor@laravel.com',
        ]));

        $results = $parser->explode([
            'name' => Rule::forEach(function ($value, $attribute, $data, $context) {
                $this->assertSame('Taylor Otwell', $value);
                $this->assertSame('name', $attribute);
                $this->assertEquals(['name' => 'Taylor Otwell', 'email' => 'taylor@laravel.com'], $data);
                $this->assertEquals(['name' => 'Taylor Otwell', 'email' => 'taylor@laravel.com'], $context);

                return 'required';
            }),
        ]);

        $this->assertEquals(['name' => ['required']], $results->rules);
        $this->assertEquals([], $results->implicitAttributes);
    }

    public function testExplodeHandlesForwardSlashesInWildcardRule()
    {
        $parser = (new ValidationRuleParser([
            'redirects' => [
                'directory/subdirectory/file' => [
                    'directory/subdirectory/redirectedfile',
                ],
            ],
        ]));

        $results = $parser->explode([
            'redirects.directory/subdirectory/file.*' => 'string',
        ]);

        $this->assertEquals([
            'redirects.directory/subdirectory/file.0' => ['string'],
        ], $results->rules);
        $this->assertEquals([
            'redirects.directory/subdirectory/file.*' => ['redirects.directory/subdirectory/file.0'],
        ], $results->implicitAttributes);
    }

    public function testExplodeHandlesArraysOfNestedRules()
    {
        $parser = (new ValidationRuleParser([
            'users' => [
                ['name' => 'Taylor Otwell'],
                ['name' => 'Abigail Otwell'],
            ],
        ]));

        $results = $parser->explode([
            'users.*.name' => Rule::forEach(function ($value, $attribute, $data) {
                $this->assertEquals([
                    'users.0.name' => 'Taylor Otwell',
                    'users.1.name' => 'Abigail Otwell',
                ], $data);

                return [
                    Rule::requiredIf(true),
                    $value === 'Taylor Otwell'
                        ? Rule::in('taylor')
                        : Rule::in('abigail'),
                ];
            }),
        ]);

        $this->assertEquals([
            'users.0.name' => ['required', 'in:"taylor"'],
            'users.1.name' => ['required', 'in:"abigail"'],
        ], $results->rules);

        $this->assertEquals([
            'users.*.name' => [
                'users.0.name',
                'users.1.name',
            ],
        ], $results->implicitAttributes);
    }

    public function testExplodeHandlesRecursivelyNestedRules()
    {
        $parser = (new ValidationRuleParser([
            'users' => [['name' => 'Taylor Otwell']],
        ]));

        $results = $parser->explode([
            'users.*.name' => Rule::forEach(function ($value, $attribute, $data) {
                $this->assertSame('Taylor Otwell', $value);
                $this->assertSame('users.0.name', $attribute);
                $this->assertEquals(['users.0.name' => 'Taylor Otwell'], $data);

                return Rule::forEach(function ($value, $attribute, $data) {
                    $this->assertNull($value);
                    $this->assertSame('users.0.name', $attribute);
                    $this->assertEquals(['users.0.name' => 'Taylor Otwell'], $data);

                    return Rule::forEach(function ($value, $attribute, $data) {
                        $this->assertNull($value);
                        $this->assertSame('users.0.name', $attribute);
                        $this->assertEquals(['users.0.name' => 'Taylor Otwell'], $data);

                        return [Rule::requiredIf(true)];
                    });
                });
            }),
        ]);

        $this->assertEquals(['users.0.name' => ['required']], $results->rules);
        $this->assertEquals(['users.*.name' => ['users.0.name']], $results->implicitAttributes);
    }

    public function testExplodeHandlesSegmentingNestedRules()
    {
        $parser = (new ValidationRuleParser([
            'items' => [
                ['discounts' => [['id' => 1], ['id' => 2]]],
                ['discounts' => [['id' => 1], ['id' => 2]]],
            ],
        ]));

        $rules = [
            'items.*' => Rule::forEach(function () {
                return ['discounts.*.id' => 'distinct'];
            }),
        ];

        $results = $parser->explode($rules);

        $this->assertEquals([
            'items.0.discounts.0.id' => ['distinct'],
            'items.0.discounts.1.id' => ['distinct'],
            'items.1.discounts.0.id' => ['distinct'],
            'items.1.discounts.1.id' => ['distinct'],
        ], $results->rules);

        $this->assertEquals([
            'items.1.discounts.*.id' => [
                'items.1.discounts.0.id',
                'items.1.discounts.1.id',
            ],
            'items.0.discounts.*.id' => [
                'items.0.discounts.0.id',
                'items.0.discounts.1.id',
            ],
            'items.*' => [
                'items.0',
                'items.1',
            ],
        ], $results->implicitAttributes);
    }

    public function testExplodeHandlesStringDateRule()
    {
        $parser = (new ValidationRuleParser([
            'date' => '2021-01-01',
        ]));

        $rules = [
            'date' => 'date|date_format:Y-m-d',
        ];

        $results = $parser->explode($rules);

        $this->assertEquals([
            'date' => [
                'date',
                'date_format:Y-m-d',
            ],
        ], $results->rules);
    }

    public function testExplodeHandlesDateRule()
    {
        $parser = (new ValidationRuleParser([
            'date' => '2021-01-01',
        ]));

        $rules = [
            'date' => Rule::date(),
        ];

        $results = $parser->explode($rules);

        $this->assertEquals([
            'date' => [
                'date',
            ],
        ], $results->rules);
    }

    public function testExplodeHandlesDateRuleWithAdditionalRules()
    {
        $parser = (new ValidationRuleParser([
            'date' => '2021-01-01',
        ]));

        $rules = [
            'date' => Rule::date()->after('today'),
        ];

        $results = $parser->explode($rules);

        $this->assertEquals([
            'date' => [
                'date',
                'after:today',
            ],
        ], $results->rules);
    }

    public function testExplodeHandlesNumericStringRule()
    {
        $parser = (new ValidationRuleParser([
            'number' => 42,
        ]));

        $rules = [
            'number' => 'numeric|max:100',
        ];

        $results = $parser->explode($rules);

        $this->assertEquals([
            'number' => [
                'numeric',
                'max:100',
            ],
        ], $results->rules);
    }

    public function testExplodeHandlesNumericRule()
    {
        $parser = (new ValidationRuleParser([
            'number' => 42,
        ]));

        $rules = [
            'number' => Rule::numeric(),
        ];

        $results = $parser->explode($rules);

        $this->assertEquals([
            'number' => [
                'numeric',
            ],
        ], $results->rules);
    }

    public function testExplodeHandlesNumericRuleWithAdditionalRules()
    {
        $parser = (new ValidationRuleParser([
            'number' => 42,
        ]));

        $rules = [
            'number' => Rule::numeric()->max(100),
        ];

        $results = $parser->explode($rules);

        $this->assertEquals([
            'number' => [
                'numeric',
                'max:100',
            ],
        ], $results->rules);
    }
}
