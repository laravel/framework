<?php

namespace Illuminate\Tests\Console;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Foundation\Application;
use Illuminate\Support\Carbon;
use Illuminate\Tests\Console\Fixtures\FakeEventMutex;
use Mockery;
use PHPUnit\Framework\TestCase;

class ConsoleScheduledEventTest extends TestCase
{
    /**
     * The default configuration timezone.
     *
     * @var string
     */
    protected $defaultTimezone;

    protected function setUp(): void
    {
        $this->defaultTimezone = date_default_timezone_get();
        date_default_timezone_set('UTC');
    }

    protected function tearDown(): void
    {
        date_default_timezone_set($this->defaultTimezone);
    }

    public function testBasicCronCompilation()
    {
        $app = Mockery::mock(Application::class.'[isDownForMaintenance,environment]');
        $app->expects('isDownForMaintenance')->times(3)->andReturn(false);
        $app->expects('environment')->times(3)->andReturn('production');

        $event = new Event(new FakeEventMutex, 'php foo');
        $this->assertSame('* * * * *', $event->getExpression());
        $this->assertTrue($event->isDue($app));
        $this->assertTrue($event->skip(function () {
            return true;
        })->isDue($app));
        $this->assertFalse($event->skip(function () {
            return true;
        })->filtersPass($app));

        $event = new Event(new FakeEventMutex, 'php foo');
        $this->assertSame('* * * * *', $event->getExpression());
        $this->assertFalse($event->environments('local')->isDue($app));

        $event = new Event(new FakeEventMutex, 'php foo');
        $this->assertSame('* * * * *', $event->getExpression());
        $this->assertFalse($event->when(function () {
            return false;
        })->filtersPass($app));

        $event = new Event(new FakeEventMutex, 'php foo');
        $this->assertSame('* * * * *', $event->getExpression());
        $this->assertFalse($event->when(false)->filtersPass($app));

        // chained rules should be commutative
        $eventA = new Event(new FakeEventMutex, 'php foo');
        $eventB = new Event(new FakeEventMutex, 'php foo');
        $this->assertEquals(
            $eventA->daily()->hourly()->getExpression(),
            $eventB->hourly()->daily()->getExpression());

        $eventA = new Event(new FakeEventMutex, 'php foo');
        $eventB = new Event(new FakeEventMutex, 'php foo');
        $this->assertEquals(
            $eventA->weekdays()->hourly()->getExpression(),
            $eventB->hourly()->weekdays()->getExpression());
    }

    public function testEventIsDueCheck()
    {
        $app = Mockery::mock(Application::class.'[isDownForMaintenance,environment]');
        $app->expects('isDownForMaintenance')->times(2)->andReturn(false);
        $app->expects('environment')->times(2)->andReturn('production');
        Carbon::setTestNow(Carbon::create(2015, 1, 1, 0, 0, 0));

        $event = new Event(new FakeEventMutex, 'php foo');
        $this->assertSame('* * * * 4', $event->thursdays()->getExpression());
        $this->assertTrue($event->isDue($app));

        $event = new Event(new FakeEventMutex, 'php foo');
        $this->assertSame('0 19 * * 3', $event->wednesdays()->at('19:00')->timezone('EST')->getExpression());
        $this->assertTrue($event->isDue($app));
    }

    public function testTimeBetweenChecks()
    {
        $app = Mockery::mock(Application::class.'[isDownForMaintenance,environment]');

        Carbon::setTestNow(Carbon::today()->addHours(9));

        $event = new Event(new FakeEventMutex, 'php foo', 'UTC');
        $this->assertTrue($event->between('8:00', '10:00')->filtersPass($app));

        $event = new Event(new FakeEventMutex, 'php foo', 'UTC');
        $this->assertTrue($event->between('9:00', '9:00')->filtersPass($app));

        $event = new Event(new FakeEventMutex, 'php foo', 'UTC');
        $this->assertTrue($event->between('23:00', '10:00')->filtersPass($app));

        $event = new Event(new FakeEventMutex, 'php foo', 'UTC');
        $this->assertTrue($event->between('8:00', '6:00')->filtersPass($app));

        $event = new Event(new FakeEventMutex, 'php foo', 'UTC');
        $this->assertFalse($event->between('10:00', '11:00')->filtersPass($app));

        $event = new Event(new FakeEventMutex, 'php foo', 'UTC');
        $this->assertFalse($event->between('10:00', '8:00')->filtersPass($app));
    }

    public function testTimeBetweenChecksTimezoneCallOrder()
    {
        $app = Mockery::mock(Application::class.'[isDownForMaintenance,environment]');

        Carbon::setTestNow(Carbon::parse('2024-07-01 09:00:00', 'UTC'));

        $event = new Event(new FakeEventMutex, 'php foo', 'UTC');
        $this->assertTrue($event->timezone('Europe/Rome')->between('10:00', '12:00')->filtersPass($app));

        $event = new Event(new FakeEventMutex, 'php foo', 'UTC');
        $this->assertTrue($event->between('10:00', '12:00')->timezone('Europe/Rome')->filtersPass($app));

        $event = new Event(new FakeEventMutex, 'php foo', 'UTC');
        $this->assertFalse($event->timezone('Europe/Rome')->unlessBetween('10:00', '12:00')->filtersPass($app));

        $event = new Event(new FakeEventMutex, 'php foo', 'UTC');
        $this->assertFalse($event->unlessBetween('10:00', '12:00')->timezone('Europe/Rome')->filtersPass($app));
    }

    public function testTimeUnlessBetweenChecks()
    {
        $app = Mockery::mock(Application::class.'[isDownForMaintenance,environment]');

        Carbon::setTestNow(Carbon::today()->addHours(9));

        $event = new Event(new FakeEventMutex, 'php foo', 'UTC');
        $this->assertFalse($event->unlessBetween('8:00', '10:00')->filtersPass($app));

        $event = new Event(new FakeEventMutex, 'php foo', 'UTC');
        $this->assertFalse($event->unlessBetween('9:00', '9:00')->filtersPass($app));

        $event = new Event(new FakeEventMutex, 'php foo', 'UTC');
        $this->assertFalse($event->unlessBetween('23:00', '10:00')->filtersPass($app));

        $event = new Event(new FakeEventMutex, 'php foo', 'UTC');
        $this->assertFalse($event->unlessBetween('8:00', '6:00')->filtersPass($app));

        $event = new Event(new FakeEventMutex, 'php foo', 'UTC');
        $this->assertTrue($event->unlessBetween('10:00', '11:00')->filtersPass($app));

        $event = new Event(new FakeEventMutex, 'php foo', 'UTC');
        $this->assertTrue($event->unlessBetween('10:00', '8:00')->filtersPass($app));
    }

    public function testEnvironmentsWithEnums()
    {
        $event = new Event(new FakeEventMutex, 'php foo');

        $event->environments(ScheduledEventTestEnvironment::Production);
        $this->assertSame(['production'], $event->environments);
        $this->assertTrue($event->runsInEnvironment('production'));
        $this->assertFalse($event->runsInEnvironment('local'));

        $event->environments([ScheduledEventTestEnvironment::Local, 'staging']);
        $this->assertSame(['local', 'staging'], $event->environments);

        $event->environments(ScheduledEventTestEnvironment::Local, ScheduledEventTestEnvironment::Production);
        $this->assertSame(['local', 'production'], $event->environments);
    }
}

enum ScheduledEventTestEnvironment: string
{
    case Local = 'local';
    case Production = 'production';
}
