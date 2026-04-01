<?php

namespace Illuminate\Queue\Jobs;

use Illuminate\Support\Carbon;

class InspectedJob
{
    /**
     * Create a new inspected job instance.
     *
     * @param  string|null  $name  The display name of the job.
     * @param  string|null  $queue  The name of the queue the job belongs to.
     * @param  int  $attempts  The number of times the job has been attempted.
     * @param  string|null  $uuid  The unique identifier for the job.
     * @param  \Illuminate\Support\Carbon|null  $createdAt  The date and time the job was created.
     */
    public function __construct(
        public readonly ?string $name,
        public readonly ?string $queue,
        public readonly int $attempts,
        public readonly ?string $uuid,
        public readonly ?Carbon $createdAt,
    ) {
    }

    /**
     * Create a new instance from a raw job payload.
     *
     * @param  string  $payload  The raw JSON job payload.
     * @param  string  $queue  The name of the queue the job belongs to.
     * @param  int|null  $attempts  The number of times the job has been attempted.
     * @return static
     */
    public static function fromPayload(string $payload, string $queue, ?int $attempts = null): static
    {
        $decoded = json_decode($payload, true);

        return new static(
            name: $decoded['displayName'] ?? null,
            queue: $queue,
            attempts: $attempts ?? $decoded['attempts'] ?? 0,
            uuid: $decoded['uuid'] ?? null,
            createdAt: isset($decoded['createdAt']) ? Carbon::createFromTimestamp($decoded['createdAt']) : null,
        );
    }
}
