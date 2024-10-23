<?php

namespace Illuminate\Database\Eloquent\Concerns;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;

trait HasUuids
{
    use HasUniqueStringIds;

    /**
     * Generate a new UUID for the model.
     *
     * @return string
     */
    public function newUniqueId()
    {
        return (string) Str::orderedUuid();
    }

    /**
     * Determine if value is a valid UUID.
     *
     * @param  mixed  $value
     * @return bool
     */
    protected function isValidKey($value): bool
    {
        return Str::isUuid($value);
    }
}
