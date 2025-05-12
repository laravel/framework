<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Numeric;
use Illuminate\Validation\Validator;
use PHPUnit\Framework\TestCase;

class ValidationNumericRuleTest extends TestCase
{
    public function testDefaultNumericRule()
    {
        $rule = Rule::numeric();
        $this->assertEquals('numeric', (string) $rule);

        $rule = new Numeric();
        $this->assertSame('numeric', (string) $rule);
    }

    public function testBetweenRule()
    {
        $rule = Rule::numeric()->between(1, 10);
        $this->assertEquals('numeric|between:1,10', (string) $rule);

        $rule = Rule::numeric()->between(1.5, 10.5);
        $this->assertEquals('numeric|between:1.5,10.5', (string) $rule);
    }

    public function testDecimalRule()
    {
        $rule = Rule::numeric()->decimal(2, 4);
        $this->assertEquals('numeric|decimal:2,4', (string) $rule);

        $rule = Rule::numeric()->decimal(2);
        $this->assertEquals('numeric|decimal:2', (string) $rule);
    }

    public function testDifferentRule()
    {
        $rule = Rule::numeric()->different('some_field');
        $this->assertEquals('numeric|different:some_field', (string) $rule);
    }

    public function testDigitsRule()
    {
        $rule = Rule::numeric()->digits(10);
        $this->assertEquals('numeric|integer|digits:10', (string) $rule);
    }

    public function testDigitsBetweenRule()
    {
        $rule = Rule::numeric()->digitsBetween(2, 10);
        $this->assertEquals('numeric|integer|digits_between:2,10', (string) $rule);
    }

    public function testGreaterThanRule()
    {
        $rule = Rule::numeric()->greaterThan('some_field');
        $this->assertEquals('numeric|gt:some_field', (string) $rule);
    }

    public function testGreaterThanOrEqualRule()
    {
        $rule = Rule::numeric()->greaterThanOrEqualTo('some_field');
        $this->assertEquals('numeric|gte:some_field', (string) $rule);
    }

    public function testIntegerRule()
    {
        $rule = Rule::numeric()->integer();
        $this->assertEquals('numeric|integer', (string) $rule);
    }

    public function testLessThanRule()
    {
        $rule = Rule::numeric()->lessThan('some_field');
        $this->assertEquals('numeric|lt:some_field', (string) $rule);
    }

    public function testLessThanOrEqualRule()
    {
        $rule = Rule::numeric()->lessThanOrEqualTo('some_field');
        $this->assertEquals('numeric|lte:some_field', (string) $rule);
    }

    public function testMaxRule()
    {
        $rule = Rule::numeric()->max(10);
        $this->assertEquals('numeric|max:10', (string) $rule);

        $rule = Rule::numeric()->max(10.5);
        $this->assertEquals('numeric|max:10.5', (string) $rule);
    }

    public function testMaxDigitsRule()
    {
        $rule = Rule::numeric()->maxDigits(10);
        $this->assertEquals('numeric|max_digits:10', (string) $rule);
    }

    public function testMinRule()
    {
        $rule = Rule::numeric()->min(10);
        $this->assertEquals('numeric|min:10', (string) $rule);

        $rule = Rule::numeric()->min(10.5);
        $this->assertEquals('numeric|min:10.5', (string) $rule);
    }

    public function testMinDigitsRule()
    {
        $rule = Rule::numeric()->minDigits(10);
        $this->assertEquals('numeric|min_digits:10', (string) $rule);
    }

    public function testMultipleOfRule()
    {
        $rule = Rule::numeric()->multipleOf(10);
        $this->assertEquals('numeric|multiple_of:10', (string) $rule);
    }

    public function testSameRule()
    {
        $rule = Rule::numeric()->same('some_field');
        $this->assertEquals('numeric|same:some_field', (string) $rule);
    }

    public function testSizeRule()
    {
        $rule = Rule::numeric()->exactly(10);
        $this->assertEquals('numeric|integer|size:10', (string) $rule);
    }

    public function testChainedRules()
    {
        $rule = Rule::numeric()
            ->integer()
            ->multipleOf(10)
            ->lessThanOrEqualTo('some_field')
            ->max(100);
        $this->assertEquals('numeric|integer|multiple_of:10|lte:some_field|max:100', (string) $rule);

        $rule = Rule::numeric()
            ->decimal(2)
            ->when(true, function ($rule) {
                $rule->same('some_field');
            })
            ->unless(true, function ($rule) {
                $rule->different('some_field_2');
            });
        $this->assertSame('numeric|decimal:2|same:some_field', (string) $rule);
    }

    public function testNumericValidation()
    {
        $trans = new Translator(new ArrayLoader, 'en');

        $rule = Rule::numeric();

        $validator = new Validator(
            $trans,
            ['numeric' => 'NaN'],
            ['numeric' => $rule]
        );

        $this->assertSame(
            $trans->get('validation.numeric'),
            $validator->errors()->first('numeric')
        );

        $validator = new Validator(
            $trans,
            ['numeric' => '100'],
            ['numeric' => $rule]
        );

        $this->assertEmpty($validator->errors()->first('numeric'));

        $rule = Rule::numeric()->between(10, 100);

        $validator = new Validator(
            $trans,
            ['numeric' => '50'],
            ['numeric' => (string) $rule]
        );

        $this->assertEmpty($validator->errors()->first('numeric'));

        $rule = Rule::numeric()->different('some_field');

        $validator = new Validator(
            $trans,
            ['numeric' => '50', 'some_field' => '100'],
            ['numeric' => (string) $rule]
        );

        $this->assertEmpty($validator->errors()->first('numeric'));

        $rule = Rule::numeric()->digits(2);

        $validator = new Validator(
            $trans,
            ['numeric' => '10'],
            ['numeric' => (string) $rule]
        );

        $this->assertEmpty($validator->errors()->first('numeric'));

        $rule = Rule::numeric()->digitsBetween(2, 4);

        $validator = new Validator(
            $trans,
            ['numeric' => '100'],
            ['numeric' => (string) $rule]
        );

        $this->assertEmpty($validator->errors()->first('numeric'));

        $rule = Rule::numeric()->greaterThan('some_field');

        $validator = new Validator(
            $trans,
            ['numeric' => '100', 'some_field' => '10'],
            ['numeric' => (string) $rule]
        );

        $this->assertEmpty($validator->errors()->first('numeric'));

        $rule = Rule::numeric()->greaterThanOrEqualTo('some_field');

        $validator = new Validator(
            $trans,
            ['numeric' => '100', 'some_field' => '100'],
            ['numeric' => (string) $rule]
        );

        $this->assertEmpty($validator->errors()->first('numeric'));

        $rule = Rule::numeric()->integer();

        $validator = new Validator(
            $trans,
            ['numeric' => '10'],
            ['numeric' => (string) $rule]
        );

        $this->assertEmpty($validator->errors()->first('numeric'));

        $rule = Rule::numeric()->lessThan('some_field');

        $validator = new Validator(
            $trans,
            ['numeric' => '100', 'some_field' => '150'],
            ['numeric' => (string) $rule]
        );

        $this->assertEmpty($validator->errors()->first('numeric'));

        $rule = Rule::numeric()->lessThanOrEqualTo('some_field');

        $validator = new Validator(
            $trans,
            ['numeric' => '100', 'some_field' => '100'],
            ['numeric' => (string) $rule]
        );

        $this->assertEmpty($validator->errors()->first('numeric'));

        $rule = Rule::numeric()->max(200);

        $validator = new Validator(
            $trans,
            ['numeric' => '200'],
            ['numeric' => (string) $rule]
        );

        $this->assertEmpty($validator->errors()->first('numeric'));

        $rule = Rule::numeric()->maxDigits(3);

        $validator = new Validator(
            $trans,
            ['numeric' => '100'],
            ['numeric' => (string) $rule]
        );

        $this->assertEmpty($validator->errors()->first('numeric'));

        $rule = Rule::numeric()->min(2);

        $validator = new Validator(
            $trans,
            ['numeric' => '10'],
            ['numeric' => (string) $rule]
        );

        $this->assertEmpty($validator->errors()->first('numeric'));

        $rule = Rule::numeric()->minDigits(2);

        $validator = new Validator(
            $trans,
            ['numeric' => '10'],
            ['numeric' => (string) $rule]
        );

        $this->assertEmpty($validator->errors()->first('numeric'));

        $rule = Rule::numeric()->multipleOf(10);

        $validator = new Validator(
            $trans,
            ['numeric' => '100'],
            ['numeric' => (string) $rule]
        );

        $this->assertEmpty($validator->errors()->first('numeric'));

        $rule = Rule::numeric()->same('some_field');

        $validator = new Validator(
            $trans,
            ['numeric' => '100', 'some_field' => '100'],
            ['numeric' => (string) $rule]
        );

        $this->assertEmpty($validator->errors()->first('numeric'));

        $rule = Rule::numeric()->exactly(10);

        $validator = new Validator(
            $trans,
            ['numeric' => '10'],
            ['numeric' => (string) $rule]
        );

        $this->assertEmpty($validator->errors()->first('numeric'));

        $rule = Rule::numeric()->exactly(10);

        $validator = new Validator(
            $trans,
            ['numeric' => 10],
            ['numeric' => [$rule]]
        );

        $this->assertEmpty($validator->errors()->first('numeric'));
    }

    public function testUniquenessValidation()
    {
        $rule = Rule::numeric()->integer()->digits(2)->exactly(2);
        $this->assertEquals('numeric|integer|digits:2|size:2', (string) $rule);
    }
}
