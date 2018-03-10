<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use PHPUnit\Framework\TestCase;
use Illuminate\Validation\Rules\DateComparison;

class ValidationDateComparisonRuleTest extends TestCase
{
    public function testItCorrectlyFormatsAStringVersionOfTheDateEqualsRule()
    {
        $rule = new DateComparison(
            Carbon::create(1977, 05, 25, 06, 52, 59)
        );

        $this->assertEquals('date_equals:1977-05-25T06:52:59+00:00', (string) $rule);

        $rule = (new DateComparison(
            Carbon::create(1977, 05, 25, 06, 52, 59)
        ))->orEqual();

        $this->assertEquals('date_equals:1977-05-25T06:52:59+00:00', (string) $rule);

        $rule = Rule::dateEquals(Carbon::create(1977, 05, 25, 06, 52, 59));

        $this->assertEquals('date_equals:1977-05-25T06:52:59+00:00', (string) $rule);
    }

    public function testItCorrectlyFormatsAStringVersionOfTheBeforeRule()
    {
        $rule = (new DateComparison(
            Carbon::create(1977, 05, 25, 06, 52, 59)
        ))->before();

        $this->assertEquals('before:1977-05-25T06:52:59+00:00', (string) $rule);

        $rule = Rule::before(Carbon::create(1977, 05, 25, 06, 52, 59));

        $this->assertEquals('before:1977-05-25T06:52:59+00:00', (string) $rule);
    }

    public function testItCorrectlyFormatsAStringVersionOfTheBeforeOrEqualRule()
    {
        $rule = (new DateComparison(
            Carbon::create(1977, 05, 25, 06, 52, 59)
        ))->before()->orEqual();

        $this->assertEquals('before_or_equal:1977-05-25T06:52:59+00:00', (string) $rule);

        $rule = Rule::before(Carbon::create(1977, 05, 25, 06, 52, 59))
            ->orEqual();

        $this->assertEquals('before_or_equal:1977-05-25T06:52:59+00:00', (string) $rule);
    }

    public function testItCorrectlyFormatsAStringVersionOfTheAfterRule()
    {
        $rule = (new DateComparison(
            Carbon::create(1977, 05, 25, 06, 52, 59)
        ))->after();

        $this->assertEquals('after:1977-05-25T06:52:59+00:00', (string) $rule);

        $rule = Rule::after(Carbon::create(1977, 05, 25, 06, 52, 59));

        $this->assertEquals('after:1977-05-25T06:52:59+00:00', (string) $rule);
    }

    public function testItCorrectlyFormatsAStringVersionOfTheAfterOrEqualRule()
    {
        $rule = (new DateComparison(
            Carbon::create(1977, 05, 25, 06, 52, 59)
        ))->after()->orEqual();

        $this->assertEquals('after_or_equal:1977-05-25T06:52:59+00:00', (string) $rule);

        $rule = Rule::after(Carbon::create(1977, 05, 25, 06, 52, 59))
            ->orEqual();

        $this->assertEquals('after_or_equal:1977-05-25T06:52:59+00:00', (string) $rule);
    }
}
