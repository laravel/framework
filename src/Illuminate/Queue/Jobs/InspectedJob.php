<?php

namespace Illuminate\Queue\Jobs;

use Illuminate\Container\Container;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Support\Carbon;

class InspectedJob
{
    /**
     * Create a new inspected job instance.
     *
     * @param  string|null  $uuid  The unique identifier for the job.
     * @param  string|null  $name  The display name of the job.
     * @param  int  $attempts  The number of times the job has been attempted.
     * @param  array  $payload
     * @param  \Illuminate\Support\Carbon|null  $createdAt  The date and time the job was created.
     */
    public function __construct(
        public readonly ?string $uuid,
        public readonly ?string $name,
        public readonly int $attempts,
        public readonly array $payload = [],
        public readonly ?Carbon $createdAt = null,
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

        $commandName = $decoded['data']['commandName'] ?? null;
        $command = $decoded['data']['command'] ?? null;

        if ($commandName && $command) {
            if (str_starts_with($command, 'O:')) {
                $decoded['data']['command'] = unserialize($command);
            } elseif (is_subclass_of($commandName, ShouldBeEncrypted::class) && Container::getInstance()->bound(Encrypter::class)) {
                $decoded['data']['command'] = unserialize(Container::getInstance()[Encrypter::class]->decrypt($command));
            }
        }

        return new static(
            uuid: $decoded['uuid'] ?? null,
            name: $decoded['displayName'] ?? null,
            attempts: $attempts ?? $decoded['attempts'] ?? 0,
            payload: $decoded,
            createdAt: isset($decoded['createdAt']) ? Carbon::createFromTimestamp($decoded['createdAt']) : null,
        );
    }
}
