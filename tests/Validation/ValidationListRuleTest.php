<?php

namespace Illuminate\Tests\Validation;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\ListRule;
use Illuminate\Validation\Validator;
use PHPUnit\Framework\TestCase;

class ValidationListRuleTest extends TestCase
{
    protected Translator $translator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->translator = new Translator(new ArrayLoader, 'en');
    }

    public function testDefaultListRule()
    {
        $rule = Rule::list();
        $this->assertInstanceOf(ListRule::class, $rule);
    }

    public function testDefaultListPassesSequentialArray()
    {
        $this->passes(Rule::list(), [1, 2, 3]);
        $this->passes(Rule::list(), ['a', 'b', 'c']);
        $this->passes(Rule::list(), []);
    }

    public function testDefaultListFailsAssociativeArray()
    {
        $this->fails(Rule::list(), ['a' => 1, 'b' => 2]);
        $this->fails(Rule::list(), 'not-an-array');
        $this->fails(Rule::list(), 123);
    }

    public function testOfTypeRule()
    {
        $this->passes(Rule::list()->of('integer'), [1, 2, 3]);
        $this->fails(Rule::list()->of('integer'), ['a', 'b', 'c']);
        $this->fails(Rule::list()->of('integer'), [1, 'a', 3]);

        $this->passes(Rule::list()->of('string'), ['a', 'b', 'c']);
        $this->fails(Rule::list()->of('string'), [1, 2, 3]);
    }

    public function testOfWithArrayRules()
    {
        $this->passes(Rule::list()->of(['string', 'min:2']), ['ab', 'cd']);
        $this->fails(Rule::list()->of(['string', 'min:2']), ['a']);
        $this->fails(Rule::list()->of(['string', 'min:2']), [1, 2]);
    }

    public function testMinRule()
    {
        $this->passes(Rule::list()->min(2), [1, 2]);
        $this->passes(Rule::list()->min(2), [1, 2, 3]);
        $this->fails(Rule::list()->min(2), [1]);
        $this->fails(Rule::list()->min(2), []);
    }

    public function testMaxRule()
    {
        $this->passes(Rule::list()->max(3), [1, 2, 3]);
        $this->passes(Rule::list()->max(3), [1, 2]);
        $this->fails(Rule::list()->max(3), [1, 2, 3, 4]);
    }

    public function testBetweenRule()
    {
        $this->passes(Rule::list()->between(2, 4), [1, 2]);
        $this->passes(Rule::list()->between(2, 4), [1, 2, 3]);
        $this->passes(Rule::list()->between(2, 4), [1, 2, 3, 4]);
        $this->fails(Rule::list()->between(2, 4), [1]);
        $this->fails(Rule::list()->between(2, 4), [1, 2, 3, 4, 5]);
    }

    public function testSizeRule()
    {
        $this->passes(Rule::list()->size(3), [1, 2, 3]);
        $this->fails(Rule::list()->size(3), [1, 2]);
        $this->fails(Rule::list()->size(3), [1, 2, 3, 4]);
    }

    public function testDistinctRule()
    {
        $this->passes(Rule::list()->distinct(), [1, 2, 3]);
        $this->fails(Rule::list()->distinct(), [1, 2, 2]);
    }

    public function testDistinctStrictRule()
    {
        $this->passes(Rule::list()->distinctStrict(), [1, '1']);
        $this->fails(Rule::list()->distinctStrict(), [1, 1]);
    }

    public function testDistinctIgnoreCaseRule()
    {
        $this->fails(Rule::list()->distinctIgnoreCase(), ['a', 'A']);
        $this->passes(Rule::list()->distinct(), ['a', 'A']);
    }

    public function testChainedRules()
    {
        $this->passes(Rule::list()->of('integer')->min(1)->max(5)->distinct(), [1, 2, 3]);
        $this->fails(Rule::list()->of('integer')->min(1)->max(5)->distinct(), []);
        $this->fails(Rule::list()->of('integer')->min(1)->max(5)->distinct(), [1, 1, 2]);
        $this->fails(Rule::list()->of('integer')->min(1)->max(5)->distinct(), ['a', 'b']);
        $this->fails(Rule::list()->of('integer')->min(1)->max(5)->distinct(), [1, 2, 3, 4, 5, 6]);
    }

    public function testErrorMessagesAppearOnIndexedKeys()
    {
        $validator = new Validator(
            $this->translator,
            ['field' => ['a', 'b', 'c']],
            ['field' => Rule::list()->of('integer')],
        );

        $this->assertTrue($validator->fails());
        $this->assertNotEmpty($validator->errors()->get('field.0'));
        $this->assertNotEmpty($validator->errors()->get('field.1'));
        $this->assertNotEmpty($validator->errors()->get('field.2'));
    }

    public function testCustomAttributesArePreserved()
    {
        $validator = new Validator(
            $this->translator,
            ['field' => ['a', 'b']],
            ['field' => Rule::list()->of('integer')],
        );

        $validator->setCustomMessages([
            'field.*.integer' => 'Custom message for :attribute.',
        ]);

        $validator->addCustomAttributes([
            'field.0' => 'first item',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertContains('Custom message for first item.', $validator->errors()->get('field.0'));
        $this->assertContains('Custom message for field.1.', $validator->errors()->get('field.1'));
    }

    public function testConditionalRules()
    {
        $this->passes(Rule::list()->when(true, fn ($rule) => $rule->of('string')), ['a', 'b']);
        $this->fails(Rule::list()->when(true, fn ($rule) => $rule->of('string')), [1, 2]);
    }

    public function testNestedListRule()
    {
        $this->passes(Rule::list()->of([Rule::list()->of('integer')]), [[1, 2], [3, 4]]);
        $this->fails(Rule::list()->of([Rule::list()->of('integer')]), [[1, 'a'], [3, 4]]);
        $this->fails(Rule::list()->of([Rule::list()->of('integer')]), ['not-a-list', [3, 4]]);
    }

    public function testMinAndMaxCalledSeparately()
    {
        $this->passes(Rule::list()->min(2)->max(5), [1, 2]);
        $this->passes(Rule::list()->min(2)->max(5), [1, 2, 3, 4, 5]);
        $this->fails(Rule::list()->min(2)->max(5), [1]);
        $this->fails(Rule::list()->min(2)->max(5), [1, 2, 3, 4, 5, 6]);
    }

    public function testNullValueFails()
    {
        $this->fails(Rule::list(), null);
        $this->fails(Rule::list()->of('string'), null);
        $this->fails(Rule::list()->min(1), null);
    }

    public function testOfWithValidationRuleObject()
    {
        $uppercaseRule = new class implements ValidationRule
        {
            public function validate(string $attribute, mixed $value, Closure $fail): void
            {
                if (strtoupper($value) !== $value) {
                    $fail('The :attribute must be uppercase.');
                }
            }
        };

        $this->passes(Rule::list()->of([$uppercaseRule]), ['ABC', 'DEF']);
        $this->fails(Rule::list()->of([$uppercaseRule]), ['abc', 'DEF']);
    }

    protected function passes($rule, $value)
    {
        $validator = new Validator(
            $this->translator,
            ['field' => $value],
            ['field' => $rule],
        );

        $this->assertTrue($validator->passes(), 'Expected list input to pass.');
    }

    protected function fails($rule, $value)
    {
        $validator = new Validator(
            $this->translator,
            ['field' => $value],
            ['field' => $rule],
        );

        $this->assertTrue($validator->fails(), 'Expected list input to fail.');
    }
}
