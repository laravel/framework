<?php

namespace Illuminate\Console\Attributes;

use Attribute;
use Illuminate\Console\Scheduling\Event;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class Schedule
{
    /**
     * Create a new schedule attribute instance.
     *
     * @param  string|null  $expression  A cron expression (e.g. '0 8 * * *').
     * @param  bool  $everySecond
     * @param  bool  $everyTwoSeconds
     * @param  bool  $everyFiveSeconds
     * @param  bool  $everyTenSeconds
     * @param  bool  $everyFifteenSeconds
     * @param  bool  $everyTwentySeconds
     * @param  bool  $everyThirtySeconds
     * @param  bool  $everyMinute
     * @param  bool  $everyTwoMinutes
     * @param  bool  $everyThreeMinutes
     * @param  bool  $everyFourMinutes
     * @param  bool  $everyFiveMinutes
     * @param  bool  $everyTenMinutes
     * @param  bool  $everyFifteenMinutes
     * @param  bool  $everyThirtyMinutes
     * @param  bool  $hourly
     * @param  int|array|null  $hourlyAt  Minute offset(s) past the hour (e.g. 17 or [17, 37]).
     * @param  bool  $everyOddHour
     * @param  bool  $everyTwoHours
     * @param  bool  $everyThreeHours
     * @param  bool  $everyFourHours
     * @param  bool  $everySixHours
     * @param  bool  $daily
     * @param  string|null  $dailyAt  Time to run daily (e.g. '13:00').
     * @param  bool  $weekly
     * @param  bool  $monthly
     * @param  bool  $quarterly
     * @param  bool  $yearly
     * @param  array|string  $environments  Limit to specific environments.
     * @param  \DateTimeZone|string|null  $timezone
     * @param  string|null  $description
     * @param  bool  $withoutOverlapping
     * @param  bool  $onOneServer
     * @param  bool  $runInBackground
     * @param  bool  $evenInMaintenanceMode
     */
    public function __construct(
        public readonly ?string $expression = null,
        public readonly bool $everySecond = false,
        public readonly bool $everyTwoSeconds = false,
        public readonly bool $everyFiveSeconds = false,
        public readonly bool $everyTenSeconds = false,
        public readonly bool $everyFifteenSeconds = false,
        public readonly bool $everyTwentySeconds = false,
        public readonly bool $everyThirtySeconds = false,
        public readonly bool $everyMinute = false,
        public readonly bool $everyTwoMinutes = false,
        public readonly bool $everyThreeMinutes = false,
        public readonly bool $everyFourMinutes = false,
        public readonly bool $everyFiveMinutes = false,
        public readonly bool $everyTenMinutes = false,
        public readonly bool $everyFifteenMinutes = false,
        public readonly bool $everyThirtyMinutes = false,
        public readonly bool $hourly = false,
        public readonly int|array|null $hourlyAt = null,
        public readonly bool $everyOddHour = false,
        public readonly bool $everyTwoHours = false,
        public readonly bool $everyThreeHours = false,
        public readonly bool $everyFourHours = false,
        public readonly bool $everySixHours = false,
        public readonly bool $daily = false,
        public readonly ?string $dailyAt = null,
        public readonly bool $weekly = false,
        public readonly bool $monthly = false,
        public readonly bool $quarterly = false,
        public readonly bool $yearly = false,
        public readonly array|string $environments = [],
        public readonly mixed $timezone = null,
        public readonly ?string $description = null,
        public readonly bool $withoutOverlapping = false,
        public readonly bool $onOneServer = false,
        public readonly bool $runInBackground = false,
        public readonly bool $evenInMaintenanceMode = false,
    ) {
    }

    /**
     * Apply the attribute's frequency and options to the given schedule event.
     */
    public function applyTo(Event $event): void
    {
        $this->applyFrequency($event);
        $this->applyOptions($event);
    }

    /**
     * Apply the frequency to the event.
     */
    protected function applyFrequency(Event $event): void
    {
        match (true) {
            $this->expression !== null => $event->cron($this->expression),
            $this->everySecond => $event->everySecond(),
            $this->everyTwoSeconds => $event->everyTwoSeconds(),
            $this->everyFiveSeconds => $event->everyFiveSeconds(),
            $this->everyTenSeconds => $event->everyTenSeconds(),
            $this->everyFifteenSeconds => $event->everyFifteenSeconds(),
            $this->everyTwentySeconds => $event->everyTwentySeconds(),
            $this->everyThirtySeconds => $event->everyThirtySeconds(),
            $this->everyMinute => $event->everyMinute(),
            $this->everyTwoMinutes => $event->everyTwoMinutes(),
            $this->everyThreeMinutes => $event->everyThreeMinutes(),
            $this->everyFourMinutes => $event->everyFourMinutes(),
            $this->everyFiveMinutes => $event->everyFiveMinutes(),
            $this->everyTenMinutes => $event->everyTenMinutes(),
            $this->everyFifteenMinutes => $event->everyFifteenMinutes(),
            $this->everyThirtyMinutes => $event->everyThirtyMinutes(),
            $this->hourlyAt !== null => $event->hourlyAt($this->hourlyAt),
            $this->hourly => $event->hourly(),
            $this->everyOddHour => $event->everyOddHour(),
            $this->everyTwoHours => $event->everyTwoHours(),
            $this->everyThreeHours => $event->everyThreeHours(),
            $this->everyFourHours => $event->everyFourHours(),
            $this->everySixHours => $event->everySixHours(),
            $this->dailyAt !== null => $event->dailyAt($this->dailyAt),
            $this->daily => $event->daily(),
            $this->weekly => $event->weekly(),
            $this->monthly => $event->monthly(),
            $this->quarterly => $event->quarterly(),
            $this->yearly => $event->yearly(),
            default => $event->everyMinute(),
        };
    }

    /**
     * Apply the options to the event.
     */
    protected function applyOptions(Event $event): void
    {
        if (! empty($this->environments)) {
            $event->environments($this->environments);
        }

        if ($this->timezone !== null) {
            $event->timezone($this->timezone);
        }

        if ($this->description !== null) {
            $event->description($this->description);
        }

        if ($this->withoutOverlapping) {
            $event->withoutOverlapping();
        }

        if ($this->onOneServer) {
            $event->onOneServer();
        }

        if ($this->runInBackground) {
            $event->runInBackground();
        }

        if ($this->evenInMaintenanceMode) {
            $event->evenInMaintenanceMode();
        }
    }
}
