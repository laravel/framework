<?php

namespace Illuminate\Tests\Console\Scheduling;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\EventMutex;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class FrequencyTest extends TestCase
{
    /*
     * @var \Illuminate\Console\Scheduling\Event
     */
    protected $event;

    protected function setUp(): void
    {
        $this->event = new Event(
            m::mock(EventMutex::class),
            'php foo'
        );
    }

    public function testEveryMinute()
    {
        $this->assertSame('* * * * *', $this->event->getExpression());
        $this->assertSame('* * * * *', $this->event->everyMinute()->getExpression());
    }

    public function testEveryXMinutes()
    {
        $this->assertSame('*/2 * * * *', $this->event->everyTwoMinutes()->getExpression());
        $this->assertSame('*/3 * * * *', $this->event->everyThreeMinutes()->getExpression());
        $this->assertSame('*/4 * * * *', $this->event->everyFourMinutes()->getExpression());
        $this->assertSame('*/5 * * * *', $this->event->everyFiveMinutes()->getExpression());
    }

    public function testDaily()
    {
        $this->assertSame('0 0 * * *', $this->event->daily()->getExpression());
    }

    public function testTwiceDaily()
    {
        $this->assertSame('0 3,15 * * *', $this->event->twiceDaily(3, 15)->getExpression());
    }

    public function testOverrideWithHourly()
    {
        $this->assertSame('0 * * * *', $this->event->everyFiveMinutes()->hourly()->getExpression());
        $this->assertSame('37 * * * *', $this->event->hourlyAt(37)->getExpression());
        $this->assertSame('15,30,45 * * * *', $this->event->hourlyAt([15, 30, 45])->getExpression());
    }

    public function testHourly()
    {
        $this->assertSame('0 */2 * * *', $this->event->everyTwoHours()->getExpression());
        $this->assertSame('0 */3 * * *', $this->event->everyThreeHours()->getExpression());
        $this->assertSame('0 */4 * * *', $this->event->everyFourHours()->getExpression());
        $this->assertSame('0 */6 * * *', $this->event->everySixHours()->getExpression());
    }

    public function testMonthlyOn()
    {
        $this->assertSame('0 15 4 * *', $this->event->monthlyOn(4, '15:00')->getExpression());
    }

    public function testTwiceMonthly()
    {
        $this->assertSame('0 0 1,16 * *', $this->event->twiceMonthly(1, 16)->getExpression());
    }

    public function testMonthlyOnWithMinutes()
    {
        $this->assertSame('15 15 4 * *', $this->event->monthlyOn(4, '15:15')->getExpression());
    }

    public function testWeekdaysDaily()
    {
        $this->assertSame('0 0 * * 1-5', $this->event->weekdays()->daily()->getExpression());
    }

    public function testWeekdaysHourly()
    {
        $this->assertSame('0 * * * 1-5', $this->event->weekdays()->hourly()->getExpression());
    }

    public function testWeekdays()
    {
        $this->assertSame('* * * * 1-5', $this->event->weekdays()->getExpression());
    }

    public function testSundays()
    {
        $this->assertSame('* * * * 0', $this->event->sundays()->getExpression());
    }

    public function testMondays()
    {
        $this->assertSame('* * * * 1', $this->event->mondays()->getExpression());
    }

    public function testTuesdays()
    {
        $this->assertSame('* * * * 2', $this->event->tuesdays()->getExpression());
    }

    public function testWednesdays()
    {
        $this->assertSame('* * * * 3', $this->event->wednesdays()->getExpression());
    }

    public function testThursdays()
    {
        $this->assertSame('* * * * 4', $this->event->thursdays()->getExpression());
    }

    public function testFridays()
    {
        $this->assertSame('* * * * 5', $this->event->fridays()->getExpression());
    }

    public function testSaturdays()
    {
        $this->assertSame('* * * * 6', $this->event->saturdays()->getExpression());
    }

    public function testQuarterly()
    {
        $this->assertSame('0 0 1 1-12/3 *', $this->event->quarterly()->getExpression());
    }

    public function testFrequencyMacro()
    {
        Event::macro('everyXMinutes', function ($x) {
            return $this->spliceIntoPosition(1, "*/{$x}");
        });

        $this->assertSame('*/6 * * * *', $this->event->everyXMinutes(6)->getExpression());
    }
}
