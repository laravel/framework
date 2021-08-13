<?php

namespace Illuminate\Tests\Validation;

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
            'city' => ['required', Rule::when(function (array $input) {
                return true;
            }, ['min:2'])],
        ]);

        $this->assertEquals([
            'name' => ['required', 'min:2'],
            'password' => ['required', 'min:2'],
            'username' => ['required', 'min:2'],
            'address' => ['required'],
            'city' => ['required', 'min:2'],
        ], $rules);
    }

    public function testMergeRules()
    {
        $rules = [
            'name' => 'required|string',
            'city' => 'required|min:2',
            'airports.*' => ['required', 'string'],

            Rule::mergeWhen(true, [
                'city' => 'string',
                'country' => 'required|string|size:2',

                'airports.*' => [
                    Rule::when(function ($input) {
                        return $input['country'] === 'US';
                    }, 'in:NYC'),
                    Rule::when(function ($input) {
                        return $input['country'] === 'NL';
                    }, 'in:AMS'),
                ],

                Rule::mergeWhen(function ($input) {
                    return $input['country'] === 'US';
                }, [
                    'state' => 'required|size:2',
                ]),

                Rule::mergeWhen(function ($input) {
                    return $input['country'] === 'NL';
                }, [
                    'province' => 'required|size:2',
                ]),
            ]),

            Rule::mergeWhen(false, [
                'notincluded' => ['required', 'size:2'],
            ]),
        ];

        $data = [
            'country' => 'US',
            'airports' => ['NYC', 'AMS'],
        ];

        $response = (new ValidationRuleParser($data))
            ->explode(ValidationRuleParser::filterConditionalRules($rules, $data));

        $this->assertEquals([
            'name' => ['required', 'string'],
            'city' => ['required', 'min:2', 'string'],
            'country' => ['required', 'string', 'size:2'],
            'state' => ['required', 'size:2'],
            'airports.0' => ['required', 'string', 'in:NYC'],
            'airports.1' => ['required', 'string', 'in:NYC'],
        ], $response->rules);
    }
}
