<?php

namespace Illuminate\Console\Scheduling;

/**
 * @mixin \Illuminate\Console\Scheduling\Schedule
 */
class PendingEventAttributes
{
    use ManagesAttributes, ManagesFrequencies;

    /**
     * Create a new pending event attributes instance.
     */
    public function __construct(
        protected Schedule $schedule,
    ) {
    }

    /**
     * Merge the current attributes into the given event.
     */
    public function mergeAttributes(Event $event): void
    {
        $event->expression = $this->expression;
        $event->repeatSeconds = $this->repeatSeconds;

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

        if ($this->withoutOverlapping) {
            $event->withoutOverlapping($this->expiresAt);
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
    }

    /**
     * Proxy missing methods onto the underlying schedule.
     */
    public function __call(string $method, array $parameters): mixed
    {
        return $this->schedule->{$method}(...$parameters);
    }
}
