<?php

namespace Tests\Unit\Rules;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Date;
use PHPUnit\Framework\TestCase;

class ValidationDateRuleTest extends TestCase
{
    public function testDefaultDateRule()
    {
        $rule = Rule::date();
        $this->assertEquals('date', (string) $rule);

        $rule = new Date;
        $this->assertSame('date', (string) $rule);
    }

    public function testDateFormatRule()
    {
        $rule = Rule::date()->format('d/m/Y');
        $this->assertEquals('date,date_format:d/m/Y', (string) $rule);
    }

    public function testAfterTodayRule()
    {
        $rule = Rule::date()->afterToday();
        $this->assertEquals('date,after:today', (string) $rule);
    }

    public function testBeforeTodayRule()
    {
        $rule = Rule::date()->beforeToday();
        $this->assertEquals('date,before:today', (string) $rule);
    }

    public function testAfterSpecificDateRule()
    {
        $rule = Rule::date()->after('2024-01-01');
        $this->assertEquals('date,after:2024-01-01', (string) $rule);
    }

    public function testBeforeSpecificDateRule()
    {
        $rule = Rule::date()->before('2024-01-01');
        $this->assertEquals('date,before:2024-01-01', (string) $rule);
    }

    public function testAfterOrEqualSpecificDateRule()
    {
        $rule = Rule::date()->afterOrEqual('2024-01-01');
        $this->assertEquals('date,after_or_equal:2024-01-01', (string) $rule);
    }

    public function testBeforeOrEqualSpecificDateRule()
    {
        $rule = Rule::date()->beforeOrEqual('2024-01-01');
        $this->assertEquals('date,before_or_equal:2024-01-01', (string) $rule);
    }

    public function testChainedRules()
    {
        $rule = Rule::date()
            ->format('Y-m-d')
            ->after('2024-01-01')
            ->before('2025-01-01');
        $this->assertEquals('date,date_format:Y-m-d,after:2024-01-01,before:2025-01-01', (string) $rule);

        $rule = Rule::date()
            ->format('Y-m-d')
            ->when(true, function ($rule) {
                $rule->after('2024-01-01');
            })
            ->unless(true, function ($rule) {
                $rule->before('2025-01-01');
            });
        $this->assertSame('date,date_format:Y-m-d,after:2024-01-01', (string) $rule);
    }
}
