<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\At;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class SupportAtTest extends TestCase
{
    /** @var \Illuminate\Support\At */
    protected $at;

    protected function setUp(): void
    {
        Carbon::setTestNow('2020-01-01 19:30:05.168');

        $this->at = new At();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
    }

    public function testUnits(): void
    {
        $this->assertEquals('2020-01-01 19:30:06', $this->at->next->second);
        $this->assertEquals('2020-01-01 19:30:15', $this->at->next(10)->seconds);
        $this->assertEquals('2020-01-01 19:30:04', $this->at->prev->second);
        $this->assertEquals('2020-01-01 19:29:55', $this->at->prev(10)->seconds);

        $this->assertEquals('2020-01-01 19:31:05', $this->at->next->minute);
        $this->assertEquals('2020-01-01 19:40:05', $this->at->next(10)->minutes);
        $this->assertEquals('2020-01-01 19:29:05', $this->at->prev->minute);
        $this->assertEquals('2020-01-01 19:20:05', $this->at->prev(10)->minutes);

        $this->assertEquals('2020-01-01 20:30:05', $this->at->next->hour);
        $this->assertEquals('2020-01-02 05:30:05', $this->at->next(10)->hours);
        $this->assertEquals('2020-01-01 18:30:05', $this->at->prev->hour);
        $this->assertEquals('2020-01-01 09:30:05', $this->at->prev(10)->hours);

        $this->assertEquals('2020-01-02 19:30:05', $this->at->next->day);
        $this->assertEquals('2020-01-11 19:30:05', $this->at->next(10)->days);
        $this->assertEquals('2019-12-31 19:30:05', $this->at->prev->day);
        $this->assertEquals('2019-12-22 19:30:05', $this->at->prev(10)->days);

        $this->assertEquals('2020-01-08 19:30:05', $this->at->next->week);
        $this->assertEquals('2020-03-11 19:30:05', $this->at->next(10)->weeks);
        $this->assertEquals('2019-12-25 19:30:05', $this->at->prev->week);
        $this->assertEquals('2019-10-23 19:30:05', $this->at->prev(10)->weeks);

        $this->assertEquals('2020-02-01 19:30:05', $this->at->next->month);
        $this->assertEquals('2020-11-01 19:30:05', $this->at->next(10)->months);
        $this->assertEquals('2019-12-01 19:30:05', $this->at->prev->month);
        $this->assertEquals('2019-03-01 19:30:05', $this->at->prev(10)->months);

        $this->assertEquals('2021-01-01 19:30:05', $this->at->next->year);
        $this->assertEquals('2030-01-01 19:30:05', $this->at->next(10)->years);
        $this->assertEquals('2019-01-01 19:30:05', $this->at->prev->year);
        $this->assertEquals('2010-01-01 19:30:05', $this->at->prev(10)->years);
    }

    /**
     * @dataProvider dayOfWeeks
     */
    public function testDayOfWeek($name, $next, $prev)
    {
        $this->assertEquals($next, $this->at->next->{$name}, "The day $name is not the next.");
        $this->assertEquals($prev, $this->at->prev->{$name}, "The day $name is not the previous.");
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
        $this->assertEquals('2020-02-01 19:30:05', $this->at->next->nthOfMonth(1));
        $this->assertEquals('2019-12-01 19:30:05', $this->at->prev->nthOfMonth(1));

        $this->assertEquals('2021-01-30 19:30:05', $this->at->next(12)->nthOfMonth(30));
        $this->assertEquals('2021-02-28 19:30:05', $this->at->next(13)->nthOfMonth(30));
        $this->assertEquals('2019-01-30 19:30:05', $this->at->prev(12)->nthOfMonth(30));
        $this->assertEquals('2018-12-30 19:30:05', $this->at->prev(13)->nthOfMonth(30));

        // This test if it can find the last day of february when the date overflows.
        Carbon::setTestNow('2020-01-30 19:30:05');

        $this->assertEquals('2020-02-01 19:30:05', $this->at->next->nthOfMonth(1));
        $this->assertEquals('2019-12-01 19:30:05', $this->at->prev->nthOfMonth(1));

        $this->assertEquals('2021-01-30 19:30:05', $this->at->next(12)->nthOfMonth(30));
        $this->assertEquals('2021-02-28 19:30:05', $this->at->next(13)->nthOfMonth(30));
        $this->assertEquals('2019-01-30 19:30:05', $this->at->prev(12)->nthOfMonth(30));
        $this->assertEquals('2018-12-30 19:30:05', $this->at->prev(13)->nthOfMonth(30));
    }

    public function testStartOf()
    {
        $this->at->startOf;

        $this->assertEquals('2020-01-01 19:30:06', $this->at->next->second);
        $this->assertEquals('2020-01-01 19:31:00', $this->at->next->minute);
        $this->assertEquals('2020-01-01 20:00:00', $this->at->next->hour);
        $this->assertEquals('2020-01-02 00:00:00', $this->at->next->day);
        $this->assertEquals('2020-01-06 00:00:00', $this->at->next->week);
        $this->assertEquals('2020-02-01 00:00:00', $this->at->next->month);
        $this->assertEquals('2021-01-01 00:00:00', $this->at->next->year);
    }
}
