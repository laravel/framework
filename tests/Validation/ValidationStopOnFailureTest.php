<?php

namespace Illuminate\Tests\Validation;

use Closure;
use Illuminate\Contracts\Validation\StopUponFailure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Validator;
use PHPUnit\Framework\TestCase;

class ValidationStopOnFailureTest extends TestCase
{
    public function testFailingStopsFurtherValidation()
    {
        $trans = new Translator(new ArrayLoader, 'en');
        $v = new Validator(
            $trans,
            ['foo' => 'foobar'],
            ['foo' => [new StoppingValidationRule(), 'numeric']],
        );
        $this->assertFalse($v->passes());
        $this->assertEquals(
            ['foo' => ['Illuminate\Tests\Validation\StoppingValidationRule' => []]],
            $v->failed()
        );
    }
}

class StoppingValidationRule implements ValidationRule, StopUponFailure
{
    public function shouldStop(): bool
    {
        return true;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $fail('failed');
    }
}
