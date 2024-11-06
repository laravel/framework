<?php

declare(strict_types=1);

namespace Illuminate\Console\Scheduling;

use Closure;

class ScheduleGroup
{
    use ManagesFrequencies;

    /**
     * The callback to be called after the group's events have been registered.
     *
     * @var Closure
     */
    protected $onRegister;

    /**
     * @var Schedule
     */
    protected $schedule;

    /**
     * The cron expression representing the grouped events' frequency.
     *
     * @var string
     */
    protected string $expression = '* * * * *';

    /**
     * How often to repeat the grouped events during a minute.
     *
     * @var int|null
     */
    protected ?int $repeatSeconds = null;

    /**
     * The timezone the grouped events' date should be evaluated on.
     *
     * @var \DateTimeZone|string
     */
    protected $timezone;

    /**
     * The user the grouped events should run as.
     *
     * @var string|null
     */
    protected $user;

    /**
     * The list of environments the grouped events should run under.
     *
     * @var array
     */
    protected array $environments;

    /**
     * Indicates if the grouped events should run in maintenance mode.
     *
     * @var bool
     */
    protected bool $evenInMaintenanceMode;

    /**
     * Indicates if the grouped events should not overlap itself.
     *
     * @var bool
     */
    protected bool $withoutOverlapping;

    /**
     * Indicates if the grouped events should only be allowed to run on one server for each cron expression.
     *
     * @var bool
     */
    protected bool $onOneServer;

    /**
     * The number of minutes the grouped events' mutex should be valid.
     *
     * @var int
     */
    protected $expiresAt;

    /**
     * Indicates if the grouped events should run in the background.
     *
     * @var bool
     */
    protected $runInBackground;

    /**
     * @param  Schedule  $schedule
     * @param  callable  $onRegister
     */
    public function __construct($schedule, $onRegister)
    {
        $this->schedule = $schedule;
        $this->onRegister = $onRegister;
    }

    public function schedules(callable $callback): void
    {
        $callback($this->schedule);

        ($this->onRegister)();
    }

    public function mergeAttributes(Event $event): void
    {
        $event->expression = $this->expression;
        $event->repeatSeconds = $this->repeatSeconds;

        if (isset($this->withoutOverlapping) && $this->withoutOverlapping) {
            $event->withoutOverlapping($this->expiresAt);
        }

        /**
         * Loop through the attributes and only set the ones that were set on the group.
         */
        foreach ($this->attributes() as $attribute) {
            if (isset($this->{$attribute})) {
                $event->{$attribute} = $this->{$attribute};
            }
        }
    }

    /**
     * Set which user the grouped events should be run as.
     *
     * @param  string  $user
     * @return $this
     */
    public function user($user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Limit the environments the grouped events should run in.
     *
     * @param  array|mixed  $environments
     * @return $this
     */
    public function environments($environments)
    {
        $this->environments = is_array($environments) ? $environments : func_get_args();

        return $this;
    }

    /**
     * Allow the grouped events to only run on one server for each cron expression.
     *
     * @return $this
     */
    public function onOneServer()
    {
        $this->onOneServer = true;

        return $this;
    }

    /**
     * State that the grouped events should run in the background.
     *
     * @return $this
     */
    public function runInBackground()
    {
        $this->runInBackground = true;

        return $this;
    }

    /**
     * State that the grouped events should run even in maintenance mode.
     *
     * @return $this
     */
    public function evenInMaintenanceMode()
    {
        $this->evenInMaintenanceMode = true;

        return $this;
    }

    /**
     * Do not allow the grouped events to overlap each other.
     * The expiration time of the underlying cache lock may be specified in minutes.
     *
     * @param  int  $expiresAt
     * @return $this
     */
    public function withoutOverlapping($expiresAt = 1440)
    {
        $this->withoutOverlapping = true;

        $this->expiresAt = $expiresAt;

        return $this;
    }

    /**
     * @return string[]
     */
    protected function attributes()
    {
        return [
            'user',
            'timezone',
            'onOneServer',
            'environments',
            'runInBackground',
            'evenInMaintenanceMode',
        ];
    }
}