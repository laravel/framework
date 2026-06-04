<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\ExcludeUnless;
use Illuminate\Validation\Validator;
use PHPUnit\Framework\TestCase;

class ValidationExcludeUnlessRuleTest extends TestCase
{
    protected Translator $translator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->translator = new Translator(new ArrayLoader, 'en');
    }

    public function testInstanceOf()
    {
        $this->assertInstanceOf(ExcludeUnless::class, Rule::excludeUnless(true));
    }

    public function testBooleanConditionTrue()
    {
        $rule = Rule::excludeUnless(true);
        $this->assertSame('', (string) $rule);
    }

    public function testBooleanConditionFalse()
    {
        $rule = Rule::excludeUnless(false);
        $this->assertSame('exclude', (string) $rule);
    }

    public function testClosureConditionTrue()
    {
        $rule = Rule::excludeUnless(fn () => true);
        $this->assertSame('', (string) $rule);
    }

    public function testClosureConditionFalse()
    {
        $rule = Rule::excludeUnless(fn () => false);
        $this->assertSame('exclude', (string) $rule);
    }

    public function testFieldIsExcludedWhenConditionFalse()
    {
        $validator = new Validator(
            $this->translator,
            ['name' => 'Taylor', 'extra' => 'value'],
            [
                'name' => 'required|string',
                'extra' => [Rule::excludeUnless(false), 'string'],
            ],
        );

        $this->assertTrue($validator->passes());
        $this->assertArrayNotHasKey('extra', $validator->validated());
    }

    public function testFieldIsKeptWhenConditionTrue()
    {
        $validator = new Validator(
            $this->translator,
            ['name' => 'Taylor', 'extra' => 'value'],
            [
                'name' => 'required|string',
                'extra' => [Rule::excludeUnless(true), 'string'],
            ],
        );

        $this->assertTrue($validator->passes());
        $this->assertArrayHasKey('extra', $validator->validated());
    }

    public function testInvalidConditionThrows()
    {
        $this->expectException(\InvalidArgumentException::class);

        Rule::excludeUnless('invalid');
    }
}
