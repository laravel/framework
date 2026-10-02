<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Validator;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

class ValidationInArrayRuleTest extends TestCase
{
    #[TestWith(['0e123456789', '0', false])]
    #[TestWith(['1e0', '1', false])]
    #[TestWith([1, '1', false])]
    #[TestWith(['1', 1, false])]
    #[TestWith([1, 1, true])]
    #[TestWith(['1', '1', true])]
    public function testInArrayRuleUsesStrictComparison(mixed $value, mixed $otherValue, bool $expectation)
    {
        $trans = new Translator(new ArrayLoader, 'en');

        $v = new Validator($trans, ['value' => $value, 'other' => [$otherValue]], ['value' => 'in_array:other.*']);

        $this->assertSame($expectation, $v->passes());
    }
}
