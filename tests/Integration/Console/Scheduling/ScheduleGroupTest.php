<?php

declare(strict_types=1);

namespace Illuminate\Tests\Integration\Console\Scheduling;

use Illuminate\Console\Scheduling\Schedule as ScheduleClass;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Tests\Console\Fixtures\JobToTestWithSchedule;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class ScheduleGroupTest extends TestCase
{
    public function testGroupCanSetScheduleCronExpression()
    {
        $schedule = new ScheduleClass;

        $schedule
            ->daily()
            ->group(function (ScheduleClass $schedule) {
                $schedule->command('inspire');
            });

        $events = $schedule->events();
        $this->assertSame('0 0 * * *', $events[0]->expression);
    }

    public function testGroupedScheduleCanOverrideGroupCronExpression()
    {
        Schedule::daily()->group(function () {
            Schedule::command('inspire');
            Schedule::command('inspire')
                ->twiceDaily();
        });

        $events = Schedule::events();
        $this->assertSame('0 0 * * *', $events[0]->expression);
        $this->assertSame('0 1,13 * * *', $events[1]->expression);
    }

    public function testGroupCanSetScheduleRepeatSeconds()
    {
        Schedule::everyMinute()
            ->everyThirtySeconds()
            ->group(function () {
                Schedule::command('inspire');
            });

        $events = Schedule::events();
        $this->assertSame(30, $events[0]->repeatSeconds);
        $this->assertSame('* * * * *', $events[0]->expression);
    }

    public function testGroupedScheduleCanOverrideGroupRepeatSeconds()
    {
        Schedule::everyMinute()
            ->everyThirtySeconds()
            ->group(function () {
                Schedule::command('inspire');
                Schedule::command('inspire')
                    ->everyTwentySeconds();
            });

        $events = Schedule::events();
        $this->assertSame(30, $events[0]->repeatSeconds);
        $this->assertSame('* * * * *', $events[0]->expression);

        $this->assertSame(20, $events[1]->repeatSeconds);
        $this->assertSame('* * * * *', $events[1]->expression);
    }

    public function testGroupedScheduleCanBeNested()
    {
        Schedule::daily()
            ->timezone('UTC')
            ->group(function () {
                Schedule::command('inspire');
                Schedule::timezone('Asia/Dhaka')->group(function () {
                    Schedule::command('inspire');
                });
            });

        $events = Schedule::events();
        $this->assertSame('UTC', $events[0]->timezone);
        $this->assertSame('Asia/Dhaka', $events[1]->timezone);
    }

    #[DataProvider('groupAttributes')]
    public function testGroupCanApplyAttributeToSchedules(string $property, mixed $value)
    {
        Schedule::$property($value)->group(function () {
            Schedule::command('inspire');
        });

        $events = Schedule::events();

        if ($property !== 'withoutOverlapping') {
            $this->assertSame($value, $events[0]->$property);
        } else {
            $this->assertSame($value, $events[0]->expiresAt);
            $this->assertTrue($events[0]->withoutOverlapping);
        }
    }

    public static function groupAttributes(): array
    {
        return [
            'user' => ['user', fake()->userName()],
            'timezone' => ['timezone', fake()->timezone()],
            'onOneServer' => ['onOneServer', true],
            'environments' => [
                'environments',
                fake()->randomElements(['local', 'production', 'testing', 'staging'], 2),
            ],
            'runInBackground' => ['runInBackground', true],
            'evenInMaintenanceMode' => ['evenInMaintenanceMode', true],
            'withoutOverlapping' => ['withoutOverlapping', rand(1000, 1400)],
        ];
    }

    #[DataProvider('scheduleTestCases')]
    public function testGroupedScheduleExecution($time, $expected, $description)
    {
        Carbon::setTestNow($time);
        $app = app();

        Schedule::days([1, 2, 3, 4, 5, 6])->group(function () {
            Schedule::between('07:00', '08:00')->group(function () {
                Schedule::call(fn () => 'Task 1')->everyMinute();
                Schedule::call(fn () => 'Task 2')->everyFiveMinutes();
            });

            Schedule::call(fn () => 'Task 3')->at('08:05');
        });

        $events = Schedule::events();

        foreach (array_keys($expected) as $index => $task) {
            $this->assertTaskExecution(
                $events[$index],
                $app,
                $expected[$task],
                "[$description] $task should ".($expected[$task] ? 'run' : 'not run')
            );
        }

        Carbon::setTestNow();
    }

    private function assertTaskExecution($event, $app, $expected, $message): void
    {
        $this->assertSame(
            $expected,
            $event->filtersPass($app) && $event->isDue($app),
            $message
        );
    }

    public static function scheduleTestCases()
    {
        return [
            [
                Carbon::create(2024, 1, 1, 7, 30),
                [
                    'Task 1' => true,
                    'Task 2' => true,
                    'Task 3' => false,
                ],
                'Tasks at 07:30',
            ],
            [
                Carbon::create(2024, 1, 1, 8, 5),
                [
                    'Task 1' => false,
                    'Task 2' => false,
                    'Task 3' => true,
                ],
                'Tasks at 08:05',
            ],
        ];
    }

    public function testGroupedPendingEventAttribute()
    {
        $schedule = new ScheduleClass;
        $schedule->weekdays()->group(function ($schedule) {
            $schedule->command('inspire')->at('00:00'); // this is event, not pending attribute
            $schedule->at('01:00')->command('inspire'); // this is pending attribute
            $schedule->command('inspire');  // this goes back to group pending attribute
        });

        $events = $schedule->events();
        $this->assertSame('0 0 * * 1-5', $events[0]->expression);
        $this->assertSame('0 1 * * 1-5', $events[1]->expression);
        $this->assertSame('* * * * 1-5', $events[2]->expression);
    }

    public function testGroupedPendingEventAttributesWithoutOverlapping()
    {
        $schedule = new ScheduleClass;
        $schedule->weekdays()->withoutOverlapping()->group(function ($schedule) {
            $schedule->command('inspire')->at('14:00'); // this is event, not pending attribute
            $schedule->at('03:00')->command('inspire'); // this is pending attribute
            $schedule->command('inspire');  // this goes back to group pending attribute
            $schedule->job(JobToTestWithSchedule::class)->at('04:00');  // this is pending attribute
        });

        $events = $schedule->events();
        $this->assertSame('0 14 * * 1-5', $events[0]->expression);
        $this->assertSame('0 3 * * 1-5', $events[1]->expression);
        $this->assertSame('* * * * 1-5', $events[2]->expression);
        $this->assertSame('0 4 * * 1-5', $events[3]->expression);
    }
}
