<?php

declare(strict_types=1);

namespace Illuminate\Tests\Console\Scheduling;

use Illuminate\Console\Attributes\Schedule;
use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\CallbackEvent;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\EventMutex;
use Illuminate\Console\Scheduling\Schedule as Scheduler;
use Illuminate\Console\Scheduling\SchedulingMutex;
use Illuminate\Container\Container;
use Illuminate\Contracts\Queue\ShouldQueue;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class ScheduleAttributeTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        parent::setUp();

        $this->container = new Container;
        Container::setInstance($this->container);
        $this->container->instance(EventMutex::class, m::mock(EventMutex::class));
        $this->container->instance(SchedulingMutex::class, m::mock(SchedulingMutex::class));
    }

    protected function tearDown(): void
    {
        m::close();
        Container::setInstance(null);

        parent::tearDown();
    }

    public function test_cron_expression_is_applied(): void
    {
        $schedule = new Scheduler;
        $event = $schedule->command(ScheduledWithCronCommand::class);

        $reflection = new \ReflectionClass(ScheduledWithCronCommand::class);
        $attribute = $reflection->getAttributes(Schedule::class)[0]->newInstance();
        $attribute->applyTo($event);

        $this->assertEquals('0 8 * * *', $event->expression);
    }

    public function test_every_minute_frequency_is_applied(): void
    {
        $event = $this->makeEvent();

        (new Schedule(everyMinute: true))->applyTo($event);

        $this->assertEquals('* * * * *', $event->expression);
    }

    public function test_hourly_frequency_is_applied(): void
    {
        $event = $this->makeEvent();

        (new Schedule(hourly: true))->applyTo($event);

        $this->assertEquals('0 * * * *', $event->expression);
    }

    public function test_hourly_at_frequency_is_applied(): void
    {
        $event = $this->makeEvent();

        (new Schedule(hourlyAt: 17))->applyTo($event);

        $this->assertEquals('17 * * * *', $event->expression);
    }

    public function test_daily_frequency_is_applied(): void
    {
        $event = $this->makeEvent();

        (new Schedule(daily: true))->applyTo($event);

        $this->assertEquals('0 0 * * *', $event->expression);
    }

    public function test_daily_at_frequency_is_applied(): void
    {
        $event = $this->makeEvent();

        (new Schedule(dailyAt: '13:00'))->applyTo($event);

        $this->assertEquals('0 13 * * *', $event->expression);
    }

    public function test_weekly_frequency_is_applied(): void
    {
        $event = $this->makeEvent();

        (new Schedule(weekly: true))->applyTo($event);

        $this->assertEquals('0 0 * * 0', $event->expression);
    }

    public function test_monthly_frequency_is_applied(): void
    {
        $event = $this->makeEvent();

        (new Schedule(monthly: true))->applyTo($event);

        $this->assertEquals('0 0 1 * *', $event->expression);
    }

    public function test_yearly_frequency_is_applied(): void
    {
        $event = $this->makeEvent();

        (new Schedule(yearly: true))->applyTo($event);

        $this->assertEquals('0 0 1 1 *', $event->expression);
    }

    public function test_environments_option_is_applied(): void
    {
        $event = $this->makeEvent();

        (new Schedule(everyMinute: true, environments: ['production', 'staging']))->applyTo($event);

        $this->assertEquals(['production', 'staging'], $event->environments);
    }

    public function test_description_option_is_applied(): void
    {
        $event = $this->makeEvent();

        (new Schedule(daily: true, description: 'Run daily cleanup'))->applyTo($event);

        $this->assertEquals('Run daily cleanup', $event->description);
    }

    public function test_without_overlapping_option_is_applied(): void
    {
        $event = $this->makeEvent();

        (new Schedule(everyMinute: true, withoutOverlapping: true))->applyTo($event);

        $this->assertTrue($event->withoutOverlapping);
    }

    public function test_on_one_server_option_is_applied(): void
    {
        $event = $this->makeEvent();

        (new Schedule(everyMinute: true, onOneServer: true))->applyTo($event);

        $this->assertTrue($event->onOneServer);
    }

    public function test_run_in_background_option_is_applied(): void
    {
        $event = $this->makeEvent();

        (new Schedule(everyMinute: true, runInBackground: true))->applyTo($event);

        $this->assertTrue($event->runInBackground);
    }

    public function test_even_in_maintenance_mode_option_is_applied(): void
    {
        $event = $this->makeEvent();

        (new Schedule(everyMinute: true, evenInMaintenanceMode: true))->applyTo($event);

        $this->assertTrue($event->evenInMaintenanceMode);
    }

    public function test_attribute_is_repeatable_for_multiple_environments(): void
    {
        $reflection = new \ReflectionClass(ScheduledWithMultipleAttributesCommand::class);
        $attributes = $reflection->getAttributes(Schedule::class);

        $this->assertCount(2, $attributes);

        $first = $attributes[0]->newInstance();
        $this->assertTrue($first->everyMinute);
        $this->assertEquals(['local'], $first->environments);

        $second = $attributes[1]->newInstance();
        $this->assertTrue($second->everyFiveMinutes);
        $this->assertEquals(['production'], $second->environments);
    }

    public function test_attribute_can_be_applied_to_a_job(): void
    {
        $schedule = new Scheduler;

        $reflection = new \ReflectionClass(ScheduledJob::class);
        $attribute = $reflection->getAttributes(Schedule::class)[0]->newInstance();

        $event = $schedule->job(ScheduledJob::class);
        $attribute->applyTo($event);

        $this->assertInstanceOf(CallbackEvent::class, $event);
        $this->assertEquals('0 * * * *', $event->expression);
        $this->assertEquals(['production'], $event->environments);
    }

    private function makeEvent(): Event
    {
        $schedule = new Scheduler;

        return $schedule->command('test:command');
    }
}

#[Schedule(expression: '0 8 * * *', description: 'Run at 8am daily')]
class ScheduledWithCronCommand extends Command
{
    protected $signature = 'test:cron';
}

#[Schedule(everyMinute: true, environments: ['local'])]
#[Schedule(everyFiveMinutes: true, environments: ['production'])]
class ScheduledWithMultipleAttributesCommand extends Command
{
    protected $signature = 'test:multi-schedule';
}

#[Schedule(hourly: true, environments: ['production'])]
class ScheduledJob implements ShouldQueue
{
    public function handle(): void
    {
    }
}
