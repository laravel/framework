<?php

namespace Illuminate\Queue\Failed;

use Illuminate\Support\Str;

trait ExtractsFailedJobUuid
{
    /**
     * Extract the UUID from the given failed job payload.
     *
     * @param  string  $payload
     * @return string
     */
    protected function extractUuid($payload)
    {
        $decoded = json_validate($payload)
            ? json_decode($payload, true, flags: JSON_THROW_ON_ERROR)
            : null;

        if (is_array($decoded) && is_string($decoded['uuid'] ?? null) && $decoded['uuid'] !== '') {
            return $decoded['uuid'];
        }

        return (string) Str::uuid();
    }
}
