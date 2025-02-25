<?php

namespace Illuminate\Tests\Validation;

use Closure;
use Illuminate\Contracts\Validation\Bailable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Validator;
use PHPUnit\Framework\TestCase;

class ValidationRuleWithBailableTest extends TestCase
{
    public function testFailingStopsFurtherValidation()
    {
        $trans = new Translator(new ArrayLoader, 'en');
        $v = new Validator(
            $trans,
            ['foo' => 'foobar'],
            ['foo' => [new BailableValidationRule(), 'numeric']],
        );
        $this->assertFalse($v->passes());
        $this->assertEquals(
            ['foo' => ['Illuminate\Tests\Validation\BailableValidationRule' => []]],
            $v->failed()
        );
    }
}

class BailableValidationRule implements ValidationRule, Bailable
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $fail('failed');
    }
}
