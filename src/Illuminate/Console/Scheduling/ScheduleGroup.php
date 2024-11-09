<?php

declare(strict_types=1);

namespace Illuminate\Console\Scheduling;

use Closure;
use DateTimeZone;

class ScheduleGroup
{
    use ManagesFrequencies;

    /**
     * The cron expression representing the grouped events' frequency.
     */
    protected string $expression = '* * * * *';

    /**
     * How often to repeat the grouped events during a minute.
     */
    protected ?int $repeatSeconds = null;

    /**
     * The timezone the grouped events' date should be evaluated on.
     */
    protected DateTimeZone|string $timezone;

    /**
     * The user the grouped events should run as.
     */
    protected ?string $user;

    /**
     * The list of environments the grouped events should run under.
     *
     * @var string[]
     */
    protected array $environments;

    /**
     * Indicates if the grouped events should run in maintenance mode.
     */
    protected bool $evenInMaintenanceMode;

    /**
     * Indicates if the grouped events should not overlap itself.
     */
    protected bool $withoutOverlapping;

    /**
     * Indicates if the grouped events should only be allowed to run on one server for each cron expression.
     */
    protected bool $onOneServer;

    /**
     * The number of minutes the grouped events' mutex should be valid.
     */
    protected int $expiresAt;

    /**
     * Indicates if the grouped events should run in the background.
     */
    protected bool $runInBackground;

    /**
     * The array of filter callbacks.
     *
     * @var array<int, Closure|bool>
     */
    protected array $filters = [];

    /**
     * The array of reject callbacks.
     *
     * @var array<int, Closure|bool>
     */
    protected array $rejects = [];

    /**
     * Create a new schedule group instance.
     */
    public function __construct(
        protected Schedule $schedule,
        protected Closure $onRegister
    ) {
    }

    /**
     * Register scheduled tasks within the current group.
     */
    public function schedules(callable $callback): void
    {
        $callback($this->schedule);

        ($this->onRegister)();
    }

    /**
     * Merge the group's attributes to the given event.
     */
    public function mergeAttributes(Event $event): void
    {
        $event->expression = $this->expression;
        $event->repeatSeconds = $this->repeatSeconds;

        if (isset($this->withoutOverlapping) && $this->withoutOverlapping) {
            $event->withoutOverlapping($this->expiresAt);
        }

        // Merge the filter callbacks into the event.
        foreach ($this->filters as $filter) {
            $event->when($filter);
        }

        // Merge the reject callbacks into the event.
        foreach ($this->rejects as $reject) {
            $event->skip($reject);
        }

        // Loop through the attributes and only set the ones that were set on the group.
        foreach ($this->attributes() as $attribute) {
            if (isset($this->{$attribute})) {
                $event->{$attribute} = $this->{$attribute};
            }
        }
    }

    /**
     * Set which user the grouped events should be run as.
     */
    public function user(?string $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Limit the environments the grouped events should run in.
     *
     * @param  array|mixed  $environments
     */
    public function environments($environments): self
    {
        $this->environments = is_array($environments) ? $environments : func_get_args();

        return $this;
    }

    /**
     * Allow the grouped events to only run on one server for each cron expression.
     */
    public function onOneServer(): self
    {
        $this->onOneServer = true;

        return $this;
    }

    /**
     * State that the grouped events should run in the background.
     */
    public function runInBackground(): self
    {
        $this->runInBackground = true;

        return $this;
    }

    /**
     * State that the grouped events should run even in maintenance mode.
     */
    public function evenInMaintenanceMode(): self
    {
        $this->evenInMaintenanceMode = true;

        return $this;
    }

    /**
     * Do not allow the grouped events to overlap each other.
     * The expiration time of the underlying cache lock may be specified in minutes.
     */
    public function withoutOverlapping(int $expiresAt = 1440): self
    {
        $this->withoutOverlapping = true;

        $this->expiresAt = $expiresAt;

        return $this;
    }

    /**
     * Register a callback to further filter the grouped events.
     */
    public function when(Closure|bool $callback): self
    {
        $this->filters[] = $callback;

        return $this;
    }

    /**
     * Register a callback to further filter the grouped events.
     */
    public function skip(Closure|bool $callback): self
    {
        $this->rejects[] = $callback;

        return $this;
    }

    /**
     * List of attributes that should be merged onto the events.
     * @return string[]
     */
    protected function attributes(): array
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
