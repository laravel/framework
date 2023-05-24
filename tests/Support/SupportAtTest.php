<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\At;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class SupportAtTest extends TestCase
{
    protected function setUp(): void
    {
        Carbon::setTestNow('2020-01-01 19:30:05.168');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
    }

    public function testUnits(): void
    {
        $this->assertEquals('2020-01-01 19:30:06', At::next()->second);
        $this->assertEquals('2020-01-01 19:30:15', At::next(10)->seconds);
        $this->assertEquals('2020-01-01 19:30:04', At::prev()->second);
        $this->assertEquals('2020-01-01 19:29:55', At::prev(10)->seconds);

        $this->assertEquals('2020-01-01 19:31:05', At::next()->minute);
        $this->assertEquals('2020-01-01 19:40:05', At::next(10)->minutes);
        $this->assertEquals('2020-01-01 19:29:05', At::prev()->minute);
        $this->assertEquals('2020-01-01 19:20:05', At::prev(10)->minutes);

        $this->assertEquals('2020-01-01 20:30:05', At::next()->hour);
        $this->assertEquals('2020-01-02 05:30:05', At::next(10)->hours);
        $this->assertEquals('2020-01-01 18:30:05', At::prev()->hour);
        $this->assertEquals('2020-01-01 09:30:05', At::prev(10)->hours);

        $this->assertEquals('2020-01-02 19:30:05', At::next()->day);
        $this->assertEquals('2020-01-11 19:30:05', At::next(10)->days);
        $this->assertEquals('2019-12-31 19:30:05', At::prev()->day);
        $this->assertEquals('2019-12-22 19:30:05', At::prev(10)->days);

        $this->assertEquals('2020-01-08 19:30:05', At::next()->week);
        $this->assertEquals('2020-03-11 19:30:05', At::next(10)->weeks);
        $this->assertEquals('2019-12-25 19:30:05', At::prev()->week);
        $this->assertEquals('2019-10-23 19:30:05', At::prev(10)->weeks);

        $this->assertEquals('2020-02-01 19:30:05', At::next()->month);
        $this->assertEquals('2020-11-01 19:30:05', At::next(10)->months);
        $this->assertEquals('2019-12-01 19:30:05', At::prev()->month);
        $this->assertEquals('2019-03-01 19:30:05', At::prev(10)->months);

        $this->assertEquals('2021-01-01 19:30:05', At::next()->year);
        $this->assertEquals('2030-01-01 19:30:05', At::next(10)->years);
        $this->assertEquals('2019-01-01 19:30:05', At::prev()->year);
        $this->assertEquals('2010-01-01 19:30:05', At::prev(10)->years);
    }

    /**
     * @dataProvider dayOfWeeks
     */
    public function testDayOfWeek($name, $next, $prev)
    {
        $this->assertEquals($next, At::next()->{$name}, "The day $name is not the next.");
        $this->assertEquals($prev, At::prev()->{$name}, "The day $name is not the previous.");
    }

    public static function dayOfWeeks()
    {
        return [
            ['monday', '2020-01-06 19:30:05', '2019-12-30 19:30:05'],
            ['tuesday', '2020-01-07 19:30:05', '2019-12-31 19:30:05'],
            ['wednesday', '2020-01-08 19:30:05', '2019-12-25 19:30:05'],
            ['thursday', '2020-01-02 19:30:05', '2019-12-26 19:30:05'],
            ['friday', '2020-01-03 19:30:05', '2019-12-27 19:30:05'],
            ['saturday', '2020-01-04 19:30:05', '2019-12-28 19:30:05'],
            ['sunday', '2020-01-05 19:30:05', '2019-12-29 19:30:05'],
        ];
    }

    public function testNthOfMonth()
    {
        $this->assertEquals('2020-02-01 19:30:05', At::next()->nthOfMonth(1));
        $this->assertEquals('2019-12-01 19:30:05', At::prev()->nthOfMonth(1));

        $this->assertEquals('2021-01-30 19:30:05', At::next(12)->nthOfMonth(30));
        $this->assertEquals('2021-02-28 19:30:05', At::next(13)->nthOfMonth(30));
        $this->assertEquals('2019-01-30 19:30:05', At::prev(12)->nthOfMonth(30));
        $this->assertEquals('2018-12-30 19:30:05', At::prev(13)->nthOfMonth(30));
        $this->assertEquals('2018-12-31 19:30:05', At::prev(13)->nthOfMonth(99));

        $this->assertEquals('2020-02-01 19:30:05', At::next()->nthOfMonth(1));
        $this->assertEquals('2019-12-01 19:30:05', At::prev()->nthOfMonth(1));

        $this->assertEquals('2021-01-30 19:30:05', At::next(12)->nthOfMonth(30));
        $this->assertEquals('2021-02-28 19:30:05', At::next(13)->nthOfMonth(30));
        $this->assertEquals('2019-01-30 19:30:05', At::prev(12)->nthOfMonth(30));
        $this->assertEquals('2018-12-30 19:30:05', At::prev(13)->nthOfMonth(30));
        $this->assertEquals('2018-12-31 19:30:05', At::prev(13)->nthOfMonth(99));
    }

    public function testNthOfMonthDoesNotOverflowInFebruary()
    {
        Carbon::setTestNow('2019-10-31 19:30:05');

        $this->assertEquals('2020-02-29 19:30:05', At::next(4)->month);

        Carbon::setTestNow('2020-01-30 19:30:05');

        $this->assertEquals('2020-02-29 19:30:05', At::next()->month);

        $this->assertEquals('2020-02-29 19:30:05', At::next()->nthOfMonth(30));
        $this->assertEquals('2020-02-29 19:30:05', At::next()->nthOfMonth(99));

        Carbon::setTestNow('2020-03-31 19:30:05');

        $this->assertEquals('2020-02-29 19:30:05', At::prev()->month);

        $this->assertEquals('2020-02-29 19:30:05', At::prev()->nthOfMonth(30));
        $this->assertEquals('2020-02-29 19:30:05', At::prev()->nthOfMonth(99));

        Carbon::setTestNow('2020-06-30 19:30:05');

        $this->assertEquals('2020-02-29 19:30:05', At::prev(4)->month);
    }

    public function testStartOf()
    {
        $this->assertEquals('2020-01-01 19:30:06', At::next()->startOf->second);
        $this->assertEquals('2020-01-01 19:31:00', At::next()->startOf->minute);
        $this->assertEquals('2020-01-01 20:00:00', At::next()->startOf->hour);
        $this->assertEquals('2020-01-02 00:00:00', At::next()->startOf->day);
        $this->assertEquals('2020-01-06 00:00:00', At::next()->startOf->week);
        $this->assertEquals('2020-02-01 00:00:00', At::next()->startOf->month);
        $this->assertEquals('2021-01-01 00:00:00', At::next()->startOf->year);
    }

    public function testEndOf()
    {
        $this->assertEquals('2020-01-01T19:30:06.999999Z', At::next()->endOf->second->toJSON());
        $this->assertEquals('2020-01-01T19:31:59.999999Z', At::next()->endOf->minute->toJSON());
        $this->assertEquals('2020-01-01T20:59:59.999999Z', At::next()->endOf->hour->toJSON());
        $this->assertEquals('2020-01-02T23:59:59.999999Z', At::next()->endOf->day->toJSON());
        $this->assertEquals('2020-01-12T23:59:59.999999Z', At::next()->endOf->week->toJSON());
        $this->assertEquals('2020-02-29T23:59:59.999999Z', At::next()->endOf->month->toJSON());
        $this->assertEquals('2021-12-31T23:59:59.999999Z', At::next()->endOf->year->toJSON());
    }
}
