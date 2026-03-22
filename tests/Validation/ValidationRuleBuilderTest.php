<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use PHPUnit\Framework\TestCase;

class ValidationRuleBuilderTest extends TestCase
{
    public function testStringRuleMethod()
    {
        $rule = Rule::string()->rule('required')->rule('email')->max(255);
        $this->assertSame('string|required|email|max:255', (string) $rule);
    }

    public function testStringRuleMethodWithArray()
    {
        $rule = Rule::string()->rule(['required', 'email'])->max(255);
        $this->assertSame('string|required|email|max:255', (string) $rule);
    }

    public function testNumericRuleMethod()
    {
        $rule = Rule::numeric()->rule('required')->rule('integer')->min(0);
        $this->assertSame('numeric|required|integer|min:0', (string) $rule);
    }

    public function testDateRuleMethod()
    {
        $rule = Rule::date()->rule('required')->after('today');
        $this->assertSame('date|required|after:today', (string) $rule);
    }

    public function testArrayRuleMethod()
    {
        $rule = Rule::array()->rule('required')->rule('min:1');
        $this->assertSame('array|required|min:1', (string) $rule);
    }

    public function testArrayRuleMethodWithKeys()
    {
        $rule = Rule::array(['name', 'email'])->rule('required');
        $this->assertSame('array:name,email|required', (string) $rule);
    }

    public function testArrayRuleMethodBackwardsCompat()
    {
        $this->assertSame('array', (string) Rule::array());
        $this->assertSame('array:name,email', (string) Rule::array(['name', 'email']));
    }

    public function testStringRuleMethodValidation()
    {
        $trans = new Translator(new ArrayLoader, 'en');

        $validator = new Validator(
            $trans,
            ['name' => 'John'],
            ['name' => Rule::string()->rule('required')->min(2)->max(255)]
        );

        $this->assertTrue($validator->passes());
    }

    public function testStringRuleMethodValidationFails()
    {
        $trans = new Translator(new ArrayLoader, 'en');

        $validator = new Validator(
            $trans,
            ['name' => 'J'],
            ['name' => Rule::string()->rule('required')->rule('min:2')]
        );

        $this->assertFalse($validator->passes());
    }

    public function testNumericRuleMethodValidation()
    {
        $trans = new Translator(new ArrayLoader, 'en');

        $validator = new Validator(
            $trans,
            ['age' => 25],
            ['age' => Rule::numeric()->rule('required')->rule('integer')->min(0)]
        );

        $this->assertTrue($validator->passes());
    }

    public function testChainedWithConditionable()
    {
        $isAdmin = true;

        $rule = Rule::string()
            ->rule('required')
            ->when($isAdmin, fn ($rule) => $rule->min(12))
            ->max(255);

        $this->assertSame('string|required|min:12|max:255', (string) $rule);
    }

    public function testChainedWithConditionableFalse()
    {
        $isAdmin = false;

        $rule = Rule::string()
            ->rule('required')
            ->when($isAdmin, fn ($rule) => $rule->min(12))
            ->max(255);

        $this->assertSame('string|required|max:255', (string) $rule);
    }

    public function testNestedInArrayOfRules()
    {
        $trans = new Translator(new ArrayLoader, 'en');

        $validator = new Validator(
            $trans,
            ['name' => 'John'],
            ['name' => ['sometimes', Rule::string()->min(2)]]
        );

        $this->assertTrue($validator->passes());
    }

    public function testArrayValidationPasses()
    {
        $trans = new Translator(new ArrayLoader, 'en');

        $validator = new Validator(
            $trans,
            ['tags' => ['php', 'laravel']],
            ['tags' => Rule::array()->rule('required')->rule('min:1')]
        );

        $this->assertTrue($validator->passes());
    }

    public function testArrayValidationFails()
    {
        $trans = new Translator(new ArrayLoader, 'en');

        $validator = new Validator(
            $trans,
            ['tags' => []],
            ['tags' => Rule::array()->rule('required')->rule('min:1')]
        );

        $this->assertFalse($validator->passes());
    }

    public function testDateValidation()
    {
        $trans = new Translator(new ArrayLoader, 'en');

        $validator = new Validator(
            $trans,
            ['date' => '2099-01-01'],
            ['date' => Rule::date()->rule('required')->after('today')]
        );

        $this->assertTrue($validator->passes());
    }
}
