<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Support\Fluent;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationRuleParser;
use PHPUnit\Framework\TestCase;

class ValidationRuleParserTest extends TestCase
{
    public function test_conditional_rules_are_properly_expanded_and_filtered()
    {
        $rules = ValidationRuleParser::filterConditionalRules([
            'name' => Rule::when(true, ['required', 'min:2']),
            'email' => Rule::when(false, ['required', 'min:2']),
            'password' => Rule::when(true, 'required|min:2'),
            'username' => ['required', Rule::when(true, ['min:2'])],
            'address' => ['required', Rule::when(false, ['min:2'])],
            'city' => ['required', Rule::when(function (Fluent $input) {
                return true;
            }, ['min:2'])],
        ]);

        $this->assertEquals([
            'name' => ['required', 'min:2'],
            'email' => [],
            'password' => ['required', 'min:2'],
            'username' => ['required', 'min:2'],
            'address' => ['required'],
            'city' => ['required', 'min:2'],
        ], $rules);
    }

    public function test_empty_rules_are_preserved()
    {
        $rules = ValidationRuleParser::filterConditionalRules([
            'name' => [],
            'email' => '',
            'password' => Rule::when(true, 'required|min:2'),
        ]);

        $this->assertEquals([
            'name' => [],
            'email' => '',
            'password' => ['required', 'min:2'],
        ], $rules);
    }

    public function test_conditional_rules_with_default()
    {
        $rules = ValidationRuleParser::filterConditionalRules([
            'name' => Rule::when(true, ['required', 'min:2'], ['string', 'max:10']),
            'email' => Rule::when(false, ['required', 'min:2'], ['string', 'max:10']),
            'password' => Rule::when(false, 'required|min:2', 'string|max:10'),
            'username' => ['required', Rule::when(true, ['min:2'], ['string', 'max:10'])],
            'address' => ['required', Rule::when(false, ['min:2'], ['string', 'max:10'])],
        ]);

        $this->assertEquals([
            'name' => ['required', 'min:2'],
            'email' => ['string', 'max:10'],
            'password' => ['string', 'max:10'],
            'username' => ['required', 'min:2'],
            'address' => ['required', 'string', 'max:10'],
        ], $rules);
    }

    public function test_empty_conditional_rules_are_preserved()
    {
        $rules = ValidationRuleParser::filterConditionalRules([
            'name' => Rule::when(true, '', ['string', 'max:10']),
            'email' => Rule::when(false, ['required', 'min:2'], []),
            'password' => Rule::when(false, 'required|min:2', 'string|max:10'),
        ]);

        $this->assertEquals([
            'name' => [],
            'email' => [],
            'password' => ['string', 'max:10'],
        ], $rules);
    }

    public function test_explode_generates_nested_rules()
    {
        $parser = (new ValidationRuleParser([
            'users' => [
                ['name' => 'Taylor Otwell'],
            ],
        ]));

        $results = $parser->explode([
            'users.*.name' => Rule::nested(function ($attribute, $value, $data) {
                $this->assertEquals('users.0.name', $attribute);
                $this->assertEquals('Taylor Otwell', $value);
                $this->assertEquals($data['users.0.name'], 'Taylor Otwell');

                return [Rule::requiredIf(true)];
            }),
        ]);

        $this->assertEquals(['users.0.name' => ['required']], $results->rules);
        $this->assertEquals(['users.*.name' => ['users.0.name']], $results->implicitAttributes);
    }

    public function test_explode_handles_arrays_of_nested_rules()
    {
        $parser = (new ValidationRuleParser([
            'users' => [
                ['name' => 'Taylor Otwell'],
                ['name' => 'Abigail Otwell'],
            ],
        ]));

        $results = $parser->explode([
            'users.*.name' => [
                Rule::nested(function ($attribute, $value, $data) {
                    $this->assertEquals([
                        'users.0.name' => 'Taylor Otwell',
                        'users.1.name' => 'Abigail Otwell',
                    ], $data);

                    return [Rule::requiredIf(true)];
                }),
                Rule::nested(function ($attribute, $value) {
                    return [
                        $value === 'Taylor Otwell'
                            ? Rule::in('taylor')
                            : Rule::in('abigail'),
                    ];
                }),
            ],
        ]);

        $this->assertEquals([
            'users.0.name' => ['required', 'in:"taylor"'],
            'users.1.name' => ['required', 'in:"abigail"'],
        ], $results->rules);

        $this->assertEquals([
            'users.*.name' => [
                'users.0.name',
                'users.0.name',
                'users.1.name',
                'users.1.name',
            ],
        ], $results->implicitAttributes);
    }
}
