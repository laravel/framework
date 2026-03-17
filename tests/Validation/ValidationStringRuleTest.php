<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\StringRule;
use Illuminate\Validation\Validator;
use PHPUnit\Framework\TestCase;

class ValidationStringRuleTest extends TestCase
{
    public function testDefaultStringRule()
    {
        $rule = Rule::string();
        $this->assertSame('string', (string) $rule);

        $rule = new StringRule();
        $this->assertSame('string', (string) $rule);
    }

    public function testMinRule()
    {
        $rule = Rule::string()->min(3);
        $this->assertSame('string|min:3', (string) $rule);
    }

    public function testMaxRule()
    {
        $rule = Rule::string()->max(255);
        $this->assertSame('string|max:255', (string) $rule);
    }

    public function testBetweenRule()
    {
        $rule = Rule::string()->between(3, 255);
        $this->assertSame('string|between:3,255', (string) $rule);
    }

    public function testExactlyRule()
    {
        $rule = Rule::string()->exactly(10);
        $this->assertSame('string|size:10', (string) $rule);
    }

    public function testAlphaRule()
    {
        $rule = Rule::string()->alpha();
        $this->assertSame('string|alpha', (string) $rule);

        $rule = Rule::string()->alpha(ascii: true);
        $this->assertSame('string|alpha:ascii', (string) $rule);
    }

    public function testAlphaNumericRule()
    {
        $rule = Rule::string()->alphaNumeric();
        $this->assertSame('string|alpha_num', (string) $rule);

        $rule = Rule::string()->alphaNumeric(ascii: true);
        $this->assertSame('string|alpha_num:ascii', (string) $rule);
    }

    public function testAlphaDashRule()
    {
        $rule = Rule::string()->alphaDash();
        $this->assertSame('string|alpha_dash', (string) $rule);

        $rule = Rule::string()->alphaDash(ascii: true);
        $this->assertSame('string|alpha_dash:ascii', (string) $rule);
    }

    public function testAsciiRule()
    {
        $rule = Rule::string()->ascii();
        $this->assertSame('string|ascii', (string) $rule);
    }

    public function testUppercaseRule()
    {
        $rule = Rule::string()->uppercase();
        $this->assertSame('string|uppercase', (string) $rule);
    }

    public function testLowercaseRule()
    {
        $rule = Rule::string()->lowercase();
        $this->assertSame('string|lowercase', (string) $rule);
    }

    public function testStartsWithRule()
    {
        $rule = Rule::string()->startsWith('foo');
        $this->assertSame('string|starts_with:foo', (string) $rule);

        $rule = Rule::string()->startsWith('foo', 'bar');
        $this->assertSame('string|starts_with:foo,bar', (string) $rule);
    }

    public function testEndsWithRule()
    {
        $rule = Rule::string()->endsWith('.com');
        $this->assertSame('string|ends_with:.com', (string) $rule);

        $rule = Rule::string()->endsWith('.com', '.org');
        $this->assertSame('string|ends_with:.com,.org', (string) $rule);
    }

    public function testDoesntStartWithRule()
    {
        $rule = Rule::string()->doesntStartWith('foo');
        $this->assertSame('string|doesnt_start_with:foo', (string) $rule);

        $rule = Rule::string()->doesntStartWith('foo', 'bar');
        $this->assertSame('string|doesnt_start_with:foo,bar', (string) $rule);
    }

    public function testDoesntEndWithRule()
    {
        $rule = Rule::string()->doesntEndWith('.exe');
        $this->assertSame('string|doesnt_end_with:.exe', (string) $rule);

        $rule = Rule::string()->doesntEndWith('.exe', '.bat');
        $this->assertSame('string|doesnt_end_with:.exe,.bat', (string) $rule);
    }

    public function testBailRule()
    {
        $rule = Rule::string()->bail()->max(255);
        $this->assertSame('string|bail|max:255', (string) $rule);
    }

    public function testNullableRule()
    {
        $rule = Rule::string()->nullable();
        $this->assertSame('string|nullable', (string) $rule);
    }

    public function testRequiredRule()
    {
        $rule = Rule::string()->required();
        $this->assertSame('string|required', (string) $rule);
    }

    public function testSometimesRule()
    {
        $rule = Rule::string()->sometimes();
        $this->assertSame('string|sometimes', (string) $rule);
    }

    public function testChainedRules()
    {
        $rule = Rule::string()
            ->min(3)
            ->max(255)
            ->alpha()
            ->uppercase();
        $this->assertSame('string|min:3|max:255|alpha|uppercase', (string) $rule);

        $rule = Rule::string()
            ->between(1, 100)
            ->when(true, function ($rule) {
                $rule->startsWith('prefix');
            })
            ->unless(true, function ($rule) {
                $rule->endsWith('suffix');
            });
        $this->assertSame('string|between:1,100|starts_with:prefix', (string) $rule);
    }

    public function testStringValidation()
    {
        $trans = new Translator(new ArrayLoader, 'en');

        $rule = Rule::string();

        $validator = new Validator(
            $trans,
            ['field' => 123],
            ['field' => $rule]
        );

        $this->assertSame(
            $trans->get('validation.string'),
            $validator->errors()->first('field')
        );

        $validator = new Validator(
            $trans,
            ['field' => 'hello'],
            ['field' => $rule]
        );

        $this->assertEmpty($validator->errors()->first('field'));

        $rule = Rule::string()->min(3)->max(10);

        $validator = new Validator(
            $trans,
            ['field' => 'hello'],
            ['field' => $rule]
        );

        $this->assertEmpty($validator->errors()->first('field'));

        $rule = Rule::string()->min(3)->max(10);

        $validator = new Validator(
            $trans,
            ['field' => 'ab'],
            ['field' => $rule]
        );

        $this->assertNotEmpty($validator->errors()->first('field'));

        $rule = Rule::string()->min(3)->max(10);

        $validator = new Validator(
            $trans,
            ['field' => 'this string is too long'],
            ['field' => $rule]
        );

        $this->assertNotEmpty($validator->errors()->first('field'));

        $rule = Rule::string()->uppercase();

        $validator = new Validator(
            $trans,
            ['field' => 'HELLO'],
            ['field' => $rule]
        );

        $this->assertEmpty($validator->errors()->first('field'));

        $rule = Rule::string()->uppercase();

        $validator = new Validator(
            $trans,
            ['field' => 'hello'],
            ['field' => $rule]
        );

        $this->assertNotEmpty($validator->errors()->first('field'));
    }

    public function testUniquenessOfConstraints()
    {
        $rule = Rule::string()->alpha()->alpha();
        $this->assertSame('string|alpha', (string) $rule);
    }
}
