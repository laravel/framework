<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\CompareDate;
use PHPUnit\Framework\TestCase;

class ValidationCompareDateRuleTest extends TestCase
{
    public function testItCorrectlyFormatsAStringVersionOfTheRule()
    {
        $date = '2023-10-14 10:00:00';

        $rule = new CompareDate($date, '<');

        $this->assertSame('before:'.$date, (string) $rule);

        $rule = new CompareDate($date, '>');

        $this->assertSame('after:'.$date, (string) $rule);

        $rule = new CompareDate($date, '=');

        $this->assertSame('date_equals:'.$date, (string) $rule);

        $rule = Rule::beforeDate($date);

        $this->assertSame('before:'.$date, (string) $rule);

        $rule = Rule::afterDate($date);

        $this->assertSame('after:'.$date, (string) $rule);

        $rule = Rule::equalDate($date);

        $this->assertSame('date_equals:'.$date, (string) $rule);

        $rule = Rule::beforeDate($date)->orEqual();

        $this->assertSame('before_or_equal:'.$date, (string) $rule);

        $rule = Rule::afterDate($date)->orEqual();

        $this->assertSame('after_or_equal:'.$date, (string) $rule);

        $rule = Rule::equalDate($date)->orEqual();

        $this->assertSame('date_equals:'.$date, (string) $rule);
    }
}
