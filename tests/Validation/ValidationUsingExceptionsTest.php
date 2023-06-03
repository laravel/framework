<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Concerns\ValidatesUsingExceptions;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use PHPUnit\Framework\TestCase;

class ValidationUsingExceptionsTest extends TestCase
{
    public function testCanPassEarly()
    {
        $rule = new class () implements ValidationRule
        {
            public static $reached = [];

            use ValidatesUsingExceptions;

            public function run(string $attribute, mixed $value): void
            {
                self::$reached[] = '1';
                $this->pass();
                self::$reached[] = '2';
            }
        };

        $trans = $this->getIlluminateArrayTranslator();
        $validator = new Validator($trans, ['foo' => 'bar'], ['foo' => $rule]);

        $this->assertTrue($validator->passes());
        $this->assertEquals(['1'], $rule::$reached);
    }

    public function testCanFailEarly()
    {
        $rule = new class () implements ValidationRule
        {
            public static $reached = [];

            use ValidatesUsingExceptions;

            public function run(string $attribute, mixed $value): void
            {
                self::$reached[] = '1';
                $this->fail('Some custom error message');
                self::$reached[] = '2';
                $this->fail('Some other error message');
            }
        };

        $trans = $this->getIlluminateArrayTranslator();
        $validator = new Validator($trans, ['foo' => 'bar'], ['foo' => $rule]);

        $this->assertFalse($validator->passes());
        $this->assertEquals([
            'foo' => [
                'Some custom error message',
            ],
        ], $validator->messages()->messages());
        $this->assertEquals(['1'], $rule::$reached);
    }

    public function getIlluminateArrayTranslator(): Translator
    {
        return new Translator(
            new ArrayLoader(),
            'en',
        );
    }
}
