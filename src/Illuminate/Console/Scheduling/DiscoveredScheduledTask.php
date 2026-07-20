<?php

namespace Illuminate\Console\Scheduling;

use Illuminate\Console\Scheduling\Attributes\Scheduled;

class DiscoveredScheduledTask
{
    /**
     * Create a discovered scheduled task.
     *
     * @param  class-string  $class
     * @param  string  $method
     * @param  Scheduled  $schedule
     */
    public function __construct(
        public string $class,
        public string $method,
        public Scheduled $schedule,
    ) {
        //
    }

    /**
     * Get the task name.
     *
     * @return string
     */
    public function name(): string
    {
        return $this->schedule->name
            ?? $this->class.'@'.$this->method;
    }

    /**
     * Convert the task into a cacheable array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'class' => $this->class,
            'method' => $this->method,
            'schedule' => $this->schedule->toArray(),
        ];
    }

    /**
     * Create a discovered task from cached data.
     *
     * @param  array<string, mixed>  $data
     * @return static
     */
    public static function fromArray(array $data): static
    {
        return new static(
            class: $data['class'],
            method: $data['method'],
            schedule: Scheduled::fromArray($data['schedule']),
        );
    }
}
