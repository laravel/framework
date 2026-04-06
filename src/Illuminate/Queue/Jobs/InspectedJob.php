<?php

namespace Illuminate\Queue\Jobs;

use Illuminate\Support\Carbon;

class InspectedJob
{
    /**
     * Create a new inspected job instance.
     *
     * @param  string|null  $uuid  The unique identifier for the job.
     * @param  string|null  $name  The display name of the job.
     * @param  int  $attempts  The number of times the job has been attempted.
     * @param  \Illuminate\Support\Carbon|null  $createdAt  The date and time the job was created.
     */
    public function __construct(
        public readonly ?string $uuid,
        public readonly ?string $name,
        public readonly int $attempts,
        public readonly ?Carbon $createdAt,
    ) {
    }

    /**
     * Create a new instance from a raw job payload.
     *
     * @param  string  $payload  The raw JSON job payload.
     * @param  int|null  $attempts  The number of times the job has been attempted.
     * @return static
     */
    public static function fromPayload(string $payload, ?int $attempts = null): static
    {
        $decoded = json_decode($payload, true);

        return new static(
            uuid: $decoded['uuid'] ?? null,
            name: $decoded['displayName'] ?? null,
            attempts: $attempts ?? $decoded['attempts'] ?? 0,
            createdAt: isset($decoded['createdAt']) ? Carbon::createFromTimestamp($decoded['createdAt']) : null,
        );
    }
}
