<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\InvokableRule;
use Illuminate\Contracts\Validation\ValidatorAwareRule;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Validator;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ValidationInvokableRuleTest extends TestCase
{
    public function testItCanPass()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $rule = new class() implements InvokableRule
        {
            public function __invoke($attribute, $value, $fail)
            {
                //
            }
        };

        $validator = new Validator($trans, ['foo' => 'bar'], ['foo' => $rule]);

        $this->assertTrue($validator->passes());
        $this->assertSame([], $validator->messages()->messages());
    }

    public function testItCanFail()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $rule = new class() implements InvokableRule
        {
            public function __invoke($attribute, $value, $fail)
            {
                $fail("The {$attribute} attribute is not 'foo'. Got '{$value}' instead.");
            }
        };

        $validator = new Validator($trans, ['foo' => 'bar'], ['foo' => $rule]);

        $this->assertTrue($validator->fails());
        $this->assertSame([
            'foo' => [
                "The foo attribute is not 'foo'. Got 'bar' instead.",
            ],
        ], $validator->messages()->messages());
    }

    public function testItCanReturnMultipleErrorMessages()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $rule = new class() implements InvokableRule
        {
            public function __invoke($attribute, $value, $fail)
            {
                $fail('Error message 1.');
                $fail('Error message 2.');
            }
        };

        $validator = new Validator($trans, ['foo' => 'bar'], ['foo' => $rule]);

        $this->assertTrue($validator->fails());
        $this->assertSame([
            'foo' => [
                'Error message 1.',
                'Error message 2.',
            ],
        ], $validator->messages()->messages());
    }

    public function testItCanTranslateMessages()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.translated-error' => 'Translated error message.'], 'en');
        $rule = new class() implements InvokableRule
        {
            public function __invoke($attribute, $value, $fail)
            {
                $fail('validation.translated-error')->translate();
            }
        };

        $validator = new Validator($trans, ['foo' => 'bar'], ['foo' => $rule]);

        $this->assertTrue($validator->fails());
        $this->assertSame([
            'foo' => [
                'Translated error message.',
            ],
        ], $validator->messages()->messages());
    }

    public function testItCanAccessDataDuringValidation()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $rule = new class() implements InvokableRule, DataAwareRule
        {
            public $data = [];

            public function setData($data)
            {
                $this->data = $data;
            }

            public function __invoke($attribute, $value, $fail)
            {
                if ($this->data === []) {
                    $fail('xxxx');
                }
            }
        };

        $validator = new Validator($trans, ['foo' => 'bar', 'bar' => 'baz'], ['foo' => $rule]);

        $this->assertTrue($validator->passes());
        $this->assertSame([
            'foo' => 'bar',
            'bar' => 'baz',
        ], $rule->data);
    }

    public function testItCanAccessValidatorDuringValidation()
    {
        $trans = $this->getIlluminateArrayTranslator();

        $rule = new class() implements InvokableRule, ValidatorAwareRule
        {
            public $validator = null;

            public function setValidator($validator)
            {
                $this->validator = $validator;
            }

            public function __invoke($attribute, $value, $fail)
            {
                if ($this->validator === null) {
                    $fail('xxxx');
                }
            }
        };

        $validator = new Validator($trans, ['foo' => 'bar', 'bar' => 'baz'], ['foo' => $rule]);

        $this->assertTrue($validator->passes());
        $this->assertSame($validator, $rule->validator);
    }

    public function testItCanBeExplicit()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $rule = new class() implements InvokableRule
        {
            public $implicit = false;

            public function __invoke($attribute, $value, $fail)
            {
                $fail('xxxx');
            }
        };

        $validator = new Validator($trans, ['foo' => ''], ['foo' => $rule]);

        $this->assertTrue($validator->passes());
        $this->assertSame([], $validator->messages()->messages());
    }

    public function testItCanBeImplicit()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $rule = new class() implements InvokableRule
        {
            public $implicit = true;

            public function __invoke($attribute, $value, $fail)
            {
                $fail('xxxx');
            }
        };

        $validator = new Validator($trans, ['foo' => ''], ['foo' => $rule]);

        $this->assertFalse($validator->passes());
        $this->assertSame([
            'foo' => [
                'xxxx',
            ],
        ], $validator->messages()->messages());
    }

    public function testItIsExplicitByDefault()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $rule = new class() implements InvokableRule
        {
            public function __invoke($attribute, $value, $fail)
            {
                $fail('xxxx');
            }
        };

        $validator = new Validator($trans, ['foo' => ''], ['foo' => $rule]);

        $this->assertTrue($validator->passes());
        $this->assertSame([], $validator->messages()->messages());
    }

    public function testItThrowsIfTranslationIsNotFound()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $rule = new class() implements InvokableRule
        {
            public function __invoke($attribute, $value, $fail)
            {
                $fail('validation.key')->translate();
            }
        };

        $validator = new Validator($trans, ['foo' => 'bar'], ['foo' => $rule]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to find translation [validation.key] for locale [en].');

        $validator->passes();
    }

    private function getIlluminateArrayTranslator()
    {
        return new Translator(
            new ArrayLoader(),
            'en'
        );
    }
}
