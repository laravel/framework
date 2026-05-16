<?php

namespace Illuminate\Console\Scheduling;

/**
 * @mixin \Illuminate\Console\Scheduling\Schedule
 */
class PendingEventAttributes
{
    use ManagesAttributes, ManagesFrequencies;

    /**
     * Event lifecycle and output methods that should be deferred and replayed on each event in the group.
     *
     * @var array<int, string>
     */
    protected const DEFERRED_EVENT_METHODS = [
        'before',
        'after',
        'then',
        'thenWithOutput',
        'onSuccess',
        'onSuccessWithOutput',
        'onFailure',
        'onFailureWithOutput',
        'pingBefore',
        'pingBeforeIf',
        'thenPing',
        'thenPingIf',
        'pingOnSuccess',
        'pingOnSuccessIf',
        'pingOnFailure',
        'pingOnFailureIf',
        'sendOutputTo',
        'appendOutputTo',
        'emailOutputTo',
        'emailWrittenOutputTo',
        'emailOutputOnFailure',
    ];

    /**
     * The recorded macro and deferred method calls to replay on each event.
     *
     * @var array<int, array{string, array}>
     */
    protected array $macros = [];

    /**
     * Create a new pending event attributes instance.
     */
    public function __construct(
        protected Schedule $schedule,
    ) {
    }

    /**
     * Do not allow the event to overlap each other.
     *
     * The expiration time of the underlying cache lock may be specified in minutes.
     *
     * @param  int  $expiresAt
     * @param  bool  $releaseOnTerminationSignals
     * @return $this
     */
    public function withoutOverlapping($expiresAt = 1440, $releaseOnTerminationSignals = true)
    {
        $this->withoutOverlapping = true;

        $this->expiresAt = $expiresAt;

        $this->releaseOnTerminationSignals = $releaseOnTerminationSignals;

        return $this;
    }

    /**
     * Merge the current attributes into the given event.
     */
    public function mergeAttributes(Event $event): void
    {
        $event->expression = $this->expression;
        $event->repeatSeconds = $this->repeatSeconds;

        if ($this->description !== null) {
            $event->name($this->description);
        }

        if ($this->timezone !== null) {
            $event->timezone($this->timezone);
        }

        if ($this->user !== null) {
            $event->user = $this->user;
        }

        if (! empty($this->environments)) {
            $event->environments($this->environments);
        }

        if ($this->evenInMaintenanceMode) {
            $event->evenInMaintenanceMode();
        }

        if ($this->evenWhenPaused) {
            $event->evenWhenPaused();
        }

        if ($this->withoutOverlapping) {
            $event->withoutOverlapping($this->expiresAt, $this->releaseOnTerminationSignals);
        }

        if ($this->onOneServer) {
            $event->onOneServer();
        }

        if ($this->runInBackground) {
            $event->runInBackground();
        }

        foreach ($this->filters as $filter) {
            $event->when($filter);
        }

        foreach ($this->rejects as $reject) {
            $event->skip($reject);
        }

        foreach ($this->macros as [$method, $parameters]) {
            $event->{$method}(...$parameters);
        }
    }

    /**
     * Proxy missing methods onto the underlying schedule.
     */
    public function __call(string $method, array $parameters): mixed
    {
        if (Event::hasMacro($method) || in_array($method, static::DEFERRED_EVENT_METHODS, true)) {
            $this->macros[] = [$method, $parameters];

            return $this;
        }

        return $this->schedule->{$method}(...$parameters);
    }
}
