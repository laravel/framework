<?php

namespace Tests\Unit\Rules;

use Illuminate\Support\Carbon;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Date;
use Illuminate\Validation\Validator;
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
        $this->assertEquals('date_format:d/m/Y', (string) $rule);
    }

    public function testAfterTodayRule()
    {
        $rule = Rule::date()->afterToday();
        $this->assertEquals('date|after:today', (string) $rule);

        $rule = Rule::date()->todayOrAfter();
        $this->assertEquals('date|after_or_equal:today', (string) $rule);
    }

    public function testBeforeTodayRule()
    {
        $rule = Rule::date()->beforeToday();
        $this->assertEquals('date|before:today', (string) $rule);

        $rule = Rule::date()->todayOrBefore();
        $this->assertEquals('date|before_or_equal:today', (string) $rule);
    }

    public function testAfterSpecificDateRule()
    {
        $rule = Rule::date()->after(Carbon::parse('2024-01-01'));
        $this->assertEquals('date|after:2024-01-01', (string) $rule);

        $rule = Rule::date()->format('d/m/Y')->after(Carbon::parse('2024-01-01'));
        $this->assertEquals('date_format:d/m/Y|after:01/01/2024', (string) $rule);
    }

    public function testBeforeSpecificDateRule()
    {
        $rule = Rule::date()->before(Carbon::parse('2024-01-01'));
        $this->assertEquals('date|before:2024-01-01', (string) $rule);

        $rule = Rule::date()->format('d/m/Y')->before(Carbon::parse('2024-01-01'));
        $this->assertEquals('date_format:d/m/Y|before:01/01/2024', (string) $rule);
    }

    public function testAfterOrEqualSpecificDateRule()
    {
        $rule = Rule::date()->afterOrEqual(Carbon::parse('2024-01-01'));
        $this->assertEquals('date|after_or_equal:2024-01-01', (string) $rule);

        $rule = Rule::date()->format('d/m/Y')->afterOrEqual(Carbon::parse('2024-01-01'));
        $this->assertEquals('date_format:d/m/Y|after_or_equal:01/01/2024', (string) $rule);
    }

    public function testBeforeOrEqualSpecificDateRule()
    {
        $rule = Rule::date()->beforeOrEqual(Carbon::parse('2024-01-01'));
        $this->assertEquals('date|before_or_equal:2024-01-01', (string) $rule);

        $rule = Rule::date()->format('d/m/Y')->beforeOrEqual(Carbon::parse('2024-01-01'));
        $this->assertEquals('date_format:d/m/Y|before_or_equal:01/01/2024', (string) $rule);
    }

    public function testBetweenDatesRule()
    {
        $rule = Rule::date()->between(Carbon::parse('2024-01-01'), Carbon::parse('2024-02-01'));
        $this->assertEquals('date|after:2024-01-01|before:2024-02-01', (string) $rule);

        $rule = Rule::date()->format('d/m/Y')->between(Carbon::parse('2024-01-01'), Carbon::parse('2024-02-01'));
        $this->assertEquals('date_format:d/m/Y|after:01/01/2024|before:01/02/2024', (string) $rule);
    }

    public function testBetweenOrEqualDatesRule()
    {
        $rule = Rule::date()->betweenOrEqual('2024-01-01', '2024-02-01');
        $this->assertEquals('date|after_or_equal:2024-01-01|before_or_equal:2024-02-01', (string) $rule);
    }

    public function testChainedRules()
    {
        $rule = Rule::date('Y-m-d H:i:s')
            ->format('Y-m-d')
            ->after('2024-01-01 00:00:00')
            ->before('2025-01-01 00:00:00');
        $this->assertEquals('date_format:Y-m-d|after:2024-01-01 00:00:00|before:2025-01-01 00:00:00', (string) $rule);

        $rule = Rule::date()
            ->format('Y-m-d')
            ->when(true, function ($rule) {
                $rule->after('2024-01-01');
            })
            ->unless(true, function ($rule) {
                $rule->before('2025-01-01');
            });
        $this->assertSame('date_format:Y-m-d|after:2024-01-01', (string) $rule);
    }

    public function testDateValidation()
    {
        $trans = new Translator(new ArrayLoader, 'en');

        $rule = Rule::date();

        $validator = new Validator(
            $trans,
            ['date' => 'not a date'],
            ['date' => $rule]
        );

        $this->assertSame(
            $trans->get('validation.date'),
            $validator->errors()->first('date')
        );

        $validator = new Validator(
            $trans,
            ['date' => '2024-01-01'],
            ['date' => $rule]
        );

        $this->assertEmpty($validator->errors()->first('date'));

        $rule = Rule::date()->between('2024-01-01', '2025-01-01');

        $validator = new Validator(
            $trans,
            ['date' => '2024-02-01'],
            ['date' => (string) $rule]
        );

        $this->assertEmpty($validator->errors()->first('date'));

        $rule = Rule::date()->between('2024/01/01', '2024/02/01')->format('Y/m/d');

        $validator = new Validator(
            $trans,
            ['date' => '2024/01/15'],
            ['date' => [$rule]]
        );

        $this->assertEmpty($validator->errors()->first('date'));
    }
}
