<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use PHPUnit\Framework\TestCase;

class ValidationFluentRuleTest extends TestCase
{
    public function testRequiredValidation()
    {
        $trans = new Translator(new ArrayLoader, 'en');

        $validation = new Validator(
            $trans,
            [],
            ['foo' => Rule::fluent()->required()],
        );

        $this->assertFalse($validation->passes());
        $this->assertSame(
            $trans->get('validation.required'),
            $validation->errors()->first('foo')
        );

        $validation = new Validator(
            $trans,
            ['foo' => null],
            ['foo' => Rule::fluent()->required()],
        );

        $this->assertFalse($validation->passes());
        $this->assertSame(
            $trans->get('validation.required'),
            $validation->errors()->first('foo')
        );

        $validation = new Validator(
            $trans,
            ['foo' => 'bar'],
            ['foo' => Rule::fluent()->required()],
        );

        $this->assertTrue($validation->passes());
    }

    public function testNullableValidation()
    {
        $trans = new Translator(new ArrayLoader, 'en');

        $validation = new Validator(
            $trans,
            ['foo' => null],
            ['foo' => Rule::fluent()->nullable()],
        );

        $this->assertTrue($validation->passes());
    }

    public function testSometimesValidation()
    {
        $trans = new Translator(new ArrayLoader, 'en');

        $validation = new Validator(
            $trans,
            ['foo' => 'abc'],
            ['foo' => Rule::fluent()->sometimes()->shouldBe(Rule::numeric())],
        );

        $this->assertFalse($validation->passes());
        $this->assertSame(
            $trans->get('validation.numeric'),
            $validation->errors()->first('foo')
        );

        $validation = new Validator(
            $trans,
            [],
            ['foo' => Rule::fluent()->sometimes()->shouldBe(Rule::numeric())],
        );

        $this->assertTrue($validation->passes());

        $validation = new Validator(
            $trans,
            ['foo' => 123],
            ['foo' => Rule::fluent()->sometimes()->shouldBe(Rule::numeric())],
        );

        $this->assertTrue($validation->passes());
    }

    public function testShouldBeWorkWithStringBasedValidation()
    {
        $trans = new Translator(new ArrayLoader, 'en');

        $validation = new Validator(
            $trans,
            ['foo' => 'abc'],
            ['foo' => Rule::fluent()->required()->shouldBe('numeric')],
        );

        $this->assertFalse($validation->passes());
        $this->assertSame(
            $trans->get('validation.numeric'),
            $validation->errors()->first('foo')
        );

        $validation = new Validator(
            $trans,
            ['foo' => 123],
            ['foo' => Rule::fluent()->required()->shouldBe('numeric')],
        );

        $this->assertTrue($validation->passes());
    }

    public function testShouldBeWorkWithArrayValidation()
    {
        $trans = new Translator(new ArrayLoader, 'en');

        $validation = new Validator(
            $trans,
            ['foo' => 'abc'],
            ['foo' => Rule::fluent()->required()->shouldBe(['numeric'])],
        );

        $this->assertFalse($validation->passes());
        $this->assertSame(
            $trans->get('validation.numeric'),
            $validation->errors()->first('foo')
        );

        $validation = new Validator(
            $trans,
            ['foo' => 123],
            ['foo' => Rule::fluent()->required()->shouldBe(['numeric'])],
        );

        $this->assertTrue($validation->passes());
    }

    public function testShouldBeWorkWithStringableBasedValidation()
    {
        $trans = new Translator(new ArrayLoader, 'en');

        $validation = new Validator(
            $trans,
            ['foo' => 'abc'],
            ['foo' => Rule::fluent()->required()->shouldBe(Rule::numeric())],
        );

        $this->assertFalse($validation->passes());
        $this->assertSame(
            $trans->get('validation.numeric'),
            $validation->errors()->first('foo')
        );

        $validation = new Validator(
            $trans,
            ['foo' => 3],
            ['foo' => Rule::fluent()->required()->shouldBe(Rule::numeric()->min(5))],
        );

        $this->assertFalse($validation->passes());

        $validation = new Validator(
            $trans,
            ['foo' => 7],
            ['foo' => Rule::fluent()->required()->shouldBe(Rule::numeric()->min(5))],
        );

        $this->assertTrue($validation->passes());
    }

    public function testShouldBeWorkWithOldRuleBasedValidation()
    {
        $trans = new Translator(new ArrayLoader, 'en');

        $validation = new Validator(
            $trans,
            ['foo' => 'abc'],
            ['foo' => Rule::fluent()->required()->shouldBe(Rule::enum(StringStatus::class))],
        );

        $this->assertFalse($validation->passes());

        $validation = new Validator(
            $trans,
            ['foo' => StringStatus::done],
            ['foo' => Rule::fluent()->required()->shouldBe(Rule::enum(StringStatus::class))],
        );

        $this->assertTrue($validation->passes());
    }

    public function testShouldBeWorkWithAnotherFluentBasedValidation()
    {
        $trans = new Translator(new ArrayLoader, 'en');

        $validation = new Validator(
            $trans,
            ['foo' => 'abc'],
            ['foo' => Rule::fluent()->required()->shouldBe(Rule::fluent()->shouldBe(Rule::numeric()))],
        );

        $this->assertFalse($validation->passes());

        $validation = new Validator(
            $trans,
            ['foo' => 5],
            ['foo' => Rule::fluent()->required()->shouldBe(Rule::fluent()->shouldBe(Rule::numeric()))],
        );

        $this->assertTrue($validation->passes());
    }

    public function testConditionalValidation()
    {
        $trans = new Translator(new ArrayLoader, 'en');

        $validation = new Validator(
            $trans,
            ['foo' => 'abc'],
            ['foo' => Rule::fluent()->when(true, fn($rule) => $rule->required())],
        );

        $this->assertTrue($validation->passes());

        $validation = new Validator(
            $trans,
            [],
            ['foo' => Rule::fluent()->when(false, fn($rule) => $rule->required())],
        );

        $this->assertTrue($validation->passes());

        $validation = new Validator(
            $trans,
            [],
            ['foo' => Rule::fluent()->unless(true, fn($rule) => $rule->required())],
        );

        $this->assertTrue($validation->passes());

        $validation = new Validator(
            $trans,
            ['foo' => 'abc'],
            ['foo' => Rule::fluent()->unless(false, fn($rule) => $rule->required())],
        );

        $this->assertTrue($validation->passes());
    }
}
