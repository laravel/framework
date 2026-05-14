<?php

declare(strict_types=1);

namespace Illuminate\Tests\Integration\Console\Scheduling;

use Illuminate\Console\Scheduling\Event;
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
            $this->assertTrue($events[0]->releaseOnTerminationSignals);
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
            'evenWhenPaused' => ['evenWhenPaused', true],
            'withoutOverlapping' => ['withoutOverlapping', mt_rand(1000, 1400)],
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

    public function testGroupCanOptOutOfReleaseOnTerminationSignals()
    {
        $schedule = new ScheduleClass;
        $schedule->daily()
            ->withoutOverlapping(1440, releaseOnTerminationSignals: false)
            ->group(function ($schedule) {
                $schedule->command('inspire');
            });

        $events = $schedule->events();
        $this->assertTrue($events[0]->withoutOverlapping);
        $this->assertFalse($events[0]->releaseOnTerminationSignals);
    }

    public function testGroupAppliesEventMacrosToAllEvents()
    {
        Event::macro('sentryMonitor', function () {
            $this->sentryMonitored = true;

            return $this;
        });

        $schedule = new ScheduleClass;
        $schedule->daily()->sentryMonitor()->group(function ($schedule) {
            $schedule->command('inspire');
            $schedule->command('inspire');
        });

        $events = $schedule->events();
        $this->assertTrue($events[0]->sentryMonitored);
        $this->assertTrue($events[1]->sentryMonitored);
        $this->assertSame('0 0 * * *', $events[0]->expression);
        $this->assertSame('0 0 * * *', $events[1]->expression);

        Event::flushMacros();
    }

    public function testGroupAppliesEventMacroCalledBeforeBuiltInAttributes()
    {
        Event::macro('sentryMonitor', function () {
            $this->sentryMonitored = true;

            return $this;
        });

        $schedule = new ScheduleClass;
        $schedule->sentryMonitor()->daily()->onOneServer()->group(function ($schedule) {
            $schedule->command('inspire');
        });

        $events = $schedule->events();
        $this->assertTrue($events[0]->sentryMonitored);
        $this->assertTrue($events[0]->onOneServer);
        $this->assertSame('0 0 * * *', $events[0]->expression);

        Event::flushMacros();
    }

    public function testGroupAppliesMultipleEventMacros()
    {
        Event::macro('sentryMonitor', function () {
            $this->sentryMonitored = true;

            return $this;
        });

        Event::macro('customTag', function ($tag) {
            $this->customTag = $tag;

            return $this;
        });

        $schedule = new ScheduleClass;
        $schedule->daily()->sentryMonitor()->customTag('billing')->group(function ($schedule) {
            $schedule->command('inspire');
            $schedule->command('inspire');
        });

        $events = $schedule->events();
        $this->assertTrue($events[0]->sentryMonitored);
        $this->assertSame('billing', $events[0]->customTag);
        $this->assertTrue($events[1]->sentryMonitored);
        $this->assertSame('billing', $events[1]->customTag);

        Event::flushMacros();
    }

    public function testNestedGroupInheritsEventMacros()
    {
        Event::macro('sentryMonitor', function () {
            $this->sentryMonitored = true;

            return $this;
        });

        $schedule = new ScheduleClass;
        $schedule->daily()->sentryMonitor()->group(function ($schedule) {
            $schedule->command('inspire');
            $schedule->weekly()->group(function ($schedule) {
                $schedule->command('inspire');
            });
        });

        $events = $schedule->events();
        $this->assertTrue($events[0]->sentryMonitored);
        $this->assertSame('0 0 * * *', $events[0]->expression);
        $this->assertTrue($events[1]->sentryMonitored);
        $this->assertSame('0 0 * * 0', $events[1]->expression);

        Event::flushMacros();
    }

    public function testGroupAppliesOnFailureCallbackToAllEvents()
    {
        $calls = [];

        $schedule = new ScheduleClass;
        $schedule->daily()
            ->onFailure(function () use (&$calls) {
                $calls[] = 'group-failure';
            })
            ->group(function ($schedule) {
                $schedule->command('inspire');
                $schedule->command('inspire');
            });

        $events = $schedule->events();
        $this->assertCount(2, $events);

        $events[0]->finish(app(), 1);
        $events[1]->finish(app(), 1);

        $this->assertSame(['group-failure', 'group-failure'], $calls);
    }

    public function testGroupOnFailureCallbackDoesNotRunOnSuccess()
    {
        $calls = [];

        $schedule = new ScheduleClass;
        $schedule->daily()
            ->onFailure(function () use (&$calls) {
                $calls[] = 'group-failure';
            })
            ->group(function ($schedule) {
                $schedule->command('inspire');
            });

        $events = $schedule->events();
        $events[0]->finish(app(), 0);

        $this->assertSame([], $calls);
    }

    public function testGroupAppliesOnSuccessCallbackToAllEvents()
    {
        $calls = [];

        $schedule = new ScheduleClass;
        $schedule->daily()
            ->onSuccess(function () use (&$calls) {
                $calls[] = 'group-success';
            })
            ->group(function ($schedule) {
                $schedule->command('inspire');
                $schedule->command('inspire');
            });

        $events = $schedule->events();
        $events[0]->finish(app(), 0);
        $events[1]->finish(app(), 0);

        $this->assertSame(['group-success', 'group-success'], $calls);
    }

    public function testGroupAppliesBeforeAndAfterCallbacksToAllEvents()
    {
        $calls = [];

        $schedule = new ScheduleClass;
        $schedule->daily()
            ->before(function () use (&$calls) {
                $calls[] = 'before';
            })
            ->after(function () use (&$calls) {
                $calls[] = 'after';
            })
            ->then(function () use (&$calls) {
                $calls[] = 'then';
            })
            ->group(function ($schedule) {
                $schedule->command('inspire');
            });

        $events = $schedule->events();
        $events[0]->callBeforeCallbacks(app());
        $events[0]->finish(app(), 0);

        $this->assertSame(['before', 'after', 'then'], $calls);
    }

    public function testGroupCallbacksCombineWithEventLevelCallbacks()
    {
        $calls = [];

        $schedule = new ScheduleClass;
        $schedule->daily()
            ->onFailure(function () use (&$calls) {
                $calls[] = 'group';
            })
            ->group(function ($schedule) use (&$calls) {
                $schedule->command('inspire')->onFailure(function () use (&$calls) {
                    $calls[] = 'event';
                });
            });

        $events = $schedule->events();
        $events[0]->finish(app(), 1);

        $this->assertSame(['group', 'event'], $calls);
    }

    public function testNestedGroupInheritsLifecycleCallbacks()
    {
        $calls = [];

        $schedule = new ScheduleClass;
        $schedule->daily()
            ->onFailure(function () use (&$calls) {
                $calls[] = 'outer';
            })
            ->group(function ($schedule) use (&$calls) {
                $schedule->command('inspire');
                $schedule->weekly()
                    ->onFailure(function () use (&$calls) {
                        $calls[] = 'inner';
                    })
                    ->group(function ($schedule) {
                        $schedule->command('inspire');
                    });
            });

        $events = $schedule->events();
        $this->assertCount(2, $events);

        $events[0]->finish(app(), 1);
        $this->assertSame(['outer'], $calls);

        $events[1]->finish(app(), 1);
        $this->assertSame(['outer', 'outer', 'inner'], $calls);
    }
}
