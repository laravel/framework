<?php

namespace Illuminate\Console\Scheduling;

use Illuminate\Container\Container;
use InvalidArgumentException;

class ScheduleRegistrar
{
    /**
     * Register discovered tasks on the schedule.
     *
     * @param  Schedule  $schedule
     * @param  iterable<int, DiscoveredScheduledTask>  $tasks
     * @return void
     */
    public function register(Schedule $schedule, iterable $tasks): void
    {
        foreach ($tasks as $task) {
            $this->registerTask($schedule, $task);
        }
    }

    /**
     * Register a discovered task.
     *
     * @param  Schedule  $schedule
     * @param  DiscoveredScheduledTask  $task
     * @return void
     */
    protected function registerTask(
        Schedule $schedule,
        DiscoveredScheduledTask $task
    ): void {
        $container = Container::getInstance();

        $event = $schedule->call(function () use ($container, $task) {
            $instance = $container->make($task->class);

            return $container->call([
                $instance,
                $task->method,
            ]);
        });

        $attribute = $task->schedule;
        $frequency = $attribute->frequency;

        if (
            ! method_exists($event, $frequency)
            && ! Event::hasMacro($frequency)
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    'Unsupported schedule frequency [%s] on scheduled task [%s].',
                    $frequency,
                    $task->name(),
                )
            );
        }

        $event->{$frequency}(...$attribute->arguments);

        if ($attribute->at !== null) {
            $event->at($attribute->at);
        }

        if ($attribute->timezone !== null) {
            $event->timezone($attribute->timezone);
        }

        $event->name($task->name());

        if ($attribute->withoutOverlapping !== false) {
            $event->withoutOverlapping(
                $attribute->withoutOverlapping
            );
        }

        if ($attribute->onOneServer) {
            $event->onOneServer();
        }

        if ($attribute->evenInMaintenanceMode) {
            $event->evenInMaintenanceMode();
        }

        if ($attribute->environments !== []) {
            $event->environments(
                $attribute->environments
            );
        }
    }
}
