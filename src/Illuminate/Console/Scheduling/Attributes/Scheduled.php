<?php

namespace Illuminate\Console\Scheduling\Attributes;

use Attribute;
use InvalidArgumentException;

#[Attribute(
    Attribute::TARGET_CLASS
    | Attribute::TARGET_METHOD
    | Attribute::IS_REPEATABLE
)]
class Scheduled
{
    /**
     * Create a new scheduled attribute instance.
     *
     * @param  string  $frequency
     * @param  array<int, mixed>  $arguments
     * @param  string|null  $at
     * @param  string|null  $timezone
     * @param  int|false  $withoutOverlapping
     * @param  bool  $onOneServer
     * @param  bool  $evenInMaintenanceMode
     * @param  array<int, string>  $environments
     * @param  string|null  $name
     */
    public function __construct(
        public string $frequency = 'daily',
        public array $arguments = [],
        public ?string $at = null,
        public ?string $timezone = null,
        public int|false $withoutOverlapping = false,
        public bool $onOneServer = false,
        public bool $evenInMaintenanceMode = false,
        public array $environments = [],
        public ?string $name = null,
    ) {
        if ($this->frequency === '') {
            throw new InvalidArgumentException(
                'The scheduled task frequency may not be empty.'
            );
        }

        if (
            $this->withoutOverlapping !== false
            && $this->withoutOverlapping < 1
        ) {
            throw new InvalidArgumentException(
                'The scheduled task overlap expiration must be at least one minute.'
            );
        }
    }

    /**
     * Convert the attribute into a cacheable array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'frequency' => $this->frequency,
            'arguments' => $this->arguments,
            'at' => $this->at,
            'timezone' => $this->timezone,
            'withoutOverlapping' => $this->withoutOverlapping,
            'onOneServer' => $this->onOneServer,
            'evenInMaintenanceMode' => $this->evenInMaintenanceMode,
            'environments' => $this->environments,
            'name' => $this->name,
        ];
    }

    /**
     * Create an attribute from cached data.
     *
     * @param  array<string, mixed>  $data
     * @return static
     */
    public static function fromArray(array $data): static
    {
        return new static(
            frequency: $data['frequency'] ?? 'daily',
            arguments: $data['arguments'] ?? [],
            at: $data['at'] ?? null,
            timezone: $data['timezone'] ?? null,
            withoutOverlapping: $data['withoutOverlapping'] ?? false,
            onOneServer: $data['onOneServer'] ?? false,
            evenInMaintenanceMode: $data['evenInMaintenanceMode'] ?? false,
            environments: $data['environments'] ?? [],
            name: $data['name'] ?? null,
        );
    }
}
