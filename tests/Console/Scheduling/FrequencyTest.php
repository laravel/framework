<?php

namespace Illuminate\Tests\Console\Scheduling;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\EventMutex;

class FrequencyTest extends TestCase
{
    /*
     * @var \Illuminate\Console\Scheduling\Event
     */
    protected $event;

    public function setUp()
    {
        $this->event = new Event(
            m::mock(EventMutex::class),
            'php foo'
        );
    }

    public function testEveryMinute()
    {
        $this->assertEquals('* * * * *', $this->event->getExpression());
        $this->assertEquals('* * * * *', $this->event->everyMinute()->getExpression());
    }

    public function testEveryFiveMinutes()
    {
        $this->assertEquals('*/5 * * * *', $this->event->everyFiveMinutes()->getExpression());
    }

    public function testDaily()
    {
        $this->assertEquals('0 0 * * *', $this->event->daily()->getExpression());
    }

    public function testDailyAt()
    {
        $this->assertEquals('59 12 * * *', $this->event->dailyAt('12:59')->getExpression());
        $this->assertEquals('0 12 * * *', $this->event->dailyAt('12')->getExpression());
        $this->assertEquals('0 0 * * *', $this->event->dailyAt('0')->getExpression());
        $this->assertEquals('0 0 * * *', $this->event->dailyAt(0)->getExpression());
        $this->assertEquals('0 0 * * *', $this->event->dailyAt('00')->getExpression());
        $this->assertEquals('0 0 * * *', $this->event->dailyAt('00:00')->getExpression());
    }

    /**
     * @expectedException \Exception
     */
    public function testDailyAtAcceptInvalidDataExceptionOne()
    {
        $this->event->dailyAt('12:abc');
    }

    /**
     * @expectedException \Exception
     */
    public function testDailyAtAcceptInvalidDataExceptionTwo()
    {
        $this->event->dailyAt('1 :59');
    }

    /**
     * @expectedException \Exception
     */
    public function testDailyAtAcceptDatetimeException()
    {
        $this->event->dailyAt('2018-12-06 18:06:37');
    }

    /**
     * @expectedException \Exception
     */
    public function testDailyAtAcceptSecondException()
    {
        $this->event->dailyAt('12:59:00');
    }

    /**
     * @expectedException \Exception
     */
    public function testDailyAtAcceptBoolException()
    {
        $this->event->dailyAt(true);
    }

    /**
     * @expectedException \Exception
     */
    public function testDailyAtAcceptNullException()
    {
        $this->event->dailyAt(null);
    }

    /**
     * @expectedException \Exception
     */
    public function testDailyAtAcceptWordException()
    {
        $this->event->dailyAt('word');
    }

    public function testTwiceDaily()
    {
        $this->assertEquals('0 3,15 * * *', $this->event->twiceDaily(3, 15)->getExpression());
    }

    public function testOverrideWithHourly()
    {
        $this->assertEquals('0 * * * *', $this->event->everyFiveMinutes()->hourly()->getExpression());
        $this->assertEquals('37 * * * *', $this->event->hourlyAt(37)->getExpression());
    }

    public function testMonthlyOn()
    {
        $this->assertEquals('0 15 4 * *', $this->event->monthlyOn(4, '15:00')->getExpression());
    }

    /**
     * @expectedException \Exception
     */
    public function testMonthlyOnAcceptWordException()
    {
        $this->event->monthlyOn(1, 'word');
    }

    public function testTwiceMonthly()
    {
        $this->assertEquals('0 0 1,16 * *', $this->event->twiceMonthly(1, 16)->getExpression());
    }

    public function testMonthlyOnWithMinutes()
    {
        $this->assertEquals('15 15 4 * *', $this->event->monthlyOn(4, '15:15')->getExpression());
    }

    public function testWeekdaysDaily()
    {
        $this->assertEquals('0 0 * * 1-5', $this->event->weekdays()->daily()->getExpression());
    }

    public function testWeekdaysHourly()
    {
        $this->assertEquals('0 * * * 1-5', $this->event->weekdays()->hourly()->getExpression());
    }

    public function testWeekdays()
    {
        $this->assertEquals('* * * * 1-5', $this->event->weekdays()->getExpression());
    }

    public function testWeeklyOn()
    {
        $this->assertEquals('0 8 * * 1', $this->event->weeklyOn(1, '08:00')->getExpression());
    }

    public function testWeeklyOnWithMinutes()
    {
        $this->assertEquals('12 8 * * 1', $this->event->weeklyOn(1, '08:12')->getExpression());
    }

    /**
     * @expectedException \Exception
     */
    public function testWeeklyOnAcceptWordException()
    {
        $this->event->weeklyOn(1, 'word');
    }

    public function testAt()
    {
        $this->assertEquals('22 13 * * 1', $this->event->weekly()->mondays()->at('13:22')->getExpression());
    }

    /**
     * @expectedException \Exception
     */
    public function testAtAcceptWordException()
    {
        $this->event->weekly()->mondays()->at('word');
    }

    public function testSundays()
    {
        $this->assertEquals('* * * * 0', $this->event->sundays()->getExpression());
    }

    public function testMondays()
    {
        $this->assertEquals('* * * * 1', $this->event->mondays()->getExpression());
    }

    public function testTuesdays()
    {
        $this->assertEquals('* * * * 2', $this->event->tuesdays()->getExpression());
    }

    public function testWednesdays()
    {
        $this->assertEquals('* * * * 3', $this->event->wednesdays()->getExpression());
    }

    public function testThursdays()
    {
        $this->assertEquals('* * * * 4', $this->event->thursdays()->getExpression());
    }

    public function testFridays()
    {
        $this->assertEquals('* * * * 5', $this->event->fridays()->getExpression());
    }

    public function testSaturdays()
    {
        $this->assertEquals('* * * * 6', $this->event->saturdays()->getExpression());
    }

    public function testQuarterly()
    {
        $this->assertEquals('0 0 1 1-12/3 *', $this->event->quarterly()->getExpression());
    }

    public function testFrequencyMacro()
    {
        Event::macro('everyXMinutes', function ($x) {
            return $this->spliceIntoPosition(1, "*/{$x}");
        });

        $this->assertEquals('*/6 * * * *', $this->event->everyXMinutes(6)->getExpression());
    }
}
