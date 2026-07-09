<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\RequiredUnless;
use Illuminate\Validation\Validator;
use PHPUnit\Framework\TestCase;

class ValidationRequiredUnlessRuleTest extends TestCase
{
    protected Translator $translator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->translator = new Translator(new ArrayLoader, 'en');
    }

    public function testInstanceOf()
    {
        $this->assertInstanceOf(RequiredUnless::class, Rule::requiredUnless(true));
    }

    public function testBooleanConditionTrue()
    {
        $rule = Rule::requiredUnless(true);
        $this->assertSame('', (string) $rule);
    }

    public function testBooleanConditionFalse()
    {
        $rule = Rule::requiredUnless(false);
        $this->assertSame('required', (string) $rule);
    }

    public function testClosureConditionTrue()
    {
        $rule = Rule::requiredUnless(fn () => true);
        $this->assertSame('', (string) $rule);
    }

    public function testClosureConditionFalse()
    {
        $rule = Rule::requiredUnless(fn () => false);
        $this->assertSame('required', (string) $rule);
    }

    public function testFieldIsRequiredWhenConditionFalse()
    {
        $validator = new Validator(
            $this->translator,
            ['name' => 'Taylor'],
            [
                'name' => 'required|string',
                'age' => [Rule::requiredUnless(false), 'integer'],
            ],
        );

        $this->assertTrue($validator->fails());
    }

    public function testFieldIsOptionalWhenConditionTrue()
    {
        $validator = new Validator(
            $this->translator,
            ['name' => 'Taylor'],
            [
                'name' => 'required|string',
                'age' => [Rule::requiredUnless(true), 'integer'],
            ],
        );

        $this->assertTrue($validator->passes());
    }

    public function testInvalidConditionThrows()
    {
        $this->expectException(\InvalidArgumentException::class);

        Rule::requiredUnless('invalid');
    }
}
