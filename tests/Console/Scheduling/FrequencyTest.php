<?php

namespace Illuminate\Tests\Console\Scheduling;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\EventMutex;
use Illuminate\Support\Carbon;
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
        $this->assertSame('*/10 * * * *', $this->event->everyTenMinutes()->getExpression());
        $this->assertSame('*/15 * * * *', $this->event->everyFifteenMinutes()->getExpression());
        $this->assertSame('*/30 * * * *', $this->event->everyThirtyMinutes()->getExpression());
    }

    public function testDaily()
    {
        $this->assertSame('0 0 * * *', $this->event->daily()->getExpression());
    }

    public function testDailyAt()
    {
        $this->assertSame('8 13 * * *', $this->event->dailyAt('13:08')->getExpression());
    }

    public function testTwiceDaily()
    {
        $this->assertSame('0 3,15 * * *', $this->event->twiceDaily(3, 15)->getExpression());
    }

    public function testTwiceDailyAt()
    {
        $this->assertSame('5 3,15 * * *', $this->event->twiceDailyAt(3, 15, 5)->getExpression());
    }

    public function testWeekly()
    {
        $this->assertSame('0 0 * * 0', $this->event->weekly()->getExpression());
    }

    public function testWeeklyOn()
    {
        $this->assertSame('0 8 * * 1', $this->event->weeklyOn(1, '8:00')->getExpression());
    }

    public function testOverrideWithHourly()
    {
        $this->assertSame('0 * * * *', $this->event->everyFiveMinutes()->hourly()->getExpression());
        $this->assertSame('37 * * * *', $this->event->hourlyAt(37)->getExpression());
        $this->assertSame('*/10 * * * *', $this->event->hourlyAt('*/10')->getExpression());
        $this->assertSame('15,30,45 * * * *', $this->event->hourlyAt([15, 30, 45])->getExpression());
    }

    public function testHourly()
    {
        $this->assertSame('0 1-23/2 * * *', $this->event->everyOddHour()->getExpression());
        $this->assertSame('0 */2 * * *', $this->event->everyTwoHours()->getExpression());
        $this->assertSame('0 */3 * * *', $this->event->everyThreeHours()->getExpression());
        $this->assertSame('0 */4 * * *', $this->event->everyFourHours()->getExpression());
        $this->assertSame('0 */6 * * *', $this->event->everySixHours()->getExpression());

        $this->assertSame('37 1-23/2 * * *', $this->event->everyOddHour(37)->getExpression());
        $this->assertSame('37 */2 * * *', $this->event->everyTwoHours(37)->getExpression());
        $this->assertSame('37 */3 * * *', $this->event->everyThreeHours(37)->getExpression());
        $this->assertSame('37 */4 * * *', $this->event->everyFourHours(37)->getExpression());
        $this->assertSame('37 */6 * * *', $this->event->everySixHours(37)->getExpression());

        $this->assertSame('*/10 1-23/2 * * *', $this->event->everyOddHour('*/10')->getExpression());
        $this->assertSame('*/10 */2 * * *', $this->event->everyTwoHours('*/10')->getExpression());
        $this->assertSame('*/10 */3 * * *', $this->event->everyThreeHours('*/10')->getExpression());
        $this->assertSame('*/10 */4 * * *', $this->event->everyFourHours('*/10')->getExpression());
        $this->assertSame('*/10 */6 * * *', $this->event->everySixHours('*/10')->getExpression());

        $this->assertSame('15,30,45 1-23/2 * * *', $this->event->everyOddHour([15, 30, 45])->getExpression());
        $this->assertSame('15,30,45 */2 * * *', $this->event->everyTwoHours([15, 30, 45])->getExpression());
        $this->assertSame('15,30,45 */3 * * *', $this->event->everyThreeHours([15, 30, 45])->getExpression());
        $this->assertSame('15,30,45 */4 * * *', $this->event->everyFourHours([15, 30, 45])->getExpression());
        $this->assertSame('15,30,45 */6 * * *', $this->event->everySixHours([15, 30, 45])->getExpression());
    }

    public function testMonthly()
    {
        $this->assertSame('0 0 1 * *', $this->event->monthly()->getExpression());
    }

    public function testMonthlyOn()
    {
        $this->assertSame('0 15 4 * *', $this->event->monthlyOn(4, '15:00')->getExpression());
    }

    public function testLastDayOfMonth()
    {
        Carbon::setTestNow('2020-10-10 10:10:10');

        $this->assertSame('0 0 31 * *', $this->event->lastDayOfMonth()->getExpression());

        Carbon::setTestNow(null);
    }

    public function testTwiceMonthly()
    {
        $this->assertSame('0 0 1,16 * *', $this->event->twiceMonthly(1, 16)->getExpression());
    }

    public function testTwiceMonthlyAtTime()
    {
        $this->assertSame('30 1 1,16 * *', $this->event->twiceMonthly(1, 16, '1:30')->getExpression());
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

    public function testWeekends()
    {
        $this->assertSame('* * * * 6,0', $this->event->weekends()->getExpression());
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

    public function testYearly()
    {
        $this->assertSame('0 0 1 1 *', $this->event->yearly()->getExpression());
    }

    public function testYearlyOn()
    {
        $this->assertSame('8 15 5 4 *', $this->event->yearlyOn(4, 5, '15:08')->getExpression());
    }

    public function testYearlyOnMondaysOnly()
    {
        $this->assertSame('1 9 * 7 1', $this->event->mondays()->yearlyOn(7, '*', '09:01')->getExpression());
    }

    public function testYearlyOnTuesdaysAndDayOfMonth20()
    {
        $this->assertSame('1 9 20 7 2', $this->event->tuesdays()->yearlyOn(7, 20, '09:01')->getExpression());
    }

    public function testFrequencyMacro()
    {
        Event::macro('everyXMinutes', function ($x) {
            return $this->spliceIntoPosition(1, "*/{$x}");
        });

        $this->assertSame('*/6 * * * *', $this->event->everyXMinutes(6)->getExpression());
    }
}
