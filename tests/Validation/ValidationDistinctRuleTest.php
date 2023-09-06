<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Distinct;
use PHPUnit\Framework\TestCase;

class ValidationDistinctRuleTest extends TestCase
{
    public function testItCorrectlyFormatsAStringVersionOfTheRule()
    {
        $rule = new Distinct();

        $this->assertSame('distinct', (string) $rule);

        $rule = new Distinct(ignoreCase: true);

        $this->assertSame('distinct:ignore_case', (string) $rule);

        $rule = new Distinct(strict: true);

        $this->assertSame('distinct:strict', (string) $rule);

        $rule = new Distinct(strict: true, ignoreCase: true);

        $this->assertSame('distinct:ignore_case', (string) $rule);

        $rule = Rule::distinct();

        $this->assertSame('distinct', (string) $rule);

        $rule = Rule::distinct(ignoreCase: true);

        $this->assertSame('distinct:ignore_case', (string) $rule);

        $rule = Rule::distinct(strict: true);

        $this->assertSame('distinct:strict', (string) $rule);

        $rule = Rule::distinct(strict: true, ignoreCase: true);

        $this->assertSame('distinct:ignore_case', (string) $rule);
    }
}
