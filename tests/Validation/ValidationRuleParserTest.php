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
            'password' => ['required', 'min:2'],
            'username' => ['required', 'min:2'],
            'address' => ['required'],
            'city' => ['required', 'min:2'],
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
}
