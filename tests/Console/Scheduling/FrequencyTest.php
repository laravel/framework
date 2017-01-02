<?php

use Illuminate\Console\Scheduling\ManagesFrequencies;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class FrequencyTest extends TestCase
{
    /**
     * @var \Illuminate\Console\Scheduling\Event
     */
    protected $event;

    public function setUp()
    {
        /**
         * Fake the Illuminate\Console\Scheduling\Event class
         */
        $this->event = new class {
            use ManagesFrequencies;

            public $expression = '* * * * * *';

            public function getExpression()
            {
                return $this->expression;
            }
        };
    }

    /**
     * @test
     */
    function every_minute()
    {
        $this->assertEquals('* * * * * *', $this->event->getExpression());
        $this->assertEquals('* * * * * *', $this->event->everyMinute()->getExpression());
    }

    /**
     * @test
     */
    function every_five_minutes()
    {
        $this->assertEquals("*/5 * * * * *", $this->event->everyFiveMinutes()->getExpression());
    }

    /**
     * @test
     */
    function daily()
    {
        $this->assertEquals('0 0 * * * *', $this->event->daily()->getExpression());
    }

    /**
     * @test
     */
    function twice_daily()
    {
        $this->assertEquals('0 3,15 * * * *', $this->event->twiceDaily(3, 15)->getExpression());
    }

    /**
     * @test
     */
    function override_with_hourly()
    {
        $this->assertEquals('0 * * * * *', $this->event->everyFiveMinutes()->hourly()->getExpression());
    }

    /**
     * @test
     */
    function monthly_on()
    {
        $this->assertEquals('0 15 4 * * *', $this->event->monthlyOn(4, '15:00')->getExpression());
    }

    /**
     * @test
     */
    function monthly_on_with_minutes()
    {
        $this->assertEquals('15 15 4 * * *', $this->event->monthlyOn(4, '15:15')->getExpression());
    }

    /**
     * @test
     */
    function weekdays_daily()
    {
        $this->assertEquals('0 0 * * 1-5 *', $this->event->weekdays()->daily()->getExpression());
    }

    /**
     * @test
     */
    function weekdays_hourly()
    {
        $this->assertEquals('0 * * * 1-5 *', $this->event->weekdays()->hourly()->getExpression());
    }

    /**
     * @test
     */
    function weekdays()
    {
        $this->assertEquals('* * * * 1-5 *', $this->event->weekdays()->getExpression());
    }

    /**
     * @test
     */
    function sunday()
    {
        $this->assertEquals('* * * * 0 *', $this->event->sundays()->getExpression());
    }

    /**
     * @test
     */
    function monday()
    {
        $this->assertEquals('* * * * 1 *', $this->event->mondays()->getExpression());
    }

    /**
     * @test
     */
    function tuesday()
    {
        $this->assertEquals('* * * * 2 *', $this->event->tuesdays()->getExpression());
    }

    /**
     * @test
     */
    function wednesday()
    {
        $this->assertEquals('* * * * 3 *', $this->event->wednesdays()->getExpression());
    }

    /**
     * @test
     */
    function thursday()
    {
        $this->assertEquals('* * * * 4 *', $this->event->thursdays()->getExpression());
    }

    /**
     * @test
     */
    function friday()
    {
        $this->assertEquals('* * * * 5 *', $this->event->fridays()->getExpression());
    }

    /**
     * @test
     */
    function saturday()
    {
        $this->assertEquals('* * * * 6 *', $this->event->saturdays()->getExpression());
    }
}
