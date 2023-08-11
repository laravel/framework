<?php

namespace Illuminate\Tests\Integration\Console;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Queue\ShouldQueue;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class CommandWithDefaultsTest extends TestCase
{
    #[DataProvider('allowedPropertiesProvider')]
    public function testWithDefaultsWithoutCallbackAppliesCallbacksToAllScheduledCalls(string $property, $value)
    {
        $schedule = new Schedule();

        $schedule->withEventDefaults([$property => $value]);

        $schedule->call('/path/to/command');
        $schedule->call(SomeJob::class);
        $events = $schedule->events();
        $this->assertSame($value, $events[0]->{$property});
        $this->assertSame($value, $events[1]->{$property});
    }

    #[DataProvider('allowedPropertiesProvider')]
    public function testWithDefaultsWithCallbackAppliesToCurrentClosureOnly(string $property, $value)
    {
        $schedule = new Schedule();

        $schedule->call('/path/to/command')->{$property} = $value;
        $schedule->withEventDefaults([$property => $value], function ($schedule) {
            $schedule->call('/path/to/command');
        });
        $dummyEvent = $schedule->call('/path/to/command');

        $events = $schedule->events();
        $this->assertSame($value, $events[0]->{$property});
        $this->assertSame($value, $events[1]->{$property});
        $this->assertEquals($dummyEvent->{$property}, $events[2]->{$property});
    }

    public static function allowedPropertiesProvider(): array
    {
        return [
            'onOneServer' => ['onOneServer', fake()->boolean()],
            'timezone' => ['timezone', fake()->timezone()],
            'user' => ['user', fake()->userName()],
            'environments' => ['environments', fake()->randomElements(['local', 'production', 'testing', 'staging'], 2)],
            'evenInMaintenanceMode' => ['evenInMaintenanceMode', fake()->boolean()],
            'withoutOverlapping' => ['withoutOverlapping', fake()->boolean()],
            'runInBackground' => ['runInBackground', fake()->boolean()],
        ];
    }
}

class SomeJob implements ShouldQueue
{

}
